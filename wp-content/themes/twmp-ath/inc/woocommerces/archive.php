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

    $badges = function_exists('get_field') ? get_field('ath_badges', $product_id) : [];

    if (!is_array($badges)) {
        $badges = [];
    }

    $short_info      = function_exists('get_field') ? (string) get_field('ath_short_info', $product_id) : '';
    $location_detail = function_exists('get_field') ? (string) get_field('ath_location_detail', $product_id) : '';
    $location        = function_exists('get_field') ? (string) get_field('ath_location', $product_id) : '';

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

    $book_url = add_query_arg(
        [
            'book_ticket' => $product_id,
        ],
        $url
    );
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

            <div class="product-card__top">
                <?php if (!empty($badges)) : ?>
                    <div class="product-card__badges">
                        <?php foreach ($badges as $badge) : ?>
                            <?php
                            $badge_label = '';
                            $badge_theme = 'orange';

                            if (is_array($badge)) {
                                $badge_label = $badge['label'] ?? $badge['text'] ?? $badge['title'] ?? '';
                                $badge_theme = $badge['theme'] ?? $badge['color'] ?? $badge['type'] ?? $badge_theme;
                            } elseif (is_string($badge)) {
                                $badge_label = $badge;
                            }

                            $badge_label = trim((string) $badge_label);
                            $badge_theme = sanitize_html_class((string) $badge_theme);

                            if ('' === $badge_label) {
                                continue;
                            }
                            ?>
                            <span class="ath-badge ath-badge--<?php echo esc_attr($badge_theme); ?>">
                                <?php echo esc_html($badge_label); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($date_day || $date_weekday || $date_month || $date_year) : ?>
                    <div class="product-card__date">
                        <?php if ($date_day) : ?>
                            <span class="product-card__date-day"><?php echo esc_html($date_day); ?></span>
                        <?php endif; ?>

                        <?php if ($date_weekday) : ?>
                            <span class="product-card__date-weekday"><?php echo esc_html($date_weekday); ?></span>
                        <?php endif; ?>

                        <?php if ($date_month || $date_year) : ?>
                            <span class="product-card__date-month">
                                <?php echo esc_html(trim($date_month . ', ' . $date_year, ', ')); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="product-card__body">
                <?php if ($short_info) : ?>
                    <p class="product-card__short-info"><?php echo esc_html($short_info); ?></p>
                <?php endif; ?>

                <?php if ($location || $location_detail) : ?>
                    <div class="product-card__location">
                        <?php echo esc_html($location ?: $location_detail); ?>
                    </div>
                <?php endif; ?>

                <?php if ($description) : ?>
                    <div class="product-card__more">
                        <div class="product-card__description">
                            <?php echo esc_html($description); ?>
                        </div>
                        <div class="product-card__actions">
                            <div class="product-card__action product-card__action--book">
                                <a
                                    class="bg-primary-500 text-system-white typo-system-button button-default cart-redirect-btn"
                                    href="<?php echo esc_url($book_url); ?>">
                                    <span class="text pe-none"><?php echo esc_html__('Book Ticket', 'twmp'); ?></span>
                                    <span class="icon pe-none" aria-hidden="true"></span>
                                </a>
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
        </a>
    </div>

    <?php
}

// Layout

add_action('woocommerce_before_main_content', 'twmp_render_shop_layout', 40);
function twmp_render_shop_layout()
{
    echo '<div class="twmp-shop-layout"><div class="twmp-shop-layout__main"><div class="twmp-shop-layout__container container">';
}

add_action('woocommerce_after_main_content', 'twmp_render_shop_layout_end', 9);
function twmp_render_shop_layout_end()
{
    echo '</div></div></div>';
}

add_action('woocommerce_shop_loop_header', 'twmp_render_shop_header', 20);
function twmp_render_shop_header()
{
    echo '<div class="twmp-shop-layout-wrapper"><div class="twmp-shop-layout__left"><div class="twmp-shop-layout__left-innner">' . do_shortcode('[facetwp facet="categories"]');
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
    if (class_exists('WooCommerce') && (is_shop() || is_product_taxonomy() ) ) {
    ?>
        <div class="filter-shop">
            <div class="filter-item__head">
                <h3 class="filter-item__title"><?php echo esc_html__('Filter', 'twmp-ath'); ?></h3>
                <button class="filter-item__reset button-text d-flex items-center gap-8" onclick="FWP.reset()"><?php echo esc_html__('Clear all', 'twmp-ath'); ?> <?php echo twmp_get_svg_icon('clear-all'); ?></button>
            </div>
            <div class="filter-item__body">
                <div class="filter-item">
                    <span class="filter-item__label"><?php echo esc_html__('Date Time', 'twmp-ath'); ?></span>
                    <?php
                    echo do_shortcode('[facetwp facet="date_time"]');
                    ?>
                </div>
                <div class="filter-item">
                    <span class="filter-item__label"><?php echo esc_html__('Date of week', 'twmp-ath'); ?></span>
                    <?php
                    echo do_shortcode('[facetwp facet="date_of_week"]');
                    ?>
                </div>
                <div class="filter-item">
                    <span class="filter-item__label"><?php echo esc_html__('Age group', 'twmp-ath'); ?></span>
                    <?php
                    echo do_shortcode('[facetwp facet="age_group"]');
                    ?>
                </div>
                <div class="filter-item">
                    <span class="filter-item__label"><?php echo esc_html__('Event status', 'twmp-ath'); ?></span>
                    <?php
                    echo do_shortcode('[facetwp facet="event_status"]');
                    ?>
                </div>
                <div class="filter-item">
                    <span class="filter-item__label"><?php echo esc_html__('Event type', 'twmp-ath'); ?></span>
                    <?php
                    echo do_shortcode('[facetwp facet="event_type"]');
                    ?>
                </div>
                <div class="filter-item">
                    <span class="filter-item__label"><?php echo esc_html__('Location', 'twmp-ath'); ?></span>
                    <?php
                    echo do_shortcode('[facetwp facet="location"]');
                    ?>
                </div>
            </div>
    <?php
    }
}

add_action('woocommerce_after_main_content', 'twmp_render_shop_sidebar_end', 8);
function twmp_render_shop_sidebar_end()
{
    echo '</div></div></div>';
}
