<?php
declare(strict_types=1);

/**
 * Migration Runner for Order Extras Feature
 * Run this file once to add the order extras feature to your database
 * 
 * Usage: Open in browser or run via CLI:
 *   php database/run_order_extras_migration.php
 */

require_once __DIR__ . '/../src/functions.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = getDB();
    
    // Read and execute the migration SQL
    $sqlFile = __DIR__ . '/add_order_extras.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new Exception("Failed to read migration file: {$sqlFile}");
    }
    
    // Split SQL statements by semicolon and execute each one
    $statements = array_filter(
        array_map(
            'trim',
            preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql)
        ),
        fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--')
    );
    
    foreach ($statements as $statement) {
        if (!empty(trim($statement))) {
            $pdo->exec($statement);
        }
    }
    
    echo "✓ Order Extras Migration Completed Successfully!\n\n";
    echo "✓ Created tables:\n";
    echo "  - order_extras (stores extras selected for orders)\n";
    echo "  - available_extras (pre-configured extras available for selection)\n\n";
    echo "✓ Inserted 10 default extras\n";
    echo "\nYou can now use the order extras feature in your application.\n";
    
} catch (Exception $e) {
    http_response_code(500);
    echo "✗ Migration Error:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}
?>
