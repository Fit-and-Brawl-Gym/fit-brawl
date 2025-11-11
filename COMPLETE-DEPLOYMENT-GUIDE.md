# 🎯 Complete System Deployment Guide - Localhost & Production

**Date:** November 11, 2025
**Status:** Complete - Ready for both environments

---

## 📊 System Overview

### Environment Detection
The system automatically detects the environment based on:
1. **`APP_ENV`** in `.env` file (if set)
2. **Auto-detection** from server name:
   - `localhost`, `127.0.0.1`, `*.local` → Development
   - AWS IP, domain names → Production

### Path Configuration

| Path Constant | Localhost Value | Production Value |
|---------------|-----------------|------------------|
| `BASE_PATH` | `/fit-brawl/` | `/` |
| `PUBLIC_PATH` | `/fit-brawl/public` | `` (empty) |
| `IMAGES_PATH` | `/fit-brawl/images` | `/images` |
| `UPLOADS_PATH` | `/fit-brawl/uploads` | `/uploads` |

---

## 🖥️ LOCALHOST SETUP (XAMPP)

### Prerequisites
- XAMPP installed
- MySQL running
- Git repository cloned to `C:\xampp\htdocs\fit-brawl`

### Step 1: Database Setup

```bash
# Start XAMPP MySQL
# Open http://localhost/phpmyadmin

# Create database
CREATE DATABASE fit_and_brawl_gym;

# Import schema (if you have a dump file)
# Or run your migration scripts
```

### Step 2: Environment Configuration

```bash
# .env file is already configured for localhost
# Verify these settings in .env:

APP_ENV=development  # Optional - will auto-detect
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=fit_and_brawl_gym
DB_PORT=3306
```

### Step 3: Install Dependencies

```bash
# Open terminal in C:\xampp\htdocs\fit-brawl

# Install PHP dependencies
php composer.phar install

# Install Node dependencies for server-renderer
cd server-renderer
npm install
cd ..
```

### Step 4: Test Localhost

```bash
# Open browser
http://localhost/fit-brawl/public/

# Test configuration
http://localhost/fit-brawl/public/test-config.php

# Test admin
http://localhost/fit-brawl/public/php/admin/admin.php

# Test trainer
http://localhost/fit-brawl/public/php/trainer/index.php
```

### Expected Results ✅

- Homepage loads with full CSS
- Admin login works
- Trainer login works
- Member login works
- All JavaScript files load (no 404s)
- Images display correctly

---

## ☁️ PRODUCTION DEPLOYMENT (AWS EC2 + Docker)

### Architecture
```
Internet → Port 80 → Docker (fitbrawl_web) → Apache → PHP
                                            → MySQL (fitbrawl_db)
          Port 3000 → Cloudflare Tunnel → Public HTTPS URL
```

### Prerequisites
- EC2 instance running
- Docker and Docker Compose installed
- SSH access configured
- Security groups: Port 22 (SSH) and Port 80 (HTTP) open

---

## 🚀 MANUAL DEPLOYMENT PROCESS

Since GitHub Actions is currently not working, use manual deployment:

### Step 1: Connect to EC2

```bash
# From your local machine
ssh -i ~/.ssh/github_actions_deploy ec2-user@54.227.103.23
```

**If SSH fails:** Use AWS Console → EC2 → Instance Connect

### Step 2: Pull Latest Code

```bash
# On EC2 server
cd /home/ec2-user/fit-brawl

# Pull latest changes
git pull origin main

# Expected output: "Already up to date" or list of changed files
```

### Step 3: Rebuild and Restart Docker Containers

```bash
# Option A: Quick restart (for PHP-only changes)
docker restart fitbrawl_web

# Option B: Full rebuild (for dependency changes)
docker compose down
docker compose up -d --build

# Verify containers are running
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'
```

**Expected output:**
```
NAMES          STATUS          PORTS
fitbrawl_web   Up X seconds    0.0.0.0:80->80/tcp
fitbrawl_db    Up X seconds    0.0.0.0:3306->3306/tcp
```

### Step 4: Restart Cloudflare Tunnel (if needed)

```bash
# Stop old tunnel
sudo pkill cloudflared

# Start new tunnel pointing to port 80
nohup cloudflared tunnel --url http://localhost:80 > /tmp/cloudflared.log 2>&1 &

# Wait for connection
sleep 5

# Get new URL
grep -oP 'https://[a-z0-9-]+\.trycloudflare\.com' /tmp/cloudflared.log | tail -1
```

**Note:** Cloudflare URL changes each time you restart the tunnel!

---

## ✅ VERIFICATION CHECKLIST

### Test on Localhost

- [ ] Homepage: http://localhost/fit-brawl/public/
- [ ] Config test: http://localhost/fit-brawl/public/test-config.php
- [ ] Admin panel: http://localhost/fit-brawl/public/php/admin/admin.php
- [ ] Trainer panel: http://localhost/fit-brawl/public/php/trainer/index.php
- [ ] Member pages work
- [ ] CSS loads (no 404s)
- [ ] JavaScript loads (no 404s)
- [ ] Images display
- [ ] Forms submit correctly

### Test on Production (Direct IP)

- [ ] Homepage: http://54.227.103.23/
- [ ] Config test: http://54.227.103.23/test-config.php
- [ ] Admin panel: http://54.227.103.23/php/admin/admin.php
- [ ] Trainer panel: http://54.227.103.23/php/trainer/index.php
- [ ] Member pages work
- [ ] CSS loads (no 404s in console)
- [ ] JavaScript loads (check sidebar, forms)
- [ ] Images display
- [ ] Database connection works

### Test on Production (Cloudflare)

- [ ] Get current URL: `grep -oP 'https://[a-z0-9-]+\.trycloudflare\.com' /tmp/cloudflared.log | tail -1`
- [ ] Homepage loads
- [ ] Admin panel works
- [ ] All features functional

---

## 🔧 TROUBLESHOOTING

### Localhost Issues

**Problem:** CSS/JS not loading (404 errors)

**Solution:**
```bash
# Check if PUBLIC_PATH is correct
http://localhost/fit-brawl/public/test-config.php

# Should show:
# PUBLIC_PATH: /fit-brawl/public
# ENVIRONMENT: development
```

**Problem:** Database connection fails

**Solution:**
```bash
# Check MySQL is running in XAMPP
# Verify .env has correct credentials
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=fit_and_brawl_gym
```

### Production Issues

**Problem:** Can't connect to EC2

**Solution:**
```bash
# Use AWS Console → EC2 → Instance Connect
# Or check Security Group has SSH (port 22) open
```

**Problem:** Website shows 404 or blank page

**Solution:**
```bash
# Check Docker containers are running
docker ps

# Check logs
docker logs fitbrawl_web --tail 50

# Restart containers
docker restart fitbrawl_web
```

**Problem:** CSS/JS not loading (404 errors)

**Solution:**
```bash
# Check PUBLIC_PATH in production
curl http://localhost/test-config.php

# Should show:
# PUBLIC_PATH: (empty)
# ENVIRONMENT: production

# Verify files exist
docker exec fitbrawl_web ls -la /var/www/html/public/php/admin/js/
```

---

## 📝 QUICK DEPLOYMENT COMMANDS

### Push Changes from Local

```bash
# On your local machine
cd C:\xampp\htdocs\fit-brawl

# Add and commit changes
git add -A
git commit -m "Your commit message"
git push origin main
```

### Deploy to Production

```bash
# SSH to EC2
ssh -i ~/.ssh/github_actions_deploy ec2-user@54.227.103.23

# Run deployment
cd /home/ec2-user/fit-brawl && \
git pull origin main && \
docker restart fitbrawl_web && \
echo "Deployment complete! Testing in 5 seconds..." && \
sleep 5 && \
curl -I http://localhost:80/ | head -10
```

### One-Line Full Rebuild (if needed)

```bash
cd /home/ec2-user/fit-brawl && \
git pull origin main && \
docker compose down && \
docker compose up -d --build && \
sleep 10 && \
docker ps
```

---

## 🎯 CURRENT STATUS

### What's Working ✅

- ✅ **Environment auto-detection:** Works in both localhost and production
- ✅ **Path configuration:** PUBLIC_PATH correctly set for both environments
- ✅ **Admin pages:** All 14 admin PHP files fixed with PUBLIC_PATH
- ✅ **Trainer pages:** Using trainer_header.php with PUBLIC_PATH
- ✅ **Member pages:** Using header.php with PUBLIC_PATH
- ✅ **Docker deployment:** Containers running with port 80 exposed
- ✅ **Cloudflare tunnel:** Working HTTPS access via tunnel

### Recent Fixes 🔧

1. Fixed admin JavaScript paths (14 files)
2. Fixed transaction.js path
3. Fixed config.php environment detection
4. Fixed Docker port mapping (8080 → 80)
5. Fixed Cloudflare tunnel configuration

---

## 🎉 SUCCESS INDICATORS

You'll know everything is working when:

### Localhost
- ✅ http://localhost/fit-brawl/public/ loads homepage
- ✅ Browser console shows 0 errors
- ✅ Admin panel fully functional
- ✅ All CSS and JS files load

### Production
- ✅ http://54.227.103.23/ loads homepage
- ✅ https://[random].trycloudflare.com/ loads via tunnel
- ✅ Browser console shows 0 errors
- ✅ Admin panel sidebar and JS work
- ✅ All features functional

---

## 📚 IMPORTANT FILES

| File | Purpose |
|------|---------|
| `includes/config.php` | Environment detection and path configuration |
| `.env` | Localhost environment variables |
| `.env.production` | Production environment template |
| `docker-compose.yml` | Docker container configuration |
| `Dockerfile` | Web container build instructions |
| `includes/header.php` | Member pages header with PUBLIC_PATH |
| `includes/trainer_header.php` | Trainer pages header with PUBLIC_PATH |
| `public/php/admin/*.php` | Admin pages (all use PUBLIC_PATH for JS) |

---

**🚀 System is now fully configured for both localhost and production deployment!**
