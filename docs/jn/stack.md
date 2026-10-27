# Hồ Sơ Công Nghệ & Công Cụ Phát Triển (`docs/jn/stack.md`)

> **Dự án:** Website Jannie Nguyễn — Luxury Beauty Academy & Mentorship
> **Cập nhật lần cuối:** 2026-10-28 (JN Step 8 — Đã cài đặt và cấu hình `wp-super-cache` `v3.1.4` và `The SEO Framework` (`autodescription` `v5.2.0`), nâng cấp `jn-performance.php` `v2.0.0` và `jn-hardening.php` `v2.0.0`, tích hợp `mod_brotli`/`mod_deflate`, AVIF/WebP responsive images, Speculative Loading, Third-Party Facade, JSON-LD Schema `@graph` và Lighthouse CI `lighthouserc.json`)
> **Tham chiếu quy tắc:** `.agent/rules/jn-project.md` (§2 Fixed stack, §3 Engineering, §5 Performance, §6 Hide WordPress)

---

## 1. Kiểm Kê Toolchain Tại Máy Trạm Local (Host Machine Audit — Step 0)

Kết quả kiểm tra trực tiếp môi trường macOS tại thư mục workspace `/Users/daugiabao/Documents/SUNEXT/Spa/Website` vào ngày `2026-10-27`:

| Công cụ | Trạng thái trên Host | Đường dẫn thực thi (`which`) | Phiên bản xác thực | Vai trò trong dự án |
| :--- | :--- | :--- | :--- | :--- |
| **Docker Engine** | **CÓ SẴN** | `/usr/local/bin/docker` | `Docker version 29.7.2, build a7dcaa6` | Chạy container PHP-FPM/Apache, MariaDB/MySQL, WP-CLI ở Step 1 |
| **Docker Compose** | **CÓ SẴN** | `/usr/local/bin/docker-compose` | `Docker Compose version v5.4.0` | Điều phối multi-container stack (`wordpress`, `db`, `wpcli`) |
| **Node.js** | **CÓ SẴN** | `/Users/daugiabao/.nvm/versions/node/v24.19.0/bin/node` | `v24.19.0` | Chạy công cụ kiểm thử tự động (Playwright, Lighthouse CI, axe-core) |
| **npm / npx** | **CÓ SẴN** | `/Users/daugiabao/.nvm/versions/node/v24.19.0/bin/npm` | `11.17.0` | Quản lý devDependencies tại thư mục gốc workspace (không bao giờ deploy) |
| **Google Chrome** | **CÓ SẴN** | `/Applications/Google Chrome.app/Contents/MacOS/Google Chrome` | `Google Chrome 156.0.8078.48` | Chụp ảnh màn hình đối chiếu trực quan, chạy Lighthouse & Playwright |
| **Git** | **CÓ SẴN** | `/usr/bin/git` | `git version 2.54.0 (Apple Git-157)` | Quản lý phiên bản với whitelist `.gitignore` tại gốc workspace |
| **Python 3** | **CÓ SẴN** | `/usr/bin/python3` | `Python 3.9.6` | Chạy script kiểm chứng nội dung (`verify-wireframe-text.py`) và `fontTools` (`pyftsubset`) |
| **cURL** | **CÓ SẴN** | `/usr/bin/curl` | `curl 8.7.1` | Kiểm tra HTTP headers, REST API, trạng thái phản hồi |
| **PHP CLI** | **CHƯA CÀI ĐẶT** | *Không có trên host* | — | Sẽ chạy bên trong Docker container (`php:8.3-apache` / `wordpress:php8.3`) từ Step 1 |
| **MySQL / MariaDB** | **CHƯA CÀI ĐẶT** | *Không có trên host* | — | Sẽ chạy bên trong Docker container (`mariadb:11.4` hoặc `mysql:8.4`) từ Step 1 |
| **WP-CLI (`wp`)** | **CHƯA CÀI ĐẶT** | *Không có trên host* | — | Sẽ chạy qua container `wordpress:cli-php8.3` kết nối trực tiếp vào `./wordpress` từ Step 1 |
| **Composer** | **CHƯA CÀI ĐẶT** | *Không có trên host* | — | Sẽ chạy qua Docker image `composer:2` hoặc cài trong container dev để chạy PHPUnit & PHPCS/WPCS |

### Kết luận hạ tầng Step 1
Do máy trạm host đã có sẵn **Docker (`29.7.2`)**, **Docker Compose (`v5.4.0`)**, **Node.js (`v24.19.0`)** và **Google Chrome (`156.0`)**, nhưng **không cài đặt PHP, MySQL, WP-CLI hay Composer trực tiếp trên macOS**, toàn bộ môi trường chạy WordPress, cơ sở dữ liệu, WP-CLI, Mailpit, PHPUnit và PHPCS + WPCS ở **Step 1** được đóng gói qua **Docker Compose** gắn kết (bind-mount) trực tiếp thư mục `./wordpress`.

- **Alias ngắn gọn để chạy WP-CLI theo yêu cầu (`wpcli` service):**
  ```zsh
  alias jnwp='docker compose run --rm wpcli wp'
  ```
  *(Hoặc dùng script wrapper `./scripts/wp.sh <lệnh>` tại thư mục gốc workspace).*

---

## 2. Kiến Trúc Phần Mềm Cố Định (Production Runtime Stack)

Tuân thủ nghiêm ngặt `.agent/rules/jn-project.md` §2:

### 2.1. Nền tảng Cốt lõi & Giao diện
1. **WordPress Core (`7.1.3`, `$wp_db_version = 61833`):**
   - Đặt tại `./wordpress/`.
   - Tuyệt đối không chỉnh sửa bất kỳ file nào thuộc WordPress Core.
   - Tách thư mục nội dung khỏi đường dẫn mặc định `wp-content`: sử dụng `WP_CONTENT_DIR` / `WP_CONTENT_URL` trỏ về `/app`, `PLUGINDIR` / `WP_PLUGIN_DIR` trỏ về `/app/modules`, đường dẫn upload trỏ về `/media`, và tiền tố REST API đổi thành `/api`.
2. **Parent Theme — Blocksy (`v2.1.60`, Free, từ `wordpress.org`, `Tested up to: 7.1`) + gói ngôn ngữ `vi`:**
   - Chỉ sử dụng bản miễn phí (**không dùng Blocksy Pro**).
   - **Blocksy Companion (Free):** Chỉ cài đặt nếu có tính năng miễn phí bắt buộc nằm trong đó và phải ghi rõ lý do trong `docs/jn/decisions.md` (mặc định: không cài đặt nếu child theme + `theme.json` đã đáp ứng đủ).
3. **Child Theme — `jannie` (`/app/themes/jannie`):**
   - Chứa toàn bộ phần trình bày giao diện (presentation layer): `style.css` (header tối giản không lộ chữ WordPress/Blocksy ở HTML công khai), `theme.json` (khai báo toàn bộ bảng màu Luxury Warm Ivory, thang chữ fluid `clamp()`, khoảng cách, bo góc, bóng đổ), `functions.php`, `inc/woocommerce.php`, `assets/css/woocommerce.css`, các Block Style Variations, Block Patterns (`jannie/*`), và tài sản tự host (`assets/fonts/*.woff2`, `assets/img/jn-email-logo.svg`).
4. **Trình dựng trang — Gutenberg Core Blocks + 3 Custom Blocks nội bộ:**
   - **Được phép:** Các block cốt lõi của WordPress (`core/group`, `core/columns`, `core/column`, `core/heading`, `core/paragraph`, `core/buttons`, `core/button`, `core/image`, `core/list`, `core/list-item`, `core/separator`, `core/navigation`, `core/site-title`, `core/template-part`), các khối WooCommerce (`woocommerce/product-collection`, `woocommerce/cart`, `woocommerce/checkout`) kết hợp với Block Style Variations, Block Patterns, Synced Patterns và 3 block riêng của dự án:
     - `jn/icon` (chèn inline SVG từ bộ 19 icon đã trích xuất + `sparkline`, thay thế font Material Symbols 4 MB).
     - `jn/rating` (hiển thị cụm 5 ngôi sao SVG với nhãn `aria-label` chuẩn WCAG).
     - `jn/booking-form` (render biểu mẫu ứng tuyển/đặt lịch phía server, hỗ trợ chọn trước gói từ tham số URL hoặc sự kiện click ở bảng gói, kiểm tra nonce, honeypot, rate-limit và lưu đơn).
   - **Bị cấm tuyệt đối:** Elementor và mọi page builder khác; các thư viện block bên thứ ba (Spectra, Kadence Blocks, Stackable, GenerateBlocks, Otter...); dùng `core/html` hoặc shortcode để dựng bố cục.

### 2.2. Thương mại Điện tử & Nghiệp vụ Đặt lịch (`jn-core` + MU-Plugins)
1. **WooCommerce (`v11.2.1` — bản ổn định mới nhất tương thích WordPress 7.1, từ `wordpress.org`) + gói ngôn ngữ Tiếng Việt (`vi`):**
   - Cài đặt sạch ở Step 4: tắt theo dõi sử dụng (`woocommerce_allow_tracking = no`), tắt gợi ý Marketplace (`woocommerce_show_marketplace_suggestions = no`), không cài plugin phụ đi kèm, tắt Order Attribution (`woocommerce_feature_order_attribution_enabled = no` — xem `ADR-005`).
   - Bật **HPOS (High-Performance Order Storage)** (`OrderTableDataStore`, tắt đồng bộ ngược `custom_orders_table_data_sync_enabled = no`).
   - Tiền tệ `VND`, `0` chữ số thập phân, dấu phân cách hàng nghìn `"."`, dấu thập phân `","`, ký hiệu `₫` đứng sau cách 1 khoảng trắng (`right_space` → `450.000 ₫`).
   - Sử dụng các trang WooCommerce Blocks chuẩn (**Product Collection** trên `/my-favorite-product/` được chỉ định làm `woocommerce_shop_page_id` theo `ADR-006`, **Cart Block** trên `/cart/`, **Checkout Block** trên `/checkout/`, và **My Account** trên `/tai-khoan/`).
   - Tắt hoàn toàn các script/style của WooCommerce và `wc-cart-fragments` trên những trang nội dung không có giỏ hàng/thanh toán (`/coaching/`, `/my-story/`, `/booking/`...).
2. **Custom Plugin — `jn-core` (`v1.3.0`, `/app/modules/jn-core`):**
   - Khai báo tương thích chính thức với `custom_order_tables` (HPOS) và `cart_checkout_blocks`.
   - Tổ chức theo các thư mục tính năng rõ ràng:
     - `booking/`: Quản lý 3 sản phẩm dịch vụ ảo (`Virtual`, `Simple`, danh mục `"Coaching & Mentorship"`), CPT `jn_booking` riêng tư, máy trạng thái lịch hẹn (`Chờ xác nhận` → `Đã xác nhận lịch` → `Hoàn thành` | `Vắng mặt` / `Đã hủy`), kiểm tra trùng khung giờ, đồng bộ đơn hàng HPOS `WC_Order`, quản lý lộ trình 12 buổi Mentorship, sinh file lịch `.ics` (`Asia/Ho_Chi_Minh`), email giao dịch Tiếng Việt, phân quyền 4 vai trò nhân sự (`JN_Booking_Roles`) kèm ép buộc 2FA (`two-factor` `v0.17.0`) và cô lập tài khoản khách hàng khỏi `/wp-admin/`.
     - `customers/`: Quản lý hồ sơ khách hàng hợp nhất (`JN_Customer_Profile`, `JN_Customer_Service`, `JN_Customer_Admin`, `JN_CSV_Exporter`, `JN_Booking_Privacy`) đồng bộ 2 chiều với `WC_Customer`, tự động gắn lịch hẹn & đơn hàng vãng lai vào tài khoản khi đăng ký/đăng nhập, cảnh báo trùng SĐT/Email (`jn_customer_duplicate_log`), màn hình `"Khách hàng"` chuyên biệt, xuất CSV UTF-8 có BOM, và công cụ Xuất/Xóa dữ liệu cá nhân kèm chính sách lưu trữ lịch hẹn vãng lai đã hủy (`ADR-008`, `ADR-009`).
     - `testimonials/`: Quản lý dữ liệu chứng thực học viên có kiểm chứng (chỉ hiển thị đúng 3 đánh giá gốc đã được chủ dự án cung cấp, tuyệt đối không tự bịa thêm).
     - `blocks/`: Đăng ký 3 custom blocks (`jn/icon`, `jn/rating`, `jn/booking-form`) sử dụng JavaScript thuần với biến toàn cục `wp.*` và `ServerSideRender` (không cần bước build phức tạp hay `node_modules` đi kèm plugin).
3. **Must-Use Plugins (`/app/mu-plugins/`):**
   - `jn-hardening.php` (`v2.0.0`): Ẩn dấu vết WordPress ("Hide WordPress"), tắt XML-RPC, tắt pingbacks/trackbacks, gỡ bỏ thẻ `<meta name="generator">`, ẩn danh sách user qua REST API (`/api/wp/v2/users`) và `?author=N` đối với khách chưa đăng nhập, thay tham số `?ver=` bằng hàm băm SHA-256 10 ký tự `?v=<hash>`, ánh xạ đường dẫn tài nguyên tĩnh sang `/static/{core,core-admin,base,ui,shop,academy,ext}/` và `/api/ajax`, giới hạn đăng nhập sai (`<= 5 lần / 15 phút`), thêm đầy đủ 6 HTTP Security Headers (`Strict-Transport-Security`, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy-Report-Only`).
   - `jn-performance.php` (`v2.0.0`): Tắt `wp-emoji-release.min.js`, tắt `wp-embed`, gỡ `dashicons`, `comment-reply`, `jquery` và tài nguyên WooCommerce trên các trang nội dung; gỡ `zxcvbn` (`400 KB`) và `selectWoo` trên `/cart/`, `/checkout/`, `/tai-khoan/`; nhúng nội tuyến CSS thiết yếu (`jannie-bundle.min.css` & `woocommerce.css`) và trì hoãn CSS/JS không thiết yếu sau FCP; chuyển đổi ảnh tải lên sang định dạng **AVIF** (`quality = 50`) kèm bản dự phòng **WebP** qua `image_editor_output_format`; thêm `<link rel="preload" as="image" imagesrcset="..." imagesizes="..." fetchpriority="high">` cho ảnh LCP; cấu hình **Speculative Loading** (`prerender`/`moderate`) loại trừ đường dẫn giỏ hàng/thanh toán/đặt lịch; cung cấp cổng **Third-Party Facade** (`0` script bên thứ ba mặc định); tích hợp **The SEO Framework** (`autodescription` `v5.2.0`) kèm JSON-LD Schema `@graph` (`Organization`/`ProfessionalService` + `Person` + `Service`/`Offer`), `/sitemap.xml` và `/robots.txt` theo môi trường; tự động dọn dẹp transient hết hạn và kiểm soát kích thước `autoloaded options` (`< 65 KB`).

---

## 3. Danh Mục Plugin Bên Thứ Ba Được Phê Duyệt (`Approved Third-Party Plugins`)

Theo `.agent/rules/jn-project.md` §2, chỉ những plugin sau được phép cài đặt mà không cần xin phép bổ sung:

| STT | Tên Plugin | Nguồn | Mục đích sử dụng | Bước cài đặt & Phiên bản |
| :---: | :--- | :--- | :--- | :---: |
| 1 | **WooCommerce** | `wordpress.org/plugins/woocommerce` | Quản lý đơn hàng (HPOS), khách hàng, sản phẩm dịch vụ coaching/khóa học, cổng thanh toán và trang Tài khoản (`My Account`) | Step 4 (`v11.2.1`) |
| 2 | **WPS Hide Login** | `wordpress.org/plugins/wps-hide-login` | Đổi đường dẫn đăng nhập quản trị sang `/phong-lam-viec-7429` và trả về `404` cho `/wp-login.php` & `/wp-admin` khi chưa đăng nhập | Step 1 / Step 7 (`v1.9.19`) |
| 3 | **Two Factor** | `wordpress.org/plugins/two-factor` | Xác thực 2 lớp (2FA: TOTP, Email, Backup Codes) cho tài khoản quản trị viên và trợ lý | Step 6 (`v0.17.0`) |
| 4 | **WP Super Cache** (*Plugin Page Cache theo `ADR-011`*) | `wordpress.org/plugins/wp-super-cache` | Lưu bộ nhớ đệm trang HTML tĩnh (`WP_CACHE = true`, `advanced-cache.php`), loại trừ `/cart/`, `/checkout/`, `/tai-khoan/`, `order-pay`, `/api/` và cookie phiên WooCommerce/đăng nhập (nếu Production dùng LiteSpeed sẽ chuyển sang **LiteSpeed Cache**) | Step 8 (`v3.1.4`) |
| 5 | **The SEO Framework** (`autodescription`) | `wordpress.org/plugins/autodescription` | Quản lý thẻ tiêu đề, `meta description` tiếng Việt, `canonical`, Open Graph (`1200×630`), `/sitemap.xml` và `/robots.txt` với chữ ký HTML comment bị vô hiệu hóa hoàn toàn | Step 8 (`v5.2.0`) |
| 6 | **FluentSMTP** | `wordpress.org/plugins/fluent-smtp` | Gửi email giao dịch (xác nhận đăng ký tư vấn, thông báo cho trợ lý) qua SMTP/API tin cậy trên môi trường thực tế | Step 9 (khi có `UNK-11`) |
| 7 | **Plugin Cổng Thanh Toán Chính Thức** | Nhà cung cấp cổng thanh toán | Chỉ cài đặt sau khi chủ dự án xác nhận cổng thanh toán và cung cấp thông tin định danh Merchant (`PAYMENT_GATEWAYS`) | Sau khi chốt `UNK-05` |

> **Chính sách kiểm soát:** Gỡ bỏ ngay lập tức 2 plugin mặc định đi kèm bộ cài WordPress (`akismet` và `hello.php`) cùng các theme mặc định (`twentytwentythree`, `twentytwentyfour`, `twentytwentyfive`) ở Step 1. Mọi plugin, dịch vụ bên ngoài hoặc CDN khác ngoài danh sách trên đều **bắt buộc phải xin ý kiến phê duyệt của chủ dự án trước khi cài đặt**.

---

## 4. Danh Mục Công Cụ Chỉ Dùng Cho Phát Triển & Kiểm Thử (`Dev-Only Tools — Never Shipped`)

Các công cụ dưới đây chỉ phục vụ quá trình phát triển, tối ưu tài nguyên và kiểm thử tự động tại máy local/Docker, được loại trừ hoàn toàn khỏi gói triển khai (deploy) qua `.gitignore` và quy trình đóng gói:

| Nhóm công cụ | Tên công cụ / Gói | Môi trường chạy | Mục đích cụ thể |
| :--- | :--- | :--- | :--- |
| **Container & Runtime** | `wordpress:php8.3-apache`, `mariadb:11.4`, `wordpress:cli-php8.3` | Docker Compose | Chạy máy chủ web, cơ sở dữ liệu và WP-CLI cục bộ cho `./wordpress` |
| **Kiểm chuẩn Mã nguồn PHP** | `PHP_CodeSniffer` (`squizlabs/php_codesniffer`) + `wp-coding-standards/wpcs` | Docker (`composer` / `php`) | Kiểm tra 100% code PHP trong `blocksy-jn`, `jn-core` và `mu-plugins` đạt chuẩn WordPress Coding Standards |
| **Kiểm thử Đơn vị & Tích hợp PHP** | `PHPUnit` + `yoast/phpunit-polyfills` + WP Test Suite | Docker (`php`) | Kiểm thử tự động logic đặt lịch, phân loại khách hàng, kiểm tra quyền (capability), nonce và bảo mật trong `jn-core` |
| **Kiểm thử E2E & Trình duyệt** | `@playwright/test` (Playwright) | Host Node.js (`v24.19.0`) | Kiểm thử luồng người dùng đầu-cuối (chọn gói Coaching → điền form → gửi yêu cầu → kiểm tra lưu trữ admin), chụp ảnh đối chiếu ở `375px`, `768px`, `1024px`, `1440px` |
| **Kiểm thử Khả năng Tiếp cận (A11y)** | `@axe-core/playwright` | Host Node.js (`v24.19.0`) | Quét tự động tiêu chuẩn WCAG 2.1 AA (độ tương phản màu sắc, thứ tự thẻ tiêu đề H1→H2→H3, nhãn form, điều hướng bàn phím) |
| **Đo lường Hiệu năng & Core Web Vitals** | `@lhci/cli` (Lighthouse CI) / `lighthouse` | Host Node.js + Google Chrome | Đo điểm Performance, Accessibility, Best Practices, SEO trên giả lập Mobile & Desktop (mục tiêu: Performance ≥ 95 Mobile / ≥ 99 Desktop; 08/08 chỉ số xanh) |
| **Xử lý & Tối ưu Font chữ** | `fonttools` (`pyftsubset`) + `brotli` | Python 3 / Docker | Trích xuất và gộp dải ký tự `latin` + `vietnamese` (`U+0000-00FF, U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+1EA0-1EF9, U+20AB`) thành 1 file `.woff2` duy nhất cho mỗi kiểu chữ |
| **Kiểm chứng Nội dung Wireframe** | `scripts/verify-wireframe-text.py` | Host Python 3 (`3.9.6`) | Đối chiếu tự động 100% các nút văn bản DOM và placeholder trong `code.html` với tài liệu đặc tả `docs/jn/wireframe-spec.md` |
| **Máy chủ Tĩnh Tạm thời** | `npx serve` / Python `http.server` | Host Node.js / Python 3 | Phục vụ trang đối chiếu tĩnh (`font-specimen.html`, `code.html`) khi cần kiểm tra độc lập |
