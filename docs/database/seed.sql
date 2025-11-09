-- =============================================
-- Fit & Brawl Gym Database Seed Data
-- =============================================
-- Sample data for testing and development
-- All personal information has been anonymized
-- Default password for all users: "password123"
-- =============================================

USE fit_and_brawl_gym;

-- =====================
-- SEED DATA FOR USERS
-- Password: "password123" (hashed with bcrypt)
-- =====================
INSERT INTO users (username, email, password, role, avatar, is_verified) VALUES
('admin_user', 'admin@fitxbrawl.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'default-avatar.png', 1),
('member_john', 'john.member@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', 'default-avatar.png', 1),
('member_jane', 'jane.member@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', 'default-avatar.png', 1),
('trainer_mike', 'mike.trainer@fitxbrawl.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer', 'default-avatar.png', 1),
('member_test', 'test.member@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', 'default-avatar.png', 0);

-- =====================
-- SEED DATA FOR MEMBERSHIPS
-- =====================
INSERT INTO memberships (plan_name, class_type) VALUES
('Gladiator', 'Boxing and MMA'),
('Brawler', 'Muay Thai'),
('Champion', 'Boxing'),
('Clash', 'MMA'),
('Resolution Regular', 'Gym');
-- =====================
-- SEED DATA FOR TRAINERS
-- Updated with complete trainer information
-- =====================
INSERT INTO trainers (name, email, phone, specialization, bio, photo, emergency_contact_name, emergency_contact_phone, max_clients_per_day, status) VALUES
-- Boxing Trainers
('Coach Thei Lei', 'thei.lei@fitxbrawl.com', '+63-917-123-4567', 'Boxing', 'Former professional boxer with 10 years of coaching experience. Specializes in technique and footwork.', 'coach-thei.jpg', 'Maria Martinez', '+63-917-123-4568', 3, 'Active'),
('Coach Jealous Pogi', 'jealous.pogi@fitxbrawl.com', '+63-917-234-5678', 'Boxing', '5 years coaching experience. Focus on conditioning and power development.', 'coach-chris.jpg', 'Juan Santos', '+63-917-234-5679', 3, 'Active'),
('Coach Rizz Andrei', 'rizz.andrei@fitxbrawl.com', '+63-917-345-6789', 'Boxing', 'Weekend specialist in boxing fundamentals for beginners.', 'coach-taylor.jpg', 'Ana Reyes', '+63-917-345-6790', 3, 'Active'),

-- MMA Trainers
('Coach Mikell Drei', 'mikell.drei@fitxbrawl.com', '+63-917-456-7890', 'MMA', 'MMA champion with expertise in ground game and submissions. 8 years coaching experience.', 'coach-ryan.jpg', 'Sofia Cruz', '+63-917-456-7891', 3, 'Active'),
('Coach XL', 'coach.xl@fitxbrawl.com', '+63-917-567-8901', 'MMA', 'Certified MMA instructor specializing in striking and grappling techniques.', 'coach-morgan.jpg', 'Kim Lee', '+63-917-567-8902', 3, 'Active'),
('Coach Sean Pogi', 'sean.pogi@fitxbrawl.com', '+63-917-678-9012', 'MMA', 'Former cage fighter turned coach. Focus on fight strategy and conditioning.', 'coach-casey.jpg', 'Pedro Diaz', '+63-917-678-9013', 3, 'On Leave'),

-- Muay Thai Trainers
('Coach Sam "Elbow King" Fernandez', 'sam.fernandez@fitxbrawl.com', '+63-917-789-0123', 'Muay Thai', 'Traditional Muay Thai instructor from Thailand. 12 years experience in the art of eight limbs.', 'coach-sam.jpg', 'Rosa Fernandez', '+63-917-789-0124', 3, 'Active'),
('Coach Jordan Garcia', 'jordan.garcia@fitxbrawl.com', '+63-917-890-1234', 'Muay Thai', 'Competitive fighter and coach specializing in clinch work and knee strikes.', 'coach-jordan.jpg', 'Luis Garcia', '+63-917-890-1235', 3, 'Active'),

-- Gym/Fitness Trainers
('Coach Pat Anderson', 'pat.anderson@fitxbrawl.com', '+63-917-901-2345', 'Gym', 'Certified personal trainer with focus on strength and conditioning. Early morning specialist.', 'coach-pat.jpg', 'Alex Anderson', '+63-917-901-2346', 3, 'Active'),
('Coach Jamie Ramos', 'jamie.ramos@fitxbrawl.com', '+63-917-012-3456', 'Gym', 'Evening fitness expert. Specializes in HIIT and functional training programs.', 'coach-jamie.jpg', 'Carlos Ramos', '+63-917-012-3457', 3, 'Active');

-- =====================
-- SEED DATA FOR MEMBERSHIP_TRAINERS
-- Links membership plans to their assigned trainers
-- =====================
-- Gladiator (Boxing and MMA) - Trainers 1 & 2
INSERT INTO membership_trainers (membership_id, trainer_id) VALUES
(1, 1),
(1, 2);

-- Brawler (Muay Thai) - Trainers 3 & 4
INSERT INTO membership_trainers (membership_id, trainer_id) VALUES
(2, 3),
(2, 4);

-- Champion (Boxing) - Trainers 5 & 6
INSERT INTO membership_trainers (membership_id, trainer_id) VALUES
(3, 5),
(3, 6);

-- Clash (MMA) - Trainers 7 & 8
INSERT INTO membership_trainers (membership_id, trainer_id) VALUES
(4, 7),
(4, 8);

-- Resolution Regular (Gym) - Trainers 9 & 10
INSERT INTO membership_trainers (membership_id, trainer_id) VALUES
(5, 9),
(5, 10);

-- Resolution Student (Gym) - Trainers 9 & 10
INSERT INTO membership_trainers (membership_id, trainer_id) VALUES
(6, 9),
(6, 10);


-- =====================
-- SEED DATA FOR USER MEMBERSHIPS
-- Sample active memberships for testing
-- =====================
INSERT INTO user_memberships (
    user_id, name, country, permanent_address, plan_id, plan_name,
    start_date, end_date, billing_type, membership_status, request_status, date_approved
) VALUES
(2, 'John Member', 'Philippines', '123 Sample Street, City', 2, 'Brawler', '2025-09-01', '2025-12-01', 'monthly', 'active', 'approved', '2025-09-01'),
(3, 'Jane Member', 'Philippines', '456 Example Avenue, City', 1, 'Gladiator', '2025-09-15', '2025-12-15', 'monthly', 'active', 'approved', '2025-09-15');

-- =====================================================
-- SEED DATA FOR RESERVATIONS
-- =====================================================
-- NOTE: For production, use 'seed_trainer_schedules.sql' instead!
-- That file contains the complete Nov-Dec 2025 schedule (~338 sessions).
-- This seed.sql only includes basic sample data for quick testing.
--
-- To use the full schedule:
--   mysql -u root -p fit_and_brawl_gym < docs/database/seed_trainer_schedules.sql
-- =====================================================

-- Sample booking data (optional - for testing only)
-- Uncomment if you need sample bookings
-- INSERT INTO user_reservations (user_id, reservation_id, booking_status) VALUES
-- (2, 1, 'confirmed'),
-- (3, 2, 'confirmed');

-- =====================
-- SEED DATA FOR EQUIPMENT
-- Gym equipment inventory
-- =====================
INSERT INTO equipment (name, category, status, description, image_path) VALUES
-- Cardio Equipment
('Treadmill Pro X500', 'Cardio', 'Available', 'High-performance treadmill with incline features', '../uploads/equipment/treadmill-pro-x500.jpg'),
('Stationary Bike Elite', 'Cardio', 'Available', 'Adjustable resistance cycling bike', '../uploads/equipment/stationary-bike-elite.jpg'),
('Elliptical Trainer', 'Cardio', 'Out of Order', 'Low-impact cardio machine', '../uploads/equipment/elliptical-trainer.jpg'),
('Rowing Machine', 'Cardio', 'Available', 'Full-body cardio workout equipment', '../uploads/equipment/rowing-machine.jpg'),
('Assault AirBike', 'Cardio', 'Available', 'Fan-based resistance bike for HIIT', '../uploads/equipment/assault-airbike.jpg'),

-- Flexibility Equipment
('Yoga Mat Premium', 'Flexibility', 'Available', 'Non-slip yoga mat with carrying strap', '../uploads/equipment/yoga-mat-premium.jpg'),
('Foam Roller', 'Flexibility', 'Available', 'Muscle recovery and stretching tool', '../uploads/equipment/foam-roller.jpg'),
('Resistance Bands Set', 'Flexibility', 'Available', 'Multiple resistance levels for stretching', '../uploads/equipment/resistance-bands-set.jpg'),
('Pilates Reformer', 'Flexibility', 'Maintenance', 'Professional pilates equipment', '../uploads/equipment/pilates-reformer.jpg'),
('Stretch Strap', 'Flexibility', 'Available', 'Assisted stretching tool', '../uploads/equipment/stretch-strap.jpg'),

-- Core Equipment
('Ab Wheel Roller', 'Core', 'Available', 'Core strengthening wheel', '../uploads/equipment/ab-wheel-roller.jpg'),
('Medicine Ball 10kg', 'Core', 'Available', 'Weighted ball for core exercises', '../uploads/equipment/medicine-ball-10kg.jpg'),
('Stability Ball 65cm', 'Core', 'Available', 'Swiss ball for balance training', '../uploads/equipment/stability-ball-65cm.jpg'),
('Captains Chair', 'Core', 'Out of Order', 'Leg raise station - needs repair', '../uploads/equipment/captains-chair.jpg'),
('Plank Station', 'Core', 'Available', 'Dedicated plank workout area', '../uploads/equipment/plank-station.jpg'),

-- Strength Training Equipment
('Barbell Olympic 20kg', 'Strength Training', 'Available', 'Standard Olympic barbell', '../uploads/equipment/barbell-olympic-20kg.jpg'),
('Dumbbell Set 5 to 50kg', 'Strength Training', 'Available', 'Complete dumbbell rack', '../uploads/equipment/dumbbell-set-5-to-50kg.jpg'),
('Power Rack', 'Strength Training', 'Available', 'Multi-purpose squat rack with safety bars', '../uploads/equipment/power-rack.jpg'),
('Bench Press Station', 'Strength Training', 'Available', 'Adjustable bench with barbell support', '../uploads/equipment/bench-press-station.jpg'),
('Leg Press Machine', 'Strength Training', 'Maintenance', 'Heavy-duty leg press - under servicing', '../uploads/equipment/leg-press-machine.jpg'),

-- Functional Training Equipment
('Kettlebell 16kg', 'Functional Training', 'Available', 'Cast iron kettlebell', '../uploads/equipment/kettlebell-16kg.jpg'),
('Battle Ropes', 'Functional Training', 'Available', '15m heavy rope for conditioning', '../uploads/equipment/battle-ropes.jpg'),
('Suspension Trainer', 'Functional Training', 'Available', 'TRX-style bodyweight training', '../uploads/equipment/suspension-trainer.jpg'),
('Plyometric Box Set', 'Functional Training', 'Available', 'Jump boxes in various heights', '../uploads/equipment/plyometric-box-set.jpg'),
('Slam Ball 15kg', 'Functional Training', 'Available', 'Heavy ball for power training', '../uploads/equipment/slam-ball-15kg.jpg');


-- =====================
-- SEED DATA FOR PRODUCTS
-- Consumable items sold at the gym
-- =====================
INSERT INTO products (name, category, stock, status, image_path) VALUES
-- Supplements
('Whey Protein Powder', 'Supplements', 50, 'in stock', '../../uploads/products/whey-protein-powder.jpg'),
('Pre-Workout Supplement', 'Supplements', 10, 'low stock', '../../uploads/products/workout-supplement.jpg'),
('BCAA Powder', 'Supplements', 30, 'in stock', '../../uploads/products/bcaa-powder.jpg'),

-- Hydration & Drinks
('Bottled Water', 'Hydration & Drinks', 100, 'in stock', '../../uploads/products/bottled-water.jpg'),
('Sports Drink', 'Hydration & Drinks', 45, 'in stock', '../../uploads/products/sports-drink.jpg'),

-- Snacks
('Protein Bar', 'Snacks', 30, 'in stock', '../../uploads/products/recovery-bar.jpg'),
('Energy Bar', 'Snacks', 25, 'in stock', '../../uploads/products/energy-bar.jpg'),

-- Accessories
('Muscle Roller', 'Accessories', 15, 'in stock', '../../uploads/products/muscle-roller.jpg'),
('Ice Pack', 'Accessories', 5, 'low stock', '../../uploads/products/ice-pack.jpg'),
('Resistance Bands', 'Accessories', 0, 'out of stock', '../../uploads/products/resistance-bands.jpg'),
('Hand Wraps', 'Boxing & Muay Thai Products', 20, 'in stock', '../../uploads/products/hand-wraps.jpg'),
('Mouth Guards', 'Boxing & Muay Thai Products', 25, 'in stock', '../../uploads/products/mouth-guards.jpg');
-- =====================
-- SEED DATA FOR FEEDBACK
-- Sample user feedback and reviews
-- =====================
INSERT INTO feedback (user_id, username, email, avatar, message, is_visible) VALUES
(2, 'member_john', 'john.member@example.com', 'default-avatar.png', 'Great gym with excellent trainers! Highly recommend the Boxing classes.', 1),
(3, 'member_jane', 'jane.member@example.com', 'default-avatar.png', 'Love the new equipment and the Muay Thai sessions are intense!', 1),
(4, 'trainer_mike', 'mike.trainer@fitxbrawl.com', 'default-avatar.png', 'Proud to be part of this amazing fitness community!', 1);

-- =============================================
-- END OF SEED DATA
-- =============================================
-- Note: This seed file creates sample data for development/testing
-- All passwords are hashed using bcrypt
-- Default password for all test users: "password123"
-- =============================================
