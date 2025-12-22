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

requireLogin();

$pdo = getDB();
$error = '';

// Get cart items
$cartItems = [];
$cartTotal = 0.0;

if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    $stmt = $pdo->prepare("SELECT p.*, c.name_en as category_name_en, c.name_ku as category_name_ku 
                           FROM products p 
                           JOIN categories c ON p.category_id = c.id 
                           WHERE p.id IN ({$placeholders})");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();
    
    foreach ($products as $product) {
        $productId = (int)$product['id'];
        $quantity = (int)($_SESSION['cart'][$productId] ?? 0);
        
        if ($quantity > 0) {
            $product['cart_quantity'] = $quantity;
            $product['subtotal'] = (float)$product['price'] * $quantity;
            $cartItems[] = $product;
            $cartTotal += $product['subtotal'];
        }
    }
}

// Redirect if cart is empty
if (empty($cartItems)) {
    redirect('cart.php', e('Your cart is empty'), 'error');
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
    $paymentMethod = sanitizeInput('payment_method', 'POST');
    $cardNumber = sanitizeInput('card_number', 'POST');
    $cardholderName = sanitizeInput('cardholder_name', 'POST');
    $expiryMonth = sanitizeInput('expiry_month', 'POST');
    $expiryYear = sanitizeInput('expiry_year', 'POST');
    $cvv = sanitizeInput('cvv', 'POST');
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = t('order_error');
    } elseif (empty($shippingAddress)) {
        $error = t('order_error') . ' - ' . e('Shipping address is required');
    } elseif (empty($deliveryDate)) {
        $error = t('delivery_date_required');
    } elseif (!strtotime($deliveryDate) || strtotime($deliveryDate) < strtotime('tomorrow')) {
        $error = t('delivery_date_invalid');
    } elseif (empty($paymentMethod) || !in_array($paymentMethod, ['visa', 'mastercard'], true)) {
        $error = t('payment_method_required');
    } elseif (empty($cardNumber)) {
        $error = t('card_number_required');
    } elseif (empty($cardholderName)) {
        $error = t('cardholder_name_required');
    } elseif (empty($expiryMonth) || empty($expiryYear)) {
        $error = t('expiry_date_required');
    } elseif (empty($cvv)) {
        $error = t('cvv_required');
    } else {
        // Validate card number (basic validation)
        $cardNumberClean = preg_replace('/\s+/', '', $cardNumber);
        if (!preg_match('/^\d{13,19}$/', $cardNumberClean)) {
            $error = t('card_number_invalid');
        } elseif ($paymentMethod === 'visa' && !preg_match('/^4\d{12,15}$/', $cardNumberClean)) {
            $error = t('card_number_invalid') . ' - ' . e('Visa cards must start with 4');
        } elseif ($paymentMethod === 'mastercard' && !preg_match('/^5[1-5]\d{14}$/', $cardNumberClean)) {
            $error = t('card_number_invalid') . ' - ' . e('Mastercard must start with 51-55');
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
                // Extract last 4 digits for storage
                $cardLastFour = substr($cardNumberClean, -4);
                
                try {
                    $pdo->beginTransaction();
                    
                    // Verify stock availability before creating order
                    $stockOk = true;
                    foreach ($cartItems as $item) {
                        $stmt = $pdo->prepare('SELECT stock_qty FROM products WHERE id = :id FOR UPDATE');
                        $stmt->execute(['id' => $item['id']]);
                        $product = $stmt->fetch();
                        
                        if (!$product || (int)$product['stock_qty'] < $item['cart_quantity']) {
                            $stockOk = false;
                            break;
                        }
                    }
                    
                    if (!$stockOk) {
                        $pdo->rollBack();
                        $error = t('order_error') . ' - ' . e('Insufficient stock for one or more products');
                    } else {
                        // Create order with payment details
                        $stmt = $pdo->prepare('
                            INSERT INTO orders (user_id, grand_total, payment_status, shipping_address, delivery_date, payment_method, card_last_four, cardholder_name, card_expiry_month, card_expiry_year)
                            VALUES (:user_id, :grand_total, :payment_status, :shipping_address, :delivery_date, :payment_method, :card_last_four, :cardholder_name, :card_expiry_month, :card_expiry_year)
                        ');
                        $stmt->execute([
                            'user_id' => $userId,
                            'grand_total' => $cartTotal,
                            'payment_status' => 'paid', // Mark as paid when card details provided
                            'shipping_address' => $shippingAddress,
                            'delivery_date' => $deliveryDate,
                            'payment_method' => $paymentMethod,
                            'card_last_four' => $cardLastFour,
                            'cardholder_name' => $cardholderName,
                            'card_expiry_month' => $expiryMonthInt,
                            'card_expiry_year' => $expiryYearInt
                        ]);
                        
                        $orderId = (int)$pdo->lastInsertId();
                        
                        // Create order items and update stock
                        foreach ($cartItems as $item) {
                            // Insert order item
                            $stmt = $pdo->prepare('
                                INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                                VALUES (:order_id, :product_id, :quantity, :unit_price)
                            ');
                            $stmt->execute([
                                'order_id' => $orderId,
                                'product_id' => $item['id'],
                                'quantity' => $item['cart_quantity'],
                                'unit_price' => $item['price']
                            ]);
                            
                            // Update product stock
                            $stmt = $pdo->prepare('UPDATE products SET stock_qty = stock_qty - :quantity WHERE id = :id');
                            $stmt->execute([
                                'quantity' => $item['cart_quantity'],
                                'id' => $item['id']
                            ]);
                        }
                        
                        $pdo->commit();
                        
                        // Clear cart
                        $_SESSION['cart'] = [];
                        
                        redirect('index.php', t('order_placed'), 'success');
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
    <title><?= e(t('checkout')) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/src/header.php'; ?>

    <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
        <h1 class="text-3xl md:text-4xl font-luxury font-bold text-luxury-primary mb-6 md:mb-8 tracking-wide"><?= e(t('checkout')) ?></h1>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-sm mb-6">
                <?= e($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8">
            <!-- Order Summary -->
            <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8 order-2 lg:order-1">
                <h2 class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e('Order Summary') ?></h2>
                
                <div class="space-y-4 md:space-y-6 mb-6">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="flex justify-between items-start border-b border-luxury-border pb-4">
                            <div class="flex-1">
                                <p class="font-medium text-luxury-primary mb-1"><?= e(getProductName($item)) ?></p>
                                <p class="text-sm text-luxury-textLight"><?= e((string)$item['cart_quantity']) ?> x <?= e(formatPrice((float)$item['price'])) ?></p>
                            </div>
                            <p class="font-semibold text-luxury-accent ml-4"><?= e(formatPrice($item['subtotal'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="border-t border-luxury-border pt-4">
                    <div class="flex justify-between items-center">
                        <span class="text-lg md:text-xl font-bold text-luxury-primary"><?= e(t('total')) ?>:</span>
                        <span class="text-xl md:text-2xl font-bold text-luxury-accent font-luxury"><?= e(formatPrice($cartTotal)) ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Checkout Form -->
            <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-8 order-1 lg:order-2">
                <h2 class="text-xl md:text-2xl font-luxury font-bold text-luxury-primary mb-6 tracking-wide"><?= e('Customer Information') ?></h2>
                
                <div class="mb-6 p-4 bg-luxury-border rounded-sm">
                    <p class="text-sm text-luxury-textLight mb-2"><?= e('Name') ?>: <span class="font-medium text-luxury-primary"><?= e($user['full_name']) ?></span></p>
                    <p class="text-sm text-luxury-textLight"><?= e('Email') ?>: <span class="font-medium text-luxury-primary"><?= e($user['email']) ?></span></p>
                </div>
                
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    
                    <div>
                        <label for="shipping_address" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e(t('shipping_address')) ?> *
                        </label>
                        <textarea id="shipping_address" name="shipping_address" rows="4" required
                                  class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent"
                                  placeholder="<?= e('Enter your complete shipping address') ?>"><?= e(sanitizeInput('shipping_address', 'POST', '')) ?></textarea>
                    </div>
                    
                    <div>
                        <label for="delivery_date" class="block text-sm font-medium text-luxury-text mb-2">
                            <?= e(t('delivery_date')) ?> *
                        </label>
                        <input type="date" id="delivery_date" name="delivery_date" required
                               min="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>"
                               value="<?= e(sanitizeInput('delivery_date', 'POST', '')) ?>"
                               class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                        <p class="text-xs text-luxury-textLight mt-1">
                            <?= e('Please select a date for flower delivery. Minimum delivery time is 1 day.') ?>
                        </p>
                    </div>
                    
                    <!-- Payment Method Section -->
                    <div class="border-t border-luxury-border pt-6 mt-6">
                        <h3 class="text-lg md:text-xl font-luxury font-bold text-luxury-primary mb-4 tracking-wide"><?= e(t('payment_method')) ?></h3>
                        
                        <div class="mb-4">
                            <label for="payment_method" class="block text-sm font-medium text-luxury-text mb-2">
                                <?= e(t('select_payment_method')) ?> *
                            </label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="relative flex items-center p-4 border-2 border-luxury-border rounded-sm cursor-pointer hover:border-luxury-accent transition-colors payment-method-option <?= (sanitizeInput('payment_method', 'POST', '') === 'visa') ? 'border-luxury-accent bg-luxury-border' : '' ?>">
                                    <input type="radio" name="payment_method" value="visa" required class="sr-only" <?= (sanitizeInput('payment_method', 'POST', '') === 'visa') ? 'checked' : '' ?>>
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-8 bg-blue-600 rounded flex items-center justify-center">
                                            <span class="text-white font-bold text-xs">VISA</span>
                                        </div>
                                        <span class="font-medium text-luxury-primary"><?= e(t('visa')) ?></span>
                                    </div>
                                </label>
                                <label class="relative flex items-center p-4 border-2 border-luxury-border rounded-sm cursor-pointer hover:border-luxury-accent transition-colors payment-method-option <?= (sanitizeInput('payment_method', 'POST', '') === 'mastercard') ? 'border-luxury-accent bg-luxury-border' : '' ?>">
                                    <input type="radio" name="payment_method" value="mastercard" required class="sr-only" <?= (sanitizeInput('payment_method', 'POST', '') === 'mastercard') ? 'checked' : '' ?>>
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-8 bg-red-600 rounded flex items-center justify-center">
                                            <span class="text-white font-bold text-xs">MC</span>
                                        </div>
                                        <span class="font-medium text-luxury-primary"><?= e(t('mastercard')) ?></span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="card_number" class="block text-sm font-medium text-luxury-text mb-2">
                                    <?= e(t('card_number')) ?> *
                                </label>
                                <input type="text" id="card_number" name="card_number" required
                                       maxlength="19" pattern="[\d\s]+"
                                       value="<?= e(sanitizeInput('card_number', 'POST', '')) ?>"
                                       placeholder="1234 5678 9012 3456"
                                       class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                            </div>
                            
                            <div>
                                <label for="cardholder_name" class="block text-sm font-medium text-luxury-text mb-2">
                                    <?= e(t('cardholder_name')) ?> *
                                </label>
                                <input type="text" id="cardholder_name" name="cardholder_name" required
                                       value="<?= e(sanitizeInput('cardholder_name', 'POST', '')) ?>"
                                       placeholder="<?= e('Name on card') ?>"
                                       class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-luxury-text mb-2">
                                        <?= e(t('expiry_date')) ?> *
                                    </label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <select name="expiry_month" id="expiry_month" required
                                                class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                                            <option value=""><?= e(t('expiry_month')) ?></option>
                                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                                <option value="<?= e(sprintf('%02d', $i)) ?>" <?= (sanitizeInput('expiry_month', 'POST', '') === sprintf('%02d', $i)) ? 'selected' : '' ?>>
                                                    <?= e(sprintf('%02d', $i)) ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                        <select name="expiry_year" id="expiry_year" required
                                                class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                                            <option value=""><?= e(t('expiry_year')) ?></option>
                                            <?php 
                                            $currentYear = (int)date('Y');
                                            for ($i = $currentYear; $i <= $currentYear + 10; $i++): 
                                            ?>
                                                <option value="<?= e((string)$i) ?>" <?= (sanitizeInput('expiry_year', 'POST', '') === (string)$i) ? 'selected' : '' ?>>
                                                    <?= e((string)$i) ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="cvv" class="block text-sm font-medium text-luxury-text mb-2">
                                        <?= e(t('cvv')) ?> *
                                    </label>
                                    <input type="text" id="cvv" name="cvv" required
                                           maxlength="4" pattern="\d{3,4}"
                                           value="<?= e(sanitizeInput('cvv', 'POST', '')) ?>"
                                           placeholder="123"
                                           class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                                    <p class="text-xs text-luxury-textLight mt-1"><?= e(t('cvv_hint')) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-luxury-accent text-white py-3 px-4 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                        <?= e(t('place_order')) ?>
                    </button>
                </form>
                
                <a href="cart.php" 
                   class="block mt-6 text-center text-luxury-accent hover:text-luxury-primary transition-colors font-medium">
                    <?= e('← Back to Cart') ?>
                </a>
            </div>
        </div>
    </div>

    <?= modernFooter() ?>
    
    <script>
    // Card number formatting
    document.getElementById('card_number')?.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s+/g, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        if (formattedValue.length <= 19) {
            e.target.value = formattedValue;
        }
    });
    
    // Payment method selection visual feedback
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.payment-method-option').forEach(option => {
                option.classList.remove('border-luxury-accent', 'bg-luxury-border');
            });
            if (this.checked) {
                this.closest('.payment-method-option').classList.add('border-luxury-accent', 'bg-luxury-border');
            }
        });
    });
    
    // Card number validation based on payment method
    document.getElementById('card_number')?.addEventListener('blur', function() {
        const cardNumber = this.value.replace(/\s+/g, '');
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
        
        if (cardNumber && paymentMethod) {
            let isValid = false;
            if (paymentMethod === 'visa') {
                isValid = /^4\d{12,15}$/.test(cardNumber);
            } else if (paymentMethod === 'mastercard') {
                isValid = /^5[1-5]\d{14}$/.test(cardNumber);
            }
            
            if (!isValid && cardNumber.length >= 13) {
                this.setCustomValidity('<?= e("Card number does not match selected payment method") ?>');
                this.classList.add('border-red-500');
            } else {
                this.setCustomValidity('');
                this.classList.remove('border-red-500');
            }
        }
    });
    
    // CVV validation
    document.getElementById('cvv')?.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '');
    });
    
    // Expiry date validation
    const expiryMonth = document.getElementById('expiry_month');
    const expiryYear = document.getElementById('expiry_year');
    
    function validateExpiry() {
        if (expiryMonth?.value && expiryYear?.value) {
            const month = parseInt(expiryMonth.value);
            const year = parseInt(expiryYear.value);
            const currentYear = new Date().getFullYear();
            const currentMonth = new Date().getMonth() + 1;
            
            if (year < currentYear || (year === currentYear && month < currentMonth)) {
                expiryMonth.setCustomValidity('<?= e("Card has expired") ?>');
                expiryYear.setCustomValidity('<?= e("Card has expired") ?>');
            } else {
                expiryMonth.setCustomValidity('');
                expiryYear.setCustomValidity('');
            }
        }
    }
    
    expiryMonth?.addEventListener('change', validateExpiry);
    expiryYear?.addEventListener('change', validateExpiry);
    </script>
</body>
</html>

