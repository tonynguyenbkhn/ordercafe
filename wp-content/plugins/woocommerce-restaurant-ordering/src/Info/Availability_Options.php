<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Info;

use Barn2\Plugin\WC_Restaurant_Ordering\Settings;
use Barn2\Plugin\WC_Restaurant_Ordering\Util;

/**
 * Stores availability options for the restaurant menu (the opening hours, etc).
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Availability_Options {

	/**
	 * @var array $args The supplied args to create the availability options.
	 */
	protected $args;

	/**
	 * @var Opening_Hours $opening_hours The opening hours for the restaurant, created from the supplied $args.
	 */
	protected $opening_hours;

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
			'wc_restaurant_ordering_availability_default_options',
			[
				'enable_opening_hours' => false,
				'opening_hours'        => [],
				'open_notice'          => __( 'Open until {close_time}', 'woocommerce-restaurant-ordering' ),
				'closed_notice'        => __( 'The restaurant is currently closed', 'woocommerce-restaurant-ordering' ),
			]
		);
	}

	/**
	 * @return bool
	 */
	public function are_opening_hours_enabled(): bool {
		return (bool) $this->args['enable_opening_hours'];
	}

	/**
	 * @return string
	 */
	public function get_closed_notice(): string {
		return apply_filters( 'wc_restaurant_ordering_availability_closed_notice', $this->replace_tags( $this->args['closed_notice'] ) );
	}

	/**
	 * @return string
	 */
	public function get_open_notice(): string {
		return apply_filters( 'wc_restaurant_ordering_availability_open_notice', $this->replace_tags( $this->args['open_notice'] ) );
	}

	/**
	 * @return Opening_Hours
	 */
	public function get_opening_hours() {
		return $this->opening_hours;
	}

	/**
	 * Get the full list of availability options as an array.
	 *
	 * @return array The availability options
	 */
	public function get_options(): array {
		return $this->args;
	}

	public function is_restaurant_open(): bool {
		return $this->opening_hours->is_open();
	}

	/**
	 * Get the availability plugin settings from the database, merged with the defaults.
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

		$args['enable_opening_hours'] = filter_var( $args['enable_opening_hours'], FILTER_VALIDATE_BOOLEAN );

		if ( $args['enable_opening_hours'] ) {
			$args['opening_hours'] = (array) $args['opening_hours'];
			$args['open_notice']   = trim( wp_strip_all_tags( $args['open_notice'] ) );
			$args['closed_notice'] = trim( wp_strip_all_tags( $args['closed_notice'] ) );
		} else {
			$args['opening_hours'] = [];
			$args['open_notice']   = '';
			$args['closed_notice'] = '';
		}

		$this->opening_hours = new Opening_Hours( $args['opening_hours'] );

		return apply_filters( 'wc_restaurant_ordering_availability_parse_args', $args );
	}

	/**
	 * Replace tags in the availability notices, e.g. {open_time}
	 *
	 * @param string $text The text to replace tags in
	 * @return string The text with replacements
	 */
	private function replace_tags( string $text ): string {
		$replacements = [
			'open_time'  => '',
			'close_time' => ''
		];

		$opening_hours = Util::get_opening_hours();

		if ( $opening_hours->is_open() ) {
			$next_closing_time = $opening_hours->next_closing_time();

			if ( $next_closing_time ) {
				$replacements['close_time'] = apply_filters(
					'wc_restaurant_ordering_availability_next_close_time',
					Opening_Hours_Formatter::format_open_close_time( $next_closing_time ),
					$next_closing_time
				);
			}
		} else {
			$next_opening_time = $opening_hours->next_opening_time();

			if ( $next_opening_time ) {
				$replacements['open_time'] = apply_filters(
					'wc_restaurant_ordering_availability_next_open_time',
					Opening_Hours_Formatter::format_open_close_time( $next_opening_time, apply_filters( 'wc_restaurant_ordering_availability_open_time_include_day', true ) ),
					$next_opening_time
				);
			}
		}

		// Allow plugins to add or override text replacement.
		$replacements = apply_filters( 'wc_restaurant_ordering_availability_text_replacements', $replacements, $opening_hours, $this );

		foreach ( $replacements as $tag => $replacement ) {
			$text = str_replace( "{{$tag}}", $replacement, $text );
		}

		return $text;
	}

}
