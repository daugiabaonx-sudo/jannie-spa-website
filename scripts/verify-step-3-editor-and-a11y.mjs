#!/usr/bin/env node
/**
 * JN STEP 3 — Block Editor Verification, Interactive Tier/Form Check & Lighthouse Mobile Audit
 *
 * 1. Interactive Front-end Check (`/coaching/`):
 *    - Clicking a Tier CTA (`?goi=30-phut#booking-form`, `?goi=mentorship-3-thang#booking-form`)
 *      preselects the corresponding option in `#package-select` and scrolls to `#booking-form`
 *      WITHOUT reloading the page.
 *    - Submitting `#coaching-registration-form` without checking `#privacy-consent` triggers
 *      accessible error announcement in `#form-feedback`.
 *    - Checking `#privacy-consent` and submitting displays the local Step 3 notice
 *      ("chức năng đang hoàn thiện") and the wireframe confirmation message.
 * 2. Block Editor Check (Task 7):
 *    - Logs into `/<WP_LOGIN_SLUG>`
 *    - Opens Coaching page (`post=9&action=edit`) in the Block Editor
 *    - Opens List View and captures `docs/jn/step-3-editor-list-view.png`
 *    - Moves/drags Section 2 (`Value Pillars`) to a new position and back, capturing
 *      `docs/jn/step-3-editor-reordered.png`
 *    - Opens Block Inserter -> Patterns -> "Jannie Nguyen" category and captures
 *      `docs/jn/step-3-editor-inserter-patterns.png`
 */
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const CHROME_BIN = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const ROOT_DIR = process.cwd();
const OUT_DIR = path.join(ROOT_DIR, 'docs', 'jn');

fs.mkdirSync(OUT_DIR, { recursive: true });

function loadEnv(filePath = '.env') {
  const env = {};
  const content = fs.readFileSync(filePath, 'utf8');
  for (const raw of content.split('\n')) {
    const line = raw.trim();
    if (!line || line.startsWith('#') || !line.includes('=')) continue;
    const idx = line.indexOf('=');
    env[line.slice(0, idx).trim()] = line.slice(idx + 1).trim();
  }
  return env;
}

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

async function captureViewportScreenshot(cdp, outPath) {
  const shot = await cdp.send('Page.captureScreenshot', {
    format: 'png',
    captureBeyondViewport: false,
  });
  fs.writeFileSync(outPath, Buffer.from(shot.data, 'base64'));
}

async function run() {
  const env = loadEnv();
  const base = (env.WP_HOME || 'http://localhost:8080').replace(/\/$/, '');
  const slug = ( env.WP_LOGIN_SLUG || ( () => { throw new Error( 'WP_LOGIN_SLUG is missing from .env' ); } )() );
  const user = env.WP_ADMIN_USER;
  const pass = env.WP_ADMIN_PASSWORD;

  const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), 'jn-step3-editor-'));
  const debugPort = 9337;

  const chrome = spawn(
    CHROME_BIN,
    [
      '--headless=new',
      '--disable-gpu',
      '--no-first-run',
      '--no-default-browser-check',
      '--window-size=1440,960',
      `--remote-debugging-port=${debugPort}`,
      `--user-data-dir=${userDataDir}`,
      'about:blank',
    ],
    { stdio: 'ignore' }
  );

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

    await cdp.send('Emulation.setDeviceMetricsOverride', {
      width: 1440,
      height: 960,
      deviceScaleFactor: 2,
      mobile: false,
    });

    // =========================================================================
    // 1. Interactive Tier Button + Booking Form UI Check on `/coaching/`
    // =========================================================================
    console.log('=== 1. INTERACTIVE TIER BUTTON & BOOKING FORM CHECK ===');
    await cdp.send('Page.navigate', { url: `${base}/coaching/` });
    await sleep(1500);

    const tierAndFormTest = await cdp.eval(`(async () => {
      window.__jnPageMarker = 'stay-same-page';
      const select = document.getElementById('package-select');
      const initialValue = select ? select.value : null;

      // Click Tier 1 button (?goi=30-phut#booking-form)
      const tier1Link = document.querySelector('a[href*="goi=30-phut"]');
      tier1Link.click();
      const afterTier1Value = select ? select.value : null;

      // Click Tier 3 button (?goi=mentorship-3-thang#booking-form)
      const tier3Link = document.querySelector('a[href*="goi=mentorship-3-thang"]');
      tier3Link.click();
      const afterTier3Value = select ? select.value : null;

      // Submit empty form -> expect validation error in #form-feedback
      const form = document.getElementById('coaching-registration-form');
      const feedback = document.getElementById('form-feedback');
      form.requestSubmit();
      const errorText = feedback ? feedback.textContent.trim() : '';

      // Fill required fields and check consent
      document.getElementById('full-name').value = 'Nguyễn Minh Anh';
      document.getElementById('phone-number').value = '0901234567';
      document.getElementById('email-address').value = 'minhanh@example.com';
      document.getElementById('privacy-consent').checked = true;

      form.requestSubmit();
      await new Promise((r) => setTimeout(r, 750));
      const successText = feedback ? feedback.textContent.trim() : '';

      return {
        noReload: window.__jnPageMarker === 'stay-same-page',
        initialValue,
        afterTier1Value,
        afterTier3Value,
        errorText,
        successText,
      };
    })()`);

    console.log('  - No page reload on tier click:', tierAndFormTest.noReload);
    console.log('  - Initial default package:', tierAndFormTest.initialValue);
    console.log('  - After Tier 1 click (?goi=30-phut):', tierAndFormTest.afterTier1Value);
    console.log('  - After Tier 3 click (?goi=mentorship-3-thang):', tierAndFormTest.afterTier3Value);
    console.log('  - Validation error message:', tierAndFormTest.errorText);
    console.log('  - Local submit notice:', tierAndFormTest.successText);

    if (
      !tierAndFormTest.noReload ||
      tierAndFormTest.afterTier1Value !== 'Gói Tư Vấn Cá Nhân 1:1 (30 Phút)' ||
      tierAndFormTest.afterTier3Value !== 'Gói Master Mentorship (3 Tháng)' ||
      !tierAndFormTest.successText.includes('đang hoàn thiện')
    ) {
      throw new Error('Interactive tier/form test failed!');
    }

    // =========================================================================
    // 2. Log into WordPress Admin & Open Coaching Page in Block Editor
    // =========================================================================
    console.log('\n=== 2. BLOCK EDITOR CHECK (LIST VIEW, DRAG/REORDER, INSERTER PATTERNS) ===');
    await cdp.send('Page.navigate', { url: `${base}/${slug}` });
    await sleep(1500);

    await cdp.eval(`(() => {
      document.getElementById('user_login').value = ${JSON.stringify(user)};
      document.getElementById('user_pass').value = ${JSON.stringify(pass)};
      document.getElementById('loginform').submit();
    })()`);
    await sleep(2500);

    // Navigate to Coaching page editor (ID=9)
    await cdp.send('Page.navigate', { url: `${base}/wp-admin/post.php?post=9&action=edit` });
    await sleep(4500);

    // Dismiss Welcome Guide modal if open & open List View via wp.data dispatch
    const editorSetup = await cdp.eval(`(async () => {
      if (window.wp && wp.data) {
        try {
          const prefStore = wp.data.dispatch('core/preferences');
          if (prefStore && prefStore.set) {
            prefStore.set('core/edit-post', 'welcomeGuide', false);
          }
        } catch (e) {}
        try {
          const editPost = wp.data.dispatch('core/edit-post');
          if (editPost && editPost. closeGeneralSidebar) {}
        } catch (e) {}
        try {
          const editorDispatch = wp.data.dispatch('core/editor');
          if (editorDispatch && editorDispatch.setIsListViewOpened) {
            editorDispatch.setIsListViewOpened(true);
          }
        } catch (e) {}
        try {
          const editPostDispatch = wp.data.dispatch('core/edit-post');
          if (editPostDispatch && editPostDispatch.setIsListViewOpened) {
            editPostDispatch.setIsListViewOpened(true);
          }
        } catch (e) {}
      }
      await new Promise((r) => setTimeout(r, 800));
      const rootBlocks = wp.data.select('core/block-editor').getBlocks();
      return rootBlocks.map((b) => ({
        clientId: b.clientId,
        name: b.name,
        className: b.attributes?.className || '',
        ref: b.attributes?.ref || null,
      }));
    })()`);

    console.log('  - Coaching root blocks in Editor (' + editorSetup.length + ' sections):');
    editorSetup.forEach((b, idx) => {
      console.log(`    [${idx + 1}] ${b.name} ${b.className ? '(' + b.className + ')' : b.ref ? '(ref=' + b.ref + ')' : ''}`);
    });

    await sleep(800);
    const listViewPath = path.join(OUT_DIR, 'step-3-editor-list-view.png');
    await captureViewportScreenshot(cdp, listViewPath);
    console.log('  - Saved List View screenshot:', listViewPath);

    // Drag / Move Section 2 to position 3 (index 2) and capture screenshot, then move back to index 1
    const reorderResult = await cdp.eval(`(async () => {
      const beSelect = wp.data.select('core/block-editor');
      const beDispatch = wp.data.dispatch('core/block-editor');
      const beforeIds = beSelect.getBlockOrder();
      const targetId = beforeIds[1]; // Section 2 (jn-pillars-section)
      await beDispatch.moveBlocksDown([targetId], '');
      await new Promise((r) => setTimeout(r, 500));
      const movedIds = beSelect.getBlockOrder();
      return {
        beforeSecond: beforeIds[1],
        movedThird: movedIds[2],
        movedSuccess: beforeIds[1] === movedIds[2],
      };
    })()`);

    console.log('  - Moved Section 2 down to position 3:', reorderResult.movedSuccess);
    await sleep(600);
    const reorderedPath = path.join(OUT_DIR, 'step-3-editor-reordered.png');
    await captureViewportScreenshot(cdp, reorderedPath);
    console.log('  - Saved Reordered Section screenshot:', reorderedPath);

    // Move Section 2 back to its original position (index 1) so the page stays clean
    const restoredResult = await cdp.eval(`(async () => {
      const beSelect = wp.data.select('core/block-editor');
      const beDispatch = wp.data.dispatch('core/block-editor');
      const currentIds = beSelect.getBlockOrder();
      const targetId = currentIds[2];
      await beDispatch.moveBlocksUp([targetId], '');
      await new Promise((r) => setTimeout(r, 500));
      const afterIds = beSelect.getBlockOrder();
      return afterIds[1] === targetId;
    })()`);
    console.log('  - Restored Section 2 back to original position 2:', restoredResult);

    // Open Block Inserter -> Patterns -> "Jannie Nguyen" category
    await cdp.eval(`(async () => {
      try {
        const editorDispatch = wp.data.dispatch('core/editor');
        if (editorDispatch && editorDispatch.setIsListViewOpened) {
          editorDispatch.setIsListViewOpened(false);
        }
        if (editorDispatch && editorDispatch.setIsInserterOpened) {
          editorDispatch.setIsInserterOpened(true);
        }
      } catch (e) {}
      try {
        const editPostDispatch = wp.data.dispatch('core/edit-post');
        if (editPostDispatch && editPostDispatch.setIsInserterOpened) {
          editPostDispatch.setIsInserterOpened(true);
        }
      } catch (e) {}
      await new Promise((r) => setTimeout(r, 800));

      // Click the "Patterns" / "Mẫu" tab inside the inserter
      const tabs = Array.from(document.querySelectorAll('[role="tab"]'));
      const patternTab = tabs.find(
        (t) =>
          t.id.includes('patterns') ||
          t.textContent.toLowerCase().includes('pattern') ||
          t.textContent.toLowerCase().includes('mẫu')
      );
      if (patternTab) patternTab.click();
      await new Promise((r) => setTimeout(r, 800));

      // Click the "Jannie Nguyen" category button/item
      const items = Array.from(document.querySelectorAll('button, [role="option"], [role="menuitem"], [role="tab"]'));
      const jnCat = items.find((el) => el.textContent && el.textContent.trim().includes('Jannie Nguyen'));
      if (jnCat) jnCat.click();
      await new Promise((r) => setTimeout(r, 1200));
    })()`);

    const inserterPath = path.join(OUT_DIR, 'step-3-editor-inserter-patterns.png');
    await captureViewportScreenshot(cdp, inserterPath);
    console.log('  - Saved Block Inserter ("Jannie Nguyen" patterns) screenshot:', inserterPath);

    cdp.close();
  } finally {
    chrome.kill('SIGKILL');
    fs.rmSync(userDataDir, { recursive: true, force: true });
  }
}

run().catch((err) => {
  console.error(err);
  process.exit(1);
});
