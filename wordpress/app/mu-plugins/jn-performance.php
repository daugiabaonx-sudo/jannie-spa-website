<?php
/**
 * Plugin Name: Jannie Performance, Asset Diet, Responsive Media & SEO Architecture
 * Description: Implements Step 8 PageSpeed optimization: per-page asset scoping, Blocksy module trimming, AVIF/WebP responsive images, LCP preloading, speculative loading exclusions, third-party facade, and SEO/Schema/Sitemap/robots.txt/llms.txt rules (SEO modules in jn-performance/).
 * Version: 2.1.0
 * Author: Jannie Nguyen Academy
 *
 * @package JN_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check whether any non-coaching WooCommerce product is currently published in the catalog.
 *
 * @return bool
 */
function jn_has_published_catalog_products() {
	static $has_products = null;
	if ( null !== $has_products ) {
		return $has_products;
	}

	$posts = get_posts(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				array(
					'key'     => '_jn_coaching_package',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);

	$has_products = ! empty( $posts );
	return $has_products;
}

/**
 * Determine whether the current frontend request is a WooCommerce commerce surface.
 *
 * Content pages (`/`, `/my-story/`, `/coaching/`, `/course/`, `/client-success-story/`, `/booking/`,
 * `/chinh-sach-bao-mat/`, `/dieu-khoan-dich-vu/`) return false so zero WooCommerce assets load.
 *
 * @return bool
 */
function jn_is_commerce_page() {
	if ( is_admin() ) {
		return false;
	}

	if ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) {
		return true;
	}

	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return true;
	}

	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return true;
	}

	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return true;
	}

	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() ) {
		return true;
	}

	if ( is_page( array( 'my-favorite-product', 'cart', 'checkout', 'tai-khoan' ) ) ) {
		return true;
	}

	$queried = get_queried_object();
	if ( $queried instanceof WP_Post && ! empty( $queried->post_content ) ) {
		if (
			false !== strpos( $queried->post_content, '<!-- wp:woocommerce/' ) ||
			false !== strpos( $queried->post_content, '[woocommerce_' )
		) {
			return true;
		}
	}

	return false;
}

/**
 * Return `'no'` for WooCommerce/Blocksy boolean option filters.
 *
 * @return string
 */
function __return_no_string() {
	return 'no';
}

/**
 * Return `'yes'` for Blocksy boolean option filters.
 *
 * @return string
 */
function jn_return_yes_string() {
	return 'yes';
}

/**
 * Disable WooCommerce Order Attribution tracking scripts and cookies site-wide (ADR-005).
 */
add_filter( 'pre_option_woocommerce_feature_order_attribution_enabled', '__return_no_string' );
add_filter( 'pre_option_woocommerce_allow_tracking', '__return_no_string' );
add_filter( 'pre_option_woocommerce_show_marketplace_suggestions', '__return_no_string' );

/**
 * Enforce Blocksy free performance theme mods (Task 3):
 * - Disable Emoji scripts (`emoji_scripts = 'no'`)
 * - Enable lazy loading (`has_lazy_load = 'yes'`)
 */
add_filter( 'theme_mod_emoji_scripts', '__return_no_string', 99 );
add_filter( 'theme_mod_has_lazy_load', 'jn_return_yes_string', 99 );

/**
 * Ensure WordPress 7.1.3 loads core block styles on demand and buffers templates for enhancement (Task 3).
 */
add_filter( 'should_load_separate_core_block_assets', '__return_true', 999 );
add_filter( 'should_load_block_assets_on_demand', '__return_true', 999 );
add_filter( 'wp_should_output_buffer_template_for_enhancement', '__return_true', 999 );

/**
 * Strip unused Blocksy static CSS modules before they are registered (Task 3).
 *
 * @param array<string, array<string, mixed>> $files Blocksy static file config map.
 * @return array<string, array<string, mixed>>
 */
function jn_filter_blocksy_static_files( $files ) {
	if ( ! is_array( $files ) ) {
		return $files;
	}

	$unused_handles = array(
		'ct-page-title-styles',
		'ct-comments-styles',
		'ct-sidebar-styles',
		'ct-author-box-styles',
		'ct-posts-nav-styles',
		'ct-featured-image-styles',
		'ct-share-box-styles',
		'ct-related-posts-styles',
		'ct-flexy-styles',
		'ct-search-input-styles',
	);

	foreach ( $unused_handles as $handle ) {
		unset( $files[ $handle ] );
	}

	// On content pages or when `/my-favorite-product/` has no published products, drop ct-woocommerce-styles.
	if ( ! jn_is_commerce_page() || ( is_page( 'my-favorite-product' ) && ! jn_has_published_catalog_products() ) ) {
		unset( $files['ct-woocommerce-styles'] );
	}

	return $files;
}
add_filter( 'blocksy:static-files:all', 'jn_filter_blocksy_static_files', 99 );

/**
 * Remove `jquery-migrate` from frontend `jquery` dependencies so it never loads (Task 3).
 *
 * @param WP_Scripts $scripts WP_Scripts instance.
 * @return void
 */
function jn_remove_jquery_migrate_frontend( $scripts ) {
	if ( is_admin() || ! ( $scripts instanceof WP_Scripts ) ) {
		return;
	}

	if ( isset( $scripts->registered['jquery'] ) && is_array( $scripts->registered['jquery']->deps ) ) {
		$scripts->registered['jquery']->deps = array_values(
			array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) )
		);
	}
}
add_action( 'wp_default_scripts', 'jn_remove_jquery_migrate_frontend', 99 );

/**
 * Scope WooCommerce, jQuery, dashicons, comment-reply, and Blocksy assets per page (Task 3).
 *
 * @return void
 */
function jn_scope_woocommerce_assets() {
	if ( is_admin() ) {
		return;
	}

	// 1. Never load cart fragments, order attribution, comment-reply, or wp-embed anywhere on the frontend.
	$always_blocked_scripts = array(
		'wc-cart-fragments',
		'wc-order-attribution',
		'sourcebuster-js',
		'comment-reply',
		'wp-embed',
		'jquery-migrate',
	);

	foreach ( $always_blocked_scripts as $handle ) {
		wp_dequeue_script( $handle );
		wp_deregister_script( $handle );
	}

	// Dequeue dashicons for unauthenticated visitors.
	if ( ! is_user_logged_in() || ! is_admin_bar_showing() ) {
		wp_dequeue_style( 'dashicons' );
	}

	// Dequeue unused Blocksy styles everywhere on the frontend.
	$unused_blocksy_styles = array(
		'ct-page-title-styles',
		'ct-comments-styles',
		'ct-sidebar-styles',
		'ct-author-box-styles',
		'ct-posts-nav-styles',
		'ct-featured-image-styles',
		'ct-share-box-styles',
		'ct-related-posts-styles',
		'ct-flexy-styles',
		'ct-search-input-styles',
	);
	foreach ( $unused_blocksy_styles as $style_handle ) {
		wp_dequeue_style( $style_handle );
		wp_deregister_style( $style_handle );
	}

	// Defer Blocksy main script (`ct-scripts`) so it never blocks rendering.
	wp_script_add_data( 'ct-scripts', 'strategy', 'defer' );

	// 2. On `/my-favorite-product/`, remove legacy jQuery & classic WooCommerce scripts
	// (Product Collection block uses modern Interactivity API module / standard anchors).
	if ( is_page( 'my-favorite-product' ) ) {
		$fav_blocked_scripts = array(
			'jquery',
			'jquery-core',
			'jquery-migrate',
			'jquery-blockui',
			'js-cookie',
			'woocommerce',
			'wc-add-to-cart',
		);
		foreach ( $fav_blocked_scripts as $handle ) {
			wp_dequeue_script( $handle );
		}

		wp_dequeue_style( 'woocommerce-general' );
		wp_dequeue_style( 'woocommerce-layout' );
		wp_dequeue_style( 'woocommerce-smallscreen' );

		if ( ! jn_has_published_catalog_products() ) {
			wp_dequeue_style( 'ct-woocommerce-styles' );
			wp_dequeue_style( 'wc-blocks-style' );
		}
		return;
	}

	// 3. On `/cart/`, `/checkout/`, and `/tai-khoan/`, prevent loading of 400 KB zxcvbn password meter and unused selectWoo.
	if (
		( function_exists( 'is_cart' ) && is_cart() ) ||
		( function_exists( 'is_checkout' ) && is_checkout() ) ||
		( function_exists( 'is_account_page' ) && is_account_page() ) ||
		is_page( array( 'cart', 'checkout', 'tai-khoan' ) )
	) {
		$heavy_commerce_handles = array(
			'wc-password-strength-meter',
			'password-strength-meter',
			'zxcvbn-async',
			'selectWoo',
			'select2',
		);
		foreach ( $heavy_commerce_handles as $h ) {
			wp_dequeue_script( $h );
			wp_deregister_script( $h );
		}
		wp_dequeue_style( 'select2' );
		wp_deregister_style( 'select2' );

		global $wp_scripts;
		if ( $wp_scripts instanceof WP_Scripts && ! empty( $wp_scripts->registered ) ) {
			foreach ( $wp_scripts->registered as $reg_script ) {
				if ( is_array( $reg_script->deps ) ) {
					$reg_script->deps = array_values(
						array_diff( $reg_script->deps, $heavy_commerce_handles )
					);
				}
			}
		}

		// On `/cart/` and `/checkout/` (which use WooCommerce React/Interactivity Blocks), drop redundant classic jQuery scripts.
		if ( ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) || is_page( array( 'cart', 'checkout' ) ) ) {
			foreach ( array( 'jquery', 'jquery-core', 'jquery-blockui', 'js-cookie', 'woocommerce', 'wc-add-to-cart', 'wc-cart-fragments' ) as $classic_handle ) {
				wp_dequeue_script( $classic_handle );
			}
		}
	}

	if ( jn_is_commerce_page() ) {
		return;
	}

	// 4. On content pages, remove all WooCommerce & Blocksy WooCommerce styles.
	global $wp_styles;
	if ( $wp_styles instanceof WP_Styles && ! empty( $wp_styles->queue ) ) {
		foreach ( (array) $wp_styles->queue as $handle ) {
			$src = isset( $wp_styles->registered[ $handle ]->src ) ? (string) $wp_styles->registered[ $handle ]->src : '';
			if (
				0 === strpos( $handle, 'woocommerce' ) ||
				0 === strpos( $handle, 'wc-' ) ||
				'ct-woocommerce-styles' === $handle ||
				false !== strpos( $src, '/woocommerce/' ) ||
				false !== strpos( $src, 'woocommerce.min.css' )
			) {
				wp_dequeue_style( $handle );
				wp_deregister_style( $handle );
			}
		}
	}

	// 5. On content pages, remove all WooCommerce scripts and jQuery.
	global $wp_scripts;
	if ( $wp_scripts instanceof WP_Scripts && ! empty( $wp_scripts->queue ) ) {
		foreach ( (array) $wp_scripts->queue as $handle ) {
			$src = isset( $wp_scripts->registered[ $handle ]->src ) ? (string) $wp_scripts->registered[ $handle ]->src : '';
			if (
				0 === strpos( $handle, 'woocommerce' ) ||
				0 === strpos( $handle, 'wc-' ) ||
				in_array( $handle, array( 'jquery', 'jquery-core', 'jquery-migrate', 'jquery-blockui', 'js-cookie', 'sourcebuster-js' ), true ) ||
				false !== strpos( $src, '/woocommerce/' )
			) {
				wp_dequeue_script( $handle );
			}
		}
	}

	remove_action( 'wp_head', 'wc_gallery_noscript' );
	remove_action( 'wp_footer', 'wc_no_js' );
}
add_action( 'wp_enqueue_scripts', 'jn_scope_woocommerce_assets', 999 );
add_action( 'wp_print_styles', 'jn_scope_woocommerce_assets', 999 );
add_action( 'wp_print_scripts', 'jn_scope_woocommerce_assets', 999 );
add_action( 'wp_footer', 'jn_scope_woocommerce_assets', 1 );

/**
 * Suppress Blocksy's empty `.woocommerce-notices-wrapper` container on non-commerce content pages.
 *
 * @return void
 */
function jn_suppress_woo_notices_on_content_pages() {
	if ( ! jn_is_commerce_page() ) {
		$GLOBALS['blocksy_messages_content'] = '';
	}
}
add_action( 'blocksy:content:top', 'jn_suppress_woo_notices_on_content_pages', 20 );

/**
 * Ensure `ct-scripts` (`main.js`) and other non-critical scripts carry `defer` attribute (Task 3).
 *
 * @param string $tag    Script HTML tag.
 * @param string $handle Script handle.
 * @return string
 */
function jn_defer_non_critical_scripts( $tag, $handle ) {
	if ( is_admin() || false === strpos( $tag, '<script ' ) ) {
		return $tag;
	}

	// On `/tai-khoan/` and single product, defer jQuery and classic WooCommerce scripts to post-paint (`data-jn-script-src`)
	// so they never block FCP/LCP while still executing in strict dependency order right after initial paint.
	if ( is_page( 'tai-khoan' ) || ( function_exists( 'is_account_page' ) && is_account_page() ) || is_singular( 'product' ) ) {
		$is_jq_or_woo = (
			'jquery-core' === $handle ||
			0 === strpos( $handle, 'wc-' ) ||
			0 === strpos( $handle, 'woocommerce' ) ||
			in_array( $handle, array( 'jquery-blockui', 'js-cookie', 'zoom', 'flexslider', 'photoswipe', 'photoswipe-ui-default', 'account-i18n' ), true ) ||
			false !== strpos( $tag, '/woocommerce/' ) ||
			false !== strpos( $tag, '/static/shop/' )
		);
		if ( $is_jq_or_woo && false === strpos( $tag, 'data-jn-script-src=' ) && false === strpos( $tag, 'type="module"' ) ) {
			return (string) preg_replace_callback(
				'/<script\b[^>]*\bsrc=(["\'])([^"\']+)\1[^>]*><\/script>/i',
				static function ( array $matches ): string {
					return '<script type="text/jn-deferred-script" data-jn-script-src="' . esc_url( $matches[2] ) . '"></script>';
				},
				$tag
			);
		}
	}

	if ( false !== strpos( $tag, ' defer' ) || false !== strpos( $tag, ' async' ) || false !== strpos( $tag, 'type="module"' ) || false !== strpos( $tag, "type='module'" ) ) {
		return $tag;
	}

	$defer_handles = array(
		'ct-scripts',
		'jn-booking-form-view-script',
	);

	if ( in_array( $handle, $defer_handles, true ) ) {
		$tag = str_replace( ' src=', ' defer src=', $tag );
	}

	return $tag;
}
add_filter( 'script_loader_tag', 'jn_defer_non_critical_scripts', 20, 2 );

/**
 * Load non-inlined parent/commerce stylesheets asynchronously (`media="print" onload="this.media='all'"`)
 * while critical layout & Jannie design system CSS is already inlined in `<head>` (Task 3).
 *
 * @param string $html   Link tag HTML.
 * @param string $handle Style handle.
 * @return string
 */
function jn_defer_non_critical_stylesheets( $html, $handle ) {
	if ( is_admin() || false === strpos( $html, "rel='stylesheet'" ) && false === strpos( $html, 'rel="stylesheet"' ) ) {
		return $html;
	}

	$async_style_handles = array(
		'ct-main-styles',
		'ct-woocommerce-styles',
		'woocommerce-general',
		'woocommerce-layout',
		'woocommerce-smallscreen',
		'wc-blocks-style',
	);

	if ( in_array( $handle, $async_style_handles, true ) && false === strpos( $html, 'data-jn-href=' ) ) {
		// Extract only the <link ...> tag for the noscript fallback (do not duplicate inline <style> blocks).
		$noscript_link = $html;
		if ( preg_match( '/<link\b[^>]+>/i', $html, $m ) ) {
			$noscript_link = $m[0];
		}

		// Replace href= with data-jn-href= on the <link> tag so it activates right after initial paint/LCP.
		$async_html = (string) preg_replace_callback(
			'/<link\b[^>]+>/i',
			static function ( array $matches ): string {
				return (string) preg_replace( '/\bhref=(["\'])/i', 'data-jn-href=$1', $matches[0], 1 );
			},
			$html,
			1
		);

		return $async_html . '<noscript>' . $noscript_link . "</noscript>\n";
	}

	return $html;
}
add_filter( 'style_loader_tag', 'jn_defer_non_critical_stylesheets', 20, 2 );

/**
 * Activate deferred stylesheets (`link[data-jn-href]`) and the italic accent WOFF2 font
 * right after initial paint (`window.load + 150ms`) so they never contend with FCP/LCP bandwidth.
 *
 * @return void
 */
function jn_print_deferred_style_and_font_loader() {
	if ( is_admin() ) {
		return;
	}

	$italic_url = content_url( 'themes/jannie/assets/fonts/playfair-display-vi-italic.woff2' );
	if ( class_exists( 'JN_Hardening_HTML_Headers' ) ) {
		$italic_url = JN_Hardening_HTML_Headers::alias_asset_url( $italic_url );
	}
	?>
	<script id="jn-deferred-assets">
	(function(){
		function loadDeferred(){
			var links=document.querySelectorAll('link[data-jn-href]');
			for(var i=0;i<links.length;i++){
				var href=links[i].getAttribute('data-jn-href');
				if(href){links[i].setAttribute('href',href);links[i].removeAttribute('data-jn-href');}
			}
			var scripts=Array.prototype.slice.call(document.querySelectorAll('script[data-jn-script-src]'));
			function loadNextScript(idx){
				if(idx>=scripts.length){return;}
				var s=scripts[idx];
				var src=s.getAttribute('data-jn-script-src');
				s.removeAttribute('data-jn-script-src');
				if(!src){loadNextScript(idx+1);return;}
				var el=document.createElement('script');
				el.src=src;
				el.async=false;
				el.onload=function(){loadNextScript(idx+1);};
				el.onerror=function(){loadNextScript(idx+1);};
				document.body.appendChild(el);
			}
			loadNextScript(0);
			if('FontFace' in window&&document.fonts){
				var f=new FontFace('Playfair Display',"url('<?php echo esc_url( $italic_url ); ?>') format('woff2')",{style:'italic',weight:'400 900',display:'swap'});
				f.load().then(function(l){document.fonts.add(l);}).catch(function(){});
			}
		}
		if(document.readyState==='complete'){setTimeout(loadDeferred,150);}
		else{window.addEventListener('load',function(){setTimeout(loadDeferred,150);},{once:true});}
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'jn_print_deferred_style_and_font_loader', 99 );

/**
 * Strip `woocommerce-no-js` body class on non-commerce content pages.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function jn_clean_non_commerce_body_classes( $classes ) {
	if ( ! is_admin() && ! jn_is_commerce_page() ) {
		$classes = array_diff( $classes, array( 'woocommerce-no-js', 'woocommerce-js' ) );
	}
	return $classes;
}
add_filter( 'body_class', 'jn_clean_non_commerce_body_classes', 99 );

// =============================================================================
// Task 4 & Task 5 — AVIF + WebP Output Format, Responsive Sizes & LCP Preload
// =============================================================================

/**
 * Generate AVIF sub-sizes (with WebP fallback when AVIF is unsupported) for uploads (Task 5).
 *
 * @param array<string, string> $formats Mapping of input MIME types to output MIME types.
 * @return array<string, string>
 */
function jn_filter_image_editor_output_format( $formats ) {
	if ( ! is_array( $formats ) ) {
		$formats = array();
	}

	if ( function_exists( 'wp_image_editor_supports' ) && wp_image_editor_supports( array( 'mime_type' => 'image/avif' ) ) ) {
		$formats['image/jpeg'] = 'image/avif';
		$formats['image/png']  = 'image/avif';
		$formats['image/webp'] = 'image/avif';
	} else {
		$formats['image/jpeg'] = 'image/webp';
		$formats['image/png']  = 'image/webp';
	}

	return $formats;
}
add_filter( 'image_editor_output_format', 'jn_filter_image_editor_output_format', 20 );

/**
 * Use optimal AVIF quantization quality (58 in libavif corresponds to ~82 perceptual WebP quality)
 * so generated AVIF sub-sizes are compact and pass Lighthouse `image-delivery-insight` (Task 5).
 *
 * @param int    $quality   Default editor quality (82).
 * @param string $mime_type Output MIME type.
 * @return int
 */
function jn_filter_avif_editor_quality( $quality, $mime_type ) {
	if ( 'image/avif' === $mime_type ) {
		return 50;
	}
	return $quality;
}
add_filter( 'wp_editor_set_quality', 'jn_filter_avif_editor_quality', 20, 2 );

/**
 * Only omit `loading="lazy"` on the first content image (Hero LCP) on pages with an above-the-fold hero image;
 * lazy-load all other content images (Task 4).
 *
 * @return int
 */
function jn_filter_omit_loading_attr_threshold() {
	if ( is_front_page() || is_page( 'coaching' ) || is_singular( 'product' ) ) {
		return 1;
	}
	return 0;
}
add_filter( 'wp_omit_loading_attr_threshold', 'jn_filter_omit_loading_attr_threshold', 99 );

/**
 * Prefer AVIF full-dimension sub-sizes (`jn-hero-800`, `jn-expert-750`, `jn-avatar-160`) in `srcset`
 * while keeping the original `.webp` URL in `<img src="...">` as the universal fallback (Task 5).
 *
 * @param array<int, array<string, mixed>> $sources       Srcset sources array keyed by width.
 * @param int[]                            $size_array    Requested width and height.
 * @param string                           $image_src     Image `src` URL.
 * @param array<string, mixed>             $image_meta    Attachment metadata.
 * @param int                              $attachment_id Attachment ID.
 * @return array<int, array<string, mixed>>
 */
function jn_prefer_avif_in_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
	unset( $image_src, $attachment_id );

	if ( ! is_array( $sources ) || empty( $image_meta['sizes'] ) || ! is_array( $image_meta['sizes'] ) || empty( $image_meta['file'] ) ) {
		return $sources;
	}

	$req_w   = isset( $size_array[0] ) ? (int) $size_array[0] : 0;
	$req_h   = isset( $size_array[1] ) ? (int) $size_array[1] : 0;
	$dirname = _wp_get_attachment_relative_path( (string) $image_meta['file'] );
	$dirname = $dirname ? trailingslashit( $dirname ) : '';
	$uploads = wp_get_upload_dir();
	$baseurl = trailingslashit( $uploads['baseurl'] ) . $dirname;

	foreach ( $image_meta['sizes'] as $subsize ) {
		if ( ! is_array( $subsize ) || empty( $subsize['width'] ) || empty( $subsize['height'] ) || empty( $subsize['file'] ) ) {
			continue;
		}
		$w    = (int) $subsize['width'];
		$h    = (int) $subsize['height'];
		$mime = isset( $subsize['mime-type'] ) ? (string) $subsize['mime-type'] : '';
		if ( 'image/avif' === $mime && isset( $sources[ $w ] ) && ( $req_w <= 0 || wp_image_matches_ratio( $req_w, $req_h, $w, $h ) ) ) {
			$sources[ $w ]['url'] = $baseurl . $subsize['file'];
		}
	}

	// If a full-size .avif companion exists on disk alongside the .webp original, use it in srcset for the full width.
	if ( ! empty( $image_meta['width'] ) && isset( $sources[ (int) $image_meta['width'] ] ) ) {
		$full_w    = (int) $image_meta['width'];
		$avif_file = preg_replace( '/\.(?:webp|jpe?g|png)$/i', '.avif', wp_basename( (string) $image_meta['file'] ) );
		$avif_path = trailingslashit( $uploads['basedir'] ) . $dirname . $avif_file;
		if ( is_string( $avif_file ) && is_file( $avif_path ) ) {
			$sources[ $full_w ]['url'] = $baseurl . $avif_file;
		}
	}

	return $sources;
}
add_filter( 'wp_calculate_image_srcset', 'jn_prefer_avif_in_srcset', 20, 5 );

/**
 * Enforce accurate `sizes`, `srcset`, `loading`, `fetchpriority`, and `width`/`height` on content images (Task 4).
 *
 * @param string $filtered_image Full `<img>` tag markup.
 * @param string $context        Context name.
 * @param int    $attachment_id  Attachment ID.
 * @return string
 */
function jn_optimize_content_img_tag( $filtered_image, $context, $attachment_id ) {
	unset( $context );

	if ( '' === $filtered_image ) {
		return $filtered_image;
	}

	// Strip WordPress 6.7+ `auto, ` prefix from sizes so preload `imagesizes` and `<img>` `sizes` match 1:1.
	$filtered_image = str_replace( 'sizes="auto, ', 'sizes="', $filtered_image );

	$media_map = get_option( 'jn_step3_media_map', array() );
	$hero_id   = isset( $media_map['hero_portrait']['id'] ) ? (int) $media_map['hero_portrait']['id'] : 86;
	$expert_id = isset( $media_map['expert_consultation']['id'] ) ? (int) $media_map['expert_consultation']['id'] : 87;

	// 1. Hero 4:5 portrait (above the fold LCP image): eager + fetchpriority="high" + exact responsive sizes & srcset.
	if ( $attachment_id === $hero_id || false !== strpos( $filtered_image, 'wp-image-' . $hero_id ) ) {
		$hero_sizes     = '(max-width: 767px) 228px, (max-width: 1023px) 340px, 384px';
		$hero_srcset    = wp_get_attachment_image_srcset( $hero_id, 'full' );
		$filtered_image = (string) preg_replace( '/\s+loading=(["\'])[^"\']*\1/i', '', $filtered_image );
		$filtered_image = (string) preg_replace( '/\s+fetchpriority=(["\'])[^"\']*\1/i', '', $filtered_image );

		if ( $hero_srcset && false === strpos( $filtered_image, 'srcset=' ) ) {
			$filtered_image = str_replace( '<img ', '<img srcset="' . esc_attr( $hero_srcset ) . '" ', $filtered_image );
		}

		if ( preg_match( '/\ssizes=(["\'])[^"\']*\1/i', $filtered_image ) ) {
			$filtered_image = (string) preg_replace(
				'/\ssizes=(["\'])[^"\']*\1/i',
				' sizes="' . esc_attr( $hero_sizes ) . '"',
				$filtered_image
			);
		} else {
			$filtered_image = str_replace( '<img ', '<img sizes="' . esc_attr( $hero_sizes ) . '" ', $filtered_image );
		}

		$filtered_image = str_replace( '<img ', '<img loading="eager" fetchpriority="high" decoding="async" ', $filtered_image );
		return $filtered_image;
	}

	// 2. Expert 3:4 portrait (below the fold in Section 4): always lazy-loaded, never fetchpriority="high".
	if ( $attachment_id === $expert_id || false !== strpos( $filtered_image, 'wp-image-' . $expert_id ) ) {
		$expert_sizes   = '(max-width: 767px) 240px, (max-width: 1023px) 360px, 420px';
		$expert_srcset  = wp_get_attachment_image_srcset( $expert_id, 'full' );
		$filtered_image = (string) preg_replace( '/\s+loading=(["\'])[^"\']*\1/i', '', $filtered_image );
		$filtered_image = (string) preg_replace( '/\s+fetchpriority=(["\'])[^"\']*\1/i', '', $filtered_image );

		if ( $expert_srcset && false === strpos( $filtered_image, 'srcset=' ) ) {
			$filtered_image = str_replace( '<img ', '<img srcset="' . esc_attr( $expert_srcset ) . '" ', $filtered_image );
		}

		if ( preg_match( '/\ssizes=(["\'])[^"\']*\1/i', $filtered_image ) ) {
			$filtered_image = (string) preg_replace(
				'/\ssizes=(["\'])[^"\']*\1/i',
				' sizes="' . esc_attr( $expert_sizes ) . '"',
				$filtered_image
			);
		} else {
			$filtered_image = str_replace( '<img ', '<img sizes="' . esc_attr( $expert_sizes ) . '" ', $filtered_image );
		}

		$filtered_image = str_replace( '<img ', '<img loading="lazy" decoding="async" ', $filtered_image );
		return $filtered_image;
	}

	// 3. All other content images below the fold: ensure `loading="lazy"` and no `fetchpriority="high"`.
	$filtered_image = (string) preg_replace( '/\s+fetchpriority=(["\'])[^"\']*\1/i', '', $filtered_image );
	if ( false === strpos( $filtered_image, 'loading=' ) ) {
		$filtered_image = str_replace( '<img ', '<img loading="lazy" ', $filtered_image );
	} else {
		$filtered_image = (string) preg_replace( '/\s+loading=(["\'])eager\1/i', ' loading="lazy"', $filtered_image );
	}

	return $filtered_image;
}
add_filter( 'wp_content_img_tag', 'jn_optimize_content_img_tag', 99, 3 );

/**
 * Optimize attachment image attributes for Testimonial avatars (rendered via `core/post-featured-image`)
 * and single product images (Task 4).
 *
 * @param array<string, string> $attr       Image attributes.
 * @param WP_Post               $attachment Image attachment post.
 * @param string|int[]          $size       Requested image size.
 * @return array<string, string>
 */
function jn_optimize_attachment_image_attributes( $attr, $attachment, $size ) {
	if ( is_admin() || ! is_array( $attr ) ) {
		return $attr;
	}

	if ( isset( $attr['sizes'] ) ) {
		$attr['sizes'] = str_replace( 'auto, ', '', (string) $attr['sizes'] );
	}

	$att_id    = $attachment instanceof WP_Post ? (int) $attachment->ID : 0;
	$media_map = get_option( 'jn_step3_media_map', array() );
	$avatar_ids = array(
		isset( $media_map['avatar_1']['id'] ) ? (int) $media_map['avatar_1']['id'] : 88,
		isset( $media_map['avatar_2']['id'] ) ? (int) $media_map['avatar_2']['id'] : 89,
		isset( $media_map['avatar_3']['id'] ) ? (int) $media_map['avatar_3']['id'] : 90,
	);

	if ( in_array( $att_id, $avatar_ids, true ) || 'jn-avatar-96' === $size || 'jn-avatar-160' === $size ) {
		$attr['loading']  = 'lazy';
		$attr['decoding'] = 'async';
		$attr['sizes']    = '48px';
		unset( $attr['fetchpriority'] );
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'jn_optimize_attachment_image_attributes', 99, 3 );

/**
 * Preload ONLY the above-the-fold LCP image in `<head>` with `imagesrcset`, `imagesizes`,
 * and `fetchpriority="high"` on pages where an image is the LCP element (Task 4).
 *
 * @return void
 */
function jn_preload_lcp_image() {
	if ( is_admin() ) {
		return;
	}

	// `/` and `/coaching/` use the Hero 4:5 portrait as the LCP element.
	if ( is_front_page() || is_page( 'coaching' ) ) {
		$media_map = get_option( 'jn_step3_media_map', array() );
		$hero_id   = isset( $media_map['hero_portrait']['id'] ) ? (int) $media_map['hero_portrait']['id'] : 86;
		$src       = wp_get_attachment_image_url( $hero_id, 'full' );
		$srcset    = wp_get_attachment_image_srcset( $hero_id, 'full' );
		$sizes     = '(max-width: 767px) 228px, (max-width: 1023px) 340px, 384px';

		if ( $src && $srcset ) {
			printf(
				'<link rel="preload" as="image" href="%s" imagesrcset="%s" imagesizes="%s" fetchpriority="high" />' . "\n",
				esc_url( $src ),
				esc_attr( $srcset ),
				esc_attr( $sizes )
			);
		}
		return;
	}

	// Single product page with a featured image.
	if ( is_singular( 'product' ) && has_post_thumbnail() ) {
		$thumb_id = (int) get_post_thumbnail_id();
		$src      = wp_get_attachment_image_url( $thumb_id, 'full' );
		$srcset   = wp_get_attachment_image_srcset( $thumb_id, 'full' );
		$sizes    = '(max-width: 767px) 372px, 560px';

		if ( $src ) {
			if ( $srcset ) {
				printf(
					'<link rel="preload" as="image" href="%s" imagesrcset="%s" imagesizes="%s" fetchpriority="high" />' . "\n",
					esc_url( $src ),
					esc_attr( $srcset ),
					esc_attr( $sizes )
				);
			} else {
				printf(
					'<link rel="preload" as="image" href="%s" fetchpriority="high" />' . "\n",
					esc_url( $src )
				);
			}
		}
	}
}
add_action( 'wp_head', 'jn_preload_lcp_image', 1 );

// =============================================================================
// Task 7 — Speculative Loading Exclusions (Core Filter)
// =============================================================================

/**
 * Add commerce, account, logout, order-pay, and booking endpoints to WordPress Core's
 * speculative loading exclusion filter (Task 7).
 *
 * @param string[] $exclude_paths Default excluded path patterns.
 * @return string[]
 */
function jn_filter_speculation_rules_exclude_paths( $exclude_paths ) {
	if ( ! is_array( $exclude_paths ) ) {
		$exclude_paths = array();
	}

	$custom_exclusions = array(
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
		'/phong-lam-viec-7429/*',
	);

	return array_values( array_unique( array_merge( $exclude_paths, $custom_exclusions ) ) );
}
add_filter( 'wp_speculation_rules_href_exclude_paths', 'jn_filter_speculation_rules_exclude_paths', 99 );

// =============================================================================
// Task 8 — Third-Party Facade Loader (None by default; interaction/consent gated)
// =============================================================================

/**
 * Return approved third-party widget definitions (default empty per §5 & Task 8).
 * Any future approved widget (GA4, Meta Pixel, Zalo chat) registered via the
 * `jn_approved_third_party_widgets` filter will only load after user interaction or consent.
 *
 * @return array<int, array<string, string>>
 */
function jn_get_approved_third_party_widgets() {
	$widgets = apply_filters( 'jn_approved_third_party_widgets', array() );
	return is_array( $widgets ) ? $widgets : array();
}

/**
 * Print interaction/consent facade loader in `wp_footer` only when approved widgets exist.
 *
 * @return void
 */
function jn_print_third_party_facade_loader() {
	if ( is_admin() ) {
		return;
	}

	$widgets = jn_get_approved_third_party_widgets();
	if ( empty( $widgets ) ) {
		return;
	}

	$encoded = wp_json_encode( array_values( $widgets ), JSON_UNESCAPED_SLASHES );
	if ( ! is_string( $encoded ) ) {
		return;
	}
	?>
	<script id="jn-third-party-facade">
	(function(){
		var loaded=false,widgets=<?php echo $encoded; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
		function boot(){
			if(loaded||window.jnConsentGranted===false)return;
			loaded=true;
			widgets.forEach(function(w){
				if(!w||!w.src)return;
				var s=document.createElement('script');
				s.src=w.src;s.async=true;
				if(w.id)s.id=w.id;
				document.body.appendChild(s);
			});
		}
		['pointerdown','keydown','touchstart','scroll'].forEach(function(ev){
			window.addEventListener(ev,boot,{once:true,passive:true});
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'jn_print_third_party_facade_loader', 99 );

// =============================================================================
// Task 9 — SEO / AEO (Step 10 refactor: modules in jn-performance/class-jn-seo-*.php)
// =============================================================================

require_once __DIR__ . '/jn-performance/class-jn-seo-brand.php';
require_once __DIR__ . '/jn-performance/class-jn-seo-meta.php';
require_once __DIR__ . '/jn-performance/class-jn-seo-robots.php';
require_once __DIR__ . '/jn-performance/class-jn-seo-sitemap.php';
require_once __DIR__ . '/jn-performance/class-jn-seo-schema.php';
require_once __DIR__ . '/jn-performance/class-jn-seo-llms.php';

JN_SEO_Meta::init();
JN_SEO_Robots::init();
JN_SEO_Sitemap::init();
JN_SEO_Schema::init();
JN_SEO_Llms::init();

// =============================================================================
// Task 10 — Database Autoload Hygiene & Expired Transient Cleanup
// =============================================================================

/**
 * Keep expired transients cleared and prevent non-critical transients/admin caches
 * from bloating autoloaded options above the 65 KB budget (Task 10).
 *
 * @return void
 */
function jn_maintain_database_hygiene() {
	global $wpdb;

	if ( ! ( $wpdb instanceof wpdb ) ) {
		return;
	}

	$now = time();

	// 1. Purge any expired transients and their value rows.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$expired_timeouts = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND CAST(option_value AS UNSIGNED) > 0 AND CAST(option_value AS UNSIGNED) < %d",
			$wpdb->esc_like( '_transient_timeout_' ) . '%',
			$now
		)
	);

	if ( ! empty( $expired_timeouts ) ) {
		foreach ( $expired_timeouts as $timeout_key ) {
			$transient_name = substr( (string) $timeout_key, strlen( '_transient_timeout_' ) );
			delete_transient( $transient_name );
		}
	}

	// 2. Keep transient rows and bulky admin-only options out of autoload.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		"UPDATE {$wpdb->options}
		SET autoload = 'off'
		WHERE autoload IN ('yes', 'on', 'auto', 'auto-on')
		AND (
			option_name LIKE '\\_transient\\_%'
			OR option_name LIKE '\\_site\\_transient\\_%'
			OR option_name LIKE 'woocommerce\\_admin\\_%'
			OR option_name LIKE 'woocommerce\\_marketplace\\_%'
			OR option_name LIKE 'wc\\_remote\\_%'
			OR option_name IN ('cron', 'rewrite_rules', 'wp_user_roles', 'autodescription-site-settings')
		)"
	);
}
add_action( 'init', 'jn_maintain_database_hygiene', 20 );
