# Easy Free HTTPS Deployment Guide

Quick alternatives to Cloudflare Tunnel for temporary deployment (1 month).

---

## Option 1: ngrok (Recommended - Easiest)

### Step 1: Download ngrok
1. Go to https://ngrok.com/download
2. Download for Windows
3. Extract `ngrok.exe` to a folder (e.g., `C:\ngrok\`)

### Step 2: Create Free Account
1. Sign up at https://dashboard.ngrok.com/signup
2. Copy your authtoken from the dashboard

### Step 3: Configure ngrok
```bash
# Open terminal and run:
cd /c/ngrok
./ngrok authtoken YOUR_AUTH_TOKEN_HERE
```

### Step 4: Start Your XAMPP Server
Make sure Apache and MySQL are running in XAMPP.

### Step 5: Expose Your App
```bash
# Expose your local server (assumes Apache runs on port 80)
./ngrok http 80

# Or if your app runs on a specific port:
./ngrok http 8080
```

### Step 6: Access Your Site
ngrok will show you a URL like:
```
Forwarding    https://abc123.ngrok-free.app -> http://localhost:80
```

Share this HTTPS URL with anyone! ✅

### Tips:
- Free tier gives random subdomain (changes each restart)
- Session lasts 8 hours, just restart ngrok when it expires
- Get a static subdomain with paid plan ($8/month)

---

## Option 2: Serveo (Zero Installation)

No download needed - uses SSH!

### Step 1: Start XAMPP
Make sure Apache is running on port 80.

### Step 2: Create Tunnel
```bash
ssh -R 80:localhost:80 serveo.net
```

### Step 3: Access Your Site
You'll get a URL like: `https://random.serveo.net`

### Tips:
- Completely free, no signup
- Custom subdomain: `ssh -R fitbrawl:80:localhost:80 serveo.net`
- Less stable than ngrok

---

## Option 3: LocalTunnel (Node.js)

### Step 1: Install Node.js
Download from https://nodejs.org if not installed.

### Step 2: Install LocalTunnel
```bash
npm install -g localtunnel
```

### Step 3: Start XAMPP
Make sure Apache is running.

### Step 4: Create Tunnel
```bash
# Basic usage:
lt --port 80

# With custom subdomain:
lt --port 80 --subdomain fitbrawl
```

### Step 5: Access Your Site
You'll get: `https://fitbrawl.loca.lt` (or random subdomain)

### First Visit Warning:
LocalTunnel shows a warning page on first visit. Users need to click "Click to Continue" once.

---

## Option 4: Render.com (Real Cloud Hosting)

Best for a more permanent demo. Uses your existing Dockerfile.

### Step 1: Push to GitHub
Make sure your code is on GitHub.

### Step 2: Create Render Account
1. Go to https://render.com
2. Sign up with GitHub

### Step 3: Create New Web Service
1. Click "New" → "Web Service"
2. Connect your GitHub repo
3. Configure:
   - **Name**: fit-brawl
   - **Environment**: Docker
   - **Plan**: Free

### Step 4: Add Environment Variables
Add your database credentials as environment variables.

### Step 5: Deploy
Click "Create Web Service" and wait for deployment.

### Result:
You get a URL like: `https://fit-brawl.onrender.com`

### Tips:
- 750 free hours/month
- Spins down after 15 min inactivity (slow first load)
- Free SSL included

---

## Quick Comparison

| Feature | ngrok | Serveo | LocalTunnel | Render |
|---------|-------|--------|-------------|--------|
| Setup Time | 2 min | 1 min | 3 min | 15 min |
| Installation | Yes | No | Yes (npm) | No |
| Signup Required | Yes | No | No | Yes |
| HTTPS | ✅ | ✅ | ✅ | ✅ |
| Custom Subdomain | Paid | Free | Free | Free |
| Session Limit | 8 hrs | None | None | None |
| Stability | High | Medium | Medium | High |
| Best For | Demo | Quick test | Dev | Production |

---

## Recommended for Capstone Defense

**For a 1-month demo/defense presentation:**

1. **Primary**: Use **ngrok** - most reliable, easy to restart
2. **Backup**: Have **Serveo** ready as backup (no install needed)

### Quick Start Command (ngrok):
```bash
# After setup, just run:
./ngrok http 80
```

### Share with Panel:
1. Copy the HTTPS URL from ngrok
2. Share the link with your defense panel
3. They can access your live demo immediately!

---

## Troubleshooting

### ngrok "session expired"
Just restart ngrok - you'll get a new URL.

### "Connection refused"
Make sure XAMPP Apache is running.

### Database connection issues
Update your database config to use `localhost` not `127.0.0.1`.

### Slow loading
First load may be slow. Refresh the page.

---

## Security Notes

⚠️ **Remember**: These tools expose your local machine to the internet!

1. Don't leave tunnels running when not needed
2. Use strong passwords in your app
3. Don't expose sensitive data
4. Close the tunnel after your presentation

---

*Last updated: November 2025*
