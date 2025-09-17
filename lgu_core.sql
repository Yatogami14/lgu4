
--
-- Database: `hsi_lgu_core`
--
DROP DATABASE IF EXISTS `hsi_lgu_core`;
CREATE DATABASE `hsi_lgu_core`;
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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
