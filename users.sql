-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 22, 2026 at 12:12 PM
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
  `emp_id` varchar(50) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `role` enum('provincial_director','district_dd','veterinary_surgeon','training_officer','sms','farms_dd','finance_admin','planning_officer','administrator','data_entry','employee') NOT NULL,
  `district_id` int(11) DEFAULT NULL,
  `range_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `registered_date` date DEFAULT NULL,
  `office_id` int(11) DEFAULT NULL COMMENT 'Links to veterinary_ranges.id (for Veterinary Surgeon only)',
  `district` enum('Amparai','Batticaloa','Trincomalee','Provincial') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `full_name`, `emp_id`, `designation`, `role`, `district_id`, `range_id`, `unit_id`, `registered_date`, `office_id`, `district`, `is_active`, `last_login`, `created_at`, `profile_image`) VALUES
(5, 'yo', 'provinciald2@gmail.com', NULL, 'b62c1853f21bb51f6ce7faca1becc040', 'Provincial Director', NULL, NULL, 'provincial_director', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-01-03 16:15:19', '2025-12-12 11:30:50', NULL),
(7, 'adminstrator', 'admins@gmail.com', NULL, '$2y$10$nlm7FQcS7mceOa48ZahFTO.DdagUFOjijh5Yl.HNTs4yj2fWBcq/2', 'Admin Login', NULL, NULL, 'administrator', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-05-21 16:36:50', '2025-12-15 11:32:14', NULL),
(10, 'finance_admin', 'finance@gmail.com', NULL, '$2y$10$pjmgh5Ij1k6tTXpCPuKo3.bxhwYip.D/D33bT4CSm4su2YUYnHlWe', 'Finance admin', NULL, NULL, 'finance_admin', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-06-10 16:04:04', '2025-12-16 07:42:06', NULL),
(11, 'Planning officer', 'planning@gmail.com', NULL, '$2y$10$xM5nKggJu8OJ5E4AV9n4OOuqJ4L2TUqxfXnBoAV0dBcqycEv2L99W', 'Planning officer', NULL, NULL, 'planning_officer', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-06-10 16:05:02', '2025-12-16 09:34:59', NULL),
(12, 'Subject Matter Specialist', 'sms@gmail.com', NULL, '$2y$10$M2geolCGKHuoKMn1R1A0x.Qde.C5H7ME3GS.BzQRMAE5gNpA4VmCu', 'Subject Matter Specialist', NULL, NULL, 'sms', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-06-17 12:34:03', '2025-12-16 11:30:03', NULL),
(13, 'Farms Officer', 'farms@gmail.com', NULL, '$2y$10$yig.Tm9WNcTOZx0wOY5ZzukY9Zp4L1Yf2tmilQWcHM5Rfw3euAyW6', 'Deputy Director (Farms Operation)', NULL, NULL, 'farms_dd', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-05-25 20:22:03', '2025-12-17 08:46:28', NULL),
(15, 'Training Officer', 'training@gmail.com', NULL, '$2y$10$dK4TD.h0f07IW/xDn.p8GuEW0kIiu2lhXlnYt64SUBeOaeWvIqNNK', 'Training Officer', NULL, NULL, 'training_officer', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-06-10 17:18:35', '2025-12-17 10:22:46', NULL),
(16, 'District Deputy Director', 'district_dd@gmail.com', NULL, '$2y$10$ktztqj1XUpA6UsNmP2wreuSepNmMZ.cdIAnSuQhhXBcuyjZcmrAQq', 'District Deputy Director', NULL, NULL, 'district_dd', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-06-09 18:17:56', '2025-12-17 13:23:28', NULL),
(17, 'veterinary surgeon', 'veterinary@gmail.com', NULL, '$2y$10$BuPbuNbGjVvPCb14jXTaBO4lKeuJSaMVVqMBmOlEmnQV2K.8P4B0W', 'veterinary surgeon', NULL, NULL, 'veterinary_surgeon', 1, 1, NULL, NULL, NULL, 'Amparai', 1, '2026-06-22 11:55:11', '2025-12-18 10:10:22', NULL),
(18, 'Provincial director', 'provinciald@gmail.com', NULL, '$2y$10$rosK7hcBMssxuPRgI6iqi.CbGiv7bmo7lsM68UAPaRxZR4/uJc37G', 'Provincial Director', NULL, NULL, 'provincial_director', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-06-17 12:17:54', '2026-01-05 13:18:11', NULL),
(19, 'Ampara veterinary surgeon', 'amp_veterinary@gmail.com', NULL, '$2y$10$tmJDAqL84RjQGr9TxsLdKeZTlIPk0PV5mWSC.RXYg7WqNPygorIhO', 'Ampara Veterinary Surgeon', NULL, NULL, 'veterinary_surgeon', 1, 1, NULL, NULL, 1, 'Amparai', 1, '2026-06-10 18:37:48', '2026-03-25 10:58:36', NULL),
(20, 'employee', 'emp@gmail.com', NULL, '$2y$10$ITeSMQXxM8Ciwu4KK/Sy2O7ai30xUjP8yrL1WNRzXlNnsrG8ylfZK', 'Test Employee', NULL, NULL, 'employee', NULL, 1, NULL, NULL, NULL, 'Amparai', 1, '2026-05-18 17:56:16', '2026-04-22 06:10:30', 'profile_20_1777526035.png'),
(21, 'dujiththera', 'dujiththera.l@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Dr. (Mrs). L. Dujiththera', NULL, 'GVS', 'veterinary_surgeon', 2, 21, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(22, 'sinharasa', 'sinharasa.a@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mr. A. Sinharasa', NULL, 'LDO', 'employee', 2, 21, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(23, 'amirthalingam', 'amirthalingam.p@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mrs. P. Amirthalingam', NULL, 'LDO', 'employee', 2, 21, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(24, 'vimalathasan', 'vimalathasan.s@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mrs. S. Vimalathasan', NULL, 'PDO', 'employee', 2, 21, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(25, 'muruhathasan', 'muruhathasan.k@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mrs. K. Muruhathasan', NULL, 'PDO', 'employee', 2, 21, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(26, 'yoganathan', 'yoganathan.k@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mrs. K. Yoganathan', NULL, 'PDO', 'employee', 2, 21, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(27, 'thiruganasuntharam', 'thiru.s@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mrs. S. Thiruganasuntharam', NULL, 'CDO', 'employee', 2, 21, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(28, 'koneswaran', 'koneswaran.n@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mr. N. Koneswaran', NULL, 'PDO', 'employee', 2, 21, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(29, 'saththiyawan', 'saththiyawan.t@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mr. T. Saththiyawan', NULL, 'Driver', 'employee', 2, 21, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(30, 'gaminiraj', 'gaminiraj.n@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mr. N. Gaminiraj', NULL, 'Watcher', 'employee', 2, 21, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

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
