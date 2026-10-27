<?php
/**
 * Title: Testimonials (3-Column Query Loop)
 * Slug: jannie/testimonials-3
 * Categories: jannie-nguyen
 * Description: Phân đoạn Cảm nhận Học viên & Kết quả Thực tế sử dụng Query Loop CPT jn_testimonial, Block Bindings và jn/rating.
 * Viewport Width: 1440
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"tagName":"section","metadata":{"name":"Section 5 — Testimonials"},"align":"full","className":"jn-section jn-testimonials-section"} -->
<section class="wp-block-group alignfull jn-section jn-testimonials-section">
	<!-- wp:group {"className":"jn-container"} -->
	<div class="wp-block-group jn-container">
		<!-- wp:group {"className":"jn-section-header-split jn-testimonials-header"} -->
		<div class="wp-block-group jn-section-header-split jn-testimonials-header">
			<!-- wp:group {"className":"jn-section-header-split__left"} -->
			<div class="wp-block-group jn-section-header-split__left">
				<!-- wp:paragraph {"className":"is-style-eyebrow"} -->
				<p class="is-style-eyebrow">Minh Chứng Thành Công</p>
				<!-- /wp:paragraph -->

				<!-- wp:heading {"level":2,"className":"jn-section-title-md"} -->
				<h2 class="wp-block-heading jn-section-title-md">Cảm Nhận Học Viên &amp; Kết Quả Thực Tế</h2>
				<!-- /wp:heading -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"templateLock":"contentOnly","className":"jn-sparkline-badge"} -->
			<div class="wp-block-group jn-sparkline-badge">
				<!-- wp:group {"className":"jn-sparkline-badge__text"} -->
				<div class="wp-block-group jn-sparkline-badge__text">
					<!-- wp:paragraph {"className":"jn-sparkline-label"} -->
					<p class="jn-sparkline-label">Tăng trưởng TB</p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"jn-sparkline-value"} -->
					<p class="jn-sparkline-value">+180% Doanh Thu</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:jn/icon {"name":"sparkline","size":32} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->

		<!-- wp:query {"queryId":1,"query":{"perPage":3,"pages":0,"offset":0,"postType":"jn_testimonial","order":"asc","orderBy":"menu_order","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"jn-testimonials-query"} -->
		<div class="wp-block-query jn-testimonials-query">
			<!-- wp:post-template {"layout":{"type":"grid","columnCount":3},"className":"jn-testimonials-grid"} -->
				<!-- wp:group {"className":"is-style-card jn-testimonial-card"} -->
				<div class="wp-block-group is-style-card jn-testimonial-card">
					<!-- wp:group {"className":"jn-testimonial-card__top"} -->
					<div class="wp-block-group jn-testimonial-card__top">
						<!-- wp:jn/rating {"size":18} /-->

						<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"jn_quote"}}}},"className":"jn-testimonial-quote"} -->
						<p class="jn-testimonial-quote"></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"jn-testimonial-card__author"} -->
					<div class="wp-block-group jn-testimonial-card__author">
						<!-- wp:post-featured-image {"width":"48px","height":"48px","scale":"cover","sizeSlug":"jn-avatar-96","className":"jn-testimonial-avatar"} /-->

						<!-- wp:group {"className":"jn-testimonial-author-meta"} -->
						<div class="wp-block-group jn-testimonial-author-meta">
							<!-- wp:heading {"level":3,"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"jn_client_name"}}}},"className":"jn-testimonial-name"} -->
							<h3 class="wp-block-heading jn-testimonial-name"></h3>
							<!-- /wp:heading -->

							<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"jn_role_display"}}}},"className":"jn-testimonial-role"} -->
							<p class="jn-testimonial-role"></p>
							<!-- /wp:paragraph -->
						</div>
						<!-- /wp:group -->
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
