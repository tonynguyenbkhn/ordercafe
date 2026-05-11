<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering;

/**
 * Represents a restaurant menu component that can be rendered.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
interface Component {

	/**
	 * Renders the component. HTML is returned, not echoed.
	 *
	 * @return string The component output.
	 */
	public function render();

}
