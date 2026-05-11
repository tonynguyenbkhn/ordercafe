<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Menu;

/**
 * Data passed to each Menu_Section.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Section_Data {

	/**
	 * @var string $title The section title.
	 */
	public $title;

	/**
	 * @var string $description The section description.
	 */
	public $description;

	/**
	 * @var WC_Product[] $products The products to display in this section.
	 */
	public $products;

	/**
	 * @var string The anchor link
	 */
	public $anchor;

}
