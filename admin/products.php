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
requirePermission('manage_products');

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
                <!-- Header Section -->
                <div class="bg-gradient-to-r from-green-600 via-green-700 to-teal-800 text-white rounded-3xl p-8 mb-8 shadow-xl relative overflow-hidden">
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <h1 class="text-3xl md:text-4xl font-luxury font-bold mb-2">
                                <i class="fas fa-box-open me-3"></i><?= e(t('products_management')) ?>
                            </h1>
                            <p class="text-green-200"><?= e(t('products_desc')) ?></p>
                        </div>
                        <a href="add_product.php" 
                           class="inline-flex items-center gap-2 bg-white text-green-700 px-6 py-3 rounded-full hover:bg-green-50 transition-all duration-300 font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                            <i class="fas fa-plus-circle text-xl"></i>
                            <?= e(t('add_new_product')) ?>
                        </a>
                    </div>
                </div>

                <!-- Products Table Card -->
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <div>
                            <h2 class="text-lg font-bold text-gray-800"><i class="fas fa-list me-2 text-green-600"></i><?= e(t('all_products')) ?></h2>
                            <p class="text-gray-500 text-sm"><?= e(t('total_products_count', ['count' => $totalProducts])) ?></p>
                        </div>
                        <div class="text-sm font-medium text-gray-500 bg-white px-3 py-1 rounded-lg border border-gray-200">
                            <?= e(t('page_x_of_y', ['page' => $page, 'total' => $totalPages])) ?>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('image')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('product')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('category')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('price')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('stock')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('actions')) ?></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($products as $product): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <?php if ($product['image_url']): ?>
                                                <img src="<?= e(url($product['image_url'])) ?>" 
                                                     alt="<?= e(getProductName($product)) ?>"
                                                     class="w-16 h-16 object-cover rounded-lg shadow-sm">
                                            <?php else: ?>
                                                <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-image text-2xl text-gray-300"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-bold text-gray-900"><?= e(getProductName($product)) ?></div>
                                            <?php if ($product['is_featured']): ?>
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded-full mt-1">
                                                    <i class="fas fa-star text-[10px]"></i><?= e(t('featured')) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full text-xs font-semibold">
                                                <i class="fas fa-tag text-[10px]"></i>
                                                <?= e(getCategoryName($product)) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-base font-bold text-green-600"><?= e(formatPrice((float)$product['price'])) ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold rounded-full <?= $product['stock_qty'] > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                                <?= $product['stock_qty'] > 0 ? e((string)$product['stock_qty'] . ' in stock') : 'Out of stock' ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex gap-2">
                                                <a href="edit_product.php?id=<?= e((string)$product['id']) ?>" 
                                                   class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors" title="<?= e(t('edit')) ?>">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="../product.php?id=<?= e((string)$product['id']) ?>" 
                                                   target="_blank"
                                                   class="p-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-colors" title="<?= e(t('view')) ?>">
                                                    <i class="fas fa-eye"></i>
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
                        <div class="px-6 py-4 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-gray-50/30">
                            <div class="text-xs text-gray-500 font-medium">
                                <?= e(t('showing_results', [
                                    'start' => ($page - 1) * $perPage + 1,
                                    'end' => min($page * $perPage, $totalProducts),
                                    'total' => $totalProducts
                                ])) ?>
                            </div>
                            <div class="flex gap-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= e((string)($page - 1)) ?>" 
                                       class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-green-600 transition-all font-semibold text-sm shadow-sm">
                                        <i class="fas fa-chevron-left rtl:rotate-180 me-1"></i><?= e(t('previous')) ?>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?= e((string)($page + 1)) ?>" 
                                       class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-green-600 transition-all font-semibold text-sm shadow-sm">
                                        <?= e(t('next')) ?><i class="fas fa-chevron-right rtl:rotate-180 ms-1"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
                        
            <!-- Footer -->
            <?php include __DIR__ . '/footer.php'; ?>
        </div>
    </div>
</body>
</html>
