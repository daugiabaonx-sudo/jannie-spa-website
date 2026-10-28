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
		add_action( 'wp_head', array( __CLASS__, 'print_graph' ), 2 );
	}

	/**
	 * Build the graph for a page context.
	 *
	 * @param array{canonical: string, title: string, description: string, page_type: string,
	 *              breadcrumbs: array<int, array{name: string, url: string}>, service_page: bool,
	 *              faq: array<int, array{question: string, answer: string}>} $ctx Page context.
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
			array(
				'@type'       => 'Person',
				'@id'         => $ids['person'],
				'name'        => $brand['name'],
				'jobTitle'    => $brand['job_title'],
				'description' => $brand['person_description'],
				'knowsAbout'  => array( 'Điêu khắc thẩm mỹ', 'Phun xăm tự nhiên', 'Vận hành cơ sở thẩm mỹ & spa' ),
				'url'         => home_url( '/my-story/' ),
				'worksFor'    => array( '@id' => $ids['org'] ),
			) + $same_as,
			self::webpage_node( $ctx, $ids ),
		);

		if ( ! empty( $ctx['breadcrumbs'] ) ) {
			$graph[] = self::breadcrumb_node( $ctx['breadcrumbs'], $ids['crumbs'] );
		}
		if ( ! empty( $ctx['service_page'] ) ) {
			$graph = array_merge( $graph, self::service_nodes( $ids ) );
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
			'alternateName' => 'Jannie Nguyễn — Beauty Blog & Mentorship',
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
		}
		if ( ! empty( $ctx['breadcrumbs'] ) ) {
			$node['breadcrumb'] = array( '@id' => $ids['crumbs'] );
		}
		if ( '' === $node['description'] ) {
			unset( $node['description'] );
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
		$type      = self::PAGE_TYPES[ $slug ] ?? ( is_search() ? 'SearchResultsPage' : 'WebPage' );
		$crumbs    = array();
		$faq       = array();

		if ( $queried instanceof WP_Post ) {
			$faq = self::extract_faq_from_blocks( (string) $queried->post_content );
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
		}

		return array(
			'canonical'    => $canonical,
			'title'        => '' !== $meta['title'] ? $meta['title'] : wp_get_document_title(),
			'description'  => $meta['description'],
			'page_type'    => $type,
			'breadcrumbs'  => $crumbs,
			'service_page' => is_front_page() || is_page( array( 'coaching', 'booking' ) ),
			'faq'          => $faq,
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
