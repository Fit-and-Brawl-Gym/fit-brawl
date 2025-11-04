#!/usr/bin/env node
// HTTP Server wrapper for Cloud Run
import http from 'http';
import { execFile } from 'child_process';
import { fileURLToPath } from 'url';
import { promisify } from 'util';
import path from 'path';
import fs from 'fs/promises';
import { tmpdir } from 'os';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const execFileAsync = promisify(execFile);

const PORT = process.env.PORT || 8080;

// HTTP server for Cloud Run
const server = http.createServer(async (req, res) => {
  // Health check endpoint
  if (req.url === '/' || req.url === '/health') {
    res.writeHead(200, { 'Content-Type': 'text/plain' });
    res.end('Receipt Renderer Service Running\n');
    return;
  }

  // Render endpoint: /render?url=...&format=...
  if (req.url.startsWith('/render')) {
    try {
      const url = new URL(req.url, `http://localhost:${PORT}`);
      const targetUrl = url.searchParams.get('url');
      const format = url.searchParams.get('format') || 'pdf';
      const selector = url.searchParams.get('selector') || '.receipt-wrapper';

      if (!targetUrl) {
        res.writeHead(400, { 'Content-Type': 'text/plain' });
        res.end('Missing url parameter\n');
        return;
      }

      // Create temp output file
      const tempFile = path.join(tmpdir(), `receipt-${Date.now()}.${format}`);

      // Execute render.js
      const renderScript = path.join(__dirname, 'render.js');
      await execFileAsync('node', [
        renderScript,
        `--url=${targetUrl}`,
        `--format=${format}`,
        `--output=${tempFile}`,
        `--selector=${selector}`,
        '--timeout=30000'
      ]);

      // Read and send file
      const fileContent = await fs.readFile(tempFile);
      const contentType = format === 'pdf' ? 'application/pdf' : 'image/png';

      res.writeHead(200, {
        'Content-Type': contentType,
        'Content-Length': fileContent.length,
        'Content-Disposition': `inline; filename="receipt.${format}"`
      });
      res.end(fileContent);

      // Cleanup
      await fs.unlink(tempFile).catch(() => {});

    } catch (error) {
      console.error('Render error:', error);
      res.writeHead(500, { 'Content-Type': 'text/plain' });
      res.end(`Error rendering: ${error.message}\n`);
    }
    return;
  }

  // 404 for other routes
  res.writeHead(404, { 'Content-Type': 'text/plain' });
  res.end('Not Found\n');
});

server.listen(PORT, () => {
  console.log(`Receipt renderer listening on port ${PORT}`);
});

