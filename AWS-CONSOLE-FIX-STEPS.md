# 🔍 AWS Console Actions Needed - Cannot SSH

## Current Situation
- ✅ Security Group fixed - traffic reaches instance
- ❌ SSH still timing out (port 22)
- ❌ HTTP connection refused (port 80) - Docker containers likely not running
- **Problem:** Can't SSH to restart Docker containers

---

## 🎯 SOLUTION: Use AWS Systems Manager Session Manager

Since SSH isn't working, use AWS's web-based terminal (Session Manager) to access your instance:

### Option 1: EC2 Instance Connect (Easiest)

1. **Go to your EC2 instance in AWS Console**
2. **Select the `fitbrawl-web` instance** (checkbox)
3. **Click "Connect" button** (top right, orange button you saw in the screenshot)
4. **Choose "EC2 Instance Connect" tab**
5. **Click "Connect" button**

This will open a browser-based terminal directly to your instance!

### Option 2: Session Manager (If Instance Connect doesn't work)

1. **In AWS Console, select your instance**
2. **Click "Connect" button**
3. **Choose "Session Manager" tab**
4. **Click "Connect"**

---

## 📋 Commands to Run Once Connected

After you get the terminal (via either method above), run these commands:

### Step 1: Check Docker Status
```bash
# Check if Docker is running
sudo systemctl status docker

# If not running, start it
sudo systemctl start docker
```

### Step 2: Check Container Status
```bash
# Go to project directory
cd /home/ec2-user/fit-brawl

# Check if containers are running
docker ps

# Check ALL containers (including stopped)
docker ps -a
```

### Step 3: Start Containers
```bash
# If containers are stopped, start them
docker compose up -d

# Wait 10 seconds, then verify
sleep 10
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'
```

### Step 4: Verify Port Mapping
Expected output:
```
NAMES          STATUS          PORTS
fitbrawl_web   Up X seconds    0.0.0.0:80->80/tcp
fitbrawl_db    Up X seconds    0.0.0.0:3306->3306/tcp
```

If you see `0.0.0.0:8080->80/tcp` instead, the docker-compose.yml didn't update properly.

### Step 5: Pull Latest Code (if needed)
```bash
cd /home/ec2-user/fit-brawl
git pull origin main
docker compose down
docker compose up -d --build
```

### Step 6: Check Why SSH Isn't Working
```bash
# Check if SSH service is running
sudo systemctl status sshd

# If not running, start it
sudo systemctl start sshd

# Check SSH port
sudo ss -ltnp | grep :22
```

---

## 🔧 Alternative: Reboot Instance

If you can't get Session Manager to work, you can:

1. **In EC2 Console**
2. **Select your instance**
3. **Actions → Instance state → Reboot**
4. **Wait 2-3 minutes**
5. **Try SSH again:**
   ```bash
   ssh -i ~/.ssh/github_actions_deploy ec2-user@54.227.103.23
   ```

**⚠️ Note:** Rebooting will restart Docker containers automatically if they're configured to auto-start.

---

## 🎯 Quick Fix Checklist

Do these steps IN ORDER:

- [ ] Step 1: Click "Connect" button in EC2 Console
- [ ] Step 2: Use EC2 Instance Connect to get terminal
- [ ] Step 3: Run `docker ps` - are containers running?
- [ ] Step 4: If NO containers, run `docker compose up -d`
- [ ] Step 5: Check port mapping is `0.0.0.0:80->80/tcp`
- [ ] Step 6: Test website: open http://54.227.103.23/ in browser
- [ ] Step 7: Fix SSH: `sudo systemctl status sshd`

---

## 📸 What You Should See

### In EC2 Instance Connect Terminal:
```bash
[ec2-user@ip-XXX ~]$ cd /home/ec2-user/fit-brawl
[ec2-user@ip-XXX fit-brawl]$ docker ps
NAMES          STATUS          PORTS
fitbrawl_web   Up 2 minutes    0.0.0.0:80->80/tcp
fitbrawl_db    Up 2 minutes    0.0.0.0:3306->3306/tcp
```

### If containers NOT running:
```bash
[ec2-user@ip-XXX fit-brawl]$ docker compose up -d
[+] Running 2/2
✔ Container fitbrawl_db   Started
✔ Container fitbrawl_web  Started
```

---

## 🚨 Why SSH Might Not Work

Possible reasons:
1. **SSM Agent not installed** - Session Manager requires it
2. **SSH service stopped** - Can fix via Instance Connect
3. **Network ACL blocking** - Check VPC → Network ACLs
4. **Instance was rebooted** and services didn't auto-start
5. **Firewall on the instance** blocking SSH

---

## ✅ Success Indicators

You'll know it's working when:
1. ✅ EC2 Instance Connect terminal opens
2. ✅ `docker ps` shows 2 running containers
3. ✅ Port 80 mapped: `0.0.0.0:80->80/tcp`
4. ✅ Browser loads: http://54.227.103.23/
5. ✅ SSH works: `ssh -i ~/.ssh/github_actions_deploy ec2-user@54.227.103.23`

---

**🎯 ACTION NOW:** 
1. Go back to EC2 Console
2. Click the orange "Connect" button
3. Use EC2 Instance Connect
4. Run the commands above!
