

--
-- Database: `hsi_lgu_violations_ticketing`
--
DROP DATABASE IF EXISTS `hsi_lgu_violations_ticketing`;
CREATE DATABASE `hsi_lgu_violations_ticketing`;
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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
