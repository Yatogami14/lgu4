

--
-- Database: `hsi_lgu_checklist_assessment`
--
DROP DATABASE IF EXISTS `hsi_lgu_checklist_assessment`;
CREATE DATABASE `hsi_lgu_checklist_assessment`;
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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
