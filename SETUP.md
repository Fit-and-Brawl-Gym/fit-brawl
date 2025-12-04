# Fit & Brawl Gym - Setup Guide

This guide will help you set up the Fit & Brawl Gym Management System on your local machine.

## Table of Contents
1. [Requirements](#requirements)
2. [Installation Steps](#installation-steps)
3. [Database Setup](#database-setup)
4. [Configuration](#configuration)
5. [Running the Application](#running-the-application)
6. [Common Issues](#common-issues)

---

## Requirements

Before you start, make sure you have these installed on your computer:

- **XAMPP** (includes Apache, MySQL, PHP)
  - Download from: https://www.apachefriends.org/
  - Version: PHP 8.0 or higher recommended

- **Composer** (PHP dependency manager)
  - Download from: https://getcomposer.org/download/
  - For Windows: Download and run the installer

- **Node.js** (for server-side rendering)
  - Download from: https://nodejs.org/
  - Version: 14.x or higher recommended

---

## Installation Steps

### Step 1: Download/Clone the Repository

1. Download this project or clone it using Git
2. Place it in your XAMPP `htdocs` folder
   - Example path: `C:\xampp\htdocs\fit-brawl`

### Step 2: Install PHP Dependencies

1. Open Command Prompt or PowerShell
2. Navigate to the project folder:
   ```
   cd C:\xampp\htdocs\fit-brawl
   ```
3. Install Composer dependencies:
   ```
   composer install
   ```
   
   **Note:** This will download all PHP libraries needed (PHPMailer, TCPDF, etc.)

### Step 3: Install Node.js Dependencies

Still in the project folder, run:
```
cd server-renderer
npm install
cd ..
```

This installs the packages needed for PDF generation.

---

## Database Setup

### Step 1: Start XAMPP

1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** services

### Step 2: Create Database

1. Open your browser and go to: http://localhost/phpmyadmin
2. Click on **"New"** in the left sidebar
3. Create a new database named: `fit_and_brawl_gym`
   - Collation: `utf8mb4_general_ci`

### Step 3: Import Database Schema

1. Click on your newly created database `fit_and_brawl_gym`
2. Click on the **"Import"** tab at the top
3. Click **"Choose File"** and select:
   - File location: `docs/database/schema.sql`
4. Click **"Go"** at the bottom to import
5. Wait for the success message

**What this does:** Creates all the tables needed for the system (users, trainers, bookings, etc.)

---

## Configuration

### Step 1: Create Environment File

1. In the project root folder, find the file `.env.example`
2. Copy it and rename the copy to `.env`
3. Open `.env` in a text editor (Notepad is fine)

### Step 2: Configure Database Connection

Edit these lines in your `.env` file:

```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=fit_and_brawl_gym
DB_PORT=3306
```

**Note:** For default XAMPP, the password is empty (leave `DB_PASS=` blank)

### Step 3: Configure Email (Optional)

If you want to test email features:

1. Use a Gmail account
2. Enable 2-Factor Authentication on your Google account
3. Generate an App Password: https://myaccount.google.com/apppasswords
4. Update these in `.env`:

```
EMAIL_HOST=smtp.gmail.com
EMAIL_PORT=587
EMAIL_USER=your-email@gmail.com
EMAIL_PASS=your-16-digit-app-password
EMAIL_FROM_ADDRESS=your-email@gmail.com
EMAIL_FROM_NAME=Fit & Brawl Gym
```

### Step 4: Generate Encryption Key

For security features, generate an encryption key:

1. Open Command Prompt in your project folder
2. Run this command:
   ```
   php -r "echo bin2hex(random_bytes(32));"
   ```
3. Copy the output (a long string of letters and numbers)
4. Paste it in your `.env` file:
   ```
   ENCRYPTION_KEY=paste-your-key-here
   ```

---

## Running the Application

### Step 1: Make Sure XAMPP is Running

- Apache and MySQL should be running in XAMPP Control Panel
- If ports are blocked, you might need to change Apache port to 8080

### Step 2: Access the Application

Open your browser and go to:
```
http://localhost/fit-brawl/public/
```

You should see the Fit & Brawl Gym homepage!

### Step 3: Create Admin Account (First Time)

Since the database is empty, you'll need to create an admin account manually:

1. Go to phpMyAdmin: http://localhost/phpmyadmin
2. Select `fit_and_brawl_gym` database
3. Click on `users` table
4. Click **"Insert"** tab
5. Fill in:
   - `id`: `ADM-25-0001`
   - `username`: `admin`
   - `email`: `admin@fitbrawl.com`
   - `password`: Use this hashed password for "admin123":
     ```
     $2y$10$8K1p/eO.PLxEJL89lYZz0OqYrB5KKXq4L3qwQvKBVQ7jZ0x5r5X5K
     ```
   - `role`: `admin`
   - `is_verified`: `1`
   - `account_status`: `active`
6. Click **"Go"**

Now you can login with:
- Username: `admin`
- Password: `admin123`

**Important:** Change this password after first login!

---

## Common Issues

### Problem: "Access forbidden" or "404 Not Found"

**Solution:** Make sure you're accessing the `public` folder:
```
http://localhost/fit-brawl/public/
```

### Problem: Database connection failed

**Solutions:**
1. Check if MySQL is running in XAMPP
2. Verify database name is `fit_and_brawl_gym`
3. Check `.env` file settings match your database

### Problem: Composer command not found

**Solution:** 
1. Make sure Composer is installed
2. Restart your Command Prompt after installation
3. Or use full path: `C:\ProgramData\ComposerSetup\bin\composer.bat install`

### Problem: npm command not found

**Solution:**
1. Install Node.js from nodejs.org
2. Restart Command Prompt after installation
3. Check with: `node --version`

### Problem: Port 80 or 443 already in use

**Solution:**
1. In XAMPP Control Panel, click "Config" for Apache
2. Select "httpd.conf"
3. Change `Listen 80` to `Listen 8080`
4. Access via: http://localhost:8080/fit-brawl/public/

---

## Project Structure

Quick overview of important folders:

```
fit-brawl/
├── public/           # Main website files (CSS, JS, PHP pages)
├── includes/         # Core PHP files (database, config, helpers)
├── vendor/           # PHP dependencies (installed by Composer)
├── server-renderer/  # PDF generation service
├── docs/database/    # Database schema and documentation
├── uploads/          # User uploaded files (avatars, receipts)
├── .env              # Your configuration (DO NOT SHARE!)
└── composer.json     # PHP dependencies list
```

---

## Need Help?

If you encounter issues not covered here:
1. Check the database connection in `.env`
2. Look at Apache error logs in XAMPP
3. Make sure all dependencies are installed
4. Verify file permissions (especially `uploads/` folder)

---

**Good luck with your setup!** 🥊💪

