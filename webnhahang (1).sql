-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 19, 2025 at 10:46 AM
-- Server version: 10.4.32-MariaDB
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
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dishes`
--

INSERT INTO `dishes` (`id`, `name`, `price`, `image`) VALUES
(1, 'Cơm rang thập cẩm', 125000, 'images/com-rang.jpg'),
(2, 'Lẩu cua khổng lồ', 139000, 'images/lau-cua.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `table_number` int(11) NOT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items`)),
  `total` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer') NOT NULL DEFAULT 'cash',
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `ref_code` varchar(32) DEFAULT NULL,
  `status` enum('pending','kitchen','ready','served','paid','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `table_number`, `items`, `total`, `payment_method`, `payment_status`, `ref_code`, `status`, `created_at`) VALUES
(1, NULL, 1, '{\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}}', 139000.00, 'cash', 'pending', NULL, 'paid', '2025-10-09 04:17:50'),
(2, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1},\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}}', 264000.00, 'cash', 'pending', NULL, 'paid', '2025-10-09 04:19:41'),
(3, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, 'cash', 'pending', NULL, 'paid', '2025-10-09 04:21:04'),
(4, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, 'bank_transfer', 'pending', NULL, 'paid', '2025-10-09 04:21:12'),
(5, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, 'cash', 'pending', NULL, 'paid', '2025-10-09 04:26:28'),
(6, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1},\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}}', 264000.00, 'cash', 'pending', NULL, 'paid', '2025-10-09 04:27:09'),
(7, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, 'bank_transfer', 'pending', NULL, 'paid', '2025-10-09 04:29:11'),
(8, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, 'cash', 'pending', NULL, 'paid', '2025-10-09 04:33:33'),
(9, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, 'cash', 'pending', NULL, 'paid', '2025-10-09 04:33:44'),
(10, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, 'cash', 'pending', NULL, 'paid', '2025-10-09 04:33:52'),
(11, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, 'cash', 'pending', NULL, 'paid', '2025-10-09 04:37:56'),
(12, NULL, 1, '[]', 0.00, 'cash', 'pending', NULL, 'pending', '2025-10-09 04:38:06'),
(13, NULL, 1, '[]', 0.00, 'cash', 'pending', NULL, 'pending', '2025-10-09 04:38:06'),
(14, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1},\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":3}}', 542000.00, 'cash', 'pending', NULL, 'pending', '2025-10-09 04:38:12'),
(15, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":4},\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":2}}', 778000.00, 'cash', 'pending', NULL, 'pending', '2025-10-10 07:43:46'),
(16, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":4},\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":2}}', 778000.00, 'cash', 'pending', NULL, 'pending', '2025-10-10 07:44:09'),
(17, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, 'cash', 'pending', NULL, 'paid', '2025-10-14 12:51:21'),
(18, NULL, 1, '{\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}}', 139000.00, 'cash', 'pending', NULL, 'pending', '2025-10-14 13:35:13'),
(19, NULL, 1, '{\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}}', 139000.00, 'cash', 'pending', NULL, 'pending', '2025-10-14 13:35:21'),
(20, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1},\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}}', 264000.00, 'cash', 'pending', NULL, 'pending', '2025-10-14 13:35:27'),
(21, NULL, 1, '{\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":2}}', 278000.00, 'cash', 'pending', NULL, 'pending', '2025-10-14 13:35:31'),
(22, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1},\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":2}}', 403000.00, 'cash', 'pending', NULL, 'pending', '2025-10-14 13:36:04'),
(23, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":19},\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}}', 2514000.00, 'cash', 'pending', NULL, 'pending', '2025-10-14 13:42:02'),
(24, NULL, 1, '{\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":3}}', 417000.00, 'cash', 'paid', NULL, 'paid', '2025-10-14 13:44:36'),
(25, NULL, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":2}]', 250000.00, 'cash', 'paid', NULL, 'paid', '2025-10-18 09:32:35'),
(26, NULL, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'bank_transfer', 'pending', 'ORDER000026521', 'paid', '2025-10-18 09:32:41'),
(27, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":3}]', 417000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 09:53:18'),
(28, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":3}]', 417000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 09:53:34'),
(29, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":3}]', 417000.00, 'bank_transfer', 'pending', 'CF1D-18', 'pending', '2025-10-18 09:53:37'),
(30, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":2}]', 250000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 10:05:15'),
(31, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":3}]', 417000.00, 'bank_transfer', 'pending', 'CF1F-18', 'pending', '2025-10-18 10:47:28'),
(32, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":31},{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":22}]', 7059000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 10:54:29'),
(33, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 15:43:55'),
(34, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":9}]', 1251000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:21:51'),
(35, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:21:52'),
(36, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:21:52'),
(37, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:21:52'),
(38, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:21:53'),
(39, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:21:53'),
(40, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:21:58'),
(41, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:22:03'),
(42, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":3}]', 417000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:22:30'),
(43, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'bank_transfer', 'pending', 'CF2B-18', 'pending', '2025-10-18 16:22:32'),
(44, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":2}]', 250000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:27:27'),
(45, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:27:28'),
(46, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:27:29'),
(47, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:27:29'),
(48, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:27:29'),
(49, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:27:30'),
(50, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 125000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:27:30'),
(51, 1, 1, '[{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":3}]', 375000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:32:28'),
(52, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":5}]', 695000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:32:34'),
(53, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:32:35'),
(54, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:32:35'),
(55, 1, 1, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'pending', NULL, 'pending', '2025-10-18 16:32:36'),
(56, 1, 7, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'pending', NULL, 'pending', '2025-10-19 07:20:26'),
(57, 1, 7, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'cash', 'pending', NULL, 'pending', '2025-10-19 07:20:31'),
(58, 1, 7, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}]', 139000.00, 'bank_transfer', 'pending', 'CF3A-19', 'pending', '2025-10-19 07:20:35'),
(59, 1, 7, '[{\"id\":2,\"name\":\"Lẩu cua khổng lồ\",\"price\":139000,\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1},{\"id\":1,\"name\":\"Cơm rang thập cẩm\",\"price\":125000,\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}]', 264000.00, 'bank_transfer', 'pending', 'CF3B-19', 'pending', '2025-10-19 07:22:10');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `dish_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `id` int(11) NOT NULL,
  `table_number` int(11) NOT NULL,
  `floor` int(11) NOT NULL DEFAULT 1,
  `qr_secret` varchar(64) NOT NULL DEFAULT '',
  `capacity` int(11) DEFAULT 4,
  `status` enum('available','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`id`, `table_number`, `floor`, `qr_secret`, `capacity`, `status`, `created_at`) VALUES
(1, 1, 1, 'TBL001', 4, 'unavailable', '2025-10-09 04:17:15'),
(2, 2, 1, 'TBL002', 4, 'available', '2025-10-09 04:17:15'),
(3, 3, 1, 'TBL003', 6, 'available', '2025-10-09 04:17:15'),
(4, 4, 1, 'TBL004', 2, 'unavailable', '2025-10-09 04:17:15'),
(12, 7, 1, 'TBL012', 4, 'unavailable', '2025-10-19 07:03:34'),
(13, 8, 1, 'TBL013', 4, 'available', '2025-10-19 07:03:38'),
(14, 5, 2, 'TBL014', 4, 'available', '2025-10-19 07:03:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `restaurant_id` int(11) NOT NULL,
  `role` enum('admin','staff','customer') DEFAULT 'customer'
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
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_user` (`user_id`),
  ADD KEY `fk_orders_table` (`table_number`),
  ADD KEY `idx_orders_status_created_at` (`status`,`created_at`),
  ADD KEY `idx_orders_table_created_at` (`table_number`,`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `table_number` (`table_number`),
  ADD UNIQUE KEY `uq_tables_qr_secret` (`qr_secret`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
