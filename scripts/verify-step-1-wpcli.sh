#!/usr/bin/env sh
# =============================================================================
# JN STEP 1 — WP-CLI Verification Checks
# =============================================================================
set -eu

echo "=== 1. WordPress Core Version & Language ==="
wp core version --extra
wp language core list --status=active

echo ""
echo "=== 2. Database Table Prefix (must not be wp_) ==="
printf "wp db prefix: "
wp db prefix

echo ""
echo "=== 3. Active Theme & Recovery Theme Status ==="
wp theme list

echo ""
echo "=== 4. Active Plugins & MU-Plugins ==="
wp plugin list

echo ""
echo "=== 5. PHP Extensions Check (gd, imagick, intl, zip, exif, opcache) ==="
wp eval '
$required = array( "gd", "imagick", "intl", "zip", "exif", "Zend OPcache" );
foreach ( $required as $ext ) {
	printf( "%-15s : %s\n", $ext, extension_loaded( $ext ) ? "LOADED" : "MISSING" );
}
printf( "memory_limit    : %s\n", ini_get( "memory_limit" ) );
printf( "upload_max_size : %s\n", ini_get( "upload_max_filesize" ) );
printf( "error_log       : %s\n", ini_get( "error_log" ) );
'

echo ""
echo "=== 6. Active Image Editor + AVIF & WebP Support via wp eval ==="
wp eval '
$editor_webp = _wp_image_editor_choose( array( "mime_type" => "image/webp" ) );
$editor_avif = _wp_image_editor_choose( array( "mime_type" => "image/avif" ) );
$supports_webp = wp_image_editor_supports( array( "mime_type" => "image/webp" ) );
$supports_avif = wp_image_editor_supports( array( "mime_type" => "image/avif" ) );
printf( "Active Editor (WebP): %s (supports image/webp = %s)\n", $editor_webp ?: "NONE", $supports_webp ? "YES" : "NO" );
printf( "Active Editor (AVIF): %s (supports image/avif = %s)\n", $editor_avif ?: "NONE", $supports_avif ? "YES" : "NO" );
'

echo ""
echo "=== 7. Directory & URL Constants Verification (Card Names) ==="
wp eval '
$upload_dir = wp_upload_dir();
printf( "WP_CONTENT_DIR  : %s\n", WP_CONTENT_DIR );
printf( "WP_CONTENT_URL  : %s\n", WP_CONTENT_URL );
printf( "WP_PLUGIN_DIR   : %s\n", WP_PLUGIN_DIR );
printf( "WP_PLUGIN_URL   : %s\n", WP_PLUGIN_URL );
printf( "WPMU_PLUGIN_DIR : %s\n", WPMU_PLUGIN_DIR );
printf( "WPMU_PLUGIN_URL : %s\n", WPMU_PLUGIN_URL );
printf( "UPLOADS         : %s\n", UPLOADS );
printf( "Upload Base URL : %s\n", $upload_dir["baseurl"] );
printf( "REST API URL    : %s\n", rest_url() );
'

echo ""
echo "=== 8. Core Checksums Verification (wp core verify-checksums) ==="
wp core verify-checksums

echo ""
echo "=== 9. Database Snapshot (backups/jn-step-1.sql.gz) ==="
wp db export - | gzip -9 > /backups/jn-step-1.sql.gz
ls -lh /backups/jn-step-1.sql.gz
