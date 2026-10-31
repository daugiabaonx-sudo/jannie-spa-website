<?php
/**
 * Consult dialog shared by every `jn/product-actions` block (ADR-025).
 *
 * The owner's mockup lets a visitor copy a ready-made message about a product and open Zalo to send it. The
 * Zalo chat link lives in one option (`jn_contact_zalo_url`, only `https://zalo.me/<id>` is accepted); while it
 * is empty the buttons read "Nhận tư vấn" and the dialog offers email instead, so the page never promises a
 * channel that does not exist (D7). The dialog is printed once, in the footer, by the first block that needs it.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Consult
 */
class JN_Consult {

	const OPTION = 'jn_contact_zalo_url';
	const EMAIL  = 'contact@jannienguyen.vn';

	/**
	 * Whether the footer hook has been added for this request.
	 *
	 * @var bool
	 */
	private static $requested = false;

	/**
	 * Whether the dialog has been printed for this request.
	 *
	 * @var bool
	 */
	private static $printed = false;

	/**
	 * Validated Zalo chat link, or '' when unset or not a zalo.me link.
	 *
	 * @return string
	 */
	public static function zalo_url(): string {
		$url = trim( (string) get_option( self::OPTION, '' ) );
		return (bool) preg_match( '#^https://zalo\.me/[0-9A-Za-z._-]+$#', $url ) ? $url : '';
	}

	/**
	 * Zalo phone number for display ("0559 912 642"), or '' when the link is not a phone number.
	 *
	 * @return string
	 */
	public static function zalo_phone(): string {
		$id = (string) substr( self::zalo_url(), strlen( 'https://zalo.me/' ) );
		if ( ! preg_match( '/^0\d{9}$/', $id ) ) {
			return '';
		}
		return substr( $id, 0, 4 ) . ' ' . substr( $id, 4, 3 ) . ' ' . substr( $id, 7 );
	}

	/**
	 * Label of the consult buttons: it names Zalo only when a Zalo link exists.
	 *
	 * @return string
	 */
	public static function consult_label(): string {
		return '' !== self::zalo_url() ? __( 'Tư vấn qua Zalo', 'jn-core' ) : __( 'Nhận tư vấn', 'jn-core' );
	}

	/**
	 * Ask for the dialog in this page's footer (once; never inside the editor's REST previews).
	 *
	 * @return void
	 */
	public static function request_dialog(): void {
		if ( self::$requested || wp_is_serving_rest_request() ) {
			return;
		}
		self::$requested = true;
		add_action( 'wp_footer', array( __CLASS__, 'print_dialog' ), 20 );
	}

	/**
	 * Print the dialog once per request.
	 *
	 * @return void
	 */
	public static function print_dialog(): void {
		if ( self::$printed ) {
			return;
		}
		self::$printed = true;
		echo self::dialog_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in dialog_html().
	}

	/**
	 * Dialog markup. Message templates travel as data attributes so `view.js` never builds HTML.
	 *
	 * @return string
	 */
	public static function dialog_html(): string {
		$zalo    = self::zalo_url();
		$phone   = self::zalo_phone();
		$subject = __( 'Tư vấn sản phẩm yêu thích của Jannie', 'jn-core' );
		$general = __( "Xin chào Jannie!\nTôi muốn tìm hiểu thêm về bộ sưu tập sản phẩm yêu thích của Jannie.\nMong được tư vấn. Cảm ơn!", 'jn-core' );
		$product = __( "Xin chào Jannie!\nTôi quan tâm đến sản phẩm:\n{name}\nGiá tham khảo: {price}\nMong được tư vấn thêm. Cảm ơn!", 'jn-core' );
		$mailto  = 'mailto:' . self::EMAIL . '?subject=' . rawurlencode( $subject ) . '&body=' . rawurlencode( $general );

		if ( '' !== $zalo ) {
			$subtitle = __( 'Sao chép lời nhắn bên dưới để gửi khi liên hệ với Jannie qua Zalo.', 'jn-core' );
			$channel  = sprintf(
				'<a class="jn-consult__btn jn-consult__btn--secondary" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s<span class="screen-reader-text"> %3$s</span></a>',
				esc_url( $zalo ),
				esc_html__( 'Mở Zalo', 'jn-core' ),
				esc_html__( '(mở trong thẻ mới)', 'jn-core' )
			);
			$note     = '' !== $phone
				/* translators: %s: Zalo phone number. */
				? sprintf( __( 'Nút Mở Zalo mở cuộc trò chuyện với Jannie trên Zalo (%s).', 'jn-core' ), $phone )
				: __( 'Nút Mở Zalo mở cuộc trò chuyện với Jannie trên Zalo.', 'jn-core' );
		} else {
			$subtitle = __( 'Sao chép lời nhắn bên dưới hoặc gửi thẳng qua email cho Jannie.', 'jn-core' );
			$channel  = sprintf(
				'<a class="jn-consult__btn jn-consult__btn--secondary" href="%1$s" data-jn-consult-mail="%2$s">%3$s</a>',
				esc_attr( $mailto ),
				esc_attr( self::EMAIL ),
				esc_html__( 'Gửi email', 'jn-core' )
			);
			/* translators: %s: email address. */
			$note = sprintf( __( 'Email của Jannie: %s.', 'jn-core' ), self::EMAIL );
		}

		// Material Symbols Outlined `close` (Apache-2.0), decorative.
		$close_icon = '<svg viewBox="0 -960 960 960" width="20" height="20" aria-hidden="true" focusable="false"><path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z"/></svg>';

		return sprintf(
			'<dialog class="jn-consult" id="jn-consult" aria-labelledby="jn-consult-title" data-jn-msg-general="%1$s" data-jn-msg-product="%2$s" data-jn-mail-subject="%3$s" data-jn-copied="%4$s" data-jn-copy-failed="%5$s">'
				. '<div class="jn-consult__inner">'
				. '<div class="jn-consult__head"><h2 class="jn-consult__title" id="jn-consult-title">%6$s</h2><button type="button" class="jn-consult__close" data-jn-consult-close aria-label="%7$s">%8$s</button></div>'
				. '<p class="jn-consult__subtitle">%9$s</p>'
				. '<label class="screen-reader-text" for="jn-consult-text">%10$s</label>'
				. '<textarea class="jn-consult__text" id="jn-consult-text" rows="6" readonly>%11$s</textarea>'
				. '<div class="jn-consult__actions"><button type="button" class="jn-consult__btn jn-consult__btn--primary" data-jn-consult-copy>%12$s</button>%13$s</div>'
				. '<p class="jn-consult__status" role="status"></p>'
				. '<p class="jn-consult__note">%14$s</p>'
				. '</div></dialog>',
			esc_attr( $general ),
			esc_attr( $product ),
			esc_attr( $subject ),
			esc_attr__( 'Đã sao chép lời nhắn tư vấn.', 'jn-core' ),
			esc_attr__( 'Không thể sao chép tự động trên trình duyệt này — hãy chọn và sao chép lời nhắn.', 'jn-core' ),
			esc_html__( 'Kết nối với Jannie', 'jn-core' ),
			esc_attr__( 'Đóng hộp thoại tư vấn', 'jn-core' ),
			$close_icon,
			esc_html( $subtitle ),
			esc_html__( 'Nội dung tin nhắn tư vấn', 'jn-core' ),
			esc_textarea( $general ),
			esc_html__( 'Sao chép lời nhắn', 'jn-core' ),
			$channel,
			esc_html( $note )
		);
	}
}
