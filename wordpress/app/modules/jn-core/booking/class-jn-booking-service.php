<?php
/**
 * Core Booking Engine Service, State Machine, Overlap Detector, Order Sync & Mentorship Manager.
 *
 * Implements:
 * - Stateless HMAC form tokens (works on page-cached HTML, enforces min fill time & expiry).
 * - Hashed IP and phone/email rate limiting (zero PII in option/transient keys).
 * - Public intake validation & creation (`Chờ xác nhận`).
 * - Server-enforced state machine (`Chờ xác nhận` -> `Đã xác nhận lịch` -> `Hoàn thành` | `Vắng mặt`; any non-final -> `Đã hủy`).
 * - Schedule overlap detection across confirmed bookings (`Asia/Ho_Chi_Minh`).
 * - WooCommerce HPOS pending order creation & bidirectional linking on confirmation.
 * - Order payment status derivation & sync (`Đã thanh toán` notification; unpaid cancellation vs paid manual refund flag).
 * - Mentorship 12-session linking to a single enrolment order.
 * - Optional monthly capacity per package (default off) + waitlist flagging.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Booking_Service
 */
class JN_Booking_Service {

	/**
	 * Minimum form fill time in seconds to block automated bots.
	 */
	const MIN_FILL_SECONDS = 2;

	/**
	 * Default form token TTL in seconds (24 hours for cached HTML / 15 min for fresh tokens).
	 */
	const TOKEN_TTL_SECONDS = 86400;

	/**
	 * Rate limit window in seconds (15 minutes).
	 */
	const RATE_LIMIT_WINDOW = 900;

	/**
	 * Max submissions per hashed IP within the rate limit window.
	 */
	const MAX_PER_IP = 5;

	/**
	 * Max submissions per hashed phone or email within the rate limit window.
	 */
	const MAX_PER_CONTACT = 3;

	/**
	 * Max sessions per Master Mentorship enrolment.
	 */
	const MENTORSHIP_MAX_SESSIONS = 12;

	/**
	 * Register WooCommerce order status synchronization hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'handle_order_paid' ), 20, 1 );
		add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'handle_order_paid' ), 20, 1 );
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'handle_order_paid' ), 20, 1 );
	}

	/**
	 * Issue a stateless signed form token encoding the issue timestamp.
	 *
	 * Safe for page-cached HTML (when issued at render) and fresh REST token handshakes
	 * on first user interaction.
	 *
	 * @param int|null $issued_at Optional Unix timestamp (used in tests). Defaults to `time()`.
	 * @return string Signed token `<timestamp>.<nonce>.<hmac>`.
	 */
	public static function issue_form_token( ?int $issued_at = null ): string {
		$ts      = null !== $issued_at ? $issued_at : time();
		$rand    = substr( wp_generate_password( 12, false, false ), 0, 10 );
		$payload = $ts . '.' . $rand;
		$sig     = hash_hmac( 'sha256', $payload, wp_salt( 'nonce' ) );

		return $payload . '.' . $sig;
	}

	/**
	 * Verify a signed form token for authenticity, minimum fill time, and expiration.
	 *
	 * @param string $token            Signed token string.
	 * @param int    $min_fill_seconds Minimum fill time in seconds (default 3).
	 * @param int    $ttl_seconds      Maximum token age in seconds (default 86400).
	 * @return true|\WP_Error
	 */
	public static function verify_form_token(
		string $token,
		int $min_fill_seconds = self::MIN_FILL_SECONDS,
		int $ttl_seconds = self::TOKEN_TTL_SECONDS
	) {
		$parts = explode( '.', trim( $token ) );
		if ( 3 !== count( $parts ) || ! ctype_digit( $parts[0] ) || '' === $parts[1] || '' === $parts[2] ) {
			return new \WP_Error(
				'expired_token',
				'Phiên biểu mẫu không hợp lệ hoặc đã hết hạn. Vui lòng thử gửi lại.',
				array( 'status' => 400 )
			);
		}

		$issued_ts = (int) $parts[0];
		$rand      = $parts[1];
		$sig       = $parts[2];
		$payload   = $issued_ts . '.' . $rand;
		$expected  = hash_hmac( 'sha256', $payload, wp_salt( 'nonce' ) );

		if ( ! hash_equals( $expected, $sig ) ) {
			return new \WP_Error(
				'expired_token',
				'Phiên biểu mẫu không hợp lệ hoặc đã hết hạn. Vui lòng thử gửi lại.',
				array( 'status' => 400 )
			);
		}

		$now = time();
		$age = $now - $issued_ts;

		if ( $age > $ttl_seconds ) {
			return new \WP_Error(
				'expired_token',
				'Phiên biểu mẫu đã hết hạn. Vui lòng tải lại hoặc gửi lại biểu mẫu.',
				array( 'status' => 400 )
			);
		}

		if ( $age < $min_fill_seconds ) {
			return new \WP_Error(
				'too_fast_submit',
				'Hồ sơ được gửi quá nhanh. Vui lòng kiểm tra lại thông tin trước khi gửi.',
				array( 'status' => 400 )
			);
		}

		return true;
	}

	/**
	 * Build a privacy-safe transient key for rate limiting (never stores raw IP, phone, or email).
	 *
	 * @param string $type  Bucket type (`ip`, `phone`, `email`).
	 * @param string $value Raw identifier value.
	 * @return string
	 */
	public static function get_rate_limit_key( string $type, string $value ): string {
		$hash = substr( hash_hmac( 'sha256', strtolower( trim( $value ) ), wp_salt( 'auth' ) ), 0, 24 );
		return 'jn_rl_' . sanitize_key( $type ) . '_' . $hash;
	}

	/**
	 * Check and optionally increment rate limits per hashed IP and per hashed phone/email.
	 *
	 * @param string $client_ip        Client IP address.
	 * @param string $normalized_phone Normalized `+84...` phone.
	 * @param string $email            Normalized lowercase email.
	 * @param bool   $increment        Whether to increment counters when allowed.
	 * @return true|\WP_Error
	 */
	public static function check_rate_limits(
		string $client_ip,
		string $normalized_phone,
		string $email,
		bool $increment = true
	) {
		$ip_key    = '' !== $client_ip ? self::get_rate_limit_key( 'ip', $client_ip ) : '';
		$phone_key = '' !== $normalized_phone ? self::get_rate_limit_key( 'phone', $normalized_phone ) : '';
		$email_key = '' !== $email ? self::get_rate_limit_key( 'email', $email ) : '';

		$ip_count    = '' !== $ip_key ? (int) get_transient( $ip_key ) : 0;
		$phone_count = '' !== $phone_key ? (int) get_transient( $phone_key ) : 0;
		$email_count = '' !== $email_key ? (int) get_transient( $email_key ) : 0;

		if ( $ip_count >= self::MAX_PER_IP || $phone_count >= self::MAX_PER_CONTACT || $email_count >= self::MAX_PER_CONTACT ) {
			return new \WP_Error(
				'rate_limited',
				'Bạn đã gửi quá nhiều yêu cầu trong thời gian ngắn. Vui lòng đợi 15 phút trước khi thử lại.',
				array( 'status' => 429 )
			);
		}

		if ( $increment ) {
			if ( '' !== $ip_key ) {
				set_transient( $ip_key, $ip_count + 1, self::RATE_LIMIT_WINDOW );
			}
			if ( '' !== $phone_key ) {
				set_transient( $phone_key, $phone_count + 1, self::RATE_LIMIT_WINDOW );
			}
			if ( '' !== $email_key ) {
				set_transient( $email_key, $email_count + 1, self::RATE_LIMIT_WINDOW );
			}
		}

		return true;
	}

	/**
	 * Reset rate limit counters for a specific IP, phone, and email (used in automated tests).
	 *
	 * @param string $client_ip        Client IP.
	 * @param string $normalized_phone Normalized phone.
	 * @param string $email            Email address.
	 * @return void
	 */
	public static function reset_rate_limits( string $client_ip, string $normalized_phone, string $email ): void {
		if ( '' !== $client_ip ) {
			delete_transient( self::get_rate_limit_key( 'ip', $client_ip ) );
		}
		if ( '' !== $normalized_phone ) {
			delete_transient( self::get_rate_limit_key( 'phone', $normalized_phone ) );
		}
		if ( '' !== $email ) {
			delete_transient( self::get_rate_limit_key( 'email', $email ) );
		}
	}

	/**
	 * Count active (non-cancelled) root bookings for a package in the current month (`Asia/Ho_Chi_Minh`).
	 *
	 * @param string $package_key Package key (`30-phut`, `60-phut`, `mentorship-3-thang`).
	 * @return int
	 */
	public static function get_monthly_package_count( string $package_key ): int {
		$tz          = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
		$month_start = new \DateTimeImmutable( 'first day of this month 00:00:00', $tz );
		$month_end   = new \DateTimeImmutable( 'last day of this month 23:59:59', $tz );

		$ids = get_posts(
			array(
				'post_type'      => JN_Booking_CPT::POST_TYPE,
				'post_status'    => array(
					JN_Booking_CPT::STATUS_PENDING,
					JN_Booking_CPT::STATUS_CONFIRMED,
					JN_Booking_CPT::STATUS_COMPLETED,
					JN_Booking_CPT::STATUS_NO_SHOW,
				),
				'post_parent'    => 0,
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'date_query'     => array(
					array(
						'after'     => $month_start->format( 'Y-m-d H:i:s' ),
						'before'    => $month_end->format( 'Y-m-d H:i:s' ),
						'inclusive' => true,
					),
				),
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_jn_package_key',
						'value' => sanitize_key( $package_key ),
					),
				),
			)
		);

		return count( $ids );
	}

	/**
	 * Determine whether a package has reached its optional monthly capacity limit.
	 *
	 * @param string $package_key Package key (`30-phut`, `60-phut`, `mentorship-3-thang`).
	 * @return bool
	 */
	public static function is_package_at_capacity( string $package_key ): bool {
		$enabled = (string) JN_Booking_Settings::get( 'capacity_enabled', 'no' );
		if ( 'yes' !== $enabled ) {
			return false;
		}

		$limits = (array) JN_Booking_Settings::get( 'capacity_limits', array() );
		$limit  = isset( $limits[ $package_key ] ) ? (int) $limits[ $package_key ] : 0;
		if ( $limit <= 0 ) {
			return false;
		}

		return self::get_monthly_package_count( $package_key ) >= $limit;
	}

	/**
	 * Validate and create a new booking from a public form submission.
	 *
	 * @param array<string, mixed> $payload Form submission data.
	 * @param array<string, mixed> $context Request context (`ip`, `landing_url`, `referrer`, `skip_token_check`).
	 * @return array<string, mixed>|\WP_Error Created booking summary or WP_Error.
	 */
	public static function submit_public_booking( array $payload, array $context = array() ) {
		// 1. Honeypot check (must be strictly empty).
		$honeypot = isset( $payload['jn_website_hp'] ) ? (string) $payload['jn_website_hp'] : '';
		if ( '' !== trim( $honeypot ) ) {
			return new \WP_Error(
				'honeypot_filled',
				'Yêu cầu không hợp lệ.',
				array( 'status' => 400 )
			);
		}

		// 2. Signed token check (verifies authenticity, min fill time >= 3s, and non-expired TTL).
		if ( empty( $context['skip_token_check'] ) ) {
			$token        = isset( $payload['_jn_form_token'] ) ? (string) $payload['_jn_form_token'] : '';
			$min_fill     = isset( $context['min_fill_seconds'] ) ? (int) $context['min_fill_seconds'] : self::MIN_FILL_SECONDS;
			$token_result = self::verify_form_token( $token, $min_fill );
			if ( is_wp_error( $token_result ) ) {
				return $token_result;
			}
		}

		// 3. Required fields & format validation.
		$full_name = sanitize_text_field( (string) ( $payload['full_name'] ?? '' ) );
		if ( mb_strlen( $full_name ) < 2 ) {
			return new \WP_Error(
				'missing_name',
				'Vui lòng nhập đầy đủ Họ và Tên của bạn.',
				array( 'status' => 400 )
			);
		}

		$raw_phone        = sanitize_text_field( (string) ( $payload['phone_number'] ?? '' ) );
		$normalized_phone = JN_Customer_Service::normalize_vn_phone( $raw_phone );
		if ( '' === $normalized_phone ) {
			return new \WP_Error(
				'invalid_phone',
				'Số điện thoại không hợp lệ. Vui lòng nhập số điện thoại Việt Nam (ví dụ: 090 123 4567 hoặc +84 90 123 4567).',
				array( 'status' => 400 )
			);
		}

		$raw_email = sanitize_text_field( (string) ( $payload['email_address'] ?? '' ) );
		$email     = strtolower( sanitize_email( $raw_email ) );
		if ( '' === $email || ! is_email( $email ) || $email !== strtolower( trim( $raw_email ) ) ) {
			return new \WP_Error(
				'invalid_email',
				'Địa chỉ email không hợp lệ. Vui lòng kiểm tra lại.',
				array( 'status' => 400 )
			);
		}

		// 4. Privacy consent checkbox check.
		$consent_raw = $payload['privacy_consent'] ?? '';
		$consented   = ( '1' === (string) $consent_raw || 'yes' === (string) $consent_raw || true === $consent_raw || 'on' === (string) $consent_raw );
		if ( ! $consented ) {
			return new \WP_Error(
				'missing_consent',
				'Vui lòng đồng ý với Chính sách bảo mật trước khi gửi yêu cầu xếp lịch.',
				array( 'status' => 400 )
			);
		}

		// 5. Bookable package product validation.
		$raw_package = sanitize_text_field( (string) ( $payload['package_code'] ?? '' ) );
		$product     = JN_Coaching_Packages::get_product_by_key( $raw_package );
		if ( ! $product instanceof \WC_Product || ! JN_Coaching_Packages::is_coaching_package( $product ) ) {
			return new \WP_Error(
				'invalid_package',
				'Gói cố vấn đã chọn không hợp lệ hoặc không hỗ trợ đặt lịch.',
				array( 'status' => 400 )
			);
		}

		// 6. Rate limits per hashed IP and per hashed phone/email.
		$client_ip  = isset( $context['ip'] ) ? (string) $context['ip'] : self::get_client_ip();
		$rate_check = self::check_rate_limits( $client_ip, $normalized_phone, $email, true );
		if ( is_wp_error( $rate_check ) ) {
			return $rate_check;
		}

		// 7. Format, preferred window, and goals.
		$formats      = (array) JN_Booking_Settings::get( 'formats', array() );
		$format_key   = sanitize_key( (string) ( $payload['coaching_format'] ?? 'online' ) );
		if ( ! isset( $formats[ $format_key ] ) ) {
			$format_key = 'online';
		}
		$format_label = $formats[ $format_key ] ?? 'Online Trực Tuyến (Zoom High-Res)';

		$windows      = (array) JN_Booking_Settings::get( 'time_windows', array() );
		$window_key   = sanitize_key( (string) ( $payload['time_preference'] ?? 'morning' ) );
		if ( ! isset( $windows[ $window_key ] ) ) {
			$window_key = 'morning';
		}
		$window_label = $windows[ $window_key ] ?? 'Buổi sáng (09:00 - 11:30)';

		$goals = sanitize_textarea_field( (string) ( $payload['notes'] ?? '' ) );

		// 8. Package details & capacity check.
		$pkg_key        = (string) $product->get_meta( '_jn_package_key', true );
		$pkg_title      = $product->get_name();
		$pkg_form_label = (string) $product->get_meta( '_jn_form_label', true );
		if ( '' === $pkg_form_label ) {
			$pkg_form_label = $pkg_title;
		}
		$raw_duration = (string) $product->get_meta( '_jn_duration', true );
		$duration_min = is_numeric( $raw_duration ) ? max( 15, (int) $raw_duration ) : 60;
		$total_sess   = max( 1, (int) $product->get_meta( '_jn_sessions', true ) );
		$is_waitlist  = self::is_package_at_capacity( $pkg_key ) ? 'yes' : 'no';

		// 9. Customer upsert (respects AUTO-CREATE = no).
		$customer_user_id = JN_Customer_Service::upsert_customer_for_booking(
			array(
				'name'  => $full_name,
				'email' => $email,
				'phone' => $normalized_phone,
			)
		);

		// 10. Create the `jn_booking` post in `Chờ xác nhận` (`jn_pending`).
		$post_id = wp_insert_post(
			array(
				'post_type'   => JN_Booking_CPT::POST_TYPE,
				'post_status' => JN_Booking_CPT::STATUS_PENDING,
				'post_title'  => 'JN-PENDING',
				'post_author' => $customer_user_id > 0 ? $customer_user_id : 0,
				'post_parent' => 0,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$booking_id   = (int) $post_id;
		$tz           = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
		$now_vn       = new \DateTimeImmutable( 'now', $tz );
		$booking_code = sprintf( 'JN-%s-%04d', $now_vn->format( 'Ym' ), $booking_id );

		wp_update_post(
			array(
				'ID'         => $booking_id,
				'post_title' => $booking_code,
			)
		);

		$privacy_meta = JN_Booking_Settings::get_privacy_metadata();
		$source_data  = self::extract_source_metadata( $payload, $context );

		update_post_meta( $booking_id, '_jn_booking_code', $booking_code );
		update_post_meta( $booking_id, '_jn_customer_user_id', $customer_user_id );
		update_post_meta( $booking_id, '_jn_customer_name', $full_name );
		update_post_meta( $booking_id, '_jn_customer_phone_raw', $raw_phone );
		update_post_meta( $booking_id, '_jn_customer_phone', $normalized_phone );
		update_post_meta( $booking_id, '_jn_customer_email', $email );

		update_post_meta( $booking_id, '_jn_package_product_id', (int) $product->get_id() );
		update_post_meta( $booking_id, '_jn_package_key', $pkg_key );
		update_post_meta( $booking_id, '_jn_package_title', $pkg_title );
		update_post_meta( $booking_id, '_jn_package_option_label', $pkg_form_label );
		update_post_meta( $booking_id, '_jn_format', $format_key );
		update_post_meta( $booking_id, '_jn_format_label', $format_label );
		update_post_meta( $booking_id, '_jn_preferred_window', $window_key );
		update_post_meta( $booking_id, '_jn_preferred_window_label', $window_label );
		update_post_meta( $booking_id, '_jn_goals', $goals );
		update_post_meta( $booking_id, '_jn_is_waitlist', $is_waitlist );

		update_post_meta( $booking_id, '_jn_consent_given', 'yes' );
		update_post_meta( $booking_id, '_jn_consent_timestamp', $now_vn->format( 'c' ) );
		update_post_meta( $booking_id, '_jn_consent_policy_page_id', (int) $privacy_meta['page_id'] );
		update_post_meta( $booking_id, '_jn_consent_policy_url', (string) $privacy_meta['url'] );
		update_post_meta( $booking_id, '_jn_consent_policy_version', (string) $privacy_meta['version'] );
		update_post_meta( $booking_id, '_jn_consent_text_snapshot', (string) $privacy_meta['consent_text'] );

		update_post_meta( $booking_id, '_jn_source', $source_data );
		update_post_meta( $booking_id, '_jn_duration_minutes', $duration_min );
		update_post_meta( $booking_id, '_jn_session_number', 1 );
		update_post_meta( $booking_id, '_jn_total_sessions', $total_sess );
		update_post_meta( $booking_id, '_jn_order_id', 0 );
		update_post_meta( $booking_id, '_jn_refund_flagged', 'no' );
		update_post_meta( $booking_id, '_jn_staff_notes', '' );

		$timeline = array(
			array(
				'actor_id'   => $customer_user_id,
				'actor_name' => $full_name . ' (Khách hàng)',
				'timestamp'  => $now_vn->format( 'c' ),
				'from'       => 'new',
				'to'         => JN_Booking_CPT::STATUS_PENDING,
				'note'       => 'yes' === $is_waitlist
					? 'Khách hàng gửi yêu cầu xếp lịch (Danh sách chờ tháng).'
					: 'Khách hàng gửi yêu cầu xếp lịch từ biểu mẫu trực tuyến.',
			),
		);
		update_post_meta( $booking_id, '_jn_status_timeline', $timeline );

		/**
		 * Fires immediately after a new public booking request is created.
		 *
		 * @param int $booking_id Created booking post ID.
		 */
		do_action( 'jn_booking_created', $booking_id );

		return array(
			'booking_id'   => $booking_id,
			'booking_code' => $booking_code,
			'status'       => JN_Booking_CPT::STATUS_PENDING,
			'status_label' => JN_Booking_CPT::get_status_label( JN_Booking_CPT::STATUS_PENDING ),
			'is_waitlist'  => 'yes' === $is_waitlist,
			'waitlist_msg' => 'yes' === $is_waitlist ? (string) JN_Booking_Settings::get( 'waitlist_note', JN_Booking_Settings::DEFAULT_WAITLIST_NOTE ) : '',
			'message_lead' => JN_Booking_Settings::SUCCESS_LEAD,
			'message_body' => JN_Booking_Settings::SUCCESS_BODY,
			'message'      => JN_Booking_Settings::SUCCESS_TEXT,
		);
	}

	/**
	 * Check whether a status transition `from -> to` is permitted by the server state machine.
	 *
	 * Allowed transitions:
	 * - `jn_pending` -> `jn_confirmed` | `jn_cancelled`
	 * - `jn_confirmed` -> `jn_completed` | `jn_no_show` | `jn_cancelled`
	 * - Any final status (`jn_completed`, `jn_no_show`, `jn_cancelled`) -> none.
	 *
	 * @param string $from_status Current status slug.
	 * @param string $to_status   Target status slug.
	 * @return bool
	 */
	public static function is_transition_allowed( string $from_status, string $to_status ): bool {
		if ( $from_status === $to_status ) {
			return false;
		}

		$allowed_map = array(
			JN_Booking_CPT::STATUS_PENDING   => array(
				JN_Booking_CPT::STATUS_CONFIRMED,
				JN_Booking_CPT::STATUS_CANCELLED,
			),
			JN_Booking_CPT::STATUS_CONFIRMED => array(
				JN_Booking_CPT::STATUS_COMPLETED,
				JN_Booking_CPT::STATUS_NO_SHOW,
				JN_Booking_CPT::STATUS_CANCELLED,
			),
			JN_Booking_CPT::STATUS_COMPLETED => array(),
			JN_Booking_CPT::STATUS_NO_SHOW   => array(),
			JN_Booking_CPT::STATUS_CANCELLED => array(),
		);

		if ( ! isset( $allowed_map[ $from_status ] ) ) {
			return false;
		}

		return in_array( $to_status, $allowed_map[ $from_status ], true );
	}

	/**
	 * Find confirmed bookings whose `[start_ts, end_ts)` interval overlaps `[start_ts, end_ts)`.
	 *
	 * @param int $start_ts           UTC start timestamp of candidate slot.
	 * @param int $end_ts             UTC end timestamp of candidate slot.
	 * @param int $exclude_booking_id Booking ID to exclude from comparison.
	 * @return array<int, array<string, mixed>> Array of conflicting booking summaries.
	 */
	public static function find_overlapping_bookings( int $start_ts, int $end_ts, int $exclude_booking_id = 0 ): array {
		if ( $start_ts <= 0 || $end_ts <= $start_ts ) {
			return array();
		}

		$candidate_ids = get_posts(
			array(
				'post_type'      => JN_Booking_CPT::POST_TYPE,
				'post_status'    => array( JN_Booking_CPT::STATUS_CONFIRMED ),
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'     => '_jn_confirmed_start_ts',
						'value'   => $end_ts,
						'compare' => '<',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => '_jn_confirmed_end_ts',
						'value'   => $start_ts,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		$conflicts = array();
		foreach ( $candidate_ids as $cid ) {
			$cid = (int) $cid;
			if ( $cid === $exclude_booking_id ) {
				continue;
			}
			$conflicts[] = array(
				'booking_id'       => $cid,
				'booking_code'     => (string) get_post_meta( $cid, '_jn_booking_code', true ),
				'customer_name'    => (string) get_post_meta( $cid, '_jn_customer_name', true ),
				'package_title'    => (string) get_post_meta( $cid, '_jn_package_title', true ),
				'confirmed_start'  => (string) get_post_meta( $cid, '_jn_confirmed_start', true ),
				'duration_minutes' => (int) get_post_meta( $cid, '_jn_duration_minutes', true ),
			);
		}

		return $conflicts;
	}

	/**
	 * Transition a booking to a new status with full server-side validation, scheduling,
	 * WooCommerce HPOS order creation/linking, and timeline auditing.
	 *
	 * @param int                  $booking_id Booking post ID.
	 * @param string               $to_status  Target status slug.
	 * @param array<string, mixed> $args       Scheduling/order/actor arguments.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function transition_status( int $booking_id, string $to_status, array $args = array() ) {
		$actor_id      = isset( $args['actor_id'] ) ? (int) $args['actor_id'] : get_current_user_id();
		$system_action = ! empty( $args['system_action'] );

		if ( ! $system_action && ! JN_Booking_Roles::user_can_manage_bookings( $actor_id ) ) {
			return new \WP_Error(
				'forbidden_booking_action',
				'Bạn không có quyền thực hiện thao tác quản lý lịch hẹn này.',
				array( 'status' => 403 )
			);
		}

		$post = get_post( $booking_id );
		if ( ! $post instanceof \WP_Post || JN_Booking_CPT::POST_TYPE !== $post->post_type ) {
			return new \WP_Error(
				'booking_not_found',
				'Không tìm thấy hồ sơ lịch hẹn.',
				array( 'status' => 404 )
			);
		}

		$from_status = (string) $post->post_status;
		if ( ! self::is_transition_allowed( $from_status, $to_status ) ) {
			return new \WP_Error(
				'invalid_status_transition',
				sprintf(
					'Không thể chuyển trạng thái từ "%s" sang "%s".',
					JN_Booking_CPT::get_status_label( $from_status ),
					JN_Booking_CPT::get_status_label( $to_status )
				),
				array( 'status' => 400 )
			);
		}

		$overlaps = array();
		$order_id = (int) get_post_meta( $booking_id, '_jn_order_id', true );

		// Handle confirmation requirements (`Chờ xác nhận` -> `Đã xác nhận lịch`).
		if ( JN_Booking_CPT::STATUS_CONFIRMED === $to_status ) {
			$sched_result = self::apply_confirmation_schedule( $booking_id, $args );
			if ( is_wp_error( $sched_result ) ) {
				return $sched_result;
			}
			$overlaps = $sched_result['overlaps'];

			// Create or link the pending WooCommerce order.
			$order_result = self::ensure_order_for_confirmed_booking( $booking_id, $args );
			if ( is_wp_error( $order_result ) ) {
				return $order_result;
			}
			$order_id = (int) $order_result;
		}

		// Handle cancellation rules (`-> Đã hủy`).
		if ( JN_Booking_CPT::STATUS_CANCELLED === $to_status ) {
			self::handle_booking_cancellation_order_policy( $booking_id );
		}

		wp_update_post(
			array(
				'ID'          => $booking_id,
				'post_status' => $to_status,
			)
		);

		$tz         = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
		$now_vn     = new \DateTimeImmutable( 'now', $tz );
		$actor_name = 'Hệ thống';
		if ( $actor_id > 0 ) {
			$u = get_userdata( $actor_id );
			if ( $u instanceof \WP_User ) {
				$actor_name = $u->display_name . ' (#' . $actor_id . ')';
			}
		}

		$note = isset( $args['note'] ) ? sanitize_text_field( (string) $args['note'] ) : '';
		if ( '' === $note && ! empty( $overlaps ) ) {
			$conflict_codes = wp_list_pluck( $overlaps, 'booking_code' );
			$note           = 'Xác nhận lịch (có cảnh báo trùng khung giờ với: ' . implode( ', ', $conflict_codes ) . ').';
		}

		self::append_timeline_entry(
			$booking_id,
			array(
				'actor_id'   => $actor_id,
				'actor_name' => $actor_name,
				'timestamp'  => $now_vn->format( 'c' ),
				'from'       => $from_status,
				'to'         => $to_status,
				'note'       => $note,
			)
		);

		if ( JN_Booking_CPT::STATUS_CONFIRMED === $to_status ) {
			/**
			 * Fires when a booking is confirmed by staff and linked to an order.
			 *
			 * @param int $booking_id Booking post ID.
			 * @param int $order_id   Linked WooCommerce order ID.
			 */
			do_action( 'jn_booking_confirmed', $booking_id, $order_id );
		} elseif ( JN_Booking_CPT::STATUS_CANCELLED === $to_status ) {
			/**
			 * Fires when a booking is cancelled.
			 *
			 * @param int $booking_id Booking post ID.
			 */
			do_action( 'jn_booking_cancelled', $booking_id );
		}

		return array(
			'booking_id' => $booking_id,
			'from'       => $from_status,
			'to'         => $to_status,
			'order_id'   => $order_id,
			'overlaps'   => $overlaps,
		);
	}

	/**
	 * Validate and save scheduling fields (`confirmed_start`, `duration_minutes`, `format`, `meeting_link`/`location`).
	 *
	 * @param int                  $booking_id Booking post ID.
	 * @param array<string, mixed> $args       Scheduling inputs.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function apply_confirmation_schedule( int $booking_id, array $args ) {
		$raw_start = isset( $args['confirmed_start'] )
			? sanitize_text_field( (string) $args['confirmed_start'] )
			: (string) get_post_meta( $booking_id, '_jn_confirmed_start', true );

		if ( '' === $raw_start ) {
			return new \WP_Error(
				'missing_confirmed_start',
				'Vui lòng chọn ngày và giờ xác nhận lịch hẹn (múi giờ Asia/Ho_Chi_Minh).',
				array( 'status' => 400 )
			);
		}

		try {
			$tz       = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
			$start_dt = new \DateTimeImmutable( str_replace( 'T', ' ', $raw_start ), $tz );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'invalid_confirmed_start',
				'Định dạng ngày giờ xác nhận không hợp lệ.',
				array( 'status' => 400 )
			);
		}

		$duration_min = isset( $args['duration_minutes'] )
			? (int) $args['duration_minutes']
			: (int) get_post_meta( $booking_id, '_jn_duration_minutes', true );
		if ( $duration_min <= 0 ) {
			$duration_min = 60;
		}

		$formats = (array) JN_Booking_Settings::get( 'formats', array() );
		$format  = isset( $args['format'] )
			? sanitize_key( (string) $args['format'] )
			: (string) get_post_meta( $booking_id, '_jn_format', true );
		if ( ! isset( $formats[ $format ] ) ) {
			$format = 'online';
		}
		$format_label = $formats[ $format ] ?? 'Online Trực Tuyến (Zoom High-Res)';

		$meeting_link = isset( $args['meeting_link'] )
			? esc_url_raw( trim( (string) $args['meeting_link'] ) )
			: (string) get_post_meta( $booking_id, '_jn_meeting_link', true );

		$location = isset( $args['location'] )
			? sanitize_text_field( trim( (string) $args['location'] ) )
			: (string) get_post_meta( $booking_id, '_jn_location', true );

		if ( 'online' === $format && '' === $meeting_link ) {
			return new \WP_Error(
				'missing_meeting_link',
				'Vui lòng nhập đường dẫn phòng họp trực tuyến (Zoom/Meet) cho hình thức Online.',
				array( 'status' => 400 )
			);
		}

		if ( 'in-person' === $format && '' === $location ) {
			$location = (string) JN_Booking_Settings::get( 'academy_address', 'TP. Hồ Chí Minh, Việt Nam' );
		}

		$start_ts = $start_dt->getTimestamp();
		$end_ts   = $start_ts + ( $duration_min * 60 );
		$overlaps = self::find_overlapping_bookings( $start_ts, $end_ts, $booking_id );

		if ( ! empty( $overlaps ) && empty( $args['allow_overlap'] ) ) {
			$conflict_labels = array();
			foreach ( $overlaps as $ov ) {
				$conflict_labels[] = sprintf( '%s (%s — %s)', $ov['booking_code'], $ov['customer_name'], $ov['confirmed_start'] );
			}
			return new \WP_Error(
				'schedule_overlap',
				'Cảnh báo trùng lịch: Khung giờ đã chọn trùng với lịch hẹn đã xác nhận: ' . implode( '; ', $conflict_labels ) . '. Vui lòng chọn giờ khác hoặc tích xác nhận vẫn xếp lịch trùng.',
				array(
					'status'   => 409,
					'overlaps' => $overlaps,
				)
			);
		}

		update_post_meta( $booking_id, '_jn_confirmed_start', $start_dt->format( 'Y-m-d H:i:s' ) );
		update_post_meta( $booking_id, '_jn_confirmed_start_ts', $start_ts );
		update_post_meta( $booking_id, '_jn_confirmed_end_ts', $end_ts );
		update_post_meta( $booking_id, '_jn_duration_minutes', $duration_min );
		update_post_meta( $booking_id, '_jn_format', $format );
		update_post_meta( $booking_id, '_jn_format_label', $format_label );
		update_post_meta( $booking_id, '_jn_meeting_link', $meeting_link );
		update_post_meta( $booking_id, '_jn_location', $location );

		return array(
			'start_ts' => $start_ts,
			'end_ts'   => $end_ts,
			'overlaps' => $overlaps,
		);
	}

	/**
	 * Create a pending WooCommerce order for a confirmed booking, or link an existing order.
	 *
	 * @param int                  $booking_id Booking post ID.
	 * @param array<string, mixed> $args       Optional `existing_order_id` or `agreed_price`.
	 * @return int|\WP_Error Linked WooCommerce order ID.
	 */
	public static function ensure_order_for_confirmed_booking( int $booking_id, array $args = array() ) {
		if ( ! function_exists( 'wc_create_order' ) || ! function_exists( 'wc_get_order' ) ) {
			return new \WP_Error( 'woocommerce_unavailable', 'WooCommerce chưa sẵn sàng.' );
		}

		$post = get_post( $booking_id );
		if ( ! $post instanceof \WP_Post ) {
			return new \WP_Error( 'booking_not_found', 'Không tìm thấy lịch hẹn.' );
		}

		// 1. If this is a child Mentorship session (`post_parent > 0`), inherit parent enrolment order if available.
		$existing_order_id = isset( $args['existing_order_id'] ) ? absint( $args['existing_order_id'] ) : 0;
		if ( 0 === $existing_order_id ) {
			$existing_order_id = (int) get_post_meta( $booking_id, '_jn_order_id', true );
		}
		if ( 0 === $existing_order_id && $post->post_parent > 0 ) {
			$existing_order_id = (int) get_post_meta( (int) $post->post_parent, '_jn_order_id', true );
		}

		$booking_code = (string) get_post_meta( $booking_id, '_jn_booking_code', true );

		if ( $existing_order_id > 0 ) {
			$existing_order = wc_get_order( $existing_order_id );
			if ( ! $existing_order instanceof \WC_Order ) {
				return new \WP_Error( 'invalid_order_id', 'Đơn hàng WooCommerce được chỉ định không tồn tại.' );
			}
			self::link_booking_and_order( $booking_id, $existing_order );
			return (int) $existing_order->get_id();
		}

		// 2. Create a new pending WooCommerce Order via HPOS CRUD.
		$product_id   = (int) get_post_meta( $booking_id, '_jn_package_product_id', true );
		$pkg_key      = (string) get_post_meta( $booking_id, '_jn_package_key', true );
		$product      = $product_id > 0 ? wc_get_product( $product_id ) : JN_Coaching_Packages::get_product_by_key( $pkg_key );
		$customer_uid = (int) get_post_meta( $booking_id, '_jn_customer_user_id', true );
		$cust_name    = (string) get_post_meta( $booking_id, '_jn_customer_name', true );
		$cust_phone   = (string) get_post_meta( $booking_id, '_jn_customer_phone', true );
		$cust_email   = (string) get_post_meta( $booking_id, '_jn_customer_email', true );

		$agreed_price = isset( $args['agreed_price'] )
			? max( 0, (int) $args['agreed_price'] )
			: (int) get_post_meta( $booking_id, '_jn_agreed_price', true );

		if ( $agreed_price <= 0 && $product instanceof \WC_Product && '' !== $product->get_regular_price() ) {
			$agreed_price = (int) $product->get_regular_price();
		}

		// Default agreed fee fallback when package product price is UNKNOWN (Draft) so order-pay is payable.
		if ( $agreed_price <= 0 ) {
			$default_fees = array(
				'30-phut'            => 1500000,
				'60-phut'            => 2500000,
				'mentorship-3-thang' => 25000000,
			);
			$agreed_price = $default_fees[ $pkg_key ] ?? 2500000;
		}
		update_post_meta( $booking_id, '_jn_agreed_price', $agreed_price );

		$order = wc_create_order(
			array(
				'status'      => 'pending',
				'customer_id' => $customer_uid > 0 ? $customer_uid : 0,
			)
		);

		if ( is_wp_error( $order ) || ! $order instanceof \WC_Order ) {
			return new \WP_Error( 'order_creation_failed', 'Không thể khởi tạo đơn hàng WooCommerce.' );
		}

		if ( $product instanceof \WC_Product ) {
			$order->add_product(
				$product,
				1,
				array(
					'subtotal' => $agreed_price,
					'total'    => $agreed_price,
				)
			);
		}

		$order->set_currency( 'VND' );
		$order->set_billing_first_name( $cust_name );
		$order->set_billing_phone( $cust_phone );
		$order->set_billing_email( $cust_email );
		$order->set_billing_country( 'VN' );
		$order->set_payment_method( 'bacs' );
		$order->set_payment_method_title( 'Chuyển khoản ngân hàng' );
		$order->calculate_totals( false );

		$order->add_order_note(
			sprintf(
				'Đơn hàng được tạo tự động khi xác nhận lịch hẹn %s (%s).',
				$booking_code,
				(string) get_post_meta( $booking_id, '_jn_package_title', true )
			)
		);

		self::link_booking_and_order( $booking_id, $order );

		return (int) $order->get_id();
	}

	/**
	 * Bidirectionally link a booking post and a WooCommerce order (HPOS-safe).
	 *
	 * @param int       $booking_id Booking post ID.
	 * @param \WC_Order $order      WooCommerce order instance.
	 * @return void
	 */
	public static function link_booking_and_order( int $booking_id, \WC_Order $order ): void {
		$order_id     = (int) $order->get_id();
		$booking_code = (string) get_post_meta( $booking_id, '_jn_booking_code', true );

		update_post_meta( $booking_id, '_jn_order_id', $order_id );

		$linked_ids = $order->get_meta( '_jn_booking_ids', true );
		if ( ! is_array( $linked_ids ) ) {
			$linked_ids = array();
		}
		if ( ! in_array( $booking_id, $linked_ids, true ) ) {
			$linked_ids[] = $booking_id;
		}

		$order->update_meta_data( '_jn_booking_id', $booking_id );
		$order->update_meta_data( '_jn_booking_code', $booking_code );
		$order->update_meta_data( '_jn_booking_ids', array_values( array_unique( array_map( 'intval', $linked_ids ) ) ) );
		$order->save();
	}

	/**
	 * Derive the current payment state of a booking from its linked WooCommerce order.
	 *
	 * @param int $booking_id Booking post ID.
	 * @return array<string, mixed> Derived payment state (`state`, `label`, `order_id`, `pay_url`, `is_payable`, `is_paid`, `refund_flagged`).
	 */
	public static function get_derived_payment_state( int $booking_id ): array {
		$order_id       = (int) get_post_meta( $booking_id, '_jn_order_id', true );
		$refund_flagged = 'yes' === (string) get_post_meta( $booking_id, '_jn_refund_flagged', true );

		if ( $order_id <= 0 || ! function_exists( 'wc_get_order' ) ) {
			return array(
				'state'          => 'unbilled',
				'label'          => 'Chưa tạo đơn',
				'order_id'       => 0,
				'order_status'   => '',
				'pay_url'        => '',
				'is_payable'     => false,
				'is_paid'        => false,
				'refund_flagged' => false,
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return array(
				'state'          => 'unbilled',
				'label'          => 'Chưa tạo đơn',
				'order_id'       => 0,
				'order_status'   => '',
				'pay_url'        => '',
				'is_payable'     => false,
				'is_paid'        => false,
				'refund_flagged' => false,
			);
		}

		$wc_status  = $order->get_status();
		$is_paid    = $order->is_paid() || in_array( $wc_status, array( 'processing', 'completed' ), true );
		$is_payable = ! $is_paid && $order->needs_payment() && JN_Booking_CPT::STATUS_CANCELLED !== get_post_status( $booking_id );
		$pay_url    = $is_payable ? $order->get_checkout_payment_url() : '';

		if ( $refund_flagged || 'yes' === (string) $order->get_meta( '_jn_refund_flagged', true ) ) {
			return array(
				'state'          => 'paid_refund_flagged',
				'label'          => 'Đã thanh toán — Cần hoàn tiền thủ công',
				'order_id'       => $order_id,
				'order_status'   => $wc_status,
				'pay_url'        => '',
				'is_payable'     => false,
				'is_paid'        => true,
				'refund_flagged' => true,
			);
		}

		if ( 'refunded' === $wc_status ) {
			return array(
				'state'          => 'refunded',
				'label'          => 'Đã hoàn tiền',
				'order_id'       => $order_id,
				'order_status'   => $wc_status,
				'pay_url'        => '',
				'is_payable'     => false,
				'is_paid'        => false,
				'refund_flagged' => false,
			);
		}

		if ( $is_paid ) {
			return array(
				'state'          => 'paid',
				'label'          => 'Đã thanh toán',
				'order_id'       => $order_id,
				'order_status'   => $wc_status,
				'pay_url'        => '',
				'is_payable'     => false,
				'is_paid'        => true,
				'refund_flagged' => false,
			);
		}

		if ( 'cancelled' === $wc_status ) {
			return array(
				'state'          => 'order_cancelled',
				'label'          => 'Đã hủy đơn',
				'order_id'       => $order_id,
				'order_status'   => $wc_status,
				'pay_url'        => '',
				'is_payable'     => false,
				'is_paid'        => false,
				'refund_flagged' => false,
			);
		}

		if ( 'on-hold' === $wc_status ) {
			return array(
				'state'          => 'on_hold_verification',
				'label'          => 'Chờ xác nhận chuyển khoản',
				'order_id'       => $order_id,
				'order_status'   => $wc_status,
				'pay_url'        => $pay_url,
				'is_payable'     => false,
				'is_paid'        => false,
				'refund_flagged' => false,
			);
		}

		return array(
			'state'          => 'pending_payment',
			'label'          => 'Chờ thanh toán',
			'order_id'       => $order_id,
			'order_status'   => $wc_status,
			'pay_url'        => $pay_url,
			'is_payable'     => $is_payable,
			'is_paid'        => false,
			'refund_flagged' => false,
		);
	}

	/**
	 * Enforce cancellation payment policy when a booking transitions to `Đã hủy`:
	 * - Unpaid order -> automatically cancelled.
	 * - Paid order -> NEVER automatically refunded; flagged for manual refund (`_jn_refund_flagged = 'yes'`).
	 *
	 * @param int $booking_id Booking post ID.
	 * @return void
	 */
	private static function handle_booking_cancellation_order_policy( int $booking_id ): void {
		$post = get_post( $booking_id );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		// If this is a child Mentorship session (`post_parent > 0`), cancelling one session does not cancel the whole 12-session enrolment order.
		if ( $post->post_parent > 0 ) {
			return;
		}

		$order_id = (int) get_post_meta( $booking_id, '_jn_order_id', true );
		if ( $order_id <= 0 || ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$booking_code = (string) get_post_meta( $booking_id, '_jn_booking_code', true );
		$wc_status    = $order->get_status();
		$is_paid      = $order->is_paid() || in_array( $wc_status, array( 'processing', 'completed' ), true );

		if ( $is_paid ) {
			// Paid order: flag for manual refund, NEVER auto-refund.
			update_post_meta( $booking_id, '_jn_refund_flagged', 'yes' );
			$order->update_meta_data( '_jn_refund_flagged', 'yes' );
			$order->add_order_note(
				sprintf(
					'CẢNH BÁO HOÀN TIỀN THỦ CÔNG: Lịch hẹn %s đã bị hủy sau khi đơn hàng đã thanh toán. Vui lòng kiểm tra và hoàn tiền thủ công cho khách hàng.',
					$booking_code
				)
			);
			$order->save();
			return;
		}

		if ( in_array( $wc_status, array( 'pending', 'on-hold', 'failed' ), true ) ) {
			$order->update_status(
				'cancelled',
				sprintf( 'Đơn hàng tự động hủy do lịch hẹn %s chuyển sang trạng thái Đã hủy.', $booking_code )
			);
		}
	}

	/**
	 * Synchronize booking timeline and notify staff when a linked WooCommerce order is paid.
	 *
	 * @param int $order_id WooCommerce Order ID.
	 * @return void
	 */
	public static function handle_order_paid( int $order_id ): void {
		if ( $order_id <= 0 || ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$booking_ids = get_posts(
			array(
				'post_type'      => JN_Booking_CPT::POST_TYPE,
				'post_status'    => array_keys( JN_Booking_CPT::get_statuses() ),
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_jn_order_id',
						'value' => $order_id,
						'type'  => 'NUMERIC',
					),
				),
			)
		);

		$primary_id = (int) $order->get_meta( '_jn_booking_id', true );
		if ( $primary_id > 0 && ! in_array( $primary_id, $booking_ids, true ) ) {
			$booking_ids[] = $primary_id;
		}

		if ( empty( $booking_ids ) ) {
			return;
		}

		$tz     = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
		$now_vn = new \DateTimeImmutable( 'now', $tz );

		foreach ( $booking_ids as $bid ) {
			$bid          = (int) $bid;
			$already_sent = (string) get_post_meta( $bid, '_jn_payment_notified', true );
			if ( 'yes' === $already_sent ) {
				continue;
			}

			update_post_meta( $bid, '_jn_payment_notified', 'yes' );

			$current_status = (string) get_post_status( $bid );
			self::append_timeline_entry(
				$bid,
				array(
					'actor_id'   => (int) $order->get_customer_id(),
					'actor_name' => 'WooCommerce HPOS',
					'timestamp'  => $now_vn->format( 'c' ),
					'from'       => $current_status,
					'to'         => $current_status,
					'note'       => sprintf( 'Đơn hàng #%d đã được thanh toán (%s).', $order_id, wc_price( $order->get_total() ) ),
				)
			);

			$post = get_post( $bid );
			if ( $post instanceof \WP_Post && 0 === (int) $post->post_parent ) {
				/**
				 * Fires when the order linked to a root booking is marked paid.
				 *
				 * @param int $booking_id Booking post ID.
				 * @param int $order_id   WooCommerce Order ID.
				 */
				do_action( 'jn_booking_order_paid', $bid, $order_id );
			}
		}
	}

	/**
	 * Return all Mentorship session bookings (parent = session #1 + child sessions #2..#12)
	 * for a Mentorship enrolment.
	 *
	 * @param int $booking_id Enrolment (root) or child session booking ID.
	 * @return int[] Ordered array of session booking IDs.
	 */
	public static function get_mentorship_sessions( int $booking_id ): array {
		$post = get_post( $booking_id );
		if ( ! $post instanceof \WP_Post || JN_Booking_CPT::POST_TYPE !== $post->post_type ) {
			return array();
		}

		$root_id  = $post->post_parent > 0 ? (int) $post->post_parent : (int) $post->ID;
		$children = get_posts(
			array(
				'post_type'      => JN_Booking_CPT::POST_TYPE,
				'post_status'    => array_keys( JN_Booking_CPT::get_statuses() ),
				'post_parent'    => $root_id,
				'posts_per_page' => 25,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);

		return array_merge( array( $root_id ), array_map( 'intval', $children ) );
	}

	/**
	 * Create a new follow-up Mentorship session booking (up to 12 total sessions) linked to
	 * the parent enrolment booking and its single WooCommerce order.
	 *
	 * @param int                  $enrolment_booking_id Root Mentorship booking ID.
	 * @param array<string, mixed> $args                 Optional scheduling/actor arguments.
	 * @return int|\WP_Error Created child session booking ID or WP_Error.
	 */
	public static function create_mentorship_session( int $enrolment_booking_id, array $args = array() ) {
		$actor_id = isset( $args['actor_id'] ) ? (int) $args['actor_id'] : get_current_user_id();
		if ( empty( $args['system_action'] ) && ! JN_Booking_Roles::user_can_manage_bookings( $actor_id ) ) {
			return new \WP_Error(
				'forbidden_booking_action',
				'Bạn không có quyền tạo buổi học Mentorship.',
				array( 'status' => 403 )
			);
		}

		$root_post = get_post( $enrolment_booking_id );
		if ( ! $root_post instanceof \WP_Post || JN_Booking_CPT::POST_TYPE !== $root_post->post_type ) {
			return new \WP_Error( 'booking_not_found', 'Không tìm thấy hồ sơ Mentorship gốc.' );
		}

		if ( $root_post->post_parent > 0 ) {
			$enrolment_booking_id = (int) $root_post->post_parent;
			$root_post            = get_post( $enrolment_booking_id );
		}

		$pkg_key    = (string) get_post_meta( $enrolment_booking_id, '_jn_package_key', true );
		$total_sess = (int) get_post_meta( $enrolment_booking_id, '_jn_total_sessions', true );
		if ( 'mentorship-3-thang' !== $pkg_key && $total_sess <= 1 ) {
			return new \WP_Error(
				'not_mentorship_booking',
				'Chỉ gói Master Mentorship (3 Tháng) mới hỗ trợ tạo chuỗi 12 buổi học.',
				array( 'status' => 400 )
			);
		}

		$all_sessions    = self::get_mentorship_sessions( $enrolment_booking_id );
		$active_sessions = array();
		foreach ( $all_sessions as $sid ) {
			if ( JN_Booking_CPT::STATUS_CANCELLED !== get_post_status( $sid ) ) {
				$active_sessions[] = $sid;
			}
		}

		if ( count( $active_sessions ) >= self::MENTORSHIP_MAX_SESSIONS ) {
			return new \WP_Error(
				'mentorship_session_limit_reached',
				sprintf( 'Lộ trình Mentorship này đã đủ tối đa %d buổi học.', self::MENTORSHIP_MAX_SESSIONS ),
				array( 'status' => 400 )
			);
		}

		$next_number  = count( $active_sessions ) + 1;
		$customer_uid = (int) get_post_meta( $enrolment_booking_id, '_jn_customer_user_id', true );
		$order_id     = (int) get_post_meta( $enrolment_booking_id, '_jn_order_id', true );

		$child_id = wp_insert_post(
			array(
				'post_type'   => JN_Booking_CPT::POST_TYPE,
				'post_status' => JN_Booking_CPT::STATUS_PENDING,
				'post_title'  => 'JN-SESSION',
				'post_author' => $customer_uid,
				'post_parent' => $enrolment_booking_id,
			),
			true
		);

		if ( is_wp_error( $child_id ) ) {
			return $child_id;
		}

		$child_id    = (int) $child_id;
		$parent_code = (string) get_post_meta( $enrolment_booking_id, '_jn_booking_code', true );
		$child_code  = sprintf( '%s-S%02d', $parent_code, $next_number );

		wp_update_post(
			array(
				'ID'         => $child_id,
				'post_title' => $child_code,
			)
		);

		$copy_keys = array(
			'_jn_customer_user_id',
			'_jn_customer_name',
			'_jn_customer_phone_raw',
			'_jn_customer_phone',
			'_jn_customer_email',
			'_jn_package_product_id',
			'_jn_package_key',
			'_jn_package_title',
			'_jn_package_option_label',
			'_jn_format',
			'_jn_format_label',
			'_jn_preferred_window',
			'_jn_preferred_window_label',
			'_jn_goals',
			'_jn_consent_given',
			'_jn_consent_timestamp',
			'_jn_consent_policy_page_id',
			'_jn_consent_policy_url',
			'_jn_consent_policy_version',
			'_jn_duration_minutes',
		);

		foreach ( $copy_keys as $meta_key ) {
			update_post_meta( $child_id, $meta_key, get_post_meta( $enrolment_booking_id, $meta_key, true ) );
		}

		update_post_meta( $child_id, '_jn_booking_code', $child_code );
		update_post_meta( $child_id, '_jn_session_number', $next_number );
		update_post_meta( $child_id, '_jn_total_sessions', self::MENTORSHIP_MAX_SESSIONS );
		update_post_meta( $child_id, '_jn_order_id', $order_id );
		update_post_meta( $child_id, '_jn_refund_flagged', 'no' );
		update_post_meta( $child_id, '_jn_staff_notes', '' );

		if ( $order_id > 0 && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
			if ( $order instanceof \WC_Order ) {
				self::link_booking_and_order( $child_id, $order );
			}
		}

		$tz     = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
		$now_vn = new \DateTimeImmutable( 'now', $tz );

		$timeline = array(
			array(
				'actor_id'   => $actor_id,
				'actor_name' => 'Trợ lý Học viện',
				'timestamp'  => $now_vn->format( 'c' ),
				'from'       => 'new',
				'to'         => JN_Booking_CPT::STATUS_PENDING,
				'note'       => sprintf( 'Khởi tạo Buổi %d/%d thuộc lộ trình Mentorship (%s).', $next_number, self::MENTORSHIP_MAX_SESSIONS, $parent_code ),
			),
		);
		update_post_meta( $child_id, '_jn_status_timeline', $timeline );

		return $child_id;
	}

	/**
	 * Append an entry to the booking's immutable status timeline.
	 *
	 * @param int                  $booking_id Booking post ID.
	 * @param array<string, mixed> $entry      Timeline entry (`actor_id`, `actor_name`, `timestamp`, `from`, `to`, `note`).
	 * @return void
	 */
	public static function append_timeline_entry( int $booking_id, array $entry ): void {
		$timeline = get_post_meta( $booking_id, '_jn_status_timeline', true );
		if ( ! is_array( $timeline ) ) {
			$timeline = array();
		}
		$timeline[] = $entry;
		update_post_meta( $booking_id, '_jn_status_timeline', $timeline );
	}

	/**
	 * Extract sanitized landing URL, referrer, and `utm_*` attribution parameters without third-party tracking.
	 *
	 * @param array<string, mixed> $payload Submitted payload.
	 * @param array<string, mixed> $context Request context.
	 * @return array<string, mixed>
	 */
	private static function extract_source_metadata( array $payload, array $context ): array {
		$landing_url = isset( $context['landing_url'] )
			? esc_url_raw( (string) $context['landing_url'] )
			: esc_url_raw( (string) ( $payload['_jn_landing_url'] ?? '' ) );

		$referrer = isset( $context['referrer'] )
			? esc_url_raw( (string) $context['referrer'] )
			: esc_url_raw( (string) ( $payload['_jn_referrer'] ?? '' ) );

		$utm = array();
		$utm_keys = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content' );
		foreach ( $utm_keys as $ukey ) {
			if ( ! empty( $payload[ $ukey ] ) ) {
				$utm[ $ukey ] = sanitize_text_field( (string) $payload[ $ukey ] );
			}
		}

		if ( '' !== $landing_url ) {
			$query_str = (string) wp_parse_url( $landing_url, PHP_URL_QUERY );
			if ( '' !== $query_str ) {
				$parsed_q = array();
				wp_parse_str( $query_str, $parsed_q );
				foreach ( $utm_keys as $ukey ) {
					if ( empty( $utm[ $ukey ] ) && ! empty( $parsed_q[ $ukey ] ) && is_string( $parsed_q[ $ukey ] ) ) {
						$utm[ $ukey ] = sanitize_text_field( $parsed_q[ $ukey ] );
					}
				}
			}
		}

		return array(
			'landing_url' => $landing_url,
			'referrer'    => $referrer,
			'utm'         => $utm,
		);
	}

	/**
	 * Retrieve client IP address for hashed rate-limiting only.
	 *
	 * @return string
	 */
	public static function get_client_ip(): string {
		$remote = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '127.0.0.1';
		return filter_var( $remote, FILTER_VALIDATE_IP ) ? $remote : '127.0.0.1';
	}
}
