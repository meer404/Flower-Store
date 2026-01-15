<?php
declare(strict_types=1);

/**
 * Order Action Handler
 * Bloom & Vine Flower Store
 * Handles order status updates by admin
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';

requireAdmin();

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/dashboard.php', e('Invalid request'), 'error');
}

$action = sanitizeInput('action', 'POST');
$orderId = (int)sanitizeInput('order_id', 'POST', '0');
$csrfToken = sanitizeInput('csrf_token', 'POST');

if (!verifyCSRFToken($csrfToken)) {
    redirect('admin/dashboard.php', e('Invalid security token'), 'error');
}

if ($orderId <= 0) {
    redirect('admin/dashboard.php', e('Invalid order ID'), 'error');
}

try {
    if ($action === 'update_status') {
        $newStatus = sanitizeInput('order_status', 'POST');
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if (!in_array($newStatus, $validStatuses, true)) {
            redirect('order_details.php?id=' . $orderId, e('Invalid order status'), 'error');
        }
        
        // Check if order exists and get user_id
        $stmt = $pdo->prepare('SELECT id, user_id, order_status FROM orders WHERE id = :id');
        $stmt->execute(['id' => $orderId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            redirect('admin/dashboard.php', e('Order not found'), 'error');
        }
        
        $oldStatus = $order['order_status'] ?? 'pending';
        
        // Update order status
        $stmt = $pdo->prepare('UPDATE orders SET order_status = :status WHERE id = :id');
        $stmt->execute(['status' => $newStatus, 'id' => $orderId]);
        
        // Create notification for user if status changed
        if ($oldStatus !== $newStatus) {
            $statusLabels = [
                'pending' => t('pending'),
                'processing' => t('processing'),
                'shipped' => t('shipped'),
                'delivered' => t('delivered'),
                'cancelled' => t('cancelled')
            ];
            
            $title = t('order_status_updated_title');
            $message = t('order_status_msg', [
                'order_id' => $orderId,
                'old_status' => $statusLabels[$oldStatus] ?? ucfirst($oldStatus),
                'new_status' => $statusLabels[$newStatus] ?? ucfirst($newStatus)
            ]);
            
            createNotification(
                (int)$order['user_id'],
                'order_status',
                $title,
                $message,
                $orderId
            );
        }
        
        redirect('order_details.php?id=' . $orderId, e('Order status updated successfully'), 'success');
        
    } elseif ($action === 'update_tracking') {
        $trackingNumber = sanitizeInput('tracking_number', 'POST');
        
        // Check if order exists and get user_id
        $stmt = $pdo->prepare('SELECT id, user_id, tracking_number FROM orders WHERE id = :id');
        $stmt->execute(['id' => $orderId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            redirect('admin/dashboard.php', e('Order not found'), 'error');
        }
        
        // Update tracking number
        $stmt = $pdo->prepare('UPDATE orders SET tracking_number = :tracking WHERE id = :id');
        $stmt->execute(['tracking' => $trackingNumber, 'id' => $orderId]);
        
        // Create notification if tracking number was added/updated
        if (!empty($trackingNumber)) {
            $title = t('tracking_added_title');
            $message = t('tracking_added_msg', [
                'order_id' => $orderId,
                'tracking_number' => $trackingNumber
            ]);
            
            createNotification(
                (int)$order['user_id'],
                'order_tracking',
                $title,
                $message,
                $orderId
            );
        }
        
        redirect('order_details.php?id=' . $orderId, e('Tracking number updated successfully'), 'success');
        
    } elseif ($action === 'update_payment_status') {
        $paymentStatus = sanitizeInput('payment_status', 'POST');
        $validStatuses = ['pending', 'paid'];
        
        if (!in_array($paymentStatus, $validStatuses, true)) {
            redirect('order_details.php?id=' . $orderId, e('Invalid payment status'), 'error');
        }
        
        // Check if order exists
        $stmt = $pdo->prepare('SELECT id FROM orders WHERE id = :id');
        $stmt->execute(['id' => $orderId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            redirect('admin/dashboard.php', e('Order not found'), 'error');
        }
        
        // Update payment status
        $stmt = $pdo->prepare('UPDATE orders SET payment_status = :status WHERE id = :id');
        $stmt->execute(['status' => $paymentStatus, 'id' => $orderId]);
        
        redirect('order_details.php?id=' . $orderId, e('Payment status updated successfully'), 'success');
        
    } else {
        redirect('admin/dashboard.php', e('Invalid action'), 'error');
    }
} catch (PDOException $e) {
    redirect('admin/dashboard.php', e('An error occurred while updating the order'), 'error');
}

