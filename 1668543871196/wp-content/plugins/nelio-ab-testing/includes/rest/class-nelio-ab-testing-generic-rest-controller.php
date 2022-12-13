<?php
/**
 * This file contains the class that defines generic REST API endpoints.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/rest
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_Generic_REST_Controller extends WP_REST_Controller {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @access protected
	 * @var    Nelio_AB_Testing_REST_API
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_REST_API the single instance of this class.
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
			'external.js',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_external_tracking_data' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'external.html',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'generate_iframe_page_to_track_external_event' ),
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/plugins/',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_plugins' ),
					'permission_callback' => array( $this, 'check_if_user_can_use_plugin' ),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/plugin/clean',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'clean_plugin' ),
					'permission_callback' => array( $this, 'check_if_user_can_deactivate_plugin' ),
				),
			)
		);

	}//end register_routes()

	/**
	 * Returns all active plugins.
	 *
	 * @return WP_REST_Response The response
	 */
	public function get_plugins() {
		$plugins = array_values( get_option( 'active_plugins', array() ) );
		return new WP_REST_Response( $plugins, 200 );
	}//end get_plugins()

	/**
	 * Returns whether the user can use the plugin or not.
	 *
	 * @return boolean whether the user can use the plugin or not.
	 */
	public function check_if_user_can_use_plugin() {
		return current_user_can( 'edit_others_posts' );
	}//end check_if_user_can_use_plugin()

	/**
	 * Returns whether the user can use the plugin or not.
	 *
	 * @return boolean whether the user can use the plugin or not.
	 */
	public function check_if_user_can_deactivate_plugin() {
		return current_user_can( 'deactivate_plugin', nelioab()->plugin_file );
	}//end check_if_user_can_deactivate_plugin()

	/**
	 * Generates a script to track events in third-party websites.
	 */
	public function get_external_tracking_data() {

		header( 'Content-Type: application/javascript' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );

		$settings = array(
			'trackingFrameUrl' => get_rest_url( null, nelioab()->rest_namespace . '/external.html' ),
		);

		// phpcs:ignore
		echo file_get_contents( nelioab()->plugin_path . '/assets/dist/js/external-tracking-script.js' );
		printf( "\nnab.init(%s);", wp_json_encode( $settings ) ); // phpcs:ignore

		die();

	}//end get_external_tracking_data()

	/**
	 * Generates a page to track events in third-party websites using an iframe.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 */
	public function generate_iframe_page_to_track_external_event( $request ) {

		header( 'Content-Type: text/html; charset=UTF-8' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );

		echo "<!DOCTYPE html>\n";
		echo "<html>\n<head>\n";

		$public = Nelio_AB_Testing_Public::instance();
		$public->add_kickoff_script();
		echo "\n";

		echo "<script type=\"text/javascript\">\n";

		$tracking = Nelio_AB_Testing_Tracking::instance();
		$settings = $tracking->get_tracking_script_settings( array( 'disable-heatmaps' ) );
		// phpcs:ignore
		echo file_get_contents( nelioab()->plugin_path . '/assets/dist/js/main.js' );
		printf( "\nnab.init( %s );", wp_json_encode( $settings ) );

		$parameters = $request->get_params();
		if ( isset( $parameters['experiment'] ) && isset( $parameters['goal'] ) ) {
			printf(
				"\nnab.convert(%d,%d);",
				wp_json_encode( absint( $parameters['experiment'] ) ),
				wp_json_encode( absint( $parameters['goal'] ) )
			);
		}//end if

		if ( isset( $parameters['eventName'] ) ) {
			printf(
				"\nnab.trigger(%s);",
				wp_json_encode( sanitize_text_field( $parameters['eventName'] ) )
			);
		}//end if

		echo '</script>';
		echo "\n</head>\n<body></body>\n</html>";

		die();

	}//end generate_iframe_page_to_track_external_event()

	/**
	 * Cleans the plugin. If a reason is provided, it tells our cloud what happened.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response The response
	 */
	public function clean_plugin( $request ) {

		$nonce = $request['_nonce'];
		if ( ! wp_verify_nonce( $nonce, 'nab_clean_plugin_data_' . get_current_user_id() ) ) {
			return new WP_Error( 'invalid-nonce' );
		}//end if

		$reason = $request['reason'];
		$reason = ! empty( $reason ) ? $reason : 'none';

		// 1. Clean cloud.
		$data = array(
			'method'    => 'DELETE',
			'timeout'   => apply_filters( 'nab_request_timeout', 30 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'body'      => wp_json_encode( array( 'reason' => $reason ) ),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/site/' . nab_get_site_id(), 'wp' );
		$response = wp_remote_request( $url, $data );
		$error    = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		// Clean database.
		$experiment_ids = nab_get_all_experiment_ids();
		foreach ( $experiment_ids as $id ) {
			wp_delete_post( $id, true );
		}//end foreach
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE 'nab_%'" ) ); // phpcs:ignore

		return new WP_REST_Response( true, 200 );

	}//end clean_plugin()

	private function get_relevant_experiments() {

		return array_reduce(
			nab_get_running_experiments(),
			function ( $result, $experiment ) {
				$result[ $experiment->get_id() ] = $this->get_experiment_summary( $experiment );
				return $result;
			},
			array()
		);

	}//end get_relevant_experiments()

	private function get_experiment_summary( $experiment ) {

		$active_goals = array();
		$goals        = $experiment->get_goals();

		foreach ( $goals as $index => $goal ) {
			if ( $this->has_goal_a_custom_event_conversion_action( $goal ) ) {
				array_push( $active_goals, $index );
			}//end if
		}//end foreach

		return array(
			'numOfAlts'   => count( $experiment->get_alternatives() ),
			'goalIndexes' => $active_goals,
		);

	}//end get_experiment_summary()

	private function has_goal_a_custom_event_conversion_action( $goal ) {

		return array_reduce(
			$goal['conversionActions'],
			function ( $memo, $action ) {
				return $memo || 'nab/custom-event' === $action['type'];
			},
			false
		);

	}//end has_goal_a_custom_event_conversion_action()

}//end class
