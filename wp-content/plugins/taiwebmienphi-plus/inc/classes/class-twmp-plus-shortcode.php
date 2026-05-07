<?php

namespace TWMP_PLUS\Inc;

use TWMP_PLUS\Inc\Traits\Singleton;

class TWMP_PLUS_SHORTCODE
{
    use Singleton;

    protected function __construct()
    {
        $this->setup_hooks();
    }

    protected function setup_hooks()
    {
        add_shortcode('post_published_date', [$this, 'twmp_post_published_date_shortcode']);
        add_shortcode('post_modified_date', [$this, 'twmp_post_modified_date_shortcode']);
    }

    function twmp_post_published_date_shortcode() {
        global $post;
        $published = get_the_date( 'd/m/Y', $post );
        return $published;
    }
    
    
    function twmp_post_modified_date_shortcode() {
        global $post;
        $published = get_the_date( 'd/m/Y', $post );
        $modified  = get_the_modified_date( 'd/m/Y', $post );
    
        if ( $published !== $modified ) {
            return $modified;
        } else {
            return $published;
        }
    }
    
}
