# Trùng lặp ý định & nội dung giữa các trang (local)

- Base: `http://localhost:8080` · Độ đo: cosine char 3-gram của skill (`cosine_counter`), ngưỡng cannibalization `0.84`
- `intent` = cosine char 3-gram của title + H1 + H2 (cờ ≥ 0.84)
- `shared` = tỷ lệ cụm 5 từ của trang ngắn hơn xuất hiện nguyên văn ở trang kia (cờ ≥ 0.5)

## Trang

| Path | Words | H1 | Placeholder còn hiển thị |
|---|---|---|---|
| `/` | 1427 | Khám Phá Sự Cân Bằng & Vẻ Đẹp Đích Thực | 0 |
| `/coaching/` | 1700 | Coaching & Đồng Hành Chuyên Sâu | 0 |
| `/my-story/` | 600 | Câu Chuyện Thương Hiệu & Vẻ Đẹp Đích Thực | 0 |
| `/course/` | 646 | Chương Trình Đào Tạo Thẩm Mỹ & Phun Xăm | 0 |
| `/client-success-story/` | 552 | Câu Chuyện Thành Công Học Viên & Khách Hàng | 0 |
| `/booking/` | 688 | Đăng Ký Tư Vấn & Xếp Lịch Coaching 1:1 | 0 |
| `/my-favorite-product/` | 103 | Sản Phẩm Yêu Thích Của Jannie | 0 |
| `/chinh-sach-bao-mat/` | 12 | Chính Sách Bảo Mật Dữ Liệu Cá Nhân | 0 |
| `/dieu-khoan-dich-vu/` | 13 | Điều Khoản Dịch Vụ & Quy Định Học Viện | 0 |

## Cặp trang (sắp theo intent)

| A | B | intent | shared | Cờ |
|---|---|---|---|---|
| `/` | `/coaching/` | 0.846 | 0.927 | ⚠️ trùng ý định · ⚠️ lặp nội dung |
| `/` | `/my-story/` | 0.738 | 0.923 | ⚠️ lặp nội dung |
| `/coaching/` | `/booking/` | 0.71 | 0.975 | ⚠️ lặp nội dung |
| `/coaching/` | `/my-story/` | 0.707 | 0.923 | ⚠️ lặp nội dung |
| `/coaching/` | `/course/` | 0.687 | 0.91 | ⚠️ lặp nội dung |
| `/` | `/course/` | 0.681 | 0.913 | ⚠️ lặp nội dung |
| `/` | `/client-success-story/` | 0.633 | 0.929 | ⚠️ lặp nội dung |
| `/coaching/` | `/client-success-story/` | 0.619 | 0.929 | ⚠️ lặp nội dung |
| `/` | `/booking/` | 0.598 | 0.504 | ⚠️ lặp nội dung |
| `/course/` | `/booking/` | 0.538 | 0.532 | ⚠️ lặp nội dung |
| `/my-story/` | `/booking/` | 0.528 | 0.575 | ⚠️ lặp nội dung |
| `/my-story/` | `/course/` | 0.522 | 0.575 | ⚠️ lặp nội dung |
| `/client-success-story/` | `/booking/` | 0.464 | 0.622 | ⚠️ lặp nội dung |
| `/my-story/` | `/client-success-story/` | 0.46 | 0.614 | ⚠️ lặp nội dung |
| `/course/` | `/client-success-story/` | 0.412 | 0.618 | ⚠️ lặp nội dung |

## H2 lặp lại trên nhiều trang

| H2 | Trang |
|---|---|
| Đăng Ký Tư Vấn & Xếp Lịch Coaching | `/`, `/coaching/`, `/my-story/`, `/course/`, `/client-success-story/`, `/booking/` |
| Hơn 20 Năm Đồng Hành Cùng Vẻ Đẹp Đích Thực | `/`, `/coaching/`, `/my-story/` |
| Cảm Nhận Học Viên & Kết Quả Thực Tế | `/`, `/coaching/`, `/client-success-story/` |
| Lợi Ích Của Chương Trình Coaching | `/`, `/coaching/`, `/course/` |
| Chi Tiết Gói Đào Tạo & Cố Vấn | `/`, `/coaching/` |
| Câu Hỏi Thường Gặp: Quy Trình Cố Vấn 1:1 Diễn Ra Như Thế Nào? | `/coaching/`, `/booking/` |
