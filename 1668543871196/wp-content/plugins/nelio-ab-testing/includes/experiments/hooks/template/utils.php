<?php
namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

function get_actual_template( $post_id ) {
	global $wpdb;
	$query = $wpdb->prepare(
		"SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s AND post_id = %d",
		'_wp_page_template',
		$post_id
	);

	$template = $wpdb->get_var( $query ); // phpcs:ignore
	if ( ! locate_template( $template ) ) {
		$template = 'default';
	}//end if

	return $template;
}//end get_actual_template()
