-- =============================================
-- Fit & Brawl Gym Database Schema
-- =============================================
-- This schema is designed for a gym management system
-- supporting memberships, reservations, equipment tracking,
-- products, and user management.
--
-- DEPLOYMENT STEPS:
--   1. Run this schema.sql file to create database structure
--   2. Run seed.sql for basic data (users, memberships, equipment, products)
--   3. Run seed_trainer_schedules.sql for complete Nov-Dec 2025 schedule
--
-- See docs/database/SCHEDULE_README.md for detailed instructions
-- =============================================

-- Create database
CREATE DATABASE IF NOT EXISTS fit_and_brawl_gym;
USE fit_and_brawl_gym;

-- =====================
-- USERS TABLE
-- Stores user accounts with authentication and verification
-- =====================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL COMMENT 'Hashed password using bcrypt',
    role ENUM('member', 'admin', 'trainer') DEFAULT 'member',
    avatar VARCHAR(255) DEFAULT 'default-avatar.png',
    is_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255) DEFAULT NULL,
    otp VARCHAR(6) DEFAULT NULL,
    otp_expiry DATETIME DEFAULT NULL,
    otp_attempts INT DEFAULT 0,
    last_otp_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_logout DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- =====================
-- REMEMBER PASSWORD TOKENS TABLE
-- =====================
CREATE TABLE remember_password (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =====================
-- MEMBERSHIPS TABLE
-- =====================
CREATE TABLE memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(50) NOT NULL,
    class_type VARCHAR(100) NOT NULL
);

-- =====================
-- TRAINERS TABLE
-- Stores trainer information with status and soft delete support
-- =====================
CREATE TABLE trainers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    specialization ENUM('Gym', 'MMA', 'Boxing', 'Muay Thai') NOT NULL,
    bio TEXT DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    emergency_contact_name VARCHAR(100) DEFAULT NULL,
    emergency_contact_phone VARCHAR(20) DEFAULT NULL,
    max_clients_per_day INT DEFAULT 3,
    status ENUM('Active', 'Inactive', 'On Leave') DEFAULT 'Active' NOT NULL,
    password_changed TINYINT(1) DEFAULT 0 COMMENT 'Whether trainer has changed their default password',
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_trainers_status (status),
    INDEX idx_trainers_deleted_at (deleted_at),
    INDEX idx_trainers_specialization (specialization)
);

-- =====================
-- TRAINER ACTIVITY LOG TABLE
-- Tracks all changes to trainer records for audit purposes
-- =====================
CREATE TABLE trainer_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id INT NOT NULL,
    admin_id INT DEFAULT NULL,
    action VARCHAR(50) NOT NULL COMMENT 'Type of action: Added, Edited, Status Changed, Deleted',
    details TEXT DEFAULT NULL COMMENT 'Description of what changed',
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_trainer_activity_trainer (trainer_id),
    INDEX idx_trainer_activity_timestamp (timestamp DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Audit log for trainer management actions';

-- =====================
-- MEMBERSHIP_TRAINERS TABLE
-- Links memberships to their assigned trainers (many-to-many)
-- =====================
CREATE TABLE membership_trainers (
    membership_id INT NOT NULL,
    trainer_id INT NOT NULL,
    FOREIGN KEY (membership_id) REFERENCES memberships(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE,
    PRIMARY KEY (membership_id, trainer_id)
);

-- =====================
-- USER MEMBERSHIPS TABLE (COMBINED)
-- Merges subscription requests and active membership records
-- =====================
CREATE TABLE user_memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    permanent_address VARCHAR(255) DEFAULT NULL,
    plan_id INT DEFAULT NULL,
    plan_name VARCHAR(100) DEFAULT NULL,
    duration INT DEFAULT NULL COMMENT 'Duration in days (used to compute end_date if needed)',
    qr_proof VARCHAR(255) DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    date_submitted DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_approved DATETIME DEFAULT NULL,
    remarks VARCHAR(255) DEFAULT NULL,
    request_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    billing_type ENUM('monthly','yearly') DEFAULT 'monthly',
    membership_status ENUM('active','expired','cancelled') DEFAULT NULL,
    source_table ENUM('user_memberships','subscriptions') DEFAULT NULL,
    source_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES memberships(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
);


-- =====================
-- RESERVATIONS TABLE
-- =====================

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id INT NOT NULL,
    class_type ENUM('Boxing', 'Muay Thai', 'MMA', 'Gym') NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_slots INT NOT NULL DEFAULT 1 COMMENT 'Each session has 1 slot per trainer',
    status ENUM('available', 'full', 'cancelled') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session (trainer_id, class_type, date, start_time),
    INDEX idx_reservations_date (date),
    INDEX idx_reservations_trainer_date (trainer_id, date),
    INDEX idx_reservations_class_date (class_type, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Training sessions with 1 booking slot each';

-- =====================
-- USER RESERVATIONS TABLE
-- Links users to their booked training sessions
-- =====================
CREATE TABLE user_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reservation_id INT NOT NULL,
    booking_status ENUM('confirmed', 'cancelled', 'completed') DEFAULT 'confirmed',
    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_booking (user_id, reservation_id, booking_status) COMMENT 'User can rebook after cancellation',
    INDEX idx_user_bookings (user_id, booking_status),
    INDEX idx_reservation_bookings (reservation_id, booking_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User training session bookings';

-- =====================
-- EQUIPMENT TABLE
-- Tracks gym equipment and their maintenance status
-- =====================
CREATE TABLE equipment (
    id INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(100) NOT NULL,
    status ENUM('Available', 'Maintenance', 'Out of Order') DEFAULT 'Available',
    description VARCHAR(255) DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


-- =====================
-- PRODUCTS TABLE (Consumables Only)
-- =====================
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    stock INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'out of stock',
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =====================
-- FEEDBACK TABLE
-- Stores user feedback and reviews
-- =====================
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default-avatar.png',
    message TEXT NOT NULL,
    is_visible TINYINT(1) DEFAULT 1 COMMENT 'Admin can hide/show feedback',
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================
-- ADMIN ACTION LOGS TABLE
-- =====================
CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL COMMENT 'ID of admin who performed the action',
    admin_name VARCHAR(100) NOT NULL COMMENT 'Name of the admin user',
    action_type VARCHAR(50) NOT NULL COMMENT 'Type of action (subscription_approved, equipment_add, etc.)',
    target_user VARCHAR(100) DEFAULT NULL COMMENT 'Name of user/member affected by action',
    target_id INT DEFAULT NULL COMMENT 'ID of the record that was affected',
    details TEXT DEFAULT NULL COMMENT 'Detailed description of the action',
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'When the action occurred',

    -- Indexes for faster queries
    INDEX idx_admin_id (admin_id),
    INDEX idx_action_type (action_type),
    INDEX idx_timestamp (timestamp DESC),
    INDEX idx_target_id (target_id),

    -- Foreign key to users table
    CONSTRAINT fk_admin_logs_user FOREIGN KEY (admin_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks all admin actions in the system';

-- =====================
-- ACTIVITY LOGS TABLE
-- =====================
CREATE TABLE activity_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  role ENUM('member', 'trainer', 'admin') NOT NULL,
  action VARCHAR(50) NOT NULL,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =====================
-- CONTACT TABLE
-- =====================
CREATE TABLE contact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20),
    message TEXT NOT NULL,
    date_submitted TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================
-- TRAINING SESSIONS TABLE
-- Tracks individual training session attendance
-- =====================
CREATE TABLE training_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_date DATE NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    trainer_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================
-- INQUIRIES TABLE
-- Stores contact form submissions
-- =====================
CREATE TABLE inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    message TEXT NOT NULL,
    date_sent DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Unread', 'Read') DEFAULT 'Unread'
);

-- =====================
-- SERVICE BOOKINGS TABLES
-- Tracks service bookings for both members and non-members
-- =====================

-- For registered members
CREATE TABLE member_service_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_id VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    service_key VARCHAR(50) NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    is_member TINYINT(1) DEFAULT 0 COMMENT 'Whether user had active membership at time of booking',
    name VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    permanent_address VARCHAR(255) NOT NULL,
    service_date DATE NOT NULL,
    booking_date DATETIME NOT NULL,
    qr_proof VARCHAR(255) NOT NULL COMMENT 'Payment receipt filename',
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'confirmed',
    checked_in TINYINT(1) DEFAULT 0,
    checked_in_at DATETIME DEFAULT NULL,
    admin_notes TEXT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_receipt_id (receipt_id),
    INDEX (service_date),
    INDEX (status)
);

-- For non-members (walk-ins)
CREATE TABLE non_member_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_id VARCHAR(50) NOT NULL UNIQUE,
    service_key VARCHAR(50) NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    service_date DATE NOT NULL,
    booking_date DATETIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    checked_in TINYINT(1) DEFAULT 0,
    checked_in_at DATETIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    INDEX idx_service_date (service_date),
    INDEX idx_customer_email (customer_email)
);
