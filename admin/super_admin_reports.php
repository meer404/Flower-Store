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

// Require view_reports permission
requirePermission('view_reports');

$pdo = getDB();
$lang = getCurrentLang();
$dir = getHtmlDir();
$currency = (string)getSystemSetting('currency', 'IQD ');
$usdToIqdRate = (float)getSystemSetting('usd_to_iqd_rate', 1300);
$isIqdCurrency = strtoupper(trim($currency)) === 'IQD' || str_starts_with(strtoupper(trim($currency)), 'IQD');

$period = sanitizeInput('period', 'GET', 'month');
if (!in_array($period, ['day', 'week', 'month', 'year'])) {
    $period = 'month';
}

$report = getSalesReport($period);

$today = new DateTimeImmutable('today');
$plPeriod = sanitizeInput('pl_period', 'GET', 'month');
$plStartInput = sanitizeInput('pl_start', 'GET');
$plEndInput = sanitizeInput('pl_end', 'GET');
$damagedCostRaw = sanitizeInput('damaged_cost', 'GET');
$damagedCost = (float)str_replace(',', '', $damagedCostRaw);
if ($damagedCost < 0) {
    $damagedCost = 0.0;
}
$plPeriods = ['day', 'week', 'month', 'year', 'custom'];

if (!in_array($plPeriod, $plPeriods, true)) {
    $plPeriod = 'month';
}

switch ($plPeriod) {
    case 'day':
        $plStartDate = $today;
        $plEndDate = $today;
        break;
    case 'week':
        $plStartDate = $today->modify('monday this week');
        $plEndDate = $today->modify('sunday this week');
        break;
    case 'year':
        $plStartDate = $today->setDate((int)$today->format('Y'), 1, 1);
        $plEndDate = $today->setDate((int)$today->format('Y'), 12, 31);
        break;
    case 'custom':
        $plStartDate = $plStartInput !== '' ? DateTimeImmutable::createFromFormat('Y-m-d', $plStartInput) : false;
        $plEndDate = $plEndInput !== '' ? DateTimeImmutable::createFromFormat('Y-m-d', $plEndInput) : false;
        $plStartValid = $plStartDate && $plStartDate->format('Y-m-d') === $plStartInput;
        $plEndValid = $plEndDate && $plEndDate->format('Y-m-d') === $plEndInput;

        if (!$plStartValid || !$plEndValid || $plStartDate > $plEndDate) {
            $plPeriod = 'month';
            $plStartDate = $today->modify('first day of this month');
            $plEndDate = $today->modify('last day of this month');
        }
        break;
    case 'month':
    default:
        $plStartDate = $today->modify('first day of this month');
        $plEndDate = $today->modify('last day of this month');
        break;
}

$plStartDateStr = $plStartDate->format('Y-m-d');
$plEndDateStr = $plEndDate->format('Y-m-d');

$profitRows = [];
$profitReport = [];
$profitSummary = [
    'total_revenue' => 0.0,
    'total_cost' => 0.0,
    'net_profit' => 0.0,
    'expired_count' => 0,
    'damaged_cost' => 0.0,
    'loss_amount' => 0.0
];
$salesCostTotal = 0.0;

try {
    $stmt = $pdo->prepare('
        SELECT p.id, p.name_en, p.name_ku, p.price, p.cost_price, p.stock_qty, p.expiry_date,
               COALESCE(SUM(oi.quantity), 0) AS units_sold,
               COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS revenue,
               COALESCE(SUM(oi.quantity * p.cost_price), 0) AS total_cost
        FROM orders o
        INNER JOIN order_items oi ON oi.order_id = o.id
        INNER JOIN products p ON p.id = oi.product_id
        WHERE o.order_date BETWEEN :start_date AND :end_date
        GROUP BY p.id, p.name_en, p.name_ku, p.price, p.cost_price, p.stock_qty, p.expiry_date
        ORDER BY revenue DESC, p.name_en
    ');
    $stmt->execute([
        'start_date' => $plStartDateStr,
        'end_date' => $plEndDateStr
    ]);
    $profitReport = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Profit report error: ' . $e->getMessage());
    $profitReport = [];
}
$warningDate = $today->modify('+7 days');

foreach ($profitReport as $row) {
    $unitsSold = (int)$row['units_sold'];
    $revenue = (float)$row['revenue'];
    $salesCost = (float)$row['total_cost'];
    $costPrice = (float)$row['cost_price'];
    $stockQty = (int)$row['stock_qty'];
    $expiryDate = $row['expiry_date'];
    $expiryLoss = 0.0;
    $expiryStatus = 'none';

    if ($expiryDate) {
        $expiryDateObj = new DateTimeImmutable((string)$expiryDate);
        if ($expiryDateObj <= $today) {
            $expiryStatus = 'expired';
            $expiryLoss = $stockQty * $costPrice;
        } elseif ($expiryDateObj <= $warningDate) {
            $expiryStatus = 'warning';
        } else {
            $expiryStatus = 'valid';
        }
    }

    $profitValue = $revenue - $salesCost - $expiryLoss;

    $profitSummary['total_revenue'] += $revenue;
    $salesCostTotal += $salesCost;

    $profitRows[] = [
        'id' => $row['id'],
        'name_en' => $row['name_en'],
        'name_ku' => $row['name_ku'],
        'price' => (float)$row['price'],
        'cost_price' => $costPrice,
        'units_sold' => $unitsSold,
        'revenue' => $revenue,
        'total_cost' => $salesCost + $expiryLoss,
        'profit_value' => $profitValue,
        'expiry_date' => $expiryDate,
        'expiry_loss' => $expiryLoss,
        'expiry_status' => $expiryStatus
    ];
}

$profitSummary['damaged_cost'] = $damagedCost;

$expiredInventoryLoss = 0.0;
try {
    $expiredLossStmt = $pdo->query('SELECT COALESCE(SUM(stock_qty * cost_price), 0) FROM products WHERE expiry_date IS NOT NULL AND expiry_date <= CURDATE()');
    $expiredInventoryLoss = (float)$expiredLossStmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Expired inventory loss error: ' . $e->getMessage());
}

$profitSummary['total_cost'] = $salesCostTotal + $expiredInventoryLoss + $damagedCost;
$profitSummary['net_profit'] = $profitSummary['total_revenue'] - $profitSummary['total_cost'];
$profitSummary['loss_amount'] = max(0.0, $profitSummary['total_cost'] - $profitSummary['total_revenue']);

try {
    $expiredCountStmt = $pdo->query('SELECT COUNT(*) FROM products WHERE expiry_date IS NOT NULL AND expiry_date <= CURDATE()');
    $profitSummary['expired_count'] = (int)$expiredCountStmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Expired count error: ' . $e->getMessage());
}
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
                    <?= statsCard(t('total_revenue'), formatPrice($totalRevenue, $currency), 'fas fa-coins text-3xl', 'green') ?>
                    <?= statsCard(t('total_orders'), (string)$totalOrders, 'fas fa-shopping-bag text-3xl', 'blue') ?>
                    <?= statsCard(t('paid_revenue'), formatPrice($paidRevenue, $currency), 'fas fa-check-circle text-3xl', 'purple') ?>
                    <?= statsCard(t('avg_order_value'), formatPrice($avgOrderValue, $currency), 'fas fa-chart-bar text-3xl', 'orange') ?>
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

                <!-- INSERT HERE: Profit & Loss Report -->
                <div class="bg-white rounded-2xl shadow-xl p-8 mt-8 border border-gray-100">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-green-100 text-green-600 flex items-center justify-center">
                                <i class="fas fa-chart-pie"></i>
                            </span>
                            <?= e(t('profit_loss_report')) ?>
                        </h2>
                    </div>

                    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-6">
                        <div class="bg-gray-50 p-1 rounded-xl flex flex-wrap items-center gap-1">
                            <?php foreach (['day' => t('daily'), 'week' => t('weekly'), 'month' => t('monthly'), 'year' => t('yearly'), 'custom' => t('custom_range')] as $key => $label): ?>
                                <a href="?period=<?= e($period) ?>&pl_period=<?= e($key) ?>&damaged_cost=<?= e((string)$damagedCost) ?>"
                                   class="px-4 py-2 rounded-lg text-xs font-bold transition-all <?= $plPeriod === $key ? 'bg-white text-green-700 shadow-md transform scale-105' : 'text-gray-600 hover:bg-white hover:text-green-700' ?>">
                                    <?= e($label) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <form method="GET" class="flex flex-wrap items-end gap-3">
                            <input type="hidden" name="period" value="<?= e($period) ?>">
                            <input type="hidden" name="pl_period" value="custom">
                            <div>
                                <label for="pl_start" class="block text-xs font-bold text-gray-500 mb-1"><?= e(t('start_date')) ?></label>
                                <input type="date" id="pl_start" name="pl_start" value="<?= e($plStartDateStr) ?>"
                                       class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-white">
                            </div>
                            <div>
                                <label for="pl_end" class="block text-xs font-bold text-gray-500 mb-1"><?= e(t('end_date')) ?></label>
                                <input type="date" id="pl_end" name="pl_end" value="<?= e($plEndDateStr) ?>"
                                       class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-white">
                            </div>
                            <div>
                                <label for="damaged_cost" class="block text-xs font-bold text-gray-500 mb-1"><?= e(t('damaged_goods_cost')) ?></label>
                                <input type="text" id="damaged_cost" name="damaged_cost" value="<?= e((string)$damagedCost) ?>"
                                       inputmode="decimal" class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-white">
                                <p class="text-[11px] text-gray-400 mt-1"><?= e(t('damaged_goods_hint')) ?></p>
                            </div>
                            <button type="submit"
                                    class="px-4 py-2 rounded-lg bg-green-600 text-white text-xs font-bold hover:bg-green-700 transition-colors">
                                <?= e(t('apply')) ?>
                            </button>
                        </form>
                    </div>

                    <?php
                    $netProfitColor = $profitSummary['net_profit'] > 0 ? 'green' : ($profitSummary['net_profit'] < 0 ? 'red' : 'gray');
                    ?>

                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-8">
                        <?= statsCard(t('total_revenue'), formatPrice($profitSummary['total_revenue'], $currency), 'fas fa-coins text-3xl', 'green') ?>
                        <?= statsCard(t('total_cost'), formatPrice($profitSummary['total_cost'], $currency), 'fas fa-receipt text-3xl', 'orange') ?>
                        <?= statsCard(t('net_profit'), formatPrice($profitSummary['net_profit'], $currency), 'fas fa-balance-scale text-3xl', $netProfitColor) ?>
                        <?= statsCard(t('loss_amount'), formatPrice($profitSummary['loss_amount'], $currency), 'fas fa-triangle-exclamation text-3xl', $profitSummary['loss_amount'] > 0 ? 'red' : 'gray') ?>
                        <?= statsCard(t('damaged_goods_cost'), formatPrice($profitSummary['damaged_cost'], $currency), 'fas fa-box-open text-3xl', 'purple') ?>
                        <?= statsCard(t('expired_products'), (string)$profitSummary['expired_count'], 'fas fa-skull-crossbones text-3xl', 'red') ?>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('product')) ?> (EN/KU)</th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('cost_price')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('sell_price')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('units_sold')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('revenue')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('total_cost')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('profit_loss')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('expiry_date')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('status')) ?></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (empty($profitRows)): ?>
                                    <tr>
                                        <td colspan="9" class="px-6 py-8 text-center text-gray-400"><?= e(t('no_data_available')) ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($profitRows as $row): ?>
                                        <?php
                                        $profitValue = (float)$row['profit_value'];
                                        $profitClass = $profitValue > 0 ? 'text-green-600' : ($profitValue < 0 ? 'text-red-600' : 'text-gray-500');
                                        $profitLabel = $profitValue > 0 ? t('profit') : ($profitValue < 0 ? t('loss') : t('break_even'));

                                        $expiryDateDisplay = $row['expiry_date'] ? (string)$row['expiry_date'] : t('not_set');

                                        $expiryLabel = t('no_expiry');
                                        $expiryBadge = 'bg-gray-100 text-gray-700';
                                        if ($row['expiry_status'] === 'expired') {
                                            $expiryLabel = t('expired');
                                            $expiryBadge = 'bg-red-100 text-red-700';
                                        } elseif ($row['expiry_status'] === 'warning') {
                                            $expiryLabel = t('expiring_soon');
                                            $expiryBadge = 'bg-yellow-100 text-yellow-700';
                                        } elseif ($row['expiry_status'] === 'valid') {
                                            $expiryLabel = t('valid');
                                            $expiryBadge = 'bg-green-100 text-green-700';
                                        }
                                        ?>
                                        <tr class="hover:bg-green-50/10 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="font-semibold text-gray-800"><?= e($row['name_en']) ?></div>
                                                <div class="text-xs text-gray-500"><?= e($row['name_ku']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 text-sm font-semibold text-gray-700"><?= e(formatPrice($row['cost_price'], $currency)) ?></td>
                                            <td class="px-6 py-4 text-sm font-semibold text-gray-700"><?= e(formatPrice($row['price'], $currency)) ?></td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 rounded bg-blue-50 text-blue-700 text-xs font-bold"><?= e((string)$row['units_sold']) ?></span>
                                            </td>
                                            <td class="px-6 py-4 font-bold text-green-600"><?= e(formatPrice($row['revenue'], $currency)) ?></td>
                                            <td class="px-6 py-4 font-bold text-orange-600"><?= e(formatPrice($row['total_cost'], $currency)) ?></td>
                                            <td class="px-6 py-4">
                                                <div class="font-bold <?= $profitClass ?>"><?= e(formatPrice($profitValue, $currency)) ?></div>
                                                <div class="text-xs text-gray-500"><?= e($profitLabel) ?></div>
                                                <?php if ($row['expiry_loss'] > 0): ?>
                                                    <div class="text-xs text-red-500 mt-1"><?= e(t('expiry_loss')) ?>: <?= e(formatPrice($row['expiry_loss'], $currency)) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600"><?= e($expiryDateDisplay) ?></td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 rounded text-xs font-bold <?= $expiryBadge ?>"><?= e($expiryLabel) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
        const currency = <?= json_encode($currency) ?>;
        const isIqd = <?= $isIqdCurrency ? 'true' : 'false' ?>;
        const usdToIqdRate = <?= json_encode($usdToIqdRate) ?>;
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
                                const v = Number(value);
                                const converted = isIqd ? (v * (usdToIqdRate || 1300)) : v;
                                const formatted = Number(converted).toLocaleString(undefined, { maximumFractionDigits: 0 });
                                return (isIqd ? (currency.trim() + ' ') : currency) + formatted;
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
