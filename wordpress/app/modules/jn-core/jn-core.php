<?php
/**
 * Plugin Name:          JN Core
 * Plugin URI:           https://jannienguyen.vn
 * Description:          Core domain features, inline SVG icon system, coaching booking, customer CRM management, staff roles, and testimonials for Jannie Nguyen Academy.
 * Version:              1.3.0
 * Requires at least:    6.7
 * Requires PHP:         8.1
 * WC requires at least: 9.0
 * WC tested up to:      11.2.1
 * Author:               Jannie Nguyen Engineering
 * License:              GPL-2.0-or-later
 * Text Domain:          jn-core
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'JN_CORE_VERSION', '1.3.0' );
define( 'JN_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'JN_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declare explicit compatibility with WooCommerce High-Performance Order Storage (HPOS)
 * and WooCommerce Cart & Checkout Blocks.
 *
 * Confirmed in Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility().
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

require_once JN_CORE_PATH . 'blocks/icon/class-jn-icon-sprite.php';
require_once JN_CORE_PATH . 'blocks/class-jn-blocks.php';
require_once JN_CORE_PATH . 'testimonials/class-jn-testimonials.php';
require_once JN_CORE_PATH . 'booking/class-jn-coaching-packages.php';
require_once JN_CORE_PATH . 'booking/class-jn-booking-cpt.php';
require_once JN_CORE_PATH . 'booking/class-jn-booking-roles.php';
require_once JN_CORE_PATH . 'booking/class-jn-booking-settings.php';
require_once JN_CORE_PATH . 'customers/class-jn-customer-profile.php';
require_once JN_CORE_PATH . 'customers/class-jn-customer-service.php';
require_once JN_CORE_PATH . 'customers/class-jn-booking-privacy.php';
require_once JN_CORE_PATH . 'customers/class-jn-csv-exporter.php';
require_once JN_CORE_PATH . 'customers/class-jn-customer-admin.php';
require_once JN_CORE_PATH . 'booking/class-jn-booking-ics.php';
require_once JN_CORE_PATH . 'booking/class-jn-booking-service.php';
require_once JN_CORE_PATH . 'booking/class-jn-booking-emails.php';
require_once JN_CORE_PATH . 'booking/class-jn-booking-rest.php';
require_once JN_CORE_PATH . 'booking/class-jn-booking-admin.php';
require_once JN_CORE_PATH . 'booking/class-jn-booking-myaccount.php';

JN_Blocks::init();
JN_Testimonials::init();
JN_Coaching_Packages::init();
JN_Booking_CPT::init();
JN_Booking_Roles::init();
JN_Customer_Profile::init();
JN_Customer_Service::init();
JN_Booking_Privacy::init();
JN_CSV_Exporter::init();
JN_Customer_Admin::init();
JN_Booking_Service::init();
JN_Booking_Emails::init();
JN_Booking_REST::init();
JN_Booking_Admin::init();
JN_Booking_MyAccount::init();
