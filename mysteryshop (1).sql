-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 31, 2026 at 04:05 AM
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
-- Database: `mysteryshop`
--

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`cart_id`, `user_id`, `created_at`) VALUES
(1, 48, '2026-03-20 10:51:15'),
(2, 61, '2026-03-29 06:43:06');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`cart_item_id`, `cart_id`, `product_id`, `quantity`) VALUES
(44, 2, 16, 1),
(45, 2, 17, 3),
(46, 2, 18, 1),
(47, 2, 19, 1),
(69, 1, 16, 1),
(70, 1, 17, 1),
(71, 1, 29, 2),
(72, 1, 20, 2),
(73, 1, 21, 2);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(1, 'Accessories'),
(2, 'Makeups'),
(3, 'Bags'),
(4, 'Fashion'),
(5, 'Sports'),
(6, 'Electronics');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`conversation_id`, `user_id`, `admin_id`, `order_id`, `subject`, `last_message_at`, `created_at`, `updated_at`) VALUES
(3, 48, 60, 43, 'Order #43', '2026-03-29 21:35:56', '2026-03-29 21:29:51', '2026-03-29 21:35:56'),
(4, 48, 60, 44, 'Premium Yoga Mat', '2026-03-30 11:47:28', '2026-03-29 21:41:17', '2026-03-30 11:47:28'),
(5, 48, 60, 45, 'KMY Black Show', '2026-03-29 22:29:24', '2026-03-29 22:29:24', '2026-03-29 22:29:24'),
(6, 48, 60, 46, 'Headset Bluetooth X-One', '2026-03-29 22:54:13', '2026-03-29 22:54:13', '2026-03-29 22:54:13'),
(7, 48, 60, 47, 'KMY Black Show', '2026-03-29 23:07:58', '2026-03-29 23:07:58', '2026-03-29 23:07:58'),
(8, 48, 60, 48, 'KMY Black Show', '2026-03-30 00:36:48', '2026-03-29 23:10:59', '2026-03-30 00:36:48'),
(9, 48, 60, 49, 'Samsung Galaxy A21s 6/128', '2026-03-29 23:13:12', '2026-03-29 23:11:18', '2026-03-29 23:13:12'),
(10, 48, 60, 50, 'KMY Black Show', '2026-03-29 23:14:09', '2026-03-29 23:14:09', '2026-03-29 23:14:09'),
(11, 48, 60, 51, 'Headset Bluetooth X-One', '2026-03-29 23:14:30', '2026-03-29 23:14:30', '2026-03-29 23:14:30'),
(12, 48, 60, 52, 'Headset Bluetooth X-One', '2026-03-29 23:18:16', '2026-03-29 23:18:16', '2026-03-29 23:18:16'),
(13, 48, 60, 53, 'KMY Black Show', '2026-03-30 14:48:05', '2026-03-29 23:18:54', '2026-03-30 14:48:05'),
(14, 48, 60, 54, 'Samsung Galaxy A21s 6/128', '2026-03-30 10:58:46', '2026-03-30 10:58:46', '2026-03-30 10:58:46'),
(15, 48, 60, 55, 'Low Heels Black Shoe', '2026-03-30 11:13:45', '2026-03-30 11:13:45', '2026-03-30 11:13:45'),
(16, 48, 60, 56, 'Headset Bluetooth X-One', '2026-03-30 12:59:00', '2026-03-30 12:59:00', '2026-03-30 12:59:00'),
(17, 48, 60, 57, 'Samsung Galaxy A21s 6/128', '2026-03-30 14:19:50', '2026-03-30 14:19:50', '2026-03-30 14:19:50'),
(18, 48, 60, 58, 'New High Heels Black shoe', '2026-03-30 14:29:04', '2026-03-30 14:29:04', '2026-03-30 14:29:04'),
(19, 48, 60, 59, 'KMY Black Show', '2026-03-30 14:36:27', '2026-03-30 14:36:27', '2026-03-30 14:36:27'),
(20, 48, 60, 60, 'Black Socks', '2026-03-30 14:40:07', '2026-03-30 14:40:07', '2026-03-30 14:40:07'),
(21, 48, 60, 61, 'New Samsung Headphone', '2026-03-30 18:15:30', '2026-03-30 16:38:34', '2026-03-30 18:15:30'),
(22, 48, 60, 62, 'Shopping Bag | Tote BAg', '2026-03-30 19:07:32', '2026-03-30 19:05:50', '2026-03-30 19:07:32'),
(23, 48, 60, 63, 'Black Socks', '2026-03-30 19:33:04', '2026-03-30 19:33:04', '2026-03-30 19:33:04'),
(24, 48, 60, 64, 'Shopping Bag | Tote BAg', '2026-03-30 21:00:54', '2026-03-30 21:00:54', '2026-03-30 21:00:54'),
(25, 48, 60, 65, 'Nike Air Max 270', '2026-03-30 22:36:50', '2026-03-30 21:42:49', '2026-03-30 22:36:50');

-- --------------------------------------------------------

--
-- Table structure for table `conversation_participants`
--

CREATE TABLE `conversation_participants` (
  `conversation_id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `participant_role` enum('user','admin') NOT NULL,
  `last_read_message_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_profiles`
--

CREATE TABLE `customer_profiles` (
  `user_id` int(11) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_profiles`
--

INSERT INTO `customer_profiles` (`user_id`, `phone_number`, `gender`, `birthday`, `profile_picture`) VALUES
(48, '09814585663', 'Male', '2005-02-02', 'user_48_1774537585.jpg'),
(61, '', '', '0000-00-00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_role` enum('user','admin','system') NOT NULL,
  `message_text` text NOT NULL,
  `message_type` enum('chat','order_notice','status_notice') NOT NULL DEFAULT 'chat',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `conversation_id`, `sender_id`, `sender_role`, `message_text`, `message_type`, `is_read`, `created_at`) VALUES
(1, 3, 0, 'system', 'Hello! Thank you for your order.\nOrder #43 has been placed successfully.\nItems: 1\nTotal: PHP 499.00\nPayment: CASH\nCurrent status: Pending\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-29 21:29:51'),
(2, 3, 60, 'admin', 'thankyou for ordering', 'chat', 1, '2026-03-29 21:35:56'),
(3, 4, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: Premium Yoga Mat\nOrder Details\nOrder Date: 2026-03-29 15:41:17\nStatus: Pending\nPayment: CASH\nRecipient: Jhon Ner A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 45.00\n\nProducts\n- Premium Yoga Mat | Qty: 1 | Unit: PHP 45.00 | Subtotal: PHP 45.00\n\n[PRODUCT_IMAGE]Yoga.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-29 21:41:17'),
(4, 5, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: KMY Black Show\nOrder Details\nOrder Date: 2026-03-29 16:29:24\nStatus: Pending\nPayment: CASH\nRecipient: Jhon Ner A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 499.00\n\nProducts\n- KMY Black Show | Qty: 1 | Unit: PHP 499.00 | Subtotal: PHP 499.00\n\n[PRODUCT_IMAGE]product_media/product_20_img_1_1774785433_970f6e.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-29 22:29:24'),
(5, 6, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: Headset Bluetooth X-One\nOrder Details\nOrder Date: 2026-03-29 16:54:13\nStatus: Pending\nPayment: CASH\nRecipient: Jhon Ner A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 39.77\n\nProducts\n- Headset Bluetooth X-One | Qty: 1 | Unit: PHP 39.77 | Subtotal: PHP 39.77\n\n[PRODUCT_IMAGE]headset.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-29 22:54:13'),
(6, 7, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: KMY Black Show\nOrder Details\nOrder Date: 2026-03-29 17:07:58\nStatus: Pending\nPayment: CASH\nRecipient: Jhon Ner A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 499.00\n\nProducts\n- KMY Black Show | Qty: 1 | Unit: PHP 499.00 | Subtotal: PHP 499.00\n\n[PRODUCT_IMAGE]product_media/product_20_img_1_1774785433_970f6e.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-29 23:07:58'),
(7, 8, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: KMY Black Show\nOrder Details\nOrder Date: 2026-03-29 17:10:59\nStatus: Pending\nPayment: CASH\nRecipient: Jhon Ner A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 499.00\n\nProducts\n- KMY Black Show | Qty: 1 | Unit: PHP 499.00 | Subtotal: PHP 499.00\n\n[PRODUCT_IMAGE]product_media/product_20_img_1_1774785433_970f6e.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-29 23:10:59'),
(8, 9, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: Samsung Galaxy A21s 6/128\nOrder Details\nOrder Date: 2026-03-29 17:11:18\nStatus: Pending\nPayment: CASH\nRecipient: Jhon Ner A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 279.00\n\nProducts\n- Samsung Galaxy A21s 6/128 | Qty: 1 | Unit: PHP 279.00 | Subtotal: PHP 279.00\n\n[PRODUCT_IMAGE]samsung.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-29 23:11:18'),
(9, 9, 60, 'admin', 'Great news! Your order #49 is now Processing. We are preparing your items for shipment.', 'status_notice', 1, '2026-03-29 23:13:12'),
(10, 10, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: KMY Black Show\nOrder Details\nOrder Date: 2026-03-29 17:14:09\nStatus: Pending\nPayment: CASH\nRecipient: Jhon Ner A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 499.00\n\nProducts\n- KMY Black Show | Qty: 1 | Unit: PHP 499.00 | Subtotal: PHP 499.00\n\n[PRODUCT_IMAGE]product_media/product_20_img_1_1774785433_970f6e.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-29 23:14:09'),
(11, 11, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: Headset Bluetooth X-One\nOrder Details\nOrder Date: 2026-03-29 17:14:30\nStatus: Pending\nPayment: CASH\nRecipient: Jhon Ner A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 39.77\n\nProducts\n- Headset Bluetooth X-One | Qty: 1 | Unit: PHP 39.77 | Subtotal: PHP 39.77\n\n[PRODUCT_IMAGE]headset.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-29 23:14:30'),
(12, 12, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: Headset Bluetooth X-One\nOrder Details\nOrder Date: 2026-03-29 17:18:16\nStatus: Pending\nPayment: CASH\nRecipient: Jhon Ner A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 39.77\n\nProducts\n- Headset Bluetooth X-One | Qty: 1 | Unit: PHP 39.77 | Subtotal: PHP 39.77\n\n[PRODUCT_IMAGE]headset.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-29 23:18:16'),
(13, 13, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: KMY Black Show\nOrder Details\nOrder Date: 2026-03-29 17:18:54\nStatus: Pending\nPayment: CASH\nRecipient: Jhon Ner A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 499.00\n\nProducts\n- KMY Black Show | Qty: 1 | Unit: PHP 499.00 | Subtotal: PHP 499.00\n\n[PRODUCT_IMAGE]product_media/product_20_img_1_1774785433_970f6e.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-29 23:18:54'),
(14, 8, 60, 'admin', 'Great news! Your order #48 is now Processing. We are preparing your items for shipment.', 'status_notice', 1, '2026-03-29 23:55:40'),
(15, 8, 60, 'admin', 'Update: Your order #48 has been Shipped and is now on the way.', 'status_notice', 1, '2026-03-29 23:56:06'),
(16, 8, 60, 'admin', 'Your order for KMY Black Show is marked Delivered. Please confirm once received.', 'status_notice', 1, '2026-03-30 00:01:14'),
(17, 8, 48, 'user', 'ganduhhh', 'chat', 1, '2026-03-30 00:36:48'),
(18, 14, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: Samsung Galaxy A21s 6/128\nOrder Details\nOrder Date: 2026-03-30 04:58:46\nStatus: Pending\nPayment: CASH\nRecipient: Jhon Ner A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 279.00\n\nProducts\n- Samsung Galaxy A21s 6/128 | Qty: 1 | Unit: PHP 279.00 | Subtotal: PHP 279.00\n\n[PRODUCT_IMAGE]samsung.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-30 10:58:46'),
(19, 15, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: Low Heels Black Shoe\nOrder Details\nOrder Date: 2026-03-30 05:13:45\nStatus: Pending\nPayment: CASH\nRecipient: Jhon Ner A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 599.00\n\nProducts\n- Low Heels Black Shoe | Qty: 1 | Unit: PHP 599.00 | Subtotal: PHP 599.00\n\n[PRODUCT_IMAGE]product_media/product_26_variant_img_1774840316_9e5ef0.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-30 11:13:45'),
(20, 4, 60, 'admin', 'Great news! Your order for Premium Yoga Mat is now Processing. We are preparing your items for shipment.', 'status_notice', 1, '2026-03-30 11:47:28'),
(21, 13, 60, 'admin', 'Great news! Your order for KMY Black Show is now Processing. We are preparing your items for shipment.', 'status_notice', 1, '2026-03-30 12:49:26'),
(22, 16, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: Headset Bluetooth X-One\nOrder Details\nOrder Date: 2026-03-30 06:59:00\nStatus: Pending\nPayment: CASH\nRecipient: Jhon Ner A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 39.77\n\nProducts\n- Headset Bluetooth X-One | Qty: 1 | Unit: PHP 39.77 | Subtotal: PHP 39.77\n\n[PRODUCT_IMAGE]headset.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-30 12:59:00'),
(23, 13, 60, 'admin', 'Update: Your order for KMY Black Show has been Shipped and is now on the way.', 'status_notice', 1, '2026-03-30 13:05:45'),
(24, 17, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: Samsung Galaxy A21s 6/128\nOrder Details\nOrder Date: 2026-03-30 08:19:50\nStatus: Pending\nPayment: CASH\nRecipient: Jhon Ner A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 279.00\n\nProducts\n- Samsung Galaxy A21s 6/128 | Qty: 1 | Unit: PHP 279.00 | Subtotal: PHP 279.00\n\n[PRODUCT_IMAGE]samsung.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-30 14:19:50'),
(25, 18, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: New High Heels Black shoe\nOrder Details\nOrder Date: 2026-03-30 08:29:04\nStatus: Pending\nPayment: CASH\nRecipient: Jhonrick A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 700.00\n\nProducts\n- New High Heels Black shoe | Qty: 1 | Unit: PHP 700.00 | Subtotal: PHP 700.00\n\n[PRODUCT_IMAGE]product_media/product_25_img_1_1774840316_f12699.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-30 14:29:04'),
(26, 19, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: KMY Black Show\nOrder Details\nOrder Date: 2026-03-30 08:36:27\nStatus: Pending\nPayment: CASH\nRecipient: Jhonrick A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 499.00\n\nProducts\n- KMY Black Show | Qty: 1 | Unit: PHP 499.00 | Subtotal: PHP 499.00\n\n[PRODUCT_IMAGE]product_media/product_20_img_1_1774785433_970f6e.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-30 14:36:27'),
(27, 20, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: Black Socks\nOrder Details\nOrder Date: 2026-03-30 08:40:07\nStatus: Pending\nPayment: CASH\nRecipient: Jhon Ner A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 50.00\n\nProducts\n- Black Socks | Qty: 1 | Unit: PHP 50.00 | Subtotal: PHP 50.00\n\n[PRODUCT_IMAGE]product_media/product_27_img_1_1774848505_e1712b.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-30 14:40:07'),
(28, 13, 48, 'user', 'Hi, I have a question about \"KMY Black Show\" (Order #53).', 'chat', 1, '2026-03-30 14:48:05'),
(29, 21, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: New Samsung Headphone\nOrder Details\nOrder Date: 2026-03-30 10:38:34\nStatus: Pending\nPayment: CASH\nRecipient: Jhonrick A. Tagulinao\nPhone: +639814585663\nAddress: paliparan, n/a, fasef, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 15,000.00\n\nProducts\n- New Samsung Headphone | Qty: 1 | Unit: PHP 15,000.00 | Subtotal: PHP 15,000.00\n\n[PRODUCT_IMAGE]product_media/product_29_img_1_1774856738_0c0566.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-30 16:38:34'),
(30, 21, 60, 'admin', 'Great news! Your order for New Samsung Headphone is now Processing. We are preparing your items for shipment.', 'status_notice', 1, '2026-03-30 18:13:33'),
(31, 21, 48, 'user', 'sis, dadating ba order ko bukas?', 'chat', 1, '2026-03-30 18:15:30'),
(32, 22, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: Shopping Bag | Tote BAg\nOrder Details\nOrder Date: 2026-03-30 13:05:50\nStatus: Pending\nPayment: CASH\nRecipient: Jayhro Cantor\nPhone: +639814585663\nAddress: paliparan, n/a, faserr, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 70.00\n\nProducts\n- Shopping Bag | Tote BAg | Qty: 1 | Unit: PHP 70.00 | Subtotal: PHP 70.00\n\n[PRODUCT_IMAGE]product_media/product_17_img_main_1774867909_d3600a.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-30 19:05:50'),
(33, 22, 48, 'user', 'Yo, sup', 'chat', 1, '2026-03-30 19:06:22'),
(34, 22, 60, 'admin', 'Great news! Your order for Shopping Bag | Tote BAg is now Processing. We are preparing your items for shipment.', 'status_notice', 1, '2026-03-30 19:07:09'),
(35, 22, 60, 'admin', 'Update: Your order for Shopping Bag | Tote BAg has been Shipped and is now on the way.', 'status_notice', 1, '2026-03-30 19:07:30'),
(36, 22, 60, 'admin', 'Your order for Shopping Bag | Tote BAg is marked Delivered. Please confirm once received.', 'status_notice', 1, '2026-03-30 19:07:32'),
(37, 23, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: Black Socks\nOrder Details\nOrder Date: 2026-03-30 13:33:04\nStatus: Pending\nPayment: CASH\nRecipient: Jayhro Cantor\nPhone: +639814585663\nAddress: paliparan, n/a, faserr, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 50.00\n\nProducts\n- Black Socks | Qty: 1 | Unit: PHP 50.00 | Subtotal: PHP 50.00\n\n[PRODUCT_IMAGE]product_media/product_27_img_1_1774848505_e1712b.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-30 19:33:04'),
(38, 24, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: Shopping Bag | Tote BAg\nOrder Details\nOrder Date: 2026-03-30 15:00:54\nStatus: Pending\nPayment: CASH\nRecipient: Jayhro Cantor\nPhone: +639814585663\nAddress: paliparan, n/a, faserr, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 70.00\n\nProducts\n- Shopping Bag | Tote BAg | Qty: 1 | Unit: PHP 70.00 | Subtotal: PHP 70.00\n\n[PRODUCT_IMAGE]product_media/product_17_img_main_1774867909_d3600a.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-30 21:00:54'),
(39, 25, 0, 'system', 'Hello! Thank you for your order.\nYour order has been placed successfully.\n\nProducts: Nike Air Max 270\nOrder Details\nOrder Date: 2026-03-30 15:42:49\nStatus: Pending\nPayment: CASH\nRecipient: Jayhro Cantor\nPhone: +639814585663\nAddress: paliparan, n/a, faserr, dsfvsdf, dvvfsd\nTotal Items: 1\nOrder Total: PHP 149.00\n\nProducts\n- Nike Air Max 270 | Qty: 1 | Unit: PHP 149.00 | Subtotal: PHP 149.00\n\n[PRODUCT_IMAGE]product_media/product_19_img_main_1774854207_21f3a3.jpg[/PRODUCT_IMAGE]\nWe\'ll send updates here as your order status changes.', 'order_notice', 1, '2026-03-30 21:42:49'),
(40, 25, 60, 'admin', 'Great news! Your order for Nike Air Max 270 is now Processing. We are preparing your items for shipment.', 'status_notice', 1, '2026-03-30 22:36:43'),
(41, 25, 60, 'admin', 'Update: Your order for Nike Air Max 270 has been Shipped and is now on the way.', 'status_notice', 1, '2026-03-30 22:36:47'),
(42, 25, 60, 'admin', 'Your order for Nike Air Max 270 is marked Delivered. Please confirm once received.', 'status_notice', 1, '2026-03-30 22:36:50');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','processing','shipped','delivered','received','cancelled','reviewed') DEFAULT 'pending',
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `binned` tinyint(1) NOT NULL DEFAULT 0,
  `payment_method` enum('cash','gcash') DEFAULT 'cash',
  `total_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `recipient_id`, `order_date`, `status`, `archived`, `binned`, `payment_method`, `total_amount`) VALUES
(39, 48, NULL, '2026-03-29 18:36:05', 'reviewed', 0, 0, 'cash', 279.00),
(40, 48, NULL, '2026-03-29 19:59:25', 'reviewed', 1, 0, 'cash', 499.00),
(43, 48, NULL, '2026-03-29 21:29:51', 'pending', 0, 0, 'cash', 499.00),
(44, 48, NULL, '2026-03-29 21:41:17', 'processing', 0, 0, 'cash', 45.00),
(45, 48, NULL, '2026-03-29 22:29:24', 'pending', 0, 0, 'cash', 499.00),
(46, 48, NULL, '2026-03-29 22:54:13', 'pending', 0, 0, 'cash', 39.77),
(47, 48, NULL, '2026-03-29 23:07:58', 'pending', 0, 0, 'cash', 499.00),
(48, 48, NULL, '2026-03-29 23:10:59', 'reviewed', 0, 0, 'cash', 499.00),
(49, 48, NULL, '2026-03-29 23:11:18', 'processing', 0, 0, 'cash', 279.00),
(50, 48, NULL, '2026-03-29 23:14:09', 'pending', 0, 0, 'cash', 499.00),
(51, 48, NULL, '2026-03-29 23:14:30', 'pending', 0, 0, 'cash', 39.77),
(52, 48, NULL, '2026-03-29 23:18:16', 'pending', 0, 0, 'cash', 39.77),
(53, 48, NULL, '2026-03-29 23:18:54', 'shipped', 0, 0, 'cash', 499.00),
(54, 48, NULL, '2026-03-30 10:58:46', 'pending', 0, 0, 'cash', 279.00),
(55, 48, NULL, '2026-03-30 11:13:45', 'pending', 0, 0, 'cash', 599.00),
(56, 48, NULL, '2026-03-30 12:59:00', 'pending', 0, 0, 'cash', 39.77),
(57, 48, NULL, '2026-03-30 14:19:50', 'pending', 0, 0, 'cash', 279.00),
(58, 48, NULL, '2026-03-30 14:29:04', 'pending', 0, 0, 'cash', 700.00),
(59, 48, NULL, '2026-03-30 14:36:27', 'pending', 0, 0, 'cash', 499.00),
(60, 48, NULL, '2026-03-30 14:40:07', 'pending', 0, 0, 'cash', 50.00),
(61, 48, NULL, '2026-03-30 16:38:34', 'processing', 0, 0, 'cash', 15000.00),
(62, 48, 12, '2026-03-30 19:05:50', 'reviewed', 0, 0, 'cash', 70.00),
(63, 48, 12, '2026-03-30 19:33:04', 'pending', 0, 0, 'cash', 50.00),
(64, 48, 12, '2026-03-30 21:00:54', 'pending', 0, 0, 'cash', 70.00),
(65, 48, 12, '2026-03-30 21:42:49', 'reviewed', 0, 0, 'cash', 149.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(39, 39, 15, 1, 279.00),
(40, 40, 20, 1, 499.00),
(43, 43, 20, 1, 499.00),
(44, 44, 18, 1, 45.00),
(45, 45, 20, 1, 499.00),
(46, 46, 16, 1, 39.77),
(47, 47, 20, 1, 499.00),
(48, 48, 20, 1, 499.00),
(49, 49, 15, 1, 279.00),
(50, 50, 20, 1, 499.00),
(51, 51, 16, 1, 39.77),
(52, 52, 16, 1, 39.77),
(53, 53, 20, 1, 499.00),
(54, 54, 15, 1, 279.00),
(55, 55, 26, 1, 599.00),
(56, 56, 16, 1, 39.77),
(57, 57, 15, 1, 279.00),
(58, 58, 25, 1, 700.00),
(59, 59, 20, 1, 499.00),
(60, 60, 27, 1, 50.00),
(61, 61, 29, 1, 15000.00),
(62, 62, 17, 1, 70.00),
(63, 63, 27, 1, 50.00),
(64, 64, 17, 1, 70.00),
(65, 65, 19, 1, 149.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `parent_product_id` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `product_name` varchar(150) DEFAULT NULL,
  `product_description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `product_stock` int(11) DEFAULT NULL,
  `order_count` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `average_rating` decimal(3,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `parent_product_id`, `archived`, `product_name`, `product_description`, `price`, `product_stock`, `order_count`, `category_id`, `average_rating`) VALUES
(15, NULL, 1, 'Samsung Galaxy A21s 6/128', '6.5\" HD+ screen, 48MP quad camera, 5000mAh battery.', 279.00, 0, 3, 1, 3.00),
(16, NULL, 0, 'Headset Bluetooth X-One', 'Wireless Bluetooth headset, noise cancelling, 8h playback.', 39.77, 67, 4, 1, 2.00),
(17, NULL, 0, 'Shopping Bag | Tote BAg', 'Electric tabletop Korean grill, non-stick, 1800W.', 70.00, 17, 2, 3, 5.00),
(18, NULL, 1, 'Premium Yoga Mat', 'Anti-slip, 6mm thick fitness mat for yoga and home workouts.', 45.00, 112, 1, 3, 3.00),
(19, NULL, 0, 'Nike Air Max 270', 'Running shoes with Air Max cushioning and stylish design.', 149.00, 51, 1, 5, 4.00),
(20, NULL, 0, 'KMY Black Show', 'Crafted from premium leather, the sleek black finish pairs effortlessly with casual outfits or smart-casual attire.', 499.00, 7, 8, 4, 3.50),
(21, NULL, 0, 'Doc Martin Black Shoe', 'Made from high quality materials', 800.00, 2, 0, 4, 0.00),
(22, NULL, 1, 'KMY Black Shoe New Design - KMY High Heels', 'made from carbon', 800.00, 5, 0, 4, 0.00),
(23, NULL, 1, 'New Design KYC Black Shoe', 'Good from international', 0.00, 3, 0, 4, 0.00),
(24, NULL, 1, 'New Design KYC Black Shoe - KYC Black Shoe', 'Good from international', 800.00, 7, 0, 4, 0.00),
(25, NULL, 0, 'New High Heels Black shoe', 'for all day wears', 700.00, 1, 1, 5, 0.00),
(26, 25, 0, 'Low Heels Black Shoe', 'for all day wears', 599.00, 4, 1, 5, 0.00),
(27, NULL, 0, 'Black Socks', 'High Quality Socks\r\n3 pairs', 50.00, 1, 2, 5, 0.00),
(28, 27, 0, 'White Socks', 'High Quality Socks\r\n3 pairs', 49.00, 5, 0, 5, 0.00),
(29, NULL, 0, 'New Samsung Headphone', 'Samsung Galaxy upgraded products', 15000.00, 1, 1, 6, 0.00),
(30, 29, 0, 'Samsung Tablet(250gb ram)', 'Samsung Galaxy upgraded products', 20000.00, 3, 0, 6, 0.00),
(31, NULL, 1, 'Samsung Tablet(500gb ram)', 'Samsung high end electronics', 25000.00, 2, 0, 6, 0.00),
(32, 31, 1, 'Samsung Headphone', 'Samsung high end electronics', 1500.00, 3, 0, 6, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `product_drafts`
--

CREATE TABLE `product_drafts` (
  `draft_id` int(11) NOT NULL,
  `admin_user_id` int(11) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `product_description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `product_stock` int(11) DEFAULT NULL,
  `use_new_category` tinyint(1) NOT NULL DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `new_category_name` varchar(120) DEFAULT NULL,
  `pinned_image_index` int(11) NOT NULL DEFAULT 0,
  `image_count` int(11) NOT NULL DEFAULT 0,
  `has_video` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_drafts`
--

INSERT INTO `product_drafts` (`draft_id`, `admin_user_id`, `product_name`, `product_description`, `price`, `product_stock`, `use_new_category`, `category_id`, `new_category_name`, `pinned_image_index`, `image_count`, `has_video`, `created_at`, `updated_at`) VALUES
(3, 60, 'Samsung Headphone pro max', 'Pro max products', 5000.00, 2, 0, 6, '', 0, 1, 0, '2026-03-30 08:12:19', '2026-03-30 08:12:19');

-- --------------------------------------------------------

--
-- Table structure for table `product_draft_media`
--

CREATE TABLE `product_draft_media` (
  `media_id` int(11) NOT NULL,
  `draft_id` int(11) NOT NULL,
  `media_role` enum('main_image','video','variant_image') NOT NULL,
  `client_variant_id` int(11) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_draft_media`
--

INSERT INTO `product_draft_media` (`media_id`, `draft_id`, `media_role`, `client_variant_id`, `file_path`, `sort_order`, `is_pinned`, `created_at`) VALUES
(5, 3, 'main_image', NULL, 'product_media/drafts/draft_3_img_1_1774858340_1bd56fc6.jpg', 0, 1, '2026-03-30 08:12:20'),
(6, 3, 'variant_image', 1, 'product_media/drafts/draft_3_variant_1_1774858340_5c47d579.jpg', 0, 1, '2026-03-30 08:12:20');

-- --------------------------------------------------------

--
-- Table structure for table `product_draft_variants`
--

CREATE TABLE `product_draft_variants` (
  `variant_id` int(11) NOT NULL,
  `draft_id` int(11) NOT NULL,
  `client_variant_id` int(11) NOT NULL,
  `variant_order` int(11) NOT NULL DEFAULT 0,
  `variant_name` varchar(255) DEFAULT NULL,
  `variant_price` decimal(10,2) DEFAULT NULL,
  `variant_stock` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_draft_variants`
--

INSERT INTO `product_draft_variants` (`variant_id`, `draft_id`, `client_variant_id`, `variant_order`, `variant_name`, `variant_price`, `variant_stock`, `created_at`) VALUES
(6, 3, 1, 0, 'Samsung tablet Pro max', 30000.00, 2, '2026-03-30 08:16:40');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `image_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`image_id`, `product_id`, `image_url`, `is_pinned`) VALUES
(23, 15, 'samsung.jpg', 0),
(24, 15, 'https://via.placeholder.com/900x600?text=Galaxy+A21s+2', 0),
(25, 15, 'https://via.placeholder.com/900x600?text=Galaxy+A21s+3', 0),
(26, 16, 'headset.jpg', 1),
(27, 16, 'https://via.placeholder.com/900x600?text=Headset+X-One+2', 0),
(28, 16, 'https://via.placeholder.com/900x600?text=Headset+X-One+3', 0),
(30, 17, 'https://via.placeholder.com/900x600?text=Maslon+Korean+Hot+Grill+2', 0),
(31, 17, 'https://via.placeholder.com/900x600?text=Maslon+Korean+Hot+Grill+3', 0),
(32, 18, 'Yoga.jpg', 1),
(33, 18, 'https://via.placeholder.com/900x600?text=Premium+Yoga+Mat+2', 0),
(34, 18, 'https://via.placeholder.com/900x600?text=Premium+Yoga+Mat+3', 0),
(35, 19, 'https://via.placeholder.com/900x600?text=Nike+Air+Max+270+1', 0),
(36, 19, 'https://via.placeholder.com/900x600?text=Nike+Air+Max+270+2', 0),
(37, 19, 'nike.jpg', 0),
(38, 19, 'uploads/heroBg.jpg', 0),
(39, 20, 'product_media/product_20_img_1_1774785433_970f6e.jpg', 1),
(40, 20, 'product_media/product_20_img_2_1774785434_4e37cd.jpg', 0),
(41, 21, 'product_media/product_21_variant_img_1774837151_f1d97f.jpg', 1),
(42, 22, 'product_media/product_22_variant_img_1774838060_facae7.jpg', 1),
(43, 23, 'product_media/product_23_img_1_1774838390_5bd135.jpg', 1),
(44, 24, 'product_media/product_24_variant_img_1774838390_ae9445.jpg', 1),
(45, 25, 'product_media/product_25_img_1_1774840316_f12699.jpg', 1),
(46, 26, 'product_media/product_26_variant_img_1774840316_9e5ef0.jpg', 1),
(48, 27, 'product_media/product_27_img_1_1774848505_e1712b.jpg', 1),
(49, 28, 'product_media/product_28_variant_img_1774848505_6f12c5.jpg', 1),
(52, 19, 'product_media/product_19_img_main_1774854207_21f3a3.jpg', 1),
(53, 15, 'product_media/product_15_img_main_1774854282_e0631a.jpg', 1),
(54, 16, 'product_media/product_16_img_main_1774854358_bd48a4.jpg', 0),
(55, 29, 'product_media/product_29_img_1_1774856738_0c0566.jpg', 1),
(56, 30, 'product_media/product_30_variant_img_1774856738_a1994d.jpg', 1),
(57, 31, 'product_media/product_31_img_1_1774858185_73e162.jpg', 1),
(58, 32, 'product_media/product_32_variant_img_1774858185_524c70.jpg', 1),
(59, 29, 'product_media/product_29_img_main_1774864862_0b2d15.jpg', 0),
(60, 17, 'product_media/product_17_img_main_1774867909_d3600a.jpg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `recipients`
--

CREATE TABLE `recipients` (
  `recipient_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `recipient_name` varchar(100) DEFAULT NULL,
  `phone_no` varchar(20) DEFAULT NULL,
  `street_name` varchar(100) DEFAULT NULL,
  `unit_floor` varchar(50) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipients`
--

INSERT INTO `recipients` (`recipient_id`, `user_id`, `recipient_name`, `phone_no`, `street_name`, `unit_floor`, `district`, `city`, `region`, `is_default`) VALUES
(9, 61, 'Jhon Ner A. Tagulinao', '+639814585663', 'paliparan', 'n/a', 'fasef', 'dsfvsdf', 'dvvfsd', 1),
(10, 48, 'Jhon Nerick A. Tagulinao', '+639814585663', 'paliparan', 'n/a', 'fasef', 'dsfvsdf', 'dvvfsd', 0),
(11, 48, 'Jhon Nerick A. Tagulinao', '+639814585663', 'paliparan', 'n/a', 'faserr', 'dsfvsdf', 'dvvfsd', 0),
(12, 48, 'Jayhro Cantor', '+639814585663', 'paliparan', 'n/a', 'faserr', 'dsfvsdf', 'dvvfsd', 1);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `review_image` longblob DEFAULT NULL,
  `review_image_type` varchar(50) DEFAULT NULL,
  `is_anonymous` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `user_id`, `product_id`, `rating`, `review_text`, `created_at`, `review_image`, `review_image_type`, `is_anonymous`) VALUES
(13, 48, 20, 3, 'fsgfsdghfsdhdf', '2026-03-29 12:55:54', NULL, NULL, 0),
(14, 48, 20, 4, 'The shoe is made of\r\n high quality materials', '2026-03-30 05:34:04', 0x757365725f7265766965775f6d656469612f696d616765732f7265766965775f31345f36396361306234636563666339352e34343337303236312e6a7067, 'image/jpeg', 1),
(15, 48, 17, 5, 'This is so useful for day to day shopping and gala', '2026-03-30 11:10:01', 0x757365725f7265766965775f6d656469612f696d616765732f7265766965775f31355f36396361356130393534336639382e34333231303131342e6a7067, 'image/jpeg', 0),
(16, 48, 19, 4, 'best product I have bought', '2026-03-30 14:59:08', 0x757365725f7265766965775f6d656469612f696d616765732f7265766965775f31365f36396361386662636430343862372e32343833343838302e6a7067, 'image/jpeg', 0);

-- --------------------------------------------------------

--
-- Table structure for table `review_media_files`
--

CREATE TABLE `review_media_files` (
  `media_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `media_type` varchar(64) NOT NULL,
  `file_size` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_media_files`
--

INSERT INTO `review_media_files` (`media_id`, `review_id`, `file_path`, `media_type`, `file_size`, `created_at`) VALUES
(6, 14, 'user_review_media/images/review_14_69ca0b4cecfc95.44370261.jpg', 'image/jpeg', 199918, '2026-03-30 05:34:04'),
(7, 15, 'user_review_media/images/review_15_69ca5a09543f98.43210114.jpg', 'image/jpeg', 89039, '2026-03-30 11:10:01'),
(8, 16, 'user_review_media/images/review_16_69ca8fbcd048b7.24834880.jpg', 'image/jpeg', 25692, '2026-03-30 14:59:08');

-- --------------------------------------------------------

--
-- Table structure for table `search_history`
--

CREATE TABLE `search_history` (
  `search_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `search_term` varchar(80) NOT NULL,
  `searched_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `search_history`
--

INSERT INTO `search_history` (`search_id`, `user_id`, `search_term`, `searched_at`) VALUES
(1, 61, 'yoga', '2026-03-29 06:50:54'),
(2, 61, 'm', '2026-03-29 06:53:34'),
(3, 48, 's', '2026-03-29 06:54:12'),
(4, 48, 'j', '2026-03-29 06:54:31'),
(5, 48, 'Nike Air Max 270', '2026-03-30 14:12:50'),
(8, 48, 'Headset Bluetooth X-One', '2026-03-30 14:17:19'),
(10, 48, 'Premium Yoga Mat', '2026-03-30 14:13:34'),
(16, 48, 'Doc Martin Black Shoe', '2026-03-30 14:13:03'),
(18, 48, 'New Samsung Headphone', '2026-03-30 12:18:03'),
(21, 48, 'Shopping Bag | Tote BAg', '2026-03-30 14:12:45');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(48, 'nerick', 'jntagulinao@kld.edu.ph', '$2y$10$oT7P8z/imckOLpZk.8hAK.YbX7o/OWfajZ4bnCRhehEhfPqCivo/a', 'user', 'active', '2026-02-21 15:52:56', '2026-03-26 19:13:21'),
(60, 'sky', 'rikiskyler372@gmail.com', '$2y$10$aClmHAPdoNK.c1NQp.5L1Ok4eI235BlkHnDWOD6dwHIOrikmQrrOe', 'admin', 'active', '2026-02-22 04:23:32', '2026-03-29 08:55:29'),
(61, 'John Wick', 'nerickaducal02@gmail.com', '$2y$10$9jYdaOpOW0CCssIHHQnSZewLbXU8zjjoddLPSC.kCb54LYg2dSPcC', 'user', 'active', '2026-03-05 13:34:20', '2026-03-29 06:42:53'),
(62, 'Andrea', 'andreamysteryshop@gmail.com', '$2y$10$y7vJcgqgXf6.3239z5IsYOsCQFkmwSQhY8OL6UbCriErdaKpko7Sq', 'admin', 'active', '2026-03-30 05:07:46', '2026-03-30 05:08:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD UNIQUE KEY `cart_id` (`cart_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`conversation_id`),
  ADD UNIQUE KEY `uq_user_order` (`user_id`,`order_id`),
  ADD KEY `idx_conv_user` (`user_id`),
  ADD KEY `idx_conv_admin` (`admin_id`),
  ADD KEY `idx_conv_order` (`order_id`);

--
-- Indexes for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD PRIMARY KEY (`conversation_id`,`participant_id`,`participant_role`),
  ADD KEY `idx_participant_lookup` (`participant_id`,`participant_role`);

--
-- Indexes for table `customer_profiles`
--
ALTER TABLE `customer_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_msg_conv_time` (`conversation_id`,`created_at`),
  ADD KEY `idx_msg_unread` (`conversation_id`,`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `recipient_id` (`recipient_id`),
  ADD KEY `idx_orders_archived_status` (`status`),
  ADD KEY `idx_orders_archive_bin_status` (`archived`,`binned`,`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD UNIQUE KEY `order_id` (`order_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `parent_product_id` (`parent_product_id`),
  ADD KEY `idx_products_archived` (`archived`);

--
-- Indexes for table `product_drafts`
--
ALTER TABLE `product_drafts`
  ADD PRIMARY KEY (`draft_id`),
  ADD KEY `idx_product_drafts_admin_updated` (`admin_user_id`,`updated_at`);

--
-- Indexes for table `product_draft_media`
--
ALTER TABLE `product_draft_media`
  ADD PRIMARY KEY (`media_id`),
  ADD KEY `idx_product_draft_media_draft` (`draft_id`),
  ADD KEY `idx_product_draft_media_role` (`draft_id`,`media_role`),
  ADD KEY `idx_product_draft_media_variant` (`draft_id`,`client_variant_id`);

--
-- Indexes for table `product_draft_variants`
--
ALTER TABLE `product_draft_variants`
  ADD PRIMARY KEY (`variant_id`),
  ADD KEY `idx_product_draft_variants_draft` (`draft_id`),
  ADD KEY `idx_product_draft_variants_client` (`draft_id`,`client_variant_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_product_images_pinned` (`product_id`,`is_pinned`,`image_id`);

--
-- Indexes for table `recipients`
--
ALTER TABLE `recipients`
  ADD PRIMARY KEY (`recipient_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `idx_product_reviews` (`product_id`),
  ADD KEY `idx_user_reviews` (`user_id`);

--
-- Indexes for table `review_media_files`
--
ALTER TABLE `review_media_files`
  ADD PRIMARY KEY (`media_id`),
  ADD KEY `idx_review_media_review_id` (`review_id`);

--
-- Indexes for table `search_history`
--
ALTER TABLE `search_history`
  ADD PRIMARY KEY (`search_id`),
  ADD UNIQUE KEY `uq_user_search_term` (`user_id`,`search_term`),
  ADD KEY `idx_search_history_user_time` (`user_id`,`searched_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `conversation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `product_drafts`
--
ALTER TABLE `product_drafts`
  MODIFY `draft_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_draft_media`
--
ALTER TABLE `product_draft_media`
  MODIFY `media_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_draft_variants`
--
ALTER TABLE `product_draft_variants`
  MODIFY `variant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `recipients`
--
ALTER TABLE `recipients`
  MODIFY `recipient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `review_media_files`
--
ALTER TABLE `review_media_files`
  MODIFY `media_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `search_history`
--
ALTER TABLE `search_history`
  MODIFY `search_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`cart_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD CONSTRAINT `fk_participant_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_profiles`
--
ALTER TABLE `customer_profiles`
  ADD CONSTRAINT `fk_customer_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_msg_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`conversation_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `recipients` (`recipient_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`parent_product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_draft_media`
--
ALTER TABLE `product_draft_media`
  ADD CONSTRAINT `fk_product_draft_media_draft` FOREIGN KEY (`draft_id`) REFERENCES `product_drafts` (`draft_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_draft_variants`
--
ALTER TABLE `product_draft_variants`
  ADD CONSTRAINT `fk_product_draft_variants_draft` FOREIGN KEY (`draft_id`) REFERENCES `product_drafts` (`draft_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `recipients`
--
ALTER TABLE `recipients`
  ADD CONSTRAINT `recipients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `review_media_files`
--
ALTER TABLE `review_media_files`
  ADD CONSTRAINT `fk_review_media_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`review_id`) ON DELETE CASCADE;

--
-- Constraints for table `search_history`
--
ALTER TABLE `search_history`
  ADD CONSTRAINT `fk_search_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
