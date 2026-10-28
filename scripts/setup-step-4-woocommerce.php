<?php
/**
 * JN Step 4 — Idempotent WooCommerce Store, Categories, Coaching Packages, Pages & Gateways Setup.
 *
 * Run via: ./scripts/wp.sh eval-file scripts/setup-step-4-woocommerce.php
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Destructive local-only script: refuse to run against production (review 2026-10-28, H2).
require_once __DIR__ . '/lib/jn-env-guard.php';
jn_require_non_production( basename( __FILE__ ) );

echo "=== 1. Configuring WooCommerce Onboarding, Privacy & Tracking Flags ===\n";
update_option( 'woocommerce_allow_tracking', 'no' );
update_option( 'woocommerce_show_marketplace_suggestions', 'no' );
update_option( 'woocommerce_feature_order_attribution_enabled', 'no' );
update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'woocommerce_store_pages_only', 'no' );
update_option(
	'woocommerce_onboarding_profile',
	array(
		'completed' => true,
		'skipped'   => true,
	)
);
update_option( 'woocommerce_task_list_hidden', 'yes' );
update_option( 'woocommerce_task_list_complete', 'yes' );

echo "=== 2. Configuring Store Address, Currency (VND), Taxes, HPOS, Accounts & Legal Pages ===\n";
// Store address & selling location (Vietnam per Project Context Card).
update_option( 'woocommerce_store_address', 'TP. Hồ Chí Minh' );
update_option( 'woocommerce_store_address_2', '' );
update_option( 'woocommerce_store_city', 'TP. Hồ Chí Minh' );
update_option( 'woocommerce_default_country', 'VN' );
update_option( 'woocommerce_store_postcode', '700000' );
update_option( 'woocommerce_allowed_countries', 'specific' );
update_option( 'woocommerce_specific_allowed_countries', array( 'VN' ) );
update_option( 'woocommerce_ship_to_countries', 'disabled' );

// Currency: VND, 0 decimals, thousand separator ".", decimal separator ",", symbol after amount with space ("right_space").
update_option( 'woocommerce_currency', 'VND' );
update_option( 'woocommerce_price_num_decimals', '0' );
update_option( 'woocommerce_price_thousand_sep', '.' );
update_option( 'woocommerce_price_decimal_sep', ',' );
update_option( 'woocommerce_currency_pos', 'right_space' );

// Taxes off.
update_option( 'woocommerce_calc_taxes', 'no' );

// HPOS on, legacy sync off.
update_option( 'woocommerce_custom_orders_table_enabled', 'yes' );
update_option( 'woocommerce_custom_orders_table_data_sync_enabled', 'no' );
update_option( 'woocommerce_feature_custom_order_tables_enabled', 'yes' );

// Guest checkout & My Account registration.
update_option( 'woocommerce_enable_guest_checkout', 'yes' );
update_option( 'woocommerce_enable_checkout_login_reminder', 'yes' );
update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'no' );
update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
update_option( 'woocommerce_registration_generate_username', 'yes' );
update_option( 'woocommerce_registration_generate_password', 'no' );

// Privacy Policy (ID=15) & Terms and Conditions (ID=16).
update_option( 'wp_page_for_privacy_policy', 15 );
update_option( 'woocommerce_terms_page_id', 16 );
update_option(
	'woocommerce_checkout_privacy_policy_text',
	'Thông tin cá nhân của bạn sẽ được sử dụng để xử lý đơn hàng, hỗ trợ trải nghiệm trên website và cho các mục đích cụ thể khác đã được mô tả trong [privacy_policy] của Jannie Nguyen Academy.'
);
update_option(
	'woocommerce_registration_privacy_policy_text',
	'Dữ liệu cá nhân của bạn sẽ được sử dụng để hỗ trợ trải nghiệm trên website, quản lý quyền truy cập tài khoản và cho các mục đích cụ thể khác được mô tả trong [privacy_policy] của chúng tôi.'
);
update_option(
	'woocommerce_checkout_terms_and_conditions_checkbox_text',
	'Tôi đã đọc và đồng ý với [terms] của website'
);

// Branded WooCommerce Emails.
update_option( 'woocommerce_email_from_name', 'Jannie Nguyen Academy' );
update_option( 'woocommerce_email_from_address', 'contact@jannienguyen.vn' );
update_option( 'woocommerce_email_header_image', home_url( '/app/themes/jannie/assets/img/jn-email-logo.svg' ) );
update_option( 'woocommerce_email_base_color', '#1F1E1D' );
update_option( 'woocommerce_email_background_color', '#FAF8F5' );
update_option( 'woocommerce_email_body_background_color', '#FFFFFF' );
update_option( 'woocommerce_email_text_color', '#1F1E1D' );
update_option(
	'woocommerce_email_footer_text',
	'© {site_title} — TP. Hồ Chí Minh, Việt Nam | Email: contact@jannienguyen.vn | Sứ mệnh của Jannie là tạo ra sự cân bằng và khám phá vẻ đẹp đích thực trong mỗi người.'
);

echo "=== 3. Creating Product Categories ===\n";
$categories_to_ensure = array(
	'coaching-mentorship' => array(
		'name'        => 'Coaching & Mentorship',
		'description' => 'Các gói tư vấn cá nhân 1:1 và chương trình đồng hành chuyên sâu cùng Jannie Nguyen.',
	),
	'khoa-hoc'            => array(
		'name'        => 'Khóa học',
		'description' => 'Các chương trình đào tạo và khóa học chuyên sâu của Jannie Nguyen Academy.',
	),
	'san-pham-yeu-thich'  => array(
		'name'        => 'Sản phẩm yêu thích',
		'description' => 'Những sản phẩm chăm sóc sức khỏe, sắc đẹp và phong cách sống được Jannie Nguyen tuyển chọn.',
	),
);

$category_ids = array();
foreach ( $categories_to_ensure as $slug => $cat_data ) {
	$existing = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $existing || is_wp_error( $existing ) ) {
		$inserted = wp_insert_term(
			$cat_data['name'],
			'product_cat',
			array(
				'slug'        => $slug,
				'description' => $cat_data['description'],
			)
		);
		if ( ! is_wp_error( $inserted ) ) {
			$category_ids[ $slug ] = (int) $inserted['term_id'];
			echo "Created product_cat '{$cat_data['name']}' (term_id={$category_ids[$slug]})\n";
		}
	} else {
		wp_update_term(
			(int) $existing->term_id,
			'product_cat',
			array(
				'name'        => $cat_data['name'],
				'description' => $cat_data['description'],
			)
		);
		$category_ids[ $slug ] = (int) $existing->term_id;
		echo "Updated product_cat '{$cat_data['name']}' (term_id={$category_ids[$slug]})\n";
	}
}

echo "=== 4. Creating / Updating 3 Coaching Package Virtual Products (Draft) ===\n";
if ( class_exists( 'JN_Coaching_Packages' ) ) {
	$pkg_ids = JN_Coaching_Packages::ensure_packages_exist();
	foreach ( $pkg_ids as $key => $pid ) {
		echo "Coaching package [{$key}] => Product ID {$pid} (status=" . get_post_status( $pid ) . ")\n";
	}
} else {
	echo "ERROR: JN_Coaching_Packages class not loaded!\n";
}

echo "=== 5. Configuring Store Pages (My Favorite Product, Cart, Checkout, My Account) ===\n";
$fav_cat_id = isset( $category_ids['san-pham-yeu-thich'] ) ? (int) $category_ids['san-pham-yeu-thich'] : 0;

// 5.1 My Favorite Product (ID=12, slug=my-favorite-product) = section-header pattern + WooCommerce Product Collection block.
$fav_page_content = '<!-- wp:group {"tagName":"section","align":"full","backgroundColor":"palette-color-7","className":"jn-section jn-page-intro-section"} -->
<section class="wp-block-group alignfull jn-section jn-page-intro-section has-palette-color-7-background-color has-background"><!-- wp:group {"className":"jn-container jn-section-header-centered"} -->
<div class="wp-block-group jn-container jn-section-header-centered"><!-- wp:paragraph {"align":"center","className":"is-style-eyebrow jn-eyebrow-wide"} -->
<p class="has-text-align-center is-style-eyebrow jn-eyebrow-wide">Danh Mục Tuyển Chọn</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":1,"className":"jn-page-intro-title"} -->
<h1 class="wp-block-heading has-text-align-center jn-page-intro-title">Sản Phẩm Yêu Thích <em class="jn-accent-italic">Của Jannie</em></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"jn-section-header-centered__desc","textColor":"muted-stone"} -->
<p class="has-text-align-center jn-section-header-centered__desc has-muted-stone-color has-text-color">Mỗi sản phẩm xuất hiện tại đây đều được Jannie trực tiếp trải nghiệm và kiểm chứng về chất lượng, độ an toàn cũng như giá trị bền vững cho sắc đẹp và sự cân bằng nội tại.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></section>
<!-- /wp:group -->

<!-- wp:woocommerce/product-collection {"queryId":1,"query":{"perPage":9,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":{"product_cat":[' . $fav_cat_id . ']},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false},"tagName":"div","displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/product-catalog","hideControls":["inherit","filterable"]} -->
<div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"single","isDescendentOfQueryLoop":true} -->
<!-- wp:woocommerce/product-sale-badge {"align":"right"} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:post-title {"textAlign":"left","level":2,"isLink":true,"fontSize":"headline-sm","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left"} /-->

<!-- wp:woocommerce/product-button {"textAlign":"center","isDescendentOfQueryLoop":true} /-->
<!-- /wp:woocommerce/product-template -->

<!-- wp:woocommerce/product-collection-no-results -->
<!-- wp:group {"layout":{"type":"constrained","contentSize":"680px"}} -->
<div class="wp-block-group"><!-- wp:heading {"textAlign":"center","level":2,"fontSize":"headline-md"} -->
<h2 class="wp-block-heading has-text-align-center has-headline-md-font-size">Danh Mục Đang Được Tuyển Chọn</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"muted-stone"} -->
<p class="has-text-align-center has-muted-stone-color has-text-color">Hiện tại các sản phẩm yêu thích đang được Jannie hoàn thiện khâu đánh giá chất lượng cuối cùng trước khi giới thiệu chính thức. Trong thời gian chờ đợi, bạn có thể khám phá các lộ trình Coaching &amp; Mentorship cá nhân hóa.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-jn-pill"} -->
<div class="wp-block-button is-style-jn-pill"><a class="wp-block-button__link wp-element-button" href="' . esc_url( home_url( '/coaching/' ) ) . '">Khám Phá Lộ Trình Coaching</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
<!-- /wp:woocommerce/product-collection-no-results --></div>
<!-- /wp:woocommerce/product-collection -->';

wp_update_post(
	array(
		'ID'           => 12,
		'post_title'   => 'My Favorite Product',
		'post_name'    => 'my-favorite-product',
		'post_status'  => 'publish',
		'post_content' => $fav_page_content,
	)
);

// 5.2 Cart page (ID=159, slug=cart) — WooCommerce Cart Block.
$cart_block_content = '<!-- wp:heading {"level":1,"fontSize":"headline-lg","style":{"spacing":{"margin":{"bottom":"1.5rem"}}}} -->
<h1 class="wp-block-heading has-headline-lg-font-size" style="margin-bottom:1.5rem">Giỏ Hàng Của Bạn</h1>
<!-- /wp:heading -->

<!-- wp:woocommerce/cart -->
<div class="wp-block-woocommerce-cart alignwide is-loading"><!-- wp:woocommerce/filled-cart-block -->
<div class="wp-block-woocommerce-filled-cart-block"><!-- wp:woocommerce/cart-items-block -->
<div class="wp-block-woocommerce-cart-items-block"><!-- wp:woocommerce/cart-line-items-block -->
<div class="wp-block-woocommerce-cart-line-items-block"></div>
<!-- /wp:woocommerce/cart-line-items-block --></div>
<!-- /wp:woocommerce/cart-items-block -->

<!-- wp:woocommerce/cart-totals-block -->
<div class="wp-block-woocommerce-cart-totals-block"><!-- wp:woocommerce/cart-order-summary-block -->
<div class="wp-block-woocommerce-cart-order-summary-block"><!-- wp:woocommerce/cart-order-summary-heading-block -->
<div class="wp-block-woocommerce-cart-order-summary-heading-block"></div>
<!-- /wp:woocommerce/cart-order-summary-heading-block -->

<!-- wp:woocommerce/cart-order-summary-coupon-form-block -->
<div class="wp-block-woocommerce-cart-order-summary-coupon-form-block"></div>
<!-- /wp:woocommerce/cart-order-summary-coupon-form-block -->

<!-- wp:woocommerce/cart-order-summary-subtotal-block -->
<div class="wp-block-woocommerce-cart-order-summary-subtotal-block"></div>
<!-- /wp:woocommerce/cart-order-summary-subtotal-block -->

<!-- wp:woocommerce/cart-order-summary-fee-block -->
<div class="wp-block-woocommerce-cart-order-summary-fee-block"></div>
<!-- /wp:woocommerce/cart-order-summary-fee-block -->

<!-- wp:woocommerce/cart-order-summary-discount-block -->
<div class="wp-block-woocommerce-cart-order-summary-discount-block"></div>
<!-- /wp:woocommerce/cart-order-summary-discount-block -->

<!-- wp:woocommerce/cart-order-summary-shipping-block -->
<div class="wp-block-woocommerce-cart-order-summary-shipping-block"></div>
<!-- /wp:woocommerce/cart-order-summary-shipping-block -->

<!-- wp:woocommerce/cart-order-summary-taxes-block -->
<div class="wp-block-woocommerce-cart-order-summary-taxes-block"></div>
<!-- /wp:woocommerce/cart-order-summary-taxes-block --></div>
<!-- /wp:woocommerce/cart-order-summary-block -->

<!-- wp:woocommerce/cart-express-payment-block -->
<div class="wp-block-woocommerce-cart-express-payment-block"></div>
<!-- /wp:woocommerce/cart-express-payment-block -->

<!-- wp:woocommerce/proceed-to-checkout-block -->
<div class="wp-block-woocommerce-proceed-to-checkout-block"></div>
<!-- /wp:woocommerce/proceed-to-checkout-block -->

<!-- wp:woocommerce/cart-accepted-payment-methods-block -->
<div class="wp-block-woocommerce-cart-accepted-payment-methods-block"></div>
<!-- /wp:woocommerce/cart-accepted-payment-methods-block --></div>
<!-- /wp:woocommerce/cart-totals-block --></div>
<!-- /wp:woocommerce/filled-cart-block -->

<!-- wp:woocommerce/empty-cart-block -->
<div class="wp-block-woocommerce-empty-cart-block"><!-- wp:heading {"textAlign":"center","className":"with-empty-cart-icon wc-block-cart__empty-cart__title"} -->
<h2 class="wp-block-heading has-text-align-center with-empty-cart-icon wc-block-cart__empty-cart__title">Giỏ hàng của bạn hiện đang trống</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><a href="' . esc_url( home_url( '/my-favorite-product/' ) ) . '">Khám phá Sản Phẩm Yêu Thích</a> hoặc <a href="' . esc_url( home_url( '/coaching/' ) ) . '">tìm hiểu các gói Coaching</a>.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:woocommerce/empty-cart-block --></div>
<!-- /wp:woocommerce/cart -->';

wp_update_post(
	array(
		'ID'           => 159,
		'post_title'   => 'Giỏ hàng',
		'post_name'    => 'cart',
		'post_status'  => 'publish',
		'post_content' => $cart_block_content,
	)
);

// 5.3 Checkout page (ID=160, slug=checkout) — WooCommerce Checkout Block with required Terms checkbox.
$checkout_block_content = '<!-- wp:heading {"level":1,"fontSize":"headline-lg","style":{"spacing":{"margin":{"bottom":"1.5rem"}}}} -->
<h1 class="wp-block-heading has-headline-lg-font-size" style="margin-bottom:1.5rem">Thanh Toán Đơn Hàng</h1>
<!-- /wp:heading -->

<!-- wp:woocommerce/checkout -->
<div class="wp-block-woocommerce-checkout alignwide wc-block-checkout is-loading"><!-- wp:woocommerce/checkout-fields-block -->
<div class="wp-block-woocommerce-checkout-fields-block"><!-- wp:woocommerce/checkout-express-payment-block -->
<div class="wp-block-woocommerce-checkout-express-payment-block"></div>
<!-- /wp:woocommerce/checkout-express-payment-block -->

<!-- wp:woocommerce/checkout-contact-information-block -->
<div class="wp-block-woocommerce-checkout-contact-information-block"></div>
<!-- /wp:woocommerce/checkout-contact-information-block -->

<!-- wp:woocommerce/checkout-shipping-method-block -->
<div class="wp-block-woocommerce-checkout-shipping-method-block"></div>
<!-- /wp:woocommerce/checkout-shipping-method-block -->

<!-- wp:woocommerce/checkout-pickup-options-block -->
<div class="wp-block-woocommerce-checkout-pickup-options-block"></div>
<!-- /wp:woocommerce/checkout-pickup-options-block -->

<!-- wp:woocommerce/checkout-shipping-address-block -->
<div class="wp-block-woocommerce-checkout-shipping-address-block"></div>
<!-- /wp:woocommerce/checkout-shipping-address-block -->

<!-- wp:woocommerce/checkout-billing-address-block -->
<div class="wp-block-woocommerce-checkout-billing-address-block"></div>
<!-- /wp:woocommerce/checkout-billing-address-block -->

<!-- wp:woocommerce/checkout-shipping-methods-block -->
<div class="wp-block-woocommerce-checkout-shipping-methods-block"></div>
<!-- /wp:woocommerce/checkout-shipping-methods-block -->

<!-- wp:woocommerce/checkout-payment-block -->
<div class="wp-block-woocommerce-checkout-payment-block"></div>
<!-- /wp:woocommerce/checkout-payment-block -->

<!-- wp:woocommerce/checkout-additional-information-block -->
<div class="wp-block-woocommerce-checkout-additional-information-block"></div>
<!-- /wp:woocommerce/checkout-additional-information-block -->

<!-- wp:woocommerce/checkout-order-note-block -->
<div class="wp-block-woocommerce-checkout-order-note-block"></div>
<!-- /wp:woocommerce/checkout-order-note-block -->

<!-- wp:woocommerce/checkout-terms-block {"checkbox":true} -->
<div class="wp-block-woocommerce-checkout-terms-block"></div>
<!-- /wp:woocommerce/checkout-terms-block -->

<!-- wp:woocommerce/checkout-actions-block -->
<div class="wp-block-woocommerce-checkout-actions-block"></div>
<!-- /wp:woocommerce/checkout-actions-block --></div>
<!-- /wp:woocommerce/checkout-fields-block -->

<!-- wp:woocommerce/checkout-totals-block -->
<div class="wp-block-woocommerce-checkout-totals-block"><!-- wp:woocommerce/checkout-order-summary-block -->
<div class="wp-block-woocommerce-checkout-order-summary-block"><!-- wp:woocommerce/checkout-order-summary-cart-items-block -->
<div class="wp-block-woocommerce-checkout-order-summary-cart-items-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-cart-items-block -->

<!-- wp:woocommerce/checkout-order-summary-coupon-form-block -->
<div class="wp-block-woocommerce-checkout-order-summary-coupon-form-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-coupon-form-block -->

<!-- wp:woocommerce/checkout-order-summary-subtotal-block -->
<div class="wp-block-woocommerce-checkout-order-summary-subtotal-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-subtotal-block -->

<!-- wp:woocommerce/checkout-order-summary-fee-block -->
<div class="wp-block-woocommerce-checkout-order-summary-fee-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-fee-block -->

<!-- wp:woocommerce/checkout-order-summary-discount-block -->
<div class="wp-block-woocommerce-checkout-order-summary-discount-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-discount-block -->

<!-- wp:woocommerce/checkout-order-summary-shipping-block -->
<div class="wp-block-woocommerce-checkout-order-summary-shipping-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-shipping-block -->

<!-- wp:woocommerce/checkout-order-summary-taxes-block -->
<div class="wp-block-woocommerce-checkout-order-summary-taxes-block"></div>
<!-- /wp:woocommerce/checkout-order-summary-taxes-block --></div>
<!-- /wp:woocommerce/checkout-order-summary-block --></div>
<!-- /wp:woocommerce/checkout-totals-block --></div>
<!-- /wp:woocommerce/checkout -->';

wp_update_post(
	array(
		'ID'           => 160,
		'post_title'   => 'Thanh toán',
		'post_name'    => 'checkout',
		'post_status'  => 'publish',
		'post_content' => $checkout_block_content,
	)
);

// 5.4 My Account page (ID=14, slug=tai-khoan).
wp_update_post(
	array(
		'ID'           => 14,
		'post_title'   => 'Tài khoản',
		'post_name'    => 'tai-khoan',
		'post_status'  => 'publish',
		'post_content' => '<!-- wp:shortcode -->[woocommerce_my_account]<!-- /wp:shortcode -->',
	)
);

// Link WooCommerce page IDs.
update_option( 'woocommerce_shop_page_id', 12 );
update_option( 'woocommerce_cart_page_id', 159 );
update_option( 'woocommerce_checkout_page_id', 160 );
update_option( 'woocommerce_myaccount_page_id', 14 );

// Set Blocksy clean full-width layout meta on commerce pages (12, 14, 159, 160).
$blocksy_commerce_meta = array(
	'page_title_panel'            => 'disabled',
	'page_structure_type'         => 'type-4',
	'content_style_source'        => 'custom',
	'content_style'               => 'unboxed',
	'vertical_spacing_source'     => 'custom',
	'content_area_spacing'        => 'none',
	'has_Export_Block_Editor_Css' => true,
);
foreach ( array( 12, 14, 159, 160 ) as $commerce_page_id ) {
	update_post_meta( $commerce_page_id, 'blocksy_post_meta_options', $blocksy_commerce_meta );
}

// Delete redundant auto-created WooCommerce pages (158=Shop, 161=My account, 162=Refund policy draft).
foreach ( array( 158, 161, 162 ) as $redundant_page_id ) {
	$redundant_post = get_post( $redundant_page_id );
	if ( $redundant_post && 'page' === $redundant_post->post_type ) {
		wp_delete_post( $redundant_page_id, true );
		echo "Deleted redundant page ID={$redundant_page_id} ({$redundant_post->post_name})\n";
	}
}

echo "=== 6. Configuring Payment Gateways (BACS + COD for Physical Only) ===\n";
update_option(
	'woocommerce_bacs_settings',
	array(
		'enabled'      => 'yes',
		'title'        => 'Chuyển khoản ngân hàng (VietQR / 24/7)',
		'description'  => 'Thực hiện thanh toán chuyển khoản trực tiếp vào tài khoản ngân hàng của Jannie Nguyen Academy. Vui lòng ghi rõ Mã đơn hàng trong nội dung chuyển khoản.',
		'instructions' => 'Quý khách vui lòng chuyển khoản theo thông tin tài khoản bên dưới với nội dung: [Mã đơn hàng] - [Số điện thoại]. Đơn hàng sẽ được xác nhận ngay sau khi hệ thống ghi nhận giao dịch.',
	)
);

update_option(
	'woocommerce_bacs_accounts',
	array(
		array(
			'account_name'   => 'JANNIE NGUYEN ACADEMY (PLACEHOLDER - CHƯA DÙNG THẬT)',
			'account_number' => '0000000000 (PLACEHOLDER)',
			'bank_name'      => 'Ngân hàng TMCP Ngoại thương Việt Nam - Vietcombank (PLACEHOLDER)',
			'sort_code'      => 'Chi nhánh TP. Hồ Chí Minh',
			'iban'           => '',
			'bic'            => '',
		),
	)
);

update_option(
	'woocommerce_cod_settings',
	array(
		'enabled'            => 'yes',
		'title'              => 'Thanh toán khi nhận hàng (COD)',
		'description'        => 'Thanh toán bằng tiền mặt khi nhận sản phẩm vật lý tại địa chỉ giao hàng.',
		'instructions'       => 'Thanh toán bằng tiền mặt khi nhận hàng.',
		'enable_for_methods' => array(),
		'enable_for_virtual' => 'no',
	)
);

flush_rewrite_rules();
echo "=== Step 4 WooCommerce Setup Complete! ===\n";
