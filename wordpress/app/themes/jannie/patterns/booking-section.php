<?php
/**
 * Title: Booking Section (5:7 Intake & Registration Form)
 * Slug: jannie/booking-section
 * Categories: jannie-nguyen
 * Description: Phân đoạn Đăng ký Tư vấn & Xếp lịch Coaching (#booking-form) với danh sách quy trình 3 bước <ol> bên trái và block jn/booking-form bên phải.
 * Viewport Width: 1440
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"tagName":"section","metadata":{"name":"Section 6 — Booking Form"},"align":"full","anchor":"booking-form","backgroundColor":"palette-color-6","className":"jn-section jn-booking-section"} -->
<section id="booking-form" class="wp-block-group alignfull jn-section jn-booking-section has-palette-color-6-background-color has-background">
	<!-- wp:group {"className":"jn-container"} -->
	<div class="wp-block-group jn-container">
		<!-- wp:columns {"className":"jn-booking-shell"} -->
		<div class="wp-block-columns jn-booking-shell">
			<!-- wp:column {"width":"41.67%","className":"jn-booking-shell__left"} -->
			<div class="wp-block-column jn-booking-shell__left" style="flex-basis:41.67%">
				<!-- wp:group {"className":"jn-booking-left-top"} -->
				<div class="wp-block-group jn-booking-left-top">
					<!-- wp:paragraph {"className":"is-style-eyebrow jn-booking-eyebrow"} -->
					<p class="is-style-eyebrow jn-booking-eyebrow">Quy Trình Tiếp Nhận</p>
					<!-- /wp:paragraph -->

					<!-- wp:heading {"level":2,"className":"jn-booking-title"} -->
					<h2 class="wp-block-heading jn-booking-title">Đăng Ký Tư Vấn &amp; Xếp Lịch Coaching</h2>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"className":"jn-booking-desc"} -->
					<p class="jn-booking-desc">Để bảo đảm chất lượng sâu sát nhất, chuyên gia Jannie Nguyễn chỉ nhận số lượng học viên và khách hàng giới hạn mỗi tháng. Vui lòng cung cấp đầy đủ thông tin để được ưu tiên sắp xếp lịch trình.</p>
					<!-- /wp:paragraph -->

					<!-- wp:list {"ordered":true,"className":"jn-booking-steps"} -->
					<ol class="wp-block-list jn-booking-steps">
						<!-- wp:list-item -->
						<li><strong>Gửi yêu cầu thông tin</strong><br>Điền mẫu khảo sát nhu cầu cá nhân hoặc cơ sở làm đẹp.</li>
						<!-- /wp:list-item -->
						<!-- wp:list-item -->
						<li><strong>Xác nhận khung giờ</strong><br>Trợ lý học viện sẽ liên hệ trong 24 giờ làm việc để thống nhất lịch hẹn.</li>
						<!-- /wp:list-item -->
						<!-- wp:list-item -->
						<li><strong>Bước vào phiên làm việc 1:1</strong><br>Trực tiếp cùng Jannie Nguyễn qua nền tảng bảo mật hoặc tại viện.</li>
						<!-- /wp:list-item -->
					</ol>
					<!-- /wp:list -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"templateLock":"contentOnly","className":"jn-booking-hotline"} -->
				<div class="wp-block-group jn-booking-hotline">
					<!-- wp:paragraph {"className":"jn-booking-hotline__label"} -->
					<p class="jn-booking-hotline__label">Hotline Hỗ Trợ Khẩn</p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"jn-booking-hotline__value"} -->
					<p class="jn-booking-hotline__value">contact@jannienguyen.vn • <a href="tel:+84559912642">0559 912 642</a></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"width":"58.33%","className":"jn-booking-shell__right"} -->
			<div class="wp-block-column jn-booking-shell__right" style="flex-basis:58.33%">
				<!-- wp:jn/booking-form /-->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
