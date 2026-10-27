# Nhật Ký Tiến Độ & Kế Hoạch Triển Khai (`docs/jn/PROGRESS.md`)

> **Dự án:** Website Jannie Nguyễn — Luxury Beauty Academy & Mentorship (WordPress 7.1.3)
> **Cập nhật lần cuối:** 2026-10-27 (Hoàn tất **JN STEP 2** — Giao diện con `"jannie"` trên nền Blocksy `2.1.60`, hệ thống Design Tokens & 17 mã màu độc nhất trong Block Editor, tự host WOFF2 `Playfair Display` + `Geist`, plugin `jn-core` + block `jn/icon`, 18 biến thể kiểu khối, Header/Footer toàn cục khớp wireframe ở 4 độ phân giải, 0 lỗi tương phản axe-core; sẵn sàng cho Step 3)

---

## 1. Bảng Theo Dõi Trạng Thái Các Giai Đoạn (Steps 0–9)

| Bước | Tên giai đoạn | Trạng thái | Tag Git | Kết quả / Ghi chú chính |
| :---: | :--- | :---: | :---: | :--- |
| **Step 0** | **Kiểm toán Wireframe, Đặc tả Kỹ thuật, Quy tắc Dự án & Bảng mẫu Font** | **HOÀN TẤT (Đã duyệt)** | `jn-step-0` | Khởi tạo `.agent/rules/jn-project.md`, `docs/jn/wireframe-spec.md` (khớp 207/207 nút văn bản DOM), `docs/jn/font-specimen.html` + `.png`, `docs/jn/stack.md`, `docs/jn/decisions.md`, `docs/jn/PROGRESS.md`; chốt `HEADING_FONT = Playfair Display`; không chạm vào `./wordpress`. |
| **Step 1** | **Khởi tạo Docker, Cài đặt WordPress 7.1.3, Cấu trúc `/app` & Bảo mật Cài đặt** | **HOÀN TẤT** | `jn-step-1` | Docker (`wordpress` PHP 8.3.36 Apache, `mariadb:11.4`, `wpcli`, `mailpit:v1.22`); đổi đường dẫn sang `/app`, `/app/modules`, `/media`, `/api`; tiền tố bảng `jn_8f4c_`; ngôn ngữ `vi`; WPS Hide Login `/phong-lam-viec-7429`; giữ `twentytwentyfive` (`1.5`) ở trạng thái `inactive` làm recovery theme; Site Health = 0 lỗi nghiêm trọng; snapshot `backups/jn-step-1.sql.gz`. |
| **Step 2** | **Blocksy Child Theme `"jannie"`, Hệ thống Thiết kế, Tự host Font WOFF2, `jn-core` (`jn/icon`), Block Styles & Header/Footer Toàn cục** | **HOÀN TẤT** | `jn-step-2` | Blocksy `v2.1.60` (`Tested up to: 7.1`) + gói ngôn ngữ `vi`; child theme `"jannie"` (thay thế stub `blocksy-jn`); 8 màu thương hiệu trong Blocksy Global Palette + 9 token mở rộng = **17 mã màu xuất hiện đúng 1 lần** trong Editor; tự host 3 file WOFF2 variable (`Playfair Display` Regular/Italic + `Geist`, `latin`+`vietnamese`, 0 fallback glyphs, 0 CDN requests); plugin `jn-core` với block `jn/icon` (`apiVersion: 3`, 19 icon + `sparkline`); 18 Block-style variations; nhịp phân đoạn `96px` desktop / `64px` mobile (`< 768px`); Header `100px` sticky + Off-canvas `< 1280px` & Footer `6/3/3` khớp wireframe; `axe-core` = 0 lỗi tương phản; xuất `docs/jn/theme-mods.json` & `backups/jn-step-2.sql.gz`. |
| **Step 3** | **Xây dựng Trang Coaching (6 Block Patterns) bằng Gutenberg Core Blocks** | **Sẵn sàng thực thi** | `jn-step-3` | Tái tạo chuẩn xác 6 phân đoạn trang Coaching bằng Gutenberg Core Blocks + Block Styles + `jn/icon` không dùng Page Builder. |
| **Step 4** | **Tích hợp WooCommerce HPOS, Sản phẩm Dịch vụ Coaching & Luồng Đặt lịch 3 Bước (`jn-core/booking`)** | Chưa bắt đầu | `jn-step-4` | Xây dựng block `jn/booking-form`, tạo đơn yêu cầu đặt lịch HPOS-safe, chọn trước gói từ CTA. |
| **Step 5** | **Phân hệ Quản lý Khách hàng (`jn-core/customers`), Chứng thực (`testimonials`) & Trang Tài khoản** | Chưa bắt đầu | `jn-step-5` | Quản lý 3 phân khúc khách hàng, lịch sử tư vấn, cờ đồng ý bảo mật và giao diện `My Account`. |
| **Step 6** | **Khung Các Trang Còn Lại Trong Sơ Đồ Điều Hướng (7 Trang Ngoài Coaching)** | Chưa bắt đầu | `jn-step-6` | Dựng cấu trúc chuẩn thiết kế cho `home`, `my-story`, `course`, `client-success-story`, `my-favorite-product`, `booking`, `my-account`. |
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

## 3. Sổ Đăng Ký Các Hạng Mục Chưa Xác Định (`UNKNOWN Register`)

Mọi thông tin thuộc quyền sở hữu của chủ dự án đang ở trạng thái `UNKNOWN` trong `PROJECT CONTEXT CARD` được theo dõi tại bảng dưới đây. Theo quy tắc dự án (§1), hệ thống **tuyệt đối không tự bịa số liệu hay nội dung**, mà sử dụng nhãn giữ chỗ (placeholder) rõ ràng kèm ghi chú tại đây cho đến khi chủ dự án cung cấp:

| Mã ID | Hạng mục thông tin (`PROJECT CONTEXT CARD`) | Hiện trạng trong Wireframe / Dự án | Bước bị ảnh hưởng (Blocking Step) | Chặn phát hành chính thức? (`Blocks Launch?`) | Giải pháp tạm thời trong quá trình phát triển (Steps 1–8) |
| :---: | :--- | :--- | :---: | :---: | :--- |
| **`UNK-01`** | **Giá bán chính thức của 3 gói Coaching (`PACKAGE_PRICES`)** | Không hiển thị giá tiền trong `code.html`; chỉ có nhãn gói (`1:1 Private Coaching`, `Career & Skill Upgrade`, `Business & Salon Mentorship`) và mô tả quyền lợi. | **Step 4** (Khởi tạo sản phẩm dịch vụ WooCommerce) | **CÓ (Chặn phát hành nếu bật thanh toán trực tuyến; hoặc giữ chế độ "Báo giá sau khi thẩm định hồ sơ")** | Tạo 3 sản phẩm dịch vụ WooCommerce với giá `0 ₫` kèm cờ meta `_jn_price_on_request = yes` (hiển thị nhãn *"Theo lộ trình cá nhân hóa — Trợ lý báo giá sau khi xác nhận lịch"* cho đến khi chủ dự án cung cấp bảng giá). |
| **`UNK-02`** | **Bộ ảnh thật có sự đồng ý của chủ nhân (`REAL_CONSENTED_PHOTOS`)** | Cả 5 ảnh trong `code.html` đều là ảnh giữ chỗ do AI sinh ra trên `lh3.googleusercontent.com` mô tả "Jannie Nguyen" và 3 học viên có tên cụ thể (xem `DEF-04`). | **Step 3** (Tải tài sản ảnh mẫu vào thư viện `/media`) & **Step 9** (Nghiệm thu xuất bản) | **CÓ — CHẶN PHÁT HÀNH TUYỆT ĐỐI (`HARD LAUNCH BLOCKER`)** | Tải bản sao cục bộ đã chuyển sang `.webp`/`.avif` kèm nhãn quản trị `[PLACEHOLDER - CHỜ ẢNH THẬT]` để dựng giao diện ở Step 3; khóa cổng phát hành ở Step 9 cho đến khi thay bằng ảnh thật có bản quyền/sự đồng ý. |
| **`UNK-03`** | **Xác thực các con số tuyên bố & 3 đánh giá học viên (`CLAIMS_AND_TESTIMONIALS_VERIFICATION`)** | Các số liệu (`20+ năm`, `98%`, `+180%`, `> 85%`, `Oscar for Beauty`) và 3 lời chứng thực (`Chị Minh Thư`, `Elena Vũ`, `Thanh Hằng`) xuất hiện trong wireframe. | **Step 5** (Nhập dữ liệu `jn-core/testimonials`) & **Step 9** (Duyệt nội dung pháp lý) | **CÓ — CHẶN PHÁT HÀNH (`HARD LAUNCH BLOCKER`)** | Giữ nguyên văn 100% theo đúng wireframe ở Step 3 & Step 5, gắn cờ `needs_owner_verification = true` trong quản trị và báo cáo kiểm수 ở Step 9. |
| **`UNK-04`** | **Thông tin liên hệ chính thức: Địa chỉ chi tiết, Số điện thoại/Zalo, Giờ mở cửa (`CONTACT_DETAILS`)** | Footer chỉ ghi chung `"Hồ Chí Minh, Việt Nam"`, `"contact@jannienguyen.vn"`, `"Thứ 2 - Thứ 7: 9:00 - 18:00"`, thiếu số hotline/Zalo và số nhà/tên đường cụ thể. | **Step 3** (Footer) & **Step 8** (Schema `LocalBusiness` / `EducationalOrganization`) | **CÓ (Chặn hoàn thiện thông tin liên hệ & Local SEO trước khi Go-Live)** | Giữ nguyên văn nội dung Footer từ wireframe và cung cấp trang cấu hình `Cài đặt Học viện (JN Settings)` để chủ dự án điền số hotline/địa chỉ chi tiết bất kỳ lúc nào. |
| **`UNK-05`** | **Cổng thanh toán & Thông tin định danh Merchant (`PAYMENT_GATEWAYS`)** | Chưa xác định cổng thanh toán (chuyển khoản ngân hàng VietQR, VNPay, MoMo, Stripe, hay chỉ thu phí sau khi trợ lý tư vấn). | **Step 4** (Cấu hình WooCommerce Checkout) | **KHÔNG chặn ra mắt luồng Đặt lịch tư vấn (`Request-based booking`), nhưng CÓ chặn nếu cần thu tiền tự động ngay trên web** | Bật sẵn phương thức *"Xác nhận lịch hẹn trước — Thanh toán theo hướng dẫn của Trợ lý"* (kèm tùy chọn Chuyển khoản ngân hàng chuẩn của WooCommerce) ở Step 4; chỉ cài plugin cổng thanh toán khi có thông tin Merchant. |
| **`UNK-06`** | **Font chữ tiêu đề chính thức (`HEADING_FONT`)** | **ĐÃ CHỐT: `Playfair Display`** (Chủ dự án đã phê duyệt vào ngày 2026-10-27 sau khi đối chiếu `docs/jn/font-specimen.png`). | **Đã giải quyết ở Step 0** (Thực thi ở **Step 2**) | **KHÔNG (Đã chốt)** | Tự host WOFF2 bộ font **`Playfair Display`** (gộp `latin` + `vietnamese`) cho toàn bộ tiêu đề trên website ở Step 2. |
| **`UNK-07`** | **Tài sản nhận diện thương hiệu: File Logo Vector & Favicon (`BRAND_ASSETS`)** | Đã khởi tạo logo monogram `"JN"` vector (`jn-monogram.svg`) và Site Icon `512×512` (`jn-monogram-icon.png`) ở Step 2; chưa có file ảnh chia sẻ mạng xã hội (`og:image`) chính thức của chủ dự án. | **Step 8** (SEO/OpenGraph) | **CÓ (Cần ảnh OG chính thức trước khi Go-Live)** | Đã cài đặt Favicon/Site Icon monogram `"JN"` theo bảng màu `#1F1E1D` / `#FAF8F5` ở Step 2, sẵn sàng thay thế bằng file của chủ dự án. |
| **`UNK-08`** | **Thiết kế & Nội dung chi tiết của 7 trang còn lại trên thanh điều hướng (`OTHER_PAGES_CONTENT`)** | Đã tạo 8 trang khung trong sơ đồ điều hướng ở Step 2 để liên kết menu hoạt động; chỉ trang **Coaching** có wireframe (`code.html`), 7 trang còn lại chưa có wireframe riêng. | **Step 6** (Dựng bố cục chi tiết các trang còn lại) | **CÓ đối với các trang chủ dự án muốn mở công khai ngay ngày ra mắt** | Ở Step 6: hoàn thiện bố cục chi tiết cho 7 trang ngoài Coaching đồng bộ hệ thống thiết kế và có tùy chọn ẩn khỏi menu nếu chưa có bài viết thật. |
| **`UNK-09`** | **Văn bản pháp lý: Chính sách Bảo mật, Điều khoản Dịch vụ & Hoàn tiền (`LEGAL_PAGES_COPY`)** | Đã tạo 2 trang thật `/chinh-sach-bao-mat/` và `/dieu-khoan-dich-vu/` gắn nhãn `[PLACEHOLDER — UNK-09]` và nối vào Footer ở Step 2; đang chờ văn bản pháp lý chính thức từ chủ dự án. | **Step 4** (Liên kết tại ô Consent của biểu mẫu) & **Step 9** (Duyệt nội dung pháp lý) | **CÓ — CHẶN PHÁT HÀNH (`HARD LAUNCH BLOCKER` theo quy định bảo vệ dữ liệu cá nhân)** | Đã có trang đích hoạt động với thông báo giữ chỗ rõ ràng (`UNK-09`), sẵn sàng cập nhật nội dung pháp lý chính thức trước khi Go-Live. |
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
