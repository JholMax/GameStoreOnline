<?php

namespace Nelio_AB_Testing\Experiment_Library\Compat\Elementor;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \add_filter;
use \class_exists;

use Elementor\Core\Files\CSS\Post as Post_CSS;

function use_proper_styles( $alternative, $control ) {

	if ( $control['postId'] === $alternative['postId'] ) {
		return;
	}//end if

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		return;
	}//end if

	$control_id     = $control['postId'];
	$alternative_id = $alternative['postId'];

	if ( ! get_post_meta( $control_id, '_elementor_edit_mode', true ) ) {
		return;
	}//end if

	/**
	 * Whether we should use the original post ID when loading an alternative post built with Elementor or not.
	 *
	 * @param boolean $use_control_id whether we should use the original post ID or not.
	 *                                Default: `true`.
	 * @param int     $control_id     ID of the tested post.
	 *
	 * @since 5.0.7
	 */
	if ( ! apply_filters( 'nab_use_control_id_in_elementor_alternative', true, $control_id ) ) {
		add_filter( 'nab_use_control_id_in_alternative', '__return_false' );
		return;
	}//end if

	// 1. Add proper CSS classes in HTML nodes.
	add_action(
		'wp_footer',
		function() use ( $control_id, $alternative_id ) {
			$script  = 'wp.domReady( function() {';
			$script .= ' elements = document.querySelectorAll( ".elementor-%1$s" );';
			$script .= ' for ( i = 0; i < elements.length; ++i ) {';
			$script .= '  elements[ i ].classList.add( "elementor-%2$s" );';
			$script .= ' }';
			$script .= '} );';
			wp_enqueue_script( 'wp-dom-ready' );
			wp_add_inline_script( 'wp-dom-ready', sprintf( $script, $control_id, $alternative_id ) );
		}
	);

	// 2. Dequeue control and alternative CSS.
	add_action(
		'wp_enqueue_scripts',
		function() use ( $control_id, $alternative_id ) {

			// Dequeue if its an external CSS.
			wp_dequeue_style( "elementor-post-{$control_id}" );

			// And dequeue if its inline.
			$styles = wp_styles();
			if ( ! isset( $styles->registered['elementor-frontend'] ) ) {
				return;
			}//end if

			$elementor = $styles->registered['elementor-frontend'];
			if ( ! isset( $elementor->extra['after'] ) ) {
				return;
			}//end if

			$elementor->extra['after'] = array_values(
				array_filter(
					$elementor->extra['after'],
					function( $style ) use ( $control_id, $alternative_id ) {
						return false === strpos( $style, ".elementor-{$control_id} " ) &&
							false === strpos( $style, ".elementor-{$alternative_id} " );
					}
				)
			);

		},
		9999
	);

	// 3. Enqueue alternative CSS.
	add_action(
		'wp_enqueue_scripts',
		function() use ( $alternative_id ) {
			$aux = new Post_CSS( $alternative_id );
			$aux->enqueue();
		},
		9999
	);

}//end use_proper_styles()

function fix_issue_with_elementor_landing_pages() {
	add_filter(
		'nab_is_tested_post_by_nab/custom-post-type_experiment',
		function( $tested, $post_id, $control, $experiment_id ) {
			$type = 'e-landing-page';
			if ( $type !== $control['postType'] ) {
				return $tested;
			}//end if

			$name = get_query_var( 'category_name' );
			if ( empty( $name ) ) {
				return $tested;
			}//end if

			global $wpdb;
			$key = "nab/$type/$name";
			$id  = wp_cache_get( $key );
			if ( empty( $id ) ) {
				$sql = "SELECT ID FROM $wpdb->posts p WHERE p.post_type = %s AND p.post_name = %s";
				$id  = absint( $wpdb->get_var( $wpdb->prepare( $sql, $type, $name ) ) ); // phpcs:ignore
				wp_cache_set( $key, $id );
			}//end if

			if ( empty( $control['testAgainstExistingContent'] ) ) {
				return $id === $control['postId'];
			}//end if

			$experiment = nab_get_experiment( $experiment_id );
			$alts       = $experiment->get_alternatives();
			$pids       = wp_list_pluck( wp_list_pluck( $alts, 'attributes' ), 'postId' );
			$pids       = array_values( array_filter( $pids ) );
			return in_array( $id, $pids, true );
		},
		10,
		4
	);
}//end fix_issue_with_elementor_landing_pages()

add_action(
	'plugins_loaded',
	function() {
		if ( ! class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			return;
		}//end if
		add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\use_proper_styles', 10, 2 );
		add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\use_proper_styles', 10, 2 );
		add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\use_proper_styles', 10, 2 );

		fix_issue_with_elementor_landing_pages();
	}
);

