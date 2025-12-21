<?php
declare(strict_types=1);

/**
 * Super Admin - System Settings
 * Configure system-wide settings
 */

require_once __DIR__ . '/../src/language.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/design_config.php';
require_once __DIR__ . '/../src/components.php';

requireSuperAdmin();

$pdo = getDB();
$lang = getCurrentLang();
$dir = getHtmlDir();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = sanitizeInput('csrf_token', 'POST');
    
    if (verifyCSRFToken($csrfToken)) {
        $settings = [
            'site_name' => sanitizeInput('site_name', 'POST'),
            'site_email' => sanitizeInput('site_email', 'POST'),
            'currency' => sanitizeInput('currency', 'POST'),
            'tax_rate' => sanitizeInput('tax_rate', 'POST'),
            'shipping_cost' => sanitizeInput('shipping_cost', 'POST'),
            'maintenance_mode' => sanitizeInput('maintenance_mode', 'POST', '0'),
            'max_upload_size' => sanitizeInput('max_upload_size', 'POST')
        ];
        
        try {
            foreach ($settings as $key => $value) {
                $type = 'string';
                if (in_array($key, ['tax_rate', 'shipping_cost', 'max_upload_size'])) {
                    $type = 'number';
                } elseif ($key === 'maintenance_mode') {
                    $type = 'boolean';
                }
                setSystemSetting($key, $value, $type);
            }
            
            logActivity('system_settings_updated', 'system', null, 'Updated system settings');
            redirect('super_admin_settings.php', 'Settings updated successfully', 'success');
        } catch (Exception $e) {
            error_log('Settings update error: ' . $e->getMessage());
            redirect('super_admin_settings.php', 'Error updating settings', 'error');
        }
    }
}

// Get current settings
$currentSettings = [
    'site_name' => getSystemSetting('site_name', 'Bloom & Vine'),
    'site_email' => getSystemSetting('site_email', 'info@bloomandvine.com'),
    'currency' => getSystemSetting('currency', '$'),
    'tax_rate' => getSystemSetting('tax_rate', 0),
    'shipping_cost' => getSystemSetting('shipping_cost', 0),
    'maintenance_mode' => getSystemSetting('maintenance_mode', false),
    'max_upload_size' => getSystemSetting('max_upload_size', 5242880)
];

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Super Admin</title>
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
                        <i class="fas fa-cog mr-4"></i>System Settings
                    </h1>
                    <p class="text-red-200">Configure system-wide settings</p>
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

        <form method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            
            <!-- General Settings -->
            <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl p-6">
                <h2 class="text-2xl font-bold text-luxury-primary mb-6">
                    <i class="fas fa-info-circle mr-2 text-blue-600"></i>General Settings
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-luxury-text mb-2">Site Name</label>
                        <input type="text" name="site_name" value="<?= e($currentSettings['site_name']) ?>" required
                               class="w-full px-4 py-2 border border-luxury-border rounded-xl focus:outline-none focus:ring-2 focus:ring-red-600">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-luxury-text mb-2">Site Email</label>
                        <input type="email" name="site_email" value="<?= e($currentSettings['site_email']) ?>" required
                               class="w-full px-4 py-2 border border-luxury-border rounded-xl focus:outline-none focus:ring-2 focus:ring-red-600">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-luxury-text mb-2">Currency Symbol</label>
                        <input type="text" name="currency" value="<?= e($currentSettings['currency']) ?>" required maxlength="5"
                               class="w-full px-4 py-2 border border-luxury-border rounded-xl focus:outline-none focus:ring-2 focus:ring-red-600">
                    </div>
                </div>
            </div>

            <!-- Financial Settings -->
            <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl p-6">
                <h2 class="text-2xl font-bold text-luxury-primary mb-6">
                    <i class="fas fa-dollar-sign mr-2 text-green-600"></i>Financial Settings
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-luxury-text mb-2">Tax Rate (%)</label>
                        <input type="number" name="tax_rate" value="<?= e((string)$currentSettings['tax_rate']) ?>" min="0" max="100" step="0.01"
                               class="w-full px-4 py-2 border border-luxury-border rounded-xl focus:outline-none focus:ring-2 focus:ring-red-600">
                        <p class="text-xs text-luxury-textLight mt-1">Enter tax rate as percentage (e.g., 10 for 10%)</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-luxury-text mb-2">Default Shipping Cost</label>
                        <input type="number" name="shipping_cost" value="<?= e((string)$currentSettings['shipping_cost']) ?>" min="0" step="0.01"
                               class="w-full px-4 py-2 border border-luxury-border rounded-xl focus:outline-none focus:ring-2 focus:ring-red-600">
                        <p class="text-xs text-luxury-textLight mt-1">Default shipping cost in <?= e($currentSettings['currency']) ?></p>
                    </div>
                </div>
            </div>

            <!-- System Settings -->
            <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl p-6">
                <h2 class="text-2xl font-bold text-luxury-primary mb-6">
                    <i class="fas fa-server mr-2 text-purple-600"></i>System Settings
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="maintenance_mode" value="1" 
                                   <?= $currentSettings['maintenance_mode'] ? 'checked' : '' ?>
                                   class="w-5 h-5 text-red-600 border-luxury-border rounded focus:ring-red-600">
                            <div>
                                <span class="block text-sm font-medium text-luxury-text">Maintenance Mode</span>
                                <span class="text-xs text-luxury-textLight">Enable to put site in maintenance mode</span>
                            </div>
                        </label>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-luxury-text mb-2">Max Upload Size (bytes)</label>
                        <input type="number" name="max_upload_size" value="<?= e((string)$currentSettings['max_upload_size']) ?>" min="1048576"
                               class="w-full px-4 py-2 border border-luxury-border rounded-xl focus:outline-none focus:ring-2 focus:ring-red-600">
                        <p class="text-xs text-luxury-textLight mt-1">Maximum file upload size in bytes (default: 5MB = 5242880)</p>
                    </div>
                </div>
            </div>

            <!-- Database Info -->
            <div class="bg-white border-2 border-luxury-border rounded-2xl shadow-xl p-6">
                <h2 class="text-2xl font-bold text-luxury-primary mb-6">
                    <i class="fas fa-database mr-2 text-orange-600"></i>Database Information
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php
                    $dbStats = [
                        'Total Users' => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
                        'Total Products' => $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
                        'Total Orders' => $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn()
                    ];
                    foreach ($dbStats as $label => $value):
                    ?>
                        <div class="text-center p-4 bg-gray-50 rounded-xl">
                            <p class="text-3xl font-bold text-luxury-primary"><?= e((string)$value) ?></p>
                            <p class="text-sm text-luxury-textLight mt-2"><?= e($label) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end gap-4">
                <a href="super_admin_dashboard.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-8 py-3 rounded-xl transition-all font-semibold">
                    Cancel
                </a>
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-xl transition-all font-semibold">
                    <i class="fas fa-save mr-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>

    <?= modernFooter() ?>
</body>
</html>

