<?php
/**
 * JN Step 7 — Automated PHP Integration & Security Verification Suite.
 *
 * Run via:
 *   docker compose exec -T wordpress php -r 'require "wp-load.php"; require "/var/www/html/../scripts/test-step-7-hardening.php";'
 * or:
 *   docker compose run --rm wpcli eval-file /var/www/scripts/test-step-7-hardening.php
 *
 * @package JannieNguyen\Tests
 */

if ( ! defined( 'ABSPATH' ) ) {
	require_once '/var/www/html/wp-load.php';
}

// Destructive local-only script: refuse to run against production (review 2026-10-28, H2).
require_once __DIR__ . '/lib/jn-env-guard.php';
jn_require_non_production( basename( __FILE__ ) );

$GLOBALS['jn_pass'] = 0;
$GLOBALS['jn_fail'] = 0;

/**
 * Assert a boolean condition and print result.
 *
 * @param bool   $condition Condition under test.
 * @param string $label     Human-readable assertion description.
 * @return void
 */
function jn_assert( bool $condition, string $label ): void {
	if ( $condition ) {
		++$GLOBALS['jn_pass'];
		echo "[PASS] {$label}\n";
	} else {
		++$GLOBALS['jn_fail'];
		echo "[FAIL] {$label}\n";
	}
}

echo "=== JN STEP 7: HIDE WORDPRESS & HARDENING INTEGRATION SUITE ===\n\n";

// -----------------------------------------------------------------------------
// Task A: HTML, Headers, Content-Hash Versioning & Body Classes
// -----------------------------------------------------------------------------
echo "--- Task A: HTML, Headers, Content-Hash Versioning & Body Classes ---\n";

jn_assert( '' === apply_filters( 'the_generator', 'WordPress 7.1.3', 'html' ), 'the_generator filter returns empty string' );
jn_assert( '' === apply_filters( 'get_the_generator_html', '<meta name="generator" content="WordPress 7.1.3" />', 'html' ), 'get_the_generator_html returns empty string' );
jn_assert( '' === apply_filters( 'get_the_generator_rss2', '<generator>https://wordpress.org/?v=7.1.3</generator>', 'rss2' ), 'get_the_generator_rss2 returns empty string' );
jn_assert( false === apply_filters( 'x_redirect_by', 'WordPress' ), 'x_redirect_by filter returns false' );

$sample_i18n_json = wp_json_encode(
	array(
		'translation-revision-date' => '2026-10-04',
		'generator'                 => 'GlotPress/4.1.0',
		'domain'                    => 'messages',
		'locale_data'               => array( 'messages' => array( '' => array( 'domain' => 'messages' ) ) ),
		'comment'                   => array( 'reference' => 'wp-includes/js/dist/api-fetch.js' ),
	)
);
$scrubbed_i18n    = JN_Hardening_HTML_Headers::strip_glotpress_generator_from_json( $sample_i18n_json );
jn_assert(
	is_string( $scrubbed_i18n )
	&& false === strpos( $scrubbed_i18n, 'GlotPress' )
	&& false === strpos( $scrubbed_i18n, 'wp-includes' ),
	'load_script_translations strips GlotPress generator and wp-includes reference'
);

$raw_asset_url    = home_url( '/app/themes/jannie/assets/css/tokens.css?ver=1.0.0' );
$hashed_asset_url = JN_Hardening_HTML_Headers::replace_version_with_hash( $raw_asset_url );
$aliased_url      = JN_Hardening_HTML_Headers::alias_asset_url( $hashed_asset_url );
jn_assert(
	false === strpos( $hashed_asset_url, 'ver=' ) && 1 === preg_match( '/\?v=[0-9a-f]{10}$/', $hashed_asset_url ),
	'replace_version_with_hash replaces ?ver=1.0.0 with 10-char SHA-256 content hash (?v=<hash>)'
);
jn_assert(
	str_contains( $aliased_url, '/static/ui/assets/css/tokens.css?v=' ),
	'alias_asset_url maps /app/themes/jannie/ to /static/ui/'
);

$body_classes = JN_Hardening_HTML_Headers::scrub_body_classes(
	array( 'home', 'page', 'wp-theme-blocksy', 'wp-child-theme-jannie', 'blocksy', 'jannie', 'wp-embed-responsive', 'ct-loading' )
);
jn_assert(
	! in_array( 'wp-theme-blocksy', $body_classes, true )
	&& ! in_array( 'wp-child-theme-jannie', $body_classes, true )
	&& ! in_array( 'blocksy', $body_classes, true )
	&& ! in_array( 'jannie', $body_classes, true )
	&& ! in_array( 'wp-embed-responsive', $body_classes, true )
	&& in_array( 'ct-loading', $body_classes, true ),
	'scrub_body_classes drops theme-slug and wp-embed-responsive classes while preserving layout classes'
);

$sample_html = "<!-- WordPress Cache Comment --><style>body{margin:0}/*# sourceURL=http://localhost:8080/wp-includes/blocks/group/style.min.css */</style><script src=\"http://localhost:8080/wp-includes/js/zxcvbn.min.js?ver=7.1.3\"></script><div class=\"blocksy-woo-messages-default\"></div>";
$clean_html  = JN_Hardening_HTML_Headers::scrub_html_output( $sample_html );
jn_assert(
	false === strpos( $clean_html, '<!--' )
	&& false === strpos( $clean_html, 'sourceURL' )
	&& false === strpos( $clean_html, '/wp-includes/' )
	&& false === strpos( $clean_html, 'ver=7.1.3' )
	&& false === strpos( $clean_html, 'blocksy-woo-messages-default' )
	&& str_contains( $clean_html, '/static/core/js/zxcvbn.min.js?v=' ),
	'scrub_html_output strips HTML comments, sourceURLs, /wp-includes/, ?ver=7.1.3, and blocksy-woo-messages-default'
);

// -----------------------------------------------------------------------------
// Task B: Paths, REST Endpoints, XML-RPC, Sitemaps & Author Enumeration
// -----------------------------------------------------------------------------
echo "\n--- Task B: Paths, REST Endpoints, XML-RPC, Sitemaps & Author Enumeration ---\n";

jn_assert( 'api' === rest_get_url_prefix(), 'REST API prefix is "api"' );
jn_assert( false === apply_filters( 'xmlrpc_enabled', true ), 'XML-RPC is disabled via xmlrpc_enabled filter' );
jn_assert( array() === apply_filters( 'xmlrpc_methods', array( 'system.multicall' => 'foo' ) ), 'xmlrpc_methods filter returns empty array' );
jn_assert( false === apply_filters( 'pings_open', true, 1 ), 'Pingbacks are closed via pings_open filter' );
jn_assert( false === apply_filters( 'wp_sitemaps_enabled', true ), 'Core XML sitemaps are disabled via wp_sitemaps_enabled' );

// Anonymous REST /wp/v2/users vs Authenticated Admin REST /wp/v2/users
wp_set_current_user( 0 );
$anon_req = new WP_REST_Request( 'GET', '/wp/v2/users' );
$anon_res = rest_do_request( $anon_req );
jn_assert( 404 === $anon_res->get_status(), 'Anonymous GET /wp/v2/users returns HTTP 404' );

$anon_single_req = new WP_REST_Request( 'GET', '/wp/v2/users/1' );
$anon_single_res = rest_do_request( $anon_single_req );
jn_assert( 404 === $anon_single_res->get_status(), 'Anonymous GET /wp/v2/users/1 returns HTTP 404' );

$admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
if ( ! empty( $admins ) ) {
	wp_set_current_user( $admins[0]->ID );
	$admin_req = new WP_REST_Request( 'GET', '/wp/v2/users' );
	$admin_res = rest_do_request( $admin_req );
	jn_assert( 200 === $admin_res->get_status(), 'Authenticated Administrator GET /wp/v2/users returns HTTP 200 (Block Editor preserved)' );
	wp_set_current_user( 0 );
}

// Booking & Store API routes remain operational for anonymous visitors
$token_req = new WP_REST_Request( 'GET', '/jn/v1/booking/token' );
$token_res = rest_do_request( $token_req );
jn_assert( 200 === $token_res->get_status(), 'Booking token endpoint GET /api/jn/v1/booking/token returns HTTP 200' );

// Author, feed, and sitemap query vars blocked at request time
jn_assert(
	array( 'error' => '404' ) === JN_Hardening_Endpoints::filter_blocked_query_vars( array( 'author' => '1' ) ),
	'Request with ?author=1 query_var is forced to error=404'
);
jn_assert(
	array( 'error' => '404' ) === JN_Hardening_Endpoints::filter_blocked_query_vars( array( 'feed' => 'rss2' ) ),
	'Request with feed=rss2 query_var is forced to error=404 (ADR-010)'
);
jn_assert(
	array( 'error' => '404' ) === JN_Hardening_Endpoints::filter_blocked_query_vars( array( 'sitemap' => 'index' ) ),
	'Request with sitemap=index query_var is forced to error=404'
);

// Front-end admin-ajax.php aliasing
$ajax_url = apply_filters( 'admin_url', 'http://localhost:8080/wp-admin/admin-ajax.php', 'admin-ajax.php', null );
jn_assert(
	str_ends_with( $ajax_url, '/api/ajax' ) && false === strpos( $ajax_url, 'admin-ajax.php' ),
	'Front-end admin_url("admin-ajax.php") aliases to /api/ajax'
);

// Clean robots.txt
$robots_txt = apply_filters( 'robots_txt', '', '1' );
jn_assert(
	false === strpos( $robots_txt, 'wp-admin' )
	&& false === strpos( $robots_txt, 'admin-ajax.php' )
	&& false === strpos( $robots_txt, 'wp-sitemap.xml' )
	&& false === strpos( $robots_txt, 'phong-lam-viec' )
	&& str_contains( $robots_txt, 'Sitemap: ' ),
	'robots.txt exposes zero WordPress paths and never reveals the hidden login slug (cart/checkout/account use noindex, ADR-013)'
);

// -----------------------------------------------------------------------------
// Task C: Branding Traces, Designed 404 Pattern & Vietnamese Emails
// -----------------------------------------------------------------------------
echo "\n--- Task C: Branding Traces, Designed 404 Pattern & Vietnamese Emails ---\n";

$favicon_url      = get_site_icon_url();
$fallback_favicon = JN_Hardening_Branding_Auth::filter_site_icon_url( '' );
jn_assert(
	( str_contains( $favicon_url, 'jn-monogram-icon' ) || str_contains( $favicon_url, 'favicon.svg' ) )
	&& false === strpos( $favicon_url, 'w-logo-blue' )
	&& str_contains( $fallback_favicon, '/static/ui/assets/images/favicon.svg' ),
	'get_site_icon_url returns brand monogram icon (with SVG fallback) and never w-logo-blue'
);

jn_assert(
	home_url( '/' ) === apply_filters( 'login_headerurl', 'https://wordpress.org/' ),
	'login_headerurl points to site home URL'
);
jn_assert(
	'Jannie Nguyen Academy' === apply_filters( 'login_headertext', 'Powered by WordPress' ),
	'login_headertext returns "Jannie Nguyen Academy"'
);
$login_title = apply_filters( 'login_title', 'Đăng nhập &lsaquo; JANNIE NGUYEN &#8212; WordPress', 'Đăng nhập' );
jn_assert(
	false === stripos( $login_title, 'WordPress' ) && str_contains( $login_title, 'Jannie Nguyen Academy' ),
	'login_title contains "Jannie Nguyen Academy" and never "WordPress"'
);

// Designed 404 Pattern registration & markup
$pattern_registry = WP_Block_Patterns_Registry::get_instance();
jn_assert(
	$pattern_registry->is_registered( 'jannie/error-404' ),
	'Pattern jannie/error-404 is registered in WP_Block_Patterns_Registry'
);
$rendered_404 = function_exists( 'jannie_render_404_pattern_markup' ) ? jannie_render_404_pattern_markup() : '';
jn_assert(
	str_contains( $rendered_404, 'Trang Bạn Tìm Kiếm' )
	&& str_contains( $rendered_404, 'Không Tồn Tại' )
	&& str_contains( $rendered_404, '/coaching/#booking-form' ),
	'jannie_render_404_pattern_markup renders Vietnamese 404 copy and /coaching/#booking-form CTA'
);

// Outgoing email branding & Mailpit delivery verification
jn_assert(
	'contact@jannienguyen.vn' === apply_filters( 'wp_mail_from', 'wordpress@localhost' ),
	'wp_mail_from replaces wordpress@localhost with contact@jannienguyen.vn'
);
jn_assert(
	'Jannie Nguyen Academy' === apply_filters( 'wp_mail_from_name', 'WordPress' ),
	'wp_mail_from_name replaces WordPress with Jannie Nguyen Academy'
);

$test_user = ! empty( $admins ) ? $admins[0] : new WP_User( 1 );
$reset_subject = apply_filters( 'retrieve_password_title', '[WordPress] Password Reset', 'admin', $test_user );
$reset_message = apply_filters( 'retrieve_password_message', 'Default WordPress message', 'resetkey123', 'admin', $test_user );
jn_assert(
	false === stripos( $reset_subject, 'WordPress' ) && str_contains( $reset_subject, 'Jannie Nguyen Academy' ),
	'retrieve_password_title uses Vietnamese Jannie Nguyen Academy subject'
);
jn_assert(
	false === stripos( $reset_message, 'WordPress' )
	&& str_contains( $reset_message, '/tai-khoan/lost-password/' )
	&& str_contains( $reset_message, 'Jannie Nguyen Academy' ),
	'retrieve_password_message uses Vietnamese copy and links to /tai-khoan/lost-password/ (never wp-login.php)'
);

$sent = wp_mail(
	'client-step7-verify@example.com',
	'[WordPress] Kiểm tra hệ thống gửi thư',
	'Xin chào từ WordPress <wordpress@localhost>.'
);
jn_assert( true === $sent, 'wp_mail() delivers test email to Mailpit successfully' );

$mailpit_res = wp_remote_get( 'http://mailpit:8025/api/v1/messages?limit=5' );
if ( ! is_wp_error( $mailpit_res ) && 200 === wp_remote_retrieve_response_code( $mailpit_res ) ) {
	$payload  = json_decode( wp_remote_retrieve_body( $mailpit_res ), true );
	$messages = isset( $payload['messages'] ) && is_array( $payload['messages'] ) ? $payload['messages'] : array();
	$latest   = $messages[0] ?? array();
	$from_str = isset( $latest['From'] ) ? wp_json_encode( $latest['From'] ) : '';
	$subj_str = isset( $latest['Subject'] ) ? (string) $latest['Subject'] : '';
	jn_assert(
		str_contains( (string) $from_str, 'contact@jannienguyen.vn' )
		&& str_contains( (string) $from_str, 'Jannie Nguyen Academy' )
		&& false === stripos( $subj_str, 'WordPress' ),
		'Mailpit captured email with From "Jannie Nguyen Academy <contact@jannienguyen.vn>" and scrubbed subject'
	);
}

// -----------------------------------------------------------------------------
// Task D: Auth Rate-Limiting, App Passwords, Registration Lock & Security Headers
// -----------------------------------------------------------------------------
echo "\n--- Task D: Auth Rate-Limiting, App Passwords, Registration Lock & Security Headers ---\n";

$rl_user = 'rate-limit-test@example.com';
JN_Hardening_Branding_Auth::reset_rate_limit( $rl_user );
jn_assert( ! JN_Hardening_Branding_Auth::is_rate_limited( $rl_user ), 'Rate limiter starts unlocked after reset' );

// Attempts 1 to 4: should return generic invalid credentials error (not locked out yet).
for ( $i = 1; $i <= 4; $i++ ) {
	$res = wp_authenticate( $rl_user, 'WrongPassword!' . $i );
	jn_assert(
		is_wp_error( $res ) && 'jn_invalid_credentials' === $res->get_error_code(),
		"Failed login attempt #{$i} returns generic anti-enumeration error (jn_invalid_credentials)"
	);
}

// Attempt 5: reaches threshold (5) and triggers 15-minute lockout.
$res_5 = wp_authenticate( $rl_user, 'WrongPassword!5' );
jn_assert(
	is_wp_error( $res_5 ) && 'jn_login_rate_limited' === $res_5->get_error_code(),
	'Failed login attempt #5 triggers lockout (jn_login_rate_limited)'
);
jn_assert( JN_Hardening_Branding_Auth::is_rate_limited( $rl_user ), 'is_rate_limited() returns true after 5 failed attempts' );

// Verify WooCommerce My Account login error filter also blocks when locked out.
$wc_err = apply_filters( 'woocommerce_process_login_errors', new WP_Error(), $rl_user, 'AnyPassword' );
jn_assert(
	is_wp_error( $wc_err ) && 'jn_login_rate_limited' === $wc_err->get_error_code(),
	'WooCommerce My Account login filter (woocommerce_process_login_errors) blocks locked-out user/IP'
);

// Attempt 6: even a valid administrator login from the locked-out IP is blocked pre-auth!
if ( ! empty( $admins ) ) {
	$admin_login = $admins[0]->user_login;
	$pre_auth    = JN_Hardening_Branding_Auth::enforce_rate_limit_pre_auth( null, $admin_login, 'any-pass' );
	jn_assert(
		is_wp_error( $pre_auth ) && 'jn_login_rate_limited' === $pre_auth->get_error_code(),
		'Locked-out IP is short-circuited before password verification even for valid username'
	);
}

// Reset rate limit so subsequent E2E tests are not locked out.
JN_Hardening_Branding_Auth::reset_rate_limit( $rl_user );
jn_assert( ! JN_Hardening_Branding_Auth::is_rate_limited( $rl_user ), 'Rate limiter unlocks cleanly after reset_rate_limit()' );

// Application Passwords restricted to Administrators only
$subscriber = new WP_User();
$subscriber->add_role( 'customer' );
jn_assert(
	false === apply_filters( 'wp_is_application_passwords_available_for_user', true, $subscriber ),
	'Application Passwords disabled for Customer/non-admin role'
);
if ( ! empty( $admins ) ) {
	jn_assert(
		true === apply_filters( 'wp_is_application_passwords_available_for_user', true, $admins[0] ),
		'Application Passwords enabled for Administrator role'
	);
}

// Core users_can_register off
jn_assert( '0' === (string) get_option( 'users_can_register' ), 'Core users_can_register option is forced to 0 (off)' );

// Security headers dictionary
$sec_headers = JN_Hardening_HTML_Headers::get_security_headers();
$expected_sec = array(
	'Strict-Transport-Security',
	'X-Content-Type-Options',
	'X-Frame-Options',
	'Referrer-Policy',
	'Permissions-Policy',
	'Content-Security-Policy-Report-Only',
);
foreach ( $expected_sec as $header_name ) {
	jn_assert( ! empty( $sec_headers[ $header_name ] ), "Security header configured: {$header_name}" );
}

echo "\n===================================================================\n";
echo "SUMMARY: {$GLOBALS['jn_pass']} PASSED, {$GLOBALS['jn_fail']} FAILED\n";
echo "===================================================================\n";

if ( $GLOBALS['jn_fail'] > 0 ) {
	exit( 1 );
}
