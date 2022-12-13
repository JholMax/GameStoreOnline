<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \add_filter;
use \strpos;

function load_alternative( $alternative ) {

	add_action(
		'wp_head',
		function() use ( $alternative ) {

			if ( ! isset( $alternative['css'] ) ) {
				return;
			}//end if

			if ( false !== strpos( $alternative['css'], '</style>' ) ) {
				return;
			}//end if

			echo '<style id="nab-alternative-css" type="text/css">';
			echo $alternative['css']; // phpcs:ignore
			echo '</style>';

		},
		999999
	);

}//end load_alternative()
add_action( 'nab_nab/css_load_alternative', __NAMESPACE__ . '\load_alternative' );
