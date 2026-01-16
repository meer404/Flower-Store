<?php
declare(strict_types=1);

/**
 * Add Product Page (Admin)
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';
require_once __DIR__ . '/../src/components.php';

requireAdmin();
requirePermission('manage_products');

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
                    $error = t('product_error') . ' - ' . t('invalid_file_type');
                } elseif ($file['size'] > $maxSize) {
                    $error = t('product_error') . ' - ' . t('file_too_large');
                } else {
                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('product_', true) . '_' . time() . '.' . $extension;
                    $filepath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $imageUrl = 'uploads/' . $filename;
                    } else {
                        $error = t('product_error') . ' - ' . t('upload_failed');
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
                    
                    redirect('products.php', t('product_added'), 'success');
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-gray-50 min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-x-hidden">
            <!-- Admin Header -->
            <?php include __DIR__ . '/header.php'; ?>

            <!-- Main Content -->
            <main class="flex-1 p-4 md:p-8">
                <div class="max-w-4xl mx-auto">
                    <!-- Page Header -->
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h1 class="text-3xl font-luxury font-bold text-gray-800 flex items-center gap-3">
                                <i class="fas fa-plus-circle text-purple-600"></i>
                                <?= e(t('admin_add_product')) ?>
                            </h1>
                            <p class="text-gray-500 mt-1">Create a new product for your store</p>
                        </div>
                        <a href="products.php" class="text-gray-500 hover:text-gray-700 font-medium flex items-center gap-2 transition-colors">
                            <i class="fas fa-arrow-left"></i> Back to Products
                        </a>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-2xl mb-6 shadow-sm flex items-center gap-3">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                            <div><?= e($error) ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Left Column: Main Info -->
                            <div class="lg:col-span-2 space-y-6">
                                <!-- Basic details -->
                                <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8">
                                    <h2 class="text-xl font-bold text-gray-800 mb-6 pb-2 border-b border-gray-100">Product Details</h2>
                                    
                                    <div class="grid grid-cols-1 gap-6">
                                        <!-- English Name -->
                                        <div>
                                            <label for="name_en" class="block text-sm font-bold text-gray-700 mb-2">
                                                <?= e(t('product_name_en')) ?> <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" id="name_en" name="name_en" required
                                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white placeholder-gray-400">
                                        </div>
                                        
                                        <!-- Kurdish Name -->
                                        <div>
                                            <label for="name_ku" class="block text-sm font-bold text-gray-700 mb-2">
                                                <?= e(t('product_name_ku')) ?> <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" id="name_ku" name="name_ku" required
                                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white placeholder-gray-400">
                                        </div>
                                        
                                        <!-- English Description -->
                                        <div>
                                            <label for="description_en" class="block text-sm font-bold text-gray-700 mb-2">
                                                <?= e(t('product_description_en')) ?> <span class="text-red-500">*</span>
                                            </label>
                                            <textarea id="description_en" name="description_en" rows="4" required
                                                      class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white placeholder-gray-400"></textarea>
                                        </div>
                                        
                                        <!-- Kurdish Description -->
                                        <div>
                                            <label for="description_ku" class="block text-sm font-bold text-gray-700 mb-2">
                                                <?= e(t('product_description_ku')) ?> <span class="text-red-500">*</span>
                                            </label>
                                            <textarea id="description_ku" name="description_ku" rows="4" required
                                                      class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white placeholder-gray-400"></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pricing & Inventory -->
                                <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8">
                                    <h2 class="text-xl font-bold text-gray-800 mb-6 pb-2 border-b border-gray-100">Pricing & Inventory</h2>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="price" class="block text-sm font-bold text-gray-700 mb-2">
                                                <?= e(t('price')) ?> ($) <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 font-bold">$</span>
                                                </div>
                                                <input type="number" id="price" name="price" step="0.01" min="0" required
                                                       class="w-full pl-8 pr-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label for="stock_qty" class="block text-sm font-bold text-gray-700 mb-2">
                                                <?= e(t('stock_quantity')) ?> <span class="text-red-500">*</span>
                                            </label>
                                            <input type="number" id="stock_qty" name="stock_qty" min="0" required
                                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Column: Media & Organization -->
                            <div class="lg:col-span-1 space-y-6">
                                <!-- Organization -->
                                <div class="bg-white rounded-2xl shadow-lg p-6">
                                    <h2 class="text-lg font-bold text-gray-800 mb-4">Organization</h2>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label for="category_id" class="block text-sm font-bold text-gray-700 mb-2">
                                                <?= e(t('category')) ?> <span class="text-red-500">*</span>
                                            </label>
                                            <select id="category_id" name="category_id" required
                                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                                <option value=""><?= e(t('select_category')) ?></option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?= e((string)$category['id']) ?>">
                                                        <?= e($category['name_en']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="pt-4 border-t border-gray-100">
                                            <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                                <div class="relative flex items-center">
                                                    <input type="checkbox" name="is_featured" value="1"
                                                           class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border border-gray-300 transition-all checked:border-purple-600 checked:bg-purple-600">
                                                    <div class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-white opacity-0 transition-opacity peer-checked:opacity-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                                    </div>
                                                </div>
                                                <span class="text-sm font-bold text-gray-700"><?= e(t('featured')) ?></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Image Upload -->
                                <div class="bg-white rounded-2xl shadow-lg p-6">
                                    <h2 class="text-lg font-bold text-gray-800 mb-4"><?= e(t('product_image')) ?></h2>
                                    
                                    <div class="relative border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-purple-400 transition-colors bg-gray-50">
                                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp"
                                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                            <p class="text-sm font-medium text-gray-600">Click to upload image</p>
                                            <p class="text-xs text-gray-400 mt-1">JPG, PNG, WebP up to 5MB</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Submit Button -->
                                <button type="submit" 
                                        class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-4 px-6 rounded-xl hover:shadow-xl transition-all duration-300 font-bold text-lg transform hover:-translate-y-1">
                                    <i class="fas fa-save me-2"></i><?= e(t('save_product')) ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
                        
            <!-- Footer -->
            <?php include __DIR__ . '/footer.php'; ?>
        </div>
    </div>
</body>
</html>
