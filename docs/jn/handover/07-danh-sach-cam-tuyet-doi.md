# 07 — Danh Sách "Tuyệt Đối Không Làm" (`Do Not List`)

Để bảo vệ kiến trúc tinh gọn, tốc độ tải trang tức thì (**PageSpeed 100/100**), khả năng ẩn dấu vết WordPress và tính ổn định lâu dài của website Jannie Nguyễn, toàn bộ quản trị viên và nhân sự **cam kết tuân thủ nghiêm ngặt 7 điều cấm dưới đây**:

---

## 1. TUYỆT ĐỐI KHÔNG cài đặt Elementor hoặc bất kỳ trình dựng trang (`Page Builder`) nào khác
- **Không bao giờ cài đặt:** *Elementor, Divi, WPBakery, Beaver Builder, Brizy, Oxygen*, hoặc các bộ thư viện khối nặng nề của bên thứ ba (*Spectra, Kadence Blocks, Stackable, GenerateBlocks, Otter Blocks...*).
- **Lý do:** Các plugin này nạp hàng trăm KB mã CSS/JavaScript thừa trên mọi trang, phá vỡ ngay lập tức ngân sách tốc độ (`Performance >= 95`), làm lệch bảng màu/phông chữ thương hiệu và gây xung đột khi nâng cấp WordPress.
- **Cách làm đúng:** Chỉ sử dụng các khối gốc của Gutenberg và bộ 10 mẫu thiết kế có sẵn trong danh mục **`Jannie Nguyen`** (xem [Hướng dẫn 01](./01-chinh-sua-trang-gutenberg.md)).

---

## 2. TUYỆT ĐỐI KHÔNG tự ý cài đặt các Plugin chưa được phê duyệt
- Website đã có đầy đủ mọi tính năng cần thiết (Đặt lịch, CRM khách hàng, Xuất CSV, Bảo mật, Tốc độ, SEO, 2FA) thông qua plugin độc quyền `jn-core` và 2 mô-đun hệ thống `jn-hardening.php`, `jn-performance.php`.
- **Không tự ý cài thêm:** plugin tạo form khác (*Contact Form 7, WPForms*), plugin bảo mật nặng (*Wordfence, iThemes*), plugin SEO khác (*Yoast SEO, Rank Math* — sẽ xung đột trực tiếp với *The SEO Framework* đang chạy), hay các plugin chèn hiệu ứng/popup.
- Nếu thực sự cần một tính năng mới, bắt buộc phải hỏi ý kiến kỹ thuật và kiểm tra trên môi trường Staging trước.

---

## 3. TUYỆT ĐỐI KHÔNG chỉnh sửa trực tiếp tệp mã nguồn của Blocksy, WooCommerce hoặc WordPress Core
- Không bao giờ dùng FTP/SSH để sửa bất kỳ file nào nằm trong:
  - Giao diện mẹ `/app/themes/blocksy/`
  - Thư mục plugin `/app/modules/woocommerce/` hoặc các plugin bên thứ ba khác
  - Thư mục gốc của WordPress (`wp-includes/`, `wp-admin/`)
- **Lý do:** Mọi thay đổi trực tiếp trên giao diện mẹ hoặc plugin bên thứ ba sẽ bị **xóa mất hoàn toàn** ngay trong lần cập nhật tiếp theo và làm hỏng cơ chế kiểm tra chữ ký an toàn. Mọi tùy biến giao diện chỉ được đặt trong giao diện con `/app/themes/jannie/` và plugin `/app/modules/jn-core/`.

---

## 4. TUYỆT ĐỐI KHÔNG dùng ảnh AI tạo sẵn cho chân dung Jannie Nguyễn hoặc Học viên, và KHÔNG tải ảnh gốc chưa thu nhỏ (`> 2000 px`)
- Không sử dụng hình ảnh do AI sinh ra để minh họa cho Chuyên gia Jannie Nguyễn hoặc khách hàng có tên thật nhằm bảo vệ tuyệt đối uy tín thương hiệu.
- Không tải trực tiếp ảnh gốc nặng hàng chục MB từ máy ảnh/điện thoại khi chưa giảm kích thước chiều rộng/cao về `<= 2000 px`.

---

## 5. TUYỆT ĐỐI KHÔNG nhúng mã theo dõi bên thứ ba (Facebook Pixel, Google Analytics, Zalo Widget) trực tiếp vào `<head>`
- Mọi mã theo dõi hoặc hộp chat bên thứ ba nếu nạp ngay khi mở trang sẽ làm tụt điểm PageSpeed từ `100` xuống `60–70`.
- Nếu sau này Học viện muốn gắn mã phân tích hoặc nút chat, hãy khai báo qua cổng chờ thông minh (`Third-Party Facade` trong `jn-performance.php`) để mã chỉ được tải sau khi khách hàng chạm tay hoặc di chuột trên trang.

---

## 6. TUYỆT ĐỐI KHÔNG dùng chung tài khoản Quản trị hoặc tắt Xác thực 2 lớp (`2FA`)
- Mỗi nhân sự phải dùng đúng tài khoản theo vai trò được cấp (`Quản lý cửa hàng`, `Trợ lý lịch hẹn`, `Biên tập nội dung`).
- Không tắt plugin **Two Factor** và không chia sẻ mật khẩu qua tin nhắn văn bản không mã hóa.

---

## 7. TUYỆT ĐỐI KHÔNG đổi tên thư mục của giao diện mẹ (`blocksy`) hoặc các plugin bên thứ ba trên ổ đĩa
- Hệ thống ẩn dấu vết WordPress (`jn-hardening.php`) đã tự động ánh xạ đường dẫn tĩnh sang `/static/base/`, `/static/shop/`, `/static/ext/` một cách an toàn. Việc đổi tên thư mục vật lý trên ổ đĩa sẽ làm đứt kết nối cập nhật bảo mật tự động của WordPress.
