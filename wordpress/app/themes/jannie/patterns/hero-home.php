<?php
/**
 * Title: Hero Home (Arched Portrait)
 * Slug: jannie/hero-home
 * Categories: jannie-nguyen
 * Description: Hero trang chủ: tiêu đề H1 ba dòng, thanh 3 chỉ số, hai nút CTA và khung ảnh vòm có polaroid đổi ảnh (jn/hero-portrait).
 * Viewport Width: 1440
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$jn_hero_media = jannie_get_placeholder_media( 'hero_portrait', 'jn-hero-portrait.webp' );
$jn_legacy_map = get_option( 'jn_legacy_media_map', array() );
$jn_swap_id    = isset( $jn_legacy_map['banner_home']['id'] ) ? (int) $jn_legacy_map['banner_home']['id'] : 0;
?>
<!-- wp:group {"tagName":"section","metadata":{"name":"Section 1 — Hero Home"},"align":"full","className":"jn-section jn-hero-section jn-hero-home"} -->
<section class="wp-block-group alignfull jn-section jn-hero-section jn-hero-home">
	<!-- wp:group {"className":"jn-container jn-hero-home__grid"} -->
	<div class="wp-block-group jn-container jn-hero-home__grid">
		<!-- wp:group {"className":"jn-hero-home__copy"} -->
		<div class="wp-block-group jn-hero-home__copy">
			<!-- wp:paragraph {"className":"is-style-eyebrow-dot jn-hero-eyebrow"} -->
			<p class="is-style-eyebrow-dot jn-hero-eyebrow">Jannie Nguyen Academy &amp; Mentorship</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":1,"className":"jn-hero-title"} -->
			<h1 class="wp-block-heading jn-hero-title">Khám Phá Sự <br>Cân Bằng &amp; <em class="jn-accent-italic">Vẻ Đẹp Đích Thực</em></h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"jn-hero-lead","textColor":"muted-stone","fontSize":"body-lg"} -->
			<p class="jn-hero-lead has-muted-stone-color has-text-color has-body-lg-font-size">Sứ mệnh của Jannie là tạo ra sự cân bằng và khám phá vẻ đẹp đích thực trong mỗi người. Hơn 20 năm thực chiến được đúc kết thành ba hướng đồng hành: cố vấn 1:1 cá nhân hóa, chương trình đào tạo thẩm mỹ và phun xăm, cùng những sản phẩm được Jannie tự tay tuyển chọn.</p>
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
					<p class="jn-metric-label">Cố Vấn Cá Nhân</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"className":"jn-metric-item"} -->
				<div class="wp-block-group jn-metric-item">
					<!-- wp:paragraph {"className":"jn-metric-value"} -->
					<p class="jn-metric-value">03</p>
					<!-- /wp:paragraph -->
					<!-- wp:paragraph {"className":"jn-metric-label"} -->
					<p class="jn-metric-label">Hướng Đồng Hành</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->

			<!-- wp:buttons {"className":"jn-hero-actions"} -->
			<div class="wp-block-buttons jn-hero-actions">
				<!-- wp:button {"className":"is-style-primary-pill has-icon-north-east"} -->
				<div class="wp-block-button is-style-primary-pill has-icon-north-east"><a class="wp-block-button__link wp-element-button" href="#booking-form">Đặt Lịch Tư Vấn 1:1</a></div>
				<!-- /wp:button -->

				<!-- wp:button {"className":"is-style-soft-pill has-icon-north-east"} -->
				<div class="wp-block-button is-style-soft-pill has-icon-north-east"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/coaching/' ) ); ?>">Khám Phá Coaching</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->

			<!-- wp:paragraph {"className":"jn-hero-home__note"} -->
			<p class="jn-hero-home__note">Beauty · Balance · Mentorship</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:jn/hero-portrait {"primaryId":<?php echo (int) $jn_hero_media['id']; ?>,"secondaryId":<?php echo (int) $jn_swap_id; ?>} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
