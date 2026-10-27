<?php
/**
 * Block Registration Manager for JN Core.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Blocks
 */
class JN_Blocks {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
	}

	/**
	 * Register custom Gutenberg blocks.
	 *
	 * @return void
	 */
	public static function register_blocks() {
		wp_register_script(
			'jn-block-icon-editor',
			JN_CORE_URL . 'blocks/icon/editor.js',
			array(
				'wp-blocks',
				'wp-element',
				'wp-block-editor',
				'wp-components',
				'wp-server-side-render',
			),
			JN_CORE_VERSION,
			true
		);

		register_block_type( JN_CORE_PATH . 'blocks/icon' );
	}
}
