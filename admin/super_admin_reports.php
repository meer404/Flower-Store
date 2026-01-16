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
    <title><?= e(t($period)) ?> <?= e(t('reports')) ?> - Super Admin</title>
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
                <!-- Page Header -->
                <div class="bg-gradient-to-r from-red-600 via-red-700 to-purple-800 text-white rounded-3xl p-8 mb-8 shadow-xl relative overflow-hidden">
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <h1 class="text-3xl md:text-4xl font-luxury font-bold mb-2">
                                <i class="fas fa-chart-line me-3"></i><?= e(t($period)) ?> <?= e(t('reports')) ?>
                            </h1>
                            <p class="text-red-200"><?= e(t('reports_desc')) ?></p>
                        </div>
                        <div class="bg-white/10 p-1 rounded-xl flex items-center backdrop-blur-sm">
                            <?php foreach (['day' => 'Daily', 'week' => 'Weekly', 'month' => 'Monthly', 'year' => 'Yearly'] as $key => $label): ?>
                                <a href="?period=<?= $key ?>" 
                                   class="px-4 py-2 rounded-lg text-sm font-bold transition-all <?= $period === $key ? 'bg-white text-red-600 shadow-md transform scale-105' : 'text-white/80 hover:bg-white/10 hover:text-white' ?>">
                                    <?= e(t($key)) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Decorative Circles -->
                    <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-48 h-48 rounded-full bg-black/10 blur-2xl"></div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <?php
                    $totalRevenue = array_sum(array_column($report['sales_data'], 'total_revenue'));
                    $totalOrders = array_sum(array_column($report['sales_data'], 'total_orders'));
                    $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
                    $paidRevenue = array_sum(array_column($report['sales_data'], 'paid_revenue'));
                    ?>
                    <?= statsCard(t('total_revenue'), formatPrice($totalRevenue), 'fas fa-dollar-sign text-3xl', 'green') ?>
                    <?= statsCard(t('total_orders'), (string)$totalOrders, 'fas fa-shopping-bag text-3xl', 'blue') ?>
                    <?= statsCard(t('paid_revenue'), formatPrice($paidRevenue), 'fas fa-check-circle text-3xl', 'purple') ?>
                    <?= statsCard(t('avg_order_value'), formatPrice($avgOrderValue), 'fas fa-chart-bar text-3xl', 'orange') ?>
                </div>

                <!-- Sales Chart -->
                <div class="bg-white rounded-2xl shadow-xl p-6 mb-8 border border-gray-100">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-red-100 text-red-600 flex items-center justify-center">
                                <i class="fas fa-chart-area"></i>
                            </span>
                            <?= e(t('sales_trend')) ?>
                        </h2>
                    </div>
                    <div class="h-80 w-full">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Sales Data Table -->
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-table text-blue-500"></i>
                                <?= e(t('sales_breakdown')) ?>
                            </h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('period')) ?></th>
                                        <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('orders')) ?></th>
                                        <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('revenue')) ?></th>
                                        <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('avg_value')) ?></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if (empty($report['sales_data'])): ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-8 text-center text-gray-400"><?= e(t('no_data_available')) ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($report['sales_data'] as $data): ?>
                                            <tr class="hover:bg-blue-50/10 transition-colors">
                                                <td class="px-6 py-4 font-semibold text-gray-700"><?= e($data['period']) ?></td>
                                                <td class="px-6 py-4">
                                                    <span class="px-2 py-1 rounded bg-blue-50 text-blue-700 text-xs font-bold"><?= e((string)$data['total_orders']) ?></span>
                                                </td>
                                                <td class="px-6 py-4 font-bold text-green-600"><?= e(formatPrice((float)$data['total_revenue'])) ?></td>
                                                <td class="px-6 py-4 text-sm text-gray-500"><?= e(formatPrice((float)$data['avg_order_value'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-crown text-amber-500"></i>
                                <?= e(t('top_products')) ?>
                            </h2>
                        </div>
                        <div class="p-0">
                            <?php if (empty($report['top_products'])): ?>
                                <p class="text-center text-gray-400 py-8"><?= e(t('no_product_data')) ?></p>
                            <?php else: ?>
                                <div class="divide-y divide-gray-100">
                                    <?php foreach ($report['top_products'] as $index => $product): ?>
                                        <div class="p-4 hover:bg-amber-50/10 transition-colors flex items-center justify-between group">
                                            <div class="flex items-center gap-4">
                                                <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full flex items-center justify-center text-white font-bold shadow-sm text-lg">
                                                    <?= e((string)($index + 1)) ?>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-gray-800 group-hover:text-amber-600 transition-colors"><?= e(getProductName($product)) ?></p>
                                                    <p class="text-xs text-gray-500 flex items-center gap-1">
                                                        <i class="fas fa-tag"></i>
                                                        <?= e(t('sold')) ?>: <strong><?= e((string)$product['total_sold']) ?></strong>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <p class="font-bold text-green-600"><?= e(formatPrice((float)$product['total_revenue'])) ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Customer Statistics -->
                <div class="bg-white rounded-2xl shadow-xl p-8 mt-8 border border-gray-100">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <i class="fas fa-users text-purple-600"></i><?= e(t('customer_statistics')) ?>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="text-center p-6 bg-purple-50 rounded-2xl hover:bg-purple-100 transition-colors cursor-default">
                            <div class="w-16 h-16 bg-purple-200 rounded-full flex items-center justify-center mx-auto mb-4 text-purple-600 text-2xl">
                                <i class="fas fa-users"></i>
                            </div>
                            <p class="text-4xl font-extrabold text-gray-800 mb-1"><?= e((string)$report['customer_stats']['total_customers']) ?></p>
                            <p class="text-sm font-bold text-gray-500 uppercase tracking-wide"><?= e(t('total_customers')) ?></p>
                        </div>
                        <div class="text-center p-6 bg-blue-50 rounded-2xl hover:bg-blue-100 transition-colors cursor-default">
                            <div class="w-16 h-16 bg-blue-200 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-600 text-2xl">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <p class="text-4xl font-extrabold text-gray-800 mb-1"><?= e((string)$report['customer_stats']['new_customers']) ?></p>
                            <p class="text-sm font-bold text-gray-500 uppercase tracking-wide"><?= e(t('new_customers')) ?></p>
                        </div>
                        <div class="text-center p-6 bg-green-50 rounded-2xl hover:bg-green-100 transition-colors cursor-default">
                            <div class="w-16 h-16 bg-green-200 rounded-full flex items-center justify-center mx-auto mb-4 text-green-600 text-2xl">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <p class="text-4xl font-extrabold text-gray-800 mb-1"><?= e($totalOrders > 0 ? number_format($totalOrders / max($report['customer_stats']['total_customers'], 1), 2) : '0') ?></p>
                            <p class="text-sm font-bold text-gray-500 uppercase tracking-wide"><?= e(t('avg_orders_per_customer')) ?></p>
                        </div>
                    </div>
                </div>

            </main>
                        
            <!-- Footer -->
            <?php include __DIR__ . '/footer.php'; ?>
        </div>
    </div>

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
                    backgroundColor: 'rgba(220, 38, 38, 0.05)',
                    borderWidth: 3,
                    pointBackgroundColor: 'rgb(220, 38, 38)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Orders',
                    data: salesData.map(d => parseInt(d.total_orders)).reverse(),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.05)',
                    borderWidth: 3,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 20,
                            font: {
                                size: 12,
                                family: "'Inter', sans-serif"
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            family: "'Inter', sans-serif"
                        },
                        bodyFont: {
                            size: 13,
                            family: "'Inter', sans-serif"
                        },
                        cornerRadius: 8,
                        displayColors: true
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                family: "'Inter', sans-serif"
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            },
                            font: {
                                family: "'Inter', sans-serif"
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                            drawBorder: false
                        },
                        beginAtZero: true
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false,
                }
            }
        });
    </script>
</body>
</html>
