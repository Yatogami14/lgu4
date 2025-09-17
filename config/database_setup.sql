
-- --------------------------------------------------------
-- Step 2: Create a single user for the application
-- --------------------------------------------------------
CREATE USER 'lgu_user'@'localhost' IDENTIFIED BY 'Admin123';

-- --------------------------------------------------------
-- Step 3: Create all databases
-- --------------------------------------------------------
CREATE DATABASE `hsi_lgu_checklist_assessment` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE `hsi_lgu_core` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE `hsi_lgu_inspection_scheduling` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE `hsi_lgu_media_uploads` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE `hsi_lgu_reports_notifications` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE `hsi_lgu_violations_ticketing` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- --------------------------------------------------------
-- Step 4: Grant privileges to the single user
-- --------------------------------------------------------
GRANT ALL PRIVILEGES ON `hsi_lgu_checklist_assessment`.* TO 'lgu_user'@'localhost';
GRANT ALL PRIVILEGES ON `hsi_lgu_core`.* TO 'lgu_user'@'localhost';
GRANT ALL PRIVILEGES ON `hsi_lgu_inspection_scheduling`.* TO 'lgu_user'@'localhost';
GRANT ALL PRIVILEGES ON `hsi_lgu_media_uploads`.* TO 'lgu_user'@'localhost';
GRANT ALL PRIVILEGES ON `hsi_lgu_reports_notifications`.* TO 'lgu_user'@'localhost';
GRANT ALL PRIVILEGES ON `hsi_lgu_violations_ticketing`.* TO 'lgu_user'@'localhost';

-- Apply the new privileges
FLUSH PRIVILEGES;

-- --------------------------------------------------------
-- Step 5: Define schema and data for each database
-- --------------------------------------------------------

--
-- Database: `hsi_lgu_checklist_assessment`
--
USE `hsi_lgu_checklist_assessment`;

-- Table structure for table `checklist_templates`
CREATE TABLE `checklist_templates` (
  `id` int(11) NOT NULL,
  `inspection_type_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `question` text NOT NULL,
  `required` tinyint(1) DEFAULT 1,
  `input_type` enum('checkbox','text','select','number') NOT NULL DEFAULT 'checkbox',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `checklist_templates`
INSERT INTO `checklist_templates` (`id`, `inspection_type_id`, `category`, `question`, `required`, `input_type`, `options`, `created_at`) VALUES
(1, 1, 'General Cleanliness', 'Are the premises generally clean and well-maintained?', 1, 'checkbox', NULL, '2025-09-16 14:06:15'),
(2, 1, 'Waste Management', 'Describe the waste disposal system and its condition', 1, 'text', NULL, '2025-09-16 14:06:15'),
(3, 1, 'Water Supply', 'Rate the water supply quality', 1, 'select', '[\"Excellent\", \"Good\", \"Fair\", \"Poor\", \"Not Available\"]', '2025-09-16 14:06:15'),
(4, 1, 'Pest Control', 'Evidence of pest control measures?', 1, 'checkbox', NULL, '2025-09-16 14:06:15'),
(5, 1, 'Food Storage', 'Number of food storage violations observed', 0, 'number', NULL, '2025-09-16 14:06:15'),
(6, 1, 'Employee Hygiene', 'Describe employee hygiene practices and compliance', 1, 'text', NULL, '2025-09-16 14:06:15'),
(7, 2, 'Fire Exits', 'Are all fire exits clearly marked and unobstructed?', 1, 'checkbox', NULL, '2025-09-16 14:06:15'),
(8, 2, 'Fire Extinguishers', 'Number of functional fire extinguishers present', 1, 'number', NULL, '2025-09-16 14:06:15'),
(9, 2, 'Smoke Detectors', 'Condition of smoke detection systems', 1, 'select', '[\"Fully Functional\", \"Partially Working\", \"Not Working\", \"Not Present\"]', '2025-09-16 14:06:15'),
(10, 2, 'Emergency Lighting', 'Are emergency lights operational?', 1, 'checkbox', NULL, '2025-09-16 14:06:15'),
(11, 2, 'Fire Safety Plan', 'Describe the fire safety plan and evacuation procedures', 1, 'text', NULL, '2025-09-16 14:06:15');

-- Table structure for table `inspection_responses`
CREATE TABLE `inspection_responses` (
  `id` int(11) NOT NULL,
  `inspection_id` int(11) NOT NULL,
  `checklist_template_id` int(11) NOT NULL,
  `response` text DEFAULT NULL,
  `ai_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_analysis`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `inspection_responses`
INSERT INTO `inspection_responses` (`id`, `inspection_id`, `checklist_template_id`, `response`, `ai_analysis`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Yes', '{\"confidence\": 0.95}', '2025-09-16 14:06:15', '2025-09-16 14:06:15'),
(2, 1, 2, 'Proper waste bins and regular collection', '{\"sentiment\": \"positive\"}', '2025-09-16 14:06:15', '2025-09-16 14:06:15'),
(3, 2, 6, 'Good hygiene practices observed', '{\"compliance\": 0.88}', '2025-09-16 14:06:15', '2025-09-16 14:06:15'),
(4, 2, 7, '5', NULL, '2025-09-16 14:06:15', '2025-09-16 14:06:15'),
(5, 3, 8, 'Yes', '{\"confidence\": 0.92}', '2025-09-16 14:06:15', '2025-09-16 14:06:15');

-- Indexes for table `checklist_templates`
ALTER TABLE `checklist_templates` ADD PRIMARY KEY (`id`), ADD KEY `idx_inspection_type` (`inspection_type_id`), ADD KEY `idx_category` (`category`);
-- Indexes for table `inspection_responses`
ALTER TABLE `inspection_responses` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `unique_inspection_checklist` (`inspection_id`,`checklist_template_id`), ADD KEY `idx_inspection` (`inspection_id`);
-- AUTO_INCREMENT for table `checklist_templates`
ALTER TABLE `checklist_templates` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
-- AUTO_INCREMENT for table `inspection_responses`
ALTER TABLE `inspection_responses` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Database: `hsi_lgu_core`
--
USE `hsi_lgu_core`;

-- Table structure for table `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','inspector','business_owner','community_user') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `users` (example data, password is 'password')
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'Super Admin', 'superadmin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin'),
(2, 'John Inspector', 'inspector@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'inspector'),
(3, 'Jane Inspector', 'inspector2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'inspector'),
(4, 'Bob Owner', 'owner@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business_owner'),
(5, 'Alice Community', 'community@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'community_user'),
(6, 'Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(7, 'Test Owner', 'testowner@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'business_owner'),
(8, 'Main Inspector', 'maininspector@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'inspector'),
(10, 'Community Reporter', 'reporter@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'community_user');

-- Table structure for table `businesses`
CREATE TABLE `businesses` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `business_type` varchar(100) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `last_inspection_date` date DEFAULT NULL,
  `overall_compliance_score` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `businesses`
INSERT INTO `businesses` (`id`, `name`, `address`, `business_type`, `owner_id`) VALUES
(1, 'ABC Restaurant', '123 Main St, Quezon City', 'Restaurant', 4),
(2, 'XYZ Mall', '456 High St, Quezon City', 'Mall', 4),
(3, 'Tech Hub Office', '789 Tech Ave, Quezon City', 'Office', 7),
(4, 'Corner Cafe', '101 Corner Rd, Quezon City', 'Cafe', 7),
(5, 'The Grand Hotel', '202 Grand Blvd, Quezon City', 'Hotel', 4),
(6, 'Bilis Bakeshop', '303 Speedy St, Quezon City', 'Bakeshop', 7);

-- Table structure for table `inspection_types`
CREATE TABLE `inspection_types` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `inspection_types`
INSERT INTO `inspection_types` (`id`, `name`, `description`) VALUES
(1, 'Sanitary Inspection', 'General sanitary and health inspection for food establishments.'),
(2, 'Fire Safety Inspection', 'Inspection for fire safety compliance.'),
(3, 'Structural Integrity Inspection', 'Inspection of building structural safety.'),
(4, 'Electrical Safety Inspection', 'Inspection of electrical wiring and installations.'),
(5, 'Follow-up Inspection', 'A follow-up visit to check on previously reported violations.');

-- Table structure for table `sessions` (for database-backed sessions)
CREATE TABLE `sessions` (
  `session_id` varchar(128) NOT NULL,
  `session_data` blob NOT NULL,
  `session_last_access` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `remember_me_tokens`
CREATE TABLE `remember_me_tokens` (
  `id` int(11) NOT NULL,
  `selector` varchar(255) NOT NULL,
  `hashed_validator` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expires` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- Database: `hsi_lgu_inspection_scheduling`
--
USE `hsi_lgu_inspection_scheduling`;

-- Table structure for table `inspections`
CREATE TABLE `inspections` (
  `id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `inspector_id` int(11) DEFAULT NULL,
  `inspection_type_id` int(11) NOT NULL,
  `scheduled_date` datetime NOT NULL,
  `completed_date` datetime DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','overdue') NOT NULL DEFAULT 'scheduled',
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `compliance_score` int(11) DEFAULT NULL,
  `total_violations` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `notes_ai_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notes_ai_analysis`)),
  `draft_data` text DEFAULT NULL,
  `hash` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `inspections`
INSERT INTO `inspections` (`id`, `business_id`, `inspector_id`, `inspection_type_id`, `scheduled_date`, `completed_date`, `status`, `priority`, `compliance_score`, `total_violations`, `notes`, `notes_ai_analysis`, `draft_data`, `hash`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, '2024-01-15 09:00:00', NULL, 'scheduled', 'high', NULL, 0, NULL, NULL, NULL, NULL, '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(2, 2, 3, 2, '2024-01-16 10:00:00', NULL, 'in_progress', 'medium', 85, 2, NULL, NULL, NULL, NULL, '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(3, 3, 2, 3, '2024-01-14 14:00:00', NULL, 'completed', 'low', 92, 1, NULL, NULL, NULL, NULL, '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(4, 4, NULL, 1, '2024-01-20 09:00:00', NULL, 'scheduled', 'medium', NULL, 0, NULL, NULL, NULL, NULL, '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(5, 5, 8, 2, '2024-01-21 10:00:00', '2025-09-16 16:32:10', 'completed', 'high', 100, 0, '', NULL, NULL, NULL, '2025-09-16 14:06:16', '2025-09-16 14:32:10'),
(6, 6, 8, 4, '2025-09-16 00:00:00', NULL, 'scheduled', 'low', NULL, 0, 'Bilis ya', NULL, NULL, NULL, '2025-09-16 14:35:43', '2025-09-16 14:36:39'),
(7, 1, 8, 5, '2025-09-17 01:00:00', NULL, 'scheduled', 'low', NULL, 0, 'Follow-up on violation (ID: 7): &quot;may ipis yung lugaw&quot;', NULL, NULL, NULL, '2025-09-16 14:40:20', '2025-09-17 13:31:22');

-- Table structure for table `inspector_specializations`
CREATE TABLE `inspector_specializations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `inspection_type_id` int(11) NOT NULL,
  `proficiency_level` enum('beginner','intermediate','expert') DEFAULT 'intermediate',
  `certification_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `inspector_specializations`
INSERT INTO `inspector_specializations` (`id`, `user_id`, `inspection_type_id`, `proficiency_level`, `certification_date`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'expert', '2023-06-15', '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(2, 2, 3, 'intermediate', '2023-08-20', '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(3, 3, 2, 'expert', '2023-05-10', '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(4, 3, 4, 'intermediate', '2023-09-05', '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(5, 1, 1, 'expert', '2022-12-01', '2025-09-16 14:06:16', '2025-09-16 14:06:16');

-- Indexes for table `inspections`
ALTER TABLE `inspections` ADD PRIMARY KEY (`id`), ADD KEY `idx_business` (`business_id`), ADD KEY `idx_inspector` (`inspector_id`), ADD KEY `idx_status` (`status`), ADD KEY `idx_scheduled_date` (`scheduled_date`), ADD KEY `idx_hash` (`hash`);
-- Indexes for table `inspector_specializations`
ALTER TABLE `inspector_specializations` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `unique_user_specialization` (`user_id`,`inspection_type_id`), ADD KEY `idx_user` (`user_id`), ADD KEY `idx_inspection_type` (`inspection_type_id`);
-- AUTO_INCREMENT for table `inspections`
ALTER TABLE `inspections` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
-- AUTO_INCREMENT for table `inspector_specializations`
ALTER TABLE `inspector_specializations` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Database: `hsi_lgu_media_uploads`
--
USE `hsi_lgu_media_uploads`;

-- Table structure for table `media`
CREATE TABLE `media` (
  `id` int(11) NOT NULL,
  `inspection_id` int(11) DEFAULT NULL,
  `violation_id` int(11) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `media` (example data)
INSERT INTO `media` (`id`, `inspection_id`, `violation_id`, `file_path`, `file_name`, `file_type`, `file_size`, `uploaded_by`) VALUES
(1, 1, 4, 'uploads/2025/09/sample_water.jpg', 'sample_water.jpg', 'image/jpeg', 102400, 2),
(2, 2, 1, 'uploads/2025/09/extinguisher.jpg', 'extinguisher.jpg', 'image/jpeg', 123456, 3),
(5, 2, 2, 'uploads/2025/09/blocked_exit.jpg', 'blocked_exit.jpg', 'image/jpeg', 98765, 3);

-- Indexes and AUTO_INCREMENT for table `media`
ALTER TABLE `media` ADD PRIMARY KEY (`id`), ADD KEY `inspection_id` (`inspection_id`), ADD KEY `violation_id` (`violation_id`);
ALTER TABLE `media` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- Database: `hsi_lgu_reports_notifications`
--
USE `hsi_lgu_reports_notifications`;

-- Table structure for table `notifications`
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','alert','success') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `related_entity_type` enum('inspection','violation','user') DEFAULT NULL,
  `related_entity_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `notifications`
INSERT INTO `notifications` (`id`, `user_id`, `message`, `type`, `is_read`, `related_entity_type`, `related_entity_id`, `created_at`) VALUES
(1, 1, 'New inspection scheduled for ABC Restaurant', 'info', 0, 'inspection', 1, '2025-09-16 14:06:16'),
(2, 1, 'Violation reported at XYZ Mall - Fire Exit Blocked', 'warning', 0, 'violation', 1, '2025-09-16 14:06:16'),
(3, 1, 'Inspector certification expires in 30 days', 'alert', 0, 'user', 2, '2025-09-16 14:06:16'),
(4, 2, 'Inspection completed for Tech Hub Office', 'success', 1, 'inspection', 3, '2025-09-16 14:06:16'),
(5, 3, 'New violation assigned: Water supply quality issue', 'alert', 0, 'violation', 4, '2025-09-16 14:06:16'),
(6, 1, 'Monthly compliance report generated', 'info', 0, NULL, NULL, '2025-09-16 14:06:16');

-- Indexes for table `notifications`
ALTER TABLE `notifications` ADD PRIMARY KEY (`id`), ADD KEY `idx_user` (`user_id`), ADD KEY `idx_is_read` (`is_read`), ADD KEY `idx_created_at` (`created_at`);
-- AUTO_INCREMENT for table `notifications`
ALTER TABLE `notifications` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Database: `hsi_lgu_violations_ticketing`
--
USE `hsi_lgu_violations_ticketing`;

-- Table structure for table `violations`
CREATE TABLE `violations` (
  `id` int(11) NOT NULL,
  `inspection_id` int(11) NOT NULL,
  `business_id` int(11) NOT NULL,
  `checklist_response_id` int(11) DEFAULT NULL,
  `media_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `due_date` date DEFAULT NULL,
  `resolved_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `hash` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `violations`
INSERT INTO `violations` (`id`, `inspection_id`, `business_id`, `checklist_response_id`, `media_id`, `description`, `severity`, `status`, `due_date`, `resolved_date`, `created_by`, `hash`, `created_at`, `updated_at`) VALUES
(1, 2, 2, 4, 2, 'Fire extinguisher not properly maintained', 'high', 'open', '2024-02-01', NULL, 3, NULL, '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(2, 2, 2, NULL, 5, 'Emergency exit sign partially obscured', 'medium', 'in_progress', '2024-01-25', NULL, 3, NULL, '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(3, 3, 3, 1, NULL, 'Minor cleanliness issue in storage area', 'low', 'resolved', '2024-01-20', NULL, 2, NULL, '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(4, 1, 1, 3, 1, 'Water supply quality below standard', 'medium', 'open', '2024-01-30', NULL, 2, NULL, '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(5, 2, 2, 7, NULL, 'Insufficient number of fire extinguishers', 'high', 'open', '2024-02-05', NULL, 3, NULL, '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(6, 5, 5, NULL, NULL, 'no fire extinguiser', 'medium', 'open', '2025-09-16', NULL, 7, NULL, '2025-09-16 14:24:03', '2025-09-16 14:24:03'),
(7, 7, 1, NULL, NULL, 'may ipis yung lugaw', 'low', 'in_progress', NULL, NULL, 10, NULL, '2025-09-16 14:38:35', '2025-09-16 14:40:20'),
(8, 0, 1, NULL, NULL, 'expired', 'medium', 'open', '2025-09-18', NULL, 8, NULL, '2025-09-17 13:43:41', '2025-09-17 13:43:41'),
(9, 0, 1, NULL, NULL, 'madumi yung kapaligiran', 'low', 'open', NULL, NULL, 10, NULL, '2025-09-17 13:46:44', '2025-09-17 13:46:44'),
(10, 0, 1, NULL, NULL, 'madumi yung kapaligiran', 'low', 'open', NULL, NULL, 10, NULL, '2025-09-17 13:46:45', '2025-09-17 13:46:45');

-- Indexes for table `violations`
ALTER TABLE `violations` ADD PRIMARY KEY (`id`), ADD KEY `idx_inspection` (`inspection_id`), ADD KEY `idx_business` (`business_id`), ADD KEY `idx_status` (`status`), ADD KEY `idx_severity` (`severity`), ADD KEY `idx_hash` (`hash`);
-- AUTO_INCREMENT for table `violations`
ALTER TABLE `violations` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
