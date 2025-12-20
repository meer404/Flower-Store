-- Extended Schema for Advanced Features
-- Run this after the base schema.sql

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

-- Add additional fields to users table
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `phone` VARCHAR(20) DEFAULT NULL AFTER `email`,
ADD COLUMN IF NOT EXISTS `address` TEXT DEFAULT NULL AFTER `phone`,
ADD COLUMN IF NOT EXISTS `city` VARCHAR(100) DEFAULT NULL AFTER `address`,
ADD COLUMN IF NOT EXISTS `postal_code` VARCHAR(20) DEFAULT NULL AFTER `city`,
ADD COLUMN IF NOT EXISTS `country` VARCHAR(100) DEFAULT NULL AFTER `postal_code`,
ADD COLUMN IF NOT EXISTS `avatar_url` VARCHAR(255) DEFAULT NULL AFTER `country`;

-- Add order status tracking
ALTER TABLE `orders`
ADD COLUMN IF NOT EXISTS `order_status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending' AFTER `payment_status`,
ADD COLUMN IF NOT EXISTS `tracking_number` VARCHAR(100) DEFAULT NULL AFTER `order_status`,
ADD COLUMN IF NOT EXISTS `notes` TEXT DEFAULT NULL AFTER `tracking_number`;

-- Add more product fields
ALTER TABLE `products`
ADD COLUMN IF NOT EXISTS `sku` VARCHAR(100) DEFAULT NULL AFTER `id`,
ADD COLUMN IF NOT EXISTS `weight` DECIMAL(8,2) DEFAULT NULL AFTER `stock_qty`,
ADD COLUMN IF NOT EXISTS `dimensions` VARCHAR(100) DEFAULT NULL AFTER `weight`,
ADD COLUMN IF NOT EXISTS `views` INT(11) NOT NULL DEFAULT 0 AFTER `is_featured`,
ADD COLUMN IF NOT EXISTS `sales_count` INT(11) NOT NULL DEFAULT 0 AFTER `views`;

-- Add index for SKU
ALTER TABLE `products` ADD UNIQUE KEY IF NOT EXISTS `sku` (`sku`);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_orders_user_date` ON `orders` (`user_id`, `order_date`);
CREATE INDEX IF NOT EXISTS `idx_products_featured_stock` ON `products` (`is_featured`, `stock_qty`);
CREATE INDEX IF NOT EXISTS `idx_reviews_product_rating` ON `reviews` (`product_id`, `rating`);

