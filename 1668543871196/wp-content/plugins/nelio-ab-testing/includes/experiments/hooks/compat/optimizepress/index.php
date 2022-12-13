<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with Optimize Press.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/library
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.4
 */

namespace Nelio_AB_Testing\Experiment_Library\Compat\Optimize_Press;

defined( 'ABSPATH' ) || exit;

require_once dirname( __FILE__ ) . '/load.php';
require_once dirname( __FILE__ ) . '/preview.php';

