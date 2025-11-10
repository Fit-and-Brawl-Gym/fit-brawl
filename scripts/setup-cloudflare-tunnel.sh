#!/bin/bash
# Cloudflare Tunnel Setup Script for Fit & Brawl
# This gives you FREE HTTPS with a cloudflare subdomain

set -e

echo "=================================================="
echo "🚀 Cloudflare Tunnel Setup for Fit & Brawl"
echo "=================================================="
echo ""
echo "This will:"
echo "  ✅ Install cloudflared on your EC2 server"
echo "  ✅ Create a free HTTPS tunnel"
echo "  ✅ Give you a URL like: https://fitbrawl-XXXXX.trycloudflare.com"
echo "  ✅ Automatic SSL certificate (no configuration needed!)"
echo ""
echo "=================================================="
echo ""

# Check if running on EC2
if [ ! -f /sys/hypervisor/uuid ] || ! grep -q ec2 /sys/hypervisor/uuid 2>/dev/null; then
    echo "⚠️  This script should be run on the EC2 server"
    echo "Run this instead:"
    echo "  ssh -i '/c/Users/Mikell Razon/Downloads/Mikell.pem' ec2-user@54.227.103.23 'bash -s' < scripts/setup-cloudflare-tunnel.sh"
    exit 1
fi

echo "📦 Step 1: Installing Cloudflare Tunnel (cloudflared)..."
echo ""

# Download and install cloudflared
if ! command -v cloudflared &> /dev/null; then
    echo "Downloading cloudflared..."

    # Detect architecture
    ARCH=$(uname -m)
    if [ "$ARCH" == "x86_64" ]; then
        CLOUDFLARED_URL="https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64"
    elif [ "$ARCH" == "aarch64" ]; then
        CLOUDFLARED_URL="https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64"
    else
        echo "❌ Unsupported architecture: $ARCH"
        exit 1
    fi

    wget -q $CLOUDFLARED_URL -O cloudflared
    sudo chmod +x cloudflared
    sudo mv cloudflared /usr/local/bin/

    echo "✅ cloudflared installed!"
else
    echo "✅ cloudflared already installed"
fi

echo ""
echo "=================================================="
echo "🌐 Step 2: Creating Cloudflare Tunnel..."
echo "=================================================="
echo ""
echo "Starting tunnel to Docker container on port 80..."
echo ""
echo "⚠️  IMPORTANT: This will create a temporary tunnel"
echo "    Copy the URL that appears and test it!"
echo ""
echo "The tunnel URL will look like:"
echo "  https://your-random-name.trycloudflare.com"
echo ""
echo "Press Ctrl+C when you're done testing to continue setup..."
echo ""

# Start a quick tunnel to show the user
cloudflared tunnel --url http://localhost:80 &
TUNNEL_PID=$!

echo ""
echo "Tunnel started! Check the URL above ⬆️"
echo ""
echo "Press Enter when you've tested the URL and want to continue..."
read -r

# Kill the test tunnel
kill $TUNNEL_PID 2>/dev/null || true

echo ""
echo "=================================================="
echo "🔧 Step 3: Setting up Permanent Tunnel..."
echo "=================================================="
echo ""

# Create config directory
sudo mkdir -p /etc/cloudflared

# Create tunnel config
sudo tee /etc/cloudflared/config.yml > /dev/null <<EOF
url: http://localhost:80
tunnel: fitbrawl-tunnel
credentials-file: /etc/cloudflared/cert.json
EOF

echo "✅ Configuration created"
echo ""

# Login to Cloudflare (this will open browser or show URL)
echo "🔐 Step 4: Authenticating with Cloudflare..."
echo ""
echo "This will open your browser or show a URL to visit."
echo "Sign in with:"
echo "  - Free Cloudflare account (create one if needed)"
echo "  - Or use Google/GitHub to sign in"
echo ""

sudo cloudflared tunnel login

echo ""
echo "✅ Authenticated!"
echo ""

# Create named tunnel
echo "🚇 Step 5: Creating named tunnel 'fitbrawl-tunnel'..."
echo ""

sudo cloudflared tunnel create fitbrawl-tunnel || echo "Tunnel may already exist, continuing..."

echo ""
echo "✅ Tunnel created!"
echo ""

# Create systemd service
echo "⚙️  Step 6: Setting up systemd service..."
echo ""

sudo tee /etc/systemd/system/cloudflared.service > /dev/null <<EOF
[Unit]
Description=Cloudflare Tunnel
After=network.target

[Service]
Type=simple
User=root
ExecStart=/usr/local/bin/cloudflared tunnel --config /etc/cloudflared/config.yml run fitbrawl-tunnel
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

# Enable and start service
sudo systemctl daemon-reload
sudo systemctl enable cloudflared
sudo systemctl start cloudflared

echo "✅ Service configured and started!"
echo ""

# Get tunnel info
echo "📊 Step 7: Getting your tunnel information..."
echo ""

TUNNEL_ID=$(sudo cloudflared tunnel info fitbrawl-tunnel 2>/dev/null | grep -oP 'ID:\s+\K[a-f0-9-]+' || echo "unknown")

echo ""
echo "=================================================="
echo "🎉 SUCCESS! Cloudflare Tunnel is Running!"
echo "=================================================="
echo ""
echo "Your tunnel details:"
echo "  Name: fitbrawl-tunnel"
echo "  ID: $TUNNEL_ID"
echo ""
echo "🌐 Your public URL:"
echo ""
sudo cloudflared tunnel info fitbrawl-tunnel 2>/dev/null | grep -i "url" || echo "  Check Cloudflare dashboard for your URL"
echo ""
echo "Or create a custom route:"
echo "  sudo cloudflared tunnel route dns fitbrawl-tunnel yoursubdomain.trycloudflare.com"
echo ""
echo "📋 Check tunnel status:"
echo "  sudo systemctl status cloudflared"
echo ""
echo "📜 View tunnel logs:"
echo "  sudo journalctl -u cloudflared -f"
echo ""
echo "🔄 Restart tunnel:"
echo "  sudo systemctl restart cloudflared"
echo ""
echo "=================================================="
echo ""
