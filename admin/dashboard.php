<?php
declare(strict_types=1);

/**
 * Admin Dashboard
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';

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
    <!-- Navbar -->
    <nav class="bg-white border-b border-luxury-border shadow-sm">
        <div class="container mx-auto px-4 md:px-6 py-4">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <a href="../index.php" class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary tracking-wide">Bloom & Vine</a>
                <div class="flex items-center space-x-4 md:space-x-8 flex-wrap text-sm md:text-base" style="direction: ltr;">
                    <a href="dashboard.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('admin_dashboard')) ?></a>
                    <a href="products.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium hidden md:inline"><?= e('Products') ?></a>
                    <a href="add_product.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('admin_add_product')) ?></a>
                    <a href="../index.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_home')) ?></a>
                    <a href="../logout.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_logout')) ?></a>
                    <a href="?lang=<?= $lang === 'en' ? 'ku' : 'en' ?>" class="text-luxury-accent hover:text-luxury-primary font-semibold border border-luxury-accent px-2 md:px-3 py-1 rounded-sm text-xs md:text-sm">
                        <?= $lang === 'en' ? 'KU' : 'EN' ?>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
        <h1 class="text-3xl md:text-4xl font-luxury font-bold text-luxury-primary mb-6 md:mb-8 tracking-wide"><?= e(t('admin_dashboard')) ?></h1>
        
        <?php
        $flash = getFlashMessage();
        if ($flash):
        ?>
            <div class="bg-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-50 border border-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-200 text-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-700 px-4 py-3 rounded-sm mb-6">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 mb-8 md:mb-12">
            <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8">
                <h2 class="text-lg md:text-xl font-semibold text-luxury-textLight mb-3"><?= e(t('total_orders')) ?></h2>
                <p class="text-3xl md:text-4xl font-luxury font-bold text-luxury-accent"><?= e((string)$totalOrders) ?></p>
            </div>
            
            <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8">
                <h2 class="text-lg md:text-xl font-semibold text-luxury-textLight mb-3"><?= e(t('total_sales')) ?></h2>
                <p class="text-3xl md:text-4xl font-luxury font-bold text-luxury-accent"><?= e(formatPrice($totalSales)) ?></p>
            </div>
        </div>
        
        <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8">
            <h2 class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e(t('admin_orders')) ?></h2>
            <?php
            $stmt = $pdo->query('SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC LIMIT 10');
            $orders = $stmt->fetchAll();
            
            if (empty($orders)):
            ?>
                <p class="text-luxury-textLight"><?= e('No orders yet.') ?></p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-luxury-border">
                        <thead class="bg-luxury-border">
                            <tr>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-luxury-text uppercase tracking-wide"><?= e('Order ID') ?></th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-luxury-text uppercase tracking-wide"><?= e('Customer') ?></th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-luxury-text uppercase tracking-wide"><?= e('Total') ?></th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-luxury-text uppercase tracking-wide"><?= e('Status') ?></th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-luxury-text uppercase tracking-wide"><?= e('Date') ?></th>
                                <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-luxury-text uppercase tracking-wide"><?= e('Actions') ?></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-luxury-border">
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-luxury-primary font-medium">#<?= e((string)$order['id']) ?></td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-luxury-text"><?= e($order['full_name']) ?></td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-semibold text-luxury-accent"><?= e(formatPrice((float)$order['grand_total'])) ?></td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs rounded-sm <?= $order['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                        <?= e($order['payment_status']) ?>
                                    </span>
                                    <?php if (isset($order['order_status'])): ?>
                                        <span class="ml-2 px-2 py-1 text-xs rounded-sm bg-blue-100 text-blue-800">
                                            <?= e(ucfirst($order['order_status'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm text-luxury-textLight"><?= e(date('Y-m-d H:i', strtotime($order['order_date']))) ?></td>
                                <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="../order_details.php?id=<?= e((string)$order['id']) ?>" class="text-luxury-accent hover:text-luxury-primary transition-colors font-medium">
                                        <?= e('View / Manage') ?>
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

    <!-- Footer -->
    <footer class="bg-luxury-primary text-white py-12 mt-20 border-t border-luxury-border">
        <div class="container mx-auto px-6 text-center">
            <p class="text-luxury-accentLight font-light tracking-wide">&copy; <?= e(date('Y')) ?> Bloom & Vine. <?= e('All rights reserved.') ?></p>
        </div>
    </footer>
</body>
</html>

