<?php
/**
 * WP Super Cache Advanced-Cache Drop-in with Jannie Hardening & Commerce Exclusions.
 *
 * Bypasses page cache before phase 1 when:
 * - Request URI targets Cart, Checkout, My Account (/tai-khoan/), order-pay, REST/AJAX (/api/),
 *   wp-admin (the hidden login screen is never cached: JN_Hardening_Branding_Auth::disable_login_page_cache), robots.txt, llms.txt, or sitemap.xml
 * - Query string exists (?goi=, ?jn_bf=, ?add-to-cart=, etc.)
 * - Request carries a logged-in cookie or an active WooCommerce cart cookie
 *
 * @package JN_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( 'cli' === PHP_SAPI || defined( 'WP_CLI' ) || defined( 'DOING_CRON' ) ) {
	return;
}

$jn_req_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
$jn_has_qs  = ! empty( $_GET ) || ( false !== strpos( $jn_req_uri, '?' ) );

$jn_bypass_cache = $jn_has_qs || ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' !== $_SERVER['REQUEST_METHOD'] && 'HEAD' !== $_SERVER['REQUEST_METHOD'] );

if ( ! $jn_bypass_cache && '' !== $jn_req_uri ) {
	if ( preg_match( '#^/(?:cart|checkout|order-pay|tai-khoan|my-account|api|wp-admin|wp-json)(?:/|$|\?)|(?:robots\.txt|llms\.txt|sitemap.*\.xml|order-pay)#i', $jn_req_uri ) ) {
		$jn_bypass_cache = true;
	}
}

if ( ! $jn_bypass_cache && ! empty( $_COOKIE ) && is_array( $_COOKIE ) ) {
	foreach ( $_COOKIE as $jn_cookie_name => $jn_cookie_val ) {
		$jn_cookie_name = (string) $jn_cookie_name;
		if (
			0 === strpos( $jn_cookie_name, 'wordpress_logged_in_' ) ||
			0 === strpos( $jn_cookie_name, 'comment_author_' ) ||
			( 'woocommerce_items_in_cart' === $jn_cookie_name && '' !== (string) $jn_cookie_val && '0' !== (string) $jn_cookie_val ) ||
			( 'woocommerce_cart_hash' === $jn_cookie_name && '' !== (string) $jn_cookie_val ) ||
			0 === strpos( $jn_cookie_name, 'wp_woocommerce_session_' )
		) {
			$jn_bypass_cache = true;
			break;
		}
	}
}

if ( $jn_bypass_cache ) {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	return;
}

// Fast-path delivery from WP Super Cache static directory for both Desktop & Mobile viewports (HTTP & HTTPS / Cloudflare Tunnel).
$jn_parsed_path = (string) parse_url( $jn_req_uri, PHP_URL_PATH );
$jn_clean_path  = trim( $jn_parsed_path, '/' );
if ( '' === $jn_clean_path || preg_match( '#^[a-z0-9/_-]+$#i', $jn_clean_path ) ) {
	$jn_raw_host  = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : 'localhost';
	$jn_host_only = (string) preg_replace( '/:\d+$/', '', $jn_raw_host );
	$jn_is_https  = ( isset( $_SERVER['HTTPS'] ) && ( 'on' === strtolower( (string) $_SERVER['HTTPS'] ) || '1' === (string) $_SERVER['HTTPS'] ) )
		|| ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( (string) $_SERVER['HTTP_X_FORWARDED_PROTO'] ) );
	$jn_subpath   = '' === $jn_clean_path ? '' : '/' . $jn_clean_path;
	$jn_super_dir = WP_CONTENT_DIR . '/cache/supercache/' . $jn_host_only . $jn_subpath;

	$jn_candidates = $jn_is_https
		? array( $jn_super_dir . '/index-https.html', $jn_super_dir . '/index.html' )
		: array( $jn_super_dir . '/index.html', $jn_super_dir . '/index-https.html' );

	foreach ( $jn_candidates as $jn_cache_file ) {
		if ( is_file( $jn_cache_file ) && is_readable( $jn_cache_file ) && ( time() - (int) filemtime( $jn_cache_file ) ) < 3600 ) {
			if ( ! headers_sent() ) {
				header( 'Content-Type: text/html; charset=UTF-8' );
				header( 'X-Cache-Status: HIT' );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
			readfile( $jn_cache_file );
			exit;
		}
	}

	// Cloudflare Quick Tunnel fast-path fallback: if a warm localhost cache exists, rewrite origin in-memory and serve immediately.
	if ( defined( 'JN_TUNNEL_HOST' ) && defined( 'JN_LOCAL_HOME' ) && defined( 'WP_HOME' ) ) {
		$jn_local_file = WP_CONTENT_DIR . '/cache/supercache/localhost' . $jn_subpath . '/index.html';
		if ( is_file( $jn_local_file ) && is_readable( $jn_local_file ) && ( time() - (int) filemtime( $jn_local_file ) ) < 3600 ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$jn_html = (string) file_get_contents( $jn_local_file );
			if ( '' !== $jn_html ) {
				$jn_from      = (string) JN_LOCAL_HOME;
				$jn_to        = (string) WP_HOME;
				$jn_json_from = str_replace( '/', '\/', $jn_from );
				$jn_json_to   = str_replace( '/', '\/', $jn_to );
				$jn_rewritten = str_replace(
					array( $jn_from, $jn_json_from, rawurlencode( $jn_from ), rawurlencode( $jn_json_from ) ),
					array( $jn_to, $jn_json_to, rawurlencode( $jn_to ), rawurlencode( $jn_json_to ) ),
					$jn_html
				);
				if ( ! headers_sent() ) {
					header( 'Content-Type: text/html; charset=UTF-8' );
					header( 'X-Cache-Status: HIT' );
				}
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Full cached HTML document.
				echo $jn_rewritten;
				exit;
			}
		}
	}
}

if ( defined( 'WPCACHEHOME' ) && is_file( WPCACHEHOME . 'wp-cache-phase1.php' ) ) {
	include_once WPCACHEHOME . 'wp-cache-phase1.php';
}
