#!/usr/bin/env node
/**
 * JN Step 6 — Comprehensive Browser E2E Verification (`scripts/verify-step-6.mjs`).
 *
 * Uses Google Chrome via CDP to verify and capture required screenshots:
 * 1. `docs/jn/step-6-staff-login-2fa.png` — Staff login (`tro_ly_lich_hen`) at `/<WP_LOGIN_SLUG>`
 *    requiring Two Factor (2FA) OTP, retrieving the 8-digit code from Mailpit, and completing login.
 * 2. `docs/jn/step-6-customers-list.png` — Unified "Khách hàng" (`admin.php?page=jn-customers`) directory
 *    showing customers from bookings, orders, and registrations, KPI strip, 9 columns, duplicate warnings, and CSV export.
 * 3. `docs/jn/step-6-customers-filters.png` — Filtering by type (`Chủ cơ sở`), tag (`VIP`),
 *    `has_pending_booking = 1`, and phone-format search (`0908 234 567` matching `+84908234567`).
 * 4. `docs/jn/step-6-customer-detail.png` — Customer detail page with 10 CRM fields, duplicate warning banner,
 *    "Tạo lịch hẹn" walk-in action, booking history, HPOS order history with links, internal notes timeline, and read-only consent log.
 */

import { spawn, execSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const CHROME_BIN = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const ROOT_DIR = process.cwd();
const DOCS_DIR = path.join(ROOT_DIR, 'docs/jn');
const ARTIFACT_DIR = '/Users/daugiabao/.gemini/antigravity/brain/8e2c1822-7ddc-4aa0-99ed-675943e71aad';
const REPORT_PATH = path.join(DOCS_DIR, 'step-6-verification.json');

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function loadEnv() {
	const envPath = path.join(ROOT_DIR, '.env');
	const env = {};
	if (fs.existsSync(envPath)) {
		const lines = fs.readFileSync(envPath, 'utf8').split(/\r?\n/);
		for (const line of lines) {
			const trimmed = line.trim();
			if (!trimmed || trimmed.startsWith('#')) continue;
			const idx = trimmed.indexOf('=');
			if (idx > 0) {
				env[trimmed.slice(0, idx).trim()] = trimmed.slice(idx + 1).trim();
			}
		}
	}
	return env;
}

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
		if (res?.exceptionDetails) {
			throw new Error(`Eval error: ${JSON.stringify(res.exceptionDetails)}`);
		}
		return res?.result?.value;
	}

	async navigateAndWait(url, waitMs = 2000) {
		await this.send('Page.navigate', { url });
		await sleep(waitMs);
	}

	async setViewport(width, height, mobile = false) {
		this.currentViewport = { width, height, mobile };
		await this.send('Emulation.setDeviceMetricsOverride', {
			width,
			height,
			deviceScaleFactor: 2,
			mobile,
		});
	}

	async screenshotBoth(filename, fullPage = false) {
		const outDocs = path.join(DOCS_DIR, filename);
		const outArtifact = path.join(ARTIFACT_DIR, filename);

		if (fullPage) {
			const vp = this.currentViewport || { width: 1440, height: 960, mobile: false };
			let contentHeight = vp.height;
			try {
				contentHeight = await this.eval(
					`Math.min(Math.max(document.documentElement.scrollHeight, document.body.scrollHeight, ${vp.height}), 4200)`
				);
			} catch {}
			await this.send('Emulation.setDeviceMetricsOverride', {
				width: vp.width,
				height: Math.ceil(contentHeight || vp.height),
				deviceScaleFactor: 2,
				mobile: vp.mobile,
			});
			await sleep(300);
			const shot = await this.send('Page.captureScreenshot', {
				format: 'png',
				captureBeyondViewport: false,
			});
			const buf = Buffer.from(shot.data, 'base64');
			fs.writeFileSync(outDocs, buf);
			if (fs.existsSync(ARTIFACT_DIR)) {
				fs.writeFileSync(outArtifact, buf);
			}
			await this.send('Emulation.setDeviceMetricsOverride', {
				width: vp.width,
				height: vp.height,
				deviceScaleFactor: 2,
				mobile: vp.mobile,
			});
		} else {
			const shot = await this.send('Page.captureScreenshot', {
				format: 'png',
				captureBeyondViewport: false,
			});
			const buf = Buffer.from(shot.data, 'base64');
			fs.writeFileSync(outDocs, buf);
			if (fs.existsSync(ARTIFACT_DIR)) {
				fs.writeFileSync(outArtifact, buf);
			}
		}
	}

	close() {
		this.ws.close();
	}
}

async function withChromeSession(port, callback) {
	const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), `jn-step6-${port}-`));
	const chrome = spawn(
		CHROME_BIN,
		[
			'--headless=new',
			'--disable-gpu',
			'--no-first-run',
			'--no-default-browser-check',
			'--window-size=1440,960',
			`--remote-debugging-port=${port}`,
			`--user-data-dir=${userDataDir}`,
			'about:blank',
		],
		{ stdio: 'ignore' }
	);

	try {
		let targets = null;
		for (let i = 0; i < 40; i++) {
			try {
				const r = await fetch(`http://127.0.0.1:${port}/json/list`);
				targets = await r.json();
				if (targets && targets.length > 0) break;
			} catch {}
			await sleep(200);
		}
		if (!targets || !targets.length) {
			throw new Error(`Could not connect to Chrome on port ${port}`);
		}
		const pageTarget = targets.find((t) => t.type === 'page');
		const cdp = new CDPClient(pageTarget.webSocketDebuggerUrl);
		await cdp.connect();
		await cdp.send('Page.enable');
		await cdp.send('Runtime.enable');
		await cdp.send('DOM.enable');
		await cdp.send('Network.enable');
		await cdp.setViewport(1440, 960, false);

		const result = await callback(cdp);
		cdp.close();
		return result;
	} finally {
		chrome.kill('SIGKILL');
		await sleep(200);
		fs.rmSync(userDataDir, { recursive: true, force: true });
	}
}

function runHelper(action) {
	const out = execSync(
		`./scripts/wp.sh eval '$action="${action}"; require "/scripts/step-6-e2e-helper.php";'`,
		{ encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }
	);
	const match = out.match(/STEP6_(?:PREPARE|CLEANUP)_JSON=(\{.*\})/);
	if (!match) {
		throw new Error(`Failed to parse helper output for ${action}:\n${out}`);
	}
	return JSON.parse(match[1]);
}

async function main() {
	const env = loadEnv();
	const BASE_URL = env.WP_HOME || 'http://localhost:8080';
	const MAILPIT_URL = `http://localhost:${env.MAILPIT_UI_PORT || '8025'}`;
	const LOGIN_SLUG = ( env.WPS_HIDE_LOGIN_SLUG || env.WP_LOGIN_SLUG || ( () => { throw new Error( 'WP_LOGIN_SLUG is missing from .env' ); } )() );

	console.log('1. Preparing Step 6 E2E seed data via WP-CLI...');
	const prep = runHelper('prepare');
	console.log('   Prepared:', {
		assistant_login: prep.assistant_login,
		cust1_key: prep.cust1_key,
		cust2_key: prep.cust2_key,
		guest3_key: prep.guest3_key,
		dup_key: prep.dup_key,
	});

	await fetch(`${MAILPIT_URL}/api/v1/messages`, { method: 'DELETE' });

	const report = {
		timestamp: new Date().toISOString(),
		staffLogin2FA: {},
		customersListScreen: {},
		customersFiltersAndSearch: {},
		customerDetailScreen: {},
		csvHttpStreamCheck: {},
	};

	try {
		await withChromeSession(9561, async (cdp) => {
			// =====================================================================
			// 1. STAFF LOGIN WITH MANDATORY TWO FACTOR (2FA)
			// =====================================================================
			console.log('2. Logging in as staff (`tro_ly_lich_hen`) at /' + LOGIN_SLUG + '...');
			await cdp.navigateAndWait(`${BASE_URL}/${LOGIN_SLUG}`, 2000);

			await cdp.eval(`(() => {
				document.getElementById('user_login').value = ${JSON.stringify(prep.assistant_login)};
				document.getElementById('user_pass').value = ${JSON.stringify(prep.assistant_pass)};
				document.getElementById('wp-submit').click();
			})()`);
			await sleep(2500);

			const twoFactorScreenState = await cdp.eval(`(() => {
				const authInput = document.getElementById('authcode');
				const form = document.querySelector('form[name="validate_2fa_form"], #loginform');
				return {
					url: window.location.href,
					hasAuthCodeInput: !!authInput,
					bodyText: document.body.innerText.slice(0, 800),
				};
			})()`);

			if (!twoFactorScreenState.hasAuthCodeInput) {
				throw new Error(`Expected Two-Factor #authcode input after staff password submit, got: ${JSON.stringify(twoFactorScreenState)}`);
			}

			// Fetch 8-digit OTP from Mailpit
			await sleep(1000);
			const msgsRes = await fetch(`${MAILPIT_URL}/api/v1/messages`);
			const msgsJson = await msgsRes.json();
			const messages = msgsJson.messages || [];
			let otpCode = '';

			for (const m of messages) {
				const detailRes = await fetch(`${MAILPIT_URL}/api/v1/message/${m.ID}`);
				const detail = await detailRes.json();
				const textBody = (detail.Text || '') + ' ' + (detail.HTML || '');
				const codeMatch = textBody.match(/\b([0-9]{8})\b/);
				if (codeMatch) {
					otpCode = codeMatch[1];
					break;
				}
			}

			if (!otpCode) {
				throw new Error('Could not find 8-digit 2FA verification code in Mailpit!');
			}

			// Fill the OTP code into the input so the screenshot shows the 2FA screen ready to submit
			await cdp.eval(`(() => {
				const authInput = document.getElementById('authcode');
				if (authInput) {
					authInput.value = ${JSON.stringify(otpCode)};
					authInput.focus();
				}
			})()`);
			await sleep(300);

			await cdp.screenshotBoth('step-6-staff-login-2fa.png', false);
			console.log('   Captured docs/jn/step-6-staff-login-2fa.png (OTP received via Mailpit)');

			// Submit 2FA form
			await cdp.eval(`(() => {
				const submitBtn = document.getElementById('submit') || document.querySelector('input[type="submit"], button[type="submit"]');
				if (submitBtn) submitBtn.click();
			})()`);
			await sleep(2500);

			const after2faUrl = await cdp.eval(`window.location.href`);
			report.staffLogin2FA = {
				staffUser: prep.assistant_login,
				roleLabel: 'Trợ lý lịch hẹn',
				twoFactorPromptShown: twoFactorScreenState.hasAuthCodeInput,
				otpDeliveredToMailpit: otpCode.length === 8,
				loggedInAdminUrl: after2faUrl,
				loginSucceeded: after2faUrl.includes('/wp-admin'),
			};
			if (!report.staffLogin2FA.loginSucceeded) {
				throw new Error(`2FA login did not land in /wp-admin: ${after2faUrl}`);
			}

			// =====================================================================
			// 2. CUSTOMERS LIST SCREEN (`admin.php?page=jn-customers`)
			// =====================================================================
			console.log('3. Navigating to Khách hàng admin screen (`admin.php?page=jn-customers`)...');
			await cdp.navigateAndWait(`${BASE_URL}/wp-admin/admin.php?page=jn-customers`, 2200);

			const listState = await cdp.eval(`(() => {
				const headers = Array.from(document.querySelectorAll('.jn-customers-table thead th')).map((th) => th.innerText.trim());
				const rows = Array.from(document.querySelectorAll('.jn-customers-table tbody tr')).map((tr) => tr.innerText.trim());
				const hasExportBtn = !!document.querySelector('a[href*="action=jn_export_customers_csv"]');
				const hasDupBadge = document.body.innerText.includes('Trùng SĐT/Email');
				return {
					heading: document.querySelector('.wrap h1')?.innerText.trim() || '',
					headers,
					rowCount: rows.length,
					hasExportBtn,
					hasDupBadge,
					hasLanAnh: document.body.innerText.includes('Trần Thị Lan Anh'),
					hasMinhThao: document.body.innerText.includes('Nguyễn Minh Thảo'),
					hasNgocBichGuest: document.body.innerText.includes('Lê Hoàng Ngọc Bích'),
				};
			})()`);

			await cdp.screenshotBoth('step-6-customers-list.png', true);
			console.log('   Captured docs/jn/step-6-customers-list.png:', listState);
			report.customersListScreen = listState;

			// =====================================================================
			// 3. FILTERS & PHONE-FORMAT SEARCH (`step-6-customers-filters.png`)
			// =====================================================================
			console.log('4. Testing filters (`customer_type=chu_co_so`, `customer_tag=VIP`, `has_pending_booking=1`) and phone search (`0908 234 567`)...');
			const filterUrl = `${BASE_URL}/wp-admin/admin.php?page=jn-customers&s=${encodeURIComponent('0908 234 567')}&customer_type=chu_co_so&customer_tag=VIP&has_pending_booking=1`;
			await cdp.navigateAndWait(filterUrl, 2000);

			const filterState = await cdp.eval(`(() => {
				const rows = Array.from(document.querySelectorAll('.jn-customers-table tbody tr'));
				return {
					searchValue: document.querySelector('input[name="s"]')?.value || '',
					selectedType: document.querySelector('select[name="customer_type"]')?.value || '',
					selectedTag: document.querySelector('select[name="customer_tag"]')?.value || '',
					pendingChecked: !!document.querySelector('input[name="has_pending_booking"]')?.checked,
					matchingRowCount: rows.length,
					matchesLanAnhE164Phone: document.body.innerText.includes('+84908234567') && document.body.innerText.includes('Trần Thị Lan Anh'),
				};
			})()`);

			await cdp.screenshotBoth('step-6-customers-filters.png', true);
			console.log('   Captured docs/jn/step-6-customers-filters.png:', filterState);
			report.customersFiltersAndSearch = filterState;

			// =====================================================================
			// 4. CUSTOMER DETAIL SCREEN (`step-6-customer-detail.png`)
			// =====================================================================
			console.log('5. Navigating to Customer Detail screen for Trần Thị Lan Anh...');
			const detailUrl = `${BASE_URL}/wp-admin/admin.php?page=jn-customers&action=view&customer_key=${encodeURIComponent(prep.cust1_key)}`;
			await cdp.navigateAndWait(detailUrl, 2200);

			const detailState = await cdp.eval(`(() => {
				const text = document.body.innerText;
				return {
					customerName: document.querySelector('.wrap h1')?.innerText.trim() || '',
					hasDuplicateWarningBanner: text.toUpperCase().includes('CẢNH BÁO TRÙNG THÔNG TIN KHÁCH HÀNG'),
					hasProfileForm: !!document.querySelector('form input[name="action"][value="jn_save_customer_profile"]'),
					hasCreateBookingForm: !!document.querySelector('form input[name="action"][value="jn_create_staff_booking"]'),
					hasBookingHistory: text.includes('Lịch Sử Đặt Lịch Coaching') && text.includes('Gói Master Mentorship (3 Tháng)'),
					hasOrderHistory: text.includes('Lịch Sử Đơn Hàng WooCommerce HPOS') && text.includes('45.000.000'),
					hasInternalNotesTimeline: text.includes('Ghi Chú Nội Bộ (Chỉ Nhân Sự)') && text.includes('Khách chủ hệ thống 2 chi nhánh'),
					hasReadOnlyConsentLog: text.includes('Nhật Ký Đồng Ý Chính Sách Bảo Mật (Consent Log — Chỉ Đọc)'),
					exportCsvHref: document.querySelector('a[href*="action=jn_export_customers_csv"]')?.href || '',
				};
			})()`);

			await cdp.screenshotBoth('step-6-customer-detail.png', true);
			console.log('   Captured docs/jn/step-6-customer-detail.png:', detailState);
			report.customerDetailScreen = detailState;

			// =====================================================================
			// 5. STREAMED UTF-8 BOM CSV DOWNLOAD VERIFICATION IN BROWSER
			// =====================================================================
			console.log('6. Verifying CSV download response & UTF-8 BOM via authenticated browser fetch...');
			const csvCheck = await cdp.eval(`(async () => {
				const link = document.querySelector('a[href*="action=jn_export_customers_csv"]');
				const listUrl = ${JSON.stringify(BASE_URL + '/wp-admin/admin.php?page=jn-customers')};
				const pageRes = await fetch(listUrl, { credentials: 'same-origin' });
				const html = await pageRes.text();
				const doc = new DOMParser().parseFromString(html, 'text/html');
				const exportHref = doc.querySelector('a[href*="action=jn_export_customers_csv"]')?.href || '';
				if (!exportHref) return { ok: false, error: 'Missing export link' };
				const res = await fetch(exportHref, { credentials: 'same-origin' });
				const buf = new Uint8Array(await res.arrayBuffer());
				const text = new TextDecoder('utf-8').decode(buf);
				return {
					ok: res.status === 200,
					status: res.status,
					contentType: res.headers.get('content-type'),
					contentDisposition: res.headers.get('content-disposition'),
					bomBytes: [buf[0], buf[1], buf[2]],
					hasUtf8Bom: buf[0] === 0xef && buf[1] === 0xbb && buf[2] === 0xbf,
					containsVietnameseHeader: text.includes('Mã Khách Hàng') && text.includes('Họ và Tên'),
					containsLanAnhRow: text.includes('Trần Thị Lan Anh') && text.includes('+84908234567'),
				};
			})()`);
			console.log('   CSV HTTP stream check:', csvCheck);
			report.csvHttpStreamCheck = csvCheck;
		});

		fs.writeFileSync(REPORT_PATH, JSON.stringify(report, null, 2));
		console.log('Saved verification report to', REPORT_PATH);
	} finally {
		console.log('7. Cleaning up temporary E2E seed data...');
		const cleaned = runHelper('cleanup');
		console.log('   Cleaned up:', cleaned);
	}
}

main().catch((err) => {
	console.error('STEP 6 E2E VERIFICATION FAILED:', err);
	try {
		runHelper('cleanup');
	} catch {}
	process.exit(1);
});
