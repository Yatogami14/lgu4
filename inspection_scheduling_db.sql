-- Inspection Scheduling and Assignment Database Schema
-- Decentralized submodule for scheduling inspections and assigning inspectors

-- Drop and recreate the database to ensure a clean state and avoid tablespace errors.
DROP DATABASE IF EXISTS lgu_inspection_scheduling;
CREATE DATABASE lgu_inspection_scheduling;
USE lgu_inspection_scheduling;

-- To make the script re-runnable, drop tables if they exist.
-- Drop in reverse order of creation to avoid foreign key issues.
DROP TABLE IF EXISTS inspector_specializations;
DROP TABLE IF EXISTS inspections;

-- Inspections table
CREATE TABLE inspections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    business_id INT NOT NULL,
    inspector_id INT NULL,
    inspection_type_id INT NOT NULL,
    scheduled_date DATETIME NOT NULL,
    completed_date DATETIME NULL,
    status ENUM('scheduled', 'in_progress', 'completed', 'overdue') NOT NULL DEFAULT 'scheduled',
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    compliance_score INT NULL,
    total_violations INT DEFAULT 0,
    notes TEXT NULL,
    notes_ai_analysis JSON NULL,
    draft_data TEXT NULL,
    hash VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Note: Foreign keys removed for cross-DB decentralization
    INDEX idx_business (business_id),
    INDEX idx_inspector (inspector_id),
    INDEX idx_status (status),
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_hash (hash)
);

-- Inspector specializations table
CREATE TABLE inspector_specializations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    inspection_type_id INT NOT NULL,
    proficiency_level ENUM('beginner', 'intermediate', 'expert') DEFAULT 'intermediate',
    certification_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Note: Foreign keys removed for cross-DB decentralization
    UNIQUE KEY unique_user_specialization (user_id, inspection_type_id),
    INDEX idx_user (user_id),
    INDEX idx_inspection_type (inspection_type_id)
);

-- Insert sample inspections
INSERT INTO inspections (business_id, inspector_id, inspection_type_id, scheduled_date, status, priority, compliance_score, total_violations) VALUES
(1, 2, 1, '2024-01-15 09:00:00', 'scheduled', 'high', NULL, 0),
(2, 3, 2, '2024-01-16 10:00:00', 'in_progress', 'medium', 85, 2),
(3, 2, 3, '2024-01-14 14:00:00', 'completed', 'low', 92, 1),
(4, NULL, 1, '2024-01-20 09:00:00', 'scheduled', 'medium', NULL, 0),
(5, NULL, 2, '2024-01-21 10:00:00', 'scheduled', 'high', NULL, 0);

-- Insert sample inspector specializations
INSERT INTO inspector_specializations (user_id, inspection_type_id, proficiency_level, certification_date) VALUES
(2, 1, 'expert', '2023-06-15'),
(2, 3, 'intermediate', '2023-08-20'),
(3, 2, 'expert', '2023-05-10'),
(3, 4, 'intermediate', '2023-09-05'),
(1, 1, 'expert', '2022-12-01');
