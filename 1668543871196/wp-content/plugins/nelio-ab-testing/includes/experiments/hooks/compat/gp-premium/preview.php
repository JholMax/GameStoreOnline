<?php

namespace Nelio_AB_Testing\Experiment_Library\Compat\GeneratePress_Premium;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \defined;

add_action(
	'plugins_loaded',
	function() {
		if ( ! defined( 'GP_PREMIUM_VERSION' ) ) {
			return;
		}//end if
		add_action( 'nab_nab/page_preview_alternative', __NAMESPACE__ . '\use_proper_source', 10, 2 );
		add_action( 'nab_nab/post_preview_alternative', __NAMESPACE__ . '\use_proper_source', 10, 2 );
		add_action( 'nab_nab/custom-post-type_preview_alternative', __NAMESPACE__ . '\use_proper_source', 10, 2 );
	}
);

