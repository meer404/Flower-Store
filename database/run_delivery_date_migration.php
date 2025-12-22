<?php
/**
 * Run Delivery Date Migration
 * Bloom & Vine Flower Store
 * 
 * This script adds the delivery_date column to the orders table.
 * Run this once to update your database schema.
 */

require_once __DIR__ . '/../src/config/db.php';

try {
    $pdo = getDB();
    
    echo "Starting migration: Adding delivery_date column to orders table...\n";
    
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'delivery_date'");
    if ($stmt->rowCount() > 0) {
        echo "Column 'delivery_date' already exists. Migration not needed.\n";
        exit(0);
    }
    
    // Add the column
    $pdo->exec("ALTER TABLE `orders` ADD COLUMN `delivery_date` DATE DEFAULT NULL AFTER `shipping_address`");
    
    // Add index
    $pdo->exec("CREATE INDEX `idx_orders_delivery_date` ON `orders` (`delivery_date`)");
    
    echo "Migration completed successfully!\n";
    echo "The delivery_date column has been added to the orders table.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

