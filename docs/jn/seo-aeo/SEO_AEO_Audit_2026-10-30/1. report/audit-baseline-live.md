# Kết quả kiểm toán SEO/AEO tự động (local)

- Base: `http://localhost:8080` · Thời điểm: `2026-10-30T10:32:35+0700` · robots: `live`
- Engine: skill `sunext-semrush-ahrefs-os` (`audit_single_page`, `compute_bilingual_readability`, `audit_robots_txt_for_ai_crawlers`, `compute_autogeo_geu_score`) + JN consistency checks
- Tổng hợp: CRITICAL **0** · HIGH **0** · MEDIUM **1** · LOW **11**
- sitemap.xml: HTTP 200 (9 URL) · llms.txt: HTTP 200

## Trang

| Path | Policy | HTTP | Title (len) | Desc len | robots | H1 | Words | JSON-LD types | GEU | Issues |
|---|---|---|---|---|---|---|---|---|---|---|
| `/` | index | 200 | 55 | 155 | index, follow, max-snippet:-1, max-image-preview:large | 1 | 1581 | Organization, Person, Service, WebPage, WebSite | 12.0 | 1 |
| `/coaching/` | index | 200 | 51 | 154 | index, follow, max-snippet:-1, max-image-preview:large | 1 | 1851 | BreadcrumbList, FAQPage, Organization, Person, Service, WebPage, WebSite | 12.0 | 1 |
| `/my-story/` | index | 200 | 50 | 140 | index, follow, max-snippet:-1, max-image-preview:large | 1 | 816 | BreadcrumbList, Organization, Person, ProfilePage, WebSite | 12.0 | 1 |
| `/course/` | index | 200 | 58 | 160 | index, follow, max-snippet:-1, max-image-preview:large | 1 | 841 | BreadcrumbList, Organization, Person, WebPage, WebSite | 12.0 | 1 |
| `/client-success-story/` | index | 200 | 51 | 151 | index, follow, max-snippet:-1, max-image-preview:large | 1 | 750 | BreadcrumbList, Organization, Person, WebPage, WebSite | 12.0 | 1 |
| `/booking/` | index | 200 | 51 | 133 | index, follow, max-snippet:-1, max-image-preview:large | 1 | 840 | BreadcrumbList, FAQPage, Organization, Person, Service, WebPage, WebSite | 12.0 | 1 |
| `/my-favorite-product/` | index | 200 | 48 | 143 | index, follow, max-snippet:-1, max-image-preview:large | 1 | 256 | BreadcrumbList, CollectionPage, Organization, Person, WebSite | 12.0 | 1 |
| `/chinh-sach-bao-mat/` | index | 200 | 44 | 125 | index, follow, max-snippet:-1, max-image-preview:large | 1 | 293 | BreadcrumbList, Organization, Person, WebPage, WebSite | 12.0 | 1 |
| `/dieu-khoan-dich-vu/` | index | 200 | 54 | 118 | index, follow, max-snippet:-1, max-image-preview:large | 1 | 288 | BreadcrumbList, Organization, Person, WebPage, WebSite | 12.0 | 1 |
| `/cart/` | noindex | 200 | 32 | 110 | noindex, follow | 1 | 172 | BreadcrumbList, Organization, Person, WebPage, WebSite | 12.0 | 2 |
| `/tai-khoan/` | noindex | 200 | 47 | 119 | noindex, follow | 1 | 286 | BreadcrumbList, Organization, Person, WebPage, WebSite | 12.0 | 0 |
| `/?s=coaching` | noindex | 200 | 43 | 74 | noindex, follow | 1 | 457 | Organization, Person, SearchResultsPage, WebSite | 12.0 | 1 |

## Vấn đề (sắp theo mức độ)

| Mức | Rule | Vấn đề | URL | Bằng chứng |
|---|---|---|---|---|
| MEDIUM | `AUD-CNT-01` | Thin Content Page (172 words < 250 words threshold) | http://localhost:8080/cart/ | Extracted 172 words (including SPA hydration) |
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
