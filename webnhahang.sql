-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 11, 2025 at 08:11 PM
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
  `payment_method` varchar(50) DEFAULT 'cash',
  `status` enum('pending','kitchen','paid','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `table_number`, `items`, `total`, `payment_method`, `status`, `created_at`) VALUES
(1, NULL, 1, '{\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}}', 139000.00, 'cash', 'paid', '2025-10-09 04:17:50'),
(2, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1},\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}}', 264000.00, 'cash', 'paid', '2025-10-09 04:19:41'),
(3, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, 'cash', 'paid', '2025-10-09 04:21:04'),
(4, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, 'transfer', 'paid', '2025-10-09 04:21:12'),
(5, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, 'cash', 'paid', '2025-10-09 04:26:28'),
(6, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1},\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":1}}', 264000.00, 'cash', 'paid', '2025-10-09 04:27:09'),
(7, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, 'bank', 'paid', '2025-10-09 04:29:11'),
(8, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, NULL, 'paid', '2025-10-09 04:33:33'),
(9, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, NULL, 'paid', '2025-10-09 04:33:44'),
(10, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, NULL, 'paid', '2025-10-09 04:33:52'),
(11, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1}}', 125000.00, NULL, 'paid', '2025-10-09 04:37:56'),
(12, NULL, 1, '[]', 0.00, 'cash', 'pending', '2025-10-09 04:38:06'),
(13, NULL, 1, '[]', 0.00, 'cash', 'pending', '2025-10-09 04:38:06'),
(14, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":1},\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":3}}', 542000.00, 'cash', 'pending', '2025-10-09 04:38:12'),
(15, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":4},\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":2}}', 778000.00, 'cash', 'pending', '2025-10-10 07:43:46'),
(16, NULL, 1, '{\"1\":{\"id\":\"1\",\"name\":\"Cơm rang thập cẩm\",\"price\":\"125000\",\"image\":\"images\\/com-rang.jpg\",\"quantity\":4},\"2\":{\"id\":\"2\",\"name\":\"Lẩu cua khổng lồ\",\"price\":\"139000\",\"image\":\"images\\/lau-cua.jpg\",\"quantity\":2}}', 778000.00, 'cash', 'pending', '2025-10-10 07:44:09');

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `id` int(11) NOT NULL,
  `table_number` int(11) NOT NULL,
  `capacity` int(11) DEFAULT 4,
  `status` enum('available','unavailable') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`id`, `table_number`, `capacity`, `status`, `created_at`) VALUES
(1, 1, 4, 'unavailable', '2025-10-09 04:17:15'),
(2, 2, 4, 'available', '2025-10-09 04:17:15'),
(3, 3, 6, 'available', '2025-10-09 04:17:15'),
(4, 4, 2, 'unavailable', '2025-10-09 04:17:15');

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
  ADD KEY `fk_orders_table` (`table_number`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `table_number` (`table_number`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
