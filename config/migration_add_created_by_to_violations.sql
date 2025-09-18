-- Migration to add created_by column to violations table
-- This fixes the issue where the code expects created_by but the database has user_id

USE `hsi_lgu_unified`;

-- Add the created_by column to violations table
ALTER TABLE `violations`
ADD COLUMN `created_by` int(11) NOT NULL AFTER `severity`,
ADD COLUMN `due_date` date DEFAULT NULL AFTER `status`,
ADD COLUMN `resolved_date` date DEFAULT NULL AFTER `due_date`,
ADD COLUMN `checklist_response_id` int(11) DEFAULT NULL AFTER `business_id`,
ADD COLUMN `media_id` int(11) DEFAULT NULL AFTER `checklist_response_id`,
ADD COLUMN `hash` varchar(64) DEFAULT NULL AFTER `created_by`;

-- Add indexes for the new columns
ALTER TABLE `violations`
ADD KEY `idx_created_by` (`created_by`),
ADD KEY `idx_due_date` (`due_date`),
ADD KEY `idx_resolved_date` (`resolved_date`);

-- Optional: If you want to migrate existing data from user_id to created_by
-- (assuming there's a user_id column that needs to be migrated)
-- UPDATE `violations` SET `created_by` = `user_id` WHERE `created_by` IS NULL;
-- ALTER TABLE `violations` DROP COLUMN `user_id`;
