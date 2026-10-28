<?php
/**
 * Booking Engine Settings Repository for Jannie Nguyen Academy.
 *
 * Manages staff notification emails, verbatim time-window definitions,
 * formats + Academy address, optional monthly capacity per package (default off),
 * customer account auto-creation policy (`AUTO-CREATE = no`), and privacy consent settings.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Booking_Settings
 */
class JN_Booking_Settings {

	/**
	 * Option name in `wp_options`.
	 */
	const OPTION_KEY = 'jn_booking_settings';

	/**
	 * Default waitlist notice (approved Vietnamese wording).
	 */
	const DEFAULT_WAITLIST_NOTE = 'Lưu ý: Số lượng lịch Coaching 1:1 cho gói này trong tháng hiện tại đã đủ để bảo đảm chất lượng đồng hành sâu sát nhất. Bạn vẫn có thể gửi yêu cầu để được ưu tiên xếp vào Danh sách chờ (Waitlist) cho khung lịch trống gần nhất.';

	/**
	 * Default privacy consent text.
	 */
	const DEFAULT_CONSENT_TEXT = 'Tôi đồng ý để Jannie Nguyen Academy thu thập và xử lý thông tin cá nhân đã cung cấp nhằm mục đích liên hệ tư vấn và sắp xếp lịch hẹn theo Chính sách bảo mật.';

	/**
	 * Verbatim wireframe success message.
	 */
	const SUCCESS_LEAD = 'Cảm ơn bạn!';
	const SUCCESS_BODY = 'Yêu cầu đã được ghi nhận. Đội ngũ trợ lý của Jannie Nguyễn sẽ liên hệ trực tiếp qua số điện thoại của bạn trong vòng 24 giờ.';
	const SUCCESS_TEXT = 'Cảm ơn bạn! Yêu cầu đã được ghi nhận. Đội ngũ trợ lý của Jannie Nguyễn sẽ liên hệ trực tiếp qua số điện thoại của bạn trong vòng 24 giờ.';

	/**
	 * Return canonical default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults(): array {
		$privacy_page_id = (int) get_option( 'wp_page_for_privacy_policy', 15 );
		if ( $privacy_page_id <= 0 ) {
			$privacy_page_id = 15;
		}

		$admin_email = (string) get_option( 'admin_email', 'admin@example.com' );
		$staff_list  = array_unique( array_filter( array( 'contact@jannienguyen.vn', $admin_email ) ) );

		return array(
			'staff_emails'        => implode( ', ', $staff_list ),
			'time_windows'        => array(
				'morning'   => 'Buổi sáng (09:00 - 11:30)',
				'afternoon' => 'Buổi chiều (14:00 - 17:30)',
				'weekend'   => 'Cuối tuần (Thứ 7)',
			),
			'formats'             => array(
				'online'    => 'Online Trực Tuyến (Zoom High-Res)',
				'in-person' => 'Trực tiếp tại Academy (TP.HCM)',
			),
			'academy_address'     => 'TP. Hồ Chí Minh, Việt Nam',
			'capacity_enabled'    => 'no',
			'capacity_limits'     => array(
				'30-phut'            => 0,
				'60-phut'            => 0,
				'mentorship-3-thang' => 0,
			),
			// Fee (VND) used for the order-pay link when staff confirm without entering an agreed price.
			'default_fees'                      => array(
				'30-phut'            => 1500000,
				'60-phut'            => 2500000,
				'mentorship-3-thang' => 25000000,
			),
			'waitlist_note'                     => self::DEFAULT_WAITLIST_NOTE,
			'auto_create_account'               => 'no',
			'consent_text'                      => self::DEFAULT_CONSENT_TEXT,
			'privacy_page_id'                   => $privacy_page_id,
			'privacy_version'                   => '1.0',
			'retention_cancelled_guest_enabled' => 'no',
			'retention_cancelled_guest_days'    => 90,
		);
	}

	/**
	 * Return merged settings array.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_all(): array {
		$defaults = self::get_defaults();
		$saved    = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			return $defaults;
		}

		$merged = wp_parse_args( $saved, $defaults );

		if ( ! is_array( $merged['time_windows'] ) || empty( $merged['time_windows'] ) ) {
			$merged['time_windows'] = $defaults['time_windows'];
		}
		if ( ! is_array( $merged['formats'] ) || empty( $merged['formats'] ) ) {
			$merged['formats'] = $defaults['formats'];
		}
		if ( ! is_array( $merged['capacity_limits'] ) ) {
			$merged['capacity_limits'] = $defaults['capacity_limits'];
		} else {
			$merged['capacity_limits'] = wp_parse_args( $merged['capacity_limits'], $defaults['capacity_limits'] );
		}
		$merged['default_fees'] = is_array( $merged['default_fees'] )
			? wp_parse_args( $merged['default_fees'], $defaults['default_fees'] )
			: $defaults['default_fees'];

		return $merged;
	}

	/**
	 * Default fee (VND) for a package key, or 0 when none is configured.
	 *
	 * @param string $package_key Package key (`30-phut`, `60-phut`, `mentorship-3-thang`).
	 * @return int
	 */
	public static function get_default_fee( string $package_key ): int {
		$fees = (array) self::get( 'default_fees', array() );
		return max( 0, (int) ( $fees[ $package_key ] ?? 0 ) );
	}

	/**
	 * Get a single setting by key.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	public static function get( string $key, $default = null ) {
		$all = self::get_all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
	}

	/**
	 * Sanitize and save settings array.
	 *
	 * @param array<string, mixed> $raw Raw input settings.
	 * @return array<string, mixed> Sanitized settings.
	 */
	public static function update( array $raw ): array {
		$current  = self::get_all();
		$defaults = self::get_defaults();

		// 1. Staff emails.
		if ( isset( $raw['staff_emails'] ) ) {
			$parts       = preg_split( '/[\s,;]+/', (string) $raw['staff_emails'] );
			$valid_mails = array();
			if ( is_array( $parts ) ) {
				foreach ( $parts as $candidate ) {
					$clean_mail = sanitize_email( trim( $candidate ) );
					if ( '' !== $clean_mail && is_email( $clean_mail ) ) {
						$valid_mails[] = strtolower( $clean_mail );
					}
				}
			}
			$current['staff_emails'] = ! empty( $valid_mails )
				? implode( ', ', array_unique( $valid_mails ) )
				: $defaults['staff_emails'];
		}

		// 2. Time windows.
		if ( isset( $raw['time_windows'] ) && is_array( $raw['time_windows'] ) ) {
			$windows = array();
			foreach ( $defaults['time_windows'] as $win_key => $default_label ) {
				$val                 = isset( $raw['time_windows'][ $win_key ] )
					? sanitize_text_field( (string) $raw['time_windows'][ $win_key ] )
					: $default_label;
				$windows[ $win_key ] = '' !== $val ? $val : $default_label;
			}
			$current['time_windows'] = $windows;
		}

		// 3. Formats & Academy address.
		if ( isset( $raw['formats'] ) && is_array( $raw['formats'] ) ) {
			$formats = array();
			foreach ( $defaults['formats'] as $fmt_key => $default_label ) {
				$val                 = isset( $raw['formats'][ $fmt_key ] )
					? sanitize_text_field( (string) $raw['formats'][ $fmt_key ] )
					: $default_label;
				$formats[ $fmt_key ] = '' !== $val ? $val : $default_label;
			}
			$current['formats'] = $formats;
		}

		if ( isset( $raw['academy_address'] ) ) {
			$addr                       = sanitize_text_field( (string) $raw['academy_address'] );
			$current['academy_address'] = '' !== $addr ? $addr : $defaults['academy_address'];
		}

		// 4. Monthly capacity per package.
		if ( array_key_exists( 'capacity_enabled', $raw ) ) {
			$current['capacity_enabled'] = ( 'yes' === $raw['capacity_enabled'] || '1' === (string) $raw['capacity_enabled'] || true === $raw['capacity_enabled'] )
				? 'yes'
				: 'no';
		}

		if ( isset( $raw['capacity_limits'] ) && is_array( $raw['capacity_limits'] ) ) {
			$limits = array();
			foreach ( array_keys( $defaults['capacity_limits'] ) as $pkg_key ) {
				$limits[ $pkg_key ] = isset( $raw['capacity_limits'][ $pkg_key ] )
					? max( 0, absint( $raw['capacity_limits'][ $pkg_key ] ) )
					: 0;
			}
			$current['capacity_limits'] = $limits;
		}

		if ( isset( $raw['default_fees'] ) && is_array( $raw['default_fees'] ) ) {
			$fees = array();
			foreach ( array_keys( $defaults['default_fees'] ) as $pkg_key ) {
				$fees[ $pkg_key ] = isset( $raw['default_fees'][ $pkg_key ] )
					? max( 0, absint( $raw['default_fees'][ $pkg_key ] ) )
					: 0;
			}
			$current['default_fees'] = $fees;
		}

		if ( isset( $raw['waitlist_note'] ) ) {
			$note                     = sanitize_textarea_field( (string) $raw['waitlist_note'] );
			$current['waitlist_note'] = '' !== $note ? $note : self::DEFAULT_WAITLIST_NOTE;
		}

		// 5. Auto-create customer account.
		if ( array_key_exists( 'auto_create_account', $raw ) ) {
			$current['auto_create_account'] = ( 'yes' === $raw['auto_create_account'] || '1' === (string) $raw['auto_create_account'] || true === $raw['auto_create_account'] )
				? 'yes'
				: 'no';
		}

		// 6. Consent text + Privacy page + Policy version.
		if ( isset( $raw['consent_text'] ) ) {
			$text                    = sanitize_textarea_field( (string) $raw['consent_text'] );
			$current['consent_text'] = '' !== $text ? $text : self::DEFAULT_CONSENT_TEXT;
		}

		if ( isset( $raw['privacy_page_id'] ) ) {
			$page_id                    = absint( $raw['privacy_page_id'] );
			$current['privacy_page_id'] = $page_id > 0 ? $page_id : $defaults['privacy_page_id'];
		}

		if ( isset( $raw['privacy_version'] ) ) {
			$ver                        = sanitize_text_field( (string) $raw['privacy_version'] );
			$current['privacy_version'] = '' !== $ver ? $ver : '1.0';
		}

		// 7. Retention setting for cancelled guest bookings (default off, logged as an owner decision).
		if ( array_key_exists( 'retention_cancelled_guest_enabled', $raw ) ) {
			$new_ret = ( 'yes' === $raw['retention_cancelled_guest_enabled'] || '1' === (string) $raw['retention_cancelled_guest_enabled'] || true === $raw['retention_cancelled_guest_enabled'] )
				? 'yes'
				: 'no';
			if ( $new_ret !== (string) $current['retention_cancelled_guest_enabled'] ) {
				self::log_owner_decision(
					'retention_cancelled_guest_enabled',
					(string) $current['retention_cancelled_guest_enabled'],
					$new_ret
				);
			}
			$current['retention_cancelled_guest_enabled'] = $new_ret;
		}

		if ( isset( $raw['retention_cancelled_guest_days'] ) ) {
			$days                                      = max( 7, absint( $raw['retention_cancelled_guest_days'] ) );
			$current['retention_cancelled_guest_days'] = $days;
		}

		update_option( self::OPTION_KEY, $current, false );
		return $current;
	}

	/**
	 * Ensure default settings and the initial owner decision (`ADR-009`: `retention_cancelled_guest_enabled = no`)
	 * are recorded in `wp_options`.
	 *
	 * @return void
	 */
	public static function ensure_defaults(): void {
		$all = self::get_all();
		update_option( self::OPTION_KEY, $all, false );

		$log = self::get_owner_decisions_log();
		if ( empty( $log ) ) {
			self::log_owner_decision(
				'retention_cancelled_guest_enabled',
				'unset',
				(string) $all['retention_cancelled_guest_enabled']
			);
		}
	}

	/**
	 * Record an owner decision audit entry when privacy/retention policies are changed.
	 *
	 * @param string $setting_key Setting key.
	 * @param string $old_value   Previous value.
	 * @param string $new_value   New value.
	 * @return void
	 */
	public static function log_owner_decision( string $setting_key, string $old_value, string $new_value ): void {
		$log = get_option( 'jn_owner_decision_log', array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		$tz     = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
		$now_vn = new \DateTimeImmutable( 'now', $tz );

		array_unshift(
			$log,
			array(
				'timestamp'   => $now_vn->format( 'c' ),
				'actor_id'    => get_current_user_id(),
				'setting_key' => $setting_key,
				'old_value'   => $old_value,
				'new_value'   => $new_value,
			)
		);
		update_option( 'jn_owner_decision_log', array_slice( $log, 0, 100 ), false );
	}

	/**
	 * Return the owner decision audit log (`jn_owner_decision_log`).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_owner_decisions_log(): array {
		$log = get_option( 'jn_owner_decision_log', array() );
		return is_array( $log ) ? $log : array();
	}

	/**
	 * Return validated array of staff recipient email addresses.
	 *
	 * @return string[]
	 */
	public static function get_staff_email_list(): array {
		$raw   = (string) self::get( 'staff_emails', 'contact@jannienguyen.vn' );
		$parts = preg_split( '/[\s,;]+/', $raw );
		$list  = array();
		if ( is_array( $parts ) ) {
			foreach ( $parts as $email ) {
				$clean = sanitize_email( trim( $email ) );
				if ( '' !== $clean && is_email( $clean ) ) {
					$list[] = strtolower( $clean );
				}
			}
		}
		if ( empty( $list ) ) {
			$list[] = 'contact@jannienguyen.vn';
		}
		return array_values( array_unique( $list ) );
	}

	/**
	 * Return privacy policy metadata (page ID, URL, version, consent text).
	 *
	 * @return array<string, mixed>
	 */
	public static function get_privacy_metadata(): array {
		$page_id = (int) self::get( 'privacy_page_id', 15 );
		$url     = $page_id > 0 ? get_permalink( $page_id ) : '';
		if ( empty( $url ) ) {
			$url = get_privacy_policy_url();
		}
		if ( empty( $url ) ) {
			$url = home_url( '/chinh-sach-bao-mat/' );
		}

		$version = (string) self::get( 'privacy_version', '1.0' );
		$post    = $page_id > 0 ? get_post( $page_id ) : null;
		if ( $post instanceof \WP_Post && ! empty( $post->post_modified_gmt ) && '0000-00-00 00:00:00' !== $post->post_modified_gmt ) {
			$version = $version . ' (' . substr( $post->post_modified_gmt, 0, 10 ) . ')';
		}

		return array(
			'page_id'      => $page_id,
			'url'          => $url,
			'version'      => $version,
			'consent_text' => (string) self::get( 'consent_text', self::DEFAULT_CONSENT_TEXT ),
		);
	}
}
