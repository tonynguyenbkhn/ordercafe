<?php
namespace Barn2\Plugin\WC_Restaurant_Ordering\Rest;

use Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Registerable;
use	Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Service\Premium_Service;
use	Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Rest\Rest_Server;
use	Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Rest\Base_Server;
use	Barn2\Plugin\WC_Restaurant_Ordering\Dependencies\Lib\Rest\Route;

/**
 * Main controller which registers the REST routes for the plugin.
 *
 * @package   Barn2\woocommerce-restaurant-ordering
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Rest_Controller extends Base_Server implements Registerable, Premium_Service, Rest_Server {

	const NAMESPACE = 'wc-restaurant-ordering/v1';

	/**
	 * @var Route[] The list of REST route objects handled by this server.
	 */
	private $routes = [];

	public function __construct() {
		$this->routes = [
			new Routes\Cart_Route( self::NAMESPACE ),
			new Routes\Order_Type_Route( self::NAMESPACE ),
			new Routes\Product_Modal_Route( self::NAMESPACE )
		];
	}

	public function register() {
		parent::register();

		add_filter( 'rest_authentication_errors', [ $this, 'rest_authentication_check_nonce_valid' ] );
	}

	public function get_namespace() {
		return self::NAMESPACE;
	}

	public function get_routes() {
		return $this->routes;
	}

	public function rest_authentication_check_nonce_valid( $errors ) {
		// Bail if rest_route isn't defined (shouldn't happen!)
		if ( empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			return $errors;
		}

		$route = ltrim( $GLOBALS['wp']->query_vars['rest_route'], '/' );

		// Ensure we're dealing with a Restaurant Ordering request.
		if ( 0 !== strpos( $route, self::NAMESPACE ) ) {
			return $errors;
		}

		if ( ! empty( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
			$nonce = $_SERVER['HTTP_X_WP_NONCE'];

			// If nonce verification fails, create a new one and add our user ID filter.
			if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				add_filter( 'nonce_user_logged_out', [ $this, 'nonce_user_logged_out' ], 50, 2 );

				$_SERVER['HTTP_X_WP_NONCE'] = wp_create_nonce( 'wp_rest' );
			}
		}

		return $errors;
	}

	/**
	 * Prevent WooCommerce overriding the user ID for logged out users as this breaks our nonce validation.
	 *
	 * @param int $uid The user ID
	 * @param string $action The nonce action
	 * @return int The user ID when logged out
	 */
	public function nonce_user_logged_out( $uid, $action ) {
		if ( 'wp_rest' === $action && ! is_user_logged_in() ) {
			return 0;
		}

		return $uid;
	}

}
