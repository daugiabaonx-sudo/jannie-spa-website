<?php
/**
 * Testimonials Custom Post Type, Meta Fields, Block Bindings & Admin UI.
 *
 * Registers `jn_testimonial` (not publicly queryable on its own URL, but
 * accessible to core/query and core/post-meta block bindings) and seeds the
 * 3 wireframe testimonials verbatim with the owner verification note.
 *
 * @package JN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JN_Testimonials
 */
class JN_Testimonials {

	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	const POST_TYPE = 'jn_testimonial';

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_meta_fields' ), 6 );
		add_filter( 'is_post_type_viewable', array( __CLASS__, 'filter_post_type_viewable_for_blocks' ), 10, 2 );
		add_filter( 'query_loop_block_query_vars', array( __CLASS__, 'filter_query_loop_vars' ), 10, 2 );
		add_filter( 'rest_jn_testimonial_query', array( __CLASS__, 'filter_rest_query_order' ), 10, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'block_direct_singular_requests' ), 1 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta_boxes' ), 10, 2 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'filter_admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_admin_column' ), 10, 2 );
	}

	/**
	 * Register the `jn_testimonial` Custom Post Type.
	 *
	 * `public` and `publicly_queryable` are false so no individual permalink or
	 * archive page exists on the front-end.
	 *
	 * @return void
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => 'Cảm nhận học viên',
			'singular_name'      => 'Cảm nhận học viên',
			'menu_name'          => 'Cảm nhận (Testimonials)',
			'add_new'            => 'Thêm cảm nhận',
			'add_new_item'       => 'Thêm cảm nhận học viên',
			'edit_item'          => 'Chỉnh sửa cảm nhận',
			'new_item'           => 'Cảm nhận mới',
			'view_item'          => 'Xem cảm nhận',
			'search_items'       => 'Tìm kiếm cảm nhận',
			'not_found'          => 'Chưa có cảm nhận nào.',
			'not_found_in_trash' => 'Không có cảm nhận nào trong thùng rác.',
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => true,
				'rest_base'           => 'jn_testimonial',
				'query_var'           => false,
				'rewrite'             => false,
				'has_archive'         => false,
				'hierarchical'        => false,
				'menu_position'       => 26,
				'menu_icon'           => 'dashicons-format-quote',
				'supports'            => array(
					'title',
					'editor',
					'thumbnail',
					'custom-fields',
					'page-attributes',
				),
			)
		);
	}

	/**
	 * Register post meta fields exposed to REST and `core/post-meta` Block Bindings.
	 *
	 * @return void
	 */
	public static function register_meta_fields() {
		$string_fields = array(
			'jn_quote'           => 'Trích dẫn cảm nhận nguyên văn',
			'jn_headline'        => 'Câu tiêu đề của cảm nhận',
			'jn_client_name'     => 'Tên khách hàng / học viên',
			'jn_client_role'     => 'Chức danh / Cơ sở kinh doanh',
			'jn_client_city'     => 'Tỉnh / Thành phố',
			'jn_role_display'    => 'Chuỗi chức danh & thành phố hiển thị',
			'jn_related_package' => 'Gói Coaching liên quan',
			'jn_admin_note'      => 'Ghi chú quản trị (xác minh & xin phép khách)',
		);

		foreach ( $string_fields as $meta_key => $description ) {
			register_post_meta(
				self::POST_TYPE,
				$meta_key,
				array(
					'type'              => 'string',
					'description'       => $description,
					'single'            => true,
					'default'           => '',
					'sanitize_callback' => 'sanitize_textarea_field',
					// The admin note is staff-only (verification/consent status) and is edited in the PHP meta box,
					// so it is never exposed to REST, where published testimonials are readable anonymously.
					'show_in_rest'      => 'jn_admin_note' !== $meta_key,
					'auth_callback'     => static fn( $allowed, $key, $post_id ) => current_user_can( 'edit_post', (int) $post_id ),
				)
			);
		}

		register_post_meta(
			self::POST_TYPE,
			'jn_video_url',
			array(
				'type'              => 'string',
				'description'       => 'Video YouTube của cảm nhận (dạng https://www.youtube.com/watch?v=…)',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => array( __CLASS__, 'sanitize_video_url' ),
				'show_in_rest'      => true,
				'auth_callback'     => static fn( $allowed, $key, $post_id ) => current_user_can( 'edit_post', (int) $post_id ),
			)
		);

		register_post_meta(
			self::POST_TYPE,
			'jn_video_poster_id',
			array(
				'type'              => 'integer',
				'description'       => 'Ảnh bìa video tự host (ID trong Media Library)',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
				'auth_callback'     => static fn( $allowed, $key, $post_id ) => current_user_can( 'edit_post', (int) $post_id ),
			)
		);

		// 0 = no star rating: a testimonial never shows stars the client did not give (ADR-022).
		register_post_meta(
			self::POST_TYPE,
			'jn_rating',
			array(
				'type'              => 'integer',
				'description'       => 'Số sao đánh giá (1–5; 0 = không hiển thị sao)',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => array( __CLASS__, 'sanitize_rating' ),
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Clamp a rating to 0–5, where 0 means "no star rating".
	 *
	 * @param mixed $value Raw rating value.
	 * @return int
	 */
	public static function sanitize_rating( $value ) {
		return min( 5, max( 0, (int) $value ) );
	}

	/**
	 * Extract the 11-character video ID from a YouTube URL (youtube.com, youtu.be, youtube-nocookie.com).
	 *
	 * @param string $url Raw URL.
	 * @return string Video ID, or '' when the URL is not an http(s) YouTube video URL.
	 */
	public static function youtube_id( $url ) {
		$parts  = wp_parse_url( trim( (string) $url ) );
		$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
		$host   = strtolower( (string) ( $parts['host'] ?? '' ) );
		$hosts  = array( 'youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtube-nocookie.com' );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) || ! in_array( $host, $hosts, true ) ) {
			return '';
		}

		$path = (string) ( $parts['path'] ?? '' );
		$id   = '';
		if ( 'youtu.be' === $host ) {
			$id = ltrim( $path, '/' );
		} elseif ( preg_match( '#^/(?:embed|shorts|live)/([^/]+)#', $path, $match ) ) {
			$id = $match[1];
		} elseif ( '/watch' === $path ) {
			wp_parse_str( (string) ( $parts['query'] ?? '' ), $query );
			$id = (string) ( $query['v'] ?? '' );
		}
		return 1 === preg_match( '/^[A-Za-z0-9_-]{11}$/', $id ) ? $id : '';
	}

	/**
	 * Store a YouTube URL in its canonical watch form; anything else becomes ''.
	 *
	 * @param mixed $value Raw URL.
	 * @return string
	 */
	public static function sanitize_video_url( $value ) {
		$id = self::youtube_id( is_string( $value ) ? $value : '' );
		return '' === $id ? '' : 'https://www.youtube.com/watch?v=' . $id;
	}

	/**
	 * Allow `core/query` and `core/post-meta` Block Bindings to read `jn_testimonial`
	 * while keeping `public` and `publicly_queryable` false on the post type object.
	 *
	 * @param bool         $is_viewable Current viewability flag.
	 * @param WP_Post_Type $post_type   Post type object.
	 * @return bool
	 */
	public static function filter_post_type_viewable_for_blocks( $is_viewable, $post_type ) {
		if ( is_object( $post_type ) && self::POST_TYPE === $post_type->name ) {
			return true;
		}
		return $is_viewable;
	}

	/**
	 * Ensure Query Loop blocks targeting `jn_testimonial` sort deterministically
	 * by `menu_order` ASC, `ID` ASC so the 3 wireframe cards appear in exact order.
	 *
	 * @param array<string, mixed> $query_vars Constructed WP_Query vars.
	 * @param WP_Block             $block      Current `core/post-template` block instance.
	 * @return array<string, mixed>
	 */
	public static function filter_query_loop_vars( $query_vars, $block ) {
		$target_type = $block->context['query']['postType'] ?? '';
		if ( self::POST_TYPE === $target_type ) {
			$query_vars['post_type'] = self::POST_TYPE;
			$query_vars['orderby']   = array(
				'menu_order' => 'ASC',
				'ID'         => 'ASC',
			);
			$query_vars['order']     = 'ASC';
		}
		return $query_vars;
	}

	/**
	 * Default REST collection order for `jn_testimonial` in the Block Editor.
	 *
	 * @param array<string, mixed> $args    WP_Query arguments.
	 * @param WP_REST_Request      $request REST request.
	 * @return array<string, mixed>
	 */
	public static function filter_rest_query_order( $args, $request ) {
		if ( empty( $request->get_param( 'orderby' ) ) ) {
			$args['orderby'] = array(
				'menu_order' => 'ASC',
				'ID'         => 'ASC',
			);
			$args['order']   = 'ASC';
		}
		return $args;
	}

	/**
	 * Guarantee that direct front-end singular/archive requests for `jn_testimonial`
	 * never resolve publicly.
	 *
	 * @return void
	 */
	public static function block_direct_singular_requests() {
		if ( is_singular( self::POST_TYPE ) || is_post_type_archive( self::POST_TYPE ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}

	/**
	 * Register the admin metabox for editing testimonial fields and the verification note.
	 *
	 * @return void
	 */
	public static function register_meta_boxes() {
		add_meta_box(
			'jn-testimonial-details',
			'Thông tin Cảm nhận Học viên & Trạng thái Xác minh',
			array( __CLASS__, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the testimonial details metabox in WP Admin.
	 *
	 * @param WP_Post $post Current post object.
	 * @return void
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'jn_save_testimonial_meta', 'jn_testimonial_meta_nonce' );

		$quote      = (string) get_post_meta( $post->ID, 'jn_quote', true );
		$name       = (string) get_post_meta( $post->ID, 'jn_client_name', true );
		$role       = (string) get_post_meta( $post->ID, 'jn_client_role', true );
		$city       = (string) get_post_meta( $post->ID, 'jn_client_city', true );
		$rating     = self::sanitize_rating( get_post_meta( $post->ID, 'jn_rating', true ) );
		$package    = (string) get_post_meta( $post->ID, 'jn_related_package', true );
		$admin_note = (string) get_post_meta( $post->ID, 'jn_admin_note', true );
		$headline   = (string) get_post_meta( $post->ID, 'jn_headline', true );
		$video_url  = (string) get_post_meta( $post->ID, 'jn_video_url', true );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="jn_client_name">Tên khách hàng / học viên</label></th>
				<td><input type="text" id="jn_client_name" name="jn_client_name" value="<?php echo esc_attr( $name ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="jn_client_role">Chức danh / Cơ sở kinh doanh</label></th>
				<td><input type="text" id="jn_client_role" name="jn_client_role" value="<?php echo esc_attr( $role ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="jn_client_city">Tỉnh / Thành phố</label></th>
				<td><input type="text" id="jn_client_city" name="jn_client_city" value="<?php echo esc_attr( $city ); ?>" class="regular-text" placeholder="Hà Nội, TP.HCM..." /></td>
			</tr>
			<tr>
				<th scope="row"><label for="jn_rating">Xếp hạng sao (1–5)</label></th>
				<td>
					<select id="jn_rating" name="jn_rating">
						<option value="0" <?php selected( $rating, 0 ); ?>>Không hiển thị sao</option>
						<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
							<option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( $rating, $i ); ?>><?php echo esc_html( $i . ' sao' ); ?></option>
						<?php endfor; ?>
					</select>
					<p class="description">Chỉ chọn số sao khi khách thật sự cho điểm.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="jn_headline">Câu tiêu đề</label></th>
				<td><input type="text" id="jn_headline" name="jn_headline" value="<?php echo esc_attr( $headline ); ?>" class="large-text" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="jn_video_url">Video YouTube</label></th>
				<td>
					<input type="url" id="jn_video_url" name="jn_video_url" value="<?php echo esc_attr( $video_url ); ?>" class="large-text" placeholder="https://www.youtube.com/watch?v=…" />
					<p class="description">Chỉ nhận link YouTube; link khác sẽ bị bỏ.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="jn_related_package">Gói liên quan</label></th>
				<td>
					<select id="jn_related_package" name="jn_related_package">
						<option value="60-phut" <?php selected( $package, '60-phut' ); ?>>Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)</option>
						<option value="30-phut" <?php selected( $package, '30-phut' ); ?>>Gói Tư Vấn Cá Nhân 1:1 (30 Phút)</option>
						<option value="mentorship-3-thang" <?php selected( $package, 'mentorship-3-thang' ); ?>>Gói Master Mentorship (3 Tháng)</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="jn_quote">Trích dẫn nguyên văn (Quote)</label></th>
				<td><textarea id="jn_quote" name="jn_quote" rows="4" class="large-text"><?php echo esc_textarea( $quote ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="jn_admin_note">Ghi chú quản trị nội bộ</label></th>
				<td>
					<input type="text" id="jn_admin_note" name="jn_admin_note" value="<?php echo esc_attr( $admin_note ); ?>" class="large-text" />
					<p class="description">Ghi chú bắt buộc trước khi xuất bản: <strong>cần xác minh &amp; xin phép khách</strong></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save metabox fields on post save.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public static function save_meta_boxes( $post_id, $post ) {
		if ( ! isset( $_POST['jn_testimonial_meta_nonce'] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['jn_testimonial_meta_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'jn_save_testimonial_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$name  = isset( $_POST['jn_client_name'] ) ? sanitize_text_field( wp_unslash( $_POST['jn_client_name'] ) ) : '';
		$role  = isset( $_POST['jn_client_role'] ) ? sanitize_text_field( wp_unslash( $_POST['jn_client_role'] ) ) : '';
		$city  = isset( $_POST['jn_client_city'] ) ? sanitize_text_field( wp_unslash( $_POST['jn_client_city'] ) ) : '';
		$quote = isset( $_POST['jn_quote'] ) ? sanitize_textarea_field( wp_unslash( $_POST['jn_quote'] ) ) : '';
		$pkg   = isset( $_POST['jn_related_package'] ) ? sanitize_key( wp_unslash( $_POST['jn_related_package'] ) ) : '60-phut';
		$note  = isset( $_POST['jn_admin_note'] ) ? sanitize_text_field( wp_unslash( $_POST['jn_admin_note'] ) ) : '';
		$stars = isset( $_POST['jn_rating'] ) ? self::sanitize_rating( wp_unslash( $_POST['jn_rating'] ) ) : 0;
		$head  = isset( $_POST['jn_headline'] ) ? sanitize_text_field( wp_unslash( $_POST['jn_headline'] ) ) : '';
		$video = isset( $_POST['jn_video_url'] ) ? self::sanitize_video_url( wp_unslash( $_POST['jn_video_url'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_video_url() accepts only a YouTube video ID.

		$role_display = $role;
		if ( '' !== $city ) {
			$role_display = sprintf( '%s (%s)', $role, $city );
		}

		update_post_meta( $post_id, 'jn_client_name', $name );
		update_post_meta( $post_id, 'jn_client_role', $role );
		update_post_meta( $post_id, 'jn_client_city', $city );
		update_post_meta( $post_id, 'jn_role_display', $role_display );
		update_post_meta( $post_id, 'jn_quote', $quote );
		update_post_meta( $post_id, 'jn_related_package', $pkg );
		update_post_meta( $post_id, 'jn_admin_note', $note );
		update_post_meta( $post_id, 'jn_rating', $stars );
		update_post_meta( $post_id, 'jn_headline', $head );
		update_post_meta( $post_id, 'jn_video_url', $video );
	}

	/**
	 * Customize columns in the Testimonials admin list table.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public static function filter_admin_columns( $columns ) {
		return array(
			'cb'                 => $columns['cb'] ?? '<input type="checkbox" />',
			'title'              => 'Học viên / Khách hàng',
			'jn_role_display'    => 'Chức danh & Thành phố',
			'jn_rating'          => 'Đánh giá',
			'jn_related_package' => 'Gói liên quan',
			'jn_admin_note'      => 'Ghi chú xác minh',
			'date'               => $columns['date'] ?? 'Ngày',
		);
	}

	/**
	 * Render custom column values in the Testimonials admin list table.
	 *
	 * @param string $column  Column slug.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function render_admin_column( $column, $post_id ) {
		switch ( $column ) {
			case 'jn_role_display':
				echo esc_html( (string) get_post_meta( $post_id, 'jn_role_display', true ) );
				break;
			case 'jn_rating':
				$rating = self::sanitize_rating( get_post_meta( $post_id, 'jn_rating', true ) );
				echo esc_html( $rating > 0 ? $rating . '/5 ★' : '—' );
				break;
			case 'jn_related_package':
				echo esc_html( (string) get_post_meta( $post_id, 'jn_related_package', true ) );
				break;
			case 'jn_admin_note':
				$note = (string) get_post_meta( $post_id, 'jn_admin_note', true );
				echo '<strong>' . esc_html( $note ) . '</strong>';
				break;
		}
	}
}
