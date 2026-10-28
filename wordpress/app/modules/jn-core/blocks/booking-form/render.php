<?php
/**
 * Server-side render callback template for the `jn/booking-form` block (Step 5 Booking Engine).
 *
 * Renders the wireframe registration form verbatim with:
 * - Dynamic package options from bookable WooCommerce products (`JN_Coaching_Packages::get_bookable_packages_for_form()`)
 * - Server-side package preselection from `?goi=<package-key>` (no-JS support)
 * - Default selection: "Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)"
 * - Optional monthly capacity waitlist banner (`JN_Booking_Service::is_package_at_capacity()`)
 * - Page-cache-safe stateless signed token + REST endpoint attributes built strictly with `rest_url()`
 * - Post/Redirect/Get (PRG) no-JS submission feedback
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

$jn_packages = JN_Coaching_Packages::get_bookable_packages_for_form();

$jn_selected_key = isset( $attributes['defaultPackage'] ) ? sanitize_key( (string) $attributes['defaultPackage'] ) : '60-phut';
if ( ! isset( $jn_packages[ $jn_selected_key ] ) ) {
	$jn_selected_key = isset( $jn_packages['60-phut'] ) ? '60-phut' : (string) array_key_first( $jn_packages );
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
			if ( $jn_pkg_data['value'] === $jn_raw_goi || $jn_pkg_data['label'] === $jn_raw_goi ) {
				$jn_selected_key = $jn_pkg_slug;
				break;
			}
		}
	}
}

$jn_selected_value = isset( $jn_packages[ $jn_selected_key ]['value'] )
	? (string) $jn_packages[ $jn_selected_key ]['value']
	: 'Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)';

$jn_formats       = (array) JN_Booking_Settings::get( 'formats', array() );
$jn_time_windows  = (array) JN_Booking_Settings::get( 'time_windows', array() );
$jn_waitlist_note = (string) JN_Booking_Settings::get( 'waitlist_note', JN_Booking_Settings::DEFAULT_WAITLIST_NOTE );
$jn_privacy_meta  = JN_Booking_Settings::get_privacy_metadata();
$jn_privacy_url   = (string) $jn_privacy_meta['url'];

$jn_capacity_map = array();
foreach ( array_keys( $jn_packages ) as $jn_pkg_slug ) {
	$jn_capacity_map[ $jn_pkg_slug ] = JN_Booking_Service::is_package_at_capacity( $jn_pkg_slug );
}
$jn_selected_at_capacity = ! empty( $jn_capacity_map[ $jn_selected_key ] );

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

// Consume Post/Redirect/Get (PRG) flash result when submitted without JavaScript.
$jn_prg_flash     = JN_Booking_REST::consume_prg_flash();
$jn_is_success    = is_array( $jn_prg_flash ) && ! empty( $jn_prg_flash['ok'] );
$jn_is_error      = is_array( $jn_prg_flash ) && empty( $jn_prg_flash['ok'] );
$jn_error_message = $jn_is_error && isset( $jn_prg_flash['message'] ) ? (string) $jn_prg_flash['message'] : '';

// Issue initial token backdated by MIN_FILL_SECONDS so server-rendered no-JS submissions work immediately,
// while JS mode refreshes a live short-lived signed token on first interaction via `rest_url()`.
$jn_initial_issued = time();
$jn_form_token     = JN_Booking_Service::issue_form_token( $jn_initial_issued );
$jn_submit_url     = rest_url( JN_Booking_REST::NAMESPACE . '/bookings' );
$jn_token_url      = rest_url( JN_Booking_REST::NAMESPACE . '/booking/token' );
$jn_current_url    = home_url( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/' );

$jn_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'jn-booking-form-wrap',
	)
);
?>
<div <?php echo $jn_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<form
		class="jn-booking-form"
		id="coaching-registration-form"
		method="post"
		action=""
		data-submit-url="<?php echo esc_url( $jn_submit_url ); ?>"
		data-token-url="<?php echo esc_url( $jn_token_url ); ?>"
		data-min-fill="<?php echo esc_attr( (string) JN_Booking_Service::MIN_FILL_SECONDS ); ?>"
		novalidate
	>
		<input type="hidden" name="jn_booking_submit_action" value="1" />
		<input type="hidden" name="_jn_form_token" id="jn-form-token" value="<?php echo esc_attr( $jn_form_token ); ?>" data-issued-at="<?php echo esc_attr( (string) $jn_initial_issued ); ?>" />
		<input type="hidden" name="_jn_landing_url" id="jn-landing-url" value="<?php echo esc_url( $jn_current_url ); ?>" />
		<input type="hidden" name="_jn_referrer" id="jn-referrer" value="" />

		<!-- Honeypot field hidden from people and assistive technology -->
		<div class="jn-booking-form__hp" aria-hidden="true">
			<label for="jn-website-hp">Website</label>
			<input type="text" id="jn-website-hp" name="jn_website_hp" value="" tabindex="-1" autocomplete="off" />
		</div>

		<!-- Row 1: Full Name & Phone -->
		<div class="jn-booking-form__row jn-booking-form__row--2col">
			<div class="jn-booking-form__field">
				<label class="jn-booking-form__label" for="full-name">Họ và Tên <span class="jn-booking-form__required" aria-hidden="true">*</span></label>
				<input class="jn-booking-form__input" id="full-name" name="full_name" type="text" required aria-required="true" autocomplete="name" maxlength="<?php echo esc_attr( (string) JN_Booking_Service::MAX_NAME_LENGTH ); ?>" placeholder="Nguyễn Văn A" />
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
						<?php $jn_at_cap = ! empty( $jn_capacity_map[ $jn_pkg_slug ] ); ?>
						<option
							value="<?php echo esc_attr( $jn_pkg['value'] ); ?>"
							data-package-key="<?php echo esc_attr( $jn_pkg_slug ); ?>"
							data-at-capacity="<?php echo $jn_at_cap ? '1' : '0'; ?>"
							<?php selected( $jn_selected_value, $jn_pkg['value'] ); ?>
						><?php echo esc_html( $jn_pkg['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php echo $jn_expand_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<p
				class="jn-booking-form__waitlist-note<?php echo $jn_selected_at_capacity ? ' is-visible' : ''; ?>"
				id="jn-booking-waitlist-note"
				<?php echo $jn_selected_at_capacity ? '' : 'hidden'; ?>
			><?php echo esc_html( $jn_waitlist_note ); ?></p>
		</div>

		<!-- Row 4: Preferred Mode & Time Preference -->
		<div class="jn-booking-form__row jn-booking-form__row--2col">
			<div class="jn-booking-form__field">
				<label class="jn-booking-form__label" for="coaching-format">Hình Thức Gặp</label>
				<div class="jn-booking-form__select-wrap">
					<select class="jn-booking-form__select" id="coaching-format" name="coaching_format">
						<?php foreach ( $jn_formats as $jn_fmt_key => $jn_fmt_label ) : ?>
							<option value="<?php echo esc_attr( $jn_fmt_key ); ?>"><?php echo esc_html( $jn_fmt_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo $jn_expand_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
			<div class="jn-booking-form__field">
				<label class="jn-booking-form__label" for="time-preference">Khung Giờ Thuận Tiện</label>
				<div class="jn-booking-form__select-wrap">
					<select class="jn-booking-form__select" id="time-preference" name="time_preference">
						<?php foreach ( $jn_time_windows as $jn_win_key => $jn_win_label ) : ?>
							<option value="<?php echo esc_attr( $jn_win_key ); ?>"><?php echo esc_html( $jn_win_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo $jn_expand_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>

		<!-- Row 5: Current Challenge / Goals Message -->
		<div class="jn-booking-form__field">
			<label class="jn-booking-form__label" for="notes">Mục Tiêu Hoặc Thách Thức Lớn Nhất Của Bạn</label>
			<textarea class="jn-booking-form__textarea" id="notes" name="notes" rows="3" maxlength="<?php echo esc_attr( (string) JN_Booking_Service::MAX_NOTES_LENGTH ); ?>" placeholder="Chia sẻ ngắn gọn tình trạng kỹ thuật, lượng khách hiện tại hoặc câu hỏi bạn mong muốn giải quyết nhất cùng Jannie..."></textarea>
		</div>

		<!-- Row 6: Privacy Policy Consent Checkbox (Unchecked by default) -->
		<div class="jn-booking-form__consent">
			<input class="jn-booking-form__checkbox" type="checkbox" id="privacy-consent" name="privacy_consent" value="1" required aria-required="true" />
			<label class="jn-booking-form__consent-label" for="privacy-consent">
				Tôi đồng ý để Jannie Nguyen Academy thu thập và xử lý thông tin cá nhân đã cung cấp nhằm mục đích liên hệ tư vấn và sắp xếp lịch hẹn theo <a href="<?php echo esc_url( $jn_privacy_url ); ?>">Chính sách bảo mật</a>. <span class="jn-booking-form__required" aria-hidden="true">*</span>
			</label>
		</div>

		<!-- Submit Trigger -->
		<div class="jn-booking-form__submit-wrap">
			<button class="jn-booking-form__submit<?php echo $jn_is_success ? ' is-hidden' : ''; ?>" id="submit-btn" type="submit">
				<span>Gửi Yêu Cầu Xếp Lịch Coaching</span>
				<?php echo $jn_send_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>

		<!-- Accessible Live Region for Validation Errors and Wireframe Verbatim Success Text -->
		<?php
		$jn_feedback_classes = 'jn-booking-form__feedback';
		if ( $jn_is_success ) {
			$jn_feedback_classes .= ' is-visible';
		} elseif ( $jn_is_error ) {
			$jn_feedback_classes .= ' is-visible is-error';
		}
		?>
		<div class="<?php echo esc_attr( $jn_feedback_classes ); ?>" id="form-feedback" role="status" aria-live="polite" aria-atomic="true">
			<?php if ( $jn_is_error ) : ?>
				<?php echo esc_html( $jn_error_message ); ?>
			<?php else : ?>
				<p class="jn-booking-form__feedback-copy"><span class="jn-booking-form__feedback-strong">Cảm ơn bạn!</span> Yêu cầu đã được ghi nhận. Đội ngũ trợ lý của Jannie Nguyễn sẽ liên hệ trực tiếp qua số điện thoại của bạn trong vòng 24 giờ.</p>
			<?php endif; ?>
		</div>
	</form>
</div>
