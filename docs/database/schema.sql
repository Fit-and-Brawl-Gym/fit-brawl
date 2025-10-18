-- Create database
CREATE DATABASE IF NOT EXISTS fit_and_brawl_gym;
USE fit_and_brawl_gym;

-- =====================
-- USERS TABLE
-- =====================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('member', 'admin', 'trainer') DEFAULT 'member',
    avatar VARCHAR(255) DEFAULT 'profile-icon.svg',
    otp VARCHAR(6) DEFAULT NULL,
    otp_expiry DATETIME DEFAULT NULL,
    otp_attempts INT DEFAULT 0,
    last_otp_request TIMESTAMP DEFAULT NULL
);
-- Add verification fields (safe as separate command)
ALTER TABLE users
ADD COLUMN is_verified TINYINT(1) DEFAULT 0 AFTER avatar,
ADD COLUMN verification_token VARCHAR(255) DEFAULT NULL AFTER is_verified;
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
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in days'
);

-- =====================
-- USER MEMBERSHIPS TABLE (COMBINED)
-- Merges subscription requests and active membership records
-- =====================
CREATE TABLE user_memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT DEFAULT NULL,
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
-- TRAINERS TABLE
-- =====================
CREATE TABLE trainers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    schedule TEXT
);

-- =====================
-- RESERVATIONS TABLE
-- =====================

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id INT NOT NULL,
    class_type ENUM('Boxing', 'Muay Thai', 'MMA') NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_slots INT NOT NULL DEFAULT 10,
    status ENUM('available', 'full', 'cancelled') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session (trainer_id, class_type, date, start_time)
);

-- =====================
-- USER RESERVATIONS TABLE (NEW)
-- =====================
CREATE TABLE user_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reservation_id INT NOT NULL,
    booking_status ENUM('confirmed', 'cancelled', 'completed') DEFAULT 'confirmed',
    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    class_type VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_slots INT NOT NULL DEFAULT 1,
    remaining_slots INT NOT NULL DEFAULT 1,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_booking (user_id, reservation_id)
);

-- =====================
-- EQUIPMENT TABLE
-- =====================
CREATE TABLE equipment (
    id INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    name VARCHAR(100) NOT NULL,
    status ENUM('Available', 'Maintenance', 'Out of Order') NOT NULL DEFAULT 'Available',
    category VARCHAR(50) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL
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
-- =====================
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'profile-icon.svg',
    message TEXT NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================
-- ADMIN ACTION LOGS TABLE
-- =====================
CREATE TABLE admin_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  action TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
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