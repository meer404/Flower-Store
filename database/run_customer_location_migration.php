<?php
declare(strict_types = 1)
;

/**
 * Migration: Add Customer Location to Orders
 * Adds customer_lat and customer_lng columns to orders table
 */

require_once __DIR__ . '/../src/functions.php';

echo "<h2>Customer Location Migration</h2>";

try {
    $pdo = getDB();

    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'customer_lat'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: blue;'>✓ Columns already exist. Migration not needed.</p>";
        exit;
    }

    // Run migration
    $sql = file_get_contents(__DIR__ . '/add_customer_location.sql');
    $pdo->exec($sql);

    echo "<p style='color: green;'>✓ Successfully added customer_lat and customer_lng columns to orders table.</p>";


}
catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Migration failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}
