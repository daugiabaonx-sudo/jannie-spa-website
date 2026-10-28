<?php
/**
 * JN Step 11c — Code-review fixes: booking & order integrity (H3, H6, H7, M3, M9, M10, M11).
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/test-step-11c-booking-orders.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/jn-test-kit.php';
jn_require_non_production( 'test-step-11c-booking-orders.php' );

echo "=================================================================\n";
echo "JN STEP 11c — BOOKING & ORDER INTEGRITY\n";
echo "=================================================================\n";

$jn_assistant_id = jn_t_create_user( JN_Booking_Roles::ROLE_ASSISTANT, jn_t_email( 'assistant' ) );
wp_set_current_user( $jn_assistant_id );

$GLOBALS['jn_t_slot'] = 0;

/**
 * Create a pending booking for a coaching package.
 *
 * @param string $pkg_key Package key.
 * @param string $slug    Email slug.
 * @return int
 */
function jn11c_booking( string $pkg_key, string $slug ): int {
	$product = JN_Coaching_Packages::get_product_by_key( $pkg_key );
	return jn_t_create_booking(
		array(
			'_jn_customer_email'     => jn_t_email( $slug ),
			'_jn_customer_name'      => 'Khách ' . $slug,
			'_jn_customer_phone'     => '+84901234567',
			'_jn_package_key'        => $pkg_key,
			'_jn_package_product_id' => $product instanceof WC_Product ? $product->get_id() : 0,
			'_jn_package_title'      => $product instanceof WC_Product ? $product->get_name() : $pkg_key,
			'_jn_duration_minutes'   => 60,
			'_jn_total_sessions'     => 'mentorship-3-thang' === $pkg_key ? 12 : 1,
			'_jn_format'             => 'online',
		)
	);
}

/**
 * Confirm a booking on a unique far-future slot.
 *
 * @param int   $booking_id Booking ID.
 * @param array $extra      Extra transition args.
 * @return array|WP_Error
 */
function jn11c_confirm( int $booking_id, array $extra = array() ) {
	++$GLOBALS['jn_t_slot'];
	$res = JN_Booking_Service::transition_status(
		$booking_id,
		JN_Booking_CPT::STATUS_CONFIRMED,
		array_merge(
			array(
				'actor_id'        => (int) $GLOBALS['jn11c_actor'],
				'confirmed_start' => gmdate( 'Y-m-d H:i:s', strtotime( '2031-01-01 09:00:00' ) + $GLOBALS['jn_t_slot'] * 7200 ),
				'format'          => 'online',
				'meeting_link'    => 'https://zoom.example.test/j/' . $booking_id,
				'agreed_price'    => 2500000,
				'allow_overlap'   => true,
			),
			$extra
		)
	);
	if ( is_array( $res ) && ! empty( $res['order_id'] ) ) {
		$order_id = (int) $res['order_id'];
		jn_t_on_cleanup(
			static function () use ( $order_id ) {
				$order = wc_get_order( $order_id );
				if ( $order instanceof WC_Order ) {
					$order->delete( true );
				}
			}
		);
	}
	return $res;
}
$GLOBALS['jn11c_actor'] = $jn_assistant_id;

$GLOBALS['jn11c_cancelled_events'] = array();
add_action(
	'jn_booking_cancelled',
	static function ( $booking_id ) {
		$GLOBALS['jn11c_cancelled_events'][] = (int) $booking_id;
	}
);

/**
 * Count `jn_booking_cancelled` events for a booking.
 *
 * @param int $booking_id Booking ID.
 * @return int
 */
function jn11c_cancel_events( int $booking_id ): int {
	return count( array_keys( $GLOBALS['jn11c_cancelled_events'], $booking_id, true ) );
}

// -----------------------------------------------------------------------------
// H3 — .ics attachments never live in the public uploads directory
// -----------------------------------------------------------------------------
jn_t_section( 'H3 — .ics attachment files are private and short-lived' );
$jn_ics_booking = jn11c_booking( '60-phut', 'ics' );
jn11c_confirm( $jn_ics_booking );
$jn_ics_path  = JN_Booking_ICS::write_attachment_file( $jn_ics_booking );
$jn_uploads   = trailingslashit( (string) wp_upload_dir()['basedir'] );
jn_t_assert( '' !== $jn_ics_path && file_exists( $jn_ics_path ), 'An .ics attachment is written for a confirmed booking' );
jn_t_assert( 0 !== strpos( $jn_ics_path, $jn_uploads ), 'The .ics attachment is written outside the public uploads directory' );
JN_Booking_ICS::cleanup_attachment_files();
jn_t_assert( ! file_exists( $jn_ics_path ), 'Attachment files are deleted by the end-of-request cleanup' );

wp_mkdir_p( $jn_uploads . 'jn-ics-tmp' );
file_put_contents( $jn_uploads . 'jn-ics-tmp/lich-hen-legacy.ics', 'BEGIN:VCALENDAR' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
JN_Booking_ICS::purge_legacy_upload_dir();
jn_t_assert( ! is_dir( $jn_uploads . 'jn-ics-tmp' ), 'The legacy public media/jn-ics-tmp directory is purged' );

// -----------------------------------------------------------------------------
// M9 — tentative times are never shown or exported as confirmed
// -----------------------------------------------------------------------------
jn_t_section( 'M9 — tentative schedule stays internal' );
$jn_pending = jn11c_booking( '60-phut', 'pending' );
update_post_meta( $jn_pending, '_jn_confirmed_start', '2031-02-01 10:00:00' );
jn_t_assert( '' === JN_Booking_ICS::generate_content( $jn_pending ), 'No .ics is generated for a booking that is not confirmed' );
jn_t_assert( ! JN_Booking_Service::is_schedule_visible_to_customer( $jn_pending ), 'A pending booking with a tentative time does not expose that time to the customer' );
jn_t_assert( JN_Booking_Service::is_schedule_visible_to_customer( $jn_ics_booking ), 'A confirmed booking exposes its time to the customer' );

$jn_m9 = jn11c_booking( '60-phut', 'm9-save' );
jn11c_confirm( $jn_m9 );
$_POST = array(
	'jn_booking_admin_nonce' => wp_create_nonce( 'jn_save_booking_' . $jn_m9 ),
	'jn_confirmed_date'      => '2031-03-01',
	'jn_confirmed_time'      => '10:00',
	'jn_format'              => 'online',
	'jn_meeting_link'        => '',
	'jn_target_status'       => JN_Booking_CPT::STATUS_CONFIRMED,
);
delete_transient( 'jn_booking_admin_err_' . $jn_assistant_id );
JN_Booking_Admin::handle_save_booking_metabox( $jn_m9, get_post( $jn_m9 ) );
$_POST = array();
jn_t_assert( false !== get_transient( 'jn_booking_admin_err_' . $jn_assistant_id ), 'A rejected reschedule on the edit screen surfaces an admin error instead of failing silently' );

// -----------------------------------------------------------------------------
// H7 — WooCommerce order outcomes flow back to the booking
// -----------------------------------------------------------------------------
jn_t_section( 'H7 — order cancelled / refunded / failed sync to the booking' );
$jn_h7_cancel = jn11c_booking( '60-phut', 'h7-cancel' );
$jn_res       = jn11c_confirm( $jn_h7_cancel );
$jn_order     = is_array( $jn_res ) ? wc_get_order( (int) $jn_res['order_id'] ) : null;
if ( $jn_order instanceof WC_Order ) {
	$jn_order->update_status( 'cancelled', 'JN11 test' );
}
clean_post_cache( $jn_h7_cancel );
jn_t_assert( JN_Booking_CPT::STATUS_CANCELLED === get_post_status( $jn_h7_cancel ), 'Cancelling the WooCommerce order cancels the booking (slot released)' );
jn_t_assert( 1 === jn11c_cancel_events( $jn_h7_cancel ), 'Exactly one cancellation event fires (no duplicate cancellation email)' );

$jn_h7_loop = jn11c_booking( '60-phut', 'h7-loop' );
$jn_res     = jn11c_confirm( $jn_h7_loop );
JN_Booking_Service::transition_status( $jn_h7_loop, JN_Booking_CPT::STATUS_CANCELLED, array( 'actor_id' => $jn_assistant_id ) );
$jn_order = is_array( $jn_res ) ? wc_get_order( (int) $jn_res['order_id'] ) : null;
jn_t_assert( $jn_order instanceof WC_Order && 'cancelled' === $jn_order->get_status(), 'Cancelling the booking still cancels its unpaid order' );
jn_t_assert( 1 === jn11c_cancel_events( $jn_h7_loop ), 'Booking → order → booking does not loop into a second cancellation' );

$jn_h7_refund = jn11c_booking( '60-phut', 'h7-refund' );
$jn_res       = jn11c_confirm( $jn_h7_refund );
$jn_order     = is_array( $jn_res ) ? wc_get_order( (int) $jn_res['order_id'] ) : null;
if ( $jn_order instanceof WC_Order ) {
	$jn_order->update_status( 'processing', 'JN11 paid' );
	$jn_order->update_status( 'refunded', 'JN11 refunded' );
}
clean_post_cache( $jn_h7_refund );
jn_t_assert( JN_Booking_CPT::STATUS_CANCELLED === get_post_status( $jn_h7_refund ), 'A fully refunded order cancels the booking' );

$jn_h7_failed = jn11c_booking( '60-phut', 'h7-failed' );
$jn_res       = jn11c_confirm( $jn_h7_failed );
$jn_order     = is_array( $jn_res ) ? wc_get_order( (int) $jn_res['order_id'] ) : null;
if ( $jn_order instanceof WC_Order ) {
	$jn_order->update_status( 'failed', 'JN11 failed' );
}
clean_post_cache( $jn_h7_failed );
jn_t_assert( JN_Booking_CPT::STATUS_CONFIRMED === get_post_status( $jn_h7_failed ), 'A failed payment keeps the booking confirmed (customer can retry)' );
jn_t_assert( 'yes' === get_post_meta( $jn_h7_failed, '_jn_payment_failed', true ), 'A failed payment is flagged on the booking' );

// -----------------------------------------------------------------------------
// H6 / M10 — Mentorship enrolment vs. individual sessions
// -----------------------------------------------------------------------------
jn_t_section( 'H6 / M10 — Mentorship sessions' );
$jn_unconfirmed_root = jn11c_booking( 'mentorship-3-thang', 'mentor-unconfirmed' );
$jn_res              = JN_Booking_Service::create_mentorship_session( $jn_unconfirmed_root, array( 'actor_id' => $jn_assistant_id ) );
jn_t_assert( is_wp_error( $jn_res ) && 'enrolment_not_confirmed' === $jn_res->get_error_code(), 'A session cannot be added before the enrolment has its order (no second order per session)' );

$jn_root = jn11c_booking( 'mentorship-3-thang', 'mentor' );
$jn_res  = jn11c_confirm( $jn_root, array( 'agreed_price' => 25000000 ) );
$jn_root_order_id = is_array( $jn_res ) ? (int) $jn_res['order_id'] : 0;

$jn_sessions = array();
for ( $jn_i = 0; $jn_i < 3; $jn_i++ ) {
	$jn_sid = JN_Booking_Service::create_mentorship_session( $jn_root, array( 'actor_id' => $jn_assistant_id ) );
	if ( ! is_wp_error( $jn_sid ) ) {
		$jn_sessions[] = jn_t_track_post( (int) $jn_sid );
	}
}
JN_Booking_Service::transition_status( $jn_sessions[1], JN_Booking_CPT::STATUS_CANCELLED, array( 'actor_id' => $jn_assistant_id ) );
$jn_new_sid = JN_Booking_Service::create_mentorship_session( $jn_root, array( 'actor_id' => $jn_assistant_id ) );
if ( ! is_wp_error( $jn_new_sid ) ) {
	jn_t_track_post( (int) $jn_new_sid );
}
$jn_codes = array_map( static fn( $sid ) => (string) get_post_meta( $sid, '_jn_booking_code', true ), array_merge( $jn_sessions, is_wp_error( $jn_new_sid ) ? array() : array( (int) $jn_new_sid ) ) );
jn_t_assert( count( $jn_codes ) === count( array_unique( $jn_codes ) ), 'Session codes stay unique after a session is cancelled', implode( ',', $jn_codes ) );

add_option( 'jn_lock_mentorship_' . $jn_root, time(), '', 'no' );
$jn_res = JN_Booking_Service::create_mentorship_session( $jn_root, array( 'actor_id' => $jn_assistant_id ) );
delete_option( 'jn_lock_mentorship_' . $jn_root );
if ( ! is_wp_error( $jn_res ) ) {
	jn_t_track_post( (int) $jn_res );
}
jn_t_assert( is_wp_error( $jn_res ) && 'mentorship_busy' === $jn_res->get_error_code(), 'Concurrent session creation is serialized by a lock' );

JN_Booking_Service::transition_status( $jn_root, JN_Booking_CPT::STATUS_CANCELLED, array( 'actor_id' => $jn_assistant_id ) );
$jn_root_order = wc_get_order( $jn_root_order_id );
jn_t_assert( $jn_root_order instanceof WC_Order && 'pending' === $jn_root_order->get_status(), 'Cancelling session #1 keeps the enrolment order while other sessions are active' );

foreach ( array_merge( $jn_sessions, is_wp_error( $jn_new_sid ) ? array() : array( (int) $jn_new_sid ) ) as $jn_sid ) {
	if ( JN_Booking_CPT::STATUS_CANCELLED !== get_post_status( $jn_sid ) ) {
		JN_Booking_Service::transition_status( $jn_sid, JN_Booking_CPT::STATUS_CANCELLED, array( 'actor_id' => $jn_assistant_id ) );
	}
}
$jn_root_order = wc_get_order( $jn_root_order_id );
jn_t_assert( $jn_root_order instanceof WC_Order && 'cancelled' === $jn_root_order->get_status(), 'Cancelling the last active session applies the order policy to the enrolment order' );

// -----------------------------------------------------------------------------
// M11 — prices come from settings, never from silent hardcoded fallbacks
// -----------------------------------------------------------------------------
jn_t_section( 'M11 — confirmation price' );
$jn_fees = JN_Booking_Settings::get( 'default_fees', array() );
jn_t_assert( is_array( $jn_fees ) && isset( $jn_fees['30-phut'], $jn_fees['60-phut'], $jn_fees['mentorship-3-thang'] ), 'Default package fees live in the booking settings' );

$jn_settings_before = JN_Booking_Settings::get_all();
JN_Booking_Settings::update( array( 'default_fees' => array_merge( (array) $jn_fees, array( '30-phut' => 0 ) ) ) );
$jn_no_price = jn11c_booking( '30-phut', 'no-price' );
$jn_res      = jn11c_confirm( $jn_no_price, array( 'agreed_price' => 0 ) );
JN_Booking_Settings::update( $jn_settings_before );
jn_t_assert( is_wp_error( $jn_res ) && 'missing_price' === $jn_res->get_error_code(), 'Confirming without any known price is refused instead of inventing one' );

$jn_rest_booking = jn11c_booking( '60-phut', 'rest-price' );
$jn_request      = new WP_REST_Request( 'POST', '/jn/v1/bookings/' . $jn_rest_booking . '/transition' );
$jn_request->set_body_params(
	array(
		'to_status'       => JN_Booking_CPT::STATUS_CONFIRMED,
		'confirmed_start' => '2031-04-01 10:00:00',
		'format'          => 'online',
		'meeting_link'    => 'https://zoom.example.test/j/rest',
		'allow_overlap'   => 1,
	)
);
$jn_response = rest_do_request( $jn_request );
clean_post_cache( $jn_rest_booking );
jn_t_assert( 400 === $jn_response->get_status() && JN_Booking_CPT::STATUS_PENDING === get_post_status( $jn_rest_booking ), 'REST confirmation without agreed_price is rejected (400)', 'HTTP ' . $jn_response->get_status() );

// -----------------------------------------------------------------------------
// M3 — public form abuse limits
// -----------------------------------------------------------------------------
jn_t_section( 'M3 — public booking form limits' );
$jn_base = array(
	'full_name'       => 'Khách Công Khai',
	'phone_number'    => '0901111222',
	'email_address'   => jn_t_email( 'public' ),
	'package_code'    => '60-phut',
	'coaching_format' => 'online',
	'time_preference' => 'morning',
	'notes'           => 'Ghi chú hợp lệ.',
	'privacy_consent' => '1',
	'jn_website_hp'   => '',
	'_jn_form_token'  => JN_Booking_Service::issue_form_token( time() - 10 ),
);
$jn_res = JN_Booking_Service::submit_public_booking( array_merge( $jn_base, array( 'full_name' => str_repeat( 'A', 500 ) ) ), array( 'ip' => '10.11.0.1' ) );
jn_t_assert( is_wp_error( $jn_res ) && 'name_too_long' === $jn_res->get_error_code(), 'A 500-character name is rejected' );
$jn_res = JN_Booking_Service::submit_public_booking( array_merge( $jn_base, array( 'notes' => str_repeat( 'B', 3000 ) ) ), array( 'ip' => '10.11.0.2' ) );
jn_t_assert( is_wp_error( $jn_res ) && 'notes_too_long' === $jn_res->get_error_code(), '3000-character notes are rejected' );

jn_t_assert(
	JN_Booking_Service::get_rate_limit_key( 'ip', '2001:db8:abcd:12:1::1' ) === JN_Booking_Service::get_rate_limit_key( 'ip', '2001:db8:abcd:12:ffff::9' ),
	'IPv6 addresses in the same /64 share one rate-limit bucket'
);
jn_t_assert(
	JN_Booking_Service::get_rate_limit_key( 'ip', '2001:db8:abcd:12::1' ) !== JN_Booking_Service::get_rate_limit_key( 'ip', '2001:db8:abcd:13::1' ),
	'Different /64 networks get different buckets'
);
jn_t_assert(
	JN_Booking_Service::get_rate_limit_key( 'ip', '10.0.0.1' ) !== JN_Booking_Service::get_rate_limit_key( 'ip', '10.0.0.2' ),
	'IPv4 addresses are still bucketed individually'
);

jn_t_finish();
