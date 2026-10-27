# Nhật Ký Tiến Độ & Kế Hoạch Triển Khai (`docs/jn/PROGRESS.md`)

> **Dự án:** Website Jannie Nguyễn — Luxury Beauty Academy & Mentorship (WordPress 7.1.3)
> **Cập nhật lần cuối:** 2026-10-27 (Hoàn tất **JN STEP 5** — Bộ máy Đặt lịch `jn-core` v1.2.0: CPT riêng tư `jn_booking` theo `ADR-007`, máy trạng thái 5 bước thực thi phía máy chủ, gửi biểu mẫu không cần JS qua Post/Redirect/Get `303` + có JS qua `rest_url()` kèm cơ chế làm mới token HMAC tại tương tác đầu tiên để tương thích 100% với trang HTML lưu cache toàn trang, giới hạn tần suất theo băm IP/SĐT/Email, tạo đơn HPOS `pending` + đính kèm tệp lịch `.ics` + liên kết `order-pay`, quản lý 12 buổi Mentorship, công suất tháng & ghi chú Waitlist, giao diện quản trị tiếng Việt `"Lịch hẹn"` + `"Lịch theo ngày"` + `"Cài đặt"`, 5 lớp email WooCommerce tùy chỉnh, trang `/tai-khoan/lich-hen/`, vai trò `"Trợ lý lịch hẹn"` và trình xuất/xóa dữ liệu cá nhân WordPress; đạt **45/45** bài kiểm thử tự động backend & E2E trình duyệt xanh 100%, `view.js` chỉ `3.78 KB` nén gzip; sẵn sàng cho Step 6)

---

## 1. Bảng Theo Dõi Trạng Thái Các Giai Đoạn (Steps 0–9)

| Bước | Tên giai đoạn | Trạng thái | Tag Git | Kết quả / Ghi chú chính |
| :---: | :--- | :---: | :---: | :--- |
| **Step 0** | **Kiểm toán Wireframe, Đặc tả Kỹ thuật, Quy tắc Dự án & Bảng mẫu Font** | **HOÀN TẤT (Đã duyệt)** | `jn-step-0` | Khởi tạo `.agent/rules/jn-project.md`, `docs/jn/wireframe-spec.md` (khớp 207/207 nút văn bản DOM), `docs/jn/font-specimen.html` + `.png`, `docs/jn/stack.md`, `docs/jn/decisions.md`, `docs/jn/PROGRESS.md`; chốt `HEADING_FONT = Playfair Display`; không chạm vào `./wordpress`. |
| **Step 1** | **Khởi tạo Docker, Cài đặt WordPress 7.1.3, Cấu trúc `/app` & Bảo mật Cài đặt** | **HOÀN TẤT** | `jn-step-1` | Docker (`wordpress` PHP 8.3.36 Apache, `mariadb:11.4`, `wpcli`, `mailpit:v1.22`); đổi đường dẫn sang `/app`, `/app/modules`, `/media`, `/api`; tiền tố bảng `jn_8f4c_`; ngôn ngữ `vi`; WPS Hide Login `/phong-lam-viec-7429`; giữ `twentytwentyfive` (`1.5`) ở trạng thái `inactive` làm recovery theme; Site Health = 0 lỗi nghiêm trọng; snapshot `backups/jn-step-1.sql.gz`. |
| **Step 2** | **Blocksy Child Theme `"jannie"`, Hệ thống Thiết kế, Tự host Font WOFF2, `jn-core` (`jn/icon`), Block Styles & Header/Footer Toàn cục** | **HOÀN TẤT** | `jn-step-2` | Blocksy `v2.1.60` (`Tested up to: 7.1`) + gói ngôn ngữ `vi`; child theme `"jannie"` (thay thế stub `blocksy-jn`); 8 màu thương hiệu trong Blocksy Global Palette + 9 token mở rộng = **17 mã màu xuất hiện đúng 1 lần** trong Editor; tự host 3 file WOFF2 variable (`Playfair Display` Regular/Italic + `Geist`, `latin`+`vietnamese`, 0 fallback glyphs, 0 CDN requests); plugin `jn-core` với block `jn/icon` (`apiVersion: 3`, 19 icon + `sparkline`); 18 Block-style variations; nhịp phân đoạn `96px` desktop / `64px` mobile (`< 768px`); Header `100px` sticky + Off-canvas `< 1280px` & Footer `6/3/3` khớp wireframe; `axe-core` = 0 lỗi tương phản; xuất `docs/jn/theme-mods.json` & `backups/jn-step-2.sql.gz`. |
| **Step 3** | **Thư viện Gutenberg Patterns, Trang Coaching, CPT `jn_testimonial`, Block `jn/booking-form` (UI) & Khung Các Trang** | **HOÀN TẤT** | `jn-step-3` | 10 pattern trong `patterns/` (danh mục `"Jannie Nguyen"`, `0` mã màu hex cứng, `0` `core/html`) + 2 Synced Patterns (`wp_block` ID `94`, `95`); CPT `jn_testimonial` (`publicly_queryable: false`) gắn Block Bindings `core/post-meta` + block `jn/rating` (SVG stars, `0` JS) + 3 lời chứng thực gắn ghi chú `"cần xác minh & xin phép khách"`; 5 ảnh `.webp` nhập vào Media Library với tiền tố `"PLACEHOLDER – "`; block `jn/booking-form` chọn trước gói từ `?goi=` cả phía server & client (`view.js` `3.4 KB` raw / `< 1.5 KB` gzip); hoàn thiện `/coaching/` (`Δ = 0px` ở `375/768/1024/1440`) và dàn khung `Home`, `My Story`, `Course`, `Client Success Story`, `Booking`, `Chính sách bảo mật`, `Điều khoản dịch vụ`; Lighthouse Mobile trên `/coaching/`: **Accessibility = 100**, **Best Practices = 100**, **Performance = 96**; snapshot `backups/jn-step-3.sql.gz`. |
| **Step 4** | **WooCommerce: Cửa hàng, 3 Gói Coaching Ẩn, Trang `My Favorite Product`, Khối Cart/Checkout/My Account & Phân lập Tài nguyên** | **HOÀN TẤT** | `jn-step-4` | WooCommerce `v11.2.1` + gói ngôn ngữ `vi`; tiền tệ `VND` (`450.000 ₫`), HPOS bật (`OrderTableDataStore`), tắt Order Attribution (`ADR-005`); 3 sản phẩm dịch vụ ảo đặt tên nguyên văn theo biểu mẫu (`ID 163, 164, 165`) giữ ở trạng thái `Draft` do giá `UNKNOWN` (`UNK-01`), ẩn khỏi danh mục & chặn thêm vào giỏ nhưng thanh toán được qua liên kết `order-pay` (`Order #175`); trang `/my-favorite-product/` (`ID=12`, `woocommerce_shop_page_id` theo `ADR-006`) dựng bằng pattern đầu mục + `woocommerce/product-collection`; Cart/Checkout/My Account dùng WooCommerce Blocks định kiểu bằng token; nút `"Đăng ký"` trên Header trỏ tới `/tai-khoan/#register`; Checkout giỏ hàng ảo chỉ hỏi Họ tên + SĐT + Email + ô tích Điều khoản; kiểm thử E2E thành công 1 đơn khách vãng lai (`#176`), 1 đơn khách đăng nhập (`#177` → đổi trạng thái `Đã hoàn thành` trong HPOS → khách thấy trong `/tai-khoan/orders/`), 8 email tiếng Việt gắn thương hiệu trong Mailpit; đã xóa sản phẩm `"DEMO – xóa trước khi chạy thật"` sau khi kiểm thử; `0` tài nguyên WooCommerce & `0` `get_refreshed_fragments` trên `/coaching/`, `/my-story/`, `/booking/`; Lighthouse Mobile: `/my-favorite-product/` `96/100/100`, `/checkout/` `100/100/100`, `/coaching/` `98/100/100`; snapshot `backups/jn-step-4.sql.gz`. |
| **Step 5** | **Bộ Máy Đặt Lịch (`jn-core/booking`), Quản Lý Khách Hàng (`customers`), Email `.ics` & Endpoint `Lịch hẹn của tôi`** | **HOÀN TẤT** | `jn-step-5` | CPT riêng tư `jn_booking` (`ADR-007`), máy trạng thái 5 bước (`Chờ xác nhận` → `Đã xác nhận lịch` → `Hoàn thành` / `Vắng mặt`, mọi trạng thái chưa kết thúc → `Đã hủy`), gửi biểu mẫu không cần JS (Post/Redirect/Get `303`) + có JS (`rest_url()`) kèm làm mới token HMAC khi tương tác đầu tiên cho trang lưu cache, chống spam (honeypot, `>= 2s`, băm IP/SĐT/Email), khớp/tạo khách hàng & tự gắn khi đăng ký, trợ lý xác nhận lịch → cảnh báo trùng giờ → tạo đơn HPOS `pending` → gửi email đính kèm `.ics` + liên kết `order-pay`, đồng bộ trạng thái thanh toán & gắn cờ hoàn tiền thủ công khi hủy đơn đã trả tiền, hỗ trợ 1 đơn ghi danh + tối đa 12 buổi Mentorship, công suất tháng & ghi chú Waitlist, giao diện quản trị tiếng Việt `"Lịch hẹn"` + `"Lịch theo ngày"` + `"Cài đặt"`, 5 lớp `WC_Email` tùy chỉnh, trang `/tai-khoan/lich-hen/`, vai trò `"Trợ lý lịch hẹn"` (`jn_booking_assistant`), trình xuất/xóa dữ liệu cá nhân WP; đạt **45/45** bài test backend & E2E trình duyệt xanh 100%, `view.js` = `3,785 bytes` (`3.70 KB`) gzip (`<= 5 KB`), trang không có form tải `0` tài nguyên mới; snapshot `backups/jn-step-5.sql.gz`. |
| **Step 6** | **Khung Các Trang Còn Lại Trong Sơ Đồ Điều Hướng (7 Trang Ngoài Coaching)** | **Sẵn sàng thực thi** | `jn-step-6` | Dựng cấu trúc chuẩn thiết kế cho `home`, `my-story`, `course`, `client-success-story`, `my-favorite-product`, `booking`, `my-account`. |
| **Step 7** | **Ẩn Dấu Vết WordPress ("Hide WordPress") & Gia Cố Bảo Mật Toàn Diện (`jn-hardening.php`)** | Chưa bắt đầu | `jn-step-7` | Áp dụng quy tắc rewrite, ẩn `/wp-admin` & `/wp-login.php` (`404`), `/phong-lam-viec-7429`, 2FA, Security Headers. |
| **Step 8** | **Tối Ưu Hiệu Năng (`jn-performance.php`), Khả Năng Tiếp Cận (WCAG AA) & SEO Kỹ Thuật** | Chưa bắt đầu | `jn-step-8` | Tối ưu LCP/INP/CLS, preload font/LCP, cấu hình Cache & SEO plugin, kiểm thử axe-core = 0 lỗi. |
| **Step 9** | **Kiểm Thử Toàn Diện (PHPUnit, Playwright E2E, Lighthouse CI) & Đóng Gói Bàn Giao Production** | Chưa bắt đầu | `jn-step-9` | Xác nhận 8/8 cổng chất lượng xanh, rà soát danh sách chặn phát hành (Launch Blockers) và bàn giao. |

---

## 2. Kết Quả Nghiệm Thu JN STEP 0

1. **Quy tắc dự án (`.agent/rules/jn-project.md`):**
   - Đã tạo tại `.agent/rules/jn-project.md` áp dụng cho toàn bộ workspace (không giới hạn đường dẫn), chứa chính xác nội dung giữa `<<<RULES` và `RULES>>>` kèm bảng `PROJECT CONTEXT CARD` ở cuối file.
2. **Đặc tả kỹ thuật Wireframe (`docs/jn/wireframe-spec.md`) & Kiểm chứng 100% văn bản gốc:**
   - Đã hoàn thành đầy đủ các mục **2a → 2g** bằng tiếng Việt.
   - Script kiểm chứng tự động `scripts/verify-wireframe-text.py` đã xác nhận:
     - **Tổng số nút văn bản hiển thị trong DOM của `code.html`:** `207 / 207` nút (`160` nút văn bản nội dung + `47` nút tên icon Material Symbols, tương ứng `169` chuỗi duy nhất) đều xuất hiện **nguyên văn 100% (verbatim)** trong `docs/jn/wireframe-spec.md`.
     - **Tổng số thuộc tính `placeholder` của biểu mẫu:** `4 / 4` chuỗi khớp nguyên văn 100%.
3. **Bảng mẫu đối chiếu Font chữ (`docs/jn/font-specimen.html` & `docs/jn/font-specimen.png`):**
   - Đã dựng và chụp ảnh màn hình trình duyệt ở độ phân giải đầy đủ (`1600×3200`) cho cả 5 tiêu đề ở 3 cỡ chữ (`56px`, `40px`, `22px`) trên 4 phông chữ: **Bodoni Moda**, **Libre Bodoni**, **Playfair Display** và **Noto Serif Display**.
   - Chứng minh trực quan lỗi thiếu ký tự tiếng Việt `U+1EA0–U+1EF1` của `Bodoni Moda` và sự hiển thị hoàn hảo của 3 phông chữ ứng viên thay thế.
4. **Kiểm kê Toolchain (`docs/jn/stack.md`) & Nhật ký ADR (`docs/jn/decisions.md`):**
   - Ghi nhận máy host có sẵn Docker (`29.7.2`), Docker Compose (`v5.4.0`), Node.js (`v24.19.0`), npm (`11.17.0`), Google Chrome (`156.0.8078.48`), Git (`2.54.0`), Python (`3.9.6`); chưa có PHP, MySQL, WP-CLI, Composer trên host → sử dụng Docker Compose ở Step 1.
   - Đã khởi tạo 4 quyết định kiến trúc nền tảng: `ADR-001` (Stack), `ADR-002` (Booking model), `ADR-003` (Heading font), `ADR-004` (Hide-WordPress scope).
5. **Tính toàn vẹn của `./wordpress`:**
   - Không có bất kỳ file nào bên trong `./wordpress` bị thay đổi hay thêm mới ở Step 0 (`git status` xác nhận sạch hoàn toàn).

---

## 2b. Kết Quả Nghiệm Thu JN STEP 1

1. **Hạ tầng Docker Compose (`docker-compose.yml`, `docker/`, `.env`, `.env.example`):**
   - `wordpress`: `jn-wordpress:php8.3-apache` (`PHP 8.3.36` + Apache `mod_rewrite`, `headers`, `expires`), bind-mount trực tiếp `./wordpress:/var/www/html` với `ENTRYPOINT ["docker-php-entrypoint"]` để không bao giờ ghi đè mã nguồn hay tự sinh `wp-config.php`; `memory_limit = 256M`, `upload_max_filesize = 64M`, `post_max_size = 64M`; log gắn ra ngoài web root tại `./logs/wordpress:/var/log/wordpress/debug.log`.
   - `db`: `mariadb:11.4` (LTS) với named volume `jn_db_data` và healthcheck chuẩn.
   - `wpcli`: `wordpress:cli-php8.3` chạy theo yêu cầu với cùng mounts và `.env`. Alias ngắn gọn: `alias jnwp='docker compose run --rm wpcli wp'` (hoặc `./scripts/wp.sh`).
   - `mailpit`: `axllent/mailpit:v1.22` bắt toàn bộ thư SMTP local tại cổng `1025` và giao diện web tại `http://localhost:8025`.
2. **Các biện pháp bảo mật tại thời điểm cài đặt (Install-Time Hide-WordPress Measures):**
   - Đổi tên thư mục `./wordpress/wp-content` $\rightarrow$ `./wordpress/app`, `./wordpress/app/plugins` $\rightarrow$ `./wordpress/app/modules`, tạo `./wordpress/app/mu-plugins` và `./wordpress/media`.
   - Cấu hình `wordpress/wp-config.php` không chứa bất kỳ khóa bí mật nào (đọc toàn bộ DB credentials và 8 salts từ biến môi trường), tiền tố bảng `$table_prefix = jn_8f4c_` (`!= wp_`), `WP_ENVIRONMENT_TYPE = local`, `WP_DEBUG = true`, `WP_DEBUG_DISPLAY = false`, `WP_DEBUG_LOG = /var/log/wordpress/debug.log`, `DISALLOW_FILE_EDIT = true`, `WP_POST_REVISIONS = 10`.
   - Khởi tạo MU-Plugin `wordpress/app/mu-plugins/jn-hardening.php` (v1.0.0): đổi tiền tố REST API sang `/api` qua `rest_url_prefix`, flush rewrite rules 1 lần, định tuyến email môi trường `local` vào Mailpit, trả về HTTP `404` trực tiếp khi khách chưa đăng nhập truy cập `/wp-admin/` hoặc `/wp-login.php`.
3. **Cài đặt WordPress 7.1.3 Tiếng Việt & Chính sách Giao diện Dự phòng (`Recovery Theme`):**
   - Cài đặt với tiêu đề `"Jannie Nguyen"`, tài khoản quản trị không đoán được (`jn_director_7391`), ngôn ngữ Tiếng Việt (`vi` — `<html lang="vi">`), múi giờ `Asia/Ho_Chi_Minh`, định dạng ngày `d/m/Y`, giờ `H:i`, tuần bắt đầu Thứ Hai (`1`), đường dẫn tĩnh `/%postname%/`, `blog_public = 0`, đóng bình luận & pingback mặc định.
   - Đã xóa bài viết mẫu, trang mẫu, bình luận mẫu và 2 plugin mặc định (`hello`, `akismet`).
   - **Ghi chú giao diện dự phòng (Recovery Theme):** Đã xóa `twentytwentythree` và `twentytwentyfour`; **giữ lại `twentytwentyfive` (`v1.5`) ở trạng thái `inactive` làm giao diện dự phòng khôi phục (recovery theme)** theo đúng yêu cầu; cài đặt parent theme `blocksy` (`v2.1.60`) và kích hoạt child theme `blocksy-jn` (`v1.0.0`).
4. **WPS Hide Login & Kiểm định Kỹ thuật:**
   - Cài đặt và kích hoạt `wps-hide-login` (`v1.9.19`), đặt `LOGIN_SLUG = phong-lam-viec-7429`.
   - Xác nhận đầy đủ PHP extensions (`gd`, `imagick`, `intl`, `zip`, `exif`, `Zend OPcache`), trình xử lý ảnh `WP_Image_Editor_GD` hỗ trợ cả `image/webp` và `image/avif`, `wp core verify-checksums` đạt 100%, và **Tình trạng website (Site Health) = Tốt (`0` vấn đề nghiêm trọng)** (ảnh chụp tại `docs/jn/step-1-site-health.png` và `docs/jn/step-1-mailpit.png`).
   - Đã xuất bản sao lưu cơ sở dữ liệu tại `backups/jn-step-1.sql.gz`.

---

## 2c. Kết Quả Nghiệm Thu JN STEP 2

1. **Blocksy `v2.1.60` & Gói Ngôn Ngữ Tiếng Việt (`vi`):**
   - Kiểm tra metadata trong `wordpress/app/themes/blocksy/style.css`: `Version: 2.1.60`, `Requires at least: 5.2`, **`Tested up to: 7.1`**, `Requires PHP: 7.0` — xác nhận tương thích chính thức với **WordPress 7.1 (`7.1.3`)**.
   - Đã cài đặt đầy đủ gói ngôn ngữ tiếng Việt của Blocksy (`vi`) tại `wordpress/app/languages/themes/blocksy-vi.po`, `blocksy-vi.mo`, `blocksy-vi.l10n.php` + 5 file JSON dịch thuật.
   - Đã khảo sát cơ chế tích hợp Editor của Blocksy (`inc/integrations/theme-json.php`, `inc/dynamic-styles/global.php`, `inc/options/general/colors.php`): Blocksy ghi `colorPalette` thành `--theme-palette-color-1..8` và đồng bộ sang `settings.color.palette.theme` trong `wp_theme_json_data_theme` ở priority `10`.
2. **Giao Diện Con `"jannie"` & Hệ Thống Design Tokens (`17` mã màu xuất hiện đúng 1 lần trong Editor):**
   - Khởi tạo child theme chuẩn tại `wordpress/app/themes/jannie/` (`style.css`, `functions.php` chỉ chứa enqueue + includes, `theme.json`, `inc/palette.php`, `inc/setup.php`, `inc/block-styles.php`, `inc/layout-shell.php`, `assets/css/tokens.css`, `assets/css/base.css`, `assets/css/block-styles.css`, `patterns/`). Đã kích hoạt `"jannie"` và gỡ bỏ thư mục stub cũ `blocksy-jn`.
   - **8 màu thương hiệu cốt lõi** được nạp vào Blocksy Global Color Palette (`colorPalette` `--theme-palette-color-1..8`), kết hợp **9 token mở rộng** (bao gồm các biến thể chữ đạt chuẩn tương phản WCAG AA §4: `#7E6754`, `#A0846E`, `#716A61`, `#D1C7BB`...) được chuẩn hóa qua filter `wp_theme_json_data_theme` (priority `99`) với `defaultPalette = false` $\rightarrow$ **đúng 17 token màu xuất hiện duy nhất 1 lần** trong bảng chọn màu của Block Editor (`uniqueSlugCount = 17`, `uniqueHexCount = 17`).
   - Toàn bộ thang chữ linh hoạt (`clamp()` từ Mobile $\leftrightarrow$ Desktop theo `DESIGN.md`), thang khoảng cách (`--jn-space-*`), bo góc (`4px`, `8px`, `12px`, `9999px`), đổ bóng Ambient Luxury và chuyển động `200ms` (tôn trọng `prefers-reduced-motion: reduce`) được định nghĩa bằng biến CSS `--jn-*`.
3. **Tự Host Font WOFF2 Tiếng Việt (`Playfair Display` + `Geist`) & Tắt 100% Google Fonts:**
   - Tải từ nguồn OFL và cắt tập con (`pyftsubset` `latin` + `vietnamese` `U+0102-0103,U+0110-0111,U+0128-0129,U+0168-0169,U+01A0-01A1,U+01AF-01B0,U+1EA0-1EF9,U+20AB`) thành 3 file WOFF2 variable tại `wordpress/app/themes/jannie/assets/fonts/`:
     - `playfair-display-vi-normal.woff2` (`45.1 KB`, weight `400 700`)
     - `playfair-display-vi-italic.woff2` (`44.1 KB`, weight `400 700`, italic)
     - `geist-vi-normal.woff2` (`35.3 KB`, weight `300 700`)
   - Khai báo `@font-face` với `font-display: swap` kèm fallback khớp metric (`Playfair Display Fallback`, `Geist Fallback` với `size-adjust`, `ascent-override`, `descent-override`).
   - Chỉ preload đúng **2 file font** dùng trong vùng nhìn đầu tiên (`playfair-display-vi-normal.woff2` và `geist-vi-normal.woff2`).
   - Vô hiệu hóa mọi kết nối Google Fonts của Blocksy (`blocksy:typography:google:use-remote = false`). Kiểm chứng CDP trên trang mẫu tiếng Việt xác nhận **`0` glyph rơi về font hệ thống (`fallbackGlyphsTotal = 0`)** và **`0` request ra mạng ngoài (`externalRequestsCount = 0`)**.
4. **Plugin `jn-core` & Block `jn/icon` (`apiVersion: 3`):**
   - Khởi tạo cấu trúc thư mục tính năng tại `wordpress/app/modules/jn-core/` (`booking/`, `customers/`, `testimonials/`, `blocks/`).
   - Xây dựng block `jn/icon` (`block.json` `apiVersion: 3`) render SVG nội tuyến từ phía máy chủ (`render.php` + `JN_Icon_Sprite`) gồm đủ **19 icon Material Symbols Outlined** (`stroke-width="1.5"`) cộng thêm hình trang trí **`sparkline`** cho huy hiệu thống kê, hỗ trợ thuộc tính `name`, `size`, `label` (mặc định `aria-hidden="true"` trừ khi `label` có giá trị) và **0 byte JS ngoài giao diện khách**.
5. **18 Biến Thể Kiểu Khối (`Block-Style Variations`) & Bố Cục Khung Trang:**
   - Đã đăng ký và định kiểu bằng token CSS cho 18 biến thể:
     - `core/button`: `primary-pill` (nền `#1F1E1D`, hover `#735944` chữ trắng), `soft-pill` (nền `#F4F0EA`), `accent-pill` (nền `#B39881` chữ `#1F1E1D`), `outline`, `text-link` (gạch chân `#7E6754` cách `4px`).
     - `core/group`: `card`, `card-dark` (thẻ gói nổi bật, nhấc cao `-16px` trên màn hình `>= 1024px`), `panel-beige`, `panel-cream`, `metrics-bar`, `glass-caption` (nhãn kính mờ `backdrop-filter: blur(12px)`).
     - `core/paragraph`: `eyebrow`, `eyebrow-dot`, `eyebrow-line`.
     - `core/list`: `check`, `verified`.
     - `core/image`: `portrait-4-5`, `portrait-3-4`.
   - Khung chứa rộng tối đa `1360px` với lề hai bên đáp ứng **`56px`** (`>= 1200px`), **`32px`** (`768px–1199px`), **`20px`** (`< 768px`).
   - **Nhịp khoảng cách phân đoạn dọc (`Section Rhythm`):** **`96px`** trên Desktop (`>= 768px`) và **`64px`** trên Mobile (`< 768px`, chọn `64px` = `4rem` thuộc thang giãn cách `8px` để giữ độ thoáng sang trọng mà không phải cuộn quá dài trên màn hình nhỏ).
   - Thiết lập mặc định cho trang (`single_page_*` trong Blocksy): tắt tiêu đề trang tự động, không có thanh bên (`no-sidebar`), cấu trúc rộng toàn phần, không chèn khoảng đệm nội dung mặc định (`content_area_spacing = none`).
6. **Header Toàn Cục, Menu Off-Canvas (`< 1280px`) & Footer Toàn Cục (`6 / 3 / 3`):**
   - **Header Desktop (`100px`, sticky):** Logo monogram `JN` vector + `"JANNIE NGUYEN"` + `"BEAUTY BLOG"`; menu chính gồm 7 mục đúng thứ tự wireframe (`Home`, `My Story`, `Coaching`, `Course`, `Client Success Story`, `My Favorite Product`, `Booking`) với mục đang chọn `Coaching` có gạch chân `2px` màu `#7E6754` + chữ `semibold (600)`; cụm tài khoản gồm liên kết `"Đăng nhập"`, nút pill `"Đăng ký"` và nút icon tài khoản (`aria-label="Tài khoản của tôi"`) trỏ về `/tai-khoan/`. Nền kem trong suốt `90%` (`rgba(251, 249, 246, 0.90)`) kèm `backdrop-filter: blur(12px)` và bóng đổ nhẹ. `html { scroll-padding-top: 100px; }`. Site Icon cài đặt từ monogram `JN`.
   - **Menu Off-Canvas (`< 1280px`):** Nút mở menu (`aria-expanded`, `aria-label="Mở menu điều hướng"`), ngăn kéo chứa 7 mục điều hướng + nút CTA `"Đặt lịch"` trỏ tới `#booking-form`, đóng bằng phím `Escape` và trả focus về nút kích hoạt.
   - **Footer (`6 / 3 / 3`):** Cột 1 gồm monogram `JN` + `"JANNIE NGUYEN"` + câu sứ mệnh in nghiêng; Cột 2 `"Khám Phá"`; Cột 3 `"Liên Hệ & Tư Vấn"` kèm bộ icon SVG (`location_on`, `mail`, `schedule`) và thông tin từ `PROJECT CONTEXT CARD`. Thanh đáy: `"© 2026 Jannie Nguyen Academy. Tất cả quyền được bảo lưu."` (năm tự động qua `{current_year}`) + liên kết `"Chính sách bảo mật"` (`/chinh-sach-bao-mat/`) và `"Điều khoản dịch vụ"` (`/dieu-khoan-dich-vu/`) trỏ vào trang thật đã gắn nhãn giữ chỗ pháp lý; xóa sạch dòng ghi công giao diện mặc định.
   - Xuất cấu hình Theme Mods ra `docs/jn/theme-mods.json` và bản sao lưu DB `backups/jn-step-2.sql.gz`.
7. **Kiểm Thử Tự Động (`scripts/verify-step-2.mjs` & `docs/jn/step-2-verification-report.json`):**
   - Ảnh chụp Header & Footer ở `375 / 768 / 1024 / 1440` đặt cạnh ảnh cắt từ `screen.png`: `docs/jn/step-2-header-comparison.png`, `docs/jn/step-2-footer-comparison.png`.
   - Ảnh chụp trang mẫu tiếng Việt & đối chiếu Editor vs. Front-end: `docs/jn/step-2-vietnamese-specimen.png`, `docs/jn/step-2-editor-palette.png`, `docs/jn/step-2-editor-vs-frontend.png`.
   - Kết quả quét **`axe-core` WCAG 2.1 AA**: **`0` vi phạm tổng thể (`violationsCount = 0`)**, **`0` vi phạm độ tương phản màu (`colorContrastViolationsCount = 0`)**, `24` quy tắc đạt.

---

## 2d. Kết Quả Nghiệm Thu JN STEP 3

1. **Thư viện 10 Block Patterns trong Child Theme `jannie` (`wordpress/app/themes/jannie/patterns/`) & 2 Synced Patterns (`wp_block`):**
   - Đã đăng ký danh mục `"Jannie Nguyen"` (`jannie-nguyen`) trong `wordpress/app/themes/jannie/inc/patterns.php` và xây dựng đầy đủ 10 file pattern chuẩn `core/*` + `jn/icon` + `jn/rating` + `jn/booking-form` (`0` mã màu hex cứng, `0` khối `core/html`):
     - `jannie/hero-editorial` (`patterns/hero-editorial.php`): Bố cục 7:5, watermark `"1:1"` gắn `aria-hidden="true"`, thanh chỉ số `is-style-metrics-bar` khóa `templateLock="contentOnly"` cho từng ô con, ảnh LCP `core/image` gắn `loading="eager" fetchpriority="high"` và chú thích nổi `is-style-glass-caption`.
     - `jannie/section-header`, `jannie/section-header-split`, `jannie/section-header-centered`: Các mẫu tiêu đề phân đoạn (chia 2 cột và căn giữa).
     - `jannie/pillars-3` (`patterns/pillars-3.php`): Lưới 3 cột giá trị cốt lõi (`jn/icon` `psychology`, `trending_up`, `workspace_premium` + thẻ `is-style-card`).
     - `jannie/pricing-tiers-3` (`patterns/pricing-tiers-3.php`): 3 thẻ gói Coaching (`#coaching-tiers`), thẻ giữa `is-style-card-dark` nổi bật có huy hiệu `"KHUYÊN CHỌN // CHỦ CƠ SỞ & HỌC VIÊN"`, khóa `contentOnly` cho nhóm chip/thời lượng bên trong nhưng mở khóa toàn bộ phân đoạn ngoài để kéo thả tự do trong List View; 3 nút CTA trỏ về `#booking-form` kèm `?goi=30-phut`, `?goi=60-phut`, `?goi=mentorship-3-thang`.
     - `jannie/expert-intro` (`patterns/expert-intro.php`): Chân dung `3:4` + hộp trích dẫn nổi + câu chuyện chuyên gia + lưới 2 cột chứng chỉ quốc tế (`<h3>`).
     - `jannie/testimonials-3` (`patterns/testimonials-3.php`): Tiêu đề + huy hiệu `sparkline` (`+180%`) + `core/query` (`postType: "jn_testimonial"`) kết nối `core/post-meta` Block Bindings và block `jn/rating`.
     - `jannie/booking-section` (`patterns/booking-section.php`): Khung `#booking-form` 12 cột (`5:7`), quy trình 3 bước sử dụng danh sách có thứ tự `<ol class="wp-block-list jn-booking-steps">` (khắc phục lỗi nhảy bậc `h2 -> h4` của wireframe), hộp Hotline và block `jn/booking-form`.
     - `jannie/page-intro` (`patterns/page-intro.php`): Đầu trang chuẩn cho các trang con.
   - Đã khởi tạo 2 **Synced Patterns (`wp_block`)** tái sử dụng xuyên suốt các trang:
     - `Testimonials Section (Synced)` (`ID=94`, nhúng trên `/coaching/`, `/` và `/client-success-story/`).
     - `Booking Section (Synced)` (`ID=95`, nhúng trên `/coaching/`, `/`, `/my-story/`, `/course/`, `/client-success-story/` và `/booking/`).
2. **Quản trị Lời chứng thực (`CPT jn_testimonial`), Block Bindings & Block `jn/rating`:**
   - Khởi tạo CPT `jn_testimonial` trong `wordpress/app/modules/jn-core/testimonials/class-jn-testimonials.php` với `public => false`, `publicly_queryable => false`, `query_var => false`, `rewrite => false` và guard trả về `404` nếu truy cập trực tiếp.
   - Đăng ký các trường meta (`jn_quote`, `jn_client_name`, `jn_client_role`, `jn_client_city`, `jn_role_display`, `jn_rating`, `jn_related_package`, `jn_admin_note`) với `show_in_rest => true` để gắn trực tiếp vào `core/paragraph` / `core/heading` qua **Block Bindings API (`core/post-meta`)**.
   - Xây dựng block `jn/rating` (`wordpress/app/modules/jn-core/blocks/rating/`) đọc `jn_rating` từ `postId` trong `core/query`, render 1–5 ngôi sao SVG từ máy chủ kèm `role="img"` và `aria-label="Đánh giá 5 trên 5 sao"` (`0` byte JS).
   - Nạp nguyên văn 3 lời chứng thực từ wireframe (`Chị Minh Thảo` `ID=91`, `Chị Ngọc Bích` `ID=92`, `Chị Phương Uyên` `ID=93`), mỗi bản ghi đều gắn ghi chú quản trị **`"cần xác minh & xin phép khách"`** (`UNK-03`).
3. **Nhập 5 Ảnh Mẫu Vào Media Library (`scripts/import-step-3-media.php`):**
   - Đã chuyển đổi sang `.webp` và nhập vào Media Library (lưu tại `/media/2026/10/`, không hot-link):
     - `ID=86`: `PLACEHOLDER – Chuyên gia Jannie Nguyễn – Ảnh chân dung Hero Coaching` (`800×1000`, `28.1 KB`)
     - `ID=87`: `PLACEHOLDER – Chuyên gia Jannie Nguyễn – Ảnh tư vấn thẩm mỹ viện` (`750×1000`, `40.7 KB`)
     - `ID=88`: `PLACEHOLDER – Chị Minh Thảo – Ảnh đại diện học viên` (`160×160`, `4.2 KB`)
     - `ID=89`: `PLACEHOLDER – Chị Ngọc Bích – Ảnh đại diện học viên` (`160×160`, `5.0 KB`)
     - `ID=90`: `PLACEHOLDER – Chị Phương Uyên – Ảnh đại diện học viên` (`160×160`, `4.0 KB`)
4. **Block `jn/booking-form` (UI Step 3) & Tiền chọn Gói (`?goi=`):**
   - Render từ máy chủ (`wordpress/app/modules/jn-core/blocks/booking-form/render.php`): giữ nguyên văn 100% nhãn, placeholder, 3 `<option>` gói (mặc định `"Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)"`); bổ sung dấu `*` bắt buộc, `autocomplete="name|tel|email"`, `inputmode="tel"`, ô nhập cao `48px` kèm vòng focus rõ nét, trường ẩn honeypot (`jn_website_url`), vùng thông báo `aria-live="polite"`, và ô tích đồng ý chính sách bảo mật (mặc định chưa chọn) liên kết tới `/chinh-sach-bao-mat/` (gắn cờ chờ chủ dự án duyệt pháp lý `UNK-09`).
   - Tiền chọn gói hoạt động cả khi **tắt JS** (server đọc `$_GET['goi']` với `30-phut`, `60-phut`, `mentorship-3-thang`) và khi **bật JS** (`view.js` chỉ `3.4 KB` raw / `< 1.5 KB` nén, `defer`, chỉ nạp ở trang có block `jn/booking-form`, bắt sự kiện click nút gói để chọn `<select>`, cập nhật URL `history.replaceState` và cuộn mượt không tải lại trang). Khi gửi form ở Step 3, hiển thị thông báo cục bộ *"Chức năng gửi hồ sơ trực tuyến đang hoàn thiện (Step 5)"*.
5. **Trang `/coaching/`, 7 Trang Khung & Hệ Thống Menu:**
   - Hoàn thiện trang `/coaching/` (`ID=9`) và 7 trang khung trung thực (gắn nhãn `[CẦN NỘI DUNG: …]`, không tự bịa tiểu sử/kết quả/giá tiền): `Home` (`ID=7`, Front Page gồm 6 phân đoạn đã duyệt), `My Story` (`ID=8`), `Course` (`ID=10`), `Client Success Story` (`ID=11`), `Booking` (`ID=13`), `Chính sách bảo mật` (`ID=15`, WP Privacy Page), `Điều khoản dịch vụ` (`ID=16`).
   - Menu chính (7 mục theo đúng thứ tự wireframe) và menu chân trang `"Khám Phá"` (5 mục) + Pháp lý (2 mục) đều dùng URL thật, `0` liên kết `href="#"`.
6. **Kiểm Định Đối Chiếu Thị Giác (`Visual Parity`), Block Editor & Lighthouse Mobile:**
   - Script `scripts/verify-step-3.php` xác nhận **`0` khối `core/html`** trên toàn bộ 10 trang và 2 `wp_block`; 100% khối thuộc `core/*` hoặc `jn/*`.
   - Đối chiếu 24 ảnh chụp song song (`docs/jn/parity-step-3/parity-s1..s6-{375,768,1024,1440}px.png` & `parity-report.json`): độ lệch `paddingTop`, `paddingBottom`, `headingFontSize` (`h1`/`h2`), `h3FontSize` đạt **`Δ = 0px`** trên cả 4 độ phân giải (`375 / 768 / 1024 / 1440`).
   - Kiểm tra trình sửa khối Gutenberg (`docs/jn/step-3-editor-list-view.png`, `step-3-editor-reordered.png`, `step-3-editor-inserter-patterns.png`): `0` cảnh báo khối lỗi, kéo thả phân đoạn mượt mà, danh mục `"Jannie Nguyen"` hiển thị đầy đủ 10 pattern trong Inserter.
   - Kết quả **Lighthouse Mobile** trên `http://localhost:8080/coaching/` (`docs/jn/step-3-lighthouse-mobile.json`):
     - **Accessibility: `100`**
     - **Best Practices: `100`**
     - **Performance: `96`** (`FCP: 1.7s`, `LCP: 2.6s`, `TBT: 0ms`, `CLS: 0`)
   - Đã xuất bản sao lưu cơ sở dữ liệu tại `backups/jn-step-3.sql.gz`.

---

## 2e. Kết Quả Nghiệm Thu JN STEP 4

1. **Cài Đặt WooCommerce `v11.2.1` & Gói Ngôn Ngữ Tiếng Việt (`vi`):**
   - Cài đặt phiên bản ổn định mới nhất hỗ trợ chính thức WordPress 7.1 (`WooCommerce 11.2.1`) cùng gói ngôn ngữ tiếng Việt (`wordpress/app/languages/plugins/woocommerce-vi.*`).
   - Cấu hình sạch ngay từ đầu: tắt theo dõi sử dụng (`woocommerce_allow_tracking = no`), tắt gợi ý Marketplace (`woocommerce_show_marketplace_suggestions = no`), bỏ qua toàn bộ trình hướng dẫn cài đặt plugin phụ (`woocommerce_onboarding_profile = {"skipped":true}`), và tắt chế độ Coming Soon (`woocommerce_coming_soon = no`).
2. **Thiết Lập Cửa Hàng Việt Nam, Tiền Tệ `VND`, HPOS & Email Thương Hiệu:**
   - Địa chỉ cửa hàng: `TP. Hồ Chí Minh, Việt Nam` (`VN`), chỉ bán tại Việt Nam (`woocommerce_allowed_countries = specific`, `["VN"]`).
   - Tiền tệ: `VND`, `0` chữ số thập phân, dấu phân cách hàng nghìn `"."`, dấu thập phân `","`, ký hiệu `₫` đứng sau số tiền cách 1 khoảng trắng (`woocommerce_currency_pos = right_space` → hiển thị `450.000 ₫` / `2.500.000 ₫`).
   - Thuế: tắt (`woocommerce_calc_taxes = no`).
   - **HPOS (High-Performance Order Storage):** bật hoàn toàn (`woocommerce_custom_orders_table_enabled = yes`, `OrderTableDataStore`), tắt đồng bộ ngược sang bảng `posts` (`woocommerce_custom_orders_table_data_sync_enabled = no`).
   - Cho phép khách vãng lai thanh toán (`woocommerce_enable_guest_checkout = yes`) và cho phép tạo tài khoản trên trang `/tai-khoan/` (`woocommerce_enable_myaccount_registration = yes`, tự sinh username và mật khẩu từ email).
   - Liên kết trang Chính sách bảo mật (`ID=15`, `/chinh-sach-bao-mat/`) và Điều khoản dịch vụ (`ID=16`, `/dieu-khoan-dich-vu/`).
   - **Email giao dịch WooCommerce:** gắn logo SVG thương hiệu (`jn-email-logo.svg`), bảng màu token (`#1F1E1D` / `#FAF8F5` / `#FFFFFF` / `#4A4642`), chân trang tiếng Việt và người gửi `"Jannie Nguyen Academy <contact@jannienguyen.vn>"`.
3. **Tắt Order Attribution (`ADR-005`):**
   - Đặt `woocommerce_feature_order_attribution_enabled = no` và gỡ hoàn toàn `wc-order-attribution` + `sourcebuster-js` ngoài giao diện khách để bảo vệ quyền riêng tư và ngân sách hiệu năng; ghi nhận quyết định tại `docs/jn/decisions.md` (`ADR-005`).
4. **3 Sản Phẩm Dịch Vụ Coaching Ẩn (`Draft`) & Luồng Thanh Toán `order-pay`:**
   - Khởi tạo danh mục `"Coaching & Mentorship"` (`term_id=39`) và 3 sản phẩm đơn giản ảo (`Simple`, `Virtual`) đặt tên **khớp nguyên văn 100%** với `<option>` của biểu mẫu đặt lịch:
     1. `"Gói Tư Vấn Cá Nhân 1:1 (30 Phút)"` (`ID=163`, SKU `jn-coaching-30m`, `_jn_package_key = 30-phut`, `_jn_bookable = yes`, `_jn_duration = 30`, `_jn_sessions = 1`, `_jn_formats = ["online","in-person"]`)
     2. `"Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)"` (`ID=164`, SKU `jn-coaching-60m`, `_jn_package_key = 60-phut`, `_jn_bookable = yes`, `_jn_duration = 60`, `_jn_sessions = 1`, `_jn_formats = ["online","in-person"]`)
     3. `"Gói Master Mentorship (3 Tháng)"` (`ID=165`, SKU `jn-mentorship-3m`, `_jn_package_key = mentorship-3-thang`, `_jn_bookable = yes`, `_jn_duration = –`, `_jn_sessions = 12`, `_jn_formats = ["online","in-person"]`)
   - Mô tả ngắn và mô tả chi tiết trích nguyên văn từ thẻ gói trong wireframe.
   - Do giá bán đang là `UNKNOWN` trong `PROJECT CONTEXT CARD` (`UNK-01`), cả 3 sản phẩm được giữ ở trạng thái **`Draft` (`post_status = draft`)** kèm cờ `_jn_price_on_request = yes`.
   - **Cơ chế bảo vệ danh mục & thanh toán qua `order-pay` (`JN_Coaching_Packages`):**
     - Ẩn hoàn toàn khỏi danh mục (`exclude-from-catalog`, `exclude-from-search`).
     - Chặn mọi thao tác thêm trực tiếp vào giỏ hàng (`?add-to-cart=164` và Store API đều bị từ chối với thông báo tiếng Việt hướng dẫn đăng ký tư vấn) và chuyển hướng URL sản phẩm đơn lẻ (`/?p=164`) về `/coaching/#coaching-tiers`.
     - Khi đơn hàng `pending` chứa gói Coaching được tạo từ quản trị (ví dụ `Order #175` với đơn giá thỏa thuận `2.500.000 ₫`) và khách truy cập liên kết `/checkout/order-pay/175/?pay_for_order=true&key=...`, bộ lọc `woocommerce_is_purchasable` & `woocommerce_product_is_visible` cho phép hiển thị và hoàn tất thanh toán chuyển khoản ngân hàng tới trang cảm ơn `/checkout/order-received/175/`.
5. **Danh Mục Sản Phẩm & Dọn Dẹp Sản Phẩm Mẫu (`Task 5`):**
   - Tạo danh mục `"Khóa học"` (`term_id=40`) và `"Sản phẩm yêu thích"` (`term_id=41`).
   - Sử dụng duy nhất 1 sản phẩm tạm `"DEMO – xóa trước khi chạy thật"` (`ID=174`, `450.000 ₫`) để kiểm định giao diện và chạy kiểm thử E2E / Lighthouse `/checkout/`, sau đó **đã xóa vĩnh viễn (`wp_delete_post( 174, true )`)** trước khi chụp trạng thái rỗng và xuất snapshot Step 4.
6. **Các Trang Cửa Hàng (`My Favorite Product`, `Cart`, `Checkout`, `My Account`) & Định Kiểu Token:**
   - **Trang `/my-favorite-product/` (`ID=12`):** Dựng bằng pattern đầu trang (`jannie/page-intro` + `jannie/section-header-centered`) kết hợp khối `woocommerce/product-collection` (cấu hình hiển thị sản phẩm thuộc danh mục `"Sản phẩm yêu thích"` kèm trạng thái trống tiếng Việt trang nhã). Được chỉ định làm `woocommerce_shop_page_id` theo **`ADR-006`** nhưng vẫn giữ nguyên kết xuất Gutenberg qua filter `woocommerce_has_block_template` + `has_archive = false`.
   - **Các trang `/cart/` (`ID=159`), `/checkout/` (`ID=160`), `/tai-khoan/` (`ID=14`):** Sử dụng các khối WooCommerce Blocks chuẩn, định kiểu 100% bằng biến CSS `--jn-*` trong `wordpress/app/themes/jannie/assets/css/woocommerce.css`.
   - **Nút `"Đăng ký"` trên Header:** Trỏ trực tiếp tới `/tai-khoan/#register`, tự động cuộn mượt, làm nổi bật khung đăng ký và đặt focus vào ô nhập email đăng ký.
7. **Rút Gọn Trường Thanh Toán Cho Giỏ Hàng Ảo & Cổng Thanh Toán:**
   - Khi giỏ hàng chỉ gồm sản phẩm ảo (`virtual-only`), khối Checkout chỉ hiển thị đúng **3 trường thông tin**: `Địa chỉ email`, `Họ và tên`, `Số điện thoại / Zalo` cùng ô tích bắt buộc đồng ý Điều khoản dịch vụ & Chính sách bảo mật (`woocommerce/checkout-terms-block` với `"checkbox":true`).
   - Bật **Chuyển khoản ngân hàng (`bacs`)** với thông tin tài khoản giữ chỗ được đánh dấu rõ ràng (`[PLACEHOLDER – CẦN CẬP NHẬT SỐ TÀI KHOẢN THẬT TRƯỚC KHI GO-LIVE (UNK-05)]`); bật **COD (`cod`)** chỉ dành cho đơn hàng có sản phẩm vật lý (`enable_for_virtual = no`, tự động ẩn trên mọi giỏ hàng và đơn `order-pay` thuần ảo).
8. **Tương Thích Plugin & Phân Lập Tài Nguyên (`Asset Scoping`):**
   - `jn-core` (`v1.1.0`) khai báo tương thích chính thức với `custom_order_tables` (HPOS) và `cart_checkout_blocks`.
   - `wordpress/app/mu-plugins/jn-performance.php` kiểm tra `jn_is_commerce_page()` và gỡ toàn bộ CSS/JS của WooCommerce, Blocksy WooCommerce bundle, `wc-cart-fragments`, `wc-order-attribution`, `sourcebuster-js` và `jquery` trên các trang nội dung:
     - `/coaching/`: **`0` tài nguyên WooCommerce**, **`0` request `get_refreshed_fragments`**, tổng cộng `18` request.
     - `/my-story/`: **`0` tài nguyên WooCommerce**, **`0` request `get_refreshed_fragments`**, tổng cộng `13` request.
     - `/booking/`: **`0` tài nguyên WooCommerce**, **`0` request `get_refreshed_fragments`**, tổng cộng `12` request.
9. **Kiểm Thử E2E Trình Duyệt & Điểm Chuẩn Lighthouse Mobile (`docs/jn/step-4-verification.json`):**
   - Hoàn tất kiểm thử E2E tự động (`scripts/verify-step-4.mjs`):
     - Thanh toán đơn gói Coaching chờ thanh toán qua `order-pay` (`Order #175`).
     - Đặt hàng thành công với tư cách khách vãng lai (`Order #176`).
     - Đăng ký tài khoản mới từ nút `"Đăng ký"` trên Header → đặt hàng (`Order #177`) → kiểm tra cả 3 đơn trong **WooCommerce → Đơn hàng (HPOS)** → quản trị viên đổi trạng thái `Order #177` sang `Đã hoàn thành` → khách hàng thấy trạng thái `Đã hoàn thành` trong `/tai-khoan/orders/` và `/tai-khoan/view-order/177/`.
     - Bắt và chụp ảnh 8 email tiếng Việt gắn thương hiệu trong Mailpit (`http://localhost:8025`).
   - Điểm chuẩn **Lighthouse Mobile** lưu tại `docs/jn/`:
     - `/my-favorite-product/` (`docs/jn/step-4-lighthouse-my-favorite-product-mobile.json`): **Performance = `96`**, **Accessibility = `100`**, **Best Practices = `100`** (`FCP: 1.8s`, `LCP: 2.6s`, `TBT: 0ms`, `CLS: 0.001`).
     - `/checkout/` (`docs/jn/step-4-lighthouse-checkout-mobile.json`): **Performance = `100`**, **Accessibility = `100`**, **Best Practices = `100`** (`FCP: 0.9s`, `LCP: 1.0s`, `TBT: 70ms`, `CLS: 0`).
     - `/coaching/` (`docs/jn/step-4-lighthouse-coaching-mobile.json`): **Performance = `98`**, **Accessibility = `100`**, **Best Practices = `100`** (`FCP: 1.2s`, `LCP: 2.4s`, `TBT: 0ms`, `CLS: 0`).
   - Đã xuất bản sao lưu cơ sở dữ liệu tại `backups/jn-step-4.sql.gz`.

---

## 2f. Kết Quả Nghiệm Thu JN STEP 5

1. **Mô Hình Dữ Liệu (`CPT jn_booking`) & Quyết Định Kiến Trúc (`ADR-007`):**
   - Ghi nhận tại `docs/jn/decisions.md` (`ADR-007`): sử dụng Custom Post Type riêng tư `jn_booking` (`public => false`, `publicly_queryable => false`, `exclude_from_search => true`, `show_in_rest => false`, loại trừ khỏi XML Sitemap và trả về `404` trên mọi truy vấn giao diện khách hoặc REST ẩn danh `/api/wp/v2/jn_booking`) kết hợp bảng đơn hàng hiệu năng cao HPOS của WooCommerce (`WC_Order`) cho lớp giao dịch tài chính.
   - Hỗ trợ mô hình **1 đơn ghi danh (`enrolment order`) + tối đa 12 buổi 1:1 (`child session bookings` qua `post_parent`)** cho gói `Master Mentorship (3 Tháng)` (`ID=165`).
   - Lưu đầy đủ các trường: khách hàng (`_jn_customer_id`) hoặc bản chụp khách vãng lai (`_jn_guest_name`, `_jn_guest_phone_raw`, `_jn_guest_phone_e164` chuẩn hóa `+84...`, `_jn_guest_email`), sản phẩm gói cố vấn (`_jn_package_product_id`, `_jn_package_key`, `_jn_package_label` nguyên văn), hình thức (`online` | `in-person`), khung giờ mong muốn (`_jn_preferred_window_key`, `_jn_preferred_window_label` nguyên văn), mục tiêu/thách thức (`_jn_goals_text`), bằng chứng đồng ý bảo mật (`_jn_consent_timestamp`, `_jn_consent_policy_page_id`, `_jn_consent_policy_version`, `_jn_consent_text_snapshot`), nguồn truy cập (`_jn_source_landing_url`, `_jn_source_utm`, `_jn_source_referrer`), thời điểm hẹn xác nhận (`_jn_confirmed_start_local` theo `Asia/Ho_Chi_Minh`, `_jn_confirmed_start_utc`, `_jn_duration_minutes`), liên kết phòng họp hoặc địa điểm Academy, đơn hàng liên kết (`_jn_linked_order_id`), ghi chú nội bộ trợ lý (`_jn_staff_notes`) và nhật ký chuyển trạng thái bất biến (`_jn_status_timeline`: `who`, `when`, `from -> to`, `note`).
2. **Máy Trạng Thái 5 Bước Thực Thi Phía Máy Chủ & Trạng Thái Thanh Toán Dẫn Xuất:**
   - 5 trạng thái với nhãn tiếng Việt chuẩn:
     - `jn_pending` (`Chờ xác nhận`) → `jn_confirmed` (`Đã xác nhận lịch`) → `jn_completed` (`Hoàn thành`) | `jn_no_show` (`Vắng mặt`);
     - Mọi trạng thái chưa kết thúc (`jn_pending`, `jn_confirmed`) → `jn_cancelled` (`Đã hủy`);
     - Chặn 100% thao tác chuyển ngược hoặc chuyển ra khỏi trạng thái kết thúc (`jn_completed`, `jn_no_show`, `jn_cancelled`).
   - Trạng thái thanh toán được dẫn xuất trực tiếp từ `WC_Order` liên kết (`Chưa tạo đơn`, `Chờ thanh toán`, `Đã thanh toán`, `Đã thanh toán — Cần hoàn tiền thủ công`, `Đơn đã hủy`, `Đã hoàn tiền`).
3. **6 Luồng Nghiệp Vụ Cốt Lõi (`Flows 1–6`):**
   - **Luồng 1 (Gửi yêu cầu công khai — Không JS & Có JS & Trang lưu Cache):**
     - Hoạt động khi tắt JS (POST về chính trang hiện tại → xử lý tại `template_redirect` → chuyển hướng `303 See Other` kèm mã flash ngắn hạn `?jn_bf=...#booking-form`) và khi bật JS (`fetch()` tới đường dẫn sinh từ `rest_url( 'jn/v1/bookings' )`, không bao giờ gắn cứng tiền tố REST).
     - Tương thích 100% với HTML được lưu bộ nhớ đệm toàn trang (Full-Page Cache): tại tương tác đầu tiên (`focusin` / `pointerdown` / `touchstart`), `view.js` tự động gọi `GET /api/jn/v1/booking/token` (`Cache-Control: no-store`) để làm mới token ký HMAC-SHA256 ngắn hạn mà không gặp lỗi nonce hết hạn.
     - Kiểm tra đầy đủ: các trường bắt buộc, số điện thoại Việt Nam chuẩn hóa về `+84...`, định dạng email, gói thuộc danh mục đặt lịch, ô tích đồng ý chính sách bảo mật, trường ẩn honeypot rỗng (`jn_website_url`), thời gian điền tối thiểu `>= 2 giây`, hạn sử dụng chữ ký token và giới hạn tần suất theo hàm băm IP (`<= 5 lần / 15 phút`) và hàm băm SĐT/Email (`<= 3 lần / 15 phút`).
     - Khớp khách hàng theo Email trước, sau đó theo SĐT `+84...`; mặc định giữ bản ghi khách vãng lai (`AUTO-CREATE = no`) và tự động gắn lịch hẹn + đơn hàng vào tài khoản khi khách đăng ký sau đó tại `/tai-khoan/#register` (hoặc tạo ngay tài khoản `customer` nếu bật `AUTO-CREATE = yes`).
     - Hiển thị nguyên văn câu thông báo thành công từ wireframe: *"Cảm ơn bạn! Yêu cầu đã được ghi nhận. Đội ngũ trợ lý của Jannie Nguyễn sẽ liên hệ trực tiếp qua số điện thoại của bạn trong vòng 24 giờ."*
   - **Luồng 2 (Tùy chọn gói động từ sản phẩm WooCommerce):** Nạp trực tiếp từ 3 sản phẩm dịch vụ có `_jn_bookable = yes` (`ID 163, 164, 165`), giữ nguyên văn nhãn hiển thị, mặc định chọn gói 60 phút, hỗ trợ tiền chọn qua `?goi=`.
   - **Luồng 3 (Trợ lý xác nhận lịch hẹn):** Kiểm tra và cảnh báo trùng khung giờ (`Overlap Detection`) với các lịch hẹn đã xác nhận khác theo múi giờ `Asia/Ho_Chi_Minh`; khi chuyển sang `Đã xác nhận lịch`, tự động tạo đơn hàng WooCommerce HPOS ở trạng thái `pending` (hoặc gắn đơn có sẵn / đơn ghi danh cha của Mentorship) và gửi email xác nhận cho khách hàng kèm **tệp lịch mời `.ics` chuẩn RFC 5545** và liên kết thanh toán `order-pay`.
   - **Luồng 4 (Đồng bộ thanh toán & Chính sách hủy lịch):** Khi đơn hàng được thanh toán (`woocommerce_payment_complete` / `processing` / `completed`), lịch hẹn tự động hiển thị `Đã thanh toán`, ghi vào timeline và gửi email báo cho trợ lý. Khi hủy lịch hẹn: nếu đơn chưa thanh toán thì tự động chuyển đơn sang `cancelled`; nếu đơn đã thanh toán thì **tuyệt đối không tự động hoàn tiền** mà gắn cờ `_jn_refund_flagged = 'yes'` (`Đã thanh toán — Cần hoàn tiền thủ công`) để kế toán kiểm tra.
   - **Luồng 5 (Chuỗi 12 buổi Master Mentorship):** Cho phép tạo tối đa 12 buổi hẹn con gắn vào lịch ghi danh gốc và dùng chung duy nhất 1 đơn hàng ghi danh (`enrolment order`).
   - **Luồng 6 (Công suất tháng tùy chọn & Ghi chú Waitlist):** Mặc định tắt (`0 = không giới hạn`); khi bật và số lượng lịch trong tháng đạt ngưỡng, biểu mẫu tự động hiển thị hộp thông báo Danh sách chờ (Waitlist) đã được chủ dự án phê duyệt và gắn cờ `_jn_is_waitlist = 'yes'` cho yêu cầu mới.
4. **Giao Diện Quản Trị Tiếng Việt, 5 Lớp Email WooCommerce & Trang `Lịch hẹn của tôi`:**
   - Menu quản trị **"Lịch hẹn"** kèm huy hiệu đếm số yêu cầu `Chờ xác nhận`, bảng danh sách 10 cột (`Mã`, `Khách hàng`, `SĐT / Zalo`, `Gói`, `Hình thức`, `Khung giờ mong muốn`, `Lịch xác nhận`, `Trạng thái`, `Thanh toán`, `Ngày gửi`), bộ lọc theo trạng thái/gói/hình thức/khoảng ngày, tìm kiếm theo tên/SĐT/email/mã, thao tác nhanh từng dòng & hàng loạt, cùng màn hình **"Lịch theo ngày" (Light Agenda View)** bằng HTML/CSS thuần.
   - Màn hình chi tiết lịch hẹn: Thẻ Hồ sơ khách hàng, Thẻ Xếp lịch & Chuyển trạng thái, Thẻ Yêu cầu gốc (chỉ đọc), Thẻ Đơn hàng WooCommerce liên kết, Bảng điều khiển 12 buổi Mentorship, Nhật ký trạng thái (Timeline) và Ghi chú nội bộ.
   - Màn hình **"Cài đặt"** (`Lịch hẹn → Cài đặt`): cấu hình email nhận thông báo của trợ lý, khung giờ mong muốn, hình thức & địa chỉ Học viện, công suất tháng từng gói, cờ tự động tạo tài khoản (`AUTO-CREATE`) và nội dung/phiên bản đồng ý bảo mật.
   - Đăng ký **5 lớp `WC_Email` tùy chỉnh** hiển thị và chỉnh sửa được trong **WooCommerce → Cài đặt → Email**:
     1. `WC_Email_JN_Booking_New_Request` (Khách hàng — Đã tiếp nhận yêu cầu)
     2. `WC_Email_JN_Booking_Staff_Alert` (Trợ lý — Thông báo yêu cầu mới)
     3. `WC_Email_JN_Booking_Confirmed` (Khách hàng — Đã xác nhận lịch + đính kèm `.ics` + liên kết `order-pay`)
     4. `WC_Email_JN_Booking_Payment_Received` (Trợ lý — Đã nhận thanh toán)
     5. `WC_Email_JN_Booking_Cancelled` (Khách hàng & Trợ lý — Thông báo hủy lịch hẹn)
   - Endpoint **My Account `"Lịch hẹn của tôi"` (`/tai-khoan/lich-hen/`):** Hiển thị danh sách lịch hẹn của học viên kèm trạng thái, thời gian, hình thức, liên kết vào phòng họp, nút tải tệp `.ics` và nút **`Thanh Toán Ngay`** (`order-pay`) khi đơn đang chờ thanh toán.
5. **Bảo Mật, Quyền Riêng Tư & Kết Quả Kiểm Thử (`45/45` Backend Tests + Browser E2E):**
   - Khởi tạo vai trò **"Trợ lý lịch hẹn" (`jn_booking_assistant`)** với bộ quyền hạn riêng (`manage_jn_bookings`, `edit_jn_bookings`...), chặn leo thang đặc quyền qua `editable_roles` và `map_meta_cap`.
   - Tích hợp đầy đủ vào công cụ **Xuất dữ liệu cá nhân (`Personal Data Exporter`)** và **Xóa dữ liệu cá nhân (`Personal Data Eraser`)** của WordPress (ẩn danh hóa PII trên các lịch hẹn đã hoàn tất và giữ nguyên mã đơn hàng kế toán).
   - **Ngân sách tài nguyên:** Trang không chứa biểu mẫu (`/chinh-sach-bao-mat/`) tải **`0` tài nguyên mới**; tệp `wordpress/app/modules/jn-core/blocks/booking-form/view.js` chỉ nặng **`3,785 bytes` (`3.70 KB`) sau khi nén gzip** (đạt chuẩn `<= 5 KB`).
   - **Kiểm thử tự động Backend (`scripts/test-step-5-booking.php`):** Đạt tuyệt đối **`45 / 45 TESTS PASSED` (`0` failed)**.
   - **Kiểm thử E2E Trình duyệt (`scripts/verify-step-5.mjs` & `docs/jn/step-5-verification.json`):** Xác nhận thành công toàn bộ 7 kịch bản (chọn gói & gửi bằng JS, gửi khi tắt JS qua PRG `303`, gửi trên trang HTML lưu cache với token làm mới tự động, trợ lý xác nhận & tạo đơn HPOS `#255`, kiểm tra email xác nhận kèm `.ics` trong Mailpit, thanh toán chuyển khoản trên `order-pay` & đồng bộ trạng thái `Đã thanh toán`, và kiểm tra `/tai-khoan/lich-hen/`). Đã dọn sạch dữ liệu kiểm thử tạm và xuất bản sao lưu cơ sở dữ liệu tại `backups/jn-step-5.sql.gz`.

---

## 3. Sổ Đăng Ký Các Hạng Mục Chưa Xác Định (`UNKNOWN Register`)

Mọi thông tin thuộc quyền sở hữu của chủ dự án đang ở trạng thái `UNKNOWN` trong `PROJECT CONTEXT CARD` được theo dõi tại bảng dưới đây. Theo quy tắc dự án (§1), hệ thống **tuyệt đối không tự bịa số liệu hay nội dung**, mà sử dụng nhãn giữ chỗ (placeholder) rõ ràng kèm ghi chú tại đây cho đến khi chủ dự án cung cấp:

| Mã ID | Hạng mục thông tin (`PROJECT CONTEXT CARD`) | Hiện trạng trong Wireframe / Dự án | Bước bị ảnh hưởng (Blocking Step) | Chặn phát hành chính thức? (`Blocks Launch?`) | Giải pháp tạm thời trong quá trình phát triển (Steps 1–8) |
| :---: | :--- | :--- | :---: | :---: | :--- |
| **`UNK-01`** | **Giá bán chính thức của 3 gói Coaching (`PACKAGE_PRICES`)** | Đã tạo 3 sản phẩm dịch vụ ảo ở Step 4 (`ID 163, 164, 165`) khớp nguyên văn tên gói trong biểu mẫu, giữ ở trạng thái **`Draft`** và ẩn khỏi danh mục do chưa có giá chính thức. | **Step 4** (Đã khởi tạo sản phẩm `Draft`) & **Step 9** (Xuất bản giá thật nếu thu tiền trực tiếp) | **CÓ — CHẶN PHÁT HÀNH (`LAUNCH BLOCKER`) đối với tính năng niêm yết giá công khai; luồng tạo đơn `pending` từ quản trị & thanh toán qua `order-pay` đã hoạt động 100%** | Giữ cả 3 sản phẩm gói Coaching ở trạng thái `Draft` (`_jn_price_on_request = yes`), chặn thêm trực tiếp vào giỏ hàng nhưng cho phép trợ lý tạo đơn `pending` theo mức phí thỏa thuận và gửi liên kết `order-pay` cho học viên thanh toán. |
| **`UNK-02`** | **Bộ ảnh thật có sự đồng ý của chủ nhân (`REAL_CONSENTED_PHOTOS`)** | Đã nhập 5 ảnh `.webp` vào Media Library ở Step 3 (`ID 86..90`) với tiền tố tiêu đề `"PLACEHOLDER – "` và `alt` tiếng Việt từ `data-alt`. | **Step 3** (Đã nhập ảnh giữ chỗ) & **Step 9** (Nghiệm thu xuất bản) | **CÓ — CHẶN PHÁT HÀNH TUYỆT ĐỐI (`HARD LAUNCH BLOCKER`)** | Đã gắn tiền tố `"PLACEHOLDER – "` trong Media Library ở Step 3; khóa cổng phát hành ở Step 9 cho đến khi thay bằng ảnh thật có bản quyền/sự đồng ý. |
| **`UNK-03`** | **Xác thực các con số tuyên bố & 3 đánh giá học viên (`CLAIMS_AND_TESTIMONIALS_VERIFICATION`)** | Đã nạp nguyên văn 3 bản ghi `jn_testimonial` (`ID 91..93`: `Chị Minh Thảo`, `Chị Ngọc Bích`, `Chị Phương Uyên`) ở Step 3 kèm trường `jn_admin_note = "cần xác minh & xin phép khách"`. | **Step 3** (Đã tạo CPT `jn_testimonial`) & **Step 9** (Duyệt nội dung pháp lý) | **CÓ — CHẶN PHÁT HÀNH (`HARD LAUNCH BLOCKER`)** | Đã gắn cờ `"cần xác minh & xin phép khách"` trực tiếp trên từng bản ghi trong WP Admin ở Step 3 và kiểm tra lại ở cổng xuất bản Step 9. |
| **`UNK-04`** | **Thông tin liên hệ chính thức: Địa chỉ chi tiết, Số điện thoại/Zalo, Giờ mở cửa (`CONTACT_DETAILS`)** | Footer và hộp Hotline chỉ ghi `"Hồ Chí Minh, Việt Nam"`, `"contact@jannienguyen.vn"`, `"(+84) 908-XXX-XXX"`, `"Thứ 2 - Thứ 7: 9:00 - 18:00"`, thiếu số hotline/Zalo và địa chỉ cụ thể. | **Step 3** (Footer & Hotline) & **Step 8** (Schema `LocalBusiness` / `EducationalOrganization`) | **CÓ (Chặn hoàn thiện thông tin liên hệ & Local SEO trước khi Go-Live)** | Giữ nguyên văn nội dung từ wireframe và chờ chủ dự án cung cấp số điện thoại/địa chỉ thật. |
| **`UNK-05`** | **Cổng thanh toán & Thông tin tài khoản ngân hàng nhận chuyển khoản (`PAYMENT_GATEWAYS`)** | Đã cấu hình phương thức Chuyển khoản ngân hàng (`bacs`) ở Step 4 với thông tin tài khoản giữ chỗ gắn nhãn `[PLACEHOLDER – CẦN CẬP NHẬT SỐ TÀI KHOẢN THẬT TRƯỚC KHI GO-LIVE]`; COD chỉ bật cho sản phẩm vật lý. | **Step 4** (Đã cấu hình BACS + COD) & **Step 9** (Cập nhật STK thật / duyệt cài VNPay, MoMo) | **CÓ — CHẶN PHÁT HÀNH (`LAUNCH BLOCKER` — phải thay STK ngân hàng thật trước khi nhận thanh toán thực tế)** | Sử dụng BACS với thông tin giữ chỗ rõ ràng để kiểm thử E2E luồng đặt hàng & `order-pay`; không cài đặt bất kỳ plugin cổng thanh toán bên thứ ba nào (VNPay, MoMo...) khi chưa có phê duyệt và thông tin định danh Merchant của chủ dự án. |
| **`UNK-06`** | **Font chữ tiêu đề chính thức (`HEADING_FONT`)** | **ĐÃ CHỐT: `Playfair Display`** (Chủ dự án đã phê duyệt vào ngày 2026-10-27 sau khi đối chiếu `docs/jn/font-specimen.png`). | **Đã giải quyết ở Step 0** (Thực thi ở **Step 2**) | **KHÔNG (Đã chốt)** | Tự host WOFF2 bộ font **`Playfair Display`** (gộp `latin` + `vietnamese`) cho toàn bộ tiêu đề trên website ở Step 2. |
| **`UNK-07`** | **Tài sản nhận diện thương hiệu: File Logo Vector & Favicon (`BRAND_ASSETS`)** | Đã khởi tạo logo monogram `"JN"` vector (`jn-monogram.svg`), logo email (`jn-email-logo.svg`) và Site Icon `512×512` (`jn-monogram-icon.png`); chưa có file ảnh chia sẻ mạng xã hội (`og:image`) chính thức của chủ dự án. | **Step 8** (SEO/OpenGraph) | **CÓ (Cần ảnh OG chính thức trước khi Go-Live)** | Đã cài đặt Favicon/Site Icon monogram `"JN"` theo bảng màu `#1F1E1D` / `#FAF8F5` ở Step 2 và logo email ở Step 4, sẵn sàng thay thế bằng file của chủ dự án. |
| **`UNK-08`** | **Thiết kế & Nội dung chi tiết của 7 trang còn lại trên thanh điều hướng + Danh sách sản phẩm yêu thích thật (`OTHER_PAGES_CONTENT`)** | Đã dựng khung 7 trang ở Step 3 và hoàn thiện trang `/my-favorite-product/` (`ID=12`, `woocommerce/product-collection`) ở Step 4 (hiện hiển thị trạng thái trống chuẩn thiết kế sau khi xóa sản phẩm DEMO). | **Step 3** & **Step 4** (Đã hoàn tất cấu trúc) | **CÓ đối với các trang/danh mục sản phẩm chủ dự án muốn mở công khai ngay ngày ra mắt** | Giữ trạng thái trống trung thực trên `/my-favorite-product/` và nhãn `[CẦN NỘI DUNG: …]` trên các trang khung cho đến khi chủ dự án cung cấp dữ liệu thật. |
| **`UNK-09`** | **Văn bản pháp lý: Chính sách Bảo mật, Điều khoản Dịch vụ & Câu chữ ô Consent (`LEGAL_PAGES_COPY`)** | Đã dựng khung 2 trang `/chinh-sach-bao-mat/` (đặt làm `wp_page_for_privacy_policy`) và `/dieu-khoan-dich-vu/` gắn nhãn `[CẦN NỘI DUNG: …]`, đồng thời bổ sung ô Consent trong `jn/booking-form` ở Step 3. | **Step 3** (Liên kết tại ô Consent của biểu mẫu) & **Step 9** (Duyệt nội dung pháp lý) | **CÓ — CHẶN PHÁT HÀNH (`HARD LAUNCH BLOCKER` theo quy định bảo vệ dữ liệu cá nhân)** | Đã có trang đích và câu chữ đề xuất tại ô Consent (`"Tôi đồng ý để Jannie Nguyen Academy thu thập và xử lý thông tin cá nhân theo Chính sách bảo mật nhằm mục đích liên hệ tư vấn & xếp lịch Coaching."`), gắn cờ chờ chủ dự án/luật sư duyệt trước khi Go-Live. |
| **`UNK-10`** | **Tên miền chính thức & Máy chủ Web môi trường Production (`PROD_DOMAIN` & `PROD_WEB_SERVER`)** | Đang ghi `UNKNOWN` trong `PROJECT CONTEXT CARD` (chưa rõ máy chủ đích chạy LiteSpeed, Nginx hay Apache). | **Step 8** (Chọn LiteSpeed Cache hay WP Super Cache) & **Step 9** (Tệp cấu hình rewrite máy chủ đích) | **CÓ (Cần biết trước khi đóng gói bàn giao lên máy chủ thật ở Step 9)** | Phát triển trên Docker Apache (`.htaccess`) ở Steps 1–8; chuẩn bị sẵn cả 2 bộ quy tắc rewrite (`.htaccess` cho Apache/LiteSpeed và `nginx.conf` snippet cho Nginx) cùng cấu hình WP Super Cache mặc định (hoặc LiteSpeed Cache nếu xác nhận LiteSpeed). |
| **`UNK-11`** | **Thông tin máy chủ gửi thư giao dịch (`SMTP_CREDENTIALS`)** | Chưa có thông tin tài khoản SMTP/API (Amazon SES, Postmark, Google Workspace, Resend...) cho địa chỉ `contact@jannienguyen.vn`. | **Step 8 / Step 9** (Cấu hình FluentSMTP gửi mail thật) | **CÓ (Cần để gửi email thông báo yêu cầu đặt lịch trên môi trường Production)** | Trong môi trường Docker local (Steps 1–8), định tuyến email qua Mailpit/local catcher để kiểm thử tự động 100% luồng gửi thư; cài sẵn FluentSMTP chờ nhập khóa API khi lên Production. |
| **`UNK-12`** | **Mã theo dõi phân tích (Analytics / Pixel) & Cơ chế Cookie Consent (`ANALYTICS_ID`)** | `PROJECT CONTEXT CARD` ghi rõ: *"Analytics = NONE unless owner adds later (no cookie banner needed if none)"*. | **Step 8** (Hiệu năng & Quyền riêng tư) | **KHÔNG (Mặc định không cài mã theo dõi bên thứ ba)** | Không chèn bất kỳ script theo dõi bên thứ ba nào (giúp đạt điểm tối đa PageSpeed & Best Practices và không cần banner làm phiền người dùng). |
| **`UNK-13`** | **Liên kết mạng xã hội chính thức (`SOCIAL_LINKS`)** | Chưa có URL Facebook, Instagram, YouTube, TikTok hay Zalo Official của Jannie Nguyễn. | **Step 3** (Footer) & **Step 8** (Schema `sameAs`) | **KHÔNG (Có thể bổ sung bất cứ lúc nào)** | Không tự ý thêm icon mạng xã hội trỏ về `#`; chỉ hiển thị khi chủ dự án cung cấp đường dẫn thật. |

---

## 4. Kế Hoạch Triển Khai Chi Tiết Cho Các Bước 1–9 (`Implementation Plan Steps 1–9`)

Dưới đây là lộ trình thực thi từng bước từ **Step 1** đến **Step 9**, bao gồm phạm vi công việc (Scope), sản phẩm đầu ra (Outputs), rủi ro kỹ thuật & biện pháp giảm thiểu (Risks & Mitigations), cùng các điểm cần phê duyệt (Approvals Needed):

---

### STEP 1 — Khởi Tạo Hạ Tầng Docker, Cài Đặt WordPress 7.1.3, Cấu Trúc `/app` & Khung `blocksy-jn` / `jn-core`

- **Phạm vi công việc (Scope):**
  1. Thiết lập `docker-compose.yml` và cấu hình môi trường phát triển cục bộ (`wordpress` chạy PHP 8.3 + Apache với `mod_rewrite`, `db` chạy MariaDB 11.4, `wpcli` chạy `wordpress:cli-php8.3`, và `mailpit` để bắt email giao dịch local).
  2. Cấu hình `wp-config.php` chuẩn bảo mật & tùy biến đường dẫn ngay từ đầu:
     - Tiền tố bảng cơ sở dữ liệu ngẫu nhiên (không dùng `wp_`).
     - Đặt `WP_CONTENT_DIR` / `WP_CONTENT_URL` sang `/app`, `WP_PLUGIN_DIR` / `WP_PLUGIN_URL` sang `/app/modules`, đường dẫn upload sang `/media`.
     - Bật `DISALLOW_FILE_EDIT`, cấu hình khóa bảo mật (salts) qua biến môi trường `.env` (không đưa `.env` vào Git).
  3. Cài đặt cơ sở dữ liệu WordPress `7.1.3`, ngôn ngữ hiển thị Tiếng Việt (`vi`), cấu hình đường dẫn tĩnh (`/%postname%/`), tắt bình luận/pingback mặc định.
  4. Xóa các plugin/theme mặc định không sử dụng (`akismet`, `hello.php`, `twentytwentythree`, `twentytwentyfour`, `twentytwentyfive`).
  5. Cài đặt parent theme **Blocksy (Free)** và plugin **WooCommerce** (bật ngay **HPOS**), **WPS Hide Login** (đặt đường dẫn đăng nhập `/phong-lam-viec-7429`).
  6. Khởi tạo bộ khung (skeleton) cho:
     - Child theme `/app/themes/blocksy-jn` (`style.css`, `functions.php`, `theme.json`).
     - Plugin nghiệp vụ `/app/modules/jn-core` (`jn-core.php` và 4 thư mục `booking/`, `customers/`, `testimonials/`, `blocks/`).
     - Hai Must-Use Plugins `/app/mu-plugins/jn-hardening.php` (đổi REST prefix sang `/api`, tắt XML-RPC, gỡ generator tag) và `/app/mu-plugins/jn-performance.php` (gỡ emoji, dashicons, oembed, Google Fonts của theme cha).
  7. Cấu hình bộ công cụ kiểm chuẩn PHPCS + WPCS và PHPUnit trong Docker.
- **Sản phẩm đầu ra (Outputs):**
  - `docker-compose.yml`, `.env.example`, `docker/` configs.
  - Website WordPress 7.1.3 hoạt động tại `http://localhost:8080`, đăng nhập qua `/phong-lam-viec-7429`, REST API tại `/api/`.
  - Bộ khung `blocksy-jn`, `jn-core`, `jn-hardening.php`, `jn-performance.php` vượt qua kiểm tra `phpcs` với 0 lỗi.
  - Commit và gắn thẻ Git `jn-step-1`.
- **Rủi ro & Giảm thiểu (Risks & Mitigations):**
  - *Rủi ro:* Thay đổi `WP_CONTENT_DIR` sang `/app` và `WP_PLUGIN_DIR` sang `/app/modules` có thể làm một số hằng số nội bộ của WooCommerce hiểu sai nếu khai báo sai thứ tự trong `wp-config.php`.
  - *Giảm thiểu:* Khai báo đầy đủ cả đường dẫn vật lý (`WP_CONTENT_DIR`, `WP_PLUGIN_DIR`, `WPMU_PLUGIN_DIR`) và URL (`WP_CONTENT_URL`, `WP_PLUGIN_URL`, `WPMU_PLUGIN_URL`) trước dòng `require_once ABSPATH . 'wp-settings.php';` và kiểm chứng bằng lệnh `wp plugin list` + `wp option get upload_url_path`.
- **Phê duyệt cần thiết (Approvals Needed):**
  - Chủ dự án phê duyệt kết quả **Step 0** này và chốt lựa chọn **`HEADING_FONT`** trước khi bắt đầu Step 1.

---

### STEP 2 — Hệ Thống Thiết Kế (`theme.json`), Tự Host Font WOFF2, Bộ 19 SVG Icons & Custom Blocks `jn/icon`, `jn/rating`

- **Phạm vi công việc (Scope):**
  1. **Tự host Font chữ (`.woff2`):**
     - Dùng `fontTools` (`pyftsubset`) tạo các file `.woff2` gộp `latin` + `vietnamese` cho **`HEADING_FONT`** đã chọn (mặc định: `Libre Bodoni` — Regular `400`, SemiBold `600`, Italic `400i`) và **`Geist`** (Regular `400`, Medium `500`, SemiBold `600`).
     - Đặt tại `/app/themes/blocksy-jn/assets/fonts/`, khai báo trong `theme.json` và `@font-face` kèm `font-display: swap` và metric-matched fallback (`size-adjust`, `ascent-override`, `descent-override`).
  2. **Mã hóa Design Tokens vào `theme.json` & CSS Variables:**
     - Áp dụng bảng màu Luxury Warm Ivory cùng toàn bộ hiệu chỉnh độ tương phản WCAG AA (§4: `#716A61` cho chữ phụ, `#7E6754` cho chữ nhấn < 24px, `#A0846E` chỉ cho chữ ≥ 24px hoặc đồ họa, `#D1C7BB` cho viền nút/ô nhập liệu `1px solid`, vòng focus rõ nét `2px solid #7E6754`).
     - Thiết lập thang chữ linh hoạt (`clamp()`) khớp chính xác kích thước Mobile → Desktop trong đặc tả mục `2b.2`.
     - Giải quyết triệt để 7 xung đột giữa `DESIGN.md` và `code.html` (xem mục `2b.5` trong `docs/jn/wireframe-spec.md`).
  3. **Trích xuất bộ 19 SVG Icons & Xây dựng Block `jn/icon`, `jn/rating`:**
     - Tạo 19 file SVG tối ưu từ Material Symbols Outlined (nét mảnh `stroke-width: 1.5` / `wght 300`, tổng dung lượng < 8 KB, thay thế hoàn toàn file font 4.0 MB).
     - Xây dựng block `jn/icon` (chọn tên icon trong 19 icon, kích thước, màu sắc, cờ `aria-hidden="true"`).
     - Xây dựng block `jn/rating` (hiển thị 5 ngôi sao SVG màu `#A0846E` kèm `role="img"` và `aria-label="Đánh giá 5 trên 5 sao"`).
  4. **Đăng ký Block Style Variations:**
     - Đăng ký các biến thể kiểu dáng cho `core/group`, `core/button`, `core/heading`, `core/list`, `core/columns` như đã ánh xạ tại mục `2d` của `docs/jn/wireframe-spec.md`.
- **Sản phẩm đầu ra (Outputs):**
  - `theme.json` hoàn chỉnh, bộ font `.woff2` tự host (< 130 KB tổng cộng), bộ 19 SVG icons (< 8 KB).
  - Các block `jn/icon` và `jn/rating` hoạt động mượt trong trình soạn thảo Gutenberg và ngoài giao diện khách.
  - Bài kiểm thử đơn vị (PHPUnit) cho hàm render của `jn/icon` và `jn/rating`.
  - Commit và gắn thẻ Git `jn-step-2`.
- **Rủi ro & Giảm thiểu:**
  - *Rủi ro:* Blocksy parent theme có thể sinh thêm CSS biến màu hoặc tải Google Fonts mặc định.
  - *Giảm thiểu:* Kiểm tra mã nguồn Blocksy bằng `grep`, dùng filter của Blocksy và `wp_dequeue_style` trong `blocksy-jn` / `jn-performance.php` để tắt mọi CSS/font dư thừa.
- **Phê duyệt cần thiết:**
  - Không cần dừng nếu `HEADING_FONT` đã được chốt ở cuối Step 0.

---

### STEP 3 — Xây Dựng Header, Footer, Điều Hướng Mobile & Trang Coaching Bằng Gutenberg Core Blocks + Patterns

- **Phạm vi công việc (Scope):**
  1. **Header & Điều hướng đáp ứng (Responsive Navigation):**
     - Dựng Header cố định (`sticky`, cao `100px`, nền kính mờ `rgba(250, 248, 245, 0.85)` + `backdrop-filter: blur(12px)`, `scroll-padding-top: 112px` trên `html`).
     - Khắc phục lỗi `DEF-07` của wireframe: bổ sung nút mở menu di động (Hamburger drawer) đầy đủ chức năng đóng/mở bằng bàn phím (`Escape`, focus trap, `aria-expanded`) cho màn hình `< 1280px`.
  2. **Footer 4 cột & Bản quyền động:**
     - Dựng Footer 4 cột (`4 : 2 : 3 : 3`), năm bản quyền tự động cập nhật theo năm hiện tại kèm liên kết thực tế thay vì `#`.
  3. **6 Block Patterns cho 6 Phân đoạn Trang Coaching (`jn/coaching-*`):**
     - `jn/coaching-hero`: Bố cục bất đối xứng `7 : 5`, tiêu đề H1 với chữ nghiêng `Chuyên Sâu`, nút CTA cuộn mượt tới `#coaching-tiers`, khung ảnh dọc `4:5` kèm thẻ nổi glassmorphism (`"20+ Năm Kinh Nghiệm Quốc Tế"`).
     - `jn/coaching-benefits`: Lưới 3 cột (`4 : 4 : 4`) gồm 3 thẻ lợi ích sử dụng `jn/icon` và tiêu đề `H3` chuẩn ngữ nghĩa.
     - `jn/coaching-tiers`: Lưới 3 cột trình bày 3 gói (`1:1 Private Coaching`, `Career & Skill Upgrade` có nhãn `"ĐƯỢC CHỌN NHIỀU NHẤT"` và dịch chuyển `-16px` trên desktop, `Business & Salon Mentorship`), các nút CTA gắn thuộc tính `data-select-tier` để chọn trước gói trong biểu mẫu.
     - `jn/coaching-authority`: Bố cục `5 : 7`, cụm ảnh ghép (ảnh chân dung `3:4` + hộp số liệu `"98%"` + thẻ `"Oscar for Beauty"`), khối trích dẫn và lưới 2 cột bằng cấp quốc tế (sửa lỗi nhảy bậc tiêu đề từ `H2 → H4` thành `H2 → H3`).
     - `jn/coaching-testimonials`: Dải số liệu thống kê 3 cột (`98%`, `+180%`, `> 85%`) và lưới 3 thẻ đánh giá học viên dùng `jn/rating`, ảnh đại diện tròn có đầy đủ `alt`, `width="48"`, `height="48"`.
     - `jn/coaching-booking`: Khung biểu mẫu ứng tuyển Mentorship 12 cột (`5 : 7`), sửa bậc tiêu đề quy trình 3 bước thành `H3`, tích hợp block `jn/booking-form`.
  4. **Xử lý 5 hình ảnh giữ chỗ:**
     - Chuyển đổi 5 ảnh mẫu sang định dạng `.webp` tối ưu, lưu tại `/media/`, gắn đầy đủ `alt` từ `data-alt` gốc, khai báo rõ `width`/`height`, đặt `fetchpriority="high"` + `loading="eager"` cho duy nhất ảnh Hero LCP và `loading="lazy"` + `decoding="async"` cho 4 ảnh bên dưới.
- **Sản phẩm đầu ra (Outputs):**
  - Trang `/coaching/` hoàn chỉnh trên WordPress, hiển thị khớp thiết kế ở cả 4 kích thước màn hình (`375px`, `768px`, `1024px`, `1440px`).
  - Ảnh chụp màn hình đối chiếu trực quan ở 4 breakpoint.
  - Commit và gắn thẻ Git `jn-step-3`.
- **Rủi ro & Giảm thiểu:**
  - *Rủi ro:* Độ lệch bố cục ở thẻ giữa (`Career & Skill Upgrade`) và cụm ảnh ghép phân đoạn 4 trên màn hình Tablet (`768px`–`1024px`).
  - *Giảm thiểu:* Sử dụng CSS Grid / Flexbox trong class biến thể của `blocksy-jn` và kiểm tra trực tiếp bằng ảnh chụp Playwright ở cả 4 độ phân giải.
- **Phê duyệt cần thiết:**
  - Không phát sinh yêu cầu dừng nếu không có thay đổi thiết kế.

---

### STEP 4 — Tích Hợp WooCommerce HPOS, Sản Phẩm Dịch Vụ Coaching & Luồng Đặt Lịch 3 Bước (`jn-core/booking`)

- **Phạm vi công việc (Scope):**
  1. **Khởi tạo 3 Sản phẩm Dịch vụ WooCommerce:**
     - Tạo tự động (qua WP-CLI / migration trong `jn-core`) 3 sản phẩm dịch vụ ảo (`Virtual`) ứng với 3 gói:
       - `1:1 Private Coaching` (slug: `private-1on1`)
       - `Career & Skill Upgrade` (slug: `career-skill-upgrade`)
       - `Business & Salon Mentorship` (slug: `business-salon-mentorship`)
  2. **Hoàn thiện Block & Endpoint Xử lý `jn/booking-form`:**
     - Render biểu mẫu với đầy đủ 5 trường gốc trong wireframe + trường chọn **Nhóm đối tượng khách hàng** (`Cá nhân`, `Thợ / Học viên`, `Chủ cơ sở thẩm mỹ, phun xăm, spa`) + **Ô tích đồng ý chính sách bảo mật dữ liệu (Consent checkbox bắt buộc)** + trường ẩn **Honeypot** chống spam.
     - Bổ sung đầy đủ thuộc tính `autocomplete` (`name`, `tel`, `email`), định dạng gợi ý số điện thoại chuẩn Việt Nam `(+84) 908 123 456`, vòng focus rõ nét và thông báo lỗi tiếng Việt thân thiện (`aria-live="polite"`).
     - Xử lý gửi biểu mẫu qua REST API `/api/jn/v1/booking` (có hỗ trợ progressive enhancement fallback khi tắt JS):
       - Kiểm tra Nonce, Honeypot và giới hạn tần suất (Rate-limiting: tối đa 3 yêu cầu / 15 phút mỗi IP).
       - Tạo đơn hàng yêu cầu đặt lịch qua **WooCommerce Order CRUD (`WC_Order` — HPOS-safe)** ở trạng thái `on-hold` / `booking-request`, lưu đầy đủ meta: gói coaching, phân khúc khách hàng, mục tiêu/khó khăn, trạng thái quy trình 3 bước (`step_1_received` → `step_2_slot_confirmed` → `step_3_session_completed`), thời điểm đồng ý bảo mật.
       - Gửi email xác nhận cho học viên và email thông báo cho trợ lý (bắt qua Mailpit trên local).
  3. **Script chọn trước gói (`Tier Pre-selection`):**
     - Viết file JS thuần nhẹ (`booking-form.js`, < 2 KB, `defer`) chỉ tải ở trang có block `jn/booking-form`, xử lý sự kiện bấm nút CTA ở 3 thẻ gói Coaching để chọn đúng `<option>` theo giá trị slug, cuộn mượt tới `#booking-form` và focus vào ô Họ và tên.
- **Sản phẩm đầu ra (Outputs):**
  - Phân hệ `jn-core/booking/` hoạt động đầy đủ với WooCommerce HPOS.
  - Bài kiểm thử PHPUnit cho toàn bộ luồng xác thực dữ liệu, chống giả mạo nonce, rate-limit và khởi tạo đơn HPOS.
  - Commit và gắn thẻ Git `jn-step-4`.
- **Rủi ro & Giảm thiểu:**
  - *Rủi ro:* Ghi dữ liệu trực tiếp vào bảng `wp_postmeta` sẽ làm hỏng tính tương thích HPOS của WooCommerce.
  - *Giảm thiểu:* Chỉ sử dụng `$order->update_meta_data()` và `$order->save()` của đối tượng `WC_Order`; bật chế độ HPOS-only (tắt đồng bộ ngược về `wp_posts`) để đảm bảo phát hiện ngay nếu có lệnh gọi cũ.
- **Phê duyệt cần thiết:**
  - Nếu chủ dự án muốn bật cổng thanh toán trực tuyến cụ thể ngay ở bước này, cần cung cấp `UNK-01` (Giá gói) và `UNK-05` (Thông tin cổng thanh toán); nếu chưa có, hệ thống sử dụng luồng mặc định *"Trợ lý xác nhận khung giờ trong 24 giờ và hướng dẫn thanh toán"*.

---

### STEP 5 — Phân Hệ Quản Lý Khách Hàng (`jn-core/customers`), Chứng Thực (`testimonials`) & Trang Tài Khoản

- **Phạm vi công việc (Scope):**
  1. **Phân hệ Quản lý Khách hàng & Lịch hẹn (`jn-core/customers`):**
     - Xây dựng giao diện quản trị trong WP Admin dành cho chủ dự án và trợ lý để theo dõi danh sách khách hàng và yêu cầu đặt lịch Coaching:
       - Lọc theo 3 phân khúc (`Cá nhân`, `Thợ / Học viên`, `Chủ cơ sở thẩm mỹ, phun xăm, spa`), theo gói đăng ký và theo trạng thái xử lý 3 bước (1. Mới gửi yêu cầu → 2. Đã xác nhận khung giờ trong 24h → 3. Đã hoàn thành phiên 1:1).
       - Lưu ghi chú tư vấn nội bộ, khung giờ hẹn thực tế và hình thức học (Online qua Zoom hoặc Trực tiếp tại Học viện).
       - Xuất danh sách khách hàng có kiểm soát quyền hạn (`manage_woocommerce`).
  2. **Phân hệ Chứng thực Học viên (`jn-core/testimonials`):**
     - Quản lý 3 đánh giá học viên gốc (`Chị Minh Thư`, `Elena Vũ`, `Thanh Hằng`) kèm cờ trạng thái xác minh của chủ dự án (`verified_by_owner`), đảm bảo dễ dàng cập nhật khi chủ dự án thay ảnh thật hoặc điều chỉnh thông tin.
  3. **Đồng bộ Giao diện Trang Tài khoản (`My Account`), Giỏ hàng (`Cart`) & Thanh toán (`Checkout`):**
     - Áp dụng bảng màu Warm Ivory, kiểu chữ `HEADING_FONT` + `Geist`, viền ô nhập liệu `#D1C7BB` và nút bấm pill cho các trang WooCommerce Blocks (`My Account`, `Cart`, `Checkout`) để trải nghiệm đăng nhập/đăng ký tại nút `"Login / Register"` trên Header đồng nhất 100% với trang Coaching.
- **Sản phẩm đầu ra (Outputs):**
  - Phân hệ `jn-core/customers/` và `jn-core/testimonials/` kèm kiểm thử PHPUnit.
  - Giao diện trang `My Account` (`/tai-khoan/`) đồng bộ hoàn toàn với hệ thống thiết kế.
  - Commit và gắn thẻ Git `jn-step-5`.
- **Rủi ro & Giảm thiểu:**
  - *Rủi ro:* Lộ dữ liệu cá nhân của khách hàng qua REST API hoặc trang quản trị cho tài khoản cấp thấp.
  - *Giảm thiểu:* Kiểm tra chặt chẽ quyền `current_user_can( 'manage_woocommerce' )` trên mọi trang quản trị và endpoint dữ liệu khách hàng; viết unit test kiểm tra truy cập trái phép trả về `403`.
- **Phê duyệt cần thiết:**
  - Không cần dừng chờ phê duyệt.

---

### STEP 6 — Dựng Khung Cho 7 Trang Còn Lại Trên Thanh Điều Hướng (`Sitemap Pages Scaffolding`)

- **Phạm vi công việc (Scope):**
  1. Khởi tạo đầy đủ các trang trong sơ đồ điều hướng (mục `2a` của `docs/jn/wireframe-spec.md`) bằng Gutenberg Core Blocks + Patterns đồng bộ ngôn ngữ thiết kế *Warm Ivory & Editorial Serif*:
     - **Trang chủ (`/`)**: Hero giới thiệu định vị thương hiệu Jannie Nguyễn, lối tắt dẫn vào chương trình **Coaching** (trang trọng tâm đã có wireframe), giới thiệu ngắn học viện và CTA đặt lịch.
     - **My Story (`/my-story/`)**: Khung trình bày hành trình 20+ năm kinh nghiệm quốc tế và bằng cấp (tái sử dụng pattern Authority chuẩn hóa, gắn nhãn giữ chỗ rõ ràng cho phần bài viết chi tiết chờ chủ dự án bổ sung).
     - **Course (`/course/`)**: Khung danh mục khóa học đào tạo và đường dẫn chuyển đổi sang Coaching 1:1.
     - **Client Success Story (`/client-success-story/`)**: Trang tổng hợp kết quả thực tế và 3 câu chuyện thành công đã có trong wireframe.
     - **My Favorite Product (`/my-favorite-product/`)**: Khung danh mục sản phẩm khuyên dùng (WooCommerce product grid chuẩn bị sẵn cho chủ dự án cập nhật).
     - **Booking (`/booking/`)**: Trang đặt lịch trực tiếp nhúng pattern `jn/coaching-booking` để khách truy cập từ menu `"Booking"` có thể gửi yêu cầu ngay lập tức.
     - **Login / Register (`/tai-khoan/`)**: Trang WooCommerce My Account đã hoàn thiện ở Step 5.
     - **Trang Pháp lý (`/chinh-sach-bao-mat/` & `/dieu-khoan-dich-vu/`)**: Khung nội dung chính sách bảo mật dữ liệu cá nhân và điều khoản dịch vụ (gắn nhãn `[CẦN DUYỆT PHÁP LÝ - UNK-09]`).
  2. Cập nhật toàn bộ liên kết trong Header và Footer trỏ về các đường dẫn thật (loại bỏ hoàn toàn `href="#"` của wireframe).
- **Sản phẩm đầu ra (Outputs):**
  - Toàn bộ 8 mục điều hướng trên Header và các liên kết ở Footer hoạt động thực tế, không còn bất kỳ liên kết chết (`#` hoặc `404`) nào.
  - Commit và gắn thẻ Git `jn-step-6`.
- **Rủi ro & Giảm thiểu:**
  - *Rủi ro:* Vi phạm quy tắc "không tự bịa tuyên bố/số liệu của chủ dự án" trên các trang chưa có wireframe.
  - *Giảm thiểu:* Chỉ tái sử dụng đúng các thông tin đã có trong wireframe Coaching và dùng các khối giữ chỗ được đánh dấu rõ ràng (`[Nội dung chi tiết đang được cập nhật từ Học viện Jannie Nguyễn]`) cho các phần văn bản chưa được cung cấp (`UNK-08`).
- **Phê duyệt cần thiết:**
  - Chủ dự án có thể chọn giữ công khai tất cả các trang khung này trên menu hoặc tạm ẩn các mục chưa có bài viết hoàn chỉnh trước khi ra mắt.

---

### STEP 7 — Ẩn Dấu Vết WordPress ("Hide WordPress") & Gia Cố Bảo Mật Toàn Diện (`jn-hardening.php`)

- **Phạm vi công việc (Scope):**
  1. **Ẩn hoàn toàn dấu vết WordPress đối với khách chưa đăng nhập (Rule §5):**
     - Cấu hình quy tắc rewrite trên máy chủ web (`.htaccess` và bản đối ứng cho Nginx) kết hợp bộ lọc URL trong `/app/mu-plugins/jn-hardening.php` để mọi đường dẫn tài nguyên tĩnh công khai (CSS, JS, fonts, icons, media) sử dụng đường dẫn sạch (`/app/...`, `/media/...`, `/core-assets/...`), tuyệt đối không xuất hiện chuỗi `wp-content`, `wp-includes`, `wp-json`, `wp-login.php`, `xmlrpc.php` hay tên theme `blocksy` trong mã nguồn HTML trả về cho khách.
     - Xóa bỏ tham số `?ver=X.Y.Z` trên toàn bộ thẻ `<link>` và `<script>`, thay bằng cơ chế cache-busting dựa trên hàm băm nội dung file.
     - Gỡ bỏ hoàn toàn `<meta name="generator">`, `X-Pingback`, RSD link, wlwmanifest link, shortlink, REST API discovery header đối với khách chưa đăng nhập, và các bình luận HTML sinh tự động tiết lộ tên plugin.
  2. **Khóa chặt các đường dẫn nhạy cảm & Chống liệt kê tài khoản:**
     - Truy cập `/wp-admin`, `/wp-login.php`, `/xmlrpc.php`, `/readme.html`, `/license.txt`, `/wp-config-sample.php` khi chưa đăng nhập đều trả về mã **`404 Not Found`** (không chuyển hướng `302` làm lộ đường dẫn `/phong-lam-viec-7429`).
     - Chặn liệt kê người dùng qua `/api/wp/v2/users` (trả về `401`/`403` khi chưa đăng nhập) và chặn truy vấn `?author=N` (trả về `404`).
  3. **Bảo mật Tài khoản & HTTP Security Headers:**
     - Cài đặt và kích hoạt plugin **Two Factor** cho tài khoản quản trị viên.
     - Thiết lập các HTTP Headers bảo mật: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=()`.
- **Sản phẩm đầu ra (Outputs):**
  - `jn-hardening.php` hoàn chỉnh + quy tắc rewrite máy chủ.
  - Kịch bản kiểm thử tự động (`scripts/verify-hide-wordpress.sh` / Playwright) xác nhận 100% các điều kiện trong cổng chất lượng "Hide WordPress" (§7).
  - Commit và gắn thẻ Git `jn-step-7`.
- **Rủi ro & Giảm thiểu:**
  - *Rủi ro:* Quy tắc rewrite ẩn `wp-includes` có thể làm lỗi trình soạn thảo Gutenberg trong trang quản trị nếu áp dụng sai phạm vi.
  - *Giảm thiểu:* Chỉ áp dụng bộ lọc viết lại URL đầu ra cho các yêu cầu giao diện khách (`! is_admin() && ! is_user_logged_in()`), trong khi quy tắc rewrite ở tầng máy chủ web vẫn ánh xạ chính xác đường dẫn sạch về đúng file vật lý trên đĩa.
- **Phê duyệt cần thiết:**
  - Không cần dừng chờ phê duyệt.

---

### STEP 8 — Tối Ưu Hiệu Năng (`jn-performance.php`), Khả Năng Tiếp Cận (WCAG 2.1 AA) & SEO Kỹ Thuật

- **Phạm vi công việc (Scope):**
  1. **Tối ưu Hiệu năng & Ngân sách Tài nguyên (Rule §6):**
     - Trong `jn-performance.php`: gỡ bỏ hoàn toàn các script/style của WooCommerce (`wc-blocks-style`, `woocommerce-general`, `wc-cart-fragments`...) trên trang `/coaching/` và các trang nội dung không có giỏ hàng.
     - Thêm thẻ `<link rel="preload" as="font" type="font/woff2" crossorigin>` cho các file font trọng yếu và `<link rel="preload" as="image" fetchpriority="high">` cho ảnh Hero LCP.
     - Kiểm tra và ép tổng dung lượng tài nguyên trên trang `/coaching/` nằm dưới ngưỡng cứng:
       - HTML < 30 KB nén; CSS ≤ 50 KB nén; JS ≤ 35 KB nén; Fonts ≤ 130 KB; Tổng dung lượng trang lần tải đầu ≤ 500 KB; Số request ≤ 18; **0 request tới bên thứ ba**.
  2. **Cấu hình Page Cache & SEO Nhẹ:**
     - Cài đặt và cấu hình plugin bộ nhớ đệm trang (**WP Super Cache** mặc định hoặc **LiteSpeed Cache** tùy máy chủ đích `UNK-10`).
     - Cài đặt **The SEO Framework** (hoặc **Slim SEO**), cấu hình tiêu đề meta, mô tả meta, OpenGraph và dữ liệu có cấu trúc JSON-LD (`EducationalOrganization` / `Service` / `Course`) cho trang Coaching.
     - Cài đặt sẵn **FluentSMTP** chờ thông tin SMTP Production (`UNK-11`).
  3. **Kiểm toán Khả năng Tiếp cận (WCAG 2.1 AA):**
     - Đảm bảo liên kết "Chuyển đến nội dung chính" (`Skip to content`) hoạt động, thứ tự tiêu đề chuẩn `H1 → H2 → H3` không nhảy bậc, toàn bộ biểu mẫu có `<label for>` và thông báo lỗi `aria-describedby`, hỗ trợ `prefers-reduced-motion: reduce`.
- **Sản phẩm đầu ra (Outputs):**
  - Báo cáo ngân sách tài nguyên (Asset Budget Report) xác nhận đạt mọi ngưỡng cứng ở mục §6.
  - Kết quả quét `@axe-core/playwright` đạt **0 lỗi vi phạm WCAG 2.1 AA**.
  - Commit và gắn thẻ Git `jn-step-8`.
- **Rủi ro & Giảm thiểu:**
  - *Rủi ro:* Điểm LCP trên Mobile bị ảnh hưởng bởi kích thước ảnh Hero hoặc thời gian phản hồi TTFB.
  - *Giảm thiểu:* Nén ảnh Hero `.webp` ở kích thước chuẩn hiển thị kèm `srcset` đáp ứng (responsive images), bật Page Cache tĩnh để TTFB < 50ms.
- **Phê duyệt cần thiết:**
  - Không cần dừng chờ phê duyệt.

---

### STEP 9 — Kiểm Thử Toàn Diện (PHPUnit, Playwright E2E, Lighthouse CI) & Đóng Gói Bàn Giao Production

- **Phạm vi công việc (Scope):**
  1. **Chạy toàn bộ 8 Cổng Chất Lượng Cuối Cùng (Final Quality Gates — Rule §7):**
     - **Gate 1 (Visual Fidelity):** Chụp ảnh màn hình toàn trang `/coaching/` ở `375px`, `768px`, `1024px`, `1440px` và đối chiếu trực quan với `screen.png`.
     - **Gate 2 (Lighthouse Mobile):** Chạy 3 lần liên tiếp trên `/coaching/` ở cấu hình Mobile chuẩn (`Moto G Power`, Slow 4G, CPU 4× throttling) — lấy trung vị đạt: **Performance ≥ 95**, **Accessibility = 100**, **Best Practices = 100**, **SEO = 100** (`LCP ≤ 2.0s`, `CLS ≤ 0.02`, `TBT ≤ 100ms`).
     - **Gate 3 (Lighthouse Desktop):** Đạt **Performance ≥ 99**, **Accessibility = 100**, **Best Practices = 100**, **SEO = 100**.
     - **Gate 4 (Asset Budget):** Xác nhận số lượng request ≤ 18, tổng dung lượng ≤ 500 KB, 0 kết nối ra tên miền ngoài.
     - **Gate 5 (Hide WordPress):** Xác nhận 0 chuỗi `wp-content`, `wp-includes`, `wp-json`, `wp-login.php`, `xmlrpc.php`, `<meta name="generator">` hay `?ver=` trong HTML của khách; `/wp-admin`, `/wp-login.php`, `/xmlrpc.php`, `/readme.html`, `?author=1` trả về `404`; `/api/wp/v2/users` trả về `401`/`403`.
     - **Gate 6 (Functional E2E — Playwright):** Kiểm thử tự động luồng bấm nút CTA chọn gói Coaching → điền form → gửi thành công → xác nhận bản ghi đơn hàng/yêu cầu xuất hiện trong quản trị và email xuất hiện trong Mailpit; kiểm thử menu Mobile, điều hướng bàn phím và lọc danh sách khách hàng.
     - **Gate 7 (Accessibility & Code Quality):** `@axe-core/playwright` = 0 lỗi; `phpcs` (WPCS) = 0 lỗi; `phpunit` = 100% pass; `0` lỗi `console.error` hay `debug.log`.
     - **Gate 8 (Launch Blockers):** Lập bảng kiểm tra rõ ràng từng hạng mục chặn phát hành (`UNK-01` đến `UNK-11`) kèm hướng dẫn thay thế 1-click cho chủ dự án trước khi trỏ tên miền thật.
  2. **Tài liệu Bàn giao & Hướng dẫn Vận hành (`docs/jn/handover.md`):**
     - Hướng dẫn quản trị viên và trợ lý cách xử lý yêu cầu đặt lịch 3 bước, cách cập nhật ảnh thật/giá gói và quy trình sao lưu/triển khai lên máy chủ Production.
- **Sản phẩm đầu ra (Outputs):**
  - Bộ báo cáo kiểm thử tự động (PHPUnit, Playwright E2E, Lighthouse JSON/HTML reports, ảnh chụp 4 độ phân giải).
  - Tài liệu bàn giao `docs/jn/handover.md` và cập nhật trạng thái hoàn tất trong `docs/jn/PROGRESS.md`.
  - Commit và gắn thẻ Git `jn-step-9`.
- **Phê duyệt cần thiết:**
  - Bàn giao nghiệm thu tổng thể cho chủ dự án.
