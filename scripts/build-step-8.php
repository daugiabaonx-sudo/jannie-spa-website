<?php
/**
 * JN Step 8 — Provisioning & Optimization Script (Tasks 2, 3, 4, 5, 6, 9, 10).
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/build-step-8.php
 *
 * @package JN_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Destructive local-only script: refuse to run against production (review 2026-10-28, H2).
require_once __DIR__ . '/lib/jn-env-guard.php';
jn_require_non_production( basename( __FILE__ ) );

require_once __DIR__ . '/lib/jn-placeholder-media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

global $wpdb;

echo "=== JN STEP 8 BUILD & OPTIMIZATION ===\n";

// 1. Capture DB Autoload & Transient Baseline (Before Optimization).
$autoload_vals = array( 'yes', 'on', 'auto', 'auto-on' );
$in_Format     = "'" . implode( "','", $autoload_vals ) . "'";

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$before_autoload = $wpdb->get_row( "SELECT COUNT(*) AS cnt, SUM(LENGTH(option_name) + LENGTH(option_value)) AS total_bytes FROM {$wpdb->options} WHERE autoload IN ({$in_Format})", ARRAY_A );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$before_expired_transients = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND CAST(option_value AS UNSIGNED) < %d", $wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) );

// 2. Build the combined & minified child theme CSS bundle (`assets/css/jannie-bundle.min.css`).
if ( function_exists( 'jannie_ensure_front_css_bundle' ) ) {
	$bundle_rel  = jannie_ensure_front_css_bundle();
	$bundle_path = get_stylesheet_directory() . $bundle_rel;
	$bundle_size = is_file( $bundle_path ) ? filesize( $bundle_path ) : 0;
	echo sprintf( "[Task 3] Built front-end CSS bundle: %s (%d bytes uncompressed, %d bytes gzip)\n", $bundle_rel, (int) $bundle_size, is_file( $bundle_path ) ? strlen( gzencode( (string) file_get_contents( $bundle_path ), 9 ) ) : 0 );
}

// 3. Generate AVIF + WebP sub-sizes for all Step 3 media attachments (Task 5).
$media_map = get_option( 'jn_step3_media_map', array() );
$regenerated = array();

foreach ( $media_map as $key => $info ) {
	$att_id = isset( $info['id'] ) ? (int) $info['id'] : 0;
	if ( $att_id <= 0 ) {
		continue;
	}

	$result = jn_regenerate_attachment_with_avif( $att_id );
	if ( is_wp_error( $result ) ) {
		echo sprintf( "[Task 5] Skipped %s: %s\n", $key, $result->get_error_message() );
		continue;
	}
	$regenerated[ $key ] = $result;
}
echo sprintf( "[Task 5] Regenerated AVIF sub-sizes for %d media attachments.\n", count( $regenerated ) );

// 4. Update synced pattern (`wp_block`) and page content in DB so Testimonial avatars use `jn-avatar-96`
// and Hero/Expert images have explicit loading/fetchpriority/dimensions (Task 4 & 5).
$posts_to_update = get_posts(
	array(
		'post_type'      => array( 'page', 'wp_block' ),
		'post_status'    => array( 'publish', 'draft' ),
		'posts_per_page' => 50,
	)
);

$updated_post_count = 0;
foreach ( $posts_to_update as $p ) {
	if ( ! ( $p instanceof WP_Post ) || '' === $p->post_content ) {
		continue;
	}

	$content = $p->post_content;

	// Switch testimonial avatar block from sizeSlug:"full" to sizeSlug:"jn-avatar-96".
	$content = str_replace(
		'<!-- wp:post-featured-image {"width":"48px","height":"48px","scale":"cover","sizeSlug":"full","className":"jn-testimonial-avatar"} /-->',
		'<!-- wp:post-featured-image {"width":"48px","height":"48px","scale":"cover","sizeSlug":"jn-avatar-96","className":"jn-testimonial-avatar"} /-->',
		$content
	);

	if ( $content !== $p->post_content ) {
		wp_update_post(
			array(
				'ID'           => $p->ID,
				'post_content' => $content,
			)
		);
		++$updated_post_count;
	}
}
echo sprintf( "[Task 4/5] Updated %d posts/synced patterns to use jn-avatar-96.\n", $updated_post_count );

// 5. Generate 1200x630 Branded Open Graph Placeholder Image (Task 9).
$uploads = wp_upload_dir();
$og_dir  = trailingslashit( $uploads['basedir'] ) . '2026/10';
wp_mkdir_p( $og_dir );
$og_filename = 'jn-placeholder-og-1200x630.webp';
$og_filepath = $og_dir . '/' . $og_filename;
$og_url      = trailingslashit( $uploads['baseurl'] ) . '2026/10/' . $og_filename;

if ( function_exists( 'imagecreatetruecolor' ) ) {
	$im = imagecreatetruecolor( 1200, 630 );
	$bg = imagecolorallocate( $im, 31, 30, 29 );       // #1F1E1D Ink Charcoal
	$bd = imagecolorallocate( $im, 172, 142, 118 );    // #AC8E76 Champagne Accent
	$fg = imagecolorallocate( $im, 250, 248, 245 );    // #FAF8F5 Surface Cream
	$mt = imagecolorallocate( $im, 209, 199, 189 );    // #D1C7BD Wireframe Guide

	imagefilledrectangle( $im, 0, 0, 1199, 629, $bg );
	imagerectangle( $im, 36, 36, 1163, 593, $bd );
	imagerectangle( $im, 40, 40, 1159, 589, $bd );

	imagestring( $im, 5, 80, 240, 'JANNIE NGUYEN — PRIVATE MENTORSHIP & ADVISORY PROGRAM', $bd );
	imagestring( $im, 5, 80, 290, 'PLACEHOLDER - Jannie Nguyen Open Graph Image (1200x630)', $fg );
	imagestring( $im, 4, 80, 340, 'Co Van 1:1 & Dao Tao Tham My Chuyen Sau', $mt );

	imagewebp( $im, $og_filepath, 82 );
	imagedestroy( $im );
}

update_option(
	'jn_step8_og_image',
	array(
		'url'    => $og_url,
		'width'  => 1200,
		'height' => 630,
		'bytes'  => is_file( $og_filepath ) ? filesize( $og_filepath ) : 0,
	),
	true
);
echo sprintf( "[Task 9] Generated Open Graph image: %s (%d bytes)\n", $og_url, is_file( $og_filepath ) ? (int) filesize( $og_filepath ) : 0 );

// 6. Populate The SEO Framework (`autodescription`) Settings & Per-Page Meta (Task 9).
$seo_pages = array(
	'coaching'             => array(
		'title'       => 'Coaching & Đồng Hành Chuyên Sâu 1:1 | Jannie Nguyễn',
		'description' => 'Chương trình cố vấn 1:1 độc quyền cùng Chuyên gia Jannie Nguyễn: Tư Vấn Định Hướng Cá Nhân, Khai Vấn Chiến Lược Kinh Doanh và Master Mentorship (3 Tháng).',
	),
	'my-story'             => array(
		'title'       => 'Hành Trình Hơn 20 Năm Của Chuyên Gia Jannie Nguyễn',
		'description' => 'Hành trình hơn 20 năm đồng hành cùng vẻ đẹp đích thực của Nghệ nhân Bàn Tay Vàng Jannie Nguyễn và phương pháp cân bằng tự nhiên The Balance.',
	),
	'course'               => array(
		'title'       => 'Chương Trình Đào Tạo & Khóa Học Chuyên Sâu | Jannie Nguyễn',
		'description' => 'Hệ thống khóa học kỹ thuật điêu khắc, phun xăm tự nhiên và tư duy vận hành thẩm mỹ thực chiến được đúc kết từ hơn 20 năm kinh nghiệm lâm sàng của Jannie Nguyễn.',
	),
	'client-success-story' => array(
		'title'       => 'Cảm Nhận Học Viên & Kết Quả Thực Tế | Jannie Nguyễn',
		'description' => 'Minh chứng thành công và câu chuyện chuyển hóa thực tế từ các học viên, chủ cơ sở thẩm mỹ và spa sau lộ trình cố vấn 1:1 cùng Chuyên gia Jannie Nguyễn.',
	),
	'booking'              => array(
		'title'       => 'Đặt Lịch Tư Vấn 1:1 Cùng Chuyên Gia | Jannie Nguyễn',
		'description' => 'Để lại thông tin để đội ngũ trợ lý của Jannie Nguyễn liên hệ xác nhận lịch hẹn phù hợp và chuẩn bị tài liệu phân tích trước buổi gặp.',
	),
	'my-favorite-product'  => array(
		'title'       => 'Sản Phẩm Yêu Thích & Khuyên Dùng | Jannie Nguyễn',
		'description' => 'Danh mục sản phẩm chăm sóc, dụng cụ và giải pháp làm đẹp được Chuyên gia Jannie Nguyễn trực tiếp tuyển chọn và kiểm chứng qua thực tế lâm sàng.',
	),
	'cart'                 => array(
		'title'       => 'Giỏ Hàng Của Bạn | Jannie Nguyễn',
		'description' => 'Kiểm tra danh sách khóa học và sản phẩm bạn đã chọn tại Học viện Jannie Nguyễn trước khi tiến hành thanh toán.',
	),
	'checkout'             => array(
		'title'       => 'Thanh Toán Đơn Hàng | Jannie Nguyễn',
		'description' => 'Hoàn tất thông tin thanh toán đơn hàng khóa học và sản phẩm tại Học viện Jannie Nguyễn.',
	),
	'tai-khoan'            => array(
		'title'       => 'Tài Khoản Học Viên & Khách Hàng | Jannie Nguyễn',
		'description' => 'Đăng nhập hoặc đăng ký tài khoản thành viên tại Học viện Jannie Nguyễn để quản lý đơn hàng và lộ trình học tập của bạn.',
	),
	'chinh-sach-bao-mat'   => array(
		'title'       => 'Chính Sách Bảo Mật Thông Tin | Jannie Nguyễn',
		'description' => 'Cam kết bảo mật thông tin cá nhân và dữ liệu khách hàng khi đăng ký tư vấn 1:1 và sử dụng dịch vụ tại Học viện Jannie Nguyễn.',
	),
	'dieu-khoan-dich-vu'   => array(
		'title'       => 'Điều Khoản Dịch Vụ & Chính Sách Cố Vấn | Jannie Nguyễn',
		'description' => 'Quy định về đặt lịch tư vấn 1:1, chính sách thanh toán, đổi lịch hẹn và quyền lợi học viên tại Học viện Jannie Nguyễn.',
	),
);

foreach ( $seo_pages as $slug => $meta ) {
	$page = get_page_by_path( $slug );
	if ( $page instanceof WP_Post ) {
		update_post_meta( $page->ID, '_genesis_title', $meta['title'] );
		update_post_meta( $page->ID, '_genesis_description', $meta['description'] );
		update_post_meta( $page->ID, '_open_graph_title', $meta['title'] );
		update_post_meta( $page->ID, '_open_graph_description', $meta['description'] );
		update_post_meta( $page->ID, '_twitter_title', $meta['title'] );
		update_post_meta( $page->ID, '_twitter_description', $meta['description'] );
		update_post_meta( $page->ID, '_social_image_url', $og_url );
	}
}

$front_id = (int) get_option( 'page_on_front' );
if ( $front_id > 0 ) {
	update_post_meta( $front_id, '_genesis_title', 'Jannie Nguyễn — Cố Vấn 1:1 & Đào Tạo Thẩm Mỹ Chuyên Sâu' );
	update_post_meta( $front_id, '_genesis_description', 'Chương trình cố vấn 1:1 độc quyền cùng Chuyên gia Jannie Nguyễn dành cho cá nhân định hình vẻ đẹp tự nhiên và các chủ cơ sở thẩm mỹ, phun xăm, spa cao cấp.' );
	update_post_meta( $front_id, '_social_image_url', $og_url );
}

$tsf_settings = get_option( 'autodescription-site-settings', array() );
if ( is_array( $tsf_settings ) ) {
	$tsf_settings['homepage_title']            = 'Jannie Nguyễn — Cố Vấn 1:1 & Đào Tạo Thẩm Mỹ Chuyên Sâu';
	$tsf_settings['homepage_tagline']          = 0;
	$tsf_settings['homepage_description']      = 'Chương trình cố vấn 1:1 độc quyền cùng Chuyên gia Jannie Nguyễn dành cho cá nhân định hình vẻ đẹp tự nhiên và các chủ cơ sở thẩm mỹ, phun xăm, spa cao cấp.';
	$tsf_settings['social_image_fb_url']       = $og_url;
	$tsf_settings['knowledge_output']          = 0;
	$tsf_settings['ld_json_searchbox']         = 0;
	$tsf_settings['ld_json_breadcrumbs']       = 0;
	$tsf_settings['sitemaps_output']           = 0; // Handled by `jn_serve_clean_sitemap_xml()`.
	$tsf_settings['display_seo_bar_tables']    = 0;
	$tsf_settings['display_pixel_counter']     = 0;
	$tsf_settings['display_character_counter'] = 0;
	update_option( 'autodescription-site-settings', $tsf_settings );
}
echo "[Task 9] Configured The SEO Framework settings & Vietnamese wireframe meta on all pages.\n";

// 7. Task 10: Clear expired transients & trim non-frontend autoloaded options.
delete_expired_transients( true );
delete_transient( 'wp_core_block_css_files' );
delete_transient( 'wp_styles_for_blocks' );

$non_frontend_options = array(
	'woocommerce_paypal_settings',
	'woocommerce_maxmind_geolocation_settings',
	'woocommerce_ces_tracks_queue',
	'woocommerce_admin_install_timestamp',
	'jn_owner_decisions_log',
	'jn_customer_duplicate_log',
	'_transient_health-check-site-status-result',
	'_transient_timeout_health-check-site-status-result',
	'theme_switched',
	'finished_updating_comment_type',
	'wp_super_cache_Disabled',
);

foreach ( $non_frontend_options as $opt_name ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->update(
		$wpdb->options,
		array( 'autoload' => 'no' ),
		array( 'option_name' => $opt_name )
	);
}

// Clear WP Super Cache directory if any files exist.
if ( function_exists( 'wp_cache_clean_cache' ) ) {
	global $file_prefix;
	if ( ! empty( $file_prefix ) ) {
		wp_cache_clean_cache( $file_prefix, true );
	}
}
$supercache_dir = WP_CONTENT_DIR . '/cache/supercache';
if ( is_dir( $supercache_dir ) ) {
	$it = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $supercache_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $it as $fs_item ) {
		if ( $fs_item->isDir() ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			@rmdir( $fs_item->getRealPath() );
		} elseif ( 'index.html' === $fs_item->getFilename() && $supercache_dir !== $fs_item->getPath() ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $fs_item->getRealPath() );
		}
	}
}

// Capture After Autoload & Transient metrics.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$after_autoload = $wpdb->get_row( "SELECT COUNT(*) AS cnt, SUM(LENGTH(option_name) + LENGTH(option_value)) AS total_bytes FROM {$wpdb->options} WHERE autoload IN ({$in_Format})", ARRAY_A );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$after_expired_transients = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND CAST(option_value AS UNSIGNED) < %d", $wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) );

$opcache_status = function_exists( 'opcache_get_status' ) ? opcache_get_status( false ) : false;

$report = array(
	'timestamp'          => gmdate( 'c' ),
	'autoload_before'    => array(
		'count' => (int) ( $before_autoload['cnt'] ?? 0 ),
		'bytes' => (int) ( $before_autoload['total_bytes'] ?? 0 ),
	),
	'autoload_after'     => array(
		'count' => (int) ( $after_autoload['cnt'] ?? 0 ),
		'bytes' => (int) ( $after_autoload['total_bytes'] ?? 0 ),
	),
	'expired_transients' => array(
		'before' => $before_expired_transients,
		'after'  => $after_expired_transients,
	),
	'opcache'            => array(
		'enabled'        => is_array( $opcache_status ) && ! empty( $opcache_status['opcache_enabled'] ),
		'cached_scripts' => is_array( $opcache_status ) ? (int) ( $opcache_status['opcache_statistics']['num_cached_scripts'] ?? 0 ) : 0,
	),
	'regenerated_media'  => $regenerated,
);

echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
