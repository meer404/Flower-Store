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
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT NULL,
  `role` ENUM('super_admin', 'admin', 'customer') NOT NULL DEFAULT 'customer',
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
  `sku` VARCHAR(100) DEFAULT NULL,
  `category_id` INT(11) UNSIGNED NOT NULL,
  `name_en` VARCHAR(255) NOT NULL,
  `name_ku` VARCHAR(255) NOT NULL,
  `description_en` TEXT NOT NULL,
  `description_ku` TEXT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `stock_qty` INT(11) NOT NULL DEFAULT 0,
  `weight` DECIMAL(8,2) DEFAULT NULL,
  `dimensions` VARCHAR(100) DEFAULT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `is_featured` BOOLEAN NOT NULL DEFAULT FALSE,
  `views` INT(11) NOT NULL DEFAULT 0,
  `sales_count` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `category_id` (`category_id`),
  KEY `is_featured` (`is_featured`),
  KEY `price` (`price`),
  KEY `featured_stock` (`is_featured`, `stock_qty`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `grand_total` DECIMAL(10,2) NOT NULL,
  `payment_status` ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
  `order_status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
  `shipping_address` TEXT NOT NULL,
  `delivery_date` DATE DEFAULT NULL,
  `payment_method` ENUM('visa', 'mastercard') DEFAULT NULL,
  `card_last_four` VARCHAR(4) DEFAULT NULL,
  `cardholder_name` VARCHAR(255) DEFAULT NULL,
  `card_expiry_month` TINYINT(2) DEFAULT NULL,
  `card_expiry_year` SMALLINT(4) DEFAULT NULL,
  `tracking_number` VARCHAR(255) DEFAULT NULL,
  `order_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `payment_status` (`payment_status`),
  KEY `order_status` (`order_status`),
  KEY `order_date` (`order_date`),
  KEY `delivery_date` (`delivery_date`),
  KEY `payment_method` (`payment_method`),
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

-- Table: wishlist
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`, `product_id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: reviews
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `rating` TINYINT(1) UNSIGNED NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product_review` (`user_id`, `product_id`),
  KEY `product_id` (`product_id`),
  KEY `rating` (`rating`),
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: product_images (for image gallery)
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `display_order` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: system_settings
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `setting_type` ENUM('string', 'number', 'boolean', 'json') NOT NULL DEFAULT 'string',
  `description` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` INT(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `fk_system_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'Bloom & Vine', 'string', 'Website name'),
('site_email', 'info@bloomandvine.com', 'string', 'Site contact email'),
('currency', '$', 'string', 'Currency symbol'),
('tax_rate', '0', 'number', 'Tax rate percentage'),
('shipping_cost', '0', 'number', 'Default shipping cost'),
('maintenance_mode', '0', 'boolean', 'Maintenance mode enabled'),
('max_upload_size', '5242880', 'number', 'Max file upload size in bytes')
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;

-- Table: activity_log
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) DEFAULT NULL,
  `entity_id` INT(11) UNSIGNED DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `entity_type` (`entity_type`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_activity_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `order_id` INT(11) UNSIGNED DEFAULT NULL,
  `type` ENUM('order_status', 'order_tracking', 'payment_status', 'general') NOT NULL DEFAULT 'general',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` BOOLEAN NOT NULL DEFAULT FALSE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `order_id` (`order_id`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_notifications_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Super Admin User (Password: superadmin123 - CHANGE IN PRODUCTION!)
-- Password hash for 'superadmin123' using ARGON2ID
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`) VALUES
('Super Administrator', 'superadmin@bloomvine.com', '$argon2id$v=19$m=65536,t=4,p=1$ZE83WHRaMld5Y0JHaXI5Ng$C78HCnRT3AmlSm4OTLlxb9uT9qJrsZMZl+04KaV60H4', 'super_admin')
ON DUPLICATE KEY UPDATE `email`=`email`;

-- Create composite indexes for performance
CREATE INDEX IF NOT EXISTS `idx_orders_user_date` ON `orders` (`user_id`, `order_date`);
CREATE INDEX IF NOT EXISTS `idx_reviews_product_rating` ON `reviews` (`product_id`, `rating`);

