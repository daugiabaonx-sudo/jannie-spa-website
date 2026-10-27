<?php
/**
 * Custom WooCommerce `WC_Email` Subclasses for the Jannie Nguyen Booking Engine.
 *
 * Loaded by `JN_Booking_Emails::register_email_classes()` once `WC_Email` is available.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WC_Email' ) ) {
	exit;
}

if ( class_exists( 'WC_Email_JN_Booking_Base', false ) ) {
	return;
}

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

/**
 * Base class for JN Booking WooCommerce Emails.
 */
abstract class WC_Email_JN_Booking_Base extends \WC_Email {

	/**
	 * Current booking data array.
	 *
	 * @var array<string, mixed>
	 */
	public $booking_data = array();

	/**
	 * Initialize placeholders and parent WC_Email.
	 */
	public function __construct() {
		$this->placeholders = array(
			'{booking_code}'  => '',
			'{customer_name}' => '',
			'{package_title}' => '',
			'{order_number}'  => '',
		);

		parent::__construct();

		if ( ! $this->customer_email ) {
			$this->recipient = $this->get_option( 'recipient', implode( ', ', JN_Booking_Settings::get_staff_email_list() ) );
		}
	}

	/**
	 * Initialise Settings Form Fields for `WooCommerce -> Settings -> Emails`.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		parent::init_form_fields();
		if ( ! $this->customer_email ) {
			$this->form_fields['recipient'] = array(
				'title'       => 'Người nhận (Trợ lý)',
				'type'        => 'text',
				'description' => 'Danh sách email nhận thông báo (phân cách bằng dấu phẩy).',
				'placeholder' => implode( ', ', JN_Booking_Settings::get_staff_email_list() ),
				'default'     => implode( ', ', JN_Booking_Settings::get_staff_email_list() ),
				'desc_tip'    => true,
			);
		}
	}

	/**
	 * Populate booking placeholders and trigger sending.
	 *
	 * @param int $booking_id Booking post ID.
	 * @param int $order_id   Optional WooCommerce order ID.
	 * @return void
	 */
	public function trigger( int $booking_id, int $order_id = 0 ): void {
		$this->setup_locale();

		$data               = JN_Booking_Emails::get_booking_email_data( $booking_id, $order_id );
		$this->booking_data = $data;
		$this->object       = get_post( $booking_id );

		$this->placeholders['{booking_code}']  = $data['booking_code'];
		$this->placeholders['{customer_name}'] = $data['customer_name'];
		$this->placeholders['{package_title}'] = $data['package_title'];
		$this->placeholders['{order_number}']  = '' !== $data['order_number'] ? $data['order_number'] : (string) $data['order_id'];

		if ( $this->customer_email ) {
			$this->recipient = $data['customer_email'];
		} else {
			$configured      = (string) $this->get_option( 'recipient', '' );
			$this->recipient = '' !== trim( $configured )
				? $configured
				: implode( ', ', JN_Booking_Settings::get_staff_email_list() );
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send(
				$this->get_recipient(),
				$this->get_subject(),
				$this->get_content(),
				$this->get_headers(),
				$this->get_attachments()
			);
		}

		$this->restore_locale();
	}

	/**
	 * Wrap inner HTML with standard WooCommerce email header and footer.
	 *
	 * @param string $inner_html Inner email body HTML.
	 * @return string
	 */
	protected function wrap_woocommerce_email( string $inner_html ): string {
		ob_start();
		do_action( 'woocommerce_email_header', $this->get_heading(), $this );
		echo $inner_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$additional = $this->get_additional_content();
		if ( '' !== trim( $additional ) ) {
			echo '<div style="margin-top:20px;padding-top:16px;border-top:1px solid #E5DFD7;color:#716A61;font-size:13px;">' . wp_kses_post( wpautop( wptexturize( $additional ) ) ) . '</div>';
		}
		do_action( 'woocommerce_email_footer', $this );
		return (string) ob_get_clean();
	}

	/**
	 * Render summary table rows HTML.
	 *
	 * @param array<string, string> $rows Label => value pairs.
	 * @return string
	 */
	protected function render_summary_table( array $rows ): string {
		$html = '<table cellspacing="0" cellpadding="10" style="width:100%;border:1px solid #E5DFD7;border-radius:8px;margin:20px 0;background:#FAF8F5;border-collapse:collapse;"><tbody>';
		foreach ( $rows as $label => $value ) {
			if ( '' === $value ) {
				continue;
			}
			$html .= '<tr>';
			$html .= '<th scope="row" style="text-align:left;width:38%;border-bottom:1px solid #E5DFD7;color:#716A61;font-weight:600;font-size:13px;">' . esc_html( $label ) . '</th>';
			$html .= '<td style="text-align:left;border-bottom:1px solid #E5DFD7;color:#1F1E1D;font-size:14px;">' . wp_kses_post( $value ) . '</td>';
			$html .= '</tr>';
		}
		$html .= '</tbody></table>';
		return $html;
	}
}

/**
 * 1. Customer Intake Confirmation Email (`WC_Email_JN_Booking_New_Request`).
 */
class WC_Email_JN_Booking_New_Request extends WC_Email_JN_Booking_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'jn_booking_new_request';
		$this->customer_email = true;
		$this->title          = 'Lịch hẹn Coaching — Đã tiếp nhận yêu cầu (Khách hàng)';
		$this->description    = 'Gửi xác nhận cho khách hàng ngay sau khi khách gửi biểu mẫu đăng ký tư vấn & xếp lịch Coaching.';
		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return '[Jannie Nguyễn] Đã tiếp nhận yêu cầu xếp lịch Coaching ({booking_code})';
	}

	/**
	 * Default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return 'Cảm ơn bạn đã gửi yêu cầu xếp lịch Coaching';
	}

	/**
	 * HTML content.
	 *
	 * @return string
	 */
	public function get_content_html() {
		$d    = $this->booking_data;
		$body = '<p>Xin chào <strong>' . esc_html( $d['customer_name'] ) . '</strong>,</p>';
		$body .= '<p>' . esc_html( JN_Booking_Settings::SUCCESS_TEXT ) . '</p>';
		$body .= $this->render_summary_table(
			array(
				'Mã yêu cầu'           => esc_html( $d['booking_code'] ),
				'Gói cố vấn quan tâm'  => esc_html( $d['package_option_label'] ),
				'Hình thức gặp'        => esc_html( $d['format_label'] ),
				'Khung giờ thuận tiện' => esc_html( $d['preferred_window_label'] ),
				'Số điện thoại / Zalo' => esc_html( $d['customer_phone'] ),
				'Mục tiêu chia sẻ'     => nl2br( esc_html( $d['goals'] ) ),
				'Trạng thái'           => '<strong>' . esc_html( $d['status_label'] ) . '</strong>',
			)
		);
		return $this->wrap_woocommerce_email( $body );
	}

	/**
	 * Plain text content.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		$d = $this->booking_data;
		return sprintf(
			"Xin chào %s,\n\n%s\n\nMã lịch hẹn: %s\nGói cố vấn: %s\nHình thức: %s\nKhung giờ mong muốn: %s\nTrạng thái: %s\n",
			$d['customer_name'],
			JN_Booking_Settings::SUCCESS_TEXT,
			$d['booking_code'],
			$d['package_option_label'],
			$d['format_label'],
			$d['preferred_window_label'],
			$d['status_label']
		);
	}
}

/**
 * 2. Staff New Request Alert Email (`WC_Email_JN_Booking_Staff_Alert`).
 */
class WC_Email_JN_Booking_Staff_Alert extends WC_Email_JN_Booking_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'jn_booking_staff_alert';
		$this->customer_email = false;
		$this->title          = 'Lịch hẹn Coaching — Thông báo yêu cầu mới (Trợ lý)';
		$this->description    = 'Gửi thông báo tới danh sách email trợ lý khi có khách hàng gửi yêu cầu xếp lịch mới.';
		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return '[Nội bộ Jannie Nguyễn] Yêu cầu xếp lịch mới {booking_code} — {customer_name}';
	}

	/**
	 * Default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return 'Yêu cầu xếp lịch Coaching mới ({booking_code})';
	}

	/**
	 * HTML content.
	 *
	 * @return string
	 */
	public function get_content_html() {
		$d    = $this->booking_data;
		$body = '<p>Hệ thống vừa ghi nhận một yêu cầu đăng ký tư vấn &amp; xếp lịch Coaching mới cần trợ lý liên hệ xác nhận trong 24 giờ:</p>';
		$body .= $this->render_summary_table(
			array(
				'Mã lịch hẹn'          => '<strong>' . esc_html( $d['booking_code'] ) . '</strong>',
				'Khách hàng'           => esc_html( $d['customer_name'] ),
				'Số điện thoại / Zalo' => '<strong>' . esc_html( $d['customer_phone'] ) . '</strong>',
				'Địa chỉ Email'        => esc_html( $d['customer_email'] ),
				'Gói cố vấn'           => esc_html( $d['package_option_label'] ),
				'Hình thức gặp'        => esc_html( $d['format_label'] ),
				'Khung giờ mong muốn'  => esc_html( $d['preferred_window_label'] ),
				'Mục tiêu / Ghi chú'   => nl2br( esc_html( $d['goals'] ) ),
			)
		);
		$body .= '<p style="margin-top:24px;"><a href="' . esc_url( $d['admin_edit_url'] ) . '" style="display:inline-block;padding:12px 24px;background:#1F1E1D;color:#FAF8F5 !important;text-decoration:none !important;border-radius:9999px;font-weight:600;font-size:13px;">Mở Hồ Sơ &amp; Xác Nhận Lịch Hẹn</a></p>';
		return $this->wrap_woocommerce_email( $body );
	}

	/**
	 * Plain text content.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		$d = $this->booking_data;
		return sprintf(
			"Yêu cầu xếp lịch mới: %s\nKhách hàng: %s\nSĐT/Zalo: %s\nEmail: %s\nGói: %s\nKhung giờ mong muốn: %s\nQuản lý: %s\n",
			$d['booking_code'],
			$d['customer_name'],
			$d['customer_phone'],
			$d['customer_email'],
			$d['package_option_label'],
			$d['preferred_window_label'],
			$d['admin_edit_url']
		);
	}
}

/**
 * 3. Customer Confirmed Appointment Email with `.ics` and `order-pay` link (`WC_Email_JN_Booking_Confirmed`).
 */
class WC_Email_JN_Booking_Confirmed extends WC_Email_JN_Booking_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'jn_booking_confirmed';
		$this->customer_email = true;
		$this->title          = 'Lịch hẹn Coaching — Đã xác nhận lịch & Thanh toán (Khách hàng)';
		$this->description    = 'Gửi cho khách hàng khi trợ lý xác nhận ngày giờ hẹn, đính kèm tệp lịch .ics và liên kết thanh toán order-pay.';
		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return '[Jannie Nguyễn] Đã xác nhận lịch hẹn Coaching ({booking_code}) & Thông tin thanh toán';
	}

	/**
	 * Default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return 'Xác nhận lịch hẹn Coaching 1:1 cùng Jannie Nguyễn';
	}

	/**
	 * Attach the RFC 5545 `.ics` file for the confirmed appointment.
	 *
	 * @return string[]
	 */
	public function get_attachments() {
		$attachments = parent::get_attachments();
		if ( ! empty( $this->booking_data['booking_id'] ) ) {
			$ics_path = JN_Booking_ICS::write_attachment_file( (int) $this->booking_data['booking_id'] );
			if ( '' !== $ics_path && file_exists( $ics_path ) ) {
				$attachments[] = $ics_path;
			}
		}
		return $attachments;
	}

	/**
	 * HTML content with appointment details and `order-pay` button while unpaid.
	 *
	 * @return string
	 */
	public function get_content_html() {
		$d     = $this->booking_data;
		$venue = 'online' === $d['format']
			? '<a href="' . esc_url( $d['meeting_link'] ) . '">' . esc_html( $d['meeting_link'] ) . '</a>'
			: esc_html( $d['location'] );

		$body  = '<p>Xin chào <strong>' . esc_html( $d['customer_name'] ) . '</strong>,</p>';
		$body .= '<p>Trợ lý Học viện Jannie Nguyễn đã xác nhận khung giờ làm việc 1:1 của bạn. Tệp lịch mời (<code>.ics</code>) đã được đính kèm trong email này để bạn thêm nhanh vào lịch cá nhân.</p>';
		$body .= $this->render_summary_table(
			array(
				'Mã lịch hẹn'             => '<strong>' . esc_html( $d['booking_code'] ) . '</strong>',
				'Trạng thái'              => '<strong>' . esc_html( $d['status_label'] ) . '</strong>',
				'Gói cố vấn'              => esc_html( $d['package_title'] ),
				'Thời gian hẹn chính thức' => '<strong>' . esc_html( $d['confirmed_display'] ) . '</strong>',
				'Thời lượng'              => esc_html( $d['duration_minutes'] . ' phút' ),
				'Hình thức gặp'           => esc_html( $d['format_label'] ),
				'Liên kết / Địa điểm'     => $venue,
				'Đơn hàng liên kết'       => $d['order_id'] > 0 ? '#' . esc_html( $d['order_number'] ) . ' (' . $d['order_total_html'] . ')' : '',
			)
		);

		if ( ! empty( $d['is_payable'] ) && '' !== $d['pay_url'] ) {
			$body .= '<div style="margin:24px 0;padding:20px;background:#F3EFEA;border-radius:10px;border:1px solid #E5DFD7;">';
			$body .= '<p style="margin:0 0 12px;font-weight:600;color:#1F1E1D;">Hoàn tất thanh toán để giữ lịch hẹn chính thức:</p>';
			$body .= '<p style="margin:0 0 16px;"><a href="' . esc_url( $d['pay_url'] ) . '" style="display:inline-block;padding:14px 28px;background:#1F1E1D;color:#FAF8F5 !important;text-decoration:none !important;border-radius:9999px;font-weight:600;font-size:14px;">Thanh Toán Đơn Hàng Ngay</a></p>';
			$body .= '<p style="margin:0;font-size:12px;color:#716A61;">Liên kết thanh toán trực tiếp: <a href="' . esc_url( $d['pay_url'] ) . '">' . esc_html( $d['pay_url'] ) . '</a></p>';
			$body .= '</div>';
		}

		return $this->wrap_woocommerce_email( $body );
	}

	/**
	 * Plain text content.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		$d     = $this->booking_data;
		$venue = 'online' === $d['format'] ? $d['meeting_link'] : $d['location'];
		$text  = sprintf(
			"Xin chào %s,\n\nLịch hẹn %s của bạn đã được xác nhận.\nGói cố vấn: %s\nThời gian: %s (%d phút)\nHình thức: %s\nĐịa điểm / Liên kết: %s\n",
			$d['customer_name'],
			$d['booking_code'],
			$d['package_title'],
			$d['confirmed_display'],
			$d['duration_minutes'],
			$d['format_label'],
			$venue
		);
		if ( ! empty( $d['is_payable'] ) && '' !== $d['pay_url'] ) {
			$text .= "\nLiên kết thanh toán đơn hàng: " . $d['pay_url'] . "\n";
		}
		return $text;
	}
}

/**
 * 4. Staff Payment Received Notification Email (`WC_Email_JN_Booking_Payment_Received`).
 */
class WC_Email_JN_Booking_Payment_Received extends WC_Email_JN_Booking_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'jn_booking_payment_received';
		$this->customer_email = false;
		$this->title          = 'Lịch hẹn Coaching — Đã nhận thanh toán (Trợ lý)';
		$this->description    = 'Thông báo cho đội ngũ trợ lý khi đơn hàng liên kết với lịch hẹn đã được thanh toán.';
		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return '[Nội bộ Jannie Nguyễn] Đã thanh toán lịch hẹn {booking_code} (Đơn hàng #{order_number})';
	}

	/**
	 * Default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return 'Khách hàng đã hoàn tất thanh toán lịch hẹn ({booking_code})';
	}

	/**
	 * HTML content.
	 *
	 * @return string
	 */
	public function get_content_html() {
		$d    = $this->booking_data;
		$body = '<p>Đơn hàng liên kết với lịch hẹn <strong>' . esc_html( $d['booking_code'] ) . '</strong> đã được ghi nhận trạng thái <strong>Đã thanh toán</strong>:</p>';
		$body .= $this->render_summary_table(
			array(
				'Mã lịch hẹn'           => esc_html( $d['booking_code'] ),
				'Khách hàng'            => esc_html( $d['customer_name'] . ' (' . $d['customer_phone'] . ')' ),
				'Gói cố vấn'            => esc_html( $d['package_title'] ),
				'Thời gian hẹn'         => esc_html( $d['confirmed_display'] ),
				'Đơn hàng WooCommerce'  => '#' . esc_html( $d['order_number'] ) . ' — ' . $d['order_total_html'],
				'Trạng thái thanh toán' => '<strong>Đã thanh toán</strong>',
			)
		);
		$body .= '<p><a href="' . esc_url( $d['admin_edit_url'] ) . '" style="display:inline-block;padding:12px 24px;background:#1F1E1D;color:#FAF8F5 !important;text-decoration:none !important;border-radius:9999px;font-weight:600;font-size:13px;">Xem Chi Tiết Lịch Hẹn</a></p>';
		return $this->wrap_woocommerce_email( $body );
	}

	/**
	 * Plain text content.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		$d = $this->booking_data;
		return sprintf(
			"Đã nhận thanh toán cho lịch hẹn %s (Đơn hàng #%s).\nKhách hàng: %s (%s)\nThời gian hẹn: %s\nChi tiết: %s\n",
			$d['booking_code'],
			$d['order_number'],
			$d['customer_name'],
			$d['customer_phone'],
			$d['confirmed_display'],
			$d['admin_edit_url']
		);
	}
}

/**
 * 5. Booking Cancelled Notification Email (`WC_Email_JN_Booking_Cancelled`).
 */
class WC_Email_JN_Booking_Cancelled extends WC_Email_JN_Booking_Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'jn_booking_cancelled';
		$this->customer_email = true;
		$this->title          = 'Lịch hẹn Coaching — Thông báo hủy lịch hẹn';
		$this->description    = 'Thông báo khi lịch hẹn Coaching được chuyển sang trạng thái Đã hủy.';
		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return '[Jannie Nguyễn] Thông báo hủy lịch hẹn Coaching ({booking_code})';
	}

	/**
	 * Default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return 'Thông báo hủy lịch hẹn ({booking_code})';
	}

	/**
	 * HTML content.
	 *
	 * @return string
	 */
	public function get_content_html() {
		$d    = $this->booking_data;
		$body = '<p>Xin chào <strong>' . esc_html( $d['customer_name'] ) . '</strong>,</p>';
		$body .= '<p>Lịch hẹn <strong>' . esc_html( $d['booking_code'] ) . '</strong> (' . esc_html( $d['package_title'] ) . ') đã được chuyển sang trạng thái <strong>Đã hủy</strong>.</p>';
		$body .= '<p>Nếu bạn cần sắp xếp lại một khung giờ mới, vui lòng liên hệ trực tiếp với trợ lý Học viện qua email <code>contact@jannienguyen.vn</code>.</p>';
		return $this->wrap_woocommerce_email( $body );
	}

	/**
	 * Plain text content.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		$d = $this->booking_data;
		return sprintf(
			"Xin chào %s,\nLịch hẹn %s (%s) đã được chuyển sang trạng thái Đã hủy.\n",
			$d['customer_name'],
			$d['booking_code'],
			$d['package_title']
		);
	}
}
// phpcs:enable Generic.Files.OneObjectStructurePerFile.MultipleFound
