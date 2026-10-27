<?php
/**
 * RFC 5545 iCalendar (`.ics`) Generator for Confirmed Coaching Bookings.
 *
 * Generates timezone-accurate (`Asia/Ho_Chi_Minh`) calendar invitations for
 * WooCommerce confirmation emails and My Account downloads.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Booking_ICS
 */
class JN_Booking_ICS {

	/**
	 * Build the RFC 5545 `.ics` file contents for a confirmed booking.
	 *
	 * @param int $booking_id Booking post ID.
	 * @return string Raw `.ics` content, or `''` if booking has no confirmed start time.
	 */
	public static function generate_content( int $booking_id ): string {
		$post = get_post( $booking_id );
		if ( ! $post instanceof \WP_Post || JN_Booking_CPT::POST_TYPE !== $post->post_type ) {
			return '';
		}

		$confirmed_start = (string) get_post_meta( $booking_id, '_jn_confirmed_start', true );
		$duration_min    = max( 15, (int) get_post_meta( $booking_id, '_jn_duration_minutes', true ) );
		if ( '' === $confirmed_start ) {
			return '';
		}

		try {
			$tz       = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
			$start_dt = new \DateTimeImmutable( $confirmed_start, $tz );
			$end_dt   = $start_dt->modify( '+' . $duration_min . ' minutes' );
			$now_utc  = new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
		} catch ( \Exception $e ) {
			return '';
		}

		$code          = (string) get_post_meta( $booking_id, '_jn_booking_code', true );
		$pkg_title     = (string) get_post_meta( $booking_id, '_jn_package_title', true );
		$format        = (string) get_post_meta( $booking_id, '_jn_format', true );
		$format_label  = (string) get_post_meta( $booking_id, '_jn_format_label', true );
		$meeting_link  = (string) get_post_meta( $booking_id, '_jn_meeting_link', true );
		$location      = (string) get_post_meta( $booking_id, '_jn_location', true );
		$customer_name = (string) get_post_meta( $booking_id, '_jn_customer_name', true );
		$session_num   = (int) get_post_meta( $booking_id, '_jn_session_number', true );
		$total_sess    = (int) get_post_meta( $booking_id, '_jn_total_sessions', true );

		$venue = 'online' === $format
			? ( '' !== $meeting_link ? $meeting_link : 'Online Trực Tuyến (Zoom High-Res)' )
			: ( '' !== $location ? $location : (string) JN_Booking_Settings::get( 'academy_address', 'TP. Hồ Chí Minh, Việt Nam' ) );

		$summary = 'Coaching 1:1 cùng Jannie Nguyễn — ' . $pkg_title;
		if ( $total_sess > 1 && $session_num > 0 ) {
			$summary .= sprintf( ' (Buổi %d/%d)', $session_num, $total_sess );
		}

		$desc_lines = array(
			'Mã lịch hẹn: ' . $code,
			'Khách hàng / Học viên: ' . $customer_name,
			'Gói cố vấn: ' . $pkg_title,
			'Hình thức: ' . $format_label,
			'Địa điểm / Liên kết: ' . $venue,
		);
		$description = implode( "\\n", array_map( array( __CLASS__, 'escape_ics_text' ), $desc_lines ) );

		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		if ( '' === $host ) {
			$host = 'jannienguyen.vn';
		}
		$uid = sprintf( 'booking-%d-%s@%s', $booking_id, sanitize_key( $code ), $host );

		$lines = array(
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//Jannie Nguyen Academy//JN Booking Engine//VI',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'BEGIN:VTIMEZONE',
			'TZID:Asia/Ho_Chi_Minh',
			'BEGIN:STANDARD',
			'TZOFFSETFROM:+0700',
			'TZOFFSETTO:+0700',
			'TZNAME:ICT',
			'DTSTART:19700101T000000',
			'END:STANDARD',
			'END:VTIMEZONE',
			'BEGIN:VEVENT',
			'UID:' . self::escape_ics_text( $uid ),
			'DTSTAMP:' . $now_utc->format( 'Ymd\THis\Z' ),
			'DTSTART;TZID=Asia/Ho_Chi_Minh:' . $start_dt->format( 'Ymd\THis' ),
			'DTEND;TZID=Asia/Ho_Chi_Minh:' . $end_dt->format( 'Ymd\THis' ),
			'SUMMARY:' . self::escape_ics_text( $summary ),
			'DESCRIPTION:' . $description,
			'LOCATION:' . self::escape_ics_text( $venue ),
			'ORGANIZER;CN="Jannie Nguyen Academy":mailto:contact@jannienguyen.vn',
			'STATUS:CONFIRMED',
			'END:VEVENT',
			'END:VCALENDAR',
		);

		return implode( "\r\n", $lines ) . "\r\n";
	}

	/**
	 * Write a temporary `.ics` file for email attachment and return its absolute file path.
	 *
	 * @param int $booking_id Booking post ID.
	 * @return string Absolute path to `.ics` file, or `''` on failure.
	 */
	public static function write_attachment_file( int $booking_id ): string {
		$content = self::generate_content( $booking_id );
		if ( '' === $content ) {
			return '';
		}

		$code = (string) get_post_meta( $booking_id, '_jn_booking_code', true );
		$slug = '' !== $code ? sanitize_file_name( strtolower( $code ) ) : (string) $booking_id;

		$upload_dir = wp_upload_dir();
		$base_dir   = isset( $upload_dir['basedir'] ) ? trailingslashit( $upload_dir['basedir'] ) . 'jn-ics-tmp' : trailingslashit( get_temp_dir() ) . 'jn-ics-tmp';

		if ( ! is_dir( $base_dir ) ) {
			wp_mkdir_p( $base_dir );
			// Prevent directory listing or direct public browsing of temp ics folder.
			$ht_file = trailingslashit( $base_dir ) . '.htaccess';
			if ( ! file_exists( $ht_file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				@file_put_contents( $ht_file, "Deny from all\n" );
			}
		}

		$filename = sprintf( 'lich-hen-%s.ics', $slug );
		$filepath = trailingslashit( $base_dir ) . $filename;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$written = @file_put_contents( $filepath, $content );
		if ( false === $written ) {
			return '';
		}

		return $filepath;
	}

	/**
	 * Escape special characters per RFC 5545 section 3.3.11.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	private static function escape_ics_text( string $text ): string {
		$clean = str_replace( array( "\r\n", "\r", "\n" ), ' ', $text );
		$clean = str_replace( '\\', '\\\\', $clean );
		$clean = str_replace( ';', '\;', $clean );
		$clean = str_replace( ',', '\,', $clean );
		return $clean;
	}
}
