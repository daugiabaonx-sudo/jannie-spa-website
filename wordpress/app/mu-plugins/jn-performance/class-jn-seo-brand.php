<?php
/**
 * JN SEO — Brand entity facts (single source of truth for JSON-LD, llms.txt and meta).
 *
 * Every value here must trace back to the Project Context Card in `.agent/rules/jn-project.md`
 * or to verbatim wireframe copy (`docs/jn/wireframe-spec.md`). Values the owner has not provided
 * (telephone, street address, social profiles, prices) stay empty and are read from options that
 * the owner can set later, e.g.:
 *   wp option update jn_brand_telephone "+84 …"
 *   wp option update jn_brand_same_as '["https://www.facebook.com/…"]' --format=json
 *
 * @package JN_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Brand / entity data provider.
 */
class JN_SEO_Brand {

	/**
	 * Return the verified brand facts.
	 *
	 * @return array{
	 *     name: string, org_name: string, email: string, telephone: string, locality: string,
	 *     country: string, job_title: string, person_description: string, same_as: array<int, string>,
	 *     logo: string
	 * }
	 */
	public static function get(): array {
		$same_as = get_option( 'jn_brand_same_as', array() );
		$same_as = is_array( $same_as ) ? array_values( array_filter( array_map( 'esc_url_raw', $same_as ) ) ) : array();

		return array(
			'name'               => 'Jannie Nguyễn',
			'org_name'           => 'Jannie Nguyễn Academy',
			// Every other spelling the pages use (footer/page-intro, meta descriptions) + the site tagline (Step 14, F14-02).
			'org_alternate_names' => array( 'Jannie Nguyen Academy', 'Học viện Jannie Nguyễn', 'Jannie Nguyễn — Beauty Blog & Mentorship' ),
			'email'              => 'contact@jannienguyen.vn',
			'telephone'          => sanitize_text_field( (string) get_option( 'jn_brand_telephone', '' ) ),
			'locality'           => 'TP. Hồ Chí Minh',
			'country'            => 'VN',
			'job_title'          => 'Chuyên gia Cố vấn & Nghệ nhân Bàn Tay Vàng ngành Phun xăm Thẩm mỹ',
			'person_description' => 'Chuyên gia cố vấn thẩm mỹ hơn 20 năm kinh nghiệm thực chiến, sáng lập phương pháp cân bằng tự nhiên The Balance.',
			'same_as'            => $same_as,
			'logo'               => (string) get_site_icon_url( 512 ),
		);
	}

	/**
	 * Return the 3 coaching packages (names/descriptions verbatim from the wireframe).
	 *
	 * @return array<string, array{name: string, description: string, url: string}>
	 */
	public static function get_packages(): array {
		return array(
			'30-phut'            => array(
				'name'        => 'Tư Vấn Định Hướng Cá Nhân (1:1 — 30 Phút)',
				'description' => 'Giải pháp gỡ rối tức thì cho cá nhân muốn định hình phong cách cá nhân hoặc giải đáp thắc mắc kỹ thuật chuyên biệt cùng Chuyên gia Jannie Nguyễn.',
				'url'         => home_url( '/coaching/?goi=30-phut#booking-form' ),
			),
			'60-phut'            => array(
				'name'        => 'Khai Vấn Chiến Lược Kinh Doanh (1:1 — 60 Phút)',
				'description' => 'Phiên làm việc chuyên sâu dành cho chủ Spa, Viện thẩm mỹ muốn tối ưu hóa quy trình vận hành, nâng cấp dịch vụ mũi nhọn và bứt phá doanh thu.',
				'url'         => home_url( '/coaching/?goi=60-phut#booking-form' ),
			),
			'mentorship-3-thang' => array(
				'name'        => 'Master Mentorship (Đồng Hành 3 Tháng)',
				'description' => 'Chương trình cố vấn toàn diện nhất: Jannie Nguyễn trực tiếp đồng hành thiết lập lại toàn bộ hệ thống từ kỹ thuật lõi đến chiến lược thương hiệu dài hạn.',
				'url'         => home_url( '/coaching/?goi=mentorship-3-thang#booking-form' ),
			),
		);
	}

	/**
	 * Return the published positive price of a coaching package, or null when not public yet.
	 *
	 * @param string $package_key Package key from get_packages().
	 * @return string|null
	 */
	public static function get_package_price( string $package_key ): ?string {
		static $products = null;
		if ( ! class_exists( 'JN_Coaching_Packages' ) || ! function_exists( 'wc_get_product' ) ) {
			return null;
		}
		if ( null === $products ) {
			$products = JN_Coaching_Packages::get_bookable_packages_for_form();
		}
		if ( empty( $products[ $package_key ]['product_id'] ) ) {
			return null;
		}
		$product = wc_get_product( (int) $products[ $package_key ]['product_id'] );
		// Draft/private packages (UNK-01: prices not public yet) never leak a price into JSON-LD.
		if ( ! $product || 'publish' !== $product->get_status() || ! is_numeric( $product->get_price() ) || (float) $product->get_price() <= 0 ) {
			return null;
		}
		return (string) $product->get_price();
	}
}
