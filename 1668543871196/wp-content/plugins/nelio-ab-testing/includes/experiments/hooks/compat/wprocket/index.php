<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with WPRocket.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/library
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      5.0.6
 */

namespace Nelio_AB_Testing\Experiment_Library\Compat\WPRocket;

defined( 'ABSPATH' ) || exit;

function exclude_files( $excluded_files = array() ) {
	$excluded_files[] = 'nelio';
	$excluded_files[] = 'nab';

	return $excluded_files;
}//end exclude_files()
add_filter( 'rocket_exclude_defer_js', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_exclude_async_css', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_exclude_cache_busting', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_exclude_static_dynamic_resources', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_excluded_inline_js_content', __NAMESPACE__ . '\exclude_files', 10, 1 );
add_filter( 'rocket_exclude_js', __NAMESPACE__ . '\exclude_files', 10, 1 );
