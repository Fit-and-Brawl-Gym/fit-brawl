USE fit_and_brawl_gym;

-- !!! DUMMY VALUES ONLY !!! --
-- WILL BE MODIFIED/REMOVED IN PRODUCTION --

INSERT INTO users (username, password, role) VALUES
('riezemabangis', 'pass123', 'member'),
('mikell_admin', 'pass456', 'admin'),
('coach_thei', 'pass789', 'trainer');

-- Membership Plans
INSERT INTO memberships (plan_name, price, duration) VALUES
('Brawler', 1000.00, 30),
('Gladiator', 2500.00, 90),
('Champion', 9000.00, 365);

-- Trainers
INSERT INTO trainers (name, specialization, schedule) VALUES
('Coach Thei', 'Muay Thai', 'Mon-Wed-Fri 6-8PM'),
('Coach Drei', 'Boxing', 'Tue-Thu 7-9PM');

-- Reservations
INSERT INTO reservations (user_id, trainer_id, class_type, datetime, status) VALUES
(1, 1, 'Muay Thai', '2025-09-30 18:00:00', 'Confirmed'),
(1, 2, 'Boxing', '2025-10-02 19:00:00', 'Confirmed');

-- Equipment
INSERT INTO equipment (name, status) VALUES
('Boxing Gloves', 'Available'),
('Punching Bag', 'Maintenance');

-- Products (Consumables)
INSERT INTO products (name, stock, status) VALUES
('Whey Protein', 20, 'In Stock'),
('Hand Wraps', 0, 'Out of Stock'),
('Energy Drink', 5, 'Low Stock');

-- Feedback
INSERT INTO feedback (user_id, message) VALUES
(1, 'Great gym, coaches are really helpful, pakiss nga!'),
(1, 'Can we get more punching bags available?');
