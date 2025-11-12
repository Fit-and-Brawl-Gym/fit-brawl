# 🚀 Quick Reference: Upload Products & Equipment

## Your Images
- **13 product images** in `uploads/products/`
- **28 equipment images** in `uploads/equipment/`
- **Total: 41 images** (currently in .gitignore)

---

## ⚡ Fastest Method (Windows → EC2)

### Step 1: Open PowerShell

### Step 2: Upload images via SCP
```powershell
# Set your EC2 key path (CHANGE THIS!)
$KEY = "C:\path\to\your-key.pem"

# Upload products
scp -i $KEY -r uploads/products/* ec2-user@18.208.222.13:/tmp/products/

# Upload equipment
scp -i $KEY -r uploads/equipment/* ec2-user@18.208.222.13:/tmp/equipment/
```

### Step 3: SSH to EC2
```powershell
ssh -i $KEY ec2-user@18.208.222.13
```

### Step 4: Deploy on EC2
```bash
cd /home/ec2-user/fit-brawl
chmod +x deploy-product-equipment-images.sh
./deploy-product-equipment-images.sh
```

---

## 📋 Alternative: Manual Commands

If the script doesn't work, use these manual commands on EC2:

```bash
# Copy files
sudo cp -r /tmp/products/* /home/ec2-user/fit-brawl/uploads/products/
sudo cp -r /tmp/equipment/* /home/ec2-user/fit-brawl/uploads/equipment/

# Set permissions
sudo chown -R www-data:www-data /home/ec2-user/fit-brawl/uploads/
sudo chmod -R 755 /home/ec2-user/fit-brawl/uploads/

# Clean up
rm -rf /tmp/products /tmp/equipment

# Verify
ls -la /home/ec2-user/fit-brawl/uploads/products/
ls -la /home/ec2-user/fit-brawl/uploads/equipment/
```

---

## ✅ Verify After Upload

1. **Count files:**
   ```bash
   ls uploads/products/ | grep -v gitkeep | wc -l
   ls uploads/equipment/ | grep -v gitkeep | wc -l
   ```

2. **Test URLs:**
   ```
   https://your-url.trycloudflare.com/uploads/products/bcaa-powder.jpg
   https://your-url.trycloudflare.com/uploads/equipment/barbell-olympic-20kg.jpg
   ```

3. **Check website:**
   - Products page shows all images
   - Equipment page shows all images
   - No broken images

---

## 🔧 Troubleshooting

**SCP fails:**
```powershell
# Fix key permissions (Windows)
icacls "C:\path\to\key.pem" /inheritance:r
icacls "C:\path\to\key.pem" /grant:r "$($env:USERNAME):(R)"
```

**Images not showing:**
```bash
# On EC2, fix permissions
sudo chown -R www-data:www-data /home/ec2-user/fit-brawl/uploads/
sudo chmod -R 755 /home/ec2-user/fit-brawl/uploads/
```

---

## 📁 Image Lists

### Products (13):
bcaa-powder.jpg, bottled-water.jpg, energy-bar.jpg, hand-wraps.jpg, ice-pack.jpg, mouth-guards.jpg, muscle-roller.jpg, recovery-bar.jpg, resistance-bands.jpg, sports-drink.jpg, whey-protein-powder.jpg, workout-supplement.jpg

### Equipment (28):
ab-wheel-roller.jpg, assault-airbike.jpg, barbell-olympic-20kg.jpg, battle-ropes.jpg, bench-press-station.jpg, captains-chair.jpg, dumbbell-set-5-to-50kg.jpg, elliptical-trainer.jpg, foam-roller.jpg, kettlebell-16kg.jpg, leg-press-machine.jpg, medicine-ball-10kg.jpg, pilates-reformer.jpg, plank-station.jpg, plyometric-box-set.jpg, power-rack.jpg, pull-up-bar-station.jpg, resistance-band-set.jpg, rowing-machine.jpg, sandbag-20kg.jpg, seated-calf-raise.jpg, slam-ball-15kg.jpg, smith-machine.jpg, spin-bike.jpg, treadmill.jpg, trx-suspension-trainer.jpg, yoga-mat.jpg

---

**Ready to deploy!** 🎯
