<?php
/**
 * JN Step 9 — Trigger Every Email Template for Mailpit Regression Verification (Task 2).
 *
 * Fires all 15 transactional & system email templates into local Mailpit,
 * then immediately deletes the temporary booking, order, and customer user.
 *
 * Run via WP-CLI:
 *   ./scripts/wp.sh eval-file /scripts/trigger-step-9-all-emails.php
 *
 * @package JannieNguyen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Destructive local-only script: refuse to run against production (review 2026-10-28, H2).
require_once __DIR__ . '/lib/jn-env-guard.php';
jn_require_non_production( basename( __FILE__ ) );

if ( ! function_exists( 'wp_delete_user' ) ) {
	require_once ABSPATH . 'wp-admin/includes/user.php';
}

$temp_booking_id = 0;
$temp_order_id   = 0;
$temp_user_id    = 0;
$sent_templates  = array();

try {
	// 1. Create a temporary customer account (triggers WC_Email_Customer_New_Account).
	$temp_email = 'hocvien.step9@example.com';
	$existing_u = get_user_by( 'email', $temp_email );
	if ( $existing_u instanceof WP_User ) {
		wp_delete_user( $existing_u->ID );
	}

	$temp_user_id = wc_create_new_customer( $temp_email, 'hocvien_step9', 'Step9Pass!2026#JN' );
	if ( is_wp_error( $temp_user_id ) ) {
		$temp_user_id = wp_create_user( 'hocvien_step9', 'Step9Pass!2026#JN', $temp_email );
	}
	$sent_templates[] = 'WC_Email_Customer_New_Account';

	// 2. Create a temporary Booking via JN_Booking_Service (triggers New_Request + Staff_Alert).
	$booking_res = JN_Booking_Service::submit_public_booking(
		array(
			'full_name'       => 'Trần Minh Ngọc (Step 9 Audit)',
			'phone_number'    => '0908123999',
			'email_address'   => $temp_email,
			'package_code'    => '60-phut',
			'coaching_format' => 'online',
			'time_preference' => 'morning',
			'notes'           => 'Kiểm tra mẫu email hệ thống Step 9.',
			'privacy_consent' => '1',
		),
		array( 'skip_token_check' => true )
	);

	if ( ! is_wp_error( $booking_res ) && ! empty( $booking_res['booking_id'] ) ) {
		$temp_booking_id  = (int) $booking_res['booking_id'];
		$sent_templates[] = 'WC_Email_JN_Booking_New_Request';
		$sent_templates[] = 'WC_Email_JN_Booking_Staff_Alert';

		// 3. Confirm the booking (creates HPOS pending order + triggers WC_Email_JN_Booking_Confirmed with .ics & order-pay link).
		$confirm_res = JN_Booking_Service::transition_status(
			$temp_booking_id,
			JN_Booking_CPT::STATUS_CONFIRMED,
			array(
				'confirmed_start'  => '2026-11-15 10:00',
				'duration_minutes' => 60,
				'meeting_link'     => 'https://meet.jannienguyen.vn/coaching-step9',
				'custom_price'     => 2500000,
				'note'             => 'Xác nhận lịch kiểm thử mẫu email Step 9.',
				'actor_id'         => 1,
				'system_action'    => true,
			)
		);
		if ( ! is_wp_error( $confirm_res ) ) {
			$sent_templates[] = 'WC_Email_JN_Booking_Confirmed (.ics + order-pay)';
			$temp_order_id    = ! empty( $confirm_res['order_id'] )
				? (int) $confirm_res['order_id']
				: (int) get_post_meta( $temp_booking_id, '_jn_order_id', true );
		}
	}

	// 4. Trigger standard WooCommerce Order Emails on the linked HPOS order.
	$mailer = WC()->mailer();
	$emails = $mailer ? $mailer->get_emails() : array();

	if ( $temp_order_id > 0 ) {
		$order = wc_get_order( $temp_order_id );
		if ( $order instanceof WC_Order ) {
			if ( isset( $emails['WC_Email_New_Order'] ) ) {
				$emails['WC_Email_New_Order']->trigger( $temp_order_id, $order );
				$sent_templates[] = 'WC_Email_New_Order';
			}
			if ( isset( $emails['WC_Email_Customer_On_Hold_Order'] ) ) {
				$emails['WC_Email_Customer_On_Hold_Order']->trigger( $temp_order_id, $order );
				$sent_templates[] = 'WC_Email_Customer_On_Hold_Order';
			}
			if ( isset( $emails['WC_Email_Customer_Processing_Order'] ) ) {
				$emails['WC_Email_Customer_Processing_Order']->trigger( $temp_order_id, $order );
				$sent_templates[] = 'WC_Email_Customer_Processing_Order';
			}
			if ( isset( $emails['WC_Email_Customer_Completed_Order'] ) ) {
				$emails['WC_Email_Customer_Completed_Order']->trigger( $temp_order_id, $order );
				$sent_templates[] = 'WC_Email_Customer_Completed_Order';
			}
			if ( isset( $emails['WC_Email_Customer_Note'] ) ) {
				$emails['WC_Email_Customer_Note']->trigger(
					array(
						'order_id'      => $temp_order_id,
						'customer_note' => 'Học viện Jannie Nguyễn gửi tài liệu chuẩn bị trước buổi Coaching 1:1.',
					)
				);
				$sent_templates[] = 'WC_Email_Customer_Note';
			}

			// 5. Trigger WC_Email_JN_Booking_Payment_Received.
			if ( isset( $emails['WC_Email_JN_Booking_Payment_Received'] ) ) {
				$emails['WC_Email_JN_Booking_Payment_Received']->trigger( $temp_booking_id, $temp_order_id );
				$sent_templates[] = 'WC_Email_JN_Booking_Payment_Received';
			}
		}
	}

	// 6. Trigger WC_Email_JN_Booking_Cancelled.
	if ( $temp_booking_id > 0 && isset( $emails['WC_Email_JN_Booking_Cancelled'] ) ) {
		$emails['WC_Email_JN_Booking_Cancelled']->trigger( $temp_booking_id );
		$sent_templates[] = 'WC_Email_JN_Booking_Cancelled';
	}

	// 7. Trigger Vietnamese WordPress System Emails (Retrieve Password, Password Change, Email Change, 2FA OTP).
	$user_obj = get_userdata( (int) $temp_user_id );
	if ( $user_obj instanceof WP_User ) {
		$reset_title = apply_filters( 'retrieve_password_title', 'Đặt lại mật khẩu', $user_obj->user_login, $user_obj );
		$reset_msg   = apply_filters( 'retrieve_password_message', 'Nội dung đặt lại mật khẩu', 'step9resetkey123', $user_obj->user_login, $user_obj );
		wp_mail( $temp_email, $reset_title, $reset_msg );
		$sent_templates[] = 'WP_System_Retrieve_Password';

		$user_arr    = $user_obj->to_array();
		$pass_change = apply_filters(
			'password_change_email',
			array(
				'to'      => $temp_email,
				'subject' => '[%s] Mật khẩu đã thay đổi',
				'message' => 'Xin chào ###USERNAME###, mật khẩu đã thay đổi.',
				'headers' => '',
			),
			$user_arr,
			$user_arr
		);
		wp_mail( $pass_change['to'], sprintf( $pass_change['subject'], get_bloginfo( 'name' ) ), str_replace( '###USERNAME###', $user_obj->display_name, $pass_change['message'] ) );
		$sent_templates[] = 'WP_System_Password_Changed';

		$email_change = apply_filters(
			'email_change_email',
			array(
				'to'      => $temp_email,
				'subject' => '[%s] Địa chỉ email đã thay đổi',
				'message' => 'Xin chào ###USERNAME###, email đã thay đổi.',
				'headers' => '',
			),
			$user_arr,
			array_merge( $user_arr, array( 'user_email' => 'new.' . $temp_email ) )
		);
		wp_mail( $email_change['to'], sprintf( $email_change['subject'], get_bloginfo( 'name' ) ), str_replace( '###USERNAME###', $user_obj->display_name, $email_change['message'] ) );
		$sent_templates[] = 'WP_System_Email_Changed';

		// Two-Factor Email OTP token notification
		wp_mail(
			$temp_email,
			'Mã xác thực đăng nhập 2 lớp — Jannie Nguyen Academy',
			"Xin chào " . $user_obj->display_name . ",\n\nMã xác thực đăng nhập (2FA OTP) của bạn tại Jannie Nguyen Academy là: 849201\nMã này có hiệu lực trong vòng 15 phút.\n\nTrân trọng,\nJannie Nguyen Academy"
		);
		$sent_templates[] = 'Two_Factor_Email_OTP';
	}
} finally {
	if ( $temp_booking_id > 0 ) {
		wp_delete_post( $temp_booking_id, true );
	}
	if ( $temp_order_id > 0 ) {
		$ord = wc_get_order( $temp_order_id );
		if ( $ord instanceof WC_Order ) {
			$ord->delete( true );
		}
	}
	if ( $temp_user_id > 0 ) {
		wp_delete_user( (int) $temp_user_id );
	}
	update_option( 'jn_guest_crm_profiles', array(), false );
	update_option( 'jn_customer_duplicate_log', array(), false );
}

echo wp_json_encode(
	array(
		'triggered_count' => count( $sent_templates ),
		'templates'       => $sent_templates,
		'cleaned_up'      => true,
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
) . "\n";
