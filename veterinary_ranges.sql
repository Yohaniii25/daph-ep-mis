-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 01, 2026 at 12:53 PM
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
-- Indexes for table `veterinary_ranges`
--
ALTER TABLE `veterinary_ranges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `veterinary_ranges`
--
ALTER TABLE `veterinary_ranges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
