-- Checklist-Based Field Assessment Forms Database Schema
-- Decentralized submodule for checklist templates and responses

-- Drop and recreate the database to ensure a clean state and avoid tablespace errors.
DROP DATABASE IF EXISTS lgu_checklist_assessment;
CREATE DATABASE lgu_checklist_assessment;
USE lgu_checklist_assessment;

-- To make the script re-runnable, drop tables if they exist.
-- Drop in reverse order of creation to avoid foreign key issues.
DROP TABLE IF EXISTS inspection_responses;
DROP TABLE IF EXISTS checklist_templates;

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
