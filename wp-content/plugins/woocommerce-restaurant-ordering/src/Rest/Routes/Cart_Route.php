<?php

namespace Barn2\Plugin\WC_Restaurant_Ordering\Rest\Routes;

use Barn2\Plugin\WC_Restaurant_Ordering\Rest\Rest_Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Util;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Rest\Base_Route;
use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Rest\Route;
use WC;
use WC_Cart;
use WC_Customer;
use WC_Product;
use WP_Error;
use WP_REST_Server;

/**
 * REST controller for the cart route.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Cart_Route extends Base_Route implements Route {

	protected $rest_base = 'cart';

	protected $cart_quantity = 'cart-quantity';

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				'args' => [
					'product_id' => [
						'type'        => 'integer',
						'required'    => true,
						'minimum'     => 1,
						'description' => __( 'The unique identifier for the product.', 'woocommerce-restaurant-ordering' )
					],
					'quantity'   => [
						'type'        => 'float',
						'required'    => false,
						'description' => __( 'The quantity of the product.', 'woocommerce-restaurant-ordering' )
					],
					'buy_button_style'   => [
						'type'        => 'string',
						'required'    => false,
						'description' => __( 'Buy button style.', 'woocommerce-restaurant-ordering' )
					]
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'add_product' ],
					'permission_callback' => '__return_true'
				]
			]
		);
		
		// register route for getting cart quantity
		register_rest_route(
			$this->namespace,
			'/' . $this->cart_quantity,
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'cart_quantity' ],
				'permission_callback' => '__return_true'
			]
		);
	}

	/**
	 * Cart quantity route callback.
	 */
	public function cart_quantity( $request ) {
		$this->check_prerequisites();
		$product_data = [];

		// Loop through each item in the cart
		foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
			// Access the cart item data
			$product_id = $cart_item['product_id'];
			$quantity = $cart_item['quantity'];
			$product_data[] = [
				'product_id' => $product_id,
				'quantity' => $quantity
			];
		}

		return apply_filters( 'wc_restaurant_ordering_rest_cart_quantity_response', rest_ensure_response( $product_data ), $request );
	}

	public function add_product( $request ) {
		$this->check_prerequisites();

		$response = [ 'success' => false ];
		$params   = $this->validate_add_item_params( $request->get_params() );

		if ( $params instanceof WP_Error ) {
			$response['error_message'] = Rest_Util::format_errors( $params->get_error_message() );
		} else {
			$products_added = $this->add_product_to_cart( $params );

			// when user tries to remove a product or decrease quantity from the cart
			if ( isset( $products_added['product_removed'] ) ) {
				$response = [
					'success'            => true,
					'products'           => $products_added,
					'num_products_added' => count( $products_added ),
					'quantity_removed'   => 1,
					'product_name'       => $this->get_product_names( $products_added['products'], false ),
					'fragments'          => $this->get_add_to_cart_fragments(),
					'cart_hash'          => $this->get_add_to_cart_hash()
				];
			} else if ( $products_added ) {
				$response = [
					'success'            => true,
					'products'           => $products_added,
					'num_products_added' => count( $products_added ),
					'quantity_added'     => array_sum( $products_added ),
					'product_name'       => $this->get_product_names( $products_added, true ),
					'fragments'          => $this->get_add_to_cart_fragments(),
					'cart_hash'          => $this->get_add_to_cart_hash(),
					'sold_individually'  => $this->is_product_sold_individually( $products_added ),
				];
			} else {
				$response['error_message'] = $this->get_error_message();
			}
		}

		return apply_filters( 'wc_restaurant_ordering_rest_add_product_response', rest_ensure_response( $response ), $request );
	}

	protected function add_product_to_cart( array $params ) {
		$params = array_merge(
			[
				'product_id'   => 0,
				'product'      => null,
				'quantity'     => 1,
				'buy_button_style' => 'buyButton',
				'variation_id' => 0,
				'variations'   => []
			],
			$params
		);

		$product = $params['product'];

		if ( ! $product ) {
			$product = Rest_Util::validate_product( $params['product_id'] );
		}

		// Bail if product doesn't exist.
		if ( ! ( $product instanceof WC_Product ) ) {
			return false;
		}

		if ( isset( $params['action'] ) && $params['action'] === 'remove' ) {
			$passed_validation = true;
		} else {
			$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $params['product_id'], $params['quantity'] );
		}

		// Bail if product does not pass add to cart checks.
		if ( ! $passed_validation ) {
			return false;
		}

		$products_added = [];

		if ( $product->is_type( 'variable' ) || $params['buy_button_style'] == 'clickAnywhere' ) {
			if ( WC()->cart->add_to_cart( $params['product_id'], $params['quantity'], $params['variation_id'], $params['variations'] ) ) {
				// Product successfully added.
				$products_added[ $params['product_id'] ] = $params['quantity'];
				do_action( 'woocommerce_ajax_added_to_cart', $params['product_id'] );
			}
		} else if ( $product->is_type( 'grouped' ) ) {
			// For grouped products the quantity is an array of product IDs => quantity, so we add them one at a time.
			$quantity = (array) $params['quantity'];

			if ( ! empty( $quantity ) ) {
				foreach ( $quantity as $linked_product_id => $linked_qty ) {
					$cart_data = [
						'product_id' => $linked_product_id,
						'quantity'   => $linked_qty
					];

					if ( $linked_qty && $this->add_product_to_cart( $cart_data ) ) {
						$products_added[ $linked_product_id ] = $linked_qty;
					}
				}
			} else {
				wc_add_notice( __( 'Please choose the quantity of items you wish to add to your cart&hellip;', 'woocommerce-restaurant-ordering' ), 'error' );
			}
		} else {
			do_action( 'wc_restaurant_ordering_before_add_to_cart', $params );

			// If the cart is empty, the add the product
			if ( empty( WC()->cart->get_cart() ) ) {
				if ( WC()->cart->add_to_cart( $params['product_id'], $params['quantity'], $params['variation_id'], $params['variations'] ) ) {
					// Product successfully added.
					$products_added[ $params['product_id'] ] = $params['quantity'];
					do_action( 'woocommerce_ajax_added_to_cart', $params['product_id'] );
				}
			} else {
				$product_id = $params['product_id'];
				$cart_item_data = (array) apply_filters( 'woocommerce_add_cart_item_data', [] , $product_id, $params['variation_id'], $params['quantity'] );
				$product_cart_id = WC()->cart->generate_cart_id( $product_id, $params['variation_id'], $params['variations'], $cart_item_data );
				// if product found on the cart, update the quantity
				if( WC()->cart->find_product_in_cart( $product_cart_id ) ) {

					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						// TODO: This doesn't work in all the situations.
						// Another function should be created and check this based on the type of product
						if ( $cart_item["product_id"] === $product_id ) {
							if( isset( $params['action'] ) && $params['action'] === 'remove' ) {
								$quantity = 0;
							} else {
								$quantity = $params['quantity'];
							}
							WC()->cart->set_quantity( $cart_item_key, $quantity );
							// set the notification message
							if ( isset( $params['action'] ) && $params['action'] === 'remove' ) {
								$products_added[ 'products' ] = [
									$params['product_id'] => 1
								];
								$products_added[ 'product_removed' ] = $params['product_id'];
							} else {
								$products_added[ $params['product_id'] ] = $params['quantity'];
							}
						}
					}
				} else {
					if ( WC()->cart->add_to_cart( $params['product_id'], $params['quantity'], $params['variation_id'], $params['variations'] ) ) {
						// Product successfully added.
						$products_added[ $params['product_id'] ] = $params['quantity'];
						do_action( 'woocommerce_ajax_added_to_cart', $params['product_id'] );
					}
				}			
			}

			do_action( 'wc_restaurant_ordering_after_add_to_cart', $params );
		}

		return ! empty( $products_added ) ? $products_added : false;
	}

	/**
	 * Check any prerequisites required for our add to cart request.
	 */
	protected function check_prerequisites() {
		if ( defined( 'WC_ABSPATH' ) ) {
			// WC 3.6+ - Cart and notice functions are not included during a REST request.
			include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
			include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
			include_once WC_ABSPATH . 'includes/wc-template-hooks.php';
		}

		if ( null === WC()->session ) {
			$session_class = apply_filters( 'woocommerce_session_handler', '\WC_Session_Handler' );

			WC()->session = new $session_class();
			WC()->session->init();
		}

		if ( null === WC()->customer ) {
			WC()->customer = new WC_Customer( get_current_user_id(), true );
		}

		if ( null === WC()->cart ) {
			WC()->cart = new WC_Cart();

			// We need to force a refresh of the cart contents from session here (cart contents are normally refreshed on wp_loaded, which has already happened by this point).
			WC()->cart->get_cart();
		}
	}

	protected function filter_quantity( $qty ) {
		return $qty ? ( wc_stock_amount( abs( $qty ) ) ) : 0;
	}

	protected function get_add_to_cart_fragments() {
		ob_start();
		woocommerce_mini_cart();
		$mini_cart = ob_get_clean();

		return apply_filters(
			'woocommerce_add_to_cart_fragments',
			[ 'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>' ]
		);
	}

	protected function get_add_to_cart_hash() {
		$cart = WC()->cart->get_cart_for_session();

		return apply_filters( 'woocommerce_add_to_cart_hash', $cart ? md5( json_encode( $cart ) ) : '', $cart );
	}

	/**
	 * Check if the product is sold individually.
	 * 
	 * @param array $params The request params.
	 * 
	 * @return bool
	 */
	protected function is_product_sold_individually( $params ) {
		$product_ids = array_keys( $params );
		$first_product_id = $product_ids[ 0 ];
		
		$product = wc_get_product( $first_product_id );
		return $product->is_sold_individually();
	}

	protected function get_error_message() {
		// Error adding to cart - get error notices from WC.
		return Rest_Util::format_errors( Util::get_wc_notices( 'error' ) );
	}

	/**
	 * Get the list of product names from the passed array of products [ product ID => quantity ].
	 *
	 * @param array $products An array of products IDs mapped to the quantity added.
	 * @return string The formatted product names.
	 */
	protected function get_product_names( $products, $include_quantity = false, $name_in_quotes = true ) {
		$names = [];

		$include_quantity = apply_filters( 'wc_restaurat_ordering_cart_message_include_quantities', $include_quantity );
		$name_in_quotes   = apply_filters( 'wc_restaurat_ordering_cart_message_product_name_in_quotes', $name_in_quotes );

		/* translators: %s: product quantity */
		$qty_format = _x( '%s &times; ', 'Quantity prefix for cart message', 'woocommerce-restaurant-ordering' );

		/* translators: %s: product name */
		$name_format = _x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'woocommerce-restaurant-ordering' );

		foreach ( $products as $product_id => $qty ) {
			$qty_prefix     = ( $include_quantity && $qty > 1 ) ? sprintf( $qty_format, abs( $qty ) ) : '';
			$product_name   = strip_tags( get_the_title( $product_id ) );
			$formatted_name = $name_in_quotes ? sprintf( $name_format, $product_name ) : $product_name;

			$names[] = $qty_prefix . $formatted_name;
		}

		return esc_html( wc_format_list_of_items( $names ) );
	}

	protected function get_submitted_variations( $params, WC_Product $product ) {
		if ( empty( $params )
			|| ! $product->is_type( [ 'variable', 'variation' ] )
			|| ! method_exists( $product, 'get_variation_attributes' ) ) {
			return false;
		}

		$attributes = $product->get_variation_attributes();

		// Attributes for 'variable' products are not prefixed 'attribute_', so we do this to make it consistent with 'variation' products.
		if ( $product->is_type( 'variable' ) ) {
			$variable_attributes = [];

			foreach ( $attributes as $key => $value ) {
				$variable_attributes[ 'attribute_' . sanitize_title( $key ) ] = $value;
			}

			$attributes = $variable_attributes;
		}

		// Return the params which are valid variations.
		return array_intersect_key( $params, $attributes );
	}

	/**
	 * Validate the params for this REST request.
	 *
	 * @param array $params The params to validate.
	 * @return WP_Error|array - The validated params or a WP_Error if the product is not valid.
	 */
	protected function validate_add_item_params( array $params ) {
		$params = array_merge(
			[
				'product_id'   => 0,
				'quantity'     => 1,
				'variation_id' => 0
			],
			$params
		);

		$params = Rest_Util::validate_product_params( $params );

		if ( $params instanceof WP_Error ) {
			return $params;
		}

		$product    = $params['product'];
		$product_id = $product->get_id();

		if ( 'grouped' === $product->get_type() ) {
			// For grouped products, quantity is passed as an array in the form array( product_id => quantity ).
			$quantity = array_map( [ $this, 'filter_quantity' ], (array) $params['quantity'] );
		} else {
			$quantity = $this->filter_quantity( $params['quantity'] );
		}

		if ( ! $quantity ) {
			return new WP_Error(
				'rest_invalid_quantity',
				esc_html__( 'The quantity must be greater than 0.', 'woocommerce-restaurant-ordering' ),
				[ 'status' => 400 ]
			);
		}

		$variation_id = absint( $params['variation_id'] );

		if ( $product->is_type( 'variation' ) ) {
			$variation_id = $product_id;
			$product_id   = $product->get_parent_id();
		} elseif ( $product->is_type( 'variable' ) && ! $variation_id ) {
			return new WP_Error(
				'rest_invalid_variation_id',
				esc_html__( 'A variation ID must be specified for variable products.', 'woocommerce-restaurant-ordering' ),
				[ 'status' => 400 ]
			);
		}

		$variations = [];

		if ( $variation_id ) {
			$variations = $this->get_submitted_variations( $params, $product );

			if ( ! $variations ) {
				return new WP_Error(
					'rest_invalid_variations',
					esc_html__( 'At least one variation attribute must be specified for variable products.', 'woocommerce-restaurant-ordering' ),
					[ 'status' => 400 ]
				);
			}
		}

		if ( ! Util::is_accepting_orders() ) {
			return new WP_Error( 'rest_restaurant_not_accepting_orders', Util::get_restaurant_closed_error_message() );
		}

		$params = array_merge(
			$params,
			[
				'product_id'   => $product_id,
				'product'      => $product,
				'quantity'     => $quantity,
				'variation_id' => $variation_id,
				'variations'   => $variations
			]
		);

		return apply_filters( 'wc_restaurant_ordering_rest_validate_cart_params', $params, $product );
	}

}
