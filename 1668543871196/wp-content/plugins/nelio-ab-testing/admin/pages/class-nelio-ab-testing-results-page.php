<?php
/**
 * This file contains the class that renders the results of an experiment page.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that defines the results of an experiment page.
 */
class Nelio_AB_Testing_Results_Page extends Nelio_AB_Testing_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-ab-testing',
			_x( 'Results', 'text', 'nelio-ab-testing' ),
			_x( 'Tests', 'text', 'nelio-ab-testing' ),
			'edit_others_posts',
			'nelio-ab-testing-experiment-view'
		);

	}//end __construct()

	// @Overrides
	// phpcs:ignore
	public function init() {

		parent::init();
		add_action( 'admin_menu', array( $this, 'maybe_remove_this_page_from_the_menu' ), 999 );
		add_action( 'current_screen', array( $this, 'maybe_redirect_to_experiments_page' ) );
		add_action( 'current_screen', array( $this, 'die_if_params_are_invalid' ) );

	}//end init()

	public function maybe_redirect_to_experiments_page() {

		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}//end if

		if ( ! $this->does_request_have_an_experiment() ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=nab_experiment' ) );
			exit;
		}//end if

	}//end maybe_redirect_to_experiments_page()

	public function maybe_remove_this_page_from_the_menu() {

		if ( ! $this->is_current_screen_this_page() ) {
			$this->remove_this_page_from_the_menu();
		} else {
			$this->remove_experiments_list_from_menu();
		}//end if

	}//end maybe_remove_this_page_from_the_menu()

	public function die_if_params_are_invalid() {

		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}//end if

		$experiment_id = absint( $_GET['experiment'] ); // phpcs:ignore
		if ( 'nab_experiment' !== get_post_type( $experiment_id ) ) {
			wp_die( esc_html_x( 'You attempted to edit a test that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
			return;
		}//end if

		$experiment = nab_get_experiment( $experiment_id );
		$status     = $experiment->get_status();
		if ( ! in_array( $status, array( 'running', 'finished' ), true ) ) {
			wp_die( esc_html_x( 'You’re not allowed to view this page.', 'user', 'nelio-ab-testing' ) );
			return;
		}//end if

	}//end die_if_params_are_invalid()

	// @Implements
	// phpcs:ignore
	public function enqueue_assets() {

		/**
		 * Fires after enqueuing experiments assets in the experiment and the alternative edit screens.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_enqueue_experiment_assets' );

		wp_register_style(
			'nab-results-page',
			nelioab()->plugin_url . '/assets/dist/css/results-page.css',
			array( 'nab-components', 'nab-experiment-library' ),
			nelioab()->plugin_version
		);

		wp_register_style(
			'nab-heatmap-results-page',
			nelioab()->plugin_url . '/assets/dist/css/heatmap-results-page.css',
			array( 'nab-results-page' ),
			nelioab()->plugin_version
		);

		$experiment = nab_get_experiment( absint( $_GET['experiment'] ) ); // phpcs:ignore

		if ( 'nab/heatmap' !== $experiment->get_type() && ! isset( $_GET['heatmap'] ) ) { // phpcs:ignore
			$this->add_experiment_result_assets( $experiment );
			return;
		}//end if

		if ( isset( $_GET['heatmap'] ) ) { // phpcs:ignore
			$alternative_id = sanitize_text_field( wp_unslash( $_GET['heatmap'] ) ); // phpcs:ignore
		} else {
			$alternative_id = 'control';
		}//end if

		$this->add_heatmap_result_assets( $experiment, $alternative_id );

	}//end enqueue_assets()

	private function add_experiment_result_assets( $experiment ) {

		wp_enqueue_style( 'nab-results-page' );
		nab_enqueue_script_with_auto_deps( 'nab-results-page', 'results-page', true );

		$script = <<<JS
		( function() {
			wp.domReady( function() {
				nab.initPage( "results", %s );
			} );
		} )();
JS;

		$settings = array(
			'experimentId' => $experiment->get_id(),
			'isStaging'    => nab_is_staging(),
		);

		wp_add_inline_script(
			'nab-results-page',
			sprintf(
				$script,
				wp_json_encode( $settings )
			)
		);

	}//end add_experiment_result_assets()

	private function add_heatmap_result_assets( $experiment, $alternative_id ) {

		$alternative = $experiment->get_alternative( $alternative_id );
		if ( 'nab/heatmap' !== $experiment->get_type() && empty( $alternative ) ) {
			$helper = Nelio_AB_Testing_Experiment_Helper::instance();
			wp_die(
				esc_html(
					sprintf(
						/* translators: 1 -> variant ID, 2 -> experiment name */
						_x( 'Variant %1$s not found in test %2$s.', 'text', 'nelio-ab-testing' ),
						$alternative_id,
						$helper->get_non_empty_name( $experiment )
					)
				)
			);
		}//end if

		wp_enqueue_style( 'nab-heatmap-results-page' );
		nab_enqueue_script_with_auto_deps( 'nab-heatmap-results-page', 'heatmap-results-page', true );

		$script = <<<JS
		( function() {
			wp.domReady( function() {
				nab.initPage( "results", %s );
			} );
		} )();
JS;

		$settings = array(
			'alternativeId'  => $alternative_id,
			'awsAuthToken'   => nab_generate_api_auth_token(),
			'endDate'        => $experiment->get_end_date(),
			'experimentId'   => $experiment->get_id(),
			'firstDayOfWeek' => get_option( 'start_of_week', 0 ),
			'isStaging'      => nab_is_staging(),
			'siteId'         => nab_get_site_id(),
			'timezone'       => nab_get_timezone(),
		);

		wp_add_inline_script(
			'nab-heatmap-results-page',
			sprintf(
				$script,
				wp_json_encode( $settings )
			)
		);

	}//end add_heatmap_result_assets()

	// @Implements
	// phpcs:ignore
	public function display() {
		$title = $this->page_title;
		// phpcs:ignore
		include nelioab()->plugin_path . '/admin/views/nelio-ab-testing-results-page.php';
	}//end display()

	private function does_request_have_an_experiment() {

		return isset( $_GET['experiment'] ) && absint( $_GET['experiment'] ); // phpcs:ignore

	}//end does_request_have_an_experiment()

	private function remove_this_page_from_the_menu() {

		$this->remove_page_from_menu( 'nelio-ab-testing', $this->menu_slug );

	}//end remove_this_page_from_the_menu()

	private function remove_experiments_list_from_menu() {

		$this->remove_page_from_menu( 'nelio-ab-testing', 'edit.php?post_type=nab_experiment' );

	}//end remove_experiments_list_from_menu()

}//end class
