<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Rest\Routes;

use Barn2\Plugin\WC_Restaurant_Ordering\Menu\Product_Modal;
use Barn2\Plugin\WC_Restaurant_Ordering\Rest\Rest_Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Rest\Base_Route;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Rest\Route;
use DateTimeImmutable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST handler for the product modal route.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Modal_Route extends Base_Route implements Route {

	protected $rest_base = 'modal';

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<product_id>\d+)',
			[
				'args' => [
					'product_id' => [
						'type'        => 'integer',
						'required'    => true,
						'minimum'     => 1,
						'description' => __( 'The unique indentifier for the product.', 'woocommerce-restaurant-ordering' )
					]
				],
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_modal' ],
					'permission_callback' => '__return_true'
				]
			]
		);
	}

	public function get_modal( WP_REST_Request $request ) {
		$this->check_prerequisites();
		$params = Rest_Util::validate_product_params( $request->get_params() );

		if ( $params instanceof WP_Error ) {
			$response = [
				'success'       => false,
				'error_message' => Rest_Util::format_errors( $params->get_error_message() )
			];
		} else {
			$product_modal = new Product_Modal( $params['product'] );
			$response      = [
				'success'      => true,
				'product_data' => $product_modal->get_modal_data()
			];
		}

		$rest_response = new WP_REST_Response( $response, 200, $this->get_response_headers() );

		return apply_filters( 'wc_restaurant_ordering_rest_product_modal_response', $rest_response, $request );
	}

	private function check_prerequisites() {
		if ( defined( 'WC_ABSPATH' ) ) {
			// WC 3.6+ - Template hooks are not included during a REST request.
			include_once WC_ABSPATH . 'includes/wc-template-hooks.php';
		}

		// Add WPBakery Page Builder (Visual Composer) compatibility.
		// Register all WPBakery shortcodes before processing content.
		if ( is_callable( [ 'WPBMap', 'addAllMappedShortcodes' ] ) ) {
			\WPBMap::addAllMappedShortcodes();
		}

		// Enqueue WPBakery frontend assets.
		if ( function_exists( 'vc_asset_url' ) ) {
			wp_enqueue_style( 'js_composer_front' );
		}

		// Trigger Visual Composer frontend editor to enqueue its assets.
		if ( class_exists( 'Vc_Base' ) && method_exists( 'Vc_Base', 'frontCss' ) ) {
			add_action( 'wp_enqueue_scripts', [ \Vc_Base::class, 'frontCss' ] );
		}
	}

	private function get_response_headers() {
		$headers         = [];
		$max_age_seconds = 1800;
		$now             = new DateTimeImmutable( 'now', Util::get_timezone() );
		$opening_hours   = Util::get_opening_hours();

		if ( $opening_hours->is_valid() ) {
			$next_open_or_close_in_seconds = null;

			if ( $opening_hours->is_open() ) {
				if ( $close_time = $opening_hours->next_closing_time() ) {
					$next_open_or_close_in_seconds = $close_time->getTimestamp() - $now->getTimestamp();
				}
			} else {
				if ( $open_time = $opening_hours->next_opening_time() ) {
					$next_open_or_close_in_seconds = $open_time->getTimestamp() - $now->getTimestamp();
				}
			}

			if ( is_int( $next_open_or_close_in_seconds ) ) {
				if ( $next_open_or_close_in_seconds < 60 ) {
					// Store change in less than a minute, don't cache.
					$max_age_seconds = 0;
				} elseif ( $next_open_or_close_in_seconds < 300 ) {
					// Store change in less than 5 minutes.
					$max_age_seconds = 20;
				} elseif ( $next_open_or_close_in_seconds < 600 ) {
					// Store change in less than 10 minutes.
					$max_age_seconds = 30;
				} elseif ( $next_open_or_close_in_seconds < 1800 ) {
					// Store change in less than 30 minutes.
					$max_age_seconds = 120;
				} elseif ( $next_open_or_close_in_seconds < 3600 ) {
					// Store change in less than 60 minutes.
					$max_age_seconds = 240;
				}

				$max_age_seconds = apply_filters( 'wc_restaurant_ordering_modal_cache_time_adjusted', $max_age_seconds, $next_open_or_close_in_seconds, $opening_hours );
			}
		}

		$max_age_seconds          = apply_filters( 'wc_restaurant_ordering_modal_cache_time', $max_age_seconds, $opening_hours );
		$headers['Cache-Control'] = sprintf( 'public, max-age=%s', $max_age_seconds );

		/**
		 * Filter the response headers for the product modal route.
		 * 
		 * @param array $headers The response headers.
		 * @return array The response headers.
		 */
		return apply_filters( 'wc_restaurant_ordering_ordering_modal_response_headers', $headers );

	}

}
