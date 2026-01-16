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

// Get order statistics
$stats = [
    'pending' => $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn(),
    'processing' => $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'processing'")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'delivered'")->fetchColumn(),
    'revenue' => $pdo->query("SELECT SUM(grand_total) FROM orders WHERE payment_status = 'paid'")->fetchColumn()
];

// Fallback to 0 if null (e.g. no revenue yet)
$stats['revenue'] = $stats['revenue'] ?: 0;

$totalSales = (float)$stats['revenue'];

$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('admin_dashboard')) ?> - Bloom & Vine</title>
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
            <!-- Top Header -->
            <?php include __DIR__ . '/header.php'; ?>

            <!-- Main Content -->
            <main class="flex-1 p-4 md:p-8">
                <!-- Admin Welcome Banner -->
                <div class="bg-gradient-to-r from-purple-600 via-purple-700 to-indigo-800 text-white rounded-3xl p-8 md:p-12 mb-8 shadow-xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-16 -mt-16 transition-transform group-hover:scale-110 duration-700"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full -ml-16 -mb-16 transition-transform group-hover:scale-110 duration-700"></div>
                    
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <h1 class="text-4xl md:text-5xl font-luxury font-bold mb-4">
                                <i class="fas fa-chart-line me-4"></i><?= e(t('admin_dashboard')) ?>
                            </h1>
                            <p class="text-xl text-purple-200"><?= e(t('admin_welcome', ['name' => $_SESSION['full_name'] ?? 'Admin'])) ?></p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm px-8 py-6 rounded-2xl border border-white/20 hover:bg-white/20 transition-colors cursor-default">
                            <p class="text-sm text-purple-200 mb-1"><?= e(t('total_revenue')) ?></p>
                            <p class="text-4xl font-bold text-white font-luxury"><?= e(formatPrice($totalSales)) ?></p>
                        </div>
                    </div>
                </div>

                <?php
                $flash = getFlashMessage();
                if ($flash):
                    echo alert($flash['message'], $flash['type']);
                endif;
                ?>
                
                <!-- Quick Actions -->
                <div class="mb-12">
                    <h2 class="text-2xl font-bold text-luxury-primary mb-6 flex items-center">
                        <span class="w-1 h-8 bg-purple-600 rounded-full mr-3"></span>
                        <?= e(t('quick_actions')) ?>
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                        <a href="add_product.php" class="group bg-white border border-gray-100 rounded-2xl p-6 text-center hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                            <div class="w-16 h-16 bg-purple-50 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-600 transition-colors duration-300">
                                <i class="fas fa-plus text-2xl text-purple-600 group-hover:text-white transition-colors duration-300"></i>
                            </div>
                            <h3 class="font-bold text-gray-700 group-hover:text-purple-600 transition-colors"><?= e(t('admin_add_product')) ?></h3>
                        </a>
                        <a href="products.php" class="group bg-white border border-gray-100 rounded-2xl p-6 text-center hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                            <div class="w-16 h-16 bg-green-50 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-green-600 transition-colors duration-300">
                                <i class="fas fa-box text-2xl text-green-600 group-hover:text-white transition-colors duration-300"></i>
                            </div>
                            <h3 class="font-bold text-gray-700 group-hover:text-green-600 transition-colors"><?= e(t('manage_products')) ?></h3>
                        </a>
                        <a href="categories.php" class="group bg-white border border-gray-100 rounded-2xl p-6 text-center hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                            <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-600 transition-colors duration-300">
                                <i class="fas fa-list text-2xl text-blue-600 group-hover:text-white transition-colors duration-300"></i>
                            </div>
                            <h3 class="font-bold text-gray-700 group-hover:text-blue-600 transition-colors"><?= e(t('manage_categories')) ?></h3>
                        </a>
                        <a href="super_admin_reports.php" class="group bg-white border border-gray-100 rounded-2xl p-6 text-center hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                            <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:bg-indigo-600 transition-colors duration-300">
                                <i class="fas fa-chart-bar text-2xl text-indigo-600 group-hover:text-white transition-colors duration-300"></i>
                            </div>
                            <h3 class="font-bold text-gray-700 group-hover:text-indigo-600 transition-colors"><?= e(t('view_reports')) ?></h3>
                        </a>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <?= statsCard(t('pending_orders'), (string)$stats['pending'], 'fas fa-clock', 'orange') ?>
                    <?= statsCard(t('processing_orders'), (string)$stats['processing'], 'fas fa-spinner', 'blue') ?>
                    <?= statsCard(t('completed_orders'), (string)$stats['completed'], 'fas fa-check-circle', 'green') ?>
                    <?= statsCard(t('total_revenue'), formatPrice((float)$stats['revenue']), 'fas fa-wallet', 'purple') ?>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-history text-purple-600"></i>
                            <?= e(t('recent_orders')) ?>
                        </h2>
                        <a href="orders.php" class="text-purple-600 hover:text-purple-700 font-bold text-sm flex items-center gap-1">
                            <?= e(t('view_all')) ?> <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('order_id')) ?></th>
                                    <th class="px-6 py-3 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('customer')) ?></th>
                                    <th class="px-6 py-3 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('total')) ?></th>
                                    <th class="px-6 py-3 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('status')) ?></th>
                                    <th class="px-6 py-3 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('date')) ?></th>
                                    <th class="px-6 py-3 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('action')) ?></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (empty($recentOrders)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                            <i class="fas fa-box-open text-4xl mb-2"></i>
                                            <p><?= e(t('no_orders_found')) ?></p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr class="hover:bg-purple-50/10 transition-colors">
                                            <td class="px-6 py-4 font-semibold text-gray-800">#<?= e((string)$order['id']) ?></td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-bold text-xs me-3">
                                                        <?= e(strtoupper(substr($order['guest_name'] ?? $order['user_name'] ?? 'Guest', 0, 1))) ?>
                                                    </div>
                                                    <span class="text-sm font-medium text-gray-700"><?= e($order['guest_name'] ?? $order['user_name'] ?? 'Guest') ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 font-bold text-green-600"><?= e(formatPrice((float)$order['grand_total'])) ?></td>
                                            <td class="px-6 py-4">
                                                <?php
                                                $statusColors = [
                                                    'pending' => 'bg-orange-100 text-orange-700',
                                                    'processing' => 'bg-blue-100 text-blue-700',
                                                    'shipped' => 'bg-purple-100 text-purple-700',
                                                    'completed' => 'bg-green-100 text-green-700',
                                                    'cancelled' => 'bg-red-100 text-red-700'
                                                ];
                                                $statusClass = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-700';
                                                ?>
                                                <span class="<?= $statusClass ?> px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide">
                                                    <?= e(ucfirst($order['status'])) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                <?= e(date('M d, H:i', strtotime($order['created_at']))) ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <a href="order_details.php?id=<?= $order['id'] ?>" class="text-purple-600 hover:text-purple-800 transition-colors font-semibold text-sm">
                                                    <?= e(t('details')) ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <?php include __DIR__ . '/footer.php'; ?>
        </div>
    </div>
</body>
</html>
