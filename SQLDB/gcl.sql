-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 15, 2025 at 01:19 PM
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
-- Database: `gcl`
--

-- --------------------------------------------------------

--
-- Table structure for table `alarms`
--

CREATE TABLE `alarms` (
  `id` int(11) NOT NULL,
  `meter` varchar(255) DEFAULT NULL,
  `option_selected` varchar(255) DEFAULT NULL,
  `value` float DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `db_value` float DEFAULT NULL,
  `url_value` float DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `alarm_count` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `alarms`
--

INSERT INTO `alarms` (`id`, `meter`, `option_selected`, `value`, `created_at`, `db_value`, `url_value`, `status`, `alarm_count`) VALUES
(1, 'Meter 1', 'Low Voltage', 2576, '2024-11-19 10:42:22', 2576, 616.4, 'normal', 1),
(2, 'Meter 1', 'High Current', 2468, '2024-11-19 10:42:22', 2468, 616.4, 'normal', 1),
(3, 'Meter 1', 'High Current', 2589, '2024-11-19 10:42:22', 2589, 616.4, 'normal', 1),
(4, 'Meter 1', 'Low Voltage', 1253, '2024-11-19 10:42:22', 1253, 616.4, 'normal', 1),
(5, 'Meter 1', 'High Current', 9090, '2024-11-19 10:49:29', 9090, 529.6, 'normal', 1),
(6, 'Meter 1', 'High Current', 2563, '2024-11-19 10:52:34', 2563, 566.7, 'normal', 1),
(7, 'Meter 1', 'High Current', 5637, '2024-11-19 10:55:53', 5637, 566.7, 'normal', 1),
(8, 'Meter 2', 'Low Voltage', 576, '2024-11-19 12:59:12', 576, 43.7, 'normal', 1),
(9, 'Meter 2', 'Low Voltage', 565656, '2024-11-19 12:59:12', 565656, 43.7, 'normal', 1),
(10, 'Meter 2', 'Low Voltage', 563, '2024-11-19 12:59:12', 563, 43.7, 'normal', 1),
(11, 'Meter 1', 'High Current', 6923, '2024-11-20 04:28:38', 6923, 278.7, 'normal', 1),
(12, 'Meter 1', 'High Current', 12563, '2024-11-20 06:33:15', 12563, 278.7, 'normal', 1),
(13, 'Meter 3', 'High Current', 256, '2024-11-20 06:48:56', 256, 23.3, 'normal', 1),
(14, 'Meter 3', 'High Current', 2468, '2024-11-20 06:48:56', 2468, 23.3, 'normal', 1),
(15, 'Meter 1', 'High Current', 1256, '2024-11-20 06:48:56', 1256, 278.7, 'normal', 1),
(16, 'Meter 3', 'High Current', 6555, '2024-11-20 06:49:19', 6555, 23.3, 'normal', 1),
(17, 'Meter 3', 'High Current', 1256, '2024-11-20 08:07:04', 1256, 23.3, 'normal', 1),
(18, 'Meter 1', 'Low Voltage', 4569, '2024-11-20 11:02:39', 4569, 278.7, 'normal', 1),
(21, 'Meter 2', 'Low Voltage', 2568, '2024-11-21 06:35:06', 2568, 40, 'normal', 1),
(22, 'Meter 2', 'Low Voltage', 12569, '2024-11-21 06:35:06', 12569, 40, 'normal', 1),
(23, 'Meter 1', 'Low Voltage', 6868, '2024-11-21 06:35:07', 6868, 659.6, 'normal', 1),
(24, 'Meter 1', 'Low Voltage', 62, '2024-11-21 06:35:07', 62, 659.6, 'exceeded', 1),
(25, 'Meter 1', 'High Current', 623, '2024-11-21 06:46:28', 623, 541.2, 'normal', 1),
(26, 'Meter 1', 'High Current', 6060, '2024-11-21 07:21:11', 6060, 410.5, 'normal', 1),
(27, 'Meter 1', 'Low Voltage', 5236, '2024-11-21 09:41:36', 5236, 633.8, 'normal', 1),
(28, 'Meter 2', 'High Current', 989, '2024-11-21 09:43:47', 989, 39.9, 'normal', 1),
(29, 'Meter 2', 'High Current', 650, '2024-11-21 10:01:12', 650, 6.2, 'normal', 1),
(30, 'Meter 1', 'Low Voltage', 963, '2024-11-21 10:01:12', 963, 629.5, 'normal', 1),
(31, 'Meter 1', 'High Voltage', 6321, '2024-11-21 10:03:56', 6321, 547.3, 'normal', 1),
(32, 'Meter 3', 'High Current', 6565, '2024-11-21 12:46:28', 6565, 29.6, 'normal', 1),
(33, 'Meter 3', 'Low Voltage', 102020, '2024-11-21 12:46:28', 102020, 29.6, 'normal', 1),
(34, 'Meter 1', 'Low Voltage', 1235, '2024-11-22 04:44:25', 1235, 452.4, 'normal', 1),
(35, 'Meter 2', 'Low Voltage', 569, '2024-11-22 05:00:26', 569, 7.2, 'normal', 1),
(36, 'Meter 1', 'High Current', 50623, '2024-11-22 05:00:26', 50623, 462.6, 'normal', 1),
(37, 'Meter 1', 'Low Voltage', 263, '2024-11-22 05:45:53', 263, 454.3, 'exceeded', 1),
(38, 'Meter 2', 'Low Voltage', 2468, '2024-11-22 05:46:09', 2468, 5.2, 'normal', 1),
(39, 'Meter 1', 'Low Voltage', 856, '2024-11-22 05:50:23', 856, 456.5, 'normal', 1),
(40, 'Meter 2', 'High Current', 963, '2024-11-22 05:50:48', 963, 32.8, 'normal', 1),
(41, 'Meter 2', 'High Current', 963, '2024-11-22 05:50:48', 963, 32.8, 'normal', 1),
(42, 'Meter 2', 'High Current', 2563, '2024-11-22 05:52:14', 2563, 43.4, 'normal', 1),
(43, 'Meter 2', 'High Voltage', 639, '2024-11-22 05:52:44', 639, 41.4, 'normal', 1),
(44, 'Meter 1', 'High Current', 656, '2024-11-22 07:03:25', 656, 438.2, 'normal', 1),
(45, 'Meter 3', 'High Voltage', 500, '2024-11-26 12:28:23', 500, 25, 'normal', 1),
(46, 'Meter 1', 'Low Voltage', 4568, '2024-11-26 12:28:23', 4568, 491.8, 'normal', 1),
(47, 'Meter 1', 'High Current', 5989, '2024-11-26 12:28:23', 5989, 491.8, 'normal', 1),
(48, 'Meter 1', 'Low Voltage', 2468, '2024-11-26 12:28:23', 2468, 491.8, 'normal', 1),
(49, 'Meter 1', 'Low Voltage', 5698, '2024-11-26 12:28:23', 5698, 491.8, 'normal', 1),
(50, 'Meter 2', 'Low Voltage', 5756, '2024-11-26 12:28:46', 5756, 6.1, 'normal', 1),
(51, 'Meter 1', 'High Current', 4568, '2024-11-26 12:37:54', 4568, 504.5, 'normal', 1),
(52, 'Meter 1', 'Low Voltage', 9898, '2024-11-26 12:37:54', 9898, 504.5, 'normal', 1),
(53, 'Meter 1', 'High Current', 5656, '2024-11-26 12:37:54', 5656, 504.5, 'normal', 1),
(54, 'Meter 1', 'Low Voltage', 8469, '2024-11-26 12:37:54', 8469, 504.5, 'normal', 1),
(55, 'Meter 1', 'High Current', 8985, '2024-11-26 12:37:54', 8985, 504.5, 'normal', 1),
(56, 'Meter 1', 'Low Voltage', 5468, '2024-11-26 12:39:21', 5468, 495.4, 'normal', 1),
(57, 'Meter 1', 'Low Voltage', 2123, '2024-11-26 12:42:03', 2123, 487.7, 'normal', 1),
(58, 'Meter 2', 'Low Voltage', 589, '2024-11-26 12:42:03', 589, 6.2, 'normal', 1),
(59, 'Meter 1', 'Low Voltage', 5689, '2024-11-26 12:43:32', 5689, 495, 'normal', 1),
(60, 'Meter 1', 'Low Voltage', 65623, '2024-11-26 12:49:48', 65623, 495.6, 'normal', 1),
(61, 'Meter 1', 'Low Voltage', 589, '2024-11-26 12:49:48', 589, 495.6, 'normal', 1),
(62, 'Meter 3', 'High Current', 5789, '2024-11-26 12:49:48', 5789, 25.8, 'normal', 1),
(63, 'Meter 1', 'High Current', 5689, '2024-11-26 12:50:47', 5689, 459.1, 'normal', 1),
(64, 'Meter 1', 'High Current', 8589, '2024-11-26 12:53:13', 8589, 485.3, 'normal', 1),
(65, 'Meter 2', 'High Current', 5656, '2024-11-26 12:59:01', 5656, 21.5, 'normal', 1),
(66, 'Meter 1', 'High Voltage', 5655, '2024-11-26 12:59:01', 5655, 493.1, 'normal', 1),
(67, 'Meter 1', 'High Current', 4213, '2024-11-26 13:00:11', 4213, 481.9, 'normal', 1),
(68, 'Meter 1', 'High Current', 5463, '2024-11-26 13:05:08', 5463, 458.9, 'Alarm: Both DB and URL values exceed the threshold.', 1),
(69, 'Meter 2', 'Low Voltage', 8989, '2024-11-26 13:05:08', 8989, 7.2, 'Alarm: Both DB and URL values exceed the threshold.', 1),
(70, 'Meter 1', 'High Current', 565, '2024-11-26 13:05:08', 565, 458.9, 'Alarm: Both DB and URL values exceed the threshold.', 1),
(71, 'Meter 1', 'Low Voltage', 8590, '2024-11-26 13:05:32', 8590, 455.1, 'Alarm: Both DB and URL values exceed the threshold.', 1),
(72, 'Meter 1', 'Low Voltage', 687, '2024-12-02 07:34:49', 687, 367.1, 'Alarm: Both DB and URL values exceed the threshold.', 1);

-- --------------------------------------------------------

--
-- Table structure for table `meterdata`
--

CREATE TABLE `meterdata` (
  `id` int(11) NOT NULL,
  `meter` varchar(255) DEFAULT NULL,
  `option_selected` varchar(255) DEFAULT NULL,
  `value` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `meterdata`
--

INSERT INTO `meterdata` (`id`, `meter`, `option_selected`, `value`, `created_at`) VALUES
(5265, 'Meter 3', 'High Current', '256.00', '2024-11-19 04:40:50'),
(5266, 'Meter 3', 'High Current', '256.00', '2024-11-19 09:41:08'),
(5267, 'Meter 1', 'Low Voltage', '2576.00', '2024-11-19 04:41:56'),
(5268, 'Meter 1', 'Low Voltage', '2576.00', '2024-11-19 09:42:08'),
(5272, 'Meter 1', 'Low Voltage', '1253.00', '2024-11-19 05:17:03'),
(5273, 'Meter 2', 'Low Voltage', '576.00', '2024-11-19 05:43:27'),
(5274, 'Meter 2', 'Low Voltage', '565656.00', '2024-11-19 05:45:19'),
(5275, 'Meter 1', 'High Current', '9090.00', '2024-11-19 05:48:49'),
(5276, 'Meter 2', 'Low Voltage', '563.00', '2024-11-19 05:49:52'),
(5277, 'Meter 1', 'High Current', '2563.00', '2024-11-19 05:52:22'),
(5278, 'Meter 1', 'High Current', '5637.00', '2024-11-19 05:55:29'),
(5279, 'Meter 1', 'High Current', '5637.00', '2024-11-19 07:59:08'),
(5280, 'Meter 1', 'High Current', '6923.00', '2024-11-19 23:28:10'),
(5281, 'Meter 2', 'Low Voltage', '2568.00', '2024-11-19 23:29:27'),
(5282, 'Meter 1', 'High Current', '12563.00', '2024-11-20 01:33:03'),
(5283, 'Meter 2', 'Low Voltage', '12569.00', '2024-11-20 01:33:11'),
(5284, 'Meter 3', 'High Current', '2468.00', '2024-11-20 01:33:57'),
(5285, 'Meter 1', 'High Current', '1256.00', '2024-11-20 01:48:50'),
(5286, 'Meter 3', 'High Current', '6555.00', '2024-11-20 01:49:12'),
(5287, 'Meter 3', 'High Current', '1256.00', '2024-11-20 03:06:58'),
(5288, 'Meter 1', 'Low Voltage', '4569.00', '2024-11-20 06:02:23'),
(5293, 'Meter 3', 'High Current', '6565.00', '2024-11-21 00:56:56'),
(5294, 'Meter 1', 'Low Voltage', '62.00', '2024-11-21 00:58:04'),
(5295, 'Meter 1', 'High Current', '623.00', '2024-11-21 01:45:45'),
(5296, 'Meter 1', 'High Current', '6060.00', '2024-11-21 02:19:59'),
(5297, 'Meter 1', 'Low Voltage', '5236.00', '2024-11-21 04:41:29'),
(5298, 'Meter 2', 'High Current', '989.00', '2024-11-21 04:43:41'),
(5299, 'Meter 3', 'Low Voltage', '102020.00', '2024-11-21 04:44:17'),
(5300, 'Meter 2', 'High Current', '650.00', '2024-11-21 05:00:24'),
(5301, 'Meter 1', 'Low Voltage', '963.00', '2024-11-21 05:01:08'),
(5302, 'Meter 1', 'High Voltage', '6321.00', '2024-11-21 05:03:53'),
(5303, 'Meter 1', 'Low Voltage', '1235.00', '2024-11-21 23:42:55'),
(5304, 'Meter 2', 'Low Voltage', '569.00', '2024-11-21 23:57:37'),
(5305, 'Meter 1', 'High Current', '50623.00', '2024-11-22 00:00:06'),
(5306, 'Meter 1', 'High Current', '1256.00', '2024-11-22 00:15:45'),
(5307, 'Meter 1', 'Low Voltage', '263.00', '2024-11-22 00:27:12'),
(5308, 'Meter 1', 'High Current', '1256.00', '2024-11-22 00:33:10'),
(5309, 'Meter 2', 'Low Voltage', '2468.00', '2024-11-22 00:37:55'),
(5310, 'Meter 1', 'High Current', '2563.00', '2024-11-22 00:40:03'),
(5311, 'Meter 1', 'High Current', '2563.00', '2024-11-22 00:41:34'),
(5312, 'Meter 1', 'Low Voltage', '856.00', '2024-11-22 00:50:20'),
(5313, 'Meter 2', 'High Current', '963.00', '2024-11-22 00:50:45'),
(5314, 'Meter 2', 'High Current', '2563.00', '2024-11-22 00:52:03'),
(5315, 'Meter 2', 'High Voltage', '639.00', '2024-11-22 00:52:41'),
(5316, 'Meter 1', 'High Current', '656.00', '2024-11-22 02:03:22'),
(5317, 'Meter 3', 'High Voltage', '500.00', '2024-11-22 02:45:01'),
(5318, 'Meter 1', 'Low Voltage', '4568.00', '2024-11-26 07:24:55'),
(5319, 'Meter 1', 'High Current', '5989.00', '2024-11-26 07:25:31'),
(5320, 'Meter 1', 'Low Voltage', '2468.00', '2024-11-26 07:26:58'),
(5321, 'Meter 1', 'Low Voltage', '5698.00', '2024-11-26 07:28:10'),
(5322, 'Meter 2', 'Low Voltage', '5756.00', '2024-11-26 07:28:36'),
(5323, 'Meter 1', 'High Current', '4568.00', '2024-11-26 07:31:45'),
(5324, 'Meter 1', 'Low Voltage', '9898.00', '2024-11-26 07:32:13'),
(5325, 'Meter 1', 'High Current', '5656.00', '2024-11-26 07:34:20'),
(5326, 'Meter 1', 'Low Voltage', '8469.00', '2024-11-26 07:36:08'),
(5327, 'Meter 1', 'High Current', '8985.00', '2024-11-26 07:37:23'),
(5328, 'Meter 1', 'Low Voltage', '5468.00', '2024-11-26 07:38:56'),
(5329, 'Meter 1', 'Low Voltage', '2123.00', '2024-11-26 07:39:39'),
(5330, 'Meter 2', 'Low Voltage', '589.00', '2024-11-26 07:41:04'),
(5331, 'Meter 1', 'Low Voltage', '5698.00', '2024-11-26 07:41:51'),
(5332, 'Meter 1', 'Low Voltage', '5689.00', '2024-11-26 07:43:17'),
(5333, 'Meter 1', 'Low Voltage', '65623.00', '2024-11-26 07:43:51'),
(5334, 'Meter 1', 'Low Voltage', '589.00', '2024-11-26 07:45:31'),
(5335, 'Meter 3', 'High Current', '5789.00', '2024-11-26 07:48:37'),
(5336, 'Meter 1', '', '0.00', '2024-11-26 07:49:56'),
(5337, 'Meter 1', 'High Current', '656.00', '2024-11-26 07:50:01'),
(5338, 'Meter 1', 'High Current', '5689.00', '2024-11-26 07:50:37'),
(5339, 'Meter 1', 'High Current', '8589.00', '2024-11-26 07:52:13'),
(5340, 'Meter 1', 'High Current', '8589.00', '2024-11-26 07:53:27'),
(5341, 'Meter 1', 'Low Voltage', '2468.00', '2024-11-26 07:54:11'),
(5342, 'Meter 2', 'High Current', '5656.00', '2024-11-26 07:54:45'),
(5343, 'Meter 1', 'High Voltage', '5655.00', '2024-11-26 07:55:58'),
(5344, 'Meter 1', 'Low Voltage', '589.00', '2024-11-26 07:56:31'),
(5345, 'Meter 1', 'High Current', '4213.00', '2024-11-26 07:59:48'),
(5346, 'Meter 1', 'High Current', '5656.00', '2024-11-26 08:02:08'),
(5347, 'Meter 1', 'High Current', '5463.00', '2024-11-26 08:03:47'),
(5348, 'Meter 2', 'Low Voltage', '8989.00', '2024-11-26 08:04:15'),
(5349, 'Meter 1', 'High Current', '565.00', '2024-11-26 08:04:58'),
(5350, 'Meter 1', 'Low Voltage', '8590.00', '2024-11-26 08:05:21'),
(5351, 'Meter 1', 'High Current', '2468.00', '2024-11-26 08:06:03'),
(5352, 'Meter 1', 'Low Voltage', '687.00', '2024-11-26 08:07:44'),
(5353, 'Meter 1', 'High Current', '5656.00', '2024-11-26 08:15:13'),
(5354, 'Meter 1', 'High Current', '5656.00', '2024-11-26 08:15:32');

-- --------------------------------------------------------

--
-- Table structure for table `production`
--

CREATE TABLE `production` (
  `id` int(11) NOT NULL,
  `GWP` int(11) NOT NULL,
  `Airjet` int(11) NOT NULL,
  `Sewing2` int(11) NOT NULL,
  `Textile` int(11) NOT NULL,
  `Sewing1` int(11) NOT NULL,
  `PG` int(11) NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `production`
--

INSERT INTO `production` (`id`, `GWP`, `Airjet`, `Sewing2`, `Textile`, `Sewing1`, `PG`, `date`) VALUES
(14, 40008, 43372, 7083, 27407, 17839, 2000, '2025-01-01'),
(15, 40008, 43372, 7083, 27407, 17839, 2000, '2025-01-02'),
(16, 40008, 43372, 7083, 27407, 17839, 2000, '2025-01-03'),
(17, 40008, 43372, 7083, 27407, 17839, 2000, '2025-01-04'),
(18, 40008, 43372, 7083, 27407, 17839, 2000, '2025-01-05'),
(19, 40008, 43372, 7083, 27407, 17839, 2000, '2025-01-06'),
(20, 40008, 43372, 7083, 27407, 17839, 2000, '2025-01-07'),
(21, 40008, 43372, 7083, 27407, 17839, 2000, '2025-01-08'),
(22, 40008, 43372, 7083, 27407, 17839, 2000, '2025-01-09'),
(23, 40008, 43372, 7083, 27407, 17839, 2000, '2025-01-10'),
(24, 40008, 43372, 7083, 27407, 17839, 2000, '2025-01-11'),
(25, 40008, 43372, 7083, 27407, 17839, 2000, '2025-01-12'),
(26, 40008, 43372, 7083, 27407, 17839, 2000, '2025-01-13'),
(27, 40008, 43372, 7083, 27407, 17839, 2000, '2025-01-14');

-- --------------------------------------------------------

--
-- Table structure for table `recentalarms`
--

CREATE TABLE `recentalarms` (
  `id` int(11) NOT NULL,
  `meter` varchar(50) DEFAULT NULL,
  `option_selected` varchar(50) DEFAULT NULL,
  `db_value` float DEFAULT NULL,
  `url_value` float DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `total_duration` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `recentalarms`
--

INSERT INTO `recentalarms` (`id`, `meter`, `option_selected`, `db_value`, `url_value`, `status`, `start_time`, `end_time`, `total_duration`, `created_at`) VALUES
(1, 'Meter 1', 'Low Voltage', 2576, 635.7, 'Alarm: Threshold exceeded.', '2024-11-21 15:01:20', '2024-11-21 15:04:51', '211', '2024-11-21 15:01:20'),
(2, 'Meter 1', 'High Current', 9090, 635.7, 'Alarm: Threshold exceeded.', '2024-11-21 15:01:20', '2024-11-21 15:04:51', '211', '2024-11-21 15:01:20'),
(3, 'Meter 1', 'High Voltage', 6321, 563.6, 'Alarm: Threshold exceeded.', '2024-11-21 15:04:11', '2024-11-21 15:04:51', '40', '2024-11-21 15:04:11'),
(4, 'Meter 1', 'Low Voltage', 2576, 470.9, 'Alarm: Threshold exceeded.', '2024-11-22 10:57:57', NULL, NULL, '2024-11-22 10:57:57'),
(5, 'Meter 2', 'Low Voltage', 576, 41, 'Alarm: Threshold exceeded.', '2024-11-22 10:57:57', '2024-11-22 10:59:25', '88', '2024-11-22 10:57:57'),
(6, 'Meter 1', 'High Current', 9090, 470.9, 'Alarm: Threshold exceeded.', '2024-11-22 10:57:57', NULL, NULL, '2024-11-22 10:57:57'),
(7, 'Meter 2', 'High Current', 989, 41, 'Alarm: Threshold exceeded.', '2024-11-22 10:57:57', '2024-11-22 10:59:25', '88', '2024-11-22 10:57:57'),
(8, 'Meter 1', 'High Voltage', 6321, 470.9, 'Alarm: Threshold exceeded.', '2024-11-22 10:57:57', NULL, NULL, '2024-11-22 10:57:57'),
(9, 'Meter 2', 'High Voltage', 639, 41, 'Alarm: Threshold exceeded.', '2024-11-22 10:57:57', '2024-11-22 10:59:25', '88', '2024-11-22 10:57:57'),
(10, 'Meter 2', 'Low Voltage', 576, 6, 'Alarm: Threshold exceeded.', '2024-11-22 10:59:50', '2024-11-22 11:01:33', '103', '2024-11-22 10:59:50'),
(11, 'Meter 2', 'High Current', 989, 6, 'Alarm: Threshold exceeded.', '2024-11-22 10:59:50', '2024-11-22 11:01:33', '103', '2024-11-22 10:59:50'),
(12, 'Meter 2', 'High Voltage', 639, 6, 'Alarm: Threshold exceeded.', '2024-11-22 10:59:50', '2024-11-22 11:01:33', '103', '2024-11-22 10:59:50'),
(13, 'Meter 2', 'Low Voltage', 576, 6.1, 'Alarm: Threshold exceeded.', '2024-11-22 11:02:13', '2024-11-22 11:02:24', '11', '2024-11-22 11:02:13'),
(14, 'Meter 2', 'High Current', 989, 6.1, 'Alarm: Threshold exceeded.', '2024-11-22 11:02:13', '2024-11-22 11:02:24', '11', '2024-11-22 11:02:13'),
(15, 'Meter 2', 'High Voltage', 639, 6.1, 'Alarm: Threshold exceeded.', '2024-11-22 11:02:13', '2024-11-22 11:02:24', '11', '2024-11-22 11:02:13'),
(16, 'Meter 2', 'Low Voltage', 576, 5.7, 'Alarm: Threshold exceeded.', '2024-11-22 11:05:47', '2024-11-22 11:06:57', '70', '2024-11-22 11:05:47'),
(17, 'Meter 2', 'High Current', 989, 5.7, 'Alarm: Threshold exceeded.', '2024-11-22 11:05:47', '2024-11-22 11:06:57', '70', '2024-11-22 11:05:47'),
(18, 'Meter 2', 'High Voltage', 639, 5.7, 'Alarm: Threshold exceeded.', '2024-11-22 11:05:47', '2024-11-22 11:06:57', '70', '2024-11-22 11:05:47'),
(19, 'Meter 2', 'Low Voltage', 576, 6.1, 'Alarm: Threshold exceeded.', '2024-11-22 11:07:15', '2024-11-22 11:08:19', '64', '2024-11-22 11:07:15'),
(20, 'Meter 2', 'High Current', 989, 6.1, 'Alarm: Threshold exceeded.', '2024-11-22 11:07:15', '2024-11-22 11:08:19', '64', '2024-11-22 11:07:15'),
(21, 'Meter 2', 'High Voltage', 639, 6.1, 'Alarm: Threshold exceeded.', '2024-11-22 11:07:15', '2024-11-22 11:08:19', '64', '2024-11-22 11:07:15'),
(22, 'Meter 2', 'Low Voltage', 576, 8.1, 'Alarm: Threshold exceeded.', '2024-11-22 11:08:29', '2024-11-22 11:08:40', '11', '2024-11-22 11:08:29'),
(23, 'Meter 2', 'High Current', 989, 8.1, 'Alarm: Threshold exceeded.', '2024-11-22 11:08:29', '2024-11-22 11:08:40', '11', '2024-11-22 11:08:29'),
(24, 'Meter 2', 'High Voltage', 639, 8.1, 'Alarm: Threshold exceeded.', '2024-11-22 11:08:29', '2024-11-22 11:08:40', '11', '2024-11-22 11:08:29'),
(25, 'Meter 2', 'Low Voltage', 576, 7.6, 'Alarm: Threshold exceeded.', '2024-11-22 11:09:44', '2024-11-22 11:15:43', '359', '2024-11-22 11:09:44'),
(26, 'Meter 2', 'High Current', 989, 7.6, 'Alarm: Threshold exceeded.', '2024-11-22 11:09:44', '2024-11-22 11:15:43', '359', '2024-11-22 11:09:44'),
(27, 'Meter 2', 'High Voltage', 639, 7.6, 'Alarm: Threshold exceeded.', '2024-11-22 11:09:44', '2024-11-22 11:15:43', '359', '2024-11-22 11:09:44'),
(28, 'Meter 2', 'Low Voltage', 576, 5.1, 'Alarm: Threshold exceeded.', '2024-11-22 11:16:00', '2024-11-22 11:16:11', '11', '2024-11-22 11:16:00'),
(29, 'Meter 2', 'High Current', 989, 5.1, 'Alarm: Threshold exceeded.', '2024-11-22 11:16:00', '2024-11-22 11:16:11', '11', '2024-11-22 11:16:00'),
(30, 'Meter 2', 'High Voltage', 639, 5.1, 'Alarm: Threshold exceeded.', '2024-11-22 11:16:00', '2024-11-22 11:16:11', '11', '2024-11-22 11:16:00'),
(31, 'Meter 2', 'Low Voltage', 576, 5.2, 'Alarm: Threshold exceeded.', '2024-11-22 11:16:21', '2024-11-22 11:16:31', '10', '2024-11-22 11:16:21'),
(32, 'Meter 2', 'High Current', 989, 5.2, 'Alarm: Threshold exceeded.', '2024-11-22 11:16:21', '2024-11-22 11:16:31', '10', '2024-11-22 11:16:21'),
(33, 'Meter 2', 'High Voltage', 639, 5.2, 'Alarm: Threshold exceeded.', '2024-11-22 11:16:21', '2024-11-22 11:16:31', '10', '2024-11-22 11:16:21'),
(34, 'Meter 2', 'Low Voltage', 576, 5.4, 'Alarm: Threshold exceeded.', '2024-11-22 11:17:58', '2024-11-22 11:21:34', '216', '2024-11-22 11:17:58'),
(35, 'Meter 2', 'High Current', 989, 5.4, 'Alarm: Threshold exceeded.', '2024-11-22 11:17:58', '2024-11-22 11:21:34', '216', '2024-11-22 11:17:58'),
(36, 'Meter 2', 'High Voltage', 639, 5.4, 'Alarm: Threshold exceeded.', '2024-11-22 11:17:58', '2024-11-22 11:21:34', '216', '2024-11-22 11:17:58'),
(37, 'Meter 2', 'Low Voltage', 576, 43, 'Alarm: Threshold exceeded.', '2024-11-22 11:25:35', '2024-11-22 11:29:38', '243', '2024-11-22 11:25:35'),
(38, 'Meter 2', 'High Current', 989, 43, 'Alarm: Threshold exceeded.', '2024-11-22 11:25:35', '2024-11-22 11:29:38', '243', '2024-11-22 11:25:35'),
(39, 'Meter 2', 'High Voltage', 639, 43, 'Alarm: Threshold exceeded.', '2024-11-22 11:25:35', '2024-11-22 11:29:38', '243', '2024-11-22 11:25:35'),
(40, 'Meter 3', 'High Current', 256, 27.9, 'Alarm: Threshold exceeded.', '2024-11-22 11:28:21', '2024-11-22 11:38:41', '620', '2024-11-22 11:28:21'),
(41, 'Meter 3', 'Low Voltage', 102020, 27.9, 'Alarm: Threshold exceeded.', '2024-11-22 11:28:21', '2024-11-22 11:38:41', '620', '2024-11-22 11:28:21'),
(42, 'Meter 2', 'Low Voltage', 576, 5.7, 'Alarm: Threshold exceeded.', '2024-11-22 11:33:37', '2024-11-22 11:35:56', '139', '2024-11-22 11:33:37'),
(43, 'Meter 2', 'High Current', 989, 5.7, 'Alarm: Threshold exceeded.', '2024-11-22 11:33:38', '2024-11-22 11:35:56', '138', '2024-11-22 11:33:38'),
(44, 'Meter 2', 'High Voltage', 639, 5.7, 'Alarm: Threshold exceeded.', '2024-11-22 11:33:38', '2024-11-22 11:35:56', '138', '2024-11-22 11:33:38'),
(45, 'Meter 2', 'Low Voltage', 576, 5.3, 'Alarm: Threshold exceeded.', '2024-11-22 11:44:56', '2024-11-22 11:46:15', '79', '2024-11-22 11:44:56'),
(46, 'Meter 2', 'Low Voltage', 576, 5.3, 'Alarm: Threshold exceeded.', '2024-11-22 11:44:56', '2024-11-22 11:46:15', '79', '2024-11-22 11:44:56'),
(47, 'Meter 2', 'High Current', 989, 5.3, 'Alarm: Threshold exceeded.', '2024-11-22 11:44:56', '2024-11-22 11:46:15', '79', '2024-11-22 11:44:56'),
(48, 'Meter 2', 'High Current', 989, 5.3, 'Alarm: Threshold exceeded.', '2024-11-22 11:44:56', '2024-11-22 11:46:15', '79', '2024-11-22 11:44:56'),
(49, 'Meter 2', 'High Voltage', 639, 5.3, 'Alarm: Threshold exceeded.', '2024-11-22 11:44:56', '2024-11-22 11:46:15', '79', '2024-11-22 11:44:56'),
(50, 'Meter 2', 'Low Voltage', 576, 6.8, 'Alarm: Threshold exceeded.', '2024-11-22 11:47:35', '2024-11-22 11:48:53', '78', '2024-11-22 11:47:35'),
(51, 'Meter 2', 'High Current', 989, 6.8, 'Alarm: Threshold exceeded.', '2024-11-22 11:47:35', '2024-11-22 11:48:53', '78', '2024-11-22 11:47:35'),
(52, 'Meter 2', 'High Voltage', 639, 6.8, 'Alarm: Threshold exceeded.', '2024-11-22 11:47:35', '2024-11-22 11:48:53', '78', '2024-11-22 11:47:35'),
(53, 'Meter 2', 'High Voltage', 639, 6.8, 'Alarm: Threshold exceeded.', '2024-11-22 11:47:35', '2024-11-22 11:48:53', '78', '2024-11-22 11:47:35'),
(54, 'Meter 2', 'Low Voltage', 576, 16, 'Alarm: Threshold exceeded.', '2024-11-22 11:56:06', '2024-11-22 12:09:08', '782', '2024-11-22 11:56:06'),
(55, 'Meter 2', 'High Current', 989, 16, 'Alarm: Threshold exceeded.', '2024-11-22 11:56:06', '2024-11-22 12:09:08', '782', '2024-11-22 11:56:06'),
(56, 'Meter 2', 'High Voltage', 639, 16, 'Alarm: Threshold exceeded.', '2024-11-22 11:56:06', '2024-11-22 12:09:08', '782', '2024-11-22 11:56:06'),
(57, 'Meter 2', 'Low Voltage', 576, 6.5, 'Alarm: Threshold exceeded.', '2024-11-22 12:16:41', '2024-11-22 12:53:13', '2192', '2024-11-22 12:16:41'),
(58, 'Meter 2', 'High Current', 989, 6.5, 'Alarm: Threshold exceeded.', '2024-11-22 12:16:41', '2024-11-22 12:53:13', '2192', '2024-11-22 12:16:41'),
(59, 'Meter 2', 'High Voltage', 639, 6.5, 'Alarm: Threshold exceeded.', '2024-11-22 12:16:41', '2024-11-22 12:53:13', '2192', '2024-11-22 12:16:41'),
(60, 'Meter 2', 'Low Voltage', 576, 41.3, 'Alarm: Threshold exceeded.', '2024-11-22 12:56:40', NULL, NULL, '2024-11-22 12:56:40'),
(61, 'Meter 2', 'High Current', 989, 41.3, 'Alarm: Threshold exceeded.', '2024-11-22 12:56:40', NULL, NULL, '2024-11-22 12:56:40'),
(62, 'Meter 2', 'High Voltage', 639, 41.3, 'Alarm: Threshold exceeded.', '2024-11-22 12:56:40', NULL, NULL, '2024-11-22 12:56:40'),
(63, 'Meter 3', 'High Current', 256, 25.8, 'Alarm: Threshold exceeded.', '2024-11-26 17:49:44', '2024-12-02 12:34:55', '499511', '2024-11-26 17:49:44'),
(64, 'Meter 3', 'Low Voltage', 102020, 25.8, 'Alarm: Threshold exceeded.', '2024-11-26 17:49:44', '2024-12-02 12:34:55', '499511', '2024-11-26 17:49:44'),
(65, 'Meter 3', 'High Voltage', 500, 25.8, 'Alarm: Threshold exceeded.', '2024-11-26 17:49:44', '2024-12-02 12:34:55', '499511', '2024-11-26 17:49:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`) VALUES
(1, 'demo', 'demo'),
(2, 'test@gmail.com', 'test'),
(3, 'testuser', 'testpassword'),
(4, 'automation@jiotp.com', 'sahamid');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alarms`
--
ALTER TABLE `alarms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meterdata`
--
ALTER TABLE `meterdata`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `production`
--
ALTER TABLE `production`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recentalarms`
--
ALTER TABLE `recentalarms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alarms`
--
ALTER TABLE `alarms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `meterdata`
--
ALTER TABLE `meterdata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5355;

--
-- AUTO_INCREMENT for table `production`
--
ALTER TABLE `production`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `recentalarms`
--
ALTER TABLE `recentalarms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
