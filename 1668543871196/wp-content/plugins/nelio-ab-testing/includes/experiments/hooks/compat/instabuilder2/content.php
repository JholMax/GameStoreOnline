<?php

namespace Nelio_AB_Testing\Experiment_Library\Compat\Instabuilder2;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \add_filter;
use \class_exists;

function duplicate_post( $result, $src_id ) {

	if ( ! ib2_page_exists( $src_id ) ) {
		return $result;
	}//end if

	$post_id = ib2_clone_page( $src_id );
	if ( is_wp_error( $post_id ) ) {
		return $result;
	}//end if

	return $post_id;

}//end duplicate_post()

add_action(
	'plugins_loaded',
	function() {
		if ( ! defined( 'IB2_VERSION' ) ) {
			return;
		}//end if
		add_filter( 'nab_duplicate_post_pre', __NAMESPACE__ . '\duplicate_post', 10, 2 );
	}
);

