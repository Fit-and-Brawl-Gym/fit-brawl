#!/usr/bin/env node
import fs from 'fs/promises';
import path from 'path';
import { fileURLToPath } from 'url';
import process from 'process';
import puppeteer from 'puppeteer';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

function parseArgs(argv) {
  const args = {};
  for (const part of argv.slice(2)) {
    const eqIdx = part.indexOf('=');
    if (eqIdx > 0) {
      const k = part.substring(0, eqIdx);
      const v = part.substring(eqIdx + 1);
      if (k.startsWith('--')) args[k.slice(2)] = v;
    } else if (part.startsWith('--')) {
      args[part.slice(2)] = true;
    }
  }
  return args;
}

function mmToPx(mm, dpi = 96) { return Math.round((mm * dpi) / 25.4); }
function delay(ms) { return new Promise(resolve => setTimeout(resolve, ms)); }

async function main() {
  const args = parseArgs(process.argv);
  const url = args.url;
  const format = (args.format || 'pdf').toLowerCase(); // 'pdf' | 'png'
  const out = args.output ? path.resolve(args.output) : path.resolve(__dirname, `out.${format}`);
  const selector = args.selector || '.receipt-wrapper';
  const timeout = Number(args.timeout || 20000);

  if (!url) {
    console.error('Missing --url');
    process.exit(2);
  }
  if (!['pdf', 'png'].includes(format)) {
    console.error('Invalid --format, expected pdf|png');
    process.exit(2);
  }

  // Let Puppeteer handle Chrome discovery - it will download if needed
  const browser = await puppeteer.launch({
    headless: 'new',
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-web-security',
      '--disable-features=IsolateOrigins,site-per-process',
      '--font-render-hinting=medium',
    ],
    defaultViewport: {
      width: mmToPx(210), // A4 width as baseline
      height: mmToPx(297),
      deviceScaleFactor: 2,
    }
  });

  try {
    const page = await browser.newPage();
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome Headless Safari/537.36');

    // Navigate and wait until network settles
    await page.goto(url, { waitUntil: 'networkidle2', timeout });

    // Wait for the receipt and QR to appear
    await page.waitForSelector(selector, { timeout });
    await page.waitForSelector('#qrcode img, #qrcode canvas', { timeout }).catch(() => {});

    // Give fonts and layout a moment
    await page.evaluate(() => document.fonts && document.fonts.ready ? document.fonts.ready : null).catch(() => {});
    await delay(200);

    if (format === 'pdf') {
      await page.emulateMediaType('print');
      await page.pdf({
        path: out,
        format: 'A4',
        printBackground: true,
        preferCSSPageSize: false,
        margin: { top: '15mm', right: '15mm', bottom: '15mm', left: '15mm' }
      });
    } else {
      // PNG of the receipt wrapper element at print width (150mm)
      const target = await page.$(selector);
      if (!target) throw new Error('Receipt wrapper not found');

      // Ensure wrapper width ~150mm for consistency
      await page.addStyleTag({ content: `.receipt-wrapper{width:150mm !important;max-width:150mm !important;margin:0 auto !important;}` });
      await delay(100);

      await target.screenshot({ path: out, type: 'png' });
    }

    console.log(out);
  } finally {
    await browser.close();
  }
}

main().catch(async (err) => {
  console.error(err && err.stack || String(err));
  process.exit(1);
});
