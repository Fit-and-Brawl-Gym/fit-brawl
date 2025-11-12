# Uploading Products & Equipment Images to Production

## 📦 Current Situation

You have **41 images** that are currently ignored by Git:
- **13 Product images** in `uploads/products/`
- **28 Equipment images** in `uploads/equipment/`

These files are in `.gitignore` and won't be deployed through normal Git push.

---

## 🚀 Quick Upload Solution

### **Option 1: Using SCP (Recommended for Windows)**

**Step 1: Open PowerShell or Git Bash**

**Step 2: Navigate to your project directory**
```powershell
cd C:\xampp\htdocs\fit-brawl
```

**Step 3: Upload products and equipment to EC2**
```powershell
# Replace YOUR-KEY-PATH with your actual EC2 key path
scp -i "C:\path\to\your-key.pem" -r uploads/products/* ec2-user@18.208.222.13:/tmp/products/
scp -i "C:\path\to\your-key.pem" -r uploads/equipment/* ec2-user@18.208.222.13:/tmp/equipment/
```

**Alternative using Windows-style paths:**
```powershell
scp -i "C:/Users/YourName/.ssh/your-key.pem" -r uploads/products/* ec2-user@18.208.222.13:/tmp/products/
scp -i "C:/Users/YourName/.ssh/your-key.pem" -r uploads/equipment/* ec2-user@18.208.222.13:/tmp/equipment/
```

---

### **Option 2: Temporary Git Push (If SCP not available)**

**Step 1: Create a temporary upload branch**
```bash
git checkout -b temp-image-upload
```

**Step 2: Temporarily remove from .gitignore**
```bash
# Comment out the uploads in .gitignore
sed -i 's/^uploads\/equipment/# uploads\/equipment/' .gitignore
sed -i 's/^uploads\/products/# uploads\/products/' .gitignore
```

**Step 3: Add and commit images**
```bash
git add uploads/products/*
git add uploads/equipment/*
git commit -m "Temp: Upload product and equipment images"
git push origin temp-image-upload
```

**Step 4: On EC2, pull the temporary branch**
```bash
cd /home/ec2-user/fit-brawl
git fetch origin temp-image-upload
git checkout temp-image-upload
```

**Step 5: Clean up (both local and EC2)**
```bash
# On EC2: Switch back to main
git checkout main

# Local: Delete temp branch
git checkout main
git branch -D temp-image-upload
git push origin --delete temp-image-upload

# Restore .gitignore
git checkout .gitignore
```

---

## 🎯 Recommended: SCP Direct Upload

Since you're on Windows, here's the complete workflow:

### **Step-by-Step Guide:**

**1. Locate your EC2 SSH key**
- Usually in `C:\Users\YourName\.ssh\` or Downloads folder
- File ends with `.pem`

**2. Open PowerShell as Administrator**

**3. Navigate to project**
```powershell
cd C:\xampp\htdocs\fit-brawl
```

**4. Create temporary directories on EC2**
```powershell
ssh -i "C:\path\to\your-key.pem" ec2-user@18.208.222.13 "mkdir -p /tmp/products /tmp/equipment"
```

**5. Upload all images**
```powershell
# Upload products
scp -i "C:\path\to\your-key.pem" uploads/products/*.jpg ec2-user@18.208.222.13:/tmp/products/

# Upload equipment
scp -i "C:\path\to\your-key.pem" uploads/equipment/*.jpg ec2-user@18.208.222.13:/tmp/equipment/
```

**6. SSH into EC2 and deploy**
```bash
ssh -i "C:\path\to\your-key.pem" ec2-user@18.208.222.13
```

**7. On EC2, run the deployment script**
```bash
cd /home/ec2-user/fit-brawl

# Move images to correct location
sudo cp -r /tmp/products/* uploads/products/
sudo cp -r /tmp/equipment/* uploads/equipment/

# Set permissions
sudo chown -R www-data:www-data uploads/
sudo chmod -R 755 uploads/
find uploads/ -type f -exec sudo chmod 644 {} \;

# Clean up
rm -rf /tmp/products /tmp/equipment

# Verify
echo "Product images:"
ls -la uploads/products/ | grep -v gitkeep | wc -l

echo "Equipment images:"
ls -la uploads/equipment/ | grep -v gitkeep | wc -l
```

---

## 🛠️ Automated Script for EC2

I'll create a script that you can run on EC2 after uploading via SCP:

**Save this as `deploy-product-equipment-images.sh` on EC2:**

```bash
#!/bin/bash

echo "🖼️  Deploying Product & Equipment Images..."

# Variables
UPLOAD_DIR="/home/ec2-user/fit-brawl/uploads"
TEMP_PRODUCTS="/tmp/products"
TEMP_EQUIPMENT="/tmp/equipment"

# Check if temp directories exist
if [ ! -d "$TEMP_PRODUCTS" ] && [ ! -d "$TEMP_EQUIPMENT" ]; then
    echo "❌ Error: No images found in /tmp/"
    echo "Please upload images first using SCP"
    exit 1
fi

# Create upload directories
mkdir -p "$UPLOAD_DIR/products"
mkdir -p "$UPLOAD_DIR/equipment"

# Copy products
if [ -d "$TEMP_PRODUCTS" ]; then
    PRODUCT_COUNT=$(ls -1 "$TEMP_PRODUCTS" 2>/dev/null | wc -l)
    echo "📦 Copying $PRODUCT_COUNT product images..."
    sudo cp -r "$TEMP_PRODUCTS/"* "$UPLOAD_DIR/products/"
    echo "✅ Products copied"
fi

# Copy equipment
if [ -d "$TEMP_EQUIPMENT" ]; then
    EQUIPMENT_COUNT=$(ls -1 "$TEMP_EQUIPMENT" 2>/dev/null | wc -l)
    echo "🏋️  Copying $EQUIPMENT_COUNT equipment images..."
    sudo cp -r "$TEMP_EQUIPMENT/"* "$UPLOAD_DIR/equipment/"
    echo "✅ Equipment copied"
fi

# Set permissions
echo "🔒 Setting permissions..."
sudo chown -R www-data:www-data "$UPLOAD_DIR"
sudo chmod -R 755 "$UPLOAD_DIR"
find "$UPLOAD_DIR" -type f -exec sudo chmod 644 {} \;

# Clean up
echo "🧹 Cleaning up..."
rm -rf "$TEMP_PRODUCTS" "$TEMP_EQUIPMENT"

# Show summary
echo ""
echo "✅ Deployment Complete!"
echo ""
echo "📊 Summary:"
echo "   Products:  $(ls -1 $UPLOAD_DIR/products/ 2>/dev/null | grep -v gitkeep | wc -l) files"
echo "   Equipment: $(ls -1 $UPLOAD_DIR/equipment/ 2>/dev/null | grep -v gitkeep | wc -l) files"
echo ""
echo "🔗 Test images at:"
echo "   https://your-domain.com/uploads/products/bcaa-powder.jpg"
echo "   https://your-domain.com/uploads/equipment/barbell-olympic-20kg.jpg"
```

---

## 📋 Your Images List

### **Products (13 images):**
- bcaa-powder.jpg
- bottled-water.jpg
- energy-bar.jpg
- hand-wraps.jpg
- ice-pack.jpg
- mouth-guards.jpg
- muscle-roller.jpg
- recovery-bar.jpg
- resistance-bands.jpg
- sports-drink.jpg
- whey-protein-powder.jpg
- workout-supplement.jpg

### **Equipment (28 images):**
- ab-wheel-roller.jpg
- assault-airbike.jpg
- barbell-olympic-20kg.jpg
- battle-ropes.jpg
- bench-press-station.jpg
- captains-chair.jpg
- dumbbell-set-5-to-50kg.jpg
- elliptical-trainer.jpg
- foam-roller.jpg
- kettlebell-16kg.jpg
- leg-press-machine.jpg
- medicine-ball-10kg.jpg
- pilates-reformer.jpg
- plank-station.jpg
- plyometric-box-set.jpg
- power-rack.jpg
- pull-up-bar-station.jpg
- resistance-band-set.jpg
- rowing-machine.jpg
- sandbag-20kg.jpg
- seated-calf-raise.jpg
- slam-ball-15kg.jpg
- smith-machine.jpg
- spin-bike.jpg
- treadmill.jpg
- trx-suspension-trainer.jpg
- yoga-mat.jpg

---

## ⚡ One-Command Solution (Fastest)

**From your Windows machine (PowerShell):**

```powershell
# Set your key path (adjust this!)
$KEY = "C:\path\to\your-key.pem"
$SERVER = "ec2-user@18.208.222.13"

# Upload images
scp -i $KEY -r uploads/products/* ${SERVER}:/tmp/products/
scp -i $KEY -r uploads/equipment/* ${SERVER}:/tmp/equipment/

# SSH and deploy
ssh -i $KEY $SERVER "cd /home/ec2-user/fit-brawl && sudo cp -r /tmp/products/* uploads/products/ && sudo cp -r /tmp/equipment/* uploads/equipment/ && sudo chown -R www-data:www-data uploads/ && sudo chmod -R 755 uploads/ && rm -rf /tmp/products /tmp/equipment && echo 'Done! Images uploaded.'"
```

---

## ✅ Verification Steps

After uploading:

1. **Check file count on EC2:**
   ```bash
   ls -la /home/ec2-user/fit-brawl/uploads/products/ | grep -v gitkeep | wc -l
   ls -la /home/ec2-user/fit-brawl/uploads/equipment/ | grep -v gitkeep | wc -l
   ```

2. **Test image URLs:**
   ```
   https://your-cloudflare-url.trycloudflare.com/uploads/products/bcaa-powder.jpg
   https://your-cloudflare-url.trycloudflare.com/uploads/equipment/barbell-olympic-20kg.jpg
   ```

3. **Check in browser:**
   - Open your website
   - Go to Products page
   - Go to Equipment page
   - Verify all images load

---

## 🔧 Troubleshooting

### **SCP Permission Denied:**
```powershell
# Fix key permissions (Windows PowerShell as Admin)
icacls "C:\path\to\your-key.pem" /inheritance:r
icacls "C:\path\to\your-key.pem" /grant:r "$($env:USERNAME):(R)"
```

### **Images not showing after upload:**
```bash
# On EC2, check permissions
ls -la /home/ec2-user/fit-brawl/uploads/products/
ls -la /home/ec2-user/fit-brawl/uploads/equipment/

# Fix if needed
sudo chown -R www-data:www-data /home/ec2-user/fit-brawl/uploads/
sudo chmod -R 755 /home/ec2-user/fit-brawl/uploads/
```

---

## 💡 Best Practice

After successful upload, keep these images in Git for future deployments:

**Create a deployment package:**
```bash
# On your local machine
mkdir deployment-images
cp uploads/products/* deployment-images/
cp uploads/equipment/* deployment-images/

# Compress for easy sharing
tar -czf product-equipment-images.tar.gz deployment-images/
```

---

**Created:** November 12, 2025  
**Images:** 13 products + 28 equipment = **41 total**  
**Method:** SCP Direct Upload (Recommended)
