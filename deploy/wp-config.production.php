<?php
/**
 * Production WordPress Configuration — Jannie Nguyen Website (`deploy/wp-config.production.php`).
 *
 * Copy this file to `/var/www/<PROD_DOMAIN>/wordpress/wp-config.php` on the production host
 * alongside a root-owned (0600) `.env.production` loaded into the PHP-FPM / Apache / LiteSpeed
 * environment. Contains zero hardcoded secrets.
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

// ** 1. Database settings (loaded strictly from environment variables) ** //
define( 'DB_NAME', jn_env( 'DB_NAME' ) );
define( 'DB_USER', jn_env( 'DB_USER' ) );
define( 'DB_PASSWORD', jn_env( 'DB_PASSWORD' ) );
define( 'DB_HOST', jn_env( 'DB_HOST', 'localhost' ) );
define( 'DB_CHARSET', jn_env( 'DB_CHARSET', 'utf8mb4' ) );
define( 'DB_COLLATE', jn_env( 'DB_COLLATE', '' ) );

// ** 2. Fresh cryptographic salts (generated via ./deploy/generate-salts.sh) ** //
define( 'AUTH_KEY', jn_env( 'AUTH_KEY' ) );
define( 'SECURE_AUTH_KEY', jn_env( 'SECURE_AUTH_KEY' ) );
define( 'LOGGED_IN_KEY', jn_env( 'LOGGED_IN_KEY' ) );
define( 'NONCE_KEY', jn_env( 'NONCE_KEY' ) );
define( 'AUTH_SALT', jn_env( 'AUTH_SALT' ) );
define( 'SECURE_AUTH_SALT', jn_env( 'SECURE_AUTH_SALT' ) );
define( 'LOGGED_IN_SALT', jn_env( 'LOGGED_IN_SALT' ) );
define( 'NONCE_SALT', jn_env( 'NONCE_SALT' ) );

// ** 3. Database table prefix (randomized, never wp_) ** //
$table_prefix = jn_env( 'DB_TABLE_PREFIX', 'jn_8f4c_' );

// ** 4. Production environment type & canonical HTTPS URLs ** //
define( 'WP_ENVIRONMENT_TYPE', 'production' );
define( 'WP_HOME', rtrim( jn_env( 'WP_HOME', 'https://PROD_DOMAIN' ), '/' ) );
define( 'WP_SITEURL', rtrim( jn_env( 'WP_SITEURL', 'https://PROD_DOMAIN' ), '/' ) );

// ** 5. Install-time Hide-WordPress directory constants ** //
// content = app; plugins = modules; uploads = media
define( 'WP_CONTENT_DIR', __DIR__ . '/app' );
define( 'WP_CONTENT_URL', WP_HOME . '/app' );

define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/modules' );
define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/modules' );
define( 'PLUGINDIR', 'app/modules' );

define( 'WPMU_PLUGIN_DIR', WP_CONTENT_DIR . '/mu-plugins' );
define( 'WPMU_PLUGIN_URL', WP_CONTENT_URL . '/mu-plugins' );

define( 'UPLOADS', 'media' );

// ** 6. Page Cache (WP Super Cache / LiteSpeed Cache) ** //
define( 'WP_CACHE', true );
define( 'WPCACHEHOME', WP_PLUGIN_DIR . '/wp-super-cache/' );

// ** 7. Production debugging strictly OFF ** //
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_DEBUG_LOG', false );
// phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed
@ini_set( 'display_errors', '0' );

// ** 8. SSL enforcement, real system cron & editorial hardening ** //
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === strtolower( (string) $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
	$_SERVER['HTTPS'] = 'on';
}
define( 'FORCE_SSL_ADMIN', true );

// Disable pseudo-cron on page requests; run real OS cron every 5 minutes (see deploy/cron.d-jannie-wordpress):
// */5 * * * * www-data /usr/bin/php /var/www/PROD_DOMAIN/wordpress/wp-cron.php >/dev/null 2>&1
define( 'DISABLE_WP_CRON', true );

define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_POST_REVISIONS', 10 );
define( 'EMPTY_TRASH_DAYS', 14 );
define( 'CORE_UPGRADE_SKIP_NEW_BUNDLED', true );
define( 'FS_METHOD', 'direct' );

/* That's all, stop editing! Happy publishing. */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
