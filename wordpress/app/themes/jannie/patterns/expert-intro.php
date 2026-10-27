<?php
/**
 * Title: Expert Intro & Philosophy (5:7 Editorial)
 * Slug: jannie/expert-intro
 * Categories: jannie-nguyen
 * Description: Phân đoạn Giới thiệu Chuyên gia & Phương pháp Triết lý (tỷ lệ 5:7) với khung ảnh 3:4, hộp trích dẫn nổi và 2 danh hiệu H3.
 * Viewport Width: 1440
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$jn_expert_media = jannie_get_placeholder_media( 'expert_consultation', 'jn-placeholder-expert-consultation.webp' );
?>
<!-- wp:group {"tagName":"section","metadata":{"name":"Section 4 — Expert Intro & Philosophy"},"align":"full","backgroundColor":"palette-color-7","className":"jn-section jn-expert-section"} -->
<section class="wp-block-group alignfull jn-section jn-expert-section has-palette-color-7-background-color has-background">
	<!-- wp:group {"className":"jn-container"} -->
	<div class="wp-block-group jn-container">
		<!-- wp:columns {"className":"jn-expert-grid"} -->
		<div class="wp-block-columns jn-expert-grid">
			<!-- wp:column {"width":"41.67%","className":"jn-expert-visual-col"} -->
			<div class="wp-block-column jn-expert-visual-col" style="flex-basis:41.67%">
				<!-- wp:group {"className":"jn-expert-media-wrap"} -->
				<div class="wp-block-group jn-expert-media-wrap">
					<!-- wp:image {"id":<?php echo (int) $jn_expert_media['id']; ?>,"sizeSlug":"full","linkDestination":"none","className":"is-style-portrait-3-4 jn-expert-image"} -->
					<figure class="wp-block-image size-full is-style-portrait-3-4 jn-expert-image"><img src="<?php echo esc_url( $jn_expert_media['url'] ); ?>" alt="<?php echo esc_attr( $jn_expert_media['alt'] ); ?>" class="wp-image-<?php echo (int) $jn_expert_media['id']; ?>" width="750" height="1000" loading="lazy" decoding="async"/></figure>
					<!-- /wp:image -->

					<!-- wp:group {"templateLock":"contentOnly","className":"jn-expert-quote-card"} -->
					<div class="wp-block-group jn-expert-quote-card">
						<!-- wp:jn/icon {"name":"format_quote","size":28} /-->

						<!-- wp:paragraph {"className":"jn-expert-quote-text"} -->
						<p class="jn-expert-quote-text">"Làm đẹp với tình yêu và sự cân bằng là con đường duy nhất dẫn đến sự tự tin trường tồn."</p>
						<!-- /wp:paragraph -->

						<!-- wp:paragraph {"className":"jn-expert-quote-author"} -->
						<p class="jn-expert-quote-author">— Jannie Nguyễn</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"58.33%","className":"jn-expert-narrative-col"} -->
			<div class="wp-block-column jn-expert-narrative-col" style="flex-basis:58.33%">
				<!-- wp:paragraph {"className":"is-style-eyebrow-line jn-expert-eyebrow"} -->
				<p class="is-style-eyebrow-line jn-expert-eyebrow">Chuyên Gia Cố Vấn</p>
				<!-- /wp:paragraph -->

				<!-- wp:heading {"level":2,"className":"jn-expert-title"} -->
				<h2 class="wp-block-heading jn-expert-title">Hơn 20 Năm Đồng Hành Cùng <em class="jn-accent-italic">Vẻ Đẹp Đích Thực</em></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"className":"jn-expert-p","textColor":"muted-stone"} -->
				<p class="jn-expert-p has-muted-stone-color has-text-color">Với chuyên gia Jannie Nguyễn (Nghệ nhân Bàn Tay Vàng ngành phun xăm thẩm mỹ Việt Nam), đào tạo làm đẹp không đơn thuần dừng lại ở việc học thuộc công thức hay thao tác máy móc. Đó là hành trình tìm kiếm sự cân bằng sâu sắc giữa thể chất, tâm trí và cảm xúc thẩm mỹ.</p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"className":"jn-expert-p","textColor":"muted-stone"} -->
				<p class="jn-expert-p has-muted-stone-color has-text-color">Triết lý đào tạo thực chiến của Jannie được đúc kết từ hàng ngàn ca điều trị lâm sàng, thấu hiểu tường tận cấu trúc cơ địa và làn da người Việt. Phương pháp kết hợp độc đáo giữa <strong>kỹ thuật điêu khắc tự nhiên</strong> và <strong>năng lượng an yên nội tại</strong> giúp mỗi học viên không chỉ sở hữu tay nghề xuất sắc mà còn lan tỏa sự tin cậy tuyệt đối tới khách hàng của mình.</p>
				<!-- /wp:paragraph -->

				<!-- wp:columns {"className":"jn-expert-credentials"} -->
				<div class="wp-block-columns jn-expert-credentials">
					<!-- wp:column -->
					<div class="wp-block-column">
						<!-- wp:group {"className":"jn-credential-item"} -->
						<div class="wp-block-group jn-credential-item">
							<!-- wp:jn/icon {"name":"award_star","size":20} /-->

							<!-- wp:group {"className":"jn-credential-item__body"} -->
							<div class="wp-block-group jn-credential-item__body">
								<!-- wp:heading {"level":3,"className":"jn-credential-title"} -->
								<h3 class="wp-block-heading jn-credential-title">Nghệ Nhân Bàn Tay Vàng</h3>
								<!-- /wp:heading -->

								<!-- wp:paragraph {"className":"jn-credential-desc","textColor":"muted-stone"} -->
								<p class="jn-credential-desc has-muted-stone-color has-text-color">Vinh danh tại Oscar for Beauty danh giá ngành thẩm mỹ Việt Nam.</p>
								<!-- /wp:paragraph -->
							</div>
							<!-- /wp:group -->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:column -->

					<!-- wp:column -->
					<div class="wp-block-column">
						<!-- wp:group {"className":"jn-credential-item"} -->
						<div class="wp-block-group jn-credential-item">
							<!-- wp:jn/icon {"name":"spa","size":20} /-->

							<!-- wp:group {"className":"jn-credential-item__body"} -->
							<div class="wp-block-group jn-credential-item__body">
								<!-- wp:heading {"level":3,"className":"jn-credential-title"} -->
								<h3 class="wp-block-heading jn-credential-title">Khái Niệm 'The Balance'</h3>
								<!-- /wp:heading -->

								<!-- wp:paragraph {"className":"jn-credential-desc","textColor":"muted-stone"} -->
								<p class="jn-credential-desc has-muted-stone-color has-text-color">Phương pháp cân bằng tự nhiên đưa tinh thần Việt ra quốc tế.</p>
								<!-- /wp:paragraph -->
							</div>
							<!-- /wp:group -->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:column -->
				</div>
				<!-- /wp:columns -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
