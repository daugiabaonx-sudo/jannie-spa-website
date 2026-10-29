<?php
/**
 * Shared helpers for imported media: the 5 Step 3 placeholder images (`import-step-3-media.php`,
 * `build-step-8.php`, `test-placeholder-media.php`) and the 14 legacy jannieedu.com images
 * (`import-legacy-media.php`, `test-legacy-media.php`).
 *
 * Source quality rules:
 * - lh3.googleusercontent.com serves a 512px rendition unless the URL asks for `=s0` (1408x768 original).
 * - Items with `ai_upscale` prefer a 4x Real-ESRGAN master made on the host by
 *   `node scripts/upscale-step-3-media.mjs [--items <file>]`, so the final WebP is downscaled, never stretched.
 *   Items with `require_master` refuse to fall back to the original when that master is missing.
 * - `source_region` (fractions of the source) keeps the crop inside the photo when the Stitch
 *   source image is a picture of a wireframe with UI chrome around the photo.
 * - `transparent` items (logos) keep their alpha channel; `sharpen` applies a light sharpen after the downscale.
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
const JN_MEDIA_ALPHA_TRANSPARENT  = 127;
// Real-ESRGAN renders white paper as luma 254–255; anything this bright counts as paper, not faint ink.
const JN_MEDIA_PAPER_LUMA = 252.0;
// Slugs end up in file paths (masters, attachment names): keep them to plain lowercase kebab-case.
const JN_MEDIA_SLUG_PATTERN = '/^[a-z0-9-]+$/';
const JN_MEDIA_MAX_QUALITY  = 100;

/**
 * Return media item definitions keyed by media-map key (the Step 3 placeholders by default).
 *
 * @param string $file JSON definitions file.
 * @return array<string, array<string, mixed>>
 * @throws RuntimeException When the definitions file is missing or invalid.
 */
function jn_placeholder_media_items( string $file = JN_PLACEHOLDER_ITEMS_FILE ): array {
	$json  = is_file( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$items = json_decode( $json, true );
	if ( ! is_array( $items ) || empty( $items ) ) {
		throw new RuntimeException( 'Invalid or missing media definitions: ' . $file );
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
	if ( ! empty( $item['require_master'] ) ) {
		return new WP_Error( 'jn_master_missing', "AI master is required but missing: {$master} (run scripts/upscale-step-3-media.mjs on the host)" );
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
	if ( ! empty( $item['transparent'] ) ) {
		// Copy alpha instead of blending onto the default black canvas.
		imagealphablending( $canvas, false );
		imagesavealpha( $canvas, true );
		imagefill( $canvas, 0, 0, imagecolorallocatealpha( $canvas, 0, 0, 0, JN_MEDIA_ALPHA_TRANSPARENT ) );
	}
	imagecopyresampled( $canvas, $src, 0, 0, $box['x'], $box['y'], $dst_w, $dst_h, $box['w'], $box['h'] );
	// Sharpen before masking: after jn_media_ink_to_alpha() the RGB is a flat ink colour and convolution is a no-op.
	if ( ! empty( $item['sharpen'] ) ) {
		jn_media_sharpen( $canvas, (float) $item['sharpen'] );
	}
	if ( ! empty( $item['ink'] ) ) {
		// The master is the logo flattened on white: rebuild its transparency at the final size.
		$masked = jn_media_ink_to_alpha( $canvas, (array) $item['ink'] );
		imagedestroy( $canvas );
		$canvas = $masked;
	}

	return array(
		'image' => $canvas,
		'scale' => round( $dst_w / $box['w'], 3 ),
	);
}

/**
 * Light 3x3 sharpen to recover the crispness a large downscale averages away.
 *
 * @param GdImage $image  Image, sharpened in place (GD has no copy-on-write convolution).
 * @param float   $amount Neighbour weight; 0.1–0.3 is subtle, higher adds halos.
 * @return void
 */
function jn_media_sharpen( GdImage $image, float $amount ): void {
	$matrix = array(
		array( 0.0, -$amount, 0.0 ),
		array( -$amount, 1.0 + 4.0 * $amount, -$amount ),
		array( 0.0, -$amount, 0.0 ),
	);
	imageconvolution( $image, $matrix, 1.0, 0.0 );
}

/**
 * Turn a single-ink logo flattened on white into that ink on a transparent background: each pixel's distance
 * from white becomes its ink coverage (alpha), and every pixel is painted in the ink colour. Real-ESRGAN
 * upscales colour and alpha separately, which leaves a dark fringe on logos; upscaling the flattened logo
 * and rebuilding alpha afterwards keeps the edges crisp and fringe-free.
 *
 * @param GdImage        $image Opaque truecolor image of the logo on white.
 * @param array<int,int> $ink   Ink colour as [r, g, b], each 0–255.
 * @return GdImage New image with alpha; `$image` is left untouched.
 * @throws InvalidArgumentException When `$ink` is not three 0–255 channels or `$image` is a palette image.
 */
function jn_media_ink_to_alpha( GdImage $image, array $ink ): GdImage {
	if ( ! jn_media_is_rgb( $ink ) ) {
		throw new InvalidArgumentException( 'Ink must be [r, g, b] integers in 0–255, got ' . wp_json_encode( $ink ) );
	}
	if ( ! imageistruecolor( $image ) ) {
		throw new InvalidArgumentException( 'jn_media_ink_to_alpha() needs a truecolor image (imagecolorat() returns palette indexes otherwise).' );
	}
	[ $red, $green, $blue ] = array_values( $ink );
	$luma_of                = static fn( int $c ): float => 0.299 * ( ( $c >> 16 ) & 255 ) + 0.587 * ( ( $c >> 8 ) & 255 ) + 0.114 * ( $c & 255 );
	$paper_to_ink           = max( 1.0, JN_MEDIA_PAPER_LUMA - $luma_of( ( $red << 16 ) | ( $green << 8 ) | $blue ) );

	$width  = imagesx( $image );
	$height = imagesy( $image );
	$out    = imagecreatetruecolor( $width, $height );
	imagealphablending( $out, false );
	imagesavealpha( $out, true );
	for ( $y = 0; $y < $height; $y++ ) {
		for ( $x = 0; $x < $width; $x++ ) {
			$coverage = max( 0.0, min( 1.0, ( JN_MEDIA_PAPER_LUMA - $luma_of( imagecolorat( $image, $x, $y ) ) ) / $paper_to_ink ) );
			$alpha    = (int) round( JN_MEDIA_ALPHA_TRANSPARENT * ( 1.0 - $coverage ) );
			imagesetpixel( $out, $x, $y, imagecolorallocatealpha( $out, $red, $green, $blue, $alpha ) );
		}
	}
	return $out;
}

/**
 * Whether a value is an [r, g, b] colour: three integer channels in 0–255.
 *
 * @param mixed $ink Candidate colour.
 * @return bool
 */
function jn_media_is_rgb( $ink ): bool {
	if ( ! is_array( $ink ) || 3 !== count( $ink ) ) {
		return false;
	}
	foreach ( $ink as $channel ) {
		if ( ! is_int( $channel ) || $channel < 0 || $channel > 255 ) {
			return false;
		}
	}
	return true;
}

/**
 * Reject item definitions that would write outside the uploads folder or crash mid-run: the slug and
 * filename end up in file paths, and a bad size or `ink` would throw inside GD.
 *
 * @param array<string, mixed> $item Item definition.
 * @return WP_Error|null Null when the item is usable.
 */
function jn_media_validate_item( array $item ): ?WP_Error {
	$slug     = (string) ( $item['slug'] ?? '' );
	$filename = (string) ( $item['filename'] ?? '' );
	if ( 1 !== preg_match( JN_MEDIA_SLUG_PATTERN, $slug ) ) {
		return new WP_Error( 'jn_invalid_item', "Slug must be lowercase kebab-case: {$slug}" );
	}
	if ( '' === $filename || basename( $filename ) !== $filename || sanitize_file_name( $filename ) !== $filename ) {
		return new WP_Error( 'jn_invalid_item', "Unsafe filename: {$filename}" );
	}
	$is_sized = is_int( $item['width'] ?? null ) && $item['width'] > 0 && is_int( $item['height'] ?? null ) && $item['height'] > 0;
	$quality  = $item['quality'] ?? null;
	if ( ! $is_sized || ! is_int( $quality ) || $quality < 0 || $quality > JN_MEDIA_MAX_QUALITY ) {
		return new WP_Error( 'jn_invalid_item', "{$slug}: width/height must be positive integers and quality 0–100" );
	}
	if ( isset( $item['ink'] ) && ! jn_media_is_rgb( $item['ink'] ) ) {
		return new WP_Error( 'jn_invalid_item', "{$slug}: ink must be [r, g, b] integers in 0–255, got " . wp_json_encode( $item['ink'] ) );
	}
	return null;
}

/**
 * Folder an item's WebP goes to: an existing attachment keeps its folder, so a re-run in a later month
 * overwrites its files instead of orphaning them; a new attachment goes to the current uploads folder.
 *
 * @param string $current_file Existing attachment's file path, '' when there is none.
 * @param string $upload_path  Current uploads folder (`wp_upload_dir()['path']`).
 * @return string Folder path with a trailing slash.
 */
function jn_media_target_dir( string $current_file, string $upload_path ): string {
	return trailingslashit( '' !== $current_file ? dirname( $current_file ) : $upload_path );
}

/**
 * Encode an image as WebP through a temp file renamed into place, so a failed encode never truncates
 * the existing file.
 *
 * @param GdImage $image     Image to encode.
 * @param string  $dest_file Destination path; its folder must exist.
 * @param int     $quality   WebP quality, 0–100.
 * @return WP_Error|null Null on success.
 */
function jn_media_write_webp( GdImage $image, string $dest_file, int $quality ): ?WP_Error {
	if ( ! is_dir( dirname( $dest_file ) ) ) {
		return new WP_Error( 'jn_write_failed', 'Destination folder does not exist: ' . dirname( $dest_file ) );
	}
	$temp_file = $dest_file . '.tmp';
	if ( imagewebp( $image, $temp_file, $quality ) && rename( $temp_file, $dest_file ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
		return null;
	}
	wp_delete_file( $temp_file );
	return new WP_Error( 'jn_write_failed', "Could not write {$dest_file}" );
}

/**
 * ID of the attachment with this slug, or 0.
 *
 * @param string $slug Attachment `post_name`.
 * @return int
 */
function jn_media_find_attachment_id( string $slug ): int {
	$ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'name'           => $slug,
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		)
	);
	return (int) ( $ids[0] ?? 0 );
}

/**
 * Point the item's attachment at `$dest_file`, creating the attachment when it does not exist yet.
 *
 * @param array<string, mixed> $item        Item definition.
 * @param string               $dest_file   WebP path.
 * @param int                  $existing_id Existing attachment ID, 0 when there is none.
 * @return int|WP_Error Attachment ID.
 */
function jn_media_upsert_attachment( array $item, string $dest_file, int $existing_id ) {
	$fields = array(
		'post_title'     => $item['title'],
		'post_name'      => $item['slug'],
		'post_mime_type' => 'image/webp',
	);
	if ( $existing_id > 0 ) {
		$updated = wp_update_post( array_merge( $fields, array( 'ID' => $existing_id ) ), true );
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}
		update_attached_file( $existing_id, $dest_file );
		return $existing_id;
	}

	$extra = array(
		'guid'         => trailingslashit( wp_upload_dir()['url'] ) . $item['filename'],
		'post_content' => '',
		'post_status'  => 'inherit',
	);
	return wp_insert_attachment( array_merge( $fields, $extra ), $dest_file, 0, true );
}

/**
 * Store an imported attachment's post meta and describe it for the media map.
 *
 * @param int                   $attach_id Attachment ID.
 * @param array<string, mixed>  $item      Item definition.
 * @param string                $dest_file WebP path.
 * @param array<string, string> $meta      Post meta to store.
 * @return array<string, mixed> Media-map entry.
 */
function jn_media_finish_attachment( int $attach_id, array $item, string $dest_file, array $meta ): array {
	foreach ( $meta as $meta_key => $meta_value ) {
		update_post_meta( $attach_id, $meta_key, $meta_value );
	}
	return array(
		'id'          => $attach_id,
		'url'         => wp_get_attachment_url( $attach_id ),
		'title'       => $item['title'],
		'alt'         => $item['alt'],
		'width'       => (int) $item['width'],
		'height'      => (int) $item['height'],
		'filesize_kb' => round( filesize( $dest_file ) / 1024, 1 ),
	);
}

/**
 * Render one item from its source, write its WebP and create or update its attachment (matched by slug) with
 * AVIF sub-sizes, a full-size `.avif` companion, alt text and provenance meta.
 *
 * An invalid item, a missing source or a failed WebP encode returns a WP_Error and leaves the existing file and
 * attachment untouched; a later failure (sub-size regeneration) returns a WP_Error after the attachment already
 * points at the new WebP — re-run the import to finish it.
 *
 * Requires `wp-admin/includes/{image,file,media}.php` to be loaded by the caller.
 *
 * @param array<string, mixed>  $item       Item definition.
 * @param array<string, string> $extra_meta Extra post meta to store on the attachment.
 * @return array{entry: array<string, mixed>, origin: string, scale: float}|WP_Error
 */
function jn_import_media_item( array $item, array $extra_meta = array() ) {
	$invalid = jn_media_validate_item( $item );
	if ( null !== $invalid ) {
		return $invalid;
	}
	$source = jn_placeholder_load_source( $item );
	if ( is_wp_error( $source ) ) {
		return $source;
	}

	$existing_id = jn_media_find_attachment_id( (string) $item['slug'] );
	$target_dir  = jn_media_target_dir( $existing_id > 0 ? (string) get_attached_file( $existing_id ) : '', wp_upload_dir()['path'] );
	wp_mkdir_p( $target_dir );
	$dest_file = $target_dir . $item['filename'];

	try {
		$rendered = jn_placeholder_render( $source['image'], $item );
	} catch ( InvalidArgumentException $e ) {
		return new WP_Error( 'jn_invalid_item', $e->getMessage() );
	}
	$origin = $source['origin'];
	unset( $source ); // Free the (up to ~45 MB) 4x master before sub-size generation.
	$written = jn_media_write_webp( $rendered['image'], $dest_file, (int) $item['quality'] );
	unset( $rendered['image'] );
	if ( null !== $written ) {
		return $written;
	}

	$attach_id = jn_media_upsert_attachment( $item, $dest_file, $existing_id );
	if ( is_wp_error( $attach_id ) ) {
		return $attach_id;
	}
	$regenerated = jn_regenerate_attachment_with_avif( (int) $attach_id );
	if ( is_wp_error( $regenerated ) ) {
		return $regenerated;
	}

	$meta = array(
		'_wp_attachment_image_alt' => $item['alt'],
		'_jn_media_source'         => $origin,
		'_jn_media_scale'          => (string) $rendered['scale'],
	);
	return array(
		'entry'  => jn_media_finish_attachment( (int) $attach_id, $item, $dest_file, array_merge( $meta, $extra_meta ) ),
		'origin' => $origin,
		'scale'  => (float) $rendered['scale'],
	);
}

/**
 * Import the selected items (all when no key is given), print one [OK]/[ERROR] line per item and merge the
 * results into the `$map_option` media map, so a partial run keeps the other items' entries.
 *
 * @param array<string, array<string, mixed>> $items      Item definitions keyed by media-map key.
 * @param array<int|string, mixed>            $only_keys  Keys from the command line; empty for all.
 * @param string                              $map_option Option that holds the media map.
 * @param callable                            $extra_meta fn( array $item ): array<string, string>, extra post meta.
 * @return int Exit code: 0 when every item was imported, 1 otherwise.
 */
function jn_run_media_import( array $items, array $only_keys, string $map_option, callable $extra_meta ): int {
	$keys    = array_values( array_filter( $only_keys ) );
	$unknown = array_diff( $keys, array_keys( $items ) );
	if ( ! empty( $unknown ) ) {
		echo '[ERROR] Unknown media key(s): ' . implode( ', ', $unknown ) . "\n";
		return 1;
	}

	$selected  = empty( $keys ) ? $items : array_intersect_key( $items, array_flip( $keys ) );
	$media_map = array();
	$failures  = 0;
	foreach ( $selected as $key => $item ) {
		$imported = jn_import_media_item( $item, $extra_meta( $item ) );
		if ( is_wp_error( $imported ) ) {
			echo sprintf( "[ERROR] %s -> %s\n", $key, $imported->get_error_message() );
			++$failures;
			continue;
		}
		$entry             = $imported['entry'];
		$media_map[ $key ] = $entry;
		echo sprintf(
			"[OK] %s -> ID=%d | %dx%d | %s KB | source=%s scale=%sx | %s\n",
			$key,
			$entry['id'],
			$entry['width'],
			$entry['height'],
			$entry['filesize_kb'],
			$imported['origin'],
			$imported['scale'],
			$entry['url']
		);
	}

	$previous = get_option( $map_option, array() );
	update_option( $map_option, array_merge( is_array( $previous ) ? $previous : array(), $media_map ), false );
	if ( $failures > 0 ) {
		echo "[ERROR] {$failures} item(s) failed (see above); re-run with their keys to retry.\n";
		return 1;
	}
	return 0;
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
