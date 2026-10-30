# JN Project Rules — Jannie Nguyen website on WordPress. Read and apply in every conversation.

## 1. Sources and language
- Design truth: ./stitch_jannie_nguyen_website_wireframe/ (code.html, DESIGN.md, screen.png) and docs/jn/wireframe-spec.md.
- WordPress root: ./wordpress (7.1.3). Owner inputs: PROJECT CONTEXT CARD at the end of this file. UNKNOWN → clearly labelled placeholder + entry in docs/jn/PROGRESS.md; never invent it.
- Artifacts (plans, task lists, walkthroughs, docs) in Vietnamese. Code, identifiers, code comments and commit messages in English. Site copy in Vietnamese, verbatim from the wireframe where it exists.
- This file is more specific than the other workspace rules and wins on conflict. PHP follows the WordPress Coding Standards (PHPCS + WPCS), not PSR-12.

## 2. Fixed stack (hard)
- Theme: Blocksy (free, wordpress.org) + custom child theme. No Blocksy Pro. Blocksy Companion only if a required free feature lives there (record why).
- Layout: Gutenberg core only: core blocks, patterns, synced patterns, block-style variations, plus the project blocks jn/icon, jn/rating, jn/booking-form. Forbidden: Elementor and every other page builder; third-party block libraries (Spectra, Kadence Blocks, Stackable, GenerateBlocks, Otter…); Custom HTML blocks or shortcodes for layout.
- Commerce: WooCommerce with HPOS and the Cart/Checkout/My Account blocks.
- Booking + customer management: custom plugin jn-core (feature folders booking/, customers/, testimonials/, blocks/) on top of WooCommerce customers and orders. Presentation in the child theme, features in jn-core, site-wide policies in the mu-plugins jn-hardening.php and jn-performance.php.
- Approved third-party plugins: WooCommerce; WPS Hide Login; Two Factor; one page-cache plugin matching PROD_WEB_SERVER (LiteSpeed Cache on LiteSpeed, otherwise WP Super Cache); one lightweight SEO plugin (The SEO Framework or Slim SEO); FluentSMTP (production mail); an official payment-gateway plugin only once the owner provides merchant credentials. Dev-only tools that never ship (Docker images, Playwright, Lighthouse CI, fontTools, npx serve, PHPUnit, PHPCS) are allowed; list them in docs/jn/stack.md. Anything else — plugins, paid products, external services, CDNs — needs my approval first.

## 3. Engineering (hard)
- Never edit WordPress core files, the parent theme or third-party plugins.
- Before relying on any WordPress, WooCommerce or Blocksy hook, filter, constant, block attribute or setting, confirm it exists in the installed source (grep) or the admin UI.
- Front-end JS: vanilla, deferred, enqueued only where used; no jQuery in project code; no inline event handlers. Never: Tailwind CDN, Google Fonts CDN, icon fonts.
- Block editor scripts: plain JS using the wp.* globals and ServerSideRender, no build step, so jn-core ships without node_modules. If a block truly needs a build, use @wordpress/scripts, commit only build/, and ask me first. node_modules lives only at the workspace root for dev tools and is never deployed.
- Security: sanitise input, escape output, nonce or signed token + capability check on every state change, $wpdb->prepare for SQL, WooCommerce CRUD only (HPOS-safe); no secrets in git, artifacts or chat; no personal data in logs or URLs.
- Tests: jn-core logic and security checks get automated tests; critical flows get Playwright E2E; visual work is verified with screenshots, axe and Lighthouse.

## 4. Design corrections (verified 2026-10-27, apply everywhere)
- Headings use HEADING_FONT: Bodoni Moda has no Vietnamese glyphs (U+1EA0–U+1EF1 missing). If the card names a font without full Vietnamese coverage, show the specimen evidence and ask before using it. Body text uses Geist. Fonts are self-hosted WOFF2, latin + Vietnamese merged into one file per style, font-display: swap with metric-matched fallbacks.
- Contrast (WCAG AA): champagne #AC8E76 as text on light surfaces fails (2.66–3.05:1). Use #7E6754 for small accent text on light surfaces (4.64–5.31:1) and #A0846E for large display accents ≥ 24 px (3.05–3.49:1). Keep #AC8E76 for decoration, for backgrounds with #1F1E1D text (5.46:1) and as text on #1F1E1D (5.46:1). Never put light text on #AC8E76 (2.88:1). Muted #7A7268 fails on beige/cream (4.14–4.47:1): use #716A61 for muted text (4.66–5.33:1).
- Icons: inline SVG of the 19 Material Symbols Outlined icons in use (Apache-2.0), never the icon font.
- Where DESIGN.md prose and code.html/screen.png disagree, follow code.html/screen.png, except for a visible 2 px focus ring, which DESIGN.md requires.

## 5. Performance budget (hard; Lighthouse mobile with PSI settings; numbers only from saved JSON reports)
- Content pages (home, coaching, my-story, course, client-success-story, booking): Performance ≥ 95 (aim 100), desktop ≥ 98; Accessibility, Best Practices and SEO = 100; LCP ≤ 2.0 s, TBT ≤ 100 ms, CLS ≤ 0.05, FCP ≤ 1.5 s.
- Shop/product: Performance ≥ 90. Cart/checkout/my-account: ≥ 85. Accessibility/Best Practices/SEO 100 unless a documented WooCommerce-core exception.
- Per content page: ≤ 3 font files; JS ≤ 60 KB and CSS ≤ 50 KB compressed in total; LCP image ≤ 150 KB; no render-blocking third-party request; no WooCommerce assets unless the page needs them.

## 6. Hide-WordPress policy (hard)
- Public HTML, headers, files and endpoints must not reveal WordPress defaults (checklist + scanner in Step 7). Install-time measures (renamed content/plugin/upload dirs, random table prefix, custom login slug, custom REST prefix) happen in Step 1.
- Never rename the parent theme or third-party plugin folders on disk (that breaks updates). Path aliasing only through URL filters + server rewrites, and only if every regression test passes.
- When obscurity conflicts with real security or updatability, keep security and updates.

## 7. Workflow (process)
- One step per conversation. Start by reading this file, docs/jn/PROGRESS.md and docs/jn/wireframe-spec.md.
- Plan first: give the step's Implementation Plan + Task List, then wait for my approval before changing files.
- Prove, don't assume: verify with WP-CLI, curl, browser screenshots (375/768/1024/1440), tests and Lighthouse. Say plainly when something could not be verified.
- Close every step with: git commit + tag jn-step-N; DB snapshot backups/jn-step-N.sql.gz (git-ignored); PROGRESS.md update (done, decisions, versions, open UNKNOWNs); a Walkthrough with evidence (files changed, key command output, screenshots, test and Lighthouse results).
- If acceptance still fails after 3 focused fix attempts: stop and report root cause + options.
- Ask first before: installing anything outside §2, changing the approved design beyond §4, deleting anything that is not project-owned, any action on a remote server, DNS or payment provider, sending email to real addresses.
- When critical input is missing: mark it UNKNOWN, continue with what is safe, and end with exactly one question — the one that unblocks the most.

PROJECT CONTEXT CARD (write UNKNOWN for anything not known yet)
- PROD_DOMAIN: UNKNOWN
- PROD_WEB_SERVER + PHP: UNKNOWN
- ADMIN_EMAIL: admin@example.com
- LOGIN_SLUG: <WP_LOGIN_SLUG>
- DIR NAMES: content = app; plugins = modules; uploads = media; REST prefix = api
- HEADING_FONT: Playfair Display
- BRAND CONTACT: email contact@jannienguyen.vn; phone/Zalo UNKNOWN; address "TP. Hồ Chí Minh, Việt Nam" (full address UNKNOWN); hours "Thứ Hai - Thứ Bảy: 09:00 - 18:00"
- PACKAGE PRICES (VND): 30 phút UNKNOWN; 60 phút UNKNOWN; Master Mentorship 3 tháng UNKNOWN
- PAYMENT POLICY: pay after the assistant confirms the slot (payment link by email)
- PAYMENT METHODS: bank transfer (bank, account number, holder UNKNOWN)
- BOOKING MODEL: request-based, as in the wireframe
- AUTO-CREATE CUSTOMER ACCOUNT ON BOOKING: no
- MY FAVORITE PRODUCT: 6 products from the old jannieedu.com page (owner instruction 2026-10-30, ADR-022); prices = May 2024 archive, owner to confirm (UNK-15)
- COURSES: 26 courses from the old jannieedu.com /course/ page (owner instruction 2026-10-30, ADR-022); no prices, consultation booking only
- REAL PHOTOS: UNKNOWN
- STAFF ACCOUNTS: UNKNOWN
- PRODUCTION SMTP: UNKNOWN
- TRACKING / CHAT WIDGETS: none
