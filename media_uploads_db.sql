-- Photo and Video Uploads Database Schema
-- Decentralized submodule for media files associated with inspections

-- Drop and recreate the database to ensure a clean state and avoid tablespace errors.
DROP DATABASE IF EXISTS lgu_media_uploads;
CREATE DATABASE lgu_media_uploads;
USE lgu_media_uploads;

-- To make the script re-runnable, drop tables if they exist.
DROP TABLE IF EXISTS inspection_media;

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
