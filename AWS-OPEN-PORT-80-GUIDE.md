# 🔓 AWS Security Group - Open Port 80 Guide

**Issue:** `ERR_CONNECTION_REFUSED` when accessing http://54.227.103.23/  
**Cause:** AWS Security Group is blocking HTTP traffic on port 80  
**Solution:** Add inbound rule to allow HTTP traffic

---

## 📋 Step-by-Step Instructions

### Step 1: Log into AWS Console
1. Open browser and go to: **https://console.aws.amazon.com/**
2. Sign in with your AWS account credentials
3. Make sure you're in the **US East (N. Virginia) us-east-1** region (top-right corner)

---

### Step 2: Navigate to EC2 Security Groups
1. In the AWS Console search bar (top), type: **EC2**
2. Click on **EC2** service
3. In the left sidebar, scroll down to **Network & Security**
4. Click **Security Groups**

---

### Step 3: Find Your Security Group
Your EC2 instance IP is **54.227.103.23**

**Option A - Find by Instance:**
1. In the left sidebar, click **Instances**
2. Look for the instance with IP **54.227.103.23**
3. Select it (checkbox)
4. In the bottom panel, click **Security** tab
5. Click on the Security Group name (it's a blue link)

**Option B - Find Directly:**
1. In Security Groups list, look for a group that might be named:
   - Something with "fit-brawl"
   - "launch-wizard-X"
   - Or check which group is attached to your instance

---

### Step 4: Edit Inbound Rules
1. Select your security group (checkbox)
2. At the bottom, click the **Inbound rules** tab
3. Click **Edit inbound rules** button (top-right)

---

### Step 5: Add HTTP Rule
1. Click **Add rule** button
2. Fill in the new rule:

   | Field | Value |
   |-------|-------|
   | **Type** | Select **HTTP** from dropdown |
   | **Protocol** | TCP (auto-filled) |
   | **Port range** | 80 (auto-filled) |
   | **Source** | Select **Anywhere-IPv4** from dropdown<br>*(This will auto-fill as `0.0.0.0/0`)* |
   | **Description** | `Allow HTTP traffic for Fit & Brawl website` |

3. Click **Save rules** button (bottom-right)

---

### Step 6: Verify the Rule
After saving, you should see in the Inbound rules list:

```
Type: HTTP
Protocol: TCP
Port range: 80
Source: 0.0.0.0/0
Description: Allow HTTP traffic for Fit & Brawl website
```

⏱️ **The rule takes effect immediately (within 5-10 seconds)**

---

## 🧪 Test After Opening Port 80

### Test 1: Quick Connection Test
Open browser and go to: **http://54.227.103.23/**

**Expected Result:**
- ✅ Page loads (might show login page or redirect)
- ❌ **NOT:** "This site can't be reached" or "ERR_CONNECTION_REFUSED"

### Test 2: Admin Dashboard
1. Go to: **http://54.227.103.23/php/admin/admin.php**
2. Login with admin credentials
3. **Check:** Dashboard should have full CSS styling with colors and layout

### Test 3: Trainer UI
1. Go to: **http://54.227.103.23/php/trainer/index.php**
2. Login with trainer credentials
3. **Check:** Trainer pages should have full CSS styling

---

## 📸 Visual Checklist

### Before Opening Port 80:
```
Browser shows:
┌────────────────────────────────────┐
│ 🚫 This site can't be reached     │
│                                    │
│ 54.227.103.23 refused to connect. │
│                                    │
│ ERR_CONNECTION_REFUSED             │
└────────────────────────────────────┘
```

### After Opening Port 80:
```
Browser shows:
┌────────────────────────────────────┐
│ ✅ Fit & Brawl Gym Website        │
│                                    │
│ [Login page or dashboard loads]   │
│ [CSS styling is visible]          │
│                                    │
└────────────────────────────────────┘
```

---

## 🔍 Troubleshooting

### If it still doesn't work after adding the rule:

**Check 1: Verify Docker is running**
```bash
ssh -i ~/.ssh/github_actions_deploy ec2-user@54.227.103.23 "docker ps"
```
Should show: `fitbrawl_web` and `fitbrawl_db` containers running

**Check 2: Verify Apache is running**
```bash
ssh -i ~/.ssh/github_actions_deploy ec2-user@54.227.103.23 "docker exec fitbrawl_web service apache2 status"
```
Should show: Apache is running

**Check 3: Check Docker logs**
```bash
ssh -i ~/.ssh/github_actions_deploy ec2-user@54.227.103.23 "docker logs fitbrawl_web --tail 50"
```
Look for any error messages

**Check 4: Restart containers**
```bash
ssh -i ~/.ssh/github_actions_deploy ec2-user@54.227.103.23 "cd /home/ec2-user/fit-brawl && docker compose restart"
```

---

## ⚠️ Important Notes

### Security Consideration
Opening port 80 to `0.0.0.0/0` (anywhere) means anyone on the internet can access your website. This is **normal and expected** for a public website.

### HTTPS (Port 443)
You may also want to add HTTPS later for secure connections:
- Follow the same steps
- But select **HTTPS** instead of HTTP
- Port will be **443** instead of 80

### SSH Access (Port 22)
Your SSH port should already be open (since you can SSH in), but make sure it's restricted to your IP for security.

---

## ✅ Success Indicators

Once port 80 is open, you'll know it's working when:

1. ✅ **Browser:** No more "connection refused" errors
2. ✅ **Admin Dashboard:** Full styling with colors, cards, and sidebar
3. ✅ **Trainer UI:** Full styling with navigation and components
4. ✅ **CSS Files:** Directly accessible at URLs like:
   - http://54.227.103.23/php/admin/css/admin.css
   - http://54.227.103.23/css/global.css

---

## 🎯 Quick Reference

**Your Server IP:** `54.227.103.23`  
**Port to Open:** `80`  
**Protocol:** `TCP`  
**Source:** `0.0.0.0/0` (Anywhere-IPv4)  
**AWS Region:** `us-east-1` (US East N. Virginia)

**After opening port 80:**
- Admin Dashboard: http://54.227.103.23/php/admin/admin.php
- Trainer Dashboard: http://54.227.103.23/php/trainer/index.php
- Member Pages: http://54.227.103.23/

---

## 💬 Need Help?

If you get stuck at any step, take a screenshot of what you're seeing and I can guide you through it!

**Common questions:**
- "Which security group?" → Look for the one attached to instance with IP 54.227.103.23
- "Can't find EC2?" → Use the search bar at the top of AWS Console
- "Changes not working?" → Wait 10 seconds and hard refresh browser (Ctrl+F5)

🚀 **Once port 80 is open, everything will work!**
