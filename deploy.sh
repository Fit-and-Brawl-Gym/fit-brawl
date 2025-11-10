#!/bin/bash
# Quick deployment script - run this on your local machine to deploy manually
# Usage: ./deploy.sh

set -e

echo "🚀 Starting deployment to production..."

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configuration - EDIT THESE
SSH_HOST="54.227.103.23"  # Your server IP or domain
SSH_USER="ubuntu"          # SSH username
PROJECT_PATH="/var/www/html"

echo -e "${BLUE}→ Testing SSH connection...${NC}"
if ! ssh -o ConnectTimeout=5 "${SSH_USER}@${SSH_HOST}" "echo 'SSH connection successful'" 2>/dev/null; then
    echo -e "${RED}✗ Cannot connect to server. Check SSH_HOST and SSH_USER.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ SSH connection OK${NC}"

echo -e "${BLUE}→ Pulling latest code from GitHub...${NC}"
ssh "${SSH_USER}@${SSH_HOST}" << 'ENDSSH'
cd /var/www/html
git fetch origin main
git reset --hard origin/main
echo "✓ Code updated"
ENDSSH

echo -e "${GREEN}✓ Code pulled${NC}"

echo -e "${BLUE}→ Installing server-renderer dependencies...${NC}"
ssh "${SSH_USER}@${SSH_HOST}" << 'ENDSSH'
cd /var/www/html/server-renderer
npm ci --no-audit --no-fund
echo "✓ Dependencies installed"
ENDSSH

echo -e "${GREEN}✓ Dependencies installed${NC}"

echo -e "${BLUE}→ Restarting renderer service...${NC}"
ssh "${SSH_USER}@${SSH_HOST}" << 'ENDSSH'
if systemctl is-active --quiet fit-brawl-renderer; then
    sudo systemctl restart fit-brawl-renderer
    echo "✓ Renderer service restarted"
else
    echo "⚠ Renderer service not found (this is OK if not using systemd)"
fi
ENDSSH

echo -e "${BLUE}→ Setting permissions...${NC}"
ssh "${SSH_USER}@${SSH_HOST}" << 'ENDSSH'
cd /var/www/html
sudo chown -R www-data:www-data uploads/
sudo chmod -R 755 uploads/
echo "✓ Permissions set"
ENDSSH

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo -e "${BLUE}→ Visit your site to verify changes${NC}"
