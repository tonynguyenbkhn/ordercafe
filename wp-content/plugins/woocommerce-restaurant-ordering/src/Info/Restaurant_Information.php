<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Info;

use Barn2\Plugin\WC_Restaurant_Ordering\Component;
use Barn2\Plugin\WC_Restaurant_Ordering\Template_Loader_Factory;
use Barn2\Plugin\WC_Restaurant_Ordering\Util;

/**
 * Handles the display of basic restaurant information (address, opening times, etc).
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Restaurant_Information implements Component {

	/**
	 * @var Information_Options $restaurant_info The basic restaurant information.
	 */
	protected $restaurant_info;

	/**
	 * @var Availability_Options $availability The restaurant availability options.
	 */
	protected $availability;

	private $template_loader;

	public function __construct( Information_Options $restaurant_info, Availability_Options $availability ) {
		$this->restaurant_info = $restaurant_info;
		$this->availability    = $availability;
		$this->template_loader = Template_Loader_Factory::create();
	}

	public function render() {
		$template_args = $this->get_template_args();

		// Only render the restaurant info if there's something to show.
		if ( $this->can_render_template( $template_args ) ) {
			$this->load_js_template();
			return $this->template_loader->get_template( 'restaurant-info', $template_args );
		}

		return '';
	}

	private function can_render_template( $template_args ) {
		$can_render =
			! empty( $template_args['restaurant_address'] ) ||
			! empty( $template_args['availability_notice'] ) ||
			( ! empty( $template_args['delivery_notice'] ) && $template_args['show_delivery_notice'] );

		return apply_filters( 'wc_restaurant_ordering_info_can_render_template', $can_render, $template_args );
	}

	private function get_image_folder_url() {
		return Util::get_assets_url() . '/images';
	}

	private function get_template_args() {
		$is_restaurant_open      = $this->availability->is_restaurant_open();
		$are_opening_hours_valid = $this->are_opening_hours_valid();

		return apply_filters(
			'wc_restaurant_ordering_info_template_args',
			[
				'restaurant_name'          => $this->restaurant_info->get_restaurant_name(),
				'restaurant_address'       => $this->restaurant_info->get_restaurant_address(),
				'is_restaurant_open'       => $is_restaurant_open,
				'show_availability_notice' => apply_filters( 'wc_restaurant_ordering_info_show_availability_notice', $are_opening_hours_valid ),
				'availability_notice'      => $is_restaurant_open ? $this->availability->get_open_notice() : $this->availability->get_closed_notice(),
				'show_delivery_notice'     => apply_filters( 'wc_restaurant_ordering_info_show_delivery_notice', $is_restaurant_open ),
				'delivery_notice'          => $this->restaurant_info->get_delivery_notice(),
				'images_url'               => $this->get_image_folder_url(),
				'opening_hours'            => $this->availability->get_opening_hours(),
				'current_day'              => Opening_Hours::get_current_day_of_week(),
				'show_more_link'           => apply_filters( 'wc_restaurant_ordering_info_show_more_link', $are_opening_hours_valid )
			]
		);
	}

	private function load_js_template() {
		add_action( 'wp_footer', [ $this, 'render_js_templates' ] );
	}

	public function render_js_templates() {
		$this->template_loader->load_template_once(
			'restaurant-info/modal-template.php',
			[
				'modal_content' => $this->template_loader->get_template( 'restaurant-info/modal-content.php', $this->get_template_args() )
			]
		);
	}

	private function are_opening_hours_valid() {
		return $this->availability->are_opening_hours_enabled() && $this->availability->get_opening_hours()->is_valid();
	}

}
