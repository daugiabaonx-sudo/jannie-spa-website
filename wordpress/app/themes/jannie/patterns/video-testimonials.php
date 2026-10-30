<?php
/**
 * Title: Video Testimonials (Query Loop)
 * Slug: jannie/video-testimonials
 * Categories: jannie-nguyen
 * Description: Cảm nhận học viên dạng video (jannieedu.com cũ): Query Loop CPT jn_testimonial, block jn/video (ảnh bìa tự host, chỉ tải YouTube khi bấm) và Block Bindings cho câu tiêu đề, trích dẫn, tên.
 * Viewport Width: 1440
 *
 * Same markup as jn_lb_video_testimonials() in scripts/lib/jn-legacy-blocks.php (test-step-15 checks they match).
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"tagName":"section","metadata":{"name":"Cảm nhận học viên (video)"},"align":"full","className":"jn-section jn-video-testimonials-section"} -->
<section class="wp-block-group alignfull jn-section jn-video-testimonials-section">
<!-- wp:group {"className":"jn-container"} -->
<div class="wp-block-group jn-container">
<!-- wp:query {"queryId":2,"query":{"perPage":6,"pages":0,"offset":0,"postType":"jn_testimonial","order":"asc","orderBy":"menu_order","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"jn-video-testimonials-query"} -->
<div class="wp-block-query jn-video-testimonials-query">
<!-- wp:post-template {"className":"jn-video-testimonials"} -->
<!-- wp:group {"className":"jn-video-testimonial"} -->
<div class="wp-block-group jn-video-testimonial">
<!-- wp:jn/video /-->
<!-- wp:group {"className":"jn-video-testimonial__text"} -->
<div class="wp-block-group jn-video-testimonial__text">
<!-- wp:heading {"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"jn_headline"}}}},"className":"jn-video-testimonial__headline"} -->
<h2 class="wp-block-heading jn-video-testimonial__headline"></h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"jn_quote"}}}},"className":"jn-video-testimonial__quote"} -->
<p class="jn-video-testimonial__quote"></p>
<!-- /wp:paragraph -->
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"jn_client_name"}}}},"className":"jn-video-testimonial__name"} -->
<p class="jn-video-testimonial__name"></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- /wp:post-template -->
</div>
<!-- /wp:query -->
</div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->
