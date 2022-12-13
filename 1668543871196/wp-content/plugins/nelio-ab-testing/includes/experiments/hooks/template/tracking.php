<?php
namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_filter;

function should_trigger_page_view( $result, $alternative, $control ) {

	if ( ! is_singular() ) {
		return false;
	}//end if

	if ( get_post_type() !== $control['postType'] ) {
		return false;
	}//end if

	$actual_template = get_actual_template( get_the_ID() );
	if ( empty( $actual_template ) || 'default' === $actual_template ) {
		$actual_template = '';
	}//end if

	$expected_template = $control['templateId'];
	if ( '_nab_default_template' === $expected_template ) {
		$expected_template = '';
	}//end if

	return $actual_template === $expected_template;

}//end should_trigger_page_view()
add_filter( 'nab_nab/template_should_trigger_page_view', __NAMESPACE__ . '\should_trigger_page_view', 10, 3 );
