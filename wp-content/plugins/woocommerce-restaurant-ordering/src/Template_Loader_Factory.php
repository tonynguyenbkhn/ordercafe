<?php
namespace Barn2\Plugin\WC_Restaurant_Ordering;

use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\WooCommerce\Templates;
use	Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Template_Loader;

/**
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Template_Loader_Factory {

	private static $template_loader = null;

	/**
	 * Get the shared template loader instance.
	 *
	 * @return Template_Loader The template loader.
	 */
	public static function create() {
		if ( null === self::$template_loader ) {
			self::$template_loader = new Templates( 'restaurant', wro()->get_dir_path() . 'templates/' );
		}
		return self::$template_loader;
	}

}
