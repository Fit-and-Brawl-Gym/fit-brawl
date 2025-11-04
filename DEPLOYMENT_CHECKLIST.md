# Deployment Checklist - Fit & Brawl

## ✅ Pre-Deployment Checklist

### 1. Code Fixes (Already Done)
- ✅ Fixed routing in `index.php` for all pages
- ✅ Fixed login.php with proper error handling and absolute paths
- ✅ All includes now use `__DIR__` for reliable path resolution

### 2. Verify Configuration Files

#### Check `app.yaml`
- ✅ Runtime: `php81`
- ✅ Environment variables configured
- ✅ Cloud SQL connection string correct
- ✅ Handlers configured correctly
- ✅ Health check path set

#### Check `.gcloudignore`
- ✅ `.env` file excluded
- ✅ `node_modules/` excluded
- ✅ `uploads/` excluded (if using Cloud Storage)
- ✅ `.git/` excluded

### 3. Google Cloud Setup

#### Prerequisites
- [ ] Google Cloud account created
- [ ] Google Cloud SDK installed (`gcloud` CLI)
- [ ] Billing account linked (required even for free tier)
- [ ] Project created: `fit-and-brawl-gym`
- [ ] Authenticated: `gcloud auth login`

#### Required APIs Enabled
```bash
gcloud services enable appengine.googleapis.com
gcloud services enable run.googleapis.com
gcloud services enable sqladmin.googleapis.com
gcloud services enable storage.googleapis.com
gcloud services enable containerregistry.googleapis.com
gcloud services enable cloudbuild.googleapis.com
```

#### Cloud SQL Instance
- [ ] MySQL instance created: `fit-brawl-db`
- [ ] Database created: `fit_and_brawl_gym`
- [ ] Schema imported from `docs/database/schema.sql`
- [ ] Connection string in `app.yaml` matches instance

#### Cloud Storage Bucket
- [ ] Bucket created: `fit-brawl-uploads`
- [ ] IAM permissions set (if needed)

---

## 🚀 Deployment Steps

### Step 1: Final Code Review
```bash
# Navigate to project directory
cd C:\xampp\htdocs\fit-brawl

# Verify all files are present
dir app.yaml
dir index.php
dir health.php
```

### Step 2: Set Google Cloud Project
```bash
gcloud config set project fit-and-brawl-gym
```

### Step 3: Deploy to App Engine

#### Option A: Using Windows Batch Script
```bash
deploy.bat
```

#### Option B: Manual Deployment
```bash
gcloud app deploy app.yaml --quiet
```

**Expected Output:**
```
Services to deploy:
descriptor:      [C:\xampp\htdocs\fit-brawl\app.yaml]
source:          [C:\xampp\htdocs\fit-brawl]
target project:  [fit-and-brawl-gym]
target service:  [default]
target version:  [YYYYMMDDtHHMMSS]
target url:      [https://fit-and-brawl-gym.appspot.com]
```

### Step 4: Verify Deployment

#### Check Health Endpoint
```bash
curl https://fit-and-brawl-gym.appspot.com/health.php
# Should return: OK
```

#### Test Homepage
Open browser: `https://fit-and-brawl-gym.appspot.com/`

#### Test Login Page
Open browser: `https://fit-and-brawl-gym.appspot.com/login`

#### Test Other Pages
- `/sign-up`
- `/membership`
- `/equipment`
- `/products`
- `/contact`
- `/feedback`

### Step 5: Deploy Receipt Renderer (Cloud Run) - Optional

If you need the receipt renderer service:

```bash
# Deploy Cloud Run service
gcloud builds submit --config=cloudbuild.yaml

# Get the Cloud Run URL
gcloud run services describe receipt-renderer \
    --region=asia-southeast1 \
    --format='value(status.url)'

# Update app.yaml with the Cloud Run URL
# RECEIPT_RENDERER_URL: 'https://receipt-renderer-XXXXXX.asia-southeast1.run.app'
```

### Step 6: Test Database Connection

Create a test file to verify database connection (temporary):
```php
<?php
require_once __DIR__ . '/includes/db_connect.php';
if ($conn && $conn->ping()) {
    echo "Database connection: OK";
} else {
    echo "Database connection: FAILED";
}
?>
```

---

## 🔍 Post-Deployment Verification

### Functionality Tests

1. **Homepage**
   - [ ] Loads correctly
   - [ ] Navigation links work
   - [ ] Images load properly

2. **Login**
   - [ ] Login page loads
   - [ ] Can submit login form
   - [ ] Successful login redirects correctly
   - [ ] Error messages display properly

3. **Sign Up**
   - [ ] Sign up page loads
   - [ ] Form validation works
   - [ ] Can create new account

4. **Member Pages** (Requires login)
   - [ ] Dashboard loads
   - [ ] Profile page works
   - [ ] Reservations page works

5. **Admin Pages** (Requires admin login)
   - [ ] Admin dashboard loads
   - [ ] Admin features work

6. **Static Files**
   - [ ] CSS files load
   - [ ] JavaScript files load
   - [ ] Images display

### Performance Checks

- [ ] Page load times acceptable (< 3 seconds)
- [ ] No 500 errors in logs
- [ ] No 404 errors for valid pages
- [ ] Database queries execute properly

---

## 🐛 Troubleshooting

### Issue: 500 Internal Server Error

**Check Logs:**
```bash
gcloud app logs tail -s default
```

**Common Causes:**
1. Database connection failure
   - Verify Cloud SQL instance is running
   - Check connection string in `app.yaml`
   - Verify database credentials

2. Missing environment variables
   - Check `app.yaml` env_variables section
   - Verify all required variables are set

3. PHP syntax errors
   - Check logs for specific error messages
   - Test locally first

### Issue: 404 Not Found

**Solutions:**
1. Verify routing in `index.php` is correct
2. Check file paths use `__DIR__` for includes
3. Verify `app.yaml` handlers are configured

### Issue: Database Connection Failed

**Check:**
1. Cloud SQL instance is running
2. Connection string format: `/cloudsql/PROJECT:REGION:INSTANCE`
3. Database credentials are correct
4. App Engine has Cloud SQL connection permissions

### Issue: Static Files Not Loading

**Check:**
1. `app.yaml` handlers for static files
2. File paths in HTML/CSS
3. Cloud Storage bucket permissions (if using)

---

## 📊 Monitoring

### View Logs
```bash
# Real-time logs
gcloud app logs tail -s default

# Recent logs
gcloud app logs read -s default --limit=50
```

### Check Status
```bash
# App Engine status
gcloud app describe

# Instance status
gcloud app instances list
```

### Monitor Costs
- Visit: https://console.cloud.google.com/billing
- Set up budget alerts
- Monitor daily usage

---

## 🔄 Update Deployment

### Update Code
1. Make changes locally
2. Test locally
3. Deploy:
   ```bash
   gcloud app deploy app.yaml --quiet
   ```

### Rollback (if needed)
```bash
# List versions
gcloud app versions list

# Rollback to previous version
gcloud app versions migrate PREVIOUS_VERSION
```

---

## 📝 Important Notes

1. **Free Tier Limits:**
   - App Engine: 28 instance-hours/day
   - Cloud SQL: 1 f1-micro instance
   - Cloud Storage: 5 GB
   - Monitor usage to stay within limits

2. **Environment Variables:**
   - Sensitive data (passwords) should be in `app.yaml`
   - Never commit `.env` file
   - Use Google Secret Manager for production (optional)

3. **Database:**
   - Regular backups recommended
   - Monitor connection limits
   - Optimize queries for performance

4. **Security:**
   - HTTPS is automatic on App Engine
   - Keep dependencies updated
   - Regular security audits

---

## ✅ Deployment Complete When:

- [ ] All pages load without 404 errors
- [ ] Login functionality works
- [ ] Database connection successful
- [ ] Static files load correctly
- [ ] No 500 errors in logs
- [ ] Application is accessible publicly
- [ ] All critical features tested

---

## 🆘 Need Help?

1. **Check Logs:**
   ```bash
   gcloud app logs tail -s default
   ```

2. **Google Cloud Console:**
   - App Engine: https://console.cloud.google.com/appengine
   - Cloud SQL: https://console.cloud.google.com/sql
   - Logs: https://console.cloud.google.com/logs

3. **Documentation:**
   - App Engine: https://cloud.google.com/appengine/docs
   - Cloud SQL: https://cloud.google.com/sql/docs

---

**Last Updated:** After routing and login fixes
**Status:** Ready for Deployment ✅

