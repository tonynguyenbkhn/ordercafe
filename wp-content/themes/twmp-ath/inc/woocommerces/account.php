<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('woocommerce_before_account_navigation', function() {
    echo '<div class="page-block page-block--account">';
});
add_action('woocommerce_before_customer_login_form', function() {
    echo '<div class="page-block page-block--login">';
});
add_action('woocommerce_before_lost_password_form', function() {
    echo '<div class="page-block page-block--login">';
});
$hooks = [
    'woocommerce_account_dashboard',
    'woocommerce_after_account_orders',
    'woocommerce_after_account_downloads',
    'woocommerce_after_edit_account_form',
    'woocommerce_after_customer_login_form'
];

foreach ($hooks as $hook) {
    add_action($hook, 'close_account_page_block', 100);
}

function close_account_page_block() {
    echo '</div><!-- End page-block--account -->';
}

add_action('woocommerce_before_account_downloads', function() {
    echo '<div class="page-block--download">';
});
add_action('woocommerce_after_account_downloads', function() {
    echo '</div><!-- End page-block--download -->';
});