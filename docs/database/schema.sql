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
    role ENUM('member', 'admin', 'trainer') DEFAULT 'member'
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
    user_id INT NOT NULL,
    trainer_id INT NOT NULL,
    class_type ENUM('Boxing', 'Muay Thai') NOT NULL,
    datetime DATETIME NOT NULL,
    status ENUM('Confirmed', 'Cancelled', 'Completed') DEFAULT 'Confirmed',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE
);

-- =====================
-- EQUIPMENT TABLE
-- =====================
CREATE TABLE equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    status ENUM('Available', 'Out of Order', 'Maintenance') DEFAULT 'Available'
);

-- =====================
-- PRODUCTS TABLE (Consumables Only)
-- =====================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    status ENUM('In Stock', 'Low Stock', 'Out of Stock') DEFAULT 'In Stock'
);

-- =====================
-- FEEDBACK TABLE
-- =====================
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
