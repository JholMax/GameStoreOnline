<?php
/**
 * This file contains the class that defines REST API endpoints for
 * managing a Nelio A/B Testing account.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/rest
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_Account_REST_Controller extends WP_REST_Controller {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @access protected
	 * @var    Nelio_AB_Testing_Account_REST_Controller
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Account_REST_Controller the single instance of this class.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;

	}//end instance()

	/**
	 * Hooks into WordPress.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function init() {

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

	}//end init()

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {

		register_rest_route(
			nelioab()->rest_namespace,
			'/site',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_site_data' ),
					'permission_callback' => array( $this, 'check_if_user_can_use_plugin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/site/free',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'create_free_site' ),
					'permission_callback' => array( $this, 'check_if_user_is_admin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/site/premium',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'create_premium_site' ),
					'permission_callback' => array( $this, 'check_if_user_is_admin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/site/subscription',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'use_license_in_site' ),
					'permission_callback' => array( $this, 'check_if_user_is_admin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/site/(?P<id>[\w\-]+)/subscription',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'remove_license_from_site' ),
					'permission_callback' => array( $this, 'check_if_user_is_admin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/subscription/(?P<id>[\w\-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'upgrade_subscription' ),
					'permission_callback' => array( $this, 'check_if_user_is_admin' ),
					'args'                => array(),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'cancel_subscription' ),
					'permission_callback' => array( $this, 'check_if_user_is_admin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/subscription/(?P<id>[\w\-]+)/uncancel',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'uncancel_subscription' ),
					'permission_callback' => array( $this, 'check_if_user_is_admin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/subscription/(?P<id>[\w\-]+)/quota',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'buy_more_quota' ),
					'permission_callback' => array( $this, 'check_if_user_is_admin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/subscription/(?P<id>[\w\-]+)/sites',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_sites_using_subscription' ),
					'permission_callback' => array( $this, 'check_if_user_is_admin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/subscription/(?P<id>[\w\-]+)/invoices',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_invoices_from_subscription' ),
					'permission_callback' => array( $this, 'check_if_user_is_admin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/products',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_products' ),
					'permission_callback' => array( $this, 'check_if_user_is_admin' ),
					'args'                => array(),
				),
			)
		);

	}//end register_routes()

	/**
	 * Retrieves information about the site.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function get_site_data( $request ) {

		$data = array(
			'method'    => 'GET',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/site/' . nab_get_site_id(), 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		// Regenerate the account result and send it to the JS.
		$site_info = json_decode( $response['body'], true );

		$account = $this->create_account_object( $site_info );
		nab_update_subscription( $account['plan'] );

		if ( 'OL-' === substr( $account['subscription'], 0, 3 ) ) {
			update_option( 'nab_is_subscription_deprecated', true );
		} else {
			delete_option( 'nab_is_subscription_deprecated' );
		}//end if

		return new WP_REST_Response( $account, 200 );

	}//end get_site_data()

	/**
	 * Creates a new free site in AWS and updates the info in WordPress.
	 *
	 * @return WP_REST_Response The response
	 */
	public function create_free_site() {

		if ( nab_get_site_id() ) {
			return new WP_REST_Response( true, 200 );
		}//end if

		$params = array(
			'id'         => nab_uuid(),
			'url'        => home_url(),
			'language'   => nab_get_language(),
			'timezone'   => nab_get_timezone(),
			'wpVersion'  => get_bloginfo( 'version' ),
			'nabVersion' => nelioab()->plugin_version,
		);

		$data = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'accept'       => 'application/json',
				'content-type' => 'application/json',
			),
			'body'      => wp_json_encode( $params ),
		);

		$url      = nab_get_api_url( '/site', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		// Regenerate the account result and send it to the JS.
		$site_info = json_decode( $response['body'], true );
		update_option( 'nab_site_id', $site_info['id'] );
		update_option( 'nab_api_secret', $site_info['secret'] );

		$this->notify_site_created();

		return new WP_REST_Response( true, 200 );

	}//end create_free_site()

	/**
	 * Creates a new premium site in AWS and updates the info in WordPress.
	 *
	 * @return WP_REST_Response The response
	 */
	public function create_premium_site() {

		if ( nab_get_site_id() ) {
			return new WP_REST_Response( true, 200 );
		}//end if

		$options = get_option( 'nelioab_account_settings' );
		$license = $options['reg_num'];

		$params = array(
			'id'         => nab_uuid(),
			'url'        => home_url(),
			'language'   => nab_get_language(),
			'timezone'   => nab_get_timezone(),
			'wpVersion'  => get_bloginfo( 'version' ),
			'nabVersion' => nelioab()->plugin_version,
			'license'    => $license,
		);

		$data = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'accept'       => 'application/json',
				'content-type' => 'application/json',
			),
			'body'      => wp_json_encode( $params ),
		);

		$url      = nab_get_api_url( '/site/subscription', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $this->create_free_site();
		}//end if

		// Regenerate the account result and send it to the JS.
		$site_info = json_decode( $response['body'], true );
		update_option( 'nab_site_id', $site_info['id'] );
		update_option( 'nab_api_secret', $site_info['secret'] );

		$this->notify_site_created();

		return new WP_REST_Response( true, 200 );

	}//end create_premium_site()

	/**
	 * Connects a site with a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function use_license_in_site( $request ) {

		$parameters = $request->get_json_params();

		if ( ! isset( $parameters['license'] ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'License key is missing.', 'text', 'nelio-ab-testing' )
			);
		}//end if

		$license = trim( sanitize_text_field( $parameters['license'] ) );

		$data = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => wp_json_encode( array( 'license' => $license ) ),
		);

		$url      = nab_get_api_url( '/site/' . nab_get_site_id() . '/subscription', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		// Regenerate the account result and send it to the JS.
		$site_info = json_decode( $response['body'], true );
		$account   = $this->create_account_object( $site_info );

		nab_update_subscription( $account['plan'] );

		return new WP_REST_Response( $account, 200 );

	}//end use_license_in_site()

	/**
	 * Disconnects a site from a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function remove_license_from_site( $request ) {

		$site = $request['id'];

		$data = array(
			'method'    => 'DELETE',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/site/' . $site . '/subscription', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		if ( nab_get_site_id() === $site ) {
			nab_update_subscription( 'free' );
		}//end if

		return new WP_REST_Response( 'OK', 200 );

	}//end remove_license_from_site()

	/**
	 * Upgrades a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function upgrade_subscription( $request ) {

		$parameters = $request->get_json_params();

		if ( ! isset( $parameters['plan'] ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'Plan is missing.', 'text', 'nelio-ab-testing' )
			);
		}//end if

		$subscription = $request['id'];
		$plan         = trim( sanitize_text_field( $parameters['plan'] ) );

		$data = array(
			'method'    => 'PUT',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => wp_json_encode( array( 'product' => $plan ) ),
		);

		$url      = nab_get_api_url( '/subscription/' . $subscription, 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		return new WP_REST_Response( 'OK', 200 );

	}//end upgrade_subscription()

	/**
	 * Cancels a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function cancel_subscription( $request ) {

		$subscription = $request['id'];

		$data = array(
			'method'    => 'DELETE',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/subscription/' . $subscription, 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		return new WP_REST_Response( 'OK', 200 );

	}//end cancel_subscription()

	/**
	 * Un-cancels a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function uncancel_subscription( $request ) {

		$subscription = $request['id'];

		$data = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/subscription/' . $subscription . '/uncancel', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		return new WP_REST_Response( 'OK', 200 );

	}//end uncancel_subscription()

	/**
	 * Buys additional quota for a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function buy_more_quota( $request ) {

		$parameters = $request->get_json_params();

		if ( ! isset( $parameters['quantity'] ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'Quantity is missing.', 'text', 'nelio-ab-testing' )
			);
		}//end if

		if ( ! isset( $parameters['currency'] ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'Currency is missing.', 'text', 'nelio-ab-testing' )
			);
		}//end if

		$subscription = $request['id'];
		$quantity     = trim( sanitize_text_field( $parameters['quantity'] ) );
		$currency     = trim( sanitize_text_field( $parameters['currency'] ) );

		$data = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => wp_json_encode(
				array(
					'subscriptionId' => $subscription,
					'quantity'       => $quantity,
					'currency'       => $currency,
				)
			),
		);

		$url      = nab_get_api_url( '/fastspring/quota', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		return new WP_REST_Response( 'OK', 200 );

	}//end buy_more_quota()

	/**
	 * Obtains all sites connected with a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function get_sites_using_subscription( $request ) {

		$subscription = $request['id'];

		$data = array(
			'method'    => 'GET',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/subscription/' . $subscription . '/sites', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		// Regenerate the account result to send it to the JS.
		$sites = json_decode( $response['body'], true );

		// Move the current site to the top of the array of sites.
		$site_id                  = nab_get_site_id();
		$key                      = array_search( $site_id, array_column( $sites, 'id' ), true );
		$actual_site              = $sites[ $key ];
		$actual_site['actualUrl'] = home_url();
		array_splice( $sites, $key, 1 );
		array_unshift( $sites, $actual_site );

		$sites = array_map(
			function( $site ) {
				$aux = array(
					'id'            => $site['id'],
					'url'           => $site['url'],
					'isCurrentSite' => nab_get_site_id() === $site['id'],
				);

				if ( $aux['isCurrentSite'] ) {
					$aux['actualUrl'] = $site['actualUrl'];
				}//end if

				return $aux;
			},
			$sites
		);

		return new WP_REST_Response( $sites, 200 );

	}//end get_sites_using_subscription()

	/**
	 * Obtains the invoices of a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function get_invoices_from_subscription( $request ) {

		$subscription = $request['id'];

		$data = array(
			'method'    => 'GET',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/subscription/' . $subscription . '/invoices', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		// Regenerate the invoices result and send it to the JS.
		$invoices = json_decode( $response['body'], true );
		$invoices = array_map(
			function( $invoice ) {
				$invoice['chargeDate'] = gmdate( get_option( 'date_format' ), strtotime( $invoice['chargeDate'] ) );
				return $invoice;
			},
			$invoices
		);

		return new WP_REST_Response( $invoices, 200 );

	}//end get_invoices_from_subscription()

	/**
	 * Obtains the subscription products of Nelio A/B Testing.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function get_products( $request ) {

		$data = array(
			'method'    => 'GET',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/fastspring/products', 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		// Regenerate the products result and send it to the JS.
		$products = json_decode( $response['body'], true );
		$products = array_map(
			function( $product ) {
				$product = wp_parse_args(
					$product,
					array(
						'product'        => '',
						'display'        => '',
						'pricing'        => array(),
						'description'    => array(),
						'attributes'     => array(),
						'isSubscription' => true,
					)
				);

				$product['pricing']     = wp_parse_args( $product['pricing'], array( 'price' => '' ) );
				$product['description'] = wp_parse_args( $product['description'], array( 'full' => '' ) );

				$from = $product['upgradeableFrom'];
				if ( ! is_array( $from ) ) {
					$from = empty( $from ) ? array() : array( $from );
				}//end if
				return array(
					'id'              => $product['product'],
					'plan'            => nab_get_plan( $product['product'] ),
					'period'          => nab_get_period( $product['product'] ),
					'displayName'     => $product['display'],
					'price'           => $product['pricing']['price'],
					'description'     => $product['description']['full'],
					'attributes'      => $product['attributes'],
					'isSubscription'  => $product['isSubscription'],
					'upgradeableFrom' => $from,
				);
			},
			$products
		);

		return new WP_REST_Response( $products, 200 );

	}//end get_products()

	public function check_if_user_is_admin() {
		return current_user_can( 'manage_options' );
	}//end check_if_user_is_admin()

	public function check_if_user_can_use_plugin() {
		return current_user_can( 'edit_others_posts' );
	}//end check_if_user_can_use_plugin()

	/**
	 * This helper function creates an account object.
	 *
	 * @param object $site The data about the site.
	 *
	 * @return array an account object.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	private function create_account_object( $site ) {

		$subscription = wp_parse_args(
			$site['subscription'],
			array(
				'account'                => array(),
				'license'                => '',
				'endDate'                => '',
				'nextChargeDate'         => '',
				'deactivationDate'       => '',
				'nextChargeTotalDisplay' => '',
				'intervalUnit'           => 'month',
				'currency'               => 'USD',
				'subscription'           => '',
				'isAgency'               => false,
			)
		);

		$account = wp_parse_args(
			$subscription['account'],
			array(
				'email'     => '',
				'firstname' => '',
				'lastname'  => '',
			)
		);

		return array(
			'creationDate'        => $site['creation'],
			'email'               => $account['email'],
			/* translators: 1 -> firstname, 2 -> lastname */
			'fullname'            => sprintf( _x( '%1$s %2$s', 'text name', 'nelio-ab-testing' ), $account['firstname'], $account['lastname'] ),
			'firstname'           => $account['firstname'],
			'lastname'            => $account['lastname'],
			'photo'               => get_avatar_url( $account['email'], array( 'default' => 'mysteryman' ) ),
			'mode'                => $subscription['mode'],
			'startDate'           => $subscription['startDate'],
			'license'             => $subscription['license'],
			'endDate'             => $subscription['endDate'],
			'nextChargeDate'      => $subscription['nextChargeDate'],
			'deactivationDate'    => $subscription['deactivationDate'],
			'nextChargeTotal'     => $subscription['nextChargeTotalDisplay'],
			'plan'                => nab_get_plan( $subscription['product'] ),
			'productId'           => $subscription['product'],
			'productDisplay'      => $subscription['display'],
			'state'               => $subscription['state'],
			'quota'               => $subscription['quota'],
			'quotaExtra'          => $subscription['quotaExtra'],
			'quotaPerMonth'       => $subscription['quotaPerMonth'],
			'currency'            => $subscription['currency'],
			'sitesAllowed'        => $subscription['sitesAllowed'],
			'period'              => $subscription['intervalUnit'],
			'subscription'        => $subscription['id'],
			'isAgency'            => $subscription['isAgency'],
			'urlToManagePayments' => nab_get_api_url( '/fastspring/' . $subscription['id'] . '/url', 'browser' ),
		);

	}//end create_account_object()

	private function notify_site_created() {

		/**
		 * Fires once the site has been registered in Nelio’s cloud.
		 *
		 * When fired, the site has a valid site ID and an API secret.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_site_created' );

	}//end notify_site_created()

}//end class
