<?php

namespace Nelio_AB_Testing\Experiment_Library\Compat\Leadpages;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \class_exists;

add_action(
	'plugins_loaded',
	function() {
		if ( ! class_exists( 'LeadpagesWP\Admin\CustomPostTypes\LeadpagesPostType' ) ) {
			return;
		}//end if
		add_action( 'nab_nab/custom-post-type_preview_alternative', __NAMESPACE__ . '\fix_leadpages_query_for_alternative', 10, 2 );
	}
);

