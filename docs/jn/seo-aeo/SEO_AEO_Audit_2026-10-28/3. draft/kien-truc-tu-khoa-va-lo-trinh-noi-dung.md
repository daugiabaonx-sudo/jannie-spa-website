# BẢN NHÁP — Kiến Trúc Từ Khóa Hub-Spoke & Lộ Trình Nội Dung (đã chọn lọc từ output skill)

> Nguồn thô: [`ban-thiet-ke-tu-khoa-seo-geo.md`](../1.%20report/ban-thiet-ke-tu-khoa-seo-geo.md) — `seo_os_cli.py keyword-architect` chạy Mode A (không cần API key), 705 từ khóa, 11 cụm.
> Tài liệu này **đánh giá phản biện** output đó và ánh xạ sang các URL đang có. Mọi chỉ số `KD` / `Opportunity` là **ước lượng** `[INFERENCE]` từ Autocomplete, không phải Search Volume thật.

## 1. Đánh giá chất lượng output thô (Counter-Evidence)

| Vấn đề | Ví dụ | Xử lý |
|---|---|---|
| Từ khóa sinh từ ma trận modifier, không có người tìm thật | `cố vấn kinh doanh spa nhận làm`, `thuê đào tạo phun xăm thẩm mỹ`, `jannie nguyễn mua ở đâu` | Loại |
| Hạt giống bị tách sai từ mô tả | Cụm `CL-08` "`bảng giá sâu (phun xăm`" | Loại |
| Sai địa lý | `đào tạo phun xăm thẩm mỹ tại hà nội` (thương hiệu ở TP.HCM) | Loại cho đến khi có cơ sở tại Hà Nội |
| Điểm Opportunity > 100 ở bảng cụm (`120.4`) | `CL-01` | Chỉ dùng thứ tự tương đối, không dùng giá trị tuyệt đối |
| Cảnh báo cannibalization hợp lệ | `bảng giá` ↔ `báo giá` ↔ `chi phí` cùng chủ đề (cosine ≥ 0.84) | Gộp vào **một** URL (H2 + FAQ) |

## 2. Ánh xạ cụm → URL hiện có (không tạo trang trùng ý định)

| Cụm (đã lọc) | Ý định | URL đích | Hành động nội dung (chờ duyệt) |
|---|---|---|---|
| `cố vấn 1:1 ngành thẩm mỹ` · `coaching ngành thẩm mỹ` · `mentor chủ spa` | BOFU | `/coaching/` (Money page) | Giữ H1 hiện tại; thêm H2 dạng câu hỏi + FAQ nhóm A ([bản nháp FAQ](faq-va-khoi-tra-loi-truc-tiep.md)); thêm bảng giá khi `PACKAGE PRICES` được công bố |
| `đặt lịch tư vấn 1:1` | BOFU | `/booking/` | Giữ biểu mẫu; FAQ A2–A3 |
| `cố vấn kinh doanh spa` · `tư vấn mở spa` | BOFU/MOFU | `/coaching/` (gói 60 phút) → về sau tách trang dịch vụ riêng nếu có nội dung đủ sâu | Không tách khi chưa có ≥ 800 từ nội dung riêng |
| `đào tạo phun xăm thẩm mỹ tphcm` · `khóa học điêu khắc chân mày` | BOFU | `/course/` | **Chặn bởi `COURSES = UNKNOWN`** — cần lịch khai giảng, thời lượng, học phí, chứng nhận |
| `jannie nguyễn` (thương hiệu) | Navigational | `/` + `/my-story/` | Đã có `Person` + `ProfilePage` schema; thêm `sameAs` khi có mạng xã hội (`UNK-13`) |
| `cảm nhận học viên jannie nguyễn` | MOFU | `/client-success-story/` | Chỉ đăng lời chứng thực có xác minh & xin phép |

## 3. Lộ trình nội dung 12 tuần đề xuất (sau go-live, khi có dữ kiện)

| Tuần | Trang / bài | Ý định | Điều kiện tiên quyết |
|---|---|---|---|
| 1–2 | Hoàn thiện `/coaching/`: FAQ nhóm A + giá gói | BOFU | Duyệt FAQ, giá gói |
| 3–4 | `/course/`: trang khóa học thật | BOFU | `COURSES` |
| 5–6 | Bài hub "Mở spa cần chuẩn bị gì?" → liên kết về gói 60 phút | MOFU | Chủ dự án duyệt dàn ý |
| 7–8 | Bài "Phun xăm tự nhiên khác gì phun xăm truyền thống?" (phương pháp The Balance) | TOFU | Nội dung chuyên môn từ chuyên gia |
| 9–10 | 2–3 case study học viên (có số liệu được phép công bố) | MOFU | Xác minh & xin phép |
| 11–12 | Rà GSC: các truy vấn ở vị trí 4–20 → bổ sung H2/FAQ | Tối ưu | Có dữ liệu GSC (cần domain production) |

> [!NOTE]
> Website hiện **không có blog**, nên các bài TOFU/MOFU cần một khu vực bài viết (post type `post` của core). Việc bật lại archive/blog phải tương thích chính sách tắt feed (ADR-010) và Hide-WordPress (§6) — cần chủ dự án quyết định trước khi làm.

## 4. Nguồn backlink / trích dẫn thực thể phù hợp (lọc từ kho 5.510 nguồn)

**Nên làm (liên quan ngành, an toàn):** Google Business Profile, Bing Places, Apple Business Connect, Facebook Page, Instagram, LinkedIn, Pinterest (ảnh trước/sau có xin phép), Trang Vàng Việt Nam (`trangvangvietnam.com`), Foursquare, OpenStreetMap; báo chí ngành làm đẹp Việt Nam (PR có biên tập); Class Central chỉ khi có khóa học online công khai.

**Loại bỏ (không liên quan ngành, skill gợi ý nhầm):** npmjs, PyPI, Hugging Face, GitHub, HG.org, Chambers, ArchDaily, Michelin Guide, Airbnb/Booking, Clutch/GoodFirms (dành cho agency B2B), các site subdomain miễn phí (Blogger, Wix, Weebly — rủi ro bị xem là mạng lưới backlink kém chất lượng).

> [!WARNING]
> Mọi hồ sơ trích dẫn (citation) phải dùng **cùng một NAP** (tên, địa chỉ, điện thoại). Hiện địa chỉ đầy đủ và điện thoại còn `UNKNOWN` (`UNK-04`) → chỉ nên tạo hồ sơ sau khi chủ dự án chốt NAP.
