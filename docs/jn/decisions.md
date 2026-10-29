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
