<?php

namespace Nelio_AB_Testing\Experiment_Library\Compat\Divi;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \class_exists;

add_action(
	'plugins_loaded',
	function() {
		// Notice: these hooks must be enabled ALWAYS, because during `plugins_loaded`
		// we can't check if Divi theme is active and, if it is, we need them.
		add_action( 'nab_nab/page_preview_alternative', __NAMESPACE__ . '\load_alternative_content', 1, 2 );
		add_action( 'nab_nab/post_preview_alternative', __NAMESPACE__ . '\load_alternative_content', 1, 2 );
		add_action( 'nab_nab/custom-post-type_preview_alternative', __NAMESPACE__ . '\load_alternative_content', 1, 2 );
	}
);

