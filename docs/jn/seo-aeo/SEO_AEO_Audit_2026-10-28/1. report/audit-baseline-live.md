# Kết quả kiểm toán SEO/AEO tự động (local)

- Base: `http://localhost:8080` · Thời điểm: `2026-10-28T10:20:11+0700` · robots: `live`
- Engine: skill `sunext-semrush-ahrefs-os` (`audit_single_page`, `compute_bilingual_readability`, `audit_robots_txt_for_ai_crawlers`, `compute_autogeo_geu_score`) + JN consistency checks
- Tổng hợp: CRITICAL **12** · HIGH **5** · MEDIUM **23** · LOW **20**
- sitemap.xml: HTTP 200 (9 URL) · llms.txt: HTTP 404

## Trang

| Path | Policy | HTTP | Title (len) | Desc len | robots | H1 | Words | JSON-LD types | GEU | Issues |
|---|---|---|---|---|---|---|---|---|---|---|
| `/` | index | 200 | 55 | 155 | index, follow | 1 | 1575 | Organization, Person, ProfessionalService, Service, WebPage, WebSite | 12.0 | 2 |
| `/coaching/` | index | 200 | 67 | 154 | index, follow | 1 | 1558 | Organization, Person, ProfessionalService, Service, WebPage, WebSite | 12.0 | 5 |
| `/my-story/` | index | 200 | 66 | 140 | index, follow | 1 | 809 | Organization, Person, ProfessionalService, WebPage, WebSite | 12.0 | 5 |
| `/course/` | index | 200 | 74 | 160 | index, follow | 1 | 828 | Organization, Person, ProfessionalService, WebPage, WebSite | 12.0 | 5 |
| `/client-success-story/` | index | 200 | 67 | 151 | index, follow | 1 | 756 | Organization, Person, ProfessionalService, WebPage, WebSite | 12.0 | 5 |
| `/booking/` | index | 200 | 67 | 133 | index, follow | 1 | 543 | Organization, Person, ProfessionalService, Service, WebPage, WebSite | 12.0 | 5 |
| `/my-favorite-product/` | index | 200 | 64 | 143 | index, follow | 2 | 262 | CollectionPage, Organization, Person, ProfessionalService, WebPage, WebSite | 12.0 | 5 |
| `/chinh-sach-bao-mat/` | index | 200 | 60 | 125 | index, follow | 1 | 296 | Organization, Person, ProfessionalService, WebPage, WebSite | 12.0 | 4 |
| `/dieu-khoan-dich-vu/` | index | 200 | 70 | 118 | index, follow | 1 | 291 | Organization, Person, ProfessionalService, WebPage, WebSite | 12.0 | 5 |
| `/cart/` | noindex | 200 | 48 | 110 | index, follow | 1 | 175 | Organization, Person, ProfessionalService, WebPage, WebSite | 12.0 | 7 |
| `/tai-khoan/` | noindex | 200 | 63 | 119 | index, follow | 1 | 277 | Organization, Person, ProfessionalService, WebPage, WebSite | 12.0 | 5 |
| `/?s=coaching` | noindex | 200 | 71 | 0 | noindex, nofollow | 1 | 476 | CollectionPage, Organization, Person, ProfessionalService, SearchResultsPage, WebPage, WebSite | 12.0 | 5 |

## Vấn đề (sắp theo mức độ)

| Mức | Rule | Vấn đề | URL | Bằng chứng |
|---|---|---|---|---|
| CRITICAL | `JN-SCH-06` | Schema telephone not shown anywhere on the page (unverified NAP data) | http://localhost:8080/ | telephone=+84908789866 |
| CRITICAL | `JN-SCH-06` | Schema telephone not shown anywhere on the page (unverified NAP data) | http://localhost:8080/coaching/ | telephone=+84908789866 |
| CRITICAL | `JN-SCH-06` | Schema telephone not shown anywhere on the page (unverified NAP data) | http://localhost:8080/my-story/ | telephone=+84908789866 |
| CRITICAL | `JN-SCH-06` | Schema telephone not shown anywhere on the page (unverified NAP data) | http://localhost:8080/course/ | telephone=+84908789866 |
| CRITICAL | `JN-SCH-06` | Schema telephone not shown anywhere on the page (unverified NAP data) | http://localhost:8080/client-success-story/ | telephone=+84908789866 |
| CRITICAL | `JN-SCH-06` | Schema telephone not shown anywhere on the page (unverified NAP data) | http://localhost:8080/booking/ | telephone=+84908789866 |
| CRITICAL | `JN-SCH-06` | Schema telephone not shown anywhere on the page (unverified NAP data) | http://localhost:8080/my-favorite-product/ | telephone=+84908789866 |
| CRITICAL | `JN-SCH-06` | Schema telephone not shown anywhere on the page (unverified NAP data) | http://localhost:8080/chinh-sach-bao-mat/ | telephone=+84908789866 |
| CRITICAL | `JN-SCH-06` | Schema telephone not shown anywhere on the page (unverified NAP data) | http://localhost:8080/dieu-khoan-dich-vu/ | telephone=+84908789866 |
| CRITICAL | `JN-SCH-06` | Schema telephone not shown anywhere on the page (unverified NAP data) | http://localhost:8080/cart/ | telephone=+84908789866 |
| CRITICAL | `JN-SCH-06` | Schema telephone not shown anywhere on the page (unverified NAP data) | http://localhost:8080/tai-khoan/ | telephone=+84908789866 |
| CRITICAL | `JN-SCH-06` | Schema telephone not shown anywhere on the page (unverified NAP data) | http://localhost:8080/?s=coaching | telephone=+84908789866 |
| HIGH | `AUD-META-03` | Missing Meta Description | http://localhost:8080/?s=coaching | Meta description length = 0 chars |
| HIGH | `JN-CAN-02` | Duplicate canonical tags (2) | http://localhost:8080/cart/ | multiple <link rel=canonical> |
| HIGH | `JN-CAN-02` | Duplicate canonical tags (2) | http://localhost:8080/tai-khoan/ | multiple <link rel=canonical> |
| HIGH | `JN-IDX-02` | Utility/search page is indexable | http://localhost:8080/cart/ | robots='index, follow' |
| HIGH | `JN-IDX-02` | Utility/search page is indexable | http://localhost:8080/tai-khoan/ | robots='index, follow' |
| MEDIUM | `AUD-CNT-01` | Thin Content Page (175 words < 250 words threshold) | http://localhost:8080/cart/ | Extracted 175 words (including SPA hydration) |
| MEDIUM | `AUD-HDG-02` | Multiple <h1> Headings (2 found) | http://localhost:8080/my-favorite-product/ | H1s: ['My Favorite Product', 'Sản Phẩm Yêu Thích Của Jannie'] |
| MEDIUM | `AUD-META-02` | Sub-optimal <title> Length (67 chars; optimal: 30-60 chars) | http://localhost:8080/coaching/ | Current Title: 'Coaching & Đồng Hành Chuyên Sâu 1:1 \| Jannie Nguyễn - JANNIE NGUYEN' |
| MEDIUM | `AUD-META-02` | Sub-optimal <title> Length (66 chars; optimal: 30-60 chars) | http://localhost:8080/my-story/ | Current Title: 'Hành Trình Hơn 20 Năm Của Chuyên Gia Jannie Nguyễn - JANNIE NGUYEN' |
| MEDIUM | `AUD-META-02` | Sub-optimal <title> Length (74 chars; optimal: 30-60 chars) | http://localhost:8080/course/ | Current Title: 'Chương Trình Đào Tạo & Khóa Học Chuyên Sâu \| Jannie Nguyễn - JANNIE NGUYEN' |
| MEDIUM | `AUD-META-02` | Sub-optimal <title> Length (67 chars; optimal: 30-60 chars) | http://localhost:8080/client-success-story/ | Current Title: 'Cảm Nhận Học Viên & Kết Quả Thực Tế \| Jannie Nguyễn - JANNIE NGUYEN' |
| MEDIUM | `AUD-META-02` | Sub-optimal <title> Length (67 chars; optimal: 30-60 chars) | http://localhost:8080/booking/ | Current Title: 'Đặt Lịch Tư Vấn 1:1 Cùng Chuyên Gia \| Jannie Nguyễn - JANNIE NGUYEN' |
| MEDIUM | `AUD-META-02` | Sub-optimal <title> Length (70 chars; optimal: 30-60 chars) | http://localhost:8080/dieu-khoan-dich-vu/ | Current Title: 'Điều Khoản Dịch Vụ & Chính Sách Cố Vấn \| Jannie Nguyễn - JANNIE NGUYEN' |
| MEDIUM | `AUD-META-02` | Sub-optimal <title> Length (71 chars; optimal: 30-60 chars) | http://localhost:8080/?s=coaching | Current Title: 'Jannie Nguyễn — Cố Vấn 1:1 & Đào Tạo Thẩm Mỹ Chuyên Sâu - JANNIE NGUYEN' |
| MEDIUM | `JN-AEO-01` | /llms.txt not served | http://localhost:8080/llms.txt | HTTP 404 |
| MEDIUM | `JN-META-05` | Duplicate meta description tags (2) | http://localhost:8080/cart/ | multiple <meta name=description> |
| MEDIUM | `JN-META-05` | Duplicate meta description tags (2) | http://localhost:8080/tai-khoan/ | multiple <meta name=description> |
| MEDIUM | `JN-META-06` | Brand repeated in <title> | http://localhost:8080/coaching/ | Coaching & Đồng Hành Chuyên Sâu 1:1 \| Jannie Nguyễn - JANNIE NGUYEN |
| MEDIUM | `JN-META-06` | Brand repeated in <title> | http://localhost:8080/my-story/ | Hành Trình Hơn 20 Năm Của Chuyên Gia Jannie Nguyễn - JANNIE NGUYEN |
| MEDIUM | `JN-META-06` | Brand repeated in <title> | http://localhost:8080/course/ | Chương Trình Đào Tạo & Khóa Học Chuyên Sâu \| Jannie Nguyễn - JANNIE NGUYEN |
| MEDIUM | `JN-META-06` | Brand repeated in <title> | http://localhost:8080/client-success-story/ | Cảm Nhận Học Viên & Kết Quả Thực Tế \| Jannie Nguyễn - JANNIE NGUYEN |
| MEDIUM | `JN-META-06` | Brand repeated in <title> | http://localhost:8080/booking/ | Đặt Lịch Tư Vấn 1:1 Cùng Chuyên Gia \| Jannie Nguyễn - JANNIE NGUYEN |
| MEDIUM | `JN-META-06` | Brand repeated in <title> | http://localhost:8080/my-favorite-product/ | Sản Phẩm Yêu Thích & Khuyên Dùng \| Jannie Nguyễn - JANNIE NGUYEN |
| MEDIUM | `JN-META-06` | Brand repeated in <title> | http://localhost:8080/chinh-sach-bao-mat/ | Chính Sách Bảo Mật Thông Tin \| Jannie Nguyễn - JANNIE NGUYEN |
| MEDIUM | `JN-META-06` | Brand repeated in <title> | http://localhost:8080/dieu-khoan-dich-vu/ | Điều Khoản Dịch Vụ & Chính Sách Cố Vấn \| Jannie Nguyễn - JANNIE NGUYEN |
| MEDIUM | `JN-META-06` | Brand repeated in <title> | http://localhost:8080/cart/ | Giỏ Hàng Của Bạn \| Jannie Nguyễn - JANNIE NGUYEN |
| MEDIUM | `JN-META-06` | Brand repeated in <title> | http://localhost:8080/tai-khoan/ | Tài Khoản Học Viên & Khách Hàng \| Jannie Nguyễn - JANNIE NGUYEN |
| MEDIUM | `JN-META-06` | Brand repeated in <title> | http://localhost:8080/?s=coaching | Jannie Nguyễn — Cố Vấn 1:1 & Đào Tạo Thẩm Mỹ Chuyên Sâu - JANNIE NGUYEN |
| LOW | `AUD-SEC-01` | Missing HTTP Security Headers (1 headers) | http://localhost:8080/ | Missing: content-security-policy |
| LOW | `AUD-SEC-01` | Missing HTTP Security Headers (1 headers) | http://localhost:8080/coaching/ | Missing: content-security-policy |
| LOW | `AUD-SEC-01` | Missing HTTP Security Headers (1 headers) | http://localhost:8080/my-story/ | Missing: content-security-policy |
| LOW | `AUD-SEC-01` | Missing HTTP Security Headers (1 headers) | http://localhost:8080/course/ | Missing: content-security-policy |
| LOW | `AUD-SEC-01` | Missing HTTP Security Headers (1 headers) | http://localhost:8080/client-success-story/ | Missing: content-security-policy |
| LOW | `AUD-SEC-01` | Missing HTTP Security Headers (1 headers) | http://localhost:8080/booking/ | Missing: content-security-policy |
| LOW | `AUD-SEC-01` | Missing HTTP Security Headers (1 headers) | http://localhost:8080/my-favorite-product/ | Missing: content-security-policy |
| LOW | `AUD-SEC-01` | Missing HTTP Security Headers (1 headers) | http://localhost:8080/chinh-sach-bao-mat/ | Missing: content-security-policy |
| LOW | `AUD-SEC-01` | Missing HTTP Security Headers (1 headers) | http://localhost:8080/dieu-khoan-dich-vu/ | Missing: content-security-policy |
| LOW | `AUD-SEC-01` | Missing HTTP Security Headers (1 headers) | http://localhost:8080/cart/ | Missing: content-security-policy |
| LOW | `AUD-SEC-01` | Missing HTTP Security Headers (1 headers) | http://localhost:8080/?s=coaching | Missing: content-security-policy |
| LOW | `JN-AEO-02` | No FAQPage / question-answer blocks on any page | http://localhost:8080 | 0 FAQPage nodes |
| LOW | `JN-SCH-05` | No BreadcrumbList on inner page | http://localhost:8080/coaching/ | types=['Organization', 'Person', 'ProfessionalService', 'Service', 'WebPage', 'WebSite'] |
| LOW | `JN-SCH-05` | No BreadcrumbList on inner page | http://localhost:8080/my-story/ | types=['Organization', 'Person', 'ProfessionalService', 'WebPage', 'WebSite'] |
| LOW | `JN-SCH-05` | No BreadcrumbList on inner page | http://localhost:8080/course/ | types=['Organization', 'Person', 'ProfessionalService', 'WebPage', 'WebSite'] |
| LOW | `JN-SCH-05` | No BreadcrumbList on inner page | http://localhost:8080/client-success-story/ | types=['Organization', 'Person', 'ProfessionalService', 'WebPage', 'WebSite'] |
| LOW | `JN-SCH-05` | No BreadcrumbList on inner page | http://localhost:8080/booking/ | types=['Organization', 'Person', 'ProfessionalService', 'Service', 'WebPage', 'WebSite'] |
| LOW | `JN-SCH-05` | No BreadcrumbList on inner page | http://localhost:8080/my-favorite-product/ | types=['CollectionPage', 'Organization', 'Person', 'ProfessionalService', 'WebPage', 'WebSite'] |
| LOW | `JN-SCH-05` | No BreadcrumbList on inner page | http://localhost:8080/chinh-sach-bao-mat/ | types=['Organization', 'Person', 'ProfessionalService', 'WebPage', 'WebSite'] |
| LOW | `JN-SCH-05` | No BreadcrumbList on inner page | http://localhost:8080/dieu-khoan-dich-vu/ | types=['Organization', 'Person', 'ProfessionalService', 'WebPage', 'WebSite'] |

## Quyền truy cập bot AI (robots.txt)

| Bot | Trạng thái |
|---|---|
| GPTBot | BLOCKED (Disallow: / for *) |
| OAI-SearchBot | BLOCKED (Disallow: / for *) |
| ChatGPT-User | BLOCKED (Disallow: / for *) |
| ClaudeBot | BLOCKED (Disallow: / for *) |
| Claude-Web | BLOCKED (Disallow: / for *) |
| anthropic-ai | BLOCKED (Disallow: / for *) |
| PerplexityBot | BLOCKED (Disallow: / for *) |
| Google-Extended | BLOCKED (Disallow: / for *) |
| GoogleOther | BLOCKED (Disallow: / for *) |
| Applebot-Extended | BLOCKED (Disallow: / for *) |
| Bytespider | BLOCKED (Disallow: / for *) |
| CCBot | BLOCKED (Disallow: / for *) |
| Meta-ExternalAgent | BLOCKED (Disallow: / for *) |
| Amazonbot | BLOCKED (Disallow: / for *) |
