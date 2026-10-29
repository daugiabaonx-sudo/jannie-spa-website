<?php
/**
 * Custom WordPress configuration for the Jannie Nguyen project.
 *
 * All database credentials and cryptographic salts are loaded from environment
 * variables (.env in local Docker) so this file holds zero secrets and can be
 * safely committed to version control.
 *
 * @package JannieNguyen
 */

/**
 * Retrieve an environment variable with a safe fallback.
 *
 * @param string $key     Environment variable name.
 * @param string $default Fallback value when the variable is not set.
 * @return string
 */
function jn_env( string $key, string $default = '' ): string {
	$value = getenv( $key );
	if ( false !== $value && '' !== $value ) {
		return (string) $value;
	}
	if ( isset( $_ENV[ $key ] ) && '' !== $_ENV[ $key ] ) {
		return (string) $_ENV[ $key ];
	}
	if ( isset( $_SERVER[ $key ] ) && '' !== $_SERVER[ $key ] ) {
		return (string) $_SERVER[ $key ];
	}
	return $default;
}

// ** Database settings from environment variables ** //
define( 'DB_NAME', jn_env( 'DB_NAME', 'jn_wordpress' ) );
define( 'DB_USER', jn_env( 'DB_USER', 'jn_db_user' ) );
define( 'DB_PASSWORD', jn_env( 'DB_PASSWORD', '' ) );
define( 'DB_HOST', jn_env( 'DB_HOST', 'db' ) );
define( 'DB_CHARSET', jn_env( 'DB_CHARSET', 'utf8mb4' ) );
define( 'DB_COLLATE', jn_env( 'DB_COLLATE', '' ) );

// ** Authentication unique keys and salts from environment variables ** //
define( 'AUTH_KEY', jn_env( 'AUTH_KEY' ) );
define( 'SECURE_AUTH_KEY', jn_env( 'SECURE_AUTH_KEY' ) );
define( 'LOGGED_IN_KEY', jn_env( 'LOGGED_IN_KEY' ) );
define( 'NONCE_KEY', jn_env( 'NONCE_KEY' ) );
define( 'AUTH_SALT', jn_env( 'AUTH_SALT' ) );
define( 'SECURE_AUTH_SALT', jn_env( 'SECURE_AUTH_SALT' ) );
define( 'LOGGED_IN_SALT', jn_env( 'LOGGED_IN_SALT' ) );
define( 'NONCE_SALT', jn_env( 'NONCE_SALT' ) );

// ** Database table prefix (randomized, never wp_) ** //
$table_prefix = jn_env( 'DB_TABLE_PREFIX', 'jn_8f4c_' );

/**
 * Return the public Host of a request that arrived through a Cloudflare quick tunnel, or '' otherwise.
 *
 * Only a single-label `*.trycloudflare.com` Host that carries a valid Cloudflare client IP is accepted,
 * and only in the local/development environments, so a spoofed Host cannot repoint the site URLs.
 *
 * @param array<string, mixed> $server      Request server vars (normally `$_SERVER`).
 * @param string               $environment WP_ENVIRONMENT_TYPE.
 * @return string Lower-case tunnel host, or '' when the request is not a tunnel request.
 */
function jn_tunnel_host( array $server, string $environment ): string {
	if ( ! in_array( $environment, array( 'local', 'development' ), true ) ) {
		return '';
	}
	if ( false === filter_var( (string) ( $server['HTTP_CF_CONNECTING_IP'] ?? '' ), FILTER_VALIDATE_IP ) ) {
		return '';
	}
	$host = strtolower( (string) ( $server['HTTP_HOST'] ?? '' ) );
	return 1 === preg_match( '/^[a-z0-9-]+\.trycloudflare\.com\z/', $host ) ? $host : '';
}

// ** Environment type & canonical site URLs ** //
define( 'WP_ENVIRONMENT_TYPE', jn_env( 'WP_ENVIRONMENT_TYPE', 'local' ) );
define( 'JN_LOCAL_HOME', rtrim( jn_env( 'WP_HOME', 'http://localhost:8080' ), '/' ) );

// A Cloudflare quick tunnel (`cloudflared tunnel --url http://localhost:8080`) forwards the public
// https Host, so serve the page on that host instead of the loopback-only .env URL.
$jn_tunnel_host = jn_tunnel_host( $_SERVER, WP_ENVIRONMENT_TYPE );
if ( '' !== $jn_tunnel_host ) {
	$_SERVER['HTTPS']       = 'on';
	$_SERVER['SERVER_PORT'] = '443';
	// Every tunnel visitor reaches Apache from cloudflared's address; use the real client IP so the
	// per-IP login and booking rate limits do not lock out the whole audience at once.
	$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
	define( 'JN_TUNNEL_HOST', $jn_tunnel_host );
	define( 'WP_HOME', 'https://' . $jn_tunnel_host );
	define( 'WP_SITEURL', WP_HOME );
} else {
	define( 'WP_HOME', JN_LOCAL_HOME );
	define( 'WP_SITEURL', rtrim( jn_env( 'WP_SITEURL', 'http://localhost:8080' ), '/' ) );
}

// ** Install-time Hide-WordPress directory constants (PROJECT CONTEXT CARD) ** //
// content = app; plugins = modules; uploads = media
define( 'WP_CONTENT_DIR', __DIR__ . '/app' );
define( 'WP_CONTENT_URL', WP_HOME . '/app' );

define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/modules' );
define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/modules' );
define( 'PLUGINDIR', 'app/modules' );

// ** Page Cache (WP Super Cache — Step 8 Task 2) ** //
define( 'WP_CACHE', true );
define( 'WPCACHEHOME', WP_PLUGIN_DIR . '/wp-super-cache/' );

define( 'WPMU_PLUGIN_DIR', WP_CONTENT_DIR . '/mu-plugins' );
define( 'WPMU_PLUGIN_URL', WP_CONTENT_URL . '/mu-plugins' );

define( 'UPLOADS', 'media' );

// ** Debugging & logging (disabled in production; log stored outside web root in local/staging) ** //
$jn_is_production = ( 'production' === WP_ENVIRONMENT_TYPE );
define( 'WP_DEBUG', ! $jn_is_production );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_DEBUG_LOG', $jn_is_production ? false : '/var/log/wordpress/debug.log' );
// phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed
@ini_set( 'display_errors', '0' );

// ** Security, SSL, Cron & editorial hygiene ** //
define( 'FORCE_SSL_ADMIN', $jn_is_production || 'true' === jn_env( 'FORCE_SSL_ADMIN', 'false' ) );
define( 'DISABLE_WP_CRON', $jn_is_production || 'true' === jn_env( 'DISABLE_WP_CRON', 'false' ) );
define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_POST_REVISIONS', 10 );
define( 'CORE_UPGRADE_SKIP_NEW_BUNDLED', true );
define( 'FS_METHOD', 'direct' );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
