-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 30, 2025 at 05:46 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `litoda_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE DATABASE IF NOT EXISTS `litoda_db`;

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` text DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `middlename` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `firstname`, `middlename`, `lastname`, `profile_pic`) VALUES
(1, 'admin', '123', 'Jhonriel', 'C', 'Padecio', 'uploads/admin_1_691f1cb7f0eb5.png');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `middlename` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `tricycle_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `contact_no` varchar(20) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `firstname`, `middlename`, `lastname`, `tricycle_number`, `created_at`, `contact_no`, `profile_pic`) VALUES
(12, 'Jhonriel', 'C', 'Padecio', 'D-27', '2025-11-28 07:10:04', '09518342203', 'uploads/Jhonriel_Padecio/profile_69295a6435c4a_1764326500.jpg'),
(18, 'Justine Kaye', 'G.', 'Aves', 'D-01', '2025-11-29 09:37:05', '09123456789', 'uploads/JustineKaye_Aves/profile_692abeb124c88_1764409009.jpg'),
(19, 'Alexandra', 'F.', 'Relente', 'D-40', '2025-11-29 10:37:47', '09639123921', 'uploads/Alexandra_Relente/profile_692accfba1ab9_1764412667.jpg'),
(24, 'Justine Kaye', 'G', 'Aves', 'D-01', '2025-11-29 12:42:42', '09876754325', 'uploads/Justinekaye_Aves/profile_692aea420f311_1764420162.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `history`
--

CREATE TABLE `history` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `tricycle_number` varchar(255) DEFAULT NULL,
  `dispatch_time` datetime DEFAULT NULL,
  `queue_id` int(11) DEFAULT NULL,
  `queue_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `history`
--

INSERT INTO `history` (`id`, `driver_id`, `driver_name`, `tricycle_number`, `dispatch_time`, `queue_id`, `queue_time`) VALUES
(76, 12, 'Jhonriel Padecio', 'D-27', NULL, 49, '2025-11-29 07:46:58'),
(77, 12, 'Jhonriel Padecio', 'D-27', '2025-11-29 07:48:58', 49, NULL),
(78, 18, 'Justine Kaye Aves', 'D-01', NULL, 50, '2025-11-29 18:08:43'),
(79, 19, 'Alexandra Relente', 'D-40', NULL, 51, '2025-11-29 18:38:31'),
(80, 20, 'Justine Kaye Aves', 'D-01', NULL, 52, '2025-11-29 18:45:38'),
(81, 19, 'Alexandra Relente', 'D-40', '2025-11-29 18:46:21', 51, NULL),
(82, 20, 'Justine Kaye Aves', 'D-01', '2025-11-29 18:46:50', 52, NULL),
(83, 18, '', NULL, '2025-11-29 18:50:58', 50, NULL),
(84, 21, 'Justine Kaye Aves', 'D-01', NULL, 53, '2025-11-29 19:05:54'),
(85, 21, 'Justine Kaye Aves', 'D-01', '2025-11-29 19:56:51', 53, NULL),
(86, 23, 'Justine Kaye Aves', 'D-01', NULL, 54, '2025-11-29 20:10:39'),
(87, 19, 'Alexandra Relente', 'D-40', NULL, 55, '2025-11-29 20:13:42'),
(88, 23, 'Justine Kaye Aves', 'D-01', '2025-11-29 20:36:32', 54, NULL),
(89, 24, 'Justine Kaye Aves', 'D-01', NULL, 56, '2025-11-29 20:43:19'),
(90, 19, 'Alexandra Relente', 'D-40', '2025-11-29 20:44:21', 55, NULL),
(91, 24, 'Justine Kaye Aves', 'D-01', NULL, 57, '2025-11-30 13:20:04'),
(92, 24, 'Justine Kaye Aves', 'D-01', '2025-11-30 13:23:11', 57, NULL),
(93, 24, 'Justine Kaye Aves', 'D-01', NULL, 58, '2025-11-30 13:25:40'),
(94, 12, 'Jhonriel Padecio', 'D-27', NULL, 59, '2025-11-30 13:28:50');

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` enum('Success','Failed') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_time` datetime DEFAULT current_timestamp(),
  `failure_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `admin_id`, `username`, `email`, `status`, `ip_address`, `user_agent`, `login_time`, `failure_reason`) VALUES
(52, '20', 'Justine Kaye Aves', 'D-01', 'Dispatched', '2025-11-29 10:45:38', '2025-11-29 10:46:50', NULL),
(53, '21', 'Justine Kaye Aves', 'D-01', 'Dispatched', '2025-11-29 11:05:54', '2025-11-29 11:56:51', NULL),
(54, '23', 'Justine Kaye Aves', 'D-01', 'Dispatched', '2025-11-29 12:10:39', '2025-11-29 12:36:32', NULL),
(55, '19', 'Alexandra Relente', 'D-40', 'Dispatched', '2025-11-29 12:13:42', '2025-11-29 12:44:21', NULL),
(56, '24', 'Justine Kaye Aves', 'D-01', 'Onqueue', '2025-11-29 12:43:19', NULL, NULL),
(57, '24', 'Justine Kaye Aves', 'D-01', 'Dispatched', '2025-11-30 05:20:04', '2025-11-30 05:23:11', NULL),
(58, '24', 'Justine Kaye Aves', 'D-01', 'Onqueue', '2025-11-30 05:25:40', NULL, NULL),
(59, '12', 'Jhonriel Padecio', 'D-27', 'Onqueue', '2025-11-30 05:28:50', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `status` enum('sent','failed','pending') DEFAULT 'pending',
  `response` text DEFAULT NULL,
  `sent_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `queue`
--

CREATE TABLE `queue` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `status` enum('Onqueue','Dispatched','Cancelled','Removed') DEFAULT 'Onqueue',
  `queued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `dispatched_at` timestamp NULL DEFAULT NULL,
  `queue_number` int(11) DEFAULT NULL,
  `queue_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `removal_logs`
--

CREATE TABLE `removal_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `driver_id` int(11) DEFAULT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `tricycle_number` varchar(50) DEFAULT NULL,
  `queue_number` int(11) DEFAULT NULL,
  `remover_driver_id` int(11) DEFAULT NULL,
  `remover_driver_name` varchar(255) DEFAULT NULL,
  `removed_at` datetime DEFAULT NULL,
  `reason` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `history`
--
ALTER TABLE `history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_login_time` (`login_time`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `queue`
--
ALTER TABLE `queue`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_queue_per_day` (`queue_number`,`queue_date`),
  ADD KEY `idx_queue_number` (`queue_number`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_driver_id` (`driver_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sent_at` (`sent_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `history`
--
ALTER TABLE `history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `queue`
--
ALTER TABLE `queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
