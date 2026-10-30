<?php
/**
 * JN — Step 15 (ADR-022): old jannieedu.com content on /my-favorite-product/, /course/ and
 * /client-success-story/.
 *
 * Covers the testimonial fields (headline, video, rating 0 = no stars), the `jn/video` facade block, the
 * CSP frame source, the six WooCommerce products, the three legacy testimonials, the stored page content
 * and the live pages. Every visible string is compared with `lib/jn-legacy-content.json`, the single
 * source the migration also uses; that file is checked against the Wayback evidence by
 * `verify-step-15-legacy-pages.mjs`.
 *
 * Run via (after `build-step-15-legacy-content.php`):
 *   docker compose run --rm wpcli wp eval-file /scripts/test-step-15-legacy-content.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/jn-test-kit.php';
require_once __DIR__ . '/lib/jn-legacy-blocks.php';
require_once __DIR__ . '/lib/jn-legacy-test-kit.php';
jn_require_non_production( 'test-step-15-legacy-content.php' );

echo "=================================================================\n";
echo "JN — STEP 15 LEGACY CONTENT (ADR-022)\n";
echo "=================================================================\n";

const JN_T15_PAGES = array(
	'my-favorite-product'  => 12,
	'course'               => 10,
	'client-success-story' => 11,
);

/**
 * Every string a page must show, from the shared content source.
 *
 * @param string $slug Page slug.
 * @return string[]
 */
function jn_t15_expected_strings( string $slug ): array {
	$c   = jn_lb_content();
	$out = array();
	if ( 'my-favorite-product' === $slug ) {
		$p   = $c['products'];
		$out = array_merge( array_values( $p['page'] ), array( $p['intro']['body'], $p['section_title'] ) );
		foreach ( $p['items'] as $item ) {
			$out[] = $item['name'];
			$out[] = $item['description'];
		}
	} elseif ( 'course' === $slug ) {
		$k   = $c['courses'];
		$out = array_merge( array_values( $k['page'] ), array( $k['intro']['eyebrow'], $k['intro']['title'], $k['button'] ), array_values( $k['closing'] ) );
		foreach ( $k['categories'] as $cat ) {
			$out[] = $cat['title'];
			foreach ( $cat['clusters'] as $cluster ) {
				foreach ( $cluster['courses'] as $course ) {
					$out[] = $course['title'];
					$out[] = $course['description'];
				}
			}
		}
	} else {
		$k   = $c['clients'];
		$out = array_merge( array_values( $k['page'] ), array( $k['intro_body'], $k['values']['title'], $k['process']['title'], $k['group_button'] ) );
		foreach ( $k['about']['blocks'] as $b ) {
			array_push( $out, $b['title'], $b['text'] );
		}
		foreach ( $k['groups'] as $g ) {
			array_push( $out, $g['title'], $g['text'] );
			foreach ( $g['items'] as $i ) {
				$out = array_merge( $out, array( $i['title'] ), isset( $i['text'] ) ? array( $i['text'] ) : $i['bullets'] );
			}
		}
		foreach ( array_merge( $k['values']['items'], $k['process']['steps'] ) as $i ) {
			array_push( $out, $i['title'], $i['text'] );
		}
		$out = array_merge( $out, array( $k['closing']['title'], $k['closing']['subtitle'], $k['closing']['text'], $k['closing']['button'] ) );
		foreach ( $c['testimonials'] as $t ) {
			array_push( $out, $t['name'], $t['headline'], $t['quote'] );
		}
	}
	return array_values( array_unique( array_map( 'jn_lt_text', $out ) ) );
}

/**
 * Render a block outside a page, optionally with a Query Loop post context.
 *
 * @param string               $name    Block name.
 * @param array<string, mixed> $attrs   Attributes.
 * @param int                  $post_id Context post ID (0 = none).
 * @return string
 */
function jn_t15_render( string $name, array $attrs = array(), int $post_id = 0 ): string {
	$block = new WP_Block(
		array(
			'blockName' => $name,
			'attrs'     => $attrs,
		),
		$post_id > 0 ? array(
			'postId'   => $post_id,
			'postType' => 'jn_testimonial',
		) : array()
	);
	return (string) $block->render();
}

$jn_c = jn_lb_content();

// -----------------------------------------------------------------------------
jn_t_section( 'Content source' );
$jn_course_count = 0;
foreach ( $jn_c['courses']['categories'] as $jn_cat ) {
	foreach ( $jn_cat['clusters'] as $jn_cluster ) {
		$jn_course_count += count( $jn_cluster['courses'] );
	}
}
jn_t_assert( 6 === count( $jn_c['products']['items'] ), '6 products in the source' );
jn_t_assert( 26 === $jn_course_count, '26 courses in the source', (string) $jn_course_count );
jn_t_assert( 3 === count( $jn_c['testimonials'] ), '3 testimonials in the source' );
jn_t_assert( 6 === count( $jn_c['clients']['groups'] ), '6 course groups on the client page' );
foreach ( $jn_c['products']['items'] as $jn_item ) {
	jn_t_assert( jn_lb_media( $jn_item['image'] )['id'] > 0, "image imported for {$jn_item['slug']}" );
}

// -----------------------------------------------------------------------------
jn_t_section( 'Testimonial fields' );
$jn_keys = get_registered_meta_keys( 'post', 'jn_testimonial' );
foreach ( array( 'jn_headline', 'jn_video_url', 'jn_video_poster_id' ) as $jn_key ) {
	jn_t_assert( isset( $jn_keys[ $jn_key ] ), "meta {$jn_key} registered" );
}
jn_t_assert( 0 === JN_Testimonials::sanitize_rating( 0 ), 'rating 0 is kept (no stars)' );
jn_t_assert( 0 === JN_Testimonials::sanitize_rating( -3 ), 'negative rating becomes 0' );
jn_t_assert( 5 === JN_Testimonials::sanitize_rating( 9 ), 'rating above 5 is clamped to 5' );
jn_t_assert( 0 === (int) ( $jn_keys['jn_rating']['default'] ?? -1 ), 'jn_rating defaults to 0 (never invent stars)' );
$jn_video_cases = array(
	'https://www.youtube.com/watch?v=eDk34ql8Qb0'          => 'eDk34ql8Qb0',
	'https://youtu.be/nWqff-L-mQg'                         => 'nWqff-L-mQg',
	'https://www.youtube.com/embed/Qw-_mpLp-QQ?x=1'        => 'Qw-_mpLp-QQ',
	'https://evil.example/watch?v=eDk34ql8Qb0'             => '',
	'javascript:alert(1)//youtube.com/watch?v=aaaaaaaaaaa' => '',
	'https://www.youtube.com/watch?v=short'                => '',
);
foreach ( $jn_video_cases as $jn_in => $jn_id ) {
	jn_t_assert( JN_Testimonials::youtube_id( $jn_in ) === $jn_id, "youtube_id( {$jn_in} ) = '{$jn_id}'" );
}
jn_t_assert(
	'https://www.youtube.com/watch?v=eDk34ql8Qb0' === JN_Testimonials::sanitize_video_url( 'https://youtu.be/eDk34ql8Qb0?si=x' ),
	'video URL stored in canonical watch form'
);
jn_t_assert( '' === JN_Testimonials::sanitize_video_url( 'https://vimeo.com/1' ), 'non-YouTube video URL rejected' );

// -----------------------------------------------------------------------------
jn_t_section( 'Block jn/rating with rating 0' );
$jn_t_ids = array();
foreach ( $jn_c['testimonials'] as $jn_t ) {
	$jn_found                  = get_posts(
		array(
			'post_type'   => 'jn_testimonial',
			'post_status' => 'any',
			'title'       => $jn_t['name'],
			'numberposts' => 1,
			'fields'      => 'ids',
		)
	);
	$jn_t_ids[ $jn_t['name'] ] = (int) ( $jn_found[0] ?? 0 );
}
$jn_first_t = (int) reset( $jn_t_ids );
jn_t_assert( $jn_first_t > 0 && '' === trim( jn_t15_render( 'jn/rating', array(), $jn_first_t ) ), 'jn/rating renders nothing for a testimonial with rating 0' );
jn_t_assert( false !== strpos( jn_t15_render( 'jn/rating', array(), 91 ), 'Đánh giá 5 trên 5 sao' ), 'stored 5-star ratings still render' );

// -----------------------------------------------------------------------------
jn_t_section( 'Block jn/video' );
jn_t_assert( WP_Block_Type_Registry::get_instance()->is_registered( 'jn/video' ), 'jn/video registered' );
$jn_video = jn_t15_render( 'jn/video', array(), $jn_first_t );
jn_t_assert( false !== strpos( $jn_video, 'href="https://www.youtube.com/watch?v=eDk34ql8Qb0"' ), 'facade links to the YouTube watch page (works without JS)' );
jn_t_assert( false !== strpos( $jn_video, 'data-jn-video-id="eDk34ql8Qb0"' ), 'facade carries the video id for view.js' );
jn_t_assert( false === stripos( $jn_video, '<iframe' ), 'no iframe before the visitor clicks' );
jn_t_assert( 1 === preg_match( '#<img[^>]+jn-legacy-video-loan-nguyen[^>]+#', $jn_video, $jn_m ) && false !== strpos( $jn_m[0], 'loading="lazy"' ) && false !== strpos( $jn_m[0], 'alt=""' ), 'self-hosted lazy poster, decorative alt' );
jn_t_assert( false !== strpos( $jn_video, 'Xem video cảm nhận của Loan Nguyễn' ), 'accessible link name names the person' );
jn_t_assert( '' === trim( jn_t15_render( 'jn/video', array( 'url' => 'https://evil.example/x' ) ) ), 'invalid URL renders nothing' );
$jn_view = (string) file_get_contents( WP_PLUGIN_DIR . '/jn-core/blocks/video/view.js' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
jn_t_assert( false !== strpos( $jn_view, 'https://www.youtube-nocookie.com/embed/' ), 'view.js loads the privacy-enhanced embed' );
jn_t_assert( false === strpos( $jn_view, 'jQuery' ) && strlen( $jn_view ) < 3072, 'view.js is vanilla and < 3 KB' );

// -----------------------------------------------------------------------------
jn_t_section( 'CSP frame-src' );
foreach ( array( WPMU_PLUGIN_DIR . '/jn-hardening/class-jn-hardening-html-headers.php', ABSPATH . '.htaccess' ) as $jn_file ) {
	$jn_src = is_file( $jn_file ) ? (string) file_get_contents( $jn_file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	jn_t_assert( false !== strpos( $jn_src, 'frame-src https://www.youtube-nocookie.com' ), 'frame-src youtube-nocookie in ' . basename( $jn_file ) );
}

// -----------------------------------------------------------------------------
jn_t_section( 'WooCommerce products' );
$jn_permalinks = (array) get_option( 'woocommerce_permalinks', array() );
jn_t_assert( 'san-pham' === ( $jn_permalinks['product_base'] ?? '' ), 'product base is /san-pham/ (old URLs resolve again)' );
foreach ( $jn_c['products']['items'] as $jn_order => $jn_item ) {
	$jn_post    = get_page_by_path( $jn_item['slug'], OBJECT, 'product' );
	$jn_product = $jn_post ? wc_get_product( $jn_post->ID ) : null;
	if ( ! $jn_product instanceof WC_Product ) {
		jn_t_assert( false, "product {$jn_item['slug']} exists" );
		continue;
	}
	$jn_price = null === $jn_item['price'] ? '' : (string) $jn_item['price'];
	jn_t_assert(
		'publish' === $jn_product->get_status() && $jn_item['name'] === $jn_product->get_name() && $jn_price === $jn_product->get_regular_price(),
		"{$jn_item['slug']}: published, name and price match the source",
		$jn_product->get_status() . ' / ' . $jn_product->get_name() . ' / ' . $jn_product->get_regular_price()
	);
	jn_t_assert( in_array( 41, $jn_product->get_category_ids(), true ) && $jn_order + 1 === (int) $jn_product->get_menu_order(), "{$jn_item['slug']}: favourite category, old order" );
	jn_t_assert( $jn_item['description'] === $jn_product->get_short_description(), "{$jn_item['slug']}: short description verbatim" );
	jn_t_assert( (int) jn_lb_media( $jn_item['image'] )['id'] === (int) $jn_product->get_image_id(), "{$jn_item['slug']}: image" );
	jn_t_assert( JN_SEO_Sitemap::is_product_indexable( $jn_product ), "{$jn_item['slug']}: indexable" );
}
// check-launch-blockers.php deletes every non-canonical product (it removed these six once, before this guard).
$jn_blockers = (string) file_get_contents( __DIR__ . '/check-launch-blockers.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
jn_t_assert( false !== strpos( $jn_blockers, '$is_legacy' ) && false !== strpos( $jn_blockers, "'_jn_legacy_source'" ), 'launch-blocker cleanup keeps the legacy products' );
$jn_soap_post = get_page_by_path( 'xa-bong-thao-duoc', OBJECT, 'product' );
$jn_soap      = $jn_soap_post ? wc_get_product( $jn_soap_post->ID ) : null;
jn_t_assert( $jn_soap instanceof WC_Product && false !== strpos( $jn_soap->get_price_html(), 'Liên hệ' ), 'product without a price shows "Liên hệ"' );

// -----------------------------------------------------------------------------
jn_t_section( 'Testimonials' );
foreach ( $jn_c['testimonials'] as $jn_order => $jn_t ) {
	$jn_id = $jn_t_ids[ $jn_t['name'] ];
	jn_t_assert(
		$jn_id > 0 && 'publish' === get_post_status( $jn_id ) && $jn_order + 1 === (int) get_post_field( 'menu_order', $jn_id ),
		"{$jn_t['name']}: published in old order"
	);
	jn_t_assert(
		$jn_t['quote'] === get_post_meta( $jn_id, 'jn_quote', true ) && $jn_t['headline'] === get_post_meta( $jn_id, 'jn_headline', true )
			&& $jn_t['video'] === get_post_meta( $jn_id, 'jn_video_url', true ) && '0' === (string) get_post_meta( $jn_id, 'jn_rating', true ),
		"{$jn_t['name']}: quote, headline, video verbatim; rating 0"
	);
	jn_t_assert( false !== strpos( (string) get_post_meta( $jn_id, 'jn_admin_note', true ), 'UNK-03' ), "{$jn_t['name']}: admin note records the pending consent" );
}
foreach ( array( 91, 92, 93 ) as $jn_old ) {
	jn_t_assert( 'draft' === get_post_status( $jn_old ), "wireframe testimonial {$jn_old} moved to draft (kept)" );
}

// -----------------------------------------------------------------------------
jn_t_section( 'Stored page content' );
foreach ( JN_T15_PAGES as $jn_slug => $jn_page_id ) {
	$jn_raw = (string) get_post_field( 'post_content', $jn_page_id );
	jn_t_assert( false === strpos( $jn_raw, '[CẦN NỘI DUNG' ), "{$jn_slug}: no scaffold left" );
	jn_t_assert( false === strpos( $jn_raw, '<!-- wp:html' ), "{$jn_slug}: no Custom HTML block" );
	jn_t_assert( 1 === preg_match( '#<h1[^>]*>[^<]*<em class="jn-accent-italic">[^<]+</em></h1>#u', $jn_raw ), "{$jn_slug}: H1 ends with the italic accent (ADR-014)" );
	jn_t_assert( 1 === substr_count( $jn_raw, '<h1' ), "{$jn_slug}: exactly one H1" );
}
$jn_course_raw = (string) get_post_field( 'post_content', 10 );
$jn_client_raw = (string) get_post_field( 'post_content', 11 );
jn_t_assert( false === strpos( $jn_course_raw, 'Lợi Ích Của Chương Trình Coaching' ), 'course: coaching pillars removed' );
jn_t_assert( false !== strpos( $jn_course_raw, '<!-- wp:block {"ref":95} /-->' ), 'course: booking section kept' );
jn_t_assert( false === strpos( $jn_client_raw, '<!-- wp:block {"ref":94} /-->' ), 'client: wireframe testimonials section replaced' );
jn_t_assert( false !== strpos( $jn_client_raw, '<!-- wp:jn/video' ), 'client: video testimonials use jn/video' );
$jn_pattern = (string) file_get_contents( get_stylesheet_directory() . '/patterns/video-testimonials.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
jn_t_assert( false !== strpos( $jn_pattern, trim( jn_lb_video_testimonials() ) ), 'patterns/video-testimonials.php matches the builder markup' );

// -----------------------------------------------------------------------------
jn_t_section( 'Live pages' );
foreach ( array_keys( JN_T15_PAGES ) as $jn_slug ) {
	$jn_res = jn_lt_get( "/{$jn_slug}/" );
	jn_t_assert( 200 === $jn_res['code'], "/{$jn_slug}/ returns 200", (string) $jn_res['code'] );
	$jn_missing = jn_lt_missing( jn_t15_expected_strings( $jn_slug ), jn_lt_text( $jn_res['body'] ) );
	jn_t_assert( array() === $jn_missing, "/{$jn_slug}/ shows every source string", implode( ' | ', array_map( static fn( $s ) => mb_substr( $s, 0, 60 ), $jn_missing ) ) );
	jn_t_assert( false === stripos( $jn_res['body'], '<iframe' ), "/{$jn_slug}/ embeds no frame before a click" );
}
$jn_product_page = jn_lt_get( '/san-pham/egf-cleansing-gel/' );
jn_t_assert( 200 === $jn_product_page['code'] && false !== strpos( jn_lt_text( $jn_product_page['body'] ), '496.000' ), '/san-pham/egf-cleansing-gel/ shows the archived price' );
$jn_home = jn_lt_text( jn_lt_get( '/' )['body'] );
jn_t_assert( false !== mb_strpos( $jn_home, 'Loan Nguyễn' ) && false === mb_strpos( $jn_home, 'Minh Thảo' ), 'home testimonials now show the legacy students' );

jn_t_finish();
