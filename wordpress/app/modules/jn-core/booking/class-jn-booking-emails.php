<?php
/**
 * Custom WooCommerce Email Manager for the Jannie Nguyen Booking Engine.
 *
 * Registers 5 editable WooCommerce email classes under `WooCommerce -> Settings -> Emails`:
 * 1. `WC_Email_JN_Booking_New_Request` (Customer intake confirmation)
 * 2. `WC_Email_JN_Booking_Staff_Alert` (Staff new booking alert)
 * 3. `WC_Email_JN_Booking_Confirmed` (Customer slot confirmation + `.ics` attachment + `order-pay` link)
 * 4. `WC_Email_JN_Booking_Payment_Received` (Staff notification when booking order is paid)
 * 5. `WC_Email_JN_Booking_Cancelled` (Customer/staff cancellation notification)
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Booking_Emails
 */
class JN_Booking_Emails {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'woocommerce_email_classes', array( __CLASS__, 'register_email_classes' ), 20, 1 );
		add_action( 'jn_booking_created', array( __CLASS__, 'on_booking_created' ), 20, 1 );
		add_action( 'jn_booking_confirmed', array( __CLASS__, 'on_booking_confirmed' ), 20, 2 );
		add_action( 'jn_booking_order_paid', array( __CLASS__, 'on_booking_order_paid' ), 20, 2 );
		add_action( 'jn_booking_cancelled', array( __CLASS__, 'on_booking_cancelled' ), 20, 1 );
	}

	/**
	 * Register the 5 booking email classes with WooCommerce.
	 *
	 * Confirmed filter in `woocommerce/includes/class-wc-emails.php`.
	 *
	 * @param array<string, \WC_Email> $emails Registered WooCommerce email classes.
	 * @return array<string, \WC_Email>
	 */
	public static function register_email_classes( array $emails ): array {
		if ( ! class_exists( 'WC_Email' ) ) {
			return $emails;
		}

		require_once JN_CORE_PATH . 'booking/class-jn-booking-wc-email-items.php';

		$emails['WC_Email_JN_Booking_New_Request']      = new \WC_Email_JN_Booking_New_Request();
		$emails['WC_Email_JN_Booking_Staff_Alert']      = new \WC_Email_JN_Booking_Staff_Alert();
		$emails['WC_Email_JN_Booking_Confirmed']        = new \WC_Email_JN_Booking_Confirmed();
		$emails['WC_Email_JN_Booking_Payment_Received'] = new \WC_Email_JN_Booking_Payment_Received();
		$emails['WC_Email_JN_Booking_Cancelled']        = new \WC_Email_JN_Booking_Cancelled();

		return $emails;
	}

	/**
	 * Trigger customer intake confirmation and staff alert emails on public booking submission.
	 *
	 * @param int $booking_id Booking post ID.
	 * @return void
	 */
	public static function on_booking_created( int $booking_id ): void {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		$mailer = WC()->mailer();
		$emails = $mailer ? $mailer->get_emails() : array();

		if ( isset( $emails['WC_Email_JN_Booking_New_Request'] ) ) {
			$emails['WC_Email_JN_Booking_New_Request']->trigger( $booking_id );
		}
		if ( isset( $emails['WC_Email_JN_Booking_Staff_Alert'] ) ) {
			$emails['WC_Email_JN_Booking_Staff_Alert']->trigger( $booking_id );
		}
	}

	/**
	 * Trigger customer confirmation email (with `.ics` attachment and `order-pay` link).
	 *
	 * @param int $booking_id Booking post ID.
	 * @param int $order_id   Linked WooCommerce order ID.
	 * @return void
	 */
	public static function on_booking_confirmed( int $booking_id, int $order_id = 0 ): void {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		$mailer = WC()->mailer();
		$emails = $mailer ? $mailer->get_emails() : array();

		if ( isset( $emails['WC_Email_JN_Booking_Confirmed'] ) ) {
			$emails['WC_Email_JN_Booking_Confirmed']->trigger( $booking_id, $order_id );
		}
	}

	/**
	 * Trigger staff notification email when a booking's linked order is paid.
	 *
	 * @param int $booking_id Booking post ID.
	 * @param int $order_id   Linked WooCommerce order ID.
	 * @return void
	 */
	public static function on_booking_order_paid( int $booking_id, int $order_id = 0 ): void {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		$mailer = WC()->mailer();
		$emails = $mailer ? $mailer->get_emails() : array();

		if ( isset( $emails['WC_Email_JN_Booking_Payment_Received'] ) ) {
			$emails['WC_Email_JN_Booking_Payment_Received']->trigger( $booking_id, $order_id );
		}
	}

	/**
	 * Trigger cancellation email when a booking is cancelled.
	 *
	 * @param int $booking_id Booking post ID.
	 * @return void
	 */
	public static function on_booking_cancelled( int $booking_id ): void {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		$mailer = WC()->mailer();
		$emails = $mailer ? $mailer->get_emails() : array();

		if ( isset( $emails['WC_Email_JN_Booking_Cancelled'] ) ) {
			$emails['WC_Email_JN_Booking_Cancelled']->trigger( $booking_id );
		}
	}

	/**
	 * Extract structured booking data for email templates.
	 *
	 * @param int $booking_id Booking post ID.
	 * @param int $order_id   Optional order ID.
	 * @return array<string, mixed>
	 */
	public static function get_booking_email_data( int $booking_id, int $order_id = 0 ): array {
		if ( 0 === $order_id ) {
			$order_id = (int) get_post_meta( $booking_id, '_jn_order_id', true );
		}
		$order         = ( $order_id > 0 && function_exists( 'wc_get_order' ) ) ? wc_get_order( $order_id ) : null;
		$payment_state = JN_Booking_Service::get_derived_payment_state( $booking_id );
		$confirmed_raw = (string) get_post_meta( $booking_id, '_jn_confirmed_start', true );
		$confirmed_fmt = '';

		if ( '' !== $confirmed_raw ) {
			try {
				$tz            = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
				$dt            = new \DateTimeImmutable( $confirmed_raw, $tz );
				$confirmed_fmt = $dt->format( 'H:i — d/m/Y' ) . ' (Giờ Việt Nam GMT+7)';
			} catch ( \Exception $e ) {
				$confirmed_fmt = $confirmed_raw;
			}
		}

		$format       = (string) get_post_meta( $booking_id, '_jn_format', true );
		$meeting_link = (string) get_post_meta( $booking_id, '_jn_meeting_link', true );
		$location     = (string) get_post_meta( $booking_id, '_jn_location', true );
		if ( 'in-person' === $format && '' === $location ) {
			$location = (string) JN_Booking_Settings::get( 'academy_address', 'TP. Hồ Chí Minh, Việt Nam' );
		}

		return array(
			'booking_id'             => $booking_id,
			'booking_code'           => (string) get_post_meta( $booking_id, '_jn_booking_code', true ),
			'status'                 => (string) get_post_status( $booking_id ),
			'status_label'           => JN_Booking_CPT::get_status_label( (string) get_post_status( $booking_id ) ),
			'customer_name'          => (string) get_post_meta( $booking_id, '_jn_customer_name', true ),
			'customer_phone'         => (string) get_post_meta( $booking_id, '_jn_customer_phone', true ),
			'customer_email'         => (string) get_post_meta( $booking_id, '_jn_customer_email', true ),
			'package_title'          => (string) get_post_meta( $booking_id, '_jn_package_title', true ),
			'package_option_label'   => (string) get_post_meta( $booking_id, '_jn_package_option_label', true ),
			'format'                 => $format,
			'format_label'           => (string) get_post_meta( $booking_id, '_jn_format_label', true ),
			'preferred_window_label' => (string) get_post_meta( $booking_id, '_jn_preferred_window_label', true ),
			'goals'                  => (string) get_post_meta( $booking_id, '_jn_goals', true ),
			'confirmed_start'        => $confirmed_raw,
			'confirmed_display'      => $confirmed_fmt,
			'duration_minutes'       => (int) get_post_meta( $booking_id, '_jn_duration_minutes', true ),
			'meeting_link'           => $meeting_link,
			'location'               => $location,
			'order_id'               => $order instanceof \WC_Order ? (int) $order->get_id() : 0,
			'order_number'           => $order instanceof \WC_Order ? (string) $order->get_order_number() : '',
			'order_total_html'       => $order instanceof \WC_Order ? $order->get_formatted_order_total() : '',
			'pay_url'                => (string) $payment_state['pay_url'],
			'is_payable'             => (bool) $payment_state['is_payable'],
			'payment_label'          => (string) $payment_state['label'],
			'admin_edit_url'         => admin_url( 'post.php?post=' . $booking_id . '&action=edit' ),
			'myaccount_url'          => home_url( '/tai-khoan/lich-hen/' ),
		);
	}
}
