# 🚀 Quick Start: Deploy to GitHub

## Commit and Push Changes

```bash
# Add all new files
git add .github/workflows/deploy.yml
git add docs/deployment/GITHUB-ACTIONS-SETUP.md
git add deploy.sh build.sh build.ps1
git add BUILD-SUMMARY.md
git add scripts/import_seed.php

# Commit changes
git commit -m "Fix avatar upload headers error, fix receipt rendering ERR_BLOCKED_BY_CLIENT, add CI/CD deployment"

# Push to GitHub
git push origin main
```

## Setup Auto-Deployment (One-Time)

### 1. Get Your SSH Private Key
```bash
# On your local machine:
cat ~/.ssh/id_rsa
# Copy EVERYTHING including -----BEGIN and -----END lines
```

### 2. Add GitHub Secrets
1. Go to https://github.com/Mikell-Razon/fit-brawl/settings/secrets/actions
2. Click **New repository secret**
3. Add these 3 secrets:

| Name | Value |
|------|-------|
| `SSH_HOST` | `54.227.103.23` (or your domain) |
| `SSH_USER` | `ubuntu` (or your SSH username) |
| `SSH_PRIVATE_KEY` | Paste your private key |

### 3. Configure Server (First Time Only)
```bash
# SSH into your server
ssh ubuntu@54.227.103.23

# Navigate to project
cd /var/www/html

# Allow git to work
git config --global --add safe.directory /var/www/html

# Pull latest changes manually first time
git pull origin main

# Install renderer dependencies
cd server-renderer
npm ci --no-audit --no-fund

# (Optional) Setup systemd service for renderer
sudo nano /etc/systemd/system/fit-brawl-renderer.service
# Copy content from docs/deployment/GITHUB-ACTIONS-SETUP.md

sudo systemctl enable fit-brawl-renderer
sudo systemctl start fit-brawl-renderer
```

## Import Database Seed

```bash
# On server:
ssh ubuntu@54.227.103.23
cd /var/www/html
php scripts/import_seed.php
# Type 'yes' to confirm
```

## Test Everything

### 1. Test Locally
```bash
# Build project
bash build.sh

# Start renderer
cd server-renderer
node server.js
```

### 2. Test on Production
1. Visit your site
2. Login with test account
3. Upload an avatar → Should work without headers warning
4. Create a booking and download receipt → Should work without ERR_BLOCKED_BY_CLIENT

### 3. Test Auto-Deployment
```bash
# Make a small change
echo "# Test" >> README.md

# Commit and push
git add README.md
git commit -m "Test auto-deployment"
git push origin main

# Watch deployment: https://github.com/Mikell-Razon/fit-brawl/actions
```

## Manual Deployment (Alternative)

If you prefer manual deployments:

```bash
# Edit deploy.sh with your server details (already configured)
bash deploy.sh
```

## Troubleshooting

### Deployment fails
- Check GitHub Actions logs: https://github.com/Mikell-Razon/fit-brawl/actions
- Verify SSH secrets are correct
- Make sure server can pull from GitHub

### Receipt rendering still fails
```bash
# On server, test manually:
cd /var/www/html/server-renderer
node render.js --url=http://localhost/php/receipt_service.php?id=TEST-123&render=1 --format=pdf
```

### Headers still sent error
- Make sure you pushed the file_upload_security.php changes
- Clear PHP opcache: `sudo systemctl restart apache2` or `sudo systemctl restart php-fpm`

## 📁 What Was Fixed

✅ Avatar upload headers error  
✅ Receipt rendering ERR_BLOCKED_BY_CLIENT  
✅ Added database seed import tool  
✅ GitHub Actions CI/CD  
✅ Build verification scripts  

See `BUILD-SUMMARY.md` for complete details.
