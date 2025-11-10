# Complete Website Setup Guide
**From HTTP to HTTPS with Custom Domain**

---

## 📋 What We're Going to Do

1. ✅ **Port 80 (HTTP) is already working** - Anyone can visit `http://54.227.103.23`
2. 🔧 **Open Port 443 for HTTPS** - Configure AWS Security Group
3. 🌐 **Get a domain name** - Purchase and configure DNS
4. 🔒 **Setup SSL certificate** - Free from Let's Encrypt
5. 🚀 **Deploy HTTPS** - Configure Docker and Apache

---

## STEP 1: Open Port 443 in AWS (5 minutes)

### Do this first - Required for HTTPS!

1. **Open AWS EC2 Console:**
   - Go to: https://console.aws.amazon.com/ec2/
   - Make sure you're in region: **US East (N. Virginia) us-east-1**

2. **Find your instance:**
   - Click "Instances (running)" in left sidebar
   - Find instance with IP: `54.227.103.23`
   - ✅ Check the box next to it

3. **Open Security Group:**
   - Look at the bottom panel → Click "Security" tab
   - Under "Security groups", click the security group name (looks like `sg-0abc123...`)

4. **Add HTTPS Rule:**
   - Click "Edit inbound rules" button
   - Click "Add rule"
   - Fill in:
     ```
     Type: HTTPS
     Protocol: TCP
     Port range: 443
     Source: Custom → 0.0.0.0/0
     Description: Allow HTTPS from anywhere
     ```
   - Click "Add rule" again (for IPv6)
   - Fill in:
     ```
     Type: HTTPS
     Protocol: TCP
     Port range: 443
     Source: Custom → ::/0
     Description: Allow HTTPS from anywhere IPv6
     ```

5. **Save changes:**
   - Click "Save rules" (bottom right)
   - ✅ Done! Port 443 is now open

### Verify Port 443 is Open:
Run this command in your terminal:
```bash
cd "/c/Users/Mikell Razon/Jaymie Twentieth/fit-brawl" && bash scripts/check-aws-ports.sh
```
You should see: ✅ Port 443 is OPEN

---

## STEP 2: Get a Domain Name (15 minutes)

### Option A: Namecheap (Recommended - Easiest)

1. **Go to:** https://www.namecheap.com
2. **Search for your domain** (e.g., `fitandbrawl.com`)
3. **Purchase** (~$10-15/year)
4. **After purchase:**
   - Go to Dashboard → Domain List
   - Click "Manage" next to your domain
   - Click "Advanced DNS"
   - Delete any existing A records
   - Click "Add New Record":
     ```
     Type: A Record
     Host: @
     Value: 54.227.103.23
     TTL: Automatic
     ```
   - Add another record for www:
     ```
     Type: A Record
     Host: www
     Value: 54.227.103.23
     TTL: Automatic
     ```
   - Click ✅ Save

### Option B: AWS Route53 (Better integration with AWS)

1. **Go to:** https://console.aws.amazon.com/route53/
2. **Register domain:**
   - Click "Registered domains" → "Register domain"
   - Search for domain → Purchase ($12-15/year)
3. **After registration:**
   - Go to "Hosted zones"
   - Click on your domain
   - Click "Create record"
   - Configure:
     ```
     Record name: (leave blank)
     Record type: A
     Value: 54.227.103.23
     TTL: 300
     ```
   - Click "Create record"
   - Repeat for www subdomain:
     ```
     Record name: www
     Record type: A
     Value: 54.227.103.23
     TTL: 300
     ```

### Option C: Already have a domain?

Just add these DNS records:
```
@ (or blank)  →  54.227.103.23  (A record)
www           →  54.227.103.23  (A record)
```

### Test your domain (wait 5-30 minutes):
```bash
# Check if DNS is working
nslookup yourdomain.com

# Test if website loads
curl http://yourdomain.com
```

**⏳ Once you see your website at `http://yourdomain.com`, move to Step 3!**

---

## STEP 3: Install SSL Certificate (10 minutes)

**⚠️ IMPORTANT: Your domain must be working first!**

### Part A: Prepare Docker Configuration

I'll create the configuration files for you. Once your domain is working, tell me your domain name and I'll:

1. Update `docker-compose.yml` to expose port 443
2. Create Apache SSL configuration
3. Update Dockerfile to enable SSL module
4. Create setup script for SSL certificate

### Part B: Run SSL Setup Script

Once I create the files, you'll run:

```bash
# The script I'll create will do:
# 1. Stop Docker container
# 2. Install Certbot
# 3. Get SSL certificate from Let's Encrypt
# 4. Configure Apache for HTTPS
# 5. Restart Docker with SSL enabled
# 6. Setup auto-renewal

# You'll just run:
bash scripts/setup-ssl.sh yourdomain.com
```

---

## STEP 4: Test Everything

After setup completes:

1. **Test HTTPS:**
   - Visit: `https://yourdomain.com`
   - You should see 🔒 padlock in browser

2. **Test HTTP redirect:**
   - Visit: `http://yourdomain.com`
   - Should automatically redirect to `https://yourdomain.com`

3. **Check SSL certificate:**
   - Click the 🔒 padlock in browser
   - Should show "Certificate (Valid)" from Let's Encrypt
   - Valid for 90 days (auto-renews)

---

## 🎯 Your Action Items RIGHT NOW:

### ✅ Step 1: Open Port 443
- [ ] Log into AWS Console
- [ ] Find your EC2 instance (54.227.103.23)
- [ ] Edit Security Group
- [ ] Add HTTPS rules (443) for 0.0.0.0/0 and ::/0
- [ ] Save rules
- [ ] Run `bash scripts/check-aws-ports.sh` to verify

### 🌐 Step 2: Get Domain Name
- [ ] Choose registrar (Namecheap or Route53)
- [ ] Purchase domain
- [ ] Add DNS A records pointing to 54.227.103.23
- [ ] Wait 5-30 minutes for DNS propagation
- [ ] Test: `http://yourdomain.com` should work
- [ ] **Tell me your domain name when ready!**

### 🔒 Step 3: SSL Setup (I'll help with this)
- [ ] Once domain is working, tell me the domain name
- [ ] I'll create the SSL setup script
- [ ] You run the script
- [ ] Test HTTPS access

---

## 💬 Let me know when you complete each step!

**What should you do first?**

1. **Right now:** Open port 443 in AWS Security Group (5 minutes)
2. **Next:** Tell me if you:
   - Already have a domain name? (tell me what it is)
   - Need to buy a domain? (I'll guide you)
   - Want to use a subdomain? (e.g., `gym.yourdomain.com`)

**Once you tell me, I'll create the SSL setup script customized for your domain!** 🚀
