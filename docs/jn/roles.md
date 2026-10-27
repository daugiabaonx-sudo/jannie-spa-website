# Phân Quyền Nhân Sự & Quản Lý Tài Khoản (`docs/jn/roles.md`)

> **Dự án:** Website Jannie Nguyễn — Luxury Beauty Academy & Mentorship
> **Cập nhật lần cuối:** 2026-10-27 (JN Step 6 — Customer management and staff accounts)
> **Tham chiếu:** `.agent/rules/jn-project.md` (§4 Project Context Card), `docs/jn/decisions.md` (`ADR-008`, `ADR-009`), `wordpress/app/modules/jn-core/booking/class-jn-booking-roles.php`

---

## 1. Nguyên Tắc Đặc Quyền Tối Thiểu (Least Privilege)

Hệ thống phân quyền của Học viện Jannie Nguyễn được thiết kế theo nguyên tắc **đặc quyền tối thiểu (`Least Privilege`)**, tách biệt rạch ròi giữa 4 nhóm tài khoản nhân sự nội bộ và tài khoản khách hàng/học viên:

1. **Quản trị (`administrator`):** Chỉ dành cho Chủ sở hữu (Jannie Nguyễn) và Kỹ sư phát triển hệ thống.
2. **Quản lý cửa hàng (`shop_manager`):** Quản lý vận hành thương mại điện tử WooCommerce, đơn hàng HPOS, sản phẩm, khách hàng CRM, lịch hẹn và xuất báo cáo CSV; **không** được cài đặt/gỡ plugin, sửa mã nguồn, thay đổi cấu hình hệ thống cốt lõi hoặc tạo/nâng quyền tài khoản Quản trị.
3. **Trợ lý lịch hẹn (`jn_booking_assistant`):** Chuyên trách tiếp nhận, xác nhận khung giờ 1:1, quản lý hồ sơ khách hàng CRM, ghi chú nội bộ, tạo lịch hẹn điện thoại/trực tiếp (`Walk-in`) và xuất danh sách CSV phục vụ vận hành; **không** được sửa sản phẩm, thay đổi cài đặt cửa hàng, xóa lịch hẹn vĩnh viễn hay truy cập quản lý người dùng hệ thống (`Users`).
4. **Biên tập nội dung (`editor`):** Quản lý bài viết, trang tĩnh và thư viện truyền thông (Media); **không** được xem dữ liệu khách hàng CRM, lịch hẹn, đơn hàng hay xuất CSV.
5. **Khách hàng (`customer` / `subscriber`):** Chỉ sử dụng cổng khách hàng mặt tiền (`/tai-khoan/` — `My Account`); bị ẩn hoàn toàn thanh công cụ quản trị (`Admin Bar`) và tự động chuyển hướng khỏi `/wp-admin/` về `/tai-khoan/`.

---

## 2. Ma Trận Phân Quyền Chi Tiết (`Role & Capability Matrix`)

| Nhóm chức năng / Quyền hạn (`Capability`) | Quản trị (`administrator`) | Quản lý cửa hàng (`shop_manager`) | Trợ lý lịch hẹn (`jn_booking_assistant`) | Biên tập nội dung (`editor`) | Khách hàng (`customer`) |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **1. Bảo mật & Đăng nhập** | | | | | |
| Truy cập `/wp-admin/` & hiển thị Admin Bar | ✅ Có | ✅ Có | ✅ Có | ✅ Có | ❌ Chặn (Chuyển hướng `/tai-khoan/`) |
| Bắt buộc Xác thực 2 lớp (`Two Factor` — 2FA) | ✅ Bắt buộc | ✅ Bắt buộc | ✅ Bắt buộc | ✅ Bắt buộc | ⚪ Không bắt buộc |
| Đường dẫn đăng nhập nhân sự (`/phong-lam-viec-7429`) | ✅ Có | ✅ Có | ✅ Có | ✅ Có | ✅ Có (hoặc `/tai-khoan/`) |
| **2. Quản lý Khách hàng CRM (`Khách hàng`)** | | | | | |
| Xem danh sách & tìm kiếm khách hàng (`manage_jn_customers`) | ✅ Có | ✅ Có | ✅ Có | ❌ Không | ❌ Không |
| Xem hồ sơ chi tiết, lịch sử lịch hẹn, đơn hàng & nhật ký đồng ý bảo mật | ✅ Có | ✅ Có | ✅ Có | ❌ Không | ❌ Không *(chỉ xem của chính mình tại `/tai-khoan/`)* |
| Chỉnh sửa thông tin CRM (Loại khách, Cơ sở, Tỉnh/Thành, Nguồn, Tags) | ✅ Có | ✅ Có | ✅ Có | ❌ Không | ❌ Không |
| Xem & thêm Ghi chú nội bộ (`Ghi chú nội bộ` — Staff only) | ✅ Có | ✅ Có | ✅ Có | ❌ Không | ❌ Không *(tuyệt đối ẩn khỏi khách)* |
| Xem cảnh báo trùng lặp SĐT / Email (`jn_customer_duplicate_log`) | ✅ Có | ✅ Có | ✅ Có | ❌ Không | ❌ Không |
| Tạo lịch hẹn qua điện thoại / trực tiếp (`Tạo lịch hẹn` Walk-in) | ✅ Có | ✅ Có | ✅ Có | ❌ Không | ❌ Không |
| **3. Quản lý Lịch hẹn Coaching (`Lịch hẹn` — `jn_booking`)** | | | | | |
| Xem, lọc & xử lý lịch hẹn (`manage_jn_bookings`, `edit_jn_bookings`) | ✅ Có | ✅ Có | ✅ Có | ❌ Không | ❌ Không |
| Xác nhận lịch hẹn, xếp phòng họp Zoom/Địa điểm & sinh đơn hàng chờ thanh toán | ✅ Có | ✅ Có | ✅ Có | ❌ Không | ❌ Không |
| Thêm buổi hẹn tiếp theo cho học viên Mentorship (tối đa 12 buổi) | ✅ Có | ✅ Có | ✅ Có | ❌ Không | ❌ Không |
| Xóa vĩnh viễn bản ghi lịch hẹn (`delete_jn_bookings`) | ✅ Có | ✅ Có | ❌ Không | ❌ Không | ❌ Không |
| Cấu hình khung giờ, địa chỉ Học viện, chính sách lưu trữ (`manage_jn_booking_settings`) | ✅ Có | ✅ Có | ❌ Không | ❌ Không | ❌ Không |
| **4. Xuất Dữ liệu Báo cáo (`CSV Export`)** | | | | | |
| Xuất CSV danh sách Khách hàng & Lịch hẹn (`export_jn_data`, UTF-8 BOM) | ✅ Có | ✅ Có | ✅ Có | ❌ Không | ❌ Không |
| **5. Cửa hàng & Đơn hàng WooCommerce (`HPOS`)** | | | | | |
| Quản lý đơn hàng, sản phẩm, mã giảm giá, báo cáo (`manage_woocommerce`) | ✅ Có | ✅ Có | ❌ Không | ❌ Không | ❌ Không |
| Xem chi tiết đơn hàng liên kết từ lịch hẹn (`edit_shop_orders`) | ✅ Có | ✅ Có | 🔗 Xem trạng thái trên màn hình Lịch hẹn / Khách hàng | ❌ Không | ❌ Không |
| **6. Quản trị Nội dung (`Pages`, `Posts`, `Media`, `Testimonials`)** | | | | | |
| Biên tập Trang tĩnh, Bài viết, Thư viện ảnh (`edit_pages`, `edit_posts`, `upload_files`) | ✅ Có | ✅ Có | ❌ Không | ✅ Có | ❌ Không |
| **7. Quản trị Hệ thống & Tài khoản Nhân sự** | | | | | |
| Quản lý danh sách người dùng (`list_users`, `edit_users`) | ✅ Toàn quyền | ⚠️ Chỉ với tài khoản Khách hàng & Nhân sự cấp dưới (`map_meta_cap` chặn Quản trị) | ⚠️ Chỉ được sửa tài khoản Khách hàng (`customer`), cấm xóa user & cấm sửa nhân sự | ❌ Không | ❌ Không |
| Tạo / chỉnh sửa / xóa hoặc gán quyền Quản trị (`administrator`) | ✅ Có | ❌ Chặn cứng (`map_meta_cap` & `editable_roles`) | ❌ Không | ❌ Không | ❌ Không |
| Công cụ Xuất / Xóa dữ liệu cá nhân (`export_others_personal_data`, `erase_others_personal_data`) | ✅ Có | ❌ Không | ❌ Không | ❌ Không | ❌ Không |
| Cài đặt / gỡ Plugin, Theme, Cập nhật Core (`install_plugins`, `manage_options`) | ✅ Có | ❌ Không | ❌ Không | ❌ Không | ❌ Không |

---

## 3. Tài Khoản Nhân Sự Khởi Tạo (`STAFF ACCOUNTS: UNKNOWN`)

Do trường `STAFF ACCOUNTS` trong `PROJECT CONTEXT CARD` hiện đang ở trạng thái **`UNKNOWN`** (chờ chủ sở hữu cung cấp họ tên và địa chỉ email thực tế của từng nhân sự), hệ thống khởi tạo sẵn các tài khoản nhân sự giữ chỗ (`Placeholder Staff Accounts`) với mật khẩu ngẫu nhiên 32 ký tự độ mạnh cao và kích hoạt sẵn **Two Factor (`Two_Factor_Email`)**:

| Vai trò hiển thị (Tiếng Việt) | Role Slug | Tên đăng nhập (`user_login`) | Email giữ chỗ (`user_email`) | Trạng thái 2FA | Ghi chú bàn giao |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Quản trị** | `administrator` | `jannie_admin` | `admin@jannienguyen.local` | ✅ Đã bật (`Two_Factor_Email` + hỗ trợ `Two_Factor_Totp`, `Two_Factor_Backup_Codes`) | Tài khoản Chủ sở hữu / Kỹ sư phát triển |
| **Quản lý cửa hàng** | `shop_manager` | `quan_ly_cua_hang` | `quanly@jannienguyen.vn` | ✅ Đã bật (`Two_Factor_Email`) | Thay email thực tế trước khi Go-Live |
| **Trợ lý lịch hẹn** | `jn_booking_assistant` | `tro_ly_lich_hen` | `troly@jannienguyen.vn` | ✅ Đã bật (`Two_Factor_Email`) | Thay email thực tế trước khi Go-Live |
| **Biên tập nội dung** | `editor` | `bien_tap_noi_dung` | `bientap@jannienguyen.vn` | ✅ Đã bật (`Two_Factor_Email`) | Thay email thực tế trước khi Go-Live |

> **Lưu ý bảo mật:** Không có bất kỳ mật khẩu tĩnh nào được lưu trong mã nguồn hay tài liệu. Khi bàn giao cho nhân sự thật, Quản trị viên cập nhật địa chỉ email chính thức của nhân sự và gửi liên kết đặt mật khẩu mới kèm hướng dẫn thiết lập ứng dụng xác thực **TOTP (Google Authenticator / Authy / 1Password)** + lưu trữ **Backup Codes**.

---

## 4. Chính Sách Xác Thực 2 Lớp (Two-Factor Authentication — 2FA)

1. **Plugin sử dụng:** `Two Factor` chính thức từ WordPress.org (`two-factor` `v0.17.0`), nằm trong danh sách plugin được phê duyệt tại `.agent/rules/jn-project.md` §2.
2. **Phạm vi bắt buộc:** Áp dụng tự động cho toàn bộ 4 vai trò nhân sự (`administrator`, `shop_manager`, `jn_booking_assistant`, `editor`) thông qua các bộ lọc:
   - `two_factor_providers`: Chỉ giữ lại 3 phương thức xác thực thực tế (`Two_Factor_Totp`, `Two_Factor_Email`, `Two_Factor_Backup_Codes`); **vô hiệu hóa hoàn toàn `Two_Factor_Dummy`** kể cả khi `WP_DEBUG = true`.
   - `two_factor_enabled_providers_for_user`: Đảm bảo mọi tài khoản thuộc 4 vai trò nhân sự luôn có ít nhất `Two_Factor_Email` được kích hoạt làm phương thức dự phòng ngay từ lần đăng nhập đầu tiên, đồng thời cho phép nhân sự bật thêm `Two_Factor_Totp` và `Two_Factor_Backup_Codes` trong trang Hồ sơ cá nhân.
   - `two_factor_is_required_for_user`: Trả về `true` cho mọi tài khoản nhân sự.
3. **Luồng đăng nhập nhân sự:**
   - Nhân sự truy cập đường dẫn đăng nhập ẩn `/phong-lam-viec-7429`.
   - Sau khi nhập đúng tên đăng nhập và mật khẩu, hệ thống chặn phiên đăng nhập hoàn tất, hiển thị màn hình yêu cầu mã xác thực 2 lớp (`Mã xác thực`) và gửi mã OTP 8 chữ số qua email (hoặc nhận mã TOTP 6 chữ số từ ứng dụng).
   - Chỉ khi xác thực 2FA thành công, cookie phiên đăng nhập `wp-admin` mới được cấp phát.
