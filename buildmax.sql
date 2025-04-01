-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 09, 2024 at 12:47 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `buildmax`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `demo_orders`
--

CREATE TABLE `demo_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `date` varchar(50) NOT NULL,
  `order` int(10) NOT NULL,
  `pi` int(10) NOT NULL,
  `customer` varchar(50) NOT NULL,
  `supplier` varchar(50) NOT NULL,
  `item` varchar(50) NOT NULL,
  `status` smallint(6) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `demo_orders`
--

INSERT INTO `demo_orders` (`id`, `date`, `order`, `pi`, `customer`, `supplier`, `item`, `status`, `created_at`, `updated_at`) VALUES
(1, '1.12.2024', 1, 2, 'customer 1', 'supplier 1', 'lorrem ipsum', 1, NULL, NULL),
(2, '2.2.2024', 2, 2, 'customer 2', 'supplier 2', 'lorrem ipsum', 1, NULL, NULL),
(3, '3.3.2024', 3, 3, 'customer 3', 'supplier 3', 'lorrem ipsum', 1, NULL, NULL),
(4, '4.30.2024', 4, 4, 'customer 4', 'supplier 4', 'lorrem ipsum', 0, NULL, NULL),
(5, '2.10.2024', 5, 5, 'customer 5', 'supplier 5', 'lorrem ipsum', 0, NULL, NULL),
(6, '2.13.2024', 6, 6, 'customer 6', 'supplier 6', 'lorrem ipsum', 1, NULL, NULL),
(7, '2.21.2024', 4, 4, 'customer 4', 'supplier 4', 'lorrem ipsum', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2024_03_25_114708_create_personal_access_tokens_table', 2),
(5, '2024_03_27_072810_create_personal_access_tokens_table', 3),
(6, '2024_04_04_100633_create_countries_table', 3),
(7, '2024_04_04_100921_create_countries_table', 4),
(9, '2024_04_05_044103_create_countries_table', 5),
(11, '2024_04_05_093731_create_sales_terms_table', 6),
(12, '2024_04_06_041504_create_order_statuses_table', 7),
(13, '2024_04_06_050050_create_customer_industries_table', 8),
(14, '2024_04_06_050700_create_supplier_types_table', 9),
(15, '2024_04_06_051228_create_shipping_lines_table', 10),
(16, '2024_04_06_051840_create_forwarders_table', 11),
(17, '2024_04_06_052540_create_ports_table', 12),
(18, '2024_04_06_053043_create_transports_table', 13),
(19, '2024_04_06_054131_create_representatives_table', 14),
(20, '2024_04_06_054909_create_departments_table', 15),
(22, '2024_04_06_055701_create_spend_bies_table', 16),
(23, '2024_04_06_055747_create_spenders_table', 16),
(24, '2024_04_06_060248_create_founders_table', 17),
(25, '2024_04_06_061007_create_units_table', 18),
(26, '2024_04_06_061735_create_sales_terms_table', 19),
(28, '2024_04_06_062730_create_sale_terms_table', 20),
(29, '2024_04_06_063228_create_sales_table', 20),
(30, '2024_04_06_063303_create_terms_table', 20),
(31, '2024_04_06_064255_create_supplier_types_table', 21),
(32, '2024_04_06_065022_create_market_sectors_table', 22),
(33, '2024_04_06_072602_create_sub_sectors_table', 23),
(34, '2024_04_06_100407_create_currencies_table', 24),
(35, '2024_04_15_044350_create_suppliers_table', 25),
(36, '2024_04_15_053834_create_primary_contacts_table', 26),
(37, '2024_04_15_055538_create_primary_contact_emails_table', 27),
(38, '2024_04_15_061012_create_primary_contact_phones_table', 28),
(39, '2024_04_15_061024_create_primary_contact_websites_table', 29),
(40, '2024_04_16_061613_create_product_service_categories_table', 30),
(41, '2024_04_16_061632_create_product_service_sub_categories_table', 31),
(42, '2024_04_19_062312_create_customers_table', 32),
(43, '2024_04_19_062349_create_customer_primary_contacts_table', 33),
(44, '2024_04_19_062411_create_customer_contact_emails_table', 34),
(45, '2024_04_19_062439_create_customer_contact_phones_table', 35),
(46, '2024_04_19_062454_create_customer_contact_websites_table', 36),
(47, '2024_04_30_123206_create_demo_orders_table', 37);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(6, 'App\\Models\\User', 1, 'api-token', '4d9266688c09fc3cfa30f026c4220f243606f68b1e61fdeca4e81d5835833461', '[\"*\"]', '2024-04-16 03:05:31', NULL, '2024-04-07 23:05:10', '2024-04-16 03:05:31'),
(7, 'App\\Models\\User', 1, 'api-token', '23f274971a909a88325ffedd9d34cd862e198886f664ecf2e14130b1b7eb4618', '[\"*\"]', '2024-04-25 22:37:14', NULL, '2024-04-16 22:18:59', '2024-04-25 22:37:14'),
(8, 'App\\Models\\User', 1, 'api-token', 'b2fc7626c6fd8085bbb4106b9677375cb0842ddac3a80aacb70caaea7b3fa809', '[\"*\"]', '2024-04-22 23:00:13', NULL, '2024-04-17 05:47:59', '2024-04-22 23:00:13'),
(9, 'App\\Models\\User', 1, 'api-token', '091685050733555120a010fbaf2b7116be15b6b74638318eeea9ca167ee8c8c1', '[\"*\"]', '2024-04-26 02:17:08', NULL, '2024-04-26 00:36:03', '2024-04-26 02:17:08'),
(10, 'App\\Models\\User', 1, 'api-token', 'fe4ae6a589f08e53b00c3464dc0e24d3717e01cf16f6e836f914ed2c5022f299', '[\"*\"]', '2024-05-08 02:06:53', NULL, '2024-04-30 04:33:17', '2024-05-08 02:06:53'),
(11, 'App\\Models\\User', 1, 'api-token', 'd131fb2413ef65b44a9e3f992cb6f60434b178f6992b4546eb22e293798584af', '[\"*\"]', '2024-05-08 23:37:45', NULL, '2024-05-08 04:31:20', '2024-05-08 23:37:45'),
(12, 'App\\Models\\User', 1, 'api-token', '602a383ccc62375c43b56a3926574e0b017cb7e62ffefd20655e519536f45f36', '[\"*\"]', '2024-05-09 03:24:17', NULL, '2024-05-09 03:06:43', '2024-05-09 03:24:17'),
(14, 'App\\Models\\User', 1, 'api-token', '27ba101e2ab7e34dfaccf3a51a6e0833e92b92292f6597eeab602c3c59beefc0', '[\"*\"]', NULL, NULL, '2024-05-09 04:25:31', '2024-05-09 04:25:31');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('S8OhUu2ZoyPYPLxqGxvvDaC33KpHUjApbnEisBL7', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNW0zeXJrUDJGUTVMZ2lVVzg2ZkdQTnYwanFHM01HTkZCY05MWFRtRyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1715250501);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Golam Faroque', 'faroque@trenzasoft.com', NULL, '$2y$12$0TgykPAWJcUM0KQafBP7SO/lywk5S3YaDVN/BkcZmlhuSpdZZma8q', NULL, '2024-03-25 08:30:34', '2024-03-25 08:30:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `demo_orders`
--
ALTER TABLE `demo_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `demo_orders`
--
ALTER TABLE `demo_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
