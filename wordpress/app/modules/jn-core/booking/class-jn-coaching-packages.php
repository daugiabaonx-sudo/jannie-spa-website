<?php
/**
 * Coaching Packages & Virtual Checkout Rules for Jannie Nguyen Academy.
 *
 * - Registers the 3 canonical virtual coaching packages in category "Coaching & Mentorship".
 * - Enforces catalog invisibility and blocks direct front-end add-to-cart for packages.
 * - Allows pending package orders created by admin/assistant to be paid via their order-pay link.
 * - Simplifies Checkout Block and classic checkout fields for virtual-only carts (Name, Phone, Email + Terms).
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Coaching_Packages
 */
class JN_Coaching_Packages {

	/**
	 * Category slug for coaching packages.
	 */
	const CATEGORY_SLUG = 'coaching-mentorship';

	/**
	 * Category name for coaching packages.
	 */
	const CATEGORY_NAME = 'Coaching & Mentorship';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Prevent direct front-end add-to-cart for coaching packages while allowing order-pay.
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'block_direct_package_add_to_cart' ), 10, 2 );
		add_filter( 'woocommerce_is_purchasable', array( __CLASS__, 'filter_package_purchasable' ), 20, 2 );
		add_filter( 'woocommerce_pay_order_product_in_stock', array( __CLASS__, 'allow_package_in_order_pay' ), 20, 3 );
		add_filter( 'woocommerce_order_item_visible', array( __CLASS__, 'ensure_package_order_item_visible' ), 20, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_package_single_product_page' ), 10 );

		// Virtual-only cart checkout simplification for WooCommerce Checkout Block & Store API.
		add_filter( 'woocommerce_default_address_fields', array( __CLASS__, 'filter_virtual_default_address_fields' ), 99, 1 );
		add_filter( 'woocommerce_get_country_locale', array( __CLASS__, 'filter_virtual_country_locale' ), 99, 1 );
		add_filter( 'woocommerce_get_country_locale_default', array( __CLASS__, 'filter_virtual_default_address_fields' ), 99, 1 );
		add_filter( 'woocommerce_get_country_locale_base', array( __CLASS__, 'filter_virtual_default_address_fields' ), 99, 1 );
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'filter_classic_virtual_checkout_fields' ), 99, 1 );
		add_action( 'woocommerce_cart_loaded_from_session', array( __CLASS__, 'reset_countries_locale_cache' ), 10 );

		// Ensure COD is never offered for virtual-only carts or virtual order-pay orders.
		add_filter( 'woocommerce_available_payment_gateways', array( __CLASS__, 'filter_available_payment_gateways' ), 20, 1 );

		// Allow direct payment via valid order-pay key for pending coaching package orders.
		add_filter( 'woocommerce_order_email_verification_required', array( __CLASS__, 'allow_package_order_pay_without_email_prompt' ), 20, 3 );
	}

	/**
	 * Idempotently create or update the 3 canonical coaching package virtual products.
	 *
	 * Keeps status as `draft` when price is empty (`UNKNOWN` in Project Context Card).
	 *
	 * @return array<string, int> Map of package key => product ID.
	 */
	public static function ensure_packages_exist(): array {
		if ( ! class_exists( 'WC_Product_Simple' ) ) {
			return array();
		}

		$cat_term = get_term_by( 'slug', self::CATEGORY_SLUG, 'product_cat' );
		if ( ! $cat_term || is_wp_error( $cat_term ) ) {
			$inserted = wp_insert_term(
				self::CATEGORY_NAME,
				'product_cat',
				array(
					'slug' => self::CATEGORY_SLUG,
				)
			);
			$cat_id   = ! is_wp_error( $inserted ) ? (int) $inserted['term_id'] : 0;
		} else {
			$cat_id = (int) $cat_term->term_id;
		}

		$definitions = self::get_package_definitions();
		$result_ids  = array();

		foreach ( $definitions as $key => $pkg ) {
			$existing = self::get_product_by_key( $key );
			if ( ! $existing ) {
				$existing_id = wc_get_product_id_by_sku( $pkg['sku'] );
				if ( $existing_id > 0 ) {
					$existing = wc_get_product( $existing_id );
				}
			}

			$product = ( $existing instanceof \WC_Product_Simple ) ? $existing : new \WC_Product_Simple();
			$product->set_name( $pkg['title'] );
			$product->set_slug( 'goi-coaching-' . $key );
			$product->set_sku( $pkg['sku'] );
			$product->set_status( '' === (string) $pkg['price'] ? 'draft' : 'publish' );
			$product->set_catalog_visibility( 'hidden' );
			$product->set_virtual( true );
			$product->set_downloadable( false );
			$product->set_sold_individually( true );
			$product->set_reviews_allowed( false );
			$product->set_short_description( $pkg['short_description'] );
			$product->set_description( $pkg['description'] );
			$product->set_regular_price( (string) $pkg['price'] );

			if ( $cat_id > 0 ) {
				$product->set_category_ids( array( $cat_id ) );
			}

			$product->update_meta_data( '_jn_package_key', $key );
			$product->update_meta_data( '_jn_bookable', $pkg['bookable'] );
			$product->update_meta_data( '_jn_duration', $pkg['duration'] );
			$product->update_meta_data( '_jn_sessions', $pkg['sessions'] );
			$product->update_meta_data( '_jn_formats', $pkg['formats'] );
			$product->update_meta_data( '_jn_form_label', $pkg['form_label'] );

			$product->update_meta_data( 'jn_package_key', $key );
			$product->update_meta_data( 'jn_bookable', $pkg['bookable'] );
			$product->update_meta_data( 'jn_duration', $pkg['duration'] );
			$product->update_meta_data( 'jn_sessions', $pkg['sessions'] );
			$product->update_meta_data( 'jn_formats', $pkg['formats'] );

			$product_id         = $product->save();
			$result_ids[ $key ] = (int) $product_id;
		}

		return $result_ids;
	}

	/**
	 * Allow a guest customer with a valid cryptographic order key (`key=wc_order_...`)
	 * to pay for an admin-created pending order via its `order-pay` link without an extra barrier.
	 *
	 * @param bool      $required Whether email verification is required.
	 * @param \WC_Order $order    Order object.
	 * @param string    $context  Context ('order-pay' or 'order-received').
	 * @return bool
	 */
	public static function allow_package_order_pay_without_email_prompt( bool $required, $order, string $context ): bool {
		if ( 'order-pay' === $context && $order instanceof \WC_Order && $order->needs_payment() ) {
			return false;
		}
		return $required;
	}

	/**
	 * Return the canonical definitions for the 3 coaching packages verbatim from the wireframe.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_package_definitions(): array {
		return array(
			'30-phut'            => array(
				'key'               => '30-phut',
				'sku'               => 'jn-coaching-30m',
				'title'             => 'Gói Tư Vấn Cá Nhân 1:1 (30 Phút)',
				'form_label'        => 'Gói Tư Vấn Cá Nhân 1:1 (30 Phút) — Định hình vẻ đẹp riêng',
				'chip'              => 'Cá Nhân',
				'duration_label'    => 'Thời lượng: 30 Phút',
				'card_heading'      => 'Tư Vấn Cá Nhân 1:1',
				'subtitle'          => 'Định hình vẻ đẹp & phong cách riêng',
				'short_description' => 'Định hình vẻ đẹp & phong cách riêng. Dành cho khách hàng mong muốn khám phá đường nét tự nhiên, thấu hiểu làn da và nhận định hướng chăm sóc, thẩm mỹ tinh tế nhất từ Jannie Nguyễn.',
				'description'       => "<p><strong>Định hình vẻ đẹp &amp; phong cách riêng</strong></p>\n<p>Dành cho khách hàng mong muốn khám phá đường nét tự nhiên, thấu hiểu làn da và nhận định hướng chăm sóc, thẩm mỹ tinh tế nhất từ Jannie Nguyễn.</p>\n<ul>\n<li>Phân tích cấu trúc khuôn mặt và làn da</li>\n<li>Chiến lược làm đẹp tự nhiên, không lạm dụng hóa chất</li>\n<li>Khuyến nghị sản phẩm và liệu trình phù hợp</li>\n<li>Giải đáp trực tiếp 1:1 các thắc mắc làm đẹp</li>\n</ul>",
				'bookable'          => 'yes',
				'duration'          => '30',
				'sessions'          => 1,
				'formats'           => array( 'online', 'in-person' ),
				'price'             => '', // UNKNOWN in PROJECT CONTEXT CARD -> kept as Draft.
			),
			'60-phut'            => array(
				'key'               => '60-phut',
				'sku'               => 'jn-coaching-60m',
				'title'             => 'Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)',
				'form_label'        => 'Gói Coaching Chuyên Nghiệp 1:1 (60 Phút) — Dành cho thợ & chủ Spa',
				'chip'              => 'Chuyên Nghiệp',
				'duration_label'    => 'Thời lượng: 60 Phút',
				'card_heading'      => 'Coaching Chuyên Nghiệp 1:1',
				'subtitle'          => 'Tăng tốc kỹ thuật & Chiến lược Spa / Phun xăm',
				'short_description' => 'Tăng tốc kỹ thuật & Chiến lược Spa / Phun xăm. Dành cho thợ làm nghề, học viên nâng cao và chủ Salon/Spa cần giải quyết trực tiếp vướng mắc kỹ thuật, chốt sale dịch vụ cao cấp và giữ chân khách hàng.',
				'description'       => "<p><strong>Tăng tốc kỹ thuật &amp; Chiến lược Spa / Phun xăm</strong></p>\n<p>Dành cho thợ làm nghề, học viên nâng cao và chủ Salon/Spa cần giải quyết trực tiếp vướng mắc kỹ thuật, chốt sale dịch vụ cao cấp và giữ chân khách hàng.</p>\n<ul>\n<li>Đánh giá kỹ thuật phun xăm / thẩm mỹ hiện tại</li>\n<li>Tối ưu hoá quy trình tư vấn và thiết lập bảng giá</li>\n<li>Chiến lược xây dựng tệp khách VIP trung thành</li>\n<li>Bản ghi hình buổi coaching &amp; Action Plan chi tiết</li>\n</ul>",
				'bookable'          => 'yes',
				'duration'          => '60',
				'sessions'          => 1,
				'formats'           => array( 'online', 'in-person' ),
				'price'             => '', // UNKNOWN in PROJECT CONTEXT CARD -> kept as Draft.
			),
			'mentorship-3-thang' => array(
				'key'               => 'mentorship-3-thang',
				'sku'               => 'jn-mentorship-3m',
				'title'             => 'Gói Master Mentorship (3 Tháng)',
				'form_label'        => 'Gói Master Mentorship (Lộ trình 3 tháng toàn diện)',
				'chip'              => 'Master Class',
				'duration_label'    => 'Lộ trình: 3 Tháng Toàn Diện',
				'card_heading'      => 'Master Mentorship 3 Tháng',
				'subtitle'          => 'Đồng hành chuyển giao & Nhân bản thương hiệu',
				'short_description' => 'Đồng hành chuyển giao & Nhân bản thương hiệu. Chương trình cố vấn dài hạn toàn diện nhất cùng Jannie Nguyễn, trực tiếp tái cấu trúc kỹ thuật chuyên môn, đào tạo đội ngũ và nâng tầm vị thế cơ sở thẩm mỹ.',
				'description'       => "<p><strong>Đồng hành chuyển giao &amp; Nhân bản thương hiệu</strong></p>\n<p>Chương trình cố vấn dài hạn toàn diện nhất cùng Jannie Nguyễn, trực tiếp tái cấu trúc kỹ thuật chuyên môn, đào tạo đội ngũ và nâng tầm vị thế cơ sở thẩm mỹ.</p>\n<ul>\n<li>12 buổi coaching chuyên sâu 1:1 trực tiếp/online</li>\n<li>Kèm cặp kỹ thuật thực chiến trên người thật</li>\n<li>Cố vấn giải pháp chiến lược truyền thông nhân hiệu</li>\n<li>Hỗ trợ khẩn cấp 24/7 trong suốt 90 ngày</li>\n</ul>",
				'bookable'          => 'yes',
				'duration'          => '–',
				'sessions'          => 12,
				'formats'           => array( 'online', 'in-person' ),
				'price'             => '', // UNKNOWN in PROJECT CONTEXT CARD -> kept as Draft.
			),
		);
	}

	/**
	 * Check whether a product ID or WC_Product is a coaching package.
	 *
	 * @param int|\WC_Product $product Product ID or instance.
	 * @return bool
	 */
	public static function is_coaching_package( $product ): bool {
		$product_obj = is_numeric( $product ) ? wc_get_product( (int) $product ) : $product;
		if ( ! $product_obj instanceof \WC_Product ) {
			return false;
		}

		$bookable = $product_obj->get_meta( '_jn_bookable', true );
		if ( '' === $bookable ) {
			$bookable = $product_obj->get_meta( 'jn_bookable', true );
		}

		return 'yes' === $bookable || '1' === (string) $bookable;
	}

	/**
	 * Find a coaching package product by its stable `?goi=` key or exact option title.
	 *
	 * @param string $key_or_title Stable key (`30-phut`, `60-phut`, `mentorship-3-thang`) or exact title.
	 * @return \WC_Product|null
	 */
	public static function get_product_by_key( string $key_or_title ) {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return null;
		}

		$clean_key = sanitize_key( $key_or_title );
		$products  = wc_get_products(
			array(
				'status'     => array( 'publish', 'draft', 'private' ),
				'limit'      => 10,
				'meta_key'   => '_jn_bookable', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => 'yes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		foreach ( $products as $product ) {
			$pkg_key    = (string) $product->get_meta( '_jn_package_key', true );
			$form_label = (string) $product->get_meta( '_jn_form_label', true );
			if (
				$pkg_key === $clean_key ||
				$product->get_name() === $key_or_title ||
				$form_label === $key_or_title ||
				( is_numeric( $key_or_title ) && (int) $product->get_id() === (int) $key_or_title )
			) {
				return $product;
			}
		}

		return null;
	}

	/**
	 * Return ordered array of bookable WooCommerce package options for the booking form.
	 *
	 * Default order places the 60-minute package first per wireframe specification.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_bookable_packages_for_form(): array {
		$defs     = self::get_package_definitions();
		$order    = array( '60-phut', '30-phut', 'mentorship-3-thang' );
		$packages = array();

		if ( function_exists( 'wc_get_products' ) ) {
			$products = wc_get_products(
				array(
					'status'     => array( 'publish', 'draft', 'private' ),
					'limit'      => 20,
					'meta_key'   => '_jn_bookable', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => 'yes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			);

			$by_key = array();
			foreach ( $products as $product ) {
				if ( ! $product instanceof \WC_Product ) {
					continue;
				}
				$key = (string) $product->get_meta( '_jn_package_key', true );
				if ( '' === $key ) {
					$key = sanitize_key( $product->get_slug() );
				}
				$def_label  = isset( $defs[ $key ]['form_label'] ) ? $defs[ $key ]['form_label'] : $product->get_name();
				$form_label = (string) $product->get_meta( '_jn_form_label', true );
				if ( '' === $form_label ) {
					$form_label = $def_label;
				}
				$raw_duration = (string) $product->get_meta( '_jn_duration', true );
				$duration_min = is_numeric( $raw_duration ) ? max( 15, (int) $raw_duration ) : 60;
				$sessions     = max( 1, (int) $product->get_meta( '_jn_sessions', true ) );

				$by_key[ $key ] = array(
					'product_id'   => (int) $product->get_id(),
					'key'          => $key,
					'value'        => $product->get_name(),
					'label'        => $form_label,
					'duration_min' => $duration_min,
					'sessions'     => $sessions,
					'price'        => $product->get_regular_price(),
				);
			}

			foreach ( $order as $pref_key ) {
				if ( isset( $by_key[ $pref_key ] ) ) {
					$packages[ $pref_key ] = $by_key[ $pref_key ];
					unset( $by_key[ $pref_key ] );
				}
			}
			foreach ( $by_key as $extra_key => $extra_pkg ) {
				$packages[ $extra_key ] = $extra_pkg;
			}
		}

		// Fallback to canonical definitions if products are not yet seeded.
		if ( empty( $packages ) ) {
			foreach ( $order as $pref_key ) {
				if ( isset( $defs[ $pref_key ] ) ) {
					$d                     = $defs[ $pref_key ];
					$packages[ $pref_key ] = array(
						'product_id'   => 0,
						'key'          => $pref_key,
						'value'        => $d['title'],
						'label'        => $d['form_label'],
						'duration_min' => is_numeric( $d['duration'] ) ? (int) $d['duration'] : 60,
						'sessions'     => (int) $d['sessions'],
						'price'        => (string) $d['price'],
					);
				}
			}
		}

		return $packages;
	}

	/**
	 * Determine whether the current request is handling payment for an existing order (`order-pay`).
	 *
	 * @return bool
	 */
	public static function is_order_pay_context(): bool {
		if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return true;
		}

		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
		if ( isset( $_GET['pay_for_order'], $_GET['key'] ) || isset( $_POST['woocommerce_pay'], $_GET['key'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Block direct front-end add-to-cart requests for coaching packages.
	 *
	 * @param bool $passed     Current validation status.
	 * @param int  $product_id Product ID being added to cart.
	 * @return bool
	 */
	public static function block_direct_package_add_to_cart( bool $passed, int $product_id ): bool {
		if ( self::is_coaching_package( $product_id ) ) {
			wc_add_notice(
				'Gói Coaching & Mentorship chỉ tiếp nhận qua biểu mẫu đăng ký tư vấn 1:1. Vui lòng gửi yêu cầu xếp lịch để trợ lý xác nhận khung giờ và gửi liên kết thanh toán.',
				'error'
			);
			return false;
		}

		return $passed;
	}

	/**
	 * Filter `woocommerce_is_purchasable`:
	 * - False in normal front-end catalog/cart contexts so coaching packages cannot be added to cart.
	 * - True in `order-pay` and admin contexts so pending package orders remain payable even when Draft.
	 *
	 * @param bool        $purchasable Whether the product is purchasable.
	 * @param \WC_Product $product     Product object.
	 * @return bool
	 */
	public static function filter_package_purchasable( bool $purchasable, $product ): bool {
		if ( ! self::is_coaching_package( $product ) ) {
			return $purchasable;
		}

		if ( self::is_order_pay_context() ) {
			return true;
		}

		return false;
	}

	/**
	 * Ensure coaching packages inside an order are always considered in stock on `order-pay`.
	 *
	 * @param bool        $in_stock Whether product is in stock.
	 * @param \WC_Product $product  Product instance.
	 * @param \WC_Order   $order    Order instance.
	 * @return bool
	 */
	public static function allow_package_in_order_pay( bool $in_stock, $product, $order ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( self::is_coaching_package( $product ) ) {
			return true;
		}
		return $in_stock;
	}

	/**
	 * Ensure coaching package line items remain visible on `order-pay` and `order-received` even when Draft.
	 *
	 * @param bool            $visible Whether item is visible.
	 * @param \WC_Order_Item  $item    Order item instance.
	 * @return bool
	 */
	public static function ensure_package_order_item_visible( bool $visible, $item ): bool {
		if ( is_object( $item ) && method_exists( $item, 'get_product_id' ) ) {
			$product_id = (int) $item->get_product_id();
			if ( $product_id > 0 && self::is_coaching_package( $product_id ) ) {
				return true;
			}
		}
		return $visible;
	}

	/**
	 * Redirect direct visits to a coaching package single product page to `/coaching/#coaching-tiers`.
	 *
	 * @return void
	 */
	public static function redirect_package_single_product_page(): void {
		if ( is_admin() ) {
			return;
		}

		$product_id = 0;
		if ( function_exists( 'is_product' ) && is_product() ) {
			$product_id = get_queried_object_id();
		} elseif ( isset( $_GET['p'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$product_id = absint( $_GET['p'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( get_query_var( 'product' ) ) {
			$slug = sanitize_title( (string) get_query_var( 'product' ) );
			if ( 0 === strpos( $slug, 'goi-coaching-' ) ) {
				wp_safe_redirect( home_url( '/coaching/#coaching-tiers' ), 302 );
				exit;
			}
		}

		if ( $product_id > 0 && self::is_coaching_package( $product_id ) ) {
			wp_safe_redirect( home_url( '/coaching/#coaching-tiers' ), 302 );
			exit;
		}
	}

	/**
	 * Check whether the current cart contains only virtual products (does not require shipping).
	 *
	 * @return bool
	 */
	public static function is_virtual_only_cart(): bool {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return true;
		}

		if ( WC()->cart->is_empty() ) {
			return true;
		}

		return ! WC()->cart->needs_shipping();
	}

	/**
	 * Clear cached locale array on `WC_Countries` once the cart session is loaded
	 * so virtual vs. physical cart field rules are evaluated accurately.
	 *
	 * @return void
	 */
	public static function reset_countries_locale_cache(): void {
		if ( function_exists( 'WC' ) && isset( WC()->countries ) && is_object( WC()->countries ) ) {
			WC()->countries->locale = array();
		}
	}

	/**
	 * Simplify default address fields for virtual-only carts (Name, Phone, Email + Terms).
	 *
	 * Confirmed in `Automattic\WooCommerce\StoreApi\Utilities\OrderController::validate_address_fields`
	 * and `Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::get_country_data`:
	 * setting `required => false` and `hidden => true` removes physical address fields from
	 * the React Checkout Block and skips Store API address validation for those fields.
	 *
	 * @param array<string, array<string, mixed>> $fields Default address fields.
	 * @return array<string, array<string, mixed>>
	 */
	public static function filter_virtual_default_address_fields( array $fields ): array {
		if ( ! self::is_virtual_only_cart() ) {
			return $fields;
		}

		$hidden_fields = array( 'company', 'address_1', 'address_2', 'city', 'state', 'postcode' );
		foreach ( $hidden_fields as $field_key ) {
			if ( isset( $fields[ $field_key ] ) ) {
				$fields[ $field_key ]['required'] = false;
				$fields[ $field_key ]['hidden']   = true;
			} else {
				$fields[ $field_key ] = array(
					'required' => false,
					'hidden'   => true,
				);
			}
		}

		if ( isset( $fields['first_name'] ) ) {
			$fields['first_name']['label']    = 'Họ và tên';
			$fields['first_name']['required'] = true;
			$fields['first_name']['hidden']   = false;
		}

		if ( isset( $fields['last_name'] ) ) {
			$fields['last_name']['label']    = 'Họ / Tên đệm';
			$fields['last_name']['required'] = false;
			$fields['last_name']['hidden']   = true;
		}

		if ( isset( $fields['phone'] ) ) {
			$fields['phone']['label']    = 'Số điện thoại / Zalo';
			$fields['phone']['required'] = true;
			$fields['phone']['hidden']   = false;
		} else {
			$fields['phone'] = array(
				'label'        => 'Số điện thoại / Zalo',
				'required'     => true,
				'hidden'       => false,
				'type'         => 'tel',
				'autocomplete' => 'tel',
				'priority'     => 100,
			);
		}

		return $fields;
	}

	/**
	 * Apply virtual-only field visibility overrides to the `VN` country locale used by Checkout Block.
	 *
	 * @param array<string, array<string, mixed>> $locales Country locales keyed by ISO country code.
	 * @return array<string, array<string, mixed>>
	 */
	public static function filter_virtual_country_locale( array $locales ): array {
		if ( ! self::is_virtual_only_cart() ) {
			return $locales;
		}

		if ( ! isset( $locales['VN'] ) || ! is_array( $locales['VN'] ) ) {
			$locales['VN'] = array();
		}

		$locales['VN']['first_name'] = array(
			'label'    => 'Họ và tên',
			'required' => true,
			'hidden'   => false,
		);
		$locales['VN']['last_name']  = array(
			'label'    => 'Họ / Tên đệm',
			'required' => false,
			'hidden'   => true,
		);
		$locales['VN']['phone']      = array(
			'label'    => 'Số điện thoại / Zalo',
			'required' => true,
			'hidden'   => false,
		);

		$hidden_fields = array( 'company', 'address_1', 'address_2', 'city', 'state', 'postcode' );
		foreach ( $hidden_fields as $field_key ) {
			$locales['VN'][ $field_key ] = array(
				'required' => false,
				'hidden'   => true,
			);
		}

		return $locales;
	}

	/**
	 * Also simplify classic checkout fields if classic checkout fallback is ever triggered.
	 *
	 * @param array<string, array<string, mixed>> $fields Checkout fields.
	 * @return array<string, array<string, mixed>>
	 */
	public static function filter_classic_virtual_checkout_fields( array $fields ): array {
		if ( ! self::is_virtual_only_cart() ) {
			return $fields;
		}

		$remove_billing = array(
			'billing_last_name',
			'billing_company',
			'billing_country',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_postcode',
		);

		foreach ( $remove_billing as $key ) {
			unset( $fields['billing'][ $key ] );
		}

		if ( isset( $fields['billing']['billing_first_name'] ) ) {
			$fields['billing']['billing_first_name']['label']    = 'Họ và tên';
			$fields['billing']['billing_first_name']['required'] = true;
			$fields['billing']['billing_first_name']['class']    = array( 'form-row-wide' );
		}

		if ( isset( $fields['billing']['billing_phone'] ) ) {
			$fields['billing']['billing_phone']['label']    = 'Số điện thoại / Zalo';
			$fields['billing']['billing_phone']['required'] = true;
		}

		return $fields;
	}

	/**
	 * Ensure Cash on Delivery (`cod`) is only available when an order/cart requires physical shipping.
	 *
	 * @param array<string, \WC_Payment_Gateway> $gateways Available payment gateways.
	 * @return array<string, \WC_Payment_Gateway>
	 */
	public static function filter_available_payment_gateways( array $gateways ): array {
		if ( ! isset( $gateways['cod'] ) ) {
			return $gateways;
		}

		// Check order-pay endpoint first.
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
			$order_id = absint( get_query_var( 'order-pay' ) );
			$order    = $order_id > 0 ? wc_get_order( $order_id ) : false;
			if ( $order instanceof \WC_Order && ! $order->needs_shipping_address() ) {
				unset( $gateways['cod'] );
				return $gateways;
			}
		}

		if ( self::is_virtual_only_cart() ) {
			unset( $gateways['cod'] );
		}

		return $gateways;
	}
}
