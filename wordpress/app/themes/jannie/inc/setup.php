<?php
/**
 * Jannie Setup: Self-hosted font preloading, Google Fonts blocking,
 * editor styles, and Blocksy landing-page defaults.
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turn off every Google Fonts request from Blocksy.
 */
add_filter( 'blocksy:typography:google:use-remote', '__return_false', 99 );
add_filter( 'blocksy-typography-google-fonts-source', '__return_empty_array', 99 );

/**
 * Remove any Google Fonts stylesheet or resource hint if registered.
 *
 * @return void
 */
function jannie_dequeue_remote_fonts() {
	wp_dequeue_style( 'blocksy-fonts-font-source-google' );
	wp_deregister_style( 'blocksy-fonts-font-source-google' );
}
add_action( 'wp_enqueue_scripts', 'jannie_dequeue_remote_fonts', 99 );
add_action( 'enqueue_block_editor_assets', 'jannie_dequeue_remote_fonts', 99 );

/**
 * Strip fonts.googleapis.com and fonts.gstatic.com from resource hints.
 *
 * @param array<int, string|array<string, string>> $urls          URLs to print for resource hints.
 * @param string                                   $relation_type Relation type ('dns-prefetch', 'preconnect', etc.).
 * @return array<int, string|array<string, string>>
 */
function jannie_filter_resource_hints( $urls, $relation_type ) {
	if ( ! in_array( $relation_type, array( 'dns-prefetch', 'preconnect' ), true ) ) {
		return $urls;
	}

	return array_values(
		array_filter(
			$urls,
			function ( $url ) {
				$href = is_array( $url ) && isset( $url['href'] ) ? $url['href'] : (string) $url;
				return false === strpos( $href, 'fonts.googleapis.com' ) && false === strpos( $href, 'fonts.gstatic.com' );
			}
		)
	);
}
add_filter( 'wp_resource_hints', 'jannie_filter_resource_hints', 99, 2 );

/**
 * Preload the 2 primary WOFF2 fonts used above the fold
 * (`playfair-display-vi-normal.woff2` and `geist-vi-normal.woff2`, <= 2 preloaded fonts per Task 4),
 * using the aliased `/static/ui/` path so the preload URL matches `@font-face` 1:1.
 * The 3rd accent font (`playfair-display-vi-italic.woff2`) renders immediately with the
 * metric-matched `'Playfair Display Italic Fallback'` at FCP/LCP and swaps in right after load.
 *
 * @return void
 */
function jannie_preload_above_fold_fonts() {
	$fonts = array(
		JANNIE_URI . '/assets/fonts/playfair-display-vi-normal.woff2',
		JANNIE_URI . '/assets/fonts/geist-vi-normal.woff2',
	);

	foreach ( $fonts as $font_url ) {
		if ( class_exists( 'JN_Hardening_HTML_Headers' ) ) {
			$font_url = JN_Hardening_HTML_Headers::alias_asset_url( $font_url );
		}
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin="anonymous" />' . "\n",
			esc_url( $font_url )
		);
	}

	$italic_url = JANNIE_URI . '/assets/fonts/playfair-display-vi-italic.woff2';
	if ( class_exists( 'JN_Hardening_HTML_Headers' ) ) {
		$italic_url = JN_Hardening_HTML_Headers::alias_asset_url( $italic_url );
	}
	printf(
		"<noscript><style>@font-face{font-family:'Playfair Display';font-style:italic;font-weight:400 900;font-display:swap;src:url('%s') format('woff2');}</style></noscript>\n",
		esc_url( $italic_url )
	);
}
add_action( 'wp_head', 'jannie_preload_above_fold_fonts', 9 );

/**
 * Register custom responsive image sizes for Hero (4:5), Expert (3:4), and Testimonial Avatars (1:1),
 * plus register child theme stylesheets for the Block Editor.
 *
 * @return void
 */
function jannie_setup_editor_styles() {
	add_editor_style(
		array(
			'assets/css/tokens.css',
			'assets/css/base.css',
			'assets/css/block-styles.css',
		)
	);

	// Hero portrait 4:5 responsive steps (400x500, 652x815, 800x1000 full).
	add_image_size( 'jn-hero-400', 400, 500, true );
	add_image_size( 'jn-hero-652', 652, 815, true );
	add_image_size( 'jn-hero-800', 800, 1000, true );

	// Expert consultation 3:4 responsive steps (420x560, 640x853, 750x1000 full).
	add_image_size( 'jn-expert-420', 420, 560, true );
	add_image_size( 'jn-expert-640', 640, 853, true );
	add_image_size( 'jn-expert-750', 750, 1000, true );

	// Testimonial avatars 1:1 (96x96 for 2x retina on 48x48 CSS box, 160x160 full).
	add_image_size( 'jn-avatar-96', 96, 96, true );
	add_image_size( 'jn-avatar-160', 160, 160, true );
}
add_action( 'after_setup_theme', 'jannie_setup_editor_styles', 20 );

/**
 * Enforce landing-page defaults in Blocksy's page options:
 * - No page title (`single_page_hero_enabled` = 'no')
 * - No sidebar / normal container (`single_page_structure` = 'type-4')
 * - Full width / unboxed (`single_page_content_style` = 'wide')
 * - No content area vertical spacing (`single_page_content_area_spacing` = 'none', `contentSpacing` = 'none')
 * - Container max width (`maxSiteWidth` = 1248, with 56px side padding = 1360px bounding box)
 */
add_filter(
	'theme_mod_single_page_hero_enabled',
	function () {
		return 'no';
	}
);
add_filter(
	'theme_mod_single_page_structure',
	function () {
		return 'type-4';
	}
);
add_filter(
	'theme_mod_single_page_content_style',
	function () {
		return 'wide';
	}
);
add_filter(
	'theme_mod_single_page_content_area_spacing',
	function () {
		return 'none';
	}
);
add_filter(
	'theme_mod_contentSpacing',
	function () {
		return 'none';
	}
);
add_filter(
	'theme_mod_maxSiteWidth',
	function () {
		return 1248;
	}
);
