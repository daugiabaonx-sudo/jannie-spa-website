<?php
/**
 * JN STEP 2 — Provisioning Script for Child Theme "jannie", "jn-core" Plugin,
 * Pages, Navigation Menus, Site Icon, Footer Widgets, and Blocksy Builder Mods.
 *
 * Executed via: wp eval-file /var/www/html/../scripts/provision-step-2.php
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Destructive local-only script: refuse to run against production (review 2026-10-28, H2).
require_once __DIR__ . '/lib/jn-env-guard.php';
jn_require_non_production( basename( __FILE__ ) );

echo "=== [1/8] Activating jn-core Plugin & jannie Child Theme ===\n";
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$plugin_result = activate_plugin( 'jn-core/jn-core.php' );
if ( is_wp_error( $plugin_result ) ) {
	fwrite( STDERR, 'Failed to activate jn-core: ' . $plugin_result->get_error_message() . "\n" );
	exit( 1 );
}
echo "Activated plugin: jn-core/jn-core.php\n";

switch_theme( 'jannie' );
if ( ! function_exists( 'jannie_get_global_color_palette' ) ) {
	require_once get_theme_root() . '/jannie/functions.php';
}
echo 'Activated theme: ' . get_stylesheet() . ' (parent: ' . get_template() . ")\n";

$old_stub_dir = WP_CONTENT_DIR . '/themes/blocksy-jn';
if ( is_dir( $old_stub_dir ) ) {
	$it    = new RecursiveDirectoryIterator( $old_stub_dir, RecursiveDirectoryIterator::SKIP_DOTS );
	$files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $files as $file ) {
		if ( $file->isDir() ) {
			rmdir( $file->getRealPath() );
		} else {
			unlink( $file->getRealPath() );
		}
	}
	rmdir( $old_stub_dir );
	echo "Removed old stub theme: blocksy-jn\n";
}

echo "=== [2/8] Updating Site Identity & Importing JN Monogram Site Icon ===\n";
update_option( 'blogname', 'JANNIE NGUYEN' );
update_option( 'blogdescription', 'BEAUTY BLOG' );

$icon_source = get_stylesheet_directory() . '/assets/images/jn-monogram-icon.png';
$existing_icon_id = (int) get_option( 'site_icon', 0 );
if ( ! $existing_icon_id || ! get_post( $existing_icon_id ) ) {
	$upload = wp_upload_bits( 'jn-monogram-icon.png', null, file_get_contents( $icon_source ) );
	if ( empty( $upload['error'] ) ) {
		$attachment = array(
			'post_mime_type' => 'image/png',
			'post_title'     => 'Jannie Nguyen Monogram',
			'post_content'   => '',
			'post_status'    => 'inherit',
		);
		$attach_id  = wp_insert_attachment( $attachment, $upload['file'] );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		update_post_meta( $attach_id, '_wp_attachment_image_alt', 'Jannie Nguyen' );
		update_option( 'site_icon', $attach_id );
		set_theme_mod( 'custom_logo', $attach_id );
		$existing_icon_id = $attach_id;
		echo "Imported JN Monogram Site Icon (Attachment ID: {$attach_id})\n";
	}
} else {
	set_theme_mod( 'custom_logo', $existing_icon_id );
	echo "Using existing JN Monogram Site Icon (Attachment ID: {$existing_icon_id})\n";
}

// Also import a portrait placeholder image for testing Portrait 4:5 and Portrait 3:4 block styles.
$portrait_query = get_posts(
	array(
		'post_type'      => 'attachment',
		'name'           => 'jn-specimen-portrait',
		'posts_per_page' => 1,
		'post_status'    => 'inherit',
	)
);
if ( empty( $portrait_query ) ) {
	$im = imagecreatetruecolor( 800, 1000 );
	$bg = imagecolorallocate( $im, 243, 239, 234 );
	$fg = imagecolorallocate( $im, 172, 142, 118 );
	imagefilledrectangle( $im, 0, 0, 800, 1000, $bg );
	imagerectangle( $im, 40, 40, 760, 960, $fg );
	ob_start();
	imagepng( $im );
	$png_bytes = ob_get_clean();
	imagedestroy( $im );

	$p_upload = wp_upload_bits( 'jn-specimen-portrait.png', null, $png_bytes );
	$p_id     = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_name'      => 'jn-specimen-portrait',
			'post_title'     => 'Jannie Editorial Portrait Specimen',
			'post_status'    => 'inherit',
		),
		$p_upload['file']
	);
	wp_update_attachment_metadata( $p_id, wp_generate_attachment_metadata( $p_id, $p_upload['file'] ) );
	update_post_meta( $p_id, '_wp_attachment_image_alt', 'Chân dung biên tập Jannie Nguyễn' );
	$portrait_url = wp_get_attachment_url( $p_id );
} else {
	$p_id         = $portrait_query[0]->ID;
	$portrait_url = wp_get_attachment_url( $p_id );
}

echo "=== [3/8] Creating Wireframe Pages & Vietnamese Design System Specimen ===\n";

$specimen_content = <<<HTML
<!-- wp:group {"align":"full","className":"jn-section"} -->
<div class="wp-block-group alignfull jn-section">
	<!-- wp:group {"className":"jn-container"} -->
	<div class="wp-block-group jn-container">
		<!-- wp:paragraph {"className":"is-style-eyebrow-dot"} -->
		<p class="is-style-eyebrow-dot">Private Mentorship &amp; Advisory Program</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1} -->
		<h1 class="wp-block-heading">Coaching &amp; Đồng Hành <em class="has-champagne-text-lg-color has-text-color">Chuyên Sâu</em></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"style":{"typography":{"fontSize":"16px","lineHeight":"1.625"}},"textColor":"muted-stone"} -->
		<p class="has-muted-stone-color has-text-color" style="font-size:16px;line-height:1.625">Chương trình cố vấn 1:1 độc quyền cùng Chuyên gia Jannie Nguyễn dành cho cá nhân định hình vẻ đẹp tự nhiên và các chủ cơ sở thẩm mỹ, phun xăm, spa cao cấp.</p>
		<!-- /wp:paragraph -->

		<!-- wp:group {"className":"is-style-metrics-bar"} -->
		<div class="wp-block-group is-style-metrics-bar">
			<!-- wp:group -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading">20+</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"className":"is-style-eyebrow"} -->
				<p class="is-style-eyebrow">Năm Thực Chiến</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
			<!-- wp:group -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading">1:1</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"className":"is-style-eyebrow"} -->
				<p class="is-style-eyebrow">Cố Vấn Độc Bản</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
			<!-- wp:group -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading">98%</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"className":"is-style-eyebrow"} -->
				<p class="is-style-eyebrow">Tỷ Lệ Tối Ưu Hóa</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->

		<!-- wp:separator {"backgroundColor":"palette-color-5"} -->
		<hr class="wp-block-separator has-text-color has-palette-color-5-color has-alpha-channel-opacity has-palette-color-5-background-color has-background"/>
		<!-- /wp:separator -->

		<!-- wp:paragraph {"className":"is-style-eyebrow-line"} -->
		<p class="is-style-eyebrow-line">Bảng Kiểm Chứng Tiếng Việt (Heading H1–H6 Regular &amp; Italic)</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":2} -->
		<h2 class="wp-block-heading">H2: Chi Tiết Gói Đào Tạo &amp; <em>Cố Vấn Độc Bản</em> — Ă Â Đ Ê Ô Ơ Ư</h2>
		<!-- /wp:heading -->

		<!-- wp:heading {"level":3} -->
		<h3 class="wp-block-heading">H3: Lợi Ích Của Chương Trình Coaching &amp; <em>Định Hướng Chiến Lược</em></h3>
		<!-- /wp:heading -->

		<!-- wp:heading {"level":4} -->
		<h4 class="wp-block-heading">H4: Quản Lý Cơ Sở, Tăng Trưởng Doanh Số &amp; <em>May Đo Lộ Trình 1:1</em></h4>
		<!-- /wp:heading -->

		<!-- wp:heading {"level":5} -->
		<h5 class="wp-block-heading">H5: Tái định vị tay nghề, chuẩn hóa quy trình chăm sóc thượng khách</h5>
		<!-- /wp:heading -->

		<!-- wp:heading {"level":6} -->
		<h6 class="wp-block-heading">H6: Sứ mệnh của Jannie là tạo ra sự cân bằng và khám phá vẻ đẹp đích thực</h6>
		<!-- /wp:heading -->

		<!-- wp:paragraph -->
		<p>Bộ dấu thanh tiếng Việt đầy đủ (Regular &amp; <em>Italic</em>): à á ả ã ạ — ằ ắ ẳ ẵ ặ — ầ ấ ẩ ẫ ậ — è é ẻ ẽ ẹ — ề ế ể ễ ệ — ì í ỉ ĩ ị — ò ó ỏ õ ọ — ồ ố ổ ỗ hộ — ờ ớ ở ỡ ợ — ù ú ủ ũ ụ — ừ ứ ử ữ ự — ỳ ý ỷ ỹ ỵ — đ Đ. <em>“Phác đồ rõ ràng, tinh tế và thực tiễn cho từng mô hình thẩm mỹ.”</em></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"is-style-eyebrow"} -->
		<p class="is-style-eyebrow">5 Biến Thể Nút Bấm (Button Block Styles)</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-primary-pill"} -->
			<div class="wp-block-button is-style-primary-pill"><a class="wp-block-button__link wp-element-button" href="#booking-form">Đặt Lịch Coaching Ngay</a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-soft-pill"} -->
			<div class="wp-block-button is-style-soft-pill"><a class="wp-block-button__link wp-element-button" href="#coaching-tiers">Xem Chi Tiết 3 Gói Cố Vấn</a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-accent-pill"} -->
			<div class="wp-block-button is-style-accent-pill"><a class="wp-block-button__link wp-element-button" href="#booking-form">Đăng Ký Gói Chuyên Sâu</a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#booking-form">Chọn Gói Tư Vấn 30 Phút</a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-text-link"} -->
			<div class="wp-block-button is-style-text-link"><a class="wp-block-button__link wp-element-button" href="#coaching-tiers">Tìm hiểu thêm lộ trình</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->

		<!-- wp:paragraph {"className":"is-style-eyebrow"} -->
		<p class="is-style-eyebrow">Biến Thể Nhóm, Danh Sách &amp; Khung Chân Dung (Group, List &amp; Image Block Styles)</p>
		<!-- /wp:paragraph -->

		<!-- wp:columns -->
		<div class="wp-block-columns">
			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-card"} -->
				<div class="wp-block-group is-style-card">
					<!-- wp:paragraph {"className":"is-style-eyebrow"} -->
					<p class="is-style-eyebrow">Gói Khởi Đầu</p>
					<!-- /wp:paragraph -->
					<!-- wp:heading {"level":4} -->
					<h4 class="wp-block-heading">Tư Vấn Cá Nhân 1:1</h4>
					<!-- /wp:heading -->
					<!-- wp:list {"className":"is-style-check"} -->
					<ul class="wp-block-list is-style-check">
						<!-- wp:list-item -->
						<li>Đánh giá nhanh hiện trạng kỹ thuật hoặc định hình phong cách cá nhân.</li>
						<!-- /wp:list-item -->
						<!-- wp:list-item -->
						<li>Giải đáp trực tiếp 1–2 vấn đề trọng tâm cần tháo gỡ ngay.</li>
						<!-- /wp:list-item -->
					</ul>
					<!-- /wp:list -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-card-dark"} -->
				<div class="wp-block-group is-style-card-dark">
					<!-- wp:paragraph {"className":"is-style-eyebrow"} -->
					<p class="is-style-eyebrow">Gói Chuyên Sâu</p>
					<!-- /wp:paragraph -->
					<!-- wp:heading {"level":4} -->
					<h4 class="wp-block-heading">Phiên Chiến Lược 1:1</h4>
					<!-- /wp:heading -->
					<!-- wp:list {"className":"is-style-verified"} -->
					<ul class="wp-block-list is-style-verified">
						<!-- wp:list-item -->
						<li>Hoạch định lộ trình phát triển kỹ năng hoặc tái cấu trúc dịch vụ cơ sở.</li>
						<!-- /wp:list-item -->
						<!-- wp:list-item -->
						<li>Tư vấn chiến lược định giá, nâng cấp trải nghiệm khách hàng cao cấp.</li>
						<!-- /wp:list-item -->
					</ul>
					<!-- /wp:list -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-panel-beige"} -->
				<div class="wp-block-group is-style-panel-beige">
					<!-- wp:paragraph {"className":"is-style-eyebrow"} -->
					<p class="is-style-eyebrow">Panel Beige &amp; Glass Caption</p>
					<!-- /wp:paragraph -->
					<!-- wp:group {"className":"is-style-glass-caption"} -->
					<div class="wp-block-group is-style-glass-caption">
						<!-- wp:heading {"level":6} -->
						<h6 class="wp-block-heading">Triết Lý Cân Bằng Toàn Diện</h6>
						<!-- /wp:heading -->
						<!-- wp:paragraph -->
						<p>Kết hợp tinh hoa kỹ thuật thẩm mỹ và năng lực định vị nhân hiệu bền vững.</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:columns -->
		<div class="wp-block-columns">
			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:image {"id":{$p_id},"sizeSlug":"full","linkDestination":"none","className":"is-style-portrait-4-5"} -->
				<figure class="wp-block-image size-full is-style-portrait-4-5"><img src="{$portrait_url}" alt="Chân dung biên tập Jannie Nguyễn" class="wp-image-{$p_id}"/></figure>
				<!-- /wp:image -->
			</div>
			<!-- /wp:column -->
			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:image {"id":{$p_id},"sizeSlug":"full","linkDestination":"none","className":"is-style-portrait-3-4"} -->
				<figure class="wp-block-image size-full is-style-portrait-3-4"><img src="{$portrait_url}" alt="Chân dung biên tập Jannie Nguyễn" class="wp-image-{$p_id}"/></figure>
				<!-- /wp:image -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:paragraph {"className":"is-style-eyebrow"} -->
		<p class="is-style-eyebrow">20 Biểu Tượng Vector Nội Tuyến (Block jn/icon)</p>
		<!-- /wp:paragraph -->

		<!-- wp:group {"className":"is-style-panel-cream"} -->
		<div class="wp-block-group is-style-panel-cream">
			<!-- wp:jn/icon {"name":"person","size":24,"label":"Tài khoản"} /-->
			<!-- wp:jn/icon {"name":"north_east","size":24} /-->
			<!-- wp:jn/icon {"name":"south","size":24} /-->
			<!-- wp:jn/icon {"name":"all_inclusive","size":24} /-->
			<!-- wp:jn/icon {"name":"architecture","size":24} /-->
			<!-- wp:jn/icon {"name":"query_stats","size":24} /-->
			<!-- wp:jn/icon {"name":"fingerprint","size":24} /-->
			<!-- wp:jn/icon {"name":"schedule","size":24} /-->
			<!-- wp:jn/icon {"name":"check_circle","size":24} /-->
			<!-- wp:jn/icon {"name":"verified","size":24} /-->
			<!-- wp:jn/icon {"name":"arrow_forward","size":24} /-->
			<!-- wp:jn/icon {"name":"award_star","size":24} /-->
			<!-- wp:jn/icon {"name":"spa","size":24} /-->
			<!-- wp:jn/icon {"name":"format_quote","size":24} /-->
			<!-- wp:jn/icon {"name":"star","size":24} /-->
			<!-- wp:jn/icon {"name":"expand_more","size":24} /-->
			<!-- wp:jn/icon {"name":"send","size":24} /-->
			<!-- wp:jn/icon {"name":"location_on","size":24} /-->
			<!-- wp:jn/icon {"name":"mail","size":24} /-->
			<!-- wp:jn/icon {"name":"sparkline","size":48,"label":"Biểu đồ tăng trưởng"} /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
HTML;

$pages = array(
	'home'                 => array(
		'title'   => 'Home',
		'content' => $specimen_content,
	),
	'my-story'             => array(
		'title'   => 'My Story',
		'content' => '<!-- wp:group {"className":"jn-container jn-section"} --><div class="wp-block-group jn-container jn-section"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">My Story</h1><!-- /wp:heading --></div><!-- /wp:group -->',
	),
	'coaching'             => array(
		'title'   => 'Coaching',
		'content' => $specimen_content,
	),
	'course'               => array(
		'title'   => 'Course',
		'content' => '<!-- wp:group {"className":"jn-container jn-section"} --><div class="wp-block-group jn-container jn-section"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Course</h1><!-- /wp:heading --></div><!-- /wp:group -->',
	),
	'client-success-story' => array(
		'title'   => 'Client Success Story',
		'content' => '<!-- wp:group {"className":"jn-container jn-section"} --><div class="wp-block-group jn-container jn-section"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Client Success Story</h1><!-- /wp:heading --></div><!-- /wp:group -->',
	),
	'my-favorite-product'  => array(
		'title'   => 'My Favorite Product',
		'content' => '<!-- wp:group {"className":"jn-container jn-section"} --><div class="wp-block-group jn-container jn-section"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">My Favorite Product</h1><!-- /wp:heading --></div><!-- /wp:group -->',
	),
	'booking'              => array(
		'title'   => 'Booking',
		'content' => '<!-- wp:group {"className":"jn-container jn-section"} --><div class="wp-block-group jn-container jn-section"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Booking</h1><!-- /wp:heading --></div><!-- /wp:group -->',
	),
	'tai-khoan'            => array(
		'title'   => 'Tài khoản',
		'content' => '<!-- wp:group {"className":"jn-container jn-section"} --><div class="wp-block-group jn-container jn-section"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Tài khoản của tôi</h1><!-- /wp:heading --><!-- wp:paragraph --><p>Trang tài khoản học viên và khách hàng Jannie Nguyen Academy.</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
	),
	'chinh-sach-bao-mat'   => array(
		'title'   => 'Chính sách bảo mật',
		'content' => '<!-- wp:group {"className":"jn-container jn-section"} --><div class="wp-block-group jn-container jn-section"><!-- wp:paragraph {"className":"is-style-eyebrow"} --><p class="is-style-eyebrow">[PLACEHOLDER — UNK-09]</p><!-- /wp:paragraph --><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Chính sách bảo mật</h1><!-- /wp:heading --><!-- wp:paragraph --><p>[PLACEHOLDER — Nội dung pháp lý Chính sách bảo mật đang chờ cập nhật chính thức từ Jannie Nguyen Academy theo mục UNK-09 trong wireframe-spec.md.]</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
	),
	'dieu-khoan-dich-vu'   => array(
		'title'   => 'Điều khoản dịch vụ',
		'content' => '<!-- wp:group {"className":"jn-container jn-section"} --><div class="wp-block-group jn-container jn-section"><!-- wp:paragraph {"className":"is-style-eyebrow"} --><p class="is-style-eyebrow">[PLACEHOLDER — UNK-09]</p><!-- /wp:paragraph --><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Điều khoản dịch vụ</h1><!-- /wp:heading --><!-- wp:paragraph --><p>[PLACEHOLDER — Nội dung pháp lý Điều khoản dịch vụ đang chờ cập nhật chính thức từ Jannie Nguyen Academy theo mục UNK-09 trong wireframe-spec.md.]</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
	),
);

$page_ids = array();
foreach ( $pages as $slug => $data ) {
	$existing = get_page_by_path( $slug, OBJECT, 'page' );
	if ( $existing ) {
		wp_update_post(
			array(
				'ID'           => $existing->ID,
				'post_title'   => $data['title'],
				'post_content' => $data['content'],
				'post_status'  => 'publish',
			)
		);
		$page_ids[ $slug ] = $existing->ID;
	} else {
		$page_ids[ $slug ] = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_name'    => $slug,
				'post_title'   => $data['title'],
				'post_content' => $data['content'],
				'post_status'  => 'publish',
			)
		);
	}
}

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $page_ids['home'] );
echo 'Configured front page ID: ' . $page_ids['home'] . ' and Coaching page ID: ' . $page_ids['coaching'] . "\n";

echo "=== [4/8] Creating Navigation Menus (Primary, Khám Phá, Footer Legal) ===\n";

/**
 * Helper to recreate a nav menu deterministically.
 *
 * @param string                              $menu_name Menu name.
 * @param array<int, array<string, int|string>> $items     Menu items.
 * @return int Menu term ID.
 */
function jannie_seed_menu( $menu_name, $items ) {
	$existing = wp_get_nav_menu_object( $menu_name );
	if ( $existing ) {
		wp_delete_nav_menu( $existing->term_id );
	}
	$menu_id = wp_create_nav_menu( $menu_name );
	foreach ( $items as $idx => $item ) {
		if ( ! empty( $item['page_id'] ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $item['title'],
					'menu-item-object-id' => (int) $item['page_id'],
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
					'menu-item-position'  => $idx + 1,
				)
			);
		} else {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'    => $item['title'],
					'menu-item-url'      => $item['url'],
					'menu-item-type'     => 'custom',
					'menu-item-status'   => 'publish',
					'menu-item-position' => $idx + 1,
				)
			);
		}
	}
	return (int) $menu_id;
}

$primary_menu_id = jannie_seed_menu(
	'Primary Menu',
	array(
		array(
			'title'   => 'Home',
			'page_id' => $page_ids['home'],
		),
		array(
			'title'   => 'My Story',
			'page_id' => $page_ids['my-story'],
		),
		array(
			'title'   => 'Coaching',
			'page_id' => $page_ids['coaching'],
		),
		array(
			'title'   => 'Course',
			'page_id' => $page_ids['course'],
		),
		array(
			'title'   => 'Client Success Story',
			'page_id' => $page_ids['client-success-story'],
		),
		array(
			'title'   => 'My Favorite Product',
			'page_id' => $page_ids['my-favorite-product'],
		),
		array(
			'title'   => 'Booking',
			'page_id' => $page_ids['booking'],
		),
	)
);

$explore_menu_id = jannie_seed_menu(
	'Khám Phá',
	array(
		array(
			'title'   => 'My Story',
			'page_id' => $page_ids['my-story'],
		),
		array(
			'title'   => 'Coaching & Mentorship',
			'page_id' => $page_ids['coaching'],
		),
		array(
			'title'   => 'Chương Trình Học',
			'page_id' => $page_ids['course'],
		),
		array(
			'title'   => 'Client Success Story',
			'page_id' => $page_ids['client-success-story'],
		),
		array(
			'title'   => 'Sản Phẩm Yêu Thích',
			'page_id' => $page_ids['my-favorite-product'],
		),
	)
);

$legal_menu_id = jannie_seed_menu(
	'Footer Legal Menu',
	array(
		array(
			'title'   => 'Chính sách bảo mật',
			'page_id' => $page_ids['chinh-sach-bao-mat'],
		),
		array(
			'title'   => 'Điều khoản dịch vụ',
			'page_id' => $page_ids['dieu-khoan-dich-vu'],
		),
	)
);

set_theme_mod(
	'nav_menu_locations',
	array(
		'menu_1'      => $primary_menu_id,
		'menu_2'      => $explore_menu_id,
		'menu_mobile' => $primary_menu_id,
		'footer'      => $legal_menu_id,
	)
);

echo "Created menus — Primary: {$primary_menu_id}, Khám Phá: {$explore_menu_id}, Legal: {$legal_menu_id}\n";

echo "=== [5/8] Populating Footer Widget Areas 1, 2, 3 ===\n";
$widget_block = array(
	2            => array(
		'content' => '<!-- wp:shortcode -->[jn_footer_brand]<!-- /wp:shortcode -->',
	),
	3            => array(
		'content' => '<!-- wp:shortcode -->[jn_footer_explore]<!-- /wp:shortcode -->',
	),
	4            => array(
		'content' => '<!-- wp:shortcode -->[jn_footer_contact]<!-- /wp:shortcode -->',
	),
	'_multiwidget' => 1,
);
update_option( 'widget_block', $widget_block );

$sidebars_widgets                        = get_option( 'sidebars_widgets', array() );
$sidebars_widgets['ct-footer-sidebar-1'] = array( 'block-2' );
$sidebars_widgets['ct-footer-sidebar-2'] = array( 'block-3' );
$sidebars_widgets['ct-footer-sidebar-3'] = array( 'block-4' );
update_option( 'sidebars_widgets', $sidebars_widgets );

echo "=== [6/8] Configuring Blocksy Global Palette, Layout & Header/Footer Builder Mods ===\n";

set_theme_mod( 'colorPalette', jannie_get_global_color_palette() );
set_theme_mod( 'maxSiteWidth', 1248 );
set_theme_mod( 'contentSpacing', 'none' );
set_theme_mod( 'single_page_structure', 'type-4' );
set_theme_mod( 'single_page_content_style', 'wide' );
set_theme_mod( 'single_page_hero_enabled', 'no' );
set_theme_mod( 'single_page_content_area_spacing', 'none' );

$header_placements = array(
	'current_section' => 'type-1',
	'sections'        => array(
		array(
			'id'      => 'type-1',
			'mode'    => 'placements',
			'desktop' => array(
				array(
					'id'         => 'top-row',
					'placements' => array(
						array(
							'id'    => 'start',
							'items' => array(),
						),
						array(
							'id'    => 'middle',
							'items' => array(),
						),
						array(
							'id'    => 'end',
							'items' => array(),
						),
					),
				),
				array(
					'id'         => 'middle-row',
					'placements' => array(
						array(
							'id'    => 'start',
							'items' => array( 'logo' ),
						),
						array(
							'id'    => 'middle',
							'items' => array( 'menu' ),
						),
						array(
							'id'    => 'end',
							'items' => array( 'text', 'trigger' ),
						),
					),
				),
				array(
					'id'         => 'bottom-row',
					'placements' => array(
						array(
							'id'    => 'start',
							'items' => array(),
						),
						array(
							'id'    => 'middle',
							'items' => array(),
						),
						array(
							'id'    => 'end',
							'items' => array(),
						),
					),
				),
				array(
					'id'         => 'offcanvas',
					'placements' => array(
						array(
							'id'    => 'start',
							'items' => array( 'mobile-menu', 'button' ),
						),
					),
				),
			),
			'mobile'  => array(
				array(
					'id'         => 'top-row',
					'placements' => array(
						array(
							'id'    => 'start',
							'items' => array(),
						),
						array(
							'id'    => 'middle',
							'items' => array(),
						),
						array(
							'id'    => 'end',
							'items' => array(),
						),
					),
				),
				array(
					'id'         => 'middle-row',
					'placements' => array(
						array(
							'id'    => 'start',
							'items' => array( 'logo' ),
						),
						array(
							'id'    => 'middle',
							'items' => array(),
						),
						array(
							'id'    => 'end',
							'items' => array( 'text', 'trigger' ),
						),
					),
				),
				array(
					'id'         => 'bottom-row',
					'placements' => array(
						array(
							'id'    => 'start',
							'items' => array(),
						),
						array(
							'id'    => 'middle',
							'items' => array(),
						),
						array(
							'id'    => 'end',
							'items' => array(),
						),
					),
				),
				array(
					'id'         => 'offcanvas',
					'placements' => array(
						array(
							'id'    => 'start',
							'items' => array( 'mobile-menu', 'button' ),
						),
					),
				),
			),
			'items'   => array(
				array(
					'id'     => 'middle-row',
					'values' => array(
						'headerRowHeight' => array(
							'desktop' => 100,
							'tablet'  => 100,
							'mobile'  => 100,
						),
						'headerRowWidth'  => 'fixed',
					),
				),
				array(
					'id'     => 'logo',
					'values' => array(
						'custom_logo'                => $existing_icon_id,
						'has_site_title'             => 'yes',
						'has_tagline'                => 'yes',
						'blogname'                   => 'JANNIE NGUYEN',
						'blogdescription'            => 'BEAUTY BLOG',
						'logo_position'              => array(
							'desktop' => 'left',
							'tablet'  => 'left',
							'mobile'  => 'left',
						),
						'blogname_visibility'        => array(
							'desktop' => true,
							'tablet'  => true,
							'mobile'  => true,
						),
						'blogdescription_visibility' => array(
							'desktop' => true,
							'tablet'  => true,
							'mobile'  => true,
						),
					),
				),
				array(
					'id'     => 'menu',
					'values' => array(
						'menu'                            => $primary_menu_id,
						'header_menu_type'                => 'type-2',
						'collapse_non_fitting_menu_items' => 'no',
					),
				),
				array(
					'id'     => 'text',
					'values' => array(
						'header_text' => '[jn_header_account]',
						'visibility'  => array(
							'desktop' => true,
							'tablet'  => true,
							'mobile'  => true,
						),
					),
				),
				array(
					'id'     => 'trigger',
					'values' => array(
						'mobile_menu_trigger_type'  => 'type-1',
						'trigger_design'            => 'simple',
						'trigger_label'             => 'Menu',
						'header_trigger_visibility' => array(
							'desktop' => true,
							'tablet'  => true,
							'mobile'  => true,
						),
					),
				),
				array(
					'id'     => 'mobile-menu',
					'values' => array(
						'menu' => $primary_menu_id,
					),
				),
				array(
					'id'     => 'button',
					'values' => array(
						'header_button_text' => 'Đặt lịch',
						'header_button_link' => home_url( '/booking/' ),
					),
				),
				array(
					'id'     => 'offcanvas',
					'values' => array(
						'offcanvas_behavior'          => 'panel',
						'side_panel_position'         => 'right',
						'has_offcanvas_close_trigger' => 'yes',
					),
				),
			),
		),
	),
);
set_theme_mod( 'header_placements', $header_placements );

$footer_placements = array(
	'current_section' => 'type-1',
	'sections'        => array(
		array(
			'id'       => 'type-1',
			'mode'     => 'columns',
			'rows'     => array(
				array(
					'id'      => 'top-row',
					'columns' => array(
						array(),
						array(),
					),
				),
				array(
					'id'      => 'middle-row',
					'columns' => array(
						array( 'widget-area-1' ),
						array( 'widget-area-2' ),
						array( 'widget-area-3' ),
					),
				),
				array(
					'id'      => 'bottom-row',
					'columns' => array(
						array( 'copyright' ),
						array( 'menu' ),
					),
				),
			),
			'items'    => array(
				array(
					'id'     => 'middle-row',
					'values' => array(
						'items_per_row'    => '3',
						'3_columns_layout' => array(
							'desktop' => '2fr 1fr 1fr',
							'tablet'  => '2fr 1fr 1fr',
							'mobile'  => 'initial',
						),
					),
				),
				array(
					'id'     => 'bottom-row',
					'values' => array(
						'items_per_row'    => '2',
						'2_columns_layout' => array(
							'desktop' => 'repeat(2, 1fr)',
							'tablet'  => 'repeat(2, 1fr)',
							'mobile'  => 'initial',
						),
					),
				),
				array(
					'id'     => 'copyright',
					'values' => array(
						'copyright_text' => '© {current_year} Jannie Nguyen Academy. Tất cả quyền được bảo lưu.',
					),
				),
				array(
					'id'     => 'menu',
					'values' => array(
						'menu' => $legal_menu_id,
					),
				),
			),
			'settings' => array(),
		),
	),
);
set_theme_mod( 'footer_placements', $footer_placements );

echo "=== [7/8] Exporting theme_mods_jannie ===\n";
$mods = get_theme_mods();
ksort( $mods );
file_put_contents(
	ABSPATH . 'theme-mods-export.json',
	wp_json_encode( $mods, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n"
);

echo "=== [8/8] Flushing Rewrite Rules & Caches ===\n";
delete_transient( 'blocksy_dynamic_styles_descriptor' );
if ( function_exists( 'blocksy_manager' ) && isset( blocksy_manager()->db ) ) {
	blocksy_manager()->db->wipe_cache();
}
do_action( 'blocksy:dynamic-css:refresh-caches' );
flush_rewrite_rules();
wp_cache_flush();
echo "=== JN STEP 2 Provisioning Complete ===\n";
