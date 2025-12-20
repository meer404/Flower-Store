<?php
declare(strict_types=1);

/**
 * Cart Actions Handler (Add, Remove, Update)
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';

requireLogin();

// Initialize cart if not exists
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = sanitizeInput('action', 'POST', '');
$csrfToken = sanitizeInput('csrf_token', 'POST', '');

// Verify CSRF token
if (!verifyCSRFToken($csrfToken)) {
    redirect('shop.php', t('error'), 'error');
}

$pdo = getDB();

switch ($action) {
    case 'add':
        $productId = (int)sanitizeInput('product_id', 'POST', '0');
        $quantity = (int)sanitizeInput('quantity', 'POST', '1');
        
        if ($productId > 0 && $quantity > 0) {
            // Verify product exists and has stock
            $stmt = $pdo->prepare('SELECT id, stock_qty FROM products WHERE id = :id AND stock_qty > 0');
            $stmt->execute(['id' => $productId]);
            $product = $stmt->fetch();
            
            if ($product) {
                $currentQty = $_SESSION['cart'][$productId] ?? 0;
                $newQty = $currentQty + $quantity;
                
                // Check stock availability
                if ($newQty <= (int)$product['stock_qty']) {
                    $_SESSION['cart'][$productId] = $newQty;
                    redirect('cart.php', t('add_to_cart') . ' - ' . t('success'), 'success');
                } else {
                    redirect('cart.php', t('error') . ' - ' . e('Insufficient stock'), 'error');
                }
            }
        }
        break;
        
    case 'update':
        $productId = (int)sanitizeInput('product_id', 'POST', '0');
        $quantity = (int)sanitizeInput('quantity', 'POST', '0');
        
        if ($productId > 0) {
            if ($quantity > 0) {
                // Verify stock
                $stmt = $pdo->prepare('SELECT stock_qty FROM products WHERE id = :id');
                $stmt->execute(['id' => $productId]);
                $product = $stmt->fetch();
                
                if ($product && $quantity <= (int)$product['stock_qty']) {
                    $_SESSION['cart'][$productId] = $quantity;
                }
            } else {
                // Remove if quantity is 0
                unset($_SESSION['cart'][$productId]);
            }
        }
        redirect('cart.php', '', '');
        break;
        
    case 'remove':
        $productId = (int)sanitizeInput('product_id', 'POST', '0');
        
        if ($productId > 0 && isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
        }
        redirect('cart.php', t('remove') . ' - ' . t('success'), 'success');
        break;
        
    case 'clear':
        $_SESSION['cart'] = [];
        redirect('cart.php', e('Cart cleared'), 'info');
        break;
}

redirect('shop.php', t('error'), 'error');

