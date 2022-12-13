<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/public
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The public-facing functionality of the plugin.
 */
class Nelio_AB_Testing_Public {

	protected static $instance;

	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;

	}//end instance()

	public function init() {

		$this->load_admin_helpers();

		add_filter( 'script_loader_tag', array( $this, 'maybe_add_extra_script_attributes' ), 10, 2 );

		add_action( 'set_logged_in_cookie', array( $this, 'set_user_session_cookies' ), 10, 4 );
		add_action( 'init', array( $this, 'update_user_session_cookies' ) );
		add_action( 'clear_auth_cookie', array( $this, 'clear_user_session_cookies' ) );

		add_action( 'wp_head', array( $this, 'add_kickoff_script' ), 1 );
		add_action( 'plugins_loaded', array( $this, 'init_split_testing' ) );
		add_action( 'set_current_user', array( $this, 'simulate_anonymous_visitor' ), 9999 );

		add_action(
			'plugins_loaded',
			function() {

				/**
				 * Initialized the public facet of the plugin.
				 *
				 * Fires right after WordPress’ `plugins_loaded` action with a low priority
				 * (so that other plugins can hook into `nab_public_init` during `plugins_loaded`).
				 *
				 * @since 5.0.0
				 */
				do_action( 'nab_public_init' );

			},
			9999
		);

	}//end init()

	public function maybe_add_extra_script_attributes( $tag, $handle ) {
		if ( false === strpos( $handle, 'nelio-ab-testing' ) ) {
			return $tag;
		}//end if
		return nab_add_extra_script_attributes( $tag );
	}//end maybe_add_extra_script_attributes()

	public function set_user_session_cookies( $_, $__, $expiration, $user_id ) {

		// phpcs:ignore
		setcookie( 'nabIsUserLoggedIn', 'true', $expiration, '/' );

		if ( ! $this->is_visitor_tested( $user_id ) ) {
			// phpcs:ignore
			setcookie( 'nabIsVisitorExcluded', 'true', $expiration, '/' );
		} else {
			// phpcs:ignore
			setcookie( 'nabIsVisitorExcluded', 'true', time() - YEAR_IN_SECONDS, '/' );
		}//end if

	}//end set_user_session_cookies()

	public function update_user_session_cookies() {
		if ( isset( $_COOKIE['nabIsUserLoggedIn'] ) && isset( $_COOKIE['nabIsVisitorExcluded'] ) ) {
			return;
		}//end if

		if ( nab_is_preview() || nab_is_heatmap() ) {
			return;
		}//end if

		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			return;
		}//end if

		$this->set_user_session_cookies( null, null, time() + DAY_IN_SECONDS, $user_id );
	}//end update_user_session_cookies()

	public function clear_user_session_cookies() {
		// phpcs:ignore
		setcookie( 'nabIsUserLoggedIn', 'true', time() - YEAR_IN_SECONDS, '/' );
		// phpcs:ignore
		setcookie( 'nabIsVisitorExcluded', 'true', time() - YEAR_IN_SECONDS, '/' );
	}//end clear_user_session_cookies()

	public function add_kickoff_script() {

		if ( nab_is_split_testing_disabled() ) {
			return;
		}//end if

		$runtime = Nelio_AB_Testing_Runtime::instance();
		if ( $runtime->are_scripts_disabled() ) {
			return;
		}//end if

		echo "<style type=\"text/css\" id=\"nab-alternative-loader-style\"></style>\n";
		nab_print_inline_script( 'nelio-ab-testing-kickoff', 'kickoff' );
	}//end add_kickoff_script()

	public function init_split_testing() {

		if ( nab_is_split_testing_disabled() ) {
			return;
		}//end if

		$aux = Nelio_AB_Testing_Runtime::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Alternative_Loader::instance();
		$aux->init();

	}//end init_split_testing()

	public function load_admin_helpers() {

		$aux = Nelio_AB_Testing_Alternative_Preview::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Css_Selector_Finder::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Heatmap_Renderer::instance();
		$aux->init();

		$aux = Nelio_AB_Testing_Quick_Experiment_Menu::instance();
		$aux->init();

	}//end load_admin_helpers()

	public function simulate_anonymous_visitor() {

		/**
		 * Simulates an anonymous visitor.
		 *
		 * When set to `true`, Nelio A/B Testing will set the current user
		 * to an anonymous (non-logged-in) user. This way, the web will look
		 * as if an anonymous users were browsing the site.
		 *
		 * @param boolean $anonymize whether the user should be set to an anonymous
		 *                           user or not. Default: `false`.
		 *
		 * @since 5.0.0
		 */
		if ( ! apply_filters( 'nab_simulate_anonymous_visitor', false ) ) {
			return;
		}//end if

		global $current_user;
		$current_user = new WP_User(); // phpcs:ignore

	}//end simulate_anonymous_visitor()

	private function is_visitor_tested( $user_id ) {

		$is_visitor_tested = true;

		if ( is_super_admin( $user_id ) ) {
			$is_visitor_tested = false;
		} elseif ( in_array( nab_get_user_role( $user_id ), array( 'editor', 'administrator' ), true ) ) {
			$is_visitor_tested = false;
		}//end if

		/**
		 * Whether the user related to the current request should be tested or not.
		 *
		 * With this filter, you can decide if the current user participates in your running experiments or not.
		 * By default, all users are tested except those that have (at least) an `editor` role.
		 *
		 * **Notice.** Our plugin uses JavaScript to load alternative content. Be careful when limiting tests
		 * in PHP, as it’s possible that your cache or CDN ends up caching these limitations and, as a result,
		 * none of your visitors are tested.
		 *
		 * @param boolean $is_visitor_tested whether the user related to the current request should be tested or not.
		 * @param int     $user_id           ID of the visitor.
		 *
		 * @since 5.0.0
		 * @since 5.0.9 The `$user_id` param has been added.
		 */
		return apply_filters( 'nab_is_visitor_tested', $is_visitor_tested, $user_id );

	}//end is_visitor_tested()

}//end class
