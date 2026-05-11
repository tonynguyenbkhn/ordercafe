<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Menu;

use Barn2\Plugin\WC_Restaurant_Ordering\Component;
use Barn2\Plugin\WC_Restaurant_Ordering\Template_Loader_Factory;

/**
 * Restaurant navigation menu component.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Navigation implements Component {

	/**
	 * @var WP_Term[] $categories The categories to display in the navigation menu.
	 */
	protected $categories;

	/**
	 * @var string $anchor_prefix The prefix for anchor links in the navigation.
	 */
	private $anchor_prefix;

	public function __construct( array $categories, $anchor_prefix = 'wro-cat-' ) {
		$this->categories    = $categories;
		$this->anchor_prefix = $anchor_prefix;
	}

	public function render() {
		$min_category_threshold = apply_filters( 'wc_restaurant_ordering_navigation_minimum_categories_to_display', 2 );

		if ( absint( $min_category_threshold ) > count( $this->categories ) ) {
			return '';
		}

		$template_loader      = Template_Loader_Factory::create();

		return apply_filters(
			'wc_restaurant_ordering_navigation_output',
			$template_loader->get_template(
				'menu/navigation.php',
				[
					'categories'    => $this->categories,
					'anchor_prefix' => $this->anchor_prefix
				]
			),
			$this->categories
		);
	}

}
