<?php
/**
 * Title: Hero Editorial (8:4 Asymmetric)
 * Slug: jannie/hero-editorial
 * Categories: jannie-nguyen
 * Description: Phân đoạn Hero bất đối xứng 8:4 với tiêu đề H1, thanh 3 chỉ số (contentOnly) và khung ảnh chân dung 4:5 kèm hộp kính mờ.
 * Viewport Width: 1440
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$jn_hero_media = jannie_get_placeholder_media( 'hero_portrait', 'jn-hero-portrait.webp' );
?>
<!-- wp:group {"tagName":"section","metadata":{"name":"Section 1 — Hero Editorial"},"align":"full","className":"jn-section jn-hero-section"} -->
<section class="wp-block-group alignfull jn-section jn-hero-section">
	<!-- wp:group {"className":"jn-container jn-hero-inner"} -->
	<div class="wp-block-group jn-container jn-hero-inner">
		<!-- wp:columns {"className":"jn-hero-grid"} -->
		<div class="wp-block-columns jn-hero-grid">
			<!-- wp:column {"width":"66.66%","className":"jn-hero-col-left"} -->
			<div class="wp-block-column jn-hero-col-left" style="flex-basis:66.66%">
				<!-- wp:paragraph {"className":"is-style-eyebrow-dot jn-hero-eyebrow"} -->
				<p class="is-style-eyebrow-dot jn-hero-eyebrow">Private Mentorship &amp; Advisory Program</p>
				<!-- /wp:paragraph -->

				<!-- wp:heading {"level":1,"className":"jn-hero-title"} -->
				<h1 class="wp-block-heading jn-hero-title">Coaching &amp; Đồng Hành <em class="jn-accent-italic">Chuyên Sâu</em></h1>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"className":"jn-hero-lead","textColor":"muted-stone","fontSize":"body-lg"} -->
				<p class="jn-hero-lead has-muted-stone-color has-text-color has-body-lg-font-size">Chương trình cố vấn 1:1 độc quyền cùng Chuyên gia Jannie Nguyễn dành cho cá nhân định hình vẻ đẹp tự nhiên và các chủ cơ sở thẩm mỹ, phun xăm, spa cao cấp.</p>
				<!-- /wp:paragraph -->

				<!-- wp:group {"templateLock":"contentOnly","className":"is-style-metrics-bar jn-hero-metrics"} -->
				<div class="wp-block-group is-style-metrics-bar jn-hero-metrics">
					<!-- wp:group {"className":"jn-metric-item"} -->
					<div class="wp-block-group jn-metric-item">
						<!-- wp:paragraph {"className":"jn-metric-value"} -->
						<p class="jn-metric-value">20+</p>
						<!-- /wp:paragraph -->
						<!-- wp:paragraph {"className":"jn-metric-label"} -->
						<p class="jn-metric-label">Năm Thực Chiến</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"jn-metric-item"} -->
					<div class="wp-block-group jn-metric-item">
						<!-- wp:paragraph {"className":"jn-metric-value"} -->
						<p class="jn-metric-value">1:1</p>
						<!-- /wp:paragraph -->
						<!-- wp:paragraph {"className":"jn-metric-label"} -->
						<p class="jn-metric-label">Cố Vấn Độc Bản</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:group {"className":"jn-metric-item"} -->
					<div class="wp-block-group jn-metric-item">
						<!-- wp:paragraph {"className":"jn-metric-value"} -->
						<p class="jn-metric-value">98%</p>
						<!-- /wp:paragraph -->
						<!-- wp:paragraph {"className":"jn-metric-label"} -->
						<p class="jn-metric-label">Tỷ Lệ Tối Ưu Hóa</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->

				<!-- wp:buttons {"className":"jn-hero-actions"} -->
				<div class="wp-block-buttons jn-hero-actions">
					<!-- wp:button {"className":"is-style-primary-pill has-icon-north-east"} -->
					<div class="wp-block-button is-style-primary-pill has-icon-north-east"><a class="wp-block-button__link wp-element-button" href="#booking-form">Đặt Lịch Coaching Ngay</a></div>
					<!-- /wp:button -->

					<!-- wp:button {"className":"is-style-soft-pill has-icon-south"} -->
					<div class="wp-block-button is-style-soft-pill has-icon-south"><a class="wp-block-button__link wp-element-button" href="#coaching-tiers">Xem Chi Tiết 3 Gói Cố Vấn</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"33.33%","className":"jn-hero-col-right"} -->
			<div class="wp-block-column jn-hero-col-right" style="flex-basis:33.33%">
				<!-- wp:group {"className":"jn-hero-media-card"} -->
				<div class="wp-block-group jn-hero-media-card">
					<!-- wp:image {"id":<?php echo (int) $jn_hero_media['id']; ?>,"sizeSlug":"full","linkDestination":"none","className":"is-style-portrait-4-5 jn-hero-lcp-image"} -->
					<figure class="wp-block-image size-full is-style-portrait-4-5 jn-hero-lcp-image"><img src="<?php echo esc_url( $jn_hero_media['url'] ); ?>" alt="<?php echo esc_attr( $jn_hero_media['alt'] ); ?>" class="wp-image-<?php echo (int) $jn_hero_media['id']; ?>"/></figure>
					<!-- /wp:image -->

					<!-- wp:group {"className":"jn-hero-overlay"} -->
					<div class="wp-block-group jn-hero-overlay">
						<!-- wp:group {"className":"jn-hero-overlay-top"} -->
						<div class="wp-block-group jn-hero-overlay-top">
							<!-- wp:jn/icon {"name":"all_inclusive","size":18} /-->
						</div>
						<!-- /wp:group -->

						<!-- wp:group {"className":"is-style-glass-caption jn-hero-glass-box"} -->
						<div class="wp-block-group is-style-glass-caption jn-hero-glass-box">
							<!-- wp:paragraph {"className":"jn-hero-glass-title"} -->
							<p class="jn-hero-glass-title">Triết Lý Cân Bằng Toàn Diện</p>
							<!-- /wp:paragraph -->

							<!-- wp:paragraph {"className":"jn-hero-glass-desc"} -->
							<p class="jn-hero-glass-desc">Kết hợp tinh hoa kỹ thuật thẩm mỹ và năng lực định vị nhân hiệu bền vững.</p>
							<!-- /wp:paragraph -->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
