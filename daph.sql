-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 02, 2026 at 09:49 AM
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
  `range_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `task` text NOT NULL,
  `place` varchar(255) NOT NULL,
  `distance` decimal(6,2) NOT NULL DEFAULT 0.00,
  `time_duration` varchar(100) NOT NULL COMMENT 'e.g., 2 hours, 08:30-10:30'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `advanced_programmes`
--

INSERT INTO `advanced_programmes` (`id`, `range_id`, `date`, `task`, `place`, `distance`, `time_duration`) VALUES
(3, 1, '2026-07-10', 'test', 'uppuveli', '0.00', '09.00 am - 11.00 am'),
(4, 1, '2026-07-10', 'test', 'uppuveli', '0.00', '09.00 am - 11.00 am'),
(5, 1, '2026-07-23', 'Test Advanced Programme', 'uppuveli', '0.00', '09.00 am - 11.00 am');

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
(3, 1, '2026-03-30', 'AMP-009', 'Cattle', 'Fever', 3, 'FMD', 3, 'come only after having breakfast', 'Submitted', 19, '2026-03-30 13:50:27', '2026-03-30 13:50:27'),
(4, 1, '2026-07-06', 'EP/AMP/VET/1001', 'Cattle', 'Foot and Mouth Disease', 50, 'FMD', 50, 'Test Remark', 'Submitted', 17, '2026-07-06 09:04:28', '2026-07-06 09:04:28');

-- --------------------------------------------------------

--
-- Table structure for table `animal_populations`
--

CREATE TABLE `animal_populations` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `animal_type` enum('Cow','Buffalo','Goat','Chicken','Pig','Others') NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `animal_populations`
--

INSERT INTO `animal_populations` (`id`, `range_id`, `year`, `animal_type`, `quantity`, `updated_at`) VALUES
(1, 1, 2024, 'Cow', 3950, '2026-07-04 11:35:24'),
(2, 1, 2024, 'Buffalo', 1450, '2026-07-04 11:35:24'),
(3, 1, 2024, 'Goat', 2320, '2026-07-04 11:35:24'),
(4, 1, 2024, 'Chicken', 18700, '2026-07-04 11:35:24'),
(5, 1, 2024, 'Pig', 850, '2026-07-04 11:35:24'),
(6, 1, 2024, 'Others', 440, '2026-07-04 11:35:24'),
(7, 1, 2025, 'Cow', 4000, '2026-07-06 11:24:22'),
(8, 1, 2025, 'Buffalo', 1600, '2026-07-04 11:35:24'),
(9, 1, 2025, 'Goat', 2550, '2026-07-04 11:35:24'),
(10, 1, 2025, 'Chicken', 21000, '2026-07-04 11:35:24'),
(11, 1, 2025, 'Pig', 930, '2026-07-04 11:35:24'),
(12, 1, 2025, 'Others', 500, '2026-07-04 11:35:24'),
(13, 1, 2026, 'Cow', 3000, '2026-07-09 08:14:24'),
(14, 1, 2026, 'Buffalo', 1030, '2026-07-09 08:26:37'),
(15, 1, 2026, 'Goat', 7000, '2026-07-22 09:22:42'),
(16, 1, 2026, 'Chicken', 9000, '2026-07-22 09:20:45'),
(17, 1, 2026, 'Pig', 1030, '2026-07-04 11:35:24'),
(18, 1, 2026, 'Others', 570, '2026-07-04 11:35:24');

-- --------------------------------------------------------

--
-- Table structure for table `annual_feed_production`
--

CREATE TABLE `annual_feed_production` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `feed_mill_name` varchar(255) NOT NULL,
  `proprietor_details` text DEFAULT NULL,
  `category_type` varchar(50) NOT NULL,
  `produced_qty_mt_month` decimal(12,2) DEFAULT 0.00,
  `raw_materials_source` varchar(255) DEFAULT NULL,
  `market_outlets` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `annual_feed_production`
--

INSERT INTO `annual_feed_production` (`id`, `district_id`, `range_id`, `report_year`, `feed_mill_name`, `proprietor_details`, `category_type`, `produced_qty_mt_month`, `raw_materials_source`, `market_outlets`, `created_by`, `created_at`) VALUES
(3, 1, 1, 2026, 'Test edited', 'Test Address edited', 'pig', '6000.00', 'Test', 'Test', 19, '2026-07-22 09:08:47');

-- --------------------------------------------------------

--
-- Table structure for table `annual_livestock_societies`
--

CREATE TABLE `annual_livestock_societies` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `gn_division` varchar(150) DEFAULT NULL,
  `name_and_address` text NOT NULL,
  `overall_objective` varchar(255) DEFAULT NULL,
  `total_members` int(11) DEFAULT 0,
  `registration_no` varchar(100) DEFAULT NULL,
  `registration_department` varchar(150) DEFAULT NULL,
  `major_activities` text DEFAULT NULL,
  `has_financial_records` enum('Yes','No') DEFAULT 'No' COMMENT 'State whether audited financial statement maintained',
  `regulated_by` varchar(150) DEFAULT NULL,
  `contact_no` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `annual_milk_collecting_centers`
--

CREATE TABLE `annual_milk_collecting_centers` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `center_name` varchar(200) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_no` varchar(50) DEFAULT NULL,
  `collection_lit_month` decimal(12,2) DEFAULT 0.00,
  `chilling_capacity_lit` decimal(12,2) DEFAULT 0.00,
  `milk_supply_to` varchar(200) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `annual_milk_processing_centers`
--

CREATE TABLE `annual_milk_processing_centers` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `center_name` varchar(200) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_no` varchar(50) DEFAULT NULL,
  `yoghurt_lit_month` decimal(10,2) DEFAULT 0.00,
  `curd_lit_month` decimal(10,2) DEFAULT 0.00,
  `ice_cream_lit_month` decimal(10,2) DEFAULT 0.00,
  `ghee_lit_month` decimal(10,2) DEFAULT 0.00,
  `other_products_lit_month` decimal(10,2) DEFAULT 0.00,
  `income_rs_month` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `annual_milk_sales_centers`
--

CREATE TABLE `annual_milk_sales_centers` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `sales_center_name` varchar(200) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_no` varchar(50) DEFAULT NULL,
  `fresh_milk_lit_month` decimal(10,2) DEFAULT 0.00,
  `yoghurt_lit_month` decimal(10,2) DEFAULT 0.00,
  `curd_lit_month` decimal(10,2) DEFAULT 0.00,
  `ice_cream_lit_month` decimal(10,2) DEFAULT 0.00,
  `ghee_lit_month` decimal(10,2) DEFAULT 0.00,
  `other_products_lit_month` decimal(10,2) DEFAULT 0.00,
  `income_rs_month` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `annual_pasture_fodder_lands`
--

CREATE TABLE `annual_pasture_fodder_lands` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `pasture_fam_quarter_ac` int(11) DEFAULT 0,
  `pasture_fam_half_ac` int(11) DEFAULT 0,
  `pasture_fam_one_ac` int(11) DEFAULT 0,
  `pasture_fam_gt_one_ac` int(11) DEFAULT 0,
  `pasture_total_acres` decimal(10,2) DEFAULT 0.00,
  `fodder_fam_quarter_ac` int(11) DEFAULT 0,
  `fodder_fam_half_ac` int(11) DEFAULT 0,
  `fodder_fam_one_ac` int(11) DEFAULT 0,
  `fodder_fam_gt_one_ac` int(11) DEFAULT 0,
  `fodder_total_acres` decimal(10,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `annual_pasture_fodder_lands`
--

INSERT INTO `annual_pasture_fodder_lands` (`id`, `district_id`, `range_id`, `report_year`, `pasture_fam_quarter_ac`, `pasture_fam_half_ac`, `pasture_fam_one_ac`, `pasture_fam_gt_one_ac`, `pasture_total_acres`, `fodder_fam_quarter_ac`, `fodder_fam_half_ac`, `fodder_fam_one_ac`, `fodder_fam_gt_one_ac`, `fodder_total_acres`, `created_by`, `created_at`) VALUES
(1, 1, 1, 2026, 4, 0, 4, 0, '0.00', 4, 0, 0, 4, '0.00', 19, '2026-07-13 13:58:41');

-- --------------------------------------------------------

--
-- Table structure for table `annual_pasture_yields`
--

CREATE TABLE `annual_pasture_yields` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `co3_kg_year` decimal(12,2) DEFAULT 0.00,
  `co4_kg_year` decimal(12,2) DEFAULT 0.00,
  `co5_kg_year` decimal(12,2) DEFAULT 0.00,
  `australian_red_nepier_kg_year` decimal(12,2) DEFAULT 0.00,
  `super_nepier_kg_year` decimal(12,2) DEFAULT 0.00,
  `sampoorna_kg_year` decimal(12,2) DEFAULT 0.00,
  `other_varieties_kg_year` decimal(12,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `annual_pasture_yields`
--

INSERT INTO `annual_pasture_yields` (`id`, `district_id`, `range_id`, `report_year`, `co3_kg_year`, `co4_kg_year`, `co5_kg_year`, `australian_red_nepier_kg_year`, `super_nepier_kg_year`, `sampoorna_kg_year`, `other_varieties_kg_year`, `created_by`, `created_at`) VALUES
(1, 1, 1, 2026, '4.00', '7.00', '0.00', '9.00', '0.00', '9.00', '0.00', 19, '2026-07-13 14:02:57'),
(3, 1, 1, 2025, '8.00', '7.00', '9.00', '9.00', '4.00', '7.00', '8.00', 19, '2026-07-22 09:06:07');

-- --------------------------------------------------------

--
-- Table structure for table `annual_producers_processors`
--

CREATE TABLE `annual_producers_processors` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `chick_producers_count` int(11) DEFAULT 0,
  `chicks_produced_month` int(11) DEFAULT 0,
  `feed_producers_count` int(11) DEFAULT 0,
  `feed_production_mt_month` decimal(10,2) DEFAULT 0.00,
  `poultry_processors_count` int(11) DEFAULT 0,
  `chicken_sale_live_kg_month` decimal(12,2) DEFAULT 0.00,
  `chicken_sale_dressed_kg_month` decimal(12,2) DEFAULT 0.00,
  `organic_fert_farm_families` int(11) DEFAULT 0,
  `organic_fert_prod_mt_year` decimal(10,2) DEFAULT 0.00,
  `organic_fert_sale_kg_month` decimal(12,2) DEFAULT 0.00,
  `organic_fert_own_use_kg_month` decimal(12,2) DEFAULT 0.00,
  `organic_fert_price_rs_kg` decimal(10,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `annual_producers_processors`
--

INSERT INTO `annual_producers_processors` (`id`, `district_id`, `range_id`, `report_year`, `chick_producers_count`, `chicks_produced_month`, `feed_producers_count`, `feed_production_mt_month`, `poultry_processors_count`, `chicken_sale_live_kg_month`, `chicken_sale_dressed_kg_month`, `organic_fert_farm_families`, `organic_fert_prod_mt_year`, `organic_fert_sale_kg_month`, `organic_fert_own_use_kg_month`, `organic_fert_price_rs_kg`, `created_by`, `created_at`) VALUES
(1, 1, 1, 2026, 8, 8, 8, '9.00', 8, '0.00', '8.00', 8, '0.00', '8.00', '9.00', '8.00', 19, '2026-07-21 12:12:27'),
(2, 1, 1, 2025, 9, 6, 0, '0.00', 0, '0.00', '0.00', 0, '0.00', '0.00', '0.00', '0.00', 19, '2026-07-22 08:49:18'),
(3, 1, 1, 2024, 80, 60, 0, '0.00', 0, '0.00', '0.00', 70, '600.00', '8.00', '99.00', '80.00', 19, '2026-07-22 09:07:26');

-- --------------------------------------------------------

--
-- Table structure for table `annual_production_levels`
--

CREATE TABLE `annual_production_levels` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `cow_milk_lit_day` decimal(10,2) DEFAULT 0.00,
  `buffalo_milk_lit_day` decimal(10,2) DEFAULT 0.00,
  `goat_milk_lit_day` decimal(10,2) DEFAULT 0.00,
  `chicks_production_no_day` int(11) DEFAULT 0,
  `eggs_production_no_day` int(11) DEFAULT 0,
  `beef_kg_day` decimal(10,2) DEFAULT 0.00,
  `mutton_kg_day` decimal(10,2) DEFAULT 0.00,
  `chicken_kg_day` decimal(10,2) DEFAULT 0.00,
  `curd_lit_day` decimal(10,2) DEFAULT 0.00,
  `ghee_lit_day` decimal(10,2) DEFAULT 0.00,
  `yoghurt_lit_day` decimal(10,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `annual_production_levels`
--

INSERT INTO `annual_production_levels` (`id`, `district_id`, `range_id`, `report_year`, `cow_milk_lit_day`, `buffalo_milk_lit_day`, `goat_milk_lit_day`, `chicks_production_no_day`, `eggs_production_no_day`, `beef_kg_day`, `mutton_kg_day`, `chicken_kg_day`, `curd_lit_day`, `ghee_lit_day`, `yoghurt_lit_day`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2026, '7.00', '8.00', '9.00', 0, 50, '7.00', '6.00', '8.00', '4.00', '8.00', '9.00', 19, '2026-07-13 13:58:06', '2026-07-21 12:04:03');

-- --------------------------------------------------------

--
-- Table structure for table `annual_vaccination_targets`
--

CREATE TABLE `annual_vaccination_targets` (
  `id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `assigned_vaccinator_id` int(11) DEFAULT NULL,
  `target_fmd` int(11) DEFAULT 0,
  `target_bq` int(11) DEFAULT 0,
  `target_hs` int(11) DEFAULT 0,
  `available_ldo_count` int(11) DEFAULT 0,
  `allocated_ldo_target` int(11) DEFAULT 0,
  `casual_vaccinators_needed` int(11) DEFAULT 0,
  `allocated_man_days` int(11) DEFAULT 0,
  `syringes_10cc_req` int(11) DEFAULT 0,
  `needles_14g_dozen_req` int(11) DEFAULT 0,
  `fuel_liters_per_month` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `animal_type` enum('Cow','Buffalo','Goat','Chicken','Pig','Others') NOT NULL DEFAULT 'Others'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `annual_vaccination_targets`
--

INSERT INTO `annual_vaccination_targets` (`id`, `year`, `range_id`, `assigned_vaccinator_id`, `target_fmd`, `target_bq`, `target_hs`, `available_ldo_count`, `allocated_ldo_target`, `casual_vaccinators_needed`, `allocated_man_days`, `syringes_10cc_req`, `needles_14g_dozen_req`, `fuel_liters_per_month`, `created_at`, `updated_at`, `animal_type`) VALUES
(1, 2026, 1, 5, 8, 6, 6, 4, 8, 7, 10, 5, 5, '5.00', '2026-07-06 10:42:28', '2026-07-09 08:24:51', 'Others'),
(6, 2026, 1, 4, 0, 0, 0, 0, 0, 1, 0, 0, 0, '0.00', '2026-07-06 13:11:59', '2026-07-09 08:06:08', 'Cow'),
(7, 2026, 1, 4, 0, 0, 0, 0, 0, 1, 0, 0, 0, '0.00', '2026-07-09 08:26:37', '2026-07-09 08:26:37', 'Buffalo'),
(8, 2026, 1, 3, 0, 0, 0, 0, 0, 1, 0, 0, 0, '0.00', '2026-07-09 08:26:53', '2026-07-09 08:26:53', 'Chicken'),
(9, 2026, 1, 3, 7, 9, 9, 70, 70, 1, 8, 9, 0, '0.00', '2026-07-22 09:22:42', '2026-07-22 09:23:04', 'Goat');

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
(59, '2026-05-14 04:59:38', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(60, '2026-05-14 11:40:22', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(61, '2026-05-15 03:33:45', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(62, '2026-05-18 10:26:58', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(63, '2026-05-18 11:52:29', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(64, '2026-05-18 12:25:49', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(65, '2026-05-18 12:25:54', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(66, '2026-05-18 12:26:16', 20, 'employee', 'employee', 'LOGIN', NULL, '0', 20, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(67, '2026-05-18 12:27:07', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(68, '2026-05-19 03:22:57', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(69, '2026-05-19 10:28:44', 15, 'Training Officer', 'training_officer', 'LOGIN', NULL, '0', 15, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(70, '2026-05-19 10:28:55', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(71, '2026-05-19 11:54:07', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(72, '2026-05-20 04:39:45', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(73, '2026-05-20 04:58:06', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(74, '2026-05-20 06:13:39', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(75, '2026-05-21 11:06:18', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(76, '2026-05-21 11:06:50', 7, 'adminstrator', 'administrator', 'LOGIN', NULL, '0', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(77, '2026-05-25 14:52:03', 13, 'Farms Officer', 'farms_dd', 'LOGIN', NULL, '0', 13, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(78, '2026-05-25 14:52:45', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(79, '2026-05-26 06:45:07', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(80, '2026-05-26 13:44:40', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(81, '2026-05-27 04:42:36', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(82, '2026-05-27 08:38:13', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(83, '2026-05-27 13:05:48', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(84, '2026-05-28 05:04:52', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(85, '2026-05-31 09:26:27', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(86, '2026-06-01 04:46:39', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(87, '2026-06-02 05:50:09', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(88, '2026-06-02 08:38:10', 15, 'Training Officer', 'training_officer', 'LOGIN', NULL, '0', 15, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(89, '2026-06-02 12:46:37', 15, 'Training Officer', 'training_officer', 'LOGIN', NULL, '0', 15, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(90, '2026-06-03 04:21:15', 15, 'Training Officer', 'training_officer', 'LOGIN', NULL, '0', 15, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(91, '2026-06-03 08:59:52', 17, 'veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(92, '2026-06-03 09:12:38', 15, 'Training Officer', 'training_officer', 'LOGIN', NULL, '0', 15, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', 'User logged in via Web'),
(93, '2026-06-08 06:48:24', 15, 'Training Officer', 'training_officer', 'LOGIN', NULL, '0', 15, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in via Web'),
(94, '2026-06-08 09:46:52', 15, 'Training Officer', 'training_officer', 'LOGIN', NULL, '0', 15, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in via Web'),
(95, '2026-06-09 11:54:00', 15, 'Training Officer', 'training_officer', 'LOGIN', NULL, '0', 15, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in via Web'),
(96, '2026-06-09 12:47:22', 10, 'finance_admin', 'finance_admin', 'LOGIN', NULL, '0', 10, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in via Web'),
(97, '2026-06-09 12:47:56', 16, 'District Deputy Director', 'district_dd', 'LOGIN', NULL, '0', 16, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in via Web'),
(98, '2026-06-10 10:34:04', 10, 'finance_admin', 'finance_admin', 'LOGIN', NULL, '0', 10, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in via Web'),
(99, '2026-06-10 10:35:02', 11, 'Planning officer', 'planning_officer', 'LOGIN', NULL, '0', 11, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in via Web'),
(100, '2026-06-10 11:48:35', 15, 'Training Officer', 'training_officer', 'LOGIN', NULL, '0', 15, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in via Web'),
(101, '2026-06-10 13:07:48', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in via Web'),
(102, '2026-06-16 10:02:19', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in via Web'),
(103, '2026-06-16 12:44:46', 18, 'Provincial director', 'provincial_director', 'LOGIN', NULL, '0', 18, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in via Web'),
(104, '2026-06-17 06:42:14', 17, 'veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in via Web'),
(105, '2026-06-17 06:47:54', 18, 'Provincial director', 'provincial_director', 'LOGIN', NULL, '0', 18, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in via Web'),
(106, '2026-06-17 07:04:03', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in via Web'),
(107, '2026-06-22 06:25:11', 17, 'veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in via Web'),
(108, '2026-06-22 10:17:31', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in with context: range_veterinary_officer'),
(109, '2026-06-22 11:03:07', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in with context: range_veterinary_officer'),
(110, '2026-06-22 12:15:58', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in with context: range_veterinary_officer'),
(111, '2026-06-22 12:17:18', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in with context: range_veterinary_officer'),
(112, '2026-06-24 10:46:25', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in with context: range_veterinary_officer'),
(113, '2026-06-24 13:55:05', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in with context: range_veterinary_officer'),
(114, '2026-06-24 14:06:16', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in with context: range_veterinary_officer'),
(115, '2026-06-25 02:31:54', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in with context: range_veterinary_officer'),
(116, '2026-06-30 10:12:07', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in with context: range_veterinary_officer'),
(117, '2026-06-30 14:48:36', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in with context: range_veterinary_officer'),
(118, '2026-06-30 15:02:47', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in with context: range_veterinary_officer'),
(119, '2026-07-01 04:25:29', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0', 'User logged in with context: range_veterinary_officer'),
(120, '2026-07-04 08:41:17', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(121, '2026-07-04 12:30:24', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(122, '2026-07-06 06:16:14', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(123, '2026-07-06 09:02:24', 17, 'veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'User logged in with context: range_veterinary_officer'),
(124, '2026-07-06 10:44:37', 17, 'veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'User logged in with context: range_veterinary_officer'),
(125, '2026-07-06 10:47:16', 17, 'veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'User logged in with context: range_veterinary_officer'),
(126, '2026-07-06 13:23:49', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(127, '2026-07-06 13:30:41', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '124.43.8.234', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in via Web'),
(128, '2026-07-07 06:33:42', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '124.43.8.234', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in via Web'),
(129, '2026-07-07 07:29:04', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '124.43.8.234', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in via Web'),
(130, '2026-07-07 07:47:58', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '124.43.8.234', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in via Web'),
(131, '2026-07-07 07:49:25', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '124.43.8.234', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in via Web'),
(132, '2026-07-07 08:20:56', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '124.43.8.234', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in via Web'),
(133, '2026-07-09 06:28:31', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '124.43.8.234', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in via Web'),
(134, '2026-07-10 09:43:13', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(135, '2026-07-13 04:32:40', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(136, '2026-07-13 04:44:12', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(137, '2026-07-13 05:18:53', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(138, '2026-07-13 06:03:52', 12, 'Subject Matter Specialist', 'sms', 'LOGIN', NULL, '0', 12, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: subject_matter_specialist'),
(139, '2026-07-13 06:35:34', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(140, '2026-07-13 10:08:55', 17, 'veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'User logged in with context: range_veterinary_officer'),
(141, '2026-07-13 11:30:58', 17, 'veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'User logged in with context: range_veterinary_officer'),
(142, '2026-07-13 13:45:24', 17, 'veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 17, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', 'User logged in with context: range_veterinary_officer'),
(143, '2026-07-13 14:41:49', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(144, '2026-07-20 06:38:31', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(145, '2026-07-20 07:32:51', 45, 'regionalfarms', 'farms_dd', 'LOGIN', NULL, '0', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: regional_farms'),
(146, '2026-07-20 08:50:12', 45, 'regionalfarms', 'farms_dd', 'LOGIN', NULL, '0', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: regional_farms'),
(147, '2026-07-20 10:03:15', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(148, '2026-07-20 10:06:05', 45, 'regionalfarms', 'farms_dd', 'LOGIN', NULL, '0', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: regional_farms'),
(149, '2026-07-20 14:45:23', 45, 'regionalfarms', 'farms_dd', 'LOGIN', NULL, '0', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: regional_farms'),
(150, '2026-07-20 15:20:23', 45, 'regionalfarms', 'farms_dd', 'LOGIN', NULL, '0', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: regional_farms'),
(151, '2026-07-21 10:33:21', 45, 'regionalfarms', 'farms_dd', 'LOGIN', NULL, '0', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: regional_farms'),
(152, '2026-07-21 11:38:36', 45, 'regionalfarms', 'farms_dd', 'LOGIN', NULL, '0', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: regional_farms'),
(153, '2026-07-21 12:03:06', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(154, '2026-07-22 07:08:01', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(155, '2026-07-22 08:37:03', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(156, '2026-07-22 08:59:14', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(157, '2026-07-23 06:17:40', 45, 'regionalfarms', 'farms_dd', 'LOGIN', NULL, '0', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: regional_farms'),
(158, '2026-07-27 04:47:42', 45, 'regionalfarms', 'farms_dd', 'LOGIN', NULL, '0', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: regional_farms'),
(159, '2026-07-27 08:45:03', 45, 'regionalfarms', 'farms_dd', 'LOGIN', NULL, '0', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: regional_farms'),
(160, '2026-07-28 04:38:12', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(161, '2026-07-28 05:58:08', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(162, '2026-07-28 05:58:50', 19, 'Ampara veterinary surgeon', 'veterinary_surgeon', 'LOGIN', NULL, '0', 19, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: range_veterinary_officer'),
(163, '2026-07-28 11:11:22', 45, 'regionalfarms', 'farms_dd', 'LOGIN', NULL, '0', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', 'User logged in with context: regional_farms');

-- --------------------------------------------------------

--
-- Table structure for table `breeding_ai_performance`
--

CREATE TABLE `breeding_ai_performance` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `report_month` tinyint(4) NOT NULL COMMENT '1 to 12',
  `technician_code` varchar(50) DEFAULT NULL,
  `ai_date` date NOT NULL,
  `cow_id` varchar(50) NOT NULL,
  `semen_code` varchar(50) NOT NULL,
  `ai_type` varchar(50) DEFAULT NULL COMMENT 'Stores the sub-category ticked under Type of AI',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `breeding_ai_performance`
--

INSERT INTO `breeding_ai_performance` (`id`, `range_id`, `report_year`, `report_month`, `technician_code`, `ai_date`, `cow_id`, `semen_code`, `ai_type`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 2026, 1, 'Test', '2026-07-13', 'Test', 'Test', 'Repeat', 19, '2026-07-13 10:09:00', '2026-07-13 10:09:00'),
(2, 1, 2026, 1, 'TECH/T1', '2026-01-10', 'COW/TEST01', 'SEMEN/S01', 'First Service', 17, '2026-07-13 10:10:06', '2026-07-13 10:10:06'),
(3, 1, 2026, 1, 'Test 02', '2026-07-22', 'Test Cow ID', 'Test Record', 'Repeat', 19, '2026-07-22 09:48:48', '2026-07-22 09:49:16');

-- --------------------------------------------------------

--
-- Table structure for table `breeding_calving_performance`
--

CREATE TABLE `breeding_calving_performance` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `report_month` tinyint(4) NOT NULL COMMENT '1 to 12',
  `technician_code` varchar(50) DEFAULT NULL,
  `ai_date` date DEFAULT NULL,
  `semen_code` varchar(50) DEFAULT NULL,
  `cow_id` varchar(50) NOT NULL,
  `calf_id` varchar(50) NOT NULL,
  `calving_date` date NOT NULL,
  `calf_sex` enum('M','F') NOT NULL COMMENT 'M = Male, F = Female',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `breeding_calving_performance`
--

INSERT INTO `breeding_calving_performance` (`id`, `range_id`, `report_year`, `report_month`, `technician_code`, `ai_date`, `semen_code`, `cow_id`, `calf_id`, `calving_date`, `calf_sex`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 2026, 7, 'Test', '2026-07-14', 'Test', 'Test', 'test', '2026-07-16', 'M', 19, '2026-07-13 10:10:00', '2026-07-13 10:10:00'),
(2, 1, 2026, 1, 'Test', '2026-07-22', 'Test record', 'Test Cow ID', 'Test Calf ID', '2026-07-23', 'M', 19, '2026-07-22 09:52:07', '2026-07-22 09:52:25');

-- --------------------------------------------------------

--
-- Table structure for table `breeding_pd_performance`
--

CREATE TABLE `breeding_pd_performance` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `report_month` tinyint(4) NOT NULL COMMENT '1 to 12',
  `vs_tech_code` varchar(50) DEFAULT NULL,
  `ai_date` date DEFAULT NULL,
  `cow_id` varchar(50) NOT NULL,
  `pd_date` date NOT NULL,
  `result` enum('P','NP') NOT NULL COMMENT 'P = Pregnant, NP = Not Pregnant',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `breeding_pd_performance`
--

INSERT INTO `breeding_pd_performance` (`id`, `range_id`, `report_year`, `report_month`, `vs_tech_code`, `ai_date`, `cow_id`, `pd_date`, `result`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 2026, 5, 'Test', '2026-07-15', 'test', '2026-07-21', 'P', 19, '2026-07-13 10:09:31', '2026-07-13 10:09:31');

-- --------------------------------------------------------

--
-- Table structure for table `building_inventories`
--

CREATE TABLE `building_inventories` (
  `id` int(11) NOT NULL,
  `land_asset_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `inventory_item` varchar(255) NOT NULL,
  `specification` text DEFAULT NULL,
  `current_condition` enum('Excellent','Good','Fair (Needs Service)','Critical Failure','Damaged') NOT NULL,
  `available_quantity` int(11) NOT NULL DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `building_inventories`
--

INSERT INTO `building_inventories` (`id`, `land_asset_id`, `user_id`, `inventory_item`, `specification`, `current_condition`, `available_quantity`, `remarks`, `is_active`, `created_at`) VALUES
(1, 2, 19, 'AC', '2', 'Excellent', 1, '2', 1, '2026-06-30 13:36:51'),
(2, 4, 19, 'AC', '2', 'Excellent', 6, 'test', 1, '2026-07-07 07:53:25');

-- --------------------------------------------------------

--
-- Table structure for table `cages`
--

CREATE TABLE `cages` (
  `id` int(11) NOT NULL,
  `cage_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `cages`
--

INSERT INTO `cages` (`id`, `cage_name`, `created_at`) VALUES
(1, 'Cage A', '2026-07-20 10:23:18'),
(2, 'Cage B', '2026-07-20 10:23:18'),
(3, 'Cage C', '2026-07-20 10:23:18'),
(4, 'Cage D', '2026-07-20 10:23:18'),
(5, 'Cage G', '2026-07-27 08:50:08');

-- --------------------------------------------------------

--
-- Table structure for table `cash_book_summaries`
--

CREATE TABLE `cash_book_summaries` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `report_month` tinyint(4) NOT NULL COMMENT '1 to 12 for Jan to Dec',
  `item_name` varchar(255) NOT NULL COMMENT 'e.g., Consultation fee, Day old chicks, Semen straws',
  `quantity_sold` int(11) NOT NULL DEFAULT 0,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount_deposited` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `cash_book_summaries`
--

INSERT INTO `cash_book_summaries` (`id`, `district_id`, `range_id`, `report_year`, `report_month`, `item_name`, `quantity_sold`, `unit_price`, `total_amount`, `amount_deposited`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2026, 6, 'Wound ress', 2, '100.00', '200.00', '200.00', 19, '2026-07-13 05:20:01', '2026-07-13 05:20:01'),
(2, 1, 1, 2026, 1, 'Wound ress', 90, '70.00', '6300.00', '6300.00', 19, '2026-07-22 09:36:21', '2026-07-22 09:36:43');

-- --------------------------------------------------------

--
-- Table structure for table `casual_vaccinator_deployments`
--

CREATE TABLE `casual_vaccinator_deployments` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `nic_no` varchar(20) NOT NULL,
  `range_id` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `casual_vaccinator_deployments`
--

INSERT INTO `casual_vaccinator_deployments` (`id`, `full_name`, `nic_no`, `range_id`, `year`) VALUES
(2, 'Uvindu Rathnayaka', '4', 1, 2026),
(3, 'Yohani Abeykoon', '4', 1, 2026),
(4, 'Lakmi Uresha', '19876543219', 1, 2026);

-- --------------------------------------------------------

--
-- Table structure for table `cattle_voucher_usage`
--

CREATE TABLE `cattle_voucher_usage` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `report_month` tinyint(4) NOT NULL COMMENT '1 to 12 for Jan to Dec',
  `opening_balance` int(11) NOT NULL DEFAULT 0 COMMENT 'Nos. at the Beginning of the month',
  `received_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'Nos. Received During the month',
  `used_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'Nos. used During The Month',
  `spoilt_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'Nos. Spoilt/Damaged',
  `transferred_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'Transfer to Another Range',
  `closing_balance` int(11) NOT NULL DEFAULT 0 COMMENT 'Balance at the End of the Month',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `cattle_voucher_usage`
--

INSERT INTO `cattle_voucher_usage` (`id`, `district_id`, `range_id`, `report_year`, `report_month`, `opening_balance`, `received_qty`, `used_qty`, `spoilt_qty`, `transferred_qty`, `closing_balance`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2026, 8, 300, 100, 50, 5, 15, 330, 17, '2026-07-13 08:24:37', '2026-07-13 08:24:37'),
(2, 1, 1, 2026, 6, 700, 600, 500, 100, 200, 500, 19, '2026-07-13 08:25:22', '2026-07-13 08:25:22'),
(3, 1, 1, 2026, 1, 0, 90, 60, 20, 10, 0, 19, '2026-07-22 09:45:37', '2026-07-22 09:45:37');

-- --------------------------------------------------------

--
-- Table structure for table `chicks_death_details`
--

CREATE TABLE `chicks_death_details` (
  `id` int(11) NOT NULL,
  `record_month` date NOT NULL COMMENT 'Store the first day of the month, e.g., 2026-05-01 for May 2026',
  `batch_no` varchar(255) NOT NULL COMMENT 'e.g., Kadaknath 10, CPRS-19, 817',
  `deaths` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `chicks_death_details`
--

INSERT INTO `chicks_death_details` (`id`, `record_month`, `batch_no`, `deaths`, `created_at`) VALUES
(1, '2026-07-01', 'CRPS-23', 60, '2026-07-23 11:18:33'),
(2, '2026-06-01', 'CRPS-23', 80, '2026-07-23 11:18:51');

-- --------------------------------------------------------

--
-- Table structure for table `chick_growth_log`
--

CREATE TABLE `chick_growth_log` (
  `id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `cage_id` int(11) NOT NULL,
  `opening_chicks_count` int(11) NOT NULL DEFAULT 0,
  `no_of_deaths` int(11) NOT NULL DEFAULT 0,
  `feed_type` varchar(255) DEFAULT NULL,
  `feed_amount_to_be_given` decimal(10,2) NOT NULL DEFAULT 0.00,
  `feed_amount_given` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vaccination_treatment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `chick_growth_log`
--

INSERT INTO `chick_growth_log` (`id`, `record_date`, `cage_id`, `opening_chicks_count`, `no_of_deaths`, `feed_type`, `feed_amount_to_be_given`, `feed_amount_given`, `vaccination_treatment`, `created_at`) VALUES
(1, '2026-07-27', 2, 80, 10, 'Test', '80.00', '800.00', 'Vaccine Test', '2026-07-27 09:06:18');

-- --------------------------------------------------------

--
-- Table structure for table `counterfoil_assets`
--

CREATE TABLE `counterfoil_assets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `counterfoil_type` varchar(150) NOT NULL,
  `current_condition` varchar(100) NOT NULL,
  `available_quantity` int(11) NOT NULL DEFAULT 1,
  `purchase_date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `counterfoil_assets`
--

INSERT INTO `counterfoil_assets` (`id`, `user_id`, `district_id`, `range_id`, `counterfoil_type`, `current_condition`, `available_quantity`, `purchase_date`, `remarks`, `is_active`, `created_at`) VALUES
(1, 19, 1, 1, 'TEST', 'Half-Used', 1, '0000-00-00', 'TEST', 0, '2026-06-30 14:43:19'),
(2, 19, 1, 1, 'Test', 'Half-Used', 100, '0000-00-00', 'Note', 1, '2026-07-07 07:58:52');

-- --------------------------------------------------------

--
-- Table structure for table `crop_returns`
--

CREATE TABLE `crop_returns` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `report_month` tinyint(4) NOT NULL COMMENT '1 to 12 for Jan to Dec',
  `item_name` varchar(255) NOT NULL,
  `balance_previous_month` int(11) NOT NULL DEFAULT 0,
  `received_current_month` int(11) NOT NULL DEFAULT 0,
  `issued_current_month` int(11) NOT NULL DEFAULT 0,
  `balance_current_month` int(11) NOT NULL DEFAULT 0,
  `remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `crop_returns`
--

INSERT INTO `crop_returns` (`id`, `district_id`, `range_id`, `report_year`, `report_month`, `item_name`, `balance_previous_month`, `received_current_month`, `issued_current_month`, `balance_current_month`, `remark`, `created_at`, `updated_at`) VALUES
(6, 1, 1, 2026, 6, 'BQ', 0, 0, 0, 0, '', '2026-07-11 10:26:46', '2026-07-11 10:26:46'),
(7, 1, 1, 2026, 1, 'Test', 70, 70, 70, 10, 'edit remark', '2026-07-22 09:29:02', '2026-07-22 09:29:20');

-- --------------------------------------------------------

--
-- Table structure for table `daily_egg_production`
--

CREATE TABLE `daily_egg_production` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `cage_id` int(11) NOT NULL,
  `collection_date` date NOT NULL,
  `pullets` int(11) DEFAULT 0,
  `cockerels` int(11) DEFAULT 0,
  `total_eggs` int(11) DEFAULT 0,
  `total_eggs_kg` decimal(10,2) DEFAULT 0.00,
  `hatchable_eggs` int(11) DEFAULT 0,
  `hatchable_eggs_kg` decimal(10,2) DEFAULT 0.00,
  `table_eggs` int(11) DEFAULT 0,
  `table_eggs_kg` decimal(10,2) DEFAULT 0.00,
  `cracked_eggs` int(11) DEFAULT 0,
  `cracked_eggs_kg` decimal(10,2) DEFAULT 0.00,
  `loading_date` date DEFAULT NULL,
  `hatchery_name` varchar(255) DEFAULT NULL,
  `eggs_loaded` int(11) DEFAULT 0,
  `hatching_date` date DEFAULT NULL,
  `hatched_eggs` int(11) DEFAULT 0,
  `hatchability_percentage` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `daily_egg_production`
--

INSERT INTO `daily_egg_production` (`id`, `batch_id`, `cage_id`, `collection_date`, `pullets`, `cockerels`, `total_eggs`, `total_eggs_kg`, `hatchable_eggs`, `hatchable_eggs_kg`, `table_eggs`, `table_eggs_kg`, `cracked_eggs`, `cracked_eggs_kg`, `loading_date`, `hatchery_name`, `eggs_loaded`, `hatching_date`, `hatched_eggs`, `hatchability_percentage`, `created_at`) VALUES
(4, 8, 2, '2026-07-01', 89, 60, 210, '2000.00', 70, '600.00', 70, '700.00', 70, '700.00', NULL, 'Test', 90, '2026-07-23', 80, '88.89', '2026-07-23 07:18:51'),
(5, 8, 1, '2026-01-23', 90, 80, 20, '1900.00', 8, '700.00', 6, '600.00', 6, '600.00', NULL, 'Test', 8, '2026-07-24', 8, '100.00', '2026-07-23 08:34:12'),
(6, 8, 3, '2026-01-23', 9, 9, 220, '2200.00', 60, '600.00', 80, '800.00', 80, '800.00', '0000-00-00', 'Test', 220, '2026-07-24', 200, '90.91', '2026-07-23 09:10:44'),
(7, 8, 1, '2026-07-01', 0, 0, 100, '1000.00', 100, '1000.00', 0, '0.00', 0, '0.00', NULL, NULL, 0, NULL, 0, '0.00', '2026-07-23 10:26:51'),
(8, 8, 3, '2026-07-01', 0, 0, 100, '1000.00', 100, '1000.00', 0, '0.00', 0, '0.00', NULL, NULL, 0, NULL, 0, '0.00', '2026-07-23 10:27:07'),
(9, 8, 4, '2026-07-01', 0, 0, 80, '800.00', 80, '800.00', 0, '0.00', 0, '0.00', NULL, NULL, 0, NULL, 0, '0.00', '2026-07-23 10:27:14'),
(10, 10, 1, '2026-07-27', 0, 0, 970, '9700.00', 80, '800.00', 90, '900.00', 800, '8000.00', NULL, NULL, 0, NULL, 0, '0.00', '2026-07-27 08:52:59');

-- --------------------------------------------------------

--
-- Table structure for table `daily_egg_sales_returns`
--

CREATE TABLE `daily_egg_sales_returns` (
  `id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `hatchery_return_no` int(11) DEFAULT 0,
  `hatchery_return_kg` decimal(10,2) DEFAULT 0.00,
  `total_sales_no` int(11) DEFAULT 0,
  `total_sales_kg` decimal(10,2) DEFAULT 0.00,
  `balance_no` int(11) DEFAULT 0,
  `balance_kg` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `daily_egg_sales_returns`
--

INSERT INTO `daily_egg_sales_returns` (`id`, `record_date`, `hatchery_return_no`, `hatchery_return_kg`, `total_sales_no`, `total_sales_kg`, `balance_no`, `balance_kg`, `created_at`) VALUES
(1, '2026-07-23', 7, '9.00', 9, '8.00', 0, '0.00', '2026-07-23 09:06:24'),
(2, '2026-01-23', 80, '800.00', 10, '1000.00', 0, '0.00', '2026-07-23 09:07:59'),
(3, '2026-07-01', 900, '9000.00', 800, '8000.00', 0, '0.00', '2026-07-23 09:08:46'),
(5, '2026-07-27', 90, '900.00', 90, '1000.00', 0, '0.00', '2026-07-27 08:52:28');

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
-- Table structure for table `day_old_chicks_distribution`
--

CREATE TABLE `day_old_chicks_distribution` (
  `id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `no_of_chicks_produced` int(11) NOT NULL DEFAULT 0,
  `sent_to_place` varchar(255) NOT NULL,
  `no_of_chicks_sent` int(11) NOT NULL DEFAULT 0,
  `price_per_chick` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `day_old_chicks_distribution`
--

INSERT INTO `day_old_chicks_distribution` (`id`, `record_date`, `no_of_chicks_produced`, `sent_to_place`, `no_of_chicks_sent`, `price_per_chick`, `total_amount`, `created_at`) VALUES
(1, '2026-07-27', 0, 'Uppuweli', 79, '9.00', '711.00', '2026-07-27 08:28:22');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `time_duration` varchar(100) NOT NULL COMMENT 'e.g., 2 hours, 08:30-10:30'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `diary_tasks`
--

INSERT INTO `diary_tasks` (`id`, `user_id`, `task_date`, `place`, `activity`, `created_at`, `time_duration`) VALUES
(2, 19, '2026-07-11', 'uppuveli', 'test', '2026-07-11 09:32:30', '09.00 am - 11.00 am'),
(3, 19, '2026-07-23', 'uppuveli', 'Test Task', '2026-07-22 09:27:49', '09.00 am - 11.00 am');

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
-- Table structure for table `drug_records`
--

CREATE TABLE `drug_records` (
  `id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `drug_type_id` int(11) NOT NULL COMMENT 'References drug_types.id',
  `vaccine_batch_id` int(11) NOT NULL COMMENT 'References vaccine_batches.id',
  `starter_count_month` int(11) NOT NULL DEFAULT 0,
  `during_month_received` int(11) NOT NULL DEFAULT 0,
  `used_doses_count` int(11) NOT NULL DEFAULT 0,
  `doses_damaged` int(11) NOT NULL DEFAULT 0,
  `balance_end_month` int(11) GENERATED ALWAYS AS (`starter_count_month` + `during_month_received` - (`used_doses_count` + `doses_damaged`)) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `drug_records`
--

INSERT INTO `drug_records` (`id`, `log_date`, `drug_type_id`, `vaccine_batch_id`, `starter_count_month`, `during_month_received`, `used_doses_count`, `doses_damaged`, `created_at`) VALUES
(5, '2026-07-13', 2, 7, 100, 50, 30, 70, '2026-07-13 07:13:10'),
(6, '2026-07-14', 2, 6, 800, 600, 500, 300, '2026-07-13 07:32:02'),
(7, '2026-07-22', 4, 8, 90, 80, 60, 70, '2026-07-22 09:43:03');

-- --------------------------------------------------------

--
-- Table structure for table `drug_types`
--

CREATE TABLE `drug_types` (
  `id` int(11) NOT NULL,
  `vaccine_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_animal` set('Cattle','Dairy Cows','Buffalo','Goats','Poultry','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `drug_types`
--

INSERT INTO `drug_types` (`id`, `vaccine_name`, `target_animal`, `description`, `expiry_date`, `created_at`, `updated_at`) VALUES
(2, 'Xyaject Inj', 'Cattle,Dairy Cows,Buffalo,Goats,Poultry,other', '', '2026-07-31', '2026-06-02 06:48:31', '2026-06-02 06:48:31'),
(4, 'Vitamin B', 'Cattle,Dairy Cows,Buffalo', 'test', '2030-12-01', '2026-07-22 09:38:59', '2026-07-22 09:38:59');

-- --------------------------------------------------------

--
-- Table structure for table `ear_tag_usage`
--

CREATE TABLE `ear_tag_usage` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `report_month` tinyint(4) NOT NULL COMMENT '1 to 12 for Jan to Dec',
  `opening_balance` int(11) NOT NULL DEFAULT 0 COMMENT 'Nos. at the Beginning of the month',
  `received_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'Nos. Received During the month',
  `used_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'Nos. used During The Month',
  `spoilt_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'Nos. Spoilt/Damaged',
  `transferred_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'Transfer to Another Range',
  `closing_balance` int(11) NOT NULL DEFAULT 0 COMMENT 'Balance at the End of the Month',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `ear_tag_usage`
--

INSERT INTO `ear_tag_usage` (`id`, `district_id`, `range_id`, `report_year`, `report_month`, `opening_balance`, `received_qty`, `used_qty`, `spoilt_qty`, `transferred_qty`, `closing_balance`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 2026, 7, 400, 200, 120, 10, 5, 465, 17, '2026-07-13 08:22:02', '2026-07-13 08:23:12'),
(3, 1, 1, 2026, 3, 0, 90, 30, 10, 10, 40, 19, '2026-07-22 09:44:39', '2026-07-22 09:44:39');

-- --------------------------------------------------------

--
-- Table structure for table `furniture_assets`
--

CREATE TABLE `furniture_assets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `furniture_type` varchar(150) NOT NULL,
  `current_condition` enum('Excellent','Good','Fair','Damaged','Unserviceable') NOT NULL,
  `available_quantity` int(11) NOT NULL DEFAULT 1,
  `date_received` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `furniture_assets`
--

INSERT INTO `furniture_assets` (`id`, `user_id`, `district_id`, `range_id`, `furniture_type`, `current_condition`, `available_quantity`, `date_received`, `remarks`, `is_active`, `created_at`) VALUES
(1, 19, 1, 1, 'test', 'Excellent', 1, '2026-06-30', 'test', 0, '2026-06-30 14:09:07'),
(2, 19, 1, 1, 'test', 'Excellent', 1, '2026-06-30', 'test', 0, '2026-06-30 14:09:53'),
(3, 19, 1, 1, 'test', 'Excellent', 1, '2026-06-30', 'test', 1, '2026-06-30 14:10:07'),
(4, 19, 1, 1, 'test2', 'Excellent', 1, '2026-06-30', 'test2', 0, '2026-06-30 14:10:33'),
(5, 19, 1, 1, 'Wooden Desk', 'Fair', 100, '2026-07-06', 'Special Note', 1, '2026-07-07 07:56:36');

-- --------------------------------------------------------

--
-- Table structure for table `hatchery_batches`
--

CREATE TABLE `hatchery_batches` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `farm_id` int(11) DEFAULT NULL,
  `batch_date` date NOT NULL,
  `hatchable_count` int(11) NOT NULL DEFAULT 0,
  `cracked_count` int(11) NOT NULL DEFAULT 0,
  `table_count` int(11) NOT NULL DEFAULT 0,
  `total_collected` int(11) GENERATED ALWAYS AS (`hatchable_count` + `cracked_count` + `table_count`) STORED,
  `chicks_hatched` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `hatchery_batches`
--

INSERT INTO `hatchery_batches` (`id`, `user_id`, `farm_id`, `batch_date`, `hatchable_count`, `cracked_count`, `table_count`, `chicks_hatched`, `created_at`) VALUES
(1, 13, NULL, '2026-05-18', 40, 10, 30, 30, '2026-05-18 13:45:59'),
(3, 13, NULL, '2026-04-30', 100, 10, 60, 90, '2026-05-19 08:55:03');

-- --------------------------------------------------------

--
-- Table structure for table `hatchery_register`
--

CREATE TABLE `hatchery_register` (
  `id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `cage_id` int(11) NOT NULL,
  `no_of_eggs_loaded` int(11) NOT NULL DEFAULT 0,
  `date_of_candling` date DEFAULT NULL,
  `discarded_during_candling` int(11) NOT NULL DEFAULT 0,
  `date_of_hatching` date DEFAULT NULL,
  `no_of_hatched_eggs` int(11) NOT NULL DEFAULT 0,
  `no_of_deaths` int(11) NOT NULL DEFAULT 0,
  `no_of_good_chicks` int(11) NOT NULL DEFAULT 0,
  `hatching_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `loaded_to_cage_id` int(11) NOT NULL,
  `remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `hatchery_register`
--

INSERT INTO `hatchery_register` (`id`, `record_date`, `cage_id`, `no_of_eggs_loaded`, `date_of_candling`, `discarded_during_candling`, `date_of_hatching`, `no_of_hatched_eggs`, `no_of_deaths`, `no_of_good_chicks`, `hatching_percentage`, `loaded_to_cage_id`, `remark`, `created_at`) VALUES
(1, '2026-07-27', 1, 90, '2026-07-27', 40, '2026-07-28', 90, 10, 80, '88.89', 2, 'Test', '2026-07-27 09:01:09');

-- --------------------------------------------------------

--
-- Table structure for table `hatchery_sales`
--

CREATE TABLE `hatchery_sales` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `farm_id` int(11) DEFAULT NULL,
  `sales_date` date NOT NULL,
  `egg_category` enum('Table','Cracked') NOT NULL,
  `quantity_sold` int(11) NOT NULL DEFAULT 0,
  `actual_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `hope_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_revenue` decimal(15,2) GENERATED ALWAYS AS (`quantity_sold` * `actual_rate`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `hatchery_sales`
--

INSERT INTO `hatchery_sales` (`id`, `user_id`, `farm_id`, `sales_date`, `egg_category`, `quantity_sold`, `actual_rate`, `hope_rate`, `created_at`) VALUES
(1, 13, NULL, '2026-05-19', 'Table', 30, '30.00', '10.00', '2026-05-19 06:48:49'),
(2, 13, NULL, '2026-05-18', 'Cracked', 10, '10.00', '10.00', '2026-05-19 06:50:19');

-- --------------------------------------------------------

--
-- Table structure for table `health_certificate_issues`
--

CREATE TABLE `health_certificate_issues` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `report_month` tinyint(4) NOT NULL COMMENT '1 to 12 for Jan to Dec',
  `health_certificate_no` varchar(100) NOT NULL,
  `applicant_name_address` text NOT NULL COMMENT 'Name of the Applicant & Address',
  `farm_registration_no` varchar(100) DEFAULT NULL,
  `date_of_issue` date NOT NULL,
  `species` varchar(100) DEFAULT NULL COMMENT 'Species B/Nc',
  `animal_details_male` int(11) NOT NULL DEFAULT 0 COMMENT 'Details of animal - Male',
  `animal_details_female` int(11) NOT NULL DEFAULT 0 COMMENT 'Details of animal - Female',
  `vehicle_fitness_certificate_no` varchar(100) DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `health_certificate_issues`
--

INSERT INTO `health_certificate_issues` (`id`, `district_id`, `range_id`, `report_year`, `report_month`, `health_certificate_no`, `applicant_name_address`, `farm_registration_no`, `date_of_issue`, `species`, `animal_details_male`, `animal_details_female`, `vehicle_fitness_certificate_no`, `purpose`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2026, 10, 'HC/2026/0854', 'John Doe, 123 Farm Road, Balapitiya', 'FRN/BAL/101', '2026-10-15', 'Bovine', 15, 20, 'VF/6075', 'Breeding', 17, '2026-07-13 09:17:13', '2026-07-13 09:18:06');

-- --------------------------------------------------------

--
-- Table structure for table `human_populations`
--

CREATE TABLE `human_populations` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `ethnicity` varchar(50) NOT NULL,
  `population_type` varchar(50) NOT NULL,
  `population_count` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `human_populations`
--

INSERT INTO `human_populations` (`id`, `range_id`, `year`, `ethnicity`, `population_type`, `population_count`, `created_at`) VALUES
(1, 1, 2025, 'Sinhala', 'Male', 1800, '2026-07-01 11:42:10'),
(2, 1, 2025, 'Sinhala', 'Female', 1700, '2026-07-01 11:42:10'),
(3, 1, 2025, 'Sinhala', 'Households', 850, '2026-07-01 11:42:10'),
(4, 1, 2025, 'Tamil', 'Male', 1000, '2026-07-01 11:42:10'),
(5, 1, 2025, 'Tamil', 'Female', 900, '2026-07-01 11:42:10'),
(6, 1, 2025, 'Tamil', 'Households', 450, '2026-07-01 11:42:10'),
(7, 1, 2025, 'Muslim', 'Male', 520, '2026-07-01 11:42:10'),
(8, 1, 2025, 'Muslim', 'Female', 500, '2026-07-01 11:42:10'),
(9, 1, 2025, 'Muslim', 'Households', 255, '2026-07-01 11:42:10'),
(10, 1, 2024, 'Sinhala', 'Male', 1750, '2026-07-01 11:42:10'),
(11, 1, 2024, 'Sinhala', 'Female', 1680, '2026-07-01 11:42:10'),
(12, 1, 2024, 'Sinhala', 'Households', 830, '2026-07-01 11:42:10');

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
-- Table structure for table `instrument_assets`
--

CREATE TABLE `instrument_assets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `instrument_type` varchar(150) NOT NULL,
  `current_condition` varchar(100) NOT NULL,
  `available_quantity` int(11) NOT NULL DEFAULT 1,
  `purchase_date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `instrument_assets`
--

INSERT INTO `instrument_assets` (`id`, `user_id`, `district_id`, `range_id`, `instrument_type`, `current_condition`, `available_quantity`, `purchase_date`, `remarks`, `is_active`, `created_at`) VALUES
(1, 19, 1, 1, 'test', 'Good', 1, '0000-00-00', 'test', 0, '2026-06-30 14:35:22'),
(2, 19, 1, 1, 'Surgical Kit', 'Good', 50, '0000-00-00', 'Special Note', 1, '2026-07-07 07:58:09');

-- --------------------------------------------------------

--
-- Table structure for table `land_assets`
--

CREATE TABLE `land_assets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `property_name` varchar(255) NOT NULL,
  `land_extent` varchar(150) NOT NULL,
  `building_area` varchar(150) NOT NULL,
  `land_status` enum('State Owned','Leased','Vested','Private') NOT NULL,
  `deed_reference` varchar(255) NOT NULL,
  `deed_description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `land_assets`
--

INSERT INTO `land_assets` (`id`, `user_id`, `district_id`, `range_id`, `property_name`, `land_extent`, `building_area`, `land_status`, `deed_reference`, `deed_description`, `is_active`, `created_at`) VALUES
(1, 19, 1, 1, 'test', 'test', '500', 'State Owned', 'test', 'test', 1, '2026-06-30 13:27:16'),
(2, 19, 1, 1, 'test', 'test', '500', 'Private', 'test', '', 1, '2026-06-30 13:28:38'),
(3, 19, 1, 1, 'test', 'test', '500', 'Leased', 'test', 'test', 1, '2026-07-07 07:29:43'),
(4, 19, 1, 1, 'Test 01', 'Test', '2500', 'Leased', 'Test', 'Note test', 1, '2026-07-07 07:52:41');

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
-- Table structure for table `letter_h_accounts`
--

CREATE TABLE `letter_h_accounts` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` enum('Receipt','Disbursement') NOT NULL DEFAULT 'Receipt',
  `reference_no` varchar(50) DEFAULT NULL COMMENT 'Receipt No (e.g., 1030832) or Deposit Ref',
  `particulars` varchar(255) NOT NULL COMMENT 'Description (e.g., Consultation, Ranikhet vaccine, Deposit to Bank)',
  `quantity` int(11) DEFAULT NULL COMMENT 'Optional: Extracted from details (e.g., 01 from 01x150/-)',
  `rate` decimal(10,2) DEFAULT NULL COMMENT 'Optional: Extracted from details (e.g., 150.00)',
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL COMMENT 'References users.id',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `letter_h_accounts`
--

INSERT INTO `letter_h_accounts` (`id`, `district_id`, `range_id`, `transaction_date`, `transaction_type`, `reference_no`, `particulars`, `quantity`, `rate`, `amount`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-07-13', 'Receipt', '1030832', 'Consultation', 1, '150.00', '150.00', 19, '2026-07-13 04:45:17', '2026-07-13 05:02:00'),
(2, 1, 1, '2026-07-13', 'Disbursement', '1030832', 'Consultation', 1, '150.00', '150.00', 19, '2026-07-13 05:06:25', '2026-07-13 05:06:25'),
(3, 1, 1, '2026-07-23', 'Receipt', '1030832', 'Consultation', 70, '300.00', '21000.00', 19, '2026-07-22 09:34:45', '2026-07-22 09:34:45');

-- --------------------------------------------------------

--
-- Table structure for table `livestock_societies`
--

CREATE TABLE `livestock_societies` (
  `id` int(11) NOT NULL,
  `vs_range` varchar(255) NOT NULL,
  `gn_division` varchar(255) DEFAULT NULL,
  `name_address` text DEFAULT NULL,
  `overall_objective` text DEFAULT NULL,
  `total_members` int(11) DEFAULT NULL,
  `reg_no` varchar(100) DEFAULT NULL,
  `reg_department` varchar(255) DEFAULT NULL,
  `major_activities` text DEFAULT NULL,
  `financial_records_availability` varchar(50) DEFAULT NULL,
  `regulated_by` varchar(255) DEFAULT NULL,
  `tp_no` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `livestock_societies`
--

INSERT INTO `livestock_societies` (`id`, `vs_range`, `gn_division`, `name_address`, `overall_objective`, `total_members`, `reg_no`, `reg_department`, `major_activities`, `financial_records_availability`, `regulated_by`, `tp_no`, `created_at`) VALUES
(1, 'Ampara', 'Uppuveli', 'GVSO, Trincomalee', 'Member\'s Request', 160, '74-338', 'Dept Co- Operatives Trincomalee', 'Yes', 'Yes', '', '0777460260', '2026-07-21 12:52:43');

-- --------------------------------------------------------

--
-- Table structure for table `machinery_assets`
--

CREATE TABLE `machinery_assets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `machinery_type` varchar(150) NOT NULL,
  `current_condition` varchar(100) NOT NULL,
  `available_quantity` int(11) NOT NULL DEFAULT 1,
  `purchase_date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `machinery_assets`
--

INSERT INTO `machinery_assets` (`id`, `user_id`, `district_id`, `range_id`, `machinery_type`, `current_condition`, `available_quantity`, `purchase_date`, `remarks`, `is_active`, `created_at`) VALUES
(1, 19, 1, 1, 'test', 'Good', 1, '0000-00-00', 'test', 0, '2026-06-30 14:26:53'),
(2, 19, 1, 1, 'Test', 'Needs Repair', 6, '0000-00-00', 'Special Record', 1, '2026-07-07 07:57:15');

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
-- Table structure for table `milk_collecting_centers`
--

CREATE TABLE `milk_collecting_centers` (
  `id` int(11) NOT NULL,
  `vs_range` varchar(255) NOT NULL,
  `collecting_center_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_no` varchar(50) DEFAULT NULL,
  `milk_collection_lit_per_month` decimal(10,2) DEFAULT NULL,
  `milk_chilling_capacity` decimal(10,2) DEFAULT NULL,
  `milk_supply_to` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `milk_collecting_centers`
--

INSERT INTO `milk_collecting_centers` (`id`, `vs_range`, `collecting_center_name`, `address`, `contact_no`, `milk_collection_lit_per_month`, `milk_chilling_capacity`, `milk_supply_to`, `created_at`) VALUES
(1, 'Ampara', 'Milco', 'Uppuveli', '', '2500.00', '9000.00', 'Milco', '2026-07-21 12:56:50'),
(2, 'Ampara', 'Milco', 'Milco address edited', '0771234567', '8000.00', '500.00', 'Cargills', '2026-07-22 09:11:57');

-- --------------------------------------------------------

--
-- Table structure for table `milk_processing_centers`
--

CREATE TABLE `milk_processing_centers` (
  `id` int(11) NOT NULL,
  `vs_range` varchar(255) NOT NULL,
  `processing_center_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_no` varchar(50) DEFAULT NULL,
  `yoghurt_lit_per_month` decimal(10,2) DEFAULT NULL,
  `curd_lit_per_month` decimal(10,2) DEFAULT NULL,
  `ice_cream_lit_per_month` decimal(10,2) DEFAULT NULL,
  `ghee_lit_per_month` decimal(10,2) DEFAULT NULL,
  `other_milk_product_lit_per_month` decimal(10,2) DEFAULT NULL,
  `total_lit_per_month` decimal(10,2) DEFAULT NULL,
  `income_rs_per_month` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `milk_processing_centers`
--

INSERT INTO `milk_processing_centers` (`id`, `vs_range`, `processing_center_name`, `address`, `contact_no`, `yoghurt_lit_per_month`, `curd_lit_per_month`, `ice_cream_lit_per_month`, `ghee_lit_per_month`, `other_milk_product_lit_per_month`, `total_lit_per_month`, `income_rs_per_month`, `created_at`) VALUES
(1, 'Ampara', 'Milco', '18,Mahayaya,Uppuweli', '0771234567', '18000.00', '3000.00', '5000.00', '700.00', '600.00', '27300.00', '27300.00', '2026-07-21 13:02:28'),
(2, 'Ampara', 'Milco', 'Milco Address', '0112345678', '900.00', '700.00', '1700.00', '500.00', '300.00', '4100.00', '19000.00', '2026-07-22 09:13:26');

-- --------------------------------------------------------

--
-- Table structure for table `milk_product_sales_centers`
--

CREATE TABLE `milk_product_sales_centers` (
  `id` int(11) NOT NULL,
  `vs_range` varchar(255) NOT NULL,
  `sales_center_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_no` varchar(50) DEFAULT NULL,
  `fresh_milk_lit_per_month` decimal(10,2) DEFAULT NULL,
  `yoghurt_lit_per_month` decimal(10,2) DEFAULT NULL,
  `curd_lit_per_month` decimal(10,2) DEFAULT NULL,
  `ice_cream_lit_per_month` decimal(10,2) DEFAULT NULL,
  `ghee_lit_per_month` decimal(10,2) DEFAULT NULL,
  `other_milk_product_lit_per_month` decimal(10,2) DEFAULT NULL,
  `total_lit_per_month` decimal(10,2) DEFAULT NULL,
  `income_rs_per_month` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `milk_product_sales_centers`
--

INSERT INTO `milk_product_sales_centers` (`id`, `vs_range`, `sales_center_name`, `address`, `contact_no`, `fresh_milk_lit_per_month`, `yoghurt_lit_per_month`, `curd_lit_per_month`, `ice_cream_lit_per_month`, `ghee_lit_per_month`, `other_milk_product_lit_per_month`, `total_lit_per_month`, `income_rs_per_month`, `created_at`) VALUES
(1, 'Ampara', 'Nestle', 'Test Address', '0712345678', '90.00', '60.00', '60.00', '60.00', '60.00', '60.00', '390.00', '700.00', '2026-07-22 09:14:56');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_vaccine_balances`
--

CREATE TABLE `monthly_vaccine_balances` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` year(4) NOT NULL,
  `report_month` tinyint(4) NOT NULL COMMENT '1 to 12 for Jan to Dec',
  `vaccine_name` varchar(150) NOT NULL COMMENT 'e.g., Ranikhet 1, Fowl pox, ARV',
  `opening_balance` int(11) NOT NULL DEFAULT 0 COMMENT 'No. of Doses beginning of the Month',
  `received_doses` int(11) NOT NULL DEFAULT 0 COMMENT 'No. of Doses Received During the Month',
  `used_doses` int(11) NOT NULL DEFAULT 0 COMMENT 'No. of Doses Used During the Month',
  `spoilt_damaged_doses` int(11) NOT NULL DEFAULT 0 COMMENT 'No. of Doses Spoilt/Damaged',
  `transferred_doses` int(11) NOT NULL DEFAULT 0 COMMENT 'Transfer to another Range',
  `closing_balance` int(11) NOT NULL DEFAULT 0 COMMENT 'Balance Doses at the End of the Month',
  `batch_no` varchar(100) DEFAULT NULL,
  `expiry_date` varchar(50) DEFAULT NULL COMMENT 'VARCHAR used because sometimes formats are loose (e.g. August/2026)',
  `remarks` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `monthly_vaccine_balances`
--

INSERT INTO `monthly_vaccine_balances` (`id`, `district_id`, `range_id`, `report_year`, `report_month`, `vaccine_name`, `opening_balance`, `received_doses`, `used_doses`, `spoilt_damaged_doses`, `transferred_doses`, `closing_balance`, `batch_no`, `expiry_date`, `remarks`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 2026, 6, 'Ranikhet 1', 4800, 0, 2800, 0, 0, 2000, '2025/03', '05.08.2026', 'Pg No.67', 19, '2026-07-13 06:01:56', '2026-07-13 06:01:56'),
(2, 1, 1, 2026, 1, 'Xyaject Inj', 90, 60, 10, 10, 10, 120, 'SLNV-06/03-2025', '2026-07-31', 'Test', 19, '2026-07-22 09:41:29', '2026-07-22 09:41:29');

-- --------------------------------------------------------

--
-- Table structure for table `month_old_chicks_distribution`
--

CREATE TABLE `month_old_chicks_distribution` (
  `id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `cage_id` int(11) DEFAULT NULL,
  `no_of_chicks_produced` int(11) NOT NULL DEFAULT 0,
  `sent_to_place` varchar(255) NOT NULL,
  `no_of_chicks_sent` int(11) NOT NULL DEFAULT 0,
  `price_per_chick` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `month_old_chicks_distribution`
--

INSERT INTO `month_old_chicks_distribution` (`id`, `record_date`, `cage_id`, `no_of_chicks_produced`, `sent_to_place`, `no_of_chicks_sent`, `price_per_chick`, `total_amount`, `created_at`) VALUES
(1, '2026-07-29', 1, 7, 'Uppuweli', 8, '8.00', '64.00', '2026-07-27 08:29:00');

-- --------------------------------------------------------

--
-- Table structure for table `parent_stock_flocks`
--

CREATE TABLE `parent_stock_flocks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `farm_id` int(11) DEFAULT NULL,
  `flock_code` varchar(50) NOT NULL,
  `region` varchar(100) NOT NULL,
  `current_count` int(11) NOT NULL DEFAULT 0,
  `assigned_cages` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `parent_stock_flocks`
--

INSERT INTO `parent_stock_flocks` (`id`, `user_id`, `farm_id`, `flock_code`, `region`, `current_count`, `assigned_cages`) VALUES
(1, NULL, NULL, 'SAT-CB-2026-01', 'Sathurukondan', 4, 'C-09'),
(2, NULL, NULL, 'THM-CB-2026-02', 'Thampalakamam', 6, 'B-05'),
(3, NULL, NULL, 'TRK-HB-2026-01', 'Thirukkovil', 8, 'A-10');

-- --------------------------------------------------------

--
-- Table structure for table `pasture_fodder_lands`
--

CREATE TABLE `pasture_fodder_lands` (
  `id` int(11) NOT NULL,
  `vs_range` varchar(255) NOT NULL,
  `report_year` int(11) DEFAULT 2024,
  `pasture_families_quarter_ac` int(11) DEFAULT 0 COMMENT '1/4 Ac',
  `pasture_families_half_ac` int(11) DEFAULT 0 COMMENT '1/2 Ac',
  `pasture_families_one_ac` int(11) DEFAULT 0 COMMENT '1 Ac',
  `pasture_families_gt_one_ac` int(11) DEFAULT 0 COMMENT '> 1Ac',
  `pasture_total_acre` decimal(10,2) DEFAULT 0.00,
  `pasture_total_families` int(11) DEFAULT 0,
  `fodder_families_quarter_ac` int(11) DEFAULT 0 COMMENT '1/4 Ac',
  `fodder_families_half_ac` int(11) DEFAULT 0 COMMENT '1/2 Ac',
  `fodder_families_one_ac` int(11) DEFAULT 0 COMMENT '1 Ac',
  `fodder_families_gt_one_ac` int(11) DEFAULT 0 COMMENT '> 1Ac',
  `fodder_total_acre` decimal(10,2) DEFAULT 0.00,
  `fodder_total_families` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `pasture_fodder_lands`
--

INSERT INTO `pasture_fodder_lands` (`id`, `vs_range`, `report_year`, `pasture_families_quarter_ac`, `pasture_families_half_ac`, `pasture_families_one_ac`, `pasture_families_gt_one_ac`, `pasture_total_acre`, `pasture_total_families`, `fodder_families_quarter_ac`, `fodder_families_half_ac`, `fodder_families_one_ac`, `fodder_families_gt_one_ac`, `fodder_total_acre`, `fodder_total_families`, `created_at`) VALUES
(3, 'Ampara', 2026, 1, 1, 1, 0, '6.00', 8, 8, 5, 7, 0, '8.00', 9, '2026-07-22 08:44:42'),
(4, 'Ampara', 2025, 9, 9, 9, 7, '9.00', 34, 6, 18, 6, 7, '5.00', 37, '2026-07-22 09:04:34');

-- --------------------------------------------------------

--
-- Table structure for table `production_activity_targets`
--

CREATE TABLE `production_activity_targets` (
  `id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `range_id` int(11) NOT NULL,
  `activity_name` varchar(255) NOT NULL,
  `animal_category` enum('Cow','Buffalo','Goat','Chicken','Pig','Other') DEFAULT NULL,
  `animal_category_other` varchar(100) DEFAULT NULL,
  `target_quantity` int(11) NOT NULL DEFAULT 0,
  `achieved_quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `production_activity_targets`
--

INSERT INTO `production_activity_targets` (`id`, `year`, `range_id`, `activity_name`, `animal_category`, `animal_category_other`, `target_quantity`, `achieved_quantity`, `created_at`, `updated_at`) VALUES
(1, 2026, 1, 'Cattle Shed Construction', 'Cow', NULL, 8, 9, '2026-07-06 13:07:25', '2026-07-06 13:07:25'),
(2, 2026, 1, 'Goat Shed Construction', 'Goat', NULL, 3, 2, '2026-07-06 13:09:00', '2026-07-06 13:09:00'),
(3, 2026, 1, 'Test', 'Other', 'Rabbit', 50, 40, '2026-07-22 09:23:59', '2026-07-22 09:23:59');

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
(7, 'Poultry Feed Production ', 7),
(9, 'Slaughter Statistics', 0),
(10, 'Semen Details', 0);

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
(30, 7, 'Layer Feed Production', 'Kg'),
(32, 9, 'Neat Cattle Slaughter at Slaughter House During the Month', 'Nos'),
(33, 10, 'Available Semen at beginning of the month', 'Nos');

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
-- Table structure for table `regional_farms`
--

CREATE TABLE `regional_farms` (
  `id` int(11) NOT NULL,
  `farm_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `regional_farms`
--

INSERT INTO `regional_farms` (`id`, `farm_name`, `location`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Regional Livestock Farm', 'Uppuveli', 1, '2026-06-22 09:06:46', '2026-06-22 09:06:46'),
(2, 'Intergrade Model Farm', 'Mandoor', 1, '2026-06-22 09:06:46', '2026-06-22 09:06:46'),
(3, 'Goat Breeder Farm', 'Sathurukonda', 1, '2026-06-22 09:06:46', '2026-06-22 09:06:46'),
(4, 'Buffalo Nuclear Farm', 'Morawewa', 1, '2026-06-22 09:06:46', '2026-06-22 09:06:46'),
(5, 'Goat Genetic Resource Development Center', 'Thumpankerny', 1, '2026-06-22 09:06:46', '2026-06-22 09:06:46'),
(6, 'Stud Center', 'Thirukkovil', 1, '2026-06-22 09:06:46', '2026-06-22 09:06:46'),
(7, 'Stud Center', 'Kantalai', 1, '2026-06-22 09:06:46', '2026-06-22 09:06:46');

-- --------------------------------------------------------

--
-- Table structure for table `registered_vehicles`
--

CREATE TABLE `registered_vehicles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `vehicle_type` varchar(100) NOT NULL,
  `vehicle_number` varchar(50) NOT NULL,
  `chassis_number` varchar(100) NOT NULL,
  `current_condition` varchar(100) NOT NULL,
  `other_details` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `registered_vehicles`
--

INSERT INTO `registered_vehicles` (`id`, `user_id`, `district_id`, `range_id`, `vehicle_type`, `vehicle_number`, `chassis_number`, `current_condition`, `other_details`, `is_active`, `created_at`) VALUES
(1, 19, 1, 1, 'Motorbike', 'TEST', 'TEST', 'Running', 'test', 1, '2026-06-30 14:01:06'),
(2, 19, 1, 1, 'Double Cab', 'TEST 1', 'TEST RECORD', 'Needs Repair', 'test record', 1, '2026-07-07 07:54:33');

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
-- Table structure for table `section_e`
--

CREATE TABLE `section_e` (
  `id` int(11) NOT NULL,
  `district_id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `report_year` int(11) NOT NULL,
  `report_month` tinyint(4) NOT NULL COMMENT '1 to 12 for Jan to Dec',
  `category_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `section_e`
--

INSERT INTO `section_e` (`id`, `district_id`, `range_id`, `report_year`, `report_month`, `category_id`, `item_id`, `amount`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 2026, 7, 1, 1, '8.00', '2026-07-11 12:18:26', '2026-07-11 12:18:37'),
(3, 1, 1, 2026, 7, 9, 32, '9000.00', '2026-07-11 12:37:27', '2026-07-22 09:32:30'),
(4, 1, 1, 2026, 7, 10, 33, '154.00', '2026-07-11 12:38:54', '2026-07-11 12:38:54');

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
(1, 1, 1, 2026, 'Buffalo', 50, 30, 10, 0, 0, '1000.00', '2026-04-03 06:03:01'),
(3, 1, 2, 2026, 'Poultry', 60, 10, 20, 10, 0, '2000.00', '2026-04-03 06:33:42'),
(4, 1, 3, 2026, 'Cock', 50, 10, 10, 0, 0, '2000.00', '2026-04-03 06:34:22');

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
(1, 1, 4, 2026, 'Cattle', 'Slaughter House', 30, '3000.00', 19, '2026-04-03 04:36:53'),
(2, 1, 4, 2026, 'Goat', 'In-Farm', 29, '5000.00', 19, '2026-04-03 04:38:03');

-- --------------------------------------------------------

--
-- Table structure for table `sms_immunization`
--

CREATE TABLE `sms_immunization` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `vaccination_type` varchar(150) NOT NULL,
  `starter_count_month` int(11) NOT NULL DEFAULT 0,
  `during_month_received` int(11) NOT NULL DEFAULT 0,
  `used_batch_number` varchar(100) NOT NULL,
  `used_doses_count` int(11) NOT NULL DEFAULT 0,
  `doses_damaged` int(11) NOT NULL DEFAULT 0,
  `balance_batch_number` varchar(100) NOT NULL,
  `balance_doses_qty` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sms_immunization`
--

INSERT INTO `sms_immunization` (`id`, `user_id`, `log_date`, `vaccination_type`, `starter_count_month`, `during_month_received`, `used_batch_number`, `used_doses_count`, `doses_damaged`, `balance_batch_number`, `balance_doses_qty`, `created_at`) VALUES
(2, 12, '2026-06-01', 'Fowl pox', 100, 50, '6', 120, 10, '6', 20, '2026-06-01 10:02:09');

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

--
-- Dumping data for table `stock_balance_logs`
--

INSERT INTO `stock_balance_logs` (`id`, `flock_id`, `newly_added`, `culling`, `log_date`) VALUES
(7, 1, 5, 1, '2026-05-14 10:55:13'),
(8, 2, 7, 1, '2026-05-14 13:36:46'),
(9, 3, 10, 2, '2026-05-14 13:36:58');

-- --------------------------------------------------------

--
-- Table structure for table `strategic_action_indicators`
--

CREATE TABLE `strategic_action_indicators` (
  `id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `range_id` int(11) NOT NULL,
  `strategy_pillar` varchar(255) NOT NULL,
  `sub_activity` text NOT NULL,
  `target_count` int(11) NOT NULL DEFAULT 0,
  `achieved_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `strategic_action_indicators`
--

INSERT INTO `strategic_action_indicators` (`id`, `year`, `range_id`, `strategy_pillar`, `sub_activity`, `target_count`, `achieved_count`, `created_at`, `updated_at`) VALUES
(1, 2026, 1, 'Disease Prevention and Prophylaxis control', 'test', 6, 4, '2026-07-06 13:06:58', '2026-07-06 13:06:58'),
(2, 2026, 1, 'Disease Prevention and Prophylaxis control', 'test', 8, 8, '2026-07-22 07:41:29', '2026-07-22 07:41:29'),
(3, 2026, 1, 'Institutional Capacity and Staff Deployment', 'Test', 70, 35, '2026-07-22 09:25:08', '2026-07-22 09:25:08');

-- --------------------------------------------------------

--
-- Table structure for table `training_centers`
--

CREATE TABLE `training_centers` (
  `id` int(11) NOT NULL,
  `center_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `training_centers`
--

INSERT INTO `training_centers` (`id`, `center_name`, `location`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Animal Husbandry Training Centre', 'Uppuveli', 1, '2026-06-22 09:03:08', '2026-06-22 09:03:08'),
(2, 'Regional Training Centre', 'Kallady', 1, '2026-06-22 09:03:08', '2026-06-22 09:03:08'),
(3, 'Animal Husbandry Farmer Training Centre', 'Kanchirankuda', 1, '2026-06-22 09:03:08', '2026-06-22 09:03:08');

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
  `service_number` varchar(100) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `role` enum('provincial_director','district_dd','veterinary_surgeon','training_officer','sms','farms_dd','finance_admin','planning_officer','administrator','data_entry','employee') NOT NULL,
  `service_category` varchar(150) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `range_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `registered_date` date DEFAULT NULL,
  `appointment_date` date DEFAULT NULL,
  `appointment_date_current_position` date DEFAULT NULL,
  `office_id` int(11) DEFAULT NULL COMMENT 'Links to veterinary_ranges.id (for Veterinary Surgeon only)',
  `farm_id` int(11) DEFAULT NULL,
  `district` enum('Amparai','Batticaloa','Trincomalee','Provincial') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password`, `full_name`, `emp_id`, `service_number`, `designation`, `role`, `service_category`, `district_id`, `range_id`, `unit_id`, `registered_date`, `appointment_date`, `appointment_date_current_position`, `office_id`, `farm_id`, `district`, `is_active`, `last_login`, `created_at`, `profile_image`) VALUES
(5, 'yo', 'provinciald2@gmail.com', NULL, 'b62c1853f21bb51f6ce7faca1becc040', 'Provincial Director', NULL, NULL, NULL, 'provincial_director', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-01-03 16:15:19', '2025-12-12 11:30:50', NULL),
(7, 'adminstrator', 'admins@gmail.com', NULL, '$2y$10$nlm7FQcS7mceOa48ZahFTO.DdagUFOjijh5Yl.HNTs4yj2fWBcq/2', 'Admin Login', NULL, NULL, NULL, 'administrator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-05-21 16:36:50', '2025-12-15 11:32:14', NULL),
(10, 'finance_admin', 'finance@gmail.com', NULL, '$2y$10$pjmgh5Ij1k6tTXpCPuKo3.bxhwYip.D/D33bT4CSm4su2YUYnHlWe', 'Finance admin', NULL, NULL, NULL, 'finance_admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-06-10 16:04:04', '2025-12-16 07:42:06', NULL),
(11, 'Planning officer', 'planning@gmail.com', NULL, '$2y$10$xM5nKggJu8OJ5E4AV9n4OOuqJ4L2TUqxfXnBoAV0dBcqycEv2L99W', 'Planning officer', NULL, NULL, NULL, 'planning_officer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-06-10 16:05:02', '2025-12-16 09:34:59', NULL),
(12, 'Subject Matter Specialist', 'sms@gmail.com', NULL, '$2y$10$M2geolCGKHuoKMn1R1A0x.Qde.C5H7ME3GS.BzQRMAE5gNpA4VmCu', 'Subject Matter Specialist', NULL, NULL, NULL, 'sms', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-07-13 11:33:52', '2025-12-16 11:30:03', NULL),
(13, 'Farms Officer', 'farms@gmail.com', NULL, '$2y$10$yig.Tm9WNcTOZx0wOY5ZzukY9Zp4L1Yf2tmilQWcHM5Rfw3euAyW6', 'Deputy Director (Farms Operation)', NULL, NULL, NULL, 'farms_dd', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-05-25 20:22:03', '2025-12-17 08:46:28', NULL),
(15, 'Training Officer', 'training@gmail.com', NULL, '$2y$10$dK4TD.h0f07IW/xDn.p8GuEW0kIiu2lhXlnYt64SUBeOaeWvIqNNK', 'Training Officer', NULL, NULL, NULL, 'training_officer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-06-10 17:18:35', '2025-12-17 10:22:46', NULL),
(16, 'District Deputy Director', 'district_dd@gmail.com', NULL, '$2y$10$ktztqj1XUpA6UsNmP2wreuSepNmMZ.cdIAnSuQhhXBcuyjZcmrAQq', 'District Deputy Director', NULL, NULL, NULL, 'district_dd', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-06-09 18:17:56', '2025-12-17 13:23:28', NULL),
(17, 'veterinary surgeon', 'veterinary@gmail.com', '0712345678', '$2y$10$.rrAOsDrZRZ1auMc3Y.orODketpLbb0ctCrg5MwUqkcEWpqrUqIYC', 'veterinary surgeon', NULL, NULL, NULL, 'veterinary_surgeon', NULL, 1, 13, NULL, NULL, NULL, NULL, NULL, NULL, 'Amparai', 1, '2026-07-13 19:15:24', '2025-12-18 10:10:22', NULL),
(18, 'Provincial director', 'provinciald@gmail.com', NULL, '$2y$10$rosK7hcBMssxuPRgI6iqi.CbGiv7bmo7lsM68UAPaRxZR4/uJc37G', 'Provincial Director', NULL, NULL, NULL, 'provincial_director', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Provincial', 1, '2026-06-17 12:17:54', '2026-01-05 13:18:11', NULL),
(19, 'Ampara veterinary surgeon', 'amp_veterinary@gmail.com', '0712345678', '$2y$10$C23XrN3nUI/IaA4vmnQOR.lASC11IaUhMlh6lfrwTFo6lUorD8hmG', 'Ampara Veterinary Surgeon', NULL, NULL, NULL, 'veterinary_surgeon', NULL, 1, 1, NULL, NULL, NULL, NULL, 1, NULL, 'Amparai', 1, '2026-07-28 11:28:50', '2026-03-25 10:58:36', NULL),
(20, 'employee', 'emp@gmail.com', NULL, '$2y$10$ITeSMQXxM8Ciwu4KK/Sy2O7ai30xUjP8yrL1WNRzXlNnsrG8ylfZK', 'Test Employee', NULL, NULL, NULL, 'employee', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 'Amparai', 1, '2026-05-18 17:56:16', '2026-04-22 06:10:30', 'profile_20_1777526035.png'),
(21, 'dujiththera', 'dujiththera.l@daph.lk', NULL, '$2y$10$.rrAOsDrZRZ1auMc3Y.orODketpLbb0ctCrg5MwUqkcEWpqrUqIYC', 'Dr. (Mrs). L. Dujiththera', NULL, NULL, 'GVS', 'veterinary_surgeon', NULL, 2, 21, NULL, NULL, NULL, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(22, 'sinharasa', 'sinharasa.a@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mr. A. Sinharasa', NULL, NULL, 'LDO', 'employee', NULL, 2, 21, NULL, NULL, NULL, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(23, 'amirthalingam', 'amirthalingam.p@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mrs. P. Amirthalingam', NULL, NULL, 'LDO', 'employee', NULL, 2, 21, NULL, NULL, NULL, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(24, 'vimalathasan', 'vimalathasan.s@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mrs. S. Vimalathasan', NULL, NULL, 'PDO', 'employee', NULL, 2, 21, NULL, NULL, NULL, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(25, 'muruhathasan', 'muruhathasan.k@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mrs. K. Muruhathasan', NULL, NULL, 'PDO', 'employee', NULL, 2, 21, NULL, NULL, NULL, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(26, 'yoganathan', 'yoganathan.k@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mrs. K. Yoganathan', NULL, NULL, 'PDO', 'employee', NULL, 2, 21, NULL, NULL, NULL, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(27, 'thiruganasuntharam', 'thiru.s@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mrs. S. Thiruganasuntharam', NULL, NULL, 'CDO', 'employee', NULL, 2, 21, NULL, NULL, NULL, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(28, 'koneswaran', 'koneswaran.n@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mr. N. Koneswaran', NULL, NULL, 'PDO', 'employee', NULL, 2, 21, NULL, NULL, NULL, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(29, 'saththiyawan', 'saththiyawan.t@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mr. T. Saththiyawan', NULL, NULL, 'Driver', 'employee', NULL, 2, 21, NULL, NULL, NULL, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(30, 'gaminiraj', 'gaminiraj.n@daph.lk', NULL, '$2y$10$8K1p/a0PdzS.pG92CPpY9.NmsY6F.6P.1N3G7.Y6N3G7.Y6N3G7.', 'Mr. N. Gaminiraj', NULL, NULL, 'Watcher', 'employee', NULL, 2, 21, NULL, NULL, NULL, NULL, NULL, NULL, 'Batticaloa', 1, NULL, '2026-04-29 10:32:40', NULL),
(42, 'test', 'test@gmail.com', '0778439871', '$2y$10$dynlOJHtL.8fdGd0fcwNz.dZYr4FHzBsUxSUqBOxk9zgjySfr4n7y', 'test', '210', '210', 'Veterinary Surgeon', 'employee', 'test', 1, 1, NULL, '2026-07-09', '2026-07-09', '2026-07-09', NULL, NULL, 'Amparai', 0, NULL, '2026-07-09 06:32:57', NULL),
(45, 'regionalfarms', 'regionalfarms@gmail.com', NULL, '$2y$10$k5hbQiiYpVp70ObCvcWTTecgRxgETgKmvSCs/.b/ENUMuwfceWMVS', 'Regional Farms User', NULL, NULL, NULL, 'farms_dd', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 'Provincial', 1, '2026-07-28 16:41:22', '2026-07-20 07:29:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `vaccine_batches`
--

CREATE TABLE `vaccine_batches` (
  `id` int(11) NOT NULL,
  `batch_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `remarks` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vaccine_batches`
--

INSERT INTO `vaccine_batches` (`id`, `batch_number`, `is_active`, `created_at`, `updated_at`, `remarks`, `user_id`) VALUES
(6, 'SLNV-06/03-2025', 1, '2026-06-01 06:44:59', '2026-06-01 06:49:24', '', NULL),
(7, 'T-006', 1, '2026-06-01 11:22:24', '2026-06-01 11:22:24', '', NULL),
(8, 'CRPS-23', 1, '2026-07-20 14:58:39', '2026-07-21 11:38:52', '', 45),
(9, '02/26', 0, '2026-07-22 09:37:43', '2026-07-22 09:37:54', '', NULL),
(10, 'SLNV-06/01-2025', 1, '2026-07-27 08:49:55', '2026-07-27 08:49:55', '', 45);

-- --------------------------------------------------------

--
-- Table structure for table `vaccine_types`
--

CREATE TABLE `vaccine_types` (
  `id` int(11) NOT NULL,
  `vaccine_name` varchar(100) NOT NULL,
  `target_animal` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `vaccine_types`
--

INSERT INTO `vaccine_types` (`id`, `vaccine_name`, `target_animal`, `description`, `created_at`) VALUES
(4, 'Fowl pox', 'Cattle,Swine,Other', 'test', '2026-05-28 12:40:08'),
(5, 'Gumboro', 'Cattle,Swine', 'test', '2026-05-28 13:04:12'),
(6, 'HS Oil (60 dose)', 'Other', 'test', '2026-05-31 10:54:06'),
(7, 'HS Alum (60 dose)', 'Cattle,Swine,Poultry,Buffalo,Goat,Sheep,Other', 'test', '2026-05-31 10:54:27');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_repairs`
--

CREATE TABLE `vehicle_repairs` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `repair_date` date NOT NULL,
  `repair_done` varchar(255) NOT NULL,
  `repair_description` text DEFAULT NULL,
  `place_of_repair` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `vehicle_repairs`
--

INSERT INTO `vehicle_repairs` (`id`, `vehicle_id`, `user_id`, `repair_date`, `repair_done`, `repair_description`, `place_of_repair`, `amount`, `is_active`, `created_at`) VALUES
(1, 1, 19, '2026-06-30', 'test', '', 'test', '5000.00', 1, '2026-06-30 14:02:26'),
(2, 2, 19, '2026-07-07', 'Full repair', 'test', 'Trincomalee', '5000.00', 1, '2026-07-07 07:55:33');

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

-- --------------------------------------------------------

--
-- Table structure for table `veterinary_range_maps`
--

CREATE TABLE `veterinary_range_maps` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `iframe_url` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `veterinary_range_maps`
--

INSERT INTO `veterinary_range_maps` (`id`, `range_id`, `iframe_url`, `created_at`) VALUES
(1, 1, 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3944.965725775666!2d81.21529707414845!3d8.59928909556909!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3afbbcf9ebe05efb%3A0x554f03dee02fd89e!2sGovernment%20Veterinary%20Office!5e0!3m2!1sen!2slk!4v1782904673982!5m2!1sen!2slk', '2026-07-01 11:16:42');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `advanced_programmes`
--
ALTER TABLE `advanced_programmes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_adv_prog_range` (`range_id`);

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
-- Indexes for table `animal_populations`
--
ALTER TABLE `animal_populations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_range_year_animal_type` (`range_id`,`year`,`animal_type`);

--
-- Indexes for table `annual_feed_production`
--
ALTER TABLE `annual_feed_production`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`);

--
-- Indexes for table `annual_livestock_societies`
--
ALTER TABLE `annual_livestock_societies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_society_range` (`range_id`);

--
-- Indexes for table `annual_milk_collecting_centers`
--
ALTER TABLE `annual_milk_collecting_centers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_collecting_center_range` (`range_id`);

--
-- Indexes for table `annual_milk_processing_centers`
--
ALTER TABLE `annual_milk_processing_centers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_processing_center_range` (`range_id`);

--
-- Indexes for table `annual_milk_sales_centers`
--
ALTER TABLE `annual_milk_sales_centers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sales_center_range` (`range_id`);

--
-- Indexes for table `annual_pasture_fodder_lands`
--
ALTER TABLE `annual_pasture_fodder_lands`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_land_fam_range` (`range_id`);

--
-- Indexes for table `annual_pasture_yields`
--
ALTER TABLE `annual_pasture_yields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_yield_range` (`range_id`);

--
-- Indexes for table `annual_producers_processors`
--
ALTER TABLE `annual_producers_processors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_producers_range` (`range_id`);

--
-- Indexes for table `annual_production_levels`
--
ALTER TABLE `annual_production_levels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prod_lvl_range` (`range_id`);

--
-- Indexes for table `annual_vaccination_targets`
--
ALTER TABLE `annual_vaccination_targets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_range_year_species` (`range_id`,`year`,`animal_type`);

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
-- Indexes for table `breeding_ai_performance`
--
ALTER TABLE `breeding_ai_performance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_breeding_ai_range` (`range_id`),
  ADD KEY `fk_breeding_ai_user` (`created_by`);

--
-- Indexes for table `breeding_calving_performance`
--
ALTER TABLE `breeding_calving_performance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_breeding_calving_range` (`range_id`),
  ADD KEY `fk_breeding_calving_user` (`created_by`);

--
-- Indexes for table `breeding_pd_performance`
--
ALTER TABLE `breeding_pd_performance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_breeding_pd_range` (`range_id`),
  ADD KEY `fk_breeding_pd_user` (`created_by`);

--
-- Indexes for table `building_inventories`
--
ALTER TABLE `building_inventories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `land_asset_id` (`land_asset_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cages`
--
ALTER TABLE `cages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cash_book_summaries`
--
ALTER TABLE `cash_book_summaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cash_book_district` (`district_id`),
  ADD KEY `fk_cash_book_range` (`range_id`),
  ADD KEY `fk_cash_book_user` (`created_by`);

--
-- Indexes for table `casual_vaccinator_deployments`
--
ALTER TABLE `casual_vaccinator_deployments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cattle_voucher_usage`
--
ALTER TABLE `cattle_voucher_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cattle_vouchers_district` (`district_id`),
  ADD KEY `fk_cattle_vouchers_range` (`range_id`),
  ADD KEY `fk_cattle_vouchers_user` (`created_by`);

--
-- Indexes for table `chicks_death_details`
--
ALTER TABLE `chicks_death_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chick_growth_log`
--
ALTER TABLE `chick_growth_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cage_id` (`cage_id`),
  ADD KEY `record_date` (`record_date`);

--
-- Indexes for table `counterfoil_assets`
--
ALTER TABLE `counterfoil_assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `district_id` (`district_id`),
  ADD KEY `range_id` (`range_id`);

--
-- Indexes for table `crop_returns`
--
ALTER TABLE `crop_returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_crop_returns_district` (`district_id`),
  ADD KEY `fk_crop_returns_range` (`range_id`);

--
-- Indexes for table `daily_egg_production`
--
ALTER TABLE `daily_egg_production`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `cage_id` (`cage_id`);

--
-- Indexes for table `daily_egg_sales_returns`
--
ALTER TABLE `daily_egg_sales_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `record_date` (`record_date`);

--
-- Indexes for table `dairy_hub_records`
--
ALTER TABLE `dairy_hub_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `day_old_chicks_distribution`
--
ALTER TABLE `day_old_chicks_distribution`
  ADD PRIMARY KEY (`id`),
  ADD KEY `record_date` (`record_date`);

--
-- Indexes for table `diary_tasks`
--
ALTER TABLE `diary_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_diary_user_link` (`user_id`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `drug_records`
--
ALTER TABLE `drug_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `drug_types`
--
ALTER TABLE `drug_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ear_tag_usage`
--
ALTER TABLE `ear_tag_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ear_tags_district` (`district_id`),
  ADD KEY `fk_ear_tags_range` (`range_id`),
  ADD KEY `fk_ear_tags_user` (`created_by`);

--
-- Indexes for table `furniture_assets`
--
ALTER TABLE `furniture_assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `district_id` (`district_id`),
  ADD KEY `range_id` (`range_id`);

--
-- Indexes for table `hatchery_batches`
--
ALTER TABLE `hatchery_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `hatchery_register`
--
ALTER TABLE `hatchery_register`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cage_id` (`cage_id`),
  ADD KEY `loaded_to_cage_id` (`loaded_to_cage_id`);

--
-- Indexes for table `hatchery_sales`
--
ALTER TABLE `hatchery_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `health_certificate_issues`
--
ALTER TABLE `health_certificate_issues`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_health_cert_district` (`district_id`),
  ADD KEY `fk_health_cert_range` (`range_id`),
  ADD KEY `fk_health_cert_user` (`created_by`);

--
-- Indexes for table `human_populations`
--
ALTER TABLE `human_populations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`);

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
-- Indexes for table `instrument_assets`
--
ALTER TABLE `instrument_assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `district_id` (`district_id`),
  ADD KEY `range_id` (`range_id`);

--
-- Indexes for table `land_assets`
--
ALTER TABLE `land_assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `district_id` (`district_id`),
  ADD KEY `range_id` (`range_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_leave_user` (`user_id`);

--
-- Indexes for table `letter_h_accounts`
--
ALTER TABLE `letter_h_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_letter_h_district` (`district_id`),
  ADD KEY `fk_letter_h_range` (`range_id`),
  ADD KEY `fk_letter_h_user` (`created_by`);

--
-- Indexes for table `livestock_societies`
--
ALTER TABLE `livestock_societies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `machinery_assets`
--
ALTER TABLE `machinery_assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `district_id` (`district_id`),
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
-- Indexes for table `milk_collecting_centers`
--
ALTER TABLE `milk_collecting_centers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `milk_processing_centers`
--
ALTER TABLE `milk_processing_centers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `milk_product_sales_centers`
--
ALTER TABLE `milk_product_sales_centers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monthly_vaccine_balances`
--
ALTER TABLE `monthly_vaccine_balances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vac_bal_district` (`district_id`),
  ADD KEY `fk_vac_bal_range` (`range_id`),
  ADD KEY `fk_vac_bal_user` (`created_by`);

--
-- Indexes for table `month_old_chicks_distribution`
--
ALTER TABLE `month_old_chicks_distribution`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cage_id` (`cage_id`),
  ADD KEY `record_date` (`record_date`);

--
-- Indexes for table `parent_stock_flocks`
--
ALTER TABLE `parent_stock_flocks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pasture_fodder_lands`
--
ALTER TABLE `pasture_fodder_lands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `production_activity_targets`
--
ALTER TABLE `production_activity_targets`
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
-- Indexes for table `regional_farms`
--
ALTER TABLE `regional_farms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registered_vehicles`
--
ALTER TABLE `registered_vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_vehicle_num` (`vehicle_number`),
  ADD UNIQUE KEY `idx_chassis_num` (`chassis_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `district_id` (`district_id`),
  ADD KEY `range_id` (`range_id`);

--
-- Indexes for table `regulatory_records`
--
ALTER TABLE `regulatory_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `section_e`
--
ALTER TABLE `section_e`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prod_rec_district` (`district_id`),
  ADD KEY `fk_prod_rec_range` (`range_id`),
  ADD KEY `fk_prod_rec_category` (`category_id`),
  ADD KEY `fk_prod_rec_item` (`item_id`);

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
-- Indexes for table `sms_immunization`
--
ALTER TABLE `sms_immunization`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `stock_balance_logs`
--
ALTER TABLE `stock_balance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `flock_id` (`flock_id`);

--
-- Indexes for table `strategic_action_indicators`
--
ALTER TABLE `strategic_action_indicators`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `training_centers`
--
ALTER TABLE `training_centers`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `fk_user_range` (`range_id`),
  ADD KEY `idx_user_farm` (`farm_id`);

--
-- Indexes for table `vaccine_batches`
--
ALTER TABLE `vaccine_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_batch_number_unique` (`batch_number`),
  ADD KEY `fk_vaccine_batch_user` (`user_id`);

--
-- Indexes for table `vaccine_types`
--
ALTER TABLE `vaccine_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_vaccine_unique` (`vaccine_name`,`target_animal`);

--
-- Indexes for table `vehicle_repairs`
--
ALTER TABLE `vehicle_repairs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `veterinary_ranges`
--
ALTER TABLE `veterinary_ranges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `veterinary_range_maps`
--
ALTER TABLE `veterinary_range_maps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `range_id` (`range_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `advanced_programmes`
--
ALTER TABLE `advanced_programmes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `amended_programmes`
--
ALTER TABLE `amended_programmes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `animal_health_records`
--
ALTER TABLE `animal_health_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `animal_populations`
--
ALTER TABLE `animal_populations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `annual_feed_production`
--
ALTER TABLE `annual_feed_production`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `annual_livestock_societies`
--
ALTER TABLE `annual_livestock_societies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `annual_milk_collecting_centers`
--
ALTER TABLE `annual_milk_collecting_centers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `annual_milk_processing_centers`
--
ALTER TABLE `annual_milk_processing_centers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `annual_milk_sales_centers`
--
ALTER TABLE `annual_milk_sales_centers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `annual_pasture_fodder_lands`
--
ALTER TABLE `annual_pasture_fodder_lands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `annual_pasture_yields`
--
ALTER TABLE `annual_pasture_yields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `annual_producers_processors`
--
ALTER TABLE `annual_producers_processors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `annual_production_levels`
--
ALTER TABLE `annual_production_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `annual_vaccination_targets`
--
ALTER TABLE `annual_vaccination_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=164;

--
-- AUTO_INCREMENT for table `breeding_ai_performance`
--
ALTER TABLE `breeding_ai_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `breeding_calving_performance`
--
ALTER TABLE `breeding_calving_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `breeding_pd_performance`
--
ALTER TABLE `breeding_pd_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `building_inventories`
--
ALTER TABLE `building_inventories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cages`
--
ALTER TABLE `cages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cash_book_summaries`
--
ALTER TABLE `cash_book_summaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `casual_vaccinator_deployments`
--
ALTER TABLE `casual_vaccinator_deployments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cattle_voucher_usage`
--
ALTER TABLE `cattle_voucher_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `chicks_death_details`
--
ALTER TABLE `chicks_death_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `chick_growth_log`
--
ALTER TABLE `chick_growth_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `counterfoil_assets`
--
ALTER TABLE `counterfoil_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `crop_returns`
--
ALTER TABLE `crop_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `daily_egg_production`
--
ALTER TABLE `daily_egg_production`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `daily_egg_sales_returns`
--
ALTER TABLE `daily_egg_sales_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `dairy_hub_records`
--
ALTER TABLE `dairy_hub_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `day_old_chicks_distribution`
--
ALTER TABLE `day_old_chicks_distribution`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `diary_tasks`
--
ALTER TABLE `diary_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `drug_records`
--
ALTER TABLE `drug_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `drug_types`
--
ALTER TABLE `drug_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ear_tag_usage`
--
ALTER TABLE `ear_tag_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `furniture_assets`
--
ALTER TABLE `furniture_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `hatchery_batches`
--
ALTER TABLE `hatchery_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `hatchery_register`
--
ALTER TABLE `hatchery_register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hatchery_sales`
--
ALTER TABLE `hatchery_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `health_certificate_issues`
--
ALTER TABLE `health_certificate_issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `human_populations`
--
ALTER TABLE `human_populations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
-- AUTO_INCREMENT for table `instrument_assets`
--
ALTER TABLE `instrument_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `land_assets`
--
ALTER TABLE `land_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `letter_h_accounts`
--
ALTER TABLE `letter_h_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `livestock_societies`
--
ALTER TABLE `livestock_societies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `machinery_assets`
--
ALTER TABLE `machinery_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- AUTO_INCREMENT for table `milk_collecting_centers`
--
ALTER TABLE `milk_collecting_centers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `milk_processing_centers`
--
ALTER TABLE `milk_processing_centers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `milk_product_sales_centers`
--
ALTER TABLE `milk_product_sales_centers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `monthly_vaccine_balances`
--
ALTER TABLE `monthly_vaccine_balances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `month_old_chicks_distribution`
--
ALTER TABLE `month_old_chicks_distribution`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `parent_stock_flocks`
--
ALTER TABLE `parent_stock_flocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pasture_fodder_lands`
--
ALTER TABLE `pasture_fodder_lands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `production_activity_targets`
--
ALTER TABLE `production_activity_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `production_categories`
--
ALTER TABLE `production_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `production_items`
--
ALTER TABLE `production_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

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
-- AUTO_INCREMENT for table `regional_farms`
--
ALTER TABLE `regional_farms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `registered_vehicles`
--
ALTER TABLE `registered_vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `regulatory_records`
--
ALTER TABLE `regulatory_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `section_e`
--
ALTER TABLE `section_e`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT for table `sms_immunization`
--
ALTER TABLE `sms_immunization`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_balance_logs`
--
ALTER TABLE `stock_balance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `strategic_action_indicators`
--
ALTER TABLE `strategic_action_indicators`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `training_centers`
--
ALTER TABLE `training_centers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `vaccine_batches`
--
ALTER TABLE `vaccine_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `vaccine_types`
--
ALTER TABLE `vaccine_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `vehicle_repairs`
--
ALTER TABLE `vehicle_repairs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `veterinary_ranges`
--
ALTER TABLE `veterinary_ranges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `veterinary_range_maps`
--
ALTER TABLE `veterinary_range_maps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `advanced_programmes`
--
ALTER TABLE `advanced_programmes`
  ADD CONSTRAINT `fk_adv_prog_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `animal_populations`
--
ALTER TABLE `animal_populations`
  ADD CONSTRAINT `animal_populations_ibfk_1` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `annual_feed_production`
--
ALTER TABLE `annual_feed_production`
  ADD CONSTRAINT `fk_feed_prod_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `annual_livestock_societies`
--
ALTER TABLE `annual_livestock_societies`
  ADD CONSTRAINT `fk_society_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `annual_milk_collecting_centers`
--
ALTER TABLE `annual_milk_collecting_centers`
  ADD CONSTRAINT `fk_collecting_center_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `annual_milk_processing_centers`
--
ALTER TABLE `annual_milk_processing_centers`
  ADD CONSTRAINT `fk_processing_center_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `annual_milk_sales_centers`
--
ALTER TABLE `annual_milk_sales_centers`
  ADD CONSTRAINT `fk_sales_center_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `annual_pasture_fodder_lands`
--
ALTER TABLE `annual_pasture_fodder_lands`
  ADD CONSTRAINT `fk_land_fam_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `annual_pasture_yields`
--
ALTER TABLE `annual_pasture_yields`
  ADD CONSTRAINT `fk_yield_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `annual_producers_processors`
--
ALTER TABLE `annual_producers_processors`
  ADD CONSTRAINT `fk_producers_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `annual_production_levels`
--
ALTER TABLE `annual_production_levels`
  ADD CONSTRAINT `fk_prod_lvl_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `annual_vaccination_targets`
--
ALTER TABLE `annual_vaccination_targets`
  ADD CONSTRAINT `fk_vaccination_targets_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`);

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
-- Constraints for table `breeding_ai_performance`
--
ALTER TABLE `breeding_ai_performance`
  ADD CONSTRAINT `fk_breeding_ai_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_breeding_ai_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `breeding_calving_performance`
--
ALTER TABLE `breeding_calving_performance`
  ADD CONSTRAINT `fk_breeding_calving_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_breeding_calving_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `breeding_pd_performance`
--
ALTER TABLE `breeding_pd_performance`
  ADD CONSTRAINT `fk_breeding_pd_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_breeding_pd_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cash_book_summaries`
--
ALTER TABLE `cash_book_summaries`
  ADD CONSTRAINT `fk_cash_book_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cash_book_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cash_book_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cattle_voucher_usage`
--
ALTER TABLE `cattle_voucher_usage`
  ADD CONSTRAINT `fk_cattle_vouchers_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cattle_vouchers_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cattle_vouchers_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chick_growth_log`
--
ALTER TABLE `chick_growth_log`
  ADD CONSTRAINT `fk_growth_cage` FOREIGN KEY (`cage_id`) REFERENCES `cages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `crop_returns`
--
ALTER TABLE `crop_returns`
  ADD CONSTRAINT `fk_crop_returns_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_crop_returns_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `daily_egg_production`
--
ALTER TABLE `daily_egg_production`
  ADD CONSTRAINT `daily_egg_production_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `vaccine_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `daily_egg_production_ibfk_2` FOREIGN KEY (`cage_id`) REFERENCES `cages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `diary_tasks`
--
ALTER TABLE `diary_tasks`
  ADD CONSTRAINT `fk_diary_user_link` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ear_tag_usage`
--
ALTER TABLE `ear_tag_usage`
  ADD CONSTRAINT `fk_ear_tags_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ear_tags_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ear_tags_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `hatchery_register`
--
ALTER TABLE `hatchery_register`
  ADD CONSTRAINT `fk_hatchery_cage` FOREIGN KEY (`cage_id`) REFERENCES `cages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_hatchery_target_cage` FOREIGN KEY (`loaded_to_cage_id`) REFERENCES `cages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `health_certificate_issues`
--
ALTER TABLE `health_certificate_issues`
  ADD CONSTRAINT `fk_health_cert_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_health_cert_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_health_cert_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `letter_h_accounts`
--
ALTER TABLE `letter_h_accounts`
  ADD CONSTRAINT `fk_letter_h_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_letter_h_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_letter_h_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `monthly_vaccine_balances`
--
ALTER TABLE `monthly_vaccine_balances`
  ADD CONSTRAINT `fk_vac_bal_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vac_bal_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vac_bal_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `month_old_chicks_distribution`
--
ALTER TABLE `month_old_chicks_distribution`
  ADD CONSTRAINT `fk_month_dist_cage` FOREIGN KEY (`cage_id`) REFERENCES `cages` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `section_e`
--
ALTER TABLE `section_e`
  ADD CONSTRAINT `fk_prod_rec_category` FOREIGN KEY (`category_id`) REFERENCES `production_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prod_rec_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prod_rec_item` FOREIGN KEY (`item_id`) REFERENCES `production_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prod_rec_range` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_farm` FOREIGN KEY (`farm_id`) REFERENCES `regional_farms` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `vaccine_batches`
--
ALTER TABLE `vaccine_batches`
  ADD CONSTRAINT `fk_vaccine_batch_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
