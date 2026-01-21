-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 21, 2026 at 01:38 PM
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
-- Table structure for table `admin_diaries_todo`
--

CREATE TABLE `admin_diaries_todo` (
  `id` int(11) NOT NULL,
  `type` enum('Task','Diary') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `entry_date` date NOT NULL,
  `status` enum('Pending','Completed') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_diaries_todo`
--

INSERT INTO `admin_diaries_todo` (`id`, `type`, `title`, `description`, `due_date`, `entry_date`, `status`, `created_by`, `created_at`) VALUES
(2, 'Diary', 'District Monthly Review Meeting', 'Conducted monthly progress review meeting with all Veterinary Surgeons and LDOs at District Office. Discussed FMD vaccination coverage and pending revenue collections.', NULL, '2026-01-05', 'Completed', 10, '2026-01-13 12:47:25'),
(3, 'Task', 'FMD Vaccination Campaign - Pottuvil Division', 'Coordinate and supervise Foot-and-Mouth Disease vaccination in Pottuvil area. Target: 600 cattle. Ensure vaccine stock and cold chain.', '2026-01-18', '2026-01-08', 'Pending', 10, '2026-01-13 12:47:25'),
(4, 'Diary', 'Inspection of Veterinary Office - Sainthamaruthu', 'Inspected Sainthamaruthu Veterinary Office. Checked drug stock, cold chain equipment, and staff attendance. Noted shortage of rabies vaccine.', NULL, '2026-01-07', 'Completed', 10, '2026-01-13 12:47:25'),
(5, 'Task', 'Farmer Training on Improved Fodder Cultivation', 'Organize training programme for 40 farmers in Damana area on improved fodder grass varieties and silage making. Coordinate with Agriculture Dept.', '2026-01-25', '2026-01-10', 'Pending', 10, '2026-01-13 12:47:25'),
(6, 'Diary', 'Meeting with Provincial Director', 'Attended meeting with Provincial Director at DAPH Head Office. Submitted district revenue report and discussed pending vehicle repairs.', NULL, '2026-01-12', 'Completed', 10, '2026-01-13 12:47:25');

-- --------------------------------------------------------

--
-- Table structure for table `diary_entries`
--

CREATE TABLE `diary_entries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `entry_date` date NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Draft','Submitted','Approved') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `diary_entries`
--

INSERT INTO `diary_entries` (`id`, `user_id`, `entry_date`, `title`, `notes`, `status`, `created_at`) VALUES
(14, 5, '2026-01-03', 'test 01', 'test description edited', 'Draft', '2026-01-03 12:14:40'),
(15, 18, '2026-01-05', 'Handing over autoclave', 'safely handed over the Autoclave to UOP\r\n\r\n\"ADD IMAGES\"', 'Draft', '2026-01-06 00:08:38'),
(21, 17, '2026-01-13', 'Meeting with Provinicial Director', 'at 10.00 am on 22-01-2026', 'Draft', '2026-01-13 06:01:26'),
(22, 17, '2026-01-14', 'General Campaign', 'To all Veterinary surgeons', 'Draft', '2026-01-13 06:02:08'),
(24, 16, '2026-01-13', 'FMD Vaccination Campaign - Pottuvil Division', 'Urgent', 'Draft', '2026-01-13 12:48:47'),
(25, 16, '2026-01-14', 'Meeting with Provincial Director', 'Top Urgent', 'Draft', '2026-01-13 12:49:07');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `reg_id` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `reason` varchar(100) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `department` varchar(100) NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `applied_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `staff_id`, `reg_id`, `name`, `reason`, `from_date`, `to_date`, `department`, `status`, `applied_date`) VALUES
(1, 1, 'REG_2500001', 'User 01', 'Sick Leave', '2025-12-26', '2025-12-28', 'Farm', 'Pending', '2025-12-23 12:38:53'),
(2, 2, 'REG_2500002', 'User 02', 'Annual Leave', '2025-12-30', '2026-01-05', 'Finance', 'Pending', '2025-12-23 12:38:53'),
(3, 19, 'REG_2500003', 'User 05', 'Medical Leave', '2025-12-24', '2025-12-27', 'Training', 'Approved', '2025-12-23 12:38:53'),
(4, 1, 'REG_2500001', 'User 01', 'Casual Leave', '2025-12-20', '2025-12-21', 'Farm', 'Approved', '2025-12-23 12:38:53'),
(5, 2, 'REG_2500002', 'User 02', 'Maternity Leave', '2026-01-01', '2026-03-31', 'Finance', 'Pending', '2025-12-23 12:38:53');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `project_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `budget` decimal(15,2) NOT NULL DEFAULT 0.00,
  `spent` decimal(15,2) NOT NULL DEFAULT 0.00,
  `progress` int(11) DEFAULT 0,
  `status` enum('Ongoing','Completed','Pending') DEFAULT 'Ongoing',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `reg_id` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` text NOT NULL,
  `reg_date` date NOT NULL,
  `department` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `reg_id`, `name`, `address`, `reg_date`, `department`, `created_at`) VALUES
(1, 'REG_2500001', 'User 01', 'home address', '2025-12-23', 'Farm', '2025-12-23 04:32:21'),
(2, 'REG_2500002', 'User 02', 'home address', '2025-12-23', 'Finance', '2025-12-23 04:33:15'),
(19, 'REG_2500003', 'User 05', 'Home Address', '2025-12-23', 'Training', '2025-12-23 05:08:25'),
(22, 'REG_2500004', 'Dr. Ahmed Rizwan', 'User Address', '2026-01-01', 'Veterinary ', '2026-01-12 06:15:16'),
(23, 'REG_2600001', 'Saman Perera', 'Home Address', '2026-01-01', 'Veterinary', '2026-01-12 06:45:18'),
(24, 'REG_2600002', 'Fathima Hassan', 'Home Address', '2026-01-01', 'Veterinary', '2026-01-12 06:45:46'),
(25, 'REG_2600003', 'Ravi Fernando', 'Home Address', '2026-01-01', 'Veterinary', '2026-01-12 06:46:16');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('provincial_director','district_dd','veterinary_surgeon','training_officer','sms','farms_dd','finance_admin','planning_officer','administrator','data_entry') NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `district` enum('Amparai','Batticaloa','Trincomalee','Provincial') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `office_id`, `district`, `status`, `last_login`, `created_at`) VALUES
(5, 'yo', 'provinciald2@gmail.com', 'b62c1853f21bb51f6ce7faca1becc040', 'Provincial Director', 'provincial_director', NULL, 'Provincial', 'active', '2026-01-03 16:15:19', '2025-12-12 11:30:50'),
(7, 'adminstrator', 'admins@gmail.com', '$2y$10$nlm7FQcS7mceOa48ZahFTO.DdagUFOjijh5Yl.HNTs4yj2fWBcq/2', 'Admin Login', 'administrator', NULL, 'Provincial', 'active', '2026-01-13 17:58:11', '2025-12-15 11:32:14'),
(10, 'finance_admin', 'finance@gmail.com', '$2y$10$pjmgh5Ij1k6tTXpCPuKo3.bxhwYip.D/D33bT4CSm4su2YUYnHlWe', 'Finance admin', 'finance_admin', NULL, 'Provincial', 'active', '2026-01-05 11:34:25', '2025-12-16 07:42:06'),
(11, 'Planning officer', 'planning@gmail.com', '$2y$10$xM5nKggJu8OJ5E4AV9n4OOuqJ4L2TUqxfXnBoAV0dBcqycEv2L99W', 'Planning officer', 'planning_officer', NULL, 'Provincial', 'active', '2026-01-21 17:28:49', '2025-12-16 09:34:59'),
(12, 'Subject Matter Specialist', 'sms@gmail.com', '$2y$10$M2geolCGKHuoKMn1R1A0x.Qde.C5H7ME3GS.BzQRMAE5gNpA4VmCu', 'Subject Matter Specialist', 'sms', NULL, 'Provincial', 'active', '2026-01-14 12:36:00', '2025-12-16 11:30:03'),
(13, 'Farms Officer', 'farms@gmail.com', '$2y$10$yig.Tm9WNcTOZx0wOY5ZzukY9Zp4L1Yf2tmilQWcHM5Rfw3euAyW6', 'Deputy Director (Farms Operation)', 'farms_dd', NULL, 'Provincial', 'active', '2026-01-05 11:43:58', '2025-12-17 08:46:28'),
(15, 'Training Officer', 'training@gmail.com', '$2y$10$dK4TD.h0f07IW/xDn.p8GuEW0kIiu2lhXlnYt64SUBeOaeWvIqNNK', 'Training Officer', 'training_officer', NULL, 'Provincial', 'active', '2026-01-21 17:41:01', '2025-12-17 10:22:46'),
(16, 'District Deputy Director', 'district_dd@gmail.com', '$2y$10$ktztqj1XUpA6UsNmP2wreuSepNmMZ.cdIAnSuQhhXBcuyjZcmrAQq', 'District Deputy Director', 'district_dd', NULL, 'Provincial', 'active', '2026-01-13 17:59:03', '2025-12-17 13:23:28'),
(17, 'veterinary surgeon', 'veterinary@gmail.com', '$2y$10$BuPbuNbGjVvPCb14jXTaBO4lKeuJSaMVVqMBmOlEmnQV2K.8P4B0W', 'veterinary surgeon', 'veterinary_surgeon', NULL, 'Provincial', 'active', '2026-01-13 12:54:56', '2025-12-18 10:10:22'),
(18, 'Provincial director', 'provinciald@gmail.com', '$2y$10$rosK7hcBMssxuPRgI6iqi.CbGiv7bmo7lsM68UAPaRxZR4/uJc37G', 'Provincial Director', 'provincial_director', NULL, 'Provincial', 'active', '2026-01-05 18:02:20', '2026-01-05 13:18:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_diaries_todo`
--
ALTER TABLE `admin_diaries_todo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_date` (`entry_date`);

--
-- Indexes for table `diary_entries`
--
ALTER TABLE `diary_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_date` (`user_id`,`entry_date`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_id` (`project_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reg_id` (`reg_id`);

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
  ADD KEY `idx_district` (`district`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_diaries_todo`
--
ALTER TABLE `admin_diaries_todo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `diary_entries`
--
ALTER TABLE `diary_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `diary_entries`
--
ALTER TABLE `diary_entries`
  ADD CONSTRAINT `diary_entries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
