<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Admin;

use Barn2\Plugin\WC_Restaurant_Ordering\Settings;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\WooCommerce\Admin\Custom_Settings_Fields;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\WooCommerce\Admin\Plugin_Promo;
use WC_Settings_Page;

// Bail if WooCommerce settings page class not found.
if ( ! class_exists( 'WC_Settings_Page' ) ) {
	return;
}

/**
 * The WooCommerce settings page. Appears under the main WooCommerce -> Settings menu.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Settings_Page extends WC_Settings_Page implements Registerable {

	/**
	 * @var string The settings page ID.
	 */
	protected $id = 'restaurant-ordering';

	/**
	 * @var Licensed_Plugin The plugin object.
	 */
	private $plugin;

	public function __construct( Licensed_Plugin $plugin ) {
		parent::__construct();

		$this->label  = __( 'Restaurant Ordering', 'woocommerce-restaurant-ordering' );
		$this->plugin = $plugin;
	}

	public function register() {
		Util::register_services(
			[
				new Custom_Settings_Fields( $this->plugin ),
				new Opening_Hours_Setting( $this->plugin ),
				new Plugin_Promo( $this->plugin, $this->id ),
			]
		);

		add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
	}

	/**
	 * Get the full list of settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		global $current_section;

		$settings = array_merge(
			Settings::get_general_settings( $this->plugin ),
			Settings::get_order_form_settings(),
			Settings::get_restaurant_info_settings(),
			Settings::get_availability_settings(),
			Settings::get_uninstall_settings( $this->plugin )
		);

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );
	}

	public function load_scripts() {
		wp_enqueue_style( 'wro-settings', $this->plugin->get_dir_url() . 'assets/css/admin/settings.css', [], $this->plugin->get_version() );
		wp_enqueue_script(
			'wro-settings',
			$this->plugin->get_dir_url() . 'assets/js/admin/settings.js',
			[
				'jquery',
				'jquery-ui-sortable',
				'selectWoo',
				'underscore',
			],
			$this->plugin->get_version(),
			true
		);
	}

	/**
	 * Save the plugin settings.
	 *
	 * @see WC_Settings_Page::save()
	 */
	public function save() {
		parent::save();
		$order_page = Settings::get_setting( 'menu_page' );

		// Don't do anything if no order page set.
		if ( ! $order_page ) {
			return;
		}

		$order_page = (int) $order_page;

		// Don't add the shortcode to the shop page - this is handled separately.
		if ( $order_page === wc_get_page_id( 'shop' ) ) {
			return;
		}

		Settings::add_shortcode_to_page( $order_page );
	}
}
