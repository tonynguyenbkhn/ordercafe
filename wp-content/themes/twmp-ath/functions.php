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
require_once TWMP_DIR_PATH . '/inc/helpers/staff-orders.php';

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

add_filter('show_admin_bar', '__return_false');

function twmp_current_user_has_role($roles)
{
    $user = wp_get_current_user();

    if (!$user instanceof WP_User) {
        return false;
    }

    return (bool) array_intersect((array) $roles, (array) $user->roles);
}

function twmp_current_user_can_access_private_pages()
{
    return twmp_current_user_has_role(array('administrator', 'shop_manager'));
}

function twmp_render_access_notice($args = array())
{
    $defaults = array(
        'type'        => 'denied',
        'eyebrow'     => __('Khu vực nội bộ', 'twmp-ath'),
        'title'       => __('Bạn không có quyền truy cập', 'twmp-ath'),
        'message'     => __('Tài khoản của bạn chưa được cấp quyền phù hợp hoặc chưa được gán chi nhánh.', 'twmp-ath'),
        'action_url'  => '',
        'action_text' => '',
    );
    $args = wp_parse_args($args, $defaults);
    $classes = 'twmp-access-notice twmp-access-notice--' . sanitize_html_class($args['type']);

    ob_start();
    ?>
    <section class="<?php echo esc_attr($classes); ?>">
        <div class="twmp-access-notice__icon" aria-hidden="true"><span></span></div>
        <div class="twmp-access-notice__body">
            <p class="twmp-access-notice__eyebrow"><?php echo esc_html($args['eyebrow']); ?></p>
            <h2 class="twmp-access-notice__title"><?php echo esc_html($args['title']); ?></h2>
            <p class="twmp-access-notice__message"><?php echo esc_html($args['message']); ?></p>
            <?php if (!empty($args['action_url']) && !empty($args['action_text'])) : ?>
                <a class="twmp-access-notice__action" href="<?php echo esc_url($args['action_url']); ?>">
                    <?php echo esc_html($args['action_text']); ?>
                </a>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax() || is_user_logged_in() || !is_page()) {
        return;
    }

    wp_safe_redirect(wp_login_url(get_permalink()));
    exit;
}, 1);

add_filter('the_content', function ($content) {
    if (is_admin() || !is_page() || !is_main_query() || !in_the_loop() || !is_user_logged_in()) {
        return $content;
    }

    if (twmp_current_user_can_access_private_pages()) {
        return $content;
    }

    return twmp_render_access_notice(array(
        'title'   => __('Bạn không có quyền truy cập', 'twmp-ath'),
        'message' => __('Tài khoản của bạn không thuộc nhóm được phép xem nội dung nội bộ. Vui lòng liên hệ quản trị viên nếu cần cấp quyền.', 'twmp-ath'),
    ));
}, 5);

add_action('wp_enqueue_scripts', function () {
    $css = '.twmp-access-notice{align-items:flex-start;background:#fff;border:1px solid #dbe3e8;border-radius:8px;box-shadow:0 14px 38px rgba(16,24,40,.08);color:#17202a;display:flex;gap:16px;margin:24px auto;max-width:720px;padding:22px}.twmp-access-notice__icon{align-items:center;background:#fff4ec;border-radius:8px;color:#ef6f1a;display:inline-flex;flex:0 0 44px;height:44px;justify-content:center;width:44px}.twmp-access-notice__icon span{border:2px solid currentColor;border-radius:999px;display:block;height:18px;position:relative;width:18px}.twmp-access-notice__icon span:after{background:currentColor;content:"";height:8px;left:50%;position:absolute;top:7px;transform:translateX(-50%);width:2px}.twmp-access-notice__eyebrow{color:#ef6f1a;font-size:12px;font-weight:800;letter-spacing:.04em;margin:0 0 5px;text-transform:uppercase}.twmp-access-notice__title{color:#111827;font-size:22px;font-weight:700;line-height:1.25;margin:0 0 8px}.twmp-access-notice__message{color:#52616d;font-size:15px;line-height:1.55;margin:0}.twmp-access-notice__action{align-items:center;background:#17202a;border-radius:6px;color:#fff;display:inline-flex;font-size:14px;font-weight:700;justify-content:center;margin-top:16px;min-height:40px;padding:0 14px;text-decoration:none}.twmp-access-notice__action:hover{background:#ef6f1a;color:#fff}@media (max-width:575px){.twmp-access-notice{flex-direction:column;margin:16px 0;padding:18px}}';

    wp_add_inline_style('mytheme-style', $css);
}, 20);

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

add_action('woocommerce_checkout_create_order', function ($order, $data) {
    if (!$order instanceof WC_Order) {
        return;
    }

    if (is_admin() && !wp_doing_ajax()) {
        return;
    }

    if ($order->get_status() !== 'processing') {
        $order->set_status('processing');
    }
}, 20, 2);
