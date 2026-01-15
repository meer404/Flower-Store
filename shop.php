<?php
declare(strict_types=1);

/**
 * Shop Page with Filters and Search
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';
require_once __DIR__ . '/src/components.php';

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
    <?php include __DIR__ . '/src/header.php'; ?>

    <!-- Page Header -->
    <div class="bg-gradient-to-r from-luxury-primary to-gray-900 text-white py-16">
        <div class="container mx-auto px-6">
            <h1 class="text-5xl font-luxury font-bold mb-4"><?= e(t('nav_shop')) ?></h1>
            <p class="text-xl text-gray-300"><?= e(t('shop_subtitle')) ?></p>
        </div>
    </div>

    <div class="container mx-auto px-6 py-12">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Filters -->
            <aside class="lg:w-80">
                <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl p-6 sticky top-24">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-luxury-accent rounded-xl flex items-center justify-center">
                            <i class="fas fa-filter text-white text-xl"></i>
                        </div>
                        <h2 class="text-2xl font-luxury font-bold text-luxury-primary"><?= e(t('filters')) ?></h2>
                    </div>
                    
                    <form method="GET" action="shop.php" id="filterForm">
                        <input type="hidden" name="lang" value="<?= e($lang) ?>">
                        
                        <!-- Search -->
                        <div class="mb-6">
                            <label for="search" class="block text-sm font-bold text-luxury-primary mb-3 uppercase tracking-wider">
                                <i class="fas fa-search me-2"></i><?= e(t('search')) ?>
                            </label>
                            <div class="relative">
                                <input type="text" id="search" name="search" 
                                       value="<?= e($search) ?>"
                                       placeholder="<?= e(t('search_placeholder')) ?>"
                                       class="w-full ps-12 pe-4 py-3.5 border-2 border-luxury-border rounded-xl focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent transition-all">
                                <i class="fas fa-search absolute start-4 top-1/2 transform -translate-y-1/2 text-luxury-textLight"></i>
                            </div>
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="mb-6">
                            <label for="category" class="block text-sm font-bold text-luxury-primary mb-3 uppercase tracking-wider">
                                <i class="fas fa-th-large me-2"></i><?= e(t('category')) ?>
                            </label>
                            <select id="category" name="category" 
                                    onchange="document.getElementById('filterForm').submit();"
                                    class="w-full px-4 py-3.5 border-2 border-luxury-border rounded-xl focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent transition-all">
                                <option value="0"><?= e(t('all_categories')) ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= e((string)$category['id']) ?>" 
                                            <?= $categoryId === $category['id'] ? 'selected' : '' ?>>
                                        <?= e(getCategoryName($category)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-luxury-accent to-yellow-500 text-white py-4 px-6 rounded-xl hover:from-yellow-500 hover:to-luxury-accent transition-all duration-300 font-bold shadow-xl hover:shadow-2xl transform hover:-translate-y-0.5">
                            <i class="fas fa-search me-2"></i><?= e(t('search')) ?>
                        </button>
                        
                        <?php if ($search || $categoryId > 0): ?>
                            <a href="shop.php?lang=<?= e($lang) ?>" 
                               class="block mt-4 text-center text-luxury-accent hover:text-luxury-primary transition-colors font-semibold py-2">
                                <i class="fas fa-times me-2"></i><?= e(t('clear_filters')) ?>
                            </a>
                        <?php endif; ?>
                    </form>
                    
                    <!-- Active Filters Display -->
                    <?php if ($search || $categoryId > 0): ?>
                        <div class="mt-6 pt-6 border-t-2 border-luxury-border">
                            <p class="text-sm font-bold text-luxury-primary mb-3 uppercase"><?= e(t('active_filters')) ?></p>
                            <div class="flex flex-wrap gap-2">
                                <?php if ($search): ?>
                                    <span class="bg-luxury-accent/10 text-luxury-accent px-3 py-1 rounded-full text-sm font-semibold">
                                        Search: "<?= e($search) ?>"
                                    </span>
                                <?php endif; ?>
                                <?php if ($categoryId > 0):
                                    $selectedCat = array_filter($categories, fn($c) => $c['id'] === $categoryId);
                                    if (!empty($selectedCat)):
                                        $catName = getCategoryName(reset($selectedCat));
                                ?>
                                    <span class="bg-luxury-accent/10 text-luxury-accent px-3 py-1 rounded-full text-sm font-semibold">
                                        <?= e($catName) ?>
                                    </span>
                                <?php endif; endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>

            <!-- Products Grid -->
            <main class="flex-1">
                <?php if (!empty($products)): ?>
                    <div class="flex justify-between items-center mb-8">
                        <div class="text-luxury-textLight">
                            <i class="fas fa-check-circle text-green-500 me-2"></i>
                            <?= t('found_products', ['count' => count($products)]) ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($products)): ?>
                    <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl p-16 text-center">
                        <div class="w-32 h-32 bg-luxury-border rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-search text-5xl text-luxury-textLight"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-luxury-primary mb-3"><?= e(t('no_products_found')) ?></h3>
                        <p class="text-luxury-textLight mb-6"><?= e(t('no_products_subtitle')) ?></p>
                        <a href="shop.php?lang=<?= e($lang) ?>" 
                           class="inline-flex items-center gap-2 bg-luxury-accent text-white px-8 py-3 rounded-full hover:bg-opacity-90 transition-all font-semibold">
                            <i class="fas fa-redo"></i>
                            <?= e(t('clear_filters')) ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-8">
                        <?php foreach ($products as $product): ?>
                            <?= productCard($product) ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <?= modernFooter() ?>
    
    <!-- Wishlist Toggle Script -->
    <script>
        function toggleWishlist(productId) {
            fetch('wishlist_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=toggle&product_id=' + productId + '&csrf_token=<?= e(generateCSRFToken()) ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>

