<?php

namespace Nelio_AB_Testing\Experiment_Library\WooCommerce;

defined( 'ABSPATH' ) || exit;

use \add_filter;

function add_product_extra_info( $info, $post ) {
	if ( 'product' !== $post->post_type ) {
		return $info;
	}//end if

	return array_merge(
		$info,
		array(
			'regularPrice' => get_post_meta( $post->ID, '_regular_price', true ),
		)
	);
}//end add_product_extra_info()
add_filter( 'nab_post_json_extra_data', __NAMESPACE__ . '\add_product_extra_info', 10, 2 );

add_filter(
	'woocommerce_add_to_cart_fragments',
	function( $data ) {
		$items = array();
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$items[] = $cart_item;
		}//end foreach
		$data['nab_cart_info'] = array(
			'items' => $items,
		);

		return $data;
	},
	99,
	1
);
