<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Admin;

use Barn2\Plugin\WC_Restaurant_Ordering\Settings;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Service\Standard_Service;

/**
 * Handles any specific requirements when WRO is upgraded from an earlier version.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Plugin_Upgrade_Check implements Registerable, Standard_Service {

	/**
	 * @var Licensed_Plugin The plugin object.
	 */
	private $plugin;

	public function __construct( Licensed_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function register() {
		add_filter( 'wc_restaurant_ordering_menu_default_options', [ $this, 'set_default_menu_options' ], 5 );
	}

	public function set_default_menu_options( $defaults ) {
		$menu_navigation = Settings::get_setting( 'menu_navigation', null );

		return $defaults;
	}

}
