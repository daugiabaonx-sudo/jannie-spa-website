#!/usr/bin/env node
/**
 * JN STEP 4 — Re-capture `/my-favorite-product/` demo screenshots & run Lighthouse Mobile baselines
 * for `/my-favorite-product/`, `/checkout/` (with non-empty cart), and `/coaching/`.
 */

import { spawn, execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const CHROME_PATH = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const LIGHTHOUSE_BIN = '/Users/daugiabao/.npm/_npx/0f94ee7615faf582/node_modules/.bin/lighthouse';
const BASE_URL = 'http://localhost:8080';
const ARTIFACT_DIR = '/Users/daugiabao/.gemini/antigravity/brain/ddb23a35-60cd-444c-9355-4e676ad23023';
const DOCS_DIR = path.resolve(process.cwd(), 'docs/jn');

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

class CDPClient {
  constructor(wsUrl) {
    this.wsUrl = wsUrl;
    this.ws = null;
    this.id = 0;
    this.pending = new Map();
  }

  async connect() {
    this.ws = new WebSocket(this.wsUrl);
    await new Promise((resolve, reject) => {
      this.ws.addEventListener('open', resolve, { once: true });
      this.ws.addEventListener('error', reject, { once: true });
    });
    this.ws.addEventListener('message', (event) => {
      const msg = JSON.parse(typeof event.data === 'string' ? event.data : event.data.toString());
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
    const res = await this.send('Runtime.evaluate', {
      expression,
      awaitPromise: true,
      returnByValue: true,
    });
    if (res.exceptionDetails) {
      throw new Error('Runtime.evaluate exception: ' + JSON.stringify(res.exceptionDetails));
    }
    return res.result ? res.result.value : undefined;
  }

  close() {
    if (this.ws) this.ws.close();
  }
}

async function launchChrome(port = 0) {
  const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), 'jn-step4-lh-'));
  const chrome = spawn(
    CHROME_PATH,
    [
      '--headless=new',
      `--remote-debugging-port=${port}`,
      `--user-data-dir=${userDataDir}`,
      '--no-first-run',
      '--no-default-browser-check',
      '--disable-gpu',
      '--window-size=1440,900',
      'about:blank',
    ],
    { stdio: ['ignore', 'pipe', 'pipe'] }
  );

  const wsUrl = await new Promise((resolve, reject) => {
    let stderr = '';
    const timeout = setTimeout(() => reject(new Error('Chrome launch timeout: ' + stderr)), 15000);
    chrome.stderr.on('data', (chunk) => {
      stderr += chunk.toString();
      const match = stderr.match(/DevTools listening on (ws:\/\/[^\s]+)/);
      if (match) {
        clearTimeout(timeout);
        resolve(match[1]);
      }
    });
  });

  const actualPort = Number(new URL(wsUrl).port);
  const targetsRes = await fetch(`http://127.0.0.1:${actualPort}/json/list`);
  const targets = await targetsRes.json();
  const pageTarget = targets.find((t) => t.type === 'page');
  const cdp = new CDPClient(pageTarget.webSocketDebuggerUrl);
  await cdp.connect();
  await cdp.send('Page.enable');
  await cdp.send('Runtime.enable');
  await cdp.send('DOM.enable');
  // Reduced motion keeps the theme's scroll reveal (inc/motion.php) off, so full-page captures show every section.
  await cdp.send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
  return { chrome, userDataDir, cdp, port: actualPort };
}

async function capturePageScreenshot(cdp, filePath, width = 1440, height = 900, fullPage = true) {
  await cdp.send('Emulation.setDeviceMetricsOverride', {
    width,
    height,
    deviceScaleFactor: 2,
    mobile: width < 768,
  });
  await sleep(400);

  if (fullPage) {
    const docHeight = await cdp.eval(`Math.max(
      document.body ? document.body.scrollHeight : 0,
      document.documentElement ? document.documentElement.scrollHeight : 0,
      ${height}
    )`);
    const targetHeight = Math.min(Math.ceil(docHeight), 5000);
    await cdp.send('Emulation.setDeviceMetricsOverride', {
      width,
      height: targetHeight,
      deviceScaleFactor: 2,
      mobile: width < 768,
    });
    await sleep(300);
  }

  const { data } = await cdp.send('Page.captureScreenshot', {
    format: 'png',
    captureBeyondViewport: false,
  });
  fs.writeFileSync(filePath, Buffer.from(data, 'base64'));
}

function summarizeLighthouse(jsonPath) {
  const report = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));
  const cats = report.categories || {};
  const audits = report.audits || {};
  return {
    finalDisplayedUrl: report.finalDisplayedUrl,
    scores: {
      performance: cats.performance ? Math.round(cats.performance.score * 100) : null,
      accessibility: cats.accessibility ? Math.round(cats.accessibility.score * 100) : null,
      bestPractices: cats['best-practices'] ? Math.round(cats['best-practices'].score * 100) : null,
      seo: cats.seo ? Math.round(cats.seo.score * 100) : null,
    },
    metrics: {
      fcp: audits['first-contentful-paint']?.displayValue || null,
      lcp: audits['largest-contentful-paint']?.displayValue || null,
      tbt: audits['total-blocking-time']?.displayValue || null,
      cls: audits['cumulative-layout-shift']?.displayValue || null,
      si: audits['speed-index']?.displayValue || null,
    },
  };
}

async function main() {
  const isPostCleanup = process.argv.includes('--post-cleanup');
  const demoProductId = 174;
  const checkoutLhPath = path.join(DOCS_DIR, 'step-4-lighthouse-checkout-mobile.json');
  const favLhPath = path.join(DOCS_DIR, 'step-4-lighthouse-my-favorite-product-mobile.json');
  const coachingLhPath = path.join(DOCS_DIR, 'step-4-lighthouse-coaching-mobile.json');

  if (isPostCleanup) {
    const session = await launchChrome(0);
    try {
      console.log('=== Capturing /my-favorite-product/ clean empty state (post-cleanup) ===');
      await session.cdp.send('Page.navigate', { url: `${BASE_URL}/my-favorite-product/` });
      await sleep(2000);
      await capturePageScreenshot(
        session.cdp,
        path.join(ARTIFACT_DIR, 'step4-my-favorite-product-empty-1440.png'),
        1440,
        900,
        true
      );
      session.cdp.close();
    } finally {
      session.chrome.kill('SIGKILL');
      fs.rmSync(session.userDataDir, { recursive: true, force: true });
    }

    console.log('=== Running Lighthouse Mobile on /my-favorite-product/ (post-cleanup) ===');
    execFileSync(
      LIGHTHOUSE_BIN,
      [
        `${BASE_URL}/my-favorite-product/`,
        '--only-categories=performance,accessibility,best-practices,seo',
        '--output=json',
        `--output-path=${favLhPath}`,
        '--chrome-flags=--headless=new --no-sandbox --disable-gpu',
        '--quiet',
      ],
      { stdio: 'inherit', env: { ...process.env, CHROME_PATH } }
    );

    const summary = {
      myFavoriteProduct: summarizeLighthouse(favLhPath),
      checkout: summarizeLighthouse(checkoutLhPath),
      coaching: summarizeLighthouse(coachingLhPath),
    };
    console.log('\n=== FINAL LIGHTHOUSE MOBILE BASELINES SUMMARY ===');
    console.log(JSON.stringify(summary, null, 2));
    return;
  }

  const session = await launchChrome(9333);
  try {
    // 1. Re-capture /my-favorite-product/ with updated section-header pattern at 1440px and 375px
    console.log('=== 1. Capturing /my-favorite-product/ with DEMO product ===');
    await session.cdp.send('Page.navigate', { url: `${BASE_URL}/my-favorite-product/` });
    await sleep(2200);
    await capturePageScreenshot(
      session.cdp,
      path.join(ARTIFACT_DIR, 'step4-my-favorite-product-demo-1440.png'),
      1440,
      900,
      true
    );
    await capturePageScreenshot(
      session.cdp,
      path.join(ARTIFACT_DIR, 'step4-my-favorite-product-demo-375.png'),
      375,
      812,
      true
    );

    // 2. Add demo product to cart in this Chrome profile so /checkout/ has an active cart
    console.log('=== 2. Adding DEMO product to cart for /checkout/ Lighthouse run ===');
    await session.cdp.send('Page.navigate', { url: `${BASE_URL}/?add-to-cart=${demoProductId}` });
    await sleep(2000);
    await session.cdp.send('Page.navigate', { url: `${BASE_URL}/checkout/` });
    await sleep(3000);
    const checkoutUrlCheck = await session.cdp.eval('window.location.href');
    console.log('Verified active cart at:', checkoutUrlCheck);
    session.cdp.close();

    // 3. Run Lighthouse Mobile on /checkout/ attached to port 9333 with --disable-storage-reset
    console.log('=== 3. Running Lighthouse Mobile on /checkout/ ===');
    execFileSync(
      LIGHTHOUSE_BIN,
      [
        `${BASE_URL}/checkout/`,
        `--port=${session.port}`,
        '--disable-storage-reset',
        '--only-categories=performance,accessibility,best-practices,seo',
        '--output=json',
        `--output-path=${checkoutLhPath}`,
        '--quiet',
      ],
      { stdio: 'inherit', env: { ...process.env, CHROME_PATH } }
    );
  } finally {
    session.chrome.kill('SIGKILL');
    fs.rmSync(session.userDataDir, { recursive: true, force: true });
  }

  // 4. Run Lighthouse Mobile on /my-favorite-product/ (with demo product in catalog)
  console.log('=== 4. Running Lighthouse Mobile on /my-favorite-product/ ===');
  execFileSync(
    LIGHTHOUSE_BIN,
    [
      `${BASE_URL}/my-favorite-product/`,
      '--only-categories=performance,accessibility,best-practices,seo',
      '--output=json',
      `--output-path=${favLhPath}`,
      '--chrome-flags=--headless=new --no-sandbox --disable-gpu',
      '--quiet',
    ],
    { stdio: 'inherit', env: { ...process.env, CHROME_PATH } }
  );

  // 5. Run Lighthouse Mobile on /coaching/
  console.log('=== 5. Running Lighthouse Mobile on /coaching/ ===');
  execFileSync(
    LIGHTHOUSE_BIN,
    [
      `${BASE_URL}/coaching/`,
      '--only-categories=performance,accessibility,best-practices,seo',
      '--output=json',
      `--output-path=${coachingLhPath}`,
      '--chrome-flags=--headless=new --no-sandbox --disable-gpu',
      '--quiet',
    ],
    { stdio: 'inherit', env: { ...process.env, CHROME_PATH } }
  );

  const summary = {
    myFavoriteProduct: summarizeLighthouse(favLhPath),
    checkout: summarizeLighthouse(checkoutLhPath),
    coaching: summarizeLighthouse(coachingLhPath),
  };
  console.log('\n=== LIGHTHOUSE MOBILE BASELINES SUMMARY ===');
  console.log(JSON.stringify(summary, null, 2));
}

main().catch((err) => {
  console.error('Lighthouse runner failed:', err);
  process.exit(1);
});
