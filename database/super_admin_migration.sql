-- Super Admin Migration
-- Run this SQL to add super_admin role support

-- Update users table to include super_admin role
ALTER TABLE `users` 
MODIFY COLUMN `role` ENUM('super_admin', 'admin', 'customer') NOT NULL DEFAULT 'customer';

-- Create super admin user (Password: superadmin123 - CHANGE IN PRODUCTION!)
-- Password hash for 'superadmin123' using ARGON2ID
-- Note: If user already exists, run fix_superadmin_password.php to update the password hash
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`) VALUES
('Super Administrator', 'superadmin@bloomvine.com', '$argon2id$v=19$m=65536,t=4,p=1$ZE83WHRaMld5Y0JHaXI5Ng$C78HCnRT3AmlSm4OTLlxb9uT9qJrsZMZl+04KaV60H4', 'super_admin')
ON DUPLICATE KEY UPDATE `email`=`email`;

-- Create system_settings table for super admin configuration
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

-- Create activity_log table for tracking super admin actions
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

