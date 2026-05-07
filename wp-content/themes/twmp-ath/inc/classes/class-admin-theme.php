<?php

namespace TWMP_THEME\Inc;

use TWMP_THEME\Inc\Traits\Singleton;

class Admin_Theme
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

        /**
         * Actions
         */
        add_filter('manage_edit-product_columns', [$this, 'add_product_columns'], 20);
        add_action('manage_product_posts_custom_column', [$this, 'render_product_columns'], 10, 2);
    }

    /**
     * Add custom product list columns.
     *
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function add_product_columns($columns)
    {
        $updated_columns = [];

        foreach ($columns as $key => $label) {
            $updated_columns[$key] = $label;

            if ('date' === $key) {
                $updated_columns['ath_start_datetime'] = esc_html__('Start Date Time', 'twmp-ath');
                $updated_columns['ath_end_datetime']   = esc_html__('End Date Time', 'twmp-ath');
            }
        }

        if (!isset($updated_columns['ath_start_datetime'])) {
            $updated_columns['ath_start_datetime'] = esc_html__('Start Date Time', 'twmp-ath');
        }

        if (!isset($updated_columns['ath_end_datetime'])) {
            $updated_columns['ath_end_datetime'] = esc_html__('End Date Time', 'twmp-ath');
        }

        return $updated_columns;
    }

    /**
     * Render custom product list columns.
     *
     * @param string $column
     * @param int    $post_id
     * @return void
     */
    public function render_product_columns($column, $post_id)
    {
        if (!in_array($column, ['ath_start_datetime', 'ath_end_datetime'], true)) {
            return;
        }

        $field_value = function_exists('get_field') ? get_field($column, $post_id) : get_post_meta($post_id, $column, true);

        if (empty($field_value)) {
            echo '&ndash;';
            return;
        }

        $timestamp = strtotime((string) $field_value);

        if (!$timestamp) {
            echo esc_html((string) $field_value);
            return;
        }

        echo esc_html(wp_date('d/m/Y H:i', $timestamp));
    }
}
