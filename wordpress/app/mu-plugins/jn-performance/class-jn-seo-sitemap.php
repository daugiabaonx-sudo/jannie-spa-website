<?php
/**
 * JN SEO — Clean `/sitemap.xml` (audit finding F4: sitemap ↔ robots meta ↔ robots.txt consistency).
 *
 * Lists exactly the indexable pages (every mapped page except the commerce utility routes) plus
 * published non-coaching products. Private CPTs (`jn_booking`, `jn_testimonial`) never appear.
 *
 * @package JN_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * XML sitemap.
 */
class JN_SEO_Sitemap {

	/**
	 * Page slugs that carry `noindex` and must never be listed.
	 *
	 * @var array<int, string>
	 */
	const NOINDEX_SLUGS = array( 'cart', 'checkout', 'tai-khoan' );

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'serve' ), 1 );
		add_action( 'save_post', array( __CLASS__, 'flush_cache' ) );
		add_action( 'deleted_post', array( __CLASS__, 'flush_cache' ) );
	}

	/**
	 * Transient key for the cached URL list.
	 *
	 * @var string
	 */
	const CACHE_KEY = 'jn_seo_sitemap_urls';

	/**
	 * Drop the cached URL list whenever content changes.
	 *
	 * @return void
	 */
	public static function flush_cache(): void {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Return the indexable page slugs ('' = front page).
	 *
	 * @return array<int, string>
	 */
	public static function get_indexable_slugs(): array {
		$slugs = array_diff( array_keys( JN_SEO_Meta::get_page_map() ), self::NOINDEX_SLUGS, array( 'front' ) );
		return array_merge( array( '' ), array_values( $slugs ) );
	}

	/**
	 * Collect sitemap entries.
	 *
	 * @return array<int, array{loc: string, lastmod: string}>
	 */
	public static function get_urls(): array {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$urls = array();
		foreach ( self::get_indexable_slugs() as $slug ) {
			$page = '' === $slug ? get_post( (int) get_option( 'page_on_front' ) ) : get_page_by_path( $slug );
			if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) {
				continue;
			}
			$urls[] = array(
				'loc'     => '' === $slug ? home_url( '/' ) : (string) get_permalink( $page ),
				'lastmod' => (string) get_post_modified_time( 'c', true, $page ),
			);
		}

		$products = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'no_found_rows'  => true,
				'has_password'   => false,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_jn_coaching_package',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
		foreach ( $products as $product ) {
			$wc_product = function_exists( 'wc_get_product' ) ? wc_get_product( $product ) : null;
			$hidden     = $wc_product && 'hidden' === $wc_product->get_catalog_visibility();
			$noindex    = (int) get_post_meta( $product->ID, '_genesis_noindex', true ) === 1; // TSF per-post noindex.
			if ( $hidden || $noindex ) {
				continue;
			}
			$urls[] = array(
				'loc'     => (string) get_permalink( $product ),
				'lastmod' => (string) get_post_modified_time( 'c', true, $product ),
			);
		}
		set_transient( self::CACHE_KEY, $urls, 12 * HOUR_IN_SECONDS );
		return $urls;
	}

	/**
	 * Serve `/sitemap.xml` before WordPress routing.
	 *
	 * @return void
	 */
	public static function serve(): void {
		if ( is_admin() || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$req_path = trim( (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ), '/' );
		if ( 'sitemap.xml' !== $req_path ) {
			return;
		}

		status_header( 200 );
		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow' );

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		foreach ( self::get_urls() as $entry ) {
			echo "\t<url>\n";
			echo "\t\t<loc>" . esc_url( $entry['loc'] ) . "</loc>\n";
			echo "\t\t<lastmod>" . esc_html( $entry['lastmod'] ) . "</lastmod>\n";
			echo "\t</url>\n";
		}
		echo "</urlset>\n";
		exit;
	}
}
