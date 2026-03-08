-- Update script generated from local_db.sql vs online_db.sql
-- Purpose: add missing shop-related tables and seed rows without modifying existing online records.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

START TRANSACTION;

-- ---------------------------------------------------------------------
-- 1) Create missing tables (safe with IF NOT EXISTS)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(80) NOT NULL,
  `email` varchar(160) DEFAULT NULL,
  `full_name` varchar(160) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(30) NOT NULL DEFAULT 'admin',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(180) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `short_description` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `discount_price` decimal(12,2) DEFAULT NULL,
  `stock_qty` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `main_image` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_status` (`status`),
  KEY `idx_products_featured` (`featured`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_images_product` (`product_id`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gadget_requests` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` varchar(140) NOT NULL,
  `phone` varchar(40) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `gadget_name` varchar(190) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','done') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gadget_requests_status` (`status`),
  KEY `idx_gadget_requests_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2) Seed only missing rows (no updates/deletes to existing online data)
-- ---------------------------------------------------------------------

INSERT INTO `admins` (`id`, `username`, `email`, `full_name`, `password_hash`, `role`, `status`, `last_login`, `created_at`, `updated_at`)
SELECT 1, 'admin', 'admin@example.com', 'Shop Administrator', '$2y$12$C7rCxAHXeciEkys7bzY/JeaGwsT1nslqD9PfYXZ.jlSO/Gq.Gzn1i', 'admin', 1, NULL, '2026-03-05 18:47:05', NULL
WHERE NOT EXISTS (SELECT 1 FROM `admins` WHERE `id` = 1);

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `cover_image`, `status`, `created_at`, `updated_at`)
SELECT 1, 'Phones', 'phones', 'Smartphones and accessories', 'category_69a9b64f91dbe2.35182704.jpg', 1, '2026-03-05 18:47:05', '2026-03-05 19:58:55'
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `id` = 1);

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `cover_image`, `status`, `created_at`, `updated_at`)
SELECT 2, 'Laptops', 'laptops', 'Business and personal laptops', 'category_69a9e94b601f01.10670420.jpg', 1, '2026-03-05 18:47:05', '2026-03-05 23:36:27'
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `id` = 2);

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `cover_image`, `status`, `created_at`, `updated_at`)
SELECT 3, 'Networking', 'networking', 'Routers and office networking devices', NULL, 1, '2026-03-05 18:47:05', NULL
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `id` = 3);

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `short_description`, `description`, `price`, `discount_price`, `stock_qty`, `status`, `featured`, `main_image`, `created_at`, `updated_at`)
SELECT 1, 1, 'Galaxy A15', 'galaxy-a15', '128GB storage smartphone', 'Reliable battery, clear camera, and dual SIM support.', 750000.00, 699000.00, 20, 1, 1, 'product_69a9b1da69a582.01541421.png', '2026-03-05 18:47:05', '2026-03-05 19:39:54'
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `id` = 1);

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `short_description`, `description`, `price`, `discount_price`, `stock_qty`, `status`, `featured`, `main_image`, `created_at`, `updated_at`)
SELECT 2, 2, 'Lenovo ThinkPad E14', 'lenovo-thinkpad-e14', '14-inch business laptop', 'Intel Core i5, 16GB RAM, 512GB SSD.', 3250000.00, NULL, 8, 1, 1, 'placeholder.svg', '2026-03-05 18:47:05', NULL
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `id` = 2);

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `short_description`, `description`, `price`, `discount_price`, `stock_qty`, `status`, `featured`, `main_image`, `created_at`, `updated_at`)
SELECT 3, 3, 'TP-Link Archer C6', 'tp-link-archer-c6', 'Dual-band Wi-Fi router', 'Strong home and office coverage with 4 external antennas.', 280000.00, 250000.00, 35, 1, 1, 'placeholder.svg', '2026-03-05 18:47:05', '2026-03-06 00:29:43'
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `id` = 3);

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `sort_order`, `created_at`)
SELECT 1, 1, 'product_69a9b1b54ec448.98579788.png', 0, '2026-03-05 19:39:17'
WHERE NOT EXISTS (SELECT 1 FROM `product_images` WHERE `id` = 1);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
