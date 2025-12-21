<?php
declare(strict_types=1);

/**
 * Notifications Page
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';
require_once __DIR__ . '/src/components.php';

requireLogin();

$pdo = getDB();
$userId = (int)$_SESSION['user_id'];

// Handle mark as read action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notificationId = (int)sanitizeInput('notification_id', 'POST', '0');
    if ($notificationId > 0) {
        markNotificationAsRead($notificationId);
    }
}

// Handle mark all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $csrfToken = sanitizeInput('csrf_token', 'POST');
    if (verifyCSRFToken($csrfToken)) {
        markAllNotificationsAsRead();
        redirect('notifications.php', e('All notifications marked as read'), 'success');
    }
}

// Get all notifications
$notifications = getNotifications(50, false);

$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('Notifications') ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/src/header.php'; ?>

    <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
        <div class="mb-6">
            <a href="account.php" class="text-luxury-accent hover:text-luxury-primary transition-colors font-medium">← <?= e('Back to Account') ?></a>
        </div>
        
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl md:text-4xl font-luxury font-bold text-luxury-primary tracking-wide"><?= e('Notifications') ?></h1>
            <?php if (!empty($notifications)): ?>
                <form method="POST" action="" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                    <input type="hidden" name="mark_all_read" value="1">
                    <button type="submit" class="text-sm text-luxury-accent hover:text-luxury-primary transition-colors font-medium">
                        <?= e('Mark All as Read') ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <?php
        $flash = getFlashMessage();
        if ($flash):
        ?>
            <div class="bg-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-50 border border-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-200 text-<?= $flash['type'] === 'success' ? 'green' : ($flash['type'] === 'error' ? 'red' : 'blue') ?>-700 px-4 py-3 rounded-sm mb-6">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($notifications)): ?>
            <div class="bg-white border border-luxury-border shadow-luxury p-8 text-center">
                <p class="text-luxury-textLight text-lg"><?= e('No notifications yet.') ?></p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($notifications as $notification): ?>
                    <div class="bg-white border border-luxury-border shadow-luxury p-6 <?= !$notification['is_read'] ? 'bg-blue-50 border-blue-200' : '' ?>">
                        <div class="flex justify-between items-start gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="text-lg font-semibold text-luxury-primary"><?= e($notification['title']) ?></h3>
                                    <?php if (!$notification['is_read']): ?>
                                        <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-luxury-text mb-2"><?= e($notification['message']) ?></p>
                                <p class="text-sm text-luxury-textLight"><?= e(date('F j, Y g:i A', strtotime($notification['created_at']))) ?></p>
                                <?php if ($notification['order_id']): ?>
                                    <a href="order_details.php?id=<?= e((string)$notification['order_id']) ?>" class="text-sm text-luxury-accent hover:text-luxury-primary transition-colors font-medium mt-2 inline-block">
                                        <?= e('View Order') ?> →
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                                <form method="POST" action="" class="inline">
                                    <input type="hidden" name="notification_id" value="<?= e((string)$notification['id']) ?>">
                                    <input type="hidden" name="mark_read" value="1">
                                    <button type="submit" class="text-xs text-luxury-textLight hover:text-luxury-accent transition-colors">
                                        <?= e('Mark as read') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?= modernFooter() ?>
</body>
</html>

