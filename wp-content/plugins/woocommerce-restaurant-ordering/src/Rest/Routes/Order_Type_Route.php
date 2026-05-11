<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Rest\Routes;

use Barn2\Plugin\WC_Restaurant_Ordering\Menu\Menu_Options;
use Barn2\Plugin\WC_Restaurant_Ordering\Menu\Product_Data;
use Barn2\Plugin\WC_Restaurant_Ordering\Menu\Product_Modal;
use Barn2\Plugin\WC_Restaurant_Ordering\Rest\Rest_Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Rest\Base_Route;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Rest\Route;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * This REST route checks the supported order type for a product.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Order_Type_Route extends Base_Route implements Route {

	protected $rest_base = 'order_type';

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
					'callback'            => [ $this, 'check_order_type' ],
					'permission_callback' => '__return_true'
				]
			]
		);
	}

	public function check_order_type( WP_REST_Request $request ) {
		$params   = Rest_Util::validate_product_params( $request->get_params() );

		if ( $params instanceof WP_Error ) {
			$response = [
				'success'       => false,
				'error_message' => Rest_Util::format_errors( $params->get_error_message() )
			];
		} else {
			$product  = $params['product'];
			$response = [
				'success'    => true,
				'order_type' => Product_Data::get_order_type( $product )
			];

			// If order type is lightbox, we fetch the modal data now so client can immediately open modal if required.
			if ( Menu_Options::OT_LIGHTBOX === $response['order_type'] ) {
				$product_modal            = new Product_Modal( $product );
				$response['product_data'] = $product_modal->get_modal_data();
			}
		}

		$rest_response = new WP_REST_Response( $response, 200, [ 'Cache-Control' => 'public, max-age=1800' ] );

		return apply_filters( 'wc_restaurant_ordering_rest_order_type_response', $rest_response, $request );
	}

}
