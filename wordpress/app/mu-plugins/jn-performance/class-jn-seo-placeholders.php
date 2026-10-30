<?php
/**
 * JN SEO — Hide "[CẦN NỘI DUNG: …]" content scaffolds from visitors (Step 14, finding F14-03, ADR-020).
 *
 * Pages awaiting owner copy (UNK-03, UNK-08, UNK-09) carry visible scaffolds: a `jn-scaffold-placeholder`
 * group ("Khung Nội Dung Chờ Cập Nhật" + heading + marker paragraph) or a lone marker paragraph (page-intro
 * lead of the legal pages). Crawlers and answer engines would index that text. Owner decision (2026-10-30):
 * visitors never see it; users who can edit pages still do, as a reminder. A scaffold counts as pending
 * only while its source still holds the marker, so typing real copy into it (class kept) publishes it with
 * no code change. Logged-in users bypass the page cache, so cached pages always hold the visitor version.
 *
 * @package JN_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scaffold placeholder visibility.
 */
class JN_SEO_Placeholders {

	/**
	 * Text that opens every scaffold marker paragraph.
	 *
	 * @var string
	 */
	const MARKER = '[CẦN NỘI DUNG';

	/**
	 * Class name of the group that wraps a scaffold section.
	 *
	 * @var string
	 */
	const SCAFFOLD_CLASS = 'jn-scaffold-placeholder';

	/**
	 * Capability that keeps scaffolds visible (scaffolds only live on pages).
	 *
	 * @var string
	 */
	const CAPABILITY = 'edit_pages';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'render_block', array( __CLASS__, 'filter_render_block' ), 10, 2 );
		// Core builds automatic excerpts from leaf blocks only (excerpt_remove_blocks()), so the group rule in
		// filter_render_block() never sees the scaffold there; runs before core's wp_trim_excerpt (priority 10).
		add_filter( 'get_the_excerpt', array( __CLASS__, 'filter_excerpt' ), 9, 2 );
	}

	/**
	 * Drop scaffold blocks from the rendered page for visitors.
	 *
	 * @param string               $content Rendered block HTML (inner blocks already filtered).
	 * @param array<string, mixed> $block   Parsed block.
	 * @return string
	 */
	public static function filter_render_block( $content, $block ) {
		if ( ! is_string( $content ) || ! is_array( $block ) || current_user_can( self::CAPABILITY ) ) {
			return $content;
		}
		return self::should_hide( $block, $content ) ? '' : $content;
	}

	/**
	 * Build the automatic excerpt from the visitor version of the content.
	 *
	 * @param string       $excerpt Manual excerpt ('' when none).
	 * @param WP_Post|null $post    Post.
	 * @return string
	 */
	public static function filter_excerpt( $excerpt, $post = null ) {
		if ( '' !== (string) $excerpt || ! $post instanceof WP_Post || false === strpos( $post->post_content, self::MARKER )
			|| current_user_can( self::CAPABILITY ) ) {
			return $excerpt;
		}
		$blocks       = parse_blocks( $post->post_content );
		$root         = array(
			'innerBlocks'  => $blocks,
			'innerContent' => array_fill( 0, count( $blocks ), null ),
		);
		$visitor_post = new WP_Post(
			(object) array_merge(
				get_object_vars( $post ),
				array( 'post_content' => serialize_blocks( self::without_pending( $root )['innerBlocks'] ) )
			)
		);
		return wp_trim_excerpt( '', $visitor_post );
	}

	/**
	 * Whether a rendered block is a pending scaffold, or a wrapper left empty by removing one.
	 *
	 * @param array<string, mixed> $block   Parsed block.
	 * @param string               $content Rendered block HTML.
	 * @return bool
	 */
	private static function should_hide( array $block, string $content ): bool {
		if ( self::is_pending( $block ) ) {
			return true;
		}
		if ( empty( $block['innerBlocks'] ) || ! self::contains_pending( $block ) ) {
			return false;
		}
		// A section/container whose only child was a scaffold would otherwise leave a blank padded band.
		return '' === trim( wp_strip_all_tags( $content ) )
			&& ! preg_match( '#<(?:img|picture|video|audio|iframe|embed|object|canvas|form|svg|hr)\b#i', $content );
	}

	/**
	 * Whether the block itself is a pending scaffold group or a marker paragraph (judged on its source).
	 *
	 * @param array<string, mixed> $block Parsed block.
	 * @return bool
	 */
	private static function is_pending( array $block ): bool {
		$name = (string) ( $block['blockName'] ?? '' );
		if ( 'core/paragraph' === $name ) {
			$html = (string) ( $block['innerHTML'] ?? '' );
			return false !== strpos( $html, self::MARKER ) && 0 === strpos( trim( wp_strip_all_tags( $html ) ), self::MARKER );
		}
		$classes = preg_split( '/\s+/', (string) ( $block['attrs']['className'] ?? '' ) );
		return 'core/group' === $name && in_array( self::SCAFFOLD_CLASS, (array) $classes, true ) && self::has_marker( $block );
	}

	/**
	 * Whether the marker appears anywhere in the block's source (inner blocks included).
	 *
	 * @param array<string, mixed> $block Parsed block.
	 * @return bool
	 */
	private static function has_marker( array $block ): bool {
		if ( false !== strpos( (string) ( $block['innerHTML'] ?? '' ), self::MARKER ) ) {
			return true;
		}
		foreach ( (array) ( $block['innerBlocks'] ?? array() ) as $inner ) {
			if ( is_array( $inner ) && self::has_marker( $inner ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether a pending scaffold sits anywhere inside the block.
	 *
	 * @param array<string, mixed> $block Parsed block.
	 * @return bool
	 */
	private static function contains_pending( array $block ): bool {
		foreach ( (array) ( $block['innerBlocks'] ?? array() ) as $inner ) {
			if ( is_array( $inner ) && ( self::is_pending( $inner ) || self::contains_pending( $inner ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return a copy of the block without its pending inner blocks (innerContent kept aligned for serialize_blocks()).
	 *
	 * @param array<string, mixed> $block Parsed block.
	 * @return array<string, mixed>
	 */
	private static function without_pending( array $block ): array {
		$inner_blocks  = array();
		$inner_content = array();
		$index         = 0;
		foreach ( (array) ( $block['innerContent'] ?? array() ) as $chunk ) {
			if ( null !== $chunk ) {
				$inner_content[] = $chunk;
				continue;
			}
			$inner = $block['innerBlocks'][ $index ] ?? null;
			++$index;
			if ( is_array( $inner ) && ! self::is_pending( $inner ) ) {
				$inner_blocks[]  = self::without_pending( $inner );
				$inner_content[] = null;
			}
		}
		return array_merge(
			$block,
			array(
				'innerBlocks'  => $inner_blocks,
				'innerContent' => $inner_content,
			)
		);
	}
}
