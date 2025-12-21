<?php
declare(strict_types=1);

/**
 * Shopping Cart Page
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';

requireLogin();

$pdo = getDB();
$cartItems = [];
$cartTotal = 0.0;

// Get cart items with product details
if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    $stmt = $pdo->prepare("SELECT p.*, c.name_en as category_name_en, c.name_ku as category_name_ku 
                           FROM products p 
                           JOIN categories c ON p.category_id = c.id 
                           WHERE p.id IN ({$placeholders})");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();
    
    // Combine with cart quantities
    foreach ($products as $product) {
        $productId = (int)$product['id'];
        $quantity = (int)($_SESSION['cart'][$productId] ?? 0);
        
        if ($quantity > 0) {
            $product['cart_quantity'] = $quantity;
            $product['subtotal'] = (float)$product['price'] * $quantity;
            $cartItems[] = $product;
            $cartTotal += $product['subtotal'];
        }
    }
}

$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('nav_cart')) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/src/header.php'; ?>

    <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
        <h1 class="text-3xl md:text-4xl font-luxury font-bold text-luxury-primary mb-6 md:mb-8 tracking-wide"><?= e(t('nav_cart')) ?></h1>
        
        <?php
        $flash = getFlashMessage();
        if ($flash):
        ?>
            <div class="bg-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-50 border border-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-200 text-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-700 px-4 py-3 rounded-sm mb-6">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($cartItems)): ?>
            <div class="bg-white border border-luxury-border shadow-luxury p-8 md:p-12 text-center">
                <p class="text-luxury-textLight mb-6 text-lg"><?= e(t('empty_cart')) ?></p>
                <a href="shop.php" 
                   class="inline-block bg-luxury-primary text-white px-8 py-3 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                    <?= e(t('continue_shopping')) ?>
                </a>
            </div>
        <?php else: ?>
            <!-- Desktop Table View -->
            <div class="bg-white border border-luxury-border shadow-luxury p-4 md:p-6 hidden md:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-luxury-border">
                        <thead class="bg-luxury-border">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-luxury-text uppercase tracking-wide"><?= e('Product') ?></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-luxury-text uppercase tracking-wide"><?= e(t('price')) ?></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-luxury-text uppercase tracking-wide"><?= e(t('quantity')) ?></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-luxury-text uppercase tracking-wide"><?= e(t('subtotal')) ?></th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-luxury-text uppercase tracking-wide"><?= e('Action') ?></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-luxury-border">
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <?php if ($item['image_url']): ?>
                                                <img src="<?= e($item['image_url']) ?>" 
                                                     alt="<?= e(getProductName($item)) ?>"
                                                     class="w-16 h-16 object-cover rounded-sm mr-4">
                                            <?php endif; ?>
                                            <div>
                                                <h3 class="text-sm font-medium text-luxury-primary"><?= e(getProductName($item)) ?></h3>
                                                <p class="text-xs text-luxury-textLight"><?= e(getCategoryName($item)) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-luxury-text"><?= e(formatPrice((float)$item['price'])) ?></td>
                                    <td class="px-6 py-4">
                                        <form method="POST" action="cart_action.php" class="inline">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?= e((string)$item['id']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                            <input type="number" name="quantity" value="<?= e((string)$item['cart_quantity']) ?>" 
                                                   min="1" max="<?= e((string)$item['stock_qty']) ?>"
                                                   class="w-20 px-2 py-1.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent"
                                                   onchange="this.form.submit()">
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold text-luxury-accent"><?= e(formatPrice($item['subtotal'])) ?></td>
                                    <td class="px-6 py-4">
                                        <form method="POST" action="cart_action.php" class="inline">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?= e((string)$item['id']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                            <button type="submit" 
                                                    class="text-red-600 hover:text-red-800 text-sm font-medium">
                                                <?= e(t('remove')) ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-luxury-border">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-luxury-text">
                                    <?= e(t('total')) ?>:
                                </td>
                                <td class="px-6 py-4 text-lg font-bold text-luxury-accent font-luxury">
                                    <?= e(formatPrice($cartTotal)) ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Mobile Card View -->
            <div class="md:hidden space-y-4">
                <?php foreach ($cartItems as $item): ?>
                    <div class="bg-white border border-luxury-border shadow-luxury p-4">
                        <div class="flex gap-4 mb-4">
                            <?php if ($item['image_url']): ?>
                                <img src="<?= e($item['image_url']) ?>" 
                                     alt="<?= e(getProductName($item)) ?>"
                                     class="w-20 h-20 object-cover rounded-sm flex-shrink-0">
                            <?php endif; ?>
                            <div class="flex-1">
                                <h3 class="text-base font-semibold text-luxury-primary mb-1"><?= e(getProductName($item)) ?></h3>
                                <p class="text-xs text-luxury-textLight mb-2"><?= e(getCategoryName($item)) ?></p>
                                <p class="text-lg font-bold text-luxury-accent"><?= e(formatPrice((float)$item['price'])) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mb-4 pb-4 border-b border-luxury-border">
                            <label class="text-sm font-medium text-luxury-text">
                                <?= e(t('quantity')) ?>:
                                <form method="POST" action="cart_action.php" class="inline">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?= e((string)$item['id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                    <input type="number" name="quantity" value="<?= e((string)$item['cart_quantity']) ?>" 
                                           min="1" max="<?= e((string)$item['stock_qty']) ?>"
                                           class="ml-2 w-20 px-2 py-1.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent"
                                           onchange="this.form.submit()">
                                </form>
                            </label>
                            <div>
                                <p class="text-sm text-luxury-textLight"><?= e(t('subtotal')) ?></p>
                                <p class="text-lg font-bold text-luxury-accent"><?= e(formatPrice($item['subtotal'])) ?></p>
                            </div>
                        </div>
                        <form method="POST" action="cart_action.php" class="inline w-full">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?= e((string)$item['id']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                            <button type="submit" 
                                    class="w-full text-red-600 hover:text-red-800 text-sm font-medium py-2">
                                <?= e(t('remove')) ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <div class="bg-luxury-border p-4 rounded-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-base font-medium text-luxury-text"><?= e(t('total')) ?>:</span>
                        <span class="text-2xl font-bold text-luxury-accent font-luxury"><?= e(formatPrice($cartTotal)) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 md:mt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
                <a href="shop.php" 
                   class="w-full sm:w-auto border border-luxury-primary text-luxury-primary px-8 py-3 rounded-sm hover:bg-luxury-primary hover:text-white transition-all duration-300 text-center font-medium">
                    <?= e(t('continue_shopping')) ?>
                </a>
                
                <a href="checkout.php" 
                   class="w-full sm:w-auto bg-luxury-accent text-white px-8 py-3 rounded-sm hover:bg-opacity-90 transition-all duration-300 text-center font-medium shadow-md">
                    <?= e(t('checkout')) ?>
                </a>
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

