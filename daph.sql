-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 25, 2026 at 12:26 PM
-- Server version: 10.4.18-MariaDB
-- PHP Version: 8.0.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `daph`
--

-- --------------------------------------------------------

--
-- Table structure for table `animal_health_records`
--

CREATE TABLE `animal_health_records` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `disease_name` varchar(100) DEFAULT NULL,
  `occurrence_count` int(11) DEFAULT NULL,
  `affected_animals` text DEFAULT NULL,
  `treatment_details` text DEFAULT NULL,
  `report_status` varchar(20) DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `log_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `role` varchar(30) DEFAULT NULL,
  `action_type` varchar(20) NOT NULL,
  `module_name` varchar(100) DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_info` text DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `breeding_activities`
--

CREATE TABLE `breeding_activities` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `activity_type` varchar(20) DEFAULT NULL,
  `animal_count` int(11) DEFAULT NULL,
  `success_rate` decimal(5,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `districts`
--

INSERT INTO `districts` (`id`, `name`) VALUES
(1, 'Ampara'),
(2, 'Batticaloa'),
(3, 'Trincomalee');

-- --------------------------------------------------------

--
-- Table structure for table `office_details`
--

CREATE TABLE `office_details` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `total_staff` int(11) DEFAULT 0,
  `immovable_assets_text` text DEFAULT NULL,
  `movable_assets_text` text DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `regulatory_records`
--

CREATE TABLE `regulatory_records` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `activity_type` varchar(50) DEFAULT NULL,
  `certificate_number` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('provincial_director','district_dd','veterinary_surgeon','training_officer','sms','farms_dd','finance_admin','planning_officer','administrator','data_entry') NOT NULL,
  `district_id` int(11) DEFAULT NULL,
  `range_id` int(11) DEFAULT NULL,
  `office_id` int(11) DEFAULT NULL COMMENT 'Links to veterinary_ranges.id (for Veterinary Surgeon only)',
  `district` enum('Amparai','Batticaloa','Trincomalee','Provincial') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `full_name`, `role`, `district_id`, `range_id`, `office_id`, `district`, `is_active`, `last_login`, `created_at`) VALUES
(5, 'yo', 'provinciald2@gmail.com', NULL, 'b62c1853f21bb51f6ce7faca1becc040', 'Provincial Director', 'provincial_director', NULL, NULL, NULL, 'Provincial', 1, '2026-01-03 16:15:19', '2025-12-12 11:30:50'),
(7, 'adminstrator', 'admins@gmail.com', NULL, '$2y$10$nlm7FQcS7mceOa48ZahFTO.DdagUFOjijh5Yl.HNTs4yj2fWBcq/2', 'Admin Login', 'administrator', NULL, NULL, NULL, 'Provincial', 1, '2026-02-16 10:20:16', '2025-12-15 11:32:14'),
(10, 'finance_admin', 'finance@gmail.com', NULL, '$2y$10$pjmgh5Ij1k6tTXpCPuKo3.bxhwYip.D/D33bT4CSm4su2YUYnHlWe', 'Finance admin', 'finance_admin', NULL, NULL, NULL, 'Provincial', 1, '2026-02-16 10:40:11', '2025-12-16 07:42:06'),
(11, 'Planning officer', 'planning@gmail.com', NULL, '$2y$10$xM5nKggJu8OJ5E4AV9n4OOuqJ4L2TUqxfXnBoAV0dBcqycEv2L99W', 'Planning officer', 'planning_officer', NULL, NULL, NULL, 'Provincial', 1, '2026-02-17 13:11:58', '2025-12-16 09:34:59'),
(12, 'Subject Matter Specialist', 'sms@gmail.com', NULL, '$2y$10$M2geolCGKHuoKMn1R1A0x.Qde.C5H7ME3GS.BzQRMAE5gNpA4VmCu', 'Subject Matter Specialist', 'sms', NULL, NULL, NULL, 'Provincial', 1, '2026-02-16 11:03:05', '2025-12-16 11:30:03'),
(13, 'Farms Officer', 'farms@gmail.com', NULL, '$2y$10$yig.Tm9WNcTOZx0wOY5ZzukY9Zp4L1Yf2tmilQWcHM5Rfw3euAyW6', 'Deputy Director (Farms Operation)', 'farms_dd', NULL, NULL, NULL, 'Provincial', 1, '2026-02-16 15:09:31', '2025-12-17 08:46:28'),
(15, 'Training Officer', 'training@gmail.com', NULL, '$2y$10$dK4TD.h0f07IW/xDn.p8GuEW0kIiu2lhXlnYt64SUBeOaeWvIqNNK', 'Training Officer', 'training_officer', NULL, NULL, NULL, 'Provincial', 1, '2026-02-18 16:59:37', '2025-12-17 10:22:46'),
(16, 'District Deputy Director', 'district_dd@gmail.com', NULL, '$2y$10$ktztqj1XUpA6UsNmP2wreuSepNmMZ.cdIAnSuQhhXBcuyjZcmrAQq', 'District Deputy Director', 'district_dd', NULL, NULL, NULL, 'Provincial', 1, '2026-02-18 16:59:48', '2025-12-17 13:23:28'),
(17, 'veterinary surgeon', 'veterinary@gmail.com', NULL, '$2y$10$BuPbuNbGjVvPCb14jXTaBO4lKeuJSaMVVqMBmOlEmnQV2K.8P4B0W', 'veterinary surgeon', 'veterinary_surgeon', 1, 1, NULL, 'Amparai', 1, '2026-03-24 11:08:27', '2025-12-18 10:10:22'),
(18, 'Provincial director', 'provinciald@gmail.com', NULL, '$2y$10$rosK7hcBMssxuPRgI6iqi.CbGiv7bmo7lsM68UAPaRxZR4/uJc37G', 'Provincial Director', 'provincial_director', NULL, NULL, NULL, 'Provincial', 1, '2026-02-13 10:00:42', '2026-01-05 13:18:11'),
(19, 'Ampara veterinary surgeon', 'amp_veterinary@gmail.com', NULL, '$2y$10$tmJDAqL84RjQGr9TxsLdKeZTlIPk0PV5mWSC.RXYg7WqNPygorIhO', 'Ampara Veterinary Surgeon', 'veterinary_surgeon', NULL, NULL, NULL, 'Amparai', 1, NULL, '2026-03-25 10:58:36');

-- --------------------------------------------------------

--
-- Table structure for table `veterinary_ranges`
--

CREATE TABLE `veterinary_ranges` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `district_id` int(11) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `veterinary_ranges`
--

INSERT INTO `veterinary_ranges` (`id`, `name`, `district_id`, `code`, `is_active`) VALUES
(1, 'Ampara', 1, NULL, 1),
(2, 'Kalmunai TD', 1, NULL, 1),
(3, 'Kalmunai MD', 1, NULL, 1),
(4, 'Sammanthurai', 1, NULL, 1),
(5, 'Navethanveli', 1, NULL, 1),
(6, 'Akkaraipattu', 1, NULL, 1),
(7, 'Addalaichenai', 1, NULL, 1),
(8, 'Pottuvil', 1, NULL, 1),
(9, 'Lahugala', 1, NULL, 1),
(10, 'Uhana', 1, NULL, 1),
(11, 'Dehiattakandiya', 1, NULL, 1),
(12, 'Mahaoya', 1, NULL, 1),
(13, 'Damana', 1, NULL, 1),
(14, 'Irakkamam', 1, NULL, 1),
(15, 'Karaitivu', 1, NULL, 1),
(16, 'Alayadivembu', 1, NULL, 1),
(17, 'Thirukkovil', 1, NULL, 1),
(18, 'Nintavur', 1, NULL, 1),
(19, 'Sainthamaruthu', 1, NULL, 1),
(20, 'Padiyathalawa', 1, NULL, 1),
(21, 'Batticaloa', 2, NULL, 1),
(22, 'Chenkalady', 2, NULL, 1),
(23, 'Eravur', 2, NULL, 1),
(24, 'Kathankudy', 2, NULL, 1),
(25, 'Valaichenai', 2, NULL, 1),
(26, 'Kiran', 2, NULL, 1),
(27, 'Oddamavadi', 2, NULL, 1),
(28, 'Vaharai', 2, NULL, 1),
(29, 'Kaluwanchikudy', 2, NULL, 1),
(30, 'Kokkaddicholai', 2, NULL, 1),
(31, 'karadiyanaru', 2, NULL, 1),
(32, 'Sathurukondan', 2, NULL, 1),
(33, 'Thumpankerny', 2, NULL, 1),
(34, 'Rethintenna', 2, NULL, 1),
(35, 'Town & Gravets', 3, NULL, 1),
(36, 'Kuchchaveli', 3, NULL, 1),
(37, 'Morawewa', 3, NULL, 1),
(38, 'PadaviSeripura', 3, NULL, 1),
(39, 'Thampalakamam', 3, NULL, 1),
(40, 'Kanthalai', 3, NULL, 1),
(41, 'Kinniya', 3, NULL, 1),
(42, 'Mutur', 3, NULL, 1),
(43, 'Echchilampattu', 3, NULL, 1),
(44, 'Seruwila', 3, NULL, 1),
(45, 'Gomarankadawala', 3, NULL, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `animal_health_records`
--
ALTER TABLE `animal_health_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_logs_user` (`user_id`),
  ADD KEY `idx_audit_logs_date` (`log_timestamp`);

--
-- Indexes for table `breeding_activities`
--
ALTER TABLE `breeding_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `office_details`
--
ALTER TABLE `office_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `regulatory_records`
--
ALTER TABLE `regulatory_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_district` (`district`),
  ADD KEY `fk_user_district` (`district_id`),
  ADD KEY `fk_user_range` (`range_id`);

--
-- Indexes for table `veterinary_ranges`
--
ALTER TABLE `veterinary_ranges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `animal_health_records`
--
ALTER TABLE `animal_health_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `breeding_activities`
--
ALTER TABLE `breeding_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `office_details`
--
ALTER TABLE `office_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `regulatory_records`
--
ALTER TABLE `regulatory_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `veterinary_ranges`
--
ALTER TABLE `veterinary_ranges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `animal_health_records`
--
ALTER TABLE `animal_health_records`
  ADD CONSTRAINT `animal_health_records_ibfk_1` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`),
  ADD CONSTRAINT `animal_health_records_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `breeding_activities`
--
ALTER TABLE `breeding_activities`
  ADD CONSTRAINT `breeding_activities_ibfk_1` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`),
  ADD CONSTRAINT `breeding_activities_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `office_details`
--
ALTER TABLE `office_details`
  ADD CONSTRAINT `office_details_ibfk_1` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`),
  ADD CONSTRAINT `office_details_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `regulatory_records`
--
ALTER TABLE `regulatory_records`
  ADD CONSTRAINT `regulatory_records_ibfk_1` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`),
  ADD CONSTRAINT `regulatory_records_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`),
  ADD CONSTRAINT `fk_user_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
