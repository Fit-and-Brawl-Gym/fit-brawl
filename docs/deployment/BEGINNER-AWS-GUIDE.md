# FitXBrawl AWS Deployment - Complete Beginner Guide

**Welcome!** This guide assumes you've never used AWS before. I'll explain everything step-by-step with no assumptions.

---

## 🎯 What We're Building

You have a website (FitXBrawl) on your computer. We want to put it on the internet using Amazon's computers (AWS) so anyone can visit it.

**Simple breakdown**:
- **EC2** = Amazon's computer that will run your website
- **RDS** = Amazon's database storage for your user data
- **Security Group** = Like a firewall - controls who can access what

---

## 📋 Before We Start

**What you need**:
1. ✅ AWS account (you said you have free trial - perfect!)
2. ✅ This project folder on your computer
3. ✅ A way to run commands (Git Bash, PowerShell, or Command Prompt on Windows)

**What we'll do**:
1. First: Fix the SSH connection issue (so you can access the EC2 computer)
2. Then: Set up everything else step by step

---

## 🔧 PART 1: Fix SSH Connection (SOLVE YOUR CURRENT PROBLEM)

### Step 1: Find Your Public IP Address

Your computer has an "address" on the internet. We need to find it.

**Option A: Use a website**
1. Open your web browser
2. Go to: https://www.whatismyip.com/
3. You'll see a number like `203.45.67.89` - **write this down!**

**Option B: Use command line**
1. Open Git Bash or PowerShell
2. Type: `curl https://checkip.amazonaws.com`
3. Press Enter
4. You'll see your IP - **write this down!**

---

### Step 2: Log Into AWS Console

1. Go to: https://aws.amazon.com/console/
2. Click the orange **"Sign In to the Console"** button
3. Enter your email and password
4. You should see the AWS homepage with lots of services

---

### Step 3: Go to EC2 (Where Your Computer Lives)

1. At the very top of the AWS page, you'll see a search bar
2. Type: **EC2**
3. Click on **EC2** (it says "Virtual Servers in the Cloud")
4. You're now in the EC2 Dashboard

---

### Step 4: Find Your Instance (Your Virtual Computer)

1. On the left sidebar, click **"Instances"** (it's usually already selected)
2. You should see a list with your instance - it might be called `fitbrawl-web` or something similar
3. **Check the status**:
   - ✅ **Running** = Good! Continue to next step
   - ❌ **Stopped** = Click the checkbox next to it, then click **"Instance state" → "Start instance"**. Wait 2 minutes, then refresh.

---

### Step 5: Click on Your Instance

1. Click the checkbox next to your instance name
2. Below, you'll see tabs: **Details**, **Security**, **Networking**, etc.
3. Click the **"Security"** tab

---

### Step 6: Find the Security Group

In the Security tab, you'll see:
- **Security groups**: This will show one or more blue links like `sg-xxxxxxxx` or names like `default`, `fitbrawl-web-sg`
- **Click on the FIRST security group link** (the blue clickable text)

A new tab or section will open showing the security group details.

---

### Step 7: Edit the Security Rules (Allow SSH)

Now we'll tell AWS: "Let my computer connect to this EC2 instance"

1. You'll see two tabs: **Inbound rules** and **Outbound rules**
2. Click **"Inbound rules"** (probably already selected)
3. Click the **"Edit inbound rules"** button (bottom right)
4. Click **"Add rule"** button

Now fill in the new rule:
- **Type**: Click the dropdown and select **SSH**
- **Protocol**: Will automatically say **TCP** (don't change it)
- **Port range**: Will automatically say **22** (don't change it)
- **Source**: This is the important part!
  - Click the dropdown next to "Custom"
  - Select **"My IP"** - it will automatically fill in your IP address!
  - OR manually type your IP from Step 1 in this format: `203.45.67.89/32` (your IP + /32)
- **Description**: Type something like "SSH from my laptop"

5. Click **"Save rules"** (orange button at bottom right)

**✅ Done!** You've now opened the door for SSH.

---

### Step 8: Try SSH Again

Open Git Bash, PowerShell, or Command Prompt and try:

```bash
ssh -i "C:\Users\Mikell Razon\Downloads\Mikell.pem" ec2-user@54.227.103.23
```

**What should happen**:
- First time: You'll see a message asking "Are you sure you want to continue connecting?" - Type **yes** and press Enter
- You should see a welcome message and be inside the EC2 computer!
- Your prompt will change to something like `[ec2-user@ip-xxx-xxx-xxx-xxx ~]$`

**🎉 Success!** If you see the EC2 prompt, SSH is working! Skip to Part 2.

---

### Step 9: If SSH Still Doesn't Work

**Try this quick test**:
1. Go back to Security Group → Edit inbound rules
2. Find the SSH rule you just added
3. Change **Source** from "My IP" to **"Anywhere IPv4"** (select from dropdown)
   - This temporarily opens SSH to everyone (we'll fix it later)
4. Save rules
5. Try SSH command again

**If it works now**:
- Your IP address was wrong or changed
- Go find your IP again (Step 1) and update the rule with the correct IP
- Change source back from "Anywhere" to "My IP" for security

**If it still doesn't work**:
- Your instance might not be properly started
- Your key file might have wrong permissions
- Your network might be blocking port 22

---

## 🚀 PART 2: Set Up the Database (After SSH Works)

### Step 1: Create RDS Database

1. In AWS Console search bar, type **RDS**
2. Click **RDS** (says "Managed Relational Database Service")
3. On the left sidebar, click **"Databases"**
4. Click the orange **"Create database"** button

---

### Step 2: Fill Out the Database Form

**Choose a database creation method**:
- Select **"Standard create"** (not Easy create)

**Engine options**:
- Select **MySQL** (click the MySQL logo)
- Version: Leave as default (should be MySQL 8.x)

**Templates**:
- Select **"Free tier"** (very important!)

**Settings**:
- **DB instance identifier**: Type `fitbrawl-db` (this is just a name)
- **Master username**: Type `admin` (or whatever you want - write it down!)
- **Master password**: Type a strong password (write it down somewhere safe!)
- **Confirm password**: Type the same password again

**DB instance class**:
- Should automatically be **db.t3.micro** or **db.t2.micro** (free tier)

**Storage**:
- **Allocated storage**: 20 GB
- Uncheck **"Enable storage autoscaling"** (to avoid surprise charges)

**Connectivity**:
- **Virtual private cloud (VPC)**: Select **"Default VPC"**
- **Public access**: Select **"No"**
- **VPC security group**: Select **"Create new"**
- **New VPC security group name**: Type `fitbrawl-db-sg`

**Additional configuration** (click to expand):
- **Initial database name**: Type `fit_and_brawl_gym` (this is important!)
- Everything else: Leave as default

---

### Step 3: Create the Database

1. Scroll all the way down
2. Click **"Create database"** (orange button)
3. You'll see a success message
4. Wait 5-10 minutes (AWS is building your database)
5. Refresh the page until Status shows **"Available"**

---

### Step 4: Get the Database Address

1. Click on your database name: `fitbrawl-db`
2. Look for **"Endpoint & port"** section
3. You'll see something like: `fitbrawl-db.c9xxx.us-east-1.rds.amazonaws.com`
4. **Copy this entire address** - you'll need it later!

---

### Step 5: Allow EC2 to Talk to Database

Now we need to tell the database: "Let the EC2 computer connect to you"

1. Still on the database details page, scroll down to **"Security group rules"**
2. Click on the security group link (probably says `fitbrawl-db-sg`)
3. Click **"Edit inbound rules"**
4. Click **"Add rule"**

Fill in:
- **Type**: Select **"MYSQL/Aurora"** (this automatically sets port 3306)
- **Source**:
  - Click the search box
  - You'll see a dropdown - look for your EC2 security group
  - It might be called `default` or `sg-00fb08394b8c6ed46` or similar
  - **If you can't find it**: Type `sg-` and AWS will show all security groups
  - Select the security group that your EC2 instance is using
- **Description**: Type "From EC2 web server"

5. Click **"Save rules"**

**✅ Done!** Database is ready and EC2 can access it.

---

## 📦 PART 3: Install Docker on EC2

Now we'll install the software needed to run your website.

### Step 1: Connect via SSH

```bash
ssh -i "C:\Users\Mikell Razon\Downloads\Mikell.pem" ec2-user@54.227.103.23
```

You should see the EC2 prompt: `[ec2-user@ip-xxx ~]$`

---

### Step 2: Update the System

Copy and paste this command (then press Enter):

```bash
sudo yum update -y
```

Wait for it to finish (might take 1-2 minutes). You'll see lots of text scrolling.

---

### Step 3: Install Docker

Copy and paste this:

```bash
sudo yum install -y docker git mariadb105
```

Wait for it to finish.

---

### Step 4: Start Docker

Copy and paste:

```bash
sudo systemctl enable docker
sudo systemctl start docker
```

---

### Step 5: Add Your User to Docker Group

This lets you use Docker without typing `sudo` every time:

```bash
sudo usermod -aG docker ec2-user
```

---

### Step 6: Logout and Login Again

```bash
exit
```

Then reconnect:

```bash
ssh -i "C:\Users\Mikell Razon\Downloads\Mikell.pem" ec2-user@54.227.103.23
```

---

### Step 7: Test Docker

Type:

```bash
docker --version
```

You should see something like: `Docker version 20.10.x`

**✅ Docker is installed!**

---

## 📁 PART 4: Get Your Website Files on EC2

### Option A: Upload from Your Computer (Easiest)

**Step 1: Package your project**
On your laptop, in the project folder:

1. Delete `node_modules` folder (if it exists in server-renderer)
2. Create a zip file of the entire `fit-brawl` folder
3. Name it `fit-brawl.zip`

**Step 2: Upload to EC2**
Open a NEW terminal/command prompt on your laptop (don't close the SSH one):

```bash
scp -i "C:\Users\Mikell Razon\Downloads\Mikell.pem" C:\path\to\fit-brawl.zip ec2-user@54.227.103.23:~
```

(Replace `C:\path\to\fit-brawl.zip` with the actual path to your zip file)

**Step 3: Unzip on EC2**
Back in your SSH session:

```bash
unzip fit-brawl.zip
cd fit-brawl
```

---

### Option B: Clone from GitHub (If your code is on GitHub)

In your SSH session:

```bash
git clone https://github.com/Mikell-Razon/fit-brawl.git
cd fit-brawl
```

---

## ⚙️ PART 5: Configure Your Website

### Step 1: Create Environment File

```bash
cp .env.example .env
```

---

### Step 2: Edit the Environment File

```bash
nano .env
```

This opens a text editor. Use arrow keys to move around.

---

### Step 3: Fill In Your Settings

Change these values:

```
APP_ENV=production
APP_URL=http://54.227.103.23
BASE_PATH=/

DB_HOST=fitbrawl-db.c9xxx.us-east-1.rds.amazonaws.com
DB_PORT=3306
DB_NAME=fit_and_brawl_gym
DB_USER=admin
DB_PASS=your-database-password-here

EMAIL_HOST=smtp.gmail.com
EMAIL_PORT=587
EMAIL_USER=your-email@gmail.com
EMAIL_PASS=your-gmail-app-password

RENDERER_URL=
```

**What to change**:
- `DB_HOST`: Paste the RDS endpoint you copied earlier
- `DB_USER`: The database username you created (probably `admin`)
- `DB_PASS`: Your database password
- `EMAIL_USER`: Your Gmail address
- `EMAIL_PASS`: Your Gmail app password (NOT your regular Gmail password!)

---

### Step 4: Save the File

1. Press `Ctrl + X`
2. Press `Y` (for yes, save)
3. Press `Enter`

---

## 🐳 PART 6: Build and Run Your Website

### Step 1: Build the Docker Image

This might take 5-10 minutes:

```bash
docker build -t fitbrawl-web .
```

You'll see lots of output. Wait until you see "Successfully built" and "Successfully tagged".

---

### Step 2: Run the Container

```bash
docker run -d --name fitbrawl_web \
  --env-file .env \
  -p 80:80 \
  -v $(pwd)/uploads:/var/www/html/uploads \
  fitbrawl-web
```

---

### Step 3: Check if It's Running

```bash
docker ps
```

You should see a line with `fitbrawl_web` and status "Up".

---

### Step 4: Check the Logs

```bash
docker logs fitbrawl_web
```

Look for any errors. You should see Apache starting up.

---

## 💾 PART 7: Set Up the Database Tables

### Step 1: Load the Schema

From the `fit-brawl` directory:

```bash
mysql -h fitbrawl-db.c9xxx.us-east-1.rds.amazonaws.com -u admin -p fit_and_brawl_gym < docs/database/schema.sql
```

(Replace the host with your RDS endpoint)

When prompted, enter your database password.

---

### Step 2: Verify Tables Were Created

```bash
mysql -h fitbrawl-db.c9xxx.us-east-1.rds.amazonaws.com -u admin -p -e "SHOW TABLES;" fit_and_brawl_gym
```

You should see a list of tables like `users`, `user_memberships`, etc.

---

## 🎉 PART 8: Test Your Website!

### Step 1: Open Your Browser

Go to: `http://54.227.103.23/php/index.php`

You should see your FitXBrawl website!

---

### Step 2: Test Registration

1. Click "Sign Up" or "Register"
2. Fill out the form
3. Submit
4. Check your email (might be in spam folder)

---

## 🆘 Common Problems and Solutions

### Problem: Can't SSH
- ✅ Check security group has SSH rule with your IP
- ✅ Check instance is "Running"
- ✅ Try: `ssh -v -i "path-to-key.pem" ec2-user@54.227.103.23` for detailed error

### Problem: Website shows error
- Check logs: `docker logs fitbrawl_web`
- Look for database connection errors
- Verify .env has correct RDS endpoint

### Problem: Can't connect to database from EC2
- ✅ Check RDS security group allows inbound from EC2 security group
- ✅ Verify RDS status is "Available"
- ✅ Test: `mysql -h YOUR-RDS-ENDPOINT -u admin -p` from EC2

### Problem: Docker build fails
- Check if you have enough disk space: `df -h`
- Try: `docker system prune -a` to free up space
- Then rebuild

---

## 📚 What Each Command Does (For Learning)

- `ssh`: Securely connect to a remote computer
- `sudo`: Run a command as administrator
- `yum install`: Install software (Amazon Linux package manager)
- `docker build`: Create a container image from your code
- `docker run`: Start a container from an image
- `docker ps`: List running containers
- `docker logs`: Show output from a container
- `mysql`: Connect to MySQL database
- `nano`: Simple text editor in terminal
- `cp`: Copy file
- `cd`: Change directory
- `pwd`: Show current directory

---

## 💰 Important: Cost Control

**To avoid charges**:

1. **Stop EC2 when not using**:
   - EC2 Console → Instances → Select instance → Instance state → Stop instance
   - ⚠️ This changes your public IP! You'll need to update security groups and .env

2. **Stop RDS when done testing**:
   - RDS Console → Databases → Select database → Actions → Stop temporarily
   - Can only stop for 7 days, then AWS auto-starts it

3. **Best practice**: Delete everything when done learning
   - Delete EC2 instance
   - Delete RDS database
   - Delete security groups
   - Delete EBS volumes (storage)

---

## ✅ Next Steps After Everything Works

Once your site is running:

1. **Get a domain name** (like fitxbrawl.com)
2. **Add HTTPS** (secure connection with padlock)
3. **Set up backups** for your database
4. **Add monitoring** to know if site goes down
5. **Move uploads to S3** (more reliable storage)

---

## 🤝 Getting More Help

**If you're stuck**:
1. Take a screenshot of the error
2. Copy the exact error message
3. Note which step you're on
4. Ask for help with those details!

**Useful commands to diagnose**:
```bash
# Check if EC2 can reach RDS
mysql -h YOUR-RDS-ENDPOINT -u admin -p

# Check Docker container status
docker ps -a

# See container logs
docker logs fitbrawl_web

# Check disk space
df -h

# Check memory
free -h
```

---

**You've got this!** 🚀 Take it one step at a time, and don't hesitate to ask questions!
