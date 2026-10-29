<?php
/**
 * JN — Hero media card cleanup (ADR-016): remove the decorative "1:1" watermark paragraph and the
 * "PRO-COACHING // SPEC.24" badge paragraph from the Home and Coaching content already stored in the
 * database. The `jannie/hero-editorial` pattern no longer contains them, so a fresh build-step-3.php
 * install is clean without this script.
 *
 * Idempotent: each block is removed by an exact match of its own block markup (block comments + `<p>`)
 * and only when it appears exactly once; pages already clean are skipped. `parse_blocks()` /
 * `serialize_blocks()` is not used because it does not round-trip these pages byte for byte.
 * `wp_update_post()` stores a revision, which is the rollback point.
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/build-hero-overlay-cleanup.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Rewrites page content: refuse to run against production.
require_once __DIR__ . '/lib/jn-env-guard.php';
jn_require_non_production( basename( __FILE__ ) );

echo "=== JN — HERO MEDIA CARD CLEANUP (ADR-016) ===\n";

const JN_HOC_PAGES = array( 'home', 'coaching' );

// One regex per removed paragraph block, including the leading indentation and trailing newline.
const JN_HOC_BLOCKS = array(
	'watermark'  => '#[\t ]*<!-- wp:paragraph \{"className":"jn-hero-watermark"\} -->\s*<p class="jn-hero-watermark">1:1</p>\s*<!-- /wp:paragraph -->\R(?:[\t ]*\R)?#',
	'spec badge' => '#[\t ]*<!-- wp:paragraph \{"className":"jn-hero-spec-badge"\} -->\s*<p class="jn-hero-spec-badge">PRO-COACHING // SPEC\.24</p>\s*<!-- /wp:paragraph -->\R(?:[\t ]*\R)?#',
);

/**
 * Remove the hero watermark / spec badge blocks from page content.
 *
 * @param string $content Stored post content.
 * @return array{content: string, removed: string[], errors: string[]}
 */
function jn_hoc_strip_blocks( string $content ): array {
	$removed = array();
	$errors  = array();
	foreach ( JN_HOC_BLOCKS as $label => $pattern ) {
		$count = preg_match_all( $pattern, $content );
		if ( 0 === $count ) {
			continue;
		}
		if ( 1 !== $count ) {
			$errors[] = "{$label} matched {$count} times (expected 1)";
			continue;
		}
		$content   = (string) preg_replace( $pattern, '', $content, 1 );
		$removed[] = $label;
	}
	foreach ( array( 'jn-hero-watermark', 'jn-hero-spec-badge' ) as $leftover ) {
		if ( empty( $errors ) && false !== strpos( $content, $leftover ) ) {
			$errors[] = "{$leftover} still present in an unexpected form (edited by hand?)";
		}
	}
	return array(
		'content' => $content,
		'removed' => $removed,
		'errors'  => $errors,
	);
}

$jn_failed = 0;
foreach ( JN_HOC_PAGES as $jn_slug ) {
	$jn_page = get_page_by_path( $jn_slug, OBJECT, 'page' );
	if ( ! $jn_page ) {
		echo "[FAIL] /{$jn_slug}/: page not found\n";
		++$jn_failed;
		continue;
	}

	$jn_result = jn_hoc_strip_blocks( $jn_page->post_content );
	if ( ! empty( $jn_result['errors'] ) ) {
		echo "[FAIL] /{$jn_slug}/: " . implode( '; ', $jn_result['errors'] ) . " — nothing written\n";
		++$jn_failed;
		continue;
	}
	if ( empty( $jn_result['removed'] ) ) {
		echo "[SKIP] /{$jn_slug}/: already clean\n";
		continue;
	}

	$jn_updated = wp_update_post(
		array(
			'ID'           => (int) $jn_page->ID,
			'post_content' => wp_slash( $jn_result['content'] ),
		),
		true
	);
	if ( is_wp_error( $jn_updated ) ) {
		echo "[FAIL] /{$jn_slug}/: " . $jn_updated->get_error_message() . "\n";
		++$jn_failed;
		continue;
	}
	echo "[OK] /{$jn_slug}/ (ID={$jn_page->ID}) removed: " . implode( ', ', $jn_result['removed'] ) . "\n";
}

// Every page inlines the theme CSS, so the whole page cache is stale, not only the edited pages.
jannie_ensure_front_css_bundle();
echo "[OK] CSS bundle rebuilt\n";
if ( function_exists( 'wp_cache_clear_cache' ) ) {
	wp_cache_clear_cache();
	echo "[OK] WP Super Cache cleared\n";
} else {
	echo "[WARN] wp_cache_clear_cache() unavailable — clear the page cache by hand\n";
}

if ( $jn_failed > 0 ) {
	echo "RESULT: {$jn_failed} page(s) failed\n";
	exit( 1 );
}
echo "RESULT: done\n";
