#!/bin/bash
# Manual deployment script for receipt rendering fix
# Run this to deploy the fixed render.js to production

set -e

echo "🚀 Deploying receipt rendering fix to production..."

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configuration
SSH_KEY="/c/Users/Mikell Razon/Downloads/Mikell.pem"
SSH_USER="ec2-user"
SSH_HOST="54.227.103.23"
PROJECT_PATH="/var/www/html"

echo -e "${BLUE}→ Testing SSH connection...${NC}"
if ! ssh -i "$SSH_KEY" -o ConnectTimeout=5 "${SSH_USER}@${SSH_HOST}" "echo 'SSH OK'" 2>/dev/null; then
    echo -e "${RED}✗ Cannot connect to server. Check your SSH key and connection.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ SSH connection OK${NC}"

echo -e "${BLUE}→ Pulling latest code from GitHub...${NC}"
ssh -i "$SSH_KEY" "${SSH_USER}@${SSH_HOST}" << 'ENDSSH'
cd /var/www/html
git fetch origin main
git reset --hard origin/main
echo "✓ Code updated"
ENDSSH
echo -e "${GREEN}✓ Code pulled${NC}"

echo -e "${BLUE}→ Installing/updating server-renderer dependencies...${NC}"
ssh -i "$SSH_KEY" "${SSH_USER}@${SSH_HOST}" << 'ENDSSH'
cd /var/www/html/server-renderer
npm ci --no-audit --no-fund 2>&1 | tail -5
echo "✓ Dependencies installed"
ENDSSH
echo -e "${GREEN}✓ Dependencies installed${NC}"

echo -e "${BLUE}→ Restarting renderer service...${NC}"
ssh -i "$SSH_KEY" "${SSH_USER}@${SSH_HOST}" << 'ENDSSH'
# Check if systemd service exists
if systemctl is-active --quiet fit-brawl-renderer 2>/dev/null; then
    sudo systemctl restart fit-brawl-renderer
    echo "✓ Renderer service restarted"
else
    # If no systemd service, kill any running node processes and restart manually
    echo "No systemd service found, checking for running renderer..."
    pkill -f "node.*server.js" || true
    cd /var/www/html/server-renderer
    nohup node server.js > /tmp/renderer.log 2>&1 &
    echo "✓ Renderer started in background (PID: $!)"
fi
ENDSSH
echo -e "${GREEN}✓ Renderer service restarted${NC}"

echo -e "${BLUE}→ Setting permissions...${NC}"
ssh -i "$SSH_KEY" "${SSH_USER}@${SSH_HOST}" << 'ENDSSH'
cd /var/www/html
sudo chown -R apache:apache uploads/ 2>/dev/null || sudo chown -R www-data:www-data uploads/
sudo chmod -R 755 uploads/
echo "✓ Permissions set"
ENDSSH
echo -e "${GREEN}✓ Permissions set${NC}"

echo -e "${BLUE}→ Verifying renderer is running...${NC}"
ssh -i "$SSH_KEY" "${SSH_USER}@${SSH_HOST}" << 'ENDSSH'
if pgrep -f "node.*server.js" > /dev/null; then
    echo "✓ Renderer process is running"
    echo "Process details:"
    ps aux | grep "node.*server.js" | grep -v grep
else
    echo "⚠ Warning: Renderer process not found!"
fi
ENDSSH

echo ""
echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo -e "${BLUE}→ Test receipt generation at: http://54.227.103.23/php/...${NC}"
echo ""
echo "📋 Next steps:"
echo "  1. Try generating a receipt"
echo "  2. If still failing, check renderer logs:"
echo "     ssh -i '$SSH_KEY' ${SSH_USER}@${SSH_HOST} 'tail -50 /tmp/renderer.log'"
