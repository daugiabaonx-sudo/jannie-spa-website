# Sổ Tay Vận Hành Triển Khai & Khôi Phục Thảm Họa (`Deployment & Rollback Runbook`)

> **Dự án:** Website Jannie Nguyễn — Luxury Beauty Academy & Mentorship (`WordPress 7.1.3`)
> **Bộ công cụ triển khai:** [`deploy/`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/)
> **Nguyên tắc an toàn tuyệt đối:** Luôn chụp bản sao lưu tệp mã nguồn và cơ sở dữ liệu trước khi thao tác; luôn chạy `--dry-run` trước khi thay thế tên miền trong cơ sở dữ liệu; sẵn sàng khôi phục (`Rollback`) về trạng thái trước đó trong vòng **dưới 60 giây** nếu bất kỳ kiểm tra sau triển khai nào thất bại.

---

## 1. Danh Mục Bộ Công Cụ Trong `deploy/`

| Tệp trong `deploy/` | Chức năng |
| :--- | :--- |
| [`deploy/wp-config.production.php`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/wp-config.production.php) | Cấu hình `wp-config.php` chuẩn Production: đọc toàn bộ biến từ môi trường, `WP_ENVIRONMENT_TYPE = 'production'`, tắt toàn bộ `WP_DEBUG*`, bật `FORCE_SSL_ADMIN = true`, `DISABLE_WP_CRON = true`, `DISALLOW_FILE_EDIT = true`, đường dẫn `/app`, `/app/modules`, `/media`. |
| [`deploy/.env.production.example`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/.env.production.example) | Mẫu biến môi trường `.env.production` (đặt ngoài web root hoặc quyền `0600`). |
| [`deploy/generate-salts.sh`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/generate-salts.sh) | Sinh mới 8 khóa mật mã học (`AUTH_KEY` .. `NONCE_SALT`) độ dài 64 byte bằng `openssl rand -base64 48`. |
| [`deploy/cron.d-jannie-wordpress`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/cron.d-jannie-wordpress) | Cấu hình Linux Cron thật (`/etc/cron.d/jannie-wordpress`) chạy định kỳ mỗi 5 phút thay thế `wp-cron.php` theo lượt tải trang. |
| [`deploy/apache-litespeed.htaccess`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/apache-litespeed.htaccess) | Quy tắc máy chủ cho **Apache 2.4+ / LiteSpeed**: ép buộc HTTPS & canonical host (`non-www`), 6 HTTP Security Headers, nén Brotli/Gzip, `Cache-Control: immutable`, quy tắc chặn Step 7 và bí danh `/static/*`, `/api/ajax`. |
| [`deploy/nginx-production.conf`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/nginx-production.conf) | Quy tắc máy chủ tương đương 1:1 cho **Nginx + PHP-FPM 8.3**. |
| [`deploy/rsync-exclude.txt`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/rsync-exclude.txt) | Danh sách loại trừ khi đồng bộ tệp lên máy chủ thật (loại trừ `backups/`, `node_modules/`, `tests/`, `docs/`, `scripts/`, `docker/`, `.git/`, `.env*`, `*.sql*`, `*.log`). |
| [`deploy/db-search-replace.sh`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/db-search-replace.sh) | Tự động xuất snapshot DB trước khi chạy, thực thi `wp search-replace` an toàn tuần tự hóa PHP (`--precise --recurse-objects`) ở chế độ **`--dry-run` mặc định**, và chỉ áp dụng thật + bật `blog_public = 1` khi có cờ `--apply`. |
| [`deploy/rollback.sh`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/rollback.sh) | Kịch bản khôi phục khẩn cấp 1 lệnh: khôi phục mã nguồn từ bản phát hành liền trước và nạp lại bản chụp cơ sở dữ liệu `pre-deploy.sql.gz`. |

---

## 2. Quy Trình Triển Khai Lên Máy Chủ Production (`Step-by-Step Deployment`)

### Bước 2.1 — Kiểm tra điều kiện tiên quyết (Pre-Flight Checklist)
1. Xác nhận `0` mục chặn phát hành chưa được giải quyết hoặc miễn trừ bằng lệnh:
   ```bash
   ./scripts/wp.sh eval-file /scripts/check-launch-blockers.php
   ```
2. Xác nhận chứng chỉ SSL/TLS (`Let's Encrypt` hoặc thương mại) đã hoạt động cho cả `PROD_DOMAIN` và `www.PROD_DOMAIN`.
3. Tạo bản sao lưu hiện trạng trên máy chủ đích (nếu máy chủ đã có phiên bản cũ):
   ```bash
   mkdir -p /var/www/PROD_DOMAIN/backups /var/www/PROD_DOMAIN/releases/previous
   rsync -a --delete /var/www/PROD_DOMAIN/wordpress/ /var/www/PROD_DOMAIN/releases/previous/
   wp db export - --path=/var/www/PROD_DOMAIN/wordpress --add-drop-table | gzip -9 > /var/www/PROD_DOMAIN/backups/pre-deploy.sql.gz
   ```

### Bước 2.2 — Đồng bộ mã nguồn sạch theo `deploy/rsync-exclude.txt`
Từ máy trạm phát triển, đồng bộ thư mục `./wordpress/` lên máy chủ đích (tuyệt đối không đẩy `backups/`, `node_modules/`, `tests/`, `docs/`, `.git/`, `.env`).
`deploy/rsync-exclude.txt` loại trừ các đường dẫn do production sở hữu (`/media/`, `/wp-config.php`, `/.htaccess`, `/app/cache/`, `/app/upgrade/`): rsync **không ghi đè và không xóa** chúng dù dùng `--delete`.

1. **Luôn chạy thử trước** (`--dry-run`) và đọc kỹ mọi dòng `deleting`:
   ```bash
   rsync -avz --delete --dry-run \
     --exclude-from=deploy/rsync-exclude.txt \
     ./wordpress/ deploy_user@PROD_SERVER:/var/www/PROD_DOMAIN/wordpress/
   ```
2. Khi kết quả chạy thử hợp lệ, chạy thật (bỏ `--dry-run`):
   ```bash
   rsync -avz --delete \
     --exclude-from=deploy/rsync-exclude.txt \
     ./wordpress/ deploy_user@PROD_SERVER:/var/www/PROD_DOMAIN/wordpress/
   ```
3. **Chỉ ở lần triển khai đầu tiên:** đẩy thư viện ảnh đã duyệt **không kèm `--delete`** (các lần sau, ảnh được quản lý trực tiếp trên production):
   ```bash
   rsync -avz --exclude='jn-ics-tmp/' --exclude='wc-logs/' \
     ./wordpress/media/ deploy_user@PROD_SERVER:/var/www/PROD_DOMAIN/wordpress/media/
   ```

### Bước 2.3 — Cài đặt `wp-config.php`, Khóa Salt mới, Quy tắc Máy chủ & Cron thật
1. Sinh bộ 8 khóa Salt mới và tạo tệp `/var/www/PROD_DOMAIN/.env.production` **bên ngoài thư mục web** (theo mẫu `deploy/.env.production.example`, điền thêm `DB_*`, `WP_HOME`, `WP_SITEURL`, `WP_LOGIN_SLUG`):
   ```bash
   ./deploy/generate-salts.sh   # dán kết quả vào .env.production
   sudo chown root:www-data /var/www/PROD_DOMAIN/.env.production
   sudo chmod 0640 /var/www/PROD_DOMAIN/.env.production
   ```
   `wp-config.php` tự nạp tệp này (đường dẫn mặc định: thư mục cha của web root; đổi bằng biến `JN_ENV_FILE`), nên PHP-FPM, cron hệ thống và WP-CLI dùng chung một nguồn. Nếu thiếu biến bắt buộc, site trả `503` (CLI thoát mã `1`) và ghi rõ tên biến thiếu — không bao giờ khởi động với mật khẩu/salt rỗng.
2. Sao chép [`deploy/wp-config.production.php`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/wp-config.production.php) thành `/var/www/PROD_DOMAIN/wordpress/wp-config.php` (`chmod 0640`), rồi kiểm tra: `wp option get home --path=/var/www/PROD_DOMAIN/wordpress`.
3. Cài đặt quy tắc máy chủ tùy theo `PROD_WEB_SERVER`:
   - **Nếu dùng Apache hoặc LiteSpeed:** Sao chép [`deploy/apache-litespeed.htaccess`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/apache-litespeed.htaccess) vào `/var/www/PROD_DOMAIN/wordpress/.htaccess`. (Nếu máy chủ là LiteSpeed Enterprise, có thể chuyển từ `wp-super-cache` sang `litespeed-cache` theo `ADR-011`).
   - **Nếu dùng Nginx:** Sao chép [`deploy/nginx-security-headers.conf`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/nginx-security-headers.conf) vào `/etc/nginx/snippets/jn-security-headers.conf` (được `include` ở cấp server **và** trong block tài nguyên tĩnh, vì `add_header` trong `location` không kế thừa header cấp server), sao chép [`deploy/nginx-production.conf`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/nginx-production.conf) vào `/etc/nginx/sites-available/PROD_DOMAIN.conf`, thay `PROD_DOMAIN` bằng tên miền thật, chạy `nginx -t && systemctl reload nginx`.
4. Cài đặt Cron hệ thống thật (thay thế `wp-cron.php` đã tắt qua `DISABLE_WP_CRON = true`). Kết quả chạy được ghi vào `/var/log/jannie-wp-cron.log` để phát hiện lỗi:
   ```bash
   sudo cp deploy/cron.d-jannie-wordpress /etc/cron.d/jannie-wordpress
   sudo chmod 0644 /etc/cron.d/jannie-wordpress
   sudo sed -i 's/PROD_DOMAIN/tên-miền-thật/g' /etc/cron.d/jannie-wordpress
   sudo touch /var/log/jannie-wp-cron.log && sudo chown www-data:www-data /var/log/jannie-wp-cron.log
   ```

### Bước 2.4 — Nạp Cơ Sở Dữ Liệu & Chạy `search-replace` Có `--dry-run` Trước
1. Nạp bản cơ sở dữ liệu bàn giao `backups/jn-step-9.sql.gz` vào MariaDB trên máy chủ:
   ```bash
   gzip -dc backups/jn-step-9.sql.gz | wp db import - --path=/var/www/PROD_DOMAIN/wordpress
   ```
2. **Chạy thử (`--dry-run`) trước** để kiểm tra số lượng chuỗi được thay thế (an toàn với dữ liệu tuần tự hóa PHP `serialized data`):
   ```bash
   ./deploy/db-search-replace.sh PROD_DOMAIN --dry-run
   ```
   *(Kiểm tra bảng kết quả: dự kiến ~130 vị trí trong `jn_8f4c_options`, `jn_8f4c_postmeta`, `jn_8f4c_posts` với cột `Type = PHP`).*
3. **Thực thi thay thế thật + mở phép lập chỉ mục Google (`blog_public = 1`):**
   ```bash
   ./deploy/db-search-replace.sh PROD_DOMAIN --apply
   ```

### Bước 2.4b — Thay toàn bộ thông tin đăng nhập kế thừa từ bản DB phát triển (bắt buộc trước khi mở site)
Bản DB nạp ở bước 2.4 đến từ môi trường phát triển; đường dẫn đăng nhập và tên quản trị cũ từng nằm trong lịch sử git. Trước khi mở site:
1. Đặt `WP_LOGIN_SLUG` **mới, ngẫu nhiên** trong `/var/www/PROD_DOMAIN/.env.production` (ví dụ `openssl rand -hex 6` ghép với một từ dễ nhớ). Plugin ẩn đăng nhập tự đọc biến này; bàn giao giá trị cho quản trị viên qua kênh bảo mật.
2. Tạo tài khoản quản trị mới với tên không đoán được, chuyển nội dung sang và xóa tài khoản quản trị cũ:
   ```bash
   wp user create <ten-quan-tri-moi> <email-quan-tri> --role=administrator --path=/var/www/PROD_DOMAIN/wordpress
   wp user delete <ten-quan-tri-cu> --reassign=<id-quan-tri-moi> --path=/var/www/PROD_DOMAIN/wordpress
   ```
3. Buộc mọi tài khoản nhân sự đặt lại mật khẩu (gửi email đặt lại mật khẩu, mật khẩu cũ hết hiệu lực):
   ```bash
   wp user reset-password $(wp user list --role__in=administrator,shop_manager,jn_booking_assistant,editor --field=ID --path=/var/www/PROD_DOMAIN/wordpress) --path=/var/www/PROD_DOMAIN/wordpress
   ```

### Bước 2.5 — Kiểm tra Sau Triển Khai (Post-Deploy Verification)
1. Kiểm tra chuyển hướng HTTPS và Canonical Host:
   ```bash
   curl -I http://PROD_DOMAIN/
   curl -I https://www.PROD_DOMAIN/
   curl -I https://PROD_DOMAIN/robots.txt
   curl -I https://PROD_DOMAIN/sitemap.xml
   ```
2. Chạy trình quét dấu vết ẩn WordPress trên tên miền thật:
   ```bash
   JN_BASE_URL="https://PROD_DOMAIN" node scripts/jn-fingerprint-scan.mjs
   ```
3. Chạy kiểm tra PageSpeed Insights (3 lần Mobile + 3 lần Desktop cho mỗi URL) và kiểm thử khói Đặt lịch + Thanh toán (`Task 4`):
   ```bash
   JN_LIVE_URL="https://PROD_DOMAIN" node scripts/run-step-9-psi-and-live-e2e.mjs
   ```

---

## 3. Quy Trình Khôi Phục Khẩn Cấp (`Rollback Runbook` — Hoàn Tất Trong `< 60 Giây`)

### 3.1. Khi nào phải kích hoạt Rollback ngay lập tức?
Kích hoạt Rollback ngay lập tức nếu xảy ra **bất kỳ** tình huống nào sau đây sau khi triển khai mà không thể xử lý trong vòng 5 phút:
- Trang chủ (`/`) hoặc trang `/coaching/` trả về mã lỗi HTTP `500`, `502`, `503` hoặc trắng trang.
- Biểu mẫu đặt lịch `jn/booking-form` không gửi được yêu cầu hoặc liên kết thanh toán `order-pay` bị lỗi.
- Lỗi hỏng chuỗi tuần tự hóa (`PHP unserialize notice`) làm mất cấu hình giao diện hoặc cài đặt WooCommerce.
- Trình quét dấu vết [`scripts/jn-fingerprint-scan.mjs`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/scripts/jn-fingerprint-scan.mjs) báo `FAIL` trên các đường dẫn bảo mật trọng yếu.

### 3.2. Thực thi Rollback bằng 1 lệnh tự động
Chạy kịch bản [`deploy/rollback.sh`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/deploy/rollback.sh) với đường dẫn tới bản sao lưu cơ sở dữ liệu trước khi deploy (`pre-deploy.sql.gz` hoặc `pre-search-replace-*.sql.gz`) và thư mục mã nguồn phiên bản trước (`/var/www/PROD_DOMAIN/releases/previous`):

```bash
WEB_ROOT="/var/www/PROD_DOMAIN/wordpress" BACKUP_DIR="/var/www/PROD_DOMAIN/backups" \
  ./deploy/rollback.sh \
  /var/www/PROD_DOMAIN/backups/pre-deploy.sql.gz \
  /var/www/PROD_DOMAIN/releases/previous
```

Kịch bản sẽ hỏi xác nhận (gõ `rollback`); thêm `--yes` làm tham số đầu tiên nếu chạy không tương tác. Thứ tự thực hiện: (1) xuất bản chụp DB **hiện tại** ra `pre-rollback-<thời-điểm>.sql.gz` để giữ lại đơn hàng/lịch hẹn phát sinh sau deploy, (2) nạp DB trước deploy, (3) mới khôi phục mã nguồn — nếu nạp DB lỗi thì mã nguồn chưa bị đổi. Kịch bản từ chối chạy khi `WEB_ROOT` còn là `PROD_DOMAIN` hoặc không chứa `wp-config.php`.

### 3.3. Thực thi Rollback thủ công từng bước (nếu cần chạy trực tiếp qua SSH)
1. **Bước 1 — Xuất bản chụp DB hiện tại (giữ dữ liệu phát sinh sau deploy):**
   ```bash
   (umask 077; wp db export - --add-drop-table --path=/var/www/PROD_DOMAIN/wordpress | \
     gzip -9 > /var/www/PROD_DOMAIN/backups/pre-rollback-$(date -u +%Y%m%d-%H%M%S).sql.gz)
   ```
2. **Bước 2 — Khôi phục bản chụp cơ sở dữ liệu trước khi deploy:**
   ```bash
   gzip -dc /var/www/PROD_DOMAIN/backups/pre-deploy.sql.gz | \
     wp db import - --path=/var/www/PROD_DOMAIN/wordpress
   ```
3. **Bước 3 — Khôi phục mã nguồn phiên bản trước (giữ nguyên ảnh `/media/`, `wp-config.php`, `.htaccess`; dấu `/` đầu neo đúng thư mục gốc):**
   ```bash
   rsync -a --delete --exclude='/media/' --exclude='/wp-config.php' --exclude='/.htaccess' \
     /var/www/PROD_DOMAIN/releases/previous/ \
     /var/www/PROD_DOMAIN/wordpress/
   ```
4. **Bước 4 — Xóa bộ nhớ đệm Object Cache, Rewrite Rules và WP Super Cache:**
   ```bash
   wp cache flush --path=/var/www/PROD_DOMAIN/wordpress
   wp rewrite flush --hard --path=/var/www/PROD_DOMAIN/wordpress
   rm -rf /var/www/PROD_DOMAIN/wordpress/app/cache/supercache/*
   ```
5. **Bước 5 — Xác nhận hệ thống đã trở lại bình thường:**
   ```bash
   curl -fsSI https://PROD_DOMAIN/coaching/ | head -n 10
   wp core verify-checksums --path=/var/www/PROD_DOMAIN/wordpress
   ```
