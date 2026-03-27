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
$quantity = (int)requestScalar('quantity', '1');
$csrfToken = requestScalar('csrf_token', '');

// Backward compatibility for older links/forms that omit explicit action.
if ($action === '' && $productId > 0) {
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
        $currentQty = (int)($_SESSION['cart'][$productId] ?? 0);
        $_SESSION['cart'][$productId] = min($currentQty + $addQty, $stockQty);

        redirect('cart.php', t('success'), 'success');
    }

    if ($action === 'update') {
        if (!$csrfOk) {
            redirect('cart.php', t('error'), 'error');
        }
        if ($productId <= 0) {
            redirect('cart.php', t('error'), 'error');
        }

        $stmt = $pdo->prepare('SELECT id, stock_qty FROM products WHERE id = :id');
        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch();
        if (!$product) {
            unset($_SESSION['cart'][$productId]);
            redirect('cart.php', t('error'), 'error');
        }

        $stockQty = (int)($product['stock_qty'] ?? 0);
        $newQty = max(1, min($quantity, max(1, $stockQty)));
        $_SESSION['cart'][$productId] = $newQty;
        redirect('cart.php', t('success'), 'success');
    }

    if ($action === 'remove') {
        if (!$csrfOk) {
            redirect('cart.php', t('error'), 'error');
        }
        if ($productId > 0) {
            unset($_SESSION['cart'][$productId]);
        }
        redirect('cart.php', t('success'), 'success');
    }

    if ($action === 'clear') {
        if (!$csrfOk) {
            redirect('cart.php', t('error'), 'error');
        }
        $_SESSION['cart'] = [];
        redirect('cart.php', t('success'), 'success');
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

