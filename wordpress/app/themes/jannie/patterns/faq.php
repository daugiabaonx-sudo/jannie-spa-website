<?php
/**
 * Title: FAQ — Câu Hỏi Thường Gặp
 * Slug: jannie/faq
 * Categories: jannie-nguyen
 * Description: Khối câu hỏi thường gặp (core/details, không JS) — tự động sinh FAQPage JSON-LD. Mục còn chứa "[NHÁP]" sẽ không được đưa vào dữ liệu có cấu trúc; thay bằng nội dung đã được chủ dự án duyệt.
 * Viewport Width: 1440
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"className":"jn-faq","layout":{"type":"constrained","contentSize":"880px"}} -->
<div class="wp-block-group jn-faq">
	<!-- wp:paragraph {"align":"center","className":"is-style-eyebrow jn-eyebrow-wide"} -->
	<p class="has-text-align-center is-style-eyebrow jn-eyebrow-wide">Giải Đáp</p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"textAlign":"center","level":2,"className":"jn-section-title-lg"} -->
	<h2 class="wp-block-heading has-text-align-center jn-section-title-lg">Câu Hỏi Thường Gặp</h2>
	<!-- /wp:heading -->

	<!-- wp:details {"className":"jn-faq-item"} -->
	<details class="wp-block-details jn-faq-item"><summary>[NHÁP] Câu hỏi 1 — thay bằng câu hỏi khách thường hỏi?</summary>
		<!-- wp:paragraph -->
		<p>[NHÁP] Câu trả lời trực tiếp 40–60 từ: câu đầu trả lời thẳng, câu sau nêu điều kiện hoặc con số đã được xác nhận.</p>
		<!-- /wp:paragraph -->
	</details>
	<!-- /wp:details -->

	<!-- wp:details {"className":"jn-faq-item"} -->
	<details class="wp-block-details jn-faq-item"><summary>[NHÁP] Câu hỏi 2?</summary>
		<!-- wp:paragraph -->
		<p>[NHÁP] Câu trả lời đã được chủ dự án duyệt.</p>
		<!-- /wp:paragraph -->
	</details>
	<!-- /wp:details -->

	<!-- wp:details {"className":"jn-faq-item"} -->
	<details class="wp-block-details jn-faq-item"><summary>[NHÁP] Câu hỏi 3?</summary>
		<!-- wp:paragraph -->
		<p>[NHÁP] Câu trả lời đã được chủ dự án duyệt.</p>
		<!-- /wp:paragraph -->
	</details>
	<!-- /wp:details -->
</div>
<!-- /wp:group -->
