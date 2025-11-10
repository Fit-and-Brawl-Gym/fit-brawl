# ⚡ Quick Setup: GitHub Actions Auto-Deployment

Complete these 4 steps to enable automatic deployment.

---

## Step 1: Create GitHub Personal Access Token (2 minutes)

1. **Go to GitHub Token Settings:**
   👉 https://github.com/settings/tokens/new

2. **Fill in the form:**
   - **Note:** `fit-brawl-deployment`
   - **Expiration:** `No expiration` (or select 1 year)
   - **Select scopes:** ✅ Check `repo` (Full control of private repositories)

3. **Click "Generate token"**

4. **COPY THE TOKEN** - You won't see it again!
   - It looks like: `ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`
   - Save it temporarily in a text file

---

## Step 2: Configure Git on Server (2 minutes)

Open your terminal and run these commands:

```bash
# SSH into server
ssh -i "/c/Users/Mikell Razon/Downloads/Mikell.pem" ec2-user@54.227.103.23

# Navigate to project
cd /home/ec2-user/fit-brawl

# Update git remote with your token (REPLACE YOUR_TOKEN_HERE)
git remote set-url origin https://YOUR_TOKEN_HERE@github.com/Fit-and-Brawl-Gym/fit-brawl.git

# Test it works
git fetch origin main

# If you see "Already up to date" or similar, it worked! ✅
# If you see "fatal: could not read Username", the token is wrong ❌

# Configure git user info
git config user.email "fitxbrawl@gmail.com"
git config user.name "FitBrawl Server"

# Mark as safe directory
git config --global --add safe.directory /home/ec2-user/fit-brawl

# Exit SSH
exit
```

**⚠️ Important:** Replace `YOUR_TOKEN_HERE` with the token you copied in Step 1!

---

## Step 3: Add GitHub Secrets (3 minutes)

1. **Go to Repository Secrets:**
   👉 https://github.com/Fit-and-Brawl-Gym/fit-brawl/settings/secrets/actions

2. **Add Secret #1: SSH_HOST**
   - Click "New repository secret"
   - Name: `SSH_HOST`
   - Value: `54.227.103.23`
   - Click "Add secret"

3. **Add Secret #2: SSH_USER**
   - Click "New repository secret"
   - Name: `SSH_USER`
   - Value: `ec2-user`
   - Click "Add secret"

4. **Add Secret #3: SSH_PRIVATE_KEY**
   - Click "New repository secret"
   - Name: `SSH_PRIVATE_KEY`
   - Value: (Get from next step)

**Get SSH Private Key:**

In your terminal:
```bash
cat "/c/Users/Mikell Razon/Downloads/Mikell.pem"
```

**Copy ALL the output** including:
```
-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEA...
(many lines)
...
-----END RSA PRIVATE KEY-----
```

- Paste this into the `SSH_PRIVATE_KEY` secret
- Click "Add secret"

---

## Step 4: Test Auto-Deployment (2 minutes)

1. **Make a small test change:**

```bash
# On your local machine
cd /c/xampp/htdocs/fit-brawl

# Make a test change
echo "# Auto-deployment test" >> README.md

# Commit and push
git add README.md
git commit -m "Test: GitHub Actions auto-deployment"
git push origin main
```

2. **Watch the deployment:**
   - Go to: https://github.com/Fit-and-Brawl-Gym/fit-brawl/actions
   - You should see a new workflow running
   - Click on it to watch progress
   - Should complete in 1-2 minutes with green checkmark ✅

3. **Verify deployment worked:**

```bash
ssh -i "/c/Users/Mikell Razon/Downloads/Mikell.pem" ec2-user@54.227.103.23 "cd /home/ec2-user/fit-brawl && git log -1 --oneline"
```

Should show your latest commit!

---

## ✅ Success Checklist

After completing all steps:

- [ ] GitHub Personal Access Token created
- [ ] Git configured on server with token
- [ ] `git fetch origin main` works without errors
- [ ] GitHub Secrets added (SSH_HOST, SSH_USER, SSH_PRIVATE_KEY)
- [ ] Test push triggered GitHub Actions workflow
- [ ] Workflow completed successfully (green checkmark)
- [ ] Changes visible on server

---

## 🎉 You're Done!

**From now on:**
- Push code to `main` branch
- GitHub Actions automatically deploys
- Wait 1-2 minutes
- Changes are live! ✅

**Monitor deployments:**
- https://github.com/Fit-and-Brawl-Gym/fit-brawl/actions

---

## 🔧 Troubleshooting

### Issue: "fatal: could not read Username"

**Problem:** Git token not configured or wrong.

**Fix:**
```bash
ssh -i "/c/Users/Mikell Razon/Downloads/Mikell.pem" ec2-user@54.227.103.23
cd /home/ec2-user/fit-brawl
git remote set-url origin https://YOUR_NEW_TOKEN@github.com/Fit-and-Brawl-Gym/fit-brawl.git
git fetch origin main
```

### Issue: Workflow fails with "Permission denied (publickey)"

**Problem:** SSH_PRIVATE_KEY not set or incorrect.

**Fix:**
1. Run: `cat "/c/Users/Mikell Razon/Downloads/Mikell.pem"`
2. Copy ENTIRE output
3. Update `SSH_PRIVATE_KEY` secret in GitHub

### Issue: Workflow runs but server not updated

**Problem:** Deployment script path wrong.

**Fix:** Check `.github/workflows/deploy.yml` uses `/home/ec2-user/fit-brawl`

---

## 📖 Full Documentation

For detailed information, see:
- `docs/deployment/GITHUB-ACTIONS-AUTO-DEPLOY.md`

---

**Total setup time: ~10 minutes** ⚡

**Deployment time after setup: 1-2 minutes** 🚀
