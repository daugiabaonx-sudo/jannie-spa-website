<?php
/**
 * Server-side render template for the `jn/product-stage` block (ADR-025).
 *
 * One decorative picture (`role="img"` + label, so its parts are presentational): three orbits, a pedestal and
 * three bottles drawn in CSS after the real packaging (white body, beige label band — D4). The first two
 * bottles are pump bottles, the last a jar, as in the owner's mockup. `view.js` only moves the spotlight and
 * adds a small parallax; without it the picture is complete.
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

$jn_stage_bottles = array_slice( array_map( 'strval', (array) ( $attributes['bottles'] ?? array() ) ), 0, 3 );
$jn_stage_bottles = array_pad( $jn_stage_bottles, 3, '' );
$jn_stage_brand   = (string) ( $attributes['brand'] ?? '' );
$jn_stage_tag     = (string) ( $attributes['tag'] ?? '' );

$jn_stage_items = '';
foreach ( $jn_stage_bottles as $jn_stage_index => $jn_stage_name ) {
	$jn_stage_items .= sprintf(
		'<span class="jn-bottle jn-bottle--%1$s"><span class="jn-bottle__cap"></span><span class="jn-bottle__body"><span class="jn-bottle__label"><strong>%2$s</strong><i></i><span>%3$s</span><small>%4$s</small></span></span></span>',
		2 === $jn_stage_index ? 'jar' : 'pump',
		esc_html( $jn_stage_brand ),
		esc_html( $jn_stage_name ),
		esc_html( $jn_stage_tag )
	);
}

printf(
	'<div %1$s role="img" aria-label="%2$s"><span class="jn-product-stage__spotlight"></span><span class="jn-product-stage__orbit jn-product-stage__orbit--outer"></span><span class="jn-product-stage__orbit"></span><span class="jn-product-stage__orbit jn-product-stage__orbit--inner"></span><span class="jn-product-stage__note" lang="en">%3$s</span><span class="jn-product-stage__pedestal"></span><span class="jn-product-stage__products">%4$s</span></div>',
	get_block_wrapper_attributes( array( 'class' => 'jn-product-stage' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes().
	esc_attr( (string) ( $attributes['label'] ?? '' ) ),
	esc_html( (string) ( $attributes['note'] ?? '' ) ),
	$jn_stage_items // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every piece escaped above.
);
