<?php
/**
 * Plugin Name: Jannie Performance & Asset Scoping
 * Description: Prevents WooCommerce CSS/JS, cart-fragments, and Order Attribution scripts from loading on non-commerce content pages.
 * Version: 1.0.0
 * Author: Jannie Nguyen Academy
 *
 * @package JN_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine whether the current frontend request is a WooCommerce commerce surface.
 *
 * Returns true only on:
 * - Cart, Checkout (including order-pay / order-received endpoints), My Account (and its endpoints)
 * - Shop / My Favorite Product page (`/my-favorite-product/`)
 * - Single `product` or product taxonomy archives
 * - Any page containing a `woocommerce/*` block or `[woocommerce_*]` shortcode
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
 * Disable WooCommerce Order Attribution tracking scripts and cookies site-wide (ADR-005).
 */
add_filter( 'pre_option_woocommerce_feature_order_attribution_enabled', '__return_no_string' );
add_filter( 'pre_option_woocommerce_allow_tracking', '__return_no_string' );
add_filter( 'pre_option_woocommerce_show_marketplace_suggestions', '__return_no_string' );

/**
 * Return `'no'` for WooCommerce boolean option filters.
 *
 * @return string
 */
function __return_no_string() {
	return 'no';
}

/**
 * Scope WooCommerce and Blocksy WooCommerce assets strictly to commerce pages.
 *
 * Also disables `wc-cart-fragments` everywhere (no header mini-cart exists in the wireframe)
 * and removes `wc-order-attribution` / `sourcebuster-js`.
 *
 * @return void
 */
function jn_scope_woocommerce_assets() {
	if ( is_admin() ) {
		return;
	}

	// Never load cart fragments or order attribution anywhere on the frontend.
	$always_blocked_scripts = array(
		'wc-cart-fragments',
		'wc-order-attribution',
		'sourcebuster-js',
	);

	foreach ( $always_blocked_scripts as $handle ) {
		wp_dequeue_script( $handle );
		wp_deregister_script( $handle );
	}

	if ( jn_is_commerce_page() ) {
		return;
	}

	// On content pages, remove all WooCommerce & Blocksy WooCommerce styles.
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

	// On content pages, remove all WooCommerce scripts and jQuery (pulled in only by WooCommerce).
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

	// Remove inline wc_no_js footer script on content pages.
	remove_action( 'wp_footer', 'wc_no_js' );
}
add_action( 'wp_enqueue_scripts', 'jn_scope_woocommerce_assets', 999 );
add_action( 'wp_print_styles', 'jn_scope_woocommerce_assets', 999 );
add_action( 'wp_print_scripts', 'jn_scope_woocommerce_assets', 999 );
add_action( 'wp_footer', 'jn_scope_woocommerce_assets', 1 );

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
