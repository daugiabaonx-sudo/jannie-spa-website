<?php
/**
 * Custom Capabilities & Role "Trợ lý lịch hẹn" (`jn_booking_assistant`) for Jannie Nguyen Academy.
 *
 * Grants full booking capabilities + customer management + order read/booking-order capabilities
 * to the assistant role while preventing privilege escalation onto administrator/shop_manager accounts.
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
	 * Primary capability for managing bookings and booking settings.
	 */
	const CAP_MANAGE = 'manage_jn_bookings';

	/**
	 * Option flag version to sync capabilities once per version bump.
	 */
	const CAPS_VERSION = '1.0.0';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'maybe_sync_roles_and_caps' ), 7 );
		add_filter( 'editable_roles', array( __CLASS__, 'restrict_assistant_editable_roles' ), 20, 1 );
		add_filter( 'map_meta_cap', array( __CLASS__, 'restrict_assistant_user_capabilities' ), 20, 4 );
	}

	/**
	 * Return all custom booking capabilities.
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
	 * Ensure the `jn_booking_assistant` role exists and capabilities are granted to
	 * `administrator`, `shop_manager`, and `jn_booking_assistant`.
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
		update_option( 'jn_booking_caps_version', self::CAPS_VERSION, true );
	}

	/**
	 * Synchronize roles and capabilities immediately.
	 *
	 * @return void
	 */
	public static function sync_roles_and_caps(): void {
		$booking_caps = self::get_booking_capabilities();

		$assistant_caps = array(
			'read'                     => true,
			'view_admin_dashboard'     => true,
			// Customer management capabilities (scoped to `customer` role via map_meta_cap).
			'list_users'               => true,
			'edit_users'               => true,
			'create_users'             => true,
			// Read WooCommerce orders and view order items in admin.
			'read_shop_order'          => true,
			'read_private_shop_orders' => true,
			'edit_shop_order'          => true,
			'edit_shop_orders'         => true,
			'edit_others_shop_orders'  => true,
		);

		foreach ( $booking_caps as $cap ) {
			$assistant_caps[ $cap ] = true;
		}

		$existing = get_role( self::ROLE_ASSISTANT );
		if ( null === $existing ) {
			add_role( self::ROLE_ASSISTANT, self::ROLE_ASSISTANT_LABEL, $assistant_caps );
		} else {
			global $wp_roles;
			if ( $wp_roles instanceof \WP_Roles && isset( $wp_roles->roles[ self::ROLE_ASSISTANT ] ) ) {
				$wp_roles->roles[ self::ROLE_ASSISTANT ]['name'] = self::ROLE_ASSISTANT_LABEL;
				$wp_roles->role_names[ self::ROLE_ASSISTANT ]    = self::ROLE_ASSISTANT_LABEL;
			}
			foreach ( $assistant_caps as $cap => $grant ) {
				if ( $grant ) {
					$existing->add_cap( $cap );
				}
			}
		}

		foreach ( array( 'administrator', 'shop_manager' ) as $role_slug ) {
			$role = get_role( $role_slug );
			if ( $role instanceof \WP_Role ) {
				foreach ( $booking_caps as $cap ) {
					$role->add_cap( $cap );
				}
			}
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

		return user_can( $user_id, self::CAP_MANAGE ) || user_can( $user_id, 'manage_woocommerce' ) || user_can( $user_id, 'manage_options' );
	}

	/**
	 * Ensure `jn_booking_assistant` can only assign or edit the `customer` role.
	 *
	 * @param array<string, array<string, mixed>> $roles Editable roles.
	 * @return array<string, array<string, mixed>>
	 */
	public static function restrict_assistant_editable_roles( array $roles ): array {
		$user = wp_get_current_user();
		if ( ! $user instanceof \WP_User || ! $user->exists() ) {
			return $roles;
		}

		if ( in_array( self::ROLE_ASSISTANT, (array) $user->roles, true ) && ! user_can( $user, 'manage_options' ) ) {
			return isset( $roles['customer'] ) ? array( 'customer' => $roles['customer'] ) : array();
		}

		return $roles;
	}

	/**
	 * Prevent `jn_booking_assistant` from editing, deleting, or promoting non-customer users
	 * (such as administrators, shop managers, or editors).
	 *
	 * @param string[] $caps    Primitive capabilities required.
	 * @param string   $cap     Capability being checked.
	 * @param int      $user_id User ID performing the check.
	 * @param mixed[]  $args    Additional context arguments (e.g. target user/post ID).
	 * @return string[]
	 */
	public static function restrict_assistant_user_capabilities( array $caps, string $cap, int $user_id, array $args ): array {
		if ( ! in_array( $cap, array( 'edit_user', 'delete_user', 'remove_user', 'promote_user' ), true ) ) {
			return $caps;
		}

		$actor = get_userdata( $user_id );
		if ( ! $actor instanceof \WP_User || user_can( $actor, 'manage_options' ) ) {
			return $caps;
		}

		if ( ! in_array( self::ROLE_ASSISTANT, (array) $actor->roles, true ) ) {
			return $caps;
		}

		// Assistants can never delete or remove users.
		if ( in_array( $cap, array( 'delete_user', 'remove_user' ), true ) ) {
			return array( 'do_not_allow' );
		}

		$target_user_id = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( $target_user_id > 0 && $target_user_id !== $user_id ) {
			$target_user = get_userdata( $target_user_id );
			if ( ! $target_user instanceof \WP_User ) {
				return array( 'do_not_allow' );
			}

			$target_roles = (array) $target_user->roles;
			$allowed_only = empty( array_diff( $target_roles, array( 'customer', 'subscriber' ) ) );
			if ( ! $allowed_only ) {
				return array( 'do_not_allow' );
			}
		}

		return $caps;
	}
}
