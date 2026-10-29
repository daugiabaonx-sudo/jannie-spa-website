<?php
/**
 * JN — Legacy jannieedu.com media: the 14 images recovered from the Wayback Machine must be imported
 * from their 4x Real-ESRGAN masters only (never the blurry archive copy), only ever downscaled, measurably
 * sharper than a plain bicubic resize of the archive copy, and logos must keep their transparency in the
 * WebP original, the full-size AVIF companion and the AVIF sub-sizes.
 *
 * Run via (after `import-legacy-media.php`):
 *   docker compose run --rm wpcli wp eval-file /scripts/test-legacy-media.php
 *
 * Dev-only suite: it reads the local DB (`jn_legacy_media_map`) and the gitignored archive copies in
 * `scripts/.media-masters/<slug>.src.*`. Items with `sharpness_pending` (logos whose small print every AI model
 * garbles, ADR-018) report their sharpness as [KNOWN] instead of failing, until the official logo file arrives.
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/jn-test-kit.php';
jn_require_non_production( 'test-legacy-media.php' );

$jn_media_lib = __DIR__ . '/lib/jn-placeholder-media.php';
if ( is_file( $jn_media_lib ) ) {
	require_once $jn_media_lib;
}

echo "=================================================================\n";
echo "JN — LEGACY JANNIEEDU.COM MEDIA\n";
echo "=================================================================\n";

const JN_TLM_ITEMS_FILE  = __DIR__ . '/lib/jn-legacy-media.json';
const JN_TLM_ITEM_COUNT  = 14;
const JN_TLM_MAX_WEBP_KB = 400;
const JN_TLM_ALPHA_CLEAR = 127;
const JN_TLM_ALPHA_SOLID = 0;
// Lossy AVIF may round a fully transparent pixel down by a step or two.
const JN_TLM_ALPHA_NEAR_CLEAR = 125;
// The imported image must beat a bicubic resize of the archive copy by at least this Laplacian-variance ratio.
const JN_TLM_MIN_SHARPNESS_GAIN = 1.25;

/**
 * Variance of the 4-neighbour Laplacian of an image's luminance (higher = more fine detail / sharper).
 * Transparent images are measured over white, as they are shown on the page.
 *
 * @param GdImage $im Image.
 * @return float
 */
function jn_tlm_sharpness( GdImage $im ): float {
	$w    = imagesx( $im );
	$h    = imagesy( $im );
	$flat = imagecreatetruecolor( $w, $h );
	imagefill( $flat, 0, 0, imagecolorallocate( $flat, 255, 255, 255 ) );
	imagecopyresampled( $flat, $im, 0, 0, 0, 0, $w, $h, $w, $h );

	$luma = static function ( int $y ) use ( $flat, $w ): array {
		$row = array();
		for ( $x = 0; $x < $w; $x++ ) {
			$c     = imagecolorat( $flat, $x, $y );
			$row[] = 0.299 * ( ( $c >> 16 ) & 255 ) + 0.587 * ( ( $c >> 8 ) & 255 ) + 0.114 * ( $c & 255 );
		}
		return $row;
	};

	$sum   = 0.0;
	$sumsq = 0.0;
	$n     = 0;
	$prev  = $luma( 0 );
	$cur   = $luma( 1 );
	for ( $y = 1; $y < $h - 1; $y++ ) {
		$next = $luma( $y + 1 );
		for ( $x = 1; $x < $w - 1; $x++ ) {
			$lap    = 4 * $cur[ $x ] - $cur[ $x - 1 ] - $cur[ $x + 1 ] - $prev[ $x ] - $next[ $x ];
			$sum   += $lap;
			$sumsq += $lap * $lap;
			++$n;
		}
		$prev = $cur;
		$cur  = $next;
	}
	imagedestroy( $flat );

	return $n > 0 ? $sumsq / $n - ( $sum / $n ) ** 2 : 0.0;
}

/**
 * Alpha (0 opaque … 127 transparent) of an image file's top-left pixel, or -1 when unreadable.
 *
 * @param string $path Image path (.webp or .avif).
 * @return int
 */
function jn_tlm_corner_alpha( string $path ): int {
	$im = str_ends_with( $path, '.avif' ) ? @imagecreatefromavif( $path ) : @imagecreatefromwebp( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	if ( ! $im ) {
		return -1;
	}
	$alpha = ( imagecolorat( $im, 0, 0 ) >> 24 ) & 127;
	imagedestroy( $im );
	return $alpha;
}

/**
 * Whether a WebP has at least one fully opaque pixel (guards against an all-transparent logo passing).
 *
 * @param string $path WebP path.
 * @return bool
 */
function jn_tlm_has_opaque_pixel( string $path ): bool {
	$im = @imagecreatefromwebp( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	if ( ! $im ) {
		return false;
	}
	for ( $y = 0; $y < imagesy( $im ); $y++ ) {
		for ( $x = 0; $x < imagesx( $im ); $x++ ) {
			if ( JN_TLM_ALPHA_SOLID === ( ( imagecolorat( $im, $x, $y ) >> 24 ) & 127 ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * The archive copy kept next to an item's AI master (`<slug>.src.<ext>`), or '' when missing.
 *
 * @param string $slug Item slug.
 * @return string
 */
function jn_tlm_archive_copy( string $slug ): string {
	$found = glob( JN_PLACEHOLDER_MASTER_DIR . '/' . $slug . '.src.*' );
	return is_array( $found ) && ! empty( $found ) ? $found[0] : '';
}

/**
 * Bicubic resize of an archive copy to the item's web size: what "just reuse the old image" would give.
 *
 * @param string $path   Archive copy path.
 * @param int    $width  Target width.
 * @param int    $height Target height.
 * @return GdImage|false
 */
function jn_tlm_bicubic_baseline( string $path, int $width, int $height ) {
	$raw = @imagecreatefromstring( (string) file_get_contents( $path ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! $raw ) {
		return false;
	}
	imagepalettetotruecolor( $raw );
	imagealphablending( $raw, false );
	imagesavealpha( $raw, true );
	$scaled = imagescale( $raw, $width, $height, IMG_BICUBIC );
	imagedestroy( $raw );
	return $scaled;
}

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Library + item definitions' );

jn_t_assert( function_exists( 'jn_placeholder_media_items' ), 'jn_placeholder_media_items() is defined' );
jn_t_assert( function_exists( 'jn_media_sharpen' ), 'jn_media_sharpen() is defined' );
jn_t_assert( function_exists( 'jn_import_media_item' ), 'jn_import_media_item() is defined' );
jn_t_assert( is_file( JN_TLM_ITEMS_FILE ), 'lib/jn-legacy-media.json exists' );

if ( ! function_exists( 'jn_media_sharpen' ) || ! function_exists( 'jn_import_media_item' ) || ! is_file( JN_TLM_ITEMS_FILE ) ) {
	jn_t_finish();
	return;
}

$jn_items = jn_placeholder_media_items( JN_TLM_ITEMS_FILE );
jn_t_assert( JN_TLM_ITEM_COUNT === count( $jn_items ), 'All 14 legacy items are defined', (string) count( $jn_items ) );

foreach ( $jn_items as $jn_key => $jn_item ) {
	$jn_is_valid = str_starts_with( (string) $jn_item['slug'], 'jn-legacy-' )
		&& $jn_item['slug'] . '.webp' === $jn_item['filename']
		&& '' !== trim( (string) $jn_item['alt'] )
		&& ! empty( $jn_item['ai_upscale'] )
		&& ! empty( $jn_item['require_master'] )
		&& (int) $jn_item['width'] > 0
		&& (int) $jn_item['height'] > 0
		&& str_starts_with( (string) $jn_item['source_url'], 'https://web.archive.org/web/' );
	jn_t_assert( $jn_is_valid, "{$jn_key}: slug, alt, master requirement, size and archive source are declared" );
}

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Rendering rules' );

$jn_missing = jn_placeholder_load_source(
	array(
		'slug'           => 'jn-legacy-does-not-exist',
		'ai_upscale'     => true,
		'require_master' => true,
		'source_url'     => 'https://web.archive.org/web/20260711000257im_/https://example.invalid/x.png',
	)
);
jn_t_assert( is_wp_error( $jn_missing ) && 'jn_master_missing' === $jn_missing->get_error_code(), 'A missing AI master fails fast instead of falling back to the archive copy' );

$jn_src = imagecreatetruecolor( 40, 20 );
imagealphablending( $jn_src, false );
imagesavealpha( $jn_src, true );
imagefill( $jn_src, 0, 0, imagecolorallocatealpha( $jn_src, 0, 0, 0, JN_TLM_ALPHA_CLEAR ) );
imagefilledrectangle( $jn_src, 15, 5, 25, 15, imagecolorallocatealpha( $jn_src, 0, 0, 0, JN_TLM_ALPHA_SOLID ) );
$jn_out = jn_placeholder_render(
	$jn_src,
	array(
		'width'       => 20,
		'height'      => 10,
		'transparent' => true,
	)
);
jn_t_assert( JN_TLM_ALPHA_CLEAR === ( ( imagecolorat( $jn_out['image'], 0, 0 ) >> 24 ) & 127 ), 'A transparent item keeps a transparent background after resampling' );
jn_t_assert( JN_TLM_ALPHA_SOLID === ( ( imagecolorat( $jn_out['image'], 10, 5 ) >> 24 ) & 127 ), 'A transparent item keeps its opaque artwork opaque' );
imagedestroy( $jn_out['image'] );
imagedestroy( $jn_src );

// Single-ink logos: Real-ESRGAN upscales the logo flattened on white, then the ink coverage becomes alpha.
$jn_ink   = array( 172, 142, 118 );
$jn_flat  = imagecreatetruecolor( 4, 1 );
$jn_mid_r = (int) round( ( 255 + 172 ) / 2 );
$jn_mid_g = (int) round( ( 255 + 142 ) / 2 );
$jn_mid_b = (int) round( ( 255 + 118 ) / 2 );
imagesetpixel( $jn_flat, 0, 0, imagecolorallocate( $jn_flat, 255, 255, 255 ) );
imagesetpixel( $jn_flat, 1, 0, imagecolorallocate( $jn_flat, 172, 142, 118 ) );
imagesetpixel( $jn_flat, 2, 0, imagecolorallocate( $jn_flat, $jn_mid_r, $jn_mid_g, $jn_mid_b ) );
// Real-ESRGAN's "white" is (255, 254, 254): near-white noise must not leave a faint ink haze over the background.
imagesetpixel( $jn_flat, 3, 0, imagecolorallocate( $jn_flat, 255, 254, 254 ) );
$jn_masked = function_exists( 'jn_media_ink_to_alpha' ) ? jn_media_ink_to_alpha( $jn_flat, $jn_ink ) : null;
jn_t_assert( $jn_masked instanceof GdImage, 'jn_media_ink_to_alpha() returns an image' );
jn_t_assert( 0xFFFFFF === ( imagecolorat( $jn_flat, 0, 0 ) & 0xFFFFFF ), 'jn_media_ink_to_alpha() leaves its input untouched' );
if ( $jn_masked instanceof GdImage ) {
	$jn_px = array_map( static fn( int $x ) => imagecolorat( $jn_masked, $x, 0 ), array( 0, 1, 2, 3 ) );
	$jn_a  = array_map( static fn( int $c ) => ( $c >> 24 ) & 127, $jn_px );
	// Coverage is the distance from paper (JN_MEDIA_PAPER_LUMA, not 255) to ink, so derive the expected edge alpha.
	$jn_luma     = static fn( int $r, int $g, int $b ): float => 0.299 * $r + 0.587 * $g + 0.114 * $b;
	$jn_coverage = ( JN_MEDIA_PAPER_LUMA - $jn_luma( $jn_mid_r, $jn_mid_g, $jn_mid_b ) ) / ( JN_MEDIA_PAPER_LUMA - $jn_luma( 172, 142, 118 ) );
	$jn_expected = (int) round( JN_TLM_ALPHA_CLEAR * ( 1 - $jn_coverage ) );
	jn_t_assert( JN_TLM_ALPHA_CLEAR === $jn_a[0], 'White paper becomes fully transparent', (string) $jn_a[0] );
	jn_t_assert( JN_TLM_ALPHA_SOLID === $jn_a[1], 'Pure ink stays fully opaque', (string) $jn_a[1] );
	jn_t_assert( $jn_expected === $jn_a[2] && $jn_a[2] > JN_TLM_ALPHA_SOLID && $jn_a[2] < JN_TLM_ALPHA_CLEAR, 'A half-covered edge pixel stays partly transparent (anti-aliasing kept)', "{$jn_a[2]} vs {$jn_expected}" );
	jn_t_assert( JN_TLM_ALPHA_CLEAR === $jn_a[3], 'Near-white upscaler noise becomes fully transparent', (string) $jn_a[3] );
	jn_t_assert( 0xAC8E76 === ( $jn_px[2] & 0xFFFFFF ), 'Every pixel is painted in the ink colour (no dark fringe)', dechex( $jn_px[2] & 0xFFFFFF ) );
	imagedestroy( $jn_masked );
}
imagedestroy( $jn_flat );

$jn_soft = imagecreatetruecolor( 64, 64 );
imagefill( $jn_soft, 0, 0, imagecolorallocate( $jn_soft, 255, 255, 255 ) );
imagefilledellipse( $jn_soft, 32, 32, 40, 40, imagecolorallocate( $jn_soft, 40, 40, 40 ) );
imagefilter( $jn_soft, IMG_FILTER_GAUSSIAN_BLUR );
$jn_before = jn_tlm_sharpness( $jn_soft );
jn_media_sharpen( $jn_soft, 0.3 );
$jn_after = jn_tlm_sharpness( $jn_soft );
jn_t_assert( $jn_after > $jn_before, 'jn_media_sharpen() increases edge contrast', round( $jn_before, 1 ) . ' -> ' . round( $jn_after, 1 ) );
imagedestroy( $jn_soft );

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Import safety' );

// Only the pure validator is called until it exists: without it, a `../` filename would really be written.
jn_t_assert( function_exists( 'jn_media_validate_item' ), 'jn_media_validate_item() is defined' );
jn_t_assert( function_exists( 'jn_media_target_dir' ), 'jn_media_target_dir() is defined' );
jn_t_assert( function_exists( 'jn_media_write_webp' ), 'jn_media_write_webp() is defined' );
jn_t_assert( function_exists( 'jn_run_media_import' ), 'jn_run_media_import() is defined' );

if ( function_exists( 'jn_media_validate_item' ) ) {
	$jn_good = $jn_items['logo_ngoi_sao'];
	jn_t_assert( null === jn_media_validate_item( $jn_good ), 'A well-formed item passes validation' );

	$jn_bad_cases = array(
		'a ../ filename'      => array( 'filename' => '../../jn-tlm-evil.webp' ),
		'a ../ slug'          => array( 'slug' => '../jn-tlm-evil' ),
		'a two-channel ink'   => array( 'ink' => array( 0, 0 ) ),
		'an out-of-range ink' => array( 'ink' => array( 0, 0, 300 ) ),
	);
	foreach ( $jn_bad_cases as $jn_case => $jn_override ) {
		$jn_error = jn_media_validate_item( array_merge( $jn_good, $jn_override ) );
		jn_t_assert( is_wp_error( $jn_error ) && 'jn_invalid_item' === $jn_error->get_error_code(), "Rejects {$jn_case}" );
	}

	// A bad item must come back as a WP_Error (one [ERROR] line), not an uncaught exception that stops the run.
	$jn_file_before = (string) get_attached_file( (int) ( get_option( 'jn_legacy_media_map', array() )['logo_ngoi_sao']['id'] ?? 0 ) );
	$jn_mtime       = is_file( $jn_file_before ) ? filemtime( $jn_file_before ) : 0;
	$jn_result      = jn_import_media_item( array_merge( $jn_good, array( 'ink' => array( 0, 0 ) ) ) );
	clearstatcache();
	jn_t_assert( is_wp_error( $jn_result ) && 'jn_invalid_item' === $jn_result->get_error_code(), 'jn_import_media_item() returns a WP_Error for a malformed ink' );
	jn_t_assert( ! is_file( $jn_file_before ) || filemtime( $jn_file_before ) === $jn_mtime, 'A rejected item leaves the existing WebP untouched' );
}

if ( function_exists( 'jn_media_target_dir' ) ) {
	jn_t_assert( '/srv/media/2026/09/' === jn_media_target_dir( '/srv/media/2026/09/a.webp', '/srv/media/2026/10' ), 'An existing attachment keeps its uploads folder (no orphans on a later-month re-run)' );
	jn_t_assert( '/srv/media/2026/10/' === jn_media_target_dir( '', '/srv/media/2026/10' ), 'A new attachment goes to the current uploads folder' );
}

if ( function_exists( 'jn_media_write_webp' ) ) {
	$jn_tmp_dir = trailingslashit( get_temp_dir() ) . 'jn-tlm-' . $GLOBALS['jn_t_run'];
	wp_mkdir_p( $jn_tmp_dir );
	jn_t_on_cleanup(
		static function () use ( $jn_tmp_dir ): void {
			array_map( 'wp_delete_file', (array) glob( $jn_tmp_dir . '/*' ) );
			rmdir( $jn_tmp_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		}
	);
	$jn_pixel = imagecreatetruecolor( 4, 4 );
	$jn_dest  = $jn_tmp_dir . '/ok.webp';
	$jn_wrote = jn_media_write_webp( $jn_pixel, $jn_dest, 80 );
	jn_t_assert( null === $jn_wrote && is_file( $jn_dest ) && ! is_file( $jn_dest . '.tmp' ), 'jn_media_write_webp() writes via a temp file and leaves none behind' );
	$jn_lost  = $jn_tmp_dir . '/no-such-dir/lost.webp';
	$jn_wrote = jn_media_write_webp( $jn_pixel, $jn_lost, 80 );
	jn_t_assert( is_wp_error( $jn_wrote ) && ! is_file( $jn_lost ) && ! is_file( $jn_lost . '.tmp' ), 'jn_media_write_webp() returns a WP_Error and leaves no partial file when it cannot write' );
	imagedestroy( $jn_pixel );
}

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Imported attachments' );

$jn_map = get_option( 'jn_legacy_media_map', array() );

foreach ( $jn_items as $jn_key => $jn_item ) {
	$jn_id = (int) ( $jn_map[ $jn_key ]['id'] ?? 0 );
	jn_t_assert( $jn_id > 0, "{$jn_key}: attachment is in jn_legacy_media_map" );
	if ( $jn_id <= 0 ) {
		continue;
	}

	$jn_file = (string) get_attached_file( $jn_id );
	$jn_dims = is_file( $jn_file ) ? getimagesize( $jn_file ) : false;
	$jn_size = "{$jn_item['width']}x{$jn_item['height']}";
	jn_t_assert( str_ends_with( $jn_file, (string) $jn_item['filename'] ) && 'image/webp' === get_post_mime_type( $jn_id ), "{$jn_key}: attached file is {$jn_item['filename']} (image/webp)", $jn_file );
	jn_t_assert( is_array( $jn_dims ) && (int) $jn_item['width'] === $jn_dims[0] && (int) $jn_item['height'] === $jn_dims[1], "{$jn_key}: WebP is exactly {$jn_size}", is_array( $jn_dims ) ? "{$jn_dims[0]}x{$jn_dims[1]}" : 'missing' );

	$jn_kb = is_file( $jn_file ) ? filesize( $jn_file ) / 1024 : INF;
	jn_t_assert( $jn_kb <= JN_TLM_MAX_WEBP_KB, "{$jn_key}: WebP stays within " . JN_TLM_MAX_WEBP_KB . ' KB', round( $jn_kb, 1 ) . ' KB' );

	$jn_origin = (string) get_post_meta( $jn_id, '_jn_media_source', true );
	$jn_scale  = (float) get_post_meta( $jn_id, '_jn_media_scale', true );
	jn_t_assert( 'ai-x4' === $jn_origin, "{$jn_key}: built from the 4x AI master", $jn_origin );
	jn_t_assert( $jn_scale > 0 && $jn_scale <= 1.0, "{$jn_key}: AI master is only ever downscaled", (string) $jn_scale );
	jn_t_assert( get_post_meta( $jn_id, '_wp_attachment_image_alt', true ) === $jn_item['alt'], "{$jn_key}: alt text is set" );
	jn_t_assert( get_post_meta( $jn_id, '_jn_media_origin', true ) === $jn_item['source_url'], "{$jn_key}: archive origin is recorded" );
	jn_t_assert( '' === get_post_meta( $jn_id, '_jn_is_placeholder', true ), "{$jn_key}: not flagged as an AI placeholder" );

	$jn_avif      = (string) preg_replace( '/\.webp$/i', '.avif', $jn_file );
	$jn_avif_dims = is_file( $jn_avif ) ? getimagesize( $jn_avif ) : false;
	jn_t_assert( is_array( $jn_avif_dims ) && (int) $jn_item['width'] === $jn_avif_dims[0], "{$jn_key}: full-size AVIF companion is {$jn_size}" );

	$jn_meta  = wp_get_attachment_metadata( $jn_id );
	$jn_subs  = is_array( $jn_meta ) ? (array) ( $jn_meta['sizes'] ?? array() ) : array();
	$jn_avifs = array_filter( $jn_subs, static fn( $s ) => 'image/avif' === ( $s['mime-type'] ?? '' ) );
	jn_t_assert( ! empty( $jn_avifs ), "{$jn_key}: responsive sub-sizes are AVIF", (string) count( $jn_subs ) );

	if ( ! empty( $jn_item['transparent'] ) ) {
		jn_t_assert( JN_TLM_ALPHA_CLEAR === jn_tlm_corner_alpha( $jn_file ), "{$jn_key}: WebP background stays transparent" );
		jn_t_assert( jn_tlm_has_opaque_pixel( $jn_file ), "{$jn_key}: WebP still has solid artwork" );
		jn_t_assert( jn_tlm_corner_alpha( $jn_avif ) >= JN_TLM_ALPHA_NEAR_CLEAR, "{$jn_key}: AVIF companion background stays transparent" );
		$jn_largest = array_reduce( $jn_avifs, static fn( $carry, $s ) => null === $carry || $s['width'] > $carry['width'] ? $s : $carry );
		$jn_sub     = null !== $jn_largest ? dirname( $jn_file ) . '/' . $jn_largest['file'] : '';
		jn_t_assert( '' !== $jn_sub && jn_tlm_corner_alpha( $jn_sub ) >= JN_TLM_ALPHA_NEAR_CLEAR, "{$jn_key}: AVIF sub-size background stays transparent", $jn_sub );
	}

	$jn_archive  = jn_tlm_archive_copy( (string) $jn_item['slug'] );
	$jn_baseline = '' !== $jn_archive ? jn_tlm_bicubic_baseline( $jn_archive, (int) $jn_item['width'], (int) $jn_item['height'] ) : false;
	$jn_web      = is_file( $jn_file ) ? @imagecreatefromwebp( $jn_file ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	if ( ! $jn_baseline || ! $jn_web ) {
		jn_t_assert( false, "{$jn_key}: archive copy and WebP are readable for the sharpness check", $jn_archive );
		continue;
	}
	$jn_gain  = jn_tlm_sharpness( $jn_web ) / max( 1e-6, jn_tlm_sharpness( $jn_baseline ) );
	$jn_label = "{$jn_key}: >= " . JN_TLM_MIN_SHARPNESS_GAIN . 'x sharper than a bicubic resize of the archive copy';
	if ( ! empty( $jn_item['sharpness_pending'] ) ) {
		echo "[KNOWN] {$jn_label} — " . round( $jn_gain, 2 ) . "x; {$jn_item['sharpness_pending']}\n";
	} else {
		jn_t_assert( $jn_gain >= JN_TLM_MIN_SHARPNESS_GAIN, $jn_label, round( $jn_gain, 2 ) . 'x' );
	}
	imagedestroy( $jn_web );
	imagedestroy( $jn_baseline );
}

jn_t_finish();
