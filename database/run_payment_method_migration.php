<?php
/**
 * Run Payment Method Migration
 * Bloom & Vine Flower Store
 * 
 * This script adds payment method fields to the orders table.
 * Run this once to update your database schema.
 */

require_once __DIR__ . '/../src/config/db.php';

try {
    $pdo = getDB();
    
    echo "Starting migration: Adding payment method fields to orders table...\n";
    
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'payment_method'");
    if ($stmt->rowCount() > 0) {
        echo "Payment method columns already exist. Migration not needed.\n";
        exit(0);
    }
    
    // Add the columns
    $pdo->exec("ALTER TABLE `orders` ADD COLUMN `payment_method` ENUM('visa', 'mastercard') DEFAULT NULL AFTER `payment_status`");
    $pdo->exec("ALTER TABLE `orders` ADD COLUMN `card_last_four` VARCHAR(4) DEFAULT NULL AFTER `payment_method`");
    $pdo->exec("ALTER TABLE `orders` ADD COLUMN `cardholder_name` VARCHAR(255) DEFAULT NULL AFTER `card_last_four`");
    $pdo->exec("ALTER TABLE `orders` ADD COLUMN `card_expiry_month` TINYINT(2) DEFAULT NULL AFTER `cardholder_name`");
    $pdo->exec("ALTER TABLE `orders` ADD COLUMN `card_expiry_year` SMALLINT(4) DEFAULT NULL AFTER `card_expiry_month`");
    
    // Add index
    $pdo->exec("CREATE INDEX `idx_orders_payment_method` ON `orders` (`payment_method`)");
    
    echo "Migration completed successfully!\n";
    echo "The payment method columns have been added to the orders table.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

