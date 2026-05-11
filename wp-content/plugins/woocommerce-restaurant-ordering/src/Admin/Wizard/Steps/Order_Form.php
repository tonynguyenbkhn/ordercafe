<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Setup_Wizard\Api;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Setup_Wizard\Step;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Setup_Wizard\Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Settings;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\WooCommerce\Admin\Settings_Util;

class Order_Form extends Step {

	private $checkboxes = [
		'wro_show_navigation',
		'wro_show_category_titles',
		'wro_show_category_descriptions',
		'wro_show_product_image',
		'wro_show_product_description',
	];

	/**
	 * Configure the step.
	 */
	public function init() {
		$this->set_id( 'order-form' );
		$this->set_name( esc_html__( 'Order form', 'woocommerce-restaurant-ordering' ) );
		$this->set_title( esc_html__( 'Order form content', 'woocommerce-restaurant-ordering' ) );
		$this->set_description( esc_html__( 'Now, choose the information that will appear in your food order forms.', 'woocommerce-restaurant-ordering' ) );
	}

	/**
	 * Setup fields.
	 *
	 * @return array
	 */
	public function setup_fields() {

		$fields['my_heading'] = [
			'type'  => 'heading',
			'label' => __( 'Order form', 'woocommerce-restaurant-ordering' ),
			'size'  => 'h2',
			'style' => [
				'marginBottom' => '0',
				'marginTop'    => '5px',
			]
		];

		$plucked = Util::pluck_wc_settings(
			Settings::get_order_form_settings(),
			[
				'wro_show_navigation',
				'wro_show_category_titles',
				'wro_show_category_descriptions',
				'wro_show_product_image',
				'wro_show_product_description',
				'wro_menu_columns',
			]
		);

		$fields = array_merge( $fields, $plucked );

		$fields['wro_show_navigation']['border']      = false;
		$fields['wro_show_navigation']['description'] = false;
		$fields['wro_show_navigation']['type']        = 'checkbox';
		$fields['wro_show_navigation']['label']       = '';
		$fields['wro_show_navigation']['title']       = __( 'Show menu navigation bar', 'woocommerce-restaurant-ordering' );
		$fields['wro_show_navigation']['value']       = get_option( 'wro_show_navigation', 'yes' ) === 'yes';

		$fields['wro_show_category_titles']['border']      = false;
		$fields['wro_show_category_titles']['type']        = 'checkbox';
		$fields['wro_show_category_titles']['description'] = false;
		$fields['wro_show_category_titles']['label']       = '';
		$fields['wro_show_category_titles']['title']       = __( 'Show category names', 'woocommerce-restaurant-ordering' );
		$fields['wro_show_category_titles']['value'] = get_option( 'wro_show_category_titles', 'yes' ) === 'yes';

		$fields['wro_show_category_descriptions']['border']      = false;
		$fields['wro_show_category_descriptions']['description'] = false;
		$fields['wro_show_category_descriptions']['label']       = '';
		$fields['wro_show_category_descriptions']['title']       = __( 'Show category descriptions', 'woocommerce-restaurant-ordering' );
		$fields['wro_show_category_descriptions']['value'] = get_option( 'wro_show_category_descriptions', 'yes' ) === 'yes';

		$fields['wro_show_product_image']['border']      = false;
		$fields['wro_show_product_image']['description'] = false;
		$fields['wro_show_product_image']['label']       = '';
		$fields['wro_show_product_image']['title']       = __( 'Show product images', 'woocommerce-restaurant-ordering' );
		$fields['wro_show_product_image']['value'] = get_option( 'wro_show_product_image', 'yes' ) === 'yes';

		$fields['wro_show_product_description']['type']        = 'checkbox';
		$fields['wro_show_product_description']['border']      = false;
		$fields['wro_show_product_description']['title']       = $fields['wro_show_product_description']['description'];
		$fields['wro_show_product_description']['description'] = false;
		$fields['wro_show_product_description']['label']       = '';

		$fields['wro_show_product_description']['value'] = get_option( 'wro_show_product_description', 'yes' ) === 'yes';

		$fields['wro_menu_columns']['value'] = absint( get_option( 'wro_menu_columns', 2 ) );

		return $fields;
	}

	/**
	 * {@inheritdoc}
	 */
	public function submit( array $values ) {
		foreach ( $values as $option_key => $option_value ) {
			if ( in_array( $option_key, $this->checkboxes, true ) ) {
				if ( $option_value === 'false' || empty( $option_value ) ) {
					$option_value = 'no';
				} elseif ( $option_value === 'true' || $option_value === '1' ) {
					$option_value = 'yes';
				}
			}

			update_option( $option_key, $option_value );
		}

		return Api::send_success_response();
	}
}
