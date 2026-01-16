<?php
declare(strict_types=1);

/**
 * Edit Product Page (Admin)
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';
require_once __DIR__ . '/../src/components.php';

requireAdmin();
requirePermission('manage_products');

$pdo = getDB();
$productId = (int)sanitizeInput('id', 'GET', '0');
$error = '';
$success = '';

if ($productId <= 0) {
    redirect('dashboard.php', e(t('invalid_product')), 'error');
}

// Get product
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    redirect('dashboard.php', e(t('product_not_found')), 'error');
}

// Get categories
$stmt = $pdo->query('SELECT id, name_en, name_ku FROM categories ORDER BY name_en');
$categories = $stmt->fetchAll();

// Get product images
$stmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = :id ORDER BY display_order, id');
$stmt->execute(['id' => $productId]);
$productImages = $stmt->fetchAll();

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
        $sku = sanitizeInput('sku', 'POST');
        
        if (empty($nameEn) || empty($nameKu) || $categoryId <= 0 || $price <= 0) {
            $error = t('product_error');
        } else {
            try {
                // Handle main image upload
                $imageUrl = $product['image_url'];
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../uploads/';
                    $file = $_FILES['image'];
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $maxSize = 5 * 1024 * 1024;
                    
                    if (in_array($file['type'], $allowedTypes, true) && $file['size'] <= $maxSize) {
                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = uniqid('product_', true) . '_' . time() . '.' . $extension;
                        $filepath = $uploadDir . $filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            $imageUrl = 'uploads/' . $filename;
                        }
                    }
                }
                
                // Update product
                $stmt = $pdo->prepare('
                    UPDATE products 
                    SET category_id = :category_id, name_en = :name_en, name_ku = :name_ku, 
                        description_en = :description_en, description_ku = :description_ku, 
                        price = :price, stock_qty = :stock_qty, image_url = :image_url, 
                        is_featured = :is_featured, sku = :sku
                    WHERE id = :id
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
                    'is_featured' => $isFeatured,
                    'sku' => $sku ?: null,
                    'id' => $productId
                ]);
                
                // Handle gallery images
                if (isset($_FILES['gallery_images']) && is_array($_FILES['gallery_images']['name'])) {
                    foreach ($_FILES['gallery_images']['name'] as $index => $name) {
                        if ($_FILES['gallery_images']['error'][$index] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['gallery_images']['name'][$index],
                                'type' => $_FILES['gallery_images']['type'][$index],
                                'tmp_name' => $_FILES['gallery_images']['tmp_name'][$index],
                                'size' => $_FILES['gallery_images']['size'][$index]
                            ];
                            
                            if (in_array($file['type'], $allowedTypes, true) && $file['size'] <= $maxSize) {
                                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                                $filename = uniqid('gallery_', true) . '_' . time() . '.' . $extension;
                                $filepath = $uploadDir . $filename;
                                
                                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                                    $stmt = $pdo->prepare('INSERT INTO product_images (product_id, image_url, display_order) VALUES (:product_id, :image_url, :display_order)');
                                    $stmt->execute([
                                        'product_id' => $productId,
                                        'image_url' => 'uploads/' . $filename,
                                        'display_order' => count($productImages) + $index
                                    ]);
                                }
                            }
                        }
                    }
                }
                
                redirect('products.php', t('product_updated'), 'success');
            } catch (PDOException $e) {
                error_log('Product update error: ' . $e->getMessage());
                $error = t('product_error');
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
    <title><?= e(t('edit_product')) ?> - Bloom & Vine</title>
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
                                <i class="fas fa-edit text-blue-600"></i>
                                <?= e(t('edit_product')) ?>
                            </h1>
                            <p class="text-gray-500 mt-1">Editing product #<?= e((string)$productId) ?></p>
                        </div>
                        <a href="products.php" class="text-gray-500 hover:text-gray-700 font-medium flex items-center gap-2 transition-colors">
                            <i class="fas fa-arrow-left"></i> Back to Products
                        </a>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-xl mb-6 shadow-sm flex items-center gap-3">
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
                                                   value="<?= e($product['name_en']) ?>"
                                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                        </div>
                                        
                                        <!-- Kurdish Name -->
                                        <div>
                                            <label for="name_ku" class="block text-sm font-bold text-gray-700 mb-2">
                                                <?= e(t('product_name_ku')) ?> <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" id="name_ku" name="name_ku" required
                                                   value="<?= e($product['name_ku']) ?>"
                                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                        </div>
                                        
                                        <!-- English Description -->
                                        <div>
                                            <label for="description_en" class="block text-sm font-bold text-gray-700 mb-2">
                                                <?= e(t('product_description_en')) ?> <span class="text-red-500">*</span>
                                            </label>
                                            <textarea id="description_en" name="description_en" rows="4" required
                                                      class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white"><?= e($product['description_en']) ?></textarea>
                                        </div>
                                        
                                        <!-- Kurdish Description -->
                                        <div>
                                            <label for="description_ku" class="block text-sm font-bold text-gray-700 mb-2">
                                                <?= e(t('product_description_ku')) ?> <span class="text-red-500">*</span>
                                            </label>
                                            <textarea id="description_ku" name="description_ku" rows="4" required
                                                      class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white"><?= e($product['description_ku']) ?></textarea>
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
                                                       value="<?= e((string)$product['price']) ?>"
                                                       class="w-full pl-8 pr-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label for="stock_qty" class="block text-sm font-bold text-gray-700 mb-2">
                                                <?= e(t('stock_quantity')) ?> <span class="text-red-500">*</span>
                                            </label>
                                            <input type="number" id="stock_qty" name="stock_qty" min="0" required
                                                   value="<?= e((string)$product['stock_qty']) ?>"
                                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                        </div>
                                        
                                        <div class="md:col-span-2">
                                            <label for="sku" class="block text-sm font-bold text-gray-700 mb-2">
                                                <?= e(t('sku')) ?>
                                            </label>
                                            <input type="text" id="sku" name="sku"
                                                   value="<?= e($product['sku'] ?? '') ?>"
                                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
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
                                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?= e((string)$category['id']) ?>" 
                                                            <?= $product['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                                        <?= e($category['name_en']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="pt-4 border-t border-gray-100">
                                            <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                                <div class="relative flex items-center">
                                                    <input type="checkbox" name="is_featured" value="1" 
                                                           <?= $product['is_featured'] ? 'checked' : '' ?>
                                                           class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border border-gray-300 transition-all checked:border-blue-600 checked:bg-blue-600">
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
                                    
                                    <?php if ($product['image_url']): ?>
                                        <div class="mb-4">
                                            <img src="<?= e(url($product['image_url'])) ?>" alt="Current Image" class="w-full h-48 object-cover rounded-xl border border-gray-200">
                                            <p class="text-xs text-gray-500 mt-2 text-center">Current Main Image</p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="relative border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-blue-400 transition-colors bg-gray-50">
                                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp"
                                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                            <p class="text-sm font-medium text-gray-600">Change Image</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Gallery Images -->
                                <div class="bg-white rounded-2xl shadow-lg p-6">
                                    <h2 class="text-lg font-bold text-gray-800 mb-4"><?= e(t('gallery_images')) ?></h2>
                                    
                                    <?php if (!empty($productImages)): ?>
                                        <div class="grid grid-cols-3 gap-2 mb-4">
                                            <?php foreach ($productImages as $img): ?>
                                                <div class="relative group aspect-square">
                                                    <img src="<?= e(url($img['image_url'])) ?>" class="w-full h-full object-cover rounded-lg border border-gray-200">
                                                    <a href="delete_image.php?id=<?= e((string)$img['id']) ?>&product_id=<?= e((string)$productId) ?>" 
                                                       class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 transition-colors shadow-sm"
                                                       onclick="return confirm('<?= e(t('delete_image_confirm')) ?>')">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="relative border-2 border-dashed border-gray-300 rounded-xl p-4 text-center hover:border-blue-400 transition-colors bg-gray-50">
                                        <input type="file" id="gallery_images" name="gallery_images[]" multiple
                                               accept="image/jpeg,image/png,image/gif,image/webp"
                                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-images text-2xl text-gray-400 mb-2"></i>
                                            <p class="text-xs font-medium text-gray-600">Add Gallery Images</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Submit Button -->
                                <button type="submit" 
                                        class="w-full bg-blue-600 text-white py-4 px-6 rounded-xl hover:bg-blue-700 transition-all duration-300 font-bold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                                    <i class="fas fa-save me-2"></i><?= e(t('update_product')) ?>
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
