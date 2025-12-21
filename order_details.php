<?php
declare(strict_types=1);

/**
 * Order Details Page
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';
require_once __DIR__ . '/src/components.php';

requireLogin();

$pdo = getDB();
$orderId = (int)sanitizeInput('id', 'GET', '0');
$userId = (int)$_SESSION['user_id'];

if ($orderId <= 0) {
    redirect('account.php', e('Invalid order'), 'error');
}

// Get order details
$stmt = $pdo->prepare('SELECT o.*, u.full_name, u.email 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       WHERE o.id = :id AND (o.user_id = :user_id OR :is_admin = 1)');
$isAdmin = isAdmin() ? 1 : 0;
$stmt->execute(['id' => $orderId, 'user_id' => $userId, 'is_admin' => $isAdmin]);
$order = $stmt->fetch();

if (!$order) {
    redirect('account.php', e('Order not found'), 'error');
}

// Get order items
$stmt = $pdo->prepare('SELECT oi.*, p.name_en, p.name_ku, p.image_url 
                       FROM order_items oi 
                       JOIN products p ON oi.product_id = p.id 
                       WHERE oi.order_id = :order_id');
$stmt->execute(['order_id' => $orderId]);
$orderItems = $stmt->fetchAll();

$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('Order #') ?><?= e((string)$orderId) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/src/header.php'; ?>

    <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
        <div class="mb-6">
            <?php if (isAdmin()): ?>
                <a href="admin/dashboard.php" class="text-luxury-accent hover:text-luxury-primary transition-colors font-medium">← <?= e('Back to Admin Dashboard') ?></a>
            <?php else: ?>
                <a href="account.php" class="text-luxury-accent hover:text-luxury-primary transition-colors font-medium">← <?= e('Back to Account') ?></a>
            <?php endif; ?>
        </div>
        
        <h1 class="text-3xl md:text-4xl font-luxury font-bold text-luxury-primary mb-6 md:mb-8 tracking-wide"><?= e('Order #') ?><?= e((string)$orderId) ?></h1>
        
        <?php
        $flash = getFlashMessage();
        if ($flash):
        ?>
            <div class="bg-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-50 border border-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-200 text-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-700 px-4 py-3 rounded-sm mb-6">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
            <!-- Order Items -->
            <div class="lg:col-span-2 order-2 lg:order-1">
                <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8 mb-6">
                    <h2 class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e('Order Items') ?></h2>
                    <div class="space-y-4 md:space-y-6">
                        <?php foreach ($orderItems as $item): ?>
                            <div class="flex items-center gap-4 border-b border-luxury-border pb-4 md:pb-6 last:border-0">
                                <?php if ($item['image_url']): ?>
                                    <img src="<?= e($item['image_url']) ?>" 
                                         alt="<?= e($item['name_en']) ?>"
                                         class="w-16 h-16 md:w-20 md:h-20 object-cover rounded-sm flex-shrink-0">
                                <?php endif; ?>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-luxury-primary mb-1"><?= e(getProductName($item)) ?></h3>
                                    <p class="text-sm text-luxury-textLight">
                                        <?= e((string)$item['quantity']) ?> x <?= e(formatPrice((float)$item['unit_price'])) ?>
                                    </p>
                                </div>
                                <p class="font-semibold text-luxury-accent text-right flex-shrink-0">
                                    <?= e(formatPrice((float)$item['quantity'] * (float)$item['unit_price'])) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-6 md:mt-8 pt-4 md:pt-6 border-t border-luxury-border">
                        <div class="flex justify-between items-center">
                            <span class="text-lg md:text-xl font-bold text-luxury-primary"><?= e(t('total')) ?>:</span>
                            <span class="text-xl md:text-2xl font-bold text-luxury-accent font-luxury"><?= e(formatPrice((float)$order['grand_total'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Information -->
            <div class="order-1 lg:order-2 space-y-6">
                <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8">
                    <h2 class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e('Order Information') ?></h2>
                    <div class="space-y-4 text-sm md:text-base">
                        <div>
                            <p class="text-luxury-textLight mb-1"><?= e('Order Date') ?></p>
                            <p class="font-semibold text-luxury-primary"><?= e(date('F j, Y g:i A', strtotime($order['order_date']))) ?></p>
                        </div>
                        <div>
                            <p class="text-luxury-textLight mb-2"><?= e('Payment Status') ?></p>
                            <span class="px-3 py-1 text-xs md:text-sm rounded-sm <?= $order['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                <?= e(ucfirst($order['payment_status'])) ?>
                            </span>
                        </div>
                        <?php if (isset($order['order_status'])): ?>
                            <div>
                                <p class="text-luxury-textLight mb-2"><?= e('Order Status') ?></p>
                                <span class="px-3 py-1 text-xs md:text-sm rounded-sm bg-blue-100 text-blue-800">
                                    <?= e(ucfirst($order['order_status'])) ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div>
                                <p class="text-luxury-textLight mb-2"><?= e('Order Status') ?></p>
                                <span class="px-3 py-1 text-xs md:text-sm rounded-sm bg-gray-100 text-gray-800">
                                    <?= e('Pending') ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php if ($isAdmin): ?>
                            <div class="mt-6 pt-6 border-t border-luxury-border">
                                <h3 class="text-lg font-semibold text-luxury-primary mb-4"><?= e('Admin Actions') ?></h3>
                                
                                <!-- Update Order Status Form -->
                                <form method="POST" action="order_action.php" class="mb-4">
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= e((string)$orderId) ?>">
                                    
                                    <div class="mb-3">
                                        <label for="order_status" class="block text-sm font-medium text-luxury-text mb-2"><?= e('Update Order Status') ?></label>
                                        <select name="order_status" id="order_status" class="w-full px-3 py-2 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent">
                                            <option value="pending" <?= (isset($order['order_status']) && $order['order_status'] === 'pending') ? 'selected' : '' ?>><?= e('Pending') ?></option>
                                            <option value="processing" <?= (isset($order['order_status']) && $order['order_status'] === 'processing') ? 'selected' : '' ?>><?= e('Processing') ?></option>
                                            <option value="shipped" <?= (isset($order['order_status']) && $order['order_status'] === 'shipped') ? 'selected' : '' ?>><?= e('Shipped') ?></option>
                                            <option value="delivered" <?= (isset($order['order_status']) && $order['order_status'] === 'delivered') ? 'selected' : '' ?>><?= e('Delivered') ?></option>
                                            <option value="cancelled" <?= (isset($order['order_status']) && $order['order_status'] === 'cancelled') ? 'selected' : '' ?>><?= e('Cancelled') ?></option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="w-full bg-luxury-accent text-white px-4 py-2 rounded-sm hover:bg-luxury-primary transition-colors font-medium">
                                        <?= e('Update Order Status') ?>
                                    </button>
                                </form>
                                
                                <!-- Update Payment Status Form -->
                                <form method="POST" action="order_action.php" class="mb-4">
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                    <input type="hidden" name="action" value="update_payment_status">
                                    <input type="hidden" name="order_id" value="<?= e((string)$orderId) ?>">
                                    
                                    <div class="mb-3">
                                        <label for="payment_status" class="block text-sm font-medium text-luxury-text mb-2"><?= e('Update Payment Status') ?></label>
                                        <select name="payment_status" id="payment_status" class="w-full px-3 py-2 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent">
                                            <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>><?= e('Pending') ?></option>
                                            <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>><?= e('Paid') ?></option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="w-full bg-luxury-accent text-white px-4 py-2 rounded-sm hover:bg-luxury-primary transition-colors font-medium">
                                        <?= e('Update Payment Status') ?>
                                    </button>
                                </form>
                                
                                <!-- Update Tracking Number Form -->
                                <form method="POST" action="order_action.php">
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                    <input type="hidden" name="action" value="update_tracking">
                                    <input type="hidden" name="order_id" value="<?= e((string)$orderId) ?>">
                                    
                                    <div class="mb-3">
                                        <label for="tracking_number" class="block text-sm font-medium text-luxury-text mb-2"><?= e('Tracking Number') ?></label>
                                        <input type="text" name="tracking_number" id="tracking_number" 
                                               value="<?= e($order['tracking_number'] ?? '') ?>" 
                                               class="w-full px-3 py-2 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent"
                                               placeholder="<?= e('Enter tracking number') ?>">
                                    </div>
                                    
                                    <button type="submit" class="w-full bg-luxury-accent text-white px-4 py-2 rounded-sm hover:bg-luxury-primary transition-colors font-medium">
                                        <?= e('Update Tracking Number') ?>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($order['tracking_number']) && $order['tracking_number']): ?>
                            <div>
                                <p class="text-luxury-textLight mb-1"><?= e('Tracking Number') ?></p>
                                <p class="font-semibold text-luxury-primary"><?= e($order['tracking_number']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8">
                    <h2 class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e('Shipping Address') ?></h2>
                    <p class="text-luxury-text whitespace-pre-line leading-relaxed"><?= e($order['shipping_address']) ?></p>
                </div>
            </div>
        </div>
    </div>

    <?= modernFooter() ?>
</body>
</html>

