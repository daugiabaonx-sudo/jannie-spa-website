# Báo Cáo Lighthouse CI Bước 8 — Tối Ưu Hóa PageSpeed Theo Ngân Sách (§5)

- **Ngày thực hiện:** `2026-10-28`
- **Cấu hình kiểm thử:** [`lighthouserc.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/lighthouserc.json) & [`scripts/run-step-8-lhci.mjs`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/scripts/run-step-8-lhci.mjs) (`3` lượt chạy mỗi URL cho từng chế độ Mobile PSI-equivalent throttling & Desktop, lấy giá trị trung vị `median`).
- **Tệp dữ liệu tổng hợp:** [`docs/jn/lighthouse/2026-10-28/summary.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/summary.json)

---

## 1. Bảng So Sánh Trước / Sau Tối Ưu (Before / After — Mobile PSI Throttling)

| Trang | Trước Bước 8 (Baseline: Perf / A11y / BP / SEO — FCP / LCP / TBT / CLS) | Sau Bước 8 (Median 3 Runs: Perf / A11y / BP / SEO — FCP / LCP / TBT / CLS) | Ngân sách §5 | Trạng thái |
| :--- | :--- | :--- | :--- | :--- |
| `/` | `93 / 100 / 100 / 61` — FCP `1664ms`, LCP `2714ms`, TBT `0ms`, CLS `0` | **`100 / 100 / 100 / 100`** — FCP `933ms`, LCP `1884ms`, TBT `0ms`, CLS `0` | Perf `>=95`, 100/100/100, FCP `<=1500ms`, LCP `<=2000ms` | **PASS** |
| `/coaching/` | `93 / 100 / 100 / 61` — FCP `1664ms`, LCP `2714ms`, TBT `0ms`, CLS `0` | **`100 / 100 / 100 / 100`** — FCP `795ms`, LCP `1733ms`, TBT `0ms`, CLS `0` | Perf `>=95`, 100/100/100, FCP `<=1500ms`, LCP `<=2000ms` | **PASS** |
| `/my-story/` | `94 / 100 / 100 / 61` — FCP `1664ms`, LCP `2565ms`, TBT `0ms`, CLS `0` | **`100 / 100 / 100 / 100`** — FCP `787ms`, LCP `1732ms`, TBT `0ms`, CLS `0` | Perf `>=95`, 100/100/100, FCP `<=1500ms`, LCP `<=2000ms` | **PASS** |
| `/course/` | `94 / 100 / 100 / 61` — FCP `1664ms`, LCP `2565ms`, TBT `0ms`, CLS `0` | **`100 / 100 / 100 / 100`** — FCP `789ms`, LCP `1658ms`, TBT `0ms`, CLS `0` | Perf `>=95`, 100/100/100, FCP `<=1500ms`, LCP `<=2000ms` | **PASS** |
| `/client-success-story/` | `94 / 100 / 100 / 61` — FCP `1664ms`, LCP `2565ms`, TBT `0ms`, CLS `0` | **`100 / 100 / 100 / 100`** — FCP `926ms`, LCP `1733ms`, TBT `0ms`, CLS `0` | Perf `>=95`, 100/100/100, FCP `<=1500ms`, LCP `<=2000ms` | **PASS** |
| `/booking/` | `94 / 100 / 100 / 61` — FCP `1664ms`, LCP `2565ms`, TBT `0ms`, CLS `0` | **`100 / 100 / 100 / 100`** — FCP `927ms`, LCP `1660ms`, TBT `0ms`, CLS `0.0321` | Perf `>=95`, 100/100/100, FCP `<=1500ms`, LCP `<=2000ms` | **PASS** |
| `/my-favorite-product/` | `96 / 100 / 100 / 58` — FCP `1818ms`, LCP `2568ms`, TBT `0ms`, CLS `0.001` | **`99 / 100 / 100 / 100`** — FCP `1360ms`, LCP `1960ms`, TBT `0ms`, CLS `0` | Perf `>=90`, 100/100/100, FCP `<=1500ms`, LCP `<=2000ms` | **PASS** |
| `/product/so-tay-dinh-huong-su-nghiep/` | `91 / 100 / 96 / 58` — FCP `1818ms`, LCP `2712ms`, TBT `0ms`, CLS `0` | **`100 / 100 / 100 / 100`** — FCP `800ms`, LCP `1806ms`, TBT `0ms`, CLS `0` | Perf `>=90`, 100/100/100, FCP `<=1500ms`, LCP `<=2000ms` | **PASS** |
| `/cart/` | `96 / 97 / 100 / 61` — FCP `1073ms`, LCP `1073ms`, TBT `144ms`, CLS `0` | **`100 / 100 / 100 / 100`** — FCP `1072ms`, LCP `1072ms`, TBT `82ms`, CLS `0` | Perf `>=85`, 100/100/100, TBT `<=100ms`, LCP `<=2000ms` | **PASS** |
| `/checkout/` | `100 / 100 / 100 / 61` — FCP `949ms`, LCP `960ms`, TBT `148ms`, CLS `0` | **`100 / 100 / 100 / 100`** — FCP `1028ms`, LCP `1028ms`, TBT `83ms`, CLS `0` | Perf `>=85`, 100/100/100, TBT `<=100ms`, LCP `<=2000ms` | **PASS** |
| `/tai-khoan/` | `92 / 96 / 96 / 61` — FCP `1660ms`, LCP `2560ms`, TBT `0ms`, CLS `0.033` | **`100 / 100 / 100 / 100`** — FCP `926ms`, LCP `1530ms`, TBT `0ms`, CLS `0` | Perf `>=85`, 100/100/100, FCP `<=1500ms`, LCP `<=2000ms` | **PASS** |

---

## 2. Kết Quả Chi Tiết Mobile (PSI-Equivalent Throttling — Median 3 Runs)

| Đường dẫn | Perf | A11y | BP | SEO | FCP | LCP | TBT | CLS | TTFB | Fonts | JS (KB) | CSS (KB) | Ảnh lớn nhất (KB) | Woo Assets | Báo cáo JSON |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :--- |
| `/` | **100** | **100** | **100** | **100** | `933 ms` | `1884 ms` | `0 ms` | `0` | `12 ms` | `3` | `15.3 KB` | `18.3 KB` | `13.1 KB` | `0` | [`home-mobile.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/home-mobile.json) |
| `/coaching/` | **100** | **100** | **100** | **100** | `795 ms` | `1733 ms` | `0 ms` | `0` | `10 ms` | `3` | `15.3 KB` | `18.3 KB` | `9.4 KB` | `0` | [`coaching-mobile.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/coaching-mobile.json) |
| `/my-story/` | **100** | **100** | **100** | **100** | `787 ms` | `1732 ms` | `0 ms` | `0` | `9 ms` | `3` | `15.3 KB` | `18.3 KB` | `13.1 KB` | `0` | [`my-story-mobile.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/my-story-mobile.json) |
| `/course/` | **100** | **100** | **100** | **100** | `789 ms` | `1658 ms` | `0 ms` | `0` | `11 ms` | `3` | `15.3 KB` | `18.3 KB` | `0 KB` | `0` | [`course-mobile.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/course-mobile.json) |
| `/client-success-story/` | **100** | **100** | **100** | **100** | `926 ms` | `1733 ms` | `0 ms` | `0` | `9 ms` | `3` | `15.3 KB` | `18.3 KB` | `2.5 KB` | `0` | [`client-success-story-mobile.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/client-success-story-mobile.json) |
| `/booking/` | **100** | **100** | **100** | **100** | `927 ms` | `1660 ms` | `0 ms` | `0.0321` | `11 ms` | `3` | `15.3 KB` | `18.3 KB` | `0 KB` | `0` | [`booking-mobile.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/booking-mobile.json) |
| `/my-favorite-product/` | **99** | **100** | **100** | **100** | `1360 ms` | `1960 ms` | `0 ms` | `0` | `12 ms` | `3` | `42.3 KB` | `36.7 KB` | `5.1 KB` | `9` | [`my-favorite-product-mobile.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/my-favorite-product-mobile.json) |
| `/product/so-tay-dinh-huong-su-nghiep/` | **100** | **100** | **100** | **100** | `800 ms` | `1806 ms` | `0 ms` | `0` | `12 ms` | `3` | `61.8 KB` | `31.1 KB` | `5.1 KB` | `8` | [`product-so-tay-dinh-huong-su-nghiep-mobile.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/product-so-tay-dinh-huong-su-nghiep-mobile.json) |
| `/cart/` | **100** | **100** | **100** | **100** | `1072 ms` | `1072 ms` | `82 ms` | `0` | `234 ms` | `3` | `0 KB (cached)` | `0 KB (cached)` | `0 KB` | `44` | [`cart-mobile.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/cart-mobile.json) |
| `/checkout/` | **100** | **100** | **100** | **100** | `1028 ms` | `1028 ms` | `83 ms` | `0` | `190 ms` | `3` | `0 KB (cached)` | `0 KB (cached)` | `0 KB` | `45` | [`checkout-mobile.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/checkout-mobile.json) |
| `/tai-khoan/` | **100** | **100** | **100** | **100** | `926 ms` | `1530 ms` | `0 ms` | `0` | `214 ms` | `3` | `56.0 KB` | `31.1 KB` | `0 KB` | `6` | [`tai-khoan-mobile.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/tai-khoan-mobile.json) |

---

## 3. Kết Quả Chi Tiết Desktop (`--preset=desktop` — Median 3 Runs)

| Đường dẫn | Perf | A11y | BP | SEO | FCP | LCP | TBT | CLS | TTFB | Fonts | JS (KB) | CSS (KB) | Ảnh lớn nhất (KB) | Woo Assets | Báo cáo JSON |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :--- |
| `/` | **100** | **100** | **100** | **100** | `254 ms` | `430 ms` | `0 ms` | `0` | `12 ms` | `3` | `15.3 KB` | `18.3 KB` | `13.1 KB` | `0` | [`home-desktop.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/home-desktop.json) |
| `/coaching/` | **100** | **100** | **100** | **100** | `253 ms` | `427 ms` | `0 ms` | `0` | `10 ms` | `3` | `15.3 KB` | `18.3 KB` | `13.1 KB` | `0` | [`coaching-desktop.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/coaching-desktop.json) |
| `/my-story/` | **100** | **100** | **100** | **100** | `213 ms` | `410 ms` | `0 ms` | `0` | `10 ms` | `3` | `15.3 KB` | `18.3 KB` | `13.1 KB` | `0` | [`my-story-desktop.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/my-story-desktop.json) |
| `/course/` | **100** | **100** | **100** | **100** | `255 ms` | `410 ms` | `0 ms` | `0` | `13 ms` | `3` | `15.3 KB` | `18.3 KB` | `0 KB` | `0` | [`course-desktop.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/course-desktop.json) |
| `/client-success-story/` | **100** | **100** | **100** | **100** | `253 ms` | `429 ms` | `0 ms` | `0` | `11 ms` | `3` | `15.3 KB` | `18.3 KB` | `2.5 KB` | `0` | [`client-success-story-desktop.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/client-success-story-desktop.json) |
| `/booking/` | **100** | **100** | **100** | **100** | `213 ms` | `410 ms` | `0 ms` | `0` | `10 ms` | `3` | `15.3 KB` | `18.3 KB` | `0 KB` | `0` | [`booking-desktop.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/booking-desktop.json) |
| `/my-favorite-product/` | **100** | **100** | **100** | **100** | `373 ms` | `483 ms` | `0 ms` | `0.0003` | `13 ms` | `3` | `42.3 KB` | `36.7 KB` | `6.9 KB` | `9` | [`my-favorite-product-desktop.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/my-favorite-product-desktop.json) |
| `/product/so-tay-dinh-huong-su-nghiep/` | **100** | **100** | **100** | **100** | `214 ms` | `452 ms` | `0 ms` | `0.0001` | `10 ms` | `3` | `61.8 KB` | `31.1 KB` | `5.1 KB` | `8` | [`product-so-tay-dinh-huong-su-nghiep-desktop.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/product-so-tay-dinh-huong-su-nghiep-desktop.json) |
| `/cart/` | **100** | **100** | **100** | **100** | `286 ms` | `286 ms` | `2 ms` | `0.0131` | `173 ms` | `3` | `0 KB (cached)` | `0 KB (cached)` | `0 KB` | `44` | [`cart-desktop.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/cart-desktop.json) |
| `/checkout/` | **100** | **100** | **100** | **100** | `276 ms` | `382 ms` | `5 ms` | `0.0063` | `263 ms` | `3` | `0 KB (cached)` | `0 KB (cached)` | `0 KB` | `45` | [`checkout-desktop.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/checkout-desktop.json) |
| `/tai-khoan/` | **100** | **100** | **100** | **100** | `209 ms` | `372 ms` | `0 ms` | `0` | `176 ms` | `3` | `56.0 KB` | `31.1 KB` | `0 KB` | `6` | [`tai-khoan-desktop.json`](file:///Users/daugiabao/Documents/SUNEXT/Spa/Website/docs/jn/lighthouse/2026-10-28/tai-khoan-desktop.json) |
