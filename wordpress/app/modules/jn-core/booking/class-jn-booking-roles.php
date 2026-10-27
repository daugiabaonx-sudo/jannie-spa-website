<?php
/**
 * Role & Capability Matrix, Least-Privilege Guards, Staff Account Provisioning & 2FA Enforcement.
 *
 * Implements Step 6 Task 6:
 * - 4 Staff Roles ("Tài khoản nhân sự"):
 *   1. Quản trị (`administrator` — owner/developer only)
 *   2. Quản lý cửa hàng (`shop_manager`)
 *   3. Trợ lý lịch hẹn (`jn_booking_assistant`)
 *   4. Biên tập nội dung (`editor`)
 * - Customer Role (`customer` — kept away from admin bar and `wp-admin`).
 * - Requires Two Factor (2FA) for every staff role (`administrator`, `shop_manager`, `jn_booking_assistant`, `editor`).
 * - Enforces least privilege via `editable_roles` and `map_meta_cap`.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Booking_Roles
 */
class JN_Booking_Roles {

	/**
	 * Custom assistant role slug.
	 */
	const ROLE_ASSISTANT = 'jn_booking_assistant';

	/**
	 * Custom assistant role Vietnamese name.
	 */
	const ROLE_ASSISTANT_LABEL = 'Trợ lý lịch hẹn';

	/**
	 * Capability for managing bookings (`Lịch hẹn` & `Lịch theo ngày`).
	 */
	const CAP_MANAGE = 'manage_jn_bookings';

	/**
	 * Capability for managing booking engine settings (`Lịch hẹn -> Cài đặt`).
	 */
	const CAP_SETTINGS = 'manage_jn_booking_settings';

	/**
	 * Capability for managing customer CRM profiles & internal notes (`Khách hàng`).
	 */
	const CAP_CUSTOMERS = 'manage_jn_customers';

	/**
	 * Capability for exporting customer and booking CSV files.
	 */
	const CAP_EXPORT = 'export_jn_data';

	/**
	 * Option flag version to sync capabilities and staff accounts once per version bump.
	 */
	const CAPS_VERSION = '1.3.2';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'maybe_sync_roles_and_caps' ), 7 );
		add_filter( 'editable_roles', array( __CLASS__, 'restrict_editable_roles' ), 20, 1 );
		add_filter( 'map_meta_cap', array( __CLASS__, 'restrict_user_and_domain_capabilities' ), 20, 4 );

		// Enforce Two Factor (2FA) for all 4 staff roles.
		add_filter( 'two_factor_providers', array( __CLASS__, 'filter_two_factor_providers' ), 20, 1 );
		add_filter( 'two_factor_enabled_providers_for_user', array( __CLASS__, 'enforce_staff_two_factor_providers' ), 20, 2 );
		add_filter( 'two_factor_is_required_for_user', array( __CLASS__, 'enforce_staff_two_factor_required' ), 20, 2 );

		// Keep customers away from the front-end admin bar and `wp-admin`.
		add_filter( 'show_admin_bar', array( __CLASS__, 'filter_show_admin_bar' ), 99, 1 );
		add_action( 'admin_init', array( __CLASS__, 'block_customer_wp_admin_access' ), 1 );
		add_filter( 'woocommerce_prevent_admin_access', array( __CLASS__, 'filter_wc_prevent_admin_access' ), 20, 1 );
		add_filter( 'woocommerce_disable_admin_bar', array( __CLASS__, 'filter_wc_prevent_admin_access' ), 20, 1 );
	}

	/**
	 * Return the list of all 4 staff role slugs.
	 *
	 * @return string[]
	 */
	public static function get_staff_roles(): array {
		return array(
			'administrator',
			'shop_manager',
			self::ROLE_ASSISTANT,
			'editor',
		);
	}

	/**
	 * Check whether a user belongs to any of the 4 staff roles without triggering `map_meta_cap` recursion.
	 *
	 * @param int|\WP_User $user Optional user ID or WP_User object. Defaults to current user.
	 * @return bool
	 */
	public static function is_staff_user( $user = 0 ): bool {
		if ( 0 === $user || null === $user ) {
			$user = wp_get_current_user();
		} elseif ( is_numeric( $user ) ) {
			$user = get_userdata( (int) $user );
		}

		if ( ! $user instanceof \WP_User || ! $user->exists() ) {
			return false;
		}

		$user_roles = (array) $user->roles;
		if ( ! empty( array_intersect( $user_roles, self::get_staff_roles() ) ) ) {
			return true;
		}

		$allcaps = is_array( $user->allcaps ) ? $user->allcaps : array();
		return ! empty( $allcaps['manage_options'] )
			|| ! empty( $allcaps['manage_woocommerce'] )
			|| ! empty( $allcaps[ self::CAP_MANAGE ] )
			|| ! empty( $allcaps['edit_pages'] );
	}

	/**
	 * Check whether a user is a customer/subscriber with no staff roles.
	 *
	 * @param int|\WP_User $user Optional user ID or WP_User object. Defaults to current user.
	 * @return bool
	 */
	public static function is_customer_only_user( $user = 0 ): bool {
		if ( 0 === $user || null === $user ) {
			$user = wp_get_current_user();
		} elseif ( is_numeric( $user ) ) {
			$user = get_userdata( (int) $user );
		}

		if ( ! $user instanceof \WP_User || ! $user->exists() ) {
			return false;
		}

		return ! self::is_staff_user( $user );
	}

	/**
	 * Return all custom booking CPT capabilities.
	 *
	 * @return string[]
	 */
	public static function get_booking_capabilities(): array {
		return array(
			self::CAP_MANAGE,
			'edit_jn_booking',
			'read_jn_booking',
			'delete_jn_booking',
			'edit_jn_bookings',
			'edit_others_jn_bookings',
			'publish_jn_bookings',
			'read_private_jn_bookings',
			'delete_jn_bookings',
			'delete_private_jn_bookings',
			'delete_published_jn_bookings',
			'delete_others_jn_bookings',
			'edit_private_jn_bookings',
			'edit_published_jn_bookings',
		);
	}

	/**
	 * Return booking capabilities granted to `jn_booking_assistant` (excludes permanent deletion).
	 *
	 * @return string[]
	 */
	public static function get_assistant_booking_capabilities(): array {
		return array(
			self::CAP_MANAGE,
			'edit_jn_booking',
			'read_jn_booking',
			'edit_jn_bookings',
			'edit_others_jn_bookings',
			'publish_jn_bookings',
			'read_private_jn_bookings',
			'edit_private_jn_bookings',
			'edit_published_jn_bookings',
		);
	}

	/**
	 * Ensure roles, capabilities, Vietnamese role labels, and placeholder staff accounts are synced.
	 *
	 * @return void
	 */
	public static function maybe_sync_roles_and_caps(): void {
		$installed_version = get_option( 'jn_booking_caps_version', '' );
		$assistant_role    = get_role( self::ROLE_ASSISTANT );

		if ( self::CAPS_VERSION === $installed_version && null !== $assistant_role ) {
			return;
		}

		self::sync_roles_and_caps();
		self::ensure_staff_accounts();
		update_option( 'jn_booking_caps_version', self::CAPS_VERSION, true );
	}

	/**
	 * Synchronize roles, Vietnamese role labels, and least-privilege capabilities immediately.
	 *
	 * @return void
	 */
	public static function sync_roles_and_caps(): void {
		$all_booking_caps       = self::get_booking_capabilities();
		$assistant_booking_caps = self::get_assistant_booking_capabilities();

		$assistant_caps = array(
			'read'                     => true,
			'view_admin_dashboard'     => true,
			self::CAP_MANAGE           => true,
			self::CAP_CUSTOMERS        => true,
			self::CAP_EXPORT           => true,
			// Customer user management capabilities (strictly scoped to `customer` role via map_meta_cap).
			'list_users'               => true,
			'edit_users'               => true,
			'create_users'             => true,
			// Read WooCommerce orders and create/edit booking-linked orders in admin.
			'read_shop_order'          => true,
			'read_private_shop_orders' => true,
			'edit_shop_order'          => true,
			'edit_shop_orders'         => true,
			'edit_others_shop_orders'  => true,
		);

		foreach ( $assistant_booking_caps as $cap ) {
			$assistant_caps[ $cap ] = true;
		}

		$existing = get_role( self::ROLE_ASSISTANT );
		if ( null === $existing ) {
			add_role( self::ROLE_ASSISTANT, self::ROLE_ASSISTANT_LABEL, $assistant_caps );
		} else {
			foreach ( $assistant_caps as $cap => $grant ) {
				if ( $grant ) {
					$existing->add_cap( $cap );
				}
			}
			// Ensure assistant does NOT have content, product, settings, user deletion, or permanent booking deletion capabilities.
			$forbidden_for_assistant = array(
				self::CAP_SETTINGS,
				'delete_jn_booking',
				'delete_jn_bookings',
				'delete_private_jn_bookings',
				'delete_published_jn_bookings',
				'delete_others_jn_bookings',
				'manage_woocommerce',
				'manage_options',
				'edit_posts',
				'edit_pages',
				'edit_products',
				'delete_users',
			);
			foreach ( $forbidden_for_assistant as $fcap ) {
				$existing->remove_cap( $fcap );
			}
		}

		// Grant full booking, customer, settings, and export capabilities to `administrator` and `shop_manager`.
		foreach ( array( 'administrator', 'shop_manager' ) as $role_slug ) {
			$role = get_role( $role_slug );
			if ( $role instanceof \WP_Role ) {
				foreach ( $all_booking_caps as $cap ) {
					$role->add_cap( $cap );
				}
				$role->add_cap( self::CAP_SETTINGS );
				$role->add_cap( self::CAP_CUSTOMERS );
				$role->add_cap( self::CAP_EXPORT );
			}
		}

		// Ensure `editor` ("Biên tập nội dung") has strictly editorial capabilities and NO booking/customer/commerce caps.
		$editor_role = get_role( 'editor' );
		if ( $editor_role instanceof \WP_Role ) {
			foreach ( $all_booking_caps as $cap ) {
				$editor_role->remove_cap( $cap );
			}
			$editor_role->remove_cap( self::CAP_SETTINGS );
			$editor_role->remove_cap( self::CAP_CUSTOMERS );
			$editor_role->remove_cap( self::CAP_EXPORT );
			$editor_role->remove_cap( 'manage_woocommerce' );
			$editor_role->remove_cap( 'list_users' );
			$editor_role->remove_cap( 'edit_users' );
			$editor_role->remove_cap( 'create_users' );
		}

		// Ensure `customer` has only `read` and no staff capabilities.
		$customer_role = get_role( 'customer' );
		if ( $customer_role instanceof \WP_Role ) {
			foreach ( $all_booking_caps as $cap ) {
				$customer_role->remove_cap( $cap );
			}
			$customer_role->remove_cap( self::CAP_SETTINGS );
			$customer_role->remove_cap( self::CAP_CUSTOMERS );
			$customer_role->remove_cap( self::CAP_EXPORT );
			$customer_role->remove_cap( 'edit_posts' );
			$customer_role->remove_cap( 'view_admin_dashboard' );
		}

		// Update Vietnamese display names for the roles in `$wp_roles`.
		global $wp_roles;
		if ( $wp_roles instanceof \WP_Roles ) {
			$vn_labels = array(
				'administrator'      => 'Quản trị',
				'shop_manager'       => 'Quản lý cửa hàng',
				self::ROLE_ASSISTANT => self::ROLE_ASSISTANT_LABEL,
				'editor'             => 'Biên tập nội dung',
				'customer'           => 'Khách hàng',
			);
			foreach ( $vn_labels as $rslug => $rlabel ) {
				if ( isset( $wp_roles->roles[ $rslug ] ) ) {
					$wp_roles->roles[ $rslug ]['name'] = $rlabel;
					$wp_roles->role_names[ $rslug ]    = $rlabel;
				}
			}
		}
	}

	/**
	 * Provision the canonical placeholder staff accounts when `STAFF ACCOUNTS` in the
	 * Project Context Card is `UNKNOWN`, and configure Two Factor on every staff account.
	 *
	 * @return array<string, int> Map of role slug to provisioned/existing user ID.
	 */
	public static function ensure_staff_accounts(): array {
		$staff_specs = array(
			'shop_manager'       => array(
				'user_login'   => 'quan_ly_cua_hang',
				'user_email'   => 'quanly@jannienguyen.vn',
				'display_name' => 'PLACEHOLDER – Quản lý cửa hàng',
				'first_name'   => 'Quản lý cửa hàng',
				'last_name'    => '(Placeholder)',
			),
			self::ROLE_ASSISTANT => array(
				'user_login'   => 'tro_ly_lich_hen',
				'user_email'   => 'troly@jannienguyen.vn',
				'display_name' => 'PLACEHOLDER – Trợ lý lịch hẹn',
				'first_name'   => 'Trợ lý lịch hẹn',
				'last_name'    => '(Placeholder)',
			),
			'editor'             => array(
				'user_login'   => 'bien_tap_noi_dung',
				'user_email'   => 'bientap@jannienguyen.vn',
				'display_name' => 'PLACEHOLDER – Biên tập nội dung',
				'first_name'   => 'Biên tập nội dung',
				'last_name'    => '(Placeholder)',
			),
		);

		$result_ids = array();

		// Find existing administrator(s) and ensure 2FA metadata is enabled.
		$admins = get_users(
			array(
				'role'   => 'administrator',
				'number' => 10,
			)
		);
		foreach ( $admins as $admin_user ) {
			if ( $admin_user instanceof \WP_User ) {
				$result_ids['administrator'] = (int) $admin_user->ID;
				self::enable_default_two_factor_for_user( (int) $admin_user->ID );
			}
		}

		foreach ( $staff_specs as $role_slug => $spec ) {
			$existing_user = get_user_by( 'login', $spec['user_login'] );
			if ( ! $existing_user instanceof \WP_User ) {
				$existing_user = get_user_by( 'email', $spec['user_email'] );
			}

			if ( ! $existing_user instanceof \WP_User ) {
				$user_id = wp_insert_user(
					array(
						'user_login'   => $spec['user_login'],
						'user_pass'    => wp_generate_password( 24, true, true ),
						'user_email'   => $spec['user_email'],
						'display_name' => $spec['display_name'],
						'first_name'   => $spec['first_name'],
						'last_name'    => $spec['last_name'],
						'role'         => $role_slug,
					)
				);
				if ( is_wp_error( $user_id ) || $user_id <= 0 ) {
					continue;
				}
				$uid = (int) $user_id;
			} else {
				$uid = (int) $existing_user->ID;
				$existing_user->set_role( $role_slug );
			}

			update_user_meta( $uid, '_jn_is_placeholder_staff', 'yes' );
			update_user_meta( $uid, '_jn_staff_role_label', $spec['first_name'] );
			self::enable_default_two_factor_for_user( $uid );
			$result_ids[ $role_slug ] = $uid;
		}

		return $result_ids;
	}

	/**
	 * Ensure a staff user has `Two_Factor_Email` enabled in user meta as the baseline 2FA provider.
	 *
	 * @param int $user_id Staff user ID.
	 * @return void
	 */
	public static function enable_default_two_factor_for_user( int $user_id ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		$enabled = get_user_meta( $user_id, '_two_factor_enabled_providers', true );
		if ( ! is_array( $enabled ) ) {
			$enabled = array();
		}
		if ( ! in_array( 'Two_Factor_Email', $enabled, true ) ) {
			$enabled[] = 'Two_Factor_Email';
			update_user_meta( $user_id, '_two_factor_enabled_providers', array_values( array_unique( $enabled ) ) );
		}

		$primary = (string) get_user_meta( $user_id, '_two_factor_provider', true );
		if ( '' === $primary ) {
			update_user_meta( $user_id, '_two_factor_provider', 'Two_Factor_Email' );
		}
	}

	/**
	 * Remove `Two_Factor_Dummy` so only real 2FA providers (`Two_Factor_Email`, `Two_Factor_Totp`, `Two_Factor_Backup_Codes`) exist.
	 *
	 * @param array<string, string> $providers Registered provider class map.
	 * @return array<string, string>
	 */
	public static function filter_two_factor_providers( array $providers ): array {
		unset( $providers['Two_Factor_Dummy'] );
		return $providers;
	}

	/**
	 * Enforce that every staff role (`administrator`, `shop_manager`, `jn_booking_assistant`, `editor`)
	 * always has at least `Two_Factor_Email` enabled and cannot bypass 2FA by clearing their providers.
	 *
	 * Confirmed filter in `two-factor/class-two-factor-core.php`: `two_factor_enabled_providers_for_user`.
	 *
	 * @param array<int|string, string> $enabled_providers Enabled provider keys.
	 * @param int                       $user_id           User ID.
	 * @return string[]
	 */
	public static function enforce_staff_two_factor_providers( $enabled_providers, int $user_id ): array {
		$list = is_array( $enabled_providers ) ? array_values( array_filter( $enabled_providers, 'is_string' ) ) : array();

		if ( self::is_staff_user( $user_id ) ) {
			if ( ! in_array( 'Two_Factor_Email', $list, true ) ) {
				$list[] = 'Two_Factor_Email';
			}
		}

		return array_values( array_unique( $list ) );
	}

	/**
	 * Enforce that Two Factor authentication is strictly required for every staff user on login.
	 *
	 * Confirmed filter in `two-factor/class-two-factor-core.php`: `two_factor_is_required_for_user`.
	 *
	 * @param bool         $is_required Whether 2FA is required.
	 * @param int|\WP_User $user        User object or user ID.
	 * @return bool
	 */
	public static function enforce_staff_two_factor_required( bool $is_required, $user ): bool {
		if ( self::is_staff_user( $user ) ) {
			return true;
		}
		return $is_required;
	}

	/**
	 * Hide the front-end WordPress Admin Bar for customers and any non-staff users.
	 *
	 * @param bool $show Whether to show the admin bar.
	 * @return bool
	 */
	public static function filter_show_admin_bar( bool $show ): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		if ( ! self::is_staff_user( get_current_user_id() ) ) {
			return false;
		}
		return $show;
	}

	/**
	 * Tell WooCommerce to prevent `wp-admin` access for non-staff users (`customer` / `subscriber`),
	 * while allowing all 4 staff roles (`administrator`, `shop_manager`, `jn_booking_assistant`, `editor`).
	 *
	 * @param bool $prevent Default WooCommerce prevent flag.
	 * @return bool
	 */
	public static function filter_wc_prevent_admin_access( bool $prevent ): bool {
		if ( ! is_user_logged_in() ) {
			return $prevent;
		}
		return ! self::is_staff_user( get_current_user_id() );
	}

	/**
	 * Redirect logged-in non-staff users (`customer` / `subscriber`) away from `/wp-admin/`
	 * to `/tai-khoan/` (`My Account`), except for legitimate AJAX or `admin-post.php` requests.
	 *
	 * @return void
	 */
	public static function block_customer_wp_admin_access(): void {
		if ( ! is_user_logged_in() || defined( 'DOING_AJAX' ) || defined( 'WP_CLI' ) ) {
			return;
		}

		global $pagenow;
		if ( 'admin-post.php' === $pagenow || 'admin-ajax.php' === $pagenow ) {
			return;
		}

		if ( ! self::is_staff_user( get_current_user_id() ) ) {
			$redirect_url = function_exists( 'wc_get_page_permalink' )
				? wc_get_page_permalink( 'myaccount' )
				: home_url( '/tai-khoan/' );
			if ( empty( $redirect_url ) ) {
				$redirect_url = home_url( '/tai-khoan/' );
			}
			wp_safe_redirect( $redirect_url, 302 );
			exit;
		}
	}

	/**
	 * Check whether the current user (or a specified user ID) can manage bookings.
	 *
	 * @param int $user_id Optional user ID. Defaults to current user.
	 * @return bool
	 */
	public static function user_can_manage_bookings( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return false;
		}

		return user_can( $user_id, self::CAP_MANAGE )
			|| user_can( $user_id, 'manage_woocommerce' )
			|| user_can( $user_id, 'manage_options' );
	}

	/**
	 * Check whether the user can manage booking engine settings (`Lịch hẹn -> Cài đặt`).
	 * Restricted to `administrator` and `shop_manager` (least privilege).
	 *
	 * @param int $user_id Optional user ID. Defaults to current user.
	 * @return bool
	 */
	public static function user_can_manage_booking_settings( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return false;
		}

		return user_can( $user_id, self::CAP_SETTINGS )
			|| user_can( $user_id, 'manage_woocommerce' )
			|| user_can( $user_id, 'manage_options' );
	}

	/**
	 * Check whether the user can manage Customer CRM profiles and internal notes ("Khách hàng").
	 * Granted to `administrator`, `shop_manager`, and `jn_booking_assistant`.
	 *
	 * @param int $user_id Optional user ID. Defaults to current user.
	 * @return bool
	 */
	public static function user_can_manage_customers( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return false;
		}

		return user_can( $user_id, self::CAP_CUSTOMERS )
			|| user_can( $user_id, self::CAP_MANAGE )
			|| user_can( $user_id, 'manage_woocommerce' )
			|| user_can( $user_id, 'manage_options' );
	}

	/**
	 * Check whether the user can export customer and booking CSV files.
	 * Granted to `administrator`, `shop_manager`, and `jn_booking_assistant`.
	 *
	 * @param int $user_id Optional user ID. Defaults to current user.
	 * @return bool
	 */
	public static function user_can_export_csv( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return false;
		}

		return user_can( $user_id, self::CAP_EXPORT )
			|| user_can( $user_id, 'manage_woocommerce' )
			|| user_can( $user_id, 'manage_options' );
	}

	/**
	 * Restrict editable roles per actor role (prevents privilege escalation):
	 * - `jn_booking_assistant` can only assign/edit `customer`.
	 * - `shop_manager` can assign/edit `customer`, `jn_booking_assistant`, `editor` (never `administrator`).
	 * - `editor` and `customer` cannot assign any roles.
	 *
	 * @param array<string, array<string, mixed>> $roles Editable roles.
	 * @return array<string, array<string, mixed>>
	 */
	public static function restrict_editable_roles( array $roles ): array {
		$user = wp_get_current_user();
		if ( ! $user instanceof \WP_User || ! $user->exists() ) {
			return $roles;
		}

		$user_roles = (array) $user->roles;
		if ( in_array( 'administrator', $user_roles, true ) || ! empty( $user->allcaps['manage_options'] ) ) {
			return $roles;
		}

		if ( in_array( 'shop_manager', $user_roles, true ) ) {
			unset( $roles['administrator'] );
			return $roles;
		}

		if ( in_array( self::ROLE_ASSISTANT, $user_roles, true ) ) {
			return isset( $roles['customer'] ) ? array( 'customer' => $roles['customer'] ) : array();
		}

		return array();
	}

	/**
	 * Enforce least-privilege boundaries across user management, bookings, and WooCommerce:
	 * - Non-admins (`shop_manager`, `jn_booking_assistant`, `editor`) can NEVER edit, delete, or promote `administrator` users.
	 * - `jn_booking_assistant` can only edit `customer`/`subscriber` users and can never delete any user.
	 * - `editor` cannot edit/create/delete any other user, cannot access `jn_booking`, and cannot access `shop_order`.
	 *
	 * IMPORTANT: Never call `user_can()` or `has_cap()` inside `map_meta_cap` to avoid infinite recursion.
	 *
	 * @param string[] $caps    Primitive capabilities required.
	 * @param string   $cap     Capability being checked.
	 * @param int      $user_id User ID performing the check.
	 * @param mixed[]  $args    Additional context arguments (e.g. target user/post ID).
	 * @return string[]
	 */
	public static function restrict_user_and_domain_capabilities( array $caps, string $cap, int $user_id, array $args ): array {
		$guarded_caps = array(
			'edit_user',
			'delete_user',
			'remove_user',
			'promote_user',
			'delete_shop_order',
			'delete_shop_orders',
			'edit_product',
			'edit_products',
			'edit_page',
			'edit_pages',
			'edit_post',
		);

		if ( ! in_array( $cap, $guarded_caps, true ) ) {
			return $caps;
		}

		$actor = get_userdata( $user_id );
		if ( ! $actor instanceof \WP_User ) {
			return $caps;
		}

		$actor_roles = (array) $actor->roles;
		if ( in_array( 'administrator', $actor_roles, true ) || ! empty( $actor->allcaps['manage_options'] ) ) {
			return $caps;
		}

		// 1. User management guards (`edit_user`, `delete_user`, `remove_user`, `promote_user`).
		if ( in_array( $cap, array( 'edit_user', 'delete_user', 'remove_user', 'promote_user' ), true ) ) {
			$target_user_id = isset( $args[0] ) ? (int) $args[0] : 0;

			// Nobody except an administrator can edit/delete/promote an administrator.
			if ( $target_user_id > 0 && $target_user_id !== $user_id ) {
				$target_user = get_userdata( $target_user_id );
				if ( ! $target_user instanceof \WP_User || in_array( 'administrator', (array) $target_user->roles, true ) ) {
					return array( 'do_not_allow' );
				}

				if ( in_array( self::ROLE_ASSISTANT, $actor_roles, true ) ) {
					if ( in_array( $cap, array( 'delete_user', 'remove_user' ), true ) ) {
						return array( 'do_not_allow' );
					}
					$target_roles = (array) $target_user->roles;
					$allowed_only = empty( array_diff( $target_roles, array( 'customer', 'subscriber' ) ) );
					if ( ! $allowed_only ) {
						return array( 'do_not_allow' );
					}
				}
			} elseif ( in_array( self::ROLE_ASSISTANT, $actor_roles, true ) && in_array( $cap, array( 'delete_user', 'remove_user' ), true ) ) {
				return array( 'do_not_allow' );
			}
		}

		// 2. Prevent `jn_booking_assistant` from deleting WooCommerce orders or editing pages/posts/products.
		if ( in_array( self::ROLE_ASSISTANT, $actor_roles, true ) && ! in_array( 'shop_manager', $actor_roles, true ) ) {
			if ( in_array( $cap, array( 'delete_shop_order', 'delete_shop_orders', 'edit_product', 'edit_products', 'edit_page', 'edit_pages', 'edit_post' ), true ) ) {
				$target_post_id = isset( $args[0] ) ? (int) $args[0] : 0;
				if ( $target_post_id > 0 ) {
					$ptype = get_post_type( $target_post_id );
					if ( JN_Booking_CPT::POST_TYPE !== $ptype && 'shop_order' !== $ptype && 'shop_order_placehold' !== $ptype ) {
						return array( 'do_not_allow' );
					}
				} elseif ( 'edit_post' !== $cap ) {
					return array( 'do_not_allow' );
				}
			}
		}

		return $caps;
	}
}
