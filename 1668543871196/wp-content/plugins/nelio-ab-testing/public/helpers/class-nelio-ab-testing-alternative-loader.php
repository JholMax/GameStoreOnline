<?php
/**
 * This class adds the required scripts in the front-end to enable alternative loading.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/public/helpers
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds the required scripts in the front-end to enable alternative loading.
 */
class Nelio_AB_Testing_Alternative_Loader {

	protected static $instance;
	const MAX_NUMBER_OF_POSSIBLE_COMBINATIONS = 24;

	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;

	}//end instance()

	public function init() {

		add_action( 'nab_relevant_priority_experiments_loaded', array( $this, 'add_alternative_loading_hooks' ) );
		add_action( 'nab_relevant_regular_experiments_loaded', array( $this, 'add_alternative_loading_hooks' ) );

		add_action( 'wp_head', array( $this, 'add_alternative_loader_script' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_alternative_loader_script' ) );
		add_action( 'get_canonical_url', array( $this, 'fix_canonical_url' ), 50 );

	}//end init()

	public function register_alternative_loader_script() {
		nab_register_script_with_auto_deps( 'nelio-ab-testing-alternative-loader', 'alternative-loader', false );
	}//end register_alternative_loader_script()

	public function add_alternative_loader_script() {

		$num_of_alternatives = $this->get_number_of_alternatives();

		$runtime = Nelio_AB_Testing_Runtime::instance();
		if ( $runtime->are_scripts_disabled() ) {
			return;
		}//end if

		if ( $runtime->are_scripts_optional() && empty( $num_of_alternatives ) ) {
			$before = sprintf(
				'window.nabPreloadConfig = %s;',
				wp_json_encode( $this->get_preload_query_arg_urls() )
			);
			nab_print_inline_script( 'nelio-ab-testing-alternative-loader', 'alternative-loader-fake', $before );
			return;
		}//end if

		$settings                    = Nelio_AB_Testing_Settings::instance();
		$alternative_loader_settings = array(
			'experiments'           => $this->get_simplified_experiments(),
			'gdprCookie'            => $runtime->get_gdpr_cookie(),
			'hideQueryArgs'         => $settings->get( 'hide_query_args' ),
			'ignoreTrailingSlash'   => $this->ignore_trailing_slash(),
			'isStagingSite'         => nab_is_staging(),
			'isTestedPostRequest'   => $runtime->is_tested_post_request(),
			'maxCombinations'       => self::MAX_NUMBER_OF_POSSIBLE_COMBINATIONS,
			'numOfAlternatives'     => $num_of_alternatives,
			'participationChance'   => $settings->get( 'percentage_of_tested_visitors' ),
			'postId'                => is_singular() ? get_the_ID() : false,
			'postUrls'              => $this->get_post_urls(),
			'preloadQueryArgUrls'   => $this->get_preload_query_arg_urls(),
			'referrerParam'         => $runtime->get_referrer_param(),
			'site'                  => nab_get_site_id(),
			'whenIsGeoDataRequired' => $this->when_is_geo_data_required(),
		);

		// phpcs:ignore
		$asset   = include nelioab()->plugin_path . '/assets/dist/js/alternative-loader.asset.php';
		$version = $asset['version'];

		$script = add_query_arg( 'version', $version, nelioab()->plugin_url . '/assets/dist/js/alternative-loader.js' );
		nab_print_inline_script( 'nelio-ab-testing-alternative-loader', $script );

		$script = sprintf(
			'window.nabAddSingleAction( "alt-loader-init-available", function() { nab.loader.init( %s ); } );',
			wp_json_encode( $alternative_loader_settings )
		);
		nab_print_inline_script( 'nelio-ab-testing-alternative-loader-after', $script );

	}//end add_alternative_loader_script()

	public function fix_canonical_url( $url ) {
		$runtime   = Nelio_AB_Testing_Runtime::instance();
		$post_urls = $this->get_post_urls();
		if ( ! empty( $post_urls ) ) {
			return get_permalink();
		}//end if

		$requested_alt = $runtime->get_alternative_from_request();
		return $requested_alt ? $runtime->get_untested_url() : $url;
	}//end fix_canonical_url()

	public function add_alternative_loading_hooks( $experiments ) {

		if ( ! is_array( $experiments ) ) {
			$experiments = array( $experiments );
		}//end if

		$runtime       = Nelio_AB_Testing_Runtime::instance();
		$requested_alt = $runtime->get_alternative_from_request();

		foreach ( $experiments as $experiment ) {

			$experiment_type = $experiment->get_type();

			$control      = $experiment->get_alternative( 'control' );
			$alternatives = $experiment->get_alternatives();
			$alternative  = $alternatives[ $requested_alt % count( $alternatives ) ];

			/**
			 * Fires when a certain alternative is about to be loaded as part of a split test.
			 *
			 * Use this action to add any hooks that your experiment type might require in order
			 * to properly load the alternative.
			 *
			 * @param array  $alternative    attributes of the active alternative.
			 * @param array  $control        attributes of the control version.
			 * @param int    $experiment_id  experiment ID.
			 * @param string $alternative_id alternative ID.
			 *
			 * @since 5.0.0
			 */
			do_action( "nab_{$experiment_type}_load_alternative", $alternative['attributes'], $control['attributes'], $experiment->get_id(), $alternative['id'] );

		}//end foreach

	}//end add_alternative_loading_hooks()

	private function get_post_urls() {
		if ( ! is_singular() ) {
			return array();
		}//end if

		$post_id  = get_the_ID();
		$post_url = array(
			'postId' => $post_id,
			'url'    => get_permalink( $post_id ),
		);

		$experiment = $this->get_relevant_post_experiment( $post_id );
		if ( empty( $experiment ) ) {
			return array( $post_url );
		}//end if

		$control = $experiment->get_alternative( 'control' );
		$control = $control['attributes'];
		if ( empty( $control['testAgainstExistingContent'] ) ) {
			return array( $post_url );
		}//end if

		$alts = $experiment->get_alternatives();
		$alts = wp_list_pluck( wp_list_pluck( $alts, 'attributes' ), 'postId' );
		$urls = array_map( 'get_permalink', $alts );

		$result = array();
		foreach ( $alts as $i => $pid ) {
			$result[] = array(
				'postId' => $pid,
				'url'    => $urls[ $i ],
			);
		}//end foreach
		return $result;
	}//end get_post_urls()

	private function get_relevant_post_experiment( $post_id ) {
		$runtime = Nelio_AB_Testing_Runtime::instance();
		$exps    = $runtime->get_relevant_running_experiments();

		foreach ( $exps as $exp ) {
			$control    = $exp->get_alternative( 'control' );
			$control    = $control['attributes'];
			$control_id = ! empty( $control['postId'] ) ? $control['postId'] : 0;
			if ( $post_id === $control_id ) {
				return $exp;
			}//end if

			if ( ! empty( $control['testAgainstExistingContent'] ) ) {
				$alts = $exp->get_alternatives();
				$pids = wp_list_pluck( wp_list_pluck( $alts, 'attributes' ), 'postId' );
				$pids = array_values( array_filter( $pids ) );
				foreach ( $pids as $pid ) {
					if ( $pid === $post_id ) {
						return $exp;
					}//end if
				}//end foreach
			}//end if
		}//end foreach

		return false;
	}//end get_relevant_post_experiment()

	private function get_number_of_alternatives() {

		$gcd = function( $n, $m ) use ( &$gcd ) {
			if ( 0 === $n || 0 === $m ) {
				return 1;
			}//end if
			if ( $n === $m && $n > 1 ) {
				return $n;
			}//end if
			return $m < $n ? $gcd( $n - $m, $n ) : $gcd( $n, $m - $n );
		};

		$lcm = function( $n, $m ) use ( &$gcd ) {
			return $m * ( $n / $gcd( $n, $m ) );
		};

		$experiments  = $this->get_experiments_that_load_alternative_content();
		$alternatives = array_unique(
			array_map(
				function( $experiment ) {
					return count( $experiment->get_alternatives() );
				},
				$experiments
			)
		);

		if ( empty( $alternatives ) ) {
			return 0;
		}//end if

		return array_reduce( $alternatives, $lcm, 1 );

	}//end get_number_of_alternatives()

	private function get_simplified_experiments() {

		return array_reduce(
			$this->get_experiments_that_load_alternative_content(),
			function ( $result, $experiment ) {
				$result[ $experiment->get_id() ] = array(
					'segments' => $this->simplify_segments( $experiment->get_segments() ),
					'type'     => $experiment->get_type(),
				);
				return $result;
			},
			array()
		);

	}//end get_simplified_experiments()

	private function simplify_segments( $segments ) {

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

	private function when_is_geo_data_required() {
		$experiments = $this->get_experiments_that_load_alternative_content();
		foreach ( $experiments as $experiment ) {
			if ( $this->contains_geo_segmentation_rules( $experiment ) ) {
				return 'now';
			}//end if
		}//end foreach

		$experiments = nab_get_running_experiments();
		foreach ( $experiments as $experiment ) {
			if ( $this->contains_geo_segmentation_rules( $experiment ) ) {
				return 'future';
			}//end if
		}//end foreach

		return 'never';
	}//end when_is_geo_data_required()

	private function contains_geo_segmentation_rules( $experiment ) {
		$segments = $experiment->get_segments();
		foreach ( $segments as $segment ) {
			$rules = $segment['segmentationRules'];
			foreach ( $rules as $rule ) {

				if ( in_array( $rule['type'], array( 'nab/ip-address', 'nab/location' ), true ) ) {
					return true;
				}//end if
			}//end foreach
		}//end foreach

		return false;
	}//end contains_geo_segmentation_rules()

	private function get_experiments_that_load_alternative_content() {

		$runtime     = Nelio_AB_Testing_Runtime::instance();
		$experiments = $runtime->get_relevant_running_experiments();

		return array_values(
			array_filter(
				$experiments,
				function( $experiment ) {

					$control         = $experiment->get_alternative( 'control' );
					$experiment_id   = $experiment->get_id();
					$experiment_type = $experiment->get_type();

					/**
					 * Whether the experiment should be excluded from adding a `nab` query arg in the current request or not.
					 *
					 * @param boolean $is_excluded   whether the experiment should be excluded from the current request or not.
					 *                               Default: `false`.
					 * @param array   $control       original version.
					 * @param int     $experiment_id id of the experiment.
					 *
					 * @since 5.0.4
					 */
					return ! apply_filters( "nab_{$experiment_type}_exclude_experiment_from_loading", false, $control['attributes'], $experiment_id );

				}
			)
		);

	}//end get_experiments_that_load_alternative_content()

	private function ignore_trailing_slash() {
		/**
		 * Filters whether alternative content loading should ignore the trailing slash in a URL when comparing the current URL and the URL of the alternative the visitor is supposed to see.
		 *
		 * If it’s set to ignore, `https://example.com/some-page` and `https://example.com/some-page/` will be considered the same page. Otherwise, they’ll be different.
		 *
		 * @param boolean $ignore_trailing_slash whether to ignore the trailing slash or not.
		 *
		 * @since 5.0.8
		 */
		return apply_filters( 'nab_ignore_trailing_slash_in_alternative_loading', true );
	}//end ignore_trailing_slash()

	private function get_preload_query_arg_urls() {
		$settings = Nelio_AB_Testing_Settings::instance();
		if ( ! $settings->get( 'preload_query_args' ) ) {
			return array();
		}//end if

		$experiments = nab_get_running_experiments();
		if ( empty( $experiments ) ) {
			return array();
		}//end if

		return array_map(
			function( $e ) {
				$control = $e->get_alternative( 'control' );
				$alts    = wp_list_pluck( $e->get_alternatives(), 'attributes' );
				if ( isset( $control['attributes']['testAgainstExistingContent'] ) ) {
					$alts = wp_list_pluck( $alts, 'postId' );
					$alts = array_map( 'get_permalink', $alts );
					return array(
						'type'     => 'alt-urls',
						'altUrls'  => $alts,
						'altCount' => count( $alts ),
					);
				}//end if

				$rules = wp_list_pluck( $e->get_scope(), 'attributes' );
				if ( empty( $rules ) ) {
					return array(
						'type'     => 'scope',
						'scope'    => array( '**' ),
						'altCount' => count( $alts ),
					);
				}//end if

				$main = $e->get_tested_element();
				$urls = array_map(
					function ( $rule ) use ( $main ) {
						if ( 'tested-post' === $rule['type'] ) {
							return get_permalink( $main );
						}//end if
						return 'exact' === $rule['type']
							? $rule['value']
							: "*{$rule['value']}*";
					},
					$rules
				);
				return array(
					'type'     => 'scope',
					'scope'    => $urls,
					'altCount' => count( $alts ),
				);
			},
			$experiments
		);
	}//end get_preload_query_arg_urls()

}//end class
