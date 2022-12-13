<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

use \add_action;
use \add_filter;
use \update_post_meta;
use \wp_delete_post;

use \Nelio_AB_Testing_Post_Helper;

defined( 'ABSPATH' ) || exit;

function get_alternative_post_attribute() {
	return 'postId';
}//end get_alternative_post_attribute()
add_filter( 'nab_nab/page_alternative_post_attribute', __NAMESPACE__ . '\get_alternative_post_attribute' );
add_filter( 'nab_nab/post_alternative_post_attribute', __NAMESPACE__ . '\get_alternative_post_attribute' );
add_filter( 'nab_nab/custom-post-type_alternative_post_attribute', __NAMESPACE__ . '\get_alternative_post_attribute' );

function get_tested_element( $tested_element, $control ) {
	return $control['postId'];
}//end get_tested_element()
add_filter( 'nab_nab/page_get_tested_element', __NAMESPACE__ . '\get_tested_element', 10, 2 );
add_filter( 'nab_nab/post_get_tested_element', __NAMESPACE__ . '\get_tested_element', 10, 2 );
add_filter( 'nab_nab/custom-post-type_get_tested_element', __NAMESPACE__ . '\get_tested_element', 10, 2 );

function remove_alternative_content( $alternative, $experiment_id ) {

	if ( ! empty( $alternative['isExistingContent'] ) ) {
		return;
	}//end if

	if ( ! empty( $alternative['testAgainstExistingContent'] ) ) {
		return;
	}//end if

	wp_delete_post( $alternative['postId'], true );

}//end remove_alternative_content()
add_action( 'nab_nab/page_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content', 10, 2 );
add_action( 'nab_nab/post_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content', 10, 2 );
add_action( 'nab_nab/custom-post-type_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content', 10, 2 );

function create_alternative_content( $alternative, $control, $experiment_id ) {

	if ( ! empty( $alternative['isExistingContent'] ) ) {
		return $alternative;
	}//end if

	$post_helper = Nelio_AB_Testing_Post_Helper::instance();

	$new_post_id = $post_helper->duplicate( $control['postId'] );
	if ( is_wp_error( $new_post_id ) ) {
		return $alternative;
	}//end if

	update_post_meta( $new_post_id, '_nab_experiment', $experiment_id );
	$alternative['postId'] = $new_post_id;

	return $alternative;

}//end create_alternative_content()
add_filter( 'nab_nab/page_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );
add_filter( 'nab_nab/post_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );
add_filter( 'nab_nab/custom-post-type_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );

// Duplicating content is exactly the same as creating it from scratch, as long as “control” is set to the “old alternative” (which it is).
add_filter( 'nab_nab/page_duplicate_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );
add_filter( 'nab_nab/post_duplicate_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );
add_filter( 'nab_nab/custom-post-type_duplicate_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );

function backup_control( $alternative, $control, $experiment_id ) {
	return empty( $alternative['testAgainstExistingContent'] )
		? create_alternative_content( $alternative, $control, $experiment_id )
		: $alternative;
}//end backup_control()
add_filter( 'nab_nab/page_backup_control', __NAMESPACE__ . '\backup_control', 10, 3 );
add_filter( 'nab_nab/post_backup_control', __NAMESPACE__ . '\backup_control', 10, 3 );
add_filter( 'nab_nab/custom-post-type_backup_control', __NAMESPACE__ . '\backup_control', 10, 3 );

function apply_alternative( $applied, $alternative, $control ) {

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		return false;
	}//end if

	$tested_element = get_post( $control['postId'] );
	if ( empty( $tested_element ) || is_wp_error( $tested_element ) ) {
		return false;
	}//end if

	$alternative_post = get_post( $alternative['postId'] );
	if ( empty( $alternative_post ) || is_wp_error( $alternative_post ) ) {
		return false;
	}//end if

	$post_helper = Nelio_AB_Testing_Post_Helper::instance();
	$post_helper->overwrite( $control['postId'], $alternative['postId'] );
	return true;

}//end apply_alternative()
add_filter( 'nab_nab/page_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );
add_filter( 'nab_nab/post_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );
add_filter( 'nab_nab/custom-post-type_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );

// Heatmap link is essentially the preview link which will need some extra params to load the heatmap renderer on top of it.
add_filter( 'nab_nab/page_heatmap_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );
add_filter( 'nab_nab/post_heatmap_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );
add_filter( 'nab_nab/custom-post-type_heatmap_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );

