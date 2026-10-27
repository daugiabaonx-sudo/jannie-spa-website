<?php
/**
 * Customer Upsert, Vietnamese Phone Normalization & Guest-to-Account Linking Service.
 *
 * - Normalizes valid Vietnamese mobile and landline numbers to E.164 (`+84...`).
 * - Matches existing customers by email first, then by normalized phone.
 * - Creates a WooCommerce customer on booking only when `auto_create_account === 'yes'`
 *   (default `'no'` per Project Context Card).
 * - Automatically links guest bookings and orders when a guest later registers an account.
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
			if ( $user instanceof \WP_User ) {
				return $user;
			}
		}

		if ( '' !== $normalized_phone ) {
			$local_phone = self::to_local_phone( $normalized_phone );
			$users       = get_users(
				array(
					'number'     => 1,
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

			if ( ! empty( $users ) && $users[0] instanceof \WP_User ) {
				return $users[0];
			}
		}

		return null;
	}

	/**
	 * Upsert customer on booking submission.
	 *
	 * - Matches by email first, then normalized phone.
	 * - Creates a WooCommerce customer ONLY when `auto_create_account === 'yes'`.
	 * - Otherwise returns `user_id = 0` so the booking stores a guest snapshot that links on registration.
	 *
	 * @param array<string, string> $data Customer data (`name`, `email`, `phone` normalized to `+84...`).
	 * @return int Matched or created `WP_User` ID, or `0` for guest.
	 */
	public static function upsert_customer_for_booking( array $data ): int {
		$name  = sanitize_text_field( $data['name'] ?? '' );
		$email = strtolower( sanitize_email( $data['email'] ?? '' ) );
		$phone = self::normalize_vn_phone( (string) ( $data['phone'] ?? '' ) );

		$existing = self::find_existing_user( $email, $phone );
		if ( $existing instanceof \WP_User ) {
			$user_id = (int) $existing->ID;
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
	 * Hook callback on user login to ensure any guest bookings with matching email/phone are linked.
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
	 * Link any previous guest bookings (and their linked WooCommerce orders) to the registered user
	 * when email or normalized phone matches.
	 *
	 * @param int $user_id WordPress User ID.
	 * @return int Number of guest bookings linked.
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

		if ( empty( $meta_or ) ) {
			return 0;
		}

		if ( count( $meta_or ) > 1 ) {
			$meta_or['relation'] = 'OR';
		}

		$booking_ids = get_posts(
			array(
				'post_type'      => JN_Booking_CPT::POST_TYPE,
				'post_status'    => array_keys( JN_Booking_CPT::get_statuses() ),
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					$meta_or,
				),
			)
		);

		$linked_count = 0;
		foreach ( $booking_ids as $booking_id ) {
			$booking_id   = (int) $booking_id;
			$current_user = (int) get_post_meta( $booking_id, '_jn_customer_user_id', true );
			if ( 0 === $current_user ) {
				update_post_meta( $booking_id, '_jn_customer_user_id', $user_id );
				++$linked_count;

				$guest_phone = (string) get_post_meta( $booking_id, '_jn_customer_phone', true );
				$guest_name  = (string) get_post_meta( $booking_id, '_jn_customer_name', true );
				self::sync_user_contact_meta( $user_id, $guest_name, $email, $guest_phone );
			}

			// Also link the booking's WooCommerce order if it was created for a guest.
			$order_id = (int) get_post_meta( $booking_id, '_jn_order_id', true );
			if ( $order_id > 0 && function_exists( 'wc_get_order' ) ) {
				$order = wc_get_order( $order_id );
				if ( $order instanceof \WC_Order && 0 === (int) $order->get_customer_id() ) {
					$order->set_customer_id( $user_id );
					$order->save();
				}
			}
		}

		return $linked_count;
	}
}
