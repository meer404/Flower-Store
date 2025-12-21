<?php
declare(strict_types=1);

/**
 * Super Admin Reports
 * Daily, Weekly, Monthly Reports
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';
require_once __DIR__ . '/../src/components.php';

requireSuperAdmin();

$pdo = getDB();
$lang = getCurrentLang();
$dir = getHtmlDir();

$period = sanitizeInput('period', 'GET', 'month');
if (!in_array($period, ['day', 'week', 'month', 'year'])) {
    $period = 'month';
}

$report = getSalesReport($period);
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(ucfirst($period)) ?> Reports - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?= getLuxuryTailwindConfig() ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/../src/header.php'; ?>

    <div class="bg-gradient-to-r from-red-600 via-red-700 to-purple-800 text-white py-12">
        <div class="container mx-auto px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-luxury font-bold mb-2">
                        <i class="fas fa-chart-line mr-4"></i><?= e(ucfirst($period)) ?> Reports
                    </h1>
                    <p class="text-red-200">Comprehensive sales and analytics</p>
                </div>
                <div class="flex gap-2">
                    <a href="?period=day" class="px-4 py-2 rounded-lg <?= $period === 'day' ? 'bg-white text-red-600' : 'bg-white/20 hover:bg-white/30' ?> transition-all">
                        Daily
                    </a>
                    <a href="?period=week" class="px-4 py-2 rounded-lg <?= $period === 'week' ? 'bg-white text-red-600' : 'bg-white/20 hover:bg-white/30' ?> transition-all">
                        Weekly
                    </a>
                    <a href="?period=month" class="px-4 py-2 rounded-lg <?= $period === 'month' ? 'bg-white text-red-600' : 'bg-white/20 hover:bg-white/30' ?> transition-all">
                        Monthly
                    </a>
                    <a href="?period=year" class="px-4 py-2 rounded-lg <?= $period === 'year' ? 'bg-white text-red-600' : 'bg-white/20 hover:bg-white/30' ?> transition-all">
                        Yearly
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <?php
            $totalRevenue = array_sum(array_column($report['sales_data'], 'total_revenue'));
            $totalOrders = array_sum(array_column($report['sales_data'], 'total_orders'));
            $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
            $paidRevenue = array_sum(array_column($report['sales_data'], 'paid_revenue'));
            ?>
            <?= statsCard('Total Revenue', formatPrice($totalRevenue), 'fas fa-dollar-sign text-3xl', 'green') ?>
            <?= statsCard('Total Orders', (string)$totalOrders, 'fas fa-shopping-bag text-3xl', 'blue') ?>
            <?= statsCard('Paid Revenue', formatPrice($paidRevenue), 'fas fa-check-circle text-3xl', 'purple') ?>
            <?= statsCard('Avg Order Value', formatPrice($avgOrderValue), 'fas fa-chart-bar text-3xl', 'orange') ?>
        </div>

        <!-- Sales Chart -->
        <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl p-6 mb-8">
            <h2 class="text-2xl font-bold text-luxury-primary mb-6">
                <i class="fas fa-chart-line mr-2 text-red-600"></i>Sales Trend
            </h2>
            <canvas id="salesChart" height="80"></canvas>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Sales Data Table -->
            <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-4">
                    <h2 class="text-2xl font-bold"><i class="fas fa-table mr-2"></i>Sales Breakdown</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-blue-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase">Period</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase">Orders</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase">Revenue</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase">Avg Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-luxury-border">
                            <?php if (empty($report['sales_data'])): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-luxury-textLight">No data available</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($report['sales_data'] as $data): ?>
                                    <tr class="hover:bg-blue-50">
                                        <td class="px-6 py-4 font-semibold"><?= e($data['period']) ?></td>
                                        <td class="px-6 py-4"><?= e((string)$data['total_orders']) ?></td>
                                        <td class="px-6 py-4 font-bold text-green-600"><?= e(formatPrice((float)$data['total_revenue'])) ?></td>
                                        <td class="px-6 py-4"><?= e(formatPrice((float)$data['avg_order_value'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Products -->
            <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-emerald-600 text-white px-6 py-4">
                    <h2 class="text-2xl font-bold"><i class="fas fa-star mr-2"></i>Top Products</h2>
                </div>
                <div class="p-6">
                    <?php if (empty($report['top_products'])): ?>
                        <p class="text-center text-luxury-textLight py-8">No product data available</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($report['top_products'] as $index => $product): ?>
                                <div class="border-b border-luxury-border pb-4 last:border-0">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-full flex items-center justify-center text-white font-bold">
                                                <?= e((string)($index + 1)) ?>
                                            </div>
                                            <div>
                                                <p class="font-bold text-luxury-primary"><?= e(getProductName($product)) ?></p>
                                                <p class="text-sm text-luxury-textLight">Sold: <?= e((string)$product['total_sold']) ?> units</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-green-600"><?= e(formatPrice((float)$product['total_revenue'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Customer Statistics -->
        <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl p-6 mt-6">
            <h2 class="text-2xl font-bold text-luxury-primary mb-6">
                <i class="fas fa-users mr-2 text-purple-600"></i>Customer Statistics
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center p-6 bg-purple-50 rounded-xl">
                    <i class="fas fa-users text-4xl text-purple-600 mb-4"></i>
                    <p class="text-3xl font-bold text-luxury-primary"><?= e((string)$report['customer_stats']['total_customers']) ?></p>
                    <p class="text-luxury-textLight">Total Customers</p>
                </div>
                <div class="text-center p-6 bg-blue-50 rounded-xl">
                    <i class="fas fa-user-plus text-4xl text-blue-600 mb-4"></i>
                    <p class="text-3xl font-bold text-luxury-primary"><?= e((string)$report['customer_stats']['new_customers']) ?></p>
                    <p class="text-luxury-textLight">New Customers</p>
                </div>
                <div class="text-center p-6 bg-green-50 rounded-xl">
                    <i class="fas fa-shopping-cart text-4xl text-green-600 mb-4"></i>
                    <p class="text-3xl font-bold text-luxury-primary"><?= e($totalOrders > 0 ? number_format($totalOrders / max($report['customer_stats']['total_customers'], 1), 2) : '0') ?></p>
                    <p class="text-luxury-textLight">Avg Orders per Customer</p>
                </div>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="super_admin_dashboard.php" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl transition-all font-semibold">
                <i class="fas fa-arrow-left"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <?= modernFooter() ?>

    <script>
        // Sales Chart
        const salesData = <?= json_encode($report['sales_data']) ?>;
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: salesData.map(d => d.period).reverse(),
                datasets: [{
                    label: 'Revenue',
                    data: salesData.map(d => parseFloat(d.total_revenue)).reverse(),
                    borderColor: 'rgb(220, 38, 38)',
                    backgroundColor: 'rgba(220, 38, 38, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Orders',
                    data: salesData.map(d => parseInt(d.total_orders)).reverse(),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    </script>
</body>
</html>

