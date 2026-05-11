<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Info;

use Barn2\Plugin\WC_Restaurant_Ordering\Settings;

/**
 * Stores basic restaurant information (the address, opening hours, etc).
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Information_Options {

	/**
	 * @var array $args The args array used to create the options object.
	 */
	protected $args;

	/**
	 * Constructor.
	 *
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
	 * Get the availability default options.
	 *
	 * @return array The defaults
	 */
	public static function get_defaults() {
		return apply_filters(
			'wc_restaurant_ordering_info_default_options',
			[
				'restaurant_name'    => '',
				'restaurant_address' => '',
				'delivery_notice'    => ''
			]
		);
	}

	/**
	 * Get the delivery/collection notice for this restaurant.
	 *
	 * @return string The delivery notice
	 */
	public function get_delivery_notice() {
		return apply_filters( 'wc_restaurant_ordering_info_delivery_notice', $this->args['delivery_notice'] );
	}

	/**
	 * Get the full list of information options as an array.
	 *
	 * @return array The information options
	 */
	public function get_options() {
		return $this->args;
	}

	/**
	 * Get the restaurant address.
	 *
	 * @return string The restaurant address
	 */
	public function get_restaurant_address() {
		return apply_filters( 'wc_restaurant_ordering_info_restaurant_address', $this->args['restaurant_address'] );
	}

	/**
	 * Get the restaurant name.
	 *
	 * @return string The restaurant name
	 */
	public function get_restaurant_name() {
		return apply_filters( 'wc_restaurant_ordering_info_restaurant_name', $this->args['restaurant_name'] );
	}

	/**
	 * Get the plugin settings related to restaurant information. If no settings are stored, the default value from get_defaults is returned.
	 *
	 * @return array The settings
	 * @see get_defaults
	 */
	protected function get_settings() {
		$defaults = self::get_defaults();
		return Settings::get_settings( array_keys( $defaults ), $defaults );
	}

	private function parse_args( array $args ): array {
		$defaults = self::get_defaults();
		$args     = array_merge( $defaults, array_intersect_key( $args, $defaults ) );

		$args['restaurant_name']    = trim( wp_strip_all_tags( $args['restaurant_name'] ) );
		$args['restaurant_address'] = trim( wp_strip_all_tags( $args['restaurant_address'] ) );
		$args['delivery_notice']    = trim( wp_strip_all_tags( $args['delivery_notice'] ) );

		return apply_filters( 'wc_restaurant_ordering_info_parse_args', $args );
	}

}
