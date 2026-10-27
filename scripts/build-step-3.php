<?php
/**
 * Build & Seed Step 3 Content:
 * 1. Seed the 3 wireframe `jn_testimonial` posts verbatim + verification note
 * 2. Create/update the 2 Synced Patterns (`wp_block`): Testimonials & Booking Section
 * 3. Build the Coaching page (`/coaching/`) from patterns in wireframe order
 * 4. Build honest scaffolds for Home (front page), My Story, Course, Client Success Story,
 *    Booking, Chính sách bảo mật (WP privacy page), Điều khoản dịch vụ
 * 5. Update Primary Menu (7 items) and "Khám Phá" Footer Menu (5 items) with real URLs
 *
 * Usage: ./scripts/wp.sh eval-file /scripts/build-step-3.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$theme_dir = get_stylesheet_directory();
$media_map = get_option( 'jn_step3_media_map', array() );

/**
 * Helper to render a PHP pattern file from `patterns/` into a string.
 *
 * @param string $filename Pattern filename inside `patterns/`.
 * @return string
 */
function jn_render_pattern_file( $filename ) {
	$path = trailingslashit( get_stylesheet_directory() ) . 'patterns/' . $filename;
	if ( ! file_exists( $path ) ) {
		fwrite( STDERR, "Missing pattern file: {$path}\n" );
		exit( 1 );
	}
	ob_start();
	include $path;
	return trim( (string) ob_get_clean() );
}

// ============================================================================
// 1. Seed the 3 wireframe testimonials into CPT `jn_testimonial`
// ============================================================================
$testimonials_seed = array(
	array(
		'slug'            => 'minh-thao',
		'menu_order'      => 1,
		'client_name'     => 'Minh Thảo',
		'client_role'     => 'Chủ Thảo Beauty Academy',
		'client_city'     => 'Hà Nội',
		'role_display'    => 'Chủ Thảo Beauty Academy (Hà Nội)',
		'quote'           => '"Buổi coaching 60 phút với cô Jannie đã tháo gỡ điểm nghẽn lớn nhất trong kỹ thuật sợi chân mày của em. Khách hàng giờ đây quay lại khen dáng mày tự nhiên và giới thiệu thêm rất nhiều bạn bè."',
		'rating'          => 5,
		'related_package' => '60-phut',
		'admin_note'      => 'cần xác minh & xin phép khách',
		'media_key'       => 'avatar_minh_thao',
	),
	array(
		'slug'            => 'ngoc-bich',
		'menu_order'      => 2,
		'client_name'     => 'Ngọc Bích',
		'client_role'     => 'Founder, Bích Clinic & Spa',
		'client_city'     => 'TP.HCM',
		'role_display'    => 'Founder, Bích Clinic & Spa (TP.HCM)',
		'quote'           => '"Chương trình Mentorship 3 tháng thực sự thay đổi tư duy kinh doanh của mình. Không chỉ nâng cấp tay nghề cho đội ngũ kỹ thuật viên, doanh số Spa tháng vừa qua đã tăng gấp đôi nhờ chuẩn hóa quy trình chăm sóc."',
		'rating'          => 5,
		'related_package' => 'mentorship-3-thang',
		'admin_note'      => 'cần xác minh & xin phép khách',
		'media_key'       => 'avatar_ngoc_bich',
	),
	array(
		'slug'            => 'phuong-uyen',
		'menu_order'      => 3,
		'client_name'     => 'Phương Uyên',
		'client_role'     => 'Học viên Tư Vấn Cá Nhân',
		'client_city'     => '',
		'role_display'    => 'Học viên Tư Vấn Cá Nhân',
		'quote'           => '"Chỉ sau 30 phút tư vấn 1:1, Jannie đã giúp em hiểu được làn da của mình cần sự tối giản chứ không phải cả chục bước dưỡng đắt đỏ. Da em khỏe lên từng ngày và em tự tin hơn bao giờ hết."',
		'rating'          => 5,
		'related_package' => '30-phut',
		'admin_note'      => 'cần xác minh & xin phép khách',
		'media_key'       => 'avatar_phuong_uyen',
	),
);

foreach ( $testimonials_seed as $item ) {
	$existing = get_posts(
		array(
			'post_type'      => 'jn_testimonial',
			'name'           => $item['slug'],
			'posts_per_page' => 1,
			'post_status'    => 'any',
		)
	);

	$post_arr = array(
		'post_type'    => 'jn_testimonial',
		'post_name'    => $item['slug'],
		'post_title'   => $item['client_name'],
		'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $item['quote'] ) . '</p><!-- /wp:paragraph -->',
		'post_status'  => 'publish',
		'menu_order'   => $item['menu_order'],
	);

	if ( ! empty( $existing ) ) {
		$post_arr['ID'] = (int) $existing[0]->ID;
		$t_id           = wp_update_post( $post_arr );
	} else {
		$t_id = wp_insert_post( $post_arr );
	}

	update_post_meta( $t_id, 'jn_client_name', $item['client_name'] );
	update_post_meta( $t_id, 'jn_client_role', $item['client_role'] );
	update_post_meta( $t_id, 'jn_client_city', $item['client_city'] );
	update_post_meta( $t_id, 'jn_role_display', $item['role_display'] );
	update_post_meta( $t_id, 'jn_quote', $item['quote'] );
	update_post_meta( $t_id, 'jn_rating', $item['rating'] );
	update_post_meta( $t_id, 'jn_related_package', $item['related_package'] );
	update_post_meta( $t_id, 'jn_admin_note', $item['admin_note'] );

	if ( ! empty( $media_map[ $item['media_key'] ]['id'] ) ) {
		set_post_thumbnail( $t_id, (int) $media_map[ $item['media_key'] ]['id'] );
	}

	echo sprintf( "[OK] Seeded jn_testimonial: %s (ID=%d, note='%s')\n", $item['client_name'], $t_id, $item['admin_note'] );
}

// ============================================================================
// 2. Create/Update the 2 Synced Patterns (`wp_block`)
// ============================================================================
$existing_term = get_term_by( 'slug', 'jannie-nguyen', 'wp_pattern_category' );
if ( $existing_term && ! is_wp_error( $existing_term ) ) {
	wp_delete_term( (int) $existing_term->term_id, 'wp_pattern_category' );
}
$cat_term_id = 0;

/**
 * Upsert a synced pattern (`wp_block`).
 *
 * @param string $slug    Post slug.
 * @param string $title   Pattern title.
 * @param string $content Block markup.
 * @param int    $term_id `wp_pattern_category` term ID.
 * @return int Post ID.
 */
function jn_upsert_synced_pattern( $slug, $title, $content, $term_id ) {
	$existing = get_posts(
		array(
			'post_type'      => 'wp_block',
			'name'           => $slug,
			'posts_per_page' => 1,
			'post_status'    => 'any',
		)
	);

	$post_data = array(
		'post_type'    => 'wp_block',
		'post_name'    => $slug,
		'post_title'   => $title,
		'post_content' => wp_slash( $content ),
		'post_status'  => 'publish',
	);

	if ( ! empty( $existing ) ) {
		$post_data['ID'] = (int) $existing[0]->ID;
		$block_id        = wp_update_post( $post_data );
	} else {
		$block_id = wp_insert_post( $post_data );
	}

	delete_post_meta( $block_id, 'wp_pattern_sync_status' );
	if ( $term_id > 0 && taxonomy_exists( 'wp_pattern_category' ) ) {
		wp_set_object_terms( $block_id, array( $term_id ), 'wp_pattern_category', false );
	}

	return (int) $block_id;
}

$pattern_hero         = jn_render_pattern_file( 'hero-editorial.php' );
$pattern_pillars      = jn_render_pattern_file( 'pillars-3.php' );
$pattern_tiers        = jn_render_pattern_file( 'pricing-tiers-3.php' );
$pattern_expert       = jn_render_pattern_file( 'expert-intro.php' );
$pattern_testimonials = jn_render_pattern_file( 'testimonials-3.php' );
$pattern_booking      = jn_render_pattern_file( 'booking-section.php' );

$synced_testimonials_id = jn_upsert_synced_pattern(
	'jn-synced-testimonials-3',
	'Testimonials Section (Synced)',
	$pattern_testimonials,
	$cat_term_id
);

$synced_booking_id = jn_upsert_synced_pattern(
	'jn-synced-booking-section',
	'Booking Section (Synced)',
	$pattern_booking,
	$cat_term_id
);

echo sprintf(
	"[OK] Synced Patterns -> Testimonials ID=%d | Booking ID=%d\n",
	$synced_testimonials_id,
	$synced_booking_id
);

$ref_testimonials_block = sprintf( '<!-- wp:block {"ref":%d} /-->', $synced_testimonials_id );
$ref_booking_block      = sprintf( '<!-- wp:block {"ref":%d} /-->', $synced_booking_id );

// ============================================================================
// 3. Helper to build a Page Intro + Placeholder Scaffold Box
// ============================================================================
function jn_build_page_intro_markup( $eyebrow, $title_plain, $title_italic, $lead ) {
	return sprintf(
		'<!-- wp:group {"tagName":"section","align":"full","backgroundColor":"palette-color-7","className":"jn-section jn-page-intro-section"} -->' . "\n" .
		'<section class="wp-block-group alignfull jn-section jn-page-intro-section has-palette-color-7-background-color has-background">' . "\n" .
			'<!-- wp:group {"className":"jn-container jn-page-intro-inner"} -->' . "\n" .
			'<div class="wp-block-group jn-container jn-page-intro-inner">' . "\n" .
				'<!-- wp:paragraph {"className":"is-style-eyebrow-dot"} -->' . "\n" .
				'<p class="is-style-eyebrow-dot">%1$s</p>' . "\n" .
				'<!-- /wp:paragraph -->' . "\n" .
				'<!-- wp:heading {"level":1,"className":"jn-page-intro-title"} -->' . "\n" .
				'<h1 class="wp-block-heading jn-page-intro-title">%2$s <em class="jn-accent-italic">%3$s</em></h1>' . "\n" .
				'<!-- /wp:heading -->' . "\n" .
				'<!-- wp:paragraph {"className":"jn-page-intro-lead","textColor":"muted-stone","fontSize":"body-lg"} -->' . "\n" .
				'<p class="jn-page-intro-lead has-muted-stone-color has-text-color has-body-lg-font-size">%4$s</p>' . "\n" .
				'<!-- /wp:paragraph -->' . "\n" .
			'</div>' . "\n" .
			'<!-- /wp:group -->' . "\n" .
		'</section>' . "\n" .
		'<!-- /wp:group -->',
		esc_html( $eyebrow ),
		esc_html( $title_plain ),
		esc_html( $title_italic ),
		esc_html( $lead )
	);
}

function jn_build_scaffold_section_markup( $heading, $placeholder_copy ) {
	return sprintf(
		'<!-- wp:group {"tagName":"section","align":"full","className":"jn-section"} -->' . "\n" .
		'<section class="wp-block-group alignfull jn-section">' . "\n" .
			'<!-- wp:group {"className":"jn-container"} -->' . "\n" .
			'<div class="wp-block-group jn-container">' . "\n" .
				'<!-- wp:group {"className":"jn-scaffold-placeholder"} -->' . "\n" .
				'<div class="wp-block-group jn-scaffold-placeholder">' . "\n" .
					'<!-- wp:paragraph {"className":"is-style-eyebrow"} -->' . "\n" .
					'<p class="is-style-eyebrow">Khung Nội Dung Chờ Cập Nhật</p>' . "\n" .
					'<!-- /wp:paragraph -->' . "\n" .
					'<!-- wp:heading {"level":2,"className":"jn-section-title-md"} -->' . "\n" .
					'<h2 class="wp-block-heading jn-section-title-md">%1$s</h2>' . "\n" .
					'<!-- /wp:heading -->' . "\n" .
					'<!-- wp:paragraph {"textColor":"muted-stone"} -->' . "\n" .
					'<p class="has-muted-stone-color has-text-color">%2$s</p>' . "\n" .
					'<!-- /wp:paragraph -->' . "\n" .
				'</div>' . "\n" .
				'<!-- /wp:group -->' . "\n" .
			'</div>' . "\n" .
			'<!-- /wp:group -->' . "\n" .
		'</section>' . "\n" .
		'<!-- /wp:group -->',
		esc_html( $heading ),
		esc_html( $placeholder_copy )
	);
}

// ============================================================================
// 4. Upsert all pages with 100% Core + `jn/*` blocks (zero `core/html`)
// ============================================================================
$hero_media = jannie_get_placeholder_media( 'hero_portrait', 'jn-placeholder-hero-portrait.webp' );

// Home Hero variation built from `jannie/hero-editorial` pattern structure.
$home_hero_markup = str_replace(
	array(
		'Private Mentorship &amp; Advisory Program',
		'Coaching &amp; Đồng Hành <em class="jn-accent-italic">Chuyên Sâu</em>',
		'Chương trình cố vấn 1:1 độc quyền cùng Chuyên gia Jannie Nguyễn dành cho cá nhân định hình vẻ đẹp tự nhiên và các chủ cơ sở thẩm mỹ, phun xăm, spa cao cấp.',
		'<a class="wp-block-button__link wp-element-button" href="#booking-form">Đặt Lịch Coaching Ngay</a>',
		'<a class="wp-block-button__link wp-element-button" href="#coaching-tiers">Xem Chi Tiết 3 Gói Cố Vấn</a>',
	),
	array(
		'Jannie Nguyen Academy &amp; Mentorship',
		'Khám Phá Sự Cân Bằng &amp; <em class="jn-accent-italic">Vẻ Đẹp Đích Thực</em>',
		'Sứ mệnh của Jannie là tạo ra sự cân bằng và khám phá vẻ đẹp đích thực trong mỗi người. [CẦN NỘI DUNG: Đoạn giới thiệu tổng quan hệ sinh thái Học viện, Cố vấn 1:1 và Đào tạo của Jannie Nguyễn do chủ dự án cung cấp.]',
		'<a class="wp-block-button__link wp-element-button" href="' . esc_url( home_url( '/coaching/' ) ) . '">Khám Phá Chương Trình Coaching</a>',
		'<a class="wp-block-button__link wp-element-button" href="#booking-form">Đặt Lịch Tư Vấn 1:1</a>',
	),
	$pattern_hero
);

$pages_spec = array(
	'coaching'             => array(
		'title'   => 'Coaching',
		'content' => implode(
			"\n\n",
			array(
				$pattern_hero,
				$pattern_pillars,
				$pattern_tiers,
				$pattern_expert,
				$ref_testimonials_block,
				$ref_booking_block,
			)
		),
	),
	'home'                 => array(
		'title'   => 'Home',
		'content' => implode(
			"\n\n",
			array(
				$home_hero_markup,
				$pattern_expert,
				$pattern_pillars,
				$pattern_tiers,
				$ref_testimonials_block,
				$ref_booking_block,
			)
		),
	),
	'my-story'             => array(
		'title'   => 'My Story',
		'content' => implode(
			"\n\n",
			array(
				jn_build_page_intro_markup(
					'Hành Trình & Triết Lý',
					'Câu Chuyện Thương Hiệu &',
					'Vẻ Đẹp Đích Thực',
					'[CẦN NỘI DUNG: Lời mở đầu tự sự của chuyên gia Jannie Nguyễn về cơ duyên đến với ngành thẩm mỹ và triết lý The Balance.]'
				),
				$pattern_expert,
				jn_build_scaffold_section_markup(
					'Các Cột Mốc Sự Nghiệp & Giải Thưởng Quốc Tế',
					'[CẦN NỘI DUNG: Biên niên sử các cột mốc sự nghiệp, giải thưởng quốc tế, chứng chỉ chuyên môn và bộ ảnh tư liệu thật của chuyên gia Jannie Nguyễn — không tự suy diễn số liệu hay tiểu sử khi chưa có dữ liệu từ chủ dự án.]'
				),
				$ref_booking_block,
			)
		),
	),
	'course'               => array(
		'title'   => 'Course',
		'content' => implode(
			"\n\n",
			array(
				jn_build_page_intro_markup(
					'Jannie Nguyen Academy',
					'Chương Trình Đào Tạo',
					'Thẩm Mỹ & Phun Xăm',
					'[CẦN NỘI DUNG: Danh mục các khóa học chính thức, lịch khai giảng, giáo trình chi tiết và học phí từ Jannie Nguyen Academy — UNK-08.]'
				),
				jn_build_scaffold_section_markup(
					'Danh Mục Khóa Học Chuyên Sâu',
					'[CẦN NỘI DUNG: Thông tin chi tiết từng khóa học, đối tượng học viên, thời lượng đào tạo, quyền lợi thực hành và mức học phí chính thức — UNK-08.]'
				),
				$pattern_pillars,
				$ref_booking_block,
			)
		),
	),
	'client-success-story' => array(
		'title'   => 'Client Success Story',
		'content' => implode(
			"\n\n",
			array(
				jn_build_page_intro_markup(
					'Minh Chứng Thực Tế',
					'Câu Chuyện Thành Công',
					'Học Viên & Khách Hàng',
					'[CẦN NỘI DUNG: Tổng hợp các hồ sơ case-study thực tế sau khi đã xác minh và có sự đồng thuận bằng văn bản của khách hàng — UNK-03.]'
				),
				$ref_testimonials_block,
				jn_build_scaffold_section_markup(
					'Hồ Sơ Chuyển Đổi Chi Tiết (Case Studies)',
					'[CẦN NỘI DUNG: Bài viết phân tích chi tiết tình trạng trước/sau buổi cố vấn và kết quả vận hành thực tế của từng học viên đã xác minh — UNK-03.]'
				),
				$ref_booking_block,
			)
		),
	),
	'booking'              => array(
		'title'   => 'Booking',
		'content' => implode(
			"\n\n",
			array(
				jn_build_page_intro_markup(
					'Private Mentorship & Advisory',
					'Đăng Ký Tư Vấn &',
					'Xếp Lịch Coaching 1:1',
					'Chương trình cố vấn 1:1 độc quyền cùng Chuyên gia Jannie Nguyễn dành cho cá nhân định hình vẻ đẹp tự nhiên và các chủ cơ sở thẩm mỹ, phun xăm, spa cao cấp.'
				),
				$ref_booking_block,
			)
		),
	),
	'chinh-sach-bao-mat'   => array(
		'title'   => 'Chính sách bảo mật',
		'content' => implode(
			"\n\n",
			array(
				jn_build_page_intro_markup(
					'Thông Tin Pháp Lý',
					'Chính Sách Bảo Mật',
					'Dữ Liệu Cá Nhân',
					'[CẦN NỘI DUNG: Văn bản Chính sách bảo mật dữ liệu cá nhân chính thức được chủ dự án và bộ phận pháp lý phê duyệt — UNK-09.]'
				),
				jn_build_scaffold_section_markup(
					'1. Mục Đích & Phạm Vi Thu Thập Dữ Liệu',
					'[CẦN NỘI DUNG: Quy định chi tiết về việc thu thập Họ tên, Số điện thoại/Zalo, Email và nhu cầu tư vấn khi khách hàng gửi biểu mẫu đăng ký Coaching — UNK-09.]'
				),
				jn_build_scaffold_section_markup(
					'2. Thời Gian Lưu Trữ & Quyền Của Chủ Thể Dữ Liệu',
					'[CẦN NỘI DUNG: Quy định về thời hạn lưu trữ, cam kết không chia sẻ dữ liệu cho bên thứ ba và đầu mối liên hệ yêu cầu chỉnh sửa/xóa dữ liệu tại contact@jannienguyen.vn — UNK-09.]'
				),
			)
		),
	),
	'dieu-khoan-dich-vu'   => array(
		'title'   => 'Điều khoản dịch vụ',
		'content' => implode(
			"\n\n",
			array(
				jn_build_page_intro_markup(
					'Thông Tin Pháp Lý',
					'Điều Khoản Dịch Vụ &',
					'Quy Định Học Viện',
					'[CẦN NỘI DUNG: Văn bản Điều khoản dịch vụ chính thức được chủ dự án và bộ phận pháp lý phê duyệt — UNK-09.]'
				),
				jn_build_scaffold_section_markup(
					'1. Quy Định Đặt Lịch, Đổi Lịch & Thanh Toán',
					'[CẦN NỘI DUNG: Quy định chi tiết về quy trình xác nhận lịch hẹn trong 24 giờ, hướng dẫn thanh toán sau khi xác nhận lịch và chính sách đổi/hoãn lịch hẹn — UNK-09.]'
				),
				jn_build_scaffold_section_markup(
					'2. Cam Kết Bảo Mật Thông Tin Phiên Cố Vấn 1:1',
					'[CẦN NỘI DUNG: Quy định về bản quyền tài liệu Action Plan, bản ghi hình buổi coaching và cam kết bảo mật thông tin kinh doanh của học viên — UNK-09.]'
				),
			)
		),
	),
	'my-favorite-product'  => array(
		'title'   => 'My Favorite Product',
		'content' => implode(
			"\n\n",
			array(
				jn_build_page_intro_markup(
					'Curated Selection',
					'Sản Phẩm Yêu Thích',
					'Khuyên Dùng',
					'[CẦN NỘI DUNG: Danh mục sản phẩm chăm sóc và dụng cụ thẩm mỹ được chuyên gia Jannie Nguyễn tuyển chọn — triển khai ở Step 4.]'
				),
				jn_build_scaffold_section_markup(
					'Danh Mục Sản Phẩm Tuyển Chọn',
					'[CẦN NỘI DUNG: Cửa hàng sản phẩm yêu thích sẽ được tích hợp ở Step 4 sau khi chủ dự án cung cấp danh sách sản phẩm — UNK-08.]'
				),
			)
		),
	),
	'tai-khoan'            => array(
		'title'   => 'Tài khoản',
		'content' => implode(
			"\n\n",
			array(
				jn_build_page_intro_markup(
					'Học Viện Jannie Nguyễn',
					'Tài Khoản',
					'Học Viên & Khách Hàng',
					'[CẦN NỘI DUNG: Khu vực quản lý lịch hẹn và hồ sơ học viên — tích hợp ở Step 4 & Step 5.]'
				),
			)
		),
	),
);

$page_ids = array();

foreach ( $pages_spec as $slug => $spec ) {
	$existing = get_page_by_path( $slug, OBJECT, 'page' );
	$post_arr = array(
		'post_type'      => 'page',
		'post_name'      => $slug,
		'post_title'     => $spec['title'],
		'post_content'   => wp_slash( $spec['content'] ),
		'post_status'    => 'publish',
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	);

	if ( $existing ) {
		$post_arr['ID'] = (int) $existing->ID;
		$p_id           = wp_update_post( $post_arr );
	} else {
		$p_id = wp_insert_post( $post_arr );
	}

	$page_ids[ $slug ] = (int) $p_id;
	echo sprintf( "[OK] Upserted page: %-22s -> ID=%d (%s)\n", $slug, $p_id, get_permalink( $p_id ) );
}

// Delete the leftover default draft `privacy-policy` page (ID 3) if it exists.
$draft_privacy = get_page_by_path( 'privacy-policy', OBJECT, 'page' );
if ( $draft_privacy && (int) $draft_privacy->ID !== $page_ids['chinh-sach-bao-mat'] ) {
	wp_delete_post( (int) $draft_privacy->ID, true );
}

// Set Front Page = Home and Privacy Policy Page = Chính sách bảo mật.
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $page_ids['home'] );
update_option( 'wp_page_for_privacy_policy', $page_ids['chinh-sach-bao-mat'] );

// ============================================================================
// 5. Rebuild Navigation Menus with Real Page URLs
// ============================================================================
function jn_rebuild_menu( $menu_name, $items, $locations = array() ) {
	$menu_obj = wp_get_nav_menu_object( $menu_name );
	if ( $menu_obj ) {
		wp_delete_nav_menu( $menu_obj->term_id );
	}
	$menu_id = wp_create_nav_menu( $menu_name );

	foreach ( $items as $idx => $item ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $item['title'],
				'menu-item-object-id' => $item['page_id'],
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
				'menu-item-position'  => $idx + 1,
			)
		);
	}

	if ( ! empty( $locations ) ) {
		$nav_locations = get_theme_mod( 'nav_menu_locations', array() );
		if ( ! is_array( $nav_locations ) ) {
			$nav_locations = array();
		}
		foreach ( $locations as $loc ) {
			$nav_locations[ $loc ] = $menu_id;
		}
		set_theme_mod( 'nav_menu_locations', $nav_locations );
	}

	return $menu_id;
}

$primary_menu_id = jn_rebuild_menu(
	'Primary Menu',
	array(
		array( 'title' => 'Home', 'page_id' => $page_ids['home'] ),
		array( 'title' => 'My Story', 'page_id' => $page_ids['my-story'] ),
		array( 'title' => 'Coaching', 'page_id' => $page_ids['coaching'] ),
		array( 'title' => 'Course', 'page_id' => $page_ids['course'] ),
		array( 'title' => 'Client Success Story', 'page_id' => $page_ids['client-success-story'] ),
		array( 'title' => 'My Favorite Product', 'page_id' => $page_ids['my-favorite-product'] ),
		array( 'title' => 'Booking', 'page_id' => $page_ids['booking'] ),
	),
	array( 'menu_1', 'menu_mobile' )
);

$explore_menu_id = jn_rebuild_menu(
	'Khám Phá',
	array(
		array( 'title' => 'My Story', 'page_id' => $page_ids['my-story'] ),
		array( 'title' => 'Coaching & Mentorship', 'page_id' => $page_ids['coaching'] ),
		array( 'title' => 'Chương Trình Học', 'page_id' => $page_ids['course'] ),
		array( 'title' => 'Client Success Story', 'page_id' => $page_ids['client-success-story'] ),
		array( 'title' => 'Sản Phẩm Yêu Thích', 'page_id' => $page_ids['my-favorite-product'] ),
	),
	array()
);

$legal_menu_id = jn_rebuild_menu(
	'Footer Legal',
	array(
		array( 'title' => 'Chính sách bảo mật', 'page_id' => $page_ids['chinh-sach-bao-mat'] ),
		array( 'title' => 'Điều khoản dịch vụ', 'page_id' => $page_ids['dieu-khoan-dich-vu'] ),
	),
	array( 'footer' )
);

echo sprintf(
	"[OK] Rebuilt Menus -> Primary=%d | Khám Phá=%d | Footer Legal=%d\n",
	$primary_menu_id,
	$explore_menu_id,
	$legal_menu_id
);
