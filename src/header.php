<?php
/**
 * Modern Header Component
 * Bloom & Vine Flower Store
 */

$lang = getCurrentLang();
$dir = getHtmlDir();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Font Awesome for Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* Smooth transitions */
    * {
        transition: all 0.2s ease;
    }
    
    /* Sticky header with shadow on scroll */
    .header-scrolled {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    
    /* Mobile menu animation */
    .mobile-menu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-in-out;
    }
    
    .mobile-menu.active {
        max-height: 100vh;
    }
    
    /* Search bar animation */
    .search-bar {
        width: 0;
        opacity: 0;
        transition: all 0.3s ease;
    }
    
    .search-bar.active {
        width: 300px;
        opacity: 1;
    }
    
    @media (max-width: 768px) {
        .search-bar.active {
            width: 200px;
        }
    }
    
    /* Badge pulse animation */
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
        }
    }
    
    .badge-pulse {
        animation: pulse 2s infinite;
    }
    
    /* Dropdown menu */
    .dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        inset-inline-end: 0;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        min-width: 200px;
        z-index: 1000;
        margin-top: 0.5rem;
    }
    
    .dropdown:hover .dropdown-menu {
        display: block;
    }
    
    .dropdown-menu a {
        display: block;
        padding: 0.75rem 1rem;
        color: #2d2d2d;
        text-decoration: none;
        transition: background-color 0.2s;
    }
    
    .dropdown-menu a:hover {
        background-color: #f5e6d3;
        color: #d4af37;
    }
    
    /* Active link indicator */
    .nav-link.active {
        color: #d4af37 !important;
        position: relative;
    }
    
    .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -4px;
        inset-inline-start: 0;
        inset-inline-end: 0;
        height: 2px;
        background: #d4af37;
    }
</style>

<!-- Main Header -->
<header class="bg-white border-b border-luxury-border sticky top-0 z-50" id="mainHeader">
    <!-- Top Bar -->
    <div class="bg-luxury-primary text-white py-2 hidden md:block">
        <div class="container mx-auto px-6">
            <div class="flex justify-between items-center text-sm">
                <div class="flex items-center gap-4">
                    <span><i class="fas fa-phone me-2"></i>+964 750 123 4567</span>
                    <span><i class="fas fa-envelope me-2"></i>info@bloomandvine.com</span>
                </div>
                <div class="flex items-center gap-4">
                    <span><i class="fas fa-truck me-2"></i><?= e(t('free_shipping_notice')) ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Navigation -->
    <nav class="container mx-auto px-4 md:px-6 py-4">
        <div class="flex justify-between items-center">
            <!-- Logo -->
            <a href="index.php" class="flex items-center space-x-3 group">
                <div class="w-12 h-12 bg-gradient-to-br from-luxury-primary to-luxury-accent rounded-full flex items-center justify-center transform group-hover:rotate-12 transition-transform duration-300">
                    <i class="fas fa-spa text-white text-xl"></i>
                </div>
                <div>
                    <div class="text-2xl font-luxury font-bold text-luxury-primary tracking-wide">Bloom & Vine</div>
                    <div class="text-xs text-luxury-textLight tracking-wider"><?= e(t('premium_flowers')) ?></div>
                </div>
            </a>
            
            <!-- Desktop Navigation -->
            <div class="hidden lg:flex items-center space-x-8">
                <a href="index.php" class="nav-link text-luxury-text hover:text-luxury-accent font-medium relative <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-home mr-2"></i><?= e(t('nav_home')) ?>
                </a>
                <a href="shop.php" class="nav-link text-luxury-text hover:text-luxury-accent font-medium relative <?= $currentPage === 'shop.php' ? 'active' : '' ?>">
                    <i class="fas fa-store mr-2"></i><?= e(t('nav_shop')) ?>
                </a>
                
                <?php if (isLoggedIn()): ?>
                    <?php if (isSuperAdmin()): ?>
                        <a href="admin/super_admin_dashboard.php" class="nav-link text-luxury-text hover:text-red-600 font-medium relative">
                            <i class="fas fa-crown mr-2"></i><?= e('Super Admin') ?>
                        </a>
                    <?php elseif (isAdmin()): ?>
                        <a href="admin/dashboard.php" class="nav-link text-luxury-text hover:text-luxury-accent font-medium relative">
                            <i class="fas fa-dashboard mr-2"></i><?= e(t('nav_admin')) ?>
                        </a>
                    <?php endif; ?>
                    
                    <a href="wishlist.php" class="nav-link text-luxury-text hover:text-luxury-accent font-medium relative">
                        <i class="fas fa-heart mr-2"></i><?= e('Wishlist') ?>
                        <?php 
                        $wishlistCount = getWishlistCount();
                        if ($wishlistCount > 0): 
                        ?>
                            <span class="absolute -top-2 -end-2 bg-pink-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center badge-pulse">
                                <?= e((string)$wishlistCount) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Right Side Actions -->
            <div class="flex items-center gap-4">
                <!-- Search Bar -->
                <div class="hidden md:block relative">
                    <button onclick="toggleSearch()" class="text-luxury-text hover:text-luxury-accent p-2">
                        <i class="fas fa-search text-xl"></i>
                    </button>
                    <div id="searchBar" class="search-bar absolute right-0 top-12">
                        <form action="shop.php" method="GET" class="relative">
                            <input type="hidden" name="lang" value="<?= e($lang) ?>">
                            <input type="text" name="search" placeholder="<?= e(t('search')) ?>..." 
                                   class="w-full px-4 py-3 pe-12 border-2 border-luxury-accent rounded-full focus:outline-none focus:ring-2 focus:ring-luxury-accent shadow-lg">
                            <button type="submit" class="absolute end-2 top-1/2 transform -translate-y-1/2 bg-luxury-accent text-white p-2 rounded-full hover:bg-opacity-90">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <?php if (isLoggedIn()): ?>
                    <!-- Notifications -->
                    <a href="<?= url('notifications.php') ?>" class="relative text-luxury-text hover:text-luxury-accent p-2">
                        <i class="fas fa-bell text-xl"></i>
                        <?php 
                        $unreadCount = getUnreadNotificationCount();
                        if ($unreadCount > 0): 
                        ?>
                            <span class="absolute -top-1 -end-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center badge-pulse">
                                <?= e((string)$unreadCount) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Shopping Cart -->
                    <a href="<?= url('cart.php') ?>" class="relative text-luxury-text hover:text-luxury-accent p-2">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <?php 
                        $cartCount = getCartCount();
                        if ($cartCount > 0): 
                        ?>
                            <span class="absolute -top-1 -end-1 bg-luxury-accent text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center badge-pulse">
                                <?= e((string)$cartCount) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- User Dropdown -->
                    <div class="relative dropdown hidden lg:block">
                        <button class="flex items-center gap-2 text-luxury-text hover:text-luxury-accent p-2">
                            <div class="w-10 h-10 bg-luxury-accent rounded-full flex items-center justify-center text-white font-bold">
                                <?= e(strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1))) ?>
                            </div>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>
                        <div class="dropdown-menu">
                            <?php if (isSuperAdmin()): ?>
                                <a href="admin/super_admin_dashboard.php">
                                    <i class="fas fa-crown mr-2 text-red-600"></i><?= e('Super Admin') ?>
                                </a>
                                <div class="border-t border-luxury-border my-1"></div>
                            <?php elseif (isAdmin()): ?>
                                <a href="admin/dashboard.php">
                                    <i class="fas fa-dashboard mr-2"></i><?= e('Admin Dashboard') ?>
                                </a>
                                <div class="border-t border-luxury-border my-1"></div>
                            <?php endif; ?>
                            <a href="account.php">
                                <i class="fas fa-user mr-2"></i><?= e('My Account') ?>
                            </a>
                            <a href="order_details.php">
                                <i class="fas fa-box mr-2"></i><?= e('My Orders') ?>
                            </a>
                            <a href="wishlist.php">
                                <i class="fas fa-heart mr-2"></i><?= e('My Wishlist') ?>
                            </a>
                            <div class="border-t border-luxury-border my-1"></div>
                            <a href="<?= url('logout.php') ?>">
                                <i class="fas fa-sign-out-alt mr-2"></i><?= e(t('nav_logout')) ?>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Login/Register -->
                    <a href="login.php" class="hidden md:inline-block text-luxury-text hover:text-luxury-accent font-medium px-4 py-2">
                        <i class="fas fa-sign-in-alt mr-2"></i><?= e(t('nav_login')) ?>
                    </a>
                    <a href="register.php" class="hidden md:inline-block bg-luxury-accent text-white px-6 py-2 rounded-full hover:bg-opacity-90 transition-all font-medium shadow-md">
                        <i class="fas fa-user-plus mr-2"></i><?= e(t('nav_register')) ?>
                    </a>
                <?php endif; ?>
                
                <!-- Language Switcher -->
                <a href="?lang=<?= $lang === 'en' ? 'ku' : 'en' ?>" 
                   class="bg-gradient-to-r from-luxury-primary to-luxury-accent text-white px-4 py-2 rounded-full hover:shadow-lg font-semibold text-sm">
                    <i class="fas fa-globe me-2"></i><?= $lang === 'en' ? 'کوردی' : 'English' ?>
                </a>
                
                <!-- Mobile Menu Toggle -->
                <button onclick="toggleMobileMenu()" class="lg:hidden text-luxury-text hover:text-luxury-accent p-2">
                    <i class="fas fa-bars text-2xl" id="menuIcon"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobileMenu" class="mobile-menu lg:hidden mt-4">
            <div class="bg-white border border-luxury-border rounded-lg shadow-lg p-4 space-y-3">
                <!-- Mobile Search -->
                <form action="<?= url('shop.php') ?>" method="GET" class="mb-4">
                    <input type="hidden" name="lang" value="<?= e($lang) ?>">
                    <div class="relative">
                        <input type="text" name="search" placeholder="<?= e(t('search')) ?>..." 
                               class="w-full px-4 py-2 pe-10 border border-luxury-border rounded-full focus:outline-none focus:ring-2 focus:ring-luxury-accent">
                        <button type="submit" class="absolute end-2 top-1/2 transform -translate-y-1/2 text-luxury-accent">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
                <a href="<?= url('index.php') ?>" class="block py-2 px-4 text-luxury-text hover:bg-luxury-accentLight rounded-lg">
                    <i class="fas fa-home mr-3"></i><?= e(t('nav_home')) ?>
                </a>
                <a href="<?= url('shop.php') ?>" class="block py-2 px-4 text-luxury-text hover:bg-luxury-accentLight rounded-lg">
                    <i class="fas fa-store mr-3"></i><?= e(t('nav_shop')) ?>
                </a>
                
                <?php if (isLoggedIn()): ?>
                    <?php if (isSuperAdmin()): ?>
                        <a href="admin/super_admin_dashboard.php" class="block py-2 px-4 text-red-600 hover:bg-red-50 rounded-lg">
                            <i class="fas fa-crown mr-3"></i><?= e('Super Admin') ?>
                        </a>
                    <?php elseif (isAdmin()): ?>
                        <a href="admin/dashboard.php" class="block py-2 px-4 text-luxury-text hover:bg-luxury-accentLight rounded-lg">
                            <i class="fas fa-dashboard mr-3"></i><?= e(t('nav_admin')) ?>
                        </a>
                    <?php endif; ?>
                    
                    <a href="account.php" class="block py-2 px-4 text-luxury-text hover:bg-luxury-accentLight rounded-lg">
                        <i class="fas fa-user mr-3"></i><?= e('Account') ?>
                    </a>
                    <a href="wishlist.php" class="block py-2 px-4 text-luxury-text hover:bg-luxury-accentLight rounded-lg">
                        <i class="fas fa-heart mr-3"></i><?= e('Wishlist') ?>
                        <?php if ($wishlistCount > 0): ?>
                            <span class="bg-pink-500 text-white px-2 py-0.5 rounded-full text-xs ms-2"><?= e((string)$wishlistCount) ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="notifications.php" class="block py-2 px-4 text-luxury-text hover:bg-luxury-accentLight rounded-lg">
                        <i class="fas fa-bell mr-3"></i><?= e('Notifications') ?>
                        <?php if ($unreadCount > 0): ?>
                            <span class="bg-red-500 text-white px-2 py-0.5 rounded-full text-xs ms-2"><?= e((string)$unreadCount) ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="cart.php" class="block py-2 px-4 text-luxury-text hover:bg-luxury-accentLight rounded-lg">
                        <i class="fas fa-shopping-cart mr-3"></i><?= e(t('nav_cart')) ?>
                        <?php if ($cartCount > 0): ?>
                            <span class="bg-luxury-accent text-white px-2 py-0.5 rounded-full text-xs ms-2"><?= e((string)$cartCount) ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="order_details.php" class="block py-2 px-4 text-luxury-text hover:bg-luxury-accentLight rounded-lg">
                        <i class="fas fa-box mr-3"></i><?= e('My Orders') ?>
                    </a>
                    <div class="border-t border-luxury-border my-2"></div>
                    <a href="logout.php" class="block py-2 px-4 text-red-600 hover:bg-red-50 rounded-lg">
                        <i class="fas fa-sign-out-alt mr-3"></i><?= e(t('nav_logout')) ?>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="block py-2 px-4 text-luxury-text hover:bg-luxury-accentLight rounded-lg">
                        <i class="fas fa-sign-in-alt mr-3"></i><?= e(t('nav_login')) ?>
                    </a>
                    <a href="register.php" class="block py-2 px-4 bg-luxury-accent text-white hover:bg-opacity-90 rounded-lg text-center">
                        <i class="fas fa-user-plus mr-2"></i><?= e(t('nav_register')) ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<script>
    // Sticky header on scroll
    window.addEventListener('scroll', function() {
        const header = document.getElementById('mainHeader');
        if (window.scrollY > 50) {
            header.classList.add('header-scrolled');
        } else {
            header.classList.remove('header-scrolled');
        }
    });
    
    // Toggle mobile menu
    function toggleMobileMenu() {
        const menu = document.getElementById('mobileMenu');
        const icon = document.getElementById('menuIcon');
        menu.classList.toggle('active');
        
        if (menu.classList.contains('active')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        } else {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    }
    
    // Toggle search bar
    function toggleSearch() {
        const searchBar = document.getElementById('searchBar');
        searchBar.classList.toggle('active');
        
        if (searchBar.classList.contains('active')) {
            searchBar.querySelector('input').focus();
        }
    }
    
    // Close search bar when clicking outside
    document.addEventListener('click', function(event) {
        const searchBar = document.getElementById('searchBar');
        const searchButton = event.target.closest('button[onclick="toggleSearch()"]');
        
        if (!searchBar.contains(event.target) && !searchButton && searchBar.classList.contains('active')) {
            searchBar.classList.remove('active');
        }
    });
</script>

