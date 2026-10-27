<?php
/**
 * Vietnamese Admin UI for the Jannie Nguyen Booking Engine (`jn_booking`).
 *
 * Provides:
 * - Menu "Lịch hẹn" with live pending-count badge.
 * - Custom 10-column list table, filters (status, package, format, date range), search (name/phone/email/code),
 *   row actions, and bulk actions.
 * - Light HTML/CSS Agenda view ("Lịch làm việc theo ngày") of confirmed bookings per day.
 * - Comprehensive Edit screen (customer card, read-only original request, scheduling fields with overlap warning,
 *   linked WooCommerce HPOS order, Mentorship 12-session manager, timeline, staff notes).
 * - Settings screen ("Cài đặt").
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Booking_Admin
 */
class JN_Booking_Admin {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_submenus_and_badge' ), 20 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ), 20 );
		add_action( 'save_post_' . JN_Booking_CPT::POST_TYPE, array( __CLASS__, 'handle_save_booking_metabox' ), 20, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notices' ) );
		add_action( 'admin_head', array( __CLASS__, 'print_admin_styles' ) );

		// List table columns, filters, search, row actions, bulk actions.
		add_filter( 'manage_' . JN_Booking_CPT::POST_TYPE . '_posts_columns', array( __CLASS__, 'filter_columns' ), 20, 1 );
		add_action( 'manage_' . JN_Booking_CPT::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_column' ), 20, 2 );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'render_list_table_filters' ), 20, 1 );
		add_action( 'pre_get_posts', array( __CLASS__, 'apply_list_table_filters_and_search' ), 20, 1 );
		add_filter( 'post_row_actions', array( __CLASS__, 'filter_row_actions' ), 20, 2 );
		add_filter( 'bulk_actions-edit-' . JN_Booking_CPT::POST_TYPE, array( __CLASS__, 'register_bulk_actions' ), 20, 1 );
		add_filter( 'handle_bulk_actions-edit-' . JN_Booking_CPT::POST_TYPE, array( __CLASS__, 'handle_bulk_actions' ), 20, 3 );

		// Admin-post handlers for row actions, Mentorship session creation, and Settings save.
		add_action( 'admin_post_jn_booking_row_action', array( __CLASS__, 'handle_row_action_request' ) );
		add_action( 'admin_post_jn_create_mentorship_session', array( __CLASS__, 'handle_create_mentorship_session' ) );
		add_action( 'admin_post_jn_save_booking_settings', array( __CLASS__, 'handle_save_settings' ) );
	}

	/**
	 * Add pending-count badge to "Lịch hẹn" menu and register Agenda & Settings submenus.
	 *
	 * @return void
	 */
	public static function register_submenus_and_badge(): void {
		global $menu;

		$counts        = wp_count_posts( JN_Booking_CPT::POST_TYPE );
		$pending_count = isset( $counts->{JN_Booking_CPT::STATUS_PENDING} ) ? (int) $counts->{JN_Booking_CPT::STATUS_PENDING} : 0;

		if ( $pending_count > 0 && is_array( $menu ) ) {
			foreach ( $menu as $idx => $item ) {
				if ( isset( $item[2] ) && 'edit.php?post_type=' . JN_Booking_CPT::POST_TYPE === $item[2] ) {
					// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					$menu[ $idx ][0] = sprintf(
						'Lịch hẹn <span class="awaiting-mod count-%1$d"><span class="pending-count">%2$s</span></span>',
						$pending_count,
						number_format_i18n( $pending_count )
					);
					break;
				}
			}
		}

		add_submenu_page(
			'edit.php?post_type=' . JN_Booking_CPT::POST_TYPE,
			'Lịch làm việc theo ngày — Jannie Nguyễn',
			'Lịch theo ngày',
			JN_Booking_Roles::CAP_MANAGE,
			'jn-booking-agenda',
			array( __CLASS__, 'render_agenda_page' )
		);

		add_submenu_page(
			'edit.php?post_type=' . JN_Booking_CPT::POST_TYPE,
			'Cài đặt Lịch hẹn — Jannie Nguyễn',
			'Cài đặt',
			JN_Booking_Roles::CAP_MANAGE,
			'jn-booking-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Define the 10 Vietnamese columns on the `jn_booking` list table.
	 *
	 * @param array<string, string> $columns Default columns.
	 * @return array<string, string>
	 */
	public static function filter_columns( array $columns ): array {
		$cb = isset( $columns['cb'] ) ? array( 'cb' => $columns['cb'] ) : array();
		return array_merge(
			$cb,
			array(
				'jn_code'               => 'Mã',
				'jn_customer'           => 'Khách hàng',
				'jn_phone'              => 'SĐT / Zalo',
				'jn_package'            => 'Gói',
				'jn_format'             => 'Hình thức',
				'jn_preferred_window'   => 'Khung giờ mong muốn',
				'jn_confirmed_schedule' => 'Lịch xác nhận',
				'jn_status'             => 'Trạng thái',
				'jn_payment'            => 'Thanh toán',
				'jn_submitted_date'     => 'Ngày gửi',
			)
		);
	}

	/**
	 * Render custom column content on the `jn_booking` list table.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Booking post ID.
	 * @return void
	 */
	public static function render_column( string $column, int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		switch ( $column ) {
			case 'jn_code':
				$code        = (string) get_post_meta( $post_id, '_jn_booking_code', true );
				$edit_url    = get_edit_post_link( $post_id );
				$is_waitlist = 'yes' === (string) get_post_meta( $post_id, '_jn_is_waitlist', true );
				$sess_num    = (int) get_post_meta( $post_id, '_jn_session_number', true );
				$total_sess  = (int) get_post_meta( $post_id, '_jn_total_sessions', true );
				echo '<strong><a class="row-title" href="' . esc_url( (string) $edit_url ) . '">' . esc_html( '' !== $code ? $code : '#' . $post_id ) . '</a></strong>';
				if ( $total_sess > 1 && $sess_num > 0 ) {
					echo ' <span class="jn-badge jn-badge--session">Buổi ' . esc_html( $sess_num . '/' . $total_sess ) . '</span>';
				}
				if ( $is_waitlist ) {
					echo ' <span class="jn-badge jn-badge--waitlist">Waitlist</span>';
				}
				break;

			case 'jn_customer':
				$name    = (string) get_post_meta( $post_id, '_jn_customer_name', true );
				$email   = (string) get_post_meta( $post_id, '_jn_customer_email', true );
				$user_id = (int) get_post_meta( $post_id, '_jn_customer_user_id', true );
				echo '<strong>' . esc_html( $name ) . '</strong>';
				if ( $user_id > 0 ) {
					echo ' <a href="' . esc_url( get_edit_user_link( $user_id ) ) . '" class="jn-sub-meta">(TK #' . esc_html( (string) $user_id ) . ')</a>';
				} else {
					echo ' <span class="jn-sub-meta">(Khách vãng lai)</span>';
				}
				if ( '' !== $email ) {
					echo '<br><span class="jn-sub-meta">' . esc_html( $email ) . '</span>';
				}
				break;

			case 'jn_phone':
				$phone = (string) get_post_meta( $post_id, '_jn_customer_phone', true );
				if ( '' !== $phone ) {
					echo '<a href="tel:' . esc_attr( $phone ) . '"><code>' . esc_html( $phone ) . '</code></a>';
				} else {
					echo '—';
				}
				break;

			case 'jn_package':
				$pkg_title = (string) get_post_meta( $post_id, '_jn_package_title', true );
				echo esc_html( '' !== $pkg_title ? $pkg_title : '—' );
				break;

			case 'jn_format':
				$format = (string) get_post_meta( $post_id, '_jn_format', true );
				echo 'in-person' === $format
					? '<span class="jn-badge jn-badge--inperson">Tại Academy</span>'
					: '<span class="jn-badge jn-badge--online">Online (Zoom)</span>';
				break;

			case 'jn_preferred_window':
				$win_label = (string) get_post_meta( $post_id, '_jn_preferred_window_label', true );
				echo esc_html( '' !== $win_label ? $win_label : '—' );
				break;

			case 'jn_confirmed_schedule':
				$confirmed_start = (string) get_post_meta( $post_id, '_jn_confirmed_start', true );
				$duration        = (int) get_post_meta( $post_id, '_jn_duration_minutes', true );
				if ( '' !== $confirmed_start ) {
					try {
						$tz = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
						$dt = new \DateTimeImmutable( $confirmed_start, $tz );
						echo '<strong>' . esc_html( $dt->format( 'd/m/Y H:i' ) ) . '</strong>';
						echo '<br><span class="jn-sub-meta">' . esc_html( $duration . ' phút (GMT+7)' ) . '</span>';
					} catch ( \Exception $e ) {
						echo esc_html( $confirmed_start );
					}
				} else {
					echo '<span class="jn-sub-meta">Chưa chốt giờ</span>';
				}
				break;

			case 'jn_status':
				$status = $post->post_status;
				$label  = JN_Booking_CPT::get_status_label( $status );
				echo '<span class="jn-status-pill jn-status-pill--' . esc_attr( $status ) . '">' . esc_html( $label ) . '</span>';
				break;

			case 'jn_payment':
				$pstate = JN_Booking_Service::get_derived_payment_state( $post_id );
				echo '<span class="jn-pay-pill jn-pay-pill--' . esc_attr( (string) $pstate['state'] ) . '">' . esc_html( (string) $pstate['label'] ) . '</span>';
				if ( ! empty( $pstate['order_id'] ) ) {
					$order_edit = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . (int) $pstate['order_id'] );
					echo '<br><a href="' . esc_url( $order_edit ) . '" class="jn-sub-meta">Đơn #' . esc_html( (string) $pstate['order_id'] ) . '</a>';
				}
				break;

			case 'jn_submitted_date':
				echo esc_html( get_the_date( 'd/m/Y H:i', $post ) );
				break;
		}
	}

	/**
	 * Render status, package, format, and date-range filter controls above the list table.
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public static function render_list_table_filters( string $post_type ): void {
		if ( JN_Booking_CPT::POST_TYPE !== $post_type ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$cur_status  = isset( $_GET['filter_jn_status'] ) ? sanitize_key( wp_unslash( $_GET['filter_jn_status'] ) ) : '';
		$cur_package = isset( $_GET['filter_jn_package'] ) ? sanitize_key( wp_unslash( $_GET['filter_jn_package'] ) ) : '';
		$cur_format  = isset( $_GET['filter_jn_format'] ) ? sanitize_key( wp_unslash( $_GET['filter_jn_format'] ) ) : '';
		$date_from   = isset( $_GET['filter_jn_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_jn_date_from'] ) ) : '';
		$date_to     = isset( $_GET['filter_jn_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_jn_date_to'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$statuses = JN_Booking_CPT::get_statuses();
		$packages = JN_Coaching_Packages::get_bookable_packages_for_form();
		$formats  = (array) JN_Booking_Settings::get( 'formats', array() );
		?>
		<select name="filter_jn_status" aria-label="Lọc theo trạng thái">
			<option value="">Tất cả trạng thái</option>
			<?php foreach ( $statuses as $st_slug => $st_label ) : ?>
				<option value="<?php echo esc_attr( $st_slug ); ?>" <?php selected( $cur_status, $st_slug ); ?>><?php echo esc_html( $st_label ); ?></option>
			<?php endforeach; ?>
		</select>

		<select name="filter_jn_package" aria-label="Lọc theo gói cố vấn">
			<option value="">Tất cả gói cố vấn</option>
			<?php foreach ( $packages as $pkg_key => $pkg_info ) : ?>
				<option value="<?php echo esc_attr( $pkg_key ); ?>" <?php selected( $cur_package, $pkg_key ); ?>><?php echo esc_html( $pkg_info['value'] ); ?></option>
			<?php endforeach; ?>
		</select>

		<select name="filter_jn_format" aria-label="Lọc theo hình thức gặp">
			<option value="">Tất cả hình thức</option>
			<?php foreach ( $formats as $fmt_key => $fmt_label ) : ?>
				<option value="<?php echo esc_attr( $fmt_key ); ?>" <?php selected( $cur_format, $fmt_key ); ?>><?php echo esc_html( $fmt_label ); ?></option>
			<?php endforeach; ?>
		</select>

		<input type="date" name="filter_jn_date_from" value="<?php echo esc_attr( $date_from ); ?>" placeholder="Từ ngày" title="Từ ngày gửi" />
		<input type="date" name="filter_jn_date_to" value="<?php echo esc_attr( $date_to ); ?>" placeholder="Đến ngày" title="Đến ngày gửi" />
		<?php
	}

	/**
	 * Apply custom filters and name/phone/email/code search to the admin `jn_booking` query.
	 *
	 * @param \WP_Query $query Current WP_Query instance.
	 * @return void
	 */
	public static function apply_list_table_filters_and_search( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || JN_Booking_CPT::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		// Default to showing all 5 custom booking statuses when no specific post_status is requested.
		$current_post_status = $query->get( 'post_status' );
		if ( empty( $current_post_status ) || 'all' === $current_post_status ) {
			$query->set( 'post_status', array_keys( JN_Booking_CPT::get_statuses() ) );
		}

		$meta_query = (array) $query->get( 'meta_query' );
		$meta_query = array_values( array_filter( $meta_query ) );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$filter_status  = isset( $_GET['filter_jn_status'] ) ? sanitize_key( wp_unslash( $_GET['filter_jn_status'] ) ) : '';
		$filter_package = isset( $_GET['filter_jn_package'] ) ? sanitize_key( wp_unslash( $_GET['filter_jn_package'] ) ) : '';
		$filter_format  = isset( $_GET['filter_jn_format'] ) ? sanitize_key( wp_unslash( $_GET['filter_jn_format'] ) ) : '';
		$date_from      = isset( $_GET['filter_jn_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_jn_date_from'] ) ) : '';
		$date_to        = isset( $_GET['filter_jn_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_jn_date_to'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( '' !== $filter_status && isset( JN_Booking_CPT::get_statuses()[ $filter_status ] ) ) {
			$query->set( 'post_status', $filter_status );
		}

		if ( '' !== $filter_package ) {
			$meta_query[] = array(
				'key'   => '_jn_package_key',
				'value' => $filter_package,
			);
		}

		if ( '' !== $filter_format ) {
			$meta_query[] = array(
				'key'   => '_jn_format',
				'value' => $filter_format,
			);
		}

		if ( '' !== $date_from || '' !== $date_to ) {
			$dq = array( 'inclusive' => true );
			if ( '' !== $date_from ) {
				$dq['after'] = $date_from . ' 00:00:00';
			}
			if ( '' !== $date_to ) {
				$dq['before'] = $date_to . ' 23:59:59';
			}
			$query->set( 'date_query', array( $dq ) );
		}

		// Search across customer name, phone (+84 and raw), email, and booking code.
		$search_term = trim( (string) $query->get( 's' ) );
		if ( '' !== $search_term ) {
			$query->set( 's', '' );
			$norm_phone = JN_Customer_Service::normalize_vn_phone( $search_term );
			$search_or  = array(
				'relation' => 'OR',
				array(
					'key'     => '_jn_booking_code',
					'value'   => $search_term,
					'compare' => 'LIKE',
				),
				array(
					'key'     => '_jn_customer_name',
					'value'   => $search_term,
					'compare' => 'LIKE',
				),
				array(
					'key'     => '_jn_customer_email',
					'value'   => $search_term,
					'compare' => 'LIKE',
				),
				array(
					'key'     => '_jn_customer_phone',
					'value'   => $search_term,
					'compare' => 'LIKE',
				),
				array(
					'key'     => '_jn_customer_phone_raw',
					'value'   => $search_term,
					'compare' => 'LIKE',
				),
			);

			if ( '' !== $norm_phone ) {
				$search_or[] = array(
					'key'   => '_jn_customer_phone',
					'value' => $norm_phone,
				);
			}

			$meta_query[] = $search_or;
		}

		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}
	}

	/**
	 * Customize row actions on the `jn_booking` list table.
	 *
	 * @param array<string, string> $actions Existing row actions.
	 * @param \WP_Post              $post    Post instance.
	 * @return array<string, string>
	 */
	public static function filter_row_actions( array $actions, \WP_Post $post ): array {
		if ( JN_Booking_CPT::POST_TYPE !== $post->post_type || ! JN_Booking_Roles::user_can_manage_bookings() ) {
			return $actions;
		}

		$custom = array(
			'edit' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( (string) get_edit_post_link( $post->ID ) ),
				'Chi tiết / Xếp lịch'
			),
		);

		$status = $post->post_status;

		if ( JN_Booking_CPT::STATUS_CONFIRMED === $status ) {
			$complete_url          = wp_nonce_url(
				admin_url( 'admin-post.php?action=jn_booking_row_action&booking_id=' . $post->ID . '&to_status=' . JN_Booking_CPT::STATUS_COMPLETED ),
				'jn_booking_row_' . $post->ID
			);
			$noshow_url            = wp_nonce_url(
				admin_url( 'admin-post.php?action=jn_booking_row_action&booking_id=' . $post->ID . '&to_status=' . JN_Booking_CPT::STATUS_NO_SHOW ),
				'jn_booking_row_' . $post->ID
			);
			$custom['jn_complete'] = '<a href="' . esc_url( $complete_url ) . '">Hoàn thành</a>';
			$custom['jn_noshow']   = '<a href="' . esc_url( $noshow_url ) . '">Vắng mặt</a>';
		}

		if ( ! JN_Booking_CPT::is_final_status( $status ) ) {
			$cancel_url          = wp_nonce_url(
				admin_url( 'admin-post.php?action=jn_booking_row_action&booking_id=' . $post->ID . '&to_status=' . JN_Booking_CPT::STATUS_CANCELLED ),
				'jn_booking_row_' . $post->ID
			);
			$custom['jn_cancel'] = '<a href="' . esc_url( $cancel_url ) . '" class="submitdelete">Hủy lịch</a>';
		}

		return $custom;
	}

	/**
	 * Handle single-row status action links from the list table.
	 *
	 * @return void
	 */
	public static function handle_row_action_request(): void {
		if ( ! JN_Booking_Roles::user_can_manage_bookings() ) {
			wp_die( 'Bạn không có quyền thực hiện thao tác này.', 'Từ chối truy cập', array( 'response' => 403 ) );
		}

		$booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;
		$to_status  = isset( $_GET['to_status'] ) ? sanitize_key( wp_unslash( $_GET['to_status'] ) ) : '';

		check_admin_referer( 'jn_booking_row_' . $booking_id );

		$result = JN_Booking_Service::transition_status(
			$booking_id,
			$to_status,
			array( 'note' => 'Cập nhật nhanh từ danh sách Lịch hẹn.' )
		);

		$redirect = admin_url( 'edit.php?post_type=' . JN_Booking_CPT::POST_TYPE );
		if ( is_wp_error( $result ) ) {
			$redirect = add_query_arg( 'jn_admin_err', rawurlencode( $result->get_error_message() ), $redirect );
		} else {
			$redirect = add_query_arg( 'jn_admin_msg', 'updated', $redirect );
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Register bulk actions for the `jn_booking` list table.
	 *
	 * @param array<string, string> $bulk_actions Default bulk actions.
	 * @return array<string, string>
	 */
	public static function register_bulk_actions( array $bulk_actions ): array {
		unset( $bulk_actions['edit'] );
		$bulk_actions['jn_bulk_complete'] = 'Đánh dấu Hoàn thành';
		$bulk_actions['jn_bulk_no_show']  = 'Đánh dấu Vắng mặt';
		$bulk_actions['jn_bulk_cancel']   = 'Chuyển sang Đã hủy';
		return $bulk_actions;
	}

	/**
	 * Handle bulk actions on the `jn_booking` list table.
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $doaction    Selected bulk action.
	 * @param int[]  $post_ids    Selected booking IDs.
	 * @return string
	 */
	public static function handle_bulk_actions( string $redirect_to, string $doaction, array $post_ids ): string {
		if ( ! JN_Booking_Roles::user_can_manage_bookings() ) {
			return $redirect_to;
		}

		$action_map = array(
			'jn_bulk_complete' => JN_Booking_CPT::STATUS_COMPLETED,
			'jn_bulk_no_show'  => JN_Booking_CPT::STATUS_NO_SHOW,
			'jn_bulk_cancel'   => JN_Booking_CPT::STATUS_CANCELLED,
		);

		if ( ! isset( $action_map[ $doaction ] ) ) {
			return $redirect_to;
		}

		$target_status = $action_map[ $doaction ];
		$changed       = 0;

		foreach ( $post_ids as $pid ) {
			$res = JN_Booking_Service::transition_status(
				(int) $pid,
				$target_status,
				array( 'note' => 'Thao tác hàng loạt trong danh sách Lịch hẹn.' )
			);
			if ( ! is_wp_error( $res ) ) {
				++$changed;
			}
		}

		return add_query_arg( 'jn_bulk_updated', $changed, $redirect_to );
	}

	/**
	 * Register custom meta boxes for the `jn_booking` Edit screen.
	 *
	 * @return void
	 */
	public static function register_meta_boxes(): void {
		remove_meta_box( 'submitdiv', JN_Booking_CPT::POST_TYPE, 'side' );
		remove_meta_box( 'slugdiv', JN_Booking_CPT::POST_TYPE, 'normal' );

		add_meta_box(
			'jn-booking-schedule-box',
			'1. Xếp Lịch, Xác Nhận Khung Giờ & Chuyển Trạng Thái',
			array( __CLASS__, 'render_schedule_metabox' ),
			JN_Booking_CPT::POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'jn-booking-request-box',
			'2. Thông Tin Yêu Cầu Gốc Của Khách Hàng (Chỉ Đọc)',
			array( __CLASS__, 'render_original_request_metabox' ),
			JN_Booking_CPT::POST_TYPE,
			'normal',
			'default'
		);

		add_meta_box(
			'jn-booking-mentorship-box',
			'3. Lộ Trình Master Mentorship (1 Đơn Ghi Danh & Tối Đa 12 Buổi 1:1)',
			array( __CLASS__, 'render_mentorship_metabox' ),
			JN_Booking_CPT::POST_TYPE,
			'normal',
			'default'
		);

		add_meta_box(
			'jn-booking-timeline-box',
			'4. Nhật Ký Trạng Thái (Timeline) & Ghi Chú Nội Bộ Trợ Lý',
			array( __CLASS__, 'render_timeline_and_notes_metabox' ),
			JN_Booking_CPT::POST_TYPE,
			'normal',
			'default'
		);

		add_meta_box(
			'jn-booking-customer-card',
			'Hồ Sơ Khách Hàng',
			array( __CLASS__, 'render_customer_card_metabox' ),
			JN_Booking_CPT::POST_TYPE,
			'side',
			'high'
		);

		add_meta_box(
			'jn-booking-order-card',
			'Đơn Hàng WooCommerce Liên Kết',
			array( __CLASS__, 'render_order_card_metabox' ),
			JN_Booking_CPT::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * Render Metabox 1: Scheduling fields, overlap warning, and status transitions.
	 *
	 * @param \WP_Post $post Booking post.
	 * @return void
	 */
	public static function render_schedule_metabox( \WP_Post $post ): void {
		wp_nonce_field( 'jn_save_booking_' . $post->ID, 'jn_booking_admin_nonce' );

		$booking_id      = (int) $post->ID;
		$current_status  = $post->post_status;
		if ( 'auto-draft' === $current_status || 'draft' === $current_status ) {
			$current_status = JN_Booking_CPT::STATUS_PENDING;
		}

		$confirmed_start = (string) get_post_meta( $booking_id, '_jn_confirmed_start', true );
		$date_val        = '';
		$time_val        = '10:00';
		if ( '' !== $confirmed_start ) {
			try {
				$tz       = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
				$dt       = new \DateTimeImmutable( $confirmed_start, $tz );
				$date_val = $dt->format( 'Y-m-d' );
				$time_val = $dt->format( 'H:i' );
			} catch ( \Exception $e ) {
				$date_val = '';
			}
		}

		$duration_min = (int) get_post_meta( $booking_id, '_jn_duration_minutes', true );
		if ( $duration_min <= 0 ) {
			$duration_min = 60;
		}

		$format       = (string) get_post_meta( $booking_id, '_jn_format', true );
		if ( '' === $format ) {
			$format = 'online';
		}
		$formats      = (array) JN_Booking_Settings::get( 'formats', array() );
		$meeting_link = (string) get_post_meta( $booking_id, '_jn_meeting_link', true );
		$location     = (string) get_post_meta( $booking_id, '_jn_location', true );
		if ( '' === $location ) {
			$location = (string) JN_Booking_Settings::get( 'academy_address', 'TP. Hồ Chí Minh, Việt Nam' );
		}

		$agreed_price = (int) get_post_meta( $booking_id, '_jn_agreed_price', true );
		if ( $agreed_price <= 0 ) {
			$pkg_key      = (string) get_post_meta( $booking_id, '_jn_package_key', true );
			$default_fees = array(
				'30-phut'            => 1500000,
				'60-phut'            => 2500000,
				'mentorship-3-thang' => 25000000,
			);
			$agreed_price = $default_fees[ $pkg_key ] ?? 2500000;
		}

		$start_ts = (int) get_post_meta( $booking_id, '_jn_confirmed_start_ts', true );
		$end_ts   = (int) get_post_meta( $booking_id, '_jn_confirmed_end_ts', true );
		$overlaps = ( $start_ts > 0 && $end_ts > $start_ts )
			? JN_Booking_Service::find_overlapping_bookings( $start_ts, $end_ts, $booking_id )
			: array();

		$all_statuses = JN_Booking_CPT::get_statuses();
		?>
		<div class="jn-admin-panel">
			<div class="jn-admin-status-bar">
				<div>
					<strong>Trạng thái hiện tại:</strong>
					<span class="jn-status-pill jn-status-pill--<?php echo esc_attr( $current_status ); ?>">
						<?php echo esc_html( JN_Booking_CPT::get_status_label( $current_status ) ); ?>
					</span>
				</div>
				<div>
					<label for="jn-target-status"><strong>Chuyển trạng thái sang:</strong></label>
					<select id="jn-target-status" name="jn_target_status">
						<option value="<?php echo esc_attr( $current_status ); ?>">— Giữ nguyên (<?php echo esc_html( JN_Booking_CPT::get_status_label( $current_status ) ); ?>) —</option>
						<?php foreach ( $all_statuses as $st_slug => $st_label ) : ?>
							<?php if ( JN_Booking_Service::is_transition_allowed( $current_status, $st_slug ) ) : ?>
								<option value="<?php echo esc_attr( $st_slug ); ?>">➔ <?php echo esc_html( $st_label ); ?></option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<?php if ( ! empty( $overlaps ) ) : ?>
				<div class="jn-overlap-alert" id="jn-overlap-warning">
					<strong>⚠️ Cảnh báo trùng khung giờ:</strong> Khung giờ này đang giao với lịch hẹn đã xác nhận khác:
					<ul>
						<?php foreach ( $overlaps as $ov ) : ?>
							<li>
								<strong><?php echo esc_html( $ov['booking_code'] ); ?></strong> —
								<?php echo esc_html( $ov['customer_name'] ); ?>
								(<?php echo esc_html( $ov['confirmed_start'] . ' • ' . $ov['duration_minutes'] . ' phút' ); ?>)
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<div class="jn-admin-grid-2">
				<div class="jn-admin-field">
					<label for="jn-confirmed-date"><strong>Ngày hẹn chính thức (Asia/Ho_Chi_Minh) *</strong></label>
					<input type="date" id="jn-confirmed-date" name="jn_confirmed_date" value="<?php echo esc_attr( $date_val ); ?>" class="widefat" />
				</div>
				<div class="jn-admin-field">
					<label for="jn-confirmed-time"><strong>Giờ bắt đầu (24h — GMT+7) *</strong></label>
					<input type="time" id="jn-confirmed-time" name="jn_confirmed_time" value="<?php echo esc_attr( $time_val ); ?>" class="widefat" />
				</div>
			</div>

			<div class="jn-admin-grid-2">
				<div class="jn-admin-field">
					<label for="jn-duration-minutes"><strong>Thời lượng phiên (Phút) *</strong></label>
					<input type="number" id="jn-duration-minutes" name="jn_duration_minutes" value="<?php echo esc_attr( (string) $duration_min ); ?>" min="15" max="480" step="5" class="widefat" />
				</div>
				<div class="jn-admin-field">
					<label for="jn-format"><strong>Hình thức làm việc 1:1 *</strong></label>
					<select id="jn-format" name="jn_format" class="widefat">
						<?php foreach ( $formats as $fmt_key => $fmt_label ) : ?>
							<option value="<?php echo esc_attr( $fmt_key ); ?>" <?php selected( $format, $fmt_key ); ?>><?php echo esc_html( $fmt_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="jn-admin-grid-2">
				<div class="jn-admin-field">
					<label for="jn-meeting-link"><strong>Liên kết phòng họp trực tuyến (Zoom/Meet — Bắt buộc nếu Online)</strong></label>
					<input type="url" id="jn-meeting-link" name="jn_meeting_link" value="<?php echo esc_attr( $meeting_link ); ?>" placeholder="https://zoom.us/j/..." class="widefat" />
				</div>
				<div class="jn-admin-field">
					<label for="jn-location"><strong>Địa điểm gặp trực tiếp (Nếu gặp tại Academy)</strong></label>
					<input type="text" id="jn-location" name="jn_location" value="<?php echo esc_attr( $location ); ?>" class="widefat" />
				</div>
			</div>

			<div class="jn-admin-grid-2">
				<div class="jn-admin-field">
					<label for="jn-agreed-price"><strong>Mức phí gói tạo đơn thanh toán (VND)</strong></label>
					<input type="number" id="jn-agreed-price" name="jn_agreed_price" value="<?php echo esc_attr( (string) $agreed_price ); ?>" min="0" step="50000" class="widefat" />
					<p class="description">Sử dụng khi khởi tạo đơn hàng <code>pending</code> lúc chuyển sang <strong>Đã xác nhận lịch</strong>.</p>
				</div>
				<div class="jn-admin-field">
					<label for="jn-transition-note"><strong>Ghi chú chuyển trạng thái (Lưu vào Timeline)</strong></label>
					<input type="text" id="jn-transition-note" name="jn_transition_note" value="" placeholder="Ví dụ: Đã gọi xác nhận qua Zalo với khách hàng..." class="widefat" />
					<label style="display:block;margin-top:8px;">
						<input type="checkbox" name="jn_allow_overlap" value="1" />
						Cho phép lưu ngay cả khi trùng khung giờ với lịch hẹn khác
					</label>
				</div>
			</div>

			<div class="jn-admin-submit-row">
				<button type="submit" name="jn_save_booking_btn" value="1" class="button button-primary button-large">
					Lưu &amp; Cập Nhật Lịch Hẹn
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Metabox 2: Read-only original intake request, consent metadata, and source attribution.
	 *
	 * @param \WP_Post $post Booking post.
	 * @return void
	 */
	public static function render_original_request_metabox( \WP_Post $post ): void {
		$booking_id    = (int) $post->ID;
		$pkg_opt_label = (string) get_post_meta( $booking_id, '_jn_package_option_label', true );
		$format_label  = (string) get_post_meta( $booking_id, '_jn_format_label', true );
		$window_label  = (string) get_post_meta( $booking_id, '_jn_preferred_window_label', true );
		$goals         = (string) get_post_meta( $booking_id, '_jn_goals', true );
		$consent_ts    = (string) get_post_meta( $booking_id, '_jn_consent_timestamp', true );
		$consent_url   = (string) get_post_meta( $booking_id, '_jn_consent_policy_url', true );
		$consent_ver   = (string) get_post_meta( $booking_id, '_jn_consent_policy_version', true );
		$source        = get_post_meta( $booking_id, '_jn_source', true );
		$landing_url   = is_array( $source ) && isset( $source['landing_url'] ) ? (string) $source['landing_url'] : '';
		$referrer      = is_array( $source ) && isset( $source['referrer'] ) ? (string) $source['referrer'] : '';
		$utm           = is_array( $source ) && isset( $source['utm'] ) && is_array( $source['utm'] ) ? $source['utm'] : array();
		?>
		<table class="widefat striped jn-readonly-table">
			<tbody>
				<tr>
					<th style="width:28%;">Gói cố vấn đăng ký (Nguyên văn)</th>
					<td><strong><?php echo esc_html( '' !== $pkg_opt_label ? $pkg_opt_label : '—' ); ?></strong></td>
				</tr>
				<tr>
					<th>Hình thức gặp mong muốn</th>
					<td><?php echo esc_html( '' !== $format_label ? $format_label : '—' ); ?></td>
				</tr>
				<tr>
					<th>Khung giờ thuận tiện (Nguyên văn)</th>
					<td><?php echo esc_html( '' !== $window_label ? $window_label : '—' ); ?></td>
				</tr>
				<tr>
					<th>Mục tiêu / Thách thức chia sẻ</th>
					<td><?php echo '' !== $goals ? nl2br( esc_html( $goals ) ) : '<em>(Không có ghi chú)</em>'; ?></td>
				</tr>
				<tr>
					<th>Đồng ý Chính sách bảo mật</th>
					<td>
						Thời điểm: <code><?php echo esc_html( '' !== $consent_ts ? $consent_ts : '—' ); ?></code>
						• Phiên bản: <code><?php echo esc_html( '' !== $consent_ver ? $consent_ver : '1.0' ); ?></code>
						<?php if ( '' !== $consent_url ) : ?>
							• <a href="<?php echo esc_url( $consent_url ); ?>" target="_blank" rel="noopener">Trang chính sách</a>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th>Nguồn truy cập (Landing / UTM / Referrer)</th>
					<td>
						Trang gửi: <code><?php echo esc_html( '' !== $landing_url ? $landing_url : '—' ); ?></code><br>
						Referrer: <code><?php echo esc_html( '' !== $referrer ? $referrer : '(Trực tiếp)' ); ?></code>
						<?php if ( ! empty( $utm ) ) : ?>
							<br>UTM: <code><?php echo esc_html( wp_json_encode( $utm ) ); ?></code>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render Metabox 3: Master Mentorship 12-Session Enrolment Panel.
	 *
	 * @param \WP_Post $post Booking post.
	 * @return void
	 */
	public static function render_mentorship_metabox( \WP_Post $post ): void {
		$booking_id = (int) $post->ID;
		$pkg_key    = (string) get_post_meta( $booking_id, '_jn_package_key', true );
		$total_sess = (int) get_post_meta( $booking_id, '_jn_total_sessions', true );

		if ( 'mentorship-3-thang' !== $pkg_key && $total_sess <= 1 && 0 === (int) $post->post_parent ) {
			echo '<p class="description">Lịch hẹn này thuộc gói đơn lẻ (1 buổi). Chức năng quản lý chuỗi 12 buổi chỉ áp dụng cho <strong>Gói Master Mentorship (3 Tháng)</strong>.</p>';
			return;
		}

		$root_id  = $post->post_parent > 0 ? (int) $post->post_parent : $booking_id;
		$sessions = JN_Booking_Service::get_mentorship_sessions( $root_id );
		$add_url  = wp_nonce_url(
			admin_url( 'admin-post.php?action=jn_create_mentorship_session&enrolment_id=' . $root_id ),
			'jn_mentorship_add_' . $root_id
		);
		?>
		<p>
			Gói <strong>Master Mentorship (3 Tháng)</strong> sử dụng <strong>1 đơn hàng ghi danh duy nhất</strong> và cho phép xếp lịch tối đa <strong>12 buổi Coaching 1:1</strong> liên kết cùng đơn hàng đó.
		</p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>Buổi</th>
					<th>Mã buổi hẹn</th>
					<th>Thời gian xác nhận</th>
					<th>Hình thức</th>
					<th>Trạng thái</th>
					<th>Thao tác</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $sessions as $idx => $sid ) : ?>
					<?php
					$s_code  = (string) get_post_meta( $sid, '_jn_booking_code', true );
					$s_start = (string) get_post_meta( $sid, '_jn_confirmed_start', true );
					$s_fmt   = (string) get_post_meta( $sid, '_jn_format_label', true );
					$s_stat  = (string) get_post_status( $sid );
					$s_num   = (int) get_post_meta( $sid, '_jn_session_number', true );
					if ( $s_num <= 0 ) {
						$s_num = $idx + 1;
					}
					?>
					<tr <?php echo $sid === $booking_id ? 'style="background:#FFF8EB;"' : ''; ?>>
						<td><strong>Buổi <?php echo esc_html( $s_num . '/12' ); ?></strong></td>
						<td><code><?php echo esc_html( $s_code ); ?></code></td>
						<td><?php echo esc_html( '' !== $s_start ? $s_start : 'Chưa chốt giờ' ); ?></td>
						<td><?php echo esc_html( $s_fmt ); ?></td>
						<td><span class="jn-status-pill jn-status-pill--<?php echo esc_attr( $s_stat ); ?>"><?php echo esc_html( JN_Booking_CPT::get_status_label( $s_stat ) ); ?></span></td>
						<td>
							<?php if ( $sid === $booking_id ) : ?>
								<em>(Đang mở)</em>
							<?php else : ?>
								<a href="<?php echo esc_url( (string) get_edit_post_link( $sid ) ); ?>" class="button button-small">Mở buổi này</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ( count( $sessions ) < JN_Booking_Service::MENTORSHIP_MAX_SESSIONS ) : ?>
			<p style="margin-top:12px;">
				<a href="<?php echo esc_url( $add_url ); ?>" class="button button-secondary">
					+ Thêm Buổi Học Tiếp Theo (Buổi <?php echo esc_html( ( count( $sessions ) + 1 ) . '/12' ); ?>)
				</a>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render Metabox 4: Status Timeline and Staff Internal Notes.
	 *
	 * @param \WP_Post $post Booking post.
	 * @return void
	 */
	public static function render_timeline_and_notes_metabox( \WP_Post $post ): void {
		$booking_id  = (int) $post->ID;
		$staff_notes = (string) get_post_meta( $booking_id, '_jn_staff_notes', true );
		$timeline    = get_post_meta( $booking_id, '_jn_status_timeline', true );
		if ( ! is_array( $timeline ) ) {
			$timeline = array();
		}
		?>
		<div class="jn-admin-field" style="margin-bottom:20px;">
			<label for="jn-staff-notes"><strong>Ghi chú nội bộ của trợ lý (Chỉ hiển thị trong Admin):</strong></label>
			<textarea id="jn-staff-notes" name="jn_staff_notes" rows="4" class="widefat" placeholder="Ghi chú kết quả cuộc gọi, mong muốn đặc biệt của học viên..."><?php echo esc_textarea( $staff_notes ); ?></textarea>
		</div>

		<h4>Nhật ký chuyển trạng thái (Status Timeline)</h4>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>Thời điểm (Asia/Ho_Chi_Minh)</th>
					<th>Người thực hiện</th>
					<th>Chuyển trạng thái</th>
					<th>Ghi chú</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $timeline ) ) : ?>
					<tr><td colspan="4">Chưa có lịch sử trạng thái.</td></tr>
				<?php else : ?>
					<?php foreach ( array_reverse( $timeline ) as $entry ) : ?>
						<?php
						$from_st = isset( $entry['from'] ) ? (string) $entry['from'] : '';
						$to_st   = isset( $entry['to'] ) ? (string) $entry['to'] : '';
						$from_lb = 'new' === $from_st ? 'Khởi tạo mới' : JN_Booking_CPT::get_status_label( $from_st );
						$to_lb   = JN_Booking_CPT::get_status_label( $to_st );
						?>
						<tr>
							<td><code><?php echo esc_html( (string) ( $entry['timestamp'] ?? '' ) ); ?></code></td>
							<td><?php echo esc_html( (string) ( $entry['actor_name'] ?? '' ) ); ?></td>
							<td><strong><?php echo esc_html( $from_lb . ' ➔ ' . $to_lb ); ?></strong></td>
							<td><?php echo wp_kses_post( (string) ( $entry['note'] ?? '' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render Side Metabox: Customer Card.
	 *
	 * @param \WP_Post $post Booking post.
	 * @return void
	 */
	public static function render_customer_card_metabox( \WP_Post $post ): void {
		$booking_id = (int) $post->ID;
		$name       = (string) get_post_meta( $booking_id, '_jn_customer_name', true );
		$phone      = (string) get_post_meta( $booking_id, '_jn_customer_phone', true );
		$phone_raw  = (string) get_post_meta( $booking_id, '_jn_customer_phone_raw', true );
		$email      = (string) get_post_meta( $booking_id, '_jn_customer_email', true );
		$user_id    = (int) get_post_meta( $booking_id, '_jn_customer_user_id', true );
		?>
		<div class="jn-side-card">
			<p><strong>Họ và tên:</strong><br><?php echo esc_html( '' !== $name ? $name : '—' ); ?></p>
			<p>
				<strong>SĐT / Zalo (Chuẩn hóa E.164):</strong><br>
				<code><?php echo esc_html( '' !== $phone ? $phone : '—' ); ?></code>
				<?php if ( '' !== $phone_raw && $phone_raw !== $phone ) : ?>
					<br><span class="description">Nhập gốc: <?php echo esc_html( $phone_raw ); ?></span>
				<?php endif; ?>
			</p>
			<p><strong>Email:</strong><br><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( '' !== $email ? $email : '—' ); ?></a></p>
			<p>
				<strong>Tài khoản khách hàng:</strong><br>
				<?php if ( $user_id > 0 ) : ?>
					<a href="<?php echo esc_url( get_edit_user_link( $user_id ) ); ?>" class="button button-small">Hồ sơ khách hàng #<?php echo esc_html( (string) $user_id ); ?></a>
				<?php else : ?>
					<span class="jn-badge">Khách vãng lai (Tự động gắn khi đăng ký)</span>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render Side Metabox: Linked WooCommerce Order Card.
	 *
	 * @param \WP_Post $post Booking post.
	 * @return void
	 */
	public static function render_order_card_metabox( \WP_Post $post ): void {
		$booking_id = (int) $post->ID;
		$pstate     = JN_Booking_Service::get_derived_payment_state( $booking_id );
		$order_id   = (int) $pstate['order_id'];
		$order      = ( $order_id > 0 && function_exists( 'wc_get_order' ) ) ? wc_get_order( $order_id ) : null;
		?>
		<div class="jn-side-card">
			<p>
				<strong>Trạng thái thanh toán:</strong><br>
				<span class="jn-pay-pill jn-pay-pill--<?php echo esc_attr( (string) $pstate['state'] ); ?>">
					<?php echo esc_html( (string) $pstate['label'] ); ?>
				</span>
			</p>

			<?php if ( ! empty( $pstate['refund_flagged'] ) ) : ?>
				<div class="jn-refund-alert">
					<strong>⚠️ CẦN HOÀN TIỀN THỦ CÔNG:</strong> Lịch hẹn đã hủy sau khi đơn hàng đã thanh toán. Tuyệt đối không hoàn tự động — vui lòng kiểm tra và hoàn tiền thủ công.
				</div>
			<?php endif; ?>

			<?php if ( $order instanceof \WC_Order ) : ?>
				<?php $order_edit_url = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id ); ?>
				<p>
					<strong>Mã đơn hàng HPOS:</strong>
					<a href="<?php echo esc_url( $order_edit_url ); ?>"><strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong></a>
					(<?php echo wp_kses_post( $order->get_formatted_order_total() ); ?>)
				</p>
				<?php if ( ! empty( $pstate['pay_url'] ) ) : ?>
					<p>
						<label for="jn-order-pay-url"><strong>Liên kết thanh toán (order-pay):</strong></label>
						<input type="text" id="jn-order-pay-url" readonly value="<?php echo esc_url( (string) $pstate['pay_url'] ); ?>" class="widefat" onclick="this.select();" />
					</p>
				<?php endif; ?>
				<p>
					<a href="<?php echo esc_url( $order_edit_url ); ?>" class="button button-secondary">Xem / Xử Lý Đơn Hàng #<?php echo esc_html( (string) $order_id ); ?></a>
				</p>
			<?php else : ?>
				<p class="description">Đơn hàng <code>pending</code> sẽ được tạo tự động ngay khi chuyển lịch hẹn sang <strong>Đã xác nhận lịch</strong>.</p>
				<p>
					<label for="jn-existing-order-id"><strong>Hoặc gắn ID đơn hàng có sẵn:</strong></label>
					<input type="number" id="jn-existing-order-id" name="jn_existing_order_id" value="" placeholder="Nhập Order ID..." class="widefat" />
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle saving the booking edit screen (scheduling fields, staff notes, and status transition).
	 *
	 * @param int      $post_id Booking post ID.
	 * @param \WP_Post $post    Booking post object.
	 * @return void
	 */
	public static function handle_save_booking_metabox( int $post_id, \WP_Post $post ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['jn_booking_admin_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['jn_booking_admin_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'jn_save_booking_' . $post_id ) ) {
			return;
		}

		if ( ! JN_Booking_Roles::user_can_manage_bookings() ) {
			return;
		}

		// Prevent infinite recursion when `wp_update_post` runs inside `transition_status`.
		remove_action( 'save_post_' . JN_Booking_CPT::POST_TYPE, array( __CLASS__, 'handle_save_booking_metabox' ), 20 );

		if ( isset( $_POST['jn_staff_notes'] ) ) {
			update_post_meta( $post_id, '_jn_staff_notes', sanitize_textarea_field( wp_unslash( $_POST['jn_staff_notes'] ) ) );
		}

		$date_val   = isset( $_POST['jn_confirmed_date'] ) ? sanitize_text_field( wp_unslash( $_POST['jn_confirmed_date'] ) ) : '';
		$time_val   = isset( $_POST['jn_confirmed_time'] ) ? sanitize_text_field( wp_unslash( $_POST['jn_confirmed_time'] ) ) : '10:00';
		$conf_start = '' !== $date_val ? ( $date_val . ' ' . ( '' !== $time_val ? $time_val : '10:00' ) . ':00' ) : '';

		$sched_args = array(
			'confirmed_start'   => $conf_start,
			'duration_minutes'  => isset( $_POST['jn_duration_minutes'] ) ? max( 15, absint( $_POST['jn_duration_minutes'] ) ) : 60,
			'format'            => isset( $_POST['jn_format'] ) ? sanitize_key( wp_unslash( $_POST['jn_format'] ) ) : 'online',
			'meeting_link'      => isset( $_POST['jn_meeting_link'] ) ? esc_url_raw( wp_unslash( $_POST['jn_meeting_link'] ) ) : '',
			'location'          => isset( $_POST['jn_location'] ) ? sanitize_text_field( wp_unslash( $_POST['jn_location'] ) ) : '',
			'agreed_price'      => isset( $_POST['jn_agreed_price'] ) ? max( 0, absint( $_POST['jn_agreed_price'] ) ) : 0,
			'existing_order_id' => isset( $_POST['jn_existing_order_id'] ) ? absint( $_POST['jn_existing_order_id'] ) : 0,
			'allow_overlap'     => ! empty( $_POST['jn_allow_overlap'] ),
			'note'              => isset( $_POST['jn_transition_note'] ) ? sanitize_text_field( wp_unslash( $_POST['jn_transition_note'] ) ) : '',
		);

		$current_status = (string) get_post_status( $post_id );
		$target_status  = isset( $_POST['jn_target_status'] ) ? sanitize_key( wp_unslash( $_POST['jn_target_status'] ) ) : $current_status;

		// Save scheduling metadata if provided even without status change.
		if ( '' !== $conf_start && $target_status === $current_status ) {
			JN_Booking_Service::apply_confirmation_schedule( $post_id, $sched_args );
		} else {
			update_post_meta( $post_id, '_jn_duration_minutes', $sched_args['duration_minutes'] );
			update_post_meta( $post_id, '_jn_format', $sched_args['format'] );
			update_post_meta( $post_id, '_jn_meeting_link', $sched_args['meeting_link'] );
			update_post_meta( $post_id, '_jn_location', $sched_args['location'] );
			if ( $sched_args['agreed_price'] > 0 ) {
				update_post_meta( $post_id, '_jn_agreed_price', $sched_args['agreed_price'] );
			}
		}

		// Execute state transition if a new target status was chosen.
		if ( $target_status !== $current_status ) {
			$res = JN_Booking_Service::transition_status( $post_id, $target_status, $sched_args );
			if ( is_wp_error( $res ) ) {
				set_transient( 'jn_booking_admin_err_' . get_current_user_id(), $res->get_error_message(), 60 );
			} else {
				set_transient(
					'jn_booking_admin_ok_' . get_current_user_id(),
					sprintf( 'Đã chuyển trạng thái lịch hẹn sang "%s".', JN_Booking_CPT::get_status_label( $target_status ) ),
					60
				);
			}
		}

		add_action( 'save_post_' . JN_Booking_CPT::POST_TYPE, array( __CLASS__, 'handle_save_booking_metabox' ), 20, 2 );
	}

	/**
	 * Handle creating a new child Mentorship session from the Edit screen.
	 *
	 * @return void
	 */
	public static function handle_create_mentorship_session(): void {
		if ( ! JN_Booking_Roles::user_can_manage_bookings() ) {
			wp_die( 'Bạn không có quyền thực hiện thao tác này.', 'Từ chối truy cập', array( 'response' => 403 ) );
		}

		$enrolment_id = isset( $_GET['enrolment_id'] ) ? absint( $_GET['enrolment_id'] ) : 0;
		check_admin_referer( 'jn_mentorship_add_' . $enrolment_id );

		$child_id = JN_Booking_Service::create_mentorship_session( $enrolment_id );
		if ( is_wp_error( $child_id ) ) {
			set_transient( 'jn_booking_admin_err_' . get_current_user_id(), $child_id->get_error_message(), 60 );
			wp_safe_redirect( admin_url( 'post.php?post=' . $enrolment_id . '&action=edit' ) );
			exit;
		}

		set_transient( 'jn_booking_admin_ok_' . get_current_user_id(), 'Đã tạo buổi học Mentorship tiếp theo.', 60 );
		wp_safe_redirect( admin_url( 'post.php?post=' . (int) $child_id . '&action=edit' ) );
		exit;
	}

	/**
	 * Render the Light HTML/CSS Agenda View ("Lịch làm việc theo ngày") of confirmed bookings.
	 *
	 * @return void
	 */
	public static function render_agenda_page(): void {
		if ( ! JN_Booking_Roles::user_can_manage_bookings() ) {
			wp_die( 'Bạn không có quyền xem trang này.' );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$from_date = isset( $_GET['agenda_from'] ) ? sanitize_text_field( wp_unslash( $_GET['agenda_from'] ) ) : '';
		$to_date   = isset( $_GET['agenda_to'] ) ? sanitize_text_field( wp_unslash( $_GET['agenda_to'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$tz = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
		try {
			$start_dt = '' !== $from_date
				? new \DateTimeImmutable( $from_date . ' 00:00:00', $tz )
				: new \DateTimeImmutable( 'today -7 days 00:00:00', $tz );
			$end_dt   = '' !== $to_date
				? new \DateTimeImmutable( $to_date . ' 23:59:59', $tz )
				: $start_dt->modify( '+60 days 23:59:59' );
		} catch ( \Exception $e ) {
			$start_dt = new \DateTimeImmutable( 'today -7 days 00:00:00', $tz );
			$end_dt   = $start_dt->modify( '+60 days 23:59:59' );
		}

		$booking_ids = get_posts(
			array(
				'post_type'      => JN_Booking_CPT::POST_TYPE,
				'post_status'    => array( JN_Booking_CPT::STATUS_CONFIRMED, JN_Booking_CPT::STATUS_COMPLETED ),
				'posts_per_page' => 200,
				'meta_key'       => '_jn_confirmed_start_ts', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'orderby'        => 'meta_value_num',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'     => '_jn_confirmed_start_ts',
						'value'   => $start_dt->getTimestamp(),
						'compare' => '>=',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => '_jn_confirmed_start_ts',
						'value'   => $end_dt->getTimestamp(),
						'compare' => '<=',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		$by_day = array();
		foreach ( $booking_ids as $bid ) {
			$bid       = (int) $bid;
			$raw_start = (string) get_post_meta( $bid, '_jn_confirmed_start', true );
			if ( '' === $raw_start ) {
				continue;
			}
			$day_key              = substr( $raw_start, 0, 10 );
			$by_day[ $day_key ][] = $bid;
		}
		?>
		<div class="wrap">
			<h1>Lịch Làm Việc Theo Ngày (Agenda View — Asia/Ho_Chi_Minh)</h1>
			<p class="description">Danh sách các phiên Coaching 1:1 <strong>Đã xác nhận lịch</strong> sắp xếp theo từng ngày làm việc.</p>

			<form method="get" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>" style="margin:16px 0;display:flex;gap:10px;align-items:center;">
				<input type="hidden" name="post_type" value="<?php echo esc_attr( JN_Booking_CPT::POST_TYPE ); ?>" />
				<input type="hidden" name="page" value="jn-booking-agenda" />
				<label>Từ ngày: <input type="date" name="agenda_from" value="<?php echo esc_attr( $start_dt->format( 'Y-m-d' ) ); ?>" /></label>
				<label>Đến ngày: <input type="date" name="agenda_to" value="<?php echo esc_attr( $end_dt->format( 'Y-m-d' ) ); ?>" /></label>
				<button type="submit" class="button button-primary">Xem lịch</button>
			</form>

			<?php if ( empty( $by_day ) ) : ?>
				<div class="notice notice-info"><p>Không có lịch hẹn nào đã xác nhận trong khoảng thời gian đã chọn.</p></div>
			<?php else : ?>
				<?php foreach ( $by_day as $day_str => $day_booking_ids ) : ?>
					<?php
					$day_dt = new \DateTimeImmutable( $day_str, $tz );
					?>
					<div class="jn-agenda-day-card">
						<h2 class="jn-agenda-day-title"><?php echo esc_html( 'Ngày ' . $day_dt->format( 'd/m/Y' ) ); ?></h2>
						<table class="widefat striped">
							<thead>
								<tr>
									<th style="width:14%;">Khung giờ (GMT+7)</th>
									<th style="width:14%;">Mã</th>
									<th style="width:20%;">Khách hàng &amp; SĐT</th>
									<th style="width:22%;">Gói cố vấn</th>
									<th style="width:16%;">Hình thức / Địa điểm</th>
									<th style="width:14%;">Thanh toán</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $day_booking_ids as $bid ) : ?>
									<?php
									$start_ts = (int) get_post_meta( $bid, '_jn_confirmed_start_ts', true );
									$end_ts   = (int) get_post_meta( $bid, '_jn_confirmed_end_ts', true );
									$s_dt     = ( new \DateTimeImmutable( '@' . $start_ts ) )->setTimezone( $tz );
									$e_dt     = ( new \DateTimeImmutable( '@' . $end_ts ) )->setTimezone( $tz );
									$code     = (string) get_post_meta( $bid, '_jn_booking_code', true );
									$cname    = (string) get_post_meta( $bid, '_jn_customer_name', true );
									$cphone   = (string) get_post_meta( $bid, '_jn_customer_phone', true );
									$pkg      = (string) get_post_meta( $bid, '_jn_package_title', true );
									$fmt      = (string) get_post_meta( $bid, '_jn_format', true );
									$link     = (string) get_post_meta( $bid, '_jn_meeting_link', true );
									$loc      = (string) get_post_meta( $bid, '_jn_location', true );
									$pstate   = JN_Booking_Service::get_derived_payment_state( $bid );
									?>
									<tr>
										<td><strong><?php echo esc_html( $s_dt->format( 'H:i' ) . ' – ' . $e_dt->format( 'H:i' ) ); ?></strong></td>
										<td><a href="<?php echo esc_url( (string) get_edit_post_link( $bid ) ); ?>"><strong><?php echo esc_html( $code ); ?></strong></a></td>
										<td><?php echo esc_html( $cname ); ?><br><code><?php echo esc_html( $cphone ); ?></code></td>
										<td><?php echo esc_html( $pkg ); ?></td>
										<td>
											<?php if ( 'online' === $fmt ) : ?>
												Online: <a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener">Mở phòng họp</a>
											<?php else : ?>
												Tại Academy: <?php echo esc_html( $loc ); ?>
											<?php endif; ?>
										</td>
										<td><span class="jn-pay-pill jn-pay-pill--<?php echo esc_attr( (string) $pstate['state'] ); ?>"><?php echo esc_html( (string) $pstate['label'] ); ?></span></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the Booking Engine Settings screen ("Lịch hẹn -> Cài đặt").
	 *
	 * @return void
	 */
	public static function render_settings_page(): void {
		if ( ! JN_Booking_Roles::user_can_manage_bookings() ) {
			wp_die( 'Bạn không có quyền truy cập cài đặt lịch hẹn.' );
		}

		$settings = JN_Booking_Settings::get_all();
		$windows  = (array) $settings['time_windows'];
		$formats  = (array) $settings['formats'];
		$limits   = (array) $settings['capacity_limits'];
		?>
		<div class="wrap">
			<h1>Cài Đặt Hệ Thống Đặt Lịch Coaching &amp; Mentorship</h1>
			<p class="description">
				Quản lý danh sách email trợ lý, định nghĩa khung giờ, địa chỉ Học viện, giới hạn số lượng nhận mỗi tháng, tự động tạo tài khoản và chính sách bảo mật.
				Mẫu email đặt lịch được chỉnh sửa tại <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email' ) ); ?>"><strong>WooCommerce → Cài đặt → Email</strong></a>.
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="jn_save_booking_settings" />
				<?php wp_nonce_field( 'jn_save_booking_settings_nonce', 'jn_settings_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="jn-staff-emails">Email nhận thông báo của trợ lý</label></th>
						<td>
							<input type="text" id="jn-staff-emails" name="staff_emails" value="<?php echo esc_attr( (string) $settings['staff_emails'] ); ?>" class="regular-text" style="width:100%;max-width:520px;" />
							<p class="description">Phân cách nhiều email bằng dấu phẩy (ví dụ: <code>contact@jannienguyen.vn, admin@example.com</code>).</p>
						</td>
					</tr>

					<tr>
						<th scope="row">Định nghĩa khung giờ mong muốn</th>
						<td>
							<p><label>Buổi sáng (<code>morning</code>): <input type="text" name="time_windows[morning]" value="<?php echo esc_attr( (string) ( $windows['morning'] ?? '' ) ); ?>" class="regular-text" /></label></p>
							<p><label>Buổi chiều (<code>afternoon</code>): <input type="text" name="time_windows[afternoon]" value="<?php echo esc_attr( (string) ( $windows['afternoon'] ?? '' ) ); ?>" class="regular-text" /></label></p>
							<p><label>Cuối tuần (<code>weekend</code>): <input type="text" name="time_windows[weekend]" value="<?php echo esc_attr( (string) ( $windows['weekend'] ?? '' ) ); ?>" class="regular-text" /></label></p>
						</td>
					</tr>

					<tr>
						<th scope="row">Hình thức gặp &amp; Địa chỉ Học viện</th>
						<td>
							<p><label>Nhãn Online (<code>online</code>): <input type="text" name="formats[online]" value="<?php echo esc_attr( (string) ( $formats['online'] ?? '' ) ); ?>" class="regular-text" /></label></p>
							<p><label>Nhãn Trực tiếp (<code>in-person</code>): <input type="text" name="formats[in-person]" value="<?php echo esc_attr( (string) ( $formats['in-person'] ?? '' ) ); ?>" class="regular-text" /></label></p>
							<p><label>Địa chỉ Học viện mặc định: <input type="text" name="academy_address" value="<?php echo esc_attr( (string) $settings['academy_address'] ); ?>" class="regular-text" /></label></p>
						</td>
					</tr>

					<tr>
						<th scope="row">Giới hạn số lượng nhận mỗi tháng (Capacity)</th>
						<td>
							<label>
								<input type="checkbox" name="capacity_enabled" value="yes" <?php checked( 'yes', (string) $settings['capacity_enabled'] ); ?> />
								Bật giới hạn số lượng lịch nhận mỗi tháng theo từng gói (Mặc định: Tắt)
							</label>
							<p style="margin-top:10px;">
								<label>Gói 30 Phút: <input type="number" name="capacity_limits[30-phut]" value="<?php echo esc_attr( (string) ( $limits['30-phut'] ?? 0 ) ); ?>" min="0" class="small-text" /> suất/tháng</label> &nbsp;|&nbsp;
								<label>Gói 60 Phút: <input type="number" name="capacity_limits[60-phut]" value="<?php echo esc_attr( (string) ( $limits['60-phut'] ?? 0 ) ); ?>" min="0" class="small-text" /> suất/tháng</label> &nbsp;|&nbsp;
								<label>Master Mentorship: <input type="number" name="capacity_limits[mentorship-3-thang]" value="<?php echo esc_attr( (string) ( $limits['mentorship-3-thang'] ?? 0 ) ); ?>" min="0" class="small-text" /> suất/tháng</label>
							</p>
							<p>
								<label for="jn-waitlist-note"><strong>Thông báo Danh sách chờ (Waitlist) khi đủ lịch:</strong></label><br>
								<textarea id="jn-waitlist-note" name="waitlist_note" rows="2" class="large-text"><?php echo esc_textarea( (string) $settings['waitlist_note'] ); ?></textarea>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">Tự động tạo tài khoản khách hàng</th>
						<td>
							<label>
								<input type="checkbox" name="auto_create_account" value="yes" <?php checked( 'yes', (string) $settings['auto_create_account'] ); ?> />
								Tự động tạo tài khoản WooCommerce ngay khi khách gửi biểu mẫu (Mặc định: <code>Không</code> theo Project Context Card — lưu hồ sơ khách vãng lai và tự gắn khi khách đăng ký)
							</label>
						</td>
					</tr>

					<tr>
						<th scope="row">Chính sách bảo mật &amp; Đồng ý dữ liệu</th>
						<td>
							<p>
								<label for="jn-consent-text">Nội dung ô tích đồng ý (Consent):</label><br>
								<textarea id="jn-consent-text" name="consent_text" rows="2" class="large-text"><?php echo esc_textarea( (string) $settings['consent_text'] ); ?></textarea>
							</p>
							<p>
								<label>ID Trang Chính sách bảo mật: <input type="number" name="privacy_page_id" value="<?php echo esc_attr( (string) $settings['privacy_page_id'] ); ?>" class="small-text" /></label>
								&nbsp;&nbsp;
								<label>Phiên bản Chính sách: <input type="text" name="privacy_version" value="<?php echo esc_attr( (string) $settings['privacy_version'] ); ?>" class="small-text" /></label>
							</p>
						</td>
					</tr>
				</table>

				<?php submit_button( 'Lưu Cài Đặt Lịch Hẹn' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle saving the Booking Settings form.
	 *
	 * @return void
	 */
	public static function handle_save_settings(): void {
		if ( ! JN_Booking_Roles::user_can_manage_bookings() ) {
			wp_die( 'Bạn không có quyền lưu cài đặt này.', 'Từ chối truy cập', array( 'response' => 403 ) );
		}

		check_admin_referer( 'jn_save_booking_settings_nonce', 'jn_settings_nonce' );

		$raw = wp_unslash( $_POST );
		if ( ! isset( $raw['capacity_enabled'] ) ) {
			$raw['capacity_enabled'] = 'no';
		}
		if ( ! isset( $raw['auto_create_account'] ) ) {
			$raw['auto_create_account'] = 'no';
		}

		JN_Booking_Settings::update( $raw );

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type'       => JN_Booking_CPT::POST_TYPE,
					'page'            => 'jn-booking-settings',
					'settings-updated' => 'true',
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	/**
	 * Display admin notices for booking transitions, errors, and settings saves.
	 *
	 * @return void
	 */
	public static function render_admin_notices(): void {
		$uid = get_current_user_id();
		$err = get_transient( 'jn_booking_admin_err_' . $uid );
		if ( false !== $err && '' !== $err ) {
			delete_transient( 'jn_booking_admin_err_' . $uid );
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( (string) $err ) . '</p></div>';
		}

		$ok = get_transient( 'jn_booking_admin_ok_' . $uid );
		if ( false !== $ok && '' !== $ok ) {
			delete_transient( 'jn_booking_admin_ok_' . $uid );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( (string) $ok ) . '</p></div>';
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['jn_admin_err'] ) && is_string( $_GET['jn_admin_err'] ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['jn_admin_err'] ) ) ) . '</p></div>';
		}
		if ( isset( $_GET['jn_admin_msg'] ) && 'updated' === $_GET['jn_admin_msg'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>Đã cập nhật trạng thái lịch hẹn.</p></div>';
		}
		if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>Đã lưu cài đặt hệ thống đặt lịch.</p></div>';
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Print scoped admin CSS for booking badges, timeline, and agenda cards.
	 *
	 * @return void
	 */
	public static function print_admin_styles(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || JN_Booking_CPT::POST_TYPE !== $screen->post_type ) {
			return;
		}
		?>
		<style>
			.jn-status-pill, .jn-pay-pill, .jn-badge {
				display: inline-block;
				padding: 3px 10px;
				border-radius: 9999px;
				font-size: 12px;
				font-weight: 600;
				line-height: 1.4;
			}
			.jn-status-pill--jn_pending { background: #FFF4E5; color: #8A5300; border: 1px solid #F5D5A3; }
			.jn-status-pill--jn_confirmed { background: #E6F4EA; color: #137333; border: 1px solid #A8DAB5; }
			.jn-status-pill--jn_completed { background: #1F1E1D; color: #FAF8F5; }
			.jn-status-pill--jn_no_show { background: #FCE8E6; color: #C5221F; border: 1px solid #F6AEA9; }
			.jn-status-pill--jn_cancelled { background: #F1F3F4; color: #5F6368; border: 1px solid #DADCE0; }

			.jn-pay-pill--unbilled { background: #F1F3F4; color: #5F6368; }
			.jn-pay-pill--pending_payment, .jn-pay-pill--on_hold_verification { background: #FEF7E0; color: #7C5800; }
			.jn-pay-pill--paid { background: #E6F4EA; color: #137333; }
			.jn-pay-pill--paid_refund_flagged { background: #FCE8E6; color: #B31412; border: 1px solid #F28B82; }
			.jn-pay-pill--order_cancelled, .jn-pay-pill--refunded { background: #F1F3F4; color: #5F6368; }

			.jn-badge--session { background: #EFECE6; color: #1F1E1D; font-size: 11px; }
			.jn-badge--waitlist { background: #FCE8E6; color: #A50E0E; font-size: 11px; }
			.jn-badge--online { background: #E8F0FE; color: #1967D2; }
			.jn-badge--inperson { background: #F3EFEA; color: #4A4640; }
			.jn-sub-meta { color: #646970; font-size: 12px; }

			.jn-admin-status-bar {
				display: flex;
				flex-wrap: wrap;
				justify-content: space-between;
				align-items: center;
				gap: 16px;
				padding: 14px 16px;
				background: #FAF8F5;
				border: 1px solid #E5DFD7;
				border-radius: 8px;
				margin-bottom: 16px;
			}
			.jn-admin-grid-2 {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
				gap: 16px;
				margin-bottom: 16px;
			}
			.jn-overlap-alert, .jn-refund-alert {
				padding: 12px 16px;
				background: #FFF4E5;
				border-left: 4px solid #D97706;
				margin-bottom: 16px;
				border-radius: 4px;
			}
			.jn-refund-alert {
				background: #FCE8E6;
				border-left-color: #C5221F;
				color: #93000A;
			}
			.jn-agenda-day-card {
				background: #FFFFFF;
				border: 1px solid #DCDCDE;
				border-radius: 8px;
				padding: 16px;
				margin-bottom: 20px;
			}
			.jn-agenda-day-title {
				margin-top: 0;
				padding-bottom: 8px;
				border-bottom: 1px solid #E5DFD7;
			}
		</style>
		<?php
	}
}
