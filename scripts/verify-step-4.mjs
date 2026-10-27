#!/usr/bin/env node
/**
 * JN Step 4 — Comprehensive Browser E2E Verification, Network Asset Scoping Proof & Mailpit Screenshots.
 *
 * Uses Google Chrome via CDP (zero external npm dependencies) to verify:
 * 1. Network asset scoping on `/coaching/`, `/my-story/`, and `/booking/` (0 WooCommerce CSS/JS, 0 `get_refreshed_fragments`).
 * 2. Direct `?add-to-cart=164` blocked for Coaching Package, while `order-pay` link of pending package order (`ID=171`) works end-to-end.
 * 3. `/my-favorite-product/` responsive layout (`375`, `768`, `1024`, `1440`) with `"DEMO – xóa trước khi chạy thật"` in `woocommerce/product-collection`.
 * 4. Guest E2E order via `woocommerce/cart` & `woocommerce/checkout` (virtual-only fields: Name, Phone, Email + Terms checkbox) using Bank Transfer (`bacs`).
 * 5. Logged-in customer E2E order: header `"Đăng ký"` deep-link (`/tai-khoan/#register`) -> account registration -> Checkout Block order using Bank Transfer (`bacs`).
 * 6. Admin HPOS (`admin.php?page=wc-orders`) verification of orders + status update to Completed -> Customer My Account (`/tai-khoan/orders/`) verification.
 * 7. Mailpit (`http://localhost:8025`) branded Vietnamese WooCommerce emails verification & screenshots.
 */

import { spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const CHROME_BIN = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const ROOT_DIR = process.cwd();
const ARTIFACT_DIR = '/Users/daugiabao/.gemini/antigravity/brain/ddb23a35-60cd-444c-9355-4e676ad23023';
const REPORT_PATH = path.join(ROOT_DIR, 'docs/jn/step-4-verification.json');

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

	async screenshot(outPath, fullPage = false) {
		if (fullPage) {
			const vp = this.currentViewport || { width: 1440, height: 960, mobile: false };
			const contentHeight = await this.eval(
				`Math.min(Math.max(document.documentElement.scrollHeight, document.body.scrollHeight, ${vp.height}), 5000)`
			);
			await this.send('Emulation.setDeviceMetricsOverride', {
				width: vp.width,
				height: Math.ceil(contentHeight),
				deviceScaleFactor: 2,
				mobile: vp.mobile,
			});
			await sleep(250);
			const shot = await this.send('Page.captureScreenshot', {
				format: 'png',
				captureBeyondViewport: false,
			});
			fs.writeFileSync(outPath, Buffer.from(shot.data, 'base64'));
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
			fs.writeFileSync(outPath, Buffer.from(shot.data, 'base64'));
		}
	}

	close() {
		this.ws.close();
	}
}

async function withChromeSession(port, callback) {
	const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), `jn-step4-${port}-`));
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

async function waitForUrlMatch(cdp, regex, timeoutMs = 25000) {
	const start = Date.now();
	while (Date.now() - start < timeoutMs) {
		const url = await cdp.eval('window.location.href');
		if (regex.test(url)) {
			await sleep(1000);
			return url;
		}
		await sleep(400);
	}
	const lastUrl = await cdp.eval('window.location.href');
	const errorBanner = await cdp.eval(`(() => {
		const err = document.querySelector('.wc-block-components-notice-banner.is-error, .woocommerce-error');
		return err ? err.innerText.trim() : '';
	})()`);
	throw new Error(`Timed out waiting for URL ${regex}. Current URL: ${lastUrl}. Notice: ${errorBanner}`);
}

async function fillCheckoutBlockForm(cdp, { email, firstName, phone }) {
	// Wait until Checkout Block inputs are mounted
	const start = Date.now();
	while (Date.now() - start < 20000) {
		const ready = await cdp.eval(`Boolean(document.querySelector('.wc-block-components-checkout-place-order-button'))`);
		if (ready) break;
		await sleep(400);
	}
	await sleep(800);

	return await cdp.eval(`(async () => {
		function setReactVal(el, val) {
			if (!el) return false;
			el.focus();
			const proto = el instanceof HTMLSelectElement ? window.HTMLSelectElement.prototype : window.HTMLInputElement.prototype;
			const setter = Object.getOwnPropertyDescriptor(proto, 'value')?.set;
			if (setter) {
				setter.call(el, val);
			} else {
				el.value = val;
			}
			el.dispatchEvent(new Event('input', { bubbles: true }));
			el.dispatchEvent(new Event('change', { bubbles: true }));
			el.blur();
			return true;
		}

		const emailInput = document.querySelector('#email, input[type="email"]');
		if (${JSON.stringify(Boolean(email))} && emailInput) {
			setReactVal(emailInput, ${JSON.stringify(email || '')});
		}

		const nameInput = document.querySelector('#billing-first_name, #shipping-first_name, input[autocomplete="given-name"]');
		if (nameInput) {
			setReactVal(nameInput, ${JSON.stringify(firstName)});
		}

		const phoneInput = document.querySelector('#billing-phone, #shipping-phone, input[type="tel"]');
		if (phoneInput) {
			setReactVal(phoneInput, ${JSON.stringify(phone)});
		}

		const termsCheckbox = document.querySelector('.wp-block-woocommerce-checkout-terms-block input[type="checkbox"]');
		if (termsCheckbox && !termsCheckbox.checked) {
			termsCheckbox.click();
		}

		await new Promise((r) => setTimeout(r, 400));

		// Collect visible form inputs inside the Checkout Block for Task 7 verification
		const form = document.querySelector('.wc-block-checkout__form') || document.querySelector('.wp-block-woocommerce-checkout');
		const visibleFields = [];
		if (form) {
			const controls = Array.from(form.querySelectorAll('input, select, textarea'));
			for (const el of controls) {
				const style = window.getComputedStyle(el);
				const rect = el.getBoundingClientRect();
				if (style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0 && el.type !== 'hidden') {
					visibleFields.push({
						id: el.id,
						name: el.name,
						type: el.type,
						label: (el.labels && el.labels[0] ? el.labels[0].innerText.trim() : '') || el.getAttribute('aria-label') || '',
					});
				}
			}
		}

		return {
			emailFilled: Boolean(emailInput),
			nameFilled: Boolean(nameInput),
			phoneFilled: Boolean(phoneInput),
			termsChecked: Boolean(termsCheckbox && termsCheckbox.checked),
			visibleFields,
		};
	})()`);
}

async function main() {
	fs.mkdirSync(ARTIFACT_DIR, { recursive: true });
	const env = loadEnv();
	const BASE_URL = (env.WP_HOME || 'http://localhost:8080').replace(/\/$/, '');
	const MAILPIT_URL = 'http://localhost:8025';
	const ADMIN_USER = env.WP_ADMIN_USER;
	const ADMIN_PASS = env.WP_ADMIN_PASSWORD;
	const LOGIN_SLUG = env.WP_LOGIN_SLUG || 'wp-login.php';

	const DEMO_PRODUCT_ID = Number(process.env.DEMO_PRODUCT_ID || 174);
	const COACHING_PACKAGE_ID = 164;
	const PENDING_PACKAGE_ORDER_ID = Number(process.env.PENDING_ORDER_ID || 175);
	const PENDING_PACKAGE_ORDER_KEY = process.env.PENDING_ORDER_KEY || 'wc_order_Qdgo1iHyPU5ZT';

	// Clear Mailpit before starting E2E
	try {
		await fetch(`${MAILPIT_URL}/api/v1/messages`, { method: 'DELETE' });
	} catch {}

	const report = {
		timestamp: new Date().toISOString(),
		networkScoping: {},
		packageProtectionAndOrderPay: {},
		myFavoriteProductResponsive: {},
		guestOrderFlow: {},
		loggedInOrderFlow: {},
		adminHposAndMyAccount: {},
		mailpitEmails: {},
	};

	// =========================================================================
	// 1. TASK 10 — Network Asset Scoping Proof on Content Pages
	// =========================================================================
	console.log('1. Verifying WooCommerce asset scoping on /coaching/, /my-story/, and /booking/...');
	await withChromeSession(9441, async (cdp) => {
		const contentPaths = ['/coaching/', '/my-story/', '/booking/'];
		for (const urlPath of contentPaths) {
			const requests = [];
			const onReq = (params) => {
				if (params.request && params.request.url) {
					requests.push({
						url: params.request.url,
						type: params.type || '',
					});
				}
			};
			cdp.on('Network.requestWillBeSent', onReq);
			await cdp. send('Network.clearBrowserCache');
			await cdp.navigateAndWait(`${BASE_URL}${urlPath}`, 2200);
			cdp.off('Network.requestWillBeSent', onReq);

			const wooAssets = requests.filter((r) =>
				/\/modules\/woocommerce\/|woocommerce\.min\.css|wc-blocks|wc-cart-fragments|order-attribution|sourcebuster|jannie\/assets\/css\/woocommerce\.css/i.test(
					r.url
				)
			);
			const cartFragments = requests.filter((r) => /get_refreshed_fragments/i.test(r.url));

			report.networkScoping[urlPath] = {
				totalRequests: requests.length,
				allRequestUrls: requests.map((r) => r.url),
				wooAssetsCount: wooAssets.length,
				wooAssets: wooAssets.map((r) => r.url),
				cartFragmentCount: cartFragments.length,
			};

			if (urlPath === '/coaching/') {
				await cdp.screenshot(path.join(ARTIFACT_DIR, 'step4-coaching-zero-woo-assets.png'), false);
			}
		}
	});
	console.log('   /coaching/ WooCommerce assets:', report.networkScoping['/coaching/'].wooAssetsCount);
	console.log('   /coaching/ cart fragments:', report.networkScoping['/coaching/'].cartFragmentCount);

	// =========================================================================
	// 2. TASK 4 — Coaching Package Direct Add-to-Cart Blocked + order-pay Works
	// =========================================================================
	console.log('2. Verifying Coaching Package direct add-to-cart block & order-pay payment...');
	await withChromeSession(9442, async (cdp) => {
		// 2a. Attempt direct ?add-to-cart=164
		await cdp.navigateAndWait(`${BASE_URL}/?add-to-cart=${COACHING_PACKAGE_ID}`, 1500);
		await cdp.navigateAndWait(`${BASE_URL}/cart/`, 2000);
		const isCartStillEmpty = await cdp.eval(`(() => {
			const html = document.body.innerHTML;
			return html.includes('Giỏ hàng của bạn hiện đang trống') || Boolean(document.querySelector('.wp-block-woocommerce-empty-cart-block'));
		})()`);

		// 2b. Single product page redirect to /coaching/#coaching-tiers
		await cdp.navigateAndWait(`${BASE_URL}/?p=${COACHING_PACKAGE_ID}`, 1500);
		const redirectedPkgUrl = await cdp.eval('window.location.href');

		// 2c. Open order-pay link for pending coaching package order
		const orderPayUrl = `${BASE_URL}/checkout/order-pay/${PENDING_PACKAGE_ORDER_ID}/?pay_for_order=true&key=${PENDING_PACKAGE_ORDER_KEY}`;
		await cdp.navigateAndWait(orderPayUrl, 2000);

		const orderPayState = await cdp.eval(`(() => {
			const html = document.body.innerHTML;
			return {
				hasPackageTitle: html.includes('Gói Coaching Chuyên Nghiệp 1:1 (60 Phút)'),
				hasFormattedVnd: html.includes('2.500.000') && html.includes('₫'),
				hasCodOption: Boolean(document.querySelector('#payment_method_cod')),
				hasPlaceOrderBtn: Boolean(document.querySelector('#place_order')),
			};
		})()`);

		await cdp.screenshot(path.join(ARTIFACT_DIR, 'step4-package-order-pay-form.png'), true);

		// Submit payment on order-pay
		await cdp.eval(`(() => {
			const terms = document.querySelector('input#terms, input[name="terms"]');
			if (terms && !terms.checked) terms.checked = true;
			const btn = document.getElementById('place_order');
			if (btn) btn.click();
		})()`);

		const orderPayResultUrl = await waitForUrlMatch(cdp, /\/order-received\//, 20000);
		const bankDetailsShown = await cdp.eval(`document.body.innerHTML.includes('JANNIE NGUYEN ACADEMY')`);
		await cdp.screenshot(path.join(ARTIFACT_DIR, 'step4-package-order-pay-thankyou.png'), true);

		report.packageProtectionAndOrderPay = {
			directAddToCartBlocked: isCartStillEmpty,
			singleProductRedirectUrl: redirectedPkgUrl,
			orderPayUrl,
			...orderPayState,
			orderPayCompletedRedirectUrl: orderPayResultUrl,
			orderPaySucceeded: orderPayResultUrl.includes('/order-received/'),
			bankDetailsShown,
		};
	});
	console.log('   Package order-pay succeeded:', report.packageProtectionAndOrderPay.orderPaySucceeded);

	// =========================================================================
	// 3. TASK 6 — /my-favorite-product/ Responsive Views (375, 768, 1024, 1440)
	// =========================================================================
	console.log('3. Capturing /my-favorite-product/ with DEMO product across 375/768/1024/1440...');
	await withChromeSession(9443, async (cdp) => {
		const viewports = [
			{ width: 375, height: 812, mobile: true },
			{ width: 768, height: 1024, mobile: true },
			{ width: 1024, height: 768, mobile: false },
			{ width: 1440, height: 900, mobile: false },
		];
		for (const vp of viewports) {
			await cdp.setViewport(vp.width, vp.height, vp.mobile);
			await cdp.navigateAndWait(`${BASE_URL}/my-favorite-product/`, 1800);
			const state = await cdp.eval(`(() => {
				return {
					hasHorizontalOverflow: document.documentElement.scrollWidth > window.innerWidth,
					hasDemoCard: document.body.innerText.includes('DEMO – xóa trước khi chạy thật'),
					hasVndPrice: document.body.innerText.includes('450.000 ₫'),
				};
			})()`);
			report.myFavoriteProductResponsive[`${vp.width}x${vp.height}`] = state;

			if (vp.width === 375 || vp.width === 1440) {
				await cdp.screenshot(path.join(ARTIFACT_DIR, `step4-my-favorite-product-demo-${vp.width}.png`), true);
			}
		}
	});

	// =========================================================================
	// 4. ACCEPTANCE — Guest Order E2E via Cart Block & Checkout Block (BACS)
	// =========================================================================
	console.log('4. Running Guest Order E2E via Cart Block & Checkout Block...');
	let guestOrderId = 0;
	await withChromeSession(9444, async (cdp) => {
		await cdp.navigateAndWait(`${BASE_URL}/?add-to-cart=${DEMO_PRODUCT_ID}`, 1500);
		await cdp.navigateAndWait(`${BASE_URL}/cart/`, 2800);
		await cdp.screenshot(path.join(ARTIFACT_DIR, 'step4-cart-block-1440.png'), true);

		await cdp.navigateAndWait(`${BASE_URL}/checkout/`, 3200);
		const fillInfo = await fillCheckoutBlockForm(cdp, {
			email: 'khach.guest@example.com',
			firstName: 'Lê Hoàng Lan',
			phone: '0909111222',
		});

		await cdp.screenshot(path.join(ARTIFACT_DIR, 'step4-checkout-block-virtual-guest-1440.png'), true);
		await cdp.setViewport(375, 812, true);
		await sleep(500);
		await cdp.screenshot(path.join(ARTIFACT_DIR, 'step4-checkout-block-virtual-guest-375.png'), true);
		await cdp.setViewport(1440, 960, false);
		await sleep(500);

		await cdp.eval(`document.querySelector('.wc-block-components-checkout-place-order-button').click()`);
		const guestOrderReceivedUrl = await waitForUrlMatch(cdp, /\/checkout\/order-received\/(\d+)\//, 25000);
		const m = guestOrderReceivedUrl.match(/\/order-received\/(\d+)\//);
		guestOrderId = m ? Number(m[1]) : 0;

		await cdp.screenshot(path.join(ARTIFACT_DIR, 'step4-guest-order-received-1440.png'), true);

		report.guestOrderFlow = {
			...fillInfo,
			guestOrderId,
			guestOrderReceivedUrl,
		};
	});
	console.log('   Guest order placed! ID:', guestOrderId);

	// =========================================================================
	// 5 & 6. ACCEPTANCE — Logged-In Customer Order + Admin HPOS + My Account
	// =========================================================================
	console.log('5. Running Logged-In Customer E2E (/tai-khoan/#register -> Order -> Admin HPOS -> My Account)...');
	let customerOrderId = 0;
	await withChromeSession(9445, async (customerCdp) => {
		// 5a. Click header "Đăng ký" pill on home page
		await customerCdp.navigateAndWait(`${BASE_URL}/`, 1600);
		const registerLinkHref = await customerCdp.eval(
			`document.querySelector('.jn-header-register')?.getAttribute('href') || ''`
		);
		await customerCdp.navigateAndWait(`${BASE_URL}/tai-khoan/#register`, 1800);

		const isRegisterFormHighlighted = await customerCdp.eval(`(() => {
			const regForm = document.getElementById('register');
			const col2 = document.querySelector('#customer_login .u-column2');
			return Boolean(regForm && col2 && col2.classList.contains('is-register-highlighted'));
		})()`);

		await customerCdp.screenshot(path.join(ARTIFACT_DIR, 'step4-my-account-login-register-1440.png'), true);

		// 5b. Register new customer account
		const customerEmail = `hocvien.step4.${Date.now()}@example.com`;
		await customerCdp.eval(`(() => {
			document.getElementById('reg_email').value = ${JSON.stringify(customerEmail)};
			document.getElementById('reg_password').value = 'JannieAcademy!2026#Step4';
			document.querySelector('button[name="register"]').click();
		})()`);
		await sleep(3000);

		const myAccountDashboardUrl = await customerCdp.eval('window.location.href');
		const isCustomerLoggedIn = await customerCdp.eval(
			`Boolean(document.querySelector('.woocommerce-MyAccount-navigation'))`
		);

		// 5c. Add DEMO product and checkout
		await customerCdp.navigateAndWait(`${BASE_URL}/?add-to-cart=${DEMO_PRODUCT_ID}`, 1500);
		await customerCdp.navigateAndWait(`${BASE_URL}/checkout/`, 3200);
		await fillCheckoutBlockForm(customerCdp, {
			email: '',
			firstName: 'Nguyễn Thu Hà',
			phone: '0988777666',
		});

		await customerCdp.eval(`document.querySelector('.wc-block-components-checkout-place-order-button').click()`);
		const customerOrderReceivedUrl = await waitForUrlMatch(customerCdp, /\/checkout\/order-received\/(\d+)\//, 25000);
		const m = customerOrderReceivedUrl.match(/\/order-received\/(\d+)\//);
		customerOrderId = m ? Number(m[1]) : 0;

		report.loggedInOrderFlow = {
			headerRegisterHref: registerLinkHref,
			isRegisterFormHighlighted,
			customerEmail,
			myAccountDashboardUrl,
			isCustomerLoggedIn,
			customerOrderId,
			customerOrderReceivedUrl,
		};
		console.log('   Logged-in order placed! ID:', customerOrderId);

		// 6a. In a separate Admin Chrome session, verify HPOS Orders list and change status to Completed
		console.log('6. Verifying orders in Admin HPOS (admin.php?page=wc-orders) and updating status...');
		await withChromeSession(9446, async (adminCdp) => {
			await adminCdp.navigateAndWait(`${BASE_URL}/${LOGIN_SLUG}/`, 1600);
			await adminCdp.eval(`(() => {
				document.getElementById('user_login').value = ${JSON.stringify(ADMIN_USER)};
				document.getElementById('user_pass').value = ${JSON.stringify(ADMIN_PASS)};
				document.getElementById('loginform').submit();
			})()`);
			await sleep(2800);

			await adminCdp.navigateAndWait(`${BASE_URL}/wp-admin/admin.php?page=wc-orders`, 2500);
			const hposCheck = await adminCdp.eval(`(() => {
				const html = document.body.innerHTML;
				return {
					guestVisibleInHpos: html.includes('#${guestOrderId}') || html.includes('Lê Hoàng Lan'),
					customerVisibleInHpos: html.includes('#${customerOrderId}') || html.includes('Nguyễn Thu Hà'),
					packageVisibleInHpos: html.includes('#${PENDING_PACKAGE_ORDER_ID}') || html.includes('Trần Minh Ngọc'),
				};
			})()`);

			await adminCdp.screenshot(path.join(ARTIFACT_DIR, 'step4-admin-hpos-orders-list.png'), true);

			// Update customerOrderId status to wc-completed
			await adminCdp.navigateAndWait(
				`${BASE_URL}/wp-admin/admin.php?page=wc-orders&action=edit&id=${customerOrderId}`,
				2500
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
			await adminCdp.screenshot(path.join(ARTIFACT_DIR, 'step4-admin-hpos-order-status-updated.png'), false);

			report.adminHposAndMyAccount = {
				hposAdminUrl: `${BASE_URL}/wp-admin/admin.php?page=wc-orders`,
				...hposCheck,
			};
		});

		// 6b. Back in customer session, verify My Account -> Orders (/tai-khoan/orders/)
		await customerCdp.navigateAndWait(`${BASE_URL}/tai-khoan/orders/`, 2000);
		const customerMyAccountCheck = await customerCdp.eval(`(() => {
			const html = document.body.innerHTML;
			return {
				customerSeesOrderInMyAccount: html.includes('${customerOrderId}'),
				customerSeesCompletedStatus: html.includes('Đã hoàn thành') || html.includes('Completed'),
			};
		})()`);
		await customerCdp.screenshot(path.join(ARTIFACT_DIR, 'step4-my-account-customer-orders-1440.png'), true);

		await customerCdp.navigateAndWait(`${BASE_URL}/tai-khoan/view-order/${customerOrderId}/`, 2000);
		await customerCdp.screenshot(path.join(ARTIFACT_DIR, 'step4-my-account-view-order-1440.png'), true);

		Object.assign(report.adminHposAndMyAccount, customerMyAccountCheck);
	});
	console.log('   Admin HPOS & My Account check:', report.adminHposAndMyAccount);

	// =========================================================================
	// 7. ACCEPTANCE — Mailpit Branded Vietnamese Emails Verification
	// =========================================================================
	console.log('7. Inspecting Mailpit emails and capturing branded Vietnamese email screenshots...');
	const mailRes = await fetch(`${MAILPIT_URL}/api/v1/messages`);
	const mailData = await mailRes.json();
	const messages = mailData.messages || [];

	const customerMailMsg =
		messages.find((m) => m.Subject && m.Subject.includes('hoàn thành')) ||
		messages.find((m) => m.Subject && m.Subject.includes('nhận được')) ||
		messages[0];

	let sampleEmailDetails = null;
	let sampleEmailHtmlUrl = '';
	if (customerMailMsg) {
		const msgDetailRes = await fetch(`${MAILPIT_URL}/api/v1/message/${customerMailMsg.ID}`);
		const msgDetail = await msgDetailRes.json();
		const lowerHtml = (msgDetail.HTML || '').toLowerCase();
		sampleEmailDetails = {
			id: msgDetail.ID,
			subject: msgDetail.Subject,
			from: msgDetail.From,
			to: msgDetail.To,
			hasLogoImg: lowerHtml.includes('jn-email-logo.svg'),
			hasBrandBgColor: lowerHtml.includes('#faf8f5'),
			hasBrandBaseColor: lowerHtml.includes('#1f1e1d'),
			hasVietnameseFooter: (msgDetail.HTML || '').includes('Sứ mệnh của Jannie'),
			hasFormattedVnd: (msgDetail.HTML || '').includes('450.000') && (msgDetail.HTML || '').includes('₫'),
		};
		sampleEmailHtmlUrl = `${MAILPIT_URL}/view/${customerMailMsg.ID}.html`;
	}

	await withChromeSession(9447, async (mailCdp) => {
		await mailCdp.navigateAndWait(MAILPIT_URL, 2000);
		await mailCdp.screenshot(path.join(ARTIFACT_DIR, 'step4-mailpit-inbox.png'), false);

		// Click first message in Mailpit UI
		await mailCdp.eval(`(() => {
			const row = document.querySelector('.message, a.message, .list-group-item-action');
			if (row) row.click();
		})()`);
		await sleep(1500);
		await mailCdp.screenshot(path.join(ARTIFACT_DIR, 'step4-mailpit-viewer.png'), false);

		if (sampleEmailHtmlUrl) {
			await mailCdp.navigateAndWait(sampleEmailHtmlUrl, 1500);
			await mailCdp.screenshot(path.join(ARTIFACT_DIR, 'step4-mailpit-branded-email.png'), true);
		}
	});

	report.mailpitEmails = {
		totalEmailsCaptured: messages.length,
		subjects: messages.map((m) => ({
			subject: m.Subject,
			from: m.From ? `${m.From.Name} <${m.From.Address}>` : '',
			to: (m.To || []).map((t) => t.Address),
		})),
		sampleEmailVerification: sampleEmailDetails,
	};
	console.log('   Mailpit emails captured:', report.mailpitEmails.totalEmailsCaptured);

	fs.writeFileSync(REPORT_PATH, JSON.stringify(report, null, 2));
	console.log(`Saved full verification report to ${REPORT_PATH}`);
}

main().catch((err) => {
	console.error('Step 4 E2E verification failed:', err);
	process.exit(1);
});
