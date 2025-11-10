# 🔧 Fix Cloudflare Tunnel - Commands to Run

**Issue:** Cloudflare tunnel URL not working: https://infrastructure-media-sagem-going.trycloudflare.com/  
**Cause:** Server-renderer (Node.js on port 3000) and/or cloudflared tunnel stopped when instance restarted

---

## 📋 Run These Commands in EC2 Instance Connect

Copy and paste these commands into your EC2 terminal:

### Step 1: Check What's Running

```bash
# Check if server-renderer (Node.js) is running
ps aux | grep node

# Check if cloudflared tunnel is running
ps aux | grep cloudflared

# Check what's listening on port 3000
sudo ss -ltnp | grep :3000
```

---

### Step 2: Restart Server-Renderer (Node.js)

```bash
# Stop any existing Node.js processes
pkill -f 'node.*server.js'

# Go to server-renderer directory
cd /home/ec2-user/fit-brawl/server-renderer

# Start server-renderer in background
nohup node server.js > /tmp/renderer.log 2>&1 &

# Verify it started
sleep 2
ps aux | grep "node server.js" | grep -v grep

# Check logs
tail -20 /tmp/renderer.log
```

**Expected output in logs:**
```
Server-renderer listening on port 3000
```

---

### Step 3: Restart Cloudflare Tunnel

```bash
# Stop any existing cloudflared processes (use sudo if needed)
sudo pkill cloudflared

# Start new Cloudflare tunnel
nohup cloudflared tunnel --url http://localhost:3000 > /tmp/cloudflared.log 2>&1 &

# Wait for tunnel to connect
sleep 5

# Check the logs for the new URL
tail -30 /tmp/cloudflared.log | grep -A2 "trycloudflare.com"
```

**The logs will show something like:**
```
Your quick Tunnel has been created! Visit it at:
https://XXXXX-XXXX-XXXX-XXXX.trycloudflare.com
```

⚠️ **IMPORTANT:** The Cloudflare URL changes each time you restart the tunnel!  
You'll get a **NEW URL** - it won't be `infrastructure-media-sagem-going` anymore.

---

### Step 4: Get the New Cloudflare URL

```bash
# Extract the new URL from logs
grep -oP 'https://[a-z0-9-]+\.trycloudflare\.com' /tmp/cloudflared.log | tail -1
```

Copy this new URL and test it in your browser!

---

## 🚀 Quick One-Liner Commands

If you want to do everything at once:

```bash
# Stop everything (use sudo for cloudflared)
pkill -f 'node.*server.js'; sudo pkill cloudflared; sleep 2

# Start server-renderer
cd /home/ec2-user/fit-brawl/server-renderer && nohup node server.js > /tmp/renderer.log 2>&1 & sleep 3

# Start Cloudflare tunnel
nohup cloudflared tunnel --url http://localhost:3000 > /tmp/cloudflared.log 2>&1 & sleep 5

# Show new URL
echo "=== Server-renderer status ===" && ps aux | grep "node server.js" | grep -v grep && echo "=== New Cloudflare URL ===" && grep -oP 'https://[a-z0-9-]+\.trycloudflare\.com' /tmp/cloudflared.log | tail -1
```

---

## 🔍 Troubleshooting

### If Server-Renderer Won't Start:

```bash
# Check for errors
cat /tmp/renderer.log

# Check if port 3000 is already in use
sudo ss -ltnp | grep :3000

# If something else is using port 3000, kill it
sudo fuser -k 3000/tcp
```

### If Cloudflare Tunnel Won't Connect:

```bash
# Check cloudflared logs for errors
cat /tmp/cloudflared.log

# Verify cloudflared is installed
which cloudflared

# If not installed, install it:
wget https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64
chmod +x cloudflared-linux-amd64
sudo mv cloudflared-linux-amd64 /usr/local/bin/cloudflared
```

---

## ⚙️ Make Services Auto-Start on Reboot

To prevent this issue in the future, create systemd services:

### Create Server-Renderer Service:

```bash
sudo tee /etc/systemd/system/fit-brawl-renderer.service << 'EOF'
[Unit]
Description=Fit & Brawl Server Renderer
After=network.target docker.service
Requires=docker.service

[Service]
Type=simple
User=ec2-user
WorkingDirectory=/home/ec2-user/fit-brawl/server-renderer
ExecStart=/usr/bin/node server.js
Restart=always
RestartSec=10
StandardOutput=append:/tmp/renderer.log
StandardError=append:/tmp/renderer.log

[Install]
WantedBy=multi-user.target
EOF

# Enable and start the service
sudo systemctl daemon-reload
sudo systemctl enable fit-brawl-renderer.service
sudo systemctl start fit-brawl-renderer.service
```

### Create Cloudflare Tunnel Service:

```bash
sudo tee /etc/systemd/system/cloudflared-tunnel.service << 'EOF'
[Unit]
Description=Cloudflare Tunnel
After=network.target fit-brawl-renderer.service
Requires=fit-brawl-renderer.service

[Service]
Type=simple
User=ec2-user
ExecStart=/usr/local/bin/cloudflared tunnel --url http://localhost:3000
Restart=always
RestartSec=10
StandardOutput=append:/tmp/cloudflared.log
StandardError=append:/tmp/cloudflared.log

[Install]
WantedBy=multi-user.target
EOF

# Enable and start the service
sudo systemctl daemon-reload
sudo systemctl enable cloudflared-tunnel.service
sudo systemctl start cloudflared-tunnel.service
```

### Check Service Status:

```bash
# Check server-renderer service
sudo systemctl status fit-brawl-renderer

# Check Cloudflare tunnel service
sudo systemctl status cloudflared-tunnel

# Get the Cloudflare URL
grep -oP 'https://[a-z0-9-]+\.trycloudflare\.com' /tmp/cloudflared.log | tail -1
```

---

## 📝 Quick Reference

| Service | Port | Log File | Command to Check |
|---------|------|----------|------------------|
| Server-Renderer | 3000 | `/tmp/renderer.log` | `ps aux \| grep "node server.js"` |
| Cloudflare Tunnel | - | `/tmp/cloudflared.log` | `ps aux \| grep cloudflared` |
| Docker Web | 80 | `docker logs fitbrawl_web` | `docker ps` |

---

## ✅ Success Indicators

You'll know it's working when:

1. ✅ `ps aux | grep node` shows `node server.js` running
2. ✅ `ps aux | grep cloudflared` shows cloudflared running
3. ✅ `/tmp/cloudflared.log` shows a `trycloudflare.com` URL
4. ✅ Opening the Cloudflare URL in browser loads your site
5. ✅ Server-renderer serves content on port 3000

---

**🎯 ACTION NOW:** 
Run the commands above in your EC2 Instance Connect terminal!
