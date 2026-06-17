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

while (have_posts()) :
    the_post();
endwhile;

$terms = twmp_cafe_menu_get_terms();
$hero_eyebrow = function_exists('get_field') ? (string) get_field('cafe_menu_hero_eyebrow', 'option') : '';
$hero_title = function_exists('get_field') ? (string) get_field('cafe_menu_hero_title', 'option') : '';
$hero_description = function_exists('get_field') ? (string) get_field('cafe_menu_hero_description', 'option') : '';
$hero_card_label = function_exists('get_field') ? (string) get_field('cafe_menu_hero_card_label', 'option') : '';
$hero_card_title = function_exists('get_field') ? (string) get_field('cafe_menu_hero_card_title', 'option') : '';
$hero_card_description = function_exists('get_field') ? (string) get_field('cafe_menu_hero_card_description', 'option') : '';

if ('' === $hero_eyebrow) {
    $hero_eyebrow = __('Cafe Take Away', 'twmp-ath');
}

if ('' === $hero_title) {
    $hero_title = __('Menu', 'twmp-ath');
}

if ('' === $hero_description) {
    $hero_description = '<p>' . esc_html__('Chọn món, tuỳ biến size / topping, thêm vào giỏ và lấy nhanh tại quán.', 'twmp-ath') . '</p>';
}

if ('' === $hero_card_label) {
    $hero_card_label = __('Pickup only', 'twmp-ath');
}

if ('' === $hero_card_title) {
    $hero_card_title = __('Đặt trước, lấy nhanh', 'twmp-ath');
}

if ('' === $hero_card_description) {
    $hero_card_description = '<p>' . esc_html__('Món nóng, món lạnh và topping đều được gom theo danh mục để chọn nhanh hơn.', 'twmp-ath') . '</p>';
}

get_header();
?>
<div class="twmp-cafe-menu">
    <div class="twmp-cafe-menu__container">
        <header class="twmp-cafe-menu__hero">
            <div class="twmp-cafe-menu__hero-copy">
                <p class="twmp-cafe-menu__eyebrow"><?php echo esc_html($hero_eyebrow); ?></p>
                <h1 class="twmp-cafe-menu__title"><?php echo esc_html($hero_title); ?></h1>
                <div class="twmp-cafe-menu__description">
                    <?php echo wp_kses_post($hero_description); ?>
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
                <span class="twmp-cafe-menu__hero-card-label"><?php echo esc_html($hero_card_label); ?></span>
                <strong><?php echo esc_html($hero_card_title); ?></strong>
                <div class="twmp-cafe-menu__hero-card-description">
                    <?php echo wp_kses_post($hero_card_description); ?>
                </div>
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

        <?php echo twmp_cafe_menu_render_product_modal_shell(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <button type="button" class="twmp-cafe-menu__mobile-cart js-cafe-cart-toggle">
            <?php echo twmp_get_svg_icon('cart'); ?>
            <strong class="js-cafe-cart-count" data-cafe-cart-count><?php echo esc_html((int) WC()->cart->get_cart_contents_count()); ?></strong>
        </button>

        <div class="twmp-cafe-menu__backdrop js-cafe-cart-toggle" aria-hidden="true"></div>
    </div>
</div>
<?php
get_footer();
