<?php
/**
 * Jannie child theme functions and definitions.
 *
 * Enqueues design system stylesheets and includes modular feature files only.
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'JANNIE_VERSION', '1.1.0' );
define( 'JANNIE_PATH', get_stylesheet_directory() );
define( 'JANNIE_URI', get_stylesheet_directory_uri() );

require_once JANNIE_PATH . '/inc/palette.php';
require_once JANNIE_PATH . '/inc/setup.php';
require_once JANNIE_PATH . '/inc/block-styles.php';
require_once JANNIE_PATH . '/inc/patterns.php';
require_once JANNIE_PATH . '/inc/layout-shell.php';
require_once JANNIE_PATH . '/inc/woocommerce.php';

/**
 * Minify a CSS string while preserving strings, URLs, and calc() whitespace.
 *
 * @param string $css Raw CSS content.
 * @return string Minified CSS.
 */
function jannie_minify_css_string( $css ) {
	// Strip block comments.
	$css = (string) preg_replace( '#/\*.*?\*/#s', '', $css );
	// Collapse whitespace runs to a single space.
	$css = (string) preg_replace( '/\s+/', ' ', $css );
	// Strip spaces around structural punctuation (excluding + and - for calc()).
	$css = (string) preg_replace( '/\s*([{};:,>~])\s*/', '$1', $css );
	$css = str_replace( ';}', '}', $css );
	return trim( $css );
}

/**
 * Ensure the combined & minified front-end stylesheet `assets/css/jannie-bundle.min.css`
 * is up to date with `tokens.css`, `base.css`, and `block-styles.css`.
 *
 * @return string Relative path to the bundle or fallback stylesheet.
 */
function jannie_ensure_front_css_bundle() {
	$css_dir     = JANNIE_PATH . '/assets/css';
	$bundle_file = $css_dir . '/jannie-bundle.min.css';
	$sources     = array(
		$css_dir . '/tokens.css',
		$css_dir . '/base.css',
		$css_dir . '/block-styles.css',
	);

	// Staleness is decided by source CONTENT (mtimes are unreliable after git checkout / rsync).
	$source_hashes = array();
	foreach ( $sources as $src_file ) {
		$source_hashes[] = is_file( $src_file ) ? (string) md5_file( $src_file ) : '';
	}
	$signature = '/*jn-bundle:' . md5( implode( '|', $source_hashes ) ) . '*/';

	$current_head = '';
	if ( is_file( $bundle_file ) && is_readable( $bundle_file ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$current_head = (string) file_get_contents( $bundle_file, false, null, 0, strlen( $signature ) );
	}

	if ( $current_head !== $signature ) {
		$combined = '';
		foreach ( $sources as $src_file ) {
			if ( is_file( $src_file ) && is_readable( $src_file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$combined .= "\n" . (string) file_get_contents( $src_file );
			}
		}

		$minified = jannie_minify_css_string( $combined );
		if ( '' !== $minified && is_writable( $css_dir ) ) {
			// Write to a private temp file, then rename (atomic on the same filesystem): a concurrent request
			// never reads, inlines and page-caches a half-written bundle.
			$tmp_file = $bundle_file . '.tmp' . wp_generate_password( 8, false );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			$written = file_put_contents( $tmp_file, $signature . $minified );
			if ( false === $written || ! rename( $tmp_file, $bundle_file ) ) {
				wp_delete_file( $tmp_file );
			}
		}
	}

	return is_file( $bundle_file ) ? '/assets/css/jannie-bundle.min.css' : '/assets/css/block-styles.css';
}

/**
 * Enqueue front-end design system stylesheets:
 * Inlines the minified Jannie child-theme CSS (`tokens.css` + `base.css` + `block-styles.css`)
 * onto `ct-main-styles` so `<head>` has only a single external stylesheet (`ct-main-styles`)
 * and zero chained stylesheet round-trips (Step 8 Task 3).
 *
 * @return void
 */
function jannie_enqueue_front_assets() {
	$bundle_rel  = jannie_ensure_front_css_bundle();
	$bundle_file = JANNIE_PATH . $bundle_rel;

	if ( '/assets/css/jannie-bundle.min.css' === $bundle_rel && is_file( $bundle_file ) && is_readable( $bundle_file ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$inline_css = (string) file_get_contents( $bundle_file );
		$fonts_base = JANNIE_URI . '/assets/fonts/';
		if ( class_exists( 'JN_Hardening_HTML_Headers' ) ) {
			$fonts_base = JN_Hardening_HTML_Headers::alias_asset_url( $fonts_base );
		}
		$inline_css = str_replace( "url('../fonts/", "url('" . esc_url_raw( $fonts_base ), $inline_css );
		// Defer the italic accent WOFF2 @font-face to post-paint (loaded in setup.php with metric-matched fallback at FCP).
		$inline_css = (string) preg_replace( '/@font-face\{font-family:\'Playfair Display\';font-style:italic;[^}]+\}/', '', $inline_css );

		wp_add_inline_style( 'ct-main-styles', $inline_css );

		wp_register_style( 'jn-tokens', false, array( 'ct-main-styles' ), JANNIE_VERSION );
		wp_enqueue_style( 'jn-tokens' );

		wp_register_style( 'jn-base', false, array( 'jn-tokens' ), JANNIE_VERSION );
		wp_enqueue_style( 'jn-base' );

		wp_register_style( 'jn-block-styles', false, array( 'jn-base' ), JANNIE_VERSION );
		wp_enqueue_style( 'jn-block-styles' );
		return;
	}

	wp_enqueue_style(
		'jn-tokens',
		JANNIE_URI . '/assets/css/tokens.css',
		array( 'ct-main-styles' ),
		JANNIE_VERSION
	);

	wp_enqueue_style(
		'jn-base',
		JANNIE_URI . '/assets/css/base.css',
		array( 'jn-tokens' ),
		JANNIE_VERSION
	);

	wp_enqueue_style(
		'jn-block-styles',
		JANNIE_URI . '/assets/css/block-styles.css',
		array( 'jn-base' ),
		JANNIE_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'jannie_enqueue_front_assets', 20 );

/**
 * Enqueue design system stylesheets inside the Block Editor so editor and front-end match.
 *
 * @return void
 */
function jannie_enqueue_editor_assets() {
	if ( ! is_admin() ) {
		return;
	}

	wp_enqueue_style(
		'jannie-editor-tokens',
		JANNIE_URI . '/assets/css/tokens.css',
		array(),
		JANNIE_VERSION
	);

	wp_enqueue_style(
		'jannie-editor-base',
		JANNIE_URI . '/assets/css/base.css',
		array( 'jannie-editor-tokens' ),
		JANNIE_VERSION
	);

	wp_enqueue_style(
		'jannie-editor-block-styles',
		JANNIE_URI . '/assets/css/block-styles.css',
		array( 'jannie-editor-base' ),
		JANNIE_VERSION
	);
}
add_action( 'enqueue_block_assets', 'jannie_enqueue_editor_assets', 20 );
