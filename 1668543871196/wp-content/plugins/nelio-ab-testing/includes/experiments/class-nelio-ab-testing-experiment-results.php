<?php
/**
 * This file defines the class of the results of a Nelio A/B Testing Experiment.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/experiments
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Results of an Experiment in Nelio A/B Testing.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/experiments
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      5.0.0
 */
class Nelio_AB_Testing_Experiment_Results {

	/**
	 * The experiment results.
	 *
	 * @var array
	 */
	public $results = null;

	/**
	 * The experiment (post) ID.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param integer|WP_Post $experiment The identifier of an experiment
	 *            in the database, or a WP_Post instance with it.
	 * @param array           $results Results object.
	 *
	 * @since  5.0.0
	 * @access private
	 */
	private function __construct( $experiment, $results ) {

		if ( isset( $experiment->ID ) ) {

			$this->ID      = absint( $experiment->ID );
			$this->results = $results;

		}//end if

	}//end __construct()

	public static function get_experiment_results( $experiment ) {

		$experiment = Nelio_AB_Testing_Experiment::get_experiment( $experiment );
		$results    = self::get_results_from_cloud( $experiment );

		return new Nelio_AB_Testing_Experiment_Results( $experiment, $results );

	}//end get_experiment_results()

	/**
	 * Returns the ID of this experiment.
	 *
	 * @return integer the ID of this experiment.
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_id() {

		return $this->ID;

	}//end get_id()

	/**
	 * Returns the consumed page views for the experiment.
	 *
	 * @return int the consumed page views
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_consumed_page_views() {

		$results = $this->results;
		if ( is_wp_error( $results ) || empty( $results ) ) {
			return 0;
		}//end if

		$page_views = 0;

		foreach ( $results as $key => $value ) {
			if ( 'a' !== $key[0] ) {
				continue;
			}//end if

			$page_views += $value['v'];
		}//end foreach

		return $page_views;

	}//end get_consumed_page_views()

	/**
	 * Returns the current confidence of the experiment results.
	 *
	 * @return float the current confidence of the results
	 *
	 * @since  5.0.0
	 * @access public
	 */
	public function get_current_confidence() {

		$results = $this->results;
		if ( is_wp_error( $results ) || empty( $results ) ) {
			return 0;
		}//end if

		if ( ! isset( $results['results'] ) ) {
			return 0;
		}//end if

		$results_value = $results['results'];
		if ( ! isset( $results_value['g0'] ) ) {
			return 0;
		}//end if

		$main_goal = $results_value['g0'];
		if ( ! isset( $main_goal['confidence'] ) ) {
			return 0;
		} else {
			return $main_goal['confidence'];
		}//end if

	}//end get_current_confidence()

	private static function get_results_from_cloud( $experiment ) {

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

		$url      = nab_get_api_url( '/site/' . nab_get_site_id() . '/experiment/' . $experiment->get_id(), 'wp' );
		$response = wp_remote_request( $url, $data );

		// If the response is an error, leave.
		$error = nab_maybe_return_error_json( $response );
		if ( $error ) {
			return $error;
		}//end if

		return json_decode( $response['body'], true );

	}//end get_results_from_cloud()

}//end class
