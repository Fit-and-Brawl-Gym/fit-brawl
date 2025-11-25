-- Performance Optimization Indexes for Fit & Brawl Gym
-- Run this script to add indexes that speed up common queries

-- =====================================================
-- USERS TABLE INDEXES
-- =====================================================

-- Speed up login queries (email/username lookup)
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_is_verified ON users(is_verified);

-- Composite index for login (email + password check)
CREATE INDEX IF NOT EXISTS idx_users_email_verified ON users(email, is_verified);

-- =====================================================
-- BOOKINGS/RESERVATIONS INDEXES
-- =====================================================

-- Speed up booking lookups by user
CREATE INDEX IF NOT EXISTS idx_bookings_user_id ON bookings(user_id);
CREATE INDEX IF NOT EXISTS idx_bookings_trainer_id ON bookings(trainer_id);
CREATE INDEX IF NOT EXISTS idx_bookings_date ON bookings(booking_date);
CREATE INDEX IF NOT EXISTS idx_bookings_status ON bookings(status);

-- Composite index for common booking queries
CREATE INDEX IF NOT EXISTS idx_bookings_user_date ON bookings(user_id, booking_date);
CREATE INDEX IF NOT EXISTS idx_bookings_trainer_date ON bookings(trainer_id, booking_date);
CREATE INDEX IF NOT EXISTS idx_bookings_date_status ON bookings(booking_date, status);

-- =====================================================
-- MEMBERSHIPS INDEXES
-- =====================================================

-- Speed up membership lookups
CREATE INDEX IF NOT EXISTS idx_memberships_user_id ON memberships(user_id);
CREATE INDEX IF NOT EXISTS idx_memberships_status ON memberships(status);
CREATE INDEX IF NOT EXISTS idx_memberships_end_date ON memberships(end_date);

-- Composite for active membership checks
CREATE INDEX IF NOT EXISTS idx_memberships_user_status ON memberships(user_id, status);

-- User memberships table
CREATE INDEX IF NOT EXISTS idx_user_memberships_user_id ON user_memberships(user_id);
CREATE INDEX IF NOT EXISTS idx_user_memberships_status ON user_memberships(status);

-- =====================================================
-- TRAINERS INDEXES
-- =====================================================

CREATE INDEX IF NOT EXISTS idx_trainers_user_id ON trainers(user_id);
CREATE INDEX IF NOT EXISTS idx_trainers_specialty ON trainers(specialty);
CREATE INDEX IF NOT EXISTS idx_trainers_status ON trainers(status);

-- Trainer availability
CREATE INDEX IF NOT EXISTS idx_trainer_availability_trainer_id ON trainer_availability(trainer_id);
CREATE INDEX IF NOT EXISTS idx_trainer_availability_date ON trainer_availability(date);
CREATE INDEX IF NOT EXISTS idx_trainer_availability_trainer_date ON trainer_availability(trainer_id, date);

-- =====================================================
-- PRODUCTS/EQUIPMENT INDEXES
-- =====================================================

CREATE INDEX IF NOT EXISTS idx_products_category ON products(category);
CREATE INDEX IF NOT EXISTS idx_products_status ON products(status);
CREATE INDEX IF NOT EXISTS idx_equipment_status ON equipment(status);

-- =====================================================
-- CONTACT/FEEDBACK INDEXES
-- =====================================================

CREATE INDEX IF NOT EXISTS idx_contact_status ON contact(status);
CREATE INDEX IF NOT EXISTS idx_contact_created_at ON contact(created_at);
CREATE INDEX IF NOT EXISTS idx_feedback_user_id ON feedback(user_id);
CREATE INDEX IF NOT EXISTS idx_feedback_created_at ON feedback(created_at);

-- =====================================================
-- SECURITY & LOGGING INDEXES
-- =====================================================

-- Rate limiting lookups
CREATE INDEX IF NOT EXISTS idx_api_rate_limits_identifier ON api_rate_limits(identifier);
CREATE INDEX IF NOT EXISTS idx_api_rate_limits_expires ON api_rate_limits(expires_at);
CREATE INDEX IF NOT EXISTS idx_login_attempts_identifier ON login_attempts(identifier);
CREATE INDEX IF NOT EXISTS idx_login_attempts_created ON login_attempts(created_at);

-- Activity logs
CREATE INDEX IF NOT EXISTS idx_activity_log_user_id ON activity_log(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_log_created ON activity_log(created_at);
CREATE INDEX IF NOT EXISTS idx_unified_logs_user_id ON unified_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_unified_logs_created ON unified_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_unified_logs_category ON unified_logs(category);

-- Security events
CREATE INDEX IF NOT EXISTS idx_security_events_severity ON security_events(severity);
CREATE INDEX IF NOT EXISTS idx_security_events_created ON security_events(created_at);

-- =====================================================
-- SESSION MANAGEMENT INDEXES
-- =====================================================

CREATE INDEX IF NOT EXISTS idx_active_sessions_user_id ON active_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_active_sessions_expires ON active_sessions(expires_at);
CREATE INDEX IF NOT EXISTS idx_remember_password_user_id ON remember_password(user_id);

-- =====================================================
-- EMAIL QUEUE INDEXES (for new queue system)
-- =====================================================

CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL,
    to_name VARCHAR(255) DEFAULT NULL,
    subject VARCHAR(500) NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT DEFAULT NULL,
    priority TINYINT DEFAULT 5,
    status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    INDEX idx_email_queue_status_priority (status, priority, created_at),
    INDEX idx_email_queue_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- OPTIMIZE TABLES (Run occasionally, not on every deploy)
-- =====================================================
-- OPTIMIZE TABLE users;
-- OPTIMIZE TABLE bookings;
-- OPTIMIZE TABLE memberships;
-- OPTIMIZE TABLE trainers;
