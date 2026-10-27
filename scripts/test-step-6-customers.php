<?php
/**
 * JN Step 6 — Automated Backend Test Suite for Customer Management, CRM Sync,
 * Guest-to-Account Linking, Phone Search, CSV Export, Staff Roles/2FA & Privacy.
 *
 * Run via WP-CLI:
 *   ./scripts/wp.sh eval-file /scripts/test-step-6-customers.php
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$GLOBALS['jn_s6_tests_total']  = 0;
$GLOBALS['jn_s6_tests_passed'] = 0;
$GLOBALS['jn_s6_tests_failed'] = array();

$created_post_ids  = array();
$created_order_ids = array();
$created_user_ids  = array();
$created_guests    = array();

/**
 * Assert helper.
 *
 * @param bool   $condition Truthy condition.
 * @param string $label     Test case description.
 * @param string $detail    Optional failure detail.
 * @return void
 */
function jn_s6_assert( bool $condition, string $label, string $detail = '' ): void {
	++$GLOBALS['jn_s6_tests_total'];
	if ( $condition ) {
		++$GLOBALS['jn_s6_tests_passed'];
		echo sprintf( "[PASS #%02d] %s\n", $GLOBALS['jn_s6_tests_total'], $label );
	} else {
		$msg                              = $label . ( '' !== $detail ? ' — ' . $detail : '' );
		$GLOBALS['jn_s6_tests_failed'][] = $msg;
		echo sprintf( "[FAIL #%02d] %s\n", $GLOBALS['jn_s6_tests_total'], $msg );
	}
}

echo "=================================================================\n";
echo "JN STEP 6 — CUSTOMER MANAGEMENT & STAFF ACCOUNTS TEST SUITE\n";
echo "=================================================================\n";

// Ensure packages, roles, and placeholder staff accounts exist.
JN_Coaching_Packages::ensure_packages_exist();
JN_Booking_Roles::sync_roles_and_caps();
JN_Booking_Roles::ensure_staff_accounts();
JN_Booking_Settings::ensure_defaults();

$original_settings = get_option( JN_Booking_Settings::OPTION_KEY, array() );
$original_dup_log  = get_option( JN_Customer_Service::DUPLICATE_LOG_OPTION, array() );
$original_guests   = get_option( JN_Customer_Profile::GUEST_PROFILES_OPTION, array() );

$admin_user = get_user_by( 'login', 'jannie_admin' );
if ( ! $admin_user instanceof \WP_User ) {
	$admins     = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
	$admin_user = $admins[0];
}
$admin_id = (int) $admin_user->ID;
wp_set_current_user( $admin_id );

if ( ! function_exists( 'wp_delete_user' ) ) {
	require_once ABSPATH . 'wp-admin/includes/user.php';
}

// Pre-clean any leftover test users or bookings from interrupted prior runs.
foreach ( array( 's6.role.customer@example.com', 'lan.anh.spa@example.com', 'minh.thao.guest@example.com', 'other.email.same.phone@example.com', 'old.cancelled.guest@example.com' ) as $cleanup_email ) {
	$leftover_u = get_user_by( 'email', $cleanup_email );
	if ( $leftover_u instanceof \WP_User ) {
		wp_delete_user( (int) $leftover_u->ID );
	}
	$leftover_bookings = JN_Booking_Privacy::get_booking_ids_for_email( $cleanup_email, 1 );
	foreach ( $leftover_bookings as $lbid ) {
		wp_delete_post( (int) $lbid, true );
	}
	if ( function_exists( 'wc_get_orders' ) ) {
		$leftover_orders = wc_get_orders( array( 'billing_email' => $cleanup_email, 'limit' => 50 ) );
		if ( is_array( $leftover_orders ) ) {
			foreach ( $leftover_orders as $lord ) {
				if ( $lord instanceof \WC_Order ) {
					$lord->delete( true );
				}
			}
		}
	}
	JN_Customer_Profile::delete_guest_profile( 'guest:' . $cleanup_email );
}
// Also clean up any old placeholder staff accounts from earlier iterations (`jn_shop_manager`, `jn_booking_assistant`, `jn_content_editor`).
foreach ( array( 'jn_shop_manager', 'jn_booking_assistant', 'jn_content_editor' ) as $old_staff_login ) {
	$old_staff_u = get_user_by( 'login', $old_staff_login );
	if ( $old_staff_u instanceof \WP_User ) {
		wp_delete_user( (int) $old_staff_u->ID );
	}
}

// =============================================================================
// 1. Staff Accounts & Role / Capability Matrix (Each Role Can and Cannot)
// =============================================================================
$shop_mgr  = get_user_by( 'login', 'quan_ly_cua_hang' );
$assistant = get_user_by( 'login', 'tro_ly_lich_hen' );
$editor    = get_user_by( 'login', 'bien_tap_noi_dung' );

$cust_test_id = wp_insert_user(
	array(
		'user_login'   => 'jn_s6_role_customer_' . wp_rand( 1000, 9999 ),
		'user_pass'    => wp_generate_password( 24, true, true ),
		'user_email'   => 's6.role.customer@example.com',
		'display_name' => 'Khách Hàng Kiểm Thử Role',
		'role'         => 'customer',
	)
);
$created_user_ids[] = (int) $cust_test_id;

jn_s6_assert(
	$admin_user instanceof \WP_User
	&& $shop_mgr instanceof \WP_User
	&& $assistant instanceof \WP_User
	&& $editor instanceof \WP_User,
	'Staff accounts: All 4 canonical staff accounts exist (Administrator, Shop Manager, Booking Assistant, Editor)'
);

// 1.1 Administrator capabilities
jn_s6_assert(
	user_can( $admin_id, JN_Booking_Roles::CAP_CUSTOMERS )
	&& user_can( $admin_id, JN_Booking_Roles::CAP_MANAGE )
	&& user_can( $admin_id, JN_Booking_Roles::CAP_SETTINGS )
	&& user_can( $admin_id, JN_Booking_Roles::CAP_EXPORT )
	&& user_can( $admin_id, 'manage_options' )
	&& user_can( $admin_id, 'install_plugins' ),
	'Role Matrix [Quản trị / Administrator]: Full access to CRM, Bookings, Settings, CSV Export, and WP Core options'
);

// 1.2 Shop Manager capabilities (CAN manage WC, CRM, bookings, settings, CSV; CANNOT install plugins or edit Administrator)
$sm_id = (int) $shop_mgr->ID;
jn_s6_assert(
	user_can( $sm_id, JN_Booking_Roles::CAP_CUSTOMERS )
	&& user_can( $sm_id, JN_Booking_Roles::CAP_MANAGE )
	&& user_can( $sm_id, JN_Booking_Roles::CAP_SETTINGS )
	&& user_can( $sm_id, JN_Booking_Roles::CAP_EXPORT )
	&& user_can( $sm_id, 'manage_woocommerce' )
	&& user_can( $sm_id, 'delete_jn_bookings' ),
	'Role Matrix [Quản lý cửa hàng / Shop Manager]: CAN manage customers, bookings, settings, CSV export, and WooCommerce'
);
jn_s6_assert(
	! user_can( $sm_id, 'install_plugins' )
	&& ! user_can( $sm_id, 'manage_options' )
	&& ! user_can( $sm_id, 'edit_user', $admin_id )
	&& ! user_can( $sm_id, 'delete_user', $admin_id ),
	'Role Matrix [Quản lý cửa hàng / Shop Manager]: CANNOT install plugins, manage core options, or edit/delete Administrator'
);

// Verify `editable_roles` filter hides `administrator` from Shop Manager.
wp_set_current_user( $sm_id );
$sm_editable_roles = JN_Booking_Roles::restrict_editable_roles( wp_roles()->roles );
jn_s6_assert(
	! isset( $sm_editable_roles['administrator'] ) && isset( $sm_editable_roles['customer'] ),
	'Role Matrix [Quản lý cửa hàng / Shop Manager]: editable_roles strips administrator to prevent privilege escalation'
);
wp_set_current_user( $admin_id );

// 1.3 Booking Assistant (`jn_booking_assistant`) capabilities
$ast_id = (int) $assistant->ID;
jn_s6_assert(
	user_can( $ast_id, JN_Booking_Roles::CAP_CUSTOMERS )
	&& user_can( $ast_id, JN_Booking_Roles::CAP_MANAGE )
	&& user_can( $ast_id, JN_Booking_Roles::CAP_EXPORT )
	&& user_can( $ast_id, 'edit_jn_bookings' )
	&& user_can( $ast_id, 'read' ),
	'Role Matrix [Trợ lý lịch hẹn / Booking Assistant]: CAN manage customers, manage bookings, and export CSV'
);
jn_s6_assert(
	! user_can( $ast_id, JN_Booking_Roles::CAP_SETTINGS )
	&& ! user_can( $ast_id, 'delete_jn_bookings' )
	&& ! user_can( $ast_id, 'manage_woocommerce' )
	&& ! user_can( $ast_id, 'edit_products' )
	&& ! user_can( $ast_id, 'delete_users' )
	&& ! user_can( $ast_id, 'edit_user', $admin_id )
	&& ! user_can( $ast_id, 'edit_user', $sm_id )
	&& user_can( $ast_id, 'edit_user', (int) $cust_test_id )
	&& ! user_can( $ast_id, 'manage_options' ),
	'Role Matrix [Trợ lý lịch hẹn / Booking Assistant]: CAN edit customer users, CANNOT change booking settings, delete bookings/users, edit staff/admins, or manage WooCommerce products'
);

// 1.4 Editor (`editor`) capabilities
$ed_id = (int) $editor->ID;
jn_s6_assert(
	user_can( $ed_id, 'edit_pages' )
	&& user_can( $ed_id, 'edit_others_posts' )
	&& user_can( $ed_id, 'upload_files' ),
	'Role Matrix [Biên tập nội dung / Editor]: CAN edit pages, posts, and media'
);
jn_s6_assert(
	! user_can( $ed_id, JN_Booking_Roles::CAP_CUSTOMERS )
	&& ! user_can( $ed_id, JN_Booking_Roles::CAP_MANAGE )
	&& ! user_can( $ed_id, JN_Booking_Roles::CAP_SETTINGS )
	&& ! user_can( $ed_id, JN_Booking_Roles::CAP_EXPORT )
	&& ! user_can( $ed_id, 'manage_woocommerce' )
	&& ! user_can( $ed_id, 'list_users' ),
	'Role Matrix [Biên tập nội dung / Editor]: CANNOT access Customer CRM, Bookings, Settings, CSV Export, or WooCommerce'
);

// 1.5 Customer (`customer`) capabilities & isolation
jn_s6_assert(
	! user_can( (int) $cust_test_id, JN_Booking_Roles::CAP_CUSTOMERS )
	&& ! user_can( (int) $cust_test_id, JN_Booking_Roles::CAP_MANAGE )
	&& ! user_can( (int) $cust_test_id, JN_Booking_Roles::CAP_EXPORT )
	&& ! user_can( (int) $cust_test_id, 'edit_posts' )
	&& JN_Booking_Roles::is_customer_only_user( (int) $cust_test_id ),
	'Role Matrix [Khách hàng / Customer]: Isolated from CRM, Bookings, CSV Export, and identified as customer-only'
);

wp_set_current_user( (int) $cust_test_id );
jn_s6_assert(
	false === apply_filters( 'show_admin_bar', true ),
	'Customer isolation: Admin bar is hidden (show_admin_bar = false) for Customer role'
);
wp_set_current_user( $admin_id );

// 1.6 Two-Factor Authentication enforcement for all 4 staff roles
$active_2fa_providers = class_exists( 'Two_Factor_Core' )
	? \Two_Factor_Core::get_providers()
	: apply_filters( 'two_factor_providers', array() );
jn_s6_assert(
	! isset( $active_2fa_providers['Two_Factor_Dummy'] )
	&& isset( $active_2fa_providers['Two_Factor_Email'] )
	&& isset( $active_2fa_providers['Two_Factor_Totp'] )
	&& isset( $active_2fa_providers['Two_Factor_Backup_Codes'] ),
	'Two Factor: Real providers (Email, TOTP, Backup Codes) enabled and Two_Factor_Dummy stripped'
);

$all_staff_2fa_active = true;
foreach ( array( $admin_id, $sm_id, $ast_id, $ed_id ) as $staff_uid ) {
	$is_req = apply_filters( 'two_factor_is_required_for_user', false, $staff_uid );
	$using  = class_exists( 'Two_Factor_Core' ) && \Two_Factor_Core::is_user_using_two_factor( $staff_uid );
	if ( ! $is_req || ! $using ) {
		$all_staff_2fa_active = false;
	}
}
jn_s6_assert(
	$all_staff_2fa_active,
	'Two Factor: Mandatory 2FA is active and enforced for all 4 staff roles (Admin, Shop Manager, Booking Assistant, Editor)'
);

jn_s6_assert(
	false === apply_filters( 'two_factor_is_required_for_user', false, (int) $cust_test_id ),
	'Two Factor: Not forced on regular Customer accounts'
);

// =============================================================================
// 2. Customer CRM Profile (10 Fields) & Bidirectional WooCommerce Billing Sync
// =============================================================================
$crm_user_id = wp_insert_user(
	array(
		'user_login'   => 'jn_s6_crm_owner_' . wp_rand( 1000, 9999 ),
		'user_pass'    => wp_generate_password( 24, true, true ),
		'user_email'   => 'lan.anh.spa@example.com',
		'display_name' => 'Trần Lan Anh',
		'role'         => 'customer',
	)
);
$created_user_ids[] = (int) $crm_user_id;

$update_res = JN_Customer_Profile::update_user_profile(
	(int) $crm_user_id,
	array(
		'full_name'     => 'Trần Thị Lan Anh',
		'phone'         => '0908 234 567',
		'email'         => 'lan.anh.spa@example.com',
		'customer_type' => JN_Customer_Profile::TYPE_OWNER,
		'business_name' => 'Lan Anh Luxury Spa & Clinic',
		'province_city' => 'TP. Hồ Chí Minh',
		'source_type'   => JN_Customer_Profile::SOURCE_UTM,
		'source_detail' => 'utm_source=facebook&utm_campaign=mentorship_q4',
		'tags'          => 'VIP, Chủ Spa, Mentorship',
	)
);

JN_Customer_Profile::add_internal_note( 'user:' . $crm_user_id, 'Khách quan tâm lộ trình Master Mentorship 3 tháng.', $ast_id );
JN_Customer_Profile::append_consent_log(
	'user:' . $crm_user_id,
	array(
		'timestamp'      => '2026-10-27T10:00:00+07:00',
		'source'         => 'Chiến dịch UTM',
		'policy_page_id' => 15,
		'policy_version' => '1.0',
		'text_snapshot'  => JN_Booking_Settings::DEFAULT_CONSENT_TEXT,
		'reference'      => 'JN-202610-0001',
	)
);

$profile = JN_Customer_Profile::get_user_profile( (int) $crm_user_id );
jn_s6_assert(
	true === $update_res
	&& 'Trần Thị Lan Anh' === $profile['full_name']
	&& '+84908234567' === $profile['phone']
	&& 'lan.anh.spa@example.com' === $profile['email']
	&& JN_Customer_Profile::TYPE_OWNER === $profile['customer_type']
	&& 'Chủ cơ sở' === $profile['customer_type_label']
	&& 'Lan Anh Luxury Spa & Clinic' === $profile['business_name']
	&& 'TP. Hồ Chí Minh' === $profile['province_city']
	&& JN_Customer_Profile::SOURCE_UTM === $profile['source_type']
	&& in_array( 'VIP', $profile['tags'], true )
	&& in_array( 'Chủ Spa', $profile['tags'], true )
	&& 1 === count( $profile['internal_notes'] )
	&& 1 === count( $profile['consent_log'] ),
	'Customer CRM Profile: All 10 CRM attributes saved and retrieved accurately'
);

// Verify WooCommerce billing fields sync (CRM -> WC_Customer)
$wc_cust = new \WC_Customer( (int) $crm_user_id );
jn_s6_assert(
	'Trần Thị Lan Anh' === $wc_cust->get_billing_first_name()
	&& '+84908234567' === $wc_cust->get_billing_phone()
	&& 'lan.anh.spa@example.com' === $wc_cust->get_billing_email()
	&& 'Lan Anh Luxury Spa & Clinic' === $wc_cust->get_billing_company()
	&& 'TP. Hồ Chí Minh' === $wc_cust->get_billing_city(),
	'WooCommerce Sync (CRM -> WC_Customer): billing_first_name, billing_phone (+84), billing_email, billing_company, billing_city match CRM'
);

// Verify reverse sync (WC_Customer billing save -> CRM profile)
update_user_meta( (int) $crm_user_id, 'billing_phone', '0919 888 777' );
update_user_meta( (int) $crm_user_id, 'billing_company', 'Lan Anh Aesthetic Center' );
update_user_meta( (int) $crm_user_id, 'billing_city', 'Hà Nội' );
JN_Customer_Profile::sync_wc_billing_to_crm( (int) $crm_user_id );
$profile_after_wc = JN_Customer_Profile::get_user_profile( (int) $crm_user_id );
jn_s6_assert(
	'+84919888777' === $profile_after_wc['phone']
	&& 'Lan Anh Aesthetic Center' === $profile_after_wc['business_name']
	&& 'Hà Nội' === $profile_after_wc['province_city'],
	'WooCommerce Sync (WC_Customer -> CRM): Saving WC billing address updates CRM phone (+84), business_name, and province_city'
);

// Restore phone to +84908234567 for subsequent search tests.
JN_Customer_Profile::update_user_profile( (int) $crm_user_id, array( 'phone' => '0908234567' ) );

// =============================================================================
// 3. Guest-to-Account Linking (Guest Bookings + Guest HPOS Orders + Guest CRM Profile)
// =============================================================================
$guest_email = 'minh.thao.guest@example.com';
$guest_phone = '0938112233';
$guest_key   = 'guest:' . $guest_email;
$created_guests[] = $guest_key;

// 3.1 Create a guest booking via public submission (with auto_create_account = no).
JN_Booking_Settings::update( array( 'auto_create_account' => 'no' ) );
JN_Booking_Service::reset_rate_limits( '127.0.0.55', '+84938112233', $guest_email );

$guest_booking_res = JN_Booking_Service::submit_public_booking(
	array(
		'full_name'        => 'Nguyễn Minh Thảo',
		'phone_number'     => $guest_phone,
		'email_address'    => $guest_email,
		'package_code'     => '60-phut',
		'coaching_format'  => 'online',
		'time_preference'  => 'morning',
		'notes'            => 'Cần tư vấn nâng cấp kỹ thuật phun xăm.',
		'privacy_consent'  => '1',
		'jn_website_hp'    => '',
		'_jn_form_token'   => JN_Booking_Service::issue_form_token( time() - 10 ),
	),
	array(
		'ip'          => '127.0.0.55',
		'landing_url' => 'http://localhost:8080/coaching/?utm_source=zalo&utm_medium=cpc',
	)
);
$guest_booking_id   = (int) ( $guest_booking_res['booking_id'] ?? 0 );
$created_post_ids[] = $guest_booking_id;

// Enrich guest CRM profile with type & tags before registration.
JN_Customer_Profile::update_guest_profile(
	$guest_key,
	array(
		'customer_type' => JN_Customer_Profile::TYPE_PRACTITIONER,
		'business_name' => 'Minh Thảo Brow Studio',
		'province_city' => 'Đà Nẵng',
		'tags'          => array( 'Học viên tiềm năng', 'Phun xăm' ),
	)
);
JN_Customer_Profile::add_internal_note( $guest_key, 'Ghi chú trước khi khách đăng ký tài khoản.', $ast_id );

// 3.2 Create a standalone guest WooCommerce HPOS order with the same email.
$pkg_product = JN_Coaching_Packages::get_product_by_key( '30-phut' );
$guest_order = wc_create_order( array( 'status' => 'completed', 'customer_id' => 0 ) );
$guest_order->add_product( $pkg_product, 1, array( 'subtotal' => 2500000, 'total' => 2500000 ) );
$guest_order->set_billing_first_name( 'Nguyễn Minh Thảo' );
$guest_order->set_billing_email( $guest_email );
$guest_order->set_billing_phone( '+84938112233' );
$guest_order->set_total( 2500000 );
$guest_order->save();
$guest_order_id      = (int) $guest_order->get_id();
$created_order_ids[] = $guest_order_id;

jn_s6_assert(
	0 === (int) get_post_meta( $guest_booking_id, '_jn_customer_user_id', true )
	&& 0 === (int) $guest_order->get_customer_id(),
	'Guest-to-account setup: Booking and HPOS order start with customer_id = 0 (guest)'
);

// 3.3 Register a new WP user with the same email (`minh.thao.guest@example.com`).
$registered_uid = wp_insert_user(
	array(
		'user_login'   => 'minh_thao_linked_' . wp_rand( 1000, 9999 ),
		'user_pass'    => wp_generate_password( 24, true, true ),
		'user_email'   => $guest_email,
		'display_name' => 'Nguyễn Minh Thảo',
		'role'         => 'customer',
	)
);
$created_user_ids[] = (int) $registered_uid;

// Verify automatic linking triggered by `user_register`.
$reloaded_order  = wc_get_order( $guest_order_id );
$merged_profile  = JN_Customer_Profile::get_user_profile( (int) $registered_uid );

jn_s6_assert(
	(int) $registered_uid === (int) get_post_meta( $guest_booking_id, '_jn_customer_user_id', true )
	&& $reloaded_order instanceof \WC_Order
	&& (int) $registered_uid === (int) $reloaded_order->get_customer_id(),
	'Guest-to-account linking (on registration): Both guest booking AND guest HPOS order automatically attached to new user ID'
);

jn_s6_assert(
	JN_Customer_Profile::TYPE_PRACTITIONER === $merged_profile['customer_type']
	&& 'Minh Thảo Brow Studio' === $merged_profile['business_name']
	&& 'Đà Nẵng' === $merged_profile['province_city']
	&& in_array( 'Học viên tiềm năng', $merged_profile['tags'], true )
	&& ! empty( $merged_profile['internal_notes'] )
	&& ! empty( $merged_profile['consent_log'] ),
	'Guest-to-account linking (CRM merge): Guest CRM fields, tags, internal notes, and consent log merged into user profile'
);

// 3.4 Verify linking on `wp_login` for another guest booking created while logged out.
$second_guest_booking_id = wp_insert_post(
	array(
		'post_type'   => JN_Booking_CPT::POST_TYPE,
		'post_status' => JN_Booking_CPT::STATUS_PENDING,
		'post_title'  => 'JN-202610-LOGIN',
	)
);
$created_post_ids[] = (int) $second_guest_booking_id;
update_post_meta( $second_guest_booking_id, '_jn_booking_code', 'JN-202610-LOGIN' );
update_post_meta( $second_guest_booking_id, '_jn_customer_user_id', 0 );
update_post_meta( $second_guest_booking_id, '_jn_customer_name', 'Nguyễn Minh Thảo' );
update_post_meta( $second_guest_booking_id, '_jn_customer_email', $guest_email );
update_post_meta( $second_guest_booking_id, '_jn_customer_phone', '+84938112233' );

$registered_user_obj = get_userdata( (int) $registered_uid );
JN_Customer_Service::handle_user_login( $registered_user_obj->user_login, $registered_user_obj );

jn_s6_assert(
	(int) $registered_uid === (int) get_post_meta( $second_guest_booking_id, '_jn_customer_user_id', true ),
	'Guest-to-account linking (on login): Guest booking with matching verified email attached on wp_login'
);

// =============================================================================
// 4. Phone-Format Search, Filters, Customer Detail & "Tạo lịch hẹn" Walk-in Action
// =============================================================================
// Search for Trần Thị Lan Anh (`+84908234567`) using 5 different phone formats:
$search_local   = JN_Customer_Service::query_customers( array( 'search' => '0908234567' ) );
$search_e164    = JN_Customer_Service::query_customers( array( 'search' => '+84908234567' ) );
$search_84      = JN_Customer_Service::query_customers( array( 'search' => '84908234567' ) );
$search_spaced  = JN_Customer_Service::query_customers( array( 'search' => '0908 234 567' ) );
$search_dotted  = JN_Customer_Service::query_customers( array( 'search' => '(+84) 908.234.567' ) );

$extract_keys = static function ( array $rows ): array {
	return array_column( $rows, 'customer_key' );
};
$target_key = 'user:' . (int) $crm_user_id;

jn_s6_assert(
	in_array( $target_key, $extract_keys( $search_local ), true )
	&& in_array( $target_key, $extract_keys( $search_e164 ), true )
	&& in_array( $target_key, $extract_keys( $search_84 ), true )
	&& in_array( $target_key, $extract_keys( $search_spaced ), true )
	&& in_array( $target_key, $extract_keys( $search_dotted ), true ),
	'Phone-format search: Searching by 0908234567, +84908234567, 84908234567, "0908 234 567", and "(+84) 908.234.567" all find the customer'
);

// Filter by customer_type = `tho_hoc_vien`, tag = `Phun xăm`, and `has_pending_booking = 1`
$filter_by_type = JN_Customer_Service::query_customers( array( 'type' => JN_Customer_Profile::TYPE_PRACTITIONER ) );
$filter_by_tag  = JN_Customer_Service::query_customers( array( 'tag' => 'Phun xăm' ) );
$filter_pending = JN_Customer_Service::query_customers( array( 'has_pending_booking' => '1' ) );
$thao_key       = 'user:' . (int) $registered_uid;

jn_s6_assert(
	in_array( $thao_key, $extract_keys( $filter_by_type ), true )
	&& in_array( $thao_key, $extract_keys( $filter_by_tag ), true )
	&& in_array( $thao_key, $extract_keys( $filter_pending ), true ),
	'Directory Filters: Filtering by type (Thợ – Học viên), tag (Phun xăm), and has_pending_booking accurately returns matching customer'
);

// Staff "Tạo lịch hẹn" action for phone / walk-in request on `Trần Thị Lan Anh`
$walkin_res = JN_Customer_Service::create_staff_booking(
	array(
		'user_id'          => (int) $crm_user_id,
		'full_name'        => 'Trần Thị Lan Anh',
		'phone'            => '+84908234567',
		'email'            => 'lan.anh.spa@example.com',
		'package_code'     => 'mentorship-3-thang',
		'format'           => 'in-person',
		'preferred_window' => 'afternoon',
		'confirm_now'      => 'yes',
		'confirmed_start'  => '2026-11-15 14:00',
		'duration_minutes' => 90,
		'location'         => 'Học viện Jannie Nguyễn — Quận 1, TP.HCM',
		'agreed_price'     => 45000000,
		'goals'            => 'Đăng ký trực tiếp qua hotline — chuẩn hóa quy trình vận hành Spa.',
		'staff_notes'      => 'Trợ lý tạo lịch hẹn qua điện thoại.',
	),
	$ast_id
);

$walkin_booking_id = is_array( $walkin_res ) ? (int) ( $walkin_res['booking_id'] ?? 0 ) : 0;
$walkin_order_id   = is_array( $walkin_res ) ? (int) ( $walkin_res['order_id'] ?? 0 ) : 0;
if ( $walkin_booking_id > 0 ) {
	$created_post_ids[] = $walkin_booking_id;
}
if ( $walkin_order_id > 0 ) {
	$created_order_ids[] = $walkin_order_id;
}

jn_s6_assert(
	! is_wp_error( $walkin_res )
	&& $walkin_booking_id > 0
	&& $walkin_order_id > 0
	&& JN_Booking_CPT::STATUS_CONFIRMED === get_post_status( $walkin_booking_id ),
	'Staff "Tạo lịch hẹn" action: Walk-in / phone booking created and confirmed with linked WooCommerce HPOS order'
);

// Verify Customer Detail aggregation (`get_customer_detail`)
$thao_detail = JN_Customer_Service::get_customer_detail( $thao_key );
jn_s6_assert(
	is_array( $thao_detail )
	&& count( $thao_detail['bookings'] ) >= 2
	&& count( $thao_detail['orders'] ) >= 1
	&& 2500000.0 === (float) $thao_detail['total_spent']
	&& ! empty( $thao_detail['profile']['internal_notes'] )
	&& ! empty( $thao_detail['profile']['consent_log'] ),
	'Customer Detail view data: Aggregates profile, booking history, HPOS order history, total spent, internal notes, and consent log',
	sprintf(
		'bookings=%d, orders=%d, spent=%s, notes=%d, consent=%d',
		 count( $thao_detail['bookings'] ?? array() ),
		 count( $thao_detail['orders'] ?? array() ),
		 (string) ( $thao_detail['total_spent'] ?? 'null' ),
		 count( $thao_detail['profile']['internal_notes'] ?? array() ),
		 count( $thao_detail['profile']['consent_log'] ?? array() )
	)
);

// =============================================================================
// 5. Duplicate Warning Detection & Audit Logging (Without Auto-Merging)
// =============================================================================
// Create a guest booking with a DIFFERENT email (`other.email@example.com`) but the SAME phone (`0908 234 567`) as `Trần Thị Lan Anh`
$dup_guest_email = 'other.email.same.phone@example.com';
$dup_guest_key   = 'guest:' . $dup_guest_email;
$created_guests[] = $dup_guest_key;

$dup_booking_id = wp_insert_post(
	array(
		'post_type'   => JN_Booking_CPT::POST_TYPE,
		'post_status' => JN_Booking_CPT::STATUS_PENDING,
		'post_title'  => 'JN-202610-DUP',
	)
);
$created_post_ids[] = (int) $dup_booking_id;
update_post_meta( $dup_booking_id, '_jn_booking_code', 'JN-202610-DUP' );
update_post_meta( $dup_booking_id, '_jn_customer_user_id', 0 );
update_post_meta( $dup_booking_id, '_jn_customer_name', 'Trần Lan Anh (Bản ghi trùng SĐT)' );
update_post_meta( $dup_booking_id, '_jn_customer_email', $dup_guest_email );
update_post_meta( $dup_booking_id, '_jn_customer_phone', '+84908234567' );

$detected_conflicts = JN_Customer_Service::check_and_log_duplicates_for_customer(
	$dup_guest_key,
	$dup_guest_email,
	'+84908234567',
	'test_duplicate_phone'
);
$dup_log = JN_Customer_Service::get_duplicate_log();

jn_s6_assert(
	! empty( $detected_conflicts )
	&& in_array( $target_key, array_column( $detected_conflicts, 'customer_key' ), true )
	&& ! empty( $dup_log )
	&& 0 === (int) get_post_meta( $dup_booking_id, '_jn_customer_user_id', true ),
	'Duplicate warning: Phone collision detected, logged in jn_customer_duplicate_log, and NOT auto-merged'
);

// =============================================================================
// 6. CSV Export (Nonce-Protected, Role-Checked, Streamed UTF-8 with BOM & Formula Escaping)
// =============================================================================
// 6.1 Authorization & Nonce verification
wp_set_current_user( $ed_id ); // Editor is NOT allowed to export CSV
$ed_nonce = wp_create_nonce( 'jn_export_customers_csv' );
$ed_check = JN_CSV_Exporter::verify_export_request( 'jn_export_customers_csv', $ed_nonce, $ed_id );
jn_s6_assert(
	is_wp_error( $ed_check ) && 'forbidden_csv_export' === $ed_check->get_error_code(),
	'CSV Export security: Editor role is denied CSV export (403 forbidden_csv_export)'
);

wp_set_current_user( $ast_id ); // Booking Assistant IS allowed to export CSV
$ast_valid_nonce = wp_create_nonce( 'jn_export_customers_csv' );
$ast_bad_nonce   = JN_CSV_Exporter::verify_export_request( 'jn_export_customers_csv', 'invalid_nonce_val', $ast_id );
$ast_good_check  = JN_CSV_Exporter::verify_export_request( 'jn_export_customers_csv', $ast_valid_nonce, $ast_id );
jn_s6_assert(
	is_wp_error( $ast_bad_nonce )
	&& 'invalid_csv_nonce' === $ast_bad_nonce->get_error_code()
	&& true === $ast_good_check,
	'CSV Export security: Rejects invalid nonce and permits Booking Assistant with valid nonce'
);
wp_set_current_user( $admin_id );

// 6.2 Customers CSV content, UTF-8 BOM (`\xEF\xBB\xBF`), and Vietnamese diacritics
$customers_csv = JN_CSV_Exporter::build_customers_csv_string( array( 'search' => '0908234567' ) );
$bookings_csv  = JN_CSV_Exporter::build_bookings_csv_string( array( 'search' => '0908234567' ) );

jn_s6_assert(
	0 === strpos( $customers_csv, "\xEF\xBB\xBF" )
	&& false !== strpos( $customers_csv, 'Họ và Tên' )
	&& false !== strpos( $customers_csv, 'Trần Thị Lan Anh' )
	&& false !== strpos( $customers_csv, '+84908234567' )
	&& false !== strpos( $customers_csv, 'Chủ cơ sở' ),
	'Customers CSV: Starts with exact 3-byte UTF-8 BOM (EF BB BF) and preserves Vietnamese diacritics and CRM fields'
);

jn_s6_assert(
	0 === strpos( $bookings_csv, "\xEF\xBB\xBF" )
	&& false !== strpos( $bookings_csv, 'Mã Lịch Hẹn' )
	&& false !== strpos( $bookings_csv, 'Trần Thị Lan Anh' )
	&& false !== strpos( $bookings_csv, 'Gói Master Mentorship (3 Tháng)' ),
	'Bookings CSV: Starts with exact 3-byte UTF-8 BOM (EF BB BF) and includes Vietnamese booking columns & data'
);

jn_s6_assert(
	"'=CMD|' /C calc'!A0" === JN_CSV_Exporter::sanitize_csv_cell( "=CMD|' /C calc'!A0" )
	&& '+84908234567' === JN_CSV_Exporter::sanitize_csv_cell( '+84908234567', true ),
	'CSV Formula Injection protection: Leading "=" is escaped with single quote while valid +84 phone is preserved'
);

// =============================================================================
// 7. Privacy Exporter, Eraser (Including CRM Fields) & Cancelled Guest Retention Policy
// =============================================================================
// 7.1 Personal Data Exporter includes CRM fields (`jn-customer-crm`) and Bookings (`jn-bookings`)
$exporters = apply_filters( 'wp_privacy_personal_data_exporters', array() );
$erasers   = apply_filters( 'wp_privacy_personal_data_erasers', array() );

jn_s6_assert(
	isset( $exporters['jn-customer-crm'], $exporters['jn-bookings'] )
	&& isset( $erasers['jn-customer-crm'], $erasers['jn-bookings'] ),
	'Privacy Registration: Both jn-customer-crm and jn-bookings exporters and erasers are registered'
);

$crm_export_res = JN_Booking_Privacy::export_customer_crm_data( 'lan.anh.spa@example.com', 1 );
$crm_export_str = wp_json_encode( $crm_export_res, JSON_UNESCAPED_UNICODE );

jn_s6_assert(
	true === $crm_export_res['done']
	&& ! empty( $crm_export_res['data'] )
	&& false !== strpos( (string) $crm_export_str, 'Trần Thị Lan Anh' )
	&& false !== strpos( (string) $crm_export_str, 'Chủ cơ sở' )
	&& false !== strpos( (string) $crm_export_str, 'Lan Anh Aesthetic Center' )
	&& false !== strpos( (string) $crm_export_str, 'VIP, Chủ Spa, Mentorship' )
	&& false !== strpos( (string) $crm_export_str, 'Nhật ký đồng ý bảo mật' ),
	'Privacy Exporter (CRM): Outputs full CRM profile fields (Name, Phone, Type, Business, Province, Source, Tags, Consent Log)'
);

// 7.2 Personal Data Eraser removes CRM fields and anonymizes bookings
$erase_crm_res  = JN_Booking_Privacy::erase_customer_crm_data( 'lan.anh.spa@example.com', 1 );
$erase_book_res = JN_Booking_Privacy::erase_personal_data( 'lan.anh.spa@example.com', 1 );

jn_s6_assert(
	true === $erase_crm_res['items_removed']
	&& '' === (string) get_user_meta( (int) $crm_user_id, '_jn_phone_e164', true )
	&& '' === (string) get_user_meta( (int) $crm_user_id, '_jn_business_name', true )
	&& '' === (string) get_user_meta( (int) $crm_user_id, 'billing_phone', true )
	&& true === $erase_book_res['items_removed']
	&& 'Khách hàng đã ẩn danh' === (string) get_post_meta( $walkin_booking_id, '_jn_customer_name', true ),
	'Privacy Eraser (CRM + Bookings): Clears all user CRM meta, WC billing meta, and anonymizes booking PII'
);

// 7.3 Cancelled Guest Bookings Retention Setting (Default OFF, logged as Owner Decision ADR-009)
JN_Booking_Settings::update( $original_settings ); // Ensure defaults
$default_retention = (string) JN_Booking_Settings::get( 'retention_cancelled_guest_enabled', 'no' );
$owner_decisions   = JN_Booking_Settings::get_owner_decisions_log();

jn_s6_assert(
	'no' === $default_retention && ! empty( $owner_decisions ),
	'Privacy Retention Policy: Cancelled guest booking retention defaults to OFF ("no") and is logged as an owner decision'
);

// Create an old cancelled guest booking (120 days old)
$old_cancelled_id = wp_insert_post(
	array(
		'post_type'     => JN_Booking_CPT::POST_TYPE,
		'post_status'   => JN_Booking_CPT::STATUS_CANCELLED,
		'post_title'    => 'JN-202606-OLD',
		'post_date'     => gmdate( 'Y-m-d H:i:s', time() - ( 120 * DAY_IN_SECONDS ) ),
		'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - ( 120 * DAY_IN_SECONDS ) ),
	)
);
$created_post_ids[] = (int) $old_cancelled_id;
update_post_meta( $old_cancelled_id, '_jn_booking_code', 'JN-202606-OLD' );
update_post_meta( $old_cancelled_id, '_jn_customer_user_id', 0 );
update_post_meta( $old_cancelled_id, '_jn_customer_name', 'Khách Hủy Cũ 120 Ngày' );
update_post_meta( $old_cancelled_id, '_jn_customer_email', 'old.cancelled.guest@example.com' );
update_post_meta( $old_cancelled_id, '_jn_customer_phone', '+84905999888' );

// While default is OFF ('no'), cleanup must NOT touch the record.
$purged_when_off = JN_Booking_Privacy::run_cancelled_guest_retention_cleanup();
jn_s6_assert(
	0 === $purged_when_off
	&& 'Khách Hủy Cũ 120 Ngày' === (string) get_post_meta( $old_cancelled_id, '_jn_customer_name', true ),
	'Privacy Retention Policy (when OFF): Does not anonymize cancelled guest bookings'
);

// Enable retention (`retention_cancelled_guest_enabled = yes`, `retention_cancelled_guest_days = 90`) and run cleanup.
JN_Booking_Settings::update(
	array(
		'retention_cancelled_guest_enabled' => 'yes',
		'retention_cancelled_guest_days'    => 90,
	)
);
$purged_when_on = JN_Booking_Privacy::run_cancelled_guest_retention_cleanup();
jn_s6_assert(
	$purged_when_on >= 1
	&& 'Khách hàng đã ẩn danh' === (string) get_post_meta( $old_cancelled_id, '_jn_customer_name', true )
	&& 'deleted@site.invalid' === (string) get_post_meta( $old_cancelled_id, '_jn_customer_email', true ),
	'Privacy Retention Policy (when ON): Anonymizes cancelled guest bookings older than retention window'
);

// =============================================================================
// Teardown & Cleanup Temporary Test Entities
// =============================================================================
foreach ( $created_post_ids as $pid ) {
	if ( $pid > 0 ) {
		wp_delete_post( $pid, true );
	}
}
foreach ( $created_order_ids as $oid ) {
	if ( $oid > 0 && function_exists( 'wc_get_order' ) ) {
		$ord = wc_get_order( $oid );
		if ( $ord instanceof \WC_Order ) {
			$ord->delete( true );
		}
	}
}
if ( ! function_exists( 'wp_delete_user' ) ) {
	require_once ABSPATH . 'wp-admin/includes/user.php';
}
foreach ( $created_user_ids as $uid ) {
	if ( $uid > 0 ) {
		wp_delete_user( $uid );
	}
}
update_option( JN_Booking_Settings::OPTION_KEY, $original_settings, false );
update_option( JN_Customer_Service::DUPLICATE_LOG_OPTION, $original_dup_log, false );
update_option( JN_Customer_Profile::GUEST_PROFILES_OPTION, $original_guests, false );

echo "=================================================================\n";
echo sprintf(
	"RESULTS: %d / %d TESTS PASSED (%d failed)\n",
	$GLOBALS['jn_s6_tests_passed'],
	$GLOBALS['jn_s6_tests_total'],
	count( $GLOBALS['jn_s6_tests_failed'] )
);
echo "=================================================================\n";

if ( ! empty( $GLOBALS['jn_s6_tests_failed'] ) ) {
	exit( 1 );
}
