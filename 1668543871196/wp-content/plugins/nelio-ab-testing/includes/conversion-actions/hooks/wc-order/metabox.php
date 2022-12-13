<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\WooCommerce_Order_Completed;

defined( 'ABSPATH' ) || exit;

use \add_filter;
use \add_meta_box;

function add_testing_meta_box( $post ) {
	$experiments = get_post_meta( $post->ID, '_nab_experiments_with_page_view', true );
	if ( empty( $experiments ) ) {
		return;
	}//end if

	add_meta_box(
		'nelioab_testing_box',
		'Nelio A/B Testing',
		__NAMESPACE__ . '\render_meta_box',
		'shop_order',
		'side',
		'default'
	);
}//end add_testing_meta_box()
add_action( 'add_meta_boxes_shop_order', __NAMESPACE__ . '\add_testing_meta_box' );

function render_meta_box() {
	$order_id      = get_the_ID();
	$experiments   = get_experiments( $order_id );
	$synched_goals = get_post_meta( $order_id, '_nab_synched_goals', true );
	$synched_goals = ! empty( $synched_goals ) ? $synched_goals : array();

	printf(
		'<p>%s</p>',
		esc_html_x( 'Tests in which the visitor participated and the variant they saw:', 'text', 'nelio-ab-testing' )
	);

	$is_experiment = function( $id ) {
		return function( $sync_goal ) use ( $id ) {
			return 0 === strpos( $sync_goal, "{$id}:" );
		};
	};

	$get_goal_index = function ( $sync_goal ) {
		return absint( explode( ':', $sync_goal )[1] );
	};

	echo '<ul>';
	foreach ( $experiments as $experiment ) {
		$id = $experiment['id'];
		$sg = array_filter( $synched_goals, $is_experiment( $id ) );
		$sg = array_map( $get_goal_index, $sg );
		render_experiment( $experiment, array_values( $sg ) );
	}//end foreach
	echo '</ul>';
}//end render_meta_box()

function render_experiment( $exp, $synched_goals ) {
	$alt = chr( ord( 'A' ) + $exp['alt'] );
	$alt = sprintf(
		/* translators: variant letter (A, B, C, ...) */
		_x( 'variant %s', 'text', 'nelio-ab-testing' ),
		esc_html( $alt )
	);

	$wc_goals = array_map(
		function ( $g ) {
			$actions = wp_list_pluck( $g['conversionActions'], 'type' );
			$actions = array_values( array_unique( $actions ) );
			return count( $actions ) === 1 && 'nab/wc-order' === $actions[0];
		},
		$exp['goals']
	);
	$wc_goals = array_keys( array_filter( $wc_goals ) );

	if ( empty( $synched_goals ) ) {
		$exp_status = _x( 'Not Synched', 'text (order sync status)', 'nelio-ab-testing' );
	} elseif ( count( $synched_goals ) < count( $wc_goals ) ) {
		$exp_status = _x( 'Partially Synched', 'text (order sync status)', 'nelio-ab-testing' );
	} else {
		$exp_status = _x( 'Synched', 'text (order sync status)', 'nelio-ab-testing' );
	}//end if

	$style = 'list-style:disc; margin-left: 1.2em';
	if ( $exp['link'] ) {
		printf(
			'<li style="%s"><a href="%s">%s</a> (%s)<br>%s: <em>%s</em></li>',
			esc_attr( $style ),
			esc_url( $exp['link'] ),
			esc_html( $exp['name'] ),
			esc_html( $alt ),
			esc_html_x( 'Status', 'text', 'nelio-ab-testing' ),
			esc_html( $exp_status )
		);
	} else {
		printf(
			'<li style="%s">%s (%s)<br>%s: <em>%s</em></li>',
			esc_attr( $style ),
			esc_html( $exp['name'] ),
			esc_html( $alt ),
			esc_html_x( 'Status', 'text', 'nelio-ab-testing' ),
			esc_html( $exp_status )
		);
	}//end if
}//end render_experiment()

function get_experiments( $order_id ) {
	global $wpdb;

	$exp_alt_map = get_post_meta( $order_id, '_nab_experiments_with_page_view', true );
	$exp_ids     = array_map( '\absint', array_keys( $exp_alt_map ) );

	$experiments = $wpdb->get_results( // phpcs:ignore
		$wpdb->prepare(
			"SELECT ID, post_title, post_status FROM $wpdb->posts p
					WHERE p.post_type = %s AND p.ID IN (%s)",
			'nab_experiment',
			implode( ',', $exp_ids )
		)
	);

	$experiments = array_reduce(
		$experiments,
		function( $carry, $exp ) {
			$carry[ absint( $exp->ID ) ] = array(
				'name'   => $exp->post_title,
				'status' => $exp->post_status,
			);
			return $carry;
		},
		array()
	);

	return array_map(
		function( $id ) use ( &$exp_alt_map, &$experiments ) {
			/* translators: test ID */
			$unknown = _x( 'Test %d is no longer available', 'text', 'nelio-ab-testing' );

			$goals = get_post_meta( $id, '_nab_goals', true );
			$goals = empty( $goals ) ? array() : $goals;

			$res = array(
				'id'    => $id,
				'link'  => false,
				'name'  => sprintf( $unknown, $id ),
				'alt'   => isset( $exp_alt_map[ $id ] ) ? $exp_alt_map[ $id ] : 0,
				'goals' => $goals,
			);

			if ( isset( $experiments[ $id ] ) ) {
				$exp = $experiments[ $id ];

				$res['name'] = $exp['name'];
				if ( in_array( $exp['status'], array( 'nab_running', 'nab_finished' ), true ) ) {
					$res['link'] = add_query_arg(
						array(
							'page'       => 'nelio-ab-testing-experiment-view',
							'experiment' => $id,
						),
						admin_url( 'admin.php' )
					);
				}//end if
			}//end if

			return $res;
		},
		$exp_ids
	);
}//end get_experiments()
