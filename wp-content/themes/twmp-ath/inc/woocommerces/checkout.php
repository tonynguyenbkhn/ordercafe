<?php


if (!defined('ABSPATH')) {
  exit;
}

remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10);
remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
remove_action('woocommerce_before_checkout_form_cart_notices', 'woocommerce_output_all_notices', 10);

add_filter('woocommerce_add_to_cart_redirect', function ($redirect_url) {
  if (!empty($_REQUEST['twmp_buy_now'])) {
    return wc_get_checkout_url();
  }

  return $redirect_url;
});

// Xử lý tính năng "Buy Now" - bỏ qua giỏ hàng và chuyển thẳng đến trang thanh toán
// wp-content\themes\twmp-ath\inc\woocommerces\checkout.php
// Keep only firstname, lastname, phone, date of birth, and age on checkout.
add_filter('woocommerce_checkout_fields', function ($fields) {
  $visible_billing_fields = array(
    'billing_first_name',
    'billing_phone',
  );

  foreach ($fields as $group_key => $group_fields) {
    foreach ($group_fields as $field_key => $field) {
      if (isset($fields[$group_key][$field_key])) {
        $fields[$group_key][$field_key]['required'] = false;
      }
    }
  }

  foreach ($fields as $group_key => $group_fields) {
    foreach ($group_fields as $field_key => $field) {
      if (in_array($field_key, $visible_billing_fields, true)) {
        continue;
      }

      if (isset($fields[$group_key][$field_key])) {
        $fields[$group_key][$field_key]['type'] = 'hidden';
        $fields[$group_key][$field_key]['required'] = false;
        $fields[$group_key][$field_key]['label'] = '';
        $fields[$group_key][$field_key]['placeholder'] = '';
        $fields[$group_key][$field_key]['class'] = array('twmp-checkout-field--hidden');
      }
    }
  }

  if (isset($fields['billing']['billing_first_name'])) {
    $fields['billing']['billing_first_name']['type'] = 'text';
    $fields['billing']['billing_first_name']['label'] = esc_html__('Full name', 'twmp-ath');
    $fields['billing']['billing_first_name']['placeholder'] = esc_html__('Full name', 'twmp-ath');
    $fields['billing']['billing_first_name']['required'] = false;
    $fields['billing']['billing_first_name']['class'] = array('form-row-first', 'twmp-checkout-field');
    $fields['billing']['billing_first_name']['priority'] = 10;
  }

  if (isset($fields['billing']['billing_phone'])) {
    $fields['billing']['billing_phone']['type'] = 'tel';
    $fields['billing']['billing_phone']['label'] = esc_html__('Phone', 'twmp-ath');
    $fields['billing']['billing_phone']['placeholder'] = esc_html__('Phone', 'twmp-ath');
    $fields['billing']['billing_phone']['required'] = false;
    $fields['billing']['billing_phone']['class'] = array('form-row-wide', 'twmp-checkout-field');
    $fields['billing']['billing_phone']['priority'] = 20;
  }

  foreach (array('shipping', 'account', 'order') as $group_key) {
    if (isset($fields[$group_key])) {
      $fields[$group_key] = array();
    }
  }

  return $fields;
}, 20);

function wcs_checkout_page_open()
{

  echo '<div class="page-block page-block--checkout">';
}

add_action('woocommerce_after_checkout_form', 'wcs_checkout_page_close', 100);

function wcs_checkout_page_close()
{
  echo '</div>';
}

function wcs_checkout_page_block_open()
{
  echo '<div class="grid page-block__grid">';
  echo '<div class="grid__col page-block__col page-block__col--main">';
}

function wcs_checkout_page_block_between()
{
  echo '</div>';
  echo '<div class="grid__col page-block__col page-block__col--sidebar">';
}

function wcs_checkout_page_block_close()
{
  echo '</div>';
  echo '</div>';
}

 add_action('template_redirect', function () {
    if (!function_exists('is_order_received_page') || !is_order_received_page()) {
      return;
    }

    if (!is_user_logged_in()) {
      wp_safe_redirect(home_url('/'));
      exit;
    }

    $user  = wp_get_current_user();
    $roles = $user instanceof WP_User ? (array) $user->roles : array();

    if (array_intersect(array('administrator', 'shop_manager'), $roles)) {
      $order_id     = absint(get_query_var('order-received'));
      $redirect_url = home_url('/staff-orders/');

      if ($order_id) {
        $redirect_url = add_query_arg(
          array(
            'twmp_order_id' => $order_id,
            'order_status'  => 'all',
          ),
          $redirect_url
        );
      }

      wp_safe_redirect($redirect_url);
      exit;
    }
  });
