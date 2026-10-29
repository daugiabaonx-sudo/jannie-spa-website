<?php
/**
 * JN — Step 3 placeholder media quality: the hero portrait must not carry the Stitch wireframe chrome
 * baked into its source image, and neither the hero nor the expert photo may be stretched from Google's
 * 512px default rendition (they are fetched at `=s0`, or taken from a 4x Real-ESRGAN master).
 *
 * Run via (after `import-step-3-media.php hero_portrait expert_consultation`):
 *   docker compose run --rm wpcli wp eval-file /scripts/test-placeholder-media.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/jn-test-kit.php';
jn_require_non_production( 'test-placeholder-media.php' );

$jn_media_lib = __DIR__ . '/lib/jn-placeholder-media.php';
if ( is_file( $jn_media_lib ) ) {
	require_once $jn_media_lib;
}

echo "=================================================================\n";
echo "JN — STEP 3 PLACEHOLDER MEDIA QUALITY\n";
echo "=================================================================\n";

const JN_TPM_NATIVE_W = 1408;
const JN_TPM_NATIVE_H = 768;
const JN_TPM_AI_SCALE = 4;

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Library + item definitions' );

jn_t_assert( function_exists( 'jn_placeholder_source_url' ), 'jn_placeholder_source_url() is defined' );
jn_t_assert( function_exists( 'jn_placeholder_crop_box' ), 'jn_placeholder_crop_box() is defined' );
jn_t_assert( function_exists( 'jn_placeholder_media_items' ), 'jn_placeholder_media_items() is defined' );
jn_t_assert( function_exists( 'jn_regenerate_attachment_with_avif' ), 'jn_regenerate_attachment_with_avif() is defined' );

if ( ! function_exists( 'jn_placeholder_media_items' ) ) {
	jn_t_finish();
	return;
}

$jn_items = jn_placeholder_media_items();
jn_t_assert( 5 === count( $jn_items ), 'All 5 Step 3 placeholder items are defined', (string) count( $jn_items ) );

$jn_hero   = $jn_items['hero_portrait'] ?? array();
$jn_expert = $jn_items['expert_consultation'] ?? array();
jn_t_assert( ! empty( $jn_hero['source_region'] ), 'Hero item declares a source_region (the photo inside the wireframe)' );
jn_t_assert( empty( $jn_expert['source_region'] ), 'Expert item uses the whole source image' );

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Source URL requests the original rendition' );

$jn_base = 'https://lh3.googleusercontent.com/aida-public/AB6AXu-abc_123';
jn_t_assert( $jn_base . '=s0' === jn_placeholder_source_url( $jn_base ), 'Appends =s0 to a bare lh3 URL' );
jn_t_assert( $jn_base . '=s0' === jn_placeholder_source_url( $jn_base . '=s0' ), 'Does not append =s0 twice' );
jn_t_assert( $jn_base . '=s0' === jn_placeholder_source_url( $jn_base . '=w512-h279' ), 'Replaces an existing size suffix with =s0' );

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Crop box maths' );

/**
 * Whether a crop box lies fully inside the item's source region, in source pixels.
 *
 * @param array<string, int>   $box    Crop box.
 * @param array<string, float> $region Fractional region.
 * @param int                  $src_w  Source width.
 * @param int                  $src_h  Source height.
 * @return bool
 */
function jn_tpm_box_inside_region( array $box, array $region, int $src_w, int $src_h ): bool {
	return $box['x'] >= floor( $region['x'] * $src_w )
		&& $box['y'] >= floor( $region['y'] * $src_h )
		&& $box['x'] + $box['w'] <= ceil( ( $region['x'] + $region['w'] ) * $src_w )
		&& $box['y'] + $box['h'] <= ceil( ( $region['y'] + $region['h'] ) * $src_h );
}

foreach ( array( 1, JN_TPM_AI_SCALE ) as $jn_scale ) {
	$jn_src_w = JN_TPM_NATIVE_W * $jn_scale;
	$jn_src_h = JN_TPM_NATIVE_H * $jn_scale;
	$jn_label = 1 === $jn_scale ? 'native 1408x768' : '4x AI master';

	$jn_box = jn_placeholder_crop_box( $jn_src_w, $jn_src_h, (int) $jn_hero['width'], (int) $jn_hero['height'], $jn_hero['source_region'], (float) ( $jn_hero['focus_x'] ?? 0.5 ) );
	jn_t_assert( abs( $jn_box['w'] / $jn_box['h'] - 0.8 ) < 0.005, "Hero crop is 4:5 ({$jn_label})", wp_json_encode( $jn_box ) );
	jn_t_assert( jn_tpm_box_inside_region( $jn_box, $jn_hero['source_region'], $jn_src_w, $jn_src_h ), "Hero crop stays inside the photo, never the wireframe UI ({$jn_label})", wp_json_encode( $jn_box ) );
	jn_t_assert( $jn_box['w'] >= 395 * $jn_scale, "Hero crop keeps >= 395px of native width ({$jn_label})", (string) $jn_box['w'] );

	$jn_box = jn_placeholder_crop_box( $jn_src_w, $jn_src_h, (int) $jn_expert['width'], (int) $jn_expert['height'] );
	jn_t_assert( 576 * $jn_scale === $jn_box['w'] && $jn_src_h === $jn_box['h'], "Expert crop is the full-height 3:4 band ({$jn_label})", wp_json_encode( $jn_box ) );
	jn_t_assert( 416 * $jn_scale === $jn_box['x'], "Expert crop keeps the current centred framing ({$jn_label})", (string) $jn_box['x'] );
}

$jn_region = array( 'x' => 0.25, 'y' => 0.0, 'w' => 0.5, 'h' => 1.0 );
$jn_box    = jn_placeholder_crop_box( 1000, 500, 400, 500, $jn_region, 0.0 );
jn_t_assert( 250 === $jn_box['x'], 'A focus point left of the region is clamped to the region edge', (string) $jn_box['x'] );
$jn_box = jn_placeholder_crop_box( 1000, 500, 400, 500, $jn_region, 1.0 );
jn_t_assert( 750 === $jn_box['x'] + $jn_box['w'], 'A focus point right of the region is clamped to the region edge', wp_json_encode( $jn_box ) );

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Imported attachments' );

// WordPress never creates a sub-size equal to the original (800x1000 / 750x1000): the full-size `.avif`
// companion fills that srcset slot, so check the largest responsive sub-size of each role instead.
$jn_map     = get_option( 'jn_step3_media_map', array() );
$jn_budgets = array(
	'hero_portrait'       => array(
		'size'      => 'jn-hero-652',
		'max_scale' => 2.0,
		'max_kb'    => 150,
	),
	'expert_consultation' => array(
		'size'      => 'jn-expert-640',
		'max_scale' => 1.35,
		'max_kb'    => 200,
	),
);

foreach ( $jn_budgets as $jn_key => $jn_budget ) {
	$jn_item = $jn_items[ $jn_key ];
	$jn_id   = (int) ( $jn_map[ $jn_key ]['id'] ?? 0 );
	jn_t_assert( $jn_id > 0, "{$jn_key}: attachment is in jn_step3_media_map" );
	if ( $jn_id <= 0 ) {
		continue;
	}

	$jn_file = (string) get_attached_file( $jn_id );
	$jn_dims = is_file( $jn_file ) ? getimagesize( $jn_file ) : false;
	jn_t_assert( str_ends_with( $jn_file, (string) $jn_item['filename'] ), "{$jn_key}: attached file keeps its filename (page content URLs unchanged)", $jn_file );
	jn_t_assert( is_array( $jn_dims ) && (int) $jn_item['width'] === $jn_dims[0] && (int) $jn_item['height'] === $jn_dims[1], "{$jn_key}: WebP is exactly {$jn_item['width']}x{$jn_item['height']}", is_array( $jn_dims ) ? "{$jn_dims[0]}x{$jn_dims[1]}" : 'missing' );

	$jn_kb = is_file( $jn_file ) ? filesize( $jn_file ) / 1024 : INF;
	jn_t_assert( $jn_kb <= $jn_budget['max_kb'], "{$jn_key}: WebP stays within {$jn_budget['max_kb']} KB", round( $jn_kb, 1 ) . ' KB' );

	$jn_origin = (string) get_post_meta( $jn_id, '_jn_media_source', true );
	$jn_scale  = (float) get_post_meta( $jn_id, '_jn_media_scale', true );
	$jn_master = jn_placeholder_master_path( (string) $jn_item['slug'] );
	jn_t_assert( in_array( $jn_origin, array( 'ai-x4', 's0' ), true ), "{$jn_key}: source is the AI master or the =s0 original, never the 512px default", $jn_origin );
	jn_t_assert( ! is_file( $jn_master ) || 'ai-x4' === $jn_origin, "{$jn_key}: uses the AI master when one exists", $jn_origin );
	jn_t_assert( $jn_scale > 0 && $jn_scale <= $jn_budget['max_scale'], "{$jn_key}: upscale factor <= {$jn_budget['max_scale']}x", (string) $jn_scale );
	jn_t_assert( 'ai-x4' !== $jn_origin || $jn_scale <= 1.0, "{$jn_key}: AI master is only ever downscaled", (string) $jn_scale );

	$jn_avif = (string) preg_replace( '/\.webp$/i', '.avif', $jn_file );
	jn_t_assert( is_file( $jn_avif ) && filemtime( $jn_avif ) >= filemtime( $jn_file ), "{$jn_key}: full-size AVIF companion is regenerated" );

	$jn_meta = wp_get_attachment_metadata( $jn_id );
	$jn_sub  = $jn_meta['sizes'][ $jn_budget['size'] ]['file'] ?? '';
	$jn_path = '' !== $jn_sub ? dirname( $jn_file ) . '/' . $jn_sub : '';
	jn_t_assert( '' !== $jn_path && is_file( $jn_path ) && filemtime( $jn_path ) >= filemtime( $jn_file ), "{$jn_key}: {$jn_budget['size']} sub-size is regenerated", $jn_path );
}

jn_t_finish();
