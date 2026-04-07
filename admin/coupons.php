<?php
declare(strict_types=1);

/**
 * Coupons Management Page (Admin)
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';
require_once __DIR__ . '/../src/components.php';

requireAdmin();
requirePermission('manage_coupons');

$pdo = getDB();
$error = '';
$success = '';

// Handle add coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = sanitizeInput('csrf_token', 'POST');
    $action = sanitizeInput('action', 'POST');
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = t('error');
    } else {
        if ($action === 'add') {
            $code = strtoupper(sanitizeInput('code', 'POST'));
            $discount_type = sanitizeInput('discount_type', 'POST');
            $discount_value = (float)sanitizeInput('discount_value', 'POST');
            $min_purchase = (float)sanitizeInput('min_purchase', 'POST', '0');
            $expiry_date = sanitizeInput('expiry_date', 'POST') ?: null;
            if ($expiry_date) {
                // Formatting to keep it valid for MySQL
                $expiry_date = date('Y-m-d H:i:s', strtotime($expiry_date));
            }
            $usage_limit = sanitizeInput('usage_limit', 'POST');
            $usage_limit = is_numeric($usage_limit) && $usage_limit > 0 ? (int)$usage_limit : null;
            $is_active = (int)isset($_POST['is_active']);

            if (empty($code) || empty($discount_type) || $discount_value <= 0) {
                $error = t('error') . ': Invalid inputs.';
            } else {
                try {
                    $stmt = $pdo->prepare('
                        INSERT INTO coupons 
                        (code, discount_type, discount_value, min_purchase, expiry_date, usage_limit, is_active) 
                        VALUES (:code, :discount_type, :discount_value, :min_purchase, :expiry_date, :usage_limit, :is_active)
                    ');
                    $stmt->execute([
                        'code' => $code,
                        'discount_type' => $discount_type,
                        'discount_value' => $discount_value,
                        'min_purchase' => $min_purchase,
                        'expiry_date' => $expiry_date,
                        'usage_limit' => $usage_limit,
                        'is_active' => $is_active
                    ]);
                    $success = e(t('coupon_added_success'));
                } catch (PDOException $e) {
                    error_log('Coupon error: ' . $e->getMessage());
                    $error = t('error') . ': Code might already exist.';
                }
            }
        } elseif ($action === 'toggle_active') {
            $id = (int)sanitizeInput('id', 'POST');
            try {
                $stmt = $pdo->prepare('UPDATE coupons SET is_active = NOT is_active WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $success = e(t('coupon_updated_success'));
            } catch (PDOException $e) {
                $error = t('error');
            }
        }
    }
}

// Handle delete
$deleteId = (int)sanitizeInput('delete', 'GET', '0');
if ($deleteId > 0) {
    try {
        $stmt = $pdo->prepare('DELETE FROM coupons WHERE id = :id');
        $stmt->execute(['id' => $deleteId]);
        $success = e(t('coupon_deleted_success'));
    } catch (PDOException $e) {
        error_log('Coupon delete error: ' . $e->getMessage());
        $error = t('error');
    }
}

// Get all coupons
$stmt = $pdo->query('SELECT * FROM coupons ORDER BY created_at DESC');
$coupons = $stmt->fetchAll();

$csrfToken = generateCSRFToken();
$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('manage_coupons')) ?> - Bloom & Vine</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
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
                <!-- Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
                    <div>
                        <h1 class="text-3xl font-luxury font-bold text-gray-800 flex items-center gap-3">
                            <i class="fas fa-ticket-alt text-pink-600"></i>
                            <?= e(t('manage_coupons')) ?>
                        </h1>
                        <p class="text-gray-500 mt-1">Manage discount codes and promotions</p>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3 shadow-sm">
                        <i class="fas fa-exclamation-circle text-xl"></i>
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3 shadow-sm">
                        <i class="fas fa-check-circle text-xl"></i>
                        <?= e($success) ?>
                    </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
                    <!-- Add Coupon Form -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-24">
                            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                                <i class="fas fa-plus-circle text-pink-600"></i>
                                <?= e(t('add_coupon')) ?>
                            </h2>
                            <form method="POST" action="" class="space-y-4">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="action" value="add">
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('coupon_code')) ?></label>
                                    <input type="text" name="code" required placeholder="SUMMER20" style="text-transform:uppercase"
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all bg-gray-50 uppercase">
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('discount_type')) ?></label>
                                        <select name="discount_type" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 bg-gray-50">
                                            <option value="percentage"><?= e(t('percentage')) ?> (%)</option>
                                            <option value="fixed"><?= e(t('fixed_amount')) ?> ($)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('discount_value')) ?></label>
                                        <input type="number" step="0.01" name="discount_value" required min="0.01"
                                               class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 bg-gray-50">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('min_purchase')) ?></label>
                                    <input type="number" step="0.01" name="min_purchase" placeholder="0.00" min="0" value="0"
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 bg-gray-50">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('expiry_date')) ?></label>
                                    <input type="datetime-local" name="expiry_date"
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 bg-gray-50">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('usage_limit')) ?></label>
                                    <input type="number" name="usage_limit" min="1" placeholder="Leave empty for unlimited"
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 bg-gray-50">
                                </div>
                                
                                <div class="flex items-center gap-2 font-bold text-gray-700">
                                    <input type="checkbox" name="is_active" id="is_active" value="1" checked class="w-5 h-5 text-pink-600 rounded">
                                    <label for="is_active"><?= e(t('is_active')) ?></label>
                                </div>
                                
                                <button type="submit" 
                                        class="w-full bg-pink-600 text-white py-3 px-4 rounded-xl hover:bg-pink-700 transition-all duration-300 font-bold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                    <i class="fas fa-plus me-2"></i><?= e(t('add_coupon')) ?>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Coupons List -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-list text-gray-500"></i>
                                    <?= e(t('coupons')) ?>
                                </h2>
                            </div>
                            <div class="divide-y divide-gray-100">
                                <?php if (empty($coupons)): ?>
                                    <div class="p-8 text-center text-gray-500">
                                        <i class="fas fa-ticket-alt text-4xl mb-3 opacity-20"></i>
                                        <p>No coupons created yet.</p>
                                    </div>
                                <?php endif; ?>
                                <?php foreach ($coupons as $coupon): ?>
                                    <div class="p-6 hover:bg-pink-50/30 transition-colors group">
                                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <h3 class="font-bold text-xl text-pink-600 tracking-wider bg-pink-50 px-3 py-1 rounded border border-pink-100">
                                                        <?= e($coupon['code']) ?>
                                                    </h3>
                                                    <?php if ($coupon['is_active']): ?>
                                                        <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs font-bold">Active</span>
                                                    <?php else: ?>
                                                        <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-xs font-bold">Inactive</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-500">
                                                    <span title="Discount">
                                                        <i class="fas fa-tag w-4"></i> 
                                                        <?= $coupon['discount_type'] === 'percentage' ? e((string)$coupon['discount_value']) . '%' : formatPrice((float)$coupon['discount_value']) ?>
                                                    </span>
                                                    <span title="Min Purchase">
                                                        <i class="fas fa-shopping-basket w-4"></i> Min: <?= formatPrice((float)$coupon['min_purchase']) ?>
                                                    </span>
                                                    <span title="Usage">
                                                        <i class="fas fa-users w-4"></i> Used: <?= e((string)$coupon['used_count']) ?><?= $coupon['usage_limit'] ? ' / ' . e((string)$coupon['usage_limit']) : '' ?>
                                                    </span>
                                                    <?php if ($coupon['expiry_date']): ?>
                                                        <span class="text-<?= strtotime($coupon['expiry_date']) < time() ? 'red' : 'gray' ?>-500" title="Expiry Date">
                                                            <i class="far fa-clock w-4"></i> <?= date('M d, Y g:i A', strtotime($coupon['expiry_date'])) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="flex gap-2 items-center">
                                                <form method="POST" action="" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                    <input type="hidden" name="action" value="toggle_active">
                                                    <input type="hidden" name="id" value="<?= e((string)$coupon['id']) ?>">
                                                    <button type="submit" class="p-2 <?= $coupon['is_active'] ? 'text-orange-500 hover:bg-orange-50' : 'text-green-600 hover:bg-green-50' ?> rounded-lg transition-colors" title="Toggle active status">
                                                        <i class="fas <?= $coupon['is_active'] ? 'fa-pause-circle' : 'fa-play-circle' ?>"></i>
                                                    </button>
                                                </form>
                                                <a href="?delete=<?= e((string)$coupon['id']) ?>" 
                                                   onclick="return confirm('<?= e(t('delete_coupon_confirm')) ?>')"
                                                   class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="<?= e(t('delete')) ?>">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
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
