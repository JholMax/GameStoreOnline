<?php

namespace Nelio_AB_Testing\Experiment_Library\Compat\Leadpages;

defined( 'ABSPATH' ) || exit;

use \add_action;

function should_trigger_page_view( $result, $alternative, $control ) {

	if ( 'leadpages_post' !== $control['postType'] ) {
		return $result;
	}//end if

	if ( ! is_singular() ) {
		return $result;
	}//end if

	if ( ! in_array( get_the_ID(), array( $control['postId'], $alternative['postId'] ), true ) ) {
		return $result;
	}//end if

	if ( is_home() && absint( get_option( 'leadpages_front_page_id' ) ) === $control['postId'] ) {
		return $result;
	}//end if

	return true;

}//end should_trigger_page_view()
add_action( 'nab_nab/custom-post-type_should_trigger_page_view', __NAMESPACE__ . '\should_trigger_page_view', 10, 3 );

function should_track_heatmap( $result, $alternative, $control ) {

	if ( 'leadpages_post' !== $control['postType'] ) {
		return $result;
	}//end if

	if ( ! is_singular() ) {
		return $result;
	}//end if

	if ( ! in_array( get_the_ID(), array( $control['postId'], $alternative['postId'] ), true ) ) {
		return $result;
	}//end if

	if ( is_home() && absint( get_option( 'leadpages_front_page_id' ) ) === $control['postId'] ) {
		return $result;
	}//end if

	return true;

}//end should_track_heatmap()
add_action( 'nab_nab/custom-post-type_should_track_heatmap', __NAMESPACE__ . '\should_track_heatmap', 10, 3 );

