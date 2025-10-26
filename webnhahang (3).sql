-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 26, 2025 at 12:07 PM
-- Server version: 9.1.0
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `webnhahang`
--

-- --------------------------------------------------------

--
-- Table structure for table `dishes`
--

CREATE TABLE `dishes` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `price` int NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `category` enum('food','drink','other') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'food'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dishes`
--

INSERT INTO `dishes` (`id`, `name`, `price`, `image`, `category`) VALUES
(1, 'Cơm rang thập cẩm', 125000, 'images/com-rang.jpg', 'food'),
(2, 'Lẩu cua khổng lồ', 139000, 'images/lau-cua.jpg', 'food');

-- --------------------------------------------------------

--
-- Table structure for table `help_requests`
--

CREATE TABLE `help_requests` (
  `id` int NOT NULL,
  `table_number` int NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('new','ack','done') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `help_requests`
--

INSERT INTO `help_requests` (`id`, `table_number`, `note`, `status`, `created_at`) VALUES
(1, 1, NULL, 'new', '2025-10-20 14:23:51'),
(2, 1, NULL, 'new', '2025-10-21 15:34:09'),
(3, 1, NULL, 'new', '2025-10-21 15:44:22'),
(4, 1, NULL, 'new', '2025-10-21 15:47:12'),
(5, 1, NULL, 'new', '2025-10-21 15:54:46'),
(6, 1, NULL, 'new', '2025-10-21 15:56:25'),
(7, 1, NULL, 'new', '2025-10-21 16:04:27'),
(8, 1, NULL, 'new', '2025-10-21 16:08:27'),
(9, 2, NULL, 'new', '2025-10-21 16:17:56'),
(10, 1, NULL, 'new', '2025-10-21 16:46:41');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `table_number` int NOT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'cash',
  `payment_status` enum('pending','paid','failed','refunded') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `ref_code` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','kitchen','ready','served','paid','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `ordered_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `table_number`, `items`, `total`, `payment_method`, `payment_status`, `ref_code`, `status`, `ordered_at`, `created_at`) VALUES
(120, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 09:20:40', '2025-10-25 09:20:40'),
(121, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 09:20:46', '2025-10-25 09:20:46'),
(122, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 09:21:02', '2025-10-25 09:21:02'),
(123, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 09:24:34', '2025-10-25 09:24:34'),
(124, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 09:26:15', '2025-10-25 09:26:15'),
(125, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 09:28:13', '2025-10-25 09:28:13'),
(126, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 09:28:44', '2025-10-25 09:28:44'),
(127, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 09:28:54', '2025-10-25 09:28:54'),
(128, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 09:29:10', '2025-10-25 09:29:10'),
(129, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 09:29:38', '2025-10-25 09:29:38'),
(130, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 09:30:04', '2025-10-25 09:30:04'),
(131, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 09:30:20', '2025-10-25 09:30:20'),
(132, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":2},{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":2}]', 528000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 09:43:53', '2025-10-25 09:43:53'),
(133, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":16}]', 2224000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 09:44:16', '2025-10-25 09:44:16'),
(134, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1},{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 264000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 09:44:50', '2025-10-25 09:44:50'),
(135, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1},{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 264000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 09:51:43', '2025-10-25 09:51:43'),
(136, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1},{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 264000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 09:51:51', '2025-10-25 09:51:51'),
(137, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 10:26:08', '2025-10-25 10:26:08'),
(138, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 10:29:25', '2025-10-25 10:29:25'),
(139, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 10:38:57', '2025-10-25 10:38:57'),
(140, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 10:39:06', '2025-10-25 10:39:06'),
(141, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 10:45:01', '2025-10-25 10:45:01'),
(142, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 11:13:06', '2025-10-25 11:13:06'),
(143, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 11:15:12', '2025-10-25 11:15:12'),
(144, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 11:17:46', '2025-10-25 11:17:46'),
(145, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 11:18:25', '2025-10-25 11:18:25'),
(146, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 11:21:20', '2025-10-25 11:21:20'),
(147, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 11:27:14', '2025-10-25 11:27:14'),
(148, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 11:33:15', '2025-10-25 11:33:15'),
(149, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 11:34:41', '2025-10-25 11:34:41'),
(150, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 11:40:09', '2025-10-25 11:40:09'),
(151, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 11:40:22', '2025-10-25 11:40:22'),
(152, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 11:43:52', '2025-10-25 11:43:52'),
(153, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 11:49:32', '2025-10-25 11:49:32'),
(154, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 11:49:49', '2025-10-25 11:49:49'),
(155, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 11:53:52', '2025-10-25 11:53:52'),
(156, 2, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 14:30:02', '2025-10-25 14:30:02'),
(157, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 15:09:15', '2025-10-25 15:09:15'),
(158, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":3}]', 417000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 15:09:23', '2025-10-25 15:09:23'),
(159, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 15:10:53', '2025-10-25 15:10:53'),
(160, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 15:11:24', '2025-10-25 15:11:24'),
(161, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":2}]', 278000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 15:12:02', '2025-10-25 15:12:02'),
(162, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 15:12:37', '2025-10-25 15:12:37'),
(163, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 15:15:08', '2025-10-25 15:15:08'),
(164, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 15:16:04', '2025-10-25 15:16:04'),
(165, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 15:17:47', '2025-10-25 15:17:47'),
(166, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 15:17:53', '2025-10-25 15:17:53'),
(167, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 15:17:57', '2025-10-25 15:17:57'),
(168, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 15:28:07', '2025-10-25 15:28:07'),
(169, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":2},{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"quantity\":1,\"image\":\"images\\/com-rang.jpg\"}]', 403000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 15:28:17', '2025-10-25 15:28:17'),
(170, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1},{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"quantity\":7,\"image\":\"images\\/com-rang.jpg\"}]', 1014000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 15:29:54', '2025-10-25 15:29:54'),
(171, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":5},{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"quantity\":2,\"image\":\"images\\/lau-cua.jpg\"}]', 903000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 15:38:48', '2025-10-25 15:38:48'),
(172, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":3}]', 375000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 15:49:55', '2025-10-25 15:49:55'),
(173, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 16:35:54', '2025-10-25 16:35:54'),
(174, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 16:37:27', '2025-10-25 16:37:27'),
(175, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1},{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"quantity\":1,\"image\":\"images\\/com-rang.jpg\"}]', 264000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 16:37:48', '2025-10-25 16:37:48'),
(176, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":2},{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":3}]', 653000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 16:51:47', '2025-10-25 16:51:47'),
(177, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":3},{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"quantity\":1,\"image\":\"images\\/com-rang.jpg\"}]', 542000.00, 'cash', 'paid', NULL, 'paid', '2025-10-25 16:52:03', '2025-10-25 16:52:03'),
(178, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1},{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":2}]', 403000.00, 'bank_transfer', 'paid', NULL, 'paid', '2025-10-25 16:52:29', '2025-10-25 16:52:29'),
(179, 2, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'cash', 'paid', NULL, 'paid', '2025-10-26 07:10:24', '2025-10-26 07:10:24'),
(180, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":2}]', 250000.00, 'cash', 'pending', NULL, 'pending', '2025-10-26 10:10:55', '2025-10-26 10:10:55');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `dish_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `dish_id`, `quantity`, `price`) VALUES
(1, 168, 1, 1, 125000.00),
(2, 169, 2, 2, 139000.00),
(3, 169, 1, 1, 125000.00),
(4, 170, 2, 1, 139000.00),
(5, 170, 1, 7, 125000.00),
(6, 171, 1, 5, 125000.00),
(7, 171, 2, 2, 139000.00),
(8, 172, 1, 3, 125000.00),
(9, 173, 2, 1, 139000.00),
(10, 174, 2, 1, 139000.00),
(11, 175, 2, 1, 139000.00),
(12, 175, 1, 1, 125000.00),
(13, 176, 2, 2, 139000.00),
(14, 176, 1, 3, 125000.00),
(15, 177, 2, 3, 139000.00),
(16, 177, 1, 1, 125000.00),
(17, 178, 1, 1, 125000.00),
(18, 178, 2, 2, 139000.00),
(19, 179, 1, 1, 125000.00),
(20, 180, 1, 2, 125000.00);

--
-- Triggers `order_items`
--
DELIMITER $$
CREATE TRIGGER `trg_order_items_after_delete` AFTER DELETE ON `order_items` FOR EACH ROW BEGIN
  DECLARE v_table_number INT;
  DECLARE v_dish_name VARCHAR(255);
  DECLARE v_app_user_id INT;
  DECLARE v_reason VARCHAR(255);

  -- Lấy thông tin bổ sung
  SELECT table_number INTO v_table_number
  FROM orders
  WHERE id = OLD.order_id
  LIMIT 1;

  SELECT name INTO v_dish_name
  FROM dishes
  WHERE id = OLD.dish_id
  LIMIT 1;

  -- Đọc biến phiên MySQL nếu ứng dụng có set trước khi xóa
  SET v_app_user_id = @app_user_id;
  SET v_reason      = @delete_reason;

  INSERT INTO order_item_deletions (
    order_id, table_number, dish_id, dish_name,
    quantity, price, line_total,
    deleted_reason, deleted_by_user_id, deleted_by_db_user
  )
  VALUES (
    OLD.order_id, v_table_number, OLD.dish_id, v_dish_name,
    OLD.quantity, OLD.price, (OLD.quantity * OLD.price),
    v_reason, v_app_user_id, CURRENT_USER()
  );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_item_deletions`
--

CREATE TABLE `order_item_deletions` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `table_number` int DEFAULT NULL,
  `dish_id` int DEFAULT NULL,
  `dish_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` int NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  `deleted_reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_by_user_id` int DEFAULT NULL,
  `deleted_by_db_user` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_item_deletions`
--

INSERT INTO `order_item_deletions` (`id`, `order_id`, `table_number`, `dish_id`, `dish_name`, `quantity`, `price`, `line_total`, `deleted_reason`, `deleted_by_user_id`, `deleted_by_db_user`, `deleted_at`) VALUES
(1, 100, 1, 2, 'Lẩu cua khổng lồ', 1, 139000.00, 139000.00, 'Xóa Lẩu cua khổng lồ x1', 1, 'root@localhost', '2025-10-24 18:07:15'),
(2, 101, 1, 1, 'Cơm rang thập cẩm', 1, 125000.00, 125000.00, 'Xóa Cơm rang thập cẩm x1', 1, 'root@localhost', '2025-10-24 18:07:37'),
(3, 101, 1, 2, 'Lẩu cua khổng lồ', 1, 139000.00, 139000.00, 'Xóa Lẩu cua khổng lồ x1', 1, 'root@localhost', '2025-10-24 18:08:07'),
(4, 102, 1, 2, 'Lẩu cua khổng lồ', 1, 139000.00, 139000.00, 'Xóa Lẩu cua khổng lồ x1', 1, 'root@localhost', '2025-10-24 18:16:30'),
(5, 103, 1, 2, 'Lẩu cua khổng lồ', 2, 139000.00, 278000.00, 'Xóa Lẩu cua khổng lồ x1', 1, 'root@localhost', '2025-10-25 08:24:16'),
(6, 107, 1, 1, 'Cơm rang thập cẩm', 1, 125000.00, 125000.00, 'Xóa Cơm rang thập cẩm x2', 1, 'root@localhost', '2025-10-25 08:33:08'),
(7, 111, 1, 1, 'Cơm rang thập cẩm', 2, 125000.00, 250000.00, 'Xóa Cơm rang thập cẩm x4', 1, 'root@localhost', '2025-10-25 08:46:15');

-- --------------------------------------------------------

--
-- Table structure for table `payment_accounts`
--

CREATE TABLE `payment_accounts` (
  `id` int NOT NULL,
  `bank_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `emv_gui` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_accounts`
--

INSERT INTO `payment_accounts` (`id`, `bank_name`, `account_number`, `account_name`, `emv_gui`, `note`, `created_at`, `updated_at`) VALUES
(1, 'VPBank', '0367903437', 'CALM SPACE', NULL, 'Tài khoản chính', '2025-10-25 10:28:49', NULL),
(2, 'Techcombank', '1234567890', 'NHÀ THỦY', NULL, 'Tài khoản phụ', '2025-10-25 10:28:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `id` int NOT NULL,
  `table_number` int NOT NULL,
  `floor` int NOT NULL DEFAULT '1',
  `qr_secret` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `capacity` int DEFAULT '4',
  `status` enum('available','unavailable') COLLATE utf8mb4_general_ci DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`id`, `table_number`, `floor`, `qr_secret`, `capacity`, `status`, `created_at`) VALUES
(1, 1, 1, 'TBL001', 4, 'unavailable', '2025-10-09 04:17:15'),
(2, 2, 1, 'TBL002', 4, 'available', '2025-10-09 04:17:15'),
(3, 3, 1, 'TBL003', 6, 'available', '2025-10-09 04:17:15'),
(4, 4, 1, 'TBL004', 2, 'available', '2025-10-09 04:17:15'),
(12, 7, 1, 'TBL012', 4, 'available', '2025-10-19 07:03:34'),
(13, 8, 1, 'TBL013', 4, 'available', '2025-10-19 07:03:38'),
(14, 5, 2, 'TBL014', 4, 'available', '2025-10-19 07:03:48'),
(15, 9, 2, 'TBL015', 4, 'available', '2025-10-20 16:59:35');

-- --------------------------------------------------------

--
-- Table structure for table `table_calls`
--

CREATE TABLE `table_calls` (
  `id` int NOT NULL,
  `table_number` int NOT NULL,
  `reason` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'help',
  `note` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('open','acknowledged','resolved') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `table_calls`
--

INSERT INTO `table_calls` (`id`, `table_number`, `reason`, `note`, `status`, `created_at`, `acknowledged_at`, `resolved_at`) VALUES
(1, 7, 'help', 'Cần hỗ trợ', 'resolved', '2025-10-21 16:04:44', NULL, '2025-10-21 16:16:08'),
(2, 1, 'help', 'Cần hỗ trợ', 'resolved', '2025-10-21 16:08:48', '2025-10-21 16:16:19', '2025-10-21 16:17:19'),
(3, 2, 'help', '', 'resolved', '2025-10-21 16:45:49', '2025-10-21 16:46:07', '2025-10-21 16:46:27'),
(4, 2, 'help', '', 'resolved', '2025-10-21 16:46:18', '2025-10-21 16:46:24', '2025-10-21 16:46:25'),
(5, 1, 'help', '', 'resolved', '2025-10-21 17:16:20', NULL, '2025-10-21 17:31:49'),
(6, 2, 'help', '', 'resolved', '2025-10-21 17:16:37', NULL, '2025-10-21 17:31:52'),
(7, 1, 'help', '', 'resolved', '2025-10-21 17:32:14', NULL, '2025-10-22 14:21:39'),
(8, 2, 'help', '', 'resolved', '2025-10-21 17:32:19', NULL, '2025-10-22 14:22:05'),
(9, 1, 'help', '', 'resolved', '2025-10-22 14:24:45', NULL, '2025-10-24 07:48:37'),
(10, 1, 'help', '', 'resolved', '2025-10-24 07:51:39', '2025-10-24 07:53:44', '2025-10-24 07:53:45'),
(11, 1, 'help', '', 'resolved', '2025-10-24 07:58:00', NULL, '2025-10-24 08:44:28'),
(12, 1, 'help', '', 'resolved', '2025-10-24 09:05:16', '2025-10-24 09:16:53', '2025-10-24 09:16:54'),
(13, 1, 'help', '', 'resolved', '2025-10-24 09:24:27', NULL, '2025-10-24 15:13:34'),
(14, 1, 'help', '', 'resolved', '2025-10-24 15:16:40', NULL, '2025-10-24 16:15:06'),
(15, 1, 'help', '', 'resolved', '2025-10-24 17:06:28', NULL, '2025-10-24 17:14:00'),
(16, 1, 'help', '', 'resolved', '2025-10-24 17:19:20', NULL, '2025-10-24 17:19:25'),
(17, 1, 'help', '', 'resolved', '2025-10-24 17:43:14', '2025-10-24 17:43:30', '2025-10-24 17:43:38'),
(18, 1, 'help', '', 'resolved', '2025-10-24 18:04:59', NULL, '2025-10-24 18:16:33'),
(19, 1, 'help', '', 'resolved', '2025-10-25 08:23:33', '2025-10-25 08:23:38', '2025-10-25 08:23:50'),
(20, 1, 'help', '', 'resolved', '2025-10-25 09:30:05', NULL, '2025-10-25 09:30:14'),
(21, 1, 'help', '', 'resolved', '2025-10-25 15:10:55', NULL, '2025-10-25 15:11:20'),
(22, 1, 'help', '', 'resolved', '2025-10-25 15:29:57', NULL, '2025-10-25 15:32:26'),
(23, 1, 'help', '', 'resolved', '2025-10-25 15:39:42', NULL, '2025-10-25 15:40:47'),
(24, 1, 'help', '', 'resolved', '2025-10-25 15:49:43', NULL, '2025-10-25 15:49:50'),
(25, 1, 'help', '', 'resolved', '2025-10-25 16:52:34', NULL, '2025-10-25 16:53:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `restaurant_id` int NOT NULL,
  `role` enum('admin','staff','customer') COLLATE utf8mb4_general_ci DEFAULT 'customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `phone`, `restaurant_id`, `role`) VALUES
(1, 'admin', '$2y$10$Jwde9wZVtPV6qlwHDVmQGeJnK0DQK7CuR2qTPYg2qF8.TotrfYJ4e', 'admin@gmail.com', NULL, 1, 'admin'),
(2, 'staff', '$2y$10$0ZfM9ZhPisFkj3vS3ZKE8uJ5OcJyEAlG9cvbn5X0CIInKfKAu2sD6', 'staff01@gmail.com', NULL, 2, 'staff');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dishes`
--
ALTER TABLE `dishes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dishes_category` (`category`);

--
-- Indexes for table `help_requests`
--
ALTER TABLE `help_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_help_table_created` (`table_number`,`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_user` (`user_id`),
  ADD KEY `fk_orders_table` (`table_number`),
  ADD KEY `idx_orders_status_created_at` (`status`,`created_at`),
  ADD KEY `idx_orders_table_created_at` (`table_number`,`created_at`),
  ADD KEY `idx_orders_ordered_at` (`ordered_at`),
  ADD KEY `idx_orders_table_ordered_at` (`table_number`,`ordered_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `order_item_deletions`
--
ALTER TABLE `order_item_deletions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_table` (`table_number`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `payment_accounts`
--
ALTER TABLE `payment_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_account_number` (`account_number`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `table_number` (`table_number`),
  ADD UNIQUE KEY `uq_tables_qr_secret` (`qr_secret`);

--
-- Indexes for table `table_calls`
--
ALTER TABLE `table_calls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_calls_table_status` (`table_number`,`status`),
  ADD KEY `idx_calls_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dishes`
--
ALTER TABLE `dishes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `help_requests`
--
ALTER TABLE `help_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `order_item_deletions`
--
ALTER TABLE `order_item_deletions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payment_accounts`
--
ALTER TABLE `payment_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `table_calls`
--
ALTER TABLE `table_calls`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_table` FOREIGN KEY (`table_number`) REFERENCES `tables` (`table_number`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
