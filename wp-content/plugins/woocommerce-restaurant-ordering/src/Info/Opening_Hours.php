<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Info;

use Barn2\Plugin\WC_Restaurant_Ordering\Util;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;

/**
 * Represents the opening hours of a restaurant in the order system.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Opening_Hours {

	/**
	 * @var array $opening_times Holds the complete opening times array [ day of week => [ array of open periods ] ]
	 */
	private $opening_times;

	/**
	 * @var DateTimeZone $timezone The current timezone, retrieved from WordPress.
	 */
	private $timezone;

	private static $days_of_week = null;

	/**
	 * Constructs a new Opening_Hours object based on the supplied opening times.
	 *
	 * @param array $opening_times The opening times array.
	 */
	public function __construct( $opening_times = [] ) {
		$opening_times       = is_array( $opening_times ) ? array_filter( $opening_times ) : [];
		$this->opening_times = $this->parse_times( $opening_times );
		$this->timezone      = Util::get_timezone();
	}

	/**
	 * Get the days of the week array used internally to create and process opening times. These days are not
	 * localized - see @return array The days of the week
	 *
	 * @link Opening_Hours_Formatter for that.
	 */
	public static function get_days_of_week() {
		if ( null === self::$days_of_week ) {
			$days = [
				'monday',
				'tuesday',
				'wednesday',
				'thursday',
				'friday',
				'saturday',
				'sunday'
			];

			if ( apply_filters( 'wc_restaurant_ordering_opening_hours_start_week_on_sunday', false ) ) {
				array_pop( $days );
				array_unshift( $days, 'sunday' );
			}

			self::$days_of_week = apply_filters( 'wc_restaurant_ordering_opening_hours_days_of_week', $days );
		}

		return self::$days_of_week;
	}

	/**
	 * Get the current day of the week. Returned in English lowercase. Used in arrays as the day 'key'.
	 *
	 * @return string The day of the week, e.g. 'monday'
	 * @throws Exception If the DateTimeImmutable object throws an error
	 */
	public static function get_current_day_of_week() {
		return self::get_day_of_week( new DateTimeImmutable( 'now', Util::get_timezone() ) );
	}

	/**
	 * Get all opening times as an array, or if $day is passed the opening times for the specified day.
	 *
	 * @param string $day The day of the week to retrieve times for (optional). E.g. 'monday'.
	 * @return array The opening times array.
	 */
	public function get_opening_times( $day = null ) {
		if ( $day && array_key_exists( $day, $this->opening_times ) ) {
			return ! empty( $this->opening_times[ $day ] ) ? $this->opening_times[ $day ] : [];
		}

		return $this->opening_times;
	}

	/**
	 * Checks if there is more than one opening period specified on any day of the week.
	 *
	 * @return bool true if there are multiple opening periods on any day, false otherwise.
	 */
	public function has_additional_opening_periods() {
		return ! empty( $this->get_second_opening_periods() );
	}

	/**
	 * Is the restaurant currently open?
	 *
	 * @param string $time The time to check against. Defaults to 'now'
	 * @return bool true if the restaurant is currently open, false if closed.
	 * @throws Exception If there's an error creating a DateTime object.
	 */
	public function is_open( $time = 'now' ) {
		// Default to open. If there are no opening times at all, then the restaurant is considered open.
		$is_open = true;

		if ( $this->is_valid() ) {
			$is_open  = false;
			$datetime = new DateTimeImmutable( $time, $this->timezone );

			// Get the day of week to compare against.
			$day             = self::get_day_of_week( $datetime );
			$opening_periods = $this->opening_periods_to_datetimes( $day, $this->get_opening_times( $day ) );

			// Check each opening period for the current day.
			if ( ! empty( $opening_periods ) ) {
				foreach ( $opening_periods as $opening_period ) {
					if ( $datetime >= $opening_period['from'] && $datetime < $opening_period['to'] ) {
						$is_open = true;
					}
				}
			}
		}

		return apply_filters( 'wc_restaurant_ordering_is_restaurant_open', $is_open );
	}

	/**
	 * Are the opening hours valid? It is considered valid if there's at least one opening period defined for at least one day.
	 *
	 * @return bool true if at least one day has one opening period, false otherwise.
	 */
	public function is_valid() {
		return ! empty( $this->get_first_opening_periods() );
	}

	/**
	 * Get the next closing time if the restaurant is currently open.
	 *
	 * @return DateTimeInterface|false The closing time (DateTime object), or false if there are no open periods or the restaurant is currently closed.
	 */
	public function next_closing_time() {
		if ( ! $this->is_open() ) {
			return false;
		}

		$now                   = new DateTimeImmutable( 'now', $this->timezone );
		$today                 = self::get_day_of_week( $now );
		$opening_periods_today = $this->opening_periods_to_datetimes( $today, $this->get_opening_times( $today ) );

		if ( ! empty( $opening_periods_today ) ) {
			foreach ( $opening_periods_today as $opening_period ) {
				if ( $opening_period['to'] > $now ) {
					return $opening_period['to'];
				}
			}
		}

		return false;
	}

	/**
	 * Get the next opening time for the restaurant. If the restaurant is currently open, the next opening time (not the
	 * opening time of the current period) is returned. The datetime returned will have the correct day of the week set.
	 *
	 * @return DateTimeInterface|false The datetime of the next opening time, or false if there are no valid open periods.
	 */
	public function next_opening_time() {
		$now          = new DateTimeImmutable( 'now', $this->timezone );
		$datetime     = new DateTime( 'now', $this->timezone );
		$days_checked = 0;
		$max_days     = count( self::get_days_of_week() );

		while ( $days_checked < $max_days ) {
			$day_to_check    = self::get_day_of_week( $datetime );
			$opening_periods = $this->opening_periods_to_datetimes( $day_to_check, $this->get_opening_times( $day_to_check ) );

			if ( ! empty( $opening_periods ) ) {
				foreach ( $opening_periods as $opening_period ) {
					if ( $opening_period['from'] > $now ) {
						return $opening_period['from'];
					}
				}
			}

			$days_checked++;
			$datetime->modify( '+1 day' );
		}

		return false;
	}

	/**
	 * Get the day of the week for the given datetime. Returns the 'l' format in English lowercase, e.g. 'monday'
	 *
	 * @param DateTimeInterface $datetime The datetime to get the day for
	 * @return string The day of the week, e.g. 'monday'
	 */
	private static function get_day_of_week( DateTimeInterface $datetime ) {
		return strtolower( $datetime->format( 'l' ) );
	}

	/**
	 * Get all opening periods for the specified period number (1 or 2).
	 *
	 * @param int $period_number The period number - 1 or 2
	 * @return array The opening periods, or an empty array if there are none.
	 */
	private function get_opening_periods( int $period_number ) {
		return array_filter(
			array_map(
				function ( $opening_period ) use ( $period_number ) {
					if ( isset( $opening_period[ $period_number ] ) && $this->is_valid_opening_period( $opening_period[ $period_number ] ) ) {
						return $opening_period[ $period_number ];
					}

					return null;
				},
				$this->opening_times
			)
		);
	}

	private function get_first_opening_periods() {
		return $this->get_opening_periods( 1 );
	}

	private function get_second_opening_periods() {
		return $this->get_opening_periods( 2 );
	}

	private function is_valid_opening_period( $period ) {
		$is_valid = is_array( $period ) && ! empty( $period['from'] ) && ! empty( $period['to'] );

		try {
			$from = new DateTime( $period['from'] );
			$to   = new DateTime( $period['to'] );
		} catch (Exception $e) {
			$is_valid = false;
		}

		if ( $is_valid ) {			

			$from_hour = $from->format( 'H' );
			$to_hour   = $to->format( 'H' );

			// If 'to' goes into the following day (e.g. 2:00am), add on 1 day to allow our $from < $to comparison to work.
			if ( $to_hour < $from_hour && $to_hour < 12 ) {
				$to->modify( '+1 day' );
			}

			$is_valid = $from < $to;
		}

		return $is_valid;
	}

	private function opening_periods_to_datetimes( $day, array $opening_periods ) {
		$result = [];

		foreach ( $opening_periods as $period => $opening_period ) {
			$open_time  = new DateTime( "{$day} {$opening_period['from']}", $this->timezone );
			$close_time = new DateTime( "{$day} {$opening_period['to']}", $this->timezone );

			if ( $open_time > $close_time ) {
				$close_time->modify( '+1 day' );
			}

			$result[ $period ] = [
				'from' => $open_time,
				'to'   => $close_time
			];
		}

		return $result;
	}

	private function parse_times( array $opening_times ) {
		$parsed        = [];
		$period_number = 1;

		foreach ( $opening_times as $day => $opening_periods ) {
			// Only process opening times if the day exists in the 'days of week' array.
			if ( ! in_array( $day, self::get_days_of_week(), true ) ) {
				continue;
			}

			if ( empty( $opening_periods ) || ! is_array( $opening_periods ) || count( $opening_periods ) > 2 ) {
				continue;
			}

			foreach ( $opening_periods as $opening_period ) {
				if ( $this->is_valid_opening_period( $opening_period ) ) {
					$parsed[ $day ][ $period_number ] = $opening_period;
					$period_number++;
				}
			}

			$period_number = 1;
		}

		// Return parsed times in the order specified by the 'days of week' array.
		return array_merge(
			array_fill_keys( self::get_days_of_week(), [] ),
			$parsed
		);
	}

}
