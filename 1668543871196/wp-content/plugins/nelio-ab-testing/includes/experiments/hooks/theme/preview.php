<?php

namespace Nelio_AB_Testing\Experiment_Library\Theme_Experiment;

use \add_filter;
use \add_query_arg;
use \Nelio_AB_Testing_Experiment_Helper;

defined( 'ABSPATH' ) || exit;

function get_preview_link( $preview_link, $alternative, $control, $experiment_id, $alternative_id ) {

	$theme_id = '';
	if ( isset( $alternative['themeId'] ) ) {
		$theme_id = $alternative['themeId'];
	}//end if

	$theme = wp_get_theme( $theme_id );
	if ( ! $theme ) {
		return false;
	}//end if

	$experiment = nab_get_experiment( $experiment_id );
	$scope      = $experiment->get_scope();
	return Nelio_AB_Testing_Experiment_Helper::instance()->get_preview_url_from_scope( $scope, $experiment_id, $alternative_id );

}//end get_preview_link()
add_filter( 'nab_nab/theme_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );

add_action( 'nab_nab/theme_preview_alternative', __NAMESPACE__ . '\load_alternative' );

