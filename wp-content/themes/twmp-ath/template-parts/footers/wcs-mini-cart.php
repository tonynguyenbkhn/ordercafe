<?php

if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_before_mini_cart');

if (!WC()->cart->is_empty()) {
?>
    <div class="widget_shopping_cart_content">
        <ul class="cart_list product_list_widget">
            <?php
            do_action('woocommerce_before_mini_cart_contents');

            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
                // $bundled_cart_items = wc_pb_get_bundled_cart_items( $cart_item ); This is template code.

                if ($_product && $_product->exists() && $cart_item['quantity'] > 0) {
                    $product_name      = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
                    $thumbnail         = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key);
                    $product_price     = apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key);
                    $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key); ?>
                    <li class="woocommerce-mini-cart-item mini_cart_item <?php echo esc_attr(apply_filters('woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key)); ?>">
                        <?php
                        echo apply_filters( // phpcs:ignore
                            'woocommerce_cart_item_remove_link',
                            sprintf(
                                '<a href="%s" class="remove remove_from_cart_button" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s"><svg width="17" height="17" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 7L7 18M7 7L18 18" stroke="#121923" stroke-width="1.2"/></svg></a>',
                                esc_url(wc_get_cart_remove_url($cart_item_key)),
                                esc_attr__('Remove this item', 'twmp-ath'),
                                esc_attr($product_id),
                                esc_attr($cart_item_key),
                                esc_attr($_product->get_sku())
                            ),
                            $cart_item_key
                        );
                        echo wp_kses_post( $thumbnail );
                        echo '<div class="d-flex flex-column">';
                        if (empty($product_permalink)) {
                            echo '<span>' . $product_name . '</span>';
                        } else {
                            echo '<a href="' . esc_url($product_permalink) . '"><span>' . $product_name . '</span>' . '</a>';
                        }
                        echo wc_get_formatted_cart_item_data($cart_item);
                        echo apply_filters('woocommerce_widget_cart_item_quantity', '<span class="quantity">' . sprintf('%s &times; %s', $cart_item['quantity'], $product_price) . '</span>', $cart_item, $cart_item_key);
                        echo '</div>';
                        ?>
                    </li>
            <?php
                }
            }

            do_action('woocommerce_mini_cart_contents'); ?>
        </ul>

        <p class="woocommerce-mini-cart__total total">
            <?php
            /**
             * Hook: woocommerce_widget_shopping_cart_total.
             *
             * @hooked woocommerce_widget_shopping_cart_subtotal - 10
             */
            do_action('woocommerce_widget_shopping_cart_total'); ?>
        </p>

        <?php do_action('woocommerce_widget_shopping_cart_before_buttons'); ?>

        <p class="woocommerce-mini-cart__buttons buttons"><?php do_action('woocommerce_widget_shopping_cart_buttons'); ?></p>

        <?php
        do_action('woocommerce_widget_shopping_cart_after_buttons'); ?>
    </div>
<?php
} else {
?>
    <div class="widget_shopping_cart_content">
        <p class="woocommerce-mini-cart__empty-message"><?php esc_html_e('No products in the cart.', 'twmp-ath'); ?></p>
    </div>
<?php
}

do_action('woocommerce_after_mini_cart');
