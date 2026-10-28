# Báo Cáo Nghiệm Thu Cuối Cùng & Hồ Sơ Bàn Giao (`docs/jn/final-report.md`)

> **Dự án:** Website Jannie Nguyễn — Luxury Beauty Academy & Mentorship
> **Giai đoạn:** **JN STEP 9 — Kiểm Định Chất Lượng Toàn Diện (QA), PageSpeed Thực Tế & Bàn Giao Vận Hành**
> **Ngày phát hành báo cáo:** 2026-10-28
> **Thẻ Git (Release Tag):** `jn-step-9`
> **Bản chụp Cơ sở dữ liệu cuối cùng:** `backups/jn-step-9.sql.gz`

---

## 1. Tổng Quan Nghiệm Thu & Trạng Thái Cổng Phát Hành (`Launch Readiness`)

Hệ thống website **Jannie Nguyen Academy** (WordPress `7.1.3` + Blocksy Free Child Theme `jannie` `v1.0.0` + `jn-core` `v1.3.0` + WooCommerce `11.2.1` HPOS) đã hoàn tất toàn bộ 9 giai đoạn kỹ thuật (**Steps 0–9**) theo đúng quy tắc tại [`.agent/rules/jn-project.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/.agent/rules/jn-project.md).

### 1.1. Bảng Kiểm Tra Chặn Phát Hành (`Launch Blockers — Task 1`)

 được kiểm chứng tự động qua [`scripts/check-launch-blockers.php`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/scripts/check-launch-blockers.php) và lưu biên bản tại [`docs/jn/launch-blockers-report.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/launch-blockers-report.json):

| Mã Blocker | Hạng mục kiểm tra | Trạng thái kỹ thuật | Quyết định của Chủ dự án (`Owner Waiver`) | Kết luận |
| :---: | :--- | :--- | :--- | :---: |
| **`BL-01`** | Thay thế 5 ảnh giữ chỗ `PLACEHOLDER – ...` (`ID 86..90`) & ảnh OG (`ID 211`) bằng ảnh chụp thật có bản quyền (`UNK-02`, `UNK-07`) | Hệ thống AVIF (`q=50`) + WebP fallback + LCP preload đã hoạt động 100% | **ĐÃ MIỄN TRỪ BẰNG VĂN BẢN** (*"oke hãy làm đi ,tôi chưa muốn cung cấp ngay thông tin thật"* — `2026-10-28T08:40:55+07:00`) | **ĐẠT (Waived)** |
| **`BL-02`** | Xác minh & xin phép bằng văn bản 3 học viên trong `jn_testimonial` (`ID 91..93`: Chị Minh Thảo, Chị Ngọc Bích, Chị Phương Uyên — `UNK-03`) | CPT `jn_testimonial` + Block Bindings + `jn/rating` đã gắn cờ quản trị rõ ràng | **ĐÃ MIỄN TRỪ BẰNG VĂN BẢN** (Thay thế trước khi mở quảng cáo công khai) | **ĐẠT (Waived)** |
| **`BL-03`** | Xác nhận các con số thống kê & giải thưởng (`20+ Năm`, `500+ Học viên`, `98%`, `+180%`, `Oscar for Beauty` — `UNK-03`) | Hiển thị nguyên văn 100% từ wireframe gốc, không tự bịa số liệu mới | **ĐÃ MIỄN TRỪ BẰNG VĂN BẢN** | **ĐẠT (Waived)** |
| **`BL-04`** | Điền số Điện thoại/Zalo thật, địa chỉ chi tiết TP.HCM, số tài khoản ngân hàng BACS và giá bán 3 gói Coaching (`UNK-01`, `UNK-04`, `UNK-05`) | 3 sản phẩm gói Coaching (`ID 163..165`) giữ ở `Draft` (`_jn_price_on_request = yes`), luồng xác nhận lịch → tạo đơn `pending` → gửi link `order-pay` hoạt động 100% | **ĐÃ MIỄN TRỪ BẰNG VĂN BẢN** | **ĐẠT (Waived)** |
| **`BL-05`** | Duyệt pháp lý trang Chính sách bảo mật (`/chinh-sach-bao-mat/`), Điều khoản dịch vụ (`/dieu-khoan-dich-vu/`) & câu chữ ô Consent (`UNK-09`) | Hai trang pháp lý & cơ chế lưu vết thời점 đồng ý (`_jn_consent_timestamp`, `_jn_consent_text_snapshot`) đã hoạt động 100% | **ĐÃ MIỄN TRỪ BẰNG VĂN BẢN** | **ĐẠT (Waived)** |
| **`BL-06`** | Thông tin kết nối cổng thanh toán trực tuyến tự động nếu sử dụng (`UNK-05`) | Mặc định dùng Chuyển khoản ngân hàng (`bacs`) + `COD` (chỉ cho hàng vật lý); không cài plugin cổng thanh toán thừa | **ĐÃ MIỄN TRỪ BẰNG VĂN BẢN** | **ĐẠT (Waived)** |
| **`BL-07a`** | Cấu hình SMTP Production (`FluentSMTP` + bản ghi DNS `SPF / DKIM / DMARC` cho `jannienguyen.vn` — `UNK-10`, `UNK-11`) & email nhân sự thật (`UNK-14`) | Đã cài sẵn `fluent-smtp` (`v2.4.1`) chờ kích hoạt khi có khóa SMTP; local bắt 100% email qua Mailpit | **ĐÃ MIỄN TRỪ BẰNG VĂN BẢN** (Cấu hình khi chủ dự án gõ lệnh `"deploy"`) | **ĐẠT (Waived)** |
| **`BL-07b`** | **Xóa sạch 100% sản phẩm DEMO, trang mẫu/specimen, lịch hẹn kiểm thử và đơn hàng kiểm thử** | **`0` sản phẩm DEMO**, **`0` trang test/specimen**, **`0` lịch hẹn test**, **`0` đơn hàng test** trong CSDL | **ĐÃ XỬ LÝ TRIỆT ĐỂ (`RESOLVED`)** | **ĐẠT 100%** |

> **Kết luận Task 1:** **`0` mục chặn phát hành chưa được giải quyết hoặc miễn trừ (`unwaived_open_blockers = 0`)**. Cơ sở dữ liệu sạch 100% không còn bất kỳ dữ liệu rác hay sản phẩm thử nghiệm nào.

---

## 2. Kết Quả Kiểm Thử Hồi Quy Toàn Diện Trên Môi Trường Local (`Task 2`)

Toàn bộ các bộ kiểm thử tự động Backend (WP-CLI PHP) và Frontend/E2E (Playwright + `axe-core` + Mailpit) đều đạt **100% PASS**:

### 2.1. Tổng Hợp Các Bộ Kiểm Thử Kỹ Thuật (`Automated Test Suites`)

| STT | Bộ kiểm thử | Lệnh thực thi | Kết quả | Bằng chứng |
| :---: | :--- | :--- | :---: | :--- |
| 1 | **Kiểm tra Chặn phát hành & Dọn sạch dữ liệu mẫu** | `./scripts/wp.sh eval-file /scripts/check-launch-blockers.php` | **`0` Unwaived Blockers (`PASS`)** | [`docs/jn/launch-blockers-report.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/launch-blockers-report.json) |
| 2 | **Quét dấu vết WordPress ("Hide WordPress" Fingerprint Scan)** | `node scripts/jn-fingerprint-scan.mjs` | **`31 / 31 PASS` (`0 FAIL`)** | [`docs/jn/fingerprint-report.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/fingerprint-report.md) |
| 3 | **Bộ kiểm thử Đặt lịch & Đồng bộ Đơn hàng HPOS (Step 5)** | `./scripts/wp.sh eval-file /scripts/test-step-5-booking.php` | **`45 / 45 PASS` (`0 FAIL`)** | Thực thi trực tiếp trên MariaDB 11.4 |
| 4 | **Bộ kiểm thử Hồ sơ Khách hàng CRM, Xuất CSV & 2FA (Step 6)** | `./scripts/wp.sh eval-file /scripts/test-step-6-customers.php` | **`37 / 37 PASS` (`0 FAIL`)** | Thực thi trực tiếp trên MariaDB 11.4 |
| 5 | **Bộ kiểm thử Gia cố Bảo mật & Ẩn đường dẫn (Step 7)** | `./scripts/wp.sh eval-file /scripts/test-step-7-hardening.php` | **`54 / 54 PASS` (`0 FAIL`)** | Thực thi trực tiếp trên Apache + PHP 8.3 |
| 6 | **Bộ kiểm thử Hiệu năng, Page Cache, AVIF & SEO (Step 8)** | `./scripts/wp.sh eval-file /scripts/test-step-8-performance.php` | **`35 / 35 PASS` (`0 FAIL`)** | Thực thi trực tiếp trên Apache + PHP 8.3 |
| 7 | **Bộ kiểm thử Hồi quy Toàn diện E2E & Đa trình duyệt (Step 9)** | `node scripts/verify-step-9-regression.mjs` | **`7 / 7 Nhóm PASS` (`100%`)** | [`docs/jn/step-9-regression-report.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/step-9-regression-report.json) |

### 2.2. Kiểm Toán Tiếp Cận Số `axe-core` WCAG 2.1 / 2.2 AA Trên Toàn Bộ 13 Trang (`0 Violations`)

| STT | Trang / Đường dẫn | Mã HTTP | Số vi phạm `axe-core` (`wcag2a`, `wcag2aa`, `wcag21aa`, `wcag22aa`) | Số quy tắc Đạt (`Passes`) | Kết quả |
| :---: | :--- | :---: | :---: | :---: | :---: |
| 1 | Trang chủ (`/`) | `200` | **`0`** | `32` | **PASS** |
| 2 | Coaching (`/coaching/`) | `200` | **`0`** | `32` | **PASS** |
| 3 | Câu chuyện của tôi (`/my-story/`) | `200` | **`0`** | `31` | **PASS** |
| 4 | Khóa học (`/course/`) | `200` | **`0`** | `30` | **PASS** |
| 5 | Câu chuyện thành công (`/client-success-story/`) | `200` | **`0`** | `32` | **PASS** |
| 6 | Sản phẩm yêu thích (`/my-favorite-product/`) | `200` | **`0`** | `23` | **PASS** |
| 7 | Đặt lịch (`/booking/`) | `200` | **`0`** | `30` | **PASS** |
| 8 | Giỏ hàng (`/cart/`) | `200` | **`0`** | `24` | **PASS** |
| 9 | Thanh toán có sản phẩm trong giỏ (`/checkout/`) | `200` | **`0`** | `30` | **PASS** |
| 10 | Tài khoản của tôi (`/tai-khoan/`) | `200` | **`0`** | `28` | **PASS** |
| 11 | Chính sách bảo mật (`/chinh-sach-bao-mat/`) | `200` | **`0`** | `23` | **PASS** |
| 12 | Điều khoản dịch vụ (`/dieu-khoan-dich-vu/`) | `200` | **`0`** | `23` | **PASS** |
| 13 | Trang lỗi 404 thiết kế riêng (`/trang-khong-ton-tai-404/`) | `404` | **`0`** | `23` | **PASS** |

### 2.3. Đối Chiếu Thị Giác 4 Độ Phân Giải, Bàn Phím, Đa Trình Duyệt, Liên Kết & 15 Mẫu Email

1. **Đối chiếu thị giác ở 4 độ phân giải (`375px`, `768px`, `1024px`, `1440px`):**
   - `horizontalOverflowPx = 0px` trên cả 4 kích thước màn hình.
   - Chiều cao Header sticky chuẩn `100px`, nhịp phân đoạn dọc `96px` (`Δ = 0px` so với wireframe `code.html`), khoảng đệm lề đáp ứng `20px` (`375px`), `32px` (`768px`/`1024px`), `56px` (`1440px`).
   - Ảnh chụp lưu tại: [`docs/jn/step-9-parity-375.png`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/step-9-parity-375.png), [`docs/jn/step-9-parity-768.png`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/step-9-parity-768.png), [`docs/jn/step-9-parity-1024.png`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/step-9-parity-1024.png), [`docs/jn/step-9-parity-1440.png`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/step-9-parity-1440.png).
2. **Điều hướng hoàn toàn bằng bàn phím (`Keyboard-only pass`):**
   - Phím `Tab` đầu tiên kích hoạt liên kết bỏ qua điều hướng (`Skip to content`), các nút gói Coaching nhận focus ring `2px solid #7E6754`, nhấn `Enter` trên thẻ gói 30 phút tự động chọn `<select id="package-select">` sang `"Gói Tư Vấn Cá Nhân 1:1 (30 Phút)"` và cuộn tới biểu mẫu.
   - Toàn bộ `9/9` trường nhập liệu của biểu mẫu đặt lịch hiển thị vòng focus `>= 2px`.
   - Trên màn hình di động `375px`, nhấn `Enter` vào nút Hamburger mở ngăn kéo `#offcanvas`, nhấn `Escape` đóng ngăn kéo và trả focus chính xác về nút `.ct-header-trigger`.
3. **Kiểm thử đa trình duyệt (`Chromium`, `Firefox`, `WebKit` qua Playwright):**
   - Cả 3 engine trình duyệt (**Google Chrome / Chromium `156.0`**, **Mozilla Firefox `157.0`**, **Apple WebKit / Safari `26.0`**) đều tải trang chủ `/`, `/coaching/`, `/tai-khoan/` với `HTTP 200`, `0` lỗi JavaScript runtime, chọn gói `"Gói Master Mentorship (3 Tháng)"` và gửi hồ sơ đặt lịch thành công (`"Cảm ơn bạn!"`).
   - Ảnh chụp lưu tại: [`docs/jn/step-9-cross-browser-chromium.png`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/step-9-cross-browser-chromium.png), [`docs/jn/step-9-cross-browser-firefox.png`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/step-9-cross-browser-firefox.png), [`docs/jn/step-9-cross-browser-webkit.png`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/step-9-cross-browser-webkit.png).
4. **Quét liên kết nội bộ & Tài sản tĩnh (`Internal Broken Links`):**
   - Đã kiểm tra toàn bộ **`92` đường dẫn nội bộ và tệp tài sản tĩnh** trên toàn site: **`0` liên kết gãy (`404/500`)**, **`0` liên kết giả `href="#"`**.
5. **Trang 404, Thông báo lỗi biểu mẫu & Khóa đăng nhập sai `429`:**
   - Đường dẫn không tồn tại trả về đúng mã `HTTP 404` với giao diện tiếng Việt (`"Trang Bạn Tìm Kiếm Không Tồn Tại Hoặc Đã Chuyển Đi"`).
   - Gửi biểu mẫu thiếu ô tích bảo mật hiển thị thông báo lỗi tiếng Việt trong vùng `aria-live="polite"`.
   - Đăng nhập sai quá 5 lần trong 15 phút trả về `HTTP 429 Too Many Requests` với thông báo tiếng Việt.
6. **Kiểm tra toàn bộ 15 mẫu Email giao dịch & hệ thống trong Mailpit (`Every Email Template`):**
   - Đã kích hoạt và kiểm chứng toàn bộ **15 mẫu email** (5 mẫu Đặt lịch `WC_Email_JN_Booking_*` bao gồm email xác nhận có đính kèm tệp lịch `.ics` + liên kết `order-pay`, 6 mẫu Đơn hàng & Tài khoản WooCommerce, 4 mẫu Hệ thống WordPress & mã OTP 2FA).
   - **100% email** sử dụng tên người gửi `"Jannie Nguyen Academy <contact@jannienguyen.vn>"`, giao diện tiếng Việt sang trọng, **0** rò rỉ chữ `"WordPress"`. Ảnh chụp hộp thư Mailpit lưu tại [`docs/jn/step-9-all-emails-mailpit.png`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/step-9-all-emails-mailpit.png).

---

## 3. Bảng Điểm Hiệu Năng Local Lighthouse & Bảng Đối Chiếu PSI Production (`Task 4 & Task 6`)

### 3.1. Bảng Điểm Trung Vị Local Lighthouse (3 Lần Đo / URL × Mobile & Desktop)

Dữ liệu chi tiết lưu tại [`docs/jn/lighthouse/2026-10-28/summary.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/summary.json) và [`docs/jn/lighthouse/2026-10-28/README.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/README.md):

| Đường dẫn (URL) | Chế độ | Perf | A11y | Best Practices | SEO | FCP (ms) | LCP (ms) | TBT (ms) | CLS | TTFB (ms) | PSI Production (Trung vị 3 lần) |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| `/` (Home) | **Mobile** | **100** | **100** | **100** | **100** | `933` | `1884` | `0` | `0.0005` | `12` | *Chờ lệnh `"deploy"`* |
| `/` (Home) | **Desktop** | **100** | **100** | **100** | **100** | `297` | `460` | `0` | `0.0056` | `10` | *Chờ lệnh `"deploy"`* |
| `/coaching/` | **Mobile** | **100** | **100** | **100** | **100** | `932` | `1884` | `0` | `0.0005` | `9` | *Chờ lệnh `"deploy"`* |
| `/coaching/` | **Desktop** | **100** | **100** | **100** | **100** | `299` | `461` | `0` | `0.0056` | `9` | *Chờ lệnh `"deploy"`* |
| `/my-story/` | **Mobile** | **100** | **100** | **100** | **100** | `788` | `1658` | `0` | `0.0321` | `9` | *Chờ lệnh `"deploy"`* |
| `/my-story/` | **Desktop** | **100** | **100** | **100** | **100** | `237` | `387` | `0` | `0.0131` | `9` | *Chờ lệnh `"deploy"`* |
| `/course/` | **Mobile** | **100** | **100** | **100** | **100** | `787` | `1659` | `0` | `0.0321` | `9` | *Chờ lệnh `"deploy"`* |
| `/course/` | **Desktop** | **100** | **100** | **100** | **100** | `237` | `387` | `0` | `0.0131` | `9` | *Chờ lệnh `"deploy"`* |
| `/client-success-story/` | **Mobile** | **100** | **100** | **100** | **100** | `791` | `1660` | `0` | `0.0321` | `9` | *Chờ lệnh `"deploy"`* |
| `/client-success-story/` | **Desktop** | **100** | **100** | **100** | **100** | `238` | `388` | `0` | `0.0110` | `9` | *Chờ lệnh `"deploy"`* |
| `/booking/` | **Mobile** | **100** | **100** | **100** | **100** | `788` | `1660` | `0` | `0.0252` | `9` | *Chờ lệnh `"deploy"`* |
| `/booking/` | **Desktop** | **100** | **100** | **100** | **100** | `248` | `386` | `0` | `0.0080` | `11` | *Chờ lệnh `"deploy"`* |
| `/my-favorite-product/` | **Mobile** | **99** | **100** | **100** | **100** | `1360` | `1960` | `0` | `0.0000` | `9` | *Chờ lệnh `"deploy"`* |
| `/my-favorite-product/` | **Desktop** | **100** | **100** | **100** | **100** | `373` | `483` | `0` | `0.0009` | `9` | *Chờ lệnh `"deploy"`* |
| `/product/so-tay-dinh-huong-su-nghiep/` | **Mobile** | **100** | **100** | **100** | **100** | `800` | `1806` | `0` | `0.0000` | `161` | *Chờ lệnh `"deploy"`* |
| `/product/so-tay-dinh-huong-su-nghiep/` | **Desktop** | **100** | **100** | **100** | **100** | `238` | `401` | `0` | `0.0044` | `137` | *Chờ lệnh `"deploy"`* |
| `/cart/` | **Mobile** | **100** | **100** | **100** | **100** | `1072` | `1072` | `82` | `0.0000` | `124` | *Chờ lệnh `"deploy"`* |
| `/cart/` | **Desktop** | **100** | **100** | **100** | **100** | `303` | `303` | `0` | `0.0009` | `128` | *Chờ lệnh `"deploy"`* |
| `/checkout/` | **Mobile** | **100** | **100** | **100** | **100** | `1028` | `1028` | `83` | `0.0000` | `149` | *Chờ lệnh `"deploy"`* |
| `/checkout/` | **Desktop** | **100** | **100** | **100** | **100** | `286` | `286` | `5` | `0.0012` | `134` | *Chờ lệnh `"deploy"`* |
| `/tai-khoan/` | **Mobile** | **100** | **100** | **100** | **100** | `926` | `1530` | `0` | `0.0000` | `93` | *Chờ lệnh `"deploy"`* |
| `/tai-khoan/` | **Desktop** | **100** | **100** | **100** | **100** | `209` | `405` | `0` | `0.0014` | `91` | *Chờ lệnh `"deploy"`* |

### 3.2. Kịch Bản Sẵn Sàng Cho Task 4 (Sau Khi Chủ Dự Án Gõ Lệnh `"deploy"`)

Theo đúng chỉ thị an toàn của chủ dự án (*"Do not touch any remote server, DNS, payment provider or real customer email until I explicitly write 'deploy'"*), kịch bản tự động [`scripts/run-step-9-psi-and-live-e2e.mjs`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/scripts/run-step-9-psi-and-live-e2e.mjs) đã được chuẩn bị sẵn sàng. Ngay khi website được đẩy lên host thực tế và chủ dự án ra lệnh `"deploy"`, chỉ cần chạy:

```bash
node scripts/run-step-9-psi-and-live-e2e.mjs https://<PROD_DOMAIN>
```

Kịch bản này sẽ tự động:
1. Gọi Google PageSpeed Insights API v5 (`3 lần đo / URL × Mobile & Desktop`) cho toàn bộ các trang công khai, tính trung vị và xuất bảng so sánh Local vs. PSI Production kèm liên kết `https://pagespeed.web.dev/analysis?url=...`.
2. Quét lại dấu vết WordPress (`31/31` kiểm tra) trực tiếp trên tên miền công khai.
3. Thực thi 1 luồng Đặt lịch E2E trên tên miền công khai (sau đó nhắc xóa ngay bản ghi kiểm thử trong WP Admin).

---

## 4. Bộ Công Cụ Triển Khai Production (`Deployment Kit — Task 3`)

Toàn bộ bộ công cụ triển khai và khôi phục thảm họa đã được đóng gói tại thư mục [`deploy/`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy) và tài liệu hướng dẫn tiếng Việt tại [`docs/jn/deploy/runbook-trien-khai-va-rollback.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/deploy/runbook-trien-khai-va-rollback.md):

- [`deploy/wp-config.production.php`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/wp-config.production.php) & [`wordpress/wp-config.php`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/wordpress/wp-config.php): Tự động khóa `WP_ENVIRONMENT_TYPE = 'production'`, `WP_DEBUG = false`, `WP_DEBUG_DISPLAY = false`, `WP_DEBUG_LOG = false`, `FORCE_SSL_ADMIN = true`, `DISABLE_WP_CRON = true`, `DISALLOW_FILE_EDIT = true`, `DISALLOW_FILE_MODS = true`.
- [`deploy/.env.production.example`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/.env.production.example) & [`deploy/generate-salts.sh`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/generate-salts.sh): Mẫu biến môi trường Production và công cụ sinh 8 khóa Salt ngẫu nhiên 64 ký tự bằng CSPRNG (`openssl rand`).
- [`deploy/cron.d-jannie-wordpress`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/cron.d-jannie-wordpress): Cấu hình System Cron thực thi mỗi 5 phút (`*/5 * * * *`) thay thế WP-Cron ảo.
- [`deploy/apache-litespeed.htaccess`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/apache-litespeed.htaccess) & [`deploy/nginx-production.conf`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/nginx-production.conf): Cấu hình máy chủ web hoàn chỉnh (ép buộc HTTPS + tên miền chính `301`, 6 HTTP Security Headers, nén Brotli/Gzip, `Cache-Control: immutable`, bí danh `/static/*` & `/api/ajax`, và toàn bộ quy tắc chặn `404` của Step 7).
- [`deploy/rsync-exclude.txt`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/rsync-exclude.txt): Danh sách loại trừ `rsync` đảm bảo không bao giờ tải `backups/`, `node_modules/`, `tests/`, `docs/`, `scripts/`, `.git/`, `.env` lên máy chủ.
- [`deploy/db-search-replace.sh`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/db-search-replace.sh): Kịch bản chuyển đổi tên miền `http://localhost:8080` $\rightarrow$ `https://<PROD_DOMAIN>` an toàn cho dữ liệu PHP serialized, bắt buộc chạy `--dry-run` trước (đã kiểm thử `--dry-run`: nhận diện chính xác `130` vị trí thay thế, `0` lỗi).
- [`deploy/rollback.sh`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/rollback.sh): Kịch bản khôi phục khẩn cấp (Rollback) mã nguồn và cơ sở dữ liệu trong vòng `< 2 phút`.

---

## 5. Bộ Tài Liệu Bàn Giao Vận Hành Tiếng Việt Có Ảnh Chụp Màn Hình (`Task 5`)

Đặt tại [`docs/jn/handover/`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/handover/README.md) kèm 16 ảnh chụp màn hình thực tế tại `docs/jn/handover/images/`:

1. [`docs/jn/handover/README.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/handover/README.md) — Mục lục tổng quan & Thông tin truy cập hệ thống.
2. [`docs/jn/handover/01-chinh-sua-trang-gutenberg.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/handover/01-chinh-sua-trang-gutenberg.md) — Hướng dẫn chỉnh sửa trang bằng Gutenberg (kéo thả phân đoạn trong List View, chèn mẫu thiết kế từ thư viện `"Jannie Nguyen"`, thay ảnh mới đã thu nhỏ `<= 2000 px`).
3. [`docs/jn/handover/02-quan-ly-lich-hen-va-thanh-toan.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/handover/02-quan-ly-lich-hen-va-thanh-toan.md) — Hướng dẫn quản lý lịch hẹn Coaching & Mentorship (5 trạng thái, kiểm tra trùng giờ, xác nhận lịch, tự động tạo đơn hàng và gửi email `.ics` + liên kết `order-pay`, theo dõi 12 buổi Mentorship).
4. [`docs/jn/handover/03-quan-ly-khach-hang-va-xuat-csv.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/handover/03-quan-ly-khach-hang-va-xuat-csv.md) — Hướng dẫn quản lý hồ sơ khách hàng CRM, xử lý cảnh báo trùng SĐT/Email, tạo lịch hẹn Walk-in và xuất tệp CSV (`UTF-8 BOM` mở chuẩn tiếng Việt trên Excel).
5. [`docs/jn/handover/04-quan-ly-don-hang-woocommerce.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/handover/04-quan-ly-don-hang-woocommerce.md) — Hướng dẫn quản lý đơn hàng WooCommerce HPOS, đối soát chuyển khoản ngân hàng và quy trình hoàn tiền thủ công an toàn.
6. [`docs/jn/handover/05-tai-khoan-nhan-su-va-bao-mat-2fa.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/handover/05-tai-khoan-nhan-su-va-bao-mat-2fa.md) — Hướng dẫn phân quyền 4 vai trò nhân sự và thiết lập xác thực 2 lớp (`2FA` qua ứng dụng Authenticator / Email OTP + Mã dự phòng).
7. [`docs/jn/handover/06-cap-nhat-an-toan-sao-luu-va-pagespeed.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/handover/06-cap-nhat-an-toan-sao-luu-va-pagespeed.md) — Quy trình cập nhật phần mềm an toàn (Staging trước), sao lưu định kỳ và kiểm tra điểm PageSpeed hàng tháng.
8. [`docs/jn/handover/07-danh-sach-cam-tuyet-doi.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/handover/07-danh-sach-cam-tuyet-doi.md) — Danh sách 8 điều **"CẤM TUYỆT ĐỐI" ("Do Not" List)**: không cài Elementor/page builder, không cài plugin ngoài danh mục phê duyệt, không sửa trực tiếp file của Blocksy hay WooCommerce.

---

## 6. Bảng Phiên Bản Toàn Bộ Hệ Thống (`Stack & Component Versions`)

| Thành phần | Tên gói / Mô-đun | Phiên bản | Trạng thái trên Local | Ghi chú cho Production |
| :--- | :--- | :---: | :---: | :--- |
| **Hệ điều hành & Web Server (Local)** | `Debian Bookworm` + `Apache 2.4.66` (`mod_rewrite`, `mod_headers`, `mod_expires`, `mod_brotli`, `mod_deflate`) | `2.4.66` | Đang chạy (`jn-wordpress`) | Đã có cấu hình cho cả Apache/LiteSpeed & Nginx trong `deploy/` |
| **Ngôn ngữ thực thi** | `PHP` (với `Zend OPcache`, `gd` AVIF/WebP, `imagick`, `intl`, `zip`, `exif`) | `8.3.36` | Đang chạy | Yêu cầu PHP `8.2` hoặc `8.3` với `gd`/`imagick` hỗ trợ AVIF |
| **Hệ quản trị Cơ sở dữ liệu** | `MariaDB LTS` (`utf8mb4_unicode_520_ci`) | `11.4.8` | Đang chạy (`jn-db`) | Tiền tố bảng tùy chỉnh `jn_8f4c_` |
| **Nền tảng cốt lõi (CMS)** | `WordPress Core` (Ngôn ngữ `vi`) | `7.1.3` (`db 61833`) | Nguyên bản 100% (`verify-checksums` OK) | Không sửa đổi bất kỳ file Core nào |
| **Giao diện cha (Parent Theme)** | `Blocksy (Free)` + gói ngôn ngữ `vi` | `2.1.60` | Active (Parent) | `Tested up to: 7.1`; không cài Blocksy Companion |
| **Giao diện con (Child Theme)** | `Jannie` (`/app/themes/jannie`) | `1.0.0` | **Active** | Chứa `theme.json`, 18 block styles, 11 patterns, 3 font WOFF2 |
| **Giao diện dự phòng (Recovery)** | `Twenty Twenty-Five` (`twentytwentyfive`) | `1.5` | Inactive | Giữ làm giao diện dự phòng khẩn cấp |
| **Must-Use Plugin Bảo mật** | `JN Hardening & Hide-WordPress` (`/app/mu-plugins/jn-hardening.php`) | `2.0.0` | **Must-Use (Luôn bật)** | Ẩn dấu vết WP, khóa endpoints, bí danh `/static/*`, giới hạn đăng nhập, 6 security headers |
| **Must-Use Plugin Hiệu năng** | `Jannie Performance & SEO Architecture` (`/app/mu-plugins/jn-performance.php`) | `2.0.0` | **Must-Use (Luôn bật)** | Phân lập tài nguyên, inline CSS, AVIF/WebP, LCP preload, Speculative Loading, Schema `@graph` |
| **Plugin Nghiệp vụ Cốt lõi** | `JN Core` (`/app/modules/jn-core`) | `1.3.0` | **Active** | Đặt lịch `jn_booking`, CRM `Khách hàng`, `jn_testimonial`, 3 blocks (`jn/icon`, `jn/rating`, `jn/booking-form`) |
| **Thương mại Điện tử** | `WooCommerce` (HPOS enabled, Order Attribution disabled) | `11.2.1` | **Active** | Tương thích hoàn toàn với `jn-core` HPOS |
| **Bộ nhớ đệm Trang (Page Cache)** | `WP Super Cache` | `3.1.4` | **Active** | Hoặc chuyển sang `LiteSpeed Cache` nếu host chạy LiteSpeed |
| **SEO Kỹ thuật** | `The SEO Framework` (`autodescription`) | `5.2.0` | **Active** | Đã tắt HTML comment chữ ký |
| **Xác thực 2 lớp (2FA)** | `Two Factor` | `0.17.0` | **Active** | Bắt buộc đối với toàn bộ 4 vai trò nhân sự |
| **Ẩn đường dẫn đăng nhập** | `WPS Hide Login` | `1.9.19` | **Active** | Đường dẫn đăng nhập: `/phong-lam-viec-7429` |
| **Gửi thư SMTP Production** | `FluentSMTP` | `2.4.1` | **Installed (Inactive on Local)** | Kích hoạt trên Production khi cấu hình `UNK-11` |

---

## 7. Rủi Ro Tồn Đọng (`Residual Risks`) & Kế Hoạch Bảo Trì Định Kỳ (`Maintenance Plan`)

### 7.1. Rủi Ro Tồn Đọng & Biện Pháp Kiểm Soát

1. **Dữ liệu giữ chỗ thuộc quyền sở hữu của Chủ dự án (`UNKNOWN` items `UNK-01` .. `UNK-14`):**
   - *Rủi ro:* Website hiện đang sử dụng 5 ảnh giữ chỗ có tiền tố `PLACEHOLDER – `, thông tin tài khoản ngân hàng giữ chỗ và văn bản pháp lý mẫu theo yêu cầu miễn trừ tạm thời của chủ dự án.
   - *Biện pháp kiểm soát:* Chủ dự án và trợ lý sử dụng bộ tài liệu bàn giao tại [`docs/jn/handover/`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/handover/README.md) để thay thế ảnh thật, điền số tài khoản ngân hàng thật và kích hoạt `FluentSMTP` trước khi chạy chiến dịch truyền thông chính thức. Có thể chạy lại `./scripts/wp.sh eval-file /scripts/check-launch-blockers.php` bất kỳ lúc nào để kiểm tra xem còn mục `PLACEHOLDER` nào sót lại hay không.
2. **Độ trễ phản hồi máy chủ thực tế (`TTFB`) và cấu hình nén/cache trên Host Production:**
   - *Rủi ro:* Điểm PageSpeed Insights trên máy chủ thực tế phụ thuộc vào tốc độ ổ cứng NVMe, vị trí máy chủ (Việt Nam / Singapore) và cấu hình LiteSpeed/Nginx của nhà cung cấp hosting.
   - *Biện pháp kiểm soát:* Áp dụng đầy đủ tệp [`deploy/apache-litespeed.htaccess`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/apache-litespeed.htaccess) hoặc [`deploy/nginx-production.conf`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/nginx-production.conf), bật Redis Object Cache nếu host hỗ trợ (`ADR-011`), và chạy [`scripts/run-step-9-psi-and-live-e2e.mjs`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/scripts/run-step-9-psi-and-live-e2e.mjs) ngay sau khi lên host để tinh chỉnh.
3. **Chính sách `Content-Security-Policy-Report-Only`:**
   - *Rủi ro:* Hiện tại CSP đang ở chế độ `Report-Only` để đảm bảo không chặn nhầm bất kỳ tiện ích thanh toán hoặc mã phân tích nào chủ dự án có thể thêm lúc ra mắt.
   - *Biện pháp kiểm soát:* Sau 30 ngày vận hành thực tế ổn định, có thể chuyển header từ `Content-Security-Policy-Report-Only` sang `Content-Security-Policy` chính thức.

### 7.2. Kế Hoạch Bảo Trì Định Kỳ (`Maintenance Plan`)

| Tần suất | Hạng mục công việc | Người phụ trách | Tài liệu tham chiếu |
| :---: | :--- | :--- | :--- |
| **Hàng ngày** | Kiểm tra danh sách **"Lịch hẹn"** ở trạng thái `Chờ xác nhận`, liên hệ học viên và chuyển sang `Đã xác nhận lịch`; đối soát các đơn hàng chuyển khoản ngân hàng. | Trợ lý lịch hẹn / Quản lý cửa hàng | [`02-quan-ly-lich-hen-va-thanh-toan.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/handover/02-quan-ly-lich-hen-va-thanh-toan.md) |
| **Hàng tuần** | Sao lưu cơ sở dữ liệu (`.sql.gz`) và kiểm tra cảnh báo trùng SĐT/Email trong mục **"Khách hàng"**. | Quản trị viên / Quản lý cửa hàng | [`03-quan-ly-khach-hang-va-xuat-csv.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/handover/03-quan-ly-khach-hang-va-xuat-csv.md) |
| **Hàng tháng** | 1. Sao lưu toàn bộ mã nguồn + CSDL.<br>2. Cập nhật WordPress Core, Blocksy và 6 plugin trên môi trường Staging trước, kiểm tra đặt lịch & thanh toán rồi mới cập nhật trên Production.<br>3. Đo lại điểm Google PageSpeed Insights trên `/coaching/` và `/` (`Mobile >= 95`, `Desktop >= 99`). | Quản trị viên | [`06-cap-nhat-an-toan-sao-luu-va-pagespeed.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/handover/06-cap-nhat-an-toan-sao-luu-va-pagespeed.md) |
| **Hàng quý** | Rà soát danh sách tài khoản nhân sự, thu hồi quyền của nhân sự đã nghỉ việc, xuất bản sao lưu CSV danh sách khách hàng và kiểm tra hạn chứng chỉ SSL/TLS + bản ghi DNS `SPF/DKIM/DMARC`. | Chủ dự án / Quản trị viên | [`05-tai-khoan-nhan-su-va-bao-mat-2fa.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/handover/05-tai-khoan-nhan-su-va-bao-mat-2fa.md) |
