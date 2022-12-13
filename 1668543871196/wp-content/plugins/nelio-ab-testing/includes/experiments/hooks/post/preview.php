<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

use \add_filter;
use \add_query_arg;
use \get_permalink;

defined( 'ABSPATH' ) || exit;

function get_preview_link( $preview_link, $alternative, $control, $experiment_id, $alternative_id ) {

	$link = empty( $control['testAgainstExistingContent'] )
		? get_permalink( $control['postId'] )
		: get_permalink( $alternative['postId'] );

	if ( ! $link ) {
		return false;
	}//end if

	return $link;

}//end get_preview_link()
add_filter( 'nab_nab/page_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );
add_filter( 'nab_nab/post_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );
add_filter( 'nab_nab/custom-post-type_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );

add_filter( 'nab_nab/page_preview_alternative', __NAMESPACE__ . '\load_alternative', 10, 2 );
add_filter( 'nab_nab/post_preview_alternative', __NAMESPACE__ . '\load_alternative', 10, 2 );
add_filter( 'nab_nab/custom-post-type_preview_alternative', __NAMESPACE__ . '\load_alternative', 10, 2 );


function maybe_overwrite_native_preview_post_link( $link, $post ) {
	$post_id = $post->ID;
	$exp_id  = absint( get_post_meta( $post_id, '_nab_experiment', true ) );
	if ( empty( $exp_id ) ) {
		return $link;
	}//end if

	$exp = nab_get_experiment( $exp_id );
	if ( empty( $exp ) ) {
		return $link;
	}//end if

	$types = array( 'nab/page', 'nab/post', 'nab/custom-post-type' );
	if ( ! in_array( $exp->get_type(), $types, true ) ) {
		return $link;
	}//end if

	$alts = $exp->get_alternatives();
	if ( ! is_array( $alts ) ) {
		return $link;
	}//end if

	$alts = array_filter(
		$alts,
		function ( $a ) use ( $post_id ) {
			return (
				isset( $a['attributes'] ) &&
				isset( $a['attributes']['postId'] ) &&
				absint( $a['attributes']['postId'] ) === $post_id
			);
		}
	);
	$alts = array_values( $alts );
	$alt  = empty( $alts ) ? null : $alts[0];

	return (
		empty( $alt ) || empty( $alt['links'] ) || empty( $alt['links']['preview'] )
			? $link
			: $alt['links']['preview']
	);
}//end maybe_overwrite_native_preview_post_link()
add_filter( 'preview_post_link', __NAMESPACE__ . '\maybe_overwrite_native_preview_post_link', 10, 2 );
