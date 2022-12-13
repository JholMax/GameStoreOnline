<?php
/**
 * This file contains the class that registers the help menu item in Nelio A/B Testing.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class that registers the help menu item in Nelio A/B Testing.
 */
class Nelio_AB_Testing_Help_Page extends Nelio_AB_Testing_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-ab-testing',
			_x( 'Help', 'text', 'nelio-ab-testing' ),
			_x( 'Help', 'text', 'nelio-ab-testing' ),
			'edit_others_posts',
			'nelio-ab-testing-help'
		);

	}//end __construct()

	// @Overrides
	// phpcs:ignore
	public function add_page() {

		parent::add_page();

		global $submenu;
		if ( isset( $submenu['nelio-ab-testing'] ) ) {
			$count = count( $submenu['nelio-ab-testing'] );
			for ( $i = 0; $i < $count; ++$i ) {
				if ( 'nelio-ab-testing-help' === $submenu['nelio-ab-testing'][ $i ][2] ) {
					$submenu['nelio-ab-testing'][ $i ][2] = add_query_arg( // phpcs:ignore
						array(
							'utm_source'   => 'nelio-ab-testing',
							'utm_medium'   => 'plugin',
							'utm_campaign' => 'support',
							'utm_content'  => 'overview-help',
						),
						_x( 'https://neliosoftware.com/testing/help/', 'text', 'nelio-ab-testing' )
					);
					break;
				}//end if
			}//end for
		}//end if

	}//end add_page()

	// @Implements
	// phpcs:ignore
	public function enqueue_assets() {
		// Nothing to be done.
	}//end enqueue_assets()

	// @Implements
	// phpcs:ignore
	public function display() {
		// Nothing to be done.
	}//end display()

}//end class
