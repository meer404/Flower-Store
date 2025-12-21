<?php
declare(strict_types=1);

/**
 * Shopping Cart Page
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';
require_once __DIR__ . '/src/components.php';

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

    <!-- Page Header -->
    <div class="bg-gradient-to-r from-luxury-primary to-gray-900 text-white py-16">
        <div class="container mx-auto px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-5xl font-luxury font-bold mb-4">
                        <i class="fas fa-shopping-cart mr-4"></i><?= e(t('nav_cart')) ?>
                    </h1>
                    <p class="text-xl text-gray-300">
                        <?php if (!empty($cartItems)): ?>
                            You have <?= count($cartItems) ?> item<?= count($cartItems) > 1 ? 's' : '' ?> in your cart
                        <?php else: ?>
                            Your cart is waiting for beautiful flowers
                        <?php endif; ?>
                    </p>
                </div>
                <?php if (!empty($cartItems)): ?>
                <div class="hidden md:block bg-white/10 backdrop-blur-sm px-8 py-6 rounded-2xl">
                    <p class="text-sm text-gray-300 mb-1">Cart Total</p>
                    <p class="text-4xl font-bold text-luxury-accent font-luxury"><?= e(formatPrice($cartTotal)) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
        <?php
        $flash = getFlashMessage();
        if ($flash):
            echo alert($flash['message'], $flash['type']);
        endif;
        ?>
        
        <?php if (empty($cartItems)): ?>
            <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl p-16 text-center">
                <div class="w-32 h-32 bg-luxury-border rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-shopping-cart text-6xl text-luxury-textLight"></i>
                </div>
                <h3 class="text-3xl font-bold text-luxury-primary mb-4"><?= e(t('empty_cart')) ?></h3>
                <p class="text-luxury-textLight text-lg mb-8">Start adding beautiful flowers to your cart!</p>
                <a href="shop.php" 
                   class="inline-flex items-center gap-3 bg-gradient-to-r from-luxury-accent to-yellow-500 text-white px-10 py-4 rounded-full hover:from-yellow-500 hover:to-luxury-accent transition-all duration-300 font-bold shadow-xl hover:shadow-2xl transform hover:-translate-y-1">
                    <i class="fas fa-store"></i>
                    <?= e(t('continue_shopping')) ?>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        <?php else: ?>
            <!-- Desktop Table View -->
            <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl overflow-hidden hidden md:block">
                <div class="bg-gradient-to-r from-luxury-primary to-gray-900 text-white px-6 py-4">
                    <h2 class="text-xl font-bold"><i class="fas fa-shopping-basket mr-2"></i>Cart Items</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-luxury-accentLight/30">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-luxury-primary uppercase tracking-wider"><?= e('Product') ?></th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-luxury-primary uppercase tracking-wider"><?= e(t('price')) ?></th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-luxury-primary uppercase tracking-wider"><?= e(t('quantity')) ?></th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-luxury-primary uppercase tracking-wider"><?= e(t('subtotal')) ?></th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-luxury-primary uppercase tracking-wider"><?= e('Action') ?></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-luxury-border">
                            <?php foreach ($cartItems as $item): ?>
                                <tr class="hover:bg-luxury-accentLight/10 transition-colors">
                                    <td class="px-6 py-6">
                                        <div class="flex items-center">
                                            <?php if ($item['image_url']): ?>
                                                <img src="<?= e($item['image_url']) ?>" 
                                                     alt="<?= e(getProductName($item)) ?>"
                                                     class="w-20 h-20 object-cover rounded-xl mr-4 shadow-md">
                                            <?php endif; ?>
                                            <div>
                                                <h3 class="text-base font-bold text-luxury-primary mb-1"><?= e(getProductName($item)) ?></h3>
                                                <p class="text-sm text-luxury-textLight"><i class="fas fa-tag mr-1"></i><?= e(getCategoryName($item)) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-6">
                                        <span class="text-lg font-bold text-luxury-accent"><?= e(formatPrice((float)$item['price'])) ?></span>
                                    </td>
                                    <td class="px-6 py-6">
                                        <form method="POST" action="cart_action.php" class="inline">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?= e((string)$item['id']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                            <div class="flex items-center gap-2">
                                                <input type="number" name="quantity" value="<?= e((string)$item['cart_quantity']) ?>" 
                                                       min="1" max="<?= e((string)$item['stock_qty']) ?>"
                                                       class="w-24 px-4 py-2.5 border-2 border-luxury-border rounded-xl focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent text-center font-bold"
                                                       onchange="this.form.submit()">
                                                <button type="submit" class="text-luxury-accent hover:text-luxury-primary">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                    <td class="px-6 py-6">
                                        <span class="text-xl font-bold text-luxury-accent font-luxury"><?= e(formatPrice($item['subtotal'])) ?></span>
                                    </td>
                                    <td class="px-6 py-6">
                                        <form method="POST" action="cart_action.php" class="inline">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?= e((string)$item['id']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                            <button type="submit" 
                                                    class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white px-4 py-2 rounded-xl transition-all duration-300 font-semibold">
                                                <i class="fas fa-trash-alt mr-1"></i><?= e(t('remove')) ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gradient-to-r from-luxury-accentLight to-yellow-100">
                            <tr>
                                <td colspan="3" class="px-6 py-6 text-right text-lg font-bold text-luxury-primary">
                                    <i class="fas fa-calculator mr-2"></i><?= e(t('total')) ?>:
                                </td>
                                <td colspan="2" class="px-6 py-6">
                                    <span class="text-3xl font-bold text-luxury-accent font-luxury"><?= e(formatPrice($cartTotal)) ?></span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Mobile Card View -->
            <div class="md:hidden space-y-6">
                <?php foreach ($cartItems as $item): ?>
                    <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl overflow-hidden">
                        <div class="flex gap-4 p-4 bg-luxury-accentLight/20">
                            <?php if ($item['image_url']): ?>
                                <img src="<?= e($item['image_url']) ?>" 
                                     alt="<?= e(getProductName($item)) ?>"
                                     class="w-24 h-24 object-cover rounded-xl flex-shrink-0 shadow-md">
                            <?php endif; ?>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-luxury-primary mb-1"><?= e(getProductName($item)) ?></h3>
                                <p class="text-sm text-luxury-textLight mb-2"><i class="fas fa-tag mr-1"></i><?= e(getCategoryName($item)) ?></p>
                                <p class="text-xl font-bold text-luxury-accent"><?= e(formatPrice((float)$item['price'])) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-4 border-t-2 border-luxury-border">
                            <form method="POST" action="cart_action.php" class="flex items-center gap-2">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?= e((string)$item['id']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                <label class="text-sm font-bold text-luxury-primary">
                                    <i class="fas fa-hashtag mr-1"></i><?= e(t('quantity')) ?>:
                                </label>
                                <input type="number" name="quantity" value="<?= e((string)$item['cart_quantity']) ?>" 
                                       min="1" max="<?= e((string)$item['stock_qty']) ?>"
                                       class="w-20 px-3 py-2 border-2 border-luxury-border rounded-xl focus:outline-none focus:ring-2 focus:ring-luxury-accent text-center font-bold"
                                       onchange="this.form.submit()">
                            </form>
                            <div class="text-right">
                                <p class="text-xs text-luxury-textLight uppercase tracking-wider"><?= e(t('subtotal')) ?></p>
                                <p class="text-2xl font-bold text-luxury-accent font-luxury"><?= e(formatPrice($item['subtotal'])) ?></p>
                            </div>
                        </div>
                        <form method="POST" action="cart_action.php" class="p-4 bg-red-50">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?= e((string)$item['id']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                            <button type="submit" 
                                    class="w-full bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl transition-all duration-300 font-bold shadow-md">
                                <i class="fas fa-trash-alt mr-2"></i><?= e(t('remove')) ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
                
                <!-- Mobile Total -->
                <div class="bg-gradient-to-r from-luxury-accent to-yellow-500 text-white p-6 rounded-2xl shadow-xl">
                    <div class="flex justify-between items-center">
                        <span class="text-xl font-bold"><i class="fas fa-calculator mr-2"></i><?= e(t('total')) ?>:</span>
                        <span class="text-3xl font-bold font-luxury"><?= e(formatPrice($cartTotal)) ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="mt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
                <a href="shop.php" 
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 border-2 border-luxury-primary text-luxury-primary px-8 py-4 rounded-full hover:bg-luxury-primary hover:text-white transition-all duration-300 font-bold shadow-md">
                    <i class="fas fa-arrow-left"></i>
                    <?= e(t('continue_shopping')) ?>
                </a>
                
                <a href="checkout.php" 
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-gradient-to-r from-luxury-accent to-yellow-500 text-white px-10 py-4 rounded-full hover:from-yellow-500 hover:to-luxury-accent transition-all duration-300 font-bold shadow-xl hover:shadow-2xl transform hover:-translate-y-1">
                    <?= e(t('checkout')) ?>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?= modernFooter() ?>
</body>
</html>

