<?php

namespace TWMP_THEME\Inc;

use TWMP_THEME\Inc\Traits\Singleton;

class Rest_Api_Theme
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
        add_action('rest_api_init', [$this, 'rest_api_filter_theme']);
        add_action('wp_enqueue_scripts', [$this, 'twmp_enqueue_localized_view']);
    }

    function rest_api_filter_theme()
    {
        register_rest_route('twmp/v1', '/filter_theme', array(
            'methods' => 'POST',
            'callback' => array($this, 'twmp_filter_theme'),
            'permission_callback' => '__return_true',
        ));
    }

    public function twmp_filter_theme(\WP_REST_Request $request)
    {
        $category_ids = isset($request['category_id']) && is_numeric($request['category_id']) ? esc_attr($request['category_id']) : null;
        $platform_ids = isset($request['platform_id']) && is_numeric($request['platform_id']) ? esc_attr($request['platform_id']) : null;

        $search       = sanitize_text_field($request['search_theme']);
        $order        = $request['sortOrder'] === 'asc' ? 'ASC' : 'DESC';
        $orderBy        = !empty($request['sortBy'])  ? $request['sortBy'] : 'date';
        $paged        = max(1, (int) $request['page']);
        $per_page     = get_option('posts_per_page') ? get_option('posts_per_page') : 12;

        $args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => $order,
            's'              => $search,
            'tax_query'      => [],
        ];

        if ($orderBy !== 'date') {
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = $orderBy;
        }

        if (!empty($category_ids)) {
            $args['tax_query'][] = [
                'taxonomy' => 'category',
                'field'    => 'term_id',
                'terms'    => $category_ids,
                'operator' => 'IN',
            ];
        }

        if (!empty($platform_ids)) {
            $args['tax_query'][] = [
                'taxonomy' => 'nen-tang',
                'field'    => 'term_id',
                'terms'    => $platform_ids,
                'operator' => 'IN',
            ];
        }

        if (count($args['tax_query']) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }

        $post_query = twmp_get_post_query($args);

        $total = (int) $post_query->found_posts;
        $pages = (int) $post_query->max_num_pages;

        if ($post_query->have_posts()) :

            ob_start();
            echo '<div class="row">';
            while ($post_query->have_posts()) :
                $post_query->the_post();
                get_template_part('templates/blocks/theme-card', null, [
                    'class' => 'col col-lg-4 col-md-6 col-12 theme-card--col',
                    'post_data' => get_post(get_the_ID()),
                    'post_id' => get_the_ID(),
                    'view_more_button' => esc_html__('', 'twmp-ath'),
                ]);
            endwhile;
            echo '</div>';
            if ($pages > 1) {
                $args = array(
                    'total' => $pages,
                    'current' => $paged,
                );
                echo '<div class="pagination">';
                twmp_component_pagi_post($args);
                echo '</div>';
            }
            $html = ob_get_clean();
            wp_reset_postdata();
            $response = new \WP_Rest_Response();
            $data_args = array(
                'html'      => $html,
                'total'     => $total,
                'pages'     => $pages,
                'current'   => $paged,
                'per_page'  => $per_page,
            );

            if (empty($request->get_param('cache'))) :
                $response->set_headers(array(
                    'Cache-Control' => 'max-age=3600' // 1 hour
                ));
                $data_args['cache'] = true;
            else :
                $data_args['cache'] = false;
            endif;

            $response->set_data($data_args);
            $response->set_status(200);

            return $response;

        else :

            return new \WP_Rest_Response(array(
                'errorCode' => 404,
                'errorMessage' => __('There is no theme to display.', 'twmp-ath'),
                'html' => __('There is no theme to display.', 'twmp-ath')
            ), 200);

        endif;
    }

    public function twmp_enqueue_localized_view()
    {
        if (get_page_template_slug() === 'templates/taiwebmienphi.php' || taxonomy_exists('category')) {
            wp_register_script('twmp-filter-theme', false);

            wp_localize_script('twmp-filter-theme', 'filterApi', array(
                'post_id' => get_the_ID(),
                'endpoint' => 'filter_theme',
                'ajax' => array(
                    'restUrl' => get_rest_url(null, 'twmp/v1'),
                    'url' => admin_url('admin-ajax.php'),
                    'ajax_error' => __('Sorry, something went wrong. Please refresh this page and try again!', 'twmp-ath')
                ),
                'themePath' => get_template_directory_uri(),
            ));

            wp_enqueue_script('twmp-filter-theme');
        }
    }
}
