<?php
/**
 * JN Step 4 — E2E Helper for Demo Product & Admin-Created Pending Coaching Package Order.
 *
 * Usage:
 *   ./scripts/wp.sh eval '$action="prepare"; require "/scripts/step-4-e2e-helper.php";'
 *   ./scripts/wp.sh eval '$action="cleanup"; require "/scripts/step-4-e2e-helper.php";'
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$action = isset( $action ) ? (string) $action : 'prepare';

if ( 'prepare' === $action ) {
	// 0. Remove previous E2E test orders so HPOS starts clean for the verification run.
	$old_orders = wc_get_orders(
		array(
			'limit'  => -1,
			'return' => 'ids',
		)
	);
	foreach ( $old_orders as $old_order_id ) {
		$ord = wc_get_order( $old_order_id );
		if ( $ord ) {
			$ord->delete( true );
		}
	}

	// 1. Delete any stale DEMO product first.
	$existing_demos = get_posts(
		array(
			'post_type'   => 'product',
			'post_status' => 'any',
			's'           => 'DEMO – xóa trước khi chạy thật',
			'fields'      => 'ids',
		)
	);
	foreach ( $existing_demos as $old_id ) {
		wp_delete_post( (int) $old_id, true );
	}

	// 2. Find an existing media library image for visual card testing.
	$media_map = get_option( 'jn_step3_media_map', array() );
	$image_id  = 0;
	if ( is_array( $media_map ) && ! empty( $media_map ) ) {
		$image_id = (int) reset( $media_map );
	}
	if ( ! $image_id ) {
		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		if ( ! empty( $attachments ) ) {
			$image_id = (int) $attachments[0];
		}
	}

	// 3. Create "DEMO – xóa trước khi chạy thật" in "Sản phẩm yêu thích".
	$fav_term = get_term_by( 'slug', 'san-pham-yeu-thich', 'product_cat' );
	$fav_id   = ( $fav_term && ! is_wp_error( $fav_term ) ) ? (int) $fav_term->term_id : 0;

	$demo = new WC_Product_Simple();
	$demo->set_name( 'DEMO – xóa trước khi chạy thật' );
	$demo->set_slug( 'demo-xoa-truoc-khi-chay-that' );
	$demo->set_sku( 'jn-demo-temp-product' );
	$demo->set_status( 'publish' );
	$demo->set_catalog_visibility( 'visible' );
	$demo->set_virtual( true );
	$demo->set_regular_price( '450000' );
	$demo->set_short_description( 'Sản phẩm mẫu tạm thời phục vụ kiểm thử giao diện và luồng đặt hàng WooCommerce Step 4 (sẽ xóa ngay sau khi kiểm thử).' );
	$demo->set_description( '<p>Sản phẩm mẫu tạm thời phục vụ kiểm thử giao diện và luồng đặt hàng WooCommerce Step 4.</p>' );
	if ( $fav_id > 0 ) {
		$demo->set_category_ids( array( $fav_id ) );
	}
	if ( $image_id > 0 ) {
		$demo->set_image_id( $image_id );
	}
	$demo_id = $demo->save();

	// 4. Create an admin-created pending order containing a Draft Coaching Package ("Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)").
	$pkg_product = JN_Coaching_Packages::get_product_by_key( '60-phut' );
	if ( ! $pkg_product ) {
		JN_Coaching_Packages::ensure_packages_exist();
		$pkg_product = JN_Coaching_Packages::get_product_by_key( '60-phut' );
	}

	$pkg_order = wc_create_order(
		array(
			'status'      => 'pending',
			'customer_id' => 0,
		)
	);

	$item = new WC_Order_Item_Product();
	$item->set_product( $pkg_product );
	$item->set_name( $pkg_product->get_name() );
	$item->set_quantity( 1 );
	$item->set_subtotal( 2500000 );
	$item->set_total( 2500000 );
	$item->add_meta_data( 'Thời lượng', '60 Phút', true );
	$item->add_meta_data( 'Hình thức', 'Online 1:1 qua Zoom', true );
	$pkg_order->add_item( $item );

	$pkg_order->set_billing_first_name( 'Trần Minh Ngọc' );
	$pkg_order->set_billing_phone( '0912345678' );
	$pkg_order->set_billing_email( 'ngoc.coaching@example.com' );
	$pkg_order->set_billing_country( 'VN' );
	$pkg_order->set_payment_method( 'bacs' );
	$pkg_order->set_payment_method_title( 'Chuyển khoản ngân hàng (VietQR / 24/7)' );
	$pkg_order->set_total( 2500000 );
	$pkg_order->add_order_note( 'Đơn hàng Gói Coaching 1:1 (60 Phút) được tạo bởi Admin sau khi xác nhận lịch hẹn với khách hàng.' );
	$pkg_order->save();

	$payload = array(
		'demo_product_id'              => (int) $demo_id,
		'demo_product_url'             => get_permalink( $demo_id ),
		'coaching_60m_product_id'      => (int) $pkg_product->get_id(),
		'coaching_60m_status'          => $pkg_product->get_status(),
		'pending_package_order_id'     => (int) $pkg_order->get_id(),
		'pending_package_order_key'    => $pkg_order->get_order_key(),
		'pending_package_order_pay_url' => $pkg_order->get_checkout_payment_url(),
	);

	echo 'STEP4_PREPARE_JSON=' . wp_json_encode( $payload ) . PHP_EOL;
} elseif ( 'cleanup' === $action ) {
	$existing_demos = get_posts(
		array(
			'post_type'   => 'product',
			'post_status' => 'any',
			's'           => 'DEMO – xóa trước khi chạy thật',
			'fields'      => 'ids',
		)
	);
	$deleted = array();
	foreach ( $existing_demos as $demo_id ) {
		wp_delete_post( (int) $demo_id, true );
		$deleted[] = (int) $demo_id;
	}

	$sku_id = wc_get_product_id_by_sku( 'jn-demo-temp-product' );
	if ( $sku_id > 0 ) {
		wp_delete_post( (int) $sku_id, true );
		$deleted[] = (int) $sku_id;
	}

	wc_delete_product_transients();

	$remaining = wc_get_products(
		array(
			'status' => array( 'publish', 'draft', 'private', 'pending' ),
			'limit'  => -1,
		)
	);
	$summary   = array();
	foreach ( $remaining as $prod ) {
		$summary[] = array(
			'id'         => $prod->get_id(),
			'name'       => $prod->get_name(),
			'status'     => $prod->get_status(),
			'visibility' => $prod->get_catalog_visibility(),
			'virtual'    => $prod->is_virtual(),
			'key'        => $prod->get_meta( '_jn_package_key', true ),
		);
	}

	echo 'STEP4_CLEANUP_JSON=' . wp_json_encode(
		array(
			'deleted_demo_ids'   => array_values( array_unique( $deleted ) ),
			'remaining_products' => $summary,
		)
	) . PHP_EOL;
}
