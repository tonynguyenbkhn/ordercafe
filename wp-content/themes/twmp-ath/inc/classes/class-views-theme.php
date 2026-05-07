<?php

namespace TWMP_THEME\Inc;

use TWMP_THEME\Inc\Traits\Singleton;

class Views_Theme
{

    use Singleton;

    /**
     * Construct method.
     */
    protected function __construct()
    {
        $this->setup_hooks();
    }

    /**
     * To register action/filter.
     *
     * @return void
     */
    protected function setup_hooks()
    {
        add_action('rest_api_init', [$this, 'rest_api_update_post_views']);
        add_action('reset_post_views_weekly', array($this, 'reset_post_views_week_callback'));

        add_filter('manage_post_posts_columns', [$this, 'twmp_columns_head']);
        add_action('manage_post_posts_custom_column', [$this, 'twmp_columns_content'], 10, 2);
        add_filter('manage_edit-post_sortable_columns', [$this, 'twmp_sortable_columns']);

        add_filter('manage_tin-tuc_posts_columns', [$this, 'twmp_columns_head']);
        add_action('manage_tin-tuc_posts_custom_column', [$this, 'twmp_columns_content'], 10, 2);
        add_filter('manage_edit-tin-tuc_sortable_columns', [$this, 'twmp_sortable_columns']);

        // add_filter('manage_article_posts_columns', [$this, 'twmp_columns_head']);
        // add_action('manage_article_posts_custom_column', [$this, 'twmp_columns_content'], 10, 2);
        // add_filter('manage_edit-article_sortable_columns', [$this, 'twmp_sortable_columns']);

        add_action('pre_get_posts', [$this, 'twmp_sort_posts']);
        add_action('wp_enqueue_scripts', [$this, 'twmp_enqueue_localized_view']);
    }

    function rest_api_update_post_views()
    {
        register_rest_route('twmp/v1', '/update_post_views', array(
            'methods' => 'POST',
            'callback' => array($this, 'twmp_update_post_views'),
            'permission_callback' => array($this, 'twmp_check_post_views_permission'),
        ));
    }

    public function twmp_check_post_views_permission(\WP_REST_Request $request)
    {
        // Rate limiting: max 10 requests per minute per IP
        $client_ip = $this->twmp_get_client_ip();
        $rate_limit_key = 'twmp_post_views_rate_' . md5($client_ip);
        $request_count = (int) get_transient($rate_limit_key);

        if ($request_count >= 10) {
            return new \WP_Error(
                'twmp_rate_limit_exceeded',
                'Too many requests. Maximum 10 requests per minute allowed.',
                array('status' => 429)
            );
        }

        // Increment request count
        if ($request_count === 0) {
            set_transient($rate_limit_key, 1, 60); // 60 seconds = 1 minute
        } else {
            set_transient($rate_limit_key, $request_count + 1, 60);
        }

        return true;
    }

    private function twmp_get_client_ip()
    {
        // Get real client IP even behind proxy
        $cf_ip = filter_input(INPUT_SERVER, 'HTTP_CF_CONNECTING_IP', FILTER_UNSAFE_RAW);
        if (is_string($cf_ip)) {
            $cf_ip = sanitize_text_field($cf_ip);
            if (filter_var($cf_ip, FILTER_VALIDATE_IP)) {
                return $cf_ip;
            }
        }

        $forwarded_for = filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR', FILTER_UNSAFE_RAW);
        if (is_string($forwarded_for)) {
            $ips = explode(',', sanitize_text_field($forwarded_for));
            $client_ip = trim($ips[0]);

            if (filter_var($client_ip, FILTER_VALIDATE_IP)) {
                return $client_ip;
            }
        }

        $remote_addr = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_UNSAFE_RAW);
        if (is_string($remote_addr)) {
            $remote_addr = sanitize_text_field($remote_addr);
            if (filter_var($remote_addr, FILTER_VALIDATE_IP)) {
                return $remote_addr;
            }
        }

        return 'unknown';
    }

    public function twmp_update_post_views(\WP_REST_Request $request)
    {
        $post_id = (int) $request->get_param('post_id');

        // Validate post_id exists and is published
        if (empty($post_id) || !get_post($post_id)) {
            return new \WP_Error(
                'twmp_invalid_post',
                'Invalid or non-existent post ID.',
                array('status' => 404)
            );
        }

        $post = get_post($post_id);
        if ($post->post_status !== 'publish') {
            return new \WP_Error(
                'twmp_post_not_published',
                'Post is not published.',
                array('status' => 403)
            );
        }

        // Cập nhật tổng số lượt xem
        $count = (int) get_post_meta($post_id, 'post_views', true);
        $count++;
        update_post_meta($post_id, 'post_views', $count);

        // Cập nhật số lượt xem trong tuần
        $count_week = (int) get_post_meta($post_id, 'post_views_week', true);
        $count_week++;
        update_post_meta($post_id, 'post_views_week', $count_week);

        return new \WP_REST_Response(array(
            'post_views' => $count,
            'post_views_week' => $count_week
        ), 200);
    }

    function reset_post_views_week_callback()
    {
        global $wpdb;
        $meta_key = 'post_views_week';
        $reset_value = 0;

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $wpdb->postmeta SET meta_value = %d WHERE meta_key = %s",
                $reset_value,
                $meta_key
            )
        );
    }

    function twmp_columns_head($cols)
    {

        $cols = array_merge(
            array_slice($cols, 0, 1, true),
            array('viewsycol' => __('View Y', 'twmp-ath')),
            array_slice($cols, 1, null, true)
        );

        $cols = array_merge(
            array_slice($cols, 0, 1, true),
            array('viewswcol' => __('View W', 'twmp-ath')),
            array_slice($cols, 1, null, true)
        );

        $cols = array_merge(
            array_slice($cols, 0, 1, true),
            array('thumbnail' => __('Image', 'twmp-ath')),
            array_slice($cols, 1, null, true)
        );
        return $cols;
    }

    function twmp_columns_content($col_name, $post_ID)
    {
        if ($col_name == 'thumbnail') {
            $image = wp_get_attachment_image_src(get_post_thumbnail_id($post_ID), 'thumbnail');
            if ($image) {
                echo '<a href="' . get_edit_post_link($post_ID) . '"><img width="70px" src="' . $image['0'] . '"/></a>';
            } else {
                echo '—';
            }
            echo '<style type="text/css">';
            echo '.column-thumbnail, .column-post_views--qef-type-number-- { width:70px; }';
            echo '.column-categories, .column-tags { width:10% !important; }';
            echo '.column-viewswcol, .column-viewsycol{ width:100px; }';
            echo '</style>';
        } elseif ($col_name == 'viewswcol') {
            $post_views_week = get_post_meta($post_ID, 'post_views_week', true);
            if (!$post_views_week || $post_views_week < 1) $post_views_week = 0;
            echo esc_html( $post_views_week );
        } elseif ($col_name == 'viewsycol') {
            $post_views = get_post_meta($post_ID, 'post_views', true);
            if (!$post_views || $post_views < 1) $post_views = 0;
            echo esc_html( $post_views );
        }
    }

    function twmp_sortable_columns($columns)
    {
        $columns['viewswcol'] = 'post_views_week';
        $columns['viewsycol'] = 'post_views';
        return $columns;
    }

    function twmp_sort_posts($query)
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ('post_views_week' === $query->get('orderby') || 'post_views' === $query->get('orderby') || 'rating_count' === $query->get('orderby')) {
            $query->set('meta_key', $query->get('orderby'));
            $query->set('orderby', 'meta_value_num');
        }
    }

    public function twmp_enqueue_localized_view()
    {
        if (is_single()) {
            wp_register_script('twmp-views', false);

            wp_localize_script('twmp-views', 'apiviews', array(
                'post_id' => get_the_ID(),
                'endpoint' => 'update_post_views',
                'ajax' => array(
                    'restUrl' => get_rest_url(null, 'twmp/v1'),
                    'url' => admin_url('admin-ajax.php'),
                    'ajax_error' => __('Sorry, something went wrong. Please refresh this page and try again!', 'twmp-ath')
                ),
                'themePath' => get_template_directory_uri(),
            ));

            wp_enqueue_script('twmp-views');
        }
    }
}
