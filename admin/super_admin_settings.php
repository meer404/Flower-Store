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

requireAdmin();
requirePermission('system_settings');

$pdo = getDB();
$lang = getCurrentLang();
$dir = getHtmlDir();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = sanitizeInput('csrf_token', 'POST');
    
    if (verifyCSRFToken($csrfToken)) {
        $tierMaxes = $_POST['delivery_tier_max'] ?? [];
        $tierFees = $_POST['delivery_tier_fee'] ?? [];
        $deliveryTiers = [];
        $tierCount = max(count((array)$tierMaxes), count((array)$tierFees));
        for ($i = 0; $i < $tierCount; $i++) {
            $maxRaw = trim((string)($tierMaxes[$i] ?? ''));
            $feeRaw = trim((string)($tierFees[$i] ?? ''));

            if ($maxRaw === '' || $feeRaw === '') {
                continue;
            }

            if (!is_numeric($maxRaw) || !is_numeric($feeRaw)) {
                continue;
            }

            $maxVal = (float)$maxRaw;
            $feeVal = (float)$feeRaw;

            if ($maxVal <= 0 || $feeVal < 0) {
                continue;
            }

            $deliveryTiers[] = ['max' => $maxVal, 'fee' => $feeVal];
        }

        $settings = [
            'site_name' => sanitizeInput('site_name', 'POST'),
            'site_email' => sanitizeInput('site_email', 'POST'),
            'currency' => sanitizeInput('currency', 'POST'),
            'tax_rate' => sanitizeInput('tax_rate', 'POST'),
            'shipping_cost' => sanitizeInput('shipping_cost', 'POST'),
            'delivery_outer_fee' => sanitizeInput('delivery_outer_fee', 'POST'),
            'maintenance_mode' => sanitizeInput('maintenance_mode', 'POST', '0'),
            'max_upload_size' => sanitizeInput('max_upload_size', 'POST')
        ];
        
        try {
            foreach ($settings as $key => $value) {
                $type = 'string';
                if (in_array($key, ['tax_rate', 'shipping_cost', 'delivery_outer_fee', 'max_upload_size'])) {
                    $type = 'number';
                } elseif ($key === 'maintenance_mode') {
                    $type = 'boolean';
                }
                setSystemSetting($key, $value, $type);
            }

            if (empty($deliveryTiers)) {
                $deliveryTiers = getDeliveryFeeTiers();
            }
            setSystemSetting('delivery_fee_tiers', $deliveryTiers, 'json');
            
            logActivity('system_settings_updated', 'system', null, t('settings_updated_log'));
            redirect('super_admin_settings.php', t('settings_updated_success'), 'success');
        } catch (Exception $e) {
            error_log('Settings update error: ' . $e->getMessage());
            redirect('super_admin_settings.php', t('settings_update_error'), 'error');
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
    'delivery_outer_fee' => getSystemSetting('delivery_outer_fee', 0),
    'delivery_fee_tiers' => getSystemSetting('delivery_fee_tiers', getDeliveryFeeTiers()),
    'maintenance_mode' => getSystemSetting('maintenance_mode', false),
    'max_upload_size' => getSystemSetting('max_upload_size', 5242880)
];

$deliveryTiers = $currentSettings['delivery_fee_tiers'];
if (!is_array($deliveryTiers)) {
    $deliveryTiers = getDeliveryFeeTiers();
}
$deliveryTiers = array_values($deliveryTiers);
$deliveryTiers = array_slice($deliveryTiers, 0, 5);
while (count($deliveryTiers) < 5) {
    $deliveryTiers[] = ['max' => 0, 'fee' => 0];
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('system_settings')) ?> - Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
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
                                <i class="fas fa-cogs me-3"></i><?= e(t('system_settings')) ?>
                            </h1>
                            <p class="text-red-200"><?= e(t('system_settings_desc')) ?></p>
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

                <form method="POST" class="space-y-8">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- General Settings -->
                        <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8 border border-gray-100">
                            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2 pb-4 border-b border-gray-100">
                                <i class="fas fa-globe text-blue-600"></i><?= e(t('general_settings')) ?>
                            </h2>
                            
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('site_name')) ?></label>
                                    <input type="text" name="site_name" value="<?= e($currentSettings['site_name']) ?>" required
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('site_email')) ?></label>
                                    <input type="email" name="site_email" value="<?= e($currentSettings['site_email']) ?>" required
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('currency_symbol')) ?></label>
                                    <input type="text" name="currency" value="<?= e($currentSettings['currency']) ?>" required maxlength="5"
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                </div>
                            </div>
                        </div>

                        <!-- Financial Settings -->
                        <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8 border border-gray-100">
                            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2 pb-4 border-b border-gray-100">
                                <i class="fas fa-coins text-green-600"></i><?= e(t('financial_settings')) ?>
                            </h2>
                            
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('tax_rate')) ?> (%)</label>
                                    <input type="number" name="tax_rate" value="<?= e((string)$currentSettings['tax_rate']) ?>" min="0" max="100" step="0.01"
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                    <p class="text-xs text-gray-500 mt-1"><?= e(t('tax_rate_hint')) ?></p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('shipping_cost')) ?> (<?= e($currentSettings['currency']) ?>)</label>
                                    <input type="number" name="shipping_cost" value="<?= e((string)$currentSettings['shipping_cost']) ?>" min="0" step="0.01"
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                    <p class="text-xs text-gray-500 mt-1"><?= e(t('shipping_cost_hint')) ?></p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('delivery_outer_fee')) ?> (<?= e($currentSettings['currency']) ?>)</label>
                                    <input type="number" name="delivery_outer_fee" value="<?= e((string)$currentSettings['delivery_outer_fee']) ?>" min="0" step="0.01"
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                    <p class="text-xs text-gray-500 mt-1"><?= e(t('delivery_outer_fee_hint')) ?></p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('delivery_pricing')) ?></label>
                                    <p class="text-xs text-gray-500 mb-3"><?= e(t('delivery_pricing_hint')) ?></p>
                                    <div class="grid grid-cols-3 gap-3 text-xs font-semibold text-gray-500 mb-2">
                                        <span class="col-span-2"><?= e(t('distance_up_to_km')) ?></span>
                                        <span><?= e(t('delivery_fee_amount')) ?></span>
                                    </div>
                                    <div class="space-y-3">
                                        <?php foreach ($deliveryTiers as $index => $tier): ?>
                                            <div class="grid grid-cols-3 gap-3 delivery-tier-row" data-tier-index="<?= e((string)$index) ?>">
                                                <input type="number" name="delivery_tier_max[]" value="<?= e((string)$tier['max']) ?>" min="0" step="0.1"
                                                       class="col-span-2 w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white delivery-tier-max">
                                                <input type="number" name="delivery_tier_fee[]" value="<?= e((string)$tier['fee']) ?>" min="0" step="0.01"
                                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white delivery-tier-fee">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('delivery_map_title')) ?></label>
                                    <p class="text-xs text-gray-500 mb-3"><?= e(t('delivery_map_hint')) ?></p>
                                    <div id="delivery-map" class="w-full h-72 rounded-2xl border border-gray-200"></div>
                                    <p class="text-xs text-gray-500 mt-2"><?= e(t('delivery_map_drag_hint')) ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- System Settings -->
                        <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8 border border-gray-100">
                            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2 pb-4 border-b border-gray-100">
                                <i class="fas fa-server text-purple-600"></i><?= e(t('server_settings')) ?>
                            </h2>
                            
                            <div class="space-y-6">
                                <div>
                                    <label class="flex items-start gap-4 p-4 rounded-xl border border-gray-200 bg-gray-50 cursor-pointer hover:bg-white hover:shadow-md transition-all">
                                        <div class="flex items-center h-5 mt-1">
                                            <input type="checkbox" name="maintenance_mode" value="1" 
                                                   <?= $currentSettings['maintenance_mode'] ? 'checked' : '' ?>
                                                   class="w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                        </div>
                                        <div>
                                            <span class="block text-sm font-bold text-gray-800"><?= e(t('maintenance_mode')) ?></span>
                                            <span class="block text-xs text-gray-500 mt-1"><?= e(t('maintenance_mode_hint')) ?></span>
                                        </div>
                                    </label>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2"><?= e(t('max_upload_size')) ?></label>
                                    <div class="relative">
                                        <input type="number" name="max_upload_size" value="<?= e((string)$currentSettings['max_upload_size']) ?>" min="1048576"
                                               class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all bg-gray-50 focus:bg-white">
                                        <span class="absolute right-4 top-3 text-sm text-gray-400 font-medium">bytes</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1"><?= e(t('max_upload_size_hint')) ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Database Info -->
                        <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8 border border-gray-100">
                            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2 pb-4 border-b border-gray-100">
                                <i class="fas fa-database text-orange-600"></i><?= e(t('database_info')) ?>
                            </h2>
                            
                            <div class="grid grid-cols-3 gap-4">
                                <?php
                                $dbStats = [
                                    'total_users' => $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
                                    'total_products' => $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
                                    'total_orders' => $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn()
                                ];
                                foreach ($dbStats as $key => $value):
                                ?>
                                    <div class="text-center p-4 bg-gray-50 rounded-xl border border-gray-100">
                                        <p class="text-2xl font-black text-gray-800"><?= e((string)$value) ?></p>
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-1"><?= e(t($key)) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end pt-6 border-t border-gray-200">
                        <button type="submit" class="bg-gradient-to-r from-red-600 to-purple-800 text-white px-8 py-4 rounded-xl hover:shadow-xl transition-all font-bold text-lg transform hover:-translate-y-1">
                            <i class="fas fa-save me-2"></i><?= e(t('save_settings')) ?>
                        </button>
                    </div>
                </form>
            </main>
                        
            <!-- Footer -->
            <?php include __DIR__ . '/footer.php'; ?>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
    const deliveryMapConfig = <?= json_encode([
        'store' => getStoreCoordinates(),
        'tiers' => $deliveryTiers,
        'currency' => $currentSettings['currency']
    ], JSON_UNESCAPED_SLASHES) ?>;

    const deliveryMapLabels = {
        distanceKm: <?= json_encode(t('delivery_map_radius_km')) ?>,
        fee: <?= json_encode(t('delivery_map_fee_label')) ?>
    };

    const deliveryTierRows = Array.from(document.querySelectorAll('.delivery-tier-row'));
    const mapContainer = document.getElementById('delivery-map');

    if (mapContainer && deliveryTierRows.length) {
        const storeLatLng = L.latLng(deliveryMapConfig.store.lat, deliveryMapConfig.store.lng);
        const map = L.map(mapContainer, {
            scrollWheelZoom: false
        }).setView(storeLatLng, 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.marker(storeLatLng).addTo(map);

        const tierColors = ['#f97316', '#ef4444', '#8b5cf6', '#14b8a6', '#0ea5e9'];
        const tierState = [];

        function kmToRadiusMeters(km) {
            return Math.max(km, 0) * 1000;
        }

        function radiusToKm(radiusMeters) {
            return Math.max(radiusMeters, 0) / 1000;
        }

        function handlePositionFromRadius(center, radiusMeters) {
            const latRad = center.lat * Math.PI / 180;
            const deltaLng = (radiusMeters / 1000) / (111.32 * Math.cos(latRad));
            return L.latLng(center.lat, center.lng + deltaLng);
        }

        function updateRowFromRadius(index, radiusMeters) {
            const row = tierState[index]?.row;
            if (!row) {
                return;
            }
            const kmValue = radiusToKm(radiusMeters);
            row.maxInput.value = kmValue.toFixed(1);
        }

        function syncHandle(index) {
            const state = tierState[index];
            if (!state) {
                return;
            }
            const radiusMeters = state.circle.getRadius();
            const newLatLng = handlePositionFromRadius(storeLatLng, radiusMeters);
            state.handle.setLatLng(newLatLng);
        }

        deliveryTierRows.forEach((row, index) => {
            const maxInput = row.querySelector('.delivery-tier-max');
            const feeInput = row.querySelector('.delivery-tier-fee');
            const maxKm = parseFloat(maxInput?.value || '0');

            const circle = L.circle(storeLatLng, {
                radius: kmToRadiusMeters(maxKm),
                color: tierColors[index % tierColors.length],
                weight: 2,
                fillOpacity: 0.08
            }).addTo(map);

            const handle = L.marker(handlePositionFromRadius(storeLatLng, kmToRadiusMeters(maxKm)), {
                draggable: true
            }).addTo(map);

            handle.bindTooltip(`${deliveryMapLabels.distanceKm} ${maxKm.toFixed(1)} km\n${deliveryMapLabels.fee} ${deliveryMapConfig.currency}${parseFloat(feeInput?.value || '0').toFixed(2)}`,
                {direction: 'top', offset: [0, -8]});

            handle.on('drag', () => {
                const distanceMeters = map.distance(storeLatLng, handle.getLatLng());
                circle.setRadius(distanceMeters);
                updateRowFromRadius(index, distanceMeters);
                handle.setTooltipContent(`${deliveryMapLabels.distanceKm} ${radiusToKm(distanceMeters).toFixed(1)} km\n${deliveryMapLabels.fee} ${deliveryMapConfig.currency}${parseFloat(feeInput?.value || '0').toFixed(2)}`);
            });

            maxInput?.addEventListener('input', () => {
                const value = parseFloat(maxInput.value || '0');
                const radius = kmToRadiusMeters(value);
                circle.setRadius(radius);
                syncHandle(index);
                handle.setTooltipContent(`${deliveryMapLabels.distanceKm} ${value.toFixed(1)} km\n${deliveryMapLabels.fee} ${deliveryMapConfig.currency}${parseFloat(feeInput?.value || '0').toFixed(2)}`);
            });

            feeInput?.addEventListener('input', () => {
                const currentRadius = radiusToKm(circle.getRadius());
                handle.setTooltipContent(`${deliveryMapLabels.distanceKm} ${currentRadius.toFixed(1)} km\n${deliveryMapLabels.fee} ${deliveryMapConfig.currency}${parseFloat(feeInput.value || '0').toFixed(2)}`);
            });

            tierState[index] = { row: { maxInput, feeInput }, circle, handle };
        });

        map.on('zoomend', () => {
            tierState.forEach((_, index) => syncHandle(index));
        });

        setTimeout(() => map.invalidateSize(), 200);
    }
    </script>
</body>
</html>
