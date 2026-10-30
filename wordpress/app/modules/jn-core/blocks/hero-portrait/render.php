<?php
/**
 * Server-side render callback template for the `jn/hero-portrait` block.
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Block inner content (empty).
 * @var WP_Block             $block      Block instance.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JN_Hero_Portrait::render() escapes every attribute and builds images with wp_get_attachment_image().
echo JN_Hero_Portrait::render( $attributes );
