<?php
/**
 * Custom Post Type `jn_booking` & Booking Statuses for Jannie Nguyen Academy.
 *
 * Registers the private `jn_booking` CPT (admin UI only; excluded from public queries,
 * search, XML sitemaps, and anonymous REST) and the 5 Vietnamese booking statuses.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Booking_CPT
 */
class JN_Booking_CPT {

	/**
	 * Post type slug.
	 */
	const POST_TYPE = 'jn_booking';

	/**
	 * Booking status constants.
	 */
	const STATUS_PENDING   = 'jn_pending';
	const STATUS_CONFIRMED = 'jn_confirmed';
	const STATUS_COMPLETED = 'jn_completed';
	const STATUS_NO_SHOW   = 'jn_no_show';
	const STATUS_CANCELLED = 'jn_cancelled';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_post_statuses' ), 6 );
		add_filter( 'wp_sitemaps_post_types', array( __CLASS__, 'exclude_from_sitemaps' ), 20, 1 );
		add_action( 'template_redirect', array( __CLASS__, 'guard_direct_frontend_access' ), 1 );
	}

	/**
	 * Return all booking statuses with their Vietnamese labels.
	 *
	 * @return array<string, string>
	 */
	public static function get_statuses(): array {
		return array(
			self::STATUS_PENDING   => 'Chờ xác nhận',
			self::STATUS_CONFIRMED => 'Đã xác nhận lịch',
			self::STATUS_COMPLETED => 'Hoàn thành',
			self::STATUS_NO_SHOW   => 'Vắng mặt',
			self::STATUS_CANCELLED => 'Đã hủy',
		);
	}

	/**
	 * Return human-readable Vietnamese label for a status slug.
	 *
	 * @param string $status Status slug.
	 * @return string
	 */
	public static function get_status_label( string $status ): string {
		$statuses = self::get_statuses();
		return $statuses[ $status ] ?? $status;
	}

	/**
	 * Check whether a status is a final (terminal) status.
	 *
	 * @param string $status Status slug.
	 * @return bool
	 */
	public static function is_final_status( string $status ): bool {
		return in_array(
			$status,
			array(
				self::STATUS_COMPLETED,
				self::STATUS_NO_SHOW,
				self::STATUS_CANCELLED,
			),
			true
		);
	}

	/**
	 * Register the private `jn_booking` Custom Post Type.
	 *
	 * @return void
	 */
	public static function register_post_type(): void {
		$labels = array(
			'name'               => 'Lịch hẹn',
			'singular_name'      => 'Lịch hẹn',
			'menu_name'          => 'Lịch hẹn',
			'name_admin_bar'     => 'Lịch hẹn Coaching',
			'add_new'            => 'Thêm lịch hẹn',
			'add_new_item'       => 'Thêm lịch hẹn Coaching mới',
			'new_item'           => 'Lịch hẹn mới',
			'edit_item'          => 'Chi tiết & Xếp lịch hẹn',
			'view_item'          => 'Xem lịch hẹn',
			'all_items'          => 'Tất cả lịch hẹn',
			'search_items'       => 'Tìm kiếm lịch hẹn',
			'not_found'          => 'Không tìm thấy lịch hẹn nào.',
			'not_found_in_trash' => 'Không có lịch hẹn nào trong thùng rác.',
		);

		$capabilities = array(
			'edit_post'              => 'edit_jn_booking',
			'read_post'              => 'read_jn_booking',
			'delete_post'            => 'delete_jn_booking',
			'edit_posts'             => 'edit_jn_bookings',
			'edit_others_posts'      => 'edit_others_jn_bookings',
			'publish_posts'          => 'publish_jn_bookings',
			'read_private_posts'     => 'read_private_jn_bookings',
			'delete_posts'           => 'delete_jn_bookings',
			'delete_private_posts'   => 'delete_private_jn_bookings',
			'delete_published_posts' => 'delete_published_jn_bookings',
			'delete_others_posts'    => 'delete_others_jn_bookings',
			'edit_private_posts'     => 'edit_private_jn_bookings',
			'edit_published_posts'   => 'edit_published_jn_bookings',
			'create_posts'           => 'edit_jn_bookings',
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => true,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'show_in_rest'        => false,
				'hierarchical'        => true, // Parent enrolment -> child sessions for Mentorship.
				'menu_position'       => 56,
				'menu_icon'           => 'dashicons-calendar-alt',
				'capability_type'     => array( 'jn_booking', 'jn_bookings' ),
				'capabilities'        => $capabilities,
				'map_meta_cap'        => true,
				'supports'            => array( 'title' ),
			)
		);
	}

	/**
	 * Register the 5 custom booking post statuses.
	 *
	 * @return void
	 */
	public static function register_post_statuses(): void {
		$statuses = self::get_statuses();

		foreach ( $statuses as $slug => $label ) {
			register_post_status(
				$slug,
				array(
					'label'                     => $label,
					'public'                    => false,
					'internal'                  => false,
					'private'                   => true,
					'exclude_from_search'       => true,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: count of bookings in this status */
					'label_count'               => _n_noop(
						$label . ' <span class="count">(%s)</span>', // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle
						$label . ' <span class="count">(%s)</span>', // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralPlural
						'jn-core'
					),
				)
			);
		}
	}

	/**
	 * Explicitly remove `jn_booking` from WordPress core XML sitemaps if ever present.
	 *
	 * Confirmed filter in `wp-includes/sitemaps/providers/class-wp-sitemaps-posts.php`.
	 *
	 * @param array<string, \WP_Post_Type> $post_types Registered public post types.
	 * @return array<string, \WP_Post_Type>
	 */
	public static function exclude_from_sitemaps( array $post_types ): array {
		unset( $post_types[ self::POST_TYPE ] );
		return $post_types;
	}

	/**
	 * Guard against any direct front-end query attempting to load a `jn_booking` post.
	 *
	 * @return void
	 */
	public static function guard_direct_frontend_access(): void {
		if ( is_admin() ) {
			return;
		}

		if ( is_singular( self::POST_TYPE ) || get_query_var( 'post_type' ) === self::POST_TYPE ) {
			global $wp_query;
			if ( $wp_query instanceof \WP_Query ) {
				$wp_query->set_404();
			}
			status_header( 404 );
			nocache_headers();
		}
	}
}
