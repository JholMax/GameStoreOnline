<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \add_filter;
use \Nelio_AB_Testing_Settings;

function use_control_id_in_alternative() {
	$settings       = Nelio_AB_Testing_Settings::instance();
	$use_control_id = $settings->get( 'use_control_id_in_alternative' );

	/**
	 * Whether we should use the original post ID when loading an alternative post or not.
	 *
	 * @param boolean $use_control_id whether we should use the original post ID or not.
	 *
	 * @since 5.0.4
	 */
	return apply_filters( 'nab_use_control_id_in_alternative', $use_control_id );
}//end use_control_id_in_alternative()

function load_alternative( $alternative, $control ) {

	if ( $control['postId'] === $alternative['postId'] ) {
		return;
	}//end if

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		return;
	}//end if

	$fix_front_page = function( $res ) use ( &$fix_front_page, $control, $alternative ) {
		remove_filter( 'pre_option_page_on_front', $fix_front_page );
		$front_page = get_front_page_id();
		add_filter( 'pre_option_page_on_front', $fix_front_page );
		return $control['postId'] === $front_page ? $alternative['postId'] : $res;
	};
	add_filter( 'pre_option_page_on_front', $fix_front_page );

	add_filter(
		'single_post_title',
		function( $post_title, $post ) use ( $control, $alternative ) {
			if ( $post->ID !== $control['postId'] ) {
				return $post_title;
			}//end if
			$post = get_post( $alternative['postId'] );
			return $post->post_title;
		},
		10,
		2
	);

	$replace_post_results = function( $posts ) use ( &$replace_post_results, $alternative, $control ) {

		return array_map(
			function ( $post ) use ( &$replace_post_results, $alternative, $control ) {

				if ( $post->ID === $alternative['postId'] && get_front_page_id() === $alternative['postId'] ) {
					$post->post_status = 'publish';
					return $post;
				}//end if

				if ( $post->ID !== $control['postId'] ) {
					return $post;
				}//end if

				remove_filter( 'posts_results', $replace_post_results );
				remove_filter( 'get_pages', $replace_post_results );
				$post              = get_post( $alternative['postId'] );
				$post->post_status = 'publish';

				if ( use_control_id_in_alternative() ) {
					$post->ID = $control['postId'];
				}//end if

				if ( is_singular() && is_main_query() ) {
					global $wp_query;
					$wp_query->queried_object    = $post;
					$wp_query->queried_object_id = $post->ID;
				}//end if

				add_filter( 'posts_results', $replace_post_results );
				add_filter( 'get_pages', $replace_post_results );
				return $post;

			},
			$posts
		);

	};
	add_filter( 'posts_results', $replace_post_results );
	add_filter( 'get_pages', $replace_post_results );

	$fix_link = function( $permalink, $post_id ) use ( &$fix_link, $alternative, $control ) {

		if ( ! is_int( $post_id ) ) {
			if ( is_object( $post_id ) && isset( $post_id->ID ) ) {
				$post_id = $post_id->ID;
			} else {
				$post_id = nab_url_to_postid( $permalink );
			}//end if
		}//end if

		if ( use_control_id_in_alternative() && $post_id === $control['postId'] ) {
			remove_filter( 'post_link', $fix_link, 10, 2 );
			remove_filter( 'page_link', $fix_link, 10, 2 );
			remove_filter( 'post_type_link', $fix_link, 10, 2 );
			$permalink = get_permalink( $control['postId'] );
			add_filter( 'post_link', $fix_link, 10, 2 );
			add_filter( 'page_link', $fix_link, 10, 2 );
			add_filter( 'post_type_link', $fix_link, 10, 2 );
			return $permalink;
		}//end if

		if ( $post_id !== $alternative['postId'] ) {
			return $permalink;
		}//end if

		return get_permalink( $control['postId'] );

	};
	add_filter( 'post_link', $fix_link, 10, 2 );
	add_filter( 'page_link', $fix_link, 10, 2 );
	add_filter( 'post_type_link', $fix_link, 10, 2 );

	$fix_shortlink = function( $shortlink, $post_id ) use ( &$fix_shortlink, $alternative, $control ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}//end if

		if ( use_control_id_in_alternative() && $post_id === $control['postId'] ) {
			remove_filter( 'get_shortlink', $fix_shortlink, 10, 2 );
			$shortlink = wp_get_shortlink( $control['postId'] );
			add_filter( 'get_shortlink', $fix_shortlink, 10, 2 );
			return $shortlink;
		}//end if

		if ( $post_id !== $alternative['postId'] ) {
			return $shortlink;
		}//end if

		return wp_get_shortlink( $control['postId'] );
	};
	add_filter( 'get_shortlink', $fix_shortlink, 10, 2 );

	$use_alternative_metas = function( $value, $object_id, $meta_key, $single ) use ( &$use_alternative_metas, $alternative, $control ) {
		if ( $object_id !== $control['postId'] ) {
			return $value;
		}//end if

		// We always recover the “full” post meta (i.e. $single => false) so that
		// WordPress doesn’t “break” things.
		// See https://core.trac.wordpress.org/browser/tags/5.4/src/wp-includes/meta.php#L514.
		$value = get_post_meta( $alternative['postId'], $meta_key, false );
		if ( empty( $value ) && $single ) {
			$value[0] = '';
		}//end if

		return $value;
	};
	add_filter( 'get_post_metadata', $use_alternative_metas, 1, 4 );

	add_filter(
		'get_object_terms',
		function( $terms, $object_ids, $taxonomies, $args ) use ( $alternative, $control ) {
			if ( ! in_array( $control['postId'], $object_ids, true ) ) {
				return $terms;
			}//end if

			/**
			 * Gets the taxonomies that can be tested and, therefore, should be replaced during a test.
			 *
			 * @param array  $taxonomies list of taxonomies.
			 * @param string $post_type  the post type for which we’re retrieving the list of taxonomies
			 *
			 * @since 5.0.9
			 */
			$taxonomies = apply_filters( 'nab_get_testable_taxonomies', $taxonomies, $control['postType'] );

			$non_testable_terms = array_values(
				array_filter(
					$terms,
					function ( $term ) use ( &$taxonomies ) {
						return ! is_object( $term ) || ! in_array( $term->taxonomy, $taxonomies, true );
					}
				)
			);

			$object_ids   = array_diff( $object_ids, array( $control['postId'] ) );
			$object_ids[] = $alternative['postId'];

			$terms = array_values(
				array_merge(
					$non_testable_terms,
					wp_get_object_terms( $object_ids, $taxonomies, $args )
				)
			);

			if ( isset( $args['fields'] ) && 'all_with_object_id' !== $args['fields'] ) {
				return $terms;
			}//end if

			$terms = array_map(
				function( $term ) use ( $control, $alternative ) {
					if ( use_control_id_in_alternative() && $term->object_id === $alternative['postId'] ) {
						$term->object_id = $control['postId'];
					}//end if

					if ( ! use_control_id_in_alternative() && $term->object_id === $control['postId'] ) {
						$term->object_id = $alternative['postId'];
					}//end if

					return $term;
				},
				$terms
			);

			return $terms;
		},
		10,
		4
	);

	$use_alt_title_in_menus = function( $title, $item ) use ( $alternative, $control ) {
		if ( ! empty( $item->post_title ) ) {
			return $title;
		}//end if

		if ( "{$control['postId']}" !== "{$item->object_id}" ) {
			return $title;
		}//end if

		$post = get_post( $alternative['postId'] );
		if ( ! $post || is_wp_error( $post ) ) {
			return $title;
		}//end if

		return $post->post_title;
	};
	add_filter( 'nav_menu_item_title', $use_alt_title_in_menus, 10, 2 );

	$load_control_comments = function( $query ) use ( $control, $alternative ) {
		$post_id  = $query['post_id'];
		$post_ids = array( $control['postId'], $alternative['postId'] );
		if ( ! in_array( $post_id, $post_ids, true ) ) {
			return $query;
		}//end if

		return wp_parse_args(
			array(
				'post_id' => $control['postId'],
			),
			$query
		);
	};
	add_filter( 'comments_template_query_args', $load_control_comments );

	$load_control_comment_count = function( $count, $post_id ) use ( $alternative, $control, &$replace_post_results ) {
		$post_ids = array( $control['postId'], $alternative['postId'] );
		if ( ! in_array( $post_id, $post_ids, true ) ) {
			return $count;
		}//end if

		remove_filter( 'posts_results', $replace_post_results );
		$aux = get_post( $control['postId'] );
		add_filter( 'posts_results', $replace_post_results );
		return $aux->comment_count;
	};
	add_filter( 'get_comments_number', $load_control_comment_count, 10, 2 );

}//end load_alternative()
add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 2 );
add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 2 );
add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 2 );

function get_front_page_id() {
	return 'page' === get_option( 'show_on_front' ) ? absint( get_option( 'page_on_front' ) ) : 0;
}//end get_front_page_id()
