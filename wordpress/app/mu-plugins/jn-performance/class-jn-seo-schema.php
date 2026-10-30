<?php
/**
 * JN SEO — Unified Schema.org JSON-LD `@graph` (audit findings F1, F6, F7, F12).
 *
 * One graph per page (The SEO Framework's own graph is switched off so there are no duplicate or
 * disconnected WebPage/WebSite entities): WebSite → WebPage/ProfilePage/CollectionPage →
 * BreadcrumbList, Organization ↔ Person (Jannie Nguyễn), Service ×3 on service pages, and FAQPage
 * only when the page contains owner-published FAQ blocks (`core/details` + class `jn-faq-item`).
 *
 * Never emitted: Review / AggregateRating for self-published testimonials, unverified NAP data
 * (telephone/street address), priceRange or Offer prices that are not public yet.
 *
 * @package JN_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * JSON-LD graph builder.
 */
class JN_SEO_Schema {

	/**
	 * Marker that keeps editor drafts out of structured data.
	 *
	 * @var string
	 */
	const DRAFT_MARKER = '[NHÁP]';

	/**
	 * Page slug → schema.org page type.
	 *
	 * @var array<string, string>
	 */
	const PAGE_TYPES = array(
		'my-story'            => 'ProfilePage',
		'my-favorite-product' => 'CollectionPage',
	);

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'the_seo_framework_schema_graph_data', '__return_empty_array', 999 );
		add_filter( 'woocommerce_structured_data_product', array( __CLASS__, 'strip_wc_review_schema' ), 99 );
		add_action( 'template_redirect', array( __CLASS__, 'disable_wc_separate_schema' ), 20 );
		add_action( 'wp_head', array( __CLASS__, 'print_graph' ), 2 );
	}

	/**
	 * Unhook WooCommerce's separate footer JSON-LD on single product pages so only our unified `@graph` is printed.
	 *
	 * @return void
	 */
	public static function disable_wc_separate_schema(): void {
		if ( ! is_singular( 'product' ) || ! function_exists( 'WC' ) ) {
			return;
		}
		$wc = WC();
		if ( isset( $wc->structured_data ) && is_object( $wc->structured_data ) ) {
			remove_action( 'woocommerce_single_product_summary', array( $wc->structured_data, 'generate_product_data' ), 60 );
			remove_action( 'wp_footer', array( $wc->structured_data, 'output_structured_data' ), 10 );
		}
	}

	/**
	 * Build the graph for a page context.
	 *
	 * @param array{canonical: string, title: string, description: string, page_type: string,
	 *              breadcrumbs: array<int, array{name: string, url: string}>, service_page: bool,
	 *              faq: array<int, array{question: string, answer: string}>,
	 *              date_published?: string, date_modified?: string,
	 *              product?: array<string, mixed>|null} $ctx Page context.
	 * @return array{"@context": string, "@graph": array<int, array<string, mixed>>}
	 */
	public static function build_graph( array $ctx ): array {
		$home    = home_url( '/' );
		$ids     = array(
			'website' => $home . '#website',
			'org'     => $home . '#organization',
			'person'  => $home . '#jannie-nguyen',
			'page'    => $ctx['canonical'] . '#webpage',
			'crumbs'  => $ctx['canonical'] . '#breadcrumb',
			'product' => $ctx['canonical'] . '#product',
		);
		$brand   = JN_SEO_Brand::get();
		$same_as = $brand['same_as'] ? array( 'sameAs' => $brand['same_as'] ) : array();

		$graph = array(
			array(
				'@type'      => 'WebSite',
				'@id'        => $ids['website'],
				'url'        => $home,
				'name'       => $brand['name'],
				'inLanguage' => 'vi-VN',
				'publisher'  => array( '@id' => $ids['org'] ),
			),
			self::organization_node( $brand, $ids ) + $same_as,
			self::person_node( $brand, $ids ) + $same_as,
			self::webpage_node( $ctx, $ids ),
		);

		if ( ! empty( $ctx['breadcrumbs'] ) ) {
			$graph[] = self::breadcrumb_node( $ctx['breadcrumbs'], $ids['crumbs'] );
		}
		if ( ! empty( $ctx['service_page'] ) ) {
			$graph = array_merge( $graph, self::service_nodes( $ids ) );
		}
		if ( ! empty( $ctx['product'] ) && is_array( $ctx['product'] ) ) {
			$graph[] = self::product_node( $ctx['product'], $ctx['canonical'], $ids );
		}
		if ( ! empty( $ctx['faq'] ) ) {
			$graph[] = self::faq_node( $ctx['faq'], $ctx['canonical'], $ids['website'] );
		}

		return array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);
	}

	/**
	 * Person node for Jannie Nguyễn with verified portrait ImageObject and credentials.
	 *
	 * @param array<string, mixed>  $brand Brand facts.
	 * @param array<string, string> $ids   Node ids.
	 * @return array<string, mixed>
	 */
	private static function person_node( array $brand, array $ids ): array {
		$media_map    = get_option( 'jn_step3_media_map', array() );
		$portrait_url = isset( $media_map['hero_portrait']['url'] ) && '' !== (string) $media_map['hero_portrait']['url']
			? (string) $media_map['hero_portrait']['url']
			: home_url( '/media/2026/10/jn-hero-portrait.webp' );

		return array(
			'@type'         => 'Person',
			'@id'           => $ids['person'],
			'name'          => $brand['name'],
			'jobTitle'      => $brand['job_title'],
			'description'   => $brand['person_description'],
			'image'         => array(
				'@type'   => 'ImageObject',
				'url'     => $portrait_url,
				'width'   => 800,
				'height'  => 1000,
				'caption' => $brand['name'],
			),
			'award'         => 'Nghệ nhân Bàn Tay Vàng — Oscar for Beauty Vietnam',
			'knowsAbout'    => array( 'Điêu khắc thẩm mỹ', 'Phun xăm tự nhiên', 'Vận hành cơ sở thẩm mỹ & spa' ),
			'knowsLanguage' => array( 'vi-VN' ),
			'url'           => home_url( '/my-story/' ),
			'worksFor'      => array( '@id' => $ids['org'] ),
		);
	}

	/**
	 * Organization node (plain Organization until a full street address + phone make LocalBusiness valid).
	 *
	 * @param array<string, mixed>  $brand Brand facts.
	 * @param array<string, string> $ids   Node ids.
	 * @return array<string, mixed>
	 */
	private static function organization_node( array $brand, array $ids ): array {
		$node = array(
			'@type'         => 'Organization',
			'@id'           => $ids['org'],
			'name'          => $brand['org_name'],
			'alternateName' => $brand['org_alternate_names'], // All in-use spellings resolve to this entity (F14-02).
			'url'           => home_url( '/' ),
			'image'         => JN_SEO_Meta::get_default_og_image()['url'],
			'email'         => $brand['email'],
			'address'       => array(
				'@type'           => 'PostalAddress',
				'addressLocality' => $brand['locality'],
				'addressCountry'  => $brand['country'],
			),
			'founder'       => array( '@id' => $ids['person'] ),
		);
		if ( '' !== $brand['logo'] ) {
			$node['logo'] = array(
				'@type' => 'ImageObject',
				'url'   => $brand['logo'],
			);
		}
		if ( '' !== $brand['telephone'] ) {
			$node['telephone'] = $brand['telephone'];
		}
		return $node;
	}

	/**
	 * WebPage-family node.
	 *
	 * @param array<string, mixed>  $ctx Page context.
	 * @param array<string, string> $ids Node ids.
	 * @return array<string, mixed>
	 */
	private static function webpage_node( array $ctx, array $ids ): array {
		$node = array(
			'@type'       => $ctx['page_type'],
			'@id'         => $ids['page'],
			'url'         => $ctx['canonical'],
			'name'        => $ctx['title'],
			'description' => $ctx['description'],
			'inLanguage'  => 'vi-VN',
			'isPartOf'    => array( '@id' => $ids['website'] ),
			'about'       => array( '@id' => $ids['org'] ),
		);
		if ( 'ProfilePage' === $ctx['page_type'] ) {
			$node['mainEntity'] = array( '@id' => $ids['person'] );
		} elseif ( ! empty( $ctx['product'] ) && is_array( $ctx['product'] ) ) {
			$node['mainEntity'] = array( '@id' => $ids['product'] );
		}
		if ( ! empty( $ctx['breadcrumbs'] ) ) {
			$node['breadcrumb'] = array( '@id' => $ids['crumbs'] );
		}
		if ( ! empty( $ctx['date_published'] ) ) {
			$node['datePublished'] = (string) $ctx['date_published'];
		}
		if ( ! empty( $ctx['date_modified'] ) ) {
			$node['dateModified'] = (string) $ctx['date_modified'];
		}
		if ( 'SearchResultsPage' !== $ctx['page_type'] ) {
			$node['speakable'] = array(
				'@type'       => 'SpeakableSpecification',
				'cssSelector' => array( 'h1', '.jn-hero-lead', '.jn-page-intro-lead', '.jn-faq-item summary' ),
			);
		}
		if ( '' === $node['description'] ) {
			unset( $node['description'] );
		}
		return $node;
	}

	/**
	 * Product node unified inside `@graph` (omits unverified review/rating schema).
	 *
	 * @param array<string, mixed>  $product   Product data.
	 * @param string                $canonical Canonical product URL.
	 * @param array<string, string> $ids       Node ids.
	 * @return array<string, mixed>
	 */
	private static function product_node( array $product, string $canonical, array $ids ): array {
		$node = array(
			'@type' => 'Product',
			'@id'   => $ids['product'],
			'name'  => (string) ( $product['name'] ?? '' ),
			'url'   => $canonical,
			'brand' => array( '@id' => $ids['org'] ),
		);
		if ( ! empty( $product['description'] ) ) {
			$node['description'] = (string) $product['description'];
		}
		if ( ! empty( $product['sku'] ) ) {
			$node['sku'] = (string) $product['sku'];
		}
		if ( ! empty( $product['image'] ) ) {
			$node['image'] = (string) $product['image'];
		}
		if ( isset( $product['price'] ) && '' !== (string) $product['price'] && is_numeric( $product['price'] ) && (float) $product['price'] > 0 ) {
			$node['offers'] = array(
				'@type'         => 'Offer',
				'url'           => $canonical,
				'price'         => (string) $product['price'],
				'priceCurrency' => ! empty( $product['currency'] ) ? (string) $product['currency'] : 'VND',
				'availability'  => ! empty( $product['in_stock'] ) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
				'seller'        => array( '@id' => $ids['org'] ),
			);
		}
		return $node;
	}

	/**
	 * BreadcrumbList node.
	 *
	 * @param array<int, array{name: string, url: string}> $crumbs Trail from home to current page.
	 * @param string                                        $id     Node id.
	 * @return array<string, mixed>
	 */
	private static function breadcrumb_node( array $crumbs, string $id ): array {
		$items = array();
		foreach ( array_values( $crumbs ) as $i => $crumb ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $crumb['name'],
				'item'     => $crumb['url'],
			);
		}
		return array(
			'@type'           => 'BreadcrumbList',
			'@id'             => $id,
			'itemListElement' => $items,
		);
	}

	/**
	 * Service nodes for the 3 coaching packages (Offer only when a real price is published).
	 *
	 * @param array<string, string> $ids Node ids.
	 * @return array<int, array<string, mixed>>
	 */
	private static function service_nodes( array $ids ): array {
		$nodes = array();
		foreach ( JN_SEO_Brand::get_packages() as $key => $pkg ) {
			$node  = array(
				'@type'       => 'Service',
				'@id'         => home_url( '/coaching/#service-' . $key ),
				'name'        => $pkg['name'],
				'description' => $pkg['description'],
				'serviceType' => 'Cố vấn 1:1 ngành thẩm mỹ',
				'url'         => $pkg['url'],
				'provider'    => array( '@id' => $ids['org'] ),
				'areaServed'  => array(
					'@type' => 'Country',
					'name'  => 'Việt Nam',
				),
			);
			$price = JN_SEO_Brand::get_package_price( $key );
			if ( null !== $price ) {
				$node['offers'] = array(
					'@type'         => 'Offer',
					'url'           => $pkg['url'],
					'price'         => $price,
					'priceCurrency' => 'VND',
					'availability'  => 'https://schema.org/InStock',
				);
			}
			$nodes[] = $node;
		}
		return $nodes;
	}

	/**
	 * FAQPage node.
	 *
	 * @param array<int, array{question: string, answer: string}> $faq       FAQ entries.
	 * @param string                                              $canonical Page URL.
	 * @param string                                              $site_id   WebSite node id.
	 * @return array<string, mixed>
	 */
	private static function faq_node( array $faq, string $canonical, string $site_id ): array {
		$questions = array();
		foreach ( $faq as $entry ) {
			$questions[] = array(
				'@type'          => 'Question',
				'name'           => $entry['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $entry['answer'],
				),
			);
		}
		return array(
			'@type'      => 'FAQPage',
			'@id'        => $canonical . '#faq',
			'url'        => $canonical,
			'inLanguage' => 'vi-VN',
			'isPartOf'   => array( '@id' => $site_id ),
			'mainEntity' => $questions,
		);
	}

	/**
	 * Extract published FAQ entries from block markup (`core/details` with class `jn-faq-item`).
	 *
	 * @param string $content Post content (block markup).
	 * @return array<int, array{question: string, answer: string}>
	 */
	public static function extract_faq_from_blocks( string $content ): array {
		$entries   = array();
		$seen_refs = array();
		self::walk_blocks( parse_blocks( $content ), $entries, $seen_refs );
		return $entries;
	}

	/**
	 * Recursive block walker for extract_faq_from_blocks().
	 *
	 * @param array<int, array<string, mixed>>                    $blocks    Parsed blocks.
	 * @param array<int, array{question: string, answer: string}> $entries   Collected entries (by reference).
	 * @param array<int, bool>                                    $seen_refs Visited `wp_block` IDs to guard against cycles.
	 * @return void
	 */
	private static function walk_blocks( array $blocks, array &$entries, array &$seen_refs = array() ): void {
		foreach ( $blocks as $block ) {
			if ( 'core/block' === ( $block['blockName'] ?? '' ) && ! empty( $block['attrs']['ref'] ) ) {
				$ref_id = (int) $block['attrs']['ref'];
				if ( $ref_id > 0 && empty( $seen_refs[ $ref_id ] ) ) {
					$seen_refs[ $ref_id ] = true;
					$ref_post             = get_post( $ref_id );
					if ( $ref_post instanceof WP_Post && 'wp_block' === $ref_post->post_type && 'publish' === $ref_post->post_status ) {
						self::walk_blocks( parse_blocks( (string) $ref_post->post_content ), $entries, $seen_refs );
					}
				}
				continue;
			}
			$classes = (string) ( $block['attrs']['className'] ?? '' );
			if ( 'core/details' === $block['blockName'] && preg_match( '/(^|\s)jn-faq-item(\s|$)/', $classes ) ) {
				$html = self::block_html( $block );
				preg_match( '#<summary[^>]*>(.*?)</summary>#s', $html, $m );
				$question = trim( wp_strip_all_tags( $m[1] ?? '' ) );
				$answer   = trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) preg_replace( '#<summary[^>]*>.*?</summary>#s', '', $html ) ) ) );
				$is_draft = false !== strpos( $question . $answer, self::DRAFT_MARKER );
				if ( '' !== $question && '' !== $answer && ! $is_draft ) {
					$entries[] = array(
						'question' => $question,
						'answer'   => $answer,
					);
				}
				continue;
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				self::walk_blocks( $block['innerBlocks'], $entries, $seen_refs );
			}
		}
	}

	/**
	 * Reassemble a parsed block's raw HTML including its inner blocks.
	 *
	 * @param array<string, mixed> $block Parsed block.
	 * @return string
	 */
	private static function block_html( array $block ): string {
		$html  = '';
		$inner = 0;
		foreach ( (array) $block['innerContent'] as $chunk ) {
			if ( is_string( $chunk ) ) {
				$html .= $chunk;
			} elseif ( isset( $block['innerBlocks'][ $inner ] ) ) {
				$html .= self::block_html( $block['innerBlocks'][ $inner ] );
				++$inner;
			}
		}
		return $html;
	}

	/**
	 * Build the context for the current request.
	 *
	 * @return array<string, mixed>
	 */
	public static function current_context(): array {
		$meta      = JN_SEO_Meta::get_current();
		$canonical = JN_SEO_Meta::get_canonical_url();
		$queried   = get_queried_object();
		$slug      = $queried instanceof WP_Post ? $queried->post_name : '';
		$type      = self::PAGE_TYPES[ $slug ] ?? ( is_singular( 'product' ) ? 'ItemPage' : ( is_search() ? 'SearchResultsPage' : 'WebPage' ) );
		$crumbs    = array();
		$faq       = array();
		$date_pub  = '';
		$date_mod  = '';
		$product   = null;

		if ( $queried instanceof WP_Post ) {
			$faq      = self::extract_faq_from_blocks( (string) $queried->post_content );
			$date_pub = (string) get_post_time( 'c', true, $queried );
			$date_mod = (string) get_post_modified_time( 'c', true, $queried );
			if ( ! is_front_page() ) {
				$crumbs = array(
					array(
						'name' => 'Trang chủ',
						'url'  => home_url( '/' ),
					),
					array(
						'name' => get_the_title( $queried ),
						'url'  => $canonical,
					),
				);
			}
			if ( is_singular( 'product' ) && function_exists( 'wc_get_product' ) ) {
				$wc_product = wc_get_product( $queried->ID );
				if ( $wc_product instanceof WC_Product && JN_SEO_Sitemap::is_product_indexable( $wc_product ) ) {
					$img_id  = (int) $wc_product->get_image_id();
					$img_url = $img_id > 0 ? (string) wp_get_attachment_image_url( $img_id, 'full' ) : '';
					$product = array(
						'name'        => $wc_product->get_name(),
						'description' => $meta['description'],
						'sku'         => (string) $wc_product->get_sku(),
						'image'       => $img_url,
						'price'       => (string) $wc_product->get_price(),
						'currency'    => function_exists( 'get_woocommerce_currency' ) ? (string) get_woocommerce_currency() : 'VND',
						'in_stock'    => $wc_product->is_in_stock(),
					);
				}
			}
		}

		return array(
			'canonical'      => $canonical,
			'title'          => '' !== $meta['title'] ? $meta['title'] : wp_get_document_title(),
			'description'    => $meta['description'],
			'page_type'      => $type,
			'breadcrumbs'    => $crumbs,
			'service_page'   => is_front_page() || is_page( array( 'coaching', 'booking' ) ),
			'faq'            => $faq,
			'date_published' => $date_pub,
			'date_modified'  => $date_mod,
			'product'        => $product,
		);
	}

	/**
	 * Print the graph in `<head>`.
	 *
	 * @return void
	 */
	public static function print_graph(): void {
		if ( is_admin() || is_404() ) {
			return;
		}
		$json = wp_json_encode( self::build_graph( self::current_context() ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );
		if ( false === $json ) {
			return;
		}
		echo '<script type="application/ld+json">' . $json . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded with JSON_HEX_TAG.
	}

	/**
	 * Strip Review / AggregateRating from WooCommerce product structured data.
	 *
	 * @param array<string, mixed> $markup Structured data.
	 * @return array<string, mixed>
	 */
	public static function strip_wc_review_schema( $markup ) {
		if ( is_array( $markup ) ) {
			unset( $markup['aggregateRating'], $markup['review'] );
		}
		return $markup;
	}
}
