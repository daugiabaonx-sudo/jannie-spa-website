<?php
/**
 * JN — Step 18 (ADR-025): put the owner's "Nâng cấp My Favorite Product" design on /my-favorite-product/.
 *
 * - option `jn_contact_zalo_url` = Jannie's Zalo chat (owner input 2026-10-31), read by the consult dialog
 * - the six official products (slug + `_jn_legacy_source`): short description = mockup teaser, description =
 *   the jannieedu.com copy (D6) — nothing else on the product is touched
 * - page `my-favorite-product`: content = jn_lb_products_page() (the `jannie/favorite-*` patterns)
 *
 * Idempotent: items that already match are skipped. `wp_update_post()` keeps a revision of the page (rollback
 * point) and the content is read back to make sure kses changed nothing; product copy can be restored from
 * backups/pre-step-18-2026-10-31.sql.gz. Pass `dry-run` to print what would change without writing.
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/build-step-18-favorite-products.php [dry-run]
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Rewrites page content and product copy: refuse to run against production.
require_once __DIR__ . '/lib/jn-env-guard.php';
jn_require_non_production( basename( __FILE__ ) );
require_once __DIR__ . '/lib/jn-legacy-blocks.php';

const JN_S18_PAGE_SLUG    = 'my-favorite-product';
const JN_S18_FAVORITE_CAT = 41;
const JN_S18_ZALO_URL     = 'https://zalo.me/0559912642';

$jn_s18_dry = in_array( 'dry-run', (array) ( $args ?? array() ), true );

echo '=== JN — STEP 18 FAVORITE PRODUCTS EDIT (ADR-025)' . ( $jn_s18_dry ? ' — DRY RUN' : '' ) . " ===\n";

/**
 * Print a failure and stop.
 *
 * @param string $message Message.
 * @return never
 */
function jn_s18_fail( string $message ): void {
	echo "[FAIL] {$message} — nothing more written\n";
	exit( 1 );
}

if ( ! function_exists( 'wc_get_product' ) || ! class_exists( 'JN_Consult' ) ) {
	jn_s18_fail( 'WooCommerce and jn-core (with JN_Consult) must be active' );
}

// Admin context: kses would otherwise strip the Block Bindings metadata and the `<bdo>` language marks.
$jn_admins = get_users(
	array(
		'role'   => 'administrator',
		'number' => 1,
		'fields' => 'ID',
	)
);
wp_set_current_user( (int) ( $jn_admins[0] ?? 0 ) );

// The collection pattern queries category 41: check it still means "Sản phẩm yêu thích" here.
$jn_cat = get_term( JN_S18_FAVORITE_CAT, 'product_cat' );
if ( ! $jn_cat instanceof WP_Term || 'san-pham-yeu-thich' !== $jn_cat->slug ) {
	jn_s18_fail( 'product_cat ' . JN_S18_FAVORITE_CAT . ' is not "san-pham-yeu-thich" on this database' );
}

// 1. Zalo chat link.
if ( JN_S18_ZALO_URL === get_option( JN_Consult::OPTION ) ) {
	echo '[SKIP] Zalo link already ' . JN_S18_ZALO_URL . "\n";
} elseif ( $jn_s18_dry ) {
	echo '[DRY] would set ' . JN_Consult::OPTION . ' = ' . JN_S18_ZALO_URL . "\n";
} else {
	update_option( JN_Consult::OPTION, JN_S18_ZALO_URL, false );
	if ( JN_S18_ZALO_URL !== JN_Consult::zalo_url() ) {
		jn_s18_fail( 'Zalo link was not accepted by JN_Consult::zalo_url()' );
	}
	echo '[OK] ' . JN_Consult::OPTION . ' = ' . JN_S18_ZALO_URL . "\n";
}

// 2. Product copy (D6). Match on post_name only (never wc_get_products( slug ) — ADR-022 incident) and only
// touch the official legacy products. Every product is checked before any is written, so a bad one stops the
// run with nothing half-updated.
$jn_products = array();
foreach ( jn_lb_content()['products']['items'] as $jn_item ) {
	$jn_found = get_posts(
		array(
			'name'        => $jn_item['slug'],
			'post_type'   => 'product',
			'post_status' => array( 'publish', 'draft', 'pending', 'private' ),
			'numberposts' => 2,
			'fields'      => 'ids',
		)
	);
	if ( 1 !== count( $jn_found ) ) {
		jn_s18_fail( "{$jn_item['slug']}: expected exactly one product with this slug, found " . count( $jn_found ) );
	}
	$jn_product = wc_get_product( (int) $jn_found[0] );
	if ( ! $jn_product instanceof WC_Product_Simple || $jn_product->get_slug() !== $jn_item['slug'] || '' === (string) $jn_product->get_meta( '_jn_legacy_source', true ) ) {
		jn_s18_fail( "{$jn_item['slug']}: product #{$jn_found[0]} is not the official legacy product — refusing to write" );
	}
	if ( empty( $jn_item['teaser'] ) ) {
		jn_s18_fail( "{$jn_item['slug']}: no teaser in jn-legacy-content.json" );
	}
	$jn_products[] = array( $jn_item, $jn_product );
}

foreach ( $jn_products as list( $jn_item, $jn_product ) ) {
	if ( $jn_product->get_short_description() === $jn_item['teaser'] && $jn_product->get_description() === $jn_item['description'] ) {
		echo "[SKIP] product {$jn_item['slug']} copy already matches\n";
		continue;
	}
	if ( $jn_s18_dry ) {
		echo "[DRY] would set product {$jn_item['slug']} (#{$jn_product->get_id()}) short description = teaser, description = jannieedu.com copy\n";
		continue;
	}
	$jn_product->set_short_description( $jn_item['teaser'] );
	$jn_product->set_description( $jn_item['description'] );
	$jn_product->save();
	echo "[OK] product {$jn_item['slug']} (#{$jn_product->get_id()}) copy updated\n";
}

// 3. Page content.
$jn_page = get_page_by_path( JN_S18_PAGE_SLUG, OBJECT, 'page' );
if ( ! $jn_page instanceof WP_Post ) {
	jn_s18_fail( 'page ' . JN_S18_PAGE_SLUG . ' not found' );
}
try {
	$jn_content = jn_lb_products_page();
} catch ( RuntimeException $jn_error ) {
	jn_s18_fail( $jn_error->getMessage() );
}
if ( 1 !== substr_count( $jn_content, '<h1' ) || false === strpos( $jn_content, '<!-- wp:jn/product-actions /-->' ) ) {
	jn_s18_fail( 'built page content is incomplete (H1 / product actions missing)' );
}
if ( $jn_s18_dry ) {
	echo $jn_page->post_content === $jn_content
		? "[DRY] page {$jn_page->ID} already matches\n"
		: "[DRY] would replace page {$jn_page->ID} content (" . strlen( $jn_page->post_content ) . ' -> ' . strlen( $jn_content ) . " bytes)\n";
	echo "=== DRY RUN: nothing written ===\n";
	return;
}
try {
	$jn_status = jn_lb_write_page( $jn_page->ID, $jn_content );
} catch ( RuntimeException $jn_error ) {
	jn_s18_fail( $jn_error->getMessage() );
}
echo 'skip' === $jn_status
	? "[SKIP] page {$jn_page->ID} already matches the patterns\n"
	: "[OK] page {$jn_page->ID} (" . JN_S18_PAGE_SLUG . ") written; previous content kept as a revision\n";

// The theme pattern list is cached (refreshed only inside wp-admin): drop it so jannie/favorite-* register now.
wp_get_theme()->delete_pattern_cache();
echo "[OK] Theme pattern cache cleared\n";

jannie_ensure_front_css_bundle();
jannie_favorite_css();
echo "[OK] CSS bundles rebuilt\n";
if ( function_exists( 'wp_cache_clear_cache' ) ) {
	wp_cache_clear_cache();
	echo "[OK] WP Super Cache cleared\n";
} else {
	echo "[WARN] wp_cache_clear_cache() unavailable — clear the page cache by hand\n";
}
