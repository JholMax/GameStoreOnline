<?php

namespace Nelio_AB_Testing\Experiment_Library\Compat\Instabuilder2;

defined( 'ABSPATH' ) || exit;

use \add_action;
add_action(
	'plugins_loaded',
	function() {
		if ( ! defined( 'IB2_VERSION' ) ) {
			return;
		}//end if
		add_action( 'nab_nab/page_preview_alternative', __NAMESPACE__ . '\load_alternative_content', 1, 2 );
	}
);

