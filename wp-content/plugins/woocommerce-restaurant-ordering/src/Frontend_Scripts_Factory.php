<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering;

/**
 * Factory class to create/retrieve the Ordering scripts service.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Frontend_Scripts_Factory {

	private static $scripts = null;

	/**
	 * Factory method to create/return the scripts service.
	 *
	 * @return Frontend_Scripts the scripts service.
	 */
	public static function create() {
		if ( null === self::$scripts ) {
			$scripts = wro()->get_service( 'frontend_scripts' );

			if ( ! $scripts ) {
				_doing_it_wrong( __METHOD__, 'The frontend_scripts service has not been initialised.' );
			}

			self::$scripts = $scripts;
		}

		return self::$scripts;
	}

}
