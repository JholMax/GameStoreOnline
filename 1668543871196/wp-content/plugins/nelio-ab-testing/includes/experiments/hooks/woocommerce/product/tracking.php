<?php

namespace Nelio_AB_Testing\Experiment_Library\WooCommerce\Product_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_filter;

function add_tracking_hooks() {

	$exps_with_loaded_alts = array();

	add_filter( 'nab_nab/wc-product_track_page_views_in_footer', '__return_true' );

	$save_loaded_alternative_for_triggering_page_view_events_later = function( $alternative, $control, $experiment_id ) use ( &$exps_with_loaded_alts ) {

		add_filter(
			'the_title',
			function( $title, $post_id ) use ( $alternative, $control, $experiment_id, &$exps_with_loaded_alts ) {
				if ( $post_id === $control['postId'] && ! in_array( $experiment_id, $exps_with_loaded_alts, true ) ) {
					array_push( $exps_with_loaded_alts, $experiment_id );
				}//end if
				return $title;
			},
			10,
			2
		);

	};
	add_action( 'nab_nab/wc-product_load_alternative', $save_loaded_alternative_for_triggering_page_view_events_later, 10, 3 );

	add_filter(
		'nab_nab/wc-product_should_trigger_page_view',
		function( $result, $alternative, $control, $experiment_id ) use ( &$exps_with_loaded_alts ) {
			return in_array( $experiment_id, $exps_with_loaded_alts, true );
		},
		10,
		4
	);

}//end add_tracking_hooks()
add_tracking_hooks();
