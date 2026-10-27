<?php
/**
 * Plugin Name:       JN Core
 * Plugin URI:        https://jannienguyen.vn
 * Description:       Core domain features, inline SVG icon system, coaching booking, customer management, and testimonials for Jannie Nguyen Academy.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Author:            Jannie Nguyen Engineering
 * License:           GPL-2.0-or-later
 * Text Domain:       jn-core
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'JN_CORE_VERSION', '1.0.0' );
define( 'JN_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'JN_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once JN_CORE_PATH . 'blocks/icon/class-jn-icon-sprite.php';
require_once JN_CORE_PATH . 'blocks/class-jn-blocks.php';
require_once JN_CORE_PATH . 'testimonials/class-jn-testimonials.php';

JN_Blocks::init();
JN_Testimonials::init();
