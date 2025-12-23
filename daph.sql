-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 23, 2025 at 12:46 PM
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
(19, 'REG_2500003', 'User 05', 'Home Address', '2025-12-23', 'Training', '2025-12-23 05:08:25');

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
(5, 'yo', 'yohanii725@gmail.com', '$2y$10$Y2H/a8CIg/6yrjaR3u4YAuNUSOmaHcpWu/TSJjYfLK8qLBbmN1FMa', 'Yohani Abeykoon', 'provincial_director', NULL, 'Provincial', 'active', '2025-12-22 16:11:55', '2025-12-12 11:30:50'),
(7, 'adminstrator', 'admins@gmail.com', '$2y$10$nlm7FQcS7mceOa48ZahFTO.DdagUFOjijh5Yl.HNTs4yj2fWBcq/2', 'Admin Login', 'administrator', NULL, 'Provincial', 'active', '2025-12-23 16:39:33', '2025-12-15 11:32:14'),
(10, 'finance_admin', 'finance@gmail.com', '$2y$10$pjmgh5Ij1k6tTXpCPuKo3.bxhwYip.D/D33bT4CSm4su2YUYnHlWe', 'Finance admin', 'finance_admin', NULL, 'Provincial', 'active', '2025-12-16 13:12:26', '2025-12-16 07:42:06'),
(11, 'Planning officer', 'planning@gmail.com', '$2y$10$xM5nKggJu8OJ5E4AV9n4OOuqJ4L2TUqxfXnBoAV0dBcqycEv2L99W', 'Planning officer', 'planning_officer', NULL, 'Provincial', 'active', '2025-12-16 15:05:14', '2025-12-16 09:34:59'),
(12, 'Subject Matter Specialist', 'sms@gmail.com', '$2y$10$M2geolCGKHuoKMn1R1A0x.Qde.C5H7ME3GS.BzQRMAE5gNpA4VmCu', 'Subject Matter Specialist', 'sms', NULL, 'Provincial', 'active', '2025-12-16 17:00:21', '2025-12-16 11:30:03'),
(13, 'Farms Officer', 'farms@gmail.com', '$2y$10$yig.Tm9WNcTOZx0wOY5ZzukY9Zp4L1Yf2tmilQWcHM5Rfw3euAyW6', 'Deputy Director (Farms Operation)', 'farms_dd', NULL, 'Provincial', 'active', '2025-12-18 17:21:32', '2025-12-17 08:46:28'),
(15, 'Training Officer', 'training@gmail.com', '$2y$10$dK4TD.h0f07IW/xDn.p8GuEW0kIiu2lhXlnYt64SUBeOaeWvIqNNK', 'Training Officer', 'training_officer', NULL, 'Provincial', 'active', '2025-12-17 15:52:48', '2025-12-17 10:22:46'),
(16, 'District Deputy Director', 'district_dd@gmail.com', '$2y$10$ktztqj1XUpA6UsNmP2wreuSepNmMZ.cdIAnSuQhhXBcuyjZcmrAQq', 'District Deputy Director', 'district_dd', NULL, 'Provincial', 'active', '2025-12-17 18:53:41', '2025-12-17 13:23:28'),
(17, 'veterinary surgeon', 'veterinary@gmail.com', '$2y$10$BuPbuNbGjVvPCb14jXTaBO4lKeuJSaMVVqMBmOlEmnQV2K.8P4B0W', 'veterinary surgeon', 'veterinary_surgeon', NULL, 'Provincial', 'active', '2025-12-18 15:40:45', '2025-12-18 10:10:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

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
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
