<?php
/**
 * Import the 5 Coaching wireframe placeholder images into the local WordPress Media Library.
 *
 * Ensures:
 * - Zero hot-linking to lh3.googleusercontent.com
 * - Titles prefixed with "PLACEHOLDER – "
 * - Explicit dimensions (width & height) and optimized WebP format (LCP image <= 150 KB)
 * - Vietnamese alt text derived faithfully from wireframe data-alt attributes
 *
 * Usage: ./scripts/wp.sh eval-file scripts/import-step-3-media.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

// Destructive local-only script: refuse to run against production (review 2026-10-28, H2).
require_once __DIR__ . '/lib/jn-env-guard.php';
jn_require_non_production( basename( __FILE__ ) );

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$items = array(
	'hero_portrait'       => array(
		'slug'       => 'jn-placeholder-hero-portrait',
		'filename'   => 'jn-placeholder-hero-portrait.webp',
		'title'      => 'PLACEHOLDER – Chân dung nghệ thuật chuyên gia Jannie Nguyễn tại studio',
		'alt'        => 'Chân dung nghệ thuật đen trắng của chuyên gia thẩm mỹ Jannie Nguyễn trong không gian studio kiến trúc tối giản, trang phục linen màu kem nhạt dưới ánh sáng tự nhiên dịu nhẹ.',
		'source_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCouLm-77JEuBzI4EPtLdhj9HP_yllYUS1hOSuNPArJBH7SK8o2d5O557wW_oB-z7q5d0Dxe5zhUXp5jmfAQt71VYznJpRkUKbTnuA0y4z6kBgiUpd-ofw9wgRk-HNhTJHji_zQCRUGrau9Bt7jfvuTeFNpHxwB2aYGbda3xn3FiL18DzDbqCY2xSnmzvEDXRU2VFxGhHyDEz05bT4jY96RrQuWiFRjF_8_W1EMndWFFIv1ZLwFFKQ3nA',
		'width'      => 800,
		'height'     => 1000,
		'quality'    => 82,
	),
	'expert_consultation' => array(
		'slug'       => 'jn-placeholder-expert-consultation',
		'filename'   => 'jn-placeholder-expert-consultation.webp',
		'title'      => 'PLACEHOLDER – Chuyên gia Jannie Nguyễn tư vấn khách hàng tại viện thẩm mỹ',
		'alt'        => 'Chuyên gia Jannie Nguyễn trực tiếp tư vấn cho khách hàng trong viện thẩm mỹ ngập tràn ánh sáng tự nhiên ấm áp, dụng cụ tinh xảo sắp xếp gọn gàng trên mặt bàn đá cẩm thạch.',
		'source_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDmkj8tbSeFbInGDM8SNvocmQ8C7g33rgJZp9e29kxZ5xeHnHdKfAw2uYcoiH0WVq53fnedSqadfTHbY0weMel2Y6dS3kHkn93LA19BG4q9MiWh5-c-nl4A3XtDlKcvJvIrxRQ_QI0EqIGK9dhn8_Uxfk3VcoCcF-Pykf-hHBgtP6Lun1r252WkWsqc6ZVK-CerboMJvIeQnmLMP2AP4gm05gczTMxGYbKdO6ytJgH-aR8goGWI-TKD3Q',
		'width'      => 750,
		'height'     => 1000,
		'quality'    => 82,
	),
	'avatar_minh_thao'    => array(
		'slug'       => 'jn-placeholder-avatar-minh-thao',
		'filename'   => 'jn-placeholder-avatar-minh-thao.webp',
		'title'      => 'PLACEHOLDER – Chân dung học viên Minh Thảo',
		'alt'        => 'Chân dung Minh Thảo, nữ chuyên viên thẩm mỹ trẻ thành công với nụ cười tự tin rạng rỡ trong áo blouse trắng tối giản tại không gian salon.',
		'source_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBHahVwszajQfRB4hzVkbYqo05w1UMmuQGW-cwANgrzGTeH6J5YVKo6Oo1_NimUsrsl35PhtyVuOA4hiCOe6ATwzOweoTsoF6656dL8v6mOkYMTTUpYDgKw-f41NgQZwKLF4A_5LcU6VKUFQYplW_harPELikoFxfaQ_F5VssIiKsYJVF6agBQusjL2SxYgNtjPdsICNK91XqfLD2EYB1nLCi3XQibeSv8z8zZuJKZRr3ZgNMYxE7GAyw',
		'width'      => 160,
		'height'     => 160,
		'quality'    => 84,
	),
	'avatar_ngoc_bich'    => array(
		'slug'       => 'jn-placeholder-avatar-ngoc-bich',
		'filename'   => 'jn-placeholder-avatar-ngoc-bich.webp',
		'title'      => 'PLACEHOLDER – Chân dung học viên Ngọc Bích',
		'alt'        => 'Chân dung Ngọc Bích, nữ doanh nhân thanh lịch trong trang phục chỉn chu đứng tại sảnh viện thẩm mỹ hiện đại cao cấp.',
		'source_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDqXxpT8_3zQ6cbF1HuaUzzAMiMeiWQxsNs7GGsiPpbYIgOoiRfellHYwygJXkOyaaKkfyuaXcv3GhVlvHE3LWQovIXkNsokgdx2eSKfX9cT06XvPhSpOBI4RTqm2RE7Gr1xPq4FZBlKtbZzF4AuMqj6kX89xOXlpXQCvwnxCsuRWKcjP-aW8hWfpSqbFekxtZ1P7LlGgWj4VM808W1JCq_SWH4rdzeq6P-Nh0mKnCu0FGlV3B-UgaCYQ',
		'width'      => 160,
		'height'     => 160,
		'quality'    => 84,
	),
	'avatar_phuong_uyen'  => array(
		'slug'       => 'jn-placeholder-avatar-phuong-uyen',
		'filename'   => 'jn-placeholder-avatar-phuong-uyen.webp',
		'title'      => 'PLACEHOLDER – Chân dung học viên Phương Uyên',
		'alt'        => 'Chân dung cận cảnh Phương Uyên rạng rỡ với làn da tự nhiên căng bóng khỏe khoắn và nụ cười dịu dàng trong không gian tối giản.',
		'source_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAu3AamKMzr8NFmmqlnEmyjGjp-hPQIRyF-ch7bgz_YRjNCHSV1b6PJkjsVWUW82Lu7LPjwoHmn2urqsSEYrcYzMvkenPKlVpeBAFq9uoDmFYw49XqZXipaz5rsgWbttTTf94mTjYGap7P5_X_BjQ8BL6AQxjnSddkv4dyauAhWjodNQsuyV2KcmLCGpMu2wXAWnZKWCvziFaXoXon9MxIzdDJ1oWbUSv8d7_hq8HeWkvAPgnYiqZ7g-Q',
		'width'      => 160,
		'height'     => 160,
		'quality'    => 84,
	),
);

$upload_dir = wp_upload_dir();
$target_dir = trailingslashit( $upload_dir['path'] );
wp_mkdir_p( $target_dir );

$media_map = array();

foreach ( $items as $key => $item ) {
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'name'           => $item['slug'],
			'posts_per_page' => 1,
			'post_status'    => 'any',
		)
	);

	$dest_file = $target_dir . $item['filename'];

	// Download and center-crop to exact target dimensions in WebP.
	$response = wp_remote_get(
		$item['source_url'],
		array(
			'timeout' => 25,
		)
	);

	$src_img = false;
	if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
		$body = wp_remote_retrieve_body( $response );
		if ( ! empty( $body ) ) {
			$src_img = @imagecreatefromstring( $body );
		}
	}

	$dst_w = (int) $item['width'];
	$dst_h = (int) $item['height'];
	$canvas = imagecreatetruecolor( $dst_w, $dst_h );

	if ( $src_img ) {
		$src_w = imagesx( $src_img );
		$src_h = imagesy( $src_img );
		$src_ratio = $src_w / $src_h;
		$dst_ratio = $dst_w / $dst_h;

		if ( $src_ratio > $dst_ratio ) {
			$crop_h = $src_h;
			$crop_w = (int) round( $src_h * $dst_ratio );
			$src_x  = (int) round( ( $src_w - $crop_w ) / 2 );
			$src_y  = 0;
		} else {
			$crop_w = $src_w;
			$crop_h = (int) round( $src_w / $dst_ratio );
			$src_x  = 0;
			$src_y  = (int) round( ( $src_h - $crop_h ) / 2 );
		}

		imagecopyresampled( $canvas, $src_img, 0, 0, $src_x, $src_y, $dst_w, $dst_h, $crop_w, $crop_h );
		imagedestroy( $src_img );
	} else {
		// Fallback editorial gradient if offline.
		$bg = imagecolorallocate( $canvas, 243, 239, 234 );
		imagefilledrectangle( $canvas, 0, 0, $dst_w, $dst_h, $bg );
	}

	imagewebp( $canvas, $dest_file, (int) $item['quality'] );
	imagedestroy( $canvas );

	if ( ! empty( $existing ) ) {
		$attach_id = (int) $existing[0]->ID;
		wp_update_post(
			array(
				'ID'           => $attach_id,
				'post_title'   => $item['title'],
				'post_name'    => $item['slug'],
				'post_Mime_type' => 'image/webp',
			)
		);
		update_attached_file( $attach_id, $dest_file );
	} else {
		$attachment = array(
			'guid'           => trailingslashit( $upload_dir['url'] ) . $item['filename'],
			'post_mime_type' => 'image/webp',
			'post_title'     => $item['title'],
			'post_name'      => $item['slug'],
			'post_content'   => '',
			'post_status'    => 'inherit',
		);
		$attach_id = wp_insert_attachment( $attachment, $dest_file );
	}

	$attach_data = wp_generate_attachment_metadata( $attach_id, $dest_file );
	wp_update_attachment_metadata( $attach_id, $attach_data );
	update_post_meta( $attach_id, '_wp_attachment_image_alt', $item['alt'] );
	update_post_meta( $attach_id, '_jn_is_placeholder', '1' );

	$url = wp_get_attachment_url( $attach_id );
	$filesize_kb = round( filesize( $dest_file ) / 1024, 1 );

	$media_map[ $key ] = array(
		'id'          => $attach_id,
		'url'         => $url,
		'title'       => $item['title'],
		'alt'         => $item['alt'],
		'width'       => $dst_w,
		'height'      => $dst_h,
		'filesize_kb' => $filesize_kb,
	);

	echo sprintf(
		"[OK] %s -> ID=%d | %dx%d | %s KB | %s\n",
		$key,
		$attach_id,
		$dst_w,
		$dst_h,
		$filesize_kb,
		$url
	);
}

update_option( 'jn_step3_media_map', $media_map, false );
