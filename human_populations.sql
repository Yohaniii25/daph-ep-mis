-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 04, 2026 at 07:40 AM
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
(1, 1, 2025, 'Sinhala', 'Male', 1900, '2026-07-01 11:42:10'),
(2, 1, 2025, 'Sinhala', 'Female', 1800, '2026-07-01 11:42:10'),
(3, 1, 2025, 'Sinhala', 'Households', 900, '2026-07-01 11:42:10'),
(4, 1, 2025, 'Tamil', 'Male', 1000, '2026-07-01 11:42:10'),
(5, 1, 2025, 'Tamil', 'Female', 900, '2026-07-01 11:42:10'),
(6, 1, 2025, 'Tamil', 'Households', 450, '2026-07-01 11:42:10'),
(7, 1, 2025, 'Muslim', 'Male', 520, '2026-07-01 11:42:10'),
(8, 1, 2025, 'Muslim', 'Female', 500, '2026-07-01 11:42:10'),
(9, 1, 2025, 'Muslim', 'Households', 255, '2026-07-01 11:42:10'),
(10, 1, 2024, 'Sinhala', 'Male', 1750, '2026-07-01 11:42:10'),
(11, 1, 2024, 'Sinhala', 'Female', 1680, '2026-07-01 11:42:10'),
(12, 1, 2024, 'Sinhala', 'Households', 830, '2026-07-01 11:42:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `human_populations`
--
ALTER TABLE `human_populations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `human_populations`
--
ALTER TABLE `human_populations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
