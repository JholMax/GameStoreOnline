<?php
/**
 * Displays the UI for configuring the plugin.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/views
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="wrap">

	<h2>
		<?php echo esc_html_x( 'Nelio A/B Testing - Settings', 'text', 'nelio-ab-testing' ); ?>
	</h2>

	<?php settings_errors(); ?>

	<form method="post" action="options.php" class="nab-settings-form">
		<?php
			$settings = Nelio_AB_Testing_Settings::instance();
			settings_fields( $settings->get_option_group() );
			do_settings_sections( $settings->get_settings_page_name() );
			submit_button();
		?>
	</form>

</div><!-- .wrap -->

