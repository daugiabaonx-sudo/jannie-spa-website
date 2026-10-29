<?php
/**
 * Plugin Name: JN Dev Tunnel (MU)
 * Description: Local demos only. When wp-config.php detects a Cloudflare quick tunnel (JN_TUNNEL_HOST), rewrites the absolute localhost URLs stored in content, meta and options to the tunnel host on the way out. No-op everywhere else, including production.
 * Version:     1.0.0
 * Author:      Jannie Nguyen Engineering
 * License:     GPL-2.0-or-later
 *
 * @package JannieNguyen\MU
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrite every absolute `$from` URL in `$content` to `$to`, including the JSON-escaped (`http:\/\/…`)
 * and URL-encoded (`http%3A%2F%2F…`) forms that inline scripts and share links use.
 *
 * @param string $content Text to rewrite.
 * @param string $from    Origin to replace (no trailing slash), e.g. `http://localhost:8080`.
 * @param string $to      Replacement origin (no trailing slash).
 * @return string
 */
function jn_dev_tunnel_rewrite_urls( string $content, string $from, string $to ): string {
	if ( '' === $content || $from === $to ) {
		return $content;
	}

	$json_from = str_replace( '/', '\/', $from );
	$json_to   = str_replace( '/', '\/', $to );

	return str_replace(
		array( $from, $json_from, rawurlencode( $from ), rawurlencode( $json_from ) ),
		array( $to, $json_to, rawurlencode( $to ), rawurlencode( $json_to ) ),
		$content
	);
}

/**
 * Apply `jn_dev_tunnel_rewrite_urls()` to every string inside a (possibly nested) value.
 *
 * @param mixed  $value Value to rewrite; arrays are copied, never mutated.
 * @param string $from  Origin to replace.
 * @param string $to    Replacement origin.
 * @return mixed
 */
function jn_dev_tunnel_map_urls( $value, string $from, string $to ) {
	return map_deep(
		$value,
		static function ( $item ) use ( $from, $to ) {
			return is_string( $item ) ? jn_dev_tunnel_rewrite_urls( $item, $from, $to ) : $item;
		}
	);
}

/**
 * Keep the shared sitemap cache on the local origin so a sitemap built through the tunnel does not
 * leak the (short-lived) tunnel host into localhost responses.
 *
 * @param mixed $value Transient value about to be stored.
 * @return mixed
 */
function jn_dev_tunnel_store_local_urls( $value ) {
	return jn_dev_tunnel_map_urls( $value, WP_HOME, JN_LOCAL_HOME );
}

/**
 * Output-buffer callback: point stored localhost URLs at the tunnel host.
 *
 * @param string $buffer Rendered response.
 * @return string
 */
function jn_dev_tunnel_rewrite_buffer( string $buffer ): string {
	return jn_dev_tunnel_rewrite_urls( $buffer, JN_LOCAL_HOME, WP_HOME );
}

/**
 * Rewrite a redirect `Location` built from a stored localhost URL.
 *
 * @param mixed $location Redirect target.
 * @return mixed
 */
function jn_dev_tunnel_rewrite_redirect( $location ) {
	return is_string( $location ) ? jn_dev_tunnel_rewrite_urls( $location, JN_LOCAL_HOME, WP_HOME ) : $location;
}

/**
 * Whether a response may be rewritten. wp-admin screens and REST `context=edit` responses (the block
 * editor) are left on the local origin: they are posted back and saved, which would persist the
 * short-lived tunnel host in the database.
 *
 * @param bool                 $is_admin Whether the request is a wp-admin request.
 * @param array<string, mixed> $query    Query vars (normally `$_GET`).
 * @return bool
 */
function jn_dev_tunnel_should_rewrite( bool $is_admin, array $query ): bool {
	return ! $is_admin && 'edit' !== ( $query['context'] ?? '' );
}

/**
 * Start the rewrite buffer on `init` priority 0, before endpoints that print and exit early
 * (`/sitemap.xml` serves on `init` priority 1). It sits inside WP Super Cache's buffer, so the page
 * cache stores the rewritten document.
 *
 * @return void
 */
function jn_dev_tunnel_start_buffer(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing check.
	if ( jn_dev_tunnel_should_rewrite( is_admin(), $_GET ) ) {
		ob_start( 'jn_dev_tunnel_rewrite_buffer' );
	}
}

if ( defined( 'JN_TUNNEL_HOST' ) && defined( 'JN_LOCAL_HOME' ) ) {
	add_action( 'init', 'jn_dev_tunnel_start_buffer', 0 );
	add_filter( 'wp_redirect', 'jn_dev_tunnel_rewrite_redirect', 999 );
	add_filter( 'pre_set_transient_jn_seo_sitemap_urls', 'jn_dev_tunnel_store_local_urls' );
}
