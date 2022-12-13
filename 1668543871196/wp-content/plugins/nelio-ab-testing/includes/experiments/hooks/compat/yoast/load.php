<?php

namespace Nelio_AB_Testing\Experiment_Library\Compat\Yoast;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \add_filter;

function use_control_seo_data( $alternative, $control ) {

	if ( $control['postId'] === $alternative['postId'] ) {
		return;
	}//end if

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		return;
	}//end if

	$control_id     = $control['postId'];
	$alternative_id = $alternative['postId'];

	add_action(
		'wpseo_head',
		function() use ( $control_id, $alternative_id ) {
			global $wp_query;
			if ( $alternative_id !== $wp_query->queried_object_id ) {
				return;
			}//end if

			$wp_query->queried_object_id = $control_id;
			$wp_query->posts[0]->ID      = $control_id;
		},
		-99999
	);

	add_action(
		'wpseo_head',
		function() use ( $control_id, $alternative_id ) {
			global $wp_query;
			if ( $control_id !== $wp_query->queried_object_id ) {
				return;
			}//end if

			$wp_query->queried_object_id = $alternative_id;
			$wp_query->posts[0]->ID      = $alternative_id;
		},
		99999
	);

}//end use_control_seo_data()

add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\use_control_seo_data', 10, 2 );
add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\use_control_seo_data', 10, 2 );
add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\use_control_seo_data', 10, 2 );

