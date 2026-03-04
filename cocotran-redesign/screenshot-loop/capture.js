/**
 * Auto Screenshot Capture — Cocotran Redesign
 *
 * SETUP (one time):
 *   cd screenshot-loop && npm install
 *
 * USAGE:
 *   node capture.js --url=http://localhost:8888
 *   node capture.js --url=http://localhost:8888 --mobile
 *   node capture.js --url=http://localhost:8888 --watch
 *   node capture.js --url=http://localhost:8888 --page=/services/
 */
import puppeteer from 'puppeteer';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const args = Object.fromEntries(
  process.argv.slice(2).filter(a => a.startsWith('--'))
    .map(a => { const [k,v] = a.slice(2).split('='); return [k, v ?? true]; })
);

const TARGET  = (args.url || 'http://localhost:8888').replace(/\/$/, '') + (args.page || '/');
const OUT     = path.resolve(__dirname, '../screenshot-refs');
const MOBILE  = args.mobile === true;
const TABLET  = args.tablet === true;
const FULL    = args['full-page'] === true;
const WATCH   = args.watch === true;

const VIEWPORTS = {
  desktop: { width: 1440, height: 900,  deviceScaleFactor: 2 },
  tablet:  { width: 768,  height: 1024, isMobile: true, deviceScaleFactor: 2 },
  mobile:  { width: 390,  height: 844,  isMobile: true, deviceScaleFactor: 3 },
};

const vp    = MOBILE ? VIEWPORTS.mobile : TABLET ? VIEWPORTS.tablet : VIEWPORTS.desktop;
const label = MOBILE ? 'mobile' : TABLET ? 'tablet' : 'desktop';

async function capture() {
  const browser = await puppeteer.launch({ headless: 'new', args: ['--no-sandbox'] });
  const page    = await browser.newPage();
  await page.setViewport(vp);

  console.log(`Capturing ${label}: ${TARGET}`);

  try {
    await page.goto(TARGET, { waitUntil: 'networkidle2', timeout: 30000 });
    await new Promise(r => setTimeout(r, 900)); // let animations settle

    if (!fs.existsSync(OUT)) fs.mkdirSync(OUT, { recursive: true });

    const ts      = new Date().toISOString().replace(/[:.]/g, '-').slice(0,19);
    const file    = path.join(OUT, `${ts}-${label}.png`);
    const latest  = path.join(OUT, `latest-${label}.png`);

    await page.screenshot({ path: file, fullPage: FULL });
    fs.copyFileSync(file, latest);

    console.log(`Saved: ${path.basename(file)}`);
    console.log(`Paste ${path.basename(latest)} into Claude → /screenshot-loop`);
  } finally {
    await browser.close();
  }
}

if (WATCH) {
  // Watch plugin CSS files for changes
  const watchDir = path.resolve(__dirname, '../plugin/cocotran-scroll-fx/assets/css');
  console.log(`Watching: ${watchDir}\nPress Ctrl+C to stop.\n`);
  await capture();
  let timer;
  fs.watch(watchDir, { recursive: true }, (_, f) => {
    if (!f?.endsWith('.css')) return;
    clearTimeout(timer);
    timer = setTimeout(async () => {
      console.log(`\nChange in ${f} — capturing...`);
      await capture();
    }, 1200);
  });
} else {
  await capture();
}
