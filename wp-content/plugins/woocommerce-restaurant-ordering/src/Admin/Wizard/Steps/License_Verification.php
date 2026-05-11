<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Setup_Wizard\Steps\Welcome;

/**
 * License verification step.
 */
class License_Verification extends Welcome {
	/**
	 * Setup step.
	 */
	public function init() {
		parent::init();
		$this->set_title( 'Welcome to WooCommerce Restaurant Ordering' );
		$this->set_name( esc_html__( 'Welcome', 'woocommerce-restaurant-ordering' ) );
		$this->set_description( esc_html__( 'Start taking food orders in no time', 'woocommerce-restaurant-ordering' ) );
		$this->set_tooltip( esc_html__( 'Use this setup wizard to quickly configure the most popular options for your restaurant ordering system. You can change these options later on the plugin settings page or by relaunching the setup wizard. You can also override these options in the shortcode for individual food order forms.', 'woocommerce-restaurant-ordering' ) );
	}
}
