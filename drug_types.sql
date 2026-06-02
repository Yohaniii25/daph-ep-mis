-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 02, 2026 at 08:06 AM
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
-- Table structure for table `drug_types`
--

CREATE TABLE `drug_types` (
  `id` int(11) NOT NULL,
  `vaccine_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_animal` set('Cattle','Dairy Cows','Buffalo','Goats','Poultry','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `drug_types`
--

INSERT INTO `drug_types` (`id`, `vaccine_name`, `target_animal`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Oxytocine inj', 'Cattle,Dairy Cows,Buffalo,Goats', '', '2026-06-01 14:09:23', '2026-06-01 14:09:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `drug_types`
--
ALTER TABLE `drug_types`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `drug_types`
--
ALTER TABLE `drug_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
