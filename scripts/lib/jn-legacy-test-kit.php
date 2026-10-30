<?php
/**
 * Helpers shared by the legacy-content tests (Steps 15–16): visible page text normalised the way
 * wptexturize() changes it, missing-string lookup and a local GET through the web container.
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Visible text of an HTML fragment, normalised the way wptexturize() and the block editor change it.
 *
 * @param string $html HTML.
 * @return string
 */
function jn_lt_text( string $html ): string {
	$html = (string) preg_replace( '#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html );
	$text = html_entity_decode( wp_strip_all_tags( str_replace( '<', ' <', $html ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = str_replace( array( '“', '”', '″', '‘', '’', '–', '—', "\u{00a0}" ), array( '"', '"', '"', "'", "'", '-', '-', ' ' ), $text );
	return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
}

/**
 * Strings from $expected missing in $text.
 *
 * @param string[] $expected Expected strings.
 * @param string   $text     Normalised page text.
 * @return string[]
 */
function jn_lt_missing( array $expected, string $text ): array {
	return array_values( array_filter( $expected, static fn( string $s ): bool => false === mb_strpos( $text, $s ) ) );
}

/**
 * GET a local URL through the web container (follows no redirects).
 *
 * @param string $path Path starting with `/`.
 * @return array{code: int, body: string}
 */
function jn_lt_get( string $path ): array {
	$base = gethostbyname( 'wordpress' ) !== 'wordpress' ? 'http://wordpress' : 'http://localhost:8080';
	$res  = wp_remote_get(
		$base . $path,
		array(
			'timeout'     => 20,
			'redirection' => 0,
			'headers'     => array( 'Host' => 'localhost:8080' ),
		)
	);
	return array(
		'code' => is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res ),
		'body' => is_wp_error( $res ) ? '' : (string) wp_remote_retrieve_body( $res ),
	);
}
