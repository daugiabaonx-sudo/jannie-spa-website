<?php
/**
 * Custom 404 template for the Jannie child theme.
 *
 * Renders the `jannie/error-404` Gutenberg block pattern inside the global
 * header and footer shell without exposing any default WordPress or Blocksy
 * error strings.
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

echo '<main id="main" class="site-main hfeed" role="main">';
echo jannie_render_404_pattern_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered via do_blocks().
echo '</main>';

get_footer();
