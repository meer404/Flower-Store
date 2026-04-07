<?php
declare(strict_types=1);

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/**
 * Read a scalar input from POST/GET (supports stale cached pages).
 */
function requestScalar(string $key, string $default = ''): string {
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    if (is_array($value)) {
        return $default;
    }
    return trim(strip_tags((string)$value));
}

$action = requestScalar('action', '');
$productId = (int)requestScalar('product_id', '0');
$cartKeyParam = requestScalar('cart_key', '');
$quantity = (int)requestScalar('quantity', '1');
$csrfToken = requestScalar('csrf_token', '');
$variants = isset($_POST['variants']) && is_array($_POST['variants']) ? array_map('intval', $_POST['variants']) : [];

// Backward compatibility for older links/forms that omit explicit action.
if ($action === '' && ($productId > 0 || $cartKeyParam !== '')) {
    $action = 'add';
}

$referer = $_SERVER['HTTP_REFERER'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
$sameOrigin = $host !== '' && $referer !== '' && parse_url($referer, PHP_URL_HOST) === $host;
$fallbackRedirect = $sameOrigin ? safeRedirectTarget(parse_url($referer, PHP_URL_PATH) ?: 'shop.php', 'shop.php') : 'shop.php';

if ($quantity <= 0) {
    $quantity = 1;
}

// CSRF is required for destructive actions; allow missing token for "add" (old forms).
$csrfOk = $csrfToken !== '' ? verifyCSRFToken($csrfToken) : false;

$debug = [
    'ts' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'uri' => $_SERVER['REQUEST_URI'] ?? '',
    'referer' => $_SERVER['HTTP_REFERER'] ?? '',
    'action' => $action,
    'product_id' => $productId,
    'quantity' => $quantity,
    'post' => $_POST,
    'get' => $_GET
];
file_put_contents(__DIR__ . '/cart_debug.log', json_encode($debug, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);

try {
    $pdo = getDB();
} catch (Throwable $e) {
    error_log('Cart getDB failed: ' . $e->getMessage());
    redirect($fallbackRedirect, t('error'), 'error');
}

try {
    if ($action === 'add') {
        // If product ID is 0 but we have a cart_key, derive product ID
        if ($productId <= 0 && $cartKeyParam !== '') {
            $parts = explode('_v_', $cartKeyParam);
            $productId = (int)($parts[0] ?? 0);
        }
        
        if ($productId <= 0) {
            redirect($fallbackRedirect, t('error'), 'error');
        }

        $stmt = $pdo->prepare('SELECT id, stock_qty FROM products WHERE id = :id');
        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch();

        if (!$product) {
            redirect($fallbackRedirect, t('error'), 'error');
        }

        $stockQty = (int)($product['stock_qty'] ?? 0);
        if ($stockQty <= 0) {
            redirect($fallbackRedirect, t('out_of_stock'), 'error');
        }

        $addQty = min($quantity, $stockQty);
        
        // Construct cart key
        sort($variants);
        $cartKey = empty($variants) ? (string)$productId : $productId . '_v_' . implode('_', $variants);
        if ($cartKeyParam !== '') {
            $cartKey = $cartKeyParam; // Override if explicitly provided
        }
        
        $currentQty = (int)($_SESSION['cart'][$cartKey] ?? 0);
        $_SESSION['cart'][$cartKey] = min($currentQty + $addQty, $stockQty);

        redirect('cart.php', t('success'), 'success');
    }

    if ($action === 'update') {
        if (!$csrfOk) {
            redirect('cart.php', t('error'), 'error');
        }
        $targetKey = $cartKeyParam !== '' ? $cartKeyParam : (string)$productId;
        
        if ($targetKey === '0' || $targetKey === '') {
            redirect('cart.php', t('error'), 'error');
        }
        
        // Determine base product ID for max stock check
        $baseIdParts = explode('_v_', $targetKey);
        $baseId = (int)$baseIdParts[0];

        $stmt = $pdo->prepare('SELECT id, stock_qty FROM products WHERE id = :id');
        $stmt->execute(['id' => $baseId]);
        $product = $stmt->fetch();
        if (!$product) {
            unset($_SESSION['cart'][$targetKey]);
            redirect('cart.php', t('error'), 'error');
        }

        $stockQty = (int)($product['stock_qty'] ?? 0);
        $newQty = max(1, min($quantity, max(1, $stockQty)));
        $_SESSION['cart'][$targetKey] = $newQty;
        redirect('cart.php', t('success'), 'success');
    }

    if ($action === 'remove') {
        if (!$csrfOk) {
            redirect('cart.php', t('error'), 'error');
        }
        $targetKey = $cartKeyParam !== '' ? $cartKeyParam : (string)$productId;
        if ($targetKey !== '' && $targetKey !== '0') {
            unset($_SESSION['cart'][$targetKey]);
        }
        redirect('cart.php', t('success'), 'success');
    }

    if ($action === 'clear') {
        if (!$csrfOk) {
            redirect('cart.php', t('error'), 'error');
        }
        $_SESSION['cart'] = [];
        clearCoupon();
        redirect('cart.php', t('success'), 'success');
    }

    if ($action === 'apply_coupon') {
        if (!$csrfOk) {
            redirect('cart.php', t('error'), 'error');
        }
        $code = requestScalar('coupon_code', '');
        if (empty($code)) {
            redirect('cart.php', t('error'), 'error');
        }
        
        // Calculate cart total to validate min purchase
        $cartTotal = getCartTotal();
        
        if (applyCoupon($code, $cartTotal)) {
            redirect('cart.php', t('coupon_applied'), 'success');
        } else {
            redirect('cart.php', t('invalid_coupon'), 'error');
        }
    }

    if ($action === 'remove_coupon') {
        if (!$csrfOk) {
            redirect('cart.php', t('error'), 'error');
        }
        clearCoupon();
        redirect('cart.php', t('coupon_removed'), 'success');
    }
} catch (PDOException $e) {
    error_log('Cart action error: ' . $e->getMessage());
    redirect($fallbackRedirect, t('error'), 'error');
} catch (Throwable $e) {
    error_log('Cart action throwable: ' . $e->getMessage());
    redirect($fallbackRedirect, t('error'), 'error');
}

redirect($fallbackRedirect, t('error'), 'error');
?>

