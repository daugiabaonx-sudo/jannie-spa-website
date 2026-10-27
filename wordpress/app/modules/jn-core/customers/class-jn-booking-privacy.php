<?php
/**
 * WordPress Personal Data Exporter, Eraser & Retention Policy for Customer CRM and `jn_booking` Records.
 *
 * Covers:
 * - Customer CRM profile (`WP_User` + Guest CRM store): Họ tên, SĐT/Zalo, Email,
 *   Loại khách, Tên cơ sở, Tỉnh/Thành, Nguồn, Tags, Ghi chú nội bộ, Consent log.
 * - Coaching bookings (`jn_booking`).
 * - Configurable retention policy for cancelled guest bookings (default off, logged as owner decision ADR-009).
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
	 * Daily cron hook for cancelled guest booking retention cleanup.
	 */
	public const CRON_RETENTION_HOOK = 'jn_daily_cancelled_guest_booking_cleanup';

	/**
	 * Register WordPress personal data exporter, eraser, and retention hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ), 10, 1 );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ), 10, 1 );
		add_action( 'init', array( __CLASS__, 'schedule_retention_cron' ), 30 );
		add_action( self::CRON_RETENTION_HOOK, array( __CLASS__, 'run_cancelled_guest_retention_cleanup' ) );
	}

	/**
	 * Ensure the daily retention cron event is scheduled.
	 *
	 * @return void
	 */
	public static function schedule_retention_cron(): void {
		if ( ! wp_next_scheduled( self::CRON_RETENTION_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_RETENTION_HOOK );
		}
	}

	/**
	 * Register the JN Customer CRM and JN Bookings personal data exporters.
	 *
	 * @param array<string, array<string, mixed>> $exporters Registered exporters.
	 * @return array<string, array<string, mixed>>
	 */
	public static function register_exporter( array $exporters ): array {
		$exporters['jn-customer-crm'] = array(
			'exporter_friendly_name' => 'Hồ sơ Khách hàng CRM (Jannie Nguyễn)',
			'callback'               => array( __CLASS__, 'export_customer_crm_data' ),
		);
		$exporters['jn-bookings']     = array(
			'exporter_friendly_name' => 'Lịch hẹn Coaching & Mentorship (Jannie Nguyễn)',
			'callback'               => array( __CLASS__, 'export_personal_data' ),
		);
		return $exporters;
	}

	/**
	 * Register the JN Customer CRM and JN Bookings personal data erasers.
	 *
	 * @param array<string, array<string, mixed>> $erasers Registered erasers.
	 * @return array<string, array<string, mixed>>
	 */
	public static function register_eraser( array $erasers ): array {
		$erasers['jn-customer-crm'] = array(
			'eraser_friendly_name' => 'Hồ sơ Khách hàng CRM (Jannie Nguyễn)',
			'callback'             => array( __CLASS__, 'erase_customer_crm_data' ),
		);
		$erasers['jn-bookings']     = array(
			'eraser_friendly_name' => 'Lịch hẹn Coaching & Mentorship (Jannie Nguyễn)',
			'callback'             => array( __CLASS__, 'erase_personal_data' ),
		);
		return $erasers;
	}

	/**
	 * Export Customer CRM profile data matching an email address.
	 *
	 * Includes: Họ tên, SĐT/Zalo (+84…), Email, Loại khách, Tên cơ sở, Tỉnh/Thành,
	 * Nguồn, Tags, and Consent log.
	 *
	 * @param string $email_address Customer email address.
	 * @param int    $page          Batch page number.
	 * @return array<string, mixed>
	 */
	public static function export_customer_crm_data( string $email_address, int $page = 1 ): array {
		$clean_email = strtolower( sanitize_email( $email_address ) );
		if ( '' === $clean_email || $page > 1 ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$export_data = array();
		$user        = get_user_by( 'email', $clean_email );

		if ( $user instanceof \WP_User && $user->ID > 0 ) {
			$profile      = JN_Customer_Profile::get_user_profile( (int) $user->ID );
			$export_data[] = self::build_crm_export_item( 'jn-crm-user-' . (int) $user->ID, $profile );
		}

		$guest_key     = 'guest:' . $clean_email;
		$guest_profile = JN_Customer_Profile::get_guest_profile( $guest_key );
		if (
			'' !== $guest_profile['full_name'] ||
			'' !== $guest_profile['phone_e164'] ||
			! empty( $guest_profile['tags'] ) ||
			! empty( $guest_profile['consent_log'] )
		) {
			$export_data[] = self::build_crm_export_item( 'jn-crm-guest-' . md5( $clean_email ), $guest_profile );
		}

		// If neither explicit user profile nor explicit guest CRM store exists, check if customer exists via booking/order aggregation.
		if ( empty( $export_data ) ) {
			$detail = JN_Customer_Service::get_customer_detail( $guest_key );
			if ( is_array( $detail ) && ( ! empty( $detail['bookings'] ) || ! empty( $detail['orders'] ) ) ) {
				$export_data[] = self::build_crm_export_item( 'jn-crm-guest-' . md5( $clean_email ), $detail['profile'] );
			}
		}

		return array(
			'data' => $export_data,
			'done' => true,
		);
	}

	/**
	 * Build a single WP Privacy export item from a CRM profile array.
	 *
	 * @param string               $item_id Unique item identifier.
	 * @param array<string, mixed> $profile Normalized CRM profile array.
	 * @return array<string, mixed>
	 */
	private static function build_crm_export_item( string $item_id, array $profile ): array {
		$consent_entries = array();
		if ( ! empty( $profile['consent_log'] ) && is_array( $profile['consent_log'] ) ) {
			foreach ( $profile['consent_log'] as $entry ) {
				if ( is_array( $entry ) ) {
					$consent_entries[] = sprintf(
						'%s (Phiên bản: %s, Nguồn: %s)',
						(string) ( $entry['timestamp'] ?? '' ),
						(string) ( $entry['policy_version'] ?? '' ),
						(string) ( $entry['source'] ?? '' )
					);
				}
			}
		}

		$tags_list = ! empty( $profile['tags'] ) && is_array( $profile['tags'] )
			? implode( ', ', $profile['tags'] )
			: '';

		$source_label = JN_Customer_Profile::get_source_label( (string) ( $profile['source_type'] ?? '' ) );
		if ( ! empty( $profile['source_detail'] ) ) {
			$source_label .= ' (' . (string) $profile['source_detail'] . ')';
		}

		return array(
			'group_id'          => 'jn_customer_crm',
			'group_label'       => 'Hồ sơ Khách hàng CRM (Jannie Nguyễn)',
			'group_description' => 'Thông tin hồ sơ khách hàng, phân loại học viên/cơ sở, nguồn tiếp cận và lịch sử đồng ý bảo mật.',
			'item_id'           => $item_id,
			'data'              => array(
				array(
					'name'  => 'Họ và tên',
					'value' => (string) ( $profile['full_name'] ?? '' ),
				),
				array(
					'name'  => 'Số điện thoại / Zalo',
					'value' => (string) ( $profile['phone_e164'] ?? '' ),
				),
				array(
					'name'  => 'Địa chỉ Email',
					'value' => (string) ( $profile['email'] ?? '' ),
				),
				array(
					'name'  => 'Loại khách hàng',
					'value' => JN_Customer_Profile::get_customer_type_label( (string) ( $profile['customer_type'] ?? '' ) ),
				),
				array(
					'name'  => 'Tên cơ sở / Spa',
					'value' => (string) ( $profile['business_name'] ?? '' ),
				),
				array(
					'name'  => 'Tỉnh / Thành phố',
					'value' => (string) ( $profile['province_city'] ?? '' ),
				),
				array(
					'name'  => 'Nguồn khách hàng',
					'value' => $source_label,
				),
				array(
					'name'  => 'Nhãn phân loại (Tags)',
					'value' => $tags_list,
				),
				array(
					'name'  => 'Nhật ký đồng ý bảo mật (Consent Log)',
					'value' => ! empty( $consent_entries ) ? implode( ' | ', $consent_entries ) : 'Chưa ghi nhận',
				),
			),
		);
	}

	/**
	 * Erase/anonymize Customer CRM profile fields matching an email address.
	 *
	 * @param string $email_address Customer email address.
	 * @param int    $page          Batch page number.
	 * @return array<string, mixed>
	 */
	public static function erase_customer_crm_data( string $email_address, int $page = 1 ): array {
		$clean_email = strtolower( sanitize_email( $email_address ) );
		if ( '' === $clean_email ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$items_removed = false;
		$user          = get_user_by( 'email', $clean_email );

		if ( $user instanceof \WP_User && $user->ID > 0 ) {
			$user_id   = (int) $user->ID;
			$meta_keys = array(
				JN_Customer_Profile::META_FULL_NAME,
				JN_Customer_Profile::META_PHONE_E164,
				JN_Customer_Profile::META_PHONE_RAW,
				JN_Customer_Profile::META_CUSTOMER_TYPE,
				JN_Customer_Profile::META_BUSINESS_NAME,
				JN_Customer_Profile::META_PROVINCE_CITY,
				JN_Customer_Profile::META_SOURCE_TYPE,
				JN_Customer_Profile::META_SOURCE_DETAIL,
				JN_Customer_Profile::META_TAGS,
				JN_Customer_Profile::META_TAGS_CSV,
				JN_Customer_Profile::META_INTERNAL_NOTES,
				JN_Customer_Profile::META_CONSENT_LOG,
				'billing_phone',
				'billing_company',
				'billing_city',
				'billing_state',
			);

			foreach ( $meta_keys as $meta_key ) {
				delete_user_meta( $user_id, $meta_key );
			}

			$items_removed = true;
		}

		// Remove matching guest CRM profiles.
		$guest_key = 'guest:' . $clean_email;
		if ( JN_Customer_Profile::delete_guest_profile( $guest_key ) ) {
			$items_removed = true;
		}

		// Also check all guest profiles whose stored email matches.
		$all_guests = JN_Customer_Profile::get_all_guest_profiles();
		foreach ( $all_guests as $key => $record ) {
			if ( is_array( $record ) && isset( $record['email'] ) && strtolower( (string) $record['email'] ) === $clean_email ) {
				if ( JN_Customer_Profile::delete_guest_profile( (string) $key ) ) {
					$items_removed = true;
				}
			}
		}

		// Purge customer references from duplicate audit log.
		$dup_logs = get_option( JN_Customer_Service::DUPLICATE_LOG_OPTION, array() );
		if ( is_array( $dup_logs ) && ! empty( $dup_logs ) ) {
			$target_keys = array( $guest_key );
			if ( $user instanceof \WP_User && $user->ID > 0 ) {
				$target_keys[] = 'user:' . (int) $user->ID;
			}
			$filtered = array();
			foreach ( $dup_logs as $entry ) {
				if ( is_array( $entry ) ) {
					$p_key = (string) ( $entry['primary_customer_key'] ?? '' );
					$c_key = (string) ( $entry['conflicting_customer_key'] ?? '' );
					if ( in_array( $p_key, $target_keys, true ) || in_array( $c_key, $target_keys, true ) ) {
						$items_removed = true;
						continue;
					}
				}
				$filtered[] = $entry;
			}
			if ( count( $filtered ) !== count( $dup_logs ) ) {
				update_option( JN_Customer_Service::DUPLICATE_LOG_OPTION, $filtered, false );
			}
		}

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
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
			self::anonymize_booking_post( $booking_id, $anon_email, 'Đã ẩn danh hóa dữ liệu cá nhân theo yêu cầu xóa dữ liệu.' );
			$items_removed = true;
		}

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => count( $booking_ids ) < 50,
		);
	}

	/**
	 * Anonymize a single `jn_booking` post's PII metadata.
	 *
	 * @param int    $booking_id Booking post ID.
	 * @param string $anon_email Anonymized replacement email.
	 * @param string $note       Audit note appended to status timeline.
	 * @return void
	 */
	public static function anonymize_booking_post( int $booking_id, string $anon_email = 'deleted@site.invalid', string $note = '' ): void {
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
			'note'       => '' !== $note ? $note : 'Đã ẩn danh hóa dữ liệu cá nhân.',
		);
		update_post_meta( $booking_id, '_jn_status_timeline', $timeline );
	}

	/**
	 * Execute the retention policy for cancelled guest bookings.
	 *
	 * Default setting (`retention_cancelled_guest_enabled`) is `'no'` (off, logged as owner decision ADR-009).
	 * When enabled (`'yes'`), anonymizes cancelled guest bookings older than `retention_cancelled_guest_days`.
	 *
	 * @return int Number of cancelled guest bookings anonymized.
	 */
	public static function run_cancelled_guest_retention_cleanup(): int {
		$enabled = (string) JN_Booking_Settings::get( 'retention_cancelled_guest_enabled', 'no' );
		if ( 'yes' !== $enabled ) {
			return 0;
		}

		$days = max( 1, (int) JN_Booking_Settings::get( 'retention_cancelled_guest_days', 90 ) );
		$cutoff_gmt = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		$booking_ids = get_posts(
			array(
				'post_type'      => JN_Booking_CPT::POST_TYPE,
				'post_status'    => array( JN_Booking_CPT::STATUS_CANCELLED ),
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'date_query'     => array(
					array(
						'column' => 'post_date_gmt',
						'before' => $cutoff_gmt,
					),
				),
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'     => '_jn_customer_user_id',
						'value'   => 0,
						'compare' => '=',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => '_jn_customer_email',
						'value'   => 'deleted@site.invalid',
						'compare' => '!=',
					),
				),
			)
		);

		$count = 0;
		foreach ( $booking_ids as $booking_id ) {
			$booking_id = (int) $booking_id;
			$email      = strtolower( (string) get_post_meta( $booking_id, '_jn_customer_email', true ) );
			self::anonymize_booking_post(
				$booking_id,
				'deleted@site.invalid',
				sprintf( 'Đã tự động ẩn danh lịch hẹn khách vãng lai đã hủy quá %d ngày (chính sách lưu trữ).', $days )
			);
			if ( '' !== $email && 'deleted@site.invalid' !== $email ) {
				JN_Customer_Profile::delete_guest_profile( 'guest:' . $email );
			}
			++$count;
		}

		return $count;
	}
}
