<?php
declare(strict_types=1);

/**
 * Wishlist Actions Handler
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';

$action = sanitizeInput('action', 'POST', '');
$productId = (int)sanitizeInput('product_id', 'POST', '0');
$csrfToken = sanitizeInput('csrf_token', 'POST', '');
$expectsJson = $action === 'toggle';

$csrfValid = verifyCSRFToken($csrfToken);
if (!$csrfValid || $productId <= 0) {
    if (!$csrfValid) {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $sameOrigin = $host !== '' && $referer !== '' && parse_url($referer, PHP_URL_HOST) === $host;
        if (!($sameOrigin && $productId > 0)) {
            if ($expectsJson) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => t('error')]);
                exit;
            }
            redirect('shop.php', t('error'), 'error');
        }
    } else {
        if ($expectsJson) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => t('error')]);
            exit;
        }
        redirect('shop.php', t('error'), 'error');
    }
}

if (!isLoggedIn()) {
    if ($expectsJson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'redirect' => 'login.php']);
        exit;
    }
    redirect('login.php', t('error'), 'error');
}

$pdo = getDB();
$userId = (int)$_SESSION['user_id'];

try {
    if ($action === 'toggle') {
        $stmt = $pdo->prepare('SELECT id FROM wishlist WHERE user_id = :user_id AND product_id = :product_id');
        $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare('DELETE FROM wishlist WHERE user_id = :user_id AND product_id = :product_id');
            $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
            $inWishlist = false;
        } else {
            $stmt = $pdo->prepare('INSERT INTO wishlist (user_id, product_id) VALUES (:user_id, :product_id)');
            $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
            $inWishlist = true;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'inWishlist' => $inWishlist]);
        exit;
    } elseif ($action === 'add') {
        // Check if already in wishlist
        $stmt = $pdo->prepare('SELECT id FROM wishlist WHERE user_id = :user_id AND product_id = :product_id');
        $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare('INSERT INTO wishlist (user_id, product_id) VALUES (:user_id, :product_id)');
            $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
        }
        redirect('wishlist.php', e('Added to wishlist'), 'success');
    } elseif ($action === 'remove') {
        $stmt = $pdo->prepare('DELETE FROM wishlist WHERE user_id = :user_id AND product_id = :product_id');
        $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
        redirect('wishlist.php', e('Removed from wishlist'), 'success');
    }
} catch (PDOException $e) {
    error_log('Wishlist error: ' . $e->getMessage());
    if ($expectsJson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => t('error')]);
        exit;
    }
    redirect('shop.php', t('error'), 'error');
}

redirect('shop.php', 'Error: action=' . e($action) . ' product_id=' . sanitizeInput('product_id', 'POST', ''), 'error');

