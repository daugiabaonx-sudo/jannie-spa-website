#!/usr/bin/env node
/**
 * JN STEP 13 — Page header typography audit.
 * Loads every page-level header (hero + page intros, the legal pages and the 404) at 320/375/768/1024/1440px
 * and checks, per page:
 *   - no title segment (ink statement / accent italic) ends on a single-word line,
 *   - the accent italic starts its own line and closes the title (ADR-014),
 *   - eyebrow → title gap is 16px and title → lead gap is 24px,
 *   - the title lines up with the header container (shop page included),
 *   - the lead stays within 40rem, colours match the role tokens, no horizontal overflow.
 * Writes docs/jn/step-13-verification.json and header screenshots to docs/jn/step-13-headers/.
 *
 * Run: node scripts/verify-step-13-headers.mjs   (exit 1 on any failed check)
 */
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const CHROME_BIN = process.env.CHROME_BIN || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const BASE_URL = process.env.JN_BASE_URL || 'http://localhost:8080';
const ROOT_DIR = process.cwd();
const OUT_DIR = path.join(ROOT_DIR, 'docs', 'jn', 'step-13-headers');
const REPORT_PATH = path.join(ROOT_DIR, 'docs', 'jn', 'step-13-verification.json');
const DEBUG_PORT = 9343;

const PAGES = [
  '/',
  '/my-story/',
  '/coaching/',
  '/course/',
  '/client-success-story/',
  '/my-favorite-product/',
  '/booking/',
  '/chinh-sach-bao-mat/',
  '/dieu-khoan-dich-vu/',
  '/jn-step-13-missing-page/', // renders the 404 pattern
];
const PLACEHOLDER_PENDING_PAGES = ['/chinh-sach-bao-mat/', '/dieu-khoan-dich-vu/']; // legal copy awaits UNK-09
const VIEWPORTS = [320, 375, 768, 1024, 1440];
const SCREENSHOT_VIEWPORTS = [375, 1440];
const GAP_EYEBROW_TITLE = 16;
const GAP_TITLE_LEAD = 24;
const LEAD_MAX_WIDTH = 640;
const TOLERANCE_PX = 1;
const EXPECTED_COLORS = {
  eyebrow: 'rgb(113, 106, 97)', // --jn-muted-stone
  title: 'rgb(31, 30, 29)', // --jn-ink
  accent: 'rgb(160, 132, 110)', // --jn-champagne-text-lg
  lead: 'rgb(113, 106, 97)', // --jn-muted-stone
};

fs.mkdirSync(OUT_DIR, { recursive: true });

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

class CDPClient {
  constructor(wsUrl) {
    this.ws = new WebSocket(wsUrl);
    this.id = 0;
    this.pending = new Map();
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
      }
    });
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
  close() {
    this.ws.close();
  }
}

// Runs in the page: measures the first page-level header.
const MEASURE_HEADER = `(async () => {
  await document.fonts.ready;
  const h = document.querySelector('.jn-page-intro-title, .jn-hero-title');
  if (!h) return null;
  const wrap = h.parentElement;
  const eyebrow = wrap.querySelector('p[class*="eyebrow"]');
  const lead = h.nextElementSibling && h.nextElementSibling.tagName === 'P' ? h.nextElementSibling : null;
  const accent = h.querySelector('.jn-accent-italic');
  const rect = (el) => el.getBoundingClientRect();

  // Group the words of each title segment (ink statement / accent italic) by rendered line.
  const segments = { statement: {}, accent: {} };
  const walker = document.createTreeWalker(h, NodeFilter.SHOW_TEXT);
  for (let node = walker.nextNode(); node; node = walker.nextNode()) {
    const seg = accent && accent.contains(node) ? 'accent' : 'statement';
    for (const m of node.data.matchAll(/\\S+/g)) {
      const r = document.createRange();
      r.setStart(node, m.index);
      r.setEnd(node, m.index + m[0].length);
      const top = Math.round(r.getBoundingClientRect().top);
      (segments[seg][top] = segments[seg][top] || []).push(m[0]);
    }
  }
  const lines = (seg) => Object.keys(segments[seg]).map(Number).sort((a, b) => a - b).map((k) => ({ top: k, words: segments[seg][k] }));

  const header = document.querySelector('#header .ct-container');
  const headerContentLeft = header ? rect(header).left + parseFloat(getComputedStyle(header).paddingLeft) : null;

  return {
    statementLines: lines('statement'),
    accentLines: lines('accent'),
    eyebrowGap: eyebrow ? Math.round(rect(h).top - rect(eyebrow).bottom) : null,
    leadGap: lead ? Math.round(rect(lead).top - rect(h).bottom) : null,
    titleLeft: Math.round(rect(h).left),
    headerContentLeft: headerContentLeft === null ? null : Math.round(headerContentLeft),
    leadWidth: lead ? Math.round(rect(lead).width) : null,
    leadText: lead ? lead.textContent.trim() : '',
    colors: {
      eyebrow: eyebrow ? getComputedStyle(eyebrow).color : null,
      title: getComputedStyle(h).color,
      accent: accent ? getComputedStyle(accent).color : null,
      lead: lead ? getComputedStyle(lead).color : null,
    },
    overflow: document.documentElement.scrollWidth > window.innerWidth,
  };
})()`;

function checkHeader(m, pagePath) {
  const failures = [];
  if (!m) return ['no page-level header found'];
  for (const [name, segLines] of [['statement', m.statementLines], ['accent', m.accentLines]]) {
    const last = segLines[segLines.length - 1];
    if (segLines.length > 1 && last.words.length === 1) failures.push(`${name} orphan "${last.words[0]}"`);
  }
  const statementLast = m.statementLines[m.statementLines.length - 1];
  const accentTop = m.accentLines.length ? m.accentLines[0].top : null;
  if (accentTop !== null && statementLast && statementLast.top > accentTop) failures.push('accent is not the closing phrase');
  else if (accentTop !== null && statementLast && statementLast.top === accentTop) failures.push('accent shares a line with the statement');
  if (m.eyebrowGap !== null && Math.abs(m.eyebrowGap - GAP_EYEBROW_TITLE) > TOLERANCE_PX) failures.push(`eyebrow gap ${m.eyebrowGap}px`);
  if (m.leadGap !== null && Math.abs(m.leadGap - GAP_TITLE_LEAD) > TOLERANCE_PX) failures.push(`lead gap ${m.leadGap}px`);
  if (m.headerContentLeft !== null && Math.abs(m.titleLeft - m.headerContentLeft) > TOLERANCE_PX) failures.push(`title x=${m.titleLeft}, header content x=${m.headerContentLeft}`);
  if (m.leadWidth !== null && m.leadWidth > LEAD_MAX_WIDTH + TOLERANCE_PX) failures.push(`lead ${m.leadWidth}px wide`);
  if (/CẦN NỘI DUNG/.test(m.leadText) && !PLACEHOLDER_PENDING_PAGES.includes(pagePath)) failures.push('lead is still a placeholder');
  for (const [role, expected] of Object.entries(EXPECTED_COLORS)) {
    if (m.colors[role] && m.colors[role] !== expected) failures.push(`${role} colour ${m.colors[role]}`);
  }
  if (m.overflow) failures.push('horizontal overflow');
  return failures;
}

async function capturePageHeader(cdp, outPath) {
  const clip = await cdp.eval(`(() => {
    const s = document.querySelector('.jn-page-intro-section, .jn-hero-section');
    const r = s.getBoundingClientRect();
    return { x: 0, y: Math.round(r.top + scrollY), width: innerWidth, height: Math.min(Math.round(r.height), 1100), scale: 1 };
  })()`);
  const shot = await cdp.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip });
  fs.writeFileSync(outPath, Buffer.from(shot.data, 'base64'));
}

async function run() {
  const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), 'jn-step13-headers-'));
  const chrome = spawn(
    CHROME_BIN,
    ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', `--remote-debugging-port=${DEBUG_PORT}`, `--user-data-dir=${userDataDir}`, 'about:blank'],
    { stdio: 'ignore' }
  );

  const report = { generatedAt: new Date().toISOString(), baseUrl: BASE_URL, results: [] };
  let failed = 0;
  let cdp = null;

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
    await cdp.send('Page.enable');
    await cdp.send('Runtime.enable');
    await cdp.send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });

    for (const vw of VIEWPORTS) {
      await cdp.send('Emulation.setDeviceMetricsOverride', { width: vw, height: 1000, deviceScaleFactor: 1, mobile: vw < 768 });
      for (const pagePath of PAGES) {
        await cdp.send('Page.navigate', { url: BASE_URL + pagePath });
        await sleep(1200);
        const metrics = await cdp.eval(MEASURE_HEADER);
        const failures = checkHeader(metrics, pagePath);
        failed += failures.length ? 1 : 0;
        report.results.push({ page: pagePath, viewport: vw, failures, metrics });
        const title = metrics ? [...metrics.statementLines, ...metrics.accentLines].map((l) => l.words.join(' ')).join(' ⏎ ') : '';
        console.log(`${failures.length ? '[FAIL]' : '[OK]  '} ${String(vw).padStart(4)} ${pagePath.padEnd(24)} ${title}${failures.length ? '  — ' + failures.join('; ') : ''}`);

        if (SCREENSHOT_VIEWPORTS.includes(vw) && metrics) {
          const slug = pagePath === '/' ? 'home' : pagePath === PAGES[PAGES.length - 1] ? '404' : pagePath.replace(/\//g, '');
          await capturePageHeader(cdp, path.join(OUT_DIR, `${slug}-${vw}.png`));
        }
      }
    }
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
