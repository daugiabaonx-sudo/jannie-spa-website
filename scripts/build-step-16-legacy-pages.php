<?php
/**
 * JN — Step 16 (ADR-023): put the old jannieedu.com content on /my-story/ and /coaching/.
 *
 * /my-story/ is rebuilt from lib/jn-legacy-content.json. /coaching/ keeps its "Section 3 — Coaching Tiers"
 * and FAQ sections (D1) — cut from the stored content by their List View names — after the old blocks.
 * Idempotent; wp_update_post() keeps a revision as the rollback point and the stored markup is read back.
 * Needs the 12 Step 16 images imported first (import-legacy-media.php).
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/build-step-16-legacy-pages.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Writes page content: refuse to run against production.
require_once __DIR__ . '/lib/jn-env-guard.php';
jn_require_non_production( basename( __FILE__ ) );
require_once __DIR__ . '/lib/jn-legacy-blocks.php';

echo "=== JN — STEP 16 MY STORY & COACHING (ADR-023) ===\n";

const JN_S16_TIERS_SECTION = 'Section 3 — Coaching Tiers';
const JN_S16_FAQ_SECTION   = 'Section — FAQ (Câu Hỏi Thường Gặp)';

/**
 * Print a failure and stop.
 *
 * @param string $message Message.
 * @return void
 */
function jn_s16_fail( string $message ): void {
	echo "[FAIL] {$message}\n";
	exit( 1 );
}

/**
 * Page ID by slug, or stop.
 *
 * @param string $slug Page slug.
 * @return int
 */
function jn_s16_page_id( string $slug ): int {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	if ( ! $page instanceof WP_Post ) {
		jn_s16_fail( "page /{$slug}/ not found" );
	}
	return (int) $page->ID;
}

// Admin context: kses would otherwise strip the column `style`, cover attributes and Block Bindings.
$jn_admins = get_users(
	array(
		'role'   => 'administrator',
		'number' => 1,
		'fields' => 'ID',
	)
);
wp_set_current_user( (int) ( $jn_admins[0] ?? 0 ) );

if ( 'wp_block' !== get_post_type( 95 ) || false === strpos( (string) get_post_field( 'post_content', 95 ), 'wp:jn/booking-form' ) ) {
	jn_s16_fail( 'wp_block 95 is not the synced booking section on this database' );
}

$jn_story_id    = jn_s16_page_id( 'my-story' );
$jn_coaching_id = jn_s16_page_id( 'coaching' );

$jn_stored = (string) get_post_field( 'post_content', $jn_coaching_id, 'raw' );
$jn_tiers  = jn_lb_extract_section( $jn_stored, JN_S16_TIERS_SECTION );
$jn_faq    = jn_lb_extract_section( $jn_stored, JN_S16_FAQ_SECTION );
if ( '' === $jn_tiers || '' === $jn_faq || false === strpos( $jn_tiers, 'id="coaching-tiers"' ) ) {
	jn_s16_fail( '/coaching/ tiers or FAQ section not found exactly once — nothing written' );
}

try {
	foreach ( array(
		$jn_story_id    => jn_lb_my_story_page(),
		$jn_coaching_id => jn_lb_coaching_page( $jn_tiers, $jn_faq ),
	) as $jn_page_id => $jn_content ) {
		$jn_status = jn_lb_write_page( $jn_page_id, $jn_content );
		$jn_slug   = (string) get_post_field( 'post_name', $jn_page_id );
		echo 'skip' === $jn_status
			? "[SKIP] /{$jn_slug}/ already matches the legacy content\n"
			: "[OK] /{$jn_slug}/ (ID={$jn_page_id}) written; previous content kept as a revision\n";
		echo jn_lb_sync_seo_meta( $jn_page_id, $jn_slug ) ? "[OK] /{$jn_slug}/ SEO title/description synced to the TSF fields\n" : "[SKIP] /{$jn_slug}/ SEO fields already match\n";
	}
} catch ( RuntimeException $jn_error ) {
	jn_s16_fail( $jn_error->getMessage() );
}

wp_get_theme()->delete_pattern_cache();
jannie_ensure_front_css_bundle();
echo "[OK] Theme pattern cache cleared, CSS bundle rebuilt\n";
if ( function_exists( 'wp_cache_clear_cache' ) ) {
	wp_cache_clear_cache();
	echo "[OK] WP Super Cache cleared\n";
} else {
	echo "[WARN] wp_cache_clear_cache() unavailable — clear the page cache by hand\n";
}

echo "RESULT: done\n";
