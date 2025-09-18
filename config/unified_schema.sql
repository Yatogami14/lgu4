--
-- Database: `hsi_lgu_unified`
--
USE `hsi_lgu_unified`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
-- (Inferred from Business.php, Inspection.php, navigation.php)
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','inspector','business_owner','community_user') NOT NULL,
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

CREATE TABLE `inspection_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `notes_ai_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notes_ai_analysis`)),
  `draft_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`draft_data`)),
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

CREATE TABLE `checklist_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inspection_type_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `question` text NOT NULL,
  `required` tinyint(1) DEFAULT 1,
  `input_type` enum('checkbox','text','select','number') NOT NULL DEFAULT 'checkbox',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_inspection_type` (`inspection_type_id`),
  KEY `idx_category` (`category`),
  CONSTRAINT `fk_checklist_inspection_type` FOREIGN KEY (`inspection_type_id`) REFERENCES `inspection_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inspection_responses`
-- (From lgu_checklist_assessment.sql)
--

CREATE TABLE `inspection_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inspection_id` int(11) NOT NULL,
  `checklist_template_id` int(11) NOT NULL,
  `response` text DEFAULT NULL,
  `ai_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_analysis`)),
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
  `inspection_id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `status` enum('open','in_progress','closed','resolved') NOT NULL DEFAULT 'open',
  `severity` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inspection_id` (`inspection_id`),
  KEY `business_id` (`business_id`),
  CONSTRAINT `fk_violation_inspection` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_violation_business` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
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
  `ai_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_analysis`)),
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