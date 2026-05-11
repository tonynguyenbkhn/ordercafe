<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Rest;

use WP_Error;

/**
 * REST utility functions.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Rest_Util {

	public static function format_errors( $errors ) {
		$errors = array_filter( (array) $errors );

		// Set a fallback error if none is given.
		if ( empty( $errors ) ) {
			$errors = [
				__( 'Sorry, there was a problem ordering this item.', 'woocommerce-restaurant-ordering' )
			];
		}

		// Run filter to check if we should return all errors.
		if ( ! apply_filters( 'wc_restaurant_ordering_show_all_cart_errors', false ) ) {
			$errors = [ reset( $errors ) ];
		}

		// Combine into a single error message.
		$message = implode(
			'',
			array_map(
				function ( $val ) {
					return "<p class=\"notice-text\">{$val}</p>";
				},
				$errors
			)
		);

		// Remove any links from error message (e.g. link to cart)
		return preg_replace( '/<a href=.*?<\/a>/', '', $message );
	}

	public static function validate_product( $product_id ) {
		$product_id = absint( $product_id );
		$product    = wc_get_product( $product_id );

		// Check product exists.
		if ( ! $product ) {
			return new WP_Error(
				'rest_invalid_product_id',
				__( 'This product does not exist.', 'woocommerce-restaurant-ordering' ),
				[ 'status' => 400 ]
			);
		}

		// Check product is visible in shop.
		if ( ! apply_filters( 'wc_restaurant_ordering_show_hidden_products', false ) && ! $product->is_visible() ) {
			return new WP_Error(
				'rest_product_not_visible',
				__( 'This product is not visible.', 'woocommerce-restaurant-ordering' ),
				[ 'status' => 400 ]
			);
		}

		if ( ! $product->is_purchasable() ) {
			return new WP_Error(
				'rest_product_not_purchasable',
				__( 'This product cannot be purchased.', 'woocommerce-restaurant-ordering' ),
				[ 'status' => 400 ]
			);
		}

		if ( post_password_required( $product_id ) ) {
			return new WP_Error(
				'rest_product_password_required',
				__( 'This product is password protected.', 'woocommerce-restaurant-ordering' ),
				[ 'status' => 400 ]
			);
		}

		return $product;
	}

	public static function validate_product_params( array $params ) {
		$params     = array_merge( [ 'product_id' => 0 ], $params );
		$product_id = absint( $params['product_id'] );
		$product    = self::validate_product( $product_id );

		if ( $product instanceof WP_Error ) {
			return $product;
		}

		return array_merge(
			$params,
			[
				'product_id' => $product_id,
				'product'    => $product
			]
		);
	}

	public static function get_order_error_message( WP_Error $error ) {
		_deprecated_function( __METHOD__, '2.0' );
		return __( 'Sorry, this item is not available.', 'woocommerce-restaurant-ordering' );
	}

}
