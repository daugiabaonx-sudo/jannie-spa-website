<?php
/**
 * Admin UI for the Unified Customer Directory ("Khách hàng") & Customer Detail View.
 *
 * Implements ADR-008:
 * - Dedicated "Khách hàng" admin screen (`admin.php?page=jn-customers`) showing all customers
 *   from bookings, orders, and registrations in one place.
 * - Columns: Họ tên, SĐT/Zalo, Email, Loại khách, Lịch hẹn, Đơn hàng, Tổng chi tiêu, Lịch hẹn gần nhất, Tags.
 * - Search by name, email, or phone in any format (`0908...`, `+84908...`).
 * - Filters by `Loại khách`, `Tag`, and `Có lịch hẹn chờ xác nhận` (`has_pending_booking`).
 * - Customer Detail screen: CRM profile editor (synced with WooCommerce billing), duplicate warning banner,
 *   booking history, HPOS order history with links, staff-only internal notes timeline, read-only consent log,
 *   and "Tạo lịch hẹn" action for phone or walk-in requests.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Customer_Admin
 */
class JN_Customer_Admin {

	/**
	 * Admin menu slug.
	 */
	const MENU_SLUG = 'jn-customers';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ), 20 );
		add_action( 'admin_head', array( __CLASS__, 'print_admin_styles' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_admin_notices' ) );

		// Form handlers (`admin-post.php`).
		add_action( 'admin_post_jn_save_customer_profile', array( __CLASS__, 'handle_save_customer_profile' ) );
		add_action( 'admin_post_jn_add_customer_note', array( __CLASS__, 'handle_add_customer_note' ) );
		add_action( 'admin_post_jn_create_staff_booking', array( __CLASS__, 'handle_create_staff_booking' ) );
	}

	/**
	 * Register the top-level "Khách hàng" admin menu for authorised staff roles.
	 *
	 * @return void
	 */
	public static function register_admin_menu(): void {
		add_menu_page(
			'Quản lý Khách hàng — Jannie Nguyễn',
			'Khách hàng',
			JN_Booking_Roles::CAP_CUSTOMERS,
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-groups',
			57
		);

		add_submenu_page(
			self::MENU_SLUG,
			'Danh sách Khách hàng — Jannie Nguyễn',
			'Tất cả khách hàng',
			JN_Booking_Roles::CAP_CUSTOMERS,
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Dispatch between the Customer List screen and the Customer Detail screen.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! JN_Booking_Roles::user_can_manage_customers() ) {
			wp_die( 'Bạn không có quyền truy cập danh mục khách hàng.', 'Từ chối truy cập', array( 'response' => 403 ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action       = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';
		$customer_key = isset( $_GET['customer_key'] ) ? sanitize_text_field( wp_unslash( $_GET['customer_key'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( 'view' === $action && '' !== $customer_key ) {
			self::render_detail_screen( $customer_key );
			return;
		}

		self::render_list_screen();
	}

	/**
	 * Render the "Khách hàng" list table, KPI summary, search, filters, and CSV export button.
	 *
	 * @return void
	 */
	private static function render_list_screen(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$search      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$filter_type = isset( $_GET['customer_type'] ) ? sanitize_key( wp_unslash( $_GET['customer_type'] ) ) : '';
		$filter_tag  = isset( $_GET['customer_tag'] ) ? sanitize_text_field( wp_unslash( $_GET['customer_tag'] ) ) : '';
		$has_pending = isset( $_GET['has_pending_booking'] ) && '1' === (string) wp_unslash( $_GET['has_pending_booking'] );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$all_customers = JN_Customer_Service::query_customers( array() );
		$customers     = JN_Customer_Service::query_customers(
			array(
				'search'              => $search,
				'type'                => $filter_type,
				'tag'                 => $filter_tag,
				'has_pending_booking' => $has_pending ? '1' : '',
			)
		);

		$types         = JN_Customer_Profile::get_customer_types();
		$distinct_tags = JN_Customer_Service::get_all_distinct_tags();

		$total_count   = count( $all_customers );
		$account_count = 0;
		$guest_count   = 0;
		$pending_count = 0;
		$dup_count     = 0;

		foreach ( $all_customers as $c ) {
			if ( ! empty( $c['is_guest'] ) ) {
				++$guest_count;
			} else {
				++$account_count;
			}
			if ( ! empty( $c['has_pending_booking'] ) ) {
				++$pending_count;
			}
			if ( ! empty( $c['duplicates'] ) ) {
				++$dup_count;
			}
		}

		$export_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'              => 'jn_export_customers_csv',
					's'                   => $search,
					'customer_type'       => $filter_type,
					'customer_tag'        => $filter_tag,
					'has_pending_booking' => $has_pending ? '1' : '',
				),
				admin_url( 'admin-post.php' )
			),
			'jn_export_customers_csv'
		);
		?>
		<div class="wrap jn-crm-wrap">
			<div class="jn-crm-header">
				<div>
					<h1 class="wp-heading-inline">Khách Hàng (CRM Hợp Nhất)</h1>
					<p class="description">
						Quản lý tập trung toàn bộ học viên &amp; khách hàng từ Lịch hẹn Coaching, Đơn hàng WooCommerce và Tài khoản đăng ký.
					</p>
				</div>
				<div class="jn-crm-header-actions">
					<?php if ( JN_Booking_Roles::user_can_export_csv() ) : ?>
						<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary">
							Xuất CSV Khách hàng (Excel UTF-8)
						</a>
					<?php endif; ?>
				</div>
			</div>

			<!-- KPI Summary Strip -->
			<div class="jn-crm-kpi-grid">
				<div class="jn-crm-kpi-card">
					<span class="jn-crm-kpi-label">Tổng khách hàng</span>
					<strong class="jn-crm-kpi-value"><?php echo esc_html( number_format_i18n( $total_count ) ); ?></strong>
				</div>
				<div class="jn-crm-kpi-card">
					<span class="jn-crm-kpi-label">Đã có tài khoản</span>
					<strong class="jn-crm-kpi-value"><?php echo esc_html( number_format_i18n( $account_count ) ); ?></strong>
				</div>
				<div class="jn-crm-kpi-card">
					<span class="jn-crm-kpi-label">Khách vãng lai</span>
					<strong class="jn-crm-kpi-value"><?php echo esc_html( number_format_i18n( $guest_count ) ); ?></strong>
				</div>
				<div class="jn-crm-kpi-card">
					<span class="jn-crm-kpi-label">Có lịch chờ xác nhận</span>
					<strong class="jn-crm-kpi-value jn-crm-kpi-value--amber"><?php echo esc_html( number_format_i18n( $pending_count ) ); ?></strong>
				</div>
				<div class="jn-crm-kpi-card">
					<span class="jn-crm-kpi-label">Cảnh báo trùng SĐT/Email</span>
					<strong class="jn-crm-kpi-value <?php echo $dup_count > 0 ? 'jn-crm-kpi-value--danger' : ''; ?>"><?php echo esc_html( number_format_i18n( $dup_count ) ); ?></strong>
				</div>
			</div>

			<!-- Filter & Search Bar -->
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="jn-crm-filter-bar">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>" />

				<div class="jn-crm-filter-field jn-crm-filter-field--search">
					<label for="jn-customer-search-input" class="screen-reader-text">Tìm kiếm khách hàng</label>
					<input
						type="search"
						id="jn-customer-search-input"
						name="s"
						value="<?php echo esc_attr( $search ); ?>"
						placeholder="Tìm tên, email hoặc SĐT (0908... / +84908...)"
						class="regular-text"
					/>
				</div>

				<div class="jn-crm-filter-field">
					<label for="jn-filter-customer-type" class="screen-reader-text">Loại khách hàng</label>
					<select id="jn-filter-customer-type" name="customer_type">
						<option value="">— Tất cả loại khách —</option>
						<?php foreach ( $types as $tkey => $tlabel ) : ?>
							<option value="<?php echo esc_attr( $tkey ); ?>" <?php selected( $tkey, $filter_type ); ?>>
								<?php echo esc_html( $tlabel ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="jn-crm-filter-field">
					<label for="jn-filter-customer-tag" class="screen-reader-text">Lọc theo Tag</label>
					<select id="jn-filter-customer-tag" name="customer_tag">
						<option value="">— Tất cả Tag —</option>
						<?php foreach ( $distinct_tags as $tag_item ) : ?>
							<option value="<?php echo esc_attr( $tag_item ); ?>" <?php selected( $tag_item, $filter_tag ); ?>>
								Tag: <?php echo esc_html( $tag_item ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="jn-crm-filter-field jn-crm-filter-field--checkbox">
					<label>
						<input type="checkbox" name="has_pending_booking" value="1" <?php checked( $has_pending ); ?> />
						Có lịch chờ xác nhận
					</label>
				</div>

				<div class="jn-crm-filter-actions">
					<button type="submit" id="jn-customer-filter-submit" class="button button-primary">Lọc &amp; Tìm kiếm</button>
					<?php if ( '' !== $search || '' !== $filter_type || '' !== $filter_tag || $has_pending ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ); ?>" class="button">Xóa bộ lọc</a>
					<?php endif; ?>
				</div>
			</form>

			<!-- Customers Table -->
			<table class="wp-list-table widefat fixed striped jn-customers-table">
				<thead>
					<tr>
						<th scope="col" style="width:18%;">Khách hàng</th>
						<th scope="col" style="width:12%;">SĐT / Zalo</th>
						<th scope="col" style="width:15%;">Email</th>
						<th scope="col" style="width:10%;">Loại khách</th>
						<th scope="col" style="width:8%;">Lịch hẹn</th>
						<th scope="col" style="width:7%;">Đơn hàng</th>
						<th scope="col" style="width:10%;">Tổng chi tiêu</th>
						<th scope="col" style="width:11%;">Lịch hẹn gần nhất</th>
						<th scope="col" style="width:9%;">Tags</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $customers ) ) : ?>
						<tr>
							<td colspan="9">Không tìm thấy khách hàng nào khớp với điều kiện lọc hiện tại.</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $customers as $cust ) : ?>
							<?php
							$ckey       = (string) $cust['customer_key'];
							$detail_url = add_query_arg(
								array(
									'page'         => self::MENU_SLUG,
									'action'       => 'view',
									'customer_key' => rawurlencode( $ckey ),
								),
								admin_url( 'admin.php' )
							);
							$book_url   = $detail_url . '#tao-lich-hen';
							$last_b     = $cust['last_booking'];
							$has_dups   = ! empty( $cust['duplicates'] );
							?>
							<tr>
								<td>
									<strong>
										<a class="row-title" href="<?php echo esc_url( $detail_url ); ?>">
											<?php echo esc_html( '' !== $cust['full_name'] ? (string) $cust['full_name'] : '(Chưa rõ tên)' ); ?>
										</a>
									</strong>
									<?php if ( ! empty( $cust['is_guest'] ) ) : ?>
										<span class="jn-crm-badge jn-crm-badge--guest">Khách vãng lai</span>
									<?php else : ?>
										<span class="jn-crm-badge jn-crm-badge--user">TK #<?php echo esc_html( (string) $cust['user_id'] ); ?></span>
									<?php endif; ?>
									<?php if ( $has_dups ) : ?>
										<br><span class="jn-crm-badge jn-crm-badge--dup">Cảnh báo trùng SĐT/Email</span>
									<?php endif; ?>
									<?php if ( '' !== $cust['business_name'] || '' !== $cust['province_city'] ) : ?>
										<div class="jn-crm-sub">
											<?php
											$sub_parts = array_filter( array( (string) $cust['business_name'], (string) $cust['province_city'] ) );
											echo esc_html( implode( ' • ', $sub_parts ) );
											?>
										</div>
									<?php endif; ?>
									<div class="row-actions">
										<span class="view"><a href="<?php echo esc_url( $detail_url ); ?>">Chi tiết hồ sơ</a> | </span>
										<span class="book"><a href="<?php echo esc_url( $book_url ); ?>">Tạo lịch hẹn</a></span>
									</div>
								</td>

								<td>
									<?php if ( '' !== $cust['phone'] ) : ?>
										<code><?php echo esc_html( (string) $cust['phone'] ); ?></code>
										<?php if ( '' !== $cust['phone_local'] && $cust['phone_local'] !== $cust['phone'] ) : ?>
											<div class="jn-crm-sub"><?php echo esc_html( (string) $cust['phone_local'] ); ?></div>
										<?php endif; ?>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>

								<td>
									<?php if ( '' !== $cust['email'] ) : ?>
										<a href="mailto:<?php echo esc_attr( (string) $cust['email'] ); ?>"><?php echo esc_html( (string) $cust['email'] ); ?></a>
									<?php else : ?>
										—
									<?php endif; ?>
								</td>

								<td>
									<?php if ( '' !== $cust['customer_type'] ) : ?>
										<span class="jn-crm-type-pill jn-crm-type-pill--<?php echo esc_attr( (string) $cust['customer_type'] ); ?>">
											<?php echo esc_html( (string) $cust['customer_type_label'] ); ?>
										</span>
									<?php else : ?>
										<span class="jn-crm-sub">Chưa phân loại</span>
									<?php endif; ?>
								</td>

								<td>
									<strong><?php echo esc_html( (string) $cust['bookings_count'] ); ?></strong>
									<?php if ( ! empty( $cust['pending_bookings_count'] ) ) : ?>
										<br><span class="jn-crm-badge jn-crm-badge--pending"><?php echo esc_html( $cust['pending_bookings_count'] . ' chờ duyệt' ); ?></span>
									<?php endif; ?>
								</td>

								<td>
									<strong><?php echo esc_html( (string) $cust['orders_count'] ); ?></strong>
								</td>

								<td>
									<?php echo wp_kses_post( wc_price( (float) $cust['total_spent'] ) ); ?>
								</td>

								<td>
									<?php if ( is_array( $last_b ) ) : ?>
										<a href="<?php echo esc_url( (string) $last_b['edit_url'] ); ?>"><strong><?php echo esc_html( (string) $last_b['booking_code'] ); ?></strong></a>
										<div class="jn-crm-sub"><?php echo esc_html( (string) $last_b['submitted_at'] ); ?></div>
										<span class="jn-status-pill jn-status-pill--<?php echo esc_attr( (string) $last_b['status'] ); ?>">
											<?php echo esc_html( (string) $last_b['status_label'] ); ?>
										</span>
									<?php else : ?>
										<span class="jn-crm-sub">Chưa có lịch hẹn</span>
									<?php endif; ?>
								</td>

								<td>
									<?php if ( ! empty( $cust['tags'] ) ) : ?>
										<div class="jn-crm-tag-list">
											<?php foreach ( (array) $cust['tags'] as $t ) : ?>
												<span class="jn-crm-tag"><?php echo esc_html( (string) $t ); ?></span>
											<?php endforeach; ?>
										</div>
									<?php else : ?>
										<span class="jn-crm-sub">—</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render the Customer Detail screen:
	 * - Duplicate warning banner (if phone or email matches another customer)
	 * - CRM Profile editor (synced with WooCommerce billing)
	 * - "Tạo lịch hẹn" walk-in / phone booking creation card
	 * - Booking History table with links
	 * - Order History table with links
	 * - Staff-only Internal Notes timeline
	 * - Read-only Consent Log
	 *
	 * @param string $customer_key Customer key (`user:123` or `guest:...`).
	 * @return void
	 */
	private static function render_detail_screen( string $customer_key ): void {
		$detail = JN_Customer_Service::get_customer_detail( $customer_key );
		if ( null === $detail ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>Không tìm thấy hồ sơ khách hàng được yêu cầu.</p></div>';
			echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ) . '" class="button">&larr; Quay lại danh sách khách hàng</a></p></div>';
			return;
		}

		$types      = JN_Customer_Profile::get_customer_types();
		$sources    = JN_Customer_Profile::get_source_types();
		$packages   = JN_Coaching_Packages::get_bookable_packages_for_form();
		$formats    = (array) JN_Booking_Settings::get( 'formats', array() );
		$windows    = (array) JN_Booking_Settings::get( 'time_windows', array() );
		$duplicates = (array) $detail['duplicates'];
		$back_url   = admin_url( 'admin.php?page=' . self::MENU_SLUG );
		?>
		<div class="wrap jn-crm-wrap">
			<p style="margin-bottom:8px;">
				<a href="<?php echo esc_url( $back_url ); ?>" class="button button-small">&larr; Danh sách Khách hàng</a>
			</p>

			<div class="jn-crm-header">
				<div>
					<h1>
						<?php echo esc_html( '' !== $detail['full_name'] ? (string) $detail['full_name'] : 'Hồ sơ Khách hàng' ); ?>
						<?php if ( ! empty( $detail['is_guest'] ) ) : ?>
							<span class="jn-crm-badge jn-crm-badge--guest">Khách vãng lai</span>
						<?php else : ?>
							<span class="jn-crm-badge jn-crm-badge--user">Tài khoản #<?php echo esc_html( (string) $detail['user_id'] ); ?></span>
						<?php endif; ?>
					</h1>
					<p class="description">
						SĐT / Zalo: <code><?php echo esc_html( '' !== $detail['phone'] ? (string) $detail['phone'] : '—' ); ?></code>
						&nbsp;|&nbsp;
						Email: <strong><?php echo esc_html( '' !== $detail['email'] ? (string) $detail['email'] : '—' ); ?></strong>
						&nbsp;|&nbsp;
						Tổng chi tiêu: <strong><?php echo wp_kses_post( wc_price( (float) $detail['total_spent'] ) ); ?></strong>
					</p>
				</div>
				<div class="jn-crm-header-actions">
					<a href="#tao-lich-hen" class="button button-primary">+ Tạo Lịch Hẹn (Điện thoại / Walk-in)</a>
				</div>
			</div>

			<?php if ( ! empty( $duplicates ) ) : ?>
				<div class="jn-crm-dup-banner" role="alert">
					<strong>CẢNH BÁO TRÙNG THÔNG TIN KHÁCH HÀNG:</strong>
					Số điện thoại hoặc địa chỉ email của hồ sơ này đang trùng với <?php echo esc_html( (string) count( $duplicates ) ); ?> hồ sơ khác trong hệ thống (đã ghi nhật ký kiểm toán):
					<ul style="margin:6px 0 0 18px;">
						<?php foreach ( $duplicates as $dup ) : ?>
							<?php
							$dup_url = add_query_arg(
								array(
									'page'         => self::MENU_SLUG,
									'action'       => 'view',
									'customer_key' => rawurlencode( (string) $dup['customer_key'] ),
								),
								admin_url( 'admin.php' )
							);
							?>
							<li>
								Trùng <strong><?php echo 'phone' === $dup['matched_field'] ? 'Số điện thoại' : 'Email'; ?></strong> với
								<a href="<?php echo esc_url( $dup_url ); ?>">
									<strong><?php echo esc_html( (string) $dup['name'] ); ?></strong> (<code><?php echo esc_html( (string) $dup['customer_key'] ); ?></code>)
								</a>
								— SĐT: <code><?php echo esc_html( (string) $dup['phone'] ); ?></code>, Email: <code><?php echo esc_html( (string) $dup['email'] ); ?></code>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<div class="jn-crm-detail-grid">
				<!-- Left Column: Profile Form + Read-only Consent Log -->
				<div class="jn-crm-col-main">
					<div class="jn-crm-card">
						<h2>1. Hồ Sơ Khách Hàng &amp; Phân Loại CRM</h2>
						<p class="description">
							Mọi thay đổi đối với Họ tên, SĐT/Zalo, Email, Tên cơ sở và Tỉnh/Thành đều được đồng bộ tự động vào hồ sơ thanh toán WooCommerce.
						</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="jn_save_customer_profile" />
							<input type="hidden" name="customer_key" value="<?php echo esc_attr( (string) $detail['customer_key'] ); ?>" />
							<?php wp_nonce_field( 'jn_save_customer_profile_' . $detail['customer_key'], 'jn_customer_profile_nonce' ); ?>

							<table class="form-table" role="presentation">
								<tr>
									<th scope="row"><label for="jn-det-full-name">Họ và tên *</label></th>
									<td>
										<input type="text" id="jn-det-full-name" name="full_name" value="<?php echo esc_attr( (string) $detail['full_name'] ); ?>" class="regular-text" required />
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="jn-det-phone">SĐT / Zalo (+84…)</label></th>
									<td>
										<input type="tel" id="jn-det-phone" name="phone" value="<?php echo esc_attr( (string) $detail['phone'] ); ?>" class="regular-text" placeholder="+84908123456" />
										<p class="description">Chuẩn hóa tự động về <code>+84...</code>.</p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="jn-det-email">Email</label></th>
									<td>
										<input type="email" id="jn-det-email" name="email" value="<?php echo esc_attr( (string) $detail['email'] ); ?>" class="regular-text" />
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="jn-det-type">Loại khách</label></th>
									<td>
										<select id="jn-det-type" name="customer_type">
											<option value="">— Chưa phân loại —</option>
											<?php foreach ( $types as $tkey => $tlabel ) : ?>
												<option value="<?php echo esc_attr( $tkey ); ?>" <?php selected( $tkey, (string) $detail['customer_type'] ); ?>>
													<?php echo esc_html( $tlabel ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="jn-det-business">Tên cơ sở</label></th>
									<td>
										<input type="text" id="jn-det-business" name="business_name" value="<?php echo esc_attr( (string) $detail['business_name'] ); ?>" class="regular-text" placeholder="Ví dụ: Aura Luxury Spa" />
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="jn-det-city">Tỉnh / Thành</label></th>
									<td>
										<input type="text" id="jn-det-city" name="province_city" value="<?php echo esc_attr( (string) $detail['province_city'] ); ?>" class="regular-text" placeholder="TP. Hồ Chí Minh" />
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="jn-det-source">Nguồn khách hàng</label></th>
									<td>
										<select id="jn-det-source" name="source_type">
											<?php foreach ( $sources as $skey => $slabel ) : ?>
												<option value="<?php echo esc_attr( $skey ); ?>" <?php selected( $skey, (string) $detail['source_type'] ); ?>>
													<?php echo esc_html( $slabel ); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<p style="margin-top:6px;">
											<input type="text" name="source_detail" value="<?php echo esc_attr( (string) $detail['source_detail'] ); ?>" class="regular-text" placeholder="Chi tiết UTM / Người giới thiệu / URL" />
										</p>
									</td>
								</tr>
								<tr>
									<th scope="row"><label for="jn-det-tags">Tags</label></th>
									<td>
										<input type="text" id="jn-det-tags" name="tags" value="<?php echo esc_attr( implode( ', ', (array) $detail['tags'] ) ); ?>" class="regular-text" placeholder="VIP, Mentorship, Chủ Spa" />
										<p class="description">Phân cách nhiều tag bằng dấu phẩy.</p>
									</td>
								</tr>
							</table>

							<?php submit_button( 'Lưu Hồ Sơ Khách Hàng', 'primary', 'submit', false ); ?>
						</form>
					</div>

					<!-- Booking History Card -->
					<div class="jn-crm-card">
						<h2>2. Lịch Sử Đặt Lịch Coaching (<?php echo esc_html( (string) $detail['bookings_count'] ); ?>)</h2>
						<?php if ( empty( $detail['bookings'] ) ) : ?>
							<p class="description">Khách hàng này chưa có lịch hẹn nào.</p>
						<?php else : ?>
							<table class="widefat striped">
								<thead>
									<tr>
										<th>Mã lịch</th>
										<th>Gói &amp; Hình thức</th>
										<th>Lịch xác nhận</th>
										<th>Trạng thái</th>
										<th>Thanh toán / Đơn</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( (array) $detail['bookings'] as $b ) : ?>
										<tr>
											<td>
												<a href="<?php echo esc_url( (string) $b['edit_url'] ); ?>"><strong><?php echo esc_html( (string) $b['booking_code'] ); ?></strong></a>
												<div class="jn-crm-sub"><?php echo esc_html( (string) $b['submitted_at'] ); ?></div>
											</td>
											<td>
												<?php echo esc_html( (string) $b['package_title'] ); ?>
												<div class="jn-crm-sub"><?php echo esc_html( (string) $b['format_label'] ); ?></div>
											</td>
											<td>
												<?php if ( '' !== $b['confirmed_start'] ) : ?>
													<strong><?php echo esc_html( (string) $b['confirmed_start'] ); ?></strong>
												<?php else : ?>
													<span class="jn-crm-sub">Khung mong muốn: <?php echo esc_html( (string) $b['preferred_window'] ); ?></span>
												<?php endif; ?>
											</td>
											<td>
												<span class="jn-status-pill jn-status-pill--<?php echo esc_attr( (string) $b['status'] ); ?>">
													<?php echo esc_html( (string) $b['status_label'] ); ?>
												</span>
											</td>
											<td>
												<span class="jn-pay-pill jn-pay-pill--<?php echo esc_attr( (string) $b['payment_state'] ); ?>">
													<?php echo esc_html( (string) $b['payment_label'] ); ?>
												</span>
												<?php if ( ! empty( $b['order_id'] ) ) : ?>
													<br><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-orders&action=edit&id=' . (int) $b['order_id'] ) ); ?>" class="jn-crm-sub">Đơn #<?php echo esc_html( (string) $b['order_id'] ); ?></a>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>

					<!-- Order History Card -->
					<div class="jn-crm-card">
						<h2>3. Lịch Sử Đơn Hàng WooCommerce HPOS (<?php echo esc_html( (string) $detail['orders_count'] ); ?>)</h2>
						<?php if ( empty( $detail['orders'] ) ) : ?>
							<p class="description">Khách hàng này chưa có đơn hàng nào.</p>
						<?php else : ?>
							<table class="widefat striped">
								<thead>
									<tr>
										<th>Mã đơn</th>
										<th>Ngày tạo</th>
										<th>Sản phẩm / Gói dịch vụ</th>
										<th>Tổng tiền</th>
										<th>Trạng thái</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( (array) $detail['orders'] as $ord ) : ?>
										<tr>
											<td>
												<a href="<?php echo esc_url( (string) $ord['edit_url'] ); ?>"><strong>#<?php echo esc_html( (string) $ord['order_number'] ); ?></strong></a>
											</td>
											<td><?php echo esc_html( (string) $ord['created_at'] ); ?></td>
											<td><?php echo esc_html( (string) $ord['items_label'] ); ?></td>
											<td><strong><?php echo wp_kses_post( wc_price( (float) $ord['total'] ) ); ?></strong></td>
											<td><?php echo esc_html( (string) $ord['status_label'] ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>

					<!-- Read-only Consent Log Card -->
					<div class="jn-crm-card">
						<h2>4. Nhật Ký Đồng Ý Chính Sách Bảo Mật (Consent Log — Chỉ Đọc)</h2>
						<?php if ( empty( $detail['consent_log'] ) ) : ?>
							<p class="description">Chưa có bản ghi đồng ý chính sách bảo mật nào.</p>
						<?php else : ?>
							<table class="widefat striped">
								<thead>
									<tr>
										<th>Thời điểm (ISO 8601)</th>
										<th>Nguồn ghi nhận</th>
										<th>Phiên bản CSBM</th>
										<th>Mã tham chiếu</th>
										<th>Nội dung đồng ý</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( (array) $detail['consent_log'] as $clog ) : ?>
										<tr>
											<td><code><?php echo esc_html( (string) ( $clog['timestamp'] ?? '' ) ); ?></code></td>
											<td><?php echo esc_html( (string) ( $clog['source'] ?? '' ) ); ?></td>
											<td><code><?php echo esc_html( (string) ( $clog['policy_version'] ?? '1.0' ) ); ?></code> (Trang #<?php echo esc_html( (string) ( $clog['policy_page_id'] ?? 15 ) ); ?>)</td>
											<td><code><?php echo esc_html( (string) ( $clog['reference'] ?? '—' ) ); ?></code></td>
											<td><span class="jn-crm-sub"><?php echo esc_html( (string) ( $clog['text_snapshot'] ?? '' ) ); ?></span></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>

				<!-- Right Column: "Tạo lịch hẹn" Action + Staff Internal Notes Timeline -->
				<div class="jn-crm-col-side">
					<!-- Create Booking for Phone / Walk-in Card -->
					<div id="tao-lich-hen" class="jn-crm-card jn-crm-card--accent">
						<h2>Tạo Lịch Hẹn (Điện Thoại / Walk-in)</h2>
						<p class="description">
							Tạo nhanh hồ sơ lịch hẹn cho khách gọi điện hoặc đến trực tiếp Học viện.
						</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="jn_create_staff_booking" />
							<input type="hidden" name="customer_key" value="<?php echo esc_attr( (string) $detail['customer_key'] ); ?>" />
							<input type="hidden" name="user_id" value="<?php echo esc_attr( (string) $detail['user_id'] ); ?>" />
							<input type="hidden" name="full_name" value="<?php echo esc_attr( (string) $detail['full_name'] ); ?>" />
							<input type="hidden" name="phone" value="<?php echo esc_attr( (string) $detail['phone'] ); ?>" />
							<input type="hidden" name="email" value="<?php echo esc_attr( (string) $detail['email'] ); ?>" />
							<?php wp_nonce_field( 'jn_create_staff_booking_' . $detail['customer_key'], 'jn_staff_booking_nonce' ); ?>

							<p>
								<label for="jn-walkin-package"><strong>Gói cố vấn *</strong></label><br />
								<select id="jn-walkin-package" name="package_code" style="width:100%;">
									<?php foreach ( $packages as $pkg ) : ?>
										<option value="<?php echo esc_attr( (string) $pkg['key'] ); ?>" <?php selected( '60-phut', (string) $pkg['key'] ); ?>>
											<?php echo esc_html( (string) $pkg['form_label'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</p>

							<p>
								<label for="jn-walkin-format"><strong>Hình thức gặp *</strong></label><br />
								<select id="jn-walkin-format" name="format" style="width:100%;">
									<?php foreach ( $formats as $fkey => $flabel ) : ?>
										<option value="<?php echo esc_attr( $fkey ); ?>"><?php echo esc_html( (string) $flabel ); ?></option>
									<?php endforeach; ?>
								</select>
							</p>

							<p>
								<label for="jn-walkin-window"><strong>Khung giờ mong muốn</strong></label><br />
								<select id="jn-walkin-window" name="preferred_window" style="width:100%;">
									<?php foreach ( $windows as $wkey => $wlabel ) : ?>
										<option value="<?php echo esc_attr( $wkey ); ?>"><?php echo esc_html( (string) $wlabel ); ?></option>
									<?php endforeach; ?>
								</select>
							</p>

							<p>
								<label for="jn-walkin-goals"><strong>Mục tiêu / Yêu cầu của khách</strong></label><br />
								<textarea id="jn-walkin-goals" name="goals" rows="2" style="width:100%;" placeholder="Ghi nhận nội dung khách trao đổi qua điện thoại/trực tiếp..."></textarea>
							</p>

							<hr />
							<p>
								<label>
									<input type="checkbox" name="confirm_now" value="yes" />
									<strong>Xác nhận khung giờ hẹn ngay lập tức</strong> (tự động tạo đơn HPOS <code>pending</code> &amp; gửi email <code>.ics</code>)
								</label>
							</p>

							<p>
								<label for="jn-walkin-start">Ngày giờ hẹn xác nhận (GMT+7):</label><br />
								<input type="datetime-local" id="jn-walkin-start" name="confirmed_start" style="width:100%;" />
							</p>

							<p>
								<label for="jn-walkin-link">Link phòng họp (nếu Online) / Địa điểm:</label><br />
								<input type="url" id="jn-walkin-link" name="meeting_link" style="width:100%;" placeholder="https://zoom.us/j/..." />
							</p>

							<p>
								<button type="submit" class="button button-primary" style="width:100%;">Tạo Lịch Hẹn Cho Khách Hàng Này</button>
							</p>
						</form>
					</div>

					<!-- Internal Notes Timeline Card (Staff Only) -->
					<div class="jn-crm-card">
						<h2>Ghi Chú Nội Bộ (Chỉ Nhân Sự)</h2>
						<p class="description">
							Chỉ nhân sự quản trị, quản lý và trợ lý nhìn thấy; tuyệt đối không hiển thị cho khách hàng.
						</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:16px;">
							<input type="hidden" name="action" value="jn_add_customer_note" />
							<input type="hidden" name="customer_key" value="<?php echo esc_attr( (string) $detail['customer_key'] ); ?>" />
							<?php wp_nonce_field( 'jn_add_customer_note_' . $detail['customer_key'], 'jn_customer_note_nonce' ); ?>

							<p style="margin-bottom:8px;">
								<label for="jn-new-internal-note" class="screen-reader-text">Thêm ghi chú nội bộ</label>
								<textarea id="jn-new-internal-note" name="note_content" rows="3" style="width:100%;" placeholder="Nhập ghi chú chăm sóc khách hàng, lịch sử gọi điện, lưu ý đặc biệt..." required></textarea>
							</p>
							<button type="submit" class="button button-secondary">Thêm Ghi Chú Nội Bộ</button>
						</form>

						<?php if ( empty( $detail['internal_notes'] ) ) : ?>
							<p class="description">Chưa có ghi chú nội bộ nào.</p>
						<?php else : ?>
							<ul class="jn-crm-notes-timeline">
								<?php foreach ( (array) $detail['internal_notes'] as $note ) : ?>
									<li class="jn-crm-note-item">
										<div class="jn-crm-note-meta">
											<strong><?php echo esc_html( (string) ( $note['author_name'] ?? 'Nhân sự' ) ); ?></strong>
											<span><?php echo esc_html( (string) ( $note['timestamp'] ?? '' ) ); ?></span>
										</div>
										<div class="jn-crm-note-body">
											<?php echo nl2br( esc_html( (string) ( $note['content'] ?? '' ) ) ); ?>
										</div>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle saving customer CRM profile from the Customer Detail screen.
	 *
	 * @return void
	 */
	public static function handle_save_customer_profile(): void {
		if ( ! JN_Booking_Roles::user_can_manage_customers() ) {
			wp_die( 'Bạn không có quyền chỉnh sửa hồ sơ khách hàng.', 'Từ chối truy cập', array( 'response' => 403 ) );
		}

		$customer_key = isset( $_POST['customer_key'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_key'] ) ) : '';
		check_admin_referer( 'jn_save_customer_profile_' . $customer_key, 'jn_customer_profile_nonce' );

		$payload = array(
			'full_name'     => isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '',
			'phone'         => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
			'email'         => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'customer_type' => isset( $_POST['customer_type'] ) ? sanitize_key( wp_unslash( $_POST['customer_type'] ) ) : '',
			'business_name' => isset( $_POST['business_name'] ) ? sanitize_text_field( wp_unslash( $_POST['business_name'] ) ) : '',
			'province_city' => isset( $_POST['province_city'] ) ? sanitize_text_field( wp_unslash( $_POST['province_city'] ) ) : '',
			'source_type'   => isset( $_POST['source_type'] ) ? sanitize_key( wp_unslash( $_POST['source_type'] ) ) : JN_Customer_Profile::SOURCE_FORM,
			'source_detail' => isset( $_POST['source_detail'] ) ? sanitize_text_field( wp_unslash( $_POST['source_detail'] ) ) : '',
			'tags'          => isset( $_POST['tags'] ) ? sanitize_text_field( wp_unslash( $_POST['tags'] ) ) : '',
		);

		if ( 0 === strpos( $customer_key, 'user:' ) ) {
			$user_id = (int) substr( $customer_key, 5 );
			$res     = JN_Customer_Profile::update_user_profile( $user_id, $payload );
		} else {
			$res = JN_Customer_Profile::update_guest_profile( $customer_key, $payload );
		}

		$uid = get_current_user_id();
		if ( is_wp_error( $res ) ) {
			set_transient( 'jn_crm_admin_err_' . $uid, $res->get_error_message(), 60 );
		} else {
			set_transient( 'jn_crm_admin_ok_' . $uid, 'Đã cập nhật hồ sơ khách hàng và đồng bộ dữ liệu WooCommerce.', 60 );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => self::MENU_SLUG,
					'action'       => 'view',
					'customer_key' => rawurlencode( $customer_key ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle adding a staff-only internal note to a customer timeline.
	 *
	 * @return void
	 */
	public static function handle_add_customer_note(): void {
		if ( ! JN_Booking_Roles::user_can_manage_customers() ) {
			wp_die( 'Bạn không có quyền thêm ghi chú nội bộ.', 'Từ chối truy cập', array( 'response' => 403 ) );
		}

		$customer_key = isset( $_POST['customer_key'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_key'] ) ) : '';
		check_admin_referer( 'jn_add_customer_note_' . $customer_key, 'jn_customer_note_nonce' );

		$content = isset( $_POST['note_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note_content'] ) ) : '';
		$res     = JN_Customer_Profile::add_internal_note( $customer_key, $content, get_current_user_id() );

		$uid = get_current_user_id();
		if ( is_wp_error( $res ) ) {
			set_transient( 'jn_crm_admin_err_' . $uid, $res->get_error_message(), 60 );
		} else {
			set_transient( 'jn_crm_admin_ok_' . $uid, 'Đã thêm ghi chú nội bộ vào dòng thời gian của khách hàng.', 60 );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => self::MENU_SLUG,
					'action'       => 'view',
					'customer_key' => rawurlencode( $customer_key ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle creating a phone / walk-in booking from the Customer Detail screen.
	 *
	 * @return void
	 */
	public static function handle_create_staff_booking(): void {
		if ( ! JN_Booking_Roles::user_can_manage_bookings() ) {
			wp_die( 'Bạn không có quyền tạo lịch hẹn.', 'Từ chối truy cập', array( 'response' => 403 ) );
		}

		$customer_key = isset( $_POST['customer_key'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_key'] ) ) : '';
		check_admin_referer( 'jn_create_staff_booking_' . $customer_key, 'jn_staff_booking_nonce' );

		$payload = array(
			'user_id'          => isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0,
			'full_name'        => isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '',
			'phone'            => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
			'email'            => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'package_code'     => isset( $_POST['package_code'] ) ? sanitize_text_field( wp_unslash( $_POST['package_code'] ) ) : '60-phut',
			'format'           => isset( $_POST['format'] ) ? sanitize_key( wp_unslash( $_POST['format'] ) ) : 'online',
			'preferred_window' => isset( $_POST['preferred_window'] ) ? sanitize_key( wp_unslash( $_POST['preferred_window'] ) ) : 'morning',
			'goals'            => isset( $_POST['goals'] ) ? sanitize_textarea_field( wp_unslash( $_POST['goals'] ) ) : '',
			'confirm_now'      => isset( $_POST['confirm_now'] ) ? sanitize_key( wp_unslash( $_POST['confirm_now'] ) ) : 'no',
			'confirmed_start'  => isset( $_POST['confirmed_start'] ) ? sanitize_text_field( wp_unslash( $_POST['confirmed_start'] ) ) : '',
			'meeting_link'     => isset( $_POST['meeting_link'] ) ? esc_url_raw( wp_unslash( $_POST['meeting_link'] ) ) : '',
			'source_type'      => JN_Customer_Profile::SOURCE_WALK_IN,
		);

		$res = JN_Customer_Service::create_staff_booking( $payload, get_current_user_id() );
		$uid = get_current_user_id();

		if ( is_wp_error( $res ) ) {
			set_transient( 'jn_crm_admin_err_' . $uid, $res->get_error_message(), 60 );
		} else {
			set_transient(
				'jn_crm_admin_ok_' . $uid,
				sprintf( 'Đã tạo lịch hẹn %s cho khách hàng thành công.', $res['booking_code'] ),
				60
			);
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => self::MENU_SLUG,
					'action'       => 'view',
					'customer_key' => rawurlencode( $customer_key ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Display admin feedback notices on the "Khách hàng" screen.
	 *
	 * @return void
	 */
	public static function render_admin_notices(): void {
		$uid = get_current_user_id();
		$err = get_transient( 'jn_crm_admin_err_' . $uid );
		if ( false !== $err && '' !== $err ) {
			delete_transient( 'jn_crm_admin_err_' . $uid );
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( (string) $err ) . '</p></div>';
		}

		$ok = get_transient( 'jn_crm_admin_ok_' . $uid );
		if ( false !== $ok && '' !== $ok ) {
			delete_transient( 'jn_crm_admin_ok_' . $uid );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( (string) $ok ) . '</p></div>';
		}
	}

	/**
	 * Print scoped admin CSS for the "Khách hàng" list and detail screens.
	 *
	 * @return void
	 */
	public static function print_admin_styles(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( self::MENU_SLUG !== $page ) {
			return;
		}
		?>
		<style>
			.jn-crm-header {
				display: flex;
				flex-wrap: wrap;
				justify-content: space-between;
				align-items: center;
				gap: 16px;
				margin-bottom: 16px;
			}
			.jn-crm-kpi-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
				gap: 12px;
				margin-bottom: 16px;
			}
			.jn-crm-kpi-card {
				background: #FFFFFF;
				border: 1px solid #DCDCDE;
				border-radius: 8px;
				padding: 12px 16px;
			}
			.jn-crm-kpi-label {
				display: block;
				font-size: 12px;
				color: #646970;
				margin-bottom: 4px;
			}
			.jn-crm-kpi-value {
				font-size: 22px;
				color: #1F1E1D;
			}
			.jn-crm-kpi-value--amber { color: #B45309; }
			.jn-crm-kpi-value--danger { color: #B91C1C; }

			.jn-crm-filter-bar {
				display: flex;
				flex-wrap: wrap;
				align-items: center;
				gap: 10px;
				background: #FFFFFF;
				border: 1px solid #DCDCDE;
				border-radius: 8px;
				padding: 12px 16px;
				margin-bottom: 16px;
			}
			.jn-crm-filter-field--search input {
				min-width: 280px;
			}
			.jn-crm-badge, .jn-crm-type-pill, .jn-status-pill, .jn-pay-pill {
				display: inline-block;
				padding: 2px 8px;
				border-radius: 9999px;
				font-size: 11px;
				font-weight: 600;
				line-height: 1.4;
			}
			.jn-crm-badge--user { background: #E8F0FE; color: #1967D2; }
			.jn-crm-badge--guest { background: #F1F3F4; color: #5F6368; }
			.jn-crm-badge--pending { background: #FFF4E5; color: #8A5300; border: 1px solid #F5D5A3; }
			.jn-crm-badge--dup { background: #FCE8E6; color: #B31412; border: 1px solid #F28B82; }

			.jn-crm-type-pill--ca_nhan { background: #F4F0EA; color: #4A4640; }
			.jn-crm-type-pill--tho_hoc_vien { background: #E6F4EA; color: #137333; }
			.jn-crm-type-pill--chu_co_so { background: #1F1E1D; color: #FAF8F5; }

			.jn-status-pill--jn_pending { background: #FFF4E5; color: #8A5300; }
			.jn-status-pill--jn_confirmed { background: #E6F4EA; color: #137333; }
			.jn-status-pill--jn_completed { background: #1F1E1D; color: #FAF8F5; }
			.jn-status-pill--jn_no_show { background: #FCE8E6; color: #C5221F; }
			.jn-status-pill--jn_cancelled { background: #F1F3F4; color: #5F6368; }

			.jn-pay-pill--unbilled { background: #F1F3F4; color: #5F6368; }
			.jn-pay-pill--pending_payment, .jn-pay-pill--on_hold_verification { background: #FEF7E0; color: #7C5800; }
			.jn-pay-pill--paid { background: #E6F4EA; color: #137333; }
			.jn-pay-pill--paid_refund_flagged { background: #FCE8E6; color: #B31412; }

			.jn-crm-sub { color: #646970; font-size: 12px; }
			.jn-crm-tag-list { display: flex; flex-wrap: wrap; gap: 4px; }
			.jn-crm-tag {
				background: #FAF8F5;
				border: 1px solid #D1C7BB;
				color: #1F1E1D;
				padding: 1px 7px;
				border-radius: 4px;
				font-size: 11px;
			}
			.jn-crm-dup-banner {
				background: #FFFBEB;
				border-left: 4px solid #D97706;
				border-top: 1px solid #FDE68A;
				border-right: 1px solid #FDE68A;
				border-bottom: 1px solid #FDE68A;
				padding: 12px 16px;
				border-radius: 6px;
				margin-bottom: 16px;
				color: #78350F;
			}
			.jn-crm-detail-grid {
				display: grid;
				grid-template-columns: 2fr 1fr;
				gap: 20px;
				align-items: start;
			}
			@media (max-width: 1100px) {
				.jn-crm-detail-grid {
					grid-template-columns: 1fr;
				}
			}
			.jn-crm-card {
				background: #FFFFFF;
				border: 1px solid #DCDCDE;
				border-radius: 8px;
				padding: 18px 20px;
				margin-bottom: 20px;
			}
			.jn-crm-card--accent {
				border-top: 4px solid #7E6754;
				background: #FAF8F5;
			}
			.jn-crm-card h2 {
				margin-top: 0;
				padding-bottom: 10px;
				border-bottom: 1px solid #EFECE6;
				font-size: 16px;
			}
			.jn-crm-notes-timeline {
				list-style: none;
				margin: 0;
				padding: 0;
			}
			.jn-crm-note-item {
				background: #FAF8F5;
				border-left: 3px solid #7E6754;
				padding: 10px 12px;
				margin-bottom: 10px;
				border-radius: 4px;
			}
			.jn-crm-note-meta {
				display: flex;
				justify-content: space-between;
				font-size: 12px;
				color: #646970;
				margin-bottom: 4px;
			}
			.jn-crm-note-body {
				color: #1F1E1D;
				font-size: 13px;
			}
		</style>
		<?php
	}
}
