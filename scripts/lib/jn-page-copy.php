<?php
/**
 * Page intro lead copy shared by the seed (build-step-3.php), the migration (build-step-13-page-headers.php)
 * and its test, so the three can never drift apart.
 *
 * Copy restates only facts already in the repo (patterns/expert-intro.php, the hero metrics, the SEO
 * descriptions in mu-plugins/jn-performance/class-jn-seo-meta.php): no figures, dates, prices, awards,
 * testimonials or process promises the owner has not confirmed. Since Step 15 (ADR-022) /course/,
 * /client-success-story/ (Step 15) and /my-story/ (Step 16) use the old jannieedu.com lead from lib/jn-legacy-content.json instead.
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/jn-legacy-blocks.php';

if ( ! function_exists( 'jn_page_intro_leads' ) ) {
	/**
	 * Intro lead for each page, keyed by page slug.
	 *
	 * @return array<string, string>
	 */
	function jn_page_intro_leads(): array {
		return array(
			'home'                 => 'Sứ mệnh của Jannie là tạo ra sự cân bằng và khám phá vẻ đẹp đích thực trong mỗi người. Hơn 20 năm thực chiến được đúc kết thành ba hướng đồng hành: cố vấn 1:1 cá nhân hóa, chương trình đào tạo thẩm mỹ và phun xăm, cùng những sản phẩm được Jannie tự tay tuyển chọn.',
			'my-story'             => jn_lb_content()['my_story']['page']['lead'],
			'course'               => jn_lb_content()['courses']['page']['lead'],
			'client-success-story' => jn_lb_content()['clients']['page']['lead'],
		);
	}
}
