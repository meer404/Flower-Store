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

requireSuperAdmin();

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
    <?php include __DIR__ . '/../src/header.php'; ?>

    <!-- Super Admin Header -->
    <div class="bg-gradient-to-r from-red-600 via-red-700 to-purple-800 text-white py-16">
        <div class="container mx-auto px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-5xl font-luxury font-bold mb-4">
                        <i class="fas fa-crown me-4"></i><?= e(t('super_admin_dashboard')) ?>
                    </h1>
                    <p class="text-xl text-red-200"><?= e(t('system_control')) ?></p>
                    <p class="text-sm text-red-300 mt-2"><?= e(t('admin_welcome', ['name' => $_SESSION['full_name'] ?? 'Super Admin'])) ?></p>
                </div>
                <div class="hidden md:block">
                    <div class="bg-white/10 backdrop-blur-sm px-8 py-6 rounded-2xl">
                        <p class="text-sm text-red-200 mb-1"><?= e(t('total_system_revenue')) ?></p>
                        <p class="text-4xl font-bold text-white font-luxury"><?= e(formatPrice($stats['total_revenue'])) ?></p>
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
            <h2 class="text-2xl font-bold text-luxury-primary mb-6"><i class="fas fa-bolt me-2 text-red-600"></i>Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <a href="super_admin_reports.php?period=day" class="group bg-white border-2 border-luxury-border hover:border-blue-600 rounded-2xl p-6 text-center transition-all duration-300 hover:shadow-xl transform hover:-translate-y-1">
                    <div class="w-16 h-16 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-600 transition-colors">
                        <i class="fas fa-chart-line text-3xl text-blue-600 group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="font-bold text-luxury-primary text-sm">Daily Reports</h3>
                </a>
                <a href="super_admin_reports.php?period=week" class="group bg-white border-2 border-luxury-border hover:border-green-600 rounded-2xl p-6 text-center transition-all duration-300 hover:shadow-xl transform hover:-translate-y-1">
                    <div class="w-16 h-16 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:bg-green-600 transition-colors">
                        <i class="fas fa-chart-bar text-3xl text-green-600 group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="font-bold text-luxury-primary text-sm">Weekly Reports</h3>
                </a>
                <a href="super_admin_reports.php?period=month" class="group bg-white border-2 border-luxury-border hover:border-purple-600 rounded-2xl p-6 text-center transition-all duration-300 hover:shadow-xl transform hover:-translate-y-1">
                    <div class="w-16 h-16 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-600 transition-colors">
                        <i class="fas fa-chart-pie text-3xl text-purple-600 group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="font-bold text-luxury-primary text-sm">Monthly Reports</h3>
                </a>
                <a href="super_admin_users.php" class="group bg-white border-2 border-luxury-border hover:border-orange-600 rounded-2xl p-6 text-center transition-all duration-300 hover:shadow-xl transform hover:-translate-y-1">
                    <div class="w-16 h-16 bg-orange-100 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:bg-orange-600 transition-colors">
                        <i class="fas fa-users text-3xl text-orange-600 group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="font-bold text-luxury-primary text-sm">Manage Users</h3>
                </a>
                <a href="super_admin_admins.php" class="group bg-white border-2 border-luxury-border hover:border-red-600 rounded-2xl p-6 text-center transition-all duration-300 hover:shadow-xl transform hover:-translate-y-1">
                    <div class="w-16 h-16 bg-red-100 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:bg-red-600 transition-colors">
                        <i class="fas fa-user-shield text-3xl text-red-600 group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="font-bold text-luxury-primary text-sm">Manage Admins</h3>
                </a>
                <a href="super_admin_settings.php" class="group bg-white border-2 border-luxury-border hover:border-indigo-600 rounded-2xl p-6 text-center transition-all duration-300 hover:shadow-xl transform hover:-translate-y-1">
                    <div class="w-16 h-16 bg-indigo-100 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:bg-indigo-600 transition-colors">
                        <i class="fas fa-cog text-3xl text-indigo-600 group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="font-bold text-luxury-primary text-sm"><?= e(t('system_settings')) ?></h3>
                </a>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-luxury-primary mb-6"><i class="fas fa-chart-bar me-2 text-red-600"></i><?= e(t('overview_statistics')) ?></h2>
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
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-12">
            <!-- Recent Orders -->
            <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-red-600 to-purple-600 text-white px-6 py-4">
                    <h2 class="text-2xl font-bold"><i class="fas fa-shopping-cart me-2"></i>Recent Orders</h2>
                </div>
                <div class="p-6">
                    <?php if (empty($recentOrders)): ?>
                        <p class="text-center text-luxury-textLight py-8">No recent orders</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="border-b border-luxury-border pb-4 last:border-0">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-bold text-luxury-primary">Order #<?= e((string)$order['id']) ?></p>
                                            <p class="text-sm text-luxury-textLight"><?= e($order['full_name']) ?></p>
                                            <p class="text-xs text-luxury-textLight"><?= e(date('M d, Y H:i', strtotime($order['order_date']))) ?></p>
                                        </div>
                                        <div class="text-end">
                                            <p class="font-bold text-green-600"><?= e(formatPrice((float)$order['grand_total'])) ?></p>
                                            <span class="inline-flex px-2 py-1 text-xs font-bold rounded-full <?= $order['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                                <?= e(ucfirst($order['payment_status'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 text-center">
                            <a href="../order_details.php" class="text-red-600 hover:text-red-700 font-semibold">View All Orders →</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-4">
                    <h2 class="text-2xl font-bold"><i class="fas fa-history me-2"></i><?= e(t('recent_activity')) ?></h2>
                </div>
                <div class="p-6">
                    <?php if (empty($recentActivity)): ?>
                        <p class="text-center text-luxury-textLight py-8"><?= e(t('no_recent_activity')) ?></p>
                    <?php else: ?>
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="border-b border-luxury-border pb-4 last:border-0">
                                    <p class="font-bold text-luxury-primary"><?= e(ucfirst(str_replace('_', ' ', $activity['action']))) ?></p>
                                    <?php if ($activity['description']): ?>
                                        <p class="text-sm text-luxury-textLight"><?= e($activity['description']) ?></p>
                                    <?php endif; ?>
                                    <p class="text-xs text-luxury-textLight mt-1">
                                        <i class="fas fa-clock me-1"></i><?= e(date('M d, Y H:i', strtotime($activity['created_at']))) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Additional Admin Links -->
        <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl p-6">
            <h2 class="text-2xl font-bold text-luxury-primary mb-6"><i class="fas fa-link me-2 text-red-600"></i><?= e(t('admin_panel_links')) ?></h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="dashboard.php" class="text-center p-4 border-2 border-luxury-border rounded-xl hover:border-purple-600 hover:bg-purple-50 transition-all">
                    <i class="fas fa-tachometer-alt text-2xl text-purple-600 mb-2"></i>
                    <p class="font-semibold">Admin Dashboard</p>
                </a>
                <a href="products.php" class="text-center p-4 border-2 border-luxury-border rounded-xl hover:border-green-600 hover:bg-green-50 transition-all">
                    <i class="fas fa-box text-2xl text-green-600 mb-2"></i>
                    <p class="font-semibold">Products</p>
                </a>
                <a href="categories.php" class="text-center p-4 border-2 border-luxury-border rounded-xl hover:border-orange-600 hover:bg-orange-50 transition-all">
                    <i class="fas fa-tags text-2xl text-orange-600 mb-2"></i>
                    <p class="font-semibold">Categories</p>
                </a>
                <a href="<?= url('index.php') ?>" class="text-center p-4 border-2 border-luxury-border rounded-xl hover:border-blue-600 hover:bg-blue-50 transition-all">
                    <i class="fas fa-store text-2xl text-blue-600 mb-2"></i>
                    <p class="font-semibold">View Store</p>
                </a>
            </div>
        </div>
    </div>

    <?= modernFooter() ?>
</body>
</html>

