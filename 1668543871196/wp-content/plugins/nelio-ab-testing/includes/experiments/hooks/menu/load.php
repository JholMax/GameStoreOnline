<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \add_filter;
use \remove_filter;
use \wp_get_nav_menu_items;

add_action( 'nab_is_nab/menu_priority_experiment', '__return_true' );

function load_alternative( $alternative, $control ) {

	if ( $alternative['menuId'] === $control['menuId'] ) {
		return;
	}//end if

	$replace_menu = function( $items, $menu, $args ) use ( &$replace_menu, $alternative, $control ) {

		if ( $menu->term_id === $control['menuId'] && is_nav_menu( $alternative['menuId'] ) ) {
			if ( isset( $args['tax_query'] ) ) {
				unset( $args['tax_query'] );
			}//end if
			remove_filter( 'wp_get_nav_menu_items', $replace_menu, 10, 3 );
			$items = wp_get_nav_menu_items( $alternative['menuId'], $args );
			add_filter( 'wp_get_nav_menu_items', $replace_menu, 10, 3 );
		}//end if

		return $items;

	};

	add_filter( 'wp_get_nav_menu_items', $replace_menu, 10, 3 );

}//end load_alternative()
add_action( 'nab_nab/menu_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 2 );
