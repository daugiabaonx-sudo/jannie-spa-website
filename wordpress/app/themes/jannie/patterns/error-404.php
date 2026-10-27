<?php
/**
 * Title: Trang Lỗi 404 (Editorial 404)
 * Slug: jannie/error-404
 * Categories: jannie-nguyen
 * Description: Trang thông báo 404 theo ngôn ngữ thiết kế Warm Ivory & Editorial Serif kèm lối tắt về Trang chủ và Đặt lịch Coaching.
 * Viewport Width: 1440
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$home_url    = esc_url( home_url( '/' ) );
$booking_url = esc_url( home_url( '/coaching/#booking-form' ) );
?>
<!-- wp:group {"tagName":"section","align":"full","backgroundColor":"palette-color-7","className":"jn-section jn-page-intro-section jn-404-section"} -->
<section class="wp-block-group alignfull jn-section jn-page-intro-section jn-404-section has-palette-color-7-background-color has-background">
	<!-- wp:group {"className":"jn-container jn-page-intro-inner jn-404-inner"} -->
	<div class="wp-block-group jn-container jn-page-intro-inner jn-404-inner">
		<!-- wp:paragraph {"className":"is-style-eyebrow-dot"} -->
		<p class="is-style-eyebrow-dot">404 // Không Tìm Thấy Trang</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1,"className":"jn-page-intro-title"} -->
		<h1 class="wp-block-heading jn-page-intro-title">Trang Bạn Tìm Kiếm <em class="jn-accent-italic">Không Tồn Tại</em> Hoặc Đã Chuyển Đi</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"jn-page-intro-lead","textColor":"muted-stone","fontSize":"body-lg"} -->
		<p class="jn-page-intro-lead has-muted-stone-color has-text-color has-body-lg-font-size">Đường dẫn bạn vừa truy cập không còn hiệu lực hoặc đã được cập nhật. Bạn có thể quay về Trang chủ hoặc đặt lịch cố vấn 1:1 trực tiếp cùng chuyên gia Jannie Nguyễn.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"className":"jn-hero-cta-row"} -->
		<div class="wp-block-buttons jn-hero-cta-row">
			<!-- wp:button {"className":"is-style-primary-pill"} -->
			<div class="wp-block-button is-style-primary-pill"><a class="wp-block-button__link wp-element-button" href="<?php echo $home_url; ?>"><span>Quay Về Trang Chủ</span><!-- wp:jn/icon {"name":"arrow_forward","size":16} /--></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-soft-pill"} -->
			<div class="wp-block-button is-style-soft-pill"><a class="wp-block-button__link wp-element-button" href="<?php echo $booking_url; ?>"><span>Đặt Lịch Coaching 1:1</span><!-- wp:jn/icon {"name":"north_east","size":16} /--></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
