<?php
/**
 * JN Step 14 — SEO / AEO re-audit verification suite.
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/test-step-14-seo-aeo.php
 *
 * Covers the Step 14 findings (docs/jn/seo-aeo/SEO_AEO_Audit_2026-10-30/):
 *   F14-01 The page-cache fast path (app/advanced-cache.php) served non-canonical URL variants
 *          (`/coaching`, `//coaching/`, `/COACHING/`) with 200 instead of letting WordPress 301 them;
 *          `//coaching/` even returned the homepage HTML.
 *   F14-02 Organization entity listed one name ("Jannie Nguyễn Academy") while pages use "Jannie Nguyen
 *          Academy" and "Học viện Jannie Nguyễn"; every name in use must be an `alternateName`.
 *   F14-03 Five indexable pages showed "[CẦN NỘI DUNG: …]" scaffold text to visitors and crawlers. Owner
 *          decision (2026-10-30): hide it from visitors, keep it visible to users who can edit posts.
 *
 * @package JN_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( 'production' === wp_get_environment_type() ) {
	echo "Refusing to run: this suite purges the page cache.\n";
	return;
}

$GLOBALS['jn_passed'] = 0;
$GLOBALS['jn_failed'] = 0;

/**
 * Assert a condition and print PASS/FAIL.
 *
 * @param bool   $condition Truthy condition.
 * @param string $label     Assertion description.
 * @return void
 */
function jn_step14_assert( bool $condition, string $label ): void {
	if ( $condition ) {
		++$GLOBALS['jn_passed'];
		echo "[PASS] {$label}\n";
	} else {
		++$GLOBALS['jn_failed'];
		echo "[FAIL] {$label}\n";
	}
}

/**
 * Perform an HTTP GET against the local WordPress web server (works from both host and wpcli container).
 *
 * @param string                $path    Path starting with `/`.
 * @param array<string, string> $headers Optional HTTP headers.
 * @return array<string, mixed>|WP_Error
 */
function jn_step14_http_get( string $path, array $headers = array() ) {
	$base = 'http://localhost:8080';
	if ( gethostbyname( 'wordpress' ) !== 'wordpress' ) {
		$base            = 'http://wordpress';
		$headers['Host'] = $headers['Host'] ?? 'localhost:8080'; // Keep a caller-supplied (tunnel) Host.
	}
	return wp_remote_get(
		$base . $path,
		array(
			'timeout'     => 15,
			'redirection' => 0,
			'headers'     => $headers,
		)
	);
}

/**
 * Return the canonical href of an HTML document ('' when absent).
 *
 * @param string $html HTML document.
 * @return string
 */
function jn_step14_canonical( string $html ): string {
	return preg_match( '#<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']#', $html, $m ) ? $m[1] : '';
}

/**
 * Whether a response is a permanent redirect whose Location path equals `$expected_path`.
 *
 * @param array<string, mixed>|WP_Error $res           Response.
 * @param string                        $expected_path Expected Location path.
 * @return bool
 */
function jn_step14_redirects_to( $res, string $expected_path ): bool {
	$location = (string) wp_remote_retrieve_header( $res, 'location' );
	return 301 === wp_remote_retrieve_response_code( $res )
		&& $expected_path === (string) wp_parse_url( $location, PHP_URL_PATH );
}

// ---------------------------------------------------------------------------------------------
// F14-01 — Page-cache fast path must only answer canonical URLs.
// ---------------------------------------------------------------------------------------------
// Warm the cache for the canonical URLs first, so a variant could wrongly be answered from it.
jn_step14_http_get( '/' );
jn_step14_http_get( '/coaching/' );
jn_step14_http_get( '/coaching/' );

$jn_warm = jn_step14_http_get( '/coaching/' );
jn_step14_assert(
	200 === wp_remote_retrieve_response_code( $jn_warm ) && 'HIT' === strtoupper( (string) wp_remote_retrieve_header( $jn_warm, 'x-cache-status' ) ),
	'Canonical /coaching/ is still served from the page-cache fast path (X-Cache-Status: HIT)'
);

$jn_home = jn_step14_http_get( '/' );
jn_step14_assert(
	200 === wp_remote_retrieve_response_code( $jn_home ) && 'HIT' === strtoupper( (string) wp_remote_retrieve_header( $jn_home, 'x-cache-status' ) ),
	'Canonical / is still served from the page-cache fast path (X-Cache-Status: HIT)'
);

jn_step14_assert(
	jn_step14_redirects_to( jn_step14_http_get( '/coaching' ), '/coaching/' ),
	'/coaching (no trailing slash) 301-redirects to /coaching/ instead of a cached 200'
);

jn_step14_assert(
	jn_step14_redirects_to( jn_step14_http_get( '//coaching/' ), '/coaching/' ),
	'//coaching/ 301-redirects to /coaching/ instead of serving the cached homepage'
);

$jn_upper      = jn_step14_http_get( '/COACHING/' );
$jn_upper_code = wp_remote_retrieve_response_code( $jn_upper );
jn_step14_assert(
	'HIT' !== strtoupper( (string) wp_remote_retrieve_header( $jn_upper, 'x-cache-status' ) )
	&& ( 301 === $jn_upper_code || ( 200 === $jn_upper_code && home_url( '/coaching/' ) === jn_step14_canonical( wp_remote_retrieve_body( $jn_upper ) ) ) ),
	'/COACHING/ is not answered by the cache fast path (WordPress handles it: 301 or canonical /coaching/)'
);

$jn_tunnel_variant = jn_step14_http_get(
	'/coaching',
	array(
		'Host'             => 'psi-audit-check.trycloudflare.com',
		'CF-Connecting-IP' => '203.0.113.10',
	)
);
jn_step14_assert(
	'HIT' !== strtoupper( (string) wp_remote_retrieve_header( $jn_tunnel_variant, 'x-cache-status' ) )
	&& 301 === wp_remote_retrieve_response_code( $jn_tunnel_variant ),
	'Tunnel host: /coaching (no trailing slash) is not answered by the tunnel cache fallback'
);

// ---------------------------------------------------------------------------------------------
// F14-02 — Organization names used on the site are all declared on the entity.
// ---------------------------------------------------------------------------------------------
$jn_graph = JN_SEO_Schema::build_graph( JN_SEO_Schema::current_context() );
$jn_org   = array();
foreach ( $jn_graph['@graph'] ?? array() as $jn_node ) {
	if ( 'Organization' === ( $jn_node['@type'] ?? '' ) ) {
		$jn_org = $jn_node;
	}
}
$jn_alt_names = (array) ( $jn_org['alternateName'] ?? array() );
jn_step14_assert(
	'Jannie Nguyễn Academy' === ( $jn_org['name'] ?? '' )
	&& in_array( 'Jannie Nguyen Academy', $jn_alt_names, true )
	&& in_array( 'Học viện Jannie Nguyễn', $jn_alt_names, true )
	&& in_array( 'Jannie Nguyễn — Beauty Blog & Mentorship', $jn_alt_names, true ),
	'Organization keeps its name and lists every in-use variant as alternateName (Jannie Nguyen Academy, Học viện Jannie Nguyễn, tagline)'
);

// ---------------------------------------------------------------------------------------------
// F14-03 — Scaffold placeholders are hidden from visitors, kept for editors.
// ---------------------------------------------------------------------------------------------
/**
 * Build a scaffold section exactly like scripts/build-step-3.php does (section > container > scaffold group).
 *
 * @param string $body Text of the last paragraph: the marker while pending, real copy once filled in.
 * @return string Block markup.
 */
function jn_step14_scaffold_section( string $body ): string {
	return '<!-- wp:group {"tagName":"section","className":"jn-section"} --><section class="wp-block-group jn-section">'
		. '<!-- wp:group {"className":"jn-container"} --><div class="wp-block-group jn-container">'
		. '<!-- wp:group {"className":"jn-scaffold-placeholder"} --><div class="wp-block-group jn-scaffold-placeholder">'
		. '<!-- wp:paragraph {"className":"is-style-eyebrow"} --><p class="is-style-eyebrow">Khung Nội Dung Chờ Cập Nhật</p><!-- /wp:paragraph -->'
		. '<!-- wp:heading --><h2 class="wp-block-heading">Mục Chờ Nội Dung</h2><!-- /wp:heading -->'
		. '<!-- wp:paragraph --><p>' . $body . '</p><!-- /wp:paragraph -->'
		. '</div><!-- /wp:group --></div><!-- /wp:group --></section><!-- /wp:group -->';
}

/**
 * Render block markup as a given user (0 = visitor), restoring the current user afterwards.
 *
 * @param string $markup  Block markup.
 * @param int    $user_id User ID.
 * @return string
 */
function jn_step14_render_as( string $markup, int $user_id ): string {
	$previous = get_current_user_id();
	wp_set_current_user( $user_id );
	$html = do_blocks( $markup );
	wp_set_current_user( $previous );
	return $html;
}

$jn_editor_ids = get_users(
	array(
		'role__in' => array( 'administrator', 'editor' ),
		'number'   => 1,
		'fields'   => 'ID',
	)
);
$jn_editor_id  = empty( $jn_editor_ids ) ? 0 : (int) $jn_editor_ids[0];
$jn_real_intro = '<!-- wp:paragraph --><p>Đoạn giới thiệu thật của trang.</p><!-- /wp:paragraph -->';
$jn_pending    = $jn_real_intro . jn_step14_scaffold_section( '[CẦN NỘI DUNG: fixture — UNK-00.]' );
$jn_filled     = jn_step14_scaffold_section( 'Nội dung thật đã được chủ dự án điền vào khung.' );

$jn_visitor_pending = jn_step14_render_as( $jn_pending, 0 );
jn_step14_assert(
	false !== strpos( $jn_visitor_pending, 'Đoạn giới thiệu thật' )
	&& false === strpos( $jn_visitor_pending, 'CẦN NỘI DUNG' )
	&& false === strpos( $jn_visitor_pending, 'Khung Nội Dung Chờ Cập Nhật' )
	&& false === strpos( $jn_visitor_pending, 'Mục Chờ Nội Dung' )
	&& false === strpos( $jn_visitor_pending, '<section' ),
	'Visitor: a pending scaffold section disappears entirely (eyebrow, heading, marker, empty wrappers); real copy stays'
);

$jn_visitor_filled = jn_step14_render_as( $jn_filled, 0 );
jn_step14_assert(
	false !== strpos( $jn_visitor_filled, 'Nội dung thật đã được chủ dự án điền vào khung.' )
	&& false !== strpos( $jn_visitor_filled, 'Mục Chờ Nội Dung' ),
	'Visitor: a scaffold group whose marker was replaced by real copy (class kept) is shown'
);

jn_step14_assert(
	$jn_editor_id > 0 && false !== strpos( jn_step14_render_as( $jn_pending, $jn_editor_id ), 'CẦN NỘI DUNG' ),
	'Editor: users who can edit pages still see the scaffold placeholder'
);

$jn_intro_html = jn_step14_render_as(
	'<!-- wp:group {"className":"jn-page-intro-inner"} --><div class="wp-block-group jn-page-intro-inner">'
	. '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Chính Sách Bảo Mật</h1><!-- /wp:heading -->'
	. '<!-- wp:paragraph {"className":"jn-page-intro-lead"} --><p class="jn-page-intro-lead">[CẦN NỘI DUNG: lời dẫn — UNK-09.]</p><!-- /wp:paragraph -->'
	. '</div><!-- /wp:group -->',
	0
);
jn_step14_assert(
	false !== strpos( $jn_intro_html, '<h1' ) && false === strpos( $jn_intro_html, 'CẦN NỘI DUNG' ),
	'Visitor: a marker lead paragraph is removed while its page-intro group and H1 stay'
);

jn_step14_assert(
	false !== strpos( jn_step14_render_as( '<!-- wp:paragraph --><p>Nội dung thật, có nhắc tới [CẦN NỘI DUNG] ở giữa câu.</p><!-- /wp:paragraph -->', 0 ), 'ở giữa câu' )
	&& false !== strpos( jn_step14_render_as( '<!-- wp:group --><div class="wp-block-group"></div><!-- /wp:group -->', 0 ), 'wp-block-group' ),
	'Visitor: a paragraph merely mentioning the marker mid-sentence and an empty spacer group (no scaffold) are kept'
);

$jn_fake_post    = new WP_Post(
	(object) array(
		'ID'           => 0,
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_excerpt' => '',
		'post_content' => $jn_pending,
		'filter'       => 'raw',
	)
);
$jn_prev_user    = get_current_user_id();
wp_set_current_user( 0 );
$jn_excerpt_text = (string) apply_filters( 'get_the_excerpt', '', $jn_fake_post );
wp_set_current_user( $jn_prev_user );
jn_step14_assert(
	false !== strpos( $jn_excerpt_text, 'Đoạn giới thiệu thật' )
	&& false === strpos( $jn_excerpt_text, 'Khung Nội Dung Chờ Cập Nhật' )
	&& false === strpos( $jn_excerpt_text, 'Mục Chờ Nội Dung' )
	&& false === strpos( $jn_excerpt_text, 'CẦN NỘI DUNG' ),
	'Visitor: auto-generated excerpts (search results) carry no scaffold eyebrow, heading or marker'
);

/**
 * Purge WP Super Cache so the latest markup is tested (same routine as test-step-10-seo.php).
 *
 * @return void
 */
function jn_step14_purge_page_cache(): void {
	if ( function_exists( 'wp_cache_clean_cache' ) ) {
		global $file_prefix;
		if ( ! empty( $file_prefix ) ) {
			wp_cache_clean_cache( $file_prefix, true );
		}
	}
	$cache_dir = WP_CONTENT_DIR . '/cache/supercache';
	if ( ! is_dir( $cache_dir ) ) {
		return;
	}
	$it = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $cache_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $it as $fs_item ) {
		if ( $fs_item->isDir() ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			@rmdir( $fs_item->getRealPath() );
		} elseif ( 'index.html' === $fs_item->getFilename() && $cache_dir !== $fs_item->getPath() ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			@unlink( $fs_item->getRealPath() );
		}
	}
}

jn_step14_purge_page_cache();
foreach ( array( '/my-story/', '/course/', '/client-success-story/', '/chinh-sach-bao-mat/', '/dieu-khoan-dich-vu/' ) as $jn_path ) {
	$jn_res  = jn_step14_http_get( $jn_path );
	$jn_body = wp_remote_retrieve_body( $jn_res );
	jn_step14_assert(
		200 === wp_remote_retrieve_response_code( $jn_res )
		&& false === strpos( $jn_body, 'CẦN NỘI DUNG' )
		&& 0 === preg_match( '#class="[^"]*\bjn-scaffold-placeholder\b#', $jn_body ) // The inlined theme CSS keeps the selector.
		&& false === strpos( $jn_body, 'Khung Nội Dung Chờ Cập Nhật' )
		&& 1 === preg_match_all( '#<h1[\s>]#', $jn_body ),
		"Visitor view of {$jn_path} is a 200 with no scaffold placeholder and exactly one H1"
	);
}

echo "\nRESULT: {$GLOBALS['jn_passed']} passed, {$GLOBALS['jn_failed']} failed\n";
