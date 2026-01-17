<?php
declare(strict_types=1);

/**
 * Super Admin Dashboard
 * Bloom & Vine Flower Store
 * Full system control and reporting
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';
require_once __DIR__ . '/../src/components.php';

requireAdmin();
requirePermission('view_reports');

$pdo = getDB();
$lang = getCurrentLang();
$dir = getHtmlDir();

// Get comprehensive statistics
$stats = [];

// Total statistics
$stmt = $pdo->query('SELECT COUNT(*) as total FROM orders WHERE payment_status = "paid"');
$stats['total_orders'] = (int)$stmt->fetchColumn();

$stmt = $pdo->query('SELECT COALESCE(SUM(grand_total), 0) as total FROM orders WHERE payment_status = "paid"');
$stats['total_revenue'] = (float)$stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) as total FROM products');
$stats['total_products'] = (int)$stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) as total FROM users WHERE role = "customer"');
$stats['total_customers'] = (int)$stmt->fetchColumn();

$stmt = $pdo->query('SELECT COUNT(*) as total FROM users WHERE role = "admin"');
$stats['total_admins'] = (int)$stmt->fetchColumn();

// Today's statistics
$stmt = $pdo->query('SELECT COUNT(*) as total FROM orders WHERE DATE(order_date) = CURDATE() AND payment_status = "paid"');
$stats['today_orders'] = (int)$stmt->fetchColumn();

$stmt = $pdo->query('SELECT COALESCE(SUM(grand_total), 0) as total FROM orders WHERE DATE(order_date) = CURDATE() AND payment_status = "paid"');
$stats['today_revenue'] = (float)$stmt->fetchColumn();

// This week's statistics
$stmt = $pdo->query('SELECT COUNT(*) as total FROM orders WHERE YEARWEEK(order_date) = YEARWEEK(NOW()) AND payment_status = "paid"');
$stats['week_orders'] = (int)$stmt->fetchColumn();

$stmt = $pdo->query('SELECT COALESCE(SUM(grand_total), 0) as total FROM orders WHERE YEARWEEK(order_date) = YEARWEEK(NOW()) AND payment_status = "paid"');
$stats['week_revenue'] = (float)$stmt->fetchColumn();

// This month's statistics
$stmt = $pdo->query('SELECT COUNT(*) as total FROM orders WHERE MONTH(order_date) = MONTH(NOW()) AND YEAR(order_date) = YEAR(NOW()) AND payment_status = "paid"');
$stats['month_orders'] = (int)$stmt->fetchColumn();

$stmt = $pdo->query('SELECT COALESCE(SUM(grand_total), 0) as total FROM orders WHERE MONTH(order_date) = MONTH(NOW()) AND YEAR(order_date) = YEAR(NOW()) AND payment_status = "paid"');
$stats['month_revenue'] = (float)$stmt->fetchColumn();

// Low stock products
$stmt = $pdo->query('SELECT COUNT(*) as total FROM products WHERE stock_qty < 10');
$stats['low_stock'] = (int)$stmt->fetchColumn();

// Pending orders
$stmt = $pdo->query('SELECT COUNT(*) as total FROM orders WHERE payment_status = "pending"');
$stats['pending_orders'] = (int)$stmt->fetchColumn();

// Recent activity
$stmt = $pdo->query('SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10');
$recentActivity = $stmt->fetchAll();

// Recent orders
$stmt = $pdo->query('SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC LIMIT 5');
$recentOrders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?= getLuxuryTailwindConfig() ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                
                <!-- Super Admin Header Banner -->
                <div class="bg-gradient-to-r from-red-600 via-red-700 to-purple-800 text-white rounded-3xl p-8 md:p-12 mb-8 shadow-xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-16 -mt-16 transition-transform group-hover:scale-110 duration-700"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full -ml-16 -mb-16 transition-transform group-hover:scale-110 duration-700"></div>
                    
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <div class="flex items-center gap-4 mb-4">
                                <span class="bg-red-500/30 text-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider backdrop-blur-sm border border-red-400/30">System Mode</span>
                            </div>
                            <h1 class="text-4xl md:text-5xl font-luxury font-bold mb-4">
                                <i class="fas fa-crown me-4"></i><?= e(t('super_admin_dashboard')) ?>
                            </h1>
                            <p class="text-xl text-red-100"><?= e(t('system_control')) ?></p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm px-8 py-6 rounded-2xl border border-white/20 hover:bg-white/20 transition-colors cursor-default">
                            <p class="text-sm text-red-200 mb-1"><?= e(t('total_system_revenue')) ?></p>
                            <p class="text-4xl font-bold text-white font-luxury"><?= e(formatPrice($stats['total_revenue'])) ?></p>
                        </div>
                    </div>
                </div>

                <?php
                $flash = getFlashMessage();
                if ($flash):
                    echo alert($flash['message'], $flash['type']);
                endif;
                ?>
                
                <!-- Quick Actions Grid -->
                <div class="mb-12">
                    <h2 class="text-2xl font-bold text-luxury-primary mb-6 flex items-center">
                        <span class="w-1 h-8 bg-red-600 rounded-full mr-3"></span>
                        Quick Actions
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <a href="super_admin_reports.php?period=day" class="group bg-white border border-gray-100 rounded-2xl p-6 text-center hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:bg-blue-600 transition-colors">
                                <i class="fas fa-chart-line text-xl text-blue-600 group-hover:text-white transition-colors"></i>
                            </div>
                            <span class="font-bold text-sm text-gray-700">Daily Reports</span>
                        </a>
                        <a href="super_admin_reports.php?period=week" class="group bg-white border border-gray-100 rounded-2xl p-6 text-center hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:bg-green-600 transition-colors">
                                <i class="fas fa-chart-bar text-xl text-green-600 group-hover:text-white transition-colors"></i>
                            </div>
                            <span class="font-bold text-sm text-gray-700">Weekly Reports</span>
                        </a>
                        <a href="super_admin_reports.php?period=month" class="group bg-white border border-gray-100 rounded-2xl p-6 text-center hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                            <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:bg-purple-600 transition-colors">
                                <i class="fas fa-chart-pie text-xl text-purple-600 group-hover:text-white transition-colors"></i>
                            </div>
                            <span class="font-bold text-sm text-gray-700">Monthly Reports</span>
                        </a>
                        <a href="super_admin_users.php" class="group bg-white border border-gray-100 rounded-2xl p-6 text-center hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                            <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:bg-orange-600 transition-colors">
                                <i class="fas fa-users text-xl text-orange-600 group-hover:text-white transition-colors"></i>
                            </div>
                            <span class="font-bold text-sm text-gray-700">Manage Users</span>
                        </a>
                        <a href="super_admin_admins.php" class="group bg-white border border-gray-100 rounded-2xl p-6 text-center hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                            <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:bg-red-600 transition-colors">
                                <i class="fas fa-user-shield text-xl text-red-600 group-hover:text-white transition-colors"></i>
                            </div>
                            <span class="font-bold text-sm text-gray-700">Manage Admins</span>
                        </a>
                        <a href="super_admin_settings.php" class="group bg-white border border-gray-100 rounded-2xl p-6 text-center hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                            <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:bg-indigo-600 transition-colors">
                                <i class="fas fa-cog text-xl text-indigo-600 group-hover:text-white transition-colors"></i>
                            </div>
                            <span class="font-bold text-sm text-gray-700"><?= e(t('system_settings')) ?></span>
                        </a>
                    </div>
                </div>
                
                <!-- Overview Stats -->
                <div class="mb-12">
                    <h2 class="text-2xl font-bold text-luxury-primary mb-6 flex items-center">
                        <span class="w-1 h-8 bg-red-600 rounded-full mr-3"></span>
                        <?= e(t('overview_statistics')) ?>
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        <?= statsCard('Total Orders', (string)$stats['total_orders'], 'fas fa-shopping-bag text-3xl', 'blue') ?>
                        <?= statsCard('Total Revenue', formatPrice($stats['total_revenue']), 'fas fa-dollar-sign text-3xl', 'green') ?>
                        <?= statsCard('Total Products', (string)$stats['total_products'], 'fas fa-box text-3xl', 'purple') ?>
                        <?= statsCard('Total Customers', (string)$stats['total_customers'], 'fas fa-users text-3xl', 'orange') ?>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <?= statsCard(t('todays_revenue'), formatPrice($stats['today_revenue']), 'fas fa-calendar-day text-3xl', 'gold') ?>
                        <?= statsCard(t('week_revenue'), formatPrice($stats['week_revenue']), 'fas fa-calendar-week text-3xl', 'green') ?>
                        <?= statsCard(t('month_revenue'), formatPrice($stats['month_revenue']), 'fas fa-calendar-alt text-3xl', 'purple') ?>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?= statsCard(t('low_stock_items'), (string)$stats['low_stock'], 'fas fa-exclamation-triangle text-3xl', 'orange') ?>
                        <?= statsCard(t('pending_orders'), (string)$stats['pending_orders'], 'fas fa-clock text-3xl', 'blue') ?>
                        <?= statsCard(t('total_admins'), (string)$stats['total_admins'], 'fas fa-user-shield text-3xl', 'purple') ?>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
                    <!-- Recent Orders -->
                    <div class="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                            <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-shopping-cart text-red-600 me-2"></i>Recent Orders</h2>
                            <a href="../order_details.php" class="text-red-600 font-semibold hover:text-red-700 text-sm">View All Orders &rarr;</a>
                        </div>
                        <div class="p-6">
                            <?php if (empty($recentOrders)): ?>
                                <p class="text-center text-gray-400 py-8">No recent orders</p>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($recentOrders as $order): ?>
                                        <div class="flex items-center justify-between p-4 rounded-xl hover:bg-gray-50 transition-colors border border-transparent hover:border-gray-100">
                                            <div class="flex items-center gap-4">
                                                <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-600 font-bold">
                                                    <?= e(strtoupper(substr($order['full_name'], 0, 1))) ?>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-gray-800">#<?= e((string)$order['id']) ?> - <?= e($order['full_name']) ?></p>
                                                    <p class="text-xs text-gray-400"><?= e(date('M d, Y H:i', strtotime($order['order_date']))) ?></p>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <p class="font-bold text-green-600"><?= e(formatPrice((float)$order['grand_total'])) ?></p>
                                                <span class="inline-flex px-2 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full <?= $order['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                                    <?= e($order['payment_status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                            <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-history text-purple-600 me-2"></i><?= e(t('recent_activity')) ?></h2>
                            <span class="text-xs text-gray-400">Latest 10 Events</span>
                        </div>
                        <div class="p-6">
                            <?php if (empty($recentActivity)): ?>
                                <p class="text-center text-gray-400 py-8"><?= e(t('no_recent_activity')) ?></p>
                            <?php else: ?>
                                <div class="space-y-0 relative">
                                    <!-- Timeline line -->
                                    <div class="absolute left-6 top-2 bottom-2 w-0.5 bg-gray-100"></div>
                                    
                                    <?php foreach ($recentActivity as $activity): ?>
                                        <div class="relative pl-12 pb-6 last:pb-0">
                                            <div class="absolute left-4 top-1 w-4 h-4 rounded-full bg-purple-100 border-2 border-purple-500 transform -translate-x-1/2"></div>
                                            <p class="font-bold text-gray-800 text-sm"><?= e(ucfirst(str_replace('_', ' ', $activity['action']))) ?></p>
                                            <?php if ($activity['description']): ?>
                                                <p class="text-xs text-gray-500 mt-0.5"><?= e($activity['description']) ?></p>
                                            <?php endif; ?>
                                            <p class="text-[10px] text-gray-400 mt-1">
                                                <?= e(time_ago($activity['created_at'])) ?>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
            
            <!-- Footer -->
            <?php include __DIR__ . '/footer.php'; ?>
        </div>
    </div>
</body>
</html>
