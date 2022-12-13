<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Form_Submission;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \nab_track_conversion;

function add_hooks_for_tracking( $action, $experiment_id, $goal_index ) {

	add_action(
		'nelio_forms_process_complete',
		function( $fields, $form, $entry ) use ( $action, $experiment_id, $goal_index ) {
			if ( 'nelio_form' !== $action['formType'] ) {
				return;
			}//end if

			if ( absint( $form['id'] ) !== $action['formId'] ) {
				return;
			}//end if

			$experiments = get_experiments_from_request();
			maybe_sync_event_submission( $experiment_id, $goal_index, $experiments );
		},
		10,
		3
	);

	add_action(
		'gform_after_submission',
		function ( $entry, $form ) use ( $action, $experiment_id, $goal_index ) {

			if ( 'nab_gravity_form' !== $action['formType'] ) {
				return;
			}//end if

			if ( absint( $form['id'] ) !== $action['formId'] ) {
				return;
			}//end if

			$experiments = get_experiments_from_request();
			maybe_sync_event_submission( $experiment_id, $goal_index, $experiments );

		},
		10,
		2
	);

	add_action(
		'wpcf7_submit',
		function ( $form, $result ) use ( $action, $experiment_id, $goal_index ) {

			if ( 'wpcf7_contact_form' !== $action['formType'] ) {
				return;
			}//end if

			if ( $action['formId'] !== $form->id() ) {
				return;
			}//end if

			if ( ! in_array( $result['status'], array( 'mail_sent', 'demo_mode' ), true ) ) {
				return;
			}//end if

			$experiments = get_experiments_from_request();
			maybe_sync_event_submission( $experiment_id, $goal_index, $experiments );

		},
		10,
		2
	);

	add_action(
		'ninja_forms_after_submission',
		function ( $form ) use ( $action, $experiment_id, $goal_index ) {

			if ( 'nab_ninja_form' !== $action['formType'] ) {
				return;
			}//end if

			if ( absint( $form['form_id'] ) !== $action['formId'] ) {
				return;
			}//end if

			$experiments = get_experiments_from_request();
			maybe_sync_event_submission( $experiment_id, $goal_index, $experiments );

		}
	);

	add_action(
		'frm_after_create_entry',
		function ( $entry_id, $form_id ) use ( $action, $experiment_id, $goal_index ) {

			if ( 'nab_formidable_form' !== $action['formType'] ) {
				return;
			}//end if

			if ( absint( $form_id ) !== $action['formId'] ) {
				return;
			}//end if

			$experiments = get_experiments_from_request();
			maybe_sync_event_submission( $experiment_id, $goal_index, $experiments );

		},
		30,
		2
	);

	add_action(
		'wpforms_process_complete',
		function ( $fields, $entry, $form_data ) use ( $action, $experiment_id, $goal_index ) {

			if ( 'wpforms' !== $action['formType'] ) {
				return;
			}//end if

			if ( absint( $form_data['id'] ) !== $action['formId'] ) {
				return;
			}//end if

			$experiments = get_experiments_from_request();
			maybe_sync_event_submission( $experiment_id, $goal_index, $experiments );

		},
		10,
		3
	);

}//end add_hooks_for_tracking()
add_action( 'nab_nab/form-submission_add_hooks_for_tracking', __NAMESPACE__ . '\add_hooks_for_tracking', 10, 3 );

function maybe_sync_event_submission( $experiment_id, $goal_index, $experiments ) {

	if ( ! isset( $experiments[ $experiment_id ] ) ) {
		return;
	}//end if

	$alternative = $experiments[ $experiment_id ];
	nab_track_conversion( $experiment_id, $goal_index, $alternative );

}//end maybe_sync_event_submission()

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
