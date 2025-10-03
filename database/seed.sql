USE fit_and_brawl_gym;

-- =====================
-- SEED DATA FOR USERS
-- =====================
INSERT INTO users (username, password, role) VALUES
('juan_member', 'pass123', 'member'),
('ana_admin', 'pass456', 'admin'),
('coach_pedro', 'pass789', 'trainer');

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
('Boxing Gloves', 'Available'),
('Punching Bag', 'Maintenance');

-- =====================
-- SEED DATA FOR PRODUCTS
-- =====================
INSERT INTO products (name, stock, status) VALUES
('Whey Protein', 20, 'In Stock'),
('Hand Wraps', 0, 'Out of Stock'),
('Energy Drink', 5, 'Low Stock');

-- =====================
-- SEED DATA FOR FEEDBACK
-- =====================
INSERT INTO feedback (user_id, message) VALUES
(1, 'Great gym, coaches are really helpful!'),
(1, 'Can we get more punching bags available?');
