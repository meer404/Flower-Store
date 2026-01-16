<?php
/**
 * Admin Sidebar Component
 * Bloom & Vine Flower Store
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$isSuperAdmin = isSuperAdmin();
?>

<!-- Mobile Sidebar Overlay -->
<div id="sidebarOverlay" onclick="toggleAdminSidebar()" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden backdrop-blur-sm transition-opacity duration-300"></div>

<!-- Sidebar -->
<aside id="adminSidebar" class="fixed lg:static inset-y-0 left-0 z-50 w-72 bg-white border-r border-luxury-border transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col h-full shadow-2xl lg:shadow-none">
    <!-- Sidebar Header -->
    <div class="p-6 border-b border-luxury-border flex justify-between items-center bg-gradient-to-r from-luxury-primary/5 to-transparent">
        <a href="<?= url('index.php') ?>" class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-luxury-primary to-luxury-accent rounded-full flex items-center justify-center text-white shadow-lg">
                <i class="fas fa-spa text-lg"></i>
            </div>
            <div>
                <h2 class="font-luxury font-bold text-xl text-luxury-primary">Bloom & Vine</h2>
                <p class="text-[10px] uppercase tracking-widest text-luxury-textLight"><?= e(t('admin_panel')) ?></p>
            </div>
        </a>
        <button onclick="toggleAdminSidebar()" class="lg:hidden text-luxury-text hover:text-red-600 transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <!-- Navigation -->
    <div class="flex-1 overflow-y-auto py-6 px-4 space-y-8 scrollbar-thin scrollbar-thumb-gray-200">
        
        <!-- General Section -->
        <div>
            <h3 class="px-4 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2"><?= e(t('sidebar_general')) ?></h3>
            <ul class="space-y-1">
                <li>
                    <a href="<?= url('admin/dashboard.php') ?>" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group <?= $currentPage === 'dashboard.php' ? 'bg-purple-50 text-purple-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-purple-600' ?>">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors <?= $currentPage === 'dashboard.php' ? 'bg-purple-100 text-purple-600' : 'bg-gray-100 text-gray-400 group-hover:bg-purple-100 group-hover:text-purple-600' ?>">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <span class="flex-1"><?= e(t('dashboard')) ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= url('index.php') ?>" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group text-gray-600 hover:bg-gray-50 hover:text-blue-600">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-gray-100 text-gray-400 group-hover:bg-blue-100 group-hover:text-blue-600 transition-colors">
                            <i class="fas fa-store"></i>
                        </div>
                        <span class="flex-1"><?= e(t('view_store')) ?></span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Management Section -->
        <div>
            <h3 class="px-4 text-xs font-bold text-gray-400 uppercase tracking-wider mb-2"><?= e(t('sidebar_management')) ?></h3>
            <ul class="space-y-1">
                <?php if ($isSuperAdmin || hasPermission('manage_products')): ?>
                <li>
                    <a href="<?= url('admin/products.php') ?>" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group <?= in_array($currentPage, ['products.php', 'add_product.php', 'edit_product.php']) ? 'bg-green-50 text-green-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-green-600' ?>">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors <?= in_array($currentPage, ['products.php', 'add_product.php', 'edit_product.php']) ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400 group-hover:bg-green-100 group-hover:text-green-600' ?>">
                            <i class="fas fa-box"></i>
                        </div>
                        <span class="flex-1"><?= e(t('products')) ?></span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($isSuperAdmin || hasPermission('manage_categories')): ?>
                <li>
                    <a href="<?= url('admin/categories.php') ?>" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group <?= $currentPage === 'categories.php' ? 'bg-orange-50 text-orange-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-orange-600' ?>">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors <?= $currentPage === 'categories.php' ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-400 group-hover:bg-orange-100 group-hover:text-orange-600' ?>">
                            <i class="fas fa-tags"></i>
                        </div>
                        <span class="flex-1"><?= e(t('categories')) ?></span>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($isSuperAdmin || hasPermission('view_orders')): ?>
                <li>
                    <a href="<?= url('admin/orders.php') ?>" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group <?= $currentPage === 'orders.php' ? 'bg-blue-50 text-blue-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-blue-600' ?>">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors <?= $currentPage === 'orders.php' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-400 group-hover:bg-blue-100 group-hover:text-blue-600' ?>">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <span class="flex-1"><?= e(t('orders')) ?></span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <?php if ($isSuperAdmin): ?>
        <!-- Super Admin Section -->
        <div>
            <h3 class="px-4 text-xs font-bold text-red-400 uppercase tracking-wider mb-2"><?= e(t('sidebar_super_admin')) ?></h3>
            <ul class="space-y-1">
                <li>
                    <a href="<?= url('admin/super_admin_dashboard.php') ?>" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group <?= $currentPage === 'super_admin_dashboard.php' ? 'bg-red-50 text-red-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-red-600' ?>">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors <?= $currentPage === 'super_admin_dashboard.php' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-400 group-hover:bg-red-100 group-hover:text-red-600' ?>">
                            <i class="fas fa-crown"></i>
                        </div>
                        <span class="flex-1"><?= e(t('sidebar_overview')) ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= url('admin/super_admin_users.php') ?>" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group <?= $currentPage === 'super_admin_users.php' ? 'bg-red-50 text-red-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-red-600' ?>">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors <?= $currentPage === 'super_admin_users.php' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-400 group-hover:bg-red-100 group-hover:text-red-600' ?>">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="flex-1"><?= e(t('users')) ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= url('admin/super_admin_admins.php') ?>" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group <?= $currentPage === 'super_admin_admins.php' ? 'bg-red-50 text-red-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-red-600' ?>">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors <?= $currentPage === 'super_admin_admins.php' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-400 group-hover:bg-red-100 group-hover:text-red-600' ?>">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <span class="flex-1"><?= e(t('sidebar_admins')) ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= url('admin/super_admin_reports.php') ?>" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group <?= $currentPage === 'super_admin_reports.php' ? 'bg-red-50 text-red-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-red-600' ?>">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors <?= $currentPage === 'super_admin_reports.php' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-400 group-hover:bg-red-100 group-hover:text-red-600' ?>">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <span class="flex-1"><?= e(t('reports')) ?></span>
                    </a>
                </li>
                <li>
                    <a href="<?= url('admin/super_admin_settings.php') ?>" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group <?= $currentPage === 'super_admin_settings.php' ? 'bg-red-50 text-red-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-red-600' ?>">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors <?= $currentPage === 'super_admin_settings.php' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-400 group-hover:bg-red-100 group-hover:text-red-600' ?>">
                            <i class="fas fa-cog"></i>
                        </div>
                        <span class="flex-1"><?= e(t('sidebar_settings')) ?></span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <!-- User Profile Snippet (Bottom) -->
    <div class="p-4 border-t border-luxury-border bg-gray-50">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-luxury-primary text-white flex items-center justify-center font-bold">
                <?= e(strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1))) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-gray-900 truncate"><?= e($_SESSION['full_name'] ?? 'Admin') ?></p>
                <p class="text-xs text-gray-500 truncate"><?= e($_SESSION['email'] ?? '') ?></p>
            </div>
            <a href="<?= url('logout.php') ?>" class="text-gray-400 hover:text-red-600 transition-colors" title="<?= e(t('nav_logout')) ?>">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>



<script>
function toggleAdminSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    // Toggle translate class
    if (sidebar.classList.contains('-translate-x-full')) {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    } else {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
}
</script>
