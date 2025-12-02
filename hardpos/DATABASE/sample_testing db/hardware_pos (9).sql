-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2025 at 05:01 PM
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
(1, 'Added category: CEMENT with 2 points per item', '2025-10-01 15:45:16'),
(2, 'Added product: HOLCIM EXCEL', '2025-10-01 15:52:31'),
(3, 'Added new customer: lee as regular customer', '2025-10-01 16:11:25'),
(4, 'Customer lee marked as loyal', '2025-10-01 16:12:18'),
(5, 'Customer lee marked as loyal', '2025-10-01 16:31:49'),
(6, 'Customer lee marked as loyal', '2025-10-01 16:31:58'),
(7, 'Added category: CEMENT with 2 points per item', '2025-10-02 13:36:13'),
(8, 'Added product: HOLCIM EXCEL', '2025-10-02 13:36:59'),
(9, 'New sale: ID 50, Amount: ₱195, Payment: Cash', '2025-10-02 13:47:16'),
(10, 'New sale: ID 51, Amount: ₱195, Payment: Cash, Customer: 3', '2025-10-02 16:26:21'),
(11, 'Customer lee marked as loyal', '2025-10-02 16:34:46'),
(12, 'New sale: ID 52, Amount: ₱195, Payment: Cash, Customer: 3', '2025-10-02 16:35:09'),
(13, 'New sale: ID 53, Amount: ₱195, Payment: Cash, Customer: 3, Points earned: 1', '2025-10-02 17:01:39'),
(14, 'Added category: BULBS with 10 points per item', '2025-10-02 17:04:23'),
(15, 'Added product: FIREFLY 11W LED DAYLIGHT', '2025-10-02 17:07:33'),
(16, 'New sale: ID 54, Amount: ₱134, Payment: Cash, Customer: 3', '2025-10-02 17:07:54'),
(17, 'New sale: ID 55, Amount: ₱134, Payment: Cash, Customer: 3', '2025-10-02 17:17:58'),
(18, 'New sale: ID 56, Amount: ₱134, Payment: Cash, Customer: 3, Points earned: 10', '2025-10-02 17:24:13'),
(19, 'Added category: HOLLOW BLOCK with 1 points per item', '2025-10-02 17:25:25'),
(20, 'Added product: HOLLOW BLOCKS #4', '2025-10-02 17:26:56'),
(21, 'New sale: ID 57, Amount: ₱12, Payment: Cash, Customer: 3', '2025-10-02 17:27:19'),
(22, 'New sale: ID 58, Amount: ₱12, Payment: Cash, Customer: 3', '2025-10-02 17:28:20'),
(23, 'New sale: ID 59, Amount: ₱12, Payment: Cash, Customer: 3', '2025-10-02 17:30:39'),
(24, 'Added category: PAINT SUPPLY with 5 points per item', '2025-10-02 17:39:15'),
(25, 'Added product: PAINT TRAY in category: PAINT SUPPLY', '2025-10-02 17:42:42'),
(26, 'New sale: ID 60, Amount: ₱90, Payment: Cash, Customer: 3, Points earned: 10', '2025-10-02 17:43:34'),
(27, 'New sale: ID 61, Amount: ₱12, Payment: Cash, Customer: 3, Points earned: 1', '2025-10-02 17:44:04'),
(28, 'New sale: ID 62, Amount: ₱179, Payment: Cash, Customer: 3, Points earned: 15', '2025-10-02 17:46:57'),
(29, 'Added product: SANDING SEALER APOLLO ISLAND in category: PAINT SUPPLY', '2025-10-02 18:18:54'),
(30, 'Added product: REPUBLIC in category: CEMENT', '2025-10-02 18:30:34'),
(31, 'Added product: REPUBLIC in category: CEMENT with stock: 20', '2025-10-02 18:33:10'),
(32, 'Added product: REPUBLIC in category: CEMENT with stock: 20', '2025-10-02 18:34:21'),
(33, 'Added product: REPUBLIC in category: CEMENT with stock: 20', '2025-10-02 18:51:56'),
(34, 'Added product: SANDING SEALER APOLLO ISLAND in category: PAINT SUPPLY with stock: 20', '2025-10-02 18:53:30'),
(35, 'Adjusted points for customer lee: added 1 points. Reason: TRY', '2025-10-03 17:00:26'),
(36, 'Adjusted points for customer lee: deducted 1 points. Reason: TRY', '2025-10-03 17:00:59'),
(37, 'Customer lee redeemed 15 points for commission', '2025-10-03 17:01:40'),
(38, 'New sale: ID 63, Amount: ₱760, Payment: Cash, Customer: 3, Points earned: 5', '2025-10-04 12:49:02'),
(39, 'Customer lee redeemed 10 points for commission', '2025-10-05 16:12:53'),
(40, 'Customer lee redeemed 10 points for commission', '2025-10-05 16:21:13'),
(41, 'Customer lee redeemed 2 points for commission', '2025-10-05 16:24:33'),
(42, 'Customer lee redeemed 1 points for commission', '2025-10-05 16:30:29'),
(43, 'Customer lee redeemed 1 points for commission', '2025-10-05 16:41:27'),
(44, 'Customer lee redeemed 1 points for commission', '2025-10-05 16:41:40'),
(45, 'Adjusted points for customer lee: added 20 points. Reason: TRY', '2025-10-05 16:42:02'),
(46, 'Customer lee redeemed 5 points for commission', '2025-10-05 16:57:28');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `points_per_item` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `image`, `points_per_item`) VALUES
(2, 'CEMENT', 'uploads/cat_68de7fcdd71cc5.43484127.jpg', 1),
(3, 'BULBS', NULL, 10),
(4, 'HOLLOW BLOCK', NULL, 1),
(5, 'PAINT SUPPLY', NULL, 5);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_loyal` tinyint(1) DEFAULT 0,
  `points_balance` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `created_at`, `is_loyal`, `points_balance`) VALUES
(3, 'lee', '09179586502', 'lee@gmail.com', 'balide,aurora', '2025-10-01 16:11:25', 1, 17);

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
  `sale_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash',
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `customer_id`, `sale_id`, `amount`, `payment_method`, `paid_at`) VALUES
(1, 3, NULL, 5.00, 'Points Redemption', '2025-10-05 16:57:28');

-- --------------------------------------------------------

--
-- Table structure for table `points_transactions`
--

CREATE TABLE `points_transactions` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `points_earned` int(11) DEFAULT 0,
  `points_used` int(11) DEFAULT 0,
  `transaction_date` datetime NOT NULL,
  `sale_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `points_transactions`
--

INSERT INTO `points_transactions` (`id`, `customer_id`, `points_earned`, `points_used`, `transaction_date`, `sale_id`) VALUES
(1, 3, 1, 0, '2025-10-03 01:01:39', 53),
(2, 3, 10, 0, '2025-10-03 01:24:13', 56),
(3, 3, 10, 0, '2025-10-03 01:43:34', 60),
(4, 3, 1, 0, '2025-10-03 01:44:04', 61),
(5, 3, 15, 0, '2025-10-03 01:46:57', 62),
(6, 3, 1, 0, '2025-10-04 01:00:25', NULL),
(7, 3, 0, 1, '2025-10-04 01:00:59', NULL),
(8, 3, 0, 15, '2025-10-04 01:01:40', NULL),
(9, 3, 5, 0, '2025-10-04 20:49:02', 63),
(10, 3, 0, 10, '2025-10-06 00:12:53', NULL),
(11, 3, 0, 10, '2025-10-06 00:21:13', NULL),
(12, 3, 0, 2, '2025-10-06 00:24:33', NULL),
(13, 3, 0, 1, '2025-10-06 00:30:29', NULL),
(14, 3, 0, 1, '2025-10-06 00:41:27', NULL),
(15, 3, 0, 1, '2025-10-06 00:41:40', NULL),
(16, 3, 20, 0, '2025-10-06 00:42:02', NULL),
(17, 3, 0, 5, '2025-10-06 00:57:28', NULL);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `category`, `brand`, `unit`, `price`, `stock`, `image`, `created_at`, `category_id`) VALUES
(18, 'HOLCIM EXCEL', 'CEMENT', NULL, 'sack', 195.00, 16.00, 'uploads/1759412219_images.jpg', '2025-10-02 13:36:59', 2),
(19, 'FIREFLY 11W LED DAYLIGHT', 'BULBS', NULL, 'piece', 134.00, 16.00, 'uploads/1759424853_fc554060490abf539e65c68694a7de42.jpg', '2025-10-02 17:07:33', 3),
(20, 'HOLLOW BLOCKS #4', 'HOLLOW BLOCK', NULL, 'piece', 12.00, 96.00, 'uploads/1759426016_images (1).jpg', '2025-10-02 17:26:56', 4),
(21, 'PAINT TRAY', 'PAINT SUPPLY', NULL, '0', 45.00, 17.00, 'uploads/1759426962_images (2).jpg', '2025-10-02 17:42:42', 5),
(26, 'REPUBLIC', 'CEMENT', NULL, 'sack', 205.00, 20.00, 'uploads/1759431116_images (4).jpg', '2025-10-02 18:51:56', 2),
(27, 'SANDING SEALER APOLLO ISLAND', 'PAINT SUPPLY', NULL, 'piece', 760.00, 19.00, 'uploads/1759431210_images (3).jpg', '2025-10-02 18:53:30', 5);

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
(50, NULL, 1, 195.00, 'Cash', '2025-10-02 13:47:16', 0.00),
(51, 3, 1, 195.00, 'Cash', '2025-10-02 16:26:21', 0.00),
(52, 3, 1, 195.00, 'Cash', '2025-10-02 16:35:09', 0.00),
(53, 3, 1, 195.00, 'Cash', '2025-10-02 17:01:38', 0.00),
(54, 3, 1, 134.00, 'Cash', '2025-10-02 17:07:54', 0.00),
(55, 3, 1, 134.00, 'Cash', '2025-10-02 17:17:58', 0.00),
(56, 3, 1, 134.00, 'Cash', '2025-10-02 17:24:12', 0.00),
(57, 3, 1, 12.00, 'Cash', '2025-10-02 17:27:19', 0.00),
(58, 3, 1, 12.00, 'Cash', '2025-10-02 17:28:20', 0.00),
(59, 3, 1, 12.00, 'Cash', '2025-10-02 17:30:39', 0.00),
(60, 3, 1, 90.00, 'Cash', '2025-10-02 17:43:34', 0.00),
(61, 3, 1, 12.00, 'Cash', '2025-10-02 17:44:04', 0.00),
(62, 3, 1, 179.00, 'Cash', '2025-10-02 17:46:57', 0.00),
(63, 3, 1, 760.00, 'Cash', '2025-10-04 12:49:02', 0.00);

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
(1, 50, 18, 1.00, 195.00, 195.00),
(2, 51, 18, 1.00, 195.00, 195.00),
(3, 52, 18, 1.00, 195.00, 195.00),
(4, 53, 18, 1.00, 195.00, 195.00),
(5, 54, 19, 1.00, 134.00, 134.00),
(6, 55, 19, 1.00, 134.00, 134.00),
(7, 56, 19, 1.00, 134.00, 134.00),
(8, 57, 20, 1.00, 12.00, 12.00),
(9, 58, 20, 1.00, 12.00, 12.00),
(10, 59, 20, 1.00, 12.00, 12.00),
(11, 60, 21, 2.00, 45.00, 90.00),
(12, 61, 20, 1.00, 12.00, 12.00),
(13, 62, 19, 1.00, 134.00, 134.00),
(14, 62, 21, 1.00, 45.00, 45.00),
(15, 63, 27, 1.00, 760.00, 760.00);

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
(1, 'admin', '$2y$10$YY01AXw/1KtfTZIe9CPMte0anOg888zOhGeApeYcnIwMkyxWkZixi', 'admin', NULL, '2025-08-16 19:40:59');

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
  ADD UNIQUE KEY `category_name` (`category_name`),
  ADD KEY `idx_category_name` (`category_name`);

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
-- Indexes for table `points_transactions`
--
ALTER TABLE `points_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `points_transactions`
--
ALTER TABLE `points_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `sales_items`
--
ALTER TABLE `sales_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
-- Constraints for table `points_transactions`
--
ALTER TABLE `points_transactions`
  ADD CONSTRAINT `points_transactions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `points_transactions_ibfk_2` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

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
