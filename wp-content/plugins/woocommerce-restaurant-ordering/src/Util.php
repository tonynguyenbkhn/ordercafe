<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering;

use Barn2\Plugin\WC_Restaurant_Ordering\Info\Availability_Options;
use Barn2\Plugin\WC_Restaurant_Ordering\Info\Information_Options;
use Barn2\Plugin\WC_Restaurant_Ordering\Info\Opening_Hours;
use DateTimeZone;
use Exception;
use WC_Product;

/**
 * Utility functions.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Util {

	private static $default_availability_options    = null;
	private static $default_restaurant_info_options = null;

	/**
	 * Get the URL to the /assets folder, without trailing slash.
	 *
	 * @return string The assets URL
	 */
	public static function get_assets_url(): string {
		return plugins_url( 'assets', PLUGIN_FILE );
	}

	/**
	 * Get the default availability options for the restaurant.
	 *
	 * @return Availability_Options The availability options
	 */
	public static function get_default_availability_options(): Availability_Options {
		if ( null === self::$default_availability_options ) {
			self::$default_availability_options = apply_filters( 'wc_restaurant_ordering_default_availability_options', new Availability_Options() );
		}
		return self::$default_availability_options;
	}

	/**
	 * Get the default information options (name, address, etc) for the restaurant.
	 *
	 * @return Information_Options The information options
	 */
	public static function get_default_restaurant_info_options(): Information_Options {
		if ( null === self::$default_restaurant_info_options ) {
			self::$default_restaurant_info_options = apply_filters( 'wc_restaurant_ordering_default_information_options', new Information_Options() );
		}
		return self::$default_restaurant_info_options;
	}

	/**
	 * Gets the menu image size as an array, and runs the result through a filter.
	 *
	 * @param int $image_size The image size in pixels (i.e. the width).
	 * @param WC_Product|null $product The product object to pass to the filter.
	 * @return array The image size array
	 */
	public static function get_menu_image_size_array( int $image_size, ?WC_Product $product = null ) {
		return apply_filters( 'wc_restaurant_ordering_image_size', [ $image_size, $image_size ], $product );
	}

	/**
	 * Get the opening hours for the restaurant.
	 *
	 * @return Opening_Hours The opening hours object
	 */
	public static function get_opening_hours(): Opening_Hours {
		return self::get_default_availability_options()->get_opening_hours();
	}

	/**
	 * Get the error message displayed when the restaurant is closed. This is used, for example, of the Cart page when
	 * the customer tries to complete an order when the restaurant is closed.
	 *
	 * @return string The closed notice
	 */
	public static function get_restaurant_closed_error_message(): string {
		return apply_filters( 'wc_restaurant_ordering_availability_closed_error', __( 'Sorry, the restaurant is now closed.', 'woocommerce-restaurant-ordering' ) );
	}

	/**
	 * Get the timezone to use for the restaurant opening hours. Returns the result of wp_timezone().
	 *
	 * @return DateTimeZone The restaurant timezone
	 */
	public static function get_timezone(): DateTimeZone {
		return apply_filters( 'wc_restaurant_ordering_timezone', wp_timezone() );
	}

	/**
	 * Get the WooCommerce notices for the specified notice type. Notices are returned as a flat array of strings.
	 *
	 * @param string $notice_type The notice type, e.g. 'error'
	 * @return array[] The array of notices.
	 */
	public static function get_wc_notices( $notice_type ): array {
		$notices = wc_get_notices( $notice_type );
		wc_clear_notices();

		// WC > 3.8 uses nested arrays for each notice.
		if ( ! empty( $notices ) && isset( $notices[0]['notice'] ) ) {
			$notices = wp_list_pluck( $notices, 'notice' );
		}

		return array_filter( $notices );
	}

	/**
	 * Is the restaurant currently accepting orders? By default, the restaurant is accepting orders when it is open.
	 *
	 * @return bool true if accepting orders, false otherwise
	 * @throws Exception If there's an error fetching the opening hours.
	 */
	public static function is_accepting_orders(): bool {
		$is_accepting_orders = true;

		if ( ! apply_filters( 'wc_restaurant_ordering_allow_orders_when_closed', false ) ) {
			$is_accepting_orders = self::is_restaurant_open();
		}

		return $is_accepting_orders;
	}

	/**
	 * Is the restaurant currently open?
	 *
	 * @return bool true if open
	 * @throws Exception If there's an error fetching the opening hours.
	 */
	public static function is_restaurant_open(): bool {
		return self::get_opening_hours()->is_open();
	}

}
