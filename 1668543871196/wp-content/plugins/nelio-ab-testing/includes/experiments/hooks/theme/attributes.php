<?php

namespace Nelio_AB_Testing\Experiment_Library\Theme_Experiment;

defined( 'ABSPATH' ) || exit;

function get_default_attributes_in_alternative( $alternative ) {
	return array(
		'name'    => '',
		'themeId' => '',
	);
}//end get_default_attributes_in_alternative()
add_filter( 'nab_nab/theme_get_default_attributes_in_alternative', __NAMESPACE__ . '\get_default_attributes_in_alternative' );
