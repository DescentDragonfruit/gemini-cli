/**
 * Screenshot Capture Script — Cocotran WordPress Redesign
 *
 * Automatically captures screenshots of your local WordPress site
 * and saves them to child-theme/screenshot-refs/ for Claude to analyze.
 *
 * SETUP (one time):
 *   cd screenshot-loop
 *   npm init -y
 *   npm install puppeteer
 *
 * USAGE:
 *   node capture.js --url=http://localhost:8888
 *   node capture.js --url=http://localhost:8888 --mobile
 *   node capture.js --url=http://localhost:8888 --full-page
 *   node capture.js --url=http://localhost:8888 --watch
 *
 * OPTIONS:
 *   --url=<url>       WordPress local URL (required)
 *   --out=<path>      Output folder (default: ../child-theme/screenshot-refs)
 *   --mobile          Capture at iPhone 12 viewport (390x844)
 *   --tablet          Capture at iPad viewport (768x1024)
 *   --full-page       Capture entire page (not just viewport)
 *   --watch           Watch for CSS file changes and auto-capture
 *   --page=<path>     Specific page path (e.g. /services/, /travel/)
 *
 * After capturing, paste the screenshot into Claude and say:
 *   "Screenshot loop — analyze this vs. [inspiration URL]"
 */

import puppeteer from 'puppeteer';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// ── Parse CLI args ──────────────────────────────────────────────────────────
const args = Object.fromEntries(
  process.argv.slice(2)
    .filter(a => a.startsWith('--'))
    .map(a => {
      const [key, val] = a.slice(2).split('=');
      return [key, val ?? true];
    })
);

const URL      = args.url || 'http://localhost:8888';
const PAGE     = args.page || '/';
const OUT_DIR  = path.resolve(__dirname, args.out || '../child-theme/screenshot-refs');
const MOBILE   = args.mobile === true;
const TABLET   = args.tablet === true;
const FULLPAGE = args['full-page'] === true;
const WATCH    = args.watch === true;

// ── Viewport presets ────────────────────────────────────────────────────────
const VIEWPORTS = {
  desktop: { width: 1440, height: 900, deviceScaleFactor: 2 },
  tablet:  { width: 768,  height: 1024, isMobile: true, deviceScaleFactor: 2 },
  mobile:  { width: 390,  height: 844,  isMobile: true, deviceScaleFactor: 3 },
};

function getViewport() {
  if (MOBILE)  return VIEWPORTS.mobile;
  if (TABLET)  return VIEWPORTS.tablet;
  return VIEWPORTS.desktop;
}

function getViewportLabel() {
  if (MOBILE) return 'mobile';
  if (TABLET) return 'tablet';
  return 'desktop';
}

// ── Capture function ────────────────────────────────────────────────────────
async function capture() {
  const browser = await puppeteer.launch({
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  const page = await browser.newPage();
  await page.setViewport(getViewport());

  const targetUrl = URL.replace(/\/$/, '') + PAGE;
  console.log(`Capturing: ${targetUrl} (${getViewportLabel()})`);

  try {
    await page.goto(targetUrl, {
      waitUntil: 'networkidle2',
      timeout: 30000,
    });

    // Wait for animations to settle
    await new Promise(resolve => setTimeout(resolve, 800));

    // Ensure output directory exists
    if (!fs.existsSync(OUT_DIR)) {
      fs.mkdirSync(OUT_DIR, { recursive: true });
    }

    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
    const label     = getViewportLabel();
    const filename  = `${timestamp}-${label}.png`;
    const latestFn  = `latest-${label}.png`;

    const filepath  = path.join(OUT_DIR, filename);
    const latestPath = path.join(OUT_DIR, latestFn);

    await page.screenshot({
      path: filepath,
      fullPage: FULLPAGE,
      type: 'png',
    });

    // Also save as "latest.png" for easy reference
    fs.copyFileSync(filepath, latestPath);

    console.log(`Saved: ${filename}`);
    console.log(`Also saved as: ${latestFn}`);
    console.log(`\nNext step: Paste ${latestFn} into Claude and say:`);
    console.log(`"Screenshot loop — analyze this vs. [your inspiration URL]"`);

  } catch (err) {
    console.error('Capture failed:', err.message);
    console.error('Make sure your local WordPress site is running at:', URL);
  } finally {
    await browser.close();
  }
}

// ── Watch mode ──────────────────────────────────────────────────────────────
async function watchMode() {
  const WATCH_DIR = path.resolve(__dirname, '../child-theme/assets/css');

  if (!fs.existsSync(WATCH_DIR)) {
    console.error(`Watch directory not found: ${WATCH_DIR}`);
    process.exit(1);
  }

  console.log(`Watch mode active. Watching: ${WATCH_DIR}`);
  console.log(`Will capture screenshot whenever CSS files change.`);
  console.log(`Press Ctrl+C to stop.\n`);

  let debounceTimer = null;

  fs.watch(WATCH_DIR, { recursive: true }, (event, filename) => {
    if (!filename?.endsWith('.css')) return;

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(async () => {
      console.log(`\nChange detected in ${filename} — capturing screenshot...`);
      await capture();
    }, 1200); // debounce 1.2s to let file writes settle
  });

  // Initial capture
  await capture();
}

// ── Main ────────────────────────────────────────────────────────────────────
if (WATCH) {
  watchMode();
} else {
  capture();
}
