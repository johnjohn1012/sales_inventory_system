-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 14, 2025 at 12:32 AM
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
-- Database: `harah_sales`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_disabled` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`, `created_at`, `is_disabled`) VALUES
(1, 'Desserts', 'Usually sweet and sugary', '2025-03-13 12:48:14', 0);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `first_name`, `last_name`, `contact_number`, `email`, `created_at`) VALUES
(1, 'Walk-in', 'Customer', NULL, NULL, '2025-03-13 12:45:55'),
(2, 'Walk-in', 'Customer', NULL, NULL, '2025-03-13 12:49:42'),
(3, 'Elon', 'Musk', NULL, NULL, '2025-03-13 22:06:21'),
(4, 'Cedrick', 'Lamar', NULL, NULL, '2025-03-13 22:33:14'),
(5, 'QR', 'Customer', NULL, NULL, '2025-03-13 22:34:30'),
(6, 'QR', 'Customer', NULL, NULL, '2025-03-13 22:37:43'),
(7, 'QR', 'Customer', NULL, NULL, '2025-03-13 22:45:13'),
(8, 'QR', 'Customer', NULL, NULL, '2025-03-13 22:47:55'),
(9, 'QR', 'Customer', NULL, NULL, '2025-03-13 22:47:59');

-- --------------------------------------------------------

--
-- Table structure for table `customer_feedback`
--

CREATE TABLE `customer_feedback` (
  `feedback_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `position` varchar(50) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `hire_date` date NOT NULL,
  `status` enum('ACTIVE','INACTIVE','ON_LEAVE','TERMINATED') NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `first_name`, `last_name`, `position`, `contact_number`, `email`, `address`, `hire_date`, `status`, `created_at`, `updated_at`) VALUES
(13, 'Margo', 'Admin', 'ADMIN', '09123456789', 'korosomag030@gmail.com', 'Manila', '2024-01-01', 'ACTIVE', '2025-03-13 12:30:32', '2025-03-13 23:06:40'),
(14, 'Clemens', 'Kitchen', 'KITCHEN', '09123456790', 'korosomag030@gmail.com', 'Manila', '2024-01-01', 'ACTIVE', '2025-03-13 12:30:32', '2025-03-13 23:06:44'),
(15, 'Francis', 'Cashier', 'CASHIER', '09123456791', 'korosomag030@gmail.com', 'Manila', '2024-01-01', 'ACTIVE', '2025-03-13 12:30:32', '2025-03-13 23:06:47'),
(16, 'Khendal', 'Waiter', 'WAITER', '09123456792', 'korosomag030@gmail.com', 'Manila', '2024-01-01', 'ACTIVE', '2025-03-13 12:30:32', '2025-03-13 23:06:49');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `type` enum('ORDER_READY','TABLE_STATUS','PAYMENT') NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `order_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(10, 4, 'New order received', 'ORDER_READY', 0, '2025-03-13 13:20:41'),
(11, 4, 'Order #4 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 13:21:05'),
(12, 5, 'New order received', 'ORDER_READY', 0, '2025-03-13 13:22:36'),
(13, 5, 'Order #5 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 13:22:49'),
(14, 4, 'Table 2 - Order #4 is ready for service', 'ORDER_READY', 0, '2025-03-13 13:23:21'),
(15, 4, 'Table 2 - Order #4 is ready for service', 'ORDER_READY', 0, '2025-03-13 13:23:21'),
(16, 5, 'Table 2 - Order #5 is ready for service', 'ORDER_READY', 0, '2025-03-13 13:23:25'),
(17, 5, 'Table 2 - Order #5 is ready for service', 'ORDER_READY', 0, '2025-03-13 13:23:25'),
(18, 6, 'New order received', 'ORDER_READY', 0, '2025-03-13 13:29:39'),
(19, 6, 'Order #6 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 13:29:47'),
(20, 7, 'New order received', 'ORDER_READY', 0, '2025-03-13 13:43:01'),
(21, 7, 'Order #7 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 13:45:29'),
(22, 8, 'New order received', 'ORDER_READY', 0, '2025-03-13 13:47:28'),
(23, 8, 'Order #8 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 13:48:37'),
(24, 6, 'Table 2 - Order #6 is ready for service', 'ORDER_READY', 0, '2025-03-13 13:49:01'),
(25, 6, 'Table 2 - Order #6 is ready for service', 'ORDER_READY', 0, '2025-03-13 13:49:01'),
(26, 9, 'New order received', 'ORDER_READY', 0, '2025-03-13 13:50:43'),
(27, 9, 'Order #9 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 13:50:50'),
(28, 9, 'Table 2 - Order #9 is ready for service', 'ORDER_READY', 0, '2025-03-13 13:51:19'),
(29, 9, 'Table 2 - Order #9 is ready for service', 'ORDER_READY', 0, '2025-03-13 13:51:19'),
(30, 10, 'New order received', 'ORDER_READY', 0, '2025-03-13 13:54:32'),
(31, 10, 'Order #10 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 13:54:39'),
(32, 10, 'Table 2 - Order #10 is ready for service', 'ORDER_READY', 0, '2025-03-13 13:55:08'),
(33, 10, 'Table 2 - Order #10 is ready for service', 'ORDER_READY', 0, '2025-03-13 13:55:08'),
(34, 11, 'New order received', 'ORDER_READY', 0, '2025-03-13 14:01:08'),
(35, 11, 'Order #11 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 14:01:24'),
(36, 11, 'Order #11 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 14:01:34'),
(37, 11, 'Table 2 - Order #11 is ready for service', 'ORDER_READY', 0, '2025-03-13 14:02:01'),
(38, 11, 'Table 2 - Order #11 is ready for service', 'ORDER_READY', 0, '2025-03-13 14:02:01'),
(39, 12, 'New order received', 'ORDER_READY', 0, '2025-03-13 14:04:48'),
(40, 12, 'Order #12 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 14:05:04'),
(41, 12, 'Table 2 - Order #12 is ready for service', 'ORDER_READY', 0, '2025-03-13 14:05:15'),
(42, 12, 'Table 2 - Order #12 is ready for service', 'ORDER_READY', 0, '2025-03-13 14:05:15'),
(43, 13, 'New order received', 'ORDER_READY', 0, '2025-03-13 14:10:57'),
(44, 13, 'Order #13 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 14:11:34'),
(45, 14, 'New order received', 'ORDER_READY', 0, '2025-03-13 14:13:55'),
(46, 14, 'Order #14 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 14:18:45'),
(47, 15, 'New order received', 'ORDER_READY', 0, '2025-03-13 14:20:49'),
(48, 16, 'New order received', 'ORDER_READY', 0, '2025-03-13 15:03:53'),
(49, 16, 'Order #16 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 15:04:05'),
(50, 15, 'Order #15 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 15:04:12'),
(51, 13, 'Table 2 - Order #13 is ready for service', 'ORDER_READY', 0, '2025-03-13 15:04:42'),
(52, 13, 'Table 2 - Order #13 is ready for service', 'ORDER_READY', 0, '2025-03-13 15:04:42'),
(53, 15, 'Table 2 - Order #15 is ready for service', 'ORDER_READY', 0, '2025-03-13 15:04:43'),
(54, 15, 'Table 2 - Order #15 is ready for service', 'ORDER_READY', 0, '2025-03-13 15:04:43'),
(55, 14, 'Table 2 - Order #14 is ready for service', 'ORDER_READY', 0, '2025-03-13 15:04:43'),
(56, 14, 'Table 2 - Order #14 is ready for service', 'ORDER_READY', 0, '2025-03-13 15:04:43'),
(57, 16, 'Table 2 - Order #16 is ready for service', 'ORDER_READY', 0, '2025-03-13 15:04:45'),
(58, 16, 'Table 2 - Order #16 is ready for service', 'ORDER_READY', 0, '2025-03-13 15:04:45'),
(59, 17, 'New order received', 'ORDER_READY', 0, '2025-03-13 15:09:34'),
(60, 17, 'Order #17 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 15:09:42'),
(61, 18, 'New order received', 'ORDER_READY', 0, '2025-03-13 15:15:10'),
(62, 18, 'Order #18 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 15:15:18'),
(63, 19, 'New order received', 'ORDER_READY', 0, '2025-03-13 22:06:26'),
(64, 19, 'Order #19 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 22:06:58'),
(65, 20, 'New order received', 'ORDER_READY', 0, '2025-03-13 22:27:19'),
(66, 21, 'New order received', 'ORDER_READY', 0, '2025-03-13 22:29:48'),
(67, 22, 'New order received', 'ORDER_READY', 0, '2025-03-13 22:33:14'),
(68, 23, 'New order received', 'ORDER_READY', 0, '2025-03-13 22:34:30'),
(69, 24, 'New order received', 'ORDER_READY', 0, '2025-03-13 22:37:43'),
(70, 24, 'Order #24 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 22:39:50'),
(71, 19, 'Table 2 - Order #19 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:43:31'),
(72, 19, 'Table 2 - Order #19 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:43:31'),
(73, 18, 'Table 2 - Order #18 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:43:33'),
(74, 18, 'Table 2 - Order #18 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:43:33'),
(75, 17, 'Table 2 - Order #17 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:43:34'),
(76, 17, 'Table 2 - Order #17 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:43:34'),
(77, 24, 'Table 1 - Order #24 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:44:57'),
(78, 24, 'Table 1 - Order #24 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:44:57'),
(79, 25, 'New order received', 'ORDER_READY', 0, '2025-03-13 22:45:13'),
(80, 25, 'Order #25 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 22:45:43'),
(81, 23, 'Order #23 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 22:45:46'),
(82, 22, 'Order #22 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 22:45:49'),
(83, 21, 'Order #21 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 22:45:52'),
(84, 20, 'Order #20 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 22:45:57'),
(85, 23, 'Table 2 - Order #23 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:46:54'),
(86, 23, 'Table 2 - Order #23 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:46:54'),
(87, 22, 'Table 2 - Order #22 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:46:56'),
(88, 22, 'Table 2 - Order #22 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:46:56'),
(89, 21, 'Table 2 - Order #21 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:46:57'),
(90, 21, 'Table 2 - Order #21 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:46:57'),
(91, 20, 'Table 2 - Order #20 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:46:58'),
(92, 20, 'Table 2 - Order #20 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:46:58'),
(93, 26, 'New order received', 'ORDER_READY', 0, '2025-03-13 22:47:55'),
(94, 27, 'New order received', 'ORDER_READY', 0, '2025-03-13 22:47:59'),
(95, 25, 'Table 1 - Order #25 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:48:11'),
(96, 25, 'Table 1 - Order #25 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:48:11'),
(97, 27, 'Order #27 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 22:48:23'),
(98, 26, 'Order #26 has been paid and is ready for preparation', 'ORDER_READY', 0, '2025-03-13 22:48:26'),
(99, 26, 'Table 1 - Order #26 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:48:54'),
(100, 26, 'Table 1 - Order #26 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:48:54'),
(101, 26, 'Table 1 - Order #26 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:48:54'),
(102, 26, 'Table 1 - Order #26 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:48:54'),
(103, 26, 'Table 1 - Order #26 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:48:55'),
(104, 26, 'Table 1 - Order #26 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:48:55'),
(105, 26, 'Table 1 - Order #26 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:48:56'),
(106, 26, 'Table 1 - Order #26 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:48:56'),
(107, 11, 'Table 2 - Order #11 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:48:58'),
(108, 11, 'Table 2 - Order #11 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:48:58'),
(109, 27, 'Table 1 - Order #27 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:51:37'),
(110, 27, 'Table 1 - Order #27 is ready for service', 'ORDER_READY', 0, '2025-03-13 22:51:37');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `order_type` enum('QR','WALK_IN') NOT NULL,
  `status` enum('PENDING','PAID','COMPLETED') DEFAULT 'PENDING',
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `table_id`, `customer_id`, `order_type`, `status`, `total_amount`, `created_at`, `updated_at`) VALUES
(4, 2, 2, 'QR', 'COMPLETED', 30.00, '2025-03-13 13:20:41', '2025-03-13 14:11:09'),
(5, 2, 2, 'QR', '', 10.00, '2025-03-13 13:22:36', '2025-03-13 13:23:25'),
(6, 2, 2, 'QR', '', 10.00, '2025-03-13 13:29:39', '2025-03-13 13:49:01'),
(7, 2, 2, 'QR', 'COMPLETED', 10.00, '2025-03-13 13:43:01', '2025-03-13 13:45:29'),
(8, 2, 2, 'QR', 'COMPLETED', 10.00, '2025-03-13 13:47:28', '2025-03-13 13:48:37'),
(9, 2, 2, 'QR', '', 20.00, '2025-03-13 13:50:43', '2025-03-13 13:51:19'),
(10, 2, 2, 'QR', 'COMPLETED', 10.00, '2025-03-13 13:54:32', '2025-03-13 22:50:35'),
(11, 2, 2, 'QR', 'COMPLETED', 80.00, '2025-03-13 14:01:08', '2025-03-13 22:48:58'),
(12, 2, 2, 'QR', 'COMPLETED', 50.00, '2025-03-13 14:04:48', '2025-03-13 14:05:15'),
(13, 2, 2, 'QR', 'COMPLETED', 10.00, '2025-03-13 14:10:57', '2025-03-13 15:04:42'),
(14, 2, 2, 'QR', 'COMPLETED', 10.00, '2025-03-13 14:13:55', '2025-03-13 15:04:43'),
(15, 2, 2, 'QR', 'COMPLETED', 10.00, '2025-03-13 14:20:49', '2025-03-13 15:04:43'),
(16, 2, 2, 'QR', 'COMPLETED', 30.00, '2025-03-13 15:03:53', '2025-03-13 15:04:45'),
(17, 2, 2, 'QR', 'COMPLETED', 10.00, '2025-03-13 15:09:34', '2025-03-13 22:43:34'),
(18, 2, 2, 'QR', 'COMPLETED', 50.00, '2025-03-13 15:15:10', '2025-03-13 22:43:33'),
(19, 2, 3, 'QR', 'COMPLETED', 30.00, '2025-03-13 22:06:26', '2025-03-13 22:43:31'),
(20, 2, 3, 'QR', 'COMPLETED', 20.00, '2025-03-13 22:27:19', '2025-03-13 22:46:58'),
(21, 2, 3, 'QR', 'COMPLETED', 20.00, '2025-03-13 22:29:48', '2025-03-13 22:46:57'),
(22, 2, 4, 'QR', 'COMPLETED', 10.00, '2025-03-13 22:33:14', '2025-03-13 22:46:56'),
(23, 2, 5, 'QR', 'COMPLETED', 10.00, '2025-03-13 22:34:30', '2025-03-13 22:46:54'),
(24, 1, 6, 'QR', 'COMPLETED', 30.00, '2025-03-13 22:37:43', '2025-03-13 22:44:57'),
(25, 1, 7, 'QR', 'COMPLETED', 30.00, '2025-03-13 22:45:13', '2025-03-13 22:48:11'),
(26, 1, 8, 'QR', 'COMPLETED', 10.00, '2025-03-13 22:47:55', '2025-03-13 22:48:56'),
(27, 1, 9, 'QR', 'COMPLETED', 40.00, '2025-03-13 22:47:59', '2025-03-13 22:51:37');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`, `created_at`) VALUES
(4, 4, 1, 3, 10.00, 30.00, '2025-03-13 13:20:41'),
(5, 5, 1, 1, 10.00, 10.00, '2025-03-13 13:22:36'),
(6, 6, 1, 1, 10.00, 10.00, '2025-03-13 13:29:39'),
(7, 7, 1, 1, 10.00, 10.00, '2025-03-13 13:43:01'),
(8, 8, 1, 1, 10.00, 10.00, '2025-03-13 13:47:28'),
(9, 9, 1, 2, 10.00, 20.00, '2025-03-13 13:50:43'),
(10, 10, 1, 1, 10.00, 10.00, '2025-03-13 13:54:32'),
(11, 11, 1, 8, 10.00, 80.00, '2025-03-13 14:01:08'),
(12, 12, 1, 5, 10.00, 50.00, '2025-03-13 14:04:48'),
(13, 13, 1, 1, 10.00, 10.00, '2025-03-13 14:10:57'),
(14, 14, 1, 1, 10.00, 10.00, '2025-03-13 14:13:55'),
(15, 15, 1, 1, 10.00, 10.00, '2025-03-13 14:20:49'),
(16, 16, 1, 3, 10.00, 30.00, '2025-03-13 15:03:53'),
(17, 17, 1, 1, 10.00, 10.00, '2025-03-13 15:09:34'),
(18, 18, 1, 5, 10.00, 50.00, '2025-03-13 15:15:10'),
(19, 19, 1, 3, 10.00, 30.00, '2025-03-13 22:06:26'),
(20, 20, 1, 2, 10.00, 20.00, '2025-03-13 22:27:19'),
(21, 21, 1, 2, 10.00, 20.00, '2025-03-13 22:29:48'),
(22, 22, 1, 1, 10.00, 10.00, '2025-03-13 22:33:14'),
(23, 23, 1, 1, 10.00, 10.00, '2025-03-13 22:34:30'),
(24, 24, 1, 3, 10.00, 30.00, '2025-03-13 22:37:43'),
(25, 25, 1, 3, 10.00, 30.00, '2025-03-13 22:45:13'),
(26, 26, 1, 1, 10.00, 10.00, '2025-03-13 22:47:55'),
(27, 27, 1, 4, 10.00, 40.00, '2025-03-13 22:47:59');

-- --------------------------------------------------------

--
-- Table structure for table `order_modifications`
--

CREATE TABLE `order_modifications` (
  `modification_id` int(11) NOT NULL,
  `order_item_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `transaction_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `change_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('CASH','GCASH') NOT NULL,
  `status` enum('PENDING','COMPLETED','FAILED') NOT NULL DEFAULT 'PENDING',
  `transaction_reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_transactions`
--

INSERT INTO `payment_transactions` (`transaction_id`, `order_id`, `total_amount`, `amount_paid`, `change_amount`, `payment_method`, `status`, `transaction_reference`, `created_at`) VALUES
(1, 18, 50.00, 222.00, 172.00, 'CASH', 'COMPLETED', NULL, '2025-03-13 15:15:18'),
(2, 19, 30.00, 100.00, 70.00, 'CASH', 'COMPLETED', NULL, '2025-03-13 22:06:58'),
(3, 24, 30.00, 111.00, 81.00, 'CASH', 'COMPLETED', NULL, '2025-03-13 22:39:50'),
(4, 25, 30.00, 111.00, 81.00, 'CASH', 'COMPLETED', NULL, '2025-03-13 22:45:43'),
(5, 23, 10.00, 123.00, 113.00, 'CASH', 'COMPLETED', NULL, '2025-03-13 22:45:46'),
(6, 22, 10.00, 55.00, 45.00, 'CASH', 'COMPLETED', NULL, '2025-03-13 22:45:49'),
(7, 21, 20.00, 555.00, 535.00, 'CASH', 'COMPLETED', NULL, '2025-03-13 22:45:52'),
(8, 20, 20.00, 111.00, 91.00, 'CASH', 'COMPLETED', NULL, '2025-03-13 22:45:57'),
(9, 27, 40.00, 111.00, 71.00, 'CASH', 'COMPLETED', NULL, '2025-03-13 22:48:23'),
(10, 26, 10.00, 21512.00, 21502.00, 'CASH', 'COMPLETED', NULL, '2025-03-13 22:48:26');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_disabled` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `name`, `description`, `price`, `image_url`, `is_available`, `created_at`, `is_disabled`) VALUES
(1, 1, 'Ice Cream', 'Yummy Coned Dessert', 10.00, 'uploads/products/67d2d434825f4.png', 1, '2025-03-13 12:48:52', 0);

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `table_id` int(11) NOT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `number_of_guests` int(11) NOT NULL,
  `status` enum('PENDING','CONFIRMED','CANCELLED','COMPLETED') NOT NULL DEFAULT 'PENDING',
  `special_requests` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `total_revenue` decimal(10,2) NOT NULL,
  `cash_revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
  `gcash_revenue` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_orders` int(11) NOT NULL DEFAULT 0,
  `cash_orders` int(11) NOT NULL DEFAULT 0,
  `gcash_orders` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sale_id`, `date`, `total_revenue`, `cash_revenue`, `gcash_revenue`, `total_orders`, `cash_orders`, `gcash_orders`, `created_at`, `updated_at`) VALUES
(1, '2025-03-13', 200.00, 200.00, 0.00, 9, 9, 0, '2025-03-13 22:06:58', '2025-03-13 22:48:26');

-- --------------------------------------------------------

--
-- Table structure for table `shift_schedules`
--

CREATE TABLE `shift_schedules` (
  `schedule_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shift_schedules`
--

INSERT INTO `shift_schedules` (`schedule_id`, `name`, `start_time`, `end_time`, `created_at`) VALUES
(1, 'Shift A', '08:45:00', '18:40:00', '2025-03-13 15:45:56');

-- --------------------------------------------------------

--
-- Table structure for table `staff_shifts`
--

CREATE TABLE `staff_shifts` (
  `staff_shift_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `shift_date` date NOT NULL,
  `status` enum('PRESENT','ABSENT','LATE','HALF_DAY') NOT NULL DEFAULT 'PRESENT',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_shifts`
--

INSERT INTO `staff_shifts` (`staff_shift_id`, `employee_id`, `schedule_id`, `shift_date`, `status`, `created_at`) VALUES
(1, 15, 1, '2025-03-14', 'PRESENT', '2025-03-13 21:52:07');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`log_id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 13, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 12:30:50'),
(2, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 12:31:57'),
(3, 13, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 12:36:01'),
(4, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 12:38:48'),
(5, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 12:39:51'),
(6, 16, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 12:40:09'),
(7, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 12:45:18'),
(8, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 12:45:26'),
(9, 13, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 12:45:49'),
(10, 16, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 12:49:07'),
(11, 13, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 12:49:31'),
(12, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 12:55:48'),
(13, 15, 'PROCESS_PAYMENT', 'Processed payment for order #1 using CASH', '::1', '2025-03-13 13:02:33'),
(14, 15, 'PROCESS_PAYMENT', 'Processed payment for order #1 using CASH', '::1', '2025-03-13 13:02:33'),
(15, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:04:39'),
(16, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:08:08'),
(17, 15, 'PROCESS_PAYMENT', 'Processed payment for order #2 using CASH', '::1', '2025-03-13 13:08:25'),
(18, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:08:46'),
(19, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:09:06'),
(20, 16, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:09:17'),
(21, 16, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:09:17'),
(22, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:09:53'),
(23, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:14:54'),
(24, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:17:19'),
(25, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:20:11'),
(26, 15, 'PROCESS_PAYMENT', 'Processed payment for order #4 using CASH', '::1', '2025-03-13 13:21:05'),
(27, 15, 'PROCESS_PAYMENT', 'Processed payment for order #5 using CASH', '::1', '2025-03-13 13:22:49'),
(28, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:23:10'),
(29, 16, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:23:45'),
(30, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:24:01'),
(31, 15, 'PROCESS_PAYMENT', 'Processed payment for order #6 using CASH', '::1', '2025-03-13 13:29:47'),
(32, 15, 'PROCESS_PAYMENT', 'Processed payment for order #7 using CASH', '::1', '2025-03-13 13:45:29'),
(33, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:47:19'),
(34, 15, 'PROCESS_PAYMENT', 'Processed payment for order #8 using CASH', '::1', '2025-03-13 13:48:37'),
(35, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:48:53'),
(36, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:49:17'),
(37, 15, 'PROCESS_PAYMENT', 'Processed payment for order #9 using CASH', '::1', '2025-03-13 13:50:50'),
(38, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:51:13'),
(39, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:51:36'),
(40, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:52:10'),
(41, 16, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:52:47'),
(42, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:54:24'),
(43, 15, 'PROCESS_PAYMENT', 'Processed payment for order #10 using CASH', '::1', '2025-03-13 13:54:39'),
(44, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:55:01'),
(45, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 13:55:15'),
(46, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 14:01:15'),
(47, 15, 'PROCESS_PAYMENT', 'Processed payment for order #11 using CASH', '::1', '2025-03-13 14:01:24'),
(48, 15, 'PROCESS_PAYMENT', 'Processed payment for order #11 using CASH', '::1', '2025-03-13 14:01:34'),
(49, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 14:01:55'),
(50, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 14:02:11'),
(51, 16, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 14:02:30'),
(52, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 14:04:54'),
(53, 15, 'PROCESS_PAYMENT', 'Processed payment for order #12 using CASH', '::1', '2025-03-13 14:05:04'),
(54, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 14:05:12'),
(55, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 14:05:45'),
(56, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 14:11:26'),
(57, 15, 'PROCESS_PAYMENT', 'Processed payment for order #13 using CASH', '::1', '2025-03-13 14:11:34'),
(58, 15, 'PROCESS_PAYMENT', 'Processed payment for order #14 using CASH', '::1', '2025-03-13 14:18:45'),
(59, 16, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 14:33:18'),
(60, 16, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 14:53:26'),
(61, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 15:03:33'),
(62, 15, 'PROCESS_PAYMENT', 'Processed payment for order #16 using CASH', '::1', '2025-03-13 15:04:05'),
(63, 15, 'PROCESS_PAYMENT', 'Processed payment for order #15 using CASH', '::1', '2025-03-13 15:04:12'),
(64, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 15:04:24'),
(65, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 15:05:03'),
(66, 15, 'PROCESS_PAYMENT', 'Processed payment for order #17 using CASH', '::1', '2025-03-13 15:09:42'),
(67, 15, 'PROCESS_PAYMENT', 'Processed payment for order #18 using CASH. Total: 50, Paid: 222, Change: 172', '::1', '2025-03-13 15:15:18'),
(68, 13, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 15:23:33'),
(69, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 15:32:07'),
(70, 13, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 15:33:13'),
(71, 13, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 21:49:42'),
(72, 13, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 22:06:11'),
(73, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 22:06:42'),
(74, 15, 'PROCESS_PAYMENT', 'Processed payment for order #19 using CASH. Total: 30, Paid: 100, Change: 70', '::1', '2025-03-13 22:06:58'),
(75, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 22:27:39'),
(76, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 22:39:42'),
(77, 15, 'PROCESS_PAYMENT', 'Processed payment for order #24 using CASH. Total: 30, Paid: 111, Change: 81', '::1', '2025-03-13 22:39:50'),
(78, 13, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 22:42:20'),
(79, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 22:43:22'),
(80, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 22:45:18'),
(81, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 22:45:33'),
(82, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 22:45:39'),
(83, 15, 'PROCESS_PAYMENT', 'Processed payment for order #25 using CASH. Total: 30, Paid: 111, Change: 81', '::1', '2025-03-13 22:45:43'),
(84, 15, 'PROCESS_PAYMENT', 'Processed payment for order #23 using CASH. Total: 10, Paid: 123, Change: 113', '::1', '2025-03-13 22:45:46'),
(85, 15, 'PROCESS_PAYMENT', 'Processed payment for order #22 using CASH. Total: 10, Paid: 55, Change: 45', '::1', '2025-03-13 22:45:49'),
(86, 15, 'PROCESS_PAYMENT', 'Processed payment for order #21 using CASH. Total: 20, Paid: 555, Change: 535', '::1', '2025-03-13 22:45:52'),
(87, 15, 'PROCESS_PAYMENT', 'Processed payment for order #20 using CASH. Total: 20, Paid: 111, Change: 91', '::1', '2025-03-13 22:45:57'),
(88, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 22:46:10'),
(89, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 22:48:17'),
(90, 15, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 22:48:17'),
(91, 15, 'PROCESS_PAYMENT', 'Processed payment for order #27 using CASH. Total: 40, Paid: 111, Change: 71', '::1', '2025-03-13 22:48:23'),
(92, 15, 'PROCESS_PAYMENT', 'Processed payment for order #26 using CASH. Total: 10, Paid: 21512, Change: 21502', '::1', '2025-03-13 22:48:26'),
(93, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 22:48:31'),
(94, 14, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 22:48:50'),
(95, 16, 'LOGIN', 'User logged in successfully', '::1', '2025-03-13 22:51:45');

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `table_id` int(11) NOT NULL,
  `table_number` varchar(10) NOT NULL,
  `qr_code` varchar(255) NOT NULL,
  `status` enum('AVAILABLE','OCCUPIED','READY','CLEANING') DEFAULT 'AVAILABLE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`table_id`, `table_number`, `qr_code`, `status`, `created_at`) VALUES
(1, '1', 'table_1_67d2d38198ab5', 'OCCUPIED', '2025-03-13 12:45:53'),
(2, '2', 'table_2_67d2da3381171', 'OCCUPIED', '2025-03-13 13:14:27');

-- --------------------------------------------------------

--
-- Table structure for table `two_factor_auth_codes`
--

CREATE TABLE `two_factor_auth_codes` (
  `code_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `two_factor_auth_codes`
--

INSERT INTO `two_factor_auth_codes` (`code_id`, `user_id`, `code`, `is_used`, `expires_at`, `created_at`) VALUES
(1, 13, '976283', 0, '2025-03-13 16:22:10', '2025-03-13 23:17:10'),
(2, 13, '641152', 0, '2025-03-13 16:23:07', '2025-03-13 23:18:07'),
(3, 13, '820038', 0, '2025-03-13 16:24:29', '2025-03-13 23:19:29'),
(4, 15, '432402', 1, '2025-03-13 23:21:20', '2025-03-13 23:21:04'),
(5, 13, '310954', 1, '2025-03-13 23:23:23', '2025-03-13 23:23:04'),
(6, 15, '276549', 1, '2025-03-13 23:23:44', '2025-03-13 23:23:29'),
(7, 15, '545250', 1, '2025-03-13 23:24:40', '2025-03-13 23:24:19'),
(8, 15, '295128', 1, '2025-03-13 23:25:29', '2025-03-13 23:25:06'),
(9, 16, '114344', 1, '2025-03-13 23:25:59', '2025-03-13 23:25:48'),
(10, 16, '811934', 1, '2025-03-13 23:27:04', '2025-03-13 23:26:36'),
(11, 14, '061403', 1, '2025-03-13 23:28:29', '2025-03-13 23:27:49'),
(12, 15, '830912', 1, '2025-03-13 23:30:58', '2025-03-13 23:30:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('ADMIN','CASHIER','KITCHEN','WAITER') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `employee_id`, `username`, `password`, `role`, `created_at`) VALUES
(13, 13, 'margo', '$2y$10$v0EYhYBlLdySusdcweETx.28ZRK.paxSv/.Iz9aAym4pXPDo7XKza', 'ADMIN', '2025-03-13 12:30:32'),
(14, 14, 'clemens', '$2y$10$v0EYhYBlLdySusdcweETx.28ZRK.paxSv/.Iz9aAym4pXPDo7XKza', 'KITCHEN', '2025-03-13 12:30:32'),
(15, 15, 'francis', '$2y$10$v0EYhYBlLdySusdcweETx.28ZRK.paxSv/.Iz9aAym4pXPDo7XKza', 'CASHIER', '2025-03-13 12:30:32'),
(16, 16, 'khendal', '$2y$10$v0EYhYBlLdySusdcweETx.28ZRK.paxSv/.Iz9aAym4pXPDo7XKza', 'WAITER', '2025-03-13 12:30:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `table_id` (`table_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_modifications`
--
ALTER TABLE `order_modifications`
  ADD PRIMARY KEY (`modification_id`),
  ADD KEY `order_item_id` (`order_item_id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `table_id` (`table_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD UNIQUE KEY `date` (`date`);

--
-- Indexes for table `shift_schedules`
--
ALTER TABLE `shift_schedules`
  ADD PRIMARY KEY (`schedule_id`);

--
-- Indexes for table `staff_shifts`
--
ALTER TABLE `staff_shifts`
  ADD PRIMARY KEY (`staff_shift_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`table_id`),
  ADD UNIQUE KEY `table_number` (`table_number`);

--
-- Indexes for table `two_factor_auth_codes`
--
ALTER TABLE `two_factor_auth_codes`
  ADD PRIMARY KEY (`code_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `order_modifications`
--
ALTER TABLE `order_modifications`
  MODIFY `modification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `shift_schedules`
--
ALTER TABLE `shift_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `staff_shifts`
--
ALTER TABLE `staff_shifts`
  MODIFY `staff_shift_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `table_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `two_factor_auth_codes`
--
ALTER TABLE `two_factor_auth_codes`
  MODIFY `code_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD CONSTRAINT `customer_feedback_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `customer_feedback_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`table_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `order_modifications`
--
ALTER TABLE `order_modifications`
  ADD CONSTRAINT `order_modifications_ibfk_1` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`order_item_id`);

--
-- Constraints for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`table_id`) REFERENCES `tables` (`table_id`);

--
-- Constraints for table `staff_shifts`
--
ALTER TABLE `staff_shifts`
  ADD CONSTRAINT `staff_shifts_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `staff_shifts_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `shift_schedules` (`schedule_id`);

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `two_factor_auth_codes`
--
ALTER TABLE `two_factor_auth_codes`
  ADD CONSTRAINT `two_factor_auth_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
