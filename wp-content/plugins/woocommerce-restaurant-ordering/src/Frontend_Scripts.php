<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering;

use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Plugin\Plugin;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Rest\Rest_Server;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Service\Premium_Service;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Util as Lib_Util;

/**
 * Manages registering and loading of the scripts for the Ordering package.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Frontend_Scripts implements Premium_Service, Registerable {

	const MENU_SCRIPT_HANDLE = 'wc-restaurant-menu';

	private $plugin;
	private $rest_server;
	private $template_loader;

	private $scripts_loaded = false;

	public function __construct( Plugin $plugin, Rest_Server $rest_server ) {
		$this->plugin          = $plugin;
		$this->rest_server     = $rest_server;
		$this->template_loader = Template_Loader_Factory::create();
	}

	public function add_inline_styles( $inline_styles ) {
		if ( $inline_styles ) {
			wp_add_inline_style( self::MENU_SCRIPT_HANDLE, $inline_styles );
		}
	}

	public function load_scripts() {
		if ( ! $this->should_load_scripts() ) {
			return;
		}

		wp_enqueue_style( self::MENU_SCRIPT_HANDLE );
		wp_enqueue_script( self::MENU_SCRIPT_HANDLE );

		add_action( 'wp_footer', [ $this, 'render_menu_js_templates' ] );
		do_action( 'wc_restaurant_ordering_load_scripts' );

		$this->scripts_loaded = true;
	}

	public function register() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
	}

	public function register_scripts() {
		$assets_url     = $this->plugin->get_dir_url() . 'assets';
		$script_version = $this->plugin->get_version();
		$suffix         = Lib_Util::get_script_suffix();

		// Register styles
		wp_register_style( self::MENU_SCRIPT_HANDLE, $assets_url . '/css/restaurant-menu.css', [], $script_version );

		// Register scripts
		wp_register_script( 'wc-backbone-modal', WC()->plugin_url() . "/assets/js/admin/backbone-modal{$suffix}.js", [ 'underscore', 'backbone', 'wp-util' ], WC_VERSION );
		wp_register_script( 'accounting', WC()->plugin_url() . "/assets/js/accounting/accounting{$suffix}.js", [ 'jquery' ], '0.4.2', true );

		wp_register_script(
			self::MENU_SCRIPT_HANDLE,
			$assets_url . "/js/restaurant-menu.js",
			[
				'jquery-blockui',
				'wc-backbone-modal',
				'accounting',
				'wc-add-to-cart-variation'
			],
			$script_version,
			true
		);

		$data = sprintf( 'var wc_restaurant_ordering_params = %s;', wp_json_encode( $this->get_script_params() ) );
		wp_add_inline_script( self::MENU_SCRIPT_HANDLE, $data, 'before' );
	}

	private function get_script_params() {
		$script_params = [
			'rest_nonce'            => wp_create_nonce( 'wp_rest' ),
			'rest_url'              => esc_url_raw( rest_url() ),
			'rest_endpoints'        => $this->rest_server->get_endpoints(),
			'show_cart_notice'      => $this->is_cart_notice_enabled(),
			'cart_notice_timeout'   => 2800,
			'refresh_cart'          => apply_filters( 'wc_restaurant_ordering_refresh_cart_fragments', true ),
			'show_stock_in_modal'   => Settings::get_setting( 'modal_stock', true ),
			'nav_scroll_offset'     => apply_filters( 'wc_restaurant_ordering_navigation_scroll_offset', '' ),
			'price_currency_format' => str_replace( [ '%1$s', '%2$s' ], [ '%s', '%v' ], get_woocommerce_price_format() ),
			'price_currency_symbol' => get_woocommerce_currency_symbol(),
			'price_num_decimals'    => wc_get_price_decimals(),
			'price_decimal_sep'     => wc_get_price_decimal_separator(),
			'price_thousand_sep'    => wc_get_price_thousand_separator(),
			'messages'              => [
				'item_cannot_be_loaded'         => __( 'Sorry, this item cannot be loaded.', 'woocommerce-restaurant-ordering' ),
				'item_out_of_stock'             => __( 'Sorry, this item is out of stock.', 'woocommerce-restaurant-ordering' ),
				'item_not_available'            => __( 'Sorry, this item is not available.', 'woocommerce-restaurant-ordering' ),
				'error_adding_item'             => __( 'Sorry, there was a problem ordering this item.', 'woocommerce-restaurant-ordering' ),
				'enter_quantity_greater_than_0' => __( 'Please enter a quantity greater than 0.', 'woocommerce-restaurant-ordering' ),
				'select_all_required_options'   => __( 'Please select all required options.', 'woocommerce-restaurant-ordering' ),
				'address_copied'                => __( 'Copied!', 'woocommerce-restaurant-ordering' )
			],
			'self_cart_refresh'				=> false,
			'products_quantity_init_sync'	=> false,
		];

		if ( apply_filters( 'woocommerce_price_trim_zeros', false ) ) {
			$script_params['price_num_decimals'] = 0;
		}

		return apply_filters( 'wc_restaurant_ordering_script_params', $script_params );
	}

	private function is_cart_notice_enabled() {
		$show_confirmation = Settings::get_setting( 'cart_confirmation', true );
		return apply_filters( 'wc_restaurant_ordering_show_cart_notice', $show_confirmation );
	}

	public function render_menu_js_templates() {
		$this->template_loader->load_template_once(
			'modal/template.php',
			[
				'content' => $this->template_loader->get_template(
					'modal/content.php',
					[
						'buy_button' => $this->template_loader->get_template( 'modal/buy-button.php' )
					]
				)
			]
		);

		$this->template_loader->load_template_once(
			'cart-notice/template.php',
			[
				'cart_notice' => $this->template_loader->get_template( 'cart-notice' )
			]
		);
	}

	private function should_load_scripts() {
		return apply_filters( 'wc_restaurant_ordering_load_frontend_scripts', true ) && ! $this->scripts_loaded;
	}

}
