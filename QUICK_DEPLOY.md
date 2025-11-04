# Quick Deployment Guide - Fit & Brawl

## 🚀 Fast Deployment (Windows)

### Prerequisites
1. ✅ Google Cloud SDK installed
2. ✅ Authenticated: `gcloud auth login`
3. ✅ Project created: `fit-and-brawl-gym`
4. ✅ Billing enabled

---

## Step 1: Verify Setup

```bash
# Check gcloud is installed
gcloud --version

# Set project
gcloud config set project fit-and-brawl-gym

# Verify authentication
gcloud auth list
```

---

## Step 2: Enable Required APIs

```bash
gcloud services enable appengine.googleapis.com
gcloud services enable sqladmin.googleapis.com
gcloud services enable storage.googleapis.com
```

---

## Step 3: Deploy Application

### Option A: Using Batch Script (Recommended)
```bash
deploy.bat
```

### Option B: Manual Deployment
```bash
gcloud app deploy app.yaml --quiet
```

**Expected time:** 5-10 minutes

---

## Step 4: Verify Deployment

1. **Check Health Endpoint:**
   ```
   https://fit-and-brawl-gym.appspot.com/health.php
   ```
   Should return: `OK`

2. **Test Homepage:**
   ```
   https://fit-and-brawl-gym.appspot.com/
   ```

3. **Test Login:**
   ```
   https://fit-and-brawl-gym.appspot.com/login
   ```

---

## ✅ Deployment Complete!

Your application is live at:
- **URL:** https://fit-and-brawl-gym.appspot.com
- **Health Check:** https://fit-and-brawl-gym.appspot.com/health.php

---

## 🔧 Quick Troubleshooting

### Issue: Deployment fails
**Solution:** Check logs
```bash
gcloud app logs tail -s default
```

### Issue: 500 Internal Server Error
**Solution:**
1. Check database connection in `app.yaml`
2. Verify Cloud SQL instance is running
3. Check logs for specific errors

### Issue: 404 Not Found
**Solution:**
1. Verify routing in `index.php`
2. Check file paths use `__DIR__`

---

## 📊 View Logs

```bash
# Real-time logs
gcloud app logs tail -s default

# Recent logs (last 50)
gcloud app logs read -s default --limit=50
```

---

## 🔄 Update Deployment

After making code changes:

```bash
gcloud app deploy app.yaml --quiet
```

---

## 📝 Important Notes

1. **Database:** Ensure Cloud SQL instance is running
2. **Environment Variables:** Check `app.yaml` for correct values
3. **Static Files:** Should load automatically via handlers
4. **Routing:** All pages should work via `index.php` router

---

## 🆘 Need Help?

- **Logs:** `gcloud app logs tail -s default`
- **Console:** https://console.cloud.google.com/appengine
- **Status:** `gcloud app describe`

---

**Status:** Ready to deploy ✅

