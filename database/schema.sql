-- Bloom & Vine Flower Store Database Schema
-- MySQL 5.7+ / MariaDB 10.2+
-- Character Set: utf8mb4 for Kurdish/Arabic script support

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Create Database (Uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS `bloom_vine` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `bloom_vine`;

-- Table: users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name_en` VARCHAR(255) NOT NULL,
  `name_ku` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `name_en` (`name_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: products
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT(11) UNSIGNED NOT NULL,
  `name_en` VARCHAR(255) NOT NULL,
  `name_ku` VARCHAR(255) NOT NULL,
  `description_en` TEXT NOT NULL,
  `description_ku` TEXT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `stock_qty` INT(11) NOT NULL DEFAULT 0,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `is_featured` BOOLEAN NOT NULL DEFAULT FALSE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `is_featured` (`is_featured`),
  KEY `price` (`price`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `grand_total` DECIMAL(10,2) NOT NULL,
  `payment_status` ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
  `shipping_address` TEXT NOT NULL,
  `order_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `payment_status` (`payment_status`),
  KEY `order_date` (`order_date`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: order_items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) UNSIGNED NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `quantity` INT(11) NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Sample Data (Optional - for testing)
-- Default Admin User (Password: admin123 - CHANGE IN PRODUCTION!)
-- Password hash for 'admin123' using ARGON2ID
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`) VALUES
('Administrator', 'admin@bloomvine.com', '$argon2id$v=19$m=65536,t=4,p=1$YVVtUnN3NmY1UUVVQUZBeA$mQwbgIiu3g2oYlw62eB9V/i612oJzURe1H4RWZ+hnj0', 'admin')
ON DUPLICATE KEY UPDATE `email`=`email`;

-- Sample Categories
INSERT INTO `categories` (`name_en`, `name_ku`, `slug`) VALUES
('Wedding', 'گۆواد', 'wedding'),
('Anniversary', 'ساڵیادی', 'anniversary'),
('Birthday', 'جەژنی لەدایکبوون', 'birthday'),
('Sympathy', 'دڵسۆزی', 'sympathy'),
('Congratulations', 'پیرۆزی', 'congratulations')
ON DUPLICATE KEY UPDATE `slug`=`slug`;

