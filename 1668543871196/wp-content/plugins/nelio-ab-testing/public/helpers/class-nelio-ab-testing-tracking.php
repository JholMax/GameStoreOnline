<?php
/**
 * Some helper functions to add tracking capabilities.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/public/helpers
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Some helper functions to add tracking capabilities.
 */
class Nelio_AB_Testing_Tracking {

	protected static $instance;

	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;

	}//end instance()

	public function init() {

		add_action( 'init', array( $this, 'add_wp_conversion_action_hooks' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_tracking_script' ), 1 );

		add_action(
			'nab_public_init',
			function() {
				if ( nab_is_split_testing_disabled() ) {
					return;
				}//end if
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_tracking_script' ), 1 );
				add_action( 'wp_footer', array( $this, 'add_script_for_tracking_later_page_views' ), 1 );
			}
		);

	}//end init()

	public function add_wp_conversion_action_hooks() {

		$experiments = nab_get_running_experiments();

		foreach ( $experiments as $experiment ) {

			$goals = $experiment->get_goals();
			foreach ( $goals as $goal_index => $goal ) {

				$actions = $goal['conversionActions'];
				foreach ( $actions as $action ) {

					$action_type = $action['type'];

					/**
					 * Fires for each conversion action in a running experiment.
					 *
					 * Use it to add any hooks required by your conversion action. Action
					 * called during WordPress’ `init` action.
					 *
					 * @param array $action        properties of the action.
					 * @param int   $experiment_id ID of the experiment that contains this action.
					 * @param int   $goal_index    index (in the goals array of an experiment) of the goal that contains this action.
					 * @param Goal  $goal          the goal.
					 *
					 * @since 5.0.0
					 * @since 5.1.0 Add goal.
					 */
					do_action( "nab_{$action_type}_add_hooks_for_tracking", $action['attributes'], $experiment->get_id(), $goal_index, $goal );

				}//end foreach
			}//end foreach
		}//end foreach

	}//end add_wp_conversion_action_hooks()

	public function register_tracking_script() {
		nab_register_script_with_auto_deps( 'nelio-ab-testing-main', 'main', false );
	}//end register_tracking_script()

	public function enqueue_tracking_script() {

		$runtime = Nelio_AB_Testing_Runtime::instance();
		if ( $runtime->are_scripts_disabled() ) {
			return;
		}//end if

		wp_enqueue_script( 'nelio-ab-testing-main' );

		$script = <<<JS
		window.nabAddSingleAction( "main-init-available", function() { nab.init( %s ); } );
		window.nabAddSingleAction( "main-ready", function() { nab.view( %s ); } );
JS;

		$settings   = $this->get_tracking_script_settings();
		$page_views = array_merge(
			$this->get_experiments_that_should_trigger_a_page_view( 'wp_head' ),
			$this->get_heatmaps_that_should_trigger_a_page_view()
		);

		wp_add_inline_script(
			'nelio-ab-testing-main',
			sprintf(
				$script,
				wp_json_encode( $settings ),
				wp_json_encode( $page_views )
			)
		);

	}//end enqueue_tracking_script()

	public function get_tracking_script_settings( $flags = array() ) {
		$runtime  = Nelio_AB_Testing_Runtime::instance();
		$settings = Nelio_AB_Testing_Settings::instance();

		return array(
			'alternativePostIds'  => $this->get_alternative_post_ids(),
			'experiments'         => $this->get_simplified_running_experiments(),
			'heatmapTracking'     => in_array( 'disable-heatmaps', $flags, true ) ? array() : $this->get_heatmap_tracking_data(),
			'optimizeXPath'       => $this->should_track_clicks_with_optimized_xpath(),
			'gdprCookie'          => $runtime->get_gdpr_cookie(),
			'participationChance' => $settings->get( 'percentage_of_tested_visitors' ),
			'siteId'              => nab_get_site_id(),
			'timezone'            => nab_get_timezone(),
			'trackingUrl'         => nab_get_api_url( '/site/' . nab_get_site_id() . '/event', 'browser' ),
			'useSendBeacon'       => $this->use_send_beacon(),
		);
	}//end get_tracking_script_settings()

	public function add_script_for_tracking_later_page_views() {

		if (
			! wp_script_is( 'nelio-ab-testing-main', 'enqueued' ) &&
			! wp_script_is( 'nelio-ab-testing-main', 'done' )
		) {
			return;
		}//end if

		$experiments = $this->get_experiments_that_should_trigger_a_page_view( 'wp_footer' );

		if ( empty( $experiments ) ) {
			return;
		}//end if

		$script = sprintf(
			'window.nabAddSingleAction( "main-ready", function() { nab.view( %s ); } );',
			wp_json_encode( $experiments )
		);
		nab_print_inline_script( 'nelio-ab-testing-main-ready', $script );

	}//end add_script_for_tracking_later_page_views()

	private function get_alternative_post_ids() {
		$current_post_id = $this->get_current_post_id();
		if ( empty( $current_post_id ) ) {
			return array();
		}//end if

		$runtime     = Nelio_AB_Testing_Runtime::instance();
		$experiments = $runtime->get_relevant_running_experiments();
		foreach ( $experiments as $experiment ) {
			$experiment_type = $experiment->get_type();

			/**
			 * Filters the name of the attribute (if any) that contains an alternative post ID. If none, return `false`.
			 *
			 * @param boolean|string $alt_post_attr name of the attribute that contains an alternative post ID. `false` otherwise.
			 *
			 * @since 5.2.7
			 */
			$alt_post_attr = apply_filters( "nab_{$experiment_type}_alternative_post_attribute", false );
			if ( $alt_post_attr ) {
				$alternatives = wp_list_pluck( $experiment->get_alternatives(), 'attributes' );
				$post_ids     = wp_list_pluck( $alternatives, $alt_post_attr );
				$post_ids     = array_values( array_filter( array_map( 'absint', $post_ids ) ) );
				if ( isset( $post_ids[0] ) && $post_ids[0] === $current_post_id ) {
					return $post_ids;
				}//end if
			}//end if
		}//end foreach

		return array( $current_post_id );

	}//end get_alternative_post_ids()

	private function get_current_post_id() {
		if ( $this->is_blog_page() ) {
			return absint( get_option( 'page_for_posts' ) );
		}//end if

		if ( $this->is_woocommerce_shop_page() ) {
			return absint( wc_get_page_id( 'shop' ) );
		}//end if

		if ( ! is_singular() ) {
			return 0;
		}//end if

		return nab_get_queried_object_id();
	}//end get_current_post_id()

	private function is_blog_page() {
		return ! is_front_page() && is_home();
	}//end is_blog_page()

	private function is_woocommerce_shop_page() {
		return function_exists( 'is_shop' ) && function_exists( 'wc_get_page_id' ) && is_shop();
	}//end is_woocommerce_shop_page()

	private function get_experiments_that_should_trigger_a_page_view( $location ) {

		$runtime       = Nelio_AB_Testing_Runtime::instance();
		$requested_alt = $runtime->get_alternative_from_request();

		$experiments = $runtime->get_relevant_running_experiments();
		$experiments = array_filter(
			$experiments,
			function( $experiment ) use ( $requested_alt, $location ) {

				$experiment_type = $experiment->get_type();

				/**
				 * Whether experiments of the given type should send page view events in the footer, after the whole page has been created and rendered.
				 *
				 * @param boolean $track_page_views_in_footer whether experiments of the given type should send page view events in the footer. Default: `false`.
				 *
				 * @since 5.0.0
				 */
				$track_page_views_in_footer = apply_filters( "nab_{$experiment_type}_track_page_views_in_footer", false );
				if ( 'wp_head' === $location && $track_page_views_in_footer ) {
					return false;
				}//end if
				if ( 'wp_footer' === $location && ! $track_page_views_in_footer ) {
					return false;
				}//end if

				$control      = $experiment->get_alternative( 'control' );
				$alternatives = $experiment->get_alternatives();
				$alternative  = $alternatives[ $requested_alt % count( $alternatives ) ];

				$experiment_id  = $experiment->get_id();
				$alternative_id = $alternative['id'];

				/**
				 * Whether the given experiment should trigger a page view in the current page/alternative combination.
				 *
				 * @param boolean $should_trigger_page_view whether the given experiment should trigger a page view. Default: `false`.
				 * @param array   $alternative              the current alternative.
				 * @param array   $control                  original version.
				 * @param int     $experiment_id            id of the experiment.
				 * @param string  $alternative_id           id of the current alternative.
				 *
				 * @since 5.0.0
				 */
				return apply_filters( "nab_{$experiment_type}_should_trigger_page_view", false, $alternative['attributes'], $control['attributes'], $experiment_id, $alternative_id );
			}
		);

		return array_map(
			function( $experiment ) {
				return $experiment->get_id();
			},
			array_values( $experiments )
		);

	}//end get_experiments_that_should_trigger_a_page_view()

	private function get_heatmaps_that_should_trigger_a_page_view() {

		$runtime  = Nelio_AB_Testing_Runtime::instance();
		$heatmaps = $runtime->get_relevant_running_heatmaps();

		return array_map(
			function ( $heatmap ) {
				return $heatmap->get_id();
			},
			$heatmaps
		);

	}//end get_heatmaps_that_should_trigger_a_page_view()

	private function get_simplified_running_experiments() {

		return array_reduce(
			nab_get_running_experiments(),
			function ( $result, $experiment ) {
				$result[ $experiment->get_id() ] = array(
					'alternatives' => $this->simplify_alternatives( $experiment->get_alternatives() ),
					'goals'        => $this->simplify_goals( $experiment->get_goals() ),
					'segments'     => $this->simplify_segments( $experiment->get_segments() ),
					'type'         => $experiment->get_type(),
				);
				return $result;
			},
			array()
		);

	}//end get_simplified_running_experiments()

	private function get_heatmap_tracking_data() {

		$runtime       = Nelio_AB_Testing_Runtime::instance();
		$requested_alt = $runtime->get_alternative_from_request();
		$heatmaps      = $runtime->get_relevant_running_heatmaps();
		$experiments   = $runtime->get_relevant_running_experiments();

		$heatmaps = array_map(
			function ( $heatmap ) {
				return array(
					'id'   => $heatmap->get_id(),
					'type' => 'heatmap',
				);
			},
			$heatmaps
		);

		if ( ! nab_is_subscribed() ) {
			return array_values( $heatmaps );
		}//end if

		$experiments = array_filter(
			$experiments,
			function ( $experiment ) use ( $requested_alt ) {

				$experiment_type = $experiment->get_type();

				$control      = $experiment->get_alternative( 'control' );
				$alternatives = $experiment->get_alternatives();
				$alternative  = $alternatives[ $requested_alt % count( $alternatives ) ];

				$experiment_id  = $experiment->get_id();
				$alternative_id = $alternative['id'];

				/**
				 * Whether the given experiment should track heatmaps in the current request.
				 *
				 * @param boolean $should_track_heatmap  whether the given experiment should track heatmaps in the current request. Default: `false`.
				 * @param array   $alternative           the current alternative.
				 * @param array   $control               original version.
				 * @param int     $experiment_id         id of the experiment.
				 * @param string  $alternative_id        id of the current alternative.
				 *
				 * @since 5.0.0
				 */
				return apply_filters( "nab_{$experiment_type}_should_track_heatmap", false, $alternative['attributes'], $control['attributes'], $experiment_id, $alternative_id );

			}
		);
		$experiments = array_map(
			function ( $experiment ) {
				return array(
					'id'   => $experiment->get_id(),
					'type' => 'experiment',
				);
			},
			array_values( $experiments )
		);

		return array_values( array_merge( $heatmaps, $experiments ) );

	}//end get_heatmap_tracking_data()

	private function should_track_clicks_with_optimized_xpath() {

		/**
		 * Whether the plugin should track click events with an optimized xpath structured.
		 *
		 * If set to `true`, the tracked xpath element IDs and, therefore, it’s smaller
		 * and a little bit faster to process.
		 *
		 * If your theme (or one of your plugins) generates random IDs for the HTML
		 * elements included in your pages, disable this feature. Otherwise, heatmaps
		 * may not work properly.
		 *
		 * @param boolean $optimized_xpath Default: `true`.
		 *
		 * @since 5.0.0
		 */
		return true === apply_filters( 'nab_should_track_clicks_with_optimized_xpath', true );

	}//end should_track_clicks_with_optimized_xpath()

	private function simplify_alternatives( $alternatives ) {

		return array_map(
			function( $alternative ) {
				return $alternative['attributes'];
			},
			$alternatives
		);

	}//end simplify_alternatives()

	private function simplify_goals( $goals ) {

		$goals = array_map(
			function( $index ) use ( $goals ) {
				$goal = $goals[ $index ];
				return array(
					'id'                => $index,
					'conversionActions' => $goal['conversionActions'],
				);
			},
			array_keys( $goals )
		);

		foreach ( $goals as &$goal ) {
			foreach ( $goal['conversionActions'] as &$action ) {
				unset( $action['id'] );
			}//end foreach
		}//end foreach

		return $goals;

	}//end simplify_goals()

	private function simplify_segments( $segments ) {

		if ( empty( $segments ) ) {
			return array();
		}//end if

		$segments = array_map(
			function( $index ) use ( $segments ) {
				$segment = $segments[ $index ];
				return array(
					'id'                => $index,
					'segmentationRules' => $segment['segmentationRules'],
				);
			},
			array_keys( $segments )
		);

		foreach ( $segments as &$segment ) {
			foreach ( $segment['segmentationRules'] as &$rule ) {
				unset( $rule['id'] );
			}//end foreach
		}//end foreach

		return $segments;

	}//end simplify_segments()

	private function use_send_beacon() {
		/**
		 * Filters whether the plugin should track JS events with `navigator.sendBeacon` or not.
		 *
		 * In general, `navigator.sendBeacon` is faster and more reliable, and
		 * therefore it's the preferrer option for tracking JS events. However,
		 * some browsers and/or ad and track blockers may block them.
		 *
		 * @param boolean $enabled whether to use `navigator.sendBeacon` or not. Default: `true`.
		 *
		 * @since 5.2.2
		 */
		return apply_filters( 'nab_use_send_beacon_tracking', true );
	}//end use_send_beacon()

}//end class
