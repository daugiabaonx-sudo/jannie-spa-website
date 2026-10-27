<?php
/**
 * Title: Section Header (Split & Centered Variants)
 * Slug: jannie/section-header
 * Categories: jannie-nguyen
 * Description: Đầu mục phân đoạn gồm cả hai biến thể Split (trái/phải căn đáy) và Centered (căn giữa).
 * Viewport Width: 1440
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"className":"jn-section-header-split"} -->
<div class="wp-block-group jn-section-header-split">
	<!-- wp:group {"className":"jn-section-header-split__left"} -->
	<div class="wp-block-group jn-section-header-split__left">
		<!-- wp:paragraph {"className":"is-style-eyebrow"} -->
		<p class="is-style-eyebrow">Giá Trị Cốt Lõi</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":2,"className":"jn-section-title-md"} -->
		<h2 class="wp-block-heading jn-section-title-md">Lợi Ích Của Chương Trình Coaching</h2>
		<!-- /wp:heading -->
	</div>
	<!-- /wp:group -->

	<!-- wp:paragraph {"className":"jn-section-header-split__desc","textColor":"muted-stone"} -->
	<p class="jn-section-header-split__desc has-muted-stone-color has-text-color">Không đơn thuần là chuyển giao kỹ năng cơ học, chương trình tái định nghĩa toàn bộ mô hình tư duy, kỹ thuật và vận hành thương hiệu.</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"jn-section-header-centered"} -->
<div class="wp-block-group jn-section-header-centered">
	<!-- wp:paragraph {"align":"center","className":"is-style-eyebrow jn-eyebrow-wide"} -->
	<p class="has-text-align-center is-style-eyebrow jn-eyebrow-wide">Chương Trình Đào Tạo</p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"textAlign":"center","level":2,"className":"jn-section-title-lg"} -->
	<h2 class="wp-block-heading has-text-align-center jn-section-title-lg">Chi Tiết Gói Đào Tạo &amp; Cố Vấn</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","className":"jn-section-header-centered__desc","textColor":"muted-stone"} -->
	<p class="has-text-align-center jn-section-header-centered__desc has-muted-stone-color has-text-color">Lựa chọn hình thức đồng hành phù hợp với nhu cầu hoàn thiện vẻ đẹp bản thân hoặc bứt phá sự nghiệp làm đẹp của bạn.</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
