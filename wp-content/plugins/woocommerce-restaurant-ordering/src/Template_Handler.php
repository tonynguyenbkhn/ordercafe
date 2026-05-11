<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering;

use Barn2\Plugin\WC_Restaurant_Ordering\Menu\Menu_Options;
use Barn2\Plugin\WC_Restaurant_Ordering\Menu\Restaurant_Menu;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Service\Premium_Service;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Util;

/**
 * This class handles adding the restaurant order form to the WooCommerce shop pages.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Template_Handler implements Premium_Service, Registerable {

	/**
	 * Register any WordPress hooks.
	 */
	public function register() {
		add_action( 'template_redirect', [ $this, 'override_shop_template' ] );
	}

	/**
	 * Register hooks for overriding the WooCommerce shop templates.
	 *
	 * @hook template_redirect.
	 */
	public function override_shop_template() {
		if ( $this->should_override_shop_template() ) {
			add_action( 'woocommerce_before_shop_loop', [ $this, 'disable_woocommerce_loop' ], 1 );
			add_action( 'woocommerce_after_shop_loop', [ $this, 'clear_output_buffer' ], 500 );
			add_action( 'woocommerce_after_shop_loop', [ $this, 'display_restaurant_menu_shop_page' ], 510 );
			add_filter( 'woocommerce_short_description', [ $this, 'maybe_strip_restaurant_shortcode' ] );
		} elseif ( $this->should_override_category_template() ) {
			add_action( 'woocommerce_before_shop_loop', [ $this, 'disable_woocommerce_loop' ], 1 );
			add_action( 'woocommerce_after_shop_loop', [ $this, 'clear_output_buffer' ], 500 );
			add_action( 'woocommerce_after_shop_loop', [ $this, 'display_restaurant_menu_category_page' ], 510 );
			add_filter( 'woocommerce_short_description', [ $this, 'maybe_strip_restaurant_shortcode' ] );
		}
	}

	/**
	 * Should we override the default WooCommerce shop page template?
	 *
	 * @return bool true if we're overriding the shop template
	 */
	private function should_override_shop_template() {
		// Only applicable on the Shop page.
		if ( ! is_shop() ) {
			return false;
		}

		// Don't show for search results as the order form only supports categories, not search terms.
		if ( isset( $_GET['s'] ) ) {
			return false;
		}

		$restaurant_menu_page = Settings::get_setting( 'menu_page' );

		// Show on Shop page if menu page matches the WooCommerce Shop page.
		return apply_filters(
			'wc_restaurant_ordering_override_woocommerce_shop_page',
			$restaurant_menu_page
			&& $restaurant_menu_page === wc_get_page_id( 'shop' )
			&& 'subcategories' !== get_option( 'woocommerce_shop_page_display', '' )
		);
	}

	/**
	 * Should we override the default WooCommerce category page template?
	 *
	 * @return bool true if we're overriding the category template
	 */
	private function should_override_category_template() {
		if ( ! is_product_category() ) {
			return false;
		}

		$override_category_template = Settings::get_setting( 'category_template' );

		return apply_filters( 'wc_restaurant_ordering_override_woocommerce_category_page', $override_category_template );
	}

	/**
	 * Disable the WooCommerce loop for the current template, and start output buffering.
	 */
	public function disable_woocommerce_loop() {
		$GLOBALS['woocommerce_loop']['total'] = false;
		ob_start();
	}

	/**
	 * Discard the contents of the WooCommerce loop for the current template. This will be the entire products grid,
	 * plus things like sorting and catalogue options.
	 */
	public function clear_output_buffer() {
		ob_end_clean();
	}

	/**
	 * Display the restaurant menu for the shop page.
	 */
	public function display_restaurant_menu_shop_page() {
		$restaurant_menu = new Restaurant_Menu( new Menu_Options( apply_filters( 'wc_restaurant_ordering_woocommerce_shop_page_options', [] ) ) );
		echo $restaurant_menu->render();
	}

	/**
	 * Display the restaurant menu for the category pages.
	 */
	public function display_restaurant_menu_category_page() {
		$category_id         = get_queried_object_id();
		$categories_for_menu = [];

		// Get the child categories of the current category.
		$child_categories = get_terms(
			[
				'taxonomy' => 'product_cat',
				'child_of' => $category_id,
				'fields'   => 'ids'
			]
		);

		if ( is_array( $child_categories ) ) {
			$categories_for_menu = $child_categories;
		}

		// Add any child categories to current category ID.
		array_unshift( $categories_for_menu, $category_id );

		// Build menu options for category page.
		$options = [
			'categories'                            => $categories_for_menu,
			'categories_with_hidden_title'          => [ $category_id ],
			'duplicate_products_in_parent_category' => false,
			'restaurant_info'                       => false
		];

		$restaurant_menu = new Restaurant_Menu( new Menu_Options( apply_filters( 'wc_restaurant_ordering_woocommerce_category_page_options', $options ) ) );
		echo $restaurant_menu->render();
	}

	/**
	 * For sites upgraded from < 3.0.1, they could have the restaurant shortcode in the Shop page content, so we need to remove it.
	 *
	 * @param string $content The content.
	 * @return string The content.
	 */
	public function maybe_strip_restaurant_shortcode( $content ) {
		if ( $content ) {
			$content = preg_replace( sprintf( '/\[%s.*?\]/', Shortcodes::MENU_SHORTCODE ), '', $content );
		}

		return $content;
	}

}
