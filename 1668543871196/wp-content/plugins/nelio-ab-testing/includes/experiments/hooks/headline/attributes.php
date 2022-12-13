<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

function get_default_attributes_in_control() {
	return array(
		'postId'   => 0,
		'postType' => '',
	);
}//end get_default_attributes_in_control()
add_filter( 'nab_nab/headline_get_default_attributes_in_control', __NAMESPACE__ . '\get_default_attributes_in_control' );

function get_default_attributes_in_alternative() {
	return array(
		'name'     => '',
		'imageId'  => 0,
		'imageUrl' => '',
		'excerpt'  => '',
	);
}//end get_default_attributes_in_alternative()
add_filter( 'nab_nab/headline_get_default_attributes_in_alternative', __NAMESPACE__ . '\get_default_attributes_in_alternative' );
