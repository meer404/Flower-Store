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
                    
                    $adminId = (int)$pdo->lastInsertId();
                    
                    // Get selected permissions
                    $permissions = [];
                    foreach ($_POST as $key => $value) {
                        if (strpos($key, 'permission_') === 0) {
                            $permission = str_replace('permission_', '', $key);
                            $permissions[] = $permission;
                        }
                    }
                    
                    // By default, assign view_dashboard permission if no permissions selected
                    if (empty($permissions)) {
                        $permissions = ['view_dashboard'];
                    } else {
                        // Ensure view_dashboard is always included
                        if (!in_array('view_dashboard', $permissions)) {
                            $permissions[] = 'view_dashboard';
                        }
                    }
                    
                    // Assign permissions
                    setAdminPermissions($adminId, $permissions);
                    
                    logActivity('admin_created', 'user', $adminId, "Created admin: {$email} with permissions: " . implode(', ', $permissions));
                    redirect('super_admin_admins.php', t('admin_created_success'), 'success');
                } else {
                    redirect('super_admin_admins.php', t('fill_all_fields_error'), 'error');
                }
            } elseif ($action === 'update_admin_permissions') {
                $adminId = (int)sanitizeInput('admin_id', 'POST', '0');
                
                if ($adminId > 0) {
                    // Get selected permissions
                    $permissions = [];
                    foreach ($_POST as $key => $value) {
                        if (strpos($key, 'permission_') === 0) {
                            $permission = str_replace('permission_', '', $key);
                            $permissions[] = $permission;
                        }
                    }
                    
                    // Update permissions
                    setAdminPermissions($adminId, $permissions);
                    
                    logActivity('admin_permissions_updated', 'user', $adminId, "Updated admin permissions: " . implode(', ', $permissions));
                    redirect('super_admin_admins.php', t('admin_permissions_updated_success'), 'success');
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
                        <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24 border border-gray-100 max-h-screen overflow-y-auto">
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
                                
                                <!-- Permissions Section -->
                                <div class="border-t pt-4 mt-4">
                                    <label class="block text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                                        <i class="fas fa-lock text-purple-600"></i><?= e(t('permissions_label')) ?>
                                    </label>
                                    
                                    <div class="space-y-2">
                                        <?php foreach (getAvailablePermissions() as $permKey => $permLabel): ?>
                                            <div class="flex items-center">
                                                <input type="checkbox" name="permission_<?= e($permKey) ?>" value="1" id="perm_new_<?= e($permKey) ?>"
                                                       class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-2 focus:ring-green-500">
                                                <label for="perm_new_<?= e($permKey) ?>" class="ml-2 text-sm text-gray-700 cursor-pointer">
                                                    <?= e(t('permission_' . $permKey)) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
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
                                    <?php foreach ($admins as $admin): 
                                        $adminPerms = $admin['role'] === 'admin' ? getAdminPermissions($admin['id']) : [];
                                        $availablePerms = getAvailablePermissions();
                                    ?>
                                        <div class="p-6 hover:bg-gray-50 transition-colors group border-b border-gray-100 last:border-b-0">
                                            <!-- Admin Header -->
                                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                                                <div class="flex items-center gap-4 flex-1">
                                                    <div class="w-12 h-12 bg-gradient-to-br <?= $admin['role'] === 'super_admin' ? 'from-red-500 to-red-600' : 'from-purple-500 to-indigo-500' ?> rounded-full flex items-center justify-center text-white font-bold shadow-sm">
                                                        <?= e(strtoupper(substr($admin['full_name'], 0, 1))) ?>
                                                    </div>
                                                    <div>
                                                        <div class="flex items-center gap-2 mb-1">
                                                            <h3 class="font-bold text-gray-800 text-lg"><?= e($admin['full_name']) ?></h3>
                                                            <?php if ($admin['role'] === 'super_admin'): ?>
                                                                <span class="bg-red-100 text-red-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider"><?= e(t('super_admin')) ?></span>
                                                            <?php else: ?>
                                                                <span class="bg-purple-100 text-purple-700 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider"><?= e(t('role_admin')) ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <p class="text-sm text-gray-500 flex items-center gap-2">
                                                            <i class="fas fa-envelope text-xs opacity-50"></i>
                                                            <?= e($admin['email']) ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex items-center gap-2">
                                                    <?php if ($admin['role'] === 'admin'): ?>
                                                        <button type="button" onclick="togglePermissions(<?= e((string)$admin['id']) ?>)" class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition-colors text-sm font-medium">
                                                            <i class="fas fa-lock me-1"></i><?= e(t('edit_permissions')) ?>
                                                        </button>
                                                        <form method="POST" onsubmit="return confirm('<?= e(t('delete_admin_confirm')) ?>');" class="inline">
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
                                            
                                            <!-- Permissions Display -->
                                            <?php if ($admin['role'] === 'admin'): ?>
                                                <div class="mt-4 pt-4 border-t border-gray-200">
                                                    <div class="mb-3">
                                                        <p class="text-sm font-semibold text-gray-600 mb-2">
                                                            <i class="fas fa-shield-alt me-1"></i><?= e(t('permissions_label')) ?> 
                                                            <span class="bg-gray-100 text-gray-700 text-xs font-bold px-2 py-0.5 rounded"><?= e((string)count($adminPerms)) ?>/<?= e((string)count($availablePerms)) ?></span>
                                                        </p>
                                                    </div>
                                                    
                                                    <!-- Permissions List (Hidden by default) -->
                                                    <div id="perms-<?= e((string)$admin['id']) ?>" class="hidden bg-gray-50 rounded-lg p-4">
                                                        <form method="POST" class="space-y-3">
                                                            <input type="hidden" name="action" value="update_admin_permissions">
                                                            <input type="hidden" name="admin_id" value="<?= e((string)$admin['id']) ?>">
                                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                            
                                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                                <?php foreach ($availablePerms as $permKey => $permLabel): ?>
                                                                    <div class="flex items-center">
                                                                        <input type="checkbox" 
                                                                               name="permission_<?= e($permKey) ?>" 
                                                                               value="1" 
                                                                               id="perm_<?= e((string)$admin['id']) ?>_<?= e($permKey) ?>"
                                                                               <?= in_array($permKey, $adminPerms) ? 'checked' : '' ?>
                                                                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                                                        <label for="perm_<?= e((string)$admin['id']) ?>_<?= e($permKey) ?>" class="ml-2 text-sm text-gray-700 cursor-pointer">
                                                                            <?= e(t('permission_' . $permKey)) ?>
                                                                        </label>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                            
                                                            <div class="flex gap-2 pt-2">
                                                                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-3 rounded-lg transition-colors text-sm font-medium">
                                                                    <i class="fas fa-save me-1"></i><?= e(t('save_permissions')) ?>
                                                                </button>
                                                                <button type="button" onclick="togglePermissions(<?= e((string)$admin['id']) ?>)" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-3 rounded-lg transition-colors text-sm font-medium">
                                                                    <i class="fas fa-times me-1"></i><?= e(t('cancel')) ?>
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    
                                                    <!-- Permissions Tags (Shown by default) -->
                                                    <div id="perms-tags-<?= e((string)$admin['id']) ?>" class="flex flex-wrap gap-2">
                                                        <?php if (empty($adminPerms)): ?>
                                                            <span class="text-xs text-gray-400 italic"><?= e(t('no_permissions_assigned')) ?></span>
                                                        <?php else: ?>
                                                            <?php foreach ($adminPerms as $perm): ?>
                                                                <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 text-xs font-bold px-2 py-1 rounded-full">
                                                                    <i class="fas fa-check text-[10px]"></i>
                                                                    <?= e(t('permission_' . $perm)) ?>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    function togglePermissions(adminId) {
                        const permDiv = document.getElementById('perms-' + adminId);
                        const tagsDiv = document.getElementById('perms-tags-' + adminId);
                        
                        if (permDiv.classList.contains('hidden')) {
                            permDiv.classList.remove('hidden');
                            tagsDiv.classList.add('hidden');
                        } else {
                            permDiv.classList.add('hidden');
                            tagsDiv.classList.remove('hidden');
                        }
                    }
                </script>
            </main>
                        
            <!-- Footer -->
            <?php include __DIR__ . '/footer.php'; ?>
        </div>
    </div>
</body>
</html>
