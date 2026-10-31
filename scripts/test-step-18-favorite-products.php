<?php
/**
 * JN — Step 18 (ADR-025): the owner's "Nâng cấp My Favorite Product" design on /my-favorite-product/.
 *
 * Covers the three blocks (`jn/product-stage`, `jn/marquee`, `jn/product-actions` + the consult dialog), the
 * `jannie/favorite-*` patterns (core/WooCommerce/jn blocks only, copy verbatim from the archived mockup),
 * the stored page and product copy, the page-scoped stylesheet and the live page.
 *
 * Run via (after `build-step-18-favorite-products.php`):
 *   docker compose run --rm wpcli wp eval-file /scripts/test-step-18-favorite-products.php
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/jn-test-kit.php';
require_once __DIR__ . '/lib/jn-legacy-test-kit.php';
require_once __DIR__ . '/lib/jn-legacy-blocks.php';
jn_require_non_production( 'test-step-18-favorite-products.php' );

echo "=================================================================\n";
echo "JN — STEP 18 FAVORITE PRODUCTS EDIT (ADR-025)\n";
echo "=================================================================\n";

const JN_T18_PAGE_ID = 12;
const JN_T18_ZALO    = 'https://zalo.me/0559912642';
const JN_T18_MARQUEE = array( 'Curated with love', 'Beauty is a ritual', 'The Jannie Selection', 'Little moments of luxury' );
// Fidelity to the archived mockup (docs/ is not mounted in the wpcli container) is checked on the host by
// verify-step-18-favorite-products.mjs: sha256, every page string and the six teasers.

/**
 * Read a file, or an empty string when it is missing.
 *
 * @param string $path Absolute path.
 * @return string
 */
function jn_t18_read( string $path ): string {
	return is_file( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

/**
 * Lower-cased normalised text, so title-case changes (ADR-025 D1) compare equal to the mockup's capitals.
 *
 * @param string $html HTML or text.
 * @return string
 */
function jn_t18_text( string $html ): string {
	return mb_strtolower( jn_lt_text( $html ) );
}

/**
 * Render a block, optionally inside a product context.
 *
 * @param string               $name    Block name.
 * @param array<string, mixed> $attrs   Attributes.
 * @param int                  $post_id Product ID for the `postId` context (0 = none).
 * @return string
 */
function jn_t18_render( string $name, array $attrs = array(), int $post_id = 0 ): string {
	$block = new WP_Block(
		array(
			'blockName' => $name,
			'attrs'     => $attrs,
		),
		$post_id > 0 ? array(
			'postId'   => $post_id,
			'postType' => 'product',
		) : array()
	);
	return (string) $block->render();
}

/**
 * Product ID by slug (post_name lookup, never wc_get_products( slug ) — ADR-022 incident).
 *
 * @param string $slug Product slug.
 * @return int
 */
function jn_t18_product_id( string $slug ): int {
	$ids = get_posts(
		array(
			'name'        => $slug,
			'post_type'   => 'product',
			'post_status' => 'publish',
			'numberposts' => 1,
			'fields'      => 'ids',
		)
	);
	return (int) ( $ids[0] ?? 0 );
}

/**
 * Opening tags (in order) of the links/buttons carrying a class.
 *
 * @param string $html      Markup.
 * @param string $css_class CSS class.
 * @return string[]
 */
function jn_t18_tags( string $html, string $css_class ): array {
	preg_match_all( '/<(?:a|button)\b[^>]*\bclass="[^"]*\b' . preg_quote( $css_class, '/' ) . '\b[^"]*"[^>]*>/', $html, $found );
	return $found[0];
}

/**
 * Force the Zalo option for the duration of a callback (no DB write).
 *
 * @param string   $value    Option value.
 * @param callable $callback Callback.
 * @return mixed
 */
function jn_t18_with_zalo( string $value, callable $callback ) {
	$filter = static fn(): string => $value;
	add_filter( 'pre_option_jn_contact_zalo_url', $filter );
	$result = $callback();
	remove_filter( 'pre_option_jn_contact_zalo_url', $filter );
	return $result;
}

$jn_c     = jn_lb_content()['products'];
$jn_theme = get_stylesheet_directory();
$jn_core  = WP_PLUGIN_DIR . '/jn-core/blocks';

// -----------------------------------------------------------------------------
jn_t_section( 'Blocks' );
$jn_registry = WP_Block_Type_Registry::get_instance();
foreach ( array( 'product-stage', 'marquee', 'product-actions' ) as $jn_slug ) {
	$jn_type = $jn_registry->get_registered( 'jn/' . $jn_slug );
	jn_t_assert( null !== $jn_type, "jn/{$jn_slug} is registered" );
	$jn_view   = $jn_type ? (array) $jn_type->view_script_handles : array();
	$jn_handle = (string) ( $jn_view[0] ?? '' );
	$jn_script = '' !== $jn_handle ? wp_scripts()->query( $jn_handle ) : false;
	jn_t_assert(
		$jn_script instanceof _WP_Dependency && 'defer' === ( $jn_script->extra['strategy'] ?? '' ) && array() === $jn_script->deps,
		"jn/{$jn_slug} ships a deferred, dependency-free view script",
		$jn_handle
	);
	$jn_js = jn_t18_read( "{$jn_core}/{$jn_slug}/view.js" );
	jn_t_assert( '' !== $jn_js && false === strpos( $jn_js, 'jQuery' ) && false === strpos( $jn_js, 'innerHTML' ), "jn/{$jn_slug} view.js is vanilla and never writes innerHTML" );
}

// The jn/product-stage block.
$jn_stage = jn_t18_render( 'jn/product-stage', array( 'note' => '"><script>alert(1)</script>' ) );
jn_t_assert( (bool) preg_match( '/<div\b[^>]*\brole="img"[^>]*\baria-label="[^"]+"/', $jn_stage ), 'Stage is one labelled image (role="img")' );
jn_t_assert( 3 === substr_count( $jn_stage, 'class="jn-bottle ' ), 'Stage draws three bottles' );
jn_t_assert( false === stripos( $jn_stage, '<script' ), 'Stage escapes its attributes' );
jn_t_assert( false !== strpos( $jn_stage, 'jn-product-stage__spotlight' ), 'Stage carries the spotlight layer' );

// The jn/marquee block.
$jn_marquee = jn_t18_render( 'jn/marquee', array( 'items' => array( 'Curated with love', '<b>x</b>' ) ) );
preg_match( '/<div\b[^>]*class="jn-marquee__viewport"[^>]*>(.*?)<\/div>\s*<\/div>/s', $jn_marquee, $jn_track );
jn_t_assert( (bool) preg_match( '/class="jn-marquee__viewport"[^>]*aria-hidden="true"/', $jn_marquee ), 'Marquee track is hidden from assistive tech' );
jn_t_assert( 2 === substr_count( $jn_marquee, '>Curated with love<' ), 'Marquee repeats its items once for a seamless loop' );
jn_t_assert( false === strpos( $jn_marquee, '<b>' ), 'Marquee escapes its items' );
jn_t_assert(
	(bool) preg_match( '/<button\b[^>]*class="jn-marquee__toggle"[^>]*aria-pressed="false"/', $jn_marquee ) && false === strpos( $jn_track[1] ?? '', '<button' ),
	'Marquee has a pause button outside the hidden track (WCAG 2.2.2)'
);
$jn_marquee_type = $jn_registry->get_registered( 'jn/marquee' );
$jn_defaults     = $jn_marquee_type ? (array) ( $jn_marquee_type->attributes['items']['default'] ?? array() ) : array();
jn_t_assert( JN_T18_MARQUEE === $jn_defaults, 'Marquee default items are the mockup phrases' );

// The jn/product-actions block.
$jn_priced   = jn_t18_product_id( 'egf-cleansing-gel' );
$jn_unpriced = jn_t18_product_id( 'xa-bong-thao-duoc' );
jn_t_assert( $jn_priced > 0 && $jn_unpriced > 0, 'Fixture products exist (egf-cleansing-gel, xa-bong-thao-duoc)' );

$jn_html = jn_t18_with_zalo( JN_T18_ZALO, static fn(): string => jn_t18_render( 'jn/product-actions', array(), $jn_priced ) );
$jn_btns = jn_t18_tags( $jn_html, 'jn-product-actions__btn' );
jn_t_assert(
	2 === count( $jn_btns ) && str_starts_with( $jn_btns[0], '<a ' ) && false !== strpos( $jn_btns[0], 'href="' . esc_url( get_permalink( $jn_priced ) ) . '"' ) && false !== strpos( $jn_btns[0], '--primary' ),
	'Priced product: "Mua Ngay" (primary) links to the product page first'
);
jn_t_assert(
	isset( $jn_btns[1] ) && str_starts_with( $jn_btns[1], '<button ' ) && false !== strpos( $jn_btns[1], 'data-jn-consult' ) && false !== strpos( $jn_btns[1], '--secondary' ),
	'Priced product: the consult button comes second (secondary)'
);
jn_t_assert( false !== strpos( jn_lt_text( $jn_html ), 'Mua Ngay' ) && false !== strpos( jn_lt_text( $jn_html ), 'Tư vấn qua Zalo' ), 'With a Zalo link the button reads "Tư vấn qua Zalo"' );
jn_t_assert( (bool) preg_match( '/data-jn-product="The Balance EGF Cleansing Gel"/', $jn_html ) && (bool) preg_match( '/data-jn-price="496[.,]000/', $jn_html ), 'Consult button carries the product name and price as plain data' );

$jn_html = jn_t18_with_zalo( JN_T18_ZALO, static fn(): string => jn_t18_render( 'jn/product-actions', array(), $jn_unpriced ) );
$jn_btns = jn_t18_tags( $jn_html, 'jn-product-actions__btn' );
jn_t_assert(
	2 === count( $jn_btns ) && str_starts_with( $jn_btns[0], '<button ' ) && false !== strpos( $jn_btns[0], '--primary' )
		&& str_starts_with( $jn_btns[1], '<a ' ) && false !== strpos( $jn_btns[1], '--secondary' ),
	'No-price product: consult (primary) first, then "Xem sản phẩm" (secondary), as in the mockup'
);
jn_t_assert( false !== strpos( jn_lt_text( $jn_html ), 'Xem sản phẩm' ) && false === strpos( jn_lt_text( $jn_html ), 'Mua Ngay' ) && false !== strpos( $jn_html, 'data-jn-price="Liên hệ"' ), 'No-price product never offers "Mua Ngay" and quotes "Liên hệ"' );

$jn_html = jn_t18_with_zalo( '', static fn(): string => jn_t18_render( 'jn/product-actions', array(), $jn_priced ) );
jn_t_assert( false !== strpos( jn_lt_text( $jn_html ), 'Nhận tư vấn' ) && false === strpos( $jn_html, 'Zalo' ), 'Without a Zalo link the button reads "Nhận tư vấn" (never promises Zalo)' );

$jn_html = jn_t18_render(
	'jn/product-actions',
	array(
		'mode'         => 'contact',
		'contactLabel' => 'Kết Nối Với Jannie',
	)
);
jn_t_assert( 1 === count( jn_t18_tags( $jn_html, 'jn-product-actions__cta' ) ) && false !== strpos( $jn_html, 'data-jn-consult' ) && false === strpos( $jn_html, 'data-jn-product' ), 'Contact mode renders one general consult button' );
jn_t_assert( '' === jn_t18_render( 'jn/product-actions' ), 'Product mode outside a product renders nothing' );

// Consult dialog + Zalo option.
jn_t_assert( class_exists( 'JN_Consult' ), 'JN_Consult is loaded' );
if ( class_exists( 'JN_Consult' ) ) {
	jn_t_assert( JN_T18_ZALO === jn_t18_with_zalo( JN_T18_ZALO, array( 'JN_Consult', 'zalo_url' ) ), 'Zalo option accepts https://zalo.me/<number>' );
	foreach ( array( 'http://zalo.me/0559912642', 'https://evil.example/zalo.me/1', 'javascript:alert(1)', 'https://zalo.me/' ) as $jn_bad ) {
		jn_t_assert( '' === jn_t18_with_zalo( $jn_bad, array( 'JN_Consult', 'zalo_url' ) ), "Zalo option rejects {$jn_bad}" );
	}
	$jn_dialog = jn_t18_with_zalo( JN_T18_ZALO, array( 'JN_Consult', 'dialog_html' ) );
	jn_t_assert( (bool) preg_match( '/<dialog\b[^>]*id="jn-consult"[^>]*aria-labelledby="jn-consult-title"/', $jn_dialog ), 'Dialog is a labelled native <dialog>' );
	jn_t_assert( (bool) preg_match( '/<a\b[^>]*href="' . preg_quote( JN_T18_ZALO, '/' ) . '"[^>]*rel="noopener noreferrer"/', $jn_dialog ), 'Dialog opens the Zalo chat in a new tab (noopener)' );
	jn_t_assert( (bool) preg_match( '/<textarea\b[^>]*id="jn-consult-text"[^>]*readonly/', $jn_dialog ) && false !== strpos( $jn_dialog, 'for="jn-consult-text"' ), 'Dialog message box is labelled and read-only' );
	jn_t_assert( (bool) preg_match( '/role="status"/', $jn_dialog ), 'Dialog announces copy results (role="status")' );
	$jn_dialog = jn_t18_with_zalo( '', array( 'JN_Consult', 'dialog_html' ) );
	jn_t_assert( false === strpos( $jn_dialog, 'zalo.me' ) && false !== strpos( $jn_dialog, 'href="mailto:contact@jannienguyen.vn' ), 'Without Zalo the dialog falls back to email (D7)' );
	ob_start();
	JN_Consult::print_dialog();
	JN_Consult::print_dialog();
	$jn_printed = (string) ob_get_clean();
	jn_t_assert( 1 === substr_count( $jn_printed, '<dialog' ), 'The dialog prints once per page' );
}

// -----------------------------------------------------------------------------
jn_t_section( 'Patterns' );
$jn_src = '';
foreach ( array( 'favorite-hero.php', 'favorite-collection.php', 'favorite-manifesto.php' ) as $jn_file ) {
	$jn_one = jn_t18_read( "{$jn_theme}/patterns/{$jn_file}" );
	jn_t_assert( '' !== $jn_one, "patterns/{$jn_file} exists" );
	$jn_src .= $jn_one;
}
preg_match_all( '/<!-- wp:([a-z0-9-]+\/)?([a-z0-9-]+)/', $jn_src, $jn_names );
$jn_foreign = array_unique(
	array_filter(
		array_map( static fn( string $ns, string $n ): string => ( '' === $ns ? 'core/' : $ns ) . $n, $jn_names[1], $jn_names[2] ),
		static fn( string $n ): bool => ! preg_match( '#^(core|woocommerce|jn)/#', $n ) || in_array( $n, array( 'core/html', 'core/shortcode', 'core/freeform' ), true )
	)
);
jn_t_assert( '' !== $jn_src && array() === $jn_foreign, 'Patterns use core, WooCommerce and jn blocks only (no Custom HTML)', implode( ', ', $jn_foreign ) );
jn_t_assert( 1 === preg_match_all( '/<h1\b/', $jn_src ), 'Patterns hold exactly one H1' );
jn_t_assert( false === strpos( $jn_src, 'fonts.googleapis' ) && ! preg_match( '/\son[a-z]+=/', $jn_src ), 'No Google Fonts and no inline event handlers' );

$jn_page    = jn_lb_products_page();
$jn_expects = array(
	'The Jannie Edit · 2026',
	'Vẻ đẹp, rất riêng.',
	'Không cần quá nhiều. Chỉ cần những điều thực sự xứng đáng. Khám phá 6 sản phẩm được Jannie yêu thích và tuyển chọn.',
	'Khám Phá Bộ Sưu Tập',
	'Câu Chuyện Của Jannie',
	'The Curated Collection',
	$jn_c['intro']['body'],
	'Sắc Xuân Mới – Bộ Sản Phẩm Yêu Thích Của Jannie',
	'Beauty Is Personal',
	'Bạn xứng đáng với những điều tinh tế nhất.',
	'Vẻ đẹp không nằm ở số lượng sản phẩm, mà ở cách mỗi lựa chọn khiến bạn yêu thương chính mình hơn.',
	'Kết Nối Với Jannie',
);
// Rendered, because block attributes (e.g. the contact button label) live in comment delimiters.
$jn_missing = jn_lt_missing( array_map( 'jn_t18_text', $jn_expects ), jn_t18_text( do_blocks( $jn_page ) ) );
jn_t_assert( array() === $jn_missing, 'The page shows the mockup copy', implode( ' | ', $jn_missing ) );
jn_t_assert( (bool) preg_match( '/href="#bo-suu-tap"/', $jn_page ) && (bool) preg_match( '/<section\b[^>]*id="bo-suu-tap"/', $jn_page ), 'Hero CTA scrolls to the collection (#bo-suu-tap)' );
jn_t_assert( false !== strpos( $jn_page, 'href="' . esc_url( home_url( '/my-story/' ) ) . '"' ), '"Câu Chuyện Của Jannie" links to /my-story/' );
jn_t_assert( (bool) preg_match( '/<bdo lang="en" dir="ltr">The Jannie Edit<\/bdo>/', $jn_page ), 'English phrases are marked lang="en"' );

$jn_order = array( 'jn-fav-hero', 'wp:jn/marquee', 'jn-fav-intro', 'jn-fav-collection', 'jn-fav-manifesto' );
$jn_pos   = array_map( static fn( string $n ): int => (int) strpos( $jn_page, $n ), $jn_order );
$jn_sort  = $jn_pos;
sort( $jn_sort );
jn_t_assert( ! in_array( 0, $jn_pos, true ) && $jn_pos === $jn_sort, 'Sections follow the mockup: hero, marquee, intro, collection, manifesto' );
jn_t_assert(
	(bool) preg_match( '/"taxQuery":\{"product_cat":\[41\]\}/', $jn_page ) && (bool) preg_match( '#<!-- wp:woocommerce/product-template -->.*<!-- wp:jn/product-actions /-->.*<!-- /wp:woocommerce/product-template -->#s', $jn_page ),
	'Cards come from the WooCommerce collection (category 41) with jn/product-actions in the template'
);
jn_t_assert( false === strpos( $jn_page, 'wp-image-' . jn_lb_media( $jn_c['intro']['image'] )['id'] ) && false === strpos( $jn_page, 'jn-page-intro' ), 'The old intro photo and page intro are gone (mockup layout)' );

// -----------------------------------------------------------------------------
jn_t_section( 'Stored page & products' );
$jn_saved = (string) get_post_field( 'post_content', JN_T18_PAGE_ID, 'raw' );
jn_t_assert( $jn_saved === $jn_page, 'Page 12 stores exactly jn_lb_products_page() (build-step-15 and build-step-18 agree)' );
foreach ( $jn_c['items'] as $jn_item ) {
	$jn_id      = jn_t18_product_id( $jn_item['slug'] );
	$jn_product = $jn_id > 0 ? wc_get_product( $jn_id ) : null;
	if ( ! $jn_product instanceof WC_Product ) {
		jn_t_assert( false, "{$jn_item['slug']} exists" );
		continue;
	}
	$jn_teaser = (string) ( $jn_item['teaser'] ?? '' );
	jn_t_assert( '' !== $jn_teaser, "{$jn_item['slug']}: has a teaser in the content source" );
	jn_t_assert( jn_lt_text( $jn_product->get_short_description() ) === jn_lt_text( $jn_teaser ), "{$jn_item['slug']}: short description = mockup teaser (D6)" );
	jn_t_assert( jn_lt_text( $jn_product->get_description() ) === jn_lt_text( $jn_item['description'] ), "{$jn_item['slug']}: description keeps the jannieedu.com copy (D6)" );
	jn_t_assert( '' !== (string) $jn_product->get_meta( '_jn_legacy_source', true ), "{$jn_item['slug']}: still marked as an official legacy product" );
}

// -----------------------------------------------------------------------------
jn_t_section( 'Demo contact number (owner, 2026-10-31)' );
$jn_hotline = (string) get_post_field( 'post_content', 95 );
jn_t_assert( 'wp_block' === get_post_type( 95 ) && false === strpos( $jn_hotline, '908-XXX' ) && 1 === substr_count( $jn_hotline, '<a href="tel:+84559912642">0559 912 642</a>' ), 'Booking hotline (synced block 95) shows 0559 912 642 as a tel: link' );
jn_t_assert( false !== strpos( jn_t18_read( "{$jn_theme}/patterns/booking-section.php" ), '<a href="tel:+84559912642">0559 912 642</a>' ), 'patterns/booking-section.php seeds the same number' );
jn_t_assert( '+84 559 912 642' === get_option( 'jn_brand_telephone' ), 'Schema telephone (jn_brand_telephone) is +84 559 912 642' );

// -----------------------------------------------------------------------------
jn_t_section( 'Stylesheet' );
$jn_css = jn_t18_read( "{$jn_theme}/assets/css/favorite-products.css" );
jn_t_assert( '' !== $jn_css, 'assets/css/favorite-products.css exists' );
preg_match_all( '/@keyframes\s+[\w-]+\s*\{((?:[^{}]*\{[^{}]*\})*[^{}]*)\}/', $jn_css, $jn_frames );
preg_match_all( '/([a-z-]+)\s*:/', implode( '', $jn_frames[1] ), $jn_props );
$jn_bad_props = array_diff( array_unique( $jn_props[1] ), array( 'transform', 'opacity', 'translate' ) );
jn_t_assert( count( $jn_frames[1] ) >= 3 && array() === $jn_bad_props, 'Keyframes animate transform/opacity only', implode( ', ', $jn_bad_props ) );
jn_t_assert( '' !== $jn_css && ! preg_match( '/(?<![\w-])filter\s*:\s*blur/', $jn_css ), 'No blur filter on content (the mockup reveal blurred the LCP)' );
preg_match( '/@media \(prefers-reduced-motion: reduce\)\s*\{((?:[^{}]*\{[^{}]*\})*[^{}]*)\}/', $jn_css, $jn_reduce );
jn_t_assert( isset( $jn_reduce[1] ) && false !== strpos( $jn_reduce[1], 'animation: none' ) && false !== strpos( $jn_reduce[1], '.jn-marquee__track' ), 'Reduced motion stops every decorative loop' );
jn_t_assert( false !== strpos( $jn_css, '.jn-motion-lite' ) && false !== strpos( $jn_css, '.jn-fav-motion-paused' ), 'Low-end devices and the pause button stop the loops too' );
jn_t_assert( '' !== $jn_css && false === stripos( $jn_css, '#8C6A52' ) && false === stripos( $jn_css, '#8E8074' ) && false === stripos( $jn_css, '#AD8C73' ), 'Mockup text colours that fail WCAG AA are not used' );
jannie_ensure_front_css_bundle();
clearstatcache();
jn_t_assert( false === strpos( jn_t18_read( "{$jn_theme}/assets/css/jannie-bundle.min.css" ), 'jn-product-stage' ), 'Page CSS stays out of the site-wide bundle' );

// -----------------------------------------------------------------------------
jn_t_section( 'Live page' );
$jn_res  = jn_lt_get( '/my-favorite-product/' );
$jn_live = $jn_res['body'];
jn_t_assert( 200 === $jn_res['code'], '/my-favorite-product/ responds 200' );
jn_t_assert( 1 === preg_match_all( '/<h1[\s>]/', $jn_live ), 'Exactly one H1' );
jn_t_assert( 1 === substr_count( $jn_live, '<style id="jn-favorite-inline-css">' ), 'Page CSS is inlined in <head>' );
$jn_scripted = (string) preg_replace( '#<noscript>.*?</noscript>#s', '', $jn_live ); // The no-JS fallback keeps a plain link.
foreach ( array( 'wc-blocks-style-product-image', 'wc-blocks-style-product-summary' ) as $jn_handle ) {
	jn_t_assert(
		(bool) preg_match( "/<link\b[^>]*id=['\"]{$jn_handle}-css['\"][^>]*data-jn-href=/i", $jn_scripted ) && ! preg_match( "/<link\b[^>]*id=['\"]{$jn_handle}-css['\"][^>]*\shref=/i", $jn_scripted ),
		"{$jn_handle} no longer blocks the first paint (deferred)"
	);
}
jn_t_assert( 1 === substr_count( $jn_live, 'id="jn-consult"' ) && false !== strpos( $jn_live, 'href="' . JN_T18_ZALO . '"' ), 'One consult dialog, linked to Jannie\'s Zalo' );
jn_t_assert( 6 === substr_count( $jn_live, 'class="jn-product-actions jn-product-actions--product"' ) && 1 === substr_count( $jn_live, 'class="jn-product-actions jn-product-actions--contact"' ), 'Six product cards carry their actions, plus one contact button' );
jn_t_assert( false === strpos( $jn_live, 'fonts.googleapis' ) && false === strpos( $jn_live, 'chat.zalo.me' ), 'No Google Fonts, no generic Zalo Web link' );
jn_t_assert( false !== strpos( $jn_live, '"telephone":"+84 559 912 642"' ), 'The JSON-LD graph carries the demo telephone' );
foreach ( array( 'product-stage', 'marquee', 'product-actions' ) as $jn_slug ) {
	$jn_ok = (bool) preg_match( '/<script\b[^>]*src="[^"]*blocks\/' . $jn_slug . '\/view\.js[^"]*"[^>]*>/', $jn_live, $jn_tag );
	jn_t_assert( $jn_ok && false !== strpos( $jn_tag[0], 'defer' ), "{$jn_slug} view script is deferred" );
}
$jn_home = jn_lt_get( '/' )['body'];
jn_t_assert( '' !== $jn_home && false === strpos( $jn_home, 'jn-favorite-inline-css' ) && false === strpos( $jn_home, 'blocks/marquee/view.js' ), 'Other pages carry none of this page\'s CSS or JS' );

jn_t_finish();
