<?php
declare(strict_types=1);

/**
 * User Account Dashboard
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';

requireLogin();

$pdo = getDB();
$userId = (int)$_SESSION['user_id'];
$error = '';
$success = '';

// Get user info
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $csrfToken = sanitizeInput('csrf_token', 'POST');
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = t('error');
    } else {
        $fullName = sanitizeInput('full_name', 'POST');
        $phone = sanitizeInput('phone', 'POST');
        $address = sanitizeInput('address', 'POST');
        $city = sanitizeInput('city', 'POST');
        $postalCode = sanitizeInput('postal_code', 'POST');
        $country = sanitizeInput('country', 'POST');
        
        try {
            $stmt = $pdo->prepare('UPDATE users SET full_name = :full_name, phone = :phone, address = :address, city = :city, postal_code = :postal_code, country = :country WHERE id = :id');
            $stmt->execute([
                'full_name' => $fullName,
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'postal_code' => $postalCode,
                'country' => $country,
                'id' => $userId
            ]);
            
            $_SESSION['full_name'] = $fullName;
            $success = e('Profile updated successfully!');
            $user = array_merge($user, [
                'full_name' => $fullName,
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'postal_code' => $postalCode,
                'country' => $country
            ]);
        } catch (PDOException $e) {
            error_log('Profile update error: ' . $e->getMessage());
            $error = t('error');
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $csrfToken = sanitizeInput('csrf_token', 'POST');
    $currentPassword = sanitizeInput('current_password', 'POST');
    $newPassword = sanitizeInput('new_password', 'POST');
    $confirmPassword = sanitizeInput('confirm_password', 'POST');
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = t('error');
    } elseif (!password_verify($currentPassword, $user['password_hash'])) {
        $error = e('Current password is incorrect');
    } elseif ($newPassword !== $confirmPassword) {
        $error = e('New passwords do not match');
    } elseif (strlen($newPassword) < 8) {
        $error = e('Password must be at least 8 characters');
    } else {
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_ARGON2ID);
            $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
            $stmt->execute(['password_hash' => $passwordHash, 'id' => $userId]);
            $success = e('Password changed successfully!');
        } catch (PDOException $e) {
            error_log('Password change error: ' . $e->getMessage());
            $error = t('error');
        }
    }
}

// Get order history
$stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = :user_id ORDER BY order_date DESC LIMIT 10');
$stmt->execute(['user_id' => $userId]);
$orders = $stmt->fetchAll();

// Calculate order status statistics for graph (from all orders, not just last 10)
$statusCounts = [
    'pending' => 0,
    'processing' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

$stmt = $pdo->prepare('SELECT COALESCE(order_status, "pending") as order_status, COUNT(*) as count FROM orders WHERE user_id = :user_id GROUP BY order_status');
$stmt->execute(['user_id' => $userId]);
$statusStats = $stmt->fetchAll();

foreach ($statusStats as $stat) {
    $status = $stat['order_status'] ?? 'pending';
    if (isset($statusCounts[$status])) {
        $statusCounts[$status] = (int)$stat['count'];
    }
}

// Get wishlist count
$stmt = $pdo->prepare('SELECT COUNT(*) as count FROM wishlist WHERE user_id = :user_id');
$stmt->execute(['user_id' => $userId]);
$wishlistCount = (int)$stmt->fetch()['count'];

$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('My Account') ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="bg-white min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <!-- Navbar -->
    <nav class="bg-white border-b border-luxury-border shadow-sm">
        <div class="container mx-auto px-4 md:px-6 py-4">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <a href="index.php" class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary tracking-wide">Bloom & Vine</a>
                <div class="flex items-center space-x-4 md:space-x-8 flex-wrap text-sm md:text-base" style="direction: ltr;">
                    <a href="index.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_home')) ?></a>
                    <a href="shop.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_shop')) ?></a>
                    <?php if (isAdmin()): ?>
                        <a href="admin/dashboard.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium hidden md:inline"><?= e(t('nav_admin')) ?></a>
                    <?php endif; ?>
                    <a href="notifications.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium relative">
                        <?= e('Notifications') ?>
                        <?php 
                        $unreadCount = getUnreadNotificationCount();
                        if ($unreadCount > 0): 
                        ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center"><?= e((string)$unreadCount) ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="account.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e('Account') ?></a>
                    <a href="cart.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium">
                        <?= e(t('nav_cart')) ?> <span class="bg-luxury-accent text-white px-2 py-0.5 rounded-full text-xs"><?= e((string)getCartCount()) ?></span>
                    </a>
                    <a href="logout.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium"><?= e(t('nav_logout')) ?></a>
                    <a href="?lang=<?= $lang === 'en' ? 'ku' : 'en' ?>" class="text-luxury-accent hover:text-luxury-primary font-semibold border border-luxury-accent px-2 md:px-3 py-1 rounded-sm text-xs md:text-sm">
                        <?= $lang === 'en' ? 'KU' : 'EN' ?>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
        <h1 class="text-3xl md:text-4xl font-luxury font-bold text-luxury-primary mb-6 md:mb-8 tracking-wide"><?= e('My Account') ?></h1>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-sm mb-6">
                <?= e($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-sm mb-6">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white border border-luxury-border shadow-luxury p-6 mb-6">
                    <div class="text-center mb-6">
                        <div class="w-20 h-20 md:w-24 md:h-24 bg-luxury-primary rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-white text-2xl md:text-3xl font-bold"><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></span>
                        </div>
                        <h2 class="text-lg md:text-xl font-luxury font-bold text-luxury-primary"><?= e($user['full_name']) ?></h2>
                        <p class="text-sm md:text-base text-luxury-textLight"><?= e($user['email']) ?></p>
                    </div>
                    
                    <nav class="space-y-2">
                        <a href="#profile" class="block px-4 py-2.5 bg-luxury-primary text-white rounded-sm font-medium"><?= e('Profile') ?></a>
                        <a href="#orders" class="block px-4 py-2.5 text-luxury-text hover:bg-luxury-border rounded-sm transition-colors font-medium"><?= e('Orders') ?></a>
                        <a href="wishlist.php" class="block px-4 py-2.5 text-luxury-text hover:bg-luxury-border rounded-sm transition-colors font-medium">
                            <?= e('Wishlist') ?> (<?= e((string)$wishlistCount) ?>)
                        </a>
                        <a href="#password" class="block px-4 py-2.5 text-luxury-text hover:bg-luxury-border rounded-sm transition-colors font-medium"><?= e('Change Password') ?></a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6 md:space-y-8">
                <!-- Profile Section -->
                <div id="profile" class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8">
                    <h2 class="text-2xl md:text-3xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e('Profile Information') ?></h2>
                    <form method="POST" action="" class="space-y-5 md:space-y-6">
                        <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                            <div>
                                <label class="block text-sm font-medium text-luxury-text mb-2"><?= e(t('full_name')) ?></label>
                                <input type="text" name="full_name" value="<?= e($user['full_name'] ?? '') ?>" required
                                       class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-luxury-text mb-2"><?= e('Phone') ?></label>
                                <input type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>"
                                       class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-luxury-text mb-2"><?= e('Address') ?></label>
                                <textarea name="address" rows="2"
                                          class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent"><?= e($user['address'] ?? '') ?></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-luxury-text mb-2"><?= e('City') ?></label>
                                <input type="text" name="city" value="<?= e($user['city'] ?? '') ?>"
                                       class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-luxury-text mb-2"><?= e('Postal Code') ?></label>
                                <input type="text" name="postal_code" value="<?= e($user['postal_code'] ?? '') ?>"
                                       class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-luxury-text mb-2"><?= e('Country') ?></label>
                                <input type="text" name="country" value="<?= e($user['country'] ?? '') ?>"
                                       class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                            </div>
                        </div>
                        
                        <button type="submit" 
                                class="bg-luxury-primary text-white py-2.5 px-8 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                            <?= e('Update Profile') ?>
                        </button>
                    </form>
                </div>

                <!-- Orders Section -->
                <div id="orders" class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8">
                    <h2 class="text-2xl md:text-3xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e('Order History') ?></h2>
                    <?php if (empty($orders)): ?>
                        <p class="text-luxury-textLight"><?= e('No orders yet.') ?></p>
                    <?php else: ?>
                        <!-- Order Status Graph -->
                        <div class="mb-8 p-6 bg-gray-50 rounded-lg border border-luxury-border">
                            <h3 class="text-lg md:text-xl font-semibold text-luxury-primary mb-4"><?= e('Order Status Overview') ?></h3>
                            <div class="flex justify-center">
                                <canvas id="orderStatusChart" style="max-width: 400px; max-height: 400px;"></canvas>
                            </div>
                        </div>
                        
                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const ctx = document.getElementById('orderStatusChart');
                            if (ctx) {
                                new Chart(ctx, {
                                    type: 'doughnut',
                                    data: {
                                        labels: [
                                            '<?= e('Pending') ?>',
                                            '<?= e('Processing') ?>',
                                            '<?= e('Shipped') ?>',
                                            '<?= e('Delivered') ?>',
                                            '<?= e('Cancelled') ?>'
                                        ],
                                        datasets: [{
                                            data: [
                                                <?= $statusCounts['pending'] ?>,
                                                <?= $statusCounts['processing'] ?>,
                                                <?= $statusCounts['shipped'] ?>,
                                                <?= $statusCounts['delivered'] ?>,
                                                <?= $statusCounts['cancelled'] ?>
                                            ],
                                            backgroundColor: [
                                                '#fbbf24', // yellow for pending
                                                '#3b82f6', // blue for processing
                                                '#8b5cf6', // purple for shipped
                                                '#10b981', // green for delivered
                                                '#ef4444'  // red for cancelled
                                            ],
                                            borderWidth: 2,
                                            borderColor: '#ffffff'
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: true,
                                        plugins: {
                                            legend: {
                                                position: 'bottom',
                                                labels: {
                                                    padding: 15,
                                                    font: {
                                                        size: 12,
                                                        family: "'Inter', 'Segoe UI', sans-serif"
                                                    },
                                                    color: '#2d2d2d'
                                                }
                                            },
                                            tooltip: {
                                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                                padding: 12,
                                                titleFont: {
                                                    size: 14,
                                                    family: "'Inter', 'Segoe UI', sans-serif"
                                                },
                                                bodyFont: {
                                                    size: 13,
                                                    family: "'Inter', 'Segoe UI', sans-serif"
                                                },
                                                callbacks: {
                                                    label: function(context) {
                                                        let label = context.label || '';
                                                        if (label) {
                                                            label += ': ';
                                                        }
                                                        label += context.parsed + ' ' + (context.parsed === 1 ? '<?= e('order') ?>' : '<?= e('orders') ?>');
                                                        return label;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                        });
                        </script>
                        <div class="space-y-4 md:space-y-6">
                            <?php foreach ($orders as $order): ?>
                                <div class="border-b border-luxury-border pb-4 md:pb-6 last:border-0">
                                    <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                                        <div>
                                            <a href="order_details.php?id=<?= e((string)$order['id']) ?>" 
                                               class="text-base md:text-lg font-semibold text-luxury-primary hover:text-luxury-accent transition-colors">
                                                <?= e('Order #') ?><?= e((string)$order['id']) ?>
                                            </a>
                                            <p class="text-sm text-luxury-textLight mt-1"><?= e(date('F j, Y g:i A', strtotime($order['order_date']))) ?></p>
                                        </div>
                                        <div class="text-left sm:text-right">
                                            <p class="text-lg md:text-xl font-bold text-luxury-accent font-luxury mb-2"><?= e(formatPrice((float)$order['grand_total'])) ?></p>
                                            <div class="flex flex-col gap-2 items-start sm:items-end">
                                                <span class="px-2 py-1 text-xs rounded-sm <?= $order['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                                    <?= e('Payment: ') ?><?= e(ucfirst($order['payment_status'])) ?>
                                                </span>
                                                <?php if (isset($order['order_status']) && $order['order_status']): ?>
                                                    <span class="px-2 py-1 text-xs rounded-sm bg-blue-100 text-blue-800">
                                                        <?= e('Status: ') ?><?= e(ucfirst($order['order_status'])) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 text-xs rounded-sm bg-gray-100 text-gray-800">
                                                        <?= e('Status: Pending') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Change Password Section -->
                <div id="password" class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8">
                    <h2 class="text-2xl md:text-3xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e('Change Password') ?></h2>
                    <form method="POST" action="" class="space-y-5 md:space-y-6">
                        <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div>
                            <label class="block text-sm font-medium text-luxury-text mb-2"><?= e('Current Password') ?></label>
                            <input type="password" name="current_password" required
                                   class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-luxury-text mb-2"><?= e('New Password') ?></label>
                            <input type="password" name="new_password" required minlength="8"
                                   class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-luxury-text mb-2"><?= e('Confirm New Password') ?></label>
                            <input type="password" name="confirm_password" required minlength="8"
                                   class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                        </div>
                        
                        <button type="submit" 
                                class="bg-luxury-primary text-white py-2.5 px-8 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                            <?= e('Change Password') ?>
                        </button>
                    </form>
                </div>
            </div>
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

