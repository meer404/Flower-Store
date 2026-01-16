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
                    redirect('super_admin_users.php', t('user_deleted_success'), 'success');
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
    <title><?= e(t('user_management')) ?> - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-gray-50 min-h-screen" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-x-hidden">
            <!-- Admin Header -->
            <?php include __DIR__ . '/header.php'; ?>

            <!-- Main Content -->
            <main class="flex-1 p-4 md:p-8">
                <!-- Page Header -->
                <div class="bg-gradient-to-r from-red-600 via-red-700 to-purple-800 text-white rounded-3xl p-8 mb-8 shadow-xl relative overflow-hidden">
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <h1 class="text-3xl md:text-4xl font-luxury font-bold mb-2">
                                <i class="fas fa-users me-3"></i><?= e(t('user_management')) ?>
                            </h1>
                            <p class="text-red-200"><?= e(t('user_management_desc')) ?></p>
                        </div>
                    </div>
                    <!-- Decorative Circles -->
                    <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-48 h-48 rounded-full bg-black/10 blur-2xl"></div>
                </div>

                <?php
                $flash = getFlashMessage();
                if ($flash):
                    echo alert($flash['message'], $flash['type']);
                endif;
                ?>

                <!-- Filters & Controls -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 border border-gray-100">
                    <form method="GET" class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Search Users</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" name="search" value="<?= e($search) ?>" 
                                       placeholder="<?= e(t('search_users_placeholder')) ?>" 
                                       class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                            </div>
                        </div>
                        <div class="md:w-64">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Filter by Role</label>
                            <select name="role" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white cursor-pointer appearance-none">
                                <option value="all" <?= $roleFilter === 'all' ? 'selected' : '' ?>><?= e(t('all_roles')) ?></option>
                                <option value="customer" <?= $roleFilter === 'customer' ? 'selected' : '' ?>><?= e(t('role_customer')) ?></option>
                                <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>><?= e(t('role_admin')) ?></option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl transition-all font-bold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                <?= e(t('search')) ?>
                            </button>
                            <a href="super_admin_users.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-xl transition-all font-bold">
                                <?= e(t('reset')) ?>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Users Table -->
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-list text-red-500"></i>
                            <?= e(t('users')) ?> <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-sm"><?= e((string)$totalUsers) ?></span>
                        </h2>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('user')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('role')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('order')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('total_spent')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('joined')) ?></th>
                                    <th class="px-6 py-4 text-start text-xs font-bold text-gray-500 uppercase tracking-wider"><?= e(t('actions')) ?></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-users-slash text-5xl mb-4 text-gray-300"></i>
                                                <p class="text-lg font-medium"><?= e(t('no_users_found')) ?></p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr class="hover:bg-red-50/10 transition-colors group">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold me-3 shadow-sm">
                                                        <?= e(strtoupper(substr($user['full_name'], 0, 1))) ?>
                                                    </div>
                                                    <div>
                                                        <p class="font-bold text-gray-800 group-hover:text-red-600 transition-colors"><?= e($user['full_name']) ?></p>
                                                        <p class="text-sm text-gray-500"><?= e($user['email']) ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-full <?= 
                                                    $user['role'] === 'super_admin' ? 'bg-purple-100 text-purple-700 border border-purple-200' : 
                                                    ($user['role'] === 'admin' ? 'bg-blue-100 text-blue-700 border border-blue-200' : 'bg-gray-100 text-gray-700 border border-gray-200')
                                                ?>">
                                                    <i class="fas fa-<?= $user['role'] === 'super_admin' ? 'crown' : ($user['role'] === 'admin' ? 'shield-alt' : 'user') ?> text-[10px]"></i>
                                                    <?= e(ucfirst(str_replace('_', ' ', $user['role']))) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">
                                                        <?= e((string)$user['total_orders']) ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="font-bold text-green-600"><?= e(formatPrice((float)$user['total_spent'])) ?></span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                <?= e(date('M d, Y', strtotime($user['created_at']))) ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex gap-2 opacity-60 group-hover:opacity-100 transition-opacity">
                                                    <?php if ($user['role'] !== 'super_admin'): ?>
                                                        <form method="POST" onsubmit="return confirm('<?= e(t('delete_user_confirm')) ?>');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="user_id" value="<?= e((string)$user['id']) ?>">
                                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                            <button type="submit" class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors" title="<?= e(t('delete')) ?>">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-gray-300 text-xs italic">Protected</span>
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
                        <div class="px-6 py-4 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-gray-50/30">
                            <div class="text-xs text-gray-500 font-medium">
                                <?= e(t('showing_results', [
                                    'start' => ($page - 1) * $perPage + 1,
                                    'end' => min($page * $perPage, $totalUsers),
                                    'total' => $totalUsers
                                ])) ?>
                            </div>
                            <div class="flex gap-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= e((string)($page - 1)) ?>&search=<?= e($search) ?>&role=<?= e($roleFilter) ?>" 
                                       class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-red-600 transition-all font-semibold text-sm shadow-sm">
                                        <i class="fas fa-chevron-left rtl:rotate-180 me-1"></i><?= e(t('previous')) ?>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?= e((string)($page + 1)) ?>&search=<?= e($search) ?>&role=<?= e($roleFilter) ?>" 
                                       class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 hover:text-red-600 transition-all font-semibold text-sm shadow-sm">
                                        <?= e(t('next')) ?><i class="fas fa-chevron-right rtl:rotate-180 ms-1"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
                        
            <!-- Footer -->
            <?php include __DIR__ . '/footer.php'; ?>
        </div>
    </div>
</body>
</html>
