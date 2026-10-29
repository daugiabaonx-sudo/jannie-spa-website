<?php
/**
 * JN Step 13 — Apply the page header copy to an existing database: fill the four intro leads that
 * still show `[CẦN NỘI DUNG…]` and convert the shop page header to the standard page intro.
 * build-step-3.php / setup-step-4-woocommerce.php seed the same markup on a fresh install.
 *
 * Idempotent: each change is an exact string swap from any known earlier value, skipped once applied,
 * and reported as a failure (nothing written) when neither an earlier nor the new markup is found, i.e.
 * the page was edited by hand.
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/build-step-13-page-headers.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Rewrites page content: refuse to run against production.
require_once __DIR__ . '/lib/jn-env-guard.php';
jn_require_non_production( basename( __FILE__ ) );
require_once __DIR__ . '/lib/jn-page-copy.php';

echo "=== JN STEP 13 — PAGE HEADERS ===\n";

const JN_T13_SHOP_HEADER_OLD = '<!-- wp:group {"className":"jn-container jn-section-header-centered"} -->
<div class="wp-block-group jn-container jn-section-header-centered"><!-- wp:paragraph {"align":"center","className":"is-style-eyebrow jn-eyebrow-wide"} -->
<p class="has-text-align-center is-style-eyebrow jn-eyebrow-wide">Danh Mục Tuyển Chọn</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":1,"className":"jn-page-intro-title"} -->
<h1 class="wp-block-heading has-text-align-center jn-page-intro-title">Sản Phẩm Yêu Thích <em class="jn-accent-italic">Của Jannie</em></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"jn-section-header-centered__desc","textColor":"muted-stone"} -->
<p class="has-text-align-center jn-section-header-centered__desc has-muted-stone-color has-text-color">';

const JN_T13_SHOP_HEADER_NEW = '<!-- wp:group {"className":"jn-container jn-page-intro-inner"} -->
<div class="wp-block-group jn-container jn-page-intro-inner"><!-- wp:paragraph {"className":"is-style-eyebrow-dot"} -->
<p class="is-style-eyebrow-dot">Danh Mục Tuyển Chọn</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"className":"jn-page-intro-title"} -->
<h1 class="wp-block-heading jn-page-intro-title">Sản Phẩm Yêu Thích <em class="jn-accent-italic">Của Jannie</em></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"jn-page-intro-lead","textColor":"muted-stone","fontSize":"body-lg"} -->
<p class="jn-page-intro-lead has-muted-stone-color has-text-color has-body-lg-font-size">';

$jn_leads = jn_page_intro_leads();

/*
 * `from` lists every value a page may still hold: the Step 3 placeholder and, for two pages, the first
 * Step 13 draft that review rejected (my-story implied an unsupported chronology; client-success-story
 * promised a verification policy the unverified testimonials below it contradict, UNK-03 / BL-02).
 */
$jn_changes = array(
	'home'                 => array(
		'from' => array( 'Sứ mệnh của Jannie là tạo ra sự cân bằng và khám phá vẻ đẹp đích thực trong mỗi người. [CẦN NỘI DUNG: Đoạn giới thiệu tổng quan hệ sinh thái Học viện, Cố vấn 1:1 và Đào tạo của Jannie Nguyễn do chủ dự án cung cấp.]' ),
		'to'   => $jn_leads['home'],
	),
	'my-story'             => array(
		'from' => array(
			'[CẦN NỘI DUNG: Lời mở đầu tự sự của chuyên gia Jannie Nguyễn về cơ duyên đến với ngành thẩm mỹ và triết lý The Balance.]',
			'Hơn 20 năm theo nghề, từ danh hiệu Nghệ nhân Bàn Tay Vàng đến phương pháp cân bằng tự nhiên The Balance, Jannie Nguyễn luôn giữ một niềm tin: vẻ đẹp bền vững bắt đầu từ sự cân bằng giữa thể chất, tâm trí và cảm xúc.',
		),
		'to'   => $jn_leads['my-story'],
	),
	'course'               => array(
		'from' => array( '[CẦN NỘI DUNG: Danh mục các khóa học chính thức, lịch khai giảng, giáo trình chi tiết và học phí từ Jannie Nguyen Academy — UNK-08.]' ),
		'to'   => $jn_leads['course'],
	),
	'client-success-story' => array(
		'from' => array(
			'[CẦN NỘI DUNG: Tổng hợp các hồ sơ case-study thực tế sau khi đã xác minh và có sự đồng thuận bằng văn bản của khách hàng — UNK-03.]',
			$jn_leads['client-success-story'] . ' Mỗi hồ sơ chỉ được công bố khi đã xác minh và có sự đồng thuận bằng văn bản của khách hàng.',
		),
		'to'   => $jn_leads['client-success-story'],
	),
	'my-favorite-product'  => array(
		'from' => array( JN_T13_SHOP_HEADER_OLD ),
		'to'   => JN_T13_SHOP_HEADER_NEW,
	),
);

$jn_failed = 0;

foreach ( $jn_changes as $jn_slug => $jn_change ) {
	$jn_page = get_page_by_path( $jn_slug, OBJECT, 'page' );
	if ( ! $jn_page ) {
		echo "[FAIL] /{$jn_slug}/: page not found\n";
		++$jn_failed;
		continue;
	}

	$jn_content = $jn_page->post_content;
	$jn_new     = $jn_change['to'];
	$jn_old     = '';
	// Earlier values are checked first: a later draft may start with the final copy.
	foreach ( $jn_change['from'] as $jn_candidate ) {
		if ( false !== strpos( $jn_content, $jn_candidate ) ) {
			$jn_old = $jn_candidate;
			break;
		}
	}

	if ( '' === $jn_old ) {
		if ( false !== strpos( $jn_content, $jn_new ) ) {
			echo "[SKIP] /{$jn_slug}/: already applied\n";
		} else {
			echo "[FAIL] /{$jn_slug}/: expected markup not found (edited by hand?) — nothing written\n";
			++$jn_failed;
		}
		continue;
	}

	$jn_result = wp_update_post(
		array(
			'ID'           => (int) $jn_page->ID,
			'post_content' => wp_slash( str_replace( $jn_old, $jn_new, $jn_content ) ),
		),
		true
	);
	if ( is_wp_error( $jn_result ) ) {
		echo "[FAIL] /{$jn_slug}/: " . $jn_result->get_error_message() . "\n";
		++$jn_failed;
		continue;
	}
	echo "[OK] /{$jn_slug}/ (ID={$jn_page->ID}) updated\n";
}

// Every page inlines the theme CSS, so the whole page cache is stale, not only the edited pages.
jannie_ensure_front_css_bundle();
echo "[OK] CSS bundle rebuilt\n";
if ( function_exists( 'wp_cache_clear_cache' ) ) {
	wp_cache_clear_cache();
	echo "[OK] WP Super Cache cleared\n";
} else {
	echo "[WARN] wp_cache_clear_cache() unavailable — clear the page cache by hand\n";
}

if ( $jn_failed > 0 ) {
	echo "RESULT: {$jn_failed} change(s) failed\n";
	exit( 1 );
}
echo "RESULT: done\n";
