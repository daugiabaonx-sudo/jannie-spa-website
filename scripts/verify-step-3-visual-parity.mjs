#!/usr/bin/env node
/**
 * JN STEP 3 — Visual Parity Audit & Side-by-Side Screenshot Generator
 * Compares the local Wireframe (`stitch_jannie_nguyen_website_wireframe/code.html`)
 * against WordPress (`http://localhost:8080/coaching/`) across all 6 Coaching sections
 * at 375px, 768px, 1024px, and 1440px viewports.
 */
import { spawn } from 'node:child_process';
import http from 'node:http';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const CHROME_BIN = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const ROOT_DIR = process.cwd();
const WIREFRAME_DIR = path.join(ROOT_DIR, 'stitch_jannie_nguyen_website_wireframe');
const OUT_DIR = path.join(ROOT_DIR, 'docs', 'jn', 'parity-step-3');

fs.mkdirSync(OUT_DIR, { recursive: true });

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
        for (const fn of this.listeners.get(msg.method)) {
          fn(msg.params);
        }
      }
    });
  }
  on(method, fn) {
    if (!this.listeners.has(method)) this.listeners.set(method, []);
    this.listeners.get(method).push(fn);
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
      returnByValue: true,
      awaitPromise: true,
    });
    if (res?.exceptionDetails) {
      throw new Error(`Eval error: ${JSON.stringify(res.exceptionDetails)}`);
    }
    return res?.result?.value;
  }
  close() {
    this.ws.close();
  }
}

function startStaticServer(dir, port = 8081) {
  return new Promise((resolve, reject) => {
    const server = http.createServer((req, res) => {
      const urlPath = (req.url || '/').split('?')[0];
      const relPath = urlPath === '/' ? 'code.html' : urlPath.replace(/^\/+/, '');
      const filePath = path.join(dir, relPath);
      if (!filePath.startsWith(dir) || !fs.existsSync(filePath)) {
        res.writeHead(404);
        res.end('Not found');
        return;
      }
      const ext = path.extname(filePath).toLowerCase();
      const mime =
        ext === '.html'
          ? 'text/html; charset=utf-8'
          : ext === '.png'
          ? 'image/png'
          : ext === '.css'
          ? 'text/css'
          : 'application/octet-stream';
      res.writeHead(200, { 'Content-Type': mime });
      fs.createReadStream(filePath).pipe(res);
    });
    server.on('error', reject);
    server.listen(port, '127.0.0.1', () => resolve(server));
  });
}

const SECTIONS = [
  {
    id: 's1-hero',
    name: 'Section 1 — Architectural Hero',
    wfSelector: 'main section:nth-of-type(1)',
    wpSelector: 'section.jn-hero-section',
  },
  {
    id: 's2-pillars',
    name: 'Section 2 — Value Pillars (3 Columns)',
    wfSelector: 'main section:nth-of-type(2)',
    wpSelector: 'section.jn-pillars-section',
  },
  {
    id: 's3-tiers',
    name: 'Section 3 — Coaching Tiers (#coaching-tiers)',
    wfSelector: 'main section#coaching-tiers',
    wpSelector: 'section#coaching-tiers',
  },
  {
    id: 's4-expert',
    name: 'Section 4 — Expert Intro & Philosophy',
    wfSelector: 'main section:nth-of-type(4)',
    wpSelector: 'section.jn-expert-section',
  },
  {
    id: 's5-testimonials',
    name: 'Section 5 — Testimonials (Query Loop)',
    wfSelector: 'main section:nth-of-type(5)',
    wpSelector: 'section.jn-testimonials-section',
  },
  {
    id: 's6-booking',
    name: 'Section 6 — Booking Form (#booking-form)',
    wfSelector: 'main section#booking-form',
    wpSelector: 'section#booking-form',
  },
];

const VIEWPORTS = [375, 768, 1024, 1440];

async function captureElementBase64(cdp, selector) {
  const rect = await cdp.eval(`(() => {
    const el = document.querySelector(${JSON.stringify(selector)});
    if (!el) return null;
    const r = el.getBoundingClientRect();
    return {
      x: Math.max(0, Math.floor(r.left + window.scrollX)),
      y: Math.max(0, Math.floor(r.top + window.scrollY)),
      width: Math.ceil(r.width),
      height: Math.ceil(r.height),
    };
  })()`);
  if (!rect || rect.width === 0 || rect.height === 0) {
    throw new Error(`Could not get bounding rect for ${selector}`);
  }
  const shot = await cdp.send('Page.captureScreenshot', {
    format: 'png',
    captureBeyondViewport: true,
    clip: {
      x: rect.x,
      y: rect.y,
      width: rect.width,
      height: rect.height,
      scale: 1,
    },
  });
  return shot.data;
}

async function extractSectionMetrics(cdp, selector, isWp) {
  return cdp.eval(`(() => {
    const el = document.querySelector(${JSON.stringify(selector)});
    if (!el) return null;
    const cs = getComputedStyle(el);
    const rect = el.getBoundingClientRect();

    const h1OrH2 = el.querySelector('h1, h2');
    const hCS = h1OrH2 ? getComputedStyle(h1OrH2) : null;

    const h3 = el.querySelector('h3, h4');
    const h3CS = h3 ? getComputedStyle(h3) : null;

    const container = ${isWp ? 'el.querySelector(".jn-container") || el' : 'el.querySelector(".max-w-\\\\[1360px\\\\]") || el'};
    const cCS = getComputedStyle(container);
    const cRect = container.getBoundingClientRect();

    const input = el.querySelector('input[type="text"], input[type="tel"], select');
    const inputRect = input ? input.getBoundingClientRect() : null;

    return {
      width: Math.round(rect.width),
      height: Math.round(rect.height),
      paddingTop: parseFloat(cs.paddingTop),
      paddingBottom: parseFloat(cs.paddingBottom),
      backgroundColor: cs.backgroundColor,
      containerWidth: Math.round(cRect.width),
      containerPaddingLeft: parseFloat(cCS.paddingLeft),
      containerPaddingRight: parseFloat(cCS.paddingRight),
      headingFontSize: hCS ? parseFloat(hCS.fontSize) : null,
      headingLineHeight: hCS ? parseFloat(hCS.lineHeight) : null,
      headingColor: hCS ? hCS.color : null,
      h3FontSize: h3CS ? parseFloat(h3CS.fontSize) : null,
      h3LineHeight: h3CS ? parseFloat(h3CS.lineHeight) : null,
      inputHeight: inputRect ? Math.round(inputRect.height) : null,
    };
  })()`);
}

async function stitchSideBySide(cdp, wfB64, wpB64, label, outPath) {
  await cdp.send('Emulation.setDeviceMetricsOverride', {
    width: 3000,
    height: 2400,
    deviceScaleFactor: 1,
    mobile: false,
  });

  const html = `<!DOCTYPE html>
  <html>
  <head>
    <meta charset="utf-8" />
    <style>
      body { margin: 0; padding: 24px; background: #1F1E1D; color: #FAF8F5; font-family: system-ui, sans-serif; display: inline-block; }
      .header { font-size: 16px; font-weight: 600; margin-bottom: 16px; letter-spacing: 0.04em; color: #AC8E76; }
      .grid { display: flex; gap: 24px; align-items: flex-start; }
      .col { display: flex; flex-direction: column; gap: 8px; }
      .col-title { font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em; color: #FAF8F5; background: rgba(255,255,255,0.08); padding: 6px 12px; border-radius: 4px; }
      img { display: block; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; max-width: none; }
    </style>
  </head>
  <body>
    <div id="wrap" style="display:inline-block;">
      <div class="header">${label}</div>
      <div class="grid">
        <div class="col">
          <div class="col-title">Wireframe (code.html)</div>
          <img id="wf" src="data:image/png;base64,${wfB64}" />
        </div>
        <div class="col">
          <div class="col-title">WordPress (/coaching/)</div>
          <img id="wp" src="data:image/png;base64,${wpB64}" />
        </div>
      </div>
    </div>
  </body>
  </html>`;

  const dataUrl = 'data:text/html;charset=utf-8,' + encodeURIComponent(html);
  await cdp.send('Page.navigate', { url: dataUrl });
  await sleep(250);
  await cdp.eval(`(async () => {
    const imgs = Array.from(document.querySelectorAll('img'));
    await Promise.all(imgs.map((img) => (img.complete ? Promise.resolve() : new Promise((r) => (img.onload = r)))));
  })()`);
  const b64 = await captureElementBase64(cdp, 'body');
  fs.writeFileSync(outPath, Buffer.from(b64, 'base64'));
}

async function navigateAndReady(cdp, url) {
  await cdp.send('Page.navigate', { url });
  await sleep(1200);
  await cdp.eval(`(async () => {
    document.querySelectorAll('img[loading="lazy"]').forEach((img) => img.setAttribute('loading', 'eager'));
    const imgs = Array.from(document.querySelectorAll('img'));
    await Promise.all(imgs.map((img) => (img.complete ? Promise.resolve() : new Promise((r) => { img.onload = r; img.onerror = r; }))));
    if (document.fonts && document.fonts.ready) {
      await document.fonts.ready;
    }
    // Disable fixed header from covering scrolled/clipped sections during section screenshots
    const hdr = document.querySelector('header');
    if (hdr) hdr.style.position = 'relative';
    const main = document.querySelector('main');
    if (main) main.style.paddingTop = '0px';
  })()`);
  await sleep(300);
}

async function run() {
  const server = await startStaticServer(WIREFRAME_DIR, 8081);
  const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), 'jn-step3-parity-'));
  const debugPort = 9335;

  const chrome = spawn(
    CHROME_BIN,
    [
      '--headless=new',
      '--disable-gpu',
      '--no-first-run',
      '--no-default-browser-check',
      '--window-size=1440,1200',
      `--remote-debugging-port=${debugPort}`,
      `--user-data-dir=${userDataDir}`,
      'about:blank',
    ],
    { stdio: 'ignore' }
  );

  const report = {};

  try {
    let targets = null;
    for (let i = 0; i < 30; i++) {
      try {
        const r = await fetch(`http://127.0.0.1:${debugPort}/json/list`);
        targets = await r.json();
        if (targets && targets.length > 0) break;
      } catch {}
      await sleep(200);
    }
    const pageTarget = targets.find((t) => t.type === 'page');
    const cdp = new CDPClient(pageTarget.webSocketDebuggerUrl);
    await cdp.connect();

    await cdp.send('Page.enable');
    await cdp.send('Runtime.enable');
    await cdp.send('DOM.enable');
    await cdp.send('CSS.enable');

    for (const vw of VIEWPORTS) {
      report[vw] = {};
      const wfData = {};
      const wpData = {};

      // 1. Capture Wireframe at vw
      await cdp.send('Emulation.setDeviceMetricsOverride', {
        width: vw,
        height: 1200,
        deviceScaleFactor: 1,
        mobile: vw < 768,
      });
      await navigateAndReady(cdp, 'http://127.0.0.1:8081/code.html');

      for (const sec of SECTIONS) {
        wfData[sec.id] = {
          metrics: await extractSectionMetrics(cdp, sec.wfSelector, false),
          b64: await captureElementBase64(cdp, sec.wfSelector),
        };
      }

      // 2. Capture WordPress `/coaching/` at vw
      await cdp.send('Emulation.setDeviceMetricsOverride', {
        width: vw,
        height: 1200,
        deviceScaleFactor: 1,
        mobile: vw < 768,
      });
      await navigateAndReady(cdp, 'http://localhost:8080/coaching/');

      for (const sec of SECTIONS) {
        wpData[sec.id] = {
          metrics: await extractSectionMetrics(cdp, sec.wpSelector, true),
          b64: await captureElementBase64(cdp, sec.wpSelector),
        };
      }

      // 3. Stitch side-by-side & compute diffs
      for (const sec of SECTIONS) {
        const wf = wfData[sec.id].metrics;
        const wp = wpData[sec.id].metrics;
        const pairPath = path.join(OUT_DIR, `parity-${sec.id}-${vw}px.png`);
        await stitchSideBySide(cdp, wfData[sec.id].b64, wpData[sec.id].b64, `${sec.name} @ ${vw}px`, pairPath);

        const diffs = {
          paddingTopDiff: wf && wp ? wp.paddingTop - wf.paddingTop : null,
          paddingBottomDiff: wf && wp ? wp.paddingBottom - wf.paddingBottom : null,
          containerPaddingLeftDiff: wf && wp ? wp.containerPaddingLeft - wf.containerPaddingLeft : null,
          headingFontSizeDiff:
            wf && wp && wf.headingFontSize && wp.headingFontSize ? wp.headingFontSize - wf.headingFontSize : null,
          h3FontSizeDiff: wf && wp && wf.h3FontSize && wp.h3FontSize ? wp.h3FontSize - wf.h3FontSize : null,
          heightDiff: wf && wp ? wp.height - wf.height : null,
        };

        report[vw][sec.id] = { wf, wp, diffs };
      }
    }

    fs.writeFileSync(path.join(OUT_DIR, 'parity-report.json'), JSON.stringify(report, null, 2));

    console.log('=== VISUAL PARITY METRICS SUMMARY (375 / 768 / 1024 / 1440) ===');
    for (const vw of VIEWPORTS) {
      console.log(`\n--- Viewport ${vw}px ---`);
      for (const sec of SECTIONS) {
        const item = report[vw][sec.id];
        const { wf, wp, diffs } = item;
        console.log(
          `  [${sec.id}] padTop: WF=${wf?.paddingTop} WP=${wp?.paddingTop} (Δ=${diffs.paddingTopDiff}) | ` +
            `padBot: WF=${wf?.paddingBottom} WP=${wp?.paddingBottom} (Δ=${diffs.paddingBottomDiff}) | ` +
            `padX: WF=${wf?.containerPaddingLeft} WP=${wp?.containerPaddingLeft} (Δ=${diffs.containerPaddingLeftDiff}) | ` +
            `hFont: WF=${wf?.headingFontSize} WP=${wp?.headingFontSize} (Δ=${diffs.headingFontSizeDiff}) | ` +
            `h3Font: WF=${wf?.h3FontSize} WP=${wp?.h3FontSize} (Δ=${diffs.h3FontSizeDiff}) | ` +
            `H: WF=${wf?.height} WP=${wp?.height}`
        );
      }
    }

    cdp.close();
  } finally {
    chrome.kill('SIGKILL');
    server.close();
    fs.rmSync(userDataDir, { recursive: true, force: true });
  }
}

run().catch((err) => {
  console.error(err);
  process.exit(1);
});
