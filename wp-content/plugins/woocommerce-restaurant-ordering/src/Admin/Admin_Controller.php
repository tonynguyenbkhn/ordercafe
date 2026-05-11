<?php
namespace Barn2\Plugin\WC_Restaurant_Ordering\Admin;

use Barn2\Plugin\WC_Restaurant_Ordering\Admin\Wizard\Setup_Wizard;
use Barn2\Plugin\WC_Restaurant_Ordering\Settings;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Service\Service_Container;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Plugin\Admin\Admin_Links;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Service\Standard_Service;

/**
 * Handles the admin configuration.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Admin_Controller implements Registerable, Standard_Service {

	use Service_Container;

	private $plugin;

	public function __construct( Licensed_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function add_services() {
		$this->add_service( 'admin_links', new Admin_Links( $this->plugin ) );
	}

	public function register() {
		$this->register_services();
		$this->start_all_services();

		// Add settings page.
		add_filter( 'woocommerce_get_settings_pages', [ $this, 'add_settings_page' ], 20 );

		// Add a post display state for restaurant order page.
		add_filter( 'display_post_states', [ $this, 'add_display_post_states' ], 10, 2 );
	}

	public function add_display_post_states( $post_states, $post ) {
		if ( Settings::get_setting( 'menu_page' ) === $post->ID ) {
			$post_states['wro_restaurant_page'] = __( 'Restaurant Page', 'woocommerce-restaurant-ordering' );
		}

		return $post_states;
	}

	public function add_settings_page( $settings ) {
		$settings_page = new Settings_Page( $this->plugin );
		$settings_page->register();

		$settings[] = $settings_page;
		return $settings;
	}
}
