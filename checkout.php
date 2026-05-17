<?php
declare(strict_types=1);

/**
 * Checkout Page
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';
require_once __DIR__ . '/src/components.php';
require_once __DIR__ . '/src/email.php';
require_once __DIR__ . '/src/FibService.php';

requireLogin();

$pdo = getDB();
$error = '';

// Get available extras
$availableExtras = getAvailableExtras();

// Get cart items - supports compound keys like "12_v_3_5"
$cartItems = [];
$cartTotal = 0.0;
$currency = (string)getSystemSetting('currency', 'IQD ');
$usdToIqdRate = (float)getSystemSetting('usd_to_iqd_rate', 1300);
$isIqdCurrency = strtoupper(trim($currency)) === 'IQD' || str_starts_with(strtoupper(trim($currency)), 'IQD');

if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $cartKeys = array_keys($_SESSION['cart']);
    $productIdMap = [];
    $variantIdMap = [];
    foreach ($cartKeys as $cartKey) {
        $cartKey = (string)$cartKey;
        if (strpos($cartKey, '_v_') !== false) {
            [$pid, $vids] = explode('_v_', $cartKey, 2);
            $productIdMap[$cartKey] = (int)$pid;
            $variantIdMap[$cartKey] = array_map('intval', explode('_', $vids));
        } else {
            $productIdMap[$cartKey] = (int)$cartKey;
            $variantIdMap[$cartKey] = [];
        }
    }
    $uniqueProductIds = array_unique(array_values($productIdMap));
    if (!empty($uniqueProductIds)) {
        $placeholders = implode(',', array_fill(0, count($uniqueProductIds), '?'));
        $stmt = $pdo->prepare("SELECT p.*, c.name_en as category_name_en, c.name_ku as category_name_ku 
                               FROM products p 
                               JOIN categories c ON p.category_id = c.id 
                               WHERE p.id IN ({$placeholders})");
        $stmt->execute($uniqueProductIds);
        $productsById = [];
        foreach ($stmt->fetchAll() as $p) { $productsById[(int)$p['id']] = $p; }
        
        $allVariantIds = array_unique(array_merge(...array_values($variantIdMap)));
        $variantsById = [];
        if (!empty($allVariantIds)) {
            $vPlaceholders = implode(',', array_fill(0, count($allVariantIds), '?'));
            $vStmt = $pdo->prepare("SELECT * FROM product_variants WHERE id IN ({$vPlaceholders})");
            $vStmt->execute($allVariantIds);
            foreach ($vStmt->fetchAll() as $v) { $variantsById[(int)$v['id']] = $v; }
        }
        
        foreach ($cartKeys as $cartKey) {
            $cartKey = (string)$cartKey;
            $productId = $productIdMap[$cartKey] ?? 0;
            $product = $productsById[$productId] ?? null;
            if (!$product) continue;
            $quantity = (int)($_SESSION['cart'][$cartKey] ?? 0);
            if ($quantity <= 0) continue;
            $variantPrice = 0.0;
            $variantLabels = [];
            foreach (($variantIdMap[$cartKey] ?? []) as $vid) {
                if (isset($variantsById[$vid])) {
                    $variantPrice += (float)$variantsById[$vid]['price_adjustment'];
                    $variantLabels[] = $variantsById[$vid]['name_en'];
                }
            }
            $unitPrice = (float)$product['price'] + $variantPrice;
            $product['cart_key']       = $cartKey;
            $product['cart_quantity']  = $quantity;
            $product['unit_price']     = $unitPrice;
            $product['variant_labels'] = $variantLabels;
            $product['variants_summary'] = !empty($variantLabels) ? implode(', ', $variantLabels) : null;
            $product['subtotal']       = $unitPrice * $quantity;
            $cartItems[] = $product;
            $cartTotal  += $product['subtotal'];
        }
    }
}

$appliedCoupon = getAppliedCoupon($cartTotal);
$finalCartTotal = $cartTotal;
$discountAmount = 0.0;
$couponId = null;
if ($appliedCoupon) {
    $discountAmount = $appliedCoupon['discount_amount'];
    $finalCartTotal -= $discountAmount;
    $couponId = $appliedCoupon['id'];
}

// Redirect if cart is empty
if (empty($cartItems)) {
    redirect('cart.php', e(t('empty_cart')), 'error');
}

// Get user info
$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT full_name, email FROM users WHERE id = :id');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = sanitizeInput('csrf_token', 'POST');
    $shippingAddress = sanitizeInput('shipping_address', 'POST');
    $deliveryDate = sanitizeInput('delivery_date', 'POST');
    $customerLat = sanitizeInput('customer_lat', 'POST');
    $customerLng = sanitizeInput('customer_lng', 'POST');
    $paymentMethod = sanitizeInput('payment_method', 'POST');
    $cardNumber = sanitizeInput('card_number', 'POST');
    $cardholderName = sanitizeInput('cardholder_name', 'POST');
    $expiryMonth = sanitizeInput('expiry_month', 'POST');
    $expiryYear = sanitizeInput('expiry_year', 'POST');
    $cvv = sanitizeInput('cvv', 'POST');
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = t('order_error');
    } elseif (empty($shippingAddress)) {
        $error = t('order_error') . ' - ' . t('shipping_address_required');
    } elseif (empty($deliveryDate)) {
        $error = t('delivery_date_required');
    } elseif (!strtotime($deliveryDate) || strtotime($deliveryDate) < strtotime('tomorrow')) {
        $error = t('delivery_date_invalid');
    } elseif ($customerLat === '' || $customerLng === '') {
        $error = t('delivery_location_required');
    } elseif (!is_numeric($customerLat) || !is_numeric($customerLng)) {
        $error = t('delivery_location_required');
    } elseif (empty($paymentMethod) || !in_array($paymentMethod, ['visa', 'mastercard', 'fib'], true)) {
        $error = t('payment_method_required');
    } elseif ($paymentMethod !== 'fib' && empty($cardNumber)) {
        $error = t('card_number_required');
    } elseif ($paymentMethod !== 'fib' && empty($cardholderName)) {
        $error = t('cardholder_name_required');
    } elseif ($paymentMethod !== 'fib' && (empty($expiryMonth) || empty($expiryYear))) {
        $error = t('expiry_date_required');
    } elseif ($paymentMethod !== 'fib' && empty($cvv)) {
        $error = t('cvv_required');
    } else {
        $cardNumberClean = '';
        $cardLastFour = null;
        $expiryMonthInt = null;
        $expiryYearInt = null;

        if ($paymentMethod !== 'fib') {
            // Validate card number (basic validation)
            $cardNumberClean = preg_replace('/\s+/', '', $cardNumber);
            if (!preg_match('/^\d{16}$/', $cardNumberClean)) {
                $error = t('card_number_invalid');
            } elseif ($paymentMethod === 'visa' && !preg_match('/^4\d{15}$/', $cardNumberClean)) {
                $error = t('card_number_invalid') . ' - ' . t('visa_start_4');
            } elseif ($paymentMethod === 'mastercard' && !preg_match('/^5[1-5]\d{14}$/', $cardNumberClean)) {
                $error = t('card_number_invalid') . ' - ' . t('mastercard_start_5');
            } else {
                // Validate expiry date
                $expiryMonthInt = (int)$expiryMonth;
                $expiryYearInt = (int)$expiryYear;
                $currentYear = (int)date('Y');
                $currentMonth = (int)date('n');
                
                if ($expiryYearInt < $currentYear || ($expiryYearInt === $currentYear && $expiryMonthInt < $currentMonth)) {
                    $error = t('expiry_date_invalid');
                } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
                    $error = t('cvv_invalid');
                } else {
                    $cardLastFour = substr($cardNumberClean, -4);
                }
            }
        }

        if (!$error) {
                $storeCoords = getStoreCoordinates();
                $distanceKm = haversineDistanceKm((float)$customerLat, (float)$customerLng, $storeCoords['lat'], $storeCoords['lng']);
                $deliveryFee = getDeliveryFeeByDistance($distanceKm);
                
                // Get selected extras
                $selectedExtras = isset($_POST['extras']) && is_array($_POST['extras']) ? $_POST['extras'] : [];
                $selectedExtraIds = [];
                $extrasTotal = 0.0;
                
                // Validate and calculate extras total
                foreach ($selectedExtras as $extraId) {
                    $eid = (int)trim($extraId);
                    if ($eid > 0) {
                        $selectedExtraIds[] = $eid;
                    }
                }

                if (!empty($selectedExtraIds)) {
                    $placeholders = implode(',', array_fill(0, count($selectedExtraIds), '?'));
                    $stmt = $pdo->prepare("SELECT id, price FROM available_extras WHERE id IN ({$placeholders}) AND is_active = TRUE");
                    $stmt->execute($selectedExtraIds);
                    $extras = $stmt->fetchAll();
                    
                    foreach ($extras as $extra) {
                        $extrasTotal += (float)$extra['price'];
                    }
                }

                if ($deliveryFee === null) {
                    $error = t('delivery_out_of_range');
                } else {
                    $grandTotal = $finalCartTotal + $deliveryFee + $extrasTotal;
                    $customerLatValue = (float)$customerLat;
                    $customerLngValue = (float)$customerLng;
                    // Extract last 4 digits for storage
                    $cardLastFour = substr($cardNumberClean, -4);
                    
                    try {
                        $pdo->beginTransaction();
                        
                        // Verify stock availability before creating order
                        $stockOk = true;
                        $insufficientStockQty = null;
                        foreach ($cartItems as $item) {
                            $stmt = $pdo->prepare('SELECT stock_qty FROM products WHERE id = :id FOR UPDATE');
                            $stmt->execute(['id' => $item['id']]);
                            $product = $stmt->fetch();
                            
                            if (!$product || (int)$product['stock_qty'] < $item['cart_quantity']) {
                                $stockOk = false;
                                $insufficientStockQty = (int)($product['stock_qty'] ?? 0);
                                break;
                            }
                        }
                        
                        if (!$stockOk) {
                            $pdo->rollBack();
                            if ($insufficientStockQty !== null) {
                                $error = t('order_error') . ' - ' . t('insufficient_stock_available', ['available' => $insufficientStockQty]);
                            } else {
                                $error = t('order_error') . ' - ' . t('insufficient_stock');
                            }
                        } else {
                            // Create order with payment details
                            $stmt = $pdo->prepare('
                                INSERT INTO orders (user_id, grand_total, discount_amount, coupon_id, payment_status, shipping_address, customer_lat, customer_lng, delivery_date, payment_method, card_last_four, cardholder_name, card_expiry_month, card_expiry_year, fib_payment_id)
                                VALUES (:user_id, :grand_total, :discount_amount, :coupon_id, :payment_status, :shipping_address, :customer_lat, :customer_lng, :delivery_date, :payment_method, :card_last_four, :cardholder_name, :card_expiry_month, :card_expiry_year, :fib_payment_id)
                            ');
                            $stmt->execute([
                                'user_id' => $userId,
                                'grand_total' => $grandTotal,
                                'discount_amount' => $discountAmount,
                                'coupon_id' => $couponId,
                                'payment_status' => ($paymentMethod === 'fib') ? 'pending' : 'paid',
                                'shipping_address' => $shippingAddress,
                                'customer_lat' => $customerLatValue,
                                'customer_lng' => $customerLngValue,
                                'delivery_date' => $deliveryDate,
                                'payment_method' => $paymentMethod,
                                'card_last_four' => $cardLastFour,
                                'cardholder_name' => $cardholderName,
                                'card_expiry_month' => $expiryMonthInt,
                                'card_expiry_year' => $expiryYearInt,
                                'fib_payment_id' => null
                            ]);
                            
                            $orderId = (int)$pdo->lastInsertId();
                            
                            // Create order items and update stock
                            foreach ($cartItems as $item) {
                                // Insert order item
                                $stmt = $pdo->prepare('
                                    INSERT INTO order_items (order_id, product_id, quantity, unit_price, variants_summary)
                                    VALUES (:order_id, :product_id, :quantity, :unit_price, :variants_summary)
                                ');
                                $stmt->execute([
                                    'order_id' => $orderId,
                                    'product_id' => $item['id'],
                                    'quantity' => $item['cart_quantity'],
                                    'unit_price' => $item['unit_price'] ?? $item['price'],
                                    'variants_summary' => $item['variants_summary'] ?? null
                                ]);
                                
                                // Update product stock
                                $stmt = $pdo->prepare('UPDATE products SET stock_qty = stock_qty - :quantity WHERE id = :id');
                                $stmt->execute([
                                    'quantity' => $item['cart_quantity'],
                                    'id' => $item['id']
                                ]);
                            }
                            
                            // Add selected extras to order
                            if (!empty($selectedExtraIds) && !empty($extras)) {
                                foreach ($extras as $extra) {
                                    $stmt = $pdo->prepare('
                                        INSERT INTO order_extras (order_id, extra_type, extra_name_en, extra_name_ku, unit_price)
                                        SELECT :order_id, extra_type, name_en, name_ku, price
                                        FROM available_extras
                                        WHERE id = :extra_id
                                    ');
                                    $stmt->execute([
                                        'order_id' => $orderId,
                                        'extra_id' => $extra['id']
                                    ]);
                                }
                            }
                            
                            if ($couponId) {
                                $stmt = $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = :id');
                                $stmt->execute(['id' => $couponId]);
                            }

                            if ($paymentMethod === 'fib') {
                                try {
                                    $callbackUrl = getSiteURL() . '/fib_webhook.php';
                                    $redirectUrl = getSiteURL() . '/order_details.php?id=' . $orderId;
                                    $fibPayment = FibService::createPayment((int)$grandTotal, "Order #{$orderId}", $callbackUrl, $redirectUrl);
                                    
                                    if (isset($fibPayment['paymentId'])) {
                                        $stmt = $pdo->prepare('UPDATE orders SET fib_payment_id = :fib_id, fib_qr_code = :qr, fib_app_link = :link WHERE id = :id');
                                        $stmt->execute([
                                            'fib_id' => $fibPayment['paymentId'], 
                                            'qr' => $fibPayment['qrCode'] ?? null,
                                            'link' => $fibPayment['personalAppLink'] ?? null,
                                            'id' => $orderId
                                        ]);
                                        
                                        $pdo->commit();
                                        $_SESSION['cart'] = [];
                                        clearCoupon();
                                        
                                        // Redirect to specialized FIB payment page
                                        redirect('fib_payment.php?order_id=' . $orderId, t('pay_with_fib'), 'info');
                                    } else {
                                        throw new Exception("Invalid FIB response");
                                    }
                                } catch (Exception $e) {
                                    $pdo->rollBack();
                                    error_log('FIB Create Payment error: ' . $e->getMessage());
                                    $error = t('order_error') . ' (FIB: ' . $e->getMessage() . ')';
                                }
                            } else {
                                $pdo->commit();
                                
                                // Clear cart
                                $_SESSION['cart'] = [];
                                clearCoupon();

                                // Send order confirmation email to customer
                                $customerEmail = $user['email'];
                                $customerSubject = t('order_confirmation_subject', ['order_id' => $orderId]);
                                $customerBody = "<h1>" . t('thank_you_for_order') . "</h1>";
                                $customerBody .= "<p>" . t('order_details_below') . "</p>";
                                $customerBody .= "<p><strong>" . t('order_id') . ":</strong> {$orderId}</p>";
                                $customerBody .= "<p><strong>" . t('grand_total') . ":</strong> " . formatPrice((float)$grandTotal, $currency) . "</p>";
                                $customerBody .= "<p><strong>" . t('shipping_address') . ":</strong> {$shippingAddress}</p>";
                                $customerBody .= "<p>" . t('track_order_in_account') . "</p>";
                                sendEmail($customerEmail, $customerSubject, $customerBody);

                                // Send new order notification to admin
                                $emailConfig = require __DIR__ . '/src/email.php';
                                $adminEmail = $emailConfig['admin_email'];
                                $adminSubject = t('new_order_notification_subject', ['order_id' => $orderId]);
                                $adminBody = "<h1>" . t('new_order_received') . "</h1>";
                                $adminBody .= "<p>" . t('order_details_below') . "</p>";
                                $adminBody .= "<p><strong>" . t('order_id') . ":</strong> {$orderId}</p>";
                                $adminBody .= "<p><strong>" . t('customer') . ":</strong> {$user['full_name']} ({$user['email']})</p>";
                                $adminBody .= "<p><strong>" . t('grand_total') . ":</strong> " . formatPrice((float)$grandTotal, $currency) . "</p>";
                                $adminBody .= "<p><a href='" . getSiteURL() . "/admin/order_details.php?id={$orderId}'>" . t('view_order_details') . "</a></p>";
                                sendEmail($adminEmail, $adminSubject, $adminBody);
                                
                                redirect('index.php', t('order_placed'), 'success');
                            }
                        }
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        error_log('Checkout error: ' . $e->getMessage());
                        $error = t('order_error');
                    }
                }
            }
        }
}

$csrfToken = generateCSRFToken();
$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/src/pwa_head.php'; ?>
    <title><?= e(t('checkout')) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
    <style>
        .extras-slider { scrollbar-width: none; -ms-overflow-style: none; scroll-behavior: smooth; }
        .extras-slider::-webkit-scrollbar { display: none; }
        .extras-slide { flex-shrink: 0; width: 148px; }
        @media (min-width: 480px) { .extras-slide { width: 168px; } }
        @media (min-width: 768px) { .extras-slide { width: 186px; } }
        @media (min-width: 1024px) { .sticky-summary { position: sticky; top: 88px; } }
        .extra-option { transition: border-color .2s, box-shadow .2s, background-color .2s; }
        .check-icon { transition: opacity .15s ease; }
        .slider-btn { transition: all .15s ease; }
        .slider-btn:hover { transform: scale(1.1); }
        .slider-btn:active { transform: scale(0.95); }
        .payment-card-label { transition: all .2s ease; }
        .payment-card-label:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,0.08); }
        .form-control:focus { outline: none; }
        input[type="date"]::-webkit-calendar-picker-indicator { cursor: pointer; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/src/header.php'; ?>

    <!-- Checkout Progress Bar -->
    <div class="bg-white border-b border-luxury-border shadow-sm">
        <div class="container mx-auto px-4 md:px-6 py-3">
            <ol class="flex items-center gap-2 text-xs sm:text-sm">
                <li class="flex items-center gap-1.5">
                    <span class="w-6 h-6 rounded-full bg-green-500 text-white flex items-center justify-center"><i class="fas fa-check" style="font-size:9px"></i></span>
                    <a href="cart.php" class="text-green-600 font-medium hover:underline hidden sm:inline"><?= e(t('cart')) ?></a>
                </li>
                <li class="flex-1 max-w-12 h-px bg-luxury-border"></li>
                <li class="flex items-center gap-1.5">
                    <span class="w-6 h-6 rounded-full bg-luxury-accent text-white flex items-center justify-center font-bold text-xs">2</span>
                    <span class="text-luxury-accent font-semibold hidden sm:inline"><?= e(t('checkout')) ?></span>
                </li>
                <li class="flex-1 max-w-12 h-px bg-luxury-border"></li>
                <li class="flex items-center gap-1.5">
                    <span class="w-6 h-6 rounded-full border-2 border-luxury-border text-luxury-textLight flex items-center justify-center text-xs">3</span>
                    <span class="text-luxury-textLight hidden sm:inline"><?= e(t('confirmation')) ?></span>
                </li>
            </ol>
        </div>
    </div>


    <div class="container mx-auto px-4 md:px-6 py-6 md:py-10">
        <h1 class="text-2xl md:text-3xl font-luxury font-bold text-luxury-primary mb-5 md:mb-7 tracking-wide"><?= e(t('checkout')) ?></h1>

        <?php if ($error): ?>
        <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-600 px-4 py-3.5 rounded-xl mb-6">
            <i class="fas fa-exclamation-circle mt-0.5 flex-shrink-0 text-red-400"></i>
            <span class="text-sm font-medium"><?= e($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="customer_lat" id="customer_lat" value="<?= e(sanitizeInput('customer_lat', 'POST', '')) ?>">
            <input type="hidden" name="customer_lng" id="customer_lng" value="<?= e(sanitizeInput('customer_lng', 'POST', '')) ?>">
            <input type="hidden" name="delivery_distance_km" id="delivery_distance_km" value="<?= e(sanitizeInput('delivery_distance_km', 'POST', '')) ?>">
            <input type="hidden" name="delivery_fee" id="delivery_fee" value="<?= e(sanitizeInput('delivery_fee', 'POST', '')) ?>">
            <input type="hidden" name="extras_total" id="extras_total" value="0.00">

            <!--
                GRID LAYOUT (3 children, 5-col desktop):
                  Mobile  : Delivery → Payment → Extras (order 1,2,3)
                  Desktop : [Delivery    | Payment (rows 1+2)]
                            [Extras      |                    ]
            -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 lg:gap-7">

                <!-- ① DELIVERY DETAILS (order-1 everywhere) -->
                <div class="lg:col-span-3 order-1">
                    <div class="bg-white border border-luxury-border rounded-2xl shadow-sm p-5 md:p-7">
                        <h2 class="text-lg font-luxury font-bold text-luxury-primary mb-5 tracking-wide flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-xl bg-luxury-accent/10 flex items-center justify-center">
                                <i class="fas fa-user text-luxury-accent text-sm"></i>
                            </span>
                            <?= e(t('customer_info')) ?>
                        </h2>

                        <!-- User pill -->
                        <div class="flex items-center gap-3 p-3 bg-luxury-border/30 rounded-xl mb-5">
                            <div class="w-10 h-10 rounded-full bg-luxury-accent/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-user text-luxury-accent text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-luxury-primary truncate"><?= e($user['full_name']) ?></p>
                                <p class="text-xs text-luxury-textLight truncate"><?= e($user['email']) ?></p>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <!-- Shipping Address -->
                            <div>
                                <label for="shipping_address" class="block text-sm font-semibold text-luxury-text mb-1.5">
                                    <?= e(t('shipping_address')) ?> <span class="text-red-400">*</span>
                                </label>
                                <textarea id="shipping_address" name="shipping_address" rows="3" required
                                          class="w-full px-4 py-3 border border-luxury-border rounded-xl text-sm resize-none transition-colors"
                                          style="outline:none"
                                          placeholder="<?= e(t('enter_shipping_address')) ?>"><?= e(sanitizeInput('shipping_address', 'POST', '')) ?></textarea>

                                <!-- Location button + status -->
                                <div class="mt-3">
                                    <button type="button" id="use-location"
                                            class="inline-flex items-center gap-2 border-2 border-luxury-accent text-luxury-accent px-4 py-2 rounded-lg hover:bg-luxury-accent hover:text-white transition-all duration-200 font-semibold text-sm w-full sm:w-auto justify-center">
                                        <i class="fas fa-location-crosshairs" id="loc-icon"></i>
                                        <span id="loc-btn-text"><?= e(t('use_my_location')) ?></span>
                                    </button>
                                    <div id="delivery-status-box" class="mt-2 hidden">
                                        <div id="delivery-status" class="text-xs font-medium px-3 py-2 rounded-lg bg-gray-50 border border-gray-200 text-gray-600"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Delivery Date -->
                            <div>
                                <label for="delivery_date" class="block text-sm font-semibold text-luxury-text mb-1.5">
                                    <?= e(t('delivery_date')) ?> <span class="text-red-400">*</span>
                                </label>
                                <input type="date" id="delivery_date" name="delivery_date" required
                                       min="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>"
                                       value="<?= e(sanitizeInput('delivery_date', 'POST', '')) ?>"
                                       class="w-full px-4 py-3 border border-luxury-border rounded-xl text-sm transition-colors"
                                       style="outline:none">
                                <p class="text-xs text-luxury-textLight mt-1.5 flex items-center gap-1">
                                    <i class="fas fa-info-circle text-luxury-accent/70"></i>
                                    <?= e(t('delivery_date_hint')) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ② ORDER SUMMARY + PAYMENT (order-2 on mobile; right column spanning 2 rows on desktop) -->
                <div class="lg:col-span-2 lg:row-span-2 order-2 space-y-5 lg:sticky lg:top-24">

                    <!-- Order Summary -->
                    <div class="bg-white border border-luxury-border rounded-2xl shadow-sm p-5 md:p-6">
                        <h2 class="text-lg font-luxury font-bold text-luxury-primary mb-4 tracking-wide flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-xl bg-luxury-accent/10 flex items-center justify-center">
                                <i class="fas fa-receipt text-luxury-accent text-sm"></i>
                            </span>
                            <?= e(t('order_summary')) ?>
                        </h2>

                        <!-- Cart Items -->
                        <div class="space-y-3 mb-4 max-h-52 overflow-y-auto overscroll-contain pr-1">
                            <?php foreach ($cartItems as $item): ?>
                            <div class="flex justify-between items-start gap-2 pb-3 border-b border-luxury-border/50 last:border-0 last:pb-0">
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-luxury-primary text-sm leading-tight"><?= e(getProductName($item)) ?></p>
                                    <?php if (!empty($item['variant_labels'])): ?>
                                    <div class="flex flex-wrap gap-0.5 mt-0.5">
                                        <?php foreach ($item['variant_labels'] as $vl): ?>
                                        <span class="inline-block bg-luxury-accentLight/60 text-luxury-primary px-1.5 py-0.5 rounded text-xs"><?= e($vl) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    <p class="text-xs text-luxury-textLight mt-0.5"><?= e((string)$item['cart_quantity']) ?> × <?= e(formatPrice($item['unit_price'] ?? (float)$item['price'], $currency)) ?></p>
                                </div>
                                <p class="font-bold text-luxury-accent text-sm flex-shrink-0"><?= e(formatPrice($item['subtotal'], $currency)) ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pricing Breakdown -->
                        <div class="space-y-2 border-t border-luxury-border pt-3">
                            <div class="flex justify-between text-xs text-luxury-textLight">
                                <span><?= e(t('delivery_distance')) ?></span>
                                <span id="delivery-distance" class="font-medium">—</span>
                            </div>
                            <div class="flex justify-between text-xs text-luxury-textLight">
                                <span><?= e(t('delivery_fee')) ?></span>
                                <span id="delivery-fee-amount" class="font-medium">—</span>
                            </div>
                            <div class="flex justify-between text-xs text-luxury-textLight">
                                <span><?= e(t('extras_total')) ?></span>
                                <span id="extras-total-amount" class="font-medium"><?= e(formatPrice(0.0, $currency)) ?></span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-luxury-border/60">
                                <span class="text-sm text-luxury-text"><?= e(t('subtotal')) ?></span>
                                <span class="text-sm font-semibold text-luxury-accent font-luxury"><?= e(formatPrice($cartTotal, $currency)) ?></span>
                            </div>
                            <?php if ($appliedCoupon): ?>
                            <div class="flex justify-between items-center text-sm text-pink-600">
                                <span class="flex items-center gap-1.5">
                                    <?= e(t('discount')) ?>
                                    <span class="text-xs bg-pink-50 border border-pink-200 px-1.5 py-0.5 rounded font-mono"><?= e($appliedCoupon['code']) ?></span>
                                </span>
                                <span class="font-bold">-<?= e(formatPrice($discountAmount, $currency)) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex justify-between items-center pt-2 border-t-2 border-luxury-border">
                                <span class="font-bold text-luxury-primary text-sm"><?= e(t('total')) ?></span>
                                <span class="text-lg font-bold text-luxury-accent font-luxury" id="subtotal-amount" data-base-total="<?= e((string)$finalCartTotal) ?>"><?= e(formatPrice($finalCartTotal, $currency)) ?></span>
                            </div>
                            <div class="flex justify-between items-center bg-luxury-border/30 rounded-xl px-3 py-2.5 -mx-1">
                                <span class="font-bold text-luxury-primary text-sm"><?= e(t('delivery_total')) ?></span>
                                <span class="text-xl font-bold text-luxury-accent font-luxury" id="grand-total-amount">—</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Card -->
                    <div class="bg-white border border-luxury-border rounded-2xl shadow-sm p-5 md:p-6">
                        <h2 class="text-lg font-luxury font-bold text-luxury-primary mb-4 tracking-wide flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-xl bg-luxury-accent/10 flex items-center justify-center">
                                <i class="fas fa-credit-card text-luxury-accent text-sm"></i>
                            </span>
                            <?= e(t('payment_method')) ?>
                        </h2>

                        <!-- Payment Options -->
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            <label class="payment-card-label payment-method-option relative flex flex-col items-center gap-2 p-3 border-2 rounded-xl cursor-pointer transition-all <?= (sanitizeInput('payment_method', 'POST', '') === 'fib' || sanitizeInput('payment_method', 'POST', '') === '') ? 'border-[#00A69C] bg-[#00A69C]/5 shadow-sm' : 'border-luxury-border hover:border-luxury-accent' ?>">
                                <input type="radio" name="payment_method" value="fib" required class="sr-only" <?= (sanitizeInput('payment_method', 'POST', '') === 'fib' || sanitizeInput('payment_method', 'POST', '') === '') ? 'checked' : '' ?>>
                                <div class="w-11 h-7 bg-[#00A69C] rounded-lg flex items-center justify-center shadow-sm">
                                    <span class="text-white font-bold text-xs">FIB</span>
                                </div>
                                <span class="font-semibold text-luxury-primary text-xs text-center"><?= e(t('fib')) ?></span>
                            </label>
                            <label class="payment-card-label payment-method-option relative flex flex-col items-center gap-2 p-3 border-2 rounded-xl cursor-pointer transition-all <?= (sanitizeInput('payment_method', 'POST', '') === 'visa') ? 'border-luxury-accent bg-luxury-border shadow-sm' : 'border-luxury-border hover:border-luxury-accent' ?>">
                                <input type="radio" name="payment_method" value="visa" required class="sr-only" <?= (sanitizeInput('payment_method', 'POST', '') === 'visa') ? 'checked' : '' ?>>
                                <div class="w-11 h-7 bg-[#1A1F71] rounded-lg flex items-center justify-center shadow-sm">
                                    <span class="text-white font-bold text-xs italic tracking-tight">VISA</span>
                                </div>
                                <span class="font-semibold text-luxury-primary text-xs text-center"><?= e(t('visa')) ?></span>
                            </label>
                            <label class="payment-card-label payment-method-option relative flex flex-col items-center gap-2 p-3 border-2 rounded-xl cursor-pointer transition-all <?= (sanitizeInput('payment_method', 'POST', '') === 'mastercard') ? 'border-luxury-accent bg-luxury-border shadow-sm' : 'border-luxury-border hover:border-luxury-accent' ?>">
                                <input type="radio" name="payment_method" value="mastercard" required class="sr-only" <?= (sanitizeInput('payment_method', 'POST', '') === 'mastercard') ? 'checked' : '' ?>>
                                <div class="w-11 h-7 rounded-lg flex items-center justify-center shadow-sm overflow-hidden relative">
                                    <div class="absolute inset-0" style="background:linear-gradient(to right,#EB001B 50%,#F79E1B 50%)"></div>
                                    <span class="relative z-10 text-white font-bold text-xs">MC</span>
                                </div>
                                <span class="font-semibold text-luxury-primary text-xs text-center"><?= e(t('mastercard')) ?></span>
                            </label>
                        </div>

                        <!-- Card Details -->
                        <div id="card-details-section" class="<?= (sanitizeInput('payment_method', 'POST', '') === 'fib' || sanitizeInput('payment_method', 'POST', '') === '') ? 'hidden' : '' ?> space-y-3 mt-2 pt-4 border-t border-luxury-border">
                            <div>
                                <label for="card_number" class="block text-xs font-semibold text-luxury-text mb-1.5"><?= e(t('card_number')) ?> <span class="text-red-400">*</span></label>
                                <input type="text" id="card_number" name="card_number"
                                       placeholder="1234 5678 9012 3456" maxlength="19"
                                       class="w-full px-3.5 py-2.5 border border-luxury-border rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-luxury-accent/30 focus:border-luxury-accent transition-colors">
                            </div>
                            <div>
                                <label for="cardholder_name" class="block text-xs font-semibold text-luxury-text mb-1.5"><?= e(t('cardholder_name')) ?> <span class="text-red-400">*</span></label>
                                <input type="text" id="cardholder_name" name="cardholder_name"
                                       placeholder="NAME ON CARD"
                                       value="<?= e(sanitizeInput('cardholder_name', 'POST', '')) ?>"
                                       class="w-full px-3.5 py-2.5 border border-luxury-border rounded-xl text-sm uppercase focus:outline-none focus:ring-2 focus:ring-luxury-accent/30 focus:border-luxury-accent transition-colors">
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="col-span-2">
                                    <label class="block text-xs font-semibold text-luxury-text mb-1.5"><?= e(t('expiry_date')) ?> <span class="text-red-400">*</span></label>
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <select name="expiry_month" id="expiry_month" class="px-2 py-2.5 border border-luxury-border rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-luxury-accent/30 focus:border-luxury-accent transition-colors">
                                            <option value=""><?= e(t('expiry_month')) ?></option>
                                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?= e(sprintf('%02d', $i)) ?>"><?= e(sprintf('%02d', $i)) ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <select name="expiry_year" id="expiry_year" class="px-2 py-2.5 border border-luxury-border rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-luxury-accent/30 focus:border-luxury-accent transition-colors">
                                            <option value=""><?= e(t('expiry_year')) ?></option>
                                            <?php for ($i = (int)date('Y'); $i <= (int)date('Y') + 10; $i++): ?>
                                            <option value="<?= e((string)$i) ?>"><?= e((string)$i) ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label for="cvv" class="block text-xs font-semibold text-luxury-text mb-1.5">CVV <span class="text-red-400">*</span></label>
                                    <input type="text" id="cvv" name="cvv"
                                           placeholder="•••" maxlength="4"
                                           class="w-full px-2 py-2.5 border border-luxury-border rounded-xl text-sm font-mono text-center focus:outline-none focus:ring-2 focus:ring-luxury-accent/30 focus:border-luxury-accent transition-colors">
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-5 pt-4 border-t border-luxury-border space-y-3">
                            <?php
                            $isFibSelected = (sanitizeInput('payment_method', 'POST', '') === 'fib' || sanitizeInput('payment_method', 'POST', '') === '');
                            $btnStyle = $isFibSelected ? 'style="background-color:#00A69C;"' : '';
                            $btnText  = $isFibSelected
                                ? '<i class="fas fa-wallet"></i> ' . e(t('pay_with_fib'))
                                : '<i class="fas fa-check-circle"></i> ' . e(t('place_order'));
                            ?>
                            <button type="submit" id="place-order-btn"
                                    class="w-full bg-luxury-accent text-white py-3.5 px-6 rounded-xl hover:opacity-90 active:scale-[0.99] transition-all duration-200 font-bold shadow-md uppercase tracking-widest text-sm flex items-center justify-center gap-2"
                                    <?= $btnStyle ?>>
                                <?= $btnText ?>
                            </button>
                            <a href="cart.php"
                               class="flex items-center justify-center gap-1.5 text-luxury-textLight hover:text-luxury-accent transition-colors text-sm py-1 font-medium">
                                <i class="fas fa-arrow-left rtl:rotate-180 text-xs"></i>
                                <?= e(t('back_to_cart')) ?>
                            </a>
                        </div>
                    </div>

                </div><!-- end ② payment column -->

                <!-- ③ ADD EXTRAS — Horizontal Slider (order-3 on mobile; bottom-left on desktop) -->
                <?php if (!empty($availableExtras)): ?>
                <div class="lg:col-span-3 order-3">
                    <div class="bg-white border border-luxury-border rounded-2xl shadow-sm p-5 md:p-7">
                        <h2 class="text-lg font-luxury font-bold text-luxury-primary tracking-wide flex items-center gap-2.5 mb-1">
                            <span class="w-8 h-8 rounded-xl bg-luxury-accent/10 flex items-center justify-center">
                                <i class="fas fa-gift text-luxury-accent text-sm"></i>
                            </span>
                            <?= e(t('add_extras')) ?>
                        </h2>
                        <p class="text-xs text-luxury-textLight mb-5 ms-10"><?= e(t('add_extras_hint')) ?></p>

                        <div class="space-y-6">
                            <?php foreach ($availableExtras as $type => $items): ?>
                            <?php if (empty($items)) continue; ?>
                            <div class="extras-category">
                                <!-- Category header + nav arrows -->
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="font-semibold text-luxury-primary text-sm flex items-center gap-2">
                                        <?php
                                        $catIcons = [
                                            'greeting_card' => 'fa-envelope-open-text',
                                            'small_gift'    => 'fa-box-open',
                                            'chocolate_box' => 'fa-box',
                                            'candle'        => 'fa-fire',
                                            'balloons'      => 'fa-wind'
                                        ];
                                        $categoryNames = [
                                            'greeting_card' => t('greeting_cards'),
                                            'small_gift'    => t('small_gifts'),
                                            'chocolate_box' => t('chocolate_boxes'),
                                            'candle'        => t('scented_candles'),
                                            'balloons'      => t('balloons')
                                        ];
                                        ?>
                                        <i class="fas <?= e($catIcons[$type] ?? 'fa-gift') ?> text-luxury-accent text-xs"></i>
                                        <?= e($categoryNames[$type] ?? ucfirst($type)) ?>
                                        <span class="text-xs text-luxury-textLight font-normal">(<?= count($items) ?>)</span>
                                    </h3>
                                    <?php if (count($items) > 2): ?>
                                    <div class="flex gap-1">
                                        <button type="button" class="slider-btn w-7 h-7 rounded-full border border-luxury-border bg-white shadow-sm flex items-center justify-center text-luxury-textLight hover:border-luxury-accent hover:text-luxury-accent"
                                                data-target="slider-<?= e($type) ?>" data-dir="prev" aria-label="Previous">
                                            <i class="fas fa-chevron-left" style="font-size:10px"></i>
                                        </button>
                                        <button type="button" class="slider-btn w-7 h-7 rounded-full border border-luxury-border bg-white shadow-sm flex items-center justify-center text-luxury-textLight hover:border-luxury-accent hover:text-luxury-accent"
                                                data-target="slider-<?= e($type) ?>" data-dir="next" aria-label="Next">
                                            <i class="fas fa-chevron-right" style="font-size:10px"></i>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Horizontal slider track -->
                                <div id="slider-<?= e($type) ?>"
                                     style="display:flex;gap:12px;overflow-x:auto;padding-bottom:8px;scrollbar-width:none;-ms-overflow-style:none;scroll-behavior:smooth;"
                                     class="snap-x snap-mandatory">
                                    <?php foreach ($items as $extra): ?>
                                    <label class="extra-option relative flex-shrink-0 flex flex-col rounded-xl overflow-hidden border-2 border-luxury-border cursor-pointer snap-start bg-white hover:border-luxury-accent hover:shadow-md"
                                           style="width:150px;min-width:150px;transition:border-color .2s,box-shadow .2s"
                                           data-extra-id="<?= e((string)$extra['id']) ?>"
                                           data-extra-price="<?= e((string)$extra['price']) ?>">

                                        <!-- Custom checkbox indicator (top-right) -->
                                        <div class="extra-check-ui absolute top-2 right-2 z-20 flex items-center justify-center pointer-events-none"
                                             style="width:20px;height:20px;border-radius:6px;border:2px solid #d1d5db;background:#fff;">
                                            <i class="fas fa-check check-icon opacity-0 text-white" style="font-size:8px"></i>
                                        </div>
                                        <input type="checkbox" name="extras[]" value="<?= e((string)$extra['id']) ?>" class="sr-only extra-checkbox">

                                        <!-- Image or icon placeholder -->
                                        <?php if (!empty($extra['image_url'])): ?>
                                        <div style="height:110px;overflow:hidden;background:#f9fafb;flex-shrink:0">
                                            <img src="<?= e($extra['image_url']) ?>" alt="<?= e(getExtraName($extra)) ?>"
                                                 style="width:100%;height:100%;object-fit:cover">
                                        </div>
                                        <?php else: ?>
                                        <div style="height:110px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f0ebe4,#e8ddd4)">
                                            <i class="<?= e($extra['icon'] ?? 'fas fa-gift') ?> text-3xl" style="color:#b09080"></i>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Name + price -->
                                        <div style="padding:10px;flex:1;display:flex;flex-direction:column">
                                            <p class="font-semibold text-luxury-primary" style="font-size:11px;line-height:1.4;margin-bottom:4px"><?= e(getExtraName($extra)) ?></p>
                                            <p class="font-bold text-luxury-accent" style="font-size:11px">+ <?= e(formatPrice((float)$extra['price'], $currency)) ?></p>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div><!-- end ③ extras -->
                <?php endif; ?>

            </div><!-- end grid -->
        </form>
    </div>

    <?= modernFooter() ?>
    
    <script>
    const deliveryConfig = <?= json_encode([
        'store'        => getStoreCoordinates(),
        'tiers'        => getDeliveryFeeTiers(),
        'outerFee'     => getOuterZoneDeliveryFee(),
        'currency'     => $currency,
        'isIqd'        => $isIqdCurrency,
        'usdToIqdRate' => $usdToIqdRate
    ], JSON_UNESCAPED_SLASHES) ?>;

    const deliveryMessages = {
        calculating : <?= json_encode(t('delivery_calculating')) ?>,
        outOfRange  : <?= json_encode(t('delivery_out_of_range')) ?>,
        outerZone   : <?= json_encode(t('delivery_outer_zone')) ?>,
        denied      : <?= json_encode(t('delivery_location_denied')) ?>,
        unsupported : <?= json_encode(t('delivery_geolocation_unsupported')) ?>
    };

    // DOM references
    const elFee      = document.getElementById('delivery-fee-amount');
    const elDist     = document.getElementById('delivery-distance');
    const elStatus   = document.getElementById('delivery-status');
    const elStatusBox= document.getElementById('delivery-status-box');
    const elGrandTot = document.getElementById('grand-total-amount');
    const elBaseTot  = document.getElementById('subtotal-amount');
    const elLatInput = document.getElementById('customer_lat');
    const elLngInput = document.getElementById('customer_lng');
    const elDistKm   = document.getElementById('delivery_distance_km');
    const elFeeInput = document.getElementById('delivery_fee');
    const elSubmit   = document.getElementById('place-order-btn');
    const elLocBtn   = document.getElementById('use-location');
    const elLocIcon  = document.getElementById('loc-icon');
    const elForm     = document.querySelector('form');

    function formatMoney(amount) {
        const a = deliveryConfig.isIqd ? (amount * (deliveryConfig.usdToIqdRate || 1300)) : amount;
        const dec = deliveryConfig.isIqd ? 0 : 2;
        return `${deliveryConfig.currency}${deliveryConfig.isIqd ? ' ' : ''}${Number(a).toLocaleString(undefined, { minimumFractionDigits: dec, maximumFractionDigits: dec })}`;
    }

    function haversineKm(lat1, lng1, lat2, lng2) {
        const R = 6371, r = v => v * Math.PI / 180;
        const dLat = r(lat2 - lat1), dLng = r(lng2 - lng1);
        const a = Math.sin(dLat/2)**2 + Math.cos(r(lat1)) * Math.cos(r(lat2)) * Math.sin(dLng/2)**2;
        return 2 * R * Math.asin(Math.sqrt(a));
    }

    function getFeeForDistance(km) {
        for (const t of deliveryConfig.tiers) { if (km <= t.max) return t.fee; }
        return typeof deliveryConfig.outerFee === 'number' ? deliveryConfig.outerFee : null;
    }

    function currentExtrasTotal() {
        return parseFloat(document.getElementById('extras_total').value || 0);
    }

    function setStatus(msg, type) {
        if (!elStatus) return;
        elStatus.textContent = msg;
        if (elStatusBox) elStatusBox.classList.toggle('hidden', !msg);
        const c = { ok:'bg-green-50 border-green-200 text-green-700', err:'bg-red-50 border-red-200 text-red-600', info:'bg-blue-50 border-blue-200 text-blue-600', warn:'bg-yellow-50 border-yellow-200 text-yellow-700' };
        elStatus.className = 'text-xs font-medium px-3 py-2 rounded-lg border ' + (c[type] || c.info);
    }

    // Only spins the icon — never disables the button so user can always retry
    function setLocBusy(busy) {
        if (busy) {
            elLocIcon?.classList.replace('fa-location-crosshairs', 'fa-spinner');
            elLocIcon?.classList.add('fa-spin');
        } else {
            elLocIcon?.classList.replace('fa-spinner', 'fa-location-crosshairs');
            elLocIcon?.classList.remove('fa-spin');
        }
    }

    function lockSubmit() {
        if (elSubmit) { elSubmit.disabled = true; elSubmit.classList.add('opacity-60','cursor-not-allowed'); }
    }

    function unlockSubmit() {
        if (elSubmit) { elSubmit.disabled = false; elSubmit.classList.remove('opacity-60','cursor-not-allowed'); }
    }

    function updateDeliveryUi(distKm, fee) {
        const base = parseFloat(elBaseTot?.dataset.baseTotal || '0');
        if (fee === null) {
            if (elFee) elFee.textContent = deliveryMessages.outOfRange;
            if (elDist) elDist.textContent = `${distKm.toFixed(1)} km`;
            if (elGrandTot) elGrandTot.textContent = '—';
            setStatus(deliveryMessages.outOfRange, 'err');
            lockSubmit();
            return;
        }
        if (elFee) elFee.textContent = formatMoney(fee);
        if (elDist) elDist.textContent = `${distKm.toFixed(1)} km`;
        if (elGrandTot) elGrandTot.textContent = formatMoney(base + fee + currentExtrasTotal());
        const lastMax = deliveryConfig.tiers[deliveryConfig.tiers.length - 1]?.max;
        const isOuter = distKm > lastMax;
        setStatus(isOuter ? deliveryMessages.outerZone : `✓ ${distKm.toFixed(1)} km — ${formatMoney(fee)}`, isOuter ? 'warn' : 'ok');
        unlockSubmit();
    }

    let locWatchdog = null;

    function onLocSuccess(pos) {
        clearTimeout(locWatchdog);
        setLocBusy(false);
        const lat = pos.coords.latitude, lng = pos.coords.longitude;
        const km  = haversineKm(lat, lng, deliveryConfig.store.lat, deliveryConfig.store.lng);
        const fee = getFeeForDistance(km);
        if (elLatInput) elLatInput.value = lat.toFixed(6);
        if (elLngInput) elLngInput.value = lng.toFixed(6);
        if (elDistKm)   elDistKm.value   = km.toFixed(2);
        if (elFeeInput) elFeeInput.value  = fee === null ? '' : fee.toFixed(2);
        updateDeliveryUi(km, fee);
    }

    function onLocError(err) {
        clearTimeout(locWatchdog);
        setLocBusy(false);
        const msg = err.code === 1 ? deliveryMessages.denied : deliveryMessages.unsupported;
        setStatus(msg, 'err');
        // Unlock so user can retry or see the error clearly
        unlockSubmit();
    }

    function requestLocation() {
        if (!navigator.geolocation) {
            setStatus(deliveryMessages.unsupported, 'err');
            unlockSubmit();
            return;
        }
        // On non-HTTPS (and not localhost) browsers silently block geolocation
        const isSecure = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
        if (!isSecure) {
            setLocBusy(false);
            setStatus('Location requires HTTPS. Please enable location access in your browser or switch to HTTPS.', 'err');
            unlockSubmit();
            return;
        }
        setLocBusy(true);
        setStatus(deliveryMessages.calculating, 'info');
        // Watchdog: in case browser blocks silently (shouldn't happen on HTTPS, but safety net)
        clearTimeout(locWatchdog);
        locWatchdog = setTimeout(() => {
            setLocBusy(false);
            setStatus(deliveryMessages.denied, 'err');
            unlockSubmit();
        }, 12000);
        navigator.geolocation.getCurrentPosition(onLocSuccess, onLocError, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        });
    }

    elLocBtn?.addEventListener('click', requestLocation);

    // Lock submit while waiting for location; auto-detect on page load
    lockSubmit();
    setStatus(deliveryMessages.calculating, 'info');
    requestLocation();

    elForm?.addEventListener('submit', e => {
        if (!elLatInput?.value || !elLngInput?.value) {
            e.preventDefault();
            setStatus(deliveryMessages.denied, 'err');
            elLocBtn?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    // Card number formatting
    document.getElementById('card_number')?.addEventListener('input', function(e) {
        const v = e.target.value.replace(/\D/g, '').substr(0, 16);
        e.target.value = v.match(/.{1,4}/g)?.join(' ') || v;
    });

    // Card number validation
    document.getElementById('card_number')?.addEventListener('blur', function() {
        const num = this.value.replace(/\s+/g, '');
        const method = document.querySelector('input[name="payment_method"]:checked')?.value;
        if (!num || !method) return;
        const ok = method === 'visa' ? /^4\d{15}$/.test(num) : method === 'mastercard' ? /^5[1-5]\d{14}$/.test(num) : true;
        if (!ok && num.length >= 16) { this.setCustomValidity('<?= e(t('card_mismatch')) ?>'); this.classList.add('border-red-400'); }
        else { this.setCustomValidity(''); this.classList.remove('border-red-400'); }
    });

    // CVV — digits only
    document.getElementById('cvv')?.addEventListener('input', function(e) { e.target.value = e.target.value.replace(/\D/g, ''); });

    // Expiry validation
    const expiryMonth = document.getElementById('expiry_month');
    const expiryYear  = document.getElementById('expiry_year');
    function validateExpiry() {
        if (!expiryMonth?.value || !expiryYear?.value) return;
        const m = parseInt(expiryMonth.value), y = parseInt(expiryYear.value);
        const now = new Date();
        if (y < now.getFullYear() || (y === now.getFullYear() && m < now.getMonth() + 1)) {
            expiryMonth.setCustomValidity('<?= e(t('card_expired')) ?>');
            expiryYear.setCustomValidity('<?= e(t('card_expired')) ?>');
        } else { expiryMonth.setCustomValidity(''); expiryYear.setCustomValidity(''); }
    }
    expiryMonth?.addEventListener('change', validateExpiry);
    expiryYear?.addEventListener('change', validateExpiry);

    // ===== EXTRAS =====
    const extrasTotalInput   = document.getElementById('extras_total');
    const extrasTotalDisplay = document.getElementById('extras-total-amount');
    const baseTotal          = parseFloat(document.getElementById('subtotal-amount').dataset.baseTotal);

    function updateExtrasTotal() {
        let total = 0;
        document.querySelectorAll('.extra-checkbox').forEach(cb => {
            if (cb.checked) total += parseFloat(cb.closest('.extra-option')?.dataset.extraPrice || 0);
        });
        extrasTotalInput.value = total.toFixed(2);
        extrasTotalDisplay.textContent = formatMoney(total);
        const fee = parseFloat(elFeeInput?.value || 0);
        if (!isNaN(baseTotal) && !isNaN(fee)) {
            if (elGrandTot) elGrandTot.textContent = formatMoney(baseTotal + fee + total);
        }
    }

    document.querySelectorAll('.extra-option').forEach(label => {
        const cb      = label.querySelector('.extra-checkbox');
        const checkUI = label.querySelector('.extra-check-ui');
        const icon    = label.querySelector('.check-icon');
        if (!cb) return;

        cb.addEventListener('change', function () {
            if (this.checked) {
                label.style.borderColor = 'var(--luxury-accent, #b47864)';
                label.style.boxShadow   = '0 4px 12px rgba(0,0,0,0.12)';
                if (checkUI) {
                    checkUI.style.background   = 'var(--luxury-accent, #b47864)';
                    checkUI.style.borderColor  = 'var(--luxury-accent, #b47864)';
                }
                icon?.classList.remove('opacity-0');
                icon?.classList.add('opacity-100');
            } else {
                label.style.borderColor = '';
                label.style.boxShadow   = '';
                if (checkUI) {
                    checkUI.style.background  = '#fff';
                    checkUI.style.borderColor = '#d1d5db';
                }
                icon?.classList.add('opacity-0');
                icon?.classList.remove('opacity-100');
            }
            updateExtrasTotal();
        });
    });

    // ===== SLIDER NAVIGATION =====
    document.querySelectorAll('.slider-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const slider = document.getElementById(this.dataset.target);
            if (!slider) return;
            const slide = slider.querySelector('.extra-option');
            const w = slide ? slide.offsetWidth + 12 : 162;
            slider.scrollBy({ left: this.dataset.dir === 'prev' ? -(w * 2) : (w * 2), behavior: 'smooth' });
        });
    });

    // ===== PAYMENT METHOD TOGGLE =====
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const cardSection = document.getElementById('card-details-section');
            const cardInputs  = cardSection.querySelectorAll('input, select');
            const btn         = document.getElementById('place-order-btn');

            document.querySelectorAll('.payment-method-option').forEach(el => {
                el.classList.remove('border-luxury-accent','bg-luxury-border','shadow-sm','border-[#00A69C]','bg-[#00A69C]/5');
                el.classList.add('border-luxury-border');
            });
            const sel = this.closest('.payment-method-option');

            if (this.value === 'fib') {
                cardSection.classList.add('hidden');
                cardInputs.forEach(i => i.removeAttribute('required'));
                if (btn) { btn.style.backgroundColor = '#00A69C'; btn.innerHTML = '<i class="fas fa-wallet"></i> <?= e(t('pay_with_fib')) ?>'; }
                sel?.classList.add('border-[#00A69C]','bg-[#00A69C]/5','shadow-sm');
            } else {
                cardSection.classList.remove('hidden');
                cardInputs.forEach(i => i.setAttribute('required', ''));
                if (btn) { btn.style.backgroundColor = ''; btn.innerHTML = '<i class="fas fa-check-circle"></i> <?= e(t('place_order')) ?>'; }
                sel?.classList.add('border-luxury-accent','bg-luxury-border','shadow-sm');
            }
            sel?.classList.remove('border-luxury-border');
        });
    });

    // Init payment state on page load
    (() => {
        const method = document.querySelector('input[name="payment_method"]:checked')?.value;
        if (method === 'fib') {
            const cs = document.getElementById('card-details-section');
            const pb = document.getElementById('place-order-btn');
            cs?.classList.add('hidden');
            cs?.querySelectorAll('input, select').forEach(i => i.removeAttribute('required'));
            if (pb) { pb.style.backgroundColor = '#00A69C'; pb.innerHTML = '<i class="fas fa-wallet"></i> <?= e(t('pay_with_fib')) ?>'; }
            document.querySelector('input[value="fib"]')?.closest('.payment-method-option')?.classList.add('border-[#00A69C]','bg-[#00A69C]/5','shadow-sm');
        }
    })();
    </script>
</body>
</html>

