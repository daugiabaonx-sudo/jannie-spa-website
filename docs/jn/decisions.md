# Nhật Ký Quyết Định Kiến Trúc — ADR Log (`docs/jn/decisions.md`)

> **Dự án:** Website Jannie Nguyễn — Luxury Beauty Academy & Mentorship
> **Cập nhật lần cuối:** 2026-10-27 (JN Step 0)
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
   - Đặt đường dẫn đăng nhập tùy chỉnh tại **`/phong-lam-viec-7429`** (qua `WPS Hide Login` kết hợp quy tắc bảo vệ trong `jn-hardening.php`).
2. **Bảo toàn khả năng cập nhật trên ổ đĩa (Disk Updatability Rule):**
   - **Không đổi tên thư mục trên đĩa** của parent theme (`blocksy`) hay các plugin bên thứ ba (`woocommerce`, `wps-hide-login`, `two-factor`...) bên trong `/app/themes/` và `/app/modules/` để trình cập nhật của WordPress/WP-CLI vẫn hoạt động bình thường.
   - Thay vào đó, sử dụng quy tắc rewrite trên máy chủ web (`.htaccess` / cấu hình Apache/Nginx) và bộ lọc URL tài nguyên trong `jn-hardening.php` để ánh xạ các đường dẫn tài nguyên tĩnh ra ngoài thành các đường dẫn trung lập (không chứa chuỗi `wp-content`, `wp-includes`, `blocksy`, hay `?ver=X.Y.Z` trong mã nguồn HTML trả về cho khách).
3. **Khóa chặt các điểm rò rỉ thông tin (Hardening Controls tại Step 1 & Step 7):**
   - Khi chưa đăng nhập: truy cập `/wp-admin`, `/wp-login.php`, `/xmlrpc.php`, `/readme.html`, `/license.txt`, `/wp-config-sample.php` đều trả về mã trạng thái **`404 Not Found`** (hoặc bị chặn truy cập hoàn toàn, tuyệt đối không chuyển hướng `302` tới trang đăng nhập mới `/phong-lam-viec-7429`).
   - Gỡ bỏ thẻ `<meta name="generator">`, `X-Pingback`, link header `rel="https://api.w.org/"` nếu cần ẩn, tắt tính năng liệt kê tác giả qua `/api/wp/v2/users` (trả về `401`/`403`) và `?author=N` (trả về `404`).
   - Tắt chỉnh sửa file trong trang quản trị (`DISALLOW_FILE_EDIT = true`), ẩn bình luận mặc định trên các trang tĩnh/dịch vụ, và thiết lập đầy đủ các HTTP Security Headers.

### 3. Hệ quả & Ràng buộc
- **Tích cực:** Mã nguồn HTML và các đường dẫn tài nguyên công khai hoàn toàn sạch dấu vết `wp-content`, `wp-includes`, `wp-json`, `wp-login.php`, `xmlrpc.php`; trình quét tự động không thể dò tìm tài khoản quản trị hay đường dẫn đăng nhập; việc nâng cấp WordPress Core và WooCommerce vẫn diễn ra trơn tru.
- **Kiểm thử bắt buộc (Step 7):** Phải vượt qua bộ kịch bản kiểm thử tự động kiểm tra mã phản hồi HTTP (`404`/`403`) và quét toàn bộ DOM HTML của khách chưa đăng nhập để xác nhận không rò rỉ chữ ký WordPress.
