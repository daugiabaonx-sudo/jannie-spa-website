<?php
/**
 * Import the 5 Coaching wireframe placeholder images into the local WordPress Media Library.
 *
 * Ensures:
 * - Zero hot-linking to lh3.googleusercontent.com
 * - Titles prefixed with "PLACEHOLDER – "
 * - Explicit dimensions (width & height) and optimized WebP format (LCP image <= 150 KB)
 * - Vietnamese alt text derived faithfully from wireframe data-alt attributes
 * - Sources fetched at `=s0` (or from a 4x AI master) and cropped inside `source_region`,
 *   see `lib/jn-placeholder-media.php`; item definitions live in `lib/jn-step3-media.json`
 *
 * Usage: ./scripts/wp.sh eval-file scripts/import-step-3-media.php [<key>...]
 *   e.g. ./scripts/wp.sh eval-file /scripts/import-step-3-media.php hero_portrait expert_consultation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

// Destructive local-only script: refuse to run against production (review 2026-10-28, H2).
require_once __DIR__ . '/lib/jn-env-guard.php';
jn_require_non_production( basename( __FILE__ ) );

require_once __DIR__ . '/lib/jn-placeholder-media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$items     = jn_placeholder_media_items();
$only_keys = isset( $args ) && is_array( $args ) ? array_values( array_filter( $args ) ) : array();
$unknown   = array_diff( $only_keys, array_keys( $items ) );
if ( ! empty( $unknown ) ) {
	echo '[ERROR] Unknown media key(s): ' . implode( ', ', $unknown ) . "\n";
	exit( 1 );
}
if ( ! empty( $only_keys ) ) {
	$items = array_intersect_key( $items, array_flip( $only_keys ) );
}

$upload_dir = wp_upload_dir();
$target_dir = trailingslashit( $upload_dir['path'] );
wp_mkdir_p( $target_dir );

$media_map = array();
$failures  = 0;

foreach ( $items as $key => $item ) {
	// Never overwrite a good image with a blank canvas: skip the item when the source is unavailable.
	$source = jn_placeholder_load_source( $item );
	if ( is_wp_error( $source ) ) {
		echo sprintf( "[ERROR] %s -> %s\n", $key, $source->get_error_message() );
		++$failures;
		continue;
	}

	$dest_file = $target_dir . $item['filename'];
	$rendered  = jn_placeholder_render( $source['image'], $item );
	imagedestroy( $source['image'] );
	$is_written = imagewebp( $rendered['image'], $dest_file, (int) $item['quality'] );
	imagedestroy( $rendered['image'] );
	if ( ! $is_written ) {
		echo sprintf( "[ERROR] %s -> could not write %s\n", $key, $dest_file );
		++$failures;
		continue;
	}

	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'name'           => $item['slug'],
			'posts_per_page' => 1,
			'post_status'    => 'any',
		)
	);

	if ( ! empty( $existing ) ) {
		$attach_id = (int) $existing[0]->ID;
		wp_update_post(
			array(
				'ID'             => $attach_id,
				'post_title'     => $item['title'],
				'post_name'      => $item['slug'],
				'post_mime_type' => 'image/webp',
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
		$attach_id  = wp_insert_attachment( $attachment, $dest_file );
	}

	$regenerated = jn_regenerate_attachment_with_avif( $attach_id );
	if ( is_wp_error( $regenerated ) ) {
		echo sprintf( "[ERROR] %s -> %s\n", $key, $regenerated->get_error_message() );
		++$failures;
		continue;
	}
	update_post_meta( $attach_id, '_wp_attachment_image_alt', $item['alt'] );
	update_post_meta( $attach_id, '_jn_is_placeholder', '1' );
	update_post_meta( $attach_id, '_jn_media_source', $source['origin'] );
	update_post_meta( $attach_id, '_jn_media_scale', (string) $rendered['scale'] );

	$url         = wp_get_attachment_url( $attach_id );
	$filesize_kb = round( filesize( $dest_file ) / 1024, 1 );

	$media_map[ $key ] = array(
		'id'          => $attach_id,
		'url'         => $url,
		'title'       => $item['title'],
		'alt'         => $item['alt'],
		'width'       => (int) $item['width'],
		'height'      => (int) $item['height'],
		'filesize_kb' => $filesize_kb,
	);

	echo sprintf(
		"[OK] %s -> ID=%d | %dx%d | %s KB | source=%s scale=%sx | %s\n",
		$key,
		$attach_id,
		$item['width'],
		$item['height'],
		$filesize_kb,
		$source['origin'],
		$rendered['scale'],
		$url
	);
}

// Merge so a partial run (selected keys) keeps the other items' entries.
$previous_map = get_option( 'jn_step3_media_map', array() );
update_option( 'jn_step3_media_map', array_merge( is_array( $previous_map ) ? $previous_map : array(), $media_map ), false );

if ( $failures > 0 ) {
	echo "[ERROR] {$failures} item(s) failed; their existing files were left untouched.\n";
	exit( 1 );
}
