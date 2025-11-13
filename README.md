# FitXBrawl: Gym Scheduling Management Website

> A comprehensive web-based gym management system designed to streamline membership management, trainer scheduling, equipment tracking, and user reservations for fitness facilities.

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Deployment Status](https://github.com/Fit-and-Brawl-Gym/fit-brawl/actions/workflows/deploy.yml/badge.svg)](https://github.com/Fit-and-Brawl-Gym/fit-brawl/actions/workflows/deploy.yml)
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

## 🗺️ Website Navigation & Structure

### URL Structure Map (Routing)

FitXBrawl uses a traditional PHP routing architecture with role-based access control. Below is the comprehensive URL-to-component mapping:

| Route | Component/View | Auth Required? | Role | Description |
|-------|---------------|----------------|------|-------------|
| **PUBLIC ROUTES** |
| `/public/php/index.php` | Homepage | ❌ No | Public | Landing page with gym overview, hero section, services |
| `/public/php/contact.php` | Contact Form | ❌ No | Public | Contact form for inquiries, stores in `contact` table |
| `/public/php/login.php` | Login Page | ❌ No | Public | User authentication, session initialization |
| `/public/php/sign-up.php` | Registration | ❌ No | Public | User registration with email verification |
| `/public/php/verification.php` | Email Verification | ❌ No | Public | OTP-based email verification page |
| `/public/php/verify-email.php` | Email Token Verify | ❌ No | Public | Processes email verification token |
| `/public/php/forgot-password.php` | Password Reset | ❌ No | Public | Initiates password reset flow |
| `/public/php/transaction_nonmember.php` | Non-Member Booking | ❌ No | Public | Service booking for non-members with receipt |
| **AUTHENTICATED USER ROUTES** |
| `/public/php/loggedin-index.php` | User Dashboard | ✅ Yes | Member | Authenticated homepage with quick actions |
| `/public/php/user_profile.php` | Profile Page | ✅ Yes | Member | View/edit profile, avatar upload |
| `/public/php/update_profile.php` | Profile Update API | ✅ Yes | Member | AJAX endpoint for profile updates |
| `/public/php/change-password.php` | Change Password | ✅ Yes | Member | Password change with validation |
| `/public/php/membership.php` | Membership Plans | ✅ Yes | Member | View/purchase membership plans |
| `/public/php/membership-status.php` | Membership Status | ✅ Yes | Member | Track pending membership requests |
| `/public/php/reservations.php` | Booking Wizard | ✅ Yes | Member | 4-step trainer booking system (requires active membership) |
| `/public/php/transaction.php` | Booking History | ✅ Yes | Member | View upcoming/past/cancelled bookings |
| `/public/php/transaction_service.php` | Service Booking | ✅ Yes | Member | Book additional services (spa, massage, etc.) |
| `/public/php/equipment.php` | Equipment Catalog | ✅ Yes | Member | Browse gym equipment inventory |
| `/public/php/products.php` | Products Store | ✅ Yes | Member | Browse/purchase gym merchandise |
| `/public/php/feedback.php` | Feedback System | ✅ Yes | Member | Submit feedback with voting system |
| **ADMIN ROUTES** |
| `/public/php/admin/admin.php` | Admin Dashboard | ✅ Yes | Admin | Dashboard with system metrics, recent activities |
| `/public/php/admin/users.php` | User Management | ✅ Yes | Admin | CRUD operations for user accounts |
| `/public/php/admin/trainers.php` | Trainer Management | ✅ Yes | Admin | Manage trainers, view schedules, activity logs |
| `/public/php/admin/trainer_add.php` | Add Trainer | ✅ Yes | Admin | Create new trainer accounts |
| `/public/php/admin/trainer_edit.php` | Edit Trainer | ✅ Yes | Admin | Update trainer information |
| `/public/php/admin/trainer_view.php` | Trainer Details | ✅ Yes | Admin | View trainer profile and booking history |
| `/public/php/admin/trainer_schedules.php` | Trainer Schedules | ✅ Yes | Admin | Manage day-offs and availability blocks |
| `/public/php/admin/subscriptions.php` | Membership Requests | ✅ Yes | Admin | Approve/reject membership applications |
| `/public/php/admin/reservations.php` | Booking Management | ✅ Yes | Admin | View all user reservations, statistics |
| `/public/php/admin/equipment.php` | Equipment Admin | ✅ Yes | Admin | Add/edit/delete equipment items |
| `/public/php/admin/products.php` | Products Admin | ✅ Yes | Admin | Manage product catalog and pricing |
| `/public/php/admin/feedback.php` | Feedback Management | ✅ Yes | Admin | Review user feedback, moderate content |
| `/public/php/admin/contacts.php` | Contact Inquiries | ✅ Yes | Admin | View and respond to contact form submissions |
| `/public/php/admin/activity-log.php` | Activity Logs | ✅ Yes | Admin | System-wide audit trail |
| `/public/php/admin/announcements.php` | Announcements | ✅ Yes | Admin | Create/manage system announcements |
| **TRAINER ROUTES** |
| `/public/php/trainer/index.php` | Trainer Dashboard | ✅ Yes | Trainer | Trainer's personal dashboard |
| `/public/php/trainer/schedule.php` | My Schedule | ✅ Yes | Trainer | View assigned training sessions |
| `/public/php/trainer/profile.php` | Trainer Profile | ✅ Yes | Trainer | Edit personal trainer profile |
| `/public/php/trainer/feedback.php` | My Feedback | ✅ Yes | Trainer | View feedback from clients |
| **API ENDPOINTS** |
| `/public/php/api/get_available_trainers.php` | Trainer Availability API | ✅ Yes | Member | Check available trainers for date/session/class |
| `/public/php/api/get_user_bookings.php` | User Bookings API | ✅ Yes | Member | Fetch user's booking history with status |
| `/public/php/api/get_daily_bookings.php` | Daily Bookings API | ✅ Yes | Member | Get all bookings for specific date |
| `/public/php/check_session.php` | Session Check API | ✅ Yes | Any | Verify user session validity |
| `/public/php/extend_session.php` | Session Extend API | ✅ Yes | Any | Update last activity timestamp |
| `/public/php/logout.php` | Logout | ✅ Yes | Any | Destroy session, redirect to login |
| **RECEIPT GENERATION** |
| `/public/php/receipt_render.php` | Receipt Generator | ✅ Yes | Member | Generate PDF receipt via TCPDF |
| `/public/php/receipt_service.php` | Service Receipt | ✅ Yes | Member | Generate receipt for service bookings |
| `/public/php/receipt_nonmember.php` | Non-Member Receipt | ❌ No | Public | Receipt for non-member transactions |
| `/public/php/receipt_fallback.php` | Receipt Fallback | ✅ Yes | Member | HTML-based receipt if PDF fails |

**Path Constants** (defined in `/includes/config.php`):
```php
define('BASE_PATH', 'http://localhost/fit-brawl');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('IMAGES_PATH', PUBLIC_PATH . '/images');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
```

---

### Key User Flows

#### **1. User Signup & Verification Flow**

**Goal**: Register new user and verify email before granting access.

**Steps**:
1. **Registration** (`sign-up.php`)
   - User fills form: username, email, password, confirm password
   - Frontend validation: password strength (8+ chars, uppercase, lowercase, number)
   - Backend validation: check username/email uniqueness
   - Password hashed using `password_hash()` with `PASSWORD_DEFAULT` (bcrypt)
   - User inserted into `users` table with `is_verified=0`
   - 6-digit OTP generated and stored in `users.otp` column
   - OTP expiry set to 10 minutes (`otp_expiry` column)

2. **Email Dispatch** (PHPMailer)
   - OTP email sent using template from `includes/email_template.php`
   - Email contains OTP code and "Verify Email" link
   - SMTP configured via `includes/mail_config.php`

3. **Email Verification** (`verification.php`)
   - User enters 6-digit OTP from email
   - Frontend: JavaScript validates OTP format (numeric, 6 digits)
   - Backend checks:
     - OTP matches `users.otp`
     - OTP not expired (`otp_expiry > NOW()`)
     - Attempts not exceeded (max 5 attempts via `otp_attempts`)
   - On success:
     - Set `is_verified=1`
     - Clear OTP fields (`otp=NULL`, `otp_expiry=NULL`)
     - Auto-login user (create session)
     - Redirect to `loggedin-index.php`

4. **Resend OTP** (`resend-otp.php`)
   - Rate limiting: 1 resend per 60 seconds
   - Generate new OTP and extend expiry
   - Increment `otp_attempts` counter

**Error Handling**:
- Email already exists → Show error: "Email already registered"
- OTP expired → Show countdown timer + resend button
- Invalid OTP → Decrement attempts, show remaining tries
- Max attempts exceeded → Lock account temporarily (future feature)

---

#### **2. Trainer Booking (Reservations) Flow**

**Goal**: Members book 1-on-1 training sessions with available trainers.

**Prerequisites**:
- User must have active membership (`user_memberships.membership_status = 'active'`)
- Membership not expired (within grace period)
- Weekly booking limit not exceeded (default: 12 sessions/week)

**Steps**:

**Step 1: Select Date & Session** (`reservations.php`)
- Calendar display: Shows current month + next 2 months
- Date restrictions:
  - ❌ Past dates disabled (via CSS `.date-past`)
  - ❌ Dates with weekly limit reached disabled (`.all-sessions-unavailable`)
  - ✅ Only future dates selectable
- Session selection: Morning (7-11 AM), Afternoon (1-5 PM), Evening (6-10 PM)
- Weekly limit check:
  - Frontend JavaScript calculates bookings in selected week (Sunday-Saturday)
  - If 12 bookings already exist → Show warning, disable "Next" button
  - Warning: *"Weekly Booking Limit Reached: You've already booked 12 sessions..."*

**Step 2: Choose Class Type** (`reservations.js` line ~450)
- Radio button selection: Boxing, Muay Thai, MMA, Gym
- Each class type maps to trainer specializations:
  - Boxing → Trainers with `specialization='Boxing'`
  - MMA → `specialization='MMA'`
  - Muay Thai → `specialization='Muay Thai'`
  - Gym → `specialization='Gym'`

**Step 3: Select Trainer** (API call to `api/get_available_trainers.php`)
- AJAX request: `GET /api/get_available_trainers.php?date=2025-11-08&session=Morning&class=MMA`
- Backend logic (complex - see **Core System Logic** section below):
  - Query trainers with matching specialization
  - Filter by status: `status='Active'`, `deleted_at IS NULL`
  - **Availability checks** (order matters):
    1. ✅ Not on day off (`trainer_day_offs` table)
    2. ✅ Not blocked by admin (`trainer_availability_blocks` table)
    3. ✅ No existing booking for date/session (`user_reservations` table)
    4. ✅ Facility capacity check (max 2 trainers per session)
    5. ✅ Session hasn't ended or <30 minutes remaining
  - Return trainer list with status: `available`, `booked`, `unavailable`, `day_off`, `blocked`, `facility_full`
- Frontend display:
  - Available trainers → Green "Select" button
  - Unavailable trainers → Red badge with reason
  - If no trainers available → Show message: *"No trainers available for this date/session"*

**Step 4: Review & Confirm**
- Display summary:
  - Date: *"November 8, 2025 (Friday)"*
  - Session: *"Morning (7:00 AM - 11:00 AM)"*
  - Class: *"MMA"*
  - Trainer: *"John Doe"* (with photo)
- "Confirm Booking" button → Submit via AJAX
- Backend (`api/create_booking.php` - implied endpoint):
  - Insert into `user_reservations` table:
    ```sql
    INSERT INTO user_reservations (user_id, trainer_id, booking_date, session_time, class_type, booking_status)
    VALUES (?, ?, ?, ?, ?, 'confirmed')
    ```
  - Generate confirmation email with booking details
  - Log activity in `activity_log` table
- Success → Redirect to `transaction.php` with success toast

**Real-Time Status Updates** (JavaScript - `reservations.js` lines 900-1000):
- **Ongoing Session Detection**:
  - Checks if `booking_date = TODAY` AND current hour within session range
  - Morning: `currentHour >= 7 && currentHour < 11`
  - Afternoon: `currentHour >= 13 && currentHour < 17`
  - Evening: `currentHour >= 18 && currentHour < 22`
  - Display: Blue "Ongoing" badge with pulse animation
- **Completed Session**:
  - If `currentHour >= sessionEndHour` → "Completed" badge
  - Example: Evening session (6-10 PM) shows "Completed" at 10:00 PM+
- **Cancellation Window**:
  - Users can cancel up to 12 hours before session
  - Within 12 hours → "Cannot Cancel" badge (red)
  - After session ends → "Completed" (cannot cancel)

**Weekly Limit Logic** (`reservations.js` lines 100-150):
```javascript
function getWeekBoundaries(dateStr) {
  const date = new Date(dateStr + 'T00:00:00');
  const dayOfWeek = date.getDay(); // 0=Sunday, 6=Saturday
  const sunday = new Date(date);
  sunday.setDate(date.getDate() - dayOfWeek); // Calculate Sunday
  const saturday = new Date(sunday);
  saturday.setDate(sunday.getDate() + 6); // Calculate Saturday
  return { start: formatDate(sunday), end: formatDate(saturday) };
}

function getBookingCountForWeek(dateStr) {
  const weekBounds = getWeekBoundaries(dateStr);
  return allBookingsData.all.filter(booking => {
    return booking.status === 'confirmed' || booking.status === 'completed';
    return booking.date >= weekBounds.start && booking.date <= weekBounds.end;
  }).length;
}
```

---

#### **3. Membership Purchase & Approval Flow**

**Goal**: Users purchase membership plans and admins approve/reject requests.

**Steps**:

1. **Select Membership Plan** (`membership.php`)
   - Display available plans from `memberships` table
   - Plans: Monthly or Quarterly (each with different class types)
   - Class types: Boxing, Muay Thai, MMA, Gym, All Access
   - Price calculation: Frontend displays total cost
   - User clicks "Purchase" → Opens modal with terms & conditions

2. **Submit Membership Request**
   - User agrees to terms (checkbox required)
   - Form submits via POST to `membership.php`
   - Insert into `user_memberships` table:
     ```sql
     INSERT INTO user_memberships (user_id, membership_id, request_status, membership_status, date_submitted)
     VALUES (?, ?, 'pending', 'inactive', NOW())
     ```
   - Email notification sent to user: *"Your membership request is under review"*
   - Email notification sent to admin: *"New membership request from {username}"*
   - Redirect to `membership-status.php`

3. **Admin Review** (`admin/subscriptions.php`)
   - Admin views pending requests in table
   - Display: User info, plan selected, date submitted
   - Actions: "Approve" or "Reject" buttons
   - **On Approve**:
     - Update `request_status='approved'`, `membership_status='active'`
     - Set `start_date=TODAY`, `end_date=calculated based on plan`
     - Example: Monthly plan → `end_date = start_date + 30 days`
     - Send email: *"Your membership has been approved! Welcome to Fit and Brawl."*
     - Log action in `admin_logs` table
   - **On Reject**:
     - Update `request_status='rejected'`
     - Optional: Add rejection reason to `notes` field
     - Send email: *"Unfortunately, your membership request was not approved..."*

4. **User Access Changes** (`includes/header.php` lines 40-120)
   - **Before Approval**: Navbar shows "Membership" link → `membership.php`
   - **Pending Status**: Navbar shows "Status" link → `membership-status.php`
   - **After Approval**: Navbar shows "Schedule" link → `reservations.php`
   - Logic (header.php):
     ```php
     if ($hasActiveMembership) {
       $membershipLink = 'reservations.php';
       $membershipIcon = 'fa-calendar-alt';
       $membershipTitle = 'Schedule';
     } elseif ($hasAnyRequest) {
       $membershipLink = 'membership-status.php';
     } else {
       $membershipLink = 'membership.php';
     }
     ```

5. **Grace Period Handling**
   - Membership expires on `end_date`
   - Grace period: 3 days after expiry
   - During grace period: User can still book sessions (warning shown)
   - After grace period: Access revoked, redirected to `membership.php`

---

#### **4. Admin Trainer Schedule Management Flow**

**Goal**: Admins manage trainer availability via day-offs and session blocks.

**Steps**:

1. **Navigate to Trainer Schedules** (`admin/trainer_schedules.php`)
   - View all trainers in dropdown selector
   - Select trainer → Load their schedule calendar

2. **Mark Day Off**
   - Click on calendar date → Modal opens
   - Select "Full Day Off" option
   - Optional: Add reason (e.g., "Personal leave", "Sick day")
   - Submit → Insert into `trainer_day_offs` table:
     ```sql
     INSERT INTO trainer_day_offs (trainer_id, day_off_date, reason, created_by)
     VALUES (?, ?, ?, ?)
     ```
   - Trainer marked unavailable for entire day (all 3 sessions)
   - Log action in `trainer_activity_log`

3. **Block Specific Session**
   - Click on date → Modal with session checkboxes (Morning, Afternoon, Evening)
   - Select sessions to block
   - Add reason (e.g., "Administrative work", "Training certification")
   - Submit → Insert into `trainer_availability_blocks`:
     ```sql
     INSERT INTO trainer_availability_blocks (trainer_id, block_date, session_time, reason, created_by)
     VALUES (?, ?, 'Morning', ?, ?)
     ```
   - Trainer unavailable only for blocked sessions
   - Other sessions remain bookable

4. **View Existing Blocks**
   - Calendar displays visual indicators:
     - 🔴 Red dates → Full day off
     - 🟡 Yellow dates → Partial blocks (some sessions unavailable)
   - Click date → View list of blocks with "Delete" option
   - Delete block → Remove from table, restore trainer availability

5. **Impact on Booking System**
   - When user tries to book affected date/session:
     - API `get_available_trainers.php` filters out blocked trainers
     - Frontend shows trainer as "Unavailable - Scheduled Off"
     - User cannot select that trainer

---

### Global Navigation Logic

#### **Navbar State Management** (`includes/header.php`)

The navigation bar dynamically adapts based on user authentication status and role. Below is the state management logic:

**Navigation States**:

| User State | Navbar Links | Profile Dropdown | Special Behavior |
|-----------|--------------|------------------|------------------|
| **Not Logged In** | Home, Membership, Equipment, Products, Contact, Feedback | ❌ None | "Membership" → `membership.php` (login required) |
| **Logged In (No Membership)** | Home, Membership, Equipment, Products, Contact, Feedback | ✅ Profile, Change Password, Logout | "Membership" → `membership.php` (purchase page) |
| **Logged In (Pending Membership)** | Home, Status, Equipment, Products, Contact, Feedback | ✅ Profile, Change Password, Logout | "Status" → `membership-status.php` (shows request status) |
| **Logged In (Active Membership)** | Home, Schedule, Equipment, Products, Contact, Feedback | ✅ Profile, Change Password, Logout | "Schedule" → `reservations.php` (booking wizard) |
| **Admin** | Dashboard, Users, Trainers, Reservations, Equipment, Products, Feedback | ✅ Profile, Admin Panel, Logout | Additional "Admin" link in dropdown |
| **Trainer** | Dashboard, My Schedule, My Profile, My Feedback | ✅ Profile, Logout | Limited access to trainer-specific pages |

**Dynamic Icon & Title** (header.php lines 127-135):
```php
if ($hasActiveMembership) {
  $membershipIcon = 'fa-calendar-alt'; // Calendar icon
  $membershipTitle = 'Schedule';
} else {
  $membershipIcon = 'fa-id-card'; // Membership card icon
  $membershipTitle = 'Membership';
}
```

**Avatar Display Logic**:
```php
// Default avatar
$avatarSrc = IMAGES_PATH . '/account-icon.svg';

// Custom avatar (if uploaded)
if ($_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar'])) {
  $avatarSrc = UPLOADS_PATH . "/avatars/" . $_SESSION['avatar'];
}
```

**Mobile Menu Behavior** (`public/js/hamburger-menu.js`):
- Hamburger icon toggles on screen width < 768px
- Click hamburger → Slide-in menu animation
- Overlay darkens background when menu open
- Click outside menu → Close menu

**Session Timeout Warning** (`public/js/session-timeout.js`):
- Idle timeout: 15 minutes (900 seconds)
- Warning appears at 13 minutes (2 minutes before timeout)
- Modal: *"Your session will expire in 2:00. Click 'Stay Logged In' to continue."*
- Countdown timer updates every second
- "Stay Logged In" → AJAX call to `extend_session.php` (updates `last_activity`)
- If no action → Auto-logout at 15 minutes → Redirect to `login.php`

**Sticky Header** (CSS):
```css
header {
  position: sticky;
  top: 0;
  z-index: 1000;
  background: rgba(20, 20, 20, 0.95);
  backdrop-filter: blur(10px);
}
```

---

## ⚙️ Core System Logic & Architecture

### Data Model Overview

#### **Database Structure**

FitXBrawl uses a relational MySQL database with 21 primary tables organized into 5 logical modules:

**1. User Management Module**
- **`users`** (Primary user accounts)
  - Fields: `id`, `username`, `email`, `password` (bcrypt hashed), `role` (member/admin/trainer), `avatar`, `is_verified`, `otp`, `otp_expiry`, `created_at`
  - Relationships: 1:Many with `user_memberships`, `user_reservations`, `feedback`, `activity_log`
  
- **`remember_password`** (Password reset tokens)
  - Fields: `id`, `user_id`, `token_hash`, `created_at`
  - Foreign Key: `user_id` → `users.id` (CASCADE DELETE)

**2. Membership & Subscription Module**
- **`memberships`** (Available membership plans)
  - Fields: `id`, `plan_name` (Monthly/Quarterly/Yearly), `class_type` (Boxing/MMA/Muay Thai/Gym/All Access)
  - Static reference table (no foreign keys)

- **`user_memberships`** (User subscription records)
  - Fields: `id`, `user_id`, `membership_id`, `request_status` (pending/approved/rejected), `membership_status` (active/inactive/expired), `start_date`, `end_date`, `date_submitted`
  - Foreign Keys: 
    - `user_id` → `users.id`
    - `membership_id` → `memberships.id`
  - Business Logic: 
    - Active if `membership_status='active'` AND `end_date + 3 days >= TODAY`
    - Grace period: 3 days after `end_date`

**3. Trainer & Scheduling Module**
- **`trainers`** (Trainer profiles)
  - Fields: `id`, `name`, `email`, `phone`, `specialization` (Gym/MMA/Boxing/Muay Thai), `bio`, `photo`, `max_clients_per_day`, `status` (Active/Inactive/On Leave), `deleted_at` (soft delete)
  - Indexes: `idx_trainers_status`, `idx_trainers_specialization`, `idx_trainers_deleted_at`

- **`trainer_day_offs`** (Full day unavailability)
  - Fields: `id`, `trainer_id`, `day_off_date`, `reason`, `created_by`, `created_at`
  - Foreign Key: `trainer_id` → `trainers.id` (CASCADE DELETE)
  - Use Case: Mark trainer unavailable for entire day (all 3 sessions)

- **`trainer_availability_blocks`** (Session-specific blocks)
  - Fields: `id`, `trainer_id`, `block_date`, `session_time` (Morning/Afternoon/Evening), `reason`, `created_by`, `created_at`
  - Foreign Key: `trainer_id` → `trainers.id` (CASCADE DELETE)
  - Use Case: Block specific sessions (e.g., Morning only) while keeping others available

- **`trainer_activity_log`** (Audit trail)
  - Fields: `id`, `trainer_id`, `admin_id`, `action` (Added/Edited/Status Changed/Deleted), `details`, `timestamp`
  - Tracks all changes to trainer records for compliance

**4. Booking & Reservations Module**
- **`user_reservations`** (Training session bookings)
  - Fields: `id`, `user_id`, `trainer_id`, `booking_date`, `session_time` (Morning/Afternoon/Evening), `class_type`, `booking_status` (confirmed/completed/cancelled), `created_at`
  - Foreign Keys:
    - `user_id` → `users.id` (CASCADE DELETE)
    - `trainer_id` → `trainers.id` (SET NULL - preserve booking if trainer deleted)
  - Composite Index: `idx_booking_lookup` (`booking_date`, `session_time`, `trainer_id`) for fast availability checks
  - Weekly Limit: Max 12 bookings per user per week (enforced in application layer)

- **`member_service_bookings`** (Additional services)
  - Fields: `id`, `user_id`, `service_type`, `booking_date`, `status`, `price`, `receipt_path`
  - Services: Spa, Massage, Personal Training, Nutrition Consultation

- **`non_member_bookings`** (Public service bookings)
  - Fields: `id`, `guest_name`, `guest_email`, `service_type`, `booking_date`, `amount`, `receipt_path`
  - No user account required

**5. Content & Feedback Module**
- **`equipment`** (Gym equipment catalog)
  - Fields: `id`, `name`, `category`, `description`, `image`, `quantity`, `status` (Available/Maintenance/Out of Stock)

- **`products`** (Merchandise store)
  - Fields: `id`, `name`, `description`, `price`, `category`, `image`, `stock`, `created_at`

- **`feedback`** (User feedback system)
  - Fields: `id`, `user_id`, `message`, `rating` (1-5 stars), `status` (pending/reviewed), `created_at`
  - Foreign Key: `user_id` → `users.id`

- **`feedback_votes`** (Voting system)
  - Fields: `id`, `feedback_id`, `user_id`, `vote_type` (upvote/downvote)
  - Composite Unique: (`feedback_id`, `user_id`) - one vote per user per feedback

**6. Administrative & Logging Module**
- **`activity_log`** (System-wide audit trail)
  - Fields: `id`, `user_id`, `action`, `details`, `ip_address`, `timestamp`
  - Logs: Login attempts, profile updates, booking actions, admin operations

- **`admin_logs`** (Admin-specific actions)
  - Fields: `id`, `admin_id`, `action`, `target_table`, `target_id`, `details`, `timestamp`
  - Tracks: User modifications, membership approvals, content management

- **`contact`** (Contact form submissions)
  - Fields: `id`, `name`, `email`, `subject`, `message`, `status` (new/responded), `created_at`

#### **Entity Relationship Diagram (Textual)**

```
users (1) ──┬─── (M) user_memberships ──── (1) memberships
            ├─── (M) user_reservations ──── (1) trainers
            ├─── (M) feedback ──┬─── (M) feedback_votes
            ├─── (M) activity_log
            └─── (M) member_service_bookings

trainers (1) ──┬─── (M) user_reservations
               ├─── (M) trainer_day_offs
               ├─── (M) trainer_availability_blocks
               └─── (M) trainer_activity_log

Legend:
(1) = One
(M) = Many
──── = Foreign Key Relationship
```

---

### Authentication Logic

#### **Authentication Strategy**

FitXBrawl uses **session-based authentication** with enhanced security features:

**1. Password Security**
- **Hashing Algorithm**: bcrypt (via `password_hash()` with `PASSWORD_DEFAULT`)
- **Cost Factor**: Default (currently 10 rounds, auto-adjusts with PHP updates)
- **Verification**: `password_verify()` for constant-time comparison (prevents timing attacks)

**2. Session Management** (`includes/session_manager.php`)

**SessionManager Class** - Centralized session handling:

```php
class SessionManager {
  const IDLE_TIMEOUT = 900;      // 15 minutes (in seconds)
  const WARNING_TIME = 120;       // 2 minutes warning before timeout
  const ABSOLUTE_TIMEOUT = 36000; // 10 hours max session duration

  public static function initialize() {
    // Security configuration (Firefox compatibility)
    ini_set('session.use_strict_mode', '1');      // Prevent session fixation
    ini_set('session.cookie_httponly', '1');      // Prevent XSS access to cookie
    ini_set('session.use_only_cookies', '1');     // No URL-based session IDs
    ini_set('session.cookie_samesite', 'Lax');    // CSRF protection

    session_set_cookie_params([
      'lifetime' => 0,        // Session cookie (expires on browser close)
      'path' => '/',
      'domain' => '',
      'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // HTTPS only in production
      'httponly' => true,
      'samesite' => 'Lax'
    ]);

    session_start();

    // Regenerate session ID on first access (prevents session fixation)
    if (!isset($_SESSION['initiated'])) {
      session_regenerate_id(true);
      $_SESSION['initiated'] = true;
    }
  }

  public static function startSession($email) {
    $_SESSION['email'] = $email;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['session_expires'] = time() + self::IDLE_TIMEOUT;
  }

  public static function updateActivity() {
    if (self::isLoggedIn() && !self::isAbsoluteTimeoutReached()) {
      $_SESSION['last_activity'] = time();
      $_SESSION['session_expires'] = time() + self::IDLE_TIMEOUT;
      return self::getRemainingTime();
    }
    return 0;
  }

  private static function isIdleTimeoutReached() {
    return !isset($_SESSION['last_activity']) ||
           (time() - $_SESSION['last_activity']) >= self::IDLE_TIMEOUT;
  }

  private static function isAbsoluteTimeoutReached() {
    return !isset($_SESSION['login_time']) ||
           (time() - $_SESSION['login_time']) >= self::ABSOLUTE_TIMEOUT;
  }

  public static function logout($message = '') {
    $sessionName = session_name();
    $_SESSION = array(); // Clear all session data
    
    // Delete session cookie
    if (isset($_COOKIE[$sessionName])) {
      setcookie($sessionName, '', time() - 3600, '/');
    }
    
    session_destroy();
  }
}
```

**3. Login Process** (`public/php/login.php`)

**Step-by-Step Logic**:

1. **Request Validation**
   - Method: POST only
   - CSRF token verification (if implemented)
   - Rate limiting: Max 5 login attempts per IP per 5 minutes (via `rate_limiter.php`)

2. **Input Sanitization**
   ```php
   $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
   $password = $_POST['password'];
   ```

3. **Database Query**
   ```php
   $stmt = $conn->prepare("SELECT id, username, email, password, role, avatar, is_verified 
                           FROM users WHERE email = ? AND deleted_at IS NULL");
   $stmt->bind_param("s", $email);
   $stmt->execute();
   $result = $stmt->get_result();
   ```

4. **User Verification Checks**
   - User exists? → `$result->num_rows > 0`
   - Email verified? → `is_verified == 1`
   - Password correct? → `password_verify($password, $row['password'])`

5. **Session Creation** (on success)
   ```php
   SessionManager::startSession($email);
   $_SESSION['user_id'] = $row['id'];
   $_SESSION['username'] = $row['username'];
   $_SESSION['role'] = $row['role'];
   $_SESSION['avatar'] = $row['avatar'];
   ```

6. **Activity Logging**
   ```php
   $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, ip_address, timestamp) 
                               VALUES (?, 'login', ?, NOW())");
   $log_stmt->bind_param("is", $user_id, $_SERVER['REMOTE_ADDR']);
   $log_stmt->execute();
   ```

7. **Role-Based Redirect**
   - Admin → `admin/admin.php`
   - Trainer → `trainer/index.php`
   - Member → `loggedin-index.php`

**4. Session Timeout Handling** (Frontend: `public/js/session-timeout.js`)

```javascript
let sessionTimeout;
let warningTimeout;
const IDLE_TIMEOUT = 900000; // 15 minutes in milliseconds
const WARNING_TIME = 780000;  // 13 minutes (show warning 2 min before)

function resetSessionTimer() {
  clearTimeout(sessionTimeout);
  clearTimeout(warningTimeout);
  
  // Extend session via AJAX
  fetch('extend_session.php', { method: 'POST' })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        warningTimeout = setTimeout(showWarningModal, WARNING_TIME);
        sessionTimeout = setTimeout(forceLogout, IDLE_TIMEOUT);
      }
    });
}

// Reset timer on user activity
document.addEventListener('mousemove', resetSessionTimer);
document.addEventListener('keydown', resetSessionTimer);
document.addEventListener('click', resetSessionTimer);
```

**5. Access Control** (Page-Level Protection)

**Example**: `reservations.php` (requires active membership)
```php
require_once '../../includes/session_manager.php';
require_once '../../includes/membership_check.php';

SessionManager::initialize();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
  header('Location: login.php');
  exit;
}

// Check for active membership
$membership_check = check_active_membership($_SESSION['user_id']);
if (!$membership_check['has_membership']) {
  header('Location: membership.php?error=no_membership');
  exit;
}

// Grace period warning
if ($membership_check['in_grace_period']) {
  $grace_warning = "Your membership expires in " . $membership_check['days_remaining'] . " days. Please renew.";
}
```

**6. CSRF Protection** (`includes/csrf_protection.php`)

```php
function generate_csrf_token() {
  if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Usage in forms:
<input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

// Usage in POST handlers:
if (!verify_csrf_token($_POST['csrf_token'])) {
  die('CSRF token validation failed');
}
```

**7. Remember Me** (Future Feature - Placeholder)

```php
// Will use remember_password table
// Token stored as hash in database
// Cookie with long expiry (30 days)
// Token rotation on each use
```

---

### State Management

FitXBrawl uses **server-side session storage** for state management with minimal frontend state. Below are the key state structures:

#### **1. User Session State** (`$_SESSION` global array)

```php
$_SESSION = [
  // Authentication
  'user_id' => 123,
  'username' => 'johndoe',
  'email' => 'john@example.com',
  'role' => 'member',  // 'member', 'admin', 'trainer'
  'avatar' => 'user_123_avatar.jpg',
  'is_verified' => 1,
  
  // Session Tracking
  'login_time' => 1699459200,       // Unix timestamp
  'last_activity' => 1699459800,    // Unix timestamp
  'session_expires' => 1699460700,  // Unix timestamp (15 min from last activity)
  'initiated' => true,              // Session ID regenerated flag
  
  // CSRF Protection
  'csrf_token' => '8f7d6a5b4c3e2f1a9b8c7d6e5f4a3b2c1d0e9f8a7b6c5d4e3f2a1b0c9d8e7f6a',
  
  // Flash Messages (temporary data)
  'flash_success' => 'Booking confirmed successfully!',
  'flash_error' => 'Invalid email or password',
  
  // Temporary Data (cleared on use)
  'pending_booking' => [
    'date' => '2025-11-08',
    'session' => 'Morning',
    'class' => 'MMA',
    'trainer_id' => 5
  ]
];
```

#### **2. Frontend Booking State** (`reservations.js`)

JavaScript object maintaining wizard state:

```javascript
const bookingState = {
  // Wizard Data
  date: '2025-11-08',           // Selected date (YYYY-MM-DD)
  session: 'Morning',           // 'Morning', 'Afternoon', 'Evening'
  classType: 'MMA',             // 'Boxing', 'Muay Thai', 'MMA', 'Gym'
  trainerId: 5,                 // Selected trainer ID
  trainerName: 'John Doe',      // Trainer name (for display)
  currentStep: 3,               // Wizard step (1-4)
  
  // Availability Tracking
  facilityFull: false,          // Facility capacity reached (2 trainers max)
  hasAvailableTrainers: true,   // At least one trainer available
  
  // Weekly Limit Tracking
  weeklyLimit: 12,              // Max bookings per week
  currentWeekCount: 8,          // Bookings in current week (Sun-Sat)
  currentWeekRemaining: 4,      // Remaining bookings in current week
  selectedWeekCount: 3,         // Bookings in selected week
  selectedWeekFull: false       // Selected week at limit?
};
```

**State Persistence**:
- Session state persists across page loads (server-side)
- Frontend state resets on page reload (intentional - no localStorage)
- Booking wizard state cleared after successful booking

#### **3. Admin Dashboard State** (`admin/admin.php`)

```php
// Fetched on page load, stored temporarily in PHP variables
$dashboard_data = [
  'total_users' => 245,
  'active_memberships' => 189,
  'pending_memberships' => 12,
  'total_trainers' => 8,
  'todays_bookings' => 23,
  'revenue_this_month' => 45000.00,
  
  'recent_activities' => [
    ['user' => 'Jane Doe', 'action' => 'Booked session', 'time' => '5 minutes ago'],
    ['user' => 'Admin', 'action' => 'Approved membership', 'time' => '12 minutes ago']
  ],
  
  'system_alerts' => [
    ['type' => 'warning', 'message' => 'Trainer John Doe has no availability this week'],
    ['type' => 'info', 'message' => '3 memberships expiring in next 7 days']
  ]
];
```

---

### Key Logic Breakdown: Trainer Availability Check System

**Feature**: Real-time trainer availability validation for booking system  
**Complexity**: High (multiple validation layers, time-based logic, facility constraints)  
**Location**: `/public/php/api/get_available_trainers.php` + `/includes/booking_validator.php`

---

#### **Inputs**

**API Request (GET parameters)**:
```
/api/get_available_trainers.php?date=2025-11-08&session=Morning&class=MMA
```

- `date`: Target booking date (YYYY-MM-DD format)
- `session`: Time slot (`'Morning'`, `'Afternoon'`, `'Evening'`)
- `class`: Training class type (`'Boxing'`, `'Muay Thai'`, `'MMA'`, `'Gym'`)

**Session Data**:
- `$_SESSION['user_id']`: Current user ID (for existing booking check)

**Database Tables Referenced**:
- `trainers` - Trainer profiles and specializations
- `trainer_day_offs` - Full-day unavailability records
- `trainer_availability_blocks` - Session-specific blocks
- `user_reservations` - Existing bookings

---

#### **Processing Steps** (Detailed Logic)

**STEP 1: Request Validation**

```php
// Validate HTTP method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  return error('Invalid request method');
}

// Validate required parameters exist
if (empty($date) || empty($session_time) || empty($class_type)) {
  return error('Missing required parameters');
}

// Validate session_time value
$valid_sessions = ['Morning', 'Afternoon', 'Evening'];
if (!in_array($session_time, $valid_sessions)) {
  return error('Invalid session time');
}

// Validate class_type value
$valid_classes = ['Boxing', 'Muay Thai', 'MMA', 'Gym'];
if (!in_array($class_type, $valid_classes)) {
  return error('Invalid class type');
}

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
  return error('Invalid date format');
}
```

**STEP 2: Date Validation** (via `BookingValidator` class)

```php
$validator = new BookingValidator($conn);
$date_check = $validator->validateBookingDate($date);

// validateBookingDate() logic:
function validateBookingDate($date) {
  $today = date('Y-m-d');
  $max_future_date = date('Y-m-d', strtotime('+90 days')); // 3 months ahead
  
  // Cannot book past dates
  if ($date < $today) {
    return ['valid' => false, 'message' => 'Cannot book sessions in the past'];
  }
  
  // Cannot book too far in future
  if ($date > $max_future_date) {
    return ['valid' => false, 'message' => 'Bookings available up to 3 months in advance'];
  }
  
  return ['valid' => true];
}
```

**STEP 3: Session Time Cutoff Check** (30-Minute Rule)

```php
$session_end_times = [
  'Morning' => '11:00:00',      // 7:00 AM - 11:00 AM
  'Afternoon' => '17:00:00',    // 1:00 PM - 5:00 PM
  'Evening' => '22:00:00'       // 6:00 PM - 10:00 PM
];

$today = date('Y-m-d');
$now = date('H:i:s');

// Only check if booking is for TODAY
if ($date === $today) {
  $session_end = $session_end_times[$session_time];
  $end_time = strtotime($date . ' ' . $session_end);
  $current_time = strtotime($date . ' ' . $now);
  
  // Calculate minutes remaining until session ends
  $minutes_remaining = ($end_time - $current_time) / 60;
  
  // Reject if less than 30 minutes left
  if ($minutes_remaining < 30) {
    return error([
      'success' => false,
      'message' => 'Cannot book this session. Less than 30 minutes remaining before session ends.',
      'time_cutoff' => true  // Flag for frontend to show specific error
    ]);
  }
}
```

**Example**:
- Current time: 10:35 AM
- Booking: Morning session (ends 11:00 AM)
- Minutes remaining: `(11:00 - 10:35) = 25 minutes`
- Result: ❌ **REJECTED** (< 30 minutes)

**STEP 4: Facility Capacity Check**

```php
// Facility can accommodate max 2 trainers per session
$facility_slots_max = 2;

$facility_check = $validator->validateFacilityCapacity($class_type, $date, $session_time);

// validateFacilityCapacity() logic:
function validateFacilityCapacity($class_type, $date, $session_time) {
  $query = "SELECT COUNT(*) as count FROM user_reservations 
            WHERE booking_date = ? 
            AND session_time = ? 
            AND class_type = ? 
            AND booking_status = 'confirmed'";
  
  $stmt = $conn->prepare($query);
  $stmt->bind_param("sss", $date, $session_time, $class_type);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  
  return [
    'count' => $result['count'],
    'available' => $result['count'] < 2  // Max 2 trainers
  ];
}

$facility_slots_used = $facility_check['count'];
$facility_available = $facility_check['available'];
```

**STEP 5: Query All Matching Trainers**

```php
$query = "
  SELECT t.id, t.name, t.specialization, t.photo, t.status
  FROM trainers t
  WHERE t.specialization = ?          -- Match requested class type
  AND t.deleted_at IS NULL            -- Not soft-deleted
  AND t.status = 'Active'             -- Active status only
  ORDER BY t.name
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $class_type);  // $class_type = 'MMA'
$stmt->execute();
$result = $stmt->get_result();
```

**STEP 6: Per-Trainer Availability Validation**

For each trainer returned from Step 5, perform cascading availability checks:

```php
$trainers = [];

while ($row = $result->fetch_assoc()) {
  $trainer_id = $row['id'];
  $trainer_status = 'available';  // Default assumption
  $unavailable_reason = null;
  
  // ===== CHECK 1: Day Off =====
  $dayoff_check = $validator->validateDayOff($trainer_id, $date);
  
  // validateDayOff() logic:
  function validateDayOff($trainer_id, $date) {
    $query = "SELECT id FROM trainer_day_offs 
              WHERE trainer_id = ? AND day_off_date = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $trainer_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
      return ['valid' => false, 'reason' => 'Trainer has a scheduled day off'];
    }
    return ['valid' => true];
  }
  
  if (!$dayoff_check['valid']) {
    $trainer_status = 'unavailable';
    $unavailable_reason = 'day_off';
    // ❌ FAIL: Trainer marked as unavailable, skip remaining checks
  }
  
  // ===== CHECK 2: Admin Block (only if passed CHECK 1) =====
  if ($trainer_status === 'available') {
    $block_check = $validator->validateAdminBlock($trainer_id, $date, $session_time);
    
    // validateAdminBlock() logic:
    function validateAdminBlock($trainer_id, $date, $session_time) {
      $query = "SELECT id FROM trainer_availability_blocks 
                WHERE trainer_id = ? 
                AND block_date = ? 
                AND session_time = ?";
      $stmt = $conn->prepare($query);
      $stmt->bind_param("iss", $trainer_id, $date, $session_time);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result->num_rows > 0) {
        return ['valid' => false, 'reason' => 'Trainer unavailable - administrative block'];
      }
      return ['valid' => true];
    }
    
    if (!$block_check['valid']) {
      $trainer_status = 'unavailable';
      $unavailable_reason = 'blocked';
      // ❌ FAIL: Admin blocked this specific session
    }
  }
  
  // ===== CHECK 3: Existing Booking (only if passed CHECK 1 & 2) =====
  if ($trainer_status === 'available') {
    $availability_check = $validator->validateTrainerAvailability($trainer_id, $date, $session_time);
    
    // validateTrainerAvailability() logic:
    function validateTrainerAvailability($trainer_id, $date, $session_time) {
      $query = "SELECT id FROM user_reservations 
                WHERE trainer_id = ? 
                AND booking_date = ? 
                AND session_time = ? 
                AND booking_status = 'confirmed'";
      $stmt = $conn->prepare($query);
      $stmt->bind_param("iss", $trainer_id, $date, $session_time);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result->num_rows > 0) {
        return ['valid' => false, 'reason' => 'Trainer already booked for this session'];
      }
      return ['valid' => true];
    }
    
    if (!$availability_check['valid']) {
      $trainer_status = 'booked';
      $unavailable_reason = 'already_booked';
      // ❌ FAIL: Trainer has existing booking
    }
  }
  
  // ===== CHECK 4: Facility Capacity (only if passed all above) =====
  if ($trainer_status === 'available' && !$facility_available) {
    $trainer_status = 'unavailable';
    $unavailable_reason = 'facility_full';
    // ❌ FAIL: Facility at capacity (2 trainers already booked)
  }
  
  // ===== Build Trainer Object =====
  $trainers[] = [
    'id' => $trainer_id,
    'name' => $row['name'],
    'specialization' => $row['specialization'],
    'photo' => $row['photo'] ? UPLOADS_PATH . "/trainers/" . $row['photo'] : null,
    'status' => $trainer_status,  // 'available', 'booked', 'unavailable'
    'unavailable_reason' => $unavailable_reason  // 'day_off', 'blocked', 'already_booked', 'facility_full'
  ];
}
```

**Validation Cascade Visual**:
```
Trainer Query Result
    ↓
[CHECK 1] Day Off?
    ├─ ❌ YES → Status: unavailable (day_off) → STOP
    └─ ✅ NO → Continue
         ↓
[CHECK 2] Admin Block?
    ├─ ❌ YES → Status: unavailable (blocked) → STOP
    └─ ✅ NO → Continue
         ↓
[CHECK 3] Existing Booking?
    ├─ ❌ YES → Status: booked (already_booked) → STOP
    └─ ✅ NO → Continue
         ↓
[CHECK 4] Facility Full?
    ├─ ❌ YES → Status: unavailable (facility_full) → STOP
    └─ ✅ NO → Status: available ✅
```

---

#### **Outputs**

**JSON Response Format**:

```json
{
  "success": true,
  "trainers": [
    {
      "id": 5,
      "name": "John Doe",
      "specialization": "MMA",
      "photo": "/uploads/trainers/trainer_5.jpg",
      "status": "available",
      "unavailable_reason": null
    },
    {
      "id": 8,
      "name": "Jane Smith",
      "specialization": "MMA",
      "photo": "/uploads/trainers/trainer_8.jpg",
      "status": "unavailable",
      "unavailable_reason": "day_off"
    },
    {
      "id": 12,
      "name": "Mike Johnson",
      "specialization": "MMA",
      "photo": "/uploads/trainers/trainer_12.jpg",
      "status": "booked",
      "unavailable_reason": "already_booked"
    }
  ],
  "facility_status": {
    "slots_used": 1,
    "slots_max": 2,
    "available": true
  },
  "session_info": {
    "date": "2025-11-08",
    "session_time": "Morning",
    "class_type": "MMA"
  }
}
```

**Error Response Examples**:

```json
// Past date
{
  "success": false,
  "message": "Cannot book sessions in the past"
}

// Time cutoff
{
  "success": false,
  "message": "Cannot book this session. Less than 30 minutes remaining before session ends.",
  "time_cutoff": true
}

// No trainers available
{
  "success": true,
  "trainers": [],
  "message": "No trainers available for this date and session"
}
```

---

#### **Frontend Integration** (`reservations.js`)

```javascript
// Fetch available trainers when user selects class type
function loadTrainers() {
  const date = bookingState.date;
  const session = bookingState.session;
  const classType = bookingState.classType;
  
  fetch(`api/get_available_trainers.php?date=${date}&session=${session}&class=${classType}`)
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        // Handle time cutoff error
        if (data.time_cutoff) {
          showToast('This session is about to end. Please select a different session.', 'warning');
        } else {
          showToast(data.message, 'error');
        }
        return;
      }
      
      // Render trainer cards
      const trainerContainer = document.getElementById('trainerList');
      trainerContainer.innerHTML = '';
      
      data.trainers.forEach(trainer => {
        const card = createTrainerCard(trainer);
        trainerContainer.appendChild(card);
      });
      
      // Check if any trainers are available
      const hasAvailable = data.trainers.some(t => t.status === 'available');
      if (!hasAvailable) {
        showToast('No trainers available for this date/session. Try a different time.', 'info');
      }
    });
}

function createTrainerCard(trainer) {
  const card = document.createElement('div');
  card.className = 'trainer-card';
  
  if (trainer.status === 'available') {
    card.innerHTML = `
      <img src="${trainer.photo}" alt="${trainer.name}">
      <h3>${trainer.name}</h3>
      <p class="specialization">${trainer.specialization}</p>
      <button class="btn-select" onclick="selectTrainer(${trainer.id}, '${trainer.name}')">
        Select Trainer
      </button>
    `;
  } else {
    // Unavailable trainer - show reason
    const reasonText = {
      'day_off': 'Day Off',
      'blocked': 'Unavailable',
      'already_booked': 'Fully Booked',
      'facility_full': 'Facility Full'
    }[trainer.unavailable_reason] || 'Unavailable';
    
    card.classList.add('trainer-unavailable');
    card.innerHTML = `
      <img src="${trainer.photo}" alt="${trainer.name}" style="opacity: 0.5;">
      <h3>${trainer.name}</h3>
      <p class="specialization">${trainer.specialization}</p>
      <span class="badge badge-unavailable">${reasonText}</span>
    `;
  }
  
  return card;
}
```

---

#### **Performance Considerations**

- **Database Indexes**: `idx_booking_lookup` on (`booking_date`, `session_time`, `trainer_id`) speeds up availability queries
- **Query Optimization**: Single query fetches all trainers, then individual checks (vs. N+1 query problem)
- **Caching Strategy**: Response can be cached for 5 minutes (future enhancement)
- **Scalability**: Current design handles up to 50 trainers efficiently; beyond that, consider pagination

---

**Project Status**: Active Development 🚀  
**Last Updated**: November 8, 2025  
**Contributors**: Fit and Brawl Development Team

[Back to Top ⬆](#fitxbrawl-gym-scheduling-management-website)
# Auto-deployment test
# Testing auto-deployment with secrets
# Final deployment test
# Test deployment with GitHub Secrets configured
