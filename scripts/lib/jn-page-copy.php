<?php
/**
 * Page intro lead copy shared by the seed (build-step-3.php), the migration (build-step-13-page-headers.php)
 * and its test, so the three can never drift apart.
 *
 * Copy restates only facts already in the repo (patterns/expert-intro.php, the hero metrics, the SEO
 * descriptions in mu-plugins/jn-performance/class-jn-seo-meta.php): no figures, dates, prices, awards,
 * testimonials or process promises the owner has not confirmed.
 *
 * @package JN_Scripts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'jn_page_intro_leads' ) ) {
	/**
	 * Intro lead for each page, keyed by page slug.
	 *
	 * @return array<string, string>
	 */
	function jn_page_intro_leads(): array {
		return array(
			'home'                 => 'Sứ mệnh của Jannie là tạo ra sự cân bằng và khám phá vẻ đẹp đích thực trong mỗi người. Hơn 20 năm thực chiến được đúc kết thành ba hướng đồng hành: cố vấn 1:1 cá nhân hóa, chương trình đào tạo thẩm mỹ và phun xăm, cùng những sản phẩm được Jannie tự tay tuyển chọn.',
			'my-story'             => 'Hơn 20 năm theo nghề, Nghệ nhân Bàn Tay Vàng Jannie Nguyễn theo đuổi phương pháp cân bằng tự nhiên The Balance: làm đẹp từ sự cân bằng giữa thể chất, tâm trí và cảm xúc.',
			'course'               => 'Hệ thống khóa học kỹ thuật điêu khắc, phun xăm tự nhiên và tư duy vận hành thẩm mỹ thực chiến, đúc kết từ hơn 20 năm kinh nghiệm lâm sàng và hàng ngàn ca thực tế trên làn da người Việt.',
			'client-success-story' => 'Những câu chuyện chuyển hóa thực tế từ học viên, chủ cơ sở thẩm mỹ và spa sau lộ trình cố vấn 1:1 cùng Jannie Nguyễn.',
		);
	}
}
