<?php

namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \add_filter;

function maybe_exclude_experiment_from_loading( $is_excluded, $control ) {

	if ( ! is_singular() ) {
		return true;
	}//end if

	if ( get_post_type() !== $control['postType'] ) {
		return true;
	}//end if

	$current_template = get_actual_template( get_the_ID() );
	if ( empty( $current_template ) || 'default' === $current_template ) {
		$current_template = '_nab_default_template';
	}//end if

	if ( $control['templateId'] !== $current_template ) {
		return true;
	}//end if

	return $is_excluded;

}//end maybe_exclude_experiment_from_loading()
add_action( 'nab_nab/template_exclude_experiment_from_loading', __NAMESPACE__ . '\maybe_exclude_experiment_from_loading', 10, 2 );


function load_alternative( $alternative, $control ) {

	if ( $alternative['templateId'] === $control['templateId'] ) {
		return;
	}//end if

	add_filter(
		'get_post_metadata',
		function( $value, $object_id, $meta_key ) use ( $alternative, $control ) {

			if ( '_wp_page_template' !== $meta_key ) {
				return $value;
			}//end if
			if ( get_post_type( $object_id ) !== $control['postType'] ) {
				return $value;
			}//end if

			$value = get_actual_template( $object_id );
			if ( '_nab_default_template' === $control['templateId'] ) {
				if ( empty( $value ) || 'default' === $value ) {
					return $alternative['templateId'];
				}//end if
				return $value;
			}//end if

			if ( $value !== $control['templateId'] ) {
				return $value;
			}//end if

			if ( '_nab_default_template' === $alternative['templateId'] ) {
				return null;
			}//end if

			return $alternative['templateId'];
		},
		10,
		3
	);

}//end load_alternative()
add_action( 'nab_nab/template_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 2 );
