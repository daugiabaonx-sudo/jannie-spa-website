<?php
/**
 * WordPress Personal Data Exporter & Eraser for `jn_booking` Records.
 *
 * Covers all personal data stored on coaching bookings (`_jn_customer_name`,
 * `_jn_customer_phone`, `_jn_customer_email`, `_jn_goals`, `_jn_consent_*`, `_jn_source`).
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Booking_Privacy
 */
class JN_Booking_Privacy {

	/**
	 * Register WordPress personal data exporter and eraser hooks.
	 *
	 * Confirmed filters in `wp-admin/includes/privacy-tools.php`.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ), 10, 1 );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ), 10, 1 );
	}

	/**
	 * Register the JN Bookings personal data exporter.
	 *
	 * @param array<string, array<string, mixed>> $exporters Registered exporters.
	 * @return array<string, array<string, mixed>>
	 */
	public static function register_exporter( array $exporters ): array {
		$exporters['jn-bookings'] = array(
			'exporter_friendly_name' => 'Lịch hẹn Coaching & Mentorship (Jannie Nguyễn)',
			'callback'               => array( __CLASS__, 'export_personal_data' ),
		);
		return $exporters;
	}

	/**
	 * Register the JN Bookings personal data eraser.
	 *
	 * @param array<string, array<string, mixed>> $erasers Registered erasers.
	 * @return array<string, array<string, mixed>>
	 */
	public static function register_eraser( array $erasers ): array {
		$erasers['jn-bookings'] = array(
			'eraser_friendly_name' => 'Lịch hẹn Coaching & Mentorship (Jannie Nguyễn)',
			'callback'             => array( __CLASS__, 'erase_personal_data' ),
		);
		return $erasers;
	}

	/**
	 * Query booking IDs associated with an email address (or corresponding user ID).
	 *
	 * @param string $email_address Email address to look up.
	 * @param int    $page          Batch page number (1-indexed).
	 * @return int[]
	 */
	public static function get_booking_ids_for_email( string $email_address, int $page = 1 ): array {
		$clean_email = strtolower( sanitize_email( $email_address ) );
		if ( '' === $clean_email ) {
			return array();
		}

		$meta_or = array(
			array(
				'key'   => '_jn_customer_email',
				'value' => $clean_email,
			),
		);

		$user = get_user_by( 'email', $clean_email );
		if ( $user instanceof \WP_User && $user->ID > 0 ) {
			$meta_or['relation'] = 'OR';
			$meta_or[]           = array(
				'key'   => '_jn_customer_user_id',
				'value' => (int) $user->ID,
				'type'  => 'NUMERIC',
			);
		}

		$posts = get_posts(
			array(
				'post_type'      => JN_Booking_CPT::POST_TYPE,
				'post_status'    => array_keys( JN_Booking_CPT::get_statuses() ),
				'posts_per_page' => 50,
				'paged'          => max( 1, $page ),
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'meta_query'     => array( $meta_or ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		);

		return array_map( 'intval', $posts );
	}

	/**
	 * Export personal data for bookings matching an email address.
	 *
	 * @param string $email_address Customer email address.
	 * @param int    $page          Batch page number.
	 * @return array<string, mixed>
	 */
	public static function export_personal_data( string $email_address, int $page = 1 ): array {
		$booking_ids = self::get_booking_ids_for_email( $email_address, $page );
		$export_data = array();

		foreach ( $booking_ids as $booking_id ) {
			$post = get_post( $booking_id );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$code            = (string) get_post_meta( $booking_id, '_jn_booking_code', true );
			$name            = (string) get_post_meta( $booking_id, '_jn_customer_name', true );
			$phone           = (string) get_post_meta( $booking_id, '_jn_customer_phone', true );
			$email           = (string) get_post_meta( $booking_id, '_jn_customer_email', true );
			$pkg_label       = (string) get_post_meta( $booking_id, '_jn_package_option_label', true );
			$format_label    = (string) get_post_meta( $booking_id, '_jn_format_label', true );
			$window_label    = (string) get_post_meta( $booking_id, '_jn_preferred_window_label', true );
			$goals           = (string) get_post_meta( $booking_id, '_jn_goals', true );
			$confirmed_start = (string) get_post_meta( $booking_id, '_jn_confirmed_start', true );
			$consent_ts      = (string) get_post_meta( $booking_id, '_jn_consent_timestamp', true );
			$consent_ver     = (string) get_post_meta( $booking_id, '_jn_consent_policy_version', true );
			$order_id        = (int) get_post_meta( $booking_id, '_jn_order_id', true );
			$source          = get_post_meta( $booking_id, '_jn_source', true );
			$landing_url     = is_array( $source ) && isset( $source['landing_url'] ) ? (string) $source['landing_url'] : '';

			$item_data = array(
				array(
					'name'  => 'Mã lịch hẹn',
					'value' => '' !== $code ? $code : (string) $booking_id,
				),
				array(
					'name'  => 'Trạng thái',
					'value' => JN_Booking_CPT::get_status_label( $post->post_status ),
				),
				array(
					'name'  => 'Họ và tên',
					'value' => $name,
				),
				array(
					'name'  => 'Số điện thoại / Zalo',
					'value' => $phone,
				),
				array(
					'name'  => 'Địa chỉ Email',
					'value' => $email,
				),
				array(
					'name'  => 'Gói cố vấn đăng ký',
					'value' => $pkg_label,
				),
				array(
					'name'  => 'Hình thức gặp',
					'value' => $format_label,
				),
				array(
					'name'  => 'Khung giờ mong muốn',
					'value' => $window_label,
				),
				array(
					'name'  => 'Mục tiêu / Thách thức chia sẻ',
					'value' => $goals,
				),
				array(
					'name'  => 'Thời gian hẹn xác nhận (Asia/Ho_Chi_Minh)',
					'value' => '' !== $confirmed_start ? $confirmed_start : 'Chưa xác nhận',
				),
				array(
					'name'  => 'Đồng ý Chính sách bảo mật',
					'value' => $consent_ts . ( '' !== $consent_ver ? ' — Phiên bản: ' . $consent_ver : '' ),
				),
				array(
					'name'  => 'Trang gửi yêu cầu',
					'value' => $landing_url,
				),
				array(
					'name'  => 'Đơn hàng WooCommerce liên kết',
					'value' => $order_id > 0 ? '#' . $order_id : 'Chưa tạo đơn',
				),
			);

			$export_data[] = array(
				'group_id'          => 'jn_bookings',
				'group_label'       => 'Lịch hẹn Coaching & Mentorship',
				'group_description' => 'Dữ liệu yêu cầu tư vấn và lịch hẹn Coaching 1:1 tại Jannie Nguyen Academy.',
				'item_id'           => 'jn-booking-' . $booking_id,
				'data'              => $item_data,
			);
		}

		return array(
			'data' => $export_data,
			'done' => count( $booking_ids ) < 50,
		);
	}

	/**
	 * Erase/anonymize personal data for bookings matching an email address.
	 *
	 * @param string $email_address Customer email address.
	 * @param int    $page          Batch page number.
	 * @return array<string, mixed>
	 */
	public static function erase_personal_data( string $email_address, int $page = 1 ): array {
		$booking_ids    = self::get_booking_ids_for_email( $email_address, $page );
		$items_removed  = false;
		$items_retained = false;
		$messages       = array();

		$anon_email = function_exists( 'wp_privacy_anonymize_data' )
			? wp_privacy_anonymize_data( 'email', $email_address )
			: 'deleted@site.invalid';

		foreach ( $booking_ids as $booking_id ) {
			update_post_meta( $booking_id, '_jn_customer_user_id', 0 );
			update_post_meta( $booking_id, '_jn_customer_name', 'Khách hàng đã ẩn danh' );
			update_post_meta( $booking_id, '_jn_customer_phone', '' );
			update_post_meta( $booking_id, '_jn_customer_phone_raw', '' );
			update_post_meta( $booking_id, '_jn_customer_email', $anon_email );
			update_post_meta( $booking_id, '_jn_goals', '[Đã xóa theo yêu cầu quyền riêng tư]' );
			update_post_meta( $booking_id, '_jn_source', array() );
			update_post_meta( $booking_id, '_jn_meeting_link', '' );

			$timeline = get_post_meta( $booking_id, '_jn_status_timeline', true );
			if ( ! is_array( $timeline ) ) {
				$timeline = array();
			}
			$timeline[] = array(
				'actor_id'   => get_current_user_id(),
				'actor_name' => 'Hệ thống Quyền riêng tư',
				'timestamp'  => wp_date( 'c' ),
				'from'       => (string) get_post_status( $booking_id ),
				'to'         => (string) get_post_status( $booking_id ),
				'note'       => 'Đã ẩn danh hóa dữ liệu cá nhân theo yêu cầu xóa dữ liệu.',
			);
			update_post_meta( $booking_id, '_jn_status_timeline', $timeline );

			$items_removed = true;
		}

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => count( $booking_ids ) < 50,
		);
	}
}
