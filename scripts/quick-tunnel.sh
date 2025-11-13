#!/bin/bash
# Quick Cloudflare Tunnel - Get HTTPS in 30 seconds!

echo "🚀 Starting Cloudflare Quick Tunnel..."
echo ""
echo "Installing cloudflared (if needed)..."

# Check if cloudflared is installed
if ! command -v cloudflared &> /dev/null; then
    # Download cloudflared
    wget -q https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 -O cloudflared
    chmod +x cloudflared
    sudo mv cloudflared /usr/local/bin/
    echo "✅ Installed cloudflared"
else
    echo "✅ cloudflared already installed"
fi

echo ""
echo "=================================================="
echo "🌐 Starting FREE HTTPS Tunnel..."
echo "=================================================="
echo ""
echo "Your website will be available at a URL like:"
echo "  https://something-random-words.trycloudflare.com"
echo ""
echo "This tunnel will run until you press Ctrl+C"
echo ""
echo "Starting tunnel now..."
echo ""

# Start the tunnel pointing to Docker on port 80
cloudflared tunnel --url http://localhost:80

echo ""
echo "Tunnel stopped."
