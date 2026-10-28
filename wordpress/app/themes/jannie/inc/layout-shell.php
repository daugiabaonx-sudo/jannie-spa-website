<?php
/**
 * Jannie Global Header, Off-Canvas, and Footer Builder Shell Integration.
 *
 * Configures the SVG JN monogram, header account actions, keyboard-accessible
 * off-canvas drawer (< 1280px), and 6/3/3 footer builder columns + copyright.
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the inline SVG "JN" monogram for header (44px) or footer (36px).
 *
 * @param int    $size    Width and height in pixels.
 * @param string $variant Either 'header' or 'footer'.
 * @return string
 */
function jannie_get_monogram_svg( $size = 44, $variant = 'header' ) {
	$size    = max( 24, (int) $size );
	$fill    = 'footer' === $variant ? 'var(--jn-surface-cream, #FAF8F5)' : 'var(--jn-surface-beige, #F3EFEA)';
	$class   = 'footer' === $variant ? 'jn-monogram-svg jn-monogram-footer' : 'default-logo jn-monogram-svg jn-monogram-header';
	$jn_path = 'M12.352 31.756V31.407Q13.13 31.374 13.666 31.005Q14.202 30.636 14.478 29.771Q14.753 28.905 14.753 27.425V17.536Q14.753 16.87 14.649 16.555Q14.544 16.24 14.237 16.123Q13.929 16.006 13.331 15.988V15.628Q13.731 15.646 14.382 15.664Q15.034 15.682 15.747 15.682Q16.46 15.682 17.131 15.664Q17.802 15.646 18.245 15.628V15.988Q17.648 16.006 17.34 16.123Q17.032 16.24 16.928 16.555Q16.823 16.87 16.823 17.536V24.124Q16.823 24.862 16.811 25.724Q16.798 26.586 16.735 27.396Q16.672 28.206 16.517 28.822Q16.193 30.078 15.138 30.917Q14.084 31.756 12.352 31.756ZM30.669 15.628V15.988Q30.071 16.024 29.763 16.208Q29.456 16.391 29.351 16.793Q29.247 17.194 29.247 17.932V28.444Q29.106 28.437 28.964 28.437Q28.822 28.437 28.664 28.444L21.021 17.356V25.996Q21.021 26.756 21.134 27.179Q21.248 27.602 21.606 27.78Q21.964 27.958 22.695 28.012V28.372Q22.364 28.336 21.831 28.327Q21.298 28.318 20.823 28.318Q20.384 28.318 19.934 28.327Q19.484 28.336 19.185 28.372V28.012Q19.786 27.958 20.092 27.783Q20.398 27.609 20.502 27.198Q20.607 26.788 20.607 26.068V17.536Q20.607 16.87 20.502 16.555Q20.398 16.24 20.09 16.123Q19.782 16.006 19.185 15.988V15.628Q19.484 15.646 19.934 15.664Q20.384 15.682 20.823 15.682Q21.23 15.682 21.604 15.664Q21.978 15.646 22.277 15.628L28.833 25.017V18.004Q28.833 17.23 28.719 16.814Q28.606 16.398 28.25 16.211Q27.893 16.024 27.159 15.988V15.628Q27.49 15.646 28.03 15.664Q28.57 15.682 29.031 15.682Q29.488 15.682 29.936 15.664Q30.384 15.646 30.669 15.628Z';

	return sprintf(
		'<svg class="%1$s" xmlns="http://www.w3.org/2000/svg" width="%2$d" height="%2$d" viewBox="0 0 44 44" fill="none" role="img" aria-label="Jannie Nguyen">' .
		'<circle cx="22" cy="22" r="21.5" fill="%3$s" stroke="var(--jn-border, #E5DFD7)" stroke-width="1"/>' .
		'<path fill="var(--jn-ink, #1F1E1D)" d="%4$s"/>' .
		'</svg>',
		esc_attr( $class ),
		$size,
		esc_attr( $fill ),
		esc_attr( $jn_path )
	);
}

/**
 * Ensure Blocksy's header logo element always resolves a non-empty attachment ID
 * so `logo/view.php` renders `.site-logo-container`.
 *
 * @param int|string $attachment_id Current custom logo attachment ID.
 * @return int|string
 */
function jannie_filter_header_logo_id( $attachment_id ) {
	if ( empty( $attachment_id ) ) {
		$site_icon_id = (int) get_option( 'site_icon', 0 );
		return $site_icon_id > 0 ? $site_icon_id : 1;
	}
	return $attachment_id;
}
add_filter( 'blocksy:header:logo:image-id', 'jannie_filter_header_logo_id' );

/**
 * Replace the raster `<img>` inside Blocksy's `.site-logo-container` with the
 * crisp inline SVG JN monogram.
 *
 * @param string       $html          HTML img element or empty string.
 * @param int          $attachment_id Image attachment ID.
 * @param string|int[] $size          Requested image size.
 * @param bool         $icon          Whether the image should be treated as an icon.
 * @param string[]     $attr          Array of attribute values for the image markup.
 * @return string
 */
function jannie_filter_logo_attachment_image( $html, $attachment_id, $size, $icon, $attr ) {
	if ( is_array( $attr ) && isset( $attr['class'] ) && false !== strpos( $attr['class'], 'default-logo' ) ) {
		return jannie_get_monogram_svg( 44, 'header' );
	}
	return $html;
}
add_filter( 'wp_get_attachment_image', 'jannie_filter_logo_attachment_image', 20, 5 );

/**
 * Prevent Blocksy from collapsing any of the 7 desktop header menu items into a "More" dropdown.
 */
add_filter( 'blocksy:header:menu:has-responsive-desktop-menu', '__return_false', 20 );

/**
 * Resolve the WooCommerce My Account URL with a clean fallback to `/tai-khoan/`.
 *
 * @return string
 */
function jannie_get_account_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$wc_url = wc_get_page_permalink( 'myaccount' );
		if ( ! empty( $wc_url ) ) {
			return $wc_url;
		}
	}
	return home_url( '/tai-khoan/' );
}

/**
 * Shortcode `[jn_header_account]` used inside Blocksy Header Builder's HTML item.
 *
 * Renders:
 * - "Đăng nhập" text link + vertical divider + "Đăng ký" pill -> WooCommerce My Account
 * - Account icon button (`person` SVG) with explicit `aria-label`
 *
 * @return string
 */
function jannie_render_header_account_shortcode() {
	$account_url  = jannie_get_account_url();
	$register_url = trailingslashit( $account_url ) . '#register';
	$person_icon  = '';

	if ( class_exists( 'JN_Icon_Sprite' ) ) {
		$person_icon = JN_Icon_Sprite::render(
			array(
				'name'  => 'person',
				'size'  => 18,
				'label' => '',
			)
		);
	}

	return sprintf(
		'<div class="jn-header-actions">' .
			'<div class="jn-header-auth">' .
				'<a class="jn-header-login" href="%1$s">Đăng nhập</a>' .
				'<span class="jn-header-divider" aria-hidden="true"></span>' .
				'<a class="jn-header-register" href="%2$s">Đăng ký</a>' .
			'</div>' .
			'<a class="jn-header-account-btn" href="%1$s" aria-label="%3$s">%4$s</a>' .
		'</div>',
		esc_url( $account_url ),
		esc_url( $register_url ),
		esc_attr( 'Tài khoản của tôi' ),
		$person_icon
	);
}
add_shortcode( 'jn_header_account', 'jannie_render_header_account_shortcode' );

/**
 * Shortcode `[jn_footer_brand]` for Footer Column 1 (6/12 width).
 *
 * Renders the 36px JN monogram, "JANNIE NGUYEN" wordmark, and italic mission copy.
 *
 * @return string
 */
function jannie_render_footer_brand_shortcode() {
	$monogram = jannie_get_monogram_svg( 36, 'footer' );

	return sprintf(
		'<div class="jn-footer-brand">' .
			'<a class="jn-footer-brand-link" href="%1$s" rel="home">' .
				'<span class="jn-footer-monogram-wrap">%2$s</span>' .
				'<span class="jn-footer-wordmark">JANNIE NGUYEN</span>' .
			'</a>' .
			'<p class="jn-footer-mission">Sứ mệnh của Jannie là tạo ra sự cân bằng và khám phá vẻ đẹp đích thực trong mỗi người.</p>' .
		'</div>',
		esc_url( home_url( '/' ) ),
		$monogram
	);
}
add_shortcode( 'jn_footer_brand', 'jannie_render_footer_brand_shortcode' );

/**
 * Shortcode `[jn_footer_explore]` for Footer Column 2 (3/12 width).
 *
 * Renders the "Khám Phá" heading and the 5 wireframe links.
 *
 * @return string
 */
function jannie_render_footer_explore_shortcode() {
	$menu_html = wp_nav_menu(
		array(
			'menu'            => 'Khám Phá',
			'container'       => 'nav',
			'container_class' => 'jn-footer-nav',
			'menu_class'      => 'jn-footer-menu-list',
			'echo'            => false,
			'fallback_cb'     => false,
			'depth'           => 1,
		)
	);

	if ( empty( $menu_html ) ) {
		$items = array(
			array(
				'label' => 'My Story',
				'url'   => home_url( '/my-story/' ),
			),
			array(
				'label' => 'Coaching & Mentorship',
				'url'   => home_url( '/coaching/' ),
			),
			array(
				'label' => 'Chương Trình Học',
				'url'   => home_url( '/course/' ),
			),
			array(
				'label' => 'Client Success Story',
				'url'   => home_url( '/client-success-story/' ),
			),
			array(
				'label' => 'Sản Phẩm Yêu Thích',
				'url'   => home_url( '/my-favorite-product/' ),
			),
		);

		$links = '';
		foreach ( $items as $item ) {
			$links .= sprintf(
				'<li><a href="%1$s">%2$s</a></li>',
				esc_url( $item['url'] ),
				esc_html( $item['label'] )
			);
		}
		$menu_html = '<nav class="jn-footer-nav" aria-label="Khám Phá"><ul class="jn-footer-menu-list">' . $links . '</ul></nav>';
	}

	return '<div class="jn-footer-col">' .
		'<span class="jn-footer-heading">Khám Phá</span>' .
		$menu_html .
	'</div>';
}
add_shortcode( 'jn_footer_explore', 'jannie_render_footer_explore_shortcode' );

/**
 * Shortcode `[jn_footer_contact]` for Footer Column 3 (3/12 width).
 *
 * Renders "Liên Hệ & Tư Vấn" with inline SVG icons and Project Context Card values.
 *
 * @return string
 */
function jannie_render_footer_contact_shortcode() {
	$loc_icon  = class_exists( 'JN_Icon_Sprite' ) ? JN_Icon_Sprite::render(
		array(
			'name' => 'location_on',
			'size' => 16,
		)
	) : '';
	$mail_icon = class_exists( 'JN_Icon_Sprite' ) ? JN_Icon_Sprite::render(
		array(
			'name' => 'mail',
			'size' => 16,
		)
	) : '';
	$time_icon = class_exists( 'JN_Icon_Sprite' ) ? JN_Icon_Sprite::render(
		array(
			'name' => 'schedule',
			'size' => 16,
		)
	) : '';

	return sprintf(
		'<div class="jn-footer-col">' .
			'<span class="jn-footer-heading">Liên Hệ &amp; Tư Vấn</span>' .
			'<ul class="jn-footer-contact-list">' .
				'<li class="jn-footer-contact-item">%1$s<span>TP. Hồ Chí Minh, Việt Nam</span></li>' .
				'<li class="jn-footer-contact-item">%2$s<a href="mailto:contact@jannienguyen.vn">contact@jannienguyen.vn</a></li>' .
				'<li class="jn-footer-contact-item">%3$s<span>Thứ Hai - Thứ Bảy: 09:00 - 18:00</span></li>' .
			'</ul>' .
		'</div>',
		$loc_icon,
		$mail_icon,
		$time_icon
	);
}
add_shortcode( 'jn_footer_contact', 'jannie_render_footer_contact_shortcode' );
add_filter( 'widget_block_content', 'do_shortcode', 9 );

/**
 * Override Blocksy footer copyright text so no theme credit ever appears and
 * the current year updates automatically.
 *
 * @return string
 */
function jannie_filter_footer_copyright() {
	return '© {current_year} Jannie Nguyen Academy. Tất cả quyền được bảo lưu.';
}
add_filter( 'blocksy:footer:copyright:default-value', 'jannie_filter_footer_copyright', 20 );
add_filter( 'blocksy:footer:copyright:value', 'jannie_filter_footer_copyright', 20 );

/**
 * Enhance Blocksy's `#offcanvas` keyboard operability across all viewports < 1280px:
 * - Moves focus into the drawer (`.ct-toggle-close`) when opened via keyboard/click.
 * - Closes the drawer cleanly on `Escape` (`keydown`) and restores focus to `.ct-header-trigger`.
 *
 * @return void
 */
function jannie_print_offcanvas_a11y_script() {
	?>
	<script id="jn-offcanvas-a11y">
	(function () {
		var offcanvas = document.getElementById('offcanvas');
		if (!offcanvas) {
			return;
		}
		var lastTrigger = null;

		document.addEventListener('click', function (e) {
			var trigger = e.target.closest('.ct-header-trigger');
			if (trigger) {
				lastTrigger = trigger;
				setTimeout(function () {
					var closeBtn = offcanvas.querySelector('.ct-toggle-close');
					if (closeBtn && offcanvas.classList.contains('active')) {
						closeBtn.focus();
					}
				}, 120);
			}
		});

		document.addEventListener('keydown', function (e) {
			if (e.key !== 'Escape' && e.keyCode !== 27) {
				return;
			}
			var isOpen = offcanvas.classList.contains('active') || document.body.hasAttribute('data-panel');
			if (!isOpen) {
				return;
			}
			e.preventDefault();
			var closeBtn = offcanvas.querySelector('.ct-toggle-close');
			if (closeBtn) {
				closeBtn.click();
			}
			var returnTarget = lastTrigger || document.querySelector('#header [data-device="mobile"] .ct-header-trigger') || document.querySelector('#header .ct-header-trigger');
			if (returnTarget) {
				setTimeout(function () {
					returnTarget.focus();
				}, 80);
				setTimeout(function () {
					returnTarget.focus();
				}, 240);
			}
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'jannie_print_offcanvas_a11y_script', 30 );
