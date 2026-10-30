<?php
/**
 * Step 15 (ADR-022): read the old jannieedu.com copy (`jn-legacy-content.json`) and build the block markup of
 * /my-favorite-product/, /course/ and /client-success-story/ from it. Shared by the migration
 * (build-step-15-legacy-content.php) and its test, so the stored pages and the checks can never drift.
 *
 * Markup uses core blocks, the theme's block styles and `jn/video` only (no Custom HTML, no shortcodes).
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
 * /my-favorite-product/: intro, the old image + paragraph, the section title and the product collection
 * (category "Sản phẩm yêu thích", old order via menu_order) with image, title, description, price and
 * a "Mua Ngay" link to the product page, as on the old cards.
 *
 * @return string
 */
function jn_lb_products_page(): string {
	$p      = jn_lb_content()['products'];
	$intro  = jn_lb_section(
		jn_lb_columns(
			array(
				array( jn_lb_image( $p['intro']['image'], 'is-style-portrait-4-5 jn-legacy-media' ), '41.67%', 'jn-legacy-split__media' ),
				array( jn_lb_p( $p['intro']['body'], 'jn-legacy-body', array( 'textColor' => 'muted-stone' ) ), '58.33%', 'jn-legacy-split__text' ),
			),
			'jn-legacy-split'
		),
		'jn-legacy-intro',
		'Giới thiệu sản phẩm (jannieedu.com)'
	);
	$query  = array(
		'queryId'    => 1,
		'query'      => array(
			'perPage'                        => 9,
			'pages'                          => 0,
			'offset'                         => 0,
			'postType'                       => 'product',
			'order'                          => 'asc',
			'orderBy'                        => 'menu_order',
			'search'                         => '',
			'exclude'                        => array(),
			'inherit'                        => false,
			'taxQuery'                       => array( 'product_cat' => array( 41 ) ),
			'isProductCollectionBlock'       => true,
			'featured'                       => false,
			'woocommerceOnSale'              => false,
			'woocommerceStockStatus'         => array( 'instock', 'onbackorder' ),
			'woocommerceAttributes'          => array(),
			'woocommerceHandPickedProducts'  => array(),
			'filterable'                     => false,
		),
		'tagName'       => 'div',
		'displayLayout' => array(
			'type'           => 'flex',
			'columns'        => 3,
			'shrinkColumns'  => true,
		),
		'dimensions'    => array( 'widthType' => 'fill' ),
		'collection'    => 'woocommerce/product-collection/product-catalog',
		'hideControls'  => array( 'inherit', 'filterable' ),
		'className'     => 'jn-legacy-products',
	);
	$card   = jn_lb_block(
		'woocommerce/product-image',
		array(
			'showSaleBadge'            => false,
			'imageSizing'              => 'single',
			'isDescendentOfQueryLoop'  => true,
		),
		''
	)
		. jn_lb_block(
			'post-title',
			array(
				'textAlign'                  => 'left',
				'level'                      => 3,
				'isLink'                     => true,
				'fontSize'                   => 'headline-sm',
				'__woocommerceNamespace'     => 'woocommerce/product-collection/product-title',
			)
		)
		. jn_lb_block(
			'woocommerce/product-summary',
			array(
				'isDescendentOfQueryLoop' => true,
				'showDescriptionIfEmpty'  => true,
				'summaryLength'           => 0,
				'className'               => 'jn-legacy-product-summary',
			)
		)
		. jn_lb_block(
			'woocommerce/product-price',
			array(
				'isDescendentOfQueryLoop' => true,
				'textAlign'               => 'left',
			)
		)
		. jn_lb_block(
			'read-more',
			array(
				'content'   => $p['button'],
				'className' => 'jn-legacy-buy-now',
			)
		);
	$empty  = jn_lb_block( 'woocommerce/product-collection-no-results', array(), jn_lb_p( 'Danh mục đang được cập nhật.', '', array( 'align' => 'center', 'textColor' => 'muted-stone' ) ) );
	$grid   = jn_lb_block(
		'woocommerce/product-collection',
		$query,
		"<div class=\"wp-block-woocommerce-product-collection jn-legacy-products\">\n"
			. jn_lb_block( 'woocommerce/product-template', array(), $card )
			. $empty
			. '</div>'
	);
	$list   = jn_lb_section( jn_lb_section_header( $p['section_title'] ) . $grid, 'jn-legacy-product-list', 'Bộ sản phẩm yêu thích (jannieedu.com)', 'palette-color-6' );
	return jn_lb_page_intro( $p['page'] ) . "\n" . $intro . "\n" . $list;
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
 * headline, quote and name bound to post meta (Block Bindings). Same markup as patterns/video-testimonials.php.
 *
 * @return string
 */
function jn_lb_video_testimonials(): string {
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
	return jn_lb_section( $loop, 'jn-video-testimonials-section', 'Cảm nhận học viên (video)' );
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
