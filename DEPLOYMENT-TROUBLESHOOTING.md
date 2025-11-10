# 🔧 GitHub Actions Deployment - Troubleshooting Guide

## Current Status

✅ **Git configuration on server:** Working (tested manually)  
✅ **SSH key available:** Yes  
✅ **Improved workflow:** Deployed with better error messages  
⏳ **GitHub Secrets:** Need verification  

---

## 🎯 Most Likely Issue: GitHub Secrets Not Set

Based on the error you described, the deployment is failing at the SSH connection step. This means:

**The GitHub Secrets are either:**
1. ❌ Not added yet
2. ❌ Incorrectly configured
3. ❌ Missing required values

---

## ✅ Solution: Verify & Add GitHub Secrets

### Step 1: Check Current Secrets

1. Go to: https://github.com/Fit-and-Brawl-Gym/fit-brawl/settings/secrets/actions

2. **You should see 3 secrets:**
   - `SSH_HOST`
   - `SSH_USER`  
   - `SSH_PRIVATE_KEY`

3. **If any are missing, add them using the values below:**

---

### Step 2: Add Missing Secrets

Use these exact values (from `GITHUB-SECRETS-SETUP.md`):

#### Secret 1: SSH_HOST
```
54.227.103.23
```

#### Secret 2: SSH_USER
```
ec2-user
```

#### Secret 3: SSH_PRIVATE_KEY
```
-----BEGIN RSA PRIVATE KEY-----
MIIEpQIBAAKCAQEA3TI/EUfe7JfG6rm1uOxQNTABWc5wLwDyL8fNgOe9XvRzDEHz
TrehSd1nuoSKc6aSYdYiQfKCFqPx5Xs/XYg8uQ64QMORHZZd9fOVdgOKTCUrqsPC
oNdezPK53HYfL3Ths6wjq+eJJlJZ2SF3YAHCxDv95jdqrO8IpvyaCVfEj7cIJ68b
AdoTrIZm4Cztyst6EMxBBoVVm0GYLKwwTEPSt+4hnwl6cTKf/LsLkd6COi/BkXb/
nsUvgvKxwgbTndjeBeJLfdr77Ss99ySaikA4dKbWEjMoA1cHgC533ycHsJUeUKJl
uIRbqREy7qhUBpMYVagJ4MsuaYphFP6ei2GHYwIDAQABAoIBADq7EaXL+HPHZOiE
AK0mCbHlmiY3OvAwBx4KphT48v4YV8fEZw7akPek0triQESu0KUrjxMFiebrBtpQ
nCWAe+cC8oc3pfs+JpqcX23jSMApCmwf6Dh+lsxCEnln36XhGeBz71DAfFjxequ9
SA30ybsC1speonNiUGDOJzQd2rHxf9mlZWG4hTkX+vNMrBlBECzSSqpYbXdGJchU
HjhCbxfnIXcHvmdzUhtDx32CVjM6ow8PPqXKoSMtxGdSo9v7yf0VVaFDJm2x3Nxy
ykZVQuEPRFX4eeAFFIrIcgNKR2Zo1VINgRF+srkcp35vMCqgf1AmLzbAkP5MlN0f
cKwPr3kCgYEA7uq5SQwfNfd2iSmC7xNps6pI2p6PtNd2SoAmLOzHRtPNpgj5koNg
/K0snted7E7nsQ0/ajNfJ6rKP4JkAjfYC4pssY2/bH5tik6A+sS3XgfKfVxeFkX2
xDUueCGaLPThJpbsarORWjomPJzZNh3+LLlsfk+/O8CyQykjg+8zJlcCgYEA7QMn
acivEWkDgewpZK9VCwDVSx780yg4+qIuvBa6H/Xds1OqKuEn9WTcV42TOd+tIV2u
tZvAjaproGIp+PqNrGFt8NEoQthh1UkMCa4XPt9nln8QI7IQNSvxjhS6YJXy97Kb
jm56+8lnj+yU88bStfYok4qTo3fJX44UEjADx9UCgYEAjT71pYrmFMilKngBMYhf
kFlW0vC08uwCwg67AwpE0cm8JbHeolDLkPZsj8fXVHJdpZ24ZPfUBKaDBUL4Dxum
vUPr+JhdmC2yYvcAZ8DOy2d9vdlSwoZAWkU6oMj01ik7xs2pHXflsdr46hKsk8Lt
ltFUo+RPoWrNuMjkd0Z+DecCgYEAsWqk/DivZBb5+y1vJuFghaYQA/WkR+RaErOI
zF8u4HHJjU1ZmlOE99qSXi+qP65CCTH5cBSxJNqnSh1xUeEsYBdmltfajX8wbNoL
WsuotvXAsxVWXYITQ9orLbIyec6FXAmlDA+DnCr9jO0J6xmv7WngoeEf90PZx9+x
ApRSoCkCgYEAgYkl/+jNU+wRlT+rKBdKcGz7w40PiCWMZd3C9yVxXPVSDnizDOIN
+8qfj9xopQBQlRoVjhrPZnVYkC9omU6Y6BZ3w/LboABRNPzF2VAJotaGMYAwO0+I
Yihr6pg3ETgXtTJcu/Wh6gXFV8syruZ4XrMDmGbFGSctxSTCeVDkTKI=
-----END RSA PRIVATE KEY-----
```

⚠️ **Important:** Copy the ENTIRE key including `-----BEGIN` and `-----END` lines!

---

### Step 3: Test the Deployment

After adding all secrets:

```bash
cd /c/xampp/htdocs/fit-brawl
echo "# Testing deployment with diagnostics" >> README.md
git add README.md
git commit -m "Test: Deployment with improved diagnostics"
git push origin main
```

---

## 📊 What the New Workflow Will Show

The improved workflow now provides detailed diagnostics:

### ✅ If Successful:
```
🔍 Deployment Diagnostics:
   User: ec2-user
   Home: /home/ec2-user
   Current directory: /home/ec2-user

📂 Navigating to project directory...
✅ Current directory: /home/ec2-user/fit-brawl

🔍 Checking git repository...
✅ Git repository confirmed
   Remote: https://ghp_***@github.com/Fit-and-Brawl-Gym/fit-brawl.git

📥 Pulling latest code...
✅ Updated to commit: 1857476
📝 Last commit: Improve: Add detailed error diagnostics...

📦 Installing renderer dependencies...
✅ Dependencies installed

🔄 Restarting renderer service...
✅ Renderer restarted in background (PID: 12345)

🔒 Setting permissions...
✅ Permissions updated

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Deployment completed successfully!
🌐 Website: http://54.227.103.23
📦 Commit: 1857476
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

### ❌ If Failed (Missing Secrets):
```
out: --- ERROR ---
out: error: Permission denied (publickey)
```

This means `SSH_PRIVATE_KEY` is missing or incorrect.

### ❌ If Failed (Wrong Path):
```
🔍 Deployment Diagnostics:
   User: ec2-user
   ...
📂 Navigating to project directory...
❌ ERROR: Cannot access /home/ec2-user/fit-brawl
```

### ❌ If Failed (Git Fetch Error):
```
📥 Pulling latest code...
❌ ERROR: Git fetch failed!
   This usually means:
   1. Network issue
   2. Git authentication problem
   3. Remote repository access denied
```

---

## 🔍 Debugging Steps

### 1. Check GitHub Actions Logs

Go to: https://github.com/Fit-and-Brawl-Gym/fit-brawl/actions

Click on the latest workflow run to see detailed logs.

### 2. Verify Secrets Are Set

```bash
# You should see 3 secrets listed
# If not, they're missing!
```

### 3. Test SSH Connection Manually

```bash
ssh -i "C:\Users\Mikell Razon\Downloads\Mikell.pem" ec2-user@54.227.103.23 "echo 'SSH works!'"
```

Should output: `SSH works!`

### 4. Test Git on Server

```bash
ssh -i "C:\Users\Mikell Razon\Downloads\Mikell.pem" ec2-user@54.227.103.23 "cd /home/ec2-user/fit-brawl && git fetch origin main && echo 'Git works!'"
```

Should output: `Git works!`

---

## 📋 Quick Checklist

Before the next deployment attempt:

- [ ] All 3 GitHub Secrets added (SSH_HOST, SSH_USER, SSH_PRIVATE_KEY)
- [ ] SSH_PRIVATE_KEY includes BEGIN and END lines
- [ ] No extra spaces or line breaks in secret values
- [ ] Git is configured on server (already done ✅)
- [ ] Workflow file updated with diagnostics (already done ✅)

---

## 🚀 Expected Results

Once secrets are added correctly:

**Deployment time:** 1-2 minutes  
**Success rate:** Should be 100% ✅  
**Error messages:** Clear and actionable  

---

## 🆘 Still Having Issues?

### Common Problems & Solutions:

| Error | Cause | Solution |
|-------|-------|----------|
| `Permission denied (publickey)` | SSH_PRIVATE_KEY wrong/missing | Re-add secret with full key |
| `Cannot access /home/ec2-user/fit-brawl` | Wrong path or permissions | Verify path exists on server |
| `Git fetch failed` | Network or auth issue | Check git remote on server |
| `npm ci failed` | Missing package.json | Check server-renderer directory |

---

## ✅ Next Steps

1. **Add GitHub Secrets** (if not done)
2. **Push a test commit** to trigger deployment
3. **Watch GitHub Actions** logs
4. **Celebrate** when you see the green checkmark! 🎉

---

**The deployment should work perfectly once the secrets are added!** 🚀
