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

// A failed item is skipped with an [ERROR] line: a good image is never overwritten with a blank canvas.
$jn_exit = jn_run_media_import(
	jn_placeholder_media_items(),
	isset( $args ) && is_array( $args ) ? $args : array(),
	'jn_step3_media_map',
	static fn(): array => array( '_jn_is_placeholder' => '1' )
);
if ( $jn_exit > 0 ) {
	exit( $jn_exit );
}
