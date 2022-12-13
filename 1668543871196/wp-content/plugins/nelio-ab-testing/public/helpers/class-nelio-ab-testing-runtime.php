<?php
/**
 * Some helper functions used during runtime
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/public/helpers
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Some helper functions used during frontend runtime.
 */
class Nelio_AB_Testing_Runtime {

	protected static $instance;

	private $relevant_priority_experiments;
	private $relevant_regular_experiments;
	private $relevant_heatmaps;

	private $current_url;

	public static function instance() {

		if ( is_null( self::$instance ) ) {

			self::$instance = new self();

			self::$instance->relevant_priority_experiments = array();
			self::$instance->relevant_regular_experiments  = array();
			self::$instance->relevant_heatmaps             = array();
			self::$instance->current_url                   = false;

		}//end if

		return self::$instance;

	}//end instance()

	public function init() {

		add_action( 'plugins_loaded', array( $this, 'compute_relevant_priority_experiments' ), 9999 );
		add_action( 'parse_query', array( $this, 'compute_relevant_regular_experiments' ), 9999 );
		add_action( 'parse_query', array( $this, 'compute_relevant_heatmaps' ), 9999 );

	}//end init()

	public function get_relevant_running_experiments() {

		return array_merge(
			$this->relevant_priority_experiments,
			$this->relevant_regular_experiments
		);

	}//end get_relevant_running_experiments()

	public function get_relevant_running_heatmaps() {

		return $this->relevant_heatmaps;

	}//end get_relevant_running_heatmaps()

	public function get_current_url() {

		if ( empty( $this->current_url ) ) {
			$this->compute_current_url();
		}//end if
		return $this->current_url;

	}//end get_current_url()

	public function get_untested_url() {

		return remove_query_arg( 'nab', $this->get_current_url() );

	}//end get_untested_url()

	public function get_alternative_from_request() {

		if ( $this->is_post_request() ) {
			if ( $this->is_tested_post_request() ) {
				return $this->get_nab_value_from_post_request();
			} else {
				return 0;
			}//end if
		}//end if

		$url = $this->get_current_url();
		$nab = $this->get_nab_query_arg( $url );
		return absint( $nab );

	}//end get_alternative_from_request()

	public function get_referrer_param() {

		/**
		 * Filters the query arg that retains the original referrer after performing a JavaScript redirection.
		 *
		 * Nelio A/B Testing loads the appropriate variant a visitor is supposed to see by
		 * performing a JavaScript redirection. As a result of this redirection, the original
		 * referrer is lost and the website owner can’t no longer know where the traffic is
		 * coming from.
		 *
		 * Use this filter to specify the query arg the plugin should use to retain this
		 * information. By default, it uses Google Analytics default arg: `utm_referrer`.
		 *
		 * If you don’t want to keep that information at all, set the value to `false`.
		 *
		 * @param boolean|string $param_name the name of the query arg that should keep track of the referrer or false otherwise.
		 *
		 * @since 5.0.0
		 */
		return apply_filters( 'nab_referrer_param', 'utm_referrer' );

	}//end get_referrer_param()

	public function is_tested_post_request() {

		if ( ! $this->is_post_request() ) {
			return false;
		}//end if

		if ( ! $this->can_load_alternative_content_on_post_request() ) {
			return false;
		}//end if

		return false !== $this->get_nab_value_from_post_request();

	}//end is_tested_post_request()

	public function get_gdpr_cookie() {

		/**
		 * Filters the name of the cookie that monitors GDPR acceptance.
		 *
		 * According to EU regulations and, in particular, the GDPR, visitors should be able to
		 * decide whether they want to be tracked by your website or not. If you need to comply
		 * to the GDPR, you can use this setting to specify the name of the cookie that must
		 * exist for tracking that visitor.
		 *
		 * By default, this setting is set to `false`, which means that all users will be tracked
		 * regardless of any other cookies.
		 *
		 * @param boolean|string $gdpr_cookie the name of the cookie that should exist if GDPR has
		 *                                    been accepted and, therefore, tracking is allowed.
		 *                                    Default: `false`.
		 *
		 * @since 5.0.0
		 */
		return apply_filters( 'nab_gdpr_cookie', false );

	}//end get_gdpr_cookie()

	public function compute_relevant_priority_experiments() {

		$this->relevant_priority_experiments = $this->filter_relevant_experiments( nab_get_running_experiments(), 'priority' );

		/**
		 * Fires after determining the list of relevant priority experiments.
		 *
		 * Priority experiments are those experiments whose hooks need to run
		 * very early in the execution of WordPress actions. For example, theme
		 * tests must be determined before `theme_setup` runs. That’s why
		 * “priority” experiments are determined during `plugins_loaded`.
		 *
		 * @param array $experiments list of relevant priority experiments.
		 *
		 * @since 5.2.8
		 */
		do_action( 'nab_relevant_priority_experiments_loaded', $this->relevant_priority_experiments );

	}//end compute_relevant_priority_experiments()

	public function compute_relevant_regular_experiments( $query ) {
		
		if ( ! $query->is_main_query() ) {
			return;
		}//end if
		remove_action( 'parse_query', array( $this, 'compute_relevant_regular_experiments' ), 9999 );

		$this->relevant_regular_experiments = $this->filter_relevant_experiments( nab_get_running_experiments(), 'regular' );

		/**
		 * Fires after determining the list of relevant regular experiments.
		 *
		 * Regular experiments are those experiments that are NOT priority
		 * experiments. This means their hooks are set during the
		 * `parse_query` hook (which means they can use functions like
		 * `is_singular` and/or `get_queried_object_id`).
		 *
		 * @param array $experiments list of relevant regular experiments.
		 *
		 * @since 5.2.8
		 */
		do_action( 'nab_relevant_regular_experiments_loaded', $this->relevant_regular_experiments );

	}//end compute_relevant_regular_experiments()

	public function compute_relevant_heatmaps( $query ) {

		if ( ! $query->is_main_query() ) {
			return;
		}//end if
		remove_action( 'parse_query', array( $this, 'compute_relevant_heatmaps' ), 9999 );

		$untested_url = $this->get_untested_url();

		$this->relevant_heatmaps = array_filter(
			nab_get_running_heatmaps(),
			function ( $heatmap ) use ( $untested_url ) {
				if ( 'url' !== $heatmap->get_tracking_mode() ) {
					return nab_get_queried_object_id() === $heatmap->get_tracked_post_id();
				}//end if

				$expected_url = $heatmap->get_tracked_url();
				return $this->do_urls_match_exactly( $untested_url, $expected_url );
			}
		);

		/**
		 * Fires after determining the list of relevant heatmaps.
		 *
		 * @param array $heatmaps list of relevant heatmaps.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_relevant_heatmaps_loaded', $this->relevant_heatmaps );

	}//end compute_relevant_heatmaps()

	public function are_scripts_disabled() {
		$experiments = nab_get_running_experiments();
		$heatmaps    = nab_get_running_heatmaps();
		return $this->are_scripts_optional() && empty( $experiments ) && empty( $heatmaps );
	}//end are_scripts_disabled()

	public function are_scripts_optional() {
		/**
		 * Filters whether NAB scripts are optional or not.
		 *
		 * If they are not optional, NAB scripts will be always loaded. If they are,
		 * they’ll only show up if there are experiments or heatmaps running. Morever,
		 * the alternative loader script will only show up on pages that are
		 * under test.
		 *
		 * @param boolean $optional whether NAB scripts are optional or not. Default: `true`.
		 *
		 * @since 5.2.0
		 */
		return apply_filters( 'nab_optional_frontend_scripts', true );
	}//end are_scripts_optional()

	private function is_post_request() {

		return (
			isset( $_SERVER['REQUEST_METHOD'] ) &&
			'POST' === $_SERVER['REQUEST_METHOD']
		);

	}//end is_post_request()

	private function can_load_alternative_content_on_post_request() {

		/**
		 * Filters whether the plugin can attempt to load alternative content when processing a post request or not.
		 *
		 * @param boolean $can_load whether the plugin can attempt to load alternative content when processing a post request or not. Default: `true`.
		 *
		 * @since 5.0.10
		 */
		return apply_filters( 'nab_can_load_alternative_content_on_post_request', true );

	}//end can_load_alternative_content_on_post_request()

	private function filter_relevant_experiments( $experiments, $mode ) {

		$relevant_experiments = array_filter(
			$experiments,
			function( $experiment ) use ( $mode ) {

				$url             = $this->get_untested_url();
				$experiment_id   = $experiment->get_id();
				$experiment_type = $experiment->get_type();

				/**
				 * Filters whether a certain experiment is a priority experiment or not.
				 *
				 * @param boolean $is_priority   whether the given experiment is a priority
				 *                               experiment or not.
				 *                               Default: `false`.
				 * @param int     $experiment_id the ID of the experiment.
				 *
				 * @since 5.2.8
				 */
				$priority = apply_filters( "nab_is_{$experiment_type}_priority_experiment", false, $experiment_id );

				if ( $priority && 'priority' !== $mode ) {
					return false;
				}//end if

				if ( ! $priority && 'priority' === $mode ) {
					return false;
				}//end if

				/**
				 * Filters whether a certain experiment should be disabled for the current request.
				 *
				 * @param boolean $is_disabled   whether the given experiment should be disabled or not.
				 *                               Default: `false`.
				 * @param int     $experiment_id the ID of the experiment.
				 * @param string  $url           the URL of the current request.
				 *
				 * @since 5.0.7
				 */
				if ( apply_filters( "nab_{$experiment_type}_disable_experiment_in_url", false, $experiment_id, $url ) ) {
					return false;
				}//end if

				$scope = $experiment->get_scope();
				if ( empty( $scope ) ) {
					return true;
				}//end if

				foreach ( $scope as $rule ) {
					if ( $this->does_scope_rule_match( $rule['attributes'], $experiment ) ) {
						return true;
					}//end if
				}//end foreach

				return false;

			}
		);

		return array_values( $relevant_experiments );

	}//end filter_relevant_experiments()

	private function get_nab_value_from_post_request() {
		if ( isset( $_COOKIE['nabAlternative'] ) ) { // phpcs:ignore
			return absint( $_COOKIE['nabAlternative'] ); // phpcs:ignore
		}//end if

		if ( isset( $_REQUEST['nab'] ) ) { // phpcs:ignore
			return absint( $_REQUEST['nab'] ); // phpcs:ignore
		}//end if

		return false;
	}//end get_nab_value_from_post_request()

	private function get_nab_query_arg( $url ) {

		$query = wp_parse_args( wp_parse_url( $url, PHP_URL_QUERY ) );
		if ( ! isset( $query['nab'] ) ) {
			return false;
		}//end if

		return absint( $query['nab'] );

	}//end get_nab_query_arg()

	private function compute_current_url() {

		// “nab” query var and WordPress’ default public query vars (see class-wp.php).
		$query_vars = array( 'nab', 'm', 'p', 'posts', 'w', 'cat', 'withcomments', 'withoutcomments', 's', 'search', 'exact', 'sentence', 'calendar', 'page', 'paged', 'more', 'tb', 'pb', 'author', 'order', 'orderby', 'year', 'monthnum', 'day', 'hour', 'minute', 'second', 'name', 'category_name', 'tag', 'feed', 'author_name', 'pagename', 'page_id', 'error', 'attachment', 'attachment_id', 'subpost', 'subpost_id', 'preview', 'robots', 'taxonomy', 'term', 'cpage', 'post_type', 'embed' );

		/**
		 * Filters public query vars.
		 *
		 * @param array $query_vars public query vars.
		 *
		 * @since 5.0.6
		 */
		$query_vars = apply_filters( 'nab_query_vars', $query_vars );

		$url   = nab_home_url( $this->get_clean_request_uri() );
		$query = wp_parse_args( wp_parse_url( $url, PHP_URL_QUERY ) );
		$query = array_filter(
			$query,
			function( $key ) use ( $query_vars ) {
				return in_array( $key, $query_vars, true );
			},
			ARRAY_FILTER_USE_KEY
		);

		ksort( $query );
		$url = preg_replace( '/\?.*$/', '', $url );
		$url = add_query_arg( $query, $url );

		$nab = $this->get_nab_query_arg( $url );
		if ( false !== $nab ) {
			$url = remove_query_arg( 'nab', $url );
			$url = add_query_arg( 'nab', $nab, $url );
		}//end if

		$this->current_url = $url;

	}//end compute_current_url()

	private function get_clean_request_uri() {

		$request_uri             = $_SERVER['REQUEST_URI']; // phpcs:ignore
		$request_uri_in_home_url = preg_replace( '/^https?:\/\/[^\/]+/', '', nab_home_url() );

		$request_uri             = '/' . ltrim( $request_uri, '/' );
		$request_uri_in_home_url = '/' . ltrim( $request_uri_in_home_url, '/' );

		if ( 0 !== strpos( $request_uri, $request_uri_in_home_url ) ) {
			return $request_uri;
		}//end if

		$request_uri = substr( $request_uri, strlen( $request_uri_in_home_url ) );
		if ( 0 < strlen( $request_uri ) && '/' !== $request_uri[0] ) {
			$request_uri = '/' . $request_uri;
		}//end if

		return $request_uri;

	}//end get_clean_request_uri()

	private function does_scope_rule_match( $rule, $experiment ) {

		$url = $this->get_untested_url();

		switch ( $rule['type'] ) {

			case 'tested-post':
				/**
				 * Filters whether the current request is a single post that’s tested by the given experiment.
				 *
				 * @param boolean $is_tested whether the current request is a single post that’s tested by the given experiment.
				 * @param number  $post_id   post ID of the current post (if any).
				 * @param array   $control   original version.
				 * @param number  $exp_id    ID of the experiment.
				 *
				 * @since 5.2.11
				 */
				return apply_filters( "nab_is_tested_post_by_{$experiment->get_type()}_experiment", $this->is_tested_post( nab_get_queried_object_id(), $experiment ), nab_get_queried_object_id(), $experiment->get_alternative( 'control' )['attributes'], $experiment->ID );

			case 'partial':
				return false !== stripos( $url, $rule['value'] );

			case 'exact':
				return $this->do_urls_match_exactly( $url, $rule['value'] );
		}//end switch

		return false;

	}//end does_scope_rule_match()

	private function is_tested_post( $current_post_id, $experiment ) {
		if ( ! is_singular() ) {
			return false;
		}//end if

		$control_id = absint( $experiment->get_tested_element() );
		if ( $current_post_id === $control_id ) {
			return true;
		}//end if

		$control = $experiment->get_alternative( 'control' );
		$control = $control['attributes'];
		if ( empty( $control['testAgainstExistingContent'] ) ) {
			return false;
		}//end if

		$alts = $experiment->get_alternatives();
		$pids = wp_list_pluck( wp_list_pluck( $alts, 'attributes' ), 'postId' );
		$pids = array_values( array_filter( $pids ) );
		return in_array( $current_post_id, $pids, true );
	}//end is_tested_post()

	private function do_urls_match_exactly( $actual_url, $expected_url ) {

		$actual_url   = strtolower( preg_replace( '/^[^:]+:\/\//', '', $actual_url ) );
		$expected_url = strtolower( preg_replace( '/^[^:]+:\/\//', '', $expected_url ) );

		$actual_args   = wp_parse_args( wp_parse_url( $actual_url, PHP_URL_QUERY ) );
		$expected_args = wp_parse_args( wp_parse_url( $expected_url, PHP_URL_QUERY ), $actual_args );

		ksort( $actual_args );
		ksort( $expected_args );

		$actual_url   = untrailingslashit( preg_replace( '/\?.*$/', '', $actual_url ) );
		$expected_url = untrailingslashit( preg_replace( '/\?.*$/', '', $expected_url ) );

		/**
		 * Whether to ignore query args when trying to match the current URL with a URL specified in an experiment scope.
		 *
		 * @param boolean $ignore whether to ignore query args when trying to match the URL with a URL specified in an experiment scope. Default: `false`.
		 *
		 * @since 5.0.0
		 */
		if ( ! apply_filters( 'nab_ignore_query_args_in_scope', false ) ) {
			$actual_url   = add_query_arg( $actual_args, $actual_url );
			$expected_url = add_query_arg( $expected_args, $expected_url );
		}//end if

		return $actual_url === $expected_url;

	}//end do_urls_match_exactly()

}//end class
