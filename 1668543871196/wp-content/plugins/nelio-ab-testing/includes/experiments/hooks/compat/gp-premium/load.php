<?php

namespace Nelio_AB_Testing\Experiment_Library\Compat\GeneratePress_Premium;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \add_filter;
use \defined;

function use_proper_source( $alternative, $control ) {

	if ( $control['postId'] === $alternative['postId'] ) {
		return;
	}//end if

	add_filter(
		'generate_dynamic_element_source_id',
		function ( $id ) use ( $alternative, $control ) {
			return $id === $control['postId'] ? $alternative['postId'] : $id;
		}
	);

}//end use_proper_source()

add_action(
	'plugins_loaded',
	function() {
		if ( ! defined( 'GP_PREMIUM_VERSION' ) ) {
			return;
		}//end if
		add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\use_proper_source', 10, 2 );
		add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\use_proper_source', 10, 2 );
		add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\use_proper_source', 10, 2 );
	}
);

