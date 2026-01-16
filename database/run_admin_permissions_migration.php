<?php
/**
 * Admin Permissions Migration
 * Run this script once to create the admin_permissions table
 * 
 * Usage: Access this file directly in browser or run from terminal:
 * php database/run_admin_permissions_migration.php
 */

require_once __DIR__ . '/../src/config/db.php';

try {
    $pdo = getDB();
    
    echo "<h2>Starting Admin Permissions Migration...</h2>";
    echo "<p>Creating admin_permissions table...</p>";
    
    $sql = "CREATE TABLE IF NOT EXISTS `admin_permissions` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `admin_id` INT(11) UNSIGNED NOT NULL,
        `permission` VARCHAR(100) NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `admin_permission` (`admin_id`, `permission`),
        KEY `admin_id` (`admin_id`),
        KEY `permission` (`permission`),
        CONSTRAINT `fk_permissions_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    
    echo "<p style='color: green;'><strong>✓ Table created successfully!</strong></p>";
    
    echo "<h3>Migration Complete</h3>";
    echo "<p>The admin_permissions table has been created.</p>";
    echo "<p>Next steps:</p>";
    echo "<ul>";
    echo "<li>Go to Super Admin → Admins page</li>";
    echo "<li>Click 'Edit Permissions' on any admin</li>";
    echo "<li>Select permissions for that admin</li>";
    echo "<li>Save the permissions</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Migration Error</h2>";
    echo "<p style='color: red;'><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
    exit(1);
}
