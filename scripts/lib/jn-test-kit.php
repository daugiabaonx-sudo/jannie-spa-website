<?php
/**
 * Shared assertion + fixture helpers for the Step 11 (code-review fixes) `wp eval-file` suites.
 *
 * Every fixture created through these helpers is tracked and removed on shutdown, so a fatal error
 * mid-suite does not leave test users/bookings behind in the local database.
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/jn-env-guard.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

$GLOBALS['jn_t_passed']  = 0;
$GLOBALS['jn_t_failed']  = 0;
$GLOBALS['jn_t_run']     = (string) wp_rand( 100000, 999999 );
$GLOBALS['jn_t_cleanup'] = array(
	'posts' => array(),
	'users' => array(),
	'fns'   => array(),
);

/**
 * Assert a condition and print PASS/FAIL.
 *
 * @param bool   $condition Truthy condition.
 * @param string $label     Assertion description.
 * @param string $detail    Optional diagnostic printed on failure.
 * @return void
 */
function jn_t_assert( bool $condition, string $label, string $detail = '' ): void {
	if ( $condition ) {
		++$GLOBALS['jn_t_passed'];
		echo "[PASS] {$label}\n";
		return;
	}
	++$GLOBALS['jn_t_failed'];
	echo "[FAIL] {$label}" . ( '' !== $detail ? " — {$detail}" : '' ) . "\n";
}

/**
 * Print a section header.
 *
 * @param string $title Section title.
 * @return void
 */
function jn_t_section( string $title ): void {
	echo "\n--- {$title} ---\n";
}

/**
 * Build a unique fixture email for this run.
 *
 * @param string $slug Local-part suffix.
 * @return string
 */
function jn_t_email( string $slug ): string {
	return sprintf( 'jn11-%s-%s@example.test', $GLOBALS['jn_t_run'], $slug );
}

/**
 * Register an extra cleanup callback (runs before posts/users are deleted).
 *
 * @param callable $fn Cleanup callback.
 * @return void
 */
function jn_t_on_cleanup( callable $fn ): void {
	$GLOBALS['jn_t_cleanup']['fns'][] = $fn;
}

/**
 * Track a post for cleanup.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function jn_t_track_post( int $post_id ): int {
	if ( $post_id > 0 ) {
		$GLOBALS['jn_t_cleanup']['posts'][] = $post_id;
	}
	return $post_id;
}

/**
 * Create a tracked user.
 *
 * @param string               $role  Role slug.
 * @param string               $email Email address.
 * @param array<string, mixed> $meta  User meta to set.
 * @return int User ID (0 on failure).
 */
function jn_t_create_user( string $role, string $email, array $meta = array() ): int {
	$user_id = wp_insert_user(
		array(
			'user_login' => 'jn11_' . $GLOBALS['jn_t_run'] . '_' . wp_rand( 1000, 99999 ),
			'user_pass'  => wp_generate_password( 24, true, true ),
			'user_email' => $email,
			'role'       => $role,
		)
	);
	if ( is_wp_error( $user_id ) ) {
		echo '[WARN] Could not create fixture user: ' . $user_id->get_error_message() . "\n";
		return 0;
	}
	$GLOBALS['jn_t_cleanup']['users'][] = (int) $user_id;
	foreach ( $meta as $key => $value ) {
		update_user_meta( (int) $user_id, $key, $value );
	}
	return (int) $user_id;
}

/**
 * Create a tracked `jn_booking` post with the given meta.
 *
 * @param array<string, mixed> $meta   Post meta.
 * @param string               $status Booking post status.
 * @return int Booking ID (0 on failure).
 */
function jn_t_create_booking( array $meta, string $status = '' ): int {
	$post_id = wp_insert_post(
		array(
			'post_type'   => JN_Booking_CPT::POST_TYPE,
			'post_status' => '' !== $status ? $status : JN_Booking_CPT::STATUS_PENDING,
			'post_title'  => 'JN11-FIXTURE',
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		echo '[WARN] Could not create fixture booking: ' . $post_id->get_error_message() . "\n";
		return 0;
	}
	$defaults = array(
		'_jn_customer_user_id' => 0,
		'_jn_booking_code'     => 'JN11-' . $GLOBALS['jn_t_run'] . '-' . (int) $post_id,
	);
	foreach ( array_merge( $defaults, $meta ) as $key => $value ) {
		update_post_meta( (int) $post_id, $key, $value );
	}
	return jn_t_track_post( (int) $post_id );
}

/**
 * Remove every tracked fixture. Safe to call more than once.
 *
 * @return void
 */
function jn_t_cleanup(): void {
	$queue = $GLOBALS['jn_t_cleanup'];

	$GLOBALS['jn_t_cleanup'] = array(
		'posts' => array(),
		'users' => array(),
		'fns'   => array(),
	);

	foreach ( $queue['fns'] as $fn ) {
		try {
			$fn();
		} catch ( \Throwable $e ) {
			echo '[WARN] Cleanup callback failed: ' . $e->getMessage() . "\n";
		}
	}
	foreach ( array_unique( $queue['posts'] ) as $post_id ) {
		wp_delete_post( (int) $post_id, true );
	}
	foreach ( array_unique( $queue['users'] ) as $user_id ) {
		wp_delete_user( (int) $user_id );
	}
}

/**
 * Print the summary line, clean fixtures and exit non-zero on failure.
 *
 * @return void
 */
function jn_t_finish(): void {
	jn_t_cleanup();
	wp_set_current_user( 0 );
	echo "\nRESULT: {$GLOBALS['jn_t_passed']} passed, {$GLOBALS['jn_t_failed']} failed\n";
	if ( $GLOBALS['jn_t_failed'] > 0 ) {
		exit( 1 );
	}
}

register_shutdown_function( 'jn_t_cleanup' );
