<?php
/**
 * JN Step 6 — E2E Helper for Preparing & Cleaning Up Customer Management & Staff 2FA Entities.
 *
 * Usage:
 *   ./scripts/wp.sh eval '$action="prepare"; require "/scripts/step-6-e2e-helper.php";'
 *   ./scripts/wp.sh eval '$action="cleanup"; require "/scripts/step-6-e2e-helper.php";'
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$action = isset( $action ) ? (string) $action : 'prepare';

/**
 * Purge all temporary E2E customers, bookings, orders, and duplicate logs.
 *
 * @return array<string, mixed>
 */
function jn_step6_purge_e2e_entities(): array {
	if ( ! function_exists( 'wp_delete_user' ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
	}

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
		if ( $ord instanceof \WC_Order ) {
			$ord->delete( true );
			$deleted_orders[] = (int) $oid;
		}
	}

	$cust_users = get_users(
		array(
			'role__in' => array( 'customer', 'subscriber' ),
			'fields'   => 'ID',
		)
	);
	$deleted_users = array();
	foreach ( $cust_users as $uid ) {
		wp_delete_user( (int) $uid );
		$deleted_users[] = (int) $uid;
	}

	// Also remove any legacy placeholder staff usernames if present.
	foreach ( array( 'jn_shop_manager', 'jn_booking_assistant', 'jn_content_editor' ) as $legacy_login ) {
		$lu = get_user_by( 'login', $legacy_login );
		if ( $lu instanceof \WP_User ) {
			wp_delete_user( (int) $lu->ID );
		}
	}

	update_option( JN_Customer_Profile::GUEST_PROFILES_OPTION, array(), false );
	update_option( JN_Customer_Service::DUPLICATE_LOG_OPTION, array(), false );

	// Ensure staff accounts never hold leftover customer CRM meta.
	$staff_ids = JN_Booking_Roles::ensure_staff_accounts();
	$all_staff = array_unique( array_merge( array( 1 ), array_values( $staff_ids ) ) );
	foreach ( $all_staff as $suid ) {
		foreach ( array( '_jn_full_name', '_jn_phone_e164', '_jn_customer_type', '_jn_business_name', '_jn_province_city', '_jn_tags', '_jn_internal_notes', '_jn_consent_log', 'billing_phone', 'billing_first_name', 'billing_company', 'billing_city' ) as $mkey ) {
			delete_user_meta( (int) $suid, $mkey );
		}
	}

	// Rotate staff passwords to fresh random strings.
	foreach ( array( 'quan_ly_cua_hang', 'tro_ly_lich_hen', 'bien_tap_noi_dung' ) as $slogin ) {
		$su = get_user_by( 'login', $slogin );
		if ( $su instanceof \WP_User ) {
			wp_set_password( wp_generate_password( 24, true, true ), (int) $su->ID );
		}
	}

	JN_Booking_Settings::ensure_defaults();

	return array(
		'deleted_bookings' => $deleted_bookings,
		'deleted_orders'   => $deleted_orders,
		'deleted_users'    => $deleted_users,
		'staff_ids'        => $staff_ids,
	);
}

if ( 'prepare' === $action ) {
	JN_Coaching_Packages::ensure_packages_exist();
	JN_Booking_Roles::sync_roles_and_caps();
	jn_step6_purge_e2e_entities();

	$staff_ids    = JN_Booking_Roles::ensure_staff_accounts();
	$assistant_id = (int) ( $staff_ids[ JN_Booking_Roles::ROLE_ASSISTANT ] ?? 0 );
	$shop_mgr_id  = (int) ( $staff_ids['shop_manager'] ?? 0 );

	// Set an ephemeral E2E password on `tro_ly_lich_hen` and return it to the runner.
	$ephemeral_pass = 'JnE2e!' . wp_generate_password( 14, false, false );
	wp_set_password( $ephemeral_pass, $assistant_id );

	// 1. Seed Customer 1: Trần Thị Lan Anh (`Chủ cơ sở`, VIP, 2 bookings, 1 paid HPOS order, internal notes, consent log).
	$cust1_id = wp_insert_user(
		array(
			'user_login'   => 'tran_thi_lan_anh',
			'user_pass'    => wp_generate_password( 20, true, true ),
			'user_email'   => 'lan.anh.luxury@example.com',
			'display_name' => 'Trần Thị Lan Anh',
			'role'         => 'customer',
		)
	);
	JN_Customer_Profile::update_user_profile(
		(int) $cust1_id,
		array(
			'full_name'     => 'Trần Thị Lan Anh',
			'phone'         => '0908 234 567',
			'email'         => 'lan.anh.luxury@example.com',
			'customer_type' => JN_Customer_Profile::TYPE_OWNER,
			'business_name' => 'Lan Anh Luxury Spa & Clinic',
			'province_city' => 'TP. Hồ Chí Minh',
			'source_type'   => JN_Customer_Profile::SOURCE_UTM,
			'source_detail' => 'utm_source=facebook&utm_campaign=mentorship_q4',
			'tags'          => 'VIP, Chủ Spa, Mentorship',
		)
	);
	JN_Customer_Profile::add_internal_note(
		'user:' . $cust1_id,
		'Khách chủ hệ thống 2 chi nhánh tại Quận 1 và Quận 3, ưu tiên lịch cố vấn chiều Thứ 5.',
		$assistant_id
	);
	JN_Customer_Profile::add_internal_note(
		'user:' . $cust1_id,
		'Đã gửi bộ câu hỏi khảo sát vận hành trước buổi Master Mentorship #1.',
		$shop_mgr_id
	);

	// Create confirmed + paid booking for Customer 1
	$b1 = JN_Customer_Service::create_staff_booking(
		array(
			'user_id'          => (int) $cust1_id,
			'full_name'        => 'Trần Thị Lan Anh',
			'phone'            => '+84908234567',
			'email'            => 'lan.anh.luxury@example.com',
			'package_code'     => 'mentorship-3-thang',
			'format'           => 'in-person',
			'preferred_window' => 'afternoon',
			'confirm_now'      => 'yes',
			'confirmed_start'  => '2026-11-12 14:00',
			'duration_minutes' => 90,
			'location'         => 'Học viện Jannie Nguyễn — Quận 1, TP.HCM',
			'agreed_price'     => 45000000,
			'goals'            => 'Tái cấu trúc quy trình vận hành 2 chi nhánh Spa và đào tạo đội ngũ kỹ thuật viên nòng cốt.',
			'staff_notes'      => 'Đã chốt lịch buổi 1 trực tiếp tại Học viện.',
		),
		$assistant_id
	);
	if ( ! empty( $b1['order_id'] ) ) {
		$ord1 = wc_get_order( (int) $b1['order_id'] );
		if ( $ord1 instanceof \WC_Order ) {
			$ord1->payment_complete();
			$ord1->set_status( 'completed' );
			$ord1->save();
		}
	}

	// Create a second pending booking for Customer 1
	JN_Customer_Service::create_staff_booking(
		array(
			'user_id'          => (int) $cust1_id,
			'full_name'        => 'Trần Thị Lan Anh',
			'phone'            => '+84908234567',
			'email'            => 'lan.anh.luxury@example.com',
			'package_code'     => '60-phut',
			'format'           => 'online',
			'preferred_window' => 'morning',
			'confirm_now'      => 'no',
			'goals'            => 'Đăng ký thêm 1 buổi rà soát chiến dịch cuối năm.',
		),
		$assistant_id
	);

	// 2. Seed Customer 2: Nguyễn Minh Thảo (`Thợ – Học viên`, 1 confirmed booking + paid order).
	$cust2_id = wp_insert_user(
		array(
			'user_login'   => 'nguyen_minh_thao',
			'user_pass'    => wp_generate_password( 20, true, true ),
			'user_email'   => 'minh.thao.brow@example.com',
			'display_name' => 'Nguyễn Minh Thảo',
			'role'         => 'customer',
		)
	);
	JN_Customer_Profile::update_user_profile(
		(int) $cust2_id,
		array(
			'full_name'     => 'Nguyễn Minh Thảo',
			'phone'         => '0938 112 233',
			'email'         => 'minh.thao.brow@example.com',
			'customer_type' => JN_Customer_Profile::TYPE_PRACTITIONER,
			'business_name' => 'Minh Thảo Brow Studio',
			'province_city' => 'Đà Nẵng',
			'source_type'   => JN_Customer_Profile::SOURCE_FORM,
			'source_detail' => 'https://jannienguyen.vn/coaching/',
			'tags'          => 'Nâng cấp tay nghề, Phun xăm',
		)
	);
	$b2 = JN_Customer_Service::create_staff_booking(
		array(
			'user_id'          => (int) $cust2_id,
			'full_name'        => 'Nguyễn Minh Thảo',
			'phone'            => '+84938112233',
			'email'            => 'minh.thao.brow@example.com',
			'package_code'     => '60-phut',
			'format'           => 'online',
			'preferred_window' => 'morning',
			'confirm_now'      => 'yes',
			'confirmed_start'  => '2026-11-14 09:30',
			'duration_minutes' => 60,
			'meeting_link'     => 'https://zoom.us/j/98877665544',
			'agreed_price'     => 5500000,
			'goals'            => 'Chỉnh sửa lỗi sợi bị loang màu và nâng cao kỹ thuật đi kim.',
		),
		$assistant_id
	);
	if ( ! empty( $b2['order_id'] ) ) {
		$ord2 = wc_get_order( (int) $b2['order_id'] );
		if ( $ord2 instanceof \WC_Order ) {
			$ord2->payment_complete();
			$ord2->set_status( 'processing' );
			$ord2->save();
		}
	}

	// 3. Seed Customer 3 (Guest customer from booking): Lê Hoàng Ngọc Bích (`Cá nhân`, pending booking).
	$guest3_email = 'ngoc.bich.guest@example.com';
	$guest3_key   = 'guest:' . $guest3_email;
	JN_Customer_Service::create_staff_booking(
		array(
			'user_id'          => 0,
			'full_name'        => 'Lê Hoàng Ngọc Bích',
			'phone'            => '0912 345 678',
			'email'            => $guest3_email,
			'package_code'     => '30-phut',
			'format'           => 'online',
			'preferred_window' => 'weekend',
			'confirm_now'      => 'no',
			'goals'            => 'Tư vấn định hướng nghề thẩm mỹ cho người mới bắt đầu.',
		),
		$assistant_id
	);
	JN_Customer_Profile::update_guest_profile(
		$guest3_key,
		array(
			'customer_type' => JN_Customer_Profile::TYPE_INDIVIDUAL,
			'province_city' => 'Hà Nội',
			'source_type'   => JN_Customer_Profile::SOURCE_REFERRAL,
			'source_detail' => 'Học viên Nguyễn Minh Thảo giới thiệu',
			'tags'          => 'Tư vấn 1:1, Khách mới',
		)
	);

	// 4. Seed Customer 4 (Guest record sharing the exact same phone `+84908234567` as Customer 1 to demonstrate Duplicate Warning).
	$dup_email = 'lan.anh.branch2@example.com';
	$dup_key   = 'guest:' . $dup_email;
	JN_Customer_Service::create_staff_booking(
		array(
			'user_id'          => 0,
			'full_name'        => 'Trần Lan Anh (Chi nhánh Quận 3)',
			'phone'            => '0908234567',
			'email'            => $dup_email,
			'package_code'     => '30-phut',
			'format'           => 'online',
			'preferred_window' => 'afternoon',
			'confirm_now'      => 'no',
			'goals'            => 'Liên hệ từ email phụ nhưng dùng chung số Zalo chính.',
		),
		$assistant_id
	);
	JN_Customer_Profile::update_guest_profile(
		$dup_key,
		array(
			'full_name'     => 'Trần Lan Anh (Chi nhánh Quận 3)',
			'phone'         => '0908234567',
			'email'         => $dup_email,
			'customer_type' => JN_Customer_Profile::TYPE_OWNER,
			'business_name' => 'Lan Anh Luxury Spa — CN Quận 3',
			'province_city' => 'TP. Hồ Chí Minh',
			'tags'          => 'VIP, Trùng SĐT',
		)
	);

	echo 'STEP6_PREPARE_JSON=' . wp_json_encode(
		array(
			'assistant_login' => 'tro_ly_lich_hen',
			'assistant_pass'  => $ephemeral_pass,
			'cust1_key'       => 'user:' . (int) $cust1_id,
			'cust2_key'       => 'user:' . (int) $cust2_id,
			'guest3_key'      => $guest3_key,
			'dup_key'         => $dup_key,
		)
	) . PHP_EOL;
} elseif ( 'cleanup' === $action ) {
	$purged = jn_step6_purge_e2e_entities();
	echo 'STEP6_CLEANUP_JSON=' . wp_json_encode( $purged ) . PHP_EOL;
}
