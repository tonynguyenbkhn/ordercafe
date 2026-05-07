<?php

namespace TWMP_THEME\Inc;

use TWMP_THEME\Inc\Traits\Singleton;

class Assets_Theme
{
	use Singleton;

	protected $theme_version;
	protected $theme_env;

	protected function __construct()
	{

		// load class.
		$this->setup_hooks();

		$this->theme_version = WP_DEBUG ? time() : wp_get_theme()->Get('Version');
		$this->theme_env = !twmp_theme_is_localhost() ? '.min' : '';
	}

	protected function setup_hooks()
	{
		add_action('wp_enqueue_scripts', [$this, 'twmp_critical_frontend_assets']);
		add_action('wp_enqueue_scripts', [$this, 'twmp_frontend_assets']);
	}

	public function twmp_critical_frontend_assets()
	{
		$variables_css_context = file_get_contents(get_theme_file_path('variables.css'));
		$bootstrap_css_context = '';
		$critical_css_context = '';

		// $critical_css_context = file_get_contents(get_theme_file_path('assets/css/critical_frontend.min.css'));

		$critical_css_context = file_get_contents(get_theme_file_path('assets/css/critical_frontend.css'));

		if (!empty($variables_css_context)) {
			wp_register_style('twmp-variables', false);
			wp_enqueue_style('twmp-variables', false);
			wp_add_inline_style('twmp-variables', twmp_format_css_variables($variables_css_context . $critical_css_context));
		}
	}

	public function twmp_frontend_assets()
	{
		// wp_enqueue_style('twmp-frontend', get_stylesheet_directory_uri() . '/assets/css/frontend.min.css', [], $this->theme_version);
		// wp_enqueue_style('twmp-frontend', get_stylesheet_directory_uri() . '/assets/css/frontend.min.css', [], $this->theme_version);
		// wp_enqueue_script('twmp-frontend', get_stylesheet_directory_uri() . '/assets/js/frontend.min.js', ['jquery'], $this->theme_version);
		// wp_enqueue_script('twmp-woocommerce', get_stylesheet_directory_uri() . '/assets/js/woocommerce.min.js', ['jquery'], $this->theme_version);

		wp_enqueue_style('twmp-frontend', get_stylesheet_directory_uri() . '/assets/css/frontend.css', [], $this->theme_version);
		wp_enqueue_script('twmp-frontend', get_stylesheet_directory_uri() . '/assets/js/frontend.js', [], $this->theme_version, ['strategy' => 'defer']);
		// wp_enqueue_script('twmp-woocommerce', get_stylesheet_directory_uri() . '/assets/js/woocommerce.js', ['jquery'], $this->theme_version);
		// if (is_shop() || is_product_category()) {
		// 	wp_enqueue_script('twmp-woocommerce-shop', get_stylesheet_directory_uri() . '/custom/shop.js', ['jquery'], $this->theme_version);
		// }
		// if (is_checkout()) {
		// 	wp_enqueue_script('twmp-woocommerce-checkout', get_stylesheet_directory_uri() . '/custom/checkout.js', ['jquery'], $this->theme_version);
		// }
		// if (is_product()) {
		// 	wp_enqueue_script('twmp-woocommerce-product', get_stylesheet_directory_uri() . '/custom/product.js', ['jquery'], $this->theme_version);
		// }

		// Enqueue artists template styles on single product page.

		// $locale_settings = array(
		// 	'woocommerce' => array(
		// 		'checkoutUrl'    => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : '',
		// 		'addToCartUrl'    => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '',
		// 	),
		// 	'ajax' => array(
		// 		'restUrl'    => get_rest_url(null, 'twmp/v1'),
		// 		'url'        => admin_url('admin-ajax.php'),
		// 		'ajax_error' => __('Sorry, something went wrong. Please refresh this page and try again!', 'twmp-ath'),
		// 		'nonce'      => wp_create_nonce('twmp-config-nonce'),
		// 	),
		// 	'themePath' => get_template_directory_uri(),
		// 	'message' => array(
		// 		'notfound' => esc_html__('No order found.', 'twmp-ath'),
		// 		'error' => esc_html__('System error, please try again.', 'twmp-ath')
		// 	)
		// );

		// wp_localize_script('twmp-frontend', 'twmpConfig', $locale_settings);
	}
}
