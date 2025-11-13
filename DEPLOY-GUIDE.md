# Quick Deployment Guide

## 🚀 One-Command Deployment

### **From Your Local Machine (Windows - VS Code):**

**Step 1: Commit and push changes**
```bash
git add .
git commit -m "Your commit message here"
git push origin main
```

---

### **In EC2 Instance Connect:**

**One-line deployment command:**
```bash
cd /home/ec2-user/fit-brawl && git pull origin main && chmod +x deploy-now.sh && ./deploy-now.sh
```

---

## 📌 Set Up Permanent Alias (One-Time Setup)

Run this **once** in EC2 to create a shortcut command:

```bash
echo 'alias deploy="cd /home/ec2-user/fit-brawl && git pull origin main && chmod +x deploy-now.sh && ./deploy-now.sh"' >> ~/.bashrc && source ~/.bashrc
```

**After this, you can just type:**
```bash
deploy
```

And it will automatically:
1. Navigate to project folder
2. Pull latest code from GitHub
3. Rebuild Docker containers
4. Restart Cloudflare tunnel
5. Show you the new HTTPS URL

---

## 🔄 Daily Workflow

### **On Your Computer:**
```bash
# Make your changes in VS Code
# Test locally at http://localhost/fit-brawl/public/php/

# When ready to deploy:
git add .
git commit -m "Description of changes"
git push origin main
```

### **In EC2 Instance Connect:**
```bash
deploy
```

That's it! ✅

---

## 📝 Common Deployment Scenarios

### **Scenario 1: Quick CSS/JS/PHP Changes**
```bash
# Local (VS Code)
git add .
git commit -m "Updated mobile responsiveness"
git push origin main

# EC2
deploy
```

### **Scenario 2: Added New Files**
```bash
# Local (VS Code)
git add .
git commit -m "Added new feature page"
git push origin main

# EC2
deploy
```

### **Scenario 3: Database Changes**
```bash
# Local (VS Code)
git add .
git commit -m "Updated database schema"
git push origin main

# EC2
deploy
# Then manually run SQL updates if needed
```

---

## 🛠️ Manual Deployment (If Alias Not Set)

If you haven't set up the alias or are using a different session:

```bash
cd /home/ec2-user/fit-brawl && git pull origin main && chmod +x deploy-now.sh && ./deploy-now.sh
```

---

## 🔍 Troubleshooting Commands

### **Check if containers are running:**
```bash
docker ps
```

### **View container logs:**
```bash
docker logs fitbrawl_web --tail 50
```

### **Restart containers manually:**
```bash
cd /home/ec2-user/fit-brawl
docker compose down
docker compose up -d --build
```

### **Check Cloudflare tunnel:**
```bash
tail -30 /tmp/cloudflared.log
```

### **Restart Cloudflare tunnel manually:**
```bash
sudo pkill cloudflared
sleep 2
nohup cloudflared tunnel --url http://localhost:80 > /tmp/cloudflared.log 2>&1 &
sleep 5
grep -oP 'https://[a-z0-9-]+\.trycloudflare\.com' /tmp/cloudflared.log | tail -1
```

### **View recent commits:**
```bash
cd /home/ec2-user/fit-brawl
git log --oneline -5
```

### **Check current branch:**
```bash
cd /home/ec2-user/fit-brawl
git branch
```

---

## 📊 Deployment Checklist

Before deploying, make sure:
- [ ] Changes tested locally
- [ ] All files committed (`git status` is clean)
- [ ] Pushed to GitHub (`git push origin main`)
- [ ] No console errors in browser (F12)

After deploying:
- [ ] Check new Cloudflare URL from output
- [ ] Test homepage loads
- [ ] Test admin panel login
- [ ] Check browser console for errors (F12)
- [ ] Test on mobile if mobile changes were made

---

## ⏱️ Expected Deployment Time

- **Pull code:** 5 seconds
- **Build containers:** 3-5 minutes
- **Restart tunnel:** 10 seconds
- **Total:** ~5 minutes

---

## 🎯 Quick Reference Card

```
┌─────────────────────────────────────────┐
│     FIT & BRAWL DEPLOYMENT              │
├─────────────────────────────────────────┤
│ LOCAL (Windows):                        │
│   git add .                             │
│   git commit -m "message"               │
│   git push origin main                  │
│                                         │
│ EC2 (Instance Connect):                 │
│   deploy                                │
│                                         │
│ Or without alias:                       │
│   cd /home/ec2-user/fit-brawl &&        │
│   git pull origin main &&               │
│   ./deploy-now.sh                       │
└─────────────────────────────────────────┘
```

---

## 💡 Pro Tips

1. **Always test locally first** before deploying to production
2. **Write descriptive commit messages** for easier troubleshooting
3. **Deploy during low-traffic times** if possible (early morning)
4. **Keep the Cloudflare URL handy** for testing
5. **Monitor logs** after deployment for the first few minutes
6. **Test on mobile** if you made mobile-related changes

---

## 🆘 Emergency Rollback

If deployment breaks something:

```bash
cd /home/ec2-user/fit-brawl
git log --oneline -5  # Find the working commit hash
git reset --hard <commit-hash>
docker compose down
docker compose up -d --build
```

---

**Last Updated:** November 12, 2025
**Branch:** main
**Server:** EC2 (18.208.222.13)
