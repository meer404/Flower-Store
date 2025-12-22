-- Add delivery_date column to orders table
-- Bloom & Vine Flower Store

ALTER TABLE `orders`
ADD COLUMN IF NOT EXISTS `delivery_date` DATE DEFAULT NULL AFTER `shipping_address`;

-- Add index for better query performance
CREATE INDEX IF NOT EXISTS `idx_orders_delivery_date` ON `orders` (`delivery_date`);

