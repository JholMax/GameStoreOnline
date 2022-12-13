<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\WooCommerce_Order_Completed;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \nab_track_conversion;
use \wc_get_order;

function add_hooks_for_tracking( $action, $experiment_id, $goal_index, $goal ) {

	add_action(
		'woocommerce_checkout_order_processed',
		function( $order_id ) {

			$experiments = get_experiments_from_request();
			if ( empty( $experiments ) ) {
				return;
			}//end if

			update_post_meta( $order_id, '_nab_experiments_with_page_view', $experiments );

		}
	);

	add_action(
		'woocommerce_order_status_changed',
		function( $order_id, $old_status, $new_status ) use ( $action, $experiment_id, $goal_index, $goal ) {

			// If it's a revision or an autosave, do nothing.
			if ( wp_is_post_revision( $order_id ) || wp_is_post_autosave( $order_id ) ) {
				return;
			}//end if

			if ( $old_status === $new_status ) {
				return;
			}//end if

			$synched_goals = get_post_meta( $order_id, '_nab_synched_goals', true );
			$synched_goals = ! empty( $synched_goals ) ? $synched_goals : array();
			if ( in_array( "{$experiment_id}:{$goal_index}", $synched_goals, true ) ) {
				return;
			}//end if

			$expected_statuses = get_expected_statuses( $goal );
			if ( ! in_array( $new_status, $expected_statuses, true ) ) {
				return;
			}//end if

			$experiments = get_post_meta( $order_id, '_nab_experiments_with_page_view', true );
			if ( empty( $experiments ) || ! isset( $experiments[ $experiment_id ] ) ) {
				return;
			}//end if

			if ( function_exists( '\wc_get_order' ) ) {
				$order = \wc_get_order( $order_id );
			} elseif ( class_exists( 'WC_Order' ) ) {
				$order = new WC_Order( $order_id );
			} else {
				return;
			}//end if

			$action['anyProduct'] = isset( $action['anyProduct'] ) ? $action['anyProduct'] : false;
			$action['productId']  = isset( $action['productId'] ) ? $action['productId'] : 0;
			if ( ! $action['anyProduct'] && ! does_order_contain( $order, $action['productId'] ) ) {
				return;
			}//end if

			$value       = get_conversion_value( $order, $goal );
			$alternative = $experiments[ $experiment_id ];
			nab_track_conversion( $experiment_id, $goal_index, $alternative, $value );
			array_push( $synched_goals, "{$experiment_id}:{$goal_index}" );
			update_post_meta( $order_id, '_nab_synched_goals', $synched_goals );

		},
		10,
		3
	);

}//end add_hooks_for_tracking()
add_action( 'nab_nab/wc-order_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 4 );

function does_order_contain( $order, $product_id ) {

	$items = $order->get_items();
	foreach ( $items as $item ) {
		if ( is_purchased_product_equal_to( $item['product_id'], $product_id ) ) {
			return true;
		}//end if
	}//end foreach

	return false;

}//end does_order_contain()

function is_purchased_product_equal_to( $purchased_product_id, $expected_product_id ) {

	if ( empty( $expected_product_id ) ) {
		return false;
	}//end if

	if ( $purchased_product_id === $expected_product_id ) {
		return true;
	}//end if

	do {
		$product              = \wc_get_product( $purchased_product_id );
		$purchased_product_id = $product->get_parent_id();
		if ( $purchased_product_id === $expected_product_id ) {
			return true;
		}//end if
	} while ( $purchased_product_id );

	return false;

}//end is_purchased_product_equal_to()

function get_experiments_from_request() {

	if ( ! isset( $_REQUEST['nab_experiments_with_page_view'] ) ) { // phpcs:ignore
		return;
	}//end if

	$hidden_value = sanitize_text_field( wp_unslash( $_REQUEST['nab_experiments_with_page_view'] ) ); // phpcs:ignore

	return array_reduce(
		explode( ',', $hidden_value ),
		function( $result, $item ) {
			$item = explode( ':', $item );
			if ( 2 === count( $item ) && absint( $item[0] ) ) {
				$result[ absint( $item[0] ) ] = absint( $item[1] );
			}//end if
			return $result;
		},
		array()
	);

}//end get_experiments_from_request()

function get_conversion_value( $order, $goal ) {
	$attrs       = isset( $goal['attributes'] ) ? $goal['attributes'] : array();
	$use_revenue = ! empty( $attrs['useOrderRevenue'] );
	if ( ! $use_revenue ) {
		return 0;
	}//end if

	if ( any_product_goes( $goal ) ) {
		return 0 + $order->get_total();
	}//end if

	$ids   = get_product_ids( $goal );
	$items = array_filter(
		$order->get_items(),
		function( $item ) use ( $ids ) {
			$product_id = absint( $item['product_id'] );
			return $product_id && in_array( $product_id, $ids, true );
		}
	);

	return array_reduce(
		$items,
		function( $carry, $item ) {
			return $carry + $item->get_total();
		},
		0
	);
}//end get_conversion_value()

function any_product_goes( $goal ) {
	$actions = isset( $goal['conversionActions'] ) ? $goal['conversionActions'] : array();
	if ( empty( $actions ) ) {
		return false;
	}//end if

	foreach ( $actions as $action ) {
		$type   = $action['type'];
		$action = isset( $action['attributes'] ) ? $action['attributes'] : array();
		if ( 'nab/wc-order' === $type && isset( $action['anyProduct'] ) && $action['anyProduct'] ) {
			return true;
		}//end if
	}//end foreach

	return false;
}//end any_product_goes()

function get_product_ids( $goal ) {
	$actions = isset( $goal['conversionActions'] ) ? $goal['conversionActions'] : array();
	$actions = array_filter(
		$actions,
		function( $action ) {
			$attrs = isset( $action['attributes'] ) ? $action['attributes'] : array();
			return 'nab/wc-order' === $action['type'] && isset( $attrs['productId'] ) && absint( $attrs['productId'] );
		}
	);

	$product_ids = array_map(
		function( $action ) {
			return absint( $action['attributes']['productId'] );
		},
		$actions
	);

	return array_values( array_unique( $product_ids ) );
}//end get_product_ids()

function get_expected_statuses( $goal ) {
	$attrs  = isset( $goal['attributes'] ) ? $goal['attributes'] : array();
	$status = isset( $attrs['orderStatusForConversion'] ) ? $attrs['orderStatusForConversion'] : 'wc-completed';
	$status = str_replace( 'wc-', '', $status );

	/**
	 * Returns the statuses that might trigger a conversion when there’s a WooCommerce order.
	 * Don’t include the `wc-` prefix in status names.
	 *
	 * @param array|string $statuses the status (or statuses) that might trigger a conversion.
	 *
	 * @since 5.0.0
	 */
	$expected_statuses = apply_filters( 'nab_order_status_for_conversions', $status );
	if ( ! is_array( $expected_statuses ) ) {
		$expected_statuses = array( $expected_statuses );
	}//end if

	return $expected_statuses;
}//end get_expected_statuses()
