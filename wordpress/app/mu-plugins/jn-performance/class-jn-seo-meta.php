<?php
/**
 * JN SEO — Titles, meta descriptions, title branding and fallback head tags.
 *
 * Titles/descriptions are verbatim Vietnamese wireframe copy (Step 8 Task 9). Routes that are not in
 * the map get a contextual title (search, product, taxonomy) or fall through to The SEO Framework's
 * own generation — never the homepage copy (audit finding F5).
 *
 * @package JN_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta title / description provider.
 */
class JN_SEO_Meta {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'the_seo_framework_indicator', '__return_false', 999 );
		add_filter( 'the_seo_framework_indicator_sitemap', '__return_false', 999 );
		// Brand is already part of every mapped title; TSF would append " - JANNIE NGUYEN" (F9).
		add_filter( 'the_seo_framework_use_title_branding', '__return_false', 999 );

		add_filter( 'pre_get_document_title', array( __CLASS__, 'filter_document_title' ), 99 );
		// Generated values are replaced by the map; owner-entered TSF custom fields always win.
		add_filter( 'the_seo_framework_title_from_generation', array( __CLASS__, 'filter_tsf_title' ), 99, 2 );
		add_filter( 'the_seo_framework_generated_description', array( __CLASS__, 'filter_tsf_description' ), 99, 2 );
		// Fallback image only when TSF found no featured/content image (real images are kept).
		add_filter( 'the_seo_framework_generated_image_details', array( __CLASS__, 'filter_tsf_image_details' ), 99 );

		add_action( 'wp_head', array( __CLASS__, 'print_fallback_head' ), 2 );
	}

	/**
	 * Return the per-page title/description map keyed by page slug (`front` = static front page).
	 *
	 * @return array<string, array{title: string, description: string}>
	 */
	public static function get_page_map(): array {
		return array(
			'front'                => array(
				'title'       => 'Jannie Nguyễn — Cố Vấn 1:1 & Đào Tạo Thẩm Mỹ Chuyên Sâu',
				'description' => 'Chương trình cố vấn 1:1 độc quyền cùng Chuyên gia Jannie Nguyễn dành cho cá nhân định hình vẻ đẹp tự nhiên và các chủ cơ sở thẩm mỹ, phun xăm, spa cao cấp.',
			),
			'coaching'             => array(
				'title'       => 'Coaching & Đồng Hành Chuyên Sâu 1:1 | Jannie Nguyễn',
				'description' => 'Chương trình cố vấn 1:1 độc quyền cùng Chuyên gia Jannie Nguyễn: Tư Vấn Định Hướng Cá Nhân, Khai Vấn Chiến Lược Kinh Doanh và Master Mentorship (3 Tháng).',
			),
			'my-story'             => array(
				'title'       => 'Hành Trình Hơn 20 Năm Của Chuyên Gia Jannie Nguyễn',
				'description' => 'Hành trình hơn 20 năm đồng hành cùng vẻ đẹp đích thực của Nghệ nhân Bàn Tay Vàng Jannie Nguyễn và phương pháp cân bằng tự nhiên The Balance.',
			),
			'course'               => array(
				'title'       => 'Chương Trình Đào Tạo & Khóa Học Chuyên Sâu | Jannie Nguyễn',
				'description' => 'Hệ thống khóa học kỹ thuật điêu khắc, phun xăm tự nhiên và tư duy vận hành thẩm mỹ thực chiến được đúc kết từ hơn 20 năm kinh nghiệm lâm sàng của Jannie Nguyễn.',
			),
			'client-success-story' => array(
				'title'       => 'Cảm Nhận Học Viên & Kết Quả Thực Tế | Jannie Nguyễn',
				'description' => 'Minh chứng thành công và câu chuyện chuyển hóa thực tế từ các học viên, chủ cơ sở thẩm mỹ và spa sau lộ trình cố vấn 1:1 cùng Chuyên gia Jannie Nguyễn.',
			),
			'booking'              => array(
				'title'       => 'Đặt Lịch Tư Vấn 1:1 Cùng Chuyên Gia | Jannie Nguyễn',
				'description' => 'Để lại thông tin để đội ngũ trợ lý của Jannie Nguyễn liên hệ xác nhận lịch hẹn phù hợp và chuẩn bị tài liệu phân tích trước buổi gặp.',
			),
			'my-favorite-product'  => array(
				'title'       => 'Sản Phẩm Yêu Thích & Khuyên Dùng | Jannie Nguyễn',
				'description' => 'Danh mục sản phẩm chăm sóc, dụng cụ và giải pháp làm đẹp được Chuyên gia Jannie Nguyễn trực tiếp tuyển chọn và kiểm chứng qua thực tế lâm sàng.',
			),
			'cart'                 => array(
				'title'       => 'Giỏ Hàng Của Bạn | Jannie Nguyễn',
				'description' => 'Kiểm tra danh sách khóa học và sản phẩm bạn đã chọn tại Học viện Jannie Nguyễn trước khi tiến hành thanh toán.',
			),
			'checkout'             => array(
				'title'       => 'Thanh Toán Đơn Hàng | Jannie Nguyễn',
				'description' => 'Hoàn tất thông tin thanh toán đơn hàng khóa học và sản phẩm tại Học viện Jannie Nguyễn.',
			),
			'tai-khoan'            => array(
				'title'       => 'Tài Khoản Học Viên & Khách Hàng | Jannie Nguyễn',
				'description' => 'Đăng nhập hoặc đăng ký tài khoản thành viên tại Học viện Jannie Nguyễn để quản lý đơn hàng và lộ trình học tập của bạn.',
			),
			'chinh-sach-bao-mat'   => array(
				'title'       => 'Chính Sách Bảo Mật Thông Tin | Jannie Nguyễn',
				'description' => 'Cam kết bảo mật thông tin cá nhân và dữ liệu khách hàng khi đăng ký tư vấn 1:1 và sử dụng dịch vụ tại Học viện Jannie Nguyễn.',
			),
			'dieu-khoan-dich-vu'   => array(
				'title'       => 'Điều Khoản Dịch Vụ & Chính Sách Cố Vấn | Jannie Nguyễn',
				'description' => 'Quy định về đặt lịch tư vấn 1:1, chính sách thanh toán, đổi lịch hẹn và quyền lợi học viên tại Học viện Jannie Nguyễn.',
			),
		);
	}

	/**
	 * Return the title/description for a search results page.
	 *
	 * @param string $query Search terms.
	 * @return array{title: string, description: string}
	 */
	public static function get_search_meta( string $query ): array {
		$query = wp_strip_all_tags( $query );
		return array(
			'title'       => sprintf( 'Kết quả tìm kiếm “%s” | Jannie Nguyễn', $query ),
			'description' => sprintf( 'Kết quả tìm kiếm cho “%s” trên website của Chuyên gia Jannie Nguyễn.', $query ),
		);
	}

	/**
	 * Return the meta for the current request; empty strings mean "let the SEO plugin generate".
	 *
	 * @return array{title: string, description: string}
	 */
	public static function get_current(): array {
		$map = self::get_page_map();

		if ( is_front_page() ) {
			return $map['front'];
		}
		if ( is_search() ) {
			return self::get_search_meta( get_search_query( false ) );
		}
		if ( is_singular( 'product' ) ) {
			return self::get_product_meta( get_queried_object() );
		}

		$queried = get_queried_object();
		if ( $queried instanceof WP_Post && isset( $map[ $queried->post_name ] ) ) {
			return $map[ $queried->post_name ];
		}
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return $map['cart'];
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return $map['checkout'];
		}
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return $map['tai-khoan'];
		}
		if ( $queried instanceof WP_Term ) {
			return array(
				'title'       => $queried->name . ' | Jannie Nguyễn',
				'description' => '',
			);
		}

		return array(
			'title'       => '',
			'description' => '',
		);
	}

	/**
	 * Build the meta for a single product.
	 *
	 * @param mixed $post Queried object.
	 * @return array{title: string, description: string}
	 */
	private static function get_product_meta( $post ): array {
		if ( ! $post instanceof WP_Post ) {
			return array(
				'title'       => '',
				'description' => '',
			);
		}
		$raw  = has_excerpt( $post ) ? $post->post_excerpt : excerpt_remove_blocks( strip_shortcodes( $post->post_content ) );
		$desc = wp_trim_words( wp_strip_all_tags( $raw ), 32, '…' );
		return array(
			'title'       => get_the_title( $post ) . ' | Jannie Nguyễn',
			'description' => '' !== $desc ? $desc : 'Ấn phẩm và giải pháp làm đẹp chuyên sâu được tuyển chọn bởi Chuyên gia Jannie Nguyễn tại Học viện Jannie Nguyễn.',
		);
	}

	/**
	 * Filter the core document title (used when The SEO Framework is inactive).
	 *
	 * `pre_get_document_title` bypasses core escaping, so the value is escaped here.
	 *
	 * @param string $title Title.
	 * @return string
	 */
	public static function filter_document_title( $title ) {
		if ( is_admin() || is_404() ) {
			return $title;
		}
		$meta = self::get_current();
		return '' !== $meta['title'] ? esc_html( $meta['title'] ) : $title;
	}

	/**
	 * Filter TSF generated titles for the auto-determined (current) query only (TSF escapes output).
	 *
	 * @param string     $title Title.
	 * @param array|null $args  TSF query args; non-null means another object is being described.
	 * @return string
	 */
	public static function filter_tsf_title( $title, $args = null ) {
		if ( null !== $args || is_admin() || is_404() ) {
			return $title;
		}
		$meta = self::get_current();
		return '' !== $meta['title'] ? $meta['title'] : $title;
	}

	/**
	 * Filter TSF descriptions for the auto-determined (current) query only.
	 *
	 * @param string     $description Description.
	 * @param array|null $args        TSF query args.
	 * @return string
	 */
	public static function filter_tsf_description( $description, $args = null ) {
		if ( null !== $args || is_admin() || is_404() ) {
			return $description;
		}
		$meta = self::get_current();
		return '' !== $meta['description'] ? $meta['description'] : $description;
	}

	/**
	 * Return the default Open Graph image (owner-replaceable via option `jn_step8_og_image`).
	 *
	 * @return array{url: string, width: int, height: int}
	 */
	public static function get_default_og_image(): array {
		$og_meta = get_option( 'jn_step8_og_image', array() );
		if ( is_array( $og_meta ) && ! empty( $og_meta['url'] ) ) {
			return array(
				'url'    => (string) $og_meta['url'],
				'width'  => isset( $og_meta['width'] ) ? (int) $og_meta['width'] : 1200,
				'height' => isset( $og_meta['height'] ) ? (int) $og_meta['height'] : 630,
			);
		}
		$media_map = get_option( 'jn_step3_media_map', array() );
		if ( isset( $media_map['hero_portrait']['url'] ) ) {
			return array(
				'url'    => (string) $media_map['hero_portrait']['url'],
				'width'  => 800,
				'height' => 1000,
			);
		}
		return array(
			'url'    => home_url( '/media/2026/10/jn-placeholder-hero-portrait.webp' ),
			'width'  => 800,
			'height' => 1000,
		);
	}

	/**
	 * Supply the default OG image to TSF when no per-post image exists.
	 *
	 * @param array<int, array<string, mixed>> $details Image details.
	 * @return array<int, array<string, mixed>>
	 */
	public static function filter_tsf_image_details( $details ) {
		if ( ! empty( $details ) ) {
			return $details;
		}
		$og = self::get_default_og_image();
		return array(
			array(
				'url'    => $og['url'],
				'width'  => $og['width'],
				'height' => $og['height'],
				'alt'    => self::get_page_map()['front']['title'],
			),
		);
	}

	/**
	 * Return the canonical URL of the current request.
	 *
	 * @return string
	 */
	public static function get_canonical_url(): string {
		if ( is_front_page() ) {
			return home_url( '/' );
		}
		if ( is_search() ) {
			return get_search_link();
		}
		$queried = get_queried_object();
		if ( $queried instanceof WP_Post ) {
			$permalink = get_permalink( $queried );
			if ( is_string( $permalink ) && '' !== $permalink ) {
				return $permalink;
			}
		}
		$req_path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '/';
		return home_url( trailingslashit( '/' . ltrim( $req_path, '/' ) ) );
	}

	/**
	 * Print description/canonical/OG tags only when The SEO Framework is not active (F10: no duplicates).
	 *
	 * @return void
	 */
	public static function print_fallback_head(): void {
		if ( is_admin() || is_404() ) {
			return;
		}
		$tsf_active = function_exists( 'the_seo_framework' ) && the_seo_framework();
		if ( $tsf_active && is_search() ) {
			// TSF emits no description on search results; add ours (page stays noindex).
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( self::get_current()['description'] ) );
			return;
		}
		if ( $tsf_active ) {
			return;
		}
		$meta      = self::get_current();
		$canonical = self::get_canonical_url();
		$og        = self::get_default_og_image();
		$title     = '' !== $meta['title'] ? $meta['title'] : wp_get_document_title();

		remove_action( 'wp_head', 'rel_canonical' );
		if ( '' !== $meta['description'] ) {
			printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $meta['description'] ) );
			printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $meta['description'] ) );
		}
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );
		echo '<meta property="og:locale" content="vi_VN" />' . "\n";
		echo '<meta property="og:type" content="website" />' . "\n";
		printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );
		printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $canonical ) );
		echo '<meta property="og:site_name" content="Jannie Nguyễn" />' . "\n";
		printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $og['url'] ) );
		printf( '<meta property="og:image:width" content="%d" />' . "\n", (int) $og['width'] );
		printf( '<meta property="og:image:height" content="%d" />' . "\n", (int) $og['height'] );
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	}
}
