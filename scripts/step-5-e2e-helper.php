<?php
/**
 * JN Step 5 — E2E Helper for Preparing & Cleaning Up Booking Engine E2E Entities.
 *
 * Usage:
 *   ./scripts/wp.sh eval '$action="prepare"; require "/scripts/step-5-e2e-helper.php";'
 *   ./scripts/wp.sh eval '$action="cleanup"; require "/scripts/step-5-e2e-helper.php";'
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Destructive local-only script: refuse to run against production (review 2026-10-28, H2).
require_once __DIR__ . '/lib/jn-env-guard.php';
jn_require_non_production( basename( __FILE__ ) );

$action = isset( $action ) ? (string) $action : 'prepare';

/**
 * Remove all `jn_booking` posts, linked orders, `.ics` temp files, and test users.
 *
 * @return array<string, mixed>
 */
function jn_step5_purge_test_entities(): array {
	$booking_ids = get_posts(
		array(
			'post_type'      => JN_Booking_CPT::POST_TYPE,
			'post_status'    => array_keys( JN_Booking_CPT::get_statuses() ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	$deleted_bookings = array();
	foreach ( $booking_ids as $bid ) {
		$ics_file = JN_Booking_ICS::write_attachment_file( (int) $bid );
		if ( '' !== $ics_file && file_exists( $ics_file ) ) {
			wp_delete_file( $ics_file );
		}
		wp_delete_post( (int) $bid, true );
		$deleted_bookings[] = (int) $bid;
	}

	$order_ids      = wc_get_orders(
		array(
			'limit'  => -1,
			'return' => 'ids',
		)
	);
	$deleted_orders = array();
	foreach ( $order_ids as $oid ) {
		$ord = wc_get_order( (int) $oid );
		if ( $ord ) {
			$ord->delete( true );
			$deleted_orders[] = (int) $oid;
		}
	}

	require_once ABSPATH . 'wp-admin/includes/user.php';
	$cust_users    = get_users(
		array(
			'role__in' => array( 'customer', JN_Booking_Roles::ROLE_ASSISTANT ),
			'fields'   => 'ID',
		)
	);
	$deleted_users = array();
	foreach ( $cust_users as $uid ) {
		wp_delete_user( (int) $uid );
		$deleted_users[] = (int) $uid;
	}

	global $wpdb;
	// Clear rate-limit transients.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_jn_rl_%' OR option_name LIKE '_transient_timeout_jn_rl_%'" );

	JN_Booking_Settings::update( JN_Booking_Settings::get_defaults() );

	return array(
		'deleted_bookings' => $deleted_bookings,
		'deleted_orders'   => $deleted_orders,
		'deleted_users'    => $deleted_users,
	);
}

if ( 'prepare' === $action ) {
	JN_Coaching_Packages::ensure_packages_exist();
	JN_Booking_Roles::sync_roles_and_caps();
	$purged = jn_step5_purge_test_entities();
	echo 'STEP5_PREPARE_JSON=' . wp_json_encode( $purged ) . PHP_EOL;
} elseif ( 'cleanup' === $action ) {
	$purged = jn_step5_purge_test_entities();
	echo 'STEP5_CLEANUP_JSON=' . wp_json_encode( $purged ) . PHP_EOL;
}
