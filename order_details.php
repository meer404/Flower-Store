<?php
declare(strict_types=1);

/**
 * Order Details Page
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';
require_once __DIR__ . '/src/components.php';

requireLogin();

$pdo = getDB();
$orderId = (int)sanitizeInput('id', 'GET', '0');
$userId = (int)$_SESSION['user_id'];

if ($orderId <= 0) {
    redirect('account.php', e(t('invalid_order')), 'error');
}

// Get order details
$stmt = $pdo->prepare('SELECT o.*, u.full_name, u.email 
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       WHERE o.id = :id AND (o.user_id = :user_id OR :is_admin = 1)');
$isAdmin = isAdmin() ? 1 : 0;
$stmt->execute(['id' => $orderId, 'user_id' => $userId, 'is_admin' => $isAdmin]);
$order = $stmt->fetch();

if (!$order) {
    redirect('account.php', e(t('order_not_found')), 'error');
}

// Get order items
$stmt = $pdo->prepare('SELECT oi.*, p.name_en, p.name_ku, p.image_url 
                       FROM order_items oi 
                       JOIN products p ON oi.product_id = p.id 
                       WHERE oi.order_id = :order_id');
$stmt->execute(['order_id' => $orderId]);
$orderItems = $stmt->fetchAll();

$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('order_number_prefix')) ?><?= e((string)$orderId) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Space+Mono:wght@400;700&display=swap');

        :root {
            --receipt-ink: #111827;
            --receipt-muted: #6b7280;
            --receipt-line: #d1d5db;
            --receipt-soft: #f3f4f6;
            --receipt-accent: #b91c8c;
            --receipt-light-accent: #f5e6f0;
        }

        .screen-only {
            display: block;
        }

        .print-only {
            display: none;
        }

        .receipt {
            background: #ffffff;
            border: 2px solid var(--receipt-line);
            box-shadow: 0 28px 60px rgba(15, 23, 42, 0.12);
            color: var(--receipt-ink);
            font-family: "Space Mono", "Courier New", monospace;
            margin: 0 auto;
            max-width: 760px;
            padding: 32px 28px;
            border-radius: 2px;
        }

        .receipt-header {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            justify-content: space-between;
        }

        .receipt-brand {
            font-family: "Playfair Display", "Times New Roman", serif;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--receipt-accent);
        }

        .receipt-title {
            color: var(--receipt-muted);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.32em;
            margin-top: 6px;
            text-transform: uppercase;
        }

        .receipt-meta {
            color: var(--receipt-muted);
            font-size: 11px;
            margin-top: 8px;
        }

        .receipt-header-right {
            text-align: right;
        }

        .receipt-code {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }

        .receipt-date {
            color: var(--receipt-muted);
            font-size: 11px;
            margin-top: 4px;
        }

        .receipt-badge {
            border: 1px solid var(--receipt-ink);
            border-radius: 999px;
            display: inline-block;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.24em;
            margin-top: 8px;
            padding: 4px 10px;
            text-transform: uppercase;
        }

        .receipt-badge.is-paid {
            background: #ecfdf3;
            border-color: #10b981;
            color: #065f46;
        }

        .receipt-badge.is-pending {
            background: #fffbeb;
            border-color: #f59e0b;
            color: #92400e;
        }

        .receipt-divider {
            border-top: 1px dashed var(--receipt-line);
            margin: 16px 0;
        }

        .receipt-divider-ornate {
            margin: 18px 0;
            text-align: center;
            color: var(--receipt-accent);
            font-size: 12px;
            letter-spacing: 0.5em;
            opacity: 0.6;
        }

        .receipt-columns {
            display: grid;
            gap: 16px;
            grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
        }

        .receipt-section-title {
            color: var(--receipt-muted);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.18em;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .receipt-row {
            display: flex;
            font-size: 12px;
            gap: 12px;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .receipt-muted {
            color: var(--receipt-muted);
        }

        .receipt-strong {
            font-weight: 700;
        }

        .receipt-right {
            text-align: right;
        }

        .receipt-items-header,
        .receipt-item-row {
            display: grid;
            gap: 8px;
            grid-template-columns: 1fr 56px 90px 90px;
        }

        .receipt-items-header {
            background: var(--receipt-light-accent);
            border-bottom: 1px dashed var(--receipt-accent);
            border-top: 1px dashed var(--receipt-accent);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.14em;
            padding: 8px 0;
            text-transform: uppercase;
            color: var(--receipt-ink);
        }

        .receipt-item-row {
            border-bottom: 1px dotted var(--receipt-line);
            font-size: 12px;
            padding: 6px 0;
        }

        .receipt-totals {
            margin-top: 8px;
        }

        .receipt-total {
            border-top: 2px dashed var(--receipt-accent);
            font-weight: 700;
            margin-top: 10px;
            padding-top: 10px;
            font-size: 14px;
            color: var(--receipt-accent);
        }

        .receipt-address {
            font-size: 12px;
            white-space: pre-line;
        }

        .receipt-footer {
            color: var(--receipt-muted);
            font-size: 11px;
            margin-top: 20px;
            text-align: center;
            padding-top: 12px;
            border-top: 1px dotted var(--receipt-line);
        }

        .receipt-preview-btn {
            background: linear-gradient(135deg, #b91c8c, #c93ba0);
            border: none;
            border-radius: 6px;
            color: white;
            cursor: pointer;
            font-weight: 600;
            padding: 8px 16px;
            transition: all 0.3s ease;
        }

        .receipt-preview-btn:hover {
            box-shadow: 0 8px 16px rgba(185, 28, 140, 0.3);
            transform: translateY(-2px);
        }

        .preview-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            animation: fadeIn 0.3s ease;
        }

        .preview-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .preview-modal-content {
            background: #f9fafb;
            border-radius: 8px;
            max-height: 90vh;
            overflow-y: auto;
            width: 90%;
            max-width: 820px;
            padding: 20px;
        }

        .preview-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e5e7eb;
        }

        .preview-modal-header h2 {
            margin: 0;
            color: #111827;
            font-size: 18px;
        }

        .preview-close-btn {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 24px;
            padding: 0;
            width: 32px;
            height: 32px;
        }

        .preview-close-btn:hover {
            color: #111827;
        }

        .preview-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .preview-print-btn {
            background: #b91c8c;
            border: none;
            border-radius: 6px;
            color: white;
            cursor: pointer;
            flex: 1;
            font-weight: 600;
            padding: 12px;
            transition: background 0.3s ease;
        }

        .preview-print-btn:hover {
            background: #a01676;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @media print {
            @page {
                margin: 12mm;
            }

            body {
                background: #ffffff;
                color: #111827;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .screen-only {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }

            .receipt {
                border: none;
                box-shadow: none;
                font-family: "Space Mono", "Courier New", monospace;
                max-width: none;
                padding: 0;
            }

            .receipt-columns {
                grid-template-columns: 1fr 1fr;
            }

            .receipt-items-header,
            .receipt-item-row {
                grid-template-columns: 1fr 44px 70px 70px;
            }

            .preview-modal {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-white min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <div class="print-only">
        <div class="receipt">
            <div class="receipt-header">
                <div>
                    <div class="receipt-brand">Bloom &amp; Vine</div>
                    <div class="receipt-title"><?= e(t('order_summary')) ?></div>
                    <div class="receipt-meta"><?= e(t('order_number_prefix')) ?><?= e((string)$orderId) ?></div>
                </div>
                <div class="receipt-header-right">
                    <div class="receipt-code"><?= e(t('order_date')) ?></div>
                    <div class="receipt-date"><?= e(date('F j, Y g:i A', strtotime($order['order_date']))) ?></div>
                    <div class="receipt-badge <?= $order['payment_status'] === 'paid' ? 'is-paid' : 'is-pending' ?>">
                        <?= e(t($order['payment_status'])) ?>
                    </div>
                </div>
            </div>

             <div class="receipt-divider-ornate">✦ ✦ ✦</div>

            <div class="receipt-columns">
                <div>
                    <div class="receipt-section-title"><?= e(t('order_info')) ?></div>
                    <div class="receipt-row">
                        <span class="receipt-muted"><?= e(t('order_date')) ?></span>
                        <span><?= e(date('F j, Y g:i A', strtotime($order['order_date']))) ?></span>
                    </div>
                    <?php if (isset($order['delivery_date']) && $order['delivery_date']): ?>
                        <div class="receipt-row">
                            <span class="receipt-muted"><?= e(t('delivery_date')) ?></span>
                            <span><?= e(date('F j, Y', strtotime($order['delivery_date']))) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="receipt-row">
                        <span class="receipt-muted"><?= e(t('order_status_label')) ?></span>
                        <span><?= e(t($order['order_status'] ?? 'pending')) ?></span>
                    </div>
                    <?php if (isset($order['payment_method']) && $order['payment_method']): ?>
                        <div class="receipt-row">
                            <span class="receipt-muted"><?= e(t('payment_method')) ?></span>
                            <span>
                                <?= e(ucfirst($order['payment_method'])) ?>
                                <?php if (isset($order['card_last_four']) && $order['card_last_four']): ?>
                                    (****<?= e($order['card_last_four']) ?>)
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($order['tracking_number']) && $order['tracking_number']): ?>
                        <div class="receipt-row">
                            <span class="receipt-muted"><?= e(t('tracking_number')) ?></span>
                            <span><?= e($order['tracking_number']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <div class="receipt-section-title"><?= e(t('customer_info')) ?></div>
                    <div class="receipt-strong"><?= e($order['full_name']) ?></div>
                    <div class="receipt-muted"><?= e($order['email']) ?></div>
                </div>
            </div>

            <div class="receipt-divider"></div>

            <div>
                <div class="receipt-section-title"><?= e(t('shipping_address')) ?></div>
                <div class="receipt-address"><?= e($order['shipping_address']) ?></div>
            </div>

            <div class="receipt-divider-ornate">✦ ✦ ✦</div>

            <div>
                <div class="receipt-section-title"><?= e(t('order_items')) ?></div>
                <div class="receipt-items-header">
                    <span><?= e(t('product')) ?></span>
                    <span class="receipt-right"><?= e(t('quantity')) ?></span>
                    <span class="receipt-right"><?= e(t('price')) ?></span>
                    <span class="receipt-right"><?= e(t('total')) ?></span>
                </div>
                <?php $receiptSubtotal = 0.0; ?>
                <?php foreach ($orderItems as $item): ?>
                    <?php $receiptLineTotal = (float)$item['quantity'] * (float)$item['unit_price']; ?>
                    <?php $receiptSubtotal += $receiptLineTotal; ?>
                    <div class="receipt-item-row">
                        <span><?= e(getProductName($item)) ?></span>
                        <span class="receipt-right"><?= e((string)$item['quantity']) ?></span>
                        <span class="receipt-right"><?= e(formatPrice((float)$item['unit_price'])) ?></span>
                        <span class="receipt-right"><?= e(formatPrice($receiptLineTotal)) ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="receipt-totals">
                    <div class="receipt-row">
                        <span class="receipt-muted"><?= e(t('subtotal')) ?></span>
                        <span><?= e(formatPrice($receiptSubtotal)) ?></span>
                    </div>
                    <div class="receipt-row receipt-total">
                        <span><?= e(t('total')) ?></span>
                        <span><?= e(formatPrice((float)$order['grand_total'])) ?></span>
                    </div>
                </div>
            </div>

            <div class="receipt-divider"></div>

            <div class="receipt-footer">
                <div style="margin-bottom: 10px; color: var(--receipt-accent); font-size: 12px; letter-spacing: 0.5em; opacity: 0.6;">✦ ✦ ✦</div>
                <div style="margin-bottom: 6px; color: var(--receipt-ink); font-weight: 600;">Thank you for your order</div>
                <div style="font-size: 10px; color: var(--receipt-muted); margin-top: 6px;">Bloom &amp; Vine</div>
                <div style="font-size: 10px; color: var(--receipt-muted); margin-top: 2px; letter-spacing: 0.05em;">Order #<?= e((string)$orderId) ?></div>
            </div>
        </div>
    </div>

    <div class="screen-only">
        <?php include __DIR__ . '/src/header.php'; ?>
        <?php include __DIR__ . '/src/pwa_head.php'; ?>

        <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <?php if (isAdmin()): ?>
                        <a href="admin/dashboard.php" class="text-luxury-accent hover:text-luxury-primary transition-colors font-medium"><i class="fas fa-arrow-left rtl:rotate-180 me-1"></i> <?= e(t('back_to_admin')) ?></a>
                    <?php else: ?>
                        <a href="account.php" class="text-luxury-accent hover:text-luxury-primary transition-colors font-medium"><i class="fas fa-arrow-left rtl:rotate-180 me-1"></i> <?= e(t('back_to_account')) ?></a>
                    <?php endif; ?>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="toggleReceiptPreview()" class="receipt-preview-btn inline-flex items-center gap-2 px-4 py-2">
                        <i class="fas fa-eye"></i> Preview
                    </button>
                    <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 border border-luxury-border text-luxury-primary rounded-sm hover:bg-luxury-primary hover:text-white transition-colors font-medium text-sm">
                        <i class="fas fa-print"></i> <?= e(t('print')) ?>
                    </button>
                </div>
            </div>
        
        <h1 class="text-3xl md:text-4xl font-luxury font-bold text-luxury-primary mb-6 md:mb-8 tracking-wide"><?= e(t('order_number_prefix')) ?><?= e((string)$orderId) ?></h1>
        
        <?php
        $flash = getFlashMessage();
        if ($flash):
            $flashType = $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'error' : 'info');
            $bgColor = $flashType === 'success' ? 'bg-green-50' : ($flashType === 'error' ? 'bg-red-50' : 'bg-blue-50');
            $borderColor = $flashType === 'success' ? 'border-green-200' : ($flashType === 'error' ? 'border-red-200' : 'border-blue-200');
            $textColor = $flashType === 'success' ? 'text-green-700' : ($flashType === 'error' ? 'text-red-700' : 'text-blue-700');
        ?>
            <div class="<?= $bgColor . ' ' . $borderColor . ' ' . $textColor ?> border px-4 py-3 rounded-sm mb-6">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
            <!-- Order Items -->
            <div class="lg:col-span-2 order-2 lg:order-1">
                <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8 mb-6">
                    <h2 class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e(t('order_items')) ?></h2>
                    <div class="space-y-4 md:space-y-6">
                        <?php foreach ($orderItems as $item): ?>
                            <div class="flex items-center gap-4 border-b border-luxury-border pb-4 md:pb-6 last:border-0">
                                <?php if ($item['image_url']): ?>
                                    <img src="<?= e($item['image_url']) ?>" 
                                         alt="<?= e($item['name_en']) ?>"
                                         class="w-16 h-16 md:w-20 md:h-20 object-cover rounded-sm flex-shrink-0">
                                <?php endif; ?>
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-semibold text-luxury-primary mb-1"><?= e(getProductName($item)) ?></h3>
                                    <p class="text-sm text-luxury-textLight">
                                        <?= e((string)$item['quantity']) ?> x <?= e(formatPrice((float)$item['unit_price'])) ?>
                                    </p>
                                </div>
                                <p class="font-semibold text-luxury-accent text-end flex-shrink-0">
                                    <?= e(formatPrice((float)$item['quantity'] * (float)$item['unit_price'])) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-6 md:mt-8 pt-4 md:pt-6 border-t border-luxury-border">
                        <div class="flex justify-between items-center">
                            <span class="text-lg md:text-xl font-bold text-luxury-primary"><?= e(t('total')) ?>:</span>
                            <span class="text-xl md:text-2xl font-bold text-luxury-accent font-luxury"><?= e(formatPrice((float)$order['grand_total'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Information -->
            <div class="order-1 lg:order-2 space-y-6">
                <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8">
                    <h2 class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e(t('order_info')) ?></h2>
                    <div class="space-y-4 text-sm md:text-base">
                        <div>
                            <p class="text-luxury-textLight mb-1"><?= e(t('order_date')) ?></p>
                            <p class="font-semibold text-luxury-primary"><?= e(date('F j, Y g:i A', strtotime($order['order_date']))) ?></p>
                        </div>
                        <?php if (isset($order['delivery_date']) && $order['delivery_date']): ?>
                        <div>
                            <p class="text-luxury-textLight mb-1"><?= e(t('delivery_date')) ?></p>
                            <p class="font-semibold text-luxury-primary">
                                <i class="fas fa-calendar-alt me-2 text-luxury-accent"></i>
                                <?= e(date('F j, Y', strtotime($order['delivery_date']))) ?>
                            </p>
                            <?php
                            $daysUntilDelivery = (strtotime($order['delivery_date']) - time()) / (60 * 60 * 24);
                            if ($daysUntilDelivery > 0):
                            ?>
                                <p class="text-xs text-luxury-textLight mt-1">
                                    <?= e(t('in_days', ['days' => (int)ceil($daysUntilDelivery)])) ?>
                                </p>
                            <?php elseif ($daysUntilDelivery <= 0 && $daysUntilDelivery > -1): ?>
                                <p class="text-xs text-green-600 mt-1 font-semibold">
                                    <i class="fas fa-check-circle me-1"></i><?= e(t('delivery_today')) ?>
                                </p>
                            <?php else: ?>
                                <p class="text-xs text-green-600 mt-1 font-semibold">
                                    <i class="fas fa-check-circle me-1"></i><?= e(t('delivered')) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div>
                            <p class="text-luxury-textLight mb-2"><?= e(t('payment_status_label')) ?></p>
                            <span class="px-3 py-1 text-xs md:text-sm rounded-sm <?= $order['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                <?= e(t($order['payment_status'])) ?>
                            </span>
                        </div>
                        <?php if (isset($order['payment_method']) && $order['payment_method']): ?>
                        <div>
                            <p class="text-luxury-textLight mb-2"><?= e(t('payment_method')) ?></p>
                            <div class="flex items-center gap-2 mb-2">
                                <?php if ($order['payment_method'] === 'visa'): ?>
                                    <div class="w-10 h-6 bg-blue-600 rounded flex items-center justify-center">
                                        <span class="text-white font-bold text-xs">VISA</span>
                                    </div>
                                <?php elseif ($order['payment_method'] === 'mastercard'): ?>
                                    <div class="w-10 h-6 bg-red-600 rounded flex items-center justify-center">
                                        <span class="text-white font-bold text-xs">MC</span>
                                    </div>
                                <?php endif; ?>
                                <span class="font-semibold text-luxury-primary">
                                    <?= e(ucfirst($order['payment_method'])) ?>
                                </span>
                            </div>
                            <?php if (isset($order['card_last_four']) && $order['card_last_four']): ?>
                                <p class="text-sm text-luxury-textLight">
                                    <i class="fas fa-credit-card me-1"></i>
                                    <?= e(t('card_ending_in')) ?> •••• <?= e($order['card_last_four']) ?>
                                </p>
                            <?php endif; ?>
                            <?php if (isset($order['cardholder_name']) && $order['cardholder_name']): ?>
                                <p class="text-sm text-luxury-textLight mt-1">
                                    <i class="fas fa-user me-1"></i>
                                    <?= e($order['cardholder_name']) ?>
                                </p>
                            <?php endif; ?>
                            <?php if (isset($order['card_expiry_month']) && isset($order['card_expiry_year'])): ?>
                                <p class="text-sm text-luxury-textLight mt-1">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?= e(sprintf('%02d/%d', (int)$order['card_expiry_month'], (int)$order['card_expiry_year'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($order['order_status'])): ?>
                            <div>
                                <p class="text-luxury-textLight mb-2"><?= e(t('order_status_label')) ?></p>
                                <span class="px-3 py-1 text-xs md:text-sm rounded-sm bg-blue-100 text-blue-800">
                                    <?= e(t($order['order_status'])) ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div>
                                <p class="text-luxury-textLight mb-2"><?= e(t('order_status_label')) ?></p>
                                <span class="px-3 py-1 text-xs md:text-sm rounded-sm bg-gray-100 text-gray-800">
                                    <?= e(t('pending')) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php if (isSuperAdmin() || hasPermission('manage_orders')): ?>
                            <div class="mt-6 pt-6 border-t border-luxury-border">
                                <h3 class="text-lg font-semibold text-luxury-primary mb-4"><?= e(t('admin_actions')) ?></h3>
                                
                                <!-- Update Order Status Form -->
                                <form method="POST" action="order_action.php" class="mb-4">
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= e((string)$orderId) ?>">
                                    
                                    <div class="mb-3">
                                        <label for="order_status" class="block text-sm font-medium text-luxury-text mb-2"><?= e(t('update_order_status')) ?></label>
                                        <select name="order_status" id="order_status" class="w-full px-3 py-2 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent">
                                            <option value="pending" <?= (isset($order['order_status']) && $order['order_status'] === 'pending') ? 'selected' : '' ?>><?= e(t('pending')) ?></option>
                                            <option value="processing" <?= (isset($order['order_status']) && $order['order_status'] === 'processing') ? 'selected' : '' ?>><?= e(t('processing')) ?></option>
                                            <option value="shipped" <?= (isset($order['order_status']) && $order['order_status'] === 'shipped') ? 'selected' : '' ?>><?= e(t('shipped')) ?></option>
                                            <option value="delivered" <?= (isset($order['order_status']) && $order['order_status'] === 'delivered') ? 'selected' : '' ?>><?= e(t('delivered')) ?></option>
                                            <option value="cancelled" <?= (isset($order['order_status']) && $order['order_status'] === 'cancelled') ? 'selected' : '' ?>><?= e(t('cancelled')) ?></option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="w-full bg-luxury-accent text-white px-4 py-2 rounded-sm hover:bg-luxury-primary transition-colors font-medium">
                                        <?= e(t('update_order_status')) ?>
                                    </button>
                                </form>
                                
                                <!-- Update Payment Status Form -->
                                <form method="POST" action="order_action.php" class="mb-4">
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                    <input type="hidden" name="action" value="update_payment_status">
                                    <input type="hidden" name="order_id" value="<?= e((string)$orderId) ?>">
                                    
                                    <div class="mb-3">
                                        <label for="payment_status" class="block text-sm font-medium text-luxury-text mb-2"><?= e(t('update_payment_status')) ?></label>
                                        <select name="payment_status" id="payment_status" class="w-full px-3 py-2 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent">
                                            <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>><?= e(t('pending')) ?></option>
                                            <option value="paid" <?= $order['payment_status'] === 'paid' ? 'selected' : '' ?>><?= e(t('paid')) ?></option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="w-full bg-luxury-accent text-white px-4 py-2 rounded-sm hover:bg-luxury-primary transition-colors font-medium">
                                        <?= e(t('update_payment_status')) ?>
                                    </button>
                                </form>
                                
                                <!-- Update Tracking Number Form -->
                                <form method="POST" action="order_action.php">
                                    <input type="hidden" name="csrf_token" value="<?= e(generateCSRFToken()) ?>">
                                    <input type="hidden" name="action" value="update_tracking">
                                    <input type="hidden" name="order_id" value="<?= e((string)$orderId) ?>">
                                    
                                    <div class="mb-3">
                                        <label for="tracking_number" class="block text-sm font-medium text-luxury-text mb-2"><?= e(t('tracking_number')) ?></label>
                                        <input type="text" name="tracking_number" id="tracking_number" 
                                               value="<?= e($order['tracking_number'] ?? '') ?>" 
                                               class="w-full px-3 py-2 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent"
                                               placeholder="<?= e(t('enter_tracking_number')) ?>">
                                    </div>
                                    
                                    <button type="submit" class="w-full bg-luxury-accent text-white px-4 py-2 rounded-sm hover:bg-luxury-primary transition-colors font-medium">
                                        <?= e(t('update_tracking_number')) ?>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($order['tracking_number']) && $order['tracking_number']): ?>
                            <div>
                                <p class="text-luxury-textLight mb-1"><?= e(t('tracking_number')) ?></p>
                                <p class="font-semibold text-luxury-primary"><?= e($order['tracking_number']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8">
                    <h2 class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e(t('shipping_address')) ?></h2>
                    <p class="text-luxury-text whitespace-pre-line leading-relaxed"><?= e($order['shipping_address']) ?></p>
                </div>

                <?php if ((isAdmin() || isSuperAdmin()) && !empty($order['customer_lat']) && !empty($order['customer_lng'])): ?>
                    <?php
                    $mapUrl = 'https://www.google.com/maps?q=' . rawurlencode($order['customer_lat'] . ',' . $order['customer_lng']);
                    $mapEmbedUrl = $mapUrl . '&output=embed';
                    ?>
                    <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8">
                        <h2 class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e(t('customer_location')) ?></h2>
                        <div class="space-y-2 text-luxury-text">
                            <p><span class="text-luxury-textLight"><?= e(t('latitude')) ?>:</span> <?= e((string)$order['customer_lat']) ?></p>
                            <p><span class="text-luxury-textLight"><?= e(t('longitude')) ?>:</span> <?= e((string)$order['customer_lng']) ?></p>
                            <a href="<?= e($mapUrl) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-luxury-accent hover:text-luxury-primary transition-colors font-medium mt-2">
                                <i class="fas fa-map-marker-alt"></i><?= e(t('open_in_google_maps')) ?>
                            </a>
                        </div>
                        <div class="mt-4 border border-luxury-border rounded-sm overflow-hidden">
                            <iframe
                                src="<?= e($mapEmbedUrl) ?>"
                                class="w-full h-64"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                aria-label="<?= e(t('customer_location')) ?>"
                            ></iframe>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?= modernFooter() ?>
    </div>

    <div class="preview-modal" id="receiptPreviewModal">
        <div class="preview-modal-content">
            <div class="preview-modal-header">
                <h2>Receipt Preview</h2>
                <button class="preview-close-btn" onclick="toggleReceiptPreview()" aria-label="Close">&times;</button>
            </div>
            <div class="receipt">
                <div class="receipt-header">
                    <div>
                        <div class="receipt-brand">Bloom &amp; Vine</div>
                        <div class="receipt-title">Order Summary</div>
                        <div class="receipt-meta">Order #<?= e((string)$orderId) ?></div>
                    </div>
                    <div class="receipt-header-right">
                        <div class="receipt-code">Order Date</div>
                        <div class="receipt-date"><?= e(date('F j, Y g:i A', strtotime($order['order_date']))) ?></div>
                        <div class="receipt-badge <?= $order['payment_status'] === 'paid' ? 'is-paid' : 'is-pending' ?>">
                            <?= e(t($order['payment_status'])) ?>
                        </div>
                    </div>
                </div>

                 <div class="receipt-divider-ornate">✦ ✦ ✦</div>

                <div class="receipt-columns">
                    <div>
                        <div class="receipt-section-title">Order Info</div>
                        <div class="receipt-row">
                            <span class="receipt-muted">Order Date</span>
                            <span><?= e(date('F j, Y g:i A', strtotime($order['order_date']))) ?></span>
                        </div>
                        <?php if (isset($order['delivery_date']) && $order['delivery_date']): ?>
                            <div class="receipt-row">
                                <span class="receipt-muted">Delivery Date</span>
                                <span><?= e(date('F j, Y', strtotime($order['delivery_date']))) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="receipt-row">
                            <span class="receipt-muted">Order Status</span>
                            <span><?= e(t($order['order_status'] ?? 'pending')) ?></span>
                        </div>
                        <?php if (isset($order['payment_method']) && $order['payment_method']): ?>
                            <div class="receipt-row">
                                <span class="receipt-muted">Payment Method</span>
                                <span>
                                    <?= e(ucfirst($order['payment_method'])) ?>
                                    <?php if (isset($order['card_last_four']) && $order['card_last_four']): ?>
                                        (****<?= e($order['card_last_four']) ?>)
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($order['tracking_number']) && $order['tracking_number']): ?>
                            <div class="receipt-row">
                                <span class="receipt-muted">Tracking Number</span>
                                <span><?= e($order['tracking_number']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <div class="receipt-section-title">Customer Info</div>
                        <div class="receipt-strong"><?= e($order['full_name']) ?></div>
                        <div class="receipt-muted"><?= e($order['email']) ?></div>
                    </div>
                </div>

                <div class="receipt-divider"></div>

                <div>
                    <div class="receipt-section-title">Shipping Address</div>
                    <div class="receipt-address"><?= e($order['shipping_address']) ?></div>
                </div>

                <div class="receipt-divider-ornate">✦ ✦ ✦</div>

                <div>
                    <div class="receipt-section-title">Order Items</div>
                    <div class="receipt-items-header">
                        <span>Product</span>
                        <span class="receipt-right">Qty</span>
                        <span class="receipt-right">Price</span>
                        <span class="receipt-right">Total</span>
                    </div>
                    <?php $previewSubtotal = 0.0; ?>
                    <?php foreach ($orderItems as $item): ?>
                        <?php $previewLineTotal = (float)$item['quantity'] * (float)$item['unit_price']; ?>
                        <?php $previewSubtotal += $previewLineTotal; ?>
                        <div class="receipt-item-row">
                            <span><?= e(getProductName($item)) ?></span>
                            <span class="receipt-right"><?= e((string)$item['quantity']) ?></span>
                            <span class="receipt-right"><?= e(formatPrice((float)$item['unit_price'])) ?></span>
                            <span class="receipt-right"><?= e(formatPrice($previewLineTotal)) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="receipt-totals">
                        <div class="receipt-row">
                            <span class="receipt-muted">Subtotal</span>
                            <span><?= e(formatPrice($previewSubtotal)) ?></span>
                        </div>
                        <div class="receipt-row receipt-total">
                            <span>Total</span>
                            <span><?= e(formatPrice((float)$order['grand_total'])) ?></span>
                        </div>
                    </div>
                </div>

                <div class="receipt-divider"></div>

                <div class="receipt-footer">
                    <div style="margin-bottom: 10px; color: var(--receipt-accent); font-size: 12px; letter-spacing: 0.5em; opacity: 0.6;">✦ ✦ ✦</div>
                    <div style="margin-bottom: 6px; color: var(--receipt-ink); font-weight: 600;">Thank you for your order</div>
                    <div style="font-size: 10px; color: var(--receipt-muted); margin-top: 6px;">Bloom &amp; Vine</div>
                    <div style="font-size: 10px; color: var(--receipt-muted); margin-top: 2px; letter-spacing: 0.05em;">Order #<?= e((string)$orderId) ?></div>
                </div>
            </div>
            <div class="preview-actions">
                <button class="preview-print-btn" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>

    <script>
        function toggleReceiptPreview() {
            const modal = document.getElementById('receiptPreviewModal');
            modal.classList.toggle('active');
        }

        document.getElementById('receiptPreviewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    </script>
</body>
</html>

