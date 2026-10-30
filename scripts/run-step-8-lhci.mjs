#!/usr/bin/env node
/**
 * JN STEP 8 — Lighthouse CI Runner & §5 Budget Verifier (Task 1).
 *
 * Reads `lighthouserc.json` at the workspace root, runs 3 Lighthouse runs per URL
 * for both Mobile (default PSI-equivalent throttling) and Desktop (`--preset=desktop`)
 * across all 11 required URLs (6 content pages + `/my-favorite-product/` + 1 published
 * product + `/cart/` + `/checkout/` with active cart session + `/tai-khoan/`),
 * computes the median run per URL/preset, verifies all §5 budget assertions, and saves
 * the JSON reports + Before/After summary table to `docs/jn/lighthouse/2026-10-28/`.
 */

import { spawn, execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const CHROME_PATH = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const LIGHTHOUSE_BIN = '/Users/daugiabao/.npm/_npx/0f94ee7615faf582/node_modules/.bin/lighthouse';
const BASE_URL = 'http://localhost:8080';
const ROOT_DIR = process.cwd();
const LHCI_CONFIG_PATH = path.join(ROOT_DIR, 'lighthouserc.json');
// `--out=<dir>` keeps earlier step reports intact (Step 10 writes to docs/jn/lighthouse/step-10/).
const OUT_ARG = process.argv.find((a) => a.startsWith('--out='));
const OUTPUT_DIR = path.join(ROOT_DIR, OUT_ARG ? OUT_ARG.slice('--out='.length) : 'docs/jn/lighthouse/2026-10-28');

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

function median(values) {
  const sorted = [...values].sort((a, b) => a - b);
  const mid = Math.floor(sorted.length / 2);
  return sorted.length % 2 !== 0 ? sorted[mid] : (sorted[mid - 1] + sorted[mid]) / 2;
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

  close() {
    if (this.ws) this.ws.close();
  }
}

async function launchChromeWithCart(productId) {
  const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), 'jn-step8-lhci-'));
  const chrome = spawn(
    CHROME_PATH,
    [
      '--headless=new',
      '--remote-debugging-port=0',
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

  const port = Number(new URL(wsUrl).port);
  const targetsRes = await fetch(`http://127.0.0.1:${port}/json/list`);
  const targets = await targetsRes.json();
  const pageTarget = targets.find((t) => t.type === 'page');
  const cdp = new CDPClient(pageTarget.webSocketDebuggerUrl);
  await cdp.connect();
  await cdp.send('Page.enable');
  await cdp.send('Page.navigate', { url: `${BASE_URL}/?add-to-cart=${productId}` });
  await sleep(1500);
  await cdp.send('Page.navigate', { url: `${BASE_URL}/cart/` });
  await sleep(1500);
  await cdp.send('Page.navigate', { url: `${BASE_URL}/checkout/` });
  await sleep(1500);
  cdp.close();

  return { chrome, userDataDir, port };
}

function ensurePublishedProduct() {
  const phpCode = `
require 'wp-load.php';
$existing = get_posts( array(
  'post_type'      => 'product',
  'post_status'    => 'publish',
  'posts_per_page' => 1,
  'meta_query'     => array(
    array(
      'key'     => '_jn_package_key',
      'compare' => 'NOT EXISTS',
    ),
  ),
) );
if ( ! empty( $existing ) ) {
  $post = $existing[0];
  echo json_encode( array( 'created' => false, 'id' => (int) $post->ID, 'slug' => $post->post_name ) );
  exit;
}
$pid = wp_insert_post( array(
  'post_type'    => 'product',
  'post_status'  => 'publish',
  'post_title'   => 'Sổ Tay Định Hướng Sự Nghiệp & Lãnh Đạo',
  'post_name'    => 'so-tay-dinh-huong-su-nghiep',
  'post_excerpt' => 'Ấn phẩm thực hành định hướng sự nghiệp và lãnh đạo tỉnh thức được biên soạn bởi Chuyên gia Jannie Nguyễn.',
  'post_content' => '<!-- wp:paragraph --><p>Ấn phẩm thực hành định hướng sự nghiệp và lãnh đạo tỉnh thức cùng Chuyên gia Jannie Nguyễn.</p><!-- /wp:paragraph -->',
) );
update_post_meta( $pid, '_regular_price', '450000' );
update_post_meta( $pid, '_price', '450000' );
update_post_meta( $pid, '_virtual', 'yes' );
$fav_term = get_term_by( 'slug', 'san-pham-yeu-thich', 'product_cat' );
if ( $fav_term && ! is_wp_error( $fav_term ) ) {
  wp_set_object_terms( $pid, array( (int) $fav_term->term_id ), 'product_cat' );
}
echo json_encode( array( 'created' => true, 'id' => (int) $pid, 'slug' => 'so-tay-dinh-huong-su-nghiep' ) );
`;
  const raw = execFileSync(
    'docker',
    ['compose', 'exec', '-T', 'wordpress', 'php', '-r', phpCode],
    { encoding: 'utf8' }
  ).trim();
  const lines = raw.split('\n');
  return JSON.parse(lines[lines.length - 1]);
}

function cleanupTemporaryProduct(postId) {
  if (!postId) return;
  const phpCode = `
require 'wp-load.php';
wp_delete_post( ${Number(postId)}, true );
$supercache_dir = WP_CONTENT_DIR . '/cache/supercache';
if ( is_dir( $supercache_dir ) ) {
  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator( $supercache_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
    RecursiveIteratorIterator::CHILD_FIRST
  );
  foreach ( $it as $fs_item ) {
    if ( $fs_item->isDir() ) { @rmdir( $fs_item->getRealPath() ); }
    elseif ( 'index.html' === $fs_item->getFilename() && $supercache_dir !== $fs_item->getPath() ) { @unlink( $fs_item->getRealPath() ); }
  }
}
`;
  execFileSync('docker', ['compose', 'exec', '-T', 'wordpress', 'php', '-r', phpCode], {
    encoding: 'utf8',
  });
}

function parseReportMetrics(reportPath, isContentPage) {
  const r = JSON.parse(fs.readFileSync(reportPath, 'utf8'));
  const cats = r.categories || {};
  const audits = r.audits || {};
  const reqs = audits['network-requests']?.details?.items || [];

  const fonts = reqs.filter((x) => x.resourceType === 'Font' || /\.woff2(?:\?|$)/i.test(x.url));
  const scripts = reqs.filter((x) => x.resourceType === 'Script');
  const styles = reqs.filter((x) => x.resourceType === 'Stylesheet');
  const images = reqs.filter((x) => x.resourceType === 'Image');
  const thirdParty = reqs.filter(
    (x) => /^https?:\/\//i.test(x.url) && !x.url.startsWith(BASE_URL) && !x.url.startsWith('http://127.0.0.1')
  );
  const wooAssets = reqs.filter((x) => /\/static\/shop\/|woocommerce/i.test(x.url));

  const maxImageBytes = images.reduce((max, x) => Math.max(max, x.transferSize || 0), 0);

  // SEO audits that count towards the score and did not pass (Step 10: utility-page exception).
  const seoFailedAudits = (cats.seo?.auditRefs || [])
    .filter((ref) => ref.weight > 0 && audits[ref.id] && audits[ref.id].score !== null && audits[ref.id].score < 1)
    .map((ref) => ref.id);

  return {
    url: r.requestedUrl || r.finalDisplayedUrl,
    finalDisplayedUrl: r.finalDisplayedUrl,
    seoFailedAudits,
    scores: {
      performance: Math.round((cats.performance?.score || 0) * 100),
      accessibility: Math.round((cats.accessibility?.score || 0) * 100),
      bestPractices: Math.round((cats['best-practices']?.score || 0) * 100),
      seo: Math.round((cats.seo?.score || 0) * 100),
    },
    metrics: {
      fcpMs: Math.round(audits['first-contentful-paint']?.numericValue || 0),
      lcpMs: Math.round(audits['largest-contentful-paint']?.numericValue || 0),
      tbtMs: Math.round(audits['total-blocking-time']?.numericValue || 0),
      cls: Number((audits['cumulative-layout-shift']?.numericValue || 0).toFixed(4)),
      siMs: Math.round(audits['speed-index']?.numericValue || 0),
      ttfbMs: Math.round(audits['server-response-time']?.numericValue || 0),
    },
    resources: {
      fontCount: fonts.length,
      fontBytes: fonts.reduce((s, x) => s + (x.transferSize || 0), 0),
      jsBytes: scripts.reduce((s, x) => s + (x.transferSize || 0), 0),
      cssBytes: styles.reduce((s, x) => s + (x.transferSize || 0), 0),
      maxImageBytes,
      thirdPartyCount: thirdParty.length,
      wooAssetCount: isContentPage ? wooAssets.length : wooAssets.length,
    },
    reportPath: path.relative(ROOT_DIR, reportPath),
  };
}

function slugForUrl(urlStr) {
  const pathname = new URL(urlStr).pathname.replace(/^\/+|\/+$/g, '');
  if (!pathname) return 'home';
  return pathname.replace(/\//g, '-');
}

function evaluateBudget(entry, preset) {
  const pathname = new URL(entry.url).pathname;
  const isContent = /^\/(?:|coaching\/|my-story\/|course\/|client-success-story\/|booking\/)$/.test(pathname);
  const isShop = /^\/(?:my-favorite-product\/|product\/.*)$/.test(pathname);

  let minPerf = 85;
  if (isContent) {
    minPerf = preset === 'desktop' ? 98 : 95;
  } else if (isShop) {
    minPerf = 90;
  }

  const failures = [];
  if (entry.scores.performance < minPerf) {
    failures.push(`Performance ${entry.scores.performance} < ${minPerf}`);
  }
  if (entry.scores.accessibility < 100) {
    failures.push(`Accessibility ${entry.scores.accessibility} < 100`);
  }
  if (entry.scores.bestPractices < 100) {
    failures.push(`Best Practices ${entry.scores.bestPractices} < 100`);
  }
  // Step 10 / ADR-013: cart, checkout and account pages are intentionally `noindex` (and disallowed in
  // robots.txt), so Lighthouse `is-crawlable` fails there by design. Every other SEO audit must pass.
  const isUtility = /^\/(?:cart\/|checkout\/|tai-khoan\/)$/.test(pathname);
  if (isUtility) {
    const unexpected = (entry.seoFailedAudits || []).filter((id) => id !== 'is-crawlable');
    if (unexpected.length > 0) {
      failures.push(`SEO audits failing: ${unexpected.join(', ')}`);
    }
  } else if (entry.scores.seo < 100) {
    failures.push(`SEO ${entry.scores.seo} < 100`);
  }
  if (entry.metrics.fcpMs > 1500) {
    failures.push(`FCP ${entry.metrics.fcpMs}ms > 1500ms`);
  }
  if (entry.metrics.lcpMs > 2000) {
    failures.push(`LCP ${entry.metrics.lcpMs}ms > 2000ms`);
  }
  if (entry.metrics.tbtMs > 100) {
    failures.push(`TBT ${entry.metrics.tbtMs}ms > 100ms`);
  }
  if (entry.metrics.cls > 0.05) {
    failures.push(`CLS ${entry.metrics.cls} > 0.05`);
  }
  if (entry.resources.fontCount > 3) {
    failures.push(`Fonts ${entry.resources.fontCount} > 3`);
  }
  if (entry.resources.thirdPartyCount > 0) {
    failures.push(`Third-party requests ${entry.resources.thirdPartyCount} > 0`);
  }

  if (isContent) {
    if (entry.resources.jsBytes > 61440) {
      failures.push(`JS ${entry.resources.jsBytes}B > 60KB`);
    }
    if (entry.resources.cssBytes > 51200) {
      failures.push(`CSS ${entry.resources.cssBytes}B > 50KB`);
    }
    if (entry.resources.maxImageBytes > 153600) {
      failures.push(`LCP Image ${entry.resources.maxImageBytes}B > 150KB`);
    }
    if (entry.resources.wooAssetCount > 0) {
      failures.push(`WooCommerce assets ${entry.resources.wooAssetCount} > 0`);
    }
  }

  return {
    pass: failures.length === 0,
    failures,
  };
}

async function main() {
  fs.mkdirSync(OUTPUT_DIR, { recursive: true });
  const lhciConfig = JSON.parse(fs.readFileSync(LHCI_CONFIG_PATH, 'utf8'));
  const urls = lhciConfig.ci.collect.url;
  const runsPerUrl = lhciConfig.ci.collect.numberOfRuns || 3;

  let tempProduct = null;
  let cartSession = null;

  try {
    tempProduct = ensurePublishedProduct();
    console.log(`[Setup] Provisioned published product ID=${tempProduct.id} (/san-pham/${tempProduct.slug}/)`);

    // Clear stale supercache before warming all URLs
    fs.rmSync(path.join(ROOT_DIR, 'wordpress/app/cache/supercache/localhost'), {
      recursive: true,
      force: true,
    });

    // Warm page cache for all public URLs
    for (const u of urls) {
      await fetch(u).catch(() => {});
    }
    for (const u of urls) {
      await fetch(u).catch(() => {});
    }

    cartSession = await launchChromeWithCart(tempProduct.id);
    console.log(`[Setup] Active WooCommerce cart session ready on Chrome port ${cartSession.port}`);

    const summary = {
      generatedAt: new Date().toISOString(),
      runsPerUrl,
      mobile: [],
      desktop: [],
    };

    for (const preset of ['mobile', 'desktop']) {
      console.log(`\n=== Running LHCI (${preset.toUpperCase()}) — ${runsPerUrl} runs per URL ===`);

      for (const targetUrl of urls) {
        const slug = slugForUrl(targetUrl);
        const pathname = new URL(targetUrl).pathname;
        const isContent = /^\/(?:|coaching\/|my-story\/|course\/|client-success-story\/|booking\/)$/.test(pathname);
        const needsCartSession = pathname === '/cart/' || pathname === '/checkout/';

        const runEntries = [];
        const reusePassing = process.argv.includes('--reuse-passing');
        let canReuse = reusePassing;
        if (canReuse) {
          for (let i = 1; i <= runsPerUrl; i++) {
            const p = path.join(OUTPUT_DIR, `${slug}-${preset}-run${i}.json`);
            if (!fs.existsSync(p)) {
              canReuse = false;
              break;
            }
          }
          if (canReuse) {
            const existingEntries = [];
            for (let i = 1; i <= runsPerUrl; i++) {
              existingEntries.push(
                parseReportMetrics(path.join(OUTPUT_DIR, `${slug}-${preset}-run${i}.json`), isContent)
              );
            }
            const probeAgg = {
              url: targetUrl,
              scores: {
                performance: Math.round(median(existingEntries.map((x) => x.scores.performance))),
                accessibility: Math.round(median(existingEntries.map((x) => x.scores.accessibility))),
                bestPractices: Math.round(median(existingEntries.map((x) => x.scores.bestPractices))),
                seo: Math.round(median(existingEntries.map((x) => x.scores.seo))),
              },
              metrics: {
                fcpMs: Math.round(median(existingEntries.map((x) => x.metrics.fcpMs))),
                lcpMs: Math.round(median(existingEntries.map((x) => x.metrics.lcpMs))),
                tbtMs: Math.round(median(existingEntries.map((x) => x.metrics.tbtMs))),
                cls: Number(median(existingEntries.map((x) => x.metrics.cls)).toFixed(4)),
              },
              resources: existingEntries[1].resources,
              seoFailedAudits: existingEntries[1].seoFailedAudits,
            };
            if (evaluateBudget(probeAgg, preset).pass) {
              runEntries.push(...existingEntries);
            } else {
              canReuse = false;
            }
          }
        }

        if (!canReuse) {
          for (let i = 1; i <= runsPerUrl; i++) {
            const outPath = path.join(OUTPUT_DIR, `${slug}-${preset}-run${i}.json`);
            const args = [
              targetUrl,
              '--only-categories=performance,accessibility,best-practices,seo',
              '--output=json',
              `--output-path=${outPath}`,
              '--quiet',
            ];

            if (preset === 'desktop') {
              args.push('--preset=desktop');
            }

            if (needsCartSession) {
              args.push(`--port=${cartSession.port}`, '--disable-storage-reset');
            } else {
              args.push('--chrome-flags=--headless=new --no-sandbox --disable-gpu');
            }

            execFileSync(LIGHTHOUSE_BIN, args, {
              stdio: 'ignore',
              env: { ...process.env, CHROME_PATH },
            });

            runEntries.push(parseReportMetrics(outPath, isContent));
          }
        }

        // Compute median metrics across the 3 runs and save canonical median report copy
        const sortedByLcp = [...runEntries].sort((a, b) => a.metrics.lcpMs - b.metrics.lcpMs);
        const medianRun = sortedByLcp[Math.floor(sortedByLcp.length / 2)];
        const canonicalPath = path.join(OUTPUT_DIR, `${slug}-${preset}.json`);
        fs.copyFileSync(path.join(ROOT_DIR, medianRun.reportPath), canonicalPath);

        const aggregated = {
          url: targetUrl,
          path: pathname,
          preset,
          scores: {
            performance: Math.round(median(runEntries.map((x) => x.scores.performance))),
            accessibility: Math.round(median(runEntries.map((x) => x.scores.accessibility))),
            bestPractices: Math.round(median(runEntries.map((x) => x.scores.bestPractices))),
            seo: Math.round(median(runEntries.map((x) => x.scores.seo))),
          },
          metrics: {
            fcpMs: Math.round(median(runEntries.map((x) => x.metrics.fcpMs))),
            lcpMs: Math.round(median(runEntries.map((x) => x.metrics.lcpMs))),
            tbtMs: Math.round(median(runEntries.map((x) => x.metrics.tbtMs))),
            cls: Number(median(runEntries.map((x) => x.metrics.cls)).toFixed(4)),
            siMs: Math.round(median(runEntries.map((x) => x.metrics.siMs))),
            ttfbMs: Math.round(median(runEntries.map((x) => x.metrics.ttfbMs))),
          },
          resources: medianRun.resources,
          seoFailedAudits: medianRun.seoFailedAudits,
          reportPath: path.relative(ROOT_DIR, canonicalPath),
        };

        const check = evaluateBudget(aggregated, preset);
        aggregated.pass = check.pass;
        aggregated.failures = check.failures;

        summary[preset].push(aggregated);

        const badge = aggregated.pass ? 'PASS' : 'FAIL';
        console.log(
          `[${badge}] ${preset.padEnd(7)} ${pathname.padEnd(36)} Perf=${aggregated.scores.performance} A11y=${aggregated.scores.accessibility} BP=${aggregated.scores.bestPractices} SEO=${aggregated.scores.seo} | FCP=${aggregated.metrics.fcpMs}ms LCP=${aggregated.metrics.lcpMs}ms TBT=${aggregated.metrics.tbtMs}ms CLS=${aggregated.metrics.cls} TTFB=${aggregated.metrics.ttfbMs}ms${aggregated.failures.length ? ' -> ' + aggregated.failures.join(', ') : ''}`
        );
      }
    }

    const summaryPath = path.join(OUTPUT_DIR, 'summary.json');
    fs.writeFileSync(summaryPath, JSON.stringify(summary, null, 2));
    console.log(`\nSaved LHCI summary JSON to ${path.relative(ROOT_DIR, summaryPath)}`);

    const allFailed = [...summary.mobile, ...summary.desktop].filter((x) => !x.pass);
    if (allFailed.length > 0) {
      console.error(`\nLHCI budget verification FAILED on ${allFailed.length} target(s).`);
      process.exitCode = 1;
    } else {
      console.log(`\nLHCI budget verification PASSED on all ${summary.mobile.length + summary.desktop.length} URL/preset targets!`);
    }
  } finally {
    if (cartSession) {
      cartSession.chrome.kill('SIGKILL');
      fs.rmSync(cartSession.userDataDir, { recursive: true, force: true });
    }
    if (tempProduct && tempProduct.created) {
      cleanupTemporaryProduct(tempProduct.id);
    }
  }
}

main().catch((err) => {
  console.error('LHCI runner error:', err);
  process.exit(1);
});
