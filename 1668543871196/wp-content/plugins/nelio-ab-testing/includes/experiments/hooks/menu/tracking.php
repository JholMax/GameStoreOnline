<?php
namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_filter;

function add_tracking_hooks() {

	$exps_with_loaded_alts = array();

	add_filter( 'nab_nab/menu_track_page_views_in_footer', '__return_true' );

	add_action(
		'nab_nab/menu_load_alternative',
		function( $alternative, $control, $experiment_id ) use ( &$exps_with_loaded_alts ) {

			add_filter(
				'wp_get_nav_menu_items',
				function( $items, $menu ) use ( $control, $experiment_id, &$exps_with_loaded_alts ) {
					if ( $menu->term_id === $control['menuId'] ) {
						array_push( $exps_with_loaded_alts, $experiment_id );
					}//end if
					return $items;
				},
				10,
				2
			);
		},
		10,
		3
	);

	add_filter(
		'nab_nab/menu_should_trigger_page_view',
		function( $result, $alternative, $control, $experiment_id ) use ( &$exps_with_loaded_alts ) {
			return in_array( $experiment_id, $exps_with_loaded_alts, true );
		},
		10,
		4
	);

}//end add_tracking_hooks()
add_tracking_hooks();

