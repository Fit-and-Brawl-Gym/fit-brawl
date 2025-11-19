# Fit-Brawl Migration: Local to Cloud (AWS)

## 1. Prerequisites

- **AWS account** with permissions for EC2, RDS, and Security Groups
- **Cloudflare account** (recommended for production tunnels)
- **SSH key pair** for EC2 access (downloaded `.pem` or generated locally)
- **Docker & Docker Compose** installed on EC2
- **MySQL/MariaDB client** installed locally and on EC2
- **Git** installed on EC2

---

## 2. Prepare Local Environment

1. **Verify .env file**
   - Ensure all required variables are present (DB, email, encryption).
   - Example:
     ```env
     DB_HOST=localhost
     DB_USER=root
     DB_PASS=
     DB_NAME=fit_and_brawl_gym
     DB_PORT=3306
     ...
     ```
2. **Test your app locally**
   - Run in XAMPP and confirm all features work.
3. **Export your local database**
   - Open a terminal and run:
     ```bash
     mysqldump -u root -p fit_and_brawl_gym > fit_and_brawl_gym.sql
     ```
   - Store the SQL file somewhere safe.

---

## 3. Set Up AWS RDS (MySQL)

1. **Create a new RDS MySQL instance**
   - In AWS Console: RDS → Create database → MySQL
   - Choose instance size, storage, and set master username/password
   - Enable public access (for initial setup), or restrict to EC2 security group
2. **Note the endpoint, master username, and password**
3. **Set the security group**
   - Allow inbound MySQL (port 3306) from your EC2 instance’s security group or private IP

---

## 4. Import Database to RDS

1. **Copy your SQL dump to EC2**
   - From your local machine:
     ```bash
     scp -i ~/.ssh/your-key "C:/path/to/fit_and_brawl_gym.sql" ec2-user@<EC2-IP>:/tmp/
     ```
2. **On EC2, import to RDS**
   - SSH into EC2:
     ```bash
     ssh -i ~/.ssh/your-key ec2-user@<EC2-IP>
     ```
   - Import:
     ```bash
     mysql -h <rds-endpoint> -u <master-username> -p'<password>' fit_and_brawl_gym < /tmp/fit_and_brawl_gym.sql
     ```
   - If the DB does not exist, create it first:
     ```bash
     mysql -h <rds-endpoint> -u <master-username> -p'<password>' -e "CREATE DATABASE fit_and_brawl_gym CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
     ```

---

## 5. Set Up EC2 and Deploy Code

1. **Launch an EC2 instance**
   - Ubuntu/Debian recommended
   - Assign the same security group as RDS (or allow SSH from your IP)
2. **SSH in**
   ```bash
   ssh -i ~/.ssh/your-key ec2-user@<EC2-IP>
   ```
3. **Install Docker & Docker Compose**
   ```bash
   sudo apt update && sudo apt install -y docker.io docker-compose git
   sudo usermod -aG docker ec2-user
   newgrp docker
   ```
4. **Clone your repo**
   ```bash
   git clone https://github.com/Fit-and-Brawl-Gym/fit-brawl.git
   cd fit-brawl
   ```
5. **Copy your .env and update**
   - Set `DB_HOST` to your RDS endpoint
   - Set `DB_USER`, `DB_PASS`, `DB_NAME` as per RDS
   - Example:
     ```env
     DB_HOST=fitbrawl-db.xxxxxxxx.us-east-1.rds.amazonaws.com
     DB_USER=Mikell_Admin
     DB_PASS=Mikedefender#12
     DB_NAME=fit_and_brawl_gym
     DB_PORT=3306
     ...
     ```
6. **Build and start containers**
   ```bash
   docker compose up -d --build
   ```
7. **Verify containers are running**
   ```bash
   docker compose ps
   curl -I http://localhost:80/
   ```

---

## 6. Set Up Cloudflare Tunnel

1. **Quick test tunnel (not for production):**
   ```bash
   nohup cloudflared tunnel --url http://localhost:80 > /tmp/cloudflared.log 2>&1 &
   sleep 8
   grep -oP 'https://[a-z0-9-]+\.trycloudflare\.com' /tmp/cloudflared.log | tail -1
   ```
2. **Production tunnel (recommended):**
   - [Follow Cloudflare’s guide for named tunnels](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/tunnel-guide/)
   - Install `cloudflared` and authenticate with your Cloudflare account
   - Create a named tunnel and configure a CNAME DNS record for your domain
3. **Share the generated HTTPS URL for public access**

---

## 7. Post-Migration Checklist

- Test the site via the Cloudflare tunnel URL
- Test the admin panel and all user flows
- Check logs for errors:
  ```bash
  docker compose logs web | tail -40
  ```
- Confirm email sending works (check SMTP config)
- Set up automated backups for RDS and EC2
- Secure your security groups (limit SSH/MySQL to trusted IPs)

---

## 8. Troubleshooting

- **Tunnel 404:**
  - Restart cloudflared, check Docker containers, and verify `curl -I http://localhost:80/` returns 200 OK
- **DB errors:**
  - Check `.env` DB credentials and RDS security group
- **Permission errors:**
  - Ensure correct file ownership on EC2 (`chown -R ec2-user:ec2-user uploads`)
- **Docker build errors:**
  - Check Dockerfile and `docker-compose.yml` for syntax and version issues

---

## 9. Maintenance & Updates

- Use `git pull` and `docker compose up -d --build` to deploy code updates
- Use `mysqldump` for DB backups
- Rotate Cloudflare tunnel URLs as needed
- Monitor logs and set up alerts for errors

---

## 10. Security Best Practices

- Never commit secrets or passwords to your repo
- Use AWS Secrets Manager or Parameter Store for sensitive values
- Regularly update your OS, Docker images, and dependencies
- Restrict security group access to trusted IPs only

---
