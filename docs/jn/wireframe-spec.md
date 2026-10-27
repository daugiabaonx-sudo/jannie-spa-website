# Đặc Tả Kỹ Thuật Wireframe & Kiến Trúc Chuyển Đổi WordPress (`wireframe-spec.md`)

- **Nguồn thiết kế chuẩn (`Design Truth`)**:
  - `./stitch_jannie_nguyen_website_wireframe/code.html` (604 dòng, Tailwind Play CDN + Vanilla JS)
  - `./stitch_jannie_nguyen_website_wireframe/DESIGN.md` (240 dòng, Editorial Wireframe & Academy Studio)
  - `./stitch_jannie_nguyen_website_wireframe/screen.png` (Ảnh chụp giao diện đã duyệt)
- **Quy tắc áp dụng**: `.agent/rules/jn-project.md` (JN Project Rules §1–§7 & Project Context Card).

---

## 2a. Bản Đồ Trang (`Page Map`) Từ Thanh Điều Hướng & Footer

Dưới đây là toàn bộ các trang được tham chiếu qua thuộc tính `data-path` trên thanh điều hướng chính (`<header>`) và `<footer>` trong `code.html`. **Lưu ý quan trọng**: Hiện tại **chỉ duy nhất trang `Coaching` (`data-path="coaching"`) có wireframe hoàn chỉnh** trong `code.html` / `screen.png`; tất cả các trang còn lại chưa có wireframe riêng và sẽ tái sử dụng hệ thống Design Tokens, Header/Footer và các mẫu Pattern/Block từ trang Coaching.

| STT | `data-path` / Định danh | Nhãn hiển thị trên Header (`code.html`) | Nhãn hiển thị trên Footer (`code.html`) | Đường dẫn (Slug) dự kiến | Loại trang WordPress / WooCommerce | Tình trạng Wireframe |
|---:|:---|:---|:---|:---|:---|:---|
| 1 | `home` | `Home` | *(Logo `JN` / `JANNIE NGUYEN`)* | `/` | Trang chủ tĩnh (`Front Page` — Gutenberg Core Blocks) | **Chưa có wireframe** *(dùng chung Design System)* |
| 2 | `my-story` | `My Story` | `My Story` | `/my-story/` | Trang nội dung (`Page` — Gutenberg Core Blocks) | **Chưa có wireframe** |
| 3 | `coaching` | `Coaching` *(active: `aria-current="page"`)* | `Coaching & Mentorship` | `/coaching/` | Trang dịch vụ trụ cột (`Page` — Gutenberg Patterns + `jn/booking-form`) | **CÓ WIREFRAME ĐẦY ĐỦ (`code.html`, `screen.png`)** |
| 4 | `course` | `Course` | `Chương Trình Học` | `/course/` | Trang danh mục khóa học (`Page` / Archive — Gutenberg Core Blocks) | **Chưa có wireframe** (`COURSES: UNKNOWN`) |
| 5 | `client-success-story` | `Client Success Story` | `Client Success Story` | `/client-success-story/` | Trang tổng hợp câu chuyện thành công (`Page` + CPT `jn_testimonial`) | **Chưa có wireframe** |
| 6 | `my-favorite-product` | `My Favorite Product` | `Sản Phẩm Yêu Thích` | `/my-favorite-product/` | Trang cửa hàng WooCommerce (`Shop Page` / WooCommerce Blocks) | **Chưa có wireframe** (`MY FAVORITE PRODUCT: none yet`) |
| 7 | `booking` | `Booking` | *(Liên kết tới `#booking-form`)* | `/booking/` | Trang đặt lịch trực tiếp (`Page` nhúng block `jn/booking-form`) | **Chưa có wireframe riêng** *(tái sử dụng Section 6 của Coaching)* |
| 8 | `login` | `Đăng nhập` | — | `/tai-khoan/` | Trang Tài khoản khách hàng **WooCommerce My Account** (`[woocommerce_my_account]` / My Account Block; **không** lộ `/wp-login.php`) | **Chưa có wireframe** *(mapping sang WooCommerce My Account)* |
| 9 | `register` | `Đăng ký` | — | `/tai-khoan/#register` | Khối đăng ký trên trang **WooCommerce My Account** | **Chưa có wireframe** *(mapping sang WooCommerce My Account)* |
| 10 | *(footer-privacy)* | — | `Chính sách bảo mật` | `/chinh-sach-bao-mat/` | Trang pháp lý (`Privacy Policy Page`) | **Chưa có wireframe** |
| 11 | *(footer-terms)* | — | `Điều khoản dịch vụ` | `/dieu-khoan-dich-vu/` | Trang pháp lý (`Terms & Conditions Page`) | **Chưa có wireframe** |

---

## 2b. Hệ Thống Design Tokens, Hiệu Chỉnh §4 & Toàn Bộ Xung Đột Giữa `DESIGN.md` và `code.html`/`screen.png`

### 2b.1. Bảng Màu (`Color Roles`) & Hiệu Chỉnh Độ Tương Phản WCAG AA (§4)

| Token Name | Mã Hex gốc (`DESIGN.md` & `code.html`) | Vai trò trong giao diện | Kiểm định độ tương phản (WCAG 2.2 AA) & Hiệu chỉnh bắt buộc theo §4 |
|:---|:---|:---|:---|
| `surface` / `background` / `surface-bright` | `#fbf9f6` | Nền tổng thể của `<body>` và `<main>` | Đạt chuẩn khi đi với chữ `#1F1E1D` (`16.5:1`) và `#716A61` (`5.26:1`). |
| `surface-cream` | `#FAF8F5` | Nền Header (`bg-surface-cream/90`), Section 4, huy hiệu thông số Hero, ô input (`bg-surface-cream/40`) | Đạt chuẩn. |
| `surface-beige` | `#F3EFEA` | Nền Section 2, Section 6, Footer, badge gói, nút phụ | Đạt chuẩn. |
| `surface-pure` / `surface-container-lowest` | `#FFFFFF` | Nền các thẻ nổi (Pillar cards, Tier 1 & 3, Testimonial cards, Form container) | Đạt chuẩn. |
| `ink-charcoal` / `primary-container` | `#1F1E1D` | Chữ tiêu đề chính, nền Tier 2 (Featured), nền cột trái Booking Form, nền nút chính | Đạt chuẩn (`16.2:1` đến `17.3:1` trên nền sáng). |
| `primary` | `#050504` | Nền nút avatar tròn góc phải Header (`w-8 h-8 rounded-full bg-primary`) | Đạt chuẩn. |
| `on-surface` / `on-background` | `#1b1c1a` | Màu chữ mặc định trên `<body>` | Đạt chuẩn. |
| `on-surface-variant` | `#4a4640` | Màu chữ liên kết menu điều hướng ở trạng thái nghỉ | Đạt chuẩn (`8.8:1` trên `#FAF8F5`). |
| `champagne-accent` | `#AC8E76` | Điểm nhấn thương hiệu (chữ nghiêng H1/H2, eyebrow, icon, viền active nav, nền nút Tier 2) | **VI PHẠM WCAG AA khi làm chữ trên nền sáng** (`2.66:1` trên `#F3EFEA`, `2.90:1` trên `#FAF8F5`, `3.05:1` trên `#FFFFFF`) và **khi đặt chữ sáng `#FAF8F5` trên nền `#AC8E76`** (`2.88:1`).<br>**Hiệu chỉnh §4 bắt buộc**:<br>• Dùng **`#7E6754`** cho chữ nhấn cỡ nhỏ (`< 24px`) trên nền sáng (đạt `4.64:1–5.31:1`).<br>• Dùng **`#A0846E`** cho chữ nhấn cỡ lớn (`>= 24px` như chữ nghiêng trong H1/H2, đạt `3.05:1–3.49:1`).<br>• Giữ **`#AC8E76`** cho đường kẻ trang trí, icon trên nền tối `#1F1E1D` (`5.46:1`), chữ trên nền `#1F1E1D` (`5.46:1`), và nền nút/badge với **chữ màu tối `#1F1E1D`** (`5.46:1`). Tuyệt đối không đặt chữ sáng trên nền `#AC8E76`. |
| `muted-stone` | `#7A7268` | Chữ mô tả phụ, nhãn metadata, chú thích | **VI PHẠM WCAG AA trên nền `#F3EFEA` (`4.14:1`) và `#FAF8F5` (`4.47:1`)**.<br>**Hiệu chỉnh §4 bắt buộc**: Thay bằng **`#716A61`** cho toàn bộ chữ muted trên nền sáng (đạt `4.66:1–5.33:1`). |
| `wireframe-border` | `#E5DFD7` | Đường kẻ chia cột (`border-l`, `h-px`), viền mảnh logo/button | Dùng cho đường phân cách trang trí. |
| `wireframe-guide` | `#D1C7BD` | Chữ phụ trên nền tối `#1F1E1D` (Hero overlay `p`, cột trái Booking Form) | Đạt `10.1:1` trên nền `#1F1E1D`. |
| `error` / `on-error-container` | `#ba1a1a` / `#93000a` | Trạng thái lỗi biểu mẫu | Đạt chuẩn. |

*(Các token phụ khác có trong `DESIGN.md` & `tailwind.config` của `code.html`: `surface-dim: #dbdad7`, `surface-container-low: #f5f3f0`, `surface-container: #efeeeb`, `surface-container-high: #eae8e5`, `surface-container-highest: #e4e2df`, `surface-variant: #e4e2df`, `inverse-surface: #30312f`, `inverse-on-surface: #f2f0ed`, `outline: #7b766f`, `outline-variant: #ccc6bd`, `surface-tint: #605e5c`, `on-primary: #ffffff`, `on-primary-container: #888584`, `inverse-primary: #cac6c4`, `secondary: #735944`, `on-secondary: #ffffff`, `secondary-container: #fcd9be`, `on-secondary-container: #775d48`, `tertiary: #070401`, `on-tertiary: #ffffff`, `tertiary-container: #221d16`, `on-tertiary-container: #8d857a`, `on-error: #ffffff`, `error-container: #ffdad6`, `primary-fixed: #e6e2df`, `primary-fixed-dim: #cac6c4`, `on-primary-fixed: #1c1b1a`, `on-primary-fixed-variant: #484645`, `secondary-fixed: #ffdcc1`, `secondary-fixed-dim: #e2c0a6`, `on-secondary-fixed: #291807`, `on-secondary-fixed-variant: #59422e`, `tertiary-fixed: #ece1d5`, `tertiary-fixed-dim: #cfc5b9`, `on-tertiary-fixed: #201b13`, `on-tertiary-fixed-variant: #4c463d`.)*

### 2b.2. Thang Chữ (`Typography Scale` — Kèm Kích Thước Mobile & Hiệu Chỉnh Font §4)

- **Phát hiện lỗi nghiêm trọng ở font tiêu đề gốc (`Bodoni Moda`)**: Font `Bodoni Moda` trên Google Fonts có dải `latin-ext` dừng ở `U+024F` và hoàn toàn **không có các ký tự tiếng Việt thuộc khối Latin Extended Additional (`U+1EA0–U+1EF9`: ă, â, đ, ê, ô, ơ, ư và các dấu thanh tổ hợp ồ, ợ, ế, ậ, ữ, ẫ...)**. Khi hiển thị tiêu đề tiếng Việt, trình duyệt rơi về font serif hệ thống (Times/Times New Roman) cho từng chữ cái có dấu, làm chữ nhảy cao độ và lệch nét đậm nhạt ngay trong cùng một từ.
- **Hiệu chỉnh §4**:
  - Font tiêu đề (`HEADING_FONT`): Mặc định trong Project Context Card là **`Libre Bodoni`** (hoặc lựa chọn giữa `Libre Bodoni`, `Playfair Display`, `Noto Serif Display` sau khi chủ dự án duyệt ảnh chụp `docs/jn/font-specimen.html`).
  - Font nội dung (`BODY_FONT`): **`Geist`** (hỗ trợ đầy đủ tiếng Việt).
  - Cả hai font bắt buộc **tự host dạng WOFF2** (gộp dải `latin` + `vietnamese` vào 1 file mỗi kiểu chữ, tổng cộng `<= 3` file font mỗi trang nội dung, dùng `font-display: swap` kèm fallback khớp metric).

| Token | Font Family (`DESIGN.md` / `code.html` $\rightarrow$ Hiệu chỉnh §4) | Font Size | Line Height | Font Weight | Letter Spacing | Nơi sử dụng trong `code.html` |
|:---|:---|:---|:---|:---|:---|:---|
| `display-lg` | `Bodoni Moda` $\rightarrow$ `HEADING_FONT` | `56px` (`3.5rem`) | `64px` (`1.143`) | `400` | `-0.02em` | H1 Hero trên Desktop (`lg:text-display-lg`), watermark `1:1` (`220px`), chữ nghiêng `Chuyên Sâu`, `Vẻ Đẹp Đích Thực` |
| `display-lg-mobile` | `Bodoni Moda` $\rightarrow$ `HEADING_FONT` | `38px` (`2.375rem`) | `44px` (`1.158`) | `400` | `-0.01em` | H1 Hero trên Mobile (trong `code.html` đang dùng `text-headline-lg lg:text-display-lg` tức `40px` $\rightarrow$ `56px`) |
| `headline-lg` | `Bodoni Moda` $\rightarrow$ `HEADING_FONT` | `40px` (`2.5rem`) | `48px` (`1.2`) | `400` | `-0.015em` | H2 Section 3 (`Chi Tiết Gói Đào Tạo & Cố Vấn`), Section 4 (`Hơn 20 Năm...`), Section 6 (`Đăng Ký Tư Vấn...`) |
| `headline-lg-mobile` | `Bodoni Moda` $\rightarrow$ `HEADING_FONT` | `28px` (`1.75rem`) | `36px` (`1.286`) | `400` | `-0.01em` | H2 trên màn hình nhỏ (`< 768px`) |
| `headline-md` | `Bodoni Moda` $\rightarrow$ `HEADING_FONT` | `30px` (`1.875rem`) | `38px` (`1.267`) | `500` | `-0.01em` | H2 Section 2 (`Lợi Ích...`), Section 5 (`Cảm Nhận...`), con số thống kê Hero (`20+`, `1:1`, `98%`) |
| `headline-sm` | `Bodoni Moda` $\rightarrow$ `HEADING_FONT` | `22px` (`1.375rem`) | `28px` (`1.273`) | `500` | `0em` | H3 trong 3 thẻ Pillar & 3 thẻ Tier (`font-normal` override = `400`), tên thương hiệu `JANNIE NGUYEN` |
| `title-md` | `Geist` | `18px` (`1.125rem`) | `26px` (`1.444`) | `600` | `-0.01em` | Chữ `JN` trong logo Header & tên `JANNIE NGUYEN` |
| `title-sm` | `Geist` | `15px` (`0.9375rem`) | `22px` (`1.467`) | `600` | `0.01em` | H4 (`Nghệ Nhân Bàn Tay Vàng`, `Khái Niệm 'The Balance'`, tên 3 học viên), H5 (3 bước tiếp nhận), số `+180% Doanh Thu`, Hotline |
| `body-lg` | `Geist` | `16px` (`1rem`) | `26px` (`1.625`) | `400` (`font-light` = `300` ở Hero) | `0em` | Đoạn mở đầu dưới H1 Hero |
| `body-md` | `Geist` | `14px` (`0.875rem`) | `22px` (`1.571`) | `400` | `0em` | Nội dung mô tả trong các Pillar, Section 4, lời chứng thực học viên, ô input/select/textarea |
| `body-sm` | `Geist` | `13px` (`0.8125rem`) | `20px` (`1.538`) | `400` | `0.005em` | Mô tả gói Tier, danh sách gạch đầu dòng trong Tier, trích dẫn ảnh, mô tả bước tiếp nhận, liên kết Footer |
| `label-md` | `Geist` | `12px` (`0.75rem`) | `16px` (`1.333`) | `500` | `0.06em` | Liên kết menu điều hướng Header, chữ trên toàn bộ các nút bấm CTA, tiêu đề cột Footer, thời lượng gói |
| `label-sm` | `Geist` | `10px` (`0.625rem`) | `14px` (`1.4`) | `600` | `0.1em` | Eyebrow đầu mục, nhãn `BEAUTY BLOG`, nhãn thông số Hero, chip loại gói, nhãn `<label>` của form, link pháp lý Footer |

### 2b.3. Khoảng Cách (`Spacing`), Lưới (`Grid`), Bo Góc (`Radius`) & Độ Nổi (`Elevation`)

- **Spacing Tokens** (khớp 100% giữa `DESIGN.md` và `code.html`):
  - `space-xs`: `0.25rem` (`4px`)
  - `space-sm`: `0.5rem` (`8px`)
  - `space-md`: `1rem` (`16px`)
  - `space-lg`: `1.5rem` (`24px`)
  - `space-xl`: `2.5rem` (`40px`)
  - `space-2xl`: `4rem` (`64px`)
  - `space-3xl`: `6rem` (`96px`)
  - `gutter`: `1.5rem` (`24px`) | `gutter-mobile`: `1rem` (`16px`)
  - `margin`: `3.5rem` (`56px`) | `margin-mobile`: `1.25rem` (`20px`)
- **Khung Lưới (`Container & Grid`)**:
  - Chiều rộng tối đa khung nội dung: `max-w-[1360px] mx-auto` (với `px-margin` = `56px` mỗi bên trên Desktop, vùng nội dung thực tế rộng `1248px`).
  - Chiều cao Header cố định: `h-[100px]` (`main` có `pt-[100px]`).

### 2b.4. Bảng Đối Chiếu Toàn Bộ Xung Đột Giữa `DESIGN.md` và `code.html`/`screen.png`

Theo **Quy tắc §4**: *"Where DESIGN.md prose and code.html/screen.png disagree, follow code.html/screen.png, except for a visible 2 px focus ring, which DESIGN.md requires."*

| # | Hạng mục xung đột | Đặc tả trong `DESIGN.md` | Thực tế trong `code.html` & `screen.png` | Quyết định áp dụng khi dựng WordPress (§4) |
|---:|:---|:---|:---|:---|
| **1** | **Thang bo góc (`borderRadius`)** | `sm: 0.25rem` (4px), `DEFAULT: 0.5rem` (8px), `md: 0.75rem` (12px), `lg: 1rem` (16px), `xl: 1.5rem` (24px), `full: 9999px` (`#L138-L144`, `#L210-L216`). | `tailwind.config` (`#L4`) định nghĩa: `DEFAULT: 0.25rem` (4px), `lg: 0.5rem` (8px), `xl: 0.75rem` (12px), `full: 9999px` (không có `sm`, `md`). Mọi thẻ `.rounded-xl` thực tế bo góc `12px` thay vì `24px`, và `.rounded-lg` bo `8px` thay vì `16px`. | **Theo `code.html`/`screen.png`**: `rounded = 4px`, `rounded-lg = 8px`, `rounded-xl = 12px`, `rounded-full = 9999px`. |
| **2** | **Viền thẻ (`Card Border`) vs. Đổ bóng (`Shadow`)** | Mọi thẻ Level 1 (Course/Service Cards, Panels) có viền cứng `1px solid #E5DFD7` (`wireframe-border`) và không bóng hoặc bóng mờ `0 2px 12px -2px rgba(31,30,29,0.04)` (`#L205, #L233`). | Các thẻ Pillar, Tier 1–3, Testimonial, Quote badge, Booking slate **không có viền `1px`** mà chỉ dùng `box-shadow` (`shadow-[0_2px_12px_-2px_rgba(31,30,29,0.04)]`, `shadow-[0_2px_12px_-2px_rgba(31,30,29,0.03)]`, `shadow-xl`, `shadow-2xl`). Chỉ logo `JN`, nút `Đăng ký` và vạch chia cột mới có `border-wireframe-border`. | **Theo `code.html`/`screen.png`**: Thẻ nổi không viền, phân tách bằng tương phản bề mặt (`#FFFFFF` trên `#F3EFEA` / `#FAF8F5`) và bóng đổ nhẹ. |
| **3** | **Trạng thái Focus & Nền của Ô nhập liệu (`Form Inputs`)** | Nền `#FFFFFF`, viền `1px solid #E5DFD7`, bo góc `8px`; khi focus hiển thị vòng viền rõ (`1px ring in #1F1E1D or #AC8E76`, `#L229`). | Ô input/select/textarea dùng nền `#FAF8F5` độ mờ 40% (`bg-surface-cream/40`), không có viền ở trạng thái nghỉ, và dùng `focus:bg-surface-pure focus:outline-none` (**mất hoàn toàn vòng focus khi điều hướng bàn phím**). | **Kết hợp theo §4**: Giữ nền nghỉ `bg-surface-cream/40` như `code.html`, nhưng **bắt buộc hiển thị vòng focus `2px solid #1F1E1D` (hoặc `#7E6754`) khi `:focus-visible`**. |
| **4** | **Kiểu dáng Nút bấm (`Buttons`)** | Nút chính bo góc `8px` (hoặc pill cho nút đặt lịch); nút phụ viền `1px solid #1F1E1D` nền trong suốt (`#L221-L222`). | Tất cả các nút CTA và nút chọn gói trong `code.html` đều là hình viên thuốc `rounded-full` (`9999px`), cao `48px` (`h-12`); nút phụ dùng nền đặc `#F3EFEA` (`bg-surface-beige`) không viền thay vì viền mảnh trong suốt. | **Theo `code.html`/`screen.png`**: Dùng `rounded-full` (`h-12`) và nền đặc `#F3EFEA` cho nút phụ. |
| **5** | **Khung ảnh (`Media Containers`)** | Khung skeleton màu `#F3EFEA` có viền `1px #E5DFD7` và đường chéo chữ thập (`#L207, #L233, #L240`). | Hiển thị ảnh chụp tràn viền (`w-full h-full object-cover`) bo góc `rounded-xl` (`12px`) hoặc `rounded-full` (avatar), kèm lớp phủ kính mờ (`backdrop-blur`) trên ảnh Hero. | **Theo `code.html`/`screen.png`**. |
| **6** | **Trọng lượng chữ (`font-weight`) trên thẻ H3** | Token `headline-sm` định nghĩa `fontWeight: '500'` (`#L90-L95`). | Trong `code.html`, tất cả các thẻ `<h3 class="font-headline-sm text-headline-sm ... font-normal">` đều ghi đè lớp `font-normal` (`400`). | **Theo `code.html`/`screen.png`**: Dùng `font-weight: 400` cho H3. |
| **7** | **Lề ngang (`px-margin`) trên thiết bị di động** | Quy định Desktop margin `3.5rem` (`56px`), Tablet `32px`, Mobile `1.25rem` (`20px`, token `margin-mobile`, `#L148-L149, #L192-L194`). | `code.html` gắn cứng `px-margin` (`56px`) ở mọi kích thước màn hình, không gắn `px-margin-mobile` ở `< 768px`. | **Áp dụng responsive theo `DESIGN.md` `#L192-L194`**: Mobile (`<768px`) dùng `20px` (`1.25rem`), Tablet (`768px–1199px`) dùng `32px` (`2rem`), Desktop (`>=1200px`) dùng `56px` (`3.5rem`). |

---

## 2c. Đặc Tả Chi Tiết Từng Phân Đoạn (`Header`, `6 Sections`, `Footer`) & Toàn Bộ Chuỗi Văn Bản Nguyên Văn (`Verbatim`)

> **Cam kết Độ chính xác Văn bản**: Mọi chuỗi văn bản hiển thị trong `code.html` (tổng cộng **207 nút văn bản hiển thị**, bao gồm **157 nút chữ** và **50 nút icon thuộc 19 icon duy nhất**, cộng thêm **4 chuỗi `placeholder`** và **1 chuỗi trạng thái JS**) đều được trích xuất nguyên văn 100% dưới đây.

### 0. Header Cố Định (`<header>` — Dòng 4)
- **Bố cục & Kích thước**:
  - Thanh cố định đỉnh trang: `fixed top-0 inset-x-0 z-50 bg-surface-cream/90 backdrop-blur-md shadow-[0_1px_8px_rgba(0,0,0,0.03)]`.
  - Khung trong: `h-[100px] max-w-[1360px] mx-auto px-margin flex items-center justify-between gap-gutter`.
  - Chia làm 3 cụm: Cụm thương hiệu bên trái (`shrink-0`), Thanh điều hướng giữa (`hidden xl:flex items-center gap-space-lg`), Cụm tài khoản bên phải (`shrink-0`).
- **Chuỗi văn bản nguyên văn (`Verbatim`)**:
  - Logo tròn (`w-11 h-11 rounded-full bg-surface-beige border border-wireframe-border`): `JN`
  - Tên thương hiệu: `JANNIE NGUYEN`
  - Phụ đề thương hiệu: `BEAUTY BLOG`
  - 7 mục điều hướng chính (`<nav>`):
    1. `Home` (`data-path="home"`, `href="#"`)
    2. `My Story` (`data-path="my-story"`, `href="#"`)
    3. `Coaching` (`data-path="coaching"`, `href="#"`, `aria-current="page"`, trạng thái active: `text-ink-charcoal border-b-2 border-champagne-accent pb-1 font-semibold`)
    4. `Course` (`data-path="course"`, `href="#"`)
    5. `Client Success Story` (`data-path="client-success-story"`, `href="#"`)
    6. `My Favorite Product` (`data-path="my-favorite-product"`, `href="#"`)
    7. `Booking` (`data-path="booking"`, `href="#"`)
  - Cụm nút tài khoản (`hidden sm:flex`):
    - Liên kết 1: `Đăng nhập` (`data-path="login"`, `href="#"`)
    - Liên kết 2 (nút pill): `Đăng ký` (`data-path="register"`, `href="#"`)
    - Nút icon tròn (`w-8 h-8 rounded-full bg-primary`): icon `person`

---

### Section 1: Architectural Hero (`SECTION 1: ARCHITECTURAL HERO` — Dòng 5–72)
- **Bố cục & Tỷ lệ lưới (`Grid Ratios`)**:
  - Khung: `relative w-full max-w-[1360px] mx-auto px-margin pt-space-xl pb-space-3xl overflow-hidden`.
  - Lưới: `grid grid-cols-1 lg:grid-cols-12 gap-gutter items-start`.
    - `< 1024px` (Mobile/Tablet): 1 cột xếp chồng (`grid-cols-1`), cột ảnh cách trên `mt-space-xl` (`40px`).
    - `>= 1024px` (Desktop `lg:`): Tỷ lệ **8 : 4** (`lg:col-span-8` cho nội dung chữ bên trái, `lg:col-span-4` cho khung ảnh chân dung bên phải).
  - Lớp chữ chìm trang trí góc phải trên (`absolute -right-12 top-0 opacity-10 pointer-events-none select-none`): `1:1` (`220px`, `font-display-lg`).
- **Chuỗi văn bản nguyên văn (`Verbatim`)**:
  - Watermark nền: `1:1`
  - Eyebrow (chấm tròn `bg-champagne-accent` + chữ): `Private Mentorship & Advisory Program`
  - Tiêu đề chính `<h1>`: `Coaching & Đồng Hành` + thẻ `<span class="italic font-display-lg text-champagne-accent">`: `Chuyên Sâu` (Ghép đầy đủ: `Coaching & Đồng Hành Chuyên Sâu`)
  - Đoạn mô tả `<p>`: `Chương trình cố vấn 1:1 độc quyền cùng Chuyên gia Jannie Nguyễn dành cho cá nhân định hình vẻ đẹp tự nhiên và các chủ cơ sở thẩm mỹ, phun xăm, spa cao cấp.`
  - Thanh 3 chỉ số (`grid grid-cols-3 gap-space-md max-w-xl p-space-md rounded-xl bg-surface-cream`):
    - Cột 1: `20+` | `Năm Thực Chiến`
    - Cột 2 (`border-l border-wireframe-border pl-space-md`): `1:1` | `Cố Vấn Độc Bản`
    - Cột 3 (`border-l border-wireframe-border pl-space-md`): `98%` | `Tỷ Lệ Tối Ưu Hóa`
  - Cụm 2 nút hành động (`flex flex-wrap items-center gap-space-md mt-space-xl`):
    - Nút chính (`href="#booking-form"`, nền `#1F1E1D` $\rightarrow$ hover `#AC8E76` với chữ `#1F1E1D` theo §4): `Đặt Lịch Coaching Ngay` + icon `north_east`
    - Nút phụ (`href="#coaching-tiers"`, nền `#F3EFEA`): `Xem Chi Tiết 3 Gói Cố Vấn` + icon `south`
  - Lớp phủ trên khung ảnh phải (`aspect-[4/5] rounded-xl overflow-hidden shadow-xl`):
    - Nhãn góc trên trái (`bg-ink-charcoal/60 backdrop-blur-sm`): `PRO-COACHING // SPEC.24`
    - Icon góc trên phải: `all_inclusive`
    - Hộp kính mờ đáy ảnh (`bg-ink-charcoal/80 backdrop-blur-md rounded-lg`):
      - Tiêu đề hộp: `Triết Lý Cân Bằng Toàn Diện`
      - Nội dung hộp: `Kết hợp tinh hoa kỹ thuật thẩm mỹ và năng lực định vị nhân hiệu bền vững.`
- **Tài nguyên ảnh (Image #1)**:
  - `src`: `https://lh3.googleusercontent.com/aida-public/AB6AXuCouLm-77JEuBzI4EPtLdhj9HP_yllYUS1hOSuNPArJBH7SK8o2d5O557wW_oB-z7q5d0Dxe5zhUXp5jmfAQt71VYznJpRkUKbTnuA0y4z6kBgiUpd-ofw9wgRk-HNhTJHji_zQCRUGrau9Bt7jfvuTeFNpHxwB2aYGbda3xn3FiL18DzDbqCY2xSnmzvEDXRU2VFxGhHyDEz05bT4jY96RrQuWiFRjF_8_W1EMndWFFIv1ZLwFFKQ3nA`
  - `data-alt`: `A refined, editorial monochrome portrait of aesthetic master Jannie Nguyen in a minimalist architectural studio, draped in tailored ecru linen, soft atmospheric daylight highlighting sculptural profile and serene poise, high fashion fine art photography style.`

---

### Section 2: Lợi Ích Của Chương Trình (`SECTION 2: LỢI ÍCH CỦA CHƯƠNG TRÌNH (CORE PILLARS / BENTO ARCHITECTURE)` — Dòng 73–149)
- **Bố cục & Tỷ lệ lưới (`Grid Ratios`)**:
  - Nền toàn chiều rộng: `w-full bg-surface-beige py-space-3xl relative`.
  - Khung trong: `max-w-[1360px] mx-auto px-margin`.
  - Hàng tiêu đề đầu mục: `flex flex-col md:flex-row md:items-end justify-between mb-space-2xl gap-space-md`.
  - Lưới 3 thẻ Pillar: `grid grid-cols-1 md:grid-cols-3 gap-gutter` (Mobile `< 768px`: 1 cột; Tablet & Desktop `>= 768px`: **1 : 1 : 1** — 3 cột bằng nhau).
  - Mỗi thẻ Pillar: `bg-surface-pure rounded-xl p-space-xl flex flex-col justify-between shadow-[0_2px_12px_-2px_rgba(31,30,29,0.03)] hover:translate-y-[-2px] transition-transform duration-200`, có chân thẻ tách màu `bg-surface-cream/50 -mx-space-xl -mb-space-xl px-space-xl pb-space-lg rounded-b-xl`.
- **Chuỗi văn bản nguyên văn (`Verbatim`)**:
  - Eyebrow đầu mục: `Giá Trị Cốt Lõi`
  - Tiêu đề `<h2>`: `Lợi Ích Của Chương Trình Coaching`
  - Mô tả đầu mục `<p>`: `Không đơn thuần là chuyển giao kỹ năng cơ học, chương trình tái định nghĩa toàn bộ mô hình tư duy, kỹ thuật và vận hành thương hiệu.`
  - **Thẻ Pillar 1**:
    - Icon trong vòng tròn `w-12 h-12 rounded-full bg-surface-cream`: `architecture`
    - Eyebrow: `Pillar 01 // Bản Đồ Chiến Lược`
    - Tiêu đề `<h3>`: `Định Hướng Chiến Lược & Thương Hiệu`
    - Nội dung `<p>`: `Tái định vị tay nghề, xây dựng phong cách riêng biệt trong thị trường phun xăm, spa và salon làm đẹp. Kiến tạo nhân hiệu mang bản sắc uy tín độc bản và cuốn hút phân khúc khách hàng cao cấp.`
    - Chân thẻ: `Trọng tâm:` | `Kỹ nghệ tự nhiên & Định vị thương hiệu cá nhân`
  - **Thẻ Pillar 2**:
    - Icon trong vòng tròn: `query_stats`
    - Eyebrow: `Pillar 02 // Vận Hành & Doanh Số`
    - Tiêu đề `<h3>`: `Quản Lý Cơ Sở & Tăng Trưởng Doanh Số`
    - Nội dung `<p>`: `Giải pháp tháo gỡ điểm nghẽn quản lý dịch vụ, chuẩn hóa quy trình chăm sóc thượng khách và xây dựng kịch bản trải nghiệm đẳng cấp giúp gia tăng tỷ lệ khách trung thành và nâng giá trị dịch vụ.`
    - Chân thẻ: `Trọng tâm:` | `Quy trình vận hành chuẩn mực & Tỷ lệ quay lại > 85%`
  - **Thẻ Pillar 3**:
    - Icon trong vòng tròn: `fingerprint`
    - Eyebrow: `Pillar 03 // May Đo Lộ Trình`
    - Tiêu đề `<h3>`: `Cá Nhân Hóa Theo Năng Lực Cốt Lõi`
    - Nội dung `<p>`: `Không rập khuôn theo giáo trình công nghiệp. Từng học viên được đo lường điểm mạnh, góc khuất kỹ thuật và tâm lý kinh doanh để xây dựng bản kế hoạch hành động khả thi và tối ưu năng lực nội tại.`
    - Chân thẻ: `Trọng tâm:` | `May đo 1:1 theo đặc thù mô hình và mục tiêu tài chính`

---

### Section 3: Chi Tiết Các Gói Đào Tạo & Cố Vấn (`SECTION 3: CHI TIẾT CÁC GÓI ĐÀO TẠO & CỐ VẤN (PRICING & TIERS)`, `id="coaching-tiers"` — Dòng 150–296)
- **Bố cục & Tỷ lệ lưới (`Grid Ratios`)**:
  - Khung: `w-full max-w-[1360px] mx-auto px-margin py-space-3xl`, `id="coaching-tiers"`.
  - Đầu mục căn giữa: `text-center max-w-2xl mx-auto mb-space-2xl`.
  - Lưới 3 gói: `grid grid-cols-1 lg:grid-cols-3 gap-gutter items-stretch` (`< 1024px`: 1 cột; `>= 1024px`: **1 : 1 : 1** — 3 cột bằng nhau, trong đó thẻ giữa Tier 2 có nền tối `bg-ink-charcoal text-surface-cream shadow-2xl lg:-translate-y-2`).
- **Tương tác (`Interaction`)**:
  - Khi người dùng nhấp vào nút hành động ở mỗi thẻ Tier, trang cuộn mượt tới `#booking-form` và tự động chọn sẵn gói tương ứng trong `<select id="package-select">` (trong `code.html` dùng `onclick="selectTier('...')"` — khi sang WordPress chuyển thành sự kiện `data-package-value` trong file JS riêng, không dùng inline `onclick`).
- **Chuỗi văn bản nguyên văn (`Verbatim`)**:
  - Eyebrow đầu mục: `Chương Trình Đào Tạo`
  - Tiêu đề `<h2>`: `Chi Tiết Gói Đào Tạo & Cố Vấn`
  - Mô tả đầu mục `<p>`: `Lựa chọn hình thức đồng hành phù hợp với nhu cầu hoàn thiện vẻ đẹp bản thân hoặc bứt phá sự nghiệp làm đẹp của bạn.`
  - **Thẻ Tier 1 (`Tư Vấn Cá Nhân 1:1`)**:
    - Chip loại gói: `Cá Nhân`
    - Nhãn thời lượng: `Thời lượng: 30 Phút`
    - Tiêu đề `<h3>`: `Tư Vấn Cá Nhân 1:1`
    - Phụ đề: `Định hình vẻ đẹp & phong cách riêng`
    - Mô tả: `Dành cho khách hàng mong muốn khám phá đường nét tự nhiên, thấu hiểu làn da và nhận định hướng chăm sóc, thẩm mỹ tinh tế nhất từ Jannie Nguyễn.`
    - Danh sách 4 quyền lợi (mỗi dòng đi kèm icon `check_circle`):
      1. `Phân tích cấu trúc khuôn mặt và làn da`
      2. `Chiến lược làm đẹp tự nhiên, không lạm dụng hóa chất`
      3. `Khuyến nghị sản phẩm và liệu trình phù hợp`
      4. `Giải đáp trực tiếp 1:1 các thắc mắc làm đẹp`
    - Nút CTA (`href="#booking-form"`, tham số `Gói Tư Vấn Cá Nhân 1:1 (30 Phút)`): `Chọn Gói 30 Phút` + icon `arrow_forward`
  - **Thẻ Tier 2 (`Coaching Chuyên Nghiệp 1:1` — Gói Nổi Bật / Featured)**:
    - Huy hiệu nổi đỉnh thẻ (`absolute -top-3 left-1/2 -translate-x-1/2`): `Khuyên Chọn // Chủ Cơ Sở & Học Viên`
    - Chip loại gói: `Chuyên Nghiệp`
    - Nhãn thời lượng: `Thời lượng: 60 Phút`
    - Tiêu đề `<h3>`: `Coaching Chuyên Nghiệp 1:1`
    - Phụ đề: `Tăng tốc kỹ thuật & Chiến lược Spa / Phun xăm`
    - Mô tả: `Dành cho thợ làm nghề, học viên nâng cao và chủ Salon/Spa cần giải quyết trực tiếp vướng mắc kỹ thuật, chốt sale dịch vụ cao cấp và giữ chân khách hàng.`
    - Danh sách 4 quyền lợi (mỗi dòng đi kèm icon `verified`):
      1. `Đánh giá kỹ thuật phun xăm / thẩm mỹ hiện tại`
      2. `Tối ưu hoá quy trình tư vấn và thiết lập bảng giá`
      3. `Chiến lược xây dựng tệp khách VIP trung thành`
      4. `Bản ghi hình buổi coaching & Action Plan chi tiết`
    - Nút CTA (`href="#booking-form"`, tham số `Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)`): `Đăng Ký Gói Chuyên Nghiệp` + icon `arrow_forward`
  - **Thẻ Tier 3 (`Master Mentorship 3 Tháng`)**:
    - Chip loại gói: `Master Class`
    - Nhãn thời lượng: `Lộ trình: 3 Tháng Toàn Diện`
    - Tiêu đề `<h3>`: `Master Mentorship 3 Tháng`
    - Phụ đề: `Đồng hành chuyển giao & Nhân bản thương hiệu`
    - Mô tả: `Chương trình cố vấn dài hạn toàn diện nhất cùng Jannie Nguyễn, trực tiếp tái cấu trúc kỹ thuật chuyên môn, đào tạo đội ngũ và nâng tầm vị thế cơ sở thẩm mỹ.`
    - Danh sách 4 quyền lợi (mỗi dòng đi kèm icon `check_circle`):
      1. `12 buổi coaching chuyên sâu 1:1 trực tiếp/online`
      2. `Kèm cặp kỹ thuật thực chiến trên người thật`
      3. `Cố vấn giải pháp chiến lược truyền thông nhân hiệu`
      4. `Hỗ trợ khẩn cấp 24/7 trong suốt 90 ngày`
    - Nút CTA (`href="#booking-form"`, tham số `Gói Master Mentorship (3 Tháng)`): `Ứng Tuyển Mentorship` + icon `arrow_forward`

---

### Section 4: Giới Thiệu Chuyên Gia & Phương Pháp Triết Lý (`SECTION 4: GIỚI THIỆU CHUYÊN GIA & PHƯƠNG PHÁP TRIẾT LÝ` — Dòng 297–352)
- **Bố cục & Tỷ lệ lưới (`Grid Ratios`)**:
  - Nền toàn chiều rộng: `w-full bg-surface-cream py-space-3xl`.
  - Khung trong: `max-w-[1360px] mx-auto px-margin`.
  - Lưới: `grid grid-cols-1 lg:grid-cols-12 gap-gutter items-center`.
    - `< 1024px`: 1 cột xếp chồng.
    - `>= 1024px` (`lg:`): Tỷ lệ **5 : 7** (`lg:col-span-5` cho cột ảnh `aspect-[3/4]` kèm thẻ trích dẫn nổi `hidden sm:block absolute -bottom-6 -right-6 bg-surface-pure p-space-lg rounded-xl shadow-xl max-w-xs`; `lg:col-span-7 lg:pl-space-xl` cho cột câu chuyện thương hiệu).
- **Chuỗi văn bản nguyên văn (`Verbatim`)**:
  - Hộp trích dẫn nổi trên ảnh:
    - Icon: `format_quote`
    - Câu trích dẫn: `"Làm đẹp với tình yêu và sự cân bằng là con đường duy nhất dẫn đến sự tự tin trường tồn."`
    - Tác giả: `— Jannie Nguyễn`
  - Cột nội dung bên phải:
    - Eyebrow: `Chuyên Gia Cố Vấn`
    - Tiêu đề `<h2>`: `Hơn 20 Năm Đồng Hành Cùng` + `<span class="italic font-display-lg text-champagne-accent">`: `Vẻ Đẹp Đích Thực` (Ghép đầy đủ: `Hơn 20 Năm Đồng Hành Cùng Vẻ Đẹp Đích Thực`)
    - Đoạn văn 1 (`<p>`): `Với chuyên gia Jannie Nguyễn (Nghệ nhân Bàn Tay Vàng ngành phun xăm thẩm mỹ Việt Nam), đào tạo làm đẹp không đơn thuần dừng lại ở việc học thuộc công thức hay thao tác máy móc. Đó là hành trình tìm kiếm sự cân bằng sâu sắc giữa thể chất, tâm trí và cảm xúc thẩm mỹ.`
    - Đoạn văn 2 (`<p>` có 2 cụm `<strong>`): `Triết lý đào tạo thực chiến của Jannie được đúc kết từ hàng ngàn ca điều trị lâm sàng, thấu hiểu tường tận cấu trúc cơ địa và làn da người Việt. Phương pháp kết hợp độc đáo giữa` + `<strong>` `kỹ thuật điêu khắc tự nhiên` `</strong>` + `và` + `<strong>` `năng lượng an yên nội tại` `</strong>` + `giúp mỗi học viên không chỉ sở hữu tay nghề xuất sắc mà còn lan tỏa sự tin cậy tuyệt đối tới khách hàng của mình.`
    - Lưới 2 cột chứng nhận (`grid grid-cols-1 sm:grid-cols-2 gap-space-md`):
      - Mục 1 (icon `award_star`):
        - Tiêu đề (trong `code.html` là `<h4>`, sẽ chuẩn hóa thành `<h3>` ở WordPress để không nhảy cấp): `Nghệ Nhân Bàn Tay Vàng`
        - Mô tả: `Vinh danh tại Oscar for Beauty danh giá ngành thẩm mỹ Việt Nam.`
      - Mục 2 (icon `spa`):
        - Tiêu đề: `Khái Niệm 'The Balance'`
        - Mô tả: `Phương pháp cân bằng tự nhiên đưa tinh thần Việt ra quốc tế.`
- **Tài nguyên ảnh (Image #2)**:
  - `src`: `https://lh3.googleusercontent.com/aida-public/AB6AXuDmkj8tbSeFbInGDM8SNvocmQ8C7g33rgJZp9e29kxZ5xeHnHdKfAw2uYcoiH0WVq53fnedSqadfTHbY0weMel2Y6dS3kHkn93LA19BG4q9MiWh5-c-nl4A3XtDlKcvJvIrxRQ_QI0EqIGK9dhn8_Uxfk3VcoCcF-Pykf-hHBgtP6Lun1r252WkWsqc6ZVK-CerboMJvIeQnmLMP2AP4gm05gczTMxGYbKdO6ytJgH-aR8goGWI-TKD3Q`
  - `data-alt`: `Intimate editorial shot of Jannie Nguyen consulting a client in a warm natural light aesthetic clinic, delicate precision tools neatly organized on marble surface, peaceful zen atmosphere, neutral earth tones with subtle champagne accents.`

---

### Section 5: Cảm Nhận Học Viên & Kết Quả Thực Tế (`SECTION 5: CẢM NHẬN HỌC VIÊN & KẾT QUẢ THỰC TẾ (TESTIMONIALS)` — Dòng 353–453)
- **Bố cục & Tỷ lệ lưới (`Grid Ratios`)**:
  - Khung: `w-full max-w-[1360px] mx-auto px-margin py-space-3xl`.
  - Đầu mục (`flex flex-col md:flex-row md:items-end justify-between mb-space-2xl gap-space-md`): Bên trái là Eyebrow + `<h2>`; bên phải là hộp huy hiệu tăng trưởng `bg-surface-cream px-space-md py-space-sm rounded-lg` chứa nhãn + đồ thị SVG sparkline (`viewBox="0 0 100 30"`).
  - Lưới 3 thẻ cảm nhận: `grid grid-cols-1 md:grid-cols-3 gap-gutter` (`< 768px`: 1 cột; `>= 768px`: **1 : 1 : 1** — 3 cột bằng nhau).
- **Chuỗi văn bản nguyên văn (`Verbatim`)**:
  - Eyebrow đầu mục: `Minh Chứng Thành Công`
  - Tiêu đề `<h2>`: `Cảm Nhận Học Viên & Kết Quả Thực Tế`
  - Huy hiệu Sparkline góc phải:
    - Nhãn trên: `Tăng trưởng TB`
    - Nhãn dưới: `+180% Doanh Thu`
  - **Thẻ Cảm nhận 1**:
    - 5 ngôi sao: `star` × 5 (`font-variation-settings: 'FILL' 1`)
    - Nội dung `<p>`: `"Buổi coaching 60 phút với cô Jannie đã tháo gỡ điểm nghẽn lớn nhất trong kỹ thuật sợi chân mày của em. Khách hàng giờ đây quay lại khen dáng mày tự nhiên và giới thiệu thêm rất nhiều bạn bè."`
    - Tên học viên: `Minh Thảo`
    - Chức danh / Đơn vị: `Chủ Thảo Beauty Academy (Hà Nội)`
    - **Ảnh đại diện (Image #3)**:
      - `src`: `https://lh3.googleusercontent.com/aida-public/AB6AXuBHahVwszajQfRB4hzVkbYqo05w1UMmuQGW-cwANgrzGTeH6J5YVKo6Oo1_NimUsrsl35PhtyVuOA4hiCOe6ATwzOweoTsoF6656dL8v6mOkYMTTUpYDgKw-f41NgQZwKLF4A_5LcU6VKUFQYplW_harPELikoFxfaQ_F5VssIiKsYJVF6agBQusjL2SxYgNtjPdsICNK91XqfLD2EYB1nLCi3XQibeSv8z8zZuJKZRr3ZgNMYxE7GAyw`
      - `data-alt`: `Portrait of a young successful Vietnamese female aesthetician, glowing confident smile, minimalist white medical coat, softly blurred boutique salon backdrop.`
  - **Thẻ Cảm nhận 2**:
    - 5 ngôi sao: `star` × 5
    - Nội dung `<p>`: `"Chương trình Mentorship 3 tháng thực sự thay đổi tư duy kinh doanh của mình. Không chỉ nâng cấp tay nghề cho đội ngũ kỹ thuật viên, doanh số Spa tháng vừa qua đã tăng gấp đôi nhờ chuẩn hóa quy trình chăm sóc."`
    - Tên học viên: `Ngọc Bích`
    - Chức danh / Đơn vị: `Founder, Bích Clinic & Spa (TP.HCM)`
    - **Ảnh đại diện (Image #4)**:
      - `src`: `https://lh3.googleusercontent.com/aida-public/AB6AXuDqXxpT8_3zQ6cbF1HuaUzzAMiMeiWQxsNs7GGsiPpbYIgOoiRfellHYwygJXkOyaaKkfyuaXcv3GhVlvHE3LWQovIXkNsokgdx2eSKfX9cT06XvPhSpOBI4RTqm2RE7Gr1xPq4FZBlKtbZzF4AuMqj6kX89xOXlpXQCvwnxCsuRWKcjP-aW8hWfpSqbFekxtZ1P7LlGgWj4VM808W1JCq_SWH4rdzeq6P-Nh0mKnCu0FGlV3B-UgaCYQ`
      - `data-alt`: `Portrait of an elegant Vietnamese businesswoman in her late 30s, polished chic attire, standing in an upscale modern aesthetic spa lobby.`
  - **Thẻ Cảm nhận 3**:
    - 5 ngôi sao: `star` × 5
    - Nội dung `<p>`: `"Chỉ sau 30 phút tư vấn 1:1, Jannie đã giúp em hiểu được làn da của mình cần sự tối giản chứ không phải cả chục bước dưỡng đắt đỏ. Da em khỏe lên từng ngày và em tự tin hơn bao giờ hết."`
    - Tên học viên: `Phương Uyên`
    - Chức danh / Đơn vị: `Học viên Tư Vấn Cá Nhân`
    - **Ảnh đại diện (Image #5)**:
      - `src`: `https://lh3.googleusercontent.com/aida-public/AB6AXuAu3AamKMzr8NFmmqlnEmyjGjp-hPQIRyF-ch7bgz_YRjNCHSV1b6PJkjsVWUW82Lu7LPjwoHmn2urqsSEYrcYzMvkenPKlVpeBAFq9uoDmFYw49XqZXipaz5rsgWbttTTf94mTjYGap7P5_X_BjQ8BL6AQxjnSddkv4dyauAhWjodNQsuyV2KcmLCGpMu2wXAWnZKWCvziFaXoXon9MxIzdDJ1oWbUSv8d7_hq8HeWkvAPgnYiqZ7g-Q`
      - `data-alt`: `Close-up portrait of a radiant Vietnamese woman with porcelain clean glowing natural skin, soft smile, minimalist organic tone setting.`

---

### Section 6: CTA & Booking Form Section (`SECTION 6: CTA & BOOKING FORM SECTION`, `id="booking-form"` — Dòng 454–573)
- **Bố cục & Tỷ lệ lưới (`Grid Ratios`)**:
  - Nền toàn chiều rộng: `w-full bg-surface-beige py-space-3xl relative`, `id="booking-form"`.
  - Khung thẻ lớn: `bg-surface-pure rounded-xl shadow-xl overflow-hidden grid grid-cols-1 lg:grid-cols-12`.
    - `< 1024px`: 1 cột xếp chồng.
    - `>= 1024px` (`lg:`): Tỷ lệ **5 : 7** (`lg:col-span-5 p-space-xl lg:p-space-2xl bg-ink-charcoal text-surface-cream` cho cột quy trình bên trái; `lg:col-span-7 p-space-xl lg:p-space-2xl bg-surface-pure` cho cột biểu mẫu bên phải).
- **Chuỗi văn bản nguyên văn (`Verbatim`)**:
  - **Cột trái (Quy trình tiếp nhận)**:
    - Eyebrow: `Quy Trình Tiếp Nhận`
    - Tiêu đề `<h2>`: `Đăng Ký Tư Vấn & Xếp Lịch Coaching`
    - Đoạn mô tả `<p>`: `Để bảo đảm chất lượng sâu sát nhất, chuyên gia Jannie Nguyễn chỉ nhận số lượng học viên và khách hàng giới hạn mỗi tháng. Vui lòng cung cấp đầy đủ thông tin để được ưu tiên sắp xếp lịch trình.`
    - 3 bước quy trình (số thứ tự trong vòng tròn `w-6 h-6 rounded-full bg-champagne-accent`):
      - Bước `1`: Tiêu đề `Gửi yêu cầu thông tin` | Mô tả: `Điền mẫu khảo sát nhu cầu cá nhân hoặc cơ sở làm đẹp.`
      - Bước `2`: Tiêu đề `Xác nhận khung giờ` | Mô tả: `Trợ lý học viện sẽ liên hệ trong 24 giờ làm việc để thống nhất lịch hẹn.`
      - Bước `3`: Tiêu đề `Bước vào phiên làm việc 1:1` | Mô tả: `Trực tiếp cùng Jannie Nguyễn qua nền tảng bảo mật hoặc tại viện.`
    - Hộp Hotline đáy cột trái (`bg-surface-cream/5 rounded-lg p-space-md`):
      - Nhãn: `Hotline Hỗ Trợ Khẩn`
      - Nội dung: `contact@jannienguyen.vn • (+84) 908-XXX-XXX`
  - **Cột phải (Biểu mẫu `#coaching-registration-form`)**:
    - Hàng 1 (`grid grid-cols-1 sm:grid-cols-2 gap-space-md`):
      - Trường 1 (`for="full-name"`): Nhãn `Họ và Tên` + dấu `*` | `<input id="full-name" type="text" required placeholder="Nguyễn Văn A">`
      - Trường 2 (`for="phone-number"`): Nhãn `Số Điện Thoại / Zalo` + dấu `*` | `<input id="phone-number" type="tel" required placeholder="090 123 4567">`
    - Hàng 2 (`for="email-address"`):
      - Nhãn `Địa Chỉ Email` + dấu `*` | `<input id="email-address" type="email" required placeholder="name@example.com">`
    - Hàng 3 (`for="package-select"`):
      - Nhãn `Gói Cố Vấn Quan Tâm` + dấu `*` | `<select id="package-select" required>` kèm icon `expand_more` và 3 `<option>`:
        1. `value="Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)"` (`selected`): `Gói Coaching Chuyên Nghiệp 1:1 (60 Phút) — Dành cho thợ & chủ Spa`
        2. `value="Gói Tư Vấn Cá Nhân 1:1 (30 Phút)"`: `Gói Tư Vấn Cá Nhân 1:1 (30 Phút) — Định hình vẻ đẹp riêng`
        3. `value="Gói Master Mentorship (3 Tháng)"`: `Gói Master Mentorship (Lộ trình 3 tháng toàn diện)`
    - Hàng 4 (`grid grid-cols-1 sm:grid-cols-2 gap-space-md`):
      - Trường 5 (`for="coaching-format"`): Nhãn `Hình Thức Gặp` | `<select id="coaching-format">` kèm icon `expand_more` và 2 `<option>`:
        1. `value="online"`: `Online Trực Tuyến (Zoom High-Res)`
        2. `value="in-person"`: `Trực tiếp tại Academy (TP.HCM)`
      - Trường 6 (`for="time-preference"`): Nhãn `Khung Giờ Thuận Tiện` | `<select id="time-preference">` kèm icon `expand_more` và 3 `<option>`:
        1. `value="morning"`: `Buổi sáng (09:00 - 11:30)`
        2. `value="afternoon"`: `Buổi chiều (14:00 - 17:30)`
        3. `value="weekend"`: `Cuối tuần (Thứ 7)`
    - Hàng 5 (`for="notes"`):
      - Nhãn `Mục Tiêu Hoặc Thách Thức Lớn Nhất Của Bạn` | `<textarea id="notes" rows="3" placeholder="Chia sẻ ngắn gọn tình trạng kỹ thuật, lượng khách hiện tại hoặc câu hỏi bạn mong muốn giải quyết nhất cùng Jannie..."></textarea>`
    - Nút gửi (`<button id="submit-btn" type="submit">`):
      - Nhãn: `Gửi Yêu Cầu Xếp Lịch Coaching` + icon `send`
      - Nhãn trạng thái đang xử lý trong JS (`handleFormSubmit()`): `Đang Xử Lý Hồ Sơ...`
    - Hộp thông báo phản hồi (`#form-feedback`):
      - `Cảm ơn bạn!` + `Yêu cầu đã được ghi nhận. Đội ngũ trợ lý của Jannie Nguyễn sẽ liên hệ trực tiếp qua số điện thoại của bạn trong vòng 24 giờ.`

---

### 7. Footer (`<footer>` — Dòng 604)
- **Bố cục & Tỷ lệ lưới (`Grid Ratios`)**:
  - Nền: `w-full bg-surface-beige text-on-surface py-space-3xl mt-space-3xl`.
  - Khung trong: `max-w-[1360px] mx-auto px-margin`.
  - Lưới chính (`grid grid-cols-1 md:grid-cols-12 gap-space-xl pb-space-2xl`):
    - `< 768px`: 1 cột xếp chồng.
    - `>= 768px` (`md:`): Tỷ lệ **6 : 3 : 3** (`md:col-span-6` cho cột Thương hiệu; `md:col-span-3` cho cột Khám Phá; `md:col-span-3` cho cột Liên Hệ & Tư Vấn).
  - Hàng bản quyền đáy (`pt-space-lg flex flex-col md:flex-row items-center justify-between gap-space-md`).
- **Chuỗi văn bản nguyên văn (`Verbatim`)**:
  - Cột 1 (Thương hiệu):
    - Logo tròn (`w-9 h-9 rounded-full bg-surface-cream border border-wireframe-border`): `JN`
    - Tên thương hiệu: `JANNIE NGUYEN`
    - Sứ mệnh (`<p class="italic">`): `Sứ mệnh của Jannie là tạo ra sự cân bằng và khám phá vẻ đẹp đích thực trong mỗi người.`
  - Cột 2 (`Khám Phá`):
    - Tiêu đề cột: `Khám Phá`
    - 5 liên kết:
      1. `My Story` (`data-path="my-story"`)
      2. `Coaching & Mentorship` (`data-path="coaching"`)
      3. `Chương Trình Học` (`data-path="course"`)
      4. `Client Success Story` (`data-path="client-success-story"`)
      5. `Sản Phẩm Yêu Thích` (`data-path="my-favorite-product"`)
  - Cột 3 (`Liên Hệ & Tư Vấn`):
    - Tiêu đề cột: `Liên Hệ & Tư Vấn`
    - Dòng 1: icon `location_on` + `TP. Hồ Chí Minh, Việt Nam`
    - Dòng 2: icon `mail` + `contact@jannienguyen.vn`
    - Dòng 3: icon `schedule` + `Thứ Hai - Thứ Bảy: 09:00 - 18:00`
  - Hàng bản quyền đáy:
    - Trái: `© 2025 Jannie Nguyen Academy. Tất cả quyền được bảo lưu.`
    - Phải: `Chính sách bảo mật` | `Điều khoản dịch vụ`

---

### Bảng Thống Kê Toàn Bộ 19 Icon Material Symbols Outlined Đang Sử Dụng (Tổng 47 Lần Xuất Hiện)

Theo **Quy tắc §4**, toàn bộ 19 icon này sẽ được nhúng trực tiếp dưới dạng **Inline SVG (Apache-2.0)** thông qua block `jn/icon` và helper PHP của plugin `jn-core`, tuyệt đối không tải file font WOFF2 nặng ~4.0 MB từ Google Fonts.

| STT | Tên Icon (`Material Symbols Outlined`) | Số lần xuất hiện | Vị trí xuất hiện trong `code.html` |
|---:|:---|---:|:---|
| 1 | `star` | **15** | Section 5: 3 thẻ cảm nhận học viên × 5 sao (`FILL: 1`) |
| 2 | `check_circle` | **8** | Section 3: 4 quyền lợi Tier 1 + 4 quyền lợi Tier 3 |
| 3 | `verified` | **4** | Section 3: 4 quyền lợi Tier 2 (Gói Chuyên Nghiệp) |
| 4 | `arrow_forward` | **3** | Section 3: Nút CTA của Tier 1, Tier 2, Tier 3 |
| 5 | `expand_more` | **3** | Section 6: Mũi tên của 3 thẻ `<select>` (`#package-select`, `#coaching-format`, `#time-preference`) |
| 6 | `person` | **1** | Header: Nút tròn góc phải cùng |
| 7 | `north_east` | **1** | Section 1 (Hero): Nút chính `Đặt Lịch Coaching Ngay` |
| 8 | `south` | **1** | Section 1 (Hero): Nút phụ `Xem Chi Tiết 3 Gói Cố Vấn` |
| 9 | `all_inclusive` | **1** | Section 1 (Hero): Góc trên phải của lớp phủ ảnh chân dung |
| 10 | `architecture` | **1** | Section 2: Icon thẻ Pillar 01 |
| 11 | `query_stats` | **1** | Section 2: Icon thẻ Pillar 02 |
| 12 | `fingerprint` | **1** | Section 2: Icon thẻ Pillar 03 |
| 13 | `format_quote` | **1** | Section 4: Hộp trích dẫn nổi trên ảnh chuyên gia |
| 14 | `award_star` | **1** | Section 4: Điểm nhấn `Nghệ Nhân Bàn Tay Vàng` |
| 15 | `spa` | **1** | Section 4: Điểm nhấn `Khái Niệm 'The Balance'` |
| 16 | `send` | **1** | Section 6: Nút `Gửi Yêu Cầu Xếp Lịch Coaching` |
| 17 | `location_on` | **1** | Footer: Dòng địa chỉ `TP. Hồ Chí Minh, Việt Nam` |
| 18 | `mail` | **1** | Footer: Dòng email `contact@jannienguyen.vn` |
| 19 | `schedule` | **1** | Footer: Dòng giờ làm việc `Thứ Hai - Thứ Bảy: 09:00 - 18:00` |
| **∑** | **19 icon duy nhất** | **47** | *(Lưu ý: 3 thẻ `<span>` bao ngoài ở Footer cũng mang class `material-symbols-outlined` do lỗi viết lồng thẻ trong `code.html` dòng 604)* |

---

## 2d. Bảng Ánh Xạ Gutenberg Core Blocks & Custom Blocks (`Block-Mapping Table`)

Theo **Quy tắc §2**, toàn bộ bố cục trang sử dụng **Gutenberg Core Blocks**, **Block-Style Variations**, **Block Patterns** của child theme `blocksy-jn` và chỉ **3 Custom Blocks** trong plugin `jn-core` (`jn/icon`, `jn/rating`, `jn/booking-form`).

| Phân đoạn (`Section`) | Gutenberg Core Blocks sử dụng | Block-Style Variations (`register_block_style`) | Pattern Slug (`blocksy-jn/patterns/...`) | Custom Block (`jn-core`) | Lý do kỹ thuật cho Custom Block (`Justification`) |
|:---|:---|:---|:---|:---|:---|
| **Header** | `core/group` (sticky header), `core/site-logo` / `core/paragraph` (`JN`), `core/site-title`, `core/navigation` (kèm responsive drawer của Blocksy/Core), `core/buttons`, `core/button` | `jn-pill-outline` (nút `Đăng ký`), `jn-nav-editorial` (gạch chân active màu `#AC8E76`) | `jn/header-editorial` (hoặc cấu hình Blocksy Header Builder + Menu chuẩn) | `jn/icon` (`person`) | Core Gutenberg không có block hiển thị inline SVG danh mục 19 icon Material Symbols với thuộc tính `aria-hidden="true"` mà không dùng Custom HTML block (vốn bị cấm ở §2). |
| **Section 1: Architectural Hero** | `core/group`, `core/columns` (`66.66%` / `33.33%`), `core/column`, `core/paragraph` (watermark `1:1` & eyebrow), `core/heading` (`level: 1`), `core/buttons`, `core/button`, `core/image` (aspect `4/5`, `fetchpriority="high"`) | `jn-watermark-display` (`1:1`), `jn-eyebrow-dot`, `jn-metrics-bar` (khung 3 chỉ số có vạch chia trái), `jn-btn-primary`, `jn-btn-secondary`, `jn-hero-portrait-frame` (lớp phủ kính mờ) | `jn/coaching-hero` | `jn/icon` (`north_east`, `south`, `all_inclusive`) | Chèn icon mũi tên trong nút CTA và icon `all_inclusive` trên lớp phủ ảnh mà không cần tải icon font hay dùng Custom HTML. |
| **Section 2: Lợi Ích (`Core Pillars`)** | `core/group` (nền `surface-beige`), `core/columns` (header 2 cột + lưới 3 cột `33.33%` × 3), `core/column`, `core/heading` (`level: 2`, `level: 3`), `core/paragraph` | `jn-card-pillar` (thẻ trắng bóng đổ nhẹ, hover nâng `-2px`), `jn-card-pillar-footer` (chân thẻ nền `surface-cream/50`), `jn-icon-badge-circle` | `jn/coaching-pillars` | `jn/icon` (`architecture`, `query_stats`, `fingerprint`) | Hiển thị 3 biểu tượng chuyên môn (`architecture`, `query_stats`, `fingerprint`) dạng SVG nội tuyến 24×24px trong vòng tròn `w-12 h-12`. |
| **Section 3: Chi Tiết 3 Gói (`Pricing & Tiers`)** | `core/group` (anchor `coaching-tiers`), `core/columns` (3 cột `33.33%` × 3), `core/column`, `core/heading` (`level: 2`, `level: 3`), `core/paragraph`, `core/buttons`, `core/button` | `jn-card-tier` (Tier 1 & 3), `jn-card-tier-featured` (Tier 2 nền `#1F1E1D`, nhô cao `-8px`, huy hiệu nổi đỉnh thẻ), `jn-chip-badge`, `jn-tier-checklist-box`, `jn-btn-champagne` (nút Tier 2 nền `#AC8E76` chữ `#1F1E1D`) | `jn/coaching-tiers` | `jn/icon` (`check_circle` ×8, `verified` ×4, `arrow_forward` ×3) | Hiển thị các icon `check_circle`, `verified` trong từng dòng quyền lợi và `arrow_forward` trên nút chọn gói; nút gắn `data-package` để JS tự chọn gói trong form. |
| **Section 4: Giới Thiệu Chuyên Gia** | `core/group` (nền `surface-cream`), `core/columns` (`41.67%` / `58.33%`), `core/column`, `core/image` (aspect `3/4`), `core/quote` / `core/group` (hộp trích dẫn nổi), `core/heading` (`level: 2`, `level: 3`), `core/paragraph` | `jn-floating-quote-card` (hộp trích dẫn góc dưới phải ảnh), `jn-credential-item` | `jn/coaching-expert-bio` | `jn/icon` (`format_quote`, `award_star`, `spa`) | Hiển thị dấu ngoặc kép `format_quote` và 2 huy hiệu `award_star`, `spa` bằng inline SVG chuẩn xác. |
| **Section 5: Cảm Nhận Học Viên** | `core/group`, `core/columns` (header + 3 cột cảm nhận), `core/column`, `core/heading` (`level: 2`, `level: 3`), `core/paragraph`, `core/image` (avatar tròn `48×48`) | `jn-sparkline-badge` (huy hiệu `+180% Doanh Thu` kèm biểu đồ SVG), `jn-card-testimonial`, `jn-avatar-round` | `jn/coaching-testimonials` | `jn/rating` (5 sao SVG + accessible label), `jn/icon` (sparkline SVG) | **`jn/rating`**: Core Gutenberg không có block xếp hạng sao; `jn/rating` kết xuất 5 ngôi sao SVG đồng nhất có `role="img" aria-label="Đánh giá 5 trên 5 sao"` và hỗ trợ đồng bộ CPT `jn_testimonial`. |
| **Section 6: CTA & Booking Form** | `core/group` (nền `surface-beige`, anchor `booking-form`), `core/columns` (`41.67%` cột quy trình nền `#1F1E1D` / `58.33%` cột form nền `#FFFFFF`), `core/column`, `core/heading` (`level: 2`, `level: 3`), `core/paragraph` | `jn-booking-shell` (khung 12 cột bo góc `rounded-xl shadow-xl`), `jn-step-circle-number`, `jn-hotline-box` | `jn/coaching-booking-section` | `jn/booking-form` (bao gồm `jn/icon` cho `expand_more` ×3 và `send`) | **`jn/booking-form`**: Core Gutenberg hoàn toàn không có block biểu mẫu đặt lịch xử lý dữ liệu. `jn/booking-form` cung cấp ServerSideRender form chuẩn Accessibility (`autocomplete`, consent checkbox, focus ring 2px), xác thực REST/AJAX có Nonce + Honeypot + Rate Limit, tạo/cập nhật khách hàng WooCommerce + đơn hàng/booking trạng thái `jn-pending-slot`, và nhận sự kiện chọn gói từ Section 3. |
| **Footer** | `core/group` (nền `surface-beige`), `core/columns` (`50%` / `25%` / `25%`), `core/column`, `core/heading` / `core/paragraph`, `core/navigation` | `jn-footer-editorial` | `jn/footer-editorial` | `jn/icon` (`location_on`, `mail`, `schedule`) | Hiển thị 3 icon thông tin liên hệ ở cột phải của Footer bằng inline SVG. |

---

## 2e. Sổ Đăng Ký Khiếm Khuyết Kỹ Thuật (`Defect Register`)

| Mã lỗi | Mô tả khiếm khuyết đã kiểm chứng trong `code.html` / `DESIGN.md` | Giải pháp khắc phục triệt để (`Fix`) | Bước xử lý (`Owning Step`) |
|:---|:---|:---|:---|
| **DEF-01** | **Font `Bodoni Moda` thiếu toàn bộ ký tự tiếng Việt (`U+1EA0–U+1EF1`)**: Dải `latin-ext` của Bodoni Moda trên Google Fonts bỏ qua toàn bộ nguyên âm có dấu tiếng Việt (ồ, ợ, ế, ậ, ữ…), khiến mọi tiêu đề H1–H3 bị trộn 2 font (chữ không dấu dùng Bodoni Moda, chữ có dấu rơi về Times New Roman). | Chuyển sang `HEADING_FONT` hỗ trợ đầy đủ tiếng Việt (mặc định **`Libre Bodoni`**, kiểm chứng qua `docs/jn/font-specimen.html`), tự host file WOFF2 đã gộp dải `latin` + `vietnamese`, cấu hình `font-display: swap` và metric-matched fallback. | **Step 0** (Specimen & Quyết định) $\rightarrow$ **Step 2** (Tự host WOFF2 trong Child Theme) |
| **DEF-02** | **Tải trùng 2 thẻ `<link>` cho Google Material Symbols (~4.0 MB WOFF2)**: Dòng 3 và dòng 4 của `code.html` gọi 2 lần stylesheet `Material+Symbols+Outlined` để hiển thị chỉ **19 icon duy nhất** (47 lần xuất hiện), gây chặn hiển thị và phình băng thông nghiêm trọng. | Xóa bỏ hoàn toàn Google Fonts / Material Symbols stylesheet; trích xuất đúng **19 bộ đường dẫn SVG (Apache-2.0)** đóng gói vào block `jn/icon` và helper PHP. | **Step 2** (SVG Sprite / Icon Registry) & **Step 3** (`jn/icon`) |
| **DEF-03** | **Sử dụng Tailwind Play CDN (`https://cdn.tailwindcss.com`)**: Nặng ~126 KB nén, chặn luồng dựng trang (`render-blocking`), biên dịch CSS bằng JS ở runtime trên trình duyệt khách. | Loại bỏ hoàn toàn Tailwind CDN. Chuyển toàn bộ Design Tokens sang `theme.json` của child theme `blocksy-jn` và biên dịch file CSS tĩnh gọn nhẹ (`<= 50 KB` nén cho toàn trang). | **Step 2** (Child Theme & `theme.json`) |
| **DEF-04** | **5 thẻ `<img>` thiếu thuộc tính `alt` (chỉ có `data-alt`), thiếu `width`/`height`, hotlink từ `lh3.googleusercontent.com` và là ảnh AI tạo sẵn**: Gây lỗi Accessibility (WCAG 1.1.1), nguy cơ nhảy bố cục (CLS), phụ thuộc máy chủ ngoài, và **rủi ro pháp lý/uy tín** vì dùng ảnh AI minh họa chuyên gia Jannie Nguyễn và 3 khách hàng có tên cụ thể. | 1. Tạo ảnh giữ chỗ nội bộ (`local WebP/AVIF placeholders`) có nhãn rõ ràng, gán đầy đủ `alt` tiếng Việt chuẩn, `width`, `height`, `loading="lazy"` (riêng ảnh Hero `fetchpriority="high"` và `<= 150 KB`).<br>2. Đánh dấu **Launch Blocker (`UNK-11`)** yêu cầu chủ dự án cung cấp ảnh thật có sự đồng thuận trước khi mở trang công khai. | **Step 2 / Step 4** (Tối ưu ảnh nội bộ & `alt`) & **Trước Launch** (Thay ảnh thật từ Owner) |
| **DEF-05** | **Thứ tự thẻ tiêu đề (`Heading Hierarchy`) nhảy cấp**: Trong Section 4 và Section 5 nhảy từ `<h2>` xuống thẳng `<h4>` (`Nghệ Nhân Bàn Tay Vàng`, `Khái Niệm 'The Balance'`, `Minh Thảo`, `Ngọc Bích`, `Phương Uyên`), bỏ qua `<h3>`; trong Section 6 nhảy từ `<h2>` xuống thẳng `<h5>` (3 bước tiếp nhận), vi phạm WCAG 1.3.1 & kiểm toán Lighthouse Accessibility. | Chuẩn hóa thẻ HTML ngữ nghĩa thành `<h3>` trong khi vẫn giữ nguyên class kích thước chữ hiển thị (`text-title-sm` = `15px`, `font-weight: 600`) để giao diện giống hệt `screen.png` 100%. | **Step 4** (Dựng trang Coaching bằng Gutenberg Blocks) |
| **DEF-06** | **Lỗi độ tương phản màu sắc (WCAG 2.2 AA Contrast Failures)**:<br>• Màu `#AC8E76` (`champagne-accent`) làm chữ trên nền sáng chỉ đạt `2.66:1–3.05:1` (cần `>= 4.5:1` cho chữ nhỏ, `>= 3.0:1` cho chữ lớn).<br>• Chữ sáng `#FAF8F5` trên nền `#AC8E76` (huy hiệu & nút Tier 2, vòng tròn số 1–2–3 ở Section 6) chỉ đạt `2.88:1`.<br>• Chữ `#7A7268` (`muted-stone`) trên nền `#F3EFEA` / `#FAF8F5` chỉ đạt `4.14:1–4.47:1`. | Áp dụng nghiêm ngặt bảng màu đã hiệu chỉnh tại **Quy tắc §4**:<br>• Chữ nhấn nhỏ trên nền sáng dùng **`#7E6754`** (`4.64–5.31:1`).<br>• Chữ nhấn lớn (`>= 24px`) trên nền sáng dùng **`#A0846E`** (`3.05–3.49:1`).<br>• Nền `#AC8E76` bắt buộc đi với **chữ tối `#1F1E1D`** (`5.46:1`).<br>• Chữ muted trên nền sáng dùng **`#716A61`** (`4.66–5.33:1`). | **Step 2** (`theme.json` & CSS Variables) & **Step 4** |
| **DEF-07** | **Thanh điều hướng chính bị ẩn hoàn toàn dưới `1280px` (`hidden xl:flex`) và không có Mobile Menu**: Trên màn hình `< 1280px` (Tablet, Laptop 13" thu nhỏ, Mobile), người dùng không có bất kỳ nút hamburger hay ngăn kéo điều hướng nào để chuyển trang; cụm `Đăng nhập / Đăng ký` cũng biến mất dưới `640px` (`hidden sm:flex`). | Triển khai nút mở Off-Canvas Mobile/Tablet Drawer (`< 1280px`) có đầy đủ `aria-expanded`, `aria-controls`, bẫy tiêu điểm (`focus trap`), đóng bằng phím `Escape`, chứa toàn bộ 7 mục menu và cụm `Đăng nhập / Đăng ký`. | **Step 2** (Header & Mobile Navigation) |
| **DEF-08** | **Biểu mẫu giả lập (`setTimeout`), sự kiện `onclick` nội tuyến & lỗi hàm `selectTier()`**:<br>• `handleFormSubmit()` chỉ chạy `setTimeout(..., 600)` ẩn nút gửi, không lưu dữ liệu.<br>• Các nút chọn gói gắn `onclick="selectTier(...)"` nội tuyến.<br>• `selectTier(packageName)` đối chiếu bằng `packageName.slice(0, 10)`, rất dễ chọn nhầm nếu tên gói có tiền tố giống nhau (`"Gói Tư Vấn..."` vs `"Gói Coachi..."`). | 1. Xây dựng endpoint xử lý thật trong `jn-core` (`jn/booking-form`) tạo Booking + WooCommerce Order/Customer kèm kiểm tra Nonce, Honeypot, Rate-limit.<br>2. Bỏ toàn bộ `onclick` nội tuyến; dùng `data-package-id` / giá trị khớp chính xác 100% trong file JS `booking-form.js` (`defer`). | **Step 3** (Plugin `jn-core` & Block `jn/booking-form`) |
| **DEF-09** | **Ô nhập liệu không có vòng Focus (`focus:outline-none`), thiếu `autocomplete`, thiếu ô đồng ý bảo mật (Consent Checkbox)**: Vi phạm WCAG 2.4.7 (Focus Visible), WCAG 1.3.5 (Identify Input Purpose: `autocomplete="name"`, `"tel"`, `"email"`) và quy định bảo vệ dữ liệu cá nhân. | 1. Bổ sung kiểu `:focus-visible { outline: 2px solid #1F1E1D; outline-offset: 2px; }`.<br>2. Gắn `autocomplete="name"`, `autocomplete="tel"`, `autocomplete="email"`.<br>3. Bổ sung checkbox đồng ý Chính sách bảo mật bắt buộc trước khi gửi form. | **Step 2** (Focus ring CSS) & **Step 3** (`jn/booking-form`) |
| **DEF-10** | **Dữ liệu giả giữ chỗ & Liên kết chết**:<br>• Số điện thoại `(+84) 908-XXX-XXX` không bấm gọi được.<br>• Bản quyền ghi tĩnh `© 2025`.<br>• Toàn bộ liên kết Header và Footer trỏ vào `href="#"`.<br>• Thẻ `<span class="material-symbols-outlined">` ở Footer bao trùm cả chữ địa chỉ/email/giờ làm việc (dòng 604). | 1. Cấu hình biến liên hệ trung tâm trong `jn-core` (hiển thị nhãn chờ rõ ràng cho đến khi có số thật từ Owner).<br>2. Dùng năm động trong Footer.<br>3. Liên kết tất cả mục menu và Footer tới đúng URL trang thực tế.<br>4. Tách riêng thẻ `<svg>` icon và thẻ `<span>` văn bản ở Footer. | **Step 2** (Header/Footer) & **Step 4** (Các trang nội dung) |
| **DEF-11** | **Ẩn thanh cuộn toàn cục (`::-webkit-scrollbar{display:none}`) & thiếu `scroll-padding-top` cho Header cố định `100px`**: Ẩn thanh cuộn gây khó khăn cho người dùng chuột trên Desktop; khi nhảy neo `#coaching-tiers` hoặc `#booking-form`, Header cố định cao `100px` che khuất phần đầu của Section. | 1. Xóa quy tắc `::-webkit-scrollbar{display:none}`.<br>2. Thêm `html { scroll-behavior: smooth; scroll-padding-top: 116px; }` (`100px` header + `16px` khoảng thở). | **Step 2** (Base CSS trong Child Theme) |
| **DEF-12** | **Các tuyên bố số liệu (`20+` năm, `98%`, `+180%`, `> 85%`, `Oscar for Beauty`) và 3 lời chứng thực học viên**: Đây là các tuyên bố thực tế thuộc trách nhiệm pháp lý của chủ thương hiệu. | Giữ nguyên văn 100% theo wireframe, tuyệt đối không tự bịa thêm số liệu hay lời chứng thực mới; đưa vào danh mục cần chủ dự án xác nhận (`Owner Verification`) trước khi xuất bản chính thức. | **Step 0** (Ghi nhận vào `PROGRESS.md`) & **Trước Launch** |

---

## 2f. Yêu Cầu Nghiệp Vụ & Tính Năng (`Feature Requirements`)

### 2f.1. Cấu Trúc Biểu Mẫu Đặt Lịch (`jn/booking-form`) Nguyên Văn (`Verbatim`)

| Thứ tự | ID trường (`id`) | Tên trường (`name`) | Nhãn hiển thị (`<label>`) | Loại (`type`) | Bắt buộc | `autocomplete` | `placeholder` / Danh sách `<option>` nguyên văn |
|---:|:---|:---|:---|:---|:---:|:---|:---|
| 1 | `full-name` | `full_name` | `Họ và Tên *` | `text` | Có (`required`) | `name` | `Nguyễn Văn A` |
| 2 | `phone-number` | `phone_number` | `Số Điện Thoại / Zalo *` | `tel` | Có (`required`) | `tel` | `090 123 4567` |
| 3 | `email-address` | `email_address` | `Địa Chỉ Email *` | `email` | Có (`required`) | `email` | `name@example.com` |
| 4 | `package-select` | `package_code` | `Gói Cố Vấn Quan Tâm *` | `select` | Có (`required`) | `off` | 1. **Mặc định (`selected`)**:<br>• `value`: `Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)`<br>• `text`: `Gói Coaching Chuyên Nghiệp 1:1 (60 Phút) — Dành cho thợ & chủ Spa`<br>2. Tùy chọn 2:<br>• `value`: `Gói Tư Vấn Cá Nhân 1:1 (30 Phút)`<br>• `text`: `Gói Tư Vấn Cá Nhân 1:1 (30 Phút) — Định hình vẻ đẹp riêng`<br>3. Tùy chọn 3:<br>• `value`: `Gói Master Mentorship (3 Tháng)`<br>• `text`: `Gói Master Mentorship (Lộ trình 3 tháng toàn diện)` |
| 5 | `coaching-format` | `coaching_format` | `Hình Thức Gặp` | `select` | Không | `off` | 1. `value="online"`: `Online Trực Tuyến (Zoom High-Res)`<br>2. `value="in-person"`: `Trực tiếp tại Academy (TP.HCM)` |
| 6 | `time-preference` | `time_preference` | `Khung Giờ Thuận Tiện` | `select` | Không | `off` | 1. `value="morning"`: `Buổi sáng (09:00 - 11:30)`<br>2. `value="afternoon"`: `Buổi chiều (14:00 - 17:30)`<br>3. `value="weekend"`: `Cuối tuần (Thứ 7)` |
| 7 | `notes` | `notes` | `Mục Tiêu Hoặc Thách Thức Lớn Nhất Của Bạn` | `textarea` (`rows="3"`) | Không | `off` | `Chia sẻ ngắn gọn tình trạng kỹ thuật, lượng khách hiện tại hoặc câu hỏi bạn mong muốn giải quyết nhất cùng Jannie...` |
| 8 | `privacy-consent` *(bổ sung theo DEF-09)* | `privacy_consent` | Đồng ý chính sách bảo mật dữ liệu cá nhân | `checkbox` | Có (`required`) | `off` | Người dùng tích chọn trước khi gửi yêu cầu. |

- **Nút Gửi (`#submit-btn`)**:
  - Trạng thái nghỉ: `Gửi Yêu Cầu Xếp Lịch Coaching` + icon `send`.
  - Trạng thái đang gửi: `Đang Xử Lý Hồ Sơ...` (`disabled`, `aria-busy="true"`).
- **Thông báo thành công (`#form-feedback`)**:
  - `Cảm ơn bạn! Yêu cầu đã được ghi nhận. Đội ngũ trợ lý của Jannie Nguyễn sẽ liên hệ trực tiếp qua số điện thoại của bạn trong vòng 24 giờ.`

### 2f.2. Quy Trình Tiếp Nhận Đặt Lịch 3 Bước (`3-Step Intake & Payment Model`)
Theo wireframe Section 6 và Project Context Card (`BOOKING MODEL: request-based`, `PAYMENT POLICY: pay after the assistant confirms the slot`, `AUTO-CREATE CUSTOMER ACCOUNT ON BOOKING: no`):
1. **Bước 1 — Gửi yêu cầu thông tin**:
   - Khách hàng điền biểu mẫu `#coaching-registration-form` (không bắt buộc tạo tài khoản WordPress; `AUTO-CREATE CUSTOMER ACCOUNT ON BOOKING = no`).
   - Hệ thống `jn-core` kiểm tra Nonce, Honeypot, giới hạn tần suất (Rate Limit), làm sạch dữ liệu đầu vào, tạo/cập nhật hồ sơ khách hàng trong bảng khách hàng WooCommerce (HPOS-safe) và khởi tạo một **Đơn hàng / Yêu cầu Đặt lịch WooCommerce** ở trạng thái chờ xác nhận lịch (`on-hold` / custom status `wc-jn-pending-slot`) lưu kèm metadata (`coaching_format`, `time_preference`, `notes`).
2. **Bước 2 — Trợ lý xác nhận khung giờ trong 24 giờ**:
   - Đội ngũ trợ lý học viện xem yêu cầu trong trang quản trị `jn-core`, liên hệ với khách qua Số điện thoại / Zalo trong vòng 24 giờ làm việc để chốt ngày giờ cụ thể.
   - Sau khi thống nhất khung giờ, trợ lý cập nhật thời gian hẹn, chuyển trạng thái sang **Đã xác nhận lịch — Chờ thanh toán (`Pending Payment`)**, và hệ thống gửi đường dẫn thanh toán (chuyển khoản ngân hàng) qua email cho khách hàng.
3. **Bước 3 — Bước vào phiên làm việc 1:1**:
   - Sau khi xác nhận thanh toán (`Processing` / `Completed`), khách hàng tham gia phiên cố vấn 1:1 trực tiếp cùng chuyên gia Jannie Nguyễn qua Zoom High-Res hoặc tại Academy (TP.HCM).

### 2f.3. Phân Khúc Khách Hàng Mục Tiêu (`3 Customer Segments`)
1. **Phân khúc 1 — Cá nhân (`Cá Nhân`)**: Khách hàng cá nhân mong muốn định hình vẻ đẹp tự nhiên, phân tích cấu trúc khuôn mặt/làn da và xây dựng quy trình chăm sóc tối giản, không lạm dụng hóa chất.
2. **Phân khúc 2 — Thợ làm nghề & Học viên nâng cao (`Chuyên Nghiệp`)**: Kỹ thuật viên phun xăm, thẩm mỹ cần tháo gỡ điểm nghẽn kỹ thuật thực chiến (như kỹ thuật sợi chân mày, điêu khắc tự nhiên) và nâng cấp tay nghề.
3. **Phân khúc 3 — Chủ cơ sở thẩm mỹ, phun xăm, Salon & Spa cao cấp (`Chuyên Nghiệp` & `Master Class`)**: Chủ doanh nghiệp làm đẹp cần chuẩn hóa quy trình chăm sóc thượng khách, thiết lập bảng giá dịch vụ cao cấp, xây dựng tệp khách VIP trung thành (tỷ lệ quay lại > 85%), đào tạo đội ngũ và nhân bản thương hiệu.

### 2f.4. Ma Trận 3 Gói Dịch Vụ (`3 Coaching Packages` — Sản Phẩm Dịch Vụ Ảo WooCommerce)

| Mã gói (`SKU`) | Tên gói trên thẻ (`<h3>`) | Tên hiển thị trong `<select>` | Thời lượng / Lộ trình | Phân khúc mục tiêu | Giá niêm yết (VND) |
|:---|:---|:---|:---|:---|:---|
| `jn-coaching-30m` | `Tư Vấn Cá Nhân 1:1` | `Gói Tư Vấn Cá Nhân 1:1 (30 Phút) — Định hình vẻ đẹp riêng` | `Thời lượng: 30 Phút` | `Cá Nhân` | **`UNKNOWN`** *(Chờ Owner cung cấp)* |
| `jn-coaching-60m` | `Coaching Chuyên Nghiệp 1:1` *(Khuyên Chọn)* | `Gói Coaching Chuyên Nghiệp 1:1 (60 Phút) — Dành cho thợ & chủ Spa` | `Thời lượng: 60 Phút` | `Chuyên Nghiệp` (`Chủ Cơ Sở & Học Viên`) | **`UNKNOWN`** *(Chờ Owner cung cấp)* |
| `jn-mentorship-3m` | `Master Mentorship 3 Tháng` | `Gói Master Mentorship (Lộ trình 3 tháng toàn diện)` | `Lộ trình: 3 Tháng Toàn Diện` (12 buổi 1:1 + hỗ trợ 24/7 trong 90 ngày) | `Master Class` (Chủ cơ sở thẩm mỹ) | **`UNKNOWN`** *(Chờ Owner cung cấp)* |

---

## 2g. Sổ Đăng Ký Thông Tin Chưa Biết (`UNKNOWN Register`)

Theo **Quy tắc §1 & §7**, mọi thông tin `UNKNOWN` từ `PROJECT CONTEXT CARD` tuyệt đối không được tự bịa đặt mà phải gắn nhãn placeholder rõ ràng và theo dõi tại đây cùng `docs/jn/PROGRESS.md`:

| Mã ID | Hạng mục `UNKNOWN` | Giá trị hiện tại trong Context Card / Wireframe | Bước bị ảnh hưởng (`Step Blocked`) | Chặn ra mắt công khai (`Blocks Launch?`) | Giải pháp tạm thời an toàn trong quá trình phát triển |
|:---|:---|:---|:---|:---:|:---|
| **UNK-01** | **`HEADING_FONT` (Xác nhận cuối cùng)** | Mặc định đề xuất: `Libre Bodoni` (cần duyệt qua `docs/jn/font-specimen.html` so với `Bodoni Moda`, `Playfair Display`, `Noto Serif Display`) | **Chặn Step 2** (Đóng gói WOFF2 trong Child Theme) | **Có** | Dừng ở cuối Step 0, gửi ảnh chụp `font-specimen.html` để chủ dự án chốt font tiêu đề trước khi vào Step 1–2. |
| **UNK-02** | **`PROD_DOMAIN`** | `UNKNOWN` | Step 7 (Hardening/SSL), Step 8 (SEO canonical/sitemap), Step 9 (Go-live) | **Có** | Dùng biến môi trường `WP_HOME` / `WP_SITEURL` (`http://localhost:8080` ở môi trường Docker local). |
| **UNK-03** | **`PROD_WEB_SERVER + PHP`** | `UNKNOWN` | Step 7 (Rewrite rules `.htaccess` vs Nginx conf) & Step 8 (Chọn LiteSpeed Cache hay WP Super Cache theo §2) | **Có** | Viết bộ quy tắc cho cả Apache/LiteSpeed (`.htaccess`) và Nginx (`docker/nginx.conf`), đóng gói cấu hình cache trừu tượng. |
| **UNK-04** | **`BRAND CONTACT — Số điện thoại / Zalo`** | `(+84) 908-XXX-XXX` | Step 2 (Footer) & Step 4 (Section 6) | **Có** | Giữ nguyên chuỗi `contact@jannienguyen.vn • (+84) 908-XXX-XXX` từ wireframe, cấu hình qua hằng số/setting để thay 1 chỗ khi có số thật. |
| **UNK-05** | **`BRAND CONTACT — Địa chỉ chi tiết tại TP.HCM`** | `"TP. Hồ Chí Minh, Việt Nam"` (địa chỉ đầy đủ `UNKNOWN`) | Step 2 (Footer), Step 4 (Section 6), Step 8 (LocalBusiness JSON-LD Schema) | **Có** | Hiển thị `"TP. Hồ Chí Minh, Việt Nam"` đúng nguyên văn wireframe; để trường `streetAddress` trong Schema ở chế độ chờ cấu hình. |
| **UNK-06** | **`PACKAGE PRICES (VND)` cho 3 gói Coaching** | 30 phút: `UNKNOWN`; 60 phút: `UNKNOWN`; Master Mentorship 3 tháng: `UNKNOWN` | Step 3 (Tạo sản phẩm dịch vụ WooCommerce & gửi link thanh toán) | **Có** (chặn thu tiền tự động) | Khởi tạo 3 sản phẩm WooCommerce với giá `0` + nhãn `"Liên hệ / Báo giá khi xác nhận lịch"`, cho phép luồng đặt lịch theo yêu cầu hoạt động trọn vẹn. |
| **UNK-07** | **`PAYMENT METHODS — Thông tin tài khoản ngân hàng`** | `bank transfer (bank, account number, holder UNKNOWN)` | Step 3 (Cấu hình cổng BACS / VietQR của WooCommerce) | **Có** (chặn thanh toán thật) | Bật cổng chuyển khoản ngân hàng (`bacs`) của WooCommerce với placeholder rõ ràng trong trang quản trị. |
| **UNK-08** | **`COURSES` (Danh sách & nội dung khóa học)** | `UNKNOWN` | Step 5 (Nội dung trang `/course/`) | **Có** (cho trang `/course/`) | Dựng khung trang `/course/` chuẩn Design System với thông báo nhận đăng ký tư vấn lộ trình học. |
| **UNK-09** | **`REAL PHOTOS` (Ảnh thật của Jannie Nguyễn & 3 học viên)** | `UNKNOWN` (hiện đang là 5 ảnh AI trên `lh3.googleusercontent.com`) | Step 4 (Thay thế ảnh giữ chỗ trước khi xuất bản) | **CÓ (Launch Blocker nghiêm trọng)** | Lưu 5 ảnh tối ưu WebP nội bộ làm placeholder kỹ thuật để kiểm thử tốc độ (LCP `<= 150 KB`), gắn cờ bắt buộc thay bằng ảnh thật có sự đồng thuận trước khi mở site. |
| **UNK-10** | **`STAFF ACCOUNTS` (Danh sách tài khoản trợ lý vận hành)** | `UNKNOWN` | Step 3 (Phân quyền vai trò `jn_booking_assistant`) & Step 9 (Bàn giao) | **Không** (có thể tạo khi bàn giao) | Tạo sẵn Role `jn_booking_assistant` và 1 tài khoản kiểm thử nội bộ ở môi trường dev. |
| **UNK-11** | **`PRODUCTION SMTP`** | `UNKNOWN` (`ADMIN_EMAIL` đang là `admin@example.com`) | Step 7 / Step 9 (Cấu hình FluentSMTP gửi mail thật) | **Có** | Ở môi trường Docker local dùng Mailpit để bắt và kiểm thử toàn bộ email xác nhận đặt lịch; cài sẵn FluentSMTP chờ nhập thông tin SMTP thật. |
| **UNK-12** | **Wireframe / Nội dung chi tiết cho các trang phụ (`Home`, `My Story`, `Client Success Story`, `Chính sách bảo mật`, `Điều khoản dịch vụ`)** | Chưa có wireframe riêng (chỉ có trang `Coaching`) | Step 5 (Xây dựng các trang còn lại trong Page Map) | **Có** | Sử dụng các khối/pattern đã duyệt từ trang Coaching và hỏi ý kiến chủ dự án trước khi hoàn thiện nội dung trang phụ ở Step 5. |
| **UNK-13** | **Xác nhận chủ sở hữu đối với các số liệu (`20+`, `98%`, `+180%`, `> 85%`, `Oscar for Beauty`) & 3 lời chứng thực** | Đang giữ nguyên văn từ wireframe | Trước khi ra mắt (`Step 9`) | **Có** | Giữ nguyên văn 100%, không tự ý thêm bớt; yêu cầu chủ dự án ký duyệt tính xác thực trước khi go-live. |
