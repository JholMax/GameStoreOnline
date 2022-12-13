<?php

namespace Nelio_AB_Testing\Experiment_Library\Compat\Elementor;

defined( 'ABSPATH' ) || exit;

use \add_action;
use \class_exists;

add_action(
	'plugins_loaded',
	function() {
		if ( ! class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			return;
		}//end if
		add_action( 'nab_nab/page_preview_alternative', __NAMESPACE__ . '\use_proper_styles', 10, 2 );
		add_action( 'nab_nab/post_preview_alternative', __NAMESPACE__ . '\use_proper_styles', 10, 2 );
		add_action( 'nab_nab/custom-post-type_preview_alternative', __NAMESPACE__ . '\use_proper_styles', 10, 2 );
	}
);

