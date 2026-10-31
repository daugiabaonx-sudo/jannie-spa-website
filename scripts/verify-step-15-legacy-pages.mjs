/**
 * JN — Steps 15–16 (ADR-022, ADR-023) browser verification of the pages rebuilt from jannieedu.com:
 * /my-favorite-product/, /course/, /client-success-story/, /my-story/ and /coaching/.
 *
 * 1. Verbatim: every string in scripts/lib/jn-legacy-content.json occurs in the Wayback evidence
 *    (docs/jn/legacy-content/*.txt), ignoring case and after the approved spelling corrections.
 * 2. Layout at 320/375/768/1024/1440/1920: no horizontal overflow (offenders listed), one H1, every image loaded.
 * 3. Video facade on /client-success-story/: no request to YouTube before a click; a click and Enter each swap
 *    the link for a youtube-nocookie player.
 * 4. axe-core WCAG 2.2 AA (reduced motion, so scroll-reveal content is visible) at 375 and 1440, plus `/`.
 * Screenshots: docs/jn/legacy-content/screens/. Report: docs/jn/legacy-content/verification.json.
 *
 * Usage: node scripts/verify-step-15-legacy-pages.mjs   (AXE_PATH=/path/to/axe.min.js to override)
 */
import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import fs from 'node:fs';
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
const OUT_DIR = path.join(ROOT_DIR, 'docs', 'jn', 'legacy-content');
const SHOT_DIR = path.join(OUT_DIR, 'screens');
const DEBUG_PORT = 9345;

// Step 16 (ADR-023) adds /my-story/ and /coaching/.
const PAGES = ['/my-favorite-product/', '/course/', '/client-success-story/', '/my-story/', '/coaching/'];
const VIEWPORTS = [320, 375, 768, 1024, 1440, 1920];
const SHOT_VIEWPORTS = [375, 1440];
const AXE_VIEWPORTS = [375, 1440];
const SETTLE_MS = 1500;

fs.mkdirSync(SHOT_DIR, { recursive: true });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

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
  send(method, params = {}) {
    const id = ++this.id;
    return new Promise((resolve, reject) => {
      this.pending.set(id, { resolve, reject });
      this.ws.send(JSON.stringify({ id, method, params }));
    });
  }
  async eval(expression) {
    const res = await this.send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
    if (res?.exceptionDetails) throw new Error(`Eval error: ${JSON.stringify(res.exceptionDetails)}`);
    return res?.result?.value;
  }
  close() {
    this.ws.close();
  }
}

// --- 1. Verbatim check (no browser) ---------------------------------------------------------------------------
function verbatimFailures() {
  const content = JSON.parse(fs.readFileSync(path.join(ROOT_DIR, 'scripts/lib/jn-legacy-content.json'), 'utf8'));
  const norm = (s) => s.replace(/​/g, '').replace(/\s+/g, ' ').trim().toLocaleLowerCase('vi');
  let evidence = norm(
    fs.readdirSync(OUT_DIR)
      .filter((f) => /\.wayback-20\d+\.txt$/.test(f))
      .map((f) => fs.readFileSync(path.join(OUT_DIR, f), 'utf8'))
      .join(' ')
  );
  for (const c of content.corrections) evidence = evidence.split(c.from.toLocaleLowerCase('vi')).join(c.to.toLocaleLowerCase('vi'));
  // Keys that hold identifiers, URLs or labels written for the new site, not copy from the old pages.
  // `teaser` is the owner's 2026-10-31 card copy, checked against the archived mockup by verify-step-18.
  const skip = new Set(['_about', 'sources', 'corrections', 'flags', 'image', 'slug', 'video', 'poster', 'url', 'id', 'note', 'price', 'eyebrow_site', 'teaser']);
  const missing = [];
  let checked = 0;
  const walk = (node, trail) => {
    if (Array.isArray(node)) return node.forEach((v, i) => walk(v, `${trail}[${i}]`));
    if (node && typeof node === 'object') {
      return Object.entries(node).forEach(([k, v]) => {
        if (skip.has(k) || (k === 'name' && trail.includes('press'))) return;
        walk(v, `${trail}.${k}`);
      });
    }
    if (typeof node === 'string' && node.trim()) {
      checked++;
      const s = norm(node).replace(/^"|"$/g, '');
      if (!evidence.includes(s)) missing.push(`${trail}: ${node.slice(0, 60)}`);
    }
  };
  walk(content, '');
  return { checked, missing };
}

// --- 2. In-page measurements ----------------------------------------------------------------------------------
const MEASURE = `(async () => {
  await document.fonts.ready;
  const vw = document.documentElement.clientWidth;
  const offenders = [...document.querySelectorAll('body *')]
    .filter((el) => { const r = el.getBoundingClientRect(); return r.width > 0 && (r.right > vw + 1 || r.left < -1); })
    .filter((el) => !el.closest('.screen-reader-text, [aria-hidden="true"]') && getComputedStyle(el).position !== 'fixed')
    .slice(0, 5)
    .map((el) => el.tagName.toLowerCase() + '.' + String(el.className).split(' ').slice(0, 3).join('.') + ' →' + Math.round(el.getBoundingClientRect().right));
  for (const img of document.images) img.loading = 'eager';
  window.scrollTo(0, document.body.scrollHeight);
  await new Promise((r) => setTimeout(r, 1200));
  window.scrollTo(0, 0);
  const broken = [...document.images].filter((i) => i.currentSrc && (!i.complete || i.naturalWidth === 0)).map((i) => i.currentSrc);
  return {
    overflow: document.documentElement.scrollWidth > window.innerWidth,
    offenders,
    h1: document.querySelectorAll('h1').length,
    iframes: document.querySelectorAll('iframe').length,
    broken,
  };
})()`;

async function open(cdp, url, vw, height = 900) {
  await cdp.send('Emulation.setDeviceMetricsOverride', { width: vw, height, deviceScaleFactor: 1, mobile: vw < 768 });
  await cdp.send('Page.navigate', { url });
  await sleep(SETTLE_MS);
}

async function fullShot(cdp, file) {
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

// --- 3. Video facade --------------------------------------------------------------------------------------------
async function checkVideo(cdp, requests) {
  const failures = [];
  await open(cdp, `${BASE_URL}/client-success-story/`, 1440);
  await cdp.eval('window.scrollTo(0, document.body.scrollHeight); true');
  await sleep(800);
  const before = requests.filter((u) => /youtube|ytimg|googlevideo/.test(u));
  if (before.length) failures.push(`requests to YouTube before a click: ${before.slice(0, 3).join(', ')}`);
  const links = await cdp.eval("document.querySelectorAll('a[data-jn-video-id]').length");
  if (links !== 3) failures.push(`${links} video links (expected 3)`);

  // The theme sets `scroll-behavior: smooth`: scroll instantly, then measure.
  await cdp.eval("document.querySelector('a[data-jn-video-id]').scrollIntoView({ block: 'center', behavior: 'instant' }); true");
  await sleep(300);
  const box = await cdp.eval(`(() => { const r = document.querySelector('a[data-jn-video-id]').getBoundingClientRect(); return { x: r.left + r.width / 2, y: r.top + r.height / 2 }; })()`);
  for (const type of ['mousePressed', 'mouseReleased']) {
    await cdp.send('Input.dispatchMouseEvent', { type, x: box.x, y: box.y, button: 'left', clickCount: 1 });
  }
  await sleep(500);
  const click = await cdp.eval(`(() => { const f = document.querySelector('iframe.jn-video__frame'); return f ? { src: f.src, focused: document.activeElement === f, title: f.title } : null; })()`);
  if (!click || !click.src.startsWith('https://www.youtube-nocookie.com/embed/eDk34ql8Qb0')) failures.push(`click: ${JSON.stringify(click)}`);
  else if (!click.focused) failures.push('click: player not focused');

  await cdp.eval("document.querySelectorAll('a[data-jn-video-id]')[0].focus(); true");
  await cdp.send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13, text: '\r' });
  await cdp.send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13 });
  await sleep(500);
  const frames = await cdp.eval("[...document.querySelectorAll('iframe.jn-video__frame')].map((f) => f.src)");
  if (frames.length !== 2 || !frames[1].includes('youtube-nocookie.com/embed/nWqff-L-mQg')) failures.push(`Enter: ${JSON.stringify(frames)}`);
  return { failures, data: { before: before.length, links, click, frames } };
}

async function run() {
  const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), 'jn-step-15-'));
  const chrome = spawn(CHROME_BIN, ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', `--remote-debugging-port=${DEBUG_PORT}`, `--user-data-dir=${userDataDir}`, 'about:blank'], { stdio: 'ignore' });
  const report = { generatedAt: new Date().toISOString(), baseUrl: BASE_URL, results: [] };
  let failed = 0;
  const record = (check, viewport, failures, data) => {
    failed += failures.length ? 1 : 0;
    report.results.push({ check, viewport, failures, data });
    console.log(`${failures.length ? '[FAIL]' : '[OK]  '} ${String(viewport).padStart(4)} ${check}${failures.length ? '  — ' + failures.join('; ') : ''}`);
  };
  let cdp = null;
  try {
    const verbatim = verbatimFailures();
    record(`verbatim: ${verbatim.checked} source strings found in the Wayback evidence`, '-', verbatim.missing, verbatim);

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
    const requests = [];
    cdp.listeners.push((msg) => msg.method === 'Network.requestWillBeSent' && requests.push(msg.params.request.url));
    await cdp.send('Page.enable');
    await cdp.send('Runtime.enable');
    await cdp.send('Network.enable');
    await cdp.send('Network.setCacheDisabled', { cacheDisabled: true });
    // Reduced motion shows scroll-reveal content without scrolling (as in the Step 9 regression).
    await cdp.send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });

    for (const page of PAGES) {
      for (const vw of VIEWPORTS) {
        await open(cdp, BASE_URL + page, vw);
        const m = await cdp.eval(MEASURE);
        const failures = [];
        if (m.overflow) failures.push(`horizontal overflow: ${m.offenders.join(', ')}`);
        if (m.h1 !== 1) failures.push(`${m.h1} H1`);
        if (m.iframes) failures.push(`${m.iframes} iframe(s) before any click`);
        if (m.broken.length) failures.push(`broken images: ${m.broken.slice(0, 3).join(', ')}`);
        record(`${page} layout`, vw, failures, m);
        if (SHOT_VIEWPORTS.includes(vw)) await fullShot(cdp, path.join(SHOT_DIR, `${page.replace(/\//g, '') || 'home'}-${vw}.png`));
        if (AXE_VIEWPORTS.includes(vw)) {
          const a = await axe(cdp);
          record(`${page} axe WCAG 2.2 AA`, vw, a.skipped ? [a.skipped] : a.violations, a);
        }
      }
    }
    for (const vw of AXE_VIEWPORTS) {
      await open(cdp, `${BASE_URL}/`, vw);
      const a = await axe(cdp);
      record('/ axe WCAG 2.2 AA (legacy testimonials)', vw, a.skipped ? [a.skipped] : a.violations, a);
    }

    await cdp.send('Emulation.setEmulatedMedia', { features: [] });
    requests.length = 0;
    const video = await checkVideo(cdp, requests);
    record('video facade: no YouTube request before click; click + Enter open youtube-nocookie', 1440, video.failures, video.data);
  } finally {
    if (cdp) cdp.close();
    chrome.kill();
    await sleep(300);
    fs.rmSync(userDataDir, { recursive: true, force: true });
  }
  report.summary = { checks: report.results.length, failed };
  fs.writeFileSync(path.join(OUT_DIR, 'verification.json'), JSON.stringify(report, null, 2));
  console.log(`\nRESULT: ${report.results.length - failed} passed, ${failed} failed — report: docs/jn/legacy-content/verification.json`);
  process.exitCode = failed ? 1 : 0;
}

run().catch((err) => {
  console.error(err);
  process.exitCode = 1;
});
