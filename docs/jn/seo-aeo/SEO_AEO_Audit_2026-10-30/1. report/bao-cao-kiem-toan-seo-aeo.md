# Báo Cáo Kiểm Toán Lại & Tối Ưu SEO / AEO — Website Jannie Nguyễn (Step 14)

> **Thời điểm:** 2026-10-30 · **Phạm vi:** source code dự án (`app/advanced-cache.php`, `mu-plugins/jn-performance`, child theme `jannie`, cấu hình `deploy/`) + bản render local `http://localhost:8080` + hiện trạng off-page của domain cũ.
> **Công cụ:** skill `sunext-semrush-ahrefs-os` v5.0 (Suite 2 cosine/cannibalization, Suite 3 `audit_single_page`, Suite 4 `link-graph`/`backlink-sources`, Suite 6 readability, Suite 8 GEU/bot AI) + checklist skill `ecc:seo` (crawlability, indexability, canonical, heading, schema khớp nội dung, keyword ↔ URL) + review độc lập (agent `php-reviewer`).
> **Kế thừa:** Step 10 (28/10) đã đưa số lỗi từ `12/5/23/20` về `0/0/1/11`. Đợt này đo lại sau Step 11–13 và commit `6d109e0`, rồi tìm những gì audit tự động bỏ sót.
> **Nhãn:** `[FACT]` kiểm chứng trực tiếp · `[INFERENCE]` suy luận · `[ASSUMPTION]` giả định chờ duyệt · `[UNKNOWN]` thiếu dữ kiện.

---

## 1. Tóm tắt điều hành

| Chỉ số | Trước | Sau | Bằng chứng |
|---|---|---|---|
| Site local | **HTTP 500 mọi URL** (ký tự `g` lạc ở `advanced-cache.php:26`, thay đổi chưa commit) | 200 | `php -l` `[FACT]` |
| Audit tự động CRITICAL / HIGH / MEDIUM / LOW | 0 / 0 / 1 / 11 | 0 / 0 / 3 / 11 | [baseline](audit-baseline-prodrobots.md) → [sau](audit-after-prodrobots.md) `[FACT]` |
| Biến thể URL `/slug`, `//slug/`, `/SLUG/` xử lý đúng (`JN-URL-01`, mới) | `/coaching` 200 từ cache, `//coaching/` **trả HTML trang chủ** | **24/24** (301 hoặc canonical đúng, không qua cache) | F14-01 `[FACT]` |
| Trang được index hiển thị khung `[CẦN NỘI DUNG: …]` | **5/9** | 0 (khách) · vẫn hiện với người sửa trang | F14-03 `[FACT]` |
| Tên tổ chức trong schema | 1 (`Jannie Nguyễn Academy`) | 1 + 3 `alternateName` khớp cách site đang gọi | F14-02 `[FACT]` |
| Cặp trang trùng ý định (cosine ≥ 0.84) | 1: `/` ↔ `/coaching/` (0.846, lặp 93% nội dung) | 1 — **đề xuất, chưa áp dụng** (lệch wireframe) | [page-overlap.md](page-overlap.md) `[FACT]` |
| Backlink HTML xác nhận được về `jannieedu.com` | — | 0 | [bản nháp backlink](../3.%20draft/nguon-backlink-va-trich-dan.md) `[FACT]` |
| Kiểm thử tự động | 15 bộ PHP, 815 assertion | **833/833** (thêm 18 của Step 14) + fingerprint 31/31, header 50/50, hồi quy Step 9 100%, Lighthouse CI 22/22 | §5 `[FACT]` |

> [!NOTE]
> MEDIUM tăng 1 → 3 là **hệ quả có chủ đích** của F14-03: hai trang pháp lý chỉ còn tiêu đề sau khi ẩn khung giữ chỗ, nên bị gắn `AUD-CNT-01` Thin Content. Cảnh báo tự hết khi có văn bản pháp lý (UNK-09). MEDIUM thứ ba (`/cart/` mỏng) có từ trước và chấp nhận được vì trang này `noindex`.

> [!WARNING]
> **Rủi ro bảo mật ngoài codebase:** WordPress cũ ở `jannieedu.com` (103.82.37.170) **vẫn đang chạy**, chứng chỉ TLS hết hạn 2026-08-15, và có dấu hiệu từng bị xâm nhập: một trang spam liệt kê `wp-login.php` kèm chuỗi giống thông tin đăng nhập; Wayback từng lưu một URL spam bị chèn. Nên đổi mật khẩu admin, hosting và iNET, rồi gỡ WordPress cũ khỏi mạng, dù chọn domain nào (§6).

## 2. Phương pháp & phân vai hai skill

| Việc | `sunext-semrush-ahrefs-os` | `ecc:seo` |
|---|---|---|
| Kỹ thuật | Suite 3 qua [`scripts/seo-audit-local.py`](../../../../../scripts/seo-audit-local.py). Crawler của skill chặn `localhost` (SSRF guard), nên giữ nguyên chặn đó và chỉ dùng các hàm phân tích thuần. | Đọc code để tìm nguyên nhân: canonical, redirect, robots, sitemap, cache. Mỗi phát hiện ghi `[MỨC] Location / Issue / Fix`. |
| Trùng lặp | `char_ngrams` + `cosine_counter`, ngưỡng 0.84 ([`page_overlap_audit.py`](../2.%20python/page_overlap_audit.py)) | Mỗi URL một ý định chính, bảng keyword → URL (dùng lại 705 từ khóa / 11 cụm của Step 10) |
| AEO | Suite 8: GEU, quyền bot AI | Schema phải khớp nội dung hiển thị; thống nhất thực thể |
| Off-page | Suite 4 `link-graph`, `backlink-sources` + Wayback CDX, Common Crawl, sitemap cũ | — |

**Lưu ý phương pháp `[FACT]`:** cosine cụm 3 ký tự **không dùng được cho thân bài** tiếng Việt. Hai đoạn văn bất kỳ đã chung phần lớn cụm 3 ký tự, nên mọi cặp trang đều ra 0.88–0.99. Script vì vậy chỉ dùng cosine cho tiêu đề và heading (ý định), còn thân bài đo bằng tỷ lệ cụm 5 từ lặp nguyên văn.

## 3. Phát hiện & xử lý

| # | Mức | Phát hiện `[FACT]` | Vị trí | Xử lý | Trạng thái |
|---|---|---|---|---|---|
| F14-00 | CRITICAL | Ký tự `g` lạc làm toàn site lỗi 500 (thay đổi chưa commit) | `app/advanced-cache.php:26` | Xóa ký tự | ✅ |
| F14-01 | HIGH | Fast path của page cache ghép khóa bằng `trim( parse_url( URI ), '/' )`. Hậu quả: `/coaching` và `/COACHING/` nhận bản cache 200 thay vì 301, còn `//coaching/` bị đọc thành path `/` nên **trả HTML trang chủ**. Production cũng đi qua drop-in này: `deploy/` không phục vụ file supercache trực tiếp. | `app/advanced-cache.php:57-59` (cũ) | Path nào không phải `/` hay permalink chữ thường có `/` cuối thì đi vào nhánh bypass có sẵn, để WordPress tự 301 (ADR-020) | ✅ |
| F14-03 | HIGH | 5/9 trang index (`/my-story/`, `/course/`, `/client-success-story/`, 2 trang pháp lý) hiển thị khung `[CẦN NỘI DUNG: …]` cho khách và bot | Nội dung trang (seed `build-step-3.php`) | **Chủ dự án chọn "Ẩn với khách"**: module `JN_SEO_Placeholders` ẩn khung (cả trong excerpt/kết quả tìm kiếm); người có quyền `edit_pages` vẫn thấy; khung tự hiện lại khi được điền nội dung thật | ✅ |
| F14-02 | LOW | Schema gọi tổ chức là "Jannie Nguyễn Academy", trong khi trang dùng "Jannie Nguyen Academy" (39 lần) và "Học viện Jannie Nguyễn" (8 lần) | `class-jn-seo-schema.php` | `alternateName` liệt kê đủ các tên đang dùng (nguồn: `JN_SEO_Brand::get()['org_alternate_names']`) | ✅ |
| F14-04 | MEDIUM | `/` và `/coaching/` trùng ý định (cosine 0.846) và lặp 93% nội dung: trang chủ chính là `/coaching/` bỏ phần FAQ | Bố cục trang chủ (wireframe) | Đề xuất biến trang chủ thành hub ([bản nháp](../3.%20draft/de-xuat-kien-truc-noi-dung-va-chong-trung-lap.md) §1) | ⏳ Chờ duyệt |
| F14-05 | MEDIUM | `/course/` mang khối "Lợi Ích Của Chương Trình Coaching", tức ý định coaching trên trang khóa học | Nội dung `/course/` | Thay khi có UNK-08 và thêm schema `Course` (bản nháp §2) | ⏳ UNK-08 |
| F14-06 | LOW | `/booking/`: H1 và H2 của form gần trùng nhau | Pattern form dùng chung | Đổi H2 trên riêng `/booking/` (bản nháp §3) | ⏳ Chờ duyệt |
| F14-07 | HIGH (bảo mật) | WordPress cũ `jannieedu.com` còn chạy, TLS hết hạn, nghi từng bị xâm nhập | Ngoài repo | Việc của chủ dự án (§6) | ⏳ Chủ dự án |
| — | MEDIUM | Thin Content trên 2 trang pháp lý (hệ quả F14-03) | — | Tự hết khi có UNK-09 | Chấp nhận |
| — | LOW | Thiếu header `Content-Security-Policy` (11 URL) | Docker local | `deploy/apache-litespeed.htaccess` đã gửi `Content-Security-Policy-Report-Only`; bật enforce cần thiết kế riêng cho WooCommerce Blocks | Ghi nhận |

**Đã kiểm, không có lỗi `[FACT]`:**
- Title 44–58 ký tự, description 118–160 ký tự, đúng 1 H1/trang, alt ảnh đầy đủ.
- Canonical tự tham chiếu; sitemap = đúng 9 trang index; robots.txt production không chặn trang `noindex`.
- `/llms.txt` khớp các trang đã xuất bản; JSON-LD một đồ thị, `@id` toàn vẹn, không có dữ liệu bịa (giá, đánh giá, điện thoại).
- `/shop/` 301 sang `/my-favorite-product/`; feed, `/wp-sitemap.xml`, `/?p=1` trả 404; 404 thật trả mã 404.
- Liên kết nội bộ: 7 mục menu phủ mọi trang; anchor rỗng chỉ có ở logo và icon tài khoản (đều có `aria-label`).

**Ghi nhận LOW, chưa làm (YAGNI, chờ có nhu cầu):**
- `og:type` của `/my-story/` có thể là `profile`.
- `Service.url` dùng deep-link `?goi=…#booking-form`, đã canonical về `/coaching/` và hữu ích cho agent AI.
- `Disallow: /*?add-to-cart=` chỉ bắt khi tham số này đứng đầu.
- `HTTP_HOST` chưa được kiểm tra ký tự trước khi ghép vào đường dẫn file cache. Có từ trước; URI đã bị giới hạn chặt nên không khai thác được.

## 4. Thay đổi mã nguồn

| File | Thay đổi |
|---|---|
| `wordpress/app/advanced-cache.php` | Gỡ ký tự lạc (F14-00); quy tắc bypass cho path không chuẩn `#^(?:/[a-z0-9_-]+)+/\z#` (F14-01); docblock ghi rõ giới hạn với slug percent-encoded |
| `wordpress/app/mu-plugins/jn-performance/class-jn-seo-placeholders.php` (mới) | Filter `render_block` (ẩn group scaffold *còn* chuỗi đánh dấu, đoạn văn đánh dấu, wrapper rỗng) + `get_the_excerpt` (tạo excerpt qua `wp_trim_excerpt()` của core trên bản đã gỡ khung) |
| `wordpress/app/mu-plugins/jn-performance.php` | Nạp module SEO thứ 7 |
| `wordpress/app/mu-plugins/jn-performance/class-jn-seo-brand.php`, `class-jn-seo-schema.php` | `org_alternate_names` → `Organization.alternateName` |
| `scripts/test-step-14-seo-aeo.php` (mới) | 18 kiểm thử: biến thể URL (gồm host tunnel thật), entity, scaffold qua fixture (đang chờ / đã điền / editor / lời dẫn H1 / excerpt), HTTP 200 trên 5 trang |
| `scripts/seo-audit-local.py` | Kiểm tra `JN-URL-01` (24 biến thể, bắt cả trường hợp cache HIT) |
| `docs/jn/decisions.md` | ADR-020 |

## 5. Kiểm chứng

| Bộ kiểm thử | Kết quả |
|---|---|
| `test-step-14-seo-aeo.php` (mới, RED → GREEN) | **18 / 18** |
| 15 bộ PHP hiện có (step 5, 6, 7, 8, 10, 11a, 11c, 11d, 11e, 12, 13, dev-tunnel, legacy-media, placeholder-media, hero-overlay) | **815 / 815** |
| `jn-fingerprint-scan.mjs` (Hide-WordPress) | 31 / 31 |
| `verify-step-13-headers.mjs` (10 trang × 5 độ rộng) | 50 / 50 |
| `verify-step-9-regression.mjs` (axe 13 trang, 4 độ phân giải, bàn phím, 3 trình duyệt, 92 link, 404, 15 email) | 100% PASS — không để lại lịch hẹn/đơn hàng test |
| Lighthouse CI (22 mục tiêu × 3 lần đo, trung vị) | **22 / 22 PASS** — trang nội dung Perf 99–100 / A11y 100 / BP 100 / SEO 100 (mobile & desktop); 3 trang tiện ích SEO 66–69 do `noindex` (ngoại lệ ADR-013) — [`docs/jn/lighthouse/step-14/summary.json`](../../../lighthouse/step-14/summary.json) |
| Skill self-test (`test_all_8_suites.py` + 3 `--self-test`) | Exit 0 |

Bằng chứng chạy lại nằm ở [`regression/`](regression/); bằng chứng gốc trong `docs/jn/` được giữ nguyên. Review độc lập: 0 CRITICAL; đã xử lý cả 2 HIGH (khung có class nhưng đã điền nội dung bị ẩn nhầm; test tunnel không gửi đúng Host) và 5 MEDIUM (excerpt lộ khung — đã tái hiện rồi sửa; test thiếu kiểm mã 200; test editor phụ thuộc nội dung thật; audit chưa bắt cache HIT; production Nginx chưa được kiểm).

## 6. Off-page & lựa chọn domain

Chi tiết: [bản đồ 301](../3.%20draft/ban-do-301-jannieedu.md), [nguồn backlink](../3.%20draft/nguon-backlink-va-trich-dan.md).

- **Kiểm kê:** 485 URL HTML cũ, lấy từ Wayback CDX, sitemap cũ còn sống và Common Crawl. 87% trong số đó là nội dung demo của theme Edumall/Tutor LMS, đề xuất trả 410 theo nhóm `[FACT]`.
- **Trùng đường dẫn:** 10/12 đường dẫn công khai của site mới đã có y nguyên trên site cũ. Nếu production là `jannieedu.com`, các trang lõi không cần redirect `[FACT]`.
- **Đã có redirect:** `/shop/` và `/my-account/` được theme 301 sẵn (`jannie_redirect_legacy_woo_slugs()`).
- **Link equity thấp:** không có backlink HTML xác nhận. Tín hiệu đáng giữ là link `jannieedu.com/bio/` trong bio TikTok (mới thấy qua snippet) và một bài báo NTD Online có nhắc tên nhưng không có link (cơ hội xin link) `[INFERENCE]`.
- **Domain thứ hai:** `janniehongnhung.com`, hiện trả 403. Domain nào không được chọn làm production phải 301 về domain chính.
- **Catalog backlink của skill ít phù hợp:** không có nhóm beauty, chỉ 25 nguồn gắn tag VN. Trong top 30, giữ 2 nguồn, giữ có điều kiện 5, loại 23. Ưu tiên thực tế: Google Business Profile (cần UNK-04), Facebook/Instagram/YouTube/TikTok (sửa link bio), Bing Places, Apple Business Connect, Trang Vàng.

## 7. Việc cần chủ dự án quyết định / cung cấp

1. **Domain production** (`jannieedu.com` hay domain mới) và **ai đang giữ tài khoản iNET / P.A. Việt Nam**. Quyết định này mở khóa việc áp bản đồ 301 và crawl live.
2. **Bảo mật site cũ:** đổi mật khẩu, gỡ WordPress cũ, không mang database cũ sang site mới.
3. **Trang chủ thành hub** (F14-04): cần duyệt vì lệch wireframe.
4. Nội dung còn thiếu: UNK-08 (khóa học), UNK-09 (pháp lý), UNK-04 (NAP → `LocalBusiness`, Google Business Profile), UNK-07 (ảnh OG), UNK-13 (mạng xã hội → `sameAs`), giá gói (→ `Offer`).
5. Các giả định vẫn giữ từ Step 10: Q2 cho phép mọi bot AI; Q3 giữ `index` cho trang pháp lý; giữ slug tiếng Anh.
6. **Khi deploy:** purge WP Super Cache, và cả Cloudflare nếu có cache HTML, vì bản cache cũ còn khung giữ chỗ tối đa 1 giờ. Nên chạy lại `seo-audit-local.py --base https://<domain>` trên staging Nginx, vì local chạy Apache.

## 8. Counter-Evidence · Confidence · Falsification

- **Counter-Evidence:**
  - GEU giảm trên 5 trang có khung (ví dụ `/my-story/` 61.6 → 57.7, trang pháp lý ~32 → ~24), vì heuristic của skill cộng điểm theo độ dài chữ. Chất lượng thật tăng vì bot không còn đọc thấy chữ giữ chỗ `[INFERENCE]`.
  - Cosine và GEU là ước lượng Mode A, không phải thứ hạng hay Search Volume thật `[FACT]`.
  - Audit chạy trên Docker Apache; production Nginx có thể xử lý `//path` khác. Nginx vẫn chuyển `REQUEST_URI` gốc cho PHP nên quy tắc bypass vẫn áp dụng `[INFERENCE]`.
- **Confidence:** cao (~90%) cho các sửa lỗi kỹ thuật (có test RED → GREEN và hồi quy đầy đủ); trung bình (~60%) cho tác động thứ hạng của đề xuất nội dung `[INFERENCE]`.
- **Falsification:**
  - (a) Sau go-live, Search Console báo `/coaching` hoặc `//coaching/` là "Duplicate without user-selected canonical" → F14-01 chưa có hiệu lực trên production.
  - (b) Tìm `site:<domain> "CẦN NỘI DUNG"` mà ra kết quả → F14-03 thất bại (hoặc cache cũ chưa được purge).
  - (c) Sau 60 ngày, `/coaching/` vẫn không nhận lượt hiển thị cho cụm "cố vấn 1:1 ngành thẩm mỹ" trong khi trang chủ nhận → cần làm F14-04.

## Sources

1. `~/.claude/skills/sunext-SEO-AEO/scripts/engines/suite3_technical_site_audit_spider.py` — `audit_single_page`.
2. `~/.claude/skills/sunext-SEO-AEO/scripts/engines/suite8_brand_radar_llms_and_botlog_engine.py` — `audit_robots_txt_for_ai_crawlers`, `compute_autogeo_geu_score`.
3. `~/.claude/skills/sunext-SEO-AEO/scripts/seo_intent_cluster_and_scoring.py` — `char_ngrams`, `cosine_counter` (ngưỡng 0.84).
4. Skill `ecc:seo` — checklist crawlability/indexability, quy tắc title/description/heading, "schema khớp nội dung".
5. `wordpress/app/modules/wp-super-cache/wp-cache-phase2.php:223` — `$wp_cache_slash_check` (lý do không bật, ADR-020).
6. WordPress core `wp-includes/blocks.php` — `excerpt_remove_blocks()` chỉ render block lá (nguyên nhân lộ khung trong excerpt).
7. [`audit-baseline-*.md`](audit-baseline-prodrobots.md), [`audit-after-*.md`](audit-after-prodrobots.md), [`page-overlap.md`](page-overlap.md), [`regression/`](regression/).
8. [`3. draft/ban-do-301-jannieedu.md`](../3.%20draft/ban-do-301-jannieedu.md), [`3. draft/nguon-backlink-va-trich-dan.md`](../3.%20draft/nguon-backlink-va-trich-dan.md) — Wayback CDX, Common Crawl, `whois`, `openssl s_client` (đã kiểm lại: chứng chỉ `notAfter=Aug 15 14:23:53 2026 GMT`).
