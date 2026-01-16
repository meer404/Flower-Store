<?php
declare(strict_types=1);

/**
 * Wishlist Page
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';
require_once __DIR__ . '/src/components.php';

requireLogin();

$pdo = getDB();
$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT p.*, c.name_en as category_name_en, c.name_ku as category_name_ku, w.id as wishlist_id
                       FROM wishlist w
                       JOIN products p ON w.product_id = p.id
                       JOIN categories c ON p.category_id = c.id
                       WHERE w.user_id = :user_id
                       ORDER BY w.created_at DESC');
$stmt->execute(['user_id' => $userId]);
$wishlistItems = $stmt->fetchAll();

$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('wishlist_title')) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/src/header.php'; ?>

    <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
        <h1 class="text-3xl md:text-4xl font-luxury font-bold text-luxury-primary mb-6 md:mb-8 tracking-wide"><?= e(t('my_wishlist')) ?></h1>
        
        <?php
        $flash = getFlashMessage();
        if ($flash):
            $flashType = $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'error' : 'info');
            $bgColor = $flashType === 'success' ? 'bg-green-50' : ($flashType === 'error' ? 'bg-red-50' : 'bg-blue-50');
            $borderColor = $flashType === 'success' ? 'border-green-200' : ($flashType === 'error' ? 'border-red-200' : 'border-blue-200');
            $textColor = $flashType === 'success' ? 'text-green-700' : ($flashType === 'error' ? 'text-red-700' : 'text-blue-700');
        ?>
            <div class="<?= $bgColor . ' ' . $borderColor . ' ' . $textColor ?> border px-4 py-3 rounded-sm mb-6">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($wishlistItems)): ?>
            <div class="bg-white border border-luxury-border shadow-luxury p-8 md:p-12 text-center">
                <p class="text-luxury-textLight mb-6 text-lg"><?= e(t('wishlist_empty')) ?></p>
                <a href="shop.php" 
                   class="inline-block bg-luxury-primary text-white px-8 py-3 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                    <?= e(t('continue_shopping')) ?>
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 md:gap-8">
                <?php foreach ($wishlistItems as $item): ?>
                    <div class="bg-white border border-luxury-border shadow-luxury overflow-hidden hover:shadow-luxuryHover transition-all duration-300 group">
                        <a href="product.php?id=<?= e((string)$item['id']) ?>">
                            <?php if ($item['image_url']): ?>
                                <div class="overflow-hidden">
                                    <img src="<?= e($item['image_url']) ?>" 
                                         alt="<?= e(getProductName($item)) ?>"
                                         class="w-full h-48 md:h-56 object-cover group-hover:scale-105 transition-transform duration-500">
                                </div>
                            <?php else: ?>
                                <div class="w-full h-48 md:h-56 bg-luxury-border flex items-center justify-center">
                                    <span class="text-luxury-textLight"><?= e(t('no_image')) ?></span>
                                </div>
                            <?php endif; ?>
                        </a>
                        
                        <div class="p-4 md:p-6">
                            <h3 class="text-base md:text-lg font-semibold text-luxury-primary mb-2 font-luxury">
                                <a href="product.php?id=<?= e((string)$item['id']) ?>" class="hover:text-luxury-accent transition-colors">
                                    <?= e(getProductName($item)) ?>
                                </a>
                            </h3>
                            <p class="text-xl md:text-2xl font-bold text-luxury-accent mb-4 md:mb-6 font-luxury"><?= e(formatPrice((float)$item['price'])) ?></p>
                            
                            <div class="space-y-3">
                                <?php if ($item['stock_qty'] > 0): ?>
                                    <form method="POST" action="cart_action.php" class="inline w-full">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?= e((string)$item['id']) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                        <button type="submit" 
                                                class="w-full bg-luxury-accent text-white py-2.5 px-4 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                                            <?= e(t('add_to_cart')) ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" action="wishlist_action.php" class="inline w-full">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= e((string)$item['id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                    <button type="submit" 
                                            class="w-full border border-red-300 text-red-600 py-2.5 px-4 rounded-sm hover:bg-red-50 transition-all duration-300 font-medium">
                                        <?= e(t('remove_from_wishlist')) ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?= modernFooter() ?>
</body>
</html>

