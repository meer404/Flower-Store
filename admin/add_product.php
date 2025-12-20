<?php
declare(strict_types=1);

/**
 * Add Product Page (Admin)
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';

requireAdmin();

$pdo = getDB();
$error = '';
$success = '';

// Get categories for dropdown
$stmt = $pdo->query('SELECT id, name_en, name_ku FROM categories ORDER BY name_en');
$categories = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = sanitizeInput('csrf_token', 'POST');
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = t('product_error');
    } else {
        $nameEn = sanitizeInput('name_en', 'POST');
        $nameKu = sanitizeInput('name_ku', 'POST');
        $descriptionEn = sanitizeInput('description_en', 'POST');
        $descriptionKu = sanitizeInput('description_ku', 'POST');
        $categoryId = (int)sanitizeInput('category_id', 'POST');
        $price = (float)sanitizeInput('price', 'POST');
        $stockQty = (int)sanitizeInput('stock_qty', 'POST');
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        
        // Validate required fields
        if (empty($nameEn) || empty($nameKu) || empty($descriptionEn) || empty($descriptionKu) || $categoryId <= 0 || $price <= 0) {
            $error = t('product_error');
        } else {
            // Handle file upload
            $imageUrl = null;
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/';
                
                // Create uploads directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $file = $_FILES['image'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($file['type'], $allowedTypes, true)) {
                    $error = t('product_error') . ' - ' . e('Invalid file type');
                } elseif ($file['size'] > $maxSize) {
                    $error = t('product_error') . ' - ' . e('File too large');
                } else {
                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('product_', true) . '_' . time() . '.' . $extension;
                    $filepath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $imageUrl = 'uploads/' . $filename;
                    } else {
                        $error = t('product_error') . ' - ' . e('Upload failed');
                    }
                }
            }
            
            if (!$error) {
                try {
                    $stmt = $pdo->prepare('
                        INSERT INTO products (category_id, name_en, name_ku, description_en, description_ku, price, stock_qty, image_url, is_featured)
                        VALUES (:category_id, :name_en, :name_ku, :description_en, :description_ku, :price, :stock_qty, :image_url, :is_featured)
                    ');
                    
                    $stmt->execute([
                        'category_id' => $categoryId,
                        'name_en' => $nameEn,
                        'name_ku' => $nameKu,
                        'description_en' => $descriptionEn,
                        'description_ku' => $descriptionKu,
                        'price' => $price,
                        'stock_qty' => $stockQty,
                        'image_url' => $imageUrl,
                        'is_featured' => $isFeatured
                    ]);
                    
                    redirect('dashboard.php', t('product_added'), 'success');
                } catch (PDOException $e) {
                    error_log('Product insert error: ' . $e->getMessage());
                    $error = t('product_error');
                }
            }
        }
    }
}

$csrfToken = generateCSRFToken();
$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('admin_add_product')) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <!-- Navbar -->
    <nav class="bg-white border-b border-luxury-border shadow-sm">
        <div class="container mx-auto px-4 md:px-6 py-4">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <a href="../index.php" class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary tracking-wide">Bloom & Vine</a>
                <div class="flex items-center space-x-4 md:space-x-8 flex-wrap text-sm md:text-base" style="direction: ltr;">
                    <a href="dashboard.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('admin_dashboard')) ?></a>
                    <a href="products.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium hidden md:inline"><?= e('Products') ?></a>
                    <a href="../index.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_home')) ?></a>
                    <a href="../logout.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_logout')) ?></a>
                    <a href="?lang=<?= $lang === 'en' ? 'ku' : 'en' ?>" class="text-luxury-accent hover:text-luxury-primary font-semibold border border-luxury-accent px-2 md:px-3 py-1 rounded-sm text-xs md:text-sm">
                        <?= $lang === 'en' ? 'KU' : 'EN' ?>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
        <h1 class="text-3xl md:text-4xl font-luxury font-bold text-luxury-primary mb-6 md:mb-8 tracking-wide"><?= e(t('admin_add_product')) ?></h1>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-sm mb-6">
                <?= e($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8">
            <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                
                <!-- Dual Language Inputs -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name_en" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e(t('product_name_en')) ?>
                        </label>
                        <input type="text" id="name_en" name="name_en" required
                               class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                    </div>
                    
                    <div>
                        <label for="name_ku" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e(t('product_name_ku')) ?>
                        </label>
                        <input type="text" id="name_ku" name="name_ku" required
                               class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="description_en" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e(t('product_description_en')) ?>
                        </label>
                        <textarea id="description_en" name="description_en" rows="4" required
                                  class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent"></textarea>
                    </div>
                    
                    <div>
                        <label for="description_ku" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e(t('product_description_ku')) ?>
                        </label>
                        <textarea id="description_ku" name="description_ku" rows="4" required
                                  class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent"></textarea>
                    </div>
                </div>
                
                <!-- Category and Price -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e(t('category')) ?>
                        </label>
                        <select id="category_id" name="category_id" required
                                class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                            <option value=""><?= e('Select Category') ?></option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= e((string)$category['id']) ?>">
                                    <?= e($category['name_en']) ?> / <?= e($category['name_ku']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="price" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e(t('price')) ?>
                        </label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required
                               class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                    </div>
                    
                    <div>
                        <label for="stock_qty" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e(t('stock_quantity')) ?>
                        </label>
                        <input type="number" id="stock_qty" name="stock_qty" min="0" required
                               class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                    </div>
                </div>
                
                <!-- Image Upload -->
                <div>
                    <label for="image" class="block text-sm font-medium text-luxury-text mb-2">
                        <?= e(t('product_image')) ?>
                    </label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp"
                           class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                    <p class="text-xs text-luxury-textLight mt-1.5"><?= e('Max size: 5MB. Formats: JPEG, PNG, GIF, WebP') ?></p>
                </div>
                
                <!-- Featured Checkbox -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_featured" value="1"
                               class="mr-2 h-4 w-4 text-luxury-accent focus:ring-luxury-accent border-luxury-border rounded-sm">
                        <span class="text-sm font-medium text-luxury-text"><?= e(t('featured')) ?></span>
                    </label>
                </div>
                
                <button type="submit" 
                        class="w-full bg-luxury-accent text-white py-3 px-4 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                    <?= e(t('save_product')) ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-luxury-primary text-white py-12 mt-20 border-t border-luxury-border">
        <div class="container mx-auto px-6 text-center">
            <p class="text-luxury-accentLight font-light tracking-wide">&copy; <?= e(date('Y')) ?> Bloom & Vine. <?= e('All rights reserved.') ?></p>
        </div>
    </footer>
</body>
</html>

