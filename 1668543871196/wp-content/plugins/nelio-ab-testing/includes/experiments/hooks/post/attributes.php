<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

function get_default_attributes_in_control( $alternative ) {
	return array(
		'postId'   => 0,
		'postType' => '',
	);
}//end get_default_attributes_in_control()
add_filter( 'nab_nab/page_get_default_attributes_in_control', __NAMESPACE__ . '\get_default_attributes_in_control' );
add_filter( 'nab_nab/post_get_default_attributes_in_control', __NAMESPACE__ . '\get_default_attributes_in_control' );
add_filter( 'nab_nab/custom-post-type_get_default_attributes_in_control', __NAMESPACE__ . '\get_default_attributes_in_control' );

function get_default_attributes_in_alternative( $alternative ) {
	return array(
		'name'   => '',
		'postId' => 0,
	);
}//end get_default_attributes_in_alternative()
add_filter( 'nab_nab/page_get_default_attributes_in_alternative', __NAMESPACE__ . '\get_default_attributes_in_alternative' );
add_filter( 'nab_nab/post_get_default_attributes_in_alternative', __NAMESPACE__ . '\get_default_attributes_in_alternative' );
add_filter( 'nab_nab/custom-post-type_get_default_attributes_in_alternative', __NAMESPACE__ . '\get_default_attributes_in_alternative' );
