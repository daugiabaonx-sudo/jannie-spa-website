<?php
/**
 * Customer CRM Profile Schema, Validation & Bidirectional WooCommerce Sync.
 *
 * Manages the 10 Jannie Nguyen CRM customer attributes across both registered
 * WordPress/WooCommerce users (`WP_User` + `WC_Customer`) and guest customers
 * (`jn_guest_crm_profiles` keyed by normalized email or E.164 phone):
 * 1. Họ tên (`full_name`)
 * 2. SĐT / Zalo (`phone` normalized to `+84...`)
 * 3. Email (`email`)
 * 4. Loại khách (`customer_type`: `ca_nhan` | `tho_hoc_vien` | `chu_co_so`)
 * 5. Tên cơ sở (`business_name`)
 * 6. Tỉnh / Thành (`province_city`)
 * 7. Nguồn (`source_type`: `form` | `utm` | `gioi_thieu` | `checkout` | `walk_in` + `source_detail`)
 * 8. Tags (`tags`)
 * 9. Ghi chú nội bộ (`internal_notes` — staff-only timeline)
 * 10. Nhật ký đồng ý bảo mật (`consent_log` — read-only immutable log)
 *
 * Keeps WooCommerce billing fields (`billing_first_name`, `billing_last_name`,
 * `billing_phone`, `billing_email`, `billing_company`, `billing_city`, `billing_state`)
 * and `wc_customer_lookup` synchronized.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Customer_Profile
 */
class JN_Customer_Profile {

	/**
	 * Customer type constants (`Loại khách`).
	 */
	const TYPE_INDIVIDUAL   = 'ca_nhan';
	const TYPE_PRACTITIONER = 'tho_hoc_vien';
	const TYPE_OWNER        = 'chu_co_so';

	/**
	 * Customer source constants (`Nguồn`).
	 */
	const SOURCE_FORM     = 'form';
	const SOURCE_UTM      = 'utm';
	const SOURCE_REFERRAL = 'gioi_thieu';
	const SOURCE_CHECKOUT = 'checkout';
	const SOURCE_WALK_IN  = 'walk_in';

	/**
	 * Option key for storing CRM metadata on guest customers prior to registration.
	 */
	const GUEST_PROFILES_OPTION = 'jn_guest_crm_profiles';

	/**
	 * User meta key constants for CRM fields.
	 */
	const META_FULL_NAME      = '_jn_full_name';
	const META_PHONE_E164     = '_jn_phone_e164';
	const META_PHONE_RAW      = '_jn_phone_raw';
	const META_CUSTOMER_TYPE  = '_jn_customer_type';
	const META_BUSINESS_NAME  = '_jn_business_name';
	const META_PROVINCE_CITY  = '_jn_province_city';
	const META_SOURCE_TYPE    = '_jn_source_type';
	const META_SOURCE_DETAIL  = '_jn_source_detail';
	const META_TAGS           = '_jn_customer_tags';
	const META_TAGS_CSV       = '_jn_customer_tags_csv';
	const META_INTERNAL_NOTES = '_jn_internal_notes';
	const META_CONSENT_LOG    = '_jn_consent_log';

	/**
	 * Guard flag to prevent infinite recursion when syncing WP user <-> WC_Customer.
	 *
	 * @var bool
	 */
	private static $is_syncing = false;

	/**
	 * Register hooks for WooCommerce customer sync and WP user profile screens.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'woocommerce_customer_save_address', array( __CLASS__, 'handle_wc_address_saved' ), 20, 2 );
		add_action( 'woocommerce_checkout_update_customer', array( __CLASS__, 'handle_wc_checkout_customer' ), 20, 2 );
		add_action( 'woocommerce_new_order', array( __CLASS__, 'handle_wc_order_saved' ), 20, 2 );
		add_action( 'woocommerce_update_order', array( __CLASS__, 'handle_wc_order_saved' ), 20, 2 );
		add_action( 'jn_booking_created', array( __CLASS__, 'handle_booking_created' ), 20, 1 );

		// WordPress User Profile / Edit User screens (for Admin & Shop Manager).
		add_action( 'show_user_profile', array( __CLASS__, 'render_wp_user_profile_fields' ), 20, 1 );
		add_action( 'edit_user_profile', array( __CLASS__, 'render_wp_user_profile_fields' ), 20, 1 );
		add_action( 'personal_options_update', array( __CLASS__, 'handle_wp_user_profile_save' ), 20, 1 );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'handle_wp_user_profile_save' ), 20, 1 );
	}

	/**
	 * Return allowed customer types and their Vietnamese labels.
	 *
	 * @return array<string, string>
	 */
	public static function get_customer_types(): array {
		return array(
			self::TYPE_INDIVIDUAL   => 'Cá nhân',
			self::TYPE_PRACTITIONER => 'Thợ – Học viên',
			self::TYPE_OWNER        => 'Chủ cơ sở',
		);
	}

	/**
	 * Return human-readable Vietnamese label for a customer type slug.
	 *
	 * @param string $type Type slug.
	 * @return string
	 */
	public static function get_customer_type_label( string $type ): string {
		$types = self::get_customer_types();
		return $types[ $type ] ?? 'Chưa phân loại';
	}

	/**
	 * Return allowed customer sources and their Vietnamese labels.
	 *
	 * @return array<string, string>
	 */
	public static function get_source_types(): array {
		return array(
			self::SOURCE_FORM     => 'Form đặt lịch',
			self::SOURCE_UTM      => 'Chiến dịch UTM',
			self::SOURCE_REFERRAL => 'Giới thiệu',
			self::SOURCE_CHECKOUT => 'Đơn hàng / Cửa hàng',
			self::SOURCE_WALK_IN  => 'Điện thoại / Trực tiếp',
		);
	}

	/**
	 * Return human-readable Vietnamese label for a source type slug.
	 *
	 * @param string $source Source slug.
	 * @return string
	 */
	public static function get_source_type_label( string $source ): string {
		$sources = self::get_source_types();
		return $sources[ $source ] ?? 'Form đặt lịch';
	}

	/**
	 * Alias for `get_source_type_label()`.
	 *
	 * @param string $source Source slug.
	 * @return string
	 */
	public static function get_source_label( string $source ): string {
		return self::get_source_type_label( $source );
	}

	/**
	 * Normalize a list or comma-separated string of tags into a clean unique array of strings.
	 *
	 * @param string|string[] $raw_tags Raw tags input.
	 * @return string[]
	 */
	public static function sanitize_tags( $raw_tags ): array {
		if ( is_string( $raw_tags ) ) {
			$parts = explode( ',', $raw_tags );
		} elseif ( is_array( $raw_tags ) ) {
			$parts = $raw_tags;
		} else {
			return array();
		}

		$clean = array();
		$seen  = array();
		foreach ( $parts as $part ) {
			$tag = sanitize_text_field( trim( (string) $part ) );
			if ( '' === $tag ) {
				continue;
			}
			$lower = mb_strtolower( $tag );
			if ( ! isset( $seen[ $lower ] ) ) {
				$seen[ $lower ] = true;
				$clean[]        = $tag;
			}
		}

		return $clean;
	}

	/**
	 * Split a Vietnamese full name into `[first_name, last_name]` for WooCommerce billing compatibility
	 * while preserving the full name in `billing_first_name` when single-field mode is used.
	 *
	 * @param string $full_name Full name.
	 * @return array{full_name: string, first_name: string, last_name: string}
	 */
	public static function parse_full_name( string $full_name ): array {
		$clean = sanitize_text_field( trim( $full_name ) );
		return array(
			'full_name'  => $clean,
			'first_name' => $clean,
			'last_name'  => '',
		);
	}

	/**
	 * Read full CRM profile for a registered WordPress user (`user_id > 0`).
	 *
	 * @param int $user_id WordPress User ID.
	 * @return array<string, mixed>
	 */
	public static function get_user_profile( int $user_id ): array {
		$user = get_userdata( $user_id );
		if ( ! $user instanceof \WP_User ) {
			return array();
		}

		$full_name     = (string) get_user_meta( $user_id, '_jn_full_name', true );
		$billing_first = (string) get_user_meta( $user_id, 'billing_first_name', true );
		$billing_last  = (string) get_user_meta( $user_id, 'billing_last_name', true );
		if ( '' === $full_name ) {
			$full_name = trim( $billing_first . ' ' . $billing_last );
		}
		if ( '' === $full_name ) {
			$full_name = trim( $user->first_name . ' ' . $user->last_name );
		}
		if ( '' === $full_name ) {
			$full_name = (string) $user->display_name;
		}

		$phone_e164    = (string) get_user_meta( $user_id, '_jn_phone_e164', true );
		$billing_phone = (string) get_user_meta( $user_id, 'billing_phone', true );
		$norm_phone    = JN_Customer_Service::normalize_vn_phone( '' !== $phone_e164 ? $phone_e164 : $billing_phone );
		$raw_phone     = (string) get_user_meta( $user_id, '_jn_phone_raw', true );
		if ( '' === $raw_phone ) {
			$raw_phone = '' !== $billing_phone ? $billing_phone : $norm_phone;
		}

		$email         = strtolower( sanitize_email( $user->user_email ) );
		$billing_email = strtolower( sanitize_email( (string) get_user_meta( $user_id, 'billing_email', true ) ) );
		if ( '' === $email && '' !== $billing_email ) {
			$email = $billing_email;
		}

		$customer_type = (string) get_user_meta( $user_id, '_jn_customer_type', true );
		if ( ! array_key_exists( $customer_type, self::get_customer_types() ) ) {
			$customer_type = '';
		}

		$business_name = (string) get_user_meta( $user_id, '_jn_business_name', true );
		if ( '' === $business_name ) {
			$business_name = (string) get_user_meta( $user_id, 'billing_company', true );
		}

		$province_city = (string) get_user_meta( $user_id, '_jn_province_city', true );
		if ( '' === $province_city ) {
			$province_city = (string) get_user_meta( $user_id, 'billing_city', true );
		}
		if ( '' === $province_city ) {
			$province_city = (string) get_user_meta( $user_id, 'billing_state', true );
		}

		$source_type = (string) get_user_meta( $user_id, '_jn_source_type', true );
		if ( ! array_key_exists( $source_type, self::get_source_types() ) ) {
			$source_type = self::SOURCE_FORM;
		}
		$source_detail = (string) get_user_meta( $user_id, '_jn_source_detail', true );

		$tags = get_user_meta( $user_id, '_jn_customer_tags', true );
		$tags = self::sanitize_tags( is_array( $tags ) ? $tags : (string) $tags );

		$internal_notes = get_user_meta( $user_id, '_jn_internal_notes', true );
		if ( ! is_array( $internal_notes ) ) {
			$internal_notes = array();
		}

		$consent_log = get_user_meta( $user_id, '_jn_consent_log', true );
		if ( ! is_array( $consent_log ) ) {
			$consent_log = array();
		}

		return array(
			'customer_key'        => 'user:' . $user_id,
			'user_id'             => $user_id,
			'is_guest'            => false,
			'full_name'           => $full_name,
			'phone'               => $norm_phone,
			'phone_e164'          => $norm_phone,
			'phone_local'         => '' !== $norm_phone ? JN_Customer_Service::to_local_phone( $norm_phone ) : '',
			'phone_raw'           => $raw_phone,
			'email'               => $email,
			'customer_type'       => $customer_type,
			'customer_type_label' => self::get_customer_type_label( $customer_type ),
			'business_name'       => $business_name,
			'province_city'       => $province_city,
			'source_type'         => $source_type,
			'source_type_label'   => self::get_source_type_label( $source_type ),
			'source_detail'       => $source_detail,
			'tags'                => $tags,
			'internal_notes'      => $internal_notes,
			'consent_log'         => $consent_log,
			'registered_at'       => $user->user_registered,
		);
	}

	/**
	 * Update CRM profile fields on a registered WordPress user (`user_id > 0`) and synchronize
	 * WooCommerce billing fields (`WC_Customer`) so WooCommerce Customers & Analytics stay consistent.
	 *
	 * @param int                  $user_id User ID.
	 * @param array<string, mixed> $data    Profile fields to update.
	 * @return true|\WP_Error
	 */
	public static function update_user_profile( int $user_id, array $data ) {
		$user = get_userdata( $user_id );
		if ( ! $user instanceof \WP_User ) {
			return new \WP_Error( 'invalid_user', 'Không tìm thấy tài khoản khách hàng.' );
		}

		// The CRM edits customer accounts only: staff/admin identities are never writable from here.
		if ( JN_Booking_Roles::is_staff_user( $user ) ) {
			return new \WP_Error(
				'forbidden_customer_target',
				'Không thể chỉnh sửa tài khoản nhân sự hoặc quản trị từ hồ sơ khách hàng.',
				array( 'status' => 403 )
			);
		}

		// Validate every identity field before writing anything, so a rejected edit leaves no partial update.
		$raw_phone  = '';
		$norm_phone = (string) get_user_meta( $user_id, '_jn_phone_e164', true );
		if ( isset( $data['phone'] ) && '' !== trim( (string) $data['phone'] ) ) {
			$raw_phone  = sanitize_text_field( (string) $data['phone'] );
			$norm_phone = JN_Customer_Service::normalize_vn_phone( $raw_phone );
			if ( '' === $norm_phone ) {
				return new \WP_Error(
					'invalid_phone',
					'Số điện thoại không hợp lệ. Vui lòng nhập số điện thoại Việt Nam (ví dụ: 0908 123 456 hoặc +84 908 123 456).'
				);
			}
		}

		$clean_email = strtolower( sanitize_email( $user->user_email ) );
		if ( isset( $data['email'] ) && '' !== trim( (string) $data['email'] ) ) {
			$clean_email = strtolower( sanitize_email( (string) $data['email'] ) );
			if ( '' === $clean_email || ! is_email( $clean_email ) ) {
				return new \WP_Error( 'invalid_email', 'Địa chỉ email không hợp lệ.' );
			}
			$email_owner = get_user_by( 'email', $clean_email );
			if ( $email_owner instanceof \WP_User && (int) $email_owner->ID !== $user_id ) {
				return new \WP_Error( 'email_in_use', 'Địa chỉ email này đã thuộc về một tài khoản khác.' );
			}
		}

		if ( $clean_email !== strtolower( $user->user_email ) ) {
			$updated = wp_update_user(
				array(
					'ID'         => $user_id,
					'user_email' => $clean_email,
				)
			);
			if ( is_wp_error( $updated ) ) {
				return $updated;
			}
		}

		if ( '' !== $raw_phone ) {
			update_user_meta( $user_id, '_jn_phone_e164', $norm_phone );
			update_user_meta( $user_id, '_jn_phone_raw', $raw_phone );
		}

		$full_name = isset( $data['full_name'] )
			? sanitize_text_field( trim( (string) $data['full_name'] ) )
			: (string) get_user_meta( $user_id, '_jn_full_name', true );

		if ( '' !== $full_name ) {
			update_user_meta( $user_id, '_jn_full_name', $full_name );
			update_user_meta( $user_id, 'first_name', $full_name );
			update_user_meta( $user_id, 'last_name', '' );
			wp_update_user(
				array(
					'ID'           => $user_id,
					'display_name' => $full_name,
				)
			);
		}

		if ( array_key_exists( 'customer_type', $data ) ) {
			$ctype = sanitize_key( (string) $data['customer_type'] );
			if ( '' === $ctype || array_key_exists( $ctype, self::get_customer_types() ) ) {
				update_user_meta( $user_id, '_jn_customer_type', $ctype );
			}
		}

		if ( array_key_exists( 'business_name', $data ) ) {
			$bname = sanitize_text_field( trim( (string) $data['business_name'] ) );
			update_user_meta( $user_id, '_jn_business_name', $bname );
		}

		if ( array_key_exists( 'province_city', $data ) ) {
			$city = sanitize_text_field( trim( (string) $data['province_city'] ) );
			update_user_meta( $user_id, '_jn_province_city', $city );
		}

		if ( array_key_exists( 'source_type', $data ) ) {
			$stype = sanitize_key( (string) $data['source_type'] );
			if ( array_key_exists( $stype, self::get_source_types() ) ) {
				update_user_meta( $user_id, '_jn_source_type', $stype );
			}
		}

		if ( array_key_exists( 'source_detail', $data ) ) {
			$sdetail = sanitize_text_field( trim( (string) $data['source_detail'] ) );
			update_user_meta( $user_id, '_jn_source_detail', $sdetail );
		}

		if ( array_key_exists( 'tags', $data ) ) {
			$tags = self::sanitize_tags( $data['tags'] );
			update_user_meta( $user_id, '_jn_customer_tags', $tags );
			update_user_meta( $user_id, '_jn_customer_tags_csv', implode( ',', $tags ) );
		}

		// Synchronize to WooCommerce `WC_Customer` billing fields & lookup table.
		self::sync_user_to_wc_customer( $user_id );

		// Also keep linked bookings' display name/phone in sync when updated by staff.
		self::sync_bookings_contact_for_user( $user_id, $full_name, $clean_email, $norm_phone );

		// Run duplicate check & log if phone or email matches another customer.
		JN_Customer_Service::check_and_log_duplicates_for_customer(
			'user:' . $user_id,
			$clean_email,
			$norm_phone,
			'profile_update'
		);

		return true;
	}

	/**
	 * Synchronize CRM user meta fields to WooCommerce `WC_Customer` billing fields and trigger
	 * WooCommerce customer lookup table update.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return void
	 */
	public static function sync_user_to_wc_customer( int $user_id ): void {
		// CRM fields mirror into WooCommerce for customers only; staff/admin names are never rewritten.
		if ( $user_id <= 0 || self::$is_syncing || JN_Booking_Roles::is_staff_user( $user_id ) ) {
			return;
		}

		self::$is_syncing = true;

		try {
			$full_name     = (string) get_user_meta( $user_id, '_jn_full_name', true );
			$phone_e164    = (string) get_user_meta( $user_id, '_jn_phone_e164', true );
			$business_name = (string) get_user_meta( $user_id, '_jn_business_name', true );
			$province_city = (string) get_user_meta( $user_id, '_jn_province_city', true );
			$user          = get_userdata( $user_id );
			$email         = $user instanceof \WP_User ? strtolower( sanitize_email( $user->user_email ) ) : '';

			if ( '' !== $full_name ) {
				update_user_meta( $user_id, 'billing_first_name', $full_name );
				update_user_meta( $user_id, 'billing_last_name', '' );
			}
			if ( '' !== $phone_e164 ) {
				update_user_meta( $user_id, 'billing_phone', $phone_e164 );
			}
			if ( '' !== $email ) {
				update_user_meta( $user_id, 'billing_email', $email );
			}
			update_user_meta( $user_id, 'billing_company', $business_name );
			update_user_meta( $user_id, 'billing_city', $province_city );
			update_user_meta( $user_id, 'billing_state', $province_city );
			update_user_meta( $user_id, 'billing_country', 'VN' );

			if ( class_exists( '\WC_Customer' ) ) {
				$wc_customer = new \WC_Customer( $user_id );
				if ( '' !== $full_name ) {
					$wc_customer->set_first_name( $full_name );
					$wc_customer->set_last_name( '' );
					$wc_customer->set_display_name( $full_name );
					$wc_customer->set_billing_first_name( $full_name );
					$wc_customer->set_billing_last_name( '' );
				}
				if ( '' !== $phone_e164 ) {
					$wc_customer->set_billing_phone( $phone_e164 );
				}
				if ( '' !== $email ) {
					$wc_customer->set_billing_email( $email );
				}
				$wc_customer->set_billing_company( $business_name );
				$wc_customer->set_billing_city( $province_city );
				$wc_customer->set_billing_state( $province_city );
				$wc_customer->set_billing_country( 'VN' );
				$wc_customer->save();
			}
		} catch ( \Exception $e ) {
			// Log IDs only (no PII); the CRM meta above is already saved, only the WC_Customer mirror failed.
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error(
					sprintf( 'sync_user_to_wc_customer failed for user #%d: %s', $user_id, $e->getMessage() ),
					array( 'source' => 'jn-core' )
				);
			}
		} finally {
			self::$is_syncing = false;
		}
	}

	/**
	 * Synchronize WooCommerce billing fields back into JN CRM fields when a customer saves
	 * their billing address in My Account or Checkout.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return void
	 */
	public static function sync_wc_billing_to_crm( int $user_id ): void {
		if ( $user_id <= 0 || self::$is_syncing ) {
			return;
		}

		self::$is_syncing = true;

		try {
			$billing_first   = sanitize_text_field( (string) get_user_meta( $user_id, 'billing_first_name', true ) );
			$billing_last    = sanitize_text_field( (string) get_user_meta( $user_id, 'billing_last_name', true ) );
			$billing_phone   = sanitize_text_field( (string) get_user_meta( $user_id, 'billing_phone', true ) );
			$billing_company = sanitize_text_field( (string) get_user_meta( $user_id, 'billing_company', true ) );
			$billing_city    = sanitize_text_field( (string) get_user_meta( $user_id, 'billing_city', true ) );
			$billing_state   = sanitize_text_field( (string) get_user_meta( $user_id, 'billing_state', true ) );

			$combined_name = trim( $billing_first . ' ' . $billing_last );
			if ( '' !== $combined_name ) {
				update_user_meta( $user_id, '_jn_full_name', $combined_name );
			}

			if ( '' !== $billing_phone ) {
				$norm = JN_Customer_Service::normalize_vn_phone( $billing_phone );
				if ( '' !== $norm ) {
					update_user_meta( $user_id, '_jn_phone_e164', $norm );
					update_user_meta( $user_id, '_jn_phone_raw', $billing_phone );
					update_user_meta( $user_id, 'billing_phone', $norm );
				}
			}

			if ( '' !== $billing_company ) {
				update_user_meta( $user_id, '_jn_business_name', $billing_company );
			}

			$city_val = '' !== $billing_city ? $billing_city : $billing_state;
			if ( '' !== $city_val ) {
				update_user_meta( $user_id, '_jn_province_city', $city_val );
			}
		} finally {
			self::$is_syncing = false;
		}
	}

	/**
	 * Append a staff-only internal note to a customer record (registered user or guest).
	 *
	 * @param string $customer_key Customer key (`user:123` or `guest:...`).
	 * @param string $content      Note text.
	 * @param int    $author_id    Staff user ID.
	 * @return array<string, mixed>|\WP_Error Created note entry or WP_Error.
	 */
	public static function add_internal_note( string $customer_key, string $content, int $author_id = 0 ) {
		$clean_content = sanitize_textarea_field( trim( $content ) );
		if ( '' === $clean_content ) {
			return new \WP_Error( 'empty_note', 'Nội dung ghi chú nội bộ không được để trống.' );
		}

		if ( $author_id <= 0 ) {
			$author_id = get_current_user_id();
		}
		$author_name = 'Nhân sự Học viện';
		if ( $author_id > 0 ) {
			$u = get_userdata( $author_id );
			if ( $u instanceof \WP_User ) {
				$author_name = $u->display_name;
			}
		}

		$tz     = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
		$now_vn = new \DateTimeImmutable( 'now', $tz );

		$entry = array(
			'id'          => uniqid( 'jnnote_', false ),
			'author_id'   => $author_id,
			'author_name' => $author_name,
			'timestamp'   => $now_vn->format( 'c' ),
			'content'     => $clean_content,
		);

		if ( 0 === strpos( $customer_key, 'user:' ) ) {
			$user_id = (int) substr( $customer_key, 5 );
			if ( $user_id <= 0 || ! get_userdata( $user_id ) ) {
				return new \WP_Error( 'invalid_user', 'Không tìm thấy khách hàng.' );
			}
			if ( JN_Booking_Roles::is_staff_user( $user_id ) ) {
				return new \WP_Error( 'forbidden_customer_target', 'Không thể ghi chú vào tài khoản nhân sự hoặc quản trị.', array( 'status' => 403 ) );
			}
			$notes = get_user_meta( $user_id, '_jn_internal_notes', true );
			if ( ! is_array( $notes ) ) {
				$notes = array();
			}
			array_unshift( $notes, $entry );
			update_user_meta( $user_id, '_jn_internal_notes', $notes );
			return $entry;
		}

		$guest_store = self::get_all_guest_profiles();
		$gkey        = self::normalize_guest_key( $customer_key );
		if ( ! isset( $guest_store[ $gkey ] ) ) {
			$guest_store[ $gkey ] = self::get_default_guest_profile_struct( $gkey );
		}
		if ( ! isset( $guest_store[ $gkey ]['internal_notes'] ) || ! is_array( $guest_store[ $gkey ]['internal_notes'] ) ) {
			$guest_store[ $gkey ]['internal_notes'] = array();
		}
		array_unshift( $guest_store[ $gkey ]['internal_notes'], $entry );
		update_option( self::GUEST_PROFILES_OPTION, $guest_store, false );

		return $entry;
	}

	/**
	 * Append an immutable consent log entry to a customer record (registered user or guest).
	 *
	 * @param string               $customer_key Customer key (`user:123` or `guest:...`).
	 * @param array<string, mixed> $consent_item Consent metadata (`timestamp`, `source`, `policy_page_id`, `policy_version`, `text_snapshot`, `reference`).
	 * @return void
	 */
	public static function append_consent_log( string $customer_key, array $consent_item ): void {
		$tz     = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
		$now_vn = new \DateTimeImmutable( 'now', $tz );

		$normalized_item = array(
			'timestamp'      => ! empty( $consent_item['timestamp'] ) ? sanitize_text_field( (string) $consent_item['timestamp'] ) : $now_vn->format( 'c' ),
			'source'         => ! empty( $consent_item['source'] ) ? sanitize_text_field( (string) $consent_item['source'] ) : 'Biểu mẫu đặt lịch',
			'policy_page_id' => isset( $consent_item['policy_page_id'] ) ? (int) $consent_item['policy_page_id'] : (int) get_option( 'wp_page_for_privacy_policy', 15 ),
			'policy_version' => ! empty( $consent_item['policy_version'] ) ? sanitize_text_field( (string) $consent_item['policy_version'] ) : '1.0',
			'text_snapshot'  => ! empty( $consent_item['text_snapshot'] ) ? sanitize_textarea_field( (string) $consent_item['text_snapshot'] ) : JN_Booking_Settings::DEFAULT_CONSENT_TEXT,
			'reference'      => ! empty( $consent_item['reference'] ) ? sanitize_text_field( (string) $consent_item['reference'] ) : '',
		);

		if ( 0 === strpos( $customer_key, 'user:' ) ) {
			$user_id = (int) substr( $customer_key, 5 );
			if ( $user_id <= 0 ) {
				return;
			}
			$log = get_user_meta( $user_id, '_jn_consent_log', true );
			if ( ! is_array( $log ) ) {
				$log = array();
			}
			foreach ( $log as $existing ) {
				if (
					isset( $existing['reference'], $existing['timestamp'] )
					&& '' !== $normalized_item['reference']
					&& $existing['reference'] === $normalized_item['reference']
				) {
					return;
				}
			}
			array_unshift( $log, $normalized_item );
			update_user_meta( $user_id, '_jn_consent_log', $log );
			return;
		}

		$guest_store = self::get_all_guest_profiles();
		$gkey        = self::normalize_guest_key( $customer_key );
		if ( ! isset( $guest_store[ $gkey ] ) ) {
			$guest_store[ $gkey ] = self::get_default_guest_profile_struct( $gkey );
		}
		if ( ! isset( $guest_store[ $gkey ]['consent_log'] ) || ! is_array( $guest_store[ $gkey ]['consent_log'] ) ) {
			$guest_store[ $gkey ]['consent_log'] = array();
		}
		foreach ( $guest_store[ $gkey ]['consent_log'] as $existing ) {
			if (
				isset( $existing['reference'] )
				&& '' !== $normalized_item['reference']
				&& $existing['reference'] === $normalized_item['reference']
			) {
				return;
			}
		}
		array_unshift( $guest_store[ $gkey ]['consent_log'], $normalized_item );
		update_option( self::GUEST_PROFILES_OPTION, $guest_store, false );
	}

	/**
	 * Update CRM profile for a guest customer (`guest:<email_or_phone>`).
	 *
	 * @param string               $customer_key Guest customer key.
	 * @param array<string, mixed> $data         Profile fields to update.
	 * @return true|\WP_Error
	 */
	public static function update_guest_profile( string $customer_key, array $data ) {
		$gkey        = self::normalize_guest_key( $customer_key );
		$guest_store = self::get_all_guest_profiles();

		if ( ! isset( $guest_store[ $gkey ] ) ) {
			$guest_store[ $gkey ] = self::get_default_guest_profile_struct( $gkey );
		}

		$record = $guest_store[ $gkey ];

		if ( isset( $data['full_name'] ) ) {
			$record['full_name'] = sanitize_text_field( trim( (string) $data['full_name'] ) );
		}

		if ( isset( $data['phone'] ) && '' !== trim( (string) $data['phone'] ) ) {
			$raw_phone  = sanitize_text_field( (string) $data['phone'] );
			$norm_phone = JN_Customer_Service::normalize_vn_phone( $raw_phone );
			if ( '' === $norm_phone ) {
				return new \WP_Error( 'invalid_phone', 'Số điện thoại không hợp lệ.' );
			}
			$record['phone']     = $norm_phone;
			$record['phone_raw'] = $raw_phone;
		}

		if ( isset( $data['email'] ) && '' !== trim( (string) $data['email'] ) ) {
			$clean_email = strtolower( sanitize_email( (string) $data['email'] ) );
			if ( '' === $clean_email || ! is_email( $clean_email ) ) {
				return new \WP_Error( 'invalid_email', 'Địa chỉ email không hợp lệ.' );
			}
			$record['email'] = $clean_email;
		}

		if ( array_key_exists( 'customer_type', $data ) ) {
			$ctype = sanitize_key( (string) $data['customer_type'] );
			if ( '' === $ctype || array_key_exists( $ctype, self::get_customer_types() ) ) {
				$record['customer_type'] = $ctype;
			}
		}

		if ( array_key_exists( 'business_name', $data ) ) {
			$record['business_name'] = sanitize_text_field( trim( (string) $data['business_name'] ) );
		}

		if ( array_key_exists( 'province_city', $data ) ) {
			$record['province_city'] = sanitize_text_field( trim( (string) $data['province_city'] ) );
		}

		if ( array_key_exists( 'source_type', $data ) ) {
			$stype = sanitize_key( (string) $data['source_type'] );
			if ( array_key_exists( $stype, self::get_source_types() ) ) {
				$record['source_type'] = $stype;
			}
		}

		if ( array_key_exists( 'source_detail', $data ) ) {
			$record['source_detail'] = sanitize_text_field( trim( (string) $data['source_detail'] ) );
		}

		if ( array_key_exists( 'tags', $data ) ) {
			$record['tags'] = self::sanitize_tags( $data['tags'] );
		}

		$guest_store[ $gkey ] = $record;
		update_option( self::GUEST_PROFILES_OPTION, $guest_store, false );

		JN_Customer_Service::check_and_log_duplicates_for_customer(
			$gkey,
			(string) ( $record['email'] ?? '' ),
			(string) ( $record['phone'] ?? '' ),
			'guest_profile_update'
		);

		return true;
	}

	/**
	 * Retrieve all stored guest CRM profiles from `wp_options`.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_all_guest_profiles(): array {
		$raw = get_option( self::GUEST_PROFILES_OPTION, array() );
		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * Return stored guest profile for a specific guest key (`guest:<email_or_phone>`).
	 *
	 * @param string $customer_key Guest key.
	 * @return array<string, mixed>
	 */
	public static function get_guest_profile_data( string $customer_key ): array {
		$gkey  = self::normalize_guest_key( $customer_key );
		$store = self::get_all_guest_profiles();
		$data  = isset( $store[ $gkey ] ) && is_array( $store[ $gkey ] )
			? $store[ $gkey ]
			: self::get_default_guest_profile_struct( $gkey );
		if ( ! isset( $data['phone_e164'] ) ) {
			$data['phone_e164'] = (string) ( $data['phone'] ?? '' );
		}
		return $data;
	}

	/**
	 * Alias for `get_guest_profile_data()`.
	 *
	 * @param string $customer_key Guest key.
	 * @return array<string, mixed>
	 */
	public static function get_guest_profile( string $customer_key ): array {
		return self::get_guest_profile_data( $customer_key );
	}

	/**
	 * Delete a stored guest CRM profile by key (`guest:<email_or_phone>`).
	 *
	 * @param string $customer_key Guest key.
	 * @return bool True if an existing profile was deleted.
	 */
	public static function delete_guest_profile( string $customer_key ): bool {
		$gkey  = self::normalize_guest_key( $customer_key );
		$store = self::get_all_guest_profiles();
		if ( ! isset( $store[ $gkey ] ) ) {
			return false;
		}
		unset( $store[ $gkey ] );
		update_option( self::GUEST_PROFILES_OPTION, $store, false );
		return true;
	}

	/**
	 * Merge a guest CRM profile (notes, tags, consent log, business name, type, province)
	 * into a registered `WP_User` when the guest registers or logs in.
	 *
	 * @param int    $user_id   WordPress User ID.
	 * @param string $guest_key Guest profile key (`guest:...`).
	 * @return void
	 */
	public static function merge_guest_profile_into_user( int $user_id, string $guest_key ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		$gkey  = self::normalize_guest_key( $guest_key );
		$store = self::get_all_guest_profiles();
		if ( ! isset( $store[ $gkey ] ) || ! is_array( $store[ $gkey ] ) ) {
			return;
		}

		$guest = $store[ $gkey ];

		$scalar_map = array(
			'full_name'     => '_jn_full_name',
			'phone'         => '_jn_phone_e164',
			'phone_raw'     => '_jn_phone_raw',
			'customer_type' => '_jn_customer_type',
			'business_name' => '_jn_business_name',
			'province_city' => '_jn_province_city',
			'source_type'   => '_jn_source_type',
			'source_detail' => '_jn_source_detail',
		);

		foreach ( $scalar_map as $gfield => $meta_key ) {
			if ( ! empty( $guest[ $gfield ] ) && '' === (string) get_user_meta( $user_id, $meta_key, true ) ) {
				update_user_meta( $user_id, $meta_key, $guest[ $gfield ] );
			}
		}

		if ( ! empty( $guest['tags'] ) && is_array( $guest['tags'] ) ) {
			$existing_tags = get_user_meta( $user_id, '_jn_customer_tags', true );
			$merged_tags   = self::sanitize_tags(
				array_merge(
					is_array( $existing_tags ) ? $existing_tags : array(),
					$guest['tags']
				)
			);
			update_user_meta( $user_id, '_jn_customer_tags', $merged_tags );
			update_user_meta( $user_id, '_jn_customer_tags_csv', implode( ',', $merged_tags ) );
		}

		if ( ! empty( $guest['internal_notes'] ) && is_array( $guest['internal_notes'] ) ) {
			$existing_notes = get_user_meta( $user_id, '_jn_internal_notes', true );
			if ( ! is_array( $existing_notes ) ) {
				$existing_notes = array();
			}
			$merged_notes = array_merge( $existing_notes, $guest['internal_notes'] );
			update_user_meta( $user_id, '_jn_internal_notes', $merged_notes );
		}

		if ( ! empty( $guest['consent_log'] ) && is_array( $guest['consent_log'] ) ) {
			foreach ( $guest['consent_log'] as $c_item ) {
				if ( is_array( $c_item ) ) {
					self::append_consent_log( 'user:' . $user_id, $c_item );
				}
			}
		}

		unset( $store[ $gkey ] );
		update_option( self::GUEST_PROFILES_OPTION, $store, false );

		self::sync_user_to_wc_customer( $user_id );
	}

	/**
	 * Normalize a guest key into `guest:<identifier>`.
	 *
	 * @param string $raw_key Raw key or email/phone.
	 * @return string
	 */
	public static function normalize_guest_key( string $raw_key ): string {
		$trimmed = strtolower( trim( $raw_key ) );
		if ( 0 === strpos( $trimmed, 'guest:' ) ) {
			return $trimmed;
		}
		return 'guest:' . $trimmed;
	}

	/**
	 * Default empty structure for a guest CRM profile.
	 *
	 * @param string $gkey Normalized guest key.
	 * @return array<string, mixed>
	 */
	private static function get_default_guest_profile_struct( string $gkey ): array {
		$ident = 0 === strpos( $gkey, 'guest:' ) ? substr( $gkey, 6 ) : $gkey;
		$email = is_email( $ident ) ? $ident : '';
		$phone = '' === $email ? JN_Customer_Service::normalize_vn_phone( $ident ) : '';

		return array(
			'customer_key'   => $gkey,
			'full_name'      => '',
			'phone'          => $phone,
			'phone_e164'     => $phone,
			'phone_raw'      => $phone,
			'email'          => $email,
			'customer_type'  => '',
			'business_name'  => '',
			'province_city'  => '',
			'source_type'    => self::SOURCE_FORM,
			'source_detail'  => '',
			'tags'           => array(),
			'internal_notes' => array(),
			'consent_log'    => array(),
		);
	}

	/**
	 * Keep linked booking records' contact fields in sync when a user's profile is updated.
	 *
	 * @param int    $user_id          User ID.
	 * @param string $full_name        Full name.
	 * @param string $email            Email.
	 * @param string $normalized_phone Normalized E.164 phone.
	 * @return void
	 */
	private static function sync_bookings_contact_for_user( int $user_id, string $full_name, string $email, string $normalized_phone ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		$booking_ids = get_posts(
			array(
				'post_type'      => JN_Booking_CPT::POST_TYPE,
				'post_status'    => array_keys( JN_Booking_CPT::get_statuses() ),
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_jn_customer_user_id',
						'value' => $user_id,
						'type'  => 'NUMERIC',
					),
				),
			)
		);

		foreach ( $booking_ids as $bid ) {
			$bid = (int) $bid;
			if ( '' !== $full_name ) {
				update_post_meta( $bid, '_jn_customer_name', $full_name );
			}
			if ( '' !== $email ) {
				update_post_meta( $bid, '_jn_customer_email', $email );
			}
			if ( '' !== $normalized_phone ) {
				update_post_meta( $bid, '_jn_customer_phone', $normalized_phone );
			}
		}
	}

	/**
	 * Hook: Synchronize CRM fields when a customer saves their billing address in My Account.
	 *
	 * @param int    $user_id      User ID.
	 * @param string $address_type Address type (`billing` or `shipping`).
	 * @return void
	 */
	public static function handle_wc_address_saved( int $user_id, string $address_type = 'billing' ): void {
		if ( 'billing' === $address_type && $user_id > 0 ) {
			self::sync_wc_billing_to_crm( $user_id );
		}
	}

	/**
	 * Hook: Synchronize CRM fields when a customer checks out via WooCommerce.
	 *
	 * @param \WC_Customer         $customer WC_Customer object.
	 * @param array<string, mixed> $data     Posted checkout data.
	 * @return void
	 */
	public static function handle_wc_checkout_customer( $customer, array $data = array() ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( $customer instanceof \WC_Customer && $customer->get_id() > 0 ) {
			self::sync_wc_billing_to_crm( (int) $customer->get_id() );
		}
	}

	/**
	 * Hook: When a WooCommerce order is created or updated, sync customer contact & consent log.
	 *
	 * @param int            $order_id Order ID.
	 * @param \WC_Order|null $order    Order object.
	 * @return void
	 */
	public static function handle_wc_order_saved( int $order_id, $order = null ): void {
		if ( ! $order instanceof \WC_Order && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$customer_id = (int) $order->get_customer_id();
		$email       = strtolower( sanitize_email( $order->get_billing_email() ) );
		$raw_phone   = (string) $order->get_billing_phone();
		$norm_phone  = JN_Customer_Service::normalize_vn_phone( $raw_phone );
		$full_name   = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		$company     = sanitize_text_field( (string) $order->get_billing_company() );
		$city        = sanitize_text_field( (string) $order->get_billing_city() );

		if ( $customer_id > 0 ) {
			JN_Customer_Service::sync_user_contact_meta( $customer_id, $full_name, $email, $norm_phone );
			if ( '' !== $company && '' === (string) get_user_meta( $customer_id, '_jn_business_name', true ) ) {
				update_user_meta( $customer_id, '_jn_business_name', $company );
			}
			if ( '' !== $city && '' === (string) get_user_meta( $customer_id, '_jn_province_city', true ) ) {
				update_user_meta( $customer_id, '_jn_province_city', $city );
			}
			if ( '' === (string) get_user_meta( $customer_id, '_jn_source_type', true ) ) {
				update_user_meta( $customer_id, '_jn_source_type', self::SOURCE_CHECKOUT );
			}
			JN_Customer_Service::check_and_log_duplicates_for_customer(
				'user:' . $customer_id,
				$email,
				$norm_phone,
				'order_save'
			);
		} elseif ( '' !== $email || '' !== $norm_phone ) {
			$gkey = 'guest:' . ( '' !== $email ? $email : $norm_phone );
			JN_Customer_Service::check_and_log_duplicates_for_customer(
				$gkey,
				$email,
				$norm_phone,
				'guest_order_save'
			);
		}
	}

	/**
	 * Hook: When a booking is created, record consent log, source metadata, and duplicate check on the customer profile.
	 *
	 * @param int $booking_id Booking post ID.
	 * @return void
	 */
	public static function handle_booking_created( int $booking_id ): void {
		$user_id     = (int) get_post_meta( $booking_id, '_jn_customer_user_id', true );
		$name        = (string) get_post_meta( $booking_id, '_jn_customer_name', true );
		$phone       = (string) get_post_meta( $booking_id, '_jn_customer_phone', true );
		$phone_raw   = (string) get_post_meta( $booking_id, '_jn_customer_phone_raw', true );
		$email       = strtolower( sanitize_email( (string) get_post_meta( $booking_id, '_jn_customer_email', true ) ) );
		$code        = (string) get_post_meta( $booking_id, '_jn_booking_code', true );
		$consent_ts  = (string) get_post_meta( $booking_id, '_jn_consent_timestamp', true );
		$policy_pid  = (int) get_post_meta( $booking_id, '_jn_consent_policy_page_id', true );
		$policy_ver  = (string) get_post_meta( $booking_id, '_jn_consent_policy_version', true );
		$consent_txt = (string) get_post_meta( $booking_id, '_jn_consent_text_snapshot', true );
		$source_meta = get_post_meta( $booking_id, '_jn_source', true );

		$source_type   = self::SOURCE_FORM;
		$source_detail = '';
		if ( is_array( $source_meta ) ) {
			if ( ! empty( $source_meta['source_type'] ) && array_key_exists( (string) $source_meta['source_type'], self::get_source_types() ) ) {
				$source_type = (string) $source_meta['source_type'];
			} elseif ( ! empty( $source_meta['utm'] ) && is_array( $source_meta['utm'] ) ) {
				$source_type = self::SOURCE_UTM;
			}

			$parts = array();
			if ( ! empty( $source_meta['utm'] ) && is_array( $source_meta['utm'] ) ) {
				foreach ( $source_meta['utm'] as $uk => $uv ) {
					$parts[] = $uk . '=' . $uv;
				}
			}
			if ( ! empty( $source_meta['landing_url'] ) ) {
				$parts[] = (string) $source_meta['landing_url'];
			}
			$source_detail = implode( ' | ', $parts );
		}

		$customer_key = $user_id > 0
			? 'user:' . $user_id
			: 'guest:' . ( '' !== $email ? $email : $phone );

		// Only the account owner (or staff entering a phone/walk-in booking) may fill account fields;
		// an anonymous submission attached to a verified email must not plant data in that account.
		$may_fill_account = $user_id > 0
			&& ( get_current_user_id() === $user_id || JN_Booking_Roles::user_can_manage_customers() );

		if ( $may_fill_account ) {
			if ( '' === (string) get_user_meta( $user_id, '_jn_full_name', true ) && '' !== $name ) {
				update_user_meta( $user_id, '_jn_full_name', $name );
			}
			// Fill-only: a booking submission (possibly anonymous) must never replace a stored account phone.
			if ( '' !== $phone && '' === (string) get_user_meta( $user_id, '_jn_phone_e164', true ) ) {
				update_user_meta( $user_id, '_jn_phone_e164', $phone );
			}
			if ( '' !== $phone_raw && '' === (string) get_user_meta( $user_id, '_jn_phone_raw', true ) ) {
				update_user_meta( $user_id, '_jn_phone_raw', $phone_raw );
			}
			if ( '' === (string) get_user_meta( $user_id, '_jn_source_type', true ) ) {
				update_user_meta( $user_id, '_jn_source_type', $source_type );
			}
			if ( '' === (string) get_user_meta( $user_id, '_jn_source_detail', true ) && '' !== $source_detail ) {
				update_user_meta( $user_id, '_jn_source_detail', $source_detail );
			}
			self::sync_user_to_wc_customer( $user_id );
		} elseif ( 0 === $user_id ) {
			$gprofile = self::get_guest_profile_data( $customer_key );
			$updates  = array(
				'full_name' => '' !== $gprofile['full_name'] ? $gprofile['full_name'] : $name,
				'phone'     => '' !== $phone ? $phone : $gprofile['phone'],
				'email'     => '' !== $email ? $email : $gprofile['email'],
			);
			if ( '' === $gprofile['source_detail'] && '' !== $source_detail ) {
				$updates['source_type']   = $source_type;
				$updates['source_detail'] = $source_detail;
			}
			self::update_guest_profile( $customer_key, $updates );
		}

		if ( '' !== $consent_ts ) {
			self::append_consent_log(
				$customer_key,
				array(
					'timestamp'      => $consent_ts,
					'source'         => self::get_source_type_label( $source_type ),
					'policy_page_id' => $policy_pid,
					'policy_version' => $policy_ver,
					'text_snapshot'  => $consent_txt,
					'reference'      => $code,
				)
			);
		}

		JN_Customer_Service::check_and_log_duplicates_for_customer(
			$customer_key,
			$email,
			$phone,
			'booking_created:' . $code
		);
	}

	/**
	 * Render CRM fields on the WordPress Edit User (`user-edit.php` / `profile.php`) screen.
	 *
	 * @param \WP_User $user User being edited.
	 * @return void
	 */
	public static function render_wp_user_profile_fields( \WP_User $user ): void {
		if ( ! JN_Booking_Roles::user_can_manage_customers() ) {
			return;
		}

		$profile = self::get_user_profile( (int) $user->ID );
		$types   = self::get_customer_types();
		$sources = self::get_source_types();
		wp_nonce_field( 'jn_wp_user_crm_save_' . $user->ID, 'jn_wp_user_crm_nonce' );
		?>
		<h2>Hồ Sơ Khách Hàng CRM — Jannie Nguyen Academy</h2>
		<p class="description">
			Các trường dưới đây được đồng bộ tự động 2 chiều với thông tin thanh toán WooCommerce (Customers &amp; Analytics) và màn hình <a href="<?php echo esc_url( admin_url( 'admin.php?page=jn-customers&action=view&customer_key=user:' . (int) $user->ID ) ); ?>"><strong>Khách hàng → Chi tiết hồ sơ</strong></a>.
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="jn-crm-full-name">Họ và tên khách hàng</label></th>
				<td>
					<input type="text" id="jn-crm-full-name" name="jn_crm_full_name" value="<?php echo esc_attr( (string) $profile['full_name'] ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="jn-crm-phone">Số điện thoại / Zalo (+84…)</label></th>
				<td>
					<input type="tel" id="jn-crm-phone" name="jn_crm_phone" value="<?php echo esc_attr( (string) $profile['phone'] ); ?>" class="regular-text" placeholder="+84908123456" />
					<p class="description">Nhập định dạng bất kỳ (<code>0908...</code> hoặc <code>+84908...</code>), hệ thống tự chuẩn hóa về chuẩn quốc tế <code>+84...</code>.</p>
				</td>
			</tr>
			<tr>
				<th><label for="jn-crm-customer-type">Loại khách hàng</label></th>
				<td>
					<select id="jn-crm-customer-type" name="jn_crm_customer_type">
						<option value="">— Chưa phân loại —</option>
						<?php foreach ( $types as $tkey => $tlabel ) : ?>
							<option value="<?php echo esc_attr( $tkey ); ?>" <?php selected( $tkey, (string) $profile['customer_type'] ); ?>><?php echo esc_html( $tlabel ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="jn-crm-business-name">Tên cơ sở (Spa / Thẩm mỹ viện)</label></th>
				<td>
					<input type="text" id="jn-crm-business-name" name="jn_crm_business_name" value="<?php echo esc_attr( (string) $profile['business_name'] ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="jn-crm-province-city">Tỉnh / Thành phố</label></th>
				<td>
					<input type="text" id="jn-crm-province-city" name="jn_crm_province_city" value="<?php echo esc_attr( (string) $profile['province_city'] ); ?>" class="regular-text" placeholder="TP. Hồ Chí Minh" />
				</td>
			</tr>
			<tr>
				<th><label for="jn-crm-source-type">Nguồn khách hàng</label></th>
				<td>
					<select id="jn-crm-source-type" name="jn_crm_source_type">
						<?php foreach ( $sources as $skey => $slabel ) : ?>
							<option value="<?php echo esc_attr( $skey ); ?>" <?php selected( $skey, (string) $profile['source_type'] ); ?>><?php echo esc_html( $slabel ); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="text" name="jn_crm_source_detail" value="<?php echo esc_attr( (string) $profile['source_detail'] ); ?>" class="regular-text" placeholder="Chi tiết UTM / Người giới thiệu / URL" style="margin-left:8px;" />
				</td>
			</tr>
			<tr>
				<th><label for="jn-crm-tags">Tags phân nhóm</label></th>
				<td>
					<input type="text" id="jn-crm-tags" name="jn_crm_tags" value="<?php echo esc_attr( implode( ', ', (array) $profile['tags'] ) ); ?>" class="regular-text" placeholder="VIP, Mentorship, Chủ Spa" />
					<p class="description">Phân cách các tag bằng dấu phẩy.</p>
				</td>
			</tr>
			<tr>
				<th>Nhật ký đồng ý bảo mật (Chỉ đọc)</th>
				<td>
					<?php if ( empty( $profile['consent_log'] ) ) : ?>
						<span class="description">Chưa có bản ghi đồng ý bảo mật.</span>
					<?php else : ?>
						<ul style="margin:0;padding-left:18px;">
							<?php foreach ( $profile['consent_log'] as $clog ) : ?>
								<li>
									<strong><?php echo esc_html( (string) ( $clog['timestamp'] ?? '' ) ); ?></strong>
									— <?php echo esc_html( (string) ( $clog['source'] ?? '' ) ); ?>
									(Phiên bản CSBM: <code><?php echo esc_html( (string) ( $clog['policy_version'] ?? '1.0' ) ); ?></code>
									<?php if ( ! empty( $clog['reference'] ) ) : ?>
										, Mã: <code><?php echo esc_html( (string) $clog['reference'] ); ?></code>
									<?php endif; ?>)
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save CRM fields submitted from WordPress `user-edit.php` / `profile.php`.
	 *
	 * @param int $user_id User ID being updated.
	 * @return void
	 */
	public static function handle_wp_user_profile_save( int $user_id ): void {
		if ( ! JN_Booking_Roles::user_can_manage_customers() || ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if (
			! isset( $_POST['jn_wp_user_crm_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jn_wp_user_crm_nonce'] ) ), 'jn_wp_user_crm_save_' . $user_id )
		) {
			return;
		}

		$update_payload = array(
			'full_name'     => isset( $_POST['jn_crm_full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['jn_crm_full_name'] ) ) : '',
			'customer_type' => isset( $_POST['jn_crm_customer_type'] ) ? sanitize_key( wp_unslash( $_POST['jn_crm_customer_type'] ) ) : '',
			'business_name' => isset( $_POST['jn_crm_business_name'] ) ? sanitize_text_field( wp_unslash( $_POST['jn_crm_business_name'] ) ) : '',
			'province_city' => isset( $_POST['jn_crm_province_city'] ) ? sanitize_text_field( wp_unslash( $_POST['jn_crm_province_city'] ) ) : '',
			'source_type'   => isset( $_POST['jn_crm_source_type'] ) ? sanitize_key( wp_unslash( $_POST['jn_crm_source_type'] ) ) : self::SOURCE_FORM,
			'source_detail' => isset( $_POST['jn_crm_source_detail'] ) ? sanitize_text_field( wp_unslash( $_POST['jn_crm_source_detail'] ) ) : '',
			'tags'          => isset( $_POST['jn_crm_tags'] ) ? sanitize_text_field( wp_unslash( $_POST['jn_crm_tags'] ) ) : '',
		);

		if ( isset( $_POST['jn_crm_phone'] ) && '' !== trim( sanitize_text_field( wp_unslash( $_POST['jn_crm_phone'] ) ) ) ) {
			$update_payload['phone'] = sanitize_text_field( wp_unslash( $_POST['jn_crm_phone'] ) );
		}

		self::update_user_profile( $user_id, $update_payload );
	}
}
