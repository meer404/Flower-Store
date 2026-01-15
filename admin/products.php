<?php
declare(strict_types=1);

/**
 * Products Management Page (Admin)
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';
require_once __DIR__ . '/../src/components.php';

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
    <title><?= e(t('products_management')) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/../src/header.php'; ?>

    <!-- Admin Header -->
    <div class="bg-gradient-to-r from-green-600 via-green-700 to-teal-800 text-white py-16">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <h1 class="text-5xl font-luxury font-bold mb-4">
                        <i class="fas fa-box-open me-4"></i><?= e(t('products_management')) ?>
                    </h1>
                    <p class="text-xl text-green-200"><?= e(t('products_desc')) ?></p>
                </div>
                <a href="add_product.php" 
                   class="inline-flex items-center gap-3 bg-white text-green-700 px-8 py-4 rounded-full hover:bg-green-50 transition-all duration-300 font-bold shadow-xl hover:shadow-2xl transform hover:-translate-y-1">
                    <i class="fas fa-plus-circle text-2xl"></i>
                    <?= e(t('add_new_product')) ?>
                </a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
        
        <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-teal-600 text-white px-6 py-4 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold"><i class="fas fa-list me-2"></i><?= e(t('all_products')) ?></h2>
                    <p class="text-green-200 text-sm"><?= e(t('total_products_count', ['count' => $totalProducts])) ?></p>
                </div>
                <div class="text-end">
                    <p class="text-sm text-green-200"><?= e(t('page_x_of_y', ['page' => $page, 'total' => $totalPages])) ?></p>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-green-50">
                        <tr>
                            <th class="px-6 py-4 text-start text-xs font-bold text-green-900 uppercase tracking-wider"><?= e(t('image')) ?></th>
                            <th class="px-6 py-4 text-start text-xs font-bold text-green-900 uppercase tracking-wider"><?= e(t('product')) ?></th>
                            <th class="px-6 py-4 text-start text-xs font-bold text-green-900 uppercase tracking-wider"><?= e(t('category')) ?></th>
                            <th class="px-6 py-4 text-start text-xs font-bold text-green-900 uppercase tracking-wider"><?= e(t('price')) ?></th>
                            <th class="px-6 py-4 text-start text-xs font-bold text-green-900 uppercase tracking-wider"><?= e(t('stock')) ?></th>
                            <th class="px-6 py-4 text-start text-xs font-bold text-green-900 uppercase tracking-wider"><?= e(t('actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-luxury-border">
                        <?php foreach ($products as $product): ?>
                            <tr class="hover:bg-green-50 transition-colors">
                                <td class="px-6 py-4">
                                    <?php if ($product['image_url']): ?>
                                        <img src="<?= e($product['image_url']) ?>" 
                                             alt="<?= e(getProductName($product)) ?>"
                                             class="w-20 h-20 object-cover rounded-xl shadow-md">
                                    <?php else: ?>
                                        <div class="w-20 h-20 bg-green-100 rounded-xl flex items-center justify-center">
                                            <i class="fas fa-image text-3xl text-green-300"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-base font-bold text-luxury-primary"><?= e(getProductName($product)) ?></div>
                                    <?php if ($product['is_featured']): ?>
                                        <span class="inline-flex items-center gap-1 text-xs font-bold text-yellow-600 bg-yellow-100 px-2 py-1 rounded-full mt-1">
                                            <i class="fas fa-star"></i><?= e(t('featured')) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                                        <i class="fas fa-tag"></i>
                                        <?= e(getCategoryName($product)) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-lg font-bold text-green-600"><?= e(formatPrice((float)$product['price'])) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 px-3 py-2 text-sm font-bold rounded-xl <?= $product['stock_qty'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <i class="fas fa-<?= $product['stock_qty'] > 0 ? 'check' : 'times' ?>-circle"></i>
                                        <?= e(t('units_suffix', ['count' => (string)$product['stock_qty']])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <a href="edit_product.php?id=<?= e((string)$product['id']) ?>" 
                                           class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl transition-all font-semibold shadow-md">
                                            <i class="fas fa-edit"></i><?= e(t('edit')) ?>
                                        </a>
                                        <a href="../product.php?id=<?= e((string)$product['id']) ?>" 
                                           target="_blank"
                                           class="inline-flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl transition-all font-semibold shadow-md">
                                            <i class="fas fa-eye"></i><?= e(t('view')) ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-6 bg-green-50 border-t-2 border-luxury-border flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-sm font-semibold text-green-800">
                        <i class="fas fa-info-circle me-2"></i>
                        <?= e(t('showing_results', [
                            'start' => ($page - 1) * $perPage + 1,
                            'end' => min($page * $perPage, $totalProducts),
                            'total' => $totalProducts
                        ])) ?>
                    </div>
                    <div class="flex gap-3">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= e((string)($page - 1)) ?>" 
                               class="inline-flex items-center gap-2 px-6 py-3 bg-white border-2 border-green-600 text-green-600 rounded-xl hover:bg-green-600 hover:text-white transition-all font-bold shadow-md">
                                <i class="fas fa-chevron-left rtl:rotate-180"></i><?= e(t('previous')) ?>
                            </a>
                        <?php endif; ?>
                        <span class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-xl font-bold shadow-md">
                            <?= e(t('page_x_of_y', ['page' => $page, 'total' => $totalPages])) ?>
                        </span>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= e((string)($page + 1)) ?>" 
                               class="inline-flex items-center gap-2 px-6 py-3 bg-white border-2 border-green-600 text-green-600 rounded-xl hover:bg-green-600 hover:text-white transition-all font-bold shadow-md">
                                <?= e(t('next')) ?><i class="fas fa-chevron-right rtl:rotate-180"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?= modernFooter() ?>
</body>
</html>

