<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Register the "Custom Elements" post type
add_action( 'init', 'custom_elements_register_post_type' );

function custom_elements_register_post_type() {
    $labels = [
        'name'               => 'Custom Elements',
        'singular_name'      => 'Custom Element',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Custom Element',
        'edit_item'          => 'Edit Custom Element',
        'new_item'           => 'New Custom Element',
        'view_item'          => 'View Custom Element',
        'search_items'       => 'Search Custom Elements',
        'not_found'          => 'No Custom Elements found',
        'not_found_in_trash' => 'No Custom Elements found in Trash',
        'all_items'          => 'All Custom Elements',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-layout',
        'supports'           => [ 'title', 'editor' ], // Enable Gutenberg editor
        'show_in_rest'       => true, // This enables Gutenberg Editor
    ];

    register_post_type( 'custom_element', $args );
}

// Thêm cột mới
add_filter( 'manage_custom_element_posts_columns', 'custom_elements_add_shortcode_column' );
function custom_elements_add_shortcode_column( $columns ) {
    $columns['shortcode'] = 'Shortcode';
    return $columns;
}

// Hiển thị nội dung cột
add_action( 'manage_custom_element_posts_custom_column', 'custom_elements_render_shortcode_column', 10, 2 );

function custom_elements_render_shortcode_column( $column, $post_id ) {
    if ( $column === 'shortcode' ) {
        echo '<code>[custom_element id="' . esc_attr( $post_id ) . '"]</code>';
    }
}