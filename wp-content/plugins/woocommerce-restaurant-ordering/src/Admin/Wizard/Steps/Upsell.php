<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Setup_Wizard\Steps\Cross_Selling;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Setup_Wizard\Util;

class Upsell extends Cross_Selling {
	public function init() {
		parent::init();
		$this->set_name( esc_html__( 'More', 'woocommerce-restaurant-ordering' ) );
		$this->set_description(
			sprintf(
				__( 'Enhance your store with these fantastic plugins from Barn2, or get them all by upgrading to an <a href="%1$s" target="_blank">All Access Pass<a/>! <a href="%2$s" target="_blank">(learn how here)</a>', 'woocommerce-restaurant-ordering' ),
				Util::generate_utm_url( 'https://barn2.com/wordpress-plugins/bundles/', 'wro' ),
				Util::generate_utm_url( 'https://barn2.com/kb/how-to-upgrade-license/', 'wro' )
			)
		);
		$this->set_title( esc_html__( 'Extra features', 'woocommerce-restaurant-ordering' ) );
	}
}
