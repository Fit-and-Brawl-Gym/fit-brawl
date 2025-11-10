# 🚨 DEPLOYMENT EMERGENCY FIX - Connection Timeout

**Date:** November 11, 2025  
**Status:** CRITICAL - Cannot connect to EC2 instance  
**Symptoms:** 
- Website shows: `ERR_CONNECTION_TIMED_OUT`
- SSH shows: `ssh: connect to host 54.227.103.23 port 22: Connection timed out`

---

## 🔍 Root Cause Analysis

**The EC2 instance is not responding at all** - this means either:

1. ❌ **EC2 Instance Stopped** - Someone stopped the instance
2. ❌ **Security Group Changed** - SSH (port 22) access was removed
3. ❌ **Instance Terminated** - The instance was deleted
4. ❌ **Network ACL Issue** - Network layer blocking all traffic

---

## 🚑 IMMEDIATE ACTION REQUIRED

### Step 1: Check EC2 Instance Status

1. **Log into AWS Console:** https://console.aws.amazon.com/ec2/
2. **Go to:** EC2 → Instances
3. **Find instance with IP:** `54.227.103.23`
4. **Check status:**

#### If Instance State = "Stopped" 🔴
```
✅ FIX: Select instance → Actions → Instance State → Start
⏱️ Wait 1-2 minutes for instance to start
✅ Verify: Instance State = "Running" (green)
```

#### If Instance State = "Running" ✅ but still can't connect
**Security Group issue** - Continue to Step 2

#### If Instance NOT Found ❌
**Instance was terminated** - See "Disaster Recovery" section below

---

### Step 2: Fix Security Group Rules

**Your instance needs BOTH port 22 (SSH) AND port 80 (HTTP) open**

1. In EC2 Console, select your instance
2. Click **Security** tab (bottom panel)
3. Click the **Security Group** name (blue link)
4. Click **Edit inbound rules**
5. **Verify these rules exist:**

| Type | Protocol | Port | Source | Description |
|------|----------|------|--------|-------------|
| SSH | TCP | 22 | Your IP or 0.0.0.0/0 | SSH access |
| HTTP | TCP | 80 | 0.0.0.0/0 | Website access |
| Custom TCP | TCP | 8080 | 0.0.0.0/0 | Backup web port |

6. **If rules are missing, add them:**
   - Click **Add rule**
   - Type: **SSH**, Port: **22**, Source: **My IP** (or 0.0.0.0/0)
   - Click **Add rule**
   - Type: **HTTP**, Port: **80**, Source: **Anywhere-IPv4** (0.0.0.0/0)
   - Click **Save rules**

---

### Step 3: Test Connection

```bash
# Test SSH (should connect)
ssh -i ~/.ssh/github_actions_deploy ec2-user@54.227.103.23

# If SSH works, check Docker status
docker ps

# Test HTTP (from browser)
http://54.227.103.23/
```

---

## 🔄 If Instance Was Stopped - Restart Containers

After starting the instance, Docker containers may not auto-start:

```bash
# SSH to server
ssh -i ~/.ssh/github_actions_deploy ec2-user@54.227.103.23

# Go to project directory
cd /home/ec2-user/fit-brawl

# Check if containers are running
docker ps

# If containers NOT running, start them
docker compose up -d

# Verify they started
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'

# Expected output:
# NAMES          STATUS          PORTS
# fitbrawl_web   Up X seconds    0.0.0.0:80->80/tcp
# fitbrawl_db    Up X seconds    0.0.0.0:3306->3306/tcp
```

---

## 💀 Disaster Recovery - If Instance Was Terminated

**If the EC2 instance was deleted, you'll need to:**

### Option A: Launch New Instance (Recommended)
1. Launch new EC2 instance (Amazon Linux 2023)
2. Install Docker and Docker Compose
3. Clone repository
4. Set up environment files
5. Run deployment

### Option B: Restore from Backup (if available)
1. Check if you have EC2 snapshots/AMIs
2. Launch new instance from snapshot
3. Update IP address in DNS/configs

**I can provide detailed steps for either option if needed.**

---

## 🎯 Quick Diagnostic Commands

Run these to quickly assess the situation:

### Check Instance Status (AWS CLI - if installed)
```bash
aws ec2 describe-instances --instance-ids i-YOUR-INSTANCE-ID --query 'Reservations[0].Instances[0].State.Name'
```

### Check Security Groups (AWS CLI)
```bash
aws ec2 describe-security-groups --group-ids sg-YOUR-SG-ID
```

### Test Network Connectivity
```bash
# Test if port 22 is reachable
nc -zv 54.227.103.23 22

# Test if port 80 is reachable
nc -zv 54.227.103.23 80

# Ping test
ping -c 4 54.227.103.23
```

---

## 📊 Current Status Checklist

Run through this checklist in AWS Console:

- [ ] Instance exists in EC2 Instances list
- [ ] Instance State = "Running" (green circle)
- [ ] Status Checks = "2/2 checks passed"
- [ ] Security Group has SSH (22) rule
- [ ] Security Group has HTTP (80) rule
- [ ] Public IP = 54.227.103.23 (or note new IP if different)
- [ ] SSH connection works
- [ ] Docker containers running
- [ ] Website loads in browser

---

## 🔧 Common Scenarios

### Scenario 1: "I accidentally stopped the instance"
**Fix:** Start it from EC2 Console → takes 1-2 minutes → Docker containers auto-start

### Scenario 2: "I changed security group rules"
**Fix:** Re-add SSH (22) and HTTP (80) rules → takes 5 seconds to apply

### Scenario 3: "Someone else has access and made changes"
**Fix:** Review CloudTrail logs in AWS Console to see what happened

### Scenario 4: "Instance was auto-stopped due to billing/alerts"
**Fix:** Check AWS billing alerts → Increase limits if needed → Restart instance

---

## 📞 Next Steps

**After fixing the connection:**

1. ✅ Verify instance is running
2. ✅ Verify security groups are correct
3. ✅ SSH to instance successfully
4. ✅ Check Docker containers: `docker ps`
5. ✅ If containers not running: `cd /home/ec2-user/fit-brawl && docker compose up -d`
6. ✅ Test website: http://54.227.103.23/
7. ✅ Update deployment docs with what happened

---

## 🚀 Expected Working State

```
┌─────────────────────────────────────┐
│ AWS EC2 Console                     │
├─────────────────────────────────────┤
│ Instance State: Running ✅          │
│ Status Checks: 2/2 passed ✅        │
│ Public IP: 54.227.103.23           │
│                                     │
│ Security Group Inbound Rules:       │
│ - SSH (22): Your IP ✅             │
│ - HTTP (80): 0.0.0.0/0 ✅          │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ SSH to EC2: docker ps               │
├─────────────────────────────────────┤
│ fitbrawl_web   Up    0.0.0.0:80     │
│ fitbrawl_db    Up    0.0.0.0:3306   │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ Browser: http://54.227.103.23/      │
├─────────────────────────────────────┤
│ ✅ Website loads                    │
│ ✅ CSS styling visible              │
│ ✅ Can login to admin/trainer       │
└─────────────────────────────────────┘
```

---

## 💡 Prevention

To prevent this in the future:

1. **Enable EC2 Instance Stop Protection**
   - EC2 Console → Instance → Actions → Instance Settings → Change stop protection
   
2. **Set up CloudWatch Alarms**
   - Alert when instance stops
   - Alert when status checks fail

3. **Document Security Group IDs**
   - Keep a backup of required security group rules

4. **Use Elastic IP**
   - Assign a permanent Elastic IP so IP doesn't change if instance restarts

---

**🎯 ACTION REQUIRED:** Log into AWS Console NOW and check instance status!
