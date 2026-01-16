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
                                <i class="fas fa-user-shield me-3"></i><?= e(t('admin_management')) ?>
                            </h1>
                            <p class="text-red-200"><?= e(t('admin_management_desc')) ?></p>
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

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Create Admin Form -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24 border border-gray-100">
                            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                                <i class="fas fa-plus-circle text-green-600"></i><?= e(t('create_new_admin')) ?>
                            </h2>
                            <form method="POST" class="space-y-5">
                                <input type="hidden" name="action" value="create_admin">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('full_name')) ?></label>
                                    <input type="text" name="full_name" required 
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white placeholder-gray-400">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('email')) ?></label>
                                    <input type="email" name="email" required 
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white placeholder-gray-400">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('password')) ?></label>
                                    <input type="password" name="password" required minlength="6"
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white placeholder-gray-400">
                                </div>
                                
                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-xl transition-all font-bold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                    <i class="fas fa-user-plus me-2"></i><?= e(t('create_admin_btn')) ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Admins List -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-list text-purple-500"></i>
                                    <?= e(t('administrators')) ?> <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full text-sm"><?= e((string)count($admins)) ?></span>
                                </h2>
                            </div>
                            
                            <div class="divide-y divide-gray-100">
                                <?php if (empty($admins)): ?>
                                    <div class="p-8 text-center text-gray-400">
                                        <i class="fas fa-user-shield text-5xl mb-4 text-gray-300"></i>
                                        <p class="text-lg font-medium"><?= e(t('no_admins_found')) ?></p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($admins as $admin): ?>
                                        <div class="p-6 hover:bg-gray-50 transition-colors group">
                                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                                <div class="flex items-center gap-4">
                                                    <div class="w-12 h-12 bg-gradient-to-br <?= $admin['role'] === 'super_admin' ? 'from-red-500 to-red-600' : 'from-purple-500 to-indigo-500' ?> rounded-full flex items-center justify-center text-white font-bold shadow-sm">
                                                        <?= e(strtoupper(substr($admin['full_name'], 0, 1))) ?>
                                                    </div>
                                                    <div>
                                                        <div class="flex items-center gap-2 mb-1">
                                                            <h3 class="font-bold text-gray-800 text-lg"><?= e($admin['full_name']) ?></h3>
                                                            <?php if ($admin['role'] === 'super_admin'): ?>
                                                                <span class="bg-red-100 text-red-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Super Admin</span>
                                                            <?php else: ?>
                                                                <span class="bg-purple-100 text-purple-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Admin</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <p class="text-sm text-gray-500 flex items-center gap-2">
                                                            <i class="fas fa-envelope text-xs opacity-50"></i>
                                                            <?= e($admin['email']) ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex items-center gap-3">
                                                    <div class="text-xs text-gray-400 font-medium">
                                                        Created <?= e(date('M d, Y', strtotime($admin['created_at']))) ?>
                                                    </div>
                                                    
                                                    <?php if ($admin['role'] === 'admin'): ?>
                                                        <form method="POST" onsubmit="return confirm('<?= e(t('delete_admin_confirm')) ?>');">
                                                            <input type="hidden" name="action" value="delete_admin">
                                                            <input type="hidden" name="admin_id" value="<?= e((string)$admin['id']) ?>">
                                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                            <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="<?= e(t('delete')) ?>">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="p-2 text-gray-300" title="<?= e(t('protected')) ?>">
                                                            <i class="fas fa-lock"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
                        
            <!-- Footer -->
            <?php include __DIR__ . '/footer.php'; ?>
        </div>
    </div>
</body>
</html>
