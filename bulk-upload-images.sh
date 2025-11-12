#!/bin/bash

################################################################################
# Fit & Brawl - Bulk Image Upload Script
# This script helps deploy bulk images to the production server
# Usage: ./bulk-upload-images.sh
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="/home/ec2-user/fit-brawl"
UPLOAD_DIR="$PROJECT_DIR/uploads"
SOURCE_DIR="/tmp/bulk-images"

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}   🖼️  Fit & Brawl - Bulk Image Upload Tool${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Check if source directory exists
if [ ! -d "$SOURCE_DIR" ]; then
    echo -e "${RED}❌ Error: Source directory not found: $SOURCE_DIR${NC}"
    echo -e "${YELLOW}📝 Please upload your images first:${NC}"
    echo -e "${YELLOW}   From Windows: scp -i your-key.pem -r bulk-images/ ec2-user@server:/tmp/${NC}"
    exit 1
fi

# Check what images are available
echo -e "${BLUE}📁 Checking available images...${NC}"
AVATAR_COUNT=$(find "$SOURCE_DIR/avatars" -type f 2>/dev/null | wc -l || echo "0")
PRODUCT_COUNT=$(find "$SOURCE_DIR/products" -type f 2>/dev/null | wc -l || echo "0")
RECEIPT_COUNT=$(find "$SOURCE_DIR/receipts" -type f 2>/dev/null | wc -l || echo "0")
GENERAL_COUNT=$(find "$SOURCE_DIR/general" -type f 2>/dev/null | wc -l || echo "0")

echo -e "${GREEN}   ✓ Avatars: $AVATAR_COUNT files${NC}"
echo -e "${GREEN}   ✓ Products: $PRODUCT_COUNT files${NC}"
echo -e "${GREEN}   ✓ Receipts: $RECEIPT_COUNT files${NC}"
echo -e "${GREEN}   ✓ General: $GENERAL_COUNT files${NC}"
echo ""

TOTAL_COUNT=$((AVATAR_COUNT + PRODUCT_COUNT + RECEIPT_COUNT + GENERAL_COUNT))

if [ "$TOTAL_COUNT" -eq 0 ]; then
    echo -e "${RED}❌ No images found to upload!${NC}"
    exit 1
fi

# Confirm with user
echo -e "${YELLOW}⚠️  Ready to upload $TOTAL_COUNT image(s)${NC}"
read -p "Continue? (y/n): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${RED}❌ Upload cancelled${NC}"
    exit 0
fi

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}   🚀 Starting Upload Process${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Create directories if they don't exist
echo -e "${BLUE}📂 Creating upload directories...${NC}"
sudo mkdir -p "$UPLOAD_DIR/avatars"
sudo mkdir -p "$UPLOAD_DIR/products"
sudo mkdir -p "$UPLOAD_DIR/receipts"
sudo mkdir -p "$UPLOAD_DIR/general"
echo -e "${GREEN}   ✓ Directories ready${NC}"
echo ""

# Copy avatars
if [ "$AVATAR_COUNT" -gt 0 ]; then
    echo -e "${BLUE}📸 Copying $AVATAR_COUNT avatar(s)...${NC}"
    sudo cp -r "$SOURCE_DIR/avatars/"* "$UPLOAD_DIR/avatars/" 2>/dev/null || true
    echo -e "${GREEN}   ✓ Avatars copied${NC}"
fi

# Copy products
if [ "$PRODUCT_COUNT" -gt 0 ]; then
    echo -e "${BLUE}🛍️  Copying $PRODUCT_COUNT product image(s)...${NC}"
    sudo cp -r "$SOURCE_DIR/products/"* "$UPLOAD_DIR/products/" 2>/dev/null || true
    echo -e "${GREEN}   ✓ Products copied${NC}"
fi

# Copy receipts
if [ "$RECEIPT_COUNT" -gt 0 ]; then
    echo -e "${BLUE}🧾 Copying $RECEIPT_COUNT receipt(s)...${NC}"
    sudo cp -r "$SOURCE_DIR/receipts/"* "$UPLOAD_DIR/receipts/" 2>/dev/null || true
    echo -e "${GREEN}   ✓ Receipts copied${NC}"
fi

# Copy general images
if [ "$GENERAL_COUNT" -gt 0 ]; then
    echo -e "${BLUE}🖼️  Copying $GENERAL_COUNT general image(s)...${NC}"
    sudo cp -r "$SOURCE_DIR/general/"* "$UPLOAD_DIR/general/" 2>/dev/null || true
    echo -e "${GREEN}   ✓ General images copied${NC}"
fi

echo ""

# Set permissions
echo -e "${BLUE}🔒 Setting correct permissions...${NC}"
sudo chown -R www-data:www-data "$UPLOAD_DIR"
sudo chmod -R 755 "$UPLOAD_DIR"
find "$UPLOAD_DIR" -type f -exec sudo chmod 644 {} \;
echo -e "${GREEN}   ✓ Permissions set${NC}"
echo ""

# Clean up source directory
echo -e "${BLUE}🧹 Cleaning up temporary files...${NC}"
sudo rm -rf "$SOURCE_DIR"
echo -e "${GREEN}   ✓ Cleanup complete${NC}"
echo ""

# Show final summary
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}   ✅ Upload Complete!${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${GREEN}📊 Summary:${NC}"

FINAL_AVATAR_COUNT=$(ls -1 "$UPLOAD_DIR/avatars" 2>/dev/null | wc -l || echo "0")
FINAL_PRODUCT_COUNT=$(ls -1 "$UPLOAD_DIR/products" 2>/dev/null | wc -l || echo "0")
FINAL_RECEIPT_COUNT=$(ls -1 "$UPLOAD_DIR/receipts" 2>/dev/null | wc -l || echo "0")
FINAL_GENERAL_COUNT=$(ls -1 "$UPLOAD_DIR/general" 2>/dev/null | wc -l || echo "0")

echo -e "${GREEN}   • Avatars:  $FINAL_AVATAR_COUNT files in $UPLOAD_DIR/avatars/${NC}"
echo -e "${GREEN}   • Products: $FINAL_PRODUCT_COUNT files in $UPLOAD_DIR/products/${NC}"
echo -e "${GREEN}   • Receipts: $FINAL_RECEIPT_COUNT files in $UPLOAD_DIR/receipts/${NC}"
echo -e "${GREEN}   • General:  $FINAL_GENERAL_COUNT files in $UPLOAD_DIR/general/${NC}"
echo ""

echo -e "${YELLOW}📝 Next Steps:${NC}"
echo -e "${YELLOW}   1. Restart Docker containers if needed: docker compose restart${NC}"
echo -e "${YELLOW}   2. Update database references if required${NC}"
echo -e "${YELLOW}   3. Test images in browser${NC}"
echo -e "${YELLOW}   4. Verify all images load correctly${NC}"
echo ""

echo -e "${BLUE}🔗 Test your images at:${NC}"
echo -e "${BLUE}   https://your-domain.com/uploads/avatars/filename.ext${NC}"
echo ""

echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}   🎉 All Done!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
