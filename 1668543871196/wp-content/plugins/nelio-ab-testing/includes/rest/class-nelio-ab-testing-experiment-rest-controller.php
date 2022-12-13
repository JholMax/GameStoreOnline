<?php
/**
 * This file contains the class that defines REST API endpoints for
 * experiments.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/rest
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_Experiment_REST_Controller extends WP_REST_Controller {

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
			'/experiment',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_experiment' ),
					'permission_callback' => array( $this, 'check_if_user_can_use_plugin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_experiment' ),
					'permission_callback' => array( $this, 'check_if_user_can_use_plugin' ),
					'args'                => array(),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_experiment' ),
					'permission_callback' => array( $this, 'check_if_user_can_use_plugin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<id>[\d]+)/start',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'start_experiment' ),
					'permission_callback' => array( $this, 'check_if_user_can_use_plugin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<id>[\d]+)/resume',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'resume_experiment' ),
					'permission_callback' => array( $this, 'check_if_user_can_use_plugin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<id>[\d]+)/stop',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'stop_experiment' ),
					'permission_callback' => array( $this, 'check_if_user_can_use_plugin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<id>[\d]+)/pause',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'pause_experiment' ),
					'permission_callback' => array( $this, 'check_if_user_can_use_plugin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<id>[\d]+)/apply/(?P<alternative>[A-Za-z0-9-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'apply_alternative' ),
					'permission_callback' => array( $this, 'check_if_user_can_use_plugin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<id>[\d]+)/result',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_experiment_results' ),
					'permission_callback' => array( $this, 'check_if_user_can_use_plugin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<id>[\d]+)/debug/migrate-results',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'migrate_old_results' ),
					'permission_callback' => array( $this, 'check_if_user_can_use_plugin' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiments-running',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_running_experiments' ),
					'permission_callback' => array( $this, 'check_if_user_can_use_plugin' ),
					'args'                => array(),
				),
			)
		);

	}//end register_routes()

	/**
	 * Create a new experiment
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response The response
	 */
	public function create_experiment( $request ) {

		$parameters = $request->get_json_params();

		if ( ! isset( $parameters['type'] ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'Unable to create a new test because the test type is missing.', 'text', 'nelio-ab-testing' )
			);
		}//end if

		$type = trim( sanitize_text_field( $parameters['type'] ) );

		$experiment = nab_create_experiment( $type );
		if ( is_wp_error( $experiment ) ) {
			return new WP_Error(
				'error',
				_x( 'An unknown error occurred while trying to create the test. Please try again later.', 'user', 'nelio-ab-testing' )
			);
		}//end if

		if ( $parameters['addTestedPostScopeRule'] ) {
			$rule = array(
				'id'         => nab_uuid(),
				'attributes' => array(
					'type' => 'tested-post',
				),
			);
			$experiment->set_scope( array( $rule ) );
			$experiment->save();
		}//end if

		return new WP_REST_Response(
			array(
				'id'   => $experiment->get_id(),
				'edit' => add_query_arg(
					array(
						'page'       => 'nelio-ab-testing-experiment-edit',
						'experiment' => $experiment->get_id(),
					),
					admin_url( 'admin.php' )
				),
			),
			200
		);

	}//end create_experiment()

	/**
	 * Retrieves an experiment
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function get_experiment( $request ) {

		$experiment_id = $request['id'];
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return new WP_REST_Response( $experiment, 500 );
		}//end if

		return new WP_REST_Response( $this->json( $experiment ), 200 );

	}//end get_experiment()

	/**
	 * Retrieves the results of an experiment
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function get_experiment_results( $request ) {

		$experiment_id = $request['id'];
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return new WP_REST_Response( $experiment, 500 );
		}//end if

		$are_results_definitive = get_post_meta( $experiment_id, '_nab_are_timeline_results_definitive', true );
		if ( ! empty( $are_results_definitive ) ) {
			$results = get_post_meta( $experiment_id, '_nab_timeline_results', true );
			return new WP_REST_Response( $results, 200 );
		}//end if

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

		$url      = nab_get_api_url( '/site/' . nab_get_site_id() . '/experiment/' . $experiment_id, 'wp' );
		$url      = $this->add_dates_in_url( $url, $experiment );
		$url      = $this->add_segments_in_url( $url, $experiment );
		$response = wp_remote_request( $url, $data );

		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		$new_results = json_decode( $response['body'], true );
		update_post_meta( $experiment_id, '_nab_timeline_results', $new_results );

		$are_results_definitive = 'finished' === $experiment->get_status();
		if ( $are_results_definitive ) {
			update_post_meta( $experiment_id, '_nab_are_timeline_results_definitive', true );
		} else {
			delete_post_meta( $experiment_id, '_nab_are_timeline_results_definitive' );
		}//end if

		return new WP_REST_Response( $new_results, 200 );

	}//end get_experiment_results()

	/**
	 * Triggers a request to Nelio’s cloud to migrate old results.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function migrate_old_results( $request ) {

		$experiment_id = $request['id'];
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return new WP_REST_Response( $experiment, 500 );
		}//end if

		$params = $request->get_json_params();
		$data   = array(
			'method'    => 'POST',
			'timeout'   => apply_filters( 'nab_migration_request_timeout', 120 ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'body'      => wp_json_encode( $params ),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = nab_get_api_url( '/site/' . nab_get_site_id() . '/migrate/' . $experiment_id, 'wp' );
		$response = wp_remote_request( $url, $data );
		$error    = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		$backup = array(
			'params' => $params,
			'meta'   => array(
				'newSiteId'       => nab_get_site_id(),
				'newExperimentId' => $experiment_id,
				'timestamp'       => gmdate( 'c' ),
			),
		);
		update_post_meta( $experiment_id, '_nab_result_migration_params', $backup );

		$results = json_decode( $response['body'], true );
		update_post_meta( $experiment_id, '_nab_timeline_results', $results );
		if ( 'finished' === $experiment->get_status() ) {
			update_post_meta( $experiment_id, '_nab_are_timeline_results_definitive', true );
		} else {
			delete_post_meta( $experiment_id, '_nab_are_timeline_results_definitive' );
		}//end if

		return new WP_REST_Response( 'ok', 200 );

	}//end migrate_old_results()

	/**
	 * Retrieves the collection of running experiments
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function get_running_experiments( $request ) {

		$experiments = nab_get_running_experiments();

		$data = array_map(
			function( $experiment ) {
				return $this->json( $experiment );
			},
			$experiments
		);

		return new WP_REST_Response( $data, 200 );

	}//end get_running_experiments()

	/**
	 * Updates an experiment
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function update_experiment( $request ) {

		$experiment_id = $request['id'];
		$parameters    = $request->get_json_params();

		$experiment = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return new WP_REST_Response( $experiment, 500 );
		}//end if

		$experiment->set_name( $parameters['name'] );
		$experiment->set_description( $parameters['description'] );
		$experiment->set_status( $parameters['status'] );
		$experiment->set_start_date( $parameters['startDate'] );
		$experiment->set_end_mode_and_value( $parameters['endMode'], $parameters['endValue'] );

		if ( 'nab/heatmap' !== $experiment->get_type() ) {
			$experiment->set_alternatives( $parameters['alternatives'] );
			$experiment->set_goals( $parameters['goals'] );
			$experiment->set_segments( $parameters['segments'] );
			$experiment->set_scope( $parameters['scope'] );
		} else {
			$experiment->set_tracking_mode( $parameters['trackingMode'] );
			$experiment->set_tracked_post_id( $parameters['trackedPostId'] );
			$experiment->set_tracked_post_type( $parameters['trackedPostType'] );
			$experiment->set_tracked_url( $parameters['trackedUrl'] );
		}//end if

		$experiment->save();

		$experiment = nab_get_experiment( $experiment_id );
		return new WP_REST_Response( $this->json( $experiment ), 200 );

	}//end update_experiment()

	/**
	 * Start an experiment
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function start_experiment( $request ) {

		$experiment_id = $request['id'];
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return new WP_REST_Response( $experiment, 500 );
		}//end if

		$started = $experiment->start();
		if ( is_wp_error( $started ) ) {
			return new WP_REST_Response( $started, 500 );
		}//end if

		return new WP_REST_Response( $this->json( $experiment ), 200 );

	}//end start_experiment()

	/**
	 * Resumes an experiment
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function resume_experiment( $request ) {

		$experiment_id = $request['id'];
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return new WP_REST_Response( $experiment, 500 );
		}//end if

		$resumed = $experiment->resume();
		if ( is_wp_error( $resumed ) ) {
			return new WP_REST_Response( $resumed, 500 );
		}//end if

		return new WP_REST_Response( $this->json( $experiment ), 200 );

	}//end resume_experiment()

	/**
	 * Stop an experiment
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function stop_experiment( $request ) {

		$experiment_id = $request['id'];
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return new WP_REST_Response( $experiment, 500 );
		}//end if

		$stopped = $experiment->stop();
		if ( is_wp_error( $stopped ) ) {
			return new WP_REST_Response( $stopped, 500 );
		}//end if

		return new WP_REST_Response( $this->json( $experiment ), 200 );

	}//end stop_experiment()

	/**
	 * Pauses an experiment
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function pause_experiment( $request ) {

		$experiment_id = $request['id'];
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return new WP_REST_Response( $experiment, 500 );
		}//end if

		$paused = $experiment->pause();
		if ( is_wp_error( $paused ) ) {
			return new WP_REST_Response( $paused, 500 );
		}//end if

		return new WP_REST_Response( $this->json( $experiment ), 200 );

	}//end pause_experiment()

	/**
	 * Applies the given alternative.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function apply_alternative( $request ) {

		$experiment_id = $request['id'];
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return new WP_REST_Response( $experiment, 500 );
		}//end if

		$alternative_id = $request['alternative'];
		$result         = $experiment->apply_alternative( $alternative_id );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( $result, 500 );
		}//end if

		return new WP_REST_Response( $this->json( $experiment ), 200 );

	}//end apply_alternative()

	public function check_if_user_can_use_plugin() {
		return current_user_can( 'edit_others_posts' );
	}//end check_if_user_can_use_plugin()

	private function add_dates_in_url( $url, $experiment ) {

		$url = add_query_arg( 'start', rawurlencode( $experiment->get_start_date() ), $url );
		if ( 'finished' === $experiment->get_status() ) {
			$url = add_query_arg( 'end', rawurlencode( $experiment->get_end_date() ), $url );
		}//end if

		$url = add_query_arg( 'tz', rawurlencode( nab_get_timezone() ), $url );

		return $url;

	}//end add_dates_in_url()

	private function add_segments_in_url( $url, $experiment ) {

		$segments = $experiment->get_segments();
		$segments = ! empty( $segments ) ? $segments : array();

		$url = add_query_arg( 'segments', count( $segments ), $url );

		return $url;

	}//end add_segments_in_url()

	public function json( $experiment ) {

		$data = array(
			'id'          => $experiment->get_id(),
			'name'        => $experiment->get_name(),
			'description' => $experiment->get_description(),
			'status'      => $experiment->get_status(),
			'type'        => $experiment->get_type(),
			'startDate'   => $experiment->get_start_date(),
			'endDate'     => $experiment->get_end_date(),
			'endMode'     => $experiment->get_end_mode(),
			'endValue'    => $experiment->get_end_value(),
			'links'       => array(
				'preview' => $experiment->get_preview_url(),
				'edit'    => $experiment->get_url(),
			),
		);

		if ( 'nab/heatmap' === $experiment->get_type() ) {
			$data = array_merge(
				$data,
				array(
					'trackingMode'    => $experiment->get_tracking_mode(),
					'trackedPostId'   => $experiment->get_tracked_post_id(),
					'trackedPostType' => $experiment->get_tracked_post_type(),
					'trackedUrl'      => $experiment->get_tracked_url(),
				)
			);

			$data['links']['heatmap'] = $experiment->get_heatmap_url();
			return $data;
		}//end if

		$data['alternatives'] = $experiment->get_alternatives();

		$goals = $experiment->get_goals();
		if ( ! empty( $goals ) ) {
			$data['goals'] = $goals;
		}//end if

		if ( 'nab/heatmap' !== $experiment->get_type() ) {
			$segments         = $experiment->get_segments();
			$segments         = ! empty( $segments ) ? $segments : array();
			$data['segments'] = $segments;
		}//end if

		$scope = $experiment->get_scope();
		if ( ! empty( $scope ) ) {
			$data['scope'] = $scope;
		}//end if

		return $data;

	}//end json()

}//end class
