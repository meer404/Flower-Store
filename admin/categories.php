<?php
declare(strict_types=1);

/**
 * Categories Management Page (Admin)
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';
require_once __DIR__ . '/../src/components.php';

requireAdmin();
requirePermission('manage_categories');

$pdo = getDB();
$error = '';
$success = '';

// Handle add/edit category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = sanitizeInput('csrf_token', 'POST');
    $action = sanitizeInput('action', 'POST');
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = t('error');
    } else {
        $nameEn = sanitizeInput('name_en', 'POST');
        $nameKu = sanitizeInput('name_ku', 'POST');
        $slug = sanitizeInput('slug', 'POST');
        
        if (empty($nameEn) || empty($nameKu) || empty($slug)) {
            $error = t('error');
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare('INSERT INTO categories (name_en, name_ku, slug) VALUES (:name_en, :name_ku, :slug)');
                    $stmt->execute(['name_en' => $nameEn, 'name_ku' => $nameKu, 'slug' => $slug]);
                    $success = e(t('category_added_success'));
                } elseif ($action === 'edit') {
                    $id = (int)sanitizeInput('id', 'POST');
                    $stmt = $pdo->prepare('UPDATE categories SET name_en = :name_en, name_ku = :name_ku, slug = :slug WHERE id = :id');
                    $stmt->execute(['name_en' => $nameEn, 'name_ku' => $nameKu, 'slug' => $slug, 'id' => $id]);
                    $success = e(t('category_updated_success'));
                }
            } catch (PDOException $e) {
                error_log('Category error: ' . $e->getMessage());
                $error = t('error');
            }
        }
    }
}

// Handle delete
$deleteId = (int)sanitizeInput('delete', 'GET', '0');
if ($deleteId > 0) {
    try {
        // Check if category has products
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM products WHERE category_id = :id');
        $stmt->execute(['id' => $deleteId]);
        $productCount = (int)$stmt->fetch()['count'];
        
        if ($productCount > 0) {
            $error = e(t('category_has_products_error'));
        } else {
            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
            $stmt->execute(['id' => $deleteId]);
            $success = e(t('category_deleted_success'));
        }
    } catch (PDOException $e) {
        error_log('Category delete error: ' . $e->getMessage());
        $error = t('error');
    }
}

// Get all categories
$stmt = $pdo->query('SELECT c.*, COUNT(p.id) as product_count 
                     FROM categories c 
                     LEFT JOIN products p ON c.id = p.category_id 
                     GROUP BY c.id 
                     ORDER BY c.name_en');
$categories = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('categories_management')) ?> - Bloom & Vine</title>
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
                <!-- Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-luxury font-bold text-gray-800 flex items-center gap-3">
                            <i class="fas fa-tags text-orange-600"></i>
                            <?= e(t('categories_management')) ?>
                        </h1>
                        <p class="text-gray-500 mt-1">Manage product categories and organization</p>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3 shadow-sm">
                        <i class="fas fa-exclamation-circle text-xl"></i>
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3 shadow-sm">
                        <i class="fas fa-check-circle text-xl"></i>
                        <?= e($success) ?>
                    </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
                    <!-- Add Category Form -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24">
                            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                                <i class="fas fa-plus-circle text-orange-600"></i>
                                <?= e(t('add_new_category')) ?>
                            </h2>
                            <form method="POST" action="" class="space-y-5">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="action" value="add">
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('name_en')) ?></label>
                                    <input type="text" name="name_en" required
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('name_ku')) ?></label>
                                    <input type="text" name="name_ku" required
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('slug')) ?></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-400">/</span>
                                        </div>
                                        <input type="text" name="slug" required
                                               placeholder="e.g., weddings"
                                               class="w-full pl-8 pr-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">URL-friendly version of the name</p>
                                </div>
                                
                                <button type="submit" 
                                        class="w-full bg-orange-600 text-white py-3 px-4 rounded-xl hover:bg-orange-700 transition-all duration-300 font-bold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                    <i class="fas fa-save me-2"></i><?= e(t('add_category_btn')) ?>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Categories List -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-list text-gray-500"></i>
                                    <?= e(t('all_categories')) ?>
                                </h2>
                            </div>
                            <div class="divide-y divide-gray-100">
                                <?php foreach ($categories as $category): ?>
                                    <div class="p-6 hover:bg-orange-50/30 transition-colors group">
                                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-1">
                                                    <h3 class="font-bold text-lg text-gray-800"><?= e($category['name_en']) ?></h3>
                                                    <span class="text-gray-300">|</span>
                                                    <span class="font-medium text-gray-600"><?= e($category['name_ku']) ?></span>
                                                </div>
                                                <div class="flex items-center gap-4 text-sm text-gray-500">
                                                    <span class="bg-gray-100 px-2 py-0.5 rounded text-xs font-mono text-gray-600">/<?= e($category['slug']) ?></span>
                                                    <span class="flex items-center gap-1">
                                                        <i class="fas fa-box text-xs"></i>
                                                        <?= e((string)$category['product_count']) ?> <?= e(t('products')) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="flex gap-2">
                                                <a href="?edit=<?= e((string)$category['id']) ?>" 
                                                   class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="<?= e(t('edit')) ?>">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($category['product_count'] == 0): ?>
                                                    <a href="?delete=<?= e((string)$category['id']) ?>" 
                                                       onclick="return confirm('<?= e(t('delete_category_confirm')) ?>')"
                                                       class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="<?= e(t('delete')) ?>">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="p-2 text-gray-300 cursor-not-allowed" title="Cannot delete category with products">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
                        
            <!-- Footer -->
            <?php include __DIR__ . '/footer.php'; ?>
        </div>
    </div>
</body>
</html>
