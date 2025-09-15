-- Core Database Schema for LGU Inspection Platform
-- Contains shared entities: users, businesses, inspection_types, blockchain

-- Drop and recreate the database to ensure a clean state and avoid tablespace errors.
DROP DATABASE IF EXISTS lgu_core;
CREATE DATABASE lgu_core;
USE lgu_core;

-- To make the script re-runnable, drop tables if they exist.
-- Drop in reverse order of creation to avoid foreign key issues.
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS blockchain;
DROP TABLE IF EXISTS inspection_types;
DROP TABLE IF EXISTS businesses;
DROP TABLE IF EXISTS users;

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
