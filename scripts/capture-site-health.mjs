#!/usr/bin/env node
/**
 * JN STEP 1 — Automated Browser Login, Site Health Audit & Screenshot Capture
 * Uses host Google Chrome + native Node 24 WebSocket CDP (zero npm dependencies).
 */
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const CHROME_BIN = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

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
		const res = await this.send('Runtime.evaluate', {
			expression,
			returnByValue: true,
			awaitPromise: true,
		});
		return res?.result?.value;
	}
	close() {
		this.ws.close();
	}
}

async function main() {
	const env = loadEnv();
	const base = (env.WP_HOME || 'http://localhost:8080').replace(/\/$/, '');
	const slug = ( env.WP_LOGIN_SLUG || ( () => { throw new Error( 'WP_LOGIN_SLUG is missing from .env' ); } )() );
	const user = env.WP_ADMIN_USER;
	const pass = env.WP_ADMIN_PASSWORD;

	const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), 'jn-chrome-'));
	const debugPort = 9333;

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
		await cdp.send('Emulation.setDeviceMetricsOverride', {
			width: 1440,
			height: 1200,
			deviceScaleFactor: 2,
			mobile: false,
		});

		console.log(`1. Navigating to login page: ${base}/${slug}/`);
		await cdp.send('Page.navigate', { url: `${base}/${slug}/` });
		for (let i = 0; i < 30; i++) {
			const hasForm = await cdp.eval(`Boolean(document.querySelector('#loginform'))`);
			if (hasForm) break;
			await sleep(200);
		}

		console.log(`2. Submitting credentials for ${user}...`);
		await cdp.eval(`
			document.querySelector('#user_login').value = ${JSON.stringify(user)};
			document.querySelector('#user_pass').value = ${JSON.stringify(pass)};
			document.querySelector('#loginform').submit();
		`);

		for (let i = 0; i < 40; i++) {
			const url = await cdp.eval(`location.href`);
			const ready = await cdp.eval(`document.readyState`);
			if (url && url.includes('/wp-admin/') && ready === 'complete') break;
			await sleep(250);
		}

		console.log(`3. Navigating to Site Health: ${base}/wp-admin/site-health.php`);
		await cdp.send('Page.navigate', { url: `${base}/wp-admin/site-health.php` });

		// Wait for Site Health async checks to complete
		for (let i = 0; i < 60; i++) {
			await sleep(500);
			const state = await cdp.eval(`(() => {
				const progress = document.querySelector('.site-health-progress');
				const label = document.querySelector('.site-health-progress-label');
				const critical = document.querySelector('[data-issue-type="critical"] .issue-count');
				const recommended = document.querySelector('[data-issue-type="recommended"] .issue-count');
				if (!progress || !critical) return null;
				const loading = progress.classList.contains('loading');
				return {
					loading,
					label: label ? label.textContent.trim() : '',
					criticalCount: critical ? critical.textContent.trim() : '',
					recommendedCount: recommended ? recommended.textContent.trim() : '',
				};
			})()`);
			if (state && !state.loading && state.criticalCount !== '') {
				break;
			}
		}
		await sleep(500);

		const healthSummary = await cdp.eval(`(() => {
			const label = document.querySelector('.site-health-progress-label')?.textContent?.trim() || '';
			const criticalCount = document.querySelector('[data-issue-type="critical"] .issue-count')?.textContent?.trim() || '0';
			const recommendedCount = document.querySelector('[data-issue-type="recommended"] .issue-count')?.textContent?.trim() || '0';
			const items = Array.from(document.querySelectorAll('.health-check-accordion-heading')).map(el => ({
				title: el.querySelector('.title')?.textContent?.trim(),
				badge: el.querySelector('.badge')?.textContent?.trim(),
				section: el.closest('[data-issue-type]')?.getAttribute('data-issue-type') || 'passed',
			}));
			return { label, criticalCount, recommendedCount, items };
		})()`);

		console.log('=== Site Health Summary ===');
		console.log(JSON.stringify(healthSummary, null, 2));

		const shot = await cdp.send('Page.captureScreenshot', { format: 'png' });
		const siteHealthPath = path.resolve('docs/jn/step-1-site-health.png');
		fs.writeFileSync(siteHealthPath, Buffer.from(shot.data, 'base64'));
		console.log(`Saved Site Health screenshot: ${siteHealthPath}`);

		console.log('4. Navigating to Mailpit UI: http://localhost:8025/');
		await cdp.send('Page.navigate', { url: 'http://localhost:8025/' });
		await sleep(1500);
		await cdp.eval(`(() => {
			const firstMsg = document.querySelector('.message-page a, .list-group-item, tr.message');
			if (firstMsg) firstMsg.click();
		})()`);
		await sleep(1000);
		const mpShot = await cdp.send('Page.captureScreenshot', { format: 'png' });
		const mailpitPath = path.resolve('docs/jn/step-1-mailpit.png');
		fs.writeFileSync(mailpitPath, Buffer.from(mpShot.data, 'base64'));
		console.log(`Saved Mailpit screenshot: ${mailpitPath}`);

		cdp.close();
	} finally {
		chrome.kill('SIGTERM');
		try {
			fs.rmSync(userDataDir, { recursive: true, force: true });
		} catch {}
	}
}

main().catch((err) => {
	console.error(err);
	process.exit(1);
});
