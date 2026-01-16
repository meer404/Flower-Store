<?php
declare(strict_types=1);

/**
 * Order Details Page (Admin)
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';
require_once __DIR__ . '/../src/components.php';

requireAdmin();
// Check if user has permission to view orders
if (!isSuperAdmin() && !hasPermission('view_orders')) {
    redirect('dashboard.php', t('no_permission'), 'error');
}

$pdo = getDB();
$id = (int)sanitizeInput('id', 'GET', '0');

if ($id <= 0) {
    redirect('orders.php', t('invalid_order'), 'error');
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isSuperAdmin() || hasPermission('manage_orders'))) {
    // Generate CSRF token if not exists (should implement properly in forms)
    // For now assuming simple POST check
    
    $newStatus = sanitizeInput('order_status');
    $newPaymentStatus = sanitizeInput('payment_status');
    $trackingNumber = sanitizeInput('tracking_number');
    
    // Update order
    $updateStmt = $pdo->prepare('UPDATE orders SET order_status = :status, payment_status = :payment_status, tracking_number = :tracking_number WHERE id = :id');
    try {
        $updateStmt->execute([
            'status' => $newStatus,
            'payment_status' => $newPaymentStatus,
            'tracking_number' => $trackingNumber,
            'id' => $id
        ]);
        
        // Log activity
        if (function_exists('logActivity')) {
            logActivity('update_order', 'order', $id, "Updated order #{$id} status to {$newStatus}");
        }
        
        // Notify user if status changed
        // We'd need to fetch user_id first, doing it below in fetch
        
        $message = t('order_updated_success', [], 'Order updated successfully');
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = 'success';
        
        // Refresh to show changes
        header("Location: order_details.php?id={$id}");
        exit;
    } catch (PDOException $e) {
        $error = t('database_error');
    }
}

// Fetch Order
$stmt = $pdo->prepare('SELECT o.*, u.full_name as user_name, u.email as user_email 
                       FROM orders o 
                       LEFT JOIN users u ON o.user_id = u.id 
                       WHERE o.id = :id');
$stmt->execute(['id' => $id]);
$order = $stmt->fetch();

if (!$order) {
    redirect('orders.php', t('order_not_found'), 'error');
}

// Fetch Order Items
$stmt = $pdo->prepare('
    SELECT oi.*, p.name_en, p.name_ku, p.image_url 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = :id
');
$stmt->execute(['id' => $id]);
$items = $stmt->fetchAll();

$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('order_details')) ?> #<?= e((string)$order['id']) ?> - Bloom & Vine</title>
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
                        <div class="flex items-center gap-3 mb-2">
                            <a href="orders.php" class="text-gray-400 hover:text-blue-600 transition-colors">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <h1 class="text-3xl font-luxury font-bold text-gray-800">
                                <?= e(t('order_number_prefix') . $order['id']) ?>
                            </h1>
                            <?php
                            $statusColors = [
                                'pending' => 'bg-orange-100 text-orange-700',
                                'processing' => 'bg-blue-100 text-blue-700',
                                'shipped' => 'bg-purple-100 text-purple-700',
                                'delivered' => 'bg-green-100 text-green-700',
                                'cancelled' => 'bg-red-100 text-red-700'
                            ];
                            $orderStatus = strtolower($order['order_status'] ?? 'pending');
                            $statusClass = $statusColors[$orderStatus] ?? 'bg-gray-100 text-gray-700';
                            ?>
                            <span class="<?= $statusClass ?> px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide">
                                <?= e(ucfirst($orderStatus)) ?>
                            </span>
                        </div>
                        <p class="text-gray-500">
                            <?= e(date('F j, Y \a\t g:i A', strtotime($order['order_date']))) ?>
                        </p>
                    </div>
                    
                    <div class="flex gap-2">
			<a href="javascript:window.print()" class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-blue-600 transition-all font-semibold text-sm shadow-sm flex items-center gap-2">
                            <i class="fas fa-print"></i> <?= e(t('print') ?? 'Print') ?>
                        </a>
                    </div>
                </div>

                <?php
                $flash = getFlashMessage();
                if ($flash):
                    echo alert($flash['message'], $flash['type']);
                endif;
                ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left Column: Order Items & Totals -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Items -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-shopping-bag text-blue-600"></i><?= e(t('order_items')) ?>
                            </div>
                            <div class="divide-y divide-gray-100">
                                <?php foreach ($items as $item): ?>
                                    <div class="p-4 flex items-center gap-4 hover:bg-gray-50 transition-colors">
                                        <div class="w-16 h-16 rounded-lg bg-gray-100 overflow-hidden shrink-0">
                                            <?php if ($item['image_url']): ?>
                                                <img src="<?= e(url($item['image_url'])) ?>" alt="<?= e(getProductName(['name_en'=>$item['name_en'], 'name_ku'=>$item['name_ku']])) ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-bold text-gray-800 truncate">
                                                <?= e(getProductName(['name_en'=>$item['name_en'], 'name_ku'=>$item['name_ku']])) ?>
                                            </h4>
                                            <p class="text-sm text-gray-500">
                                                <?= e(formatPrice((float)$item['unit_price'])) ?> x <?= e((string)$item['quantity']) ?>
                                            </p>
                                        </div>
                                        <div class="font-bold text-gray-800">
                                            <?= e(formatPrice((float)$item['unit_price'] * (int)$item['quantity'])) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Totals -->
                            <div class="bg-gray-50 p-6 space-y-3">
                                <div class="flex justify-between text-gray-600">
                                    <span><?= e(t('subtotal')) ?></span>
                                    <span><?= e(formatPrice((float)$order['grand_total'])) // Simplified as we don't store subtotal/tax separate in order table generally ?></span>
                                </div>
                                <div class="flex justify-between text-gray-600">
                                    <span><?= e(t('shipping') ?? 'Shipping') ?></span>
                                    <span class="text-green-600"><?= e(t('free') ?? 'Free') ?></span>
                                </div>
                                <div class="flex justify-between font-bold text-xl text-gray-800 border-t border-gray-200 pt-3">
                                    <span><?= e(t('total')) ?></span>
                                    <span class="text-blue-600"><?= e(formatPrice((float)$order['grand_total'])) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Info & Actions -->
                    <div class="space-y-8">
                        <!-- Customer Info -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-user text-blue-600"></i><?= e(t('customer_info')) ?>
                            </div>
                            <div class="p-6 space-y-4">
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1"><?= e(t('name')) ?></h4>
                                    <p class="font-medium text-gray-800"><?= e($order['guest_name'] ?? $order['user_name'] ?? 'Guest') ?></p>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1"><?= e(t('email')) ?></h4>
                                    <p class="font-medium text-gray-800"><?= e($order['guest_email'] ?? $order['user_email'] ?? 'N/A') ?></p>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1"><?= e(t('shipping_address')) ?></h4>
                                    <p class="font-medium text-gray-800">
                                        <?= nl2br(e($order['shipping_address'] ?? 'No address provided')) ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Actions -->
                        <?php if (isSuperAdmin() || hasPermission('manage_orders')): ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-cog text-blue-600"></i><?= e(t('admin_actions')) ?>
                            </div>
                            <div class="p-6">
                                <form method="POST" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1"><?= e(t('order_status_label')) ?></label>
                                        <select name="order_status" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50">
                                            <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status): ?>
                                                <option value="<?= $status ?>" <?= strtolower($order['order_status']) === $status ? 'selected' : '' ?>>
                                                    <?= e(ucfirst($status)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1"><?= e(t('payment_status_label')) ?></label>
                                        <select name="payment_status" class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50">
                                            <?php foreach (['paid', 'unpaid', 'refunded'] as $status): ?>
                                                <option value="<?= $status ?>" <?= strtolower($order['payment_status']) === $status ? 'selected' : '' ?>>
                                                    <?= e(ucfirst($status)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1"><?= e(t('tracking_number')) ?></label>
                                        <input type="text" name="tracking_number" 
                                               value="<?= e($order['tracking_number'] ?? '') ?>"
                                               class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50"
                                               placeholder="<?= e(t('enter_tracking_number')) ?>">
                                    </div>

                                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded-lg hover:bg-blue-700 transition-colors shadow-lg shadow-blue-200">
                                        <?= e(t('update_order_status')) ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
                        
            <!-- Footer -->
            <?php include __DIR__ . '/footer.php'; ?>
        </div>
    </div>
</body>
</html>
