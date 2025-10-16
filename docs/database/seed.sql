USE fit_and_brawl_gym;

-- =====================
-- SEED DATA FOR USERS
-- =====================
INSERT INTO users (username, email, password, role, avatar) VALUES
('admin', 'admin@fitxbrawl.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'admin.png'),
('john_doe', 'john.doe@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', 'john.png'),
('jane_smith', 'jane.smith@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', 'jane.png'),
('trainer_mike', 'mike.trainer@fitxbrawl.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer', 'trainer.png');

-- =====================
-- SEED DATA FOR MEMBERSHIPS
-- =====================
INSERT INTO memberships (plan_name, price, duration) VALUES
('Brawler', 1000.00, 30),
('Gladiator', 2500.00, 90),
('Champion', 9000.00, 365);

-- =====================
-- SEED DATA FOR TRAINERS (MUST COME BEFORE RESERVATIONS)
-- =====================
DELETE FROM trainers;
INSERT INTO trainers (id, name, specialization, schedule) VALUES
(1, 'Coach Carlo', 'Muay Thai', 'Mon-Wed-Fri 6-8PM'),
(2, 'Coach Rieze', 'Boxing', 'Tue-Thu 7-9PM'),
(3, 'Coach Thei', 'MMA', 'Mon-Fri 5-7PM');

-- =====================
-- SEED DATA FOR USER MEMBERSHIPS (NEW)
-- =====================
-- Example seeded subscriptions / memberships
INSERT INTO user_memberships (user_id, plan_id, duration, date_submitted, date_approved, start_date, end_date, billing_type, request_status, membership_status, source_table, source_id) VALUES
(2, 2, 30, '2025-07-15 00:00:00', '2025-07-15 00:00:00', '2025-07-15', '2025-10-15', 'monthly', 'approved', 'active', 'seed', 1),
(3, 1, 30, '2025-08-01 00:00:00', '2025-08-01 00:00:00', '2025-08-01', '2025-09-01', 'monthly', 'approved', 'active', 'seed', 2);

-- =====================
-- SEED DATA FOR RESERVATIONS (UPDATED)
-- =====================
DELETE FROM user_reservations;
DELETE FROM reservations;

INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
-- September 2025 sessions
(2, 'Boxing', '2025-09-01', '17:00:00', '19:00:00', 10, 'available'),
(1, 'Muay Thai', '2025-09-02', '09:00:00', '11:00:00', 8, 'available'),
(3, 'MMA', '2025-09-03', '13:00:00', '15:00:00', 12, 'available'),
(2, 'Boxing', '2025-09-04', '15:00:00', '17:00:00', 10, 'available'),
(1, 'Muay Thai', '2025-09-08', '17:00:00', '19:00:00', 10, 'available'),
(3, 'MMA', '2025-09-09', '19:00:00', '21:00:00', 8, 'available'),
(2, 'Boxing', '2025-09-10', '13:00:00', '15:00:00', 10, 'available'),
(1, 'Muay Thai', '2025-09-11', '17:00:00', '19:00:00', 10, 'available'),
(3, 'MMA', '2025-09-15', '17:00:00', '19:00:00', 10, 'available'),
(2, 'Boxing', '2025-09-16', '09:00:00', '11:00:00', 12, 'available'),
(1, 'Muay Thai', '2025-09-17', '13:00:00', '15:00:00', 10, 'available'),
(3, 'MMA', '2025-09-18', '15:00:00', '17:00:00', 8, 'available'),
(2, 'Boxing', '2025-09-22', '17:00:00', '19:00:00', 10, 'available'),
(1, 'Muay Thai', '2025-09-23', '19:00:00', '21:00:00', 10, 'available'),
(3, 'MMA', '2025-09-24', '13:00:00', '15:00:00', 12, 'available'),
(2, 'Boxing', '2025-09-25', '17:00:00', '19:00:00', 10, 'available'),
(1, 'Muay Thai', '2025-09-29', '17:00:00', '19:00:00', 10, 'available'),
(3, 'MMA', '2025-09-30', '09:00:00', '11:00:00', 8, 'available');

-- =====================
-- SEED DATA FOR USER RESERVATIONS (NEW)
-- =====================
INSERT INTO user_reservations (user_id, reservation_id, booking_status) VALUES
(2, 1, 'confirmed'),
(2, 7, 'confirmed'),
(3, 2, 'confirmed'),
(3, 4, 'confirmed');


-- =====================
-- SEED DATA FOR EQUIPMENT
-- =====================
INSERT INTO equipment (name, status) VALUES
('Treadmill 1', 'Available'),
('Treadmill 2', 'Maintenance'),
('Bench Press', 'Available'),
('Rowing Machine 1', 'Available'),
('Rowing Machine 2', 'Out of Order');

-- =====================
-- SEED DATA FOR PRODUCTS
-- =====================
INSERT INTO `products` (`id`, `name`, `stock`, `status`) VALUES
(1, 'Whey Protein Powder', 20, 'In Stock'),
(2, 'Mouth Guards', 0, 'Out of Stock'),
(3, 'Bottled Water', 5, 'Low Stock'),
(4, 'Resistance Bands', 10, 'In Stock'),
(5, 'Recovery Bar', 0, 'Out of Stock'),
(6, 'Muscle Roller', 5, 'Low Stock'),
(7, 'Ice Pack', 25, 'In Stock'),
(8, 'Workout Supplement', 10, 'In Stock');

-- =====================
-- SEED DATA FOR FEEDBACK
-- =====================
INSERT INTO feedback (user_id, message) VALUES
(1, 'Great gym, coaches are really helpful!'),
(1, 'Can we get more punching bags available?');
