<?php

if (!defined('ABSPATH')) {
  exit;
}

remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10);
remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
remove_action('woocommerce_before_checkout_form_cart_notices', 'woocommerce_output_all_notices', 10);

// Xử lý tính năng "Buy Now" - bỏ qua giỏ hàng và chuyển thẳng đến trang thanh toán
add_filter('woocommerce_add_to_cart_validation', function ($passed, $product_id, $quantity) {
  if (!empty($_REQUEST['twmp_buy_now'])) {
    if (function_exists('WC') && WC()->cart) {
      WC()->cart->empty_cart();
    }
  }
  return $passed;
}, 1, 3);

add_filter('woocommerce_add_to_cart_redirect', function ($redirect_url) {
  if (!empty($_REQUEST['twmp_buy_now'])) {
    return wc_get_checkout_url();
  }

  return $redirect_url;
});

// Reset checkout flow nếu có item mới được thêm vào cart
add_action('woocommerce_before_checkout_form', function () {
  if (!function_exists('WC') || !WC()->session) {
    return;
  }

  // Nếu cart có item mới → reset flow
  if (!empty(WC()->cart) && !WC()->cart->is_empty()) {
    WC()->session->__unset('twmp_checkout_payment_order_id');
    WC()->session->__unset('twmp_checkout_payment_order_key');
  }
}, 1);

// Custom html cho trang thanh toán
add_action('woocommerce_before_checkout_form', 'twmp_checkout_page_open', 5);

function twmp_checkout_page_open()
{
  $state = function_exists('twmp_checkout_get_payment_order_context') ? twmp_checkout_get_payment_order_context() : array();
  $step = !empty($state['step']) ? absint($state['step']) : 1;
  $settings = array(
    'step'               => $step,
    'orderId'            => !empty($state['order_id']) ? absint($state['order_id']) : 0,
    'orderKey'           => !empty($state['order_key']) ? sanitize_text_field($state['order_key']) : '',
    'ajaxUrl'            => admin_url('admin-ajax.php'),
    'pollAction'         => 'twmp_checkout_poll_payment_status',
    'uploadAction'       => 'twmp_checkout_upload_payment_proof',
    'adminReviewAction'  => 'twmp_checkout_admin_review_order',
    'nonceActionPrefix'  => 'twmp_checkout_payment_',
  );

  echo '<div class="page-block page-block--checkout woocommerce-checkout-custom--settings" data-settings="' . esc_attr(wp_json_encode($settings)) . '">';
  echo '<div class="twmp-checkout-steps" aria-hidden="true">';
  echo '<div class="twmp-checkout-steps__item ' . esc_attr(1 === $step ? 'is-active' : '') . '"><span class="twmp-checkout-steps__index">1</span><span class="twmp-checkout-steps__label">' . esc_html__('Booking information', 'twmp-ath') . '</span></div>';
  echo '<div class="twmp-checkout-steps__line"></div>';
  echo '<div class="twmp-checkout-steps__item ' . esc_attr(2 === $step ? 'is-active' : '') . '"><span class="twmp-checkout-steps__index">2</span><span class="twmp-checkout-steps__label">' . esc_html__('Payment', 'twmp-ath') . '</span></div>';
  echo '</div>';
}

add_action('woocommerce_after_checkout_form', 'twmp_checkout_page_close', 100);

function twmp_checkout_page_close()
{
  echo '</div>';
}

// Lấy context đơn hàng cho trang thanh toán, bao gồm thông tin đơn hàng, trạng thái xác thực bill, và cấu hình thanh toán
function twmp_checkout_get_payment_order_context()
{
  static $context = null;

  if (null !== $context) {
    return $context;
  }

  $context = array(
    'step'         => 1,
    'order_id'     => 0,
    'order_key'    => '',
    'order'        => null,
    'proof_status' => 'waiting_upload',
    'status_label'  => esc_html__('Awaiting bill upload', 'twmp-ath'),
    'status_text'   => esc_html__('Complete transfer and upload the bill to continue.', 'twmp-ath'),
    'can_upload'    => true,
    'nonce'         => wp_create_nonce('twmp_checkout_payment_guest'),
    'config'        => twmp_checkout_get_payment_config(),
  );

  $request_order_id = 0;
  $request_order_key = '';

  if (isset($_GET['order_id'])) {
    $request_order_id = absint(wp_unslash($_GET['order_id']));
  }

  if (isset($_GET['order_key'])) {
    $request_order_key = sanitize_text_field(wp_unslash($_GET['order_key']));
  } elseif (isset($_GET['key'])) {
    $request_order_key = sanitize_text_field(wp_unslash($_GET['key']));
  }

  if ((!$request_order_id || !$request_order_key) && function_exists('WC') && WC()->session) {
    if (!$request_order_id) {
      $request_order_id = absint(WC()->session->get('twmp_checkout_payment_order_id', 0));
    }

    if (!$request_order_key) {
      $request_order_key = (string) WC()->session->get('twmp_checkout_payment_order_key', '');
    }
  }

  if ($request_order_id > 0 && $request_order_key !== '') {
    $order = function_exists('wc_get_order') ? wc_get_order($request_order_id) : null;
    if ($order instanceof WC_Order && hash_equals($order->get_order_key(), $request_order_key)) {
      if (!headers_sent()) {
        nocache_headers();
      }

      $context['step'] = 2;
      $context['order_id'] = $request_order_id;
      $context['order_key'] = $request_order_key;
      $context['order'] = $order;

      $proof_status = twmp_checkout_get_payment_proof_status($order);
      $context['proof_status'] = $proof_status;
      $context['status_label'] = twmp_checkout_get_payment_status_label($proof_status);
      $context['status_text'] = twmp_checkout_get_payment_status_text($proof_status);
      $context['can_upload'] = in_array($proof_status, array('waiting_upload', 'rejected'), true);
      $context['nonce'] = wp_create_nonce('twmp_checkout_payment_' . $request_order_id);
    }
  }

  return $context;
}

// Lấy cấu hình thanh toán từ options, hỗ trợ nhiều key khác nhau để linh hoạt trong việc đặt tên option
function twmp_checkout_get_option_value(array $keys, $default = '')
{
  foreach ($keys as $key) {
    if (function_exists('get_field')) {
      $value = get_field($key, 'option');
      if (is_array($value) && !empty($value['url'])) {
        return $value;
      }
      if (is_string($value) && trim($value) !== '') {
        return trim($value);
      }
      if (is_numeric($value) && absint($value) > 0) {
        return absint($value);
      }
    }

    $value = get_option($key, null);
    if ($value === null || $value === '' || $value === false) {
      continue;
    }

    return is_string($value) ? trim($value) : $value;
  }

  return $default;
}

// Hỗ trợ lấy URL media từ nhiều dạng input khác nhau (URL trực tiếp, ID attachment, hoặc array chứa URL/ID)
function twmp_checkout_resolve_media_url($value)
{
  if (is_array($value)) {
    if (!empty($value['url'])) {
      return esc_url_raw($value['url']);
    }

    if (!empty($value['ID'])) {
      $value = absint($value['ID']);
    } else {
      return '';
    }
  }

  if (is_numeric($value)) {
    $url = wp_get_attachment_url(absint($value));
    return $url ? esc_url_raw($url) : '';
  }

  if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
    return esc_url_raw($value);
  }

  return '';
}

// Lấy cấu hình thanh toán từ options, hỗ trợ nhiều key khác nhau để linh hoạt trong việc đặt tên option
function twmp_checkout_get_payment_config()
{
  $qr_value = twmp_checkout_get_option_value(array(
    'checkout_payment_qr',
    'payment_qr',
    'twmp_checkout_payment_qr',
    'twmp_payment_qr',
  ));

  return array(
    'qr_url'          => twmp_checkout_resolve_media_url($qr_value),
    'company_name'    => (string) twmp_checkout_get_option_value(array('checkout_company_name', 'company_name', 'twmp_checkout_company_name'), get_bloginfo('name')),
    'company_address' => (string) twmp_checkout_get_option_value(array('checkout_company_address', 'company_address', 'twmp_checkout_company_address')),
    'company_phone'   => (string) twmp_checkout_get_option_value(array('checkout_company_phone', 'company_phone', 'twmp_checkout_company_phone')),
    'company_email'   => (string) twmp_checkout_get_option_value(array('checkout_company_email', 'company_email', 'twmp_checkout_company_email')),
    'bank_name'       => (string) twmp_checkout_get_option_value(array('checkout_bank_name', 'bank_name', 'twmp_checkout_bank_name')),
    'account_name'    => (string) twmp_checkout_get_option_value(array('checkout_bank_account_name', 'bank_account_name', 'twmp_checkout_bank_account_name')),
    'account_number'  => (string) twmp_checkout_get_option_value(array('checkout_bank_account_number', 'bank_account_number', 'twmp_checkout_bank_account_number')),
    'branch'          => (string) twmp_checkout_get_option_value(array('checkout_bank_branch', 'bank_branch', 'twmp_checkout_bank_branch')),
    'transfer_note'   => (string) twmp_checkout_get_option_value(array('checkout_transfer_note', 'transfer_note', 'twmp_checkout_transfer_note')),
    'bill_title'      => (string) twmp_checkout_get_option_value(array('checkout_bill_title', 'payment_bill_title', 'twmp_checkout_bill_title'), esc_html__('Upload bill', 'twmp-ath')),
  );
}

// Lấy trạng thái xác thực bill của đơn hàng, dựa trên order status và meta '_twmp_checkout_payment_proof_status'
function twmp_checkout_get_payment_proof_status($order)
{
  if (!$order instanceof WC_Order) {
    return 'waiting_upload';
  }

  $order_status = $order->get_status();
  if (in_array($order_status, array('processing', 'completed'), true)) {
    return 'approved';
  }

  if ('failed' === $order_status) {
    return 'rejected';
  }

  $status = sanitize_key((string) $order->get_meta('_twmp_checkout_payment_proof_status', true));
  if (in_array($status, array('waiting_upload', 'pending_review', 'approved', 'rejected'), true)) {
    return $status;
  }

  return 'waiting_upload';
}

// Lấy label hiển thị tương ứng với trạng thái xác thực bill
function twmp_checkout_get_payment_status_label($status)
{
  $labels = array(
    'waiting_upload' => esc_html__('Waiting for bill upload', 'twmp-ath'),
    'pending_review' => esc_html__('Waiting for confirmation', 'twmp-ath'),
    'approved'       => esc_html__('Payment confirmed', 'twmp-ath'),
    'rejected'       => esc_html__('Bill rejected', 'twmp-ath'),
  );

  return !empty($labels[$status]) ? $labels[$status] : esc_html__('Waiting for bill upload', 'twmp-ath');
}

// Lấy text mô tả tương ứng với trạng thái xác thực bill, để hướng dẫn khách hàng biết bước tiếp theo cần làm gì
function twmp_checkout_get_payment_status_text($status)
{
  $texts = array(
    'waiting_upload' => esc_html__('Transfer to the account below and upload the bill.', 'twmp-ath'),
    'pending_review' => esc_html__('We have received your bill. Please wait for admin review.', 'twmp-ath'),
    'approved'       => esc_html__('Your payment was approved.', 'twmp-ath'),
    'rejected'       => esc_html__('Your bill was rejected. Please upload a clearer file.', 'twmp-ath'),
  );

  return !empty($texts[$status]) ? $texts[$status] : esc_html__('Transfer to the account below and upload the bill.', 'twmp-ath');
}

// Lấy label cho nút hành động tương ứng với trạng thái xác thực bill, ví dụ: nếu đã approved thì nút sẽ là "Success", nếu rejected thì là "Failed", còn lại sẽ là "Waiting"
function twmp_checkout_get_payment_action_label($status)
{
  if ('approved' === $status) {
    return esc_html__('Success', 'twmp-ath');
  }

  if ('rejected' === $status) {
    return esc_html__('Failed', 'twmp-ath');
  }

  return esc_html__('Waiting', 'twmp-ath');
}


// Các hàm hỗ trợ cho tính năng vé sự kiện, bao gồm lấy dữ liệu sản phẩm vé, lưu trữ lựa chọn vé vào session, và render phần chi tiết vé trên trang thanh toán
function twmp_checkout_get_ticket_product_data($product_id = 0)
{
  $product_id = absint($product_id);
  if (!$product_id && function_exists('get_the_ID')) {
    $product_id = absint(get_the_ID());
  }

  $data = array(
    'product_id'   => $product_id,
    'performances'  => array(),
    'ticket_prices' => array(),
  );

  if (!$product_id || !function_exists('get_field')) {
    return $data;
  }

  $performance_rows = (array) get_field('ath_performance_schedule', $product_id);
  foreach ($performance_rows as $row) {
    $datetime_raw = isset($row['performance_datetime']) ? trim((string) $row['performance_datetime']) : '';
    if ($datetime_raw === '') {
      continue;
    }

    $timestamp = strtotime($datetime_raw);
    if (!$timestamp) {
      continue;
    }

    $key = 'performance-' . md5($datetime_raw);
    $day = wp_date('l', $timestamp);
    $date = wp_date('d M Y', $timestamp);
    $time = wp_date('H:i', $timestamp);

    $data['performances'][$key] = array(
      'key'          => $key,
      'datetime'     => $datetime_raw,
      'timestamp'    => $timestamp,
      'day'          => $day,
      'date'         => $date,
      'time'         => $time,
      'display'      => sprintf('%s | %s %s', $day, $date, $time),
      'display_short' => sprintf('%s %s', $date, $time),
    );
  }

  $price_rows = (array) get_field('ath_ticket_price_options', $product_id);
  foreach ($price_rows as $row) {
    $label = isset($row['label']) ? trim((string) $row['label']) : '';
    $price_raw = isset($row['price']) ? $row['price'] : '';
    $price = (float) wc_format_decimal($price_raw);

    if ($label === '' || $price <= 0) {
      continue;
    }

    $key = 'price-' . md5($label . '|' . $price);
    $data['ticket_prices'][$key] = array(
      'key'   => $key,
      'label' => $label,
      'price' => $price,
    );
  }

  return $data;
}

function twmp_checkout_get_ticket_product_id()
{
  if (!function_exists('WC') || !WC()->cart) {
    return 0;
  }

  foreach (WC()->cart->get_cart() as $cart_item) {
    $product_id = !empty($cart_item['product_id']) ? absint($cart_item['product_id']) : 0;
    if (!$product_id) {
      continue;
    }

    $ticket_data = twmp_checkout_get_ticket_product_data($product_id);
    if (!empty($ticket_data['performances']) || !empty($ticket_data['ticket_prices'])) {
      return $product_id;
    }
  }

  return 0;
}

function twmp_checkout_get_ticket_selection_defaults($product_id = 0)
{
  $ticket_data = twmp_checkout_get_ticket_product_data($product_id);
  $performance_key = '';
  $price_key = '';

  if (!empty($ticket_data['performances'])) {
    $performance_key = array_key_first($ticket_data['performances']);
  }

  if (!empty($ticket_data['ticket_prices'])) {
    $price_key = array_key_first($ticket_data['ticket_prices']);
  }

  return array(
    'product_id'      => absint($ticket_data['product_id']),
    'performance_key' => $performance_key,
    'price_key'       => $price_key,
  );
}

function twmp_checkout_resolve_ticket_selection($product_id, $selection)
{
  $ticket_data = twmp_checkout_get_ticket_product_data($product_id);
  $defaults = twmp_checkout_get_ticket_selection_defaults($product_id);

  $performance_key = isset($selection['performance_key']) ? sanitize_key($selection['performance_key']) : '';
  $price_key = isset($selection['price_key']) ? sanitize_key($selection['price_key']) : '';

  if (empty($ticket_data['performances']) || empty($ticket_data['performances'][$performance_key])) {
    $performance_key = $defaults['performance_key'];
  }

  if (empty($ticket_data['ticket_prices']) || empty($ticket_data['ticket_prices'][$price_key])) {
    $price_key = $defaults['price_key'];
  }

  return array(
    'product_id'      => absint($product_id),
    'performance_key' => $performance_key,
    'price_key'       => $price_key,
  );
}

function twmp_checkout_get_ticket_selection_state($product_id = 0)
{
  $defaults = twmp_checkout_get_ticket_selection_defaults($product_id);
  $state = $defaults;

  if (function_exists('WC') && WC()->session) {
    $stored = (array) WC()->session->get('twmp_ticket_selection', array());
    $state = array_merge($state, array_filter($stored, 'strlen'));

    if (empty($state['product_id'])) {
      $state['product_id'] = $defaults['product_id'];
    }

    if (empty($state['performance_key']) && !empty($defaults['performance_key'])) {
      $state['performance_key'] = $defaults['performance_key'];
    }

    if (empty($state['price_key']) && !empty($defaults['price_key'])) {
      $state['price_key'] = $defaults['price_key'];
    }

    WC()->session->set('twmp_ticket_selection', $state);
  }

  return $state;
}

function twmp_checkout_render_ticket_detail_section()
{
  $product_id = twmp_checkout_get_ticket_product_id();
  if (!$product_id) {
    return;
  }

  $ticket_data = twmp_checkout_get_ticket_product_data($product_id);
  if (empty($ticket_data['performances']) && empty($ticket_data['ticket_prices'])) {
    return;
  }

  $state = twmp_checkout_get_ticket_selection_state($product_id);
  $quantity = 1;
  $cart_item_key = twmp_checkout_get_cart_item_key_by_product_id($product_id);

  if ($cart_item_key && function_exists('WC') && WC()->cart) {
    foreach (WC()->cart->get_cart() as $current_cart_item_key => $cart_item) {
      if ($current_cart_item_key !== $cart_item_key) {
        continue;
      }

      $quantity = !empty($cart_item['quantity']) ? max(1, absint($cart_item['quantity'])) : 1;
      break;
    }
  }
?>
  <section class="twmp-checkout-card twmp-checkout-card--ticket-detail">
    <input type="hidden" name="twmp_ticket_product_id" value="<?php echo esc_attr($product_id); ?>">

    <header class="twmp-checkout-card__header">
      <span class="twmp-checkout-card__step">2</span>
      <h3 class="twmp-checkout-card__title"><?php echo esc_html__('Ticket detail', 'twmp-ath'); ?></h3>
    </header>

    <div class="twmp-checkout-card__content">
      <?php if (!empty($ticket_data['performances'])) : ?>
        <div class="twmp-checkout-ticket-detail__group twmp-checkout-ticket-detail__group--performance">
          <p class="twmp-checkout-ticket-detail__label"><?php echo esc_html__('Select Performance date *', 'twmp-ath'); ?></p>
          <div class="twmp-checkout-ticket-detail__options twmp-checkout-ticket-detail__options--performance">
            <?php foreach ($ticket_data['performances'] as $option) : ?>
              <label class="twmp-ticket-option <?php echo esc_attr($state['performance_key'] === $option['key'] ? 'is-selected' : ''); ?>">
                <input
                  type="radio"
                  name="twmp_ticket_performance"
                  value="<?php echo esc_attr($option['key']); ?>"
                  <?php checked($state['performance_key'], $option['key']); ?>
                  required>
                <span class="twmp-ticket-option__main">
                  <span class="twmp-ticket-option__day"><?php echo esc_html($option['day']); ?></span>
                  <span class="twmp-ticket-option__date"><?php echo esc_html($option['date']); ?></span>
                </span>
                <span class="twmp-ticket-option__time"><?php echo esc_html($option['time']); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($ticket_data['ticket_prices'])) : ?>
        <div class="twmp-checkout-ticket-detail__group twmp-checkout-ticket-detail__group--price">
          <p class="twmp-checkout-ticket-detail__label"><?php echo esc_html__('Select type of ticket', 'twmp-ath'); ?></p>
          <div class="twmp-checkout-ticket-detail__options twmp-checkout-ticket-detail__options--price">
            <?php foreach ($ticket_data['ticket_prices'] as $option) : ?>
              <label class="twmp-ticket-price-option <?php echo esc_attr($state['price_key'] === $option['key'] ? 'is-selected' : ''); ?>">
                <input
                  type="radio"
                  name="twmp_ticket_price_option"
                  value="<?php echo esc_attr($option['key']); ?>"
                  <?php checked($state['price_key'], $option['key']); ?>
                  required>
                <span class="twmp-ticket-price-option__label"><?php echo esc_html($option['label']); ?></span>
                <span class="twmp-ticket-price-option__price"><?php echo wp_kses_post(wc_price($option['price'])); ?></span>
                <span class="twmp-ticket-price-option__unit"><?php echo esc_html__('/ Ticket', 'twmp-ath'); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="twmp-checkout-ticket-detail__group twmp-checkout-ticket-detail__group--quantity">
        <p class="twmp-checkout-ticket-detail__label"><?php echo esc_html__('Quantity', 'twmp-ath'); ?> *</p>
        <div class="twmp-ticket-quantity" data-ticket-quantity-control>
          <button type="button" class="twmp-ticket-quantity__button" data-ticket-quantity-step="minus" aria-label="<?php echo esc_attr__('Decrease quantity', 'twmp-ath'); ?>">-</button>
          <input
            type="number"
            name="twmp_ticket_quantity"
            class="twmp-ticket-quantity__input"
            value="<?php echo esc_attr($quantity); ?>"
            min="1"
            step="1"
            inputmode="numeric"
            required>
          <button type="button" class="twmp-ticket-quantity__button" data-ticket-quantity-step="plus" aria-label="<?php echo esc_attr__('Increase quantity', 'twmp-ath'); ?>">+</button>
        </div>
      </div>
    </div>
  </section>
<?php
}

add_action('woocommerce_checkout_after_customer_details', 'twmp_checkout_render_ticket_detail_section', 20);

function twmp_checkout_get_cart_item_key_by_product_id($product_id = 0)
{
  if (!function_exists('WC') || !WC()->cart) {
    return '';
  }

  $product_id = absint($product_id);
  if (!$product_id) {
    $product_id = twmp_checkout_get_ticket_product_id();
  }

  if (!$product_id) {
    return '';
  }

  foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
    if (!empty($cart_item['product_id']) && absint($cart_item['product_id']) === $product_id) {
      return $cart_item_key;
    }
  }

  return '';
}
/**
 * Đoạn code này là trung tâm xử lý realtime của checkout (AJAX) — mỗi khi user thay đổi option (date, ticket type, quantity), nó sẽ:
 *👉 cập nhật session + đồng bộ lại giỏ hàng
 */
add_action('woocommerce_checkout_update_order_review', function ($posted_data) {
  if (!function_exists('WC') || !WC()->session) {
    return;
  }

  parse_str($posted_data, $data);

  $product_id = !empty($data['twmp_ticket_product_id']) ? absint($data['twmp_ticket_product_id']) : twmp_checkout_get_ticket_product_id();
  if (!$product_id) {
    return;
  }

  $selection = twmp_checkout_resolve_ticket_selection($product_id, array(
    'performance_key' => isset($data['twmp_ticket_performance']) ? sanitize_key($data['twmp_ticket_performance']) : '',
    'price_key'       => isset($data['twmp_ticket_price_option']) ? sanitize_key($data['twmp_ticket_price_option']) : '',
  ));

  WC()->session->set('twmp_ticket_selection', $selection);

  $quantity = !empty($data['twmp_ticket_quantity']) ? max(1, absint($data['twmp_ticket_quantity'])) : 0;
  if ($quantity > 0 && function_exists('WC') && WC()->cart) {
    $cart_item_key = twmp_checkout_get_cart_item_key_by_product_id($product_id);
    if ($cart_item_key) {
      $current_quantity = 0;
      foreach (WC()->cart->get_cart() as $current_cart_item_key => $cart_item) {
        if ($current_cart_item_key === $cart_item_key) {
          $current_quantity = !empty($cart_item['quantity']) ? absint($cart_item['quantity']) : 0;
          break;
        }
      }

      if ($current_quantity !== $quantity) {
        WC()->cart->set_quantity($cart_item_key, $quantity, true);
      }
    }
  }
});

// Validation khi submit checkout: đảm bảo user đã chọn đủ performance + ticket type nếu sản phẩm đó yêu cầu
add_action('woocommerce_checkout_process', function () {
  $product_id = !empty($_POST['twmp_ticket_product_id']) ? absint($_POST['twmp_ticket_product_id']) : twmp_checkout_get_ticket_product_id();
  if (!$product_id) {
    return;
  }

  $ticket_data = twmp_checkout_get_ticket_product_data($product_id);
  if (empty($ticket_data['performances']) && empty($ticket_data['ticket_prices'])) {
    return;
  }

  $performance_key = isset($_POST['twmp_ticket_performance']) ? sanitize_key(wp_unslash($_POST['twmp_ticket_performance'])) : '';
  $price_key = isset($_POST['twmp_ticket_price_option']) ? sanitize_key(wp_unslash($_POST['twmp_ticket_price_option'])) : '';

  if (!empty($ticket_data['performances']) && empty($performance_key)) {
    wc_add_notice(__('Please select a performance date.', 'twmp-ath'), 'error');
  } elseif (!empty($ticket_data['performances']) && empty($ticket_data['performances'][$performance_key])) {
    wc_add_notice(__('The selected performance date is invalid.', 'twmp-ath'), 'error');
  }

  if (!empty($ticket_data['ticket_prices']) && empty($price_key)) {
    wc_add_notice(__('Please select a ticket type.', 'twmp-ath'), 'error');
  } elseif (!empty($ticket_data['ticket_prices']) && empty($ticket_data['ticket_prices'][$price_key])) {
    wc_add_notice(__('The selected ticket type is invalid.', 'twmp-ath'), 'error');
  }
});

// Khi tạo order, lưu lại lựa chọn vé vào order meta để hiển thị trong admin và email
add_action('woocommerce_checkout_create_order', function ($order, $data) {
  $product_id = !empty($_POST['twmp_ticket_product_id']) ? absint($_POST['twmp_ticket_product_id']) : twmp_checkout_get_ticket_product_id();
  if (!$product_id) {
    return;
  }

  $ticket_data = twmp_checkout_get_ticket_product_data($product_id);
  if (empty($ticket_data['performances']) && empty($ticket_data['ticket_prices'])) {
    return;
  }

  $selection = twmp_checkout_resolve_ticket_selection($product_id, array(
    'performance_key' => isset($_POST['twmp_ticket_performance']) ? sanitize_key(wp_unslash($_POST['twmp_ticket_performance'])) : '',
    'price_key'       => isset($_POST['twmp_ticket_price_option']) ? sanitize_key(wp_unslash($_POST['twmp_ticket_price_option'])) : '',
  ));

  $order->update_meta_data('_twmp_ticket_product_id', $product_id);

  if (!empty($selection['performance_key']) && !empty($ticket_data['performances'][$selection['performance_key']])) {
    $performance = $ticket_data['performances'][$selection['performance_key']];
    $order->update_meta_data('_twmp_ticket_performance_key', $performance['key']);
    $order->update_meta_data('_twmp_ticket_performance_label', $performance['display']);
    $order->update_meta_data('_twmp_ticket_performance_datetime', $performance['datetime']);
  }

  if (!empty($selection['price_key']) && !empty($ticket_data['ticket_prices'][$selection['price_key']])) {
    $price_option = $ticket_data['ticket_prices'][$selection['price_key']];
    $order->update_meta_data('_twmp_ticket_price_key', $price_option['key']);
    $order->update_meta_data('_twmp_ticket_price_label', $price_option['label']);
    $order->update_meta_data('_twmp_ticket_price_amount', $price_option['price']);
  }
}, 20, 2);


// Khi tính tổng cart, nếu có sản phẩm vé thì cập nhật lại giá dựa trên lựa chọn của user (performance + ticket type), đảm bảo giá hiển thị và tính toán đúng trong suốt quá trình checkout
add_action('woocommerce_before_calculate_totals', function ($cart) {
  if (is_admin() && !defined('DOING_AJAX')) {
    return;
  }

  if (!function_exists('WC') || !WC()->session || !WC()->cart) {
    return;
  }

  $selection = (array) WC()->session->get('twmp_ticket_selection', array());
  $product_id = !empty($selection['product_id']) ? absint($selection['product_id']) : twmp_checkout_get_ticket_product_id();
  if (!$product_id) {
    return;
  }

  $ticket_data = twmp_checkout_get_ticket_product_data($product_id);
  if (empty($ticket_data['ticket_prices'])) {
    return;
  }

  $price_key = !empty($selection['price_key']) ? sanitize_key($selection['price_key']) : '';
  if (empty($price_key) || empty($ticket_data['ticket_prices'][$price_key])) {
    $defaults = twmp_checkout_get_ticket_selection_defaults($product_id);
    $price_key = $defaults['price_key'];
    if (empty($price_key) || empty($ticket_data['ticket_prices'][$price_key])) {
      return;
    }

    $selection['product_id'] = $product_id;
    $selection['price_key'] = $price_key;
    WC()->session->set('twmp_ticket_selection', $selection);
  }

  $price = (float) $ticket_data['ticket_prices'][$price_key]['price'];
  foreach ($cart->get_cart() as $cart_item) {
    $cart_item_product_id = !empty($cart_item['product_id']) ? absint($cart_item['product_id']) : 0;
    if ($cart_item_product_id !== $product_id || empty($cart_item['data'])) {
      continue;
    }

    $cart_item['data']->set_price($price);
  }
}, 20);


// Hiển thị chi tiết vé đã chọn (performance + ticket type) trong trang admin order detail, giúp admin dễ dàng kiểm tra thông tin vé mà khách hàng đã đặt mà không cần phải mở từng meta để xem
add_action('woocommerce_admin_order_data_after_billing_address', function ($order) {
  $ticket_product_id = $order->get_meta('_twmp_ticket_product_id');
  $ticket_performance = $order->get_meta('_twmp_ticket_performance_label');
  $ticket_price_label = $order->get_meta('_twmp_ticket_price_label');
  $ticket_price_amount = $order->get_meta('_twmp_ticket_price_amount');

  if (!$ticket_product_id && !$ticket_performance && !$ticket_price_label) {
    return;
  }

  echo '<div class="address">';
  echo '<h3>' . esc_html__('Ticket detail', 'twmp-ath') . '</h3>';

  if ($ticket_performance) {
    echo '<p><strong>' . esc_html__('Performance:', 'twmp-ath') . '</strong> ' . esc_html($ticket_performance) . '</p>';
  }

  if ($ticket_price_label) {
    echo '<p><strong>' . esc_html__('Ticket type:', 'twmp-ath') . '</strong> ' . esc_html($ticket_price_label);
    if ($ticket_price_amount !== '') {
      echo ' - ' . wp_kses_post(wc_price((float) $ticket_price_amount));
    }
    echo '</p>';
  }

  echo '</div>';
}, 20);

// Khi tạo order, nếu đơn hàng cần thanh toán thì mặc định set status là "on-hold" để chờ khách upload bill, đồng thời lưu lại trạng thái xác thực bill vào order meta để tiện cho việc hiển thị và quản lý sau này
add_action('woocommerce_checkout_order_processed', function ($order_id, $posted_data, $order) {
  $order = $order instanceof WC_Order ? $order : (function_exists('wc_get_order') ? wc_get_order($order_id) : null);
  if (!$order instanceof WC_Order) {
    return;
  }

  $order->update_meta_data('_twmp_checkout_payment_step', 'payment');
  $order->update_meta_data('_twmp_checkout_payment_proof_status', 'waiting_upload');
  $order->update_meta_data('_twmp_checkout_payment_proof_attachment_id', 0);
  $order->update_meta_data('_twmp_checkout_payment_reviewed_at', '');
  $order->update_meta_data('_twmp_checkout_payment_reviewed_by', 0);
  $order->update_meta_data('_twmp_checkout_payment_review_note', '');
  $order->save();

  if (function_exists('WC') && WC()->session) {
    WC()->session->set('twmp_checkout_payment_order_id', $order->get_id());
    WC()->session->set('twmp_checkout_payment_order_key', $order->get_order_key());
  }

  if ($order->needs_payment() && !$order->has_status('on-hold')) {
    $order->update_status('on-hold', esc_html__('Awaiting payment proof upload.', 'twmp-ath'));
  }
}, 20, 3);

add_action('woocommerce_payment_complete', function ($order_id) {
  $order = wc_get_order($order_id);
  if (!$order || $order->get_payment_method() !== 'bacs') {
    return;
  }

  $order->update_meta_data('_twmp_checkout_payment_proof_status', 'waiting_upload');
  $order->save();

  if (!$order->has_status('on-hold')) {
    $order->update_status('on-hold', 'BACS selected — hold for manual proof/upload.');
  }
}, 10);

// Sau khi tạo order và chuyển đến trang "order received", thay vì hiển thị trang mặc định, chúng ta sẽ redirect về lại trang checkout với query param đặc biệt để hiển thị phần upload bill, đồng thời lưu order_id + order_key vào session để đảm bảo user có thể truy cập trực tiếp vào URL đó mà không cần phải qua bước "order received" nếu họ muốn upload bill sau khi đặt hàng
add_filter('woocommerce_get_checkout_order_received_url', function ($order_received_url, $order) {
  if (!$order instanceof WC_Order) {
    return $order_received_url;
  }

  if (function_exists('WC') && WC()->session) {
    WC()->session->set('twmp_checkout_payment_order_id', $order->get_id());
    WC()->session->set('twmp_checkout_payment_order_key', $order->get_order_key());
  }

  return add_query_arg(array(
    'twmp_checkout_step' => 2,
    'order_id'           => $order->get_id(),
    'order_key'          => $order->get_order_key(),
    'key'                => $order->get_order_key(),
  ), wc_get_checkout_url());
}, 20, 2);

// Mỗi khi order status thay đổi, tự động cập nhật lại trạng thái xác thực bill tương ứng vào order meta, giúp đồng bộ hóa trạng thái giữa order status và payment proof status, đồng thời đảm bảo hiển thị đúng thông tin cho khách hàng và admin khi xem đơn hàng
add_action('woocommerce_order_status_changed', function ($order_id, $from, $to, $order) {
  $order = $order instanceof WC_Order ? $order : (function_exists('wc_get_order') ? wc_get_order($order_id) : null);
  if (!$order instanceof WC_Order) {
    return;
  }

  if (in_array($to, array('processing', 'completed'), true)) {
    $order->update_meta_data('_twmp_checkout_payment_proof_status', 'approved');
    $order->update_meta_data('_twmp_checkout_payment_reviewed_at', current_time('mysql'));
    $order->save();
    return;
  }

  if ('failed' === $to) {
    $order->update_meta_data('_twmp_checkout_payment_proof_status', 'rejected');
    $order->update_meta_data('_twmp_checkout_payment_reviewed_at', current_time('mysql'));
    $order->save();
    return;
  }

  if ('on-hold' === $to || 'pending' === $to) {
    $current = twmp_checkout_get_payment_proof_status($order);
    if (in_array($current, array('waiting_upload', 'pending_review'), true)) {
      $order->update_meta_data('_twmp_checkout_payment_proof_status', $current);
      $order->save();
    }
  }
}, 20, 4);


// Hàm kiểm tra xem hiện tại có đang ở bước thanh toán (step 2) hay không, dựa trên order context được lưu trong session, giúp điều kiện hiển thị phần upload bill chỉ xuất hiện khi user đã đặt hàng và đang ở bước thanh toán
function twmp_checkout_is_payment_step_2()
{
  $state = twmp_checkout_get_payment_order_context();
  return 2 === absint($state['step']);
}

// Step 2: Payment - Upload bill
// Render phần thông tin thanh toán và upload bill trong trang checkout, hiển thị dựa trên trạng thái xác thực bill của đơn hàng, đồng thời cung cấp đầy đủ thông tin chuyển khoản cho khách hàng để họ dễ dàng thực hiện thanh toán và upload bill sau khi đặt hàng
function twmp_checkout_render_payment_step_section()
{
  $state = twmp_checkout_get_payment_order_context();
  if (empty($state['order']) || !$state['order'] instanceof WC_Order) {
    return;
  }

  $order = $state['order'];
  $config = !empty($state['config']) ? $state['config'] : twmp_checkout_get_payment_config();
  $order_total = $order->get_formatted_order_total();
?>
  <div class="twmp-checkout-stack twmp-checkout-stack--payment">
    <section class="twmp-checkout-card twmp-checkout-card--payment">
      <header class="twmp-checkout-card__header">
        <span class="twmp-checkout-card__step">2</span>
        <h3 class="twmp-checkout-card__title"><?php echo esc_html__('Payment', 'twmp-ath'); ?></h3>
      </header>

      <div class="twmp-checkout-card__content">
        <div class="twmp-checkout-payment-stage" data-payment-stage data-order-id="<?php echo esc_attr($state['order_id']); ?>" data-order-key="<?php echo esc_attr($state['order_key']); ?>" data-payment-status="<?php echo esc_attr($state['proof_status']); ?>" data-payment-nonce="<?php echo esc_attr($state['nonce']); ?>">
          <div class="twmp-checkout-payment-stage__header">
            <span class="twmp-checkout-payment-stage__badge" data-payment-status-badge><?php echo esc_html(twmp_checkout_get_payment_action_label($state['proof_status'])); ?></span>
            <p class="twmp-checkout-payment-stage__title" data-payment-status-title><?php echo esc_html($state['status_label']); ?></p>
            <p class="twmp-checkout-payment-stage__description" data-payment-status-text><?php echo esc_html($state['status_text']); ?></p>
          </div>

          <div class="twmp-checkout-payment-stage__grid">
            <div class="twmp-checkout-payment-stage__qr">
              <?php if (!empty($config['qr_url'])) : ?>
                <img src="<?php echo esc_url($config['qr_url']); ?>" alt="<?php echo esc_attr__('Payment QR code', 'twmp-ath'); ?>">
              <?php else : ?>
                <div class="twmp-checkout-payment-stage__qr-placeholder">
                  <span><?php echo esc_html__('QR code', 'twmp-ath'); ?></span>
                </div>
              <?php endif; ?>
            </div>

            <div class="twmp-checkout-payment-stage__info">
              <h4 class="twmp-checkout-payment-stage__info-title"><?php echo esc_html(!empty($config['company_name']) ? $config['company_name'] : get_bloginfo('name')); ?></h4>

              <ul class="twmp-checkout-payment-stage__list">
                <?php if (!empty($config['bank_name'])) : ?>
                  <li><strong><?php echo esc_html__('Bank', 'twmp-ath'); ?>:</strong> <?php echo esc_html($config['bank_name']); ?></li>
                <?php endif; ?>
                <?php if (!empty($config['account_name'])) : ?>
                  <li><strong><?php echo esc_html__('Account name', 'twmp-ath'); ?>:</strong> <?php echo esc_html($config['account_name']); ?></li>
                <?php endif; ?>
                <?php if (!empty($config['account_number'])) : ?>
                  <li><strong><?php echo esc_html__('Account number', 'twmp-ath'); ?>:</strong> <?php echo esc_html($config['account_number']); ?></li>
                <?php endif; ?>
                <?php if (!empty($config['branch'])) : ?>
                  <li><strong><?php echo esc_html__('Branch', 'twmp-ath'); ?>:</strong> <?php echo esc_html($config['branch']); ?></li>
                <?php endif; ?>
                <li><strong><?php echo esc_html__('Order', 'twmp-ath'); ?>:</strong> #<?php echo esc_html($order->get_order_number()); ?></li>
                <li><strong><?php echo esc_html__('Total', 'twmp-ath'); ?>:</strong> <?php echo wp_kses_post($order_total); ?></li>
                <?php if (!empty($config['transfer_note'])) : ?>
                  <li><strong><?php echo esc_html__('Transfer note', 'twmp-ath'); ?>:</strong> <?php echo esc_html($config['transfer_note']); ?></li>
                <?php endif; ?>
              </ul>

              <?php if (!empty($config['company_address']) || !empty($config['company_phone']) || !empty($config['company_email'])) : ?>
                <div class="twmp-checkout-payment-stage__company">
                  <?php if (!empty($config['company_address'])) : ?>
                    <p><?php echo esc_html($config['company_address']); ?></p>
                  <?php endif; ?>
                  <?php if (!empty($config['company_phone'])) : ?>
                    <p><?php echo esc_html($config['company_phone']); ?></p>
                  <?php endif; ?>
                  <?php if (!empty($config['company_email'])) : ?>
                    <p><?php echo esc_html($config['company_email']); ?></p>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <form class="twmp-checkout-proof-form" data-payment-proof-form enctype="multipart/form-data">
            <input type="hidden" name="order_id" value="<?php echo esc_attr($state['order_id']); ?>">
            <input type="hidden" name="order_key" value="<?php echo esc_attr($state['order_key']); ?>">
            <input type="hidden" name="nonce" value="<?php echo esc_attr($state['nonce']); ?>">

            <label class="twmp-checkout-proof-form__file">
              <input type="file" name="payment_bill" accept="image/*,application/pdf" data-payment-file>
              <span data-payment-file-label><?php echo esc_html__('Choose bill file', 'twmp-ath'); ?></span>
            </label>

            <button type="submit" class="twmp-checkout-proof-form__button" data-payment-submit>
              <?php echo esc_html($config['bill_title']); ?>
            </button>

            <p class="twmp-checkout-proof-form__hint"><?php echo esc_html__('Upload a clear transfer receipt after the transfer is completed.', 'twmp-ath'); ?></p>
          </form>

          <div class="twmp-checkout-proof-form__notice" data-payment-notice aria-live="polite"></div>
        </div>
      </div>
    </section>
  </div>
<?php
}

// Dùng để chặn WooCommerce redirect khỏi checkout khi giỏ hàng trống.
add_filter('woocommerce_checkout_redirect_empty_cart', 'twmp_checkout_allow_payment_step_empty_cart', 10);
function twmp_checkout_allow_payment_step_empty_cart($redirect_empty_cart)
{
  if (function_exists('twmp_checkout_is_payment_step_2') && twmp_checkout_is_payment_step_2()) {
    return false;
  }

  return $redirect_empty_cart;
}

add_action('woocommerce_admin_order_data_after_order_details', function ($order) {
  if (!$order instanceof WC_Order) {
    return;
  }
  $attachment_id = $order->get_meta('_twmp_checkout_payment_proof_attachment_id', true);
  if (!$attachment_id) {
    return;
  }

  $url       = wp_get_attachment_url($attachment_id);
  $mime_type = get_post_mime_type($attachment_id);

  echo '<div class="order_data_column" style="width:100%; margin-top:20px;">';
  echo '<h3>Payment Receipt</h3>';

  if (str_starts_with($mime_type, 'image/')) {
    echo '<a href="' . esc_url($url) . '" target="_blank">';
    echo wp_get_attachment_image($attachment_id, 'medium', false, [
      'style' => 'max-width:300px;height:auto;border:1px solid #ddd;padding:6px;background:#fff;',
    ]);
    echo '</a>';
  } else {
    echo '<a href="' . esc_url($url) . '" target="_blank">View uploaded receipt</a>';
  }

  echo '</div>';
});

add_action('wp_ajax_twmp_checkout_poll_payment_status', 'twmp_checkout_poll_payment_status');
add_action('wp_ajax_nopriv_twmp_checkout_poll_payment_status', 'twmp_checkout_poll_payment_status');

function twmp_checkout_poll_payment_status()
{
  $order_context = twmp_checkout_get_payment_order_from_request();
  if (is_wp_error($order_context)) {
    wp_send_json_error(array('message' => $order_context->get_error_message()), 403);
  }

  $order = $order_context['order'];
  check_ajax_referer('twmp_checkout_payment_' . $order->get_id(), 'nonce');

  wp_send_json_success(array(
    'message' => esc_html__('Payment status loaded.', 'twmp-ath'),
    'status'  => twmp_checkout_get_payment_status_payload($order),
  ));
}

function twmp_checkout_get_payment_order_from_request()
{
  $order_id = 0;
  $order_key = '';

  if (isset($_REQUEST['order_id'])) {
    $order_id = absint(wp_unslash($_REQUEST['order_id']));
  }

  if (isset($_REQUEST['order_key'])) {
    $order_key = sanitize_text_field(wp_unslash($_REQUEST['order_key']));
  } elseif (isset($_REQUEST['key'])) {
    $order_key = sanitize_text_field(wp_unslash($_REQUEST['key']));
  }

  if ((!$order_id || !$order_key) && function_exists('WC') && WC()->session) {
    if (!$order_id) {
      $order_id = absint(WC()->session->get('twmp_checkout_payment_order_id', 0));
    }

    if (!$order_key) {
      $order_key = (string) WC()->session->get('twmp_checkout_payment_order_key', '');
    }
  }

  if (!$order_id || !$order_key) {
    return new WP_Error('twmp_checkout_missing_order', esc_html__('Order session is missing.', 'twmp-ath'));
  }

  $order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;
  if (!$order instanceof WC_Order || !hash_equals($order->get_order_key(), $order_key)) {
    return new WP_Error('twmp_checkout_invalid_order', esc_html__('Invalid order verification token.', 'twmp-ath'));
  }

  return array(
    'order'     => $order,
    'order_id'  => $order_id,
    'order_key' => $order_key,
  );
}

function twmp_checkout_get_payment_status_payload(WC_Order $order)
{
  $proof_status = twmp_checkout_get_payment_proof_status($order);

  return array(
    'order_id'      => $order->get_id(),
    'order_number'  => $order->get_order_number(),
    'order_key'     => $order->get_order_key(),
    'order_status'  => $order->get_status(),
    'proof_status'  => $proof_status,
    'status_label'  => twmp_checkout_get_payment_status_label($proof_status),
    'status_text'   => twmp_checkout_get_payment_status_text($proof_status),
    'action_label'  => twmp_checkout_get_payment_action_label($proof_status),
    'can_upload'    => in_array($proof_status, array('waiting_upload', 'rejected'), true),
    'reviewed_at'   => (string) $order->get_meta('_twmp_checkout_payment_reviewed_at', true),
    'reviewed_by'   => absint($order->get_meta('_twmp_checkout_payment_reviewed_by', true)),
    'review_note'   => (string) $order->get_meta('_twmp_checkout_payment_review_note', true),
    'attachment_id' => absint($order->get_meta('_twmp_checkout_payment_proof_attachment_id', true)),
  );
}

add_action('wp_ajax_twmp_checkout_upload_payment_proof', 'twmp_checkout_handle_payment_proof_upload');
add_action('wp_ajax_nopriv_twmp_checkout_upload_payment_proof', 'twmp_checkout_handle_payment_proof_upload');

function twmp_checkout_handle_payment_proof_upload()
{
  $order_context = twmp_checkout_get_payment_order_from_request();
  if (is_wp_error($order_context)) {
    wp_send_json_error(array('message' => $order_context->get_error_message()), 403);
  }

  $order = $order_context['order'];
  check_ajax_referer('twmp_checkout_payment_' . $order->get_id(), 'nonce');

  $proof_status = twmp_checkout_get_payment_proof_status($order);
  if ('approved' === $proof_status) {
    wp_send_json_error(array('message' => esc_html__('This order was already approved.', 'twmp-ath')), 409);
  }

  if (empty($_FILES['payment_bill']) || empty($_FILES['payment_bill']['name'])) {
    wp_send_json_error(array('message' => esc_html__('Please choose a bill file first.', 'twmp-ath')), 400);
  }

  $file = $_FILES['payment_bill'];
  $max_size = 10 * MB_IN_BYTES;
  if (!empty($file['size']) && absint($file['size']) > $max_size) {
    wp_send_json_error(array('message' => esc_html__('Bill file is too large. Maximum size is 10MB.', 'twmp-ath')), 400);
  }

  $allowed_mimes = array(
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'pdf'  => 'application/pdf',
  );

  $file_info = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $allowed_mimes);
  if (empty($file_info['ext']) || empty($file_info['type'])) {
    wp_send_json_error(array('message' => esc_html__('Unsupported file type. Please upload image or PDF.', 'twmp-ath')), 400);
  }

  require_once ABSPATH . 'wp-admin/includes/file.php';
  require_once ABSPATH . 'wp-admin/includes/image.php';
  require_once ABSPATH . 'wp-admin/includes/media.php';

  $movefile = wp_handle_upload($file, array(
    'test_form' => false,
    'mimes'     => $allowed_mimes,
  ));

  if (!empty($movefile['error'])) {
    wp_send_json_error(array('message' => $movefile['error']), 400);
  }

  $attachment = array(
    'post_mime_type' => $movefile['type'],
    'post_title'     => sanitize_file_name(wp_basename($movefile['file'])),
    'post_content'   => '',
    'post_status'    => 'inherit',
  );

  $attachment_id = wp_insert_attachment($attachment, $movefile['file'], $order->get_id());
  if (is_wp_error($attachment_id) || !$attachment_id) {
    wp_send_json_error(array('message' => esc_html__('Could not save the uploaded file.', 'twmp-ath')), 500);
  }

  $attachment_data = wp_generate_attachment_metadata($attachment_id, $movefile['file']);
  wp_update_attachment_metadata($attachment_id, $attachment_data);

  $order->update_meta_data('_twmp_checkout_payment_step', 'payment');
  $order->update_meta_data('_twmp_checkout_payment_proof_attachment_id', absint($attachment_id));
  $order->update_meta_data('_twmp_checkout_payment_proof_status', 'pending_review');
  $order->update_meta_data('_twmp_checkout_payment_reviewed_at', '');
  $order->update_meta_data('_twmp_checkout_payment_reviewed_by', 0);
  $order->update_meta_data('_twmp_checkout_payment_review_note', '');
  $order->add_order_note(sprintf(esc_html__('Customer uploaded payment proof (attachment #%s).', 'twmp-ath'), absint($attachment_id)));
  $order->save();

  wp_send_json_success(array(
    'message' => esc_html__('Bill uploaded successfully. Waiting for confirmation.', 'twmp-ath'),
    'status'  => twmp_checkout_get_payment_status_payload($order),
    'redirect_url' => add_query_arg(
      'key',
      $order->get_order_key(),
      wc_get_endpoint_url('order-received', $order->get_id(), wc_get_checkout_url())
    ),
  ));
}
