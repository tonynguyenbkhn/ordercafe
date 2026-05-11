<?php

namespace TWMP_THEME\Inc;

use Awf\Download\Download;
use TWMP_THEME\Inc\Traits\Singleton;

class TWMP_THEME
{
	use Singleton;

	protected function __construct()
	{
		Sidebars_Theme::get_instance();
		Menus_Theme::get_instance();
		Assets_Theme::get_instance();
		// Views_Theme::get_instance();
		Admin_Theme::get_instance();
		Woo_Theme::get_instance();
		// Calendar_Theme::get_instance();
		$this->setup_hooks();
	}

	protected function setup_hooks()
	{
		add_action('after_setup_theme', [$this, 'setup_theme']);
		//        add_action('twmp_after_header', function () {
		//			get_template_part('templates/blocks/bg-shape', null, []);
		//		});        add_action('twmp_after_header', function () {
		//			get_template_part('templates/blocks/bg-shape', null, []);
		//		});
		add_filter('intermediate_image_sizes_advanced', [$this, 'remove_default_image_sizes']);
		// add_filter('wp_get_attachment_image_attributes', [$this, 'custom_update_sizes_for_post'], 10, 3);
		// add_action( 'init', [$this, 'twmp_register_patterns'] );
		add_filter('body_class', [$this, 'add_custom_body_class']);
	}

	function add_custom_body_class($classes)
	{
		if (is_page()) {
			$custom_class = get_post_meta(get_the_ID(), 'body_class', true);
			if (!empty($custom_class)) {
				$classes[] = $custom_class;
			}
		}
		return $classes;
	}

	public function setup_theme()
	{
		load_theme_textdomain('twmp-ath', TWMP_DIR_PATH . '/languages');

		// Add theme support for various features.
		add_theme_support('automatic-feed-links');
		add_theme_support('post-thumbnails');
		add_theme_support('title-tag');
		add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'script', 'style'));
		add_theme_support('customize-selective-refresh-widgets');
		add_theme_support('align-wide');
		add_theme_support('responsive-embeds');
		add_theme_support('woocommerce');

		add_theme_support(
			'custom-logo',
			array(
				'height' => 70,
				'width' => 210,
				'flex-height' => true,
				'flex-width' => true,
			)
		);

		global $content_width;
		if (! isset($content_width)) {
			$content_width = 1200;
		}

		add_theme_support('wp-block-styles');

		// Add editor styles to the block editor.
		add_theme_support('editor-styles');

		$editor_styles = apply_filters(
			'generate_editor_styles',
			array(
				'assets/css/admin.css',
			)
		);

		add_editor_style($editor_styles);

		add_theme_support('custom-header', array(
			'width' => 1600,
			'height' => 400,
			'flex-width' => true,
			'flex-height' => true,
		));

		add_theme_support('custom-background');

		// add_theme_support('wc-product-gallery-zoom');
		// add_theme_support('wc-product-gallery-lightbox');
		add_theme_support('wc-product-gallery-slider');
	}

	function custom_update_sizes_for_post($attr, $attachment, $size)
	{
		if (get_post_type() === 'post' && !is_singular('post')) {
			$attr['sizes'] = '(max-width: 320px) 102px, (max-width: 566px) 102px, 300px';
			$attr['loading'] = 'lazy';
		}

		return $attr;
	}

	function remove_default_image_sizes($sizes)
	{
		unset($sizes['1536x1536']);
		unset($sizes['2048x2048']);
		return $sizes;
	}

	// function twmp_register_patterns() {
	// 	register_block_pattern(
	//         'twmp/twmp-pattern',
	//         array(
	//             'title'         => __( 'My First Block Pattern', 'twmp-ath' ),
	//             'description'   => _x( 'This is my first block pattern', 'Block pattern description', 'twmp-ath' ),
	//             'content'       => '<!-- wp:paragraph --><p>A single paragraph block style</p><!-- /wp:paragraph -->',
	//             'categories'    => array( 'text' ),
	//             'keywords'      => array( 'cta', 'demo', 'example' ),
	//             'viewportWidth' => 800,
	//         )
	//     );
	// }

}
