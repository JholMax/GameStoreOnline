<?php
/**
 * This file defines hooks to load alternative content during WooCommerce AJAX requests.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/hooks
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      5.0.0
 */

namespace Nelio_AB_Testing\Experiment_Library\WooCommerce;

defined( 'ABSPATH' ) || exit;

require_once dirname( __FILE__ ) . '/ajax-load.php';
require_once dirname( __FILE__ ) . '/extensions.php';

require_once dirname( __FILE__ ) . '/product/index.php';
