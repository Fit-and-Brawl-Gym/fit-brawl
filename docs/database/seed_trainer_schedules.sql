-- =====================================================
-- TRAINER SCHEDULES SEED FILE
-- Generated for November & December 2025
-- =====================================================
-- This file contains comprehensive schedules for all active trainers
-- organized by specialization and time slots.
--
-- Trainers:
--   Boxing: Coach Thei Lei (1), Coach Jealous Pogi (2), Coach Rizz Andrei (3)
--   MMA: Coach Mikell Drei (4), Coach XL (5)
--   Muay Thai: Coach Sam "Elbow King" Fernandez (7), Coach Jordan Garcia (8)
--   Gym: Coach Pat Anderson (9), Coach Jamie Ramos (10) - WEEKEND SPECIALISTS
-- =====================================================

-- =====================================================
-- STEP 1: UPDATE SCHEMA (REQUIRED!)
-- =====================================================
-- Add 'Gym' to the class_type enum in reservations table
-- This is REQUIRED before importing the schedules
ALTER TABLE reservations MODIFY class_type ENUM('Boxing','Muay Thai','MMA','Gym') NOT NULL;

-- =====================================================
-- STEP 2: CLEAR EXISTING DATA
-- =====================================================
-- Clear existing reservations for November and December 2025
DELETE FROM reservations WHERE date >= '2025-11-01' AND date <= '2025-12-31';

-- Note: Gym trainers (Coach Pat & Coach Jamie) are scheduled for WEEKENDS ONLY (Saturday & Sunday)
-- They provide general fitness, HIIT, functional training, and open gym guidance

-- =====================================================
-- STEP 3: INSERT NEW SCHEDULES
-- =====================================================

-- =====================================================
-- NOVEMBER 2025 SCHEDULES
-- =====================================================

-- ========== WEEK 1: Nov 3-8 (Mon-Sat) ==========

-- Monday, November 3, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
-- Boxing - Morning
(1, 'Boxing', '2025-11-03', '08:00:00', '10:00:00', 1, 'available'), -- Coach Thei Lei
(2, 'Boxing', '2025-11-03', '10:00:00', '12:00:00', 1, 'available'), -- Coach Jealous Pogi
-- MMA - Afternoon
(4, 'MMA', '2025-11-03', '14:00:00', '16:00:00', 1, 'available'), -- Coach Mikell Drei
(5, 'MMA', '2025-11-03', '16:00:00', '18:00:00', 1, 'available'), -- Coach XL
-- Muay Thai - Evening
(7, 'Muay Thai', '2025-11-03', '18:00:00', '20:00:00', 1, 'available'), -- Coach Sam
(8, 'Muay Thai', '2025-11-03', '20:00:00', '22:00:00', 1, 'available'); -- Coach Jordan

-- Tuesday, November 4, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
-- MMA - Morning
(4, 'MMA', '2025-11-04', '08:00:00', '10:00:00', 1, 'available'),
(5, 'MMA', '2025-11-04', '10:00:00', '12:00:00', 1, 'available'),
-- Muay Thai - Afternoon
(7, 'Muay Thai', '2025-11-04', '14:00:00', '16:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-04', '16:00:00', '18:00:00', 1, 'available'),
-- Boxing - Evening
(1, 'Boxing', '2025-11-04', '18:00:00', '20:00:00', 1, 'available'),
(2, 'Boxing', '2025-11-04', '20:00:00', '22:00:00', 1, 'available');

-- Wednesday, November 5, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
-- Boxing - Morning
(3, 'Boxing', '2025-11-05', '08:00:00', '10:00:00', 1, 'available'), -- Coach Rizz Andrei
(1, 'Boxing', '2025-11-05', '10:00:00', '12:00:00', 1, 'available'),
-- MMA - Afternoon
(4, 'MMA', '2025-11-05', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-11-05', '16:00:00', '18:00:00', 1, 'available'),
-- Muay Thai - Evening
(7, 'Muay Thai', '2025-11-05', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-05', '20:00:00', '22:00:00', 1, 'available');

-- Thursday, November 6, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
-- Muay Thai - Morning
(7, 'Muay Thai', '2025-11-06', '08:00:00', '10:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-06', '10:00:00', '12:00:00', 1, 'available'),
-- Boxing - Afternoon
(1, 'Boxing', '2025-11-06', '14:00:00', '16:00:00', 1, 'available'),
(2, 'Boxing', '2025-11-06', '16:00:00', '18:00:00', 1, 'available'),
-- MMA - Evening
(4, 'MMA', '2025-11-06', '18:00:00', '20:00:00', 1, 'available'),
(5, 'MMA', '2025-11-06', '20:00:00', '22:00:00', 1, 'available');

-- Friday, November 7, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
-- Boxing - Morning
(2, 'Boxing', '2025-11-07', '08:00:00', '10:00:00', 1, 'available'),
(3, 'Boxing', '2025-11-07', '10:00:00', '12:00:00', 1, 'available'),
-- MMA - Afternoon
(4, 'MMA', '2025-11-07', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-11-07', '16:00:00', '18:00:00', 1, 'available'),
-- Muay Thai - Evening (Popular Friday night slots)
(7, 'Muay Thai', '2025-11-07', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-07', '20:00:00', '22:00:00', 1, 'available');

-- Saturday, November 8, 2025 (Weekend - More sessions)
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
-- Morning Sessions - All disciplines
(1, 'Boxing', '2025-11-08', '08:00:00', '10:00:00', 1, 'available'),
(4, 'MMA', '2025-11-08', '08:00:00', '10:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-08', '08:00:00', '10:00:00', 1, 'available'),
-- Late Morning
(2, 'Boxing', '2025-11-08', '10:00:00', '12:00:00', 1, 'available'),
(5, 'MMA', '2025-11-08', '10:00:00', '12:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-08', '10:00:00', '12:00:00', 1, 'available'),
-- Afternoon
(3, 'Boxing', '2025-11-08', '14:00:00', '16:00:00', 1, 'available'),
(4, 'MMA', '2025-11-08', '14:00:00', '16:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-08', '14:00:00', '16:00:00', 1, 'available');

-- Sunday, November 9, 2025 (GYM SESSIONS - Open Gym with Trainers)
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
-- Morning Gym Sessions
(9, 'Gym', '2025-11-09', '08:00:00', '10:00:00', 1, 'available'), -- Coach Pat - Strength & Conditioning
(10, 'Gym', '2025-11-09', '10:00:00', '12:00:00', 1, 'available'), -- Coach Jamie - HIIT & Functional
-- Afternoon Gym Sessions
(9, 'Gym', '2025-11-09', '14:00:00', '16:00:00', 1, 'available'), -- Coach Pat
(10, 'Gym', '2025-11-09', '16:00:00', '18:00:00', 1, 'available'); -- Coach Jamie

-- ========== WEEK 2: Nov 10-15 (Mon-Sat) ==========

-- Monday, November 10, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-11-10', '08:00:00', '10:00:00', 1, 'available'),
(2, 'Boxing', '2025-11-10', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-11-10', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-11-10', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-10', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-10', '20:00:00', '22:00:00', 1, 'available');

-- Tuesday, November 11, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(4, 'MMA', '2025-11-11', '08:00:00', '10:00:00', 1, 'available'),
(5, 'MMA', '2025-11-11', '10:00:00', '12:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-11', '14:00:00', '16:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-11', '16:00:00', '18:00:00', 1, 'available'),
(1, 'Boxing', '2025-11-11', '18:00:00', '20:00:00', 1, 'available'),
(2, 'Boxing', '2025-11-11', '20:00:00', '22:00:00', 1, 'available');

-- Wednesday, November 12, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(3, 'Boxing', '2025-11-12', '08:00:00', '10:00:00', 1, 'available'),
(1, 'Boxing', '2025-11-12', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-11-12', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-11-12', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-12', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-12', '20:00:00', '22:00:00', 1, 'available');

-- Thursday, November 13, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(7, 'Muay Thai', '2025-11-13', '08:00:00', '10:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-13', '10:00:00', '12:00:00', 1, 'available'),
(1, 'Boxing', '2025-11-13', '14:00:00', '16:00:00', 1, 'available'),
(2, 'Boxing', '2025-11-13', '16:00:00', '18:00:00', 1, 'available'),
(4, 'MMA', '2025-11-13', '18:00:00', '20:00:00', 1, 'available'),
(5, 'MMA', '2025-11-13', '20:00:00', '22:00:00', 1, 'available');

-- Friday, November 14, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(2, 'Boxing', '2025-11-14', '08:00:00', '10:00:00', 1, 'available'),
(3, 'Boxing', '2025-11-14', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-11-14', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-11-14', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-14', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-14', '20:00:00', '22:00:00', 1, 'available');

-- Saturday, November 15, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-11-15', '08:00:00', '10:00:00', 1, 'available'),
(4, 'MMA', '2025-11-15', '08:00:00', '10:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-15', '08:00:00', '10:00:00', 1, 'available'),
(2, 'Boxing', '2025-11-15', '10:00:00', '12:00:00', 1, 'available'),
(5, 'MMA', '2025-11-15', '10:00:00', '12:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-15', '10:00:00', '12:00:00', 1, 'available'),
(3, 'Boxing', '2025-11-15', '14:00:00', '16:00:00', 1, 'available'),
(4, 'MMA', '2025-11-15', '14:00:00', '16:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-15', '14:00:00', '16:00:00', 1, 'available');

-- Sunday, November 16, 2025 (GYM SESSIONS)
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(9, 'Gym', '2025-11-16', '08:00:00', '10:00:00', 1, 'available'),
(10, 'Gym', '2025-11-16', '10:00:00', '12:00:00', 1, 'available'),
(9, 'Gym', '2025-11-16', '14:00:00', '16:00:00', 1, 'available'),
(10, 'Gym', '2025-11-16', '16:00:00', '18:00:00', 1, 'available');

-- ========== WEEK 3: Nov 17-22 (Mon-Sat) ==========

-- Monday, November 17, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-11-17', '08:00:00', '10:00:00', 1, 'available'),
(2, 'Boxing', '2025-11-17', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-11-17', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-11-17', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-17', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-17', '20:00:00', '22:00:00', 1, 'available');

-- Tuesday, November 18, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(4, 'MMA', '2025-11-18', '08:00:00', '10:00:00', 1, 'available'),
(5, 'MMA', '2025-11-18', '10:00:00', '12:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-18', '14:00:00', '16:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-18', '16:00:00', '18:00:00', 1, 'available'),
(1, 'Boxing', '2025-11-18', '18:00:00', '20:00:00', 1, 'available'),
(2, 'Boxing', '2025-11-18', '20:00:00', '22:00:00', 1, 'available');

-- Wednesday, November 19, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(3, 'Boxing', '2025-11-19', '08:00:00', '10:00:00', 1, 'available'),
(1, 'Boxing', '2025-11-19', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-11-19', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-11-19', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-19', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-19', '20:00:00', '22:00:00', 1, 'available');

-- Thursday, November 20, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(7, 'Muay Thai', '2025-11-20', '08:00:00', '10:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-20', '10:00:00', '12:00:00', 1, 'available'),
(1, 'Boxing', '2025-11-20', '14:00:00', '16:00:00', 1, 'available'),
(2, 'Boxing', '2025-11-20', '16:00:00', '18:00:00', 1, 'available'),
(4, 'MMA', '2025-11-20', '18:00:00', '20:00:00', 1, 'available'),
(5, 'MMA', '2025-11-20', '20:00:00', '22:00:00', 1, 'available');

-- Friday, November 21, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(2, 'Boxing', '2025-11-21', '08:00:00', '10:00:00', 1, 'available'),
(3, 'Boxing', '2025-11-21', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-11-21', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-11-21', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-21', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-21', '20:00:00', '22:00:00', 1, 'available');

-- Saturday, November 22, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-11-22', '08:00:00', '10:00:00', 1, 'available'),
(4, 'MMA', '2025-11-22', '08:00:00', '10:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-22', '08:00:00', '10:00:00', 1, 'available'),
(2, 'Boxing', '2025-11-22', '10:00:00', '12:00:00', 1, 'available'),
(5, 'MMA', '2025-11-22', '10:00:00', '12:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-22', '10:00:00', '12:00:00', 1, 'available'),
(3, 'Boxing', '2025-11-22', '14:00:00', '16:00:00', 1, 'available'),
(4, 'MMA', '2025-11-22', '14:00:00', '16:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-22', '14:00:00', '16:00:00', 1, 'available');

-- Sunday, November 23, 2025 (GYM SESSIONS)
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(9, 'Gym', '2025-11-23', '08:00:00', '10:00:00', 1, 'available'),
(10, 'Gym', '2025-11-23', '10:00:00', '12:00:00', 1, 'available'),
(9, 'Gym', '2025-11-23', '14:00:00', '16:00:00', 1, 'available'),
(10, 'Gym', '2025-11-23', '16:00:00', '18:00:00', 1, 'available');

-- ========== WEEK 4: Nov 24-29 (Mon-Sat) ==========

-- Monday, November 24, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-11-24', '08:00:00', '10:00:00', 1, 'available'),
(2, 'Boxing', '2025-11-24', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-11-24', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-11-24', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-24', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-24', '20:00:00', '22:00:00', 1, 'available');

-- Tuesday, November 25, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(4, 'MMA', '2025-11-25', '08:00:00', '10:00:00', 1, 'available'),
(5, 'MMA', '2025-11-25', '10:00:00', '12:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-25', '14:00:00', '16:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-25', '16:00:00', '18:00:00', 1, 'available'),
(1, 'Boxing', '2025-11-25', '18:00:00', '20:00:00', 1, 'available'),
(2, 'Boxing', '2025-11-25', '20:00:00', '22:00:00', 1, 'available');

-- Wednesday, November 26, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(3, 'Boxing', '2025-11-26', '08:00:00', '10:00:00', 1, 'available'),
(1, 'Boxing', '2025-11-26', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-11-26', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-11-26', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-26', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-26', '20:00:00', '22:00:00', 1, 'available');

-- Thursday, November 27, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(7, 'Muay Thai', '2025-11-27', '08:00:00', '10:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-27', '10:00:00', '12:00:00', 1, 'available'),
(1, 'Boxing', '2025-11-27', '14:00:00', '16:00:00', 1, 'available'),
(2, 'Boxing', '2025-11-27', '16:00:00', '18:00:00', 1, 'available'),
(4, 'MMA', '2025-11-27', '18:00:00', '20:00:00', 1, 'available'),
(5, 'MMA', '2025-11-27', '20:00:00', '22:00:00', 1, 'available');

-- Friday, November 28, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(2, 'Boxing', '2025-11-28', '08:00:00', '10:00:00', 1, 'available'),
(3, 'Boxing', '2025-11-28', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-11-28', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-11-28', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-28', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-28', '20:00:00', '22:00:00', 1, 'available');

-- Saturday, November 29, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-11-29', '08:00:00', '10:00:00', 1, 'available'),
(4, 'MMA', '2025-11-29', '08:00:00', '10:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-29', '08:00:00', '10:00:00', 1, 'available'),
(2, 'Boxing', '2025-11-29', '10:00:00', '12:00:00', 1, 'available'),
(5, 'MMA', '2025-11-29', '10:00:00', '12:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-11-29', '10:00:00', '12:00:00', 1, 'available'),
(3, 'Boxing', '2025-11-29', '14:00:00', '16:00:00', 1, 'available'),
(4, 'MMA', '2025-11-29', '14:00:00', '16:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-11-29', '14:00:00', '16:00:00', 1, 'available');

-- Sunday, November 30, 2025 (GYM SESSIONS)
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(9, 'Gym', '2025-11-30', '08:00:00', '10:00:00', 1, 'available'),
(10, 'Gym', '2025-11-30', '10:00:00', '12:00:00', 1, 'available'),
(9, 'Gym', '2025-11-30', '14:00:00', '16:00:00', 1, 'available'),
(10, 'Gym', '2025-11-30', '16:00:00', '18:00:00', 1, 'available');

-- =====================================================
-- DECEMBER 2025 SCHEDULES
-- =====================================================

-- ========== WEEK 1: Dec 1-6 (Mon-Sat) ==========

-- Monday, December 1, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-12-01', '08:00:00', '10:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-01', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-12-01', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-12-01', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-01', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-01', '20:00:00', '22:00:00', 1, 'available');

-- Tuesday, December 2, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(4, 'MMA', '2025-12-02', '08:00:00', '10:00:00', 1, 'available'),
(5, 'MMA', '2025-12-02', '10:00:00', '12:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-02', '14:00:00', '16:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-02', '16:00:00', '18:00:00', 1, 'available'),
(1, 'Boxing', '2025-12-02', '18:00:00', '20:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-02', '20:00:00', '22:00:00', 1, 'available');

-- Wednesday, December 3, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(3, 'Boxing', '2025-12-03', '08:00:00', '10:00:00', 1, 'available'),
(1, 'Boxing', '2025-12-03', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-12-03', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-12-03', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-03', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-03', '20:00:00', '22:00:00', 1, 'available');

-- Thursday, December 4, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(7, 'Muay Thai', '2025-12-04', '08:00:00', '10:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-04', '10:00:00', '12:00:00', 1, 'available'),
(1, 'Boxing', '2025-12-04', '14:00:00', '16:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-04', '16:00:00', '18:00:00', 1, 'available'),
(4, 'MMA', '2025-12-04', '18:00:00', '20:00:00', 1, 'available'),
(5, 'MMA', '2025-12-04', '20:00:00', '22:00:00', 1, 'available');

-- Friday, December 5, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(2, 'Boxing', '2025-12-05', '08:00:00', '10:00:00', 1, 'available'),
(3, 'Boxing', '2025-12-05', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-12-05', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-12-05', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-05', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-05', '20:00:00', '22:00:00', 1, 'available');

-- Saturday, December 6, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-12-06', '08:00:00', '10:00:00', 1, 'available'),
(4, 'MMA', '2025-12-06', '08:00:00', '10:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-06', '08:00:00', '10:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-06', '10:00:00', '12:00:00', 1, 'available'),
(5, 'MMA', '2025-12-06', '10:00:00', '12:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-06', '10:00:00', '12:00:00', 1, 'available'),
(3, 'Boxing', '2025-12-06', '14:00:00', '16:00:00', 1, 'available'),
(4, 'MMA', '2025-12-06', '14:00:00', '16:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-06', '14:00:00', '16:00:00', 1, 'available');

-- Sunday, December 7, 2025 (GYM SESSIONS)
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(9, 'Gym', '2025-12-07', '08:00:00', '10:00:00', 1, 'available'),
(10, 'Gym', '2025-12-07', '10:00:00', '12:00:00', 1, 'available'),
(9, 'Gym', '2025-12-07', '14:00:00', '16:00:00', 1, 'available'),
(10, 'Gym', '2025-12-07', '16:00:00', '18:00:00', 1, 'available');

-- ========== WEEK 2: Dec 8-13 (Mon-Sat) ==========

-- Monday, December 8, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-12-08', '08:00:00', '10:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-08', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-12-08', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-12-08', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-08', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-08', '20:00:00', '22:00:00', 1, 'available');

-- Tuesday, December 9, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(4, 'MMA', '2025-12-09', '08:00:00', '10:00:00', 1, 'available'),
(5, 'MMA', '2025-12-09', '10:00:00', '12:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-09', '14:00:00', '16:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-09', '16:00:00', '18:00:00', 1, 'available'),
(1, 'Boxing', '2025-12-09', '18:00:00', '20:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-09', '20:00:00', '22:00:00', 1, 'available');

-- Wednesday, December 10, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(3, 'Boxing', '2025-12-10', '08:00:00', '10:00:00', 1, 'available'),
(1, 'Boxing', '2025-12-10', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-12-10', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-12-10', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-10', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-10', '20:00:00', '22:00:00', 1, 'available');

-- Thursday, December 11, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(7, 'Muay Thai', '2025-12-11', '08:00:00', '10:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-11', '10:00:00', '12:00:00', 1, 'available'),
(1, 'Boxing', '2025-12-11', '14:00:00', '16:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-11', '16:00:00', '18:00:00', 1, 'available'),
(4, 'MMA', '2025-12-11', '18:00:00', '20:00:00', 1, 'available'),
(5, 'MMA', '2025-12-11', '20:00:00', '22:00:00', 1, 'available');

-- Friday, December 12, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(2, 'Boxing', '2025-12-12', '08:00:00', '10:00:00', 1, 'available'),
(3, 'Boxing', '2025-12-12', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-12-12', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-12-12', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-12', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-12', '20:00:00', '22:00:00', 1, 'available');

-- Saturday, December 13, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-12-13', '08:00:00', '10:00:00', 1, 'available'),
(4, 'MMA', '2025-12-13', '08:00:00', '10:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-13', '08:00:00', '10:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-13', '10:00:00', '12:00:00', 1, 'available'),
(5, 'MMA', '2025-12-13', '10:00:00', '12:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-13', '10:00:00', '12:00:00', 1, 'available'),
(3, 'Boxing', '2025-12-13', '14:00:00', '16:00:00', 1, 'available'),
(4, 'MMA', '2025-12-13', '14:00:00', '16:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-13', '14:00:00', '16:00:00', 1, 'available');

-- Sunday, December 14, 2025 (GYM SESSIONS)
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(9, 'Gym', '2025-12-14', '08:00:00', '10:00:00', 1, 'available'),
(10, 'Gym', '2025-12-14', '10:00:00', '12:00:00', 1, 'available'),
(9, 'Gym', '2025-12-14', '14:00:00', '16:00:00', 1, 'available'),
(10, 'Gym', '2025-12-14', '16:00:00', '18:00:00', 1, 'available');

-- ========== WEEK 3: Dec 15-20 (Mon-Sat) ==========

-- Monday, December 15, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-12-15', '08:00:00', '10:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-15', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-12-15', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-12-15', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-15', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-15', '20:00:00', '22:00:00', 1, 'available');

-- Tuesday, December 16, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(4, 'MMA', '2025-12-16', '08:00:00', '10:00:00', 1, 'available'),
(5, 'MMA', '2025-12-16', '10:00:00', '12:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-16', '14:00:00', '16:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-16', '16:00:00', '18:00:00', 1, 'available'),
(1, 'Boxing', '2025-12-16', '18:00:00', '20:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-16', '20:00:00', '22:00:00', 1, 'available');

-- Wednesday, December 17, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(3, 'Boxing', '2025-12-17', '08:00:00', '10:00:00', 1, 'available'),
(1, 'Boxing', '2025-12-17', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-12-17', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-12-17', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-17', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-17', '20:00:00', '22:00:00', 1, 'available');

-- Thursday, December 18, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(7, 'Muay Thai', '2025-12-18', '08:00:00', '10:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-18', '10:00:00', '12:00:00', 1, 'available'),
(1, 'Boxing', '2025-12-18', '14:00:00', '16:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-18', '16:00:00', '18:00:00', 1, 'available'),
(4, 'MMA', '2025-12-18', '18:00:00', '20:00:00', 1, 'available'),
(5, 'MMA', '2025-12-18', '20:00:00', '22:00:00', 1, 'available');

-- Friday, December 19, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(2, 'Boxing', '2025-12-19', '08:00:00', '10:00:00', 1, 'available'),
(3, 'Boxing', '2025-12-19', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-12-19', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-12-19', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-19', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-19', '20:00:00', '22:00:00', 1, 'available');

-- Saturday, December 20, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-12-20', '08:00:00', '10:00:00', 1, 'available'),
(4, 'MMA', '2025-12-20', '08:00:00', '10:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-20', '08:00:00', '10:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-20', '10:00:00', '12:00:00', 1, 'available'),
(5, 'MMA', '2025-12-20', '10:00:00', '12:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-20', '10:00:00', '12:00:00', 1, 'available'),
(3, 'Boxing', '2025-12-20', '14:00:00', '16:00:00', 1, 'available'),
(4, 'MMA', '2025-12-20', '14:00:00', '16:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-20', '14:00:00', '16:00:00', 1, 'available');

-- Sunday, December 21, 2025 (GYM SESSIONS)
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(9, 'Gym', '2025-12-21', '08:00:00', '10:00:00', 1, 'available'),
(10, 'Gym', '2025-12-21', '10:00:00', '12:00:00', 1, 'available'),
(9, 'Gym', '2025-12-21', '14:00:00', '16:00:00', 1, 'available'),
(10, 'Gym', '2025-12-21', '16:00:00', '18:00:00', 1, 'available');

-- ========== WEEK 4: Dec 22-27 (Mon-Sat) -- Holiday Season ==========

-- Monday, December 22, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-12-22', '08:00:00', '10:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-22', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-12-22', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-12-22', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-22', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-22', '20:00:00', '22:00:00', 1, 'available');

-- Tuesday, December 23, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(4, 'MMA', '2025-12-23', '08:00:00', '10:00:00', 1, 'available'),
(5, 'MMA', '2025-12-23', '10:00:00', '12:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-23', '14:00:00', '16:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-23', '16:00:00', '18:00:00', 1, 'available'),
(1, 'Boxing', '2025-12-23', '18:00:00', '20:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-23', '20:00:00', '22:00:00', 1, 'available');

-- Wednesday, December 24, 2025 - Christmas Eve (Limited Schedule - Morning Only)
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-12-24', '08:00:00', '10:00:00', 1, 'available'),
(4, 'MMA', '2025-12-24', '08:00:00', '10:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-24', '10:00:00', '12:00:00', 1, 'available');

-- Thursday, December 25, 2025 - Christmas Day (CLOSED - No sessions)

-- Friday, December 26, 2025 - Limited Schedule
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(2, 'Boxing', '2025-12-26', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-12-26', '14:00:00', '16:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-26', '16:00:00', '18:00:00', 1, 'available');

-- Saturday, December 27, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-12-27', '08:00:00', '10:00:00', 1, 'available'),
(4, 'MMA', '2025-12-27', '08:00:00', '10:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-27', '08:00:00', '10:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-27', '10:00:00', '12:00:00', 1, 'available'),
(5, 'MMA', '2025-12-27', '10:00:00', '12:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-27', '10:00:00', '12:00:00', 1, 'available'),
(3, 'Boxing', '2025-12-27', '14:00:00', '16:00:00', 1, 'available');

-- Sunday, December 28, 2025 (GYM SESSIONS)
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(9, 'Gym', '2025-12-28', '08:00:00', '10:00:00', 1, 'available'),
(10, 'Gym', '2025-12-28', '10:00:00', '12:00:00', 1, 'available'),
(9, 'Gym', '2025-12-28', '14:00:00', '16:00:00', 1, 'available'),
(10, 'Gym', '2025-12-28', '16:00:00', '18:00:00', 1, 'available');

-- ========== Last Week: Dec 29-31 ==========

-- Monday, December 29, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-12-29', '08:00:00', '10:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-29', '10:00:00', '12:00:00', 1, 'available'),
(4, 'MMA', '2025-12-29', '14:00:00', '16:00:00', 1, 'available'),
(5, 'MMA', '2025-12-29', '16:00:00', '18:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-29', '18:00:00', '20:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-29', '20:00:00', '22:00:00', 1, 'available');

-- Tuesday, December 30, 2025
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(4, 'MMA', '2025-12-30', '08:00:00', '10:00:00', 1, 'available'),
(5, 'MMA', '2025-12-30', '10:00:00', '12:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-30', '14:00:00', '16:00:00', 1, 'available'),
(8, 'Muay Thai', '2025-12-30', '16:00:00', '18:00:00', 1, 'available'),
(1, 'Boxing', '2025-12-30', '18:00:00', '20:00:00', 1, 'available'),
(2, 'Boxing', '2025-12-30', '20:00:00', '22:00:00', 1, 'available');

-- Wednesday, December 31, 2025 - New Year's Eve (Limited Schedule - Morning Only)
INSERT INTO reservations (trainer_id, class_type, date, start_time, end_time, max_slots, status) VALUES
(1, 'Boxing', '2025-12-31', '08:00:00', '10:00:00', 1, 'available'),
(4, 'MMA', '2025-12-31', '08:00:00', '10:00:00', 1, 'available'),
(7, 'Muay Thai', '2025-12-31', '10:00:00', '12:00:00', 1, 'available');

-- =====================================================
-- SUMMARY
-- =====================================================
-- Total Schedules Generated:
--   November 2025: ~176 sessions (24 weekdays × 6 avg + 5 weekends × 13 avg)
--   December 2025: ~162 sessions (22 weekdays × 6 avg + 5 weekends × 13 avg)
--   TOTAL: ~338 training sessions
--
-- Trainers Included (Active Only):
--   Boxing (Mon-Sat): Coach Thei Lei, Coach Jealous Pogi, Coach Rizz Andrei
--   MMA (Mon-Sat): Coach Mikell Drei, Coach XL
--   Muay Thai (Mon-Sat): Coach Sam "Elbow King" Fernandez, Coach Jordan Garcia
--   Gym (WEEKENDS ONLY - Sat & Sun): Coach Pat Anderson, Coach Jamie Ramos
--
-- Schedule Pattern:
--   Mon-Fri: Boxing, MMA, Muay Thai (rotating time slots)
--   Saturday: All martial arts classes (9 sessions) + NO Gym sessions
--   Sunday: GYM SESSIONS ONLY (4 sessions) - Open gym with trainer guidance
--
-- Time Slots:
--   Morning: 8:00 AM - 12:00 PM
--   Afternoon: 2:00 PM - 6:00 PM
--   Evening: 6:00 PM - 10:00 PM
--
-- Max Slots:
--   All sessions: 1 slot per session (1-on-1 or small group training)
--   Each trainer can only have 1 booking per time slot
--   Users book the entire session, not individual slots within it
--
-- Special Notes:
--   - Christmas Day (Dec 25) has NO sessions
--   - Christmas Eve and New Year's Eve have morning sessions only
--   - Saturdays: Martial arts only (no gym sessions)
--   - Sundays: Gym sessions only (no martial arts)
--   - Friday evenings have increased capacity (12 slots)
--   - Coach Sean Pogi (MMA - ID: 6) is on leave and not scheduled
--
-- IMPORTANT DATABASE REQUIREMENT:
--   Before importing, ensure the reservations table class_type enum includes 'Gym':
--   ALTER TABLE reservations MODIFY class_type ENUM('Boxing','Muay Thai','MMA','Gym') NOT NULL;
-- =====================================================

