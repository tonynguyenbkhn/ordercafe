<?php

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

    return '<div class="custom-billing-address">
        <ul>
            <li>
                <span>Họ và tên:</span>
                <span>' . esc_html($raw_address['first_name']) . '</span>
            </li>
            <li>
                <span>Số điện thoại:</span>
                <span>' . esc_html($raw_address['phone']) . '</span>
            </li>
        </ul>
    </div>';
}

add_filter('woocommerce_thankyou_order_received_text', function ($text, $order) {
    if (!$order instanceof WC_Order) {
        return $text;
    }

    $full_name = esc_html($order->get_billing_first_name());

    $total = $order->get_formatted_order_total();

    $message  = sprintf(
        __('Cảm ơn bạn %1$s đã đặt hàng tại ABC.', 'twmp-ath'),
        '<span>' . esc_html(trim($full_name)) . '</span>'
    );

    $message .= '<br>';

    $message .= sprintf(
        __('Vui lòng thanh toán số tiền %s cho nhân viên.', 'twmp-ath'),
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
        get_template_part('templates/components/button', null, [
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
