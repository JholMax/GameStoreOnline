<?php

namespace Nelio_AB_Testing\Experiment_Library\WooCommerce\Product_Experiment;

defined( 'ABSPATH' ) || exit;

function get_default_attributes_in_control() {
	return array(
		'postId'   => 0,
		'postType' => 'product',
		'cost'     => '',
	);
}//end get_default_attributes_in_control()
add_filter( 'nab_nab/wc-product_get_default_attributes_in_control', __NAMESPACE__ . '\get_default_attributes_in_control' );

function get_default_attributes_in_alternative() {
	return array_merge(
		\Nelio_AB_Testing\Experiment_Library\Headline_Experiment\get_default_attributes_in_alternative(),
		array( 'price' => '' )
	);
}//end get_default_attributes_in_alternative()
add_filter( 'nab_nab/wc-product_get_default_attributes_in_alternative', __NAMESPACE__ . '\get_default_attributes_in_alternative' );
