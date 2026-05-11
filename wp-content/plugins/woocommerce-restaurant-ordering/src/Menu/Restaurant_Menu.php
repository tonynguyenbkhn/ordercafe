<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Menu;

use Barn2\Plugin\WC_Restaurant_Ordering\Component;
use Barn2\Plugin\WC_Restaurant_Ordering\Frontend_Scripts;
use Barn2\Plugin\WC_Restaurant_Ordering\Frontend_Scripts_Factory;
use Barn2\Plugin\WC_Restaurant_Ordering\Info\Restaurant_Information;
use Barn2\Plugin\WC_Restaurant_Ordering\Settings;
use Barn2\Plugin\WC_Restaurant_Ordering\Template_Loader_Factory;
use Barn2\Plugin\WC_Restaurant_Ordering\Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Template_Loader;
use WC_Product;
use WP_Term;

/**
 * Handles the display of a restaurant menu for a given set of options.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Restaurant_Menu implements Component {

	private static $menu_id = 0;

	/**
	 * @var Menu_Options $options The menu options.
	 */
	protected $options;

	/**
	 * @var WC_Product[] $products The products displayed in this menu.
	 */
	private $products = null;

	/**
	 * @var Template_Loader $template_loader The template loader.
	 */
	private $template_loader;

	/**
	 * @var Frontend_Scripts $script_loader The script loader.
	 */
	private $script_loader;

	public function __construct( Menu_Options $options ) {
		$this->options         = $options;
		$this->template_loader = Template_Loader_Factory::create();
		$this->script_loader   = Frontend_Scripts_Factory::create();
	}

	/**
	 * Get the categories displayed in this menu.
	 *
	 * @return WP_Term[] The array of WP_Term objects.
	 */
	public function get_categories() {
		return $this->options->get_category_objects();
	}

	/**
	 * Get the list of all products displayed in this menu.
	 *
	 * @return WC_Product[] The array of WC_Product objects.
	 */
	public function get_products() {
		if ( is_array( $this->products ) ) {
			return $this->products;
		}

		$this->products = $this->load_products( array_keys( $this->get_categories() ) );
		return $this->products;
	}

	/**
	 * Renders the restaurant menu based on the supplied options.
	 *
	 * @return string The HTML for the restaurant menu.
	 */
	public function render() {
		self::$menu_id++;
		$this->load_scripts();

		$categories           = $this->get_categories();
		$displayed_categories = [];
		$sections_html        = '';

		if ( empty( $categories ) ) {
			$sections_html = $this->format_error_message( __( 'There are no menu categories available.', 'woocommerce-restaurant-ordering' ) );
		} else {
			// $categories: WP_Term[]
			foreach ( $categories as $category ) {
				$section_title       = '';
				$section_description = '';

				if ( ! in_array( $category->slug, $this->options->get_categories_with_hidden_title() ) ) {
					if ( $this->options->show_category_titles() ) {
						$section_title = get_term_field( 'name', $category, 'product_cat' );
					}

					if ( $this->options->show_category_descriptions() ) {
						$section_description = trim( get_term_field( 'description', $category, 'product_cat' ) );
					}
				}

				// Fetch the products to display in this section.
				$section_products = $this->get_products_for_section( $category );

				// Build the section data to pass to Menu_Section.
				$section_data              = new Section_Data();
				$section_data->title       = apply_filters( 'wc_restaurant_ordering_category_title', $section_title, $category );
				$section_data->description = apply_filters( 'wc_restaurant_ordering_category_description', $section_description, $category );
				$section_data->products    = apply_filters( 'wc_restaurant_ordering_category_products', $section_products, $category );
				$section_data->anchor      = $this->get_anchor_prefix() . $category->slug;

				// Add this section if we have 1 or more products to display.
				if ( ! empty( $section_products ) ) {
					$menu_section           = new Menu_Section( $section_data, $this->options );
					$sections_html         .= $menu_section->render();
					$displayed_categories[] = $category;
				}
			} // foreach category
		}

		if ( ! $sections_html ) {
			$sections_html = $this->format_error_message( __( 'There are no products available for this menu.', 'woocommerce-restaurant-ordering' ) );
		}

		$restaurant_info = new Restaurant_Information( Util::get_default_restaurant_info_options(), Util::get_default_availability_options() );
		$navigation      = new Navigation( $displayed_categories, $this->get_anchor_prefix() );

		$output = $this->template_loader->get_template(
			'menu',
			[
				'menu_id'         => $this->get_menu_html_id(),
				'restaurant_info' => $this->options->show_restaurant_info() ? $restaurant_info->render() : '',
				'menu_navigation' => $this->options->show_menu_navigation() ? $navigation->render() : '',
				'menu_items'      => $sections_html,
				'sections'        => $sections_html // back-comat: support previous template variable
			]
		);

		return apply_filters( 'wc_restaurant_ordering_menu_output', $output, $this->options, $this->get_products() );
	}

	/**
	 * Format an error message for the menu.
	 *
	 * @param string $message         The error message.
	 * @param string $show_categories Whether to show the list of categories in the error message.
	 * @return string The error message
	 */
	protected function format_error_message( $message, $show_categories = false ) {
		$message = esc_html( $message );

		if ( $show_categories ) {
			/* translators: %s: The list of product categories */
			$message .= ' ' . sprintf( __( '[categories: %s]', 'woocommerce-restaurant-ordering' ), implode( ', ', $this->options->get_categories() ) );
		}

		return $this->template_loader->get_template( 'menu/error.php', [ 'error_message' => $message ] );
	}

	/**
	 * Get the anchor prefix for the category sections.
	 *
	 * @return string The anchor prefix.
	 */
	private function get_anchor_prefix(): string {
		return sprintf( 'wro-menu-%u-', self::$menu_id );
	}

	/**
	 * Get the menu's ID to use in the menu HTML template.
	 *
	 * @return string The menu's HTML ID.
	 */
	private function get_menu_html_id(): string {
		return 'wro-menu-' . self::$menu_id;
	}

	/**
	 * Get the products for the specified category.
	 *
	 * @param WP_Term $category The category to retrieve the products for.
	 * @return WC_Product[] The list of products for the section.
	 */
	private function get_products_for_section( WP_Term $category ) {
		$section_products = [];
		$category_ids     = wp_list_pluck( $this->get_categories(), 'term_id' );

		// Loop through the products array to check which products to include in this section.
		// $products: WC_Product[]
		foreach ( $this->get_products() as $product ) {
			$show_product         = false;
			$product_category_ids = $product->get_category_ids();

			if ( in_array( $category->term_id, $product_category_ids, true ) ) {
				// The product is in the current category, so mark it for inclusion.
				$show_product = true;

				// Are we showing duplicate products that appear in both the parent and the child category?
				// If we're hiding them, we need to do some extra logic to determine it's status in the current section.
				if ( ! $this->options->show_duplicate_products_in_parent_category() ) {
					$child_category_ids = get_term_children( $category->term_id, 'product_cat' );

					if ( is_array( $child_category_ids ) && $child_category_ids ) {
						foreach ( $child_category_ids as $child_category_id ) {
							// If the product is also in the child category, and the child category is in the list restaurant categories,
							// then don't show the product at this point as it will be shown in the child category.
							if ( in_array( $child_category_id, $product_category_ids, true ) && in_array( $child_category_id, $category_ids, true ) ) {
								$show_product = false;
								break;
							}
						}
					}
				}
			}

			if ( $show_product ) {
				$section_products[] = $product;
			}
		}

		return $section_products;
	}

	/**
	 * Load the products to use in this menu. Calls wc_get_products.
	 *
	 * @param string[] $categories The list of category slugs to load the products for.
	 * @return WC_Product[] The array of WC_Product objects.
	 */
	private function load_products( array $categories ) {
		if ( empty( $categories ) ) {
			return [];
		}

		$orderby = Settings::get_setting( 'product_order', 'menu_order' );
		$order   = 'date' === $orderby ? 'DESC' : 'ASC';
		$query   = WC()->query;

		$post_clauses_callback = null;

		if ( 'price' === $orderby ) {
			$post_clauses_callback = 'DESC' === $order ? 'order_by_price_desc_post_clauses' : 'order_by_price_asc_post_clauses';
		} elseif ( 'popularity' === $orderby ) {
			$post_clauses_callback = 'order_by_popularity_post_clauses';
		}

		if ( $post_clauses_callback ) {
			add_filter( 'posts_clauses', [ $query, $post_clauses_callback ] );
		}

		$wc_get_products_args = [
			'category' => $categories,
			'limit'    => apply_filters( 'wc_restaurant_ordering_product_limit', 250 ),
			'status'   => 'publish',
			'type'     => apply_filters( 'wc_restaurant_ordering_allowed_product_types', [ 'simple', 'variable' ] ),
			'orderby'  => $orderby,
			'order'    => $order,
			'paginate' => false
		];

		if ( ! apply_filters( 'wc_restaurant_ordering_show_hidden_products', false ) ) {
			$wc_get_products_args['visibility'] = 'catalog';
		}

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$wc_get_products_args['stock_status'] = 'instock';
		}

		$products = wc_get_products( apply_filters( 'wc_restaurant_ordering_wc_get_products_args', $wc_get_products_args, $this->options ) );

		if ( ! is_array( $products ) ) {
			$products = [];
		}

		if ( $post_clauses_callback ) {
			remove_filter( 'posts_clauses', [ $query, $post_clauses_callback ] );
		}

		return (array) apply_filters( 'wc_restaurant_ordering_menu_products', $products, $this->options );
	}

	private function get_inline_styles() {
		$image_size = $this->options->get_image_size();

		// No need to add inline styles if we're using the default image size.
		if ( $image_size === Menu_Options::DEFAULTS['image_size'] ) {
			return '';
		}

		$image_size_array = Util::get_menu_image_size_array( $this->options->get_image_size() );

		if ( is_array( $image_size_array ) ) {
			$style_fmt = '
				@media screen and (min-width:768px) {
					#%1$s .wc-restaurant-menu-product .image { flex-basis: %2$upx; min-height: %2$upx; }
				}
			';
			return trim( sprintf( $style_fmt, $this->get_menu_html_id(), $image_size_array[0] ) );
		}

		return '';
	}

	private function load_scripts() {
		$this->script_loader->load_scripts();
		$this->script_loader->add_inline_styles( $this->get_inline_styles() );
	}

	/**
	 * @deprecated 2.0 Replaced by render
	 */
	public function get_menu() {
		_deprecated_function( __METHOD__, '2.0', 'render' );
		return $this->render();
	}

	/**
	 * @deprecated 2.0 Replaced by format_error_message
	 */
	protected function error_message( $message, $show_categories = false ) {
		_deprecated_function( __METHOD__, '2.0', 'format_error_message' );
		return $this->format_error_message( $message, $show_categories );
	}

}
