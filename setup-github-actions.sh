#!/bin/bash
# GitHub Actions Auto-Deployment Quick Setup Script
# Run this to configure automatic deployments

set -e

echo "🚀 GitHub Actions Auto-Deployment Setup"
echo "========================================"
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configuration
SSH_KEY="/c/Users/Mikell Razon/Downloads/Mikell.pem"
SSH_USER="ec2-user"
SSH_HOST="54.227.103.23"
GITHUB_REPO="Fit-and-Brawl-Gym/fit-brawl"
PROJECT_PATH="/home/ec2-user/fit-brawl"

echo -e "${BLUE}📋 Configuration:${NC}"
echo "   SSH Host: $SSH_HOST"
echo "   SSH User: $SSH_USER"
echo "   GitHub Repo: $GITHUB_REPO"
echo "   Project Path: $PROJECT_PATH" 
echo ""

# Step 1: Check SSH connection
echo -e "${BLUE}→ Step 1: Testing SSH connection...${NC}"
if ssh -i "$SSH_KEY" -o ConnectTimeout=5 "${SSH_USER}@${SSH_HOST}" "echo 'SSH OK'" 2>/dev/null; then
    echo -e "${GREEN}✓ SSH connection successful${NC}"
else
    echo -e "${RED}✗ Cannot connect to server${NC}"
    exit 1
fi
echo ""

# Step 2: Update git remote on server
echo -e "${BLUE}→ Step 2: Updating git remote URL...${NC}"
echo -e "${YELLOW}⚠ You need to provide a GitHub Personal Access Token${NC}"
echo ""
echo "Please follow these steps:"
echo "1. Go to: https://github.com/settings/tokens/new"
echo "2. Token name: fit-brawl-deployment"
echo "3. Expiration: No expiration (or 1 year)"
echo "4. Scopes: Check 'repo' (Full control of private repositories)"
echo "5. Click 'Generate token'"
echo "6. COPY THE TOKEN (you won't see it again!)"
echo ""
read -p "Enter your GitHub Personal Access Token: " GITHUB_TOKEN

if [ -z "$GITHUB_TOKEN" ]; then
    echo -e "${RED}✗ Token cannot be empty${NC}"
    exit 1
fi

echo -e "${BLUE}Configuring git remote with token...${NC}"
ssh -i "$SSH_KEY" "${SSH_USER}@${SSH_HOST}" << EOF
cd $PROJECT_PATH

# Update remote URL with token
git remote set-url origin https://${GITHUB_TOKEN}@github.com/${GITHUB_REPO}.git

# Test it works
if git fetch origin main 2>&1 | grep -q "fatal"; then
    echo "✗ Git authentication failed"
    exit 1
else
    echo "✓ Git authentication successful"
fi

# Configure git user (for potential commits)
git config user.email "fitxbrawl@gmail.com"
git config user.name "FitBrawl Deployment"

# Mark directory as safe
git config --global --add safe.directory $PROJECT_PATH
EOF

echo -e "${GREEN}✓ Git remote configured${NC}"
echo ""

# Step 3: GitHub Secrets instructions
echo -e "${BLUE}→ Step 3: Configure GitHub Secrets${NC}"
echo ""
echo "You need to add these secrets to GitHub:"
echo ""
echo -e "${YELLOW}1. Go to: https://github.com/${GITHUB_REPO}/settings/secrets/actions${NC}"
echo ""
echo "2. Click 'New repository secret' for each of these:"
echo ""
echo -e "${GREEN}Secret Name: SSH_HOST${NC}"
echo "   Value: $SSH_HOST"
echo ""
echo -e "${GREEN}Secret Name: SSH_USER${NC}"
echo "   Value: $SSH_USER"
echo ""
echo -e "${GREEN}Secret Name: SSH_PRIVATE_KEY${NC}"
echo "   Value: (paste content from next step)"
echo ""
echo "3. Get your SSH private key content:"
echo ""
echo -e "${YELLOW}Run this command and copy ALL the output:${NC}"
echo "   cat \"$SSH_KEY\""
echo ""
read -p "Press Enter when you've added all GitHub Secrets..."
echo ""

# Step 4: Test deployment
echo -e "${BLUE}→ Step 4: Testing deployment${NC}"
echo ""
echo "We'll make a small test change and push it to trigger GitHub Actions."
echo ""
read -p "Press Enter to continue..."

# Make a test change
cd /c/xampp/htdocs/fit-brawl
echo "" >> README.md
echo "<!-- GitHub Actions Auto-Deployment Test - $(date) -->" >> README.md

git add README.md
git commit -m "Test: GitHub Actions auto-deployment setup"

echo -e "${BLUE}Pushing to GitHub...${NC}"
git push origin main

echo ""
echo -e "${GREEN}✅ Test deployment triggered!${NC}"
echo ""
echo -e "${YELLOW}→ Watch the deployment progress:${NC}"
echo "   https://github.com/${GITHUB_REPO}/actions"
echo ""
echo "The deployment should complete in 1-2 minutes."
echo ""
read -p "Press Enter when the deployment is complete..."

# Step 5: Verify deployment
echo ""
echo -e "${BLUE}→ Step 5: Verifying deployment...${NC}"

ssh -i "$SSH_KEY" "${SSH_USER}@${SSH_HOST}" << 'EOF'
cd /home/ec2-user/fit-brawl

echo "Latest commit on server:"
git log -1 --oneline

echo ""
echo "Renderer status:"
if pgrep -f "node.*server.js" > /dev/null; then
    echo "✓ Renderer is running"
else
    echo "✗ Renderer is not running"
fi
EOF

echo ""
echo -e "${GREEN}🎉 Setup Complete!${NC}"
echo ""
echo "From now on, every push to 'main' will automatically deploy to production!"
echo ""
echo "Useful links:"
echo "- GitHub Actions: https://github.com/${GITHUB_REPO}/actions"
echo "- Your website: http://$SSH_HOST"
echo ""
