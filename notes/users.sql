-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 16, 2025 at 03:05 PM
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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('provincial_director','district_dd','veterinary_surgeon','ldo','sms','farms_dd','finance_admin','planning_officer','administrator','data_entry') NOT NULL,
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
(5, 'yo', 'yohanii725@gmail.com', '$2y$10$Y2H/a8CIg/6yrjaR3u4YAuNUSOmaHcpWu/TSJjYfLK8qLBbmN1FMa', 'Yohani Abeykoon', 'provincial_director', NULL, 'Provincial', 'active', '2025-12-15 13:25:07', '2025-12-12 11:30:50'),
(7, 'adminstrator', 'hr@gmail.com', '$2y$10$nlm7FQcS7mceOa48ZahFTO.DdagUFOjijh5Yl.HNTs4yj2fWBcq/2', 'Admin Login', 'administrator', NULL, 'Provincial', 'active', '2025-12-16 13:00:58', '2025-12-15 11:32:14'),
(10, 'finance_admin', 'finance@gmail.com', '$2y$10$pjmgh5Ij1k6tTXpCPuKo3.bxhwYip.D/D33bT4CSm4su2YUYnHlWe', 'Finance admin', 'finance_admin', NULL, 'Provincial', 'active', '2025-12-16 13:12:26', '2025-12-16 07:42:06'),
(11, 'Planning officer', 'planning@gmail.com', '$2y$10$xM5nKggJu8OJ5E4AV9n4OOuqJ4L2TUqxfXnBoAV0dBcqycEv2L99W', 'Planning officer', 'planning_officer', NULL, 'Provincial', 'active', '2025-12-16 15:05:14', '2025-12-16 09:34:59'),
(12, 'Subject Matter Specialist', 'sms@gmail.com', '$2y$10$M2geolCGKHuoKMn1R1A0x.Qde.C5H7ME3GS.BzQRMAE5gNpA4VmCu', 'Subject Matter Specialist', 'sms', NULL, 'Provincial', 'active', '2025-12-16 17:00:21', '2025-12-16 11:30:03');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
