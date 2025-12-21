<?php
declare(strict_types=1);

/**
 * Super Admin - User Management
 * Manage all users in the system
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';
require_once __DIR__ . '/../src/components.php';

requireSuperAdmin();

$pdo = getDB();
$lang = getCurrentLang();
$dir = getHtmlDir();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput('action', 'POST');
    $userId = (int)sanitizeInput('user_id', 'POST', '0');
    $csrfToken = sanitizeInput('csrf_token', 'POST');
    
    if (verifyCSRFToken($csrfToken) && $userId > 0) {
        try {
            switch ($action) {
                case 'delete':
                    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id AND role != "super_admin"');
                    $stmt->execute(['id' => $userId]);
                    logActivity('user_deleted', 'user', $userId, "Deleted user ID: {$userId}");
                    redirect('super_admin_users.php', 'User deleted successfully', 'success');
                    break;
                    
                case 'toggle_status':
                    // You can add a status field to users table if needed
                    redirect('super_admin_users.php', 'Status updated', 'success');
                    break;
            }
        } catch (PDOException $e) {
            error_log('User management error: ' . $e->getMessage());
            redirect('super_admin_users.php', 'Error performing action', 'error');
        }
    }
}

// Get search and filter
$search = sanitizeInput('search', 'GET');
$roleFilter = sanitizeInput('role', 'GET', 'all');
$page = max(1, (int)sanitizeInput('page', 'GET', '1'));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = '(full_name LIKE :search OR email LIKE :search)';
    $params['search'] = "%{$search}%";
}

if ($roleFilter !== 'all') {
    $where[] = 'role = :role';
    $params['role'] = $roleFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users {$whereClause}");
$countStmt->execute($params);
$totalUsers = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

// Get users
$stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(DISTINCT o.id) as total_orders,
           COALESCE(SUM(CASE WHEN o.payment_status = 'paid' THEN o.grand_total ELSE 0 END), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    {$whereClause}
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $key => $value) {
    $stmt->bindValue(":{$key}", $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-gray-50 min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <?php include __DIR__ . '/../src/header.php'; ?>

    <div class="bg-gradient-to-r from-red-600 via-red-700 to-purple-800 text-white py-12">
        <div class="container mx-auto px-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-luxury font-bold mb-2">
                        <i class="fas fa-users mr-4"></i>User Management
                    </h1>
                    <p class="text-red-200">Manage all users in the system</p>
                </div>
                <a href="super_admin_dashboard.php" class="bg-white/20 hover:bg-white/30 px-6 py-3 rounded-xl transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 md:px-6 py-6 md:py-12">
        <?php
        $flash = getFlashMessage();
        if ($flash):
            echo alert($flash['message'], $flash['type']);
        endif;
        ?>

        <!-- Filters -->
        <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl p-6 mb-6">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="<?= e($search) ?>" 
                           placeholder="Search by name or email..." 
                           class="w-full px-4 py-2 border border-luxury-border rounded-xl focus:outline-none focus:ring-2 focus:ring-red-600">
                </div>
                <div>
                    <select name="role" class="px-4 py-2 border border-luxury-border rounded-xl focus:outline-none focus:ring-2 focus:ring-red-600">
                        <option value="all" <?= $roleFilter === 'all' ? 'selected' : '' ?>>All Roles</option>
                        <option value="customer" <?= $roleFilter === 'customer' ? 'selected' : '' ?>>Customers</option>
                        <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admins</option>
                    </select>
                </div>
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-xl transition-all font-semibold">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <a href="super_admin_users.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-xl transition-all font-semibold text-center">
                    <i class="fas fa-redo mr-2"></i>Reset
                </a>
            </form>
        </div>

        <!-- Users Table -->
        <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-6 py-4">
                <h2 class="text-2xl font-bold">
                    <i class="fas fa-table mr-2"></i>Users (<?= e((string)$totalUsers) ?>)
                </h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase">User</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase">Role</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase">Orders</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase">Total Spent</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase">Joined</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-luxury-border">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-luxury-textLight">
                                    <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
                                    <p class="text-xl">No users found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-blue-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-full flex items-center justify-center text-white font-bold mr-4">
                                                <?= e(strtoupper(substr($user['full_name'], 0, 1))) ?>
                                            </div>
                                            <div>
                                                <p class="font-bold text-luxury-primary"><?= e($user['full_name']) ?></p>
                                                <p class="text-sm text-luxury-textLight"><?= e($user['email']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full <?= 
                                            $user['role'] === 'super_admin' ? 'bg-red-100 text-red-800' : 
                                            ($user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800')
                                        ?>">
                                            <i class="fas fa-<?= $user['role'] === 'super_admin' ? 'crown' : ($user['role'] === 'admin' ? 'user-shield' : 'user') ?> mr-1"></i>
                                            <?= e(ucfirst(str_replace('_', ' ', $user['role']))) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-semibold"><?= e((string)$user['total_orders']) ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-green-600"><?= e(formatPrice((float)$user['total_spent'])) ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-luxury-textLight">
                                        <?= e(date('M d, Y', strtotime($user['created_at']))) ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex gap-2">
                                            <?php if ($user['role'] !== 'super_admin'): ?>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?= e((string)$user['id']) ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-all text-sm">
                                                        <i class="fas fa-trash mr-1"></i>Delete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t border-luxury-border flex items-center justify-between">
                    <p class="text-sm text-luxury-textLight">
                        Showing <?= e((string)(($page - 1) * $perPage + 1)) ?> to <?= e((string)min($page * $perPage, $totalUsers)) ?> of <?= e((string)$totalUsers) ?> users
                    </p>
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= e((string)($page - 1)) ?>&search=<?= e($search) ?>&role=<?= e($roleFilter) ?>" 
                               class="px-4 py-2 border border-luxury-border rounded-lg hover:bg-gray-50 transition-all">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= e((string)($page + 1)) ?>&search=<?= e($search) ?>&role=<?= e($roleFilter) ?>" 
                               class="px-4 py-2 border border-luxury-border rounded-lg hover:bg-gray-50 transition-all">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?= modernFooter() ?>
</body>
</html>

