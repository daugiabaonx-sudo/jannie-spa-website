<?php
/**
 * WooCommerce My Account Endpoint "Lịch hẹn của tôi" (`/tai-khoan/lich-hen/`).
 *
 * Displays the logged-in customer's coaching bookings with status, confirmed time
 * (or preferred window), format, meeting link/location, `.ics` download, and a
 * prominent "Thanh toán ngay" (`order-pay`) button while payable.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Booking_MyAccount
 */
class JN_Booking_MyAccount {

	/**
	 * Endpoint slug under `/tai-khoan/`.
	 */
	const ENDPOINT = 'lich-hen';

	/**
	 * Option flag for one-time rewrite rules flush after adding the endpoint.
	 */
	const REWRITE_FLAG = 'jn_booking_myaccount_rewrite_v1';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_endpoint' ), 10 );
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrites' ), 99 );
		add_filter( 'woocommerce_get_query_vars', array( __CLASS__, 'add_query_vars' ), 20, 1 );
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_account_menu_item' ), 20, 1 );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( __CLASS__, 'render_endpoint' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_ics_download' ), 10 );
	}

	/**
	 * Register the `lich-hen` rewrite endpoint.
	 *
	 * @return void
	 */
	public static function register_endpoint(): void {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Flush rewrite rules once after registering the `lich-hen` endpoint.
	 *
	 * @return void
	 */
	public static function maybe_flush_rewrites(): void {
		if ( '1' !== get_option( self::REWRITE_FLAG ) ) {
			flush_rewrite_rules( false );
			update_option( self::REWRITE_FLAG, '1', true );
		}
	}

	/**
	 * Register `lich-hen` in WooCommerce query vars.
	 *
	 * Confirmed filter in `woocommerce/includes/class-wc-query.php`.
	 *
	 * @param array<string, string> $vars Query vars.
	 * @return array<string, string>
	 */
	public static function add_query_vars( array $vars ): array {
		$vars[ self::ENDPOINT ] = self::ENDPOINT;
		return $vars;
	}

	/**
	 * Insert "Lịch hẹn của tôi" into the My Account navigation menu.
	 *
	 * Confirmed filter in `woocommerce/includes/wc-account-functions.php`.
	 *
	 * @param array<string, string> $items Account menu items.
	 * @return array<string, string>
	 */
	public static function add_account_menu_item( array $items ): array {
		$new_items = array();
		$inserted  = false;

		foreach ( $items as $key => $label ) {
			if ( 'orders' === $key ) {
				$new_items[ self::ENDPOINT ] = 'Lịch hẹn của tôi';
				$inserted                    = true;
			}
			$new_items[ $key ] = $label;
		}

		if ( ! $inserted ) {
			$new_items[ self::ENDPOINT ] = 'Lịch hẹn của tôi';
		}

		return $new_items;
	}

	/**
	 * Serve `.ics` download for a confirmed booking owned by the current logged-in customer.
	 *
	 * @return void
	 */
	public static function handle_ics_download(): void {
		if ( ! is_user_logged_in() || ! isset( $_GET['jn_download_ics'] ) ) {
			return;
		}

		$booking_id = absint( $_GET['jn_download_ics'] );
		$nonce      = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( $booking_id <= 0 || ! wp_verify_nonce( $nonce, 'jn_download_ics_' . $booking_id ) ) {
			wp_die( 'Liên kết tải lịch không hợp lệ hoặc đã hết hạn.', 'Lỗi xác thực', array( 'response' => 403 ) );
		}

		$user_id      = get_current_user_id();
		$booking_user = (int) get_post_meta( $booking_id, '_jn_customer_user_id', true );
		if ( $booking_user !== $user_id && ! JN_Booking_Roles::user_can_manage_bookings( $user_id ) ) {
			wp_die( 'Bạn không có quyền truy cập lịch hẹn này.', 'Từ chối truy cập', array( 'response' => 403 ) );
		}

		$ics_content = JN_Booking_ICS::generate_content( $booking_id );
		if ( '' === $ics_content ) {
			wp_die( 'Lịch hẹn này chưa có khung giờ xác nhận để tạo tệp lịch.', 'Chưa có lịch', array( 'response' => 404 ) );
		}

		$code     = (string) get_post_meta( $booking_id, '_jn_booking_code', true );
		$filename = sprintf( 'lich-hen-%s.ics', sanitize_file_name( strtolower( '' !== $code ? $code : (string) $booking_id ) ) );

		nocache_headers();
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo $ics_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Query all bookings belonging to a user ID (auto-linking any matching guest bookings first).
	 *
	 * @param int $user_id WordPress User ID.
	 * @return \WP_Post[]
	 */
	public static function get_customer_bookings( int $user_id ): array {
		if ( $user_id <= 0 ) {
			return array();
		}

		JN_Customer_Service::link_guest_bookings_to_user( $user_id );

		$posts = get_posts(
			array(
				'post_type'      => JN_Booking_CPT::POST_TYPE,
				'post_status'    => array_keys( JN_Booking_CPT::get_statuses() ),
				'posts_per_page' => 50,
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_jn_customer_user_id',
						'value' => $user_id,
						'type'  => 'NUMERIC',
					),
				),
			)
		);

		return is_array( $posts ) ? $posts : array();
	}

	/**
	 * Render the "Lịch hẹn của tôi" endpoint content inside `/tai-khoan/lich-hen/`.
	 *
	 * @return void
	 */
	public static function render_endpoint(): void {
		$user_id  = get_current_user_id();
		$bookings = self::get_customer_bookings( $user_id );
		?>
		<section class="jn-myaccount-bookings" aria-labelledby="jn-myaccount-bookings-heading">
			<h2 id="jn-myaccount-bookings-heading" class="jn-myaccount-section-title">Lịch Hẹn Coaching &amp; Mentorship Của Tôi</h2>

			<?php if ( empty( $bookings ) ) : ?>
				<div class="woocommerce-Message woocommerce-Message--info woocommerce-info jn-empty-bookings">
					<p>Bạn chưa có lịch hẹn Coaching nào. Hãy đăng ký tư vấn 1:1 cùng chuyên gia Jannie Nguyễn để bắt đầu lộ trình của bạn.</p>
					<p><a class="button wc-Forward" href="<?php echo esc_url( home_url( '/coaching/#booking-form' ) ); ?>">Đăng Ký Tư Vấn Coaching</a></p>
				</div>
			<?php else : ?>
				<div class="jn-bookings-table-wrap">
					<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive jn-bookings-table">
						<thead>
							<tr>
								<th class="woocommerce-orders-table__header"><span class="nobr">Mã &amp; Gói cố vấn</span></th>
								<th class="woocommerce-orders-table__header"><span class="nobr">Thời gian hẹn</span></th>
								<th class="woocommerce-orders-table__header"><span class="nobr">Hình thức &amp; Liên kết</span></th>
								<th class="woocommerce-orders-table__header"><span class="nobr">Trạng thái</span></th>
								<th class="woocommerce-orders-table__header"><span class="nobr">Thanh toán &amp; Thao tác</span></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $bookings as $post ) : ?>
								<?php
								$bid         = (int) $post->ID;
								$code        = (string) get_post_meta( $bid, '_jn_booking_code', true );
								$pkg_title   = (string) get_post_meta( $bid, '_jn_package_title', true );
								$sess_num    = (int) get_post_meta( $bid, '_jn_session_number', true );
								$total_sess  = (int) get_post_meta( $bid, '_jn_total_sessions', true );
								$status      = $post->post_status;
								$status_lbl  = JN_Booking_CPT::get_status_label( $status );
								// A tentative time saved on a pending booking stays internal until staff confirm it.
								$conf_start  = JN_Booking_Service::is_schedule_visible_to_customer( $bid ) ? (string) get_post_meta( $bid, '_jn_confirmed_start', true ) : '';
								$duration    = (int) get_post_meta( $bid, '_jn_duration_minutes', true );
								$pref_win    = (string) get_post_meta( $bid, '_jn_preferred_window_label', true );
								$format      = (string) get_post_meta( $bid, '_jn_format', true );
								$format_lbl  = (string) get_post_meta( $bid, '_jn_format_label', true );
								$meet_link   = (string) get_post_meta( $bid, '_jn_meeting_link', true );
								$location    = (string) get_post_meta( $bid, '_jn_location', true );
								$pstate      = JN_Booking_Service::get_derived_payment_state( $bid );
								$ics_url     = wp_nonce_url(
									add_query_arg( 'jn_download_ics', $bid, home_url( '/tai-khoan/' . self::ENDPOINT . '/' ) ),
									'jn_download_ics_' . $bid
								);

								$time_display = '';
								if ( '' !== $conf_start ) {
									try {
										$tz           = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
										$dt           = new \DateTimeImmutable( $conf_start, $tz );
										$time_display = $dt->format( 'H:i — d/m/Y' ) . ' (' . $duration . ' phút)';
									} catch ( \Exception $e ) {
										$time_display = $conf_start;
									}
								}
								?>
								<tr class="woocommerce-orders-table__row jn-booking-row">
									<td class="woocommerce-orders-table__cell" data-title="Mã &amp; Gói cố vấn">
										<strong class="jn-booking-code"><?php echo esc_html( $code ); ?></strong>
										<?php if ( $total_sess > 1 && $sess_num > 0 ) : ?>
											<span class="jn-booking-session-tag">(Buổi <?php echo esc_html( $sess_num . '/' . $total_sess ); ?>)</span>
										<?php endif; ?>
										<div class="jn-booking-pkg-name"><?php echo esc_html( $pkg_title ); ?></div>
									</td>

									<td class="woocommerce-orders-table__cell" data-title="Thời gian hẹn">
										<?php if ( '' !== $time_display ) : ?>
											<strong><?php echo esc_html( $time_display ); ?></strong>
											<div class="jn-booking-subtext">Múi giờ Việt Nam (GMT+7)</div>
										<?php else : ?>
											<span>Mong muốn: <?php echo esc_html( $pref_win ); ?></span>
											<div class="jn-booking-subtext">Trợ lý sẽ xác nhận trong 24h</div>
										<?php endif; ?>
									</td>

									<td class="woocommerce-orders-table__cell" data-title="Hình thức &amp; Liên kết">
										<div><?php echo esc_html( $format_lbl ); ?></div>
										<?php if ( JN_Booking_CPT::STATUS_CONFIRMED === $status || JN_Booking_CPT::STATUS_COMPLETED === $status ) : ?>
											<?php if ( 'online' === $format && '' !== $meet_link ) : ?>
												<a href="<?php echo esc_url( $meet_link ); ?>" class="jn-booking-venue-link" target="_blank" rel="noopener">Vào phòng họp trực tuyến ↗</a>
											<?php elseif ( 'in-person' === $format && '' !== $location ) : ?>
												<div class="jn-booking-subtext">Địa điểm: <?php echo esc_html( $location ); ?></div>
											<?php endif; ?>
										<?php endif; ?>
									</td>

									<td class="woocommerce-orders-table__cell" data-title="Trạng thái">
										<span class="jn-myaccount-status jn-myaccount-status--<?php echo esc_attr( $status ); ?>">
											<?php echo esc_html( $status_lbl ); ?>
										</span>
									</td>

									<td class="woocommerce-orders-table__cell" data-title="Thanh toán &amp; Thao tác">
										<div class="jn-booking-payment-label">
											<strong><?php echo esc_html( (string) $pstate['label'] ); ?></strong>
										</div>
										<div class="jn-booking-actions">
											<?php if ( ! empty( $pstate['is_payable'] ) && '' !== $pstate['pay_url'] ) : ?>
												<a href="<?php echo esc_url( (string) $pstate['pay_url'] ); ?>" class="woocommerce-button button pay jn-booking-pay-btn">Thanh Toán Ngay</a>
											<?php endif; ?>
											<?php if ( '' !== $conf_start && JN_Booking_CPT::STATUS_CONFIRMED === $status ) : ?>
												<a href="<?php echo esc_url( $ics_url ); ?>" class="woocommerce-button button jn-booking-ics-btn">Tải lịch .ics</a>
											<?php endif; ?>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}
}
