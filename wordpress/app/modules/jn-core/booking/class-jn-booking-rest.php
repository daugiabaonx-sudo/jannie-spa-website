<?php
/**
 * REST API Endpoints & No-JS Post/Redirect/Get Handler for the Booking Engine.
 *
 * - `GET  /api/jn/v1/booking/token` (issues short-lived signed token on first interaction for cached pages)
 * - `POST /api/jn/v1/bookings` (public booking submission)
 * - `POST /api/jn/v1/bookings/(?P<id>\d+)/transition` (staff-only status transitions)
 * - `GET  /api/jn/v1/bookings/overlap-check` (staff-only overlap warning check)
 * - `template_redirect` Post/Redirect/Get (PRG) handler for no-JS form submissions.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Booking_REST
 */
class JN_Booking_REST {

	/**
	 * REST API namespace.
	 */
	const NAMESPACE = 'jn/v1';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_nojs_post_redirect_get' ), 5 );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		// 1. Short-lived signed token endpoint for cached HTML pages.
		register_rest_route(
			self::NAMESPACE,
			'/booking/token',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_fresh_token' ),
				'permission_callback' => '__return_true',
			)
		);

		// 2. Public booking intake submission endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/bookings',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_booking' ),
				'permission_callback' => '__return_true',
			)
		);

		// 3. Staff-only booking status transition endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/bookings/(?P<id>\d+)/transition',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'transition_booking' ),
				'permission_callback' => array( __CLASS__, 'check_staff_permission' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && (int) $param > 0;
						},
					),
				),
			)
		);

		// 4. Staff-only overlap check endpoint.
		register_rest_route(
			self::NAMESPACE,
			'/bookings/overlap-check',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'check_overlap' ),
				'permission_callback' => array( __CLASS__, 'check_staff_permission' ),
			)
		);
	}

	/**
	 * Permission callback for staff-only booking endpoints.
	 *
	 * @return bool|\WP_Error
	 */
	public static function check_staff_permission() {
		if ( ! is_user_logged_in() || ! JN_Booking_Roles::user_can_manage_bookings() ) {
			return new \WP_Error(
				'rest_forbidden',
				'Bạn không có quyền thực hiện thao tác quản trị lịch hẹn.',
				array( 'status' => is_user_logged_in() ? 403 : 401 )
			);
		}
		return true;
	}

	/**
	 * Return a fresh short-lived signed token for page-cached HTML forms.
	 *
	 * @return \WP_REST_Response
	 */
	public static function get_fresh_token(): \WP_REST_Response {
		$token    = JN_Booking_Service::issue_form_token();
		$response = new \WP_REST_Response(
			array(
				'token'            => $token,
				'issued_at'        => time(),
				'min_fill_seconds' => JN_Booking_Service::MIN_FILL_SECONDS,
			),
			200
		);
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private' );
		return $response;
	}

	/**
	 * Handle public booking creation via REST API (`POST /api/jn/v1/bookings`).
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function create_booking( \WP_REST_Request $request ) {
		$params = $request->get_params();
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		$context = array(
			'ip'          => JN_Booking_Service::get_client_ip(),
			'landing_url' => isset( $params['_jn_landing_url'] ) ? (string) $params['_jn_landing_url'] : (string) $request->get_header( 'referer' ),
			'referrer'    => isset( $params['_jn_referrer'] ) ? (string) $params['_jn_referrer'] : '',
		);

		$result = JN_Booking_Service::submit_public_booking( $params, $context );
		if ( is_wp_error( $result ) ) {
			$error_data = $result->get_error_data();
			$status     = is_array( $error_data ) && isset( $error_data['status'] ) ? (int) $error_data['status'] : 400;
			return new \WP_REST_Response(
				array(
					'success' => false,
					'code'    => $result->get_error_code(),
					'message' => $result->get_error_message(),
				),
				$status
			);
		}

		return new \WP_REST_Response(
			array_merge(
				array( 'success' => true ),
				$result
			),
			201
		);
	}

	/**
	 * Handle staff status transition via REST API.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public static function transition_booking( \WP_REST_Request $request ): \WP_REST_Response {
		$booking_id = (int) $request->get_param( 'id' );
		$to_status  = sanitize_key( (string) $request->get_param( 'to_status' ) );
		$params     = $request->get_params();

		$result = JN_Booking_Service::transition_status( $booking_id, $to_status, is_array( $params ) ? $params : array() );
		if ( is_wp_error( $result ) ) {
			$error_data = $result->get_error_data();
			$status     = is_array( $error_data ) && isset( $error_data['status'] ) ? (int) $error_data['status'] : 400;
			return new \WP_REST_Response(
				array(
					'success'  => false,
					'code'     => $result->get_error_code(),
					'message'  => $result->get_error_message(),
					'overlaps' => is_array( $error_data ) && isset( $error_data['overlaps'] ) ? $error_data['overlaps'] : array(),
				),
				$status
			);
		}

		return new \WP_REST_Response(
			array_merge(
				array( 'success' => true ),
				$result
			),
			200
		);
	}

	/**
	 * Check for overlapping confirmed bookings via REST API.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public static function check_overlap( \WP_REST_Request $request ): \WP_REST_Response {
		$raw_start  = sanitize_text_field( (string) $request->get_param( 'confirmed_start' ) );
		$duration   = max( 15, (int) $request->get_param( 'duration_minutes' ) );
		$exclude_id = absint( $request->get_param( 'booking_id' ) );

		if ( '' === $raw_start ) {
			return new \WP_REST_Response( array( 'overlaps' => array() ), 200 );
		}

		try {
			$tz       = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
			$start_dt = new \DateTimeImmutable( str_replace( 'T', ' ', $raw_start ), $tz );
			$start_ts = $start_dt->getTimestamp();
			$end_ts   = $start_ts + ( $duration * 60 );
			$overlaps = JN_Booking_Service::find_overlapping_bookings( $start_ts, $end_ts, $exclude_id );
		} catch ( \Exception $e ) {
			$overlaps = array();
		}

		return new \WP_REST_Response(
			array(
				'has_overlap' => ! empty( $overlaps ),
				'overlaps'    => $overlaps,
			),
			200
		);
	}

	/**
	 * Handle non-JavaScript form submissions using the Post/Redirect/Get (PRG) pattern.
	 *
	 * Zero personal data is placed in the redirect URL or logs.
	 *
	 * @return void
	 */
	public static function handle_nojs_post_redirect_get(): void {
		if ( is_admin() || ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Authenticated via signed stateless `_jn_form_token` inside `submit_public_booking()`.
		if ( ! isset( $_POST['jn_booking_submit_action'] ) || '1' !== (string) $_POST['jn_booking_submit_action'] ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw_post = wp_unslash( $_POST );
		$req_uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		$referer  = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		$context = array(
			'ip'          => JN_Booking_Service::get_client_ip(),
			'landing_url' => home_url( $req_uri ),
			'referrer'    => $referer,
		);

		$result    = JN_Booking_Service::submit_public_booking( $raw_post, $context );
		$flash_key = substr( wp_generate_password( 16, false, false ), 0, 12 );

		if ( is_wp_error( $result ) ) {
			set_transient(
				'jn_bf_flash_' . $flash_key,
				array(
					'ok'      => false,
					'code'    => $result->get_error_code(),
					'message' => $result->get_error_message(),
				),
				120
			);
		} else {
			set_transient(
				'jn_bf_flash_' . $flash_key,
				array(
					'ok'           => true,
					'is_waitlist'  => ! empty( $result['is_waitlist'] ),
					'waitlist_msg' => (string) ( $result['waitlist_msg'] ?? '' ),
				),
				120
			);
		}

		$clean_path   = (string) wp_parse_url( $req_uri, PHP_URL_PATH );
		$redirect_url = add_query_arg( 'jn_bf', $flash_key, home_url( $clean_path ) ) . '#booking-form';

		wp_safe_redirect( $redirect_url, 303 );
		exit;
	}

	/**
	 * Consume and delete a Post/Redirect/Get flash state if `?jn_bf=` is present in the URL.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function consume_prg_flash(): ?array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['jn_bf'] ) || ! is_string( $_GET['jn_bf'] ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$flash_key = preg_replace( '/[^a-zA-Z0-9]/', '', wp_unslash( $_GET['jn_bf'] ) );
		if ( '' === $flash_key ) {
			return null;
		}

		$transient_key = 'jn_bf_flash_' . $flash_key;
		$data          = get_transient( $transient_key );
		if ( ! is_array( $data ) ) {
			return null;
		}

		delete_transient( $transient_key );
		return $data;
	}
}
