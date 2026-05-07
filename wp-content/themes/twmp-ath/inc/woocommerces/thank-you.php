<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('woocommerce_before_thankyou', 'wcs_thank_you_icon');

function wcs_thank_you_icon()
{
    echo '<div class="thank-you__icon">' . twmp_get_svg_icon('thank-you') . '</div>';
}

add_filter('woocommerce_order_get_formatted_billing_address', 'custom_format_billing_address', 10, 3);

function custom_format_billing_address($address, $raw_address, $order)
{
    if (!$order instanceof WC_Order) {
        return $address;
    }

    // Lấy custom fields từ order meta
    $sexy = $order->get_meta('_billing_sexy');
    $delivery_form = $order->get_meta('_billing_delivery_form');
    $delivery_address = $order->get_meta('_billing_delivery_address');
    $district = $order->get_meta('_billing_district_district');
    $wards = $order->get_meta('_billing_wards_and_communes');

    // Nếu không có custom fields, trả về address mặc định
    if (empty($sexy) && empty($delivery_form) && empty($delivery_address) && empty($district) && empty($wards)) {
        return $address;
    }

    // Xác định cách xưng hô
    $sexy_label = (strtolower($sexy) === 'male') ? 'Anh' : ((strtolower($sexy) === 'female') ? 'Chị' : '');

    // Xác định hình thức nhận hàng
    $delivery_label = '';
    if ($delivery_form === 'nhan-hang-tai-nha') {
        $delivery_label = 'Nhận hàng tại nhà';
    } elseif ($delivery_form === 'nhan-tai-cua-hang') {
        $delivery_label = 'Nhận tại cửa hàng';
    }

    // Tạo địa chỉ đầy đủ
    $full_address = '';
    if ($delivery_address) {
        $full_address = $delivery_address;
        if ($wards) {
            $full_address .= ', ' . $wards;
        }
        if ($district) {
            $full_address .= ', ' . $district;
        }
    }

    // Nếu là admin, hiển thị format đơn giản hơn
    if (is_admin()) {
        $admin_address = '';
        if ($sexy_label) {
            $admin_address .= $sexy_label . ' ' . $raw_address['last_name'] . "\n";
        }
        if ($raw_address['phone']) {
            $admin_address .= 'SĐT: ' . $raw_address['phone'] . "\n";
        }
        if ($delivery_label) {
            $admin_address .= 'Hình thức: ' . $delivery_label . "\n";
        }
        if ($full_address) {
            $admin_address .= 'Địa chỉ: ' . $full_address;
        }
        return $admin_address;
    }

    // Format cho frontend (thank you page)
    return '<div class="custom-billing-address">
        <ul>
            <li>
                <span>' . esc_html($sexy_label) . ':</span>
                <span>' . esc_html($raw_address['last_name']) . '</span>
            </li>
            <li>
                <span>Số điện thoại:</span>
                <span>' . esc_html($raw_address['phone']) . '</span>
            </li>
            <li>
                <span>Hình thức nhận hàng:</span>
                <span>' . esc_html($delivery_label) . '</span>
            </li>
            <li>
                <span>Địa chỉ:</span>
                <span>' . esc_html($full_address) . '</span>
            </li>
        </ul>
    </div>';
}

add_filter('woocommerce_thankyou_order_received_text', function ($text, $order) {
    if (!$order instanceof WC_Order) {
        return $text;
    }

    $full_name = esc_html($order->get_billing_last_name());
    $gender    = strtolower((string) $order->get_meta('_billing_sexy'));

    $title = '';
    if ($gender === 'male') {
        $title = 'Anh';
    } elseif ($gender === 'female') {
        $title = 'Chị';
    }

    // WooCommerce trả về HTML có span/bdi cho giá tiền
    $total = $order->get_formatted_order_total();

    $message  = sprintf(
        __('Cảm ơn bạn %1$s đã đặt hàng tại Nam Linh Digital.', 'twmp-ath'),
        '<span>' . esc_html(trim($title . ' ' . $full_name)) . '</span>'
    );

    $message .= '<br>';

    $message .= sprintf(
        __('Vui lòng thanh toán số tiền %s cho nhân viên giao hàng khi nhận được hàng.', 'twmp-ath'),
        $total
    );

    return wp_kses($message, [
        'br'   => [],
        'span' => [
            'class' => true,
        ],
        'bdi'  => [],
    ]);
}, 10, 2);


add_action('woocommerce_thankyou', function () {
    $thank_you_footer = get_field('thank_you_footer', 'option')
?>
    <div class="d-flex flex-column justify-content-center align-items-center">
        <?php
        get_template_part('templates/core-blocks/button', null, [
            'class'       => 'back-home',
            'button_text' => esc_html__('Back to home page', 'twmp-ath'),
            'button_url' => home_url(),
        ]);
        ?>
        <div class="copyright-order">
            <?php echo wp_kses_post($thank_you_footer); ?>
        </div>
    </div>
<?php
}, 10);
