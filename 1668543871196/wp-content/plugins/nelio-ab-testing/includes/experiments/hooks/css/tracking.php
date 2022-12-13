<?php
namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

use \add_filter;
add_filter( 'nab_nab/css_should_trigger_page_view', '__return_true' );

