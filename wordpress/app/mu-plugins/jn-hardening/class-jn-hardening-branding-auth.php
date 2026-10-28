<?php
/**
 * JN Hardening — Branding Traces, Vietnamese Transactional Emails & Auth Hardening (Tasks C & D).
 *
 * - Replaces default WordPress favicon (`w-logo-blue.png`) with the Jannie Nguyen monogram SVG.
 * - Brands the custom (hidden-slug) login screen in Warm Ivory & Playfair Display,
 *   removing all WordPress logos, titles, and links.
 * - Routes all outgoing emails from "Jannie Nguyen Academy <contact@jannienguyen.vn>" with
 *   warm Vietnamese copy (never "WordPress <wordpress@...>").
 * - Rate-limits failed login attempts (max 5 failures per 15 minutes per HMAC-hashed IP or username/email)
 *   across both the hidden-slug login screen and WooCommerce My Account (`/tai-khoan/`).
 * - Restricts Application Passwords strictly to administrators (`manage_options`).
 * - Forces core `users_can_register` off (`0`) so only WooCommerce controls customer registration.
 *
 * @package JannieNguyen\MU
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Branding sanitization, Vietnamese email templates, and authentication hardening.
 */
class JN_Hardening_Branding_Auth {

	/**
	 * Maximum failed login attempts allowed inside the lockout window.
	 */
	public const MAX_LOGIN_ATTEMPTS = 5;

	/**
	 * Lockout duration in seconds (15 minutes).
	 */
	public const LOCKOUT_SECONDS = 900;

	/**
	 * Register branding, email, and authentication hardening hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		// 1. Brand favicon (never serve wp-includes/images/w-logo-blue-white-bg.png).
		add_filter( 'get_site_icon_url', array( __CLASS__, 'filter_site_icon_url' ), 999, 1 );
		add_action( 'do_favicon', array( __CLASS__, 'serve_brand_favicon' ), 1 );
		remove_action( 'do_faviconico', 'do_faviconico' );
		add_action( 'do_faviconico', array( __CLASS__, 'serve_brand_favicon' ), 1 );

		// 2. Branded (hidden-slug) login screen & admin footer credits.
		// The slug itself is configuration, not code: WPS Hide Login's `whl_page` follows WP_LOGIN_SLUG.
		add_filter( 'pre_option_whl_page', array( __CLASS__, 'filter_login_slug_from_env' ) );
		add_action( 'login_init', array( __CLASS__, 'disable_login_page_cache' ), 0 );
		add_filter( 'login_headerurl', array( __CLASS__, 'filter_login_header_url' ), 999 );
		add_filter( 'login_headertext', array( __CLASS__, 'filter_login_header_text' ), 999 );
		add_filter( 'login_title', array( __CLASS__, 'filter_login_title' ), 999, 2 );
		add_filter( 'login_display_language_dropdown', '__return_false', 999 );
		add_action( 'login_enqueue_scripts', array( __CLASS__, 'render_login_brand_styles' ), 20 );
		add_filter( 'admin_footer_text', '__return_empty_string', 999 );
		add_filter( 'update_footer', '__return_empty_string', 999 );

		// 3. Branded email sender & Vietnamese core email templates (never "WordPress <wordpress@...>").
		add_filter( 'wp_mail_from', array( __CLASS__, 'filter_mail_from' ), 999, 1 );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'filter_mail_from_name' ), 999, 1 );
		add_filter( 'wp_mail', array( __CLASS__, 'scrub_wp_mail_branding' ), 999, 1 );
		add_action( 'phpmailer_init', array( __CLASS__, 'configure_local_mailpit' ), 10, 1 );

		add_filter( 'retrieve_password_title', array( __CLASS__, 'filter_retrieve_password_title' ), 999, 3 );
		add_filter( 'retrieve_password_message', array( __CLASS__, 'filter_retrieve_password_message' ), 999, 4 );
		add_filter( 'wp_new_user_notification_email', array( __CLASS__, 'filter_new_user_notification_email' ), 999, 3 );
		add_filter( 'wp_new_user_notification_email_admin', array( __CLASS__, 'filter_new_user_notification_email_admin' ), 999, 3 );
		add_filter( 'password_change_email', array( __CLASS__, 'filter_password_change_email' ), 999, 3 );
		add_filter( 'email_change_email', array( __CLASS__, 'filter_email_change_email' ), 999, 3 );
		add_filter( 'user_request_action_email_subject', array( __CLASS__, 'filter_privacy_email_subject' ), 999, 2 );

		// 4. Rate-limit failed logins on custom login slug & WooCommerce My Account (/tai-khoan/).
		add_filter( 'authenticate', array( __CLASS__, 'enforce_rate_limit_pre_auth' ), 1, 3 );
		add_filter( 'authenticate', array( __CLASS__, 'enforce_rate_limit_post_auth' ), 999, 3 );
		add_filter( 'woocommerce_process_login_errors', array( __CLASS__, 'filter_woocommerce_login_errors' ), 1, 3 );
		add_action( 'wp_login', array( __CLASS__, 'clear_rate_limit_on_success' ), 10, 1 );
		add_filter( 'login_errors', array( __CLASS__, 'sanitize_login_error_display' ), 999, 1 );

		// 5. Application Passwords only for administrators & disable core users_can_register.
		add_filter( 'wp_is_application_passwords_available_for_user', array( __CLASS__, 'restrict_app_passwords_to_admins' ), 999, 2 );
		add_filter( 'pre_option_users_can_register', '__return_zero', 999 );
	}

	/**
	 * Ensure `get_site_icon_url()` always returns the Jannie Nguyen monogram SVG instead of `w-logo-blue`.
	 *
	 * @param string $url Current site icon URL.
	 * @return string
	 */
	public static function filter_site_icon_url( string $url ): string {
		if ( '' === $url || false !== strpos( $url, 'w-logo-blue' ) || false !== strpos( $url, 'wp-includes' ) ) {
			return home_url( '/static/ui/assets/images/favicon.svg' );
		}
		return $url;
	}

	/**
	 * Serve the Jannie Nguyen monogram SVG directly when `/favicon.ico` is requested.
	 *
	 * @return void
	 */
	public static function serve_brand_favicon(): void {
		$svg_path = WP_CONTENT_DIR . '/themes/jannie/assets/images/favicon.svg';
		if ( ! headers_sent() ) {
			status_header( 200 );
			header( 'Content-Type: image/svg+xml; charset=utf-8', true );
			header( 'Cache-Control: public, max-age=86400', true );
		}
		if ( is_file( $svg_path ) && is_readable( $svg_path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents,WordPress.Security.EscapeOutput.OutputNotEscaped
			echo file_get_contents( $svg_path );
		}
		exit;
	}

	/**
	 * Read the hidden login slug (WPS Hide Login `whl_page`) from the WP_LOGIN_SLUG environment variable,
	 * so the secret lives in `.env` / `.env.production` and can be rotated without a code or DB change.
	 *
	 * @param mixed $pre Short-circuit value (false = read the stored option).
	 * @return mixed
	 */
	public static function filter_login_slug_from_env( $pre ) {
		$slug = sanitize_title( (string) getenv( 'WP_LOGIN_SLUG' ) );
		return '' !== $slug ? $slug : $pre;
	}

	/**
	 * Never write the login screen to the page cache, whatever its (secret) slug is.
	 *
	 * @return void
	 */
	public static function disable_login_page_cache(): void {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
	}

	/**
	 * Point the login logo link to the site homepage instead of wordpress.org.
	 *
	 * @return string
	 */
	public static function filter_login_header_url(): string {
		return home_url( '/' );
	}

	/**
	 * Replace "Powered by WordPress" on the login logo with the brand name.
	 *
	 * @return string
	 */
	public static function filter_login_header_text(): string {
		return 'Jannie Nguyen Academy';
	}

	/**
	 * Remove "— WordPress" from the `<title>` tag on the login screen.
	 *
	 * @param string $login_title Full login page `<title>`.
	 * @param string $title       Action title (e.g. "Đăng nhập").
	 * @return string
	 */
	public static function filter_login_title( string $login_title, string $title ): string {
		unset( $login_title );
		$clean_title = '' !== trim( $title ) ? trim( $title ) : __( 'Đăng nhập', 'jannie' );
		return sprintf( '%s — Jannie Nguyen Academy', $clean_title );
	}

	/**
	 * Render custom luxury editorial styling for the hidden-slug login screen.
	 *
	 * @return void
	 */
	public static function render_login_brand_styles(): void {
		$monogram_svg = rawurlencode(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80"><rect width="80" height="80" rx="2" fill="#1F1E1D"/><rect x="5" y="5" width="70" height="70" rx="1" fill="none" stroke="#735944" stroke-width="1.5"/><text x="40" y="50" text-anchor="middle" fill="#FAF8F5" font-family="Georgia, serif" font-size="30" font-weight="600" letter-spacing="3">JN</text></svg>'
		);
		?>
		<style id="jn-login-brand-styles">
			body.login {
				background-color: #FAF8F5;
				color: #1F1E1D;
				font-family: 'Geist', -apple-system, BlinkMacSystemFont, sans-serif;
			}
			#login {
				width: 100%;
				max-width: 420px;
				padding: 64px 20px;
			}
			.login h1 a {
				background-image: url("data:image/svg+xml,<?php echo esc_attr( $monogram_svg ); ?>") !important;
				background-size: 72px 72px !important;
				width: 72px !important;
				height: 72px !important;
				margin: 0 auto 24px !important;
			}
			.login form {
				background: #FFFFFF;
				border: 1px solid #E5DFD7;
				border-radius: 2px;
				box-shadow: none;
				padding: 32px 28px;
			}
			.login label {
				color: #1F1E1D;
				font-size: 13px;
				font-weight: 500;
				letter-spacing: 0.04em;
			}
			.login input[type="text"],
			.login input[type="password"],
			.login input[type="email"] {
				background: #FAF8F5;
				border: 1px solid #D1C7BD;
				border-radius: 2px;
				color: #1F1E1D;
				padding: 8px 12px;
				box-shadow: none;
			}
			.login input[type="text"]:focus,
			.login input[type="password"]:focus,
			.login input[type="email"]:focus {
				border-color: #735944;
				box-shadow: 0 0 0 1px #735944;
			}
			.wp-core-ui .button-primary {
				background: #1F1E1D !important;
				border-color: #1F1E1D !important;
				border-radius: 2px !important;
				color: #FAF8F5 !important;
				text-shadow: none !important;
				box-shadow: none !important;
				text-transform: uppercase;
				letter-spacing: 0.08em;
				font-size: 12px;
				font-weight: 500;
				padding: 4px 18px !important;
			}
			.wp-core-ui .button-primary:hover,
			.wp-core-ui .button-primary:focus {
				background: #735944 !important;
				border-color: #735944 !important;
			}
			.login #nav a,
			.login #backtoblog a {
				color: #4A4640 !important;
			}
			.login #nav a:hover,
			.login #backtoblog a:hover {
				color: #1F1E1D !important;
			}
			.login #login_error,
			.login .message,
			.login .success {
				border-left-color: #735944;
				background: #FFFFFF;
				border-top: 1px solid #E5DFD7;
				border-right: 1px solid #E5DFD7;
				border-bottom: 1px solid #E5DFD7;
				box-shadow: none;
			}
		</style>
		<?php
	}

	/**
	 * Ensure valid From email address (never `wordpress@...`).
	 *
	 * @param string $from_email Default From email address.
	 * @return string
	 */
	public static function filter_mail_from( string $from_email ): string {
		$trimmed = trim( $from_email );
		if ( '' === $trimmed || str_starts_with( strtolower( $trimmed ), 'wordpress@' ) ) {
			return 'contact@jannienguyen.vn';
		}
		return $from_email;
	}

	/**
	 * Ensure outgoing emails always use the brand sender name instead of "WordPress".
	 *
	 * @param string $from_name Default From name.
	 * @return string
	 */
	public static function filter_mail_from_name( string $from_name ): string {
		$trimmed = trim( $from_name );
		if ( '' === $trimmed || 'WordPress' === $trimmed || 'Jannie Nguyen' === $trimmed ) {
			return 'Jannie Nguyen Academy';
		}
		return $from_name;
	}

	/**
	 * Final safety-net filter on `wp_mail` so no email subject or body ever contains "WordPress".
	 *
	 * @param array<string, mixed> $args `wp_mail()` arguments.
	 * @return array<string, mixed>
	 */
	public static function scrub_wp_mail_branding( array $args ): array {
		if ( isset( $args['subject'] ) && is_string( $args['subject'] ) ) {
			$args['subject'] = str_ireplace( array( '[WordPress]', 'WordPress' ), array( '[Jannie Nguyen Academy]', 'Jannie Nguyen Academy' ), $args['subject'] );
		}

		if ( isset( $args['message'] ) && is_string( $args['message'] ) ) {
			$args['message'] = str_ireplace(
				array( 'wordpress@', 'WordPress' ),
				array( 'contact@', 'Jannie Nguyen Academy' ),
				$args['message']
			);
		}

		return $args;
	}

	/**
	 * Route all WordPress emails to Mailpit in the local environment only.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance passed by reference.
	 * @return void
	 */
	public static function configure_local_mailpit( $phpmailer ): void {
		if ( ! function_exists( 'wp_get_environment_type' ) || 'local' !== wp_get_environment_type() ) {
			return;
		}

		$smtp_host = getenv( 'SMTP_HOST' );
		$smtp_port = getenv( 'SMTP_PORT' );

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$phpmailer->isSMTP();
		$phpmailer->Host        = ( false !== $smtp_host && '' !== $smtp_host ) ? $smtp_host : 'mailpit';
		$phpmailer->Port        = ( false !== $smtp_port && '' !== $smtp_port ) ? (int) $smtp_port : 1025;
		$phpmailer->SMTPAuth    = false;
		$phpmailer->SMTPSecure  = '';
		$phpmailer->SMTPAutoTLS = false;
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Customize password reset email subject in Vietnamese.
	 *
	 * @param string  $title      Default email subject.
	 * @param string  $user_login Username.
	 * @param WP_User $user_data  User object.
	 * @return string
	 */
	public static function filter_retrieve_password_title( string $title, string $user_login, $user_data ): string {
		unset( $title, $user_login, $user_data );
		return '[Jannie Nguyen Academy] Đặt lại mật khẩu tài khoản của bạn';
	}

	/**
	 * Customize password reset email body in warm Vietnamese copy.
	 *
	 * @param string  $message    Default email message.
	 * @param string  $key        Password reset key.
	 * @param string  $user_login Username.
	 * @param WP_User $user_data  User object.
	 * @return string
	 */
	public static function filter_retrieve_password_message( string $message, string $key, string $user_login, $user_data ): string {
		unset( $message );
		$display_name = ( $user_data instanceof WP_User && '' !== trim( $user_data->display_name ) )
			? $user_data->display_name
			: $user_login;

		$reset_url = add_query_arg(
			array(
				'key'   => rawurlencode( $key ),
				'login' => rawurlencode( $user_login ),
			),
			home_url( '/tai-khoan/lost-password/' )
		);

		$lines = array(
			sprintf( 'Xin chào %s,', $display_name ),
			'',
			'Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại Jannie Nguyen Academy.',
			'Để thiết lập mật khẩu mới, bạn vui lòng truy cập đường dẫn bên dưới:',
			$reset_url,
			'',
			'Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này — mật khẩu hiện tại của bạn vẫn được bảo mật an toàn.',
			'',
			'Trân trọng,',
			'Jannie Nguyen Academy',
			home_url( '/' ),
		);

		return implode( "\r\n", $lines );
	}

	/**
	 * Customize new user welcome/activation email in Vietnamese.
	 *
	 * @param array<string, string> $email   Email array (`to`, `subject`, `message`, `headers`).
	 * @param WP_User               $user    New user object.
	 * @param string                $blogname Site title.
	 * @return array<string, string>
	 */
	public static function filter_new_user_notification_email( array $email, $user, string $blogname ): array {
		unset( $blogname );
		$display_name = ( $user instanceof WP_User && '' !== trim( $user->display_name ) )
			? $user->display_name
			: ( $user instanceof WP_User ? $user->user_login : 'bạn' );

		$email['subject'] = '[Jannie Nguyen Academy] Chào mừng bạn đến với Jannie Nguyen Academy';
		$email['message'] = implode(
			"\r\n",
			array(
				sprintf( 'Xin chào %s,', $display_name ),
				'',
				'Tài khoản của bạn tại Jannie Nguyen Academy đã được khởi tạo thành công.',
				sprintf( 'Bạn có thể truy cập trang quản lý tài khoản tại: %s', home_url( '/tai-khoan/' ) ),
				'',
				'Trân trọng,',
				'Jannie Nguyen Academy',
				home_url( '/' ),
			)
		);

		return $email;
	}

	/**
	 * Customize admin notification email when a new user registers.
	 *
	 * @param array<string, string> $email    Email array.
	 * @param WP_User               $user     New user object.
	 * @param string                $blogname Site title.
	 * @return array<string, string>
	 */
	public static function filter_new_user_notification_email_admin( array $email, $user, string $blogname ): array {
		unset( $blogname );
		$login            = $user instanceof WP_User ? $user->user_login : '';
		$user_email       = $user instanceof WP_User ? $user->user_email : '';
		$email['subject'] = '[Jannie Nguyen Academy] Học viên / khách hàng mới đăng ký tài khoản';
		$email['message'] = implode(
			"\r\n",
			array(
				'Thông báo hệ thống Jannie Nguyen Academy:',
				'',
				sprintf( 'Tài khoản mới: %s', $login ),
				sprintf( 'Email: %s', $user_email ),
			)
		);

		return $email;
	}

	/**
	 * Customize password change confirmation email in Vietnamese.
	 *
	 * @param array<string, string> $email                Email array.
	 * @param array<string, mixed>  $user                 Original user array.
	 * @param array<string, mixed>  $userdata             Updated user array.
	 * @return array<string, string>
	 */
	public static function filter_password_change_email( array $email, array $user, array $userdata ): array {
		unset( $userdata );
		$username         = isset( $user['user_login'] ) ? (string) $user['user_login'] : 'bạn';
		$email['subject'] = '[Jannie Nguyen Academy] Xác nhận thay đổi mật khẩu thành công';
		$email['message'] = implode(
			"\r\n",
			array(
				sprintf( 'Xin chào %s,', $username ),
				'',
				'Mật khẩu tài khoản của bạn tại Jannie Nguyen Academy vừa được cập nhật thành công.',
				'Nếu bạn không thực hiện thao tác này, vui lòng liên hệ ngay với chúng tôi qua contact@jannienguyen.vn.',
				'',
				'Trân trọng,',
				'Jannie Nguyen Academy',
			)
		);

		return $email;
	}

	/**
	 * Customize email address change notification in Vietnamese.
	 *
	 * @param array<string, string> $email    Email array.
	 * @param array<string, mixed>  $user     Original user array.
	 * @param array<string, mixed>  $userdata Updated user array.
	 * @return array<string, string>
	 */
	public static function filter_email_change_email( array $email, array $user, array $userdata ): array {
		$username         = isset( $user['user_login'] ) ? (string) $user['user_login'] : 'bạn';
		$new_email        = isset( $userdata['user_email'] ) ? (string) $userdata['user_email'] : '';
		$email['subject'] = '[Jannie Nguyen Academy] Xác nhận cập nhật địa chỉ email';
		$email['message'] = implode(
			"\r\n",
			array(
				sprintf( 'Xin chào %s,', $username ),
				'',
				sprintf( 'Địa chỉ email liên kết với tài khoản Jannie Nguyen Academy của bạn vừa được chuyển sang: %s.', $new_email ),
				'Nếu bạn không thực hiện thay đổi này, vui lòng liên hệ ngay với chúng tôi qua contact@jannienguyen.vn.',
				'',
				'Trân trọng,',
				'Jannie Nguyen Academy',
			)
		);

		return $email;
	}

	/**
	 * Customize privacy data request email subject in Vietnamese.
	 *
	 * @param string $subject Default subject.
	 * @param string $sitename Site name.
	 * @return string
	 */
	public static function filter_privacy_email_subject( string $subject, string $sitename ): string {
		unset( $subject, $sitename );
		return '[Jannie Nguyen Academy] Xác nhận yêu cầu dữ liệu cá nhân';
	}

	/**
	 * Pre-authentication rate-limit check (priority 1).
	 * When an IP or username/email is locked out, short-circuits core password verification.
	 *
	 * @param WP_User|WP_Error|null $user     User or error from earlier callbacks.
	 * @param string                $username Username or email address.
	 * @param string                $password Password string.
	 * @return WP_User|WP_Error|null
	 */
	public static function enforce_rate_limit_pre_auth( $user, string $username, string $password ) {
		if ( '' === trim( $username ) && '' === $password ) {
			return $user;
		}

		if ( self::is_rate_limited( $username ) ) {
			remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
			remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );
			remove_filter( 'authenticate', 'wp_authenticate_application_password', 20 );

			return new WP_Error( 'jn_login_rate_limited', self::get_lockout_message() );
		}

		return $user;
	}

	/**
	 * Post-authentication rate-limit enforcement & anti-enumeration error sanitization (priority 999).
	 *
	 * @param WP_User|WP_Error|null $user     Authenticated user or error.
	 * @param string                $username Submitted username or email.
	 * @param string                $password Submitted password.
	 * @return WP_User|WP_Error|null
	 */
	public static function enforce_rate_limit_post_auth( $user, string $username, string $password ) {
		$trimmed_user = trim( $username );
		if ( '' === $trimmed_user && '' === $password ) {
			return $user;
		}

		if ( self::is_rate_limited( $trimmed_user ) ) {
			return new WP_Error( 'jn_login_rate_limited', self::get_lockout_message() );
		}

		if ( is_wp_error( $user ) && '' !== $trimmed_user ) {
			$error_codes = $user->get_error_codes();
			$ignored     = array( 'empty_username', 'empty_password' );
			if ( array_diff( $error_codes, $ignored ) ) {
				$locked = self::record_failed_attempt( $trimmed_user );
				if ( $locked ) {
					return new WP_Error( 'jn_login_rate_limited', self::get_lockout_message() );
				}

				return new WP_Error(
					'jn_invalid_credentials',
					self::get_generic_invalid_credentials_message()
				);
			}
		}

		return $user;
	}

	/**
	 * Enforce rate-limit lockout on WooCommerce My Account login form before WooCommerce processes credentials.
	 *
	 * @param WP_Error $validation_error Validation error object.
	 * @param string   $username         Submitted username or email.
	 * @param string   $password         Submitted password.
	 * @return WP_Error
	 */
	public static function filter_woocommerce_login_errors( $validation_error, string $username, string $password ) {
		unset( $password );

		if ( self::is_rate_limited( $username ) ) {
			return new WP_Error( 'jn_login_rate_limited', self::get_lockout_message() );
		}

		return $validation_error;
	}

	/**
	 * Clear rate-limit counters upon successful login.
	 *
	 * @param string $user_login Authenticated username.
	 * @return void
	 */
	public static function clear_rate_limit_on_success( string $user_login ): void {
		self::reset_rate_limit( $user_login );
	}

	/**
	 * Ensure login error strings never reveal whether a username/email exists in the database.
	 *
	 * @param string $errors Rendered HTML error string on the login screen.
	 * @return string
	 */
	public static function sanitize_login_error_display( string $errors ): string {
		if ( false !== strpos( $errors, self::get_lockout_message() ) ) {
			return self::get_lockout_message();
		}
		if ( '' !== trim( $errors ) ) {
			return self::get_generic_invalid_credentials_message();
		}
		return $errors;
	}

	/**
	 * Check whether the current client IP or submitted username/email has reached the lockout threshold.
	 *
	 * @param string $username Submitted username or email.
	 * @return bool
	 */
	public static function is_rate_limited( string $username = '' ): bool {
		$ip_key   = self::get_ip_transient_key();
		$ip_count = (int) get_transient( $ip_key );
		if ( $ip_count >= self::MAX_LOGIN_ATTEMPTS ) {
			return true;
		}

		$user_key = self::get_user_transient_key( $username );
		if ( '' !== $user_key ) {
			$user_count = (int) get_transient( $user_key );
			if ( $user_count >= self::MAX_LOGIN_ATTEMPTS ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Increment failed login counters for both client IP and username/email.
	 *
	 * @param string $username Submitted username or email.
	 * @return bool True if the lockout threshold has now been reached.
	 */
	public static function record_failed_attempt( string $username = '' ): bool {
		$ip_key   = self::get_ip_transient_key();
		$ip_count = (int) get_transient( $ip_key ) + 1;
		set_transient( $ip_key, $ip_count, self::LOCKOUT_SECONDS );

		$user_count = 0;
		$user_key   = self::get_user_transient_key( $username );
		if ( '' !== $user_key ) {
			$user_count = (int) get_transient( $user_key ) + 1;
			set_transient( $user_key, $user_count, self::LOCKOUT_SECONDS );
		}

		return ( $ip_count >= self::MAX_LOGIN_ATTEMPTS || $user_count >= self::MAX_LOGIN_ATTEMPTS );
	}

	/**
	 * Reset rate-limit counters for the current client IP and optional username/email.
	 *
	 * @param string $username Optional username or email to clear.
	 * @return void
	 */
	public static function reset_rate_limit( string $username = '' ): void {
		delete_transient( self::get_ip_transient_key() );
		$user_key = self::get_user_transient_key( $username );
		if ( '' !== $user_key ) {
			delete_transient( $user_key );
		}
	}

	/**
	 * Return the Vietnamese lockout message when >= 5 failed attempts occur within 15 minutes.
	 *
	 * @return string
	 */
	public static function get_lockout_message(): string {
		return __( 'Bạn đã nhập sai thông tin đăng nhập quá 5 lần. Vui lòng thử lại sau 15 phút để bảo vệ tài khoản.', 'jannie' );
	}

	/**
	 * Return the generic Vietnamese invalid credentials message (never reveals whether email/username exists).
	 *
	 * @return string
	 */
	public static function get_generic_invalid_credentials_message(): string {
		return __( 'Thông tin đăng nhập không chính xác. Vui lòng kiểm tra lại email và mật khẩu của bạn.', 'jannie' );
	}

	/**
	 * Restrict Application Passwords strictly to administrators (`manage_options`).
	 *
	 * @param bool    $available Whether application passwords are available for the user.
	 * @param WP_User $user      The user object being checked.
	 * @return bool
	 */
	public static function restrict_app_passwords_to_admins( bool $available, $user ): bool {
		unset( $available );
		if ( ! ( $user instanceof WP_User ) ) {
			return false;
		}
		return user_can( $user, 'manage_options' );
	}

	/**
	 * Compute an HMAC-hashed transient key for the client IP address.
	 *
	 * @return string
	 */
	private static function get_ip_transient_key(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
		return 'jn_rl_ip_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) ), 0, 24 );
	}

	/**
	 * Compute an HMAC-hashed transient key for a normalized username or email address.
	 *
	 * @param string $username Username or email.
	 * @return string Empty string when username is blank.
	 */
	private static function get_user_transient_key( string $username ): string {
		$normalized = strtolower( trim( $username ) );
		if ( '' === $normalized ) {
			return '';
		}
		return 'jn_rl_usr_' . substr( hash_hmac( 'sha256', $normalized, wp_salt( 'auth' ) ), 0, 24 );
	}
}
