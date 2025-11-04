# 🎯 DEPLOYMENT QUICK REFERENCE CARD

Keep this handy during deployment!

---

## ⚡ Phase 1: Install Google Cloud SDK

**Windows Download**: https://cloud.google.com/sdk/docs/install

**After installation, restart your terminal!**

---

## ⚡ Phase 2: Quick Commands Sequence

```bash
# 1. Login
gcloud auth login

# 2. Create & setup project
gcloud projects create fit-and-brawl-gym --name="Fit and Brawl Gym"
gcloud config set project fit-and-brawl-gym

# 3. Enable billing (browser)
# Open: https://console.cloud.google.com/billing
# Link billing to fit-and-brawl-gym

# 4. Enable APIs
gcloud services enable appengine.googleapis.com run.googleapis.com sqladmin.googleapis.com storage.googleapis.com containerregistry.googleapis.com cloudbuild.googleapis.com

# 5. Create App Engine (Southeast Asia region)
gcloud app create --region=asia-southeast1

# 6. Create Cloud SQL (CHANGE PASSWORD!) - Southeast Asia
gcloud sql instances create fit-brawl-db --database-version=MYSQL_8_0 --tier=db-f1-micro --region=asia-southeast1 --root-password=YOUR_SECURE_PASSWORD --backup

# 7. Create database
gcloud sql databases create fit_and_brawl_gym --instance=fit-brawl-db

# 8. Import schema (using gcloud storage - no permission issues!)
gcloud storage buckets create gs://fit-brawl-temp --location=asia-southeast1
gcloud storage cp docs/database/schema.sql gs://fit-brawl-temp/

# Grant Cloud SQL permission to read from bucket
SERVICE_ACCOUNT=$(gcloud sql instances describe fit-brawl-db --format="value(serviceAccountEmailAddress)")
gcloud storage buckets add-iam-policy-binding gs://fit-brawl-temp --member="serviceAccount:$SERVICE_ACCOUNT" --role="roles/storage.objectViewer"

# Now import
gcloud sql import sql fit-brawl-db gs://fit-brawl-temp/schema.sql --database=fit_and_brawl_gym

# 9. Deploy Cloud Run (Southeast Asia)
cd server-renderer && npm install && cd ..
gcloud builds submit --config=cloudbuild.yaml

# 10. Get Cloud Run URL (save this!)
gcloud run services describe receipt-renderer --region=asia-southeast1 --format='value(status.url)'

# 11. Update app.yaml with:
#     - DB_PASS
#     - RECEIPT_RENDERER_URL
#     - EMAIL_PASS (Gmail App Password)

# 12. Install PHP deps
composer install

# 13. Deploy app
gcloud app deploy

# 14. Create storage (using gcloud storage)
gcloud storage buckets create gs://fit-brawl-uploads --location=asia-southeast1
gcloud storage buckets add-iam-policy-binding gs://fit-brawl-uploads --member=allUsers --role=roles/storage.objectViewer

# 15. Open app
gcloud app browse
```

---

## 📋 Information You Need to Prepare

### Before Starting:
- [ ] Google account email
- [ ] Credit card for billing (won't be charged in free tier)

### During Deployment:
- [ ] **Database Password**: (strong password, save it!)
- [ ] **Gmail App Password**: Get from https://myaccount.google.com/apppasswords

### After Deployment:
- [ ] **Cloud Run URL**: (auto-generated, copy from terminal)
- [ ] **App URL**: https://fit-and-brawl-gym.appspot.com

---

## 🔑 app.yaml Values to Update

```yaml
DB_PASS: 'YOUR_DATABASE_PASSWORD'  # From step 6
APP_URL: 'https://fit-and-brawl-gym.appspot.com'  # Fixed value
RECEIPT_RENDERER_URL: 'https://receipt-renderer-XXXXX.a.run.app'  # From step 10
EMAIL_PASS: 'xxxx xxxx xxxx xxxx'  # Gmail App Password (16 chars)
```

---

## ⏱️ Time Estimates

| Phase | Time |
|-------|------|
| Install GCloud SDK | 5-10 min |
| Login & Setup | 10 min |
| Cloud SQL | 10-15 min |
| Gmail Setup | 5 min |
| Cloud Run | 8-10 min |
| Configuration | 5 min |
| App Deploy | 8-10 min |
| Storage & Test | 10 min |
| **TOTAL** | **60-80 min** |

---

## 🆘 Quick Troubleshooting

### Problem: "gcloud: command not found"
**Solution**: Restart terminal after installing Google Cloud SDK

### Problem: "Billing is not enabled"
**Solution**: Visit https://console.cloud.google.com/billing and link billing account

### Problem: "Permission denied"
**Solution**: Run `gcloud auth login` again

### Problem: "Email sending fails"
**Solution**: Use Gmail **App Password**, not regular password

### Problem: "Cloud SQL timeout"
**Solution**: Check `beta_settings` in app.yaml matches your instance name

---

## 📞 Help Resources

- **Detailed Guide**: `DEPLOYMENT_STEPS.md`
- **Troubleshooting**: `TROUBLESHOOTING.md`
- **Progress Tracker**: `DEPLOYMENT_PROGRESS.md`
- **Full Checklist**: `DEPLOYMENT_CHECKLIST.md`

---

## ✅ Success Indicators

You're done when:
- ✅ `gcloud app browse` opens your live site
- ✅ You can register a new user
- ✅ Email verification works
- ✅ Login works
- ✅ No errors in `gcloud app logs tail`

---

## 💰 Cost Reminder

**Expected Cost**: $0/month (free tier)

**Free Tier Includes**:
- 28 F1 hours/day (App Engine)
- 2M requests/month (Cloud Run)
- 1 f1-micro instance (Cloud SQL)
- 5 GB storage

**Set Budget Alert**: $10/month (safety net)

---

**Good Luck! 🚀**

Questions? Check the detailed guides or run `gcloud help`
