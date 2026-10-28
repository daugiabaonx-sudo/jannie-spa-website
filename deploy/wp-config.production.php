<?php
/**
 * Production WordPress Configuration — Jannie Nguyen Website (`deploy/wp-config.production.php`).
 *
 * Copy this file to `/var/www/<PROD_DOMAIN>/wordpress/wp-config.php` on the production host.
 * Secrets live in `/var/www/<PROD_DOMAIN>/.env.production` (outside the web root, `root:www-data 0640`),
 * which this file loads itself so PHP-FPM, system cron and WP-CLI all read the same values
 * (override the path with the `JN_ENV_FILE` environment variable). Contains zero hardcoded secrets.
 *
 * @package JannieNguyen
 */

/**
 * Load `KEY=VALUE` lines from an env file into the process environment.
 *
 * Variables already provided by the server/shell environment take precedence. Supports `#` comments,
 * an optional `export ` prefix and single/double quoted values (as written by deploy/generate-salts.sh).
 *
 * @param string $path Absolute path to the env file.
 * @return void
 */
function jn_load_env_file( string $path ): void {
	if ( ! is_readable( $path ) ) {
		return;
	}
	$lines = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	foreach ( false === $lines ? array() : $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line || '#' === $line[0] || false === strpos( $line, '=' ) ) {
			continue;
		}
		list( $key, $value ) = array_map( 'trim', explode( '=', $line, 2 ) );
		if ( 0 === strpos( $key, 'export ' ) ) {
			$key = trim( substr( $key, 7 ) );
		}
		if ( ! preg_match( '/^[A-Z][A-Z0-9_]*$/', $key ) || false !== getenv( $key ) ) {
			continue;
		}
		$quote = strlen( $value ) >= 2 ? $value[0] : '';
		if ( ( '"' === $quote || "'" === $quote ) && substr( $value, -1 ) === $quote ) {
			$value = substr( $value, 1, -1 );
		}
		putenv( "{$key}={$value}" );
		$_ENV[ $key ] = $value;
	}
}

jn_load_env_file( false !== getenv( 'JN_ENV_FILE' ) ? (string) getenv( 'JN_ENV_FILE' ) : dirname( __DIR__ ) . '/.env.production' );

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

// ** 0. Fail closed: never boot production with empty credentials, salts or a placeholder URL ** //
$jn_required_env = array( 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'WP_HOME', 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' );
$jn_missing_env  = array_values(
	array_filter(
		$jn_required_env,
		static fn( string $key ): bool => '' === jn_env( $key )
			|| false !== strpos( jn_env( $key ), 'PROD_DOMAIN' )
			|| false !== strpos( jn_env( $key ), 'REPLACE_WITH_' )
	)
);
if ( ! empty( $jn_missing_env ) ) {
	$jn_env_message = 'Production configuration incomplete — missing or placeholder: ' . implode( ', ', $jn_missing_env ) . '. Check .env.production (JN_ENV_FILE).';
	if ( 'cli' === PHP_SAPI ) {
		fwrite( STDERR, $jn_env_message . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		exit( 1 );
	}
	error_log( $jn_env_message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	http_response_code( 503 );
	header( 'Retry-After: 300' );
	exit( 'Service temporarily unavailable.' );
}
unset( $jn_required_env, $jn_missing_env );

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

// Disable pseudo-cron on page requests; the real OS cron runs every 5 minutes (see deploy/cron.d-jannie-wordpress).
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
