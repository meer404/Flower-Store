<?php
declare(strict_types=1);

/**
 * Shop Page with Filters and Search
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';

$pdo = getDB();

// Get search query and category filter
$search = sanitizeInput('search', 'GET', '');
$categoryId = (int)sanitizeInput('category', 'GET', '0');

// Get all categories for filter
$stmt = $pdo->query('SELECT id, name_en, name_ku, slug FROM categories ORDER BY name_en');
$categories = $stmt->fetchAll();

// Build product query
$whereConditions = ['p.stock_qty > 0'];
$params = [];

if (!empty($search)) {
    $whereConditions[] = '(p.name_en LIKE :search OR p.name_ku LIKE :search OR p.description_en LIKE :search OR p.description_ku LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($categoryId > 0) {
    $whereConditions[] = 'p.category_id = :category_id';
    $params['category_id'] = $categoryId;
}

$whereClause = implode(' AND ', $whereConditions);

$sql = "SELECT p.*, c.name_en as category_name_en, c.name_ku as category_name_ku 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE {$whereClause}
        ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('nav_shop')) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <!-- Navbar -->
    <nav class="bg-white border-b border-luxury-border shadow-sm">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <a href="index.php" class="text-2xl font-luxury font-bold text-luxury-primary tracking-wide">Bloom & Vine</a>
                <div class="flex items-center space-x-8" style="direction: ltr;">
                    <a href="index.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_home')) ?></a>
                    <a href="shop.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_shop')) ?></a>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <a href="admin/dashboard.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_admin')) ?></a>
                        <?php endif; ?>
                        <a href="account.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e('Account') ?></a>
                        <a href="cart.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium">
                            <?= e(t('nav_cart')) ?> <span class="bg-luxury-accent text-white px-2 py-0.5 rounded-full text-xs"><?= e((string)getCartCount()) ?></span>
                        </a>
                        <a href="logout.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_logout')) ?></a>
                    <?php else: ?>
                        <a href="login.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_login')) ?></a>
                        <a href="register.php" class="bg-luxury-primary text-white px-6 py-2 rounded-sm hover:bg-opacity-90 transition-all font-medium"><?= e(t('nav_register')) ?></a>
                    <?php endif; ?>
                    <a href="?lang=<?= $lang === 'en' ? 'ku' : 'en' ?>" class="text-luxury-accent hover:text-luxury-primary font-semibold border border-luxury-accent px-3 py-1 rounded-sm">
                        <?= $lang === 'en' ? 'KU' : 'EN' ?>
                    </a>
                </div>
            </div>
    </nav>

    <div class="container mx-auto px-6 py-12">
        <div class="flex flex-col md:flex-row gap-8">
            <!-- Sidebar Filters -->
            <aside class="w-full md:w-64">
                <div class="bg-white border border-luxury-border shadow-luxury p-6">
                    <h2 class="text-xl font-luxury font-bold text-luxury-primary mb-6"><?= e(t('category')) ?></h2>
                    
                    <form method="GET" action="shop.php" id="filterForm">
                        <input type="hidden" name="lang" value="<?= e($lang) ?>">
                        
                        <!-- Search -->
                        <div class="mb-6">
                            <label for="search" class="block text-sm font-medium text-luxury-text mb-2">
                                <?= e(t('search')) ?>
                            </label>
                            <input type="text" id="search" name="search" 
                                   value="<?= e($search) ?>"
                                   placeholder="<?= e(t('search')) ?>..."
                                   class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="mb-6">
                            <label for="category" class="block text-sm font-medium text-luxury-text mb-2">
                                <?= e(t('category')) ?>
                            </label>
                            <select id="category" name="category" 
                                    onchange="document.getElementById('filterForm').submit();"
                                    class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                                <option value="0"><?= e('All Categories') ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= e((string)$category['id']) ?>" 
                                            <?= $categoryId === $category['id'] ? 'selected' : '' ?>>
                                        <?= e(getCategoryName($category)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-luxury-primary text-white py-2.5 px-4 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                            <?= e(t('search')) ?>
                        </button>
                        
                        <?php if ($search || $categoryId > 0): ?>
                            <a href="shop.php?lang=<?= e($lang) ?>" 
                               class="block mt-3 text-center text-luxury-accent hover:text-luxury-primary transition-colors font-medium">
                                <?= e('Clear Filters') ?>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </aside>

            <!-- Products Grid -->
            <main class="flex-1">
                <h1 class="text-4xl font-luxury font-bold text-luxury-primary mb-8 tracking-wide"><?= e(t('nav_shop')) ?></h1>
                
                <?php if (empty($products)): ?>
                    <div class="bg-white border border-luxury-border shadow-luxury p-12 text-center">
                        <p class="text-luxury-textLight"><?= e('No products found.') ?></p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                        <?php foreach ($products as $product): ?>
                            <div class="bg-white border border-luxury-border shadow-luxury overflow-hidden hover:shadow-luxuryHover transition-all duration-300 group">
                                <?php if ($product['image_url']): ?>
                                    <div class="overflow-hidden">
                                        <img src="<?= e($product['image_url']) ?>" 
                                             alt="<?= e(getProductName($product)) ?>"
                                             class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-500">
                                    </div>
                                <?php else: ?>
                                    <div class="w-full h-64 bg-luxury-border flex items-center justify-center">
                                        <span class="text-luxury-textLight"><?= e('No Image') ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="p-6">
                                    <h3 class="text-lg font-semibold text-luxury-primary mb-2 font-luxury">
                                        <a href="product.php?id=<?= e((string)$product['id']) ?>" class="hover:text-luxury-accent transition-colors">
                                            <?= e(getProductName($product)) ?>
                                        </a>
                                    </h3>
                                    <p class="text-xs text-luxury-textLight mb-2 uppercase tracking-wide"><?= e(getCategoryName($product)) ?></p>
                                    <p class="text-sm text-luxury-textLight mb-3 line-clamp-2 leading-relaxed"><?= e(substr(getProductDescription($product), 0, 100)) ?>...</p>
                                    <p class="text-2xl font-bold text-luxury-accent mb-6 font-luxury"><?= e(formatPrice((float)$product['price'])) ?></p>
                                    
                                    <div class="space-y-3">
                                        <a href="product.php?id=<?= e((string)$product['id']) ?>" 
                                           class="block w-full border border-luxury-primary text-luxury-primary py-2.5 px-4 rounded-sm hover:bg-luxury-primary hover:text-white transition-all duration-300 text-center font-medium">
                                            <?= e(t('view_details')) ?>
                                        </a>
                                        <form method="POST" action="cart_action.php" class="inline">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="product_id" value="<?= e((string)$product['id']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                            <?php if (isLoggedIn()): ?>
                                                <button type="submit" 
                                                        class="w-full bg-luxury-accent text-white py-2.5 px-4 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                                                    <?= e(t('add_to_cart')) ?>
                                                </button>
                                            <?php else: ?>
                                                <a href="login.php" 
                                                   class="block w-full bg-luxury-accent text-white py-2.5 px-4 rounded-sm hover:bg-opacity-90 transition-all duration-300 text-center font-medium shadow-md">
                                                    <?= e(t('add_to_cart')) ?>
                                                </a>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
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

