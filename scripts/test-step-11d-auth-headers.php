<?php
/**
 * JN Step 11d — Code-review fixes: login, security headers & HTML scrubber (M1, M5, M20, M21, M22).
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/test-step-11d-auth-headers.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/jn-test-kit.php';
jn_require_non_production( 'test-step-11d-auth-headers.php' );

echo "=================================================================\n";
echo "JN STEP 11d — LOGIN, HEADERS & HTML SCRUBBER\n";
echo "=================================================================\n";

// -----------------------------------------------------------------------------
// M1 — a successful login does not reset the per-IP failure counter
// -----------------------------------------------------------------------------
jn_t_section( 'M1 — login rate limit' );
$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
jn_t_on_cleanup(
	static function () {
		foreach ( array( 'x1', 'x2', 'x3', 'x4', 'x5', 'x6' ) as $name ) {
			JN_Hardening_Branding_Auth::reset_rate_limit( $name );
		}
	}
);
JN_Hardening_Branding_Auth::reset_rate_limit( '' );
$jn_valid_id = jn_t_create_user( 'customer', jn_t_email( 'valid-login' ) );
$jn_valid    = get_userdata( $jn_valid_id );
JN_Hardening_Branding_Auth::record_failed_attempt( 'x1' );
JN_Hardening_Branding_Auth::record_failed_attempt( 'x2' );
JN_Hardening_Branding_Auth::record_failed_attempt( 'x3' );
do_action( 'wp_login', $jn_valid->user_login, $jn_valid );
JN_Hardening_Branding_Auth::record_failed_attempt( 'x4' );
JN_Hardening_Branding_Auth::record_failed_attempt( 'x5' );
jn_t_assert( JN_Hardening_Branding_Auth::is_rate_limited( 'x6' ), 'A valid login in between does not reset the IP counter (5 failures still lock the IP)' );

// -----------------------------------------------------------------------------
// M20 — new-user email keeps the set-password link
// -----------------------------------------------------------------------------
jn_t_section( 'M20 — new user notification' );
$jn_core_email = array(
	'to'      => jn_t_email( 'new-user' ),
	'subject' => '[Site] Login Details',
	'message' => "Username: jn11user\r\n\r\nTo set your password, visit the following address:\r\n\r\nhttp://localhost:8080/wp-login.php?action=rp&key=JN11KEY&login=jn11user\r\n\r\nhttp://localhost:8080/wp-login.php\r\n",
	'headers' => '',
);
$jn_filtered   = apply_filters( 'wp_new_user_notification_email', $jn_core_email, $jn_valid, 'Site' );
jn_t_assert( false !== strpos( (string) $jn_filtered['message'], 'key=JN11KEY' ), 'The branded new-user email still contains the set-password link' );
jn_t_assert( false !== strpos( (string) $jn_filtered['subject'], 'Jannie Nguyen Academy' ), 'The new-user email subject stays branded' );

// -----------------------------------------------------------------------------
// M21 — only enumeration-sensitive login errors are genericized
// -----------------------------------------------------------------------------
jn_t_section( 'M21 — login error messages' );
jn_t_assert( false === has_filter( 'login_errors', array( 'JN_Hardening_Branding_Auth', 'sanitize_login_error_display' ) ), 'The blanket login_errors rewrite is gone' );
$jn_expired = apply_filters( 'wp_login_errors', new WP_Error( 'expiredkey', 'Liên kết đặt lại mật khẩu đã hết hạn.' ), '' );
jn_t_assert( 'Liên kết đặt lại mật khẩu đã hết hạn.' === $jn_expired->get_error_message( 'expiredkey' ), 'An expired reset-key message is shown as is' );
$jn_unknown = apply_filters( 'wp_login_errors', new WP_Error( 'invalid_username', 'Unknown username. Check again.' ), '' );
jn_t_assert( JN_Hardening_Branding_Auth::get_generic_invalid_credentials_message() === $jn_unknown->get_error_message(), 'An unknown-username message is replaced by the generic message' );
$jn_combo = apply_filters( 'wp_login_errors', new WP_Error( 'invalidcombo', 'There is no account with that username or email address.' ), '' );
jn_t_assert( false === strpos( $jn_combo->get_error_message(), 'no account' ), 'The lost-password "no account" message does not reveal whether an account exists' );
$jn_info = apply_filters( 'wp_login_errors', new WP_Error( 'confirm', 'Kiểm tra email để lấy liên kết xác nhận.', 'message' ), '' );
jn_t_assert( 'message' === $jn_info->get_error_data( 'confirm' ), 'Informational login messages keep their message type' );

// -----------------------------------------------------------------------------
// M5 — CSP violation reports are collected; headers have a PHP fallback
// -----------------------------------------------------------------------------
jn_t_section( 'M5 — CSP reporting' );
$jn_headers = JN_Hardening_HTML_Headers::get_security_headers();
jn_t_assert( false !== strpos( (string) $jn_headers['Content-Security-Policy-Report-Only'], 'report-uri /api/jn/v1/csp-report' ), 'The CSP header names a report-uri' );
// deploy/ and docker/ configs are checked host-side by scripts/verify-step-11b-deploy.sh.
$jn_htaccess = (string) file_get_contents( ABSPATH . '.htaccess' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
jn_t_assert( false !== strpos( $jn_htaccess, 'report-uri /api/jn/v1/csp-report' ), 'Local Apache .htaccess CSP carries the same report-uri' );

$jn_report = new WP_REST_Request( 'POST', '/jn/v1/csp-report' );
$jn_report->set_header( 'Content-Type', 'application/csp-report' );
$jn_report->set_body( wp_json_encode( array( 'csp-report' => array( 'document-uri' => home_url( '/coaching/?email=a@b.c' ), 'violated-directive' => 'script-src', 'blocked-uri' => 'https://evil.example/x.js' ) ) ) );
$GLOBALS['jn_t_logged'] = array();
$jn_capture             = static function ( $message ) {
	$GLOBALS['jn_t_logged'][] = (string) $message;
	return $message;
};
add_filter( 'woocommerce_logger_log_message', $jn_capture );
wp_set_current_user( 0 );
$jn_resp = rest_do_request( $jn_report );
remove_filter( 'woocommerce_logger_log_message', $jn_capture );
jn_t_assert( 204 === $jn_resp->get_status(), 'The CSP report endpoint accepts anonymous reports (204)', 'HTTP ' . $jn_resp->get_status() );
$jn_logged = implode( "\n", $GLOBALS['jn_t_logged'] );
jn_t_assert( false !== strpos( $jn_logged, 'script-src' ) && false === strpos( $jn_logged, 'a@b.c' ), 'Reports are logged without query strings (no PII)' );

$jn_big = new WP_REST_Request( 'POST', '/jn/v1/csp-report' );
$jn_big->set_body( str_repeat( 'A', 20000 ) );
jn_t_assert( 413 === rest_do_request( $jn_big )->get_status(), 'Oversized reports are rejected (413)' );

// -----------------------------------------------------------------------------
// M22 — HTML scrubber safety
// -----------------------------------------------------------------------------
jn_t_section( 'M22 — HTML scrubber' );
$jn_in  = '<html><body><script>var s = "<!-- keep -->"; if (a --> 0) {}</script><!-- drop --><pre><!-- keep-pre --></pre><p>ok</p></body></html>';
$jn_out = JN_Hardening_HTML_Headers::scrub_html_output( $jn_in );
jn_t_assert( false !== strpos( $jn_out, '"<!-- keep -->"; if (a --> 0)' ), 'Comment-like text inside <script> is left intact' );
jn_t_assert( false !== strpos( $jn_out, '<!-- keep-pre -->' ), 'Comment-like text inside <pre> is left intact' );
jn_t_assert( false === strpos( $jn_out, 'drop' ), 'Real HTML comments are still stripped' );

$jn_json_cases = array(
	'<script>x={"a":1,"generator":"WP 6.9"}</script>' => '<script>x={"a":1}</script>',
	'<script>x={"generator":"WP","a":1}</script>'     => '<script>x={"a":1}</script>',
	'<script>x={"generator":"WP"}</script>'           => '<script>x={}</script>',
);
foreach ( $jn_json_cases as $jn_case_in => $jn_case_out ) {
	jn_t_assert( $jn_case_out === JN_Hardening_HTML_Headers::scrub_html_output( $jn_case_in ), 'generator removal keeps valid JSON: ' . $jn_case_out );
}

$jn_wc   = rawurlencode( wp_json_encode( array( 'wpVersion' => '6.9', 'emptyMap' => new stdClass(), 'list' => array() ) ) );
$jn_wcs  = JN_Hardening_HTML_Headers::scrub_html_output( "<script>var wcSettings = JSON.parse( decodeURIComponent( '{$jn_wc}' ) );</script>" );
preg_match( "/decodeURIComponent\\( '([^']+)' \\)/", $jn_wcs, $jn_m );
$jn_decoded = isset( $jn_m[1] ) ? rawurldecode( str_replace( '\\', '', $jn_m[1] ) ) : '';
jn_t_assert( false !== strpos( $jn_decoded, '"emptyMap":{}' ) && false !== strpos( $jn_decoded, '"list":[]' ), 'wcSettings keeps empty objects as {} (not [])', $jn_decoded );

$jn_limit = ini_get( 'pcre.backtrack_limit' );
ini_set( 'pcre.backtrack_limit', '1' ); // phpcs:ignore WordPress.PHP.IniSet.Risky
$jn_pcre = JN_Hardening_HTML_Headers::scrub_html_output( '<p>body</p><!-- ' . str_repeat( 'a', 5000 ) . ' -->' );
ini_set( 'pcre.backtrack_limit', (string) $jn_limit ); // phpcs:ignore WordPress.PHP.IniSet.Risky
jn_t_assert( '' !== $jn_pcre, 'A PCRE failure never blanks the page' );

jn_t_assert( ! JN_Hardening_HTML_Headers::is_non_html_request(), 'A normal page request is buffered for scrubbing' );
$_GET['wc-ajax'] = 'update_order_review';
$jn_is_json      = JN_Hardening_HTML_Headers::is_non_html_request();
unset( $_GET['wc-ajax'] );
jn_t_assert( $jn_is_json, 'wc-ajax JSON responses are never run through the HTML scrubber' );

$jn_buffered = JN_Hardening_HTML_Headers::scrub_buffer( '<html><!-- first --><p>x</p></html>' );
jn_t_assert( false === strpos( $jn_buffered, 'first' ), 'The output-buffer callback scrubs the document' );
jn_t_assert( '<html><!-- outer --></html>' === JN_Hardening_HTML_Headers::scrub_buffer( '<html><!-- outer --></html>' ), 'Outer nested buffers pass an already-scrubbed document through (scrub once)' );

jn_t_finish();
