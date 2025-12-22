-- Add payment method fields to orders table
-- Bloom & Vine Flower Store

ALTER TABLE `orders`
ADD COLUMN IF NOT EXISTS `payment_method` ENUM('visa', 'mastercard') DEFAULT NULL AFTER `payment_status`,
ADD COLUMN IF NOT EXISTS `card_last_four` VARCHAR(4) DEFAULT NULL AFTER `payment_method`,
ADD COLUMN IF NOT EXISTS `cardholder_name` VARCHAR(255) DEFAULT NULL AFTER `card_last_four`,
ADD COLUMN IF NOT EXISTS `card_expiry_month` TINYINT(2) DEFAULT NULL AFTER `cardholder_name`,
ADD COLUMN IF NOT EXISTS `card_expiry_year` SMALLINT(4) DEFAULT NULL AFTER `card_expiry_month`;

-- Add index for payment method
CREATE INDEX IF NOT EXISTS `idx_orders_payment_method` ON `orders` (`payment_method`);

