-- Combined Database Setup for LGU Road and Infrastructure Department
-- This script combines all database setup files for phpMyAdmin upload
-- Updated: January 22, 2026
-- Purpose: Complete database initialization with all tables and sample data

-- Create database if it doesn't exist
-- CREATE DATABASE IF NOT EXISTS lgu_road_infra;
-- USE lgu_road_infra;

-- Drop existing tables to start fresh (for clean installation)
-- Drop in reverse order of dependencies
DROP TABLE IF EXISTS publication_progress;
DROP TABLE IF EXISTS public_publications;
DROP TABLE IF EXISTS repair_tasks;
DROP TABLE IF EXISTS inspections;
DROP TABLE IF EXISTS user_permissions;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS user_activity_log;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS maintenance_schedule;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS gis_data;
DROP TABLE IF EXISTS inspection_reports;
DROP TABLE IF EXISTS cost_assessments;
DROP TABLE IF EXISTS damage_reports;
DROP TABLE IF EXISTS users;

-- Users table (enhanced with all fields)
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','lgu_officer','engineer','citizen') NOT NULL DEFAULT 'citizen',
  `status` enum('active','inactive','suspended','pending') NOT NULL DEFAULT 'pending',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Login attempts table
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `success` tinyint(1) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `attempt_time` (`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- User sessions table
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- User activity log table
CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `activity_description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `activity_type` (`activity_type`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Damage Reports table
CREATE TABLE `damage_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` varchar(20) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('pending','in_progress','resolved','closed') NOT NULL DEFAULT 'pending',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `images` text DEFAULT NULL,
  `reported_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assigned_to` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_id` (`report_id`),
  KEY `reporter_id` (`reporter_id`),
  KEY `status` (`status`),
  KEY `severity` (`severity`),
  KEY `reported_at` (`reported_at`),
  FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Cost Assessments table
CREATE TABLE `cost_assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_id` varchar(20) NOT NULL,
  `damage_report_id` int(11) NOT NULL,
  `assessor_id` int(11) NOT NULL,
  `labor_cost` decimal(10,2) DEFAULT NULL,
  `material_cost` decimal(10,2) DEFAULT NULL,
  `equipment_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `assessment_notes` text DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
  `assessment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assessment_id` (`assessment_id`),
  KEY `damage_report_id` (`damage_report_id`),
  KEY `assessor_id` (`assessor_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`damage_report_id`) REFERENCES `damage_reports` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assessor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inspection Reports table (Legacy version)
CREATE TABLE `inspection_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inspection_id` varchar(20) NOT NULL,
  `inspector_id` int(11) NOT NULL,
  `damage_report_id` int(11) DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `inspection_type` enum('initial','follow_up','final','special') NOT NULL DEFAULT 'initial',
  `findings` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `inspection_status` enum('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `scheduled_date` date DEFAULT NULL,
  `completed_date` timestamp NULL DEFAULT NULL,
  `next_inspection_date` date DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `images` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `inspection_id` (`inspection_id`),
  KEY `inspector_id` (`inspector_id`),
  KEY `damage_report_id` (`damage_report_id`),
  KEY `inspection_status` (`inspection_status`),
  KEY `scheduled_date` (`scheduled_date`),
  FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`damage_report_id`) REFERENCES `damage_reports` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- GIS Data table
CREATE TABLE `gis_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `feature_id` varchar(20) NOT NULL,
  `feature_type` enum('damage','infrastructure','maintenance','zone') NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `properties` json DEFAULT NULL,
  `status` enum('active','inactive','maintenance') NOT NULL DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `feature_id` (`feature_id`),
  KEY `feature_type` (`feature_type`),
  KEY `status` (`status`),
  KEY `location` (`latitude`, `longitude`),
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Documents table
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `document_type` enum('report','image','video','pdf','other') NOT NULL,
  `category` enum('damage_report','cost_assessment','inspection','maintenance','general') NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `download_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_id` (`document_id`),
  KEY `document_type` (`document_type`),
  KEY `category` (`category`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `related_id` (`related_id`),
  FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Maintenance Schedule table
CREATE TABLE `maintenance_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` varchar(20) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `task_type` enum('routine','emergency','inspection','repair') NOT NULL DEFAULT 'routine',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('scheduled','in_progress','completed','cancelled','postponed') NOT NULL DEFAULT 'scheduled',
  `scheduled_date` datetime NOT NULL,
  `estimated_duration` int DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `completed_date` timestamp NULL DEFAULT NULL,
  `actual_duration` int DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `materials_used` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_id` (`task_id`),
  KEY `task_type` (`task_type`),
  KEY `status` (`status`),
  KEY `scheduled_date` (`scheduled_date`),
  KEY `assigned_to` (`assigned_to`),
  FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Notifications table (Consolidated)
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` json DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type` (`type`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- User Permissions table
CREATE TABLE `user_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `permission` varchar(50) NOT NULL,
  `module` varchar(50) NOT NULL,
  `granted_by` int(11) NOT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `permission` (`permission`),
  KEY `module` (`module`),
  KEY `granted_by` (`granted_by`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_permission` (`user_id`, `permission`, `module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inspections Table (Modern)
CREATE TABLE `inspections` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `inspection_id` varchar(20) NOT NULL,
    `location` varchar(255) NOT NULL,
    `inspection_date` date NOT NULL,
    `inspector_id` int(11) NOT NULL,
    `description` text NOT NULL,
    `severity` enum('low', 'medium', 'high') NOT NULL,
    `status` enum('pending', 'approved', 'rejected') DEFAULT 'pending',
    `review_date` date DEFAULT NULL,
    `reviewed_by` int(11) DEFAULT NULL,
    `review_notes` text DEFAULT NULL,
    `priority` varchar(10) DEFAULT NULL,
    `estimated_cost` decimal(10,2) DEFAULT NULL,
    `photos` json DEFAULT NULL,
    `estimated_damage` varchar(255) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `inspection_id` (`inspection_id`),
    KEY `inspector_id` (`inspector_id`),
    KEY `reviewed_by` (`reviewed_by`),
    KEY `status` (`status`),
    KEY `inspection_date` (`inspection_date`),
    FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Repair Tasks Table
CREATE TABLE `repair_tasks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `task_id` varchar(20) NOT NULL,
    `inspection_id` varchar(20) NOT NULL,
    `assigned_to` varchar(255) DEFAULT NULL,
    `status` enum('pending', 'in_progress', 'completed') DEFAULT 'pending',
    `priority` enum('low', 'medium', 'high') NOT NULL,
    `estimated_cost` decimal(10,2) DEFAULT NULL,
    `created_date` date NOT NULL,
    `estimated_completion` date DEFAULT NULL,
    `actual_completion` date DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `created_by` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `task_id` (`task_id`),
    KEY `inspection_id` (`inspection_id`),
    KEY `created_by` (`created_by`),
    KEY `status` (`status`),
    FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`inspection_id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Public Publications Table
CREATE TABLE `public_publications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publication_id` varchar(20) NOT NULL,
  `damage_report_id` int(11) NOT NULL,
  `inspection_report_id` int(11) DEFAULT NULL,
  `maintenance_task_id` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `publication_date` timestamp NULL DEFAULT NULL,
  `published_by` int(11) DEFAULT NULL,
  `road_name` varchar(255) NOT NULL,
  `issue_summary` text NOT NULL,
  `issue_type` enum('pothole','crack','drainage','surface_damage','other') NOT NULL,
  `severity_public` enum('low','medium','high') NOT NULL,
  `status_public` enum('reported','under_repair','completed','fixed') NOT NULL DEFAULT 'reported',
  `date_reported` date NOT NULL,
  `repair_start_date` date DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `repair_duration_days` int DEFAULT NULL,
  `progress_history` json DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `archive_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `publication_id` (`publication_id`),
  KEY `damage_report_id` (`damage_report_id`),
  KEY `is_published` (`is_published`),
  KEY `status_public` (`status_public`),
  FOREIGN KEY (`damage_report_id`) REFERENCES `damage_reports` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`inspection_report_id`) REFERENCES `inspection_reports` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`maintenance_task_id`) REFERENCES `maintenance_schedule` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`published_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Publication Progress table
CREATE TABLE `publication_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publication_id` int(11) NOT NULL,
  `progress_date` date NOT NULL,
  `status` enum('reported','under_assessment','inspection_scheduled','under_repair','completed','fixed') NOT NULL,
  `description` text NOT NULL,
  `is_public_visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `publication_id` (`publication_id`),
  FOREIGN KEY (`publication_id`) REFERENCES `public_publications` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- INSERT DEFAULT USERS AND SAMPLE DATA
-- ========================================

-- Insert default users
INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `role`, `status`, `email_verified`) 
VALUES 
(1, 'System', 'Administrator', 'admin@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 1),
(2, 'LGU', 'Officer', 'officer@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lgu_officer', 'active', 1),
(3, 'Engineer', 'User', 'engineer@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'engineer', 'active', 1),
(4, 'Citizen', 'User', 'citizen@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'citizen', 'active', 1)
ON DUPLICATE KEY UPDATE `password` = VALUES(`password`);

-- Insert additional sample users
INSERT INTO `users` (`id`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `role`, `status`, `email_verified`) 
VALUES 
(5, 'Juan', 'Ching', 'De la Cruz', 'juan.delacruz@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'engineer', 'pending', 0),
(6, 'Maria', 'Clara', 'Reyes', 'maria.reyes@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'citizen', 'active', 1),
(7, 'Carlos', 'Luis', 'Garcia', 'carlos.garcia@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lgu_officer', 'active', 1)
ON DUPLICATE KEY UPDATE `password` = VALUES(`password`);

-- Insert sample damage reports
INSERT INTO `damage_reports` (`id`, `report_id`, `reporter_id`, `location`, `description`, `severity`, `status`, `latitude`, `longitude`, `estimated_cost`) 
VALUES 
(1, 'DR-2025-001', 2, 'Commonwealth Avenue', 'Large pothole causing traffic disruption', 'high', 'pending', 14.6355, 121.0320, 150000.00),
(2, 'DR-2025-002', 2, 'EDSA Complex', 'Road crack spreading', 'medium', 'in_progress', 14.6400, 121.0400, 75000.00),
(3, 'DR-2025-003', 6, 'Quezon City Circle', 'Multiple small potholes', 'low', 'resolved', 14.6300, 121.0200, 25000.00);

-- Insert sample cost assessments
INSERT INTO `cost_assessments` (`assessment_id`, `damage_report_id`, `assessor_id`, `labor_cost`, `material_cost`, `equipment_cost`, `total_cost`, `status`) 
VALUES 
('CA-2025-001', 1, 3, 50000.00, 80000.00, 20000.00, 150000.00, 'approved'),
('CA-2025-002', 2, 3, 30000.00, 35000.00, 10000.00, 75000.00, 'submitted');

-- Insert sample inspections (Modern)
INSERT INTO `inspections` (
    `inspection_id`, `location`, `inspection_date`, `inspector_id`, `description`, 
    `severity`, `status`, `photos`, `estimated_damage`
) VALUES 
('INSP-1023', 'Main Road, Brgy. 3', '2025-12-10', 3, 'Large pothole approximately 2 feet in diameter causing traffic hazards.', 'high', 'pending', '["pothole1.jpg", "pothole2.jpg"]', 'Road surface damage'),
('INSP-1024', 'Market Street', '2025-12-11', 3, 'Minor crack in road surface.', 'low', 'approved', '["crack1.jpg"]', 'Surface crack');

-- Insert sample repair task
INSERT INTO `repair_tasks` (
    `task_id`, `inspection_id`, `assigned_to`, `status`, `priority`, 
    `estimated_cost`, `created_date`, `estimated_completion`, `created_by`
) VALUES 
('REP-552', 'INSP-1024', 'Maintenance Team A', 'pending', 'low', 5000.00, '2025-12-12', '2025-12-20', 3);

-- Insert sample GIS data
INSERT INTO `gis_data` (`feature_id`, `feature_type`, `name`, `description`, `latitude`, `longitude`, `properties`, `created_by`) 
VALUES 
('GIS-001', 'damage', 'Commonwealth Pothole', 'Large pothole on main road', 14.6355, 121.0320, '{"severity": "high", "estimated_cost": 150000}', 3),
('GIS-002', 'infrastructure', 'EDSA Bridge', 'Main bridge structure', 14.6400, 121.0400, '{"type": "bridge", "year_built": 1998}', 3);

-- Insert sample maintenance schedule
INSERT INTO `maintenance_schedule` (`task_id`, `task_name`, `location`, `task_type`, `priority`, `status`, `scheduled_date`, `assigned_to`, `created_by`) 
VALUES 
('MT-2025-001', 'Road Repair - Commonwealth', 'Commonwealth Avenue', 'repair', 'high', 'scheduled', '2025-01-25 09:00:00', 3, 1),
('MT-2025-002', 'Bridge Inspection', 'EDSA Bridge', 'inspection', 'medium', 'scheduled', '2025-01-30 14:00:00', 3, 1);

-- Insert sample public publications
INSERT INTO `public_publications` (
  `publication_id`, `damage_report_id`, `road_name`, `issue_summary`, `issue_type`, 
  `severity_public`, `status_public`, `date_reported`, `repair_start_date`, 
  `completion_date`, `repair_duration_days`, `is_published`, `publication_date`, `published_by`
) VALUES 
('PUB-2025-001', 1, 'Commonwealth Avenue', 'Large pothole repair', 'pothole', 'high', 'completed', '2025-01-10', '2025-01-15', '2025-01-17', 7, 1, '2025-01-18', 1),
('PUB-2025-002', 2, 'EDSA Complex', 'Road crack repair', 'crack', 'medium', 'under_repair', '2025-01-12', '2025-01-20', NULL, NULL, 1, '2025-01-20', 1);

-- Insert sample publication progress
INSERT INTO `publication_progress` (`publication_id`, `progress_date`, `status`, `description`, `created_by`) VALUES 
(1, '2025-01-10', 'reported', 'Issue reported by citizen', 2),
(1, '2025-01-17', 'completed', 'Repair work completed', 3);

-- ========================================
-- SETUP COMPLETE
-- ========================================
