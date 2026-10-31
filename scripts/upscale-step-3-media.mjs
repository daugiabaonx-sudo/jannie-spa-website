#!/usr/bin/env node
/**
 * JN — Build 4x Real-ESRGAN masters for the media items flagged `ai_upscale` in an items file
 * (default scripts/lib/jn-step3-media.json), so the import scripts only ever downscale them.
 *
 * Runs on the host (the upscaler needs the GPU via Vulkan/MoltenVK, not the Docker container).
 * Writes scripts/.media-masters/<slug>.src.<ext> (the downloaded original, reused when present — delete it
 * after changing `source_url`) and <slug>.x4.png (gitignored; removed before each rebuild so a failed run
 * never leaves a stale master for the importer). Per item:
 * - `model` picks the Real-ESRGAN model (default realesrgan-x4plus; realesrgan-x4plus-anime for logos);
 * - `ink` (single-colour logos) upscales the logo flattened on white (<slug>.flat.png, made in the wpcli
 *   container by lib/jn-flatten-on-white.php) — the import rebuilds the transparency from the ink coverage.
 * Wayback Machine sources are fetched slowly and retried on HTTP 429.
 *
 * Requires realesrgan-ncnn-vulkan (https://github.com/xinntao/Real-ESRGAN/releases/tag/v0.2.5.0).
 * Override its location with REALESRGAN_BIN; models are read from the `models/` dir next to it.
 *
 * Run:  node scripts/upscale-step-3-media.mjs [--items scripts/lib/jn-legacy-media.json] [<key>...]
 * Then: ./scripts/wp.sh eval-file /scripts/import-step-3-media.php hero_portrait expert_consultation
 *   or: ./scripts/wp.sh eval-file /scripts/import-legacy-media.php
 */
import { spawnSync } from 'node:child_process';
import crypto from 'node:crypto';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { setTimeout as sleep } from 'node:timers/promises';
import { fileURLToPath } from 'node:url';

const SCRIPTS_DIR = path.dirname(fileURLToPath(import.meta.url));
const REPO_DIR = path.dirname(SCRIPTS_DIR);
const DEFAULT_ITEMS_FILE = path.join(SCRIPTS_DIR, 'lib', 'jn-step3-media.json');
const MASTER_DIR = path.join(SCRIPTS_DIR, '.media-masters');
const REALESRGAN_BIN =
	process.env.REALESRGAN_BIN || path.join(os.homedir(), '.local/opt/realesrgan-ncnn-vulkan/realesrgan-ncnn-vulkan');
const DEFAULT_MODEL = 'realesrgan-x4plus';
const DOWNLOAD_TIMEOUT_MS = 30_000;
const ARCHIVE_HOST = 'web.archive.org';
const ARCHIVE_DELAY_MS = 5_000;
const ARCHIVE_BACKOFF_MS = 90_000;
const ARCHIVE_MAX_TRIES = 4;
const HTTP_TOO_MANY_REQUESTS = 429;
const SPAWN_MAX_BUFFER = 256 * 1024 * 1024;
const PNG_SIGNATURE = Buffer.from([0x89, 0x50, 0x4e, 0x47]);
// Leading bytes of the image formats the sources come in (PNG, JPEG, GIF, WebP's RIFF container).
const IMAGE_SIGNATURES = [PNG_SIGNATURE, Buffer.from([0xff, 0xd8, 0xff]), Buffer.from('GIF8'), Buffer.from('RIFF')];

/**
 * Ask lh3 for the original rendition; other hosts are used as-is. (The PHP jn_placeholder_source_url() appends
 * `=s0` unconditionally, but it only ever sees lh3 URLs: legacy items set `require_master` and never download.)
 */
const sourceUrl = (url) =>
	new URL(url).hostname.endsWith('googleusercontent.com') ? url.replace(/=[^/=]*$/, '') + '=s0' : url;

/** Extension of the downloaded original (lh3 URLs have none and serve JPEG). */
const sourceExt = (url) => path.extname(new URL(url).pathname).toLowerCase() || '.jpg';

const isImage = (buf) => IMAGE_SIGNATURES.some((sig) => buf.subarray(0, sig.length).equals(sig));

function parseArgs(argv) {
	const flag = argv.indexOf('--items');
	if (flag >= 0 && !argv[flag + 1]) {
		throw new Error('--items needs a path to an items JSON file');
	}
	const itemsFile = flag >= 0 ? path.resolve(argv[flag + 1]) : DEFAULT_ITEMS_FILE;
	const keys = argv.filter((_, i) => flag < 0 || (i !== flag && i !== flag + 1));
	return { itemsFile, keys };
}

/** Write via a temp file so an interrupted run never leaves a truncated file behind. */
function writeAtomic(dest, buf) {
	const tmp = `${dest}.tmp`;
	fs.writeFileSync(tmp, buf);
	fs.renameSync(tmp, dest);
}

async function download(url, dest) {
	const isArchive = new URL(url).hostname === ARCHIVE_HOST;
	for (let attempt = 1; attempt <= (isArchive ? ARCHIVE_MAX_TRIES : 1); attempt++) {
		if (isArchive) {
			await sleep(ARCHIVE_DELAY_MS);
		}
		const res = await fetch(url, { signal: AbortSignal.timeout(DOWNLOAD_TIMEOUT_MS) });
		if (res.ok) {
			const body = Buffer.from(await res.arrayBuffer());
			if (!isImage(body)) {
				throw new Error(`not an image (${res.headers.get('content-type')}): ${url}`);
			}
			writeAtomic(dest, body);
			return;
		}
		if (!isArchive || res.status !== HTTP_TOO_MANY_REQUESTS) {
			throw new Error(`HTTP ${res.status} for ${url}`);
		}
		console.log(`   ${ARCHIVE_HOST} rate-limited, waiting ${ARCHIVE_BACKOFF_MS / 1000}s...`);
		await sleep(ARCHIVE_BACKOFF_MS);
	}
	throw new Error(`still rate-limited after ${ARCHIVE_MAX_TRIES} tries: ${url}`);
}

function flattenOnWhite(input, output) {
	const run = spawnSync(
		'docker',
		['compose', 'run', '--rm', '--no-deps', '-T', '--entrypoint', 'php', 'wpcli', '/scripts/lib/jn-flatten-on-white.php'],
		{ cwd: REPO_DIR, input: fs.readFileSync(input), maxBuffer: SPAWN_MAX_BUFFER }
	);
	if (run.error) {
		throw run.error;
	}
	if (run.status !== 0 || !run.stdout.subarray(0, PNG_SIGNATURE.length).equals(PNG_SIGNATURE)) {
		throw new Error(`flatten exited ${run.status} without a PNG: ${run.stderr.toString().trim().split('\n').pop()}`);
	}
	writeAtomic(output, run.stdout);
}

function upscale(input, output, model) {
	const run = spawnSync(
		REALESRGAN_BIN,
		['-i', input, '-o', output, '-n', model, '-m', path.join(path.dirname(REALESRGAN_BIN), 'models')],
		{ encoding: 'utf8', maxBuffer: SPAWN_MAX_BUFFER }
	);
	if (run.error) {
		throw run.error;
	}
	if (run.status !== 0 || !fs.existsSync(output)) {
		throw new Error(`realesrgan exited ${run.status}: ${(run.stderr || '').trim().split('\n').pop()}`);
	}
}

function restoreFaces(output) {
	const modelsDir = path.join(path.dirname(REALESRGAN_BIN), 'models');
	const script = path.join(SCRIPTS_DIR, 'lib', 'jn-restore-faces.py');
	const run = spawnSync(
		'uv',
		['run', '--with', 'onnxruntime', '--with', 'numpy', '--with', 'opencv-python-headless', 'python3', script, output, modelsDir],
		{ cwd: REPO_DIR, encoding: 'utf8', maxBuffer: SPAWN_MAX_BUFFER }
	);
	if (run.error) {
		throw run.error;
	}
	if (run.status !== 0) {
		throw new Error(`face_restore exited ${run.status}: ${(run.stderr || run.stdout || '').trim().split('\n').pop()}`);
	}
}

async function buildMaster(item) {
	// `master_slug` points an item at another item's master (Step 3 hero/expert reuse the legacy photos).
	const slug = item.master_slug || item.slug;
	const src = item.original
		? path.join(MASTER_DIR, `${slug}.orig.${item.original}`)
		: path.join(MASTER_DIR, `${slug}.src${sourceExt(item.source_url)}`);
	const out = path.join(MASTER_DIR, `${slug}.x4.png`);
	const flat = path.join(MASTER_DIR, `${slug}.flat.png`);
	// A stale master would be imported silently if this rebuild fails (e.g. an RGBA master after adding `ink`).
	fs.rmSync(out, { force: true });
	fs.rmSync(flat, { force: true });
	if (item.original) {
		if (!fs.existsSync(src)) {
			throw new Error(`original is required but missing: ${src}`);
		}
		const digest = crypto.createHash('sha256').update(fs.readFileSync(src)).digest('hex');
		if (digest !== item.sha256) {
			throw new Error(`original does not match its recorded sha256: ${src}`);
		}
	} else if (!fs.existsSync(src)) {
		await download(sourceUrl(item.source_url), src);
	}
	let input = src;
	if (item.ink) {
		input = flat;
		flattenOnWhite(src, input);
	}
	upscale(input, out, item.model || DEFAULT_MODEL);
	if (item.face_restore) {
		restoreFaces(out);
	}
	return out;
}

async function main() {
	try {
		fs.accessSync(REALESRGAN_BIN, fs.constants.X_OK);
	} catch {
		console.error(`[ERROR] realesrgan-ncnn-vulkan missing or not executable at ${REALESRGAN_BIN} (set REALESRGAN_BIN, or chmod +x it).`);
		process.exit(1);
	}
	const { itemsFile, keys } = parseArgs(process.argv.slice(2));
	const all = JSON.parse(fs.readFileSync(itemsFile, 'utf8'));
	const unknown = keys.filter((key) => !Object.hasOwn(all, key));
	if (unknown.length > 0) {
		console.error(`[ERROR] Unknown media key(s): ${unknown.join(', ')}`);
		process.exit(1);
	}
	const notUpscaled = keys.filter((key) => !all[key].ai_upscale);
	if (notUpscaled.length > 0) {
		console.warn(`[WARN] Skipping key(s) without ai_upscale: ${notUpscaled.join(', ')}`);
	}
	const items = Object.entries(all).filter(([key, item]) => item.ai_upscale && (keys.length === 0 || keys.includes(key)));
	fs.mkdirSync(MASTER_DIR, { recursive: true });

	let failures = 0;
	for (const [key, item] of items) {
		try {
			const out = await buildMaster(item);
			console.log(`[OK] ${key} -> ${path.relative(process.cwd(), out)} (${(fs.statSync(out).size / 1048576).toFixed(1)} MB)`);
		} catch (err) {
			failures += 1;
			console.error(`[ERROR] ${key} -> ${err.message}`);
		}
	}
	process.exit(failures > 0 ? 1 : 0);
}

main().catch((err) => {
	console.error(`[ERROR] ${err.message}`);
	process.exit(1);
});
