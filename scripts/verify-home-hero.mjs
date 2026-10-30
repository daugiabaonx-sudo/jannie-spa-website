#!/usr/bin/env node
/**
 * JN — Home hero browser audit (ADR-021).
 * Loads `/` in headless Chrome at 320/375/768/1024/1440/1920px and checks, per viewport:
 *   - no horizontal overflow, the portrait frame and polaroid stay inside the viewport,
 *   - the LCP element is inside the hero (portrait on desktop, H1 / lead on one column), the portrait is
 *     painted (opacity 1, AVIF loaded),
 *   - the CTAs point at /coaching/ (primary) and #booking-form (secondary).
 * At 375 and 1440px it also drives the photo swap: Tab reaches the hidden checkbox (focus ring on the
 * polaroid), Space swaps the photos, a click on the polaroid swaps them back. Under
 * `prefers-reduced-motion: reduce` the decorative loops stop.
 * Writes docs/jn/home-hero/verification.json and screenshots to docs/jn/home-hero/.
 *
 * Run: node scripts/verify-home-hero.mjs   (exit 1 on any failed check)
 */
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const CHROME_BIN = process.env.CHROME_BIN || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const BASE_URL = process.env.JN_BASE_URL || 'http://localhost:8080';
const ROOT_DIR = process.cwd();
const OUT_DIR = path.join(ROOT_DIR, 'docs', 'jn', 'home-hero');
const REPORT_PATH = path.join(OUT_DIR, 'verification.json');
const DEBUG_PORT = 9344;

const VIEWPORTS = [320, 375, 768, 1024, 1440, 1920];
const INTERACTION_VIEWPORTS = [375, 1440];
const SCREENSHOT_VIEWPORTS = [375, 768, 1440];
const SWAP_TRANSITION_MS = 900; // --jn-motion-slow (600ms) + margin
const MAX_TABS = 40;

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
  async key(key, code, keyCode, text = undefined) {
    await this.send('Input.dispatchKeyEvent', { type: 'keyDown', key, code, windowsVirtualKeyCode: keyCode, text });
    await this.send('Input.dispatchKeyEvent', { type: 'keyUp', key, code, windowsVirtualKeyCode: keyCode });
  }
  close() {
    this.ws.close();
  }
}

// Runs in the page: layout, LCP and CTA facts for the hero.
const MEASURE_HERO = `(async () => {
  await document.fonts.ready;
  const lcp = await new Promise((resolve) => {
    let last = null;
    new PerformanceObserver((list) => { last = list.getEntries().at(-1); }).observe({ type: 'largest-contentful-paint', buffered: true });
    setTimeout(() => resolve(last), 300);
  });
  const q = (s) => document.querySelector(s);
  const primary = q('.jn-hero-portrait__img--primary');
  const frame = q('.jn-hero-portrait__frame');
  const polaroid = q('.jn-hero-portrait__polaroid');
  const r = (el) => (el ? el.getBoundingClientRect() : null);
  const lcpEl = lcp && lcp.element;
  return {
    overflow: document.documentElement.scrollWidth > innerWidth,
    lcp: lcpEl ? { tag: lcpEl.tagName, cls: lcpEl.className, ms: Math.round(lcp.startTime) } : null,
    // Desktop: the portrait. Single column (<1024px): the portrait is below the fold, so the H1 or lead wins.
    lcpIsHero: !!lcpEl && !!lcpEl.closest('.jn-hero-home'),
    primary: primary ? { complete: primary.complete && primary.naturalWidth > 0, src: primary.currentSrc, opacity: getComputedStyle(primary).opacity, loading: primary.loading, fetchPriority: primary.fetchPriority } : null,
    frame: frame ? { left: Math.round(r(frame).left), right: Math.round(r(frame).right), width: Math.round(r(frame).width) } : null,
    polaroidRight: polaroid ? Math.round(r(polaroid).right) : null,
    ctas: [...document.querySelectorAll('.jn-hero-actions a')].map((a) => a.getAttribute('href')),
    h1Count: document.querySelectorAll('h1').length,
  };
})()`;

const SWAP_STATE = `(() => {
  const t = document.querySelector('.jn-hero-portrait__toggle');
  const p = document.querySelector('.jn-hero-portrait__img--primary');
  const a = document.querySelector('.jn-hero-portrait__img--alt');
  const pol = document.querySelector('.jn-hero-portrait__polaroid');
  return {
    focused: document.activeElement === t,
    checked: t.checked,
    primaryOpacity: getComputedStyle(p).opacity,
    altOpacity: getComputedStyle(a).opacity,
    altLoaded: a.complete && a.naturalWidth > 0,
    polaroidOutline: getComputedStyle(pol).outlineStyle,
  };
})()`;

const MOTION_STATE = `(() => {
  const s = document.querySelector('.jn-hero-home');
  return {
    rings: getComputedStyle(s, '::before').animationName,
    polaroid: getComputedStyle(document.querySelector('.jn-hero-portrait__polaroid')).animationName,
  };
})()`;

function checkHero(m, vw) {
  const failures = [];
  if (!m.primary) return ['hero portrait not found'];
  if (m.overflow) failures.push('horizontal overflow');
  if (!m.lcpIsHero) failures.push(`LCP is ${m.lcp ? `${m.lcp.tag}.${m.lcp.cls}` : 'unknown'}`);
  if (!m.primary.complete) failures.push('portrait not loaded');
  if (!/\.avif$/.test(m.primary.src)) failures.push(`portrait served as ${m.primary.src}`);
  if (m.primary.opacity !== '1') failures.push(`portrait opacity ${m.primary.opacity}`);
  if (m.primary.loading !== 'eager' || m.primary.fetchPriority !== 'high') failures.push('portrait not eager/high');
  if (m.frame.left < 0 || m.frame.right > vw) failures.push(`frame x=${m.frame.left}..${m.frame.right}`);
  if (m.polaroidRight > vw) failures.push(`polaroid overflows to ${m.polaroidRight}px`);
  if (!/\/coaching\/$/.test(m.ctas[0] || '') || m.ctas[1] !== '#booking-form') failures.push(`CTAs ${m.ctas.join(', ')}`);
  if (m.h1Count !== 1) failures.push(`${m.h1Count} H1`);
  return failures;
}

async function tabToToggle(cdp) {
  await cdp.eval('document.activeElement && document.activeElement.blur(); window.scrollTo(0, 0); true');
  for (let i = 0; i < MAX_TABS; i++) {
    await cdp.key('Tab', 'Tab', 9);
    if (await cdp.eval("document.activeElement === document.querySelector('.jn-hero-portrait__toggle')")) return i + 1;
  }
  return 0;
}

async function checkSwap(cdp) {
  const failures = [];
  const tabs = await tabToToggle(cdp);
  if (!tabs) return ['Tab never reaches the swap checkbox'];
  const focused = await cdp.eval(SWAP_STATE);
  if (focused.polaroidOutline === 'none') failures.push('no focus ring on the polaroid');

  await cdp.key(' ', 'Space', 32, ' ');
  await sleep(SWAP_TRANSITION_MS);
  const swapped = await cdp.eval(SWAP_STATE);
  if (!swapped.checked || swapped.altOpacity !== '1' || swapped.primaryOpacity !== '0') failures.push(`Space did not swap (${JSON.stringify(swapped)})`);
  if (!swapped.altLoaded) failures.push('swap photo not loaded');

  const box = await cdp.eval(`(() => { const r = document.querySelector('.jn-hero-portrait__polaroid').getBoundingClientRect(); return { x: r.left + r.width / 2, y: r.top + r.height / 2 }; })()`);
  for (const type of ['mousePressed', 'mouseReleased']) {
    await cdp.send('Input.dispatchMouseEvent', { type, x: box.x, y: box.y, button: 'left', clickCount: 1 });
  }
  await sleep(SWAP_TRANSITION_MS);
  const back = await cdp.eval(SWAP_STATE);
  if (back.checked || back.primaryOpacity !== '1') failures.push('clicking the polaroid did not swap back');
  return { failures, tabs, swapped };
}

async function captureHero(cdp, outPath) {
  const clip = await cdp.eval(`(() => {
    const s = document.querySelector('.jn-hero-home');
    const r = s.getBoundingClientRect();
    return { x: 0, y: Math.round(r.top + scrollY), width: innerWidth, height: Math.round(r.height), scale: 1 };
  })()`);
  const shot = await cdp.send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: true, clip });
  fs.writeFileSync(outPath, Buffer.from(shot.data, 'base64'));
}

async function openHome(cdp, vw) {
  await cdp.send('Emulation.setDeviceMetricsOverride', { width: vw, height: 900, deviceScaleFactor: 1, mobile: vw < 768 });
  await cdp.send('Page.navigate', { url: `${BASE_URL}/` });
  await sleep(1500);
}

async function run() {
  const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), 'jn-home-hero-'));
  const chrome = spawn(
    CHROME_BIN,
    ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', `--remote-debugging-port=${DEBUG_PORT}`, `--user-data-dir=${userDataDir}`, 'about:blank'],
    { stdio: 'ignore' }
  );

  const report = { generatedAt: new Date().toISOString(), baseUrl: BASE_URL, results: [] };
  let failed = 0;
  let cdp = null;
  const record = (check, viewport, failures, data) => {
    failed += failures.length ? 1 : 0;
    report.results.push({ check, viewport, failures, data });
    console.log(`${failures.length ? '[FAIL]' : '[OK]  '} ${String(viewport).padStart(4)} ${check}${failures.length ? '  — ' + failures.join('; ') : ''}`);
  };

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

    for (const vw of VIEWPORTS) {
      await openHome(cdp, vw);
      const metrics = await cdp.eval(MEASURE_HERO);
      record(`layout + LCP (${metrics.lcp ? `${metrics.lcp.tag} ${metrics.lcp.ms}ms` : 'no LCP'})`, vw, checkHero(metrics, vw), metrics);

      if (SCREENSHOT_VIEWPORTS.includes(vw)) {
        await cdp.eval('window.scrollTo(0, 0); true');
        await captureHero(cdp, path.join(OUT_DIR, `home-hero-${vw}.png`));
      }
      if (INTERACTION_VIEWPORTS.includes(vw)) {
        const swap = await checkSwap(cdp);
        const failures = Array.isArray(swap) ? swap : swap.failures;
        record(`keyboard + click photo swap${swap.tabs ? ` (${swap.tabs} Tabs)` : ''}`, vw, failures, swap);
        if (vw === 1440 && !failures.length) {
          await cdp.key(' ', 'Space', 32, ' ');
          await sleep(SWAP_TRANSITION_MS);
          await cdp.eval('document.activeElement.blur(); window.scrollTo(0, 0); true');
          await captureHero(cdp, path.join(OUT_DIR, `home-hero-${vw}-swapped.png`));
        }
      }
    }

    await cdp.send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
    await openHome(cdp, 1440);
    const motion = await cdp.eval(MOTION_STATE);
    record('reduced motion stops the loops', 1440, motion.rings === 'none' && motion.polaroid === 'none' ? [] : [JSON.stringify(motion)], motion);
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
