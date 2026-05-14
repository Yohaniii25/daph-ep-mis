-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 14, 2026 at 12:49 PM
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
-- Table structure for table `advanced_programmes`
--

CREATE TABLE `advanced_programmes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `programme_year` year(4) NOT NULL,
  `place` varchar(255) NOT NULL,
  `activity_description` text DEFAULT NULL,
  `mid_term_status` enum('Pending','Submitted','Approved','Rejected') DEFAULT 'Pending',
  `mid_term_remarks` text DEFAULT NULL,
  `mid_term_approved_at` datetime DEFAULT NULL,
  `final_status` enum('Pending','Submitted','Approved','Rejected') DEFAULT 'Pending',
  `final_remarks` text DEFAULT NULL,
  `final_approved_at` datetime DEFAULT NULL,
  `current_stage` enum('Admin_Draft','PD_MidTerm_Review','Admin_Implementation','PD_Final_Review','Completed') DEFAULT 'Admin_Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `advanced_programmes`
--

INSERT INTO `advanced_programmes` (`id`, `user_id`, `type_id`, `programme_year`, `place`, `activity_description`, `mid_term_status`, `mid_term_remarks`, `mid_term_approved_at`, `final_status`, `final_remarks`, `final_approved_at`, `current_stage`, `created_at`) VALUES
(1, 7, 2, 2026, 'Uppuveli', 'meeting on uppuweli', 'Pending', NULL, NULL, 'Pending', NULL, NULL, 'Admin_Draft', '2026-01-20 11:47:40');

-- --------------------------------------------------------

--
-- Table structure for table `amended_programmes`
--

CREATE TABLE `amended_programmes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `original_id` int(11) DEFAULT NULL,
  `programme_year` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_id` int(11) NOT NULL,
  `place` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activity_description` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amendment_reason` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `amended_programmes`
--

INSERT INTO `amended_programmes` (`id`, `user_id`, `original_id`, `programme_year`, `type_id`, `place`, `activity_description`, `amendment_reason`, `created_at`) VALUES
(2, 7, 1, '2026', 2, 'Uppuveli', 'meeting on uppuweli', 'increase', '2026-04-21 13:32:28');

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
(23, '2026-04-11 18:21:10', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'User logged in via Web'),
(24, '2026-04-12 18:10:08', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(25, '2026-04-12 19:32:13', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(26, '2026-04-13 06:05:33', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(27, '2026-04-15 07:40:56', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(28, '2026-04-16 06:16:56', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(29, '2026-04-16 18:29:35', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(30, '2026-04-17 17:41:14', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(31, '2026-04-21 04:57:00', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(32, '2026-04-21 13:46:49', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(33, '2026-04-22 06:10:50', 20, 'employee', '', 'LOGIN', NULL, '0', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(34, '2026-04-22 06:39:35', 20, 'employee', 'employee', 'LOGIN', NULL, '0', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(35, '2026-04-22 06:59:04', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(36, '2026-04-22 10:17:49', 20, 'employee', 'employee', 'LOGIN', NULL, '0', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(37, '2026-04-22 12:17:33', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(38, '2026-04-22 12:54:39', 20, 'employee', 'employee', 'LOGIN', NULL, '0', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(39, '2026-04-27 07:18:17', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(40, '2026-04-27 07:18:55', 20, 'employee', 'employee', 'LOGIN', NULL, '0', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(41, '2026-04-28 05:50:56', 20, 'employee', 'employee', 'LOGIN', NULL, '0', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(42, '2026-04-28 06:57:23', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(43, '2026-04-28 06:57:42', 20, 'employee', 'employee', 'LOGIN', NULL, '0', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(44, '2026-04-29 05:50:22', 20, 'employee', 'employee', 'LOGIN', NULL, '0', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(45, '2026-04-29 06:03:51', 20, 'employee', 'employee', 'LOGIN', NULL, '0', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(46, '2026-04-29 06:36:21', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36', 'User logged in via Web'),
(47, '2026-04-29 10:00:49', 20, 'employee', 'employee', 'LOGIN', NULL, '0', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(48, '2026-05-06 05:36:40', 16, 'District Deputy Director', 'district_dd', 'LOGIN', NULL, '0', 16, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(49, '2026-05-06 08:53:04', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(50, '2026-05-06 13:13:32', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(51, '2026-05-07 03:55:44', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'User logged in via Web'),
(52, '2026-05-12 12:14:18', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(53, '2026-05-13 06:00:43', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(54, '2026-05-13 06:25:58', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(55, '2026-05-13 06:26:28', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(56, '2026-05-13 06:44:19', 17, 'veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(57, '2026-05-13 06:46:35', 20, 'employee', 'employee', 'LOGIN', NULL, '0', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(58, '2026-05-13 06:53:40', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(59, '2026-05-14 04:59:38', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web');

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
-- Table structure for table `daily_egg_production`
--

CREATE TABLE `daily_egg_production` (
  `id` int(11) NOT NULL,
  `flock_id` int(11) NOT NULL,
  `collection_date` date NOT NULL,
  `egg_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  `fat_percentage` decimal(4,2) DEFAULT 0.00,
  `snf_percentage` decimal(4,2) DEFAULT 0.00,
  `price_per_liter` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) GENERATED ALWAYS AS (`milk_quantity_liters` * `price_per_liter`) STORED,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `dairy_hub_records`
--

INSERT INTO `dairy_hub_records` (`id`, `range_id`, `collection_date`, `farmer_reg_no`, `milk_quantity_liters`, `fat_percentage`, `snf_percentage`, `price_per_liter`, `created_by`, `created_at`) VALUES
(1, 1, '2026-04-03', '001', '4000.00', '10.00', '2.00', '200.00', 19, '2026-04-03 13:13:30');

-- --------------------------------------------------------

--
-- Table structure for table `diary_tasks`
--

CREATE TABLE `diary_tasks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_date` date NOT NULL,
  `place` varchar(255) NOT NULL,
  `activity` text NOT NULL,
  `status` enum('Not Started','Ongoing','Completed') DEFAULT 'Not Started',
  `task_type` enum('Daily','Advanced','Amendment','Annual') DEFAULT 'Daily',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `diary_tasks`
--

INSERT INTO `diary_tasks` (`id`, `user_id`, `task_date`, `place`, `activity`, `status`, `task_type`, `created_at`) VALUES
(1, 7, '2026-04-18', 'Uppuweli', 'meeting on head office', 'Ongoing', 'Daily', '2026-04-16 09:08:55'),
(2, 20, '2026-04-30', 'Uppuveli', 'rererer', 'Ongoing', 'Daily', '2026-04-29 07:12:44');

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
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `sender_name` varchar(255) NOT NULL,
  `sender_email` varchar(150) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message_body` text NOT NULL,
  `received_at` datetime DEFAULT current_timestamp(),
  `status` enum('Pending','Minuted','Replied','Closed') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `inquiries`
--

INSERT INTO `inquiries` (`id`, `sender_name`, `sender_email`, `subject`, `message_body`, `received_at`, `status`) VALUES
(1, 'Yohani Abeykoon', 'yohanii725@gmail.com', 'need fresh milk', 'need fresh milk for market', '2026-04-13 00:23:17', 'Pending'),
(2, 'Uvindu Anurdha', 'danushka@sltds.lk', 'go to the trinco municipal ', 'go and ask about the vaccination programme', '2026-04-13 11:37:47', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `inquiry_logs`
--

CREATE TABLE `inquiry_logs` (
  `id` int(11) NOT NULL,
  `inquiry_id` int(11) NOT NULL,
  `action_type` enum('MINUTE','REPLY') NOT NULL,
  `processed_by` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `request_date` date NOT NULL,
  `start_date` date NOT NULL,
  `resume_date` date NOT NULL,
  `no_of_days` decimal(4,2) NOT NULL,
  `is_half_day` tinyint(1) DEFAULT 0,
  `reason` text NOT NULL,
  `acting_user_id` int(11) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `user_id`, `leave_type`, `request_date`, `start_date`, `resume_date`, `no_of_days`, `is_half_day`, `reason`, `acting_user_id`, `status`, `created_at`) VALUES
(1, 20, 'Casual', '2026-04-29', '2026-04-29', '2026-04-29', '0.50', 1, 'b', 17, 'Pending', '2026-04-29 10:25:55'),
(2, 20, 'Foreign', '2026-04-29', '2026-05-04', '2026-05-05', '2.00', 0, 'fff', 22, 'Approved', '2026-04-29 11:20:25');

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
(1, 1, 1, 2026, '5000.00'),
(2, 1, 1, 2026, '50000.00'),
(3, 1, 2, 2026, '15000.00'),
(4, 1, 3, 2026, '2000.00'),
(5, 1, 4, 2026, '12000.00'),
(6, 1, 5, 2026, '10000.00'),
(7, 1, 6, 2026, '5000.00'),
(8, 1, 7, 2026, '8000.00'),
(9, 1, 8, 2026, '4000.00'),
(10, 1, 9, 2026, '4000.00'),
(11, 1, 10, 2026, '6000.00'),
(12, 1, 11, 2026, '15000.00'),
(13, 1, 12, 2026, '500.00'),
(14, 1, 13, 2026, '1000.00'),
(15, 1, 14, 2026, '250.00'),
(16, 1, 15, 2026, '100.00'),
(17, 1, 16, 2026, '3000.00'),
(18, 1, 17, 2026, '100000.00'),
(19, 1, 18, 2026, '25000.00'),
(20, 1, 19, 2026, '8000.00'),
(21, 1, 20, 2026, '500.00'),
(22, 1, 21, 2026, '300.00'),
(23, 1, 22, 2026, '10000.00'),
(24, 1, 23, 2026, '50.00'),
(25, 1, 24, 2026, '5000.00'),
(26, 1, 25, 2026, '100.00'),
(27, 1, 26, 2026, '150.00'),
(28, 1, 27, 2026, '200.00'),
(29, 1, 28, 2026, '15000.00'),
(30, 1, 29, 2026, '20000.00'),
(31, 1, 30, 2026, '35000.00');

-- --------------------------------------------------------

--
-- Table structure for table `master_programme_types`
--

CREATE TABLE `master_programme_types` (
  `id` int(11) NOT NULL,
  `programme_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `master_programme_types`
--

INSERT INTO `master_programme_types` (`id`, `programme_name`, `is_active`, `created_at`) VALUES
(1, 'Veterinary Office Correspondence', 1, '2026-04-20 06:48:51'),
(2, 'Cattle Farm Visit', 1, '2026-04-20 07:09:26'),
(3, 'Poultry From Visit', 1, '2026-04-20 07:10:07'),
(4, 'Buffalo Farm Visit', 1, '2026-04-20 07:11:11'),
(5, 'Others (goat, Rabbit, Swine, etc)', 1, '2026-04-20 07:13:59'),
(6, 'Pregnancy Diagnosis', 1, '2026-04-21 12:53:07'),
(7, 'Disease Investigation', 1, '2026-04-21 12:53:07'),
(8, 'Project Follow-up', 1, '2026-04-21 12:53:07'),
(9, 'Meetings', 1, '2026-04-21 12:53:07'),
(10, 'Training Programs', 1, '2026-04-21 12:53:07'),
(11, 'Mobile Clinic', 1, '2026-04-21 12:53:07'),
(12, 'Field Days', 1, '2026-04-21 12:53:07'),
(13, 'Dairy Hub', 1, '2026-04-21 12:53:07'),
(14, 'Special Vaccination Program', 1, '2026-04-21 12:53:07'),
(15, 'Animal Identification Program', 1, '2026-04-21 12:53:07'),
(16, 'Farm Registration Program', 1, '2026-04-21 12:53:07'),
(17, 'Project Implementation', 1, '2026-04-21 12:53:07'),
(18, 'Disaster Management', 1, '2026-04-21 12:53:07'),
(19, 'Infertility Investigation Program', 1, '2026-04-21 12:53:07'),
(20, 'Specified Tasks', 1, '2026-04-21 12:53:07');

-- --------------------------------------------------------

--
-- Table structure for table `master_units`
--

CREATE TABLE `master_units` (
  `id` int(11) NOT NULL,
  `unit_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `master_units`
--

INSERT INTO `master_units` (`id`, `unit_name`) VALUES
(1, 'Veterinary'),
(2, 'Administration'),
(3, 'Employee'),
(4, 'Farm Operations'),
(5, 'Finance'),
(6, 'Training Centres'),
(7, 'Other');

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

--
-- Dumping data for table `monthly_production_records`
--

INSERT INTO `monthly_production_records` (`id`, `range_id`, `item_id`, `amount`, `report_date`, `created_at`) VALUES
(1, 1, 1, '300.00', '2026-03-01', '2026-03-31 15:05:48'),
(2, 1, 4, '2000.00', '2026-04-01', '2026-04-01 17:23:04'),
(3, 1, 3, '300.00', '2026-04-01', '2026-04-02 13:20:45');

-- --------------------------------------------------------

--
-- Table structure for table `office_details`
--

CREATE TABLE `office_details` (
  `id` int(11) NOT NULL,
  `range_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
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

INSERT INTO `office_details` (`id`, `range_id`, `unit_id`, `officer_name`, `designation`, `emp_id`, `contact_number`, `registered_date`, `email`, `status`) VALUES
(1, 1, 1, 'Dr .Mrs L. Dujiththera', 'GVS', '001', NULL, NULL, NULL, 'Active'),
(2, 1, 1, 'Mr A. Sinharasa', 'LDI', '002', NULL, NULL, NULL, 'Active'),
(3, 1, 1, 'Mrs P. Amirthalingam', 'LDO', '003', '0771234560', '2026-01-10', 'amirthalingam.p@daph.gov.lk', 'Active'),
(4, 1, 1, 'Mrs S. Vimalathasan', 'PDO', '004', '0771234561', '2026-01-12', 'vimalathasan.s@daph.gov.lk', 'Active'),
(5, 1, 1, 'Mrs K. Muruhathasan', 'PDO', '005', '0771234562', '2026-01-15', 'muruhathasan.k@daph.gov.lk', 'Active'),
(6, 1, 1, 'Mrs K. Yoganathan', 'PDO', '006', '0771234563', '2026-01-18', 'yoganathan.k@daph.gov.lk', 'Active'),
(7, 1, 1, 'Mrs S. Thiruganasuntharam', 'CDO', '007', '0771234564', '2026-01-20', 'thiruganas@daph.gov.lk', 'Active'),
(8, 1, 1, 'Mr N. Koneswaran', 'PDO', '008', '0771234565', '2026-01-22', 'koneswaran.n@daph.gov.lk', 'Active'),
(9, 1, 1, 'Mr T. Saththiyawan', 'Driver', '009', '0771234566', '2026-02-01', 'saththi@daph.gov.lk', 'Active'),
(10, 1, 1, 'Mr N. Gaminiraj', 'Watcher', '010', '0771234567', '2026-02-05', NULL, 'Active'),
(11, 1, 1, 'Mr K. Perera', 'LDI', '011', '0771234568', '2026-02-10', 'perera.k@daph.gov.lk', 'Active'),
(12, 1, 1, 'Mrs J. Logitharajah', 'Clerk', '012', '0771234569', '2026-02-12', 'logi.j@daph.gov.lk', 'Active'),
(13, 1, 1, 'Mr R. Rajeshwaran', 'LDO', '013', '0771234570', '2026-02-15', 'rajesh.r@daph.gov.lk', 'Active'),
(14, 1, 1, 'Mrs H. Silva', 'PDO', '022', '0771234579', '2026-03-12', 'silva.h@daph.gov.lk', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `parent_stock_flocks`
--

CREATE TABLE `parent_stock_flocks` (
  `id` int(11) NOT NULL,
  `flock_code` varchar(50) NOT NULL,
  `region` varchar(100) NOT NULL,
  `current_count` int(11) NOT NULL DEFAULT 0,
  `assigned_cages` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `parent_stock_flocks`
--

INSERT INTO `parent_stock_flocks` (`id`, `flock_code`, `region`, `current_count`, `assigned_cages`) VALUES
(1, 'SAT-CB-2026-01', 'Sathurukondan', 0, 'C-01, C-02'),
(2, 'THM-CB-2026-02', 'Thampalakamam', 0, 'B-05'),
(3, 'TRK-HB-2026-01', 'Thirukkovil', 0, 'A-10');

-- --------------------------------------------------------

--
-- Table structure for table `production_categories`
--

CREATE TABLE `production_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `sort_order` int(11) DEFAULT 0
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
  `summary` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `progress_percent` int(3) DEFAULT 0,
  `status` enum('Planned','In Progress','On Hold','Completed') DEFAULT 'Planned',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
  `details` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `opening_balance` int(11) DEFAULT 0,
  `received_qty` int(11) DEFAULT 0,
  `used_qty` int(11) DEFAULT 0,
  `issued_qty` int(11) DEFAULT 0,
  `spoiled_qty` int(11) DEFAULT 0,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `semen_logs`
--

INSERT INTO `semen_logs` (`id`, `range_id`, `report_month`, `report_year`, `species`, `opening_balance`, `received_qty`, `used_qty`, `issued_qty`, `spoiled_qty`, `paid_amount`, `created_at`) VALUES
(1, 1, 1, 2026, 'Buffalo', 50, 30, 10, 0, 0, '1000.00', '2026-04-03 11:33:01'),
(3, 1, 2, 2026, 'Poultry', 60, 10, 20, 10, 0, '2000.00', '2026-04-03 12:03:42'),
(4, 1, 3, 2026, 'Cock', 50, 10, 10, 0, 0, '2000.00', '2026-04-03 12:04:22');

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

--
-- Dumping data for table `slaughter_statistics`
--

INSERT INTO `slaughter_statistics` (`id`, `range_id`, `report_month`, `report_year`, `species`, `location_type`, `animal_count`, `total_weight_kg`, `created_by`, `created_at`) VALUES
(1, 1, 4, 2026, 'Cattle', 'Slaughter House', 30, '3000.00', 19, '2026-04-03 10:06:53'),
(2, 1, 4, 2026, 'Goat', 'In-Farm', 29, '5000.00', 19, '2026-04-03 10:08:03');

-- --------------------------------------------------------

--
-- Table structure for table `stock_balance_logs`
--

CREATE TABLE `stock_balance_logs` (
  `id` int(11) NOT NULL,
  `flock_id` int(11) NOT NULL,
  `newly_added` int(11) DEFAULT 0,
  `culling` int(11) DEFAULT 0,
  `log_date` timestamp NOT NULL DEFAULT current_timestamp()
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
(7, 'adminstrator', 'admins@gmail.com', NULL, '$2y$10$nlm7FQcS7mceOa48ZahFTO.DdagUFOjijh5Yl.HNTs4yj2fWBcq/2', 'Admin Login', NULL, NULL, 'administrator', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-05-13 11:55:58', '2025-12-15 11:32:14', NULL),
(10, 'finance_admin', 'finance@gmail.com', NULL, '$2y$10$pjmgh5Ij1k6tTXpCPuKo3.bxhwYip.D/D33bT4CSm4su2YUYnHlWe', 'Finance admin', NULL, NULL, 'finance_admin', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-02-16 10:40:11', '2025-12-16 07:42:06', NULL),
(11, 'Planning officer', 'planning@gmail.com', NULL, '$2y$10$xM5nKggJu8OJ5E4AV9n4OOuqJ4L2TUqxfXnBoAV0dBcqycEv2L99W', 'Planning officer', NULL, NULL, 'planning_officer', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-02-17 13:11:58', '2025-12-16 09:34:59', NULL),
(12, 'Subject Matter Specialist', 'sms@gmail.com', NULL, '$2y$10$M2geolCGKHuoKMn1R1A0x.Qde.C5H7ME3GS.BzQRMAE5gNpA4VmCu', 'Subject Matter Specialist', NULL, NULL, 'sms', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-02-16 11:03:05', '2025-12-16 11:30:03', NULL),
(13, 'Farms Officer', 'farms@gmail.com', NULL, '$2y$10$yig.Tm9WNcTOZx0wOY5ZzukY9Zp4L1Yf2tmilQWcHM5Rfw3euAyW6', 'Deputy Director (Farms Operation)', NULL, NULL, 'farms_dd', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-05-14 10:29:38', '2025-12-17 08:46:28', NULL),
(15, 'Training Officer', 'training@gmail.com', NULL, '$2y$10$dK4TD.h0f07IW/xDn.p8GuEW0kIiu2lhXlnYt64SUBeOaeWvIqNNK', 'Training Officer', NULL, NULL, 'training_officer', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-02-18 16:59:37', '2025-12-17 10:22:46', NULL),
(16, 'District Deputy Director', 'district_dd@gmail.com', NULL, '$2y$10$ktztqj1XUpA6UsNmP2wreuSepNmMZ.cdIAnSuQhhXBcuyjZcmrAQq', 'District Deputy Director', NULL, NULL, 'district_dd', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-05-06 11:06:40', '2025-12-17 13:23:28', NULL),
(17, 'veterinary surgeon', 'veterinary@gmail.com', NULL, '$2y$10$BuPbuNbGjVvPCb14jXTaBO4lKeuJSaMVVqMBmOlEmnQV2K.8P4B0W', 'veterinary surgeon', NULL, NULL, 'veterinary_surgeon', 1, 1, NULL, NULL, NULL, 'Amparai', 1, '2026-05-13 12:14:19', '2025-12-18 10:10:22', NULL),
(18, 'Provincial director', 'provinciald@gmail.com', NULL, '$2y$10$rosK7hcBMssxuPRgI6iqi.CbGiv7bmo7lsM68UAPaRxZR4/uJc37G', 'Provincial Director', NULL, NULL, 'provincial_director', NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-02-13 10:00:42', '2026-01-05 13:18:11', NULL),
(19, 'Ampara veterinary surgeon', 'amp_veterinary@gmail.com', NULL, '$2y$10$tmJDAqL84RjQGr9TxsLdKeZTlIPk0PV5mWSC.RXYg7WqNPygorIhO', 'Ampara Veterinary Surgeon', NULL, NULL, 'veterinary_surgeon', 1, 1, NULL, NULL, 1, 'Amparai', 1, '2026-04-04 12:32:40', '2026-03-25 10:58:36', NULL),
(20, 'employee', 'emp@gmail.com', NULL, '$2y$10$ITeSMQXxM8Ciwu4KK/Sy2O7ai30xUjP8yrL1WNRzXlNnsrG8ylfZK', 'Test Employee', NULL, NULL, 'employee', NULL, 1, NULL, NULL, NULL, 'Amparai', 1, '2026-05-13 12:16:35', '2026-04-22 06:10:30', 'profile_20_1777526035.png'),
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
-- Indexes for table `advanced_programmes`
--
ALTER TABLE `advanced_programmes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `amended_programmes`
--
ALTER TABLE `amended_programmes`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `daily_egg_production`
--
ALTER TABLE `daily_egg_production`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_flock_date` (`flock_id`,`collection_date`);

--
-- Indexes for table `dairy_hub_records`
--
ALTER TABLE `dairy_hub_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `diary_tasks`
--
ALTER TABLE `diary_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inquiry_logs`
--
ALTER TABLE `inquiry_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inquiry_id` (`inquiry_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_leave_user` (`user_id`);

--
-- Indexes for table `livestock_targets`
--
ALTER TABLE `livestock_targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `range_id` (`range_id`);

--
-- Indexes for table `master_programme_types`
--
ALTER TABLE `master_programme_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `programme_name` (`programme_name`);

--
-- Indexes for table `master_units`
--
ALTER TABLE `master_units`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `range_id` (`range_id`),
  ADD KEY `fk_office_unit` (`unit_id`);

--
-- Indexes for table `parent_stock_flocks`
--
ALTER TABLE `parent_stock_flocks`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `stock_balance_logs`
--
ALTER TABLE `stock_balance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `flock_id` (`flock_id`);

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
-- AUTO_INCREMENT for table `advanced_programmes`
--
ALTER TABLE `advanced_programmes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `amended_programmes`
--
ALTER TABLE `amended_programmes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

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
-- AUTO_INCREMENT for table `daily_egg_production`
--
ALTER TABLE `daily_egg_production`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dairy_hub_records`
--
ALTER TABLE `dairy_hub_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `diary_tasks`
--
ALTER TABLE `diary_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inquiry_logs`
--
ALTER TABLE `inquiry_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `livestock_targets`
--
ALTER TABLE `livestock_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `master_programme_types`
--
ALTER TABLE `master_programme_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `master_units`
--
ALTER TABLE `master_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `monthly_production_records`
--
ALTER TABLE `monthly_production_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `office_details`
--
ALTER TABLE `office_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `parent_stock_flocks`
--
ALTER TABLE `parent_stock_flocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- AUTO_INCREMENT for table `stock_balance_logs`
--
ALTER TABLE `stock_balance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

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
-- Constraints for table `daily_egg_production`
--
ALTER TABLE `daily_egg_production`
  ADD CONSTRAINT `daily_egg_production_ibfk_1` FOREIGN KEY (`flock_id`) REFERENCES `parent_stock_flocks` (`id`);

--
-- Constraints for table `inquiry_logs`
--
ALTER TABLE `inquiry_logs`
  ADD CONSTRAINT `inquiry_logs_ibfk_1` FOREIGN KEY (`inquiry_id`) REFERENCES `inquiries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inquiry_logs_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `office_details` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `fk_leave_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_office_unit` FOREIGN KEY (`unit_id`) REFERENCES `master_units` (`id`),
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
-- Constraints for table `stock_balance_logs`
--
ALTER TABLE `stock_balance_logs`
  ADD CONSTRAINT `stock_balance_logs_ibfk_1` FOREIGN KEY (`flock_id`) REFERENCES `parent_stock_flocks` (`id`);

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
