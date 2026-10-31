# Nhật Ký Quyết Định Kiến Trúc — ADR Log (`docs/jn/decisions.md`)

> **Dự án:** Website Jannie Nguyễn — Luxury Beauty Academy & Mentorship
> **Cập nhật lần cuối:** 2026-10-27 (JN Step 4 — Bổ sung ADR-005 & ADR-006)
> **Định dạng:** Architecture Decision Records (Bối cảnh → Quyết định → Hệ quả & Ràng buộc)

---

## ADR-001: Kiến Trúc Nền Tảng — WordPress 7.1.3 + Blocksy (Free) Child Theme + Gutenberg Core Blocks + WooCommerce HPOS + `jn-core`

- **Ngày quyết định:** 2026-10-27 (Step 0)
- **Trạng thái:** Đã chấp thuận (Approved — Ràng buộc cứng theo `.agent/rules/jn-project.md` §2)

### 1. Bối cảnh
Bản thiết kế wireframe trang Coaching (`stitch_jannie_nguyen_website_wireframe/code.html` và `DESIGN.md`) mang phong cách thẩm mỹ cao cấp (*"Warm Ivory & Quiet Luxury"*), sử dụng Tailwind Play CDN và font biểu tượng Material Symbols nặng ~4.0 MB. Mục tiêu của dự án là chuyển đổi thiết kế này sang hệ quản trị nội dung WordPress (`./wordpress`, phiên bản `7.1.3`) phục vụ quản lý nội dung, đơn hàng, lịch hẹn coaching và hồ sơ khách hàng, đồng thời đạt điểm tối đa trên Google PageSpeed Insights (Performance ≥ 95 trên Mobile, ≥ 99 trên Desktop, Accessibility = 100, Best Practices = 100, SEO = 100) và ẩn hoàn toàn dấu vết WordPress mặc định.

Nếu sử dụng các trình dựng trang truyền thống như Elementor hoặc các bộ thư viện block bên thứ ba (Spectra, Kadence Blocks, Stackable...), hệ thống sẽ sinh ra DOM sâu quá mức, tải hàng trăm KB CSS/JS không cần thiết và làm suy giảm nghiêm trọng chỉ số LCP/INP/CLS. Mặt khác, máy trạm phát triển hiện có sẵn Docker (`29.7.2`), Node (`v24.19.0`) và Chrome (`156.0`), nhưng chưa cài đặt PHP/MySQL/WP-CLI trực tiếp trên macOS.

### 2. Quyết định
1. **Môi trường phát triển:** Khởi chạy WordPress `7.1.3` qua **Docker Compose** (PHP 8.3 + MariaDB 11.4 + WP-CLI) gắn kết trực tiếp thư mục `./wordpress`.
2. **Giao diện (Presentation Layer):** Sử dụng **Blocksy (bản miễn phí từ `wordpress.org`)** làm parent theme kết hợp với child theme tùy chỉnh **`blocksy-jn`** (`/app/themes/blocksy-jn`). Tuyệt đối không dùng Blocksy Pro. Không cài Blocksy Companion trừ khi phát sinh một tính năng miễn phí bắt buộc nằm trong đó (và phải ghi nhận ADR bổ sung).
3. **Trình dựng bố cục (Layout Layer):** Chỉ sử dụng **Gutenberg Core Blocks**, **Block Style Variations**, **Block Patterns / Synced Patterns** và **3 custom blocks nội bộ** (`jn/icon`, `jn/rating`, `jn/booking-form`). Cấm hoàn toàn Elementor, mọi page builder khác, thư viện block bên thứ ba, hoặc dùng `core/html` / shortcode để dựng bố cục.
4. **Thương mại điện tử & Quản lý nghiệp vụ (Business Layer):**
   - Sử dụng **WooCommerce** với **HPOS (High-Performance Order Storage)** được bật mặc định và các block chuẩn Cart / Checkout / My Account.
   - Xây dựng plugin nghiệp vụ riêng **`jn-core`** (`/app/modules/jn-core`) gồm 4 phân hệ: `booking/`, `customers/`, `testimonials/`, `blocks/`. Các block trong `jn-core` dùng JavaScript thuần với biến toàn cục `wp.*` và `ServerSideRender`, không cần bước build phức tạp hay `node_modules` khi triển khai.
5. **Chính sách toàn trang (Site-wide Policies):** Đặt trong 2 Must-Use Plugins tại `/app/mu-plugins/`: `jn-hardening.php` (bảo mật & ẩn dấu vết WordPress) và `jn-performance.php` (tối ưu hiệu năng, gỡ bỏ bloatware mặc định).

### 3. Hệ quả & Ràng buộc
- **Tích cực:** Kiểm soát 100% cấu trúc DOM, kích thước CSS (< 50 KB nén) và JS (< 35 KB nén); không phụ thuộc vào bản quyền plugin trả phí; tương thích lâu dài với lộ trình phát triển cốt lõi của WordPress và WooCommerce HPOS.
- **Ràng buộc kỹ thuật:** Mọi hook, filter, hằng số hoặc thuộc tính block của WordPress, WooCommerce và Blocksy phải được kiểm chứng thực tế trong mã nguồn cài đặt (`grep`) trước khi sử dụng.

---

## ADR-002: Mô Hình Đặt Lịch Tư Vấn & Quản Lý Khách Hàng (`Request-Based Booking` Trên Nền WooCommerce HPOS)

- **Ngày quyết định:** 2026-10-27 (Step 0)
- **Trạng thái:** Đã chấp thuận (Approved — Theo `PROJECT CONTEXT CARD` và `.agent/rules/jn-project.md` §2, §3)

### 1. Bối cảnh
Theo wireframe trang Coaching (`code.html` dòng 435–555) và `PROJECT CONTEXT CARD`, dịch vụ Mentorship & Coaching của Jannie Nguyễn là dịch vụ tư vấn chuyên sâu giá trị cao gồm 3 gói (`1:1 Private Coaching`, `Career & Skill Upgrade`, `Business & Salon Mentorship`) dành cho 3 nhóm khách hàng (`Cá nhân`, `Thợ / Học viên`, `Chủ cơ sở thẩm mỹ, phun xăm, spa`). Quy trình tiếp nhận gồm **3 bước**:
1. Học viên gửi thông tin ứng tuyển qua biểu mẫu trực tuyến.
2. Trợ lý liên hệ trong vòng 24 giờ để xác nhận khung giờ phù hợp với lịch của Jannie.
3. Tiến hành phiên Coaching 1:1 trực tuyến (Zoom) hoặc trực tiếp tại Học viện.

Trong wireframe tĩnh, nút gửi chỉ gọi `setTimeout()` giả lập và phương thức `selectTier()` cắt 10 ký tự đầu của tên gói một cách thiếu ổn định. Dự án cần một luồng lưu trữ thực sự, an toàn, tích hợp chặt chẽ với quản lý đơn hàng và khách hàng của WooCommerce nhưng không ép khách hàng phải thanh toán ngay khi chưa chốt được lịch trống với trợ lý, cũng như không tự động tạo tài khoản đăng nhập gây rác cơ sở dữ liệu khi khách chỉ mới gửi yêu cầu tư vấn.

### 2. Quyết định
1. **Mô hình `Request-Based Booking` (Thanh toán sau khi xác nhận khung giờ):**
   - Khởi tạo 3 sản phẩm dịch vụ (Virtual WooCommerce Products) đại diện cho 3 gói Coaching (`1:1 Private Coaching`, `Career & Skill Upgrade`, `Business & Salon Mentorship`). Vì giá chính thức đang là `UNKNOWN` (`UNK-01`), hệ thống cho phép cấu hình chế độ hiển thị giá linh hoạt ("Liên hệ tư vấn" / "Theo báo giá sau thẩm định hồ sơ" hoặc hiển thị mức giá cụ thể ngay khi chủ dự án cung cấp).
   - Khi khách hàng điền và gửi biểu mẫu `jn/booking-form`:
     - Kiểm tra bảo mật tại máy chủ: xác thực Nonce/Signed Token, kiểm tra trường bẫy bot (honeypot), giới hạn tần suất gửi (rate limiting theo IP qua Transient), kiểm tra bắt buộc ô đồng ý chính sách bảo mật dữ liệu (consent checkbox), và làm sạch (sanitize) toàn bộ trường dữ liệu (`full_name`, `phone`, `email`, `package`, `segment`, `goals`, `preferred_contact`, `consent_timestamp`).
     - Sử dụng **WooCommerce Order & Customer CRUD API (`WC_Order`, tương thích 100% HPOS)** để tạo một đơn hàng/yêu cầu đặt lịch ở trạng thái chờ xác nhận lịch (`on-hold` hoặc trạng thái tùy chỉnh `wc-booking-request` thuộc phân hệ `jn-core/booking/`), gắn đầy đủ мета-data: gói coaching đã chọn, nhóm đối tượng khách hàng (`segment`), mục tiêu/khó khăn (`goals`), trạng thái liên hệ của trợ lý, khung giờ hẹn dự kiến và thời điểm đồng ý bảo mật.
     - **Tuyệt đối không tự động tạo tài khoản người dùng WordPress (`wp_users`)** khi khách gửi form tư vấn. Khách hàng chỉ tạo tài khoản khi chủ động đăng ký tại trang `My Account` hoặc trong bước thanh toán khi có nhu cầu.
2. **Luồng xử lý của Trợ lý & Quản trị viên:**
   - Hệ thống gửi email thông báo tức thì cho trợ lý/quản trị viên và email xác nhận đã tiếp nhận yêu cầu cho khách hàng.
   - Trong trang quản trị (`jn-core`), trợ lý có thể lọc yêu cầu theo trạng thái, gói dịch vụ, nhóm khách hàng (`Cá nhân`, `Thợ / Học viên`, `Chủ cơ sở thẩm mỹ, phun xăm, spa`), ghi chú nội bộ, cập nhật khung giờ đã chốt và chuyển trạng thái sang chờ thanh toán / đã xác nhận lịch hẹn.
3. **Cơ chế chọn trước gói dịch vụ (`Tier Pre-selection`):**
   - Thay thế hàm cắt chuỗi 10 ký tự trong wireframe bằng định danh slug chuẩn (`private-1on1`, `career-skill-upgrade`, `business-salon-mentorship`). Khi người dùng bấm nút CTA ở thẻ gói (hoặc truy cập URL có `?tier=career-skill-upgrade#booking-form`), script vanilla JS nhẹ sẽ chọn chính xác `<option>` tương ứng trong `#package-select`, cuộn mượt có bù chiều cao header cố định (`scroll-margin-top: 112px`) và đặt tiêu điểm (focus) vào biểu mẫu.

### 3. Hệ quả & Ràng buộc
- **Tích cực:** Toàn bộ dữ liệu yêu cầu đặt lịch và lịch sử khách hàng nằm tập trung trong hệ sinh thái WooCommerce HPOS, không phát sinh bảng dữ liệu rời rạc khó bảo trì, tuân thủ quy định bảo vệ dữ liệu cá nhân và phản ánh đúng quy trình vận hành thực tế của Học viện.
- **Kiểm thử:** Toàn bộ logic tạo yêu cầu, phân nhóm khách hàng, kiểm tra nonce/rate-limit và quyền truy cập quản trị sẽ được viết kiểm thử tự động (PHPUnit + Playwright E2E).

---

## ADR-003: Giải Pháp Font Chữ Tiêu Đề Tiếng Việt (`HEADING_FONT = Playfair Display`) & Tự Host WOFF2

- **Ngày quyết định:** 2026-10-27 (Step 0 — Đã được chủ dự án phê duyệt chính thức)
- **Trạng thái:** Đã chấp thuận (Approved — **`Playfair Display`** cho toàn bộ website)

### 1. Bối cảnh
Bản thiết kế gốc (`code.html` và `DESIGN.md`) chỉ định font **`Bodoni Moda`** từ Google Fonts cho toàn bộ tiêu đề (`H1`, `H2`, `H3` và các con số thống kê lớn). Tuy nhiên, qua kiểm tra bảng mã Unicode và dựng trang đối chiếu thực tế (`docs/jn/font-specimen.html`, chụp ảnh tại `docs/jn/font-specimen.png`), gói `latin-ext` của **`Bodoni Moda` hoàn toàn không chứa khối ký tự tiếng Việt mở rộng `U+1EA0–U+1EF9`** (như `ồ`, `ợ`, `ế`, `ậ`, `ữ`, `ứ`, `ể`, `ả`, `ẩ`, `ẫ`...). Hệ quả là mọi tiêu đề tiếng Việt trên trang bị trình duyệt trộn lẫn hai phông chữ khác nhau ngay trong cùng một từ (các chữ cái cơ bản hiển thị bằng Bodoni Moda, còn chữ cái có dấu kép rơi về `Times New Roman`), phá vỡ tính thẩm mỹ cao cấp của thương hiệu.

Ngoài ra, theo `.agent/rules/jn-project.md` §3 & §6, trang web chính thức không được phép tải font từ Google Fonts CDN để đảm bảo quyền riêng tư và tối ưu tốc độ tải trang (tránh kết nối DNS/TLS bên thứ ba).

### 2. Quyết định
1. **Loại bỏ `Bodoni Moda` khỏi danh sách sử dụng thực tế** do thiếu glyph tiếng Việt.
2. **Chốt sử dụng `Playfair Display` làm `HEADING_FONT` cho toàn bộ website** theo quyết định phê duyệt của chủ dự án sau khi đối chiếu bảng mẫu `docs/jn/font-specimen.png` (mang phong cách editorial serif sang trọng, chữ nghiêng `italic` bay bổng và hỗ trợ đầy đủ 100% dấu tiếng Việt).
3. **Quy trình đóng gói tự host (Self-hosted WOFF2 tại Step 2):**
   - Dùng `fontTools` (`pyftsubset`) gộp dải ký tự `latin` + `vietnamese` của **`Playfair Display`** và **`Geist`** thành **1 file `.woff2` duy nhất cho mỗi kiểu chữ**, đảm bảo tổng số file font trên mỗi trang nội dung `<= 3` file theo ngân sách hiệu năng cứng (§5).
   - Cấu hình `font-display: swap` cùng fallback khớp chỉ số kích thước (`size-adjust`, `ascent-override`, `descent-override`) để chỉ số `CLS` luôn dưới `0.02`.

### 3. Hệ quả & Ràng buộc
- **Tích cực:** Khắc phục triệt để lỗi vỡ phông chữ tiếng Việt trên mọi thiết bị; loại bỏ hoàn toàn yêu cầu mạng tới `fonts.googleapis.com` và `fonts.gstatic.com`.
- **Hành động tiếp theo:** Đóng gói các file `.woff2` của `Playfair Display` và `Geist` ở Step 2.

---

## ADR-004: Phạm Vi Ẩn Dấu Vết WordPress (`Hide-WordPress Scope` & Cấu Trúc Đường Dẫn Tùy Chỉnh)

- **Ngày quyết định:** 2026-10-27 (Step 0)
- **Trạng thái:** Đã chấp thuận (Approved — Theo `.agent/rules/jn-project.md` §5)

### 1. Bối cảnh
Yêu cầu cốt lõi của dự án là ẩn toàn bộ dấu vết mặc định của WordPress ("Hide WordPress") đối với khách truy cập chưa đăng nhập nhằm tăng tính chuyên nghiệp của thương hiệu và giảm bề mặt tấn công tự động (automated bot scanning), đồng thời vẫn phải đảm bảo khả năng cập nhật an toàn cho WordPress Core, parent theme Blocksy và các plugin bên thứ ba (như WooCommerce).

### 2. Quyết định
1. **Tái cấu trúc đường dẫn công khai ngay từ Step 1 (`wp-config.php` & Web Server Rewrites):**
   - Đổi thư mục nội dung từ `/wp-content` sang **`/app`** (`WP_CONTENT_DIR`, `WP_CONTENT_URL`).
   - Đổi thư mục plugin từ `/wp-content/plugins` sang **`/app/modules`** (`WP_PLUGIN_DIR`, `WP_PLUGIN_URL`, `PLUGINDIR`).
   - Đổi thư mục tải lên phương tiện từ `/wp-content/uploads` sang **`/media`** (`upload_path`, `upload_url_path`).
   - Đổi tiền tố REST API từ `/wp-json` sang **`/api`** thông qua filter `rest_url_prefix`.
   - Sử dụng tiền tố bảng cơ sở dữ liệu ngẫu nhiên (ví dụ: `jn7x_`, tuyệt đối không dùng `wp_`).
   - Đặt đường dẫn đăng nhập tùy chỉnh tại **`/<WP_LOGIN_SLUG>`** (qua `WPS Hide Login` kết hợp quy tắc bảo vệ trong `jn-hardening.php`).
2. **Bảo toàn khả năng cập nhật trên ổ đĩa (Disk Updatability Rule):**
   - **Không đổi tên thư mục trên đĩa** của parent theme (`blocksy`) hay các plugin bên thứ ba (`woocommerce`, `wps-hide-login`, `two-factor`...) bên trong `/app/themes/` và `/app/modules/` để trình cập nhật của WordPress/WP-CLI vẫn hoạt động bình thường.
   - Thay vào đó, sử dụng quy tắc rewrite trên máy chủ web (`.htaccess` / cấu hình Apache/Nginx) và bộ lọc URL tài nguyên trong `jn-hardening.php` để ánh xạ các đường dẫn tài nguyên tĩnh ra ngoài thành các đường dẫn trung lập (không chứa chuỗi `wp-content`, `wp-includes`, `blocksy`, hay `?ver=X.Y.Z` trong mã nguồn HTML trả về cho khách).
3. **Khóa chặt các điểm rò rỉ thông tin (Hardening Controls tại Step 1 & Step 7):**
   - Khi chưa đăng nhập: truy cập `/wp-admin`, `/wp-login.php`, `/xmlrpc.php`, `/readme.html`, `/license.txt`, `/wp-config-sample.php` đều trả về mã trạng thái **`404 Not Found`** (hoặc bị chặn truy cập hoàn toàn, tuyệt đối không chuyển hướng `302` tới trang đăng nhập mới `/<WP_LOGIN_SLUG>`).
   - Gỡ bỏ thẻ `<meta name="generator">`, `X-Pingback`, link header `rel="https://api.w.org/"` nếu cần ẩn, tắt tính năng liệt kê tác giả qua `/api/wp/v2/users` (trả về `401`/`403`) và `?author=N` (trả về `404`).
   - Tắt chỉnh sửa file trong trang quản trị (`DISALLOW_FILE_EDIT = true`), ẩn bình luận mặc định trên các trang tĩnh/dịch vụ, và thiết lập đầy đủ các HTTP Security Headers.

### 3. Hệ quả & Ràng buộc
- **Tích cực:** Mã nguồn HTML và các đường dẫn tài nguyên công khai hoàn toàn sạch dấu vết `wp-content`, `wp-includes`, `wp-json`, `wp-login.php`, `xmlrpc.php`; trình quét tự động không thể dò tìm tài khoản quản trị hay đường dẫn đăng nhập; việc nâng cấp WordPress Core và WooCommerce vẫn diễn ra trơn tru.
- **Kiểm thử bắt buộc (Step 7):** Phải vượt qua bộ kịch bản kiểm thử tự động kiểm tra mã phản hồi HTTP (`404`/`403`) và quét toàn bộ DOM HTML của khách chưa đăng nhập để xác nhận không rò rỉ chữ ký WordPress.

---

## ADR-005: Vô Hiệu Hóa Mặc Định Tính Năng Theo Dõi Nguồn Đơn Hàng (`WooCommerce Order Attribution`)

- **Ngày quyết định:** 2026-10-27 (Step 4)
- **Trạng thái:** Đã chấp thuận (Approved — Theo `.agent/rules/jn-project.md` §5 & `PROJECT CONTEXT CARD`)

### 1. Bối cảnh
Từ WooCommerce 8.5+, tính năng **Order Attribution** (`woocommerce_feature_order_attribution_enabled`) được bật mặc định trên mọi cửa hàng mới. Khi bật, WooCommerce tự động chèn 2 tập tin JavaScript (`sourcebuster.min.js` và `order-attribution.min.js`, tổng cộng ~18 KB raw) cùng thẻ `<wc-order-attribution-inputs>` trên **tất cả các trang giao diện khách** (bao gồm cả các trang nội dung tĩnh như `/coaching/`, `/my-story/`, `/booking/`), đồng thời ghi một loạt cookie theo dõi phiên truy cập của người dùng (`sbjs_migrations`, `sbjs_current`, `sbjs_first`, `sbjs_udata`, `sbjs_session`...).

Trong khi đó:
1. **`PROJECT CONTEXT CARD`** quy định rõ: `TRACKING / CHAT WIDGETS: none`.
2. **Quy tắc Hiệu năng (§5 & Task 10 Step 4)** yêu cầu: Không tải bất kỳ tài nguyên CSS/JS nào của WooCommerce trên các trang nội dung (`/coaching/`, `/my-story/`, `/booking/`...) và giữ ngân sách JS tổng cộng dưới ngưỡng tối thiểu.
3. **Quyền riêng tư (Privacy by Default):** Không đặt cookie theo dõi hành vi trên trình duyệt của khách khi chưa có yêu cầu phân tích tiếp thị từ chủ dự án.

### 2. Quyết định
1. **Tắt mặc định tính năng Order Attribution (`woocommerce_feature_order_attribution_enabled = no`)** ngay trong quá trình cấu hình WooCommerce ở Step 4.
2. **Chặn đăng ký script dự phòng trong `jn-performance.php`:** Ngay cả khi một bản cập nhật WooCommerce tương lai kiểm tra lại cờ này, `jn-performance.php` đảm bảo không nạp `sourcebuster-js` hay `wc-order-attribution` trên giao diện khách.

### 3. Hệ quả & Ràng buộc
- **Tích cực:** Loại bỏ hoàn toàn 2 request JS và toàn bộ cookie `sbjs_*` trên mọi trang của website; tuân thủ tuyệt đối chính sách không theo dõi (`TRACKING: none`) và ngân sách hiệu năng Lighthouse Mobile ≥ 95.
- **Khả năng mở rộng:** Nếu sau này chủ dự án muốn bật theo dõi nguồn chiến dịch UTM trên trang Thanh toán, có thể bật lại có điều kiện hoặc đọc trực tiếp tham số UTM ở phía máy chủ mà không cần tải `sourcebuster.min.js` trên toàn trang.

---

## ADR-006: Chọn Trang `"My Favorite Product"` (`/my-favorite-product/`) Làm Trang Cửa Hàng WooCommerce (`woocommerce_shop_page_id`) Nhưng Giữ Nguyên Bố Cục Gutenberg

- **Ngày quyết định:** 2026-10-27 (Step 4)
- **Trạng thái:** Đã chấp thuận (Approved — Step 4 Task 6)

### 1. Bối cảnh
Trong sơ đồ điều hướng của website Jannie Nguyễn (`docs/jn/wireframe-spec.md` mục `2a`), không có mục nào mang tên `"Shop"` hay `"Cửa hàng"` chung chung. Thay vào đó, 3 nhóm sản phẩm/dịch vụ được phân bổ vào 3 trang chuyên biệt theo đúng cấu trúc thương hiệu cá nhân:
- **Dịch vụ Cố vấn 1:1:** Trang `/coaching/` (chỉ đăng ký qua biểu mẫu tư vấn, ẩn khỏi danh mục mua trực tiếp).
- **Khóa học:** Trang `/course/` (danh mục `"Khóa học"`).
- **Sản phẩm khuyên dùng:** Trang **`"My Favorite Product"` (`/my-favorite-product/`)** hiển thị các sản phẩm thuộc danh mục `"Sản phẩm yêu thích"` bằng pattern tiêu đề phân đoạn (`jannie/page-intro`) kết hợp khối **WooCommerce Product Collection (`woocommerce/product-collection`)**.

Nếu để `woocommerce_shop_page_id` trống hoặc trỏ sang một trang `/shop/` riêng biệt không có trên thanh điều hướng:
- Nút *"Quay lại cửa hàng"* (`Return to shop` / `Browse products`) bên trong khối **WooCommerce Empty Cart (`woocommerce/empty-cart-block`)**, trang Tài khoản (`My Account → Orders` khi chưa có đơn hàng) và các đường dẫn breadcrumb của WooCommerce sẽ trỏ về trang `/shop/` lạ lẫm hoặc trả về `404`.
- Ngược lại, nếu gán `woocommerce_shop_page_id` vào `/my-favorite-product/` mà không kiểm soát template archive của WooCommerce, WordPress sẽ coi `/my-favorite-product/` là `is_post_type_archive( 'product' )` và thay thế toàn bộ nội dung khối Gutenberg của trang bằng vòng lặp archive PHP mặc định của theme (làm mất khối `jannie/page-intro` và cấu hình `woocommerce/product-collection`).

### 2. Quyết định
1. **Gán `woocommerce_shop_page_id` trỏ chính thức vào trang `"My Favorite Product"` (`/my-favorite-product/`).**
2. **Bảo toàn 100% nội dung Gutenberg của trang `/my-favorite-product/`:**
   - Khi truy cập `/my-favorite-product/`, hệ thống hiển thị nguyên vẹn nội dung khối Gutenberg đã dựng trên trang (`jannie/page-intro` + `woocommerce/product-collection` lọc theo danh mục `"Sản phẩm yêu thích"` kèm khối trạng thái trống thương hiệu hóa), thay vì để template archive sản phẩm mặc định của WooCommerce/Blocksy ghi đè.
   - Bất kỳ đường dẫn `/shop/` hoặc `/cua-hang/` mặc định nào (nếu có) đều được chuyển hướng `301` sạch sẽ về `/my-favorite-product/`.

### 3. Hệ quả & Ràng buộc
- **Tích cực:**
  - Mọi liên kết *"Quay lại cửa hàng"* từ Giỏ hàng trống (`/gio-hang/`), Tài khoản (`/tai-khoan/orders/`) và hệ thống WooCommerce đều dẫn khách hàng về đúng trang **`/my-favorite-product/`** có trong thanh điều hướng chính.
  - Chủ dự án có thể chỉnh sửa trực quan tiêu đề, mô tả mở đầu và lưới sản phẩm của trang `"My Favorite Product"` ngay trong trình soạn thảo Gutenberg (`Block Editor`) giống hệt các trang nội dung khác.
  - Các gói `"Coaching & Mentorship"` (`catalog_visibility = hidden`, `status = draft`) không bao giờ bị rò rỉ ra trang `"My Favorite Product"`.

---

## ADR-007: Lựa Chọn Mô Hình Lưu Trữ Đặt Lịch — Private CPT `jn_booking` Kết Hợp WooCommerce HPOS (`WC_Order` & `WC_Customer`)

- **Ngày quyết định:** 2026-10-27 (Step 5)
- **Trạng thái:** Đã chấp thuận (Approved — Step 5 Booking Engine)

### 1. Bối cảnh
Theo quy trình tiếp nhận 3 bước của wireframe (`1. Gửi yêu cầu thông tin → 2. Trợ lý xác nhận khung giờ trong 24 giờ → 3. Bước vào phiên làm việc 1:1`) và `PROJECT CONTEXT CARD` (`BOOKING MODEL: request-based`, `PAYMENT POLICY: pay after the assistant confirms the slot`, `AUTO-CREATE CUSTOMER ACCOUNT ON BOOKING: no`), hệ thống cần lưu trữ hồ sơ yêu cầu đặt lịch, quản lý vòng đời trạng thái (`Chờ xác nhận` → `Đã xác nhận lịch` → `Hoàn thành` | `Vắng mặt`, hoặc `Đã hủy`), hỗ trợ lộ trình `Master Mentorship` (1 đơn hàng ghi danh liên kết tới tối đa 12 buổi hẹn 1:1), và dẫn xuất trạng thái thanh toán từ đơn hàng WooCommerce.

Hai phương án lưu trữ chính được đặt lên bàn cân:
1. **Phương án A — Private Custom Post Type `jn_booking` + Post Meta chuẩn hóa, liên kết 2 chiều với `WC_Order` (HPOS) và `WC_Customer`.**
2. **Phương án B — Tạo bảng cơ sở dữ liệu tùy chỉnh riêng (`wp_jn_bookings`, `wp_jn_booking_meta`).**

### 2. Quyết định
Chọn **Phương án A (Private CPT `jn_booking` kết hợp WooCommerce HPOS)** vì các lý do kỹ thuật sau:
1. **Tách bạch Hồ sơ Tư vấn (Intake/Scheduling) và Chứng từ Tài chính (`WC_Order`):**
   - Khi khách hàng mới gửi biểu mẫu (`Chờ xác nhận`), chưa có khung giờ chốt và chưa phát sinh nghĩa vụ thanh toán. Việc lưu yêu cầu vào `jn_booking` và chỉ khởi tạo `WC_Order` (trạng thái `pending`) khi trợ lý xác nhận lịch (`Đã xác nhận lịch`) giúp báo cáo doanh thu và danh sách đơn hàng HPOS của WooCommerce không bị nhiễu bởi các yêu cầu khảo sát chưa chốt hoặc trùng lặp.
   - Với gói `Master Mentorship (3 Tháng)`, 1 đơn hàng `WC_Order` duy nhất được liên kết với tối đa 12 bản ghi buổi hẹn `jn_booking` (sử dụng quan hệ phân cấp tự nhiên `post_parent` của CPT), cho phép trợ lý xếp lịch, gửi thư mời `.ics` và điểm danh từng buổi (`Hoàn thành` / `Vắng mặt`) độc lập mà không sinh thêm đơn hàng giả.
2. **Tận dụng Hạ tầng Quản trị, Phân quyền & Quyền riêng tư Bản địa của WordPress:**
   - CPT `jn_booking` tích hợp trực tiếp với `WP_List_Table`, bộ lọc truy vấn, thao tác hàng loạt (`Bulk Actions`), cơ chế ánh xạ quyền chi tiết (`capability_type => array( 'jn_booking', 'jn_bookings' )`, `map_meta_cap => true`) cho vai trò `"Trợ lý lịch hẹn"` (`jn_booking_assistant`), và công cụ Xuất/Xóa dữ liệu cá nhân của WordPress (`wp_privacy_personal_data_exporters` / `erasers`) mà không phải duy trì hệ thống migration `dbDelta` riêng.
3. **Phù hợp Tuyệt đối với Quy mô Dữ liệu & Ngân sách Hiệu năng:**
   - Học viện Jannie Nguyễn vận hành theo mô hình cố vấn 1:1 cao cấp giới hạn số lượng học viên mỗi tháng (quy mô vài trăm đến vài nghìn lịch hẹn mỗi năm). Với quy mô này, truy vấn trên bảng `posts` + các khóa meta định danh (`_jn_confirmed_start_ts`, `_jn_customer_phone`, `_jn_customer_email`, `_jn_order_id`, `_jn_package_product_id`) đạt tốc độ `< 2 ms`, không mang lại lợi ích thực tế nào nếu đánh đổi bằng độ phức tạp của bảng SQL riêng.
4. **Cô lập Bảo mật Tuyệt đối:**
   - `jn_booking` được đăng ký với `public => false`, `publicly_queryable => false`, `exclude_from_search => true`, `show_in_nav_menus => false`, `has_archive => false`, `rewrite => false`, `query_var => false`, `show_in_rest => false` và loại trừ tường minh khỏi XML Sitemaps (`wp_sitemaps_post_types`).

### 3. Hệ quả & Ràng buộc
- **Tích cực:** Kiến trúc gọn sạch, tuân thủ 100% chuẩn WordPress & WooCommerce HPOS CRUD, hỗ trợ đầy đủ 12 buổi Mentorship trên 1 đơn hàng và dễ dàng bảo trì lâu dài.
- **Ràng buộc:** Mọi thao tác chuyển trạng thái lịch hẹn phải đi qua tầng dịch vụ `JN_Booking_Service` để kiểm tra máy trạng thái ở phía máy chủ và ghi nhận đầy đủ vào `_jn_status_timeline`.

---

## ADR-008: Xây Dựng Màn Hình Quản Trị `"Khách hàng"` Chuyên Biệt (`admin.php?page=jn-customers`) Thay Vì Chỉ Lọc Màn Hình `Users`

- **Ngày quyết định:** 2026-10-27 (Step 6)
- **Trạng thái:** Đã chấp thuận (Approved — Step 6 Customer Management & Staff Accounts)

### 1. Bối cảnh
Yêu cầu của Step 6 đặt ra bài toán: *"Staff can see and manage every customer — whether they came from a booking, an order or a registration — in one place, and staff accounts follow least privilege"* và cho phép lựa chọn kiến trúc: *"Khách hàng admin screen (dedicated screen or the Users screen filtered to customers — ADR)"*.

Theo `PROJECT CONTEXT CARD`:
- **`AUTO-CREATE CUSTOMER ACCOUNT ON BOOKING: no`** — Khách hàng gửi yêu cầu đặt lịch tư vấn từ biểu mẫu trực tuyến hoặc thanh toán đơn hàng khách vãng lai (`Guest Checkout`) **không** tự động sinh bản ghi `WP_User` trong bảng `wp_users`.
- Vai trò **`Trợ lý lịch hẹn` (`jn_booking_assistant`)** cần xem, tìm kiếm theo mọi định dạng SĐT (`0908…`, `+84908…`), phân loại khách hàng, ghi chú nội bộ và tạo lịch hẹn điện thoại/trực tiếp (`Walk-in`), nhưng theo nguyên tắc đặc quyền tối thiểu (`Least Privilege`), trợ lý **không** được cấp quyền `list_users` / `edit_users` trên màn hình `users.php` của WordPress (nơi chứa cả tài khoản Quản trị và Nhân sự).

### 2. Quyết định
1. **Xây dựng màn hình quản trị chuyên biệt `"Khách hàng"` (`admin.php?page=jn-customers`, điều khiển bởi quyền `manage_jn_customers`):**
   - Hợp nhất trực tiếp cả **3 nguồn khách hàng** vào một danh bạ duy nhất:
     1. Khách hàng đã đăng ký tài khoản (`WP_User` / `WC_Customer`, khóa `user:<ID>`).
     2. Khách hàng vãng lai từ lịch hẹn (`jn_booking` có `_jn_customer_user_id = 0`, khóa `guest:<email_hoặc_sđt>`).
     3. Khách hàng vãng lai từ đơn hàng WooCommerce HPOS (`WC_Order` có `customer_id = 0`, khóa `guest:<email_hoặc_sđt>`).
   - Cung cấp trang **Chi tiết khách hàng** (`admin.php?page=jn-customers&action=view&customer_key=...`) hiển thị đầy đủ: Hồ sơ CRM 10 trường, Cảnh báo trùng lặp SĐT/Email (`jn_customer_duplicate_log`), biểu mẫu **"Tạo lịch hẹn"** nhanh cho yêu cầu qua điện thoại/trực tiếp, Lịch sử lịch hẹn, Lịch sử đơn hàng HPOS kèm liên kết, Dòng thời gian Ghi chú nội bộ (chỉ nhân sự thấy), và Nhật ký đồng ý bảo mật (chỉ đọc).
2. **Đồng bộ hai chiều (`Bidirectional Sync`) với WooCommerce Billing & `user-edit.php`:**
   - Đối với khách hàng đã có tài khoản `WP_User`, mọi cập nhật trên màn hình `"Khách hàng"` (Họ tên, SĐT `+84…`, Email, Tên cơ sở, Tỉnh/Thành) đều đồng bộ tức thì sang `WC_Customer` (`billing_first_name`, `billing_phone`, `billing_email`, `billing_company`, `billing_city`, `billing_state`) để báo cáo **WooCommerce Customers / Analytics** luôn nhất quán 100%.
   - Đồng thời vẫn hiển thị các trường CRM trên màn hình `user-edit.php` dành cho Quản trị viên và Quản lý cửa hàng.
   - Khi khách vãng lai đăng ký hoặc đăng nhập với cùng email đã xác thực, toàn bộ lịch hẹn vãng lai, đơn hàng HPOS vãng lai và hồ sơ CRM vãng lai (`jn_guest_crm_profiles`) được tự động gắn kết và hợp nhất vào tài khoản `WP_User`.

### 3. Hệ quả & Ràng buộc
- **Tích cực:**
  - Nhân sự nhìn thấy 100% khách hàng (kể cả khách đặt lịch chưa tạo tài khoản) tại một nơi duy nhất mà không vi phạm chính sách `AUTO-CREATE CUSTOMER ACCOUNT ON BOOKING: no`.
  - Đảm bảo đặc quyền tối thiểu: `Trợ lý lịch hẹn` (`jn_booking_assistant`) làm việc trọn vẹn trên màn hình `"Khách hàng"` mà không cần quyền truy cập bảng `users.php` của hệ thống.
- **Ràng buộc:** Tầng dịch vụ `JN_Customer_Service` và `JN_Customer_Profile` chịu trách nhiệm giữ đồng bộ giữa hồ sơ CRM và `WC_Customer`.

---

## ADR-009: Chính Sách Lưu Trữ Lịch Hẹn Khách Vãng Lai Đã Hủy (`Cancelled Guest Bookings Retention — Mặc Định Tắt`)

- **Ngày quyết định:** 2026-10-27 (Step 6)
- **Trạng thái:** Đã chấp thuận — Ghi nhận là Quyết định của Chủ sở hữu (`Owner Decision`)

### 1. Bối cảnh
Yêu cầu về Quyền riêng tư (Task 7 — Step 6) quy định: *"Privacy: the exporter and eraser include the CRM fields; a retention setting for cancelled guest bookings (default off, logged as an owner decision)"*.

Đối với các yêu cầu đặt lịch của khách vãng lai (`_jn_customer_user_id = 0`) đã chuyển sang trạng thái **`Đã hủy` (`jn_cancelled`)**:
- Một mặt, Học viện có thể muốn giữ lại lịch sử liên hệ để đối chiếu khi khách hàng liên hệ lại sau vài tháng.
- Mặt khác, theo nguyên tắc tối thiểu hóa dữ liệu cá nhân (Data Minimization), chủ sở hữu cần có công tắc cấu hình để tự động ẩn danh hóa dữ liệu cá nhân (PII) của các lịch hẹn vãng lai đã hủy sau một khoảng thời gian nhất định nếu muốn.

### 2. Quyết định
1. **Bổ sung cấu hình Lưu trữ lịch hẹn vãng lai đã hủy trong `Lịch hẹn → Cài đặt` (`JN_Booking_Settings`):**
   - `retention_cancelled_guest_enabled`: **Mặc định `'no'` (Tắt)**.
   - `retention_cancelled_guest_days`: **Mặc định `90` ngày** (khi bật).
2. **Ghi nhật ký quyết định của Chủ sở hữu (`jn_owner_decisions_log`):**
   - Trạng thái mặc định tắt (`default off`) và mọi thay đổi bật/tắt cấu hình này trên trang Cài đặt đều được ghi nhận có dấu thời gian (`Asia/Ho_Chi_Minh`) và người thực hiện vào `wp_options` (`jn_owner_decisions_log`).
3. **Tác vụ định kỳ (`jn_daily_cancelled_guest_booking_cleanup`):**
   - Khi `retention_cancelled_guest_enabled === 'no'`, tác vụ WP-Cron hàng ngày không thực hiện bất kỳ thao tác xóa/ẩn danh nào.
   - Khi Chủ sở hữu chủ động bật (`'yes'`), các bản ghi `jn_booking` có trạng thái `jn_cancelled`, `_jn_customer_user_id = 0` và cũ hơn số ngày cấu hình sẽ được tự động ẩn danh hóa thông tin cá nhân (`JN_Booking_Privacy::anonymize_booking_post`) và xóa hồ sơ vãng lai tương ứng.

### 3. Hệ quả & Ràng buộc
- **Tích cực:** Bảo toàn dữ liệu vận hành theo mặc định, không bao giờ tự ý xóa dữ liệu khách hàng khi chưa có lệnh kích hoạt của Chủ sở hữu, đồng thời sẵn sàng đáp ứng quy định bảo vệ dữ liệu cá nhân ngay khi bật công tắc.

---

## ADR-010: Vô Hiệu Hóa RSS/Atom/RDF Feeds (`404 Not Found`) & Ánh Xạ Đường Dẫn Tài Nguyên Trung Tính (`/static/*`)

- **Ngày quyết định:** 2026-10-28 (Step 7)
- **Trạng thái:** Đã chấp thuận & Đã triển khai

### 1. Bối cảnh
Trong bước **JN Step 7 — Hide WordPress and Harden**:
1. **Về RSS/Atom/RDF Feeds:** Toàn bộ 10 trang trên website (`Home`, `My Story`, `Coaching`, `Course`, `Client Success Story`, `My Favorite Product`, `Booking`, `Cart`, `Checkout`, `My Account`) là các trang tĩnh/thương mại; website không vận hành chuyên mục Blog tin tức định kỳ. Nếu giữ nguyên các đường dẫn `/feed/`, `/comments/feed/`, `?feed=rss2`, hệ thống sẽ lộ cấu trúc XML đặc trưng của WordPress (`xmlns:wfw`, `xmlns:dc`, `xmlns:sy`, `<generator>`) và lộ thông tin tác giả (`<dc:creator>`).
2. **Về Đường dẫn Tài nguyên Tĩnh (Task E — Optional Asset Aliasing):** Mặc dù dự án đã đổi `WP_CONTENT_DIR` sang `/app` và `WP_PLUGIN_DIR` sang `/app/modules` từ Step 1, các trang sử dụng Interactivity API, `zxcvbn`, `password-strength-meter` hoặc WooCommerce Blocks vẫn phát sinh yêu cầu tải `/wp-includes/...`, `/wp-admin/js/...`, và lộ tên thư mục theme/plugin (`/app/themes/blocksy/`, `/app/themes/jannie/`, `/app/modules/woocommerce/`).

### 2. Quyết định
1. **Vô hiệu hóa toàn bộ RSS/Atom/RDF Feeds và trả về HTTP `404 Not Found`:**
   - Gỡ bỏ thẻ `<link rel="alternate" type="application/rss+xml" ...>` khỏi `<head>` (`feed_links`, `feed_links_extra`).
   - Xóa các quy tắc rewrite của feed (`feed_rewrite_rules`) và buộc mọi yêu cầu chứa `feed` hoặc truy cập `/feed/`, `/comments/feed/` trả về HTTP `404` thông qua template `404.php` của child theme.
2. **Áp dụng Ánh xạ Đường dẫn Tài nguyên Trung tính (`/static/*` & `/api/ajax`) kèm Content-Hash Versioning (`?v=<10-char-sha256>`):**
   - Ánh xạ hai chiều giữa tầng PHP (`style_loader_src`, `script_loader_src`, `script_module_loader_src`, `admin_url`, `wp_template_enhancement_output_buffer`) và tầng máy chủ web (`wordpress/.htaccess` & `docker/nginx/jn-hardening.conf`):
     - `/wp-includes/*` $\rightarrow$ `/static/core/*`
     - `/wp-admin/{css,js,images}/*` $\rightarrow$ `/static/core-admin/{css,js,images}/*`
     - `/app/themes/blocksy/*` $\rightarrow$ `/static/base/*`
     - `/app/themes/jannie/*` $\rightarrow$ `/static/ui/*`
     - `/app/modules/woocommerce/*` $\rightarrow$ `/static/shop/*`
     - `/app/modules/jn-core/*` $\rightarrow$ `/static/academy/*`
     - `/app/modules/*` $\rightarrow$ `/static/ext/*`
     - `/wp-admin/admin-ajax.php` (front-end) $\rightarrow$ `/api/ajax`
   - Thay thế toàn bộ chuỗi truy vấn `?ver=<version>` bằng mã băm nội dung tệp 10 ký tự SHA-256 (`?v=<hash>`) để xóa dấu vết phiên bản WordPress/theme/plugin mà vẫn giữ nguyên cơ chế cache-busting khi nội dung tệp thay đổi.
   - **Không đổi tên thư mục vật lý trên ổ đĩa** của `blocksy` và các plugin bên thứ ba để bảo toàn tuyệt đối khả năng cập nhật qua `wp core check-update` và `wp plugin update`.

### 3. Hệ quả & Ràng buộc
- **Tích cực:**
  - Trình quét tự động `scripts/jn-fingerprint-scan.mjs` đạt **31/31 PASS (0 FAIL)** trên toàn bộ trang công khai và đường dẫn dò tìm mặc định.
  - Không làm gãy tính năng tự động cập nhật của WordPress Core, Blocksy hay WooCommerce; trình soạn thảo Gutenberg Block Editor, WooCommerce Store API và biểu mẫu Đặt lịch hoạt động 100% bình thường.
- **Ràng buộc:** Khi triển khai lên môi trường Production sử dụng Nginx thay vì Apache, cần nạp tệp cấu hình đối xứng `docker/nginx/jn-hardening.conf` vào khối `server {}`.

---

## ADR-011: Chính Sách Object Cache, Page Cache (`wp-super-cache`) & Plugin SEO Nhẹ (`autodescription` — The SEO Framework)

- **Ngày quyết định:** 2026-10-28 (Step 8)
- **Trạng thái:** Đã chấp thuận & Đã triển khai

### 1. Bối cảnh
Trong bước **JN Step 8 — PageSpeed optimisation to budget**, dự án cần đưa ra 3 quyết định hạ tầng quan trọng dựa trên `PROJECT CONTEXT CARD` (`PROD_WEB_SERVER = UNKNOWN`, container cục bộ chạy `wordpress:php8.3-apache`):
1. **Object Cache (Task 10):** Chỉ bật persistent object cache (Redis/Memcached) nếu máy chủ Production thực sự hỗ trợ daemon tương ứng.
2. **Page Cache (Task 2):** Cần plugin bộ nhớ đệm trang toàn phần (full-page HTML cache) nằm trong danh sách cho phép của `.agent/rules/jn-project.md` (§2) và vận hành thực tế được trên Apache.
3. **SEO Plugin (Task 9):** Cần một plugin SEO nhẹ duy nhất trong danh sách cho phép (`The SEO Framework` hoặc `Slim SEO`), tắt chữ ký HTML comment, sinh tiêu đề/mô tả tiếng Việt sát nguyên văn wireframe, canonical, Open Graph, `/sitemap.xml`, `robots.txt` và Schema chuẩn.

### 2. Quyết định
1. **Không cài đặt Redis / Memcached Object Cache ở Giai đoạn 8 (Object Cache Policy):**
   - Do `PROD_WEB_SERVER` trên `PROJECT CONTEXT CARD` đang ở trạng thái `UNKNOWN` (chưa xác nhận nhà cung cấp hosting Production có cung cấp Redis hay Memcached socket/daemon riêng hay không), dự án **không** cài đặt plugin `redis-cache` hay `object-cache.php` mù quáng ở môi trường local để tránh sai lệch kiến trúc với Production.
   - Thay vào đó, tối ưu tầng dữ liệu bằng:
     - **PHP 8.3 OPcache** bật sẵn (`memory_consumption = 128M`, `max_accelerated_files = 10000`, `interned_strings_buffer = 16`).
     - Tinh giản `wp_options` autoloaded xuống dưới `60 KB` và dọn sạch các transient hết hạn.
     - Kết hợp `WP_Object_Cache` trong bộ nhớ per-request của WordPress Core với **Page Cache tĩnh** để giảm truy vấn cơ sở dữ liệu về `0` trên các lượt truy cập công khai đã cache.
   - Nếu ở Step 9 máy chủ Production xác nhận có Redis daemon nội bộ, có thể kích hoạt drop-in Redis ở bước triển khai mà không cần sửa mã nguồn ứng dụng.
2. **Chọn `WP Super Cache` (`wp-super-cache`) làm Plugin Page Cache chính thức:**
   - Tương thích gốc với Apache (`.htaccess` / PHP cache mode) đang chạy trong container local và mọi máy chủ Apache/Nginx tiêu chuẩn.
   - Cấu hình loại trừ tuyệt đối (`Rejected URIs` & `Rejected Cookies`):
     - Không bao giờ lưu cache cho `/cart/`, `/checkout/`, `/tai-khoan/`, `/my-account/`, `/order-pay/`, `/api/`, `/<WP_LOGIN_SLUG>/`, hoặc các yêu cầu có tham số `?jn_bf=`, `?add-to-cart=`, `?wc-ajax=`.
     - Bỏ qua cache khi trình duyệt gửi cookie phiên đăng nhập (`wordpress_logged_in_*`) hoặc cookie giỏ hàng WooCommerce đang hoạt động (`woocommerce_items_in_cart`, `woocommerce_cart_hash`, `wp_woocommerce_session_*`).
   - Giữ nguyên khả năng tự động chọn gói dịch vụ qua `?goi=<slug>` ở phía trình duyệt (`booking-form.js` đọc `window.location.search`) và tự động làm mới token chống spam qua `/api/jn/v1/booking/token` để biểu mẫu đặt lịch hoạt động hoàn hảo ngay cả trên trang đã lưu cache tĩnh.
3. **Chọn `The SEO Framework` (`autodescription`) làm Plugin SEO nhẹ duy nhất:**
   - Gọn nhẹ, không quảng cáo, không tạo bảng rác và không chèn script front-end.
   - Vô hiệu hóa hoàn toàn chữ ký HTML comment của plugin (`the_seo_framework_indicator => false`) để tuân thủ tuyệt đối quy tắc ẩn dấu vết WordPress (§6).
   - Bổ sung đồ thị JSON-LD `@graph` tùy chỉnh gồm `Organization` + `ProfessionalService` + `Person` (Jannie Nguyễn) + `Service`/`Offer` cho 3 gói Coaching (không bịa giá khi `PACKAGE PRICES = UNKNOWN`) và tuyệt đối không gắn `Review`/`AggregateRating` cho các lời chứng thực tự xuất bản (`jn_testimonial`).

### 3. Hệ quả & Ràng buộc
- **Tích cực:** Đạt ngân sách hiệu năng Lighthouse Mobile & Desktop (`Performance >= 95–100`, `A11y = 100`, `Best Practices = 100`, `SEO = 100`), giữ `scripts/jn-fingerprint-scan.mjs` ở mức `0 FAIL`, và cấu hình hoàn toàn tái lập được trên Production.







## ADR-012: Hợp Nhất SEO Thành Module `jn-performance/class-jn-seo-*.php` & Một Đồ Thị JSON-LD Duy Nhất (Step 10)

- **Ngày quyết định:** 2026-10-28 (Step 10 — Kiểm toán & tối ưu SEO/AEO)
- **Trạng thái:** Đã chấp thuận (kế hoạch Step 10 được duyệt) & Đã triển khai

### 1. Bối cảnh
Kiểm toán bằng skill `sunext-semrush-ahrefs-os` (báo cáo `docs/jn/seo-aeo/SEO_AEO_Audit_2026-10-28/`) phát hiện:
- Schema xuất số điện thoại `+84908789866` không có nguồn (Context Card: phone = UNKNOWN) — vi phạm quy tắc "không bịa dữ liệu".
- The SEO Framework 5.2.0 vẫn in đồ thị JSON-LD riêng vì filter `the_seo_framework_ld_json_scripts` **không tồn tại** ở bản 5.x → mỗi trang có 2 đồ thị rời nhau (2 node `WebPage` khác `@id`, `WebSite` tên "JANNIE NGUYEN / BEAUTY BLOG").
- Một số filter TSF khác cũng không tồn tại (`the_seo_framework_rel_canonical_output`, `the_seo_framework_sitemap_style_enabled`) → code chết.
- Khối SEO nằm chung trong `jn-performance.php` (1.553 dòng, vượt giới hạn 800 dòng).

### 2. Quyết định
1. Tách SEO thành 6 lớp trong `wordpress/app/mu-plugins/jn-performance/` (nạp từ `jn-performance.php`, mirror cấu trúc `jn-hardening/`): `Brand`, `Meta`, `Robots`, `Sitemap`, `Schema`, `Llms`. Không thêm MU-plugin mới.
2. Tắt đồ thị TSF qua filter có thật `the_seo_framework_schema_graph_data` → `[]`; xuất **một** `@graph`: `WebSite` → `WebPage`/`ProfilePage`/`CollectionPage` (+`isPartOf`, `breadcrumb`) → `BreadcrumbList`, `Organization` ↔ `Person`, `Service` ×3 (home/coaching/booking), `FAQPage` khi có khối FAQ đã xuất bản.
3. `Organization` thay cho `Organization + ProfessionalService`: `ProfessionalService` là `LocalBusiness`, cần địa chỉ đầy đủ + điện thoại (UNK-04). Khôi phục khi chủ dự án cung cấp.
4. Dữ liệu NAP chưa có lấy từ option do chủ dự án đặt: `jn_brand_telephone`, `jn_brand_same_as` (JSON array). Bỏ `priceRange`; `Offer` chỉ xuất khi gói có giá thật.
5. Tắt branding tiêu đề tự động của TSF (`the_seo_framework_use_title_branding`) vì mọi title đã chứa "Jannie Nguyễn" (trước đó thành "… | Jannie Nguyễn - JANNIE NGUYEN", 64–74 ký tự).
6. FAQ: pattern `jannie/faq` (`core/details` + class `jn-faq-item`, không JS). Mục còn chứa `[NHÁP]` bị loại khỏi JSON-LD.

### 3. Hệ quả
- 77/77 kiểm thử `scripts/test-step-10-seo.php`; `test-step-8-performance.php` cập nhật assertion schema (Organization, không telephone).
- Ghi chú: Google chỉ hiển thị rich result FAQ cho site chính phủ/y tế (từ 2023); `FAQPage` vẫn giữ để máy trả lời AI và parser hiểu cấu trúc hỏi–đáp.

## ADR-013: Chính Sách Robots — `noindex` Cho Trang Tiện Ích, robots.txt Một Nguồn, Cho Phép Bot AI (Step 10)

- **Ngày quyết định:** 2026-10-28 (Step 10)
- **Trạng thái:** Đã triển khai — các lựa chọn Q2/Q3 là **giả định mặc định**, chủ dự án có thể đảo ngược

### 1. Bối cảnh
Step 8 gỡ `noindex` khỏi mọi trang (kể cả Giỏ hàng/Thanh toán/Tài khoản, gỡ luôn `wc_page_no_robots`) để Lighthouse `is-crawlable` đạt SEO = 100. Trên production điều đó cho phép Google index trang giỏ hàng. robots.txt production trong code lệch với `docs/jn/robots.production.txt`; filter `robots_txt` của `jn-hardening` là code chết.

### 2. Quyết định
1. `noindex, follow` cho `/cart/`, `/checkout/` (gồm `order-pay`), `/tai-khoan/` và trang tìm kiếm; trang nội dung `index, follow, max-snippet:-1, max-image-preview:large`.
2. **Ngoại lệ Lighthouse có văn bản (§5 "documented WooCommerce-core exception"):** trên 3 trang tiện ích, audit `is-crawlable` được phép fail; mọi audit SEO khác phải đạt (`scripts/run-step-8-lhci.mjs`, `lighthouserc.json`).
3. Một builder `JN_SEO_Robots::build_robots_txt()` phục vụ cả route `/robots.txt` lẫn filter `robots_txt`. Lighthouse/PSI trên môi trường `local` nhận **đúng** nội dung production; khách thường ở local nhận `Disallow: /`.
   - **Không** `Disallow` các trang `noindex` (`/cart/`, `/checkout/`, `/tai-khoan/`, tìm kiếm): nếu chặn crawl, bot không đọc được `noindex` và URL vẫn có thể bị index dạng "bị chặn bởi robots.txt". Chỉ chặn `/api/`, `?add-to-cart=`, `?jn_bf=`.
   - **Không** liệt kê slug đăng nhập ẩn (`/<WP_LOGIN_SLUG>/`) trong robots.txt (file công khai — liệt kê sẽ làm lộ URL đăng nhập, trái §6); trang đăng nhập đã có `noindex` của core.
   - Tôn trọng "Ngăn công cụ tìm kiếm" (`blog_public = 0`) trên staging / production trước go-live: robots meta giữ `noindex` của core và robots.txt trả `Disallow: /`. Chỉ môi trường `local` mô phỏng chính sách production để đo Lighthouse.
   - Trang không thuộc danh sách index của dự án (trang riêng tư/có mật khẩu, archive, trang chủ dự án tự đặt `noindex` trong TSF…) giữ nguyên quyết định của The SEO Framework.
4. **Q2 (giả định):** cho phép bot tìm kiếm & trả lời AI (`OAI-SearchBot`, `ChatGPT-User`, `GPTBot`, `PerplexityBot`, `ClaudeBot`, `Claude-SearchBot`, `Google-Extended`, `Applebot-Extended`…) — liệt kê chung nhóm `User-agent: *` (RFC 9309) để thừa hưởng cùng luật Disallow. Muốn chặn bot huấn luyện: bỏ tên khỏi `JN_SEO_Robots::AI_AGENTS` và thêm nhóm `Disallow: /` riêng.
5. **Q3 (giả định):** giữ `index` cho 2 trang pháp lý (tín hiệu tin cậy E-E-A-T) và giữ trong sitemap.
6. Phục vụ `/llms.txt` sinh từ cùng nguồn dữ liệu (brand facts + meta map + 3 gói).

## ADR-014: Một Hệ Tiêu Đề Đầu Trang Cho Hero & Mọi Page-Intro (Step 13)

- **Ngày quyết định:** 2026-10-29 (Step 13)
- **Trạng thái:** Đã triển khai — chủ dự án duyệt kế hoạch, chọn phương án A cho trang Sản phẩm

### 1. Bối cảnh
`code.html` chỉ có wireframe cho hero `/coaching/` (tiêu đề ngắn, phần nghiêng 2 chữ). Các trang khác dùng page-intro với tiêu đề dài hơn, phần nghiêng 4–5 chữ, nên rớt 1 chữ xuống dòng, cắt ngang phần nghiêng và vỡ 4–5 dòng trên mobile. Trang Sản phẩm dùng header căn giữa kiểu section (H2) cho một H1.

### 2. Quyết định (khác `code.html` có chủ đích theo §4)
1. Phần nghiêng `jn-accent-italic` trong H1 hero/page-intro **luôn xuống dòng riêng**: tiêu đề đọc thành hai dòng có chủ đích — mệnh đề màu `ink`, từ khóa màu `champagne-text-lg`.
2. Lời dẫn dùng weight **400** thay cho `font-light` (300) của `code.html`: nét 300 cỡ 18 px trên nền kem trông nhạt dù màu đạt 5.02:1.
3. Eyebrow cấp trang (H1) dùng `label-md` (13 px) thay cho `label-sm` (11 px) để cân tỷ lệ với tiêu đề 64 px; eyebrow cấp section (H2) giữ `label-sm` màu champagne như `code.html`.
4. Mọi H1 đầu trang căn trái và thẳng hàng logo, kể cả trang Sản phẩm (phương án A); kiểu căn giữa dành cho H2 trong trang.
5. Giới hạn độ dài dòng đặt trên chính tiêu đề/lời dẫn (`--jn-measure-title`, `--jn-measure-lead`), không đặt trên wrapper `.jn-container` (bị `max-width … !important` của container đè).

### 3. Hệ quả
- Thêm H1 mới: phần nghiêng **phải** là cụm từ khóa cuối tiêu đề (không có chữ thường sau `</em>`), phần trước nó nên vừa một dòng ở 1440 px. `scripts/test-step-13-header-typography.php` kiểm tra quy tắc này trên mọi trang và pattern; `scripts/verify-step-13-headers.mjs` bắt lỗi rớt dòng ở 5 viewport.
- Trình duyệt chưa hỗ trợ `text-wrap: balance/pretty` (Safari < 17.4) ngắt dòng như cũ; quy tắc phần nghiêng xuống dòng riêng vẫn hoạt động.

## ADR-015: Ảnh Placeholder Hero & Chuyên Gia — Bỏ Khung Wireframe, Lấy Ảnh Gốc `=s0`, Phóng To Bằng AI

- **Ngày quyết định:** 2026-10-29
- **Trạng thái:** Đã triển khai bước `=s0`; bước master AI 4x chờ chủ dự án chạy `upscale-step-3-media.mjs` (chủ dự án chọn phương án B — Real-ESRGAN)

### 1. Bối cảnh
- `import-step-3-media.php` tải ảnh Stitch trên `lh3.googleusercontent.com` không kèm tham số kích thước nên Google trả bản **512×279**; cắt sang khung dọc rồi kéo lên 800×1000 / 750×1000 (~3,6×) → ảnh mờ, vỡ hạt.
- Ảnh nguồn của hero là **ảnh chụp một wireframe** (thanh "Coaching Session: Personal Branding", ô "Jannie Nguyen, Founder of [Studio Name]", nút "View Bio" / "Schedule Consultation"); ảnh thật chỉ chiếm vùng x318 y105 756×500 của khung 1408×768.

### 2. Quyết định
1. Luôn tải bản gốc `=s0` (1408×768). Hero cắt trong `source_region` (tỷ lệ theo phân số nên dùng được cho cả bản gốc lẫn master 4x); chuyên gia giữ khung cắt giữa như cũ.
2. Ảnh có `ai_upscale` ưu tiên master Real-ESRGAN 4x (`realesrgan-x4plus`) tạo trên máy host (cần GPU, không chạy trong container) tại `scripts/.media-masters/` (gitignored) → WebP cuối chỉ thu nhỏ, không kéo giãn.
3. Danh sách 5 ảnh chuyển sang `scripts/lib/jn-step3-media.json` (nguồn chung cho PHP & Node); hàm tạo AVIF dùng chung ở `scripts/lib/jn-placeholder-media.php` (Task 5 của `build-step-8.php` gọi lại).
4. Tải nguồn lỗi → báo lỗi, thoát mã 1 và **giữ nguyên** file cũ (trước đây lặng lẽ ghi đè bằng nền kem trơn). Chạy một phần (`… hero_portrait expert_consultation`) gộp vào `jn_step3_media_map` thay vì ghi đè.

### 3. Hệ quả
- Giữ nguyên tên file và ID (86, 87) → không phải sửa nội dung trang Home / My Story / Coaching.
- Chưa có master AI: hero phóng 2,0×, chuyên gia 1,3× (`_jn_media_scale`); có master: cả hai ≤ 1,0×. `scripts/test-placeholder-media.php` kiểm tra vùng cắt, kích thước, ngân sách 150 KB của LCP và AVIF.
- Vẫn là ảnh PLACEHOLDER — launch blocker "ảnh thật thay thế toàn bộ ảnh AI" không đổi.

## ADR-016: Bỏ Chữ Chìm "1:1" & Nhãn "PRO-COACHING // SPEC.24" Khỏi Khung Ảnh Hero

- **Ngày quyết định:** 2026-10-29
- **Trạng thái:** Đã triển khai — chủ dự án yêu cầu xoá cả hai

### 1. Bối cảnh
`code.html` / `wireframe-spec.md` (§Section 1) có chữ chìm `1:1` cỡ 220 px (đặt tuyệt đối, đè lên góc phải trên ảnh hero) và nhãn `PRO-COACHING // SPEC.24` ở góc trái trên ảnh. Cả hai là chi tiết trang trí của wireframe, không mang nội dung, và làm rối khung ảnh chân dung.

### 2. Quyết định (khác `code.html` có chủ đích)
1. Xoá hẳn hai block paragraph `jn-hero-watermark` và `jn-hero-spec-badge` khỏi pattern `jannie/hero-editorial`, CSS tương ứng trong `block-styles.css`, và filter `aria-hidden` không còn dùng trong `JN_Blocks`.
2. Hàng `jn-hero-overlay-top` chỉ còn icon ∞ nên chuyển `justify-content` sang `flex-end` để icon giữ góc phải trên.
3. Nội dung đã lưu của Home (ID 7) và Coaching (ID 9) được dọn bằng `scripts/build-hero-overlay-cleanup.php` (khớp đúng markup từng block, idempotent, có revision để hoàn tác) — không dùng `parse_blocks()`/`serialize_blocks()` vì không round-trip nguyên vẹn hai trang này.

### 3. Hệ quả
- Chữ "1:1" trong nội dung thật (thanh chỉ số "Cố Vấn Độc Bản", lời dẫn, nút) giữ nguyên. `scripts/test-hero-overlay-cleanup.php` kiểm tra pattern, nội dung đã lưu & đã render, CSS (cả bundle) và filter.
- Kiểm tra `Hero watermark` trong `verify-step-3.php` đổi thành "đã bị xoá".

## ADR-017: Phục Vụ Site Local Qua Cloudflare Quick Tunnel Bằng Host Động, Không Sửa DB

- **Ngày quyết định:** 2026-10-29
- **Trạng thái:** Đã triển khai — chủ dự án duyệt kế hoạch

### 1. Bối cảnh
`WP_HOME`/`WP_SITEURL` lấy từ `.env` (`http://localhost:8080`), nên khi chia sẻ site qua `cloudflared tunnel --url http://localhost:8080` mọi link, asset và redirect vẫn trỏ về `localhost` hoặc `<tunnel>:8080`. Ngoài URL sinh động, DB còn URL tuyệt đối lưu cứng (nội dung bài/trang, `_social_image_url`, `theme_mods_jannie`, `autodescription-site-settings`, `jn_step8_og_image`, `amelia_settings`…) và transient `jn_seo_sitemap_urls` dùng chung cho mọi host.

### 2. Quyết định
1. `wp-config.php` (bản dev) chọn `WP_HOME` theo request: chỉ khi Host khớp `^[a-z0-9-]+\.trycloudflare\.com\z`, `CF-Connecting-IP` là IP hợp lệ và môi trường là `local`/`development` (staging/production luôn bị từ chối). Cổng 8080 chỉ bind `127.0.0.1`, nên Host giả chỉ có thể đến từ chính máy dev. `deploy/wp-config.production.php` không đổi.
2. Ở chế độ tunnel, `REMOTE_ADDR` lấy từ `CF-Connecting-IP`: mọi khách qua tunnel đều đến Apache từ địa chỉ của `cloudflared`, nên nếu không đổi thì giới hạn 5 lần đăng nhập sai / 5 lần đặt lịch mỗi 15 phút theo IP sẽ khoá cả nhóm người xem.
3. URL lưu cứng được đổi **lúc trả trang** (output buffer từ `init` priority 0 + filter `wp_redirect`) trong `mu-plugins/jn-dev-tunnel.php`, không chạy `wp search-replace`: DB giữ nguyên dạng local, tắt tunnel là xong, không cần hoàn tác. Không đổi màn hình wp-admin và phản hồi REST `context=edit` (block editor), vì nội dung đó được gửi ngược lên và lưu vào DB.
4. Transient sitemap được đổi ngược về URL local trước khi lưu (`pre_set_transient_jn_seo_sitemap_urls`). Cache WooCommerce Blocks đã tự khoá theo `site_url` nên không cần xử lý.

### 3. Hệ quả
- Không phải sửa `.env` khi domain tunnel đổi; `localhost:8080` hoạt động như cũ. WP Super Cache tách cache theo host — xoá thư mục cache của domain tunnel cũ khi cần.
- Không sửa nội dung qua tunnel: trình soạn thảo có thể lưu URL tunnel tuyệt đối vào DB.
- Buffer mở ở `init` nên bao cả phản hồi REST (trừ `context=edit`); `admin-ajax` (kể cả bí danh `/api/ajax`) chạy với `is_admin()` nên không bị đổi URL lưu cứng. File tĩnh do Apache phục vụ trực tiếp không đi qua PHP.
- WP Super Cache mở buffer ngoài cùng (`$wp_super_cache_late_init = 0`) nên cache trang đã đổi URL; nếu bật late init, buffer cache sẽ nằm trong buffer tunnel và lưu bản `localhost` — khi đó phải xem lại thứ tự.
- Lịch hẹn gửi qua tunnel lưu `landing_url` / `_jn_consent_policy_url` theo host tunnel — đúng với nơi khách thực sự gửi, chấp nhận.
- `scripts/test-dev-tunnel.php` kiểm tra danh sách Host cho phép (Host giả mạo, hậu tố giả, Host có cổng hoặc xuống dòng, thiếu/sai IP CF, staging, production), hàm đổi URL và điều kiện bỏ qua wp-admin/REST edit.

## ADR-018: Nhập 14 Ảnh Cũ Của jannieedu.com — Bắt Buộc Dùng Master AI 4x, Logo Một Màu Dựng Lại Độ Trong Suốt

- **Ngày quyết định:** 2026-10-29
- **Trạng thái:** Đã triển khai cả 14 ảnh; 12 ảnh đạt chuẩn nét. 2 logo báo (Bình Phước, Người Tiêu Dùng) giữ bản đúng chữ nhưng chưa nét hơn — chủ dự án chọn **xin file logo chính thức** của hai báo rồi thay (mục 3)

### 1. Bối cảnh
- Site cũ jannieedu.com chỉ còn trên Wayback Machine (bản lưu 2026-07-11); 14 ảnh nội dung đều nhỏ (logo báo 200×100, ảnh footer 400×600, một ảnh 267×249). 4 ảnh nền của theme Edumall không được lưu (404).
- Chủ dự án yêu cầu ảnh **bắt buộc phải nét**: không được dùng lại bản lưu mờ rồi phóng to.
- Real-ESRGAN phóng màu (RGB) và kênh trong suốt (alpha) riêng rẽ; vùng trong suốt của PNG mang màu đen nên logo bị **viền tối** quanh nét chữ, và độ nét còn thua bản phóng bicubic (đo 0,74–0,79×).

### 2. Quyết định
1. Danh sách ở `scripts/lib/jn-legacy-media.json`; master 4x ở `scripts/.media-masters/` (gitignored), dựng bằng `node scripts/upscale-step-3-media.mjs --items scripts/lib/jn-legacy-media.json` (script nay nhận `--items`, `model` riêng từng ảnh, tải Wayback chậm và chờ khi bị HTTP 429, dùng lại bản gốc đã tải).
2. Cờ `require_master`: thiếu master → `WP_Error jn_master_missing`, **không** quay về bản lưu mờ. Ảnh chỉ thu nhỏ (`_jn_media_scale` ≤ 1) rồi làm nét nhẹ (`sharpen` 0,15, nhân 3×3).
3. Logo một màu (`ink`): đặt logo lên nền trắng (`lib/jn-flatten-on-white.php`, chạy trong container wpcli qua STDIN/STDOUT vì host không có PHP), Real-ESRGAN phóng bản đó, rồi `jn_media_ink_to_alpha()` lấy độ phủ mực làm alpha và tô toàn bộ bằng màu mực → hết viền tối, giữ khử răng cưa. Độ sáng ≥ 252 coi là giấy (`JN_MEDIA_PAPER_LUMA`) vì "trắng" của Real-ESRGAN là 254–255; nếu không, nền phủ một lớp mực mờ và file tăng 4 lần.
4. Ảnh có `transparent` mà không có `ink` vẫn giữ alpha khi thu nhỏ (trước đây `jn_placeholder_render()` dán lên nền đen).
5. Vòng import tách thành `jn_import_media_item()` dùng chung cho Step 3 và ảnh cũ; Step 3 cho ra file giống hệt (md5 không đổi).

### 3. Hệ quả
- Media Library có 14 attachment `jn-legacy-*` (WebP + `.avif` cùng cỡ + sub-size AVIF), alt tiếng Việt, URL Wayback lưu ở `_jn_media_origin`, option `jn_legacy_media_map`. Chưa gắn vào trang nào.
- `scripts/test-legacy-media.php` đo độ nét bằng phương sai Laplacian so với bản lưu phóng bicubic (≥ 1,25×), kiểm tra nền trong suốt ở WebP, AVIF và sub-size. **Phép đo không phát hiện được chữ bị AI vẽ sai** — logo phải được xem bằng mắt.
- Logo Bình Phước và Người Tiêu Dùng: mọi model (x4plus, x4plus-anime, animevideov3) đều làm méo dòng chữ nhỏ ("ĐÀI PHÁT THANH…", "Tạp chí tiêu dùng…"); bản hiện tại đọc được nhưng chưa nét hơn. Chủ dự án chọn xin file logo chính thức của hai báo; đến lúc đó `sharpness_pending` trong JSON khiến test báo `[KNOWN]` thay vì fail. Khi có file: đặt vào `scripts/.media-masters/<slug>.x4.png` (hoặc đổi `source_url`), xoá `sharpness_pending`, chạy lại import và test.
- Ảnh là ảnh thật của thương hiệu → có thể dùng để gỡ một phần launch blocker "ảnh thật thay thế ảnh AI" (việc riêng, chưa làm).
- File chạy `~/.local/opt/realesrgan-ncnn-vulkan/realesrgan-ncnn-vulkan` trên máy chủ dự án **thiếu quyền thực thi** (`chmod +x` trước khi chạy); đây cũng là lý do master Step 3 của ADR-015 chưa từng được tạo.

## ADR-019: Ảnh Hero & Chuyên Gia Dùng Ảnh Thật jannieedu.com Thay Ảnh AI Stitch

- **Ngày quyết định:** 2026-10-29
- **Trạng thái:** Đã triển khai (chủ dự án chọn "cách 1: thay file ngay trong ảnh cũ")

### 1. Bối cảnh
- Trang Home / My Story / Coaching dùng attachment 86 (hero, 800×1000) và 87 (chuyên gia, 750×1000) — ảnh AI từ Stitch, tiêu đề `PLACEHOLDER` (launch blocker BL-01).
- ADR-018 đã có master 4x của 14 ảnh thật từ jannieedu.com.

### 2. Quyết định
1. Giữ nguyên ID 86/87 và tên file (không sửa nội dung trang); `jn-step3-media.json` trỏ sang master ảnh thật qua trường mới `master_slug`: hero ← `jn-legacy-home-portrait`, chuyên gia ← `jn-legacy-footer-8` (ảnh salon bị loại: nguồn 267px, phải phóng 1,004× khi cắt 3:4). Cả hai `require_master`, `sharpen` 0,15, chỉ thu nhỏ (0,29× / 0,47×).
2. Bỏ `source_region` của hero (vùng ảnh trong wireframe Stitch); phép tính đó giữ lại trong `test-placeholder-media.php` như một ca kiểm tra `jn_placeholder_crop_box()`.
3. Tiêu đề mới không còn tiền tố `PLACEHOLDER`, xoá meta `_jn_is_placeholder` (giá trị `null` trong meta = xoá), lưu URL Wayback ở `_jn_media_origin`.

### 3. Hệ quả
- `check-launch-blockers.php`: số ảnh placeholder giảm 5 → 3 (còn 3 avatar học viên — không có ảnh thật thay thế). BL-01 vẫn chờ avatar thật và ảnh Open Graph 1200×630.
- **Sửa sau khi chạy thử:** giữ nguyên tên file là sai — ảnh được phục vụ `Cache-Control: immutable, max-age=1 năm`, nên trình duyệt (kể cả khách cũ trên production) giữ ảnh AI tới một năm; và nội dung trang lưu cứng `<img src alt>`, nên alt vẫn mô tả ảnh cũ. Vì vậy file đổi tên thành `jn-hero-portrait.webp` / `jn-expert-portrait.webp` (ID 86/87 giữ nguyên); `jn_media_delete_replaced_files()` xoá bộ file cũ, và `jn_run_media_import()` gọi `jn_media_sync_image_markup()` ghi lại `src`/`alt` của mọi `<img class="wp-image-{id}">` trong trang đã đăng (ghi qua `$wpdb` vì `wp_update_post()` từ CLI chạy kses, sẽ xoá style/SVG; không tạo revision). Tên file dự phòng trong 2 pattern, `build-step-3.php` và URL OG dự phòng của `class-jn-seo-meta.php` cũng đổi theo. Khi deploy lên production phải chạy lại `import-step-3-media.php hero_portrait expert_consultation` để nội dung trang trên production được ghi lại.
- Alt text mô tả trung tính, chưa ghi tên người trong ảnh — chủ dự án bổ sung nếu muốn. Chữ "Canon" trên máy ảnh (ảnh chuyên gia) bị AI làm méo nhẹ.

## ADR-020: Page Cache Chỉ Trả URL Chuẩn & Ẩn Khung `[CẦN NỘI DUNG]` Với Khách (Step 14)

- **Ngày quyết định:** 2026-10-30 (Step 14 — kiểm toán lại SEO/AEO)
- **Trạng thái:** Đã triển khai. Mục 2 là lựa chọn của chủ dự án ("Ẩn với khách") trong 4 phương án.

### 1. Bối cảnh
- Fast path của `app/advanced-cache.php` ghép khóa cache bằng `trim( parse_url( URI ), '/' )`, nên `/coaching`, `/COACHING/` (ổ đĩa macOS không phân biệt hoa/thường) nhận bản cache 200 thay vì 301 của `redirect_canonical()`; `//coaching/` bị `parse_url()` đọc thành host `coaching`, path `/` → trả **HTML trang chủ**. Production (Nginx/Apache trong `deploy/`) không phục vụ file supercache trực tiếp — mọi request đi qua drop-in này, nên lỗi có cả ở production.
- 5/9 trang được index hiển thị nguyên văn khung `[CẦN NỘI DUNG: …]` (UNK-03, UNK-08, UNK-09) cho khách và bot.

### 2. Quyết định
1. URI không phải `/` hoặc permalink chữ thường có `/` cuối (`#^(?:/[a-z0-9_-]+)+/$#`) đi vào nhánh bypass có sẵn (`DONOTCACHEPAGE`, không nạp phase 1 của WP Super Cache) → WordPress tự 301. Không bật `$wp_cache_slash_check` (sẽ làm phase 1 của WP Super Cache bắt đầu phục vụ URL chuẩn — đổi hành vi ngoài phạm vi).
2. `JN_SEO_Placeholders` (filter `render_block`): với người không có quyền `edit_posts`, ẩn group `jn-scaffold-placeholder`, đoạn văn bắt đầu bằng `[CẦN NỘI DUNG`, và section/container chỉ còn rỗng sau khi bỏ khung. Group còn chữ (vd. H1) không bao giờ bị ẩn. Người sửa bài vẫn thấy khung để nhắc việc; điền nội dung thật thì khung tự biến mất, không cần sửa code.
3. Các phương án không chọn: tự `noindex` trang còn khung (sẽ noindex cả `/my-story/` — trang hồ sơ thực thể chính — và hạ Lighthouse SEO 5 trang); ẩn + `noindex` 2 trang pháp lý; giữ nguyên.

### 3. Hệ quả
- 2 trang pháp lý chỉ còn tiêu đề (~12 từ trong `<main>`) → audit báo `AUD-CNT-01` Thin Content (MEDIUM) cho tới khi có văn bản UNK-09. Vẫn `index` theo giả định Q3 (ADR-013).
- `scripts/seo-audit-local.py` có thêm kiểm tra `JN-URL-01` (biến thể `/slug`, `//slug/`, `/SLUG/`); `scripts/test-step-14-seo-aeo.php` khóa cả hai hành vi.
- `/COACHING/` vẫn 200 (WordPress không redirect khác biệt chữ hoa/thường) nhưng canonical trỏ `/coaching/` và không còn đi qua cache.

## ADR-021: Hero Trang Chủ Riêng — Khung Ảnh Vòm & Polaroid Đổi Ảnh (`jannie/hero-home` + `jn/hero-portrait`)

- **Ngày quyết định:** 2026-10-30
- **Trạng thái:** Đã triển khai — chủ dự án gửi bản HTML mẫu, duyệt `/plan` (chỉ trang chủ; nút là link thật; Claude chọn ảnh thứ hai). **Sửa cùng ngày:** chủ dự án gửi ảnh cột chữ của hero cũ và chọn "giống hệt hình" + bỏ dòng gợi ý cuộn → cột chữ trở lại hệ hero chung (mục 2.1), khung ảnh vòm giữ nguyên.

### 1. Bối cảnh
- Trang chủ dùng lại hero của `/coaching/` (`jannie/hero-editorial` + `str_replace` đổi chữ trong `build-step-3.php`), nên hai trang mở đầu giống hệt nhau (Step 14 đo trùng ý định `/` ↔ `/coaching/` 0.846).
- Bản HTML mẫu: H1 ba dòng, thanh chỉ số kẻ chỉ, hai nút `<details>` mở panel "bản demo", vòng tròn trang trí "thở", khung ảnh vòm + caption, polaroid bấm để đổi ảnh (checkbox thuần CSS), ảnh Unsplash, font Arial/Georgia, mã màu cứng, animation `enter` (opacity 0 → 1) cho cả H1 và ảnh.

### 2. Quyết định (khác bản mẫu có chủ đích)
1. **Chỉ trang chủ; cột chữ theo hero chung:** pattern mới `jannie/hero-home`; `/coaching/` giữ `jannie/hero-editorial`. Cột chữ dùng nguyên quy tắc hero chung (ADR-014, `block-styles.css` §9) như ảnh chủ dự án gửi: eyebrow có gạch ngang, H1 64 px hai dòng ("Khám Phá Sự Cân Bằng &" / *"Vẻ Đẹp Đích Thực"*, không `<br>`), lời dẫn dùng chung `lib/jn-page-copy.php`, thẻ chỉ số nổi `20+ Năm Thực Chiến · 1:1 Cố Vấn Độc Bản · 98% Tỷ Lệ Tối Ưu Hóa`. Bỏ dòng gợi ý cuộn "↓ Beauty · Balance · Mentorship". **Lưới (chủ dự án yêu cầu ảnh to hơn):** cột chữ `1fr` (≥ 776 px ở 1440 để "Khám Phá Sự Cân Bằng &" vừa một dòng 64 px), cột ảnh `clamp(20rem, 32vw, 27rem)`, khoảng cách 40 px; từ 1024 px cột ảnh **tràn sang lề phải** của container đúng bằng phần lề còn chừa ≥ 40 px tới mép màn hình (`clamp(0px, pad + (100vw − 1360px)/2 − 40px, pad)`: 56 px từ 1440 px trở lên, 0 dưới 1360 px) → khung ảnh rộng 448 px ở 1440 (trước 360 px, diện tích +55%), polaroid cách mép màn hình 32 px.
2. **Khung ảnh là block động `jn/hero-portrait`** (jn-core, như `jn/icon`) vì checkbox + `<label>` không dựng được bằng block core và dự án cấm `core/html`. Block chọn được 2 ảnh và 5 đoạn chữ trong editor; ID không phải ảnh → dùng ảnh dự phòng (`jn_step3_media_map.hero_portrait`, `jn_legacy_media_map.banner_home`) và báo `wp_trigger_error`.
3. **Ảnh thật thay Unsplash:** ảnh chính là chân dung ID 86 (ADR-019); ảnh đổi + polaroid là `jn-legacy-banner-home` (ID 5180, 1920×1080, cùng tông kem, chủ thể lệch phải → `object-position: 72% 50%`).
4. **Nút là link thật:** "Khám Phá Chương Trình Coaching ↗" (nút đen) → `/coaching/`, "Đặt Lịch Tư Vấn 1:1 ↓" (nút be) → `#booking-form`; bỏ panel `<details>` ghi "bản demo".
5. **LCP không bị ẩn và đi theo block:** bỏ animation `enter` của bản mẫu. Trên trang có `jn/hero-portrait`, `jn_hero_portrait_lcp_id()` đọc `primaryId` của block (qua `JN_Hero_Portrait::primary_id_in()`), nên ảnh chọn trong editor vẫn được preload và giữ `eager` + `fetchpriority="high"`; chỉ ảnh mang class `jn-hero-portrait__img--primary` là hero (bản sao ảnh 86 ở chỗ khác bị ép `lazy`). Trang không có block (`/coaching/`) vẫn nhận hero theo ID trong `jn_step3_media_map`. `jn_hero_image_sizes()` trả `JN_Hero_Portrait::sizes()` cho cả `<img>` lẫn preload (khớp 1:1, không tải hai lần); ảnh đổi dùng `sizes_for()` nhân theo tỉ lệ cắt `object-fit: cover`.
6. **Polaroid luôn xem trước ảnh sẽ đổi sang:** hai ảnh nhỏ chồng nhau (banner khi đang hiện chân dung, chân dung khi đã đổi), đều `alt=""` + lazy, không mang class LCP. **Đổi ảnh truy cập được:** checkbox ẩn nhưng nhận focus (Tab → viền focus trên polaroid, Space đổi ảnh); tên của checkbox là chính `<label>`: chữ hiển thị "Tap to explore" (`lang="en"`) + mô tả ẩn "Đổi ảnh chân dung" (WCAG 2.5.3 — không dùng `aria-label` che chữ hiển thị). Ảnh đang ẩn có `visibility: hidden` (đổi sau khi mờ xong) để trình đọc màn hình chỉ đọc ảnh đang hiện.
7. **Chuyển động chỉ để trang trí** (vòng tròn, polaroid, nhấc khung ảnh khi hover; chỉ `transform`/`opacity`), dừng khi `prefers-reduced-motion` và trên máy yếu (`.jn-motion-lite`). Vòng tròn vẽ bằng `::before/::after` của section; dưới 1024 px thu nhỏ vào góc để không cắt chữ.
8. **Token thay mã màu cứng**, font Playfair/Geist của site; vài tông ấm không có token toàn cục khai báo thành biến cục bộ trên `.jn-hero-home`. Chữ dọc "Balance ✳ Beauty" dùng `--jn-secondary` (5.12:1 cả trên vùng sáng ấm `#EDE3D8`; `champagne-text-sm` chỉ đạt 4.19:1 ở đó). Breakpoint theo site (1023.98 / 639.98) thay 800 / 480.

### 3. Hệ quả
- CSS ở file riêng `assets/css/hero-home.css` (block-styles.css đã 1.7k dòng), nằm trong bundle inline và cả hai loader của editor.
- Nội dung trang chủ đã lưu được thay bằng `scripts/build-home-hero.php` (khớp đúng section hero editorial cũ hoặc hero-home trước đó; bỏ qua khi đã giống pattern; có revision để hoàn tác, xoá cache pattern + page cache — chạy lại mỗi khi sửa pattern); `build-step-3.php` seed hero mới và nạp lại lời dẫn từ `jn-page-copy.php`. **Khi deploy production phải chạy `build-home-hero.php`** với `JN_ALLOW_DESTRUCTIVE=1` (script ghi nội dung trang nên `jn-env-guard` chặn production theo mặc định).
- Kiểm chứng: `scripts/test-home-hero.php` (block, pattern, CSS, nội dung đã lưu, trang `/` thật), `scripts/verify-home-hero.mjs` (6 viewport, bàn phím + chuột đổi ảnh, reduced motion; ảnh `docs/jn/home-hero/`), Lighthouse `/` lưu ở `docs/jn/lighthouse/home-hero/`. `test-hero-overlay-cleanup.php` chỉ còn kiểm tra icon ∞ / hộp kính trên `/coaching/`.
- Ở một cột (< 1024 px) ảnh nằm dưới màn hình đầu, nên LCP mobile là lời dẫn (như trước); preload ảnh vẫn giữ vì trang chủ desktop dùng ảnh làm LCP.
- Khung ảnh 448 px cần ~900 px ảnh trên màn hình retina, ảnh chân dung ID 86 chỉ có bản 800 px → hơi mềm trên màn hình 2x; muốn nét hơn thì xuất lại bản 1000×1250 từ master `jn-legacy-home-portrait` (việc riêng, đổi tên file theo ADR-019).
- Chưa làm (LOW, đã cân nhắc): preload chỉ cho desktop (`media="(min-width: 1024px)"`) — Lighthouse mobile vẫn 100, LCP 1.7 s nên chưa cần; ảnh đổi tải bản lớn (`sizes` ~182vw trên mobile) lúc rảnh sau LCP; trong editor, hai block cùng trang có thể trùng ID checkbox vì mỗi ServerSideRender là một request riêng (ngoài site không bị).

## ADR-022: Nội Dung jannieedu.com Cũ Cho Trang Sản Phẩm, Khóa Học & Cảm Nhận (Step 15)

- **Ngày quyết định:** 2026-10-30
- **Trạng thái:** Đã triển khai — chủ dự án gửi 3 bản lưu Wayback, yêu cầu "bám content cũ", duyệt `/plan` với các mặc định D1–D9.

### 1. Bối cảnh
- `/my-favorite-product/`, `/course/`, `/client-success-story/` chỉ có khung `[CẦN NỘI DUNG]` (UNK-08, UNK-03); trang chủ và `/coaching/` hiện 3 cảm nhận wireframe chưa xác minh kèm 5 sao.
- Nguồn: Wayback 2025-01-19 (sản phẩm), 2025-01-23 (khóa học), 2025-02-07 (cảm nhận); giá ở JSON-LD các trang `/san-pham/*` bản lưu 05/2024. Wayback **không** lưu ảnh nội dung (21/24 ảnh trả 404), nhưng WordPress cũ ở jannieedu.com vẫn trả ảnh gốc (TLS hết hạn).

### 2. Quyết định
1. **Một nguồn câu chữ:** `scripts/lib/jn-legacy-content.json`, chép nguyên văn; chỉ đổi hoa/thường theo tiêu đề site và 9 lỗi chính tả đã duyệt (`corrections`). `eyebrow_site` giữ eyebrow đã duyệt của site mới ở trang cũ không có. `verify-step-15-legacy-pages.mjs` đối chiếu từng chuỗi với bản trích Wayback ở `docs/jn/legacy-content/`.
2. **Trang dựng từ nguồn đó** bằng `scripts/lib/jn-legacy-blocks.php` (core blocks + style của theme, không `core/html`); migration `scripts/build-step-15-legacy-content.php` (idempotent, revision để hoàn tác, đọc lại để chắc kses không sửa markup).
3. **Khóa học là nội dung, không phải sản phẩm:** 26 khóa (Phun Xăm 15, Salon 10, Spa 1) không giá, nút "Đặt Lịch Tư Vấn" → `#booking-form` như trang cũ. 6 "sản phẩm khóa học" có giá năm 2024 không tạo.
4. **6 sản phẩm WooCommerce** (slug cũ, danh mục "Sản phẩm yêu thích", thứ tự thẻ cũ); giá 05/2024, Xà bông thảo dược "Liên hệ" (`woocommerce_empty_price_html`). `product_base` → `san-pham` để URL cũ chạy lại. Thẻ: ảnh, tên, mô tả, giá, "Mua Ngay" → trang sản phẩm (`core/read-more`) như trang cũ.
5. **3 cảm nhận thật** (Loan Nguyễn, Thu Minh, Hồng Hạnh) thay 3 cảm nhận wireframe trên toàn site; 91–93 chuyển Nháp (không xoá). `jn_rating = 0` nghĩa là không có sao: `jn/rating` không render gì, mặc định của meta đổi 5 → 0 (bài cũ lưu 5 tường minh nên không đổi).
6. **Video qua facade:** block `jn/video` (ảnh bìa tự host + link YouTube; `view.js` vanilla chỉ tạo iframe `youtube-nocookie.com` khi bấm). CSP thêm `frame-src https://www.youtube-nocookie.com` ở 5 file (6 chỗ). Meta `jn_headline`, `jn_video_url` (chỉ nhận URL YouTube, lưu dạng watch chuẩn), `jn_video_poster_id`.
7. **Ảnh gốc, không AI:** 19 ảnh gốc tải một lần từ máy chủ cũ (`curl -k`, chỉ GET ảnh tĩnh, xem bằng mắt) + 3 ảnh bìa từ `i.ytimg.com`, lưu `scripts/.media-masters/<slug>.orig.<ext>` (gitignored) và sha256 ở `jn-legacy-media.json` + `docs/jn/legacy-content/originals.sha256`. `jn_placeholder_load_original()` bắt buộc file tồn tại và khớp sha256, không gọi mạng; ảnh chỉ thu nhỏ (scale 1×) rồi mã hoá lại (bỏ metadata).

### 3. Hệ quả
- Deploy: chạy `import-legacy-media.php` (22 khóa Step 15, cần các file `.orig.*`) rồi `build-step-15-legacy-content.php` (`JN_ALLOW_DESTRUCTIVE=1` trên production), xoá page cache. Trên cài mới: chạy sau `build-step-3.php` và `setup-step-4-woocommerce.php`.
- Chờ chủ dự án trước go-live: xác nhận giá (2024 có thể cũ), phí/chính sách giao hàng (shop đang tắt giao hàng), rà pháp lý mô tả The Herpes Treatment, quyền dùng video học viên (video nằm trên kênh YouTube "khoa nguyễn"), logo Ngôi Sao không còn link (tên miền chết, không có bản lưu).
- Còn giữ: huy hiệu "+180% Doanh Thu" (số liệu wireframe) cạnh cảm nhận thật trên `/` và `/coaching/`; trang Cảm nhận cũ lặp lại danh sách khóa học (trùng chủ đề `/course/`).
- **Sự cố khi chạy lần đầu:** `wc_get_products( array( 'slug' => … ) )` bỏ qua `slug` và trả sản phẩm đầu tiên → ghi đè 3 gói Coaching (ID 163–165). Đã khôi phục toàn bộ DB từ `backups/pre-step-15-2026-10-30.sql.gz` (bản hỏng lưu ở `backups/step-15-incident-damaged-2026-10-30.sql.gz`), sửa sang `get_posts( name )` và thêm chốt chặn từ chối ghi vào gói Coaching hoặc sản phẩm khác slug.
- **Sự cố thứ hai:** `scripts/check-launch-blockers.php` (Step 9) xoá vĩnh viễn mọi sản phẩm không phải 3 gói Coaching và tính chúng là "DEMO còn sót" → lần chạy kiểm tra đầu tiên đã xoá 6 sản phẩm Step 15. Đã sửa: sản phẩm có slug trong `jn-legacy-content.json` **và** meta `_jn_legacy_source` là sản phẩm chính thức (không xoá, không tính vào BL-07b); `test-step-15` khoá quy tắc này. Migration tạo lại 6 sản phẩm (ID mới).
- **Sau code review độc lập (0 CRITICAL/HIGH, 4 MEDIUM, 3 LOW):** migration tra trang theo slug, kiểm tra slug danh mục 41 và block đồng bộ 95 trước khi ghi, chỉ chuyển Nháp 91–93 khi đúng là `jn_testimonial`, cập nhật cảm nhận bằng `wp_update_post()`; vòng focus của `jn/video` vẽ vào trong khung (khung cắt tràn); ẩn dòng chức danh rỗng trên thẻ cảm nhận dùng chung; `AXE_PATH` thử `axe-core` cài được trước khi dùng cache npx. MEDIUM "bán hàng vật lý khi đang tắt giao hàng" giữ theo lựa chọn D9 của chủ dự án → UNK-15.

## ADR-023: Nội Dung jannieedu.com Cũ Cho My Story & Coaching — Thay Nội Dung Wireframe Của `/coaching/` (Step 16)

- **Ngày quyết định:** 2026-10-30
- **Trạng thái:** Đã triển khai — chủ dự án gửi 2 bản lưu Wayback (My Story 2026-05-20, Coaching 2026-07-10), yêu cầu "bám content cũ", duyệt `/plan` với mặc định D1–D6.

### 1. Bối cảnh
- `/coaching/` là trang chuẩn của wireframe (Step 3, `code.html`): hero editorial, 3 trụ cột, 3 thẻ gói, chuyên gia, cảm nhận, FAQ, form. Quy tắc §1 coi wireframe là nguồn câu chữ; chủ dự án nay chọn nội dung trang cũ.
- `/my-story/` chỉ có khối chuyên gia (chép từ trang chủ) và khung `[CẦN NỘI DUNG]`.

### 2. Quyết định
1. **Câu chữ** thêm vào `scripts/lib/jn-legacy-content.json` (`my_story`, `coaching`), nguyên văn; sửa "tim hiểu", "đặt biệt", dấu ” thừa (bảng `corrections`); eyebrow giữ chữ đã duyệt của site mới (`eyebrow_site`). Tuyên bố của trang cũ (Hồng Nhung salon & Spa 2 cơ sở Bình Phước, Oscar for Beauty 2018, Kbiz) giữ nguyên, chờ chủ dự án xác nhận (UNK-03).
2. **`/my-story/`:** hero chia đôi (ảnh banner cũ) → 5 khối ảnh/chữ xen kẽ → lời mời đặt lịch → form.
3. **`/coaching/`:** 8 khối cũ theo thứ tự (hero chia đôi, học cách tự làm đẹp + thẻ khóa học, 5 điều được học, thẻ chương trình, dải ảnh "Kinh Nghiệm Và Sự Tận Tâm", "Phản Hồi Từ Học Viên" = 3 video Step 15, lợi ích đặc biệt, dải ảnh "Đăng Ký Ngay") → **giữ** "Section 3 — Coaching Tiers" và FAQ (D1: trang cũ không có phần tương đương, form vẫn hỏi gói) → form. Bỏ: hero editorial, 3 trụ cột, khối chuyên gia, khối cảm nhận dạng thẻ (synced 94). Pattern `jannie/hero-editorial` vẫn còn trong thư viện.
4. **Dải ảnh** là `core/cover`, lớp phủ đen 60% (ảnh tông kem → nền tối nhất ~rgb(96,96,96), chữ trắng > 6:1; axe không đo được chữ trên ảnh nên tính tay), eyebrow trắng.
5. **Ảnh hero là LCP:** `jn-performance` thêm `jn_legacy_hero_ids()` (nhận diện theo ID, cả ngoài request) và `jn_legacy_hero_lcp_id()` (trang hiện tại, chỉ khi nội dung thật sự chứa ảnh); preload, `eager` + `fetchpriority`, ngưỡng lazy và `sizes` (`JN_LEGACY_HERO_SIZES`, chung cho `<img>` và preload) đi theo ảnh đó.
6. **Ảnh:** 12 ảnh gốc từ WordPress cũ (sha256). 6 ảnh Coaching chỉ thu nhỏ từ bản gốc; **6 ảnh My Story** (`my_story_banner` `1024×576` → `1920×1080`, và 5 ảnh `my_story_1`, `my_story_prize`, `my_story_su_menh`, `my_story_value`, `my_story_mission` `400×600` → `800×1200`) được chủ dự án duyệt `/plan` nâng cấp 4x bằng `realesrgan-ncnn-vulkan` (`realesrgan-x4plus`, kiểm tra `sha256` của `.orig.png` cục bộ trước khi phóng `4096×2304` / `1600×2400`), **khôi phục chi tiết khuôn mặt bằng YuNet + CodeFormer ONNX** (`scripts/lib/jn-restore-faces.py`, `face_restore: true`, ngưỡng `confidence >= 0.90`, `fidelity = 0.6`, khớp thống kê màu LAB 100%) rồi thu nhỏ `<= 0.5×` kèm `sharpen: 0.25`, mã hóa WebP `quality: 88` và AVIF (cả bản chính lẫn sub-size) ở `avif_quality: 82` kèm làm nét hậu kỳ sub-size (`min(0.18, $sharpen)`), và **đổi tên file đầu ra sang `jn-legacy-my-story-*-hd.webp`** (xoá file cũ + ghi lại `<img src>`/`srcset` trên `/my-story/` theo quy tắc phá `Cache-Control: immutable` của ADR-019). Mọi nút → `#booking-form` (D6).

### 3. Hệ quả
- Các kiểm tra gắn cứng vào wireframe Coaching được chuyển sang nội dung mới, không bỏ: Step 8 (hero `/coaching/` = ảnh cũ eager/high; ảnh chuyên gia lazy nay kiểm tra trên `/`; `/my-story/` preload đúng 1 ảnh của nó), home hero ("Coaching có hero riêng"), overlay cleanup (icon ∞/hộp kính chỉ còn trong pattern), placeholder media (ảnh 86 được dùng qua `primaryId` của hero trang chủ). `verify-step-3-visual-parity.mjs` (so `/coaching/` với `screen.png`) không còn ý nghĩa cho trang này.
- Deploy: có các file `.orig.*` (và 6 file `.x4.png` của My Story do `upscale-step-3-media.mjs` sinh trên máy host với Real-ESRGAN 4x + CodeFormer) → `import-legacy-media.php` (12 khóa Step 16) → `build-step-16-legacy-pages.php` (`JN_ALLOW_DESTRUCTIVE=1`) → purge page cache.
- **Bổ sung sau kiểm chứng:** (1) Mô tả SEO đổi ở Step 15 chưa từng lên trang — trường tùy chỉnh TSF do `build-step-8.php` gieo luôn thắng `JN_SEO_Meta::get_page_map()`; nay hai migration gọi `jn_lb_sync_seo_meta()` để chép map vào trường TSF của 5 trang, và test kiểm tra thẻ `<meta name="description">` thật. (2) Làm nét 6 ảnh My Story (chủ dự án duyệt `/plan` và phản hồi ảnh thực tế cùng ngày): lần chạy đầu giữ nguyên tên file `jn-legacy-my-story-*.avif` khiến trình duyệt phục vụ lại ảnh cũ `400×600` từ bộ nhớ đệm `Cache-Control: max-age=31536000, immutable` (ADR-019), đồng thời khuôn mặt nhỏ (`38×50 px` ở ảnh gốc `my_story_prize`, `my_story_value`, `my_story_mission`) bị `realesrgan-x4plus` làm bệt và nén AVIF `quality = 50` làm mờ chi tiết; đã khắc phục triệt để bằng cách đổi tên sang `jn-legacy-my-story-*-hd.webp` (`.avif`), tích hợp khôi phục khuôn mặt `YuNet + CodeFormer ONNX` (`scripts/lib/jn-restore-faces.py`) sau bước 4x, nâng `quality: 88` / `avif_quality: 82` + `sharpen: 0.25` (làm nét cả sub-size AVIF), và gỡ bỏ giới hạn `max-width: 300px` trên mobile để hiển thị đủ `400px` sắc nét 2x Retina. (3) Sau review độc lập: `jn_hero_image_sizes()` xét hero cũ trước (preload và `<img>` cùng `sizes`); trong request trang chỉ ảnh hero của chính trang được ưu tiên; tiêu đề/mô tả SEO My Story theo câu chữ cũ, không nêu giải thưởng chưa xác minh.

## ADR-024: Khung Hình Đầu Tiên Phải Là Khung Cuối — Bỏ Nạp CSS Blocksy/Font Theo Tương Tác, Thêm View Transitions Native (Step 17)

- **Ngày quyết định:** 2026-10-31
- **Trạng thái:** Đã triển khai — chủ dự án báo chữ giật khi chuyển tab, logo hiển thị sai lúc chuyển và cả trang giật; duyệt `/plan`, chọn phương án (a) cho ngân sách LCP.

### 1. Bối cảnh
- Site là WordPress nhiều trang: mỗi tab là một lần tải trang. `jn-performance.php` gắn `ct-main-styles` (Blocksy `main.min.css`) bằng `data-jn-href` và chỉ bật nó **ở lần tương tác đầu tiên** (`pointermove`, `scroll`, `touchstart`…); Playfair Display bị cắt khỏi CSS inline và chỉ nạp bằng `new FontFace()` cũng ở lần tương tác đầu. Code đã lệch khỏi thiết kế Step 8 (PROGRESS §2: nạp ở `load + 150ms`, preload Playfair).
- Đo trên `localhost:8080` (1440 px): ở lần rê chuột đầu tiên logo nhảy xuống 4 px, chữ "JANNIE NGUYEN" dịch phải 16 px và đổi font (194.6 → 163.5 px), menu dịch 2.3 px, footer cao thêm 95 px, form đặt lịch thấp đi 54 px; trang shop: 92/125 phần tử trong `main` dịch tới 32 px. Ở 390 px nút tài khoản và nút menu xếp chồng ở khung đầu. Mục menu active (500 → 600) làm nhãn menu xê dịch 0.3–2.3 px giữa các tab; trang ngắn bật/tắt thanh cuộn làm logo lệch 7.5 px; `scroll-behavior: smooth` làm link `#anchor` chéo trang tự cuộn ~1.2 s.
- CLS không bắt được lỗi: header nhỏ nên mỗi lần dịch chỉ ≈ 0.00002 điểm (trace báo CLS 0.00).

### 2. Quyết định
1. **`ct-main-styles` chặn render** (bỏ khỏi `$async_style_handles`; `Cache-Control: immutable`, 16.6 KB br, nên khi chuyển tab lấy từ cache). **`ct-woocommerce-styles` chặn render trên trang tài khoản và trang sản phẩm** (tại đó nó dàn bố cục: để trễ thì cột tóm tắt sản phẩm nhảy từ dưới gallery lên cạnh gallery, CLS desktop 0.356; form tài khoản dịch tới 364 px); trên shop, giỏ hàng, thanh toán vẫn hoãn (đo: không dịch gì trên màn hình đầu).
2. **Playfair Display khai báo inline** (bỏ bước cắt `@font-face`, bỏ `FontFace()` trong bộ nạp trễ, bỏ khối `<noscript>`), **preload Playfair normal + Geist** (đúng ngân sách 2 font của Step 8). Đã thử bỏ preload: FCP mobile lên 1.51 s và CLS `/coaching/` 0.063 do font đổi muộn, LCP không cải thiện → giữ preload. Italic không preload nhưng tải từ cache trước FCP khi chuyển tab (đo: 46–293 ms < FCP).
3. **Nhãn menu đứng yên:** mục active giữ 600 nhưng giảm `letter-spacing` đúng phần chữ đậm nở ra (`--jn-nav-semibold-tracking-offset: -0.0092em`, đo ở 12–13 px); sai lệch còn ≤ 0.13 px. Không dùng mẹo giữ chỗ chữ đậm vì làm menu rộng thêm ~8 px khi khe "BOOKING" → "ĐĂNG NHẬP" ở 1280 px chỉ còn 8.7 px.
4. **`scrollbar-gutter: stable`** trên `html`.
5. **View Transitions giữa các trang** (`@view-transition { navigation: auto }` trong `@media (prefers-reduced-motion: no-preference)`): header có `view-transition-name: jn-header` và nhóm của nó không chạy hoạt ảnh vị trí/kích thước (chỉ cross-fade trạng thái menu active tại chỗ); drawer đang mở (`#offcanvas.active`) được chụp riêng để mờ dần phía trên header; nội dung cũ mờ trong `--jn-motion-fast`, nội dung mới hiện và trượt lên 8 px trong `--jn-motion-page` (300 ms) với `--jn-ease-ios` (`cubic-bezier(0.32, 0.72, 0, 1)`). Chỉ animate `opacity`/`transform`. Trình duyệt chưa hỗ trợ hoặc giảm chuyển động: chuyển tức thì như cũ.
6. **Anchor:** `html { scroll-behavior: auto }` (ghi đè `html{scroll-behavior:smooth}` của Blocksy) và `html.jn-smooth-scroll` bật cuộn mượt ở lần tương tác đầu (`inc/motion.php`): cú nhảy của trình duyệt tới `#anchor` của trang khác (chạy sau `load`) là tức thì, còn anchor bấm trong trang vẫn cuộn mượt.
7. **Ngân sách LCP 2.0 s → 2.5 s** (ngưỡng "good" của Core Web Vitals) trong `lighthouserc.json` và `scripts/run-step-8-lhci.mjs` — phương án (a) chủ dự án chọn khi CSS chặn render đẩy LCP mobile lên 1.81–2.49 s.

### 3. Hệ quả
- Lighthouse mobile (cache lạnh): FCP trang nội dung 0.79 s → 0.91–1.36 s, LCP 1.51–1.89 s → 1.81–2.41 s, Performance 97–100, CLS ≤ 0.036; desktop vẫn 100 (FCP ~0.37 s). **`/my-favorite-product/` mobile nằm sát mép:** FCP dao động 1.36–1.66 s giữa các lần chạy (ngưỡng 1.5 s), LCP 2.41–2.49 s (ngưỡng 2.5 s).
- Khi chuyển tab (cache ấm) CSS và font đều lấy từ cache: khung đầu trùng khung cuối, không còn bước "sửa lại" khi rê chuột.
- Ảnh chụp kiểm chứng Step 8 và Step 13 chụp lại theo trạng thái mới (logo căn giữa, footer đầy đủ) — đúng với header đã duyệt ở `step-2-header-1440.png`.
- Chưa xác minh: hiệu ứng blur của header có thể không nằm trong ảnh chụp chuyển cảnh (nền header đục 90% nên khó thấy); Safari/Firefox thật; back/forward khi không có bfcache.

## ADR-025: Trang Sản Phẩm Yêu Thích Theo Bản "Nâng Cấp My Favorite Product" (Step 18)

- **Ngày quyết định:** 2026-10-31
- **Trạng thái:** Đã triển khai — chủ dự án gửi bản HTML mẫu (`docs/jn/favorite-products/mockup-2026-10-31.html`, sha256 `4ed06038…3a68`), duyệt `/plan` với mặc định D1–D7 và cung cấp số Zalo `0559 912 642`. Ngân sách FCP nhóm shop nâng 1.5 → 1.7 s theo lựa chọn (a) của chủ dự án (mục 2.9).

### 1. Bối cảnh
- `/my-favorite-product/` (trang 12, đồng thời là trang shop — ADR-006) đang là page intro chung + ảnh tổng hợp + lưới WooCommerce (ADR-022).
- Bản mẫu là một trang tĩnh độc lập: header/footer/menu anchor riêng, Be Vietnam Pro qua Google Fonts CDN, giỏ hàng demo trong bộ nhớ, dữ liệu 6 sản phẩm gõ cứng trong JS, chai vẽ bằng CSS thay cho ảnh, hiệu ứng hiện dần (opacity 0 + blur) cả trên H1, nút "Mở Zalo" chỉ mở `chat.zalo.me` chung chung, 3 màu chữ không đạt AA (#8C6A52 3.82–4.49:1, #8E8074 3.35:1, #AD8C73 2.43:1).

### 2. Quyết định
1. **Dựng lại, không dán HTML** (§2 cấm `core/html`): 3 pattern `jannie/favorite-hero` (hero + dải chữ), `jannie/favorite-collection` (giới thiệu + bộ sưu tập), `jannie/favorite-manifesto`; core blocks + 3 block động mới trong jn-core (cùng kiểu `jn/hero-portrait`, `jn/video`): **`jn/product-stage`** (minh họa trang trí, `role="img"`), **`jn/marquee`** (track `aria-hidden` lặp 2 lần + nút tạm dừng), **`jn/product-actions`** (nút thẻ sản phẩm theo trạng thái giá + nút liên hệ chung; hộp thoại `<dialog>` in một lần qua `wp_footer`, `JN_Consult`). `jn_lb_products_page()` dựng trang từ chính 3 pattern nên `build-step-15` và `build-step-18` cho cùng một nội dung.
2. **D1 — cột chữ hero theo hệ tiêu đề chung** (`jn-hero-eyebrow/title/lead`, như ADR-021); chữ giữ nguyên văn bản mẫu, chỉ đổi hoa/thường theo quy ước site. **D2 — nội dung theo bản mẫu:** H1 "Vẻ đẹp, *rất riêng.*"; title/meta/H2/schema vẫn giữ từ khoá "Sản Phẩm Yêu Thích".
3. **D3 — thẻ dùng ảnh thật** trong khung "mat" của bản mẫu; số 01–06 bằng CSS counter (`content: … / ""` để không lọt vào tên heading). **D4 — minh họa hero** giữ bố cục quỹ đạo/bệ/chai nổi nhưng thân chai trắng/kem, băng nhãn be như bao bì The Balance thật.
4. **D5 — "Mua Ngay" → trang sản phẩm** (giao hàng còn tắt, UNK-15); bỏ giỏ demo và nút giỏ trên header. Sản phẩm không giá: "Tư vấn qua Zalo" (chính) + "Xem sản phẩm" như bản mẫu.
5. **D6 — mô tả:** đoạn ngắn của bản mẫu (`products.items[].teaser`) là *short description*; mô tả gốc jannieedu.com chuyển sang *description* (tab Mô tả trang sản phẩm) — không mất chữ nào. `build-step-15` ghi theo cùng quy tắc.
6. **D7 — Zalo:** option `jn_contact_zalo_url` = `https://zalo.me/0559912642` (chỉ nhận `https://zalo.me/<id>`). Khi option rỗng: nút ghi "Nhận tư vấn", hộp thoại gửi email `contact@jannienguyen.vn`; không bao giờ dẫn tới `chat.zalo.me` chung.
7. **Khác bản mẫu có chủ đích:** header/footer chung (header là mốc View Transition — ADR-024); Geist tự host thay Be Vietnam Pro; token site, màu chữ thiếu AA → `--jn-secondary` / `--jn-champagne-text-sm`; không ẩn LCP (bỏ reveal + blur ở hero, phần dưới dùng reveal chung của `inc/motion.php`); vòng lặp trang trí dừng khi giảm chuyển động, máy yếu và khi bấm nút tạm dừng (WCAG 2.2.2); spotlight dịch bằng `transform`; "Câu chuyện của Jannie" → `/my-story/`; breakpoint site 1023.98/767.98/639.98; bỏ nút lên đầu trang và toast (trạng thái sao chép hiện trong hộp thoại, `role="status"`); ảnh tổng hợp 10606 rời trang (vẫn trong thư viện).
8. **CSS riêng trang:** `assets/css/favorite-products.css` chỉ inline trên trang có các section này (`jannie_page_uses_favorite_css()`), không vào bundle toàn site; `jannie_build_css_bundle()` dùng chung cơ chế chữ ký nội dung. Trên trang này `product-image.css` / `product-summary.css` của WooCommerce được hoãn (`data-jn-href`). Đã xoá các rule `.jn-legacy-products` không còn dùng khỏi `legacy-pages.css`.
9. **Ngân sách FCP nhóm shop 1.5 s → 1.7 s** (`/my-favorite-product/`, `/san-pham/*`) trong `lighthouserc.json` và `scripts/run-step-8-lhci.mjs` — phương án (a) chủ dự án chọn ngày 2026-10-31, cùng cách ADR-024 nâng LCP. Trang nội dung và giỏ hàng/thanh toán giữ 1.5 s. Các phương án không chọn: (b) chỉ inline `hero-home.css`/`legacy-pages.css` trên trang cần (đụng mọi trang), (c) hoãn CSS phần dưới màn hình đầu (rủi ro thẻ/hộp thoại chưa có style khi cuộn/bấm).

### 3. Hệ quả
- Kiểm chứng: `test-step-18-favorite-products.php` **97/97** (RED trước: 17 pass / 69 fail); `verify-step-18-favorite-products.mjs` **17/17** (sha256 + 21 chuỗi nguyên văn bản mẫu, 6 viewport không tràn ngang, H1 vẽ ngay ở khung đầu, bàn phím mở/đóng hộp thoại và trả focus, nút tạm dừng, giảm chuyển động, axe 0 vi phạm cả khi mở hộp thoại, 0 lỗi console). Hồi quy PHP 8/10/12/13/14/15/16/17/home-hero xanh; verify-step-13/15/17 xanh. Assertion cũ gắn bố cục cũ được **chuyển** (Step 13: header shop là hero riêng theo hệ chung; Step 15: thẻ hiện teaser, mô tả gốc kiểm tra trên 6 trang `/san-pham/*`; verify-step-15 bỏ khoá `teaser` khỏi đối chiếu Wayback).
- **Lighthouse** (`docs/jn/lighthouse/step-18/`): desktop 100/100/100/100, FCP 0.37 s, LCP 0.54 s. Mobile Performance 0.97, A11y/BP/SEO 100, LCP 2.41 s (trước 2.48 s; phần tử LCP là lời dẫn hero, không còn là ảnh), CLS 0. **FCP mobile 1.66 s ở cả 3 lần > ngưỡng 1.5 s của nhóm shop trong `lighthouserc.json`** (trước: 1.36/1.36/1.66 s). Nguyên nhân: tài liệu HTML nén 39.5 → 43.6 KB (≈ 4.2 KB là CSS trang inline) vượt mốc vòng TCP thứ hai mà Lighthouse mô phỏng; `main.min.css` của Blocksy vẫn là tài nguyên chặn render duy nhất. Đã thử: hoãn 2 stylesheet block WooCommerce (bỏ chặn render, FCP không đổi) và xoá CSS chết (−150 byte). Các hướng còn lại (hoãn CSS phần dưới, ngừng inline `hero-home.css`/`legacy-pages.css` trên mọi trang, giản lược minh họa) đụng nguyên tắc ADR-024 hoặc kiến trúc CSS toàn site → chủ dự án chọn (a), xem mục 2.9; đối chiếu lại 3 lần đo đã lưu với `lighthouserc.json` mới: 0 assertion điểm số/thời gian fail (FCP median 1.66 s ≤ 1.7 s — biên còn ~40 ms). Ngân sách cứng §5 cho shop (Performance ≥ 90) vẫn đạt.
- **Code review độc lập** (0 CRITICAL/HIGH, 1 MEDIUM, 5 LOW): đã sửa MEDIUM — kéo bôi đen chữ trong ô lời nhắn ra ngoài làm đóng hộp thoại (nay chỉ đóng khi cả nhấn lẫn nhả đều trên nền); và 4 LOW — migration kiểm hết 6 sản phẩm rồi mới ghi; hoãn CSS WooCommerce chỉ khi `jn-favorite` thực sự được nạp (`wp_style_is`); focus về nút "Sao chép" sau khi sao chép dự phòng; thay `{name}`/`{price}` một lượt. Giữ nguyên có chủ đích: CSS trang nạp ở cả `add_editor_style` lẫn `enqueue_block_assets` (cùng cách `hero-home.css`/`legacy-pages.css`); nhận diện trang bằng dấu hiệu trong nội dung (nếu sau này đưa section vào synced pattern thì cần thêm điều kiện). Editor: trang 12 mở với 33 block, 0 lỗi validation.
- Deploy: chạy `build-step-18-favorite-products.php` (`JN_ALLOW_DESTRUCTIVE=1` trên production, có `dry-run`), xoá page cache. Đổi số Zalo: `wp option update jn_contact_zalo_url https://zalo.me/<số>` rồi xoá page cache.
- Chưa làm: số `0559 912 642` chưa được đưa vào `jn_brand_telephone`/schema hay hộp Hotline (UNK-04 vẫn mở phần địa chỉ, hotline chính thức); tuyên bố y khoa trong mô tả gốc The Herpes Treatment vẫn chờ rà pháp lý (ADR-022), nay chỉ còn ở tab Mô tả của trang sản phẩm.
