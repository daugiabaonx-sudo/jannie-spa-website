# 01 — Hướng Dẫn Chỉnh Sửa Trang & Hình Ảnh Trong Gutenberg

Website Jannie Nguyễn được xây dựng hoàn toàn bằng trình soạn thảo khối gốc (**Gutenberg Block Editor**) kết hợp bộ mẫu thiết kế độc quyền (**Patterns `Jannie Nguyen`**). Bạn có thể tự thay đổi câu chữ, đổi thứ tự các phần hoặc thay ảnh mới mà không lo làm vỡ bố cục hay lệch màu sắc.

---

## 1. Mở Trang Cần Chỉnh Sửa & Bật "Xem Danh Sách" (`List View`)

1. Đăng nhập vào cổng quản trị `/phong-lam-viec-7429`.
2. Ở thanh menu bên trái, chọn **Trang (`Pages`) → Tất cả các trang (`All Pages`)**.
3. Di chuột vào trang muốn sửa (ví dụ: **Coaching**, **Home**, **My Story**) và bấm **Chỉnh sửa (`Edit`)**.
4. Ở góc trên cùng bên trái của màn hình soạn thảo, bấm vào biểu tượng **3 vạch ngang lệch (`Document Overview / List View`)** để mở cây cấu trúc các phân đoạn của trang.

![Cấu trúc phân đoạn rõ ràng trong List View của Gutenberg](./images/01-gutenberg-list-view.png)

---

## 2. Kéo Thả Sắp Xếp Thứ Tự Các Phân Đoạn (`Drag & Drop Sections`)

- Trong cột **Xem danh sách (`List View`)** bên trái, mỗi phân đoạn lớn của trang được đặt trong một khối **Nhóm (`Group`)** cấp cao nhất.
- Để đổi vị trí một phân đoạn:
  1. Nhấp giữ chuột vào tên khối **Nhóm (`Group`)** đó trong cột **List View** (hoặc dùng nút mũi tên **Lên / Xuống** trên thanh công cụ nổi).
  2. Kéo phân đoạn tới vị trí mong muốn rồi thả chuột.
  3. Bấm nút **Lưu (`Save` / `Update`)** ở góc trên bên phải.

![Kéo thả thay đổi thứ tự phân đoạn trực tiếp trong List View](./images/02-gutenberg-keo-tha-phan-doan.png)

> **Mẹo an toàn:** Một số cụm chi tiết nhỏ (như thanh 3 con số thống kê ở đầu trang hoặc huy hiệu thời lượng gói) đã được khóa bảo vệ cấu trúc (`Content-Only Lock`) để bạn có thể bấm vào sửa chữ bên trong thoải mái mà không vô tình xóa mất khung viền trang trí.

---

## 3. Chèn Thêm Mẫu Thiết Kế Có Sẵn (`Insert Jannie Nguyen Patterns`)

Khi tạo một trang mới hoặc muốn thêm một phân đoạn chuẩn phong cách thương hiệu:

1. Bấm nút dấu cộng màu đen **`[ + ]` (`Toggle block inserter`)** ở góc trên cùng bên trái.
2. Chuyển sang tab **Mẫu (`Patterns`)**.
3. Chọn danh mục **`Jannie Nguyen`**.
4. Nhấp vào mẫu bạn muốn thêm (ví dụ: *Hero Editorial*, *3 Trụ cột giá trị*, *Bảng 3 gói Coaching*, *Giới thiệu Chuyên gia*, *Cảm nhận học viên*, *Khung Đặt lịch*): mẫu sẽ được chèn ngay vào trang với đúng phông chữ, khoảng cách và bảng màu chuẩn.

![Thư viện 10 mẫu giao diện trong danh mục Jannie Nguyen](./images/03-gutenberg-chen-pattern.png)

---

## 4. Thay Thế Hình Ảnh & Quy Tắc Chuẩn Bị Ảnh (`<= 2000 px`)

### Bước 4.1 — Quy tắc vàng trước khi tải ảnh lên: Thu nhỏ chiều rộng/cao về `<= 2000 px`
- Máy ảnh chuyên nghiệp thường xuất file ảnh gốc rất nặng (`5000–8000 px`, `10–25 MB`).
- **Trước khi tải ảnh lên website**, hãy mở ảnh bằng công cụ **Preview** (trên máy Mac) hoặc **Photos / Squoosh.app** và giảm cạnh dài nhất của ảnh xuống **tối đa `2000 px`** (gợi ý kích thước chuẩn):
  - **Ảnh chân dung Hero đầu trang (tỷ lệ `4:5`):** `800 × 1000 px` (hoặc `1200 × 1500 px`).
  - **Ảnh chuyên gia tư vấn (tỷ lệ `3:4`):** `750 × 1000 px` (hoặc `1200 × 1600 px`).
  - **Ảnh đại diện học viên (tỷ lệ `1:1` vuông):** `320 × 320 px` (hoặc `160 × 160 px`).
  - **Ảnh chia sẻ mạng xã hội (Open Graph):** `1200 × 630 px`.
- **Hệ thống tự động tối ưu:** Ngay khi bạn tải ảnh `.jpg`, `.png` hoặc `.webp` (`<= 2000 px`) lên, website sẽ **tự động cắt các kích thước đáp ứng và nén sang định dạng siêu nhẹ `AVIF` + `WebP`** (chỉ từ `5 KB – 20 KB`), giúp trang tải tức thì trên điện thoại.

### Bước 4.2 — Thao tác thay ảnh trong Gutenberg
1. Nhấp chuột vào bức ảnh cần thay trên trang.
2. Trên thanh công cụ nổi phía trên bức ảnh, bấm nút **Thay thế (`Replace`)**.
3. Chọn **Mở thư viện Media (`Open Media Library`)** hoặc **Tải lên (`Upload`)** để chọn bức ảnh thật từ máy tính.
4. Ở cột cài đặt bên phải (`Block` → `Image`), điền mô tả ngắn gọn có nghĩa bằng tiếng Việt vào ô **Văn bản thay thế (`Alt Text`)** (ví dụ: *"Chuyên gia Jannie Nguyễn tư vấn định hình chân mày phong thủy 1:1 tại Học viện"*).
5. Bấm **Lưu (`Update`)**.
