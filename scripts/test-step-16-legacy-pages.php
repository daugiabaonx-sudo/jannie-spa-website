<?php
/**
 * JN — Step 16 (ADR-023): old jannieedu.com content on /my-story/ and /coaching/.
 *
 * Covers the content source, the 12 imported originals, the stored pages (old sections in order, the
 * wireframe sections they replace, the coaching tiers + FAQ kept before the booking form), the hero image
 * as LCP (preload, eager, fetchpriority, matching sizes) and the live pages.
 *
 * Run via (after `build-step-16-legacy-pages.php`):
 *   docker compose run --rm wpcli wp eval-file /scripts/test-step-16-legacy-pages.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/jn-test-kit.php';
require_once __DIR__ . '/lib/jn-legacy-blocks.php';
require_once __DIR__ . '/lib/jn-legacy-test-kit.php';
jn_require_non_production( 'test-step-16-legacy-pages.php' );

echo "=================================================================\n";
echo "JN — STEP 16 MY STORY & COACHING (ADR-023)\n";
echo "=================================================================\n";

const JN_T16_MEDIA = array( 'coaching_hero', 'coaching_1', 'coaching_2', 'coaching_4', 'coaching_bg_3', 'coaching_bg_5', 'my_story_banner', 'my_story_1', 'my_story_prize', 'my_story_su_menh', 'my_story_value', 'my_story_mission' );

/**
 * Every visible string of a page from the content source.
 *
 * @param string $key `my_story` or `coaching`.
 * @return string[]
 */
function jn_t16_expected( string $key ): array {
	$out  = array();
	$walk = static function ( $node, string $k = '' ) use ( &$walk, &$out ): void {
		if ( is_array( $node ) ) {
			foreach ( $node as $child_key => $child ) {
				$walk( $child, is_string( $child_key ) ? $child_key : $k );
			}
		} elseif ( is_string( $node ) && 'image' !== $k && '' !== trim( $node ) ) {
			$out[] = $node;
		}
	};
	$walk( jn_lb_content()[ $key ] );
	return array_values( array_unique( array_map( 'jn_lt_text', $out ) ) );
}

$jn_c = jn_lb_content();

// -----------------------------------------------------------------------------
jn_t_section( 'Content source + media' );
jn_t_assert( 5 === count( $jn_c['my_story']['blocks'] ), 'My Story has the 5 old image/text blocks' );
jn_t_assert( 5 === count( $jn_c['coaching']['learn']['items'] ) && 4 === count( $jn_c['coaching']['benefits']['items'] ), 'Coaching keeps the 5 learning points and 4 benefits' );
foreach ( JN_T16_MEDIA as $jn_key ) {
	jn_t_assert( jn_lb_media( $jn_key )['id'] > 0, "image {$jn_key} imported" );
}

// -----------------------------------------------------------------------------
jn_t_section( 'Hero LCP (jn-performance)' );
jn_t_assert( function_exists( 'jn_legacy_hero_lcp_id' ), 'jn_legacy_hero_lcp_id() is defined' );

// -----------------------------------------------------------------------------
jn_t_section( 'Stored page content' );
$jn_story    = get_page_by_path( 'my-story', OBJECT, 'page' );
$jn_coaching = get_page_by_path( 'coaching', OBJECT, 'page' );
$jn_story_c  = $jn_story ? (string) $jn_story->post_content : '';
$jn_coach_c  = $jn_coaching ? (string) $jn_coaching->post_content : '';
foreach ( array( 'my-story' => $jn_story_c, 'coaching' => $jn_coach_c ) as $jn_slug => $jn_raw ) {
	jn_t_assert( 1 === substr_count( $jn_raw, '<h1' ) && 1 === preg_match( '#<h1[^>]*>[^<]*<em class="jn-accent-italic">[^<]+</em></h1>#u', $jn_raw ), "{$jn_slug}: one H1 ending with the italic accent (ADR-014)" );
	jn_t_assert( false === strpos( $jn_raw, '[CẦN NỘI DUNG' ) && false === strpos( $jn_raw, '<!-- wp:html' ), "{$jn_slug}: no scaffold, no Custom HTML" );
	jn_t_assert( false !== strpos( $jn_raw, 'jn-legacy-hero' ) && false === strpos( $jn_raw, 'Section 4 — Expert Intro' ), "{$jn_slug}: legacy hero replaces the wireframe expert section" );
	jn_t_assert( str_ends_with( rtrim( $jn_raw ), '<!-- wp:block {"ref":95} /-->' ), "{$jn_slug}: booking form stays last" );
}
jn_t_assert( false === strpos( $jn_coach_c, 'Section 1 — Hero Editorial' ) && false === strpos( $jn_coach_c, 'Section 2 — Value Pillars' ) && false === strpos( $jn_coach_c, '<!-- wp:block {"ref":94} /-->' ), 'coaching: wireframe hero, pillars and card testimonials replaced' );
jn_t_assert( 1 === substr_count( $jn_coach_c, 'Section 3 — Coaching Tiers' ) && false !== strpos( $jn_coach_c, 'id="coaching-tiers"' ), 'coaching: the 3 package tiers are kept (D1)' );
jn_t_assert( 1 === substr_count( $jn_coach_c, 'Section — FAQ (Câu Hỏi Thường Gặp)' ), 'coaching: the FAQ is kept (D1)' );
$jn_old_end = strpos( $jn_coach_c, 'jn-coaching-closing' );
jn_t_assert( false !== $jn_old_end && $jn_old_end < (int) strpos( $jn_coach_c, 'Section 3 — Coaching Tiers' ), 'coaching: the old content comes first, tiers + FAQ after it' );
jn_t_assert( 2 === substr_count( $jn_coach_c, '<!-- wp:cover' ), 'coaching: the two photo bands are core/cover blocks' );
jn_t_assert( false !== strpos( $jn_coach_c, '<!-- wp:jn/video' ) && false !== strpos( $jn_coach_c, 'Phản Hồi Từ Học Viên' ), 'coaching: video testimonials with the old heading' );
jn_t_assert( 0 === preg_match( '#href="(?!\#booking-form)[^"]*"#', jn_lb_coaching_page( '', '' ) . jn_lb_my_story_page() ), 'every built CTA targets the booking form (D6)' );

// -----------------------------------------------------------------------------
jn_t_section( 'Live pages' );
foreach ( array( 'my-story' => 'my_story', 'coaching' => 'coaching' ) as $jn_slug => $jn_key ) {
	$jn_res  = jn_lt_get( "/{$jn_slug}/" );
	$jn_body = $jn_res['body'];
	jn_t_assert( 200 === $jn_res['code'], "/{$jn_slug}/ returns 200" );
	$jn_missing = jn_lt_missing( jn_t16_expected( $jn_key ), jn_lt_text( $jn_body ) );
	jn_t_assert( array() === $jn_missing, "/{$jn_slug}/ shows every source string", implode( ' | ', array_map( static fn( $s ) => mb_substr( $s, 0, 60 ), $jn_missing ) ) );
	jn_t_assert( false === stripos( $jn_body, '<iframe' ), "/{$jn_slug}/ loads no frame before a click" );

	$jn_hero_key = 'coaching' === $jn_key ? 'coaching_hero' : 'my_story_banner';
	$jn_file     = (string) preg_replace( '/\.webp$/', '', basename( jn_lb_media( $jn_hero_key )['url'] ) );
	$jn_has_pre  = 1 === preg_match( '#<link rel="preload" as="image" href="[^"]*' . preg_quote( $jn_file, '#' ) . '[^"]*"[^>]*imagesizes="([^"]+)"#', $jn_body, $jn_pre );
	jn_t_assert( $jn_has_pre, "/{$jn_slug}/ preloads the legacy hero image" );
	jn_t_assert( 1 === substr_count( $jn_body, 'rel="preload" as="image"' ), "/{$jn_slug}/ preloads exactly one image" );
	$jn_img = preg_match( '#<img[^>]*wp-image-' . jn_lb_media( $jn_hero_key )['id'] . '\b[^>]*>#', $jn_body, $jn_tag ) ? $jn_tag[0] : '';
	jn_t_assert(
		false !== strpos( $jn_img, 'loading="eager"' ) && false !== strpos( $jn_img, 'fetchpriority="high"' ) && false !== strpos( $jn_img, 'sizes="' . ( $jn_pre[1] ?? '-' ) . '"' ),
		"/{$jn_slug}/ hero image is eager, high priority, and its sizes match the preload",
		$jn_img
	);
}
// The TSF custom fields win over JN_SEO_Meta's map, so check what the pages really print (Steps 15 + 16).
$jn_seo_map = JN_SEO_Meta::get_page_map();
foreach ( array( 'my-story', 'coaching', 'course', 'client-success-story', 'my-favorite-product' ) as $jn_slug ) {
	$jn_head = jn_lt_get( "/{$jn_slug}/" )['body'];
	$jn_desc = preg_match( '#<meta name="description" content="([^"]*)"#', $jn_head, $jn_m ) ? html_entity_decode( $jn_m[1], ENT_QUOTES, 'UTF-8' ) : '';
	jn_t_assert( $jn_desc === $jn_seo_map[ $jn_slug ]['description'], "/{$jn_slug}/ prints the updated meta description", $jn_desc );
}
$jn_goi = jn_lt_get( '/coaching/?goi=30-phut' );
// Options carry the package label as value and the slug in data-package-key.
jn_t_assert( 1 === preg_match( '#<option[^>]*data-package-key="30-phut"[^>]*selected#s', $jn_goi['body'] ), '/coaching/?goi=30-phut still preselects the package' );

jn_t_finish();
