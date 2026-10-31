/**
 * JN — Step 18 (ADR-025): verify /my-favorite-product/ (the owner's "Nâng cấp My Favorite Product" design) in
 * headless Chrome over CDP.
 *
 * 1. Design source: the archived mockup matches its sha256; every page string and the six card teasers are
 *    verbatim from it (case aside) and visible on the live page.
 * 2. 320/375/768/1024/1440/1920: no horizontal overflow, the hero H1 is painted at once (never hidden for a
 *    reveal), six cards in 3/2/1 columns, LCP element; full-page screenshots at 375, 768 and 1440.
 * 3. Keyboard: a consult button opens the dialog with the product message, focus moves in, Escape closes it
 *    and focus returns; copying reports a status; the Zalo link is Jannie's.
 * 4. The marquee's pause button stops every decorative loop (aria-pressed); reduced motion stops them all.
 * 5. axe-core WCAG 2.2 AA (reduced motion, so scroll-reveal content is visible) at 375 and 1440, dialog open too.
 * 6. No console errors.
 *
 * Usage: node scripts/verify-step-18-favorite-products.mjs   (AXE_PATH=/path/to/axe.min.js to override)
 */

import { spawn } from 'node:child_process';
import crypto from 'node:crypto';
import fs from 'node:fs';
import { createRequire } from 'node:module';
import os from 'node:os';
import path from 'node:path';

const CHROME_BIN = process.env.CHROME_BIN || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const BASE_URL = process.env.JN_BASE_URL || 'http://localhost:8080';
// axe-core is not a repo dependency: AXE_PATH, then a resolvable `axe-core`, then the npx cache Step 9 used.
const AXE_PATH = process.env.AXE_PATH || (() => {
  try {
    return createRequire(import.meta.url).resolve('axe-core/axe.min.js');
  } catch {
    return path.join(os.homedir(), '.npm/_npx/0f94ee7615faf582/node_modules/axe-core/axe.min.js');
  }
})();
const ROOT_DIR = process.cwd();
const OUT_DIR = path.join(ROOT_DIR, 'docs', 'jn', 'step-18');
const REPORT_PATH = path.join(OUT_DIR, 'verification.json');
const MOCKUP_PATH = path.join(ROOT_DIR, 'docs', 'jn', 'favorite-products', 'mockup-2026-10-31.html');
const MOCKUP_SHA256 = '4ed06038d48bf55a54a2b14018780cb58004822ec611c886cd88e7ddfa003a68';
const CONTENT_PATH = path.join(ROOT_DIR, 'scripts', 'lib', 'jn-legacy-content.json');
const PAGE = '/my-favorite-product/';
const ZALO = 'https://zalo.me/0559912642';
const DEBUG_PORT = 9348;

const VIEWPORTS = [320, 375, 768, 1024, 1440, 1920];
const SCREENSHOT_VIEWPORTS = [375, 768, 1440];
const AXE_VIEWPORTS = [375, 1440];
const PAGE_STRINGS = [
  'The Jannie Edit · 2026',
  'Vẻ đẹp, rất riêng.',
  'Không cần quá nhiều. Chỉ cần những điều thực sự xứng đáng. Khám phá 6 sản phẩm được Jannie yêu thích và tuyển chọn.',
  'Khám Phá Bộ Sưu Tập',
  'Câu Chuyện Của Jannie',
  'The Curated Collection',
  'Sắc Xuân Mới – Bộ Sản Phẩm Yêu Thích Của Jannie',
  'Beauty Is Personal',
  'Bạn xứng đáng với những điều tinh tế nhất.',
  'Vẻ đẹp không nằm ở số lượng sản phẩm, mà ở cách mỗi lựa chọn khiến bạn yêu thương chính mình hơn.',
  'Kết Nối Với Jannie',
  'Curated with love',
  'Beauty is a ritual',
  'The Jannie Selection',
  'Little moments of luxury',
];

fs.mkdirSync(OUT_DIR, { recursive: true });

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

/** Visible text normalised like jn_lt_text() (scripts/lib/jn-legacy-test-kit.php), lower-cased. */
function normText(html) {
  return html
    .replace(/<(script|style)\b[^>]*>[\s\S]*?<\/\1>/gi, ' ')
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;|&#160;/g, ' ')
    .replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;|&#8220;|&#8221;/g, '"').replace(/&#039;|&#8217;/g, "'")
    .replace(/[“”″]/g, '"').replace(/[‘’]/g, "'").replace(/[–—]/g, '-')
    .replace(/\s+/g, ' ')
    .trim()
    .toLowerCase();
}

class CDPClient {
  constructor(wsUrl) {
    this.ws = new WebSocket(wsUrl);
    this.id = 0;
    this.pending = new Map();
    this.listeners = [];
  }
  async connect() {
    await new Promise((resolve, reject) => {
      this.ws.addEventListener('open', resolve, { once: true });
      this.ws.addEventListener('error', reject, { once: true });
    });
    this.ws.addEventListener('message', (ev) => {
      const msg = JSON.parse(ev.data);
      if (msg.id && this.pending.has(msg.id)) {
        const { resolve, reject } = this.pending.get(msg.id);
        this.pending.delete(msg.id);
        if (msg.error) reject(new Error(JSON.stringify(msg.error)));
        else resolve(msg.result);
      } else if (msg.method) {
        this.listeners.forEach((fn) => fn(msg));
      }
    });
  }
  on(fn) {
    this.listeners.push(fn);
  }
  send(method, params = {}) {
    const id = ++this.id;
    return new Promise((resolve, reject) => {
      this.pending.set(id, { resolve, reject });
      this.ws.send(JSON.stringify({ id, method, params }));
    });
  }
  async eval(expression) {
    const res = await this.send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (res?.exceptionDetails) {
      throw new Error(`Eval error: ${JSON.stringify(res.exceptionDetails)}`);
    }
    return res?.result?.value;
  }
  // `text` makes the browser treat it as typed (Enter needs "\r" to activate a button).
  async key(key, code, keyCode, text = undefined) {
    await this.send('Input.dispatchKeyEvent', { type: 'keyDown', key, code, windowsVirtualKeyCode: keyCode, text });
    await this.send('Input.dispatchKeyEvent', { type: 'keyUp', key, code, windowsVirtualKeyCode: keyCode });
  }
  close() {
    this.ws.close();
  }
}

async function open(cdp, vw) {
  await cdp.send('Emulation.setDeviceMetricsOverride', { width: vw, height: 900, deviceScaleFactor: 1, mobile: vw < 768 });
  await cdp.send('Page.navigate', { url: `${BASE_URL}${PAGE}` });
  await sleep(1800);
}

// Runs in the page right after load: first-frame hero state, layout and LCP.
const MEASURE = `(async () => {
  const lcp = await new Promise((resolve) => {
    let last = null;
    new PerformanceObserver((l) => { const e = l.getEntries(); last = e[e.length - 1]; }).observe({ type: 'largest-contentful-paint', buffered: true });
    setTimeout(() => resolve(last ? { tag: last.element ? last.element.tagName + '.' + String(last.element.className).split(' ')[0] : '?', ms: Math.round(last.startTime) } : null), 300);
  });
  const h1 = document.querySelector('h1');
  const cards = [...document.querySelectorAll('.jn-fav-products .wc-block-product')];
  const tops = new Set(cards.map((c) => Math.round(c.getBoundingClientRect().top)));
  const firstRow = cards.filter((c) => Math.round(c.getBoundingClientRect().top) === Math.round(cards[0].getBoundingClientRect().top)).length;
  return {
    scrollWidth: document.documentElement.scrollWidth,
    innerWidth,
    h1Text: h1 ? h1.textContent.trim() : '',
    h1Opacity: h1 ? getComputedStyle(h1).opacity : '0',
    h1Hidden: h1 ? !!h1.closest('.jn-reveal') : true,
    h1Top: h1 ? Math.round(h1.getBoundingClientRect().top) : -1,
    cards: cards.length,
    columns: firstRow,
    rows: tops.size,
    stage: !!document.querySelector('.jn-product-stage[role="img"]'),
    lcp,
  };
})()`;

function checkLayout(m, vw) {
  const failures = [];
  const columns = vw >= 1024 ? 3 : vw >= 640 ? 2 : 1;
  if (m.scrollWidth > m.innerWidth) failures.push(`horizontal overflow ${m.scrollWidth} > ${m.innerWidth}`);
  if (m.h1Text !== 'Vẻ đẹp, rất riêng.') failures.push(`H1 "${m.h1Text}"`);
  if (m.h1Opacity !== '1' || m.h1Hidden) failures.push('hero H1 hidden on the first frame');
  if (m.h1Top < 0 || m.h1Top > 900) failures.push(`H1 outside the first viewport (${m.h1Top}px)`);
  if (m.cards !== 6) failures.push(`${m.cards} cards`);
  if (m.columns !== columns) failures.push(`${m.columns} columns, expected ${columns}`);
  if (!m.stage) failures.push('no product stage');
  return failures;
}

async function fullShot(cdp, file) {
  await cdp.eval(`(async () => {
    document.querySelectorAll('.jn-reveal').forEach((el) => el.classList.add('is-revealed'));
    for (let y = 0; y < document.body.scrollHeight; y += 600) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 40)); }
    window.scrollTo(0, 0);
    return true;
  })()`);
  await sleep(700);
  const height = await cdp.eval('Math.ceil(document.documentElement.scrollHeight)');
  const width = await cdp.eval('document.documentElement.clientWidth');
  const shot = await cdp.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip: { x: 0, y: 0, width, height, scale: 1 } });
  fs.writeFileSync(file, Buffer.from(shot.data, 'base64'));
}

async function axe(cdp) {
  if (!fs.existsSync(AXE_PATH)) return { skipped: `axe-core not found at ${AXE_PATH}` };
  await cdp.eval(fs.readFileSync(AXE_PATH, 'utf8') + ';true');
  return cdp.eval(`axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'] } })
    .then((r) => ({ violations: r.violations.map((v) => v.id + ' (' + v.nodes.length + '): ' + v.nodes.slice(0, 2).map((n) => n.target.join(' ')).join(' | ')) }))`);
}

// Keyboard round trip on the dialog from the soap card (no price) and the first card.
async function checkDialog(cdp) {
  const failures = [];
  for (const index of [0, 3]) {
    const state = await cdp.eval(`(async () => {
      const btn = document.querySelectorAll('.jn-product-actions__btn[data-jn-consult]')[${index}];
      btn.scrollIntoView({ block: 'center' });
      btn.focus();
      return { product: btn.getAttribute('data-jn-product'), price: btn.getAttribute('data-jn-price') };
    })()`);
    await cdp.key('Enter', 'Enter', 13, '\r');
    await sleep(500);
    const opened = await cdp.eval(`(() => {
      const d = document.getElementById('jn-consult');
      return { open: d.open, focusIn: d.contains(document.activeElement), text: document.getElementById('jn-consult-text').value, zalo: (d.querySelector('a[href*="zalo.me"]') || {}).href || '' };
    })()`);
    if (!opened.open) failures.push(`card ${index + 1}: Enter did not open the dialog`);
    if (!opened.focusIn) failures.push(`card ${index + 1}: focus did not move into the dialog`);
    if (!opened.text.includes(state.product) || !opened.text.includes(state.price)) failures.push(`card ${index + 1}: message lacks product/price`);
    if (opened.zalo !== ZALO) failures.push(`card ${index + 1}: Zalo link ${opened.zalo}`);
    if (index === 0) {
      await cdp.eval(`document.querySelector('[data-jn-consult-copy]').click(); true`);
      await sleep(400);
      const status = await cdp.eval(`document.querySelector('.jn-consult__status').textContent.trim()`);
      if (!status) failures.push('copy button reported nothing');
    }
    await cdp.key('Escape', 'Escape', 27);
    await sleep(400);
    const closed = await cdp.eval(`(() => ({ open: document.getElementById('jn-consult').open, back: document.activeElement === document.querySelectorAll('.jn-product-actions__btn[data-jn-consult]')[${index}] }))()`);
    if (closed.open) failures.push(`card ${index + 1}: Escape did not close the dialog`);
    if (!closed.back) failures.push(`card ${index + 1}: focus did not return to the button`);
  }
  return failures;
}

const MOTION_STATE = `(() => {
  const anim = (sel) => { const el = document.querySelector(sel); const cs = el ? getComputedStyle(el) : null; return cs ? cs.animationName + '/' + cs.animationPlayState : 'missing'; };
  const toggle = document.querySelector('.jn-marquee__toggle');
  return {
    marquee: anim('.jn-marquee__track'),
    orbit: anim('.jn-product-stage__orbit'),
    float: anim('.jn-product-stage__products'),
    toggleDisplay: toggle ? getComputedStyle(toggle).display : 'missing',
    pressed: toggle ? toggle.getAttribute('aria-pressed') : 'missing',
    paused: document.documentElement.classList.contains('jn-fav-motion-paused'),
  };
})()`;

async function run() {
  const report = { generatedAt: new Date().toISOString(), baseUrl: BASE_URL, results: [] };
  let failed = 0;
  const record = (check, viewport, failures, data) => {
    failed += failures.length ? 1 : 0;
    report.results.push({ check, viewport, failures, data });
    console.log(`${failures.length ? '[FAIL]' : '[OK]  '} ${String(viewport).padStart(4)} ${check}${failures.length ? '  — ' + failures.join('; ') : ''}`);
  };

  // 1. Design source (no browser needed).
  const mockup = fs.readFileSync(MOCKUP_PATH, 'utf8');
  const sha = crypto.createHash('sha256').update(fs.readFileSync(MOCKUP_PATH)).digest('hex');
  record('archived mockup sha256', '-', sha === MOCKUP_SHA256 ? [] : [sha], { sha });
  const content = JSON.parse(fs.readFileSync(CONTENT_PATH, 'utf8'));
  const teasers = content.products.items.map((i) => i.teaser || '');
  const expected = [...PAGE_STRINGS, ...teasers];
  const mockText = normText(mockup);
  const notInMockup = expected.filter((s) => !mockText.includes(normText(s)));
  record(`${expected.length} page strings + teasers are verbatim from the mockup`, '-', notInMockup, {});
  const live = normText(await (await fetch(`${BASE_URL}${PAGE}`)).text());
  const notLive = expected.filter((s) => !live.includes(normText(s)));
  record(`${expected.length} strings are on the live page`, '-', notLive, {});

  const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), 'jn-step-18-'));
  const chrome = spawn(
    CHROME_BIN,
    ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', `--remote-debugging-port=${DEBUG_PORT}`, `--user-data-dir=${userDataDir}`, 'about:blank'],
    { stdio: 'ignore' }
  );
  let cdp = null;
  const consoleErrors = [];

  try {
    let targets = [];
    for (let i = 0; i < 30 && !targets.some((t) => t.type === 'page'); i++) {
      try {
        targets = await (await fetch(`http://127.0.0.1:${DEBUG_PORT}/json/list`)).json();
      } catch {
        await sleep(200);
      }
    }
    const pageTarget = targets.find((t) => t.type === 'page');
    if (!pageTarget) throw new Error('Chrome did not expose a page target');
    cdp = new CDPClient(pageTarget.webSocketDebuggerUrl);
    await cdp.connect();
    cdp.on((msg) => {
      if (msg.method === 'Runtime.exceptionThrown') consoleErrors.push(msg.params.exceptionDetails.text);
      if (msg.method === 'Runtime.consoleAPICalled' && msg.params.type === 'error') consoleErrors.push(msg.params.args.map((a) => a.value).join(' '));
      if (msg.method === 'Log.entryAdded' && msg.params.entry.level === 'error') consoleErrors.push(`${msg.params.entry.text} ${msg.params.entry.url || ''}`);
    });
    await cdp.send('Page.enable');
    await cdp.send('Runtime.enable');
    await cdp.send('Log.enable');
    await cdp.send('Browser.grantPermissions', { permissions: ['clipboardReadWrite', 'clipboardSanitizedWrite'], origin: BASE_URL }).catch(() => {});

    // 2. Viewports.
    for (const vw of VIEWPORTS) {
      await open(cdp, vw);
      const m = await cdp.eval(MEASURE);
      record(`layout, first frame (LCP ${m.lcp ? `${m.lcp.tag} ${m.lcp.ms}ms` : 'n/a'})`, vw, checkLayout(m, vw), m);
      if (SCREENSHOT_VIEWPORTS.includes(vw)) {
        await fullShot(cdp, path.join(OUT_DIR, `favorite-products-${vw}.png`));
      }
    }

    // 3. Keyboard + dialog, 4. pause button (normal motion).
    for (const vw of [375, 1440]) {
      await open(cdp, vw);
      record('keyboard: open dialog, copy, Escape, focus returns', vw, await checkDialog(cdp), {});
    }
    await open(cdp, 1440);
    const before = await cdp.eval(MOTION_STATE);
    await cdp.eval(`document.querySelector('.jn-marquee__toggle').focus(); true`);
    await cdp.key('Enter', 'Enter', 13, '\r');
    await sleep(200);
    const paused = await cdp.eval(MOTION_STATE);
    const pauseFailures = [];
    if (!before.marquee.startsWith('jn-fav-marquee/running')) pauseFailures.push(`marquee not running at first (${before.marquee})`);
    if (!paused.paused || paused.pressed !== 'true') pauseFailures.push('button did not switch to paused');
    for (const k of ['marquee', 'orbit', 'float']) {
      if (!paused[k].endsWith('/paused')) pauseFailures.push(`${k} still ${paused[k]}`);
    }
    record('pause button stops every decorative loop (WCAG 2.2.2)', 1440, pauseFailures, { before, paused });
    await cdp.eval(`(() => { const d = document.getElementById('jn-consult'); document.querySelector('.jn-product-actions__cta').click(); return d.open; })()`);
    await sleep(500);
    const dialogShot = await cdp.send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync(path.join(OUT_DIR, 'favorite-products-1440-dialog.png'), Buffer.from(dialogShot.data, 'base64'));

    // 4b + 5. Reduced motion: loops stop; axe with every section visible.
    await cdp.send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
    for (const vw of AXE_VIEWPORTS) {
      await open(cdp, vw);
      if (vw === 1440) {
        const m = await cdp.eval(MOTION_STATE);
        const f = [];
        for (const k of ['marquee', 'orbit', 'float']) {
          if (!m[k].startsWith('none/')) f.push(`${k} ${m[k]}`);
        }
        if (m.toggleDisplay !== 'none') f.push('pause button shown with nothing to pause');
        record('reduced motion: no decorative loop', vw, f, m);
      }
      const a = await axe(cdp);
      record('axe WCAG 2.2 AA', vw, a.skipped ? [a.skipped] : a.violations, a);
    }
    await cdp.eval(`document.querySelector('.jn-product-actions__cta').click(); true`);
    await sleep(400);
    const ad = await axe(cdp);
    record('axe WCAG 2.2 AA (dialog open)', 1440, ad.skipped ? [ad.skipped] : ad.violations, ad);

    // 6. Console.
    record('no console errors', '-', consoleErrors, { consoleErrors });
  } finally {
    if (cdp) cdp.close();
    chrome.kill();
    await sleep(300);
    fs.rmSync(userDataDir, { recursive: true, force: true });
  }

  report.summary = { checks: report.results.length, failed };
  fs.writeFileSync(REPORT_PATH, JSON.stringify(report, null, 2));
  console.log(`\nRESULT: ${report.results.length - failed} passed, ${failed} failed — report: ${path.relative(ROOT_DIR, REPORT_PATH)}`);
  process.exitCode = failed ? 1 : 0;
}

run().catch((err) => {
  console.error(err);
  process.exitCode = 1;
});
