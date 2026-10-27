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

// ** Environment type & canonical site URLs ** //
define( 'WP_ENVIRONMENT_TYPE', jn_env( 'WP_ENVIRONMENT_TYPE', 'local' ) );
define( 'WP_HOME', rtrim( jn_env( 'WP_HOME', 'http://localhost:8080' ), '/' ) );
define( 'WP_SITEURL', rtrim( jn_env( 'WP_SITEURL', 'http://localhost:8080' ), '/' ) );

// ** Install-time Hide-WordPress directory constants (PROJECT CONTEXT CARD) ** //
// content = app; plugins = modules; uploads = media
define( 'WP_CONTENT_DIR', __DIR__ . '/app' );
define( 'WP_CONTENT_URL', WP_HOME . '/app' );

define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/modules' );
define( 'WP_PLUGIN_URL', WP_CONTENT_URL . '/modules' );
define( 'PLUGINDIR', 'app/modules' );

define( 'WPMU_PLUGIN_DIR', WP_CONTENT_DIR . '/mu-plugins' );
define( 'WPMU_PLUGIN_URL', WP_CONTENT_URL . '/mu-plugins' );

define( 'UPLOADS', 'media' );

// ** Debugging & logging (log stored outside web root) ** //
define( 'WP_DEBUG', 'production' !== WP_ENVIRONMENT_TYPE );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_DEBUG_LOG', '/var/log/wordpress/debug.log' );
// phpcs:ignore WordPress.PHP.IniSet.display_errors_Disallowed
@ini_set( 'display_errors', '0' );

// ** Security & editorial hygiene ** //
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
