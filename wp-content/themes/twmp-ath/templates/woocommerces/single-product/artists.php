<?php
/**
 * WooCommerce Single Product - Artists Template
 *
 * This template displays artists associated with a WooCommerce product.
 * Data is retrieved from ACF field and displayed with proper sanitization.
 *
 * @package TWMP_ATH
 * @subpackage Templates/WooCommerce
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Get the current product.
$product = wc_get_product();

if ( ! $product ) {
	return;
}

/**
 * Hook before artists section.
 *
 * @since 1.0.0
 */
do_action( 'twmp_before_product_artists' );

// Get artists data from ACF field.
$artists = twmp_get_product_artists( $product->get_id() );

// Display artists section.
if ( ! empty( $artists ) && is_array( $artists ) ) {
	twmp_render_artists_grid( $artists );
} else {
	twmp_render_no_artists_fallback();
}

/**
 * Hook after artists section.
 *
 * @since 1.0.0
 */
do_action( 'twmp_after_product_artists' );
