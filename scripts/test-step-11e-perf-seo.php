<?php
/**
 * JN Step 11e — Code-review fixes: performance, SEO & theme (H8, H9, M14, M15, M18, M19, M23, M24, M25).
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/test-step-11e-perf-seo.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/jn-test-kit.php';
jn_require_non_production( 'test-step-11e-perf-seo.php' );

echo "=================================================================\n";
echo "JN STEP 11e — PERFORMANCE, SEO & THEME\n";
echo "=================================================================\n";

global $wpdb;

$jn_assistant_id = jn_t_create_user( JN_Booking_Roles::ROLE_ASSISTANT, jn_t_email( 'assistant' ) );
wp_set_current_user( $jn_assistant_id );

// -----------------------------------------------------------------------------
// H8 / M14 — no silent 500 cap; no per-record queries
// -----------------------------------------------------------------------------
jn_t_section( 'H8 / M14 — customer directory and CSV at scale' );
$jn_bulk_count = 510;
$jn_token      = 'jn11-' . $GLOBALS['jn_t_run'] . '-bulk';
wp_defer_term_counting( true );
for ( $jn_i = 0; $jn_i < $jn_bulk_count; $jn_i++ ) {
	jn_t_create_booking(
		array(
			'_jn_customer_email' => sprintf( '%s-%03d@example.test', $jn_token, $jn_i ),
			'_jn_customer_name'  => 'Khách Hàng Loạt ' . $jn_i,
		)
	);
}
wp_defer_term_counting( false );

$jn_found = JN_Customer_Service::query_customers( array( 'search' => $jn_token ) );
jn_t_assert( $jn_bulk_count === count( $jn_found ), 'The customer directory lists every customer (no 500 cap)', count( $jn_found ) . ' found' );

$jn_q0 = $wpdb->num_queries;
JN_Customer_Service::get_customer_map();
$jn_queries = $wpdb->num_queries - $jn_q0;
jn_t_assert( $jn_queries < $jn_bulk_count, 'Building the directory does not run queries per customer', $jn_queries . ' queries' );

$jn_csv  = JN_CSV_Exporter::build_bookings_csv_string( array( 'search' => $jn_token ) );
$jn_rows = count( array_filter( explode( "\n", trim( $jn_csv ) ), static fn( $l ) => false !== strpos( $l, $jn_token ) ) );
jn_t_assert( $jn_bulk_count === $jn_rows, 'The bookings CSV exports every matching booking (no 500 cap)', $jn_rows . ' rows' );

// -----------------------------------------------------------------------------
// M15 — a failed immediate confirmation does not orphan the created booking
// -----------------------------------------------------------------------------
jn_t_section( 'M15 — staff booking with failed confirmation' );
$jn_res = JN_Customer_Service::create_staff_booking(
	array(
		'full_name'       => 'Khách Gọi Điện',
		'phone'           => '0905123123',
		'email'           => jn_t_email( 'walkin' ),
		'package_code'    => '60-phut',
		'format'          => 'online',
		'confirm_now'     => 'yes',
		'confirmed_start' => '2031-05-01 10:00:00',
		'meeting_link'    => '',
	),
	$jn_assistant_id
);
if ( is_array( $jn_res ) ) {
	jn_t_track_post( (int) $jn_res['booking_id'] );
}
jn_t_assert( is_array( $jn_res ) && ! empty( $jn_res['warning'] ), 'The created booking is returned with a warning instead of an error (no duplicate on retry)' );
jn_t_assert( is_array( $jn_res ) && JN_Booking_CPT::STATUS_PENDING === get_post_status( (int) $jn_res['booking_id'] ), 'The booking stays pending until it can be confirmed' );

// -----------------------------------------------------------------------------
// M18 — attachment-ID matching is exact
// -----------------------------------------------------------------------------
jn_t_section( 'M18 — hero/expert image detection' );
$jn_map     = get_option( 'jn_step3_media_map', array() );
$jn_hero_id = isset( $jn_map['hero_portrait']['id'] ) ? (int) $jn_map['hero_portrait']['id'] : 86;
$jn_other   = $jn_hero_id * 10;
$jn_img     = jn_optimize_content_img_tag( '<img src="x.jpg" class="wp-image-' . $jn_other . '" loading="lazy">', 'the_content', $jn_other );
jn_t_assert( false === strpos( $jn_img, 'fetchpriority' ), "wp-image-{$jn_other} is not mistaken for the hero (wp-image-{$jn_hero_id})" );
$jn_img = jn_optimize_content_img_tag( '<img src="x.jpg" class="wp-image-' . $jn_hero_id . '">', 'the_content', 0 );
jn_t_assert( false !== strpos( $jn_img, 'fetchpriority="high"' ), 'The real hero image still gets fetchpriority="high"' );

// -----------------------------------------------------------------------------
// M19 — robots meta and sitemap share one product indexability rule
// -----------------------------------------------------------------------------
jn_t_section( 'M19 — product indexability' );
$jn_make_product = static function ( string $name, array $meta = array(), string $visibility = 'visible' ): int {
	$product = new WC_Product_Simple();
	$product->set_name( $name );
	$product->set_status( 'publish' );
	$product->set_regular_price( '100000' );
	$product->set_catalog_visibility( $visibility );
	foreach ( $meta as $key => $value ) {
		$product->update_meta_data( $key, $value );
	}
	return jn_t_track_post( (int) $product->save() );
};
$jn_plain    = $jn_make_product( 'JN11 Sản phẩm thường' );
$jn_package  = $jn_make_product( 'JN11 Gói coaching', array( '_jn_package_key' => 'jn11-test', '_jn_bookable' => 'yes' ) );
$jn_hidden   = $jn_make_product( 'JN11 Sản phẩm ẩn', array(), 'hidden' );
jn_t_assert( JN_SEO_Sitemap::is_product_indexable( $jn_plain ), 'A published, visible shop product is indexable' );
jn_t_assert( ! JN_SEO_Sitemap::is_product_indexable( $jn_package ), 'A coaching package product is not indexable (order-pay only)' );
jn_t_assert( ! JN_SEO_Sitemap::is_product_indexable( $jn_hidden ), 'A catalog-hidden product is not indexable' );
delete_transient( JN_SEO_Sitemap::CACHE_KEY );
$jn_sitemap_urls = wp_list_pluck( JN_SEO_Sitemap::get_urls(), 'loc' );
jn_t_assert( in_array( get_permalink( $jn_plain ), $jn_sitemap_urls, true ) && ! in_array( get_permalink( $jn_package ), $jn_sitemap_urls, true ), 'The sitemap uses the same rule (package excluded, product included)' );
$jn_robots_src = (string) file_get_contents( WPMU_PLUGIN_DIR . '/jn-performance/class-jn-seo-robots.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
jn_t_assert( false !== strpos( $jn_robots_src, 'JN_SEO_Sitemap::is_product_indexable' ), 'Robots meta uses the shared product rule' );
delete_transient( JN_SEO_Sitemap::CACHE_KEY );

// -----------------------------------------------------------------------------
// H9 — database hygiene runs daily, not on every request
// -----------------------------------------------------------------------------
jn_t_section( 'H9 — database hygiene schedule' );
jn_t_assert( false === has_action( 'init', 'jn_maintain_database_hygiene' ), 'Database hygiene is no longer hooked to every init' );
jn_t_assert( false !== wp_next_scheduled( 'jn_daily_db_hygiene' ), 'Database hygiene is scheduled as a daily cron event' );
wp_set_option_autoload( 'cron', true );
jn_maintain_database_hygiene();
$jn_cron_autoload = (string) $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", 'cron' ) );
jn_t_assert( in_array( $jn_cron_autoload, array( 'yes', 'on', 'auto-on' ), true ), 'Hygiene no longer flips the cron option autoload (core re-saves it every run)', $jn_cron_autoload );

// -----------------------------------------------------------------------------
// M23 — theme CSS bundle is rebuilt atomically from source content
// -----------------------------------------------------------------------------
jn_t_section( 'M23 — CSS bundle' );
$jn_bundle = get_stylesheet_directory() . '/assets/css/jannie-bundle.min.css';
$jn_backup = is_file( $jn_bundle ) ? (string) file_get_contents( $jn_bundle ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
file_put_contents( $jn_bundle, '/*jn-bundle:stale*/:root{}' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
touch( $jn_bundle, time() + 3600 );
jannie_ensure_front_css_bundle();
clearstatcache();
$jn_rebuilt = (string) file_get_contents( $jn_bundle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
jn_t_assert( 0 === strpos( $jn_rebuilt, '/*jn-bundle:' ) && false === strpos( $jn_rebuilt, 'stale' ) && strlen( $jn_rebuilt ) > 1000, 'A stale bundle is detected by source content (not mtime) and rebuilt' );
jn_t_assert( array() === glob( $jn_bundle . '.tmp*' ), 'The rebuild leaves no temporary files (written then renamed)' );

// -----------------------------------------------------------------------------
// M24 / M25 — theme assets & patterns
// -----------------------------------------------------------------------------
jn_t_section( 'M24 / M25 — theme' );
$jn_wc_css = (string) file_get_contents( get_stylesheet_directory() . '/assets/css/woocommerce.css' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
jn_t_assert( false === strpos( $jn_wc_css, 'page-id-' ), 'woocommerce.css does not depend on a database page ID' );

foreach ( array( 'hero-editorial.php', 'expert-intro.php' ) as $jn_pattern ) {
	ob_start();
	include get_stylesheet_directory() . '/patterns/' . $jn_pattern;
	$jn_markup = (string) ob_get_clean();
	preg_match( '#<img[^>]*class="wp-image-[^>]*>#', $jn_markup, $jn_img_match );
	$jn_tag = $jn_img_match[0] ?? '';
	jn_t_assert( '' !== $jn_tag && ! preg_match( '/\s(loading|fetchpriority|decoding|width|height)=/', $jn_tag ), "{$jn_pattern} saves the image exactly like core/image (attributes are added at render time)", $jn_tag );
}

if ( '' !== $jn_backup ) {
	jannie_ensure_front_css_bundle();
}

jn_t_finish();
