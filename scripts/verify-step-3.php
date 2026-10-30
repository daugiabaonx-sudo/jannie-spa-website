<?php
/**
 * Step 3 Acceptance & Block Verification Script
 *
 * Run via: ./scripts/wp.sh eval-file /scripts/verify-step-3.php
 *
 * @package Jannie_Nguyen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

/**
 * Recursively collect block names from parsed blocks.
 *
 * @param array<int, array<string, mixed>> $blocks Parsed block tree.
 * @param array<string, int>               $counts Reference map of block_name => count.
 * @return void
 */
function jn_collect_block_names( array $blocks, array &$counts ): void {
	foreach ( $blocks as $block ) {
		$name = $block['blockName'] ?? null;
		if ( ! empty( $name ) && is_string( $name ) ) {
			$counts[ $name ] = ( $counts[ $name ] ?? 0 ) + 1;
		}
		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			jn_collect_block_names( $block['innerBlocks'], $counts );
		}
	}
}

echo "=== 1. BLOCK AUDIT ACROSS ALL PAGES & SYNCED PATTERNS ===\n";

$pages = get_posts(
	array(
		'post_type'      => array( 'page', 'wp_block' ),
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'post_type',
		'order'          => 'ASC',
	)
);

$total_html_blocks       = 0;
$disallowed_block_types  = array();
$global_block_type_stats = array();

foreach ( $pages as $post_obj ) {
	$parsed = parse_blocks( $post_obj->post_content );
	$counts = array();
	jn_collect_block_names( $parsed, $counts );

	$html_count = $counts['core/html'] ?? 0;
	$total_html_blocks += $html_count;

	foreach ( $counts as $block_name => $cnt ) {
		$global_block_type_stats[ $block_name ] = ( $global_block_type_stats[ $block_name ] ?? 0 ) + $cnt;
		if ( 0 !== strpos( $block_name, 'core/' ) && 0 !== strpos( $block_name, 'jn/' ) ) {
			$disallowed_block_types[ $block_name ] = true;
		}
	}

	printf(
		"  - [%s] %-24s (ID=%d): %d blocks | core/html=%d\n",
		$post_obj->post_type,
		$post_obj->post_name,
		$post_obj->ID,
		array_sum( $counts ),
		$html_count
	);
}

echo "\nBlock type breakdown across all pages + synced patterns:\n";
ksort( $global_block_type_stats );
foreach ( $global_block_type_stats as $bname => $bcnt ) {
	printf( "  * %-24s : %d\n", $bname, $bcnt );
}

if ( 0 !== $total_html_blocks ) {
	fwrite( STDERR, "[FAIL] Found {$total_html_blocks} core/html blocks!\n" );
	exit( 1 );
}
if ( ! empty( $disallowed_block_types ) ) {
	fwrite( STDERR, '[FAIL] Found disallowed block types: ' . implode( ', ', array_keys( $disallowed_block_types ) ) . "\n" );
	exit( 1 );
}
echo "[PASS] 0 core/html blocks and 100% core/* or jn/* blocks across all pages and synced patterns.\n\n";

echo "=== 2. THEME PATTERN REGISTRY CHECK ===\n";
$registry     = WP_Block_Patterns_Registry::get_instance();
$all_patterns = $registry->get_all_registered();
$jn_patterns  = array();

foreach ( $all_patterns as $pat ) {
	$cats = $pat['categories'] ?? array();
	if ( in_array( 'jannie-nguyen', $cats, true ) ) {
		$jn_patterns[] = $pat['name'];
		$pat_counts    = array();
		jn_collect_block_names( parse_blocks( $pat['content'] ), $pat_counts );
		if ( isset( $pat_counts['core/html'] ) ) {
			fwrite( STDERR, "[FAIL] Pattern {$pat['name']} contains core/html!\n" );
			exit( 1 );
		}
	}
}
sort( $jn_patterns );
echo 'Registered patterns in category "jannie-nguyen" (' . count( $jn_patterns ) . "):\n";
foreach ( $jn_patterns as $pname ) {
	echo "  - {$pname}\n";
}

echo "\n=== 3. TESTIMONIALS CPT & BLOCK BINDINGS CHECK ===\n";
$pt_obj = get_post_type_object( 'jn_testimonial' );
if ( ! $pt_obj || $pt_obj->publicly_queryable ) {
	fwrite( STDERR, "[FAIL] jn_testimonial must exist and have publicly_queryable=false.\n" );
	exit( 1 );
}
echo "[PASS] jn_testimonial registered with publicly_queryable=false.\n";

$testimonials = get_posts(
	array(
		'post_type'      => 'jn_testimonial',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	)
);
echo 'Seeded testimonials count: ' . count( $testimonials ) . "\n";
foreach ( $testimonials as $t ) {
	$note   = get_post_meta( $t->ID, 'jn_admin_note', true );
	$rating = get_post_meta( $t->ID, 'jn_rating', true );
	$role   = get_post_meta( $t->ID, 'jn_role_display', true );
	printf(
		"  - ID=%d | %s | %s | rating=%s | note='%s'\n",
		$t->ID,
		$t->post_title,
		$role,
		$rating,
		$note
	);
}

echo "\n=== 4. COACHING PAGE RENDERED HTML & DEFECT FIXES CHECK ===\n";
$coaching_page = get_page_by_path( 'coaching' );
if ( ! $coaching_page ) {
	fwrite( STDERR, "[FAIL] Coaching page not found.\n" );
	exit( 1 );
}
$rendered_coaching = apply_filters( 'the_content', $coaching_page->post_content );

$checks = array(
	'Hero watermark removed (ADR-016)'       => strpos( $rendered_coaching, 'jn-hero-watermark' ) === false,
	'Hero image loading="eager"'             => strpos( $rendered_coaching, 'loading="eager"' ) !== false,
	'Hero image fetchpriority="high"'        => strpos( $rendered_coaching, 'fetchpriority="high"' ) !== false,
	'Anchor #coaching-tiers present'         => strpos( $rendered_coaching, 'id="coaching-tiers"' ) !== false,
	'Anchor #booking-form present'           => strpos( $rendered_coaching, 'id="booking-form"' ) !== false,
	'Tier 1 link ?goi=30-phut#booking-form'  => strpos( $rendered_coaching, '?goi=30-phut#booking-form' ) !== false,
	'Tier 2 link ?goi=60-phut#booking-form'  => strpos( $rendered_coaching, '?goi=60-phut#booking-form' ) !== false,
	'Tier 3 link ?goi=mentorship-3-thang'    => strpos( $rendered_coaching, '?goi=mentorship-3-thang#booking-form' ) !== false,
	'Booking steps rendered as <ol>'         => strpos( $rendered_coaching, '<ol class="wp-block-list jn-booking-steps">' ) !== false,
	// Step 15 (ADR-022): the published testimonials are the jannieedu.com students; stars only when given.
	'Testimonial quotes rendered via meta'   => array() === array_filter( $testimonials, static fn( $t ) => false === strpos( $rendered_coaching, wptexturize( (string) get_post_meta( $t->ID, 'jn_quote', true ) ) ) ),
	'jn/rating renders only given ratings'   => array() !== array_filter( $testimonials, static fn( $t ) => (int) get_post_meta( $t->ID, 'jn_rating', true ) > 0 )
		? false !== strpos( $rendered_coaching, 'class="wp-block-jn-rating jn-rating"' )
		: false === strpos( $rendered_coaching, 'jn-rating__star' ),
	'No href="#" placeholder links'          => strpos( $rendered_coaching, 'href="#"' ) === false,
);

foreach ( $checks as $label => $passed ) {
	printf( "  [%s] %s\n", $passed ? 'PASS' : 'FAIL', $label );
	if ( ! $passed ) {
		exit( 1 );
	}
}

echo "\n=== 5. SERVER-SIDE ?goi= PRESELECTION CHECK ===\n";
$goi_tests = array(
	''                   => 'Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)',
	'30-phut'            => 'Gói Tư Vấn Cá Nhân 1:1 (30 Phút)',
	'60-phut'            => 'Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)',
	'mentorship-3-thang' => 'Gói Master Mentorship (3 Tháng)',
);
foreach ( $goi_tests as $param => $expected_option ) {
	if ( '' === $param ) {
		unset( $_GET['goi'] );
	} else {
		$_GET['goi'] = $param;
	}
	$html    = do_blocks( '<!-- wp:jn/booking-form /-->' );
	$pattern = '/<option[^>]*value="' . preg_quote( esc_attr( $expected_option ), '/' ) . '"[^>]*selected/';
	$ok      = (bool) preg_match( $pattern, $html );
	printf( "  [%s] ?goi=%-20s -> selects '%s'\n", $ok ? 'PASS' : 'FAIL', $param ?: '(default)', $expected_option );
	if ( ! $ok ) {
		exit( 1 );
	}
}
unset( $_GET['goi'] );

echo "\n[ALL CHECKS PASSED]\n";
