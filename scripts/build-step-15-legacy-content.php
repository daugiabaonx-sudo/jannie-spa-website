<?php
/**
 * JN — Step 15 (ADR-022): put the old jannieedu.com content on /my-favorite-product/, /course/ and
 * /client-success-story/.
 *
 * - WooCommerce product base `san-pham` so the old product URLs resolve again
 * - six products (old slug, name, short description, image, May 2024 price; none for Xà bông thảo dược)
 * - three video testimonials (rating 0 = no stars); the three wireframe testimonials (91–93) go to draft
 * - the content of pages 10, 11 and 12, built by lib/jn-legacy-blocks.php from lib/jn-legacy-content.json
 *
 * Idempotent: items that already match are skipped. Page content goes through wp_update_post(), whose
 * revision is the rollback point, and is read back to make sure nothing (kses) changed the markup.
 * Needs the 22 Step 15 images imported first (import-legacy-media.php).
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/build-step-15-legacy-content.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Writes products, testimonials and page content: refuse to run against production.
require_once __DIR__ . '/lib/jn-env-guard.php';
jn_require_non_production( basename( __FILE__ ) );
require_once __DIR__ . '/lib/jn-legacy-blocks.php';

echo "=== JN — STEP 15 LEGACY CONTENT (ADR-022) ===\n";

const JN_S15_FAVORITE_CAT   = 41;
const JN_S15_PRODUCT_BASE   = 'san-pham';
const JN_S15_OLD_TESTIMONIALS = array( 91, 92, 93 );
// Page slug => builder. IDs are resolved by slug, never assumed.
const JN_S15_PAGES          = array(
	'my-favorite-product'  => 'jn_lb_products_page',
	'course'               => 'jn_lb_course_page',
	'client-success-story' => 'jn_lb_clients_page',
);
const JN_S15_TESTIMONIAL_NOTE = 'Nguồn: jannieedu.com/client-success-story (Wayback 2025-02-07) + YouTube kênh "khoa nguyễn". UNK-03: chờ chủ dự án xác nhận quyền dùng hình/video học viên.';

/**
 * Print a failure and stop.
 *
 * @param string $message Message.
 * @return never
 */
function jn_s15_fail( string $message ): void {
	echo "[FAIL] {$message}\n";
	exit( 1 );
}

/**
 * Point the WooCommerce product base at /san-pham/ (the old site's base). Rewrite rules are dropped so
 * WordPress rebuilds them on the next request, after WooCommerce re-registers `product` with the new base.
 *
 * @return void
 */
function jn_s15_product_base(): void {
	$permalinks = (array) get_option( 'woocommerce_permalinks', array() );
	if ( JN_S15_PRODUCT_BASE === ( $permalinks['product_base'] ?? '' ) ) {
		echo "[SKIP] product base already /san-pham/\n";
		return;
	}
	$permalinks['product_base'] = JN_S15_PRODUCT_BASE;
	update_option( 'woocommerce_permalinks', $permalinks );
	delete_option( 'rewrite_rules' );
	echo "[OK] product base /product/ -> /san-pham/ (rewrite rules rebuilt on next request)\n";
}

/**
 * Create or update one product from the source.
 *
 * @param array<string, mixed> $item  Product from the source.
 * @param int                  $order Menu order (old card order).
 * @return void
 */
function jn_s15_upsert_product( array $item, int $order ): void {
	// Match on post_name only. (wc_get_products() ignores `slug` and returned the first product — the Step 15
	// incident that overwrote the coaching packages before the DB was restored.)
	$found   = get_posts(
		array(
			'name'        => $item['slug'],
			'post_type'   => 'product',
			'post_status' => array( 'publish', 'draft', 'pending', 'private' ),
			'numberposts' => 2,
			'fields'      => 'ids',
		)
	);
	$product = isset( $found[0] ) ? wc_get_product( (int) $found[0] ) : new WC_Product_Simple();
	if ( count( $found ) > 1 || ! $product instanceof WC_Product_Simple ) {
		jn_s15_fail( "{$item['slug']}: expected at most one simple product with this slug" );
	}
	$is_package = '' !== (string) $product->get_meta( '_jn_package_key', true ) || JN_Coaching_Packages::is_coaching_package( $product );
	if ( $product->get_id() > 0 && ( $is_package || $product->get_slug() !== $item['slug'] ) ) {
		jn_s15_fail( "{$item['slug']}: refusing to overwrite product #{$product->get_id()} ({$product->get_slug()})" );
	}
	$image = jn_lb_media( $item['image'] )['id'];
	$price = null === $item['price'] ? '' : (string) $item['price'];

	$product->set_name( $item['name'] );
	$product->set_slug( $item['slug'] );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_regular_price( $price );
	$product->set_sale_price( '' );
	$product->set_short_description( $item['description'] );
	$product->set_category_ids( array( JN_S15_FAVORITE_CAT ) );
	$product->set_image_id( $image );
	$product->set_menu_order( $order );
	$product->set_virtual( false );
	$product->set_manage_stock( false );
	$product->set_stock_status( 'instock' );
	$product->set_reviews_allowed( false );
	$product->update_meta_data( '_jn_legacy_source', jn_lb_content()['sources']['my-favorite-product'] );
	$is_new = 0 === $product->get_id();
	$id     = $product->save();
	if ( $id < 1 || get_post_field( 'post_name', $id ) !== $item['slug'] ) {
		jn_s15_fail( "{$item['slug']}: product not saved with its slug" );
	}
	printf( "[OK] product %s ID=%d (%s, %s)\n", $item['slug'], $id, $is_new ? 'created' : 'updated', '' === $price ? 'Liên hệ' : number_format( (int) $price, 0, ',', '.' ) . ' ₫' );
}

/**
 * Create or update one legacy testimonial (matched by title = client name).
 *
 * @param array<string, string> $t     Testimonial from the source.
 * @param int                   $order Menu order.
 * @return void
 */
function jn_s15_upsert_testimonial( array $t, int $order ): void {
	$found = get_posts(
		array(
			'post_type'   => 'jn_testimonial',
			'post_status' => 'any',
			'title'       => $t['name'],
			'numberposts' => 1,
			'fields'      => 'ids',
		)
	);
	$post  = array(
		'post_type'   => 'jn_testimonial',
		'post_status' => 'publish',
		'post_title'  => $t['name'],
		'menu_order'  => $order,
	);
	// Update in place when it exists (wp_insert_post() with an ID would reset the fields not passed).
	$id = empty( $found ) ? wp_insert_post( $post, true ) : wp_update_post( array( 'ID' => (int) $found[0] ) + $post, true );
	if ( is_wp_error( $id ) ) {
		jn_s15_fail( "testimonial {$t['name']}: " . $id->get_error_message() );
	}
	$meta = array(
		'jn_client_name'     => $t['name'],
		'jn_headline'        => $t['headline'],
		'jn_quote'           => $t['quote'],
		'jn_video_url'       => $t['video'],
		'jn_video_poster_id' => jn_lb_media( $t['poster'] )['id'],
		'jn_rating'          => 0,
		'jn_client_role'     => '',
		'jn_client_city'     => '',
		'jn_role_display'    => '',
		'jn_admin_note'      => JN_S15_TESTIMONIAL_NOTE,
	);
	foreach ( $meta as $key => $value ) {
		update_post_meta( $id, $key, $value );
	}
	if ( get_post_meta( $id, 'jn_video_url', true ) !== $t['video'] ) {
		jn_s15_fail( "testimonial {$t['name']}: video URL rejected by the sanitizer" );
	}
	printf( "[OK] testimonial %s ID=%d (%s)\n", $t['name'], $id, empty( $found ) ? 'created' : 'updated' );
}

/**
 * Write a page's content when it differs from the built markup; verify it was stored byte for byte.
 *
 * @param int    $page_id Page ID.
 * @param string $content Built content.
 * @return void
 */
function jn_s15_write_page( int $page_id, string $content ): void {
	$current = (string) get_post_field( 'post_content', $page_id, 'raw' );
	if ( $current === $content ) {
		echo "[SKIP] page {$page_id} already matches the legacy content\n";
		return;
	}
	$updated = wp_update_post(
		array(
			'ID'           => $page_id,
			'post_content' => wp_slash( $content ),
		),
		true
	);
	if ( is_wp_error( $updated ) ) {
		jn_s15_fail( "page {$page_id}: " . $updated->get_error_message() );
	}
	clean_post_cache( $page_id );
	if ( (string) get_post_field( 'post_content', $page_id, 'raw' ) !== $content ) {
		jn_s15_fail( "page {$page_id}: saved content differs from the built markup (kses?) — restore the latest revision" );
	}
	echo "[OK] page {$page_id} (" . get_post_field( 'post_name', $page_id ) . ") written; previous content kept as a revision\n";
}

if ( ! function_exists( 'wc_get_products' ) || ! class_exists( 'JN_Testimonials' ) ) {
	jn_s15_fail( 'WooCommerce and jn-core must be active' );
}

// Admin context: kses would otherwise strip the column `style` and the Block Bindings metadata.
$jn_admins = get_users(
	array(
		'role'   => 'administrator',
		'number' => 1,
		'fields' => 'ID',
	)
);
wp_set_current_user( (int) ( $jn_admins[0] ?? 0 ) );

// The builders use fixed IDs from Steps 3–4: check they still point at what they mean before writing.
$jn_cat = get_term( JN_S15_FAVORITE_CAT, 'product_cat' );
if ( ! $jn_cat instanceof WP_Term || 'san-pham-yeu-thich' !== $jn_cat->slug ) {
	jn_s15_fail( 'product_cat ' . JN_S15_FAVORITE_CAT . ' is not "san-pham-yeu-thich" on this database' );
}
if ( 'wp_block' !== get_post_type( 95 ) || false === strpos( (string) get_post_field( 'post_content', 95 ), 'wp:jn/booking-form' ) ) {
	jn_s15_fail( 'wp_block 95 is not the synced booking section on this database' );
}
$jn_page_ids = array();
foreach ( array_keys( JN_S15_PAGES ) as $jn_slug ) {
	$jn_page = get_page_by_path( $jn_slug, OBJECT, 'page' );
	if ( ! $jn_page instanceof WP_Post ) {
		jn_s15_fail( "page /{$jn_slug}/ not found" );
	}
	$jn_page_ids[ $jn_slug ] = (int) $jn_page->ID;
}

jn_s15_product_base();

$jn_content = jn_lb_content();
foreach ( $jn_content['products']['items'] as $jn_index => $jn_item ) {
	jn_s15_upsert_product( $jn_item, $jn_index + 1 );
}
foreach ( $jn_content['testimonials'] as $jn_index => $jn_t ) {
	jn_s15_upsert_testimonial( $jn_t, $jn_index + 1 );
}
foreach ( JN_S15_OLD_TESTIMONIALS as $jn_old ) {
	if ( 'jn_testimonial' === get_post_type( $jn_old ) && 'publish' === get_post_status( $jn_old ) ) {
		wp_update_post(
			array(
				'ID'          => $jn_old,
				'post_status' => 'draft',
			)
		);
		echo "[OK] wireframe testimonial {$jn_old} -> draft (kept, not deleted)\n";
	}
}

foreach ( JN_S15_PAGES as $jn_slug => $jn_builder ) {
	jn_s15_write_page( $jn_page_ids[ $jn_slug ], $jn_builder() );
}

wp_get_theme()->delete_pattern_cache();
jannie_ensure_front_css_bundle();
echo "[OK] Theme pattern cache cleared, CSS bundle rebuilt\n";
if ( function_exists( 'wp_cache_clear_cache' ) ) {
	wp_cache_clear_cache();
	echo "[OK] WP Super Cache cleared\n";
} else {
	echo "[WARN] wp_cache_clear_cache() unavailable — clear the page cache by hand\n";
}

echo "RESULT: done\n";
