<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Menu;

use Barn2\Plugin\WC_Restaurant_Ordering\Settings;
use WP_Term;

/**
 * Stores the options for a restaurant order menu.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Menu_Options {

	const OT_QUICK    = 'quick';
	const OT_LIGHTBOX = 'lightbox';
	const OT_CHECK    = 'check';
	const DD_LIMITED  = 'limited';
	const DD_FULL     = 'full';
	const DEFAULTS    = [
		'categories'                            => 'default',
		'order_type'                            => self::OT_QUICK,
		'image_position'                        => 'left',
		'product_image'                         => true,
		'image_size'                            => 130,
		'product_description'                   => true,
		'description_length'                    => self::DD_LIMITED,
		'buy_button'                            => true,
		'category_titles'                       => true,
		'category_descriptions'                 => true,
		'columns'                               => 2,
		'menu_navigation'                       => true,
		'restaurant_info'                       => true,
		'categories_with_hidden_title'          => [],
		'duplicate_products_in_parent_category' => true,
		'delete_data'							=> 'no'
	];

	/**
	 * @var array $args The supplied args to create the menu options.
	 */
	protected $args;

	/**
	 * @var array $product_elements Holds the product elements to display for this menu (image, name, etc)
	 */
	protected $product_elements;

	/**
	 * @var WP_Term[] $category_objects Internal list of category objects for this menu.
	 */
	private $category_objects;

	/**
	 * @param array $args
	 * @param bool $load_settings
	 */
	public function __construct( array $args = [], $load_settings = true ) {
		if ( $load_settings ) {
			$args = array_merge( $this->get_settings(), $args );
		}

		$this->args = $this->parse_args( $args );
	}

	/**
	 * Get the default menu options.
	 *
	 * @return array The defaults
	 */
	public static function get_defaults() {
		$defaults = apply_filters( 'wc_restaurant_ordering_menu_default_options', self::DEFAULTS );

		/* @deprcated 2.0 Replaced by wc_restaurant_ordering_menu_default_options. */
		return apply_filters( 'wc_restaurant_ordering_default_menu_options', $defaults );
	}

	/**
	 * Get the list of categories (as category slugs) for this menu.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return $this->args['categories'] ? (array) $this->args['categories'] : [];
	}

	/**
	 * Get the list of category objects (WP_Term) for this menu.
	 *
	 * @return WP_Term[]
	 */
	public function get_category_objects() {
		return $this->category_objects;
	}

	/**
	 * Get the list of categories (as category slugs) for which the category title and description are hidden.
	 *
	 * @return string[]
	 */
	public function get_categories_with_hidden_title() {
		return $this->args['categories_with_hidden_title'] ? (array) $this->args['categories_with_hidden_title'] : [];
	}

	/**
	 * Get the menu item description length - DD_LIMITED or DD_FULL.
	 *
	 * @return string
	 */
	public function get_description_length() {
		return $this->args['description_length'];
	}

	/**
	 * Get the menu item image position - left or right.
	 *
	 * @return string
	 */
	public function get_image_position() {
		return $this->args['image_position'];
	}

	/**
	 * Get the menu image size in pixels. This will be a single integer value as the image is a square.
	 *
	 * @return int The image size in pixels.
	 */
	public function get_image_size() {
		return absint( $this->args['image_size'] );
	}

	/**
	 * Get the number of columns for the menu.
	 *
	 * @return int
	 */
	public function get_num_columns() {
		return absint( $this->args['columns'] );
	}

	/**
	 * Get the full list of menu options as an array.
	 *
	 * @return array The menu options
	 */
	public function get_options() {
		return $this->args;
	}

	public function get_order_type() {
		return $this->args['order_type'];
	}

	public function get_product_elements() {
		if ( empty( $this->product_elements ) ) {
			$elements = apply_filters( 'wc_restaurant_ordering_menu_default_product_elements', [ 'name', 'price' ] );

			/* @deprecated 2.0 Replaced by wc_restaurant_ordering_menu_default_product_elements. */
			$elements = apply_filters( 'wc_restaurant_ordering_default_product_elements', $elements );

			if ( $this->show_product_image() ) {
				$elements[] = 'image';
			}

			if ( $this->show_product_description() ) {
				$elements[] = 'description';
			}

			if ( $this->show_buy_button() ) {
				$elements[] = 'buy_button';
			}

			$this->product_elements = $elements;
		}

		return $this->product_elements;
	}

	public function is_enabled( $data ) {
		return in_array( $data, $this->get_product_elements(), true );
	}

	public function show_buy_button() {
		return (bool) $this->args['buy_button'];
	}

	public function show_category_titles() {
		return (bool) $this->args['category_titles'];
	}

	public function show_category_descriptions() {
		return (bool) $this->args['category_descriptions'];
	}

	public function show_duplicate_products_in_parent_category() {
		return (bool) $this->args['duplicate_products_in_parent_category'];
	}

	public function show_menu_navigation() {
		return (bool) $this->args['menu_navigation'];
	}

	public function show_product_image() {
		return (bool) $this->args['product_image'];
	}

	public function show_product_description() {
		return (bool) $this->args['product_description'];
	}

	public function show_restaurant_info() {
		return (bool) $this->args['restaurant_info'];
	}

	/**
	 * Get the plugin settings related to the restaurant menu. If no settings are stored, the default value from get_defaults is returned.
	 *
	 * @return array The settings
	 * @see get_defaults
	 */
	protected function get_settings() {
		$defaults = self::get_defaults();

		// Unset any keys which are not stored in the plugin settings.
		unset( $defaults['restaurant_info'], $defaults['categories_with_hidden_title'], $defaults['duplicate_products_in_parent_category'] );

		return Settings::get_settings( array_keys( $defaults ), $defaults );
	}

	private function get_terms( array $values ) {
		if ( empty( $values ) ) {
			return [];
		}

		$terms    = [];
		$fetch_by = 'id';

		if ( count( $values ) !== count( array_filter( $values, 'is_numeric' ) ) ) {
			$fetch_by = 'slug';
		}

		foreach ( $values as $value ) {
			$term = get_term_by( $fetch_by, $value, 'product_cat' );

			if ( $term && ! is_wp_error( $term ) ) {
				$terms[ $term->slug ] = $term;
			}
		}

		return $terms;
	}

	private function parse_args( array $args ) {
		$defaults = self::get_defaults();
		$args     = array_merge( $defaults, array_intersect_key( $args, $defaults ) );

		$categories = $args['categories'];

		if ( empty( $categories ) || 'default' === $categories ) {
			// If no categories chosen, fetch all categories.
			$category_ids = get_terms(
				[
					'taxonomy' => 'product_cat',
					'fields'   => 'ids'
				]
			);

			if ( is_wp_error( $category_ids ) ) {
				$categories = [];
			} else {
				$categories = (array) $category_ids;
			}
		} elseif ( is_scalar( $categories ) ) {
			$categories = array_map( 'trim', explode( ',', (string) $categories ) );
		}

		$this->category_objects = $this->get_terms( $categories );
		$args['categories']     = array_keys( $this->category_objects );

		if ( ! in_array( $args['image_position'], [ 'left', 'right' ] ) ) {
			$args['image_position'] = (string) $defaults['image_position'];
		}

		$args['image_size'] = absint( $args['image_size'] );

		if ( ! in_array( $args['description_length'], [ self::DD_LIMITED, self::DD_FULL ] ) ) {
			$args['description_length'] = (string) $defaults['description_length'];
		}

		if ( ! in_array( $args['order_type'], [ self::OT_QUICK, self::OT_LIGHTBOX ] ) ) {
			$args['order_type'] = (string) $defaults['order_type'];
		}

		$args['columns'] = (int) $args['columns'];

		if ( ! in_array( $args['columns'], [ 1, 2, 3 ], true ) ) {
			$args['columns'] = (int) $defaults['columns'];
		}

		$cats_with_hidden_title = $args['categories_with_hidden_title'];

		if ( is_scalar( $cats_with_hidden_title ) ) {
			$cats_with_hidden_title = array_map( 'trim', explode( ',', (string) $cats_with_hidden_title ) );
		} elseif ( ! is_array( $cats_with_hidden_title ) ) {
			$cats_with_hidden_title = [];
		}

		$args['categories_with_hidden_title'] = array_keys( $this->get_terms( $cats_with_hidden_title ) );

		// Validate boolean args.
		foreach ( [ 'category_titles', 'category_descriptions', 'product_image', 'product_description', 'buy_button', 'menu_navigation', 'restaurant_info', 'duplicate_products_in_parent_category' ] as $arg ) {
			$args[ $arg ] = filter_var( $args[ $arg ], FILTER_VALIDATE_BOOLEAN );
		}

		return apply_filters( 'wc_restaurant_ordering_menu_parse_args', $args );
	}

}
