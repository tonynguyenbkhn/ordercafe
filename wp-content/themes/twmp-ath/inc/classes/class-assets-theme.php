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
		// wp_enqueue_style('twmp-frontend', get_stylesheet_directory_uri() . '/assets/css/frontend.min.css', [], null);
		// wp_enqueue_script('twmp-frontend', get_stylesheet_directory_uri() . '/assets/js/frontend.min.js', [], null);
		// wp_enqueue_script('twmp-woocommerce', get_stylesheet_directory_uri() . '/assets/js/woocommerce.min.js', ['jquery'], $this->theme_version);

		$frontend_css_path = get_stylesheet_directory() . '/assets/css/frontend.css';
		$frontend_js_path  = get_stylesheet_directory() . '/assets/js/frontend.js';
		$frontend_css_ver  = file_exists($frontend_css_path) ? filemtime($frontend_css_path) : $this->theme_version;
		$frontend_js_ver   = file_exists($frontend_js_path) ? filemtime($frontend_js_path) : $this->theme_version;

		wp_enqueue_style('twmp-frontend', get_stylesheet_directory_uri() . '/assets/css/frontend.css', [], $frontend_css_ver);
		wp_enqueue_script('twmp-frontend', get_stylesheet_directory_uri() . '/assets/js/frontend.js', [], $frontend_js_ver, ['strategy' => 'defer']);
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

		$locale_settings = array(
			'woocommerce' => array(
				'checkoutUrl'    => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : '',
				'addToCartUrl'    => function_exists('wc_get_cart_url') ? wc_get_cart_url() : '',
			),
			'rest' => array(
				'url'   => esc_url_raw(rest_url('twmp-ath/v1')),
				'nonce' => wp_create_nonce('wp_rest'),
			),
			'themePath' => get_template_directory_uri(),
			'message' => array(
				'notfound' => esc_html__('No order found.', 'twmp-ath'),
				'error' => esc_html__('System error, please try again.', 'twmp-ath')
			)
		);

		wp_localize_script('twmp-frontend', 'twmpConfig', $locale_settings);

		if (function_exists('is_product') && is_product()) {
			wp_add_inline_style('twmp-frontend', '
.woocommerce-product-gallery .twmp-thumb-nav__viewport {
	max-height: var(--twmp-thumb-nav-max-height, 140px);
	overflow-x: auto;
	overflow-y: hidden;
	scroll-behavior: smooth;
	scrollbar-width: none;
}
.woocommerce-product-gallery .twmp-thumb-nav__viewport::-webkit-scrollbar {
	display: none;
}
.woocommerce-product-gallery .twmp-thumb-nav__controls {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	margin-top: 12px;
	width: calc( 100% - 20px );
	position: absolute;
	padding: 0 10px;
	left: 0;
	bottom: 30px;
}
.woocommerce-product-gallery .twmp-thumb-nav__button {
	align-items: center;
	background: transparent;
	border: 1px solid #ffffff;
	border-radius: 50%;
	color: #ffffff;
	cursor: pointer;
	display: inline-flex;
	font-size: 0;
	font-weight: 600;
	justify-content: center;
	letter-spacing: .04em;
	height: 28px;
	width: 28px;
	padding: 0;
	text-transform: uppercase;
	transition: background-color .2s ease, color .2s ease, border-color .2s ease, opacity .2s ease;
	position: relative;
	flex: 0 0 28px;
}
.woocommerce-product-gallery .twmp-thumb-nav__button:hover:not(:disabled) {
	background: rgba(255, 255, 255, 0.12);
	border-color: #ffffff;
	color: #ffffff;
}
.woocommerce-product-gallery .twmp-thumb-nav__button:disabled {
	cursor: not-allowed;
	opacity: .35;
}
.woocommerce-product-gallery .twmp-thumb-nav__button--prev {
	margin-right: auto;
}
.woocommerce-product-gallery .twmp-thumb-nav__button--next {
	margin-left: auto;
}
.woocommerce-product-gallery .twmp-thumb-nav__button::before {
	border-right: 2px solid currentColor;
	border-top: 2px solid currentColor;
	content: "";
	display: block;
	height: 8px;
	position: absolute;
	top: 50%;
	transform: translateY(-50%) rotate(45deg);
	width: 8px;
}
.woocommerce-product-gallery .twmp-thumb-nav__button--prev::before {
	left: 10px;
	transform: translateY(-50%) rotate(225deg);
}
.woocommerce-product-gallery .twmp-thumb-nav__button--next::before {
	left: 6px;
	transform: translateY(-50%) rotate(45deg);
}
.woocommerce-product-gallery .twmp-thumb-nav__hidden {
	display: none !important;
}
.woocommerce-product-gallery .flex-control-thumbs {
	flex-wrap: nowrap !important;
	justify-content: flex-start !important;
	gap: 16px;
	width: max-content;
}
');

			wp_add_inline_script('twmp-frontend', <<<JS
(function() {
	const state = {
		observer: null,
		raf: 0,
		listenersAttached: false,
		gallery: null,
		viewport: null,
		controls: null,
		thumbs: null,
	};

	const getGallery = function() {
		return document.querySelector('.woocommerce-product-gallery');
	};

	const cleanup = function() {
		if (state.observer) {
			state.observer.disconnect();
			state.observer = null;
		}

		if (state.controls && state.controls.parentNode) {
			state.controls.parentNode.removeChild(state.controls);
		}

		if (state.viewport && state.thumbs && state.thumbs.parentNode === state.viewport) {
			state.viewport.parentNode.insertBefore(state.thumbs, state.viewport);
			state.viewport.parentNode.removeChild(state.viewport);
		}

		state.gallery = null;
		state.viewport = null;
		state.controls = null;
		state.thumbs = null;
	};

	const scheduleBind = function() {
		if (state.raf) {
			return;
		}

		state.raf = window.requestAnimationFrame(function() {
			state.raf = 0;
			bindThumbNav();
		});
	};

	const attachGlobalListeners = function() {
		if (state.listenersAttached) {
			return;
		}

		state.listenersAttached = true;
		window.addEventListener('resize', scheduleBind);
		window.addEventListener('load', scheduleBind);

		if (window.jQuery) {
			window.jQuery(document.body).on(
				'wc-product-gallery-after-init.twmpThumbNav found_variation.twmpThumbNav reset_data.twmpThumbNav updated_wc_div.twmpThumbNav woocommerce_variation_has_changed.twmpThumbNav',
				scheduleBind
			);
		}
	};

	const bindThumbNav = function() {
		const gallery = getGallery();

		if (!gallery) {
			return false;
		}

		const thumbs = gallery.querySelector('.flex-control-thumbs');

		if (!thumbs) {
			return false;
		}

		if (state.thumbs === thumbs && state.controls && state.viewport) {
			return true;
		}

		cleanup();

		state.gallery = gallery;
		state.thumbs = thumbs;

		const viewport = document.createElement('div');
		viewport.className = 'twmp-thumb-nav__viewport';

		thumbs.parentNode.insertBefore(viewport, thumbs);
		viewport.appendChild(thumbs);

		const controls = document.createElement('div');
		controls.className = 'twmp-thumb-nav__controls';
		controls.innerHTML = '<button type="button" class="twmp-thumb-nav__button twmp-thumb-nav__button--prev" aria-label="Previous thumbnails">Prev</button><button type="button" class="twmp-thumb-nav__button twmp-thumb-nav__button--next" aria-label="Next thumbnails">Next</button>';
		viewport.parentNode.insertBefore(controls, viewport);

		state.viewport = viewport;
		state.controls = controls;

		const prevButton = controls.querySelector('.twmp-thumb-nav__button--prev');
		const nextButton = controls.querySelector('.twmp-thumb-nav__button--next');
		const firstThumb = thumbs.querySelector('li');
		const itemsPerPage = 4;

		const calcViewportHeight = function() {
			if (!firstThumb) {
				return 0;
			}

			const thumbRect = firstThumb.getBoundingClientRect();
			return Math.ceil(thumbRect.height);
		};

		const syncState = function() {
			const viewportHeight = calcViewportHeight();

			if (viewportHeight > 0) {
				viewport.style.setProperty('--twmp-thumb-nav-max-height', viewportHeight + 10 + 'px');
			}

			const thumbItems = thumbs.children.length;
			const canScroll = thumbItems > itemsPerPage && viewport.scrollWidth > viewport.clientWidth + 1;
			controls.classList.toggle('twmp-thumb-nav__hidden', !canScroll);

			if (!canScroll) {
				return;
			}

			const currentIndex = getFirstVisibleIndex();
			prevButton.disabled = currentIndex <= 0;
			nextButton.disabled = currentIndex + itemsPerPage >= thumbItems;
		};

		const getFirstVisibleIndex = function() {
			const thumbItems = Array.prototype.slice.call(thumbs.children);
			const scrollLeft = viewport.scrollLeft + 1;

			for (let i = 0; i < thumbItems.length; i++) {
				if (thumbItems[i].offsetLeft >= scrollLeft) {
					return i;
				}
			}

			return Math.max(thumbItems.length - itemsPerPage, 0);
		};

		const scrollToIndex = function(index) {
			const thumbItems = Array.prototype.slice.call(thumbs.children);

			if (!thumbItems.length) {
				return;
			}

			const normalizedIndex = Math.max(0, Math.min(index, Math.max(thumbItems.length - itemsPerPage, 0)));
			const target = thumbItems[normalizedIndex];

			if (!target) {
				return;
			}

			viewport.scrollTo({
				left: target.offsetLeft,
				behavior: 'smooth'
			});
		};

		prevButton.addEventListener('click', function() {
			scrollToIndex(getFirstVisibleIndex() - itemsPerPage);
		});

		nextButton.addEventListener('click', function() {
			scrollToIndex(getFirstVisibleIndex() + itemsPerPage);
		});

		viewport.addEventListener('scroll', syncState, { passive: true });

		state.observer = new MutationObserver(function(mutations) {
			for (let i = 0; i < mutations.length; i++) {
				if (mutations[i].addedNodes.length || mutations[i].removedNodes.length) {
					scheduleBind();
					break;
				}
			}
		});

		state.observer.observe(document.body, {
			childList: true,
			subtree: true
		});

		window.requestAnimationFrame(syncState);
		return true;
	};

	const boot = function() {
		attachGlobalListeners();

		if (!bindThumbNav()) {
			scheduleBind();
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot, { once: true });
	} else {
		boot();
	}
})();
JS);
		}
	}
}
