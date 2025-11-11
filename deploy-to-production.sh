#!/bin/bash
# Production Deployment Script for EC2
# Run this in your EC2 Instance Connect terminal

set -e  # Exit on any error

echo "============================================="
echo "🚀 Fit & Brawl Production Deployment"
echo "============================================="
echo ""

# Navigate to project directory
echo "📁 Navigating to project directory..."
cd /home/ec2-user/fit-brawl

# Pull latest code from GitHub
echo ""
echo "📥 Pulling latest code from GitHub..."
git pull origin main

# Show what changed
echo ""
echo "📋 Files changed:"
git log -1 --stat

# Stop current containers
echo ""
echo "🛑 Stopping current containers..."
docker compose down

# Rebuild containers with latest code
echo ""
echo "🔨 Rebuilding containers (this will take 3-5 minutes)..."
docker compose up -d --build

# Wait for containers to start
echo ""
echo "⏳ Waiting for containers to start..."
sleep 15

# Check container status
echo ""
echo "✅ Container status:"
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'

# Test web server
echo ""
echo "🧪 Testing web server..."
if curl -I http://localhost:80/ 2>&1 | grep -q "200 OK\|302 Found"; then
    echo "✅ Web server is responding!"
else
    echo "❌ Web server not responding properly"
    exit 1
fi

# Test admin CSS
echo ""
echo "🧪 Testing admin CSS..."
if curl -I http://localhost:80/php/admin/css/admin.css 2>&1 | grep -q "200 OK"; then
    echo "✅ Admin CSS accessible!"
else
    echo "⚠️  Admin CSS not accessible"
fi

# Test favicon
echo ""
echo "🧪 Testing favicon..."
if curl -I http://localhost:80/images/favicon-admin.png 2>&1 | grep -q "200 OK"; then
    echo "✅ Favicon accessible!"
else
    echo "⚠️  Favicon not accessible"
fi

# Restart Cloudflare tunnel
echo ""
echo "🔄 Restarting Cloudflare tunnel..."
sudo pkill cloudflared 2>/dev/null || true
sleep 2
nohup cloudflared tunnel --url http://localhost:80 > /tmp/cloudflared.log 2>&1 &
sleep 5

# Get new Cloudflare URL
echo ""
echo "🔗 Cloudflare HTTPS URL:"
NEW_URL=$(grep -oP 'https://[a-z0-9-]+\.trycloudflare\.com' /tmp/cloudflared.log 2>/dev/null | tail -1)
if [ -n "$NEW_URL" ]; then
    echo "   $NEW_URL"
    echo ""
    echo "✅ Deployment complete!"
else
    echo "   ⚠️  Could not retrieve Cloudflare URL"
    echo "   Check logs: tail -20 /tmp/cloudflared.log"
fi

echo ""
echo "============================================="
echo "📊 Deployment Summary"
echo "============================================="
echo "Direct IP:  http://54.227.103.23/"
echo "Admin:      http://54.227.103.23/php/admin/admin.php"
echo "HTTPS:      $NEW_URL"
echo ""
echo "🧪 Next Steps:"
echo "1. Test admin panel: http://54.227.103.23/php/admin/admin.php"
echo "2. Open browser console (F12) and verify NO 404 errors"
echo "3. Click all sidebar links and verify pages load"
echo "4. Test HTTPS URL: $NEW_URL"
echo ""
echo "✅ All done! Your site is deployed."
echo "============================================="
