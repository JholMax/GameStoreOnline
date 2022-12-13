<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

function get_default_attributes_in_control( $alternative ) {
	return array(
		'menuId' => 0,
	);
}//end get_default_attributes_in_control()
add_filter( 'nab_nab/menu_get_default_attributes_in_control', __NAMESPACE__ . '\get_default_attributes_in_control' );

function get_default_attributes_in_alternative( $alternative ) {
	return array(
		'name'   => '',
		'menuId' => 0,
	);
}//end get_default_attributes_in_alternative()
add_filter( 'nab_nab/menu_get_default_attributes_in_alternative', __NAMESPACE__ . '\get_default_attributes_in_alternative' );
