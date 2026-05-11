<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Setup_Wizard\Steps\Ready;
use Barn2\Plugin\WC_Restaurant_Ordering\Settings;

/**
 * The last step.
 */
class Completed extends Ready {
	/**
	 * Setup the step.
	 */
	public function init() {
		parent::init();
		$this->set_name( esc_html__( 'Ready', 'woocommerce-restaurant-ordering' ) );
		$this->set_title( esc_html__( 'Complete Setup', 'woocommerce-restaurant-ordering' ) );
		$this->set_description(
			sprintf(
				__( 'Congratulations, your restaurant ordering system is now set up! The food order forms will appear on the %s page.', 'woocommerce-restaurant-ordering' ),
				get_the_title( Settings::get_setting( 'menu_page' ) )
			)
		);
	}
}
