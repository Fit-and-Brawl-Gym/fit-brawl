# 🚀 GitHub Actions Auto-Deployment Setup Guide

Complete guide to set up automatic deployment from GitHub to your AWS server.

---

## 📋 Overview

When you push code to GitHub (main branch), GitHub Actions will automatically:
1. ✅ Pull latest code to your server
2. ✅ Install/update dependencies
3. ✅ Restart renderer service
4. ✅ Set proper permissions
5. ✅ Your website is updated! 🎉

---

## ⚡ Quick Setup (5 Steps)

### Step 1: Get Your SSH Private Key

You need to add your SSH private key to GitHub Secrets.

**On your local machine:**

```bash
# Display your PEM key content
cat "/c/Users/Mikell Razon/Downloads/Mikell.pem"
```

**Copy the ENTIRE output** including:
```
-----BEGIN RSA PRIVATE KEY-----
... (all the content)
-----END RSA PRIVATE KEY-----
```

### Step 2: Add GitHub Secrets

1. **Go to GitHub Repository Settings:**
   - Visit: https://github.com/Fit-and-Brawl-Gym/fit-brawl/settings/secrets/actions

2. **Click "New repository secret"**

3. **Add these secrets:**

| Secret Name | Value | Example |
|-------------|-------|---------|
| `SSH_HOST` | Your server IP | `54.227.103.23` |
| `SSH_USER` | SSH username | `ec2-user` |
| `SSH_PRIVATE_KEY` | Your PEM file content | (paste entire key) |
| `SSH_PORT` | SSH port (optional) | `22` |

**Click "Add secret" for each one.**

### Step 3: Fix Git Authentication on Server

The server needs to authenticate with GitHub to pull code.

**Option A: Use Personal Access Token (Recommended)**

1. **Create GitHub Personal Access Token:**
   - Go to: https://github.com/settings/tokens/new
   - Name: `fit-brawl-deployment`
   - Expiration: `No expiration` or `1 year`
   - Scopes: Check `repo` (Full control of private repositories)
   - Click "Generate token"
   - **COPY THE TOKEN** (you won't see it again!)

2. **Configure Git on Server:**

```bash
# SSH into your server
ssh -i "/c/Users/Mikell Razon/Downloads/Mikell.pem" ec2-user@54.227.103.23

# Navigate to project
cd /home/ec2-user/fit-brawl

# Update git remote to use token
git remote set-url origin https://YOUR_GITHUB_TOKEN@github.com/Fit-and-Brawl-Gym/fit-brawl.git

# Test it works
git fetch origin main
echo "✅ Git authentication working!"
```

**Replace `YOUR_GITHUB_TOKEN` with the token you copied!**

**Option B: Use Deploy Key (Alternative)**

1. **Generate SSH key on server:**

```bash
ssh -i "/c/Users/Mikell Razon/Downloads/Mikell.pem" ec2-user@54.227.103.23

# Generate new SSH key
ssh-keygen -t ed25519 -C "fitbrawl-deploy" -f ~/.ssh/fitbrawl_deploy -N ""

# Display public key
cat ~/.ssh/fitbrawl_deploy.pub
```

2. **Add Deploy Key to GitHub:**
   - Go to: https://github.com/Fit-and-Brawl-Gym/fit-brawl/settings/keys
   - Click "Add deploy key"
   - Title: `AWS EC2 Production Server`
   - Key: (paste the public key from above)
   - ✅ Check "Allow write access"
   - Click "Add key"

3. **Configure Git to use the key:**

```bash
# Update git config
cd /home/ec2-user/fit-brawl
git remote set-url origin git@github.com:Fit-and-Brawl-Gym/fit-brawl.git

# Configure SSH
cat >> ~/.ssh/config << 'EOF'
Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/fitbrawl_deploy
  StrictHostKeyChecking no
EOF

# Test
git fetch origin main
```

### Step 4: Test GitHub Actions

1. **Make a small change to any file:**

```bash
# On your local machine
cd /c/xampp/htdocs/fit-brawl
echo "# Auto-deployment test" >> README.md
git add README.md
git commit -m "Test: GitHub Actions auto-deployment"
git push origin main
```

2. **Watch the deployment:**
   - Go to: https://github.com/Fit-and-Brawl-Gym/fit-brawl/actions
   - You should see your workflow running
   - Click on it to see live progress

3. **Verify deployment:**
   - Visit: http://54.227.103.23
   - Changes should be live in 1-2 minutes!

### Step 5: Verify Everything Works

**Check deployment logs:**

```bash
ssh -i "/c/Users/Mikell Razon/Downloads/Mikell.pem" ec2-user@54.227.103.23

# Check git status
cd /home/ec2-user/fit-brawl
git log -1  # Should show your latest commit

# Check renderer is running
pgrep -f "node.*server.js"  # Should show process ID

# Check renderer logs
tail -20 /tmp/renderer.log
```

---

## 🎯 What Happens on Each Push

```
┌─────────────────────────────────────────────────────┐
│  1. You push code to GitHub (main branch)          │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│  2. GitHub Actions workflow triggers automatically  │
│     - Runs on GitHub's servers (ubuntu-latest)      │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│  3. Checkout code & install dependencies            │
│     - Gets latest code                              │
│     - Installs npm packages for renderer            │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│  4. SSH into your AWS server (54.227.103.23)        │
│     - Uses SSH_PRIVATE_KEY from secrets             │
│     - Connects as ec2-user                          │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│  5. Deploy on server:                               │
│     ✅ cd /home/ec2-user/fit-brawl                   │
│     ✅ git fetch origin main                         │
│     ✅ git reset --hard origin/main                  │
│     ✅ npm ci (install renderer dependencies)        │
│     ✅ Restart renderer service                      │
│     ✅ Set permissions on uploads/                   │
└──────────────────┬──────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────┐
│  6. Website is updated! 🎉                          │
│     - Takes 1-2 minutes total                       │
│     - View logs in GitHub Actions tab               │
└─────────────────────────────────────────────────────┘
```

---

## 🔧 Troubleshooting

### Issue: "fatal: could not read Username for 'https://github.com'"

**Problem:** Git can't authenticate with GitHub.

**Solution:** Follow **Step 3** above to configure Git authentication.

---

### Issue: "Permission denied (publickey)"

**Problem:** SSH key not added to GitHub Secrets.

**Solution:**
1. Copy your PEM file content: `cat "/c/Users/Mikell Razon/Downloads/Mikell.pem"`
2. Add to GitHub Secrets as `SSH_PRIVATE_KEY`
3. Make sure it includes `-----BEGIN` and `-----END` lines

---

### Issue: Workflow fails with "npm ci" error

**Problem:** package-lock.json out of sync.

**Solution:**

```bash
# On your local machine
cd /c/xampp/htdocs/fit-brawl/server-renderer
rm -rf node_modules package-lock.json
npm install
git add package-lock.json
git commit -m "Fix: Update package-lock.json"
git push origin main
```

---

### Issue: Renderer not restarting

**Problem:** Service not found or manual process.

**Solution:**

Check renderer status:

```bash
ssh -i "/c/Users/Mikell Razon/Downloads/Mikell.pem" ec2-user@54.227.103.23

# Check if systemd service exists
systemctl is-active fit-brawl-renderer

# If not, check manual process
pgrep -f "node.*server.js"

# View renderer logs
tail -30 /tmp/renderer.log
```

---

### Issue: Database migrations not running

**Problem:** Migrations need manual execution.

**Solution:**

After deployment, run migrations manually:

```bash
ssh -i "/c/Users/Mikell Razon/Downloads/Mikell.pem" ec2-user@54.227.103.23

# Run migration
mysql -h fitbrawl-db.carwg0m6glw6.us-east-1.rds.amazonaws.com \
      -u Mikell_Admin \
      -p'Mikedefender#12' \
      fit_and_brawl_gym < /home/ec2-user/fit-brawl/docs/database/migrations/YOUR_MIGRATION.sql
```

---

## 📊 Monitoring Deployments

### View GitHub Actions Logs

1. **Go to Actions tab:**
   - https://github.com/Fit-and-Brawl-Gym/fit-brawl/actions

2. **Click on latest workflow run**

3. **Click on "Deploy to AWS/Server" job**

4. **Expand steps to see detailed logs**

### View Server Logs

```bash
# SSH into server
ssh -i "/c/Users/Mikell Razon/Downloads/Mikell.pem" ec2-user@54.227.103.23

# Check git log
cd /home/ec2-user/fit-brawl
git log --oneline -5

# Check renderer logs
tail -50 /tmp/renderer.log

# Check Apache logs (if needed)
sudo tail -50 /var/log/httpd/error_log
```

---

## 🎨 Customizing Deployment

### Add Deployment Notifications

Update `.github/workflows/deploy.yml` to send notifications:

**Slack Notification:**
```yaml
- name: Notify Slack
  if: always()
  uses: 8398a7/action-slack@v3
  with:
    status: ${{ job.status }}
    webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```

**Discord Notification:**
```yaml
- name: Notify Discord
  if: always()
  uses: sarisia/actions-status-discord@v1
  with:
    webhook: ${{ secrets.DISCORD_WEBHOOK }}
```

### Add Health Check

```yaml
- name: Health Check
  run: |
    sleep 5  # Wait for services to start
    curl -f http://54.227.103.23 || exit 1
```

### Add Automatic Database Migrations

```yaml
- name: Run Database Migrations
  run: |
    cd /home/ec2-user/fit-brawl
    for migration in docs/database/migrations/*.sql; do
      if [ -f "$migration" ]; then
        echo "Running: $migration"
        mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME < "$migration"
      fi
    done
```

---

## 🔒 Security Best Practices

### 1. Use Least Privilege SSH Key

Create a dedicated deploy user:

```bash
# On server
sudo adduser deploy
sudo usermod -aG apache deploy  # or ec2-user group

# Give limited permissions
sudo chown -R deploy:apache /home/ec2-user/fit-brawl
```

### 2. Rotate SSH Keys Regularly

- Regenerate keys every 6-12 months
- Update GitHub Secrets with new key
- Remove old keys from server

### 3. Enable Branch Protection

- Require pull request reviews
- Require status checks to pass
- Prevent force pushes to main

### 4. Use Environment-Specific Secrets

Separate secrets for staging vs production:
- `PROD_SSH_HOST`
- `STAGING_SSH_HOST`

---

## 📝 Workflow File Reference

**Location:** `.github/workflows/deploy.yml`

**Trigger:** Automatic on push to `main` branch

**Manual Trigger:** Go to Actions tab → Select workflow → "Run workflow"

**Runs on:** Ubuntu latest (GitHub's servers)

**Deployment Target:** AWS EC2 (54.227.103.23)

---

## ✅ Verification Checklist

After setup, verify everything works:

- [ ] GitHub Secrets configured (SSH_HOST, SSH_USER, SSH_PRIVATE_KEY)
- [ ] Git authentication working on server
- [ ] Test push triggers workflow
- [ ] Workflow completes successfully (green checkmark)
- [ ] Changes appear on live site (http://54.227.103.23)
- [ ] Renderer service restarts properly
- [ ] No errors in GitHub Actions logs
- [ ] No errors in server logs

---

## 🚀 Next Steps

1. **Complete Step 3** (Fix Git Authentication)
   - Choose Option A (Personal Access Token) - easier
   - Or Option B (Deploy Key) - more secure

2. **Add GitHub Secrets** (Step 2)

3. **Test Deployment** (Step 4)

4. **Monitor first deployment**

5. **Fix AWS Security Group** (from previous guide)
   - Allow HTTP traffic on port 80

---

## 📞 Support

**GitHub Actions Documentation:**
- https://docs.github.com/en/actions

**SSH Action Documentation:**
- https://github.com/appleboy/ssh-action

**Workflow Status:**
- https://github.com/Fit-and-Brawl-Gym/fit-brawl/actions

---

**🎉 Once configured, every push to `main` automatically updates your live website!**

**Deployment time: ~1-2 minutes** ⚡
