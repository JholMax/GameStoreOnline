<?php
/**
 * Displays a simple UI for debugging an experiment.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/views
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="experiment-debug wrap">

	<h1 class="wp-heading-inline"><?php echo esc_html_x( 'Test Debug', 'text', 'nelio-ab-testing' ); ?>

	<?php
	if ( ! is_wp_error( $experiment ) && in_array( $experiment->get_status(), array( 'finished', 'running' ), true ) ) {
		printf(
			'<button type="button" class="components-button page-title-action" onClick="%s" style="height:auto">%s</button>',
			esc_attr( 'javascript:document.getElementById( "nab-migration-form-debug" ).style.display=document.getElementById( "nab-migration-form-debug" ).style.display === "block" ? "none" : "block";' ),
			esc_html( 'Results' )
		);
	}//end if
	?>

	</h1>

	<?php
	if ( is_wp_error( $experiment ) ) {
		printf(
			'<h2>%s</h2><p><a class="button" href="%s">%s</a></p>',
			/* translators: experiment ID */
			sprintf( esc_html_x( 'Test “%d” not found.', 'text', 'nelio-ab-testing' ), esc_html( $experiment_id ) ),
			esc_url( admin_url( 'admin.php?page=nelio-ab-testing' ) ),
			esc_html_x( 'Back to Overview', 'command', 'nelio-ab-testing' )
		);
		return;
	}//end if
	?>

	<?php
	if ( in_array( $experiment->get_status(), array( 'finished', 'running' ), true ) ) {
		// phpcs:ignore
		require nelioab()->plugin_path . '/admin/views/nelio-ab-testing-migration-form-debug.php';
	}//end if
	?>

	<div>
		<textarea id="experiment-debug-data" readonly style="background:#fcfcfc; border:1px solid grey; width:100%; overflow:auto; height:calc(100vh - 18em ); min-height: 30em; padding:1em; font-family:monospace; white-space:pre;"><?php // phpcs:ignore
			$aux = Nelio_AB_Testing_Experiment_REST_Controller::instance();
			echo 'test = ' . wp_json_encode( $aux->json( $experiment ), JSON_PRETTY_PRINT ) . ';';

			$migration_params = get_post_meta( $experiment_id, '_nab_result_migration_params', true );
		if ( ! empty( $migration_params ) ) {
			echo "\n\n";
			echo 'migration = ' . wp_json_encode( $migration_params, JSON_PRETTY_PRINT ) . ';';
		}//end if
		// phpcs:ignore
		?></textarea>
	</div>

</div><!-- .experiment-debug -->

