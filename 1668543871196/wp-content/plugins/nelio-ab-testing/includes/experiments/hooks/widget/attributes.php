<?php

namespace Nelio_AB_Testing\Experiment_Library\Widget_Experiment;

defined( 'ABSPATH' ) || exit;

function get_default_attributes_in_alternative( $alternative ) {
	return array(
		'name'     => '',
		'sidebars' => array(),
	);
}//end get_default_attributes_in_alternative()
add_filter( 'nab_nab/widget_get_default_attributes_in_alternative', __NAMESPACE__ . '\get_default_attributes_in_alternative' );
