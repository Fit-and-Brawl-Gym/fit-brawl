# Bulk Image Upload Deployment Guide

## 📸 Overview

This guide will help you prepare and deploy bulk images to the production server, bypassing the current upload restrictions (2MB limit, no JPG support).

---

## 🚨 Current Upload Restrictions

**From `file_upload_security.php`:**
- **Avatar uploads:** 2MB max, only PNG/GIF/WebP (no JPG)
- **Receipt uploads:** 10MB max, JPG/PNG/PDF allowed
- **File types blocked:** JPG files cannot be uploaded through the web interface for avatars

---

## 📦 Preparation Steps (Before Deployment)

### **Step 1: Organize Your Images**

Create a folder structure in your local project:

```
fit-brawl/
├── bulk-images/
│   ├── avatars/          # User/trainer profile pictures
│   ├── products/         # Product images
│   ├── receipts/         # Payment receipts
│   └── general/          # Other images
```

### **Step 2: Optimize Images (Optional but Recommended)**

**Reduce file sizes:**
```bash
# If you have ImageMagick installed
cd bulk-images/avatars
for img in *.jpg; do
    convert "$img" -resize 800x800\> -quality 85 "optimized_$img"
done

# Or convert JPG to WebP for better compression
for img in *.jpg; do
    convert "$img" -quality 80 "${img%.jpg}.webp"
done
```

**Online tools:**
- TinyPNG: https://tinypng.com/ (batch compress)
- Squoosh: https://squoosh.app/ (convert to WebP)
- ImageOptim (Mac): https://imageoptim.com/

### **Step 3: Prepare Deployment Package**

**Option A: Add to Git (Small batches < 50MB total)**
```bash
# Add images to git
git add bulk-images/
git commit -m "Add bulk images for deployment"
git push origin main
```

**Option B: Upload via SCP (Large batches > 50MB)**
```bash
# From your local machine (Windows PowerShell or Git Bash)
# Replace with your actual EC2 key path
scp -i "path/to/your-key.pem" -r bulk-images/ ec2-user@18.208.222.13:/home/ec2-user/fit-brawl/
```

---

## 🚀 Deployment Steps (On EC2)

### **Method 1: Deploy via Git (For Small Image Batches)**

**In EC2 Instance Connect:**

```bash
# Navigate to project
cd /home/ec2-user/fit-brawl

# Pull latest changes (including images)
git pull origin main

# Move images to appropriate directories
# For avatars:
cp -r bulk-images/avatars/* uploads/avatars/

# For products:
cp -r bulk-images/products/* uploads/products/

# For receipts:
cp -r bulk-images/receipts/* uploads/receipts/

# Set correct permissions
chown -R www-data:www-data uploads/
chmod -R 755 uploads/
find uploads/ -type f -exec chmod 644 {} \;

# Clean up
rm -rf bulk-images/

# Rebuild containers to ensure changes take effect
./deploy-now.sh
```

### **Method 2: Direct Upload via SCP (For Large Batches)**

**Step 1: Upload from Windows**
```bash
# Open PowerShell or Git Bash
scp -i "C:/path/to/your-key.pem" -r bulk-images/ ec2-user@18.208.222.13:/tmp/
```

**Step 2: Move Files on EC2**
```bash
# In EC2 Instance Connect
cd /home/ec2-user/fit-brawl

# Move images from temp directory
sudo cp -r /tmp/bulk-images/avatars/* uploads/avatars/
sudo cp -r /tmp/bulk-images/products/* uploads/products/
sudo cp -r /tmp/bulk-images/receipts/* uploads/receipts/

# Set correct permissions
sudo chown -R www-data:www-data uploads/
sudo chmod -R 755 uploads/
find uploads/ -type f -exec chmod 644 {} \;

# Clean up temp files
rm -rf /tmp/bulk-images/

# Restart Docker if needed
docker compose restart
```

---

## 🗄️ Update Database References (If Needed)

If you're uploading user avatars or product images, update the database:

**For User Avatars:**
```bash
# Connect to MySQL container
docker exec -it fitbrawl_db mysql -u root -p

# Update user avatar paths
USE fit_and_brawl;

-- Example: Update specific user
UPDATE users SET avatar = 'new-avatar-filename.webp' WHERE email = 'user@example.com';

-- View current avatars
SELECT id, email, avatar FROM users WHERE avatar IS NOT NULL;

exit;
```

**For Products:**
```sql
-- Update product images
UPDATE products SET image = 'new-product-image.webp' WHERE id = 1;

-- View current products
SELECT id, name, image FROM products;
```

---

## 📝 Image Upload Script (Automated Approach)

Create a deployment script for bulk uploads:

**Create `deploy-images.sh`:**
```bash
#!/bin/bash

echo "🖼️  Starting Bulk Image Upload..."

# Set variables
UPLOAD_DIR="/home/ec2-user/fit-brawl/uploads"
SOURCE_DIR="/tmp/bulk-images"

# Create upload directories if they don't exist
mkdir -p "$UPLOAD_DIR/avatars"
mkdir -p "$UPLOAD_DIR/products"
mkdir -p "$UPLOAD_DIR/receipts"

# Copy images
echo "📁 Copying avatars..."
cp -r "$SOURCE_DIR/avatars/"* "$UPLOAD_DIR/avatars/" 2>/dev/null || echo "No avatars to copy"

echo "📁 Copying products..."
cp -r "$SOURCE_DIR/products/"* "$UPLOAD_DIR/products/" 2>/dev/null || echo "No products to copy"

echo "📁 Copying receipts..."
cp -r "$SOURCE_DIR/receipts/"* "$UPLOAD_DIR/receipts/" 2>/dev/null || echo "No receipts to copy"

# Set permissions
echo "🔒 Setting permissions..."
sudo chown -R www-data:www-data "$UPLOAD_DIR"
sudo chmod -R 755 "$UPLOAD_DIR"
find "$UPLOAD_DIR" -type f -exec chmod 644 {} \;

# Clean up
echo "🧹 Cleaning up..."
rm -rf "$SOURCE_DIR"

echo "✅ Bulk image upload complete!"
echo "📊 Summary:"
echo "   Avatars: $(ls -1 $UPLOAD_DIR/avatars/ 2>/dev/null | wc -l) files"
echo "   Products: $(ls -1 $UPLOAD_DIR/products/ 2>/dev/null | wc -l) files"
echo "   Receipts: $(ls -1 $UPLOAD_DIR/receipts/ 2>/dev/null | wc -l) files"
```

**Make executable and run:**
```bash
chmod +x deploy-images.sh
./deploy-images.sh
```

---

## 🔧 Update Upload Restrictions (Optional)

If you want to allow JPG uploads and increase size limits after bulk upload:

**Edit `includes/file_upload_security.php`:**

```php
// Change line ~110
public static function imageUpload($uploadDir, $maxSizeMB = 5) {  // Increased from 2MB
    return new self(
        ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        $maxSizeMB * 1024 * 1024,
        $uploadDir
    );
}
```

**Then deploy:**
```bash
git add includes/file_upload_security.php
git commit -m "Increase upload limit to 5MB and allow JPG"
git push origin main

# On EC2
deploy
```

---

## ✅ Verification Checklist

After deployment, verify:

- [ ] Images are in correct directories (`/uploads/avatars/`, `/uploads/products/`, etc.)
- [ ] File permissions are correct (755 for directories, 644 for files)
- [ ] Images are accessible via browser (test URL: `https://your-domain.com/uploads/avatars/filename.webp`)
- [ ] Database references updated (if applicable)
- [ ] User avatars display correctly on profile pages
- [ ] Product images show in listings
- [ ] No broken image links (check browser console)

**Test URLs:**
```
https://your-cloudflare-url.trycloudflare.com/uploads/avatars/test-image.webp
https://your-cloudflare-url.trycloudflare.com/uploads/products/test-product.jpg
```

---

## 🐛 Troubleshooting

### **Images not showing:**
```bash
# Check file permissions
ls -la /home/ec2-user/fit-brawl/uploads/avatars/

# Should show: -rw-r--r-- www-data www-data

# Fix permissions if wrong:
sudo chown -R www-data:www-data /home/ec2-user/fit-brawl/uploads/
sudo chmod -R 755 /home/ec2-user/fit-brawl/uploads/
```

### **404 errors:**
```bash
# Check if files exist
ls /home/ec2-user/fit-brawl/uploads/avatars/

# Check Docker volume mapping in docker-compose.yml
cat docker-compose.yml | grep -A 5 volumes
```

### **Large file upload fails:**
```bash
# Check PHP upload limits in container
docker exec -it fitbrawl_web php -i | grep upload

# Update php.ini if needed (in Dockerfile)
```

---

## 📊 File Size Recommendations

| Image Type | Recommended Max Size | Recommended Format |
|------------|---------------------|-------------------|
| User Avatars | 500KB | WebP or PNG |
| Product Images | 1MB | WebP or JPG |
| Receipts | 2MB | PDF or JPG |
| Background Images | 2MB | WebP |

---

## 🎯 Quick Command Reference

```bash
# Upload images via SCP
scp -i your-key.pem -r bulk-images/ ec2-user@18.208.222.13:/tmp/

# On EC2: Deploy images
cd /home/ec2-user/fit-brawl
sudo cp -r /tmp/bulk-images/* uploads/
sudo chown -R www-data:www-data uploads/
sudo chmod -R 755 uploads/

# Verify
ls -la uploads/avatars/
```

---

## 📅 Deployment Timeline

**Recommended deployment schedule:**

1. **Prepare images** (1-2 hours)
   - Organize files
   - Optimize/compress
   - Test locally

2. **Upload to EC2** (5-30 minutes depending on size)
   - Via Git (small) or SCP (large)

3. **Deploy and configure** (15 minutes)
   - Move files
   - Set permissions
   - Update database

4. **Test and verify** (15 minutes)
   - Check all images load
   - Test on different pages
   - Mobile testing

**Total estimated time: 2-4 hours**

---

## 💡 Best Practices

1. **Always backup** before bulk uploads
2. **Test with a small batch first** (5-10 images)
3. **Optimize images** before uploading to save bandwidth
4. **Use WebP format** when possible for better compression
5. **Keep original filenames** for easy tracking
6. **Document what you uploaded** in commit messages
7. **Deploy during off-peak hours** (early morning/late night)
8. **Monitor server resources** during large uploads
9. **Have a rollback plan** ready
10. **Verify all images** after deployment

---

**Created:** November 12, 2025  
**For:** Fit & Brawl Production Deployment  
**Contact:** Admin Team

