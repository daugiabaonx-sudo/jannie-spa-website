#!/usr/bin/env node
/**
 * JN — Build 4x Real-ESRGAN masters for the Step 3 placeholder images flagged `ai_upscale`
 * in scripts/lib/jn-step3-media.json, so import-step-3-media.php only ever downscales them.
 *
 * Runs on the host (the upscaler needs the GPU via Vulkan/MoltenVK, not the Docker container).
 * Writes scripts/.media-masters/<slug>.src.jpg (the =s0 original) and <slug>.x4.png (gitignored).
 *
 * Requires realesrgan-ncnn-vulkan (https://github.com/xinntao/Real-ESRGAN/releases/tag/v0.2.5.0).
 * Override its location with REALESRGAN_BIN; models are read from the `models/` dir next to it.
 *
 * Run:  node scripts/upscale-step-3-media.mjs
 * Then: ./scripts/wp.sh eval-file /scripts/import-step-3-media.php hero_portrait expert_consultation
 */
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const SCRIPTS_DIR = path.dirname(fileURLToPath(import.meta.url));
const ITEMS_FILE = path.join(SCRIPTS_DIR, 'lib', 'jn-step3-media.json');
const MASTER_DIR = path.join(SCRIPTS_DIR, '.media-masters');
const REALESRGAN_BIN =
	process.env.REALESRGAN_BIN || path.join(os.homedir(), '.local/opt/realesrgan-ncnn-vulkan/realesrgan-ncnn-vulkan');
const MODEL = 'realesrgan-x4plus';
const DOWNLOAD_TIMEOUT_MS = 30_000;

/** Mirror of jn_placeholder_source_url(): ask lh3 for the original rendition. */
const sourceUrl = (url) => url.replace(/=[^/=]*$/, '') + '=s0';

async function download(url, dest) {
	const res = await fetch(url, { signal: AbortSignal.timeout(DOWNLOAD_TIMEOUT_MS) });
	if (!res.ok) {
		throw new Error(`HTTP ${res.status} for ${url}`);
	}
	fs.writeFileSync(dest, Buffer.from(await res.arrayBuffer()));
}

function upscale(input, output) {
	const run = spawnSync(
		REALESRGAN_BIN,
		['-i', input, '-o', output, '-n', MODEL, '-m', path.join(path.dirname(REALESRGAN_BIN), 'models')],
		{ encoding: 'utf8' }
	);
	if (run.error) {
		throw run.error;
	}
	if (run.status !== 0 || !fs.existsSync(output)) {
		throw new Error(`realesrgan exited ${run.status}: ${(run.stderr || '').trim().split('\n').pop()}`);
	}
}

async function main() {
	if (!fs.existsSync(REALESRGAN_BIN)) {
		console.error(`[ERROR] realesrgan-ncnn-vulkan not found at ${REALESRGAN_BIN} (set REALESRGAN_BIN).`);
		process.exit(1);
	}
	const items = Object.entries(JSON.parse(fs.readFileSync(ITEMS_FILE, 'utf8'))).filter(([, item]) => item.ai_upscale);
	fs.mkdirSync(MASTER_DIR, { recursive: true });

	let failures = 0;
	for (const [key, item] of items) {
		const src = path.join(MASTER_DIR, `${item.slug}.src.jpg`);
		const out = path.join(MASTER_DIR, `${item.slug}.x4.png`);
		try {
			await download(sourceUrl(item.source_url), src);
			upscale(src, out);
			console.log(`[OK] ${key} -> ${path.relative(process.cwd(), out)} (${(fs.statSync(out).size / 1048576).toFixed(1)} MB)`);
		} catch (err) {
			failures += 1;
			console.error(`[ERROR] ${key} -> ${err.message}`);
		}
	}
	process.exit(failures > 0 ? 1 : 0);
}

main();
