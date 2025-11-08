# FitXBrawl: Gym Scheduling Management Website

> A comprehensive web-based gym management system designed to streamline membership management, trainer scheduling, equipment tracking, and user reservations for fitness facilities.

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Status](https://img.shields.io/badge/status-Active-success.svg)]()

---

## 📑 Table of Contents

- [🚀 Project Overview & Key Features](#-project-overview--key-features)
  - [Motivation](#motivation)
  - [Key Features](#key-features)
- [⚙️ Getting Started (Installation & Setup)](#️-getting-started-installation--setup)
  - [Prerequisites](#prerequisites)
  - [Frontend Setup](#frontend-setup)
  - [Backend Setup](#backend-setup)
  - [Running Tests](#running-tests)
- [☁️ Deployment](#️-deployment)
  - [Platform](#platform)
  - [CI/CD Pipeline](#cicd-pipeline)
- [🤝 Contributing & Support](#-contributing--support)
  - [Contribution Guidelines](#contribution-guidelines)
  - [Contact/Support](#contactsupport)
  - [License](#license)
- [⚠️ NAVIGATION AND LOGIC SECTIONS WILL BE ADDED HERE](#️-navigation-and-logic-sections-will-be-added-here)

---

## 🚀 Project Overview & Key Features

### Motivation

FitXBrawl addresses the complex challenges faced by modern gym facilities in managing their operations efficiently. Traditional gym management often involves manual scheduling, paper-based membership tracking, and fragmented communication systems. This leads to:

- **Scheduling Conflicts**: Double bookings and trainer availability issues
- **Poor User Experience**: Difficulty in booking training sessions and tracking membership status
- **Administrative Overhead**: Manual tracking of memberships, equipment, and trainer schedules
- **Lack of Transparency**: Users unable to see real-time availability or session status
- **Communication Gaps**: Delayed notifications for membership approvals, session bookings, and updates

FitXBrawl solves these problems by providing a centralized, web-based platform that automates gym operations, enhances user experience, and provides real-time visibility into all aspects of gym management.

### Key Features

#### **User Management & Authentication**
- 🔐 **Secure User Registration & Login**: Email-based verification system with OTP (One-Time Password) validation
- 👤 **Role-Based Access Control**: Distinct interfaces and permissions for Regular Users, Trainers, and Administrators
- 📧 **Email Verification System**: Automated email verification using PHPMailer with Gmail SMTP integration
- 🔑 **Password Management**: Secure password reset functionality with email-based recovery
- 🖼️ **Profile Customization**: User avatar upload with image validation and custom profile management
- ⏱️ **Session Management**: Automatic session timeout with idle detection and session extension capabilities

#### **Membership Management**
- 💳 **Subscription Plans**: Multiple membership tiers (Gladiator, Brawler, Champion, Clash, Resolution)
- 📅 **Billing Options**: Monthly and quarterly billing cycles with automatic discount calculations (5-16% off for quarterly)
- ✅ **Approval Workflow**: Admin-controlled membership request approval system
- 📊 **Status Tracking**: Real-time membership status monitoring (pending, approved, active, expired)
- 🔄 **Plan Upgrades**: Seamless membership tier upgrade functionality
- 🧾 **Receipt Generation**: Automated PDF receipt generation with QR codes using TCPDF
- 💰 **Grace Period**: 3-day grace period after membership expiration before access restrictions

#### **Training Session Booking System**
- 📆 **Interactive Calendar**: Visual monthly calendar with date selection and availability indicators
- 🕒 **Session Time Slots**: Three daily sessions (Morning: 7-11 AM, Afternoon: 1-5 PM, Evening: 6-10 PM)
- 🥊 **Class Types**: Support for Boxing, Muay Thai, MMA, and Gym training classes
- 👨‍🏫 **Trainer Selection**: View available trainers by specialization with photo profiles
- 📈 **Weekly Limits**: Maximum 12 bookings per week per user with intelligent quota tracking
- ⚡ **Real-Time Availability**: Dynamic availability checking based on trainer schedules and facility capacity
- 🚫 **Smart Restrictions**: 
  - Prevents booking within 30 minutes of session end
  - Blocks past sessions and fully booked slots
  - Disables dates when all sessions are unavailable
- ✨ **Ongoing Session Detection**: Real-time status indicators showing currently active training sessions
- 📱 **Booking Management**: View, filter, and cancel upcoming reservations (with 12-hour advance notice requirement)

#### **Trainer Management (Admin)**
- 👥 **Trainer CRUD Operations**: Complete trainer profile management with photo uploads
- 🏋️ **Specialization Tracking**: Assign trainers to specific class types (Boxing, Muay Thai, MMA, Gym)
- 📅 **Day-Off Management**: Configure weekly schedules and day-off patterns for each trainer
- 🚫 **Session Blocking**: Admin ability to block specific date/time slots for trainers
- 📊 **Booking Overview**: View all trainer bookings and daily schedules
- ✏️ **Profile Updates**: Edit trainer information, photos, and specializations

#### **Equipment & Product Management**
- 🏪 **Equipment Tracking**: Comprehensive equipment inventory with status monitoring (Available, In Use, Maintenance, Out of Stock)
- 📦 **Product Management**: Merchandise and supplement inventory system
- 🖼️ **Image Management**: Upload and manage product/equipment images
- 📝 **Stock Tracking**: Real-time stock level monitoring and updates
- 💵 **Pricing Management**: Flexible pricing for products with automatic calculations

#### **Feedback & Communication**
- 💬 **User Feedback System**: Structured feedback submission with voting mechanisms
- 👍 **Vote Management**: Upvote/downvote system for feedback prioritization
- 📬 **Contact Form**: Inquiry submission system with admin response tracking
- 🔔 **Email Notifications**: Automated notifications for membership approvals, booking confirmations, and system updates
- 📋 **Admin Moderation**: Review and respond to user feedback and inquiries

#### **Security & Performance**
- 🛡️ **CSRF Protection**: Token-based protection against Cross-Site Request Forgery attacks
- 🚦 **Rate Limiting**: API request throttling to prevent abuse and DDoS attacks
- 🔒 **File Upload Security**: Strict validation for file types, sizes, and content
- 📝 **Activity Logging**: Comprehensive audit trail for all admin actions
- ⚠️ **Error Handling**: Centralized error management with user-friendly messages
- 🔐 **SQL Injection Prevention**: Prepared statements for all database queries
- 🌐 **Content Security Policy**: CSP headers to prevent XSS attacks

#### **User Interface & Experience**
- 📱 **Responsive Design**: Mobile-first design that works seamlessly across all devices
- 🎨 **Modern UI Components**: Clean, intuitive interface with consistent design language
- 🔔 **Toast Notifications**: Real-time feedback for user actions and system events
- 📊 **Dashboard Analytics**: User dashboard showing booking statistics and membership status
- 🎯 **Wizard-Based Booking**: 4-step booking process (Date → Session → Class → Trainer)
- 🔍 **Advanced Filtering**: Filter bookings by class type, date range, and status
- ⏰ **Real-Time Updates**: AJAX-based updates without page reloads
- 🌙 **Session Status Indicators**: Visual badges for ongoing, completed, and cancelled sessions

#### **Administrative Features**
- 📊 **Comprehensive Dashboard**: Overview of system statistics and recent activities
- 👥 **User Management**: View, edit, and manage all user accounts
- 💳 **Membership Oversight**: Approve/reject membership requests, modify plans
- 📅 **Booking Management**: View all reservations, manage conflicts, and override bookings
- 📈 **Activity Logs**: Detailed audit trail of all administrative actions
- 📧 **Bulk Notifications**: Send system-wide announcements and updates
- 🔧 **System Configuration**: Manage facility capacity limits and booking rules

---

## ⚙️ Getting Started (Installation & Setup)

### Prerequisites

Before setting up FitXBrawl, ensure you have the following software and accounts:

#### **Required Software**
- **PHP** >= 8.1
  - Required Extensions: `mysqli`, `pdo`, `pdo_mysql`, `gd`, `mbstring`, `json`, `openssl`, `fileinfo`
- **MySQL** >= 5.7 or **MariaDB** >= 10.2
- **Apache** >= 2.4 with `mod_rewrite` enabled
- **Composer** >= 2.0 (PHP dependency manager)
- **Web Browser** (Chrome, Firefox, Safari, or Edge - latest version)

#### **Optional (Recommended)**
- **XAMPP** (all-in-one Apache, MySQL, PHP package)
- **Git** for version control
- **Node.js** >= 14.x (for front-end build tools, if needed)

#### **Required Accounts & Services**
- **Gmail Account** (for SMTP email service)
  - App Password enabled (for PHPMailer SMTP authentication)
- **GitHub Account** (for repository access and version control)

#### **System Requirements**
- **Disk Space**: Minimum 500 MB free space
- **RAM**: Minimum 512 MB available
- **Network**: Stable internet connection for email services

---

### Frontend Setup

The frontend is built with vanilla JavaScript, CSS3, and HTML5. No build process is required for the core application.

#### **Step 1: Clone the Repository**

```bash
# Clone the repository
git clone https://github.com/Fit-and-Brawl-Gym/fit-brawl.git

# Navigate to the project directory
cd fit-brawl
```

#### **Step 2: Install PHP Dependencies**

```bash
# Install Composer dependencies
composer install
```

This will install:
- **PHPMailer** (^6.11) - Email sending functionality
- **TCPDF** (^6.7) - PDF receipt generation
- **chillerlan/php-qrcode** (^5.0) - QR code generation for receipts

#### **Step 3: Configure Static Assets**

All CSS and JavaScript files are located in the `public/` directory:

```
public/
├── css/
│   ├── global.css          # Global styles
│   ├── reset.css           # CSS reset
│   ├── admin-variables.css # Admin theme variables
│   ├── components/         # Reusable component styles
│   └── pages/              # Page-specific styles
└── js/
    ├── main.js             # Core JavaScript
    ├── reservations.js     # Booking system logic
    ├── membership.js       # Membership management
    ├── session-timeout.js  # Session handling
    └── trainer/            # Trainer dashboard scripts
```

**Note**: No npm/build step is required. All assets are served directly.

#### **Step 4: Set Up File Permissions**

Ensure the web server has write permissions for upload directories:

```bash
# On Linux/macOS
chmod -R 755 uploads/
chmod -R 755 scripts/

# On Windows (using PowerShell as Administrator)
icacls uploads /grant Everyone:F /T
icacls scripts /grant Everyone:F /T
```

---

### Backend Setup

#### **Step 1: Database Configuration**

**Create the Database:**

```bash
# Access MySQL
mysql -u root -p

# Create database
CREATE DATABASE fit_brawl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user (optional but recommended)
CREATE USER 'fitbrawl_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON fit_brawl.* TO 'fitbrawl_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Import Database Schema:**

```bash
# Import the schema
mysql -u root -p fit_brawl < docs/database/schema.sql

# Import seed data (optional - for testing)
mysql -u root -p fit_brawl < docs/database/seed.sql
```

#### **Step 2: Environment Configuration**

Create a `.env` file in the project root:

```bash
# On Linux/macOS
cp .env.example .env

# On Windows
copy .env.example .env
```

Edit the `.env` file with your configuration:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=fit_brawl
DB_USER=fitbrawl_user
DB_PASS=your_secure_password
DB_PORT=3306

# Email Configuration (Gmail SMTP)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_EMAIL=your-email@gmail.com
MAIL_FROM_NAME=Fit & Brawl Gym

# Application Settings
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/fit-brawl
SESSION_LIFETIME=1800
UPLOAD_MAX_SIZE=5242880

# Security Keys (generate random strings)
CSRF_TOKEN_KEY=your_random_csrf_key_here
SESSION_ENCRYPTION_KEY=your_random_session_key_here
```

**Generate App Password for Gmail:**

1. Go to [Google Account Security](https://myaccount.google.com/security)
2. Enable 2-Step Verification
3. Navigate to "App passwords"
4. Generate a new app password for "Mail"
5. Use this password in `MAIL_PASSWORD`

#### **Step 3: Configure Apache Virtual Host**

**For XAMPP Users:**

Edit `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName fitbrawl.local
    DocumentRoot "C:/xampp/htdocs/fit-brawl/public"
    
    <Directory "C:/xampp/htdocs/fit-brawl/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/fitbrawl-error.log"
    CustomLog "logs/fitbrawl-access.log" common
</VirtualHost>
```

Add to hosts file (`C:\Windows\System32\drivers\etc\hosts`):

```
127.0.0.1    fitbrawl.local
```

**For Linux/macOS Users:**

```bash
# Create virtual host config
sudo nano /etc/apache2/sites-available/fitbrawl.conf
```

```apache
<VirtualHost *:80>
    ServerName fitbrawl.local
    DocumentRoot /var/www/fit-brawl/public
    
    <Directory /var/www/fit-brawl/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/fitbrawl-error.log
    CustomLog ${APACHE_LOG_DIR}/fitbrawl-access.log combined
</VirtualHost>
```

```bash
# Enable site and rewrite module
sudo a2ensite fitbrawl.conf
sudo a2enmod rewrite
sudo systemctl restart apache2

# Add to hosts file
echo "127.0.0.1    fitbrawl.local" | sudo tee -a /etc/hosts
```

#### **Step 4: Start the Application**

**Using XAMPP:**

1. Start Apache and MySQL from XAMPP Control Panel
2. Navigate to `http://fitbrawl.local` or `http://localhost/fit-brawl/public`

**Using Command Line (PHP Built-in Server - Development Only):**

```bash
cd public
php -S localhost:8000
```

Access at: `http://localhost:8000`

#### **Step 5: Create Admin Account**

Navigate to the registration page and create an account. Then manually update the user role in the database:

```sql
UPDATE users 
SET role = 'admin', email_verified = 1 
WHERE email = 'your-admin-email@example.com';
```

---

### Running Tests

Currently, FitXBrawl uses manual testing procedures. Automated testing is planned for future releases.

#### **Manual Testing Checklist**

1. **User Registration & Authentication**
   ```bash
   # Test email verification system
   # Verify OTP delivery and validation
   # Test login/logout functionality
   ```

2. **Membership Workflow**
   ```bash
   # Submit membership request
   # Verify admin approval process
   # Check receipt PDF generation
   ```

3. **Booking System**
   ```bash
   # Test date selection and availability
   # Verify session time restrictions
   # Confirm trainer availability checks
   # Test booking cancellation
   ```

4. **API Endpoints**
   ```bash
   # Test using cURL or Postman
   curl http://localhost/fit-brawl/public/php/api/get_available_trainers.php?date=2025-11-08&session=Morning&class=MMA
   ```

#### **Database Testing**

```sql
-- Run database integrity checks
SELECT * FROM users WHERE email_verified = 0;
SELECT * FROM user_memberships WHERE membership_status = 'pending';
SELECT * FROM reservations WHERE booking_date >= CURDATE();
```

#### **Future Testing Plans**

- **PHPUnit**: Unit tests for business logic
- **Selenium**: End-to-end browser automation tests
- **Jest**: JavaScript unit tests
- **Continuous Integration**: GitHub Actions workflow for automated testing

---

## ☁️ Deployment

### Platform

FitXBrawl is designed to be deployed on any standard LAMP stack (Linux, Apache, MySQL, PHP) hosting environment.

#### **Recommended Hosting Providers**
- **DigitalOcean** - Cloud VPS with full control
- **AWS EC2** - Scalable cloud infrastructure
- **Linode** - Developer-friendly cloud hosting
- **SiteGround** - Managed PHP hosting
- **A2 Hosting** - Performance-optimized shared hosting

#### **Deployment Requirements**
- PHP 8.1+ support
- MySQL/MariaDB database
- Apache with mod_rewrite
- SSL certificate (Let's Encrypt recommended)
- Minimum 512 MB RAM
- 500 MB+ disk space

#### **Production Environment Variables**

Update `.env` for production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Use strong, unique keys in production
CSRF_TOKEN_KEY=generate_strong_random_key_here
SESSION_ENCRYPTION_KEY=generate_strong_random_key_here
```

#### **Security Hardening for Production**

1. **SSL/TLS Configuration**
   ```bash
   # Install Let's Encrypt certificate
   sudo certbot --apache -d your-domain.com
   ```

2. **File Permissions**
   ```bash
   # Set strict permissions
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   chmod 600 .env
   ```

3. **Apache Security Headers**
   ```apache
   Header always set X-Content-Type-Options "nosniff"
   Header always set X-Frame-Options "SAMEORIGIN"
   Header always set X-XSS-Protection "1; mode=block"
   Header always set Referrer-Policy "strict-origin-when-cross-origin"
   ```

---

### CI/CD Pipeline

Currently, FitXBrawl uses a manual deployment process. Automated CI/CD is planned for future releases.

#### **Current Deployment Workflow**

1. **Development**
   - Develop features on feature branches
   - Test locally using XAMPP or local Apache

2. **Version Control**
   ```bash
   git checkout -b feature/new-feature
   git add .
   git commit -m "Add new feature"
   git push origin feature/new-feature
   ```

3. **Code Review & Merge**
   - Create Pull Request on GitHub
   - Code review by team members
   - Merge to `main` branch after approval

4. **Manual Deployment**
   ```bash
   # SSH into production server
   ssh user@your-server.com
   
   # Pull latest changes
   cd /var/www/fit-brawl
   git pull origin main
   
   # Update dependencies
   composer install --no-dev --optimize-autoloader
   
   # Clear cache if applicable
   php artisan cache:clear  # (if using Laravel-style caching)
   
   # Restart Apache
   sudo systemctl restart apache2
   ```

#### **Planned CI/CD Implementation (GitHub Actions)**

Future automated deployment pipeline:

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
    
    - name: Install Dependencies
      run: composer install --no-dev --prefer-dist
    
    - name: Run Tests
      run: ./vendor/bin/phpunit
    
    - name: Deploy to Server
      uses: appleboy/ssh-action@master
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.SSH_KEY }}
        script: |
          cd /var/www/fit-brawl
          git pull origin main
          composer install --no-dev --optimize-autoloader
          sudo systemctl restart apache2
```

#### **Deployment Checklist**

- [ ] Backup database before deployment
- [ ] Update `.env` with production credentials
- [ ] Run database migrations if schema changed
- [ ] Test all critical user flows
- [ ] Monitor error logs after deployment
- [ ] Verify email service functionality
- [ ] Test payment/receipt generation
- [ ] Confirm backup systems are operational

---

## 🤝 Contributing & Support

### Contribution Guidelines

We welcome contributions from the community! FitXBrawl is an open-source project, and we appreciate any help in making it better.

#### **How to Contribute**

1. **Fork the Repository**
   ```bash
   # Click "Fork" on GitHub, then clone your fork
   git clone https://github.com/YOUR-USERNAME/fit-brawl.git
   cd fit-brawl
   ```

2. **Create a Feature Branch**
   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b bugfix/issue-description
   ```

3. **Make Your Changes**
   - Follow existing code style and conventions
   - Write clear, descriptive commit messages
   - Add comments for complex logic
   - Update documentation if needed

4. **Test Your Changes**
   - Test all affected functionality
   - Ensure no existing features are broken
   - Verify cross-browser compatibility

5. **Commit and Push**
   ```bash
   git add .
   git commit -m "Add: Brief description of your changes"
   git push origin feature/your-feature-name
   ```

6. **Create a Pull Request**
   - Go to the original repository on GitHub
   - Click "New Pull Request"
   - Provide a clear description of your changes
   - Reference any related issues

#### **Pull Request Guidelines**

- **Title**: Use clear, descriptive titles (e.g., "Fix: Booking system date validation bug")
- **Description**: Explain what changes you made and why
- **Screenshots**: Include before/after screenshots for UI changes
- **Testing**: Describe how you tested your changes
- **Breaking Changes**: Clearly mark any breaking changes

#### **Code Style Guidelines**

- **PHP**: Follow PSR-12 coding standards
- **JavaScript**: Use ES6+ syntax, consistent indentation (2 spaces)
- **CSS**: Use BEM naming convention, organize by components
- **SQL**: Use uppercase for keywords, snake_case for table/column names

#### **Detailed Contribution Guide**

For comprehensive contribution instructions, please see [CONTRIBUTING.md](CONTRIBUTING.md) *(placeholder - will be created)*.

---

### Contact/Support

#### **Get Help**

- **Issues**: Report bugs or request features on [GitHub Issues](https://github.com/Fit-and-Brawl-Gym/fit-brawl/issues)
- **Discussions**: Join conversations on [GitHub Discussions](https://github.com/Fit-and-Brawl-Gym/fit-brawl/discussions)
- **Email**: contact@fitandbrawl.com *(for general inquiries)*

#### **Community**

- **Discord**: [Join our Discord server](#) *(coming soon)*
- **Twitter**: [@FitAndBrawl](#) *(coming soon)*

#### **Security Issues**

If you discover a security vulnerability, please email security@fitandbrawl.com instead of using the issue tracker. We take security seriously and will respond promptly.

---

### License

FitXBrawl is open-source software licensed under the **MIT License**.

```
MIT License

Copyright (c) 2025 Fit and Brawl Gym

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

See the [LICENSE](LICENSE) file for full details.

---

## ⚠️ **[NAVIGATION AND LOGIC SECTIONS WILL BE ADDED HERE]**

*The following sections will be added in Part 2:*
- 🗺️ Website Navigation & Structure
- ⚙️ Core System Logic & Architecture

---

**Project Status**: Active Development 🚀  
**Last Updated**: November 8, 2025  
**Contributors**: Fit and Brawl Development Team

[Back to Top ⬆](#fitxbrawl-gym-scheduling-management-website)
