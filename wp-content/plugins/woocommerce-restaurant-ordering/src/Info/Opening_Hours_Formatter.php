<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Info;

use Barn2\Plugin\WC_Restaurant_Ordering\Util;
use DateTimeImmutable;
use DateTimeInterface;

class Opening_Hours_Formatter {

	private const DAYS_INDEX = [
		'sunday'    => 0,
		'monday'    => 1,
		'tuesday'   => 2,
		'wednesday' => 3,
		'thursday'  => 4,
		'friday'    => 5,
		'saturday'  => 6,
	];

	/**
	 * Format the opening times object, localized to the current locale. Days of the week are
	 * returned in the current language. Times are formatted based on the WP default time format.
	 *
	 * Return format:
	 * array [
	 *    'monday' => [
	 *       'label' => 'Monday',
	 *       'periods' => [
	 *          1 => [
	 *             'from' => '10:00am',
	 *             'to => '2:00pm'
	 *          ],
	 *          2 => [
	 *             'from' => '10:00am',
	 *             'to' => '2:00pm'
	 *          ]
	 *       ]
	 *    ],
	 *    'tuesday' => etc...
	 * ]
	 *
	 * @param Opening_Hours $hours The opening hours to format
	 * @return array A localized array of opening times
	 */
	public static function format( Opening_Hours $hours ) {
		$localized = [];

		foreach ( $hours->get_opening_times() as $day => $opening_periods ) {
			if ( ! array_key_exists( $day, self::DAYS_INDEX ) ) {
				continue;
			}

			$localized[ $day ] = self::format_periods_for_day( $day, $opening_periods );
		}

		return $localized;
	}

	/**
	 * Format the opening times for a given day, localized to the current locale. Times are formatted based on the
	 * WP default time format.
	 *
	 * Return format:
	 * array [
	 *    'label' => 'Monday',
	 *    'periods' => [
	 *       1 => [
	 *          'from' => '10:00am',
	 *          'to => '2:00pm'
	 *       ],
	 *       2 => [
	 *          'from' => '10:00am',
	 *          'to' => '2:00pm'
	 *       ]
	 *    ]
	 * ]
	 *
	 * @param string $day            The day of the week - 'monday' through 'sunday' in English lowercase
	 * @param array $opening_periods The opening periods for the day to format
	 * @return array A localized array of opening times
	 */
	public static function format_periods_for_day( $day, array $opening_periods ) {
		global $wp_locale;

		if ( ! array_key_exists( $day, self::DAYS_INDEX ) ) {
			return [];
		}

		$localized['label']   = $wp_locale->get_weekday( self::DAYS_INDEX[ $day ] );
		$localized['periods'] = [];
		$time_format          = self::get_time_format();

		foreach ( $opening_periods as $period => $opening_period ) {
			$from = new DateTimeImmutable( $opening_period['from'] );
			$to   = new DateTimeImmutable( $opening_period['to'] );

			$localized['periods'][ $period ] = [
				// Format times using specified time format.
				'from' => $from->format( $time_format ),
				'to'   => $to->format( $time_format )
			];
		}

		return $localized;
	}

	/**
	 * Formats an open or close time for a restaurant. Times are formatted based on the WP default time format.
	 *
	 * @param DateTimeInterface $datetime The datetime object to format
	 * @param bool $include_day           Whether to include the day in the formatted time, if the open/close time is a different day to today.
	 * @return string The formatted time
	 */
	public static function format_open_close_time( DateTimeInterface $datetime, $include_day = false ) {
		$timezone    = Util::get_timezone();
		$time_format = self::get_time_format();

		$now            = new DateTimeImmutable( 'now', $timezone );
		$today          = $now->format( 'w' );
		$open_close_day = $datetime->format( 'w' );

		if ( $include_day && $today !== $open_close_day ) {
			$time_format = apply_filters( 'wc_restaurant_ordering_opening_hours_time_with_day_format', $time_format . ', l' );
		}

		if ( function_exists( 'wp_date' ) ) {
			$result = wp_date( $time_format, $datetime->getTimestamp(), $timezone );
		} else {
			$result = $datetime->format( $time_format );
		}

		return $result;
	}

	private static function get_time_format() {
		$time_format = get_option( 'time_format', 'g:ia' );
		return apply_filters( 'wc_restaurant_ordering_opening_hours_time_format', $time_format );
	}

}
