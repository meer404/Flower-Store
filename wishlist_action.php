<?php
declare(strict_types=1);

/**
 * Wishlist Actions Handler
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';

requireLogin();

$action = sanitizeInput('action', 'POST', '');
$productId = (int)sanitizeInput('product_id', 'POST', '0');
$csrfToken = sanitizeInput('csrf_token', 'POST', '');

if (!verifyCSRFToken($csrfToken) || $productId <= 0) {
    redirect('shop.php', t('error'), 'error');
}

$pdo = getDB();
$userId = (int)$_SESSION['user_id'];

try {
    if ($action === 'add') {
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
    redirect('shop.php', t('error'), 'error');
}

redirect('shop.php', t('error'), 'error');

