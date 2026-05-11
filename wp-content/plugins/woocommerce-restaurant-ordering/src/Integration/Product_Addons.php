<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Integration;

use Barn2\Plugin\WC_Restaurant_Ordering\Menu\Menu_Options;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Service\Premium_Service;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Util;
use WC_Product;
use WC_Product_Addons_Helper;

/**
 * Handles the WooCommerce Product Addons integration.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Addons implements Premium_Service, Registerable {

	/**
	 * Register the integrations for Product Addons.
	 */
	public function register() {
		if ( ! Util::is_product_addons_active() ) {
			return;
		}

		add_action( 'wc_restaurant_ordering_load_scripts', [ $this, 'load_scripts' ] );
		add_filter( 'wc_restaurant_ordering_product_order_type', [ $this, 'set_order_type' ], 10, 3 );
		add_filter( 'wc_restaurant_ordering_product_order_type_checked', [ $this, 'set_checked_order_type' ], 10, 2 );
		add_filter( 'wc_restaurant_ordering_modal_data', [ $this, 'add_modal_data' ], 10, 2 );

		// Temp: Workaround for bug in Addons 4.7.0 with Storefront theme (JS bug).
		if ( defined( 'WC_PRODUCT_ADDONS_VERSION' ) && '4.7.0' === WC_PRODUCT_ADDONS_VERSION ) {
			add_filter( 'storefront_handheld_footer_bar_links', [ $this, 'storefront_remove_handheld_footer_bar_cart_link' ] );
		}
	}

	/**
	 * Load the scripts required for the WooCommerce Product Addons integration.
	 */
	public function load_scripts() {
		// First, we check the function to register the scripts exists.
		if ( ! isset( $GLOBALS['Product_Addon_Display'] ) || ! method_exists( $GLOBALS['Product_Addon_Display'], 'addon_scripts' ) ) {
			return;
		}

		// Product Addons has a dependency on jquery tipTip which isn't passed to the deps array for the addons script, so we need to load it.
		if ( ! wp_script_is( 'jquery-tiptip', 'registered' ) ) {
			wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip.min.js', [ 'jquery' ], WC_VERSION, true );
		}

		wp_enqueue_script( 'jquery-tiptip' );

		$GLOBALS['Product_Addon_Display']->addon_scripts();
	}

	/**
	 * If using WooCommerce Product Addons we don't know at this stage whether there are addons for the product, so we return 'check'.
	 * If this product is ordered, we then perform the full check on the product and either add the product (if there are no addons) or show the
	 * modal if there are.
	 *
	 * @param string $order_type    The order type before this filter ran
	 * @param WC_Product $product   The product
	 * @param Menu_Options $options The menu options
	 * @return string The order type - OT_QUICK, OT_LIGHTBOX, or OT_CHECK
	 */
	public function set_order_type( $order_type, WC_Product $product, Menu_Options $options ) {
		if ( Menu_Options::OT_QUICK === $order_type ) {
			// Set to 'check' option if Product Addons is active - the product may or may not have addons.
			$order_type = Menu_Options::OT_CHECK;
		}

		return $order_type;
	}

	/**
	 * Sets the order type during the REST request, if 'OT_CHECK' was used for the product.
	 *
	 * @param string $order_type  The order type before this filter ran
	 * @param WC_Product $product The product
	 * @return string The order type - OT_QUICK or OT_LIGHTBOX
	 */
	public function set_checked_order_type( $order_type, WC_Product $product ) {
		// Set to lightbox if product has Product Addons.
		if ( Menu_Options::OT_QUICK === $order_type ) {
			$product_addons = WC_Product_Addons_Helper::get_product_addons( $product->get_id() );

			if ( ! empty( $product_addons ) ) {
				// Products with addons should always open in the lightbox.
				$order_type = Menu_Options::OT_LIGHTBOX;
			}
		}

		return $order_type;
	}

	/**
	 * Add the HTML for the addons to the lightbox.
	 *
	 * @param array $data         The modal data before this filter ran
	 * @param WC_Product $product The product
	 * @return array The updated modal data
	 */
	public function add_modal_data( $data, WC_Product $product ) {
		if ( ! empty( $GLOBALS['Product_Addon_Display'] ) ) {
			ob_start();
			$GLOBALS['Product_Addon_Display']->display( $product->get_id() );
			$data['options'] .= ob_get_clean();
		}

		return $data;
	}

	/**
	 * Remove 'cart' from mobile menu as it conflicts with Product Addons 4.7.0.
	 *
	 * @param array $links The links.
	 * @return array The links.
	 */
	public function storefront_remove_handheld_footer_bar_cart_link( $links ) {
		if ( isset( $links['cart'] ) ) {
			unset( $links['cart'] );
		}
		return $links;
	}

}
