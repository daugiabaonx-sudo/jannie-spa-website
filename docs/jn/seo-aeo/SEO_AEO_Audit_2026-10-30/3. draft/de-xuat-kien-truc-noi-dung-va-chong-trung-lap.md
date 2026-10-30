# BẢN NHÁP — Kiến trúc nội dung, chống trùng lặp & khối trả lời AEO (chờ chủ dự án duyệt)

> Nguồn số liệu: [`page-overlap.md`](../1.%20report/page-overlap.md) (script [`page_overlap_audit.py`](../2.%20python/page_overlap_audit.py), dùng `cosine_counter` của skill `sunext-semrush-ahrefs-os`). Mọi đề xuất dưới đây **chưa được áp dụng** lên site — đều đụng tới bố cục wireframe (`docs/jn/wireframe-spec.md`) hoặc cần dữ kiện của chủ dự án. Bổ sung (không thay thế) hai bản nháp ngày 28/10: `faq-va-khoi-tra-loi-truc-tiep.md` và `kien-truc-tu-khoa-va-lo-trinh-noi-dung.md`.
> Nhãn: `[FACT]` đo được / có trong site · `[INFERENCE]` suy luận · `[UNKNOWN]` thiếu dữ kiện.

## 1. Trang chủ `/` và `/coaching/` đang tranh cùng một ý định

| Bằng chứng | Giá trị |
|---|---|
| Cosine ý định (title + H1 + H2) | **0.846** — vượt ngưỡng cannibalization 0.84 của skill `[FACT]` |
| Tỷ lệ cụm 5 từ của `/` xuất hiện nguyên văn trên `/coaching/` | **0.927** `[FACT]` |
| H2 chung | "Hơn 20 Năm Đồng Hành…", "Lợi Ích Của Chương Trình Coaching", "Chi Tiết Gói Đào Tạo & Cố Vấn", "Cảm Nhận Học Viên…", "Đăng Ký Tư Vấn…" — trang chủ = `/coaching/` bỏ FAQ `[FACT]` |

**Hệ quả** `[INFERENCE]`: với truy vấn kiểu "cố vấn 1:1 ngành thẩm mỹ", Google phải chọn giữa hai URL gần như giống nhau; trang chủ thường thắng nhờ nhiều link nội bộ hơn (73 so với 39 link trong 9 trang mẫu), nên `/coaching/` — trang có FAQ, `Service` và luồng đặt lịch — dễ bị đẩy xuống.

**Đề xuất (cần duyệt vì lệch wireframe):** biến trang chủ thành trang *hub*.

| Khối trang chủ hiện tại | Đề xuất |
|---|---|
| Hero + "Hơn 20 Năm Đồng Hành…" | **Giữ** — đây là phần thương hiệu/thực thể (truy vấn "Jannie Nguyễn") |
| "Lợi Ích Của Chương Trình Coaching" (3 pillar, đầy đủ) | Rút gọn thành 1 đoạn + nút "Xem chương trình Coaching" → `/coaching/` |
| "Chi Tiết Gói Đào Tạo & Cố Vấn" (3 thẻ gói, đầy đủ) | Giữ 3 thẻ nhưng chỉ tên gói + 1 dòng, liên kết sang `/coaching/` (không lặp mô tả dài) |
| "Cảm Nhận Học Viên…" | 1–2 lời chứng thực + link `/client-success-story/` |
| Thêm mới | Thẻ dẫn sang `/course/` và `/my-story/` (hiện trang chủ không dẫn nội dung tới 2 trang này ngoài menu) |

Sau khi đổi, chạy lại `page_overlap_audit.py`: mục tiêu cosine ý định `/` ↔ `/coaching/` < 0.84 và tỷ lệ lặp < 0.5. Cần cập nhật `scripts/verify-step-3-visual-parity.mjs` vì bố cục khác wireframe.

## 2. `/course/` dùng lại khối của `/coaching/`

- `/course/` chỉ có: lời dẫn + khung "Danh Mục Khóa Học" (đang ẩn với khách — F14-03) + **"Lợi Ích Của Chương Trình Coaching"** + form đặt lịch `[FACT]`. Khối "Lợi Ích…" nói về *coaching*, không phải *khóa học*, nên trang khóa học đang mang ý định của trang coaching.
- **Đề xuất:** khi có `UNK-08` (danh sách khóa học, đối tượng, thời lượng, học phí), thay khối "Lợi Ích Của Chương Trình Coaching" bằng nội dung khóa học thật và thêm schema `Course` (`provider` → `#organization`). **Không** gỡ khối này trước khi có `UNK-08`, vì trang sẽ gần như trống.

## 3. `/booking/`: H1 và H2 gần trùng

- H1 "Đăng Ký Tư Vấn & Xếp Lịch Coaching 1:1" và H2 của form "Đăng Ký Tư Vấn & Xếp Lịch Coaching" `[FACT]`. H2 nằm trong pattern form dùng chung trên 6 trang.
- **Đề xuất (LOW):** trên riêng `/booking/`, đổi H2 của form thành nhãn bước, ví dụ "Thông Tin Đặt Lịch Của Bạn".

## 4. Khối trả lời trực tiếp (AEO) — chỉ dùng dữ kiện đã có trên site

Đặt ngay dưới H1 (hoặc thành câu hỏi FAQ). Mỗi khối 40–60 từ, trả lời trước, giải thích sau. Nguồn dữ kiện ghi bên cạnh; không thêm số liệu, giá, giải thưởng hay cam kết mới.

| Trang | Câu hỏi | Khối trả lời đề xuất | Nguồn `[FACT]` |
|---|---|---|---|
| `/` | Jannie Nguyễn là ai? | Jannie Nguyễn là chuyên gia cố vấn thẩm mỹ với hơn 20 năm kinh nghiệm thực chiến, Nghệ nhân Bàn Tay Vàng ngành phun xăm thẩm mỹ và người sáng lập phương pháp cân bằng tự nhiên The Balance. Chuyên gia nhận cố vấn 1:1 cho cá nhân và chủ cơ sở thẩm mỹ, phun xăm, spa — trực tuyến hoặc trực tiếp tại TP. Hồ Chí Minh. | `JN_SEO_Brand::get()` (job_title, person_description), FAQ A1 |
| `/my-story/` | Phương pháp The Balance là gì? | The Balance là phương pháp cân bằng tự nhiên do Nghệ nhân Bàn Tay Vàng Jannie Nguyễn sáng lập và theo đuổi suốt hơn 20 năm theo nghề: làm đẹp từ sự cân bằng giữa thể chất, tâm trí và cảm xúc — một phương pháp đưa tinh thần Việt ra quốc tế. | Lời dẫn `/my-story/` (`scripts/lib/jn-page-copy.php`), pattern `expert-intro.php` ("Khái Niệm 'The Balance'") |
| `/coaching/` | — | Đã có FAQ nhóm A (5 câu) + `FAQPage` | — |
| `/booking/` | — | Đã có FAQ nhóm A | — |
| `/course/` | Khóa học kéo dài bao lâu, học phí thế nào? | **Chưa viết** — chặn bởi `UNK-08` `[UNKNOWN]` | — |
| `/client-success-story/` | Học viên đạt kết quả gì? | **Chưa viết** — 3 lời chứng thực chưa xác minh (`UNK-03`) `[UNKNOWN]` | — |

> Lưu ý văn phong: không dùng đại từ nhân xưng cho chuyên gia ("chị/anh/cô") vì site chưa xác định; lặp tên hoặc dùng "chuyên gia".

## 5. Dữ kiện chủ dự án còn thiếu → tác động SEO/AEO

| Mã | Thiếu | Khi có thì code tự làm gì | Việc tay |
|---|---|---|---|
| `UNK-04` | Điện thoại, địa chỉ | `jn_brand_telephone` → `Organization.telephone` | Khôi phục `ProfessionalService`/`LocalBusiness` (ADR-012), Google Business Profile |
| `UNK-07` | Ảnh OG chính thức | Option `jn_step8_og_image` → `og:image`, `Organization.image` | — |
| `UNK-08` | Khóa học | — | Nội dung `/course/` + schema `Course` (mục 2) |
| `UNK-09` | Văn bản pháp lý | Khung `[CẦN NỘI DUNG]` tự hiện lại khi được thay bằng văn bản thật | Điền nội dung → hết cảnh báo "Thin Content" trên 2 trang pháp lý |
| `UNK-13` | Mạng xã hội | `jn_brand_same_as` → `sameAs` trên `Person`/`Organization` | — |
| `PACKAGE PRICES` | Giá gói | Giá sản phẩm gói ở trạng thái `publish` → `Offer` trong `Service` | FAQ B1 |

## Counter-Evidence · Confidence · Falsification

- **Counter-Evidence:** cosine ký tự chỉ là heuristic của skill, không phải dữ liệu thứ hạng thật; Google có thể vẫn xếp đúng `/coaching/` nhờ canonical và anchor text `[INFERENCE]`. Tỷ lệ lặp cao trên mọi cặp một phần do form đặt lịch dùng chung — phần lặp đó là chủ đích `[FACT]`.
- **Confidence:** cao cho phát hiện trùng lặp (đo trực tiếp); trung bình (~60%) cho tác động thứ hạng `[INFERENCE]`.
- **Falsification:** sau go-live, nếu Search Console cho thấy `/coaching/` nhận phần lớn lượt hiển thị cho cụm "cố vấn 1:1 / coaching ngành thẩm mỹ" và trang chủ chỉ nhận truy vấn thương hiệu, thì không cần tái cấu trúc trang chủ.

## Sources

1. `docs/jn/seo-aeo/SEO_AEO_Audit_2026-10-30/1. report/page-overlap.md` / `.json`.
2. `~/.claude/skills/sunext-SEO-AEO/scripts/seo_intent_cluster_and_scoring.py` — `char_ngrams`, `cosine_counter`, ngưỡng 0.84.
3. `wordpress/app/mu-plugins/jn-performance/class-jn-seo-brand.php` — dữ kiện thương hiệu đã xác minh.
4. `docs/jn/seo-aeo/SEO_AEO_Audit_2026-10-28/3. draft/faq-va-khoi-tra-loi-truc-tiep.md` — FAQ nhóm A/B.
