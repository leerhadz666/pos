-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 26, 2025 at 03:56 AM
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
-- Database: `hardware_pos`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `action`, `created_at`) VALUES
(1, 'Added category: new', '2025-09-20 06:36:38');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `image`) VALUES
(3, 'paint', 'uploads/cat_68a12ac4ee1809.49637539.jpeg'),
(4, 'Electrical Supply', NULL),
(5, 'Angle bars', 'uploads/cat_68ce592e1e0db.jpg'),
(6, 'Cement', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `created_at`) VALUES
(1, 'lee', '09179586502', 'lee@gmail.com', 'balide,aurora', '2025-09-07 13:33:15'),
(2, 'siao', '09179586501', 'kier@gmail.com', 'balide,aurora', '2025-09-07 13:44:31');

-- --------------------------------------------------------

--
-- Table structure for table `customer_reminders`
--

CREATE TABLE `customer_reminders` (
  `id` int(11) NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `customer_id` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_reminders`
--

INSERT INTO `customer_reminders` (`id`, `note`, `created_at`, `customer_id`, `due_date`) VALUES
(2, 'bayad sa utang dong', '2025-09-09 17:13:07', 1, '2025-09-30'),
(3, 'bayad', '2025-09-09 17:30:01', 2, '2025-09-11'),
(4, 'bayad na', '2025-09-16 17:42:02', 2, '2025-09-24');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `unit_type` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `sale_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `customer_id`, `sale_id`, `amount`, `paid_at`) VALUES
(1, NULL, 33, 40.00, '2025-09-08 16:36:05'),
(2, NULL, 33, 50.00, '2025-09-08 16:36:11'),
(3, NULL, 33, 500.00, '2025-09-08 16:36:26'),
(4, NULL, 32, 365.00, '2025-09-08 16:36:40'),
(5, NULL, 35, 50.00, '2025-09-09 11:47:13'),
(6, NULL, 30, 5.00, '2025-09-24 17:40:56'),
(7, NULL, 31, 180.00, '2025-09-24 17:40:56'),
(8, NULL, 32, 200.00, '2025-09-24 17:40:56'),
(9, NULL, 33, 115.00, '2025-09-24 17:40:56');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `category` varchar(100) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `category`, `brand`, `unit`, `price`, `stock`, `image`, `created_at`) VALUES
(4, 'LOTUS 15', 'paint', NULL, 'piece', 25.00, 5.00, NULL, '2025-08-18 01:47:29'),
(5, 'wire', 'Electrical Supply', NULL, 'meter', 35.00, 7.75, NULL, '2025-08-18 04:09:04'),
(6, 'bulb', 'Electrical Supply', NULL, 'piece', 120.00, 3.00, NULL, '2025-08-18 04:09:28'),
(7, 'thinner ', 'paint', NULL, 'piece', 100.00, 0.00, NULL, '2025-08-24 23:40:02'),
(8, 'lansang no. 4', 'category 1', NULL, 'piece', 5.00, 90.00, 'uploads/1756081691_533230987_1259005015461324_8615603148736742034_n.jpg', '2025-08-25 00:28:11'),
(9, 'prod 1', 'cat 2', NULL, 'meter', 20.00, 19.50, NULL, '2025-09-02 23:22:19'),
(10, 'prod 2', 'Woods', NULL, 'piece', 30.00, 19.00, NULL, '2025-09-02 23:22:48'),
(11, 'prod3', 'Plumbing', NULL, 'piece', 10.00, 19.00, NULL, '2025-09-02 23:41:12'),
(12, 'prod 4', 'Electrical Supply', NULL, 'piece', 7.00, 18.00, NULL, '2025-09-02 23:41:48'),
(13, 'prod 5', 'Angle bars', NULL, 'piece', 24.00, 22.00, NULL, '2025-09-02 23:42:40'),
(14, 'bulb25 w', 'Electrical Supply', NULL, 'piece', 200.00, 37.00, NULL, '2025-09-02 23:43:15'),
(15, 'lansang no. 4', 'category 1', NULL, 'kilo', 50.00, 1.25, NULL, '2025-09-13 15:15:10');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `customer_id`, `user_id`, `total_amount`, `payment_method`, `created_at`, `paid_amount`) VALUES
(1, NULL, NULL, 435.00, 'Cash', '2025-08-23 02:44:44', 0.00),
(2, NULL, NULL, 435.00, 'Cash', '2025-08-23 02:44:51', 0.00),
(3, NULL, NULL, 125.00, 'Cash', '2025-08-25 01:55:53', 0.00),
(4, NULL, NULL, 105.00, 'Cash', '2025-09-01 20:25:48', 0.00),
(5, NULL, NULL, 525.00, 'Cash', '2025-09-01 20:49:21', 0.00),
(6, NULL, NULL, 5.00, 'Cash', '2025-09-02 18:19:13', 0.00),
(7, NULL, NULL, 10.00, 'Cash', '2025-09-02 18:19:15', 0.00),
(8, NULL, NULL, 255.00, 'Cash', '2025-09-03 22:19:39', 0.00),
(9, NULL, NULL, 5.00, 'Cash', '2025-09-07 03:03:47', 0.00),
(10, NULL, NULL, 5.00, 'Cash', '2025-09-07 03:03:47', 0.00),
(11, NULL, NULL, 105.00, 'Cash', '2025-09-07 03:09:08', 0.00),
(12, NULL, NULL, 105.00, 'Cash', '2025-09-07 03:09:08', 0.00),
(13, NULL, NULL, 105.00, 'Cash', '2025-09-07 03:20:21', 0.00),
(14, NULL, NULL, 105.00, 'Cash', '2025-09-07 03:20:21', 0.00),
(15, NULL, NULL, 25.00, 'Cash', '2025-09-07 03:22:10', 0.00),
(16, NULL, NULL, 25.00, 'Cash', '2025-09-07 03:22:10', 0.00),
(17, NULL, NULL, 25.00, 'Cash', '2025-09-07 03:24:36', 0.00),
(18, NULL, NULL, 25.00, 'Cash', '2025-09-07 03:24:36', 0.00),
(19, NULL, NULL, 5.00, 'Cash', '2025-09-07 03:25:17', 0.00),
(20, NULL, NULL, 5.00, 'Cash', '2025-09-07 03:25:17', 0.00),
(21, NULL, NULL, 5.00, 'Cash', '2025-09-07 06:44:32', 0.00),
(22, NULL, NULL, 5.00, 'Cash', '2025-09-07 06:44:32', 0.00),
(23, NULL, NULL, 10.00, 'Cash', '2025-09-07 06:45:10', 0.00),
(24, NULL, NULL, 120.00, 'Cash', '2025-09-07 06:45:35', 0.00),
(25, NULL, NULL, 25.00, 'Cash', '2025-09-07 06:45:58', 0.00),
(26, NULL, NULL, 5.00, 'Cash', '2025-09-07 06:47:48', 0.00),
(27, NULL, NULL, 25.00, 'Cash', '2025-09-07 07:16:28', 0.00),
(28, NULL, NULL, 105.00, 'GCash', '2025-09-07 10:09:15', 0.00),
(29, 1, NULL, 5.00, 'Paid', '2025-09-07 13:33:59', 5.00),
(30, 1, NULL, 100.00, 'Paid', '2025-09-07 13:45:15', 100.00),
(31, 1, NULL, 180.00, 'Utang', '2025-09-08 16:10:10', 180.00),
(32, 1, NULL, 200.00, 'Utang', '2025-09-08 16:11:15', 200.00),
(33, 1, NULL, 225.00, 'Utang', '2025-09-08 16:29:15', 315.00),
(34, 1, NULL, 260.00, 'Utang', '2025-09-08 17:12:38', 0.00),
(35, 2, NULL, 225.00, 'Utang', '2025-09-09 11:44:45', 50.00),
(36, NULL, NULL, 105.00, 'Cash', '2025-09-13 13:45:18', 0.00),
(37, NULL, 6, 100.00, 'Cash', '2025-09-13 14:07:33', 0.00),
(38, NULL, 6, 105.00, 'Cash', '2025-09-13 15:13:27', 0.00),
(39, NULL, 6, 125.00, 'Cash', '2025-09-13 16:21:41', 0.00),
(40, 1, 6, 125.00, 'Utang', '2025-09-16 17:40:26', 0.00),
(41, NULL, 1, 5.00, 'Cash', '2025-09-21 05:48:13', 0.00),
(42, NULL, 1, 5.00, 'Cash', '2025-09-21 10:17:49', 0.00),
(43, NULL, 1, 212.50, 'Cash', '2025-09-21 11:58:03', 0.00),
(44, 1, 1, 47.50, 'Utang', '2025-09-21 11:59:33', 0.00),
(45, 1, 1, 7.00, 'Utang', '2025-09-21 12:34:47', 0.00),
(46, 2, 1, 417.25, 'Utang', '2025-09-21 12:46:10', 0.00),
(47, NULL, 7, 75.00, 'Cash', '2025-09-24 16:03:59', 0.00),
(48, NULL, 7, 12.50, 'Cash', '2025-09-25 15:55:47', 0.00),
(49, NULL, 7, 12.50, 'Cash', '2025-09-25 15:55:47', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales_items`
--

CREATE TABLE `sales_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_items`
--

INSERT INTO `sales_items` (`id`, `sale_id`, `product_id`, `quantity`, `price`, `subtotal`) VALUES
(1, 13, 8, 1.00, 5.00, 5.00),
(2, 14, 8, 1.00, 5.00, 5.00),
(3, 13, 7, 1.00, 100.00, 100.00),
(4, 14, 7, 1.00, 100.00, 100.00),
(5, 15, 4, 1.00, 25.00, 25.00),
(6, 16, 4, 1.00, 25.00, 25.00),
(7, 17, 4, 1.00, 25.00, 25.00),
(8, 18, 4, 1.00, 25.00, 25.00),
(9, 19, 8, 1.00, 5.00, 5.00),
(10, 20, 8, 1.00, 5.00, 5.00),
(11, 21, 8, 1.00, 5.00, 5.00),
(12, 22, 8, 1.00, 5.00, 5.00),
(13, 23, 8, 2.00, 5.00, 10.00),
(14, 24, 6, 1.00, 120.00, 120.00),
(15, 25, 4, 1.00, 25.00, 25.00),
(16, 26, 8, 1.00, 5.00, 5.00),
(17, 27, 4, 1.00, 25.00, 25.00),
(18, 28, 8, 1.00, 5.00, 5.00),
(19, 28, 7, 1.00, 100.00, 100.00),
(20, 29, 8, 1.00, 5.00, 5.00),
(21, 30, 7, 1.00, 100.00, 100.00),
(22, 31, 6, 1.00, 120.00, 120.00),
(26, 33, 8, 1.00, 5.00, 5.00),
(27, 33, 7, 1.00, 100.00, 100.00),
(28, 33, 6, 1.00, 120.00, 120.00),
(29, 34, 6, 1.00, 120.00, 120.00),
(30, 34, 5, 1.00, 35.00, 35.00),
(31, 34, 7, 1.00, 100.00, 100.00),
(32, 34, 8, 1.00, 5.00, 5.00),
(33, 35, 6, 1.00, 120.00, 120.00),
(34, 35, 7, 1.00, 100.00, 100.00),
(35, 35, 8, 1.00, 5.00, 5.00),
(36, 36, 8, 1.00, 5.00, 5.00),
(37, 36, 7, 1.00, 100.00, 100.00),
(38, 37, 7, 1.00, 100.00, 100.00),
(39, 38, 8, 1.00, 5.00, 5.00),
(40, 38, 7, 1.00, 100.00, 100.00),
(41, 39, 15, 2.50, 50.00, 125.00),
(42, 40, 8, 1.00, 5.00, 5.00),
(43, 40, 6, 1.00, 120.00, 120.00),
(44, 41, 8, 1.00, 5.00, 5.00),
(45, 42, 8, 1.00, 5.00, 5.00),
(46, 43, 15, 0.25, 50.00, 12.50),
(47, 43, 14, 1.00, 200.00, 200.00),
(48, 44, 10, 1.00, 30.00, 30.00),
(49, 44, 9, 0.25, 20.00, 5.00),
(50, 44, 15, 0.25, 50.00, 12.50),
(51, 45, 12, 1.00, 7.00, 7.00),
(52, 46, 9, 0.25, 20.00, 5.00),
(53, 46, 8, 1.00, 5.00, 5.00),
(54, 46, 6, 1.00, 120.00, 120.00),
(55, 46, 5, 0.25, 35.00, 8.75),
(56, 46, 4, 1.00, 25.00, 25.00),
(57, 46, 11, 1.00, 10.00, 10.00),
(58, 46, 12, 1.00, 7.00, 7.00),
(59, 46, 13, 1.00, 24.00, 24.00),
(60, 46, 14, 1.00, 200.00, 200.00),
(61, 46, 15, 0.25, 50.00, 12.50),
(62, 47, 4, 3.00, 25.00, 75.00),
(63, 48, 15, 0.25, 50.00, 12.50),
(64, 49, 15, 0.25, 50.00, 12.50);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cashier') DEFAULT 'cashier',
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `photo`, `created_at`) VALUES
(1, 'admin', '$2y$10$YY01AXw/1KtfTZIe9CPMte0anOg888zOhGeApeYcnIwMkyxWkZixi', 'admin', NULL, '2025-08-16 19:40:59'),
(4, 'arvin', 'nirvana', 'admin', NULL, '2025-08-16 21:03:33'),
(7, 'pawpaw', '$2y$10$ggsuz8bflNvR8NEPVAk6YeUtoMbibcHki/1CbxY9Y1Lr9PNG8T1W2', 'cashier', 'uploads/users/68d1514c0b4ba.jpg', '2025-09-22 13:38:20');

-- --------------------------------------------------------

--
-- Table structure for table `utang`
--

CREATE TABLE `utang` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('unpaid','paid') DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utang`
--

INSERT INTO `utang` (`id`, `customer_id`, `sale_id`, `amount`, `status`, `created_at`) VALUES
(1, 1, 29, 0.00, 'paid', '2025-09-07 13:33:59'),
(2, 1, 30, 0.00, 'paid', '2025-09-07 13:45:15'),
(3, 1, 31, 0.00, 'paid', '2025-09-08 16:10:10'),
(4, 1, 32, 0.00, 'paid', '2025-09-08 16:11:15'),
(5, 1, 33, 110.00, 'unpaid', '2025-09-08 16:29:15'),
(6, 1, 34, 260.00, 'unpaid', '2025-09-08 17:12:38'),
(7, 2, 35, 175.00, 'unpaid', '2025-09-09 11:44:45'),
(8, 1, 40, 125.00, 'unpaid', '2025-09-16 17:40:26'),
(9, 1, 44, 47.50, 'unpaid', '2025-09-21 11:59:33'),
(10, 1, 45, 7.00, 'unpaid', '2025-09-21 12:34:47'),
(11, 2, 46, 417.25, 'unpaid', '2025-09-21 12:46:10');

-- --------------------------------------------------------

--
-- Table structure for table `utang_payments`
--

CREATE TABLE `utang_payments` (
  `id` int(11) NOT NULL,
  `utang_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_reminders`
--
ALTER TABLE `customer_reminders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `sales_items`
--
ALTER TABLE `sales_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `utang`
--
ALTER TABLE `utang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `utang_payments`
--
ALTER TABLE `utang_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utang_id` (`utang_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer_reminders`
--
ALTER TABLE `customer_reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `sales_items`
--
ALTER TABLE `sales_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `utang`
--
ALTER TABLE `utang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `utang_payments`
--
ALTER TABLE `utang_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `sales_items`
--
ALTER TABLE `sales_items`
  ADD CONSTRAINT `sales_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `sales_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `utang`
--
ALTER TABLE `utang`
  ADD CONSTRAINT `utang_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `utang_ibfk_2` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`);

--
-- Constraints for table `utang_payments`
--
ALTER TABLE `utang_payments`
  ADD CONSTRAINT `utang_payments_ibfk_1` FOREIGN KEY (`utang_id`) REFERENCES `utang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `utang_payments_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `utang_payments_ibfk_3` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
