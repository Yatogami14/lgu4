
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
