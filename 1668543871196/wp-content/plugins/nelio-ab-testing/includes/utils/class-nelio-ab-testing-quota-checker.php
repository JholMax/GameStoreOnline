<?php
/**
 * This file contains a class for checking the quota periodically.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class checks the quota periodically.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      5.0.0
 */
class Nelio_AB_Testing_Quota_Checker {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @access protected
	 * @var    Nelio_AB_Testing_Quota_Checker
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Quota_Checker the single instance of this class.
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

		add_action( 'nab_start_experiment', array( $this, 'check_quota' ), 99 );
		add_action( 'nab_check_quota', array( $this, 'check_quota' ) );

	}//end init()

	public function check_quota() {

		if ( 'enterprise' !== nab_get_subscription() ) {
			return;
		}//end if

		$settings                    = Nelio_AB_Testing_Settings::instance();
		$notify_no_more_quota        = $settings->get( 'notify_no_more_quota' );
		$notify_almost_no_more_quota = $settings->get( 'notify_almost_no_more_quota' );

		if ( ! $notify_no_more_quota && ! $notify_almost_no_more_quota ) {
			return;
		}//end if

		$quota_data      = nab_get_quota();
		$quota_per_month = $quota_data['quota_per_month'];
		$available_quota = $quota_data['quota'] + $quota_data['quota_extra'];

		$last_quota_notification_sent = get_option( 'nab_last_quota_notification_sent' );

		if ( 0 === $available_quota && $notify_no_more_quota && 'no_more_quota' !== $last_quota_notification_sent ) {

			$mailer = Nelio_AB_Testing_Mailer::instance();
			$mailer->send_no_more_quota_notification();
			update_option( 'nab_last_quota_notification_sent', 'no_more_quota' );
			$this->maybe_schedule_next_quota_check( time() + DAY_IN_SECONDS );
			return;

		}//end if

		// Less than 20% of monthly quota.
		if ( $quota_per_month * 0.2 > $available_quota && $notify_almost_no_more_quota && 'almost_no_more_quota' !== $last_quota_notification_sent ) {

			$mailer = Nelio_AB_Testing_Mailer::instance();
			$mailer->send_almost_no_more_quota_notification();
			update_option( 'nab_last_quota_notification_sent', 'almost_no_more_quota' );
			$this->maybe_schedule_next_quota_check( time() + HOUR_IN_SECONDS );
			return;

		}//end if

		if ( 'no_more_quota' === $last_quota_notification_sent && 0 < $available_quota ) {

			// Reset option for last quota notification sent.
			delete_option( 'nab_last_quota_notification_sent' );
			$this->maybe_schedule_next_quota_check( time() + DAY_IN_SECONDS );
			return;

		}//end if

		if ( 'almost_no_more_quota' === $last_quota_notification_sent && $quota_per_month * 0.2 <= $available_quota ) {

			// Reset option for last quota notification sent.
			delete_option( 'nab_last_quota_notification_sent' );
			$this->maybe_schedule_next_quota_check( time() + HOUR_IN_SECONDS );
			return;

		}//end if

	}//end check_quota()

	private function maybe_schedule_next_quota_check( $next_check_time ) {

		if ( nab_are_there_experiments_running() ) {
			wp_schedule_single_event( $next_check_time, 'nab_check_quota' );
		}//end if

	}//end maybe_schedule_next_quota_check()

}//end class
