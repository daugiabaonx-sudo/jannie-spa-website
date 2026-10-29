# 05 — Tài Khoản Nhân Sự, Phân Quyền & Bảo Mật 2 Lớp (`2FA`)

Để bảo vệ tuyệt đối dữ liệu khách hàng VIP và uy tín thương hiệu Jannie Nguyễn, hệ thống áp dụng cơ chế **phân quyền tối thiểu theo đúng chức trách** và **bắt buộc xác thực 2 lớp (`2FA`)** đối với toàn bộ nhân sự.

---

## 1. Cổng Đăng Nhập Riêng Biệt Cho Nhân Sự (`/<WP_LOGIN_SLUG>`)

- Nhân sự Học viện **chỉ đăng nhập tại đường dẫn riêng:**
  ```text
  https://<ten-mien-cua-ban>/<WP_LOGIN_SLUG>
  ```
  `<WP_LOGIN_SLUG>` là đường dẫn bí mật, **không ghi trong tài liệu**: quản trị viên nhận giá trị thật qua kênh bảo mật (biến `WP_LOGIN_SLUG` trong `.env.production` trên máy chủ). Muốn đổi đường dẫn, chỉ cần sửa biến này — không cần sửa mã nguồn hay cơ sở dữ liệu.
- Các đường dẫn mặc định của WordPress (`/wp-login.php`, `/wp-admin/`) đã được khóa ẩn và trả về trang `404` đối với người lạ.
- **Chống dò mật khẩu tự động (`Rate Limiting`):** Nếu một người nhập sai mật khẩu quá **5 lần liên tiếp trong vòng 15 phút**, hệ thống sẽ tự động khóa tạm thời cổng đăng nhập đối với địa chỉ IP/tài khoản đó trong 15 phút.

![Giao diện cổng đăng nhập nhân sự mang nhận diện Jannie Nguyen Academy](./images/13-cong-dang-nhap-nhan-su.png)

---

## 2. Bảng Phân Quyền 4 Vai Trò Nhân Sự

Mỗi thành viên trong đội ngũ được cấp một tài khoản riêng ứng với đúng công việc (tuyệt đối không dùng chung 1 tài khoản Quản trị):

| Vai trò nhân sự | Tài khoản mẫu bàn giao | Quyền hạn được phép | Giới hạn bảo mật |
| :--- | :--- | :--- | :--- |
| **1. Quản trị viên (`Administrator`)** | `<WP_ADMIN_USER>` | Toàn quyền quản lý hệ thống, cài đặt bảo mật, phân quyền nhân sự. | Bắt buộc 2FA. Chỉ dành cho Chủ sở hữu / Quản trị kỹ thuật. |
| **2. Quản lý cửa hàng (`Shop Manager`)** | `quan_ly_cua_hang` | Quản lý toàn bộ **Lịch hẹn**, **Khách hàng CRM**, **Cài đặt lịch hẹn**, **Đơn hàng & Sản phẩm WooCommerce**, **Xuất CSV**. | Bắt buộc 2FA. Không được cài/xóa plugin hay sửa tài khoản Quản trị. |
| **3. Trợ lý lịch hẹn (`Trợ lý lịch hẹn`)** | `tro_ly_lich_hen` | Xử lý **Lịch hẹn** (xác nhận lịch, tạo đơn, gửi link thanh toán), chăm sóc **Khách hàng CRM**, **Xuất CSV**. | Bắt buộc 2FA. Không được xóa lịch hẹn/khách hàng, không sửa sản phẩm hay cài đặt hệ thống. |
| **4. Biên tập nội dung (`Editor`)** | `bien_tap_noi_dung` | Chỉnh sửa nội dung các **Trang**, **Bài viết**, **Lời chứng thực** và **Thư viện hình ảnh**. | Bắt buộc 2FA. Không được xem dữ liệu Khách hàng CRM, Lịch hẹn hay Đơn hàng. |

> *(Chi tiết kỹ thuật về ma trận quyền hạn được lưu tại [`docs/jn/roles.md`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/roles.md)).*

---

## 3. Hướng Dẫn Đăng Nhập Trực Tiếp (Đã Tắt 2FA)

- Tính năng bắt buộc Xác thực 2 lớp (`2FA`) đã được tắt theo yêu cầu vận hành để hỗ trợ đăng nhập nhanh.
- Nhân sự và Quản trị viên chỉ cần truy cập `/<WP_LOGIN_SLUG>`, nhập **Tên người dùng / Email** và **Mật khẩu** là vào thẳng trang quản trị `/wp-admin/` mà không cần nhập mã OTP.
