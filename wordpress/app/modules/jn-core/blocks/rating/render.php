<?php
/**
 * Server-side render callback template for the `jn/rating` block.
 *
 * Reads `jn_rating` post meta when placed inside a Query Loop (`postId` context)
 * or falls back to the block's `rating` attribute (default 5).
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

$jn_rating_val = isset( $attributes['rating'] ) ? absint( $attributes['rating'] ) : 0;

if ( 0 === $jn_rating_val && ! empty( $block->context['postId'] ) ) {
	$jn_meta_rating = get_post_meta( (int) $block->context['postId'], 'jn_rating', true );
	if ( '' !== $jn_meta_rating ) {
		$jn_rating_val = absint( $jn_meta_rating );
	}
}

if ( $jn_rating_val < 1 || $jn_rating_val > 5 ) {
	$jn_rating_val = 5;
}

$jn_star_size = isset( $attributes['size'] ) ? max( 12, min( 36, absint( $attributes['size'] ) ) ) : 18;
$jn_stars_out = '';

for ( $jn_idx = 1; $jn_idx <= 5; $jn_idx++ ) {
	$jn_star_class = $jn_idx <= $jn_rating_val ? 'jn-rating__star is-filled' : 'jn-rating__star is-empty';
	$jn_stars_out .= JN_Icon_Sprite::render(
		array(
			'name'  => 'star',
			'size'  => $jn_star_size,
			'label' => '',
			'class' => $jn_star_class,
		)
	);
}

$jn_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'      => 'jn-rating',
		'role'       => 'img',
		'aria-label' => sprintf( 'Đánh giá %d trên 5 sao', $jn_rating_val ),
	)
);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $jn_wrapper_attributes is escaped by get_block_wrapper_attributes() and $jn_stars_out is sanitized inline SVG from JN_Icon_Sprite::render().
printf( '<div %1$s>%2$s</div>', $jn_wrapper_attributes, $jn_stars_out );
