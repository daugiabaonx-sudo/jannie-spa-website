# Bản đồ 301/410 cho jannieedu.com & dữ liệu chọn tên miền production

- **Ngày:** 2026-10-30 · **Trạng thái:** BẢN NHÁP — chưa áp vào `deploy/`, chưa áp vào theme
- **Phạm vi:** kiểm kê URL cũ của `jannieedu.com`, đề xuất đích 301/410 trên site mới, và dữ liệu giúp chủ dự án chọn domain production (`PROD_DOMAIN` trong `deploy/nginx-production.conf` vẫn là placeholder `[FACT]`).
- **Đi kèm:** backlink/mention hiện có và nguồn citation mới nằm ở `nguon-backlink-va-trich-dan.md` (cùng thư mục).

---

## 0. Tóm tắt nhanh

1. **Site cũ chưa "chết hẳn".** `jannieedu.com` vẫn được đăng ký (iNET, hết hạn 2027-06-08) và **WordPress cũ vẫn chạy**, trả HTTP 200 qua HTTPS tại `103.82.37.170`. Chứng chỉ Let's Encrypt **đã hết hạn ngày 2026-08-15**, nên trình duyệt và Googlebot gặp lỗi TLS `[FACT]`. Vì vậy người dùng thấy site như đã chết, dù máy chủ vẫn phục vụ.
2. **Kiểm kê được 485 URL HTML duy nhất.** Con số này đã bỏ 64 biến thể query-string, gộp từ Wayback CDX, sitemap WordPress còn sống và Common Crawl `[FACT]`. Trong đó **423 URL (87%) là nội dung demo của theme Edumall/Tutor LMS** (khoá tiếng Anh về crypto, Python, piano…), không phải nội dung của Jannie `[FACT]`.
3. **Chỉ có 62 URL có ý nghĩa.** Kết quả gán nhãn: **15 `[FACT]`** (10 trang trùng y nguyên đường dẫn với site mới, 2 trang đã có 301 trong theme, 3 trang tương đương chức năng), **23 `[INFERENCE]`**, **24 `[UNKNOWN]`** (đề xuất 410, hoặc chờ chủ dự án quyết). 423 URL demo nhận 410 bằng quy tắc theo nhóm.
4. **Đường dẫn của site mới gần như trùng site cũ.** 10/12 đường dẫn công khai của site mới đã tồn tại y nguyên trên site cũ: `/`, `/my-story/`, `/coaching/`, `/course/`, `/client-success-story/`, `/booking/`, `/my-favorite-product/`, `/chinh-sach-bao-mat/`, `/cart/`, `/checkout/` `[FACT]`. Nếu production là `jannieedu.com`, các trang lõi **không cần redirect nào**.
5. **Link equity thấp.** Không tìm thấy backlink HTML nào xác nhận được. Tín hiệu đáng giữ chủ yếu là thương hiệu: hashtag `#jannieedu` và link `jannieedu.com/bio/` trong bio TikTok (mới thấy qua snippet, chưa xác nhận được) `[INFERENCE]`. Chi tiết ở file `nguon-backlink-va-trich-dan.md`.
6. **Rủi ro bảo mật.** Một trang spam bên thứ ba liệt kê `jannieedu.com/wp-login.php` kèm chuỗi trông như thông tin đăng nhập. Năm 2024 Wayback còn bắt được một URL bài spam bị chèn `[FACT]`. Từ đó suy ra WordPress cũ **có thể đã từng bị xâm nhập** `[INFERENCE]`. Dù chọn domain nào, nên đổi mật khẩu và gỡ WordPress cũ khỏi mạng.
7. **Có thêm một domain cũ thứ hai: `janniehongnhung.com`.** Domain này đăng ký từ 2021-09-12 đến 2028-09-12, hiện trả HTTP 403. Trang `/bio/` cũ gắn nhãn nó là "Blog cá nhân" `[FACT]`, Wayback lưu 24 URL. Domain nào không được chọn làm production cũng phải 301 về domain chính (phụ lục §7).

---

## 1. Hiện trạng tên miền cũ — dữ liệu để chọn domain

| Hạng mục | Giá trị | Nhãn | Nguồn |
|---|---|---|---|
| Registrar `jannieedu.com` | iNET Software Company Limited, NS `ns1-3.inet.vn` | `[FACT]` | `whois`, `dig NS` |
| Ngày tạo / hết hạn | Creation **2025-06-08**, Expiry **2027-06-08** | `[FACT]` | `whois` |
| Wayback có bản lưu từ 2023-03-10 nhưng creation date là 2025-06-08 | Domain có thể đã **hết hạn rồi được đăng ký lại** vào 2025-06-08. Nội dung Jannie vẫn liên tục đến 2026-07-11 nên nhiều khả năng chính khách hàng đăng ký lại. | `[INFERENCE]` | CDX + `whois` |
| Ai đang giữ tài khoản iNET | Chưa biết | `[UNKNOWN]` | Cần chủ dự án xác nhận |
| DNS `A` | `103.82.37.170` (cả apex và `www`) | `[FACT]` | `dig` |
| HTTP | `http://` → 301 → `https://` (Apache) | `[FACT]` | `curl -I` |
| HTTPS | **200**, WordPress cũ còn chạy (`link: …/wp-json/wp/v2/pages/5849`) | `[FACT]` | `curl -k -I` |
| Chứng chỉ TLS | Let's Encrypt R12, `notAfter=Aug 15 2026` → **đã hết hạn** | `[FACT]` | `openssl s_client` |
| `robots.txt` cũ | Chỉ chặn `/wp-admin/`, khai báo `wp-sitemap.xml` | `[FACT]` | fetch trực tiếp |
| Tỷ lệ nội dung demo | 423/485 URL (87%) là demo Edumall/Tutor LMS. Common Crawl 2025-11 và 2026-03 vẫn crawl được các URL này với mã 200. | `[FACT]` | inventory §2 |
| Backlink HTML xác nhận được | 0 | `[FACT]` (trong phạm vi kênh mở) | skill `link-graph` |
| "DR" ước lượng | 32/100. Đây là heuristic của skill, không phải số đo Ahrefs/Moz. | `[INFERENCE]` | skill `link-graph` |
| Domain thứ hai | `janniehongnhung.com`: P.A. Việt Nam, tạo 2021-09-12, hết hạn 2028-09-12, HTTP 403, HTTPS self-signed | `[FACT]` | `whois`, `curl -I` |

### Hai phương án

| Tiêu chí | A. Production = `jannieedu.com` | B. Production = domain mới |
|---|---|---|
| Redirect cho 10 trang lõi | Không cần (trùng đường dẫn) `[FACT]` | Cần 301 toàn domain, từng đường dẫn |
| Giữ được link trong bio/hashtag mạng xã hội | Có, nhưng `/bio/` vẫn cần 301 | Chỉ giữ được nếu domain cũ được gia hạn và 301 lâu dài |
| Lịch sử nội dung rác (demo, nghi bị chèn spam) | Kế thừa. Cần 410 nhanh và theo dõi GSC (Security issues, Manual actions). | Tách khỏi lịch sử. Domain cũ chỉ làm nhiệm vụ 301. |
| Phù hợp tên thương hiệu | "edu" khớp với "Học viện đào tạo" (cách gọi trên `/bio/` cũ) `[FACT]`. Thương hiệu hiển thị của site mới là "Jannie Nguyễn". | Tuỳ tên mới |
| Việc phải làm thêm | Trỏ DNS sang hosting mới, cấp TLS mới, tắt WordPress cũ | GSC Change of Address, giữ 301 ít nhất 1 năm, gia hạn cả hai domain cũ |

**Nhận định `[INFERENCE]`:** xét riêng off-page, `jannieedu.com` mang ít link equity, chủ yếu là tính liên tục thương hiệu. Chọn A ít rủi ro **nếu** chủ dự án xác nhận đang giữ tài khoản iNET và chấp nhận 410 toàn bộ URL demo. Chọn B cũng không mất bao nhiêu equity, miễn là giữ 301 từ cả hai domain cũ. Quyết định cuối nên dựa vào thương hiệu và quyền sở hữu domain hơn là backlink.

**Việc cần làm ngay, dù chọn phương án nào:**
1. Đổi mật khẩu mọi tài khoản admin của WordPress cũ, tài khoản hosting và tài khoản iNET.
2. Không mang database cũ sang site mới.
3. Gỡ WordPress cũ khỏi mạng, hoặc thay bằng máy chủ chỉ làm 301. WordPress cũ hiện vẫn mở `wp-login.php` và `xmlrpc.php?rsd`.

---

## 2. Kiểm kê URL cũ

### 2.1 Phương pháp

- **Wayback CDX:** gọi trực tiếp `url=jannieedu.com/&matchType=prefix&collapse=urlkey` (677 dòng, 516 dòng `text/html`). Thêm một truy vấn không collapse để lấy ngày capture đầu và cuối (3.425 capture HTML). `*.jannieedu.com` chỉ có thêm `erp.jannieedu.com` (2023-10, trang login ERP, không thuộc site). Truy vấn wildcard `jannieedu.com*` bị 504, và về sau Wayback trả "Temporarily Offline" `[FACT]`.
- **Sitemap WordPress còn sống:** `https://jannieedu.com/wp-sitemap.xml` có 13 sub-sitemap, 477 `<loc>`. Phải fetch bằng `curl -k` vì cert hết hạn, và chỉ đọc XML như dữ liệu thụ động `[FACT]`. Không có sitemap `post`, nên site **không có bài blog nào được xuất bản** `[FACT]`. `wp-json/wp/v2/posts` cũng trả `[]`.
- **Common Crawl CDX:** `CC-MAIN-2025-47` có 170 dòng, `CC-MAIN-2026-12` có 94 dòng. Các index 2026-43, 2026-30, 2025-30 và 2024-46 trả 504; mỗi index chỉ thử lại một lần.
- **Chuẩn hoá:** bỏ scheme, host và `www`; thêm `/` cuối; tách query. Loại `wp-content`, `wp-includes`, `wp-json`, `wp-admin`, `wp-login.php`, `xmlrpc.php`, feed, và các URL dò quét `/.price`, `/.product`, `/3.14.0`.

### 2.2 Phân loại 485 URL

| Nhóm | Số URL | Của Jannie | Demo Edumall/Tutor | Hệ thống plugin / khác |
|---|---:|---:|---:|---|
| home | 1 | 1 | – | – |
| about | 3 | 3 (`/my-story/`, `/bio/`, `/chung-nhan-tutor/`) | – | – |
| course/lesson | 387 | 5 (`/coaching/`, `/course/`, `/courses/phun-xam-tong-hop/` + 2 lesson) | 381 + 1 archive `/courses/` | – |
| product | 16 | 16 (14 `/san-pham/*`, `/shop/`, `/my-favorite-product/`) | – | – |
| blog post | 2 | 0 bài thật | – | `/blog/` (trang placeholder tiếng Anh) và 1 URL spam (đã 404 từ 2024-03) |
| contact | 2 | 2 (`/booking/`, `/home-booking/`) | – | – |
| category/tag | 25 | 2 (`/course-category/phun-xam/`, `/danh-muc-san-pham/mac-dinh/`) | 23 (`/course-language/*` 8, `/faq-group/*` 15) | – |
| account/cart | 25 | – | – | 25 (`/cart/`, `/checkout/`, `/my-account/`, Tutor `/dashboard/` `/user-*` `/student-registration/` `/reset-password/`, CartFlows `/step/*` 16) |
| other | 24 | 2 (`/client-success-story/`, `/khach-hang/`) | 19 (`/faq/*`) | 3 trang pháp lý |
| **Tổng** | **485** | **31** | **424** | **30** |

Chi tiết 423 URL demo: 237 `lesson`, 79 `assignments`, 61 `tutor_quiz`, 4 trang gốc khoá học, 19 `faq`, 15 `faq-group`, 8 `course-language`. Tổng cộng 80 slug khoá demo; chỉ `phun-xam-tong-hop` là của Jannie `[FACT]`.

### 2.3 URL nào đang mang equity

- Không URL nào có backlink HTML xác nhận được `[FACT]`.
- `/bio/`: snippet DuckDuckGo của hồ sơ TikTok `@jannie_hongnhung` chứa `https://jannieedu.com/bio/`. Trang TikTok render bằng JS nên **chưa xác nhận được** link `[INFERENCE]`. Đây là URL ưu tiên số 1 cần 301.
- `/`: hashtag `#jannieedu` và `#jannieedu.com` xuất hiện trên Facebook, YouTube, Instagram, TikTok. Đây là mention không có link.
- Các trang lõi (`/coaching/`, `/course/`, `/booking/`…) được Wayback capture lặp lại đến 2026-07 và được Common Crawl crawl, chứng tỏ có link nội bộ hoặc ngoài trỏ tới. Không có dữ liệu GSC để đo lượt click `[UNKNOWN]`.

---

## 3. Bản đồ 301/410 (bảng)

Quy ước: "Đích" là đường dẫn trên site mới. Nếu production ≠ `jannieedu.com`, dùng các dòng này trên server của domain cũ, với đích tuyệt đối `https://PROD_DOMAIN/...` (§6).

### 3.1 Nhóm A — trùng đường dẫn hoặc đã có redirect (15 × `[FACT]`)

| URL cũ | Đích | Nhãn | Ghi chú |
|---|---|---|---|
| `/` | `/` | `[FACT]` | trùng đường dẫn |
| `/my-story/` | `/my-story/` | `[FACT]` | trùng đường dẫn |
| `/coaching/` | `/coaching/` | `[FACT]` | trùng đường dẫn |
| `/course/` | `/course/` | `[FACT]` | trùng đường dẫn |
| `/client-success-story/` | `/client-success-story/` | `[FACT]` | trùng đường dẫn |
| `/booking/` | `/booking/` | `[FACT]` | trùng đường dẫn |
| `/my-favorite-product/` | `/my-favorite-product/` | `[FACT]` | trùng đường dẫn (site mới dùng làm trang shop WooCommerce) |
| `/chinh-sach-bao-mat/` | `/chinh-sach-bao-mat/` | `[FACT]` | trùng đường dẫn |
| `/cart/` | `/cart/` | `[FACT]` | trùng đường dẫn |
| `/checkout/` | `/checkout/` | `[FACT]` | trùng đường dẫn (302 về `/cart/` khi giỏ trống, đúng hành vi WooCommerce) |
| `/shop/` | `/my-favorite-product/` | `[FACT]` | **Đã có**: `jannie_redirect_legacy_woo_slugs()` trong `themes/jannie/inc/woocommerce.php` |
| `/my-account/` | `/tai-khoan/` | `[FACT]` | **Đã có**: cùng hàm trên |
| `/privacy-policy/` | `/chinh-sach-bao-mat/` | `[FACT]` | Trang privacy mặc định của WordPress, trùng chức năng |
| `/dieu-khoan-dieu-kien/` | `/dieu-khoan-dich-vu/` | `[FACT]` | Cùng là trang điều khoản; nội dung khác nhau |
| `/reset-password/` | `/tai-khoan/lost-password/` | `[FACT]` | Trang đặt lại mật khẩu (Tutor) → endpoint WooCommerce, local trả 200 |

### 3.2 Nhóm B — 301 theo ý định gần nhất (23 × `[INFERENCE]`)

| URL cũ | Đích | Nhãn | Lý do |
|---|---|---|---|
| `/bio/` | `/my-story/` | `[INFERENCE]` | Trang link-in-bio "Jannie Hồng Nhung". TikTok trỏ vào đây. Phương án khác: dựng lại `/bio/` thành trang link hub. |
| `/home-booking/` | `/booking/` | `[INFERENCE]` | Trang "Home Booking" gần như rỗng; ý định là đặt lịch |
| `/khach-hang/` | `/client-success-story/` | `[INFERENCE]` | "Khách hàng", gần như rỗng; gần nhất là trang câu chuyện khách hàng |
| `/courses/` | `/course/` | `[INFERENCE]` | Archive Tutor LMS (đa số là demo), ý định là danh sách khoá học |
| `/courses/phun-xam-tong-hop/` | `/course/` | `[INFERENCE]` | Khoá thật duy nhất trên Tutor LMS |
| `/courses/phun-xam-tong-hop/lesson/cat-toc-bang-dao/` | `/course/` | `[INFERENCE]` | Lesson của khoá thật |
| `/courses/phun-xam-tong-hop/lesson/thay-doi-ngoan-muc/` | `/course/` | `[INFERENCE]` | Lesson của khoá thật |
| `/course-category/phun-xam/` (kèm `?tutor-course-filter-category=301`) | `/course/` | `[INFERENCE]` | Danh mục "phun xăm" |
| `/san-pham/coaching-11-phun-xam-spa-salon/` | `/coaching/` | `[INFERENCE]` | Sản phẩm "Coaching 1:1 (Phun xăm – Spa – Salon)", khớp chương trình cố vấn 1:1 trên `/coaching/` |
| `/san-pham/tu-van-11-ve-dep-ca-nhan/` | `/coaching/` | `[INFERENCE]` | "Tư vấn 1:1 vẻ đẹp cá nhân"; `/coaching/` mới có "Tư vấn 1:1" |
| `/san-pham/cuoc-hen/` | `/booking/` | `[INFERENCE]` | Sản phẩm 0₫ "Cuộc hẹn" mà hệ thống đặt lịch cũ dùng |
| `/san-pham/phun-xam-tong-hop/` | `/course/` | `[INFERENCE]` | Có trong danh sách khoá học trên `/course/` cũ |
| `/san-pham/ky-thuat-granulation-tong-hop/` | `/course/` | `[INFERENCE]` | Có trong danh sách khoá học trên `/course/` cũ. `/course/` mới chưa nhắc "granulation". |
| `/san-pham/phun-may-granulation/` | `/course/` | `[INFERENCE]` | như trên |
| `/san-pham/phun-chan-toc-granulation/` | `/course/` | `[INFERENCE]` | như trên |
| `/san-pham/cheek-blush/` | `/course/` | `[INFERENCE]` | như trên |
| `/san-pham/chinh-sua-long-may/` | `/course/` | `[INFERENCE]` (yếu) | Có giá như một dịch vụ. `/course/` cũ chỉ nhắc "chỉnh sửa lông mày" trong mô tả granulation. Nếu chủ dự án không còn dạy hoặc làm dịch vụ này → 410. |
| `/danh-muc-san-pham/mac-dinh/` (25 biến thể `?orderby=`, `?min_price=`…) | `/my-favorite-product/` | `[INFERENCE]` | Danh mục sản phẩm "Mặc định" → trang shop |
| `/dashboard/` | `/tai-khoan/` | `[INFERENCE]` | Dashboard học viên Tutor → tài khoản WooCommerce |
| `/user-account/` | `/tai-khoan/` | `[INFERENCE]` | như trên |
| `/user-login/` | `/tai-khoan/` | `[INFERENCE]` | Trang tài khoản hiển thị form đăng nhập |
| `/user-register/` | `/tai-khoan/` | `[INFERENCE]` | Chưa kiểm tra site mới có bật đăng ký hay không |
| `/student-registration/` | `/tai-khoan/` | `[INFERENCE]` (yếu) | Đăng ký học viên Tutor. Phương án khác: 410. |

### 3.3 Nhóm C — không có tương đương (24 `[UNKNOWN]` + 423 demo)

| URL cũ | Đề xuất | Nhãn | Lý do |
|---|---|---|---|
| `/san-pham/egf-cleansing-gel/` | **Chờ chủ dự án**: mặc định 410; nếu dòng "The Balance" còn bán → 301 `/my-favorite-product/` | `[UNKNOWN]` | `/my-favorite-product/` mới chỉ ghi "đang tuyển chọn", không có sản phẩm EGF, nên 301 sang đó dễ bị coi là soft-404 |
| `/san-pham/egf-peeling-gel/` | như trên | `[UNKNOWN]` | như trên |
| `/san-pham/egf-melting-mask/` | như trên | `[UNKNOWN]` | như trên |
| `/san-pham/collagen-ampoule/` | như trên | `[UNKNOWN]` | như trên |
| `/san-pham/herpes-treatment/` | **410** | `[UNKNOWN]` | Tên sản phẩm mang tính điều trị. Nên rà pháp lý quảng cáo trước khi dùng lại `[ASSUMPTION]`. |
| `/chung-nhan-tutor/` | **410** | `[UNKNOWN]` | Trang hệ thống chứng nhận của Tutor LMS, không có nội dung |
| `/step/*` (16 URL CartFlows checkout/thank-you) | **410** | `[UNKNOWN]` | Phễu thanh toán của sản phẩm cũ; trang thank-you không nên được index |
| `/revolutionising-change-the-brand-new-effect-…-monetary-field/` | **410** | `[UNKNOWN]` | URL bài spam bị chèn, đã 404 từ 2024-03 |
| `/blog/` | **Không thêm rule (để 404 tự nhiên)** | `[UNKNOWN]` | Nội dung placeholder tiếng Anh, 0 bài viết. Không dùng 410 vì audit 2026-10-28 đề xuất mở blog sau này, có thể dùng lại `/blog/`. |
| 381 URL `/courses/<demo>/…` (lesson, quiz, assignments, trang gốc) | **410** (quy tắc nhóm) | `[UNKNOWN]` | Demo Edumall, không liên quan đến Jannie |
| 18 URL `/faq/<slug>/`, `/faq/page/N/` | **410** (regex, chừa `/faq/`) | `[UNKNOWN]` | FAQ demo Edumall. Chừa `/faq/` vì có thể làm trang FAQ thật cho AEO. |
| 15 URL `/faq-group/*` | **410** | `[UNKNOWN]` | Taxonomy demo |
| 8 URL `/course-language/*` | **410** | `[UNKNOWN]` | Taxonomy demo |
| `/faq/` | Không thêm rule (404) | `[UNKNOWN]` | Chừa cho trang FAQ thật trong tương lai |
| `/?ic_mega_menu=homepages` | Không cần rule | `[FACT]` | Query trên `/`, trang chủ mới có canonical. Không tính vào 485 URL vì là biến thể query. |

**Tổng nhãn:** 15 `[FACT]` + 23 `[INFERENCE]` + 24 `[UNKNOWN]` = 62 URL có ý nghĩa. Cộng 423 URL demo nhận 410 theo nhóm, ra 485.

**Nguyên tắc đã áp dụng:** không đổ URL không liên quan về trang chủ. URL demo và URL hệ thống nhận 410 vì đó là cách trung thực nhất. Riêng `/blog/` và `/faq/` để 404 để còn dùng lại sau.

---

## 4. Bản nháp nginx (KHÔNG áp trực tiếp vào `deploy/nginx-production.conf`)

Dán bên trong khối `server { server_name PROD_DOMAIN; … }`, trước `location / {`. Cách nginx chọn rule: `location =` (khớp chính xác) ưu tiên trước; trong các prefix, prefix dài nhất thắng; `^~` chặn bước xét regex. Vì vậy `= /courses/` và `^~ /courses/phun-xam-tong-hop/` thắng `^~ /courses/`. `return` không tự nối query string, nên các biến thể `?orderby=` cũng bị bỏ query.

```nginx
# ===== jannieedu.com legacy map — DRAFT 2026-10-30 (ban-do-301-jannieedu.md) =====
# /shop/ và /my-account/ đã được theme xử lý (jannie_redirect_legacy_woo_slugs) — không lặp lại ở đây.

# --- Nhóm A: tương đương chức năng [FACT]
location = /privacy-policy/        { return 301 /chinh-sach-bao-mat/; }
location = /dieu-khoan-dieu-kien/  { return 301 /dieu-khoan-dich-vu/; }
location = /reset-password/        { return 301 /tai-khoan/lost-password/; }

# --- Nhóm B: ý định gần nhất [INFERENCE]
location = /bio/                   { return 301 /my-story/; }
location = /home-booking/          { return 301 /booking/; }
location = /khach-hang/            { return 301 /client-success-story/; }
location = /courses/               { return 301 /course/; }
location ^~ /courses/phun-xam-tong-hop/ { return 301 /course/; }
location ^~ /course-category/phun-xam/  { return 301 /course/; }
location = /san-pham/coaching-11-phun-xam-spa-salon/ { return 301 /coaching/; }
location = /san-pham/tu-van-11-ve-dep-ca-nhan/       { return 301 /coaching/; }
location = /san-pham/cuoc-hen/                       { return 301 /booking/; }
location = /san-pham/phun-xam-tong-hop/              { return 301 /course/; }
location = /san-pham/ky-thuat-granulation-tong-hop/  { return 301 /course/; }
location = /san-pham/phun-may-granulation/           { return 301 /course/; }
location = /san-pham/phun-chan-toc-granulation/      { return 301 /course/; }
location = /san-pham/cheek-blush/                    { return 301 /course/; }
location = /san-pham/chinh-sua-long-may/             { return 301 /course/; }
location ^~ /danh-muc-san-pham/    { return 301 /my-favorite-product/; }
location = /dashboard/             { return 301 /tai-khoan/; }
location = /user-account/          { return 301 /tai-khoan/; }
location = /user-login/            { return 301 /tai-khoan/; }
location = /user-register/         { return 301 /tai-khoan/; }
location = /student-registration/  { return 301 /tai-khoan/; }

# --- Nhóm C1: sản phẩm "The Balance" — CHỜ CHỦ DỰ ÁN [UNKNOWN]
# Mặc định 410. Nếu dòng sản phẩm còn bán: đổi thành  return 301 /my-favorite-product/;
# Nếu sau này WooCommerce dùng lại base /san-pham/ với cùng slug: XOÁ dòng tương ứng.
location = /san-pham/egf-cleansing-gel/  { return 410; }
location = /san-pham/egf-peeling-gel/    { return 410; }
location = /san-pham/egf-melting-mask/   { return 410; }
location = /san-pham/collagen-ampoule/   { return 410; }
location = /san-pham/herpes-treatment/   { return 410; }

# --- Nhóm C2: demo Edumall / hệ thống / spam [UNKNOWN] → 410
location ^~ /courses/          { return 410; }   # 381 URL demo (exact /courses/ và /courses/phun-xam-tong-hop/ ở trên thắng)
location ^~ /faq-group/        { return 410; }
location ^~ /course-language/  { return 410; }
location ^~ /step/             { return 410; }
location ~ ^/faq/.+            { return 410; }   # chừa /faq/ cho trang FAQ thật
location = /chung-nhan-tutor/  { return 410; }
location = /revolutionising-change-the-brand-new-effect-and-you-may-possible-out-of-artificial-intelligence-regarding-the-monetary-field/ { return 410; }
# /blog/ và /faq/: cố ý KHÔNG có rule → 404 tự nhiên (giữ chỗ cho nội dung tương lai).
# ===== end legacy map =====
```

Lưu ý thêm:
- URL cũ đều có dấu `/` cuối, theo permalink WordPress. Nếu muốn bắt cả dạng không có `/`, đổi `location = /bio/` thành `location ~ ^/bio/?$`.
- `location ~ ^/faq/.+` là regex, nên phải đặt **trước** các regex `location ~` khác trong cùng server block.
- Nếu muốn trang 410 có giao diện, thêm `error_page 410 /410.html;` hoặc để WordPress render. Cần quyết định riêng.

---

## 5. Bản nháp Apache / LiteSpeed (`.htaccess`, KHÔNG áp trực tiếp vào `deploy/apache-litespeed.htaccess`)

Đặt **trước** khối `# BEGIN WordPress`. mod_alias xử lý theo thứ tự xuất hiện, dòng khớp đầu tiên thắng. Lưu ý quan trọng: `Redirect` khớp **theo tiền tố đường dẫn và nối phần còn lại** vào đích (`/bio/x` → `/my-story/x`). Vì vậy các nhóm có URL con dùng `RedirectMatch` neo `^…$`. Ngoài ra mod_alias **giữ nguyên query string**, nên các biến thể `?orderby=` sẽ mang query sang đích; canonical của site mới xử lý được. Muốn bỏ query thì dùng `RewriteRule … [R=301,L,QSD]`.

```apache
# ===== jannieedu.com legacy map — DRAFT 2026-10-30 =====
# /shop/ và /my-account/ đã do theme xử lý.

# --- Nhóm A [FACT]
Redirect 301 /privacy-policy/ /chinh-sach-bao-mat/
Redirect 301 /dieu-khoan-dieu-kien/ /dieu-khoan-dich-vu/
Redirect 301 /reset-password/ /tai-khoan/lost-password/

# --- Nhóm B [INFERENCE]
Redirect 301 /bio/ /my-story/
Redirect 301 /home-booking/ /booking/
Redirect 301 /khach-hang/ /client-success-story/
RedirectMatch 301 ^/courses/?$ /course/
RedirectMatch 301 ^/courses/phun-xam-tong-hop/ /course/
RedirectMatch 301 ^/course-category/phun-xam/ /course/
Redirect 301 /san-pham/coaching-11-phun-xam-spa-salon/ /coaching/
Redirect 301 /san-pham/tu-van-11-ve-dep-ca-nhan/ /coaching/
Redirect 301 /san-pham/cuoc-hen/ /booking/
Redirect 301 /san-pham/phun-xam-tong-hop/ /course/
Redirect 301 /san-pham/ky-thuat-granulation-tong-hop/ /course/
Redirect 301 /san-pham/phun-may-granulation/ /course/
Redirect 301 /san-pham/phun-chan-toc-granulation/ /course/
Redirect 301 /san-pham/cheek-blush/ /course/
Redirect 301 /san-pham/chinh-sua-long-may/ /course/
RedirectMatch 301 ^/danh-muc-san-pham/ /my-favorite-product/
Redirect 301 /dashboard/ /tai-khoan/
Redirect 301 /user-account/ /tai-khoan/
Redirect 301 /user-login/ /tai-khoan/
Redirect 301 /user-register/ /tai-khoan/
Redirect 301 /student-registration/ /tai-khoan/

# --- Nhóm C1: The Balance — CHỜ CHỦ DỰ ÁN (mặc định 410; nếu còn bán: đổi "gone" → "301 … /my-favorite-product/")
Redirect gone /san-pham/egf-cleansing-gel/
Redirect gone /san-pham/egf-peeling-gel/
Redirect gone /san-pham/egf-melting-mask/
Redirect gone /san-pham/collagen-ampoule/
Redirect gone /san-pham/herpes-treatment/

# --- Nhóm C2 → 410 (đặt SAU các dòng /courses/ ở trên)
RedirectMatch 410 ^/courses/.+
RedirectMatch 410 ^/faq/.+
RedirectMatch 410 ^/faq-group/
RedirectMatch 410 ^/course-language/
RedirectMatch 410 ^/step/
Redirect gone /chung-nhan-tutor/
Redirect gone /revolutionising-change-the-brand-new-effect-and-you-may-possible-out-of-artificial-intelligence-regarding-the-monetary-field/
# /blog/ và /faq/: cố ý không có rule.
# ===== end legacy map =====
```

Nếu `Redirect` xung đột với các `RewriteRule` đang có trong `.htaccess` (ví dụ ép HTTPS), chuyển sang dạng `RewriteRule ^bio/$ /my-story/ [R=301,L]` và đặt ngay sau khối ép HTTPS, rồi kiểm tra lại bằng curl (§8) `[INFERENCE]`.

**Phương án không phụ thuộc web server:** theme đã có `jannie_redirect_legacy_woo_slugs()` (hook `template_redirect`, ưu tiên 5). Có thể mở rộng thành một mảng map + danh sách prefix trả 410. Cách này chạy được trên cả nginx lẫn Apache, nhưng là thay đổi code nên cần ADR và test riêng. Bản nháp này không sửa.

---

## 6. Nếu production KHÔNG phải `jannieedu.com`

1. **Gia hạn và giữ** `jannieedu.com` (đến 2027-06-08) và `janniehongnhung.com` (đến 2028-09-12) lâu dài. Theo hướng dẫn site move của Google, redirect nên giữ **ít nhất 1 năm** `[FACT]`.
2. Trỏ cả hai domain cũ về một server chỉ làm redirect, **cấp TLS hợp lệ**. Cert hiện tại của `jannieedu.com` đã hết hạn, nên bot đi qua HTTPS sẽ không bao giờ thấy được 301.
3. Server block mẫu cho domain cũ. Dùng lại các `location` ở §4 nhưng đổi đích thành tuyệt đối, ví dụ `return 301 https://PROD_DOMAIN/my-story/;`. Thêm dòng fallback cuối để 10 trang trùng đường dẫn đi thẳng sang domain mới:
   ```nginx
   server {
       listen 443 ssl; http2 on;
       server_name jannieedu.com www.jannieedu.com;
       # ssl_certificate … (cert mới, hợp lệ)
       # … các location của §4 với đích https://PROD_DOMAIN/… …
       location / { return 301 https://PROD_DOMAIN$request_uri; }
   }
   ```
4. Khai báo **Change of Address** trong Google Search Console (cần verify cả property cũ lẫn mới), và gửi sitemap mới.
5. Đổi link trong bio/hồ sơ mạng xã hội sang domain mới. Danh sách hồ sơ ở `nguon-backlink-va-trich-dan.md` §1.3.
6. Tránh chuỗi redirect. Audit 2026-10-28 đề xuất có thể đổi slug tiếng Anh sang tiếng Việt. Nếu làm vậy, phải cập nhật map để URL cũ trỏ **thẳng** tới slug cuối cùng, không qua 2 bước.

---

## 7. Phụ lục — `janniehongnhung.com` (24 URL trên Wayback, capture 2024-07-16)

Domain này được trang `/bio/` cũ gắn nhãn "Blog cá nhân" `[FACT]`. Việc nó thuộc sở hữu của khách hàng là suy luận `[INFERENCE]`, cần chủ dự án xác nhận. Các URL không có dấu `/` cuối `[FACT]`.

| URL cũ | Đích trên domain production | Nhãn |
|---|---|---|
| `/` | `/` | `[INFERENCE]` |
| `/coaching` | `/coaching/` | `[FACT]` |
| `/my-story` | `/my-story/` | `[FACT]` |
| `/life-story`, `/life-story-1`, `/life-story-2` | `/my-story/` | `[INFERENCE]` |
| `/academy` | `/course/` | `[INFERENCE]` |
| `/servive` | `/coaching/` | `[INFERENCE]` |
| `/custumer-feedback` | `/client-success-story/` | `[INFERENCE]` |
| `/favorite-product`, `-1`, `-2`, `-3` | `/my-favorite-product/` | `[INFERENCE]` |
| `/chinh-sach-bao-hanh`, `/chinh-sach-tra-hang` | `/dieu-khoan-dich-vu/` | `[INFERENCE]` |
| `/the-balance-collagen-ampoule`, `/the-balance-egf-cleansing-gel`, `/the-balance-egf-melting-mask`, `/the-balance-egf-peeling-gel`, `/xa-bong-thao-duoc` | Theo quyết định nhóm C1 (410 hoặc `/my-favorite-product/`) | `[UNKNOWN]` |
| `/the-herpes-treatment` | 410 | `[UNKNOWN]` |
| `/beauty-blog`, `/hair-beauty`, `/ho-tro-dat-hang` | 410 (hoặc để 404) | `[UNKNOWN]` |

---

## 8. Kiểm thử sau khi áp dụng (checklist)

```bash
# Thay HOST bằng domain production (hoặc http://localhost:8080 khi thử local)
HOST=https://PROD_DOMAIN
for p in /bio/ /home-booking/ /khach-hang/ /courses/ /courses/phun-xam-tong-hop/ \
         /san-pham/coaching-11-phun-xam-spa-salon/ /danh-muc-san-pham/mac-dinh/ /reset-password/ \
         /privacy-policy/ /dieu-khoan-dieu-kien/ /shop/ /my-account/; do
  printf '%-48s ' "$p"; curl -s -o /dev/null -w '%{http_code} -> %{redirect_url}\n' "$HOST$p"
done   # kỳ vọng: 301 một bước, Location đúng bảng §3
for p in /courses/sample-course/ /faq/refund-a-course/ /faq-group/mobile/ /course-language/english/ \
         /step/checkout-woo/ /chung-nhan-tutor/; do
  printf '%-48s ' "$p"; curl -s -o /dev/null -w '%{http_code}\n' "$HOST$p"
done   # kỳ vọng: 410
for p in /blog/ /faq/; do curl -s -o /dev/null -w "$p %{http_code}\n" "$HOST$p"; done   # kỳ vọng: 404
```

- [ ] Không có chuỗi 301 → 301. Kiểm tra bằng `curl -sIL`.
- [ ] Trang đích trả 200 và có canonical trỏ chính nó.
- [ ] Sau 2–4 tuần, xem báo cáo "Not found (404)" và "Page with redirect" trong GSC `[UNKNOWN: chưa có quyền GSC]`.

---

## Counter-Evidence / Confidence

- **Counter-evidence:**
  - Kênh Wayback của skill `link-graph` trả `historical_urls_count: 0`, trong khi truy vấn CDX trực tiếp trả 516 URL HTML. Vì vậy inventory dựa vào truy vấn trực tiếp, không dựa vào số của skill.
  - Ngày tạo domain trên whois (2025-06-08) mâu thuẫn với lịch sử Wayback (2023-03). Cách giải thích "đăng ký lại sau khi hết hạn" là suy luận, chưa kiểm chứng.
  - 4/6 index Common Crawl bị 504, nên inventory có thể thiếu vài URL chỉ xuất hiện trong các index đó. Nhưng sitemap sống và Wayback trùng nhau 467/485 URL, nên khả năng thiếu URL quan trọng thấp.
  - Đích `/course/` cho các khoá granulation/cheek blush chỉ khớp chủ đề chung, vì `/course/` mới không liệt kê từng khoá.
- **Confidence:**
  - Inventory và phân loại: **cao (≈90%)**, đối chiếu chéo từ 3 nguồn.
  - Đích nhóm A: **cao**.
  - Nhóm B: **trung bình**.
  - Nhóm C1 (The Balance): **thấp**, phụ thuộc kế hoạch kinh doanh.
  - Kết luận "equity thấp": **trung bình**, vì không có dữ liệu Ahrefs/Majestic/GSC.
- **Falsification:** nếu GSC của `jannieedu.com` cho thấy URL nào ở nhóm C có click hoặc backlink đáng kể, chuyển URL đó sang 301 tới hub gần nhất.

## Sources

- Wayback CDX (gọi trực tiếp, 2026-10-30):
  - `https://web.archive.org/cdx/search/cdx?url=jannieedu.com/&matchType=prefix&output=json&fl=original,timestamp,statuscode,mimetype&collapse=urlkey&limit=5000` (200, 677 dòng)
  - `…?url=jannieedu.com&matchType=domain&…&collapse=urlkey` (200, 679 dòng; thêm `erp.jannieedu.com`)
  - `…?url=jannieedu.com/&matchType=prefix&fl=urlkey,timestamp,statuscode&filter=mimetype:text/html&limit=20000` (200, 3.425 capture)
  - `…?url=jannieedu.com*&…` (504)
  - `…?url=janniehongnhung.com/&matchType=prefix&collapse=urlkey&filter=mimetype:text/html` (200, 24 URL)
- Bản lưu Wayback (mode `id_`): `https://web.archive.org/web/20260711000257id_/https://jannieedu.com/`, `…/20260710id_/https://jannieedu.com/{course,coaching,blog,my-favorite-product,bio,khach-hang,home-booking,chung-nhan-tutor,dieu-khoan-dieu-kien,step/checkout-woo,san-pham/coaching-11-phun-xam-spa-salon,san-pham/tu-van-11-ve-dep-ca-nhan,san-pham/cuoc-hen,san-pham/chinh-sua-long-may,san-pham/herpes-treatment}/`
- Site cũ còn sống (đọc thụ động, `curl -k` vì cert hết hạn): `https://jannieedu.com/robots.txt`, `https://jannieedu.com/wp-sitemap.xml` + 13 sub-sitemap, `https://jannieedu.com/wp-json/wp/v2/posts?_fields=id,link,date,status`
- Common Crawl: `https://index.commoncrawl.org/collinfo.json`; `https://index.commoncrawl.org/CC-MAIN-2025-47-index?url=jannieedu.com/*&output=json` (200), `CC-MAIN-2026-12` (200), `CC-MAIN-2026-43` / `2026-30` / `2025-30` / `2024-46` (504)
- Hạ tầng: `whois jannieedu.com`, `whois janniehongnhung.com`, `dig +short A/NS`, `curl -I http(s)://jannieedu.com/`, `openssl s_client -connect jannieedu.com:443`
- Site mới (local, chỉ đọc): `curl http://localhost:8080/{…}` để lấy status của các đường dẫn đích; `wordpress/app/themes/jannie/inc/woocommerce.php` (`jannie_redirect_legacy_woo_slugs`); `deploy/nginx-production.conf`, `deploy/apache-litespeed.htaccess` (chỉ đọc cấu trúc)
- Skill: `PAGER=cat python3 -I ~/.claude/skills/sunext-SEO-AEO/scripts/seo_os_cli.py link-graph --url https://jannieedu.com --desc "…" --lang vi --country VN` (chạy từ scratch dir)
- Google Search Central: site move with URL changes — `https://developers.google.com/search/docs/crawling-indexing/site-move-with-url-changes`
- Audit trước: `docs/jn/seo-aeo/SEO_AEO_Audit_2026-10-28/1. report/bao-cao-kiem-toan-seo-aeo.md` (đề xuất đổi slug tiếng Việt và mở blog)
