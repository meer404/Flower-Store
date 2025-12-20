<?php
declare(strict_types=1);

/**
 * Edit Product Page (Admin)
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';

requireAdmin();

$pdo = getDB();
$productId = (int)sanitizeInput('id', 'GET', '0');
$error = '';
$success = '';

if ($productId <= 0) {
    redirect('dashboard.php', e('Invalid product'), 'error');
}

// Get product
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    redirect('dashboard.php', e('Product not found'), 'error');
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
                
                redirect('dashboard.php', t('product_added'), 'success');
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
    <title><?= e('Edit Product') ?> - Bloom & Vine</title>
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
                    <a href="products.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e('Products') ?></a>
                    <a href="../index.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_home')) ?></a>
                    <a href="../logout.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_logout')) ?></a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
        <h1 class="text-3xl md:text-4xl font-luxury font-bold text-luxury-primary mb-6 md:mb-8 tracking-wide"><?= e('Edit Product') ?></h1>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-sm mb-6">
                <?= e($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8">
            <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name_en" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e(t('product_name_en')) ?>
                        </label>
                        <input type="text" id="name_en" name="name_en" required
                               value="<?= e($product['name_en']) ?>"
                               class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                    </div>
                    
                    <div>
                        <label for="name_ku" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e(t('product_name_ku')) ?>
                        </label>
                        <input type="text" id="name_ku" name="name_ku" required
                               value="<?= e($product['name_ku']) ?>"
                               class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="description_en" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e(t('product_description_en')) ?>
                        </label>
                        <textarea id="description_en" name="description_en" rows="4" required
                                  class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent"><?= e($product['description_en']) ?></textarea>
                    </div>
                    
                    <div>
                        <label for="description_ku" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e(t('product_description_ku')) ?>
                        </label>
                        <textarea id="description_ku" name="description_ku" rows="4" required
                                  class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent"><?= e($product['description_ku']) ?></textarea>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e(t('category')) ?>
                        </label>
                        <select id="category_id" name="category_id" required
                                class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= e((string)$category['id']) ?>" 
                                        <?= $product['category_id'] == $category['id'] ? 'selected' : '' ?>>
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
                               value="<?= e((string)$product['price']) ?>"
                               class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                    </div>
                    
                    <div>
                        <label for="stock_qty" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e(t('stock_quantity')) ?>
                        </label>
                        <input type="number" id="stock_qty" name="stock_qty" min="0" required
                               value="<?= e((string)$product['stock_qty']) ?>"
                               class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                    </div>
                    
                    <div>
                        <label for="sku" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e('SKU') ?>
                        </label>
                        <input type="text" id="sku" name="sku"
                               value="<?= e($product['sku'] ?? '') ?>"
                               class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                    </div>
                </div>
                
                <div>
                    <label for="image" class="block text-sm font-medium text-luxury-text mb-2">
                        <?= e(t('product_image')) ?> (<?= e('Leave empty to keep current') ?>)
                    </label>
                    <?php if ($product['image_url']): ?>
                        <img src="<?= e($product['image_url']) ?>" alt="Current" class="w-32 h-32 object-cover rounded-sm mb-3 border border-luxury-border">
                    <?php endif; ?>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp"
                           class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                </div>
                
                <div>
                    <label for="gallery_images" class="block text-sm font-medium text-luxury-text mb-2">
                        <?= e('Gallery Images') ?>
                    </label>
                    <?php if (!empty($productImages)): ?>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                            <?php foreach ($productImages as $img): ?>
                                <div class="relative group">
                                    <img src="<?= e($img['image_url']) ?>" class="w-full h-20 md:h-24 object-cover rounded-sm border border-luxury-border">
                                    <a href="delete_image.php?id=<?= e((string)$img['id']) ?>&product_id=<?= e((string)$productId) ?>" 
                                       class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 transition-colors opacity-0 group-hover:opacity-100"
                                       onclick="return confirm('<?= e('Delete this image?') ?>')">×</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="gallery_images" name="gallery_images[]" multiple
                           accept="image/jpeg,image/png,image/gif,image/webp"
                           class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                </div>
                
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_featured" value="1" 
                               <?= $product['is_featured'] ? 'checked' : '' ?>
                               class="mr-2 h-4 w-4 text-luxury-accent focus:ring-luxury-accent border-luxury-border rounded-sm">
                        <span class="text-sm font-medium text-luxury-text"><?= e(t('featured')) ?></span>
                    </label>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4">
                    <button type="submit" 
                            class="flex-1 bg-luxury-accent text-white py-3 px-4 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                        <?= e('Update Product') ?>
                    </button>
                    <a href="products.php" 
                       class="flex-1 border border-luxury-border text-luxury-text py-3 px-4 rounded-sm hover:bg-luxury-border transition-all duration-300 text-center font-medium">
                        <?= e('Cancel') ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

