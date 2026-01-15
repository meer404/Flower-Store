<?php
declare(strict_types=1);

/**
 * Super Admin - Admin Management
 * Manage admin users
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
    $csrfToken = sanitizeInput('csrf_token', 'POST');
    
    if (verifyCSRFToken($csrfToken)) {
        try {
            if ($action === 'create_admin') {
                $fullName = sanitizeInput('full_name', 'POST');
                $email = sanitizeInput('email', 'POST');
                $password = sanitizeInput('password', 'POST');
                
                if ($fullName && $email && $password) {
                    $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
                    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (:full_name, :email, :password_hash, "admin")');
                    $stmt->execute([
                        'full_name' => $fullName,
                        'email' => $email,
                        'password_hash' => $passwordHash
                    ]);
                    logActivity('admin_created', 'user', (int)$pdo->lastInsertId(), "Created admin: {$email}");
                    redirect('super_admin_admins.php', t('admin_created_success'), 'success');
                } else {
                    redirect('super_admin_admins.php', t('fill_all_fields_error'), 'error');
                }
            } elseif ($action === 'delete_admin') {
                $adminId = (int)sanitizeInput('admin_id', 'POST', '0');
                if ($adminId > 0) {
                    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id AND role = "admin"');
                    $stmt->execute(['id' => $adminId]);
                    logActivity('admin_deleted', 'user', $adminId, "Deleted admin ID: {$adminId}");
                    redirect('super_admin_admins.php', t('admin_deleted_success'), 'success');
                }
            }
        } catch (PDOException $e) {
            error_log('Admin management error: ' . $e->getMessage());
            redirect('super_admin_admins.php', t('error') . ': ' . ($e->getCode() === '23000' ? t('email_exists_error') : t('database_error')), 'error');
        }
    }
}

// Get all admins and super admins
$stmt = $pdo->query('SELECT u.*, COUNT(DISTINCT o.id) as total_orders FROM users u LEFT JOIN orders o ON u.id = o.user_id WHERE u.role IN ("admin", "super_admin") GROUP BY u.id ORDER BY u.role DESC, u.created_at DESC');
$admins = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('admin_management')) ?> - Super Admin</title>
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
                        <i class="fas fa-user-shield me-4"></i><?= e(t('admin_management')) ?>
                    </h1>
                    <p class="text-red-200"><?= e(t('admin_management_desc')) ?></p>
                </div>
                <a href="super_admin_dashboard.php" class="bg-white/20 hover:bg-white/30 px-6 py-3 rounded-xl transition-all">
                    <i class="fas fa-arrow-left me-2 rtl:rotate-180"></i><?= e(t('back_to_dashboard')) ?>
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

        <!-- Create Admin Form -->
        <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl p-6 mb-6">
            <h2 class="text-2xl font-bold text-luxury-primary mb-6">
                <i class="fas fa-plus-circle me-2 text-green-600"></i><?= e(t('create_new_admin')) ?>
            </h2>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="hidden" name="action" value="create_admin">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                
                <div>
                    <label class="block text-sm font-medium text-luxury-text mb-2"><?= e(t('full_name')) ?></label>
                    <input type="text" name="full_name" required 
                           class="w-full px-4 py-2 border border-luxury-border rounded-xl focus:outline-none focus:ring-2 focus:ring-red-600">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-luxury-text mb-2"><?= e(t('email')) ?></label>
                    <input type="email" name="email" required 
                           class="w-full px-4 py-2 border border-luxury-border rounded-xl focus:outline-none focus:ring-2 focus:ring-red-600">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-luxury-text mb-2"><?= e(t('password')) ?></label>
                    <input type="password" name="password" required minlength="6"
                           class="w-full px-4 py-2 border border-luxury-border rounded-xl focus:outline-none focus:ring-2 focus:ring-red-600">
                </div>
                
                <div class="md:col-span-3">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl transition-all font-semibold">
                        <i class="fas fa-user-plus me-2"></i><?= e(t('create_admin_btn')) ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Admins List -->
        <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-4">
                <h2 class="text-2xl font-bold">
                    <i class="fas fa-table me-2"></i><?= e(t('administrators')) ?> (<?= e((string)count($admins)) ?>)
                </h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-purple-50">
                        <tr>
                            <th class="px-6 py-4 text-start text-xs font-bold text-purple-900 uppercase"><?= e(t('role_admin')) ?></th>
                            <th class="px-6 py-4 text-start text-xs font-bold text-purple-900 uppercase"><?= e(t('role')) ?></th>
                            <th class="px-6 py-4 text-start text-xs font-bold text-purple-900 uppercase"><?= e(t('order')) ?></th>
                            <th class="px-6 py-4 text-start text-xs font-bold text-purple-900 uppercase"><?= e(t('created_at')) ?></th>
                            <th class="px-6 py-4 text-start text-xs font-bold text-purple-900 uppercase"><?= e(t('actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-luxury-border">
                        <?php if (empty($admins)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-luxury-textLight">
                                    <i class="fas fa-user-shield text-6xl text-gray-300 mb-4"></i>
                                    <p class="text-xl"><?= e(t('no_admins_found')) ?></p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($admins as $admin): ?>
                                <tr class="hover:bg-purple-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-12 h-12 bg-gradient-to-br <?= $admin['role'] === 'super_admin' ? 'from-red-500 to-red-600' : 'from-purple-500 to-indigo-500' ?> rounded-full flex items-center justify-center text-white font-bold me-4">
                                                <?= e(strtoupper(substr($admin['full_name'], 0, 1))) ?>
                                            </div>
                                            <div>
                                                <p class="font-bold text-luxury-primary"><?= e($admin['full_name']) ?></p>
                                                <p class="text-sm text-luxury-textLight"><?= e($admin['email']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex px-3 py-1 text-xs font-bold rounded-full <?= 
                                            $admin['role'] === 'super_admin' ? 'bg-red-100 text-red-800' : 'bg-purple-100 text-purple-800'
                                        ?>">
                                            <i class="fas fa-<?= $admin['role'] === 'super_admin' ? 'crown' : 'user-shield' ?> me-1"></i>
                                            <?= e(ucfirst(str_replace('_', ' ', $admin['role']))) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-semibold"><?= e((string)$admin['total_orders']) ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-luxury-textLight">
                                        <?= e(date('M d, Y', strtotime($admin['created_at']))) ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($admin['role'] === 'admin'): ?>
                                            <form method="POST" onsubmit="return confirm('<?= e(t('delete_admin_confirm')) ?>');" class="inline">
                                                <input type="hidden" name="action" value="delete_admin">
                                                <input type="hidden" name="admin_id" value="<?= e((string)$admin['id']) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-all text-sm">
                                                    <i class="fas fa-trash me-1"></i><?= e(t('delete')) ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-luxury-textLight text-sm"><?= e(t('protected')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?= modernFooter() ?>
</body>
</html>

