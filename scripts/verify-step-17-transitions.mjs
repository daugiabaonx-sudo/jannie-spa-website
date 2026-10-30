#!/usr/bin/env node
/**
 * JN STEP 17 — Page transition audit (ADR-024).
 * Navigates between the 7 header tabs the way a visitor does (document-initiated, warm cache) at 1440px
 * and 390px and checks, per page:
 *   - the header (logo, wordmark, menu labels, account / menu buttons) and footer do not move between
 *     the state shown before any input and the state after the first mouse move / touch,
 *   - the header has the same geometry on every tab (menu labels within 0.25px),
 *   - the navigation runs a view transition (and none under prefers-reduced-motion),
 *   - Playfair Display is loaded before first contentful paint,
 *   - no long animation frame (> 50ms) runs during the 500ms after the new page is revealed.
 * Also checks that the scrollbar gutter keeps the logo still between a short legal page and a long page
 * at 1440x1080, and that a cross-page #anchor lands in the first frame instead of scrolling.
 * Writes docs/jn/step-17-verification.json.
 *
 * Run: node scripts/verify-step-17-transitions.mjs   (exit 1 on any failed check)
 */
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const CHROME_BIN = process.env.CHROME_BIN || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const BASE_URL = process.env.JN_BASE_URL || 'http://localhost:8080';
const REPORT_PATH = path.join(process.cwd(), 'docs', 'jn', 'step-17-verification.json');
const DEBUG_PORT = 9347;

const TABS = ['/', '/my-story/', '/coaching/', '/course/', '/client-success-story/', '/my-favorite-product/', '/booking/'];
const VIEWPORTS = [
  { name: 'desktop-1440', width: 1440, height: 900, mobile: false },
  { name: 'mobile-390', width: 390, height: 844, mobile: true },
];
const SETTLE_MS = 1200; // load + deferred loaders (load + 150ms) + font swaps
const AFTER_INPUT_MS = 1200; // first input -> deferred CSS/fonts applied (pre-fix behaviour)
const MENU_TOLERANCE_PX = 0.15; // residual of the semibold tracking offset (measured <= 0.13px)
const LONG_FRAME_MS = 50;
const TRANSITION_WINDOW_MS = 500;

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

class CDPClient {
  constructor(wsUrl) {
    this.ws = new WebSocket(wsUrl);
    this.id = 0;
    this.pending = new Map();
    this.listeners = new Map();
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
      } else if (msg.method && this.listeners.has(msg.method)) {
        const fns = this.listeners.get(msg.method);
        this.listeners.delete(msg.method);
        fns.forEach((fn) => fn(msg.params));
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
  once(method, timeoutMs) {
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => reject(new Error(`Timed out waiting for ${method}`)), timeoutMs);
      const fns = this.listeners.get(method) || [];
      fns.push((params) => {
        clearTimeout(timer);
        resolve(params);
      });
      this.listeners.set(method, fns);
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

// Installed in every new document before any page script runs.
const INIT_SCRIPT = `(() => {
  const s = (window.__jnStep17 = { reveal: null, fonts: [], loaf: [], scrolls: 0, scrollAtLoad: null });
  addEventListener('pagereveal', (e) => { s.reveal = { viewTransition: !!e.viewTransition, t: performance.now() }; });
  document.fonts.addEventListener('loadingdone', (e) => {
    e.fontfaces.forEach((f) => s.fonts.push({ face: f.family.replace(/"/g, '') + ':' + f.style, t: performance.now() }));
  });
  try {
    new PerformanceObserver((l) => l.getEntries().forEach((e) => s.loaf.push({ start: e.startTime, duration: e.duration })))
      .observe({ type: 'long-animation-frame', buffered: true });
  } catch (e) {}
  addEventListener('scroll', () => { s.scrolls++; }, { passive: true, capture: true });
  addEventListener('load', () => { s.scrollAtLoad = scrollY; }, { once: true });
})();`;

// Header + footer geometry as the visitor sees it right now.
const MEASURE = `(() => {
  const round = (v) => Math.round(v * 100) / 100;
  const rect = (el) => { if (!el) return null; const b = el.getBoundingClientRect(); return [b.x, b.y, b.width, b.height].map(round); };
  const visible = (sel) => [...document.querySelectorAll(sel)].find((e) => e.getBoundingClientRect().width > 0) || null;
  return {
    path: location.pathname,
    logo: rect(visible('#header .site-logo-container')),
    title: rect(visible('#header .site-title')),
    menu: [...document.querySelectorAll('#header .header-menu-1 .menu > li > a')].map((a) => round(a.getBoundingClientRect().x)),
    account: rect(visible('#header .jn-header-account-btn')),
    trigger: rect(visible('#header .ct-header-trigger')),
    footerHeight: Math.round(document.getElementById('footer')?.getBoundingClientRect().height ?? 0),
  };
})()`;

// Fonts and frames recorded by INIT_SCRIPT for the current document.
const TIMELINE = `(() => {
  const s = window.__jnStep17;
  const fcp = performance.getEntriesByName('first-contentful-paint')[0]?.startTime ?? null;
  // A face counts as ready for first paint when its load finished before FCP. Italic is only checked where the
  // page renders it (accent words, hero polaroid caption).
  const face = (style, file) => {
    const ff = [...document.fonts].find((f) => f.family.replace(/"/g, '') === 'Playfair Display' && f.style === style);
    const done = s.fonts.find((f) => f.face === 'Playfair Display:' + style);
    const res = performance.getEntriesByType('resource').find((r) => r.name.includes(file));
    const readyAt = done ? done.t : res ? res.responseEnd : null;
    return { used: !!ff && ff.status !== 'unloaded', loaded: !!ff && ff.status === 'loaded', readyAt: readyAt === null ? null : Math.round(readyAt) };
  };
  const revealT = s.reveal ? s.reveal.t : 0;
  const longFrames = s.loaf.filter((f) => f.start >= revealT && f.start < revealT + ${TRANSITION_WINDOW_MS} && f.duration > ${LONG_FRAME_MS}).map((f) => Math.round(f.duration));
  return {
    viewTransition: s.reveal ? s.reveal.viewTransition : null,
    fcp: fcp === null ? null : Math.round(fcp),
    playfair: face('normal', 'playfair-display-vi-normal.woff2'),
    playfairItalic: face('italic', 'playfair-display-vi-italic.woff2'),
    loafSupported: PerformanceObserver.supportedEntryTypes.includes('long-animation-frame'),
    longFrames,
    scrolls: s.scrolls,
    scrollAtLoad: s.scrollAtLoad,
    scrollNow: Math.round(scrollY),
  };
})()`;

const CLASSIC_SCROLLBARS_THEN_MEASURE = `(async () => {
  const style = document.createElement('style');
  style.textContent = '::-webkit-scrollbar { width: 15px; } ::-webkit-scrollbar-thumb { background: #999; }';
  document.head.appendChild(style);
  await new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
  return ${MEASURE};
})()`;

const sameRect = (a, b) => JSON.stringify(a) === JSON.stringify(b);
const menuDrift = (a, b) => (a.length === b.length ? Math.max(0, ...a.map((x, i) => Math.abs(x - b[i]))) : Infinity);

async function navigate(cdp, url) {
  const loaded = cdp.once('Page.loadEventFired', 20000);
  // Document-initiated like a link click (eligible for a view transition), without moving the pointer.
  await cdp.send('Runtime.evaluate', { expression: `location.assign(${JSON.stringify(url)})` });
  await loaded;
  await sleep(SETTLE_MS);
}

async function firstInput(cdp, viewport) {
  if (viewport.mobile) {
    const point = [{ x: Math.round(viewport.width / 2), y: Math.round(viewport.height * 0.7) }];
    await cdp.send('Input.dispatchTouchEvent', { type: 'touchStart', touchPoints: point });
    await cdp.send('Input.dispatchTouchEvent', { type: 'touchCancel', touchPoints: [] });
  } else {
    await cdp.send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: Math.round(viewport.width / 2), y: Math.round(viewport.height * 0.7) });
  }
  await sleep(AFTER_INPUT_MS);
}

async function setViewport(cdp, viewport) {
  await cdp.send('Emulation.setDeviceMetricsOverride', { width: viewport.width, height: viewport.height, deviceScaleFactor: 1, mobile: viewport.mobile });
  await cdp.send('Emulation.setTouchEmulationEnabled', { enabled: viewport.mobile });
}

async function run() {
  const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), 'jn-step17-transitions-'));
  const chrome = spawn(
    CHROME_BIN,
    ['--headless=new', '--disable-gpu', '--no-first-run', '--no-default-browser-check', `--remote-debugging-port=${DEBUG_PORT}`, `--user-data-dir=${userDataDir}`, 'about:blank'],
    { stdio: 'ignore' }
  );

  const report = { generatedAt: new Date().toISOString(), baseUrl: BASE_URL, checks: [], samples: {} };
  const check = (name, pass, detail = '') => {
    report.checks.push({ name, pass, detail });
    console.log(`${pass ? '[PASS]' : '[FAIL]'} ${name}${pass || !detail ? '' : ' — ' + detail}`);
  };
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
    await cdp.send('Page.addScriptToEvaluateOnNewDocument', { source: INIT_SCRIPT });

    for (const viewport of VIEWPORTS) {
      await setViewport(cdp, viewport);
      // Warm the HTTP cache the way a returning visitor has it, then start from a fresh document.
      await cdp.send('Page.navigate', { url: `${BASE_URL}/` });
      await cdp.once('Page.loadEventFired', 20000);
      await firstInput(cdp, viewport);

      const states = [];
      for (const tab of TABS) {
        await navigate(cdp, `${BASE_URL}${tab}`);
        const before = await cdp.eval(MEASURE);
        const timeline = await cdp.eval(TIMELINE);
        await firstInput(cdp, viewport);
        const after = await cdp.eval(MEASURE);
        states.push({ tab, before, after, timeline });

        const label = `${viewport.name} ${tab}`;
        const moved = ['logo', 'title', 'account', 'trigger', 'footerHeight'].filter((k) => !sameRect(before[k], after[k]));
        const drift = menuDrift(before.menu, after.menu);
        check(`${label}: header and footer do not move on first input`, moved.length === 0 && drift === 0, `moved: ${moved.join(', ') || 'none'}, menu drift ${drift}px`);
        if (tab !== TABS[0]) {
          check(`${label}: navigation runs a view transition`, timeline.viewTransition === true, `pagereveal.viewTransition=${timeline.viewTransition}`);
          check(`${label}: no long frame during the transition`, timeline.loafSupported && timeline.longFrames.length === 0, `LoAF supported=${timeline.loafSupported}, long frames ${timeline.longFrames.join(', ')}ms`);
        }
        const ready = (f) => f.loaded && timeline.fcp !== null && f.readyAt !== null && f.readyAt <= timeline.fcp;
        check(`${label}: Playfair Display is loaded before first contentful paint`, ready(timeline.playfair), JSON.stringify({ fcp: timeline.fcp, ...timeline.playfair }));
        if (timeline.playfairItalic.used) {
          check(`${label}: Playfair Display italic is loaded before first contentful paint`, ready(timeline.playfairItalic), JSON.stringify({ fcp: timeline.fcp, ...timeline.playfairItalic }));
        }
      }

      const ref = states[0].before;
      for (const s of states.slice(1)) {
        const moved = ['logo', 'title', 'account', 'trigger'].filter((k) => !sameRect(ref[k], s.before[k]));
        const drift = menuDrift(ref.menu, s.before.menu);
        check(`${viewport.name} ${s.tab}: header geometry matches the home tab`, moved.length === 0 && drift <= MENU_TOLERANCE_PX, `moved: ${moved.join(', ') || 'none'}, menu drift ${drift.toFixed(2)}px`);
      }
      report.samples[viewport.name] = states;
    }

    // Reduced motion: navigation stays an instant swap.
    await setViewport(cdp, VIEWPORTS[0]);
    await cdp.send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
    await navigate(cdp, `${BASE_URL}/coaching/`);
    const reduced = await cdp.eval(TIMELINE);
    check('prefers-reduced-motion: navigation runs without a view transition', reduced.viewTransition === false, `pagereveal.viewTransition=${reduced.viewTransition}`);
    await cdp.send('Emulation.setEmulatedMedia', { features: [] });

    // Scrollbar gutter: with classic 15px scrollbars (Windows, macOS "always show"), a page shorter than the
    // viewport keeps the logo where a long page has it. Headless Chrome uses overlay scrollbars, so force them.
    await setViewport(cdp, { width: 1440, height: 1080, mobile: false });
    await navigate(cdp, `${BASE_URL}/chinh-sach-bao-mat/`);
    const legal = await cdp.eval(CLASSIC_SCROLLBARS_THEN_MEASURE);
    await navigate(cdp, `${BASE_URL}/coaching/`);
    const long = await cdp.eval(CLASSIC_SCROLLBARS_THEN_MEASURE);
    check('1440x1080: logo x is the same on a short legal page and a long page', legal.logo && long.logo && legal.logo[0] === long.logo[0], `legal x=${legal.logo?.[0]}, coaching x=${long.logo?.[0]}`);
    report.samples.scrollbarGutter = { legal: legal.logo, coaching: long.logo };

    // Cross-page #anchor: lands in the first frame instead of gliding through the page.
    await setViewport(cdp, VIEWPORTS[0]);
    await navigate(cdp, `${BASE_URL}/my-story/#booking-form`);
    const anchor = await cdp.eval(TIMELINE);
    check('Cross-page #booking-form lands without a smooth scroll', anchor.scrolls <= 2 && anchor.scrollNow > 0 && Math.abs((anchor.scrollAtLoad ?? -1) - anchor.scrollNow) <= 2, `scroll events ${anchor.scrolls}, at load ${anchor.scrollAtLoad}, now ${anchor.scrollNow}`);
    report.samples.anchor = anchor;
  } finally {
    if (cdp) cdp.close();
    chrome.kill('SIGKILL');
  }

  fs.writeFileSync(REPORT_PATH, JSON.stringify(report, null, 2) + '\n');
  const failed = report.checks.filter((c) => !c.pass).length;
  console.log(`\nRESULT: ${report.checks.length - failed} passed, ${failed} failed -> ${path.relative(process.cwd(), REPORT_PATH)}`);
  process.exit(failed ? 1 : 0);
}

run().catch((err) => {
  console.error(err);
  process.exit(1);
});
