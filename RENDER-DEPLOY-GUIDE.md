# 🚀 Render.com Deployment Guide for Fit & Brawl Gym

Complete guide to deploy your gym management system to Render.com with free HTTPS.

---

## 📋 Prerequisites

1. GitHub account with this repository pushed
2. Render.com account (free)
3. Free MySQL database (we'll set this up)

---

## 🗄️ Step 1: Set Up Free MySQL Database

Render's free database is PostgreSQL only. Your app uses MySQL, so we'll use **TiDB Cloud** (100% MySQL compatible, generous free tier).

### Option A: TiDB Cloud (Recommended)

1. Go to [tidbcloud.com](https://tidbcloud.com)
2. Sign up with GitHub
3. Click **"Create Cluster"**
4. Select **"Serverless"** (FREE tier)
   - Cluster Name: `fitbrawl-db`
   - Region: Choose closest to Singapore
5. Click **"Create"**
6. Once created, click **"Connect"** → **"General"**
7. Note down these values:
   ```
   Host: gateway01.xxx.prod.aws.tidbcloud.com
   Port: 4000
   User: xxxxx.root
   Password: (generate one)
   Database: fit_and_brawl_gym
   ```

### Option B: Railway.app (Alternative)

1. Go to [railway.app](https://railway.app)
2. Sign up with GitHub
3. Click **"New Project"** → **"Database"** → **"MySQL"**
4. Copy the connection variables from the Variables tab

### Option C: PlanetScale (Alternative)

1. Go to [planetscale.com](https://planetscale.com)
2. Sign up and create a free database
3. Note: PlanetScale requires SSL connection

---

## 📤 Step 2: Import Your Database

### Export from XAMPP

```bash
# Open terminal in XAMPP
cd /c/xampp/mysql/bin

# Export your database
./mysqldump -u root fit_and_brawl_gym > fitbrawl_export.sql
```

Or use **phpMyAdmin**:
1. Open http://localhost/phpmyadmin
2. Select `fit_and_brawl_gym` database
3. Click **Export** → **Go**
4. Save the `.sql` file

### Import to TiDB Cloud

1. In TiDB Cloud dashboard, click **"Import"**
2. Choose **"Local File"**
3. Upload your `.sql` file
4. Wait for import to complete

**Or via command line:**
```bash
mysql -h gateway01.xxx.prod.aws.tidbcloud.com -P 4000 -u xxxxx.root -p fit_and_brawl_gym < fitbrawl_export.sql
```

---

## 🌐 Step 3: Deploy to Render

### Method 1: One-Click Deploy (Easiest)

1. Push latest changes to GitHub:
   ```bash
   cd /c/xampp/htdocs/fit-brawl
   git add .
   git commit -m "Add Render deployment config"
   git push origin main
   ```

2. Go to [Render Dashboard](https://dashboard.render.com)

3. Click **"New +"** → **"Blueprint"**

4. Connect your GitHub repository: `Fit-and-Brawl-Gym/fit-brawl`

5. Click **"Apply"**

### Method 2: Manual Setup

1. Go to [Render Dashboard](https://dashboard.render.com)

2. Click **"New +"** → **"Web Service"**

3. Connect your GitHub repo

4. Configure:
   - **Name**: `fit-brawl`
   - **Region**: Singapore
   - **Runtime**: Docker
   - **Plan**: Free

5. Click **"Create Web Service"**

---

## ⚙️ Step 4: Configure Environment Variables

After deployment starts:

1. Go to your service in Render dashboard
2. Click **"Environment"** tab
3. Add these variables:

| Key | Value | Example |
|-----|-------|---------|
| `APP_ENV` | `production` | `production` |
| `BASE_PATH` | `/` | `/` |
| `DB_HOST` | Your TiDB host | `gateway01.xxx.prod.aws.tidbcloud.com` |
| `DB_PORT` | `4000` (TiDB) or `3306` | `4000` |
| `DB_NAME` | `fit_and_brawl_gym` | `fit_and_brawl_gym` |
| `DB_USER` | Your TiDB user | `xxxxx.root` |
| `DB_PASS` | Your TiDB password | `your-secure-password` |

4. Click **"Save Changes"**

5. The service will automatically redeploy

---

## ✅ Step 5: Verify Deployment

1. Wait for deployment to complete (5-10 minutes first time)

2. Click the URL shown in Render dashboard:
   ```
   https://fit-brawl.onrender.com
   ```

3. Test the application:
   - Login page loads ✓
   - Can login with existing user ✓
   - Database operations work ✓

---

## 🔧 Troubleshooting

### Build Fails

Check the **Logs** tab in Render for errors.

Common issues:
- **Dockerfile error**: Make sure Dockerfile exists in root
- **Memory limit**: Free tier has limited resources

### Database Connection Error

1. Verify environment variables are set correctly
2. Check if TiDB cluster is running
3. For TiDB, ensure you're using port `4000`

### Slow First Load

Normal! Free tier spins down after 15 minutes of inactivity. First request takes 30-60 seconds to "wake up".

### PHP Errors

Check logs:
1. Render Dashboard → Your Service → **Logs**
2. Look for PHP error messages

---

## 📱 Sharing Your Deployment

Your app is now live at:
```
https://fit-brawl.onrender.com
```

Share this URL with:
- Capstone defense panel ✓
- Team members ✓
- Testers ✓

---

## 💡 Tips for Capstone Defense

1. **Wake up the app** 5 minutes before presentation
   - Just visit the URL to start the container

2. **Have backup plan**
   - Keep local XAMPP running as backup
   - Have screenshots ready

3. **Test everything**
   - All login types (admin, trainer, member)
   - Key features you'll demo

---

## 🔄 Updating Your Deployment

Any push to `main` branch auto-deploys:

```bash
git add .
git commit -m "Update feature"
git push origin main
```

Render will automatically rebuild and deploy (3-5 minutes).

---

## 💰 Cost Summary

| Service | Cost | Limit |
|---------|------|-------|
| Render Web Service | FREE | 750 hrs/month |
| TiDB Cloud Serverless | FREE | 5GB storage, 50M requests |
| **Total** | **$0** | Perfect for 1-month demo |

---

## 🛡️ Security Notes

1. Never commit `.env` files with real credentials
2. Use Render's environment variables for secrets
3. The HTTPS certificate is automatic

---

## ⏰ Free Tier Limitations

### Render Free Tier
- Spins down after 15 min inactivity
- 750 free hours/month
- Limited to 512MB RAM

### TiDB Free Tier
- 5GB storage
- 50 million Request Units/month
- Auto-pauses after 7 days of no activity

**For a 1-month capstone demo, these limits are more than enough!**

---

*Last updated: November 2025*
