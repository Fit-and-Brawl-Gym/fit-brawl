#!/usr/bin/env node
import express from 'express';
import { spawn } from 'child_process';
import path from 'path';
import { promises as fs } from 'fs';
import os from 'os';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
app.use(express.json());

const PORT = process.env.PORT || 3000;

// Simple health endpoint
app.get('/health', (req, res) => res.json({ ok: true }));

// POST /render { url, format, selector, timeout }
app.post('/render', async (req, res) => {
  const { url, format = 'pdf', selector = '.receipt-wrapper', timeout = 20000 } = req.body || {};
  if (!url || !/^https?:\/\//i.test(url)) {
    return res.status(400).json({ error: 'Invalid or missing url' });
  }
  if (!['pdf', 'png'].includes(String(format).toLowerCase())) {
    return res.status(400).json({ error: 'format must be pdf|png' });
  }

  const wrapper = path.join(__dirname, 'render-wrapper.js');
  const ext = String(format).toLowerCase() === 'png' ? '.png' : '.pdf';
  const tmpFile = path.join(os.tmpdir(), `fb-render-${Date.now()}-${Math.random().toString(36).slice(2)}${ext}`);

  const args = [wrapper, `--url=${url}`, `--format=${format}`, `--timeout=${timeout}`, `--output=${tmpFile}`, `--selector=${selector}`];
  const child = spawn('node', args, {
    cwd: __dirname,
    env: { ...process.env, PUPPETEER_CACHE_DIR: path.join(__dirname, '.cache') }
  });

  let stdout = '';
  let stderr = '';
  child.stdout.on('data', (d) => (stdout += d.toString()));
  child.stderr.on('data', (d) => (stderr += d.toString()));

  child.on('close', async (code) => {
    if (code === 0) {
      try {
        const buf = await fs.readFile(tmpFile);
        await fs.unlink(tmpFile).catch(() => {});
        if (ext === '.pdf') {
          res.setHeader('Content-Type', 'application/pdf');
        } else {
          res.setHeader('Content-Type', 'image/png');
        }
        res.setHeader('Cache-Control', 'no-store');
        return res.send(buf);
      } catch (e) {
        return res.status(500).json({ ok: false, error: `Failed to read rendered file: ${e.message}` });
      }
    }
    res.status(500).json({ ok: false, error: stderr || stdout || `Renderer exited with code ${code}` });
  });
});

app.listen(PORT, () => {
  // eslint-disable-next-line no-console
  console.log(`Renderer server listening on :${PORT}`);
});
