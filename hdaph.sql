-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 31, 2026 at 05:01 PM
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
(1, 1, 1, 2026, '5000.00');

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

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `livestock_targets`
--
ALTER TABLE `livestock_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `monthly_production_records`
--
ALTER TABLE `monthly_production_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for dumped tables
--

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
-- Constraints for table `production_items`
--
ALTER TABLE `production_items`
  ADD CONSTRAINT `production_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `production_categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
