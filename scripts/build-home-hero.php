<?php
/**
 * JN — Home hero (ADR-021): replace the stored Home hero section (`Section 1 — Hero Editorial`, or an
 * earlier `Section 1 — Hero Home`) with the current `jannie/hero-home` pattern. /coaching/ keeps the
 * editorial hero; build-step-3.php seeds the new hero on a fresh install, so this script only migrates
 * content that already exists. Re-run it after editing the pattern.
 *
 * Idempotent: a Home whose hero already equals the pattern is skipped. The section is located by its exact
 * opening comment and the first `</section><!-- /wp:group -->` after it (inner groups close with `</div>`),
 * and is only replaced when it occurs once and holds one H1 and its expected markup. `parse_blocks()` /
 * `serialize_blocks()` is not used because it does not round-trip these pages byte for byte (ADR-016).
 * `wp_update_post()` stores a revision, which is the rollback point.
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/build-home-hero.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Rewrites page content: refuse to run against production.
require_once __DIR__ . '/lib/jn-env-guard.php';
jn_require_non_production( basename( __FILE__ ) );

echo "=== JN — HOME HERO (ADR-021) ===\n";

// Opening comment of a replaceable hero section => markup it must contain. The editorial hero is the
// pre-ADR-021 Home; an earlier `Hero Home` is replaced when the pattern changes (re-runnable).
const JN_HHB_SECTIONS = array(
	'<!-- wp:group {"tagName":"section","metadata":{"name":"Section 1 — Hero Editorial"}' => 'jn-hero-media-card',
	'<!-- wp:group {"tagName":"section","metadata":{"name":"Section 1 — Hero Home"}'      => '<!-- wp:jn/hero-portrait',
);
const JN_HHB_SECTION_END = "</section>\n<!-- /wp:group -->";

/**
 * Replace the hero section in the Home content with the current pattern.
 *
 * @param string $content  Stored post content.
 * @param string $new_hero Rendered `jannie/hero-home` markup.
 * @return array{content: string, status: string, errors: string[]}
 */
function jn_hhb_swap_hero( string $content, string $new_hero ): array {
	$fail  = static fn( string $error ): array => array( 'content' => $content, 'status' => 'fail', 'errors' => array( $error ) );
	$found = array_filter( array_keys( JN_HHB_SECTIONS ), static fn( string $open ): bool => false !== strpos( $content, $open ) );
	$count = array_sum( array_map( static fn( string $open ): int => substr_count( $content, $open ), $found ) );
	if ( 1 !== $count ) {
		return $fail( "hero section opening matched {$count} times (expected 1)" );
	}

	$open  = (string) reset( $found );
	$start = (int) strpos( $content, $open );
	$end   = strpos( $content, JN_HHB_SECTION_END, $start );
	if ( false === $end ) {
		return $fail( 'hero section has no closing </section> (edited by hand?)' );
	}

	$end += strlen( JN_HHB_SECTION_END );
	$old  = substr( $content, $start, $end - $start );
	if ( 1 !== preg_match_all( '/<h1\b/', $old ) ) {
		return $fail( 'hero section does not hold exactly one H1' );
	}
	if ( false === strpos( $old, JN_HHB_SECTIONS[ $open ] ) ) {
		return $fail( 'hero section has no ' . JN_HHB_SECTIONS[ $open ] . ' (unexpected markup)' );
	}
	if ( trim( $new_hero ) === $old ) {
		return array( 'content' => $content, 'status' => 'skip', 'errors' => array() );
	}

	return array(
		'content' => substr( $content, 0, $start ) . trim( $new_hero ) . substr( $content, $end ),
		'status'  => 'swap',
		'errors'  => array(),
	);
}

/**
 * Render a theme pattern file to block markup.
 *
 * @param string $filename File name inside the theme `patterns/` directory.
 * @return string
 */
function jn_hhb_render_pattern( string $filename ): string {
	$path = get_stylesheet_directory() . '/patterns/' . $filename;
	if ( ! is_file( $path ) ) {
		return '';
	}
	ob_start();
	include $path;
	return (string) ob_get_clean();
}

$jn_home_id = (int) get_option( 'page_on_front' );
$jn_home    = $jn_home_id > 0 ? get_post( $jn_home_id ) : null;
if ( ! $jn_home instanceof WP_Post ) {
	echo "[FAIL] Front page (page_on_front={$jn_home_id}) not found\n";
	exit( 1 );
}

$jn_new_hero = jn_hhb_render_pattern( 'hero-home.php' );
if ( '' === $jn_new_hero || false === strpos( $jn_new_hero, '<!-- wp:jn/hero-portrait' ) ) {
	echo "[FAIL] patterns/hero-home.php is missing or incomplete — nothing written\n";
	exit( 1 );
}

$jn_result = jn_hhb_swap_hero( $jn_home->post_content, $jn_new_hero );
if ( 'fail' === $jn_result['status'] ) {
	echo '[FAIL] Home: ' . implode( '; ', $jn_result['errors'] ) . " — nothing written\n";
	exit( 1 );
}

if ( 'skip' === $jn_result['status'] ) {
	echo "[SKIP] Home (ID={$jn_home_id}) hero already matches patterns/hero-home.php\n";
} else {
	$jn_updated = wp_update_post(
		array(
			'ID'           => $jn_home_id,
			'post_content' => wp_slash( $jn_result['content'] ),
		),
		true
	);
	if ( is_wp_error( $jn_updated ) ) {
		echo '[FAIL] Home: ' . $jn_updated->get_error_message() . "\n";
		exit( 1 );
	}
	clean_post_cache( $jn_home_id );
	$jn_saved = (string) get_post_field( 'post_content', $jn_home_id, 'raw' );
	if ( $jn_saved !== $jn_result['content'] ) {
		echo "[FAIL] Home: saved content differs from the intended markup (kses?) — restore the latest revision\n";
		exit( 1 );
	}
	echo "[OK] Home (ID={$jn_home_id}) hero replaced; previous content kept as a revision\n";
}

// The theme pattern list is cached (refreshed only inside wp-admin): drop it so jannie/hero-home registers now.
wp_get_theme()->delete_pattern_cache();
echo "[OK] Theme pattern cache cleared\n";

// Every page inlines the theme CSS, so the whole page cache is stale, not only the edited page.
jannie_ensure_front_css_bundle();
echo "[OK] CSS bundle rebuilt\n";
if ( function_exists( 'wp_cache_clear_cache' ) ) {
	wp_cache_clear_cache();
	echo "[OK] WP Super Cache cleared\n";
} else {
	echo "[WARN] wp_cache_clear_cache() unavailable — clear the page cache by hand\n";
}

echo "RESULT: done\n";
