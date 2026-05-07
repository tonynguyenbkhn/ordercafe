<?php
/**
 * Plugin Name: Custom Elements Plugin
 * Description: Create custom elements using Gutenberg and hook them into your theme.
 * Version: 1.0
 * Author: Nguyen Van Viet
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Register Custom Post Type
require_once plugin_dir_path( __FILE__ ) . 'includes/custom-post-type.php';

// Meta Box for Hook Settings
require_once plugin_dir_path( __FILE__ ) . 'includes/meta-box.php';

// Render Gutenberg Content on Hooks
require_once plugin_dir_path( __FILE__ ) . 'includes/render-hooks.php';
