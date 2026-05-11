<?php

/**
 * Template Name: Cafe Menu
 * Template Post Type: page
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('twmp_cafe_menu_ensure_cart') || !twmp_cafe_menu_ensure_cart()) {
    get_header();
    ?>
    <div class="page page-standard">
        <div class="container page-standard__container">
            <main id="primary" class="site-main">
                <p><?php esc_html_e('WooCommerce is required for this page.', 'twmp-ath'); ?></p>
            </main>
        </div>
    </div>
    <?php
    get_footer();
    return;
}

$page_title = '';
$page_content = '';

while (have_posts()) :
    the_post();
    $page_title   = get_the_title();
    $page_content = trim((string) get_the_content());
endwhile;

$terms = twmp_cafe_menu_get_terms();

get_header();
?>
<div class="twmp-cafe-menu">
    <div class="twmp-cafe-menu__container">
        <header class="twmp-cafe-menu__hero">
            <div class="twmp-cafe-menu__hero-copy">
                <p class="twmp-cafe-menu__eyebrow"><?php esc_html_e('Cafe Take Away', 'twmp-ath'); ?></p>
                <h1 class="twmp-cafe-menu__title"><?php echo esc_html($page_title ? $page_title : __('Menu', 'twmp-ath')); ?></h1>
                <div class="twmp-cafe-menu__description">
                    <?php
                    if ($page_content) {
                        echo apply_filters('the_content', $page_content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    } else {
                        ?>
                        <p><?php esc_html_e('Chọn món, tuỳ biến size / topping, thêm vào giỏ và lấy nhanh tại quán.', 'twmp-ath'); ?></p>
                        <?php
                    }
                    ?>
                </div>
                <div class="twmp-cafe-menu__hero-actions">
                    <a href="#twmp-cafe-menu" class="twmp-cafe-menu__cta">
                        <?php esc_html_e('Xem menu', 'twmp-ath'); ?>
                    </a>
                    <button type="button" class="twmp-cafe-menu__cart-cta js-cafe-cart-toggle">
                        <?php esc_html_e('Mở giỏ hàng', 'twmp-ath'); ?>
                        <span class="js-cafe-cart-count" data-cafe-cart-count><?php echo esc_html((int) WC()->cart->get_cart_contents_count()); ?></span>
                    </button>
                </div>
            </div>
            <div class="twmp-cafe-menu__hero-card">
                <span class="twmp-cafe-menu__hero-card-label"><?php esc_html_e('Pickup only', 'twmp-ath'); ?></span>
                <strong><?php esc_html_e('Đặt trước, lấy nhanh', 'twmp-ath'); ?></strong>
                <p><?php esc_html_e('Món nóng, món lạnh và topping đều được gom theo danh mục để chọn nhanh hơn.', 'twmp-ath'); ?></p>
            </div>
        </header>

        <div class="twmp-cafe-menu__topbar" id="twmp-cafe-menu">
            <nav class="twmp-cafe-menu__categories" aria-label="<?php esc_attr_e('Danh mục sản phẩm', 'twmp-ath'); ?>">
                <a href="#twmp-cafe-menu" class="twmp-cafe-menu__category is-active js-cafe-category" data-target="twmp-cafe-menu"><?php esc_html_e('Tất cả', 'twmp-ath'); ?></a>
                <?php foreach ($terms as $term) : ?>
                    <a
                        href="#menu-cat-<?php echo esc_attr($term->slug); ?>"
                        class="twmp-cafe-menu__category js-cafe-category"
                        data-target="menu-cat-<?php echo esc_attr($term->slug); ?>">
                        <?php echo esc_html($term->name); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="twmp-cafe-menu__layout">
            <main class="twmp-cafe-menu__main">
                <?php if (empty($terms)) : ?>
                    <section class="twmp-cafe-menu__empty">
                        <p><?php esc_html_e('Chưa có danh mục sản phẩm nào.', 'twmp-ath'); ?></p>
                    </section>
                <?php endif; ?>

                <?php foreach ($terms as $term) : ?>
                    <?php $products = twmp_cafe_menu_get_products_for_term($term); ?>
                    <?php if (empty($products)) { continue; } ?>
                    <section class="twmp-cafe-menu__section" id="menu-cat-<?php echo esc_attr($term->slug); ?>" data-menu-section>
                        <div class="twmp-cafe-menu__section-head">
                            <div>
                                <p class="twmp-cafe-menu__section-kicker"><?php esc_html_e('Danh mục', 'twmp-ath'); ?></p>
                                <h2 class="twmp-cafe-menu__section-title"><?php echo esc_html($term->name); ?></h2>
                            </div>
                            <span class="twmp-cafe-menu__section-count"><?php echo esc_html(sprintf(_n('%d món', '%d món', count($products), 'twmp-ath'), count($products))); ?></span>
                        </div>
                        <div class="twmp-cafe-menu__grid">
                            <?php foreach ($products as $product) : ?>
                                <?php if ($product instanceof WC_Product) { twmp_cafe_menu_render_product_card($product); } ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </main>

            <div class="twmp-cafe-menu__sidebar-wrap">
                <?php echo twmp_cafe_menu_render_cart_sidebar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </div>

        <button type="button" class="twmp-cafe-menu__mobile-cart js-cafe-cart-toggle">
            <span><?php esc_html_e('Giỏ hàng', 'twmp-ath'); ?></span>
            <strong class="js-cafe-cart-count" data-cafe-cart-count><?php echo esc_html((int) WC()->cart->get_cart_contents_count()); ?></strong>
        </button>

        <div class="twmp-cafe-menu__backdrop js-cafe-cart-toggle" aria-hidden="true"></div>
    </div>
</div>
<?php
get_footer();
