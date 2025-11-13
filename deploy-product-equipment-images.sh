#!/bin/bash

################################################################################
# Fit & Brawl - Product & Equipment Image Deployment Script
# This script deploys product and equipment images from /tmp/ to uploads/
# Usage: ./deploy-product-equipment-images.sh
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="/home/ec2-user/fit-brawl"
UPLOAD_DIR="$PROJECT_DIR/uploads"
TEMP_PRODUCTS="/tmp/products"
TEMP_EQUIPMENT="/tmp/equipment"

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}   🏋️  Product & Equipment Image Deployment${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Check if temp directories exist
if [ ! -d "$TEMP_PRODUCTS" ] && [ ! -d "$TEMP_EQUIPMENT" ]; then
    echo -e "${RED}❌ Error: No images found in /tmp/${NC}"
    echo -e "${YELLOW}📝 Upload images first using SCP:${NC}"
    echo -e "${YELLOW}   From Windows:${NC}"
    echo -e "${YELLOW}   scp -i your-key.pem -r uploads/products/* ec2-user@server:/tmp/products/${NC}"
    echo -e "${YELLOW}   scp -i your-key.pem -r uploads/equipment/* ec2-user@server:/tmp/equipment/${NC}"
    exit 1
fi

# Count images
PRODUCT_COUNT=0
EQUIPMENT_COUNT=0

if [ -d "$TEMP_PRODUCTS" ]; then
    PRODUCT_COUNT=$(find "$TEMP_PRODUCTS" -type f \( -name "*.jpg" -o -name "*.png" -o -name "*.webp" -o -name "*.gif" \) 2>/dev/null | wc -l)
fi

if [ -d "$TEMP_EQUIPMENT" ]; then
    EQUIPMENT_COUNT=$(find "$TEMP_EQUIPMENT" -type f \( -name "*.jpg" -o -name "*.png" -o -name "*.webp" -o -name "*.gif" \) 2>/dev/null | wc -l)
fi

TOTAL_COUNT=$((PRODUCT_COUNT + EQUIPMENT_COUNT))

echo -e "${BLUE}📁 Found images to upload:${NC}"
echo -e "${GREEN}   📦 Products:  $PRODUCT_COUNT images${NC}"
echo -e "${GREEN}   🏋️  Equipment: $EQUIPMENT_COUNT images${NC}"
echo -e "${MAGENTA}   📊 Total:     $TOTAL_COUNT images${NC}"
echo ""

if [ "$TOTAL_COUNT" -eq 0 ]; then
    echo -e "${RED}❌ No valid images found to upload!${NC}"
    exit 1
fi

# Confirm with user
echo -e "${YELLOW}⚠️  Ready to deploy $TOTAL_COUNT image(s) to production${NC}"
read -p "Continue? (y/n): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${RED}❌ Deployment cancelled${NC}"
    exit 0
fi

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}   🚀 Starting Deployment${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Create directories if they don't exist
echo -e "${BLUE}📂 Creating upload directories...${NC}"
sudo mkdir -p "$UPLOAD_DIR/products"
sudo mkdir -p "$UPLOAD_DIR/equipment"
echo -e "${GREEN}   ✓ Directories ready${NC}"
echo ""

# Copy products
if [ "$PRODUCT_COUNT" -gt 0 ]; then
    echo -e "${BLUE}📦 Copying $PRODUCT_COUNT product image(s)...${NC}"

    # Show sample files being copied
    echo -e "${YELLOW}   Sample files:${NC}"
    ls "$TEMP_PRODUCTS" | head -3 | while read file; do
        echo -e "${YELLOW}   • $file${NC}"
    done
    if [ "$PRODUCT_COUNT" -gt 3 ]; then
        echo -e "${YELLOW}   ... and $((PRODUCT_COUNT - 3)) more${NC}"
    fi

    sudo cp -v "$TEMP_PRODUCTS/"* "$UPLOAD_DIR/products/" 2>/dev/null || true
    echo -e "${GREEN}   ✓ Products copied${NC}"
    echo ""
fi

# Copy equipment
if [ "$EQUIPMENT_COUNT" -gt 0 ]; then
    echo -e "${BLUE}🏋️  Copying $EQUIPMENT_COUNT equipment image(s)...${NC}"

    # Show sample files being copied
    echo -e "${YELLOW}   Sample files:${NC}"
    ls "$TEMP_EQUIPMENT" | head -3 | while read file; do
        echo -e "${YELLOW}   • $file${NC}"
    done
    if [ "$EQUIPMENT_COUNT" -gt 3 ]; then
        echo -e "${YELLOW}   ... and $((EQUIPMENT_COUNT - 3)) more${NC}"
    fi

    sudo cp -v "$TEMP_EQUIPMENT/"* "$UPLOAD_DIR/equipment/" 2>/dev/null || true
    echo -e "${GREEN}   ✓ Equipment copied${NC}"
    echo ""
fi

# Set permissions
echo -e "${BLUE}🔒 Setting correct permissions...${NC}"
sudo chown -R www-data:www-data "$UPLOAD_DIR/products" "$UPLOAD_DIR/equipment"
sudo chmod -R 755 "$UPLOAD_DIR/products" "$UPLOAD_DIR/equipment"
find "$UPLOAD_DIR/products" -type f -exec sudo chmod 644 {} \; 2>/dev/null || true
find "$UPLOAD_DIR/equipment" -type f -exec sudo chmod 644 {} \; 2>/dev/null || true
echo -e "${GREEN}   ✓ Permissions set (www-data:www-data, 755/644)${NC}"
echo ""

# Clean up temp directories
echo -e "${BLUE}🧹 Cleaning up temporary files...${NC}"
sudo rm -rf "$TEMP_PRODUCTS" "$TEMP_EQUIPMENT"
echo -e "${GREEN}   ✓ Cleanup complete${NC}"
echo ""

# Verify deployment
echo -e "${BLUE}🔍 Verifying deployment...${NC}"
FINAL_PRODUCT_COUNT=$(find "$UPLOAD_DIR/products" -type f \( -name "*.jpg" -o -name "*.png" -o -name "*.webp" -o -name "*.gif" \) 2>/dev/null | wc -l)
FINAL_EQUIPMENT_COUNT=$(find "$UPLOAD_DIR/equipment" -type f \( -name "*.jpg" -o -name "*.png" -o -name "*.webp" -o -name "*.gif" \) 2>/dev/null | wc -l)

echo -e "${GREEN}   ✓ Products:  $FINAL_PRODUCT_COUNT files in $UPLOAD_DIR/products/${NC}"
echo -e "${GREEN}   ✓ Equipment: $FINAL_EQUIPMENT_COUNT files in $UPLOAD_DIR/equipment/${NC}"
echo ""

# Show final summary
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}   ✅ Deployment Complete!${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${GREEN}📊 Final Summary:${NC}"
echo -e "${GREEN}   • Total images deployed: $((FINAL_PRODUCT_COUNT + FINAL_EQUIPMENT_COUNT))${NC}"
echo -e "${GREEN}   • Products:  $FINAL_PRODUCT_COUNT${NC}"
echo -e "${GREEN}   • Equipment: $FINAL_EQUIPMENT_COUNT${NC}"
echo ""

echo -e "${YELLOW}📝 Next Steps:${NC}"
echo -e "${YELLOW}   1. Test product images on website${NC}"
echo -e "${YELLOW}   2. Test equipment images on website${NC}"
echo -e "${YELLOW}   3. Check browser console for errors${NC}"
echo -e "${YELLOW}   4. Verify images load on mobile${NC}"
echo ""

echo -e "${BLUE}🔗 Test your images at:${NC}"
echo -e "${BLUE}   https://your-domain.com/uploads/products/bcaa-powder.jpg${NC}"
echo -e "${BLUE}   https://your-domain.com/uploads/equipment/barbell-olympic-20kg.jpg${NC}"
echo ""

echo -e "${MAGENTA}💡 Tip: Update your product database if image filenames changed${NC}"
echo ""

echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}   🎉 All Done!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
