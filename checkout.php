<?php
declare(strict_types=1);

/**
 * Checkout Page
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';

requireLogin();

$pdo = getDB();
$error = '';

// Get cart items
$cartItems = [];
$cartTotal = 0.0;

if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    $stmt = $pdo->prepare("SELECT p.*, c.name_en as category_name_en, c.name_ku as category_name_ku 
                           FROM products p 
                           JOIN categories c ON p.category_id = c.id 
                           WHERE p.id IN ({$placeholders})");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();
    
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

// Redirect if cart is empty
if (empty($cartItems)) {
    redirect('cart.php', e('Your cart is empty'), 'error');
}

// Get user info
$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT full_name, email FROM users WHERE id = :id');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = sanitizeInput('csrf_token', 'POST');
    $shippingAddress = sanitizeInput('shipping_address', 'POST');
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = t('order_error');
    } elseif (empty($shippingAddress)) {
        $error = t('order_error') . ' - ' . e('Shipping address is required');
    } else {
        try {
            $pdo->beginTransaction();
            
            // Verify stock availability before creating order
            $stockOk = true;
            foreach ($cartItems as $item) {
                $stmt = $pdo->prepare('SELECT stock_qty FROM products WHERE id = :id FOR UPDATE');
                $stmt->execute(['id' => $item['id']]);
                $product = $stmt->fetch();
                
                if (!$product || (int)$product['stock_qty'] < $item['cart_quantity']) {
                    $stockOk = false;
                    break;
                }
            }
            
            if (!$stockOk) {
                $pdo->rollBack();
                $error = t('order_error') . ' - ' . e('Insufficient stock for one or more products');
            } else {
                // Create order
                $stmt = $pdo->prepare('
                    INSERT INTO orders (user_id, grand_total, payment_status, shipping_address)
                    VALUES (:user_id, :grand_total, :payment_status, :shipping_address)
                ');
                $stmt->execute([
                    'user_id' => $userId,
                    'grand_total' => $cartTotal,
                    'payment_status' => 'pending',
                    'shipping_address' => $shippingAddress
                ]);
                
                $orderId = (int)$pdo->lastInsertId();
                
                // Create order items and update stock
                foreach ($cartItems as $item) {
                    // Insert order item
                    $stmt = $pdo->prepare('
                        INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                        VALUES (:order_id, :product_id, :quantity, :unit_price)
                    ');
                    $stmt->execute([
                        'order_id' => $orderId,
                        'product_id' => $item['id'],
                        'quantity' => $item['cart_quantity'],
                        'unit_price' => $item['price']
                    ]);
                    
                    // Update product stock
                    $stmt = $pdo->prepare('UPDATE products SET stock_qty = stock_qty - :quantity WHERE id = :id');
                    $stmt->execute([
                        'quantity' => $item['cart_quantity'],
                        'id' => $item['id']
                    ]);
                }
                
                $pdo->commit();
                
                // Clear cart
                $_SESSION['cart'] = [];
                
                redirect('index.php', t('order_placed'), 'success');
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Checkout error: ' . $e->getMessage());
            $error = t('order_error');
        }
    }
}

$csrfToken = generateCSRFToken();
$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('checkout')) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/src/header.php'; ?>

    <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
        <h1 class="text-3xl md:text-4xl font-luxury font-bold text-luxury-primary mb-6 md:mb-8 tracking-wide"><?= e(t('checkout')) ?></h1>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-sm mb-6">
                <?= e($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
            <!-- Order Summary -->
            <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8 order-2 lg:order-1">
                <h2 class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e('Order Summary') ?></h2>
                
                <div class="space-y-4 md:space-y-6 mb-6">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="flex justify-between items-start border-b border-luxury-border pb-4">
                            <div class="flex-1">
                                <p class="font-medium text-luxury-primary mb-1"><?= e(getProductName($item)) ?></p>
                                <p class="text-sm text-luxury-textLight"><?= e((string)$item['cart_quantity']) ?> x <?= e(formatPrice((float)$item['price'])) ?></p>
                            </div>
                            <p class="font-semibold text-luxury-accent ml-4"><?= e(formatPrice($item['subtotal'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="border-t border-luxury-border pt-4">
                    <div class="flex justify-between items-center">
                        <span class="text-lg md:text-xl font-bold text-luxury-primary"><?= e(t('total')) ?>:</span>
                        <span class="text-xl md:text-2xl font-bold text-luxury-accent font-luxury"><?= e(formatPrice($cartTotal)) ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Checkout Form -->
            <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8 order-1 lg:order-2">
                <h2 class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e('Customer Information') ?></h2>
                
                <div class="mb-6 p-4 bg-luxury-border rounded-sm">
                    <p class="text-sm text-luxury-textLight mb-2"><?= e('Name') ?>: <span class="font-medium text-luxury-primary"><?= e($user['full_name']) ?></span></p>
                    <p class="text-sm text-luxury-textLight"><?= e('Email') ?>: <span class="font-medium text-luxury-primary"><?= e($user['email']) ?></span></p>
                </div>
                
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    
                    <div>
                        <label for="shipping_address" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e(t('shipping_address')) ?> *
                        </label>
                        <textarea id="shipping_address" name="shipping_address" rows="4" required
                                  class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent"
                                  placeholder="<?= e('Enter your complete shipping address') ?>"><?= e(sanitizeInput('shipping_address', 'POST', '')) ?></textarea>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-luxury-accent text-white py-3 px-4 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                        <?= e(t('place_order')) ?>
                    </button>
                </form>
                
                <a href="cart.php" 
                   class="block mt-6 text-center text-luxury-accent hover:text-luxury-primary transition-colors font-medium">
                    <?= e('← Back to Cart') ?>
                </a>
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

