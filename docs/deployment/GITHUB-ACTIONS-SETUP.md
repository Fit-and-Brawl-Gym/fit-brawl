# GitHub Actions CI/CD Setup Guide

## Automated Deployment with GitHub Actions

This repository includes a GitHub Actions workflow that automatically deploys your code to your production server whenever you push to the `main` branch.

## Setup Instructions

### 1. Configure GitHub Repository Secrets

Go to your GitHub repository → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**

Add the following secrets:

| Secret Name | Description | Example Value |
|-------------|-------------|---------------|
| `SSH_HOST` | Your server's IP address or domain | `54.227.103.23` or `yourdomain.com` |
| `SSH_USER` | SSH username (usually `ubuntu` or `ec2-user` for AWS) | `ubuntu` |
| `SSH_PRIVATE_KEY` | Your SSH private key | Contents of `~/.ssh/id_rsa` |
| `SSH_PORT` | SSH port (optional, defaults to 22) | `22` |

### 2. Get Your SSH Private Key

On your **local machine** (where you SSH into the server):

```bash
# Display your private key
cat ~/.ssh/id_rsa

# Or if you use a different key:
cat ~/.ssh/your-key-name.pem
```

Copy the **entire output** including the `-----BEGIN` and `-----END` lines and paste it into the `SSH_PRIVATE_KEY` secret.

### 3. Setup Server for Auto-Deployment

SSH into your production server and run:

```bash
# Navigate to web root
cd /var/www/html

# Ensure git is configured
git config --global --add safe.directory /var/www/html

# Ensure www-data can access files
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html

# Allow GitHub to pull without password (if private repo)
# Option 1: Use deploy key (recommended)
# - Go to GitHub repo → Settings → Deploy keys → Add deploy key
# - Paste the server's public key: cat ~/.ssh/id_rsa.pub

# Option 2: Clone via HTTPS with token
# git remote set-url origin https://YOUR_GITHUB_TOKEN@github.com/Mikell-Razon/fit-brawl.git
```

### 4. (Optional) Setup Systemd Service for Renderer

Create a systemd service to keep the renderer running:

```bash
sudo nano /etc/systemd/system/fit-brawl-renderer.service
```

Add this content:

```ini
[Unit]
Description=Fit & Brawl Receipt Renderer Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html/server-renderer
Environment="NODE_ENV=production"
Environment="PUPPETEER_CACHE_DIR=/var/www/html/server-renderer/.cache"
ExecStart=/usr/bin/node /var/www/html/server-renderer/server.js
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
sudo systemctl daemon-reload
sudo systemctl enable fit-brawl-renderer
sudo systemctl start fit-brawl-renderer
sudo systemctl status fit-brawl-renderer
```

### 5. Test the Deployment

1. Make a small change to any file in your repository
2. Commit and push:
   ```bash
   git add .
   git commit -m "Test auto-deployment"
   git push origin main
   ```
3. Go to GitHub → **Actions** tab
4. Watch the deployment progress
5. Check your production server to verify changes

## Manual Deployment

You can also trigger deployment manually:

1. Go to GitHub → **Actions** tab
2. Click **Deploy to Production** workflow
3. Click **Run workflow** → **Run workflow**

## Troubleshooting

### Deployment fails with "Permission denied"

Make sure your SSH private key is correct and the user has sudo access:

```bash
# On server, add user to sudo group
sudo usermod -aG sudo ubuntu

# Or configure passwordless sudo for specific commands
sudo visudo
# Add: ubuntu ALL=(ALL) NOPASSWD: /bin/systemctl restart fit-brawl-renderer
```

### "git pull" fails with authentication error

Use a deploy key or personal access token as described in Step 3.

### Changes don't appear on production

1. Check the Actions tab for errors
2. SSH into server and manually pull:
   ```bash
   cd /var/www/html
   git pull origin main
   ```
3. Check file permissions:
   ```bash
   ls -la /var/www/html
   ```

## What Gets Deployed

- All PHP code changes
- CSS/JS updates
- Server-renderer updates (with automatic npm install)
- Configuration changes (be careful with `.env`!)

## What Doesn't Get Deployed

- `.env` file (kept on server only)
- `uploads/` directory (user-generated content)
- `vendor/` directory (managed by Composer on server)
- `node_modules/` (rebuilt during deployment)

## Security Notes

⚠️ **Never commit sensitive data:**
- Keep `.env` out of git (it's in `.gitignore`)
- Use GitHub Secrets for deployment credentials
- Rotate SSH keys periodically
- Use deploy keys instead of personal SSH keys when possible
