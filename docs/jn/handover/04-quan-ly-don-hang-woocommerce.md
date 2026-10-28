# 04 — Quản Lý Đơn Hàng WooCommerce & Đối Soát Thanh Toán

Toàn bộ đơn hàng (bao gồm cả đơn phí dịch vụ **Coaching / Mentorship** được tạo tự động khi xác nhận lịch hẹn và đơn mua sản phẩm tại trang **My Favorite Product**) đều được lưu trữ trong hệ thống **WooCommerce HPOS** tốc độ cao.

---

## 1. Xem Danh Sách & Chi Tiết Đơn Hàng

1. Đăng nhập vào trang quản trị `/<WP_LOGIN_SLUG>`.
2. Chọn menu **WooCommerce → Đơn hàng (`Orders`)** ở cột bên trái.
3. Ý nghĩa các trạng thái đơn hàng thường gặp:
   - **`Chờ thanh toán` (`Pending payment`):** Đơn hàng gói Coaching vừa được tạo khi trợ lý xác nhận lịch hẹn, đang chờ học viên bấm liên kết `order-pay` trong email để thanh toán.
   - **`Tạm giữ` (`On hold`):** Học viên đã bấm xác nhận đặt hàng bằng phương thức **Chuyển khoản ngân hàng (`BACS`)**, đang chờ kế toán/trợ lý kiểm tra sao kê ngân hàng.
   - **`Đang xử lý` (`Processing`) / `Đã hoàn thành` (`Completed`):** Học viện đã nhận đủ tiền thanh toán.
   - **`Đã hủy` (`Cancelled`):** Đơn hàng đã bị hủy.

---

## 2. Xác Nhận Khi Học Viên Đã Chuyển Khoản Ngân Hàng

Khi kế toán/trợ lý nhận được thông báo biến động số dư ngân hàng khớp với mã đơn hàng (ví dụ: `Order #175`):

1. Mở đơn hàng đó trong **WooCommerce → Đơn hàng**.
2. Đổi ô **Trạng thái (`Status`)** sang **`Đã hoàn thành` (`Completed`)** (hoặc `Đang xử lý`).
3. Bấm nút **`Cập nhật` (`Update`)** ở góc phải.

**Hệ thống sẽ tự động đồng bộ 2 chiều ngay lập tức:**
- Hồ sơ **Lịch hẹn** liên kết với đơn hàng đó tự động chuyển trạng thái thanh toán sang **`Đã thanh toán`**.
- Gửi email xác nhận hoàn tất đơn hàng cho học viên và gửi thông báo *"Đã nhận thanh toán"* cho trợ lý phụ trách.
- Tổng chi tiêu của học viên trong mục **Khách hàng (CRM)** được cộng dồn tự động.

---

## 3. Xử Lý Đơn Hàng Hủy Có Gắn Cờ "Cần Hoàn Tiền Thủ Công"

Theo quy tắc an toàn tài chính của Học viện:
- Nếu một lịch hẹn bị chuyển sang **`Đã hủy`** khi đơn hàng **chưa thanh toán**, hệ thống sẽ tự động hủy đơn hàng đó.
- Nếu lịch hẹn bị chuyển sang **`Đã hủy`** sau khi khách **đã thanh toán**, hệ thống **tuyệt đối không tự động rút tiền hoàn trả** mà sẽ gắn nhãn cảnh báo màu đỏ:
  > **`Đã thanh toán — Cần hoàn tiền thủ công`**
- Khi thấy nhãn này, kế toán/quản lý cửa hàng kiểm tra lại chính sách đổi/hủy lịch hẹn của Học viện, thực hiện chuyển khoản hoàn tiền thủ công (nếu thuộc diện hoàn phí), sau đó vào đơn hàng WooCommerce bấm nút **Hoàn tiền (`Refund`)** và ghi chú mã giao dịch ngân hàng để lưu sổ kế toán.

---

## 4. Cập Nhật Số Tài Khoản Ngân Hàng Nhận Chuyển Khoản

Khi cần thay đổi số tài khoản ngân hàng hiển thị trên trang thanh toán và trong email gửi học viên:
1. Vào **WooCommerce → Cài đặt (`Settings`) → Thanh toán (`Payments`)**.
2. Tại dòng **Chuyển khoản ngân hàng trực tiếp (`Direct bank transfer - BACS`)**, bấm **Quản lý (`Manage`)**.
3. Điền chính xác **Tên tài khoản (Chủ tài khoản)**, **Số tài khoản**, **Tên ngân hàng** và **Chi nhánh / Nội dung chuyển khoản gợi ý**.
4. Bấm **Lưu thay đổi (`Save changes`)**.
