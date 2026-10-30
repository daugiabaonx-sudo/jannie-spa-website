<?php
/**
 * JN — Home hero (ADR-021): the `jannie/hero-home` pattern and the `jn/hero-portrait` block.
 *
 * Covers the block (registration, image fallback, LCP attributes, swap toggle, escaping), the pattern
 * source (core + jn/* blocks only, ADR-014 title, shared lead, metrics, real CTA targets), the stylesheet
 * (bundle, reduced motion, no hidden LCP), the stored Home / Coaching content and the live `/` page.
 *
 * Run via (after `build-home-hero.php`):
 *   docker compose run --rm wpcli wp eval-file /scripts/test-home-hero.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/jn-test-kit.php';
require_once __DIR__ . '/lib/jn-page-copy.php';
jn_require_non_production( 'test-home-hero.php' );

echo "=================================================================\n";
echo "JN — HOME HERO (ADR-021)\n";
echo "=================================================================\n";

/**
 * Read a file, or an empty string when it is missing.
 *
 * @param string $path Absolute path.
 * @return string
 */
function jn_thh_read( string $path ): string {
	return is_file( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

/**
 * Return every `<img>` tag whose class list contains the given class.
 *
 * @param string $html  Markup.
 * @param string $class CSS class.
 * @return string[]
 */
function jn_thh_imgs( string $html, string $class ): array {
	preg_match_all( '/<img\b[^>]*\bclass="[^"]*\b' . preg_quote( $class, '/' ) . '\b[^"]*"[^>]*>/', $html, $found );
	return $found[0];
}

/**
 * Read one attribute from a tag.
 *
 * @param string $tag  Opening tag.
 * @param string $name Attribute name.
 * @return string|null Null when the attribute is absent.
 */
function jn_thh_attr( string $tag, string $name ): ?string {
	return preg_match( '/\s' . preg_quote( $name, '/' ) . '="([^"]*)"/', $tag, $m ) ? $m[1] : null;
}

/**
 * Render a `jn/hero-portrait` block with the given attributes.
 *
 * @param array<string, mixed> $attrs Block attributes.
 * @return string
 */
function jn_thh_render( array $attrs = array() ): string {
	return render_block(
		array(
			'blockName'    => 'jn/hero-portrait',
			'attrs'        => $attrs,
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		)
	);
}

$jn_hero_id  = (int) ( get_option( 'jn_step3_media_map', array() )['hero_portrait']['id'] ?? 86 );
$jn_alt_id   = (int) ( get_option( 'jn_legacy_media_map', array() )['banner_home']['id'] ?? 0 );
$jn_lead     = jn_page_intro_leads()['home'];
$jn_theme    = get_stylesheet_directory();
$jn_core_dir = WP_PLUGIN_DIR . '/jn-core/blocks/hero-portrait';

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Block jn/hero-portrait' );

jn_t_assert( $jn_alt_id > 0 && wp_attachment_is_image( $jn_alt_id ), 'The jannieedu.com banner photo (secondary image) exists', (string) $jn_alt_id );
jn_t_assert( WP_Block_Type_Registry::get_instance()->is_registered( 'jn/hero-portrait' ), 'jn/hero-portrait is registered' );
jn_t_assert( class_exists( 'JN_Hero_Portrait' ), 'JN_Hero_Portrait class is loaded' );

if ( class_exists( 'JN_Hero_Portrait' ) ) {
	jn_t_assert( $jn_hero_id === JN_Hero_Portrait::resolve_image_id( 0, 'primary' ), 'Missing primary ID falls back to the hero portrait' );
	jn_t_assert( $jn_alt_id === JN_Hero_Portrait::resolve_image_id( 0, 'secondary' ), 'Missing secondary ID falls back to the banner photo' );
	jn_t_assert( $jn_hero_id === JN_Hero_Portrait::resolve_image_id( (int) get_option( 'page_on_front' ), 'primary' ), 'A non-image ID (a page) falls back instead of rendering nothing' );
	jn_t_assert( $jn_alt_id === JN_Hero_Portrait::resolve_image_id( $jn_alt_id, 'primary' ), 'A valid image ID is kept as chosen' );

	jn_t_assert( JN_Hero_Portrait::sizes() === JN_Hero_Portrait::sizes( 1.0 ), 'sizes() defaults to an unscaled frame' );
	jn_t_assert( (bool) preg_match( '/^\(max-width: 639px\) \d+vw, \(max-width: 1023px\) \d+px, \d+px$/', JN_Hero_Portrait::sizes() ), 'sizes() covers mobile, tablet and desktop', JN_Hero_Portrait::sizes() );
	jn_t_assert( JN_Hero_Portrait::sizes() === JN_Hero_Portrait::sizes_for( $jn_hero_id ), 'The 4:5 portrait needs no wider source than the frame' );
	jn_t_assert( JN_Hero_Portrait::sizes() !== JN_Hero_Portrait::sizes_for( $jn_alt_id ), 'The 16:9 banner asks for a wider source (object-fit: cover crops it)', JN_Hero_Portrait::sizes_for( $jn_alt_id ) );

	$jn_html    = jn_thh_render();
	$jn_primary = jn_thh_imgs( $jn_html, 'jn-hero-portrait__img--primary' );
	$jn_second  = jn_thh_imgs( $jn_html, 'jn-hero-portrait__img--alt' );
	$jn_thumb   = jn_thh_imgs( $jn_html, 'jn-hero-portrait__thumb' );
	jn_t_assert( 1 === count( $jn_primary ) && 1 === count( $jn_second ) && 1 === count( $jn_thumb ), 'Renders the primary, secondary and polaroid images once each' );

	$jn_p = $jn_primary[0] ?? '';
	jn_t_assert( false !== strpos( $jn_p, 'wp-image-' . $jn_hero_id ), "Primary carries wp-image-{$jn_hero_id} so the jn-performance LCP rules apply" );
	jn_t_assert( 'eager' === jn_thh_attr( $jn_p, 'loading' ) && 'high' === jn_thh_attr( $jn_p, 'fetchpriority' ), 'Primary is eager + fetchpriority="high"' );
	jn_t_assert( null !== jn_thh_attr( $jn_p, 'width' ) && null !== jn_thh_attr( $jn_p, 'height' ) && null !== jn_thh_attr( $jn_p, 'srcset' ), 'Primary has width, height and srcset' );
	jn_t_assert( '' !== (string) jn_thh_attr( $jn_p, 'alt' ), 'Primary keeps its alt text' );

	$jn_s = $jn_second[0] ?? '';
	jn_t_assert( 'lazy' === jn_thh_attr( $jn_s, 'loading' ) && null === jn_thh_attr( $jn_s, 'fetchpriority' ), 'Secondary is lazy and never high priority' );
	jn_t_assert( JN_Hero_Portrait::sizes_for( $jn_alt_id ) === jn_thh_attr( $jn_s, 'sizes' ), 'Secondary sizes account for the cover crop' );

	$jn_t = $jn_thumb[0] ?? '';
	jn_t_assert( '' === jn_thh_attr( $jn_t, 'alt' ) && 'lazy' === jn_thh_attr( $jn_t, 'loading' ), 'Polaroid thumbnail is decorative (alt="") and lazy' );

	$jn_input_id = preg_match( '/<input\b[^>]*type="checkbox"[^>]*\bid="([^"]+)"/', $jn_html, $jn_m ) ? $jn_m[1] : '';
	jn_t_assert( '' !== $jn_input_id && false !== strpos( $jn_html, 'for="' . $jn_input_id . '"' ), 'The polaroid <label> toggles the swap checkbox' );
	$jn_label = preg_match( '/<label\b.*?<\/label>/s', $jn_html, $jn_m ) ? $jn_m[0] : '';
	jn_t_assert( ! preg_match( '/<input\b[^>]*aria-label=/', $jn_html ), 'The checkbox is named by its visible <label>, not an aria-label that hides it (WCAG 2.5.3)' );
	jn_t_assert( false !== strpos( $jn_label, 'lang="en">Tap to explore' ) && false !== strpos( $jn_label, '<span class="screen-reader-text"> — Đổi ảnh chân dung</span>' ), 'Label = visible English words (lang="en") + Vietnamese description for screen readers', $jn_label );
	jn_t_assert( false === strpos( jn_thh_render( array( 'labelLang' => '" onfocus="x' ) ), 'onfocus' ), 'An invalid label language is dropped, not printed' );
	jn_t_assert( 3 <= substr_count( $jn_html, 'aria-hidden="true"' ), 'Outline, caption and side text are hidden from screen readers' );
	jn_t_assert( false === strpos( $jn_html, 'unsplash' ), 'No third-party images' );

	$jn_id_2 = preg_match( '/<input\b[^>]*\bid="([^"]+)"/', jn_thh_render(), $jn_m ) ? $jn_m[1] : '';
	jn_t_assert( '' !== $jn_id_2 && $jn_id_2 !== $jn_input_id, 'Two blocks on one page get distinct checkbox IDs' );

	$jn_evil = jn_thh_render(
		array(
			'captionTitle'  => '<script>alert(1)</script>',
			'toggleLabel'   => '"><img src=x onerror=alert(1)>',
			'polaroidLabel' => '<b>x</b>',
		)
	);
	jn_t_assert( false === strpos( $jn_evil, '<script>' ) && false === strpos( $jn_evil, '<img src=x' ) && false === strpos( $jn_evil, '<b>x</b>' ), 'Text attributes are escaped' );
}

// ---------------------------------------------------------------------------------------------
jn_t_section( 'LCP follows the block (jn-performance)' );

if ( class_exists( 'JN_Hero_Portrait' ) && function_exists( 'jn_hero_portrait_lcp_id' ) ) {
	$jn_block_with = static fn( int $id ): string => '<!-- wp:group --><div class="wp-block-group"><!-- wp:jn/hero-portrait {"primaryId":' . $id . '} /--></div><!-- /wp:group -->';
	jn_t_assert( $jn_alt_id === JN_Hero_Portrait::primary_id_in( $jn_block_with( $jn_alt_id ) ), 'primary_id_in() finds a nested block and its chosen image' );
	jn_t_assert( $jn_hero_id === JN_Hero_Portrait::primary_id_in( '<!-- wp:jn/hero-portrait /-->' ), 'primary_id_in() falls back like the block does' );
	jn_t_assert( 0 === JN_Hero_Portrait::primary_id_in( '<!-- wp:paragraph --><p>x</p><!-- /wp:paragraph -->' ), 'primary_id_in() is 0 without the block' );

	// Simulate the front page query, as the content filter sees it while rendering `/`.
	$jn_front_id             = (int) get_option( 'page_on_front' );
	$jn_cli_query            = $GLOBALS['wp_the_query'];
	$GLOBALS['wp_query']     = new WP_Query( array( 'page_id' => $jn_front_id ) );
	$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
	jn_t_assert( $jn_hero_id === jn_hero_portrait_lcp_id(), 'On / the LCP ID comes from the stored block', (string) jn_hero_portrait_lcp_id() );
	jn_t_assert( JN_Hero_Portrait::sizes() === jn_hero_image_sizes(), 'On / the hero sizes are the arched frame sizes' );
	$jn_marker = jn_optimize_content_img_tag( '<img src="x.jpg" class="jn-hero-portrait__img jn-hero-portrait__img--primary wp-image-' . $jn_alt_id . '" loading="lazy">', 'the_content', $jn_alt_id );
	jn_t_assert( false !== strpos( $jn_marker, 'fetchpriority="high"' ) && false !== strpos( $jn_marker, 'loading="eager"' ), 'A primary portrait picked in the editor (not the media-map ID) stays eager + high', $jn_marker );
	$jn_other = jn_optimize_content_img_tag( '<img src="x.jpg" class="wp-image-' . $jn_hero_id . '" loading="eager">', 'the_content', $jn_hero_id );
	jn_t_assert( false === strpos( $jn_other, 'fetchpriority' ) && false !== strpos( $jn_other, 'loading="lazy"' ), 'On a hero-portrait page another copy of the media-map hero is not a second LCP', $jn_other );
	$GLOBALS['wp_the_query'] = $jn_cli_query;
	wp_reset_query();
	jn_t_assert( ! is_singular() && 0 === jn_hero_portrait_lcp_id(), 'Outside a singular page there is no portrait LCP' );
}

$jn_editor_js = jn_thh_read( $jn_core_dir . '/editor.js' );
jn_t_assert( false !== strpos( $jn_editor_js, "registerBlockType( 'jn/hero-portrait'" ) && false !== strpos( $jn_editor_js, 'MediaUpload' ), 'Editor script registers the block with image pickers' );

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Pattern jannie/hero-home' );

jn_t_assert( is_file( $jn_theme . '/patterns/hero-home.php' ), 'patterns/hero-home.php exists' );
jn_t_assert( WP_Block_Patterns_Registry::get_instance()->is_registered( 'jannie/hero-home' ), 'jannie/hero-home is registered' );

$jn_pattern = '';
if ( is_file( $jn_theme . '/patterns/hero-home.php' ) ) {
	ob_start();
	include $jn_theme . '/patterns/hero-home.php';
	$jn_pattern = (string) ob_get_clean();
}
$jn_names = array();
$jn_walk  = static function ( array $blocks ) use ( &$jn_walk, &$jn_names ): void {
	foreach ( $blocks as $block ) {
		if ( ! empty( $block['blockName'] ) ) {
			$jn_names[] = $block['blockName'];
		}
		$jn_walk( $block['innerBlocks'] ?? array() );
	}
};
$jn_walk( parse_blocks( $jn_pattern ) );
$jn_foreign = array_filter( $jn_names, static fn( string $n ): bool => 'core/html' === $n || ( ! str_starts_with( $n, 'core/' ) && ! str_starts_with( $n, 'jn/' ) ) );
jn_t_assert( count( $jn_names ) > 10 && array() === $jn_foreign, 'Only core/* and jn/* blocks, no core/html (' . count( $jn_names ) . ' blocks)', implode( ', ', $jn_foreign ) );
jn_t_assert( 1 === substr_count( $jn_pattern, '<!-- wp:jn/hero-portrait' ), 'Uses the jn/hero-portrait block once' );

$jn_h1      = preg_match( '/<h1\b.*?<\/h1>/s', $jn_pattern, $jn_m ) ? $jn_m[0] : '';
$jn_h1_text = html_entity_decode( trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags( $jn_h1 ) ) ), ENT_QUOTES, 'UTF-8' );
jn_t_assert( (bool) preg_match( '/class="[^"]*\bjn-hero-title\b/', $jn_h1 ) && (bool) preg_match( '/<\/em>\s*<\/h1>$/', $jn_h1 ), 'H1 is a jn-hero-title ending with its accent italic (ADR-014)', $jn_h1 );
jn_t_assert( 'Khám Phá Sự Cân Bằng & Vẻ Đẹp Đích Thực' === $jn_h1_text, 'H1 reads as one sentence (a space survives each <br> for SEO / screen readers)', $jn_h1_text );
jn_t_assert( 1 === substr_count( $jn_h1, '<br' ), 'H1 breaks into the designed three lines (one <br>, the italic takes its own line)' );
jn_t_assert( false !== strpos( html_entity_decode( $jn_pattern, ENT_QUOTES, 'UTF-8' ), $jn_lead ), 'Lead is the shared copy from lib/jn-page-copy.php' );
jn_t_assert( (bool) preg_match( '/<p class="jn-hero-lead\b/', $jn_pattern ), 'Lead keeps the .jn-hero-lead class (speakable selector)' );
jn_t_assert( false !== strpos( $jn_pattern, '>03<' ) && false !== strpos( $jn_pattern, 'Hướng Đồng Hành' ) && false === strpos( $jn_pattern, '98%' ), 'Third metric is "03 Hướng Đồng Hành" (98% removed)' );
jn_t_assert( (bool) preg_match( '/href="#booking-form"[^>]*>Đặt Lịch Tư Vấn 1:1</', $jn_pattern ), 'Primary CTA books a 1:1 consultation (#booking-form)' );
jn_t_assert( (bool) preg_match( '/href="[^"]*\/coaching\/"[^>]*>Khám Phá Coaching</', $jn_pattern ), 'Secondary CTA opens /coaching/' );
jn_t_assert( false === strpos( $jn_pattern, '<details' ) && false === stripos( $jn_pattern, 'demo' ), 'No demo <details> panels' );

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Stylesheet' );

$jn_css    = (string) preg_replace( '#/\*.*?\*/#s', '', jn_thh_read( $jn_theme . '/assets/css/hero-home.css' ) );
$jn_bundle = jn_thh_read( $jn_theme . '/assets/css/jannie-bundle.min.css' );
jn_t_assert( '' !== $jn_css, 'assets/css/hero-home.css exists' );
jn_t_assert( false !== strpos( $jn_bundle, '.jn-hero-portrait__frame' ), 'The inlined bundle includes the hero styles' );
$jn_functions = jn_thh_read( $jn_theme . '/functions.php' );
jn_t_assert(
	false !== strpos( $jn_functions, "\$css_dir . '/hero-home.css'," )
	&& false !== strpos( $jn_functions, "'jn-hero-home',\n\t\tJANNIE_URI . '/assets/css/hero-home.css'" )
	&& false !== strpos( $jn_functions, "'jannie-editor-hero-home',\n\t\tJANNIE_URI . '/assets/css/hero-home.css'" )
	&& false !== strpos( jn_thh_read( $jn_theme . '/inc/setup.php' ), "'assets/css/hero-home.css'" ),
	'Bundle, fallback enqueue and both editor loaders include hero-home.css'
);
jn_t_assert( (bool) preg_match( '/@media\s*\(prefers-reduced-motion:\s*reduce\)\s*\{[^@]*animation:\s*none/s', $jn_css ), 'Infinite animations stop under prefers-reduced-motion' );
jn_t_assert( false !== strpos( $jn_css, '.jn-motion-lite' ), 'Low-end devices (.jn-motion-lite) skip the infinite animations' );
// Only the visitor's own swap (`:checked`) may hide the LCP portrait.
preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $jn_css, $jn_rules, PREG_SET_ORDER );
$jn_hidden_lcp = array_filter(
	$jn_rules,
	static fn( array $r ): bool => ( false !== strpos( $r[1], '__img--primary' ) && false === strpos( $r[1], ':checked' ) && (bool) preg_match( '/opacity:\s*0\b|visibility:\s*hidden/', $r[2] ) )
		|| ( false !== strpos( $r[1], '.jn-hero-title' ) && false !== strpos( $r[2], 'animation' ) )
);
jn_t_assert( array() === $jn_hidden_lcp, 'The LCP image and the H1 are never hidden or animated in', implode( ' | ', array_map( 'trim', array_column( $jn_hidden_lcp, 1 ) ) ) );
jn_t_assert( ! preg_match( '/#[0-9a-f]{3,8}\b/i', (string) preg_replace( '/\.jn-section\.jn-hero-home\s*\{[^}]*\}/', '', $jn_css, 1 ) ), 'Raw hex colours only appear as the local tokens on .jn-hero-home' );

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Stored content' );

$jn_home     = get_post( (int) get_option( 'page_on_front' ) );
$jn_coaching = get_page_by_path( 'coaching' );
$jn_home_c   = $jn_home instanceof WP_Post ? $jn_home->post_content : '';
jn_t_assert( 1 === substr_count( $jn_home_c, '<!-- wp:jn/hero-portrait' ) && false !== strpos( $jn_home_c, 'jn-hero-home' ), 'Home uses the new hero' );
jn_t_assert( false === strpos( $jn_home_c, 'jn-hero-media-card' ) && false === strpos( $jn_home_c, '98%' ), 'Home no longer has the old media card or the 98% metric' );
jn_t_assert( 1 === preg_match_all( '/<h1\b/', $jn_home_c ), 'Home has exactly one H1' );
jn_t_assert( $jn_coaching instanceof WP_Post && false !== strpos( $jn_coaching->post_content, 'jn-hero-media-card' ) && false === strpos( $jn_coaching->post_content, 'jn-hero-home' ), 'Coaching keeps the editorial hero' );

$jn_build3 = jn_thh_read( __DIR__ . '/build-step-3.php' );
jn_t_assert( false !== strpos( $jn_build3, "jn_render_pattern_file( 'hero-home.php' )" ) && false === strpos( $jn_build3, '$home_hero_markup = str_replace(' ), 'build-step-3.php seeds Home from the new pattern' );

// ---------------------------------------------------------------------------------------------
jn_t_section( 'Live front page' );

$jn_base    = gethostbyname( 'wordpress' ) !== 'wordpress' ? 'http://wordpress' : 'http://localhost:8080';
$jn_res     = wp_remote_get( $jn_base . '/', array( 'timeout' => 15, 'redirection' => 0, 'headers' => array( 'Host' => 'localhost:8080' ) ) );
$jn_live    = is_wp_error( $jn_res ) ? '' : wp_remote_retrieve_body( $jn_res );
$jn_sizes   = class_exists( 'JN_Hero_Portrait' ) ? JN_Hero_Portrait::sizes() : '';
$jn_live_p  = jn_thh_imgs( $jn_live, 'jn-hero-portrait__img--primary' )[0] ?? '';
$jn_live_s  = jn_thh_imgs( $jn_live, 'jn-hero-portrait__img--alt' )[0] ?? '';
$jn_preload = preg_match( '/<link rel="preload" as="image"[^>]*>/', $jn_live, $jn_m ) ? $jn_m[0] : '';
jn_t_assert( 200 === wp_remote_retrieve_response_code( $jn_res ) && '' !== $jn_live, '/ responds 200', is_wp_error( $jn_res ) ? $jn_res->get_error_message() : '' );
jn_t_assert( 'eager' === jn_thh_attr( $jn_live_p, 'loading' ) && 'high' === jn_thh_attr( $jn_live_p, 'fetchpriority' ), 'Live primary image stays eager + high priority after the content filters', $jn_live_p );
jn_t_assert( 'lazy' === jn_thh_attr( $jn_live_s, 'loading' ) && null === jn_thh_attr( $jn_live_s, 'fetchpriority' ), 'Live secondary image stays lazy' );
jn_t_assert( 1 === preg_match_all( '/<img\b[^>]*fetchpriority="high"/', $jn_live ), 'Exactly one <img> is high priority' );
jn_t_assert( '' !== $jn_sizes && $jn_sizes === jn_thh_attr( $jn_live_p, 'sizes' ), 'Live primary sizes match the new layout', (string) jn_thh_attr( $jn_live_p, 'sizes' ) );
jn_t_assert( '' !== $jn_preload && false !== strpos( $jn_preload, 'imagesizes="' . $jn_sizes . '"' ), 'LCP preload imagesizes matches the <img> sizes (no double download)', $jn_preload );
jn_t_assert( 1 === preg_match_all( '/<h1\b/', $jn_live ), 'Live page has exactly one H1' );

jn_t_finish();
