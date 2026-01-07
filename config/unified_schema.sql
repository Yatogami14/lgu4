--
-- Database: `hsi_lgu_unified`
--
USE `hsi_lgu_unified`;

SET FOREIGN_KEY_CHECKS=0;

-- --------------------------------------------------------

--
-- Table structure for table `users`
-- (Inferred from Business.php, Inspection.php, navigation.php)
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','inspector','business_owner','community_user') NOT NULL,
  `status` enum('active','pending','deactivated') DEFAULT 'pending',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_code` varchar(255) DEFAULT NULL,
  `code_expiry` datetime DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `certification` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `remember_token_selector` varchar(32) DEFAULT NULL,
  `remember_token_validator_hash` varchar(64) DEFAULT NULL,
  `remember_token_expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inspection_types`
-- (Inferred from Inspection.php, ChecklistTemplate.php)
--

DROP TABLE IF EXISTS `inspection_types`;
CREATE TABLE `inspection_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Dumping data for table `inspection_types`
--

INSERT INTO `inspection_types` (`id`, `name`, `description`, `department`) VALUES
(1, 'Sanitary Inspection', 'Verifies compliance with sanitation and health codes.', 'City Health Office'),
(2, 'Fire Safety Inspection', 'Ensures compliance with the Fire Code of the Philippines.', 'Bureau of Fire Protection (BFP)'),
(3, 'Building Inspection', 'Checks structural integrity and adherence to the National Building Code.', 'Office of the Building Official (OBO)'),
(4, 'Electrical Inspection', 'Verifies safety of electrical wirings and installations.', 'Office of the Building Official (OBO)'),
(5, 'Zoning Clearance', 'Ensures the business location conforms to the comprehensive land use plan.', 'City Planning and Development Office'),
(6, 'Environmental Compliance', 'Checks adherence to environmental laws and waste management.', 'City Environment and Natural Resources Office (CENRO)');

-- --------------------------------------------------------

--
-- Table structure for table `businesses`
-- (From Business.php)
--

CREATE TABLE `businesses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `inspector_id` int(11) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `business_type` varchar(100) DEFAULT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `establishment_date` date DEFAULT NULL,
  `inspection_frequency` varchar(50) DEFAULT NULL,
  `last_inspection_date` date DEFAULT NULL,
  `next_inspection_date` date DEFAULT NULL,
  `is_compliant` tinyint(1) DEFAULT 0,
  `compliance_score` int(3) DEFAULT NULL,
  `representative_name` varchar(255) DEFAULT NULL,
  `representative_position` varchar(255) DEFAULT NULL,
  `representative_contact` varchar(50) DEFAULT NULL,
  `status` enum('pending','verified','rejected','needs_revision') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  KEY `inspector_id` (`inspector_id`),
  CONSTRAINT `fk_business_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_business_inspector` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_documents`
-- (For business registration compliance documents)
--

CREATE TABLE `business_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL,
  `document_type` enum('building_permit','business_permit','waste_disposal_certificate','owner_id','tax_registration','mayors_permit') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `feedback` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`),
  CONSTRAINT `fk_business_document` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inspections`
-- (From Inspection.php)
--

CREATE TABLE `inspections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL,
  `inspector_id` int(11) DEFAULT NULL,
  `inspection_type_id` int(11) NOT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `completed_date` datetime DEFAULT NULL,
  `status` enum('requested','scheduled','in_progress','completed','overdue','cancelled') NOT NULL DEFAULT 'scheduled',
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `compliance_score` int(3) DEFAULT NULL,
  `total_violations` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `notes_ai_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `draft_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`),
  KEY `inspector_id` (`inspector_id`),
  KEY `inspection_type_id` (`inspection_type_id`),
  CONSTRAINT `fk_inspection_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_inspection_inspector` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inspection_type` FOREIGN KEY (`inspection_type_id`) REFERENCES `inspection_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `checklist_templates`
-- (From lgu_checklist_assessment.sql)
--

DROP TABLE IF EXISTS `checklist_templates`;
CREATE TABLE `checklist_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inspection_type_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `question` text NOT NULL,
  `required` tinyint(1) DEFAULT 1,
  `input_type` enum('checkbox','text','select','number') NOT NULL DEFAULT 'checkbox',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_inspection_type` (`inspection_type_id`),
  KEY `idx_category` (`category`),
  CONSTRAINT `fk_checklist_inspection_type` FOREIGN KEY (`inspection_type_id`) REFERENCES `inspection_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `checklist_templates`
--

INSERT INTO `checklist_templates` (`inspection_type_id`, `category`, `question`, `required`, `input_type`, `options`) VALUES
-- Sanitary Inspection (ID 1)
(1, 'General Sanitation', 'Are the premises clean and free of pests (rodents, insects)?', 1, 'checkbox', NULL),
(1, 'General Sanitation', 'Is there a proper and covered waste disposal system?', 1, 'checkbox', NULL),
(1, 'Water Supply', 'Is the source of water supply potable and safe?', 1, 'select', '["Local Water District", "Private Deep Well", "Commercial Source", "Other"]'),
(1, 'Water Supply', 'Are water storage containers clean and covered?', 1, 'checkbox', NULL),
(1, 'Personnel', 'Do all food handlers have updated health certificates?', 1, 'checkbox', NULL),
(1, 'Personnel', 'Are employees observing proper hygiene (clean uniform, hairnet)?', 1, 'checkbox', NULL),
(1, 'Toilet Facilities', 'Are toilet facilities clean, functional, and with adequate supplies (soap, water, tissue)?', 1, 'checkbox', NULL),
(1, 'Food Handling', 'Is there proper segregation of raw and cooked food?', 1, 'checkbox', NULL),
(1, 'Food Storage', 'Are dry goods stored at least 15cm off the floor?', 1, 'checkbox', NULL),
(1, 'Food Storage', 'Are refrigerators and freezers maintaining correct temperatures?', 1, 'checkbox', NULL),

-- Fire Safety Inspection (ID 2)
(2, 'Fire Extinguishers', 'Are fire extinguishers visible, accessible, and properly mounted?', 1, 'checkbox', NULL),
(2, 'Fire Extinguishers', 'Is the pressure gauge in the operational range (green area)?', 1, 'checkbox', NULL),
(2, 'Fire Extinguishers', 'Date of last inspection/recharge tag?', 1, 'text', NULL),
(2, 'Exits', 'Are emergency exits clearly marked and unobstructed?', 1, 'checkbox', NULL),
(2, 'Exits', 'Are exit doors unlocked and easy to open from the inside?', 1, 'checkbox', NULL),
(2, 'Alarm & Detection', 'Are smoke detectors and fire alarms installed and functional?', 1, 'checkbox', NULL),
(2, 'Evacuation Plan', 'Is an evacuation plan posted in conspicuous areas?', 1, 'checkbox', NULL),
(2, 'Electrical', 'Are electrical outlets and panels free from octopus connections?', 1, 'checkbox', NULL),
(2, 'Kitchen Safety', 'Is the kitchen hood suppression system functional?', 0, 'checkbox', NULL),
(2, 'Electrical', 'Are emergency lights functional in case of power failure?', 1, 'checkbox', NULL),

-- Building Inspection (ID 3)
(3, 'Structural Integrity', 'Are there visible cracks on load-bearing walls, columns, or beams?', 1, 'checkbox', NULL),
(3, 'Structural Integrity', 'Is the building occupancy permit prominently displayed?', 1, 'checkbox', NULL),
(3, 'Accessibility', 'Is there a functional ramp for Persons with Disabilities (PWDs) with proper handrails?', 0, 'checkbox', NULL),
(3, 'Safety Features', 'Are stairways and hallways free from obstruction?', 1, 'checkbox', NULL),
(3, 'Safety Features', 'Are handrails and guardrails installed and in good condition?', 1, 'checkbox', NULL),
(3, 'Sanitation', 'Are plumbing fixtures in good working condition?', 1, 'checkbox', NULL),
(3, 'Ventilation', 'Is the ventilation system adequate for the occupancy load?', 1, 'checkbox', NULL),

-- Electrical Inspection (ID 4)
(4, 'Wiring & Panels', 'Are there any exposed, frayed, or damaged electrical wires?', 1, 'checkbox', NULL),
(4, 'Wiring & Panels', 'Is the main electrical panel easily accessible and properly labeled?', 1, 'checkbox', NULL),
(4, 'Grounding', 'Is the electrical system properly grounded?', 1, 'checkbox', NULL),
(4, 'Circuit Breakers', 'Are circuit breakers properly rated for their respective loads?', 1, 'checkbox', NULL),
(4, 'Load Assessment', 'Total connected load (in kVA)', 1, 'number', NULL),
(4, 'Outlets & Switches', 'Are all outlets and switches properly covered?', 1, 'checkbox', NULL),
(4, 'Signage', 'Are high voltage areas properly marked?', 1, 'checkbox', NULL),

-- Zoning Clearance (ID 5)
(5, 'Land Use', 'Is the business activity compliant with the designated zone classification?', 1, 'checkbox', NULL),
(5, 'Parking', 'Number of available parking slots provided as per ordinance?', 1, 'number', NULL),
(5, 'Setbacks', 'Does the building comply with the required front, side, and rear setbacks?', 1, 'checkbox', NULL),
(5, 'Signage', 'Is the business signage compliant with local regulations (size, placement)?', 1, 'checkbox', NULL),
(5, 'Easements', 'Are public easements free from encroachment?', 1, 'checkbox', NULL),

-- Environmental Compliance (ID 6)
(6, 'Waste Management', 'Is there a proper system for waste segregation (biodegradable, non-bio, recyclable)?', 1, 'checkbox', NULL),
(6, 'Pollution Control', 'Is a functional grease trap installed? (for food establishments)', 0, 'checkbox', NULL),
(6, 'Air Pollution', 'Are there measures to control smoke or fume emissions?', 0, 'checkbox', NULL),
(6, 'Water Discharge', 'Is wastewater being discharged into the proper drainage system?', 1, 'checkbox', NULL),
(6, 'Permits', 'Do you have a valid Discharge Permit?', 1, 'checkbox', NULL),
(6, 'Permits', 'Do you have a Permit to Operate (for gensets/boilers)?', 0, 'checkbox', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inspection_responses`
-- (From lgu_checklist_assessment.sql)
--

DROP TABLE IF EXISTS `inspection_responses`;
CREATE TABLE `inspection_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inspection_id` int(11) NOT NULL,
  `checklist_template_id` int(11) NOT NULL,
  `response` text DEFAULT NULL,
  `ai_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_inspection_checklist` (`inspection_id`,`checklist_template_id`),
  KEY `idx_inspection` (`inspection_id`),
  KEY `checklist_template_id` (`checklist_template_id`),
  CONSTRAINT `fk_response_inspection` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_response_checklist_template` FOREIGN KEY (`checklist_template_id`) REFERENCES `checklist_templates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `violations`
-- (Inferred from Inspection.php, Business.php)
--

CREATE TABLE `violations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inspection_id` int(11) DEFAULT NULL,
  `business_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `status` enum('open','in_progress','closed','resolved') NOT NULL DEFAULT 'open',
  `severity` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `due_date` date DEFAULT NULL,
  `resolved_date` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inspection_id` (`inspection_id`),
  KEY `business_id` (`business_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_violation_inspection` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_violation_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_violation_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media`
-- (Inferred from database config)
--

CREATE TABLE `media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `related_entity_type` varchar(50) NOT NULL COMMENT 'e.g., inspection, violation',
  `related_entity_id` int(11) NOT NULL,
  `uploader_id` int(11) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `ai_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `related_entity` (`related_entity_type`,`related_entity_id`),
  KEY `uploader_id` (`uploader_id`),
  CONSTRAINT `fk_media_uploader` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
-- (Inferred from Business.php)
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'info' COMMENT 'e.g., info, alert, success, warning',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `related_entity_type` varchar(50) DEFAULT NULL,
  `related_entity_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inspector_specializations`
-- (From Specialization.php)
--

CREATE TABLE `inspector_specializations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `inspection_type_id` int(11) NOT NULL,
  `proficiency_level` enum('beginner','intermediate','expert') NOT NULL DEFAULT 'beginner',
  `certification_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_inspection_type` (`user_id`,`inspection_type_id`),
  KEY `user_id` (`user_id`),
  KEY `inspection_type_id` (`inspection_type_id`),
  CONSTRAINT `fk_spec_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_spec_inspection_type` FOREIGN KEY (`inspection_type_id`) REFERENCES `inspection_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for table `action_attempts`
-- This table is used by RateLimiter.php to prevent brute-force attacks.
--

CREATE TABLE `action_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action_name` varchar(255) NOT NULL,
  `scope` varchar(255) NOT NULL COMMENT 'The identifier being limited (e.g., IP address, email)',
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `action_scope_time_idx` (`action_name`,`scope`,`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `verification_codes`
-- (Used by register.php and includes/functions.php)
--

CREATE TABLE `verification_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_verification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
-- (Referenced in models/User.php for active inspectors)
--

CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `last_activity` int(11) NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS=1;
