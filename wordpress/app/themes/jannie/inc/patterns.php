<?php
/**
 * Block Pattern Category Registration & Pattern Helpers for the Jannie child theme.
 *
 * Registers the "Jannie Nguyen" (`jannie-nguyen`) block pattern category and
 * ensures theme pattern cache stays fresh during development.
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the "Jannie Nguyen" pattern category for both theme patterns and synced patterns.
 *
 * @return void
	 */
function jannie_register_pattern_categories() {
	register_block_pattern_category(
		'jannie-nguyen',
		array(
			'label'       => 'Jannie Nguyen',
			'description' => 'Các mẫu phân đoạn chuẩn thiết kế của Jannie Nguyen Academy & Mentorship.',
		)
	);
}
add_action( 'init', 'jannie_register_pattern_categories', 5 );

/**
 * Clear cached theme pattern file headers inside wp-admin so newly added/edited
 * patterns in `patterns/*.php` are always recognized immediately in the editor,
 * without flushing the pattern cache on front-end page loads.
 *
 * @return void
 */
function jannie_refresh_theme_pattern_cache() {
	if ( ! is_admin() ) {
		return;
	}

	$theme = wp_get_theme();
	if ( $theme && method_exists( $theme, 'delete_pattern_cache' ) ) {
		$theme->delete_pattern_cache();
	}
}
add_action( 'init', 'jannie_refresh_theme_pattern_cache', 4 );
add_filter( 'should_load_remote_block_patterns', '__return_false' );

/**
 * Return Media Library attachment info for one of the 5 Step 3 placeholder images.
 *
 * @param string $key     Placeholder key (`hero_portrait`, `expert_consultation`, etc.).
 * @param string $default Fallback filename.
 * @return array<string, mixed>
 */
function jannie_get_placeholder_media( $key, $default = '' ) {
	$map = get_option( 'jn_step3_media_map', array() );
	if ( is_array( $map ) && isset( $map[ $key ] ) && is_array( $map[ $key ] ) ) {
		return $map[ $key ];
	}

	$uploads = wp_upload_dir();
	return array(
		'id'     => 0,
		'url'    => trailingslashit( $uploads['baseurl'] ) . '2026/10/' . $default,
		'alt'    => '',
		'width'  => 800,
		'height' => 1000,
	);
}

/**
 * Render the `jannie/error-404` pattern through `do_blocks()`.
 *
 * @return string
 */
function jannie_render_404_pattern_markup() {
	$pattern_file = JANNIE_PATH . '/patterns/error-404.php';
	if ( ! file_exists( $pattern_file ) ) {
		return '';
	}

	ob_start();
	include $pattern_file;
	$raw_blocks = (string) ob_get_clean();

	return do_blocks( $raw_blocks );
}
add_filter( 'blocksy:404:custom-output', 'jannie_render_404_pattern_markup', 20 );

/**
 * Localize the 404 document title to Vietnamese so no English "Page not found" string appears.
 *
 * @param array<string, string> $parts Document title parts.
 * @return array<string, string>
 */
function jannie_filter_404_document_title( $parts ) {
	if ( is_404() ) {
		$parts['title'] = 'Không tìm thấy trang';
	}
	return $parts;
}
add_filter( 'document_title_parts', 'jannie_filter_404_document_title', 20 );
