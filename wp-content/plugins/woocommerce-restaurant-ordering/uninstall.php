<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = [
    'wro_enable_opening_hours',
    'wro_opening_hours',
    'wro_open_notice',
    'wro_closed_notice',
    'wro_menu_page',
    'wro_categories',
    'wro_category_template',
    'wro_show_navigation',
    'wro_show_category_titles',
    'wro_show_category_descriptions',
    'wro_show_product_image',
    'wro_show_product_description',
    'wro_menu_columns',
    'wro_product_order',
    'wro_image_position',
    'wro_image_size',
    'wro_description_length',
    'wro_show_modal_image',
    'wro_show_modal_description',
    'wro_show_modal_stock',
    'wro_order_type',
    'wro_show_buy_button',
    'wro_cart_confirmation',
    'wro_restaurant_name',
    'wro_restaurant_address',
    'wro_delivery_notice',
    'wro_delete_data',
    'wro_restaurant_page_created'
];

if ( get_option( 'wro_delete_data' ) === null || get_option( 'wro_delete_data' ) !== 'yes' ) {
	return;
}

// Delete the restaurant page
wp_delete_post( intval( get_option( 'wro_menu_page', '0' ) ), true );

$options_to_delete = array_merge(
	$settings,
	[
		'barn2_plugin_license_236796',
		'barn2_plugin_promo_236796',
		'barn2_plugin_review_banner_236796',
	]
);

foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}
