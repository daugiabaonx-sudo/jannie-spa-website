<?php
/**
 * JN Step 10 — SEO / AEO Automated Verification Suite.
 *
 * Run via:
 *   docker compose run --rm wpcli wp eval-file /scripts/test-step-10-seo.php
 *
 * Covers the audit findings F1–F12 (docs/jn/seo-aeo/) with unit checks on the pure builders
 * (robots.txt, JSON-LD graph, FAQ extraction, llms.txt) and HTTP integration checks on the
 * rendered pages (page cache is purged first so the latest markup is tested).
 *
 * @package JN_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( 'production' === wp_get_environment_type() ) {
	echo "Refusing to run: this suite toggles options (blog_public, jn_brand_telephone) and purges caches.\n";
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
function jn_step10_assert( bool $condition, string $label ): void {
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
function jn_step10_http_get( string $path, array $headers = array() ) {
	$base = 'http://localhost:8080';
	if ( gethostbyname( 'wordpress' ) !== 'wordpress' ) {
		$base            = 'http://wordpress';
		$headers['Host'] = 'localhost:8080';
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
 * Decode every JSON-LD block in an HTML document into a flat list of graph nodes.
 *
 * @param string $html HTML document.
 * @return array{blocks: int, nodes: array<int, array<string, mixed>>}
 */
function jn_step10_jsonld_nodes( string $html ): array {
	preg_match_all( '#<script type="application/ld\+json">(.*?)</script>#s', $html, $m );
	$nodes = array();
	foreach ( $m[1] as $raw ) {
		$data = json_decode( $raw, true );
		if ( isset( $data['@graph'] ) && is_array( $data['@graph'] ) ) {
			$nodes = array_merge( $nodes, $data['@graph'] );
		} elseif ( is_array( $data ) ) {
			$nodes[] = $data;
		}
	}
	return array(
		'blocks' => count( $m[1] ),
		'nodes'  => $nodes,
	);
}

/**
 * Return all `@type` values found in a list of graph nodes.
 *
 * @param array<int, array<string, mixed>> $nodes Graph nodes.
 * @return array<int, string>
 */
function jn_step10_types( array $nodes ): array {
	$types = array();
	foreach ( $nodes as $node ) {
		$types = array_merge( $types, (array) ( $node['@type'] ?? array() ) );
	}
	return array_values( array_unique( $types ) );
}

/**
 * Collect every `{"@id": ...}` reference inside a node tree.
 *
 * @param mixed              $value Node value.
 * @param array<int, string> $refs  Collected references (by reference).
 * @return void
 */
function jn_step10_collect_refs( $value, array &$refs ): void {
	if ( ! is_array( $value ) ) {
		return;
	}
	if ( array( '@id' ) === array_keys( $value ) ) {
		$refs[] = (string) $value['@id'];
	}
	foreach ( $value as $child ) {
		jn_step10_collect_refs( $child, $refs );
	}
}

echo "=== JN STEP 10 SEO / AEO VERIFICATION SUITE ===\n\n";

// -----------------------------------------------------------------------------
// 0. Module wiring
// -----------------------------------------------------------------------------
echo "--- 0. Module wiring ---\n";
$jn_classes = array( 'JN_SEO_Brand', 'JN_SEO_Meta', 'JN_SEO_Robots', 'JN_SEO_Sitemap', 'JN_SEO_Schema', 'JN_SEO_Llms' );
foreach ( $jn_classes as $jn_class ) {
	jn_step10_assert( class_exists( $jn_class ), "SEO module class {$jn_class} is loaded by jn-performance.php" );
}
if ( count( array_filter( $jn_classes, 'class_exists' ) ) !== count( $jn_classes ) ) {
	echo "\nRESULT: {$GLOBALS['jn_passed']} passed, {$GLOBALS['jn_failed']} failed (modules missing, aborting)\n";
	return;
}

// -----------------------------------------------------------------------------
// 1. F1 — Brand / NAP data provenance
// -----------------------------------------------------------------------------
echo "\n--- 1. Brand data provenance (F1) ---\n";
delete_option( 'jn_brand_telephone' );
$jn_brand = JN_SEO_Brand::get();
jn_step10_assert( '' === $jn_brand['telephone'], 'Telephone is empty while the owner has not provided one (no invented NAP data)' );
jn_step10_assert( 'contact@jannienguyen.vn' === $jn_brand['email'], 'Brand email comes from the Project Context Card' );

$jn_ctx   = array(
	'canonical'    => home_url( '/' ),
	'title'        => 'T',
	'description'  => 'D',
	'page_type'    => 'WebPage',
	'breadcrumbs'  => array(),
	'service_page' => true,
	'faq'          => array(),
);
$jn_graph = JN_SEO_Schema::build_graph( $jn_ctx );
$jn_json  = (string) wp_json_encode( $jn_graph );
jn_step10_assert( false === strpos( $jn_json, 'telephone' ), 'JSON-LD graph has no telephone property when none is configured' );
jn_step10_assert( false === strpos( $jn_json, '908' ), 'JSON-LD graph never contains the unverified 0908… number' );
jn_step10_assert( false === strpos( $jn_json, 'priceRange' ), 'JSON-LD graph omits priceRange while prices are UNKNOWN' );

update_option( 'jn_brand_telephone', '+84 28 0000 0000', false );
$jn_json_tel = (string) wp_json_encode( JN_SEO_Schema::build_graph( $jn_ctx ) );
jn_step10_assert( false !== strpos( $jn_json_tel, '"telephone":"+84 28 0000 0000"' ), 'Telephone appears only after the owner sets option jn_brand_telephone' );
delete_option( 'jn_brand_telephone' );

// -----------------------------------------------------------------------------
// 2. F6 — Unified JSON-LD graph integrity
// -----------------------------------------------------------------------------
echo "\n--- 2. JSON-LD graph integrity (F6) ---\n";
$jn_nodes = $jn_graph['@graph'];
$jn_types = jn_step10_types( $jn_nodes );
foreach ( array( 'WebSite', 'Organization', 'Person', 'WebPage', 'Service' ) as $jn_type ) {
	jn_step10_assert( in_array( $jn_type, $jn_types, true ), "Graph contains a {$jn_type} node" );
}
$jn_ids  = array_filter( array_map( static fn( $n ) => $n['@id'] ?? '', $jn_nodes ) );
$jn_refs = array();
foreach ( $jn_nodes as $jn_node ) {
	unset( $jn_node['@id'] );
	jn_step10_collect_refs( $jn_node, $jn_refs );
}
jn_step10_assert( array() === array_diff( array_unique( $jn_refs ), $jn_ids ), 'Every @id reference resolves to a node inside the same graph' );
$jn_webpage = current( array_filter( $jn_nodes, static fn( $n ) => in_array( 'WebPage', (array) $n['@type'], true ) ) );
jn_step10_assert( isset( $jn_webpage['isPartOf']['@id'] ) && str_ends_with( $jn_webpage['isPartOf']['@id'], '#website' ), 'WebPage.isPartOf points to the WebSite node' );

$jn_inner = JN_SEO_Schema::build_graph(
	array_merge(
		$jn_ctx,
		array(
			'canonical'    => home_url( '/my-story/' ),
			'page_type'    => 'ProfilePage',
			'service_page' => false,
			'breadcrumbs'  => array(
				array( 'name' => 'Trang chủ', 'url' => home_url( '/' ) ),
				array( 'name' => 'My Story', 'url' => home_url( '/my-story/' ) ),
			),
		)
	)
);
$jn_inner_types = jn_step10_types( $jn_inner['@graph'] );
jn_step10_assert( in_array( 'BreadcrumbList', $jn_inner_types, true ), 'Inner page graph contains a BreadcrumbList' );
jn_step10_assert( in_array( 'ProfilePage', $jn_inner_types, true ), '/my-story/ is typed ProfilePage' );
jn_step10_assert( ! in_array( 'Service', $jn_inner_types, true ), 'Service nodes are limited to service pages' );

// -----------------------------------------------------------------------------
// 3. F7 — FAQ extraction (owner-approved content only)
// -----------------------------------------------------------------------------
echo "\n--- 3. FAQ extraction (F7) ---\n";
$jn_faq_html = '<!-- wp:details {"className":"jn-faq-item"} --><details class="wp-block-details jn-faq-item"><summary>Buổi tư vấn diễn ra ở đâu?</summary><!-- wp:paragraph --><p>Trực tuyến hoặc tại Học viện.</p><!-- /wp:paragraph --></details><!-- /wp:details -->'
	. '<!-- wp:details {"className":"jn-faq-item"} --><details class="wp-block-details jn-faq-item"><summary>[NHÁP] Câu hỏi mẫu?</summary><!-- wp:paragraph --><p>[NHÁP] Trả lời mẫu.</p><!-- /wp:paragraph --></details><!-- /wp:details -->'
	. '<!-- wp:details --><details class="wp-block-details"><summary>Not a FAQ</summary><!-- wp:paragraph --><p>x</p><!-- /wp:paragraph --></details><!-- /wp:details -->';
$jn_faq      = JN_SEO_Schema::extract_faq_from_blocks( $jn_faq_html );
jn_step10_assert( 1 === count( $jn_faq ), 'Only published jn-faq-item blocks become FAQ entries ([NHÁP] drafts and plain details are skipped)' );
jn_step10_assert( isset( $jn_faq[0] ) && 'Buổi tư vấn diễn ra ở đâu?' === $jn_faq[0]['question'] && 'Trực tuyến hoặc tại Học viện.' === $jn_faq[0]['answer'], 'FAQ question/answer text is extracted verbatim' );
$jn_faq_graph = JN_SEO_Schema::build_graph( array_merge( $jn_ctx, array( 'faq' => $jn_faq ) ) );
jn_step10_assert( in_array( 'FAQPage', jn_step10_types( $jn_faq_graph['@graph'] ), true ), 'FAQPage node is emitted when FAQ entries exist' );
jn_step10_assert( ! in_array( 'FAQPage', $jn_types, true ), 'No FAQPage node without FAQ entries' );

$jn_sync_block_id = wp_insert_post(
	array(
		'post_type'    => 'wp_block',
		'post_status'  => 'publish',
		'post_title'   => 'Temp Synced FAQ Test',
		'post_content' => '<!-- wp:details {"className":"jn-faq-item"} --><details class="wp-block-details jn-faq-item"><summary>Câu hỏi đồng bộ?</summary><!-- wp:paragraph --><p>Trả lời đồng bộ.</p><!-- /wp:paragraph --></details><!-- /wp:details -->',
	)
);
$jn_synced_faq = array();
if ( is_wp_error( $jn_sync_block_id ) || (int) $jn_sync_block_id <= 0 ) {
	echo '[WARN] Could not create temp wp_block: ' . ( is_wp_error( $jn_sync_block_id ) ? $jn_sync_block_id->get_error_message() : 'unknown error' ) . "\n";
} else {
	try {
		$jn_synced_faq = JN_SEO_Schema::extract_faq_from_blocks( sprintf( '<!-- wp:block {"ref":%d} /-->', (int) $jn_sync_block_id ) );
	} finally {
		wp_delete_post( (int) $jn_sync_block_id, true );
	}
}
jn_step10_assert( 1 === count( $jn_synced_faq ) && 'Câu hỏi đồng bộ?' === $jn_synced_faq[0]['question'], 'extract_faq_from_blocks resolves synced pattern core/block refs' );

// -----------------------------------------------------------------------------
// 4. F3 — robots.txt single source of truth
// -----------------------------------------------------------------------------
echo "\n--- 4. robots.txt (F3) ---\n";
$jn_prod_robots = JN_SEO_Robots::build_robots_txt( true );
jn_step10_assert( false === strpos( $jn_prod_robots, 'phong-lam-viec' ), 'Production robots.txt does not reveal the hidden login slug' );
foreach ( array( '/api/', '/*?add-to-cart=', '/*?jn_bf=' ) as $jn_rule ) {
	jn_step10_assert( false !== strpos( $jn_prod_robots, "Disallow: {$jn_rule}\n" ), "Production robots.txt disallows {$jn_rule}" );
}
foreach ( array( '/cart/', '/checkout/', '/tai-khoan/' ) as $jn_rule ) {
	jn_step10_assert( false === strpos( $jn_prod_robots, "Disallow: {$jn_rule}" ), "Production robots.txt does NOT disallow noindex page {$jn_rule} (crawlers must see the noindex)" );
}
foreach ( array( 'GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'PerplexityBot', 'Google-Extended' ) as $jn_bot ) {
	jn_step10_assert( false !== strpos( $jn_prod_robots, "User-agent: {$jn_bot}\n" ), "Production robots.txt names AI crawler {$jn_bot} in the shared rule group" );
}
jn_step10_assert( false === strpos( $jn_prod_robots, "Disallow: /\n" ), 'Production robots.txt never disallows the whole site' );
jn_step10_assert( false !== strpos( $jn_prod_robots, 'Sitemap: ' . home_url( '/sitemap.xml' ) ), 'Production robots.txt links the sitemap from home_url()' );
jn_step10_assert( false === strpos( $jn_prod_robots, 'wp-' ), 'Production robots.txt exposes zero wp-* paths' );
jn_step10_assert( false !== strpos( JN_SEO_Robots::build_robots_txt( false ), "Disallow: /\n" ), 'Non-production robots.txt disallows everything' );
jn_step10_assert( trim( (string) apply_filters( 'robots_txt', '', '1' ) ) === trim( $jn_prod_robots ), 'Core robots_txt filter returns the same production body' );
jn_step10_assert( JN_SEO_Robots::emulates_production(), 'Local environment emulates the production robots policy (staging/production respect blog_public)' );

// -----------------------------------------------------------------------------
// 5. F5 — Contextual fallback meta
// -----------------------------------------------------------------------------
echo "\n--- 5. Meta map & fallbacks (F5) ---\n";
$jn_map = JN_SEO_Meta::get_page_map();
$jn_titles = wp_list_pluck( $jn_map, 'title' );
jn_step10_assert( count( $jn_titles ) === count( array_unique( $jn_titles ) ), 'Every mapped page has a unique title' );
$jn_descs = wp_list_pluck( $jn_map, 'description' );
jn_step10_assert( count( $jn_descs ) === count( array_unique( $jn_descs ) ), 'Every mapped page has a unique meta description' );
$jn_search_meta = JN_SEO_Meta::get_search_meta( 'spa' );
jn_step10_assert( false !== strpos( $jn_search_meta['title'], 'spa' ) && $jn_search_meta['title'] !== $jn_map['front']['title'], 'Search results get their own title instead of the homepage title' );

// -----------------------------------------------------------------------------
// 6. llms.txt builder
// -----------------------------------------------------------------------------
echo "\n--- 6. llms.txt builder (F7) ---\n";
$jn_llms = JN_SEO_Llms::build_llms_txt();
jn_step10_assert( 0 === strpos( $jn_llms, '# Jannie Nguyễn' ), 'llms.txt starts with an H1 brand title' );
jn_step10_assert( false !== strpos( $jn_llms, "\n> " ), 'llms.txt has a blockquote summary' );
jn_step10_assert( false !== strpos( $jn_llms, home_url( '/coaching/' ) ), 'llms.txt links the canonical /coaching/ page' );
jn_step10_assert( false === strpos( $jn_llms, '/cart/' ) && false === strpos( $jn_llms, '/tai-khoan/' ), 'llms.txt lists no utility pages' );
jn_step10_assert( false === strpos( $jn_llms, 'wp-' ) && false === strpos( $jn_llms, 'WordPress' ), 'llms.txt reveals no WordPress traces' );
jn_step10_assert( false === strpos( $jn_llms, '908' ) && false === stripos( $jn_llms, 'SUNEXT' ), 'llms.txt contains no invented phone or template residue' );
jn_step10_assert( false !== strpos( $jn_llms, '## Câu hỏi thường gặp' ) && false !== strpos( $jn_llms, 'Buổi tư vấn 1:1 cùng Jannie Nguyễn diễn ra ở đâu?' ), 'llms.txt exports published FAQ section from /coaching/' );

// -----------------------------------------------------------------------------
// 7. HTTP integration (rendered pages)
// -----------------------------------------------------------------------------
echo "\n--- 7. Rendered pages (F2, F4, F9–F12) ---\n";

/**
 * Purge WP Super Cache (same routine as scripts/build-step-8.php) so the latest markup is tested.
 *
 * @return void
 */
function jn_step10_purge_page_cache(): void {
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

/**
 * Count tags in HTML regardless of single/double attribute quotes.
 *
 * @param string $html HTML.
 * @param string $kind `robots`, `noindex`, `canonical` or `description`.
 * @return int
 */
function jn_step10_count( string $html, string $kind ): int {
	$patterns = array(
		'robots'      => '#<meta name=["\']robots["\']#',
		'noindex'     => '#<meta name=["\']robots["\'] content=["\'][^"\']*noindex#',
		'canonical'   => '#<link[^>]+rel=["\']canonical["\']#',
		'description' => '#<meta[^>]+name=["\']description["\']#',
	);
	return (int) preg_match_all( $patterns[ $kind ], $html );
}

jn_step10_purge_page_cache();

$jn_content_pages = array( '/', '/coaching/', '/my-story/', '/course/', '/client-success-story/', '/booking/', '/my-favorite-product/', '/chinh-sach-bao-mat/', '/dieu-khoan-dich-vu/' );
foreach ( $jn_content_pages as $jn_path ) {
	$jn_body = wp_remote_retrieve_body( jn_step10_http_get( $jn_path ) );
	preg_match( '#<title>(.*?)</title>#s', $jn_body, $jn_t );
	$jn_title = html_entity_decode( $jn_t[1] ?? '', ENT_QUOTES, 'UTF-8' );
	$jn_ld    = jn_step10_jsonld_nodes( $jn_body );
	jn_step10_assert(
		1 === jn_step10_count( $jn_body, 'canonical' )
		&& 1 === jn_step10_count( $jn_body, 'description' )
		&& 1 === $jn_ld['blocks']
		&& 0 === jn_step10_count( $jn_body, 'noindex' )
		&& false === stripos( $jn_title, 'JANNIE NGUYEN' )
		&& mb_strlen( $jn_title ) <= 60,
		"{$jn_path}: one canonical, one description, one JSON-LD graph, indexable, title ≤ 60 chars without duplicate branding"
	);
}

$jn_story = jn_step10_jsonld_nodes( wp_remote_retrieve_body( jn_step10_http_get( '/my-story/' ) ) );
jn_step10_assert( array() === array_diff( array( 'ProfilePage', 'BreadcrumbList', 'Person' ), jn_step10_types( $jn_story['nodes'] ) ), '/my-story/ renders ProfilePage + BreadcrumbList + Person' );

foreach ( array( '/coaching/', '/booking/' ) as $jn_faq_route ) {
	$jn_route_ld  = jn_step10_jsonld_nodes( wp_remote_retrieve_body( jn_step10_http_get( $jn_faq_route ) ) );
	$jn_faq_nodes = array_values( array_filter( $jn_route_ld['nodes'], static fn( $n ) => in_array( 'FAQPage', (array) ( $n['@type'] ?? array() ), true ) ) );
	$jn_faq_count = isset( $jn_faq_nodes[0]['mainEntity'] ) && is_array( $jn_faq_nodes[0]['mainEntity'] ) ? count( $jn_faq_nodes[0]['mainEntity'] ) : 0;
	jn_step10_assert( 1 === count( $jn_faq_nodes ) && 5 === $jn_faq_count, "{$jn_faq_route} renders FAQPage node with all 5 approved Group-A Q&As" );
}

$jn_shop_html = wp_remote_retrieve_body( jn_step10_http_get( '/my-favorite-product/' ) );
jn_step10_assert( 1 === preg_match_all( '#<h1[\s>]#', $jn_shop_html ), '/my-favorite-product/ (shop page) renders exactly one H1 (Blocksy archive hero suppressed)' );
$jn_search_html = wp_remote_retrieve_body( jn_step10_http_get( '/?s=spa' ) );
jn_step10_assert( 1 === jn_step10_count( $jn_search_html, 'description' ), 'Search results page carries exactly one meta description' );

foreach ( array( '/cart/', '/tai-khoan/', '/?s=spa' ) as $jn_path ) {
	$jn_body = wp_remote_retrieve_body( jn_step10_http_get( $jn_path ) );
	jn_step10_assert(
		1 === jn_step10_count( $jn_body, 'noindex' )
		&& jn_step10_count( $jn_body, 'canonical' ) <= 1
		&& jn_step10_count( $jn_body, 'description' ) <= 1,
		"{$jn_path}: noindex and no duplicate canonical/description tags"
	);
}

// Production parity: with blog_public = 1, TSF prints robots itself — still exactly one robots tag.
$jn_blog_public = get_option( 'blog_public' );
try {
	update_option( 'blog_public', '1' );
	jn_step10_purge_page_cache();
	foreach ( array( '/' => false, '/coaching/' => false, '/cart/' => true, '/tai-khoan/' => true ) as $jn_path => $jn_expect_noindex ) {
		$jn_body = wp_remote_retrieve_body( jn_step10_http_get( $jn_path ) );
		jn_step10_assert(
			1 === jn_step10_count( $jn_body, 'robots' ) && ( $jn_expect_noindex ? 1 : 0 ) === jn_step10_count( $jn_body, 'noindex' ),
			"blog_public=1 {$jn_path}: exactly one robots meta tag, " . ( $jn_expect_noindex ? 'noindex' : 'indexable' )
		);
	}
} finally {
	update_option( 'blog_public', $jn_blog_public );
	jn_step10_purge_page_cache();
}

$jn_sitemap = wp_remote_retrieve_body( jn_step10_http_get( '/sitemap.xml' ) );
$jn_in_map  = 0;
foreach ( $jn_content_pages as $jn_path ) {
	$jn_in_map += false !== strpos( $jn_sitemap, '<loc>' . home_url( $jn_path ) . '</loc>' ) ? 1 : 0;
}
jn_step10_assert( count( $jn_content_pages ) === $jn_in_map && false === strpos( $jn_sitemap, '/cart/' ) && false === strpos( $jn_sitemap, '/tai-khoan/' ), 'sitemap.xml lists all 9 indexable pages and no noindex page' );

$jn_lh_robots = wp_remote_retrieve_body( jn_step10_http_get( '/robots.txt', array( 'User-Agent' => 'Mozilla/5.0 Chrome-Lighthouse' ) ) );
jn_step10_assert( trim( $jn_lh_robots ) === trim( $jn_prod_robots ), 'Lighthouse receives exactly the production robots.txt body (audits what ships)' );
$jn_local_robots = wp_remote_retrieve_body( jn_step10_http_get( '/robots.txt' ) );
jn_step10_assert( false !== strpos( $jn_local_robots, "Disallow: /\n" ), 'Local robots.txt keeps the disallow-all policy for normal visitors' );

$jn_llms_res = jn_step10_http_get( '/llms.txt' );
jn_step10_assert(
	200 === wp_remote_retrieve_response_code( $jn_llms_res )
	&& false !== strpos( (string) wp_remote_retrieve_header( $jn_llms_res, 'content-type' ), 'text/plain' )
	&& trim( wp_remote_retrieve_body( $jn_llms_res ) ) === trim( $jn_llms ),
	'/llms.txt is served as text/plain with the builder output'
);

// -----------------------------------------------------------------------------
// 8. Post-Step-13 Deep SEO/AEO Hardening (sunext-semrush-ahrefs-os Audit)
// -----------------------------------------------------------------------------
echo "\n--- 8. Deep SEO/AEO Hardening (Cache, Social Meta, Search Hygiene, Entity Graph) ---\n";

// 8a. advanced-cache.php must bypass cache for /llms.txt as well as /robots.txt and /sitemap.xml.
$jn_adv_cache = (string) file_get_contents( WP_CONTENT_DIR . '/advanced-cache.php' );
jn_step10_assert(
	false !== strpos( $jn_adv_cache, 'llms\.txt' ),
	'advanced-cache.php explicitly excludes /llms.txt from page caching'
);

// 8b. Expanded AI crawler coverage in production robots.txt & docs/jn/robots.production.txt parity.
foreach ( array( 'Claude-Web', 'anthropic-ai', 'GoogleOther', 'Meta-ExternalAgent', 'Amazonbot' ) as $jn_extra_bot ) {
	jn_step10_assert(
		false !== strpos( $jn_prod_robots, "User-agent: {$jn_extra_bot}\n" ),
		"Production robots.txt names AI crawler {$jn_extra_bot}"
	);
}
$jn_doc_robots_path = dirname( ABSPATH ) . '/docs/jn/robots.production.txt';
if ( is_readable( $jn_doc_robots_path ) ) {
	$jn_doc_robots = str_replace( 'https://PROD_DOMAIN', untrailingslashit( home_url() ), (string) file_get_contents( $jn_doc_robots_path ) );
	jn_step10_assert(
		trim( $jn_doc_robots ) === trim( $jn_prod_robots ),
		'docs/jn/robots.production.txt matches JN_SEO_Robots::build_robots_txt(true) byte-for-byte'
	);
}

// 8c. Internal search (?s=coaching) must exclude noindex utility pages (/cart/, /checkout/, /tai-khoan/).
$jn_search_coaching_html = wp_remote_retrieve_body( jn_step10_http_get( '/?s=coaching' ) );
jn_step10_assert(
	false === strpos( $jn_search_coaching_html, 'href="' . esc_url( home_url( '/cart/' ) ) . '"' )
	&& false === strpos( $jn_search_coaching_html, '>Giỏ hàng<' ),
	'Internal search (?s=coaching) excludes noindex utility page /cart/ (Giỏ hàng) from search results'
);
jn_step10_assert(
	1 === preg_match_all( '#<meta[^>]+name=["\']twitter:card["\']#i', $jn_search_coaching_html ),
	'Internal search (?s=coaching) includes twitter:card meta tag'
);

// 8d. Social meta completeness on content pages: og:site_name diacritics, og:image:width/height/alt, twitter:image:alt.
$jn_home_html = wp_remote_retrieve_body( jn_step10_http_get( '/' ) );
jn_step10_assert(
	1 === preg_match( '#<meta[^>]+property=["\']og:site_name["\'][^>]+content=["\']Jannie Nguyễn["\']#u', $jn_home_html ),
	'Home page og:site_name uses proper Vietnamese diacritics ("Jannie Nguyễn")'
);
jn_step10_assert(
	1 === preg_match( '#<meta[^>]+property=["\']og:image:width["\'][^>]+content=["\']\d+["\']#i', $jn_home_html )
	&& 1 === preg_match( '#<meta[^>]+property=["\']og:image:height["\'][^>]+content=["\']\d+["\']#i', $jn_home_html )
	&& 1 === preg_match( '#<meta[^>]+property=["\']og:image:alt["\'][^>]+content=["\'][^"\']+["\']#i', $jn_home_html )
	&& 1 === preg_match( '#<meta[^>]+name=["\']twitter:image:alt["\'][^>]+content=["\'][^"\']+["\']#i', $jn_home_html ),
	'Home page emits og:image:width, og:image:height, og:image:alt, and twitter:image:alt'
);

// 8e. Person node includes verified portrait ImageObject, award, and knowsLanguage; WebPage includes dateModified & speakable.
$jn_home_ld     = jn_step10_jsonld_nodes( $jn_home_html );
$jn_person_node = current( array_filter( $jn_home_ld['nodes'], static fn( $n ) => in_array( 'Person', (array) ( $n['@type'] ?? array() ), true ) ) );
$jn_page_node   = current( array_filter( $jn_home_ld['nodes'], static fn( $n ) => in_array( 'WebPage', (array) ( $n['@type'] ?? array() ), true ) ) );
jn_step10_assert(
	is_array( $jn_person_node )
	&& isset( $jn_person_node['image']['url'], $jn_person_node['award'], $jn_person_node['knowsLanguage'] )
	&& false !== strpos( (string) $jn_person_node['image']['url'], 'jn-hero-portrait.webp' ),
	'Person JSON-LD node includes verified real portrait ImageObject (jn-hero-portrait.webp), award, and knowsLanguage'
);
jn_step10_assert(
	is_array( $jn_page_node )
	&& ! empty( $jn_page_node['datePublished'] )
	&& ! empty( $jn_page_node['dateModified'] )
	&& isset( $jn_page_node['speakable']['@type'] )
	&& 'SpeakableSpecification' === $jn_page_node['speakable']['@type'],
	'WebPage JSON-LD node includes ISO-8601 datePublished, dateModified, and SpeakableSpecification'
);

// 8f. Single WooCommerce Product JSON-LD unification inside @graph (no duplicate WC footer JSON-LD).
$jn_prod_ctx_graph = JN_SEO_Schema::build_graph(
	array(
		'canonical'    => home_url( '/product/test-item/' ),
		'title'        => 'Sản phẩm thử nghiệm | Jannie Nguyễn',
		'description'  => 'Mô tả sản phẩm thử nghiệm.',
		'page_type'    => 'ItemPage',
		'breadcrumbs'  => array(
			array( 'name' => 'Trang chủ', 'url' => home_url( '/' ) ),
			array( 'name' => 'Sản phẩm thử nghiệm', 'url' => home_url( '/product/test-item/' ) ),
		),
		'service_page' => false,
		'faq'          => array(),
		'product'      => array(
			'name'        => 'Sản phẩm thử nghiệm',
			'description' => 'Mô tả sản phẩm thử nghiệm.',
			'sku'         => 'JN-TEST-01',
			'image'       => home_url( '/media/2026/10/jn-hero-portrait.webp' ),
			'price'       => '450000',
			'currency'    => 'VND',
			'in_stock'    => true,
		),
	)
);
$jn_prod_nodes = $jn_prod_ctx_graph['@graph'];
$jn_prod_node  = current( array_filter( $jn_prod_nodes, static fn( $n ) => in_array( 'Product', (array) ( $n['@type'] ?? array() ), true ) ) );
$jn_item_page  = current( array_filter( $jn_prod_nodes, static fn( $n ) => in_array( 'ItemPage', (array) ( $n['@type'] ?? array() ), true ) ) );
jn_step10_assert(
	is_array( $jn_prod_node )
	&& is_array( $jn_item_page )
	&& ( $jn_item_page['mainEntity']['@id'] ?? '' ) === ( $jn_prod_node['@id'] ?? '' )
	&& '450000' === ( $jn_prod_node['offers']['price'] ?? '' )
	&& ! isset( $jn_prod_node['aggregateRating'] )
	&& ! isset( $jn_prod_node['review'] ),
	'Product node is unified inside @graph, linked via ItemPage.mainEntity, and omits unverified review/rating schema'
);

// 8g. llms.txt includes Sitemap endpoint reference.
jn_step10_assert(
	false !== strpos( $jn_llms, home_url( '/sitemap.xml' ) ),
	'llms.txt references canonical /sitemap.xml for AI crawler discovery'
);

echo "\nRESULT: {$GLOBALS['jn_passed']} passed, {$GLOBALS['jn_failed']} failed\n";
