-- Add missing department column to users table in hsi_lgu_unified database
USE `hsi_lgu_unified`;

ALTER TABLE `users` ADD COLUMN `department` varchar(255) DEFAULT NULL AFTER `role`;
