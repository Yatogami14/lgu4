-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'business_owner', 'inspector') DEFAULT 'business_owner',
    status ENUM('active', 'pending_approval', 'deactivated') DEFAULT 'pending_approval',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Businesses Table
CREATE TABLE IF NOT EXISTS businesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    email VARCHAR(255),
    contact_number VARCHAR(50),
    business_type VARCHAR(100),
    registration_number VARCHAR(100),
    establishment_date DATE,
    representative_name VARCHAR(255),
    representative_position VARCHAR(100),
    representative_contact VARCHAR(50),
    status ENUM('pending', 'verified', 'rejected', 'needs_revision', 'expired') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Business Documents Table
CREATE TABLE IF NOT EXISTS business_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    document_type ENUM('building_permit','business_permit','waste_disposal_certificate','owner_id','tax_registration','mayors_permit') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100),
    file_size INT,
    status ENUM('pending','verified','rejected') DEFAULT 'pending',
    feedback TEXT DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    related_type VARCHAR(50),
    related_id INT,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Inspection Types Table
CREATE TABLE IF NOT EXISTS inspection_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inspections Table
CREATE TABLE IF NOT EXISTS inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    inspection_type_id INT NOT NULL,
    inspector_id INT,
    scheduled_date DATETIME,
    inspection_date DATETIME,
    status ENUM('pending', 'scheduled', 'passed', 'failed', 'reinspection_needed') DEFAULT 'pending',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    FOREIGN KEY (inspection_type_id) REFERENCES inspection_types(id),
    FOREIGN KEY (inspector_id) REFERENCES users(id)
);

-- Insert Data into Inspection Types
INSERT INTO inspection_types (name, description, department) VALUES 
('Sanitary Inspection', 'Verifies compliance with sanitation and health codes.', 'City Health Office'),
('Fire Safety Inspection', 'Ensures compliance with the Fire Code of the Philippines.', 'Bureau of Fire Protection (BFP)'),
('Building Inspection', 'Checks structural integrity and adherence to the National Building Code.', 'Office of the Building Official (OBO)'),
('Electrical Inspection', 'Verifies safety of electrical wirings and installations.', 'Office of the Building Official (OBO)'),
('Zoning Clearance', 'Ensures the business location conforms to the comprehensive land use plan.', 'City Planning and Development Office'),
('Environmental Compliance', 'Checks adherence to environmental laws and waste management.', 'City Environment and Natural Resources Office (CENRO)');