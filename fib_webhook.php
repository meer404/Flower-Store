<?php
declare(strict_types=1);

/**
 * FIB Webhook Handler
 * Receives payment status updates from First Iraqi Bank
 */

require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/email.php';

// Log the incoming request for debugging
$input = file_get_contents('php://input');
$headers = getallheaders();
error_log("FIB Webhook received: " . $input);

$data = json_decode($input, true);

if ($data && isset($data['paymentId']) && isset($data['status'])) {
    $paymentId = $data['paymentId'];
    $status = $data['status'];

    $pdo = getDB();
    
    // Find the order associated with this FIB payment ID
    $stmt = $pdo->prepare('
        SELECT o.id, o.user_id, o.grand_total, o.payment_status, u.email, u.full_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.fib_payment_id = :fib_id
    ');
    $stmt->execute(['fib_id' => $paymentId]);
    $order = $stmt->fetch();

    if ($order) {
        if ($status === 'PAID' && $order['payment_status'] !== 'paid') {
            try {
                $pdo->beginTransaction();
                
                // Update order payment status
                $stmt = $pdo->prepare('UPDATE orders SET payment_status = "paid" WHERE id = :id');
                $stmt->execute(['id' => $order['id']]);
                
                // Create notification for user
                createNotification(
                    (int)$order['user_id'],
                    'payment_status',
                    t('payment_confirmed'),
                    t('order_status_msg', [
                        'order_id' => $order['id'],
                        'old_status' => t('pending'),
                        'new_status' => t('paid')
                    ]),
                    (int)$order['id']
                );
                
                $pdo->commit();

                // Send confirmation email
                $currency = (string)getSystemSetting('currency', 'IQD ');
                $customerSubject = t('order_confirmation_subject', ['order_id' => $order['id']]);
                $customerBody = "<h1>" . t('thank_you_for_order') . "</h1>";
                $customerBody .= "<p>" . t('payment_confirmed') . "</p>";
                $customerBody .= "<p><strong>" . t('order_id') . ":</strong> {$order['id']}</p>";
                $customerBody .= "<p><strong>" . t('grand_total') . ":</strong> " . formatPrice((float)$order['grand_total'], $currency) . "</p>";
                sendEmail($order['email'], $customerSubject, $customerBody);

            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Webhook processing error: " . $e->getMessage());
            }
        } elseif ($status === 'DECLINED') {
            // Optional: Handle declined status
            error_log("Payment declined for order #{$order['id']}");
        }
    } else {
        error_log("No order found for FIB payment ID: " . $paymentId);
    }
}

// Always respond with 200 OK to acknowledge receipt
http_response_code(200);
echo json_encode(['status' => 'success']);
