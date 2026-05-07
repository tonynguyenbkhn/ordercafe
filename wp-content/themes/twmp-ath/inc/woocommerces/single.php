<?php

/**
 * ==========================================
 * WooCommerce Single Product Customizations
 * Theme: twmp-ath
 * ==========================================
 */

if (!defined('ABSPATH')) {
    exit;
}

//////////////////////////////
// HELPERS
//////////////////////////////

/**
 * Get cart URL
 */
function twmp_get_cart_url()
{
    return wc_get_page_permalink('cart');
}

/**
 * Redirect to cart
 */
function twmp_redirect_to_cart()
{
    wp_safe_redirect(twmp_get_cart_url());
    exit;
}

/**
 * Render checkout/cart button
 */
function twmp_render_cart_button()
{
    global $product;

    if (!$product) {
        return;
    }

    $product_id = $product->get_id();
    $cart_url = twmp_get_cart_url();
    $button_classes = 'bg-primary-500 text-system-white typo-system-button button-default cart-redirect-btn';
    $button_text = esc_html__('Book Tiket', 'twmp-ath');
    $button_html = twmp_get_svg_icon('book-ticket');

    printf(
        '<form class="twmp-buy-now-form" action="%1$s" method="post"><input type="hidden" name="add-to-cart" value="%2$d"><input type="hidden" name="twmp_buy_now" value="1"><button type="submit" class="%3$s"><span class="text pe-none">%4$s</span>%5$s</button></form>',
        esc_url(get_permalink($product_id)),
        absint($product_id),
        esc_attr($button_classes),
        esc_html($button_text),
        $button_html ? '<span class="icon pe-none" aria-hidden="true">' . $button_html . '</span>' : ''
    );
}

/**
 * Render contact us button
 */
function twmp_render_contact_us_button()
{
    $button_text = esc_html__('Contact Us', 'twmp-ath');
    $button_link = get_permalink(get_page_by_path('contact'));
    get_template_part('templates/components/button', null, [
        'class' => 'bg-system-white text-system-black typo-system-button button-default contact-us-btn',
        'button_text' => $button_text,
        'button_url' => $button_link,
        'button_link_target' => '_self',
    ]);
}

/**
 * Convert ACF field value safely
 */
function twmp_field_to_string($value)
{
    if (is_array($value)) {
        return implode(', ', array_map('sanitize_text_field', $value));
    }

    return is_scalar($value) ? (string) $value : '';
}

//////////////////////////////
// REMOVE DEFAULT WOOCOMMERCE
//////////////////////////////

add_filter('woocommerce_product_tabs', function ($tabs) {
	// Rename Description tab
	if (isset($tabs['description'])) {
		$tabs['description']['title'] = __('About', 'twmp-ath');
	}

	// Add custom Section tab
	$tabs['section'] = [
		'title'    => __('Section', 'twmp-ath'),
		'priority' => 25,
		'callback' => 'render_product_section_tab',
	];

	return $tabs;
}, 98);

function render_product_section_tab() {
	echo '<h2>' . esc_html__('Section', 'twmp-ath') . '</h2>';
	echo '<p>' . esc_html__('Your section content here.', 'twmp-ath') . '</p>';
}

add_action('wp', function () {
    remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
});

remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
remove_action('woocommerce_before_single_product', 'woocommerce_output_all_notices', 10);
remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);

add_filter('woocommerce_product_description_heading', '__return_empty_string');

//////////////////////////////
// PRODUCT CLASSES
//////////////////////////////

add_filter('woocommerce_post_class', function ($classes) {
    if (is_product()) {
        $classes[] = 'product__detail';
    }

    return $classes;
}, 10);

//////////////////////////////
// REVIEW STRUCTURE
//////////////////////////////

add_action('woocommerce_review_before', function () {
    echo '<div class="comment-avatar">';
}, 5);

add_action('woocommerce_review_before', function () {
    echo '</div>';
}, 15);

//////////////////////////////
// ENTRY SUMMARY WRAPPER
//////////////////////////////

add_action('woocommerce_single_product_summary', function () {
    echo '<div class="entry-summary-wrapper">';
}, 1);

add_action('woocommerce_single_product_summary', function () {
    echo '</div>';
}, 1000);

//////////////////////////////
// PRODUCT HEADER
//////////////////////////////

add_action('woocommerce_single_product_summary', function () {
    global $product;

    if (!$product) {
        return;
    }

    $product_id = $product->get_id();

    echo '<div class="row align-items-center"><div class="col-12">';

    /**
     * Product badges
     */
    $badges = function_exists('get_field') ? get_field('ath_badges', $product_id) : false;

    if (!empty($badges) && is_array($badges)) {
        echo '<div class="product-badges">';

        foreach ($badges as $badge) {
            $text  = $badge['text'] ?? '';
            $style = $badge['style'] ?? 'orange';

            if ($text) {
                printf(
                    '<span class="ath-badge ath-badge--%s">%s</span>',
                    esc_attr($style),
                    esc_html($text)
                );
            }
        }

        echo '</div>';
    }

    /**
     * Title
     */
    wc_get_template('single-product/title.php');

    /**
     * Subtitle
     */
    $subtitle = function_exists('get_field') ? get_field('ath_subtitle', $product_id) : false;

    if ($subtitle) {
        printf('<p class="product-subtitle">%s</p>', esc_html($subtitle));
    }

    /**
     * Description
     */
    $description = get_the_excerpt($product_id);

    if ($description) {
        echo '<div class="product-description">' . wp_kses_post(wpautop($description)) . '</div>';
    }

    echo '</div></div>';
}, 1);

//////////////////////////////
// PRODUCT META DETAILS
//////////////////////////////

add_action('woocommerce_single_product_summary', function () {
    global $product;

    if (!$product) {
        return;
    }

    $product_id = $product->get_id();

    $fields = [
        'ath_start_datetime' => ['time', 'Time'],
        'ath_location_detail' => ['pin', 'Location'],
        'ath_language' => ['globe', 'Language'],
        'ath_format' => ['selection', 'Format'],
        'ath_age_display' => ['stack', 'Age'],
        'ath_demonstration' => ['users', 'Demonstration'],
    ];

    echo '<div class="product-details-meta">';

    foreach ($fields as $field_key => $icon) {
        $value = get_field($field_key, $product_id);

        if (!$value) {
            continue;
        }

        echo '<div class="product-details-meta__item">';
        echo twmp_get_svg_icon($icon[0]);
        echo '<div><span class="product-details-meta__item-label">' . esc_html($icon[1]) . '</span>: <span class="product-details-meta__item-text">' . esc_html(twmp_field_to_string($value)) . '</span></div>';
        echo '</div>';
    }

    echo '</div>';
}, 15);

//////////////////////////////
// CART BUTTON
//////////////////////////////

add_action('woocommerce_single_product_summary', function () {
    echo '<div class="product-action-buttons d-flex items-center gap-16">';
    twmp_render_cart_button();
    twmp_render_contact_us_button();
    echo '</div>';
}, 16);

//////////////////////////////
// ADD TO CART TEXT
//////////////////////////////

add_filter('woocommerce_product_single_add_to_cart_text', function () {
    return esc_html__('Add to cart', 'twmp-ath');
});

//////////////////////////////
// RELATED PRODUCTS
//////////////////////////////

function twmp_related_products_ids($related_products, $product_id)
{
    $custom_ids = get_field('related_product', $product_id);

    if (!empty($custom_ids)) {
        return $custom_ids;
    }

    $terms = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);

    if (empty($terms)) {
        return $related_products;
    }

    $products = get_posts([
        'posts_per_page' => 5,
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'tax_query'      => [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $terms,
            ]
        ],
        'post__not_in'   => [$product_id],
    ]);

    return wp_list_pluck($products, 'ID');
}

add_filter('woocommerce_related_products', 'twmp_related_products_ids', 10, 2);

// add_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 1);

//////////////////////////////
// AFTER SUMMARY LAYOUT
//////////////////////////////

add_action('woocommerce_after_single_product_summary', function () {
    echo '<div class="woocommerce_after_single_product_summary">';
    // echo '<div class="row">';
    // echo '<div class="col-lg-8 col-md-12 col-sm-12 col-12">';
}, 5);

add_action('woocommerce_after_single_product_summary', function () {
    // echo '</div>';
    // echo '<div class="col-lg-4 col-md-12 col-sm-12 col-12">';
    // echo '<div class="single__content-widgets">';
}, 50);

add_action('woocommerce_after_single_product_summary', function () {
    // echo '</div></div></div></div>';
    echo '</div>';
}, 1000);

add_action('woocommerce_before_single_product', function () {
    echo '<div class="container single-product-container">';
}, 15);

add_action('woocommerce_after_single_product', function () {
    echo '</div>';
}, 100);
