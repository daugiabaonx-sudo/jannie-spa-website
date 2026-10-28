# BẢN NHÁP — FAQ & Khối Trả Lời Trực Tiếp (chờ chủ dự án duyệt)

> [!IMPORTANT]
> Đây là **bản nháp**. Chưa có nội dung nào dưới đây được đưa lên website. Sau khi duyệt, chèn pattern **"FAQ — Câu Hỏi Thường Gặp"** (`jannie/faq`) vào trang `/coaching/` (và/hoặc `/booking/`), thay chữ `[NHÁP]` bằng nội dung đã duyệt. Khi trong câu hỏi/trả lời không còn `[NHÁP]`, hệ thống tự sinh `FAQPage` JSON-LD.

Quy tắc biên soạn (skill `sunext-semrush-ahrefs-os`, playbook 08 — khối trả lời trực tiếp): câu hỏi dạng tự nhiên ở `summary`; câu trả lời 40–60 từ, câu đầu trả lời thẳng, câu sau nêu điều kiện/con số **đã xác nhận**. Không đưa con số chưa được xác nhận (giá, % kết quả) vào câu trả lời.

## Nhóm A — Chỉ dùng dữ kiện đã có trong wireframe / Context Card `[FACT]`

| # | Câu hỏi đề xuất | Câu trả lời nháp | Nguồn dữ kiện |
|---|---|---|---|
| A1 | Buổi tư vấn 1:1 cùng Jannie Nguyễn diễn ra ở đâu? | Bạn có thể chọn gặp trực tuyến qua Zoom chất lượng cao hoặc gặp trực tiếp tại Academy ở TP. Hồ Chí Minh. Hình thức gặp được chọn ngay trong biểu mẫu đặt lịch, và trợ lý sẽ xác nhận lại cùng khung giờ trước buổi gặp. | `wireframe-spec.md` §2f — trường `coaching-format` (`Online Trực Tuyến (Zoom High-Res)` / `Trực tiếp tại Academy (TP.HCM)`) |
| A2 | Sau khi gửi yêu cầu đặt lịch, bao lâu thì tôi được liên hệ? | Trợ lý học viện sẽ liên hệ với bạn trong vòng 24 giờ làm việc để thống nhất ngày giờ cụ thể. Yêu cầu đặt lịch chưa phải là lịch hẹn chính thức cho đến khi trợ lý xác nhận khung giờ phù hợp với cả hai bên. | `wireframe-spec.md` §2f.2 Bước 2 |
| A3 | Khi nào tôi cần thanh toán? | Bạn chỉ thanh toán sau khi trợ lý đã xác nhận khung giờ. Liên kết thanh toán sẽ được gửi qua email kèm thông tin lịch hẹn, nên bạn không phải trả trước khi gửi yêu cầu đặt lịch. | Context Card: `PAYMENT POLICY` |
| A4 | Chương trình Master Mentorship 3 tháng gồm những gì? | Master Mentorship là chương trình đồng hành toàn diện nhất, gồm 12 buổi coaching chuyên sâu 1:1, gặp trực tiếp hoặc online. Jannie Nguyễn trực tiếp cùng bạn thiết lập lại hệ thống, từ kỹ thuật lõi đến chiến lược thương hiệu dài hạn. | `wireframe-spec.md` (thẻ gói Mentorship: `12 buổi coaching chuyên sâu 1:1 trực tiếp/online`) |
| A5 | Nên chọn gói 30 phút, 60 phút hay Mentorship? | Gói 30 phút phù hợp với cá nhân cần gỡ rối nhanh hoặc giải đáp kỹ thuật. Gói 60 phút dành cho chủ spa hoặc viện thẩm mỹ muốn tối ưu vận hành và doanh thu. Mentorship 3 tháng dành cho người cần đồng hành dài hạn để thiết lập lại toàn bộ hệ thống. | Mô tả 3 gói trên `/coaching/` |

## Nhóm B — Cần chủ dự án cung cấp dữ kiện `[UNKNOWN]`

| # | Câu hỏi | Thiếu dữ kiện |
|---|---|---|
| B1 | Chi phí từng gói cố vấn là bao nhiêu? | `PACKAGE PRICES` = UNKNOWN |
| B2 | Có thể đổi hoặc hủy lịch hẹn không, phí ra sao? | Chính sách đổi lịch (điều khoản dịch vụ) chưa có nội dung chính thức |
| B3 | Khóa học điêu khắc / phun xăm khai giảng khi nào, học bao lâu? | `COURSES` = UNKNOWN |
| B4 | Học viên được cấp chứng nhận gì? | Chưa có dữ kiện |
| B5 | Số điện thoại / Zalo liên hệ nhanh? | `UNK-04` |

## Counter-Evidence · Confidence · Falsification

- **Counter-Evidence:** Google đã giới hạn rich result FAQ cho site chính phủ/y tế từ 2023, nên FAQ **không** tạo đoạn trích mở rộng trên SERP. Giá trị chính là cho máy trả lời AI (ChatGPT Search, Perplexity, AI Overviews) và cho trải nghiệm người đọc. `[FACT]`
- **Confidence:** Trung bình–cao (`~70%`) `[INFERENCE]` — câu trả lời ngắn, tự chứa, có thực thể và điều kiện rõ thì dễ được trích dẫn hơn.
- **Falsification:** Sau 60 ngày go-live, nếu các prompt `GEO-P01..P05` (ví dụ "đặt lịch tư vấn 1:1 với Jannie Nguyễn thế nào?") không trả về trích dẫn từ `/coaching/` trên ít nhất 1 máy trả lời AI, thì cần xem lại nội dung/định dạng.
