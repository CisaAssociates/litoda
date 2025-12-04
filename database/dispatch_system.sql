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
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `contact_no` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `firstname`, `middlename`, `lastname`, `tricycle_number`, `registered_at`, `contact_no`, `profile_pic`) VALUES
(6, 'Vicente', 'C', 'Gumahob', 'D-45', '2025-11-26 02:34:13', '', 'uploads/Vicente_Gumahob/profile_692667256ad71_1764124453.jpg'),
(7, 'Pacifico', 'C', 'Igsolo', 'D-38', '2025-11-26 02:35:37', '', 'uploads/Pacifico_Igsolo/profile_6926677935246_1764124537.jpg'),
(8, 'Ferdinand', 'A', 'Cadavos', 'D-39', '2025-11-26 02:40:37', '09619147012', 'uploads/Ferdinand_Cadavos/profile_692668a550285_1764124837.jpg'),
(9, 'Anthony', 'A', 'Gerong', 'D-34', '2025-11-26 02:42:53', '09087664637', 'uploads/Anthony_Gerong/profile_6926692d32aa1_1764124973.jpg'),
(10, 'Leo', 'D', 'Sajonia', 'D-65', '2025-11-26 02:45:26', '09565467323', 'uploads/Leo_Sajonia/profile_692669c61b26e_1764125126.jpg'),
(12, 'Jhonriel', 'C', 'Padecio', 'D-27', '2025-11-26 11:13:39', '09630718267', 'uploads/Jhonriel_Padecio/profile_6926e0e347181_1764155619.jpg'),
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
(1, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 15:46:13', NULL),
(2, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 15:48:40', NULL),
(3, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 20:27:01', NULL),
(4, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 21:21:50', NULL),
(5, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 23:48:18', NULL),
(6, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 01:20:42', NULL),
(7, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 08:13:19', NULL),
(8, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 08:13:48', NULL),
(9, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 20:39:36', NULL),
(10, 1, 'admin', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 20:59:16', 'Invalid password'),
(11, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 20:59:24', NULL),
(12, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 21:57:34', NULL),
(13, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 09:04:55', NULL),
(14, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 09:27:24', NULL),
(15, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 10:27:41', NULL),
(16, NULL, 'asjdbabcdkhfk', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 10:35:32', 'User not found'),
(17, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 10:37:43', NULL),
(18, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-21 15:31:15', NULL),
(19, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 23:27:08', NULL),
(20, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 00:39:18', NULL),
(21, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 00:41:11', NULL),
(22, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 00:51:45', NULL),
(23, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 15:30:29', NULL),
(24, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-25 16:31:27', NULL),
(25, 1, 'admin', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 10:29:52', 'Invalid password'),
(26, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 10:29:58', NULL),
(27, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 10:37:43', NULL),
(28, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 14:46:11', NULL),
(29, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 19:05:15', NULL),
(30, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 21:59:21', NULL),
(31, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 00:02:15', NULL),
(32, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 14:58:15', NULL),
(33, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 22:14:03', NULL),
(34, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 22:51:14', NULL),
(35, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 07:27:18', NULL),
(36, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 13:06:19', NULL),
(37, NULL, 'admin123', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 13:35:56', 'User not found'),
(38, NULL, 'admin123', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 13:36:08', 'User not found'),
(39, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 13:36:28', NULL),
(40, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 14:02:57', NULL),
(41, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 19:28:03', NULL),
(42, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 23:15:18', NULL),
(43, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 07:45:07', NULL),
(44, NULL, '501@', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 07:56:49', 'User not found'),
(45, 1, 'admin', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 07:57:02', 'Invalid password'),
(46, NULL, '1', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 07:57:16', 'User not found'),
(47, NULL, '1', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 07:57:22', 'User not found'),
(48, NULL, '12', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 07:59:52', 'User not found'),
(49, NULL, '12', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 08:00:31', 'User not found'),
(50, NULL, '12', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 08:02:14', 'User not found'),
(51, NULL, '12', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 08:02:26', 'User not found'),
(52, NULL, '12', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 08:07:26', 'User not found'),
(53, NULL, '12', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 08:07:31', 'User not found'),
(54, NULL, '123', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 08:07:47', 'User not found'),
(55, NULL, '123', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 08:15:19', 'User not found'),
(56, 1, 'admin', NULL, 'Failed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 08:18:01', 'Invalid password'),
(57, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 08:19:01', NULL),
(58, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 14:10:22', NULL),
(59, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 17:51:01', NULL),
(60, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 18:03:51', NULL),
(61, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 18:06:54', NULL),
(62, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 18:42:02', NULL),
(63, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 18:50:41', NULL),
(64, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 18:54:43', NULL),
(65, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 19:02:16', NULL),
(66, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 19:04:26', NULL),
(67, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 19:07:46', NULL),
(68, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 19:56:40', NULL),
(69, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 20:00:19', NULL),
(70, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 20:05:21', NULL),
(71, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 20:07:05', NULL),
(72, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 20:09:39', NULL),
(73, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-29 20:41:59', NULL),
(74, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 13:08:33', NULL),
(75, 1, 'admin', NULL, 'Success', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 13:20:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `queue`
--

CREATE TABLE `queue` (
  `id` int(11) NOT NULL,
  `driver_id` varchar(255) DEFAULT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `tricycle_number` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `queued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `dispatch_at` timestamp NULL DEFAULT NULL,
  `queue_number` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `queue`
--

INSERT INTO `queue` (`id`, `driver_id`, `driver_name`, `tricycle_number`, `status`, `queued_at`, `dispatch_at`, `queue_number`) VALUES
(49, '12', 'Jhonriel Padecio', 'D-27', 'Dispatched', '2025-11-28 23:46:58', '2025-11-28 23:48:58', NULL),
(50, '18', 'Justine Kaye Aves', 'D-01', 'Dispatched', '2025-11-29 10:08:43', '2025-11-29 10:50:58', NULL),
(51, '19', 'Alexandra Relente', 'D-40', 'Dispatched', '2025-11-29 10:38:31', '2025-11-29 10:46:21', NULL),
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
  ADD UNIQUE KEY `queue_number` (`queue_number`);

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
-- Add queue_number column to queue table
ALTER TABLE queue 
ADD COLUMN queue_number INT DEFAULT NULL AFTER driver_id;

-- Add index for better performance
CREATE INDEX idx_queue_number ON queue(queue_number);

-- Update existing records with sequential numbers (if you have existing data today)
SET @num := 0;
UPDATE queue 
SET queue_number = (@num := @num + 1)
WHERE DATE(queued_at) = CURDATE()
ORDER BY queued_at ASC;

-- Verify the changes
SELECT id, driver_id, queue_number, status, queued_at 
FROM queue 
WHERE DATE(queued_at) = CURDATE()
ORDER BY queue_number ASC;
--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
