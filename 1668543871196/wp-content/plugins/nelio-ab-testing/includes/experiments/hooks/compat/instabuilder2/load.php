<?php

namespace Nelio_AB_Testing\Experiment_Library\Compat\Instabuilder2;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \add_filter;

function load_alternative_content( $alternative, $control ) {

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		return;
	}//end if

	$control_id     = $control['postId'];
	$alternative_id = $alternative['postId'];

	if ( $control_id === $alternative_id ) {
		return;
	}//end if

	if ( ! ib2_page_exists( $control_id ) ) {
		return;
	}//end if

	add_filter( 'nab_use_control_id_in_alternative', '__return_false' );

}//end load_alternative_content()

add_action(
	'plugins_loaded',
	function() {
		if ( ! defined( 'IB2_VERSION' ) ) {
			return;
		}//end if
		add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\load_alternative_content', 1, 2 );
	}
);

