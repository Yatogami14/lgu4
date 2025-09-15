-- Inspector Profile and Certification Tracking Alter Script
-- Adds or modifies profile-related fields in core and scheduling databases

-- For Core Database (users table already has profile fields)
USE lgu_core;
-- Profile fields already present in users table:
-- department, certification, avatar
-- No additional alters needed

-- For Inspection Scheduling Database (inspector_specializations table already has profile fields)
USE lgu_inspection_scheduling;
-- Profile fields already present in inspector_specializations table:
-- proficiency_level, certification_date
-- No additional alters needed

-- If additional profile tracking fields are needed, add ALTER TABLE statements here
-- Example:
-- ALTER TABLE users ADD COLUMN experience_years INT NULL;
-- ALTER TABLE inspector_specializations ADD COLUMN training_completed JSON NULL;
