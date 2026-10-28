<?php
/**
 * JN Step 5 — Automated Backend Test Suite for the Booking Engine (`jn-core`).
 *
 * Run via WP-CLI:
 *   ./scripts/wp.sh eval-file /scripts/test-step-5-booking.php
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Destructive local-only script: refuse to run against production (review 2026-10-28, H2).
require_once __DIR__ . '/lib/jn-env-guard.php';
jn_require_non_production( basename( __FILE__ ) );

$GLOBALS['jn_tests_total']  = 0;
$GLOBALS['jn_tests_passed'] = 0;
$GLOBALS['jn_tests_failed'] = array();

$created_post_ids  = array();
$created_order_ids = array();
$created_user_ids  = array();

/**
 * Assert helper.
 *
 * @param bool   $condition Truthy condition.
 * @param string $label     Test case description.
 * @param string $detail    Optional failure detail.
 * @return void
 */
function jn_assert( bool $condition, string $label, string $detail = '' ): void {
	++$GLOBALS['jn_tests_total'];
	if ( $condition ) {
		++$GLOBALS['jn_tests_passed'];
		echo sprintf( "[PASS #%02d] %s\n", $GLOBALS['jn_tests_total'], $label );
	} else {
		$msg                           = $label . ( '' !== $detail ? ' — ' . $detail : '' );
		$GLOBALS['jn_tests_failed'][] = $msg;
		echo sprintf( "[FAIL #%02d] %s\n", $GLOBALS['jn_tests_total'], $msg );
	}
}

echo "=================================================================\n";
echo "JN STEP 5 — AUTOMATED BACKEND TEST SUITE\n";
echo "=================================================================\n";

// Ensure packages and roles are initialized.
JN_Coaching_Packages::ensure_packages_exist();
JN_Booking_Roles::sync_roles_and_caps();
$original_settings = get_option( JN_Booking_Settings::OPTION_KEY, array() );

// Ensure we run admin actions as an administrator by default during the test suite.
$admin_users = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
$admin_id    = ! empty( $admin_users ) ? (int) $admin_users[0] : 1;
wp_set_current_user( $admin_id );

// Create a valid backdated token (issued 10 seconds ago so fill time >= 2s passes).
$valid_token = JN_Booking_Service::issue_form_token( time() - 10 );

// -----------------------------------------------------------------------------
// 1. Phone Normalization & Validation
// -----------------------------------------------------------------------------
jn_assert(
	'+84908123456' === JN_Customer_Service::normalize_vn_phone( '0908123456' ),
	'Phone normalization: 0908123456 -> +84908123456'
);
jn_assert(
	'+84912345678' === JN_Customer_Service::normalize_vn_phone( '+84 912 345 678' ),
	'Phone normalization: +84 912 345 678 -> +84912345678'
);
jn_assert(
	'+84987654321' === JN_Customer_Service::normalize_vn_phone( '84987654321' ),
	'Phone normalization: 84987654321 -> +84987654321'
);
jn_assert(
	'+84388111222' === JN_Customer_Service::normalize_vn_phone( '(+84) 388-111-222' ),
	'Phone normalization: (+84) 388-111-222 -> +84388111222'
);
jn_assert(
	'' === JN_Customer_Service::normalize_vn_phone( '012345' ),
	'Invalid phone rejected: 012345 (too short)'
);
jn_assert(
	'' === JN_Customer_Service::normalize_vn_phone( 'abc0908123456' ),
	'Invalid phone rejected: non-numeric characters'
);
jn_assert(
	'' === JN_Customer_Service::normalize_vn_phone( '+15551234567' ),
	'Invalid phone rejected: non-Vietnamese country prefix (+1)'
);

// -----------------------------------------------------------------------------
// 2. Public Submit Validation: Missing Name, Invalid Phone, Invalid Email, Invalid Package, Missing Consent, Honeypot, Too-Fast, Expired/Tampered Token
// -----------------------------------------------------------------------------
$base_payload = array(
	'full_name'       => 'Nguyễn Minh Anh',
	'phone_number'    => '0908111222',
	'email_address'   => 'minhanh.test@example.com',
	'package_code'    => '60-phut',
	'coaching_format' => 'online',
	'time_preference' => 'morning',
	'notes'           => 'Cần cố vấn chiến lược nâng cấp tay nghề và dịch vụ.',
	'privacy_consent' => '1',
	'jn_website_hp'   => '',
	'_jn_form_token'  => $valid_token,
);

$res_no_name = JN_Booking_Service::submit_public_booking(
	array_merge( $base_payload, array( 'full_name' => ' ' ) ),
	array( 'ip' => '10.0.0.1' )
);
jn_assert(
	is_wp_error( $res_no_name ) && 'missing_name' === $res_no_name->get_error_code(),
	'Submit validation: rejects missing customer name (missing_name)'
);

$res_bad_phone = JN_Booking_Service::submit_public_booking(
	array_merge( $base_payload, array( 'phone_number' => '12345' ) ),
	array( 'ip' => '10.0.0.1' )
);
jn_assert(
	is_wp_error( $res_bad_phone ) && 'invalid_phone' === $res_bad_phone->get_error_code(),
	'Submit validation: rejects invalid Vietnamese phone (invalid_phone)'
);

$res_bad_email = JN_Booking_Service::submit_public_booking(
	array_merge( $base_payload, array( 'email_address' => 'bad-email-format' ) ),
	array( 'ip' => '10.0.0.1' )
);
jn_assert(
	is_wp_error( $res_bad_email ) && 'invalid_email' === $res_bad_email->get_error_code(),
	'Submit validation: rejects invalid email address (invalid_email)'
);

$res_bad_pkg = JN_Booking_Service::submit_public_booking(
	array_merge( $base_payload, array( 'package_code' => 'non-existent-pkg' ) ),
	array( 'ip' => '10.0.0.1' )
);
jn_assert(
	is_wp_error( $res_bad_pkg ) && 'invalid_package' === $res_bad_pkg->get_error_code(),
	'Submit validation: rejects unbookable/unknown package (invalid_package)'
);

$res_no_consent = JN_Booking_Service::submit_public_booking(
	array_merge( $base_payload, array( 'privacy_consent' => '' ) ),
	array( 'ip' => '10.0.0.1' )
);
jn_assert(
	is_wp_error( $res_no_consent ) && 'missing_consent' === $res_no_consent->get_error_code(),
	'Submit validation: rejects unchecked privacy consent (missing_consent)'
);

$res_honeypot = JN_Booking_Service::submit_public_booking(
	array_merge( $base_payload, array( 'jn_website_hp' => 'http://spambot.example' ) ),
	array( 'ip' => '10.0.0.1' )
);
jn_assert(
	is_wp_error( $res_honeypot ) && 'honeypot_filled' === $res_honeypot->get_error_code(),
	'Anti-spam: rejects filled honeypot field (honeypot_filled)'
);

$fast_token   = JN_Booking_Service::issue_form_token( time() );
$res_too_fast = JN_Booking_Service::submit_public_booking(
	array_merge( $base_payload, array( '_jn_form_token' => $fast_token ) ),
	array( 'ip' => '10.0.0.1' )
);
jn_assert(
	is_wp_error( $res_too_fast ) && 'too_fast_submit' === $res_too_fast->get_error_code(),
	'Anti-spam: rejects too-fast form submission < 2s (too_fast_submit)'
);

$expired_token = JN_Booking_Service::issue_form_token( time() - 90000 );
$res_expired   = JN_Booking_Service::submit_public_booking(
	array_merge( $base_payload, array( '_jn_form_token' => $expired_token ) ),
	array( 'ip' => '10.0.0.1' )
);
jn_assert(
	is_wp_error( $res_expired ) && 'expired_token' === $res_expired->get_error_code(),
	'Token security: rejects expired signed token > 24h TTL (expired_token)'
);

$res_tampered = JN_Booking_Service::submit_public_booking(
	array_merge( $base_payload, array( '_jn_form_token' => $valid_token . 'tampered' ) ),
	array( 'ip' => '10.0.0.1' )
);
jn_assert(
	is_wp_error( $res_tampered ) && 'expired_token' === $res_tampered->get_error_code(),
	'Token security: rejects tampered HMAC signature (expired_token)'
);

// -----------------------------------------------------------------------------
// 3. Rate Limiting (per hashed IP and per hashed phone/email)
// -----------------------------------------------------------------------------
$rl_ip = '198.51.100.77';
JN_Booking_Service::reset_rate_limits( $rl_ip, '', '' );

$ip_pass_count = 0;
for ( $i = 1; $i <= 5; $i++ ) {
	$ph       = '+8490900010' . $i;
	$em       = 'ipuser' . $i . '@example.com';
	$rl_check = JN_Booking_Service::check_rate_limits( $rl_ip, $ph, $em, true );
	if ( true === $rl_check ) {
		++$ip_pass_count;
	}
	JN_Booking_Service::reset_rate_limits( '', $ph, $em );
}
$ip_6th = JN_Booking_Service::check_rate_limits( $rl_ip, '+84909000199', 'ipuser99@example.com', true );
jn_assert(
	5 === $ip_pass_count && is_wp_error( $ip_6th ) && 'rate_limited' === $ip_6th->get_error_code(),
	'Rate limit (IP): allows 5 requests/15m per hashed IP and blocks the 6th (rate_limited)'
);
JN_Booking_Service::reset_rate_limits( $rl_ip, '+84909000199', 'ipuser99@example.com' );

$contact_phone = '+84908777888';
$contact_email = 'ratelimit.contact@example.com';
JN_Booking_Service::reset_rate_limits( '198.51.100.81', $contact_phone, $contact_email );

$contact_pass_count = 0;
for ( $i = 1; $i <= 3; $i++ ) {
	$test_ip  = '198.51.100.' . ( 80 + $i );
	$rl_check = JN_Booking_Service::check_rate_limits( $test_ip, $contact_phone, $contact_email, true );
	if ( true === $rl_check ) {
		++$contact_pass_count;
	}
	JN_Booking_Service::reset_rate_limits( $test_ip, '', '' );
}
$contact_4th = JN_Booking_Service::check_rate_limits( '198.51.100.90', $contact_phone, $contact_email, true );
jn_assert(
	3 === $contact_pass_count && is_wp_error( $contact_4th ) && 'rate_limited' === $contact_4th->get_error_code(),
	'Rate limit (Phone/Email): allows 3 requests/15m per hashed phone/email and blocks the 4th (rate_limited)'
);
JN_Booking_Service::reset_rate_limits( '198.51.100.90', $contact_phone, $contact_email );

// -----------------------------------------------------------------------------
// 4. CPT Privacy, Sitemap Exclusion & Anonymous REST Protection
// -----------------------------------------------------------------------------
$cpt_obj = get_post_type_object( JN_Booking_CPT::POST_TYPE );
jn_assert(
	null !== $cpt_obj
	&& false === $cpt_obj->public
	&& false === $cpt_obj->publicly_queryable
	&& true === $cpt_obj->exclude_from_search
	&& false === $cpt_obj->show_in_rest
	&& true === $cpt_obj->show_ui,
	'CPT jn_booking registration: private, not publicly queryable, excluded from search, show_ui=true, show_in_rest=false'
);

$sitemap_types = apply_filters( 'wp_sitemaps_post_types', array( 'post' => 'post', 'jn_booking' => 'jn_booking' ) );
jn_assert(
	! isset( $sitemap_types['jn_booking'] ),
	'CPT jn_booking is excluded from XML sitemaps (wp_sitemaps_post_types)'
);

wp_set_current_user( 0 );
$anon_cpt_req = new WP_REST_Request( 'GET', '/wp/v2/jn_booking' );
$anon_cpt_res = rest_do_request( $anon_cpt_req );
jn_assert(
	404 === $anon_cpt_res->get_status(),
	'Anonymous REST /wp/v2/jn_booking returns 404 Not Found (CPT not exposed to REST)'
);
wp_set_current_user( $admin_id );

$wc_emails = WC()->mailer()->get_emails();
jn_assert(
	isset(
		$wc_emails['WC_Email_JN_Booking_New_Request'],
		$wc_emails['WC_Email_JN_Booking_Staff_Alert'],
		$wc_emails['WC_Email_JN_Booking_Confirmed'],
		$wc_emails['WC_Email_JN_Booking_Payment_Received'],
		$wc_emails['WC_Email_JN_Booking_Cancelled']
	),
	'WooCommerce Emails: all 5 custom JN Booking email classes are registered in WC_Emails'
);

// -----------------------------------------------------------------------------
// 5. Public Submit (Guest Snapshot when AUTO-CREATE = no) & Verbatim Success Text
// -----------------------------------------------------------------------------
JN_Booking_Settings::update( array_merge( $original_settings, array( 'auto_create_account' => 'no' ) ) );
JN_Booking_Service::reset_rate_limits( '10.10.0.1', '+84908111222', 'minhanh.test@example.com' );

$submit_res = JN_Booking_Service::submit_public_booking(
	array_merge(
		$base_payload,
		array(
			'utm_source'   => 'facebook',
			'utm_medium'   => 'cpc',
			'utm_campaign' => 'coaching_q4',
		)
	),
	array(
		'ip'          => '10.10.0.1',
		'landing_url' => home_url( '/coaching/?utm_source=facebook' ),
		'referrer'    => 'https://www.google.com/',
	)
);

jn_assert(
	! is_wp_error( $submit_res )
	&& ! empty( $submit_res['booking_id'] )
	&& JN_Booking_Settings::SUCCESS_TEXT === $submit_res['message'],
	'Public submit (AUTO-CREATE=no): creates booking in Chờ xác nhận and returns verbatim wireframe success text'
);

$booking_1_id       = (int) $submit_res['booking_id'];
$created_post_ids[] = $booking_1_id;

jn_assert(
	JN_Booking_CPT::STATUS_PENDING === get_post_status( $booking_1_id )
	&& 0 === (int) get_post_meta( $booking_1_id, '_jn_customer_user_id', true )
	&& '+84908111222' === get_post_meta( $booking_1_id, '_jn_customer_phone', true )
	&& 'Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)' === get_post_meta( $booking_1_id, '_jn_package_title', true )
	&& 'Gói Coaching Chuyên Nghiệp 1:1 (60 Phút) — Dành cho thợ & chủ Spa' === get_post_meta( $booking_1_id, '_jn_package_option_label', true )
	&& 'Buổi sáng (09:00 - 11:30)' === get_post_meta( $booking_1_id, '_jn_preferred_window_label', true )
	&& 'yes' === get_post_meta( $booking_1_id, '_jn_consent_given', true ),
	'Booking meta integrity: normalized +84 phone, verbatim package & window labels, consent metadata, guest snapshot (customer_user_id=0)'
);

// -----------------------------------------------------------------------------
// 6. Auto-Linking Guest Booking on Customer Registration + AUTO-CREATE = yes
// -----------------------------------------------------------------------------
$new_cust_id = wp_insert_user(
	array(
		'user_login' => 'minhanh_' . wp_generate_password( 4, false ),
		'user_email' => 'minhanh.test@example.com',
		'user_pass'  => wp_generate_password( 16, true ),
		'role'       => 'customer',
	)
);
if ( ! is_wp_error( $new_cust_id ) ) {
	$created_user_ids[] = (int) $new_cust_id;
}
jn_assert(
	! is_wp_error( $new_cust_id ) && 0 === (int) get_post_meta( $booking_1_id, '_jn_customer_user_id', true ),
	'Guest-to-customer linking: registering alone (unverified email) does NOT attach the prior guest booking'
);
// Completing the emailed set-password link proves email ownership (Step 11 / review H4).
if ( ! is_wp_error( $new_cust_id ) ) {
	$_POST['rp_key'] = 'jn-test-emailed-key'; // The reset is redeemed with the emailed key.
	do_action( 'after_password_reset', get_userdata( (int) $new_cust_id ), wp_generate_password( 16, true ) );
	unset( $_POST['rp_key'] );
}
jn_assert(
	! is_wp_error( $new_cust_id ) && (int) $new_cust_id === (int) get_post_meta( $booking_1_id, '_jn_customer_user_id', true ),
	'Guest-to-customer linking: verifying the email via the set-password link attaches the prior guest booking'
);

// Test AUTO-CREATE = yes.
JN_Booking_Settings::update( array_merge( $original_settings, array( 'auto_create_account' => 'yes' ) ) );
$auto_email = 'autocreate.' . strtolower( wp_generate_password( 5, false ) ) . '@example.com';
JN_Booking_Service::reset_rate_limits( '10.10.0.2', '+84908333444', $auto_email );
$auto_res = JN_Booking_Service::submit_public_booking(
	array_merge(
		$base_payload,
		array(
			'full_name'     => 'Lê Hoàng Lan',
			'phone_number'  => '0908333444',
			'email_address' => $auto_email,
		)
	),
	array( 'ip' => '10.10.0.2' )
);
if ( ! is_wp_error( $auto_res ) ) {
	$created_post_ids[] = (int) $auto_res['booking_id'];
	$auto_cust_id       = (int) get_post_meta( (int) $auto_res['booking_id'], '_jn_customer_user_id', true );
	if ( $auto_cust_id > 0 ) {
		$created_user_ids[] = $auto_cust_id;
	}
}
jn_assert(
	! is_wp_error( $auto_res ) && (int) get_post_meta( (int) $auto_res['booking_id'], '_jn_customer_user_id', true ) > 0,
	'Customer upsert (AUTO-CREATE=yes): automatically creates WC customer user and links booking'
);
JN_Booking_Settings::update( $original_settings );

// -----------------------------------------------------------------------------
// 7. State Machine Transitions (Allowed vs Blocked) & Order Creation + Linking
// -----------------------------------------------------------------------------
$blocked_skip = JN_Booking_Service::transition_status( $booking_1_id, JN_Booking_CPT::STATUS_COMPLETED );
jn_assert(
	is_wp_error( $blocked_skip ) && 'invalid_status_transition' === $blocked_skip->get_error_code(),
	'State machine: blocks invalid jump Chờ xác nhận -> Hoàn thành (invalid_status_transition)'
);

$blocked_noshow = JN_Booking_Service::transition_status( $booking_1_id, JN_Booking_CPT::STATUS_NO_SHOW );
jn_assert(
	is_wp_error( $blocked_noshow ) && 'invalid_status_transition' === $blocked_noshow->get_error_code(),
	'State machine: blocks invalid jump Chờ xác nhận -> Vắng mặt (invalid_status_transition)'
);

$blocked_no_date = JN_Booking_Service::transition_status( $booking_1_id, JN_Booking_CPT::STATUS_CONFIRMED, array() );
jn_assert(
	is_wp_error( $blocked_no_date ) && 'missing_confirmed_start' === $blocked_no_date->get_error_code(),
	'State machine: blocks confirmation without confirmed_start date/time (missing_confirmed_start)'
);

$confirm_res = JN_Booking_Service::transition_status(
	$booking_1_id,
	JN_Booking_CPT::STATUS_CONFIRMED,
	array(
		'confirmed_start' => '2026-11-15T10:00',
		'format'          => 'online',
		'meeting_link'    => 'https://zoom.us/j/9988776655',
		'agreed_price'    => 2500000,
		'note'            => 'Đã thống nhất lịch qua Zalo.',
	)
);

$linked_order_1_id = (int) get_post_meta( $booking_1_id, '_jn_order_id', true );
if ( $linked_order_1_id > 0 ) {
	$created_order_ids[] = $linked_order_1_id;
}
$linked_order_1 = wc_get_order( $linked_order_1_id );
$payment_state  = JN_Booking_Service::get_derived_payment_state( $booking_1_id );

jn_assert(
	! is_wp_error( $confirm_res )
	&& JN_Booking_CPT::STATUS_CONFIRMED === get_post_status( $booking_1_id )
	&& $linked_order_1 instanceof WC_Order
	&& 'pending' === $linked_order_1->get_status()
	&& $booking_1_id === (int) $linked_order_1->get_meta( '_jn_booking_id', true )
	&& true === $payment_state['is_payable']
	&& '' !== $payment_state['pay_url'],
	'Staff confirmation: transitions to Đã xác nhận lịch, creates pending HPOS WC_Order linked bidirectionally, and exposes order-pay link'
);

// Verify .ics file generation.
$ics_path = JN_Booking_ICS::write_attachment_file( $booking_1_id );
$ics_body = ( '' !== $ics_path && file_exists( $ics_path ) ) ? (string) file_get_contents( $ics_path ) : '';
jn_assert(
	str_contains( $ics_body, 'BEGIN:VCALENDAR' )
	&& str_contains( $ics_body, 'TZID=Asia/Ho_Chi_Minh:20261115T100000' )
	&& str_contains( $ics_body, 'https://zoom.us/j/9988776655' ),
	'Calendar invite (.ics): generates RFC 5545 VCALENDAR with TZID=Asia/Ho_Chi_Minh and meeting link'
);

// -----------------------------------------------------------------------------
// 8. Schedule Overlap Warning Detection
// -----------------------------------------------------------------------------
$ov_tz       = new DateTimeZone( 'Asia/Ho_Chi_Minh' );
$ov_start_dt = new DateTimeImmutable( '2026-11-15 10:30:00', $ov_tz );
$ov_start_ts = $ov_start_dt->getTimestamp();
$ov_end_ts   = $ov_start_ts + 3600;
$overlaps    = JN_Booking_Service::find_overlapping_bookings( $ov_start_ts, $ov_end_ts, 0 );
jn_assert(
	count( $overlaps ) >= 1 && $booking_1_id === $overlaps[0]['booking_id'],
	'Overlap detector: detects overlapping confirmed appointment in Asia/Ho_Chi_Minh timezone'
);

// -----------------------------------------------------------------------------
// 9. Order Paid Sync & Final Status Transitions
// -----------------------------------------------------------------------------
$linked_order_1->payment_complete();
$payment_state_after = JN_Booking_Service::get_derived_payment_state( $booking_1_id );
jn_assert(
	'paid' === $payment_state_after['state']
	&& 'Đã thanh toán' === $payment_state_after['label']
	&& 'yes' === (string) get_post_meta( $booking_1_id, '_jn_payment_notified', true ),
	'Order paid sync: marking WC_Order paid updates booking payment state to Đã thanh toán and sets _jn_payment_notified=yes'
);

$complete_res = JN_Booking_Service::transition_status( $booking_1_id, JN_Booking_CPT::STATUS_COMPLETED );
$cancel_after = JN_Booking_Service::transition_status( $booking_1_id, JN_Booking_CPT::STATUS_CANCELLED );
jn_assert(
	! is_wp_error( $complete_res )
	&& JN_Booking_CPT::STATUS_COMPLETED === get_post_status( $booking_1_id )
	&& is_wp_error( $cancel_after )
	&& 'invalid_status_transition' === $cancel_after->get_error_code(),
	'State machine: allows Đã xác nhận lịch -> Hoàn thành and blocks any transition out of final status Hoàn thành'
);

// Test linking an existing WC_Order and transitioning Đã xác nhận lịch -> Vắng mặt (jn_no_show).
JN_Booking_Service::reset_rate_limits( '10.10.0.25', '+84908444555', 'noshow.link@example.com' );
$noshow_sub = JN_Booking_Service::submit_public_booking(
	array_merge(
		$base_payload,
		array(
			'full_name'     => 'Đặng Quốc Khánh',
			'phone_number'  => '0908444555',
			'email_address' => 'noshow.link@example.com',
		)
	),
	array( 'ip' => '10.10.0.25' )
);
$booking_ns_id      = (int) $noshow_sub['booking_id'];
$created_post_ids[] = $booking_ns_id;

$pre_order = wc_create_order( array( 'status' => 'pending' ) );
$pre_order->set_currency( 'VND' );
$pre_order->set_total( 2500000 );
$pre_order->save();
$pre_order_id        = (int) $pre_order->get_id();
$created_order_ids[] = $pre_order_id;

JN_Booking_Service::transition_status(
	$booking_ns_id,
	JN_Booking_CPT::STATUS_CONFIRMED,
	array(
		'confirmed_start'   => '2026-11-18T15:00',
		'format'            => 'online',
		'meeting_link'      => 'https://zoom.us/j/5566778899',
		'existing_order_id' => $pre_order_id,
	)
);
jn_assert(
	$pre_order_id === (int) get_post_meta( $booking_ns_id, '_jn_order_id', true ),
	'Order linking: confirming with existing_order_id links the specified WC_Order without creating a duplicate'
);

$ns_res       = JN_Booking_Service::transition_status( $booking_ns_id, JN_Booking_CPT::STATUS_NO_SHOW );
$ns_after_err = JN_Booking_Service::transition_status( $booking_ns_id, JN_Booking_CPT::STATUS_CANCELLED );
jn_assert(
	! is_wp_error( $ns_res )
	&& JN_Booking_CPT::STATUS_NO_SHOW === get_post_status( $booking_ns_id )
	&& is_wp_error( $ns_after_err )
	&& 'invalid_status_transition' === $ns_after_err->get_error_code(),
	'State machine: allows Đã xác nhận lịch -> Vắng mặt and blocks transitions out of final status Vắng mặt'
);

// -----------------------------------------------------------------------------
// 10. Cancellation Policy: Unpaid Order Cancelled vs Paid Order Flagged for Manual Refund
// -----------------------------------------------------------------------------
$booking_2_id = (int) $auto_res['booking_id'];
JN_Booking_Service::transition_status(
	$booking_2_id,
	JN_Booking_CPT::STATUS_CONFIRMED,
	array(
		'confirmed_start' => '2026-11-16T14:00',
		'format'          => 'in-person',
		'location'        => 'Jannie Nguyen Academy, TP.HCM',
		'agreed_price'    => 1500000,
	)
);
$order_2_id = (int) get_post_meta( $booking_2_id, '_jn_order_id', true );
if ( $order_2_id > 0 ) {
	$created_order_ids[] = $order_2_id;
}
JN_Booking_Service::transition_status( $booking_2_id, JN_Booking_CPT::STATUS_CANCELLED, array( 'note' => 'Khách xin hủy lịch chưa thanh toán.' ) );
$order_2_reloaded = wc_get_order( $order_2_id );
jn_assert(
	$order_2_reloaded instanceof WC_Order && 'cancelled' === $order_2_reloaded->get_status(),
	'Cancellation policy (unpaid): cancelling booking automatically cancels its unpaid WC_Order'
);

// Create a booking with a paid order and cancel the booking -> must flag for manual refund, never auto-refund.
JN_Booking_Service::reset_rate_limits( '10.10.0.3', '+84908555666', 'paidcancel@example.com' );
$paid_sub = JN_Booking_Service::submit_public_booking(
	array_merge(
		$base_payload,
		array(
			'full_name'     => 'Phạm Thu Hà',
			'phone_number'  => '0908555666',
			'email_address' => 'paidcancel@example.com',
		)
	),
	array( 'ip' => '10.10.0.3' )
);
$booking_3_id       = (int) $paid_sub['booking_id'];
$created_post_ids[] = $booking_3_id;
JN_Booking_Service::transition_status(
	$booking_3_id,
	JN_Booking_CPT::STATUS_CONFIRMED,
	array(
		'confirmed_start' => '2026-11-17T09:30',
		'format'          => 'online',
		'meeting_link'    => 'https://zoom.us/j/1122334455',
		'agreed_price'    => 2500000,
	)
);
$order_3_id          = (int) get_post_meta( $booking_3_id, '_jn_order_id', true );
$created_order_ids[] = $order_3_id;
$order_3             = wc_get_order( $order_3_id );
$order_3->payment_complete();

JN_Booking_Service::transition_status( $booking_3_id, JN_Booking_CPT::STATUS_CANCELLED, array( 'note' => 'Khách hủy sau khi đã chuyển khoản.' ) );
$order_3_after   = wc_get_order( $order_3_id );
$payment_3_state = JN_Booking_Service::get_derived_payment_state( $booking_3_id );
jn_assert(
	'yes' === get_post_meta( $booking_3_id, '_jn_refund_flagged', true )
	&& 'yes' === $order_3_after->get_meta( '_jn_refund_flagged', true )
	&& 'refunded' !== $order_3_after->get_status()
	&& 'cancelled' !== $order_3_after->get_status()
	&& 'paid_refund_flagged' === $payment_3_state['state'],
	'Cancellation policy (paid): cancelling a paid booking flags _jn_refund_flagged=yes for manual refund and never auto-refunds'
);

// -----------------------------------------------------------------------------
// 11. Flow 5: Mentorship — 1 Enrolment Order + Up to 12 Linked Session Bookings
// -----------------------------------------------------------------------------
JN_Booking_Service::reset_rate_limits( '10.10.0.4', '+84908999000', 'mentorship@example.com' );
$mentor_sub = JN_Booking_Service::submit_public_booking(
	array_merge(
		$base_payload,
		array(
			'full_name'     => 'Trần Bảo Ngọc (Mentorship)',
			'phone_number'  => '0908999000',
			'email_address' => 'mentorship@example.com',
			'package_code'  => 'mentorship-3-thang',
		)
	),
	array( 'ip' => '10.10.0.4' )
);
$mentor_parent_id   = (int) $mentor_sub['booking_id'];
$created_post_ids[] = $mentor_parent_id;

JN_Booking_Service::transition_status(
	$mentor_parent_id,
	JN_Booking_CPT::STATUS_CONFIRMED,
	array(
		'confirmed_start' => '2026-11-20T10:00',
		'format'          => 'online',
		'meeting_link'    => 'https://zoom.us/j/mentorship01',
		'agreed_price'    => 25000000,
	)
);
$mentor_order_id     = (int) get_post_meta( $mentor_parent_id, '_jn_order_id', true );
$created_order_ids[] = $mentor_order_id;

// Parent is session #1; create sessions #2..#12 (11 child sessions).
$child_sessions_ok = true;
for ( $s = 2; $s <= 12; $s++ ) {
	$child_id = JN_Booking_Service::create_mentorship_session( $mentor_parent_id, array() );
	if ( is_wp_error( $child_id ) ) {
		$child_sessions_ok = false;
		break;
	}
	$created_post_ids[] = (int) $child_id;
}
$all_sessions = JN_Booking_Service::get_mentorship_sessions( $mentor_parent_id );
$last_sess_id = ! empty( $all_sessions ) ? (int) end( $all_sessions ) : 0;
$session_13   = JN_Booking_Service::create_mentorship_session( $mentor_parent_id, array() );

jn_assert(
	$child_sessions_ok
	&& 12 === count( $all_sessions )
	&& $mentor_order_id === (int) get_post_meta( $last_sess_id, '_jn_order_id', true )
	&& is_wp_error( $session_13 )
	&& 'mentorship_session_limit_reached' === $session_13->get_error_code(),
	'Mentorship (Flow 5): links 1 enrolment order to 12 session bookings and blocks the 13th (mentorship_session_limit_reached)'
);

// -----------------------------------------------------------------------------
// 12. Flow 6: Monthly Capacity & Waitlist Flagging
// -----------------------------------------------------------------------------
JN_Booking_Settings::update(
	array_merge(
		$original_settings,
		array(
			'capacity_enabled' => 'yes',
			'capacity_limits'  => array(
				'30-phut'            => 1,
				'60-phut'            => 0,
				'mentorship-3-thang' => 0,
			),
		)
	)
);
JN_Booking_Service::reset_rate_limits( '10.10.0.5', '+84908222111', 'cap1@example.com' );
$cap1_sub = JN_Booking_Service::submit_public_booking(
	array_merge(
		$base_payload,
		array(
			'full_name'     => 'Khách Capacity 1',
			'phone_number'  => '0908222111',
			'email_address' => 'cap1@example.com',
			'package_code'  => '30-phut',
		)
	),
	array( 'ip' => '10.10.0.5' )
);
$cap1_id            = (int) $cap1_sub['booking_id'];
$created_post_ids[] = $cap1_id;

$is_full_now = JN_Booking_Service::is_package_at_capacity( '30-phut' );
JN_Booking_Service::reset_rate_limits( '10.10.0.6', '+84908222333', 'cap2@example.com' );
$cap2_sub = JN_Booking_Service::submit_public_booking(
	array_merge(
		$base_payload,
		array(
			'full_name'     => 'Khách Waitlist 2',
			'phone_number'  => '0908222333',
			'email_address' => 'cap2@example.com',
			'package_code'  => '30-phut',
		)
	),
	array( 'ip' => '10.10.0.6' )
);
$cap2_id            = (int) $cap2_sub['booking_id'];
$created_post_ids[] = $cap2_id;

jn_assert(
	true === $is_full_now
	&& true === $cap2_sub['is_waitlist']
	&& 'yes' === get_post_meta( $cap2_id, '_jn_is_waitlist', true ),
	'Monthly capacity (Flow 6): detects full monthly package capacity and flags subsequent submission as Waitlist'
);
JN_Booking_Settings::update( $original_settings );

// -----------------------------------------------------------------------------
// 13. Capability Checks & Role "Trợ lý lịch hẹn" (`jn_booking_assistant`)
// -----------------------------------------------------------------------------
$assistant_role = get_role( JN_Booking_Roles::ROLE_ASSISTANT );
jn_assert(
	null !== $assistant_role
	&& $assistant_role->has_cap( 'manage_jn_bookings' )
	&& $assistant_role->has_cap( 'edit_jn_bookings' )
	&& $assistant_role->has_cap( 'read_shop_order' )
	&& $assistant_role->has_cap( 'list_users' ),
	'Role Trợ lý lịch hẹn (jn_booking_assistant): registered with booking, customer, and order capabilities'
);

$assistant_user_id = wp_insert_user(
	array(
		'user_login' => 'troly_' . wp_generate_password( 4, false ),
		'user_email' => 'troly.' . strtolower( wp_generate_password( 4, false ) ) . '@example.com',
		'user_pass'  => wp_generate_password( 16, true ),
		'role'       => JN_Booking_Roles::ROLE_ASSISTANT,
	)
);
$created_user_ids[] = (int) $assistant_user_id;

// Customer cannot run staff REST actions.
wp_set_current_user( (int) $new_cust_id );
$cust_rest_req = new WP_REST_Request( 'POST', '/jn/v1/bookings/' . $cap2_id . '/transition' );
$cust_rest_req->set_param( 'id', $cap2_id );
$cust_rest_req->set_param( 'to_status', JN_Booking_CPT::STATUS_CANCELLED );
$cust_rest_res = rest_do_request( $cust_rest_req );

jn_assert(
	403 === $cust_rest_res->get_status() && ! current_user_can( 'manage_jn_bookings' ),
	'Capability enforcement: customer role is blocked (403 Forbidden) from staff booking transition REST endpoint'
);

// Assistant CAN run staff REST actions and edit customers, but CANNOT edit administrator.
wp_set_current_user( (int) $assistant_user_id );
$asst_rest_req = new WP_REST_Request( 'POST', '/jn/v1/bookings/' . $cap2_id . '/transition' );
$asst_rest_req->set_param( 'id', $cap2_id );
$asst_rest_req->set_param( 'to_status', JN_Booking_CPT::STATUS_CANCELLED );
$asst_rest_res = rest_do_request( $asst_rest_req );

jn_assert(
	200 === $asst_rest_res->get_status()
	&& user_can( (int) $assistant_user_id, 'edit_user', (int) $new_cust_id )
	&& ! user_can( (int) $assistant_user_id, 'edit_user', $admin_id )
	&& ! user_can( (int) $assistant_user_id, 'delete_user', $admin_id ),
	'Capability enforcement: Trợ lý lịch hẹn can transition bookings & edit customers, but is blocked from editing administrators'
);

wp_set_current_user( $admin_id );

// -----------------------------------------------------------------------------
// 14. WordPress Personal Data Exporter & Eraser (`JN_Booking_Privacy`)
// -----------------------------------------------------------------------------
$export_res = JN_Booking_Privacy::export_personal_data( 'minhanh.test@example.com', 1 );
jn_assert(
	true === $export_res['done']
	&& count( $export_res['data'] ) >= 1
	&& 'jn_bookings' === $export_res['data'][0]['group_id'],
	'Privacy Exporter: exports customer booking records for WordPress Personal Data Export'
);

$erase_res = JN_Booking_Privacy::erase_personal_data( 'minhanh.test@example.com', 1 );
jn_assert(
	true === $erase_res['done']
	&& true === $erase_res['items_removed']
	&& 'Khách hàng đã ẩn danh' === get_post_meta( $booking_1_id, '_jn_customer_name', true )
	&& '' === get_post_meta( $booking_1_id, '_jn_customer_phone', true )
	&& '[Đã xóa theo yêu cầu quyền riêng tư]' === get_post_meta( $booking_1_id, '_jn_goals', true ),
	'Privacy Eraser: anonymizes personal data on bookings while preserving audit record'
);

// -----------------------------------------------------------------------------
// Cleanup Temporary Test Entities
// -----------------------------------------------------------------------------
foreach ( array_unique( $created_post_ids ) as $pid ) {
	$ics_file = JN_Booking_ICS::write_attachment_file( (int) $pid );
	if ( '' !== $ics_file && file_exists( $ics_file ) ) {
		wp_delete_file( $ics_file );
	}
	wp_delete_post( (int) $pid, true );
}

foreach ( array_unique( $created_order_ids ) as $oid ) {
	$ord = wc_get_order( (int) $oid );
	if ( $ord ) {
		$ord->delete( true );
	}
}

require_once ABSPATH . 'wp-admin/includes/user.php';
foreach ( array_unique( $created_user_ids ) as $uid ) {
	wp_delete_user( (int) $uid );
}

JN_Booking_Settings::update( $original_settings );

echo "=================================================================\n";
echo sprintf( "RESULT: %d / %d TESTS PASSED\n", $GLOBALS['jn_tests_passed'], $GLOBALS['jn_tests_total'] );
echo "=================================================================\n";

if ( ! empty( $GLOBALS['jn_tests_failed'] ) ) {
	foreach ( $GLOBALS['jn_tests_failed'] as $fail ) {
		echo 'FAILED: ' . $fail . "\n";
	}
	exit( 1 );
}
