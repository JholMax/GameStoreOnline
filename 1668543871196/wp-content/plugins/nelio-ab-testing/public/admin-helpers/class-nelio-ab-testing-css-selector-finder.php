<?php
/**
 * A file to discover CSS selectors.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/public/admin-helpers
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds the required script for discovering CSS selectors.
 */
class Nelio_AB_Testing_Css_Selector_Finder {

	protected static $instance;

	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;

	}//end instance()

	public function init() {

		add_action( 'nab_public_init', array( $this, 'maybe_simulate_preview_params' ), 1 );
		add_filter( 'nab_disable_split_testing', array( $this, 'should_split_testing_be_disabled' ) );
		add_filter( 'nab_simulate_anonymous_visitor', array( $this, 'should_simulate_anonymous_visitor' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

	}//end init()

	public function maybe_simulate_preview_params() {

		if ( ! $this->should_css_selector_finder_be_loaded() ) {
			return;
		}//end if

		$experiment_id  = $this->get_experiment_id();
		$alternative_id = $this->get_alternative_id();
		$timestamp      = time();
		$secret         = nab_get_api_secret();
		$nonce          = md5( "nab-preview-{$experiment_id}-{$alternative_id}-{$timestamp}-{$secret}" );

		$_GET['nab-preview'] = true;
		$_GET['timestamp']   = $timestamp;
		$_GET['nonce']       = $nonce;

	}//end maybe_simulate_preview_params()

	public function should_split_testing_be_disabled( $disabled ) {

		if ( $this->should_css_selector_finder_be_loaded() ) {
			return true;
		}//end if

		return $disabled;

	}//end should_split_testing_be_disabled()

	public function should_simulate_anonymous_visitor( $anonymize ) {

		if ( $this->should_css_selector_finder_be_loaded() ) {
			return true;
		}//end if

		return $anonymize;

	}//end should_simulate_anonymous_visitor()

	public function enqueue_assets() {

		if ( ! $this->should_css_selector_finder_be_loaded() ) {
			return;
		}//end if

		$file_without_ext = '/assets/dist/js/css-selector-finder';
		nab_enqueue_script_with_auto_deps( 'nab-css-selector-finder', 'css-selector-finder', true );

		echo '<style type="text/css">';
		echo 'a.nab-disabled-link{';
		echo 'cursor:not-allowed';
		echo '}';
		echo '.nab-highlight{';
		echo 'background: rgba(85, 165, 230, 0.5);';
		echo 'border: 2px solid rgb(85, 165, 230);';
		echo 'position: absolute;';
		echo 'pointer-events: none;';
		echo 'z-index: 99999999;';
		echo '}';
		echo '</style>';

	}//end enqueue_assets()

	public function should_css_selector_finder_be_loaded() {

		if ( ! $this->get_experiment_id() ) {
			return false;
		}//end if

		if ( ! $this->get_alternative_id() ) {
			return false;
		}//end if

		return isset( $_GET['nab-css-selector-finder'] ); // phpcs:ignore

	}//end should_css_selector_finder_be_loaded()

	private function get_experiment_id() {

		if ( ! isset( $_GET['experiment'] ) ) { // phpcs:ignore
			return false;
		}//end if

		return absint( $_GET['experiment'] ); // phpcs:ignore

	}//end get_experiment_id()

	private function get_alternative_id() {

		if ( ! isset( $_GET['alternative'] ) ) { // phpcs:ignore
			return false;
		}//end if

		return sanitize_text_field( $_GET['alternative'] ); // phpcs:ignore

	}//end get_alternative_id()

}//end class
