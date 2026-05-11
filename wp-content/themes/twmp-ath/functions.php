<?php

if (!defined('ABSPATH')) {
    exit;
}

if (! defined('TWMP_DIR_PATH')) {
    define('TWMP_DIR_PATH', untrailingslashit(get_theme_file_path()));
}

if (! defined('TWMP_DIR_URI')) {
    define('TWMP_DIR_URI', untrailingslashit(get_theme_file_uri()));
}

if (! defined('TWMP_DIST_URI')) {
    define('TWMP_DIST_URI', untrailingslashit(get_theme_file_uri()) . '/assets');
}

if (! defined('TWMP_DIST_PATH')) {
    define('TWMP_DIST_PATH', untrailingslashit(get_theme_file_path()) . '/assets');
}

if (! defined('TWMP_DIST_JS_URI')) {
    define('TWMP_DIST_JS_URI', untrailingslashit(get_theme_file_uri()) . '/assets/js');
}

if (! defined('TWMP_DIST_JS_DIR_PATH')) {
    define('TWMP_DIST_JS_DIR_PATH', untrailingslashit(get_theme_file_path()) . '/assets/js');
}

if (! defined('TWMP_IMG_URI')) {
    define('TWMP_IMG_URI', untrailingslashit(get_theme_file_uri()) . '/assets/images');
}

if (! defined('TWMP_IMAGES_URI')) {
    define('TWMP_IMAGES_URI', untrailingslashit(get_theme_file_uri()) . '/images');
}

if (! defined('TWMP_DIST_CSS_URI')) {
    define('TWMP_DIST_CSS_URI', untrailingslashit(get_theme_file_uri()) . '/assets/css');
}

if (! defined('TWMP_DIST_CSS_DIR_PATH')) {
    define('TWMP_DIST_CSS_DIR_PATH', untrailingslashit(get_theme_file_path()) . '/assets/css');
}

require_once TWMP_DIR_PATH . '/inc/helpers/utility.php';
require_once TWMP_DIR_PATH . '/inc/helpers/comments.php';
require_once TWMP_DIR_PATH . '/inc/helpers/autoloader.php';
require_once TWMP_DIR_PATH . '/inc/helpers/template-functions.php';
require_once TWMP_DIR_PATH . '/inc/helpers/cafe-menu.php';

function twmp_get_theme_instance()
{
    \TWMP_THEME\Inc\TWMP_THEME::get_instance();
}

twmp_get_theme_instance();

add_filter('rest_pre_serve_request', function ($served, $result, $request, $server) {
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Headers: Content-Type, Authorization, nonce, X-WP-Nonce');
    return $served;
}, 10, 4);

if (is_singular() && comments_open() && get_option('thread_comments')) {
    wp_enqueue_script('comment-reply');
}

add_filter('get_the_archive_title', function ($title) {
    if (is_category() || is_tag() || is_tax()) {
        $title = single_term_title('', false);
    }
    return $title;
});



add_action('save_post', function ($post_id) {
    // Kiểm tra quyền & autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['iframe_custom'])) {
        update_post_meta($post_id, 'iframe_custom', sanitize_text_field($_POST['iframe_custom']));
    }
});

add_action('twmp_before_content_page', function () {
    if (is_page()) {
        global $post;
        $page_id = $post->ID;

        if ($page_id && $hero_sliders = get_field('hero_slider', $page_id)) {
            $items = [];
            foreach ($hero_sliders as $image_id) {
                $items[]['image'] = $image_id;
            }
            get_template_part('templates/blocks/hero-slider', null, [
                'id' => '',
                'class' => 'section hero-slider mb-2',
                'lazyload' => false,
                'items' => $items,
                'enable_container' => false
            ]);
        }
    }
}, 10);

// add_filter('pre_site_transient_update_plugins', '__return_null');

add_filter('term_link', 'devvn_product_cat_permalink', 10, 3);
function devvn_product_cat_permalink($url, $term, $taxonomy)
{
    switch ($taxonomy):
        case 'product_cat':
            $taxonomy_slug = 'product-category'; //Thay bằng slug hiện tại của bạn. Mặc định là product-category
            if (strpos($url, $taxonomy_slug) === FALSE) break;
            $url = str_replace('/' . $taxonomy_slug, '', $url);
            break;
    endswitch;
    return $url;
}
// Add our custom product cat rewrite rules
function devvn_product_category_rewrite_rules($flash = false)
{
    $terms = get_terms(array(
        'taxonomy' => 'product_cat',
        'post_type' => 'product',
        'hide_empty' => false,
    ));
    if ($terms && !is_wp_error($terms)) {
        $siteurl = esc_url(home_url('/'));
        foreach ($terms as $term) {
            $term_slug = $term->slug;
            $baseterm = str_replace($siteurl, '', get_term_link($term->term_id, 'product_cat'));
            add_rewrite_rule($baseterm . '?$', 'index.php?product_cat=' . $term_slug, 'top');
            add_rewrite_rule($baseterm . 'page/([0-9]{1,})/?$', 'index.php?product_cat=' . $term_slug . '&paged=$matches[1]', 'top');
            add_rewrite_rule($baseterm . '(?:feed/)?(feed|rdf|rss|rss2|atom)/?$', 'index.php?product_cat=' . $term_slug . '&feed=$matches[1]', 'top');
        }
    }
    if ($flash == true)
        flush_rewrite_rules(false);
}
add_action('init', 'devvn_product_category_rewrite_rules');

/*Sửa lỗi khi tạo mới taxomony bị 404*/
add_action('create_term', 'devvn_new_product_cat_edit_success', 10, 2);
function devvn_new_product_cat_edit_success($term_id, $taxonomy)
{
    devvn_product_category_rewrite_rules(true);
}

add_filter('wp_img_tag_add_auto_sizes', '__return_false');

// add_action( 'after_setup_theme', function() {
//     remove_theme_support( 'duotone' );
// });

// add_action('wp_enqueue_scripts', function() {
//     wp_dequeue_style('core-block-supports-duotone');
// }, 100);

add_action('template_redirect', function () {
    ob_start('remove_core_inline_css');
});

function remove_core_inline_css($html)
{
    return preg_replace('#<style[^>]*id=[\'"]core-block-supports-inline-css[\'"][^>]*>.*?</style>#si', '', $html);
}

add_action('template_redirect', 'redirect_old_product_category_url');

function redirect_old_product_category_url()
{
    // Lấy URL hiện tại
    $requested_url = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_UNSAFE_RAW);
    $requested_url = is_string($requested_url) ? wp_unslash($requested_url) : '';

    // Kiểm tra nếu URL có chứa /product-category/
    if (strpos($requested_url, '/product-category/') !== false) {
        // Tách slug (vd: /product-category/shoes/ → shoes)
        $slug = str_replace('/product-category/', '', $requested_url);

        // Đảm bảo kết thúc bằng dấu /
        if (substr($slug, -1) !== '/') {
            $slug .= '/';
        }

        // Tạo URL mới
        $new_url = home_url('/' . $slug);

        // Redirect 301
        wp_redirect($new_url, 301);
        exit;
    }
}

function mytheme_enqueue_styles()
{
    wp_enqueue_style(
        'mytheme-style', // handle
        get_stylesheet_uri(), // tự động lấy style.css
        array(), // dependencies
        wp_get_theme()->get('Version') // version để tránh cache
    );
}
add_action('wp_enqueue_scripts', 'mytheme_enqueue_styles');

add_action('init', function () {
    register_block_style(
        'core/paragraph',
        [
            'name'  => 'highlight',
            'label' => __('Highlight', 'twmp-ath'),
        ]
    );
});

add_action('init', function () {
    register_block_pattern(
        'twmp-phonghoa/hero-simple',
        [
            'title'   => __('Hero Simple', 'twmp-ath'),
            'content' => '<!-- wp:heading --><h2>Title</h2><!-- /wp:heading -->',
        ]
    );
});

add_action('woocommerce_order_status_changed', function($order_id, $from, $to, $order) {
    if ($to !== 'processing') {
        return;
    }

    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        // bật WP_DEBUG + WP_DEBUG_LOG để ghi file nếu cần
        error_log("ORDER {$order_id} -> processing but WP_DEBUG not enabled");
    }

    $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $lines = array_map(function($i, $frame){
        $file = isset($frame['file']) ? $frame['file'] : '(unknown)';
        $line = isset($frame['line']) ? $frame['line'] : '';
        $func = isset($frame['function']) ? $frame['function'] : '';
        return sprintf("#%d %s:%s %s()", $i, $file, $line, $func);
    }, array_keys($bt), $bt);

    error_log("ORDER {$order_id} transitioned to processing. from={$from}. Backtrace:\n" . implode("\n", $lines));
}, 20, 4);

add_action('woocommerce_checkout_order_processed', function ($order_id, $posted_data, $order) {
    if (!$order instanceof WC_Order) {
        $order = wc_get_order($order_id);
    }

    error_log('ORDER DEBUG #' . $order_id);
    error_log('total=' . $order->get_total());
    error_log('payment_method=' . $order->get_payment_method());
    error_log('needs_payment=' . ($order->needs_payment() ? 'yes' : 'no'));
}, 5, 3);
