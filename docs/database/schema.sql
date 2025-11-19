-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2025 at 08:46 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.3.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

START TRANSACTION;

SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */
;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */
;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */
;
/*!40101 SET NAMES utf8mb4 */
;

--
-- Database: `fit_and_brawl_gym`
--

-- --------------------------------------------------------

--
-- Table structure for table `active_sessions`
--

CREATE TABLE `active_sessions` (
    `id` int(11) NOT NULL,
    `user_id` varchar(50) NOT NULL,
    `session_id` varchar(128) NOT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
    `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `is_current` tinyint(1) DEFAULT 0
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
    `id` int(11) NOT NULL,
    `user_id` varchar(15) NOT NULL,
    `role` enum('member', 'trainer', 'admin') NOT NULL,
    `action` varchar(50) NOT NULL,
    `timestamp` datetime DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
    `id` int(11) NOT NULL,
    `admin_id` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Formatted admin ID',
    `admin_name` varchar(100) NOT NULL COMMENT 'Name of the admin user',
    `action_type` varchar(50) NOT NULL COMMENT 'Type of action (subscription_approved, equipment_add, etc.)',
    `target_user` varchar(100) DEFAULT NULL COMMENT 'Name of user/member affected by action',
    `target_user_id` varchar(15) DEFAULT NULL COMMENT 'User ID affected by action',
    `target_id` int(11) DEFAULT NULL COMMENT 'ID of the record that was affected',
    `details` text DEFAULT NULL COMMENT 'Detailed description of the action',
    `previous_value` text DEFAULT NULL COMMENT 'Value before change (if applicable)',
    `new_value` text DEFAULT NULL COMMENT 'Value after change (if applicable)',
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` varchar(255) DEFAULT NULL,
    `severity` enum(
        'low',
        'medium',
        'high',
        'critical'
    ) DEFAULT 'low',
    `timestamp` datetime DEFAULT current_timestamp() COMMENT 'When the action occurred'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Tracks all admin actions in the system';

-- --------------------------------------------------------

--
-- Table structure for table `admin_permissions`
--

CREATE TABLE `admin_permissions` (
    `id` int(11) NOT NULL,
    `admin_id` varchar(15) NOT NULL,
    `permission_name` varchar(100) NOT NULL COMMENT 'e.g., CHANGE_USER_ROLE, SUSPEND_USER, VIEW_AUDIT_LOGS',
    `granted_by` varchar(15) NOT NULL COMMENT 'Admin who granted this permission',
    `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `expires_at` datetime DEFAULT NULL COMMENT 'Optional permission expiry',
    `is_active` tinyint(1) DEFAULT 1
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'RBAC permissions for granular admin access control';

-- --------------------------------------------------------

--
-- Table structure for table `api_rate_limits`
--

CREATE TABLE `api_rate_limits` (
    `identifier` varchar(255) NOT NULL,
    `request_count` int(11) NOT NULL DEFAULT 1,
    `window_start` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_config`
--

CREATE TABLE `booking_config` (
    `id` int(11) NOT NULL,
    `config_key` varchar(50) NOT NULL COMMENT 'Configuration parameter name',
    `config_value` varchar(255) NOT NULL COMMENT 'Configuration value',
    `description` text DEFAULT NULL COMMENT 'What this config does',
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'System configuration for booking rules';

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

CREATE TABLE `contact` (
    `id` int(11) NOT NULL,
    `first_name` varchar(50) NOT NULL,
    `last_name` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `phone_number` varchar(20) DEFAULT NULL,
    `message` text NOT NULL,
    `status` enum('unread', 'read') DEFAULT 'unread',
    `archived` tinyint(1) DEFAULT 0,
    `deleted_at` timestamp NULL DEFAULT NULL,
    `date_submitted` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
    `id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `category` enum(
        'Cardio',
        'Flexibility',
        'Core',
        'Strength Training',
        'Functional Training'
    ) NOT NULL,
    `status` enum(
        'Available',
        'Maintenance',
        'Out of Order'
    ) DEFAULT 'Available',
    `maintenance_start_date` date DEFAULT NULL,
    `maintenance_end_date` date DEFAULT NULL,
    `maintenance_reason` text DEFAULT NULL,
    `description` text DEFAULT NULL,
    `image_path` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
    `id` int(11) NOT NULL,
    `user_id` varchar(15) DEFAULT NULL,
    `username` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `avatar` varchar(255) DEFAULT 'default-avatar.png',
    `message` text NOT NULL,
    `is_visible` tinyint(1) DEFAULT 1 COMMENT 'Admin can hide/show feedback',
    `helpful_count` int(11) DEFAULT 0 COMMENT 'Number of helpful votes',
    `not_helpful_count` int(11) DEFAULT 0 COMMENT 'Number of not helpful votes',
    `date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_votes`
--

CREATE TABLE `feedback_votes` (
    `id` int(11) NOT NULL,
    `feedback_id` int(11) NOT NULL,
    `user_id` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `vote_type` enum('helpful', 'not_helpful') NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
    `identifier` varchar(255) NOT NULL,
    `attempt_count` int(11) NOT NULL DEFAULT 0,
    `last_attempt` datetime NOT NULL DEFAULT current_timestamp(),
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `memberships`
--

CREATE TABLE `memberships` (
    `id` int(11) NOT NULL,
    `plan_name` varchar(50) NOT NULL,
    `class_type` varchar(100) NOT NULL,
    `weekly_hours_limit` int(11) NOT NULL DEFAULT 24
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `membership_trainers`
--

CREATE TABLE `membership_trainers` (
    `membership_id` int(11) NOT NULL,
    `trainer_id` int(11) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nonmember_receipts`
--

CREATE TABLE `nonmember_receipts` (
    `id` int(11) NOT NULL,
    `receipt_id` varchar(32) DEFAULT NULL,
    `service` varchar(64) NOT NULL,
    `name` varchar(128) NOT NULL,
    `email` varchar(128) NOT NULL,
    `phone` varchar(32) NOT NULL,
    `service_date` date NOT NULL,
    `amount` int(11) NOT NULL,
    `created_at` datetime NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
    `id` int(11) NOT NULL,
    `user_id` varchar(15) NOT NULL,
    `token` varchar(255) NOT NULL COMMENT 'Hashed token for security',
    `expires_at` datetime NOT NULL,
    `used_at` datetime DEFAULT NULL,
    `created_by` varchar(15) DEFAULT NULL COMMENT 'Admin who triggered reset (NULL for self-service)',
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Secure one-time password reset tokens';

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
    `id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `category` enum(
        'Supplements',
        'Hydration & Drinks',
        'Snacks',
        'Accessories',
        'Boxing & Muay Thai Products'
    ) NOT NULL,
    `stock` int(11) DEFAULT 0,
    `status` enum(
        'in stock',
        'low stock',
        'out of stock'
    ) DEFAULT 'in stock',
    `image_path` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recurring_bookings`
--

CREATE TABLE `recurring_bookings` (
    `id` int(11) NOT NULL,
    `user_id` varchar(15) NOT NULL COMMENT 'User who created recurring booking (matches users.id format)',
    `trainer_id` int(11) NOT NULL COMMENT 'Assigned trainer (matches trainers.id)',
    `class_type` enum(
        'Boxing',
        'Muay Thai',
        'MMA',
        'Gym'
    ) NOT NULL COMMENT 'Training discipline',
    `recurrence_pattern` enum('weekly') DEFAULT 'weekly' COMMENT 'Currently only weekly recurrence supported',
    `start_date` date NOT NULL COMMENT 'First occurrence date',
    `end_date` date NOT NULL COMMENT 'Last occurrence date (inclusive)',
    `start_time` time NOT NULL COMMENT 'Session start time',
    `end_time` time NOT NULL COMMENT 'Session end time',
    `occurrences_count` int(11) DEFAULT 0 COMMENT 'Total number of generated bookings',
    `status` enum(
        'active',
        'cancelled',
        'completed'
    ) DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Parent records for recurring weekly bookings';

-- --------------------------------------------------------

--
-- Table structure for table `remember_password`
--

CREATE TABLE `remember_password` (
    `id` int(11) NOT NULL,
    `user_id` varchar(15) NOT NULL,
    `token_hash` varchar(255) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_events`
--

CREATE TABLE `security_events` (
    `id` int(11) NOT NULL,
    `event_type` varchar(50) NOT NULL,
    `severity` enum(
        'low',
        'medium',
        'high',
        'critical'
    ) NOT NULL DEFAULT 'medium',
    `user_id` varchar(50) DEFAULT NULL,
    `username` varchar(100) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `endpoint` varchar(255) DEFAULT NULL,
    `details` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_verification_codes`
--

CREATE TABLE `security_verification_codes` (
    `id` int(11) NOT NULL,
    `code` varchar(64) NOT NULL COMMENT 'Hashed verification code',
    `purpose` varchar(100) NOT NULL COMMENT 'e.g., ROLE_CHANGE, SENSITIVE_ACTION',
    `admin_id` varchar(15) NOT NULL,
    `valid_until` datetime NOT NULL,
    `used_at` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Secure codes for sensitive admin operations';

-- --------------------------------------------------------

--
-- Table structure for table `sensitive_change_requests`
--

CREATE TABLE `sensitive_change_requests` (
    `id` int(11) NOT NULL,
    `user_id` varchar(15) NOT NULL,
    `admin_id` varchar(15) NOT NULL COMMENT 'Admin who initiated the change',
    `change_type` enum(
        'email',
        'phone',
        'recovery_email',
        'security_question'
    ) NOT NULL,
    `old_value` varchar(255) DEFAULT NULL COMMENT 'Encrypted or hashed if sensitive',
    `new_value` varchar(255) NOT NULL COMMENT 'Encrypted or hashed if sensitive',
    `confirmation_token` varchar(255) NOT NULL COMMENT 'Hashed token sent to user',
    `status` enum(
        'pending',
        'confirmed',
        'rejected',
        'expired'
    ) DEFAULT 'pending',
    `expires_at` datetime NOT NULL,
    `confirmed_at` datetime DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'User confirmation required for sensitive data changes';

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

CREATE TABLE `trainers` (
    `id` int(11) NOT NULL,
    `user_id` varchar(15) DEFAULT NULL,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `phone` varchar(20) NOT NULL,
    `specialization` enum(
        'Gym',
        'MMA',
        'Boxing',
        'Muay Thai'
    ) NOT NULL,
    `bio` text DEFAULT NULL,
    `photo` varchar(255) DEFAULT NULL,
    `emergency_contact_name` varchar(100) DEFAULT NULL,
    `emergency_contact_phone` varchar(20) DEFAULT NULL,
    `max_clients_per_day` int(11) DEFAULT 3,
    `status` enum(
        'Active',
        'Inactive',
        'On Leave'
    ) NOT NULL DEFAULT 'Active',
    `password_changed` tinyint(1) DEFAULT 0 COMMENT 'Whether trainer has changed their default password',
    `deleted_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainer_activity_log`
--

CREATE TABLE `trainer_activity_log` (
    `id` int(11) NOT NULL,
    `trainer_id` int(11) NOT NULL,
    `admin_id` varchar(15) DEFAULT NULL,
    `action` varchar(50) NOT NULL COMMENT 'Type of action: Added, Edited, Status Changed, Deleted',
    `details` text DEFAULT NULL COMMENT 'Description of what changed',
    `timestamp` datetime DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Audit log for trainer management actions';

-- --------------------------------------------------------

--
-- Table structure for table `trainer_availability_blocks`
--

CREATE TABLE `trainer_availability_blocks` (
    `id` int(11) NOT NULL,
    `trainer_id` int(11) NOT NULL,
    `date` date NOT NULL COMMENT 'Date trainer is blocked',
    `block_start_time` datetime DEFAULT NULL COMMENT 'Block start time (Philippines Time)',
    `block_end_time` datetime DEFAULT NULL COMMENT 'Block end time (Philippines Time)',
    `is_all_day` tinyint(1) DEFAULT 0 COMMENT 'True if blocking entire day',
    `session_time` enum(
        'Morning',
        'Afternoon',
        'Evening',
        'All Day'
    ) DEFAULT 'All Day' COMMENT 'Specific session or entire day',
    `reason` varchar(255) DEFAULT NULL COMMENT 'Reason for blocking (vacation, meeting, etc)',
    `blocked_by` varchar(15) DEFAULT NULL COMMENT 'Admin formatted ID who blocked this time',
    `block_status` enum('blocked', 'available') DEFAULT 'blocked',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Admin blocks for trainer availability - prevents bookings during blocked times';

-- --------------------------------------------------------

--
-- Table structure for table `trainer_day_offs`
--

CREATE TABLE `trainer_day_offs` (
    `id` int(11) NOT NULL,
    `trainer_id` int(11) NOT NULL,
    `day_of_week` enum(
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday'
    ) NOT NULL,
    `is_day_off` tinyint(1) DEFAULT 1 COMMENT 'TRUE = day off, FALSE = working day',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Trainer weekly day-off schedule management';

-- --------------------------------------------------------

--
-- Table structure for table `trainer_shifts`
--

CREATE TABLE `trainer_shifts` (
    `id` int(11) NOT NULL,
    `trainer_id` int(11) NOT NULL COMMENT 'Foreign key to trainers table',
    `day_of_week` enum(
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday'
    ) NOT NULL COMMENT 'Day of the week',
    `shift_type` enum(
        'morning',
        'afternoon',
        'night',
        'none'
    ) DEFAULT 'none' COMMENT 'Morning: 7am-3pm, Afternoon: 11am-7pm, Night: 3pm-10pm',
    `custom_start_time` time DEFAULT NULL COMMENT 'Optional: override shift start time',
    `custom_end_time` time DEFAULT NULL COMMENT 'Optional: override shift end time',
    `break_start_time` time DEFAULT NULL COMMENT 'Shift-specific break start (e.g., 12:00 for lunch)',
    `break_end_time` time DEFAULT NULL COMMENT 'Shift-specific break end (e.g., 13:00)',
    `is_active` tinyint(1) DEFAULT 1 COMMENT 'Whether this shift is currently active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Trainer weekly shift schedule (one shift per day per trainer)';

-- --------------------------------------------------------

--
-- Table structure for table `unified_logs`
--

CREATE TABLE `unified_logs` (
    `id` int(11) NOT NULL,
    `log_level` enum(
        'debug',
        'info',
        'warning',
        'error',
        'critical'
    ) NOT NULL DEFAULT 'info',
    `log_source` enum(
        'security',
        'activity',
        'application',
        'database',
        'email',
        'system'
    ) NOT NULL,
    `category` varchar(100) DEFAULT NULL,
    `message` text NOT NULL,
    `user_id` varchar(50) DEFAULT NULL,
    `username` varchar(100) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `endpoint` varchar(255) DEFAULT NULL,
    `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
    `stack_trace` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
    `id` varchar(15) NOT NULL COMMENT 'Formatted ID: MBR-25-0012, TRN-25-0003, ADM-25-0001',
    `username` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `email_encrypted` text DEFAULT NULL,
    `contact_number` varchar(20) DEFAULT NULL,
    `password` varchar(255) NOT NULL COMMENT 'Hashed password using bcrypt',
    `role` enum('member', 'admin', 'trainer') DEFAULT 'member',
    `avatar` varchar(255) DEFAULT 'default-avatar.png',
    `is_verified` tinyint(1) DEFAULT 0,
    `account_status` enum(
        'active',
        'suspended',
        'locked',
        'pending'
    ) DEFAULT 'active',
    `verification_token` varchar(255) DEFAULT NULL,
    `otp` varchar(6) DEFAULT NULL,
    `otp_expiry` datetime DEFAULT NULL,
    `otp_attempts` int(11) DEFAULT 0,
    `last_otp_request` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `last_logout` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_memberships`
--

CREATE TABLE `user_memberships` (
    `id` int(11) NOT NULL,
    `user_id` varchar(15) NOT NULL,
    `name` varchar(255) DEFAULT NULL,
    `country` varchar(100) DEFAULT NULL,
    `permanent_address` varchar(255) DEFAULT NULL,
    `plan_id` int(11) DEFAULT NULL,
    `plan_name` varchar(100) DEFAULT NULL,
    `duration` int(11) DEFAULT NULL COMMENT 'Duration in days (used to compute end_date if needed)',
    `qr_proof` varchar(255) DEFAULT NULL,
    `admin_id` varchar(15) DEFAULT NULL,
    `date_submitted` datetime DEFAULT current_timestamp(),
    `date_approved` datetime DEFAULT NULL,
    `remarks` varchar(255) DEFAULT NULL,
    `request_status` enum(
        'pending',
        'approved',
        'rejected'
    ) DEFAULT 'pending',
    `start_date` date DEFAULT NULL,
    `end_date` date DEFAULT NULL,
    `billing_type` enum('monthly', 'quarterly') DEFAULT 'monthly',
    `payment_method` enum('online', 'cash') NOT NULL DEFAULT 'online',
    `cash_payment_status` enum('unpaid', 'paid', 'cancelled') DEFAULT NULL,
    `cash_payment_date` datetime DEFAULT NULL,
    `cash_received_by` varchar(15) DEFAULT NULL,
    `membership_status` enum(
        'active',
        'expired',
        'cancelled'
    ) DEFAULT NULL,
    `source_table` enum(
        'user_memberships',
        'subscriptions'
    ) DEFAULT NULL,
    `source_id` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
    `id` int(11) NOT NULL,
    `user_id` varchar(15) NOT NULL,
    `notification_type` varchar(50) NOT NULL COMMENT 'e.g., PROFILE_UPDATED, PASSWORD_RESET, EMAIL_CHANGED',
    `title` varchar(255) NOT NULL,
    `message` text NOT NULL,
    `admin_identifier` varchar(100) DEFAULT NULL COMMENT 'Admin name/ID who triggered (never show username)',
    `is_read` tinyint(1) DEFAULT 0,
    `sent_via_email` tinyint(1) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Track all user notifications for admin actions';

-- --------------------------------------------------------

--
-- Table structure for table `user_reservations`
--

CREATE TABLE `user_reservations` (
    `id` int(11) NOT NULL,
    `user_id` varchar(15) NOT NULL,
    `trainer_id` int(11) NOT NULL,
    `session_time` enum(
        'Morning',
        'Afternoon',
        'Evening'
    ) NOT NULL COMMENT 'Morning: 7-11 AM, Afternoon: 1-5 PM, Evening: 6-10 PM',
    `class_type` enum(
        'Boxing',
        'Muay Thai',
        'MMA',
        'Gym'
    ) NOT NULL COMMENT 'Training discipline',
    `booking_date` date NOT NULL COMMENT 'Date of the training session',
    `start_time` datetime DEFAULT NULL COMMENT 'Booking start time (Philippines Time)',
    `end_time` datetime DEFAULT NULL COMMENT 'Booking end time (Philippines Time)',
    `recurring_parent_id` int(11) DEFAULT NULL COMMENT 'Links to parent recurring booking',
    `rescheduled_from_id` int(11) DEFAULT NULL COMMENT 'Links to previous booking if rescheduled',
    `reschedule_reason` varchar(255) DEFAULT NULL COMMENT 'Reason for reschedule',
    `rescheduled_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp when rescheduled',
    `buffer_minutes` int(11) DEFAULT 10 COMMENT 'Buffer time before/after booking (minutes)',
    `booking_status` enum(
        'confirmed',
        'completed',
        'cancelled',
        'blocked'
    ) DEFAULT 'confirmed',
    `booked_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When the booking was made',
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `reservation_state` varchar(50) DEFAULT NULL,
    `cancelled_at` timestamp NULL DEFAULT NULL COMMENT 'When the booking was cancelled',
    `unavailable_marked_at` datetime DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = 'Session-based bookings - users book trainers for specific sessions';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `active_sessions`
--
ALTER TABLE `active_sessions`
ADD PRIMARY KEY (`id`),
ADD KEY `idx_user_id` (`user_id`),
ADD KEY `idx_session_id` (`session_id`),
ADD KEY `idx_is_current` (`is_current`),
ADD KEY `idx_last_activity` (`last_activity`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log` ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
ADD PRIMARY KEY (`id`),
ADD KEY `idx_action_type` (`action_type`),
ADD KEY `idx_timestamp` (`timestamp`),
ADD KEY `idx_target_id` (`target_id`),
ADD KEY `idx_admin_id` (`admin_id`),
ADD KEY `idx_target_user_id` (`target_user_id`),
ADD KEY `idx_severity` (`severity`);

--
-- Indexes for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `unique_permission` (`admin_id`, `permission_name`),
ADD KEY `idx_permission_name` (`permission_name`),
ADD KEY `idx_active` (`is_active`),
ADD KEY `fk_permission_granter` (`granted_by`);

--
-- Indexes for table `api_rate_limits`
--
ALTER TABLE `api_rate_limits` ADD PRIMARY KEY (`identifier`);

--
-- Indexes for table `booking_config`
--
ALTER TABLE `booking_config`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `config_key` (`config_key`),
ADD UNIQUE KEY `unique_config_key` (`config_key`);

--
-- Indexes for table `contact`
--
ALTER TABLE `contact`
ADD PRIMARY KEY (`id`),
ADD KEY `idx_contact_status` (`status`),
ADD KEY `idx_contact_archived` (`archived`),
ADD KEY `idx_contact_deleted` (`deleted_at`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
ADD PRIMARY KEY (`id`),
ADD KEY `idx_category` (`category`),
ADD KEY `idx_status` (`status`),
ADD KEY `idx_maintenance_dates` (
    `maintenance_start_date`,
    `maintenance_end_date`
),
ADD KEY `idx_equipment_status` (`status`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
ADD PRIMARY KEY (`id`),
ADD KEY `idx_feedback_helpful` (`helpful_count`),
ADD KEY `idx_feedback_date` (`date`),
ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feedback_votes`
--
ALTER TABLE `feedback_votes`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `unique_vote` (`feedback_id`, `user_id`),
ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts` ADD PRIMARY KEY (`identifier`);

--
-- Indexes for table `memberships`
--
ALTER TABLE `memberships` ADD PRIMARY KEY (`id`);

--
-- Indexes for table `membership_trainers`
--
ALTER TABLE `membership_trainers`
ADD PRIMARY KEY (`membership_id`, `trainer_id`),
ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `nonmember_receipts`
--
ALTER TABLE `nonmember_receipts`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `receipt_id` (`receipt_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `token` (`token`),
ADD KEY `idx_user_id` (`user_id`),
ADD KEY `idx_expires` (`expires_at`),
ADD KEY `idx_used` (`used_at`),
ADD KEY `fk_password_reset_admin` (`created_by`);

--
-- Indexes for table `recurring_bookings`
--
ALTER TABLE `recurring_bookings`
ADD PRIMARY KEY (`id`),
ADD KEY `idx_user_recurring` (`user_id`, `status`),
ADD KEY `idx_trainer_recurring` (
    `trainer_id`,
    `start_date`,
    `end_date`
),
ADD KEY `idx_date_range` (
    `start_date`,
    `end_date`,
    `status`
);

--
-- Indexes for table `remember_password`
--
ALTER TABLE `remember_password`
ADD PRIMARY KEY (`id`),
ADD KEY `fk_user` (`user_id`);

--
-- Indexes for table `security_events`
--
ALTER TABLE `security_events`
ADD PRIMARY KEY (`id`),
ADD KEY `idx_event_type` (`event_type`),
ADD KEY `idx_severity` (`severity`),
ADD KEY `idx_user_id` (`user_id`),
ADD KEY `idx_ip_address` (`ip_address`),
ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `security_verification_codes`
--
ALTER TABLE `security_verification_codes`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `code` (`code`),
ADD KEY `idx_purpose` (`purpose`),
ADD KEY `idx_valid` (`valid_until`),
ADD KEY `fk_verification_admin` (`admin_id`);

--
-- Indexes for table `sensitive_change_requests`
--
ALTER TABLE `sensitive_change_requests`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `confirmation_token` (`confirmation_token`),
ADD KEY `idx_user_id` (`user_id`),
ADD KEY `idx_status` (`status`),
ADD KEY `idx_expires` (`expires_at`),
ADD KEY `fk_sensitive_change_admin` (`admin_id`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `email` (`email`),
ADD KEY `idx_trainers_status` (`status`),
ADD KEY `idx_trainers_deleted_at` (`deleted_at`),
ADD KEY `idx_trainers_specialization` (`specialization`),
ADD KEY `idx_trainer_user_id` (`user_id`);

--
-- Indexes for table `trainer_activity_log`
--
ALTER TABLE `trainer_activity_log`
ADD PRIMARY KEY (`id`),
ADD KEY `idx_trainer_activity_trainer` (`trainer_id`),
ADD KEY `idx_trainer_activity_timestamp` (`timestamp`),
ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `trainer_day_offs`
--
ALTER TABLE `trainer_day_offs`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `unique_trainer_day` (`trainer_id`, `day_of_week`),
ADD KEY `idx_trainer_day_offs_trainer` (`trainer_id`),
ADD KEY `idx_day_off_schedule` (`trainer_id`, `is_day_off`);

--
-- Indexes for table `trainer_shifts`
--
ALTER TABLE `trainer_shifts`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `unique_trainer_day` (`trainer_id`, `day_of_week`),
ADD KEY `idx_trainer_shift` (
    `trainer_id`,
    `day_of_week`,
    `shift_type`
),
ADD KEY `idx_shift_active` (`shift_type`, `is_active`),
ADD KEY `idx_shift_day_lookup` (
    `day_of_week`,
    `shift_type`,
    `is_active`
);

--
-- Indexes for table `unified_logs`
--
ALTER TABLE `unified_logs`
ADD PRIMARY KEY (`id`),
ADD KEY `idx_log_level` (`log_level`),
ADD KEY `idx_log_source` (`log_source`),
ADD KEY `idx_category` (`category`),
ADD KEY `idx_user_id` (`user_id`),
ADD KEY `idx_ip_address` (`ip_address`),
ADD KEY `idx_created_at` (`created_at`),
ADD KEY `idx_log_source_level` (`log_source`, `log_level`),
ADD KEY `idx_created_at_source` (`created_at`, `log_source`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `username` (`username`),
ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_memberships`
--
ALTER TABLE `user_memberships`
ADD PRIMARY KEY (`id`),
ADD KEY `plan_id` (`plan_id`),
ADD KEY `user_id` (`user_id`),
ADD KEY `admin_id` (`admin_id`),
ADD KEY `idx_payment_method` (`payment_method`),
ADD KEY `idx_cash_payment_status` (`cash_payment_status`),
ADD KEY `fk_cash_received_by` (`cash_received_by`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
ADD PRIMARY KEY (`id`),
ADD KEY `idx_user_id` (`user_id`),
ADD KEY `idx_is_read` (`is_read`),
ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `user_reservations`
--
ALTER TABLE `user_reservations` ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `active_sessions`
--
ALTER TABLE `active_sessions`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_permissions`
--
ALTER TABLE `admin_permissions`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `booking_config`
--
ALTER TABLE `booking_config`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact`
--
ALTER TABLE `contact` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback_votes`
--
ALTER TABLE `feedback_votes`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `memberships`
--
ALTER TABLE `memberships`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nonmember_receipts`
--
ALTER TABLE `nonmember_receipts`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recurring_bookings`
--
ALTER TABLE `recurring_bookings`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remember_password`
--
ALTER TABLE `remember_password`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_events`
--
ALTER TABLE `security_events`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_verification_codes`
--
ALTER TABLE `security_verification_codes`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sensitive_change_requests`
--
ALTER TABLE `sensitive_change_requests`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainers`
--
ALTER TABLE `trainers` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainer_activity_log`
--
ALTER TABLE `trainer_activity_log`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainer_day_offs`
--
ALTER TABLE `trainer_day_offs`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainer_shifts`
--
ALTER TABLE `trainer_shifts`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `unified_logs`
--
ALTER TABLE `unified_logs`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_memberships`
--
ALTER TABLE `user_memberships`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_reservations`
--
ALTER TABLE `user_reservations`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */
;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */
;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */
;