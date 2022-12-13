<?php

namespace Nelio_AB_Testing\Experiment_Library\WooCommerce\Product_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \add_filter;

function load_woocommerce_alternative( $alternative, $control ) {

	if ( isset( $alternative['postId'] ) && $control['postId'] === $alternative['postId'] ) {
		return;
	}//end if

	$replace_title = function( $title, $object ) use ( $alternative, $control ) {
		$product_id = 0;
		if ( is_int( $object ) ) {
			$product_id = $object;
		} elseif ( is_object( $object ) && method_exists( $object, 'get_id' ) ) {
			$product_id = $object->get_id();
		} elseif ( is_object( $object ) && method_exists( $object, 'get_product_id' ) ) {
			$product_id = $object->get_product_id();
		}//end if

		if ( empty( $product_id ) || $product_id !== $control['postId'] ) {
			return $title;
		}//end if
		return empty( $alternative['name'] ) ? $title : $alternative['name'];
	};

	add_filter( 'the_title', $replace_title, 10, 2 );
	add_filter( 'woocommerce_product_title', $replace_title, 10, 2 );
	add_filter( 'woocommerce_product_get_name', $replace_title, 10, 2 );
	add_filter( 'woocommerce_order_item_get_name', $replace_title, 10, 2 );

	$replace_description = function( $excerpt ) use ( $alternative, $control, &$replace_description ) {
		if ( is_shop() ) {
			return $excerpt;
		}//end if
		global $post;
		if ( $post->ID !== $control['postId'] ) {
			return $excerpt;
		}//end if
		if ( empty( $alternative['excerpt'] ) ) {
			return $excerpt;
		}//end if
		remove_filter( 'woocommerce_short_description', $replace_description, 10, 2 );
		$result = wc_format_content( $alternative['excerpt'] );
		add_filter( 'woocommerce_short_description', $replace_description, 10, 2 );
		return $result;
	};
	add_filter( 'woocommerce_short_description', $replace_description, 10, 2 );

	$undo_replace_description = function( $props, $obj, $variation ) use ( $control, &$replace_description ) {
		if ( $obj->get_id() !== $control['postId'] ) {
			return $props;
		}//end if

		/**
		 * Filters whether a WC product test should replace the description of product variation or not.
		 *
		 * @param boolean $replace whether product variation description should be replaced or not. Default: `true`;
		 *
		 * @since 5.0.16
		 */
		if ( ! apply_filters( 'nab_replace_short_description_in_wc_product_variation', true ) ) {
			return $props;
		}//end if

		remove_filter( 'woocommerce_short_description', $replace_description, 10, 2 );
		$props['variation_description'] = wc_format_content( $variation->get_description() );
		add_filter( 'woocommerce_short_description', $replace_description, 10, 2 );
		return $props;
	};
	add_filter( 'woocommerce_available_variation', $undo_replace_description, 10, 3 );

	add_filter(
		'woocommerce_product_get_image_id',
		function( $value, $object ) use ( $alternative, $control ) {
			if ( $object->get_id() !== $control['postId'] ) {
				return $value;
			}//end if
			if ( empty( $alternative['imageId'] ) ) {
				return $value;
			}//end if
			return $alternative['imageId'];
		},
		10,
		2
	);

	add_filter(
		'woocommerce_product_get_price',
		function( $price, $product ) use ( $control, $alternative ) {
			return absint( $product->get_id() ) === $control['postId'] &&
				isset( $alternative['price'] ) &&
				! empty( $alternative['price'] )
				? $alternative['price']
				: $price;
		},
		10,
		2
	);

}//end load_woocommerce_alternative()
add_action( 'nab_nab/wc-product_load_alternative', __NAMESPACE__ . '\load_woocommerce_alternative', 10, 2 );
