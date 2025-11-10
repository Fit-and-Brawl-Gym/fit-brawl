# Fix Email Verification Access Issue

## Problem
Email verification links timeout with `ERR_CONNECTION_TIMED_OUT` when accessed from external networks (different internet connections).

**Error:** `54.227.103.23 took too long to respond`

## Root Cause
AWS Security Group is blocking incoming HTTP traffic on port 80 from external IP addresses.

---

## Solution: Update AWS Security Group

### Step 1: Access AWS Security Groups

1. **Log in to AWS Console:**
   - Go to: https://console.aws.amazon.com/ec2/

2. **Navigate to Security Groups:**
   - In the left sidebar, click **"Security Groups"** under "Network & Security"
   - OR click on your EC2 instance → **"Security"** tab → Click the Security Group link

3. **Find Your Security Group:**
   - Look for the security group attached to instance `54.227.103.23`
   - It might be named something like `launch-wizard-X` or custom name

### Step 2: Add HTTP Inbound Rule

1. **Select the Security Group** (checkbox on the left)

2. **Click "Edit inbound rules"** (bottom right)

3. **Click "Add rule"**

4. **Configure HTTP Rule:**
   ```
   Type: HTTP
   Protocol: TCP  
   Port range: 80
   Source: 0.0.0.0/0
   Description: Allow HTTP from anywhere (IPv4)
   ```

5. **Add IPv6 Support** (Click "Add rule" again):
   ```
   Type: HTTP
   Protocol: TCP
   Port range: 80
   Source: ::/0
   Description: Allow HTTP from anywhere (IPv6)
   ```

6. **Click "Save rules"**

### Step 3: Verify HTTPS Access (Port 443)

If you plan to use HTTPS in the future, also add:

```
Type: HTTPS
Protocol: TCP
Port range: 443
Source: 0.0.0.0/0
Description: Allow HTTPS from anywhere (IPv4)
```

```
Type: HTTPS
Protocol: TCP
Port range: 443
Source: ::/0
Description: Allow HTTPS from anywhere (IPv6)
```

---

## Verification Steps

### 1. Test from External Network

After updating the Security Group, test from a different device/network:

**Test URL:**
```
http://54.227.103.23
```

You should see your website's homepage.

### 2. Test Verification Link Format

The verification emails contain links like:
```
http://54.227.103.23/php/verify-email.php?token=XXXXXXXX
```

**Test a sample link:**
```
http://54.227.103.23/php/verify-email.php?token=test123
```

You should NOT get a timeout error. You might get "Invalid token" which is expected, but the page should load.

### 3. Test from Mobile Data

- Disconnect from WiFi
- Use mobile data
- Click the verification link from your email
- Should work now! ✅

---

## Current Security Group Rules Should Look Like:

| Type | Protocol | Port Range | Source | Description |
|------|----------|------------|--------|-------------|
| SSH | TCP | 22 | 0.0.0.0/0 | SSH access |
| HTTP | TCP | 80 | 0.0.0.0/0 | Allow HTTP (IPv4) |
| HTTP | TCP | 80 | ::/0 | Allow HTTP (IPv6) |
| HTTPS | TCP | 443 | 0.0.0.0/0 | Allow HTTPS (IPv4) |
| HTTPS | TCP | 443 | ::/0 | Allow HTTPS (IPv6) |
| All traffic | All | All | sg-xxxxx | Allow internal VPC traffic |

---

## Alternative: Use Domain Name (Recommended)

Instead of using the IP address `54.227.103.23`, set up a proper domain name:

### Benefits:
- ✅ More professional (`fitbrawl.com` vs `54.227.103.23`)
- ✅ Easier to remember
- ✅ Can enable HTTPS/SSL
- ✅ Better email deliverability
- ✅ SEO benefits

### Quick Setup:

1. **Purchase a domain** (e.g., from Namecheap, GoDaddy, or AWS Route53)
   - Cost: ~$10-15/year

2. **Add DNS A Record:**
   ```
   Type: A
   Name: @
   Value: 54.227.103.23
   TTL: 300
   ```

3. **Update production .env:**
   ```bash
   APP_URL=http://yourdomain.com
   ```

4. **Wait 5-30 minutes for DNS propagation**

5. **Test:** Visit `http://yourdomain.com`

---

## Troubleshooting

### Issue: Still timing out after Security Group update

**Check 1: Security Group applied to correct instance**
```bash
ssh -i "/c/Users/Mikell Razon/Downloads/Mikell.pem" ec2-user@54.227.103.23 \
  "curl -s http://169.254.169.254/latest/meta-data/security-groups"
```

**Check 2: Docker is running**
```bash
ssh -i "/c/Users/Mikell Razon/Downloads/Mikell.pem" ec2-user@54.227.103.23 \
  "docker ps | grep fitbrawl"
```

**Check 3: Port 80 is open**
```bash
ssh -i "/c/Users/Mikell Razon/Downloads/Mikell.pem" ec2-user@54.227.103.23 \
  "sudo netstat -tulpn | grep :80"
```

**Check 4: Test from server itself**
```bash
ssh -i "/c/Users/Mikell Razon/Downloads/Mikell.pem" ec2-user@54.227.103.23 \
  "curl -I http://localhost/php/verify-email.php"
```

### Issue: Verification link works but shows "Invalid token"

This is GOOD! It means:
- ✅ Port 80 is accessible
- ✅ Web server is working
- ✅ PHP is processing requests
- ❌ The token in the URL is expired/invalid

**Solution:** Register a new account to get a fresh verification email.

---

## Security Notes

### ⚠️ Opening Port 80 to 0.0.0.0/0

**Is this safe?** YES, this is standard practice for web servers.

- Port 80 is meant to be publicly accessible
- Your web application handles authentication/authorization
- Docker container provides isolation
- AWS provides DDoS protection
- Rate limiting should be implemented at application level

### 🔒 Future: Enable HTTPS

Once you have a domain name, enable HTTPS:

1. Install Let's Encrypt SSL certificate (FREE)
2. Force HTTPS redirects
3. Update APP_URL to `https://yourdomain.com`

See: `DOMAIN-AND-HTTPS-SETUP.md` for full guide

---

## Quick Test Command

Run this from your local machine (not SSH):

```bash
curl -I http://54.227.103.23/php/verify-email.php
```

**Expected output:**
```
HTTP/1.1 302 Found
Date: ...
Server: Apache/2.4.65 (Debian)
```

**Bad output (means port blocked):**
```
curl: (28) Failed to connect to 54.227.103.23 port 80: Connection timed out
```

---

## Summary Checklist

- [ ] Updated AWS Security Group to allow HTTP (port 80) from 0.0.0.0/0
- [ ] Updated AWS Security Group to allow HTTP (port 80) from ::/0
- [ ] Tested from external network (mobile data or different WiFi)
- [ ] Verification emails can be opened from any internet connection
- [ ] Consider purchasing domain name for better UX
- [ ] Plan to enable HTTPS/SSL certificates

---

**After completing these steps, verification links will work from ANY internet connection! ✅**
