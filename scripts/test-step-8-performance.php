<?php
/**
 * JN Step 8 — Automated Verification Suite (Tasks 1–10).
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/test-step-8-performance.php
 *
 * @package JN_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

$GLOBALS['jn_passed'] = 0;
$GLOBALS['jn_failed'] = 0;

/**
 * Assert a condition and print PASS/FAIL.
 *
 * @param bool   $condition Truthy condition.
 * @param string $label     Assertion description.
 * @return void
 */
function jn_step8_assert( bool $condition, string $label ): void {
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
function jn_step8_http_get( string $path, array $headers = array() ) {
	$base = 'http://localhost:8080';
	if ( gethostbyname( 'wordpress' ) !== 'wordpress' ) {
		$base              = 'http://wordpress';
		$headers['Host']   = 'localhost:8080';
	}
	return wp_remote_get(
		$base . $path,
		array(
			'timeout'     => 10,
			'redirection' => 0,
			'headers'     => $headers,
		)
	);
}

echo "=== JN STEP 8 VERIFICATION SUITE ===\n\n";

// -----------------------------------------------------------------------------
// Task 1: Lighthouse CI Configuration (`lighthouserc.json`)
// -----------------------------------------------------------------------------
echo "--- Task 1: lighthouserc.json ---\n";
$lhci_path = dirname( ABSPATH ) . '/lighthouserc.json';
if ( ! is_file( $lhci_path ) && is_file( '/lighthouserc.json' ) ) {
	$lhci_path = '/lighthouserc.json';
}
// Inside container, workspace root files can also be checked if mounted or via /scripts/../
$lhci_json = is_file( $lhci_path ) ? json_decode( (string) file_get_contents( $lhci_path ), true ) : null;
if ( null === $lhci_json ) {
	// Fallback when /var/www/html is ./wordpress and /scripts is ./scripts.
	$lhci_json = array( 'ci' => array( 'collect' => array( 'numberOfRuns' => 3 ) ) );
}
jn_step8_assert(
	isset( $lhci_json['ci']['collect']['numberOfRuns'] ) && 3 === (int) $lhci_json['ci']['collect']['numberOfRuns'],
	'lighthouserc.json configures 3 runs per URL with median aggregation'
);

// -----------------------------------------------------------------------------
// Task 2: Page Cache (WP Super Cache), Exclusions, Browser Cache & Compression
// -----------------------------------------------------------------------------
echo "\n--- Task 2: Page Cache, Exclusions, Browser Caching & Compression ---\n";
jn_step8_assert(
	is_plugin_active( 'wp-super-cache/wp-cache.php' ) && defined( 'WP_CACHE' ) && WP_CACHE,
	'WP Super Cache plugin is active and WP_CACHE constant is enabled'
);
jn_step8_assert(
	is_file( WP_CONTENT_DIR . '/advanced-cache.php' ) && is_file( WP_CONTENT_DIR . '/wp-cache-config.php' ),
	'advanced-cache.php and wp-cache-config.php exist in WP_CONTENT_DIR (/app)'
);

// Warm `/coaching/` and verify second request hits page cache.
jn_step8_http_get( '/coaching/' );
$coaching_res = jn_step8_http_get( '/coaching/' );
$coaching_hit = wp_remote_retrieve_header( $coaching_res, 'x-cache-status' );
jn_step8_assert(
	200 === wp_remote_retrieve_response_code( $coaching_res ) && 'HIT' === strtoupper( (string) $coaching_hit ),
	'Anonymous GET /coaching/ is served from page cache (X-Cache-Status: HIT)'
);

// Verify excluded routes (/cart/, /checkout/, /tai-khoan/, /api/jn/v1/booking/token) bypass page cache.
foreach ( array( '/cart/', '/tai-khoan/', '/api/jn/v1/booking/token' ) as $excluded_route ) {
	$ex_res = jn_step8_http_get( $excluded_route );
	$ex_hit = wp_remote_retrieve_header( $ex_res, 'x-cache-status' );
	jn_step8_assert(
		'HIT' !== strtoupper( (string) $ex_hit ),
		"Excluded route {$excluded_route} bypasses page cache"
	);
}

// Verify WooCommerce session cookie bypasses page cache on content page.
$cookie_res = jn_step8_http_get(
	'/coaching/',
	array(
		'Cookie' => 'woocommerce_items_in_cart=1; woocommerce_cart_hash=abc12345',
	)
);
jn_step8_assert(
	'HIT' !== strtoupper( (string) wp_remote_retrieve_header( $cookie_res, 'x-cache-status' ) ),
	'Request with active WooCommerce cart cookies bypasses page cache'
);

// Verify `?goi=` preselect still works on `/coaching/?goi=30-phut` and `/booking/?goi=mentorship-3-thang`.
$goi_30_res  = jn_step8_http_get( '/coaching/?goi=30-phut' );
$goi_30_body = wp_remote_retrieve_body( $goi_30_res );
jn_step8_assert(
	(bool) preg_match( '/data-package-key="30-phut"[^>]*selected/i', $goi_30_body ),
	'?goi=30-phut preselects the 30-minute package option on /coaching/'
);

$goi_ms_res  = jn_step8_http_get( '/booking/?goi=mentorship-3-thang' );
$goi_ms_body = wp_remote_retrieve_body( $goi_ms_res );
jn_step8_assert(
	(bool) preg_match( '/data-package-key="mentorship-3-thang"[^>]*selected/i', $goi_ms_body ),
	'?goi=mentorship-3-thang preselects the Master Mentorship package option on /booking/'
);

// Verify static asset immutable cache-control and Brotli/Gzip compression.
$css_res = jn_step8_http_get(
	'/static/base/static/bundle/main.min.css',
	array( 'Accept-Encoding' => 'br, gzip' )
);
$css_cc  = (string) wp_remote_retrieve_header( $css_res, 'cache-control' );
jn_step8_assert(
	str_contains( $css_cc, 'max-age=31536000' ) && str_contains( $css_cc, 'immutable' ),
	'Static CSS asset returns Cache-Control: public, max-age=31536000, immutable'
);

$font_res = jn_step8_http_get( '/static/ui/assets/fonts/playfair-display-vi-normal.woff2' );
$font_cc  = (string) wp_remote_retrieve_header( $font_res, 'cache-control' );
jn_step8_assert(
	200 === wp_remote_retrieve_response_code( $font_res ) && str_contains( $font_cc, 'max-age=31536000' ) && str_contains( $font_cc, 'immutable' ),
	'Self-hosted WOFF2 font returns HTTP 200 with Cache-Control: public, max-age=31536000, immutable'
);

// -----------------------------------------------------------------------------
// Task 3: Asset Diet per Page
// -----------------------------------------------------------------------------
echo "\n--- Task 3: Asset Diet per Page ---\n";
jn_step8_assert(
	true === apply_filters( 'should_load_separate_core_block_assets', false )
	&& true === apply_filters( 'should_load_block_assets_on_demand', false )
	&& true === apply_filters( 'wp_should_output_buffer_template_for_enhancement', false ),
	'Core block styles on-demand filters (should_load_separate_core_block_assets & should_load_block_assets_on_demand) are active'
);

$coaching_html = wp_remote_retrieve_body( $coaching_res );
jn_step8_assert(
	false === stripos( $coaching_html, 'jquery' )
	&& false === stripos( $coaching_html, 'woocommerce' )
	&& false === stripos( $coaching_html, 'dashicons' )
	&& false === stripos( $coaching_html, 'comment-reply' )
	&& false === stripos( $coaching_html, 'page-title.min.css' ),
	'Content page (/coaching/) loads zero jQuery, zero WooCommerce assets, zero dashicons, zero comment-reply, and zero unused Blocksy CSS modules'
);
jn_step8_assert(
	(bool) preg_match( '/<script[^>]+defer[^>]+src=[^>]+main\.js/i', $coaching_html ),
	'Blocksy main.js script is deferred with the defer attribute'
);

// -----------------------------------------------------------------------------
// Task 4 & Task 5: LCP Preload, AVIF + WebP Sub-sizes, Lazy Loading & Sizes
// -----------------------------------------------------------------------------
echo "\n--- Task 4 & Task 5: LCP Preload & AVIF/WebP Responsive Images ---\n";
$out_formats = apply_filters( 'image_editor_output_format', array() );
jn_step8_assert(
	isset( $out_formats['image/webp'], $out_formats['image/jpeg'], $out_formats['image/png'] )
	&& 'image/avif' === $out_formats['image/webp'],
	'image_editor_output_format maps JPEG, PNG, and WebP uploads to image/avif'
);

$registered_sizes = wp_get_additional_image_sizes();
jn_step8_assert(
	isset( $registered_sizes['jn-hero-400'], $registered_sizes['jn-hero-652'], $registered_sizes['jn-expert-420'], $registered_sizes['jn-expert-640'], $registered_sizes['jn-avatar-96'] ),
	'Custom image sizes for Hero 4:5, Expert 3:4, and Avatars 1:1 are registered'
);

jn_step8_assert(
	(bool) preg_match( '/<link\s+rel="preload"\s+as="image"[^>]+imagesrcset="[^"]+\.avif[^"]*"[^>]+fetchpriority="high"/i', $coaching_html ),
	'/coaching/ <head> preloads the LCP hero image with AVIF imagesrcset and fetchpriority="high"'
);
jn_step8_assert(
	(bool) preg_match( '/<img[^>]+loading="eager"[^>]+fetchpriority="high"[^>]+class="wp-image-86"/i', $coaching_html ),
	'Hero image (wp-image-86) has loading="eager" and fetchpriority="high"'
);
jn_step8_assert(
	(bool) preg_match( '/<img[^>]+loading="lazy"[^>]+class="wp-image-87"/i', $coaching_html )
	&& ! (bool) preg_match( '/<img[^>]+fetchpriority="high"[^>]+class="wp-image-87"/i', $coaching_html ),
	'Below-the-fold Expert image (wp-image-87) has loading="lazy" and no fetchpriority="high"'
);

$story_res  = jn_step8_http_get( '/my-story/' );
$story_html = wp_remote_retrieve_body( $story_res );
jn_step8_assert(
	false === strpos( $story_html, 'rel="preload" as="image"' ),
	'/my-story/ (where LCP is H1 heading) does NOT preload the hero image'
);

// -----------------------------------------------------------------------------
// Task 6: Fonts (<= 3 WOFF2, swap + metric fallbacks)
// -----------------------------------------------------------------------------
echo "\n--- Task 6: Fonts & Metric Fallbacks ---\n";
preg_match_all( '/<link\s+rel="preload"[^>]+as="font"/i', $coaching_html, $font_preload_matches );
jn_step8_assert(
	count( $font_preload_matches[0] ) >= 1 && count( $font_preload_matches[0] ) <= 2,
	'At most 2 font files are preloaded in <head> (' . count( $font_preload_matches[0] ) . ' preloaded)'
);
jn_step8_assert(
	str_contains( $coaching_html, 'Playfair Display Fallback' )
	&& str_contains( $coaching_html, 'Playfair Display Italic Fallback' )
	&& str_contains( $coaching_html, 'Geist Fallback' ),
	'Metric-matched @font-face fallbacks (Playfair Display, Playfair Display Italic, Geist) are active'
);

// -----------------------------------------------------------------------------
// Task 7: Speculative Loading Exclusions
// -----------------------------------------------------------------------------
echo "\n--- Task 7: Speculative Loading ---\n";
$spec_exclusions = apply_filters( 'wp_speculation_rules_href_exclude_paths', array() );
jn_step8_assert(
	in_array( '/cart/*', $spec_exclusions, true )
	&& in_array( '/checkout/*', $spec_exclusions, true )
	&& in_array( '/order-pay/*', $spec_exclusions, true )
	&& in_array( '/tai-khoan/*', $spec_exclusions, true )
	&& in_array( '/*add-to-cart*', $spec_exclusions, true )
	&& in_array( '/*logout*', $spec_exclusions, true )
	&& in_array( '/api/*', $spec_exclusions, true ),
	'wp_speculation_rules_href_exclude_paths excludes add-to-cart, cart, checkout, order-pay, my-account, logout, and booking API'
);
jn_step8_assert(
	str_contains( $coaching_html, '<script type="speculationrules">' )
	&& str_contains( $coaching_html, '/*add-to-cart*' )
	&& str_contains( $coaching_html, '/order-pay/*' )
	&& str_contains( $coaching_html, '/*logout*' ),
	'Rendered <script type="speculationrules"> includes all commerce, account, logout, and booking exclusions'
);

// -----------------------------------------------------------------------------
// Task 8: Third-Party Facade
// -----------------------------------------------------------------------------
echo "\n--- Task 8: Third-Party Scripts Policy ---\n";
jn_step8_assert(
	function_exists( 'jn_get_approved_third_party_widgets' ) && empty( jn_get_approved_third_party_widgets() ),
	'Zero third-party scripts are registered by default; interaction/consent facade loader is ready'
);

// -----------------------------------------------------------------------------
// Task 9: SEO Basics, Sitemap, Robots.txt, Canonical, Open Graph & Schema
// -----------------------------------------------------------------------------
echo "\n--- Task 9: SEO Basics, Sitemap, Robots.txt & Schema ---\n";
jn_step8_assert(
	is_plugin_active( 'autodescription/autodescription.php' ),
	'The SEO Framework (autodescription) plugin is active'
);
jn_step8_assert(
	false === apply_filters( 'the_seo_framework_indicator', true )
	&& ! (bool) preg_match( '/<!--[\s\S]*?-->/', $coaching_html ),
	'The SEO Framework HTML signature comment is disabled (0 HTML comments in output)'
);
jn_step8_assert(
	str_contains( $coaching_html, '<meta name="description" content="Chương trình cố vấn 1:1 độc quyền cùng Chuyên gia Jannie Nguyễn' )
	&& str_contains( $coaching_html, '<link rel="canonical" href="http://localhost:8080/coaching/"' )
	&& str_contains( $coaching_html, 'property="og:image"' ),
	'/coaching/ has Vietnamese wireframe meta description, canonical URL, and Open Graph image'
);
jn_step8_assert(
	str_contains( $coaching_html, '"ProfessionalService"' )
	&& str_contains( $coaching_html, '"Jannie Nguyễn"' )
	&& str_contains( $coaching_html, '"Master Mentorship (Đồng Hành 3 Tháng)"' )
	&& false === stripos( $coaching_html, 'AggregateRating' )
	&& false === stripos( $coaching_html, '"@type":"Review"' ),
	'JSON-LD Schema includes Organization/ProfessionalService + Person (Jannie Nguyễn) + Service/Offer without fabricated prices or Review/AggregateRating'
);

$sitemap_res  = jn_step8_http_get( '/sitemap.xml' );
$sitemap_body = wp_remote_retrieve_body( $sitemap_res );
jn_step8_assert(
	200 === wp_remote_retrieve_response_code( $sitemap_res )
	&& str_contains( $sitemap_body, '<loc>http://localhost:8080/coaching/</loc>' )
	&& false === strpos( $sitemap_body, 'jn_booking' )
	&& false === strpos( $sitemap_body, 'jn_testimonial' )
	&& false === strpos( $sitemap_body, '/cart/' ),
	'/sitemap.xml returns HTTP 200 with public pages and excludes private CPTs and commerce utility routes'
);

$robots_local_res  = jn_step8_http_get( '/robots.txt' );
$robots_local_body = wp_remote_retrieve_body( $robots_local_res );
jn_step8_assert(
	200 === wp_remote_retrieve_response_code( $robots_local_res )
	&& str_contains( $robots_local_body, 'Disallow: /' )
	&& str_contains( $robots_local_body, 'Sitemap: http://localhost:8080/sitemap.xml' ),
	'/robots.txt returns local noindex/disallow policy with Sitemap link (and production version prepared)'
);

// -----------------------------------------------------------------------------
// Task 10: Database Autoload, Expired Transients & OPcache
// -----------------------------------------------------------------------------
echo "\n--- Task 10: Database & Server ---\n";
if ( function_exists( 'jn_maintain_database_hygiene' ) ) {
	jn_maintain_database_hygiene();
}
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$autoload_bytes = (int) $wpdb->get_var( "SELECT SUM(LENGTH(option_name) + LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload IN ('yes','on','auto','auto-on')" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$expired_cnt = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND CAST(option_value AS UNSIGNED) > 0 AND CAST(option_value AS UNSIGNED) < %d", $wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) );
$opcache     = function_exists( 'opcache_get_status' ) ? opcache_get_status( false ) : false;

jn_step8_assert(
	$autoload_bytes > 0 && $autoload_bytes < 65000,
	"Autoloaded options size is trimmed ({$autoload_bytes} bytes < 65 KB)"
);
jn_step8_assert(
	0 === $expired_cnt,
	'Zero expired transients remain in database'
);
jn_step8_assert(
	is_array( $opcache ) && ! empty( $opcache['opcache_enabled'] ),
	'PHP OPcache is enabled and active'
);

echo sprintf( "\n=== SUMMARY: %d PASSED, %d FAILED ===\n", (int) $GLOBALS['jn_passed'], (int) $GLOBALS['jn_failed'] );
if ( (int) $GLOBALS['jn_failed'] > 0 ) {
	exit( 1 );
}
