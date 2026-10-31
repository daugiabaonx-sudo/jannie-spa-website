<?php
/**
 * Server-side render template for the `jn/marquee` block (ADR-025).
 *
 * The phrases are decorative (as in the owner's mockup), so the moving track is `aria-hidden` and holds the
 * list twice: the CSS loop slides it by half its width and starts over without a seam. Text that keeps moving
 * for more than five seconds must be pausable (WCAG 2.2.2), so the pause button sits beside the track, outside
 * the hidden region; `view.js` turns it into a page-wide switch for every decorative loop.
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

$jn_marquee_items = array_values( array_filter( array_map( 'trim', array_map( 'strval', (array) ( $attributes['items'] ?? array() ) ) ) ) );
if ( array() === $jn_marquee_items ) {
	return;
}

$jn_marquee_set = '';
foreach ( $jn_marquee_items as $jn_marquee_item ) {
	$jn_marquee_set .= '<span class="jn-marquee__item">' . esc_html( $jn_marquee_item ) . '</span><span class="jn-marquee__sep">✦</span>';
}

// Material Symbols Outlined `pause` / `play_arrow` (Apache-2.0), decorative.
$jn_marquee_icons = '<svg class="jn-marquee__icon jn-marquee__icon--pause" viewBox="0 -960 960 960" width="18" height="18" aria-hidden="true" focusable="false"><path d="M560-200v-560h160v560H560Zm-320 0v-560h160v560H240Z"/></svg>'
	. '<svg class="jn-marquee__icon jn-marquee__icon--play" viewBox="0 -960 960 960" width="18" height="18" aria-hidden="true" focusable="false"><path d="M320-200v-560l440 280-440 280Z"/></svg>';

printf(
	'<div %1$s><div class="jn-marquee__viewport" aria-hidden="true"><div class="jn-marquee__track" lang="%2$s">%3$s%3$s</div></div><button type="button" class="jn-marquee__toggle" aria-pressed="false">%4$s<span class="screen-reader-text">%5$s</span></button></div>',
	get_block_wrapper_attributes( array( 'class' => 'jn-marquee' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes().
	esc_attr( (string) ( $attributes['lang'] ?? 'en' ) ),
	$jn_marquee_set, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- items escaped above.
	$jn_marquee_icons, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
	esc_html__( 'Tạm dừng chuyển động trang trí', 'jn-core' )
);
