<?php

if (!defined('ABSPATH')) {
    exit;
}

// 1. Tắt WooCommerce assets ở page không cần
add_action('wp_enqueue_scripts', function () {
    if (
        is_shop() ||
        is_product() ||
        is_product_category() ||
        is_product_tag() ||
        is_cart() ||
        is_checkout() ||
        is_order_received_page()
    ) {
        return;
    }

    wp_dequeue_style('woocommerce-general');
    wp_dequeue_style('woocommerce-layout');
    wp_dequeue_style('woocommerce-smallscreen');
    wp_dequeue_style('wc-blocks-style');

    wp_dequeue_script('wc-cart-fragments');
    wp_dequeue_script('woocommerce');
    wp_dequeue_script('wc-add-to-cart');
    wp_dequeue_script('jquery-blockui');
    wp_dequeue_script('js-cookie');
}, 99);

// 2. Tắt cart-fragments nếu không dùng mini cart. Nếu site không có mini cart động ở header:
add_action('wp_enqueue_scripts', function () {
    if (!is_cart() && !is_checkout()) {
        wp_dequeue_script('wc-cart-fragments');
    }
}, 99);

// 3. Tắt WooCommerce Blocks nếu không dùng block cart/checkout. Nếu bạn dùng template cart/checkout classic, có thể remove block CSS:
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('wc-blocks-style');
    wp_dequeue_style('wc-blocks-vendors-style');
}, 99);

// 4. Tắt WooCommerce Analytics / Admin nếu không dùng
add_filter('woocommerce_admin_disabled', '__return_true');

// 5. Tắt REST API / AJAX dư ở frontend
add_filter('woocommerce_loop_add_to_cart_link', function ($html, $product) {
    return sprintf(
        '<a href="%s" class="button">%s</a>',
        esc_url($product->get_permalink()),
        esc_html__('View product', 'woocommerce')
    );
}, 10, 2);

// 7. Tắt review nếu không dùng
add_filter('woocommerce_product_tabs', function ($tabs) {
    unset($tabs['reviews']);
    return $tabs;
}, 98);

// 8. Tối ưu single product
remove_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15);

// 9. Tắt marketing features ở admin
add_filter( 'woocommerce_admin_features', function( $features ) {
    return array_filter( $features, function( $feature ) {
        return $feature !== 'marketing';
    });
});