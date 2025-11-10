# Domain Name & HTTPS Setup Guide

## Current Status
- ✅ Docker container is already listening on port 80 for all IPs (`0.0.0.0:80`)
- ⚠️ May need AWS Security Group updates
- ❌ Need domain name setup
- ❌ Need HTTPS/SSL certificate

---

## Part 1: Make Website Accessible to Everyone (Port 80)

### Check AWS Security Group Rules

1. **Go to AWS EC2 Console:**
   - https://console.aws.amazon.com/ec2/

2. **Find your instance:**
   - IP: `54.227.103.23`
   - Click on your instance

3. **Check Security Group:**
   - Click on the "Security" tab
   - Click on the Security Group name (e.g., `sg-xxxxx`)

4. **Add/Verify Inbound Rules:**
   ```
   Type: HTTP
   Protocol: TCP
   Port Range: 80
   Source: 0.0.0.0/0 (Anywhere IPv4)
   Description: Allow HTTP from anywhere

   Type: HTTP
   Protocol: TCP
   Port Range: 80
   Source: ::/0 (Anywhere IPv6)
   Description: Allow HTTP from anywhere IPv6
   ```

5. **Click "Save rules"**

**Test:** After this, anyone should be able to access `http://54.227.103.23`

---

## Part 2: Setup Custom Domain Name

### Option A: Using a Domain You Already Own

1. **Purchase a domain** (if you don't have one):
   - Recommended registrars: Namecheap, GoDaddy, Google Domains, AWS Route53

2. **Add DNS A Record:**
   - Go to your domain registrar's DNS management
   - Add an A record:
     ```
     Type: A
     Host: @ (or your subdomain, e.g., "www")
     Value: 54.227.103.23
     TTL: 300 (or Auto)
     ```
   - If you want both `example.com` and `www.example.com`, add both:
     ```
     @ → 54.227.103.23
     www → 54.227.103.23
     ```

3. **Wait for DNS propagation** (5-30 minutes)

4. **Test:** Visit `http://yourdomain.com`

### Option B: Using AWS Route53 (Recommended for AWS hosting)

1. **Register domain in Route53** or transfer existing domain
2. **Create Hosted Zone** (automatic if registered through Route53)
3. **Create A Record:**
   ```
   Record name: (blank for root domain)
   Record type: A
   Value: 54.227.103.23
   TTL: 300
   Routing policy: Simple routing
   ```

---

## Part 3: Setup HTTPS (SSL Certificate)

### Prerequisites:
- Domain name must be pointing to your server
- Port 80 must be accessible (for Let's Encrypt verification)

### Step 1: Add Port 443 to Security Group

1. **Go to AWS EC2 Security Group** (same as Part 1)
2. **Add Inbound Rule:**
   ```
   Type: HTTPS
   Protocol: TCP
   Port Range: 443
   Source: 0.0.0.0/0
   Description: Allow HTTPS from anywhere

   Type: HTTPS
   Protocol: TCP
   Port Range: 443
   Source: ::/0
   Description: Allow HTTPS from anywhere IPv6
   ```

### Step 2: Install Certbot (Let's Encrypt) on EC2

SSH into your server and run:

```bash
# Install Certbot
sudo yum install -y certbot python3-certbot-apache

# Stop Docker temporarily (to free port 80 for verification)
sudo docker stop fitbrawl_web

# Obtain SSL certificate
sudo certbot certonly --standalone -d yourdomain.com -d www.yourdomain.com

# Start Docker again
sudo docker start fitbrawl_web
```

**Certificate location:** `/etc/letsencrypt/live/yourdomain.com/`

### Step 3: Configure Apache with SSL in Docker

Create `docker-compose-https.yml`:

```yaml
version: "3.9"

services:
  web:
    build: .
    container_name: fitbrawl_web
    ports:
      - "80:80"
      - "443:443"
    env_file:
      - .env
    environment:
      - APP_ENV=${APP_ENV:-development}
      - BASE_PATH=${BASE_PATH:-/}
      - DB_HOST=${DB_HOST:-db}
      - DB_PORT=${DB_PORT:-3306}
      - DB_NAME=${DB_NAME:-fit_and_brawl_gym}
      - DB_USER=${DB_USER:-root}
      - DB_PASS=${DB_PASS:-password}
    volumes:
      - ./uploads:/var/www/html/uploads
      - /etc/letsencrypt:/etc/letsencrypt:ro
    restart: unless-stopped
```

### Step 4: Configure Apache SSL

Create `apache-ssl.conf` in your project:

```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    DocumentRoot /var/www/html/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem

    <Directory /var/www/html/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    Redirect permanent / https://yourdomain.com/
</VirtualHost>
```

### Step 5: Update Dockerfile

Add SSL module to your Dockerfile:

```dockerfile
# Enable Apache SSL module
RUN a2enmod ssl
RUN a2enmod rewrite
RUN a2enmod headers

# Copy SSL configuration
COPY apache-ssl.conf /etc/apache2/sites-available/default-ssl.conf
RUN a2ensite default-ssl
```

### Step 6: Auto-Renewal Setup

```bash
# Test renewal
sudo certbot renew --dry-run

# Add cron job for auto-renewal (runs twice daily)
sudo crontab -e
# Add this line:
0 0,12 * * * certbot renew --quiet --post-hook "docker restart fitbrawl_web"
```

---

## Quick Start Commands (After Domain Setup)

Once you have a domain name pointing to your server:

```bash
# 1. SSH into server
ssh -i "/path/to/Mikell.pem" ec2-user@54.227.103.23

# 2. Install Certbot
sudo yum install -y certbot

# 3. Stop Docker
sudo docker stop fitbrawl_web

# 4. Get SSL certificate (replace with your domain)
sudo certbot certonly --standalone \
  -d yourdomain.com \
  -d www.yourdomain.com \
  --email your-email@example.com \
  --agree-tos \
  --no-eff-email

# 5. Copy certificates to project (on local machine)
ssh -i "/path/to/Mikell.pem" ec2-user@54.227.103.23 \
  "sudo cp -r /etc/letsencrypt /home/ec2-user/"

# 6. Update docker-compose.yml to mount certificates

# 7. Rebuild and restart container
cd /home/ec2-user/fit-brawl
sudo docker-compose up -d --build
```

---

## Summary Checklist

### For Public Access (HTTP):
- [ ] AWS Security Group allows port 80 from 0.0.0.0/0
- [ ] Docker container running and mapped to port 80
- [ ] Test: `http://54.227.103.23` works from any device

### For Domain Name:
- [ ] Domain purchased/registered
- [ ] DNS A record points to 54.227.103.23
- [ ] Wait 5-30 minutes for DNS propagation
- [ ] Test: `http://yourdomain.com` works

### For HTTPS:
- [ ] AWS Security Group allows port 443
- [ ] Domain name is working (required!)
- [ ] Certbot installed on EC2
- [ ] SSL certificate obtained from Let's Encrypt
- [ ] Docker container configured for SSL
- [ ] Apache SSL module enabled
- [ ] HTTP→HTTPS redirect configured
- [ ] Auto-renewal cron job set up
- [ ] Test: `https://yourdomain.com` shows padlock 🔒

---

## Need Help?

1. **First Priority:** Get your domain name and point it to `54.227.103.23`
2. **Then:** Run the SSL certificate commands
3. **Finally:** Update Docker configuration

Let me know your domain name once you have it, and I can help you with the specific configuration!
