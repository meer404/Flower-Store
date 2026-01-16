<?php
/**
 * Admin Header Component
 * Simplified header for Admin/Super Admin panels
 */

$lang = getCurrentLang();
?>

<header class="bg-white border-b border-gray-200 sticky top-0 z-30 shadow-sm">
    <div class="px-6 py-4 flex justify-between items-center">
        <!-- Left Side: Mobile Toggle & Title -->
        <div class="flex items-center gap-4">
            <button onclick="toggleAdminSidebar()" class="lg:hidden text-gray-500 hover:text-gray-800 transition-colors">
                <i class="fas fa-bars text-xl"></i>
            </button>
            
            <h2 class="text-xl font-bold text-gray-800 hidden sm:block">
                <?php
                $pageTitle = 'Dashboard';
                if (strpos($_SERVER['PHP_SELF'], 'products.php') !== false) $pageTitle = t('products');
                elseif (strpos($_SERVER['PHP_SELF'], 'categories.php') !== false) $pageTitle = t('categories');
                elseif (strpos($_SERVER['PHP_SELF'], 'orders.php') !== false) $pageTitle = t('orders');
                elseif (strpos($_SERVER['PHP_SELF'], 'users.php') !== false) $pageTitle = t('users');
                elseif (strpos($_SERVER['PHP_SELF'], 'reports.php') !== false) $pageTitle = t('reports');
                elseif (strpos($_SERVER['PHP_SELF'], 'settings.php') !== false) $pageTitle = t('settings');
                echo e($pageTitle);
                ?>
            </h2>
        </div>

        <!-- Right Side: Actions -->
        <div class="flex items-center gap-4">
            <!-- View Store Link -->
            <a href="../index.php" class="text-gray-500 hover:text-purple-600 transition-colors text-sm font-medium hidden md:flex items-center gap-2" title="<?= e(t('view_store')) ?>">
                <i class="fas fa-external-link-alt"></i>
                <span class="hidden lg:inline"><?= e(t('view_store')) ?></span>
            </a>

            <div class="h-6 w-px bg-gray-200 hidden md:block"></div>

            <!-- Language Switcher -->
            <a href="?lang=<?= $lang === 'en' ? 'ku' : 'en' ?>" 
               class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-50 text-gray-600 hover:bg-purple-50 hover:text-purple-600 transition-all font-bold text-xs"
               title="<?= $lang === 'en' ? 'Switch to Kurdish' : 'Switch to English' ?>">
                <?= $lang === 'en' ? 'KU' : 'EN' ?>
            </a>

            <!-- Notifications -->
            <div class="relative">
                <button class="w-10 h-10 rounded-full bg-gray-50 text-gray-600 hover:bg-purple-50 hover:text-purple-600 transition-all flex items-center justify-center">
                    <i class="fas fa-bell"></i>
                    <?php 
                    // This could be dynamic later
                    $unreadAdminNotifs = 0; 
                    if ($unreadAdminNotifs > 0): 
                    ?>
                        <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- Profile Dropdown -->
            <div class="relative group">
                <button class="flex items-center gap-3 pl-2 focus:outline-none">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-600 to-blue-600 text-white flex items-center justify-center font-bold shadow-md">
                        <?= e(strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1))) ?>
                    </div>
                </button>
                
                <!-- Dropdown Menu -->
                <div class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 hidden group-hover:block transition-all z-50">
                    <div class="p-3 border-b border-gray-100">
                        <p class="text-sm font-bold text-gray-800 truncate"><?= e($_SESSION['full_name'] ?? 'Admin') ?></p>
                        <p class="text-xs text-gray-500 truncate"><?= e($_SESSION['email'] ?? '') ?></p>
                    </div>
                    <div class="p-2">
                        <a href="../account.php" class="block px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-purple-50 hover:text-purple-700 transition-colors">
                            <i class="fas fa-user-circle w-5"></i> <?= e(t('my_profile')) ?>
                        </a>
                        <a href="../logout.php" class="block px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 transition-colors">
                            <i class="fas fa-sign-out-alt w-5"></i> <?= e(t('nav_logout')) ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
