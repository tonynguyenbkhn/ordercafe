<?php

if (!defined('ABSPATH')) {
    exit;
}

if (is_object(WC()->cart) && !empty(WC()->cart)) :
$data = wp_parse_args($args, [

]);

$_class = 'mini-cart-sidebar';
$_class .= !empty($data['class']) ? esc_attr(' ' . $data['class']) : '';

$_col_class = 'post-grid__col';
$_col_class .= !empty($data['col_class']) ? esc_attr(' ' . $data['col_class']) : '';

$total = WC()->cart->cart_contents_count; ?>
<div class="<?php echo esc_attr( $_class ); ?>" data-block="mini-cart">
    <div class="w100 abs mini-cart__overlay js-mini-cart-close"></div>
    <div class="mini-cart__wrapper">
        <div class="mini-cart__head">
            <p class="text-uppercase mini-cart__title"><?php esc_html_e('Shopping cart', 'twmp-ath'); ?></p>
            <span class="mini-cart__count"><?php echo esc_html($total); ?></span>
            <button class="mini-cart__close js-mini-cart-close" aria-label="<?php _e('Close a mini cart', 'twmp-ath'); ?>">
                <?php echo twmp_get_svg_icon('close'); ?>
            </button>
        </div>
        <div class="widget_shopping_cart_content">
            <?php $data = []; $name = null; get_template_part('template-parts/footers/wcs-mini-cart', $name, $data); ?>
        </div>
    </div>
</div>
<?php
endif;



