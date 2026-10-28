<?php
/**
 * JN Step 11a — Code-review fixes: access control & personal data (C1, H4, H5, M2, M4, M12, M13).
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/test-step-11a-access-privacy.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/jn-test-kit.php';
jn_require_non_production( 'test-step-11a-access-privacy.php' );

echo "=================================================================\n";
echo "JN STEP 11a — ACCESS CONTROL & PERSONAL DATA\n";
echo "=================================================================\n";

$jn_admin_id     = jn_t_create_user( 'administrator', jn_t_email( 'admin' ) );
$jn_assistant_id = jn_t_create_user( JN_Booking_Roles::ROLE_ASSISTANT, jn_t_email( 'assistant' ) );
$jn_customer_id  = jn_t_create_user( 'customer', jn_t_email( 'customer' ), array( '_jn_phone_e164' => '+84901111111' ) );

// -----------------------------------------------------------------------------
// C1 — CRM profile editing can never target staff/admin accounts
// -----------------------------------------------------------------------------
jn_t_section( 'C1 — staff cannot edit administrator accounts through the CRM' );
wp_set_current_user( $jn_assistant_id );

$jn_admin_email_before = get_userdata( $jn_admin_id )->user_email;
$jn_res                = JN_Customer_Profile::update_user_profile( $jn_admin_id, array( 'email' => jn_t_email( 'evil' ) ) );
clean_user_cache( $jn_admin_id );
jn_t_assert( is_wp_error( $jn_res ), 'update_user_profile() refuses an administrator target' );
jn_t_assert( get_userdata( $jn_admin_id )->user_email === $jn_admin_email_before, 'Administrator email is unchanged' );

$jn_res = JN_Customer_Profile::update_user_profile( $jn_customer_id, array( 'full_name' => 'Khách Kiểm Thử Mười Một' ) );
jn_t_assert( true === $jn_res, 'update_user_profile() still works for a customer target', is_wp_error( $jn_res ) ? $jn_res->get_error_message() : '' );

jn_t_assert( ! current_user_can( 'edit_user', $jn_admin_id ), 'map_meta_cap denies edit_user on an administrator to the assistant role' );

$jn_note = JN_Customer_Profile::add_internal_note( 'user:' . $jn_admin_id, 'Ghi chú không hợp lệ', $jn_assistant_id );
jn_t_assert( is_wp_error( $jn_note ), 'add_internal_note() refuses a staff/admin customer key' );

jn_t_assert( null === JN_Customer_Service::get_customer_detail( 'user:' . $jn_admin_id ), 'get_customer_detail() never force-includes a staff/admin account' );

$jn_staff_booking = JN_Customer_Service::create_staff_booking(
	array(
		'user_id'      => $jn_admin_id,
		'full_name'    => 'Khách Gọi Điện',
		'phone'        => '0905555555',
		'email'        => jn_t_email( 'walkin' ),
		'package_code' => '60-phut',
	),
	$jn_assistant_id
);
if ( is_array( $jn_staff_booking ) ) {
	jn_t_track_post( (int) $jn_staff_booking['booking_id'] );
}
jn_t_assert( is_wp_error( $jn_staff_booking ) && 'invalid_customer' === $jn_staff_booking->get_error_code(), 'create_staff_booking() rejects a staff/admin user_id' );

update_user_meta( $jn_admin_id, 'first_name', 'Quản Trị' );
JN_Customer_Service::sync_user_contact_meta( $jn_admin_id, 'Tên Bị Ghi Đè', jn_t_email( 'admin' ), '+84909999999' );
update_user_meta( $jn_admin_id, '_jn_full_name', 'Tên Bị Ghi Đè' );
JN_Customer_Profile::sync_user_to_wc_customer( $jn_admin_id );
clean_user_cache( $jn_admin_id );
jn_t_assert( '' === get_user_meta( $jn_admin_id, '_jn_phone_e164', true ), 'sync_user_contact_meta() never writes to a staff/admin account (e.g. order assigned to an admin)' );
jn_t_assert( 'Quản Trị' === get_user_meta( $jn_admin_id, 'first_name', true ), 'sync_user_to_wc_customer() never rewrites a staff/admin name' );
delete_user_meta( $jn_admin_id, '_jn_full_name' );

// -----------------------------------------------------------------------------
// M12 — changing a customer's email to one owned by another account is an error
// -----------------------------------------------------------------------------
jn_t_section( 'M12 — email already in use' );
$jn_other_email   = jn_t_email( 'other-owner' );
$jn_other_id      = jn_t_create_user( 'customer', $jn_other_email );
$jn_cust_booking  = jn_t_create_booking(
	array(
		'_jn_customer_user_id' => $jn_customer_id,
		'_jn_customer_email'   => jn_t_email( 'customer' ),
		'_jn_customer_phone'   => '+84901111111',
		'_jn_customer_name'    => 'Khách Kiểm Thử',
	)
);
$jn_res           = JN_Customer_Profile::update_user_profile( $jn_customer_id, array( 'email' => $jn_other_email ) );
jn_t_assert( is_wp_error( $jn_res ) && 'email_in_use' === $jn_res->get_error_code(), 'update_user_profile() returns email_in_use for another account\'s email' );
jn_t_assert( jn_t_email( 'customer' ) === get_post_meta( $jn_cust_booking, '_jn_customer_email', true ), 'Customer bookings keep their own email' );

// -----------------------------------------------------------------------------
// M2 — anonymous booking submissions never mutate an existing account
// -----------------------------------------------------------------------------
jn_t_section( 'M2 — anonymous submission cannot overwrite account data' );
wp_set_current_user( 0 );
$jn_uid = JN_Customer_Service::upsert_customer_for_booking(
	array(
		'name'  => 'Kẻ Giả Mạo',
		'email' => jn_t_email( 'customer' ),
		'phone' => '0902222222',
	)
);
jn_t_assert( '+84901111111' === get_user_meta( $jn_customer_id, '_jn_phone_e164', true ), 'Existing _jn_phone_e164 is not overwritten by an anonymous submission' );
jn_t_assert( 0 === $jn_uid, 'Anonymous submission for an unverified account is stored as a guest booking', 'got user ' . $jn_uid );

$jn_uid = JN_Customer_Service::upsert_customer_for_booking(
	array(
		'name'  => 'Người Lạ',
		'email' => jn_t_email( 'stranger' ),
		'phone' => '0901111111',
	)
);
jn_t_assert( 0 === $jn_uid, 'A phone-only match never links an anonymous submission to an account' );

$jn_verified_email = jn_t_email( 'verified' );
$jn_verified_id    = jn_t_create_user( 'customer', $jn_verified_email, array( JN_Customer_Service::META_EMAIL_VERIFIED => $jn_verified_email ) );
$jn_uid            = JN_Customer_Service::upsert_customer_for_booking(
	array(
		'name'  => 'Kẻ Giả Mạo',
		'email' => $jn_verified_email,
		'phone' => '0906666666',
	)
);
jn_t_assert( $jn_verified_id === $jn_uid, 'An anonymous submission with a verified account email is attached to that account' );
$jn_anon_booking = jn_t_create_booking(
	array(
		'_jn_customer_user_id' => $jn_verified_id,
		'_jn_customer_email'   => $jn_verified_email,
		'_jn_customer_phone'   => '+84906666666',
		'_jn_customer_name'    => 'Kẻ Giả Mạo',
	)
);
do_action( 'jn_booking_created', $jn_anon_booking );
jn_t_assert( '' === get_user_meta( $jn_verified_id, '_jn_phone_e164', true ) && '' === get_user_meta( $jn_verified_id, '_jn_full_name', true ), 'An anonymous booking never fills the account\'s empty name/phone fields' );

// -----------------------------------------------------------------------------
// H4 — guest data links only after the account email is verified
// -----------------------------------------------------------------------------
jn_t_section( 'H4 — guest bookings/orders link only to a verified email' );
jn_t_assert( 'yes' === get_option( 'woocommerce_registration_generate_password' ), 'WooCommerce registration sends a set-password link (proves email ownership)' );
jn_t_assert( false === has_action( 'woocommerce_save_account_details', array( 'JN_Customer_Service', 'handle_user_registered' ) ), 'Changing the account email no longer triggers guest linking' );

$jn_victim_email   = jn_t_email( 'victim' );
$jn_victim_booking = jn_t_create_booking(
	array(
		'_jn_customer_email' => $jn_victim_email,
		'_jn_customer_phone' => '+84903333333',
		'_jn_customer_name'  => 'Nạn Nhân',
	)
);
$jn_victim_phone_only = jn_t_create_booking(
	array(
		'_jn_customer_email' => '',
		'_jn_customer_phone' => '+84903333333',
		'_jn_customer_name'  => 'Nạn Nhân',
	)
);
$jn_phone_guest_key = 'guest:+84903333333';
JN_Customer_Profile::add_internal_note( $jn_phone_guest_key, 'Ghi chú riêng của khách theo SĐT', $jn_assistant_id );
jn_t_on_cleanup( static fn() => JN_Customer_Profile::delete_guest_profile( 'guest:+84903333333' ) );
jn_t_on_cleanup( static fn() => JN_Customer_Profile::delete_guest_profile( 'guest:' . jn_t_email( 'victim' ) ) );

$jn_claimant_id = jn_t_create_user( 'customer', $jn_victim_email, array( 'billing_phone' => '0903333333' ) );
$jn_claimant    = get_userdata( $jn_claimant_id );
do_action( 'wp_login', $jn_claimant->user_login, $jn_claimant );
jn_t_assert( 0 === (int) get_post_meta( $jn_victim_booking, '_jn_customer_user_id', true ), 'Registration + login with an unverified email does not attach guest bookings' );
jn_t_assert( ! JN_Customer_Service::is_email_verified( $jn_claimant_id ), 'A freshly registered account is not email-verified' );

// A reset without an emailed key (e.g. Two-Factor's compromised-password reset) proves nothing.
do_action( 'after_password_reset', $jn_claimant, 'irrelevant' );
jn_t_assert( ! JN_Customer_Service::is_email_verified( $jn_claimant_id ), 'A password reset without an emailed reset key does not verify the email' );

$_POST['rp_key'] = 'jn11-emailed-key';
do_action( 'after_password_reset', $jn_claimant, 'irrelevant' );
unset( $_POST['rp_key'] );
jn_t_assert( JN_Customer_Service::is_email_verified( $jn_claimant_id ), 'Completing a password (re)set from the emailed link verifies the email' );
jn_t_assert( $jn_claimant_id === (int) get_post_meta( $jn_victim_booking, '_jn_customer_user_id', true ), 'Guest bookings with the verified email are attached after verification' );
jn_t_assert( 0 === (int) get_post_meta( $jn_victim_phone_only, '_jn_customer_user_id', true ), 'Bookings that only share an (unverified) phone are never attached' );
$jn_phone_profiles = JN_Customer_Profile::get_all_guest_profiles();
jn_t_assert( isset( $jn_phone_profiles[ $jn_phone_guest_key ] ), 'Phone-keyed guest CRM profiles are not merged into the account' );

wp_update_user(
	array(
		'ID'         => $jn_claimant_id,
		'user_email' => jn_t_email( 'claimant-new' ),
	)
);
clean_user_cache( $jn_claimant_id );
jn_t_assert( ! JN_Customer_Service::is_email_verified( $jn_claimant_id ), 'Changing the email invalidates the previous verification' );

// -----------------------------------------------------------------------------
// M4 — internal testimonial notes are not exposed through the REST API
// -----------------------------------------------------------------------------
jn_t_section( 'M4 — testimonial admin note hidden from REST' );
$jn_testimonial = jn_t_track_post(
	(int) wp_insert_post(
		array(
			'post_type'   => 'jn_testimonial',
			'post_status' => 'publish',
			'post_title'  => 'JN11 testimonial',
		)
	)
);
update_post_meta( $jn_testimonial, 'jn_admin_note', 'SECRET-NOTE-JN11' );
wp_set_current_user( 0 );
$jn_response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/jn_testimonial/' . $jn_testimonial ) );
$jn_payload  = wp_json_encode( rest_get_server()->response_to_data( $jn_response, false ) );
jn_t_assert( false === strpos( (string) $jn_payload, 'SECRET-NOTE-JN11' ), 'Anonymous REST read does not include jn_admin_note' );

// -----------------------------------------------------------------------------
// M13 — WC_Customer sync failures are logged, not swallowed
// -----------------------------------------------------------------------------
jn_t_section( 'M13 — WC_Customer sync failure is logged' );
$GLOBALS['jn_t_logged'] = array();
$jn_capture             = static function ( $message ) {
	$GLOBALS['jn_t_logged'][] = (string) $message;
	return $message;
};
$jn_thrower             = static function () {
	throw new \Exception( 'jn11-forced-sync-failure' );
};
add_filter( 'woocommerce_logger_log_message', $jn_capture );
add_action( 'woocommerce_before_customer_object_save', $jn_thrower );
JN_Customer_Profile::sync_user_to_wc_customer( $jn_customer_id );
remove_action( 'woocommerce_before_customer_object_save', $jn_thrower );
remove_filter( 'woocommerce_logger_log_message', $jn_capture );
$jn_logged_hit = array_filter( $GLOBALS['jn_t_logged'], static fn( $m ) => false !== strpos( $m, 'jn11-forced-sync-failure' ) );
jn_t_assert( ! empty( $jn_logged_hit ), 'sync_user_to_wc_customer() logs the exception through wc_get_logger()' );

// -----------------------------------------------------------------------------
// H5 — privacy exporter/eraser completeness
// -----------------------------------------------------------------------------
jn_t_section( 'H5 — privacy exporter includes internal notes' );
$jn_export_email = jn_t_email( 'export' );
$jn_export_user  = jn_t_create_user( 'customer', $jn_export_email );
JN_Customer_Profile::add_internal_note( 'user:' . $jn_export_user, 'Ghi chú cần xuất JN11', $jn_assistant_id );
$jn_export = JN_Booking_Privacy::export_customer_crm_data( $jn_export_email, 1 );
jn_t_assert( false !== strpos( (string) wp_json_encode( $jn_export['data'], JSON_UNESCAPED_UNICODE ), 'Ghi chú cần xuất JN11' ), 'CRM export contains staff internal notes about the person' );

jn_t_section( 'H5 — booking eraser removes every page and every PII field' );
$jn_erase_email = jn_t_email( 'erase' );
$jn_erase_ids   = array();
for ( $jn_i = 0; $jn_i < 60; $jn_i++ ) {
	$jn_erase_ids[] = jn_t_create_booking(
		array(
			'_jn_customer_email'  => $jn_erase_email,
			'_jn_customer_phone'  => '+84907777777',
			'_jn_customer_name'   => 'Người Cần Xóa',
			'_jn_staff_notes'     => 'Nhà ở 12 Lê Lợi',
			'_jn_location'        => '12 Lê Lợi, Quận 1',
			'_jn_referrer'        => 'https://example.test/?q=nguoi-can-xoa',
			'_jn_landing_url'     => 'https://example.test/booking/?ref=nguoi-can-xoa',
			'_jn_status_timeline' => array(
				array(
					'actor_id'   => $jn_assistant_id,
					'actor_name' => 'Trợ lý',
					'timestamp'  => '2026-10-28T10:00:00+07:00',
					'from'       => 'jn_pending',
					'to'         => 'jn_pending',
					'note'       => 'Đã gọi chị Mai số 0907777777',
				),
			),
		)
	);
}
$jn_page = 1;
do {
	$jn_result = JN_Booking_Privacy::erase_personal_data( $jn_erase_email, $jn_page );
	++$jn_page;
} while ( empty( $jn_result['done'] ) && $jn_page < 10 );

$jn_left = get_posts(
	array(
		'post_type'      => JN_Booking_CPT::POST_TYPE,
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => '_jn_customer_email', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value'     => $jn_erase_email, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	)
);
jn_t_assert( 0 === count( $jn_left ), 'Paginated eraser anonymizes all 60 bookings', count( $jn_left ) . ' left' );

$jn_leaks = 0;
foreach ( $jn_erase_ids as $jn_bid ) {
	$jn_blob = wp_json_encode(
		array(
			get_post_meta( $jn_bid, '_jn_staff_notes', true ),
			get_post_meta( $jn_bid, '_jn_location', true ),
			get_post_meta( $jn_bid, '_jn_referrer', true ),
			get_post_meta( $jn_bid, '_jn_landing_url', true ),
			get_post_meta( $jn_bid, '_jn_status_timeline', true ),
		),
		JSON_UNESCAPED_UNICODE
	);
	if ( false !== strpos( (string) $jn_blob, 'Lê Lợi' ) || false !== strpos( (string) $jn_blob, '0907777777' ) || false !== strpos( (string) $jn_blob, 'nguoi-can-xoa' ) ) {
		++$jn_leaks;
	}
}
jn_t_assert( 0 === $jn_leaks, 'Staff notes, location, referrer/landing URL and timeline notes are redacted', $jn_leaks . ' bookings leak' );

$jn_trashed = jn_t_create_booking(
	array(
		'_jn_customer_email' => $jn_erase_email,
		'_jn_customer_phone' => '+84907777777',
		'_jn_customer_name'  => 'Người Cần Xóa',
	),
	'trash'
);
JN_Booking_Privacy::erase_personal_data( $jn_erase_email, 1 );
jn_t_assert( $jn_erase_email !== get_post_meta( $jn_trashed, '_jn_customer_email', true ), 'Trashed bookings are anonymized too' );

$jn_sentinel = JN_Booking_Privacy::erase_personal_data( 'deleted@site.invalid', 1 );
jn_t_assert( ! empty( $jn_sentinel['done'] ) && empty( $jn_sentinel['items_removed'] ), 'Erasing the anonymization sentinel address terminates immediately (no endless eraser loop)' );

jn_t_section( 'H5 — CRM eraser clears WP/Woo name fields and phone-keyed duplicate log' );
$jn_crm_email = jn_t_email( 'crm-erase' );
$jn_crm_user  = jn_t_create_user(
	'customer',
	$jn_crm_email,
	array(
		'_jn_phone_e164'     => '+84908888888',
		'first_name'         => 'Riêng',
		'last_name'          => 'Tư',
		'billing_first_name' => 'Riêng',
		'billing_last_name'  => 'Tư',
		'billing_email'      => $jn_crm_email,
		'billing_country'    => 'VN',
	)
);
wp_update_user(
	array(
		'ID'           => $jn_crm_user,
		'display_name' => 'Nguyễn Văn Riêng Tư',
	)
);
$jn_dup_log   = get_option( JN_Customer_Service::DUPLICATE_LOG_OPTION, array() );
$jn_dup_log   = is_array( $jn_dup_log ) ? $jn_dup_log : array();
$jn_dup_log[] = array(
	'primary_customer_key'     => 'guest:+84908888888',
	'conflicting_customer_key' => 'guest:' . jn_t_email( 'someone-else' ),
	'context'                  => 'jn11_fixture',
);
update_option( JN_Customer_Service::DUPLICATE_LOG_OPTION, $jn_dup_log, false );

JN_Booking_Privacy::erase_customer_crm_data( $jn_crm_email, 1 );
clean_user_cache( $jn_crm_user );
$jn_name_left = array_filter(
	array(
		get_user_meta( $jn_crm_user, 'first_name', true ),
		get_user_meta( $jn_crm_user, 'last_name', true ),
		get_user_meta( $jn_crm_user, 'billing_first_name', true ),
		get_user_meta( $jn_crm_user, 'billing_last_name', true ),
		get_user_meta( $jn_crm_user, 'billing_email', true ),
	)
);
jn_t_assert( empty( $jn_name_left ), 'first/last name and billing name/email meta are erased' );
jn_t_assert( 'Nguyễn Văn Riêng Tư' !== get_userdata( $jn_crm_user )->display_name, 'display_name is anonymized' );
$jn_dup_after = wp_json_encode( get_option( JN_Customer_Service::DUPLICATE_LOG_OPTION, array() ) );
jn_t_assert( false === strpos( (string) $jn_dup_after, '+84908888888' ), 'Duplicate-log entries keyed by the person\'s phone are purged' );

jn_t_finish();
