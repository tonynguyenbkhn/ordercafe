<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Menu;

use WC_Product;

/**
 * Functions for retrieving data for a product in the restaurant menu.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Data {

	/**
	 * Get the display price for a product. Similar to WC_Product->get_price_html() but without any price suffix.
	 *
	 * @param WC_Product $product        The product object.
	 * @param boolean|string $show_range Applies to variable products only. Whether to show the range of prices (e.g. £2.00 - £4.00).
	 *                                   true - show full price range for variable products; false - show minimum price only; 'short' - show minimum price appended with "+".
	 * @param boolean $show_sale_price   Applies if product is on sale. true - show the regular price struck out before the sale price;
	 *                                   false - just show the sale price.
	 * @return string The display price.
	 */
	public static function get_display_price( WC_Product $product, $show_range = true, $show_sale_price = false ) {
		$price = $product->get_price();

		if ( '' === $price ) {
			return '';
		}

		if ( $product->is_type( 'variable' ) ) {
			$prices = $product->get_variation_prices( true );

			if ( empty( $prices['price'] ) ) {
				return '';
			} else {
				$min_price     = current( $prices['price'] );
				$max_price     = end( $prices['price'] );
				$min_reg_price = current( $prices['regular_price'] );
				$max_reg_price = end( $prices['regular_price'] );

				if ( $show_range && $min_price !== $max_price ) {
					if ( true === $show_range ) {
						$price = wc_format_price_range( $min_price, $max_price );
					} else {
						$price = sprintf( '%s+', is_numeric( $min_price ) ? wc_price( $min_price ) : $min_price );
					}
				} elseif ( $show_sale_price && $product->is_on_sale() && $min_reg_price === $max_reg_price ) {
					$price = wc_format_sale_price( wc_price( $max_reg_price ), wc_price( $min_price ) );
				} else {
					$price = wc_price( $min_price );
				}
			}
		} elseif ( $show_sale_price && $product->is_on_sale() ) {
			$price = wc_format_sale_price( wc_get_price_to_display( $product, [ 'price' => $product->get_regular_price() ] ), wc_get_price_to_display( $product ) );
		} else {
			$price = wc_price( wc_get_price_to_display( $product ) );
		}

		return apply_filters( 'wc_restaurant_ordering_product_display_price', $price, $product );
	}

	/**
	 * Get the image URL for a product. Similar to WC_Product->get_image() but returns the URL rather than the full <img> tag.
	 *
	 * @param WC_Product $product The product object.
	 * @param string $image_size  The image size to retrieve.
	 * @return string The image URL, or an empty string if product has no image.
	 */
	public static function get_image_url( WC_Product $product, $image_size = 'woocommerce_single' ) {
		$image_array = false;

		if ( $product->get_image_id() ) {
			$image_array = wp_get_attachment_image_src( $product->get_image_id(), $image_size );
		} elseif ( $product->get_parent_id() ) {
			$parent_product = wc_get_product( $product->get_parent_id() );

			if ( $parent_product && $parent_product->get_image_id() ) {
				$image_array = wp_get_attachment_image_src( $parent_product->get_image_id(), $image_size );
			}
		}

		return is_array( $image_array ) ? $image_array[0] : '';
	}

	/**
	 * Get the supported order type for a product. Variable products always return 'lightbox'.
	 *
	 * @param WC_Product $product The product object.
	 * @return string The supported order type ('lightbox' or 'quick').
	 */
	public static function get_order_type( WC_Product $product ) {
		$order_type = $product->supports( 'ajax_add_to_cart' ) ? Menu_Options::OT_QUICK : Menu_Options::OT_LIGHTBOX;

		return apply_filters( 'wc_restaurant_ordering_product_order_type_checked', $order_type, $product );
	}

	/**
	 * Get the quantity args for a product. The args include the quantity value as well as its min, max and step.
	 *
	 * The args are run through the 'woocommerce_quantity_input_args' filter to allow quantity plugins to override.
	 *
	 * @param WC_Product $product The product object
	 * @return array The quantity args - an array with 'value', 'min', 'max' and 'step' keys
	 */
	public static function get_quantity_args( WC_Product $product ) {
		// Create the args using the default WC keys and run through the 'woocommerce_quantity_input_args' filter.
		$args = apply_filters(
			'woocommerce_quantity_input_args',
			[
				'input_value' => $product->get_min_purchase_quantity(),
				'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
				'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
				'step'        => apply_filters( 'woocommerce_quantity_input_step', 1, $product )
			],
			$product
		);

		// Min quantity must be greater >= 0.
		$args['min_value'] = max( $args['min_value'], 0 );

		if ( -1 === $args['max_value'] ) {
			// A max of -1 means unlimited, so set to empty string.
			$args['max_value'] = '';
		} elseif ( is_numeric( $args['max_value'] ) && is_numeric( $args['min_value'] ) && $args['max_value'] < $args['min_value'] ) {
			// Max can't be less than min.
			$args['max_value'] = $args['min_value'];
		}

		// We map the args to the standard HTML input attributes.
		$result = [];
		$map    = [
			'input_value' => 'value',
			'min_value'   => 'min',
			'max_value'   => 'max',
			'step'        => 'step'
		];

		foreach ( $map as $wc_key => $key ) {
			$result[ $key ] = esc_attr( $args[ $wc_key ] );
		}

		return apply_filters( 'wc_restaurant_ordering_product_quantity_args', $result, $product );
	}

}
