-- Business Compliance Tracking Alter Script
-- Adds or modifies compliance-related fields in core and scheduling databases

-- For Core Database (businesses table already has compliance fields)
USE lgu_core;
-- Compliance fields already present in businesses table:
-- inspection_frequency, last_inspection_date, next_inspection_date, is_compliant, compliance_score
-- No additional alters needed

-- For Inspection Scheduling Database (inspections table already has compliance fields)
USE lgu_inspection_scheduling;
-- Compliance fields already present in inspections table:
-- compliance_score, total_violations
-- No additional alters needed

-- If additional compliance tracking fields are needed, add ALTER TABLE statements here
-- Example:
-- ALTER TABLE businesses ADD COLUMN compliance_history JSON NULL;
-- ALTER TABLE inspections ADD COLUMN compliance_trend VARCHAR(50) NULL;
