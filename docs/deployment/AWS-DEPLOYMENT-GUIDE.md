# FitXBraw## 0) What you'll have at the end
- Public site at http://54.227.103.23/php/index.php
- RDS MySQL with your schema loaded
- File uploads working (avatars/equipment/products/receipts) on EC2 disk
- Receipt rendering (Puppeteer) working via local fallback Deployment Guide (Free Trial Friendly)

This is a copy‑paste friendly, step‑by‑step runbook to deploy FitXBrawl on AWS using:
- EC2 (Docker) for the web app (PHP 8.1 + Apache + Node + Puppeteer)
- Amazon RDS (MySQL) for the database
- EBS (disk) for uploads initially

Phase 2 options (S3 uploads, multi‑container renderer, HTTPS via ALB) are at the end.

---
## 0) What you’ll have at the end
- Public site at http://YOUR-EC2-PUBLIC-IP/php/index.php
- RDS MySQL with your schema loaded
- File uploads working (avatars/equipment/products/receipts) on EC2 disk
- Receipt rendering (Puppeteer) working via local fallback

---
## 1) Prerequisites (Local + AWS)
On your laptop:
- Git, Docker, MySQL client (optional but helpful)

In AWS:
- Choose a free‑tier region (e.g., us‑east‑1)
- You have access to create RDS and EC2

Tip: Keep this guide open beside your AWS Console.

---
## 2) Create the RDS MySQL instance (GUI)
1. AWS Console → RDS → Databases → Create database
2. Method: Standard create
3. Engine: MySQL
4. Templates: Free tier
5. Settings:
   - DB instance identifier: fitbrawl-db
   - Master username: fitbrawl_admin (or your choice)
   - Master password: a strong password (store it)
6. Instance class: db.t3.micro
7. Storage: 20GB gp3 (disable autoscaling for predictability)
8. Connectivity:
   - VPC: default
   - Public access: No (recommended)
   - Create new security group: fitbrawl-db-sg
9. Additional configuration:
   - Initial database name: fit_and_brawl_gym
   - Other defaults ok → Create database

Wait for Status = Available and copy the Endpoint (e.g., fitbrawl-db.xxxxxx.us-east-1.rds.amazonaws.com).

---
## 3) Launch the EC2 Instance (GUI)
1. AWS Console → EC2 → Instances → Launch instance
2. Name: fitbrawl-web
3. AMI: Amazon Linux 2023 (64-bit x86)
4. Instance type: t2.micro or t3.micro
5. Key pair: Create/download (used for SSH)
6. Network settings:
   - VPC: same as RDS
   - Create security group: fitbrawl-web-sg
     - Inbound: HTTP (80) from 0.0.0.0/0 (for quick test; later restrict or put behind ALB)
     - Inbound: SSH (22) from your IP only
7. Storage: 30 GB gp3
8. Launch → wait for Running → copy Public IPv4 address

Now allow EC2 to talk to RDS:
9. Go to RDS → fitbrawl-db → Security → Inbound rules of fitbrawl-db-sg → Edit inbound rules
   - Add rule: Type = MySQL/Aurora (3306), Source = Security group fitbrawl-web-sg
   - Save rules

---
## 4) SSH into EC2 and install Docker
From your laptop terminal (replace key path and IP):
```bash
ssh -i "C:\Users\Mikell Razon\Downloads\Mikell.pem" ec2-user@54.227.103.23

# Update & install Docker + Git + MySQL client (mariadb105 is MySQL-compatible)
sudo yum update -y
sudo yum install -y docker git mariadb105
sudo systemctl enable --now docker
sudo usermod -aG docker ec2-user
exit
```
Reconnect to apply docker group:
```bash
ssh -i "C:\Users\Mikell Razon\Downloads\Mikell.pem" ec2-user@54.227.103.23
docker --version
```

Optional: Docker Compose plugin
```bash
DOCKER_CONFIG=${DOCKER_CONFIG:-$HOME/.docker}
mkdir -p $DOCKER_CONFIG/cli-plugins
curl -SL https://github.com/docker/compose/releases/download/v2.29.2/docker-compose-linux-x86_64 -o $DOCKER_CONFIG/cli-plugins/docker-compose
chmod +x $DOCKER_CONFIG/cli-plugins/docker-compose
docker compose version
```

---
## 5) Fetch your application on EC2
Pick one:
```bash
# Option A: Clone
git clone https://github.com/<your-account>/fit-brawl.git
cd fit-brawl

# Option B: Upload a zip then unzip (example)
# scp -i /path/to/key.pem fit-brawl.zip ec2-user@YOUR_EC2_PUBLIC_IP:~
# unzip fit-brawl.zip && cd fit-brawl
```

---
## 6) Configure environment (.env)
Create and edit:
```bash
cp .env.example .env
nano .env
```
Set the values:
```
APP_ENV=production
APP_URL=http://54.160.213.124
BASE_PATH=/

DB_HOST=<RDS_ENDPOINT>
DB_PORT=3306
DB_NAME=fit_and_brawl_gym
DB_USER=Mikell_Admin
DB_PASS=Mikedefender#12

EMAIL_HOST=smtp.gmail.com
EMAIL_PORT=587
EMAIL_USER=fitxbrawl.gym@gmail.com
EMAIL_PASS=oxck mxfc cpoj wpra

# Leave blank to use local Node fallback
RENDERER_URL=
```
Security: Don’t commit .env back to GitHub.

---
## 7) Build and run the Docker container
From the project root:
```bash
docker build -t fitbrawl-web .

# Bind-mount uploads so files persist across container rebuilds
docker run -d --name fitbrawl_web \
  --env-file .env \
  -p 80:80 \
  -v $(pwd)/uploads:/var/www/html/uploads \
  fitbrawl-web

docker logs -f fitbrawl_web
```
Open http://54.227.103.23/php/index.php in your browser.

If you see Apache/PHP page: continue. If not, check logs.

---
## 8) Load the database schema into RDS
Run from EC2 (in the repo root):
```bash
mysql -h <RDS_ENDPOINT> -u <DB_USER> -p fit_and_brawl_gym < docs/database/schema.sql
# Optional seed
mysql -h <RDS_ENDPOINT> -u <DB_USER> -p fit_and_brawl_gym < docs/database/seed.sql || true
```

Sanity test a query:
```bash
mysql -h <RDS_ENDPOINT> -u <DB_USER> -p -e "SHOW TABLES;" fit_and_brawl_gym
```

---
## 9) Functional smoke tests
1) Registration & email
   - Register a new user → confirm you receive SMTP email (check spam)
2) Membership flow
   - Submit a membership → verify a row exists in user_memberships
3) Booking
   - Create a reservation → verify a row exists in user_reservations
4) Avatar upload
   - Upload a profile image → file appears under uploads/avatars and displays in UI
5) Receipt rendering
   - Hit: `http://54.227.103.23/php/receipt_render.php?type=member&id=<ID>&format=pdf`
   - If it fails, see troubleshooting below

---
## 10) Troubleshooting quick wins
- Container logs: `docker logs -f fitbrawl_web`
- Puppeteer/Chromium issues:
  - Ensure the image built fully (Chromium download happens in server-renderer step)
  - Low memory? Add swap:
    ```bash
    sudo fallocate -l 1G /swapfile
    sudo chmod 600 /swapfile
    sudo mkswap /swapfile
    sudo swapon /swapfile
    free -h
    ```
- DB connection refused:
  - Verify RDS SG inbound rule allows Source = fitbrawl-web-sg on port 3306
  - Use correct endpoint and credentials in .env
- Avatars not loading:
  - Ensure BASE_PATH=/ and uploads directory is writable by Apache (www-data in container)
- Emails not sending:
  - Use a Gmail app password (not your normal password)
  - Check PHPMailer exceptions in logs

---
## 11) Basic hardening (Day 1)
- Restrict HTTP inbound to your IP or put behind an ALB + WAF
- Keep `.env` owner‑only readable: `chmod 600 .env`
- Create a weekly cron to purge stale temp files from /tmp and rotate logs

---
## 12) Phase 2 (optional improvements)
| Goal | Action |
|------|--------|
| HTTPS | Use ACM + ALB (recommended) or install nginx + certbot on EC2 |
| Durable uploads | Implement S3 (STORAGE_DRIVER=s3) and update code to write/read S3 |
| Split renderer | Run `server-renderer/server.js` as a second service; set RENDERER_URL=http://renderer:3000 |
| Autoscaling | Move to ECS/Fargate or Elastic Beanstalk multi‑container |
| Logs | Install CloudWatch Agent to ship Apache/PHP logs |
| Backups | RDS automated backups + S3 lifecycle rules |
| Monitoring | CloudWatch alarms (CPU, status checks); custom metric for memory |

---
## 13) Cost controls
- Stop EC2 when idle
- Snapshot then stop/delete RDS when not needed
- Clean up unused EBS volumes and snapshots

---
## 14) Appendix
### Environment variables (used by the app)
```
APP_ENV=production
APP_URL=http://54.227.103.23
BASE_PATH=/

DB_HOST=<RDS_ENDPOINT>
DB_PORT=3306
DB_NAME=fit_and_brawl_gym
DB_USER=<user>
DB_PASS=<pass>

EMAIL_HOST=smtp.gmail.com
EMAIL_PORT=587
EMAIL_USER=your-email@gmail.com
EMAIL_PASS=your-app-password

RENDERER_URL=   # optional; leave blank to use local Node
```

That’s it — you have a clear, linear checklist you can follow end‑to‑end.
