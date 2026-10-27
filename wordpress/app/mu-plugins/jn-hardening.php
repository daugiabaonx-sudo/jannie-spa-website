<?php
/**
 * Plugin Name: JN Hardening & Hide-WordPress (MU)
 * Description: Comprehensive Hide-WordPress scrubbing, endpoint locks, neutral asset aliasing (/static/*), Vietnamese branded emails, login rate-limiting, and security headers for Jannie Nguyen.
 * Version:     2.0.0
 * Author:      Jannie Nguyen Engineering
 * License:     GPL-2.0-or-later
 *
 * @package JannieNguyen\MU
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/jn-hardening/class-jn-hardening-html-headers.php';
require_once __DIR__ . '/jn-hardening/class-jn-hardening-endpoints.php';
require_once __DIR__ . '/jn-hardening/class-jn-hardening-branding-auth.php';

JN_Hardening_HTML_Headers::init();
JN_Hardening_Endpoints::init();
JN_Hardening_Branding_Auth::init();
