#!/bin/bash
# EC2 Production Deployment - Updated
# Run these commands in your EC2 Instance Connect terminal

echo "============================================="
echo "🚀 Starting Production Deployment"
echo "============================================="
echo ""

# Step 1: Navigate to project
echo "📁 Step 1: Navigate to project directory..."
cd /home/ec2-user/fit-brawl

# Step 2: Pull latest changes
echo ""
echo "📥 Step 2: Pulling latest code from GitHub..."
git pull origin main

echo ""
echo "📋 Recent changes:"
git log -3 --oneline

# Step 3: Stop containers
echo ""
echo "🛑 Step 3: Stopping current containers..."
docker compose down

# Step 4: Rebuild containers
echo ""
echo "🔨 Step 4: Rebuilding Docker containers..."
echo "⏳ This will take 3-5 minutes..."
docker compose up -d --build

# Step 5: Wait for containers
echo ""
echo "⏳ Step 5: Waiting for containers to start..."
sleep 15

# Step 6: Check status
echo ""
echo "✅ Step 6: Container status:"
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'

# Step 7: Test web server
echo ""
echo "🧪 Step 7: Testing web server..."
if curl -s -I http://localhost:80/ | grep -q "200 OK\|302 Found"; then
    echo "✅ Web server responding!"
else
    echo "⚠️  Web server issue - check logs"
    docker logs fitbrawl_web --tail 20
fi

# Step 8: Test admin panel
echo ""
echo "🧪 Step 8: Testing admin panel..."
if curl -s -I http://localhost:80/php/admin/admin.php | grep -q "200 OK\|302 Found"; then
    echo "✅ Admin panel accessible!"
else
    echo "⚠️  Admin panel issue"
fi

# Step 9: Test images
echo ""
echo "🧪 Step 9: Testing image paths..."
if curl -s -I http://localhost:80/images/favicon-admin.png | grep -q "200 OK"; then
    echo "✅ Images loading correctly!"
else
    echo "⚠️  Image path issue"
fi

# Step 10: Restart Cloudflare Tunnel
echo ""
echo "🔄 Step 10: Restarting Cloudflare tunnel..."
sudo pkill cloudflared 2>/dev/null || true
sleep 2
nohup cloudflared tunnel --url http://localhost:80 > /tmp/cloudflared.log 2>&1 &

echo ""
echo "⏳ Waiting for tunnel to connect..."
sleep 8

# Step 11: Get new URL
echo ""
echo "🔗 Step 11: Getting new Cloudflare HTTPS URL..."
NEW_URL=$(grep -oP 'https://[a-z0-9-]+\.trycloudflare\.com' /tmp/cloudflared.log 2>/dev/null | tail -1)

if [ -n "$NEW_URL" ]; then
    echo ""
    echo "============================================="
    echo "✅ DEPLOYMENT SUCCESSFUL!"
    echo "============================================="
    echo ""
    echo "📍 Access URLs:"
    echo "   Direct IP:  http://54.227.103.23/"
    echo "   Admin:      http://54.227.103.23/php/admin/admin.php"
    echo "   HTTPS:      $NEW_URL"
    echo ""
    echo "🧪 Next Steps - Test These:"
    echo "   1. Open admin panel and login"
    echo "   2. Click all sidebar links"
    echo "   3. Check browser console (F12) - NO 404 errors"
    echo "   4. Test sign-up page on mobile"
    echo "   5. Verify favicon and images load"
    echo ""
    echo "✅ All systems deployed!"
else
    echo ""
    echo "⚠️  Cloudflare tunnel URL not found"
    echo "Check logs: tail -20 /tmp/cloudflared.log"
fi

echo "============================================="
