<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

use \add_action;
use \add_filter;
use \Nelio_AB_Testing_Experiment_Helper;

defined( 'ABSPATH' ) || exit;

function get_preview_link( $preview_link, $alternative, $control, $experiment_id, $alternative_id ) {

	$experiment = nab_get_experiment( $experiment_id );
	$scope      = $experiment->get_scope();
	return Nelio_AB_Testing_Experiment_Helper::instance()->get_preview_url_from_scope( $scope, $experiment_id, $alternative_id );

}//end get_preview_link()
add_filter( 'nab_nab/css_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );

add_action( 'nab_nab/css_preview_alternative', __NAMESPACE__ . '\load_alternative' );
