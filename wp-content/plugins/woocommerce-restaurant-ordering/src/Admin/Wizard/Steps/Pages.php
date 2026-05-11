<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Restaurant_Ordering\Admin\Wizard\Setup_Wizard;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Setup_Wizard\Api;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Setup_Wizard\Step;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Setup_Wizard\Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Settings;

class Pages extends Step {
	/**
	 * Configure the step.
	 */
	public function init() {
		$this->set_id( 'pages' );
		$this->set_name( esc_html__( 'Pages', 'woocommerce-restaurant-ordering' ) );
		$this->set_title( esc_html__( 'Pages', 'woocommerce-restaurant-ordering' ) );
		$this->set_description( esc_html__( 'First, set up your main restaurant ordering pages. You can also create food order forms manually by adding the [restaurant_ordering] shortcode to any page', 'woocommerce-restaurant-ordering' ) );
	}

	/**
	 * Setup fields.
	 *
	 * @return array
	 */
	public function setup_fields() {
		$fields = Util::pluck_wc_settings(
			Settings::get_general_settings( $this->get_plugin() ),
			[ 'wro_menu_page', 'wro_categories' ]
		);

		if ( ! empty( $fields['wro_menu_page']['value'] ) && get_post_status( $fields['wro_menu_page']['value'] ) ) {
			$fields['wro_menu_page']['value'] = absint( $fields['wro_menu_page']['value'] );
		}

		$fields['wro_categories']['type']        = 'multiselect';
		$fields['wro_categories']['placeholder'] = __( 'Select an option', 'woocommerce-restaurant-ordering' );

		$pages = $fields['wro_categories']['options'];

		if ( isset( $pages[0]['value'] ) && $pages[0]['value'] === '' ) {
			unset( $pages[0] );
		}

		$fields['wro_categories']['options'] = array_values( $pages );

		if ( ! empty( $fields['wro_categories']['value'] ) ) {
			$fields['wro_categories']['value'] = array_map( 'absint', $fields['wro_categories']['value'] );
		} else {
			$fields['wro_categories']['value'] = [];
		}

		return $fields;
	}

	/**
	 * {@inheritdoc}
	 */
	public function submit( array $values ) {
		if ( ! empty( $values['wro_menu_page'] ) ) {
			update_option( 'wro_menu_page', $values['wro_menu_page'] );
			Settings::add_shortcode_to_page( $values['wro_menu_page'] );
		}

		if ( is_array( $values['wro_categories'] ) ) {
			update_option( 'wro_categories', $values['wro_categories'] );
		}

		return Api::send_success_response();
	}

}
