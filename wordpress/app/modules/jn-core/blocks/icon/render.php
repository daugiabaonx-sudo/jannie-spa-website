<?php
/**
 * Server-side render callback template for the `jn/icon` block.
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

$jn_icon_name  = isset( $attributes['name'] ) ? (string) $attributes['name'] : 'check_circle';
$jn_icon_size  = isset( $attributes['size'] ) ? absint( $attributes['size'] ) : 20;
$jn_icon_label = isset( $attributes['label'] ) ? (string) $attributes['label'] : '';

$jn_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'jn-icon-wrap',
	)
);

$jn_svg_markup = JN_Icon_Sprite::render(
	array(
		'name'  => $jn_icon_name,
		'size'  => $jn_icon_size,
		'label' => $jn_icon_label,
	)
);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $jn_wrapper_attributes is escaped by get_block_wrapper_attributes() and $jn_svg_markup is sanitized inline SVG from JN_Icon_Sprite::render().
printf( '<span %1$s>%2$s</span>', $jn_wrapper_attributes, $jn_svg_markup );
