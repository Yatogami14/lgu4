-- Violation Ticketing and Citation Issuance Database Schema
-- Decentralized submodule for managing violations and citations

-- Drop and recreate the database to ensure a clean state and avoid tablespace errors.
DROP DATABASE IF EXISTS lgu_violations_ticketing;
CREATE DATABASE lgu_violations_ticketing;
USE lgu_violations_ticketing;

-- To make the script re-runnable, drop tables if they exist.
DROP TABLE IF EXISTS violations;

-- Violations table
CREATE TABLE violations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    inspection_id INT NOT NULL,
    business_id INT NOT NULL,
    checklist_response_id INT NULL,
    media_id INT NULL,
    description TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    due_date DATE NULL,
    resolved_date DATE NULL,
    created_by INT NOT NULL,
    hash VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Note: Foreign keys removed for cross-DB decentralization
    INDEX idx_inspection (inspection_id),
    INDEX idx_business (business_id),
    INDEX idx_status (status),
    INDEX idx_severity (severity),
    INDEX idx_hash (hash)
);

-- Insert sample violations
INSERT INTO violations (inspection_id, business_id, checklist_response_id, media_id, description, severity, status, due_date, created_by) VALUES
-- Assuming business_id from core_db and inspection_scheduling_db sample data
(2, 2, 4, 2, 'Fire extinguisher not properly maintained', 'high', 'open', '2024-02-01', 3),
(2, 2, NULL, 5, 'Emergency exit sign partially obscured', 'medium', 'in_progress', '2024-01-25', 3),
(3, 3, 1, NULL, 'Minor cleanliness issue in storage area', 'low', 'resolved', '2024-01-20', 2),
(1, 1, 3, 1, 'Water supply quality below standard', 'medium', 'open', '2024-01-30', 2),
(2, 2, 7, NULL, 'Insufficient number of fire extinguishers', 'high', 'open', '2024-02-05', 3);
