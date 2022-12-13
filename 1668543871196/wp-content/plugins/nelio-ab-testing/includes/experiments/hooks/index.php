<?php

defined( 'ABSPATH' ) || exit;

// Experiment types.
require_once dirname( __FILE__ ) . '/css/index.php';
require_once dirname( __FILE__ ) . '/headline/index.php';
require_once dirname( __FILE__ ) . '/menu/index.php';
require_once dirname( __FILE__ ) . '/post/index.php';
require_once dirname( __FILE__ ) . '/template/index.php';
require_once dirname( __FILE__ ) . '/theme/index.php';
require_once dirname( __FILE__ ) . '/widget/index.php';
require_once dirname( __FILE__ ) . '/woocommerce/index.php';

// Compatibility with third-party plugins and themes.
require_once dirname( __FILE__ ) . '/compat/index.php';
