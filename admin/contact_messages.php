<?php
declare(strict_types=1);

/**
 * Admin Contact Messages
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';
require_once __DIR__ . '/../src/components.php';

requireAdmin();

$pdo = getDB();
$lang = getCurrentLang();
$dir = getHtmlDir();

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

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitizeInput('action', 'POST', '');
    $messageId = (int)sanitizeInput('message_id', 'POST', '0');
    
    if ($action === 'mark_read' && $messageId > 0) {
        $stmt = $pdo->prepare('UPDATE contact_messages SET is_read = TRUE WHERE id = :id');
        $stmt->execute(['id' => $messageId]);
    } elseif ($action === 'delete' && $messageId > 0) {
        $stmt = $pdo->prepare('DELETE FROM contact_messages WHERE id = :id');
        $stmt->execute(['id' => $messageId]);
    } elseif ($action === 'reply' && $messageId > 0) {
        $reply = sanitizeInput('admin_reply', 'POST', '');
        if ($reply) {
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
                                            <form method="POST" class="space-y-3">
                                                <input type="hidden" name="action" value="reply">
                                                <input type="hidden" name="message_id" value="<?= e($msg['id']) ?>">
                                                
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Send Reply:</label>
                                                    <textarea 
                                                        name="admin_reply" 
                                                        rows="4" 
                                                        placeholder="Type your reply here..."
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-luxury-primary"
                                                        required
                                                    ></textarea>
                                                </div>
                                                
                                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                                    Send Reply
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Actions -->
                                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                                        <?php if (!$msg['is_read']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="message_id" value="<?= e($msg['id']) ?>">
                                                <button type="submit" class="text-sm px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                                                    Mark as Read
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="message_id" value="<?= e($msg['id']) ?>">
                                            <button type="submit" class="text-sm px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                                                Delete
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
            element.classList.toggle('hidden');
        }
    </script>
</body>
</html>

<?php
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
