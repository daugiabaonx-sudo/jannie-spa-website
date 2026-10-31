<?php
/**
 * Server-side render template for the `jn/product-actions` block (ADR-025).
 *
 * Product mode (inside a WooCommerce Product Collection) follows the owner's mockup: a priced product gets
 * "Mua Ngay" (to the product page — D5, shipping is off) then the consult button; a product without a price
 * gets the consult button first and "Xem sản phẩm" second. Contact mode renders one general consult button.
 * Consult buttons carry the product name and price as plain data; `view.js` fills the shared dialog with them.
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

if ( 'contact' === ( $attributes['mode'] ?? 'product' ) ) {
	JN_Consult::request_dialog();
	printf(
		'<div class="jn-product-actions jn-product-actions--contact"><button type="button" class="jn-product-actions__cta" data-jn-consult>%1$s%2$s</button></div>',
		esc_html( (string) ( $attributes['contactLabel'] ?? '' ) ),
		JN_Icon_Sprite::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sprite markup is static.
			array(
				'name'  => 'north_east',
				'size'  => 20,
				'class' => 'jn-btn-icon',
			)
		)
	);
	return;
}

$jn_actions_id = (int) ( $block->context['postId'] ?? 0 );
if ( 0 === $jn_actions_id && wp_is_serving_rest_request() ) {
	// Editor preview (ServerSideRender passes the card's product as `post_id`).
	$jn_actions_id = (int) get_the_ID();
}
$jn_actions_product = $jn_actions_id > 0 && function_exists( 'wc_get_product' ) ? wc_get_product( $jn_actions_id ) : null;
if ( ! $jn_actions_product instanceof WC_Product ) {
	return;
}

JN_Consult::request_dialog();

$jn_actions_name  = $jn_actions_product->get_name();
$jn_actions_price = trim( str_replace( "\u{00a0}", ' ', html_entity_decode( wp_strip_all_tags( $jn_actions_product->get_price_html() ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
$jn_actions_url   = get_permalink( $jn_actions_product->get_id() );
$jn_actions_about = '<span class="screen-reader-text">: ' . esc_html( $jn_actions_name ) . '</span>';

$jn_actions_consult = static fn( string $variant ): string => sprintf(
	'<button type="button" class="jn-product-actions__btn jn-product-actions__btn--%1$s" data-jn-consult data-jn-product="%2$s" data-jn-price="%3$s">%4$s%5$s</button>',
	$variant,
	esc_attr( $jn_actions_name ),
	esc_attr( $jn_actions_price ),
	esc_html( JN_Consult::consult_label() ),
	$jn_actions_about
);
$jn_actions_link    = static fn( string $variant, string $label ): string => sprintf(
	'<a class="jn-product-actions__btn jn-product-actions__btn--%1$s" href="%2$s">%3$s%4$s</a>',
	$variant,
	esc_url( (string) $jn_actions_url ),
	esc_html( $label ),
	$jn_actions_about
);

$jn_actions_buttons = '' !== (string) $jn_actions_product->get_price()
	? $jn_actions_link( 'primary', (string) ( $attributes['buyLabel'] ?? '' ) ) . $jn_actions_consult( 'secondary' )
	: $jn_actions_consult( 'primary' ) . $jn_actions_link( 'secondary', (string) ( $attributes['viewLabel'] ?? '' ) );

echo '<div class="jn-product-actions jn-product-actions--product">' . $jn_actions_buttons . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every piece escaped above.
