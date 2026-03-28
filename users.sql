-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2026 at 11:47 AM
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
(19, 'Ampara veterinary surgeon', 'amp_veterinary@gmail.com', NULL, '$2y$10$tmJDAqL84RjQGr9TxsLdKeZTlIPk0PV5mWSC.RXYg7WqNPygorIhO', 'Ampara Veterinary Surgeon', 'veterinary_surgeon', 1, 1, 1, 'Amparai', 1, NULL, '2026-03-25 10:58:36');

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
  ADD KEY `idx_district` (`district`),
  ADD KEY `fk_user_district` (`district_id`),
  ADD KEY `fk_user_range` (`range_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

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
