<?php
declare(strict_types=1);
require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/FibService.php';
require_once __DIR__ . '/src/design_config.php';

requireLogin();

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    redirect('index.php');
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id AND user_id = :user_id');
$stmt->execute(['id' => $orderId, 'user_id' => $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order || $order['payment_method'] !== 'fib' || $order['payment_status'] === 'paid') {
    redirect('order_details.php?id=' . $orderId);
}

// Check if we need to get FIB payment details
try {
    $fibPayment = FibService::getPaymentStatus($order['fib_payment_id']);
} catch (Exception $e) {
    error_log('FIB Fetch error: ' . $e->getMessage());
    die("Error fetching payment details. Please try again later.");
}

// Polling for status via AJAX
if (isset($_GET['check_status'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $fibPayment['status']]);
    exit;
}

$lang = getCurrentLang();
$dir = getHtmlDir();
$currency = (string)getSystemSetting('currency', 'IQD ');
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('pay_with_fib')) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
    <style>
        @keyframes pulse-ring {
            0% { transform: scale(.33); }
            80%, 100% { opacity: 0; }
        }
        .pulse-ring::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background-color: var(--luxury-accent);
            animation: pulse-ring 1.25s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4" style="font-family: 'Inter', sans-serif;">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-luxury-border">
        <div class="bg-luxury-primary p-6 text-white text-center">
            <div class="inline-block p-3 bg-white/10 rounded-full mb-4">
                <i class="fas fa-wallet text-2xl text-luxury-accent"></i>
            </div>
            <h1 class="text-2xl font-luxury font-bold tracking-wide"><?= e(t('pay_with_fib')) ?></h1>
            <p class="text-white/70 text-sm mt-1"><?= e(t('order_id')) ?>: #<?= $orderId ?></p>
        </div>

        <div class="p-8 text-center">
            <div class="mb-6">
                <p class="text-luxury-textLight text-sm mb-2"><?= e(t('total_amount')) ?></p>
                <p class="text-3xl font-bold text-luxury-primary"><?= e(formatPrice((float)$order['grand_total'], $currency)) ?></p>
            </div>

            <div class="relative inline-block mb-8">
                <?php 
                $qrCode = $fibPayment['qrCode'] ?? $order['fib_qr_code'] ?? null;
                if ($qrCode): 
                ?>
                    <div class="bg-white p-4 rounded-xl border-2 border-luxury-border shadow-inner">
                        <img src="<?= $qrCode ?>" alt="FIB QR Code" class="w-64 h-64">
                    </div>
                <?php else: ?>
                    <div class="w-64 h-64 bg-gray-100 flex items-center justify-center rounded-xl border-2 border-dashed border-gray-300">
                        <p class="text-gray-400 text-sm">QR Code unavailable</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="space-y-4">
                <div class="bg-luxury-accentLight p-4 rounded-lg">
                    <p class="text-luxury-primary text-sm font-medium">
                        <i class="fas fa-qrcode me-2"></i><?= e(t('scan_to_pay')) ?>
                    </p>
                </div>

                <?php $appLink = $fibPayment['personalAppLink'] ?? $order['fib_app_link'] ?? '#'; ?>
                <a href="<?= e($appLink) ?>" 
                   class="flex items-center justify-center gap-3 w-full bg-[#00A69C] text-white py-4 rounded-xl font-bold hover:opacity-90 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-external-link-alt"></i>
                    <?= e(t('open_in_fib')) ?>
                </a>

                <div class="flex items-center justify-center gap-3 pt-4">
                    <div class="relative w-3 h-3">
                        <div class="absolute inset-0 bg-luxury-accent rounded-full pulse-ring"></div>
                        <div class="absolute inset-0 bg-luxury-accent rounded-full"></div>
                    </div>
                    <span class="text-sm text-luxury-textLight font-medium animate-pulse"><?= e(t('payment_waiting')) ?></span>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 p-4 text-center border-t border-luxury-border">
            <a href="checkout.php" class="text-sm text-luxury-textLight hover:text-luxury-primary transition-colors">
                <i class="fas fa-arrow-left me-1"></i> <?= e(t('back_to_cart')) ?>
            </a>
        </div>
    </div>

    <script>
        async function checkStatus() {
            try {
                const response = await fetch('?order_id=<?= $orderId ?>&check_status=1');
                const data = await response.json();
                
                if (data.status === 'PAID') {
                    // Success state
                    document.body.innerHTML = `
                        <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl p-8 text-center border border-luxury-border">
                            <div class="w-20 h-20 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-check text-4xl"></i>
                            </div>
                            <h1 class="text-2xl font-bold text-luxury-primary mb-4"><?= e(t('payment_confirmed')) ?></h1>
                            <p class="text-gray-600"><?= e(t('payment_confirmed')) ?></p>
                        </div>
                    `;
                    setTimeout(() => {
                        window.location.href = 'order_details.php?id=<?= $orderId ?>&payment=success';
                    }, 2000);
                } else if (data.status === 'DECLINED') {
                    window.location.href = 'checkout.php?error=payment_failed';
                } else if (data.status === 'EXPIRED') {
                    window.location.href = 'checkout.php?error=payment_expired';
                }
            } catch (e) {
                console.error('Polling error:', e);
            }
        }
        
        // Poll every 3 seconds
        const pollInterval = setInterval(checkStatus, 3000);
    </script>
    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
</body>
</html>
