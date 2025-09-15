-- Decentralized Digital Health & Safety Inspection Platform Database Schema
-- ==================================================================
-- Combined Database Schema for LGU Inspection Platform
-- This single file creates and populates all necessary databases.
-- It is designed for easy import in environments like XAMPP.
-- ==================================================================

-- Drop all databases to ensure a clean state
DROP DATABASE IF EXISTS lgu_core;
DROP DATABASE IF EXISTS lgu_inspection_scheduling;
DROP DATABASE IF EXISTS lgu_checklist_assessment;
DROP DATABASE IF EXISTS lgu_media_uploads;
DROP DATABASE IF EXISTS lgu_violations_ticketing;
DROP DATABASE IF EXISTS lgu_reports_notifications;

-- Create all databases
CREATE DATABASE lgu_core;
CREATE DATABASE lgu_inspection_scheduling;
CREATE DATABASE lgu_checklist_assessment;
CREATE DATABASE lgu_media_uploads;
CREATE DATABASE lgu_violations_ticketing;
CREATE DATABASE lgu_reports_notifications;


-- ==================================================================
-- Core Database Schema (lgu_core)
-- ==================================================================
USE lgu_core;

-- Users table with different roles
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'inspector', 'business_owner', 'community_user') NOT NULL DEFAULT 'inspector',
    avatar VARCHAR(255) NULL,
    department VARCHAR(255) NULL,
    certification VARCHAR(255) NULL,
    reset_token VARCHAR(64) NULL DEFAULT NULL,
    reset_token_expires_at DATETIME NULL DEFAULT NULL,
    remember_token_selector VARCHAR(32) NULL DEFAULT NULL,
    remember_token_validator_hash VARCHAR(255) NULL DEFAULT NULL,
    remember_token_expires_at DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_reset_token (reset_token),
    INDEX idx_remember_selector (remember_token_selector)
);

-- Sessions table for database-backed session handling
CREATE TABLE sessions (
    session_id VARCHAR(128) NOT NULL PRIMARY KEY,
    user_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    session_data TEXT NOT NULL,
    last_activity INT(11) NOT NULL,
    INDEX idx_user_id (user_id)
);

-- Businesses table
CREATE TABLE businesses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    owner_id INT NULL,
    inspector_id INT NULL,
    contact_number VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    business_type VARCHAR(100) NULL,
    registration_number VARCHAR(100) NULL,
    establishment_date DATE NULL,
    inspection_frequency ENUM('weekly', 'monthly', 'quarterly') DEFAULT 'monthly',
    last_inspection_date DATE NULL,
    next_inspection_date DATE NULL,
    is_compliant BOOLEAN DEFAULT TRUE,
    compliance_score INT DEFAULT 100,
    hash VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Note: Foreign keys to users removed for cross-DB decentralization
    INDEX idx_name (name),
    INDEX idx_business_type (business_type),
    INDEX idx_compliance (is_compliant),
    INDEX idx_next_inspection (next_inspection_date),
    INDEX idx_hash (hash)
);

-- Inspection types
CREATE TABLE inspection_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (name)
);

-- Blockchain table for decentralization
CREATE TABLE blockchain (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hash VARCHAR(64) NOT NULL,
    previous_hash VARCHAR(64) NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    data_hash VARCHAR(64) NOT NULL,
    INDEX idx_hash (hash),
    INDEX idx_previous_hash (previous_hash),
    INDEX idx_table_record (table_name, record_id)
);

-- Insert default inspection types
INSERT INTO inspection_types (name, description) VALUES
('Health & Sanitation', 'Health and sanitation compliance inspections'),
('Fire Safety', 'Fire safety and prevention inspections'),
('Building Safety', 'Building structure and safety inspections'),
('Environmental', 'Environmental compliance inspections'),
('Food Safety', 'Food safety and hygiene inspections');

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role, department, certification) VALUES
('Maria Santos', 'maria.santos@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Health and Safety Division', 'Certified Safety Inspector'),
('Juan Dela Cruz', 'juan.delacruz@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'inspector', 'Health and Safety Division', 'Certified Inspector'),
('Anna Reyes', 'anna.reyes@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'inspector', 'Fire Safety Division', 'Fire Safety Officer'),
('Carlos Mendoza', 'carlos.mendoza@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business_owner', 'Business Development', NULL),
('Liza Fernandez', 'liza.fernandez@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'community_user', 'Community Relations', NULL),
('Admin User', 'admin@gmail.com', '$2y$10$E/gL3h0v.dC.bA.fE.gH.iJ.kL.mN.oP.qR.sT.uV', 'admin', 'System Administration', 'Administrator');

-- Insert sample businesses
INSERT INTO businesses (name, address, contact_number, email, business_type, registration_number) VALUES
('ABC Restaurant', '123 Main St, Makati City', '+63 912 345 6789', 'abc.restaurant@email.com', 'Restaurant', 'BUS-001'),
('XYZ Mall', '456 Commerce Ave, BGC', '+63 917 654 3210', 'xyz.mall@email.com', 'Shopping Mall', 'BUS-002'),
('Tech Hub Office', '789 IT Park, Cebu', '+63 918 765 4321', 'tech.hub@email.com', 'Office Building', 'BUS-003'),
('Green Grocers', '321 Market St, Davao City', '+63 913 222 3344', 'green.grocers@email.com', 'Grocery Store', 'BUS-004'),
('Sunshine Bakery', '654 Baker Rd, Quezon City', '+63 914 555 6677', 'sunshine.bakery@email.com', 'Bakery', 'BUS-005');


-- ==================================================================
-- Inspection Scheduling Database Schema (lgu_inspection_scheduling)
-- ==================================================================
USE lgu_inspection_scheduling;

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


-- ==================================================================
-- Checklist Assessment Database Schema (lgu_checklist_assessment)
-- ==================================================================
USE lgu_checklist_assessment;

-- Predefined checklist templates
CREATE TABLE checklist_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    inspection_type_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    question TEXT NOT NULL,
    required BOOLEAN DEFAULT TRUE,
    input_type ENUM('checkbox', 'text', 'select', 'number') NOT NULL DEFAULT 'checkbox',
    options JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Note: Foreign keys removed for cross-DB decentralization
    INDEX idx_inspection_type (inspection_type_id),
    INDEX idx_category (category)
);

-- Inspection checklist responses
CREATE TABLE inspection_responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    inspection_id INT NOT NULL,
    checklist_template_id INT NOT NULL,
    response TEXT NULL,
    ai_analysis JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Note: Foreign keys removed for cross-DB decentralization
    UNIQUE KEY unique_inspection_checklist (inspection_id, checklist_template_id),
    INDEX idx_inspection (inspection_id)
);

-- Insert sample checklist templates for Health & Sanitation
INSERT INTO checklist_templates (inspection_type_id, category, question, required, input_type, options) VALUES
(1, 'General Cleanliness', 'Are the premises generally clean and well-maintained?', TRUE, 'checkbox', NULL),
(1, 'Waste Management', 'Describe the waste disposal system and its condition', TRUE, 'text', NULL),
(1, 'Water Supply', 'Rate the water supply quality', TRUE, 'select', '["Excellent", "Good", "Fair", "Poor", "Not Available"]'),
(1, 'Pest Control', 'Evidence of pest control measures?', TRUE, 'checkbox', NULL),
(1, 'Food Storage', 'Number of food storage violations observed', FALSE, 'number', NULL),
(1, 'Employee Hygiene', 'Describe employee hygiene practices and compliance', TRUE, 'text', NULL);

-- Insert sample checklist templates for Fire Safety
INSERT INTO checklist_templates (inspection_type_id, category, question, required, input_type, options) VALUES
(2, 'Fire Exits', 'Are all fire exits clearly marked and unobstructed?', TRUE, 'checkbox', NULL),
(2, 'Fire Extinguishers', 'Number of functional fire extinguishers present', TRUE, 'number', NULL),
(2, 'Smoke Detectors', 'Condition of smoke detection systems', TRUE, 'select', '["Fully Functional", "Partially Working", "Not Working", "Not Present"]'),
(2, 'Emergency Lighting', 'Are emergency lights operational?', TRUE, 'checkbox', NULL),
(2, 'Fire Safety Plan', 'Describe the fire safety plan and evacuation procedures', TRUE, 'text', NULL);

-- Insert sample responses (assuming inspection IDs from scheduling DB)
INSERT INTO inspection_responses (inspection_id, checklist_template_id, response, ai_analysis) VALUES
(1, 1, 'Yes', '{"confidence": 0.95}'),
(1, 2, 'Proper waste bins and regular collection', '{"sentiment": "positive"}'),
(2, 6, 'Good hygiene practices observed', '{"compliance": 0.88}'),
(2, 7, '5', NULL),
(3, 8, 'Yes', '{"confidence": 0.92}');


-- ==================================================================
-- Media Uploads Database Schema (lgu_media_uploads)
-- ==================================================================
USE lgu_media_uploads;

-- Media files for inspections
CREATE TABLE inspection_media (
    id INT PRIMARY KEY AUTO_INCREMENT,
    inspection_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('image', 'video') NOT NULL,
    file_size INT NOT NULL,
    ai_analysis JSON NULL,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Note: Foreign keys removed for cross-DB decentralization
    INDEX idx_inspection (inspection_id),
    INDEX idx_file_type (file_type)
);

-- Insert sample media files
INSERT INTO inspection_media (inspection_id, filename, file_path, file_type, file_size, ai_analysis, uploaded_by) VALUES
(1, 'restaurant_cleanliness.jpg', '/uploads/inspections/1/restaurant_cleanliness.jpg', 'image', 2048576, '{"objects": ["clean floor", "sanitized tables"]}', 2),
(2, 'fire_extinguisher_check.mp4', '/uploads/inspections/2/fire_extinguisher_check.mp4', 'video', 15728640, '{"duration": "00:02:15", "quality": "HD"}', 3),
(3, 'building_safety_photo.jpg', '/uploads/inspections/3/building_safety_photo.jpg', 'image', 1536000, '{"hazards": "none detected"}', 2),
(1, 'waste_management_area.jpg', '/uploads/inspections/1/waste_management_area.jpg', 'image', 1024000, '{"compliance": 0.95}', 2),
(2, 'emergency_exit_sign.jpg', '/uploads/inspections/2/emergency_exit_sign.jpg', 'image', 768000, '{"visibility": "clear"}', 3);


-- ==================================================================
-- Violations Ticketing Database Schema (lgu_violations_ticketing)
-- ==================================================================
USE lgu_violations_ticketing;

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


-- ==================================================================
-- Reports & Notifications Database Schema (lgu_reports_notifications)
-- ==================================================================
USE lgu_reports_notifications;

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'alert', 'success') NOT NULL DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    related_entity_type ENUM('inspection', 'violation', 'user') NULL,
    related_entity_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Note: Foreign keys removed for cross-DB decentralization
    INDEX idx_user (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- Insert sample notifications
INSERT INTO notifications (user_id, message, type, is_read, related_entity_type, related_entity_id) VALUES
(1, 'New inspection scheduled for ABC Restaurant', 'info', FALSE, 'inspection', 1),
(1, 'Violation reported at XYZ Mall - Fire Exit Blocked', 'warning', FALSE, 'violation', 1),
(1, 'Inspector certification expires in 30 days', 'alert', FALSE, 'user', 2),
(2, 'Inspection completed for Tech Hub Office', 'success', TRUE, 'inspection', 3),
(3, 'New violation assigned: Water supply quality issue', 'alert', FALSE, 'violation', 4),
(1, 'Monthly compliance report generated', 'info', FALSE, NULL, NULL);
