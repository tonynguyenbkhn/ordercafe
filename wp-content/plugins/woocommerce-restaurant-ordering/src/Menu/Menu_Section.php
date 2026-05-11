<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Menu;

use Barn2\Plugin\WC_Restaurant_Ordering\Component;
use Barn2\Plugin\WC_Restaurant_Ordering\Template_Loader_Factory;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Template_Loader;
use WC_Product;

/**
 * Handles the display of a menu section. This is one grouping of products with an optional title and description.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Menu_Section implements Component {

	/**
	 * @var Section_Data The menu section data.
	 */
	protected $data;

	/**
	 * @var Menu_Options The options for this section.
	 */
	protected $options;

	/**
	 * @var WC_Product[] The list of products to display in this section.
	 */
	protected $products;

	/**
	 * @var Template_Loader The template loader.
	 */
	private $template_loader;

	public function __construct( Section_Data $data, Menu_Options $options ) {
		$this->data     = $data;
		$this->options  = $options;
		$this->products = $this->parse_products( $this->data->products );

		$this->template_loader = Template_Loader_Factory::create();
	}

	public function render() {
		$title_html = $this->template_loader->get_template(
			'menu/section/title.php',
			[
				'title'       => $this->data->title,
				'description' => $this->data->description,
			]
		);

		$product_html = '';

		if ( ! empty( $this->products ) ) {
			foreach ( $this->products as $product ) {
				$menu_product  = new Menu_Product( $product, $this->options );
				$product_html .= $menu_product->render();
			}
		}

		$products_html = $this->template_loader->get_template(
			'menu/section/products.php',
			[
				'products'       => $product_html,
				'products_class' => $this->get_products_class(),
			]
		);

		return $this->template_loader->get_template(
			'menu/section',
			[
				'title'      => $title_html,
				'products'   => $products_html,
				'section_id' => $this->data->anchor,
			]
		);
	}

	public function get_products() {
		return $this->products;
	}

	public function get_section() {
		_deprecated_function( __METHOD__, '2.0', 'render' );
		return $this->render();
	}

	protected function get_products_class() {
		$classes   = [];
		$classes[] = 'columns-' . $this->options->get_num_columns();
		$classes[] = 'image-' . $this->options->get_image_position();
		$classes[] = $this->options->show_product_image() ? 'show-image' : 'hide-image';
		$classes[] = $this->is_product_modal_enabled() ? 'clickable' : 'not-clickable';

		return implode( ' ', apply_filters( 'wc_restaurant_ordering_menu_products_class', $classes ) );
	}

	protected function is_product_modal_enabled() {
		return ! $this->options->show_buy_button();
	}

	private function parse_products( array $products ) {
		if ( empty( $products ) ) {
			return [];
		}

		$parsed = [];

		// Products can be passed as a list of WC_Product objects or product IDs.
		foreach ( $products as $product ) {
			if ( is_numeric( $product ) ) {
				$parsed[] = wc_get_product( $product );
			} elseif ( $product instanceof WC_Product ) {
				$parsed[] = $product;
			}
		}

		return array_filter( $parsed );
	}
}
