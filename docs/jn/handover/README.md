# Bộ Tài Liệu Bàn Giao Vận Hành Website Jannie Nguyễn (`docs/jn/handover/`)

Chào mừng Chủ sở hữu (**Chuyên gia Jannie Nguyễn**) và đội ngũ **Trợ lý Học viện**!

Bộ tài liệu này được biên soạn bằng tiếng Việt dễ hiểu, kèm hình ảnh chụp trực tiếp từ hệ thống thật để bất kỳ nhân sự nào (không cần biết lập trình) cũng có thể tự tin vận hành website hàng ngày một cách an toàn, giữ vững tốc độ tải trang tối đa (**PageSpeed 99–100/100**) và bảo mật tuyệt đối.

---

## Danh Mục Tài Liệu Hướng Dẫn

1. [**01 — Chỉnh sửa trang và hình ảnh trong Gutenberg**](./01-chinh-sua-trang-gutenberg.md)
   - Cách sửa chữ, kéo thả sắp xếp các phân đoạn (`List View`), chèn mẫu thiết kế có sẵn (`Patterns — Jannie Nguyen`) và thay thế hình ảnh chuẩn kích thước (`<= 2000 px`).
2. [**02 — Quản lý Lịch hẹn Coaching & Gửi liên kết thanh toán**](./02-quan-ly-lich-hen-va-thanh-toan.md)
   - Quy trình 5 trạng thái lịch hẹn, kiểm tra trùng giờ, xác nhận lịch hẹn để tự động tạo đơn hàng và gửi email kèm lịch `.ics` + liên kết thanh toán `order-pay`, quản lý gói Master Mentorship 12 buổi.
3. [**03 — Quản lý Khách hàng (CRM) & Xuất báo cáo Excel (CSV)**](./03-quan-ly-khach-hang-va-xuat-csv.md)
   - Tra cứu khách hàng theo số điện thoại/email, phân loại khách (`Cá nhân`, `Thợ – Học viên`, `Chủ cơ sở`), ghi chú nội bộ, tạo lịch hẹn qua điện thoại (`Walk-in`) và xuất file CSV tiếng Việt mở trực tiếp trên Microsoft Excel.
4. [**04 — Quản lý Đơn hàng WooCommerce & Đối soát thanh toán**](./04-quan-ly-don-hang-woocommerce.md)
   - Cách kiểm tra đơn hàng, xác nhận khi học viên đã chuyển khoản ngân hàng, và xử lý các đơn hủy có gắn cờ *"Cần hoàn tiền thủ công"*.
5. [**05 — Tài khoản Nhân sự, Phân quyền & Bảo mật 2 lớp (2FA)**](./05-tai-khoan-nhan-su-va-bao-mat-2fa.md)
   - Đường dẫn đăng nhập riêng tư `/<WP_LOGIN_SLUG>`, 4 nhóm quyền hạn nhân sự và cách cài đặt mã xác thực 2 lớp (`2FA`).
6. [**06 — Cập nhật an toàn, Sao lưu & Kiểm tra PageSpeed hàng tháng**](./06-cap-nhat-an-toan-sao-luu-va-pagespeed.md)
   - Quy tắc cập nhật phần mềm an toàn (luôn thử trên bản nháp Staging trước), lịch sao lưu dữ liệu và cách tự kiểm tra điểm tốc độ hàng tháng.
7. [**07 — Danh sách "Tuyệt Đối Không Làm" (`Do Not List`)**](./07-danh-sach-cam-tuyet-doi.md)
   - Các nguyên tắc sống còn để không làm hỏng giao diện, làm chậm tốc độ hoặc gây lỗ hổng bảo mật cho website.

---

## Thông Tin Truy Cập Nhanh

- **Cổng đăng nhập dành riêng cho Nhân sự:** `/<WP_LOGIN_SLUG>` *(Lưu ý: đường dẫn `/wp-admin/` hay `/wp-login.php` mặc định đã được khóa ẩn và trả về trang `404` khi chưa đăng nhập để chống dò quét).*
- **Trang Tài khoản dành cho Học viên / Khách hàng:** `/tai-khoan/`
- **Email đại diện hệ thống:** `Jannie Nguyen Academy <contact@jannienguyen.vn>`
