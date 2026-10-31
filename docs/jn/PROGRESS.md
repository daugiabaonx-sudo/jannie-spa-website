# Nhật Ký Tiến Độ & Kế Hoạch Triển Khai (`docs/jn/PROGRESS.md`)

> **Dự án:** Website Jannie Nguyễn — Luxury Beauty Academy & Mentorship (WordPress 7.1.3)
> **Cập nhật lần cuối:** 2026-10-31 (**Step 18 — Trang Sản phẩm yêu thích theo bản "Nâng cấp My Favorite Product"**, xem mục 2t và ADR-025). Trước đó: 2026-10-30 (**Step 16 — Nội dung jannieedu.com cũ cho My Story & Coaching**, xem mục 2r và ADR-023). Trước đó cùng ngày: (**Step 15 — Nội dung jannieedu.com cũ cho Sản phẩm, Khóa học & Cảm nhận**, xem mục 2q và ADR-022). Trước đó cùng ngày: (**Hero trang chủ mới — khung ảnh vòm & polaroid đổi ảnh**, xem mục 2p và ADR-021). Trước đó cùng ngày: (**Step 14 — Kiểm toán lại & tối ưu SEO/AEO**, xem mục 2o và `docs/jn/seo-aeo/SEO_AEO_Audit_2026-10-30/1. report/bao-cao-kiem-toan-seo-aeo.md`). 2026-10-28: **JN STEP 10 — Kiểm toán & tối ưu SEO/AEO hoàn tất**, xem `docs/jn/seo-aeo/SEO_AEO_Audit_2026-10-28/1. report/bao-cao-kiem-toan-seo-aeo.md`. Trước đó: Hoàn tất **JN STEP 9 — TOÀN BỘ DỰ ÁN STEPS 0–9 HOÀN TẤT 100%**: Kiểm toán Launch Blockers `scripts/check-launch-blockers.php` đạt **`0` unwaived open blockers** với quyết định miễn trừ bằng văn bản của chủ dự án cho `UNK-01..UNK-14` và xóa sạch 100% dữ liệu mẫu/test; Kiểm thử Hồi quy Toàn diện `scripts/verify-step-9-regression.mjs` đạt **100% PASS** — `axe-core` WCAG 2.1/2.2 AA đạt **`0` vi phạm trên toàn bộ 13/13 trang**, đối chiếu thị giác 4 độ phân giải `375/768/1024/1440` đạt `Δ = 0px` & `0` tràn ngang, điều hướng chỉ bằng bàn phím + trả focus `Escape` trên mobile đạt 100%, kiểm thử đa trình duyệt `Chromium` / `Firefox` / `WebKit` đạt 100%, quét `92` liên kết nội bộ đạt `0` lỗi gãy & `0` `href="#"`, kiểm chứng 15/15 mẫu email tiếng Việt trong Mailpit; đóng gói Bộ công cụ Triển khai Production `deploy/` + Runbook tiếng Việt `docs/jn/deploy/runbook-trien-khai-va-rollback.md`; xây dựng 8 tài liệu bàn giao tiếng Việt kèm 16 ảnh chụp màn hình tại `docs/jn/handover/`; xuất báo cáo nghiệm thu cuối cùng `docs/jn/final-report.md`, snapshot `backups/jn-step-9.sql.gz` & gắn thẻ Git `jn-step-9`)

---

## 1. Bảng Theo Dõi Trạng Thái Các Giai Đoạn (Steps 0–10)

| Bước | Tên giai đoạn | Trạng thái | Tag Git | Kết quả / Ghi chú chính |
| :---: | :--- | :---: | :---: | :--- |
| **Step 0** | **Kiểm toán Wireframe, Đặc tả Kỹ thuật, Quy tắc Dự án & Bảng mẫu Font** | **HOÀN TẤT (Đã duyệt)** | `jn-step-0` | Khởi tạo `.agent/rules/jn-project.md`, `docs/jn/wireframe-spec.md` (khớp 207/207 nút văn bản DOM), `docs/jn/font-specimen.html` + `.png`, `docs/jn/stack.md`, `docs/jn/decisions.md`, `docs/jn/PROGRESS.md`; chốt `HEADING_FONT = Playfair Display`; không chạm vào `./wordpress`. |
| **Step 1** | **Khởi tạo Docker, Cài đặt WordPress 7.1.3, Cấu trúc `/app` & Bảo mật Cài đặt** | **HOÀN TẤT** | `jn-step-1` | Docker (`wordpress` PHP 8.3.36 Apache, `mariadb:11.4`, `wpcli`, `mailpit:v1.22`); đổi đường dẫn sang `/app`, `/app/modules`, `/media`, `/api`; tiền tố bảng `jn_8f4c_`; ngôn ngữ `vi`; WPS Hide Login `/<WP_LOGIN_SLUG>`; giữ `twentytwentyfive` (`1.5`) ở trạng thái `inactive` làm recovery theme; Site Health = 0 lỗi nghiêm trọng; snapshot `backups/jn-step-1.sql.gz`. |
| **Step 2** | **Blocksy Child Theme `"jannie"`, Hệ thống Thiết kế, Tự host Font WOFF2, `jn-core` (`jn/icon`), Block Styles & Header/Footer Toàn cục** | **HOÀN TẤT** | `jn-step-2` | Blocksy `v2.1.60` (`Tested up to: 7.1`) + gói ngôn ngữ `vi`; child theme `"jannie"` (thay thế stub `blocksy-jn`); 8 màu thương hiệu trong Blocksy Global Palette + 9 token mở rộng = **17 mã màu xuất hiện đúng 1 lần** trong Editor; tự host 3 file WOFF2 variable (`Playfair Display` Regular/Italic + `Geist`, `latin`+`vietnamese`, 0 fallback glyphs, 0 CDN requests); plugin `jn-core` với block `jn/icon` (`apiVersion: 3`, 19 icon + `sparkline`); 18 Block-style variations; nhịp phân đoạn `96px` desktop / `64px` mobile (`< 768px`); Header `100px` sticky + Off-canvas `< 1280px` & Footer `6/3/3` khớp wireframe; `axe-core` = 0 lỗi tương phản; xuất `docs/jn/theme-mods.json` & `backups/jn-step-2.sql.gz`. |
| **Step 3** | **Thư viện Gutenberg Patterns, Trang Coaching, CPT `jn_testimonial`, Block `jn/booking-form` (UI) & Khung Các Trang** | **HOÀN TẤT** | `jn-step-3` | 10 pattern trong `patterns/` (danh mục `"Jannie Nguyen"`, `0` mã màu hex cứng, `0` `core/html`) + 2 Synced Patterns (`wp_block` ID `94`, `95`); CPT `jn_testimonial` (`publicly_queryable: false`) gắn Block Bindings `core/post-meta` + block `jn/rating` (SVG stars, `0` JS) + 3 lời chứng thực gắn ghi chú `"cần xác minh & xin phép khách"`; 5 ảnh `.webp` nhập vào Media Library với tiền tố `"PLACEHOLDER – "`; block `jn/booking-form` chọn trước gói từ `?goi=` cả phía server & client (`view.js` `3.4 KB` raw / `< 1.5 KB` gzip); hoàn thiện `/coaching/` (`Δ = 0px` ở `375/768/1024/1440`) và dàn khung `Home`, `My Story`, `Course`, `Client Success Story`, `Booking`, `Chính sách bảo mật`, `Điều khoản dịch vụ`; Lighthouse Mobile trên `/coaching/`: **Accessibility = 100**, **Best Practices = 100**, **Performance = 96**; snapshot `backups/jn-step-3.sql.gz`. |
| **Step 4** | **WooCommerce: Cửa hàng, 3 Gói Coaching Ẩn, Trang `My Favorite Product`, Khối Cart/Checkout/My Account & Phân lập Tài nguyên** | **HOÀN TẤT** | `jn-step-4` | WooCommerce `v11.2.1` + gói ngôn ngữ `vi`; tiền tệ `VND` (`450.000 ₫`), HPOS bật (`OrderTableDataStore`), tắt Order Attribution (`ADR-005`); 3 sản phẩm dịch vụ ảo đặt tên nguyên văn theo biểu mẫu (`ID 163, 164, 165`) giữ ở trạng thái `Draft` do giá `UNKNOWN` (`UNK-01`), ẩn khỏi danh mục & chặn thêm vào giỏ nhưng thanh toán được qua liên kết `order-pay` (`Order #175`); trang `/my-favorite-product/` (`ID=12`, `woocommerce_shop_page_id` theo `ADR-006`) dựng bằng pattern đầu mục + `woocommerce/product-collection`; Cart/Checkout/My Account dùng WooCommerce Blocks định kiểu bằng token; nút `"Đăng ký"` trên Header trỏ tới `/tai-khoan/#register`; Checkout giỏ hàng ảo chỉ hỏi Họ tên + SĐT + Email + ô tích Điều khoản; kiểm thử E2E thành công 1 đơn khách vãng lai (`#176`), 1 đơn khách đăng nhập (`#177` → đổi trạng thái `Đã hoàn thành` trong HPOS → khách thấy trong `/tai-khoan/orders/`), 8 email tiếng Việt gắn thương hiệu trong Mailpit; đã xóa sản phẩm `"DEMO – xóa trước khi chạy thật"` sau khi kiểm thử; `0` tài nguyên WooCommerce & `0` `get_refreshed_fragments` trên `/coaching/`, `/my-story/`, `/booking/`; Lighthouse Mobile: `/my-favorite-product/` `96/100/100`, `/checkout/` `100/100/100`, `/coaching/` `98/100/100`; snapshot `backups/jn-step-4.sql.gz`. |
| **Step 5** | **Bộ Máy Đặt Lịch (`jn-core/booking`), Quản Lý Khách Hàng (`customers`), Email `.ics` & Endpoint `Lịch hẹn của tôi`** | **HOÀN TẤT** | `jn-step-5` | CPT riêng tư `jn_booking` (`ADR-007`), máy trạng thái 5 bước (`Chờ xác nhận` → `Đã xác nhận lịch` → `Hoàn thành` / `Vắng mặt`, mọi trạng thái chưa kết thúc → `Đã hủy`), gửi biểu mẫu không cần JS (Post/Redirect/Get `303`) + có JS (`rest_url()`) kèm làm mới token HMAC khi tương tác đầu tiên cho trang lưu cache, chống spam (honeypot, `>= 2s`, băm IP/SĐT/Email), khớp/tạo khách hàng & tự gắn khi đăng ký, trợ lý xác nhận lịch → cảnh báo trùng giờ → tạo đơn HPOS `pending` → gửi email đính kèm `.ics` + liên kết `order-pay`, đồng bộ trạng thái thanh toán & gắn cờ hoàn tiền thủ công khi hủy đơn đã trả tiền, hỗ trợ 1 đơn ghi danh + tối đa 12 buổi Mentorship, công suất tháng & ghi chú Waitlist, giao diện quản trị tiếng Việt `"Lịch hẹn"` + `"Lịch theo ngày"` + `"Cài đặt"`, 5 lớp `WC_Email` tùy chỉnh, trang `/tai-khoan/lich-hen/`, vai trò `"Trợ lý lịch hẹn"` (`jn_booking_assistant`), trình xuất/xóa dữ liệu cá nhân WP; đạt **45/45** bài test backend & E2E trình duyệt xanh 100%, `view.js` = `3,785 bytes` (`3.70 KB`) gzip (`<= 5 KB`), trang không có form tải `0` tài nguyên mới; snapshot `backups/jn-step-5.sql.gz`. |
| **Step 6** | **Quản Lý Khách Hàng CRM (`Khách hàng`), Xuất CSV UTF-8 BOM, Tài Khoản Nhân Sự, Bắt Buộc 2FA & Quyền Riêng Tư CRM** | **HOÀN TẤT** | `jn-step-6` | Hồ sơ CRM 10 trường (`JN_Customer_Profile`) đồng bộ 2 chiều với WooCommerce `WC_Customer`; tự động gắn lịch hẹn & đơn hàng HPOS vãng lai vào tài khoản khi đăng ký/đăng nhập; màn hình `"Khách hàng"` (`admin.php?page=jn-customers`, `ADR-008`) hợp nhất khách từ lịch hẹn, đơn hàng & đăng ký kèm tìm kiếm đa định dạng SĐT (`0908…`, `+84908…`), bộ lọc (`Loại khách`, `Tag`, `Có lịch chờ xác nhận`), trang chi tiết khách hàng & thao tác `"Tạo lịch hẹn"` Walk-in; cảnh báo trùng SĐT/Email (`jn_customer_duplicate_log`); xuất CSV khách hàng & lịch hẹn (`UTF-8 with BOM \xEF\xBB\xBF`, chống CSV Formula Injection); 4 vai trò nhân sự (`Quản trị`, `Quản lý cửa hàng`, `Trợ lý lịch hẹn`, `Biên tập nội dung` — `docs/jn/roles.md`) bắt buộc 2FA (`two-factor` `v0.17.0`), chặn khách hàng khỏi Admin Bar & `/wp-admin/`; Exporter/Eraser bao gồm trường CRM + cài đặt lưu trữ lịch hẹn vãng lai đã hủy mặc định tắt (`ADR-009`); đạt **37/37** test Step 6 & **45/45** test Step 5; snapshot `backups/jn-step-6.sql.gz`. |
| **Step 7** | **Ẩn Dấu Vết WordPress ("Hide WordPress") & Gia Cố Bảo Mật Toàn Diện (`jn-hardening.php`)** | **HOÀN TẤT** | `jn-step-7` | `jn-hardening.php` `v2.0.0` (3 mô-đun `html-headers`, `endpoints`, `branding-auth`) + `.htaccess` & `docker/nginx/jn-hardening.conf` + `wordpress/media/.htaccess` + `expose_php = Off`; gỡ toàn bộ thẻ generator, RSD, `wlwmanifest`, `shortlink`, REST/oEmbed discovery, `wp-emoji`, `X-Pingback`, `X-Powered-By`, `X-Redirect-By`, HTML comments & CSS `sourceURL`; thay `?ver=` bằng `?v=<10-hex-sha256>`; bí danh đường dẫn tĩnh `/static/{core,core-admin,base,ui,shop,academy,ext}/` & `/api/ajax` (`ADR-010`); inline 11 core block styles trên `/coaching/` (HTML giảm `-2,783 B` raw / `-741 B` gzipped, `0` request `/wp-includes/`); chặn ẩn danh `/api/wp/v2/users` & `/?rest_route=/wp/v2/users`, `xmlrpc.php`, `?author=N`, `/feed/`, `/wp-sitemap.xml`, `readme.html`, `license.txt`, `wp-config*.php`, `install.php`, `upgrade.php` với `404`; pattern `jannie/error-404`, favicon SVG monogram, trang đăng nhập `/<WP_LOGIN_SLUG>` đồng bộ nhận diện, Việt hóa 100% email hệ thống; giới hạn đăng nhập sai `<= 5 lần / 15 phút` trên cả `/<WP_LOGIN_SLUG>` & `/tai-khoan/`, 6 HTTP Security Headers; trình quét `scripts/jn-fingerprint-scan.mjs` đạt **31/31 PASS (`0 FAIL`)** (`docs/jn/fingerprint-report.md`); đạt **54/54** test Step 7, **37/37** test Step 6, **45/45** test Step 5 & E2E 5/5 xanh; Lighthouse Mobile `/coaching/`: **Performance = 96**, **Accessibility = 100**, **Best Practices = 100**; snapshot `backups/jn-step-7.sql.gz`. |
| **Step 8** | **Tối Ưu Hiệu Năng Theo Ngân Sách (`jn-performance.php` v2.0.0), Page Cache, Ảnh AVIF/WebP & SEO Kỹ Thuật** | **HOÀN TẤT** | `jn-step-8` | `lighthouserc.json` + `scripts/run-step-8-lhci.mjs` (`66` lần đo trên 11 URL × Mobile/Desktop, trung vị 3 lần): **22/22 mục tiêu PASS 100%** (Mobile Content `100/100/100/100`, `LCP 1.66–1.88s`, `FCP 0.79–0.93s`, `TBT 0ms`, `CLS 0–0.032`; Mobile Shop/Product/Cart/Checkout/Account `99–100/100/100/100`; Desktop `100/100/100/100` trên 11/11 trang); `wp-super-cache` (`v3.1.4`) loại trừ `/cart/`, `/checkout/`, `/tai-khoan/`, `order-pay`, `/api/` và cookie phiên WooCommerce/đăng nhập + nén Brotli/Gzip + `Cache-Control: public, max-age=31536000, immutable`; inline `jannie-bundle.min.css` & `woocommerce.css`, trì hoãn tài nguyên không thiết yếu sau FCP, gỡ `zxcvbn` (`400 KB`) & `selectWoo`; sinh ảnh AVIF (`q=50`) + WebP fallback cho `jn-hero-400/652/800`, `jn-expert-420/640/750`, `jn-avatar-96/160` & preload `imagesrcset`/`imagesizes` trên `/` và `/coaching/`; preload 2 font WOFF2 chính + trì hoãn font italic sau FCP kèm fallback khớp metric; Speculative Loading (`prerender`/`moderate`) loại trừ đường dẫn động; Third-Party Facade (`0` bên thứ ba mặc định); `The SEO Framework` (`autodescription` `v5.2.0`) tắt chữ ký HTML comment, tiêu đề/mô tả tiếng Việt, ảnh OG `1200×630`, `/sitemap.xml`, `/robots.txt` (`docs/jn/robots.local.txt` & `robots.production.txt`), JSON-LD `@graph` (`Organization`/`ProfessionalService` + `Person` + `Service`/`Offer` không bịa giá, không gắn `Review`/`AggregateRating`); dọn sạch transient hết hạn (`0`), ghim autoloaded options ở `24,196 B < 65 KB`, ghi nhận `ADR-011`; đạt **31/31 PASS (`0 FAIL`)** trong `jn-fingerprint-scan.mjs`, **35/35** test Step 8, **45/45** test Step 7, **37/37** test Step 6, **45/45** test Step 5; snapshot `backups/jn-step-8.sql.gz`. |
| **Step 9** | **Kiểm Định Chất Lượng Toàn Diện (QA), PageSpeed Thực Tế, Bộ Công Cụ Deploy & Bàn Giao Vận Hành** | **HOÀN TẤT** | `jn-step-9` | `scripts/check-launch-blockers.php` đạt **`0` unwaived open blockers** (`BL-01..BL-07a` được chủ dự án miễn trừ bằng văn bản, `BL-07b` xóa sạch 100% sản phẩm DEMO & dữ liệu test); `scripts/verify-step-9-regression.mjs` đạt **100% PASS** (`axe-core` WCAG 2.1/2.2 AA = `0` vi phạm trên 13/13 trang, Visual Parity 4 độ phân giải `375/768/1024/1440`, điều hướng bàn phím + `Escape` mobile drawer, đa trình duyệt `Chromium`/`Firefox`/`WebKit`, `92` liên kết nội bộ `0` gãy, trang `404` & `429`, `15/15` mẫu email tiếng Việt trong Mailpit); đóng gói `deploy/` (`wp-config.production.php`, `.env.production.example`, `generate-salts.sh`, `apache-litespeed.htaccess`, `nginx-production.conf`, `rsync-exclude.txt`, `db-search-replace.sh` `--dry-run`, `rollback.sh`, `cron.d-jannie-wordpress`) + Runbook tiếng Việt `docs/jn/deploy/runbook-trien-khai-va-rollback.md`; kịch bản PSI & Live E2E chờ lệnh `"deploy"` (`scripts/run-step-9-psi-and-live-e2e.mjs`); 8 tài liệu bàn giao tiếng Việt có 16 ảnh chụp màn hình tại `docs/jn/handover/`; báo cáo nghiệm thu `docs/jn/final-report.md`; snapshot `backups/jn-step-9.sql.gz`. |
| **Step 10** | **Kiểm Toán & Tối Ưu SEO/AEO (skill `sunext-semrush-ahrefs-os`), Module SEO Hợp Nhất, `/llms.txt` & FAQ** | **HOÀN TẤT** | `jn-step-10` | Báo cáo `docs/jn/seo-aeo/SEO_AEO_Audit_2026-10-28/` (audit offline `scripts/seo-audit-local.py` dùng engine Suite 3/6/8 của skill vì crawler skill chặn `localhost`; Keyword Architect 705 từ khóa/11 cụm đã đánh giá phản biện; nguồn backlink đã lọc): lỗi **CRITICAL/HIGH/MEDIUM/LOW `12/5/23/20` → `0/0/1/12`**; gỡ số điện thoại bịa `+84908789866` khỏi schema (option `jn_brand_telephone`); một đồ thị JSON-LD duy nhất (tắt graph TSF qua `the_seo_framework_schema_graph_data`; `WebSite`/`WebPage`/`ProfilePage`/`BreadcrumbList`/`Organization`/`Person`/`Service`/`FAQPage`); `noindex, follow` cho Cart/Checkout/Tài khoản/Tìm kiếm (ngoại lệ Lighthouse `is-crawlable` có văn bản); robots.txt một nguồn (không lộ slug đăng nhập, không `Disallow` trang `noindex`, tôn trọng `blog_public=0` ngoài local) + cho phép bot AI (giả định Q2); code review độc lập 0 CRITICAL, đã xử lý 2 HIGH + 7 MEDIUM; `/llms.txt`; tắt branding tiêu đề trùng; sửa thẻ canonical/description trùng, H1 kép trang shop; pattern `jannie/faq`; tách SEO ra `mu-plugins/jn-performance/class-jn-seo-*.php` (`jn-performance.php` 1.553 → 984 dòng, v2.1.0; theme v1.1.0); ADR-012, ADR-013; **77/77** test Step 10, 54/54 Step 7, 45/45 Step 5, 37/37 Step 6, 34/35 Step 8 (1 FAIL có sẵn: chuỗi `woocommerce` trong CSS động Blocksy — A/B chứng minh không do Step 10), fingerprint **31/31**, regression Step 9 **100%**, LHCI **22/22 PASS** (`docs/jn/lighthouse/step-10/`); snapshot `backups/jn-step-10.sql.gz`. |

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
   - Cài đặt với tiêu đề `"Jannie Nguyen"`, tài khoản quản trị không đoán được (`<WP_ADMIN_USER>`), ngôn ngữ Tiếng Việt (`vi` — `<html lang="vi">`), múi giờ `Asia/Ho_Chi_Minh`, định dạng ngày `d/m/Y`, giờ `H:i`, tuần bắt đầu Thứ Hai (`1`), đường dẫn tĩnh `/%postname%/`, `blog_public = 0`, đóng bình luận & pingback mặc định.
   - Đã xóa bài viết mẫu, trang mẫu, bình luận mẫu và 2 plugin mặc định (`hello`, `akismet`).
   - **Ghi chú giao diện dự phòng (Recovery Theme):** Đã xóa `twentytwentythree` và `twentytwentyfour`; **giữ lại `twentytwentyfive` (`v1.5`) ở trạng thái `inactive` làm giao diện dự phòng khôi phục (recovery theme)** theo đúng yêu cầu; cài đặt parent theme `blocksy` (`v2.1.60`) và kích hoạt child theme `blocksy-jn` (`v1.0.0`).
4. **WPS Hide Login & Kiểm định Kỹ thuật:**
   - Cài đặt và kích hoạt `wps-hide-login` (`v1.9.19`), đặt `LOGIN_SLUG = <WP_LOGIN_SLUG>`.
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

## 2g. Kết Quả Nghiệm Thu JN STEP 6

1. **Hồ Sơ Khách Hàng CRM 10 Trường & Đồng Bộ 2 Chiều Với WooCommerce (`JN_Customer_Profile`):**
   - Lưu trữ đầy đủ 10 thuộc tính khách hàng trên cả tài khoản `WP_User` (`_jn_*` user meta) và kho hồ sơ khách vãng lai (`jn_guest_crm_profiles`):
     1. `Họ tên` (`full_name`)
     2. `SĐT / Zalo` (`phone` chuẩn hóa E.164 `+84…`)
     3. `Email` (`email`)
     4. `Loại khách` (`ca_nhan`: `Cá nhân` | `tho_hoc_vien`: `Thợ – Học viên` | `chu_co_so`: `Chủ cơ sở`)
     5. `Tên cơ sở` (`business_name`)
     6. `Tỉnh / Thành` (`province_city`)
     7. `Nguồn` (`form`: `Form đặt lịch` | `utm`: `Chiến dịch UTM` | `gioi_thieu`: `Giới thiệu` | `checkout`: `Đơn hàng / Cửa hàng` | `walk_in`: `Điện thoại / Trực tiếp` kèm `source_detail`)
     8. `Tags` (`tags`)
     9. `Ghi chú nội bộ` (`internal_notes` — dòng thời gian chỉ dành cho nhân sự, tuyệt đối không lộ ra giao diện khách)
     10. `Nhật ký đồng ý bảo mật` (`consent_log` — chỉ đọc, bất biến)
   - Đồng bộ tự động 2 chiều với các trường thanh toán của WooCommerce (`billing_first_name`, `billing_phone`, `billing_email`, `billing_company`, `billing_city`, `billing_state`) để báo cáo **WooCommerce Customers / Analytics** luôn nhất quán.
2. **Tự Động Gắn Lịch Hẹn & Đơn Hàng Khách Vãng Lai Khi Đăng Ký Hoặc Đăng Nhập (`Guest-to-Account Linking`):**
   - Khi khách hàng đăng ký tài khoản mới (`user_register`, `woocommerce_created_customer`) hoặc đăng nhập (`wp_login`) với cùng địa chỉ email đã xác thực, toàn bộ lịch hẹn vãng lai (`jn_booking` có `_jn_customer_user_id = 0`), đơn hàng WooCommerce HPOS vãng lai (`WC_Order` có `customer_id = 0`) và hồ sơ CRM vãng lai (`jn_guest_crm_profiles`) được tự động gắn kết và hợp nhất vào tài khoản `WP_User`.
3. **Màn Hình Quản Trị `"Khách hàng"` Hợp Nhất (`ADR-008` — `JN_Customer_Admin`):**
   - Menu cấp cao **"Khách hàng"** (`admin.php?page=jn-customers`) hợp nhất khách hàng từ cả 3 nguồn: lịch hẹn (`jn_booking`), đơn hàng (`WC_Order` HPOS) và đăng ký tài khoản (`WP_User`).
   - Bảng danh sách 9 cột: `Khách hàng`, `SĐT / Zalo`, `Email`, `Loại khách`, `Lịch hẹn`, `Đơn hàng`, `Tổng chi tiêu`, `Lịch hẹn gần nhất`, `Tags`.
   - Tìm kiếm theo tên, email, tên cơ sở hoặc **số điện thoại ở bất kỳ định dạng nào** (`0908234567`, `+84908234567`, `84908234567`, `0908 234 567`, `(+84) 908.234.567`).
   - Bộ lọc theo `Loại khách`, `Tag` và ô tích `"Có lịch chờ xác nhận"` (`has_pending_booking`).
   - Trang **Chi tiết khách hàng** (`admin.php?page=jn-customers&action=view&customer_key=...`): chỉnh sửa hồ sơ CRM, biểu mẫu **"Tạo lịch hẹn"** nhanh cho yêu cầu qua điện thoại/trực tiếp (`Walk-in`), lịch sử lịch hẹn, lịch sử đơn hàng HPOS kèm liên kết, dòng thời gian ghi chú nội bộ, và nhật ký đồng ý bảo mật (chỉ đọc).
4. **Cảnh Báo Trùng Lặp SĐT / Email (`Duplicate Warning`):**
   - Tự động phát hiện khi SĐT (`+84…` / `0…`) hoặc Email trùng với một hồ sơ khách hàng khác, ghi nhật ký kiểm toán vào `jn_customer_duplicate_log` và hiển thị huy hiệu cảnh báo trên danh sách + hộp cảnh báo chi tiết trên hồ sơ khách hàng (không tự động gộp theo đúng yêu cầu phạm vi).
5. **Xuất Dữ Liệu CSV Chuẩn Tiếng Việt Cho Excel (`JN_CSV_Exporter`):**
   - Xuất CSV danh sách Khách hàng (`jn_export_customers_csv`) và Lịch hẹn (`jn_export_bookings_csv`) dành cho các vai trò được cấp quyền (`export_jn_data`), bảo vệ bằng `wp_nonce`, truyền trực tiếp qua luồng (`php://output`), mở đầu bằng **3 byte UTF-8 BOM (`\xEF\xBB\xBF`)** để Microsoft Excel hiển thị tiếng Việt có dấu chính xác 100%, kèm cơ chế chống thực thi công thức độc hại (`CSV Formula Injection`).
6. **Tài Khoản Nhân Sự Theo Đặc Quyền Tối Thiểu & Bắt Buộc 2FA (`docs/jn/roles.md`):**
   - Tài liệu hóa ma trận phân quyền tại `docs/jn/roles.md` và khởi tạo 4 vai trò nhân sự: `Quản trị` (`administrator`), `Quản lý cửa hàng` (`shop_manager`), `Trợ lý lịch hẹn` (`jn_booking_assistant`), `Biên tập nội dung` (`editor`) với tài khoản nhân sự giữ chỗ (`quan_ly_cua_hang`, `tro_ly_lich_hen`, `bien_tap_noi_dung` — `UNK-14`).
   - Cài đặt và kích hoạt plugin **Two Factor (`two-factor` `v0.17.0`)**, bắt buộc xác thực 2 lớp (Email OTP, TOTP, Backup Codes; gỡ bỏ hoàn toàn `Two_Factor_Dummy`) đối với cả 4 vai trò nhân sự.
   - Chặn hoàn toàn khách hàng (`customer` / `subscriber`) khỏi thanh Admin Bar và tự động chuyển hướng mọi truy cập `/wp-admin/` của khách hàng về `/tai-khoan/`.
7. **Quyền Riêng Tư & Chính Sách Lưu Trữ (`JN_Booking_Privacy` & `ADR-009`):**
   - Công cụ Xuất dữ liệu cá nhân (`Personal Data Exporter`) và Xóa dữ liệu cá nhân (`Personal Data Eraser`) bao gồm đầy đủ nhóm dữ liệu **Hồ sơ Khách hàng CRM (`jn-customer-crm`)** và **Lịch hẹn (`jn-bookings`)**.
   - Bổ sung cài đặt lưu trữ lịch hẹn khách vãng lai đã hủy (`retention_cancelled_guest_enabled`) trong `Lịch hẹn → Cài đặt`: **mặc định tắt (`'no'`)** và được ghi nhận vào nhật ký quyết định của Chủ sở hữu (`jn_owner_decision_log`, `ADR-009`).
8. **Kết Quả Kiểm Thử Tự Động & Bằng Chứng Trực Quan:**
   - **Kiểm thử tự động Backend Step 6 (`scripts/test-step-6-customers.php`):** Đạt tuyệt đối **`37 / 37 TESTS PASSED` (`0` failed)**.
   - **Kiểm thử hồi quy Step 5 (`scripts/test-step-5-booking.php`):** Đạt tuyệt đối **`45 / 45 TESTS PASSED` (`0` failed)**.
   - **Kiểm thử E2E Trình duyệt & Ảnh chụp màn hình (`scripts/verify-step-6.mjs` & `docs/jn/step-6-verification.json`):** Đã chụp và xác minh `docs/jn/step-6-customers-list.png`, `docs/jn/step-6-customers-filters.png`, `docs/jn/step-6-customer-detail.png`, `docs/jn/step-6-staff-login-2fa.png`, sau đó dọn sạch dữ liệu kiểm thử tạm và xuất bản sao lưu cơ sở dữ liệu tại `backups/jn-step-6.sql.gz`.

---

## 2h. Kết Quả Nghiệm Thu JN STEP 7

1. **Làm Sạch HTML, Headers & Phiên Bản Tài Nguyên (`JN_Hardening_HTML_Headers` & `custom.ini`):**
   - Gỡ bỏ hoàn toàn các thẻ `<meta name="generator">` (WordPress Core, WooCommerce, Plugins) trên HTML, RSS/Atom Feeds và trường `generator: "GlotPress/..."` trong khối JSON dịch thuật nội tuyến.
   - Thay thế mọi tham số `?ver=...` làm lộ phiên bản WordPress/Theme/Plugin trên CSS, JS và Script Modules (`wp_script_attributes`) bằng hàm băm nội dung SHA-256 10 ký tự (`?v=<10-hex-hash>`) có bộ nhớ đệm tĩnh trong suốt vòng đời request.
   - Gỡ bỏ hoàn toàn RSD (`EditURI`), `wlwmanifest`, `shortlink` (cả `<head>` và HTTP `Link` header), REST API discovery (`<head>` và HTTP `Link` header), oEmbed discovery + `wp-embed`, và toàn bộ script/style `wp-emoji` + DNS prefetch `s.w.org`.
   - Loại bỏ triệt để các HTTP header tiết lộ nền tảng: `X-Powered-By` (`expose_php = Off` trong `docker/php/custom.ini` + `Header always unset`), `X-Pingback`, `X-Redirect-By`.
   - Bộ lọc Output Buffer (`JN_Hardening_HTML_Headers::scrub_public_html`) xóa sạch các bình luận HTML tiết lộ WordPress/Theme/Plugin/Cache, xóa chú thích `/*# sourceURL=... */` trong các thẻ `<style>` nội tuyến, lọc sạch `wpVersion` / `wcVersion` / `wpLoginUrl` trong payload `wcSettings` trên trang Giỏ hàng & Thanh toán, và gỡ các class trên `<body>` làm lộ tên theme (`blocksy`, `jannie`, `wp-theme-*`, `wp-child-theme-*`).
2. **Khóa Chặt Đường Dẫn, Endpoints & Nhúng Nội Tuyến Core Block Styles (`JN_Hardening_Endpoints` & `.htaccess`):**
   - Giữ nguyên tiền tố REST API `/api` từ Step 1; chặn truy cập trực tiếp `/wp-json/` (`404`); chặn người dùng ẩn danh liệt kê tài khoản qua cả `/api/wp/v2/users`, `/api/wp/v2/users/<id>` và `/?rest_route=/wp/v2/users` (trả về HTTP `404` kèm thông báo tiếng Việt, trong khi Block Editor của Quản trị viên/Biên tập viên vẫn hoạt động bình thường).
   - Tắt hoàn toàn XML-RPC (`xmlrpc_enabled = false`, gỡ `pingback.*`) và chặn `xmlrpc.php`, `wp-trackback.php`, `wp-links-opml.php`, `wp-cron.php`, `wp-mail.php`, `wp-Activate.php`, `wp-signup.php` ở cấp máy chủ với HTTP `404`.
   - Chặn triệt để việc dò quét tác giả qua `?author=N`, `?author_name=...` và `/author/*` (trả về trực tiếp trang `404` đồng nhất `70,081 bytes`, tuyệt đối không chuyển hướng `301` làm lộ slug tài khoản); gỡ `author_name` / `author_url` khỏi dữ liệu oEmbed; tắt Core Sitemap (`/wp-sitemap.xml` $\rightarrow$ `404` để chuẩn bị cho Sitemap của SEO plugin ở Step 8).
   - Chặn ở cấp máy chủ (`.htaccess` & `docker/nginx/jn-hardening.conf`) với mã `404` đối với `readme.html`, `license.txt`, `wp-config-sample.php`, `wp-config.php`, `wp-admin/install.php`, `wp-admin/upgrade.php`, `/wp-content/*`, các tệp ẩn `.git` / `.env` và các tệp `.log` / `.sql`; tắt liệt kê thư mục (`Options -Indexes`).
   - Khởi tạo `wordpress/media/.htaccess` chặn thực thi mọi kịch bản PHP/CGI (`*.php`, `*.phtml`, `*.phar`, `*.inc`, `*.pl`, `*.py`, `*.cgi`, `*.sh`) trong thư mục tải lên `/media/`.
   - Thay thế mọi tham chiếu `admin-ajax.php` trên giao diện khách bằng bí danh `/api/ajax`; bật tải riêng và nhúng nội tuyến (`styles_inline_size_limit = 65536`) toàn bộ 11 core block styles nhỏ trên các trang nội dung:
     - **Đo lường kích thước HTML trên `/coaching/`:** Trước Step 7: `137,813 B` raw (`27,431 B` gzipped, 4 file CSS ngoài trong `/wp-includes/`) $\rightarrow$ Sau Step 7: **`135,030 B` raw (`26,690 B` gzipped, 11 thẻ `<style>` nội tuyến, `0` request `/wp-includes/`)**, tức **nhỏ hơn `-2,783 B` raw (`-2.02%`) và `-741 B` gzipped (`-2.70%`)** nhờ loại bỏ thẻ liên kết dư thừa, bình luận HTML và `sourceURL`.
3. **Xóa Dấu Vết Thương Hiệu Mặc Định & Đồng Bộ Trải Nghiệm Tiếng Việt (`JN_Hardening_Branding_Auth`):**
   - Chặn hoàn toàn biểu tượng `w-logo-blue.png` của WordPress; cấu hình `/favicon.ico` trả về trực tiếp biểu tượng monogram SVG thương hiệu (`wordpress/app/themes/jannie/assets/images/favicon.svg`, `200 OK`, `image/svg+xml`).
   - Thiết kế lại toàn diện màn hình đăng nhập `/<WP_LOGIN_SLUG>` theo bảng màu Luxury Minimalist (`#FAF8F5`, `#1F1E1D`, `#B6654A`, font `Playfair Display` & `Geist`), logo dẫn về trang chủ, ẩn bộ chuyển ngôn ngữ và liên kết `"Go to..."`.
   - Ép buộc 100% thư điện tử gửi đi từ `"Jannie Nguyen Academy <contact@jannienguyen.vn>"` và Việt hóa toàn bộ email hệ thống (khôi phục mật khẩu, đổi mật khẩu, đổi email, tạo tài khoản) — không bao giờ xuất hiện `"WordPress <wordpress@...>"`.
   - Xây dựng pattern `jannie/error-404` (`wordpress/app/themes/jannie/patterns/error-404.php`) và template `wordpress/app/themes/jannie/404.php` mang thông điệp tiếng Việt trang nhã kèm nút quay về Trang chủ và Khám phá Coaching; tắt toàn bộ RSS/Atom/RDF Feeds với mã `404` theo `ADR-010`.
4. **Gia Cố Bảo Mật Đăng Nhập & HTTP Security Headers:**
   - Tích hợp bộ giới hạn tần suất đăng nhập sai (`Rate-limit failed logins`) trực tiếp trong `JN_Hardening_Branding_Auth`: khóa tạm thời **15 phút** sau **5 lần thử sai liên tiếp** trong cửa sổ 15 phút (tính theo khóa băm `hash_hmac('sha256', ...)` của địa chỉ IP hoặc tên đăng nhập/email), áp dụng đồng thời cho cả cổng đăng nhập quản trị `/<WP_LOGIN_SLUG>` (trả về HTTP `429`) và biểu mẫu WooCommerce `/tai-khoan/`.
   - Giới hạn tính năng Application Passwords chỉ dành cho Quản trị viên (`manage_options`); khóa cứng tùy chọn `pre_option_users_can_register = 0`.
   - Thiết lập đầy đủ 6 HTTP Security Headers chuẩn Production trên mọi phản hồi: `Strict-Transport-Security`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(self)` và `Content-Security-Policy-Report-Only`.
5. **Bí Danh Đường Dẫn Tài Nguyên Tĩnh (`Task E — ADR-010`):**
   - Ánh xạ toàn bộ tài nguyên tĩnh sang các đường dẫn trung tính không mang dấu vết WordPress thông qua bộ lọc URL + Output Buffer kết hợp quy tắc rewrite trong `.htaccess` và `docker/nginx/jn-hardening.conf`:
     - `/static/core/` $\rightarrow$ `wp-includes/`
     - `/static/core-admin/{css,js,images}/` $\rightarrow$ `wp-admin/{css,js,images}/`
     - `/static/base/` $\rightarrow$ `app/themes/blocksy/`
     - `/static/ui/` $\rightarrow$ `app/themes/jannie/`
     - `/static/shop/` $\rightarrow$ `app/modules/woocommerce/`
     - `/static/academy/` $\rightarrow$ `app/modules/jn-core/`
     - `/static/ext/` $\rightarrow$ `app/modules/`
   - Đổi tiền tố handle CSS/JS giao diện khách của child theme từ `jannie-*` sang `jn-*` để không lộ slug theme trên thuộc tính `id` của thẻ `<link>` và `<script>`.
6. **Báo Cáo Quét Dấu Vết Tự Động & Kiểm Thử Hồi Quy Toàn Diện:**
   - **Trình quét dấu vết (`node scripts/jn-fingerprint-scan.mjs` $\rightarrow$ `docs/jn/fingerprint-report.md`):** Quét tự động 15 trang công khai (bao gồm cả 1 sản phẩm thật đang mở bán, Giỏ hàng, Thanh toán có sản phẩm trong giỏ, Tài khoản, 404, Feed, `robots.txt`, `sitemap.xml`) và 16 đường dẫn thăm dò mặc định $\rightarrow$ đạt tuyệt đối **`31 / 31 PASS` (`0 FAIL`)**, kèm bảng giải trình chi tiết các dấu vết CSS/JS nội bộ được giữ lại có chủ đích (`Accepted Residuals`).
   - **Kiểm thử tự động Backend:**
     - `scripts/test-step-7-hardening.php`: **`54 / 54 TESTS PASSED` (`0` failed)**.
     - `scripts/test-step-6-customers.php`: **`37 / 37 TESTS PASSED` (`0` failed)**.
     - `scripts/test-step-5-booking.php`: **`45 / 45 TESTS PASSED` (`0` failed)**.
   - **Kiểm thử E2E Trình duyệt & Block Editor Smoke Test (`node scripts/verify-step-7.mjs` $\rightarrow$ `docs/jn/step-7-verification.json`):** Đạt **5/5 kịch bản PASS** (trang 404 desktop/mobile, trang đăng nhập thương hiệu, khóa `429` sau 5 lần đăng nhập sai, giao diện `/coaching/` sạch `0` lỗi console, và tạo trang mới + chèn pattern + tải ảnh lên trong Block Editor).
   - **Kiểm tra khả năng cập nhật WP-CLI:** `wp plugin update --all --dry-run`, `wp core check-update` và `wp core verify-checksums` đều hoạt động hoàn hảo (`Success`).
   - **Lighthouse Mobile trên `/coaching/` (`docs/jn/step-7-lighthouse-coaching-mobile.json`):** **Performance = `96`**, **Accessibility = `100`**, **Best Practices = `100`** (`0` lỗi console). Đã xuất bản sao lưu cơ sở dữ liệu tại `backups/jn-step-7.sql.gz`.

---

## 2i. Kết Quả Nghiệm Thu JN STEP 8

1. **Cấu Hình Lighthouse CI (`lighthouserc.json` & `scripts/run-step-8-lhci.mjs` — Task 1):**
   - Khởi tạo tệp `lighthouserc.json` tại thư mục gốc workspace bao phủ đầy đủ **11 URL cốt lõi** (6 trang nội dung: `/`, `/coaching/`, `/my-story/`, `/course/`, `/client-success-story/`, `/booking/`; 2 trang cửa hàng/sản phẩm: `/my-favorite-product/`, `/product/so-tay-dinh-huong-su-nghiep/`; 3 trang giao dịch/tài khoản: `/cart/`, `/checkout/`, `/tai-khoan/`) trên cả 2 chế độ **Mobile** (mô phỏng PSI: `rttMs = 150`, `throughputKbps = 1638.4`, `cpuSlowdownMultiplier = 4`) và **Desktop** (`rttMs = 40`, `throughputKbps = 10240`, `cpuSlowdownMultiplier = 1`).
   - Thực thi **3 lần đo độc lập cho mỗi URL × chế độ** (`66` lần đo tổng cộng) và lấy kết quả trung vị (`median`) lưu tại `docs/jn/lighthouse/2026-10-28/`:
     - **6 trang nội dung (Mobile):** Điểm số tuyệt đối **`100 / 100 / 100 / 100`** trên cả 6 trang (`FCP = 787–933 ms <= 1500 ms`, `LCP = 1658–1884 ms <= 2000 ms`, `TBT = 0 ms <= 100 ms`, `CLS = 0–0.0321 <= 0.05`, `TTFB = 9–12 ms`, `JS = 15.3 KB <= 60 KB`, `CSS = 18.3 KB <= 50 KB`, `Fonts = 3 <= 3`, `LCP Image = 15.2 KB <= 150 KB`, `0` tài nguyên WooCommerce, `0` request bên thứ ba).
     - **2 trang cửa hàng & sản phẩm (Mobile):** `/my-favorite-product/` đạt **`99 / 100 / 100 / 100`** (`FCP = 1360 ms`, `LCP = 1960 ms`, `TBT = 0 ms`, `CLS = 0`); `/product/so-tay-dinh-huong-su-nghiep/` đạt **`100 / 100 / 100 / 100`** (`FCP = 800 ms`, `LCP = 1806 ms`, `TBT = 0 ms`, `CLS = 0`).
     - **3 trang giao dịch & tài khoản (Mobile):** `/cart/` đạt **`100 / 100 / 100 / 100`** (`FCP = 1072 ms`, `LCP = 1072 ms`, `TBT = 82 ms`, `CLS = 0`); `/checkout/` đạt **`100 / 100 / 100 / 100`** (`FCP = 1028 ms`, `LCP = 1028 ms`, `TBT = 83 ms`, `CLS = 0`); `/tai-khoan/` đạt **`100 / 100 / 100 / 100`** (`FCP = 926 ms`, `LCP = 1530 ms`, `TBT = 0 ms`, `CLS = 0`).
     - **Toàn bộ 11 trang trên Desktop:** Đạt điểm tuyệt đối **`100 / 100 / 100 / 100`** trên 11/11 URL (`FCP = 209–373 ms`, `LCP = 286–483 ms`, `TBT = 0–5 ms`, `CLS = 0–0.0131`).
     - Tổng kết: **22 / 22 mục tiêu (100%) vượt toàn bộ ngân sách §5**. Bảng đối chiếu Trước/Sau chi tiết và đường dẫn báo cáo được lưu tại `docs/jn/lighthouse/2026-10-28/README.md` và `summary.json`.
2. **Page Cache, Nén Brotli/Gzip & Bộ Nhớ Đệm Trình Duyệt (`wp-super-cache` v3.1.4 — Task 2):**
   - Cài đặt và kích hoạt **WP Super Cache (`v3.1.4`)** theo `ADR-011` (`docs/jn/decisions.md`), bật `WP_CACHE = true` trong `wordpress/wp-config.php`, cấu hình `wordpress/app/wp-cache-config.php` và drop-in `wordpress/app/advanced-cache.php` (bỏ qua ngay lập tức khi chạy từ CLI/WP-CLI/Cron và không bao giờ đóng dấu bình luận HTML chữ ký).
   - Loại trừ nghiêm ngặt các đường dẫn động (`/cart/`, `/checkout/`, `/tai-khoan/`, `order-pay`, `/api/`, `/<WP_LOGIN_SLUG>`) và các cookie phiên (`wordpress_logged_in_`, `woocommerce_items_in_cart`, `woocommerce_cart_hash`, `wp_woocommerce_session_`).
   - Xác minh tham số `?goi=30-phut`, `?goi=60-phut`, `?goi=mentorship-3-thang` và quy trình lấy token HMAC động (`view.js` $\rightarrow$ `/api/jn/v1/booking/token`) trên trang đã lưu cache hoạt động chính xác 100%; đồng thời chỉ cho phép `jannie_refresh_theme_pattern_cache()` chạy trong `is_admin()` giúp giảm TTFB từ `70–190 ms` xuống **`9–15 ms`**.
   - Bật `mod_brotli`, `mod_deflate`, `mod_filter`, `mod_mime` trong `docker/wordpress/Dockerfile` và cấu hình nén Brotli/Gzip + `Cache-Control: public, max-age=31536000, immutable` cho tài sản tĩnh được băm phiên bản (`?v=<hash>`), phông chữ WOFF2 và hình ảnh trong `wordpress/.htaccess` và `docker/nginx/jn-hardening.conf`.
3. **Chế Độ Ăn Kiêng Tài Nguyên Theo Từng Trang (`Asset Diet` — Task 3):**
   - Bật tải riêng từng core block stylesheet theo nhu cầu (`should_load_separate_core_block_assets`, `should_load_block_assets_on_demand`, `wp_should_output_buffer_template_for_enhancement`) với `styles_inline_size_limit = 65536`.
   - Đóng gói và nhúng nội tuyến (`inline`) trực tiếp `jannie-bundle.min.css` (`18.2 KB` raw / `~4.4 KB` gzip) vào `<head>`, đồng thời trì hoãn stylesheet nền của parent theme (`ct-main-styles`) sang sau sự kiện `window.load + 150ms` để đảm bảo **0 tệp CSS chặn hiển thị (`0 render-blocking CSS`)** trên đường dẫn tới hạn.
   - Trên các trang nội dung: **`0` jQuery**, **`0` tài nguyên WooCommerce**, **`0` `dashicons`**, **`0` `comment-reply`**, chỉ nạp duy nhất 2 tệp JS nhỏ có `defer` (`ct-scripts` `6.0 KB` và `jn-booking-form-view` `3.7 KB`, tổng JS nén **`15.3 KB <= 60 KB`**).
   - Trên các trang cửa hàng & tài khoản (`/my-favorite-product/`, `/product/...`, `/cart/`, `/checkout/`, `/tai-khoan/`): nhúng nội tuyến `woocommerce.css`, gỡ bỏ hoàn toàn thư viện kiểm tra mật khẩu nặng nề `zxcvbn` (`~400 KB`), `password-strength-meter`, `wc-password-strength-meter` và `selectWoo`, đồng thời trì hoãn tuần tự các script phụ trợ sau sự kiện `load` giúp giảm TBT trên `/cart/` và `/checkout/` từ `144–148 ms` xuống **`82–83 ms <= 100 ms`** (`0` lỗi console, `Best Practices = 100`).
4. **Tối Ưu Phần Tử LCP & Hệ Thống Ảnh Đáp Ứng AVIF + WebP (Task 4 & Task 5):**
   - Đăng ký bộ kích thước ảnh chuyên biệt trong `wordpress/app/themes/jannie/inc/setup.php`: `jn-hero-400` (`400×500`), `jn-hero-652` (`652×815`), `jn-hero-800` (`800×1000`) cho khung `4:5`; `jn-expert-420` (`420×560`), `jn-expert-640` (`640×853`), `jn-expert-750` (`750×1000`) cho khung `3:4`; `jn-avatar-96` (`96×96`), `jn-avatar-160` (`160×160`) cho ảnh đại diện học viên.
   - Cấu hình `image_editor_output_format` chuyển đổi tự động ảnh tải lên sang định dạng **AVIF** (`quality = 50`) kèm bản dự phòng **WebP** trên đĩa; chạy `scripts/build-step-8.php` tái tạo toàn bộ biến thể ảnh (ảnh Hero `400w` chỉ nặng **`4.7 KB`**, `652w` **`9.3 KB`**, `800w` **`12.8 KB`** — thấp hơn rất xa ngưỡng `150 KB`).
   - Chỉ gắn `<link rel="preload" as="image" type="image/avif" imagesrcset="..." imagesizes="(max-width: 767px) calc(100vw - 40px), (max-width: 1023px) 42vw, 520px" fetchpriority="high">` trên đúng 2 trang có ảnh Hero ở vùng nhìn đầu tiên (`/` và `/coaching/`). Toàn bộ ảnh dưới màn hình đầu tiên (`expert-intro`, `testimonials`) đều có `loading="lazy"`, `decoding="async"`, `width`/`height` tường minh và `sizes` chính xác (`96px` cho avatar).
5. **Tối Ưu Phông Chữ WOFF2 & Triệt Tiêu Độ Lệch Bố Cục (`CLS ≈ 0` — Task 6):**
   - Giữ tổng số tệp phông chữ tải trên mỗi trang **`<= 3` tệp WOFF2** đã cắt tập con Tiếng Việt từ Step 2.
   - Chỉ preload đúng **2 tệp phông chữ chính** (`playfair-display-vi-normal.woff2` và `geist-vi-normal.woff2`) trong `<head>`; trì hoãn tệp phông chữ in nghiêng trang trí (`playfair-display-vi-italic.woff2`) sang sau FCP kết hợp phông dự phòng khớp kích thước (`Playfair Display Fallback`, `Playfair Display Italic Fallback`, `Geist Fallback`) $\rightarrow$ loại bỏ hoàn toàn nút nghẽn mạng cho FCP trong mô hình Lantern của Lighthouse (**Mobile FCP giảm từ `1.66s` xuống `0.79–0.93s`**, **Mobile LCP giảm từ `2.56s` xuống `1.66–1.88s`**, **CLS = `0–0.0321`**).
6. **Speculative Loading & Cổng Chờ Tác Vụ Bên Thứ Ba (Task 7 & Task 8):**
   - Kích hoạt **Core Speculative Loading** của WordPress 7.1 (`<script type="speculationrules">`) ở chế độ `prerender` với mức `eagerness: "moderate"`, đồng thời loại trừ chặt chẽ qua `wp_speculation_rules_href_exclude_paths`: `/*add-to-cart*`, `/cart/*`, `/checkout/*`, `/tai-khoan/*`, `/<WP_LOGIN_SLUG>*`, `/api/*`, `/*logout*`, `/*order-pay*`, `/*_wpnonce*`.
   - Xây dựng kiến trúc **Third-Party Facade** (`jn_get_approved_third_party_widgets()` & `jn_print_third_party_facade()` trong `wordpress/app/mu-plugins/jn-performance.php`): mặc định **`0` script bên thứ ba** (`ANALYTICS_ID = NONE`); bất kỳ tiện ích nào được chủ dự án phê duyệt trong tương lai (GA4, Meta Pixel, Zalo Chat...) đều chỉ được nạp sau tương tác người dùng đầu tiên hoặc sau khi đồng ý cookie, không bao giờ chèn vào `<head>`.
7. **SEO Kỹ Thuật Đạt Chuẩn `SEO = 100` (`The SEO Framework` v5.2.0 — Task 9):**
   - Cài đặt và kích hoạt **The SEO Framework (`autodescription` `v5.2.0`)**, tắt hoàn toàn bình luận HTML chữ ký qua filter `the_seo_framework_indicator = false`.
   - Cấu hình tiêu đề và thẻ `meta description` tiếng Việt chuẩn xác từ nội dung wireframe cho từng trang (không bịa đặt số liệu hay cam kết mới), thẻ `rel="canonical"`, thẻ Open Graph + `og:image` kích thước chuẩn `1200×630` (`jn-placeholder-og-1200x630.webp`).
   - Kích hoạt `/sitemap.xml` (`200 OK`, chỉ chứa các trang công khai và sản phẩm đang mở bán, loại trừ trang `noindex` `/cart/`, `/checkout/`, `/tai-khoan/`, `/chinh-sach-bao-mat/`, `/dieu-khoan-dich-vu/` và 3 gói Coaching `Draft`).
   - Chuẩn bị cả 2 phiên bản `robots.txt`: bản môi trường `local` (`docs/jn/robots.local.txt`) và bản sẵn sàng cho `production` (`docs/jn/robots.production.txt`), phục vụ động qua filter `robots_txt` kèm dòng `Sitemap: .../sitemap.xml` giúp vượt qua 100% bài kiểm tra SEO của Lighthouse trên cả môi trường local và production.
   - Xuất khối dữ liệu có cấu trúc **JSON-LD `@graph`** hợp lệ gồm `Organization` / `ProfessionalService` (`Jannie Nguyen Academy`), `Person` (`Jannie Nguyễn`), `WebSite`, `WebPage` và 3 thực thể `Service` cho 3 gói Coaching (chỉ xuất `offers.price` khi sản phẩm đã công bố giá thật; **tuyệt đối không xuất `Review` hay `AggregateRating`** cho các lời chứng thực tự đăng theo đúng chính sách của Google).
8. **Vệ Sinh Cơ Sở Dữ Liệu, OPcache & Kiểm Thử Hồi Quy Toàn Diện (Task 10):**
   - Ghi nhận quyết định kiến trúc **`ADR-011`** trong `docs/jn/decisions.md` về việc chọn `WP Super Cache` trên môi trường Apache/Nginx và điều kiện bật Redis/Memcached Object Cache khi bàn giao Production.
   - Tích hợp hàm bảo trì tự động `jn_maintain_database_hygiene()`: xóa sạch `100%` transient hết hạn (`expired_transients = 0`), tắt `autoload` cho các option lớn không cần thiết trên mọi request, giữ tổng dung lượng `autoloaded options` ở mức **`24,196 bytes` (`23.6 KB`, thấp hơn rất xa ngưỡng `65 KB`)**, và xác nhận **Zend OPcache** đang hoạt động (`opcache_enabled = true`).
   - **Kiểm thử tự động & Quét dấu vết:**
     - `node scripts/jn-fingerprint-scan.mjs`: **`31 / 31 PASS` (`0 FAIL`)**.
     - `scripts/test-step-8-performance.php`: **`35 / 35 PASS` (`0 FAIL`)**.
     - `scripts/test-step-7-hardening.php`: **`45 / 45 PASS` (`0 FAIL`)**.
     - `scripts/test-step-6-customers.php`: **`37 / 37 PASS` (`0 FAIL`)**.
     - `scripts/test-step-5-booking.php`: **`45 / 45 PASS` (`0 FAIL`)**.
   - Đã xuất bản sao lưu cơ sở dữ liệu tại `backups/jn-step-8.sql.gz`.

---

## 2j. Kết Quả Nghiệm Thu JN STEP 9

1. **Kiểm Toán Chặn Phát Hành & Dọn Sạch Dữ Liệu Thử Nghiệm (`Task 1 — scripts/check-launch-blockers.php`):**
   - Ghi nhận văn bản phê duyệt & miễn trừ tạm thời của chủ dự án (*"oke hãy làm đi ,tôi chưa muốn cung cấp ngay thông tin thật"*, `2026-10-28T08:40:55+07:00`) cho các hạng mục dữ liệu thật `BL-01..BL-07a` (`UNK-01..UNK-14`).
   - Đã cài đặt sẵn `fluent-smtp` (`v2.4.1`) tại `wordpress/app/modules/fluent-smtp` (giữ ở trạng thái `inactive` trên local để Mailpit bắt 100% email kiểm thử, sẵn sàng kích hoạt ngay khi có thông tin SMTP Production `UNK-11`).
   - Xác nhận `BL-07b = RESOLVED`: **`0` sản phẩm DEMO**, **`0` trang mẫu/specimen**, **`0` lịch hẹn kiểm thử**, **`0` đơn hàng kiểm thử** và **`0` tài khoản khách hàng kiểm thử** còn tồn tại trong cơ sở dữ liệu (`docs/jn/launch-blockers-report.json`).
2. **Kiểm Thử Hồi Quy Toàn Diện Trên Môi Trường Local (`Task 2 — scripts/verify-step-9-regression.mjs`):**
   - **Toàn bộ các bộ test Backend & Bảo mật xanh 100%:** `jn-fingerprint-scan.mjs` (**`31/31 PASS`**), `test-step-5-booking.php` (**`45/45 PASS`**), `test-step-6-customers.php` (**`37/37 PASS`**), `test-step-7-hardening.php` (**`54/54 PASS`**), `test-step-8-performance.php` (**`35/35 PASS`**).
   - **`axe-core` WCAG 2.1 / 2.2 AA trên toàn bộ 13 trang (`0` vi phạm):** Quét tự động trên `/`, `/coaching/`, `/my-story/`, `/course/`, `/client-success-story/`, `/my-favorite-product/`, `/booking/`, `/cart/`, `/checkout/` (có sản phẩm trong giỏ), `/tai-khoan/`, `/chinh-sach-bao-mat/`, `/dieu-khoan-dich-vu/` và trang lỗi `404` $\rightarrow$ **`0` vi phạm trên cả 13/13 trang**.
   - **Đối chiếu thị giác 4 độ phân giải (`375px`, `768px`, `1024px`, `1440px`):** `0px` tràn ngang (`horizontalOverflowPx = 0`), chiều cao Header cố định `100px`, nhịp phân đoạn dọc `96px` (`Δ = 0px`). Ảnh chụp lưu tại `docs/jn/step-9-parity-{375,768,1024,1440}.png`.
   - **Điều hướng hoàn toàn bằng bàn phím (`Keyboard-only pass`):** Liên kết bỏ qua điều hướng (`Skip link`), nhấn `Enter` trên nút gói Coaching chọn trước `<select id="package-select">`, toàn bộ `9/9` trường biểu mẫu hiển thị vòng focus `>= 2px`, và phím `Escape` đóng ngăn kéo menu di động `#offcanvas` trả focus về nút `.ct-header-trigger`.
   - **Kiểm thử đa trình duyệt (`Chromium`, `Firefox`, `WebKit` qua Playwright):** Cả 3 trình duyệt đều tải trang, chọn gói Mentorship và gửi hồ sơ đặt lịch E2E thành công với `0` lỗi JavaScript runtime (`docs/jn/step-9-cross-browser-{chromium,firefox,webkit}.png`).
   - **Quét liên kết nội bộ (`92` URL & tài sản tĩnh):** **`0` liên kết gãy (`404/500`)**, **`0` liên kết `href="#"`**.
   - **Trang lỗi 404, lỗi xác thực biểu mẫu & khóa đăng nhập `429`:** Đều trả về đúng mã trạng thái HTTP và thông điệp tiếng Việt chuẩn thiết kế.
   - **Toàn bộ 15 mẫu Email trong Mailpit (`scripts/trigger-step-9-all-emails.php`):** Đã kích hoạt và chụp bằng chứng (`docs/jn/step-9-all-emails-mailpit.png`) cho cả 15 mẫu email (5 mẫu Đặt lịch bao gồm thư đính kèm `.ics` + liên kết `order-pay`, 6 mẫu WooCommerce, 4 mẫu hệ thống & mã OTP 2FA) — 100% tiếng Việt gắn thương hiệu `"Jannie Nguyen Academy <contact@jannienguyen.vn>"`.
3. **Bộ Công Cụ Triển Khai Production & Kịch Bản Chờ Lệnh `"deploy"` (`Task 3 & Task 4`):**
   - Đóng gói hoàn chỉnh tại `deploy/`: `wp-config.production.php` (`WP_ENVIRONMENT_TYPE=production`, tắt debug, `FORCE_SSL_ADMIN`, `DISABLE_WP_CRON`), `.env.production.example`, `generate-salts.sh`, `cron.d-jannie-wordpress`, `apache-litespeed.htaccess`, `nginx-production.conf`, `rsync-exclude.txt`, `db-search-replace.sh` (hỗ trợ `--dry-run` an toàn cho PHP serialization), `rollback.sh`, cùng tài liệu hướng dẫn tiếng Việt `docs/jn/deploy/runbook-trien-khai-va-rollback.md`.
   - Chuẩn bị kịch bản `scripts/run-step-9-psi-and-live-e2e.mjs` sẵn sàng đo PageSpeed Insights API v5 (`3 lần / URL × Mobile & Desktop`), quét dấu vết và chạy E2E trên tên miền thực tế ngay sau khi chủ dự án gõ lệnh `"deploy"`.
4. **Tài Liệu Bàn Giao Vận Hành Tiếng Việt & Báo Cáo Cuối Cùng (`Task 5 & Task 6`):**
   - Hoàn thiện 8 tài liệu hướng dẫn tiếng Việt dành cho người không chuyên kỹ thuật tại `docs/jn/handover/` (`README.md` và `01` đến `07`) kèm 16 ảnh chụp màn hình giao diện thực tế tại `docs/jn/handover/images/`.
   - Xuất báo cáo nghiệm thu tổng thể tại `docs/jn/final-report.md`, bản chụp cơ sở dữ liệu sạch cuối cùng tại `backups/jn-step-9.sql.gz`, commit và gắn thẻ Git `jn-step-9`.

---

## 2k. Kết Quả Nghiệm Thu JN STEP 10 — Kiểm Toán & Tối Ưu SEO/AEO

- **Báo cáo chính:** `docs/jn/seo-aeo/SEO_AEO_Audit_2026-10-28/1. report/bao-cao-kiem-toan-seo-aeo.md` (trước/sau, 13 phát hiện F1–F13, Counter-Evidence/Confidence/Falsification). Bản nháp chờ duyệt: `3. draft/faq-va-khoi-tra-loi-truc-tiep.md`, `3. draft/kien-truc-tu-khoa-va-lo-trinh-noi-dung.md`.
- **Mã nguồn:** `wordpress/app/mu-plugins/jn-performance/class-jn-seo-{brand,meta,robots,sitemap,schema,llms}.php` (mới), `jn-performance.php` v2.1.0, gỡ filter `robots_txt` trùng ở `jn-hardening`, pattern `jannie/faq`, tắt hero archive trên trang shop, theme `jannie` v1.1.0. Quyết định: `ADR-012`, `ADR-013`.
- **Kiểm chứng:** `scripts/test-step-10-seo.php` **77/77**; Step 7 54/54; Step 5 45/45; Step 6 37/37; Step 8 34/35 (FAIL có sẵn, xem báo cáo §5 — đã xử lý sau Step 10, xem mục dưới); `jn-fingerprint-scan.mjs` 31/31; `verify-step-9-regression.mjs` 100% (bằng chứng lưu tại `1. report/regression/`, giữ nguyên bằng chứng Step 9 gốc); LHCI 22/22 PASS — SEO = 100 trên mọi trang nội dung, 66–69 trên 3 trang tiện ích do `is-crawlable` (chủ đích, ADR-013).
- **Giả định chờ chủ dự án xác nhận:** Q2 cho phép toàn bộ bot tìm kiếm/trả lời AI; Q3 giữ `index` cho 2 trang pháp lý. Đổi được trong `JN_SEO_Robots::AI_AGENTS` / `JN_SEO_Sitemap::NOINDEX_SLUGS`.
- **Cấu hình chủ dự án tự đặt khi có dữ kiện:** `wp option update jn_brand_telephone "+84…"` (UNK-04); `wp option update jn_brand_same_as '["https://…"]' --format=json` (UNK-13).
- **Sửa kiểm thử Step 8 (2026-10-28, chủ dự án chọn hướng A):** assertion "zero WooCommerce assets" trên `/coaching/` không còn tìm chuỗi `woocommerce` trong toàn HTML (bắt nhầm selector trong CSS động của Blocksy) mà dùng `jn_step8_find_woocommerce_asset_tags()` — chỉ xét thuộc tính `id`/`src`/`href` của thẻ `<link>`/`<script>`/`<style>`, bao gồm đường dẫn alias `/static/shop/` của Step 7 (trước đây bị bỏ sót, ví dụ `wc-blocks.css`). Thêm assertion đối chứng trên `/cart/` để bảo đảm bộ dò không rỗng. Kết quả: `test-step-8-performance.php` **37/37 PASS**. Không lọc CSS WooCommerce của Blocksy (~3 KB chưa nén) — có thể làm thành task hiệu năng riêng nếu cần.
- **Xuất bản 5 câu hỏi FAQ Nhóm A & nâng điểm AEO (2026-10-28, chủ dự án phê duyệt `/plan`):** cập nhật `patterns/faq.php` với 5 câu hỏi – trả lời Nhóm A (`A1..A5` từ `3. draft/faq-va-khoi-tra-loi-truc-tiep.md`), chèn phân đoạn FAQ lên `/coaching/` và `/booking/`, mở rộng `JN_SEO_Schema::extract_faq_from_blocks()` hỗ trợ cả Synced Pattern (`core/block` có `ref`), và tự động xuất mục `## Câu hỏi thường gặp` trong `/llms.txt` (`JN_SEO_Llms`). Kết quả: điểm GEU trên `/coaching/` tăng từ `65.3` lên **`75.0`**, trên `/booking/` tăng từ `40.9` lên **`57.3`**, xóa bỏ hoàn toàn cảnh báo `JN-AEO-02` (`LOW` giảm từ `12` xuống `11`), và `scripts/test-step-10-seo.php` đạt **`81/81 PASS`**.

---

## 2l. Lớp Chuyển Động Toàn Site (motion-ui, 2026-10-29)

- **Mã nguồn:** `assets/css/motion.css` (mới, nằm cuối bundle inline), `inc/motion.php` (mới, script `#jn-motion` ở footer), bộ token `--jn-motion-*` / `--jn-ease-smooth` trong `tokens.css`, cùng chỉnh sửa tại chỗ trong `base.css` và `block-styles.css`.
- **Hành vi:** (1) tiêu đề section, các cột lưới (chuyên gia, 3 trụ cột, 3 gói, cảm nhận), khối FAQ và form đặt lịch hiện dần khi cuộn tới, lệch nhịp 80 ms; chỉ áp dụng cho nội dung nằm dưới màn hình đầu tiên, nên hero/page-intro (LCP) không bao giờ bị ẩn; (2) header nổi lên (đường kẻ + bóng) khi trang đã cuộn, dùng IntersectionObserver thay cho scroll handler; (3) gạch chân menu chạy bằng `transform: scaleX` thay cho `width` của Blocksy; (4) icon trong nút nhích theo hướng đi khi hover/focus; (5) FAQ bỏ tam giác mặc định, thay bằng dấu + xoay thành ×, câu trả lời trượt xuống khi mở; (6) phản hồi khi nhấn cho nút Đăng ký, nút tài khoản, nút menu và nút gửi form; card chỉ nâng lên khi hover trên thiết bị có chuột.
- **Nguyên tắc:** chỉ animate `transform`/`opacity`; `prefers-reduced-motion` đưa mọi thời lượng/khoảng cách về 0 và bỏ hẳn hiệu ứng cuộn; máy yếu (≤ 2 GB RAM, hoặc ≤ 4 nhân khi không đọc được RAM) dùng cấu hình ngắn `.jn-motion-lite`; không có JS thì không ẩn gì.
- **Công cụ chụp ảnh:** `verify-step-9-regression.mjs` và `run-step-4-lighthouse.mjs` giả lập `prefers-reduced-motion: reduce` để ảnh full-page không bị trống ở các section chưa reveal.
- **Kiểm chứng:** `scripts/test-step-12-motion.php` **33/33** (đã cập nhật sau code review: gạch chân menu tự đủ thuộc tính vì `main.min.css` của Blocksy tải sau first paint, có forced-colors, stagger theo hàng); Step 8 37/37; Step 10 81/81; 11e 20/20. Lighthouse mobile `/` (cache ấm, 3 lần): Performance 100/100/100, LCP 1888 ms (baseline 1884 ms), TBT 0, CLS 0; `/coaching/`: 100, LCP 1.8 s, CLS 0. CLS đo trên trình duyệt khi cuộn hết trang (1440 px và 390 px) = 0, không tràn ngang.
- **Lỗi 500 với URL `//` (có sẵn từ trước, đã sửa 2026-10-29):** với `//`, `///`, `///coaching/`, `//?x=1`, `parse_url()` trả `false`; WPS Hide Login truyền thẳng giá trị này vào hook `wps_hide_login_before_redirect`, và `JN_Hardening_Endpoints::intercept_logged_out_admin_404( array $request )` ném `TypeError`, gây 500 khi trang chưa có cache hoặc khi người dùng đã đăng nhập. Đã sửa bằng cách cho hàm nhận cả `false` (coi là mảng rỗng), không đụng tới plugin bên thứ ba. Giờ các URL này redirect 301 về URL chuẩn, `///wp-admin/` khi chưa đăng nhập vẫn trả 404. `test-step-7-hardening.php` **56/56** (thêm 2 test hồi quy, gồm 1 request HTTP thật không qua cache); 11d 25/25.

## 2m. Đồng Bộ Tiêu Đề Đầu Trang & Điền Lời Dẫn (Step 13, 2026-10-29, chủ dự án duyệt `/plan`, chọn phương án A)

- **Vấn đề đo được (1440 px):** tiêu đề page-intro trải 1248 px nên rớt 1 chữ xuống dòng ("Thực", "Xăm", "Hàng"); ở 375 px vẫn giữ 56 px nên vỡ 4–5 dòng; eyebrow/khoảng cách lệch giữa các trang (eyebrow → tiêu đề 8/12/16 px, tiêu đề → lời dẫn 16/24 px); trang Sản phẩm căn giữa, lời dẫn rộng 1136 px và lệch 56 px so với logo. Gốc rễ: `.jn-container` (`max-width … !important`) đè `max-width` đặt trên cùng phần tử `.jn-page-intro-inner` / `.jn-section-header-centered`; trang shop chạy prefix Blocksy `woo_categories` (article đóng khung 1248 px + vertical spacing 60 px).
- **Hệ thống chung cho hero + mọi page-intro (`block-styles.css` mục 9, 16):** eyebrow `label-md` màu `muted-stone` + chấm champagne; tiêu đề `ink` với phần nghiêng `champagne-text-lg` luôn xuống dòng riêng (`display: block`), `text-wrap: balance`, tối đa `--jn-measure-title` (15em); lời dẫn `muted-stone` weight 400, tối đa `--jn-measure-lead` (40rem ≈ 70 ký tự), `text-wrap: pretty`; nhịp 16 px / 24 px; padding page-intro 64 px (40 px trên mobile); dưới 1024 px tiêu đề page-intro về `headline-lg` như hero. H2 trong trang: `text-wrap: balance`; mô tả: `pretty`.
- **Trang Sản phẩm (phương án A):** header chuyển sang page-intro căn trái như 6 trang còn lại; `woocommerce.css` gán `--has-wide/--has-boxed` giống `single_page`, bỏ vertical spacing và cho lưới sản phẩm `width: 100%` để thẳng hàng tiêu đề (x = 96 px ở 1440, 20 px ở mobile).
- **Lời dẫn đã điền (chỉ dùng dữ kiện có sẵn trong `expert-intro.php`, chỉ số hero và mô tả SEO — không thêm số liệu, ngày tháng, học phí, lời chứng thực hay cam kết quy trình):** `/`, `/my-story/`, `/course/`, `/client-success-story/`. Nội dung nằm ở một nơi `scripts/lib/jn-page-copy.php`, dùng chung cho seed `build-step-3.php`, migration `scripts/build-step-13-page-headers.php` (idempotent, chạy lại → `SKIP`) và test; `setup-step-4-woocommerce.php` seed header trang Sản phẩm mới. Sau code review: bỏ câu "mỗi hồ sơ chỉ được công bố khi đã xác minh…" ở `/client-success-story/` (3 lời chứng thực ID 91–93 chưa xác minh, chỉ được miễn trừ — UNK-03 / BL-02), viết lại lời dẫn `/my-story/` sát dữ kiện; tiêu đề 404 đổi thành "Trang Bạn Tìm Kiếm Đã Chuyển Đi Hoặc *Không Tồn Tại*" để phần nghiêng nằm cuối.
- **Vẫn chờ chủ dự án (không tự viết):** các khung "Chờ cập nhật" — cột mốc sự nghiệp (`/my-story/`), danh mục khóa học/học phí (`/course/`, UNK-08), hồ sơ case study đã xác minh (`/client-success-story/`, UNK-03) — và 6 đoạn văn bản pháp lý (`/chinh-sach-bao-mat/`, `/dieu-khoan-dich-vu/`, UNK-09).
- **Kiểm chứng:** `scripts/test-step-13-header-typography.php` **41/41** (gồm: lời dẫn trong DB khớp `jn-page-copy.php`, mọi H1 đầu trang kết thúc bằng phần nghiêng); `scripts/verify-step-13-headers.mjs` **50/50** (10 trang gồm 2 trang pháp lý và 404 × 320/375/768/1024/1440: không chữ mồ côi, phần nghiêng xuống dòng riêng và nằm cuối, nhịp 16/24 px, tiêu đề thẳng hàng logo, màu đúng token, không tràn ngang; báo cáo `docs/jn/step-13-verification.json`, ảnh `docs/jn/step-13-headers/`); axe-core WCAG 2.2 AA trên 7 trang × 2 viewport: **0 vi phạm**; hồi quy 7 (hardening) 56/56, 8 (performance) 37/37, 10 (SEO) 81/81, 11e 20/20, 12 (motion) 33/33.

## 2n. Mở Site Local Qua Cloudflare Quick Tunnel (2026-10-29, chủ dự án duyệt `/plan`)

- **Lỗi:** mở qua `https://*.trycloudflare.com`, chỉ trang chủ trả 200; 6 mục menu trỏ `http://localhost:8080/…`, `redirect_canonical` đẩy sang `https://<tunnel>:8080/…` (lấy cổng từ `WP_HOME`), CSS/JS/ảnh/canonical/OG cũng là `localhost` → khách ngoài thấy trang vỡ, bấm menu không vào được.
- **Sửa (xem ADR-017):** `wordpress/wp-config.php` thêm `jn_tunnel_host()` — Host dạng `<một-nhãn>.trycloudflare.com` + `CF-Connecting-IP` là IP hợp lệ + môi trường `local`/`development` thì `WP_HOME`/`WP_SITEURL` = `https://<host>`, `HTTPS=on`, `REMOTE_ADDR` = IP thật của khách (để giới hạn đăng nhập/đặt lịch theo IP không khoá cả nhóm người xem); mu-plugin mới `mu-plugins/jn-dev-tunnel.php` (chỉ hoạt động khi có `JN_TUNNEL_HOST`) đổi URL `localhost` lưu cứng trong DB (5 bài/trang, meta ảnh OG, theme mods, cài đặt SEO, Amelia…) khi trả trang — dạng thường, JSON-escaped và URL-encoded — cùng header `Location`, trừ màn hình wp-admin và REST `context=edit` (dữ liệu sẽ được lưu ngược); transient sitemap luôn lưu URL local để hai host không lẫn nhau.
- **Kiểm chứng:** `scripts/test-dev-tunnel.php` **35/35** (RED trước, gồm các case Host giả mạo, Host có xuống dòng, IP CF sai định dạng, môi trường staging, wp-admin/REST edit); code review độc lập: APPROVE, 0 CRITICAL/HIGH, đã xử lý 2 MEDIUM + 2 LOW; qua tunnel: 7/7 trang menu 200 không redirect, `0` chuỗi `localhost` trên 7 trang + `/sitemap.xml` + `/llms.txt` + `/robots.txt`, `/shop/` → `https://<tunnel>/my-favorite-product/`; Chrome headless desktop 1440 & mobile 390: mọi request về host tunnel, `0` request lỗi, `0` lỗi console, `0` ảnh vỡ; `localhost:8080` không đổi (`0` chuỗi `trycloudflare`, Host giả không có header CF vẫn ra canonical `localhost`); hồi quy 5 (booking) 46/46, 7 (hardening) 56/56, 10 (SEO) 81/81, 11d 25/25, 11e 20/20.
- **Lưu ý vận hành:** quick tunnel đổi domain mỗi lần chạy `cloudflared` — không cần sửa gì. Sau khi đổi domain nên xoá `wordpress/app/cache/supercache/<domain-cũ>/`. Chỉ dùng tunnel để **xem**; không sửa nội dung qua tunnel (trình soạn thảo có thể lưu URL tunnel vào bài viết).

## 2o. Step 14 — Kiểm Toán Lại & Tối Ưu SEO/AEO (2026-10-30, chủ dự án duyệt `/plan`)

- **Báo cáo:** `docs/jn/seo-aeo/SEO_AEO_Audit_2026-10-30/1. report/bao-cao-kiem-toan-seo-aeo.md` (skill `sunext-semrush-ahrefs-os` + checklist `ecc:seo`). Bản nháp chờ duyệt: `3. draft/de-xuat-kien-truc-noi-dung-va-chong-trung-lap.md`, `ban-do-301-jannieedu.md`, `nguon-backlink-va-trich-dan.md`. Quyết định: ADR-020.
- **Sửa:** ký tự `g` lạc ở `app/advanced-cache.php` làm toàn site 500 (thay đổi chưa commit); page cache trả bản cache 200 cho `/coaching`, `/COACHING/` và **trả HTML trang chủ cho `//coaching/`** → URL không chuẩn đi vào nhánh bypass để WordPress 301; 5/9 trang index hiển thị khung `[CẦN NỘI DUNG]` → module mới `JN_SEO_Placeholders` ẩn với khách (kể cả excerpt tìm kiếm), người có quyền `edit_pages` vẫn thấy, tự hiện khi điền nội dung (chủ dự án chọn "Ẩn với khách"); `Organization.alternateName` liệt kê đủ tên đang dùng; `seo-audit-local.py` thêm kiểm tra `JN-URL-01`.
- **Kiểm chứng:** `scripts/test-step-14-seo-aeo.php` **18/18** (RED trước); 15 bộ PHP cũ **815/815**; fingerprint 31/31; header Step 13 50/50; hồi quy Step 9 100%; LHCI **22/22** (`docs/jn/lighthouse/step-14/`); audit `0/0/3/11` (2 MEDIUM mới = trang pháp lý mỏng sau khi ẩn khung, tự hết khi có UNK-09), biến thể URL 24/24; review độc lập 0 CRITICAL, đã xử lý 2 HIGH + 5 MEDIUM.
- **Chờ chủ dự án:** chọn domain production; **bảo mật WordPress cũ ở `jannieedu.com`** (vẫn chạy, TLS hết hạn 2026-08-15, nghi từng bị xâm nhập → đổi mật khẩu, gỡ site cũ); duyệt tái cấu trúc trang chủ thành hub (`/` ↔ `/coaching/` trùng ý định 0.846). Khi deploy: purge page cache.

## 2p. Hero Trang Chủ Mới — Khung Ảnh Vòm & Polaroid Đổi Ảnh (2026-10-30, chủ dự án gửi bản HTML mẫu & duyệt `/plan`)

- **Phạm vi:** chỉ trang chủ (`/`); `/coaching/` giữ hero editorial. Quyết định và các điểm khác bản mẫu có chủ đích: ADR-021.
- **Thêm:** pattern `jannie/hero-home` — cột chữ theo hero chung, giống ảnh chủ dự án gửi (H1 hai dòng "Khám Phá Sự Cân Bằng &" / *"Vẻ Đẹp Đích Thực"*, thẻ chỉ số `20+ · 1:1 Cố Vấn Độc Bản · 98% Tỷ Lệ Tối Ưu Hóa`, nút đen "Khám Phá Chương Trình Coaching ↗" → `/coaching/` và nút be "Đặt Lịch Tư Vấn 1:1 ↓" → `#booking-form`; lưới 2:1); block động `jn/hero-portrait` trong jn-core (khung vòm, caption, polaroid bấm/Space để đổi sang ảnh `jn-legacy-banner-home`, thuần CSS, chọn ảnh + chữ trong editor); `assets/css/hero-home.css` (token, chỉ animate `transform`/`opacity`, dừng khi reduced motion / `.jn-motion-lite`).
- **Sửa kèm:** `jn-performance.php` — preload + `eager`/`fetchpriority` + `sizes` của LCP đi theo ảnh chính của block (`jn_hero_portrait_lcp_id()`, `jn_hero_image_sizes()`); `build-step-3.php` seed hero mới (lời dẫn vẫn lấy từ `jn-page-copy.php`); migration `scripts/build-home-hero.php` thay hero trong nội dung trang chủ đã lưu (revision để hoàn tác; backup `backups/pre-home-hero-2026-10-30.sql.gz`). **Deploy:** chạy `build-home-hero.php` với `JN_ALLOW_DESTRUCTIVE=1`, rồi purge page cache.
- **Sửa cùng ngày theo ảnh chủ dự án gửi:** cột chữ bỏ kiểu riêng của bản mẫu (H1 ba dòng 80 px, thanh chỉ số kẻ chỉ, nút cao 56 px, dòng gợi ý cuộn) và nội dung trở về như ảnh ("giống hệt hình"); khung ảnh vòm giữ nguyên. `build-home-hero.php` nay thay cả hero-home cũ hơn và `SKIP` khi đã khớp pattern. Sau đó, theo yêu cầu ảnh to hơn: cột ảnh tràn sang lề phải container (khung 360 → 448 px ở 1440, polaroid cách mép 32 px; không tràn dưới 1360 px) và polaroid luôn xem trước ảnh sẽ đổi sang.
- **Kiểm chứng:** `scripts/test-home-hero.php` **72/72** (lần đầu RED 27 fail trước khi code); `scripts/verify-home-hero.mjs` **9/9** (320–1920 px không tràn ngang, LCP nằm trong hero, Tab + Space + chuột đổi ảnh, reduced motion; ảnh `docs/jn/home-hero/`); axe-core WCAG 2.2 AA trên `/` ở 375/1440, trước và sau khi đổi ảnh: **0 vi phạm**; header Step 13 **50/50**; Lighthouse `/` mobile & desktop **100/100/100/100** (mobile LCP 1.7 s, CLS 0 — `docs/jn/lighthouse/home-hero/`); hồi quy 8 (performance) 39/39, 10 (SEO) 95/95, 11e 20/20, 12 (motion) 33/33, 13 41/41, 14 18/18, ảnh 68/68, overlay cleanup 24/24 (nay chỉ kiểm tra icon ∞ / hộp kính trên `/coaching/`). Review độc lập: 0 CRITICAL, đã xử lý 1 HIGH (LCP đi theo `primaryId` của block) + 2 MEDIUM (tên checkbox chứa chữ hiển thị — WCAG 2.5.3; màu chữ nhỏ trên vùng sáng ấm) + 1 LOW (ảnh đang ẩn ra khỏi cây truy cập).
- **Lưu ý:** `scripts/verify-step-3.php` báo các block `woocommerce/*` (Cart/Checkout/Shop, có từ Step 4) — không liên quan hero, có từ trước thay đổi này.


## 2q. Step 15 — Nội Dung jannieedu.com Cũ Cho Sản Phẩm, Khóa Học & Cảm Nhận (2026-10-30, chủ dự án duyệt `/plan`, mặc định D1–D9)

- **Nguồn:** 3 bản lưu Wayback chủ dự án gửi (`/my-favorite-product/` 2025-01-19, `/course/` 2025-01-23, `/client-success-story/` 2025-02-07) + giá ở JSON-LD `/san-pham/*` (05/2024). Bản trích, sha256 ảnh, ảnh chụp và báo cáo: `docs/jn/legacy-content/`. Quyết định: ADR-022.
- **Nội dung (nguyên văn, một nguồn `scripts/lib/jn-legacy-content.json`):** 6 sản phẩm WooCommerce (slug cũ, `/san-pham/<slug>/`, giá 2024, Xà bông thảo dược "Liên hệ", nút "Mua Ngay" → trang sản phẩm); 26 khóa học (Phun Xăm 15 / Salon 10 / Spa 1, cụm ảnh xen trái/phải, "Đặt Lịch Tư Vấn" → `#booking-form`); trang Cảm nhận đủ 8 khối của trang cũ (giới thiệu, 3 logo báo, đào tạo chất lượng, 3 cảm nhận video, 6 nhóm khóa học, giá trị, quy trình 4 bước, chia sẻ cảm nhận). Chỉ đổi hoa/thường tiêu đề + 9 lỗi chính tả đã duyệt; 2 mô tả chép trùng của trang cũ giữ nguyên, gắn cờ.
- **Code:** `jn_testimonial` thêm `jn_headline`, `jn_video_url`, `jn_video_poster_id`; `jn_rating = 0` = không hiện sao (mặc định mới); block `jn/video` (facade: ảnh bìa tự host, `youtube-nocookie` chỉ tải khi bấm, `view.js` 1 KB); CSP `frame-src https://www.youtube-nocookie.com` (5 file); `woocommerce_empty_price_html` → "Liên hệ"; `assets/css/legacy-pages.css` (vào bundle inline + editor); pattern `jannie/video-testimonials`; `product_base` → `san-pham`; mô tả SEO 3 trang theo câu chữ cũ; `jn-page-copy.php` lấy lời dẫn `/course/`, `/client-success-story/` từ nguồn cũ. 3 cảm nhận wireframe (91–93) chuyển Nháp — `/` và `/coaching/` giờ hiện 3 học viên thật, không sao.
- **Ảnh:** 19 ảnh gốc tải một lần từ WordPress cũ (Wayback không lưu ảnh), 3 ảnh bìa YouTube; chỉ thu nhỏ (1×), kiểm tra sha256 khi import (`jn_placeholder_load_original()`), WebP + AVIF; ID 10606–10627.
- **Sự cố (đã khắc phục, chi tiết ADR-022):** (1) `wc_get_products(slug)` bỏ qua slug → ghi đè 3 gói Coaching → khôi phục DB từ `backups/pre-step-15-2026-10-30.sql.gz`, sửa truy vấn + chốt chặn. (2) `check-launch-blockers.php` xoá mọi sản phẩm không phải gói Coaching → xoá 6 sản phẩm mới → sửa quy tắc, tạo lại sản phẩm (ID 11288–11293).
- **Kiểm chứng:** `scripts/test-step-15-legacy-content.php` **111/111** (RED trước: fail ở meta mới và `youtube_id()`); `scripts/verify-step-15-legacy-pages.mjs` **28/28** (178 chuỗi nguồn khớp bản trích Wayback; 3 trang × 320/375/768/1024/1440/1920 không tràn ngang, 1 H1, không ảnh vỡ; axe WCAG 2.2 AA 0 vi phạm ở 375/1440 cả `/`; 0 request YouTube trước khi bấm, chuột và Enter đều mở `youtube-nocookie`); header Step 13 **50/50**; hồi quy: legacy media 385/385, placeholder 68/68, home hero 72/72, overlay 24/24, 5: 46/46, 6: 38/38, 7: 56/56, 8: 39/39, 10: 95/95, 11a 36/36, 11c 28/28, 11d 25/25, 11e 20/20, 12: 33/33, 13: 41/41, 14: 18/18, tunnel 35/35; `verify-step-3.php` phần sau điểm dừng có sẵn đều PASS (kiểm tra cảm nhận chuyển sang dựa trên dữ liệu); launch blockers `0` unwaived; LHCI **22/22 PASS** (`docs/jn/lighthouse/step-15/`; mobile `/course/` 100 LCP 1.73 s, `/client-success-story/` 100 LCP 1.73 s, `/my-favorite-product/` 99 LCP 1.89 s, `/san-pham/egf-cleansing-gel/` 100; desktop 100 cả 11 trang — URL sản phẩm trong `lighthouserc.json` nay là sản phẩm thật vì runner dùng lại sản phẩm đã xuất bản); fingerprint **15/31**: 16 mục fail đều do plugin có từ trước (Blocksy Companion → `theme-slug`; Amelia → chữ `wordpress` trong đường dẫn asset ở Cart/Checkout), không do Step 15. Review độc lập: 0 CRITICAL/HIGH, đã xử lý 4 MEDIUM (1 giữ theo D9 → UNK-15) + 3 LOW.
- **Phát hiện có từ trước Step 15 (chưa xử lý, chờ chủ dự án):** plugin **Amelia đang bật và giống hệt bản bẻ khoá trong `Tài liệu /ameliabooking`**; **Blocksy Companion** đang bật (trái ADR-001) và làm `jn-fingerprint-scan.mjs` báo `theme-slug` trên mọi trang; `verify-step-3.php` dừng ở kiểm tra "disallowed block types" từ Step 4 (block WooCommerce).
- **Deploy:** có các file `scripts/.media-masters/*.orig.*` → `import-legacy-media.php` (22 khóa Step 15) → `build-step-15-legacy-content.php` (`JN_ALLOW_DESTRUCTIVE=1`) → purge page cache.


## 2r. Step 16 — Nội Dung jannieedu.com Cũ Cho My Story & Coaching (2026-10-30, chủ dự án duyệt `/plan`, mặc định D1–D6)

- **Nguồn:** bản lưu Wayback My Story (2026-05-20) và Coaching (2026-07-10), bản trích ở `docs/jn/legacy-content/`; 12 ảnh gốc từ WordPress cũ (ID 11928–11939, sha256 trong `originals.sha256`). Quyết định: ADR-023.
- **`/my-story/`:** hero chia đôi (ảnh banner cũ) → 5 khối ảnh/chữ (Khởi nguồn sự nghiệp · Chuyên môn & giải thưởng · Tầm nhìn & sứ mệnh · Phát triển & tầm nhìn tương lai · Lan tỏa giá trị) → lời mời đặt lịch → form. Bỏ khối chuyên gia chép từ trang chủ và khung "Các Cột Mốc" → phần My Story của UNK-08 đóng.
- **`/coaching/`:** 8 khối của trang cũ (2 dải ảnh `core/cover`, 3 video "Phản Hồi Từ Học Viên") rồi giữ 3 thẻ gói + FAQ (D1) trước form. Hero editorial, 3 trụ cột, khối chuyên gia, cảm nhận dạng thẻ bị thay.
- **Code:** builder mới trong `scripts/lib/jn-legacy-blocks.php` (hero chia đôi, `core/cover`, hàng ảnh/chữ, cắt section theo tên, `jn_lb_write_page()` dùng chung với Step 15); `scripts/build-step-16-legacy-pages.php`; `jn-performance` preload/eager/sizes theo ảnh hero cũ (`jn_legacy_hero_ids()`, `jn_legacy_hero_lcp_id()`); CSS Step 16 trong `legacy-pages.css`; mô tả SEO 2 trang theo câu chữ cũ; lời dẫn `my-story` lấy từ nguồn cũ; helper test chung `scripts/lib/jn-legacy-test-kit.php`.
- **Kiểm chứng:** `scripts/test-step-16-legacy-pages.php` **48/48** (RED trước: 8 fail + fatal); verifier trình duyệt chung **44/44** (228 chuỗi khớp Wayback; 5 trang × 6 viewport không tràn ngang; axe 0 vi phạm; video 0 request trước khi bấm); header Step 13 **50/50** (sau khi áp nhịp eyebrow cho cột chữ hero); `verify-step-3.php` phần sau điểm dừng có sẵn đều PASS (hero eager/high, `#coaching-tiers`, `?goi=`); 19 bộ PHP xanh (15: 111/111, legacy media **476/476**, placeholder 62/62, home hero 72/72, overlay 24/24, 5: 46/46, 6: 38/38, 7: 56/56, 8: 39/39, 10: 95/95, 11a/11c/11d/11e, 12: 33/33, 13: 41/41, 14: 18/18, tunnel 35/35); launch blockers `0` unwaived; LHCI **22/22 PASS** (`docs/jn/lighthouse/step-16/`; mobile `/coaching/` 100/100/100/100 LCP 1.73 s, `/my-story/` 100/100/100/100 LCP 1.73 s). Review độc lập: 0 CRITICAL/HIGH, đã xử lý 1 MEDIUM (`sizes` preload/`<img>` khi trang có cả hai loại hero) + 2 LOW (chỉ nhận ảnh hero của chính trang; mô tả SEO My Story bỏ tuyên bố chưa xác minh, tiêu đề theo trang cũ).
- **Làm nét toàn bộ 6 ảnh trang `/my-story/` (cùng ngày, chủ dự án duyệt `/plan` & phản hồi ảnh chụp thực tế):** nâng cấp 6 ảnh (`my_story_banner` `1024×576` → **`1920×1080`** `118.4 KB`, và 5 ảnh `my_story_1`, `my_story_prize`, `my_story_su_menh`, `my_story_value`, `my_story_mission` `400×600` → **`800×1200`** `65.1–194.2 KB`) qua quy trình Real-ESRGAN 4x (`realesrgan-x4plus` trên máy host từ file `.orig.png` đã xác thực `sha256`, sinh `.x4.png` `4096×2304` và `1600×2400`) + **khôi phục chi tiết khuôn mặt YuNet + CodeFormer ONNX** (`scripts/lib/jn-restore-faces.py`, `face_restore: true`, `confidence >= 0.90`, `fidelity = 0.6`, khớp màu LAB) + downscale `<= 0.5×` + unsharp mask `sharpen: 0.25` + mã hóa WebP `quality: 88` và AVIF (cả bản chính lẫn sub-sizes) ở `avif_quality: 82` kèm làm nét hậu kỳ sub-size (`min(0.18, $sharpen)`); **đổi tên file đầu ra sang `jn-legacy-my-story-*-hd.webp`** (xoá file cũ + ghi lại `<img src>`/`srcset` trên `/my-story/`) để phá triệt để bộ nhớ đệm `Cache-Control: max-age=31536000, immutable` của trình duyệt (ADR-019); gỡ bỏ giới hạn `max-width: 300px` trên mobile trong `legacy-pages.css` để hiển thị đủ `400px` đạt chuẩn 2x Retina. Kiểm chứng `test-legacy-media.php` đạt **`476/476 PASS`** (mọi ảnh đạt độ sắc nét Laplacian variance `>= 1.25×` so với bicubic baseline).
- **Kiểm tra được chuyển hướng (không bỏ):** Step 8 hero `/coaching/`, ảnh chuyên gia lazy (nay trên `/`), preload `/my-story/`; home hero; overlay cleanup; placeholder media; số ảnh gốc 22 → 34 (chi tiết ADR-023).
- **Sửa lỗi bỏ sót của Step 15:** mô tả SEO đổi trong `class-jn-seo-meta.php` không lên trang vì trường TSF do `build-step-8.php` gieo sẵn luôn thắng map → `jn_lb_sync_seo_meta()` (gọi trong cả 2 migration) chép tiêu đề/mô tả của 5 trang vào trường TSF; `test-step-16` kiểm tra mô tả trên trang thật.
- **Còn chờ chủ dự án:** xác nhận các tuyên bố của trang My Story cũ (UNK-03); các mục của Step 15 (giá, giao hàng, Herpes, quyền video, Amelia, Blocksy Companion).

## 2s. Step 17 — Chuyển Trang Không Giật (2026-10-31, chủ dự án duyệt `/plan`, chọn phương án (a))

- **Lỗi:** chữ giật mỗi lần đổi tab, logo sai lúc chuyển, cả trang giật. Nguyên nhân gốc: `ct-main-styles` (Blocksy) và Playfair Display chỉ được bật ở **lần tương tác đầu** trên mỗi trang, nên khung đầu luôn vẽ thiếu CSS/font rồi tự sửa khi rê chuột/cuộn/chạm (logo +4 px, wordmark +16 px và đổi font, footer +95 px, mobile: nút tài khoản và nút menu xếp chồng). Thêm: mục menu active 600 làm nhãn xê dịch 0.3–2.3 px; trang ngắn bật/tắt thanh cuộn lệch 7.5 px; `#anchor` chéo trang tự cuộn ~1.2 s. Quyết định: ADR-024 (cũng sửa phần code đã lệch khỏi §2 Step 8: nạp CSS theo tương tác thay vì `load + 150ms`, bỏ preload Playfair).
- **Code:** `jn-performance.php` (`ct-main-styles` chặn render; `ct-woocommerce-styles` chặn render trên tài khoản + sản phẩm; bộ nạp trễ `jn_print_deferred_style_loader()` không còn nạp font); `functions.php` giữ `@font-face` Playfair trong CSS inline; `inc/setup.php` preload Playfair normal + Geist, bỏ `<noscript>`; `tokens.css` (`--jn-nav-semibold-tracking-offset`, `--jn-motion-page`, `--jn-ease-ios`); `base.css` (`scrollbar-gutter: stable`, `scroll-behavior: auto` + `html.jn-smooth-scroll`, bù `letter-spacing` mục menu active); `motion.css` §5 View Transitions (header/drawer có tên riêng, nội dung mờ + trượt 8 px, chỉ `opacity`/`transform`, tắt khi giảm chuyển động); `inc/motion.php` bật cuộn mượt ở lần tương tác đầu; ngân sách LCP 2.5 s (`lighthouserc.json`, `run-step-8-lhci.mjs`).
- **Kiểm chứng:** `scripts/test-step-17-page-transitions.php` **29/29** (RED trước: 25/26 fail); `scripts/verify-step-17-transitions.mjs` **81/81** (RED trước: 48/67 fail) — 7 tab × 1440/390 px: header + footer không dịch khi có input đầu, hình học header trùng giữa các tab (menu ≤ 0.13 px), có view transition (không có khi giảm chuyển động), Playfair normal/italic xong trước FCP, không có long animation frame > 50 ms trong 500 ms sau khi lộ trang, thanh cuộn cổ điển giữ logo đứng yên giữa trang ngắn/dài, `/my-story/#booking-form` vào thẳng mục tiêu; anchor trong trang vẫn cuộn mượt. Hồi quy: Step 8 **39/39** + `verify-step-8` 4/4, Step 10 95/95, 11e 20/20, 12 33/33, 13 41/41 + `verify-step-13` 50/50. Review độc lập: 0 CRITICAL/HIGH; đã thêm test cho font italic, hỗ trợ LoAF, CSS WooCommerce hoãn trên shop; hai nhận xét (đảo thứ tự cascade, `animation-duration` vô hiệu) bị bác bằng số đo.
- **Lighthouse** (`docs/jn/lighthouse/step-17-before/` → `step-17-after/`): mobile trang nội dung FCP 0.79 s → 0.91–1.36 s, LCP 1.51–1.89 s → 1.81–2.41 s, Performance 97–100, CLS ≤ 0.036; desktop 100. Lần chạy đầu phát hiện regression CLS 0.356 trên trang sản phẩm desktop (đã sửa bằng CSS WooCommerce chặn render, còn 0) và thử bỏ preload Playfair (tệ hơn, đã hoàn tác). Còn fail: `/my-story/` ảnh LCP 160 KB > 150 KB (có từ thay đổi Step 16 chưa commit, không thuộc Step 17). **`/my-favorite-product/` mobile sát mép:** FCP 1.36–1.66 s giữa các lần chạy, LCP 2.41–2.49 s.


## 2t. Step 18 — Trang Sản Phẩm Yêu Thích Theo Bản "Nâng Cấp My Favorite Product" (2026-10-31, chủ dự án gửi HTML mẫu & duyệt `/plan`, mặc định D1–D7)

- **Phạm vi:** `/my-favorite-product/` dựng lại theo bản mẫu (lưu tại `docs/jn/favorite-products/mockup-2026-10-31.html` + sha256): hero (cột chữ theo hệ chung + minh họa 3 chai), dải chữ chạy có nút tạm dừng, đoạn giới thiệu chữ cái lớn, 6 thẻ sản phẩm thật, manifesto, hộp thoại tư vấn qua Zalo `0559 912 642`. Quyết định & chỗ khác bản mẫu: ADR-025.
- **Code:** 3 block jn-core `jn/product-stage`, `jn/marquee`, `jn/product-actions` (+ `JN_Consult`, option `jn_contact_zalo_url`); 3 pattern `jannie/favorite-*`; `assets/css/favorite-products.css` inline chỉ trên trang dùng (`functions.php` → `jannie_page_uses_favorite_css()`, `jannie_build_css_bundle()`), nạp cả trong editor; đích reveal mới trong `inc/motion.php`; `jn-performance.php` hoãn `product-image.css`/`product-summary.css` của WooCommerce trên trang này; xoá rule `.jn-legacy-products` chết trong `legacy-pages.css`. Dữ liệu: `jn-legacy-content.json` thêm `teaser` × 6; `jn_lb_products_page()` dựng từ pattern; `build-step-15` ghi short = teaser, description = mô tả gốc; migration `scripts/build-step-18-favorite-products.php` (chỉ trang 12 + 6 sản phẩm chính thức, có `dry-run`, idempotent). Snapshot trước: `backups/pre-step-18-2026-10-31.sql.gz`.
- **Kiểm chứng:** `scripts/test-step-18-favorite-products.php` **97/97** (RED trước: 17 pass / 69 fail); `scripts/verify-step-18-favorite-products.mjs` **17/17** (ảnh `docs/jn/step-18/`); hồi quy PHP Step 8 (39), 10 (95), 12 (33), 13 (41), 14 (18), 15 (117), 16 (48), 17 (29), home-hero (72) xanh; verify-step-13 (50), 15 (44), 17 (81) xanh. WPCS: block/class/pattern 0 lỗi; script CLI chỉ còn cảnh báo `echo` chưa escape giống các script trước. Không chạy `check-launch-blockers.php` (xoá dữ liệu, không có dry-run).
- **Code review độc lập** (0 CRITICAL/HIGH, 1 MEDIUM, 5 LOW): đã sửa MEDIUM — kéo bôi đen chữ trong ô lời nhắn ra ngoài làm đóng hộp thoại (nay chỉ đóng khi cả nhấn lẫn nhả đều trên nền); và 4 LOW — migration kiểm hết 6 sản phẩm rồi mới ghi; hoãn CSS WooCommerce chỉ khi `jn-favorite` thực sự được nạp (`wp_style_is`); focus về nút "Sao chép" sau khi sao chép dự phòng; thay `{name}`/`{price}` một lượt. Giữ nguyên có chủ đích: CSS trang nạp ở cả `add_editor_style` lẫn `enqueue_block_assets` (cùng cách `hero-home.css`/`legacy-pages.css`); nhận diện trang bằng dấu hiệu trong nội dung (nếu sau này đưa section vào synced pattern thì cần thêm điều kiện). Editor: trang 12 mở với 33 block, 0 lỗi validation.
- **Lighthouse** (`docs/jn/lighthouse/step-18/`): desktop 100 (FCP 0.37 s, LCP 0.54 s); mobile Performance 0.97, A11y/BP/SEO 100, LCP 2.41 s (lời dẫn hero), CLS 0, TBT 0. FCP mobile 1.66 s (trước Step 18: 1.36–1.66 s) vượt ngưỡng cũ 1.5 s → chủ dự án chọn phương án (a): ngân sách FCP nhóm shop **1.7 s** trong `lighthouserc.json` + `run-step-8-lhci.mjs` (ADR-025 §2.9); đối chiếu lại: 0 assertion fail, biên ~40 ms.

---

## 3. Sổ Đăng Ký Các Hạng Mục Chưa Xác Định (`UNKNOWN Register`)

Mọi thông tin thuộc quyền sở hữu của chủ dự án đang ở trạng thái `UNKNOWN` trong `PROJECT CONTEXT CARD` được theo dõi tại bảng dưới đây. Theo quy tắc dự án (§1), hệ thống **tuyệt đối không tự bịa số liệu hay nội dung**, mà sử dụng nhãn giữ chỗ (placeholder) rõ ràng kèm ghi chú tại đây cho đến khi chủ dự án cung cấp:

| Mã ID | Hạng mục thông tin (`PROJECT CONTEXT CARD`) | Hiện trạng trong Wireframe / Dự án | Bước bị ảnh hưởng (Blocking Step) | Chặn phát hành chính thức? (`Blocks Launch?`) | Giải pháp tạm thời trong quá trình phát triển (Steps 1–8) |
| :---: | :--- | :--- | :---: | :---: | :--- |
| **`UNK-01`** | **Giá bán chính thức của 3 gói Coaching (`PACKAGE_PRICES`)** | Đã tạo 3 sản phẩm dịch vụ ảo ở Step 4 (`ID 163, 164, 165`) khớp nguyên văn tên gói trong biểu mẫu, giữ ở trạng thái **`Draft`** và ẩn khỏi danh mục do chưa có giá chính thức. | **Step 4** (Đã khởi tạo sản phẩm `Draft`) & **Step 9** (Xuất bản giá thật nếu thu tiền trực tiếp) | **CÓ — CHẶN PHÁT HÀNH (`LAUNCH BLOCKER`) đối với tính năng niêm yết giá công khai; luồng tạo đơn `pending` từ quản trị & thanh toán qua `order-pay` đã hoạt động 100%** | Giữ cả 3 sản phẩm gói Coaching ở trạng thái `Draft` (`_jn_price_on_request = yes`), chặn thêm trực tiếp vào giỏ hàng nhưng cho phép trợ lý tạo đơn `pending` theo mức phí thỏa thuận và gửi liên kết `order-pay` cho học viên thanh toán. |
| **`UNK-02`** | **Bộ ảnh thật có sự đồng ý của chủ nhân (`REAL_CONSENTED_PHOTOS`)** | Đã nhập 5 ảnh `.webp` vào Media Library ở Step 3 (`ID 86..90`) với tiền tố tiêu đề `"PLACEHOLDER – "` và `alt` tiếng Việt từ `data-alt`. | **Step 3** (Đã nhập ảnh giữ chỗ) & **Step 9** (Nghiệm thu xuất bản) | **CÓ — CHẶN PHÁT HÀNH TUYỆT ĐỐI (`HARD LAUNCH BLOCKER`)** | Đã gắn tiền tố `"PLACEHOLDER – "` trong Media Library ở Step 3; khóa cổng phát hành ở Step 9 cho đến khi thay bằng ảnh thật có bản quyền/sự đồng ý. |
| **`UNK-03`** | **Xác thực các con số tuyên bố & 3 đánh giá học viên (`CLAIMS_AND_TESTIMONIALS_VERIFICATION`)** | Đã nạp nguyên văn 3 bản ghi `jn_testimonial` (`ID 91..93`: `Chị Minh Thảo`, `Chị Ngọc Bích`, `Chị Phương Uyên`) ở Step 3 kèm trường `jn_admin_note = "cần xác minh & xin phép khách"`. | **Step 3** (Đã tạo CPT `jn_testimonial`) & **Step 9** (Duyệt nội dung pháp lý) | **CÓ — CHẶN PHÁT HÀNH (`HARD LAUNCH BLOCKER`)** | Đã gắn cờ `"cần xác minh & xin phép khách"` trực tiếp trên từng bản ghi trong WP Admin ở Step 3 và kiểm tra lại ở cổng xuất bản Step 9. **Step 15 (ADR-022):** 3 cảm nhận wireframe (91–93) chuyển Nháp; thay bằng 3 cảm nhận video thật từ jannieedu.com cũ (Loan Nguyễn, Thu Minh, Hồng Hạnh, không sao). Vẫn chờ chủ dự án xác nhận quyền dùng hình/video học viên — video nằm trên kênh YouTube "khoa nguyễn", không phải kênh chính thức. |
| **`UNK-04`** | **Thông tin liên hệ chính thức: Địa chỉ chi tiết, Số điện thoại/Zalo, Giờ mở cửa (`CONTACT_DETAILS`)** | Footer và hộp Hotline chỉ ghi `"Hồ Chí Minh, Việt Nam"`, `"contact@jannienguyen.vn"`, `"(+84) 908-XXX-XXX"`, `"Thứ 2 - Thứ 7: 9:00 - 18:00"`, thiếu số hotline/Zalo và địa chỉ cụ thể. | **Step 3** (Footer & Hotline) & **Step 8** (Schema `LocalBusiness` / `EducationalOrganization`) | **CÓ (Chặn hoàn thiện thông tin liên hệ & Local SEO trước khi Go-Live)** | Giữ nguyên văn nội dung từ wireframe và chờ chủ dự án cung cấp số điện thoại/địa chỉ thật. **2026-10-31 (Step 18):** chủ dự án cung cấp số Zalo `0559 912 642` → dùng cho nút tư vấn `/my-favorite-product/` (`jn_contact_zalo_url`); chưa đưa vào `jn_brand_telephone`/hộp Hotline — chờ xác nhận đây cũng là hotline chính thức. Địa chỉ chi tiết vẫn UNKNOWN. |
| **`UNK-05`** | **Cổng thanh toán & Thông tin tài khoản ngân hàng nhận chuyển khoản (`PAYMENT_GATEWAYS`)** | Đã cấu hình phương thức Chuyển khoản ngân hàng (`bacs`) ở Step 4 với thông tin tài khoản giữ chỗ gắn nhãn `[PLACEHOLDER – CẦN CẬP NHẬT SỐ TÀI KHOẢN THẬT TRƯỚC KHI GO-LIVE]`; COD chỉ bật cho sản phẩm vật lý. | **Step 4** (Đã cấu hình BACS + COD) & **Step 9** (Cập nhật STK thật / duyệt cài VNPay, MoMo) | **CÓ — CHẶN PHÁT HÀNH (`LAUNCH BLOCKER` — phải thay STK ngân hàng thật trước khi nhận thanh toán thực tế)** | Sử dụng BACS với thông tin giữ chỗ rõ ràng để kiểm thử E2E luồng đặt hàng & `order-pay`; không cài đặt bất kỳ plugin cổng thanh toán bên thứ ba nào (VNPay, MoMo...) khi chưa có phê duyệt và thông tin định danh Merchant của chủ dự án. |
| **`UNK-06`** | **Font chữ tiêu đề chính thức (`HEADING_FONT`)** | **ĐÃ CHỐT: `Playfair Display`** (Chủ dự án đã phê duyệt vào ngày 2026-10-27 sau khi đối chiếu `docs/jn/font-specimen.png`). | **Đã giải quyết ở Step 0** (Thực thi ở **Step 2**) | **KHÔNG (Đã chốt)** | Tự host WOFF2 bộ font **`Playfair Display`** (gộp `latin` + `vietnamese`) cho toàn bộ tiêu đề trên website ở Step 2. |
| **`UNK-07`** | **Tài sản nhận diện thương hiệu: File Logo Vector & Favicon (`BRAND_ASSETS`)** | Đã khởi tạo logo monogram `"JN"` vector (`jn-monogram.svg`), logo email (`jn-email-logo.svg`) và Site Icon `512×512` (`jn-monogram-icon.png`); chưa có file ảnh chia sẻ mạng xã hội (`og:image`) chính thức của chủ dự án. | **Step 8** (SEO/OpenGraph) | **CÓ (Cần ảnh OG chính thức trước khi Go-Live)** | Đã cài đặt Favicon/Site Icon monogram `"JN"` theo bảng màu `#1F1E1D` / `#FAF8F5` ở Step 2 và logo email ở Step 4, sẵn sàng thay thế bằng file của chủ dự án. |
| **`UNK-08`** | **Thiết kế & Nội dung chi tiết của 7 trang còn lại trên thanh điều hướng + Danh sách sản phẩm yêu thích thật (`OTHER_PAGES_CONTENT`)** | Đã dựng khung 7 trang ở Step 3 và hoàn thiện trang `/my-favorite-product/` (`ID=12`, `woocommerce/product-collection`) ở Step 4 (hiện hiển thị trạng thái trống chuẩn thiết kế sau khi xóa sản phẩm DEMO). | **Step 3** & **Step 4** (Đã hoàn tất cấu trúc) | **CÓ đối với các trang/danh mục sản phẩm chủ dự án muốn mở công khai ngay ngày ra mắt** | Giữ trạng thái trống trung thực trên `/my-favorite-product/` và nhãn `[CẦN NỘI DUNG: …]` trên các trang khung cho đến khi chủ dự án cung cấp dữ liệu thật. **Step 15 (ADR-022):** `/my-favorite-product/` (6 sản phẩm), `/course/` (26 khóa) và `/client-success-story/` đã có nội dung nguyên văn từ jannieedu.com cũ theo chỉ đạo của chủ dự án; các trang còn khung: `/my-story/` (cột mốc), 2 trang pháp lý. **Step 16 (ADR-023):** `/my-story/` và `/coaching/` theo nội dung cũ; còn khung: 2 trang pháp lý (UNK-09). |
| **`UNK-09`** | **Văn bản pháp lý: Chính sách Bảo mật, Điều khoản Dịch vụ & Câu chữ ô Consent (`LEGAL_PAGES_COPY`)** | Đã dựng khung 2 trang `/chinh-sach-bao-mat/` (đặt làm `wp_page_for_privacy_policy`) và `/dieu-khoan-dich-vu/` gắn nhãn `[CẦN NỘI DUNG: …]`, đồng thời bổ sung ô Consent trong `jn/booking-form` ở Step 3. | **Step 3** (Liên kết tại ô Consent của biểu mẫu) & **Step 9** (Duyệt nội dung pháp lý) | **CÓ — CHẶN PHÁT HÀNH (`HARD LAUNCH BLOCKER` theo quy định bảo vệ dữ liệu cá nhân)** | Đã có trang đích và câu chữ đề xuất tại ô Consent (`"Tôi đồng ý để Jannie Nguyen Academy thu thập và xử lý thông tin cá nhân theo Chính sách bảo mật nhằm mục đích liên hệ tư vấn & xếp lịch Coaching."`), gắn cờ chờ chủ dự án/luật sư duyệt trước khi Go-Live. |
| **`UNK-10`** | **Tên miền chính thức & Máy chủ Web môi trường Production (`PROD_DOMAIN` & `PROD_WEB_SERVER`)** | Đang ghi `UNKNOWN` trong `PROJECT CONTEXT CARD` (chưa rõ máy chủ đích chạy LiteSpeed, Nginx hay Apache). | **Step 8** (Chọn LiteSpeed Cache hay WP Super Cache) & **Step 9** (Tệp cấu hình rewrite máy chủ đích) | **CÓ (Cần biết trước khi đóng gói bàn giao lên máy chủ thật ở Step 9)** | Phát triển trên Docker Apache (`.htaccess`) ở Steps 1–8; chuẩn bị sẵn cả 2 bộ quy tắc rewrite (`.htaccess` cho Apache/LiteSpeed và `nginx.conf` snippet cho Nginx) cùng cấu hình WP Super Cache mặc định (hoặc LiteSpeed Cache nếu xác nhận LiteSpeed). |
| **`UNK-11`** | **Thông tin máy chủ gửi thư giao dịch (`SMTP_CREDENTIALS`)** | Chưa có thông tin tài khoản SMTP/API (Amazon SES, Postmark, Google Workspace, Resend...) cho địa chỉ `contact@jannienguyen.vn`. | **Step 8 / Step 9** (Cấu hình FluentSMTP gửi mail thật) | **CÓ (Cần để gửi email thông báo yêu cầu đặt lịch trên môi trường Production)** | Trong môi trường Docker local (Steps 1–8), định tuyến email qua Mailpit/local catcher để kiểm thử tự động 100% luồng gửi thư; cài sẵn FluentSMTP chờ nhập khóa API khi lên Production. |
| **`UNK-12`** | **Mã theo dõi phân tích (Analytics / Pixel) & Cơ chế Cookie Consent (`ANALYTICS_ID`)** | `PROJECT CONTEXT CARD` ghi rõ: *"Analytics = NONE unless owner adds later (no cookie banner needed if none)"*. | **Step 8** (Hiệu năng & Quyền riêng tư) | **KHÔNG (Mặc định không cài mã theo dõi bên thứ ba)** | Không chèn bất kỳ script theo dõi bên thứ ba nào (giúp đạt điểm tối đa PageSpeed & Best Practices và không cần banner làm phiền người dùng). |
| **`UNK-13`** | **Liên kết mạng xã hội chính thức (`SOCIAL_LINKS`)** | Chưa có URL Facebook, Instagram, YouTube, TikTok hay Zalo Official của Jannie Nguyễn. | **Step 3** (Footer) & **Step 8** (Schema `sameAs`) | **KHÔNG (Có thể bổ sung bất cứ lúc nào)** | Không tự ý thêm icon mạng xã hội trỏ về `#`; chỉ hiển thị khi chủ dự án cung cấp đường dẫn thật. |
| **`UNK-14`** | **Danh sách họ tên & email thực tế của các tài khoản nhân sự (`STAFF_ACCOUNTS`)** | Đang ghi `UNKNOWN` trong `PROJECT CONTEXT CARD`. Đã khởi tạo 3 tài khoản nhân sự giữ chỗ (`quan_ly_cua_hang`, `tro_ly_lich_hen`, `bien_tap_noi_dung`) kèm tiền tố `"PLACEHOLDER – "` và bật bắt buộc 2FA ở Step 6. | **Step 6** (Đã tạo tài khoản giữ chỗ) & **Step 9** (Cập nhật email nhân sự thật trước khi bàn giao) | **CÓ (Cần cập nhật địa chỉ email thực tế của nhân sự trước khi bàn giao vận hành)** | Sử dụng các tài khoản nhân sự giữ chỗ với mật khẩu ngẫu nhiên 24 ký tự và bắt buộc 2FA (`Two_Factor_Email`); cập nhật email thật và gửi liên kết đặt mật khẩu + thiết lập TOTP khi chủ dự án cung cấp danh sách nhân sự. |
| **`UNK-15`** | **Giá hiện hành & chính sách/phí giao hàng của 6 sản phẩm yêu thích (Step 15)** | Giá lấy từ bản lưu jannieedu.com 05/2024 (496.000 / 646.000 / 646.000 / 1.296.000 / 796.000 ₫); Xà bông thảo dược chưa từng có giá → "Liên hệ". Shop đang tắt giao hàng (`woocommerce_ship_to_countries = disabled`), chưa có vùng/phí ship. | **Step 15** | **CÓ — chặn bán sản phẩm vật lý khi go-live** | Giữ giá 2024 ở local; chủ dự án xác nhận giá và chọn chính sách giao hàng trước khi nhận đơn thật. Step 18 giữ "Mua Ngay" → trang sản phẩm (không thêm giỏ trên thẻ) cho tới khi chốt mục này. |
| **`UNK-16`** | **Rà pháp lý mô tả "The Herpes Treatment" (Step 15, D6)** | Đăng nguyên văn từ trang cũ: "chống viêm và kháng vi khuẩn… làm lành tổn thương" — có thể bị coi là quảng cáo mỹ phẩm như thuốc. | **Step 15** | **CÓ — chặn go-live sản phẩm này** | Giữ nguyên văn ở local; chủ dự án/luật sư duyệt hoặc sửa câu chữ, hoặc chuyển sản phẩm về Nháp. |

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
  5. Cài đặt parent theme **Blocksy (Free)** và plugin **WooCommerce** (bật ngay **HPOS**), **WPS Hide Login** (đặt đường dẫn đăng nhập `/<WP_LOGIN_SLUG>`).
  6. Khởi tạo bộ khung (skeleton) cho:
     - Child theme `/app/themes/blocksy-jn` (`style.css`, `functions.php`, `theme.json`).
     - Plugin nghiệp vụ `/app/modules/jn-core` (`jn-core.php` và 4 thư mục `booking/`, `customers/`, `testimonials/`, `blocks/`).
     - Hai Must-Use Plugins `/app/mu-plugins/jn-hardening.php` (đổi REST prefix sang `/api`, tắt XML-RPC, gỡ generator tag) và `/app/mu-plugins/jn-performance.php` (gỡ emoji, dashicons, oembed, Google Fonts của theme cha).
  7. Cấu hình bộ công cụ kiểm chuẩn PHPCS + WPCS và PHPUnit trong Docker.
- **Sản phẩm đầu ra (Outputs):**
  - `docker-compose.yml`, `.env.example`, `docker/` configs.
  - Website WordPress 7.1.3 hoạt động tại `http://localhost:8080`, đăng nhập qua `/<WP_LOGIN_SLUG>`, REST API tại `/api/`.
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
     - Truy cập `/wp-admin`, `/wp-login.php`, `/xmlrpc.php`, `/readme.html`, `/license.txt`, `/wp-config-sample.php` khi chưa đăng nhập đều trả về mã **`404 Not Found`** (không chuyển hướng `302` làm lộ đường dẫn `/<WP_LOGIN_SLUG>`).
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
