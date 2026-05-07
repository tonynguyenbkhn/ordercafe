<?php

namespace TWMP_THEME\Inc;

use TWMP_THEME\Inc\Traits\Singleton;

class Calendar_Theme
{
	use Singleton;

	protected function __construct()
	{
		$this->setup_hooks();
	}

	protected function setup_hooks()
	{
		add_action('rest_api_init', [$this, 'register_routes']);
	}

	public function get_filter_options()
	{
		return [
			'types' => [
				'all' => __('All type', 'twmp-ath'),
				'show_event_festival' => __('Show / Event / Festival', 'twmp-ath'),
				'class_workshop' => __('Class / Workshop', 'twmp-ath'),
				'for_school' => __('For School', 'twmp-ath'),
				'for_company' => __('For Company', 'twmp-ath'),
			],
			'locations' => [
				'all' => __('All location', 'twmp-ath'),
				'lfay' => __('LFAY', 'twmp-ath'),
				'ath_theatre' => __('ATH theatre', 'twmp-ath'),
			],
			'statuses' => [
				'all' => __('All status', 'twmp-ath'),
				'coming_soon' => __('Coming Soon', 'twmp-ath'),
				'available' => __('Available', 'twmp-ath'),
				'almost_full' => __('Almost Full', 'twmp-ath'),
				'happening' => __('Happening', 'twmp-ath'),
				'completed' => __('Completed', 'twmp-ath'),
			],
		];
	}

	public function register_routes()
	{
		register_rest_route(
			'twmp/v1',
			'/calendar-events',
			[
				'methods' => 'GET',
				'callback' => [$this, 'rest_get_calendar_events'],
				'permission_callback' => '__return_true',
			]
		);
	}

	public function rest_get_calendar_events(\WP_REST_Request $request)
	{
		$filters = [
			'type' => sanitize_key((string) $request->get_param('type')),
			'location' => sanitize_key((string) $request->get_param('location')),
			'status' => sanitize_key((string) $request->get_param('status')),
		];

		$start = $this->sanitize_datetime((string) $request->get_param('start'));
		$end = $this->sanitize_datetime((string) $request->get_param('end'));
		$year = absint($request->get_param('year'));
		$month = absint($request->get_param('month'));
		$view = sanitize_key((string) $request->get_param('view'));

		if (! $start || ! $end) {
			if ($year < 1970) {
				$year = (int) wp_date('Y');
			}

			if ($month > 0 && $month <= 12) {
				$start = sprintf('%04d-%02d-01 00:00:00', $year, $month);
				$end = wp_date('Y-m-t 23:59:59', strtotime($start));
			} elseif ('year' === $view) {
				$start_month = absint($request->get_param('start_month'));
				$start_month = $start_month >= 0 && $start_month <= 11 ? $start_month + 1 : 1;
				$start = sprintf('%04d-%02d-01 00:00:00', $year, $start_month);
				$end = wp_date('Y-m-t 23:59:59', strtotime('+5 months', strtotime($start)));
			} else {
				$start = sprintf('%04d-01-01 00:00:00', $year);
				$end = sprintf('%04d-12-31 23:59:59', $year);
			}
		}

		$events = $this->query_events($start, $end, $filters);

		return new \WP_REST_Response(
			[
				'start' => $start,
				'end' => $end,
				'events' => $events,
			],
			200
		);
	}

	public function query_events($start, $end, array $filters = [])
	{
		$product_ids = get_posts(
			[
				'post_type' => 'product',
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'fields' => 'ids',
				'orderby' => 'date',
				'order' => 'DESC',
				'no_found_rows' => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'tax_query' => [
					[
						'taxonomy' => 'product_cat',
						'field'    => 'slug',
						'terms'    => [
							'event-show',
							'class-workshop',
						],
					],
				],
			]
		);

		$range_start = $this->sanitize_timestamp($start);
		$range_end = $this->sanitize_timestamp($end);
		$events = [];

		foreach ($product_ids as $product_id) {
			$product_events = $this->build_product_events((int) $product_id, $range_start, $range_end, $filters);

			if (! empty($product_events)) {
				$events = array_merge($events, $product_events);
			}
		}

		usort(
			$events,
			static function ($left, $right) {
				return strcmp((string) $left['start'], (string) $right['start']);
			}
		);

		return $events;
	}

	private function build_product_events($product_id, $range_start, $range_end, array $filters = [])
	{
		$product = get_post($product_id);

		if (! $product instanceof \WP_Post || 'publish' !== $product->post_status) {
			return [];
		}

		$type = sanitize_key((string) get_field('ath_event_type', $product_id));
		$status = sanitize_key((string) get_field('ath_status', $product_id));
		$location_key = sanitize_key((string) get_field('ath_location', $product_id));
		$location_detail = trim((string) get_field('ath_location_detail', $product_id));
		$location_label = $location_detail !== '' ? $location_detail : $this->get_location_label($location_key);
		$short_info = trim((string) get_field('ath_short_info', $product_id));
		$age_display = trim((string) get_field('ath_age_display', $product_id));
		$language = $this->join_field_values(get_field('ath_language', $product_id));
		$format = trim((string) get_field('ath_format', $product_id));
		$subtitle = trim((string) get_field('ath_subtitle', $product_id));
		$demonstration = trim((string) get_field('ath_demonstration', $product_id));
		$badges = get_field('ath_badges', $product_id);
		$start_datetime = (string) get_field('ath_start_datetime', $product_id);
		$end_datetime = (string) get_field('ath_end_datetime', $product_id);
		$thumbnail_id = get_post_thumbnail_id($product_id);
		$thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'large') : '';
		$thumbnail_alt = $thumbnail_id ? get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) : '';
		$permalink = get_permalink($product_id);
		$product_cat = $this->get_product_categories($product_id);
		$timezone = wp_timezone();
		$default_duration = 2 * HOUR_IN_SECONDS;
		$rows = (array) get_field('ath_performance_schedule', $product_id);
		$instances = [];

		if (! empty($rows)) {
			foreach ($rows as $row) {
				$datetime_raw = isset($row['performance_datetime']) ? trim((string) $row['performance_datetime']) : '';

				if ($datetime_raw === '') {
					continue;
				}

				$instance_start = $this->parse_datetime($datetime_raw, $timezone);

				if (! $instance_start) {
					continue;
				}

				$instance_end = $this->resolve_end_timestamp($instance_start, $end_datetime, $default_duration);

				if (! $this->is_in_range($instance_start, $instance_end, $range_start, $range_end)) {
					continue;
				}

				if (! $this->passes_filters($type, $location_key, $status, $filters)) {
					continue;
				}

				$instances[] = $this->format_event_item(
					$product_id,
					$product->post_title,
					$instance_start,
					$instance_end,
					[
						'type' => $type,
						'status' => $status,
						'location' => $location_key,
						'location_label' => $location_label,
						'short_info' => $short_info,
						'age_display' => $age_display,
						'language' => $language,
						'format' => $format,
						'subtitle' => $subtitle,
						'demonstration' => $demonstration,
						'thumbnail_url' => $thumbnail_url,
						'thumbnail_alt' => $thumbnail_alt,
						'permalink' => $permalink,
						'product_cat' => $product_cat,
						'badges' => is_array($badges) ? $badges : [],
					]
				);
			}
		} elseif ($start_datetime !== '') {
			$instance_start = $this->parse_datetime($start_datetime, $timezone);

			if ($instance_start) {
				$instance_end = $this->resolve_end_timestamp($instance_start, $end_datetime, $default_duration);

				if ($this->is_in_range($instance_start, $instance_end, $range_start, $range_end) && $this->passes_filters($type, $location_key, $status, $filters)) {
					$instances[] = $this->format_event_item(
						$product_id,
						$product->post_title,
						$instance_start,
						$instance_end,
						[
							'type' => $type,
							'status' => $status,
							'location' => $location_key,
							'location_label' => $location_label,
							'short_info' => $short_info,
							'age_display' => $age_display,
							'language' => $language,
							'format' => $format,
							'subtitle' => $subtitle,
							'demonstration' => $demonstration,
							'thumbnail_url' => $thumbnail_url,
							'thumbnail_alt' => $thumbnail_alt,
							'permalink' => $permalink,
							'product_cat' => $product_cat,
							'badges' => is_array($badges) ? $badges : [],
						]
					);
				}
			}
		}

		return $instances;
	}

	private function format_event_item($product_id, $title, $start_timestamp, $end_timestamp, array $meta)
	{
		$color = $this->get_event_color($meta['type'] ?? '', $meta['status'] ?? '');
		$day_key = wp_date('Y-m-d', $start_timestamp);
		$week_key = wp_date('o-\WW', $start_timestamp);
		$time_range = $this->format_time_range($start_timestamp, $end_timestamp);

		return [
			'id' => sprintf('%d-%s', $product_id, md5($day_key . '|' . $time_range)),
			'product_id' => $product_id,
			'title' => html_entity_decode((string) $title, ENT_QUOTES, get_bloginfo('charset')),
			'start' => wp_date('c', $start_timestamp),
			'end' => wp_date('c', $end_timestamp),
			'allDay' => false,
			'url' => $meta['permalink'] ?? '',
			'backgroundColor' => $color['background'],
			'borderColor' => $color['border'],
			'textColor' => $color['text'],
			'classNames' => [
				'calendar-event',
				'calendar-event--' . ($meta['type'] ?: 'default'),
			],
			'extendedProps' => [
				'productId' => $product_id,
				'type' => $meta['type'] ?? '',
				'typeLabel' => $this->get_type_label($meta['type'] ?? ''),
				'status' => $meta['status'] ?? '',
				'statusLabel' => $this->get_status_label($meta['status'] ?? ''),
				'location' => $meta['location'] ?? '',
				'locationLabel' => $meta['location_label'] ?? '',
				'shortInfo' => $meta['short_info'] ?? '',
				'ageDisplay' => $meta['age_display'] ?? '',
				'language' => $meta['language'] ?? '',
				'format' => $meta['format'] ?? '',
				'subtitle' => $meta['subtitle'] ?? '',
				'demonstration' => $meta['demonstration'] ?? '',
				'thumbnailUrl' => $meta['thumbnail_url'] ?? '',
				'thumbnailAlt' => $meta['thumbnail_alt'] ?? '',
				'permalink' => $meta['permalink'] ?? '',
				'product_cat' => $meta['product_cat'] ?? [],
				'badges' => $meta['badges'] ?? [],
				'dayKey' => $day_key,
				'weekKey' => $week_key,
				'timeRange' => $time_range,
			],
		];
	}

	private function passes_filters($type, $location, $status, array $filters)
	{
		if (! empty($filters['type']) && 'all' !== $filters['type'] && $filters['type'] !== $type) {
			return false;
		}

		if (! empty($filters['location']) && 'all' !== $filters['location'] && $filters['location'] !== $location) {
			return false;
		}

		if (! empty($filters['status']) && 'all' !== $filters['status'] && $filters['status'] !== $status) {
			return false;
		}

		return true;
	}

	private function is_in_range($start_timestamp, $end_timestamp, $range_start, $range_end)
	{
		return $end_timestamp >= $range_start && $start_timestamp <= $range_end;
	}

	private function resolve_end_timestamp($start_timestamp, $end_datetime, $default_duration)
	{
		if ($end_datetime !== '') {
			$end_timestamp = $this->parse_datetime($end_datetime, wp_timezone());

			if ($end_timestamp && $end_timestamp > $start_timestamp) {
				return $end_timestamp;
			}
		}

		return $start_timestamp + max(1, (int) $default_duration);
	}

	private function format_time_range($start_timestamp, $end_timestamp)
	{
		return wp_date('H:i', $start_timestamp) . ' - ' . wp_date('H:i', $end_timestamp);
	}

	private function get_event_color($type, $status)
	{
		if ('class_workshop' === $type) {
			return [
				'background' => '#B8E0AA',
				'border' => '#A2D392',
				'text' => '#1D221A',
			];
		}

		if ('completed' === $status) {
			return [
				'background' => '#E7E7E7',
				'border' => '#CFCFCF',
				'text' => '#404040',
			];
		}

		return [
			'background' => '#F8B2A5',
			'border' => '#F29D8F',
			'text' => '#2A1A17',
		];
	}

	private function get_type_label($type)
	{
		$types = $this->get_filter_options()['types'];

		return $types[$type] ?? $type;
	}

	private function get_status_label($status)
	{
		$statuses = $this->get_filter_options()['statuses'];

		return $statuses[$status] ?? $status;
	}

	private function get_location_label($location)
	{
		$locations = $this->get_filter_options()['locations'];

		return $locations[$location] ?? $location;
	}

	private function join_field_values($value)
	{
		if (is_array($value)) {
			$values = array_filter(array_map('sanitize_text_field', $value));

			return implode(', ', $values);
		}

		return is_scalar($value) ? (string) $value : '';
	}

	private function get_product_categories($product_id)
	{
		$terms = get_the_terms($product_id, 'product_cat');

		if (empty($terms) || is_wp_error($terms)) {
			return [];
		}

		return array_values(
			array_map(
				static function ($term) {
					return [
						'id' => (int) $term->term_id,
						'slug' => (string) $term->slug,
						'name' => (string) $term->name,
					];
				},
				$terms
			)
		);
	}

	private function sanitize_datetime($value)
	{
		$value = trim((string) $value);

		return $value !== '' ? $value : '';
	}

	private function sanitize_timestamp($value)
	{
		$timestamp = strtotime((string) $value);

		return $timestamp ? $timestamp : current_time('timestamp');
	}

	private function parse_datetime($datetime, \DateTimeZone $timezone)
	{
		$datetime = trim((string) $datetime);

		if ($datetime === '') {
			return 0;
		}

		$time = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $datetime, $timezone);

		if ($time instanceof \DateTimeImmutable) {
			return $time->getTimestamp();
		}

		$timestamp = strtotime($datetime);

		return $timestamp ? $timestamp : 0;
	}
}
