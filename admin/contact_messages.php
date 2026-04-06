<?php
declare(strict_types=1);

/**
 * Admin Contact Messages
 * Bloom & Vine Flower Store
 */

// Load composer dependencies (PHPMailer)
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';
require_once __DIR__ . '/../src/components.php';

requireAdmin();

$pdo = getDB();
$lang = getCurrentLang();
$dir = getHtmlDir();
$successMessage = '';
$errorMessage = '';

// Ensure contact_messages table exists
try {
    $pdo->query("SELECT 1 FROM contact_messages LIMIT 1");
} catch (PDOException $e) {
    // Table doesn't exist, create it
    $sql = file_get_contents(__DIR__ . '/../database/add_contact_messages.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitizeInput('action', 'POST', '');
    $messageId = (int)sanitizeInput('message_id', 'POST', '0');
    
    if ($messageId > 0) {
        if ($action === 'mark_read') {
            try {
                $stmt = $pdo->prepare('UPDATE contact_messages SET is_read = TRUE WHERE id = :id');
                $stmt->execute(['id' => $messageId]);
                $successMessage = 'Message marked as read.';
            } catch (PDOException $e) {
                $errorMessage = 'Error marking message as read.';
            }
        } elseif ($action === 'delete') {
            try {
                $stmt = $pdo->prepare('DELETE FROM contact_messages WHERE id = :id');
                $stmt->execute(['id' => $messageId]);
                $successMessage = 'Message deleted successfully.';
            } catch (PDOException $e) {
                $errorMessage = 'Error deleting message.';
            }
        } elseif ($action === 'reply') {
            $reply = sanitizeInput('admin_reply', 'POST', '');
            if (empty($reply)) {
                $errorMessage = 'Reply cannot be empty.';
            } else {
                try {
                    // Get message details for email
                    $stmt = $pdo->prepare('SELECT full_name, email, subject FROM contact_messages WHERE id = :id');
                    $stmt->execute(['id' => $messageId]);
                    $msgDetails = $stmt->fetch();
                    
                    if (!$msgDetails) {
                        $errorMessage = 'Message not found.';
                    } else {
                        // Update message with reply
                        $stmt = $pdo->prepare('
                            UPDATE contact_messages 
                            SET is_replied = TRUE, admin_reply = :reply, replied_by = :replied_by, replied_at = NOW()
                            WHERE id = :id
                        ');
                        $stmt->execute([
                            'reply' => $reply,
                            'replied_by' => $_SESSION['user_id'],
                            'id' => $messageId
                        ]);
                        
                        // Try to send email to customer
                        $emailSent = sendReplyEmail($msgDetails['email'], $msgDetails['full_name'], $msgDetails['subject'], $reply);
                        
                        if ($emailSent) {
                            $successMessage = '✓ Reply saved and email sent to ' . e($msgDetails['email']) . '.';
                        } else {
                            $successMessage = '⚠ Reply saved but email could not be sent. Check Gmail configuration.';
                        }
                    }
                } catch (PDOException $e) {
                    $errorMessage = 'Error saving reply: ' . $e->getMessage();
                }
            }
        }
    }
}

// Get filter parameter
$filter = sanitizeInput('filter', 'GET', 'all');
$allowedFilters = ['all', 'unread', 'replied'];
if (!in_array($filter, $allowedFilters)) {
    $filter = 'all';
}

// Build query based on filter
$query = 'SELECT * FROM contact_messages WHERE 1=1';
if ($filter === 'unread') {
    $query .= ' AND is_read = FALSE';
} elseif ($filter === 'replied') {
    $query .= ' AND is_replied = TRUE';
}
$query .= ' ORDER BY created_at DESC';

$stmt = $pdo->query($query);
$messages = $stmt->fetchAll();

// Get counts for filter tabs
$countAll = $pdo->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn();
$countUnread = $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = FALSE')->fetchColumn();
$countReplied = $pdo->query('SELECT COUNT(*) FROM contact_messages WHERE is_replied = TRUE')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('contact_messages', [], 'Contact Messages')) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-gray-50 min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/header.php'; ?>
    
    <div class="flex flex-1">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <main class="flex-1">
            <div class="p-6">
                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Contact Messages</h1>
                    <p class="text-gray-600 mt-2">Manage customer contact form submissions</p>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($successMessage): ?>
                    <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800">
                        <div class="flex items-center">
                            <span class="font-bold text-lg mr-3">✓</span>
                            <span><?= e($successMessage) ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($errorMessage): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800">
                        <div class="flex items-center">
                            <span class="font-bold text-lg mr-3">✕</span>
                            <span><?= e($errorMessage) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Filter Tabs -->
                <div class="mb-6 flex gap-4 border-b border-gray-200">
                    <a href="?filter=all" class="px-4 py-2 font-medium text-sm <?= $filter === 'all' ? 'text-luxury-primary border-b-2 border-luxury-primary' : 'text-gray-600 hover:text-gray-900' ?>">
                        All Messages (<?= $countAll ?>)
                    </a>
                    <a href="?filter=unread" class="px-4 py-2 font-medium text-sm <?= $filter === 'unread' ? 'text-luxury-primary border-b-2 border-luxury-primary' : 'text-gray-600 hover:text-gray-900' ?>">
                        Unread (<?= $countUnread ?>)
                    </a>
                    <a href="?filter=replied" class="px-4 py-2 font-medium text-sm <?= $filter === 'replied' ? 'text-luxury-primary border-b-2 border-luxury-primary' : 'text-gray-600 hover:text-gray-900' ?>">
                        Replied (<?= $countReplied ?>)
                    </a>
                </div>

                <!-- Messages List -->
                <?php if (empty($messages)): ?>
                    <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                        <p class="text-gray-500 text-lg">No messages found</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($messages as $msg): ?>
                            <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h3 class="text-lg font-bold text-gray-900"><?= e($msg['full_name']) ?></h3>
                                            <?php if (!$msg['is_read']): ?>
                                                <span class="inline-block bg-luxury-accent text-white text-xs font-semibold px-2 py-1 rounded-full">New</span>
                                            <?php endif; ?>
                                            <?php if ($msg['is_replied']): ?>
                                                <span class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded-full">Replied</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-1">
                                            <strong>Email:</strong> <a href="mailto:<?= e($msg['email']) ?>" class="text-luxury-primary hover:underline"><?= e($msg['email']) ?></a>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <strong>Received:</strong> <?= e(date('M d, Y @ H:i', strtotime($msg['created_at']))) ?>
                                        </p>
                                    </div>
                                    <button onclick="toggleMessage(<?= (int)$msg['id'] ?>)" class="ml-4 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                        View Details
                                    </button>
                                </div>

                                <!-- Subject -->
                                <div class="mb-4">
                                    <p class="text-sm font-semibold text-gray-700 mb-1">Subject:</p>
                                    <p class="text-gray-900"><?= e($msg['subject']) ?></p>
                                </div>

                                <!-- Message Details (toggled) -->
                                <div id="message-<?= (int)$msg['id'] ?>" class="hidden space-y-4">
                                    <!-- Message Body -->
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                        <p class="text-sm font-semibold text-gray-700 mb-2">Message:</p>
                                        <p class="text-gray-800 whitespace-pre-wrap break-words"><?= e($msg['message']) ?></p>
                                    </div>

                                    <!-- Admin Reply (if exists) -->
                                    <?php if ($msg['is_replied'] && $msg['admin_reply']): ?>
                                        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                                            <p class="text-sm font-semibold text-green-900 mb-2">Admin Reply:</p>
                                            <p class="text-green-800 whitespace-pre-wrap break-words"><?= e($msg['admin_reply']) ?></p>
                                            <p class="text-xs text-green-700 mt-2">
                                                Replied by <?= e(getUserNameById($pdo, $msg['replied_by'])) ?> on <?= e(date('M d, Y @ H:i', strtotime($msg['replied_at']))) ?>
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <!-- Reply Form -->
                                        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                                            <h4 class="text-sm font-bold text-blue-900 mb-3">✉ Send Reply to Customer</h4>
                                            <form method="POST" class="space-y-3">
                                                <input type="hidden" name="action" value="reply">
                                                <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                                                
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Your Reply:</label>
                                                    <textarea 
                                                        name="admin_reply" 
                                                        rows="5" 
                                                        placeholder="Type your response here. This will be sent to the customer via email."
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-luxury-primary resize-vertical"
                                                        required
                                                    ></textarea>
                                                    <p class="text-xs text-gray-600 mt-1">💡 Tip: Be professional and helpful. The customer will receive this reply via email.</p>
                                                </div>
                                                
                                                <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-md hover:shadow-lg">
                                                    📧 Send Reply & Notify Customer
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Actions -->
                                    <div class="flex gap-3 pt-4 border-t border-gray-200 flex-wrap">
                                        <?php if (!$msg['is_read']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                                                <button type="submit" class="text-sm px-4 py-2 bg-amber-100 text-amber-700 font-medium rounded-lg hover:bg-amber-200 transition-colors flex items-center gap-2">
                                                    <span>👁</span> Mark as Read
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this message? This cannot be undone.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                                            <button type="submit" class="text-sm px-4 py-2 bg-red-100 text-red-700 font-medium rounded-lg hover:bg-red-200 transition-colors flex items-center gap-2">
                                                <span>🗑</span> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        function toggleMessage(id) {
            const element = document.getElementById('message-' + id);
            if (element) {
                element.classList.toggle('hidden');
                
                // Smooth scroll to details if opening
                if (!element.classList.contains('hidden')) {
                    setTimeout(() => {
                        element.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }, 100);
                }
            }
        }
        
        // Auto-hide success message after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successMsg = document.querySelector('[style*="bg-green-50"]');
            if (successMsg && successMsg.textContent.includes('successfully')) {
                setTimeout(() => {
                    successMsg.style.transition = 'opacity 0.3s ease-out';
                    successMsg.style.opacity = '0';
                    setTimeout(() => successMsg.remove(), 300);
                }, 5000);
            }
        });
    </script>
</body>
</html>

<?php
/**
 * Send reply email to customer via Gmail
 */
function sendReplyEmail(string $email, string $customerName, string $originalSubject, string $reply): bool {
    try {
        // Check if PHPMailer is available
        if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            error_log('PHPMailer not installed. Run: composer install');
            return false;
        }
        
        require_once __DIR__ . '/../src/gmail_config.php';
        
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = GMAIL_SMTP_HOST;
        $mail->Port       = GMAIL_SMTP_PORT;
        $mail->SMTPSecure = GMAIL_SMTP_SECURE;
        $mail->SMTPAuth   = true;
        
        // Gmail credentials
        $mail->Username = GMAIL_EMAIL;
        $mail->Password = GMAIL_PASSWORD;
        
        // Email details
        $mail->setFrom(GMAIL_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($email, $customerName);
        $mail->addReplyTo(GMAIL_EMAIL, MAIL_FROM_NAME);
        
        // Email subject and content
        $mail->Subject = "Re: {$originalSubject}";
        $mail->isHTML(true);
        
        $siteName = getSystemSetting('site_name', 'Bloom & Vine');
        $siteEmail = getSystemSetting('site_email', GMAIL_EMAIL);
        
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 0; }
        .header { background-color: #6B2D5C; color: white; padding: 20px; }
        .header h2 { margin: 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .message-box { background-color: white; padding: 15px; margin: 15px 0; border-left: 4px solid #6B2D5C; }
        .footer { font-size: 12px; color: #666; margin-top: 20px; text-align: center; border-top: 1px solid #ddd; padding-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Thank You for Contacting Us</h2>
        </div>
        <div class="content">
            <p>Dear <strong>{$customerName}</strong>,</p>
            
            <p>Thank you for reaching out to <strong>{$siteName}</strong>. We appreciate your message and have prepared a response below.</p>
            
            <div class="message-box">
                <strong>Original Subject:</strong> {$originalSubject}<br><br>
                <strong>Our Reply:</strong><br>
                <pre style="white-space: pre-wrap; word-wrap: break-word; font-family: Arial, sans-serif;">{$reply}</pre>
            </div>
            
            <p>If you have any further questions, please feel free to reply to this email or contact us directly.</p>
            
            <p>Best regards,<br>
            <strong>{$siteName} Team</strong><br>
            <em>{$siteEmail}</em></p>
            
            <div class="footer">
                <p>This is an automated message. Please do not reply with system issues to this email. Use our contact form instead.</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;

        // Alternative text version
        $mail->AltBody = "Dear {$customerName},\n\nThank you for contacting {$siteName}.\n\nOriginal Subject: {$originalSubject}\n\nOur Reply:\n{$reply}\n\nBest regards,\n{$siteName} Team";
        
        // Send email
        $mail->send();
        error_log("Email sent successfully to: {$email}");
        return true;
        
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}


/**
 * Helper function to get user name by ID
 */
function getUserNameById($pdo, $userId) {
    try {
        $stmt = $pdo->prepare('SELECT full_name FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $result = $stmt->fetch();
        return $result ? $result['full_name'] : 'Unknown User';
    } catch (PDOException $e) {
        return 'Unknown User';
    }
}
?>
