<?php
/**
 * JN SEO — `/llms.txt` for AI answer engines (llmstxt.org format, audit finding F7).
 *
 * Built only from data that already exists on the site (brand facts, page meta map, coaching
 * packages) so it can never drift from the pages or invent claims.
 *
 * @package JN_Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * llms.txt builder and route.
 */
class JN_SEO_Llms {

	/**
	 * Main pages (slug => listed in "Trang chính"); legal pages go to "Chính sách".
	 *
	 * @var array<int, string>
	 */
	const MAIN_SLUGS = array( 'front', 'coaching', 'booking', 'my-story', 'course', 'client-success-story', 'my-favorite-product' );

	/**
	 * Legal page slugs.
	 *
	 * @var array<int, string>
	 */
	const POLICY_SLUGS = array( 'chinh-sach-bao-mat', 'dieu-khoan-dich-vu' );

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'serve' ), 1 );
	}

	/**
	 * Build the llms.txt Markdown body.
	 *
	 * @return string
	 */
	public static function build_llms_txt(): string {
		$brand = JN_SEO_Brand::get();
		$map   = JN_SEO_Meta::get_page_map();

		$lines = array(
			'# ' . $brand['name'],
			'',
			'> ' . $brand['person_description'] . ' ' . $map['front']['description'],
			'',
			'- Tổ chức: ' . $brand['org_name'],
			'- Chức danh: ' . $brand['job_title'],
			'- Khu vực: ' . $brand['locality'] . ', Việt Nam',
			'- Liên hệ: ' . $brand['email'],
			'- Ngôn ngữ nội dung: Tiếng Việt',
			'- Đặt lịch: gửi yêu cầu trên website; trợ lý xác nhận khung giờ, thanh toán sau khi lịch được xác nhận.',
			'- Sơ đồ trang (Sitemap): ' . home_url( '/sitemap.xml' ),
			'',
			'## Trang chính',
			'',
		);
		foreach ( self::MAIN_SLUGS as $slug ) {
			if ( self::is_published( $slug ) ) {
				$lines[] = self::link_line( $slug, $map[ $slug ] );
			}
		}

		$lines[] = '';
		$lines[] = '## Chương trình cố vấn 1:1';
		$lines[] = '';
		foreach ( JN_SEO_Brand::get_packages() as $pkg ) {
			$lines[] = sprintf( '- [%s](%s): %s', $pkg['name'], home_url( '/coaching/' ), $pkg['description'] );
		}

		$coaching_page = get_page_by_path( 'coaching' );
		$faq_entries   = ( $coaching_page instanceof WP_Post && 'publish' === $coaching_page->post_status )
			? JN_SEO_Schema::extract_faq_from_blocks( (string) $coaching_page->post_content )
			: array();
		if ( ! empty( $faq_entries ) ) {
			$lines[] = '';
			$lines[] = '## Câu hỏi thường gặp';
			$lines[] = '';
			foreach ( $faq_entries as $entry ) {
				$lines[] = sprintf( '- **%s** %s', $entry['question'], $entry['answer'] );
			}
		}

		$lines[] = '';
		$lines[] = '## Chính sách';
		$lines[] = '';
		foreach ( self::POLICY_SLUGS as $slug ) {
			if ( self::is_published( $slug ) ) {
				$lines[] = self::link_line( $slug, $map[ $slug ] );
			}
		}

		return implode( "\n", $lines ) . "\n";
	}

	/**
	 * Whether the page behind a map slug is published (`front` = static front page).
	 *
	 * @param string $slug Page slug.
	 * @return bool
	 */
	private static function is_published( string $slug ): bool {
		$page = 'front' === $slug ? get_post( (int) get_option( 'page_on_front' ) ) : get_page_by_path( $slug );
		return $page instanceof WP_Post && 'publish' === $page->post_status;
	}

	/**
	 * Format one Markdown link line.
	 *
	 * @param string                                   $slug Page slug (`front` = home).
	 * @param array{title: string, description: string} $meta Page meta.
	 * @return string
	 */
	private static function link_line( string $slug, array $meta ): string {
		$url = 'front' === $slug ? home_url( '/' ) : home_url( '/' . $slug . '/' );
		return sprintf( '- [%s](%s): %s', $meta['title'], $url, $meta['description'] );
	}

	/**
	 * Serve `/llms.txt` before WordPress routing.
	 *
	 * @return void
	 */
	public static function serve(): void {
		if ( is_admin() || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$req_path = trim( (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ), '/' );
		if ( 'llms.txt' !== $req_path ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex' );
		echo self::build_llms_txt(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text Markdown from internal constants.
		exit;
	}
}
