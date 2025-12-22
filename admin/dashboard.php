<?php
declare(strict_types=1);

/**
 * Admin Dashboard
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';
require_once __DIR__ . '/../src/components.php';

requireAdmin();

$pdo = getDB();

// Get statistics
$stmt = $pdo->query('SELECT COUNT(*) as total_orders, COALESCE(SUM(grand_total), 0) as total_sales FROM orders WHERE payment_status = "paid"');
$stats = $stmt->fetch();

$totalOrders = (int)$stats['total_orders'];
$totalSales = (float)$stats['total_sales'];

$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('admin_dashboard')) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/../src/header.php'; ?>

    <!-- Admin Header -->
    <div class="bg-gradient-to-r from-purple-600 via-purple-700 to-indigo-800 text-white py-16">
        <div class="container mx-auto px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-5xl font-luxury font-bold mb-4">
                        <i class="fas fa-chart-line mr-4"></i><?= e(t('admin_dashboard')) ?>
                    </h1>
                    <p class="text-xl text-purple-200">Welcome back, <?= e($_SESSION['full_name'] ?? 'Admin') ?>! Here's your store overview.</p>
                </div>
                <div class="hidden md:block">
                    <div class="bg-white/10 backdrop-blur-sm px-8 py-6 rounded-2xl">
                        <p class="text-sm text-purple-200 mb-1">Total Revenue</p>
                        <p class="text-4xl font-bold text-white font-luxury"><?= e(formatPrice($totalSales)) ?></p>
                    </div>
                </div>
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
        
        <!-- Quick Actions -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-luxury-primary mb-6"><i class="fas fa-bolt mr-2 text-purple-600"></i>Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="add_product.php" class="group bg-white border-2 border-luxury-border hover:border-purple-600 rounded-2xl p-6 text-center transition-all duration-300 hover:shadow-xl transform hover:-translate-y-1">
                    <div class="w-16 h-16 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-600 transition-colors">
                        <i class="fas fa-plus text-3xl text-purple-600 group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="font-bold text-luxury-primary">Add Product</h3>
                </a>
                <a href="products.php" class="group bg-white border-2 border-luxury-border hover:border-green-600 rounded-2xl p-6 text-center transition-all duration-300 hover:shadow-xl transform hover:-translate-y-1">
                    <div class="w-16 h-16 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:bg-green-600 transition-colors">
                        <i class="fas fa-box text-3xl text-green-600 group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="font-bold text-luxury-primary">Manage Products</h3>
                </a>
                <a href="categories.php" class="group bg-white border-2 border-luxury-border hover:border-orange-600 rounded-2xl p-6 text-center transition-all duration-300 hover:shadow-xl transform hover:-translate-y-1">
                    <div class="w-16 h-16 bg-orange-100 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:bg-orange-600 transition-colors">
                        <i class="fas fa-tags text-3xl text-orange-600 group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="font-bold text-luxury-primary">Categories</h3>
                </a>
                <a href="../index.php" class="group bg-white border-2 border-luxury-border hover:border-blue-600 rounded-2xl p-6 text-center transition-all duration-300 hover:shadow-xl transform hover:-translate-y-1">
                    <div class="w-16 h-16 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-600 transition-colors">
                        <i class="fas fa-store text-3xl text-blue-600 group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="font-bold text-luxury-primary">View Store</h3>
                </a>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-luxury-primary mb-6"><i class="fas fa-chart-bar mr-2 text-purple-600"></i>Statistics</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?= statsCard('Total Orders', (string)$totalOrders, 'fas fa-shopping-bag text-3xl', 'blue') ?>
                <?= statsCard('Total Revenue', formatPrice($totalSales), 'fas fa-dollar-sign text-3xl', 'green') ?>
                <?= statsCard('Total Products', (string)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(), 'fas fa-box text-3xl', 'purple') ?>
                <?= statsCard('Total Customers', (string)$pdo->query('SELECT COUNT(*) FROM users WHERE role = "customer"')->fetchColumn(), 'fas fa-users text-3xl', 'orange') ?>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-4">
                <h2 class="text-2xl font-bold"><i class="fas fa-shopping-cart mr-2"></i><?= e(t('admin_orders')) ?></h2>
                <p class="text-purple-200 text-sm">Recent orders from your customers</p>
            </div>
            
            <?php
            $stmt = $pdo->query('SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC LIMIT 10');
            $orders = $stmt->fetchAll();
            
            if (empty($orders)):
            ?>
                <div class="p-16 text-center">
                    <div class="w-32 h-32 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-shopping-cart text-6xl text-purple-300"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-luxury-primary mb-3"><?= e('No orders yet.') ?></h3>
                    <p class="text-luxury-textLight">Orders will appear here once customers start purchasing.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-purple-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-purple-900 uppercase tracking-wider"><?= e('Order ID') ?></th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-purple-900 uppercase tracking-wider"><?= e('Customer') ?></th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-purple-900 uppercase tracking-wider"><?= e('Total') ?></th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-purple-900 uppercase tracking-wider"><?= e('Status') ?></th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-purple-900 uppercase tracking-wider"><?= e('Order Date') ?></th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-purple-900 uppercase tracking-wider"><?= e('Delivery Date') ?></th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-purple-900 uppercase tracking-wider"><?= e('Actions') ?></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-luxury-border">
                            <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-purple-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-bold text-purple-600">#<?= e((string)$order['id']) ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                            <i class="fas fa-user text-purple-600"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-luxury-primary"><?= e($order['full_name']) ?></div>
                                            <div class="text-xs text-luxury-textLight"><?= e($order['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-lg font-bold text-green-600"><?= e(formatPrice((float)$order['grand_total'])) ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-2">
                                        <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full <?= $order['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                            <i class="fas fa-<?= $order['payment_status'] === 'paid' ? 'check-circle' : 'clock' ?> mr-1"></i>
                                            <?= e(ucfirst($order['payment_status'])) ?>
                                        </span>
                                        <?php if (isset($order['payment_method']) && $order['payment_method']): ?>
                                            <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-full bg-purple-100 text-purple-800">
                                                <?php if ($order['payment_method'] === 'visa'): ?>
                                                    <span class="w-6 h-4 bg-blue-600 rounded text-white text-xs flex items-center justify-center font-bold">V</span>
                                                <?php elseif ($order['payment_method'] === 'mastercard'): ?>
                                                    <span class="w-6 h-4 bg-red-600 rounded text-white text-xs flex items-center justify-center font-bold">MC</span>
                                                <?php endif; ?>
                                                <?php if (isset($order['card_last_four']) && $order['card_last_four']): ?>
                                                    •••• <?= e($order['card_last_four']) ?>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (isset($order['order_status'])): ?>
                                            <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full bg-blue-100 text-blue-800">
                                                <i class="fas fa-truck mr-1"></i>
                                                <?= e(ucfirst($order['order_status'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-luxury-textLight">
                                    <i class="fas fa-calendar mr-1"></i>
                                    <?= e(date('M d, Y', strtotime($order['order_date']))) ?>
                                    <br>
                                    <i class="fas fa-clock mr-1"></i>
                                    <?= e(date('H:i', strtotime($order['order_date']))) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if (isset($order['delivery_date']) && $order['delivery_date']): ?>
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-truck text-purple-600"></i>
                                            <div>
                                                <div class="font-semibold text-luxury-primary">
                                                    <?= e(date('M d, Y', strtotime($order['delivery_date']))) ?>
                                                </div>
                                                <?php
                                                $daysUntilDelivery = (strtotime($order['delivery_date']) - time()) / (60 * 60 * 24);
                                                if ($daysUntilDelivery > 0):
                                                ?>
                                                    <div class="text-xs text-blue-600">
                                                        <?= e(sprintf('In %d day(s)', (int)ceil($daysUntilDelivery))) ?>
                                                    </div>
                                                <?php elseif ($daysUntilDelivery <= 0 && $daysUntilDelivery > -1): ?>
                                                    <div class="text-xs text-green-600 font-semibold">
                                                        <i class="fas fa-check-circle mr-1"></i><?= e('Today') ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-xs text-green-600 font-semibold">
                                                        <i class="fas fa-check-circle mr-1"></i><?= e('Delivered') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-luxury-textLight italic"><?= e('Not set') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="../order_details.php?id=<?= e((string)$order['id']) ?>" 
                                       class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-xl transition-all duration-300 font-semibold shadow-md hover:shadow-lg">
                                        <i class="fas fa-eye"></i>
                                        <?= e('View') ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?= modernFooter() ?>
</body>
</html>

