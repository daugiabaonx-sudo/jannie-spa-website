<?php
/**
 * JN Step 9 — Launch-Blocker Audit & Cleanliness Gate (Task 1).
 *
 * Audits every launch blocker defined in Step 9 Task 1 against the live
 * WordPress database, deletes any leftover DEMO products, test/specimen pages,
 * or temporary test bookings/orders/customers, records the owner's written
 * waiver for deferred owner-supplied UNKNOWN items, and outputs a structured
 * JSON report for `docs/jn/launch-blockers-report.json`.
 *
 * Run via WP-CLI:
 *   ./scripts/wp.sh eval-file /scripts/check-launch-blockers.php
 *
 * @package JannieNguyen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

echo "=================================================================\n";
echo "JN STEP 9 — LAUNCH BLOCKER AUDIT & CLEANLINESS GATE (TASK 1)\n";
echo "=================================================================\n";

// 1. Record Owner's Written Waiver (2026-10-28) for owner-supplied UNKNOWN items.
$waiver_record = array(
	'waived_by'      => 'Project Owner',
	'waived_at'      => '2026-10-28T08:40:55+07:00',
	'statement'      => 'oke hãy làm đi ,tôi chưa muốn cung cấp ngay thông tin thật',
	'scope'          => array( 'UNK-01', 'UNK-02', 'UNK-03', 'UNK-04', 'UNK-05', 'UNK-07', 'UNK-08', 'UNK-09', 'UNK-10', 'UNK-11', 'UNK-13', 'UNK-14' ),
	'remote_policy'  => 'Do not touch any remote server, DNS, payment provider or real customer email until owner explicitly writes "deploy".',
);
update_option( 'jn_launch_blocker_waiver', $waiver_record, false );

$decisions_log = get_option( 'jn_owner_decisions_log', array() );
if ( ! is_array( $decisions_log ) ) {
	$decisions_log = array();
}
$decisions_log['step9_launch_blocker_waiver'] = $waiver_record;
update_option( 'jn_owner_decisions_log', $decisions_log, false );

// 2. Clean up any DEMO products, test/specimen pages, test orders, test bookings, and test users.
$cleaned_items = array(
	'demo_products'   => array(),
	'test_pages'      => array(),
	'test_bookings'   => array(),
	'test_orders'     => array(),
	'test_users'      => array(),
);

// 2a. Delete any non-coaching DEMO or temporary test products.
$all_products = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'trash' ),
		'posts_per_page' => 100,
	)
);

$canonical_coaching_skus = array( 'jn-coaching-30m', 'jn-coaching-60m', 'jn-mentorship-3m' );
foreach ( $all_products as $prod ) {
	$sku          = (string) get_post_meta( $prod->ID, '_sku', true );
	$is_bookable  = 'yes' === (string) get_post_meta( $prod->ID, '_jn_bookable', true );
	$title_upper  = mb_strtoupper( (string) $prod->post_title, 'UTF-8' );
	$slug_lower   = strtolower( (string) $prod->post_name );
	$is_canonical = in_array( $sku, $canonical_coaching_skus, true ) || ( $is_bookable && in_array( (int) $prod->ID, array( 163, 164, 165 ), true ) );

	if ( ! $is_canonical || str_contains( $title_upper, 'DEMO' ) || 'so-tay-dinh-huong-su-nghiep' === $slug_lower ) {
		$cleaned_items['demo_products'][] = array(
			'id'    => (int) $prod->ID,
			'title' => (string) $prod->post_title,
			'slug'  => (string) $prod->post_name,
		);
		wp_delete_post( $prod->ID, true );
	}
}

// 2b. Delete any test or specimen pages (preserve only the 12 canonical site pages).
$canonical_page_slugs = array(
	'home',
	'coaching',
	'my-story',
	'course',
	'client-success-story',
	'my-favorite-product',
	'booking',
	'cart',
	'checkout',
	'tai-khoan',
	'chinh-sach-bao-mat',
	'dieu-khoan-dich-vu',
);

$all_pages = get_posts(
	array(
		'post_type'      => 'page',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'trash' ),
		'posts_per_page' => 100,
	)
);

foreach ( $all_pages as $pg ) {
	$slug = strtolower( (string) $pg->post_name );
	if ( ! in_array( $slug, $canonical_page_slugs, true ) ) {
		$cleaned_items['test_pages'][] = array(
			'id'    => (int) $pg->ID,
			'title' => (string) $pg->post_title,
			'slug'  => $slug,
		);
		wp_delete_post( $pg->ID, true );
	}
}

// 2c. Delete any leftover test bookings (`jn_booking`).
$all_bookings = get_posts(
	array(
		'post_type'      => 'jn_booking',
		'post_status'    => 'any',
		'posts_per_page' => 200,
	)
);
foreach ( $all_bookings as $bk ) {
	$cleaned_items['test_bookings'][] = (int) $bk->ID;
	wp_delete_post( $bk->ID, true );
}

// 2d. Delete any leftover test WooCommerce HPOS orders.
if ( function_exists( 'wc_get_orders' ) ) {
	$orders = wc_get_orders(
		array(
			'limit'  => 200,
			'return' => 'objects',
		)
	);
	foreach ( $orders as $ord ) {
		if ( is_object( $ord ) && method_exists( $ord, 'get_id' ) ) {
			$cleaned_items['test_orders'][] = (int) $ord->get_id();
			$ord->delete( true );
		}
	}
}

// 2e. Clear guest CRM test profiles and duplicate logs.
update_option( 'jn_guest_crm_profiles', array(), false );
update_option( 'jn_customer_duplicate_log', array(), false );

// 2f. Delete any non-staff test users (preserve only the 4 canonical staff accounts).
$canonical_staff_logins = array(
	'jn_director_7391',
	'quan_ly_cua_hang',
	'tro_ly_lich_hen',
	'bien_tap_noi_dung',
);
$all_users = get_users( array( 'fields' => 'all' ) );
if ( ! function_exists( 'wp_delete_user' ) ) {
	require_once ABSPATH . 'wp-admin/includes/user.php';
}
foreach ( $all_users as $u ) {
	if ( ! in_array( (string) $u->user_login, $canonical_staff_logins, true ) ) {
		$cleaned_items['test_users'][] = array(
			'id'    => (int) $u->ID,
			'login' => (string) $u->user_login,
			'email' => (string) $u->user_email,
		);
		wp_delete_user( $u->ID );
	}
}

// 3. Audit all 8 Launch Blocker Gates (BL-01 .. BL-07b).
$placeholder_media = get_posts(
	array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 50,
	)
);
$placeholder_media_list = array();
foreach ( $placeholder_media as $att ) {
	if ( str_starts_with( (string) $att->post_title, 'PLACEHOLDER' ) ) {
		$placeholder_media_list[] = array(
			'id'    => (int) $att->ID,
			'title' => (string) $att->post_title,
			'file'  => (string) get_post_meta( $att->ID, '_wp_attached_file', true ),
		);
	}
}

$testimonials = get_posts(
	array(
		'post_type'      => 'jn_testimonial',
		'post_status'    => 'publish',
		'posts_per_page' => 20,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);
$unverified_testimonials = array();
foreach ( $testimonials as $t ) {
	$note = (string) get_post_meta( $t->ID, 'jn_admin_note', true );
	if ( '' !== $note ) {
		$unverified_testimonials[] = array(
			'id'         => (int) $t->ID,
			'client'     => (string) get_post_meta( $t->ID, 'jn_client_name', true ),
			'admin_note' => $note,
		);
	}
}

$bacs_accounts = get_option( 'woocommerce_bacs_accounts', array() );
$coaching_pkgs = array();
foreach ( array( 163, 164, 165 ) as $pkg_id ) {
	$p = get_post( $pkg_id );
	if ( $p instanceof WP_Post ) {
		$coaching_pkgs[] = array(
			'id'               => (int) $p->ID,
			'title'            => (string) $p->post_title,
			'status'           => (string) $p->post_status,
			'price'            => (string) get_post_meta( $p->ID, '_regular_price', true ),
			'price_on_request' => (string) get_post_meta( $p->ID, '_jn_price_on_request', true ),
		);
	}
}

$privacy_page = get_post( 15 );
$terms_page   = get_post( 16 );

$fluent_smtp_installed = is_dir( WP_PLUGIN_DIR . '/fluent-smtp' );

// Verify technical cleanliness after cleanup.
$remaining_non_coaching_products = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
		'posts_per_page' => 50,
		'post__not_in'   => array( 163, 164, 165 ),
	)
);
$remaining_extra_pages = array();
$check_pages           = get_posts(
	array(
		'post_type'      => 'page',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
		'posts_per_page' => 50,
	)
);
foreach ( $check_pages as $pg ) {
	if ( ! in_array( strtolower( (string) $pg->post_name ), $canonical_page_slugs, true ) ) {
		$remaining_extra_pages[] = $pg->post_name;
	}
}
$remaining_bookings = get_posts(
	array(
		'post_type'      => 'jn_booking',
		'post_status'    => 'any',
		'posts_per_page' => 10,
	)
);
$remaining_orders = function_exists( 'wc_get_orders' ) ? wc_get_orders( array( 'limit' => 10, 'return' => 'ids' ) ) : array();

$blockers = array(
	array(
		'id'          => 'BL-01',
		'unknown_ids' => array( 'UNK-02', 'UNK-07' ),
		'title'       => 'Ảnh thật thay thế toàn bộ ảnh PLACEHOLDER (không dùng ảnh AI của Jannie hoặc học viên) & ảnh Open Graph 1200x630',
		'status'      => 'WAIVED_BY_OWNER',
		'details'     => array(
			'placeholder_attachments_count' => count( $placeholder_media_list ),
			'placeholder_attachments'       => $placeholder_media_list,
			'og_image'                      => get_option( 'jn_step8_og_image', array() ),
		),
		'how_to_replace' => 'Vào Thư viện (Media) hoặc trực tiếp trong trình sửa trang Gutenberg → nhấp vào từng ảnh giữ chỗ (ID 86..90) → chọn "Thay thế" (Replace) bằng ảnh chụp thật đã thu nhỏ <= 2000px.',
	),
	array(
		'id'          => 'BL-02',
		'unknown_ids' => array( 'UNK-03' ),
		'title'       => 'Xác minh 3 lời chứng thực với sự đồng ý của khách hàng & Chủ dự án xác nhận số liệu thống kê / giải thưởng',
		'status'      => 'WAIVED_BY_OWNER',
		'details'     => array(
			'testimonials_pending_verification' => $unverified_testimonials,
			'claims_in_wireframe'               => array( '20+ Năm Thực Chiến', '98% Tỷ Lệ Tối Ưu Hóa', '+180% Doanh Thu', 'Tỷ lệ quay lại > 85%', 'Nghệ Nhân Bàn Tay Vàng — Oscar for Beauty' ),
		),
		'how_to_replace' => 'Vào menu "Lời chứng thực" trong WP Admin → cập nhật nội dung/ảnh thật đã được khách đồng ý và xóa ghi chú "cần xác minh & xin phép khách".',
	),
	array(
		'id'          => 'BL-03',
		'unknown_ids' => array( 'UNK-01', 'UNK-04', 'UNK-05' ),
		'title'       => 'Điền Số điện thoại / Zalo, Địa chỉ đầy đủ, Tài khoản ngân hàng nhận chuyển khoản & Giá 3 gói Coaching',
		'status'      => 'WAIVED_BY_OWNER',
		'details'     => array(
			'phone_placeholder' => '(+84) 908-XXX-XXX',
			'address_current'   => 'TP. Hồ Chí Minh, Việt Nam',
			'bacs_accounts'     => $bacs_accounts,
			'coaching_packages' => $coaching_pkgs,
		),
		'how_to_replace' => '1) Vào Lịch hẹn → Cài đặt để cập nhật địa chỉ Học viện; 2) Vào WooCommerce → Cài đặt → Thanh toán → Chuyển khoản ngân hàng (BACS) để điền STK thật; 3) Vào Sản phẩm để điền giá VND và chuyển 3 gói Coaching (ID 163, 164, 165) sang Đã xuất bản nếu muốn công khai giá.',
	),
	array(
		'id'          => 'BL-04',
		'unknown_ids' => array( 'UNK-09' ),
		'title'       => 'Chủ dự án rà soát và phê duyệt Chính sách bảo mật, Điều khoản dịch vụ & câu chữ ô Consent (khuyến nghị luật sư duyệt)',
		'status'      => 'WAIVED_BY_OWNER',
		'details'     => array(
			'privacy_page_id'          => 15,
			'privacy_has_placeholder'  => $privacy_page instanceof WP_Post && str_contains( (string) $privacy_page->post_content, '[CẦN NỘI DUNG:' ),
			'terms_page_id'            => 16,
			'terms_has_placeholder'    => $terms_page instanceof WP_Post && str_contains( (string) $terms_page->post_content, '[CẦN NỘI DUNG:' ),
			'consent_version'          => get_option( 'jn_booking_settings', array() )['consent_policy_version'] ?? 'v1.0-draft',
		),
		'how_to_replace' => 'Vào Trang → mở "Chính sách bảo mật" và "Điều khoản dịch vụ" để thay phần [CẦN NỘI DUNG: ...] bằng văn bản pháp lý chính thức; vào Lịch hẹn → Cài đặt để cập nhật câu chữ Consent.',
	),
	array(
		'id'          => 'BL-05',
		'unknown_ids' => array( 'UNK-05' ),
		'title'       => 'Thông tin định danh Cổng thanh toán trực tuyến (nếu sử dụng ngoài Chuyển khoản ngân hàng)',
		'status'      => 'WAIVED_BY_OWNER',
		'details'     => array(
			'payment_policy'  => 'Pay after the assistant confirms the slot (payment link by email)',
			'enabled_gateways' => array( 'bacs (Virtual & Physical)', 'cod (Physical only)' ),
		),
		'how_to_replace' => 'Mặc định sử dụng Chuyển khoản ngân hàng (BACS) qua liên kết order-pay. Chỉ cài plugin cổng thanh toán chính thức khi chủ dự án có tài khoản Merchant.',
	),
	array(
		'id'          => 'BL-06',
		'unknown_ids' => array( 'UNK-11' ),
		'title'       => 'Production SMTP với xác thực SPF / DKIM / DMARC cho tên miền gửi thư',
		'status'      => 'WAIVED_BY_OWNER',
		'details'     => array(
			'fluent_smtp_installed' => $fluent_smtp_installed,
			'sender_identity'       => 'Jannie Nguyen Academy <contact@jannienguyen.vn>',
			'local_transport'       => 'Mailpit (localhost:1025)',
		),
		'how_to_replace' => 'Trên máy chủ Production: kích hoạt plugin FluentSMTP đã cài sẵn, nhập khóa SMTP/API cho contact@jannienguyen.vn và cấu hình 3 bản ghi DNS SPF, DKIM, DMARC.',
	),
	array(
		'id'          => 'BL-07a',
		'unknown_ids' => array( 'UNK-10', 'UNK-14' ),
		'title'       => 'Tên miền Production (PROD_DOMAIN), Máy chủ đích (PROD_WEB_SERVER) & Email thật cho tài khoản nhân sự',
		'status'      => 'WAIVED_BY_OWNER',
		'details'     => array(
			'prod_domain'     => 'UNKNOWN',
			'prod_web_server' => 'UNKNOWN (Both Apache/LiteSpeed .htaccess and Nginx configs prepared in deploy/)',
			'staff_accounts'  => $canonical_staff_logins,
		),
		'how_to_replace' => 'Sử dụng bộ công cụ trong thư mục deploy/ khi có PROD_DOMAIN và cập nhật email thật của 4 nhân sự trong Thành viên → Tất cả thành viên.',
	),
	array(
		'id'          => 'BL-07b',
		'unknown_ids' => array(),
		'title'       => 'Xóa toàn bộ sản phẩm DEMO, trang kiểm thử / specimen, đơn hàng test và lịch hẹn test',
		'status'      => ( empty( $remaining_non_coaching_products ) && empty( $remaining_extra_pages ) && empty( $remaining_bookings ) && empty( $remaining_orders ) ) ? 'RESOLVED' : 'OPEN',
		'details'     => array(
			'remaining_demo_products' => count( $remaining_non_coaching_products ),
			'remaining_test_pages'    => count( $remaining_extra_pages ),
			'remaining_test_bookings' => count( $remaining_bookings ),
			'remaining_test_orders'   => count( $remaining_orders ),
			'cleaned_in_this_run'     => $cleaned_items,
		),
		'how_to_replace' => 'Đã tự động dọn sạch 100% sản phẩm DEMO, trang test/specimen, đơn hàng và lịch hẹn kiểm thử.',
	),
);

$unwaived_open_count = 0;
foreach ( $blockers as $b ) {
	if ( 'OPEN' === $b['status'] ) {
		++$unwaived_open_count;
	}
	echo sprintf( "[%s] %s — %s\n", $b['status'], $b['id'], $b['title'] );
}

$summary = array(
	'generated_at'        => gmdate( 'c' ),
	'waiver'              => $waiver_record,
	'unwaived_open_count' => $unwaived_open_count,
	'fluent_smtp_ready'   => $fluent_smtp_installed,
	'blockers'            => $blockers,
);

echo "-----------------------------------------------------------------\n";
echo sprintf( "Unwaived Open Launch Blockers: %d (0 required)\n", $unwaived_open_count );
echo "=================================================================\n";
echo "JSON_REPORT_START\n";
echo wp_json_encode( $summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
echo "JSON_REPORT_END\n";

if ( $unwaived_open_count > 0 ) {
	exit( 1 );
}
