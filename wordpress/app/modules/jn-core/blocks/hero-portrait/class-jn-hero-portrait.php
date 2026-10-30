<?php
/**
 * `jn/hero-portrait`: the arched portrait of the Home hero (ADR-021).
 *
 * A visually hidden checkbox, the arched frame (primary + secondary photo, shade, caption) and a
 * polaroid `<label>` that toggles the checkbox, so the photo swap works with CSS only (no JS) and from
 * the keyboard. The primary photo is the LCP element: it carries `wp-image-{id}` so the jn-performance
 * content filter keeps it eager / high priority instead of lazy-loading it.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Hero_Portrait
 */
class JN_Hero_Portrait {

	/**
	 * Frame height / width (`aspect-ratio: 4 / 5.1` in hero-home.css).
	 */
	const FRAME_RATIO = 5.1 / 4;

	/**
	 * Rendered frame width per breakpoint, mirroring hero-home.css (media condition => [value, unit]).
	 * Desktop: (1248px container - gap) * 0.88fr share - art padding. Tablet: 540px art cap - padding.
	 * Mobile: 96% of the container - padding.
	 */
	const FRAME_WIDTHS = array(
		'(max-width: 639px)'  => array( 80, 'vw' ),
		'(max-width: 1023px)' => array( 506, 'px' ),
		''                    => array( 485, 'px' ),
	);

	/**
	 * Option, key and default ID of the fallback attachment for each slot.
	 */
	const FALLBACKS = array(
		'primary'   => array( 'jn_step3_media_map', 'hero_portrait', 86 ),
		'secondary' => array( 'jn_legacy_media_map', 'banner_home', 0 ),
	);

	/**
	 * Return the chosen attachment when it is an image, otherwise the slot's fallback.
	 *
	 * @param int    $attachment_id Attachment ID from the block attributes (0 = not set).
	 * @param string $slot          `primary` or `secondary`.
	 * @return int Image attachment ID, or 0 when neither the choice nor the fallback is an image.
	 */
	public static function resolve_image_id( int $attachment_id, string $slot ): int {
		if ( $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ) {
			return $attachment_id;
		}

		list( $option, $key, $default ) = self::FALLBACKS[ $slot ] ?? self::FALLBACKS['primary'];
		$map         = get_option( $option, array() );
		$fallback_id = isset( $map[ $key ]['id'] ) ? (int) $map[ $key ]['id'] : $default;

		if ( $attachment_id > 0 ) {
			wp_trigger_error( __METHOD__, sprintf( 'Attachment %d is not an image; using the %s fallback %d.', $attachment_id, $slot, $fallback_id ) );
		}

		return $fallback_id > 0 && wp_attachment_is_image( $fallback_id ) ? $fallback_id : 0;
	}

	/**
	 * LCP image of the first `jn/hero-portrait` block in some post content, as the block will render it.
	 * jn-performance uses it so the preload and the eager / high-priority rules follow the editor's choice.
	 *
	 * @param string $content Post content.
	 * @return int Attachment ID, or 0 when the content has no hero portrait block.
	 */
	public static function primary_id_in( string $content ): int {
		if ( ! has_block( 'jn/hero-portrait', $content ) ) {
			return 0;
		}
		$stack = parse_blocks( $content );
		while ( $stack ) {
			$block = array_shift( $stack );
			if ( 'jn/hero-portrait' === ( $block['blockName'] ?? '' ) ) {
				return self::resolve_image_id( (int) ( $block['attrs']['primaryId'] ?? 0 ), 'primary' );
			}
			$stack = array_merge( $block['innerBlocks'] ?? array(), $stack );
		}
		return 0;
	}

	/**
	 * `sizes` for an image filling the frame, scaled when `object-fit: cover` draws it wider.
	 *
	 * @param float $scale Source width needed per CSS pixel of frame width (>= 1).
	 * @return string
	 */
	public static function sizes( float $scale = 1.0 ): string {
		$parts = array();
		foreach ( self::FRAME_WIDTHS as $condition => list( $value, $unit ) ) {
			$width   = (int) ceil( $value * max( 1.0, $scale ) ) . $unit;
			$parts[] = '' === $condition ? $width : $condition . ' ' . $width;
		}
		return implode( ', ', $parts );
	}

	/**
	 * `sizes` for an attachment shown in the frame: a source wider than 4:5.1 is drawn at frame height
	 * by `object-fit: cover`, so it needs proportionally more pixels than the frame width.
	 *
	 * @param int $attachment_id Image attachment ID.
	 * @return string
	 */
	public static function sizes_for( int $attachment_id ): string {
		$meta   = wp_get_attachment_metadata( $attachment_id );
		$width  = isset( $meta['width'] ) ? (int) $meta['width'] : 0;
		$height = isset( $meta['height'] ) ? (int) $meta['height'] : 0;
		if ( $width <= 0 || $height <= 0 ) {
			return self::sizes();
		}
		$scale = self::FRAME_RATIO * $width / $height;
		// Within 5% of the frame shape the crop is negligible: keep the exact LCP preload sizes.
		return $scale > 1.05 ? self::sizes( $scale ) : self::sizes();
	}

	/**
	 * Render the block.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public static function render( array $attributes ): string {
		$primary_id = self::resolve_image_id( (int) ( $attributes['primaryId'] ?? 0 ), 'primary' );
		if ( 0 === $primary_id ) {
			wp_trigger_error( __METHOD__, 'No hero portrait image is available; the block renders nothing.' );
			return '';
		}
		$secondary_id = self::resolve_image_id( (int) ( $attributes['secondaryId'] ?? 0 ), 'secondary' );

		$text = static fn( string $key, string $default ): string => esc_html( (string) ( $attributes[ $key ] ?? $default ) );

		$primary = wp_get_attachment_image(
			$primary_id,
			'full',
			false,
			array(
				'class'         => 'jn-hero-portrait__img jn-hero-portrait__img--primary wp-image-' . $primary_id,
				'sizes'         => self::sizes(),
				'loading'       => 'eager',
				'fetchpriority' => 'high',
				'decoding'      => 'async',
			)
		);

		$toggle    = '';
		$secondary = '';
		$polaroid  = '';
		if ( $secondary_id > 0 ) {
			$swap_id   = wp_unique_id( 'jn-hero-swap-' );
			$toggle    = sprintf( '<input type="checkbox" id="%s" class="jn-hero-portrait__toggle">', esc_attr( $swap_id ) );
			$secondary = wp_get_attachment_image(
				$secondary_id,
				'full',
				false,
				array(
					'class'    => 'jn-hero-portrait__img jn-hero-portrait__img--alt wp-image-' . $secondary_id,
					'sizes'    => self::sizes_for( $secondary_id ),
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);
			$thumb     = wp_get_attachment_image(
				$secondary_id,
				'medium_large',
				false,
				array(
					'class'       => 'jn-hero-portrait__thumb',
					'alt'         => '',
					'sizes'       => '(max-width: 639px) 34vw, 190px',
					'loading'     => 'lazy',
					'decoding'    => 'async',
					'aria-hidden' => 'true',
				)
			);
			// The <label> names the checkbox: the visible words first (WCAG 2.5.3, voice control), then the
			// Vietnamese description for screen readers.
			$label_lang = (string) ( $attributes['labelLang'] ?? 'en' );
			$polaroid   = sprintf(
				'<label for="%1$s" class="jn-hero-portrait__polaroid">%2$s<span class="jn-hero-portrait__polaroid-text"%3$s>%4$s <span aria-hidden="true">✧</span></span><span class="screen-reader-text"> — %5$s</span></label>',
				esc_attr( $swap_id ),
				$thumb,
				preg_match( '/^[a-z]{2,3}(?:-[A-Za-z0-9]{2,8})?$/', $label_lang ) ? ' lang="' . esc_attr( $label_lang ) . '"' : '',
				$text( 'polaroidLabel', 'Tap to explore' ),
				$text( 'toggleLabel', 'Đổi ảnh chân dung' )
			);
		}

		// The checkbox precedes the frame and the polaroid: the swap uses `:checked ~` sibling selectors.
		return sprintf(
			'<div %1$s><span class="jn-hero-portrait__outline" aria-hidden="true"></span>%2$s<div class="jn-hero-portrait__frame">%3$s%4$s<span class="jn-hero-portrait__shade" aria-hidden="true"></span><p class="jn-hero-portrait__caption" aria-hidden="true"><small>%5$s</small><strong>%6$s</strong></p></div>%7$s<p class="jn-hero-portrait__side" aria-hidden="true">%8$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'jn-hero-portrait' ) ),
			$toggle,
			$primary,
			$secondary,
			$text( 'captionKicker', 'The Art Of' ),
			$text( 'captionTitle', 'Inner Beauty' ),
			$polaroid,
			$text( 'sideText', 'Balance ✳ Beauty' )
		);
	}
}
