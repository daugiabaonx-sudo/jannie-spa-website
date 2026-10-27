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

define( 'JANNIE_VERSION', '1.0.0' );
define( 'JANNIE_PATH', get_stylesheet_directory() );
define( 'JANNIE_URI', get_stylesheet_directory_uri() );

require_once JANNIE_PATH . '/inc/palette.php';
require_once JANNIE_PATH . '/inc/setup.php';
require_once JANNIE_PATH . '/inc/block-styles.php';
require_once JANNIE_PATH . '/inc/layout-shell.php';

/**
 * Enqueue front-end design system stylesheets.
 *
 * @return void
 */
function jannie_enqueue_front_assets() {
	wp_enqueue_style(
		'jannie-tokens',
		JANNIE_URI . '/assets/css/tokens.css',
		array( 'ct-main-styles' ),
		JANNIE_VERSION
	);

	wp_enqueue_style(
		'jannie-base',
		JANNIE_URI . '/assets/css/base.css',
		array( 'jannie-tokens' ),
		JANNIE_VERSION
	);

	wp_enqueue_style(
		'jannie-block-styles',
		JANNIE_URI . '/assets/css/block-styles.css',
		array( 'jannie-base' ),
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
