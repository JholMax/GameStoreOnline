<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with SG Optimizer.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/library
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      5.0.17
 */

namespace Nelio_AB_Testing\Experiment_Library\Compat\SG_Optimizer;

defined( 'ABSPATH' ) || exit;

function js_exclude( $exclude_list ) {
	$exclude_list[] = 'nelio-ab-testing-main';
	$exclude_list[] = 'nelio-ab-testing-alternative-loader';

	return $exclude_list;
}//end js_exclude()
add_filter( 'sgo_js_minify_exclude', __NAMESPACE__ . '\js_exclude' );
add_filter( 'sgo_javascript_combine_exclude', __NAMESPACE__ . '\js_exclude' );
add_filter( 'sgo_js_async_exclude', __NAMESPACE__ . '\js_exclude' );

function js_exclude_inline_script( $exclude_list ) {
	$exclude_list[] = '/*nelio-ab-testing-kickoff*/';
	$exclude_list[] = '/*nelio-ab-testing-alternative-loader*/';

	return $exclude_list;
}//end js_exclude_inline_script()
add_filter( 'sgo_javascript_combine_excluded_inline_content', __NAMESPACE__ . '\js_exclude_inline_script' );
