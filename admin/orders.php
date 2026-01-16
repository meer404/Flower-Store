<?php
declare(strict_types=1);

/**
 * Orders Management Page (Admin)
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';
require_once __DIR__ . '/../src/components.php';

requireAdmin();
// Check if user has permission to view orders or is super admin
if (!isSuperAdmin() && !hasPermission('view_orders')) {
    redirect('dashboard.php', t('no_permission'), 'error');
}

$pdo = getDB();

// Pagination
$page = max(1, (int)sanitizeInput('page', 'GET', '1'));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total count
$stmt = $pdo->query('SELECT COUNT(*) as total FROM orders');
$totalOrders = (int)$stmt->fetch()['total'];
$totalPages = (int)ceil($totalOrders / $perPage);

// Get orders
$stmt = $pdo->prepare('SELECT o.*, u.full_name as user_name, u.email as user_email 
                       FROM orders o 
                       LEFT JOIN users u ON o.user_id = u.id 
                       ORDER BY o.order_date DESC 
                       LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('orders_management')) ?> - Bloom & Vine</title>
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
                <!-- Header Section -->
                <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-800 text-white rounded-3xl p-8 mb-8 shadow-xl relative overflow-hidden">
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <h1 class="text-3xl md:text-4xl font-luxury font-bold mb-2">
                                <i class="fas fa-shopping-cart me-3"></i><?= e(t('orders_management')) ?>
                            </h1>
                            <p class="text-blue-200"><?= e(t('orders_desc') !== 'orders_desc' ? t('orders_desc') : 'Manage and track customer orders') ?></p>
                        </div>
                    </div>
                </div>

                <?php
                $flash = getFlashMessage();
                if ($flash):
                    echo alert($flash['message'], $flash['type']);
                endif;
                ?>

                <!-- Orders Table Card -->
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <div>
                            <h2 class="text-lg font-bold text-gray-800"><i class="fas fa-list me-2 text-blue-600"></i><?= e(t('all_orders')) ?></h2>
                            <p class="text-gray-500 text-sm"><?= e(t('total_orders_count', ['count' => $totalOrders])) ?></p>
                        </div>
                        <div class="text-sm font-medium text-gray-500 bg-white px-3 py-1 rounded-lg border border-gray-200">
                            <?= e(t('page_x_of_y', ['page' => $page, 'total' => $totalPages])) ?>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('order_id')) ?></th>
                                    <th class="px-6 py-3 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('customer')) ?></th>
                                    <th class="px-6 py-3 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('total')) ?></th>
                                    <th class="px-6 py-3 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('status')) ?></th>
                                    <th class="px-6 py-3 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('payment')) ?></th>
                                    <th class="px-6 py-3 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('date')) ?></th>
                                    <th class="px-6 py-3 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('action')) ?></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                                            <i class="fas fa-box-open text-4xl mb-2"></i>
                                            <p><?= e(t('no_orders_found')) ?></p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr class="hover:bg-blue-50/10 transition-colors">
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
                                                    'delivered' => 'bg-green-100 text-green-700',
                                                    'cancelled' => 'bg-red-100 text-red-700'
                                                ];
                                                $orderStatus = strtolower($order['order_status'] ?? $order['status'] ?? 'pending');
                                                $statusClass = $statusColors[$orderStatus] ?? 'bg-gray-100 text-gray-700';
                                                ?>
                                                <span class="<?= $statusClass ?> px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide">
                                                    <?= e(ucfirst($orderStatus)) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                 <span class="<?= ($order['payment_status'] ?? 'unpaid') === 'paid' ? 'text-green-600' : 'text-red-500' ?> font-semibold text-sm">
                                                    <?= e(ucfirst($order['payment_status'] ?? 'unpaid')) ?>
                                                 </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                <?= e(date('M d, H:i', strtotime($order['order_date']))) ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <a href="order_details.php?id=<?= $order['id'] ?>" class="text-blue-600 hover:text-blue-800 transition-colors font-semibold text-sm bg-blue-50 px-3 py-1.5 rounded-lg hover:bg-blue-100">
                                                    <?= e(t('details')) ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="px-6 py-4 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-gray-50/30">
                            <div class="text-xs text-gray-500 font-medium">
                                <?= e(t('showing_results', [
                                    'start' => ($page - 1) * $perPage + 1,
                                    'end' => min($page * $perPage, $totalOrders),
                                    'total' => $totalOrders
                                ])) ?>
                            </div>
                            <div class="flex gap-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= e((string)($page - 1)) ?>" 
                                       class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-blue-600 transition-all font-semibold text-sm shadow-sm">
                                        <i class="fas fa-chevron-left rtl:rotate-180 me-1"></i><?= e(t('previous')) ?>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?= e((string)($page + 1)) ?>" 
                                       class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-blue-600 transition-all font-semibold text-sm shadow-sm">
                                        <?= e(t('next')) ?><i class="fas fa-chevron-right rtl:rotate-180 ms-1"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
                        
            <!-- Footer -->
            <?php include __DIR__ . '/footer.php'; ?>
        </div>
    </div>
</body>
</html>
