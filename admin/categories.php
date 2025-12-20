<?php
declare(strict_types=1);

/**
 * Categories Management Page (Admin)
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';

requireAdmin();

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
                    $success = e('Category added successfully!');
                } elseif ($action === 'edit') {
                    $id = (int)sanitizeInput('id', 'POST');
                    $stmt = $pdo->prepare('UPDATE categories SET name_en = :name_en, name_ku = :name_ku, slug = :slug WHERE id = :id');
                    $stmt->execute(['name_en' => $nameEn, 'name_ku' => $nameKu, 'slug' => $slug, 'id' => $id]);
                    $success = e('Category updated successfully!');
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
            $error = e('Cannot delete category with products');
        } else {
            $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
            $stmt->execute(['id' => $deleteId]);
            $success = e('Category deleted successfully!');
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
    <title><?= e('Categories Management') ?> - Bloom & Vine</title>
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
        <h1 class="text-3xl md:text-4xl font-luxury font-bold text-luxury-primary mb-6 md:mb-8 tracking-wide"><?= e('Categories Management') ?></h1>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-sm mb-6">
                <?= e($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-sm mb-6">
                <?= e($success) ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
            <!-- Add Category Form -->
            <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8">
                <h2 class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e('Add New Category') ?></h2>
                <form method="POST" action="" class="space-y-5 md:space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div>
                        <label class="block text-sm font-medium text-luxury-text mb-2"><?= e('Name (English)') ?></label>
                        <input type="text" name="name_en" required
                               class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-luxury-text mb-2"><?= e('Name (Kurdish)') ?></label>
                        <input type="text" name="name_ku" required
                               class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-luxury-text mb-2"><?= e('Slug') ?></label>
                        <input type="text" name="slug" required
                               placeholder="e.g., wedding"
                               class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-luxury-accent text-white py-3 px-4 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                        <?= e('Add Category') ?>
                    </button>
                </form>
            </div>
            
            <!-- Categories List -->
            <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8">
                <h2 class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e('All Categories') ?></h2>
                <div class="space-y-3 md:space-y-4">
                    <?php foreach ($categories as $category): ?>
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-luxury-border pb-3 md:pb-4 last:border-0">
                            <div>
                                <p class="font-medium text-luxury-primary"><?= e($category['name_en']) ?> / <?= e($category['name_ku']) ?></p>
                                <p class="text-sm text-luxury-textLight"><?= e('Slug') ?>: <?= e($category['slug']) ?> | 
                                   <?= e((string)$category['product_count']) ?> <?= e('products') ?></p>
                            </div>
                            <div class="flex gap-3">
                                <a href="?edit=<?= e((string)$category['id']) ?>" 
                                   class="text-luxury-accent hover:text-luxury-primary transition-colors text-sm font-medium"><?= e('Edit') ?></a>
                                <?php if ($category['product_count'] == 0): ?>
                                    <a href="?delete=<?= e((string)$category['id']) ?>" 
                                       onclick="return confirm('<?= e('Delete this category?') ?>')"
                                       class="text-red-600 hover:text-red-800 transition-colors text-sm font-medium"><?= e('Delete') ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
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

