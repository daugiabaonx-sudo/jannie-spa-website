<?php
/**
 * Shared environment guard for destructive local-only maintenance/test scripts.
 *
 * Usage (first statement after the ABSPATH check of a `wp eval-file` script):
 *   require_once __DIR__ . '/lib/jn-env-guard.php';
 *   jn_require_non_production( 'check-launch-blockers.php' );
 *
 * WP-CLI `eval-file` rewrites `__DIR__`/`__FILE__` to the script path, so the relative require works.
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'jn_require_non_production' ) ) {
	/**
	 * Abort the current script (exit code 1) when running against a production environment.
	 *
	 * An explicit `JN_ALLOW_DESTRUCTIVE=1` environment variable overrides the guard for deliberate,
	 * supervised runs (for example a one-off cleanup on a freshly imported staging copy).
	 *
	 * @param string $script Script name shown in the refusal message.
	 * @return void
	 */
	function jn_require_non_production( string $script ): void {
		if ( 'production' !== wp_get_environment_type() ) {
			return;
		}

		if ( '1' === getenv( 'JN_ALLOW_DESTRUCTIVE' ) ) {
			echo "[WARN] {$script}: running against PRODUCTION because JN_ALLOW_DESTRUCTIVE=1 is set.\n";
			return;
		}

		$message = "Refusing to run {$script}: WP_ENVIRONMENT_TYPE is 'production' and this script deletes or rewrites data. Set JN_ALLOW_DESTRUCTIVE=1 to override deliberately.\n";
		if ( defined( 'STDERR' ) ) {
			fwrite( STDERR, $message ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		} else {
			echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		exit( 1 );
	}
}
