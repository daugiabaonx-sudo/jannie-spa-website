<?php
/**
 * JN Step 12 — Site-wide motion layer (motion-ui): scroll reveal, header scroll state,
 * nav indicator, button icon nudge and FAQ disclosure.
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/test-step-12-motion.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/jn-test-kit.php';
jn_require_non_production( 'test-step-12-motion.php' );

echo "=================================================================\n";
echo "JN STEP 12 — MOTION LAYER\n";
echo "=================================================================\n";

$jn_css_dir = get_stylesheet_directory() . '/assets/css';

/**
 * Read a theme stylesheet with comments stripped.
 *
 * @param string $path Absolute path.
 * @return string
 */
function jn_t12_read_css( string $path ): string {
	$css = is_file( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return (string) preg_replace( '#/\*.*?\*/#s', '', $css );
}

/**
 * List every property named in `transition`/`transition-property` declarations and `@keyframes` bodies.
 *
 * @param string $css Stylesheet without comments.
 * @return string[]
 */
function jn_t12_animated_properties( string $css ): array {
	$props = array();
	preg_match_all( '/transition(?:-property)?\s*:\s*([^;}]+)/', $css, $transitions );
	foreach ( $transitions[1] as $value ) {
		foreach ( explode( ',', $value ) as $part ) {
			$props[] = strtok( trim( $part ), " \t\n" );
		}
	}
	preg_match_all( '/@keyframes[^{]+\{((?:[^{}]*\{[^}]*\})*)\s*\}/', $css, $keyframes );
	foreach ( $keyframes[1] as $body ) {
		preg_match_all( '/([a-z-]+)\s*:/', $body, $declared );
		$props = array_merge( $props, $declared[1] );
	}
	return array_values( array_unique( array_filter( $props ) ) );
}

// -----------------------------------------------------------------------------
// Tokens — one motion scale, zeroed by prefers-reduced-motion
// -----------------------------------------------------------------------------
jn_t_section( 'Motion tokens' );
$jn_tokens = jn_t12_read_css( $jn_css_dir . '/tokens.css' );
foreach ( array( '--jn-motion-fast', '--jn-motion-normal', '--jn-motion-slow', '--jn-ease-smooth', '--jn-motion-distance-sm', '--jn-motion-distance-lg', '--jn-motion-stagger' ) as $jn_token ) {
	jn_t_assert( false !== strpos( $jn_tokens, $jn_token . ':' ), "tokens.css defines {$jn_token}" );
}
preg_match( '/@media\s*\(prefers-reduced-motion:\s*reduce\)\s*\{(.*?)\n\}/s', $jn_tokens, $jn_reduce_block );
$jn_reduce = $jn_reduce_block[1] ?? '';
jn_t_assert( (bool) preg_match( '/--jn-motion-slow:\s*0ms/', $jn_reduce ) && (bool) preg_match( '/--jn-motion-distance-lg:\s*0(px)?;/', $jn_reduce ), 'Reduced motion zeroes the motion durations and distances' );

// -----------------------------------------------------------------------------
// Compositor-only motion — never animate layout properties
// -----------------------------------------------------------------------------
jn_t_section( 'Compositor-only properties' );
$jn_motion_css = jn_t12_read_css( $jn_css_dir . '/motion.css' );
jn_t_assert( '' !== $jn_motion_css, 'assets/css/motion.css exists' );

$jn_layout_props = array( 'width', 'height', 'top', 'left', 'right', 'bottom', 'margin', 'padding', 'all' );
$jn_motion_props = jn_t12_animated_properties( $jn_motion_css );
jn_t_assert( array() !== $jn_motion_props && array() === array_intersect( $jn_motion_props, $jn_layout_props ), 'motion.css only transitions/animates non-layout properties', implode( ', ', $jn_motion_props ) );

$jn_base = jn_t12_read_css( $jn_css_dir . '/base.css' );
preg_match( '/a\.ct-menu-link::after\s*\{([^}]*)\}/', $jn_base, $jn_nav_after );
$jn_nav_rule = $jn_nav_after[1] ?? '';
jn_t_assert( false !== strpos( $jn_nav_rule, 'scaleX(0)' ) && (bool) preg_match( '/transition:\s*transform/', $jn_nav_rule ), 'The header nav indicator grows with transform: scaleX (not Blocksy\'s width transition)', $jn_nav_rule );
jn_t_assert( (bool) preg_match( '/position:\s*absolute/', $jn_nav_rule ) && (bool) preg_match( '/content:\s*""/', $jn_nav_rule ), 'The nav indicator is self-contained (Blocksy main.min.css is deferred past first paint)', $jn_nav_rule );
jn_t_assert( (bool) preg_match( '/@media\s*\(forced-colors:\s*active\)\s*\{[^{]*a\.ct-menu-link::after\s*\{[^}]*Highlight/', $jn_base ), 'The nav indicator stays visible in forced-colors mode' );

$jn_blocks = jn_t12_read_css( $jn_css_dir . '/block-styles.css' );
jn_t_assert( (bool) preg_match( '/\.jn-faq-item\s*>\s*summary::after\s*\{[^}]*transition:\s*transform/', $jn_blocks ), 'The FAQ indicator rotates with a transform transition' );
jn_t_assert( (bool) preg_match( '/\.jn-faq-item\s*>\s*summary::-webkit-details-marker\s*\{[^}]*display:\s*none/', $jn_blocks ), 'The default disclosure triangle is replaced by the brand indicator' );

// -----------------------------------------------------------------------------
// Scroll reveal is progressive enhancement
// -----------------------------------------------------------------------------
jn_t_section( 'Scroll reveal CSS' );
jn_t_assert( (bool) preg_match( '/\.jn-reveal\s*\{[^}]*opacity:\s*0/', $jn_motion_css ), 'Hidden state lives on .jn-reveal, a class only the script adds' );
jn_t_assert( (bool) preg_match( '/\.jn-reveal\.is-revealed\s*\{[^}]*opacity:\s*1[^}]*transform:\s*none/', $jn_motion_css ), 'Revealed state restores opacity and transform' );
jn_t_assert( (bool) preg_match( '/\.jn-motion-lite\s+\.jn-reveal\s*\{[^}]*transition-delay:\s*0s/', $jn_motion_css ), 'The low-end profile drops the stagger delay' );
jn_t_assert( (bool) preg_match( '/@media\s+print\s*\{[^{]*\.jn-reveal\s*\{[^}]*opacity:\s*1/', $jn_motion_css ), 'Printing shows content that has not been revealed yet' );
jn_t_assert( (bool) preg_match( '/#header\.ct-header\.is-scrolled::after\s*\{[^}]*opacity:\s*1/', $jn_motion_css ), 'The header elevates (pseudo-element opacity) once the page scrolls' );

// -----------------------------------------------------------------------------
// Bundle — motion.css ships in the single inlined stylesheet
// -----------------------------------------------------------------------------
jn_t_section( 'CSS bundle' );
jannie_ensure_front_css_bundle();
clearstatcache();
$jn_bundle = (string) file_get_contents( $jn_css_dir . '/jannie-bundle.min.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
jn_t_assert( false !== strpos( $jn_bundle, '.jn-reveal.is-revealed{' ) && false !== strpos( $jn_bundle, '@keyframes jn-fade-down{' ), 'The front-end bundle includes motion.css' );
$jn_faq_pos    = strpos( $jn_bundle, '.jn-faq-item>summary::after' );
$jn_reveal_pos = strpos( $jn_bundle, '.jn-reveal{' );
jn_t_assert( false !== $jn_faq_pos && false !== $jn_reveal_pos && $jn_faq_pos < $jn_reveal_pos, 'motion.css is bundled after block-styles.css (it layers on top)' );

// -----------------------------------------------------------------------------
// Script — wiring, guards and LCP safety
// -----------------------------------------------------------------------------
jn_t_section( 'Motion script' );
jn_t_assert( function_exists( 'jannie_print_motion_script' ) && false !== has_action( 'wp_footer', 'jannie_print_motion_script' ), 'The motion script is printed in the footer' );

ob_start();
if ( function_exists( 'jannie_print_motion_script' ) ) {
	jannie_print_motion_script();
}
$jn_script = (string) ob_get_clean();

jn_t_assert( false !== strpos( $jn_script, '<script id="jn-motion">' ), 'The script carries a stable id' );
jn_t_assert( false !== strpos( $jn_script, "matchMedia('(prefers-reduced-motion: reduce)')" ), 'Reduced motion skips the scroll reveal entirely' );
jn_t_assert( false !== strpos( $jn_script, "'IntersectionObserver' in window" ), 'Browsers without IntersectionObserver keep static content' );
jn_t_assert( false !== strpos( $jn_script, 'getBoundingClientRect' ) && false !== strpos( $jn_script, 'innerHeight' ), 'Only content below the first viewport is hidden (no flash, no LCP delay)' );
jn_t_assert( false !== strpos( $jn_script, 'deviceMemory' ) && false !== strpos( $jn_script, 'jn-motion-lite' ), 'Low-end devices get the shorter motion profile' );
jn_t_assert( false !== strpos( $jn_script, 'transitionend' ), 'Reveal classes are removed once done so card hover transforms keep working' );
jn_t_assert( false === strpos( $jn_script, 'jn-hero' ) && false === strpos( $jn_script, 'jn-page-intro' ), 'The hero and page intro (LCP) are never reveal targets' );
jn_t_assert( false === strpos( $jn_script, "addEventListener('scroll'" ), 'Header scroll state uses an IntersectionObserver sentinel, not a scroll handler' );
jn_t_assert( false !== strpos( $jn_script, 'entries[entries.length - 1].isIntersecting' ), 'Header state follows the latest queued sentinel entry, not the oldest' );
jn_t_assert( false !== strpos( $jn_script, 'tops[j] === tops[i]' ), 'Stagger counts only siblings on the same row (stacked mobile cards are not delayed)' );

jn_t_finish();
