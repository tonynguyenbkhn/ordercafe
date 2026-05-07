<?php

if (!defined('ABSPATH')) {
    exit;
}

remove_action('woocommerce_before_cart', 'woocommerce_output_all_notices', 10);

remove_action('woocommerce_before_cart', 'woocommerce_output_all_notices', 10);
remove_action('woocommerce_cart_is_empty', 'wc_empty_cart_message', 10);
add_action('woocommerce_before_cart', 'print_errors', 10);

// remove_action('woocommerce_cart_collaterals', 'woocommerce_cross_sell_display');
remove_action('woocommerce_cart_collaterals', 'woocommerce_cart_totals', 10);

add_action('woocommerce_before_cart', 'wcs_cart_container_open', 15);
add_action('woocommerce_before_cart', 'wcs_cart_render_shop_steps', 16);
add_action('woocommerce_before_cart', 'wcs_cart_cart_page_grid_open', 20);

// Case 2: Empty cart
add_action('woocommerce_cart_is_empty', 'wcs_cart_container_open', 15);
add_action('woocommerce_cart_is_empty', 'wcs_cart_cart_page_grid_open', 20);
add_action('woocommerce_cart_is_empty', 'wcs_cart_cart_page_col_open_main',  25);
add_action('woocommerce_cart_is_empty', 'wc_empty_cart_message', 40);

// Column: Cart table
add_action('woocommerce_before_cart', 'wcs_cart_cart_page_col_open_main',  25);
add_action('woocommerce_before_cart_collaterals',  'wcs_cart_cart_page_col_close', 1);

add_filter('woocommerce_cart_item_price', 'wcs_cart_update_cart_item_price', 10, 3);

// Column: Cart Totals
add_action('woocommerce_before_cart_collaterals', 'wcs_cart_cart_page_col_open_sidebar',  2);
add_action('woocommerce_cart_collaterals', 'woocommerce_cart_totals', 10);

add_action('woocommerce_after_cart',  'wcs_cart_cart_page_col_close', 18);
add_action('woocommerce_after_cart', 'wcs_cart_cart_page_grid_close', 19);
add_action('woocommerce_after_cart', 'wcs_cart_container_close', 20);

add_filter('woocommerce_add_to_cart_fragments', 'wcs_cart_woocommerce_header_add_to_cart_fragment', 5);

// function wcs_cart_render_shop_steps()
// {
//     get_template_part('templates/blocks/shop-steps', null, []);
// }

function wcs_cart_container_open()
{
    if (WC()->cart->is_empty()) {
        $class  = 'page-block page-block--cart-empty';
    } else {
        $class = 'page-block page-block--cart';
    }

    echo '<div class="' . $class . '">';
    echo '<div class="page-block__container">';
}

function wcs_cart_container_close()
{
    echo '</div>';
}

function wcs_cart_cart_page_grid_open()
{
    echo '<div class="page-block__flex d-flex ">';
}

function wcs_cart_cart_page_grid_close()
{
    echo '</div>';
}

function wcs_cart_cart_page_col_open_main()
{
    // echo '<div class="grid__col page-block__col page-block__col--main">';
    // echo '<div class="page-block__inner">';
}

function wcs_cart_cart_page_col_open_sidebar()
{
    // echo '<div class="grid__col page-block__col page-block__col--sidebar">';
    // echo '<div class="page-block__inner">';
}

function wcs_cart_cart_page_col_close()
{
    // echo '</div>';
    // echo '</div>';
}

function wcs_cart_update_cart_item_price($price, $cart_item, $cart_item_key)
{
    $product = $cart_item['data'];
    $regular_price    = $product->get_regular_price();
    $sale_price = $product->get_sale_price();

    if ($product->is_on_sale() && ! empty($sale_price)) {
        $percentage = twmp_get_price_discount_percentage($product, 'percentage');

        $price = '<span class="percentage">'.$percentage.'</span>';

        $price .= sprintf('%s', wc_format_sale_price(
            wc_get_price_to_display($product, array('price' => $product->get_regular_price(), 'qty' => 1)),
            wc_get_price_to_display($product, array('qty' => 1))
        ) . $product->get_price_suffix());
    }

    return '<div class="product-price-wrapper">' . $price . '</div>';
}

function wcs_cart_woocommerce_header_add_to_cart_fragment($fragments)
{
    global $woocommerce;
    ob_start();
?>
    <span class="cart-shortcode__count">
        <?php echo sprintf(_n('%d', '%d', $woocommerce->cart->cart_contents_count, 'twmp-ath'), $woocommerce->cart->cart_contents_count); ?>
    </span>
<?php
    $fragments['.cart-shortcode__count'] = ob_get_clean();
    return $fragments;
}