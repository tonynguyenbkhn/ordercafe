<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering;

use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Admin\Notices;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Plugin\Premium_Plugin;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Util;

/**
 * The main plugin class. Responsible for setting up to core plugin services.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Plugin extends Premium_Plugin {

	const NAME    = 'WooCommerce Restaurant Ordering';
	const ITEM_ID = 236796;

	/**
	 * The notices object
	 *
	 * @var Notices
	 */
	private $notices;

	/**
	 * Constructs and initialize the main plugin class.
	 *
	 * @param string $file    The path to the main plugin file.
	 * @param string $version The current plugin version.
	 */
	public function __construct( $file = null, $version = '1.0' ) {
		parent::__construct(
			[
				'name'               => self::NAME,
				'id'                 => self::ITEM_ID,
				'version'            => $version,
				'file'               => $file,
				'is_woocommerce'     => true,
				'settings_path'      => 'admin.php?page=wc-settings&tab=restaurant-ordering',
				'documentation_path' => 'kb-categories/wro-kb'
			]
		);
	}

	public function add_services() {
		$this->add_service( 'plugin_setup', new Plugin_Setup( $this->get_file(), $this ), true );
		$this->add_service( 'admin', new Admin\Admin_Controller( $this ) );
		$this->add_service( 'plugin_upgrade', new Admin\Plugin_Upgrade_Check( $this ) );
		$this->add_service( 'wizard', new Admin\Wizard\Setup_Wizard( $this ) );
		
		$rest_controller = new Rest\Rest_Controller();

		$this->add_service( 'shortcodes', new Shortcodes() );
		$this->add_service( 'frontend_scripts', new Frontend_Scripts( $this, $rest_controller ) );
		$this->add_service( 'cart_handler', new Cart\Cart_Validation() );
		$this->add_service( 'rest_controller', $rest_controller );
		$this->add_service( 'template_handler', new Template_Handler() );
		$this->add_service( 'product_addons', new Integration\Product_Addons() );
		$this->add_service( 'product_table', new Integration\Product_Table() );
	}

	/**
	 * Check the WP Requirements are met
	 *
	 * @return bool
	 */
	public function check_wp_requirements(): bool {
		global $wp_version;

		if ( version_compare( $wp_version, '5.2', '<' ) ) {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				// translators: %s: Plugin name.
				wp_die( esc_html__( 'The %s plugin requires WordPress 5.2 or greater. Please update your WordPress installation first.', 'woocommerce-restaurant-ordering' ), esc_html( self::NAME ) );
			}

			if ( is_admin() ) {
				$can_update_core = current_user_can( 'update_core' );

				$this->notices->add(
					'wro_invalid_wp_version',
					'',
					sprintf(
					/* translators: %1$s: Plugin name. %2$s: Update Core <a> tag open. %3$s: <a> tag close.  */
						__( 'The %1$s plugin requires WordPress 5.2 or greater. Please %2$supdate%3$s your WordPress installation.', 'woocommerce-restaurant-ordering' ),
						'<strong>' . self::NAME . '</strong>',
						( $can_update_core ? sprintf( '<a href="%s">', esc_url( self_admin_url( 'update-core.php' ) ) ) : '' ),
						( $can_update_core ? '</a>' : '' )
					),
					[
						'type'       => 'error',
						'capability' => 'install_plugins',
						'screens'    => [ 'plugins', 'woocommerce_page_wc-settings' ]
					]
				);
			}
			return false;
		}

		return true;
	}

	/**
	 * Check the WooCommerce requirements are met.
	 *
	 * @return bool
	 */
	public function check_wc_requirements(): bool {
		if ( ! class_exists( 'WooCommerce' ) ) {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				wp_die( esc_html__( 'Please install WooCommerce in order to use WooCommerce Restaurant Ordering.', 'woocommerce-restaurant-ordering' ) );
			}

			if ( is_admin() ) {
				$this->notices->add(
					'wro_woocommerce_missing',
					'',
					/* translators: %1$s: Install WooCommerce <a> tag open. %2$s: <a> tag close.  */
					sprintf( __( 'Please %1$sinstall WooCommerce%2$s in order to use WooCommerce Restaurant Ordering.', 'woocommerce-restaurant-ordering' ), Util::format_link_open( 'https://woocommerce.com/', true ), '</a>' ),
					[
						'type'       => 'error',
						'capability' => 'install_plugins',
						'screens'    => [ 'plugins' ],
					]
				);
			}

			return false;
		}

		global $woocommerce;

		if ( version_compare( $woocommerce->version, '5.9', '<' ) ) {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				// translators: %s: Plugin name.
				wp_die( esc_html__( 'The %s plugin requires WooCommerce 5.9 or greater. Please update your WooCommerce setup first.', 'woocommerce-restaurant-ordering' ), esc_html( self::NAME ) );
			}

			if ( is_admin() ) {
				$this->notices->add(
					'wro_invalid_wc_version',
					'',
					/* translators: %1$s: Plugin name. */
					sprintf( __( 'The %1$s plugin requires WooCommerce 5.9 or greater. Please update your WooCommerce setup first.', 'woocommerce-restaurant-ordering' ), self::NAME ),
					[
						'type'       => 'error',
						'capability' => 'install_plugins',
						'screens'    => [ 'plugins', 'woocommerce_page_wc-settings' ],
					]
				);
			}

			return false;
		}

		return true;
	}

}
