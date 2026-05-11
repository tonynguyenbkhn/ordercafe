<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Menu;

use Barn2\Plugin\WC_Restaurant_Ordering\Component;
use Barn2\Plugin\WC_Restaurant_Ordering\Template_Loader_Factory;
use Barn2\Plugin\WC_Restaurant_Ordering\Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Template_Loader;
use WC_Product;

/**
 * Handles the display of an individual product in a restaurant menu.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Menu_Product implements Component {

	/**
	 * @var WC_Product $product The product for this menu item.
	 */
	public $product;

	/**
	 * @var Menu_Options $options The restaurant menu options.
	 */
	public $options;

	/**
	 * @var Template_Loader $template_loader The template loader.
	 */
	private $template_loader;

	/**
	 * @var string The URL of the product image used in the menu (set on demand).
	 */
	private $image_url = null;

	public function __construct( WC_Product $product, Menu_Options $options ) {
		$this->product         = $product;
		$this->options         = $options;
		$this->template_loader = Template_Loader_Factory::create();
	}

	public function render() {
		return apply_filters(
			'wc_restaurant_ordering_menu_product_output',
			$this->template_loader->get_template( 'menu/product', $this->get_template_args( $this->product ) ),
			$this->options,
			$this->product
		);
	}

	public function get_description() {
		$description = '';

		// Fetch short description for product by default, but allow themes/plugins to override.
		if ( apply_filters( 'wc_restaurant_ordering_use_short_description', true, $this->product ) ) {
			$description = $this->product->get_short_description();
		}

		if ( ! $description && apply_filters( 'wc_restaurant_ordering_use_long_description', true, $this->product ) ) {
			$description = $this->product->get_description();
		}

		$description = apply_filters( 'wc_restaurant_ordering_product_description_before_formatting', $description, $this->product );

		if ( apply_filters( 'wc_restaurant_ordering_allow_shortcodes_in_description', false ) ) {
			$description = do_shortcode( $description );
		} else {
			$description = strip_shortcodes( $description );
		}

		$description = apply_filters( 'wc_restaurant_ordering_product_description_before_trim', $description, $this->product );

		// Work out the description length.
		$description_length = 0;

		if ( Menu_Options::DD_LIMITED === $this->options->get_description_length() ) {
			// If description should be limited, work out a sensible word length based on menu options.
			$words = apply_filters(
				'wc_restaurant_ordering_product_description_words_per_column',
				[
					1 => 40,
					2 => 22,
					3 => 18
				]
			);

			$columns            = $this->options->get_num_columns();
			$description_length = isset( $words[ $columns ] ) ? $words[ $columns ] : 25;

			// Reduce length when image is shown.
			if ( $this->options->show_product_image() ) {
				$description_length -= 10;
			}
		}

		$description_length = apply_filters( 'wc_restaurant_ordering_product_description_length', $description_length, $this->product, $this->options );

		if ( $description_length > 0 ) {
			$description = wp_trim_words( $description, $description_length, '&hellip;' );
		} elseif ( apply_filters( 'wc_restaurant_ordering_strip_tags_from_description', true, $this->product ) ) {
			// The call to wp_trim_words above will strip tags, so we only need to do this when showing the full description.
			$description = wp_strip_all_tags( $description );
		}

		return apply_filters( 'wc_restaurant_ordering_product_description', $description, $this->product );
	}

	public function get_image() {
		return apply_filters( 'wc_restaurant_ordering_product_image', $this->get_image_url(), $this->product );
	}

	public function get_image_url() {
		if ( null === $this->image_url ) {
			$this->image_url = Product_Data::get_image_url( $this->product, $this->get_image_size() );
			$this->image_url = $this->image_url ?: '';
		}

		return $this->image_url;
	}

	public function get_name() {
		return apply_filters( 'wc_restuarant_ordering_product_name', $this->product->get_name(), $this->product );
	}

	/**
	 * Determines the available order type for a product based on the order type for this Menu_Section.
	 *
	 * If the order type is 'quick', we check if the product supports that or whether we need to force the modal. Products which always
	 * require a modal include variable products.
	 *
	 * @param WC_Product $product The product to check
	 * @return string The supported order type (quick, check or modal)
	 */
	public function get_order_type() {
		$order_type = $this->options->get_order_type();

		if ( Menu_Options::OT_QUICK === $order_type ) {
			if ( ! $this->product->supports( 'ajax_add_to_cart' ) ) {
				// Set to lightbox if product doesn't support ajax add to cart (e.g. variable products).
				$order_type = Menu_Options::OT_LIGHTBOX;
			}
		}

		// Allow plugins to override.
		$order_type = apply_filters( 'wc_restaurant_ordering_product_order_type', $order_type, $this->product, $this->options );

		// Sanitize in case invalid value set by plugins.
		if ( ! in_array( $order_type, [ Menu_Options::OT_QUICK, Menu_Options::OT_CHECK, Menu_Options::OT_LIGHTBOX ], true ) ) {
			$order_type = Menu_Options::OT_LIGHTBOX;
		}

		return $order_type;
	}

	public function get_price() {
		$show_range      = apply_filters( 'wc_restaurant_ordering_show_price_range', 'short', $this->product );
		$show_sale_price = apply_filters( 'wc_restaurant_ordering_show_sale_price', true, $this->product );

		$price = Product_Data::get_display_price( $this->product, $show_range, $show_sale_price );
		return apply_filters( 'wc_restaurant_ordering_product_price', $price, $this->product );
	}

	public function get_product() {
		_deprecated_function( __METHOD__, '2.0', 'render' );
		return $this->render();
	}

	protected function get_image_size() {
		$image_size = Util::get_menu_image_size_array( $this->options->get_image_size(), $this->product );

		// If image size is an array (w x h) we set the height to 0 so that image_downsize() returns the smallest
		// available image size. If we use exact size then image_downsize won't find a match and will return the
		// full-size image URL.
		if ( is_array( $image_size ) ) {
			$image_size[1] = 0;
		}

		return $image_size;
	}

	protected function get_template_args( WC_Product $product ) {
		$args = [
			'product'    => $product,
			'order_type' => $this->get_order_type(),
			'quantity'   => $product->get_min_purchase_quantity(),
			'item_attrs'  => $this->get_menu_item_attrs(),
		];

		foreach ( [ 'image', 'name', 'description', 'price', 'buy_button' ] as $data ) {
			// Get the template for each data item (if enabled) and add to template args.
			$args[ $data ]      = '';
			$data_template_args = [ 'product' => $product, 'images_url' => Util::get_assets_url() . '/images' ];

			if ( $this->options->is_enabled( $data ) ) {
				$get_function = 'get_' . $data;

				if ( method_exists( $this, $get_function ) ) {
					$data_template_args[ $data ] = $this->$get_function();
				}

				$args[ $data ] = $this->template_loader->get_template(
					str_replace( '_', '-', "menu/product/{$data}.php" ),
					$data_template_args
				);
			}
		}

		// Add extra arg keys expected by woocommerce_loop_add_to_cart_args hook.
		$args = array_merge(
			$args,
			[
				'class'      => '',
				'attributes' => [],
				'columns' => $this->options->get_num_columns()
			]
		);

		// Run args through WooCommerce and WRO hooks.
		$args = apply_filters( 'woocommerce_loop_add_to_cart_args', $args, $product );
		$args = apply_filters( 'wc_restaurant_ordering_product_template_args', $args, $product );

		return $args;
	}

	public function get_menu_item_attrs() {
		if ( $this->options->show_buy_button() ) {
			return '';
		} else {
			return 'tabindex=0 role=button';
		}
	}

}
