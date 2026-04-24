-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 21, 2026 at 08:48 AM
-- Server version: 5.7.44-48
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sltdigi2_daph`
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
  `occurrence_count` int(11) DEFAULT '0',
  `vaccine_name` varchar(100) DEFAULT NULL,
  `doses` int(11) DEFAULT '0',
  `treatment_details` text,
  `report_status` enum('Draft','Submitted','Approved') DEFAULT 'Submitted',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
  `description` text,
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
  `log_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `role` varchar(30) DEFAULT NULL,
  `action_type` varchar(20) NOT NULL,
  `module_name` varchar(100) DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text,
  `new_values` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_info` text,
  `remarks` text
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
(13, '2026-03-31 05:21:17', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(14, '2026-03-31 12:56:40', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(15, '2026-04-02 06:04:39', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(16, '2026-04-03 09:22:12', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(17, '2026-04-04 07:02:40', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(18, '2026-04-07 12:31:50', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(19, '2026-04-07 13:00:07', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(20, '2026-04-08 04:16:22', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(21, '2026-04-10 07:06:44', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(22, '2026-04-10 07:29:59', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(23, '2026-04-16 18:27:22', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '175.157.184.2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(24, '2026-04-21 13:46:21', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '124.43.8.234', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web');

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
  `ai_count` int(11) DEFAULT '0',
  `pd_count` int(11) DEFAULT '0',
  `calving_count` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `breeding_progress`
--

INSERT INTO `breeding_progress` (`id`, `range_id`, `officer_id`, `year`, `month_number`, `ai_count`, `pd_count`, `calving_count`, `created_at`) VALUES
(1, 1, 1, '2026', 1, 5, 8, 6, '2026-03-28 14:08:34'),
(2, 1, 2, '2025', 5, 5, 7, 7, '2026-03-28 14:31:54'),
(3, 1, 1, '2026', 1, 7, 9, 0, '2026-03-28 14:31:54');

-- --------------------------------------------------------

--
-- Table structure for table `breeding_target_templates`
--

CREATE TABLE `breeding_target_templates` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `target_ai` int(11) DEFAULT '0',
  `target_pd` int(11) DEFAULT '0',
  `target_calving` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `breeding_target_templates`
--

INSERT INTO `breeding_target_templates` (`id`, `range_id`, `year`, `designation`, `target_ai`, `target_pd`, `target_calving`) VALUES
(1, 1, '2026', 'Veterinary Surgeon', 300, 100, 500);

-- --------------------------------------------------------

--
-- Table structure for table `dairy_hub_records`
--

CREATE TABLE `dairy_hub_records` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `collection_date` date NOT NULL,
  `farmer_reg_no` varchar(255) NOT NULL,
  `milk_quantity_liters` decimal(10,2) NOT NULL,
  `fat_percentage` decimal(4,2) DEFAULT '0.00',
  `snf_percentage` decimal(4,2) DEFAULT '0.00',
  `price_per_liter` decimal(10,2) DEFAULT '0.00',
  `total_amount` decimal(15,2) GENERATED ALWAYS AS ((`milk_quantity_liters` * `price_per_liter`)) STORED,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `dairy_hub_records`
--

INSERT INTO `dairy_hub_records` (`id`, `range_id`, `collection_date`, `farmer_reg_no`, `milk_quantity_liters`, `fat_percentage`, `snf_percentage`, `price_per_liter`, `created_by`, `created_at`) VALUES
(1, 1, '2026-04-03', '001', 4000.00, 10.00, 2.00, 200.00, 19, '2026-04-03 13:13:30');

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
-- Table structure for table `livestock_targets`
--

CREATE TABLE `livestock_targets` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `target_year` int(11) NOT NULL,
  `annual_target_value` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `livestock_targets`
--

INSERT INTO `livestock_targets` (`id`, `range_id`, `item_id`, `target_year`, `annual_target_value`) VALUES
(1, 1, 1, 2026, 5000.00),
(2, 1, 1, 2026, 50000.00),
(3, 1, 2, 2026, 15000.00),
(4, 1, 3, 2026, 2000.00),
(5, 1, 4, 2026, 12000.00),
(6, 1, 5, 2026, 10000.00),
(7, 1, 6, 2026, 5000.00),
(8, 1, 7, 2026, 8000.00),
(9, 1, 8, 2026, 4000.00),
(10, 1, 9, 2026, 4000.00),
(11, 1, 10, 2026, 6000.00),
(12, 1, 11, 2026, 15000.00),
(13, 1, 12, 2026, 500.00),
(14, 1, 13, 2026, 1000.00),
(15, 1, 14, 2026, 250.00),
(16, 1, 15, 2026, 100.00),
(17, 1, 16, 2026, 3000.00),
(18, 1, 17, 2026, 100000.00),
(19, 1, 18, 2026, 25000.00),
(20, 1, 19, 2026, 8000.00),
(21, 1, 20, 2026, 500.00),
(22, 1, 21, 2026, 300.00),
(23, 1, 22, 2026, 10000.00),
(24, 1, 23, 2026, 50.00),
(25, 1, 24, 2026, 5000.00),
(26, 1, 25, 2026, 100.00),
(27, 1, 26, 2026, 150.00),
(28, 1, 27, 2026, 200.00),
(29, 1, 28, 2026, 15000.00),
(30, 1, 29, 2026, 20000.00),
(31, 1, 30, 2026, 35000.00);

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
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `monthly_production_records`
--

INSERT INTO `monthly_production_records` (`id`, `range_id`, `item_id`, `amount`, `report_date`, `created_at`) VALUES
(1, 1, 1, 300.00, '2026-03-01', '2026-03-31 15:05:48'),
(2, 1, 4, 2000.00, '2026-04-01', '2026-04-01 17:23:04'),
(3, 1, 3, 300.00, '2026-04-01', '2026-04-02 13:20:45');

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
(2, 1, 'Mr A. Sinharasa', 'LDI', '002', NULL, NULL, NULL, 'Active'),
(3, 1, 'Mrs P. Amirthalingam', 'LDO', '003', '0771234560', '2026-01-10', 'amirthalingam.p@daph.gov.lk', 'Active'),
(4, 1, 'Mrs S. Vimalathasan', 'PDO', '004', '0771234561', '2026-01-12', 'vimalathasan.s@daph.gov.lk', 'Active'),
(5, 1, 'Mrs K. Muruhathasan', 'PDO', '005', '0771234562', '2026-01-15', 'muruhathasan.k@daph.gov.lk', 'Active'),
(6, 1, 'Mrs K. Yoganathan', 'PDO', '006', '0771234563', '2026-01-18', 'yoganathan.k@daph.gov.lk', 'Active'),
(7, 1, 'Mrs S. Thiruganasuntharam', 'CDO', '007', '0771234564', '2026-01-20', 'thiruganas@daph.gov.lk', 'Active'),
(8, 1, 'Mr N. Koneswaran', 'PDO', '008', '0771234565', '2026-01-22', 'koneswaran.n@daph.gov.lk', 'Active'),
(9, 1, 'Mr T. Saththiyawan', 'Driver', '009', '0771234566', '2026-02-01', 'saththi@daph.gov.lk', 'Active'),
(10, 1, 'Mr N. Gaminiraj', 'Watcher', '010', '0771234567', '2026-02-05', NULL, 'Active'),
(11, 1, 'Mr K. Perera', 'LDI', '011', '0771234568', '2026-02-10', 'perera.k@daph.gov.lk', 'Active'),
(12, 1, 'Mrs J. Logitharajah', 'Clerk', '012', '0771234569', '2026-02-12', 'logi.j@daph.gov.lk', 'Active'),
(13, 1, 'Mr R. Rajeshwaran', 'LDO', '013', '0771234570', '2026-02-15', 'rajesh.r@daph.gov.lk', 'Active'),
(14, 1, 'Mrs H. Silva', 'PDO', '022', '0771234579', '2026-03-12', 'silva.h@daph.gov.lk', 'Active'),
(15, 1, 'test ', 'GVS', '045', '0778439871', '2026-04-10', 'dept@pannalaps.lk', 'Active'),
(16, 1, 'test ', 'GVS', '045', '0778439871', '2026-04-10', 'dept@pannalaps.lk', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `production_categories`
--

CREATE TABLE `production_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `production_categories`
--

INSERT INTO `production_categories` (`id`, `category_name`, `sort_order`) VALUES
(1, 'Milk Production (Total)', 1),
(2, 'Milk Production (Formal)', 2),
(3, 'Milk Production (Informal) ', 3),
(4, 'Egg Production ', 4),
(5, 'Meat Production ', 5),
(6, 'Other Production Details', 6),
(7, 'Poultry Feed Production ', 7);

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

--
-- Dumping data for table `production_items`
--

INSERT INTO `production_items` (`id`, `category_id`, `item_name`, `unit`) VALUES
(1, 1, 'Cow Milk Production', 'L'),
(2, 1, 'Buffaloe Milk Production', 'L'),
(3, 1, 'Goat Milk Production', 'L'),
(4, 2, 'Nestle', 'L'),
(5, 2, 'MILCO', 'L'),
(6, 2, 'Other Private Collectors', 'L'),
(7, 2, 'Dairy Cooperatives', 'L'),
(8, 2, 'Palwatha', 'L'),
(9, 2, 'Kothmale', 'L'),
(10, 2, 'Cargills', 'L'),
(11, 3, 'Local Consumption', 'L'),
(12, 3, 'Ice cream Production', 'L'),
(13, 3, 'Milk lolly Production', 'Nos'),
(14, 3, 'Milk Toffee Production', 'Kg'),
(15, 3, 'Ghee Production', 'Kg'),
(16, 3, 'Curd Production', 'Nos'),
(17, 4, 'Egg Production (Total)', 'Nos'),
(18, 5, 'Chicken meat Production', 'Kg'),
(19, 5, 'Beef Production', 'Kg'),
(20, 5, 'Mutton Production', 'Kg'),
(21, 5, 'Pork Production', 'Kg'),
(22, 6, 'Silage Production', 'Kg'),
(23, 6, 'Bio Gas Production', 'Unit'),
(24, 6, 'Fertilizer production', 'Kg'),
(25, 6, 'Pasture Production', 'Acres'),
(26, 6, 'Fodder Production', 'Kg'),
(27, 6, 'Pasture Land Utilization', 'Acres'),
(28, 7, 'Starter Feed Production', 'Kg'),
(29, 7, 'Grower Feed Production', 'Kg'),
(30, 7, 'Layer Feed Production', 'Kg');

-- --------------------------------------------------------

--
-- Table structure for table `projects_progress`
--

CREATE TABLE `projects_progress` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `project_type` enum('PSDG','LMP','CBG','Special','Other') NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `summary` text,
  `location` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `progress_percent` int(3) DEFAULT '0',
  `status` enum('Planned','In Progress','On Hold','Completed') DEFAULT 'Planned',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `projects_progress`
--

INSERT INTO `projects_progress` (`id`, `range_id`, `project_type`, `project_name`, `summary`, `location`, `start_date`, `end_date`, `priority`, `progress_percent`, `status`, `created_at`) VALUES
(1, 1, 'PSDG', 'PSDG', 'This is for vaccination of animals in the area', 'uppuveli', '2026-04-04', '2026-04-11', 'Low', 70, 'In Progress', '2026-04-04 11:05:26'),
(2, 1, 'PSDG', 'PSDG', '', 'Batticola', '2026-04-04', '2026-04-07', 'Medium', 50, 'In Progress', '2026-04-04 11:10:03'),
(3, 1, 'CBG', 'CBG', 'Vaccination Programme', 'Batticola', '2026-04-04', '2026-04-07', 'Medium', 10, 'In Progress', '2026-04-04 11:11:27'),
(4, 1, 'LMP', 'LMP', 'no', 'Batticola', '2026-04-04', '2026-04-07', 'High', 30, 'In Progress', '2026-04-04 11:16:05');

-- --------------------------------------------------------

--
-- Table structure for table `project_assignments`
--

CREATE TABLE `project_assignments` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `officer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `project_assignments`
--

INSERT INTO `project_assignments` (`id`, `project_id`, `officer_id`) VALUES
(1, 1, 1),
(2, 1, 5),
(3, 3, 1),
(4, 3, 2),
(5, 4, 5);

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
  `details` text,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `semen_logs`
--

CREATE TABLE `semen_logs` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_month` tinyint(4) NOT NULL,
  `report_year` int(11) NOT NULL,
  `species` varchar(50) NOT NULL,
  `opening_balance` int(11) DEFAULT '0',
  `received_qty` int(11) DEFAULT '0',
  `used_qty` int(11) DEFAULT '0',
  `issued_qty` int(11) DEFAULT '0',
  `spoiled_qty` int(11) DEFAULT '0',
  `paid_amount` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `semen_logs`
--

INSERT INTO `semen_logs` (`id`, `range_id`, `report_month`, `report_year`, `species`, `opening_balance`, `received_qty`, `used_qty`, `issued_qty`, `spoiled_qty`, `paid_amount`, `created_at`) VALUES
(1, 1, 1, 2026, 'Buffalo', 50, 30, 10, 0, 0, 1000.00, '2026-04-03 11:33:01'),
(3, 1, 2, 2026, 'Poultry', 60, 10, 20, 10, 0, 2000.00, '2026-04-03 12:03:42'),
(4, 1, 3, 2026, 'Cock', 50, 10, 10, 0, 0, 2000.00, '2026-04-03 12:04:22');

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
  `animal_count` int(11) NOT NULL DEFAULT '0',
  `total_weight_kg` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `slaughter_statistics`
--

INSERT INTO `slaughter_statistics` (`id`, `range_id`, `report_month`, `report_year`, `species`, `location_type`, `animal_count`, `total_weight_kg`, `created_by`, `created_at`) VALUES
(1, 1, 4, 2026, 'Cattle', 'Slaughter House', 30, 3000.00, 19, '2026-04-03 10:06:53'),
(2, 1, 4, 2026, 'Goat', 'In-Farm', 29, 5000.00, 19, '2026-04-03 10:08:03');

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
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `full_name`, `role`, `district_id`, `range_id`, `office_id`, `district`, `is_active`, `last_login`, `created_at`) VALUES
(5, 'yo', 'provinciald2@gmail.com', NULL, 'b62c1853f21bb51f6ce7faca1becc040', 'Provincial Director', 'provincial_director', NULL, NULL, NULL, 'Provincial', 1, '2026-01-03 16:15:19', '2025-12-12 11:30:50'),
(7, 'adminstrator', 'admins@gmail.com', NULL, '$2y$10$nlm7FQcS7mceOa48ZahFTO.DdagUFOjijh5Yl.HNTs4yj2fWBcq/2', 'Admin Login', 'administrator', NULL, NULL, NULL, 'Provincial', 1, '2026-04-21 08:46:21', '2025-12-15 11:32:14'),
(10, 'finance_admin', 'finance@gmail.com', NULL, '$2y$10$pjmgh5Ij1k6tTXpCPuKo3.bxhwYip.D/D33bT4CSm4su2YUYnHlWe', 'Finance admin', 'finance_admin', NULL, NULL, NULL, 'Provincial', 1, '2026-02-16 10:40:11', '2025-12-16 07:42:06'),
(11, 'Planning officer', 'planning@gmail.com', NULL, '$2y$10$xM5nKggJu8OJ5E4AV9n4OOuqJ4L2TUqxfXnBoAV0dBcqycEv2L99W', 'Planning officer', 'planning_officer', NULL, NULL, NULL, 'Provincial', 1, '2026-02-17 13:11:58', '2025-12-16 09:34:59'),
(12, 'Subject Matter Specialist', 'sms@gmail.com', NULL, '$2y$10$M2geolCGKHuoKMn1R1A0x.Qde.C5H7ME3GS.BzQRMAE5gNpA4VmCu', 'Subject Matter Specialist', 'sms', NULL, NULL, NULL, 'Provincial', 1, '2026-02-16 11:03:05', '2025-12-16 11:30:03'),
(13, 'Farms Officer', 'farms@gmail.com', NULL, '$2y$10$yig.Tm9WNcTOZx0wOY5ZzukY9Zp4L1Yf2tmilQWcHM5Rfw3euAyW6', 'Deputy Director (Farms Operation)', 'farms_dd', NULL, NULL, NULL, 'Provincial', 1, '2026-02-16 15:09:31', '2025-12-17 08:46:28'),
(15, 'Training Officer', 'training@gmail.com', NULL, '$2y$10$dK4TD.h0f07IW/xDn.p8GuEW0kIiu2lhXlnYt64SUBeOaeWvIqNNK', 'Training Officer', 'training_officer', NULL, NULL, NULL, 'Provincial', 1, '2026-02-18 16:59:37', '2025-12-17 10:22:46'),
(16, 'District Deputy Director', 'district_dd@gmail.com', NULL, '$2y$10$ktztqj1XUpA6UsNmP2wreuSepNmMZ.cdIAnSuQhhXBcuyjZcmrAQq', 'District Deputy Director', 'district_dd', NULL, NULL, NULL, 'Provincial', 1, '2026-02-18 16:59:48', '2025-12-17 13:23:28'),
(17, 'veterinary surgeon', 'veterinary@gmail.com', NULL, '$2y$10$BuPbuNbGjVvPCb14jXTaBO4lKeuJSaMVVqMBmOlEmnQV2K.8P4B0W', 'veterinary surgeon', 'veterinary_surgeon', 1, 1, NULL, 'Amparai', 1, '2026-03-24 11:08:27', '2025-12-18 10:10:22'),
(18, 'Provincial director', 'provinciald@gmail.com', NULL, '$2y$10$rosK7hcBMssxuPRgI6iqi.CbGiv7bmo7lsM68UAPaRxZR4/uJc37G', 'Provincial Director', 'provincial_director', NULL, NULL, NULL, 'Provincial', 1, '2026-02-13 10:00:42', '2026-01-05 13:18:11'),
(19, 'Ampara veterinary surgeon', 'amp_veterinary@gmail.com', NULL, '$2y$10$tmJDAqL84RjQGr9TxsLdKeZTlIPk0PV5mWSC.RXYg7WqNPygorIhO', 'Ampara Veterinary Surgeon', 'veterinary_surgeon', 1, 1, 1, 'Amparai', 1, '2026-04-04 12:32:40', '2026-03-25 10:58:36');

-- --------------------------------------------------------

--
-- Table structure for table `veterinary_ranges`
--

CREATE TABLE `veterinary_ranges` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `district_id` int(11) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1'
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
-- Indexes for table `breeding_target_templates`
--
ALTER TABLE `breeding_target_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `range_year_desig` (`range_id`,`year`,`designation`);

--
-- Indexes for table `dairy_hub_records`
--
ALTER TABLE `dairy_hub_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `livestock_targets`
--
ALTER TABLE `livestock_targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `range_id` (`range_id`);

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
-- Indexes for table `projects_progress`
--
ALTER TABLE `projects_progress`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project_assignments`
--
ALTER TABLE `project_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `regulatory_records`
--
ALTER TABLE `regulatory_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `semen_logs`
--
ALTER TABLE `semen_logs`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `breeding_progress`
--
ALTER TABLE `breeding_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `breeding_target_templates`
--
ALTER TABLE `breeding_target_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dairy_hub_records`
--
ALTER TABLE `dairy_hub_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `livestock_targets`
--
ALTER TABLE `livestock_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `monthly_production_records`
--
ALTER TABLE `monthly_production_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `office_details`
--
ALTER TABLE `office_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `production_categories`
--
ALTER TABLE `production_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `production_items`
--
ALTER TABLE `production_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `projects_progress`
--
ALTER TABLE `projects_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `project_assignments`
--
ALTER TABLE `project_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `regulatory_records`
--
ALTER TABLE `regulatory_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `semen_logs`
--
ALTER TABLE `semen_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `slaughter_statistics`
--
ALTER TABLE `slaughter_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- Constraints for table `breeding_target_templates`
--
ALTER TABLE `breeding_target_templates`
  ADD CONSTRAINT `fk_target_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`);

--
-- Constraints for table `livestock_targets`
--
ALTER TABLE `livestock_targets`
  ADD CONSTRAINT `livestock_targets_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `production_items` (`id`),
  ADD CONSTRAINT `livestock_targets_ibfk_2` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`);

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
-- Constraints for table `project_assignments`
--
ALTER TABLE `project_assignments`
  ADD CONSTRAINT `project_assignments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects_progress` (`id`) ON DELETE CASCADE;

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
