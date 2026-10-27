<?php
/**
 * Nonce-Protected, Streamed UTF-8 with BOM CSV Exporter for Customers and Bookings.
 *
 * - Exports customers (`jn_export_customers_csv`) and bookings (`jn_export_bookings_csv`).
 * - Enforces capability check (`export_jn_data` / `JN_Booking_Roles::user_can_export_csv()`)
 *   and nonce check (`_wpnonce`).
 * - Prepends UTF-8 Byte Order Mark (`\xEF\xBB\xBF`) so Microsoft Excel displays Vietnamese characters accurately.
 * - Mitigates CSV Formula Injection (DDE) by escaping leading `=`, `+`, `-`, `@`, `\t`, `\r`.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_CSV_Exporter
 */
class JN_CSV_Exporter {

	/**
	 * UTF-8 Byte Order Mark (BOM) for Excel Vietnamese compatibility.
	 */
	const UTF8_BOM = "\xEF\xBB\xBF";

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_post_jn_export_customers_csv', array( __CLASS__, 'handle_export_customers_request' ) );
		add_action( 'admin_post_jn_export_bookings_csv', array( __CLASS__, 'handle_export_bookings_request' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'render_bookings_export_button' ), 30, 1 );
	}

	/**
	 * Render "Xuất CSV Lịch hẹn" button in the `jn_booking` list table toolbar for authorised roles.
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public static function render_bookings_export_button( string $post_type ): void {
		if ( JN_Booking_CPT::POST_TYPE !== $post_type || ! JN_Booking_Roles::user_can_export_csv() ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$export_args = array(
			'action'              => 'jn_export_bookings_csv',
			'filter_jn_status'    => isset( $_GET['filter_jn_status'] ) ? sanitize_key( wp_unslash( $_GET['filter_jn_status'] ) ) : '',
			'filter_jn_package'   => isset( $_GET['filter_jn_package'] ) ? sanitize_key( wp_unslash( $_GET['filter_jn_package'] ) ) : '',
			'filter_jn_format'    => isset( $_GET['filter_jn_format'] ) ? sanitize_key( wp_unslash( $_GET['filter_jn_format'] ) ) : '',
			'filter_jn_date_from' => isset( $_GET['filter_jn_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_jn_date_from'] ) ) : '',
			'filter_jn_date_to'   => isset( $_GET['filter_jn_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_jn_date_to'] ) ) : '',
			's'                   => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$export_url = wp_nonce_url(
			add_query_arg( $export_args, admin_url( 'admin-post.php' ) ),
			'jn_export_bookings_csv'
		);

		echo '<a href="' . esc_url( $export_url ) . '" class="button" style="margin-left:6px;">Xuất CSV Lịch hẹn</a>';
	}

	/**
	 * Sanitize a single CSV cell value to prevent formula injection (CSV Injection / DDE) in Excel.
	 *
	 * Note: Phone numbers starting with `+84` are prefixed with `'` or kept safe;
	 * in Excel, prefixing `'...` forces text treatment and prevents formula evaluation!
	 *
	 * @param mixed $value        Cell value.
	 * @param bool  $preserve_tel Whether this is a valid E.164 phone number (`+84...`).
	 * @return string
	 */
	public static function sanitize_csv_cell( $value, bool $preserve_tel = false ): string {
		$str = trim( (string) $value );
		if ( '' === $str ) {
			return '';
		}

		if ( $preserve_tel && preg_match( '/^\+84[0-9]{8,10}$/', $str ) ) {
			return $str;
		}

		$first_char = $str[0];
		if ( in_array( $first_char, array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $str;
		}

		return $str;
	}

	/**
	 * Validate authorization and nonce for a CSV export request.
	 *
	 * @param string $nonce_action Expected nonce action (`jn_export_customers_csv` or `jn_export_bookings_csv`).
	 * @param string $nonce_value  Provided nonce string.
	 * @param int    $user_id      Optional user ID (defaults to current user).
	 * @return true|\WP_Error
	 */
	public static function verify_export_request( string $nonce_action, string $nonce_value, int $user_id = 0 ) {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}

		if ( ! JN_Booking_Roles::user_can_export_csv( $user_id ) ) {
			return new \WP_Error(
				'forbidden_csv_export',
				'Tài khoản của bạn không có quyền xuất dữ liệu CSV.',
				array( 'status' => 403 )
			);
		}

		if ( '' === $nonce_value || ! wp_verify_nonce( $nonce_value, $nonce_action ) ) {
			return new \WP_Error(
				'invalid_csv_nonce',
				'Phiên xác thực xuất CSV không hợp lệ hoặc đã hết hạn.',
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Handle `admin_post_jn_export_customers_csv` HTTP request.
	 *
	 * @return void
	 */
	public static function handle_export_customers_request(): void {
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		$check = self::verify_export_request( 'jn_export_customers_csv', $nonce );
		if ( is_wp_error( $check ) ) {
			wp_die(
				esc_html( $check->get_error_message() ),
				'Từ chối truy cập',
				array( 'response' => 403 )
			);
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$filters = array(
			'search'              => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'type'                => isset( $_GET['customer_type'] ) ? sanitize_key( wp_unslash( $_GET['customer_type'] ) ) : '',
			'tag'                 => isset( $_GET['customer_tag'] ) ? sanitize_text_field( wp_unslash( $_GET['customer_tag'] ) ) : '',
			'has_pending_booking' => isset( $_GET['has_pending_booking'] ) ? sanitize_text_field( wp_unslash( $_GET['has_pending_booking'] ) ) : '',
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$tz       = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
		$now_vn   = new \DateTimeImmutable( 'now', $tz );
		$filename = 'jn-khach-hang-' . $now_vn->format( 'Y-m-d-His' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$stream = fopen( 'php://output', 'w' );
		if ( is_resource( $stream ) ) {
			self::write_customers_csv_to_stream( $stream, $filters );
			fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}
		exit;
	}

	/**
	 * Handle `admin_post_jn_export_bookings_csv` HTTP request.
	 *
	 * @return void
	 */
	public static function handle_export_bookings_request(): void {
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		$check = self::verify_export_request( 'jn_export_bookings_csv', $nonce );
		if ( is_wp_error( $check ) ) {
			wp_die(
				esc_html( $check->get_error_message() ),
				'Từ chối truy cập',
				array( 'response' => 403 )
			);
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$filters = array(
			'status'    => isset( $_GET['filter_jn_status'] ) ? sanitize_key( wp_unslash( $_GET['filter_jn_status'] ) ) : '',
			'package'   => isset( $_GET['filter_jn_package'] ) ? sanitize_key( wp_unslash( $_GET['filter_jn_package'] ) ) : '',
			'format'    => isset( $_GET['filter_jn_format'] ) ? sanitize_key( wp_unslash( $_GET['filter_jn_format'] ) ) : '',
			'date_from' => isset( $_GET['filter_jn_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_jn_date_from'] ) ) : '',
			'date_to'   => isset( $_GET['filter_jn_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_jn_date_to'] ) ) : '',
			'search'    => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$tz       = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
		$now_vn   = new \DateTimeImmutable( 'now', $tz );
		$filename = 'jn-lich-hen-' . $now_vn->format( 'Y-m-d-His' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$stream = fopen( 'php://output', 'w' );
		if ( is_resource( $stream ) ) {
			self::write_bookings_csv_to_stream( $stream, $filters );
			fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}
		exit;
	}

	/**
	 * Stream Customers CSV (UTF-8 with BOM) into any writable stream resource.
	 *
	 * @param resource             $stream  Writable stream resource (`php://output` or `php://temp`).
	 * @param array<string, mixed> $filters Optional filter arguments.
	 * @return void
	 */
	public static function write_customers_csv_to_stream( $stream, array $filters = array() ): void {
		// Write UTF-8 BOM so Microsoft Excel recognizes Vietnamese UTF-8 characters immediately.
		fwrite( $stream, self::UTF8_BOM ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

		$headers = array(
			'Mã Khách Hàng',
			'Họ và Tên',
			'SĐT / Zalo (+84)',
			'Email',
			'Loại Khách',
			'Tên Cơ Sở',
			'Tỉnh / Thành',
			'Nguồn',
			'Tags',
			'Tổng Lịch Hẹn',
			'Lịch Chờ Xác Nhận',
			'Lịch Hẹn Gần Nhất',
			'Tổng Đơn Hàng',
			'Tổng Chi Tiêu (VND)',
			'Đồng Ý Bảo Mật Gần Nhất',
		);
		fputcsv( $stream, $headers );

		$customers = JN_Customer_Service::query_customers( $filters );

		foreach ( $customers as $cust ) {
			$last_b       = $cust['last_booking'];
			$last_b_label = is_array( $last_b )
				? sprintf( '%s (%s - %s)', $last_b['booking_code'], $last_b['submitted_at'], $last_b['status_label'] )
				: '';

			$latest_consent = '';
			if ( ! empty( $cust['consent_log'] ) && is_array( $cust['consent_log'] ) ) {
				$first_c        = reset( $cust['consent_log'] );
				$latest_consent = isset( $first_c['timestamp'] ) ? (string) $first_c['timestamp'] : '';
			}

			$row = array(
				self::sanitize_csv_cell( $cust['customer_key'] ),
				self::sanitize_csv_cell( $cust['full_name'] ),
				self::sanitize_csv_cell( $cust['phone'], true ),
				self::sanitize_csv_cell( $cust['email'] ),
				self::sanitize_csv_cell( $cust['customer_type_label'] ),
				self::sanitize_csv_cell( $cust['business_name'] ),
				self::sanitize_csv_cell( $cust['province_city'] ),
				self::sanitize_csv_cell( $cust['source_type_label'] . ( '' !== $cust['source_detail'] ? ' (' . $cust['source_detail'] . ')' : '' ) ),
				self::sanitize_csv_cell( implode( ', ', (array) $cust['tags'] ) ),
				(string) (int) $cust['bookings_count'],
				(string) (int) $cust['pending_bookings_count'],
				self::sanitize_csv_cell( $last_b_label ),
				(string) (int) $cust['orders_count'],
				(string) (int) round( (float) $cust['total_spent'] ),
				self::sanitize_csv_cell( $latest_consent ),
			);

			fputcsv( $stream, $row );
		}
	}

	/**
	 * Stream Bookings CSV (UTF-8 with BOM) into any writable stream resource.
	 *
	 * @param resource             $stream  Writable stream resource (`php://output` or `php://temp`).
	 * @param array<string, mixed> $filters Optional filter arguments (`status`, `package`, `format`, `date_from`, `date_to`, `search`).
	 * @return void
	 */
	public static function write_bookings_csv_to_stream( $stream, array $filters = array() ): void {
		// Write UTF-8 BOM so Microsoft Excel recognizes Vietnamese UTF-8 characters immediately.
		fwrite( $stream, self::UTF8_BOM ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

		$headers = array(
			'Mã Lịch Hẹn',
			'Ngày Gửi Yêu Cầu',
			'Họ và Tên Khách Hàng',
			'SĐT / Zalo (+84)',
			'Email',
			'Gói Cố Vấn',
			'Hình Thức Gặp',
			'Khung Giờ Mong Muốn',
			'Lịch Hẹn Xác Nhận (GMT+7)',
			'Trạng Thái Lịch Hẹn',
			'Trạng Thái Thanh Toán',
			'Mã Đơn Hàng WooCommerce',
			'Mục Tiêu / Thách Thức',
		);
		fputcsv( $stream, $headers );

		$statuses      = JN_Booking_CPT::get_statuses();
		$filter_status = isset( $filters['status'] ) ? sanitize_key( (string) $filters['status'] ) : '';
		$post_statuses = ( '' !== $filter_status && isset( $statuses[ $filter_status ] ) )
			? array( $filter_status )
			: array_keys( $statuses );

		$meta_query = array();
		if ( ! empty( $filters['package'] ) ) {
			$meta_query[] = array(
				'key'   => '_jn_package_key',
				'value' => sanitize_key( (string) $filters['package'] ),
			);
		}
		if ( ! empty( $filters['format'] ) ) {
			$meta_query[] = array(
				'key'   => '_jn_format',
				'value' => sanitize_key( (string) $filters['format'] ),
			);
		}

		$date_query = array();
		if ( ! empty( $filters['date_from'] ) || ! empty( $filters['date_to'] ) ) {
			$dq = array( 'inclusive' => true );
			if ( ! empty( $filters['date_from'] ) ) {
				$dq['after'] = sanitize_text_field( (string) $filters['date_from'] ) . ' 00:00:00';
			}
			if ( ! empty( $filters['date_to'] ) ) {
				$dq['before'] = sanitize_text_field( (string) $filters['date_to'] ) . ' 23:59:59';
			}
			$date_query[] = $dq;
		}

		$query_args = array(
			'post_type'      => JN_Booking_CPT::POST_TYPE,
			'post_status'    => $post_statuses,
			'posts_per_page' => 500,
			'orderby'        => 'ID',
			'order'          => 'DESC',
		);
		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}
		if ( ! empty( $date_query ) ) {
			$query_args['date_query'] = $date_query;
		}

		$posts  = get_posts( $query_args );
		$search = isset( $filters['search'] ) ? trim( sanitize_text_field( (string) $filters['search'] ) ) : '';

		foreach ( $posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$bid       = (int) $post->ID;
			$code      = (string) get_post_meta( $bid, '_jn_booking_code', true );
			$name      = (string) get_post_meta( $bid, '_jn_customer_name', true );
			$phone     = (string) get_post_meta( $bid, '_jn_customer_phone', true );
			$email     = (string) get_post_meta( $bid, '_jn_customer_email', true );
			$pkg       = (string) get_post_meta( $bid, '_jn_package_title', true );
			$fmt_label = (string) get_post_meta( $bid, '_jn_format_label', true );
			$win_label = (string) get_post_meta( $bid, '_jn_preferred_window_label', true );
			$confirmed = (string) get_post_meta( $bid, '_jn_confirmed_start', true );
			$goals     = (string) get_post_meta( $bid, '_jn_goals', true );
			$pstate    = JN_Booking_Service::get_derived_payment_state( $bid );

			if ( '' !== $search ) {
				$s_low = mb_strtolower( $search );
				$match = ( false !== mb_strpos( mb_strtolower( $code ), $s_low ) )
					|| ( false !== mb_strpos( mb_strtolower( $name ), $s_low ) )
					|| ( false !== mb_strpos( mb_strtolower( $email ), $s_low ) )
					|| JN_Customer_Service::phone_matches_search( $phone, $search );
				if ( ! $match ) {
					continue;
				}
			}

			$row = array(
				self::sanitize_csv_cell( '' !== $code ? $code : '#' . $bid ),
				self::sanitize_csv_cell( get_the_date( 'd/m/Y H:i', $post ) ),
				self::sanitize_csv_cell( $name ),
				self::sanitize_csv_cell( $phone, true ),
				self::sanitize_csv_cell( $email ),
				self::sanitize_csv_cell( $pkg ),
				self::sanitize_csv_cell( $fmt_label ),
				self::sanitize_csv_cell( $win_label ),
				self::sanitize_csv_cell( $confirmed ),
				self::sanitize_csv_cell( JN_Booking_CPT::get_status_label( (string) $post->post_status ) ),
				self::sanitize_csv_cell( (string) $pstate['label'] ),
				! empty( $pstate['order_id'] ) ? '#' . (int) $pstate['order_id'] : '',
				self::sanitize_csv_cell( $goals ),
			);

			fputcsv( $stream, $row );
		}
	}

	/**
	 * Build the Customers CSV string in memory (used by automated tests to verify BOM, encoding, and rows).
	 *
	 * @param array<string, mixed> $filters Optional filter arguments.
	 * @return string
	 */
	public static function build_customers_csv_string( array $filters = array() ): string {
		$stream = fopen( 'php://temp', 'r+' );
		if ( ! is_resource( $stream ) ) {
			return '';
		}
		self::write_customers_csv_to_stream( $stream, $filters );
		rewind( $stream );
		$contents = (string) stream_get_contents( $stream );
		fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		return $contents;
	}

	/**
	 * Build the Bookings CSV string in memory (used by automated tests to verify BOM, encoding, and rows).
	 *
	 * @param array<string, mixed> $filters Optional filter arguments.
	 * @return string
	 */
	public static function build_bookings_csv_string( array $filters = array() ): string {
		$stream = fopen( 'php://temp', 'r+' );
		if ( ! is_resource( $stream ) ) {
			return '';
		}
		self::write_bookings_csv_to_stream( $stream, $filters );
		rewind( $stream );
		$contents = (string) stream_get_contents( $stream );
		fclose( $stream ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		return $contents;
	}
}
