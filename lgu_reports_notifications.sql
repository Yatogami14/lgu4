
--
-- Database: `hsi_lgu_reports_notifications`
--
DROP DATABASE IF EXISTS `hsi_lgu_reports_notifications`;
CREATE DATABASE `hsi_lgu_reports_notifications`;
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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
