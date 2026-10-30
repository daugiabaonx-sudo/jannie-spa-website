<?php
/**
 * Server-side render template for the `jn/video` block: a YouTube facade.
 *
 * Outputs a link to the YouTube watch page holding a self-hosted poster and a play icon, so the page makes
 * no request to YouTube until the visitor clicks; `view.js` then swaps the link for a youtube-nocookie
 * player. Without JS the link simply opens YouTube. Inside a Query Loop the video, poster and name come
 * from the testimonial (`jn_video_url`, `jn_video_poster_id`, `jn_client_name`); block attributes win.
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

$jn_video_post = ! empty( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;
$jn_video_meta = static fn( string $key ): string => $jn_video_post > 0 ? (string) get_post_meta( $jn_video_post, $key, true ) : '';

$jn_video_url = '' !== (string) ( $attributes['url'] ?? '' ) ? (string) $attributes['url'] : $jn_video_meta( 'jn_video_url' );
$jn_video_id  = JN_Testimonials::youtube_id( $jn_video_url );
if ( '' === $jn_video_id ) {
	return;
}

$jn_video_poster = absint( $attributes['posterId'] ?? 0 );
if ( 0 === $jn_video_poster ) {
	$jn_video_poster = absint( $jn_video_meta( 'jn_video_poster_id' ) );
}

$jn_video_name  = '' !== (string) ( $attributes['label'] ?? '' ) ? (string) $attributes['label'] : $jn_video_meta( 'jn_client_name' );
$jn_video_label = '' !== $jn_video_name ? sprintf( 'Xem video cảm nhận của %s', $jn_video_name ) : 'Xem video trên YouTube';

$jn_video_img = $jn_video_poster > 0 ? wp_get_attachment_image(
	$jn_video_poster,
	'large',
	false,
	array(
		'class'    => 'jn-video__poster',
		'alt'      => '',
		'loading'  => 'lazy',
		'decoding' => 'async',
		'sizes'    => '(min-width: 1024px) 600px, 100vw',
	)
) : '';

// Material Symbols Outlined `play_arrow` (Apache-2.0), decorative.
$jn_video_icon = '<svg class="jn-video__icon" viewBox="0 -960 960 960" width="28" height="28" aria-hidden="true" focusable="false"><path d="M320-200v-560l440 280-440 280Z"/></svg>';

printf(
	'<div %1$s><a class="jn-video__link" href="%2$s" data-jn-video-id="%3$s">%4$s<span class="jn-video__play">%5$s</span><span class="screen-reader-text">%6$s</span></a></div>',
	get_block_wrapper_attributes( array( 'class' => 'jn-video' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by get_block_wrapper_attributes().
	esc_url( 'https://www.youtube.com/watch?v=' . $jn_video_id ),
	esc_attr( $jn_video_id ),
	$jn_video_img, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output.
	$jn_video_icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
	esc_html( $jn_video_label )
);
