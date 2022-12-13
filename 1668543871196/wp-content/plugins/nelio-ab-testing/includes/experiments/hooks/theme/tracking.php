<?php
namespace Nelio_AB_Testing\Experiment_Library\Theme_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_filter;
add_filter( 'nab_nab/theme_should_trigger_page_view', '__return_true' );

