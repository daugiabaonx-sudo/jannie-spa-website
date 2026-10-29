<?php
/**
 * Import the 14 images recovered from the archived jannieedu.com home page (Wayback Machine, 2026-07-11)
 * into the local WordPress Media Library.
 *
 * Ensures:
 * - Every image is built from its 4x Real-ESRGAN master (`require_master`): a missing master is an error,
 *   never a silent fallback to the blurry archive copy
 * - The master is only ever downscaled to the web size in `lib/jn-legacy-media.json`, then lightly sharpened
 * - WebP original + full-size `.avif` companion + AVIF sub-sizes (shared with Step 3, `lib/jn-placeholder-media.php`)
 * - Logos keep their transparent background; Vietnamese alt text; the archive URL is kept in `_jn_media_origin`
 *
 * Masters: `scripts/.media-masters/<slug>.x4.png`, rebuilt on the host with
 *   node scripts/upscale-step-3-media.mjs --items scripts/lib/jn-legacy-media.json
 *
 * Usage: ./scripts/wp.sh eval-file /scripts/import-legacy-media.php [<key>...]
 *   e.g. ./scripts/wp.sh eval-file /scripts/import-legacy-media.php banner_home logo_spa
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

const JN_LEGACY_ITEMS_FILE = __DIR__ . '/lib/jn-legacy-media.json';
const JN_LEGACY_MAP_OPTION = 'jn_legacy_media_map';

$jn_exit = jn_run_media_import(
	jn_placeholder_media_items( JN_LEGACY_ITEMS_FILE ),
	isset( $args ) && is_array( $args ) ? $args : array(),
	JN_LEGACY_MAP_OPTION,
	static fn( array $item ): array => array( '_jn_media_origin' => (string) $item['source_url'] )
);
if ( $jn_exit > 0 ) {
	exit( $jn_exit );
}
