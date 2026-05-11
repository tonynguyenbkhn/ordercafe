<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Admin\Wizard;

use Barn2\Plugin\WC_Restaurant_Ordering\Admin\Wizard\Steps\Completed;
use Barn2\Plugin\WC_Restaurant_Ordering\Admin\Wizard\Steps\License_Verification;
use Barn2\Plugin\WC_Restaurant_Ordering\Admin\Wizard\Steps\Order_Form;
use Barn2\Plugin\WC_Restaurant_Ordering\Admin\Wizard\Steps\Pages;
use Barn2\Plugin\WC_Restaurant_Ordering\Admin\Wizard\Steps\Restaurant_Details;
use Barn2\Plugin\WC_Restaurant_Ordering\Admin\Wizard\Steps\Upsell;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Setup_Wizard\Setup_Wizard as WRO_Setup_Wizard;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Plugin\License\EDD_Licensing;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Plugin\License\Plugin_License;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Service\Standard_Service;

class Setup_Wizard implements Registerable, Standard_Service {
	/**
	 * Plugin instance
	 *
	 * @var Licensed_Plugin
	 */
	private $plugin;

	/**
	 * Wizard instance
	 *
	 * @var WRO_Setup_Wizard
	 */
	public $wizard;

	/**
	 * Setup the setup wizard.
	 *
	 * @param Licensed_Plugin $plugin
	 */
	public function __construct( Licensed_Plugin $plugin ) {
		$this->plugin = $plugin;

		$steps = [
			new License_Verification(),
			new Pages(),
			new Order_Form(),
			new Restaurant_Details(),
			new Upsell(),
			new Completed(),
		];

		$wizard = new WRO_Setup_Wizard( $this->plugin, $steps );

		$wizard->configure(
			[
				'skip_url'           => admin_url( 'admin.php?page=wc-settings&tab=restaurant-ordering' ),
				'utm_id'             => 'wro',
				'opening_hours_link' => admin_url( 'admin.php?page=wc-settings&tab=restaurant-ordering#wro_section_availability-description' ),
			]
		);

		$wizard->add_edd_api( EDD_Licensing::class );
		$wizard->add_license_class( Plugin_License::class );
		$wizard->add_restart_link( 'restaurant-ordering', 'wro_section_general' );

		$deps = Util::get_script_dependencies( $this->plugin, 'setup-wizard.js' );

		$wizard->add_custom_asset(
			$plugin->get_dir_url() . 'assets/js/admin/setup-wizard.js',
			$deps
		);

		$this->wizard = $wizard;
	}

	/**
	 * {@inheritdoc}
	 */
	public function register() {
		$this->wizard->boot();

		add_action( 'admin_enqueue_scripts', [ $this, 'styling' ], 20 );
	}

	/**
	 * Add inline styling required to hide the settings page button
	 * on the last page of the wizard.
	 *
	 * @return void
	 */
	public function styling() {
		$custom_css = '.completed-btn {display:none;}';

		wp_add_inline_style( 'woocommerce-restaurant-ordering-setup-wizard', $custom_css );
	}

}
