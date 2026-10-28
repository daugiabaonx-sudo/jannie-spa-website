<?php
/**
 * JN Hardening — HTML, Headers, Core Block Style Inlining & Asset Aliasing (Tasks A, B, D, E).
 *
 * - Removes generator tags (Core, WooCommerce, feeds, GlotPress inline i18n JSON).
 * - Removes RSD, wlwmanifest, shortlink, REST discovery (head + Link header), oEmbed discovery,
 *   wp-embed, emoji scripts/styles, feed links, and default speculationrules path leaks.
 * - Strips X-Pingback, X-Powered-By, X-Redirect-By and sends production security headers.
 * - Replaces ?ver=... query strings with a 10-character SHA-256 content hash (?v=<hash>).
 * - Aliases /wp-includes/, /wp-admin/{css,js,images}/, and theme/plugin asset URLs to /static/*.
 * - Aliases front-end admin-ajax.php references to /api/ajax.
 * - Inlines small core block styles on content pages and strips /*# sourceURL=... * / comments.
 * - Strips HTML comments and theme-slug body classes on the front-end.
 *
 * @package JannieNguyen\MU
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * HTML output scrubbing, HTTP security headers, and asset URL aliasing.
 */
class JN_Hardening_HTML_Headers {

	/**
	 * Per-request cache mapping local file paths to 10-char SHA-256 hashes.
	 *
	 * @var array<string, string>
	 */
	private static array $hash_cache = array();

	/**
	 * Flag set when rendering a public 404 page from an unauthenticated /wp-admin/ request.
	 *
	 * @var bool
	 */
	public static bool $rendering_public_404 = false;

	/**
	 * Set once the response has been scrubbed, so nested output buffers do not scrub it again.
	 *
	 * @var bool
	 */
	private static bool $did_scrub = false;

	/**
	 * Path (under the remapped `/api` REST prefix) that receives CSP violation reports.
	 */
	const CSP_REPORT_PATH = '/api/jn/v1/csp-report';

	/**
	 * Largest CSP report body accepted, in bytes.
	 */
	const CSP_REPORT_MAX_BYTES = 8192;

	/**
	 * CSP reports accepted per client IP per window.
	 */
	const CSP_REPORT_MAX_PER_WINDOW = 30;

	/**
	 * Check whether the current request is an authenticated wp-admin backend session.
	 *
	 * @return bool
	 */
	public static function is_authenticated_admin(): bool {
		if ( self::$rendering_public_404 ) {
			return false;
		}
		return is_admin() && is_user_logged_in();
	}

	/**
	 * Register all hooks for Tasks A, B (asset inlining), D (headers), and E (aliasing).
	 *
	 * @return void
	 */
	public static function init(): void {
		// 1. Remove generator meta tags and feed generators.
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string', 999 );

		$generator_types = array( 'html', 'xhtml', 'atom', 'rss2', 'rdf', 'comment', 'export' );
		foreach ( $generator_types as $gen_type ) {
			add_filter( "get_the_generator_{$gen_type}", '__return_empty_string', 999 );
		}

		add_action( 'init', array( __CLASS__, 'remove_head_discovery_and_emojis' ), 1 );
		add_action( 'wp_loaded', array( __CLASS__, 'remove_late_plugin_generators' ), 999 );

		// Strip "generator":"GlotPress/..." from inline wp-i18n JSON translations.
		add_filter( 'load_script_translations', array( __CLASS__, 'strip_glotpress_generator_from_json' ), 999, 1 );

		// 2. HTTP response headers (remove X-Pingback, X-Powered-By, X-Redirect-By + add Security Headers).
		add_filter( 'x_redirect_by', '__return_false', 999 );
		add_filter( 'wp_headers', array( __CLASS__, 'filter_wp_headers' ), 999, 1 );
		add_action( 'send_headers', array( __CLASS__, 'send_security_headers' ), 999 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_csp_report_route' ) );
		add_action( 'login_init', array( __CLASS__, 'send_security_headers' ), 1 );

		// 3. Core block style on-demand loading & inlining on content pages (Task B).
		add_filter( 'should_load_separate_core_block_assets', '__return_true', 999 );
		add_filter( 'should_load_block_assets_on_demand', '__return_true', 999 );
		add_filter( 'styles_inline_size_limit', array( __CLASS__, 'filter_styles_inline_size_limit' ), 999, 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'dequeue_unneeded_core_assets' ), 999 );
		add_action( 'wp_head', array( __CLASS__, 'clear_inlined_style_source_urls' ), 2 );
		add_action( 'wp_footer', array( __CLASS__, 'clear_inlined_style_source_urls' ), 2 );

		// 4. Content-hash versioning (?v=<hash>) and neutral asset URL aliasing (Tasks A & E).
		add_filter( 'style_loader_src', array( __CLASS__, 'replace_version_with_hash' ), 998, 1 );
		add_filter( 'script_loader_src', array( __CLASS__, 'replace_version_with_hash' ), 998, 1 );
		add_filter( 'script_module_loader_src', array( __CLASS__, 'replace_version_with_hash' ), 998, 1 );

		add_filter( 'style_loader_src', array( __CLASS__, 'alias_asset_url' ), 999, 1 );
		add_filter( 'script_loader_src', array( __CLASS__, 'alias_asset_url' ), 999, 1 );
		add_filter( 'script_module_loader_src', array( __CLASS__, 'alias_asset_url' ), 999, 1 );

		add_filter( 'style_loader_tag', array( __CLASS__, 'scrub_asset_tag_ids' ), 999, 1 );
		add_filter( 'script_loader_tag', array( __CLASS__, 'scrub_asset_tag_ids' ), 999, 1 );

		// 5. Front-end admin-ajax.php aliasing -> /api/ajax (Task B).
		add_filter( 'admin_url', array( __CLASS__, 'alias_frontend_admin_ajax_url' ), 999, 3 );

		// 6. Body class scrubbing (Task A).
		add_filter( 'body_class', array( __CLASS__, 'scrub_body_classes' ), 999, 1 );

		// 7. HTML output buffer scrubbing (comments, sourceURLs, inline JSON paths, residual ?ver=).
		add_filter( 'wp_template_enhancement_output_buffer', array( __CLASS__, 'scrub_buffer' ), 999, 1 );
		add_action( 'template_redirect', array( __CLASS__, 'start_fallback_output_buffer' ), -9999 );
		add_action( 'login_init', array( __CLASS__, 'start_fallback_output_buffer' ), 1 );
	}

	/**
	 * Remove RSD, wlwmanifest, shortlink, REST links, oEmbed, emoji scripts/styles, and default speculation rules.
	 *
	 * @return void
	 */
	public static function remove_head_discovery_and_emojis(): void {
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );
		remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );

		// Remove WordPress emoji detection script and styles everywhere.
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url', '__return_false' );
		add_filter( 'tiny_mce_plugins', array( __CLASS__, 'disable_emojis_tinymce' ) );

		// Replace default speculation rules (which leak /wp-*.php, /wp-admin/*, /app/themes/blocksy/*).
		remove_action( 'wp_footer', 'wp_print_speculation_rules' );
		add_action( 'wp_footer', array( __CLASS__, 'print_clean_speculation_rules' ), 10 );
	}

	/**
	 * Remove late-registered generator tags from WooCommerce or other plugins.
	 *
	 * @return void
	 */
	public static function remove_late_plugin_generators(): void {
		remove_action( 'wp_head', 'wc_generator_tag' );
		remove_action( 'get_the_generator_html', 'wc_generator_tag', 10 );
		remove_action( 'get_the_generator_xhtml', 'wc_generator_tag', 10 );
	}

	/**
	 * Remove wpemoji from TinyMCE plugins array.
	 *
	 * @param array<int, string>|mixed $plugins TinyMCE plugins list.
	 * @return array<int, string>
	 */
	public static function disable_emojis_tinymce( $plugins ): array {
		if ( ! is_array( $plugins ) ) {
			return array();
		}
		return array_values( array_diff( $plugins, array( 'wpemoji' ) ) );
	}

	/**
	 * Output clean Speculation Rules without exposing /wp-*.php, /wp-admin/*, or theme slugs.
	 *
	 * @return void
	 */
	public static function print_clean_speculation_rules(): void {
		if ( self::is_authenticated_admin() || is_user_logged_in() ) {
			return;
		}

		$rules = array(
			'prefetch' => array(
				array(
					'source'    => 'document',
					'where'     => array(
						'and' => array(
							array( 'href_matches' => '/*' ),
							array(
								'not' => array(
									'href_matches' => array(
										'/static/*',
										'/media/*',
										'/api/*',
										'/cart/*',
										'/checkout/*',
										'/order-pay/*',
										'/*order-pay*',
										'/tai-khoan/*',
										'/my-account/*',
										'/*add-to-cart*',
										'/*logout*',
										'/*jn_bf=*',
										'/booking/*\\?*',
										'/*\\?(.+)',
									),
								),
							),
							array(
								'not' => array( 'selector_matches' => 'a[rel~="nofollow"]' ),
							),
							array(
								'not' => array( 'selector_matches' => '.do-not-prefetch, .do-not-prefetch a' ),
							),
						),
					),
					'eagerness' => 'conservative',
				),
			),
		);

		echo '<script type="speculationrules">' . wp_json_encode( $rules, JSON_UNESCAPED_SLASHES ) . "</script>\n";
	}

	/**
	 * Strip the "generator":"GlotPress/..." and "comment":{"reference":"wp-includes/..."} fields
	 * from inline script translations JSON.
	 *
	 * @param string|false $translations JSON-encoded translation data.
	 * @return string|false
	 */
	public static function strip_glotpress_generator_from_json( $translations ) {
		if ( ! is_string( $translations ) || '' === $translations ) {
			return $translations;
		}

		if ( false === strpos( $translations, 'generator' ) && false === strpos( $translations, 'wp-includes' ) ) {
			return $translations;
		}

		$decoded = json_decode( $translations, true );
		if ( is_array( $decoded ) ) {
			unset( $decoded['generator'], $decoded['comment'] );
			$encoded = wp_json_encode( $decoded );
			if ( is_string( $encoded ) ) {
				return $encoded;
			}
		}

		return $translations;
	}

	/**
	 * Filter WordPress HTTP headers array to strip X-Pingback and X-Powered-By and add security headers.
	 *
	 * @param array<string, string> $headers Associative array of HTTP headers.
	 * @return array<string, string>
	 */
	public static function filter_wp_headers( array $headers ): array {
		unset( $headers['X-Pingback'], $headers['x-pingback'], $headers['X-Powered-By'], $headers['x-powered-by'] );

		if ( self::php_sends_security_headers() ) {
			foreach ( self::get_security_headers() as $name => $value ) {
				$headers[ $name ] = $value;
			}
		}

		return $headers;
	}

	/**
	 * Whether PHP itself must emit the security headers.
	 *
	 * The web server is the single source by default (wordpress/.htaccess, deploy/apache-litespeed.htaccess,
	 * deploy/nginx-security-headers.conf): Apache's `Header always setifempty` cannot see headers PHP already
	 * sent, so sending them from PHP as well would duplicate every header. Set JN_SECURITY_HEADERS_FROM_PHP=1
	 * only on hosts where the server config cannot add headers (nginx already `fastcgi_hide_header`s them).
	 *
	 * @return bool
	 */
	public static function php_sends_security_headers(): bool {
		return 'cli' === PHP_SAPI || '1' === (string) getenv( 'JN_SECURITY_HEADERS_FROM_PHP' );
	}

	/**
	 * Remove fingerprinting headers and send the security headers on responses that bypass
	 * `wp_headers` (login screen, early exits), unless a header is already set.
	 *
	 * @return void
	 */
	public static function send_security_headers(): void {
		if ( headers_sent() ) {
			return;
		}

		if ( function_exists( 'header_remove' ) ) {
			header_remove( 'X-Powered-By' );
			header_remove( 'X-Pingback' );
			header_remove( 'X-Redirect-By' );
		}

		if ( ! self::php_sends_security_headers() ) {
			return;
		}

		$sent = strtolower( implode( "\n", headers_list() ) );
		foreach ( self::get_security_headers() as $name => $value ) {
			if ( false === strpos( $sent, strtolower( $name ) . ':' ) ) {
				header( $name . ': ' . $value );
			}
		}
	}

	/**
	 * Register the anonymous CSP violation report endpoint (`POST /api/jn/v1/csp-report`).
	 *
	 * @return void
	 */
	public static function register_csp_report_route(): void {
		register_rest_route(
			'jn/v1',
			'/csp-report',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_csp_report' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Log a CSP violation report (size-capped, rate-limited, query strings stripped — no PII).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function handle_csp_report( WP_REST_Request $request ): WP_REST_Response {
		$body = (string) $request->get_body();
		if ( strlen( $body ) > self::CSP_REPORT_MAX_BYTES ) {
			return new WP_REST_Response( null, 413 );
		}

		$ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$rate_key = 'jn_csp_rl_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) ), 0, 20 );
		$count    = (int) get_transient( $rate_key );
		if ( $count >= self::CSP_REPORT_MAX_PER_WINDOW ) {
			return new WP_REST_Response( null, 429 );
		}
		set_transient( $rate_key, $count + 1, 10 * MINUTE_IN_SECONDS );

		$decoded = json_decode( $body, true );
		$report  = is_array( $decoded ) && isset( $decoded['csp-report'] ) && is_array( $decoded['csp-report'] ) ? $decoded['csp-report'] : array();
		if ( empty( $report ) ) {
			return new WP_REST_Response( null, 400 );
		}

		$strip_query = static fn( $url ): string => sanitize_text_field( strtok( (string) $url, '?#' ) ?: '' );
		$line        = sprintf(
			'CSP violation: directive=%s blocked=%s document=%s',
			sanitize_text_field( (string) ( $report['effective-directive'] ?? $report['violated-directive'] ?? '' ) ),
			$strip_query( $report['blocked-uri'] ?? '' ),
			$strip_query( $report['document-uri'] ?? '' )
		);
		if ( function_exists( 'wc_get_logger' ) ) {
			wc_get_logger()->warning( $line, array( 'source' => 'jn-csp' ) );
		} else {
			error_log( $line ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		return new WP_REST_Response( null, 204 );
	}

	/**
	 * Return canonical production-ready security headers (Task D).
	 *
	 * @return array<string, string>
	 */
	public static function get_security_headers(): array {
		return array(
			'Strict-Transport-Security'           => 'max-age=31536000; includeSubDomains',
			'X-Content-Type-Options'              => 'nosniff',
			'X-Frame-Options'                     => 'SAMEORIGIN',
			'Referrer-Policy'                     => 'strict-origin-when-cross-origin',
			'Permissions-Policy'                  => 'camera=(), microphone=(), geolocation=(), payment=(self), usb=(), interest-cohort=()',
			'Content-Security-Policy-Report-Only' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob:; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https:; font-src 'self' data:; connect-src 'self'; media-src 'self' blob:; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; report-uri " . self::CSP_REPORT_PATH,
		);
	}

	/**
	 * Increase the inline style size limit on front-end pages so small core block styles are inlined (Task B).
	 *
	 * @param int $limit Default limit in bytes (40000 in WP 7.1.3).
	 * @return int
	 */
	public static function filter_styles_inline_size_limit( int $limit ): int {
		if ( self::is_authenticated_admin() ) {
			return $limit;
		}
		return 65000;
	}

	/**
	 * Dequeue classic-theme-styles, wp-embed, and admin-only 2FA QR generator scripts on the front-end.
	 *
	 * @return void
	 */
	public static function dequeue_unneeded_core_assets(): void {
		if ( self::is_authenticated_admin() ) {
			return;
		}

		wp_dequeue_style( 'classic-theme-styles' );
		wp_deregister_style( 'classic-theme-styles' );

		wp_dequeue_script( 'wp-embed' );
		wp_deregister_script( 'wp-embed' );

		wp_dequeue_script( 'two-factor-qr-code-generator' );
		wp_deregister_script( 'two-factor-qr-code-generator' );
		wp_dequeue_script( 'totp-admin-qrcode' );
		wp_deregister_script( 'totp-admin-qrcode' );
	}

	/**
	 * Clear `inlined_src` metadata on registered styles so WP_Styles::print_inline_style()
	 * does not append `/*# sourceURL=http://.../wp-includes/blocks/... * /`.
	 *
	 * @return void
	 */
	public static function clear_inlined_style_source_urls(): void {
		global $wp_styles;

		if ( ! ( $wp_styles instanceof WP_Styles ) || empty( $wp_styles->registered ) ) {
			return;
		}

		foreach ( array_keys( $wp_styles->registered ) as $handle ) {
			if ( $wp_styles->get_data( $handle, 'inlined_src' ) ) {
				$wp_styles->add_data( $handle, 'inlined_src', false );
			}
		}
	}

	/**
	 * Replace `?ver=...` query strings on local assets with `?v=<10-char-sha256-content-hash>`.
	 *
	 * @param string|mixed $src Asset URL.
	 * @return string
	 */
	public static function replace_version_with_hash( $src ): string {
		$src = (string) $src;
		if ( '' === $src || self::is_authenticated_admin() ) {
			return $src;
		}

		$hash  = self::compute_asset_hash_from_url( $src );
		$clean = remove_query_arg( 'ver', $src );

		return add_query_arg( 'v', $hash, $clean );
	}

	/**
	 * Alias local WordPress core, theme, and module asset URLs to neutral `/static/*` paths (Task E).
	 *
	 * @param string|mixed $src Asset URL.
	 * @return string
	 */
	public static function alias_asset_url( $src ): string {
		$src = (string) $src;
		if ( '' === $src || self::is_authenticated_admin() ) {
			return $src;
		}

		$replacements = array(
			'/wp-includes/'             => '/static/core/',
			'/wp-admin/js/'             => '/static/core-admin/js/',
			'/wp-admin/css/'            => '/static/core-admin/css/',
			'/wp-admin/images/'         => '/static/core-admin/images/',
			'/app/themes/blocksy/'      => '/static/base/',
			'/app/themes/jannie/'       => '/static/ui/',
			'/app/modules/woocommerce/' => '/static/shop/',
			'/app/modules/jn-core/'     => '/static/academy/',
			'/app/modules/'             => '/static/ext/',
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $src );
	}

	/**
	 * Scrub theme slug prefixes from `<link>` and `<script>` `id` attributes on the front-end.
	 *
	 * @param string|mixed $tag Rendered HTML tag.
	 * @return string
	 */
	public static function scrub_asset_tag_ids( $tag ): string {
		$tag = (string) $tag;
		if ( '' === $tag || self::is_authenticated_admin() ) {
			return $tag;
		}

		$tag = str_replace(
			array( "id='blocksy-", 'id="blocksy-', "id='jannie-", 'id="jannie-' ),
			array( "id='ct-", 'id="ct-', "id='jn-", 'id="jn-' ),
			$tag
		);

		return $tag;
	}

	/**
	 * Alias front-end `admin_url( 'admin-ajax.php' )` calls to `/api/ajax` so front-end markup
	 * never references `admin-ajax.php` (Task B).
	 *
	 * @param string   $url     Complete admin URL.
	 * @param string   $path    Path relative to admin URL.
	 * @param int|null $blog_id Blog ID, or null for current blog.
	 * @return string
	 */
	public static function alias_frontend_admin_ajax_url( string $url, string $path, $blog_id = null ): string {
		unset( $blog_id );

		if ( self::is_authenticated_admin() ) {
			return $url;
		}

		$normalized = ltrim( $path, '/' );
		if ( 'admin-ajax.php' === $normalized || str_starts_with( $normalized, 'admin-ajax.php?' ) ) {
			$query = wp_parse_url( $url, PHP_URL_QUERY );
			$alias = home_url( '/api/ajax' );
			return $query ? "{$alias}?{$query}" : $alias;
		}

		return $url;
	}

	/**
	 * Drop body classes that expose the parent or child theme slug or wp-embed (Task A).
	 *
	 * @param array<int, string> $classes Body class array.
	 * @return array<int, string>
	 */
	public static function scrub_body_classes( array $classes ): array {
		if ( self::is_authenticated_admin() ) {
			return $classes;
		}

		$blocked_exact = array(
			'wp-theme-blocksy',
			'theme-blocksy',
			'blocksy',
			'wp-child-theme-jannie',
			'child-theme-jannie',
			'theme-jannie',
			'jannie',
			'wp-embed-responsive',
		);

		$filtered = array();
		foreach ( $classes as $class ) {
			if ( in_array( $class, $blocked_exact, true ) ) {
				continue;
			}
			if ( str_starts_with( $class, 'wp-theme-' ) || str_starts_with( $class, 'wp-child-theme-' ) ) {
				continue;
			}
			$filtered[] = $class;
		}

		return array_values( $filtered );
	}

	/**
	 * Start a fallback output buffer on front-end and login requests so pages that bypass
	 * `wp_template_enhancement_output_buffer` (e.g. custom 404 or login screen) are still scrubbed.
	 *
	 * @return void
	 */
	public static function start_fallback_output_buffer(): void {
		if ( self::is_authenticated_admin() || defined( 'WP_CLI' ) || self::is_non_html_request() ) {
			return;
		}

		ob_start( array( __CLASS__, 'scrub_buffer' ) );
	}

	/**
	 * Whether the current request produces a non-HTML response (AJAX, REST, WooCommerce `wc-ajax` JSON).
	 *
	 * `wc-ajax` is dispatched on `template_redirect` priority 0, after the fallback buffer starts at -9999,
	 * and `wp_is_json_request()` only inspects the Accept header, so it is checked explicitly.
	 *
	 * @return bool
	 */
	public static function is_non_html_request(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing check.
		return wp_doing_ajax() || wp_is_json_request() || isset( $_GET['wc-ajax'] ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}

	/**
	 * `preg_replace()` that keeps the input when PCRE fails (backtrack/JIT limits) instead of returning null.
	 *
	 * @param string $pattern     Regex.
	 * @param string $replacement Replacement.
	 * @param string $subject     Subject.
	 * @return string
	 */
	private static function safe_replace( string $pattern, string $replacement, string $subject ): string {
		$result = preg_replace( $pattern, $replacement, $subject );
		return null === $result ? $subject : $result;
	}

	/**
	 * `preg_replace_callback()` that keeps the input when PCRE fails.
	 *
	 * @param string   $pattern  Regex.
	 * @param callable $callback Callback.
	 * @param string   $subject  Subject.
	 * @return string
	 */
	private static function safe_replace_callback( string $pattern, callable $callback, string $subject ): string {
		$result = preg_replace_callback( $pattern, $callback, $subject );
		return null === $result ? $subject : $result;
	}

	/**
	 * Whether the response being buffered declared a non-HTML Content-Type.
	 *
	 * @return bool
	 */
	private static function response_is_not_html(): bool {
		foreach ( headers_list() as $header ) {
			if ( 0 === stripos( $header, 'content-type:' ) ) {
				return false === stripos( $header, 'text/html' );
			}
		}
		return false;
	}

	/**
	 * Idempotently scrub front-end HTML output:
	 * - Strips HTML comments
	 * - Strips `/*# sourceURL=... * /` comments inside inline `<style>` tags
	 * - Aliases `/wp-includes/`, `/wp-admin/`, and `/app/` asset URLs in inline JSON/importmaps
	 * - Replaces any residual `?ver=...` query parameters with `?v=<hash>`
	 * - Strips any residual `"generator":"..."` JSON fields
	 *
	 * @param string $html Rendered HTML document.
	 * @return string
	 */
	public static function scrub_html_output( string $html ): string {
		if ( '' === $html || self::is_authenticated_admin() ) {
			return $html;
		}

		// Only scrub HTML documents or fragments containing HTML tags (never JSON/XML/text responses).
		if ( false === strpos( $html, '<' ) || self::response_is_not_html() ) {
			return $html;
		}

		// 1. Strip HTML comments (excluding IE conditional comments), but never inside raw-text elements:
		// `<!--` / `-->` inside <script>, <style>, <pre> or <textarea> are content, not comments.
		$html = self::safe_replace_callback(
			'#(<(script|style|pre|textarea)\b[^>]*>.*?</\2\s*>)|<!--(?!\s*\[if\b).*?-->#is',
			static fn( array $m ): string => isset( $m[1] ) && '' !== $m[1] ? $m[1] : '',
			$html
		);

		// 2. Strip CSS sourceURL comments that leak /wp-includes/ paths.
		$html = self::safe_replace( '#/\*\#\s*sourceURL=.*?\*/\s*#s', '', $html );

		// 3. Alias asset paths in both plain URLs and JSON-escaped URLs (importmap, wc_settings, ct_localizations, zxcvbn).
		$path_replacements = array(
			'/wp-includes/'                   => '/static/core/',
			'\\/wp-includes\\/'               => '\\/static\\/core\\/',
			'/wp-admin/js/'                   => '/static/core-admin/js/',
			'\\/wp-admin\\/js\\/'             => '\\/static\\/core-admin\\/js\\/',
			'/wp-admin/css/'                  => '/static/core-admin/css/',
			'\\/wp-admin\\/css\\/'            => '\\/static\\/core-admin\\/css\\/',
			'/wp-admin/images/'               => '/static/core-admin/images/',
			'\\/wp-admin\\/images\\/'         => '\\/static\\/core-admin\\/images\\/',
			'/wp-admin/admin-ajax.php'        => '/api/ajax',
			'\\/wp-admin\\/admin-ajax.php'    => '\\/api\\/ajax',
			'/app/themes/blocksy/'            => '/static/base/',
			'\\/app\\/themes\\/blocksy\\/'    => '\\/static\\/base\\/',
			'/app/themes/jannie/'             => '/static/ui/',
			'\\/app\\/themes\\/jannie\\/'     => '\\/static\\/ui\\/',
			'/app/modules/woocommerce/'       => '/static/shop/',
			'\\/app\\/modules\\/woocommerce\\/' => '\\/static\\/shop\\/',
			'/app/modules/jn-core/'           => '/static/academy/',
			'\\/app\\/modules\\/jn-core\\/'   => '\\/static\\/academy\\/',
			'/app/modules/'                   => '/static/ext/',
			'\\/app\\/modules\\/'             => '\\/static\\/ext\\/',
		);
		$html = str_replace( array_keys( $path_replacements ), array_values( $path_replacements ), $html );

		// 4. Replace any remaining ?ver=<version> or &ver=<version> in inline scripts/importmaps with content-hashed ?v=<hash>.
		$html = self::safe_replace_callback(
			'/(\?|&|&#038;|\\\\u0026)ver=([0-9A-Za-z._-]+)/',
			static function ( array $matches ): string {
				$hash = substr( hash( 'sha256', $matches[2] . wp_salt() ), 0, 10 );
				return $matches[1] . 'v=' . $hash;
			},
			$html
		);

		// 5. Strip residual "generator":"..." key-value pairs inside inline JSON blobs, keeping the JSON valid:
		// remove the pair together with its preceding comma, or (first member) with its following comma.
		$html = self::safe_replace( '/\s*,\s*"generator"\s*:\s*"[^"]*"/', '', $html );
		$html = self::safe_replace( '/"generator"\s*:\s*"[^"]*"\s*,?\s*/', '', $html );

		// 6. Sanitize URL-encoded wcSettings payload on WooCommerce Cart/Checkout blocks so it never leaks
		// wpVersion, wcVersion, wpLoginUrl (hidden login slug), /wp-admin/, or /app/modules/woocommerce/.
		if ( false !== strpos( $html, 'var wcSettings = JSON.parse( decodeURIComponent(' ) ) {
			$html = self::safe_replace_callback(
				"/var wcSettings = JSON\.parse\( decodeURIComponent\( '([^']+)' \) \);/",
				array( __CLASS__, 'sanitize_wc_settings_inline_script' ),
				$html
			);
		}

		// 7. Scrub any remaining blocksy-/jannie- element IDs or classes on front-end/login/404 templates.
		$html = str_replace(
			array(
				"id='blocksy-",
				'id="blocksy-',
				"id='jannie-",
				'id="jannie-',
				'wp-theme-blocksy',
				'wp-child-theme-jannie',
				'blocksy-woo-messages-default',
			),
			array(
				"id='ct-",
				'id="ct-',
				"id='jn-",
				'id="jn-',
				'',
				'',
				'ct-woo-messages-default',
			),
			$html
		);

		return $html;
	}

	/**
	 * Output-buffer callback: nested buffers (template enhancement, fallback, public 404) all end here,
	 * so the complete document is scrubbed once and outer buffers pass it through untouched.
	 *
	 * @param string $html Buffered output.
	 * @return string
	 */
	public static function scrub_buffer( string $html ): string {
		if ( self::$did_scrub ) {
			return $html;
		}
		$scrubbed = self::scrub_html_output( $html );
		if ( false !== stripos( $scrubbed, '</html>' ) ) {
			self::$did_scrub = true;
		}
		return $scrubbed;
	}

	/**
	 * Sanitize the URL-encoded JSON payload inside `var wcSettings = JSON.parse( decodeURIComponent( '...' ) );`.
	 *
	 * @param array<int, string> $matches Regex match groups.
	 * @return string
	 */
	private static function sanitize_wc_settings_inline_script( array $matches ): string {
		$raw_json = rawurldecode( $matches[1] );
		// Decode to objects (not associative arrays) so empty `{}` maps survive re-encoding instead of becoming `[]`.
		$settings = json_decode( $raw_json );
		if ( ! $settings instanceof stdClass ) {
			return $matches[0];
		}

		$settings->wpVersion     = '';
		$settings->wcVersion     = '';
		$settings->adminUrl      = home_url( '/api/' );
		$settings->wpLoginUrl    = home_url( '/tai-khoan/' );
		$settings->wcAssetUrl    = home_url( '/static/shop/assets/' );
		$settings->defaultAvatar = home_url( '/static/core/images/blank.gif' );

		if ( isset( $settings->wcBlocksConfig ) && $settings->wcBlocksConfig instanceof stdClass ) {
			$settings->wcBlocksConfig->pluginUrl = home_url( '/static/shop/' );
		}

		$encoded_json = wp_json_encode( $settings );
		if ( ! is_string( $encoded_json ) ) {
			return $matches[0];
		}

		$encoded = rawurlencode( $encoded_json );
		return "var wcSettings = JSON.parse( decodeURIComponent( '" . esc_js( $encoded ) . "' ) );";
	}

	/**
	 * Resolve a local asset URL to a filesystem path and compute a 10-character SHA-256 content hash.
	 *
	 * @param string $url Asset URL.
	 * @return string 10-character lowercase hex hash.
	 */
	public static function compute_asset_hash_from_url( string $url ): string {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		if ( '' === $path ) {
			return substr( hash( 'sha256', $url . wp_salt() ), 0, 10 );
		}

		$file = self::resolve_local_file_path( $path );
		if ( '' !== $file ) {
			if ( isset( self::$hash_cache[ $file ] ) ) {
				return self::$hash_cache[ $file ];
			}

			if ( is_file( $file ) && is_readable( $file ) ) {
				$file_hash = hash_file( 'sha256', $file );
				if ( is_string( $file_hash ) && '' !== $file_hash ) {
					$short                     = substr( $file_hash, 0, 10 );
					self::$hash_cache[ $file ] = $short;
					return $short;
				}
			}
		}

		return substr( hash( 'sha256', $path . wp_salt() ), 0, 10 );
	}

	/**
	 * Map a URL path (canonical or `/static/*` alias) to an absolute filesystem path.
	 *
	 * @param string $url_path Path component of the asset URL.
	 * @return string Absolute filesystem path or empty string if unmapped.
	 */
	private static function resolve_local_file_path( string $url_path ): string {
		$map = array(
			'/static/core/'           => ABSPATH . 'wp-includes/',
			'/static/core-admin/js/'  => ABSPATH . 'wp-admin/js/',
			'/static/core-admin/css/' => ABSPATH . 'wp-admin/css/',
			'/static/base/'           => WP_CONTENT_DIR . '/themes/blocksy/',
			'/static/ui/'             => WP_CONTENT_DIR . '/themes/jannie/',
			'/static/shop/'           => WP_PLUGIN_DIR . '/woocommerce/',
			'/static/academy/'        => WP_PLUGIN_DIR . '/jn-core/',
			'/static/ext/'            => WP_PLUGIN_DIR . '/',
			'/app/modules/'           => WP_PLUGIN_DIR . '/',
			'/app/'                   => WP_CONTENT_DIR . '/',
			'/wp-includes/'           => ABSPATH . 'wp-includes/',
			'/wp-admin/'              => ABSPATH . 'wp-admin/',
		);

		foreach ( $map as $prefix => $dir ) {
			if ( str_starts_with( $url_path, $prefix ) ) {
				$relative = substr( $url_path, strlen( $prefix ) );
				return $dir . $relative;
			}
		}

		return '';
	}
}
