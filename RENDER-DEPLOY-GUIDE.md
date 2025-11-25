# 🚀 Render.com Deployment Guide for Fit & Brawl Gym

Complete guide to deploy your gym management system to Render.com with free HTTPS.

---

## 📋 Prerequisites

1. GitHub account with this repository pushed
2. Render.com account (free)
3. ✅ **Your existing AWS RDS MySQL database** (already set up!)

---

## 🗄️ Step 1: Get Your AWS Database Credentials

You already have a MySQL database on AWS free tier! Just get these connection details from your AWS RDS console:

1. Go to [AWS RDS Console](https://console.aws.amazon.com/rds/)
2. Click on your database instance
3. Note down these values:
   ```
   Endpoint (Host): fitbrawl-db.carwg0m6glw6.us-east-1.rds.amazonaws.com
   Port: 3306
   Database name: fitbrawl-db
   Master username: Mikell_Admin
   Password: Mikedefender#12
   ```

### ⚠️ Important: Make Sure AWS Allows External Connections

1. In RDS Console → Your DB → **Security Group**
2. Edit **Inbound Rules**
3. Add rule:
   - Type: **MySQL/Aurora**
   - Port: **3306**
   - Source: **0.0.0.0/0** (allows all IPs - needed for Render)
4. Save

> Note: For production, you'd want to whitelist specific IPs, but for a 1-month demo this is fine.

---

## 🌐 Step 2: Deploy to Render

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

## ⚙️ Step 3: Configure Environment Variables

After deployment starts:

1. Go to your service in Render dashboard
2. Click **"Environment"** tab
3. Add these variables with your **AWS RDS credentials**:

| Key | Value | Example |
|-----|-------|---------|
| `APP_ENV` | `production` | `production` |
| `BASE_PATH` | `/` | `/` |
| `DB_HOST` | Your AWS RDS endpoint | `fitbrawl.xxxxxxxx.ap-southeast-1.rds.amazonaws.com` |
| `DB_PORT` | `3306` | `3306` |
| `DB_NAME` | `fit_and_brawl_gym` | `fit_and_brawl_gym` |
| `DB_USER` | Your RDS username | `admin` |
| `DB_PASS` | Your RDS password | `your-secure-password` |

4. Click **"Save Changes"**

5. The service will automatically redeploy

---

## ✅ Step 4: Verify Deployment

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

| Service | Cost | Note |
|---------|------|------|
| Render Web Service | FREE | 750 hrs/month |
| AWS RDS MySQL | FREE | You already have this! |
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

### AWS RDS Free Tier
- 750 hours/month of db.t2.micro or db.t3.micro
- 20GB storage
- Valid for 12 months from signup

**For a 1-month capstone demo, these limits are more than enough!**

---

*Last updated: November 2025*
