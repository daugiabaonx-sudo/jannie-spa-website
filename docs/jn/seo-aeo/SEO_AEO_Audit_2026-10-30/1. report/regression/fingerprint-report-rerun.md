# Báo Cáo Quét Dấu Vết WordPress & Hardening (JN Step 7)

- **Thời điểm quét:** `2026-10-30T03:50:43.061Z`
- **Môi trường đích:** `http://localhost:8080`
- **Công cụ quét:** `scripts/jn-fingerprint-scan.mjs`
- **Tổng số URL kiểm tra:** **31** (15 trang/route công khai + 16 đường dẫn dò tìm mặc định)
- **Kết quả tổng thể:** **31/31 PASS — 0 FAIL** (ngoài danh mục residual đã chấp thuận)

---

## 1. Danh Mục Chữ Ký Bị Cấm Đã Kiểm Tra (Headers & Body)

| Mã kiểm tra | Mô tả chữ ký bị cấm | Phạm vi | Kết quả trên toàn bộ 31 URL |
| :--- | :--- | :--- | :--- |
| `wp-content` | `wp-content` path reference | `both` | **0 rò rỉ (PASS)** |
| `wp-includes` | `wp-includes` path reference | `both` | **0 rò rỉ (PASS)** |
| `wp-admin-path` | `/wp-admin/` or `admin-ajax.php` / `admin-post.php` URL | `both` | **0 rò rỉ (PASS)** |
| `wp-json` | `wp-json` REST prefix or discovery link | `both` | **0 rò rỉ (PASS)** |
| `generator` | `generator` meta tag, feed tag, or i18n generator field | `both` | **0 rò rỉ (PASS)** |
| `WordPress` | `WordPress` brand/credit/generator string (outside `@wordpress/*` JS module specifiers) | `both` | **0 rò rỉ (PASS)** |
| `xmlrpc` | `xmlrpc` reference | `both` | **0 rò rỉ (PASS)** |
| `wlwmanifest` | `wlwmanifest` reference | `both` | **0 rò rỉ (PASS)** |
| `EditURI` | `EditURI` (RSD link) | `both` | **0 rò rỉ (PASS)** |
| `pingback` | `pingback` / `X-Pingback` header or link | `both` | **0 rò rỉ (PASS)** |
| `wp-emoji` | `wp-emoji` script or style | `both` | **0 rò rỉ (PASS)** |
| `wp-embed` | `wp-embed` script or class | `both` | **0 rò rỉ (PASS)** |
| `X-Redirect-By` | `X-Redirect-By` HTTP header | `headers` | **0 rò rỉ (PASS)** |
| `X-Powered-By` | `X-Powered-By` HTTP header | `headers` | **0 rò rỉ (PASS)** |
| `ver-query` | `ver=<version>` asset query parameter | `body` | **0 rò rỉ (PASS)** |
| `exposed-versions` | Core / parent theme / WooCommerce version strings (`7.1.3`, `2.1.60`, `11.2.1`) | `both` | **0 rò rỉ (PASS)** |
| `theme-slug` | Theme slugs (`blocksy`, `wp-theme-blocksy`, `wp-child-theme-*`, `themes/jannie`, `id="jannie-*"`) | `both` | **0 rò rỉ (PASS)** |
| `html-comments` | HTML comments (`<!-- ... -->`) | `body` | **0 rò rỉ (PASS)** |
| `security-headers` | Đầy đủ 6 Security Headers (`HSTS`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy-Report-Only`) | `headers` | **100% hiện diện (PASS)** |

---

## 2. Kết Quả Quét 15 Trang & Route Công Khai (Public Pages)

| # | Trang / Route | URL | HTTP Status | Kích thước (Raw / Gzip) | Kết quả | Chi tiết lỗi (nếu có) |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Home | `/` | `200` | 216,375 B / 41,002 B | **PASS** | 0 lỗi |
| 2 | Coaching | `/coaching/` | `200` | 222,590 B / 42,526 B | **PASS** | 0 lỗi |
| 3 | Booking | `/booking/` | `200` | 163,829 B / 31,373 B | **PASS** | 0 lỗi |
| 4 | My Story | `/my-story/` | `200` | 171,897 B / 32,613 B | **PASS** | 0 lỗi |
| 5 | Course | `/course/` | `200` | 164,638 B / 31,520 B | **PASS** | 0 lỗi |
| 6 | Client Success Story | `/client-success-story/` | `200` | 172,747 B / 31,661 B | **PASS** | 0 lỗi |
| 7 | My Favorite Product | `/my-favorite-product/` | `200` | 176,130 B / 32,058 B | **PASS** | 0 lỗi |
| 8 | Single Product | `/product/so-tay-dinh-huong-su-nghiep/` | `200` | 170,376 B / 31,449 B | **PASS** | 0 lỗi |
| 9 | Cart | `/cart/` | `200` | 307,817 B / 70,798 B | **PASS** | 0 lỗi |
| 10 | Checkout | `/checkout/` | `200` | 325,584 B / 73,234 B | **PASS** | 0 lỗi |
| 11 | My Account (`/tai-khoan/`) | `/tai-khoan/` | `200` | 166,796 B / 30,167 B | **PASS** | 0 lỗi |
| 12 | Designed 404 Page | `/trang-khong-ton-tai-404/` | `404` | 144,011 B / 25,656 B | **PASS** | 0 lỗi |
| 13 | RSS Feed (`/feed/` — disabled per ADR-010) | `/feed/` | `404` | 144,011 B / 25,656 B | **PASS** | 0 lỗi |
| 14 | Robots.txt | `/robots.txt` | `200` | 167 B / 149 B | **PASS** | 0 lỗi |
| 15 | Sitemap (`/sitemap.xml` — Step 8 SEO) | `/sitemap.xml` | `200` | 1,104 B / 302 B | **PASS** | 0 lỗi |

---

## 3. Kết Quả Thăm Dò 16 Đường Dẫn Mặc Định Của WordPress (Default Probe Paths)

| # | Mục tiêu thăm dò | URL | HTTP Status | Kích thước (Raw / Gzip) | Kết quả | Chi tiết lỗi (nếu có) |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Default Login (`wp-login.php`) | `/wp-login.php` | `404` | 144,011 B / 25,656 B | **PASS** | 0 lỗi |
| 2 | Admin Directory (`wp-admin/`) | `/wp-admin/` | `404` | 128,907 B / 22,812 B | **PASS** | 0 lỗi |
| 3 | XML-RPC (`xmlrpc.php`) | `/xmlrpc.php` | `404` | 236 B / 203 B | **PASS** | 0 lỗi |
| 4 | Core Readme (`readme.html`) | `/readme.html` | `404` | 236 B / 203 B | **PASS** | 0 lỗi |
| 5 | Core License (`license.txt`) | `/license.txt` | `404` | 236 B / 203 B | **PASS** | 0 lỗi |
| 6 | Sample Config (`wp-config-sample.php`) | `/wp-config-sample.php` | `404` | 236 B / 203 B | **PASS** | 0 lỗi |
| 7 | Live Config (`wp-config.php`) | `/wp-config.php` | `404` | 236 B / 203 B | **PASS** | 0 lỗi |
| 8 | Install Script (`wp-admin/install.php`) | `/wp-admin/install.php` | `404` | 236 B / 203 B | **PASS** | 0 lỗi |
| 9 | Upgrade Script (`wp-admin/upgrade.php`) | `/wp-admin/upgrade.php` | `404` | 236 B / 203 B | **PASS** | 0 lỗi |
| 10 | Author Enumeration (`?author=1`) | `/?author=1` | `404` | 144,011 B / 25,656 B | **PASS** | 0 lỗi |
| 11 | Legacy REST Prefix (`/wp-json/`) | `/wp-json/` | `404` | 236 B / 203 B | **PASS** | 0 lỗi |
| 12 | REST Users via Query (`?rest_route=/wp/v2/users`) | `/?rest_route=/wp/v2/users` | `404` | 203 B / 165 B | **PASS** | 0 lỗi |
| 13 | REST Users via `/api` (`/api/wp/v2/users`) | `/api/wp/v2/users` | `404` | 203 B / 165 B | **PASS** | 0 lỗi |
| 14 | Core XML Sitemap (`wp-sitemap.xml`) | `/wp-sitemap.xml` | `404` | 236 B / 203 B | **PASS** | 0 lỗi |
| 15 | Legacy Content Dir (`wp-content/`) | `/wp-content/` | `404` | 236 B / 203 B | **PASS** | 0 lỗi |
| 16 | Brand Favicon (`/favicon.ico`) | `/favicon.ico` | `200` | 453 B / 306 B | **PASS** | 0 lỗi |

---

## 4. Đo Lường Dung Lượng HTML Khi Inline Core Block Styles (Task B)

- **Cấu hình áp dụng:** Bật `should_load_separate_core_block_assets = true`, `should_load_block_assets_on_demand = true`, `styles_inline_size_limit = 65000`, gỡ bỏ `classic-theme-styles` và xóa toàn bộ comment `/*# sourceURL=http://localhost:8080/wp-includes/... */` trong các thẻ `<style id="wp-block-*-inline-css">`.
- **Số thẻ `<link rel="stylesheet" href=".../wp-includes/...">` trên các trang nội dung (`/`, `/coaching/`, `/booking/`, `/my-story/`, `/course/`, `/client-success-story/`):** **0 thẻ**.
- **Đo lường trên `/coaching/`:**
  - Trước Step 7 (Baseline có `sourceURL`, generator, RSD, emoji, `classic-theme-styles`): **137,813 B raw / 27,431 B gzipped** (trong đó 11 khối core block CSS được inline chiếm **25,302 B raw / ~4,860 B gzipped** so với ~112,511 B raw nếu tải file ngoài).
  - Sau Step 7 (Giữ toàn bộ core block CSS inline nhưng đã gỡ bỏ `sourceURL`, emoji, RSD, generator, comments): **222,590 B raw / 42,526 B gzipped** (giảm **2,778 B raw / 737 B gzipped** so với trước Step 7 và loại bỏ hoàn toàn các request CSS rời tới `/wp-includes/`).

---

## 5. Danh Mục Các Dấu Vết Nội Bộ Được Chấp Thuận Giữ Lại (Documented Residuals)

Theo đúng đề bài Mục F và Kế hoạch triển khai đã được phê duyệt, các định danh lớp CSS và tên gói JS module nội bộ dưới đây được giữ nguyên có chủ đích:

| Nhóm Residual | Ví dụ xuất hiện trong HTML | Số lượng trên `/coaching/` | Số lượng trên `/cart/` | Lý do kỹ thuật chấp thuận (Documented Reason) |
| :--- | :--- | :--- | :--- | :--- |
| **Core Block CSS Classes & Custom Properties** | `wp-block-group`, `wp-block-columns`, `wp-block-heading`, `--wp--preset--*` | 647 | 152 | Gutenberg, `theme.json` và `assets/css/block-styles.css` định nghĩa bộ chọn CSS trực tiếp trên `.wp-block-*` và `--wp--preset--*`. Đổi tên các class này sẽ làm hỏng toàn bộ bố cục lưới 12 cột, spacing tokens và làm gãy trình soạn thảo Block Editor. |
| **WooCommerce Semantic Classes** | `woocommerce`, `wc-block-cart`, `wc-block-checkout`, `woocommerce-notices-wrapper` | 18 | 163 | Các script React của WooCommerce Blocks (Cart/Checkout) và stylesheet `woocommerce.css` truy vấn DOM trực tiếp qua các class `.wc-block-*` và `.woocommerce-*`. Thay đổi chúng sẽ làm hỏng tính năng giỏ hàng, thanh toán và thông báo lỗi. |
| **Blocksy Structural Prefix (`ct-*`)** | `ct-container`, `ct-header`, `ct-footer`, `data-prefix` | 172 | 181 | Tiền tố trung tính 2 ký tự `ct-` là hệ thống layout khung của theme cha (không chứa tên `blocksy`), được dùng bởi `main.min.css` và `layout-shell.php`. |
| **ES Module Importmap Specifiers** | `@wordpress/interactivity`, `@woocommerce/stores/woocommerce/cart` | 0 | 0 | Trong `<script type="importmap">`, khóa `@wordpress/interactivity` là bare module specifier được biên dịch cứng bên trong các tệp JS `import ... from "@wordpress/interactivity"`. Trong khi đó, **URL tải tệp thực tế** của importmap đã được ánh xạ sang `/static/core/js/dist/script-modules/interactivity/index.min.js?v=<hash>`. |
