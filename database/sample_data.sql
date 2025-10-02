USE fit_and_brawl_gym;

-- Insert sample users
INSERT INTO users (username, password, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('john_doe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member'),
('trainer_mike', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer');

-- Insert sample memberships
INSERT INTO memberships (plan_name, price, duration) VALUES
('Basic Monthly', 29.99, 30),
('Premium Monthly', 49.99, 30),
('Annual Plan', 299.99, 365);

-- Insert sample trainers
INSERT INTO trainers (name, specialization, schedule) VALUES
('Mike Johnson', 'Boxing', 'Mon-Fri 6AM-2PM'),
('Sarah Williams', 'Muay Thai', 'Tue-Sat 2PM-10PM');

-- Insert sample equipment
INSERT INTO equipment (name, status) VALUES
('Heavy Bag #1', 'Available'),
('Speed Bag #1', 'Available'),
('Boxing Ring', 'In Use'),
('Treadmill #1', 'Maintenance'),
('Dumbbells Set', 'Available');

-- Insert sample products
INSERT INTO products (name, stock, status) VALUES
('Protein Shake', 50, 'In Stock'),
('Energy Bar', 25, 'In Stock'),
('Sports Drink', 5, 'Low Stock'),
('Towel', 0, 'Out of Stock');

-- Insert sample feedback
INSERT INTO feedback (user_id, message) VALUES
(2, 'Great gym with excellent equipment!'),
(2, 'The trainers are very professional and helpful.');
