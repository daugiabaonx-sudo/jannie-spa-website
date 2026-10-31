<?php
/**
 * Title: My Favorite Product — Giới thiệu & Bộ sưu tập
 * Slug: jannie/favorite-collection
 * Categories: jannie-nguyen
 * Description: Đoạn giới thiệu có chữ cái lớn đầu đoạn và lưới sản phẩm yêu thích (WooCommerce Product Collection, danh mục 41): ảnh thật, số thứ tự, tên, mô tả ngắn, giá và nút (jn/product-actions).
 * Viewport Width: 1440
 *
 * @package Jannie
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"tagName":"section","metadata":{"name":"My Favorite Product — Giới thiệu"},"align":"full","className":"jn-section jn-fav-intro"} -->
<section class="wp-block-group alignfull jn-section jn-fav-intro">
	<!-- wp:group {"className":"jn-container"} -->
	<div class="wp-block-group jn-container">
		<!-- wp:paragraph {"className":"jn-fav-intro__text"} -->
		<p class="jn-fav-intro__text">Tại đây, chúng tôi tự hào giới thiệu bộ sưu tập sản phẩm yêu thích, đáng tin cậy để mang đến sự tươi mới và hoàn hảo cho làn da và tóc của bạn. Với sự tập trung vào chất lượng và chăm sóc, chúng tôi chỉ sử dụng các sản phẩm chất lượng cao từ các thương hiệu danh tiếng. Từ chăm sóc da tự nhiên đến chăm sóc tóc chuyên sâu, cam kết của chúng tôi là mang đến trải nghiệm làm đẹp tuyệt vời và kết quả đáng mong đợi. Hãy khám phá bộ sưu tập sản phẩm yêu thích của Jannie và trải nghiệm làn da và mái tóc khỏe mạnh và rạng rỡ.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","metadata":{"name":"My Favorite Product — Bộ sưu tập"},"anchor":"bo-suu-tap","align":"full","className":"jn-section jn-fav-collection"} -->
<section id="bo-suu-tap" class="wp-block-group alignfull jn-section jn-fav-collection">
	<!-- wp:group {"className":"jn-container"} -->
	<div class="wp-block-group jn-container">
		<!-- wp:heading {"className":"jn-fav-collection__title"} -->
		<h2 class="wp-block-heading jn-fav-collection__title">Sắc Xuân Mới – <em class="jn-accent-italic">Bộ Sản Phẩm Yêu Thích Của Jannie</em></h2>
		<!-- /wp:heading -->
		<!-- wp:woocommerce/product-collection {"queryId":1,"query":{"perPage":9,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"menu_order","search":"","exclude":[],"inherit":false,"taxQuery":{"product_cat":[41]},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false},"tagName":"div","displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/product-catalog","hideControls":["inherit","filterable"],"className":"jn-fav-products"} -->
		<div class="wp-block-woocommerce-product-collection jn-fav-products">
			<!-- wp:woocommerce/product-template -->
			<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"single","isDescendentOfQueryLoop":true} /-->
			<!-- wp:post-title {"textAlign":"left","level":3,"isLink":true,"className":"jn-fav-product-title","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
			<!-- wp:woocommerce/product-summary {"isDescendentOfQueryLoop":true,"showDescriptionIfEmpty":true,"summaryLength":0,"className":"jn-fav-product-summary"} /-->
			<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"left","className":"jn-fav-product-price"} /-->
			<!-- wp:jn/product-actions /-->
			<!-- /wp:woocommerce/product-template -->
			<!-- wp:woocommerce/product-collection-no-results -->
			<!-- wp:paragraph {"align":"center","textColor":"muted-stone"} -->
			<p class="has-text-align-center has-muted-stone-color has-text-color">Danh mục đang được cập nhật.</p>
			<!-- /wp:paragraph -->
			<!-- /wp:woocommerce/product-collection-no-results -->
		</div>
		<!-- /wp:woocommerce/product-collection -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
