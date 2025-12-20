<?php
declare(strict_types=1);

/**
 * Products Management Page (Admin)
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';

requireAdmin();

$pdo = getDB();

// Pagination
$page = max(1, (int)sanitizeInput('page', 'GET', '1'));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total count
$stmt = $pdo->query('SELECT COUNT(*) as total FROM products');
$totalProducts = (int)$stmt->fetch()['total'];
$totalPages = (int)ceil($totalProducts / $perPage);

// Get products
$stmt = $pdo->prepare('SELECT p.*, c.name_en as category_name_en, c.name_ku as category_name_ku 
                       FROM products p 
                       JOIN categories c ON p.category_id = c.id 
                       ORDER BY p.created_at DESC 
                       LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('Products Management') ?> - Bloom & Vine</title>
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
                    <a href="add_product.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e('Add Product') ?></a>
                    <a href="categories.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium hidden md:inline"><?= e('Categories') ?></a>
                    <a href="../index.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_home')) ?></a>
                    <a href="../logout.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_logout')) ?></a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 md:mb-8">
            <h1 class="text-3xl md:text-4xl font-luxury font-bold text-luxury-primary tracking-wide"><?= e('Products Management') ?></h1>
            <a href="add_product.php" 
               class="bg-luxury-accent text-white px-6 py-3 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                <?= e('Add New Product') ?>
            </a>
        </div>
        
        <div class="bg-white border border-luxury-border shadow-luxury overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= e('Image') ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= e('Product') ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= e('Category') ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= e('Price') ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= e('Stock') ?></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?= e('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <?php if ($product['image_url']): ?>
                                        <img src="<?= e($product['image_url']) ?>" 
                                             alt="<?= e(getProductName($product)) ?>"
                                             class="w-16 h-16 object-cover rounded">
                                    <?php else: ?>
                                        <div class="w-16 h-16 bg-gray-200 rounded"></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-primary"><?= e(getProductName($product)) ?></div>
                                    <?php if ($product['is_featured']): ?>
                                        <span class="text-xs text-yellow-600">★ <?= e('Featured') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?= e(getCategoryName($product)) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?= e(formatPrice((float)$product['price'])) ?></td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-2 py-1 text-xs rounded <?= $product['stock_qty'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= e((string)$product['stock_qty']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="edit_product.php?id=<?= e((string)$product['id']) ?>" 
                                       class="text-primary hover:underline mr-3"><?= e('Edit') ?></a>
                                    <a href="../product.php?id=<?= e((string)$product['id']) ?>" 
                                       target="_blank"
                                       class="text-blue-600 hover:underline"><?= e('View') ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        <?= e('Showing') ?> <?= e((string)(($page - 1) * $perPage + 1)) ?>-<?= e((string)min($page * $perPage, $totalProducts)) ?> 
                        <?= e('of') ?> <?= e((string)$totalProducts) ?>
                    </div>
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= e((string)($page - 1)) ?>" 
                               class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                <?= e('Previous') ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= e((string)($page + 1)) ?>" 
                               class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                                <?= e('Next') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

