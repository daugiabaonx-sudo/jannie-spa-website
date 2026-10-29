<?php
/**
 * JN — Hero media card cleanup (ADR-016): the decorative "1:1" watermark and the
 * "PRO-COACHING // SPEC.24" badge are gone from the pattern, the stored Home/Coaching
 * content, the stylesheets and the block filters, while the ∞ icon stays top-right and
 * the real "1:1" copy (metrics bar, CTAs) is untouched.
 *
 * Run via (after `build-hero-overlay-cleanup.php`):
 *   docker compose run --rm wpcli wp eval-file /scripts/test-hero-overlay-cleanup.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/jn-test-kit.php';
jn_require_non_production( 'test-hero-overlay-cleanup.php' );

echo "=================================================================\n";
echo "JN — HERO MEDIA CARD CLEANUP (ADR-016)\n";
echo "=================================================================\n";

const JN_THO_REMOVED = array( 'jn-hero-watermark', 'jn-hero-spec-badge', 'SPEC.24' );

/**
 * Return which of the removed markers a string still contains.
 *
 * @param string $haystack Markup or CSS.
 * @return string[]
 */
function jn_tho_leftovers( string $haystack ): array {
	return array_values( array_filter( JN_THO_REMOVED, static fn( string $needle ): bool => false !== strpos( $haystack, $needle ) ) );
}

/**
 * Read a file, or an empty string when it is missing.
 *
 * @param string $path Absolute path.
 * @return string
 */
function jn_tho_read( string $path ): string {
	return is_file( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Pattern source' );

$jn_pattern = jn_tho_read( get_stylesheet_directory() . '/patterns/hero-editorial.php' );
jn_t_assert( '' !== $jn_pattern, 'hero-editorial.php is readable' );
jn_t_assert( empty( jn_tho_leftovers( $jn_pattern ) ), 'Pattern has no watermark / spec badge', implode( ', ', jn_tho_leftovers( $jn_pattern ) ) );
jn_t_assert( false !== strpos( $jn_pattern, 'jn-hero-overlay-top' ) && false !== strpos( $jn_pattern, '"name":"all_inclusive"' ), 'Pattern keeps the ∞ icon row' );
jn_t_assert( false !== strpos( $jn_pattern, 'jn-hero-glass-title' ), 'Pattern keeps the glass caption' );

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Stored + rendered page content' );

foreach ( array( 'home', 'coaching' ) as $jn_slug ) {
	$jn_page = get_page_by_path( $jn_slug );
	jn_t_assert( $jn_page instanceof WP_Post, "{$jn_slug}: page exists" );
	if ( ! $jn_page instanceof WP_Post ) {
		continue;
	}
	$jn_rendered = (string) apply_filters( 'the_content', $jn_page->post_content );
	jn_t_assert( empty( jn_tho_leftovers( $jn_page->post_content ) ), "{$jn_slug}: stored content has no watermark / spec badge", implode( ', ', jn_tho_leftovers( $jn_page->post_content ) ) );
	jn_t_assert( empty( jn_tho_leftovers( $jn_rendered ) ), "{$jn_slug}: rendered HTML has no watermark / spec badge", implode( ', ', jn_tho_leftovers( $jn_rendered ) ) );
	jn_t_assert( false !== strpos( $jn_rendered, 'jn-hero-overlay-top' ), "{$jn_slug}: ∞ icon row still renders" );
	jn_t_assert( false !== strpos( $jn_rendered, 'jn-hero-glass-title' ), "{$jn_slug}: glass caption still renders" );
	jn_t_assert( false !== strpos( $jn_rendered, '1:1' ), "{$jn_slug}: real \"1:1\" copy is untouched" );
	jn_t_assert( has_blocks( $jn_page->post_content ) && count( parse_blocks( $jn_page->post_content ) ) > 0, "{$jn_slug}: content still parses as blocks" );
}

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Stylesheets' );

$jn_css_dir = get_stylesheet_directory() . '/assets/css';
foreach ( array( 'block-styles.css', 'jannie-bundle.min.css' ) as $jn_css_file ) {
	$jn_css = jn_tho_read( $jn_css_dir . '/' . $jn_css_file );
	jn_t_assert( '' !== $jn_css, "{$jn_css_file} is readable" );
	jn_t_assert( empty( jn_tho_leftovers( $jn_css ) ), "{$jn_css_file} has no watermark / spec badge rules", implode( ', ', jn_tho_leftovers( $jn_css ) ) );
	jn_t_assert(
		1 === preg_match( '/\.jn-hero-overlay-top\s*\{[^}]*justify-content:\s*flex-end/', $jn_css ),
		"{$jn_css_file}: ∞ icon row is right-aligned (flex-end) now that it has one child"
	);
}

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Block filters' );

jn_t_assert( ! method_exists( 'JN_Blocks', 'filter_decorative_watermark_paragraph' ), 'Dead watermark aria-hidden filter is removed' );
jn_t_assert( false === has_filter( 'render_block_core/paragraph', array( 'JN_Blocks', 'filter_decorative_watermark_paragraph' ) ), 'No paragraph render filter is registered for the watermark' );

jn_t_finish();
