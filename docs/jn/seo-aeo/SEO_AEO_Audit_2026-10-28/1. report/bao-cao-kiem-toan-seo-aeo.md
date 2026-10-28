# Báo Cáo Kiểm Toán & Tối Ưu SEO / AEO — Website Jannie Nguyễn (Step 10)

> **Thời điểm:** 2026-10-28 · **Phạm vi:** source code project-owned (`jn-performance`, `jn-hardening`, child theme `jannie`) + bản render local `http://localhost:8080` · **Công cụ:** skill `sunext-semrush-ahrefs-os` v5.0 (Suite 2, 3, 4, 6, 8) + script `scripts/seo-audit-local.py` + `scripts/test-step-10-seo.php` + Lighthouse CI.
> **Nhãn:** `[FACT]` kiểm chứng trực tiếp · `[INFERENCE]` suy luận/ước lượng · `[ASSUMPTION]` giả định chờ duyệt · `[UNKNOWN]` thiếu dữ kiện.

---

## 1. Tóm tắt điều hành

| Chỉ số | Trước (baseline) | Sau | Bằng chứng |
|---|---|---|---|
| Lỗi CRITICAL / HIGH / MEDIUM / LOW (13 URL) | **12 / 5 / 23 / 20** | **0 / 0 / 1 / 12** | [audit-baseline-live.md](audit-baseline-live.md) → [audit-after-live.md](audit-after-live.md) `[FACT]` |
| Số đồ thị JSON-LD mỗi trang | 2 (rời nhau, trùng `WebPage`) | 1 (`@id` liên kết đầy đủ) | `test-step-10-seo.php` `[FACT]` |
| Dữ liệu bịa trong schema | `telephone: +84908789866` | 0 | `JN-SCH-06` `[FACT]` |
| Trang giỏ hàng/tài khoản có thể bị index | Có | Không (`noindex, follow`) | `JN-IDX-02` `[FACT]` |
| Tiêu đề trùng thương hiệu (`… \| Jannie Nguyễn - JANNIE NGUYEN`) | 11 trang (64–74 ký tự) | 0 (≤ 58 ký tự) | `JN-META-06` `[FACT]` |
| `/llms.txt` | 404 | 200 (sinh từ dữ liệu thật) | `JN-AEO-01` `[FACT]` |
| Điểm GEU (khả năng được AI trích dẫn) trang chủ / coaching | 12.0 | 65.3 | Suite 8 `compute_autogeo_geu_score` `[INFERENCE]` |
| Lighthouse CI (22 URL × preset) | 22/22 PASS | **22/22 PASS** | [`docs/jn/lighthouse/step-10/summary.json`](../../../lighthouse/step-10/summary.json) `[FACT]` |

> [!NOTE]
> Lighthouse SEO của `/cart/`, `/checkout/`, `/tai-khoan/` giảm từ 100 xuống **66–69** — **có chủ đích**: chỉ duy nhất audit `is-crawlable` fail vì các trang này giờ mang `noindex` (ADR-013). Mọi audit SEO khác trên 3 trang này đều đạt. Trang nội dung giữ SEO = 100 (mobile & desktop).

## 2. Phương pháp & ràng buộc

1. **Crawler của skill chặn `localhost`** (`validate_safe_url` chống SSRF) → không chạy `full-suite --url http://localhost:8080`. Cơ chế bảo mật này **không bị tắt**. Thay vào đó `scripts/seo-audit-local.py` tự tải HTML local (chỉ host `--base`) rồi đưa vào các hàm phân tích thuần của skill: `audit_single_page` (Suite 3), `compute_bilingual_readability` (Suite 6), `audit_robots_txt_for_ai_crawlers` + `compute_autogeo_geu_score` (Suite 8), cộng thêm nhóm kiểm tra nhất quán `JN-*` (robots meta ↔ sitemap ↔ robots.txt, canonical tự tham chiếu, thẻ trùng, toàn vẹn `@id` JSON-LD, nguồn gốc dữ liệu NAP).
2. **Keyword Architect (Suite 2, Mode 2 — mô tả doanh nghiệp):** [ban-thiet-ke-tu-khoa-seo-geo.md](ban-thiet-ke-tu-khoa-seo-geo.md) (705 từ khóa, 11 cụm) — đã được đánh giá phản biện tại [3. draft/kien-truc-tu-khoa-va-lo-trinh-noi-dung.md](../3.%20draft/kien-truc-tu-khoa-va-lo-trinh-noi-dung.md).
3. **Backlink sources (Suite 4):** `backlink-sources` lọc ngành beauty/education + VN; danh sách đã chọn lọc nằm trong bản nháp nội dung (mục 4).
4. Chưa có `PROD_DOMAIN` → **chưa chạy được:** crawl live, Google Rich Results Test, Search Console, PageSpeed thực địa, kiểm tra backlink hiện có `[UNKNOWN]`.

## 3. Phát hiện & xử lý

| # | Mức | Phát hiện `[FACT]` | Xử lý | Trạng thái |
|---|---|---|---|---|
| F1 | CRITICAL | Schema `Organization.telephone = +84908789866` không có nguồn (Context Card: phone UNKNOWN; giao diện hiện `(+84) 908-XXX-XXX`) | Gỡ; chỉ xuất khi chủ dự án đặt option `jn_brand_telephone` | ✅ |
| F2 | HIGH | Code gỡ `noindex` khỏi mọi trang (kể cả Cart/Checkout/Tài khoản, gỡ cả `wc_page_no_robots`) để Lighthouse đạt 100; hằng số `JN_STRICT_PRODUCTION_ROBOTS` trong docblock không tồn tại | `noindex, follow` cho trang tiện ích + tìm kiếm; trang nội dung thêm `max-snippet:-1, max-image-preview:large`; ngoại lệ Lighthouse có văn bản (ADR-013) | ✅ |
| F3 | HIGH | robots.txt production trong code lệch với `docs/jn/robots.production.txt`; filter `robots_txt` của `jn-hardening` là code chết; robots.txt cũ liệt kê slug đăng nhập ẩn và `Disallow` chính các trang cần `noindex` | Một builder `JN_SEO_Robots::build_robots_txt()` cho cả route và filter; bỏ slug đăng nhập và các trang `noindex` khỏi `Disallow`; tôn trọng `blog_public = 0` ngoài môi trường local; tài liệu sinh lại từ code | ✅ |
| F4 | HIGH | Sitemap/robots meta/tài liệu PROGRESS không nhất quán về trang pháp lý | Sitemap = đúng tập trang `index` (9 URL); trang pháp lý giữ `index` `[ASSUMPTION Q3]` | ✅ |
| F5 | MEDIUM | Route ngoài bản đồ (tìm kiếm, danh mục, archive) dùng title/description của trang chủ | Title theo ngữ cảnh (`Kết quả tìm kiếm “…”`, tên danh mục) hoặc để TSF tự sinh | ✅ |
| F6 | MEDIUM | Thiếu `BreadcrumbList`, `WebPage.isPartOf` trong đồ thị của dự án; `priceRange: $$$` khi giá UNKNOWN; `ProfessionalService` (LocalBusiness) thiếu địa chỉ/điện thoại | Đồ thị mới: `WebSite`, `Organization` (+`logo`), `Person` (+`knowsAbout`), `WebPage`/`ProfilePage`/`CollectionPage`, `BreadcrumbList`, `Service` ×3; bỏ `priceRange`; `ProfessionalService` hoãn đến khi có UNK-04 (ADR-012) | ✅ |
| F7 | MEDIUM | Không có `/llms.txt`, không có chính sách bot AI, không có FAQ | `/llms.txt`; bot AI được nêu tên trong nhóm `User-agent: *` `[ASSUMPTION Q2]`; pattern `jannie/faq` + `FAQPage` tự động (bỏ qua mục `[NHÁP]`) | ✅ (nội dung FAQ chờ duyệt) |
| F9 | MEDIUM | TSF tự gắn thêm "- JANNIE NGUYEN" vào mọi title | Tắt branding qua `the_seo_framework_use_title_branding` | ✅ |
| F10 | HIGH | `/cart/`, `/tai-khoan/` có 2 `canonical` + 2 `description` (TSF + fallback của dự án) | Fallback chỉ in khi TSF không hoạt động | ✅ |
| F11 | MEDIUM | Trang tìm kiếm không có meta description | Bổ sung description (trang vẫn `noindex`) | ✅ |
| F12 | HIGH | TSF 5.2 vẫn in đồ thị JSON-LD riêng vì filter `the_seo_framework_ld_json_scripts` **không tồn tại** ở bản 5.x; cùng lỗi với `…_rel_canonical_output`, `…_sitemap_style_enabled` | Dùng filter có thật `the_seo_framework_schema_graph_data`; gỡ code chết | ✅ |
| F13 | MEDIUM | `/my-favorite-product/` có 2 thẻ H1 (hero Blocksy bị ẩn bằng CSS nhưng vẫn có trong DOM) | Không render hero archive trên trang shop (`theme_mod_woo_categories_hero_enabled`) | ✅ |
| — | MEDIUM | `/cart/` nội dung mỏng (172 từ) | Chấp nhận — trang `noindex` | Chấp nhận |
| — | LOW | Thiếu header `Content-Security-Policy` (11 URL) | Ngoài phạm vi SEO; CSP cần thiết kế riêng để không vỡ WooCommerce Blocks | Ghi nhận |
| — | LOW | Chưa có FAQ trên trang nào | Chờ chủ dự án duyệt [bản nháp FAQ](../3.%20draft/faq-va-khoi-tra-loi-truc-tiep.md) | Chờ duyệt |

## 4. Thay đổi mã nguồn

| File | Thay đổi |
|---|---|
| `wordpress/app/mu-plugins/jn-performance.php` | Gỡ khối SEO cũ (≈ 585 dòng) → nạp 6 module; 1.553 → 984 dòng; version 2.1.0 |
| `wordpress/app/mu-plugins/jn-performance/class-jn-seo-{brand,meta,robots,sitemap,schema,llms}.php` | Module SEO mới (mỗi file < 400 dòng) |
| `wordpress/app/mu-plugins/jn-hardening/class-jn-hardening-endpoints.php` | Gỡ filter `robots_txt` trùng lặp |
| `wordpress/app/themes/jannie/patterns/faq.php`, `assets/css/block-styles.css` | Pattern FAQ (core/details, không JS) + style |
| `wordpress/app/themes/jannie/inc/woocommerce.php` | Tắt hero archive trên trang shop (F13) |
| `wordpress/app/themes/jannie/style.css`, `functions.php` | Version 1.1.0 (làm mới cache pattern & asset) |
| `scripts/seo-audit-local.py`, `scripts/test-step-10-seo.php` | Audit offline dùng engine skill + 77 kiểm thử tự động |
| `scripts/run-step-8-lhci.mjs`, `lighthouserc.json` | Ngoại lệ `is-crawlable` cho 3 trang tiện ích; tham số `--out=` |
| `scripts/test-step-8-performance.php` | Cập nhật assertion schema (Organization, không telephone) |

## 5. Kiểm chứng

| Bộ kiểm thử | Kết quả |
|---|---|
| `test-step-10-seo.php` (mới) | **77 / 77 PASS** |
| `test-step-7-hardening.php` | 54 / 54 PASS |
| `test-step-5-booking.php` | 45 / 45 PASS |
| `test-step-6-customers.php` | 37 / 37 PASS |
| `test-step-8-performance.php` | 34 / 35 — 1 FAIL **có sẵn từ trước, không do Step 10** (xem dưới) |
| `jn-fingerprint-scan.mjs` (Hide-WordPress) | 31 / 31 PASS |
| `verify-step-9-regression.mjs` (axe 13 trang, 4 độ phân giải, bàn phím, 3 trình duyệt, 92 link, 15 email) | 100% PASS ([regression/](regression/)) |
| Lighthouse CI 22 mục tiêu | 22 / 22 PASS (SEO trang nội dung = 100) |

> [!WARNING]
> **FAIL còn lại trong `test-step-8-performance.php`:** assertion tìm chuỗi `"woocommerce"` trong toàn bộ HTML `/coaching/`. Chuỗi xuất hiện trong CSS inline động của Blocksy (`ct-main-styles-inline-css`, selector `.woocommerce-info`), được Blocksy sinh mỗi khi WooCommerce bật (`inc/dynamic-styles/global.php`). **A/B:** khôi phục `jn-performance.php` + `jn-hardening` bản gốc (HEAD) và xóa transient `blocksy_dynamic_styles_descriptor` → vẫn ra đúng 1 lần xuất hiện. Đây không phải asset WooCommerce (Lighthouse đo `wooAssetCount = 0` trên mọi trang nội dung). Đề xuất: sửa assertion sang kiểm tra URL asset, hoặc lọc CSS động của Blocksy trên trang nội dung — cần bạn chọn.
>
> **Cập nhật sau báo cáo:** đã xử lý theo hướng sửa assertion (chỉ kiểm tra thẻ asset được tải, kể cả alias `/static/shop/`, kèm đối chứng trên `/cart/`); `test-step-8-performance.php` đạt 37/37. Chi tiết tại `docs/jn/PROGRESS.md` mục 2k.

## 5b. Rà soát mã (code review độc lập)

Một reviewer độc lập (subagent, chỉ đọc) đã kiểm tra toàn bộ thay đổi: **0 CRITICAL**, 2 HIGH, 7 MEDIUM, 10 LOW. Đã xử lý:

| Mức | Phát hiện | Xử lý |
|---|---|---|
| HIGH | Filter robots gỡ `noindex` trên mọi route không phải trang tiện ích (trang riêng tư/mật khẩu, archive, `noindex` do chủ dự án đặt trong TSF) | Chỉ ép `index` cho trang thuộc danh sách index của dự án + sản phẩm đã xuất bản; route khác giữ quyết định của TSF |
| HIGH | `blog_public = 0` (staging / production trước go-live) vẫn ra `index` + robots.txt cho phép crawl | Chỉ môi trường `local` mô phỏng production; nơi khác giữ `noindex` + `Disallow: /` |
| MEDIUM | Ảnh OG mặc định đè ảnh đại diện thật của trang/sản phẩm | Chuyển sang `the_seo_framework_generated_image_details` (chỉ bù khi không có ảnh) |
| MEDIUM | Tiêu đề fallback (`pre_get_document_title`) không escape | `esc_html()` |
| MEDIUM | Trang đăng nhập có thể mất `noindex` | Filter bỏ qua khi `login_init` / trước `template_redirect` |
| MEDIUM | Giá gói ở trạng thái Draft có thể lọt vào `Offer` | Chỉ lấy giá sản phẩm `publish`; ghi nhớ kết quả truy vấn |
| MEDIUM | Sitemap có thể liệt kê sản phẩm bị ẩn khỏi catalog, có mật khẩu hoặc `noindex` trong TSF; mỗi lần gọi chạy ~10 truy vấn | Lọc các trường hợp đó + cache transient 12h (xóa khi lưu/xóa bài) |
| MEDIUM | `Disallow` và `noindex` trên cùng URL | Bỏ `Disallow` cho trang `noindex` |
| MEDIUM | `@id` của trang tìm kiếm trùng trang chủ | Canonical/`@id` dùng `get_search_link()` |
| LOW | Map ghi đè tiêu đề/mô tả do chủ dự án nhập trong TSF; nội dung sản phẩm chưa lọc shortcode/block; `is_shop()` gọi quá sớm; `wp_json_encode` trả `false`; test đổi `blog_public` không có `finally`; llms.txt liệt kê trang chưa xuất bản | Đã sửa tất cả (giá trị TSF hiện có trùng với map nên không đổi hiển thị) |

Chưa xử lý (ghi nhận): FAQ chèn dạng synced pattern (`core/block`) chưa được nhận diện; trang sản phẩm vẫn có thêm khối Product JSON-LD riêng của WooCommerce (không liên kết `@id`); danh sách bot AI trong code và `robots.production.txt` được đồng bộ bằng cách sinh lại file từ code.

## 6. Việc cần chủ dự án quyết định / cung cấp

1. **Q2 — Bot AI** (mặc định đang cho phép tất cả): giữ, hay chặn bot huấn luyện (`GPTBot`, `Google-Extended`, `CCBot`…) nhưng vẫn cho bot tìm kiếm (`OAI-SearchBot`, `PerplexityBot`)?
2. **Q3 — Trang pháp lý:** đang giữ `index`.
3. **Q4 — `PROD_DOMAIN`:** cần để chạy crawl live, Rich Results Test, Search Console.
4. **UNK-04 (NAP):** số điện thoại + địa chỉ đầy đủ → `wp option update jn_brand_telephone "+84…"`; khi có địa chỉ, khôi phục `ProfessionalService`/`LocalBusiness`.
5. **UNK-13:** link mạng xã hội → `wp option update jn_brand_same_as '["https://…"]' --format=json` (tự vào `sameAs`).
6. **UNK-07:** ảnh OG chính thức (đang dùng ảnh `PLACEHOLDER`).
7. **Duyệt FAQ nhóm A** trong [bản nháp](../3.%20draft/faq-va-khoi-tra-loi-truc-tiep.md).
8. **Đề xuất (chưa làm):** slug tiếng Anh (`/my-story/`, `/course/`, `/client-success-story/`) → slug tiếng Việt + 301; bật khu vực blog cho lộ trình nội dung (liên quan ADR-010).

## 7. Counter-Evidence · Confidence · Falsification

- **Counter-Evidence:** Điểm `KD`/`Opportunity` và `GEU` là ước lượng heuristic của skill ở Mode A, không phải Search Volume/thứ hạng thật `[FACT]`. Kiểm toán chạy trên bản local — cấu hình máy chủ production (HTTPS, header, CDN) có thể tạo khác biệt `[INFERENCE]`. Google không còn hiển thị rich result FAQ cho site thương mại `[FACT]`.
- **Confidence:** Cao (`~90%`) cho các sửa lỗi kỹ thuật (có test tự động + Lighthouse) `[INFERENCE]`; trung bình (`~60%`) cho tác động thứ hạng/trích dẫn AI vì phụ thuộc nội dung và backlink chưa có `[INFERENCE]`.
- **Falsification:** (a) Sau go-live, nếu Search Console báo `/cart/` hoặc `/tai-khoan/` "Indexed" → chính sách robots chưa có hiệu lực. (b) Nếu Rich Results Test báo lỗi `@id` hoặc trùng thực thể trên bất kỳ trang nội dung nào → đồ thị hợp nhất chưa đúng. (c) Sau 30 ngày được index, cụm `Tier 1` nhận < 10 lượt hiển thị → cần xem lại bộ từ khóa.

## Sources

1. `.agent/skills/sunext-semrush-ahrefs-os/scripts/engines/suite3_technical_site_audit_spider.py` — `audit_single_page`.
2. `.agent/skills/sunext-semrush-ahrefs-os/scripts/engines/suite8_brand_radar_llms_and_botlog_engine.py` — `audit_robots_txt_for_ai_crawlers`, `compute_autogeo_geu_score`.
3. `.agent/skills/sunext-semrush-ahrefs-os/scripts/engines/suite6_content_schema_readability_engine.py` — `compute_bilingual_readability`.
4. `.agent/skills/sunext-semrush-ahrefs-os/references/08_geo_aeo_llm_citation_and_schema_playbook.md` — khối trả lời trực tiếp, `Service + FAQPage`.
5. `wordpress/app/modules/autodescription/inc/classes/meta/schema.class.php` — filter `the_seo_framework_schema_graph_data` (TSF 5.2.0).
6. `wordpress/app/modules/autodescription/inc/classes/meta/robots.class.php` — TSF chỉ in robots meta khi `blog_public = 1`.
7. `wordpress/app/themes/blocksy/inc/dynamic-styles/global.php` — CSS WooCommerce động của Blocksy.
8. `.agent/rules/jn-project.md` — Project Context Card (UNK-04, UNK-07, UNK-13, PACKAGE PRICES).
