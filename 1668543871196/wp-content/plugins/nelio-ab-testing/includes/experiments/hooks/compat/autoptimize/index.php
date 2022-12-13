<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with Autoptimize.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/library
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      5.0.6
 */

namespace Nelio_AB_Testing\Experiment_Library\Compat\Autoptimize;

defined( 'ABSPATH' ) || exit;

function override_js_exclude( $exclude ) {
	return $exclude . ', nelio-ab-testing';
}//end override_js_exclude()
add_filter( 'autoptimize_filter_js_exclude', __NAMESPACE__ . '\override_js_exclude', 10, 1 );
add_filter( 'autoptimize_filter_js_minify_excluded', '__return_false' );
