-- phpMyAdmin SQL Dump
-- version 5.1.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 09:10 AM
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
(14, 1, 'Mrs H. Silva', 'PDO', '022', '0771234579', '2026-03-12', 'silva.h@daph.gov.lk', 'Active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `office_details`
--
ALTER TABLE `office_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `range_id` (`range_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `office_details`
--
ALTER TABLE `office_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `office_details`
--
ALTER TABLE `office_details`
  ADD CONSTRAINT `office_details_ibfk_1` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
