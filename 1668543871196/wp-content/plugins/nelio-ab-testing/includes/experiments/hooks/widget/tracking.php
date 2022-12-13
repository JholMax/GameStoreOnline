<?php
namespace Nelio_AB_Testing\Experiment_Library\Widget_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \add_filter;

function add_tracking_hooks() {

	$exps_with_loaded_alts = array();

	add_filter( 'nab_nab/widget_track_page_views_in_footer', '__return_true' );

	add_action(
		'nab_nab/widget_load_alternative',
		function( $alternative, $control, $experiment_id ) use ( &$exps_with_loaded_alts ) {

			add_action(
				'dynamic_sidebar_after',
				function() use ( $experiment_id, &$exps_with_loaded_alts ) {
					array_push( $exps_with_loaded_alts, $experiment_id );
				}
			);
		},
		10,
		3
	);

	add_filter(
		'nab_nab/widget_should_trigger_page_view',
		function( $result, $alternative, $control, $experiment_id ) use ( &$exps_with_loaded_alts ) {
			return in_array( $experiment_id, $exps_with_loaded_alts, true );
		},
		10,
		4
	);

}//end add_tracking_hooks()
add_tracking_hooks();

