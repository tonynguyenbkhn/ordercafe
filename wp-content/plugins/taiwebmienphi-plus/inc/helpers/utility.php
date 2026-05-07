<?php

if ( !function_exists('twmp_plus_get_template_part') ) {
    function twmp_plus_get_template_part( $slug, $name = null, $args = array() ) {
        $template = '';
        $template_dir = plugin_dir_path( __FILE__ ) . '../../templates/';
        if ( ! empty( $name ) ) {
            $template = $template_dir . "{$slug}-{$name}.php";
        }
        if ( ! file_exists( $template ) ) {
            $template = $template_dir . "{$slug}.php";
        }
        if ( file_exists( $template ) ) {
            if ( ! empty( $args ) ) {
                extract( $args );
            }
    
            require_once( $template );
        }
    }
}