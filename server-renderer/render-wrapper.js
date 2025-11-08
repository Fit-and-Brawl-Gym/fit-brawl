#!/usr/bin/env node
// Wrapper to set PUPPETEER_CACHE_DIR to a writable location
import { execFileSync } from 'child_process';
import { fileURLToPath } from 'url';
import path from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Set cache directory to server-renderer/.cache (relative to this script)
const cacheDir = path.join(__dirname, '.cache');
process.env.PUPPETEER_CACHE_DIR = cacheDir;
process.env.PUPPETEER_SKIP_CHROMIUM_DOWNLOAD = 'false';

// Execute the actual render script with all arguments
try {
  execFileSync(
    'node',
    [path.join(__dirname, 'render.js'), ...process.argv.slice(2)],
    { cwd: __dirname, env: process.env, stdio: 'inherit' }
  );
} catch (error) {
  process.exit(error.status || 1);
}

