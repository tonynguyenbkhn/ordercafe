<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Admin;

use Barn2\Plugin\WC_Restaurant_Ordering\Info\Opening_Hours;
use Barn2\Plugin\WC_Restaurant_Ordering\Template_Loader_Factory;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Plugin\Plugin;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Template_Loader;
use WC_Admin_Settings;

/**
 * Handles the opening hours settings field.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Opening_Hours_Setting implements Registerable {

	const SETTING_ID = 'opening_hours';

	/**
	 * @var Plugin A plugin object - used for retrieving the assets folder URL and plugin version.
	 */
	private $plugin;

	/**
	 * @var boolean Record whether we have printed to JS template, to prevent double output.
	 */
	private $is_template_printed = false;

	/**
	 * @var Template_Loader $template_loader Loads templates for the settings fields.
	 */
	private $template_loader;

	public function __construct( Plugin $plugin ) {
		$this->plugin          = $plugin;
		$this->template_loader = Template_Loader_Factory::create();
	}

	public function register() {
		add_action( 'woocommerce_admin_field_' . self::SETTING_ID, [ $this, 'output_field' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
		add_filter( 'woocommerce_admin_settings_sanitize_option', [ $this, 'sanitize' ], 10, 3 );
	}

	public function output_field( $field ) {
		$field_description = WC_Admin_Settings::get_field_description( $field );
		$current_value     = WC_Admin_Settings::get_option( $field['id'], $field['default'] );

		$this->template_loader->load_template(
			'admin/opening-hours',
			[
				'field'           => $field,
				'description'     => $field_description,
				'current_value'   => $current_value,
				'template_loader' => $this->template_loader
			]
		);
	}

	public function load_scripts() {
		$assets_url     = $this->plugin->get_dir_url() . 'assets';
		wp_enqueue_style( 'jquery-timepicker', $assets_url . '/css/admin/jquery.timepicker.css', [], $this->plugin->get_version() );
		wp_enqueue_script( 'jquery-timepicker', $assets_url . '/js/admin/jquery.timepicker.js', [ 'jquery' ], $this->plugin->get_version(), true );
		add_action( 'admin_print_footer_scripts', [ $this, 'load_js_template' ] );
	}

	public function load_js_template() {
		if ( $this->is_template_printed ) {
			return;
		}

		// Output the Underscores template used by settings.js to add/remove opening periods.
		$this->template_loader->load_template(
			'admin/opening-hours/opening-period-template.php',
			[
				'opening_period' => $this->template_loader->get_template(
					'admin/opening-hours/opening-period.php',
					[
						'id'         => '{{ data.id }}',
						'day'        => '{{ data.day }}',
						'period'     => '{{ data.period }}',
						'from_value' => '',
						'to_value'   => ''
					]
				)
			]
		);

		$this->is_template_printed = true;
	}

	public function sanitize( $value, $option, $raw_value ) {
		if ( empty( $option['type'] ) || self::SETTING_ID !== $option['type'] ) {
			return $value;
		}

		// Creating a new Opening_Hours object will parse and sanitize the supplied array.
		$opening_hours = new Opening_Hours( $value );
		return $opening_hours->get_opening_times();
	}

}
