<?php

namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

function get_default_attributes_in_control( $alternative ) {
	return array(
		'templateId' => '',
		'postType'   => '',
	);
}//end get_default_attributes_in_control()
add_filter( 'nab_nab/template_get_default_attributes_in_control', __NAMESPACE__ . '\get_default_attributes_in_control' );

function get_default_attributes_in_alternative( $alternative ) {
	return array(
		'name'       => '',
		'templateId' => '',
	);
}//end get_default_attributes_in_alternative()
add_filter( 'nab_nab/template_get_default_attributes_in_alternative', __NAMESPACE__ . '\get_default_attributes_in_alternative' );
