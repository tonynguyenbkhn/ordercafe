<?php

if (!defined('ABSPATH')) {
    exit;
}

// Tiếng việt: Để tùy chỉnh cách hiển thị sản phẩm trong vòng lặp sản phẩm WooCommerce, bạn có thể sử dụng hook 'woocommerce_before_shop_loop_item' để thay thế các phần tử mặc định bằng cách của riêng bạn. Dưới đây là một ví dụ về cách làm điều này:
add_action('wp', function () {
    remove_action('woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10);
    remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10);
    remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);
    remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);
    remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);
    remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5);
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
    remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);
    remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);

    add_action('woocommerce_before_shop_loop_item', 'twmp_render_product_card', 10);
});

function twmp_render_product_card()
{
    global $product, $post;

    if (!$product instanceof WC_Product) {
        return;
    }

    $product_id   = $product->get_id();
    $product_post = get_post($product_id);

    if (!$product_post instanceof WP_Post) {
        return;
    }

    $title    = $product->get_name();
    $url      = get_permalink($product_id);
    $image_id = $product->get_image_id();

    $description_source = trim(wp_strip_all_tags((string) $product_post->post_content));
    $description        = '';

    if ('' !== $description_source) {
        $description = wp_trim_words($description_source, 18, '...');
    } elseif (!empty($product_post->post_excerpt)) {
        $description = wp_trim_words(wp_strip_all_tags($product_post->post_excerpt), 18, '...');
    }

    $timestamp    = get_post_timestamp($product_post);
    $date_day     = $timestamp ? wp_date('j', $timestamp) : '';
    $date_weekday = $timestamp ? strtoupper(wp_date('D', $timestamp)) : '';
    $date_month   = $timestamp ? strtoupper(wp_date('M', $timestamp)) : '';
    $date_year    = $timestamp ? wp_date('y', $timestamp) : '';

?>

    <div class="product-card product-card--theme-red">
        <a class="product-card__link" href="<?php echo esc_url($url); ?>" aria-label="<?php echo esc_attr($title); ?>">
            <div class="product-card__media">
                <figure class="image product-card__image-wrap image--cover image--default">
                    <?php
                    if ($image_id) {
                        echo wp_get_attachment_image($image_id, 'woocommerce_thumbnail', false, [
                            'class' => 'image__img product-card__image',
                            'alt'   => esc_attr($title),
                        ]);
                    } else {
                        echo wc_placeholder_img('woocommerce_thumbnail', [
                            'class' => 'image__img product-card__image',
                        ]);
                    }
                    ?>
                </figure>

                <div class="product-card__overlay" aria-hidden="true"></div>
            </div>
        </a>

        <div class="product-card__body">
            <?php if ($description) : ?>
                <div class="product-card__more">

                    <div class="product-card__actions">
                        <div class="product-card__action product-card__action--book">
                            <?php
                            if (function_exists('twmp_render_cart_button')) {
                                twmp_render_cart_button(
                                    $product_id,
                                    __('Book Ticket', 'twmp'),
                                    'bg-primary-500 text-system-white typo-system-button button-default cart-redirect-btn'
                                );
                            }
                            ?>
                        </div>

                        <div class="product-card__action product-card__action--view">
                            <a
                                title="<?php echo esc_attr(sprintf(__('View Detail %s', 'twmp'), $title)); ?>"
                                class="product-card__view-button button-normal typo-system-button button-default has-icon has-after-icon"
                                href="<?php echo esc_url($url); ?>">
                                <span class="text pe-none"><?php echo esc_html__('View Detail', 'twmp'); ?></span>
                                <span class="icon pe-none" aria-hidden="true"></span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php
}

// Layout

add_action('woocommerce_before_main_content', 'twmp_render_shop_layout', 40);
function twmp_render_shop_layout()
{
    if (is_shop() || is_product_category()):
        echo '<div class="twmp-shop-layout"><div class="twmp-shop-layout__main"><div class="twmp-shop-layout__container container">';
    endif;
}

add_action('woocommerce_after_main_content', 'twmp_render_shop_layout_end', 9);
function twmp_render_shop_layout_end()
{
    if (is_shop() || is_product_category()):
        echo '</div></div></div>';
    endif;
}

add_action('woocommerce_shop_loop_header', 'twmp_render_shop_header', 20);
function twmp_render_shop_header()
{
    echo '<div class="twmp-shop-layout-wrapper"><div class="twmp-shop-layout__left"><div class="twmp-shop-layout__left-innner"><div class="twmp-shop-layout__left-wrap">' . do_shortcode('[facetwp facet="categories"]') . do_shortcode('[facetwp facet="search_form"]') . '</div>';
}
add_action('woocommerce_after_main_content', 'twmp_render_shop_header_end', 1);
function twmp_render_shop_header_end()
{
    echo '</div></div>';
}

add_action('woocommerce_after_main_content', 'twmp_render_shop_sidebar_start', 2);
function twmp_render_shop_sidebar_start()
{
    echo '<div class="twmp-shop-layout__right"><div class="twmp-shop-layout__right-innner">';
}

add_action('woocommerce_after_main_content', 'twmp_render_shop_sidebar_main', 5);
function twmp_render_shop_sidebar_main()
{
    if (class_exists('WooCommerce') && (is_shop() || is_product_taxonomy())) {
    ?>
        
    <?php
    }
}

add_action('woocommerce_after_main_content', 'twmp_render_shop_sidebar_end', 8);
function twmp_render_shop_sidebar_end()
{
    echo '</div></div></div>';
}


// Custom Banner Shop Page

add_action('woocommerce_before_main_content', function () {
    if (!class_exists('WooCommerce')) {
        return;
    }

    if ( !is_shop() || !is_product_category() ) {
        return;
    }

    $banner_id = 0;

    if (is_product_category()) {
        $term = get_queried_object();

        if ($term instanceof WP_Term) {
            $banner_id = absint(function_exists('get_field') ? get_field('ath_product_cat_image', $term) : 0);
        }
    }

    if (!$banner_id) {
        $banner_id = absint(function_exists('get_field') ? get_field('ath_banner_shop_page', 'option') : 0);
    }

    if (!$banner_id) {
        return;
    }
    echo '<div class="twmp-shop-banner">';
    get_template_part('templates/components/image', null, [
        'image_id'    => $banner_id,
        'image_size'  => 'full',
        'lazyload'    => false,
        'class'       => 'twmp-shop-banner__image image--cover image--default',
        'image_class' => 'twmp-shop-banner__image',
    ]);
    echo '</div>';
}, 5);
