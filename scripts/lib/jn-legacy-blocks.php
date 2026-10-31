<?php
/**
 * Step 15 (ADR-022): read the old jannieedu.com copy (`jn-legacy-content.json`) and build the block markup of
 * /my-favorite-product/, /course/ and /client-success-story/ from it. Shared by the migration
 * (build-step-15-legacy-content.php) and its test, so the stored pages and the checks can never drift.
 *
 * Markup uses core blocks, the theme's block styles and `jn/*` blocks only (no Custom HTML, no shortcodes);
 * /my-favorite-product/ comes from the theme's `jannie/favorite-*` patterns (Step 18).
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const JN_LB_CONTENT_FILE = __DIR__ . '/jn-legacy-content.json';
const JN_LB_MEDIA_OPTION = 'jn_legacy_media_map';

/**
 * Decoded content source.
 *
 * @return array<string, mixed>
 * @throws RuntimeException When the file is missing or invalid.
 */
function jn_lb_content(): array {
	static $content = null;
	if ( null === $content ) {
		$json    = is_file( JN_LB_CONTENT_FILE ) ? (string) file_get_contents( JN_LB_CONTENT_FILE ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = json_decode( $json, true );
		if ( ! is_array( $content ) ) {
			throw new RuntimeException( 'Invalid or missing legacy content: ' . JN_LB_CONTENT_FILE );
		}
	}
	return $content;
}

/**
 * Imported legacy image by media-map key.
 *
 * @param string $key Key in `jn-legacy-media.json`.
 * @return array{id: int, url: string, alt: string, width: int, height: int}
 */
function jn_lb_media( string $key ): array {
	$map  = get_option( JN_LB_MEDIA_OPTION, array() );
	$item = is_array( $map ) && isset( $map[ $key ] ) && is_array( $map[ $key ] ) ? $map[ $key ] : array();
	return array(
		'id'     => (int) ( $item['id'] ?? 0 ),
		'url'    => (string) ( $item['url'] ?? '' ),
		'alt'    => (string) ( $item['alt'] ?? '' ),
		'width'  => (int) ( $item['width'] ?? 0 ),
		'height' => (int) ( $item['height'] ?? 0 ),
	);
}

/**
 * Serialise one block: comment delimiters around $inner, or a self-closing comment when $inner is null.
 *
 * @param string               $name  Block name (`core/` prefix optional).
 * @param array<string, mixed> $attrs Block attributes.
 * @param string|null          $inner Saved inner HTML (already escaped).
 * @return string
 */
function jn_lb_block( string $name, array $attrs = array(), ?string $inner = null ): string {
	$name = str_starts_with( $name, 'core/' ) ? substr( $name, 5 ) : $name;
	$json = array() === $attrs ? '' : ' ' . serialize_block_attributes( $attrs );
	if ( null === $inner ) {
		return "<!-- wp:{$name}{$json} /-->\n";
	}
	return "<!-- wp:{$name}{$json} -->\n{$inner}\n<!-- /wp:{$name} -->\n";
}

/**
 * Class attribute from a list of class names (empty ones dropped).
 *
 * @param string ...$classes Class names.
 * @return string
 */
function jn_lb_classes( string ...$classes ): string {
	return esc_attr( implode( ' ', array_filter( $classes ) ) );
}

/**
 * `core/paragraph` in the exact markup the editor saves (alignment, className, colour, font size).
 *
 * @param string $text      Plain text (escaped here).
 * @param string $class     className.
 * @param array  $extra     Extra attributes: align, textColor, fontSize.
 * @return string
 */
function jn_lb_p( string $text, string $class = '', array $extra = array() ): string {
	$attrs = array_filter(
		array(
			'align'     => $extra['align'] ?? '',
			'className' => $class,
			'textColor' => $extra['textColor'] ?? '',
			'fontSize'  => $extra['fontSize'] ?? '',
		)
	);
	$html_class = jn_lb_classes(
		isset( $attrs['align'] ) ? 'has-text-align-' . $attrs['align'] : '',
		$class,
		isset( $attrs['textColor'] ) ? 'has-' . $attrs['textColor'] . '-color has-text-color' : '',
		isset( $attrs['fontSize'] ) ? 'has-' . $attrs['fontSize'] . '-font-size' : ''
	);
	$open = '' === $html_class ? '<p>' : '<p class="' . $html_class . '">';
	return jn_lb_block( 'paragraph', $attrs, $open . esc_html( $text ) . '</p>' );
}

/**
 * `core/heading`. `$accent` is appended in the italic accent span used by every page title (ADR-014).
 *
 * @param int    $level  1–6.
 * @param string $text   Plain text.
 * @param string $class  className.
 * @param string $accent Optional italic accent (last words of the title).
 * @param string $align  Optional text alignment.
 * @return string
 */
function jn_lb_h( int $level, string $text, string $class = '', string $accent = '', string $align = '' ): string {
	$attrs = array_filter(
		array(
			'textAlign' => $align,
			'level'     => 2 === $level ? null : $level,
			'className' => $class,
		),
		static fn( $v ) => null !== $v && '' !== $v
	);
	// Join the accent's last two words with a no-break space so its last word never wraps alone (ADR-014).
	$accent = (string) preg_replace( '/ (?=\S+$)/u', "\u{00a0}", $accent );
	$body   = esc_html( $text ) . ( '' !== $accent ? ' <em class="jn-accent-italic">' . esc_html( $accent ) . '</em>' : '' );
	$html   = sprintf( '<h%1$d class="%2$s">%3$s</h%1$d>', $level, jn_lb_classes( 'wp-block-heading', '' !== $align ? 'has-text-align-' . $align : '', $class ), $body );
	return jn_lb_block( 'heading', $attrs, $html );
}

/**
 * `core/group`.
 *
 * @param string $inner Inner blocks.
 * @param string $class className.
 * @param array  $extra tagName, align, backgroundColor, name (List View label).
 * @return string
 */
function jn_lb_group( string $inner, string $class = '', array $extra = array() ): string {
	$tag   = $extra['tagName'] ?? 'div';
	$attrs = array_filter(
		array(
			'tagName'         => 'div' === $tag ? '' : $tag,
			'metadata'        => isset( $extra['name'] ) ? array( 'name' => $extra['name'] ) : '',
			'align'           => $extra['align'] ?? '',
			'backgroundColor' => $extra['backgroundColor'] ?? '',
			'className'       => $class,
		)
	);
	$html_class = jn_lb_classes(
		'wp-block-group',
		isset( $attrs['align'] ) ? 'align' . $attrs['align'] : '',
		$class,
		isset( $attrs['backgroundColor'] ) ? 'has-' . $attrs['backgroundColor'] . '-background-color has-background' : ''
	);
	return jn_lb_block( 'group', $attrs, "<{$tag} class=\"{$html_class}\">\n" . $inner . "</{$tag}>" );
}

/**
 * Full-width page section with the standard container.
 *
 * @param string $inner Container content.
 * @param string $class Extra section classes.
 * @param string $name  List View label.
 * @param string $bg    Optional palette background slug.
 * @return string
 */
function jn_lb_section( string $inner, string $class, string $name, string $bg = '' ): string {
	return jn_lb_group(
		jn_lb_group( $inner, 'jn-container' ),
		trim( 'jn-section ' . $class ),
		array_filter(
			array(
				'tagName'         => 'section',
				'name'            => $name,
				'align'           => 'full',
				'backgroundColor' => $bg,
			)
		)
	);
}

/**
 * `core/image` of an imported legacy picture, optionally linked.
 *
 * @param string $key   Media-map key.
 * @param string $class className.
 * @param string $href  Optional link target.
 * @param string $alt   Optional alt override (e.g. the link destination for a logo).
 * @return string
 */
function jn_lb_image( string $key, string $class = '', string $href = '', string $alt = '' ): string {
	$media = jn_lb_media( $key );
	if ( $media['id'] < 1 ) {
		throw new RuntimeException( "Legacy image `{$key}` is not imported — run import-legacy-media.php first" );
	}
	$alt   = '' !== $alt ? $alt : $media['alt'];
	$img   = sprintf( '<img src="%s" alt="%s" class="wp-image-%d"/>', esc_url( $media['url'] ), esc_attr( $alt ), $media['id'] );
	$attrs = array_filter(
		array(
			'id'              => $media['id'],
			'sizeSlug'        => 'full',
			'linkDestination' => '' !== $href ? 'custom' : 'none',
			'className'       => $class,
		)
	);
	$body = '' !== $href ? '<a href="' . esc_url( $href ) . '">' . $img . '</a>' : $img;
	return jn_lb_block( 'image', $attrs, '<figure class="' . jn_lb_classes( 'wp-block-image', 'size-full', $class ) . '">' . $body . '</figure>' );
}

/**
 * `core/columns` from column bodies; widths are optional flex-basis percentages.
 *
 * @param array<int, array{0: string, 1?: string, 2?: string}> $cols  [inner, width, className] per column.
 * @param string                                               $class Columns className.
 * @return string
 */
function jn_lb_columns( array $cols, string $class = '' ): string {
	$inner = '';
	foreach ( $cols as $col ) {
		$width  = $col[1] ?? '';
		$cclass = $col[2] ?? '';
		$attrs  = array_filter(
			array(
				'width'     => $width,
				'className' => $cclass,
			)
		);
		$style  = '' !== $width ? ' style="flex-basis:' . esc_attr( $width ) . '"' : '';
		$inner .= jn_lb_block( 'column', $attrs, '<div class="' . jn_lb_classes( 'wp-block-column', $cclass ) . '"' . $style . ">\n" . $col[0] . '</div>' );
	}
	return jn_lb_block( 'columns', array_filter( array( 'className' => $class ) ), '<div class="' . jn_lb_classes( 'wp-block-columns', $class ) . "\">\n" . $inner . '</div>' );
}

/**
 * A single `core/button` in `core/buttons`.
 *
 * @param string $label Button text.
 * @param string $href  Link.
 * @param string $style Block style (`primary-pill`, `soft-pill`…).
 * @return string
 */
function jn_lb_button( string $label, string $href, string $style = 'primary-pill' ): string {
	$button = jn_lb_block(
		'button',
		array( 'className' => 'is-style-' . $style ),
		'<div class="wp-block-button is-style-' . esc_attr( $style ) . '"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $href ) . '">' . esc_html( $label ) . '</a></div>'
	);
	return jn_lb_block( 'buttons', array(), "<div class=\"wp-block-buttons\">\n" . $button . '</div>' );
}

/**
 * `core/list` of plain-text items, or of `<strong>title</strong> text` items when an item is a [title, text] pair.
 *
 * @param array<int, string|array{0: string, 1: string}> $items   Items.
 * @param string                                         $class   className.
 * @param bool                                           $ordered Ordered list.
 * @return string
 */
function jn_lb_list( array $items, string $class = '', bool $ordered = false ): string {
	$inner = '';
	foreach ( $items as $item ) {
		$body   = is_array( $item ) ? '<strong>' . esc_html( $item[0] ) . '</strong> ' . esc_html( $item[1] ) : esc_html( $item );
		$inner .= jn_lb_block( 'list-item', array(), '<li>' . $body . '</li>' );
	}
	$tag   = $ordered ? 'ol' : 'ul';
	$attrs = array_filter(
		array(
			'ordered'   => $ordered,
			'className' => $class,
		)
	);
	return jn_lb_block( 'list', $attrs, "<{$tag} class=\"" . jn_lb_classes( 'wp-block-list', $class ) . "\">\n" . $inner . "</{$tag}>" );
}

/**
 * Page intro section (same markup as build-step-3.php / ADR-014): eyebrow, H1 with italic accent, lead.
 *
 * @param array{eyebrow?: string, eyebrow_site?: string, title: string, title_accent: string, lead: string} $page Page copy.
 * @return string
 */
function jn_lb_page_intro( array $page ): string {
	$inner = jn_lb_p( $page['eyebrow'] ?? $page['eyebrow_site'], 'is-style-eyebrow-dot' )
		. jn_lb_h( 1, $page['title'], 'jn-page-intro-title', $page['title_accent'] )
		. jn_lb_p(
			$page['lead'],
			'jn-page-intro-lead',
			array(
				'textColor' => 'muted-stone',
				'fontSize'  => 'body-lg',
			)
		);
	return jn_lb_group(
		jn_lb_group( $inner, 'jn-container jn-page-intro-inner' ),
		'jn-section jn-page-intro-section',
		array(
			'tagName'         => 'section',
			'align'           => 'full',
			'backgroundColor' => 'palette-color-7',
		)
	);
}

/**
 * Centred section header (eyebrow optional).
 *
 * @param string $title   H2 text.
 * @param string $eyebrow Optional eyebrow.
 * @return string
 */
function jn_lb_section_header( string $title, string $eyebrow = '' ): string {
	$inner = ( '' !== $eyebrow ? jn_lb_p( $eyebrow, 'is-style-eyebrow jn-eyebrow-wide', array( 'align' => 'center' ) ) : '' )
		. jn_lb_h( 2, $title, 'jn-section-title-lg', '', 'center' );
	return jn_lb_group( $inner, 'jn-section-header-centered' );
}

/** Synced booking section (wp_block 95, `#booking-form`). */
const JN_LB_BOOKING_BLOCK = "<!-- wp:block {\"ref\":95} /-->\n";
const JN_LB_BOOKING_HREF  = '#booking-form';

/**
 * Render a theme pattern file to block markup (PHP in the file runs, e.g. home_url()).
 *
 * @param string $filename File name inside the theme `patterns/` directory.
 * @return string
 * @throws RuntimeException When the pattern file is missing.
 */
function jn_lb_render_pattern( string $filename ): string {
	$path = get_stylesheet_directory() . '/patterns/' . $filename;
	if ( ! is_file( $path ) ) {
		throw new RuntimeException( "Missing theme pattern: patterns/{$filename}" );
	}
	ob_start();
	include $path;
	return trim( (string) ob_get_clean() );
}

/**
 * /my-favorite-product/ (Step 18, ADR-025 — the owner's 2026-10-31 design): hero + marquee, intro with a drop
 * cap, the WooCommerce collection (category "Sản phẩm yêu thích", old order via menu_order) whose cards show
 * the photo, name, mockup teaser (short description), price and `jn/product-actions`, then the manifesto.
 * Built from the `jannie/favorite-*` patterns so the inserter, this builder and the stored page never drift.
 *
 * @return string
 */
function jn_lb_products_page(): string {
	return implode(
		"\n\n",
		array(
			jn_lb_render_pattern( 'favorite-hero.php' ),
			jn_lb_render_pattern( 'favorite-collection.php' ),
			jn_lb_render_pattern( 'favorite-manifesto.php' ),
		)
	);
}

/**
 * One course cluster: image beside the course list (sides alternate as on the old page) and the CTA.
 *
 * @param array<string, mixed> $cluster  Cluster from the source.
 * @param bool                 $reversed Image on the right.
 * @param string               $button   CTA label.
 * @return string
 */
function jn_lb_course_cluster( array $cluster, bool $reversed, string $button ): string {
	$items = '';
	foreach ( $cluster['courses'] as $course ) {
		$items .= jn_lb_group(
			jn_lb_h( 3, $course['title'], 'jn-course-item__title' )
				. jn_lb_p( $course['description'], 'jn-course-item__desc', array( 'textColor' => 'muted-stone' ) ),
			'jn-course-item'
		);
	}
	$media = array( jn_lb_image( $cluster['image'], 'is-style-portrait-3-4 jn-legacy-media' ), '41.67%', 'jn-course-cluster__media' );
	$text  = array( $items . jn_lb_button( $button, JN_LB_BOOKING_HREF ), '58.33%', 'jn-course-cluster__list' );
	return jn_lb_columns( $reversed ? array( $text, $media ) : array( $media, $text ), 'jn-course-cluster' . ( $reversed ? ' is-reversed' : '' ) );
}

/**
 * /course/: intro, banner section, the three categories (26 courses) and the closing band, then booking.
 *
 * @return string
 */
function jn_lb_course_page(): string {
	$k       = jn_lb_content()['courses'];
	$out     = jn_lb_page_intro( $k['page'] ) . "\n";
	$out    .= jn_lb_section(
		jn_lb_section_header( $k['intro']['title'], $k['intro']['eyebrow'] ) . jn_lb_image( $k['intro']['image'], 'jn-course-banner' ),
		'jn-course-intro',
		'Các khóa học tại Jannie Academy (jannieedu.com)'
	) . "\n";
	$reverse = false;
	foreach ( $k['categories'] as $index => $cat ) {
		$clusters = '';
		foreach ( $cat['clusters'] as $cluster ) {
			$clusters .= jn_lb_course_cluster( $cluster, $reverse, $k['button'] );
			$reverse   = ! $reverse;
		}
		$out .= jn_lb_section(
			jn_lb_h( 2, $cat['title'], 'jn-section-title-lg jn-course-category__title', '', 'center' ) . $clusters,
			'jn-course-category',
			'Nhóm khóa học: ' . $cat['title'],
			1 === $index % 2 ? 'palette-color-6' : ''
		) . "\n";
	}
	$closing = jn_lb_p( $k['closing']['eyebrow'], 'is-style-eyebrow jn-eyebrow-wide', array( 'align' => 'center' ) )
		. jn_lb_h( 2, $k['closing']['title'], 'jn-section-title-lg', '', 'center' )
		. jn_lb_p( $k['closing']['text'], 'jn-course-closing__text', array( 'align' => 'center' ) );
	$out    .= jn_lb_section( jn_lb_group( $closing, 'jn-section-header-centered' ), 'jn-course-closing', 'Lời kết (jannieedu.com)', 'palette-color-7' ) . "\n";
	return $out . JN_LB_BOOKING_BLOCK;
}

/**
 * Video testimonials Query Loop: every published `jn_testimonial`, old order, with `jn/video` and the
 * headline, quote and name bound to post meta (Block Bindings). Same markup as patterns/video-testimonials.php
 * when called without a title.
 *
 * @param string $title Optional centred H2 above the list (Step 16: "Phản Hồi Từ Học Viên" on /coaching/).
 * @return string
 */
function jn_lb_video_testimonials( string $title = '' ): string {
	$bound = static fn( string $tag, string $key, string $class ): string => 'heading' === $tag
		? jn_lb_block(
			'heading',
			array(
				'metadata'  => array( 'bindings' => array( 'content' => array( 'source' => 'core/post-meta', 'args' => array( 'key' => $key ) ) ) ),
				'className' => $class,
			),
			'<h2 class="wp-block-heading ' . esc_attr( $class ) . '"></h2>'
		)
		: jn_lb_block(
			'paragraph',
			array(
				'metadata'  => array( 'bindings' => array( 'content' => array( 'source' => 'core/post-meta', 'args' => array( 'key' => $key ) ) ) ),
				'className' => $class,
			),
			'<p class="' . esc_attr( $class ) . '"></p>'
		);
	$text  = jn_lb_group(
		$bound( 'heading', 'jn_headline', 'jn-video-testimonial__headline' )
			. $bound( 'paragraph', 'jn_quote', 'jn-video-testimonial__quote' )
			. $bound( 'paragraph', 'jn_client_name', 'jn-video-testimonial__name' ),
		'jn-video-testimonial__text'
	);
	$item  = jn_lb_group( jn_lb_block( 'jn/video' ) . $text, 'jn-video-testimonial' );
	$loop  = jn_lb_block(
		'query',
		array(
			'queryId'   => 2,
			'query'     => array(
				'perPage'  => 6,
				'pages'    => 0,
				'offset'   => 0,
				'postType' => 'jn_testimonial',
				'order'    => 'asc',
				'orderBy'  => 'menu_order',
				'author'   => '',
				'search'   => '',
				'exclude'  => array(),
				'sticky'   => '',
				'inherit'  => false,
			),
			'className' => 'jn-video-testimonials-query',
		),
		"<div class=\"wp-block-query jn-video-testimonials-query\">\n"
			. jn_lb_block( 'post-template', array( 'className' => 'jn-video-testimonials' ), $item )
			. '</div>'
	);
	$head = '' !== $title ? jn_lb_section_header( $title ) : '';
	return jn_lb_section( $head . $loop, 'jn-video-testimonials-section', 'Cảm nhận học viên (video)' );
}

/**
 * /client-success-story/: every block of the old page in its order, then booking.
 *
 * @return string
 */
function jn_lb_clients_page(): string {
	$k   = jn_lb_content()['clients'];
	$out = jn_lb_page_intro( $k['page'] ) . "\n";

	$logos = '';
	foreach ( $k['press'] as $press ) {
		$logos .= jn_lb_image( $press['image'], 'jn-press-logo', $press['url'], $press['name'] );
	}
	$out .= jn_lb_section(
		jn_lb_p( $k['intro_body'], 'jn-legacy-body jn-legacy-lead-body', array( 'textColor' => 'muted-stone' ) ) . jn_lb_group( $logos, 'jn-press-logos' ),
		'jn-client-intro',
		'Giới thiệu & báo chí (jannieedu.com)'
	) . "\n";

	$about = '';
	foreach ( $k['about']['blocks'] as $block ) {
		$about .= jn_lb_h( 2, $block['title'], 'jn-section-title-md' ) . jn_lb_p( $block['text'], 'jn-legacy-body', array( 'textColor' => 'muted-stone' ) );
	}
	$out .= jn_lb_section(
		jn_lb_columns(
			array(
				array( jn_lb_image( $k['about']['image'], 'is-style-portrait-3-4 jn-legacy-media' ), '41.67%', 'jn-legacy-split__media' ),
				array( $about, '58.33%', 'jn-legacy-split__text' ),
			),
			'jn-legacy-split'
		),
		'jn-client-about',
		'Đào tạo chất lượng (jannieedu.com)',
		'palette-color-7'
	) . "\n";

	$out .= jn_lb_video_testimonials() . "\n";

	$groups = '';
	foreach ( $k['groups'] as $group ) {
		$items = '';
		foreach ( $group['items'] as $item ) {
			$items .= jn_lb_h( 3, $item['title'], 'jn-course-group__item-title' )
				. ( isset( $item['text'] ) ? jn_lb_p( $item['text'], 'jn-course-group__item-text', array( 'textColor' => 'muted-stone' ) ) : jn_lb_list( $item['bullets'], 'is-style-check' ) );
		}
		$groups .= jn_lb_group(
			jn_lb_h( 2, $group['title'], 'jn-course-group__title' )
				. jn_lb_p( $group['text'], 'jn-course-group__text', array( 'textColor' => 'muted-stone' ) )
				. $items
				. jn_lb_button( $k['group_button'], JN_LB_BOOKING_HREF, 'soft-pill' ),
			'is-style-card jn-course-group'
		);
	}
	$out .= jn_lb_section( jn_lb_group( $groups, 'jn-course-groups' ), 'jn-client-groups', 'Các nhóm khóa học (jannieedu.com)', 'palette-color-6' ) . "\n";

	$values = array();
	foreach ( $k['values']['items'] as $value ) {
		$values[] = array( jn_lb_group( jn_lb_h( 3, $value['title'], 'jn-pillar-title' ) . jn_lb_p( $value['text'], 'jn-pillar-desc', array( 'textColor' => 'muted-stone' ) ), 'is-style-card jn-legacy-value' ) );
	}
	$out .= jn_lb_section( jn_lb_section_header( $k['values']['title'] ) . jn_lb_columns( $values, 'jn-legacy-values' ), 'jn-client-values', 'Những giá trị sau khi học (jannieedu.com)' ) . "\n";

	$steps = array_map( static fn( array $s ): array => array( $s['title'], $s['text'] ), $k['process']['steps'] );
	$out  .= jn_lb_section( jn_lb_section_header( $k['process']['title'] ) . jn_lb_list( $steps, 'jn-booking-steps jn-legacy-process', true ), 'jn-client-process', 'Quy trình học (jannieedu.com)', 'palette-color-7' ) . "\n";

	$c    = $k['closing'];
	$out .= jn_lb_section(
		jn_lb_columns(
			array(
				array( jn_lb_image( $c['image'], 'is-style-portrait-3-4 jn-legacy-media' ), '41.67%', 'jn-legacy-split__media' ),
				array(
					jn_lb_h( 2, $c['title'], 'jn-section-title-md' )
						. jn_lb_h( 3, $c['subtitle'], 'jn-legacy-subtitle' )
						. jn_lb_p( $c['text'], 'jn-legacy-body', array( 'textColor' => 'muted-stone' ) )
						. jn_lb_button( $c['button'], JN_LB_BOOKING_HREF ),
					'58.33%',
					'jn-legacy-split__text',
				),
			),
			'jn-legacy-split'
		),
		'jn-client-closing',
		'Chia sẻ cảm nhận (jannieedu.com)'
	) . "\n";

	return $out . JN_LB_BOOKING_BLOCK;
}

/**
 * Write a page's content when it differs from the built markup, then check it was stored byte for byte
 * (kses would strip the column style and Block Bindings). The revision is the rollback point.
 *
 * @param int    $page_id Page ID.
 * @param string $content Built content.
 * @return string `skip` or `written`.
 * @throws RuntimeException When the write fails or the stored markup differs.
 */
function jn_lb_write_page( int $page_id, string $content ): string {
	if ( (string) get_post_field( 'post_content', $page_id, 'raw' ) === $content ) {
		return 'skip';
	}
	$updated = wp_update_post(
		array(
			'ID'           => $page_id,
			'post_content' => wp_slash( $content ),
		),
		true
	);
	if ( is_wp_error( $updated ) ) {
		throw new RuntimeException( "page {$page_id}: " . $updated->get_error_message() );
	}
	clean_post_cache( $page_id );
	if ( (string) get_post_field( 'post_content', $page_id, 'raw' ) !== $content ) {
		throw new RuntimeException( "page {$page_id}: saved content differs from the built markup (kses?) — restore the latest revision" );
	}
	return 'written';
}

/**
 * Split page hero (Step 16): eyebrow, H1 with accent, lead, optional body and CTA beside the old photo,
 * which is the page's LCP image (jn-performance preloads it via jn_legacy_hero_lcp_id()).
 *
 * @param array<string, string> $page Page copy: eyebrow_site, title, title_accent, lead, image, body?, button?.
 * @param string                $name Section label in List View.
 * @return string
 */
function jn_lb_split_hero( array $page, string $name ): string {
	$text = jn_lb_p( $page['eyebrow_site'], 'is-style-eyebrow-dot' )
		. jn_lb_h( 1, $page['title'], 'jn-page-intro-title', $page['title_accent'] )
		. jn_lb_p(
			$page['lead'],
			'jn-page-intro-lead',
			array(
				'textColor' => 'muted-stone',
				'fontSize'  => 'body-lg',
			)
		)
		. ( isset( $page['body'] ) ? jn_lb_p( $page['body'], 'jn-legacy-body', array( 'textColor' => 'muted-stone' ) ) : '' )
		. ( isset( $page['button'] ) ? jn_lb_button( $page['button'], JN_LB_BOOKING_HREF ) : '' );
	$grid = jn_lb_columns(
		array(
			array( $text, '58.33%', 'jn-legacy-hero__text' ),
			array( jn_lb_image( $page['image'], 'jn-legacy-hero__image jn-hero-lcp-image' ), '41.67%', 'jn-legacy-hero__media' ),
		),
		'jn-legacy-hero'
	);
	return jn_lb_group(
		jn_lb_group( $grid, 'jn-container jn-page-intro-inner' ),
		'jn-section jn-page-intro-section jn-legacy-hero-section',
		array(
			'tagName'         => 'section',
			'name'            => $name,
			'align'           => 'full',
			'backgroundColor' => 'palette-color-7',
		)
	);
}

/**
 * `core/cover` band with an old photo behind white text, as on the old page. The photos are mostly cream, so a
 * 60% black overlay (darkest-case background ~rgb(96,96,96)) keeps white text above 6:1; axe cannot measure text on images.
 *
 * @param string $key   Media-map key of the background photo.
 * @param string $inner Inner blocks.
 * @param string $class className.
 * @return string
 */
function jn_lb_cover( string $key, string $inner, string $class ): string {
	$media = jn_lb_media( $key );
	if ( $media['id'] < 1 ) {
		throw new RuntimeException( "Legacy image `{$key}` is not imported — run import-legacy-media.php first" );
	}
	$attrs = array(
		'url'       => $media['url'],
		'id'        => $media['id'],
		'dimRatio'  => 60,
		'isDark'    => true,
		'align'     => 'full',
		'className' => $class,
	);
	$html  = '<div class="' . jn_lb_classes( 'wp-block-cover', 'alignfull', $class ) . '">'
		. '<img class="wp-block-cover__image-background wp-image-' . $media['id'] . '" alt="" src="' . esc_url( $media['url'] ) . '" data-object-fit="cover"/>'
		. '<span aria-hidden="true" class="wp-block-cover__background has-background-dim-60 has-background-dim"></span>'
		. "<div class=\"wp-block-cover__inner-container\">\n" . jn_lb_group( $inner, 'jn-container jn-cover-inner' ) . '</div></div>';
	return jn_lb_block( 'cover', $attrs, $html );
}

/**
 * Image beside text, image side alternating (shared by My Story and the Coaching cards).
 *
 * @param string $image    Media-map key.
 * @param string $text     Text column blocks.
 * @param bool   $reversed Image on the right.
 * @param string $style    Image block style (`portrait-3-4` or square).
 * @return string
 */
function jn_lb_media_row( string $image, string $text, bool $reversed, string $style = 'is-style-portrait-3-4' ): string {
	$media = array( jn_lb_image( $image, trim( $style . ' jn-legacy-media' ) ), '41.67%', 'jn-legacy-split__media' );
	$copy  = array( $text, '58.33%', 'jn-legacy-split__text' );
	return jn_lb_columns( $reversed ? array( $copy, $media ) : array( $media, $copy ), 'jn-legacy-split' . ( $reversed ? ' is-reversed' : '' ) );
}

/**
 * /my-story/: split hero, the five old image/text blocks, the closing invitation, then booking.
 *
 * @return string
 */
function jn_lb_my_story_page(): string {
	$k   = jn_lb_content()['my_story'];
	$out = jn_lb_split_hero( $k['page'], 'Hero My Story (jannieedu.com)' ) . "\n";
	foreach ( $k['blocks'] as $index => $block ) {
		$text = jn_lb_h( 2, $block['title'], 'jn-section-title-md' );
		foreach ( $block['paragraphs'] as $paragraph ) {
			$text .= jn_lb_p( $paragraph, 'jn-legacy-body', array( 'textColor' => 'muted-stone' ) );
		}
		$out .= jn_lb_section(
			jn_lb_media_row( $block['image'], $text, 1 === $index % 2, 'jn-legacy-media--story' ),
			'jn-story-block',
			'My Story: ' . $block['title'],
			1 === $index % 2 ? 'palette-color-6' : ''
		) . "\n";
	}
	$closing = '';
	foreach ( $k['closing']['paragraphs'] as $paragraph ) {
		$closing .= jn_lb_p( $paragraph, 'jn-legacy-body jn-legacy-lead-body', array( 'textColor' => 'muted-stone' ) );
	}
	$closing .= jn_lb_group( jn_lb_button( $k['closing']['button'], JN_LB_BOOKING_HREF ), 'jn-legacy-center' );
	$out     .= jn_lb_section( $closing, 'jn-story-closing', 'Mời đặt lịch (jannieedu.com)', 'palette-color-7' ) . "\n";
	return $out . JN_LB_BOOKING_BLOCK;
}

/**
 * /coaching/: the eight old blocks in order, then the kept tiers and FAQ (D1), then booking.
 *
 * @param string $tiers Stored "Section 3 — Coaching Tiers" markup ('' to build without it).
 * @param string $faq   Stored FAQ section markup ('' to build without it).
 * @return string
 */
function jn_lb_coaching_page( string $tiers, string $faq ): string {
	$k   = jn_lb_content()['coaching'];
	$out = jn_lb_split_hero( $k['page'], 'Hero Coaching (jannieedu.com)' ) . "\n";

	$intro = jn_lb_h( 2, $k['intro']['title'], 'jn-section-title-lg', '', 'center' )
		. jn_lb_p( $k['intro']['subtitle'], 'jn-legacy-subtitle-p', array( 'align' => 'center' ) )
		. jn_lb_p( $k['intro']['text'], 'jn-legacy-body jn-legacy-lead-body', array( 'textColor' => 'muted-stone' ) );
	$card  = static fn( array $c ): string => jn_lb_h( 3, $c['title'], 'jn-legacy-subtitle' )
		. jn_lb_p( $c['text'], 'jn-legacy-body', array( 'textColor' => 'muted-stone' ) )
		. jn_lb_button( $c['button'], JN_LB_BOOKING_HREF );
	$out  .= jn_lb_section(
		jn_lb_group( $intro, 'jn-section-header-centered jn-coaching-intro__head' )
			. jn_lb_media_row( $k['card_1']['image'], $card( $k['card_1'] ), false, 'jn-legacy-media--square' ),
		'jn-coaching-intro',
		'Học cách tự làm đẹp (jannieedu.com)'
	) . "\n";

	$out .= jn_lb_section(
		jn_lb_h( 2, $k['learn']['title'], 'jn-section-title-md' ) . jn_lb_list( $k['learn']['items'], 'is-style-check jn-legacy-checklist' ),
		'jn-coaching-learn',
		'Bạn sẽ được học (jannieedu.com)',
		'palette-color-6'
	) . "\n";

	$out .= jn_lb_section(
		jn_lb_media_row( $k['card_2']['image'], $card( $k['card_2'] ), true, 'jn-legacy-media--square' ),
		'jn-coaching-card',
		'Chương trình coaching (jannieedu.com)'
	) . "\n";

	$out .= jn_lb_cover(
		$k['expert']['image'],
		jn_lb_p( $k['expert']['eyebrow'], 'is-style-eyebrow jn-cover-eyebrow' )
			. jn_lb_h( 2, $k['expert']['title'], 'jn-section-title-lg jn-cover-title' )
			. jn_lb_button( $k['expert']['button'], JN_LB_BOOKING_HREF, 'accent-pill' ),
		'jn-coaching-expert'
	) . "\n";

	$out .= jn_lb_video_testimonials( $k['testimonials_title'] ) . "\n";

	$benefits = jn_lb_h( 2, $k['benefits']['title'], 'jn-section-title-md' )
		. jn_lb_list( $k['benefits']['items'], 'is-style-check jn-legacy-checklist' )
		. jn_lb_p( $k['benefits']['text'], 'jn-legacy-body', array( 'textColor' => 'muted-stone' ) );
	$out     .= jn_lb_section(
		jn_lb_media_row( $k['benefits']['image'], $benefits, true, 'jn-legacy-media--square' ),
		'jn-coaching-benefits',
		'Lợi ích đặc biệt (jannieedu.com)',
		'palette-color-6'
	) . "\n";

	$out .= jn_lb_cover(
		$k['closing']['image'],
		jn_lb_p( $k['closing']['eyebrow'], 'is-style-eyebrow jn-cover-eyebrow' )
			. jn_lb_p( $k['closing']['text'], 'jn-cover-text' )
			. jn_lb_button( $k['closing']['button'], JN_LB_BOOKING_HREF, 'accent-pill' ),
		'jn-coaching-closing'
	) . "\n";

	return $out . ( '' !== $tiers ? $tiers . "\n\n" : '' ) . ( '' !== $faq ? $faq . "\n\n" : '' ) . JN_LB_BOOKING_BLOCK;
}

/**
 * Cut one top-level section out of stored page content by its List View name (opening comment up to the
 * first column-0 `</section>` + `<!-- /wp:group -->`), as build-home-hero.php does.
 *
 * @param string $content Stored page content.
 * @param string $name    `metadata.name` of the section.
 * @return string Section markup, or '' when it is missing or ambiguous.
 */
function jn_lb_extract_section( string $content, string $name ): string {
	$open = '<!-- wp:group {"tagName":"section","metadata":{"name":"' . $name . '"}';
	$end  = "</section>\n<!-- /wp:group -->";
	if ( 1 !== substr_count( $content, $open ) ) {
		return '';
	}
	$start = (int) strpos( $content, $open );
	$stop  = strpos( $content, $end, $start );
	return false === $stop ? '' : substr( $content, $start, $stop + strlen( $end ) - $start );
}

/**
 * Copy a page's SEO title/description from JN_SEO_Meta::get_page_map() into its TSF custom fields.
 * build-step-8.php seeded those fields, and TSF custom fields win over the map, so a copy change in the
 * map alone never reaches the page (missed in Step 15, fixed in Step 16).
 *
 * @param int    $page_id Page ID.
 * @param string $slug    Page slug (key of the map).
 * @return bool True when a field changed.
 * @throws RuntimeException When the SEO module or the slug is missing.
 */
function jn_lb_sync_seo_meta( int $page_id, string $slug ): bool {
	$map = class_exists( 'JN_SEO_Meta' ) ? JN_SEO_Meta::get_page_map() : array();
	if ( empty( $map[ $slug ]['title'] ) || empty( $map[ $slug ]['description'] ) ) {
		throw new RuntimeException( "no SEO title/description for /{$slug}/ in JN_SEO_Meta::get_page_map()" );
	}
	$fields  = array(
		'_genesis_title'          => $map[ $slug ]['title'],
		'_genesis_description'    => $map[ $slug ]['description'],
		'_open_graph_title'       => $map[ $slug ]['title'],
		'_open_graph_description' => $map[ $slug ]['description'],
		'_twitter_title'          => $map[ $slug ]['title'],
		'_twitter_description'    => $map[ $slug ]['description'],
	);
	$changed = false;
	foreach ( $fields as $key => $value ) {
		if ( (string) get_post_meta( $page_id, $key, true ) !== $value ) {
			update_post_meta( $page_id, $key, $value );
			$changed = true;
		}
	}
	return $changed;
}
