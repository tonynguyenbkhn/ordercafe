<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering;

use Barn2\Plugin\WC_Restaurant_Ordering\Menu\Menu_Options;
use Barn2\Plugin\WC_Restaurant_Ordering\Menu\Restaurant_Menu;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Service\Premium_Service;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Util;

/**
 * Handles the restaurant ordering shortcodes.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Shortcodes implements Premium_Service, Registerable {

	const MENU_SHORTCODE = 'restaurant_ordering';

	public function register() {
		add_shortcode( self::MENU_SHORTCODE, [ $this, 'menu_shortcode' ] );
	}

	public function menu_shortcode( $atts, $content = '' ) {
		$menu = new Restaurant_Menu( new Menu_Options( (array) $atts ) );
		return $menu->render();
	}

}
