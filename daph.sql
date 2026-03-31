-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 31, 2026 at 01:54 PM
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
  `farmer_reg_no` varchar(50) NOT NULL,
  `animal_type` enum('Cattle','Buffalo','Goat','Sheep','Swine','Poultry','Ornamental Birds','Other') NOT NULL,
  `disease_name` varchar(100) NOT NULL,
  `occurrence_count` int(11) DEFAULT 0,
  `vaccine_name` varchar(100) DEFAULT NULL,
  `doses` int(11) DEFAULT 0,
  `treatment_details` text DEFAULT NULL,
  `report_status` enum('Draft','Submitted','Approved') DEFAULT 'Submitted',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `animal_health_records`
--

INSERT INTO `animal_health_records` (`id`, `range_id`, `date`, `farmer_reg_no`, `animal_type`, `disease_name`, `occurrence_count`, `vaccine_name`, `doses`, `treatment_details`, `report_status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-03-28', 'AMP-001', 'Cattle', 'Mouth disease', 2, 'FMD', 2, 'come and see', 'Submitted', NULL, '2026-03-28 11:50:07', '2026-03-28 11:50:07'),
(2, 1, '2026-03-28', 'AMP-001', 'Cattle', 'Mouth disease', 2, 'FMD', 0, 'ww', 'Submitted', 19, '2026-03-28 11:52:01', '2026-03-28 11:52:01'),
(3, 1, '2026-03-30', 'AMP-009', 'Cattle', 'Fever', 3, 'FMD', 3, 'come only after having breakfast', 'Submitted', 19, '2026-03-30 13:50:27', '2026-03-30 13:50:27');

-- --------------------------------------------------------

--
-- Table structure for table `assets_immovable`
--

CREATE TABLE `assets_immovable` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `extent` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assets_immovable`
--

INSERT INTO `assets_immovable` (`id`, `range_id`, `asset_name`, `description`, `location`, `extent`) VALUES
(1, 1, 'computer', 'e', 'uppuveli', '2'),
(2, 1, 'computer', 'e', 'uppuveli', '2');

-- --------------------------------------------------------

--
-- Table structure for table `assets_movable`
--

CREATE TABLE `assets_movable` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `asset_category` enum('Vehicle','Equipment','Furniture','Other') DEFAULT 'Equipment',
  `item_name` varchar(255) NOT NULL,
  `serial_no` varchar(100) DEFAULT NULL,
  `condition` enum('Good','Fair','Needs Repair','Discarded') DEFAULT 'Good'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assets_movable`
--

INSERT INTO `assets_movable` (`id`, `range_id`, `asset_category`, `item_name`, `serial_no`, `condition`) VALUES
(1, 1, 'Vehicle', 'Car', '202', 'Fair');

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

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `log_timestamp`, `user_id`, `username`, `role`, `action_type`, `module_name`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `device_info`, `remarks`) VALUES
(1, '2026-03-27 11:24:17', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(2, '2026-03-28 06:03:04', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(3, '2026-03-28 08:32:41', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(4, '2026-03-28 08:32:54', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(5, '2026-03-28 08:33:18', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(6, '2026-03-28 08:35:48', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(7, '2026-03-28 08:35:57', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(8, '2026-03-28 12:26:21', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(9, '2026-03-28 14:50:57', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(10, '2026-03-28 15:19:33', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(11, '2026-03-30 07:17:11', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(12, '2026-03-30 13:35:19', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(13, '2026-03-31 05:21:17', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web');

-- --------------------------------------------------------

--
-- Table structure for table `breeding_progress`
--

CREATE TABLE `breeding_progress` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `officer_id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `month_number` tinyint(4) NOT NULL,
  `ai_count` int(11) DEFAULT 0,
  `pd_count` int(11) DEFAULT 0,
  `calving_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `breeding_progress`
--

INSERT INTO `breeding_progress` (`id`, `range_id`, `officer_id`, `year`, `month_number`, `ai_count`, `pd_count`, `calving_count`, `created_at`) VALUES
(1, 1, 1, 2026, 1, 5, 8, 6, '2026-03-28 14:08:34'),
(2, 1, 2, 2025, 5, 5, 7, 7, '2026-03-28 14:31:54'),
(3, 1, 1, 2026, 1, 7, 9, 0, '2026-03-28 14:31:54');

-- --------------------------------------------------------

--
-- Table structure for table `breeding_semen_logs`
--

CREATE TABLE `breeding_semen_logs` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_month` tinyint(4) NOT NULL,
  `report_year` int(11) NOT NULL,
  `semen_opening_bal` int(11) NOT NULL DEFAULT 0,
  `semen_received` int(11) NOT NULL DEFAULT 0,
  `semen_used` int(11) NOT NULL DEFAULT 0,
  `semen_spoiled` int(11) NOT NULL DEFAULT 0,
  `ai_performed_count` int(11) NOT NULL DEFAULT 0,
  `revenue_collected_rs` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `breeding_target_templates`
--

CREATE TABLE `breeding_target_templates` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `target_ai` int(11) DEFAULT 0,
  `target_pd` int(11) DEFAULT 0,
  `target_calving` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `breeding_target_templates`
--

INSERT INTO `breeding_target_templates` (`id`, `range_id`, `year`, `designation`, `target_ai`, `target_pd`, `target_calving`) VALUES
(1, 1, 2026, 'Veterinary Surgeon', 300, 100, 500);

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
-- Table structure for table `monthly_production_records`
--

CREATE TABLE `monthly_production_records` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `report_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `office_details`
--

CREATE TABLE `office_details` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `officer_name` varchar(255) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `emp_id` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `registered_date` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `office_details`
--

INSERT INTO `office_details` (`id`, `range_id`, `officer_name`, `designation`, `emp_id`, `contact_number`, `registered_date`, `email`, `status`) VALUES
(1, 1, 'Dr .Mrs L. Dujiththera', 'GVS', '001', NULL, NULL, NULL, 'Active'),
(2, 1, 'Mr A. Sinharasa', 'LDI', '002', NULL, NULL, NULL, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `production_categories`
--

CREATE TABLE `production_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `production_items`
--

CREATE TABLE `production_items` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `unit` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
-- Table structure for table `slaughter_statistics`
--

CREATE TABLE `slaughter_statistics` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_month` tinyint(4) NOT NULL,
  `report_year` int(11) NOT NULL,
  `species` enum('Cattle','Goat','Poultry','Pig','Other') NOT NULL,
  `location_type` enum('Slaughter House','In-Farm') NOT NULL,
  `animal_count` int(11) NOT NULL DEFAULT 0,
  `total_weight_kg` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
(19, 'Ampara veterinary surgeon', 'amp_veterinary@gmail.com', NULL, '$2y$10$tmJDAqL84RjQGr9TxsLdKeZTlIPk0PV5mWSC.RXYg7WqNPygorIhO', 'Ampara Veterinary Surgeon', 'veterinary_surgeon', 1, 1, 1, 'Amparai', 1, '2026-03-31 10:51:17', '2026-03-25 10:58:36');

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
  ADD KEY `fk_range` (`range_id`);

--
-- Indexes for table `assets_immovable`
--
ALTER TABLE `assets_immovable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`);

--
-- Indexes for table `assets_movable`
--
ALTER TABLE `assets_movable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_logs_user` (`user_id`),
  ADD KEY `idx_audit_logs_date` (`log_timestamp`);

--
-- Indexes for table `breeding_progress`
--
ALTER TABLE `breeding_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`),
  ADD KEY `officer_id` (`officer_id`);

--
-- Indexes for table `breeding_semen_logs`
--
ALTER TABLE `breeding_semen_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`);

--
-- Indexes for table `breeding_target_templates`
--
ALTER TABLE `breeding_target_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `range_year_desig` (`range_id`,`year`,`designation`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `monthly_production_records`
--
ALTER TABLE `monthly_production_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `office_details`
--
ALTER TABLE `office_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`);

--
-- Indexes for table `production_categories`
--
ALTER TABLE `production_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `production_items`
--
ALTER TABLE `production_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `regulatory_records`
--
ALTER TABLE `regulatory_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `slaughter_statistics`
--
ALTER TABLE `slaughter_statistics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `assets_immovable`
--
ALTER TABLE `assets_immovable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `assets_movable`
--
ALTER TABLE `assets_movable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `breeding_progress`
--
ALTER TABLE `breeding_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `breeding_semen_logs`
--
ALTER TABLE `breeding_semen_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `breeding_target_templates`
--
ALTER TABLE `breeding_target_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `monthly_production_records`
--
ALTER TABLE `monthly_production_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `office_details`
--
ALTER TABLE `office_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `production_categories`
--
ALTER TABLE `production_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `production_items`
--
ALTER TABLE `production_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `regulatory_records`
--
ALTER TABLE `regulatory_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `slaughter_statistics`
--
ALTER TABLE `slaughter_statistics`
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
-- Constraints for table `assets_immovable`
--
ALTER TABLE `assets_immovable`
  ADD CONSTRAINT `assets_immovable_ibfk_1` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`);

--
-- Constraints for table `assets_movable`
--
ALTER TABLE `assets_movable`
  ADD CONSTRAINT `assets_movable_ibfk_1` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`);

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `breeding_progress`
--
ALTER TABLE `breeding_progress`
  ADD CONSTRAINT `breeding_progress_ibfk_1` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`),
  ADD CONSTRAINT `breeding_progress_ibfk_2` FOREIGN KEY (`officer_id`) REFERENCES `office_details` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `breeding_semen_logs`
--
ALTER TABLE `breeding_semen_logs`
  ADD CONSTRAINT `breeding_semen_logs_ibfk_1` FOREIGN KEY (`range_id`) REFERENCES `users` (`range_id`);

--
-- Constraints for table `breeding_target_templates`
--
ALTER TABLE `breeding_target_templates`
  ADD CONSTRAINT `fk_target_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`);

--
-- Constraints for table `monthly_production_records`
--
ALTER TABLE `monthly_production_records`
  ADD CONSTRAINT `monthly_production_records_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `production_items` (`id`);

--
-- Constraints for table `office_details`
--
ALTER TABLE `office_details`
  ADD CONSTRAINT `office_details_ibfk_1` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`);

--
-- Constraints for table `production_items`
--
ALTER TABLE `production_items`
  ADD CONSTRAINT `production_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `production_categories` (`id`);

--
-- Constraints for table `regulatory_records`
--
ALTER TABLE `regulatory_records`
  ADD CONSTRAINT `regulatory_records_ibfk_1` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`),
  ADD CONSTRAINT `regulatory_records_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `slaughter_statistics`
--
ALTER TABLE `slaughter_statistics`
  ADD CONSTRAINT `slaughter_statistics_ibfk_1` FOREIGN KEY (`range_id`) REFERENCES `users` (`range_id`);

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
