<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_filter;
use \function_exists;
use \is_home;
use \is_singular;
use \nab_get_experiment;
use \nab_get_queried_object_id;
use \wp_list_pluck;

add_filter( 'nab_nab/page_should_trigger_page_view', __NAMESPACE__ . '\is_current_post_under_test', 10, 4 );
add_filter( 'nab_nab/post_should_trigger_page_view', __NAMESPACE__ . '\is_current_post_under_test', 10, 4 );
add_filter( 'nab_nab/custom-post-type_should_trigger_page_view', __NAMESPACE__ . '\is_current_post_under_test', 10, 4 );

add_filter( 'nab_nab/page_should_track_heatmap', __NAMESPACE__ . '\is_current_post_under_test', 10, 4 );
add_filter( 'nab_nab/post_should_track_heatmap', __NAMESPACE__ . '\is_current_post_under_test', 10, 4 );
add_filter( 'nab_nab/custom-post-type_should_track_heatmap', __NAMESPACE__ . '\is_current_post_under_test', 10, 4 );

function is_current_post_under_test( $_, $__, $___, $experiment_id ) {
	$post_id  = get_current_post_id();
	$post_ids = get_alternative_post_ids( $experiment_id );
	return in_array( $post_id, $post_ids, true );
}//end is_current_post_under_test()

function get_current_post_id() {
	if ( ! is_singular() ) {
		return 0;
	}//end if

	if ( is_home() ) {
		return get_option( 'page_on_front', 0 );
	}//end if

	if ( function_exists( 'is_shop' ) && function_exists( 'wc_get_page_id' ) && is_shop() ) {
		return absint( wc_get_page_id( 'shop' ) );
	}//end if

	return nab_get_queried_object_id();
}//end get_current_post_id()

function get_alternative_post_ids( $experiment_id ) {
	$experiment   = nab_get_experiment( $experiment_id );
	$alternatives = $experiment->get_alternatives();
	$alternatives = wp_list_pluck( $alternatives, 'attributes' );

	$post_ids = wp_list_pluck( $alternatives, 'postId' );
	$post_ids = array_map( 'absint', $post_ids );
	return array_values( array_filter( $post_ids ) );
}//end get_alternative_post_ids()
