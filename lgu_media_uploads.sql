
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
