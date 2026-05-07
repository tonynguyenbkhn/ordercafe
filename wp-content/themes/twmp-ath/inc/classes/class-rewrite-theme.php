<?php

namespace TWMP_THEME\Inc;

use TWMP_THEME\Inc\Traits\Singleton;

class Rewrite_Theme {

    use Singleton;

    protected function __construct() {
        $this->setup_hooks();
    }

    protected function setup_hooks() {
//        add_action('init', array($this, 'custom_remove_category_base'));
//
//        add_action('created_category', array($this, 'no_category_base_refresh_rules'));
//        add_action('edited_category', array($this, 'no_category_base_refresh_rules'));
//        add_action('delete_category', array($this, 'no_category_base_refresh_rules'));
//        add_filter('term_link', array($this, 'filter_category_link'), 10, 3);
    }

    function custom_remove_category_base() {
        // Đăng ký lại các quy tắc rewrite
        add_rewrite_rule('^([^/]+)/?$', 'index.php?category_name=$matches[1]', 'top');
        add_rewrite_rule('^([^/]+)/page/([0-9]{1,})/?$', 'index.php?category_name=$matches[1]&paged=$matches[2]', 'top');
    }

    function no_category_base_refresh_rules() {
        // Tạo lại các quy tắc rewrite
        flush_rewrite_rules();
    }

    function filter_category_link($termlink, $term, $taxonomy) {
        if ($taxonomy !== 'category') {
            return $termlink;
        }
        $termlink = str_replace('/category', '', $termlink);
        return $termlink;
    }
}
