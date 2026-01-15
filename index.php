<?php
declare(strict_types=1);

/**
 * Home Page / Storefront
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';
require_once __DIR__ . '/src/components.php';

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
    <?php include __DIR__ . '/src/header.php'; ?>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-luxury-accentLight via-white to-luxury-accentLight overflow-hidden">
        <!-- Decorative elements -->
        <div class="absolute top-0 start-0 w-96 h-96 bg-luxury-accent/10 rounded-full blur-3xl ltr:-translate-x-1/2 rtl:translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 end-0 w-96 h-96 bg-luxury-primary/10 rounded-full blur-3xl ltr:translate-x-1/2 rtl:-translate-x-1/2 translate-y-1/2"></div>
        
        <div class="relative container mx-auto px-6 py-24 md:py-32">
            <div class="max-w-5xl mx-auto text-center">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 bg-white/80 backdrop-blur-sm px-6 py-3 rounded-full shadow-lg mb-8 animate-fade-in">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="text-luxury-primary font-semibold text-sm"><?= e(t('fresh_flowers_daily')) ?></span>
                </div>
                
                <!-- Main Heading -->
                <h1 class="text-5xl md:text-7xl lg:text-8xl font-luxury font-bold mb-6 leading-tight animate-slide-up">
                    <span class="gradient-text"><?= e(t('hero_title')) ?></span>
                </h1>
                
                <!-- Subtitle -->
                <p class="text-xl md:text-2xl text-luxury-textLight mb-12 font-light leading-relaxed max-w-3xl mx-auto animate-slide-up" style="animation-delay: 0.1s">
                    <?= e(t('hero_subtitle')) ?>
                </p>
                
                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center animate-slide-up" style="animation-delay: 0.2s">
                    <a href="shop.php" 
                       class="group inline-flex items-center gap-3 bg-gradient-to-r from-luxury-accent to-yellow-500 text-white px-10 py-5 rounded-full hover:from-yellow-500 hover:to-luxury-accent transition-all duration-300 font-semibold text-lg shadow-xl hover:shadow-2xl transform hover:-translate-y-1">
                        <i class="fas fa-store"></i>
                        <?= e(t('hero_cta')) ?>
                        <i class="fas fa-arrow-right group-hover:translate-x-1 rtl:group-hover:-translate-x-1 transition-transform"></i>
                    </a>
                    <a href="#featured" 
                       class="inline-flex items-center gap-3 bg-white text-luxury-primary px-10 py-5 rounded-full hover:bg-luxury-primary hover:text-white transition-all duration-300 font-semibold text-lg shadow-xl border-2 border-luxury-primary">
                        <i class="fas fa-sparkles"></i>
                        <?= e(t('view_featured')) ?>
                    </a>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mt-20 animate-fade-in" style="animation-delay: 0.3s">
                    <?php
                    $totalProducts = $pdo->query('SELECT COUNT(*) FROM products WHERE stock_qty > 0')->fetchColumn();
                    $totalCustomers = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "customer"')->fetchColumn();
                    ?>
                    <div class="text-center">
                        <div class="text-4xl md:text-5xl font-bold text-luxury-accent mb-2"><?= number_format($totalProducts) ?>+</div>
                        <div class="text-luxury-textLight font-medium"><?= e(t('stats_products')) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl md:text-5xl font-bold text-luxury-accent mb-2"><?= number_format($totalCustomers) ?>+</div>
                        <div class="text-luxury-textLight font-medium"><?= e(t('stats_customers')) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl md:text-5xl font-bold text-luxury-accent mb-2">100%</div>
                        <div class="text-luxury-textLight font-medium"><?= e(t('stats_quality')) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl md:text-5xl font-bold text-luxury-accent mb-2">24/7</div>
                        <div class="text-luxury-textLight font-medium"><?= e(t('stats_support')) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Flash Messages -->
    <?php
    $flash = getFlashMessage();
    if ($flash):
        echo alert($flash['message'], $flash['type']);
    endif; ?>
    
    <!-- Features Section -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center p-8 rounded-2xl hover:bg-luxury-accentLight/30 transition-all duration-300 group">
                    <div class="w-20 h-20 bg-gradient-to-br from-luxury-accent to-yellow-500 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-truck text-3xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-luxury-primary mb-3"><?= e(t('feature_delivery_title')) ?></h3>
                    <p class="text-luxury-textLight"><?= e(t('feature_delivery_desc')) ?></p>
                </div>
                
                <div class="text-center p-8 rounded-2xl hover:bg-luxury-accentLight/30 transition-all duration-300 group">
                    <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-leaf text-3xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-luxury-primary mb-3"><?= e(t('feature_fresh_title')) ?></h3>
                    <p class="text-luxury-textLight"><?= e(t('feature_fresh_desc')) ?></p>
                </div>
                
                <div class="text-center p-8 rounded-2xl hover:bg-luxury-accentLight/30 transition-all duration-300 group">
                    <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-heart text-3xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-luxury-primary mb-3"><?= e(t('feature_quality_title')) ?></h3>
                    <p class="text-luxury-textLight"><?= e(t('feature_quality_desc')) ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section id="featured" class="py-20 bg-gradient-to-b from-white to-luxury-accentLight/20">
        <div class="container mx-auto px-6">
            <!-- Section Header -->
            <div class="text-center mb-16">
                <div class="inline-block bg-luxury-accent/10 px-6 py-2 rounded-full mb-4">
                    <span class="text-luxury-accent font-semibold text-sm">✨ <?= e(t('featured_collection')) ?></span>
                </div>
                <h2 class="text-4xl md:text-5xl font-luxury font-bold text-luxury-primary mb-4"><?= e(t('best_sellers_title')) ?></h2>
                <p class="text-luxury-textLight text-lg max-w-2xl mx-auto">
                    <?= e(t('best_sellers_subtitle')) ?>
                </p>
            </div>
            
            <?php if (empty($featuredProducts)): ?>
                <div class="text-center py-16">
                    <div class="w-32 h-32 bg-luxury-border rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-seedling text-5xl text-luxury-textLight"></i>
                    </div>
                    <p class="text-luxury-textLight text-lg"><?= e(t('no_featured_products')) ?></p>
                    <a href="shop.php" class="inline-block mt-6 text-luxury-accent hover:text-luxury-primary font-semibold">
                        <?= e(t('explore_all_products')) ?> <i class="fas fa-arrow-right ms-2 rtl:rotate-180"></i>
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    <?php foreach ($featuredProducts as $product): ?>
                        <?= productCard($product) ?>
                    <?php endforeach; ?>
                </div>
                
                <!-- View All Button -->
                <div class="text-center mt-12">
                    <a href="shop.php" 
                       class="inline-flex items-center gap-3 bg-white border-2 border-luxury-accent text-luxury-accent px-8 py-4 rounded-full hover:bg-luxury-accent hover:text-white transition-all duration-300 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <span><?= e(t('view_all_products')) ?></span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Categories Section -->
    <?php if (!empty($categories)): ?>
    <section class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-luxury font-bold text-luxury-primary mb-4"><?= e(t('shop_by_category')) ?></h2>
                <p class="text-luxury-textLight text-lg"><?= e(t('shop_by_category_subtitle')) ?></p>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                <?php foreach ($categories as $category): ?>
                    <a href="shop.php?category=<?= e((string)$category['id']) ?>" 
                       class="group text-center p-6 bg-white border-2 border-luxury-border rounded-2xl hover:border-luxury-accent hover:shadow-xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="w-16 h-16 bg-luxury-accentLight rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-luxury-accent transition-colors duration-300">
                            <i class="fas fa-spa text-2xl text-luxury-accent group-hover:text-white transition-colors duration-300"></i>
                        </div>
                        <h3 class="font-semibold text-luxury-primary group-hover:text-luxury-accent transition-colors">
                            <?= e(getCategoryName($category)) ?>
                        </h3>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Newsletter Section -->
    <section class="py-20 bg-gradient-to-br from-luxury-primary via-gray-900 to-luxury-primary text-white">
        <div class="container mx-auto px-6">
            <div class="max-w-3xl mx-auto text-center">
                <div class="w-20 h-20 bg-luxury-accent rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-envelope text-3xl text-white"></i>
                </div>
                <h2 class="text-4xl font-luxury font-bold mb-4"><?= e(t('newsletter_title')) ?></h2>
                <p class="text-xl text-gray-300 mb-8">
                    <?= e(t('newsletter_subtitle')) ?>
                </p>
                <form class="flex flex-col sm:flex-row gap-4 max-w-xl mx-auto">
                    <input type="email" 
                           placeholder="<?= e(t('email_placeholder')) ?>" 
                           class="flex-1 px-6 py-4 rounded-full text-luxury-primary focus:outline-none focus:ring-4 focus:ring-luxury-accent/50">
                    <button type="submit" 
                            class="bg-luxury-accent hover:bg-yellow-500 text-white px-8 py-4 rounded-full font-semibold transition-all duration-300 shadow-xl hover:shadow-2xl">
                        <?= e(t('subscribe_button')) ?> <i class="fas fa-paper-plane ms-2 rtl:rotate-180"></i>
                    </button>
                </form>
                <p class="text-sm text-gray-400 mt-4">
                    <i class="fas fa-lock me-2"></i><?= e(t('privacy_notice')) ?>
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
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

