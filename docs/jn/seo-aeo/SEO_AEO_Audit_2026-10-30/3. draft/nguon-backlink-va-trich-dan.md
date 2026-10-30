# Nguồn backlink & trích dẫn: hiện trạng `jannieedu.com` và shortlist cho site mới

- **Ngày:** 2026-10-30 · **Trạng thái:** BẢN NHÁP, chỉ nghiên cứu off-page; chưa liên hệ hay đăng ký nơi nào
- **Đi kèm:** bản đồ 301/410 và dữ liệu chọn domain ở `ban-do-301-jannieedu.md` (cùng thư mục)
- **Quy ước:** `[FACT]` có bằng chứng từ công cụ hoặc trang đã fetch · `[INFERENCE]` suy luận từ dữ liệu · `[ASSUMPTION]` giả định cần kiểm chứng · `[UNKNOWN]` chưa có dữ liệu

---

## 0. Tóm tắt nhanh

1. **Không có backlink HTML nào trỏ tới `jannieedu.com` được xác nhận** qua các kênh mở: skill `link-graph`, Wayback, Common Crawl, và fetch thử 3 bài báo `[FACT]`. Tool tìm được 9 "mention", nhưng toàn là hashtag mạng xã hội, trang render bằng JS hoặc spam.
2. **Đường dẫn đáng giữ nhất là `jannieedu.com/bio/`.** Snippet tìm kiếm của hồ sơ TikTok `@jannie_hongnhung` chứa link này. Chưa xác nhận được vì TikTok render bằng JS `[INFERENCE]`. Cần đổi bio sang domain production và 301 `/bio/`.
3. **Có một bài báo nhắc tên Jannie nhưng không đặt link.** Bài trên NTD Online (2021) nhắc "Jannie Hồng Nhung" và thương hiệu The Balance `[FACT]`. Đây là cơ hội outreach xin bổ sung link.
4. **Có tín hiệu bảo mật xấu.** Một trang spam ở Ý (`burgiomobili.it`) liệt kê URL `wp-login.php` của `jannieedu.com` kèm chuỗi giống thông tin đăng nhập `[FACT]`. Tài liệu này cố ý **không** chép lại các chuỗi đó. Xem khuyến nghị bảo mật ở `ban-do-301-jannieedu.md` §1.
5. **Catalog ~5.500 nguồn của skill khớp kém với ngành làm đẹp ở Việt Nam.** Catalog không có niche `beauty`; trong 431 nguồn có metadata chỉ 25 nguồn gắn quốc gia VN `[FACT]`. Trong top 30 tool trả về, **giữ 2, giữ có điều kiện 5, loại 23**. Shortlist cuối gồm 10 nguồn, chủ yếu là hồ sơ thương hiệu sẵn có và citation địa phương.

---

## 1. Backlink & mention hiện có tới `jannieedu.com`

### 1.1 Phương pháp và giới hạn

- **Skill `link-graph`:** mô tả là có "20 kênh". Thực tế chỉ kênh `DuckDuckGo_HTML_Live_SERP` trả dữ liệu (9 bản ghi); kênh Wayback trả `historical_urls_count: 0` `[FACT]`. Trình xác minh crawl-to-confirm của skill đánh dấu từng bản ghi: `VERIFIED_UNLINKED_BRAND_MENTION` (có nhắc tên, không có `<a href>`) hoặc `UNVERIFIABLE_JS_SHELL` (trang render bằng JS).
- **Common Crawl CDX:** chỉ cho biết các trang **trên** `jannieedu.com` đã được crawl, không cho biết trang nào trỏ tới nó. Muốn biết referring domain phải tải bộ host-level web graph nhiều GB, ngoài phạm vi phiên này `[FACT]`.
- **Fetch thủ công:** 3 bài báo mà trang chủ cũ trỏ ra (logo báo ở footer) đã được fetch thủ công và tìm `<a href>` tới `jannieedu.com` / `janniehongnhung.com`.
- **Bị chặn:**
  - DuckDuckGo HTML trả HTTP 202 (thử thách chống bot) cho 2 truy vấn thủ công, nên không lặp lại.
  - Wayback trả "Temporarily Offline" giữa phiên.
  - `baobinhphuoc.com.vn` có chứng chỉ TLS hết hạn.
  - `ngoisaonganhlamdep.vn` không phân giải DNS.
- **Không có:** dữ liệu Ahrefs, Majestic, Moz hay GSC `[UNKNOWN]`. Con số "DR 32" của skill là heuristic `[INFERENCE]`, không dùng để ra quyết định.

### 1.2 Danh sách

| # | Trang nguồn | Nội dung | Trạng thái xác minh | Mang equity cho URL cũ? |
|---|---|---|---|---|
| 1 | `https://www.tiktok.com/@jannie_hongnhung` | Snippet bio: "Tìm hiểu thêm về tôi tại đây 👇 https://jannieedu.com/bio/" | **Chưa xác nhận** (`UNVERIFIABLE_JS_SHELL`) | `/bio/`: có thể có, nhưng link hồ sơ mạng xã hội thường là nofollow `[INFERENCE]`. Giá trị chính là lượt truy cập. |
| 2 | `https://www.tiktok.com/tag/jannieedu` | Trang hashtag, snippet ghi "28.4K views" | Chưa xác nhận (JS) | Không có link, chỉ tín hiệu thương hiệu |
| 3 | `https://www.instagram.com/p/C4KYf9TPMUK/` | Caption của `jannie.hongnhung` (2024-03-05) có `#jannieedu.com` | Chưa xác nhận (JS) | Không, hashtag không phải link |
| 4 | `https://www.instagram.com/p/C4cC54Xv5mY/` | Caption (2024-03-12) có `#jannieedu.com` | Chưa xác nhận (JS) | Không |
| 5 | `https://www.facebook.com/janny.nguyen.35/videos/…/1447030753983611/` | Video gắn `#jannieedu` | Unlinked mention (không có `<a href>`) | Không |
| 6 | `https://www.facebook.com/janny.nguyen.35/videos/…/1745486643231278/` | Video gắn `#jannieedu #janniebeautyblog` | Unlinked mention | Không. Chưa rõ hồ sơ `janny.nguyen.35` có phải của khách hàng không `[UNKNOWN]`. |
| 7 | `https://www.youtube.com/watch?v=lco_vV8Gz-A` | Tiêu đề có `#jannieedu` | Unlinked mention | Không |
| 8 | `https://www.youtube.com/watch?v=GsnAbA3YzZw` | Tiêu đề `#shorts #janniehongnhung #jannieedu #haircut` | Unlinked mention | Không |
| 9 | `https://www.burgiomobili.it/15076-2/` | **Trang spam** liệt kê `jannieedu.com/wp-login.php` kèm chuỗi giống thông tin đăng nhập, và URL `/12816-2/` | Unlinked mention. **Độc hại, chỉ dùng làm tín hiệu bảo mật.** | Không. `/12816-2/` trên site cũ hiện trả 404 `[FACT]`. Vì không có link nên không cần disavow `[INFERENCE]`. |
| 10 | `http://www.nguoitieudungonline.com/2021/04/the-balance-su-khac-biet-tao-nen-thanh_23.html` | Bài "The Balance – sự khác biệt tạo nên thành công của một thương hiệu làm đẹp" nhắc "Jannie Hồng Nhung" | **Đã fetch: không có link** tới domain nào của Jannie `[FACT]` | Không. Đây là **cơ hội outreach**. |
| 11 | `https://baobinhphuoc.com.vn/news/4/40968/khoi-nghiep-bang-0-dong-la-ban-da-may-man` | Bài mà trang chủ cũ trỏ ra (logo "Bình Phước") | **Chưa fetch được**: TLS hết hạn, Wayback offline `[UNKNOWN]` | Chưa rõ |
| 12 | `https://ngoisaonganhlamdep.vn/su-kien-sao/Chuyen-gia-Nguyen-Hong-Nhung-…-Oscar-for-Beauty-2018-18794.html` | Bài mà trang chủ cũ trỏ ra. Slug nói về giải thưởng năm 2018; nội dung giải thưởng **chưa kiểm chứng**. | **Domain không phân giải DNS**, Wayback không có bản lưu của URL này `[UNKNOWN]` | Không, domain đã chết |

**Kết luận `[INFERENCE]`:** bài #10 và #12 có từ 2021 và 2018, trước khi `jannieedu.com` có nội dung (trang cũ đăng từ 2023-05), nên gần như chắc chắn không link tới domain này. Equity off-page của `jannieedu.com` chỉ nằm ở link trong bio mạng xã hội (`/bio/`, `/`). Các link đó do chủ dự án tự sửa được, không cần outreach.

### 1.3 Hồ sơ thương hiệu tìm được (từ trang `/bio/` cũ, bản lưu Wayback 2026-05-20)

Danh sách dùng để (a) sửa link sang domain production và (b) sau khi chủ dự án xác nhận, nạp vào option `jn_brand_same_as`. Option này đã có sẵn trong `class-jn-seo-brand.php` và hiện chưa được lấp đầy `[INFERENCE]`.

| Hồ sơ | Nhãn hiển thị trên `/bio/` cũ | Nhãn |
|---|---|---|
| `https://www.facebook.com/Janniehongnhung` | "Fanpage: Jannie Hồng Nhung" | `[FACT]` |
| `https://www.instagram.com/jannie.hongnhung/` | "Jannie Hồng Nhung" | `[FACT]` |
| `https://www.tiktok.com/@jannie_hongnhung` | "Giải đáp thắc mắc về tóc…" | `[FACT]` |
| `https://www.tiktok.com/@janniehongnhung_pmu` | "Chia sẻ phun thêu thẩm mỹ…" | `[FACT]` |
| `https://www.youtube.com/channel/UC-YwBxq5p71-UXWbcdNRUUQ` | "Jannie Beauty Blog" | `[FACT]` |
| `https://www.pinterest.com/JannieNguyen7788/` | "Jannie Beauty Blog" | `[FACT]` |
| `https://janniehongnhung.com` | "Blog cá nhân" (domain riêng, nên 301 về production) | `[FACT]` |
| Liên kết Zalo / WhatsApp | Có trên trang cũ. Số điện thoại cố ý **không** chép vào đây; NAP chính thức do chủ dự án cung cấp. | `[FACT]` |

Lệnh mẫu, **chưa chạy**, chỉ chạy sau khi chủ dự án xác nhận danh sách:
`wp option update jn_brand_same_as '["https://www.facebook.com/Janniehongnhung", "..."]' --format=json`

---

## 2. Shortlist nguồn backlink / citation cho site mới

### 2.1 Lệnh đã chạy

```bash
PAGER=cat python3 -I ~/.claude/skills/sunext-SEO-AEO/scripts/seo_os_cli.py backlink-sources \
  --desc "1:1 beauty, permanent makeup (phun xăm, điêu khắc) and spa business coaching & training in Ho Chi Minh City, Vietnam" \
  --country VN --lang vi --top-n 30
# chạy lần 2, thêm: --niches "healthcare,local,edu,creator,services"
```

- Tool tự nhận diện ngành là "Healthcare, Medical Clinics, Dental, Pharma, Wellness Spa & Aesthetics" (`healthcare_medical`) `[FACT]`. Nhận diện này **lệch**: Jannie làm coaching và đào tạo, không phải phòng khám.
- Catalog: 5.510 nguồn (61 VN/AI + 314 curated + 56 citation + 5.079 tài nguyên thô) `[FACT]`. Không có niche `beauty`; chỉ 25 nguồn gắn quốc gia `VN` `[FACT]`.
- Cột "DA" bên dưới là **giá trị tĩnh trong catalog**, không phải đo live `[INFERENCE]`. Ví dụ Google Business Profile có hai bản ghi, một ghi DA 95, một ghi DA 100.

### 2.2 Sàng lọc top 30 của tool (danh sách `top_vietnam_and_ai_ecosystem_sources`, lần chạy 1)

| # tool | Nguồn | DA (catalog) / loại link | Quyết định | Lý do |
|---|---|---|---|---|
| 1 | Pinterest Business Verified Merchant (`business.pinterest.com`) | 95 / dofollow (theo catalog) | **GIỮ** | Đã có tài khoản `JannieNguyen7788` `[FACT]`. Nội dung làm đẹp hợp với Pinterest. Nên verify domain. |
| 2 | Clutch.co | 89 | LOẠI | Danh bạ agency IT/B2B |
| 3 | Zenodo | 91 | LOẠI | Kho nghiên cứu (DOI). Đăng lên để lấy link là lạm dụng/spam. |
| 4 | OSF.io | 89 | LOẠI | như trên |
| 5 | HG.org | 79 | LOẠI | Danh bạ luật |
| 6 | Kompass | 82 | LOẠI | B2B công nghiệp |
| 7 | ArchDaily | 91 | LOẠI | Kiến trúc |
| 8 | DesignRush | 84 | LOẠI | Agency |
| 9 | Class Central | 84 | CÓ ĐIỀU KIỆN (thấp) | Chủ yếu tổng hợp khoá học online kiểu MOOC. Khoá 1:1 trực tiếp bằng tiếng Việt khó được nhận `[INFERENCE]`. |
| 10 | GoodFirms | 83 | LOẠI | B2B services |
| 11 | The Manifest | 82 | LOẠI | B2B |
| 12 | Sortlist | 79 | LOẠI | Agency, chủ yếu ở EU |
| 13 | Michelin Guide | 91 | LOẠI | Nhà hàng, khách sạn |
| 14 | WhatClinic | 77 / nofollow | CÓ ĐIỀU KIỆN | Catalog có gắn VN. Chỉ hợp nếu Jannie có studio làm dịch vụ phun xăm cho khách `[UNKNOWN]`. |
| 15 | Viblo Asia | 72 | LOẠI | Cộng đồng lập trình |
| 16 | **Trang Vàng Việt Nam** (`trangvangvietnam.com`) | 62 / dofollow (catalog) | **GIỮ** | Danh bạ doanh nghiệp Việt Nam, dùng làm citation NAP |
| 17 | RateMDs | 78 | LOẠI | Đánh giá bác sĩ, không có VN |
| 18 | TopDev | 68 | LOẠI | Công nghệ |
| 19 | npm | 95 | LOẠI | Lập trình |
| 20 | Crunchbase | 91 / nofollow | LOẠI | Đồ thị startup, ít liên quan |
| 21 | Chambers & Partners | 82 | LOẠI | Luật |
| 22 | Spiderum | 64 / dofollow | CÓ ĐIỀU KIỆN (thấp) | Cộng đồng tri thức Việt. Chỉ khi viết bài thật (ví dụ câu chuyện khởi nghiệp ngành phun xăm), không đăng bài chỉ để đặt link. |
| 23 | PyPI | 94 | LOẠI | Lập trình |
| 24 | Hugging Face | 93 | LOẠI | AI |
| 25 | WikiVoyage | 88 | LOẠI | Du lịch |
| 26 | F6S | 82 | LOẠI | Startup |
| 27 | Legal500 | 81 | LOẠI | Luật |
| 28 | Diễn Đàn Doanh Nghiệp (VCCI) | 76 / "free-tier" | CÓ ĐIỀU KIỆN | Báo doanh nghiệp, hợp góc "coaching kinh doanh spa". Nhiều khả năng là PR trả phí; link trả phí phải gắn `rel="sponsored"` `[INFERENCE]`. |
| 29 | TopCV | 74 / nofollow | CÓ ĐIỀU KIỆN (thấp) | Chỉ khi tuyển dụng (trang hồ sơ công ty) |
| 30 | Sitejabber | 85 | LOẠI | Review TMĐT, chủ yếu thị trường Mỹ |

**Từ danh sách citation và lần chạy 2** (chỉ ghi các nguồn có liên quan):

| Nguồn | Quyết định | Lý do |
|---|---|---|
| Google Business Profile | **GIỮ** (có điều kiện) | Nguồn citation địa phương quan trọng nhất. Cần địa chỉ thật hoặc khu vực phục vụ ở TP.HCM `[UNKNOWN: NAP]`. |
| Bing Places for Business | **GIỮ** | Phủ Bing. Theo tactic của tool, Bing cũng là nguồn cho ChatGPT Search `[INFERENCE]`. |
| Apple Business Connect | **GIỮ** (có điều kiện) | Apple Maps; cần địa chỉ |
| Facebook Business Page | **GIỮ** | Đã có fanpage `Janniehongnhung` `[FACT]` |
| Instagram | **GIỮ** | Đã có `jannie.hongnhung` `[FACT]` |
| YouTube | **GIỮ** | Đã có kênh "Jannie Beauty Blog" `[FACT]` |
| LinkedIn (Company / cá nhân) | CÓ ĐIỀU KIỆN | Hợp mảng coaching B2B cho chủ salon/spa; chưa thấy hồ sơ hiện có `[UNKNOWN]` |
| Foursquare | CÓ ĐIỀU KIỆN | Cần địa chỉ công khai |
| OpenStreetMap | CÓ ĐIỀU KIỆN | Chỉ khi có địa điểm thật kiểm chứng được tại chỗ |
| YellowPages.com.vn | CÓ ĐIỀU KIỆN | Danh bạ VN thứ hai (DA catalog 58). Chỉ dùng nếu đảm bảo NAP đồng nhất. |
| Trustpilot | LOẠI (tạm) | Ít phổ biến với khách hàng Việt `[ASSUMPTION]` |
| Healthgrades, ZocDoc, WebMD, RateMDs | LOẠI | Danh bạ bác sĩ ở Mỹ |
| BBB, Angi, Thumbtack, Houzz | LOẠI | Thị trường Mỹ, sai ngành |
| Booking.com, Airbnb, TripAdvisor, Expedia | LOẠI | Du lịch |
| Web 2.0 (Blogger, WordPress.com, Wix, Weebly, HubPages, Strikingly, Webflow, Notion, Medium, Substack) | LOẠI | Lập blog vệ tinh để lấy link là mô hình link scheme, giá trị thấp và có rủi ro. Medium/Substack chỉ cân nhắc khi có chiến lược nội dung biên tập thật. |
| Podcast (Apple, Spotify, Amazon, iHeart, Castbox…) | CÓ ĐIỀU KIỆN (tương lai) | Chỉ khi thực sự sản xuất podcast |
| Behance, Dribbble | LOẠI | Portfolio thiết kế |

### 2.3 Top 10 nguồn đề xuất (theo thứ tự ưu tiên)

| # | Nguồn | Việc cần làm | Đích trên site mới | Nhãn |
|---|---|---|---|---|
| 1 | TikTok `@jannie_hongnhung`, `@janniehongnhung_pmu` | Đổi link bio từ `jannieedu.com/bio/` sang domain production | `/` hoặc `/coaching/` | `[FACT]` hồ sơ có tồn tại; link `/bio/` mới thấy qua snippet |
| 2 | Google Business Profile | Tạo hoặc nhận hồ sơ với NAP chính thức, danh mục phù hợp, link website, link đặt lịch | `/`, `/booking/` | `[UNKNOWN]` NAP |
| 3 | Facebook Fanpage `Janniehongnhung` | Cập nhật trường Website và nút "Đặt lịch" | `/booking/` | `[FACT]` |
| 4 | Instagram `jannie.hongnhung` | Đổi link bio; dùng link highlight cho Coaching và Course | `/coaching/`, `/course/` | `[FACT]` |
| 5 | YouTube "Jannie Beauty Blog" | Thêm link trong phần giới thiệu kênh và mô tả các video nổi bật | `/coaching/`, `/course/` | `[FACT]` |
| 6 | Pinterest Business `JannieNguyen7788` | Verify domain; pin ảnh trước/sau (có đồng ý của khách) kèm link | `/client-success-story/` | `[FACT]` |
| 7 | Bing Places for Business | Nhập từ GBP sau khi GBP xong | `/` | `[INFERENCE]` |
| 8 | Apple Business Connect | Tạo hồ sơ nếu có địa chỉ | `/` | `[UNKNOWN]` địa chỉ |
| 9 | Trang Vàng Việt Nam | Tạo listing doanh nghiệp, NAP đồng nhất với GBP | `/` | `[INFERENCE]` |
| 10 | NTD Online (`nguoitieudungonline.com`) | Gửi email lịch sự xin thêm link vào bài 2021 đã nhắc "Jannie Hồng Nhung" | `/my-story/` | `[FACT]` mention đã xác nhận, không có link |

**Tầng tiếp theo:** LinkedIn, YellowPages.com.vn, Foursquare, Zalo Official Account, Diễn Đàn Doanh Nghiệp (PR có gắn nhãn), WhatClinic (nếu có studio), Spiderum (bài viết thật). Zalo OA không có trong catalog; đề xuất vì trang cũ đã dùng link Zalo `[INFERENCE]`.

**Không làm:** mua link, PBN, đăng bừa lên web 2.0, comment spam, lạm dụng kho nghiên cứu (Zenodo/OSF), hoặc tạo citation với NAP chưa thống nhất.

---

## 3. Thông tin chủ dự án cần cung cấp (`[UNKNOWN]`, không tự điền)

1. Domain production chính thức, và ai đang giữ tài khoản iNET (`jannieedu.com`) và P.A. Việt Nam (`janniehongnhung.com`).
2. NAP chính thức: tên doanh nghiệp hoặc thương hiệu hiển thị, địa chỉ hoặc khu vực phục vụ, số điện thoại, giờ làm việc. Phải giống nhau trên mọi citation.
3. Có studio nhận khách làm dịch vụ không, hay chỉ coaching và đào tạo? Câu trả lời quyết định việc dùng WhatClinic, GBP dạng có cửa hàng hay dạng khu vực phục vụ.
4. Hồ sơ mạng xã hội nào là chính thức (bảng §1.3), và hồ sơ cá nhân Facebook `janny.nguyen.35` có thuộc khách hàng không.
5. Dòng sản phẩm "The Balance" còn kinh doanh không. Câu trả lời ảnh hưởng nhóm C1 trong bản đồ 301.
6. Có file logo chính thức của các báo đã đưa tin không (liên quan ADR-018), và có bài báo nào khác đang trỏ tới domain cũ không.

---

## Counter-Evidence / Confidence

- **Counter-evidence:**
  - Không có dữ liệu Ahrefs/Majestic/GSC, nên có thể tồn tại backlink mà các kênh mở không thấy, ví dụ từ diễn đàn bị chặn hoặc trang chỉ index trên Google.
  - Snippet TikTok có thể đã cũ; bio hiện tại có thể đã đổi.
  - "28.4K views" của tag `#jannieedu` là số liệu trong snippet, không kiểm chứng được.
  - Kênh Wayback của skill trả 0 trong khi CDX trực tiếp trả 516 URL. Đây là dấu hiệu nhiều kênh "20-channel" của skill có thể thất bại im lặng.
  - Danh sách giữ/loại dựa trên mô tả trong catalog, chưa kiểm tra điều kiện đăng ký thực tế của từng nền tảng.
- **Confidence:**
  - "Không có backlink HTML xác nhận": **trung bình–cao** trong phạm vi kênh mở; **thấp** nếu xét toàn web.
  - Shortlist top 10: **cao** cho nhóm hồ sơ sẵn có (1, 3–6); **trung bình** cho nhóm citation (2, 7–9) vì phụ thuộc NAP; **trung bình** cho outreach NTD Online.
- **Falsification:** nếu backlink report của GSC (property `jannieedu.com`) hoặc của một tool trả phí cho thấy có referring domain biên tập, đưa các trang đích đó lên ưu tiên 301 và bổ sung vào §1.2.

## Sources

- Skill `link-graph`: `PAGER=cat python3 -I ~/.claude/skills/sunext-SEO-AEO/scripts/seo_os_cli.py link-graph --url https://jannieedu.com --desc "Jannie Nguyễn - 1:1 beauty, permanent makeup (phun xăm, điêu khắc) and spa business coaching & training in Ho Chi Minh City, Vietnam" --lang vi --country VN` (chạy từ scratch dir, 2026-10-30; output JSON gồm `external_backlink_and_mention_footprint` với 9 bản ghi)
- Skill `backlink-sources`, 2 lần chạy (lệnh ở §2.1); catalog `~/.claude/skills/sunext-SEO-AEO/data/curated_backlink_sources_5449.json` (đọc metadata `countries`/`niches`)
- Fetch xác minh:
  - `http://www.nguoitieudungonline.com/2021/04/the-balance-su-khac-biet-tao-nen-thanh_23.html` (200)
  - `https://baobinhphuoc.com.vn/news/4/40968/khoi-nghiep-bang-0-dong-la-ban-da-may-man` (TLS cert expired)
  - `https://ngoisaonganhlamdep.vn/su-kien-sao/Chuyen-gia-Nguyen-Hong-Nhung-doat-giai-thuong-Nghe-nhan-Ban-Tay-Vang-nganh-phun-xam-Viet-Nam-2018-tai-Oscar-for-Beauty-2018-18794.html` (DNS fail)
- Wayback: `https://web.archive.org/web/20260711000257id_/https://jannieedu.com/` (outbound link tới báo), `https://web.archive.org/web/20260520131058id_/https://jannieedu.com/bio/` (danh sách hồ sơ mạng xã hội)
- Wayback CDX cho bài báo: `https://web.archive.org/cdx/search/cdx?url=ngoisaonganhlamdep.vn/su-kien-sao/Chuyen-gia-Nguyen-Hong-Nhung&matchType=prefix` (rỗng); `…url=baobinhphuoc.com.vn/news/4/40968/…` (503 Temporarily Offline)
- DuckDuckGo HTML: `https://html.duckduckgo.com/html/` với `q="jannieedu.com" -site:jannieedu.com` và `q="janniehongnhung.com"` (HTTP 202, chống bot, không có kết quả)
- Common Crawl: `https://index.commoncrawl.org/CC-MAIN-2025-47-index?url=jannieedu.com/*&output=json`, `…/CC-MAIN-2026-12-index…` (chỉ là URL index; không có dữ liệu referring page)
- Live check: `curl -k https://jannieedu.com/12816-2/` (404)
- Code site mới (chỉ đọc): `wordpress/app/mu-plugins/jn-performance/class-jn-seo-brand.php` (option `jn_brand_same_as`), `class-jn-seo-schema.php` (đưa `sameAs` vào Organization/Person)
