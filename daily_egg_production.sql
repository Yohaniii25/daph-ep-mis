-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 23, 2026 at 10:38 AM
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
  `hatchable_eggs` int(11) DEFAULT 0,
  `table_eggs` int(11) DEFAULT 0,
  `cracked_eggs` int(11) DEFAULT 0,
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

INSERT INTO `daily_egg_production` (`id`, `batch_id`, `cage_id`, `collection_date`, `pullets`, `cockerels`, `total_eggs`, `hatchable_eggs`, `table_eggs`, `cracked_eggs`, `loading_date`, `hatchery_name`, `eggs_loaded`, `hatching_date`, `hatched_eggs`, `hatchability_percentage`, `created_at`) VALUES
(4, 8, 2, '2026-07-23', 89, 60, 210, 70, 70, 70, '0000-00-00', 'Test', 90, '2026-07-23', 80, '88.00', '2026-07-23 07:18:51'),
(5, 8, 1, '2026-07-23', 90, 80, 20, 8, 6, 6, '0000-00-00', 'Test', 8, '2026-07-24', 8, '100.00', '2026-07-23 08:34:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `daily_egg_production`
--
ALTER TABLE `daily_egg_production`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `cage_id` (`cage_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `daily_egg_production`
--
ALTER TABLE `daily_egg_production`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `daily_egg_production`
--
ALTER TABLE `daily_egg_production`
  ADD CONSTRAINT `daily_egg_production_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `vaccine_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `daily_egg_production_ibfk_2` FOREIGN KEY (`cage_id`) REFERENCES `cages` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
