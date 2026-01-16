-- Admin Permissions Table Migration
-- This table stores granular permissions for admin users

-- Create admin_permissions table
CREATE TABLE IF NOT EXISTS `admin_permissions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT(11) UNSIGNED NOT NULL,
  `permission` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_permission` (`admin_id`, `permission`),
  KEY `admin_id` (`admin_id`),
  KEY `permission` (`permission`),
  CONSTRAINT `fk_permissions_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Available permissions reference:
-- 'view_dashboard' - View admin dashboard
-- 'manage_products' - Add, edit, delete products
-- 'manage_categories' - Manage product categories
-- 'view_orders' - View orders and order details
-- 'manage_orders' - Update order status
-- 'view_reports' - Access reports section
-- 'manage_admins' - Create and manage other admins (super admin only)
-- 'view_users' - View customer users
-- 'manage_users' - Ban/modify customer accounts
-- 'system_settings' - Access system settings

-- Note: Super admins automatically have all permissions
