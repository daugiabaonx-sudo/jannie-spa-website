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
 * Preload at most the 2 self-hosted WOFF2 font files used above the fold
 * (Playfair Display Regular/Variable and Geist Variable).
 *
 * @return void
 */
function jannie_preload_above_fold_fonts() {
	$fonts = array(
		JANNIE_URI . '/assets/fonts/playfair-display-vi-normal.woff2',
		JANNIE_URI . '/assets/fonts/geist-vi-normal.woff2',
	);

	foreach ( $fonts as $font_url ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin="anonymous" />' . "\n",
			esc_url( $font_url )
		);
	}
}
add_action( 'wp_head', 'jannie_preload_above_fold_fonts', 1 );

/**
 * Register child theme stylesheets as block editor styles so the iframe editor
 * matches the front-end 1:1.
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
