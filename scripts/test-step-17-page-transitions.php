<?php
/**
 * JN Step 17 — Page transitions without jank (ADR-024): the first paint of every page is final
 * (Blocksy CSS and Playfair Display before first paint), the header keeps one geometry on every page,
 * and same-origin navigations cross-fade through native cross-document View Transitions.
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/test-step-17-page-transitions.php
 *
 * Browser-level checks (geometry before/after first input, view transition, fonts before FCP) live in
 * scripts/verify-step-17-transitions.mjs.
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/jn-test-kit.php';
jn_require_non_production( 'test-step-17-page-transitions.php' );

echo "=================================================================\n";
echo "JN STEP 17 — PAGE TRANSITIONS\n";
echo "=================================================================\n";

$jn_css_dir = get_stylesheet_directory() . '/assets/css';

/**
 * Fetch a front-end page as a visitor (inside the wpcli container the site answers on `wordpress`).
 *
 * @param string $path Request path.
 * @return string Response body ('' on error).
 */
function jn_t17_http_get( string $path ): string {
	$base    = 'http://localhost:8080';
	$headers = array();
	if ( gethostbyname( 'wordpress' ) !== 'wordpress' ) {
		$base            = 'http://wordpress';
		$headers['Host'] = 'localhost:8080';
	}
	$res = wp_remote_get(
		$base . $path,
		array(
			'timeout'     => 15,
			'redirection' => 0,
			'headers'     => $headers,
		)
	);
	return 200 === wp_remote_retrieve_response_code( $res ) ? (string) wp_remote_retrieve_body( $res ) : '';
}

/**
 * Drop every cached page so the assertions read freshly rendered HTML.
 *
 * @return void
 */
function jn_t17_purge_page_cache(): void {
	$cache_dir = WP_CONTENT_DIR . '/cache/supercache';
	if ( ! is_dir( $cache_dir ) ) {
		return;
	}
	$it = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $cache_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $it as $fs_item ) {
		if ( $fs_item->isDir() ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			@rmdir( $fs_item->getRealPath() );
		} else {
			wp_delete_file( $fs_item->getRealPath() );
		}
	}
}

/**
 * Read a theme stylesheet with comments stripped.
 *
 * @param string $path Absolute path.
 * @return string
 */
function jn_t17_read_css( string $path ): string {
	$css = is_file( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return (string) preg_replace( '#/\*.*?\*/#s', '', $css );
}

/**
 * Properties declared inside the named `@keyframes` blocks.
 *
 * @param string   $css   Stylesheet without comments.
 * @param string[] $names Keyframes names.
 * @return string[]
 */
function jn_t17_keyframe_properties( string $css, array $names ): array {
	$props = array();
	foreach ( $names as $name ) {
		if ( preg_match( '/@keyframes\s+' . preg_quote( $name, '/' ) . '\s*\{((?:[^{}]*\{[^}]*\})*)\s*\}/', $css, $m ) ) {
			preg_match_all( '/([a-z-]+)\s*:/', $m[1], $declared );
			$props = array_merge( $props, $declared[1] );
		}
	}
	return array_values( array_unique( $props ) );
}

jn_t17_purge_page_cache();
$jn_pages = array();
foreach ( array( '/', '/my-story/', '/booking/', '/my-favorite-product/' ) as $jn_path ) {
	$jn_pages[ $jn_path ] = jn_t17_http_get( $jn_path );
}

// -----------------------------------------------------------------------------
// R1 — Blocksy's main stylesheet is applied before first paint
// -----------------------------------------------------------------------------
jn_t_section( 'Blocksy CSS before first paint' );
foreach ( $jn_pages as $jn_path => $jn_html ) {
	preg_match( "/<link\b[^>]*id=['\"]ct-main-styles-css['\"][^>]*>/i", $jn_html, $jn_link );
	$jn_tag = $jn_link[0] ?? '';
	jn_t_assert(
		'' !== $jn_tag && (bool) preg_match( '/\shref=/', $jn_tag ) && false === strpos( $jn_tag, 'data-jn-href' ),
		"{$jn_path} links Blocksy main.min.css with a real href (not activated on first input)",
		$jn_tag
	);
}

$jn_main_tag = "<link rel='stylesheet' id='ct-main-styles-css' href='http://localhost:8080/static/base/static/bundle/main.min.css' media='all' />\n";
jn_t_assert( jn_defer_non_critical_stylesheets( $jn_main_tag, 'ct-main-styles' ) === $jn_main_tag, 'jn_defer_non_critical_stylesheets() leaves ct-main-styles render-blocking' );
$jn_wc_tag = "<link rel='stylesheet' id='wc-blocks-style-css' href='http://localhost:8080/static/shop/wc-blocks.css' media='all' />\n";
jn_t_assert( false !== strpos( jn_defer_non_critical_stylesheets( $jn_wc_tag, 'wc-blocks-style' ), 'data-jn-href=' ), 'Commerce stylesheets are still deferred past first paint' );
// Blocksy's WooCommerce stylesheet lays out the account forms and the single product (gallery | summary), so it
// renders before first paint there; on the shop, cart and checkout it moves nothing above the fold.
$jn_product      = function_exists( 'wc_get_products' ) ? wc_get_products( array( 'status' => 'publish', 'limit' => 1 ) ) : array();
$jn_layout_pages = array( '/tai-khoan/' => jn_t17_http_get( '/tai-khoan/' ) );
if ( array() !== $jn_product ) {
	$jn_product_path                     = (string) wp_parse_url( get_permalink( $jn_product[0]->get_id() ), PHP_URL_PATH );
	$jn_layout_pages[ $jn_product_path ] = jn_t17_http_get( $jn_product_path );
} else {
	echo "[SKIP] No published product: single-product WooCommerce CSS check not run\n";
}
foreach ( $jn_layout_pages as $jn_path => $jn_html ) {
	preg_match( "/<link\b[^>]*id=['\"]ct-woocommerce-styles-css['\"][^>]*>/i", $jn_html, $jn_link );
	$jn_tag = $jn_link[0] ?? '';
	jn_t_assert(
		'' !== $jn_tag && (bool) preg_match( '/\shref=/', $jn_tag ) && false === strpos( $jn_tag, 'data-jn-href' ),
		"{$jn_path} links Blocksy's WooCommerce CSS before first paint (it lays out this page)",
		$jn_tag
	);
}

$jn_shop = $jn_pages['/my-favorite-product/'];
jn_t_assert(
	(bool) preg_match( "/<link\b[^>]*id=['\"]wc-blocks-style-css['\"][^>]*data-jn-href=/i", $jn_shop )
	&& false !== strpos( $jn_shop, '<script id="jn-deferred-assets">' )
	&& false !== strpos( $jn_shop, "querySelectorAll('link[data-jn-href]')" ),
	'The shop page still defers its WooCommerce stylesheet and prints the loader that activates it'
);

// -----------------------------------------------------------------------------
// R2 — Playfair Display is declared inline and preloaded (no JS FontFace on first input)
// -----------------------------------------------------------------------------
jn_t_section( 'Fonts before first paint' );
$jn_home = $jn_pages['/'];
preg_match( '#<style id="jn-tokens-inline-css">(.*?)</style>#s', $jn_home, $jn_inline );
$jn_inline_css = $jn_inline[1] ?? '';
jn_t_assert( false !== strpos( $jn_inline_css, "@font-face{font-family:'Playfair Display';font-style:normal" ), 'The inline bundle declares Playfair Display (normal)' );
jn_t_assert( false !== strpos( $jn_inline_css, "@font-face{font-family:'Playfair Display';font-style:italic" ), 'The inline bundle declares Playfair Display (italic)' );

preg_match_all( '/<link\s+rel="preload"[^>]+as="font"[^>]*>/i', $jn_home, $jn_font_preloads );
$jn_preloads = implode( ' ', $jn_font_preloads[0] );
// Without the Playfair preload, cold mobile Lighthouse runs got a later FCP and a font-swap CLS of 0.063 on
// /coaching/ while LCP did not improve (ADR-024), so the documented Step 8 pair stays preloaded.
jn_t_assert(
	2 === count( $jn_font_preloads[0] ) && false !== strpos( $jn_preloads, 'playfair-display-vi-normal.woff2' ) && false !== strpos( $jn_preloads, 'geist-vi-normal.woff2' ),
	'<head> preloads exactly Playfair Display (normal) and Geist',
	$jn_preloads
);
jn_t_assert( false === strpos( $jn_home, 'new FontFace(' ), 'No font is injected by script after first input' );
jn_t_assert( false === strpos( $jn_home, "<noscript><style>@font-face{font-family:'Playfair Display'" ), 'No duplicate <noscript> Playfair @font-face' );

// -----------------------------------------------------------------------------
// R3 — The active menu item does not change the menu's width
// -----------------------------------------------------------------------------
jn_t_section( 'Menu labels keep their place' );
$jn_tokens = jn_t17_read_css( $jn_css_dir . '/tokens.css' );
$jn_base   = jn_t17_read_css( $jn_css_dir . '/base.css' );
jn_t_assert( (bool) preg_match( '/--jn-nav-semibold-tracking-offset:\s*-0\.0092em;/', $jn_tokens ), 'tokens.css defines the semibold tracking offset' );
preg_match( '/li\.current-menu-item\s*>\s*a\.ct-menu-link,[^{]*\{([^}]*)\}/', $jn_base, $jn_active );
$jn_active_rule = $jn_active[1] ?? '';
jn_t_assert(
	(bool) preg_match( '/font-weight:\s*600/', $jn_active_rule ) && (bool) preg_match( '/letter-spacing:\s*calc\(0\.02em \+ var\(--jn-nav-semibold-tracking-offset\)\)/', $jn_active_rule ),
	'The semibold active item tracks tighter by the same width it gains',
	$jn_active_rule
);

// -----------------------------------------------------------------------------
// R4 + R6 — Stable scrollbar gutter; smooth scrolling only after load
// -----------------------------------------------------------------------------
jn_t_section( 'Scrollbar gutter and anchors' );
preg_match( '/(?:^|\})\s*html\s*\{([^}]*)\}/', $jn_base, $jn_html_rule );
$jn_html_decls = $jn_html_rule[1] ?? '';
jn_t_assert( (bool) preg_match( '/scrollbar-gutter:\s*stable/', $jn_html_decls ), 'html reserves the scrollbar gutter', $jn_html_decls );
jn_t_assert( (bool) preg_match( '/scroll-behavior:\s*auto/', $jn_html_decls ), 'html overrides Blocksy\'s smooth scroll-behavior while the page loads', $jn_html_decls );
jn_t_assert( (bool) preg_match( '/html\.jn-smooth-scroll\s*\{[^}]*scroll-behavior:\s*smooth/', $jn_base ), 'In-page anchors glide once html.jn-smooth-scroll is set' );

ob_start();
jannie_print_motion_script();
$jn_script = (string) ob_get_clean();
jn_t_assert(
	false !== strpos( $jn_script, "['pointerdown', 'keydown', 'touchstart']" ) && false !== strpos( $jn_script, "classList.add('jn-smooth-scroll')" ),
	'The motion script turns smooth scrolling on at the first input (the fragment scroll on load stays instant)'
);

// -----------------------------------------------------------------------------
// R5 — Native cross-document View Transitions
// -----------------------------------------------------------------------------
jn_t_section( 'View transitions' );
$jn_motion = jn_t17_read_css( $jn_css_dir . '/motion.css' );
jn_t_assert(
	(bool) preg_match( '/@media\s*\(prefers-reduced-motion:\s*no-preference\)\s*\{\s*@view-transition\s*\{\s*navigation:\s*auto;?\s*\}\s*\}/', $jn_motion ),
	'Same-origin navigations opt in to view transitions, only without reduced motion'
);
jn_t_assert( (bool) preg_match( '/#header\.ct-header\s*\{\s*view-transition-name:\s*jn-header;?\s*\}/', $jn_motion ), 'The header is captured on its own (it never travels)' );
jn_t_assert( (bool) preg_match( '/#offcanvas\.active\s*\{\s*view-transition-name:\s*jn-offcanvas;?\s*\}/', $jn_motion ), 'An open off-canvas drawer fades above the header' );
jn_t_assert( (bool) preg_match( '/::view-transition-group\(jn-header\)\s*\{[^}]*animation-name:\s*none/', $jn_motion ), 'The header group does not tween size or position' );
jn_t_assert(
	(bool) preg_match( '/::view-transition-old\(root\)\s*\{[^}]*jn-page-out/', $jn_motion ) && (bool) preg_match( '/::view-transition-new\(root\)\s*\{[^}]*jn-page-in[^}]*var\(--jn-ease-ios\)/', $jn_motion ),
	'Page content fades out and rises in with the iOS easing'
);
$jn_vt_props = jn_t17_keyframe_properties( $jn_motion, array( 'jn-page-in', 'jn-page-out' ) );
jn_t_assert(
	array() !== $jn_vt_props && array() === array_diff( $jn_vt_props, array( 'opacity', 'transform' ) ),
	'Page transition keyframes animate only opacity and transform',
	implode( ', ', $jn_vt_props )
);
jn_t_assert( (bool) preg_match( '/--jn-ease-ios:\s*cubic-bezier\(0\.32,\s*0\.72,\s*0,\s*1\)/', $jn_tokens ) && (bool) preg_match( '/--jn-motion-page:\s*300ms/', $jn_tokens ), 'tokens.css defines the page transition duration and easing' );
preg_match( '/@media\s*\(prefers-reduced-motion:\s*reduce\)\s*\{(.*?)\n\}/s', $jn_tokens, $jn_reduce_block );
jn_t_assert( (bool) preg_match( '/--jn-motion-page:\s*0ms/', $jn_reduce_block[1] ?? '' ), 'Reduced motion zeroes the page transition duration' );
jn_t_assert( false !== strpos( $jn_inline_css, '@view-transition{navigation:auto}' ), 'The inlined bundle ships the view transition opt-in on every page' );

jn_t_finish();
