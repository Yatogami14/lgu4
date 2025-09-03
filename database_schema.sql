-- Digital Health & Safety Inspection Platform Database Schema
-- MySQL Database

CREATE DATABASE IF NOT EXISTS lgu_inspection_platform;
USE lgu_inspection_platform;

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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_name (name),
    INDEX idx_business_type (business_type),
    INDEX idx_compliance (is_compliant),
    INDEX idx_next_inspection (next_inspection_date)
);

-- Inspection types
CREATE TABLE inspection_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_name (name)
);

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
    FOREIGN KEY (inspection_type_id) REFERENCES inspection_types(id) ON DELETE CASCADE,
    INDEX idx_inspection_type (inspection_type_id),
    INDEX idx_category (category)
);

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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (inspector_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (inspection_type_id) REFERENCES inspection_types(id) ON DELETE CASCADE,
    INDEX idx_business (business_id),
    INDEX idx_inspector (inspector_id),
    INDEX idx_status (status),
    INDEX idx_scheduled_date (scheduled_date)
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
    FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE,
    FOREIGN KEY (checklist_template_id) REFERENCES checklist_templates(id) ON DELETE CASCADE,
    UNIQUE KEY unique_inspection_checklist (inspection_id, checklist_template_id),
    INDEX idx_inspection (inspection_id)
);

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
    FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_inspection (inspection_id),
    INDEX idx_file_type (file_type)
);

-- Violations table
CREATE TABLE violations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    inspection_id INT NOT NULL,
    checklist_response_id INT NULL,
    media_id INT NULL,
    description TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    due_date DATE NULL,
    resolved_date DATE NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (inspection_id) REFERENCES inspections(id) ON DELETE CASCADE,
    FOREIGN KEY (checklist_response_id) REFERENCES inspection_responses(id) ON DELETE SET NULL,
    FOREIGN KEY (media_id) REFERENCES inspection_media(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_inspection (inspection_id),
    INDEX idx_status (status),
    INDEX idx_severity (severity)
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (inspection_type_id) REFERENCES inspection_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_specialization (user_id, inspection_type_id),
    INDEX idx_user (user_id),
    INDEX idx_inspection_type (inspection_type_id)
);

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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- Insert default inspection types
INSERT INTO inspection_types (name, description) VALUES
('Health & Sanitation', 'Health and sanitation compliance inspections'),
('Fire Safety', 'Fire safety and prevention inspections'),
('Building Safety', 'Building structure and safety inspections'),
('Environmental', 'Environmental compliance inspections'),
('Food Safety', 'Food safety and hygiene inspections');

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

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role, department, certification) VALUES
('Maria Santos', 'maria.santos@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Health and Safety Division', 'Certified Safety Inspector'),
('Juan Dela Cruz', 'juan.delacruz@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'inspector', 'Health and Safety Division', 'Certified Inspector'),
('Anna Reyes', 'anna.reyes@lgu.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'inspector', 'Fire Safety Division', 'Fire Safety Officer');

-- Insert sample businesses
INSERT INTO businesses (name, address, contact_number, email, business_type, registration_number) VALUES
('ABC Restaurant', '123 Main St, Makati City', '+63 912 345 6789', 'abc.restaurant@email.com', 'Restaurant', 'BUS-001'),
('XYZ Mall', '456 Commerce Ave, BGC', '+63 917 654 3210', 'xyz.mall@email.com', 'Shopping Mall', 'BUS-002'),
('Tech Hub Office', '789 IT Park, Cebu', '+63 918 765 4321', 'tech.hub@email.com', 'Office Building', 'BUS-003');

-- Insert sample inspections
INSERT INTO inspections (business_id, inspector_id, inspection_type_id, scheduled_date, status, priority, compliance_score, total_violations) VALUES
(1, 2, 1, '2024-01-15 09:00:00', 'scheduled', 'high', NULL, 0),
(2, 3, 2, '2024-01-16 10:00:00', 'in_progress', 'medium', 85, 2),
(3, 2, 3, '2024-01-14 14:00:00', 'completed', 'low', 92, 1);

-- Insert sample unassigned inspections for testing assignment feature
INSERT INTO inspections (business_id, inspector_id, inspection_type_id, scheduled_date, status, priority) VALUES
(1, NULL, 1, '2024-01-20 09:00:00', 'scheduled', 'medium'),
(2, NULL, 2, '2024-01-21 10:00:00', 'scheduled', 'high'),
(3, NULL, 3, '2024-01-22 14:00:00', 'scheduled', 'low');

-- Insert sample notifications
INSERT INTO notifications (user_id, message, type, is_read) VALUES
(1, 'New inspection scheduled for ABC Restaurant', 'info', FALSE),
(1, 'Violation reported at XYZ Mall - Fire Exit Blocked', 'warning', FALSE),
(1, 'Inspector certification expires in 30 days', 'alert', FALSE);
