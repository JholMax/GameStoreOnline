<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \add_filter;
use \get_permalink;

function get_preview_link( $preview_link, $alternative, $control, $experiment_id, $alternative_id ) {

	$link = get_permalink( $control['postId'] );
	if ( ! $link ) {
		return false;
	}//end if

	return $link;

}//end get_preview_link()
add_filter( 'nab_nab/headline_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );

add_action( 'nab_nab/headline_preview_alternative', __NAMESPACE__ . '\load_alternative', 10, 4 );
