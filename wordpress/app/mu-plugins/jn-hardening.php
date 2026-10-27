<?php
/**
 * Plugin Name: JN Hardening & Hide-WordPress (MU)
 * Description: Site-wide security policies, custom REST API prefix (/api), and local Mailpit SMTP routing for Jannie Nguyen.
 * Version:     1.0.0
 * Author:      Jannie Nguyen Engineering
 * License:     GPL-2.0-or-later
 *
 * @package JannieNguyen\MU
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 1. Change the REST API URL prefix from "wp-json" to "api" (PROJECT CONTEXT CARD).
 *
 * Confirmed hook in wp-includes/rest-api.php: apply_filters( 'rest_url_prefix', 'wp-json' ).
 *
 * @return string
 */
function jn_hardening_rest_url_prefix(): string {
	return 'api';
}
add_filter( 'rest_url_prefix', 'jn_hardening_rest_url_prefix', 10, 0 );

/**
 * 2. Flush rewrite rules once after the custom REST prefix is registered.
 *
 * @return void
 */
function jn_hardening_maybe_flush_rewrites(): void {
	if ( ! is_blog_installed() ) {
		return;
	}

	$flag_option = 'jn_hardening_rewrite_flushed_v1';
	if ( '1' !== get_option( $flag_option ) ) {
		flush_rewrite_rules( false );
		update_option( $flag_option, '1', true );
	}
}
add_action( 'init', 'jn_hardening_maybe_flush_rewrites', 99 );

/**
 * 3. Ensure valid From address and sender name (prevents PHPMailer rejecting wordpress@localhost).
 *
 * Confirmed hooks in wp-includes/pluggable.php: wp_mail_from and wp_mail_from_name.
 *
 * @param string $from_email Default From email address.
 * @return string
 */
function jn_hardening_mail_from( string $from_email ): string {
	if ( str_starts_with( $from_email, 'wordpress@' ) ) {
		return 'contact@jannienguyen.vn';
	}
	return $from_email;
}
add_filter( 'wp_mail_from', 'jn_hardening_mail_from', 10, 1 );

/**
 * Filter default From name so outgoing emails identify as Jannie Nguyen instead of WordPress.
 *
 * @param string $from_name Default From name.
 * @return string
 */
function jn_hardening_mail_from_name( string $from_name ): string {
	if ( 'WordPress' === $from_name || '' === trim( $from_name ) ) {
		return 'Jannie Nguyen';
	}
	return $from_name;
}
add_filter( 'wp_mail_from_name', 'jn_hardening_mail_from_name', 10, 1 );

/**
 * 4. Route all WordPress emails to Mailpit in the local environment only.
 *
 * Confirmed hook in wp-includes/pluggable.php: do_action_ref_array( 'phpmailer_init', array( &$phpmailer ) ).
 *
 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance passed by reference.
 * @return void
 */
function jn_hardening_local_mailpit( $phpmailer ): void {
	if ( ! function_exists( 'wp_get_environment_type' ) || 'local' !== wp_get_environment_type() ) {
		return;
	}

	$smtp_host = getenv( 'SMTP_HOST' );
	$smtp_port = getenv( 'SMTP_PORT' );

	// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$phpmailer->isSMTP();
	$phpmailer->Host        = ( false !== $smtp_host && '' !== $smtp_host ) ? $smtp_host : 'mailpit';
	$phpmailer->Port        = ( false !== $smtp_port && '' !== $smtp_port ) ? (int) $smtp_port : 1025;
	$phpmailer->SMTPAuth    = false;
	$phpmailer->SMTPSecure  = '';
	$phpmailer->SMTPAutoTLS = false;
	// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
}
add_action( 'phpmailer_init', 'jn_hardening_local_mailpit', 10, 1 );

/**
 * 5. Return a direct HTTP 404 (instead of a 302 redirect to /404/) when a
 *    logged-out visitor requests /wp-admin/.
 *
 * Confirmed hook in wps-hide-login/classes/plugin.php: do_action( 'wps_hide_login_before_redirect', $request ).
 *
 * @param array<string, mixed> $request Parsed request URI components.
 * @return void
 */
function jn_hardening_logged_out_admin_404( array $request ): void {
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
		status_header( 404 );
		nocache_headers();
		$template_404 = get_query_template( '404' );
		if ( $template_404 && file_exists( $template_404 ) ) {
			include $template_404;
		} else {
			echo '<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><title>404 Not Found</title></head><body><h1>404 Not Found</h1></body></html>';
		}
		exit;
	}
}
add_action( 'wps_hide_login_before_redirect', 'jn_hardening_logged_out_admin_404', 10, 1 );

