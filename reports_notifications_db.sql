-- Inspection Result Reports and Notifications Database Schema
-- Decentralized submodule for notifications and reports

-- Drop and recreate the database to ensure a clean state and avoid tablespace errors.
DROP DATABASE IF EXISTS lgu_reports_notifications;
CREATE DATABASE lgu_reports_notifications;
USE lgu_reports_notifications;

-- To make the script re-runnable, drop tables if they exist.
DROP TABLE IF EXISTS notifications;

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
