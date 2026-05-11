<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering;

use Barn2\Plugin\WC_Restaurant_Ordering\Info\Availability_Options;
use Barn2\Plugin\WC_Restaurant_Ordering\Menu\Menu_Options;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Admin\Settings_Util as Barn2_Settings_Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Plugin\Licensed_Plugin;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\WooCommerce\Admin\Settings_Util;

/**
 * Handles the plugin settings saved in WooCommerce.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Settings {

	/**
	 * The list of settings that are numeric. These can be single numbers or an  array of numbers.
	 *
	 * @var string[] The list of numeric settings.
	 */
	private const NUMERIC_SETTINGS = [ 'menu_page', 'categories', 'columns', 'image_size' ];

	public static function get_setting( $setting, $default = false ) {
		return self::get_setting_value( (string) $setting, $default );
	}

	public static function get_settings( $settings, array $defaults = [] ) {
		$settings = (array) $settings;
		$result   = [];

		foreach ( $settings as $setting ) {
			$default            = isset( $defaults[ $setting ] ) ? $defaults[ $setting ] : false;
			$result[ $setting ] = self::get_setting_value( $setting, $default );
		}

		return $result;
	}

	public static function get_id( $setting ) {
		$map = self::get_settings_map();
		return isset( $map[ $setting ] ) ? $map[ $setting ] : 'wro_' . $setting;
	}

	private static function get_setting_value( $setting, $default ) {
		$setting_id = self::get_id( $setting );

		if ( ! $setting_id ) {
			$value = $default;
		} else {
			$value = get_option( $setting_id, $default );

			if ( in_array( $value, [ 'yes', 'no' ], true ) ) {
				// Convert to bool.
				$value = Settings_Util::checkbox_setting_to_bool( $value );
			} elseif ( in_array( $setting, self::NUMERIC_SETTINGS, true ) ) {
				// Convert to int.
				if ( is_array( $value ) ) {
					$value = array_map( 'absint', $value );
				} elseif ( is_scalar( $value ) ) {
					$value = (int) $value;
				}
			}
		}

		return $value;
	}

	/**
	 * Returns a map of settings to their corresponding database keys (e.g. for get_option).
	 *
	 * @return array The settings map
	 */
	private static function get_settings_map() {
		return [
			'product_image'         => 'wro_show_product_image',
			'product_description'   => 'wro_show_product_description',
			'category_titles'       => 'wro_show_category_titles',
			'category_descriptions' => 'wro_show_category_descriptions',
			'columns'               => 'wro_menu_columns',
			'modal_image'           => 'wro_show_modal_image',
			'modal_description'     => 'wro_show_modal_description',
			'modal_stock'           => 'wro_show_modal_stock',
			'buy_button'            => 'wro_show_buy_button',
			'cart_confirmation'     => 'wro_show_cart_confirmation',
			'menu_navigation'       => 'wro_show_navigation',
		];
	}

	public static function get_all_settings_map() {
		$settings = [
			'wro_enable_opening_hours',
			'wro_opening_hours',
			'wro_open_notice',
			'wro_closed_notice',
			'wro_menu_page',
			'wro_categories',
			'wro_category_template',
			'wro_menu_navigation',
			'wro_category_titles',
			'wro_category_descriptions',
			'wro_product_image',
			'wro_product_description',
			'wro_columns',
			'wro_product_order',
			'wro_image_position',
			'wro_image_size',
			'wro_description_length',
			'wro_modal_image',
			'wro_modal_description',
			'wro_modal_stock',
			'wro_order_type',
			'wro_buy_button',
			'wro_cart_confirmation',
			'wro_restaurant_name',
			'wro_restaurant_address',
			'wro_delivery_notice',
			'wro_delete_data',
		];

		return $settings;
	}

	/**
	 * Get the availability settings for the settings page.
	 *
	 * @param Licensed_Plugin $plugin The plugin object.
	 * @return array The array of settings.
	 */
	public static function get_availability_settings() {
		$defaults = Availability_Options::get_defaults();

		return [
			[
				'name' => __( 'Opening hours', 'woocommerce-restaurant-ordering' ),
				'type' => 'title',
				'desc' => __( 'Enter the opening hours for your restaurant. If used, the restaurant will be open or closed based on the hours entered below. Customers won\'t be able to order when the restaurant is closed.', 'woocommerce-restaurant-ordering' ),
				'id'   => 'wro_section_availability',
			],
			[
				'id'                => self::get_id( 'enable_opening_hours' ),
				'type'              => 'checkbox',
				'title'             => __( 'Enable', 'woocommerce-restaurant-ordering' ),
				'desc'              => __( 'Enable restaurant opening hours', 'woocommerce-restaurant-ordering' ),
				'default'           => Settings_Util::bool_to_checkbox_setting( $defaults['enable_opening_hours'] ),
				'autoload'          => false,
				'class'             => 'toggle',
				'custom_attributes' => [
					'data-toggle-class' => 'opening-hours',
				],
			],
			[
				'id'       => self::get_id( 'opening_hours' ),
				'type'     => 'opening_hours',
				'title'    => __( 'Opening hours', 'woocommerce-restaurant-ordering' ),
				'class'    => 'opening-hours',
				'autoload' => false,
			],
			[
				'id'                => self::get_id( 'open_notice' ),
				'type'              => 'text',
				'title'             => __( 'Open message', 'woocommerce-restaurant-ordering' ),
				'desc'              => sprintf(
				/* translators: %s: A merge tag that can be used in the message */
					__( 'Displayed when the restaurant is open. Use %s as a placeholder for the next closing time.', 'woocommerce-restaurant-ordering' ),
					'{close_time}'
				),
				'default'           => $defaults['open_notice'],
				'custom_attributes' => [
					'maxlength' => self::get_restaurant_info_max_length(),
				],
				'autoload'          => false,
				'class'             => 'opening-hours',
			],
			[
				'id'                => self::get_id( 'closed_notice' ),
				'type'              => 'text',
				'title'             => __( 'Closed message', 'woocommerce-restaurant-ordering' ),
				'desc'              => sprintf(
				/* translators: %s: A merge tag that can be used in the message */
					__( 'Displayed when the restaurant is closed. Use %s as a placeholder for the next opening time.', 'woocommerce-restaurant-ordering' ),
					'{open_time}'
				),
				'default'           => $defaults['closed_notice'],
				'custom_attributes' => [
					'maxlength' => self::get_restaurant_info_max_length(),
				],
				'autoload'          => false,
				'class'             => 'opening-hours',
			],
			[
				'type' => 'sectionend',
				'id'   => 'wro_section_availability',
			],
		];
	}

	/**
	 * Get the general settings for the settings page.
	 *
	 * @param Licensed_Plugin $plugin The plugin object.
	 * @return array The array of settings.
	 */
	public static function get_general_settings( $plugin ) {
		return [
			[
				'name' => __( 'Restaurant Ordering', 'woocommerce-restaurant-ordering' ),
				'type' => 'title',
				'id'   => 'wro_section_general',
				'desc' => self::get_section_header_description( $plugin ),
			],
			$plugin->get_license_setting()->get_license_key_setting(),
			$plugin->get_license_setting()->get_license_override_setting(),
			[
				'id'       => self::get_id( 'menu_page' ),
				'type'     => 'single_select_page',
				'title'    => __( 'Restaurant order page', 'woocommerce-restaurant-ordering' ),
				'desc'     => sprintf(
				/* translators: %1$s: HTML for the shop page link, %2$s: HTML for the link close tag. */
					__( 'The restaurant ordering page. If you want your store homepage to use the restaurant order layout, select your main %1$sShop page%2$s here.', 'woocommerce-restaurant-ordering' ),
					Util::format_link_open( 'https://woo.com/document/woocommerce-pages/', true ),
					'</a>'
				),
				'autoload' => false,
			],
			[
				'id'                => self::get_id( 'categories' ),
				'type'              => 'multiselect',
				'title'             => __( 'Categories', 'woocommerce-restaurant-ordering' ),
				'options'           => self::get_product_categories_list(),
				'class'             => 'restaurant-categories',
				'custom_attributes' => [
					'aria-label'       => __( 'Categories', 'woocommerce-restaurant-ordering' ),
					'data-placeholder' => __( 'Choose your menu categories&hellip;', 'woocommerce-restaurant-ordering' ),
				],
				'desc'              => __( 'The categories to include in your restaurant order form.', 'woocommerce-restaurant-ordering' ),
				'autoload'          => false,
			],
			[
				'id'       => self::get_id( 'category_template' ),
				'type'     => 'checkbox',
				'title'    => __( 'Category pages', 'woocommerce-restaurant-ordering' ),
				'desc'     => __( 'Show the order form on WooCommerce category pages', 'woocommerce-restaurant-ordering' ),
				'desc_tip' => __( 'The order form will show the current category, plus any child categories.', 'woocommerce-restaurant-ordering' ),
				'default'  => 'no',
				'autoload' => false,
			],
			[
				'type' => 'sectionend',
				'id'   => 'wro_section_general',
			],
		];
	}

	/**
	 * Get the order form settings for the settings page.
	 *
	 * @param Licensed_Plugin $plugin The plugin object.
	 * @return array The array of settings.
	 */
	public static function get_order_form_settings() {
		$defaults = Menu_Options::get_defaults();

		return [
			[
				'name' => __( 'Order form options', 'woocommerce-restaurant-ordering' ),
				'type' => 'title',
				'id'   => 'wro_section_order_form',
			],
			[
				'id'            => self::get_id( 'menu_navigation' ),
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'title'         => __( 'Order form', 'woocommerce-restaurant-ordering' ),
				'desc'          => __( 'Show menu navigation bar', 'woocommerce-restaurant-ordering' ),
				'default'       => Settings_Util::bool_to_checkbox_setting( $defaults['menu_navigation'] ),
				'autoload'      => false,
			],
			[
				'id'            => self::get_id( 'category_titles' ),
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'desc'          => __( 'Show category names', 'woocommerce-restaurant-ordering' ),
				'default'       => Settings_Util::bool_to_checkbox_setting( $defaults['category_titles'] ),
				'autoload'      => false,
			],
			[
				'id'            => self::get_id( 'category_descriptions' ),
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'desc'          => __( 'Show category descriptions', 'woocommerce-restaurant-ordering' ),
				'default'       => Settings_Util::bool_to_checkbox_setting( $defaults['category_descriptions'] ),
				'autoload'      => false,
			],
			[
				'id'                => self::get_id( 'product_image' ),
				'type'              => 'checkbox',
				'checkboxgroup'     => '',
				'desc'              => __( 'Show product images', 'woocommerce-restaurant-ordering' ),
				'default'           => Settings_Util::bool_to_checkbox_setting( $defaults['product_image'] ),
				'autoload'          => false,
				'class'             => 'toggle',
				'custom_attributes' => [
					'data-toggle-class' => 'image-setting',
				],
			],
			[
				'id'                => self::get_id( 'product_description' ),
				'type'              => 'checkbox_tooltip',
				'checkboxgroup'     => 'end',
				'desc'              => __( 'Show product descriptions', 'woocommerce-restaurant-ordering' ),
				'desc_tip'          => __( 'Shows the short description if entered, otherwise shows the full product description.', 'woocommerce-restaurant-ordering' ),
				'default'           => Settings_Util::bool_to_checkbox_setting( $defaults['product_description'] ),
				'autoload'          => false,
				'class'             => 'toggle',
				'custom_attributes' => [
					'data-toggle-class' => 'description-length',
				],
			],
			[
				'id'      => self::get_id( 'columns' ),
				'type'    => 'select',
				'title'   => __( 'Layout', 'woocommerce-restaurant-ordering' ),
				'options' => [
					1 => __( '1 column', 'woocommerce-restaurant-ordering' ),
					2 => __( '2 columns', 'woocommerce-restaurant-ordering' ),
					3 => __( '3 columns', 'woocommerce-restaurant-ordering' ),
				],
				'desc'    => __( 'How to arrange the items in your restaurant order form.', 'woocommerce-restaurant-ordering' ),
				'css'     => 'max-width:220px',
				'default' => $defaults['columns'],
			],
			[
				'id'       => self::get_id( 'product_order' ),
				'type'     => 'select',
				'title'    => __( 'Sort products by', 'woocommerce-restaurant-ordering' ),
				'options'  => [
					'menu_order' => __( 'WooCommerce default', 'woocommerce-restaurant-ordering' ),
					'title'      => __( 'Name', 'woocommerce-restaurant-ordering' ),
					'price'      => __( 'Price', 'woocommerce-restaurant-ordering' ),
					'popularity' => __( 'Popularity', 'woocommerce-restaurant-ordering' ),
					'date'       => __( 'Date', 'woocommerce-restaurant-ordering' ),
				],
				'css'      => 'max-width:220px',
				'autoload' => false,
			],
			[
				'id'       => self::get_id( 'image_position' ),
				'type'     => 'radio',
				'title'    => __( 'Image position', 'woocommerce-restaurant-ordering' ),
				'options'  => [
					'left'  => __( 'Left', 'woocommerce-restaurant-ordering' ),
					'right' => __( 'Right', 'woocommerce-restaurant-ordering' ),
				],
				'class'    => 'image-setting',
				'default'  => $defaults['image_position'],
				'autoload' => false,
			],
			[
				'id'                => self::get_id( 'image_size' ),
				'type'              => 'number',
				'title'             => __( 'Image size', 'woocommerce-restaurant-ordering' ),
				'default'           => $defaults['image_size'],
				'class'             => 'image-setting',
				'css'               => 'max-width:80px;',
				'custom_attributes' => [
					'min' => 70,
					'max' => 400,
				],
				'autoload'          => false,
			],
			[
				'id'       => self::get_id( 'description_length' ),
				'type'     => 'radio',
				'title'    => __( 'Product description', 'woocommerce-restaurant-ordering' ),
				'options'  => [
					Menu_Options::DD_LIMITED => __( 'Limit the description length', 'woocommerce-restaurant-ordering' ),
					Menu_Options::DD_FULL    => __( 'Show full description', 'woocommerce-restaurant-ordering' ),
				],
				'desc_tip' => __( "The product description is limited to fit the available space. If you select 'Show full description', the product box will expand as required.", 'woocommerce-restaurant-ordering' ),
				'class'    => 'description-length',
				'default'  => $defaults['description_length'],
				'autoload' => false,
			],
			[
				'id'            => self::get_id( 'modal_image' ),
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'title'         => __( 'Product lightbox', 'woocommerce-restaurant-ordering' ),
				'desc'          => __( 'Show product image', 'woocommerce-restaurant-ordering' ),
				'default'       => 'yes',
				'autoload'      => false,
			],
			[
				'id'            => self::get_id( 'modal_description' ),
				'type'          => 'checkbox_tooltip',
				'checkboxgroup' => '',
				'desc'          => __( 'Show product description', 'woocommerce-restaurant-ordering' ),
				'desc_tip'      => __( 'The full product description is displayed in the lightbox.', 'woocommerce-restaurant-ordering' ),
				'default'       => 'yes',
				'autoload'      => false,
			],
			[
				'id'            => self::get_id( 'modal_stock' ),
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
				'desc'          => __( 'Show stock status', 'woocommerce-restaurant-ordering' ),
				'default'       => 'yes',
				'autoload'      => false,
			],
			[
				'id'       => self::get_id( 'order_type' ),
				'type'     => 'radio',
				'title'    => __( 'Order method', 'woocommerce-restaurant-ordering' ),
				'options'  => [
					Menu_Options::OT_QUICK    => __( 'Add items instantly where possible, otherwise show a lightbox', 'woocommerce-restaurant-ordering' ),
					Menu_Options::OT_LIGHTBOX => __( 'Always show a lightbox to select the quantity and options', 'woocommerce-restaurant-ordering' ),
				],
				'default'  => $defaults['order_type'],
				'autoload' => false,
			],
			[
				'id'       => self::get_id( 'buy_button' ),
				'title'    => __( 'Buy button', 'woocommerce-restaurant-ordering' ),
				'type'     => 'radio',
				'options'  => [
					'yes' => __( 'Order by clicking a buy button (+)', 'woocommerce-restaurant-ordering' ),
					'no'  => __( 'Order by clicking anywhere in the product box', 'woocommerce-restaurant-ordering' ),
				],
				'default'  => Settings_Util::bool_to_checkbox_setting( $defaults['buy_button'] ),
				'autoload' => false,
			],
			[
				'id'       => self::get_id( 'cart_confirmation' ),
				'title'    => __( 'Cart confirmation', 'woocommerce-restaurant-ordering' ),
				'type'     => 'checkbox_tooltip',
				'desc'     => __( 'Show a confirmation message when adding items to the cart', 'woocommerce-restaurant-ordering' ),
				'desc_tip' => __( 'We recommend you only disable this option if you provide a different way to confirm adding products to the cart. For example, using the popup cart in our WooCommerce Fast Cart plugin.', 'woocommerce-restaurant-ordering' ),
				'default'  => 'yes',
				'autoload' => false,
			],
			[
				'type' => 'sectionend',
				'id'   => 'wro_section_order_form',
			],
		];
	}

	/**
	 * Get the restaurant info for the settings page.
	 *
	 * @param Licensed_Plugin $plugin The plugin object.
	 * @return array The array of settings.
	 */
	public static function get_restaurant_info_settings() {
		return [
			[
				'name' => __( 'Restaurant details', 'woocommerce-restaurant-ordering' ),
				'type' => 'title',
				'desc' => __( 'Enter some information about your restaurant.', 'woocommerce-restaurant-ordering' ),
				'id'   => 'wro_section_restaurant_info',
			],
			[
				'id'                => self::get_id( 'restaurant_name' ),
				'type'              => 'text',
				'title'             => __( 'Restaurant name', 'woocommerce-restaurant-ordering' ),
				'desc_tip'          => __( 'The restaurant name is shown in the \'More\' popup which shows the restaurant opening hours.', 'woocommerce-restaurant-ordering' ),
				'custom_attributes' => [
					'maxlength' => 80,
				],
				'autoload'          => false,
			],
			[
				'id'                => self::get_id( 'restaurant_address' ),
				'type'              => 'text',
				'title'             => __( 'Restaurant address', 'woocommerce-restaurant-ordering' ),
				'custom_attributes' => [
					'maxlength' => self::get_restaurant_info_max_length(),
				],
				'autoload'          => false,
			],
			[
				'id'                => self::get_id( 'delivery_notice' ),
				'type'              => 'text',
				'title'             => __( 'Delivery/collection info', 'woocommerce-restaurant-ordering' ),
				'custom_attributes' => [
					'maxlength' => self::get_restaurant_info_max_length(),
				],
				'autoload'          => false,
			],
			[
				'type' => 'sectionend',
				'id'   => 'wro_section_restaurant_info',
			],
		];
	}

	/**
	 * Get the main section description, including our support links.
	 *
	 * @return string The description
	 */
	public static function get_section_header_description( $plugin ) {
		$wizard = wro()->get_service( 'wizard' );
		return sprintf(
			'<p>%s</p><p>%s</p>',
			/* translators: %s: "WooCommerce Restaurant Ordering" */
			sprintf( __( 'The following options control the %s extension.', 'woocommerce-restaurant-ordering' ), $plugin->get_name() ),
			Barn2_Settings_Util::get_help_links( $plugin )
		);
	}

	/**
	 * Get the list of categories for the order form category setting.
	 *
	 * @return array An array of product categories (id => name).
	 */
	public static function get_product_categories_list() {
		$all_categories = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'fields'     => 'id=>name',
			]
		);

		if ( is_wp_error( $all_categories ) ) {
			return [];
		}

		$current_category_ids = self::get_setting( 'categories', [] );
		$current_categories   = [];

		if ( empty( $current_category_ids ) ) {
			$current_category_ids = [];
		}

		foreach ( $current_category_ids as $category_id ) {
			// Check category still exists in the current categories list (it may have been deleted since options were last saved).
			if ( array_key_exists( $category_id, $all_categories ) ) {
				$current_categories[ $category_id ] = $all_categories[ $category_id ];
			}
		}

		// Append non-selected categories to the end of the list.
		return $current_categories + array_diff_key( $all_categories, $current_categories );
	}

	public static function get_restaurant_info_max_length() {
		return apply_filters( 'wc_restaurant_ordering_info_setting_max_length', 110 );
	}

	/**
	 * Add the plugin's shortcode to a page given a page id.
	 *
	 * @param string|int $order_page
	 * @return void
	 */
	public static function add_shortcode_to_page( $order_page ) {
		// Don't add the shortcode to WooCommerce main Shop page as this is handled separately.
		if ( $order_page === wc_get_page_id( 'shop' ) ) {
			return;
		}

		// Add the restaurant shortcode to the selected order page.
		$order_page_post = get_post( $order_page );

		if ( $order_page_post instanceof \WP_Post ) {
			$content = trim( $order_page_post->post_content );

			// Check shortcode not already added to page.
			if ( false === strpos( $content, '[' . Shortcodes::MENU_SHORTCODE ) ) {
				$shortcode_to_insert = '[' . Shortcodes::MENU_SHORTCODE . ']';

				if ( $content ) {
					$shortcode_to_insert = "\n\n" . $shortcode_to_insert;
				}

				$content .= $shortcode_to_insert;

				wp_update_post(
					[
						'ID'           => $order_page_post->ID,
						'post_content' => $content,
					]
				);
			}
		}
	}

	/**
	 * Adds the settings for the uninstall section
	 */
	public static function get_uninstall_settings( $plugin ) {
		return [
			[
				'name' => __( 'Uninstalling ' . $plugin->get_name(), 'woocommerce-restaurant-ordering' ),
				'type' => 'title',
				'id'   => 'delete_data',
			],
			[
				'id'            => 'wro_delete_data',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'title'         => __( 'Delete data on uninstall	', 'woocommerce-restaurant-ordering' ),
				'desc'          => __( 'Permanently delete all ' . $plugin->get_name() . ' settings and data when uninstalling the plugin', 'woocommerce-restaurant-ordering' ),
				'default'       => 'no',
				'autoload'      => false,
			],
			[
				'type' => 'sectionend',
				'id'   => 'delete_data',
			],
		];
	}
}
