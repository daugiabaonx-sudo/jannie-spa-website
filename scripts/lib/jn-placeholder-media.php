<?php
/**
 * Shared helpers for the 5 Step 3 placeholder images (used by `import-step-3-media.php`,
 * `build-step-8.php` and `test-placeholder-media.php`).
 *
 * Source quality rules:
 * - lh3.googleusercontent.com serves a 512px rendition unless the URL asks for `=s0` (1408x768 original).
 * - Items with `ai_upscale` prefer a 4x Real-ESRGAN master made on the host by
 *   `node scripts/upscale-step-3-media.mjs`, so the final WebP is downscaled, never stretched.
 * - `source_region` (fractions of the source) keeps the crop inside the photo when the Stitch
 *   source image is a picture of a wireframe with UI chrome around the photo.
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const JN_PLACEHOLDER_ITEMS_FILE   = __DIR__ . '/jn-step3-media.json';
const JN_PLACEHOLDER_MASTER_DIR   = __DIR__ . '/../.media-masters';
const JN_PLACEHOLDER_HTTP_TIMEOUT = 30;
const JN_PLACEHOLDER_AVIF_QUALITY = 50;

/**
 * Return the Step 3 placeholder item definitions keyed by media-map key.
 *
 * @return array<string, array<string, mixed>>
 * @throws RuntimeException When the definitions file is missing or invalid.
 */
function jn_placeholder_media_items(): array {
	$json  = is_file( JN_PLACEHOLDER_ITEMS_FILE ) ? (string) file_get_contents( JN_PLACEHOLDER_ITEMS_FILE ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$items = json_decode( $json, true );
	if ( ! is_array( $items ) || empty( $items ) ) {
		throw new RuntimeException( 'Invalid or missing placeholder media definitions: ' . JN_PLACEHOLDER_ITEMS_FILE );
	}
	return $items;
}

/**
 * Ask lh3.googleusercontent.com for the original rendition instead of its 512px default.
 *
 * @param string $url Source URL, with or without a `=size` suffix.
 * @return string
 */
function jn_placeholder_source_url( string $url ): string {
	return (string) preg_replace( '#=[^/=]*$#', '', $url ) . '=s0';
}

/**
 * Path of the host-generated 4x AI master for an item slug.
 *
 * @param string $slug Item slug.
 * @return string
 */
function jn_placeholder_master_path( string $slug ): string {
	return JN_PLACEHOLDER_MASTER_DIR . '/' . $slug . '.x4.png';
}

/**
 * Largest crop with the target aspect ratio inside a source region, centred on `$focus_x`.
 *
 * @param int                  $src_w   Source width.
 * @param int                  $src_h   Source height.
 * @param int                  $dst_w   Target width.
 * @param int                  $dst_h   Target height.
 * @param array<string, float> $region  Fractional region {x, y, w, h}; empty for the whole image.
 * @param float                $focus_x Horizontal focus point as a fraction of the source width.
 * @return array{x: int, y: int, w: int, h: int}
 */
function jn_placeholder_crop_box( int $src_w, int $src_h, int $dst_w, int $dst_h, array $region = array(), float $focus_x = 0.5 ): array {
	$region = array_merge( array( 'x' => 0.0, 'y' => 0.0, 'w' => 1.0, 'h' => 1.0 ), $region );
	$rx     = (int) round( $region['x'] * $src_w );
	$ry     = (int) round( $region['y'] * $src_h );
	$rw     = (int) round( $region['w'] * $src_w );
	$rh     = (int) round( $region['h'] * $src_h );

	$dst_ratio = $dst_w / $dst_h;
	if ( $rw / $rh > $dst_ratio ) {
		$h = $rh;
		$w = (int) round( $rh * $dst_ratio );
	} else {
		$w = $rw;
		$h = (int) round( $rw / $dst_ratio );
	}

	$x = (int) round( $focus_x * $src_w - $w / 2 );
	$x = max( $rx, min( $x, $rx + $rw - $w ) );
	$y = $ry + (int) round( ( $rh - $h ) / 2 );

	return array(
		'x' => $x,
		'y' => $y,
		'w' => $w,
		'h' => $h,
	);
}

/**
 * Load an item's source image: the 4x AI master when present, else the `=s0` original.
 *
 * @param array<string, mixed> $item Item definition.
 * @return array{image: GdImage, origin: string}|WP_Error
 */
function jn_placeholder_load_source( array $item ) {
	$master = jn_placeholder_master_path( (string) $item['slug'] );
	if ( ! empty( $item['ai_upscale'] ) && is_file( $master ) ) {
		$image = @imagecreatefrompng( $master ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $image ) {
			return new WP_Error( 'jn_master_unreadable', "AI master is not a readable PNG: {$master}" );
		}
		return array(
			'image'  => $image,
			'origin' => 'ai-x4',
		);
	}

	$url      = jn_placeholder_source_url( (string) $item['source_url'] );
	$response = wp_remote_get( $url, array( 'timeout' => JN_PLACEHOLDER_HTTP_TIMEOUT ) );
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = (string) wp_remote_retrieve_body( $response );
	if ( 200 !== $code || '' === $body ) {
		return new WP_Error( 'jn_source_http', "Source download failed (HTTP {$code}): {$url}" );
	}
	$image = @imagecreatefromstring( $body ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	if ( ! $image ) {
		return new WP_Error( 'jn_source_decode', "Source is not a decodable image: {$url}" );
	}
	return array(
		'image'  => $image,
		'origin' => 's0',
	);
}

/**
 * Crop a source image to the item's region/ratio and resample it to the target size.
 *
 * @param GdImage              $src  Source image.
 * @param array<string, mixed> $item Item definition.
 * @return array{image: GdImage, scale: float} `scale` > 1 means the crop was enlarged.
 */
function jn_placeholder_render( GdImage $src, array $item ): array {
	$dst_w = (int) $item['width'];
	$dst_h = (int) $item['height'];
	$box   = jn_placeholder_crop_box(
		imagesx( $src ),
		imagesy( $src ),
		$dst_w,
		$dst_h,
		(array) ( $item['source_region'] ?? array() ),
		(float) ( $item['focus_x'] ?? 0.5 )
	);

	$canvas = imagecreatetruecolor( $dst_w, $dst_h );
	imagecopyresampled( $canvas, $src, 0, 0, $box['x'], $box['y'], $dst_w, $dst_h, $box['w'], $box['h'] );

	return array(
		'image' => $canvas,
		'scale' => round( $dst_w / $box['w'], 3 ),
	);
}

/**
 * Regenerate an attachment's sub-sizes (AVIF via `jn-performance`) plus a full-size `.avif`
 * companion next to the `.webp` original, keeping `file` pointed at the WebP fallback.
 *
 * @param int $att_id Attachment ID.
 * @return array{id: int, file: string, sizes: array<string, array<string, mixed>>}|WP_Error
 */
function jn_regenerate_attachment_with_avif( int $att_id ) {
	$file = get_attached_file( $att_id );
	if ( ! $file || ! is_file( $file ) ) {
		return new WP_Error( 'jn_attachment_missing', "Attachment {$att_id} has no file on disk." );
	}

	// Ensure get_attached_file points to the original .webp file before regenerating sub-sizes.
	if ( str_ends_with( $file, '.avif' ) ) {
		$webp_candidate = (string) preg_replace( '/\.avif$/i', '.webp', $file );
		if ( is_file( $webp_candidate ) ) {
			$file = $webp_candidate;
			update_attached_file( $att_id, $file );
		}
	}

	// Regenerate metadata & sub-sizes (creates AVIF sub-sizes via `jn_filter_image_editor_output_format`).
	$new_meta = wp_generate_attachment_metadata( $att_id, $file );
	if ( ! is_array( $new_meta ) || empty( $new_meta['sizes'] ) ) {
		return new WP_Error( 'jn_metadata_failed', "Sub-size generation failed for attachment {$att_id}." );
	}

	// Keep primary `file` pointing to the original `.webp` fallback so `<img src="...webp">` always matches `$image_meta['file']`.
	$rel_webp = _wp_relative_upload_path( $file );
	if ( $rel_webp ) {
		$new_meta['file']      = $rel_webp;
		$new_meta['mime-type'] = 'image/webp';
		update_attached_file( $att_id, $file );
	}

	// Also create a full-dimension .avif companion next to the original .webp file for full-width srcset.
	if ( function_exists( 'imagecreatefromwebp' ) && function_exists( 'imageavif' ) && str_ends_with( $file, '.webp' ) ) {
		$src_im = @imagecreatefromwebp( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( $src_im ) {
			imageavif( $src_im, (string) preg_replace( '/\.webp$/i', '.avif', $file ), JN_PLACEHOLDER_AVIF_QUALITY );
			imagedestroy( $src_im );
		}
	}

	wp_update_attachment_metadata( $att_id, $new_meta );

	$dir   = dirname( $file );
	$sizes = array();
	foreach ( $new_meta['sizes'] as $size_name => $size_data ) {
		$sub_path            = $dir . '/' . $size_data['file'];
		$sizes[ $size_name ] = array(
			'file'      => $size_data['file'],
			'width'     => $size_data['width'],
			'height'    => $size_data['height'],
			'mime-type' => $size_data['mime-type'] ?? '',
			'bytes'     => is_file( $sub_path ) ? filesize( $sub_path ) : 0,
		);
	}

	return array(
		'id'    => $att_id,
		'file'  => basename( $file ),
		'sizes' => $sizes,
	);
}
