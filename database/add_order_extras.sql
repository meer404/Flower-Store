-- Add Order Extras/Add-ons Table for Flowers Store
-- Supports items like greeting cards, small gifts, etc.

-- Table: order_extras
CREATE TABLE IF NOT EXISTS `order_extras` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) UNSIGNED NOT NULL,
  `extra_type` ENUM('greeting_card', 'small_gift', 'chocolate_box', 'candle', 'balloons') NOT NULL,
  `extra_name_en` VARCHAR(255) NOT NULL,
  `extra_name_ku` VARCHAR(255) NOT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `extra_type` (`extra_type`),
  CONSTRAINT `fk_order_extras_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: available_extras
-- This table stores pre-configured extras available for order
CREATE TABLE IF NOT EXISTS `available_extras` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `extra_type` ENUM('greeting_card', 'small_gift', 'chocolate_box', 'candle', 'balloons') NOT NULL,
  `name_en` VARCHAR(255) NOT NULL,
  `name_ku` VARCHAR(255) NOT NULL,
  `description_en` TEXT,
  `description_ku` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT 'fas fa-gift',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `extra_type_name` (`extra_type`, `name_en`),
  KEY `is_active` (`is_active`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default extras
INSERT INTO `available_extras` (`extra_type`, `name_en`, `name_ku`, `description_en`, `description_ku`, `price`, `image_url`, `icon`, `sort_order`) VALUES
('greeting_card', 'Standard Greeting Card', 'کارتی پیروزی', 'A beautiful greeting card with personal message', 'کارتێکی جوانی پیروزی کە تۆ دەتوانی پیامی خۆت لێی بنووسی', 2.99, 'uploads/extras/greeting-card-standard.jpg', 'fas fa-envelope', 1),
('greeting_card', 'Luxury Greeting Card', 'کارتی شاخی لوکسی', 'Premium quality greeting card with embossing', 'کارتی بە کوالیتیی بەرز و دیزاینی شاخ', 4.99, 'uploads/extras/greeting-card-luxury.jpg', 'fas fa-crown', 2),
('small_gift', 'Scented Candle', 'شاممی بۆ بۆ', 'Aromatic scented candle', 'شاممی خوشبۆی', 5.99, 'uploads/extras/candle-scented.jpg', 'fas fa-fire', 1),
('small_gift', 'Chocolate Box', 'بە چۆکۆلیتی', 'Assorted premium chocolates', 'چۆکۆلیتی ڕیز', 7.99, 'uploads/extras/chocolate-box.jpg', 'fas fa-box', 2),
('small_gift', 'Gift Box with Ribbon', 'قوتووی بە رێومی', 'Elegant gift box with decorative ribbon', 'قوتووی حێزار کراو', 3.99, 'uploads/extras/gift-box-ribbon.jpg', 'fas fa-gift', 3),
('chocolate_box', 'Premium Chocolate Assortment', 'چۆکۆلیتی پریمیوم', 'Selection of premium Belgian chocolates', 'چۆکۆلیتی بە نرخی بەرز', 12.99, 'uploads/extras/chocolate-premium.jpg', 'fas fa-box', 1),
('candle', 'Rose Scented Candle', 'شاممی بۆی سۆسن', 'Premium rose-scented candle', 'شاممی خوشبۆی سۆسن', 6.99, 'uploads/extras/candle-rose.jpg', 'fas fa-fire', 1),
('candle', 'Lavender Candle', 'شاممی بۆی ئاسپ', 'Relaxing lavender-scented candle', 'شاممی خۆشبۆی ئاسپی', 6.99, 'uploads/extras/candle-lavender.jpg', 'fas fa-fire', 2),
('balloons', 'Helium Balloon Set (5pcs)', 'بۆ بالۆنی زۆر (5 دانە)', 'Set of 5 colorful helium balloons', 'پێنج بالۆنی رەنگین', 8.99, 'uploads/extras/balloons-5.jpg', 'fas fa-balloon', 1),
('balloons', 'Premium Balloon Combo (10pcs)', 'بالۆن زۆر (10 دانە)', 'Set of 10 premium balloons', 'دە بالۆنی شاخ', 13.99, 'uploads/extras/balloons-10.jpg', 'fas fa-balloon', 2)
ON DUPLICATE KEY UPDATE `price`=`price`;
