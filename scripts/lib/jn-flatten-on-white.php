<?php
/**
 * Read an image on STDIN and write it to STDOUT as an opaque PNG composited on white.
 *
 * Used by `upscale-step-3-media.mjs` for single-ink logo items (`ink` in the items file): Real-ESRGAN upscales
 * the flattened logo, then `jn_media_ink_to_alpha()` rebuilds the transparency at import time. Feeding the
 * transparent PNG to Real-ESRGAN directly leaves a dark fringe, because it upscales colour and alpha separately.
 *
 * Runs in the wpcli container (the host has no PHP/GD), streaming through STDIN/STDOUT so the read-only
 * `/scripts` mount needs no write access:
 *   docker compose run --rm -T --entrypoint php wpcli /scripts/lib/jn-flatten-on-white.php < logo.png > logo.flat.png
 *
 * @package JN_Scripts
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$jn_data = (string) stream_get_contents( STDIN );
$jn_src  = '' !== $jn_data ? @imagecreatefromstring( $jn_data ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
if ( ! $jn_src ) {
	fwrite( STDERR, "[ERROR] STDIN is not a decodable image.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	exit( 1 );
}

imagepalettetotruecolor( $jn_src );
$jn_w    = imagesx( $jn_src );
$jn_h    = imagesy( $jn_src );
$jn_flat = imagecreatetruecolor( $jn_w, $jn_h );
imagefill( $jn_flat, 0, 0, imagecolorallocate( $jn_flat, 255, 255, 255 ) );
imagealphablending( $jn_flat, true );
imagecopy( $jn_flat, $jn_src, 0, 0, 0, 0, $jn_w, $jn_h );

if ( ! imagepng( $jn_flat ) ) {
	fwrite( STDERR, "[ERROR] Could not encode the flattened PNG.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	exit( 1 );
}
