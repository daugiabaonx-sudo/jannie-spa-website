<?php
/**
 * JN Step 13 — Page header typography: one eyebrow / title / lead system for the hero and every
 * page intro (two-line title with the accent italic on its own line, no orphans, unified rhythm,
 * readable lead measure), the shop page aligned with the other pages, and the intro leads filled in.
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/test-step-13-header-typography.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/jn-test-kit.php';
jn_require_non_production( 'test-step-13-header-typography.php' );

echo "=================================================================\n";
echo "JN STEP 13 — PAGE HEADER TYPOGRAPHY\n";
echo "=================================================================\n";

$jn_css_dir = get_stylesheet_directory() . '/assets/css';

/**
 * Read a theme stylesheet with comments stripped.
 *
 * @param string $path Absolute path.
 * @return string
 */
function jn_t13_read_css( string $path ): string {
	$css = is_file( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return (string) preg_replace( '#/\*.*?\*/#s', '', $css );
}

/**
 * Return the declarations of every rule whose selector list contains all the given selectors.
 *
 * @param string   $css       Stylesheet without comments.
 * @param string[] $selectors Selectors that must all appear in the rule's selector list.
 * @return string Declarations joined in source order, or '' when no rule matches.
 */
function jn_t13_rule( string $css, array $selectors ): string {
	preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $css, $jn_rules, PREG_SET_ORDER );
	$jn_bodies = array();
	foreach ( $jn_rules as $jn_rule ) {
		$jn_list = array_map( 'trim', explode( ',', $jn_rule[1] ) );
		if ( array() === array_diff( $selectors, $jn_list ) ) {
			$jn_bodies[] = $jn_rule[2];
		}
	}
	return implode( "\n", $jn_bodies );
}

/**
 * Return the body of every `@media (max-width: …)` block for the given breakpoint.
 *
 * @param string $css       Stylesheet without comments.
 * @param string $max_width Breakpoint, e.g. `1023.98px`.
 * @return string[]
 */
function jn_t13_media_blocks( string $css, string $max_width ): array {
	preg_match_all( '/@media\s*\(max-width:\s*' . preg_quote( $max_width, '/' ) . '\)\s*\{((?:[^{}]*\{[^{}]*\})*[^{}]*)\}/', $css, $jn_blocks );
	return $jn_blocks[1];
}

/**
 * Return the inner HTML of the first `<p>`/`<h1>` in `$html` carrying `$class`.
 *
 * @param string $html  Post content.
 * @param string $class Class to look for.
 * @return string Inner HTML, or '' when absent.
 */
function jn_t13_element_text( string $html, string $class ): string {
	if ( ! preg_match( '/<(p|h1)\b[^>]*class="[^"]*\b' . preg_quote( $class, '/' ) . '\b[^"]*"[^>]*>(.*?)<\/\1>/s', $html, $jn_match ) ) {
		return '';
	}
	return $jn_match[2];
}

$jn_tokens = jn_t13_read_css( $jn_css_dir . '/tokens.css' );
$jn_blocks = jn_t13_read_css( $jn_css_dir . '/block-styles.css' );
$jn_woo    = jn_t13_read_css( $jn_css_dir . '/woocommerce.css' );

// -----------------------------------------------------------------------------
// Measure tokens — named line lengths instead of magic numbers
// -----------------------------------------------------------------------------
jn_t_section( 'Measure tokens' );
jn_t_assert( (bool) preg_match( '/--jn-measure-title:\s*15em;/', $jn_tokens ), 'tokens.css defines --jn-measure-title (15em)' );
jn_t_assert( (bool) preg_match( '/--jn-measure-lead:\s*40rem;/', $jn_tokens ), 'tokens.css defines --jn-measure-lead (40rem, ~70 characters at 18px)' );

// -----------------------------------------------------------------------------
// Page-level title (hero + page intro)
// -----------------------------------------------------------------------------
jn_t_section( 'Page-level title' );
$jn_title = jn_t13_rule( $jn_blocks, array( '.jn-hero-title', '.jn-page-intro-title' ) );
jn_t_assert( '' !== $jn_title, 'The hero and page intro share one title rule' );
jn_t_assert( (bool) preg_match( '/text-wrap:\s*balance/', $jn_title ), 'Titles balance their lines (no single-word last line)', $jn_title );
jn_t_assert( (bool) preg_match( '/max-width:\s*var\(--jn-measure-title\)/', $jn_title ), 'Titles cap their own measure (not the .jn-container wrapper)', $jn_title );
jn_t_assert( (bool) preg_match( '/margin:\s*0 0 var\(--jn-space-lg\) 0\s*!important/', $jn_title ), 'Title to lead gap is --jn-space-lg on every page', $jn_title );

$jn_accent = jn_t13_rule( $jn_blocks, array( '.jn-hero-title .jn-accent-italic', '.jn-page-intro-title .jn-accent-italic' ) );
jn_t_assert( (bool) preg_match( '/display:\s*block/', $jn_accent ), 'The accent italic always starts its own line', $jn_accent );

jn_t_assert( ! preg_match( '/\.jn-page-intro-inner\s*\{[^}]*max-width/', $jn_blocks ), 'No max-width on .jn-page-intro-inner (.jn-container overrode it with !important)' );

$jn_has_mobile_title = false;
foreach ( jn_t13_media_blocks( $jn_blocks, '1023.98px' ) as $jn_media ) {
	if ( false !== strpos( jn_t13_rule( $jn_media, array( '.jn-page-intro-title' ) ), 'var(--jn-text-headline-lg)' ) ) {
		$jn_has_mobile_title = true;
	}
}
jn_t_assert( $jn_has_mobile_title, 'Page intro titles step down to headline-lg below 1024px, like the hero' );

// -----------------------------------------------------------------------------
// Eyebrow and section rhythm
// -----------------------------------------------------------------------------
jn_t_section( 'Eyebrow and rhythm' );
// Both selectors are (0,2,1), so they outrank `p.is-style-eyebrow-dot` (0,1,1, label-sm) wherever they sit.
$jn_eyebrow = jn_t13_rule( $jn_blocks, array( 'p.is-style-eyebrow-dot.jn-hero-eyebrow', '.jn-page-intro-inner > p.is-style-eyebrow-dot' ) );
jn_t_assert( (bool) preg_match( '/font-size:\s*var\(--jn-text-label-md\)/', $jn_eyebrow ), 'Page-level eyebrows use label-md (one size on every page)', $jn_eyebrow );
jn_t_assert( (bool) preg_match( '/margin:\s*0 0 var\(--jn-space-md\) 0\s*!important/', $jn_eyebrow ), 'Eyebrow to title gap is --jn-space-md on every page', $jn_eyebrow );
jn_t_assert( ! preg_match( '/\.jn-hero-eyebrow\s*\{[^}]*margin/', $jn_blocks ), 'No competing .jn-hero-eyebrow margin rule' );

$jn_section = jn_t13_rule( $jn_blocks, array( '.jn-section.jn-page-intro-section' ) );
jn_t_assert( (bool) preg_match( '/padding-block:\s*var\(--jn-space-2xl\)\s*!important/', $jn_section ), 'Page intros use --jn-space-2xl vertical padding', $jn_section );
$jn_has_mobile_section = false;
foreach ( jn_t13_media_blocks( $jn_blocks, '767.98px' ) as $jn_media ) {
	if ( false !== strpos( jn_t13_rule( $jn_media, array( '.jn-section.jn-page-intro-section' ) ), 'var(--jn-space-xl)' ) ) {
		$jn_has_mobile_section = true;
	}
}
jn_t_assert( $jn_has_mobile_section, 'Page intros tighten to --jn-space-xl on phones' );

// -----------------------------------------------------------------------------
// Lead paragraph
// -----------------------------------------------------------------------------
jn_t_section( 'Lead paragraph' );
$jn_lead = jn_t13_rule( $jn_blocks, array( '.jn-hero-lead', '.jn-page-intro-lead' ) );
jn_t_assert( (bool) preg_match( '/max-width:\s*var\(--jn-measure-lead\)/', $jn_lead ), 'Leads share the --jn-measure-lead line length', $jn_lead );
jn_t_assert( (bool) preg_match( '/text-wrap:\s*pretty/', $jn_lead ), 'Leads avoid a short last line (text-wrap: pretty)', $jn_lead );
jn_t_assert( (bool) preg_match( '/font-weight:\s*400/', $jn_lead ), 'Leads use the regular weight', $jn_lead );
jn_t_assert( ! preg_match( '/font-weight:\s*300/', $jn_blocks ), 'No light (300) body copy left in block-styles.css' );

// -----------------------------------------------------------------------------
// In-page section headers (H2)
// -----------------------------------------------------------------------------
jn_t_section( 'Section headers' );
$jn_h2 = jn_t13_rule( $jn_blocks, array( '.jn-section-title-md', '.jn-section-title-lg' ) );
jn_t_assert( (bool) preg_match( '/text-wrap:\s*balance/', $jn_h2 ), 'Section titles balance their lines', $jn_h2 );
$jn_desc = jn_t13_rule( $jn_blocks, array( '.jn-section-header-split__desc', '.jn-section-header-centered__desc', '.jn-scaffold-placeholder > p:not(.is-style-eyebrow)' ) );
jn_t_assert( (bool) preg_match( '/text-wrap:\s*pretty/', $jn_desc ), 'Section descriptions avoid a short last line', $jn_desc );
$jn_scaffold = jn_t13_rule( $jn_blocks, array( '.jn-scaffold-placeholder > p:not(.is-style-eyebrow)' ) );
jn_t_assert( (bool) preg_match( '/max-width:\s*var\(--jn-measure-lead\)/', $jn_scaffold ), 'Scaffold copy keeps a readable measure', $jn_scaffold );

// -----------------------------------------------------------------------------
// Shop page (`woo_categories` prefix) uses the same content area as every other page
// -----------------------------------------------------------------------------
jn_t_section( 'Shop page layout' );
$jn_shop_article = jn_t13_rule( $jn_woo, array( '.woocommerce-shop [class*="ct-container"] > article[class*="post"]' ) );
jn_t_assert( (bool) preg_match( '/--has-wide:\s*var\(--true\)/', $jn_shop_article ) && (bool) preg_match( '/--has-boxed:\s*var\(--false\)/', $jn_shop_article ), 'The shop article is wide like single_page (no 1248px box)', $jn_shop_article );
$jn_shop_spacing = jn_t13_rule( $jn_woo, array( '.woocommerce-shop .ct-container-full[data-vertical-spacing]' ) );
jn_t_assert( (bool) preg_match( '/padding-block:\s*0/', $jn_shop_spacing ), 'The shop page drops Blocksy\'s archive vertical spacing', $jn_shop_spacing );
$jn_collection = jn_t13_rule( $jn_woo, array( '.wp-block-woocommerce-product-collection' ) );
jn_t_assert( (bool) preg_match( '/width:\s*100%/', $jn_collection ) && (bool) preg_match( '/max-width:\s*var\(--jn-container-max\)/', $jn_collection ), 'The product grid fills the container (not Blocksy\'s --theme-block-width) so it lines up with the title', $jn_collection );

// -----------------------------------------------------------------------------
// Content — intro leads filled in, shop header converted to the page intro
// -----------------------------------------------------------------------------
jn_t_section( 'Page content' );
require_once __DIR__ . '/lib/jn-page-copy.php';
$jn_leads = jn_page_intro_leads();
foreach ( array( 'home' => 'jn-hero-lead', 'my-story' => 'jn-page-intro-lead', 'course' => 'jn-page-intro-lead', 'client-success-story' => 'jn-page-intro-lead' ) as $jn_slug => $jn_lead_class ) {
	$jn_page = get_page_by_path( $jn_slug, OBJECT, 'page' );
	$jn_text = $jn_page ? html_entity_decode( wp_strip_all_tags( jn_t13_element_text( $jn_page->post_content, $jn_lead_class ) ), ENT_QUOTES, 'UTF-8' ) : '';
	jn_t_assert( $jn_leads[ $jn_slug ] === $jn_text, "/{$jn_slug}/ intro lead is the shared copy from lib/jn-page-copy.php", $jn_text );
}
jn_t_assert( false === strpos( implode( ' ', $jn_leads ), 'xác minh' ), 'No lead promises a verification process (testimonials are still unverified, UNK-03 / BL-02)' );

// ADR-014: the accent italic is the closing keyword, so it can take its own line without splitting a sentence.
$jn_titles = array();
foreach ( get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1 ) ) as $jn_post ) {
	if ( preg_match_all( '/<h1\b[^>]*class="[^"]*\b(?:jn-page-intro-title|jn-hero-title)\b[^"]*"[^>]*>.*?<\/h1>/s', $jn_post->post_content, $jn_found ) ) {
		foreach ( $jn_found[0] as $jn_h1 ) {
			$jn_titles[ '/' . $jn_post->post_name . '/' ] = $jn_h1;
		}
	}
}
foreach ( array( 'error-404.php', 'page-intro.php', 'hero-editorial.php' ) as $jn_pattern ) {
	$jn_src = (string) file_get_contents( get_stylesheet_directory() . '/patterns/' . $jn_pattern ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( preg_match( '/<h1\b.*?<\/h1>/s', $jn_src, $jn_h1 ) ) {
		$jn_titles[ 'patterns/' . $jn_pattern ] = $jn_h1[0];
	}
}
$jn_not_closing = array_keys( array_filter( $jn_titles, static fn( string $h1 ): bool => ! preg_match( '/<\/em>\s*<\/h1>$/', $h1 ) ) );
jn_t_assert( count( $jn_titles ) >= 10 && array() === $jn_not_closing, 'Every page-level H1 ends with its accent italic (' . count( $jn_titles ) . ' titles)', implode( ', ', $jn_not_closing ) );

$jn_shop    = get_page_by_path( 'my-favorite-product', OBJECT, 'page' );
$jn_shop_c  = $jn_shop ? $jn_shop->post_content : '';
$jn_shop_h1 = preg_match( '/<h1\b[^>]*>/', $jn_shop_c, $jn_h1_tag ) ? $jn_h1_tag[0] : '';
jn_t_assert( false !== strpos( $jn_shop_c, 'jn-container jn-page-intro-inner' ) && false === strpos( $jn_shop_c, 'jn-section-header-centered' ), 'The shop header uses the page intro wrapper' );
jn_t_assert( (bool) preg_match( '/<p class="is-style-eyebrow-dot">Danh Mục Tuyển Chọn<\/p>/', $jn_shop_c ), 'The shop eyebrow is the page-level dot eyebrow' );
jn_t_assert( false !== strpos( $jn_shop_h1, 'jn-page-intro-title' ) && false === strpos( $jn_shop_h1, 'has-text-align-center' ), 'The shop title is left-aligned like the other pages', $jn_shop_h1 );
jn_t_assert( '' !== jn_t13_element_text( $jn_shop_c, 'jn-page-intro-lead' ), 'The shop lead uses the page intro lead' );

// -----------------------------------------------------------------------------
// Seed scripts — a rebuild must not bring the placeholders or the centered shop header back
// -----------------------------------------------------------------------------
jn_t_section( 'Seed scripts' );
$jn_build3 = (string) file_get_contents( __DIR__ . '/build-step-3.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
foreach ( array( 'Đoạn giới thiệu tổng quan hệ sinh thái', 'Lời mở đầu tự sự', 'Danh mục các khóa học chính thức', 'Tổng hợp các hồ sơ case-study' ) as $jn_old ) {
	jn_t_assert( false === strpos( $jn_build3, $jn_old ), "build-step-3.php no longer seeds the \"{$jn_old}…\" placeholder" );
}
$jn_seeds_shared = false !== strpos( $jn_build3, "require_once __DIR__ . '/lib/jn-page-copy.php';" );
foreach ( array_keys( $jn_leads ) as $jn_slug ) {
	$jn_seeds_shared = $jn_seeds_shared && false !== strpos( $jn_build3, "\$jn_leads['{$jn_slug}']" );
}
jn_t_assert( $jn_seeds_shared, 'build-step-3.php seeds every intro lead from lib/jn-page-copy.php (no second copy to drift)' );
$jn_setup4 = (string) file_get_contents( __DIR__ . '/setup-step-4-woocommerce.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
jn_t_assert( false !== strpos( $jn_setup4, 'jn-container jn-page-intro-inner' ) && false === strpos( $jn_setup4, 'jn-container jn-section-header-centered' ), 'setup-step-4-woocommerce.php seeds the page intro shop header' );

// -----------------------------------------------------------------------------
// Bundle — the inlined stylesheet carries the new rules
// -----------------------------------------------------------------------------
jn_t_section( 'CSS bundle' );
jannie_ensure_front_css_bundle();
clearstatcache();
$jn_bundle = (string) file_get_contents( $jn_css_dir . '/jannie-bundle.min.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
jn_t_assert( false !== strpos( $jn_bundle, '--jn-measure-title:15em' ) && false !== strpos( $jn_bundle, 'text-wrap:balance' ), 'The front-end bundle includes the header typography rules' );

jn_t_finish();
