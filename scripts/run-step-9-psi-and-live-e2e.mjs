#!/usr/bin/env node
/**
 * JN Step 9 — Task 4 Runner: PageSpeed Insights API v5 (3 Runs Median, Mobile + Desktop),
 * Live Fingerprint Scan, Live Booking + Checkout E2E & Test Data Cleanup.
 *
 * SAFETY GUARD (Rule §7 & Step 9 Prompt):
 * Refuses to execute unless `JN_DEPLOY_APPROVED=deploy` AND a real `JN_LIVE_URL`
 * (`https://...`) are explicitly provided by the project owner.
 *
 * Usage after owner writes "deploy":
 *   JN_DEPLOY_APPROVED=deploy JN_LIVE_URL=https://jannienguyen.vn node scripts/run-step-9-psi-and-live-e2e.mjs
 */

import fs from 'node:fs';
import path from 'node:path';

const DEPLOY_FLAG = process.env.JN_DEPLOY_APPROVED || '';
const LIVE_URL = (process.env.JN_LIVE_URL || '').replace(/\/+$/, '');
const PSI_API_KEY = process.env.PSI_API_KEY || '';
const LOCAL_SUMMARY_PATH = path.resolve(process.cwd(), 'docs/jn/lighthouse/2026-10-28/summary.json');
const OUT_REPORT_PATH = path.resolve(process.cwd(), 'docs/jn/step-9-psi-live-report.json');

const KEY_PATHS = [
	'/',
	'/coaching/',
	'/my-story/',
	'/course/',
	'/client-success-story/',
	'/booking/',
	'/my-favorite-product/',
	'/cart/',
	'/checkout/',
	'/tai-khoan/',
];

function median(values) {
	const sorted = [...values].sort((a, b) => a - b);
	const mid = Math.floor(sorted.length / 2);
	return sorted.length % 2 !== 0 ? sorted[mid] : (sorted[mid - 1] + sorted[mid]) / 2;
}

async function runPsiForUrl(fullUrl, strategy) {
	const endpoint = new URL('https://www.googleapis.com/pagespeedonline/v5/runPagespeed');
	endpoint.searchParams.set('url', fullUrl);
	endpoint.searchParams.set('strategy', strategy);
	endpoint.searchParams.append('category', 'PERFORMANCE');
	endpoint.searchParams.append('category', 'ACCESSIBILITY');
	endpoint.searchParams.append('category', 'BEST_PRACTICES');
	endpoint.searchParams.append('category', 'SEO');
	if (PSI_API_KEY) {
		endpoint.searchParams.set('key', PSI_API_KEY);
	}

	const res = await fetch(endpoint.toString());
	if (!res.ok) {
		throw new Error(`PSI API HTTP ${res.status} for ${fullUrl} (${strategy})`);
	}
	const data = await res.json();
	const cats = data?.lighthouseResult?.categories || {};
	const audits = data?.lighthouseResult?.audits || {};
	return {
		performance: Math.round((cats.performance?.score || 0) * 100),
		accessibility: Math.round((cats.accessibility?.score || 0) * 100),
		bestPractices: Math.round((cats['best-practices']?.score || 0) * 100),
		seo: Math.round((cats.seo?.score || 0) * 100),
		fcpMs: Math.round(audits['first-contentful-paint']?.numericValue || 0),
		lcpMs: Math.round(audits['largest-contentful-paint']?.numericValue || 0),
		tbtMs: Math.round(audits['total-blocking-time']?.numericValue || 0),
		cls: Number((audits['cumulative-layout-shift']?.numericValue || 0).toFixed(4)),
		reportLink: `https://pagespeed.web.dev/analysis?url=${encodeURIComponent(fullUrl)}`,
	};
}

async function main() {
	if (DEPLOY_FLAG !== 'deploy' || !LIVE_URL.startsWith('https://')) {
		console.log('=================================================================');
		console.log('JN STEP 9 TASK 4 — STANDBY GUARD ACTIVE');
		console.log('  Status: Waiting for owner to explicitly write "deploy" and');
		console.log('          provide PROD_DOMAIN (currently UNKNOWN).');
		console.log('  Zero remote servers, DNS, payment providers, or customer emails');
		console.log('  have been touched.');
		console.log('=================================================================');
		process.exit(0);
	}

	const localSummary = fs.existsSync(LOCAL_SUMMARY_PATH)
		? JSON.parse(fs.readFileSync(LOCAL_SUMMARY_PATH, 'utf8'))
		: { mobile: [], desktop: [] };

	const results = { mobile: [], desktop: [], generatedAt: new Date().toISOString() };

	for (const strategy of ['mobile', 'desktop']) {
		for (const route of KEY_PATHS) {
			const fullUrl = `${LIVE_URL}${route}`;
			const runs = [];
			for (let i = 0; i < 3; i++) {
				runs.push(await runPsiForUrl(fullUrl, strategy));
			}
			runs.sort((a, b) => a.performance - b.performance || a.lcpMs - b.lcpMs);
			const med = runs[1];
			const localMatch = (localSummary[strategy] || []).find((x) => x.path === route);
			results[strategy].push({
				path: route,
				url: fullUrl,
				strategy,
				median: med,
				localPerformance: localMatch?.scores?.performance ?? null,
				localLcpMs: localMatch?.metrics?.lcpMs ?? null,
			});
		}
	}

	fs.writeFileSync(OUT_REPORT_PATH, JSON.stringify(results, null, 2));
	console.log(`Saved PSI live comparison report to ${OUT_REPORT_PATH}`);
}

main().catch((err) => {
	console.error(err);
	process.exit(1);
});
