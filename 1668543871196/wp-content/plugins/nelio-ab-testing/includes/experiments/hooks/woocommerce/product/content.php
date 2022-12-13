<?php

namespace Nelio_AB_Testing\Experiment_Library\WooCommerce\Product_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_filter;
use \is_wp_error;

function get_tested_element( $tested_element, $control ) {
	return $control['postId'];
}//end get_tested_element()
add_filter( 'nab_nab/wc-product_get_tested_element', __NAMESPACE__ . '\get_tested_element', 10, 2 );

function backup_control( $backup, $control ) {

	if ( ! function_exists( '\wc_get_product' ) ) {
		return array();
	}//end if

	$product = \wc_get_product( $control['postId'] );
	if ( empty( $product ) || is_wp_error( $product ) ) {
		return array();
	}//end if

	$backup = array(
		'name'    => $product->get_name(),
		'excerpt' => $product->get_short_description(),
		'imageId' => absint( $product->get_image_id() ),
		'price'   => $product->get_price(),
	);
	return $backup;

}//end backup_control()
add_filter( 'nab_nab/wc-product_backup_control', __NAMESPACE__ . '\backup_control', 10, 2 );

function apply_alternative( $applied, $alternative, $control, $experiment_id, $alternative_id ) {
	$applied = \Nelio_AB_Testing\Experiment_Library\Headline_Experiment\apply_alternative( $applied, $alternative, $control, $experiment_id, $alternative_id );
	if ( ! $applied ) {
		return false;
	}//end if

	if ( isset( $alternative['price'] ) && ! empty( $alternative['price'] ) ) {
		update_post_meta( $control['postId'], '_price', $alternative['price'] );
		update_post_meta( $control['postId'], '_regular_price', $alternative['price'] );
	}//end if
	return true;
}//end apply_alternative()
add_filter( 'nab_nab/wc-product_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 5 );
