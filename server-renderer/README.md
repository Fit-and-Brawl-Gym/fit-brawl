# Fit & Brawl Receipt Renderer

Server-side PDF/PNG rendering for receipts using Puppeteer (headless Chrome).

## Setup

1. Install Node.js (v18 or higher)
2. Install dependencies:
   ```bash
   cd server-renderer
   npm install
   ```
3. Chrome will be automatically downloaded to `.cache/` directory on first run

## Usage

Called automatically from PHP via `receipt_render.php`.

Manual usage:
```bash
node render-wrapper.js --url="http://localhost/path" --format=pdf --output=out.pdf
```

## File Sizes

- `node_modules/`: ~50MB (required for Puppeteer)
- `.cache/`: ~386MB (Chrome browser)
- Total: ~436MB

These dependencies are excluded from git via `.gitignore`.

## Troubleshooting

If you see "Browser not found" errors:
1. Delete `.cache/` directory
2. Run `npm install` again to re-download Chrome

