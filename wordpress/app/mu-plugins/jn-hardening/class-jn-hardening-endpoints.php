<?php
/**
 * JN Hardening — Paths, REST Endpoints, Author Enumeration, XML-RPC, Sitemaps & Feeds (Tasks B & C).
 *
 * - Keeps REST API prefix `/api` (from Step 1).
 * - Blocks anonymous user enumeration on `/wp/v2/users` and `/?rest_route=/wp/v2/users` with HTTP 404.
 * - Strips author_name and author_url from oEmbed responses.
 * - Disables XML-RPC (`xmlrpc_enabled`, `xmlrpc_methods`, `pings_open`).
 * - Blocks `?author=N`, `?author_name=...`, and `/author/*` archives with HTTP 404 (no canonical redirect).
 * - Disables WordPress core sitemaps (`/wp-sitemap.xml` -> 404).
 * - Disables RSS/Atom/RDF feeds (`ADR-010`) with HTTP 404.
 * - Renders the designed 404 template directly (no 302 redirect) when a logged-out visitor requests `/wp-admin/`.
 * - robots.txt moved to JN_SEO_Robots in jn-performance (Step 10); it still exposes no WordPress paths.
 *
 * @package JannieNguyen\MU
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Endpoint hardening and enumeration prevention.
 */
class JN_Hardening_Endpoints {

	/**
	 * Register all endpoint, REST, XML-RPC, author, sitemap, feed, and robots hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		// 1. Custom REST API prefix (/api) from Step 1.
		add_filter( 'rest_url_prefix', array( __CLASS__, 'filter_rest_url_prefix' ), 10, 0 );
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrites' ), 99 );

		// 2. Block anonymous access to /wp/v2/users (both /api/wp/v2/users and /?rest_route=/wp/v2/users).
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'block_anonymous_user_rest_routes' ), 10, 3 );
		add_filter( 'oembed_response_data', array( __CLASS__, 'strip_oembed_author_data' ), 999, 1 );

		// 3. Disable XML-RPC and pingbacks completely.
		add_filter( 'xmlrpc_enabled', '__return_false', 999 );
		add_filter( 'xmlrpc_methods', '__return_empty_array', 999 );
		add_filter( 'pings_open', '__return_false', 999 );
		add_filter( 'pre_option_enable_xmlrpc', '__return_zero', 999 );

		// 4. Disable core sitemaps (SEO plugin sitemap arrives in Step 8).
		add_filter( 'wp_sitemaps_enabled', '__return_false', 999 );
		add_filter( 'wp_sitemaps_add_provider', array( __CLASS__, 'remove_users_sitemap_provider' ), 999, 2 );

		// 5. Disable author rewrite rules and feed rewrite rules.
		add_filter( 'author_rewrite_rules', '__return_empty_array', 999 );
		add_filter( 'feed_rewrite_rules', '__return_empty_array', 999 );

		// 6. Prevent redirect_canonical from revealing author slugs on ?author=N or redirecting feeds.
		add_filter( 'redirect_canonical', array( __CLASS__, 'prevent_author_and_feed_canonical_redirect' ), 1, 2 );

		// 7. Force error=404 at request parsing time for ?author=N, author archives, feeds, and sitemaps.
		add_filter( 'request', array( __CLASS__, 'filter_blocked_query_vars' ), 1, 1 );
		add_action( 'template_redirect', array( __CLASS__, 'intercept_blocked_frontend_requests' ), 1 );

		// 8. Disable all feed actions (ADR-010).
		$feed_actions = array(
			'do_feed',
			'do_feed_rdf',
			'do_feed_rss',
			'do_feed_rss2',
			'do_feed_atom',
			'do_feed_rss2_comments',
			'do_feed_atom_comments',
		);
		foreach ( $feed_actions as $feed_action ) {
			add_action( $feed_action, array( __CLASS__, 'render_404_and_exit' ), 1 );
		}

		// 9. robots.txt is owned by JN_SEO_Robots (jn-performance/class-jn-seo-robots.php, Step 10).

		// 10. Direct HTTP 404 for logged-out /wp-admin/ requests (no 302 redirect).
		add_action( 'wps_hide_login_before_redirect', array( __CLASS__, 'intercept_logged_out_admin_404' ), 10, 1 );
	}

	/**
	 * Change the REST API URL prefix from "wp-json" to "api".
	 *
	 * @return string
	 */
	public static function filter_rest_url_prefix(): string {
		return 'api';
	}

	/**
	 * Flush rewrite rules once after hardening rules are registered.
	 *
	 * @return void
	 */
	public static function maybe_flush_rewrites(): void {
		if ( ! is_blog_installed() ) {
			return;
		}

		$flag_option = 'jn_hardening_rewrite_flushed_v2';
		if ( '1' !== get_option( $flag_option ) ) {
			flush_rewrite_rules( false );
			update_option( $flag_option, '1', true );
		}
	}

	/**
	 * Block anonymous enumeration of `/wp/v2/users` and `/wp/v2/users/<id>` while preserving
	 * authenticated Block Editor access for editors and administrators.
	 *
	 * @param mixed           $result  Response to replace the requested version with.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 * @return mixed
	 */
	public static function block_anonymous_user_rest_routes( $result, $server, $request ) {
		unset( $server );

		if ( ! ( $request instanceof WP_REST_Request ) ) {
			return $result;
		}

		$route = $request->get_route();
		if ( preg_match( '#^/wp/v2/users(?:/|$)#', $route ) ) {
			if ( ! is_user_logged_in() || ( ! current_user_can( 'list_users' ) && ! current_user_can( 'edit_posts' ) ) ) {
				return new WP_Error(
					'rest_no_route',
					__( 'Không tìm thấy đường dẫn phù hợp với URL và phương thức yêu cầu.', 'jannie' ),
					array( 'status' => 404 )
				);
			}
		}

		return $result;
	}

	/**
	 * Strip `author_name` and `author_url` from oEmbed response payloads.
	 *
	 * @param array<string, mixed> $data oEmbed response data.
	 * @return array<string, mixed>
	 */
	public static function strip_oembed_author_data( array $data ): array {
		unset( $data['author_name'], $data['author_url'] );
		return $data;
	}

	/**
	 * Ensure the `users` provider is never registered in core sitemaps even if sitemaps are queried.
	 *
	 * @param WP_Sitemaps_Provider|false $provider Sitemap provider instance.
	 * @param string                     $name     Provider name.
	 * @return WP_Sitemaps_Provider|false
	 */
	public static function remove_users_sitemap_provider( $provider, string $name ) {
		if ( 'users' === $name ) {
			return false;
		}
		return $provider;
	}

	/**
	 * Prevent `redirect_canonical()` from redirecting `?author=1` to `/author/<slug>/` or redirecting feeds.
	 *
	 * @param string|false $redirect_url  The redirect URL.
	 * @param string       $requested_url The requested URL.
	 * @return string|false
	 */
	public static function prevent_author_and_feed_canonical_redirect( $redirect_url, string $requested_url ) {
		unset( $requested_url );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( is_author() || is_feed() || isset( $_GET['author'] ) || isset( $_GET['author_name'] ) || isset( $_GET['feed'] ) ) {
			return false;
		}

		return $redirect_url;
	}

	/**
	 * Force `error=404` during `WP::parse_request()` when author archives, feeds, or core sitemaps are requested.
	 *
	 * @param array<string, mixed> $query_vars Parsed query variables.
	 * @return array<string, mixed>
	 */
	public static function filter_blocked_query_vars( array $query_vars ): array {
		if (
			isset( $query_vars['author'] )
			|| isset( $query_vars['author_name'] )
			|| isset( $query_vars['feed'] )
			|| isset( $query_vars['sitemap'] )
			|| isset( $query_vars['sitemap-subtype'] )
			|| isset( $query_vars['sitemap-stylesheet'] )
		) {
			return array( 'error' => '404' );
		}

		return $query_vars;
	}

	/**
	 * Intercept blocked front-end requests (`?author=N`, `/author/*`, feeds, core sitemaps) and force
	 * WordPress's native 404 template pipeline so `template-loader.php` renders the lean 404 page.
	 *
	 * @return void
	 */
	public static function intercept_blocked_frontend_requests(): void {
		global $wp_query;

		$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$request_path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$is_author_request = is_author()
			|| isset( $_GET['author'] )
			|| isset( $_GET['author_name'] )
			|| (bool) preg_match( '#^/author(?:/|$)#i', $request_path );

		$is_feed_request = is_feed()
			|| isset( $_GET['feed'] )
			|| (bool) preg_match( '#(?:/|^)feed(?:/|$)#i', $request_path );

		$is_sitemap_request = '' !== (string) get_query_var( 'sitemap' )
			|| isset( $_GET['sitemap'] )
			|| str_starts_with( strtolower( $request_path ), '/wp-sitemap' );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $is_author_request || $is_feed_request || $is_sitemap_request ) {
			if ( $wp_query instanceof WP_Query ) {
				$wp_query->set_404();
				$wp_query->posts      = array();
				$wp_query->post       = null;
				$wp_query->post_count = 0;
			}
			status_header( 404 );
			nocache_headers();
		}
	}

	/**
	 * Return a direct HTTP 404 (instead of a 302 redirect) when a logged-out visitor requests `/wp-admin/`.
	 *
	 * @param array<string, mixed> $request Parsed request URI components.
	 * @return void
	 */
	public static function intercept_logged_out_admin_404( array $request ): void {
		global $pagenow;

		$path = isset( $request['path'] ) ? (string) $request['path'] : '';
		if (
			is_admin()
			&& ! is_user_logged_in()
			&& ! defined( 'WP_CLI' )
			&& ! defined( 'DOING_AJAX' )
			&& ! defined( 'DOING_CRON' )
			&& 'admin-post.php' !== $pagenow
			&& '/wp-admin/options.php' !== $path
		) {
			self::render_404_and_exit();
		}
	}

	/**
	 * Set HTTP 404 state, render the child theme 404 template through the HTML scrubber, and exit.
	 *
	 * @return void
	 */
	public static function render_404_and_exit(): void {
		global $wp_query;

		JN_Hardening_HTML_Headers::$rendering_public_404 = true;
		JN_Hardening_HTML_Headers::send_security_headers();

		if ( $wp_query instanceof WP_Query ) {
			$wp_query->set_404();
		}

		status_header( 404 );
		nocache_headers();
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/html; charset=UTF-8', true );
		}

		ob_start( array( 'JN_Hardening_HTML_Headers', 'scrub_buffer' ) );

		$template_404 = get_query_template( '404' );
		if ( $template_404 && file_exists( $template_404 ) ) {
			include $template_404;
		} else {
			echo '<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><title>Không tìm thấy trang — Jannie Nguyen</title></head><body><h1>404 — Không tìm thấy trang</h1></body></html>';
		}

		if ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
		exit;
	}
}
