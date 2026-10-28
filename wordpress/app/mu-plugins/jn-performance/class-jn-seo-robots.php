<?php
/**
 * JN SEO — Robots meta policy and robots.txt (single source of truth, audit findings F2 & F3).
 *
 * - Content pages: `index, follow, max-snippet:-1, max-image-preview:large` (AEO-friendly snippets).
 * - Commerce utility routes (cart, checkout, order-pay, account) and internal search: `noindex, follow`.
 *   They are NOT disallowed in robots.txt, so crawlers can actually read the `noindex`. This
 *   intentionally fails Lighthouse `is-crawlable` on those 3 utility URLs — a documented
 *   WooCommerce exception (ADR-013); indexing a cart page is worse for SEO.
 * - Every other route keeps The SEO Framework's own decision (private/password pages, archives,
 *   per-post noindex set by the owner…).
 * - "Discourage search engines" (`blog_public = 0`) is respected everywhere except the `local`
 *   environment, which emulates the production policy so Lighthouse audits what ships.
 * - robots.txt: one builder used by the virtual `/robots.txt` route AND the core `robots_txt` filter.
 *   Production body mirrors docs/jn/robots.production.txt and explicitly welcomes AI answer engines.
 *
 * @package JN_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Robots policy.
 */
class JN_SEO_Robots {

	/**
	 * Paths disallowed for every crawler in production. The custom login slug is deliberately NOT
	 * listed (robots.txt is public — listing it would reveal the hidden login URL; the login page
	 * already sends `noindex` via core `wp_robots_sensitive_page`).
	 *
	 * @var array<int, string>
	 */
	const PRODUCTION_DISALLOW = array(
		'/api/',
		'/*?add-to-cart=',
		'/*?jn_bf=',
	);

	/**
	 * AI search / answer-engine crawlers explicitly named in the shared rule group (owner may prune).
	 *
	 * @var array<int, string>
	 */
	const AI_AGENTS = array(
		'OAI-SearchBot',
		'ChatGPT-User',
		'GPTBot',
		'PerplexityBot',
		'Perplexity-User',
		'ClaudeBot',
		'Claude-SearchBot',
		'Claude-User',
		'Google-Extended',
		'Applebot-Extended',
		'Bingbot',
	);

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'the_seo_framework_robots_meta_array', array( __CLASS__, 'filter_tsf_robots' ), 999, 2 );
		add_filter( 'wp_robots', array( __CLASS__, 'filter_wp_robots' ), 999 );
		add_filter( 'robots_txt', array( __CLASS__, 'filter_robots_txt' ), 999, 2 );
		add_action( 'init', array( __CLASS__, 'serve_robots_txt' ), 1 );
	}

	/**
	 * Whether the current frontend request must carry `noindex`.
	 *
	 * @return bool
	 */
	public static function is_noindex_request(): bool {
		if ( defined( 'JN_FORCE_LOCAL_NOINDEX' ) && JN_FORCE_LOCAL_NOINDEX ) {
			return true;
		}
		if ( is_search() ) {
			return true;
		}
		// Products left out of the sitemap (coaching packages, catalog-hidden, TSF noindex) are noindex too.
		if ( is_singular( 'product' ) && ! JN_SEO_Sitemap::is_product_indexable( get_queried_object_id() ) ) {
			return true;
		}
		$is_wc_utility = ( function_exists( 'is_cart' ) && is_cart() )
			|| ( function_exists( 'is_checkout' ) && is_checkout() )
			|| ( function_exists( 'is_account_page' ) && is_account_page() );
		return $is_wc_utility || is_page( array( 'cart', 'checkout', 'tai-khoan' ) );
	}

	/**
	 * Whether the current request is a page this site explicitly wants indexed.
	 *
	 * @return bool
	 */
	public static function is_indexable_request(): bool {
		if ( is_front_page() ) {
			return true;
		}
		if ( post_password_required() ) {
			return false;
		}
		if ( is_singular( 'product' ) ) {
			return JN_SEO_Sitemap::is_product_indexable( get_queried_object_id() );
		}
		$queried = get_queried_object();
		return is_page()
			&& $queried instanceof WP_Post
			&& 'publish' === $queried->post_status
			&& in_array( $queried->post_name, JN_SEO_Sitemap::get_indexable_slugs(), true );
	}

	/**
	 * Whether this environment emulates production robots meta while `blog_public` is 0.
	 *
	 * @return bool
	 */
	public static function emulates_production(): bool {
		return 'local' === wp_get_environment_type();
	}

	/**
	 * Apply the policy to The SEO Framework robots array (current query only).
	 *
	 * @param array<string, string> $meta Robots directives.
	 * @param array|null            $args TSF query args; non-null = another object (sitemap, admin bar).
	 * @return array<string, string>
	 */
	public static function filter_tsf_robots( $meta, $args = null ) {
		if ( ! is_array( $meta ) || null !== $args || is_admin() || is_404() ) {
			return $meta;
		}
		if ( self::is_noindex_request() ) {
			unset( $meta['nofollow'] );
			$meta['noindex'] = 'noindex';
			return $meta;
		}
		if ( ! self::is_indexable_request() ) {
			return $meta; // Keep TSF's own decision.
		}
		unset( $meta['noindex'], $meta['nofollow'] );
		$meta['max_snippet']       = 'max-snippet:-1';
		$meta['max_image_preview'] = 'max-image-preview:large';
		return $meta;
	}

	/**
	 * Whether The SEO Framework prints the robots meta tag itself (active plugin + public blog).
	 *
	 * @return bool
	 */
	public static function tsf_prints_robots(): bool {
		return function_exists( 'the_seo_framework' ) && the_seo_framework() && '1' === (string) get_option( 'blog_public' );
	}

	/**
	 * Apply the policy to core `wp_robots` — only when TSF does not print robots itself, so a page
	 * never carries two robots meta tags (TSF stays silent when the blog is not public, e.g. local).
	 *
	 * @param array<string, bool|string> $robots Robots directives.
	 * @return array<string, bool|string>
	 */
	public static function filter_wp_robots( $robots ) {
		$is_frontend = did_action( 'template_redirect' ) && ! did_action( 'login_init' );
		if ( ! is_array( $robots ) || ! $is_frontend || is_404() || self::tsf_prints_robots() ) {
			return $robots;
		}
		if ( '1' !== (string) get_option( 'blog_public' ) && ! self::emulates_production() ) {
			return $robots; // Respect "Discourage search engines" on staging / pre-launch production.
		}
		if ( self::is_noindex_request() ) {
			unset( $robots['nofollow'], $robots['index'] );
			$robots['noindex'] = true;
			$robots['follow']  = true;
			return $robots;
		}
		if ( ! self::is_indexable_request() ) {
			return $robots;
		}
		unset( $robots['nofollow'], $robots['noindex'] );
		$robots['index']             = true;
		$robots['follow']            = true;
		$robots['max-snippet']       = '-1';
		$robots['max-image-preview'] = 'large';
		return $robots;
	}

	/**
	 * Build the robots.txt body.
	 *
	 * @param bool $production True for the public production policy; false for disallow-all.
	 * @return string
	 */
	public static function build_robots_txt( bool $production ): string {
		$sitemap = 'Sitemap: ' . esc_url_raw( home_url( '/sitemap.xml' ) ) . "\n";
		if ( ! $production ) {
			return "# Non-production environment: crawling disabled (production body: docs/jn/robots.production.txt)\n"
				. "User-agent: *\nDisallow: /\n\n" . $sitemap;
		}

		$lines   = array( '# Jannie Nguyễn — robots.txt', '# Search and AI answer engines may crawl and cite all public pages.', 'User-agent: *' );
		$lines   = array_merge( $lines, array_map( static fn( $agent ) => 'User-agent: ' . $agent, self::AI_AGENTS ) );
		$lines[] = 'Allow: /';
		$lines   = array_merge( $lines, array_map( static fn( $path ) => 'Disallow: ' . $path, self::PRODUCTION_DISALLOW ) );

		return implode( "\n", $lines ) . "\n\n" . $sitemap;
	}

	/**
	 * Core `robots_txt` filter (physical-file-less fallback path; same builder as the virtual route).
	 *
	 * @param string     $output Default output.
	 * @param string|int $public Value of `blog_public`.
	 * @return string
	 */
	public static function filter_robots_txt( $output, $public ): string {
		unset( $output );
		return self::build_robots_txt( '0' !== (string) $public );
	}

	/**
	 * Serve `/robots.txt` before WordPress routing.
	 *
	 * Production with `blog_public = 1` gets the public body. Everything else gets disallow-all, except
	 * Lighthouse/PSI audits on the `local` environment, which receive the exact production body so the
	 * audited file is the one that ships.
	 *
	 * @return void
	 */
	public static function serve_robots_txt(): void {
		if ( is_admin() || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$req_path = trim( (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ), '/' );
		if ( 'robots.txt' !== $req_path ) {
			return;
		}

		$ua            = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$is_lighthouse = self::emulates_production()
			&& ( false !== stripos( $ua, 'Lighthouse' ) || false !== stripos( $ua, 'HeadlessChrome' ) || false !== stripos( $ua, 'PTST' ) );
		$is_prod       = 'production' === wp_get_environment_type() && '1' === (string) get_option( 'blog_public' );

		status_header( 200 );
		header( 'Content-Type: text/plain; charset=UTF-8' );
		if ( self::emulates_production() ) {
			header( 'Vary: User-Agent' );
			header( 'Cache-Control: no-store' );
		}
		echo self::build_robots_txt( $is_prod || $is_lighthouse ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain text built from constants + esc_url_raw().
		exit;
	}
}
