<?php
/**
 * Title: My Favorite Product — Hero & Marquee
 * Slug: jannie/favorite-hero
 * Categories: jannie-nguyen
 * Description: Hero của trang Sản phẩm yêu thích (bản nâng cấp 2026-10-31): cột chữ theo hệ tiêu đề chung, minh họa ba chai (jn/product-stage) và dải chữ chạy có nút tạm dừng (jn/marquee).
 * Viewport Width: 1440
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"tagName":"section","metadata":{"name":"My Favorite Product — Hero"},"align":"full","className":"jn-section jn-hero-section jn-fav-hero"} -->
<section class="wp-block-group alignfull jn-section jn-hero-section jn-fav-hero">
	<!-- wp:group {"className":"jn-container jn-fav-hero__grid"} -->
	<div class="wp-block-group jn-container jn-fav-hero__grid">
		<!-- wp:group {"className":"jn-fav-hero__copy"} -->
		<div class="wp-block-group jn-fav-hero__copy">
			<!-- wp:paragraph {"className":"is-style-eyebrow-dot jn-hero-eyebrow"} -->
			<p class="is-style-eyebrow-dot jn-hero-eyebrow"><bdo lang="en" dir="ltr">The Jannie Edit</bdo> · 2026</p>
			<!-- /wp:paragraph -->
			<!-- wp:heading {"level":1,"className":"jn-hero-title"} -->
			<h1 class="wp-block-heading jn-hero-title">Vẻ đẹp, <em class="jn-accent-italic">rất riêng.</em></h1>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"className":"jn-hero-lead","textColor":"muted-stone","fontSize":"body-lg"} -->
			<p class="jn-hero-lead has-muted-stone-color has-text-color has-body-lg-font-size">Không cần quá nhiều. Chỉ cần những điều thực sự xứng đáng. Khám phá 6 sản phẩm được Jannie yêu thích và tuyển chọn.</p>
			<!-- /wp:paragraph -->
			<!-- wp:buttons {"className":"jn-hero-actions"} -->
			<div class="wp-block-buttons jn-hero-actions">
				<!-- wp:button {"className":"is-style-primary-pill has-icon-south"} -->
				<div class="wp-block-button is-style-primary-pill has-icon-south"><a class="wp-block-button__link wp-element-button" href="#bo-suu-tap">Khám Phá Bộ Sưu Tập</a></div>
				<!-- /wp:button -->
				<!-- wp:button {"className":"is-style-text-link has-icon-north-east"} -->
				<div class="wp-block-button is-style-text-link has-icon-north-east"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/my-story/' ) ); ?>">Câu Chuyện Của Jannie</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
			<!-- wp:paragraph {"className":"jn-fav-hero__meta"} -->
			<p class="jn-fav-hero__meta">01 — 03 <bdo lang="en" dir="ltr">The Curated Collection</bdo></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
		<!-- wp:jn/product-stage /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:jn/marquee {"align":"full"} /-->
