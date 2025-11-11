#!/bin/bash
# Cleanup obsolete documentation files

echo "🗑️  Cleaning up obsolete MD files..."

# Keep only essential docs
KEEP_FILES=(
    "README.md"
    "COMPLETE-DEPLOYMENT-GUIDE.md"
)

# Files to delete (deployment guides we've completed)
rm -f AUTO-DEPLOYMENT-STATUS.md
rm -f AUTO-DEPLOYMENT-SUCCESS.md
rm -f AWS-CONSOLE-FIX-STEPS.md
rm -f AWS-OPEN-PORT-80-GUIDE.md
rm -f BUILD-SUMMARY.md
rm -f check-deployment.md
rm -f CSS-FIX-DEPLOYMENT-SUMMARY.md
rm -f DEPLOY-ADMIN-JS-FIXES.md
rm -f DEPLOYMENT-EMERGENCY-FIX.md
rm -f DEPLOYMENT-SUCCESS.md
rm -f DEPLOYMENT-TROUBLESHOOTING.md
rm -f DOCKER-FIX-SUCCESS.md
rm -f EC2-CONNECTION-RECOVERY.md
rm -f FIX-CLOUDFLARE-TUNNEL.md
rm -f FIX-SERVER-RENDERER-404.md
rm -f GITHUB-DEPLOYMENT-VISIBILITY.md
rm -f MANUAL-DEPLOY-COMMANDS.md
rm -f QUICK-DEPLOY-GUIDE.md
rm -f QUICK-START.md
rm -f SECURITY-UPDATE-REQUIRED.md
rm -f UAT-LIMITATIONS.md

# Cleanup scripts
rm -f fix-admin-js-paths.py
rm -f fix-all-admin-css.py
rm -f deploy-production.sh

echo "✅ Cleanup complete!"
echo ""
echo "📁 Remaining documentation:"
ls -lh *.md 2>/dev/null || echo "  (none)"
