<?php
namespace Barn2\Plugin\WC_Restaurant_Ordering\Cart;

use Barn2\Plugin\WC_Restaurant_Ordering\Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Service\Premium_Service;

/**
 * Handles validation of the WooCommerce cart for restaurant orders.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Cart_Validation implements Premium_Service, Registerable {

	const CART_ITEM_KEY = 'wro_restaurant_item';

	public function register() {
		add_action( 'wc_restaurant_ordering_before_add_to_cart', [ $this, 'add_cart_item_data_hook' ] );
		add_action( 'wc_restaurant_ordering_after_add_to_cart', [ $this, 'remove_cart_item_data_hook' ] );
		add_action( 'woocommerce_check_cart_items', [ $this, 'check_cart_items' ] );
	}

	public function add_cart_item_data_hook() {
		add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_cart_item_data' ] );
	}

	public function remove_cart_item_data_hook() {
		remove_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_cart_item_data' ] );
	}

	public function add_cart_item_data( $cart_item_data ) {
		// Add custom data for restaurant items, so we know which items were added to the cart from the restaurant menu.
		$cart_item_data[ self::CART_ITEM_KEY ] = true;
		return $cart_item_data;
	}

	public function check_cart_items() {
		if ( ! apply_filters( 'wc_restaurant_ordering_handle_cart_validation', true ) ) {
			return;
		}

		if ( Util::is_accepting_orders() ) {
			return;
		}

		// Restaurant is not accepting orders (i.e. closed), so now check the cart contents.
		$cart_contents = WC()->cart->get_cart();

		if ( ! empty( $cart_contents ) ) {
			// Get all restaurant items from the cart. If there are any found, we show an error.
			$restuarant_items = array_column( $cart_contents, self::CART_ITEM_KEY );

			if ( ! empty( $restuarant_items ) ) {
				wc_add_notice( Util::get_restaurant_closed_error_message(), 'error' );
			}
		}

	}

}
