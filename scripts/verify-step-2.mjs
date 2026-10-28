#!/usr/bin/env node
/**
 * JN STEP 2 — Automated Browser Verification, Font/Network Audit,
 * Axe Contrast Audit, Keyboard Off-Canvas Test & Multi-Breakpoint Screenshots.
 */
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const CHROME_BIN = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const AXE_PATH = '/Users/daugiabao/.gemini/antigravity/brain/1d79ab15-e3c2-4006-8b14-1ead1fdcf2e0/scratch/axe.min.js';

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

async function captureElementClip(cdp, selector, outPath, padding = 0) {
	const rect = await cdp.eval(`(() => {
		const el = document.querySelector(${JSON.stringify(selector)});
		if (!el) return null;
		const r = el.getBoundingClientRect();
		return {
			x: Math.max(0, Math.floor(r.left + window.scrollX - ${padding})),
			y: Math.max(0, Math.floor(r.top + window.scrollY - ${padding})),
			width: Math.ceil(r.width + ${padding * 2}),
			height: Math.ceil(r.height + ${padding * 2}),
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
	fs.writeFileSync(outPath, Buffer.from(shot.data, 'base64'));
	return rect;
}

async function main() {
	const env = loadEnv();
	const base = (env.WP_HOME || 'http://localhost:8080').replace(/\/$/, '');
	const slug = ( env.WP_LOGIN_SLUG || ( () => { throw new Error( 'WP_LOGIN_SLUG is missing from .env' ); } )() );
	const user = env.WP_ADMIN_USER;
	const pass = env.WP_ADMIN_PASSWORD;

	const userDataDir = fs.mkdtempSync(path.join(os.tmpdir(), 'jn-step2-chrome-'));
	const debugPort = 9334;

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

	const report = {
		timestamp: new Date().toISOString(),
		network: {},
		fonts: {},
		axe: {},
		keyboard: {},
		editorPalette: {},
		screenshots: [],
	};

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
		await cdp.send('Network.enable');
		await cdp.send('DOM.enable');
		await cdp.send('CSS.enable');

		const networkUrls = [];
		cdp.on('Network.requestWillBeSent', (params) => {
			if (params?.request?.url) {
				networkUrls.push({
					url: params.request.url,
					type: params.type || 'Other',
				});
			}
		});

		await cdp.send('Emulation.setDeviceMetricsOverride', {
			width: 1440,
			height: 1200,
			deviceScaleFactor: 2,
			mobile: false,
		});

		console.log(`=== 1. Navigating to ${base}/coaching/ for Network, Font & Specimen Audit ===`);
		await cdp.send('Page.navigate', { url: `${base}/coaching/` });
		for (let i = 0; i < 40; i++) {
			const ready = await cdp.eval(`document.readyState === 'complete'`);
			if (ready) break;
			await sleep(200);
		}
		await cdp.eval(`document.fonts.ready.then(() => true)`);
		await sleep(600);

		const externalRequests = networkUrls.filter(
			(r) =>
				!r.url.startsWith(base) &&
				!r.url.startsWith('data:') &&
				!r.url.startsWith('blob:')
		);
		const googleFontRequests = networkUrls.filter(
			(r) =>
				r.url.includes('fonts.googleapis.com') ||
				r.url.includes('fonts.gstatic.com')
		);
		const preloadedFonts = await cdp.eval(`
			Array.from(document.querySelectorAll('link[rel="preload"][as="font"]')).map(el => el.getAttribute('href'))
		`);

		report.network = {
			totalRequests: networkUrls.length,
			externalRequestsCount: externalRequests.length,
			externalRequests,
			googleFontRequestsCount: googleFontRequests.length,
			preloadedFontsCount: preloadedFonts.length,
			preloadedFonts,
			allUrls: networkUrls.map((r) => r.url),
		};
		console.log('Network audit:', {
			totalRequests: report.network.totalRequests,
			externalRequestsCount: report.network.externalRequestsCount,
			googleFontRequestsCount: report.network.googleFontRequestsCount,
			preloadedFontsCount: report.network.preloadedFontsCount,
		});

		// Verify platform fonts used for Vietnamese headings & italic accents
		const docNode = await cdp.send('DOM.getDocument', { depth: -1 });
		const selectorsToCheck = [
			'.entry-content h1',
			'.entry-content h1 em',
			'.entry-content h2',
			'.entry-content h2 em',
			'.entry-content h3',
			'.entry-content h3 em',
			'.entry-content h4',
			'.entry-content h4 em',
			'.entry-content h5',
			'.entry-content h6',
			'.entry-content p',
			'.entry-content p em',
		];
		const fontUsage = [];
		let fallbackGlyphsTotal = 0;
		for (const sel of selectorsToCheck) {
			const q = await cdp.send('DOM.querySelector', {
				nodeId: docNode.root.nodeId,
				selector: sel,
			});
			if (q?.nodeId) {
				const pf = await cdp.send('CSS.getPlatformFontsForNode', {
					nodeId: q.nodeId,
				});
				const fonts = pf?.fonts || [];
				for (const f of fonts) {
					const isSelfHosted =
						f.isCustomFont &&
						(f.familyName.includes('Playfair Display') ||
							f.familyName.includes('Geist'));
					if (!isSelfHosted) {
						fallbackGlyphsTotal += f.glyphCount;
					}
				}
				fontUsage.push({ selector: sel, fonts });
			}
		}
		report.fonts = {
			fallbackGlyphsTotal,
			fontUsage,
		};
		console.log('Font glyph audit — fallbackGlyphsTotal:', fallbackGlyphsTotal);

		// Capture Vietnamese specimen & full front-end screenshots
		await captureElementClip(
			cdp,
			'.entry-content .jn-section',
			path.resolve('docs/jn/step-2-vietnamese-specimen.png'),
			0
		);
		const fullFrontShot = await cdp.send('Page.captureScreenshot', {
			format: 'png',
			captureBeyondViewport: true,
		});
		fs.writeFileSync(
			path.resolve('docs/jn/step-2-frontend-full.png'),
			Buffer.from(fullFrontShot.data, 'base64')
		);
		report.screenshots.push(
			'docs/jn/step-2-vietnamese-specimen.png',
			'docs/jn/step-2-frontend-full.png'
		);

		console.log('=== 2. Running axe-core WCAG 2.1 AA Colour-Contrast & Accessibility Audit ===');
		const axeSource = fs.readFileSync(AXE_PATH, 'utf8');
		await cdp.eval(axeSource);
		const axeResults = await cdp.eval(`
			axe.run(document, {
				runOnly: {
					type: 'tag',
					values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']
				}
			}).then(res => ({
				violationsCount: res.violations.length,
				colorContrastViolations: res.violations.filter(v => v.id === 'color-contrast'),
				violations: res.violations.map(v => ({
					id: v.id,
					impact: v.impact,
					description: v.description,
					nodes: v.nodes.map(n => ({ html: n.html, summary: n.failureSummary }))
				})),
				passesCount: res.passes.length
			}))
		`);
		report.axe = axeResults;
		console.log('Axe audit:', {
			violationsCount: axeResults.violationsCount,
			colorContrastViolationsCount: axeResults.colorContrastViolations.length,
			passesCount: axeResults.passesCount,
		});
		if (axeResults.violations.length > 0) {
			console.log('Axe violations details:', JSON.stringify(axeResults.violations, null, 2));
		}

		console.log('=== 3. Capturing Header & Footer at 1440 / 1024 / 768 / 375 + Off-Canvas Keyboard Audit ===');
		const breakpoints = [1440, 1024, 768, 375];
		for (const bp of breakpoints) {
			await cdp.send('Emulation.setDeviceMetricsOverride', {
				width: bp,
				height: 900,
				deviceScaleFactor: 2,
				mobile: bp < 768,
			});
			await cdp.eval(`window.scrollTo(0, 0)`);
			await sleep(300);

			const headerPath = path.resolve(`docs/jn/step-2-header-${bp}.png`);
			await captureElementClip(cdp, '#header.ct-header', headerPath, 0);
			report.screenshots.push(`docs/jn/step-2-header-${bp}.png`);

			const footerPath = path.resolve(`docs/jn/step-2-footer-${bp}.png`);
			await captureElementClip(cdp, '#footer.ct-footer', footerPath, 0);
			report.screenshots.push(`docs/jn/step-2-footer-${bp}.png`);

			if (bp === 1024 || bp === 375) {
				// Test keyboard activation & Escape closing on off-canvas menu (< 1280px)
				await cdp.eval(`window.scrollTo(0, 0)`);
				await sleep(200);

				const openedState = await cdp.eval(`(() => {
					const triggers = Array.from(document.querySelectorAll('#header .ct-header-trigger'));
					const visibleTrigger = triggers.find(t => t.offsetParent !== null);
					if (!visibleTrigger) return { error: 'No visible .ct-header-trigger' };
					visibleTrigger.focus();
					visibleTrigger.click();
					return {
						triggerFocused: document.activeElement === visibleTrigger,
						triggerAriaLabel: visibleTrigger.getAttribute('aria-label'),
					};
				})()`);
				await sleep(450);

				const drawerDetails = await cdp.eval(`(() => {
					const panel = document.getElementById('offcanvas');
					const isOpen = panel && (panel.classList.contains('active') || document.body.hasAttribute('data-panel'));
					const visibleContent = Array.from(panel.querySelectorAll('.ct-panel-content')).find(c => c.offsetParent !== null) || panel.querySelector('.ct-panel-content');
					const links = visibleContent ? Array.from(visibleContent.querySelectorAll('.mobile-menu a.ct-menu-link')).map(a => a.textContent.trim()) : [];
					const cta = visibleContent ? visibleContent.querySelector('.ct-header-cta .ct-button')?.textContent?.trim() : null;
					return {
						isOpen,
						ariaModal: panel?.getAttribute('aria-modal'),
						activeTag: document.activeElement?.className || document.activeElement?.tagName,
						links,
						cta,
					};
				})()`);

				// Capture off-canvas screenshot
				const offcanvasShot = await cdp.send('Page.captureScreenshot', {
					format: 'png',
				});
				fs.writeFileSync(
					path.resolve(`docs/jn/step-2-offcanvas-${bp}.png`),
					Buffer.from(offcanvasShot.data, 'base64')
				);
				report.screenshots.push(`docs/jn/step-2-offcanvas-${bp}.png`);

				// Press Escape key via CDP Input.dispatchKeyEvent
				await cdp.send('Input.dispatchKeyEvent', {
					type: 'keyDown',
					key: 'Escape',
					code: 'Escape',
					windowsVirtualKeyCode: 27,
					nativeVirtualKeyCode: 27,
				});
				await cdp.send('Input.dispatchKeyEvent', {
					type: 'keyUp',
					key: 'Escape',
					code: 'Escape',
					windowsVirtualKeyCode: 27,
					nativeVirtualKeyCode: 27,
				});
				await sleep(450);

				const closedState = await cdp.eval(`(() => {
					const panel = document.getElementById('offcanvas');
					return {
						isClosed: !panel.classList.contains('active'),
						activeClassAfterEsc: document.activeElement?.className || '',
					};
				})()`);

				report.keyboard[`bp_${bp}`] = {
					openedState,
					drawerDetails,
					closedState,
				};
				console.log(`Keyboard off-canvas test at ${bp}px:`, report.keyboard[`bp_${bp}`]);
			}
		}

		console.log('=== 4. Logging into WP Admin & Inspecting Block Editor Palette + Canvas ===');
		await cdp.send('Emulation.setDeviceMetricsOverride', {
			width: 1440,
			height: 1050,
			deviceScaleFactor: 2,
			mobile: false,
		});

		await cdp.send('Page.navigate', { url: `${base}/${slug}/` });
		for (let i = 0; i < 30; i++) {
			const hasForm = await cdp.eval(`Boolean(document.querySelector('#loginform'))`);
			if (hasForm) break;
			await sleep(200);
		}
		await cdp.eval(`
			document.querySelector('#user_login').value = ${JSON.stringify(user)};
			document.querySelector('#user_pass').value = ${JSON.stringify(pass)};
			document.querySelector('#loginform').submit();
		`);
		for (let i = 0; i < 40; i++) {
			const url = await cdp.eval(`location.href`);
			const ready = await cdp.eval(`document.readyState === 'complete'`);
			if (url && url.includes('/wp-admin/') && ready) break;
			await sleep(250);
		}

		// Navigate to Block Editor for Coaching page (ID 9)
		await cdp.send('Page.navigate', {
			url: `${base}/wp-admin/post.php?post=9&action=edit`,
		});
		for (let i = 0; i < 60; i++) {
			const loaded = await cdp.eval(`Boolean(
				window.wp &&
				window.wp.data &&
				window.wp.data.select('core/block-editor') &&
				window.wp.data.select('core/block-editor').getBlocks().length > 0 &&
				(document.querySelector('iframe[name="editor-canvas"]') || document.querySelector('.editor-styles-wrapper'))
			)`);
			if (loaded) break;
			await sleep(300);
		}
		await sleep(1000);

		// Dismiss welcome guide and fullscreen mode notifications if present
		const editorPaletteData = await cdp.eval(`(() => {
			try {
				if (wp.data.dispatch('core/preferences')) {
					wp.data.dispatch('core/preferences').set('core/edit-post', 'welcomeGuide', false);
				}
			} catch (e) {}

			const settings = wp.data.select('core/block-editor').getSettings();
			const colors = settings.colors || [];
			const featuresColor = settings.__experimentalFeatures?.color || {};
			const themePalette = featuresColor.palette?.theme || colors;
			const defaultPaletteEnabled = Boolean(featuresColor.defaultPalette);
			const defaultPaletteItems = featuresColor.palette?.default || [];

			const slugs = themePalette.map(c => c.slug);
			const hexes = themePalette.map(c => c.color.toUpperCase());
			const uniqueSlugs = new Set(slugs);
			const uniqueHexes = new Set(hexes);

			// Select the first paragraph or heading block so the Color panel shows in the inspector
			const blocks = wp.data.select('core/block-editor').getBlocks();
			if (blocks.length > 0) {
				const inner = blocks[0].innerBlocks?.[0]?.innerBlocks || blocks[0].innerBlocks || blocks;
				const targetBlock = inner.find(b => b.name === 'core/heading' || b.name === 'core/paragraph') || blocks[0];
				wp.data.dispatch('core/block-editor').selectBlock(targetBlock.clientId);
			}

			return {
				count: themePalette.length,
				uniqueSlugCount: uniqueSlugs.size,
				uniqueHexCount: uniqueHexes.size,
				defaultPaletteEnabled,
				defaultPaletteCount: defaultPaletteItems.length,
				entries: themePalette,
			};
		})()`);

		report.editorPalette = editorPaletteData;
		console.log('Editor Palette Audit:', {
			count: editorPaletteData.count,
			uniqueSlugCount: editorPaletteData.uniqueSlugCount,
			uniqueHexCount: editorPaletteData.uniqueHexCount,
			defaultPaletteEnabled: editorPaletteData.defaultPaletteEnabled,
		});

		await sleep(600);
		// Open the Text color popover in the Block Inspector so the 17 swatches are visible in the screenshot
		await cdp.eval(`(() => {
			const colorButtons = Array.from(document.querySelectorAll('.block-editor-panel-color-gradient-settings__dropdown, .color-block-support-panel button'));
			if (colorButtons.length > 0) {
				colorButtons[0].click();
			}
		})()`);
		await sleep(600);

		const editorShot = await cdp.send('Page.captureScreenshot', {
			format: 'png',
		});
		fs.writeFileSync(
			path.resolve('docs/jn/step-2-editor-palette.png'),
			Buffer.from(editorShot.data, 'base64')
		);
		report.screenshots.push('docs/jn/step-2-editor-palette.png');

		fs.writeFileSync(
			path.resolve('docs/jn/step-2-verification-report.json'),
			JSON.stringify(report, null, 2) + '\n'
		);

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
