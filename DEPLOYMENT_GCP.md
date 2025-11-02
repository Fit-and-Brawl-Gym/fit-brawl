# Google Cloud Platform Deployment Guide

## Overview

Deploy your **Fit & Brawl** application on Google Cloud Platform using the **FREE tier** with:

- ✅ **App Engine** (Standard) for PHP application
- ✅ **Cloud Run** for Node.js receipt renderer
- ✅ **Cloud SQL** (MySQL) for database
- ✅ **Cloud Storage** for file uploads

All services have free tiers suitable for development/small-scale production.

---

## Prerequisites

1. Google Cloud account (free tier available)
2. Google Cloud SDK installed locally
3. $300 free credit (new accounts only, lasts 90 days)
4. Credit card required (but won't be charged if you stay within free tier)

---

## Free Tier Limits (2024)

### App Engine (Standard)
- **28 instance-hours per day** (F-class)
- **9 instance-hours per day** (B-class)
- **1 GB egress per day**

### Cloud Run
- **2 million requests per month**
- **360,000 GB-seconds memory per month**
- **180,000 vCPU-seconds per month**
- **80 concurrent requests per instance**

### Cloud SQL (MySQL)
- **f1-micro instance** with shared CPU
- **0.6 GB RAM, 10 GB SSD** storage
- **First instance free** (per project)

### Cloud Storage
- **5 GB standard storage**
- **1 GB download per day**

---

## Part 1: Setup Google Cloud Project

### 1.1 Create Project
```bash
# Login to Google Cloud
gcloud auth login

# Create new project
gcloud projects create fit-and-brawl-gym --name="Fit & Brawl Gym"

# Set as default project
gcloud config set project fit-and-brawl-gym

# Enable billing (required even for free tier)
# Visit: https://console.cloud.google.com/billing
# Link your billing account to the project
```

### 1.2 Enable Required APIs
```bash
# Enable App Engine API
gcloud services enable appengine.googleapis.com

# Enable Cloud Run API
gcloud services enable run.googleapis.com

# Enable Cloud SQL API
gcloud services enable sqladmin.googleapis.com

# Enable Cloud Storage API
gcloud services enable storage-component.googleapis.com

# Enable Container Registry API (for Cloud Run)
gcloud services enable containerregistry.googleapis.com
```

### 1.3 Create App Engine Application
```bash
# Initialize App Engine (select region)
gcloud app create --region=us-central
```

---

## Part 2: Deploy Database (Cloud SQL)

### 2.1 Create MySQL Instance
```bash
# Create Cloud SQL instance
gcloud sql instances create fit-brawl-db \
    --database-version=MYSQL_8_0 \
    --tier=db-f1-micro \
    --region=us-central1 \
    --root-password=YOUR_SECURE_PASSWORD \
    --backup \
    --maintenance-window-day=SUN \
    --maintenance-window-hour=3
```

### 2.2 Import Database Schema
```bash
# Create database
gcloud sql databases create fit_and_brawl_gym \
    --instance=fit-brawl-db

# Import schema
gcloud sql import sql fit-brawl-db \
    gs://your-bucket-name/schema.sql \
    --database=fit_and_brawl_gym

# Note: Upload schema.sql to Cloud Storage first
```

**Alternative: Direct Import**
```bash
# Connect to instance
gcloud sql connect fit-brawl-db --user=root

# Then run SQL manually
mysql> source path/to/docs/database/schema.sql;
```

---

## Part 3: Deploy PHP Application (App Engine)

### 3.1 Create `app.yaml`
Create `app.yaml` in project root:

```yaml
runtime: php81

env_variables:
  DB_HOST: '/cloudsql/fit-and-brawl-gym:us-central1:fit-brawl-db'
  DB_NAME: 'fit_and_brawl_gym'
  DB_USER: 'root'
  DB_PASS: 'YOUR_SECURE_PASSWORD'

  # App URL (update after deployment)
  APP_URL: 'https://YOUR-PROJECT-ID.appspot.com'

  # Email settings (if using Gmail)
  SMTP_HOST: 'smtp.gmail.com'
  SMTP_PORT: '587'
  SMTP_USER: 'your-email@gmail.com'
  SMTP_PASS: 'your-app-password'

handlers:
  # Serve static files
  - url: /public
    static_dir: public

  # PHP handlers
  - url: /.*
    script: auto

libraries:
  - name: mysql
    version: latest
```

### 3.2 Update `includes/config.php`
```php
<?php
// Auto-detect environment
if (isset($_SERVER['GAE_ENV']) || isset($_SERVER['GAE_VERSION'])) {
    // Google Cloud environment
    $db_config = [
        'host' => $_SERVER['DB_HOST'] ?? '/cloudsql/fit-and-brawl-gym:us-central1:fit-brawl-db',
        'dbname' => $_SERVER['DB_NAME'] ?? 'fit_and_brawl_gym',
        'username' => $_SERVER['DB_USER'] ?? 'root',
        'password' => $_SERVER['DB_PASS'] ?? '',
    ];
} else {
    // Local development
    $db_config = [
        'host' => 'localhost',
        'dbname' => 'fit_and_brawl_gym',
        'username' => 'root',
        'password' => '',
    ];
}
?>
```

### 3.3 Create `.gcloudignore`
```
.gcloudignore
node_modules/
vendor/
.env
server-renderer/node_modules/
server-renderer/.cache/
.git/
.gitignore
DEPLOYMENT*.md
README.md
```

### 3.4 Deploy Application
```bash
# Deploy to App Engine
gcloud app deploy

# View deployed app
gcloud app browse
```

---

## Part 4: Deploy Receipt Renderer (Cloud Run)

### 4.1 Create `Dockerfile` in `server-renderer/`
```dockerfile
FROM node:18-alpine

WORKDIR /app

# Install Chrome dependencies
RUN apk add --no-cache \
    chromium \
    nss \
    freetype \
    harfbuzz \
    ca-certificates \
    ttf-freefont

# Set Puppeteer to use system Chrome
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci --only=production

# Copy app files
COPY . .

# Expose port
EXPOSE 8080

# Run renderer
CMD ["node", "render-wrapper.js"]
```

### 4.2 Create `cloudbuild.yaml`
```yaml
steps:
  # Build Docker image
  - name: 'gcr.io/cloud-builders/docker'
    args: ['build', '-t', 'gcr.io/$PROJECT_ID/receipt-renderer', './server-renderer']

  # Push to Container Registry
  - name: 'gcr.io/cloud-builders/docker'
    args: ['push', 'gcr.io/$PROJECT_ID/receipt-renderer']

  # Deploy to Cloud Run
  - name: 'gcr.io/cloud-builders/gcloud'
    args:
      - 'run'
      - 'deploy'
      - 'receipt-renderer'
      - '--image'
      - 'gcr.io/$PROJECT_ID/receipt-renderer'
      - '--region'
      - 'us-central1'
      - '--platform'
      - 'managed'
      - '--memory'
      - '512Mi'
      - '--timeout'
      - '300'
      - '--allow-unauthenticated'

images:
  - 'gcr.io/$PROJECT_ID/receipt-renderer'
```

### 4.3 Deploy to Cloud Run
```bash
# Submit build
gcloud builds submit --config=cloudbuild.yaml

# Get Cloud Run URL
gcloud run services describe receipt-renderer --region=us-central1 --format='value(status.url)'

# Output: https://receipt-renderer-XXXXXX-uc.a.run.app
```

### 4.4 Update `receipt_render.php` to Use Cloud Run
```php
<?php
// Use Cloud Run service instead of local Node.js
$cloudRunUrl = 'https://receipt-renderer-XXXXXX-uc.a.run.app';

$receiptUrl = /* ... build URL ... */;

// Call Cloud Run service via HTTP
$ch = curl_init($cloudRunUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'url' => $receiptUrl,
    'format' => $format
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$result = curl_exec($ch);
curl_close($ch);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="receipt.pdf"');
echo $result;
exit;
```

---

## Part 5: Setup Cloud Storage for Uploads

### 5.1 Create Storage Bucket
```bash
# Create bucket
gsutil mb gs://fit-brawl-uploads

# Set bucket permissions
gsutil iam ch allUsers:objectViewer gs://fit-brawl-uploads
```

### 5.2 Update Upload Handler
Modify your upload endpoints to use Cloud Storage instead of local `uploads/` directory:

```php
use Google\Cloud\Storage\StorageClient;

$storage = new StorageClient([
    'projectId' => 'fit-and-brawl-gym'
]);
$bucket = $storage->bucket('fit-brawl-uploads');

// Upload file
$object = $bucket->upload(
    fopen($_FILES['file']['tmp_name'], 'r'),
    ['name' => 'avatars/' . $filename]
);

// Get public URL
$url = $object->publicUrl();
```

---

## Part 6: Email Configuration (Optional)

Google Cloud doesn't provide free SMTP. Options:

### Option A: Use Gmail App Password
1. Enable 2FA on Gmail
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Use in `app.yaml` env_variables

### Option B: Use SendGrid (Free Tier)
- 100 emails/day free
- Integrate via API

---

## Cost Estimation (Free Tier)

| Service | Free Tier | Your Usage | Cost |
|---------|-----------|------------|------|
| App Engine | 28 hrs/day | ~10 hrs/day | $0 |
| Cloud Run | 2M requests | ~1000/month | $0 |
| Cloud SQL | 1 instance | 1 instance | $0 |
| Cloud Storage | 5 GB | ~500 MB | $0 |
| Bandwidth | 1 GB/day | ~500 MB/day | $0 |
| **Total** | | | **$0/month** |

---

## Monitoring & Alerts

### Setup Budget Alert
```bash
# Create budget
gcloud billing budgets create \
    --billing-account=YOUR_BILLING_ACCOUNT \
    --display-name="Fit & Brawl Budget" \
    --budget-amount=5USD \
    --threshold-rule=percent=90 \
    --threshold-rule=percent=100
```

### View Logs
```bash
# App Engine logs
gcloud app logs tail -s default

# Cloud Run logs
gcloud run logs tail --service=receipt-renderer --region=us-central1
```

---

## Troubleshooting

### Chrome not found in Cloud Run
**Solution:** Dockerfile already configures Puppeteer to use system Chrome.

### Cloud SQL connection timeout
**Solution:** Use Unix socket path `/cloudsql/...` in `app.yaml` instead of IP.

### Upload permissions error
**Solution:** Ensure bucket IAM permissions are correct.

### App Engine cold starts slow
**Solution:** Use "always on" instances (may incur cost outside free tier).

---

## Production Checklist

- [ ] Database passwords rotated
- [ ] HTTPS enforced (automatic on GCP)
- [ ] Environment variables secured
- [ ] Backups enabled
- [ ] Monitoring alerts configured
- [ ] Domain configured (optional)
- [ ] CDN for static assets (optional)

---

## Need Help?

- Google Cloud Docs: https://cloud.google.com/docs
- App Engine Guide: https://cloud.google.com/appengine/docs/nodejs
- Cloud Run Guide: https://cloud.google.com/run/docs
- Free Tier Limits: https://cloud.google.com/free

