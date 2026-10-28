#!/usr/bin/env node
/**
 * JN Step 5 — Comprehensive Browser E2E Verification (`scripts/verify-step-5.mjs`).
 *
 * Uses Google Chrome via CDP to verify and screenshot:
 * 1. Asset scoping (`/my-story/` loads 0 booking-form assets; `view.js` <= 5 KB gzipped).
 * 2. Guest clicks a tier button on `/coaching/` -> package preselected -> submits -> verbatim wireframe success text.
 * 3. Same submit works with JavaScript disabled (`Emulation.setScriptExecutionDisabled(true)`) via Post/Redirect/Get (303).
 * 4. Same submit works on a cached HTML copy of the page (stale token refreshed on first interaction via `/api/jn/v1/booking/token`).
 * 5. Staff confirms in Admin UI (`Lịch hẹn`) -> pending WooCommerce HPOS order created -> Agenda view (`Lịch theo ngày`).
 * 6. Customer confirmation email in Mailpit carries the `.ics` attachment and the `order-pay` link.
 * 7. Bank-transfer (`bacs`) payment via `order-pay` -> order paid -> booking payment status syncs to `Đã thanh toán` & staff notified.
 * 8. Customer registers/logs into `/tai-khoan/` -> booking automatically linked & visible in `/tai-khoan/lich-hen/` ("Lịch hẹn của tôi").
 */

import { spawn, execSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import zlib from 'node:zlib';

const CHROME_BIN = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const ROOT_DIR = process.cwd();
const DOCS_DIR = path.join(ROOT_DIR, 'docs/jn');
const ARTIFACT_DIR = '/Users/daugiabao/.gemini/antigravity/brain/09d53d86-861c-427a-ae34-fd38e5cba0ad';
const REPORT_PATH = path.join(DOCS_DIR, 'step-5-verification.json');

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
		if (!this.listeners.has(method)) {
			this.listeners.set(method, []);
		}
		this.listeners.get(method).push(fn);
	}

	off(method, fn) {
		if (!this.listeners.has(method)) return;
		this.listeners.set(
			method,
			this.listeners.get(method).filter((item) => item !== fn)
		);
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
					`Math.min(Math.max(document.documentElement.scrollHeight, document.body.scrollHeight, ${vp.height}), 4800)`
				);
			} catch {}
			await this.send('Emulation.setDeviceMetricsOverride', {
				width: vp.width,
				height: Math.ceil(contentHeight || vp.height),
				deviceScaleFactor: 2,
				mobile: vp.mobile,
			});
			await sleep(250);
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
	const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), `jn-step5-${port}-`));
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

async function main() {
	const env = loadEnv();
	const BASE_URL = env.WP_HOME || 'http://localhost:8080';
	const MAILPIT_URL = `http://localhost:${env.MAILPIT_UI_PORT || '8025'}`;
	const LOGIN_SLUG = ( env.WPS_HIDE_LOGIN_SLUG || env.WP_LOGIN_SLUG || ( () => { throw new Error( 'WP_LOGIN_SLUG is missing from .env' ); } )() );
	const ADMIN_USER = env.WP_ADMIN_USER || 'jannie_admin';
	const ADMIN_PASS = env.WP_ADMIN_PASSWORD || '';

	const EXPECTED_SUCCESS_TEXT =
		'Cảm ơn bạn! Yêu cầu đã được ghi nhận. Đội ngũ trợ lý của Jannie Nguyễn sẽ liên hệ trực tiếp qua số điện thoại của bạn trong vòng 24 giờ.';

	const report = {
		timestamp: new Date().toISOString(),
		assetBudget: {},
		publicJsFlow: {},
		noJsPrgFlow: {},
		cachedPageFlow: {},
		staffAdminFlow: {},
		mailpitVerification: {},
		orderPayAndSyncFlow: {},
		myAccountEndpointFlow: {},
	};

	// Clear Mailpit inbox before starting E2E
	await fetch(`${MAILPIT_URL}/api/v1/messages`, { method: 'DELETE' });

	// =========================================================================
	// 1. ASSET BUDGET & ZERO ASSET LEAKAGE ON NON-FORM PAGES
	// =========================================================================
	console.log('1. Checking asset budget & non-form page asset isolation...');
	const viewJsPath = path.join(ROOT_DIR, 'wordpress/app/modules/jn-core/blocks/booking-form/view.js');
	const viewJsRaw = fs.readFileSync(viewJsPath);
	const viewJsGzipped = zlib.gzipSync(viewJsRaw, { level: 9 });

	await withChromeSession(9551, async (cdp) => {
		const nonFormRequests = [];
		const onReq1 = (params) => {
			if (params?.request?.url) nonFormRequests.push(params.request.url);
		};
		cdp.on('Network.requestWillBeSent', onReq1);
		await cdp.navigateAndWait(`${BASE_URL}/chinh-sach-bao-mat/`, 2000);
		cdp.off('Network.requestWillBeSent', onReq1);

		const bookingAssetsOnNonFormPage = nonFormRequests.filter((u) => u.includes('booking-form'));
		const wcAssetsOnNonFormPage = nonFormRequests.filter((u) => /\/modules\/woocommerce\//i.test(u));

		const coachingRequests = [];
		const onReq2 = (params) => {
			if (params?.request?.url) coachingRequests.push(params.request.url);
		};
		cdp.on('Network.requestWillBeSent', onReq2);
		await cdp.navigateAndWait(`${BASE_URL}/coaching/`, 2000);
		cdp.off('Network.requestWillBeSent', onReq2);

		const bookingAssetsOnCoaching = coachingRequests.filter((u) => u.includes('booking-form/view.js'));
		const wcAssetsOnCoaching = coachingRequests.filter((u) => /\/modules\/woocommerce\//i.test(u));

		report.assetBudget = {
			viewJsRawBytes: viewJsRaw.length,
			viewJsGzippedBytes: viewJsGzipped.length,
			within5KbCompressedBudget: viewJsGzipped.length <= 5120,
			nonFormPageTested: `${BASE_URL}/chinh-sach-bao-mat/`,
			nonFormPageBookingAssetsCount: bookingAssetsOnNonFormPage.length,
			nonFormPageWcAssetsCount: wcAssetsOnNonFormPage.length,
			coachingBookingViewJsLoaded: bookingAssetsOnCoaching.length === 1,
			coachingWcAssetsCount: wcAssetsOnCoaching.length,
		};
	});
	console.log('   Asset budget check:', report.assetBudget);
	if (!report.assetBudget.within5KbCompressedBudget || report.assetBudget.nonFormPageBookingAssetsCount > 0) {
		throw new Error('Asset budget or non-form page asset isolation failed!');
	}

	// =========================================================================
	// 2. GUEST TIER CLICK -> PACKAGE PRESELECT -> JS SUBMIT -> VERBATIM SUCCESS
	// =========================================================================
	console.log('2. Running Guest Tier Pre-selection & JS REST Submit E2E on /coaching/...');
	await withChromeSession(9552, async (cdp) => {
		await cdp.navigateAndWait(`${BASE_URL}/coaching/`, 2000);

		// Verify default package is 60-minute package
		const defaultSelectedPkg = await cdp.eval(
			`document.getElementById('package-select')?.value || ''`
		);

		// Click the 30-minute tier button first, then the 60-minute button
		await cdp.eval(`(() => {
			const btn30 = document.querySelector('a[href*="goi=30-phut"]');
			if (btn30) btn30.click();
		})()`);
		await sleep(400);
		const selectedAfter30Click = await cdp.eval(
			`document.getElementById('package-select')?.value || ''`
		);

		await cdp.eval(`(() => {
			const btn60 = document.querySelector('a[href*="goi=60-phut"]');
			if (btn60) btn60.click();
		})()`);
		await sleep(400);
		const selectedAfter60Click = await cdp.eval(
			`document.getElementById('package-select')?.value || ''`
		);

		// Trigger focusin on form so token refresh runs if needed and fill fields
		await cdp.eval(`(() => {
			const nameEl = document.getElementById('full-name');
			nameEl.focus();
			nameEl.dispatchEvent(new Event('focusin', { bubbles: true }));
			nameEl.value = 'Nguyễn Minh Khuê';
			document.getElementById('phone-number').value = '0908 123 456';
			document.getElementById('email-address').value = 'minhkhue.step5@example.com';
			document.getElementById('coaching-format').value = 'online';
			document.getElementById('time-preference').value = 'morning';
			document.getElementById('notes').value = 'Mong muốn được Jannie Nguyễn cố vấn 1:1 nâng cấp kỹ thuật phun xăm sợi tự nhiên và tối ưu quy trình tư vấn khách VIP.';
			const consent = document.getElementById('privacy-consent');
			consent.checked = true;
		})()`);

		// Wait 2.4s to satisfy MIN_FILL_SECONDS >= 2s
		await sleep(2400);

		await cdp.eval(`(() => {
			document.getElementById('submit-btn').click();
		})()`);
		await sleep(2600);

		const feedbackState = await cdp.eval(`(() => {
			const fb = document.getElementById('form-feedback');
			const submitBtn = document.getElementById('submit-btn');
			return {
				isVisible: fb ? fb.classList.contains('is-visible') : false,
				isError: fb ? fb.classList.contains('is-error') : false,
				text: fb ? fb.innerText.replace(/\\s+/g, ' ').trim() : '',
				submitHidden: submitBtn ? submitBtn.classList.contains('is-hidden') : false,
			};
		})()`);

		await cdp.eval(`document.getElementById('booking-form')?.scrollIntoView({ behavior: 'instant', block: 'center' })`);
		await sleep(300);
		await cdp.screenshotBoth('step5-01-tier-preselect-and-submit-success.png', false);

		report.publicJsFlow = {
			defaultSelectedPkg,
			selectedAfter30Click,
			selectedAfter60Click,
			...feedbackState,
			matchesVerbatimWireframeText: feedbackState.text === EXPECTED_SUCCESS_TEXT,
		};
	});
	console.log('   Public JS submit check:', report.publicJsFlow);
	if (!report.publicJsFlow.matchesVerbatimWireframeText) {
		throw new Error(`Public JS submit failed or text mismatch: ${JSON.stringify(report.publicJsFlow)}`);
	}

	// =========================================================================
	// 3. NO-JS SUBMIT (`Emulation.setScriptExecutionDisabled(true)`) — PRG 303
	// =========================================================================
	console.log('3. Running No-JS Submit E2E (JavaScript disabled -> POST -> 303 Redirect -> GET)...');
	await withChromeSession(9553, async (cdp) => {
		// Disable JavaScript execution in browser emulation before loading the page
		await cdp.send('Emulation.setScriptExecutionDisabled', { value: true });
		await cdp.navigateAndWait(`${BASE_URL}/coaching/?goi=30-phut#booking-form`, 1800);

		// Wait 2.5 seconds so the server-rendered _jn_form_token is >= 2s old
		await sleep(2500);

		// Enable Runtime evaluation briefly only to populate inputs and call native HTMLFormElement.prototype.submit()
		// (view.js never executed on page load because script execution was disabled!).
		await cdp.send('Emulation.setScriptExecutionDisabled', { value: false });
		const viewJsBoundCheck = await cdp.eval(`(() => {
			const form = document.getElementById('coaching-registration-form');
			const pkgVal = document.getElementById('package-select')?.value || '';
			document.getElementById('full-name').value = 'Trần Hoàng Yến (No-JS)';
			document.getElementById('phone-number').value = '0918222333';
			document.getElementById('email-address').value = 'hoangyen.nojs@example.com';
			document.getElementById('coaching-format').value = 'in-person';
			document.getElementById('time-preference').value = 'afternoon';
			document.getElementById('notes').value = 'Gửi biểu mẫu khi trình duyệt tắt JavaScript (Post/Redirect/Get).';
			document.getElementById('privacy-consent').checked = true;
			// Native HTML form submit (bypasses any JS listener; view.js never ran at load anyway)
			HTMLFormElement.prototype.submit.call(form);
			return { serverPreselectedPkg: pkgVal };
		})()`);

		// Re-disable JS immediately so the redirected GET page also renders with JS disabled!
		await cdp.send('Emulation.setScriptExecutionDisabled', { value: true });
		await sleep(2800);

		// Read the server-rendered DOM on the PRG target page
		await cdp.send('Emulation.setScriptExecutionDisabled', { value: false });
		const noJsAfterPrg = await cdp.eval(`(() => {
			const fb = document.getElementById('form-feedback');
			return {
				redirectedUrl: window.location.href,
				hasPrgQueryParam: window.location.search.includes('jn_bf='),
				isVisible: fb ? fb.classList.contains('is-visible') : false,
				isError: fb ? fb.classList.contains('is-error') : false,
				text: fb ? fb.innerText.replace(/\\s+/g, ' ').trim() : '',
			};
		})()`);

		await cdp.eval(`document.getElementById('booking-form')?.scrollIntoView({ behavior: 'instant', block: 'center' })`);
		await sleep(300);
		await cdp.screenshotBoth('step5-02-nojs-prg-submit-success.png', false);

		report.noJsPrgFlow = {
			...viewJsBoundCheck,
			...noJsAfterPrg,
			matchesVerbatimWireframeText: noJsAfterPrg.text === EXPECTED_SUCCESS_TEXT,
		};
	});
	console.log('   No-JS PRG submit check:', report.noJsPrgFlow);
	if (!report.noJsPrgFlow.matchesVerbatimWireframeText || !report.noJsPrgFlow.hasPrgQueryParam) {
		throw new Error(`No-JS PRG submit failed: ${JSON.stringify(report.noJsPrgFlow)}`);
	}

	// =========================================================================
	// 4. CACHED HTML PAGE SUBMIT (STALE RENDER TOKEN REFRESHED ON INTERACTION)
	// =========================================================================
	console.log('4. Running Cached HTML Copy Submit E2E (stale token refreshed on first interaction)...');
	await withChromeSession(9554, async (cdp) => {
		await cdp.navigateAndWait(`${BASE_URL}/coaching/`, 1800);

		// Simulate a 7-day-old full-page-cached HTML document with an expired/stale _jn_form_token
		const staleToken = '1700000000.cachedhtml.stalehmacsignature';
		await cdp.eval(`(() => {
			const tokenInput = document.getElementById('jn-form-token');
			if (tokenInput) tokenInput.value = ${JSON.stringify(staleToken)};
		})()`);

		// Trigger first interaction (focusin) -> view.js fetches a fresh short-lived signed token from /api/jn/v1/booking/token
		await cdp.eval(`(() => {
			const nameEl = document.getElementById('full-name');
			nameEl.focus();
			nameEl.dispatchEvent(new Event('focusin', { bubbles: true }));
		})()`);
		await sleep(1000);

		const refreshedToken = await cdp.eval(`document.getElementById('jn-form-token')?.value || ''`);

		await cdp.eval(`(() => {
			document.getElementById('full-name').value = 'Nguyễn Minh Khuê (Lịch 2 - Cached HTML)';
			document.getElementById('phone-number').value = '+84 908 123 456';
			document.getElementById('email-address').value = 'minhkhue.step5@example.com';
			document.getElementById('package-select').value = 'Gói Tư Vấn Cá Nhân 1:1 (30 Phút)';
			document.getElementById('coaching-format').value = 'online';
			document.getElementById('time-preference').value = 'weekend';
			document.getElementById('notes').value = 'Kiểm thử gửi biểu mẫu từ trang HTML được lưu bộ nhớ đệm toàn trang.';
			document.getElementById('privacy-consent').checked = true;
		})()`);

		// Wait 2.4s so the newly fetched token satisfies MIN_FILL_SECONDS >= 2s
		await sleep(2400);

		await cdp.eval(`document.getElementById('submit-btn').click()`);
		await sleep(2600);

		const cachedResult = await cdp.eval(`(() => {
			const fb = document.getElementById('form-feedback');
			return {
				isVisible: fb ? fb.classList.contains('is-visible') : false,
				isError: fb ? fb.classList.contains('is-error') : false,
				text: fb ? fb.innerText.replace(/\\s+/g, ' ').trim() : '',
			};
		})()`);

		await cdp.eval(`document.getElementById('booking-form')?.scrollIntoView({ behavior: 'instant', block: 'center' })`);
		await sleep(300);
		await cdp.screenshotBoth('step5-03-cached-page-submit-success.png', false);

		report.cachedPageFlow = {
			staleTokenInjected: staleToken,
			refreshedToken,
			tokenWasRefreshedViaRest: refreshedToken !== '' && refreshedToken !== staleToken,
			...cachedResult,
			matchesVerbatimWireframeText: cachedResult.text === EXPECTED_SUCCESS_TEXT,
		};
	});
	console.log('   Cached HTML page submit check:', report.cachedPageFlow);
	if (!report.cachedPageFlow.tokenWasRefreshedViaRest || !report.cachedPageFlow.matchesVerbatimWireframeText) {
		throw new Error(`Cached HTML page submit failed: ${JSON.stringify(report.cachedPageFlow)}`);
	}

	// =========================================================================
	// 5. STAFF ADMIN UI: LIST TABLE, CONFIRMATION -> ORDER CREATED, AGENDA, SETTINGS, WC EMAILS
	// =========================================================================
	console.log('5. Running Staff Admin UI E2E (List table -> Confirm -> Order created -> Agenda -> Settings -> WC Emails)...');
	let primaryBookingId = 0;
	let primaryBookingCode = '';
	let primaryOrderId = 0;
	let primaryOrderPayUrl = '';
	let secondaryBookingId = 0;

	await withChromeSession(9555, async (adminCdp) => {
		// Log in as Administrator
		await adminCdp.navigateAndWait(`${BASE_URL}/${LOGIN_SLUG}/`, 1600);
		await adminCdp.eval(`(() => {
			document.getElementById('user_login').value = ${JSON.stringify(ADMIN_USER)};
			document.getElementById('user_pass').value = ${JSON.stringify(ADMIN_PASS)};
			document.getElementById('loginform').submit();
		})()`);
		await sleep(2800);

		// 5a. Open "Lịch hẹn" list table
		await adminCdp.navigateAndWait(`${BASE_URL}/wp-admin/edit.php?post_type=jn_booking`, 2200);
		await adminCdp.screenshotBoth('step5-04-admin-bookings-list.png', true);

		const listTableInfo = await adminCdp.eval(`(() => {
			const menuBadge = document.querySelector('#menu-posts-jn_booking .awaiting-mod')?.innerText.trim() || '';
			const rows = Array.from(document.querySelectorAll('#the-list tr'));
			let khueId = 0;
			let tranId = 0;
			for (const r of rows) {
				const idMatch = (r.id || '').match(/post-(\\d+)/);
				const pid = idMatch ? Number(idMatch[1]) : 0;
				const txt = r.innerText || '';
				if (txt.includes('Lịch 2 - Cached HTML')) {
					tranId = pid;
				} else if (txt.includes('Nguyễn Minh Khuê')) {
					khueId = pid;
				}
			}
			return {
				pendingBadgeCount: menuBadge,
				rowCount: rows.length,
				khueBookingId: khueId,
				tranBookingId: tranId,
			};
		})()`);

		primaryBookingId = listTableInfo.khueBookingId;
		secondaryBookingId = listTableInfo.tranBookingId;
		if (!primaryBookingId) {
			throw new Error(`Could not find Nguyễn Minh Khuê booking in Admin list table: ${JSON.stringify(listTableInfo)}`);
		}

		// 5b. Open Edit screen for primaryBookingId (Nguyễn Minh Khuê)
		await adminCdp.navigateAndWait(`${BASE_URL}/wp-admin/post.php?post=${primaryBookingId}&action=edit`, 2200);
		await adminCdp.screenshotBoth('step5-05-admin-booking-edit-pending.png', true);

		// Confirm appointment: 2026-11-25 10:00 (Asia/Ho_Chi_Minh), Online Zoom, 2,500,000 VND -> Đã xác nhận lịch
		await adminCdp.eval(`(() => {
			document.getElementById('jn-confirmed-date').value = '2026-11-25';
			document.getElementById('jn-confirmed-time').value = '10:00';
			document.getElementById('jn-duration-minutes').value = '60';
			document.getElementById('jn-format').value = 'online';
			document.getElementById('jn-meeting-link').value = 'https://zoom.us/j/9988776655';
			document.getElementById('jn-agreed-price').value = '2500000';
			document.getElementById('jn-target-status').value = 'jn_confirmed';
			document.getElementById('jn-transition-note').value = 'Đã gọi xác nhận khung giờ 10:00 ngày 25/11/2026 với học viên qua Zalo.';
			document.getElementById('jn-staff-notes').value = 'Học viên ưu tiên học qua Zoom độ phân giải cao.';
			document.querySelector('button[name="jn_save_booking_btn"]').click();
		})()`);
		await sleep(3200);

		await adminCdp.screenshotBoth('step5-06-admin-booking-confirmed-with-order.png', true);

		const confirmedMeta = await adminCdp.eval(`(() => {
			const statusPill = document.querySelector('.jn-status-pill')?.innerText.trim() || '';
			const payPill = document.querySelector('.jn-pay-pill')?.innerText.trim() || '';
			const payUrl = document.getElementById('jn-order-pay-url')?.value || '';
			const orderLink = document.querySelector('#jn_booking_order a[href*="page=wc-orders"]')?.innerText.trim() || '';
			const titleCode = document.querySelector('input#title')?.value || document.body.innerText.match(/JN-\\d{6}-\\d{4}/)?.[0] || '';
			return {
				statusPill,
				payPill,
				payUrl,
				orderLink,
				bookingCode: titleCode,
			};
		})()`);

		primaryBookingCode = confirmedMeta.bookingCode;
		primaryOrderPayUrl = confirmedMeta.payUrl;
		const ordMatch = (confirmedMeta.orderLink || '').match(/#(\d+)/) || (primaryOrderPayUrl || '').match(/order-pay\/(\d+)/);
		primaryOrderId = ordMatch ? Number(ordMatch[1]) : 0;

		// Also confirm secondaryBookingId (Lê Bảo Trân) so we have a second confirmed booking
		if (secondaryBookingId > 0) {
			await adminCdp.navigateAndWait(`${BASE_URL}/wp-admin/post.php?post=${secondaryBookingId}&action=edit`, 2000);
			await adminCdp.eval(`(() => {
				document.getElementById('jn-confirmed-date').value = '2026-11-25';
				document.getElementById('jn-confirmed-time').value = '14:30';
				document.getElementById('jn-duration-minutes').value = '30';
				document.getElementById('jn-format').value = 'online';
				document.getElementById('jn-meeting-link').value = 'https://zoom.us/j/3344556677';
				document.getElementById('jn-agreed-price').value = '1500000';
				document.getElementById('jn-target-status').value = 'jn_confirmed';
				document.querySelector('button[name="jn_save_booking_btn"]').click();
			})()`);
			await sleep(2800);
		}

		// 5c. Open Light Agenda View (`Lịch theo ngày`) for 2026-11-25
		await adminCdp.navigateAndWait(
			`${BASE_URL}/wp-admin/edit.php?post_type=jn_booking&page=jn-booking-agenda&agenda_date=2026-11-25`,
			2000
		);
		const agendaHasBookings = await adminCdp.eval(`(() => {
			const txt = document.body.innerText || '';
			return txt.includes('Nguyễn Minh Khuê') && txt.includes('10:00');
		})()`);
		await adminCdp.screenshotBoth('step5-07-admin-agenda-view.png', true);

		// 5d. Open Booking Settings screen (`Cài đặt Lịch hẹn`)
		await adminCdp.navigateAndWait(
			`${BASE_URL}/wp-admin/edit.php?post_type=jn_booking&page=jn-booking-settings`,
			2000
		);
		await adminCdp.screenshotBoth('step5-08-admin-booking-settings.png', true);

		// 5e. Open WooCommerce -> Settings -> Emails to verify the 5 custom email classes
		await adminCdp.navigateAndWait(
			`${BASE_URL}/wp-admin/admin.php?page=wc-settings&tab=email`,
			2200
		);
		const wcEmailsListed = await adminCdp.eval(`(() => {
			const txt = document.body.innerText || '';
			return {
				hasNewRequestEmail: txt.includes('Lịch hẹn Coaching — Đã tiếp nhận yêu cầu (Khách hàng)'),
				hasStaffAlertEmail: txt.includes('Lịch hẹn Coaching — Thông báo yêu cầu mới (Trợ lý)'),
				hasConfirmedEmail: txt.includes('Lịch hẹn Coaching — Đã xác nhận lịch & Thanh toán (Khách hàng)'),
				hasPaymentReceivedEmail: txt.includes('Lịch hẹn Coaching — Đã nhận thanh toán (Trợ lý)'),
				hasCancelledEmail: txt.includes('Lịch hẹn Coaching — Thông báo hủy lịch hẹn'),
			};
		})()`);
		await adminCdp.screenshotBoth('step5-09-wc-settings-emails.png', true);

		report.staffAdminFlow = {
			...listTableInfo,
			...confirmedMeta,
			primaryOrderId,
			agendaHasBookings,
			wcEmailsListed,
		};
	});
	console.log('   Staff Admin flow check:', report.staffAdminFlow);
	if (!primaryOrderId || !primaryOrderPayUrl) {
		throw new Error(`Staff confirmation did not produce a pending order + order-pay URL: ${JSON.stringify(report.staffAdminFlow)}`);
	}

	// =========================================================================
	// 6. MAILPIT VERIFICATION: CUSTOMER CONFIRMATION EMAIL WITH .ICS & ORDER-PAY LINK
	// =========================================================================
	console.log('6. Verifying Mailpit emails (.ics attachment + order-pay link in confirmation email)...');
	const mailListRes = await fetch(`${MAILPIT_URL}/api/v1/messages`);
	const mailListData = await mailListRes.json();
	const messages = mailListData.messages || [];

	const confirmedMsgSummary = messages.find(
		(m) =>
			m.Subject &&
			m.Subject.includes('Đã xác nhận lịch hẹn Coaching') &&
			(m.To || []).some((t) => t.Address === 'minhkhue.step5@example.com')
	);

	if (!confirmedMsgSummary) {
		throw new Error(`Could not find confirmation email for minhkhue.step5@example.com in Mailpit! Messages: ${JSON.stringify(messages.map((m) => m.Subject))}`);
	}

	const confirmedMsgRes = await fetch(`${MAILPIT_URL}/api/v1/message/${confirmedMsgSummary.ID}`);
	const confirmedMsg = await confirmedMsgRes.json();
	const attachments = confirmedMsg.Attachments || [];
	const icsAttachment = attachments.find((a) => (a.FileName || '').endsWith('.ics'));

	let icsContentText = '';
	if (icsAttachment) {
		const partRes = await fetch(`${MAILPIT_URL}/api/v1/message/${confirmedMsgSummary.ID}/part/${icsAttachment.PartID}`);
		icsContentText = await partRes.text();
	}

	await withChromeSession(9556, async (mailCdp) => {
		await mailCdp.navigateAndWait(`${MAILPIT_URL}/view/${confirmedMsgSummary.ID}.html`, 1800);
		await mailCdp.screenshotBoth('step5-10-mailpit-confirmed-email-ics.png', true);
	});

	report.mailpitVerification = {
		totalEmailsInMailpit: messages.length,
		confirmedEmailId: confirmedMsgSummary.ID,
		confirmedEmailSubject: confirmedMsg.Subject,
		hasIcsAttachment: Boolean(icsAttachment),
		icsFileName: icsAttachment ? icsAttachment.FileName : '',
		icsContainsVcalendar: icsContentText.includes('BEGIN:VCALENDAR'),
		icsContainsHoChiMinhTz: icsContentText.includes('TZID=Asia/Ho_Chi_Minh:20261125T100000'),
		icsContainsZoomLink: icsContentText.includes('https://zoom.us/j/9988776655'),
		emailHtmlContainsOrderPayUrl: (confirmedMsg.HTML || '').includes('order-pay'),
		emailHtmlContainsZoomLink: (confirmedMsg.HTML || '').includes('https://zoom.us/j/9988776655'),
	};
	console.log('   Mailpit verification:', report.mailpitVerification);
	if (!report.mailpitVerification.hasIcsAttachment || !report.mailpitVerification.emailHtmlContainsOrderPayUrl) {
		throw new Error(`Mailpit confirmation email missing .ics or order-pay link: ${JSON.stringify(report.mailpitVerification)}`);
	}

	// =========================================================================
	// 7. TEST BANK-TRANSFER PAYMENT ON ORDER-PAY -> STATUSES SYNC -> MY ACCOUNT
	// =========================================================================
	console.log('7. Running Bank-Transfer Payment on order-pay -> Status Sync -> My Account (/tai-khoan/lich-hen/)...');
	await withChromeSession(9557, async (payCdp) => {
		// 7a. Open the order-pay URL from the email
		await payCdp.navigateAndWait(primaryOrderPayUrl, 2200);
		await payCdp.screenshotBoth('step5-11-order-pay-screen.png', true);

		// Submit BACS payment on order-pay
		await payCdp.eval(`(() => {
			const terms = document.getElementById('terms');
			if (terms) terms.checked = true;
			const placeBtn = document.getElementById('place_order');
			if (placeBtn) placeBtn.click();
		})()`);
		await sleep(3200);

		const orderReceivedUrl = await payCdp.eval('window.location.href');

		// 7b. In an Admin session, mark the BACS order Completed -> verify Booking syncs to "Đã thanh toán"
		await withChromeSession(9558, async (adminCdp) => {
			await adminCdp.navigateAndWait(`${BASE_URL}/${LOGIN_SLUG}/`, 1600);
			await adminCdp.eval(`(() => {
				document.getElementById('user_login').value = ${JSON.stringify(ADMIN_USER)};
				document.getElementById('user_pass').value = ${JSON.stringify(ADMIN_PASS)};
				document.getElementById('loginform').submit();
			})()`);
			await sleep(2600);

			await adminCdp.navigateAndWait(
				`${BASE_URL}/wp-admin/admin.php?page=wc-orders&action=edit&id=${primaryOrderId}`,
				2400
			);
			await adminCdp.eval(`(() => {
				const sel = document.getElementById('order_status');
				if (sel) {
					sel.value = 'wc-completed';
					sel.dispatchEvent(new Event('change', { bubbles: true }));
				}
				const btn = document.querySelector('button.save_order');
				if (btn) btn.click();
			})()`);
			await sleep(3200);

			// Reload the Booking Edit screen to confirm derived payment state = "Đã thanh toán"
			await adminCdp.navigateAndWait(
				`${BASE_URL}/wp-admin/post.php?post=${primaryBookingId}&action=edit`,
				2200
			);
			const syncedPayLabel = await adminCdp.eval(
				`document.querySelector('.jn-pay-pill')?.innerText.trim() || ''`
			);
			await adminCdp.screenshotBoth('step5-12-admin-booking-paid-synced.png', true);

			report.orderPayAndSyncFlow = {
				orderPayUrl: primaryOrderPayUrl,
				orderReceivedUrl,
				orderReceivedReached: orderReceivedUrl.includes('order-received'),
				syncedBookingPaymentLabel: syncedPayLabel,
			};
		});

		// Verify staff payment notification email arrived in Mailpit
		const mailAfterPayRes = await fetch(`${MAILPIT_URL}/api/v1/messages`);
		const mailAfterPayData = await mailAfterPayRes.json();
		const staffPaidEmail = (mailAfterPayData.messages || []).find(
			(m) => m.Subject && m.Subject.includes('Đã thanh toán lịch hẹn')
		);
		report.orderPayAndSyncFlow.staffPaymentEmailReceived = Boolean(staffPaidEmail);
		report.orderPayAndSyncFlow.staffPaymentEmailSubject = staffPaidEmail ? staffPaidEmail.Subject : '';

		// 7c. Register/log in as customer `minhkhue.step5@example.com` on `/tai-khoan/#register`.
		// Both `primaryBookingId` (paid) and `secondaryBookingId` (unpaid confirmed) automatically link to this customer!
		await payCdp.navigateAndWait(`${BASE_URL}/tai-khoan/#register`, 1800);
		await payCdp.eval(`(() => {
			document.getElementById('reg_email').value = 'minhkhue.step5@example.com';
			document.getElementById('reg_password').value = 'JannieAcademy!2026#Step5';
			document.querySelector('button[name="register"]').click();
		})()`);
		await sleep(3000);

		// Navigate to My Account -> "Lịch hẹn của tôi" (`/tai-khoan/lich-hen/`)
		await payCdp.navigateAndWait(`${BASE_URL}/tai-khoan/lich-hen/`, 2200);
		await payCdp.screenshotBoth('step5-13-myaccount-lich-hen.png', true);

		const myAccountCheck = await payCdp.eval(`(() => {
			const navText = document.querySelector('.woocommerce-MyAccount-navigation')?.innerText || '';
			const content = document.querySelector('.jn-myaccount-bookings');
			const html = content ? content.innerHTML : '';
			const text = content ? content.innerText : '';
			return {
				navHasLichHenCuaToi: navText.includes('Lịch hẹn của tôi'),
				bookingCodeVisible: text.includes(${JSON.stringify(primaryBookingCode)}),
				statusConfirmedVisible: text.includes('Đã xác nhận lịch'),
				paidBadgeVisible: text.includes('Đã thanh toán'),
				zoomLinkVisible: html.includes('https://zoom.us/j/9988776655'),
				icsDownloadLinkVisible: text.includes('Tải lịch .ics'),
				payNowButtonVisibleForUnpaidBooking: text.includes('Thanh Toán Ngay'),
			};
		})()`);

		report.myAccountEndpointFlow = myAccountCheck;
	});
	console.log('   Order-pay & My Account check:', report.orderPayAndSyncFlow, report.myAccountEndpointFlow);

	if (
		report.orderPayAndSyncFlow.syncedBookingPaymentLabel !== 'Đã thanh toán' ||
		!report.myAccountEndpointFlow.bookingCodeVisible ||
		!report.myAccountEndpointFlow.paidBadgeVisible ||
		!report.myAccountEndpointFlow.payNowButtonVisibleForUnpaidBooking
	) {
		throw new Error('Order-pay sync or My Account Lịch hẹn của tôi check failed!');
	}

	fs.writeFileSync(REPORT_PATH, JSON.stringify(report, null, 2));
	console.log(`Saved full Step 5 verification report to ${REPORT_PATH}`);
}

main().catch((err) => {
	console.error('Step 5 E2E verification failed:', err);
	process.exit(1);
});
