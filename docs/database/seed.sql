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
-- SEED DATA FOR TRAINERS
-- =====================
INSERT INTO trainers (name, specialization, schedule) VALUES
('Coach Pedro', 'Muay Thai', 'Mon-Wed-Fri 6-8PM'),
('Coach Liza', 'Boxing', 'Tue-Thu 7-9PM');

-- =====================
-- SEED DATA FOR RESERVATIONS
-- =====================
INSERT INTO reservations (user_id, trainer_id, class_type, datetime, status) VALUES
(1, 1, 'Muay Thai', '2025-09-30 18:00:00', 'Confirmed'),
(1, 2, 'Boxing', '2025-10-02 19:00:00', 'Confirmed');

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
(7, 'Ice Pack', 25, 'Out of Stock'),
(8, 'Workout Supplement', 10, 'In Stock');

-- =====================
-- SEED DATA FOR FEEDBACK
-- =====================
INSERT INTO feedback (user_id, message) VALUES
(1, 'Great gym, coaches are really helpful!'),
(1, 'Can we get more punching bags available?');
