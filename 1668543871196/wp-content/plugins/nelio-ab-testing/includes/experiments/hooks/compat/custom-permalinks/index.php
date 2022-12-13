<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with Custom Permalinks.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/library
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.3.3
 */

namespace Nelio_AB_Testing\Experiment_Library\Compat\Custom_Permalinks;

defined( 'ABSPATH' ) || exit;

require_once dirname( __FILE__ ) . '/content.php';
