<?php

namespace Nelio_AB_Testing\Experiment_Library\Widget_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \add_filter;
use \array_filter;
use \array_keys;
use \str_replace;
use \strpos;

add_action( 'nab_is_nab/widget_priority_experiment', '__return_true' );

function load_alternative( $alternative, $control, $experiment_id, $alternative_id ) {

	if ( 'control' === $alternative_id ) {
		return;
	}//end if

	$prefix = get_sidebar_prefix( $experiment_id, $alternative_id );

	add_filter(
		'sidebars_widgets',
		function( $sidebars_widgets ) use ( $prefix, $alternative_id ) {

			$sidebars_widgets = array_filter(
				$sidebars_widgets,
				function ( $sidebar ) use ( $prefix ) {
					return 0 === strpos( $sidebar, $prefix );
				},
				ARRAY_FILTER_USE_KEY
			);

			$keys = array_keys( $sidebars_widgets );
			foreach ( $keys as $key ) {
				$new_key                      = str_replace( $prefix, '', $key );
				$sidebars_widgets[ $new_key ] = $sidebars_widgets[ $key ];
				unset( $sidebars_widgets[ $key ] );
			}//end foreach

			return $sidebars_widgets;

		}
	);

}//end load_alternative()
add_action( 'nab_nab/widget_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 4 );
