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
INSERT INTO memberships (plan_name, class_type) VALUES
('Gladiator', 'Boxing and MMA'),
('Brawler', 'Muay Thai'),
('Champion', 'Boxing'),
('Clash', 'MMA'),
('Resolution Regular', 'Gym'),
('Resolution Student', 'Gym');
-- =====================
-- SEED DATA FOR TRAINERS
-- =====================
INSERT INTO trainers (name, specialization, schedule) VALUES
('Coach Carlo', 'Boxing', 'Mon-Fri 8AM-12PM'),
('Coach Rieze', 'MMA', 'Tue-Thu 2PM-6PM'),
('Coach Thei', 'Muay Thai', 'Mon-Wed-Fri 1PM-5PM'),
('Coach Excel', 'Muay Thai', 'Tue-Thu 10AM-3PM'),
('Coach Sean', 'Boxing', 'Mon-Fri 9AM-1PM'),
('Coach Mikell', 'Boxing', 'Sat-Sun 10AM-2PM'),
('Coach Adrian', 'MMA', 'Mon-Wed 3PM-7PM'),
('Coach Andrei', 'MMA', 'Tue-Thu 4PM-8PM'),
('Coach Bon', 'Gym', 'Daily 6AM-10AM'),
('Coach Timbs', 'Gym', 'Daily 5PM-9PM');

-- =====================
-- SEED DATA FOR MEMBERSHIP_TRAINERS
-- =====================
-- Gladiator (Boxing and MMA)
INSERT INTO membership_trainers (membership_id, trainer_id) VALUES
(1, 1), 
(1, 2); 

-- Brawler (Muay Thai)
INSERT INTO membership_trainers (membership_id, trainer_id) VALUES
(2, 3), 
(2, 4); 

-- Champion (Boxing)
INSERT INTO membership_trainers (membership_id, trainer_id) VALUES
(3, 5), 
(3, 6); 

-- Clash (MMA)
INSERT INTO membership_trainers (membership_id, trainer_id) VALUES
(4, 7), 
(4, 8); 

-- Resolution (Gym)
INSERT INTO membership_trainers (membership_id, trainer_id) VALUES
(5, 9),
(5, 10); 

-- =====================
-- SEED DATA FOR TRAINERS (MUST COME BEFORE RESERVATIONS)
-- =====================
DELETE FROM trainers;


-- =====================
-- SEED DATA FOR USER MEMBERSHIPS (NEW)
-- =====================
INSERT INTO user_memberships (
    user_id, name, country, permanent_address, plan_id, plan_name,
    start_date, end_date, billing_type, membership_status, request_status, date_approved
) VALUES
(2, 'John Doe', 'Philippines', '123 Main St, Manila', 2, 'Brawler', '2025-07-15', '2025-10-15', 'monthly', 'active', 'approved', '2025-07-15'),
(3, 'Jane Smith', 'Philippines', '456 Elm St, Makati', 1, 'Gladiator', '2025-08-01', '2025-09-01', 'monthly', 'active', 'approved', '2025-08-01');

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
INSERT INTO reservations (user_id, trainer_id, class_type, date, start_time, end_time, max_slots, remaining_slots, status)
VALUES
(1, 1, 'Boxing', '2025-09-15', '17:00:00', '19:00:00', 10, 5, 'scheduled'),
(2, 1, 'Muay Thai', '2025-09-18', '18:00:00', '20:00:00', 8, 2, 'scheduled'),
(3, 2, 'Boxing', '2025-09-20', '16:00:00', '18:00:00', 12, 12, 'scheduled'),
(1, 2, 'Muay Thai', '2025-09-22', '17:00:00', '19:00:00', 10, 0, 'completed'),
(2, 1, 'Boxing', '2025-09-25', '17:00:00', '19:00:00', 10, 7, 'scheduled');

-- =====================
-- SEED DATA FOR EQUIPMENT
-- =====================
INSERT INTO equipment (name, status, category, description, image_path) VALUES
('Treadmill 1', 'Available', 'Cardio', 'Great for improving endurance and burning calories.', '../../uploads/equipment/threadmill-bg.png'),
('Treadmill 2', 'Maintenance', 'Cardio', 'Currently under maintenance.', '../../uploads/equipment/treadmill-pro-x500.jpg'),
('Bench Press', 'Available', 'Strength Training', 'Build upper body strength and chest muscles.', '../../uploads/equipment/bench-press-station.jpg'),
('Rowing Machine 1', 'Available', 'Cardio', 'Enhances stamina and targets the entire body.', '../../uploads/equipment/rowing-machine.jpg'),
('Kettlebell Set', 'Available', 'Strength Training', 'Ideal for dynamic strength and cardio workouts.', '../../uploads/equipment/kettlebell-16kg.jpg'),
('Dumbbell Set', 'Available', 'Strength Training', 'Versatile weights for various exercises.', '../../uploads/equipment/dumbbell-set-5-to-50kg.jpg');


-- =====================
-- SEED DATA FOR PRODUCTS
-- =====================
INSERT INTO products (name, category, stock, status, image_path) VALUES
    ('Whey Protein Powder', 'Supplements', 50, 'in stock', '../../uploads/products/whey-protein-powder.jpg'),
    ('Pre-Workout Supplement', 'Supplements', 10, 'low stock', '../../uploads/products/workout-supplement.jpg'),
    ('Bottled Water', 'Hydration & Drinks', 100, 'in stock', '../../uploads/products/bottled-water.jpg'),
    ('Recovery Bar', 'Snacks', 30, 'in stock', '../../uploads/products/recovery-bar.jpg'),
    ('Muscle Roller', 'Accessories', 15, 'in stock', '../../uploads/products/muscle-roller.jpg'),
    ('Ice Pack', 'Accessories', 5, 'low stock', '../../uploads/products/ice-pack.jpg'),
    ('Resistance Bands', 'Accessories', 0, 'out of stock', '../../uploads/products/resistance-bands.jpg'),
    ('Mouth Guards', 'Boxing & Muay Thai Products', 25, 'in stock', '../../uploads/products/mouth-guards.jpg');

-- =====================
-- SEED DATA FOR FEEDBACK
-- =====================
INSERT INTO feedback (user_id, username, avatar, message, date) VALUES
(1, 'Rieze Andrei', 'default-avatar.png', 'Great gym, coaches are really helpful!', '2025-09-15'),
(2, 'John Doe', 'john.png', 'Loving the new MMA classes!', '2025-09-16'),
(3, 'Jane Smith', 'jane.png', 'Could use more evening class options.', '2025-09-17'),
