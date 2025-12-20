<?php
declare(strict_types=1);

/**
 * Home Page / Storefront
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';

$pdo = getDB();

// Get featured products
$stmt = $pdo->query('SELECT p.*, c.name_en as category_name_en, c.name_ku as category_name_ku 
                     FROM products p 
                     JOIN categories c ON p.category_id = c.id 
                     WHERE p.is_featured = 1 AND p.stock_qty > 0 
                     ORDER BY p.created_at DESC 
                     LIMIT 8');
$featuredProducts = $stmt->fetchAll();

// Get all categories for navigation
$stmt = $pdo->query('SELECT id, name_en, name_ku, slug FROM categories ORDER BY name_en');
$categories = $stmt->fetchAll();

$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloom & Vine - <?= e(t('nav_home')) ?></title>
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
                        <a href="notifications.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium relative">
                            <?= e('Notifications') ?>
                            <?php 
                            $unreadCount = getUnreadNotificationCount();
                            if ($unreadCount > 0): 
                            ?>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center"><?= e((string)$unreadCount) ?></span>
                            <?php endif; ?>
                        </a>
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

    <!-- Hero Section -->
    <section class="bg-white py-24 border-b border-luxury-border">
        <div class="container mx-auto text-center px-4 max-w-4xl">
            <h1 class="text-6xl font-luxury font-bold text-luxury-primary mb-6 leading-tight"><?= e(t('hero_title')) ?></h1>
            <p class="text-xl text-luxury-textLight mb-10 font-light tracking-wide"><?= e(t('hero_subtitle')) ?></p>
            <a href="shop.php" 
               class="inline-block bg-luxury-primary text-white px-10 py-4 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium tracking-wide shadow-luxury hover:shadow-luxuryHover">
                <?= e(t('hero_cta')) ?>
            </a>
        </div>
    </section>

    <!-- Flash Messages -->
    <?php
    $flash = getFlashMessage();
    if ($flash):
    ?>
        <div class="container mx-auto px-4 mt-4">
            <div class="bg-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-100 border border-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-400 text-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-700 px-4 py-3 rounded">
                <?= e($flash['message']) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Featured Products -->
    <section class="container mx-auto px-6 py-20">
        <h2 class="text-4xl font-luxury font-bold text-luxury-primary mb-12 text-center tracking-wide"><?= e('Featured Products') ?></h2>
        
        <?php if (empty($featuredProducts)): ?>
            <p class="text-center text-luxury-textLight"><?= e('No featured products available.') ?></p>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                <?php foreach ($featuredProducts as $product): ?>
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
    </section>

    <!-- Footer -->
    <footer class="bg-luxury-primary text-white py-12 mt-20 border-t border-luxury-border">
        <div class="container mx-auto px-6 text-center">
            <p class="text-luxury-accentLight font-light tracking-wide">&copy; <?= e(date('Y')) ?> Bloom & Vine. <?= e('All rights reserved.') ?></p>
        </div>
    </footer>
</body>
</html>

