<?php
/**
 * JN Dev tunnel — serve the local stack correctly through a Cloudflare quick tunnel (*.trycloudflare.com).
 *
 * Covers the Host allow-list in wp-config.php (`jn_tunnel_host()`) and the stored-URL rewrite in
 * mu-plugins/jn-dev-tunnel.php (`jn_dev_tunnel_rewrite_urls()`).
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/test-dev-tunnel.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/jn-test-kit.php';
jn_require_non_production( 'test-dev-tunnel.php' );

echo "=================================================================\n";
echo "JN DEV TUNNEL — HOST ALLOW-LIST & STORED-URL REWRITE\n";
echo "=================================================================\n";

jn_t_section( 'Functions are available' );
$jn_has_host_fn    = function_exists( 'jn_tunnel_host' );
$jn_has_rewrite_fn = function_exists( 'jn_dev_tunnel_rewrite_urls' );
jn_t_assert( $jn_has_host_fn, 'wp-config.php defines jn_tunnel_host()' );
jn_t_assert( $jn_has_rewrite_fn, 'mu-plugins/jn-dev-tunnel.php defines jn_dev_tunnel_rewrite_urls()' );
if ( ! $jn_has_host_fn || ! $jn_has_rewrite_fn ) {
	jn_t_finish();
	return;
}

// -----------------------------------------------------------------------------
// Host allow-list
// -----------------------------------------------------------------------------
jn_t_section( 'jn_tunnel_host()' );
$jn_cf = array( 'HTTP_CF_CONNECTING_IP' => '203.0.113.9' );

jn_t_assert(
	'bookmark-refinance-move-outdoors.trycloudflare.com' === jn_tunnel_host( $jn_cf + array( 'HTTP_HOST' => 'bookmark-refinance-move-outdoors.trycloudflare.com' ), 'local' ),
	'A quick-tunnel Host forwarded by Cloudflare is accepted'
);
jn_t_assert(
	'abc-def.trycloudflare.com' === jn_tunnel_host( $jn_cf + array( 'HTTP_HOST' => 'ABC-DEF.TryCloudflare.com' ), 'local' ),
	'The Host is matched case-insensitively and returned lower-case'
);
jn_t_assert( '' === jn_tunnel_host( array(), 'local' ), 'A request without a Host (WP-CLI, cron) is not a tunnel request' );
jn_t_assert( '' === jn_tunnel_host( $jn_cf + array( 'HTTP_HOST' => 'localhost:8080' ), 'local' ), 'localhost:8080 keeps the .env site URL' );
jn_t_assert( '' === jn_tunnel_host( $jn_cf + array( 'HTTP_HOST' => 'evil.example' ), 'local' ), 'An arbitrary Host is rejected' );
jn_t_assert( '' === jn_tunnel_host( $jn_cf + array( 'HTTP_HOST' => 'x.trycloudflare.com.evil.example' ), 'local' ), 'A look-alike suffix is rejected' );
jn_t_assert( '' === jn_tunnel_host( $jn_cf + array( 'HTTP_HOST' => 'a.b.trycloudflare.com' ), 'local' ), 'A nested sub-domain is rejected' );
jn_t_assert( '' === jn_tunnel_host( $jn_cf + array( 'HTTP_HOST' => 'abc.trycloudflare.com:8080' ), 'local' ), 'A Host carrying a port is rejected' );
jn_t_assert( '' === jn_tunnel_host( array( 'HTTP_HOST' => 'abc.trycloudflare.com' ), 'local' ), 'A tunnel Host without the Cloudflare client-IP header is rejected' );
jn_t_assert( '' === jn_tunnel_host( $jn_cf + array( 'HTTP_HOST' => 'abc.trycloudflare.com' ), 'production' ), 'Tunnel mode never activates in production' );
jn_t_assert( '' === jn_tunnel_host( $jn_cf + array( 'HTTP_HOST' => 'abc.trycloudflare.com' ), 'staging' ), 'Tunnel mode is opt-in to local/development (staging is rejected)' );
jn_t_assert( 'abc.trycloudflare.com' === jn_tunnel_host( $jn_cf + array( 'HTTP_HOST' => 'abc.trycloudflare.com' ), 'development' ), 'Tunnel mode works in the development environment' );
jn_t_assert( '' === jn_tunnel_host( $jn_cf + array( 'HTTP_HOST' => "abc.trycloudflare.com\n" ), 'local' ), 'A Host with a trailing newline is rejected' );
jn_t_assert( '' === jn_tunnel_host( array( 'HTTP_CF_CONNECTING_IP' => 'not-an-ip', 'HTTP_HOST' => 'abc.trycloudflare.com' ), 'local' ), 'A malformed Cloudflare client IP is rejected' );
jn_t_assert( 'abc.trycloudflare.com' === jn_tunnel_host( array( 'HTTP_CF_CONNECTING_IP' => '2001:db8::7', 'HTTP_HOST' => 'abc.trycloudflare.com' ), 'local' ), 'An IPv6 Cloudflare client IP is accepted' );

// -----------------------------------------------------------------------------
// Stored-URL rewrite
// -----------------------------------------------------------------------------
jn_t_section( 'jn_dev_tunnel_rewrite_urls()' );
$jn_from = 'http://localhost:8080';
$jn_to   = 'https://abc.trycloudflare.com';

jn_t_assert(
	'<a href="https://abc.trycloudflare.com/my-story/">' === jn_dev_tunnel_rewrite_urls( '<a href="http://localhost:8080/my-story/">', $jn_from, $jn_to ),
	'Plain absolute URLs are rewritten'
);
jn_t_assert(
	'{"url":"https:\/\/abc.trycloudflare.com\/media\/a.webp"}' === jn_dev_tunnel_rewrite_urls( '{"url":"http:\/\/localhost:8080\/media\/a.webp"}', $jn_from, $jn_to ),
	'JSON-escaped URLs are rewritten'
);
jn_t_assert(
	'u=https%3A%2F%2Fabc.trycloudflare.com%2Fbooking%2F' === jn_dev_tunnel_rewrite_urls( 'u=http%3A%2F%2Flocalhost%3A8080%2Fbooking%2F', $jn_from, $jn_to ),
	'URL-encoded URLs are rewritten'
);
jn_t_assert(
	'<a href="https://schema.org/Person">' === jn_dev_tunnel_rewrite_urls( '<a href="https://schema.org/Person">', $jn_from, $jn_to ),
	'Other hosts are left untouched'
);
jn_t_assert( 'abc' === jn_dev_tunnel_rewrite_urls( 'abc', $jn_from, $jn_from ), 'Identical from/to is a no-op' );
jn_t_assert( '' === jn_dev_tunnel_rewrite_urls( '', $jn_from, $jn_to ), 'An empty buffer stays empty' );

jn_t_section( 'jn_dev_tunnel_map_urls()' );
$jn_has_map_fn = function_exists( 'jn_dev_tunnel_map_urls' );
jn_t_assert( $jn_has_map_fn, 'mu-plugins/jn-dev-tunnel.php defines jn_dev_tunnel_map_urls()' );
if ( $jn_has_map_fn ) {
	$jn_sitemap = array(
		array(
			'loc'     => 'https://abc.trycloudflare.com/my-story/',
			'lastmod' => '2026-10-29',
		),
	);
	$jn_stored  = jn_dev_tunnel_map_urls( $jn_sitemap, $jn_to, $jn_from );
	jn_t_assert( 'http://localhost:8080/my-story/' === $jn_stored[0]['loc'], 'A cached URL list built on the tunnel is stored with the local origin' );
	jn_t_assert( '2026-10-29' === $jn_stored[0]['lastmod'], 'Non-URL fields are kept' );
	jn_t_assert( 'https://abc.trycloudflare.com/my-story/' === $jn_sitemap[0]['loc'], 'The input array is not mutated' );
	jn_t_assert( false === jn_dev_tunnel_map_urls( false, $jn_to, $jn_from ), 'A cache miss (false) passes through' );
}

jn_t_section( 'jn_dev_tunnel_should_rewrite()' );
$jn_has_guard_fn = function_exists( 'jn_dev_tunnel_should_rewrite' );
jn_t_assert( $jn_has_guard_fn, 'mu-plugins/jn-dev-tunnel.php defines jn_dev_tunnel_should_rewrite()' );
if ( $jn_has_guard_fn ) {
	jn_t_assert( jn_dev_tunnel_should_rewrite( false, array() ), 'Front-end pages are rewritten' );
	jn_t_assert( jn_dev_tunnel_should_rewrite( false, array( 'context' => 'view' ) ), 'REST view responses are rewritten' );
	jn_t_assert( ! jn_dev_tunnel_should_rewrite( true, array() ), 'wp-admin screens are not rewritten (forms would save tunnel URLs back)' );
	jn_t_assert( ! jn_dev_tunnel_should_rewrite( false, array( 'context' => 'edit' ) ), 'REST edit-context responses (block editor) are not rewritten' );
}

jn_t_section( 'Inactive outside tunnel mode' );
jn_t_assert( ! defined( 'JN_TUNNEL_HOST' ), 'WP-CLI runs are never in tunnel mode' );
jn_t_assert( false === has_filter( 'wp_redirect', 'jn_dev_tunnel_rewrite_redirect' ), 'No redirect rewrite is hooked outside tunnel mode' );

jn_t_finish();
