<?php

namespace Nelio_AB_Testing\Experiment_Library\Widget_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \add_filter;
use \array_filter;
use \array_map;
use \array_walk;
use \get_option;
use \in_array;
use \update_option;
use \wp_list_pluck;
use \Widgets_Helpers;

function create_alternative_content( $alternative, $control, $experiment_id, $alternative_id ) {

	$control_sidebars = get_control_sidebars();

	$alternative['sidebars'] = duplicate_sidebars_for_alternative( $control_sidebars, $experiment_id, $alternative_id );

	return $alternative;

}//end create_alternative_content()
add_filter( 'nab_nab/widget_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 4 );

// Creating a backup of the control version is equivalent to creating a new alternative.
add_filter( 'nab_nab/widget_backup_control', __NAMESPACE__ . '\create_alternative_content', 10, 4 );

function duplicate_alternative_content( $new_alternative, $old_alternative, $new_experiment_id, $new_alternative_id ) {

	$sidebars = get_alternative_sidebars( $old_alternative );

	$new_alternative['sidebars'] = duplicate_sidebars_for_alternative( $sidebars, $new_experiment_id, $new_alternative_id );

	return $new_alternative;

}//end duplicate_alternative_content()
add_filter( 'nab_nab/widget_duplicate_alternative_content', __NAMESPACE__ . '\duplicate_alternative_content', 10, 4 );

function apply_alternative( $applied, $alternative ) {

	$alternative_sidebars = wp_list_pluck( $alternative['sidebars'], 'id' );
	$control_sidebars     = wp_list_pluck( $alternative['sidebars'], 'control' );

	$helper = Widgets_Helper::instance();
	$helper->remove_alternative_sidebars( $control_sidebars );
	$helper->duplicate_sidebars( $alternative_sidebars, $control_sidebars );
	return true;

}//end apply_alternative()
add_filter( 'nab_nab/widget_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );

function remove_alternative_content( $alternative ) {

	$alternative_sidebar_ids = wp_list_pluck( $alternative['sidebars'], 'id' );

	$helper = Widgets_Helper::instance();
	$helper->remove_alternative_sidebars( $alternative_sidebar_ids );

}//end remove_alternative_content()
add_action( 'nab_nab/widget_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content' );

function register_sidebars_for_all_widget_experiments() {

	$experiment_ids = get_widget_experiment_ids();
	array_walk( $experiment_ids, __NAMESPACE__ . '\register_sidebars_in_experiment' );

}//end register_sidebars_for_all_widget_experiments()
add_action( 'widgets_init', __NAMESPACE__ . '\register_sidebars_for_all_widget_experiments', 99 );
