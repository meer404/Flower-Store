<?php
declare(strict_types=1);

/**
 * Migration Runner - Add Image Support to Order Extras
 * Run this file once if you already have order extras installed
 * 
 * Usage: Open in browser or run via CLI:
 *   php database/run_add_extras_images_migration.php
 */

require_once __DIR__ . '/../src/functions.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = getDB();
    
    // Check if image_url column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM `available_extras` LIKE 'image_url'");
    $columnExists = (bool)$stmt->fetch();
    
    if (!$columnExists) {
        // Add image_url column
        $pdo->exec('
            ALTER TABLE `available_extras` 
            ADD COLUMN `image_url` VARCHAR(255) DEFAULT NULL 
            AFTER `price`
        ');
        echo "✓ Added image_url column to available_extras table\n";
    } else {
        echo "✓ image_url column already exists\n";
    }
    
    // Update existing extras with image URLs
    $updates = [
        1 => 'uploads/extras/greeting-card-standard.jpg',
        2 => 'uploads/extras/greeting-card-luxury.jpg',
        3 => 'uploads/extras/candle-scented.jpg',
        4 => 'uploads/extras/chocolate-box.jpg',
        5 => 'uploads/extras/gift-box-ribbon.jpg',
        6 => 'uploads/extras/chocolate-premium.jpg',
        7 => 'uploads/extras/candle-rose.jpg',
        8 => 'uploads/extras/candle-lavender.jpg',
        9 => 'uploads/extras/balloons-5.jpg',
        10 => 'uploads/extras/balloons-10.jpg',
    ];
    
    $updatedCount = 0;
    foreach ($updates as $id => $imageUrl) {
        $stmt = $pdo->prepare('UPDATE `available_extras` SET `image_url` = :image_url WHERE `id` = :id AND `image_url` IS NULL');
        $result = $stmt->execute(['image_url' => $imageUrl, 'id' => $id]);
        if ($stmt->rowCount() > 0) {
            $updatedCount++;
        }
    }
    
    echo "✓ Updated {$updatedCount} extras with image URLs\n\n";
    echo "✓ Image Support Migration Completed!\n\n";
    
    echo "📝 Next Steps:\n";
    echo "1. Add your extra images to the uploads/extras/ folder\n";
    echo "2. Name them according to the image URLs in the database\n";
    echo "3. Required image files:\n";
    echo "   - greeting-card-standard.jpg\n";
    echo "   - greeting-card-luxury.jpg\n";
    echo "   - candle-scented.jpg\n";
    echo "   - chocolate-box.jpg\n";
    echo "   - gift-box-ribbon.jpg\n";
    echo "   - chocolate-premium.jpg\n";
    echo "   - candle-rose.jpg\n";
    echo "   - candle-lavender.jpg\n";
    echo "   - balloons-5.jpg\n";
    echo "   - balloons-10.jpg\n\n";
    echo "✅ Images are optional - if missing, icons will be shown instead\n";
    
} catch (Exception $e) {
    http_response_code(500);
    echo "✗ Migration Error:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}
?>
