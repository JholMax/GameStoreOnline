<?php

namespace Nelio_AB_Testing\Experiment_Library\Theme_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \add_filter;
use \get_option;
use \is_array;
use \wp_get_theme;

add_action( 'nab_is_nab/theme_priority_experiment', '__return_true' );

function load_alternative( $alternative ) {

	$theme_id = '';
	if ( isset( $alternative['themeId'] ) ) {
		$theme_id = $alternative['themeId'];
	}//end if

	$theme = wp_get_theme( $theme_id );
	if ( ! $theme ) {
		return;
	}//end if

	add_filter(
		'option_stylesheet',
		function( $stylesheet ) use ( $theme ) {
			return $theme['Stylesheet'];
		}
	);

	add_filter(
		'option_template',
		function( $template ) use ( $theme ) {
			return $theme['Template'];
		}
	);

}//end load_alternative()
add_action( 'nab_nab/theme_load_alternative', __NAMESPACE__ . '\load_alternative' );
