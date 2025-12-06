# Demo Repository Setup Guide

This guide shows how to create the repository structure from scratch for demonstration purposes.

## Step 1: Create Root Directory

```powershell
# Create project root folder
mkdir C:\xampp\htdocs\fit-brawl
cd C:\xampp\htdocs\fit-brawl
```

## Step 2: Create Main Folder Structure

```powershell
# Create all main directories
mkdir docs
mkdir docs\database
mkdir includes
mkdir public
mkdir public\css
mkdir public\js
mkdir public\php
mkdir scripts
mkdir server-renderer
mkdir uploads
mkdir uploads\avatars
mkdir uploads\equipment
mkdir uploads\products
mkdir uploads\receipts
mkdir uploads\trainers
mkdir images
mkdir vendor
```

## Step 3: Create Essential Configuration Files

### 3.1 Create .gitignore

```powershell
New-Item .gitignore -ItemType File
```

**Content:**

```
# Environment variables (NEVER commit these!)
.env
.env.local
.env.*.local
.env.production

# Sensitive credentials
app.yaml

# PHP dependencies (regenerated via composer install)
vendor/

# Node modules
node_modules/
server-renderer/node_modules/
server-renderer/.cache/

# Uploads
uploads/avatars/*
uploads/receipts/*
uploads/equipment/*
uploads/products/*
uploads/trainers/*
!uploads/avatars/.gitkeep
!uploads/receipts/.gitkeep
!uploads/equipment/.gitkeep
!uploads/products/.gitkeep
!uploads/trainers/.gitkeep

# Development files
.vscode/
.idea/
*.log
*.bak
*.tmp
*~

# OS files
.DS_Store
Thumbs.db
desktop.ini

# Database files (but keep schema.sql for setup)
*.sql
!docs/database/schema.sql
*.sqlite
*.db
composer-setup.php

# Backup SQL files
backup_*.sql
seed_*.sql
fix_*.sql
update_*.sql
test-*.sql

# Logs and temporary files
logs/
*.log
error_log
debug.log

# Cache and session files
tmp/
cache/
sessions/
```

### 3.2 Create .env.example

```powershell
New-Item .env.example -ItemType File
```

**Content:**

```
# Fit & Brawl Gym - Environment Configuration
# Copy this file to .env and fill in your actual values

# ============================================
# APPLICATION SETTINGS
# ============================================
APP_ENV=development
BASE_PATH=/fit-brawl

# ============================================
# DATABASE CONFIGURATION
# ============================================
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=fit_and_brawl_gym
DB_PORT=3306

# ============================================
# EMAIL CONFIGURATION (SMTP)
# ============================================
# For Gmail: Use App Password (not regular password)
# Enable 2FA and generate App Password at: https://myaccount.google.com/apppasswords
EMAIL_HOST=smtp.gmail.com
EMAIL_PORT=587
EMAIL_USER=your-email@gmail.com
EMAIL_PASS=your-app-password-here

# Email sender details
EMAIL_FROM_ADDRESS=your-email@gmail.com
EMAIL_FROM_NAME=Fit & Brawl Gym

# ============================================
# SECURITY & ENCRYPTION
# ============================================
# Generate with: php -r "echo bin2hex(random_bytes(32));"
# This key is used for encrypting sensitive data
ENCRYPTION_KEY=generate_your_own_32_byte_hex_key_here

# ============================================
# SESSION CONFIGURATION
# ============================================
SESSION_LIFETIME=3600
SESSION_SECURE=false
SESSION_HTTPONLY=true
```

### 3.3 Create composer.json

```powershell
New-Item composer.json -ItemType File
```

**Content:**

```json
{
  "name": "fit-and-brawl/gym-management",
  "description": "Gym Management System for Fit & Brawl Gym",
  "type": "project",
  "require": {
    "php": ">=8.0",
    "phpmailer/phpmailer": "^6.9",
    "tecnickcom/tcpdf": "^6.6",
    "chillerlan/php-qrcode": "^4.3"
  },
  "require-dev": {
    "phpunit/phpunit": "^9.5"
  },
  "autoload": {
    "psr-4": {
      "FitBrawl\\": "includes/"
    }
  }
}
```

### 3.4 Create package.json (for server-renderer)

```powershell
New-Item server-renderer\package.json -ItemType File
```

**Content:**

```json
{
  "name": "fit-brawl-renderer",
  "version": "1.0.0",
  "description": "Server-side PDF rendering service",
  "main": "server.js",
  "scripts": {
    "start": "node server.js",
    "dev": "nodemon server.js"
  },
  "dependencies": {
    "express": "^4.18.2",
    "puppeteer": "^21.0.0"
  },
  "devDependencies": {
    "nodemon": "^3.0.1"
  }
}
```

### 3.5 Create README.md

```powershell
New-Item README.md -ItemType File
```

**Content:**

```markdown
# Fit & Brawl Gym Management System

A comprehensive web-based gym management system for handling memberships, trainer schedules, equipment inventory, and bookings.

## Features

- **User Management** - Members, trainers, and admin accounts
- **Membership Plans** - Boxing, Muay Thai, MMA, and Gym memberships
- **Booking System** - Session-based trainer bookings with conflict prevention
- **Equipment Tracking** - Inventory management with maintenance scheduling
- **Product Store** - Supplements, accessories, and gym products
- **Security** - Role-based access control, activity logging, and audit trails

## Tech Stack

- **Backend:** PHP 8.0+
- **Database:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript
- **Email:** PHPMailer (SMTP)
- **PDF Generation:** TCPDF, Puppeteer
- **Dependencies:** Composer (PHP), npm (Node.js)

## Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.4+
- Composer
- Node.js 14.x+
- XAMPP/Apache web server

## Installation

See [SETUP.md](SETUP.md) for detailed installation instructions.

## Quick Start

1. Clone the repository
2. Copy `.env.example` to `.env`
3. Run `composer install`
4. Run `npm install` in `server-renderer/`
5. Import `docs/database/schema.sql`
6. Configure `.env` with your settings
7. Access via `http://localhost/fit-brawl/public/`

## License

Capstone Project - All Rights Reserved

## Contributors

Fit & Brawl Development Team
```

### 3.6 Create SETUP.md

```powershell
New-Item SETUP.md -ItemType File
```

_(Copy the content from your existing SETUP.md)_

## Step 4: Create .gitkeep Files for Empty Directories

```powershell
# Create .gitkeep in upload folders
New-Item uploads\avatars\.gitkeep -ItemType File
New-Item uploads\receipts\.gitkeep -ItemType File
New-Item uploads\equipment\.gitkeep -ItemType File
New-Item uploads\products\.gitkeep -ItemType File
New-Item uploads\trainers\.gitkeep -ItemType File

# Add simple content to .gitkeep files
"# This file keeps the directory in Git" | Out-File uploads\avatars\.gitkeep -Encoding utf8
"# This file keeps the directory in Git" | Out-File uploads\receipts\.gitkeep -Encoding utf8
"# This file keeps the directory in Git" | Out-File uploads\equipment\.gitkeep -Encoding utf8
"# This file keeps the directory in Git" | Out-File uploads\products\.gitkeep -Encoding utf8
"# This file keeps the directory in Git" | Out-File uploads\trainers\.gitkeep -Encoding utf8
```

## Step 5: Add Database Schema

```powershell
# Copy your schema.sql to docs/database/
# This file should already exist in your current repo
Copy-Item path\to\your\schema.sql docs\database\schema.sql
```

## Step 6: Initialize Git Repository

```powershell
# Initialize git
git init

# Add all files
git add .

# First commit
git commit -m "Initial commit: Clean project structure for demo"

# Optional: Create demo-setup branch
git checkout -b demo-setup
```

## Step 7: Add Core PHP Files (Essential Only)

For demo purposes, include only essential files:

### 7.1 includes/db_connect.php

```powershell
New-Item includes\db_connect.php -ItemType File
```

**Content:**

```php
<?php
// Database connection for Fit & Brawl Gym

include_once __DIR__ . '/env_loader.php';
loadEnv(__DIR__ . '/../.env');

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'fit_and_brawl_gym';
$port = getenv('DB_PORT') ?: 3306;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone = '+08:00'");
} catch (mysqli_sql_exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
```

### 7.2 includes/config.php

```powershell
New-Item includes\config.php -ItemType File
```

**Content:**

```php
<?php
// Application Configuration

require_once __DIR__ . '/env_loader.php';
loadEnv(__DIR__ . '/../.env');

$appEnv = getenv('APP_ENV') ?: 'development';
$defaultBase = $appEnv === 'production' ? '/' : '/fit-brawl';
$configuredBase = getenv('BASE_PATH') ?: $defaultBase;

define('BASE_PATH', rtrim($configuredBase, '/') . '/');
define('PUBLIC_PATH', $appEnv === 'production' ? '' : BASE_PATH . 'public');
define('ENVIRONMENT', $appEnv);

date_default_timezone_set('Asia/Manila');
?>
```

### 7.3 includes/env_loader.php

```powershell
New-Item includes\env_loader.php -ItemType File
```

**Content:**

```php
<?php
// Simple .env file loader

function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!getenv($name)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}
?>
```

### 7.4 public/index.php (Simple landing page)

```powershell
New-Item public\index.php -ItemType File
```

**Content:**

```php
<?php
require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fit & Brawl Gym - Home</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 100px 20px; text-align: center;
        }
        h1 { font-size: 3em; margin-bottom: 20px; }
        p { font-size: 1.2em; }
        .container { max-width: 1200px; margin: 50px auto; padding: 20px; }
        .success {
            background: #d4edda; border: 1px solid #c3e6cb;
            color: #155724; padding: 20px; border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="hero">
        <h1>🥊 Fit & Brawl Gym</h1>
        <p>Your Premier Training Facility</p>
    </div>
    <div class="container">
        <div class="success">
            <h2>✅ Setup Successful!</h2>
            <p>The Fit & Brawl Gym Management System is now running.</p>
            <p><strong>Environment:</strong> <?php echo ENVIRONMENT; ?></p>
            <p><strong>Base Path:</strong> <?php echo BASE_PATH; ?></p>
        </div>
    </div>
</body>
</html>
```

## Step 8: Final Verification

```powershell
# Check folder structure
tree /F

# Verify no .env exists (only .env.example)
Test-Path .env  # Should return False

# Verify no vendor/ folder
Test-Path vendor  # Should return False

# Verify no node_modules/ folder
Test-Path server-renderer\node_modules  # Should return False
```

## Step 9: Create Quick Setup Script

```powershell
New-Item quick-setup.bat -ItemType File
```

**Content:**

```batch
@echo off
echo ========================================
echo   Fit ^& Brawl Gym - Quick Setup
echo ========================================
echo.

if not exist ".env" (
    echo [1/3] Creating .env file...
    copy ".env.example" ".env"
    echo    Done! Edit .env with your settings.
) else (
    echo [1/3] .env already exists
)
echo.

echo [2/3] Installing PHP dependencies...
call composer install
echo.

echo [3/3] Installing Node.js dependencies...
cd server-renderer
call npm install
cd ..
echo.

echo ========================================
echo Setup Complete!
echo Next: Import docs/database/schema.sql
echo Then visit: http://localhost/fit-brawl/public/
echo ========================================
pause
```

## Step 10: Push to GitHub

```powershell
# Add remote (replace with your repo URL)
git remote add origin https://github.com/Fit-and-Brawl-Gym/fit-brawl.git

# Push to main branch
git push -u origin demo-setup

# Or push to main
git checkout -b main
git push -u origin main
```

---

## Demo Recording Checklist

Before recording:

- [ ] No `.env` file (only `.env.example`)
- [ ] No `vendor/` folder
- [ ] No `node_modules/` folder
- [ ] Empty upload folders (only `.gitkeep` files)
- [ ] `schema.sql` exists in `docs/database/`
- [ ] All documentation files present
- [ ] Git repository initialized

---

## Quick Clone & Setup Commands (For Demo)

```powershell
# Clone repository
git clone https://github.com/Fit-and-Brawl-Gym/fit-brawl.git
cd fit-brawl

# Copy environment file
copy .env.example .env

# Install dependencies
composer install
cd server-renderer
npm install
cd ..

# Open notepad to edit .env
notepad .env

# Create database and import schema in phpMyAdmin
# Then access: http://localhost/fit-brawl/public/
```

---

This structure gives you a clean, professional demo repository! 🚀
