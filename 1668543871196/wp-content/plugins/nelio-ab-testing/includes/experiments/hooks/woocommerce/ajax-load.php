<?php

namespace Nelio_AB_Testing\Experiment_Library\WooCommerce;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \add_filter;
use \apply_filters;
use \do_action;
use \is_plugin_active;
use \nab_get_running_experiments;
use \wp_add_inline_script;
use \wp_json_encode;
use \WC;

function load_alternatives_during_ajax_request() {

	if ( ! isset( $_GET['wc-ajax'] ) ) { // phpcs:ignore
		return;
	}//end if

	$experiments   = nab_get_running_experiments();
	$nab_query_arg = WC()->session->get( 'nab_alternative' );

	foreach ( $experiments as $experiment ) {

		$experiment_type = $experiment->get_type();

		/**
		 * Filters whether the experiment type (included in the filter name) is related to WooCommerce or not.
		 *
		 * @param boolean $is_woocommerce_experiment Whether the experiment type is a WooCommerce-related
		 *                                           experiment or not. Default: `false`.
		 *
		 * @since 5.0.0
		 */
		if ( ! apply_filters( "nab_is_{$experiment_type}_woocommerce_experiment", false ) ) {
			continue;
		}//end if

		$control      = $experiment->get_alternative( 'control' );
		$alternatives = $experiment->get_alternatives();
		$alternative  = $alternatives[ $nab_query_arg % count( $alternatives ) ];

		/** This action is documented in public/helpers/class-nelio-ab-testing-runtime.php */
		do_action( "nab_{$experiment_type}_load_alternative", $alternative['attributes'], $control['attributes'], $experiment->get_id(), $alternative['id'] );

	}//end foreach

}//end load_alternatives_during_ajax_request()
add_action( 'init', __NAMESPACE__ . '\load_alternatives_during_ajax_request' );

function maybe_add_script() {

	if ( nab_is_split_testing_disabled() ) {
		return;
	}//end if

	include_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		return;
	}//end if

	$script = sprintf(
		'!! window.nabAddSingleAction && window.nabAddSingleAction( "valid-content", function() { nab.woocommerce.setAlternativeInSession( %s ); } )',
		wp_json_encode( admin_url( 'admin-ajax.php' ) )
	);

	nab_enqueue_script_with_auto_deps( 'nelio-ab-testing-woocommerce', 'woocommerce', true );
	wp_add_inline_script( 'nelio-ab-testing-woocommerce', $script );

}//end maybe_add_script()
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\maybe_add_script' );

function sync_alternative() {

	if ( ! isset( $_REQUEST['alternative'] ) ) { // @codingStandardsIgnoreLine
		return;
	}//end if

	$alternative = intval( $_REQUEST['alternative'] ); // phpcs:ignore
	if ( empty( $alternative ) ) {
		return;
	}//end if

	WC()->session->set( 'nab_alternative', $alternative );

}//end sync_alternative()
add_action( 'wp_ajax_nab_woocommerce_sync_alternative', __NAMESPACE__ . '\sync_alternative' );
add_action( 'wp_ajax_nopriv_nab_woocommerce_sync_alternative', __NAMESPACE__ . '\sync_alternative' );
