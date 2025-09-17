-- =================================================================
-- HSI-QC Protektado - Full Database Setup Script
-- =================================================================
-- This script will drop and recreate all necessary databases.
-- It is intended to be run from a user with sufficient privileges
-- (e.g., root) to create and drop databases.
--
-- To execute this file, you can use the mysql client:
-- mysql -u root -p < database_setup.sql
-- =================================================================

-- =================================================================
-- User Creation
-- =================================================================
-- This section creates the users required by the application.
-- NOTE: The password 'Admin123' is for development only. 
-- Replace it with a strong, secure password in production.

DROP USER IF EXISTS 'hsi_lgu_core'@'localhost';
DROP USER IF EXISTS 'hsi_lgu_checklist_assessment'@'localhost';
DROP USER IF EXISTS 'hsi_lgu_inspection_scheduling'@'localhost';
DROP USER IF EXISTS 'hsi_lgu_media_uploads'@'localhost';
DROP USER IF EXISTS 'hsi_lgu_violations_ticketing'@'localhost';
DROP USER IF EXISTS 'hsi_lgu_reports_notifications'@'localhost';

CREATE USER 'hsi_lgu_core'@'localhost' IDENTIFIED BY 'Admin123';
CREATE USER 'hsi_lgu_checklist_assessment'@'localhost' IDENTIFIED BY 'Admin123';
CREATE USER 'hsi_lgu_inspection_scheduling'@'localhost' IDENTIFIED BY 'Admin123';
CREATE USER 'hsi_lgu_media_uploads'@'localhost' IDENTIFIED BY 'Admin123';
CREATE USER 'hsi_lgu_violations_ticketing'@'localhost' IDENTIFIED BY 'Admin123';
CREATE USER 'hsi_lgu_reports_notifications'@'localhost' IDENTIFIED BY 'Admin123';

-- =================================================================

--
-- Database: `hsi_lgu_checklist_assessment`
USE `hsi_lgu_checklist_assessment`;


-- --------------------------------------------------------

--
-- Table structure for table `checklist_templates`
--

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

--
-- Dumping data for table `checklist_templates`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `inspection_responses`
--

CREATE TABLE `inspection_responses` (
  `id` int(11) NOT NULL,
  `inspection_id` int(11) NOT NULL,
  `checklist_template_id` int(11) NOT NULL,
  `response` text DEFAULT NULL,
  `ai_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_analysis`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inspection_responses`
--

INSERT INTO `inspection_responses` (`id`, `inspection_id`, `checklist_template_id`, `response`, `ai_analysis`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Yes', '{\"confidence\": 0.95}', '2025-09-16 14:06:15', '2025-09-16 14:06:15'),
(2, 1, 2, 'Proper waste bins and regular collection', '{\"sentiment\": \"positive\"}', '2025-09-16 14:06:15', '2025-09-16 14:06:15'),
(3, 2, 6, 'Good hygiene practices observed', '{\"compliance\": 0.88}', '2025-09-16 14:06:15', '2025-09-16 14:06:15'),
(4, 2, 7, '5', NULL, '2025-09-16 14:06:15', '2025-09-16 14:06:15'),
(5, 3, 8, 'Yes', '{\"confidence\": 0.92}', '2025-09-16 14:06:15', '2025-09-16 14:06:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `checklist_templates`
--
ALTER TABLE `checklist_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inspection_type` (`inspection_type_id`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `inspection_responses`
--
ALTER TABLE `inspection_responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_inspection_checklist` (`inspection_id`,`checklist_template_id`),
  ADD KEY `idx_inspection` (`inspection_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `checklist_templates`
--
ALTER TABLE `checklist_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `inspection_responses`
--
ALTER TABLE `inspection_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Database: `hsi_lgu_core`
--
USE `hsi_lgu_core`;


-- --------------------------------------------------------

--
-- Table structure for table `blockchain`
--

CREATE TABLE `blockchain` (
  `id` int(11) NOT NULL,
  `hash` varchar(64) NOT NULL,
  `previous_hash` varchar(64) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `data_hash` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `businesses`
--

CREATE TABLE `businesses` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `inspector_id` int(11) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `business_type` varchar(100) DEFAULT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `establishment_date` date DEFAULT NULL,
  `inspection_frequency` enum('weekly','monthly','quarterly') DEFAULT 'monthly',
  `last_inspection_date` date DEFAULT NULL,
  `next_inspection_date` date DEFAULT NULL,
  `is_compliant` tinyint(1) DEFAULT 1,
  `compliance_score` int(11) DEFAULT 100,
  `hash` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `businesses`
--

INSERT INTO `businesses` (`id`, `name`, `address`, `owner_id`, `inspector_id`, `contact_number`, `email`, `business_type`, `registration_number`, `establishment_date`, `inspection_frequency`, `last_inspection_date`, `next_inspection_date`, `is_compliant`, `compliance_score`, `hash`, `created_at`, `updated_at`) VALUES
(1, 'ABC Restaurant', '123 Main St, Makati City', NULL, NULL, '+63 912 345 6789', 'abc.restaurant@email.com', 'Restaurant', 'BUS-001', NULL, 'monthly', NULL, NULL, 1, 100, NULL, '2025-09-16 14:06:15', '2025-09-16 14:06:15'),
(2, 'XYZ Mall', '456 Commerce Ave, BGC', NULL, NULL, '+63 917 654 3210', 'xyz.mall@email.com', 'Shopping Mall', 'BUS-002', NULL, 'monthly', NULL, NULL, 1, 100, NULL, '2025-09-16 14:06:15', '2025-09-16 14:06:15'),
(3, 'Tech Hub Office', '789 IT Park, Cebu', NULL, NULL, '+63 918 765 4321', 'tech.hub@email.com', 'Office Building', 'BUS-003', NULL, 'monthly', NULL, NULL, 1, 100, NULL, '2025-09-16 14:06:15', '2025-09-16 14:06:15'),
(4, 'Green Grocers', '321 Market St, Davao City', NULL, NULL, '+63 913 222 3344', 'green.grocers@email.com', 'Grocery Store', 'BUS-004', NULL, 'monthly', NULL, NULL, 1, 100, NULL, '2025-09-16 14:06:15', '2025-09-16 14:06:15'),
(5, 'Sunshine Bakery', '654 Baker Rd, Quezon City', NULL, NULL, '+63 914 555 6677', 'sunshine.bakery@email.com', 'Bakery', 'BUS-005', NULL, 'monthly', '2025-09-16', '2025-10-16', 1, 100, NULL, '2025-09-16 14:06:15', '2025-09-16 14:32:10'),
(6, 'Jefflix', 'Bagong Silang, Caloocan City', 9, NULL, '+639668021169', 'jefflix@gmail.com', 'Education', 'N-920128329', NULL, 'monthly', NULL, NULL, 1, 100, NULL, '2025-09-16 14:10:28', '2025-09-16 14:10:28');

-- --------------------------------------------------------

--
-- Table structure for table `inspection_types`
--

CREATE TABLE `inspection_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inspection_types`
--

INSERT INTO `inspection_types` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Health & Sanitation', 'Health and sanitation compliance inspections', '2025-09-16 14:06:15'),
(2, 'Fire Safety', 'Fire safety and prevention inspections', '2025-09-16 14:06:15'),
(3, 'Building Safety', 'Building structure and safety inspections', '2025-09-16 14:06:15'),
(4, 'Environmental', 'Environmental compliance inspections', '2025-09-16 14:06:15'),
(5, 'Food Safety', 'Food safety and hygiene inspections', '2025-09-16 14:06:15');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `session_id` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `session_data` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','inspector','business_owner','community_user') NOT NULL DEFAULT 'inspector',
  `avatar` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `certification` varchar(255) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `remember_token_selector` varchar(32) DEFAULT NULL,
  `remember_token_validator_hash` varchar(255) DEFAULT NULL,
  `remember_token_expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `avatar`, `department`, `certification`, `reset_token`, `reset_token_expires_at`, `remember_token_selector`, `remember_token_validator_hash`, `remember_token_expires_at`, `created_at`, `updated_at`) VALUES
(7, 'superadmin', 'superadmin@gmail.com', '$2y$10$.qYt3e9XV2A8dUCW1.mA4.DBDuojZfkunBBdsT9s5V3PQZzIx5bK6', 'super_admin', '', 'Super Admin', 'Super Administrator', NULL, NULL, NULL, NULL, NULL, '2025-09-16 14:09:05', '2025-09-16 14:09:05'),
(8, 'Inspector', 'inspector@gmail.com', '$2y$10$dhga1hRVgHgwTwQDCvKx2.tVYFWTq3nNkspJAhTfhsLaTDRNpzZKS', 'inspector', 'uploads/avatars/68c97507ba074-ec3e40da-dadd-4194-9d85-7edd222b63b0.jpg', 'Inspector', 'Certified Inspector', NULL, NULL, NULL, NULL, NULL, '2025-09-16 14:09:23', '2025-09-16 14:32:39'),
(9, 'Jeff Paray', 'jeffbusiness@gmail.com', '$2y$10$oQAGqmHMd82XJy.nO7IsR.xZaFsdqWQUHFiHhJY7kvX8B0RgzIZgO', 'business_owner', '', 'Jefflix', 'Business Owner', NULL, NULL, NULL, NULL, NULL, '2025-09-16 14:10:28', '2025-09-16 14:10:28'),
(10, 'jeff community', 'community@gmail.com', '$2y$10$4mbhnxutj71J9B2qXZwcJe10hYPlnOyhVSAPx0ilB9EUjMUhwcjlu', 'community_user', '', '', 'Community User', NULL, NULL, NULL, NULL, NULL, '2025-09-16 14:10:49', '2025-09-16 14:10:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blockchain`
--
ALTER TABLE `blockchain`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hash` (`hash`),
  ADD KEY `idx_previous_hash` (`previous_hash`),
  ADD KEY `idx_table_record` (`table_name`,`record_id`);

--
-- Indexes for table `businesses`
--
ALTER TABLE `businesses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_business_type` (`business_type`),
  ADD KEY `idx_compliance` (`is_compliant`),
  ADD KEY `idx_next_inspection` (`next_inspection_date`),
  ADD KEY `idx_hash` (`hash`);

--
-- Indexes for table `inspection_types`
--
ALTER TABLE `inspection_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name` (`name`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `last_activity_idx` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_reset_token` (`reset_token`),
  ADD KEY `idx_remember_selector` (`remember_token_selector`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blockchain`
--
ALTER TABLE `blockchain`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `businesses`
--
ALTER TABLE `businesses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inspection_types`
--
ALTER TABLE `inspection_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Database: `hsi_lgu_inspection_scheduling`
--
USE `hsi_lgu_inspection_scheduling`;


-- --------------------------------------------------------

--
-- Table structure for table `inspections`
--

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

--
-- Dumping data for table `inspections`
--

INSERT INTO `inspections` (`id`, `business_id`, `inspector_id`, `inspection_type_id`, `scheduled_date`, `completed_date`, `status`, `priority`, `compliance_score`, `total_violations`, `notes`, `notes_ai_analysis`, `draft_data`, `hash`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, '2024-01-15 09:00:00', NULL, 'scheduled', 'high', NULL, 0, NULL, NULL, NULL, NULL, '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(2, 2, 3, 2, '2024-01-16 10:00:00', NULL, 'in_progress', 'medium', 85, 2, NULL, NULL, NULL, NULL, '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(3, 3, 2, 3, '2024-01-14 14:00:00', NULL, 'completed', 'low', 92, 1, NULL, NULL, NULL, NULL, '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(4, 4, NULL, 1, '2024-01-20 09:00:00', NULL, 'scheduled', 'medium', NULL, 0, NULL, NULL, NULL, NULL, '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(5, 5, 8, 2, '2024-01-21 10:00:00', '2025-09-16 16:32:10', 'completed', 'high', 100, 0, '', NULL, NULL, NULL, '2025-09-16 14:06:16', '2025-09-16 14:32:10'),
(6, 6, 8, 4, '2025-09-16 00:00:00', NULL, 'scheduled', 'low', NULL, 0, 'Bilis ya', NULL, NULL, NULL, '2025-09-16 14:35:43', '2025-09-16 14:36:39'),
(7, 1, 8, 5, '2025-09-17 01:00:00', NULL, 'scheduled', 'low', NULL, 0, 'Follow-up on violation (ID: 7): &quot;may ipis yung lugaw&quot;', NULL, NULL, NULL, '2025-09-16 14:40:20', '2025-09-17 13:31:22');

-- --------------------------------------------------------

--
-- Table structure for table `inspector_specializations`
--

CREATE TABLE `inspector_specializations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `inspection_type_id` int(11) NOT NULL,
  `proficiency_level` enum('beginner','intermediate','expert') DEFAULT 'intermediate',
  `certification_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inspector_specializations`
--

INSERT INTO `inspector_specializations` (`id`, `user_id`, `inspection_type_id`, `proficiency_level`, `certification_date`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'expert', '2023-06-15', '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(2, 2, 3, 'intermediate', '2023-08-20', '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(3, 3, 2, 'expert', '2023-05-10', '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(4, 3, 4, 'intermediate', '2023-09-05', '2025-09-16 14:06:16', '2025-09-16 14:06:16'),
(5, 1, 1, 'expert', '2022-12-01', '2025-09-16 14:06:16', '2025-09-16 14:06:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inspections`
--
ALTER TABLE `inspections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_business` (`business_id`),
  ADD KEY `idx_inspector` (`inspector_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_scheduled_date` (`scheduled_date`),
  ADD KEY `idx_hash` (`hash`);

--
-- Indexes for table `inspector_specializations`
--
ALTER TABLE `inspector_specializations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_specialization` (`user_id`,`inspection_type_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_inspection_type` (`inspection_type_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inspections`
--
ALTER TABLE `inspections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `inspector_specializations`
--
ALTER TABLE `inspector_specializations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Database: `hsi_lgu_media_uploads`
--
USE `hsi_lgu_media_uploads`;


-- --------------------------------------------------------

--
-- Table structure for table `inspection_media`
--

CREATE TABLE `inspection_media` (
  `id` int(11) NOT NULL,
  `inspection_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` enum('image','video') NOT NULL,
  `file_size` int(11) NOT NULL,
  `ai_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_analysis`)),
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inspection_media`
--

INSERT INTO `inspection_media` (`id`, `inspection_id`, `filename`, `file_path`, `file_type`, `file_size`, `ai_analysis`, `uploaded_by`, `created_at`) VALUES
(1, 1, 'restaurant_cleanliness.jpg', '/uploads/inspections/1/restaurant_cleanliness.jpg', 'image', 2048576, '{\"objects\": [\"clean floor\", \"sanitized tables\"]}', 2, '2025-09-16 14:06:16'),
(2, 2, 'fire_extinguisher_check.mp4', '/uploads/inspections/2/fire_extinguisher_check.mp4', 'video', 15728640, '{\"duration\": \"00:02:15\", \"quality\": \"HD\"}', 3, '2025-09-16 14:06:16'),
(3, 3, 'building_safety_photo.jpg', '/uploads/inspections/3/building_safety_photo.jpg', 'image', 1536000, '{\"hazards\": \"none detected\"}', 2, '2025-09-16 14:06:16'),
(4, 1, 'waste_management_area.jpg', '/uploads/inspections/1/waste_management_area.jpg', 'image', 1024000, '{\"compliance\": 0.95}', 2, '2025-09-16 14:06:16'),
(5, 2, 'emergency_exit_sign.jpg', '/uploads/inspections/2/emergency_exit_sign.jpg', 'image', 768000, '{\"visibility\": \"clear\"}', 3, '2025-09-16 14:06:16'),
(6, 5, '68c9732cdf2dc-0130a0ab-5171-42fc-8c6f-46d454db1475.jpg', 'inspections/5/68c9732cdf2dc-0130a0ab-5171-42fc-8c6f-46d454db1475.jpg', 'image', 75721, '{\"compliance\":\"error\",\"confidence\":0,\"hazards\":[\"AI Vision API Error: API Error (Code: 404).\"],\"positive_observations\":[]}', 7, '2025-09-16 14:24:44'),
(7, 7, '68c982454f60a-0130a0ab-5171-42fc-8c6f-46d454db1475.jpg', 'inspections/7/68c982454f60a-0130a0ab-5171-42fc-8c6f-46d454db1475.jpg', 'image', 75721, '{\"compliance\":\"error\",\"confidence\":0,\"hazards\":[\"AI Vision API Error: API Error (Code: 404).\"],\"positive_observations\":[]}', 7, '2025-09-16 15:29:09'),
(8, 7, '68c982904c863-ec3e40da-dadd-4194-9d85-7edd222b63b0.jpg', 'inspections/7/68c982904c863-ec3e40da-dadd-4194-9d85-7edd222b63b0.jpg', 'image', 27045, '{\"compliance\":\"error\",\"confidence\":0,\"hazards\":[\"AI Vision API Error: API Error (Code: 404).\"],\"positive_observations\":[]}', 7, '2025-09-16 15:30:24'),
(9, 7, '68c98376089b7-1e349bb7-8ee1-408d-b912-22a801ab1a03.jpg', 'inspections/7/68c98376089b7-1e349bb7-8ee1-408d-b912-22a801ab1a03.jpg', 'image', 97027, '{\"compliance\":\"error\",\"confidence\":0,\"hazards\":[\"AI Vision API Error: API Error (Code: 404).\"],\"positive_observations\":[]}', 7, '2025-09-16 15:34:14'),
(10, 7, '68c984e7a5f97-0130a0ab-5171-42fc-8c6f-46d454db1475.jpg', 'inspections/7/68c984e7a5f97-0130a0ab-5171-42fc-8c6f-46d454db1475.jpg', 'image', 75721, '{\"compliance\":\"error\",\"confidence\":0,\"hazards\":[\"AI Vision API Error: API Error (Code: 429).\"],\"positive_observations\":[]}', 7, '2025-09-16 15:40:23'),
(11, 7, '68c9852c0b972-6ded4beb-3dff-4ee7-8983-f3c93e4fe324.jpg', 'inspections/7/68c9852c0b972-6ded4beb-3dff-4ee7-8983-f3c93e4fe324.jpg', 'image', 271153, '{\"hazards\":[],\"positive_observations\":[],\"confidence\":0,\"compliance\":\"needs_review\"}', 7, '2025-09-16 15:41:32'),
(12, 7, '68c985ae2fb7f-pagpag.avif', 'inspections/7/68c985ae2fb7f-pagpag.avif', 'image', 50719, '{\"hazards\":[\"Potential for biological hazards from handling waste without appropriate PPE\",\"Unsanitary conditions due to open waste and potential for contamination\",\"Lack of appropriate waste disposal procedures\"],\"positive_observations\":[],\"confidence\":0.9,\"compliance\":\"non_compliant\"}', 7, '2025-09-16 15:43:42'),
(13, 7, '68c98601995d9-malinis.jpg', 'inspections/7/68c98601995d9-malinis.jpg', 'image', 90870, '{\"hazards\":[],\"positive_observations\":[],\"confidence\":1,\"compliance\":\"compliant\"}', 7, '2025-09-16 15:45:05'),
(14, 7, '68c9866e4e8c5-malinis.jpg', 'inspections/7/68c9866e4e8c5-malinis.jpg', 'image', 90870, '{\"hazards\":[],\"positive_observations\":[\"The cooking surface appears clean.\",\"Utensils are present and appear clean.\"],\"confidence\":1,\"compliance\":\"compliant\"}', 7, '2025-09-16 15:46:54'),
(15, 7, '68c9869a6c680-pagpag.avif', 'inspections/7/68c9869a6c680-pagpag.avif', 'image', 50719, '{\"hazards\":[\"Improper waste handling and disposal practices\",\"Potential for contamination and disease transmission\",\"Lack of personal protective equipment (PPE)\"],\"positive_observations\":[],\"confidence\":0.9,\"compliance\":\"non_compliant\"}', 7, '2025-09-16 15:47:38'),
(16, 7, '68c9871d69d64-crack.png', 'inspections/7/68c9871d69d64-crack.png', 'image', 318578, '{\"hazards\":[\"Significant structural damage to the building\'s exterior walls and columns, indicating potential collapse risk.\",\"Debris and potential falling hazards from damaged building sections.\",\"Compromised building integrity, posing a serious threat to occupants and bystanders.\"],\"positive_observations\":[],\"confidence\":0.95,\"compliance\":\"non_compliant\"}', 7, '2025-09-16 15:49:49'),
(17, 7, '68c9876126d9f-building.jpg', 'inspections/7/68c9876126d9f-building.jpg', 'image', 333765, '{\"hazards\":[],\"positive_observations\":[\"Building appears to be well-maintained and structurally sound\",\"The area around the building is clean and free of debris\"],\"confidence\":0.9,\"compliance\":\"compliant\"}', 7, '2025-09-16 15:50:57'),
(18, 7, '68cabaaebdd24-malinis.jpg', 'inspections/7/68cabaaebdd24-malinis.jpg', 'image', 90870, '{\"compliance\":\"error\",\"confidence\":0,\"hazards\":[\"AI Vision API Error: API Error (Code: 404).\"],\"positive_observations\":[]}', 8, '2025-09-17 13:42:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inspection_media`
--
ALTER TABLE `inspection_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inspection` (`inspection_id`),
  ADD KEY `idx_file_type` (`file_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inspection_media`
--
ALTER TABLE `inspection_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Database: `hsi_lgu_reports_notifications`
--
USE `hsi_lgu_reports_notifications`;


-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

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

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `type`, `is_read`, `related_entity_type`, `related_entity_id`, `created_at`) VALUES
(1, 1, 'New inspection scheduled for ABC Restaurant', 'info', 0, 'inspection', 1, '2025-09-16 14:06:16'),
(2, 1, 'Violation reported at XYZ Mall - Fire Exit Blocked', 'warning', 0, 'violation', 1, '2025-09-16 14:06:16'),
(3, 1, 'Inspector certification expires in 30 days', 'alert', 0, 'user', 2, '2025-09-16 14:06:16'),
(4, 2, 'Inspection completed for Tech Hub Office', 'success', 1, 'inspection', 3, '2025-09-16 14:06:16'),
(5, 3, 'New violation assigned: Water supply quality issue', 'alert', 0, 'violation', 4, '2025-09-16 14:06:16'),
(6, 1, 'Monthly compliance report generated', 'info', 0, NULL, NULL, '2025-09-16 14:06:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Database: `hsi_lgu_violations_ticketing`
--
USE `hsi_lgu_violations_ticketing`;


-- --------------------------------------------------------

--
-- Table structure for table `violations`
--

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

--
-- Dumping data for table `violations`
--

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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `violations`
--
ALTER TABLE `violations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inspection` (`inspection_id`),
  ADD KEY `idx_business` (`business_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_hash` (`hash`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `violations`
--
ALTER TABLE `violations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

-- =================================================================
-- Grant Privileges
-- =================================================================
-- This section grants the necessary permissions for the application to function.

-- Grant base privileges for each user to their own database
GRANT ALL PRIVILEGES ON `hsi_lgu_core`.* TO 'hsi_lgu_core'@'localhost';
GRANT ALL PRIVILEGES ON `hsi_lgu_checklist_assessment`.* TO 'hsi_lgu_checklist_assessment'@'localhost';
GRANT ALL PRIVILEGES ON `hsi_lgu_inspection_scheduling`.* TO 'hsi_lgu_inspection_scheduling'@'localhost';
GRANT ALL PRIVILEGES ON `hsi_lgu_media_uploads`.* TO 'hsi_lgu_media_uploads'@'localhost';
GRANT ALL PRIVILEGES ON `hsi_lgu_violations_ticketing`.* TO 'hsi_lgu_violations_ticketing'@'localhost';
GRANT ALL PRIVILEGES ON `hsi_lgu_reports_notifications`.* TO 'hsi_lgu_reports_notifications'@'localhost';

-- Grant necessary cross-database SELECT permissions for application functionality
-- All service users need to read from the core database (e.g., for business names, user details)
GRANT SELECT ON `hsi_lgu_core`.* TO 'hsi_lgu_checklist_assessment'@'localhost';
GRANT SELECT ON `hsi_lgu_core`.* TO 'hsi_lgu_inspection_scheduling'@'localhost';
GRANT SELECT ON `hsi_lgu_core`.* TO 'hsi_lgu_media_uploads'@'localhost';
GRANT SELECT ON `hsi_lgu_core`.* TO 'hsi_lgu_violations_ticketing'@'localhost';
GRANT SELECT ON `hsi_lgu_core`.* TO 'hsi_lgu_reports_notifications'@'localhost';

-- The core user needs to read from service databases for dashboards and analytics
GRANT SELECT ON `hsi_lgu_inspection_scheduling`.* TO 'hsi_lgu_core'@'localhost';
GRANT SELECT ON `hsi_lgu_violations_ticketing`.* TO 'hsi_lgu_core'@'localhost';
GRANT SELECT ON `hsi_lgu_checklist_assessment`.* TO 'hsi_lgu_core'@'localhost';

-- Specific service-to-service permissions
GRANT SELECT ON `hsi_lgu_violations_ticketing`.* TO 'hsi_lgu_inspection_scheduling'@'localhost';

FLUSH PRIVILEGES;