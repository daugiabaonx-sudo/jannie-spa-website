<?php
/**
 * Block Registration Manager & Core Block Accessibility/Performance Filters for JN Core.
 *
 * Registers `jn/icon`, `jn/rating`, `jn/booking-form`, `jn/hero-portrait`, `jn/video`, `jn/product-stage`,
 * `jn/marquee` and `jn/product-actions`, and enforces:
 * - `loading="eager"` + `fetchpriority="high"` (never lazy-loaded) on `.jn-hero-lcp-image`
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
		add_filter( 'render_block_core/image', array( __CLASS__, 'filter_hero_lcp_image_block' ), 99, 2 );
		add_filter( 'render_block_core/button', array( __CLASS__, 'filter_button_inline_icon' ), 20, 2 );
	}

	/**
	 * Register custom Gutenberg blocks (`jn/icon`, `jn/rating`, `jn/booking-form`, `jn/hero-portrait`, `jn/video`).
	 *
	 * @return void
	 */
	public static function register_blocks() {
		$editor_deps = array(
			'wp-blocks',
			'wp-element',
			'wp-block-editor',
			'wp-components',
			'wp-server-side-render',
		);

		wp_register_script(
			'jn-block-icon-editor',
			JN_CORE_URL . 'blocks/icon/editor.js',
			$editor_deps,
			JN_CORE_VERSION,
			true
		);

		wp_register_script(
			'jn-block-rating-editor',
			JN_CORE_URL . 'blocks/rating/editor.js',
			$editor_deps,
			JN_CORE_VERSION,
			true
		);

		wp_register_script(
			'jn-block-booking-form-editor',
			JN_CORE_URL . 'blocks/booking-form/editor.js',
			$editor_deps,
			JN_CORE_VERSION,
			true
		);

		wp_register_script(
			'jn-block-hero-portrait-editor',
			JN_CORE_URL . 'blocks/hero-portrait/editor.js',
			$editor_deps,
			JN_CORE_VERSION,
			true
		);

		wp_register_script(
			'jn-block-video-editor',
			JN_CORE_URL . 'blocks/video/editor.js',
			$editor_deps,
			JN_CORE_VERSION,
			true
		);

		wp_register_script(
			'jn-block-video-view',
			JN_CORE_URL . 'blocks/video/view.js',
			array(),
			JN_CORE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_register_script(
			'jn-block-booking-form-view',
			JN_CORE_URL . 'blocks/booking-form/view.js',
			array(),
			JN_CORE_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		// /my-favorite-product/ (ADR-025): editor + deferred view script per block.
		foreach ( array( 'product-stage', 'marquee', 'product-actions' ) as $slug ) {
			wp_register_script(
				"jn-block-{$slug}-editor",
				JN_CORE_URL . "blocks/{$slug}/editor.js",
				$editor_deps,
				JN_CORE_VERSION,
				true
			);

			wp_register_script(
				"jn-block-{$slug}-view",
				JN_CORE_URL . "blocks/{$slug}/view.js",
				array(),
				JN_CORE_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		register_block_type( JN_CORE_PATH . 'blocks/icon' );
		register_block_type( JN_CORE_PATH . 'blocks/rating' );
		register_block_type( JN_CORE_PATH . 'blocks/booking-form' );
		register_block_type( JN_CORE_PATH . 'blocks/hero-portrait' );
		register_block_type( JN_CORE_PATH . 'blocks/video' );
		register_block_type( JN_CORE_PATH . 'blocks/product-stage' );
		register_block_type( JN_CORE_PATH . 'blocks/marquee' );
		register_block_type( JN_CORE_PATH . 'blocks/product-actions' );
	}

	/**
	 * Guarantee the Hero LCP `<img>` block (`jn-hero-lcp-image`) is never lazy-loaded
	 * and always carries `loading="eager"` and `fetchpriority="high"`.
	 *
	 * @param string              $block_content Rendered block HTML.
	 * @param array<string,mixed> $block         Parsed block array.
	 * @return string
	 */
	public static function filter_hero_lcp_image_block( $block_content, $block ) {
		$class_name = $block['attrs']['className'] ?? '';
		if ( false === strpos( $class_name, 'jn-hero-lcp-image' ) && false === strpos( $block_content, 'jn-hero-lcp-image' ) ) {
			return $block_content;
		}

		$processor = new WP_HTML_Tag_Processor( $block_content );
		if ( $processor->next_tag( 'img' ) ) {
			$processor->set_attribute( 'loading', 'eager' );
			$processor->set_attribute( 'fetchpriority', 'high' );
			$processor->set_attribute( 'decoding', 'async' );
			return $processor->get_updated_html();
		}

		return $block_content;
	}

	/**
	 * Append the inline SVG icon inside `core/button` links that carry one of the
	 * `has-icon-*` helper classes (`has-icon-north-east`, `has-icon-south`, `has-icon-arrow-forward`).
	 *
	 * @param string              $block_content Rendered block HTML.
	 * @param array<string,mixed> $block         Parsed block array.
	 * @return string
	 */
	public static function filter_button_inline_icon( $block_content, $block ) {
		$class_name = $block['attrs']['className'] ?? '';
		if ( '' === $class_name ) {
			return $block_content;
		}

		$icon_map = array(
			'has-icon-north-east'    => array(
				'name' => 'north_east',
				'size' => 18,
			),
			'has-icon-south'         => array(
				'name' => 'south',
				'size' => 16,
			),
			'has-icon-arrow-forward' => array(
				'name' => 'arrow_forward',
				'size' => 16,
			),
		);

		foreach ( $icon_map as $css_class => $icon_cfg ) {
			if ( false !== strpos( $class_name, $css_class ) ) {
				$svg = JN_Icon_Sprite::render(
					array(
						'name'  => $icon_cfg['name'],
						'size'  => $icon_cfg['size'],
						'class' => 'jn-btn-icon',
					)
				);

				return preg_replace(
					'/<\/a>\s*<\/div>\s*$/',
					$svg . '</a></div>',
					$block_content,
					1
				);
			}
		}

		return $block_content;
	}
}
