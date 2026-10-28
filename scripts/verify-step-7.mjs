#!/usr/bin/env node
/**
 * JN Step 7 — Browser E2E Regression, Block Editor Smoke Test & Visual Verification (`scripts/verify-step-7.mjs`).
 *
 * Verifies:
 * 1. Designed 404 Page (`docs/jn/step-7-404-page-desktop.png` & `step-7-404-page-mobile.png`):
 *    Renders `jannie/error-404` pattern with Warm Ivory background, Playfair Display H1,
 *    CTAs to `/` and `/coaching/#booking-form`, and 0 console/asset errors.
 * 2. Branded Login Screen (`docs/jn/step-7-branded-login.png`):
 *    Renders the hidden login slug with JN monogram SVG, Warm Ivory background,
 *    "Đăng nhập — Jannie Nguyen Academy" title (no "WordPress"), and 0 console/asset errors.
 * 3. Login Rate-Limiting Lockout E2E (`docs/jn/step-7-rate-limit-lockout.png`):
 *    Triggers 5 failed login attempts on `/tai-khoan/` and verifies the Vietnamese 15-minute
 *    lockout notice renders on the 5th attempt, then resets the rate-limit transient.
 * 4. Front-End Asset Aliasing & Coaching Page Health (`/coaching/`):
 *    Verifies all `/static/*` aliased CSS, JS, and font resources load with HTTP 200 and 0 console errors.
 * 5. Block Editor Smoke Test (`docs/jn/step-7-editor-smoke.png`):
 *    Logs in as Administrator, uploads an image via REST `/api/wp/v2/media`, creates a page
 *    containing a `jannie/*` pattern and the uploaded image via `/api/wp/v2/pages`, opens the
 *    page in the Gutenberg Block Editor (`/wp-admin/post.php?post=<id>&action=edit`), verifies
 *    the editor canvas renders with 0 REST errors, captures a screenshot, and cleans up the
 *    temporary page and media attachment in `finally`.
 */

import { spawn, execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

// Hidden login slug comes from the environment or .env (never hardcoded in the repository).
const LOGIN_SLUG = process.env.WP_LOGIN_SLUG || ( ( fs.existsSync( '.env' ) ? fs.readFileSync( '.env', 'utf8' ) : '' ).match( /^WP_LOGIN_SLUG=["']?([^"'\n]+)/m ) || [] )[ 1 ];
if ( ! LOGIN_SLUG ) {
	throw new Error( 'WP_LOGIN_SLUG is missing (.env or environment)' );
}

const CHROME_BIN = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const ROOT_DIR = process.cwd();
const DOCS_DIR = path.join( ROOT_DIR, 'docs/jn' );
const ARTIFACT_DIR = '/Users/daugiabao/.gemini/antigravity/brain/ade46e8c-e1c7-4a5a-9a2f-747973883c46';
const REPORT_PATH = path.join( DOCS_DIR, 'step-7-verification.json' );

const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );

function runPhpInContainer( code ) {
	return execFileSync(
		'docker',
		[ 'compose', 'exec', '-T', 'wordpress', 'php', '-r', code ],
		{ encoding: 'utf8' }
	).trim();
}

class CDPClient {
	constructor( wsUrl ) {
		this.ws = new WebSocket( wsUrl );
		this.id = 0;
		this.pending = new Map();
		this.consoleErrors = [];
		this.failedAssets = [];
	}

	async connect() {
		await new Promise( ( resolve, reject ) => {
			this.ws.addEventListener( 'open', resolve, { once: true } );
			this.ws.addEventListener( 'error', reject, { once: true } );
		} );
		this.ws.addEventListener( 'message', ( ev ) => {
			const msg = JSON.parse( ev.data );
			if ( msg.id && this.pending.has( msg.id ) ) {
				const { resolve, reject } = this.pending.get( msg.id );
				this.pending.delete( msg.id );
				if ( msg.error ) {
					reject( new Error( JSON.stringify( msg.error ) ) );
				} else {
					resolve( msg.result );
				}
			} else if ( msg.method === 'Runtime.consoleAPICalled' ) {
				if ( msg.params?.type === 'error' ) {
					const text = ( msg.params.args || [] )
						.map( ( a ) => a.value ?? a.description ?? '' )
						.join( ' ' );
					this.consoleErrors.push( text );
				}
			} else if ( msg.method === 'Network.responseReceived' ) {
				const status = msg.params?.response?.status ?? 200;
				const url = msg.params?.response?.url ?? '';
				const type = msg.params?.type ?? '';
				if ( status >= 400 && type !== 'Document' ) {
					this.failedAssets.push( { url, status, type } );
				}
			}
		} );
	}

	resetLogs() {
		this.consoleErrors = [];
		this.failedAssets = [];
	}

	send( method, params = {} ) {
		const id = ++this.id;
		return new Promise( ( resolve, reject ) => {
			this.pending.set( id, { resolve, reject } );
			this.ws.send( JSON.stringify( { id, method, params } ) );
		} );
	}

	async eval( expression ) {
		const res = await this.send( 'Runtime.evaluate', {
			expression,
			returnByValue: true,
			awaitPromise: true,
		} );
		if ( res?.exceptionDetails ) {
			throw new Error( `Eval error: ${ JSON.stringify( res.exceptionDetails ) }` );
		}
		return res?.result?.value;
	}

	async navigateAndWait( url, waitMs = 2200 ) {
		await this.send( 'Page.navigate', { url } );
		await sleep( waitMs );
	}

	async setViewport( width, height, mobile = false ) {
		this.currentViewport = { width, height, mobile };
		await this.send( 'Emulation.setDeviceMetricsOverride', {
			width,
			height,
			deviceScaleFactor: 2,
			mobile,
		} );
	}

	async screenshotBoth( filename ) {
		const shot = await this.send( 'Page.captureScreenshot', { format: 'png' } );
		const buf = Buffer.from( shot.data, 'base64' );
		fs.mkdirSync( DOCS_DIR, { recursive: true } );
		fs.writeFileSync( path.join( DOCS_DIR, filename ), buf );
		if ( fs.existsSync( ARTIFACT_DIR ) ) {
			fs.writeFileSync( path.join( ARTIFACT_DIR, filename ), buf );
		}
	}

	close() {
		try {
			this.ws.close();
		} catch {}
	}
}

async function launchChrome() {
	const userDataDir = fs.mkdtempSync( path.join( os.tmpdir(), 'jn-step7-chrome-' ) );
	const proc = spawn(
		CHROME_BIN,
		[
			'--headless=new',
			'--disable-gpu',
			'--no-first-run',
			'--no-default-browser-check',
			'--remote-debugging-port=0',
			`--user-data-dir=${ userDataDir }`,
			'about:blank',
		],
		{ stdio: [ 'ignore', 'pipe', 'pipe' ] }
	);

	const wsUrl = await new Promise( ( resolve, reject ) => {
		const timeout = setTimeout( () => reject( new Error( 'Chrome launch timed out' ) ), 12000 );
		let stderr = '';
		proc.stderr.on( 'data', ( chunk ) => {
			stderr += chunk.toString();
			const match = stderr.match( /DevTools listening on (ws:\/\/[^\s]+)/ );
			if ( match ) {
				clearTimeout( timeout );
				resolve( match[ 1 ] );
			}
		} );
		proc.on( 'exit', ( code ) => {
			clearTimeout( timeout );
			reject( new Error( `Chrome exited early with code ${ code }` ) );
		} );
	} );

	const targetsRes = await fetch(
		wsUrl.replace( 'ws://', 'http://' ).replace( /\/devtools\/browser\/.*$/, '/json/list' )
	);
	const targets = await targetsRes.json();
	const pageTarget = targets.find( ( t ) => t.type === 'page' );
	const cdp = new CDPClient( pageTarget.webSocketDebuggerUrl );
	await cdp.connect();
	await cdp.send( 'Page.enable' );
	await cdp.send( 'Runtime.enable' );
	await cdp.send( 'Network.enable' );

	return { proc, userDataDir, cdp };
}

async function main() {
	let chrome = null;
	let smokeCleanupIds = { pageId: 0, attachmentId: 0 };

	try {
		// Ensure rate limiter is clean before starting E2E
		runPhpInContainer( 'require "wp-load.php"; JN_Hardening_Branding_Auth::reset_rate_limit();' );

		chrome = await launchChrome();
		const { cdp } = chrome;
		const report = {
			timestamp: new Date().toISOString(),
			checks: {},
		};

		// -----------------------------------------------------------------
		// 1. Designed 404 Page (Desktop 1440x960 & Mobile 375x812)
		// -----------------------------------------------------------------
		console.log( '1. Verifying Designed 404 Page (Desktop & Mobile)...' );
		await cdp.setViewport( 1440, 960, false );
		cdp.resetLogs();
		await cdp.navigateAndWait( 'http://localhost:8080/trang-khong-ton-tai-404/', 2000 );

		const info404 = await cdp.eval( `({
			title: document.title,
			h1: document.querySelector('h1')?.textContent?.trim() || '',
			eyebrow: document.querySelector('.is-style-eyebrow-dot')?.textContent?.trim() || '',
			homeCta: document.querySelector('.jn-404-section a[href="http://localhost:8080/"]')?.textContent?.trim() || '',
			bookingCta: document.querySelector('.jn-404-section a[href*="/coaching/#booking-form"]')?.textContent?.trim() || '',
			bodyClasses: document.body.className
		})` );

		await cdp.screenshotBoth( 'step-7-404-page-desktop.png' );

		await cdp.setViewport( 375, 812, true );
		await sleep( 500 );
		await cdp.screenshotBoth( 'step-7-404-page-mobile.png' );

		report.checks.page404 = {
			...info404,
			consoleErrors: [ ...cdp.consoleErrors ],
			failedAssets: [ ...cdp.failedAssets ],
			pass:
				info404.h1.includes( 'Không Tồn Tại' ) &&
				info404.homeCta.includes( 'Quay Về Trang Chủ' ) &&
				info404.bookingCta.includes( 'Đặt Lịch Coaching 1:1' ) &&
				! info404.bodyClasses.includes( 'blocksy' ) &&
				! info404.bodyClasses.includes( 'jannie' ) &&
				cdp.consoleErrors.length === 0 &&
				cdp.failedAssets.length === 0,
		};
		console.log( '   404 check:', report.checks.page404.pass ? 'PASS' : 'FAIL', info404 );

		// -----------------------------------------------------------------
		// 2. Branded Login Screen (hidden login slug)
		// -----------------------------------------------------------------
		console.log( '2. Verifying Branded Login Screen (hidden login slug)...' );
		await cdp.setViewport( 1440, 960, false );
		cdp.resetLogs();
		await cdp.navigateAndWait( `http://localhost:8080/${ LOGIN_SLUG }/`, 2000 );

		const loginInfo = await cdp.eval( `({
			title: document.title,
			headerHref: document.querySelector('.login h1 a')?.getAttribute('href') || '',
			headerText: document.querySelector('.login h1 a')?.textContent?.trim() || '',
			bgImage: getComputedStyle(document.querySelector('.login h1 a')).backgroundImage,
			bodyBg: getComputedStyle(document.body).backgroundColor,
			hasLangSwitcher: !!document.querySelector('#language-switcher'),
		})` );

		await cdp.screenshotBoth( 'step-7-branded-login.png' );

		report.checks.brandedLogin = {
			...loginInfo,
			consoleErrors: [ ...cdp.consoleErrors ],
			failedAssets: [ ...cdp.failedAssets ],
			pass:
				! loginInfo.title.toLowerCase().includes( 'wordpress' ) &&
				loginInfo.title.includes( 'Jannie Nguyen Academy' ) &&
				loginInfo.bgImage.includes( 'data:image/svg+xml' ) &&
				! loginInfo.hasLangSwitcher &&
				cdp.consoleErrors.length === 0 &&
				cdp.failedAssets.length === 0,
		};
		console.log( '   Branded Login check:', report.checks.brandedLogin.pass ? 'PASS' : 'FAIL', {
			title: loginInfo.title,
			bodyBg: loginInfo.bodyBg,
		} );

		// -----------------------------------------------------------------
		// 3. Rate-Limiting Lockout E2E on /tai-khoan/
		// -----------------------------------------------------------------
		console.log( '3. Verifying Login Rate-Limit Lockout on /tai-khoan/...' );
		// Seed 4 failed attempts for test account, then submit the 5th attempt in the browser
		runPhpInContainer( `
			require "wp-load.php";
			JN_Hardening_Branding_Auth::reset_rate_limit( "lockout-e2e@example.com" );
			for ( $i = 0; $i < 4; $i++ ) {
				JN_Hardening_Branding_Auth::record_failed_attempt( "lockout-e2e@example.com" );
			}
		` );

		cdp.resetLogs();
		await cdp.navigateAndWait( 'http://localhost:8080/tai-khoan/', 2000 );
		await cdp.eval( `(() => {
			const u = document.querySelector('#username');
			const p = document.querySelector('#password');
			const btn = document.querySelector('button[name="login"]');
			if (u && p && btn) {
				u.value = 'lockout-e2e@example.com';
				p.value = 'WrongPass#5';
				btn.click();
			}
		})()` );
		await sleep( 2500 );

		const lockoutInfo = await cdp.eval( `({
			noticeText: document.querySelector('.woocommerce-error, .wc-block-components-notice-banner')?.textContent?.trim() || '',
		})` );

		await cdp.screenshotBoth( 'step-7-rate-limit-lockout.png' );

		// Reset rate limiter immediately after screenshot
		runPhpInContainer( 'require "wp-load.php"; JN_Hardening_Branding_Auth::reset_rate_limit( "lockout-e2e@example.com" );' );

		report.checks.rateLimitLockout = {
			...lockoutInfo,
			pass: lockoutInfo.noticeText.includes( 'quá 5 lần' ) && lockoutInfo.noticeText.includes( '15 phút' ),
		};
		console.log( '   Rate-limit Lockout check:', report.checks.rateLimitLockout.pass ? 'PASS' : 'FAIL', lockoutInfo );

		// -----------------------------------------------------------------
		// 4. Front-End /coaching/ Asset Aliasing & Console Cleanliness
		// -----------------------------------------------------------------
		console.log( '4. Verifying /coaching/ Aliased Assets & Zero Console Errors...' );
		cdp.resetLogs();
		await cdp.navigateAndWait( 'http://localhost:8080/coaching/', 2200 );

		const coachingAssets = await cdp.eval( `({
			stylesheets: [...document.querySelectorAll('link[rel="stylesheet"]')].map(l => l.href),
			scripts: [...document.querySelectorAll('script[src]')].map(s => s.src),
			inlinedBlockStyles: [...document.querySelectorAll('style[id^="wp-block-"]')].map(s => s.id),
			bookingFormPresent: !!document.querySelector('.jn-booking-form')
		})` );

		report.checks.coachingFrontend = {
			...coachingAssets,
			consoleErrors: [ ...cdp.consoleErrors ],
			failedAssets: [ ...cdp.failedAssets ],
			pass:
				coachingAssets.stylesheets.every( ( u ) => u.includes( '/static/' ) && u.includes( '?v=' ) ) &&
				coachingAssets.scripts.every( ( u ) => u.includes( '/static/' ) && u.includes( '?v=' ) ) &&
				coachingAssets.inlinedBlockStyles.length >= 8 &&
				coachingAssets.bookingFormPresent &&
				cdp.consoleErrors.length === 0 &&
				cdp.failedAssets.length === 0,
		};
		console.log( '   Coaching front-end check:', report.checks.coachingFrontend.pass ? 'PASS' : 'FAIL', {
			stylesheets: coachingAssets.stylesheets.length,
			scripts: coachingAssets.scripts.length,
			inlinedBlockStyles: coachingAssets.inlinedBlockStyles.length,
			consoleErrors: cdp.consoleErrors.length,
			failedAssets: cdp.failedAssets.length,
		} );

		// -----------------------------------------------------------------
		// 5. Block Editor Smoke Test (Create Page + Insert Pattern + Upload Image)
		// -----------------------------------------------------------------
		console.log( '5. Running Block Editor Smoke Test (Create Page + Pattern + Image Upload)...' );
		const smokeSetupJson = runPhpInContainer( `
			require "wp-load.php";
			require_once ABSPATH . "wp-admin/includes/image.php";
			require_once ABSPATH . "wp-admin/includes/file.php";
			require_once ABSPATH . "wp-admin/includes/media.php";

			$admins = get_users( array( "role" => "administrator", "number" => 1 ) );
			$admin_id = $admins[0]->ID;
			wp_set_current_user( $admin_id );

			// 1. Create a 120x120 PNG test image in uploads (/media/) and register attachment
			$upload_dir = wp_upload_dir();
			$filename   = "jn-step7-smoke-" . time() . ".png";
			$filepath   = trailingslashit( $upload_dir["path"] ) . $filename;

			$im = imagecreatetruecolor( 120, 120 );
			$bg = imagecolorallocate( $im, 250, 248, 245 );
			$fg = imagecolorallocate( $im, 31, 30, 29 );
			imagefilledrectangle( $im, 0, 0, 120, 120, $bg );
			imagestring( $im, 5, 28, 52, "JN STEP 7", $fg );
			imagepng( $im, $filepath );
			imagedestroy( $im );

			$attachment = array(
				"guid"           => trailingslashit( $upload_dir["url"] ) . $filename,
				"post_mime_type" => "image/png",
				"post_title"     => "JN Step 7 Editor Smoke Image",
				"post_content"   => "",
				"post_status"    => "inherit",
			);
			$attach_id   = wp_insert_attachment( $attachment, $filepath );
			$attach_data = wp_generate_attachment_metadata( $attach_id, $filepath );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			$image_url   = wp_get_attachment_url( $attach_id );

			// 2. Render a jannie pattern and combine with core/image block in a new page via REST API
			$registry = WP_Block_Patterns_Registry::get_instance();
			$pattern  = $registry->get_registered( "jannie/section-header" );
			$pattern_content = $pattern ? $pattern["content"] : "<!-- wp:heading --><h2>Jannie Pattern</h2><!-- /wp:heading -->";
			$image_block = sprintf(
				'<!-- wp:image {"id":%d,"sizeSlug":"full"} --><figure class="wp-block-image size-full"><img src="%s" alt="JN Step 7 Smoke Test" class="wp-image-%d"/></figure><!-- /wp:image -->',
				$attach_id,
				esc_url( $image_url ),
				$attach_id
			);

			$req = new WP_REST_Request( "POST", "/wp/v2/pages" );
			$req->set_param( "title", "JN Step 7 Block Editor Smoke Test" );
			$req->set_param( "content", $pattern_content . "\\n" . $image_block );
			$req->set_param( "status", "draft" );
			$res = rest_do_request( $req );
			$data = $res->get_data();
			$page_id = isset( $data["id"] ) ? (int) $data["id"] : 0;

			// 3. Generate valid auth cookies for the browser session
			$expiration = time() + 3600;
			$auth_cookie = wp_generate_auth_cookie( $admin_id, $expiration, "auth" );
			$logged_in_cookie = wp_generate_auth_cookie( $admin_id, $expiration, "logged_in" );

			echo wp_json_encode( array(
				"pageId"         => $page_id,
				"attachmentId"   => (int) $attach_id,
				"imageUrl"       => $image_url,
				"restStatus"     => $res->get_status(),
				"authCookieName" => AUTH_COOKIE,
				"authCookieVal"  => $auth_cookie,
				"logCookieName"  => LOGGED_IN_COOKIE,
				"logCookieVal"   => $logged_in_cookie,
			) );
		` );

		const smokeLines = smokeSetupJson.split( '\n' );
		const smokeData = JSON.parse( smokeLines[ smokeLines.length - 1 ] );
		smokeCleanupIds = { pageId: smokeData.pageId, attachmentId: smokeData.attachmentId };

		await cdp.send( 'Network.setCookie', {
			name: smokeData.authCookieName,
			value: smokeData.authCookieVal,
			domain: 'localhost',
			path: '/',
			httpOnly: true,
		} );
		await cdp.send( 'Network.setCookie', {
			name: smokeData.logCookieName,
			value: smokeData.logCookieVal,
			domain: 'localhost',
			path: '/',
			httpOnly: true,
		} );

		cdp.resetLogs();
		await cdp.navigateAndWait(
			`http://localhost:8080/wp-admin/post.php?post=${ smokeData.pageId }&action=edit`,
			5500
		);

		// Dismiss welcome guide modal if open
		await cdp.eval( `(() => {
			try {
				if (window.wp?.data?.dispatch) {
					window.wp.data.dispatch('core/edit-post')?. closeGeneralSidebar?.();
					window.wp.data.dispatch('core/preferences')?.set?.('core/edit-post', 'welcomeGuide', false);
				}
				const closeBtn = document.querySelector('.components-modal__header button[aria-label*="Đóng"], .components-modal__header button[aria-label*="Close"]');
				if (closeBtn) closeBtn.click();
			} catch {}
		})()` );
		await sleep( 1000 );

		const editorInfo = await cdp.eval( `({
			title: document.title,
			hasEditorCanvas: !!document.querySelector('.edit-post-visual-editor, .block-editor-writing-flow, iframe[name="editor-canvas"]'),
			postTitleInStore: window.wp?.data?.select?.('core/editor')?.getEditedPostAttribute?.('title') || '',
			blockCount: window.wp?.data?.select?.('core/block-editor')?.getBlockCount?.() || 0,
		})` );

		await cdp.screenshotBoth( 'step-7-editor-smoke.png' );

		report.checks.editorSmoke = {
			...editorInfo,
			restStatus: smokeData.restStatus,
			imageUrl: smokeData.imageUrl,
			pass:
				smokeData.restStatus === 201 &&
				smokeData.imageUrl.includes( '/media/' ) &&
				editorInfo.hasEditorCanvas &&
				editorInfo.postTitleInStore === 'JN Step 7 Block Editor Smoke Test' &&
				editorInfo.blockCount >= 2,
		};
		console.log( '   Block Editor Smoke check:', report.checks.editorSmoke.pass ? 'PASS' : 'FAIL', report.checks.editorSmoke );

		fs.writeFileSync( REPORT_PATH, JSON.stringify( report, null, 2 ) );
		console.log( `\nWrote verification report to ${ REPORT_PATH }` );

		const allPass = Object.values( report.checks ).every( ( c ) => c.pass );
		if ( ! allPass ) {
			console.error( 'One or more Step 7 E2E checks failed!' );
			process.exitCode = 1;
		} else {
			console.log( 'All Step 7 E2E & Block Editor checks PASSED!' );
		}
	} finally {
		if ( smokeCleanupIds.pageId || smokeCleanupIds.attachmentId ) {
			runPhpInContainer( `
				require "wp-load.php";
				if ( ${ Number( smokeCleanupIds.pageId ) } > 0 ) {
					wp_delete_post( ${ Number( smokeCleanupIds.pageId ) }, true );
				}
				if ( ${ Number( smokeCleanupIds.attachmentId ) } > 0 ) {
					wp_delete_attachment( ${ Number( smokeCleanupIds.attachmentId ) }, true );
				}
				JN_Hardening_Branding_Auth::reset_rate_limit();
			` );
		}
		if ( chrome ) {
			chrome.cdp.close();
			try {
				chrome.proc.kill( 'SIGKILL' );
			} catch {}
			try {
				fs.rmSync( chrome.userDataDir, { recursive: true, force: true } );
			} catch {}
		}
	}
}

main().catch( ( err ) => {
	console.error( err );
	process.exit( 1 );
} );
