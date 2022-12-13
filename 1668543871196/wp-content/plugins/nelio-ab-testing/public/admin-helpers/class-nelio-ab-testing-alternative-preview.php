<?php
/**
 * This class checks if there's a special parameter in the URL that tells
 * WordPress that an alternative should be previewed. If it exists, then a
 * special filter runs so that the associated experiment type can add the
 * expected hooks to show the variant.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/public/admin-helpers
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds the required script for previewing CSS snippets.
 */
class Nelio_AB_Testing_Alternative_Preview {

	protected static $instance;

	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;

	}//end instance()

	public function init() {

		add_filter( 'body_class', array( $this, 'maybe_add_preview_class' ) );
		add_action( 'nab_public_init', array( $this, 'run_preview_hook_if_preview_mode_is_active' ) );
		add_filter( 'nab_disable_split_testing', array( $this, 'should_split_testing_be_disabled' ) );
		add_filter( 'nab_simulate_anonymous_visitor', array( $this, 'should_simulate_anonymous_visitor' ) );

	}//end init()

	public function maybe_add_preview_class( $classes ) {
		if ( ! nab_is_preview() ) {
			return $classes;
		}//end if
		$classes = array_merge( $classes, array( 'nab-preview' ) );
		return array_values( array_unique( $classes ) );
	}//end maybe_add_preview_class()

	public function should_split_testing_be_disabled( $disabled ) {

		if ( nab_is_preview() ) {
			return true;
		}//end if

		return $disabled;

	}//end should_split_testing_be_disabled()

	public function should_simulate_anonymous_visitor( $anonymize ) {

		if ( nab_is_preview() ) {
			return true;
		}//end if

		return $anonymize;

	}//end should_simulate_anonymous_visitor()

	public function run_preview_hook_if_preview_mode_is_active() {

		if ( ! nab_is_preview() ) {
			return;
		}//end if

		if ( ! $this->is_preview_mode_valid() ) {
			wp_die( esc_html_x( 'Preview link expired.', 'text', 'nelio-ab-testing' ), 400 );
		}//end if

		$experiment_id  = $this->get_experiment_id();
		$alternative_id = $this->get_alternative_id();

		$experiment = nab_get_experiment( $experiment_id );
		if ( empty( $experiment ) ) {
			return;
		}//end if

		if ( 'finished' === $experiment->get_status() && 'control' === $alternative_id ) {
			$alternative_id = 'control_backup';
		}//end if

		$alternative = $experiment->get_alternative( $alternative_id );
		if ( empty( $alternative ) ) {
			return;
		}//end if

		$control         = $experiment->get_alternative( 'control' );
		$experiment_type = $experiment->get_type();

		/**
		 * Fires when a certain alternative is about to be previewed.
		 *
		 * Use this action to add any hooks that your experiment type might require in order
		 * to properly visualize the alternative.
		 *
		 * @param array  $alternative    attributes of the active alternative.
		 * @param array  $control        attributes of the control version.
		 * @param int    $experiment_id  experiment ID.
		 * @param string $alternative_id alternative ID.
		 *
		 * @since 5.0.0
		 */
		do_action( "nab_{$experiment_type}_preview_alternative", $alternative['attributes'], $control['attributes'], $experiment_id, $alternative_id );

	}//end run_preview_hook_if_preview_mode_is_active()

	private function is_preview_mode_valid() {

		$experiment_id  = $this->get_experiment_id();
		$alternative_id = $this->get_alternative_id();
		$timestamp      = $this->get_timestamp();
		$nonce          = $this->get_nonce();
		$secret         = nab_get_api_secret();

		if ( md5( "nab-preview-{$experiment_id}-{$alternative_id}-{$timestamp}-{$secret}" ) !== $nonce ) {
			return false;
		}//end if

		/**
		 * Filters the alternative preview duration in minutes. If set to 0, the preview link never expires.
		 *
		 * @param number $duration Duration in minutes. If 0, the preview link never expires. Default: 30.
		 *
		 * @since 5.1.2
		 */
		$duration = absint( apply_filters( 'nab_alternative_preview_link_duration', 30 ) );
		if ( ! empty( $duration ) && 60 * $duration < absint( time() - $timestamp ) ) {
			return false;
		}//end if

		return true;

	}//end is_preview_mode_valid()

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

	private function get_timestamp() {

		if ( ! isset( $_GET['timestamp'] ) ) { // phpcs:ignore
			return false;
		}//end if

		return absint( $_GET['timestamp'] ); // phpcs:ignore

	}//end get_timestamp()

	private function get_nonce() {

		if ( ! isset( $_GET['nonce'] ) ) { // phpcs:ignore
			return false;
		}//end if

		return sanitize_text_field( $_GET['nonce'] ); // phpcs:ignore

	}//end get_nonce()

}//end class
