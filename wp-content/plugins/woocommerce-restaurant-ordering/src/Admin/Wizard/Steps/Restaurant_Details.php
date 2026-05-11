<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Setup_Wizard\Api;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Setup_Wizard\Step;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Setup_Wizard\Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Settings;

class Restaurant_Details extends Step {
	/**
	 * Configure the step.
	 */
	public function init() {
		$this->set_id( 'restaurant-details' );
		$this->set_name( esc_html__( 'Restaurant details', 'woocommerce-restaurant-ordering' ) );
		$this->set_title( esc_html__( 'Restaurant details', 'woocommerce-restaurant-ordering' ) );
		$this->set_description( esc_html__( 'If you like, then you can display restaurant information above the food order form.', 'woocommerce-restaurant-ordering' ) );
	}

	/**
	 * Setup fields.
	 *
	 * @return array
	 */
	public function setup_fields() {
		$fields = Util::pluck_wc_settings(
			Settings::get_restaurant_info_settings(),
			[ 'wro_restaurant_name', 'wro_restaurant_address', 'wro_delivery_notice' ]
		);

		foreach ( [ 'wro_restaurant_name', 'wro_restaurant_address', 'wro_delivery_notice' ] as $key ) {
			$fields[ $key ]['value'] = [ get_option( $key, '' ) ];
		}

		return $fields;
	}

	/**
	 * {@inheritdoc}
	 */
	public function submit( array $values ) {
		$max_length = Settings::get_restaurant_info_max_length();

		$extractable = [ 'wro_restaurant_name', 'wro_restaurant_address', 'wro_delivery_notice' ];
		$extracted_values = [];

		foreach ( $values as $key => $value ) {
			if ( in_array( $key, $extractable, true ) ) {
				$extracted_values[ $key ] = $value;
			}
		}

		if ( is_array( $extracted_values ) && ! empty( $extracted_values ) ) {
			foreach ( $extracted_values as $key => $value ) {
				while( is_array( $value ) ) {
					$value = $value[0];
				}
				if ( is_string( $value ) && strlen( $value ) > $max_length ) {
					$field = $this->get_fields()[ $key ];
					$label = $field['label'];

					return Api::send_error_response(
						[
							'message' => sprintf( __( '"%1$s" should not exceed the max length of %2$s characters.', 'woocommerce-restaurant-ordering' ), $label, $max_length )
						]
					);
				}
				update_option( $key, $value );
			}
		}

		return Api::send_success_response();
	}
}
