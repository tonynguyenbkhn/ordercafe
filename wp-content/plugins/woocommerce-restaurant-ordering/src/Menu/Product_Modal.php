<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Menu;

use Barn2\Plugin\WC_Restaurant_Ordering\Settings;
use Barn2\Plugin\WC_Restaurant_Ordering\Template_Loader_Factory;
use Barn2\Plugin\WC_Restaurant_Ordering\Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Template_Loader;
use WC_Product;

/**
 * Handles retrieval of the modal data for a product.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Modal {

	/**
	 * @var WC_Product $product The product for the modal.
	 */
	protected $product;

	/**
	 * @var Template_Loader The template loader.
	 */
	private $template_loader;

	public function __construct( WC_Product $product ) {
		$this->product         = $product;
		$this->template_loader = Template_Loader_Factory::create();
	}

	/**
	 * Gets the modal data for the product.
	 *
	 * @return array The modal data.
	 * @global WC_Product $product Global product.
	 */
	public function get_modal_data() {
		global $product;

		// Store current global product, then update global with our modal product.
		$old_product = $product;
		$product     = $this->product;

		// Collect WPBakery styles before processing content.
		$wpbakery_styles = $this->get_wpbakery_styles();

		$closed_notice = Util::get_default_availability_options()->get_closed_notice();

		if ( ! $closed_notice ) {
			// We need to show an error in the modal when the restaurant is closed, so if no 'closed notice' set,
			// show the restaurant closed error message instead.
			$closed_notice = Util::get_restaurant_closed_error_message();
		}

		// Build the modal data.
		$data = [
			'product_id'       => esc_attr( $product->get_id() ),
			'product_name'     => apply_filters( 'wc_restuarant_ordering_modal_product_name', apply_filters( 'wc_restuarant_ordering_product_name', $product->get_name(), $product ), $product ),
			'price'            => esc_attr( $product->get_price() ),
			'display_price'    => apply_filters( 'wc_restaurant_ordering_modal_product_price', Product_Data::get_display_price( $product, false, false ), $product ),
			'quantity'         => Product_Data::get_quantity_args( $product ),
			'in_stock'         => $product->is_in_stock(),
			'purchasable'      => $this->is_purchasable(),
			'accepting_orders' => Util::is_accepting_orders(),
			'closed_notice'    => $closed_notice,
			'form_class'       => 'cart',
			'form_data'        => '',
			'options'          => ''
		];

		$show_modal_image       = Settings::get_setting( 'modal_image', true );
		$show_modal_description = Settings::get_setting( 'modal_description', true );
		$show_modal_stock       = Settings::get_setting( 'modal_stock', true );

		// Hide the 'main' stock for variable products as it's shown in the variation data when a variation is selected.
		if ( $product->is_type( 'variable' ) ) {
			$show_modal_stock = false;
		}

		if ( $show_modal_image ) {
			$data['image'] = apply_filters( 'wc_restaurant_ordering_modal_product_image', Product_Data::get_image_url( $product, $this->get_modal_image_size() ), $product );
		}

		if ( $show_modal_description ) {
			$data['description'] = $this->get_modal_description();
		}

		if ( $show_modal_stock ) {
			$data['stock'] = $this->get_modal_stock_status();
		}

		// Add WPBakery styles if available.
		if ( ! empty( $wpbakery_styles ) ) {
			$data['styles'] = $wpbakery_styles;
		}

		// Handle variable products.
		if ( $product->is_type( 'variable' ) ) {
			$available_variations = $product->get_available_variations();

			if ( ! empty( $available_variations ) ) {
				$data['options'] .= $this->template_loader->get_template(
					'modal/variations.php',
					[
						'product'              => $product,
						'variation_attributes' => $product->get_variation_attributes()
					]
				);
				// todo: add variations reset link?

				$data['form_class'] .= ' variations_form';
				$data['form_data']  .= sprintf( ' data-product_variations="%s"', wc_esc_json( wp_json_encode( $available_variations ) ) );
			} else {
				// Out of stock (no available variations).
				$data['in_stock'] = false;
			}
		}

		// Run data through a filter.
		$data = apply_filters( 'wc_restaurant_ordering_modal_data', $data, $product );

		// Trim the string data.
		$data = array_map(
			function ( $value ) {
				return is_string( $value ) ? trim( $value ) : $value;
			},
			$data
		);

		// Reset the global product.
		$product = $old_product;

		return $data;
	}

	protected function get_modal_description() {
		$description = '';

		// Fetch long description for product by default, but allow themes/plugins to override.
		if ( apply_filters( 'wc_restaurant_ordering_modal_use_long_description', true, $this->product ) ) {
			$description = $this->product->get_description();
		}

		if ( ! $description && apply_filters( 'wc_restaurant_ordering_modal_use_short_description', true, $this->product ) ) {
			$description = $this->product->get_short_description();
		}

		$description = apply_filters( 'wc_restaurant_ordering_modal_description_before_formatting', $description, $this->product );

		// Process the content through 'the_content' filter (which includes do_shortcode).
		$description = apply_filters( 'the_content', $description );

		return apply_filters( 'wc_restaurant_ordering_modal_description', $description, $this->product );
	}

	protected function get_modal_image_size() {
		return apply_filters( 'wc_restaurant_ordering_modal_image_size', [ 500, 300 ], $this->product );
	}

	protected function get_modal_stock_status() {
		return apply_filters( 'wc_restaurant_ordering_modal_stock_status', wc_get_stock_html( $this->product ), $this->product );
	}

	private function is_purchasable() {
		return apply_filters( 'wc_restaurant_ordering_product_is_purchasable', $this->product->is_purchasable() && $this->product->is_in_stock() && Util::is_accepting_orders() );
	}

	/**
	 * Get WPBakery Page Builder styles for the modal.
	 *
	 * @return string The styles HTML or empty string.
	 */
	private function get_wpbakery_styles() {
		$styles = '';

		// Check if WPBakery is active.
		if ( ! function_exists( 'vc_asset_url' ) ) {
			return $styles;
		}

		// Get custom CSS from post meta (shortcodes custom CSS).
		$post_custom_css = get_post_meta( $this->product->get_id(), '_wpb_shortcodes_custom_css', true );
		if ( ! empty( $post_custom_css ) ) {
			$styles .= '<style type="text/css" data-type="vc_shortcodes-custom-css">' . $post_custom_css . '</style>';
		}

		// Get Visual Composer post custom CSS.
		$post_custom_css = get_post_meta( $this->product->get_id(), '_wpb_post_custom_css', true );
		if ( ! empty( $post_custom_css ) ) {
			$styles .= '<style type="text/css" data-type="vc_custom-css">' . $post_custom_css . '</style>';
		}

		// Add base WPBakery CSS file link.
		if ( defined( 'WPB_VC_VERSION' ) ) {
			$vc_base_url = vc_asset_url( 'css/js_composer.min.css' );
			$styles .= '<link rel="stylesheet" href="' . esc_url( $vc_base_url ) . '" type="text/css" media="all" />';
		}

		return apply_filters( 'wc_restaurant_ordering_modal_wpbakery_styles', $styles, $this->product );
	}

}
