# 🔧 Fix Server-Renderer 404 Error

**Issue:** Cloudflare URL returns "Cannot GET /" with 404 error  
**Cause:** Server-renderer isn't properly configured or not proxying to the main website

---

## 📋 Debug Commands - Run in EC2 Terminal

### Step 1: Check Server-Renderer Logs

```bash
# Check the logs for errors
cat /tmp/renderer.log

# Check if it's actually listening on port 3000
sudo ss -ltnp | grep :3000

# Test if server-renderer responds locally
curl -I http://localhost:3000/
```

### Step 2: Check Server-Renderer Configuration

```bash
# Go to server-renderer directory
cd /home/ec2-user/fit-brawl/server-renderer

# Check if server.js exists
ls -la server.js

# View the server.js configuration
head -50 server.js
```

### Step 3: Test Direct Connection

```bash
# Test if the main Docker web container works
curl -I http://localhost:80/

# Test if server-renderer can reach it
curl -v http://localhost:80/ 2>&1 | head -20
```

---

## 🔧 Quick Fix - Restart Server-Renderer Properly

```bash
# Stop server-renderer
pkill -f 'node.*server.js'

# Go to server-renderer directory
cd /home/ec2-user/fit-brawl/server-renderer

# Check what files are there
ls -la

# Start with explicit logging
node server.js 2>&1 | tee /tmp/renderer.log &

# Wait a moment
sleep 2

# Test locally
curl http://localhost:3000/

# Check what it's actually serving
curl -v http://localhost:3000/ 2>&1 | head -30
```

---

## 🎯 Expected Server-Renderer Behavior

The server-renderer should:
1. Listen on port 3000
2. Either:
   - Serve static files from the `public` directory, OR
   - Proxy requests to http://localhost:80 (the Docker container)

---

## 🔍 Troubleshooting Commands

### If server-renderer isn't proxying correctly:

```bash
# Check the server.js code to see what it's doing
cat /home/ec2-user/fit-brawl/server-renderer/server.js

# Check if there's a package.json
cat /home/ec2-user/fit-brawl/server-renderer/package.json

# Check for any .env or config files
ls -la /home/ec2-user/fit-brawl/server-renderer/ | grep -E 'env|config'
```

### If you need to reconfigure it:

The server-renderer might need to be configured to proxy to the main Docker container. 

**Option 1: Use the Docker web container directly with Cloudflare**

```bash
# Stop current tunnel
sudo pkill cloudflared

# Point Cloudflare tunnel directly to Docker container on port 80
nohup cloudflared tunnel --url http://localhost:80 > /tmp/cloudflared.log 2>&1 &

# Wait for new URL
sleep 5

# Get new URL
grep -oP 'https://[a-z0-9-]+\.trycloudflare\.com' /tmp/cloudflared.log | tail -1
```

**This bypasses server-renderer and goes straight to the main website on port 80!**

---

## 🚀 Recommended Solution: Point Tunnel to Port 80

Since your Docker web container is working perfectly on port 80, let's use that directly:

```bash
# Complete reset - stop everything
pkill -f 'node.*server.js'
sudo pkill cloudflared
sleep 2

# Start Cloudflare tunnel pointing to port 80 (Docker web)
nohup cloudflared tunnel --url http://localhost:80 > /tmp/cloudflared.log 2>&1 &

# Wait for connection
sleep 5

# Show the new URL and test it
echo "=== New Cloudflare URL (points to port 80) ===" && grep -oP 'https://[a-z0-9-]+\.trycloudflare\.com' /tmp/cloudflared.log | tail -1

# Test locally
curl -I http://localhost:80/
```

**This should work immediately since port 80 is already working!**

---

## 📝 What's the Difference?

| Setup | Port | What It Does |
|-------|------|--------------|
| **Current (broken)** | Cloudflare → 3000 → server-renderer | Server-renderer has 404 issue |
| **Recommended** | Cloudflare → 80 → Docker web | Direct to working website |

---

## ✅ Test After Fix

Once you've restarted the tunnel pointing to port 80:

```bash
# Get the new URL
NEW_URL=$(grep -oP 'https://[a-z0-9-]+\.trycloudflare\.com' /tmp/cloudflared.log | tail -1)

echo "Test this URL in your browser: $NEW_URL"

# Test locally
curl -I http://localhost:80/
```

Open the URL in your browser - it should work perfectly now! 🎉

---

**🎯 ACTION NOW:**  
Run the "Recommended Solution" commands above to point Cloudflare directly to port 80!
