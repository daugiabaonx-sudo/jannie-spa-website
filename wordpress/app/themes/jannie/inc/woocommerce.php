<?php
/**
 * Jannie Child Theme — WooCommerce Integration, Shop Page Routing, My Account Deep-Linking & Email Branding.
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue WooCommerce token stylesheet strictly on commerce pages.
 *
 * @return void
 */
function jannie_enqueue_woocommerce_assets() {
	if ( ! function_exists( 'jn_is_commerce_page' ) || ! jn_is_commerce_page() ) {
		return;
	}

	$woo_css_file = JANNIE_PATH . '/assets/css/woocommerce.css';
	if ( is_file( $woo_css_file ) && is_readable( $woo_css_file ) && function_exists( 'jannie_minify_css_string' ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$woo_css = jannie_minify_css_string( (string) file_get_contents( $woo_css_file ) );
		wp_add_inline_style( 'ct-main-styles', $woo_css );
		wp_register_style( 'jn-woocommerce', false, array( 'jn-block-styles' ), JANNIE_VERSION );
		wp_enqueue_style( 'jn-woocommerce' );
		return;
	}

	wp_enqueue_style(
		'jn-woocommerce',
		JANNIE_URI . '/assets/css/woocommerce.css',
		array( 'jn-block-styles' ),
		JANNIE_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'jannie_enqueue_woocommerce_assets', 30 );

/**
 * Do not render Blocksy's archive hero on the shop page (`/my-favorite-product/`): the page content
 * already has the designed H1, and the hidden hero H1 made the page carry two H1s (Step 10 audit).
 * Product category archives keep their hero H1.
 *
 * @param mixed $value Theme mod value for `woo_categories_hero_enabled`.
 * @return mixed
 */
function jannie_disable_shop_page_hero( $value ) {
	return ( did_action( 'wp' ) && function_exists( 'is_shop' ) && is_shop() ) ? 'no' : $value;
}
add_filter( 'theme_mod_woo_categories_hero_enabled', 'jannie_disable_shop_page_hero' );

/**
 * Enqueue WooCommerce token stylesheet inside the Block Editor so Cart/Checkout/Product Collection match.
 *
 * @return void
 */
function jannie_enqueue_editor_woocommerce_assets() {
	if ( ! is_admin() ) {
		return;
	}

	wp_enqueue_style(
		'jannie-editor-woocommerce',
		JANNIE_URI . '/assets/css/woocommerce.css',
		array( 'jannie-editor-block-styles' ),
		JANNIE_VERSION
	);
}
add_action( 'enqueue_block_assets', 'jannie_enqueue_editor_woocommerce_assets', 30 );

/**
 * Keep `/my-favorite-product/` (`woocommerce_shop_page_id = 12`) rendering its Gutenberg blocks
 * (`jannie/page-intro` + `woocommerce/product-collection`) instead of being hijacked by `archive-product.php` (ADR-006).
 *
 * @param array<string, mixed> $args Product post type registration arguments.
 * @return array<string, mixed>
 */
function jannie_disable_product_post_type_archive( $args ) {
	$args['has_archive'] = false;
	return $args;
}
add_filter( 'woocommerce_register_post_type_product', 'jannie_disable_product_post_type_archive', 20 );

/**
 * Prevent `WC_Template_Loader::template_loader()` from replacing `page.php` with `blocksy/woocommerce.php`
 * when viewing `/my-favorite-product/` (`woocommerce_shop_page_id = 12`), so its Gutenberg blocks render natively.
 *
 * @param bool   $has_template  Whether a block template exists.
 * @param string $template_name Template name being checked.
 * @return bool
 */
function jannie_bypass_wc_archive_template_on_favorite_page( $has_template, $template_name ) {
	if ( 'archive-product' === $template_name && is_page( 'my-favorite-product' ) ) {
		return true;
	}
	return $has_template;
}
add_filter( 'woocommerce_has_block_template', 'jannie_bypass_wc_archive_template_on_favorite_page', 20, 2 );

/**
 * Redirect legacy `/shop/` and `/my-account/` paths to `/my-favorite-product/` and `/tai-khoan/`.
 *
 * @return void
 */
function jannie_redirect_legacy_woo_slugs() {
	if ( is_admin() || ! isset( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}

	$request_path = trim( (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ), '/' );

	if ( 'shop' === $request_path ) {
		wp_safe_redirect( home_url( '/my-favorite-product/' ), 301 );
		exit;
	}

	if ( 'my-account' === $request_path ) {
		wp_safe_redirect( home_url( '/tai-khoan/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'jannie_redirect_legacy_woo_slugs', 5 );

/**
 * Show "Liên hệ" for a product without a price (Step 15: the old site never priced Xà bông thảo dược).
 * WooCommerce already makes such a product unpurchasable; this only replaces the empty price slot.
 *
 * @return string
 */
function jannie_empty_price_html() {
	return '<span class="jn-price-on-request">Liên hệ</span>';
}
add_filter( 'woocommerce_empty_price_html', 'jannie_empty_price_html' );

/**
 * Add semantic body classes on commerce surfaces and virtual-only checkouts.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function jannie_add_commerce_body_classes( $classes ) {
	if ( function_exists( 'jn_is_commerce_page' ) && jn_is_commerce_page() ) {
		$classes[] = 'jn-commerce-page';
	}

	if (
		function_exists( 'is_checkout' ) &&
		is_checkout() &&
		class_exists( 'JN_Coaching_Packages' ) &&
		JN_Coaching_Packages::is_virtual_only_cart()
	) {
		$classes[] = 'jn-checkout-virtual-only';
	}

	return $classes;
}
add_filter( 'body_class', 'jannie_add_commerce_body_classes', 20 );

/**
 * Add `id="register"` to the WooCommerce registration `<form>` on `/tai-khoan/`
 * so the header "Đăng ký" button (`/tai-khoan/#register`) deep-links directly to it.
 *
 * @return void
 */
function jannie_add_register_form_id_attr() {
	echo 'id="register"';
}
add_action( 'woocommerce_register_form_tag', 'jannie_add_register_form_id_attr' );

/**
 * Render an editorial section header at the top of `/tai-khoan/` (My Account).
 *
 * @return void
 */
function jannie_render_myaccount_intro() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return;
	}

	static $rendered = false;
	if ( $rendered ) {
		return;
	}
	$rendered = true;

	$is_logged_in = is_user_logged_in();
	$eyebrow      = $is_logged_in ? 'Khu Vực Học Viên & Khách Hàng' : 'Chào Mừng Bạn Đến Với Jannie Nguyen';
	$title        = $is_logged_in ? 'Tài Khoản Của Tôi' : 'Đăng Nhập & Đăng Ký Thành Viên';
	$subtitle     = $is_logged_in
		? 'Theo dõi trạng thái đơn hàng, lịch sử giao dịch và quản lý thông tin cá nhân của bạn.'
		: 'Đăng nhập để quản lý đơn hàng và lộ trình học tập, hoặc tạo tài khoản mới chỉ với địa chỉ email và mật khẩu.';
	?>
	<header class="jn-myaccount-header">
		<span class="jn-myaccount-eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
		<h1 class="jn-myaccount-title"><?php echo esc_html( $title ); ?></h1>
		<p class="jn-myaccount-subtitle"><?php echo esc_html( $subtitle ); ?></p>
	</header>
	<?php
}
add_action( 'woocommerce_before_customer_login_form', 'jannie_render_myaccount_intro', 5 );
add_action( 'woocommerce_before_account_navigation', 'jannie_render_myaccount_intro', 5 );

/**
 * Progressive enhancement for `/tai-khoan/#register` deep-linking:
 * highlights the registration column and moves focus to `#reg_email`.
 *
 * @return void
 */
function jannie_print_register_deeplink_script() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_user_logged_in() ) {
		return;
	}
	?>
	<script id="jn-register-deeplink">
	(function () {
		function activateRegisterTarget() {
			if (window.location.hash !== '#register') {
				return;
			}
			var regForm = document.getElementById('register');
			if (!regForm) {
				return;
			}
			var regCol = regForm.closest('.u-column2');
			if (regCol) {
				regCol.classList.add('is-register-highlighted');
			}
			var emailInput = document.getElementById('reg_email');
			if (emailInput) {
				setTimeout(function () {
					regForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
					emailInput.focus({ preventScroll: true });
				}, 80);
			}
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', activateRegisterTarget);
		} else {
			activateRegisterTarget();
		}
		window.addEventListener('hashchange', activateRegisterTarget);
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'jannie_print_register_deeplink_script', 35 );

/**
 * Ensure WooCommerce Cart/Checkout React Blocks `div[aria-label]` nodes always have valid ARIA roles
 * (`role="region"` on order summary and `role="status"` on skeleton elements) so WCAG 2.1/2.2 AA
 * (`aria-prohibited-attr`) passes with 0 violations before and after hydration.
 *
 * @return void
 */
function jannie_print_wc_blocks_a11y_script() {
	if ( ! function_exists( 'jn_is_commerce_page' ) || ! jn_is_commerce_page() ) {
		return;
	}
	?>
	<script id="jn-wc-blocks-a11y">
	(function () {
		function patchAriaProhibitedNodes() {
			var summaries = document.querySelectorAll('.wc-block-components-order-summary[aria-label]:not([role])');
			for (var i = 0; i < summaries.length; i++) {
				summaries[i].setAttribute('role', 'region');
			}
			var skeletons = document.querySelectorAll('.wc-block-components-skeleton__element[aria-label]:not([role])');
			for (var j = 0; j < skeletons.length; j++) {
				skeletons[j].setAttribute('role', 'status');
			}
			var genericDivs = document.querySelectorAll('.wp-block-woocommerce-checkout div[aria-label]:not([role]), .wp-block-woocommerce-cart div[aria-label]:not([role])');
			for (var k = 0; k < genericDivs.length; k++) {
				genericDivs[k].setAttribute('role', 'region');
			}
		}
		patchAriaProhibitedNodes();
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', patchAriaProhibitedNodes);
		}
		if (typeof MutationObserver !== 'undefined' && document.body) {
			var obs = new MutationObserver(patchAriaProhibitedNodes);
			obs.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['aria-label'] });
		}
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'jannie_print_wc_blocks_a11y_script', 36 );

/**
 * Brand WooCommerce transactional emails with Jannie Nguyen design tokens and typography.
 *
 * @param string $css Default WooCommerce email stylesheet.
 * @return string
 */
function jannie_filter_woocommerce_email_styles( $css ) {
	$brand_css = '
		body, #wrapper, #outer_wrapper {
			background-color: #FAF8F5 !important;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
			color: #1F1E1D !important;
		}
		#template_header_image {
			padding: 28px 0 20px !important;
			text-align: center !important;
		}
		#template_header_image img {
			width: 210px !important;
			max-width: 210px !important;
			height: auto !important;
			margin: 0 auto !important;
			display: inline-block !important;
		}
		#template_container {
			background-color: #FFFFFF !important;
			border: 1px solid #E5DFD7 !important;
			border-radius: 12px !important;
			overflow: hidden !important;
			box-shadow: 0 4px 20px rgba(31, 30, 29, 0.04) !important;
		}
		#template_header {
			background-color: #1F1E1D !important;
			color: #FAF8F5 !important;
			border-bottom: 1px solid #E5DFD7 !important;
		}
		#header_wrapper {
			padding: 32px 36px !important;
		}
		#header_wrapper h1 {
			font-family: Georgia, "Playfair Display", "Times New Roman", serif !important;
			font-size: 24px !important;
			line-height: 1.3 !important;
			font-weight: 500 !important;
			color: #FAF8F5 !important;
			letter-spacing: -0.01em !important;
			margin: 0 !important;
		}
		#body_content_inner {
			font-size: 15px !important;
			line-height: 1.65 !important;
			color: #1F1E1D !important;
		}
		h2, h3 {
			font-family: Georgia, "Playfair Display", "Times New Roman", serif !important;
			color: #1F1E1D !important;
			font-weight: 600 !important;
		}
		a {
			color: #735944 !important;
			text-decoration: underline !important;
		}
		.td, table.td {
			border-color: #E5DFD7 !important;
		}
		#template_footer #credit {
			color: #716A61 !important;
			font-size: 12px !important;
			line-height: 1.6 !important;
			padding: 24px 20px !important;
			text-align: center !important;
		}
	';

	return $css . "\n" . $brand_css;
}
add_filter( 'woocommerce_email_styles', 'jannie_filter_woocommerce_email_styles', 20 );
