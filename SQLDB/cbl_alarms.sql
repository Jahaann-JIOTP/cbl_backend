-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 16, 2025 at 06:59 AM
-- Server version: 10.3.15-MariaDB
-- PHP Version: 7.1.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cbl_alarms`
--

-- --------------------------------------------------------

--
-- Table structure for table `alarms`
--

CREATE TABLE `alarms` (
  `id` int(11) NOT NULL,
  `Source` varchar(255) DEFAULT NULL,
  `Status` varchar(255) DEFAULT NULL,
  `Value` text DEFAULT NULL,
  `Time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `db_value` float DEFAULT NULL,
  `url_value` float DEFAULT NULL,
  `status1` varchar(255) DEFAULT NULL,
  `alarm_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `meter_data`
--

CREATE TABLE `meter_data` (
  `id` int(11) NOT NULL,
  `Source` varchar(255) DEFAULT NULL,
  `Status` varchar(255) DEFAULT NULL,
  `Value` text DEFAULT NULL,
  `Time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `meter_data`
--

INSERT INTO `meter_data` (`id`, `Source`, `Status`, `Value`, `Time`) VALUES
(27, 'New Centac Comp#2', 'High Voltage', '440', '2025-01-09 16:16:55'),
(28, 'Compressor Aux', 'High Voltage', '440', '2025-01-09 17:08:05'),
(30, 'Kaeser Compressor', 'High Voltage', '440', '2025-01-03 17:10:39'),
(31, 'Dryer', 'High Voltage', '440', '2025-01-03 17:10:45'),
(32, 'Ozen 350', 'High Voltage', '440', '2025-01-03 17:10:52'),
(33, 'Atlas Copco', 'High Voltage', '440', '2025-01-03 17:11:00'),
(34, 'Ganzair Compressor', 'High Voltage', '440', '2025-01-03 17:11:14'),
(36, 'new cantac compressor#1', 'High Voltage', '440', '2025-01-03 17:12:55'),
(38, 'New Centac Comp#2', 'Low Voltage', '370', '2025-01-14 09:34:40'),
(39, 'Compressor Aux', 'Low Voltage', '370', '2025-01-03 17:13:40'),
(41, 'Kaeser Compressor', 'Low Voltage', '370', '2025-01-13 14:49:34'),
(42, 'Dryer', 'Low Voltage', '370', '2025-01-14 09:24:58'),
(43, 'Ozen 350', 'Low Voltage', '370', '2025-01-03 17:14:07'),
(44, 'Atlas Copco', 'Low Voltage', '370', '2025-01-14 09:34:59'),
(45, 'Ganzair Compressor', 'Low Voltage', '370', '2025-01-14 10:33:43'),
(47, 'new cantac compressor#1', 'Low Voltage', '370', '2025-01-11 11:05:21'),
(50, 'New Centac Comp#2', 'High Current', '370', '2025-01-13 16:04:49'),
(51, 'Compressor Aux', 'High Current', '84', '2025-01-09 18:00:04'),
(52, 'Kaeser Compressor', 'High Current', '311', '2025-01-09 18:01:10'),
(53, 'Dryer', 'High Current', '100', '2025-01-09 18:01:33'),
(54, 'Ozen 350', 'High Current', '436', '2025-01-09 18:01:48'),
(55, 'Atlas Copco', 'High Current', '429', '2025-01-09 18:02:04'),
(56, 'Ganzair Compressor', 'High Current', '110', '2025-01-09 18:02:23'),
(57, 'new cantac compressor#1', 'High Current', '1000', '2025-01-09 18:03:06'),
(60, 'DSD 281(Kaeser)+ ML-15', 'Low Voltage', '370', '2025-01-13 16:47:39'),
(61, 'DSD 281(Kaeser)+ ML-15', 'High Current', '1958', '2025-01-11 11:31:19'),
(62, 'DSD 281(Kaeser)+ ML-15', 'High Voltage', '440', '2025-01-11 11:31:34'),
(63, 'ML-132 compressor#1', 'Low Voltage', '370', '2025-01-14 10:20:21'),
(64, 'ML-132 compressor#1', 'High Voltage', '440', '2025-01-14 09:41:42'),
(65, 'ML-132 compressor#1', 'High Current', '255', '2025-01-14 09:42:08');

-- --------------------------------------------------------

--
-- Table structure for table `recentalarms`
--

CREATE TABLE `recentalarms` (
  `id` int(11) NOT NULL,
  `meter` varchar(255) NOT NULL,
  `option_selected` varchar(255) NOT NULL,
  `db_value` float NOT NULL,
  `url_value` float NOT NULL,
  `status` varchar(255) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `total_duration` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alarms`
--
ALTER TABLE `alarms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meter_data`
--
ALTER TABLE `meter_data`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recentalarms`
--
ALTER TABLE `recentalarms`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alarms`
--
ALTER TABLE `alarms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meter_data`
--
ALTER TABLE `meter_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `recentalarms`
--
ALTER TABLE `recentalarms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
