<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Integration;

use Barn2\Plugin\WC_Product_Table\Table_Shortcode;
use Barn2\Plugin\WC_Product_Table\Util\Settings;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Service\Premium_Service;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Util;

/**
 * Handles the WooCommerce Product Table integration.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Table implements Premium_Service, Registerable {

	/**
	 * Register the integrations for Product Addons.
	 */
	public function register() {
		if ( ! Util::is_barn2_plugin_active('\\Barn2\\Plugin\\WC_Product_Table\\wpt') ) {
			return;
		}

		// Remove product table shortcodes from menu product description and modal description. The add to cart feature
		// of the product table conflict with the cart form in the restaurant modal.
		add_filter( 'wc_restaurant_ordering_modal_description_before_formatting', [ $this, 'remove_tables_from_description' ] );
		add_filter( 'wc_restaurant_ordering_product_description_before_formatting', [ $this, 'remove_tables_from_description' ] );

		// Make sure WPT takes precedence over WRO for Shop page template override.
		add_filter( 'wc_restaurant_ordering_override_woocommerce_shop_page', [ $this, 'maybe_prevent_wro_shop_page_override' ] );
	}

	public function maybe_prevent_wro_shop_page_override( $restaurant_override ) {
		$misc_setings = Settings::get_setting_misc();

		if ( is_shop() ) {
			// If we're overriding the Shop page in WPT, this takes precedence over WRO, so disable the restaurant override.
			if ( isset( $_GET['s'] ) ) {
				if ( ! empty( $misc_setings['search_override'] ) ) {
					$restaurant_override = false;
				}
			} else {
				if ( ! empty( $misc_setings['shop_override'] ) ) {
					$restaurant_override = false;
				}
			}
		}

		return $restaurant_override;
	}

	/**
	 * Remove [product_table] shortcode from modal description content. The add to cart feature of the table will
	 * conflict with the cart form in the restaurant modal.
	 *
	 * @param string $description The modal description
	 * @return string The filtered description
	 */
	public function remove_tables_from_description( string $description ): string {
		return (string) preg_replace( sprintf( '#\[%s.*?\]#', Table_Shortcode::SHORTCODE ), '', $description );
	}

}
