<?php
namespace Barn2\Plugin\WC_Restaurant_Ordering;

use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Setup_Wizard\Starter;
use Barn2\Plugin\WC_Restaurant_Ordering\Shortcodes;
use	Barn2\Plugin\WC_Restaurant_Ordering\Settings;
use	Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Plugin\Plugin_Activation_Listener;
use	Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Service\Standard_Service;

/**
 * Sets up the plugin on install.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Plugin_Setup implements Standard_Service, Plugin_Activation_Listener, Registerable {

	private $file;

	/**
	 * Wizard starter.
	 *
	 * @var Starter
	 */
	private $starter;

	/**
	 * Plugin instance
	 *
	 * @var Licensed_Plugin
	 */
	private $plugin;

	public function __construct( $file, Licensed_Plugin $plugin ) {
		$this->file = $file;
		$this->plugin  = $plugin;
		$this->starter = new Starter( $this->plugin );
	}

	public function register() {
		register_activation_hook( $this->file, [ $this, 'on_activate' ] );
		add_action( 'admin_init', [ $this, 'after_plugin_activation' ] );
	}

	public function on_activate( $network_wide ) {
		$this->setup();
	}

	public function on_deactivate( $network_wide ) {
		// We do, nothing.
	}

	public function setup() {
		$page_id = $this->create_restaurant_page();

		if ( $page_id ) {
			$this->set_restaurant_page_setting( $page_id );
		}
		
		if ( $this->starter->should_start() ) {
			$this->starter->create_transient();
		}
	}

	private function create_restaurant_page() {
		// Bail if we've already created the page or if order page has been selected in settings.
		if ( get_option( 'wro_restaurant_page_created', false ) || Settings::get_setting( 'menu_page', false ) ) {
			return false;
		}

		$page_id = Util::create_page(
			'restaurant-order',
			'wro_menu_page',
			__( 'Restaurant Order', 'woocommerce-restaurant-ordering' ),
			'<!-- wp:shortcode -->[' . Shortcodes::MENU_SHORTCODE . ']<!-- /wp:shortcode -->'
		);

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( 'wro_restaurant_page_created', true, 'no' );
			return $page_id;
		}

		return false;
	}

	private function set_restaurant_page_setting( $page_id ) {
		update_option( 'wro_menu_page', $page_id, 'no' );
	}

	/**
	 * Detect the transient and redirect to wizard.
	 *
	 * @return void
	 */
	public function after_plugin_activation() {
		if ( ! $this->starter->detected() ) {
			return;
		}

		$this->starter->delete_transient();
		$this->starter->redirect();
	}

}
