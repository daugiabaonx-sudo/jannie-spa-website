<?php
/**
 * Server-side render callback template for the `jn/booking-form` block.
 *
 * Renders the wireframe registration form verbatim with:
 * - Server-side package preselection from `?goi=<package-key>` (no-JS support)
 * - Default selection: "Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)"
 * - Required markers, autocomplete="name|tel|email", inputmode="tel"
 * - Unchecked privacy policy consent checkbox (flagged UNK-09 for owner/legal review)
 * - Hidden honeypot field (hidden from sighted users and assistive technologies)
 * - aria-live region for validation errors and local Step 3 submission notice
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Block inner content (empty).
 * @var WP_Block             $block      Block instance.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$jn_packages = array(
	'60-phut'            => array(
		'value' => 'Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)',
		'label' => 'Gói Coaching Chuyên Nghiệp 1:1 (60 Phút) — Dành cho thợ & chủ Spa',
	),
	'30-phut'            => array(
		'value' => 'Gói Tư Vấn Cá Nhân 1:1 (30 Phút)',
		'label' => 'Gói Tư Vấn Cá Nhân 1:1 (30 Phút) — Định hình vẻ đẹp riêng',
	),
	'mentorship-3-thang' => array(
		'value' => 'Gói Master Mentorship (3 Tháng)',
		'label' => 'Gói Master Mentorship (Lộ trình 3 tháng toàn diện)',
	),
);

$jn_selected_key = isset( $attributes['defaultPackage'] ) ? sanitize_key( (string) $attributes['defaultPackage'] ) : '60-phut';
if ( ! isset( $jn_packages[ $jn_selected_key ] ) ) {
	$jn_selected_key = '60-phut';
}

// Preselect package on the server when `?goi=<package key>` is present in the URL.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL parameter used strictly to preselect a <select> option.
if ( isset( $_GET['goi'] ) && is_string( $_GET['goi'] ) ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$jn_raw_goi = sanitize_text_field( wp_unslash( $_GET['goi'] ) );
	$jn_key_goi = sanitize_key( $jn_raw_goi );

	if ( isset( $jn_packages[ $jn_key_goi ] ) ) {
		$jn_selected_key = $jn_key_goi;
	} else {
		foreach ( $jn_packages as $jn_pkg_slug => $jn_pkg_data ) {
			if ( $jn_pkg_data['value'] === $jn_raw_goi ) {
				$jn_selected_key = $jn_pkg_slug;
				break;
			}
		}
	}
}

$jn_selected_value = $jn_packages[ $jn_selected_key ]['value'];

$jn_privacy_url = get_privacy_policy_url();
if ( empty( $jn_privacy_url ) ) {
	$jn_privacy_url = home_url( '/chinh-sach-bao-mat/' );
}

$jn_expand_icon = JN_Icon_Sprite::render(
	array(
		'name'  => 'expand_more',
		'size'  => 20,
		'class' => 'jn-booking-form__select-icon',
	)
);

$jn_send_icon = JN_Icon_Sprite::render(
	array(
		'name'  => 'send',
		'size'  => 18,
		'class' => 'jn-booking-form__send-icon',
	)
);

// Nonce for local submission check on no-JS fallback.
$jn_submitted_notice = false;
if ( isset( $_POST['jn_booking_step3_nonce'] ) ) {
	$jn_nonce = sanitize_text_field( wp_unslash( $_POST['jn_booking_step3_nonce'] ) );
	$jn_hp    = isset( $_POST['jn_website_hp'] ) ? sanitize_text_field( wp_unslash( $_POST['jn_website_hp'] ) ) : '';
	if ( wp_verify_nonce( $jn_nonce, 'jn_booking_step3_preview' ) && '' === $jn_hp ) {
		$jn_submitted_notice = true;
	}
}

$jn_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'jn-booking-form-wrap',
	)
);
?>
<div <?php echo $jn_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<form class="jn-booking-form" id="coaching-registration-form" method="post" action="#booking-form" novalidate>
		<?php wp_nonce_field( 'jn_booking_step3_preview', 'jn_booking_step3_nonce' ); ?>

		<!-- Honeypot field hidden from people and assistive technology -->
		<div class="jn-booking-form__hp" aria-hidden="true">
			<label for="jn-website-hp">Website</label>
			<input type="text" id="jn-website-hp" name="jn_website_hp" value="" tabindex="-1" autocomplete="off" />
		</div>

		<!-- Row 1: Full Name & Phone -->
		<div class="jn-booking-form__row jn-booking-form__row--2col">
			<div class="jn-booking-form__field">
				<label class="jn-booking-form__label" for="full-name">Họ và Tên <span class="jn-booking-form__required" aria-hidden="true">*</span></label>
				<input class="jn-booking-form__input" id="full-name" name="full_name" type="text" required aria-required="true" autocomplete="name" placeholder="Nguyễn Văn A" />
			</div>
			<div class="jn-booking-form__field">
				<label class="jn-booking-form__label" for="phone-number">Số Điện Thoại / Zalo <span class="jn-booking-form__required" aria-hidden="true">*</span></label>
				<input class="jn-booking-form__input" id="phone-number" name="phone_number" type="tel" inputmode="tel" required aria-required="true" autocomplete="tel" placeholder="090 123 4567" />
			</div>
		</div>

		<!-- Row 2: Email -->
		<div class="jn-booking-form__field">
			<label class="jn-booking-form__label" for="email-address">Địa Chỉ Email <span class="jn-booking-form__required" aria-hidden="true">*</span></label>
			<input class="jn-booking-form__input" id="email-address" name="email_address" type="email" required aria-required="true" autocomplete="email" placeholder="name@example.com" />
		</div>

		<!-- Row 3: Package Selection Dropdown -->
		<div class="jn-booking-form__field">
			<label class="jn-booking-form__label" for="package-select">Gói Cố Vấn Quan Tâm <span class="jn-booking-form__required" aria-hidden="true">*</span></label>
			<div class="jn-booking-form__select-wrap">
				<select class="jn-booking-form__select" id="package-select" name="package_code" required aria-required="true">
					<?php foreach ( $jn_packages as $jn_pkg_slug => $jn_pkg ) : ?>
						<option value="<?php echo esc_attr( $jn_pkg['value'] ); ?>" data-package-key="<?php echo esc_attr( $jn_pkg_slug ); ?>" <?php selected( $jn_selected_value, $jn_pkg['value'] ); ?>><?php echo esc_html( $jn_pkg['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php echo $jn_expand_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>

		<!-- Row 4: Preferred Mode & Time Preference -->
		<div class="jn-booking-form__row jn-booking-form__row--2col">
			<div class="jn-booking-form__field">
				<label class="jn-booking-form__label" for="coaching-format">Hình Thức Gặp</label>
				<div class="jn-booking-form__select-wrap">
					<select class="jn-booking-form__select" id="coaching-format" name="coaching_format">
						<option value="online">Online Trực Tuyến (Zoom High-Res)</option>
						<option value="in-person">Trực tiếp tại Academy (TP.HCM)</option>
					</select>
					<?php echo $jn_expand_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
			<div class="jn-booking-form__field">
				<label class="jn-booking-form__label" for="time-preference">Khung Giờ Thuận Tiện</label>
				<div class="jn-booking-form__select-wrap">
					<select class="jn-booking-form__select" id="time-preference" name="time_preference">
						<option value="morning">Buổi sáng (09:00 - 11:30)</option>
						<option value="afternoon">Buổi chiều (14:00 - 17:30)</option>
						<option value="weekend">Cuối tuần (Thứ 7)</option>
					</select>
					<?php echo $jn_expand_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>

		<!-- Row 5: Current Challenge / Goals Message -->
		<div class="jn-booking-form__field">
			<label class="jn-booking-form__label" for="notes">Mục Tiêu Hoặc Thách Thức Lớn Nhất Của Bạn</label>
			<textarea class="jn-booking-form__textarea" id="notes" name="notes" rows="3" placeholder="Chia sẻ ngắn gọn tình trạng kỹ thuật, lượng khách hiện tại hoặc câu hỏi bạn mong muốn giải quyết nhất cùng Jannie..."></textarea>
		</div>

		<!-- Row 6: Privacy Policy Consent Checkbox (Unchecked by default; wording flagged UNK-09 for owner/legal review) -->
		<div class="jn-booking-form__consent">
			<input class="jn-booking-form__checkbox" type="checkbox" id="privacy-consent" name="privacy_consent" value="1" required aria-required="true" />
			<label class="jn-booking-form__consent-label" for="privacy-consent">
				Tôi đồng ý để Jannie Nguyen Academy thu thập và xử lý thông tin cá nhân đã cung cấp nhằm mục đích liên hệ tư vấn và sắp xếp lịch hẹn theo <a href="<?php echo esc_url( $jn_privacy_url ); ?>">Chính sách bảo mật</a>. <span class="jn-booking-form__required" aria-hidden="true">*</span>
			</label>
		</div>

		<!-- Submit Trigger -->
		<div class="jn-booking-form__submit-wrap">
			<button class="jn-booking-form__submit" id="submit-btn" type="submit">
				<span>Gửi Yêu Cầu Xếp Lịch Coaching</span>
				<?php echo $jn_send_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>

		<!-- Accessible Live Region for Validation Errors and Step 3 Local Notice / Wireframe Success Text -->
		<div class="jn-booking-form__feedback<?php echo $jn_submitted_notice ? ' is-visible' : ''; ?>" id="form-feedback" role="status" aria-live="polite" aria-atomic="true">
			<?php if ( $jn_submitted_notice ) : ?>
				<p class="jn-booking-form__feedback-dev">[Thông báo hệ thống: Chức năng đặt lịch trực tuyến đang hoàn thiện ở Step 5 — dữ liệu hiện chỉ kiểm thử cục bộ trên trình duyệt.]</p>
			<?php endif; ?>
			<p class="jn-booking-form__feedback-copy"><span class="jn-booking-form__feedback-strong">Cảm ơn bạn!</span> Yêu cầu đã được ghi nhận. Đội ngũ trợ lý của Jannie Nguyễn sẽ liên hệ trực tiếp qua số điện thoại của bạn trong vòng 24 giờ.</p>
		</div>
	</form>
</div>
