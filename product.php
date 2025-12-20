<?php
declare(strict_types=1);

/**
 * Product Detail Page
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';

$pdo = getDB();
$productId = (int)sanitizeInput('id', 'GET', '0');

if ($productId <= 0) {
    redirect('shop.php', e('Product not found'), 'error');
}

// Get product details
$stmt = $pdo->prepare('SELECT p.*, c.name_en as category_name_en, c.name_ku as category_name_ku, c.slug as category_slug
                       FROM products p 
                       JOIN categories c ON p.category_id = c.id 
                       WHERE p.id = :id');
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    redirect('shop.php', e('Product not found'), 'error');
}

// Increment view count
$stmt = $pdo->prepare('UPDATE products SET views = views + 1 WHERE id = :id');
$stmt->execute(['id' => $productId]);

// Get product images (gallery)
$stmt = $pdo->prepare('SELECT image_url FROM product_images WHERE product_id = :id ORDER BY display_order, id');
$stmt->execute(['id' => $productId]);
$productImages = $stmt->fetchAll();

// Get reviews
$stmt = $pdo->prepare('SELECT r.*, u.full_name 
                       FROM reviews r 
                       JOIN users u ON r.user_id = u.id 
                       WHERE r.product_id = :id 
                       ORDER BY r.created_at DESC 
                       LIMIT 10');
$stmt->execute(['id' => $productId]);
$reviews = $stmt->fetchAll();

// Calculate average rating
$stmt = $pdo->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE product_id = :id');
$stmt->execute(['id' => $productId]);
$ratingData = $stmt->fetch();
$avgRating = $ratingData ? (float)$ratingData['avg_rating'] : 0;
$reviewCount = $ratingData ? (int)$ratingData['review_count'] : 0;

// Get related products (same category)
$stmt = $pdo->prepare('SELECT p.*, c.name_en as category_name_en, c.name_ku as category_name_ku 
                       FROM products p 
                       JOIN categories c ON p.category_id = c.id 
                       WHERE p.category_id = :category_id AND p.id != :id AND p.stock_qty > 0 
                       ORDER BY p.is_featured DESC, p.created_at DESC 
                       LIMIT 4');
$stmt->execute(['category_id' => $product['category_id'], 'id' => $productId]);
$relatedProducts = $stmt->fetchAll();

// Check if product is in wishlist (if logged in)
$inWishlist = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare('SELECT id FROM wishlist WHERE user_id = :user_id AND product_id = :product_id');
    $stmt->execute(['user_id' => $_SESSION['user_id'], 'product_id' => $productId]);
    $inWishlist = (bool)$stmt->fetch();
}

$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(getProductName($product)) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <!-- Navbar -->
    <nav class="bg-white border-b border-luxury-border shadow-sm">
        <div class="container mx-auto px-4 md:px-6 py-4">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <a href="index.php" class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary tracking-wide">Bloom & Vine</a>
                <div class="flex items-center space-x-4 md:space-x-8 flex-wrap text-sm md:text-base" style="direction: ltr;">
                    <a href="index.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_home')) ?></a>
                    <a href="shop.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_shop')) ?></a>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <a href="admin/dashboard.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium hidden md:inline"><?= e(t('nav_admin')) ?></a>
                        <?php endif; ?>
                        <a href="account.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium hidden md:inline"><?= e('Account') ?></a>
                        <a href="cart.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium">
                            <?= e(t('nav_cart')) ?> <span class="bg-luxury-accent text-white px-2 py-0.5 rounded-full text-xs"><?= e((string)getCartCount()) ?></span>
                        </a>
                        <a href="logout.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_logout')) ?></a>
                    <?php else: ?>
                        <a href="login.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_login')) ?></a>
                        <a href="register.php" class="bg-luxury-primary text-white px-4 md:px-6 py-2 rounded-sm hover:bg-opacity-90 transition-all font-medium text-sm"><?= e(t('nav_register')) ?></a>
                    <?php endif; ?>
                    <a href="?lang=<?= $lang === 'en' ? 'ku' : 'en' ?>&id=<?= e((string)$productId) ?>" class="text-luxury-accent hover:text-luxury-primary font-semibold border border-luxury-accent px-2 md:px-3 py-1 rounded-sm text-xs md:text-sm">
                        <?= $lang === 'en' ? 'KU' : 'EN' ?>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
        <!-- Breadcrumb -->
        <nav class="mb-4 md:mb-6 text-xs md:text-sm">
            <a href="index.php" class="text-luxury-accent hover:text-luxury-primary transition-colors"><?= e(t('nav_home')) ?></a>
            <span class="mx-2 text-luxury-textLight">/</span>
            <a href="shop.php" class="text-luxury-accent hover:text-luxury-primary transition-colors"><?= e(t('nav_shop')) ?></a>
            <span class="mx-2 text-luxury-textLight">/</span>
            <a href="shop.php?category=<?= e((string)$product['category_id']) ?>" class="text-luxury-accent hover:text-luxury-primary transition-colors">
                <?= e(getCategoryName($product)) ?>
            </a>
            <span class="mx-2 text-luxury-textLight">/</span>
            <span class="text-luxury-textLight"><?= e(getProductName($product)) ?></span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-12 mb-12 md:mb-16">
            <!-- Product Images -->
            <div class="bg-white border border-luxury-border shadow-luxury p-4 md:p-6">
                <?php if (!empty($productImages)): ?>
                    <div class="mb-4 md:mb-6">
                        <img id="mainImage" src="<?= e($productImages[0]['image_url']) ?>" 
                             alt="<?= e(getProductName($product)) ?>"
                             class="w-full h-64 md:h-96 object-cover rounded-sm">
                    </div>
                    <?php if (count($productImages) > 1): ?>
                        <div class="grid grid-cols-4 gap-2 md:gap-3">
                            <?php foreach ($productImages as $index => $img): ?>
                                <img src="<?= e($img['image_url']) ?>" 
                                     alt="<?= e(getProductName($product)) ?>"
                                     onclick="document.getElementById('mainImage').src = this.src"
                                     class="w-full h-16 md:h-20 object-cover rounded-sm cursor-pointer hover:opacity-75 border-2 border-transparent hover:border-luxury-accent transition-all">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php elseif ($product['image_url']): ?>
                    <img src="<?= e($product['image_url']) ?>" 
                         alt="<?= e(getProductName($product)) ?>"
                         class="w-full h-64 md:h-96 object-cover rounded-sm">
                <?php else: ?>
                    <div class="w-full h-64 md:h-96 bg-luxury-border flex items-center justify-center rounded-sm">
                        <span class="text-luxury-textLight"><?= e('No Image') ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Info -->
            <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8">
                <h1 class="text-2xl md:text-3xl font-luxury font-bold text-luxury-primary mb-3 tracking-wide"><?= e(getProductName($product)) ?></h1>
                <p class="text-xs md:text-sm text-luxury-textLight mb-4 uppercase tracking-wide">
                    <a href="shop.php?category=<?= e((string)$product['category_id']) ?>" class="text-luxury-accent hover:text-luxury-primary transition-colors">
                        <?= e(getCategoryName($product)) ?>
                    </a>
                </p>

                <!-- Rating -->
                <?php if ($reviewCount > 0): ?>
                    <div class="mb-4 md:mb-6 flex items-center flex-wrap gap-2">
                        <div class="flex text-luxury-accent text-lg md:text-xl">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span><?= $i <= round($avgRating) ? '★' : '☆' ?></span>
                            <?php endfor; ?>
                        </div>
                        <span class="text-sm md:text-base text-luxury-textLight">
                            <?= e(number_format($avgRating, 1)) ?> (<?= e((string)$reviewCount) ?> <?= e('reviews') ?>)
                        </span>
                    </div>
                <?php endif; ?>

                <p class="text-3xl md:text-4xl font-luxury font-bold text-luxury-accent mb-6"><?= e(formatPrice((float)$product['price'])) ?></p>

                <div class="mb-6 md:mb-8">
                    <p class="text-luxury-text mb-4 md:mb-6 leading-relaxed"><?= e(getProductDescription($product)) ?></p>
                    
                    <div class="space-y-2 text-sm md:text-base">
                        <?php if ($product['stock_qty'] > 0): ?>
                            <p class="text-green-600 font-medium">✓ <?= e('In Stock') ?> (<?= e((string)$product['stock_qty']) ?> <?= e('available') ?>)</p>
                        <?php else: ?>
                            <p class="text-red-600 font-medium">✗ <?= e('Out of Stock') ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="space-y-4">
                    <?php if ($product['stock_qty'] > 0): ?>
                        <form method="POST" action="cart_action.php" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= e((string)$product['id']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                            
                            <label class="text-sm font-medium text-luxury-text flex items-center gap-2 sm:flex-shrink-0">
                                <?= e(t('quantity')) ?>:
                                <input type="number" name="quantity" value="1" min="1" max="<?= e((string)$product['stock_qty']) ?>"
                                       class="w-20 px-3 py-2 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent">
                            </label>
                            
                            <?php if (isLoggedIn()): ?>
                                <button type="submit" 
                                        class="flex-1 bg-luxury-accent text-white py-3 px-6 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                                    <?= e(t('add_to_cart')) ?>
                                </button>
                            <?php else: ?>
                                <a href="login.php" 
                                   class="flex-1 bg-luxury-accent text-white py-3 px-6 rounded-sm hover:bg-opacity-90 transition-all duration-300 text-center font-medium shadow-md">
                                    <?= e(t('add_to_cart')) ?>
                                </a>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>

                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="wishlist_action.php" class="inline w-full">
                            <input type="hidden" name="action" value="<?= $inWishlist ? 'remove' : 'add' ?>">
                            <input type="hidden" name="product_id" value="<?= e((string)$product['id']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                            <button type="submit" 
                                    class="w-full border border-luxury-primary text-luxury-primary py-2.5 px-4 rounded-sm hover:bg-luxury-primary hover:text-white transition-all duration-300 font-medium">
                                <?= $inWishlist ? '❤️ ' . e('Remove from Wishlist') : '🤍 ' . e('Add to Wishlist') ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8 mb-8 md:mb-12">
            <h2 class="text-2xl md:text-3xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e('Customer Reviews') ?></h2>
            
            <?php
            $hasReviewed = false;
            if (isLoggedIn()) {
                $stmt = $pdo->prepare('SELECT id FROM reviews WHERE user_id = :user_id AND product_id = :product_id');
                $stmt->execute(['user_id' => $_SESSION['user_id'], 'product_id' => $productId]);
                $hasReviewed = (bool)$stmt->fetch();
            }
            if (isLoggedIn() && !$hasReviewed):
            ?>
                <div class="mb-6">
                    <a href="review.php?product_id=<?= e((string)$productId) ?>" 
                       class="inline-block bg-luxury-primary text-white py-2.5 px-6 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                        <?= e('Write a Review') ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (empty($reviews)): ?>
                <p class="text-luxury-textLight"><?= e('No reviews yet. Be the first to review!') ?></p>
            <?php else: ?>
                <div class="space-y-6 md:space-y-8">
                    <?php foreach ($reviews as $review): ?>
                        <div class="border-b border-luxury-border pb-4 md:pb-6 last:border-0">
                            <div class="flex flex-col sm:flex-row justify-between items-start mb-3 gap-2">
                                <div>
                                    <p class="font-semibold text-luxury-primary text-base md:text-lg"><?= e($review['full_name']) ?></p>
                                    <p class="text-xs md:text-sm text-luxury-textLight"><?= e(date('F j, Y', strtotime($review['created_at']))) ?></p>
                                </div>
                                <div class="flex text-luxury-accent text-base md:text-lg">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span><?= $i <= $review['rating'] ? '★' : '☆' ?></span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php if ($review['comment']): ?>
                                <p class="text-luxury-text leading-relaxed"><?= e($review['comment']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
            <div class="mb-8 md:mb-12">
                <h2 class="text-2xl md:text-3xl font-luxury font-bold text-luxury-primary mb-6 md:mb-8 tracking-wide"><?= e('Related Products') ?></h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
                    <?php foreach ($relatedProducts as $related): ?>
                        <div class="bg-white border border-luxury-border shadow-luxury overflow-hidden hover:shadow-luxuryHover transition-all duration-300 group">
                            <a href="product.php?id=<?= e((string)$related['id']) ?>">
                                <?php if ($related['image_url']): ?>
                                    <div class="overflow-hidden">
                                        <img src="<?= e($related['image_url']) ?>" 
                                             alt="<?= e(getProductName($related)) ?>"
                                             class="w-full h-48 md:h-56 object-cover group-hover:scale-105 transition-transform duration-500">
                                    </div>
                                <?php else: ?>
                                    <div class="w-full h-48 md:h-56 bg-luxury-border flex items-center justify-center">
                                        <span class="text-luxury-textLight"><?= e('No Image') ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="p-4 md:p-6">
                                    <h3 class="text-base md:text-lg font-semibold text-luxury-primary mb-2 font-luxury hover:text-luxury-accent transition-colors"><?= e(getProductName($related)) ?></h3>
                                    <p class="text-xl md:text-2xl font-bold text-luxury-accent font-luxury"><?= e(formatPrice((float)$related['price'])) ?></p>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-luxury-primary text-white py-12 mt-20 border-t border-luxury-border">
        <div class="container mx-auto px-6 text-center">
            <p class="text-luxury-accentLight font-light tracking-wide">&copy; <?= e(date('Y')) ?> Bloom & Vine. <?= e('All rights reserved.') ?></p>
        </div>
    </footer>
</body>
</html>

