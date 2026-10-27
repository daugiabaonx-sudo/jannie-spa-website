<?php
/**
 * Customer Upsert, Phone Search, Guest-to-Account Linking, Duplicate Detection & Unified CRM Directory.
 *
 * - Normalizes valid Vietnamese mobile and landline numbers to E.164 (`+84...`) and parses any search format (`0908...`, `+84908...`).
 * - Matches existing customers by email first, then by normalized phone.
 * - Creates a WooCommerce customer on booking only when `auto_create_account === 'yes'`
 *   (default `'no'` per Project Context Card).
 * - Automatically attaches guest bookings AND guest WooCommerce HPOS orders with the same verified email
 *   to the account on registration or login, merging guest CRM profile fields and consent logs.
 * - Detects and logs duplicate warnings when phone or email matches another customer (`jn_customer_duplicate_log`).
 * - Aggregates all customers (from bookings, orders, or registrations) for the unified "Khách hàng" screen.
 * - Provides staff "Tạo lịch hẹn" walk-in / phone booking creation.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Customer_Service
 */
class JN_Customer_Service {

	/**
	 * Option key for storing customer duplicate conflict audit logs.
	 */
	const DUPLICATE_LOG_OPTION = 'jn_customer_duplicate_log';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'user_register', array( __CLASS__, 'handle_user_registered' ), 20, 1 );
		add_action( 'woocommerce_created_customer', array( __CLASS__, 'handle_wc_customer_created' ), 20, 1 );
		add_action( 'woocommerce_save_account_details', array( __CLASS__, 'handle_user_registered' ), 20, 1 );
		add_action( 'wp_login', array( __CLASS__, 'handle_user_login' ), 20, 2 );
	}

	/**
	 * Validate and normalize a Vietnamese phone number to E.164 (`+84...`).
	 *
	 * Supported valid formats:
	 * - Mobile (9 national digits starting with 3, 5, 7, 8, 9):
	 *   `090 123 4567`, `0912345678`, `038.123.4567`, `+84 90 123 4567`, `84901234567`, `0084901234567`
	 * - Landline (9 or 10 national digits starting with 2):
	 *   `028 3812 3456`, `(024) 38123456`, `+84 28 3812 3456`
	 *
	 * Returns empty string `''` if the input is not a valid Vietnamese phone number.
	 *
	 * @param string $raw Raw phone number string.
	 * @return string Normalized `+84...` phone or `''` if invalid.
	 */
	public static function normalize_vn_phone( string $raw ): string {
		$trimmed = trim( $raw );
		if ( '' === $trimmed ) {
			return '';
		}

		// Reject if raw string contains alphabetic characters or disallowed symbols.
		if ( preg_match( '/[^0-9+\s.\-()]/', $trimmed ) ) {
			return '';
		}

		// Strip formatting separators (spaces, dots, hyphens, parentheses).
		$compact = (string) preg_replace( '/[\s.\-()]+/', '', $trimmed );

		// Only a single optional leading '+' is allowed.
		if ( substr_count( $compact, '+' ) > 1 || ( false !== strpos( $compact, '+' ) && 0 !== strpos( $compact, '+' ) ) ) {
			return '';
		}

		$national = '';
		if ( 0 === strpos( $compact, '+84' ) ) {
			$national = substr( $compact, 3 );
		} elseif ( 0 === strpos( $compact, '0084' ) ) {
			$national = substr( $compact, 4 );
		} elseif ( 0 === strpos( $compact, '84' ) && strlen( $compact ) >= 11 ) {
			$national = substr( $compact, 2 );
		} elseif ( 0 === strpos( $compact, '0' ) ) {
			$national = substr( $compact, 1 );
		} else {
			return '';
		}

		// Allow an optional redundant '0' immediately after +84 (e.g. "+84 (0)90 123 4567").
		if ( 0 === strpos( $national, '0' ) && strlen( $national ) >= 10 ) {
			$national = substr( $national, 1 );
		}

		// Mobile: [3|5|7|8|9] followed by 8 digits (total 9 national digits).
		if ( preg_match( '/^[35789][0-9]{8}$/', $national ) ) {
			return '+84' . $national;
		}

		// Fixed landline: 2 followed by 8 or 9 digits (total 9 or 10 national digits).
		if ( preg_match( '/^2[0-9]{8,9}$/', $national ) ) {
			return '+84' . $national;
		}

		return '';
	}

	/**
	 * Convert a normalized `+84...` phone number to local `0...` form for search/comparison.
	 *
	 * @param string $normalized_phone Normalized `+84...` phone.
	 * @return string
	 */
	public static function to_local_phone( string $normalized_phone ): string {
		if ( 0 === strpos( $normalized_phone, '+84' ) ) {
			return '0' . substr( $normalized_phone, 3 );
		}
		return $normalized_phone;
	}

	/**
	 * Parse a search query into canonical phone variants (`e164`, `local`, `national`)
	 * so searching by `0908...`, `+84908...`, `84908...`, or `0908 123 456` matches seamlessly.
	 *
	 * @param string $search Raw search string.
	 * @return array{is_phone_like: bool, e164: string, local: string, national: string}
	 */
	public static function parse_phone_search_variants( string $search ): array {
		$trimmed = trim( $search );
		if ( '' === $trimmed || preg_match( '/[^0-9+\s.\-()]/', $trimmed ) ) {
			return array(
				'is_phone_like' => false,
				'e164'          => '',
				'local'         => '',
				'national'      => '',
			);
		}

		$compact = (string) preg_replace( '/[\s.\-()]+/', '', $trimmed );
		$digits  = (string) preg_replace( '/[^0-9]/', '', $compact );
		if ( strlen( $digits ) < 3 ) {
			return array(
				'is_phone_like' => false,
				'e164'          => '',
				'local'         => '',
				'national'      => '',
			);
		}

		$full_norm = self::normalize_vn_phone( $trimmed );
		if ( '' !== $full_norm ) {
			$national = substr( $full_norm, 3 );
			return array(
				'is_phone_like' => true,
				'e164'          => $full_norm,
				'local'         => '0' . $national,
				'national'      => $national,
			);
		}

		// Handle partial phone prefix queries such as "0908", "+84908", "84908".
		$national = $digits;
		if ( 0 === strpos( $compact, '+84' ) ) {
			$national = substr( $compact, 3 );
		} elseif ( 0 === strpos( $compact, '0084' ) ) {
			$national = substr( $compact, 4 );
		} elseif ( 0 === strpos( $compact, '84' ) && strlen( $compact ) >= 4 ) {
			$national = substr( $compact, 2 );
		} elseif ( 0 === strpos( $compact, '0' ) ) {
			$national = substr( $compact, 1 );
		}

		if ( 0 === strpos( $national, '0' ) && strlen( $national ) >= 2 ) {
			$national = substr( $national, 1 );
		}

		return array(
			'is_phone_like' => '' !== $national,
			'e164'          => '' !== $national ? '+84' . $national : '',
			'local'         => '' !== $national ? '0' . $national : '',
			'national'      => $national,
		);
	}

	/**
	 * Check whether a customer record's phone matches a phone search query in any format.
	 *
	 * @param string $record_phone   Normalized `+84...` or raw phone on the customer record.
	 * @param string $search_query   Raw search query (`0908...`, `+84908...`, etc.).
	 * @return bool
	 */
	public static function phone_matches_search( string $record_phone, string $search_query ): bool {
		if ( '' === $record_phone || '' === $search_query ) {
			return false;
		}

		$variants = self::parse_phone_search_variants( $search_query );
		if ( ! $variants['is_phone_like'] ) {
			return false;
		}

		$norm_rec     = self::normalize_vn_phone( $record_phone );
		$rec_e164     = '' !== $norm_rec ? $norm_rec : $record_phone;
		$rec_local    = '' !== $norm_rec ? self::to_local_phone( $norm_rec ) : $record_phone;
		$rec_national = '' !== $norm_rec ? substr( $norm_rec, 3 ) : (string) preg_replace( '/[^0-9]/', '', $record_phone );

		if ( '' !== $variants['e164'] && false !== strpos( $rec_e164, $variants['e164'] ) ) {
			return true;
		}
		if ( '' !== $variants['local'] && false !== strpos( $rec_local, $variants['local'] ) ) {
			return true;
		}
		if ( '' !== $variants['national'] && false !== strpos( $rec_national, $variants['national'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Find an existing WordPress/WooCommerce user by email first, then by normalized phone.
	 *
	 * @param string $email            Validated email address.
	 * @param string $normalized_phone Normalized `+84...` phone number.
	 * @return \WP_User|null
	 */
	public static function find_existing_user( string $email, string $normalized_phone ) {
		$clean_email = strtolower( sanitize_email( $email ) );
		if ( '' !== $clean_email ) {
			$user = get_user_by( 'email', $clean_email );
			if ( $user instanceof \WP_User && ! JN_Booking_Roles::is_staff_user( (int) $user->ID ) ) {
				return $user;
			}
		}

		if ( '' !== $normalized_phone ) {
			$local_phone = self::to_local_phone( $normalized_phone );
			$users       = get_users(
				array(
					'number'     => 10,
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						'relation' => 'OR',
						array(
							'key'   => '_jn_phone_e164',
							'value' => $normalized_phone,
						),
						array(
							'key'   => 'billing_phone',
							'value' => $normalized_phone,
						),
						array(
							'key'   => 'billing_phone',
							'value' => $local_phone,
						),
					),
				)
			);

			foreach ( $users as $u ) {
				if ( $u instanceof \WP_User && ! JN_Booking_Roles::is_staff_user( (int) $u->ID ) ) {
					return $u;
				}
			}
		}

		return null;
	}

	/**
	 * Upsert customer on booking submission.
	 *
	 * - Matches by email first, then normalized phone.
	 * - Checks and logs duplicate conflicts when phone matches a user with a different email.
	 * - Creates a WooCommerce customer ONLY when `auto_create_account === 'yes'`.
	 * - Otherwise returns `user_id = 0` so the booking stores a guest snapshot that links on registration/login.
	 *
	 * @param array<string, string> $data Customer data (`name`, `email`, `phone` normalized to `+84...`).
	 * @return int Matched or created `WP_User` ID, or `0` for guest.
	 */
	public static function upsert_customer_for_booking( array $data ): int {
		$name  = sanitize_text_field( $data['name'] ?? '' );
		$email = strtolower( sanitize_email( $data['email'] ?? '' ) );
		$phone = self::normalize_vn_phone( (string) ( $data['phone'] ?? '' ) );

		// Check if email matches User A while phone matches User B (or vice versa).
		$email_user = '' !== $email ? get_user_by( 'email', $email ) : false;
		if ( $email_user instanceof \WP_User && ! JN_Booking_Roles::is_staff_user( (int) $email_user->ID ) ) {
			$user_id = (int) $email_user->ID;
			self::sync_user_contact_meta( $user_id, $name, $email, $phone );
			self::check_and_log_duplicates_for_customer( 'user:' . $user_id, $email, $phone, 'booking_upsert' );
			return $user_id;
		}

		// If no user has this email, check if another user has this phone.
		$phone_user = '' !== $phone ? self::find_existing_user( '', $phone ) : null;
		if ( $phone_user instanceof \WP_User ) {
			$phone_user_email = strtolower( sanitize_email( $phone_user->user_email ) );
			if ( '' !== $email && '' !== $phone_user_email && $email !== $phone_user_email ) {
				// Duplicate phone across two different emails! Log warning and do NOT auto-merge when auto_create is off.
				self::check_and_log_duplicates_for_customer(
					'guest:' . $email,
					$email,
					$phone,
					'booking_phone_conflict_with_user_' . $phone_user->ID
				);
				if ( 'yes' !== (string) JN_Booking_Settings::get( 'auto_create_account', 'no' ) ) {
					return 0;
				}
			}
			$user_id = (int) $phone_user->ID;
			self::sync_user_contact_meta( $user_id, $name, $email, $phone );
			return $user_id;
		}

		$auto_create = (string) JN_Booking_Settings::get( 'auto_create_account', 'no' );
		if ( 'yes' !== $auto_create ) {
			return 0;
		}

		if ( '' === $email || ! is_email( $email ) ) {
			return 0;
		}

		$username = sanitize_user( current( explode( '@', $email ) ), true );
		if ( '' === $username ) {
			$username = 'khachhang';
		}
		$base_username = $username;
		$suffix        = 1;
		while ( username_exists( $username ) ) {
			$username = $base_username . $suffix;
			++$suffix;
		}

		$password = wp_generate_password( 20, true, false );
		if ( function_exists( 'wc_create_new_customer' ) ) {
			$created_id = wc_create_new_customer( $email, $username, $password, array( 'first_name' => $name ) );
		} else {
			$created_id = wp_create_user( $username, $password, $email );
		}

		if ( is_wp_error( $created_id ) || $created_id <= 0 ) {
			return 0;
		}

		$user_id = (int) $created_id;
		self::sync_user_contact_meta( $user_id, $name, $email, $phone );
		self::link_guest_bookings_to_user( $user_id );

		return $user_id;
	}

	/**
	 * Synchronize customer contact meta fields without overwriting existing non-empty billing name.
	 *
	 * @param int    $user_id          User ID.
	 * @param string $name             Customer full name.
	 * @param string $email            Customer email.
	 * @param string $normalized_phone Customer phone (`+84...`).
	 * @return void
	 */
	public static function sync_user_contact_meta( int $user_id, string $name, string $email, string $normalized_phone ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		if ( '' !== $normalized_phone ) {
			update_user_meta( $user_id, '_jn_phone_e164', $normalized_phone );
			$current_billing_phone = (string) get_user_meta( $user_id, 'billing_phone', true );
			if ( '' === $current_billing_phone ) {
				update_user_meta( $user_id, 'billing_phone', $normalized_phone );
			}
		}

		if ( '' !== $name ) {
			if ( '' === (string) get_user_meta( $user_id, '_jn_full_name', true ) ) {
				update_user_meta( $user_id, '_jn_full_name', $name );
			}
			$current_first = (string) get_user_meta( $user_id, 'billing_first_name', true );
			if ( '' === $current_first ) {
				update_user_meta( $user_id, 'billing_first_name', $name );
			}
			$current_fn = (string) get_user_meta( $user_id, 'first_name', true );
			if ( '' === $current_fn ) {
				update_user_meta( $user_id, 'first_name', $name );
			}
		}

		if ( '' !== $email ) {
			$current_billing_email = (string) get_user_meta( $user_id, 'billing_email', true );
			if ( '' === $current_billing_email ) {
				update_user_meta( $user_id, 'billing_email', $email );
			}
		}

		if ( class_exists( 'JN_Customer_Profile' ) ) {
			JN_Customer_Profile::sync_user_to_wc_customer( $user_id );
		}
	}

	/**
	 * Hook callback when a new user registers.
	 *
	 * @param int $user_id New user ID.
	 * @return void
	 */
	public static function handle_user_registered( int $user_id ): void {
		self::link_guest_bookings_to_user( $user_id );
	}

	/**
	 * Hook callback when a WooCommerce customer is created.
	 *
	 * @param int $customer_id New customer ID.
	 * @return void
	 */
	public static function handle_wc_customer_created( int $customer_id ): void {
		self::link_guest_bookings_to_user( $customer_id );
	}

	/**
	 * Hook callback on user login to ensure any guest bookings and guest orders
	 * with matching email are attached to the account.
	 *
	 * @param string   $user_login Username.
	 * @param \WP_User $user       User object.
	 * @return void
	 */
	public static function handle_user_login( string $user_login, $user ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
		if ( $user instanceof \WP_User ) {
			self::link_guest_bookings_to_user( (int) $user->ID );
		}
	}

	/**
	 * Link any previous guest bookings AND guest WooCommerce HPOS orders to the registered user
	 * when the verified account email (or normalized phone when email matches/empty) matches.
	 *
	 * Also merges any guest CRM profile (`jn_guest_crm_profiles`) and consent logs into `user_meta`.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return int Total number of guest bookings + standalone guest orders linked.
	 */
	public static function link_guest_bookings_to_user( int $user_id ): int {
		if ( $user_id <= 0 ) {
			return 0;
		}

		$user = get_userdata( $user_id );
		if ( ! $user instanceof \WP_User ) {
			return 0;
		}

		$email         = strtolower( sanitize_email( $user->user_email ) );
		$billing_phone = (string) get_user_meta( $user_id, 'billing_phone', true );
		$e164_phone    = (string) get_user_meta( $user_id, '_jn_phone_e164', true );
		$norm_phone    = self::normalize_vn_phone( '' !== $e164_phone ? $e164_phone : $billing_phone );

		if ( '' !== $norm_phone && '' === $e164_phone ) {
			update_user_meta( $user_id, '_jn_phone_e164', $norm_phone );
		}

		// Merge any stored guest CRM profiles for this email or phone.
		if ( class_exists( 'JN_Customer_Profile' ) ) {
			if ( '' !== $email ) {
				JN_Customer_Profile::merge_guest_profile_into_user( $user_id, 'guest:' . $email );
			}
			if ( '' !== $norm_phone ) {
				JN_Customer_Profile::merge_guest_profile_into_user( $user_id, 'guest:' . $norm_phone );
			}
		}

		$meta_or = array();
		if ( '' !== $email ) {
			$meta_or[] = array(
				'key'   => '_jn_customer_email',
				'value' => $email,
			);
		}
		if ( '' !== $norm_phone ) {
			$meta_or[] = array(
				'key'   => '_jn_customer_phone',
				'value' => $norm_phone,
			);
		}

		$linked_count = 0;

		if ( ! empty( $meta_or ) ) {
			if ( count( $meta_or ) > 1 ) {
				$meta_or['relation'] = 'OR';
			}

			$booking_ids = get_posts(
				array(
					'post_type'      => JN_Booking_CPT::POST_TYPE,
					'post_status'    => array_keys( JN_Booking_CPT::get_statuses() ),
					'posts_per_page' => 200,
					'fields'         => 'ids',
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						$meta_or,
					),
				)
			);

			foreach ( $booking_ids as $booking_id ) {
				$booking_id    = (int) $booking_id;
				$current_user  = (int) get_post_meta( $booking_id, '_jn_customer_user_id', true );
				$booking_email = strtolower( sanitize_email( (string) get_post_meta( $booking_id, '_jn_customer_email', true ) ) );
				$booking_phone = (string) get_post_meta( $booking_id, '_jn_customer_phone', true );
				$booking_name  = (string) get_post_meta( $booking_id, '_jn_customer_name', true );
				$booking_code  = (string) get_post_meta( $booking_id, '_jn_booking_code', true );

				// Only auto-attach if the booking email matches the account's verified email (or if booking had no email).
				if ( '' !== $booking_email && '' !== $email && $booking_email !== $email ) {
					self::check_and_log_duplicates_for_customer(
						'user:' . $user_id,
						$email,
						$booking_phone,
						'login_phone_conflict_booking_' . $booking_code
					);
					continue;
				}

				if ( 0 === $current_user ) {
					update_post_meta( $booking_id, '_jn_customer_user_id', $user_id );
					++$linked_count;

					self::sync_user_contact_meta( $user_id, $booking_name, $email, $booking_phone );

					if ( class_exists( 'JN_Customer_Profile' ) ) {
						$consent_ts = (string) get_post_meta( $booking_id, '_jn_consent_timestamp', true );
						if ( '' !== $consent_ts ) {
							JN_Customer_Profile::append_consent_log(
								'user:' . $user_id,
								array(
									'timestamp'      => $consent_ts,
									'source'         => 'Biểu mẫu đặt lịch (Gắn khi đăng ký/đăng nhập)',
									'policy_page_id' => (int) get_post_meta( $booking_id, '_jn_consent_policy_page_id', true ),
									'policy_version' => (string) get_post_meta( $booking_id, '_jn_consent_policy_version', true ),
									'text_snapshot'  => (string) get_post_meta( $booking_id, '_jn_consent_text_snapshot', true ),
									'reference'      => $booking_code,
								)
							);
						}
					}
				}

				// Link the booking's WooCommerce order if it was created for a guest.
				$order_id = (int) get_post_meta( $booking_id, '_jn_order_id', true );
				if ( $order_id > 0 && function_exists( 'wc_get_order' ) ) {
					$order = wc_get_order( $order_id );
					if ( $order instanceof \WC_Order && 0 === (int) $order->get_customer_id() ) {
						$order->set_customer_id( $user_id );
						$order->save();
					}
				}
			}
		}

		// Link any standalone guest WooCommerce HPOS orders with the same verified email.
		if ( '' !== $email && function_exists( 'wc_get_orders' ) ) {
			$guest_orders = wc_get_orders(
				array(
					'customer_id'   => 0,
					'billing_email' => $email,
					'limit'         => 100,
				)
			);

			if ( is_array( $guest_orders ) ) {
				foreach ( $guest_orders as $guest_order ) {
					if ( $guest_order instanceof \WC_Order && 0 === (int) $guest_order->get_customer_id() ) {
						$guest_order->set_customer_id( $user_id );
						$guest_order->save();
						++$linked_count;

						$ord_name  = trim( $guest_order->get_billing_first_name() . ' ' . $guest_order->get_billing_last_name() );
						$ord_phone = self::normalize_vn_phone( (string) $guest_order->get_billing_phone() );
						self::sync_user_contact_meta( $user_id, $ord_name, $email, $ord_phone );
					}
				}
			}
		}

		return $linked_count;
	}

	/**
	 * Detect any other customer records (registered users or guest records) that share the same
	 * normalized phone (`+84...` / `0...`) or email address.
	 *
	 * @param string $current_customer_key Current customer key (`user:123` or `guest:...`) to exclude.
	 * @param string $email                Customer email.
	 * @param string $normalized_phone     Customer normalized phone (`+84...`).
	 * @return array<int, array<string, mixed>> Array of conflicting customer descriptors.
	 */
	public static function detect_duplicates( string $current_customer_key, string $email, string $normalized_phone ): array {
		$clean_email = strtolower( sanitize_email( $email ) );
		$norm_phone  = self::normalize_vn_phone( $normalized_phone );
		$local_phone = '' !== $norm_phone ? self::to_local_phone( $norm_phone ) : '';

		if ( '' === $clean_email && '' === $norm_phone ) {
			return array();
		}

		$current_user_id = 0 === strpos( $current_customer_key, 'user:' )
			? (int) substr( $current_customer_key, 5 )
			: 0;

		$conflicts = array();
		$seen_keys = array( $current_customer_key => true );

		// 1. Check registered WordPress users (excluding staff roles).
		if ( '' !== $clean_email ) {
			$u_by_email = get_user_by( 'email', $clean_email );
			if (
				$u_by_email instanceof \WP_User
				&& (int) $u_by_email->ID !== $current_user_id
				&& ! JN_Booking_Roles::is_staff_user( (int) $u_by_email->ID )
			) {
				$ckey = 'user:' . (int) $u_by_email->ID;
				if ( ! isset( $seen_keys[ $ckey ] ) ) {
					$seen_keys[ $ckey ] = true;
					$conflicts[]        = array(
						'customer_key'  => $ckey,
						'user_id'       => (int) $u_by_email->ID,
						'name'          => (string) $u_by_email->display_name,
						'email'         => strtolower( $u_by_email->user_email ),
						'phone'         => (string) get_user_meta( (int) $u_by_email->ID, '_jn_phone_e164', true ),
						'matched_field' => 'email',
					);
				}
			}
		}

		if ( '' !== $norm_phone ) {
			$phone_users = get_users(
				array(
					'number'     => 20,
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						'relation' => 'OR',
						array(
							'key'   => '_jn_phone_e164',
							'value' => $norm_phone,
						),
						array(
							'key'   => 'billing_phone',
							'value' => $norm_phone,
						),
						array(
							'key'   => 'billing_phone',
							'value' => $local_phone,
						),
					),
				)
			);

			foreach ( $phone_users as $pu ) {
				if (
					! $pu instanceof \WP_User
					|| (int) $pu->ID === $current_user_id
					|| JN_Booking_Roles::is_staff_user( (int) $pu->ID )
				) {
					continue;
				}
				$ckey = 'user:' . (int) $pu->ID;
				if ( ! isset( $seen_keys[ $ckey ] ) ) {
					$seen_keys[ $ckey ] = true;
					$conflicts[]        = array(
						'customer_key'  => $ckey,
						'user_id'       => (int) $pu->ID,
						'name'          => (string) $pu->display_name,
						'email'         => strtolower( $pu->user_email ),
						'phone'         => $norm_phone,
						'matched_field' => 'phone',
					);
				}
			}
		}

		// 2. Check guest bookings where email differs but phone matches (or vice versa).
		if ( '' !== $norm_phone ) {
			$guest_bookings = get_posts(
				array(
					'post_type'      => JN_Booking_CPT::POST_TYPE,
					'post_status'    => array_keys( JN_Booking_CPT::get_statuses() ),
					'posts_per_page' => 50,
					'fields'         => 'ids',
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'   => '_jn_customer_phone',
							'value' => $norm_phone,
						),
					),
				)
			);

			foreach ( $guest_bookings as $bid ) {
				$bid    = (int) $bid;
				$b_uid  = (int) get_post_meta( $bid, '_jn_customer_user_id', true );
				$b_mail = strtolower( sanitize_email( (string) get_post_meta( $bid, '_jn_customer_email', true ) ) );
				$b_name = (string) get_post_meta( $bid, '_jn_customer_name', true );

				if ( $b_uid > 0 && $b_uid !== $current_user_id ) {
					$ckey = 'user:' . $b_uid;
					if ( ! isset( $seen_keys[ $ckey ] ) ) {
						$seen_keys[ $ckey ] = true;
						$conflicts[]        = array(
							'customer_key'  => $ckey,
							'user_id'       => $b_uid,
							'name'          => $b_name,
							'email'         => $b_mail,
							'phone'         => $norm_phone,
							'matched_field' => 'phone',
						);
					}
				} elseif ( 0 === $b_uid && '' !== $b_mail && $b_mail !== $clean_email ) {
					$ckey = 'guest:' . $b_mail;
					if ( ! isset( $seen_keys[ $ckey ] ) ) {
						$seen_keys[ $ckey ] = true;
						$conflicts[]        = array(
							'customer_key'  => $ckey,
							'user_id'       => 0,
							'name'          => $b_name,
							'email'         => $b_mail,
							'phone'         => $norm_phone,
							'matched_field' => 'phone',
						);
					}
				}
			}
		}

		// 3. Check stored guest CRM profiles.
		if ( class_exists( 'JN_Customer_Profile' ) ) {
			$guest_profiles = JN_Customer_Profile::get_all_guest_profiles();
			foreach ( $guest_profiles as $gkey => $gdata ) {
				if ( isset( $seen_keys[ $gkey ] ) ) {
					continue;
				}
				$g_email = isset( $gdata['email'] ) ? strtolower( sanitize_email( (string) $gdata['email'] ) ) : '';
				$g_phone = isset( $gdata['phone'] ) ? self::normalize_vn_phone( (string) $gdata['phone'] ) : '';

				$matched_field = '';
				if ( '' !== $clean_email && $clean_email === $g_email ) {
					$matched_field = 'email';
				} elseif ( '' !== $norm_phone && $norm_phone === $g_phone ) {
					$matched_field = 'phone';
				}

				if ( '' !== $matched_field ) {
					$seen_keys[ $gkey ] = true;
					$conflicts[]        = array(
						'customer_key'  => $gkey,
						'user_id'       => 0,
						'name'          => (string) ( $gdata['full_name'] ?? 'Khách vãng lai' ),
						'email'         => $g_email,
						'phone'         => $g_phone,
						'matched_field' => $matched_field,
					);
				}
			}
		}

		return $conflicts;
	}

	/**
	 * Detect duplicates for a customer and log a structured entry in `jn_customer_duplicate_log`
	 * whenever a conflict exists (merging is out of scope per specification).
	 *
	 * @param string $customer_key     Customer key (`user:123` or `guest:...`).
	 * @param string $email            Email address.
	 * @param string $normalized_phone Normalized phone (`+84...`).
	 * @param string $context          Trigger context (`booking_created`, `profile_update`, etc.).
	 * @return array<int, array<string, mixed>> Detected duplicate entries.
	 */
	public static function check_and_log_duplicates_for_customer(
		string $customer_key,
		string $email,
		string $normalized_phone,
		string $context = 'check'
	): array {
		$conflicts = self::detect_duplicates( $customer_key, $email, $normalized_phone );
		if ( empty( $conflicts ) ) {
			return array();
		}

		$tz     = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
		$now_vn = new \DateTimeImmutable( 'now', $tz );
		$log    = self::get_duplicate_log();

		foreach ( $conflicts as $conflict ) {
			$entry = array(
				'timestamp'                => $now_vn->format( 'c' ),
				'primary_customer_key'     => $customer_key,
				'conflicting_customer_key' => (string) $conflict['customer_key'],
				'matched_field'            => (string) $conflict['matched_field'],
				'context'                  => sanitize_text_field( $context ),
			);

			// Avoid consecutive identical log entries for the same pair and field.
			$is_dup_entry = false;
			foreach ( array_slice( $log, 0, 25 ) as $existing ) {
				if (
					isset( $existing['primary_customer_key'], $existing['conflicting_customer_key'], $existing['matched_field'] )
					&& $existing['primary_customer_key'] === $entry['primary_customer_key']
					&& $existing['conflicting_customer_key'] === $entry['conflicting_customer_key']
					&& $existing['matched_field'] === $entry['matched_field']
				) {
					$is_dup_entry = true;
					break;
				}
			}

			if ( ! $is_dup_entry ) {
				array_unshift( $log, $entry );
			}
		}

		if ( count( $log ) > 200 ) {
			$log = array_slice( $log, 0, 200 );
		}

		update_option( self::DUPLICATE_LOG_OPTION, $log, false );

		return $conflicts;
	}

	/**
	 * Return the recorded duplicate warning log entries.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_duplicate_log(): array {
		$raw = get_option( self::DUPLICATE_LOG_OPTION, array() );
		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * Query the unified customer directory across:
	 * 1. Registered WordPress/WooCommerce customers (`WP_User`),
	 * 2. Guest bookings (`jn_booking`),
	 * 3. Guest WooCommerce HPOS orders (`WC_Order`).
	 *
	 * Supports filtering by:
	 * - `search`: Name, Email, Business Name, or Phone in ANY format (`0908...`, `+84908...`, `84908...`, with spaces/dots).
	 * - `type`: Customer type (`ca_nhan`, `tho_hoc_vien`, `chu_co_so`).
	 * - `tag`: Tag string (case-insensitive).
	 * - `has_pending_booking`: `true` / `'1'` / `'yes'`.
	 *
	 * @param array<string, mixed> $args Query/filter arguments.
	 * @return array<int, array<string, mixed>> List of unified customer records.
	 */
	public static function query_customers( array $args = array() ): array {
		$records = self::build_unified_customer_map();

		$search      = isset( $args['search'] ) ? trim( sanitize_text_field( (string) $args['search'] ) ) : '';
		$filter_type = isset( $args['type'] ) ? sanitize_key( (string) $args['type'] ) : '';
		$filter_tag  = isset( $args['tag'] ) ? trim( sanitize_text_field( (string) $args['tag'] ) ) : '';
		$has_pending = ! empty( $args['has_pending_booking'] )
			&& in_array( (string) $args['has_pending_booking'], array( '1', 'yes', 'true' ), true );

		$filtered = array();

		foreach ( $records as $key => $rec ) {
			// 1. Filter by customer type.
			if ( '' !== $filter_type && $rec['customer_type'] !== $filter_type ) {
				continue;
			}

			// 2. Filter by tag (case-insensitive).
			if ( '' !== $filter_tag ) {
				$tag_matched = false;
				foreach ( (array) $rec['tags'] as $t ) {
					if ( mb_strtolower( (string) $t ) === mb_strtolower( $filter_tag ) ) {
						$tag_matched = true;
						break;
					}
				}
				if ( ! $tag_matched ) {
					continue;
				}
			}

			// 3. Filter by "has pending booking".
			if ( $has_pending && empty( $rec['has_pending_booking'] ) ) {
				continue;
			}

			// 4. Filter by search (name, email, business_name, or phone in any format).
			if ( '' !== $search ) {
				$s_lower = mb_strtolower( $search );
				$matched = false;

				if ( '' !== $rec['full_name'] && false !== mb_strpos( mb_strtolower( (string) $rec['full_name'] ), $s_lower ) ) {
					$matched = true;
				} elseif ( '' !== $rec['email'] && false !== mb_strpos( mb_strtolower( (string) $rec['email'] ), $s_lower ) ) {
					$matched = true;
				} elseif ( '' !== $rec['business_name'] && false !== mb_strpos( mb_strtolower( (string) $rec['business_name'] ), $s_lower ) ) {
					$matched = true;
				} elseif ( self::phone_matches_search( (string) $rec['phone'], $search ) || self::phone_matches_search( (string) $rec['phone_raw'], $search ) ) {
					$matched = true;
				}

				if ( ! $matched ) {
					continue;
				}
			}

			$filtered[] = $rec;
		}

		// Sort by most recent activity (last booking date or registration date) descending.
		usort(
			$filtered,
			function ( array $a, array $b ): int {
				$ts_a = (int) ( $a['last_activity_ts'] ?? 0 );
				$ts_b = (int) ( $b['last_activity_ts'] ?? 0 );
				if ( $ts_a === $ts_b ) {
					return strcmp( (string) $a['full_name'], (string) $b['full_name'] );
				}
				return $ts_b <=> $ts_a;
			}
		);

		return $filtered;
	}

	/**
	 * Retrieve full customer detail record (profile, bookings list, HPOS orders list,
	 * notes timeline, consent log, and duplicate conflicts) by `customer_key`.
	 *
	 * @param string $customer_key Customer key (`user:123` or `guest:...`).
	 * @return array<string, mixed>|null
	 */
	public static function get_customer_detail( string $customer_key ) {
		$map = self::build_unified_customer_map();
		if ( ! isset( $map[ $customer_key ] ) ) {
			// Check if it's a valid user ID even if not in default role list.
			if ( 0 === strpos( $customer_key, 'user:' ) ) {
				$uid = (int) substr( $customer_key, 5 );
				if ( $uid > 0 && get_userdata( $uid ) ) {
					$map = self::build_unified_customer_map( $uid );
				}
			}
		}

		if ( ! isset( $map[ $customer_key ] ) ) {
			return null;
		}

		$record               = $map[ $customer_key ];
		$record['duplicates'] = self::detect_duplicates(
			$customer_key,
			(string) $record['email'],
			(string) $record['phone']
		);
		$record['profile']    = $record;

		return $record;
	}

	/**
	 * Collect all distinct tags across all customers for the filter dropdown.
	 *
	 * @return string[]
	 */
	public static function get_all_distinct_tags(): array {
		$map  = self::build_unified_customer_map();
		$tags = array();
		$seen = array();

		foreach ( $map as $rec ) {
			foreach ( (array) $rec['tags'] as $tag ) {
				$tag   = trim( (string) $tag );
				$lower = mb_strtolower( $tag );
				if ( '' !== $tag && ! isset( $seen[ $lower ] ) ) {
					$seen[ $lower ] = true;
					$tags[]         = $tag;
				}
			}
		}

		sort( $tags );
		return $tags;
	}

	/**
	 * Create a booking from the admin "Tạo lịch hẹn" action for a phone or walk-in customer.
	 *
	 * Can either create a `Chờ xác nhận` (`jn_pending`) booking or immediately confirm it
	 * as `Đã xác nhận lịch` (`jn_confirmed`) with a linked HPOS `pending` order.
	 *
	 * @param array<string, mixed> $payload Booking creation fields.
	 * @param int                  $actor_id Staff user ID creating the booking.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function create_staff_booking( array $payload, int $actor_id = 0 ) {
		if ( $actor_id <= 0 ) {
			$actor_id = get_current_user_id();
		}

		if ( ! JN_Booking_Roles::user_can_manage_bookings( $actor_id ) ) {
			return new \WP_Error(
				'forbidden_staff_booking',
				'Bạn không có quyền tạo lịch hẹn từ trang quản trị.',
				array( 'status' => 403 )
			);
		}

		$full_name = sanitize_text_field( trim( (string) ( $payload['full_name'] ?? '' ) ) );
		if ( mb_strlen( $full_name ) < 2 ) {
			return new \WP_Error( 'missing_name', 'Vui lòng nhập đầy đủ Họ và Tên khách hàng.' );
		}

		$raw_phone        = sanitize_text_field( (string) ( $payload['phone'] ?? '' ) );
		$normalized_phone = self::normalize_vn_phone( $raw_phone );
		if ( '' === $normalized_phone ) {
			return new \WP_Error( 'invalid_phone', 'Số điện thoại khách hàng không hợp lệ.' );
		}

		$email = strtolower( sanitize_email( (string) ( $payload['email'] ?? '' ) ) );
		if ( '' === $email || ! is_email( $email ) ) {
			return new \WP_Error( 'invalid_email', 'Địa chỉ email khách hàng không hợp lệ.' );
		}

		$package_code = sanitize_text_field( (string) ( $payload['package_code'] ?? '60-phut' ) );
		$product      = JN_Coaching_Packages::get_product_by_key( $package_code );
		if ( ! $product instanceof \WC_Product || ! JN_Coaching_Packages::is_coaching_package( $product ) ) {
			return new \WP_Error( 'invalid_package', 'Gói dịch vụ không hợp lệ.' );
		}

		$formats      = (array) JN_Booking_Settings::get( 'formats', array() );
		$format_key   = sanitize_key( (string) ( $payload['format'] ?? 'online' ) );
		if ( ! isset( $formats[ $format_key ] ) ) {
			$format_key = 'online';
		}
		$format_label = $formats[ $format_key ] ?? 'Online Trực Tuyến (Zoom High-Res)';

		$windows      = (array) JN_Booking_Settings::get( 'time_windows', array() );
		$window_key   = sanitize_key( (string) ( $payload['preferred_window'] ?? 'morning' ) );
		if ( ! isset( $windows[ $window_key ] ) ) {
			$window_key = 'morning';
		}
		$window_label = $windows[ $window_key ] ?? 'Buổi sáng (09:00 - 11:30)';

		$goals       = sanitize_textarea_field( (string) ( $payload['goals'] ?? '' ) );
		$staff_notes = sanitize_textarea_field( (string) ( $payload['staff_notes'] ?? '' ) );
		$user_id     = isset( $payload['user_id'] ) ? absint( $payload['user_id'] ) : 0;

		if ( 0 === $user_id ) {
			$existing = self::find_existing_user( $email, '' );
			if ( $existing instanceof \WP_User ) {
				$user_id = (int) $existing->ID;
			}
		}

		$pkg_key        = (string) $product->get_meta( '_jn_package_key', true );
		$pkg_title      = $product->get_name();
		$pkg_form_label = (string) $product->get_meta( '_jn_form_label', true );
		if ( '' === $pkg_form_label ) {
			$pkg_form_label = $pkg_title;
		}
		$raw_duration = (string) $product->get_meta( '_jn_duration', true );
		$duration_min = is_numeric( $raw_duration ) ? max( 15, (int) $raw_duration ) : 60;
		$total_sess   = max( 1, (int) $product->get_meta( '_jn_sessions', true ) );

		$post_id = wp_insert_post(
			array(
				'post_type'   => JN_Booking_CPT::POST_TYPE,
				'post_status' => JN_Booking_CPT::STATUS_PENDING,
				'post_title'  => 'JN-WALKIN',
				'post_author' => $user_id > 0 ? $user_id : $actor_id,
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
		$source_type  = isset( $payload['source_type'] ) ? sanitize_key( (string) $payload['source_type'] ) : JN_Customer_Profile::SOURCE_WALK_IN;

		update_post_meta( $booking_id, '_jn_booking_code', $booking_code );
		update_post_meta( $booking_id, '_jn_customer_user_id', $user_id );
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
		update_post_meta( $booking_id, '_jn_is_waitlist', 'no' );

		update_post_meta( $booking_id, '_jn_consent_given', 'yes' );
		update_post_meta( $booking_id, '_jn_consent_timestamp', $now_vn->format( 'c' ) );
		update_post_meta( $booking_id, '_jn_consent_policy_page_id', (int) $privacy_meta['page_id'] );
		update_post_meta( $booking_id, '_jn_consent_policy_url', (string) $privacy_meta['url'] );
		update_post_meta( $booking_id, '_jn_consent_policy_version', (string) $privacy_meta['version'] );
		update_post_meta( $booking_id, '_jn_consent_text_snapshot', 'Khách hàng đồng ý trực tiếp / qua điện thoại với nhân sự tư vấn.' );

		update_post_meta(
			$booking_id,
			'_jn_source',
			array(
				'source_type' => $source_type,
				'landing_url' => admin_url( 'admin.php?page=jn-customers' ),
				'referrer'    => 'staff_walk_in',
				'utm'         => array(),
			)
		);
		update_post_meta( $booking_id, '_jn_duration_minutes', $duration_min );
		update_post_meta( $booking_id, '_jn_session_number', 1 );
		update_post_meta( $booking_id, '_jn_total_sessions', $total_sess );
		update_post_meta( $booking_id, '_jn_order_id', 0 );
		update_post_meta( $booking_id, '_jn_refund_flagged', 'no' );
		update_post_meta( $booking_id, '_jn_staff_notes', $staff_notes );

		$actor_user = get_userdata( $actor_id );
		$actor_name = $actor_user instanceof \WP_User ? $actor_user->display_name . ' (#' . $actor_id . ')' : 'Nhân sự';

		$timeline = array(
			array(
				'actor_id'   => $actor_id,
				'actor_name' => $actor_name,
				'timestamp'  => $now_vn->format( 'c' ),
				'from'       => 'new',
				'to'         => JN_Booking_CPT::STATUS_PENDING,
				'note'       => 'Khởi tạo lịch hẹn từ yêu cầu Điện thoại / Khách trực tiếp (Walk-in).',
			),
		);
		update_post_meta( $booking_id, '_jn_status_timeline', $timeline );

		do_action( 'jn_booking_created', $booking_id );

		// If staff provided a confirmed slot and requested immediate confirmation (`confirm_now === 'yes'`).
		$confirm_now     = ! empty( $payload['confirm_now'] ) && 'yes' === (string) $payload['confirm_now'];
		$confirmed_start = isset( $payload['confirmed_start'] ) ? sanitize_text_field( (string) $payload['confirmed_start'] ) : '';

		if ( $confirm_now && '' !== $confirmed_start ) {
			$transition = JN_Booking_Service::transition_status(
				$booking_id,
				JN_Booking_CPT::STATUS_CONFIRMED,
				array(
					'actor_id'         => $actor_id,
					'confirmed_start'  => $confirmed_start,
					'duration_minutes' => $duration_min,
					'format'           => $format_key,
					'meeting_link'     => isset( $payload['meeting_link'] ) ? (string) $payload['meeting_link'] : '',
					'location'         => isset( $payload['location'] ) ? (string) $payload['location'] : '',
					'agreed_price'     => isset( $payload['agreed_price'] ) ? (int) $payload['agreed_price'] : 0,
					'allow_overlap'    => ! empty( $payload['allow_overlap'] ),
					'note'             => 'Xác nhận lịch hẹn ngay khi tạo yêu cầu Điện thoại / Walk-in.',
				)
			);

			if ( is_wp_error( $transition ) ) {
				return $transition;
			}
		}

		return array(
			'booking_id'   => $booking_id,
			'booking_code' => $booking_code,
			'status'       => (string) get_post_status( $booking_id ),
			'order_id'     => (int) get_post_meta( $booking_id, '_jn_order_id', true ),
		);
	}

	/**
	 * Build the complete map of unified customer records indexed by `customer_key`.
	 *
	 * @param int $force_include_user_id Optional user ID to include regardless of role.
	 * @return array<string, array<string, mixed>>
	 */
	private static function build_unified_customer_map( int $force_include_user_id = 0 ): array {
		$map          = array();
		$email_to_key = array();
		$phone_to_key = array();

		// 1. Load all registered customer/subscriber users.
		$customer_users = get_users(
			array(
				'role__in' => array( 'customer', 'subscriber' ),
				'number'   => 500,
			)
		);

		if ( $force_include_user_id > 0 ) {
			$forced = get_userdata( $force_include_user_id );
			if ( $forced instanceof \WP_User ) {
				$customer_users[] = $forced;
			}
		}

		foreach ( $customer_users as $u ) {
			if ( ! $u instanceof \WP_User ) {
				continue;
			}
			// Skip staff accounts from appearing in the customer list unless explicitly forced.
			if ( $u->ID !== $force_include_user_id && JN_Booking_Roles::is_staff_user( (int) $u->ID ) ) {
				continue;
			}

			$uid     = (int) $u->ID;
			$ckey    = 'user:' . $uid;
			$profile = JN_Customer_Profile::get_user_profile( $uid );
			if ( empty( $profile ) ) {
				continue;
			}

			$reg_ts = ! empty( $profile['registered_at'] ) ? (int) strtotime( (string) $profile['registered_at'] ) : 0;

			$map[ $ckey ] = array_merge(
				$profile,
				array(
					'bookings'               => array(),
					'bookings_count'         => 0,
					'pending_bookings_count' => 0,
					'has_pending_booking'    => false,
					'last_booking'           => null,
					'orders'                 => array(),
					'orders_count'           => 0,
					'total_spent'            => 0,
					'last_activity_ts'       => $reg_ts,
					'duplicates'             => array(),
				)
			);

			if ( '' !== $profile['email'] ) {
				$email_to_key[ $profile['email'] ] = $ckey;
			}
			if ( '' !== $profile['phone'] ) {
				$phone_to_key[ $profile['phone'] ] = $ckey;
			}
		}

		// 2. Load all stored guest CRM profiles (`jn_guest_crm_profiles`).
		$guest_profiles = JN_Customer_Profile::get_all_guest_profiles();
		foreach ( $guest_profiles as $gkey => $gprof ) {
			$g_email = isset( $gprof['email'] ) ? strtolower( sanitize_email( (string) $gprof['email'] ) ) : '';
			$g_phone = isset( $gprof['phone'] ) ? self::normalize_vn_phone( (string) $gprof['phone'] ) : '';

			if ( '' !== $g_email && isset( $email_to_key[ $g_email ] ) ) {
				continue;
			}

			$ctype  = isset( $gprof['customer_type'] ) ? (string) $gprof['customer_type'] : '';
			$stype  = isset( $gprof['source_type'] ) ? (string) $gprof['source_type'] : JN_Customer_Profile::SOURCE_FORM;
			$tags   = JN_Customer_Profile::sanitize_tags( $gprof['tags'] ?? array() );

			$map[ $gkey ] = array(
				'customer_key'           => $gkey,
				'user_id'                => 0,
				'is_guest'               => true,
				'full_name'              => (string) ( $gprof['full_name'] ?? 'Khách vãng lai' ),
				'phone'                  => $g_phone,
				'phone_local'            => '' !== $g_phone ? self::to_local_phone( $g_phone ) : '',
				'phone_raw'              => (string) ( $gprof['phone_raw'] ?? $g_phone ),
				'email'                  => $g_email,
				'customer_type'          => $ctype,
				'customer_type_label'    => JN_Customer_Profile::get_customer_type_label( $ctype ),
				'business_name'          => (string) ( $gprof['business_name'] ?? '' ),
				'province_city'          => (string) ( $gprof['province_city'] ?? '' ),
				'source_type'            => $stype,
				'source_type_label'      => JN_Customer_Profile::get_source_type_label( $stype ),
				'source_detail'          => (string) ( $gprof['source_detail'] ?? '' ),
				'tags'                   => $tags,
				'internal_notes'         => isset( $gprof['internal_notes'] ) && is_array( $gprof['internal_notes'] ) ? $gprof['internal_notes'] : array(),
				'consent_log'            => isset( $gprof['consent_log'] ) && is_array( $gprof['consent_log'] ) ? $gprof['consent_log'] : array(),
				'registered_at'          => '',
				'bookings'               => array(),
				'bookings_count'         => 0,
				'pending_bookings_count' => 0,
				'has_pending_booking'    => false,
				'last_booking'           => null,
				'orders'                 => array(),
				'orders_count'           => 0,
				'total_spent'            => 0,
				'last_activity_ts'       => 0,
				'duplicates'             => array(),
			);

			if ( '' !== $g_email && ! isset( $email_to_key[ $g_email ] ) ) {
				$email_to_key[ $g_email ] = $gkey;
			}
			if ( '' !== $g_phone && ! isset( $phone_to_key[ $g_phone ] ) ) {
				$phone_to_key[ $g_phone ] = $gkey;
			}
		}

		// 3. Aggregate all `jn_booking` records.
		$booking_posts = get_posts(
			array(
				'post_type'      => JN_Booking_CPT::POST_TYPE,
				'post_status'    => array_keys( JN_Booking_CPT::get_statuses() ),
				'posts_per_page' => 500,
				'orderby'        => 'ID',
				'order'          => 'DESC',
			)
		);

		foreach ( $booking_posts as $bpost ) {
			if ( ! $bpost instanceof \WP_Post ) {
				continue;
			}

			$bid     = (int) $bpost->ID;
			$b_uid   = (int) get_post_meta( $bid, '_jn_customer_user_id', true );
			$b_email = strtolower( sanitize_email( (string) get_post_meta( $bid, '_jn_customer_email', true ) ) );
			$b_phone = self::normalize_vn_phone( (string) get_post_meta( $bid, '_jn_customer_phone', true ) );
			$b_raw_p = (string) get_post_meta( $bid, '_jn_customer_phone_raw', true );
			$b_name  = (string) get_post_meta( $bid, '_jn_customer_name', true );

			// Ignore anonymized bookings erased by privacy requests.
			if ( 0 === $b_uid && '' === $b_phone && ( '' === $b_email || str_ends_with( $b_email, '.invalid' ) ) ) {
				continue;
			}

			$target_key = '';
			if ( $b_uid > 0 ) {
				$target_key = 'user:' . $b_uid;
				if ( ! isset( $map[ $target_key ] ) && get_userdata( $b_uid ) ) {
					$prof = JN_Customer_Profile::get_user_profile( $b_uid );
					if ( ! empty( $prof ) ) {
						$map[ $target_key ] = array_merge(
							$prof,
							array(
								'bookings'               => array(),
								'bookings_count'         => 0,
								'pending_bookings_count' => 0,
								'has_pending_booking'    => false,
								'last_booking'           => null,
								'orders'                 => array(),
								'orders_count'           => 0,
								'total_spent'            => 0,
								'last_activity_ts'       => 0,
								'duplicates'             => array(),
							)
						);
					}
				}
			} elseif ( '' !== $b_email && isset( $email_to_key[ $b_email ] ) ) {
				$target_key = $email_to_key[ $b_email ];
			} elseif ( '' !== $b_email ) {
				$target_key = 'guest:' . $b_email;
			} elseif ( '' !== $b_phone && isset( $phone_to_key[ $b_phone ] ) ) {
				$target_key = $phone_to_key[ $b_phone ];
			} elseif ( '' !== $b_phone ) {
				$target_key = 'guest:' . $b_phone;
			}

			if ( '' === $target_key ) {
				continue;
			}

			if ( ! isset( $map[ $target_key ] ) ) {
				$gprof = JN_Customer_Profile::get_guest_profile_data( $target_key );
				$ctype = (string) ( $gprof['customer_type'] ?? '' );
				$stype = (string) ( $gprof['source_type'] ?? JN_Customer_Profile::SOURCE_FORM );

				$map[ $target_key ] = array(
					'customer_key'           => $target_key,
					'user_id'                => 0,
					'is_guest'               => true,
					'full_name'              => '' !== $gprof['full_name'] ? (string) $gprof['full_name'] : $b_name,
					'phone'                  => '' !== $gprof['phone'] ? (string) $gprof['phone'] : $b_phone,
					'phone_local'            => '' !== $b_phone ? self::to_local_phone( $b_phone ) : '',
					'phone_raw'              => '' !== $b_raw_p ? $b_raw_p : $b_phone,
					'email'                  => '' !== $gprof['email'] ? (string) $gprof['email'] : $b_email,
					'customer_type'          => $ctype,
					'customer_type_label'    => JN_Customer_Profile::get_customer_type_label( $ctype ),
					'business_name'          => (string) ( $gprof['business_name'] ?? '' ),
					'province_city'          => (string) ( $gprof['province_city'] ?? '' ),
					'source_type'            => $stype,
					'source_type_label'      => JN_Customer_Profile::get_source_type_label( $stype ),
					'source_detail'          => (string) ( $gprof['source_detail'] ?? '' ),
					'tags'                   => JN_Customer_Profile::sanitize_tags( $gprof['tags'] ?? array() ),
					'internal_notes'         => is_array( $gprof['internal_notes'] ?? null ) ? $gprof['internal_notes'] : array(),
					'consent_log'            => is_array( $gprof['consent_log'] ?? null ) ? $gprof['consent_log'] : array(),
					'registered_at'          => '',
					'bookings'               => array(),
					'bookings_count'         => 0,
					'pending_bookings_count' => 0,
					'has_pending_booking'    => false,
					'last_booking'           => null,
					'orders'                 => array(),
					'orders_count'           => 0,
					'total_spent'            => 0,
					'last_activity_ts'       => 0,
					'duplicates'             => array(),
				);
			}

			// Backfill missing contact or consent log from booking.
			if ( '' === $map[ $target_key ]['full_name'] && '' !== $b_name ) {
				$map[ $target_key ]['full_name'] = $b_name;
			}
			if ( '' === $map[ $target_key ]['phone'] && '' !== $b_phone ) {
				$map[ $target_key ]['phone']       = $b_phone;
				$map[ $target_key ]['phone_local'] = self::to_local_phone( $b_phone );
			}
			if ( '' === $map[ $target_key ]['email'] && '' !== $b_email ) {
				$map[ $target_key ]['email'] = $b_email;
			}

			$b_code      = (string) get_post_meta( $bid, '_jn_booking_code', true );
			$b_status    = (string) $bpost->post_status;
			$b_confirmed = (string) get_post_meta( $bid, '_jn_confirmed_start', true );
			$b_ts        = (int) strtotime( $bpost->post_date );
			$pstate      = JN_Booking_Service::get_derived_payment_state( $bid );

			$booking_summary = array(
				'booking_id'       => $bid,
				'booking_code'     => '' !== $b_code ? $b_code : '#' . $bid,
				'package_title'    => (string) get_post_meta( $bid, '_jn_package_title', true ),
				'format'           => (string) get_post_meta( $bid, '_jn_format', true ),
				'format_label'     => (string) get_post_meta( $bid, '_jn_format_label', true ),
				'preferred_window' => (string) get_post_meta( $bid, '_jn_preferred_window_label', true ),
				'confirmed_start'  => $b_confirmed,
				'status'           => $b_status,
				'status_label'     => JN_Booking_CPT::get_status_label( $b_status ),
				'payment_state'    => $pstate['state'],
				'payment_label'    => $pstate['label'],
				'order_id'         => (int) $pstate['order_id'],
				'submitted_at'     => get_the_date( 'd/m/Y H:i', $bpost ),
				'submitted_ts'     => $b_ts,
				'edit_url'         => admin_url( 'post.php?post=' . $bid . '&action=edit' ),
			);

			$map[ $target_key ]['bookings'][] = $booking_summary;
			++$map[ $target_key ]['bookings_count'];

			if ( JN_Booking_CPT::STATUS_PENDING === $b_status ) {
				++$map[ $target_key ]['pending_bookings_count'];
				$map[ $target_key ]['has_pending_booking'] = true;
			}

			if ( null === $map[ $target_key ]['last_booking'] || $b_ts > (int) $map[ $target_key ]['last_booking']['submitted_ts'] ) {
				$map[ $target_key ]['last_booking'] = $booking_summary;
			}
			if ( $b_ts > (int) $map[ $target_key ]['last_activity_ts'] ) {
				$map[ $target_key ]['last_activity_ts'] = $b_ts;
			}

			// Ensure booking consent snapshot appears in read-only consent log if not already present.
			$c_ts = (string) get_post_meta( $bid, '_jn_consent_timestamp', true );
			if ( '' !== $c_ts && empty( $map[ $target_key ]['consent_log'] ) ) {
				$map[ $target_key ]['consent_log'][] = array(
					'timestamp'      => $c_ts,
					'source'         => 'Biểu mẫu đặt lịch',
					'policy_page_id' => (int) get_post_meta( $bid, '_jn_consent_policy_page_id', true ),
					'policy_version' => (string) get_post_meta( $bid, '_jn_consent_policy_version', true ),
					'text_snapshot'  => (string) get_post_meta( $bid, '_jn_consent_text_snapshot', true ),
					'reference'      => $b_code,
				);
			}
		}

		// 4. Aggregate all WooCommerce HPOS orders (`WC_Order`).
		if ( function_exists( 'wc_get_orders' ) ) {
			$orders = wc_get_orders(
				array(
					'limit'   => 500,
					'orderby' => 'ID',
					'order'   => 'DESC',
					'type'    => 'shop_order',
				)
			);

			if ( is_array( $orders ) ) {
				foreach ( $orders as $order ) {
					if ( ! $order instanceof \WC_Order ) {
						continue;
					}

					$oid     = (int) $order->get_id();
					$o_uid   = (int) $order->get_customer_id();
					$o_email = strtolower( sanitize_email( $order->get_billing_email() ) );
					$o_phone = self::normalize_vn_phone( (string) $order->get_billing_phone() );
					$o_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

					$target_key = '';
					if ( $o_uid > 0 && isset( $map[ 'user:' . $o_uid ] ) ) {
						$target_key = 'user:' . $o_uid;
					} elseif ( '' !== $o_email && isset( $email_to_key[ $o_email ] ) ) {
						$target_key = $email_to_key[ $o_email ];
					} elseif ( '' !== $o_email ) {
						$target_key = 'guest:' . $o_email;
					} elseif ( '' !== $o_phone && isset( $phone_to_key[ $o_phone ] ) ) {
						$target_key = $phone_to_key[ $o_phone ];
					} elseif ( '' !== $o_phone ) {
						$target_key = 'guest:' . $o_phone;
					}

					if ( '' === $target_key ) {
						continue;
					}

					if ( ! isset( $map[ $target_key ] ) ) {
						$gprof = JN_Customer_Profile::get_guest_profile_data( $target_key );
						$ctype = (string) ( $gprof['customer_type'] ?? '' );
						$stype = '' !== (string) ( $gprof['source_type'] ?? '' ) ? (string) $gprof['source_type'] : JN_Customer_Profile::SOURCE_CHECKOUT;

						$map[ $target_key ] = array(
							'customer_key'           => $target_key,
							'user_id'                => 0,
							'is_guest'               => true,
							'full_name'              => '' !== $gprof['full_name'] ? (string) $gprof['full_name'] : $o_name,
							'phone'                  => '' !== $gprof['phone'] ? (string) $gprof['phone'] : $o_phone,
							'phone_local'            => '' !== $o_phone ? self::to_local_phone( $o_phone ) : '',
							'phone_raw'              => (string) $order->get_billing_phone(),
							'email'                  => '' !== $gprof['email'] ? (string) $gprof['email'] : $o_email,
							'customer_type'          => $ctype,
							'customer_type_label'    => JN_Customer_Profile::get_customer_type_label( $ctype ),
							'business_name'          => (string) ( $gprof['business_name'] ?: $order->get_billing_company() ),
							'province_city'          => (string) ( $gprof['province_city'] ?: $order->get_billing_city() ),
							'source_type'            => $stype,
							'source_type_label'      => JN_Customer_Profile::get_source_type_label( $stype ),
							'source_detail'          => (string) ( $gprof['source_detail'] ?? '' ),
							'tags'                   => JN_Customer_Profile::sanitize_tags( $gprof['tags'] ?? array() ),
							'internal_notes'         => is_array( $gprof['internal_notes'] ?? null ) ? $gprof['internal_notes'] : array(),
							'consent_log'            => is_array( $gprof['consent_log'] ?? null ) ? $gprof['consent_log'] : array(),
							'registered_at'          => '',
							'bookings'               => array(),
							'bookings_count'         => 0,
							'pending_bookings_count' => 0,
							'has_pending_booking'    => false,
							'last_booking'           => null,
							'orders'                 => array(),
							'orders_count'           => 0,
							'total_spent'            => 0,
							'last_activity_ts'       => 0,
							'duplicates'             => array(),
						);
					}

					$item_names = array();
					foreach ( $order->get_items() as $item ) {
						$item_names[] = $item->get_name();
					}

					$o_status    = $order->get_status();
					$o_total     = (float) $order->get_total();
					$date_obj    = $order->get_date_created();
					$o_ts        = $date_obj ? $date_obj->getTimestamp() : 0;
					$o_date_text = $date_obj ? $date_obj->date_i18n( 'd/m/Y H:i' ) : '—';

					$map[ $target_key ]['orders'][] = array(
						'order_id'     => $oid,
						'order_number' => $order->get_order_number(),
						'status'       => $o_status,
						'status_label' => wc_get_order_status_name( $o_status ),
						'total'        => $o_total,
						'items_label'  => implode( ', ', $item_names ),
						'created_at'   => $o_date_text,
						'created_ts'   => $o_ts,
						'edit_url'     => admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $oid ),
					);

					++$map[ $target_key ]['orders_count'];

					if ( $order->is_paid() || in_array( $o_status, array( 'processing', 'completed' ), true ) ) {
						$map[ $target_key ]['total_spent'] += $o_total;
					}

					if ( $o_ts > (int) $map[ $target_key ]['last_activity_ts'] ) {
						$map[ $target_key ]['last_activity_ts'] = $o_ts;
					}
				}
			}
		}

		// Populate duplicate flags on each customer record for list badge display.
		foreach ( $map as $ckey => $rec ) {
			$map[ $ckey ]['duplicates'] = self::detect_duplicates(
				$ckey,
				(string) $rec['email'],
				(string) $rec['phone']
			);
		}

		return $map;
	}
}
