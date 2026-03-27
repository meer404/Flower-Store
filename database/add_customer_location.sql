-- Add customer location columns to orders table
-- Stores the GPS coordinates of the customer at checkout time

ALTER TABLE `orders`
  ADD COLUMN `customer_lat` DECIMAL(10,7) DEFAULT NULL AFTER `shipping_address`,
  ADD COLUMN `customer_lng` DECIMAL(10,7) DEFAULT NULL AFTER `customer_lat`;
