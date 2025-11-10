# Deployment Troubleshooting Guide

## Current Issue: SSH Timeout

**Error:** `dial tcp ***:22: i/o timeout`

**Cause:** GitHub Actions cannot reach your server on port 22.

---

## ✅ Fix: Update AWS Security Group

### Step 1: Find Your Security Group
1. Go to **AWS Console**: https://console.aws.amazon.com/ec2/
2. Click **Instances** → Select your instance
3. Click **Security** tab → Note the **Security Group ID**

### Step 2: Update Inbound Rules
1. Click on the **Security Group ID**
2. Click **Edit inbound rules**
3. Look for SSH (port 22) rule
4. **Change Source to:** `0.0.0.0/0` (or add if missing)
5. Click **Save rules**

### Step 3: Verify
```bash
# Test from your local machine
nc -zv 54.227.103.23 22

# Should output:
# Connection to 54.227.103.23 22 port [tcp/ssh] succeeded!
```

---

## 🔐 Current Required Inbound Rules

| Type | Protocol | Port | Source | Description |
|------|----------|------|--------|-------------|
| SSH | TCP | 22 | 0.0.0.0/0 | GitHub Actions + Admin |
| HTTP | TCP | 80 | 0.0.0.0/0 | Web traffic |
| HTTPS | TCP | 443 | 0.0.0.0/0 | Secure web traffic |

---

## 🧪 Test Connection

### From Your Local Machine:
```bash
# Test with your key
ssh -i "/c/Users/Mikell Razon/Downloads/Mikell.pem" ec2-user@54.227.103.23 "echo 'Connection works!'"

# Test with GitHub Actions key
ssh -i ~/.ssh/github_actions_deploy ec2-user@54.227.103.23 "echo 'GitHub key works!'"
```

### If Connection Works Locally But Not From GitHub:
- Your security group is blocking GitHub's IP addresses
- Update the security group to allow `0.0.0.0/0` on port 22

---

## 🔄 After Fixing Security Group

1. **Add the public key to your server** (if not done yet):
```bash
ssh -i "/c/Users/Mikell Razon/Downloads/Mikell.pem" ec2-user@54.227.103.23

# On server:
mkdir -p ~/.ssh
echo 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIAMRDJ2T7uqbsE1jfgf9Mi6CFJx9gh5eGKnw1LpvBaGd github-actions-fit-brawl' >> ~/.ssh/authorized_keys
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys
exit
```

2. **Re-run the GitHub Actions workflow**:
   - Go to: https://github.com/Mikell-Razon/fit-brawl/actions
   - Click the failed workflow
   - Click **"Re-run all jobs"**

---

## 🎯 Alternative: Self-Hosted Runner (No Port 22 Needed)

If you can't open port 22, use a self-hosted runner:

1. **On your EC2 instance:**
```bash
# Go to: https://github.com/Mikell-Razon/fit-brawl/settings/actions/runners/new
# Follow the instructions to download and configure the runner

cd /home/ec2-user/actions-runner
./config.sh --url https://github.com/Mikell-Razon/fit-brawl --token YOUR_TOKEN
sudo ./svc.sh install
sudo ./svc.sh start
```

2. **Update `.github/workflows/deploy.yml`:**
```yaml
jobs:
  deploy:
    runs-on: self-hosted  # Change from ubuntu-latest
```

3. **Simplify deployment (no SSH needed):**
```yaml
- name: Deploy
  run: |
    cd /var/www/html
    git pull
    cd server-renderer && npm ci
```

---

## 📞 Need Help?

**Can't access AWS Console?** Ask your AWS admin to:
- Add inbound rule for port 22 from `0.0.0.0/0`
- Or enable EC2 Instance Connect

**Still failing?** Check:
1. Instance is running (not stopped)
2. Public IP is `54.227.103.23`
3. Network ACLs allow port 22
4. VPC route table configured correctly
