<?php
/**
 * Reusable UI Components
 * Bloom & Vine Flower Store
 * Modern Design System
 */

/**
 * Generate a modern button component
 */
function button(string $text, string $href = '#', string $style = 'primary', string $size = 'md', array $attrs = []): string {
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-full transition-all duration-300 shadow-md hover:shadow-xl transform hover:-translate-y-0.5';
    
    $sizeClasses = [
        'sm' => 'px-4 py-2 text-sm',
        'md' => 'px-6 py-3 text-base',
        'lg' => 'px-8 py-4 text-lg',
        'xl' => 'px-10 py-5 text-xl'
    ];
    
    $styleClasses = [
        'primary' => 'bg-gradient-to-r from-luxury-primary to-luxury-accent text-white hover:from-luxury-accent hover:to-luxury-primary',
        'secondary' => 'bg-luxury-accent text-white hover:bg-opacity-90',
        'outline' => 'border-2 border-luxury-accent text-luxury-accent hover:bg-luxury-accent hover:text-white',
        'ghost' => 'text-luxury-accent hover:bg-luxury-accentLight'
    ];
    
    $classes = $baseClasses . ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']) . ' ' . ($styleClasses[$style] ?? $styleClasses['primary']);
    
    $attrString = '';
    foreach ($attrs as $key => $value) {
        $attrString .= ' ' . e($key) . '="' . e($value) . '"';
    }
    
    return '<a href="' . e($href) . '" class="' . $classes . '"' . $attrString . '>' . e($text) . '</a>';
}

/**
 * Generate a card component
 */
function card(string $content, string $title = '', array $options = []): string {
    $classes = $options['classes'] ?? '';
    $padding = $options['padding'] ?? 'p-6';
    $shadow = $options['shadow'] ?? 'shadow-luxury hover:shadow-luxuryHover';
    
    $html = '<div class="bg-white border border-luxury-border rounded-xl ' . $shadow . ' ' . $padding . ' transition-all duration-300 ' . $classes . '">';
    
    if ($title) {
        $html .= '<h3 class="text-2xl font-luxury font-bold text-luxury-primary mb-4">' . e($title) . '</h3>';
    }
    
    $html .= $content;
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate a badge component
 */
function badge(string $text, string $color = 'gold'): string {
    $colors = [
        'gold' => 'bg-luxury-accent text-white',
        'green' => 'bg-green-500 text-white',
        'red' => 'bg-red-500 text-white',
        'blue' => 'bg-blue-500 text-white',
        'gray' => 'bg-gray-200 text-gray-700'
    ];
    
    $colorClass = $colors[$color] ?? $colors['gold'];
    
    return '<span class="' . $colorClass . ' px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide">' . e($text) . '</span>';
}

/**
 * Generate an alert/notification component
 */
function alert(string $message, string $type = 'info'): string {
    $types = [
        'success' => ['bg' => 'bg-green-50', 'border' => 'border-green-400', 'text' => 'text-green-800', 'icon' => 'fa-check-circle'],
        'error' => ['bg' => 'bg-red-50', 'border' => 'border-red-400', 'text' => 'text-red-800', 'icon' => 'fa-exclamation-circle'],
        'warning' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-400', 'text' => 'text-yellow-800', 'icon' => 'fa-exclamation-triangle'],
        'info' => ['bg' => 'bg-blue-50', 'border' => 'border-blue-400', 'text' => 'text-blue-800', 'icon' => 'fa-info-circle']
    ];
    
    $style = $types[$type] ?? $types['info'];
    
    return '<div class="' . $style['bg'] . ' border-l-4 ' . $style['border'] . ' ' . $style['text'] . ' p-4 rounded-r-lg mb-6 flex items-center">
        <i class="fas ' . $style['icon'] . ' text-2xl mr-3"></i>
        <span>' . e($message) . '</span>
    </div>';
}

/**
 * Generate a product card
 */
function productCard(array $product): string {
    $productName = getProductName($product);
    $productDesc = getProductDescription($product);
    $price = formatPrice((float)$product['price']);
    $imageUrl = $product['image_url'] ?? '';
    $productId = (int)$product['id'];
    
    $inStock = (int)$product['stock_qty'] > 0;
    
    $html = '<div class="group bg-white border border-luxury-border rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 transform hover:-translate-y-2">';
    
    // Image
    if ($imageUrl) {
        $html .= '<div class="relative overflow-hidden h-72">
            <img src="' . e($imageUrl) . '" alt="' . e($productName) . '" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
            <div class="absolute top-4 right-4">';
        
        if ($product['is_featured'] ?? false) {
            $html .= '<span class="bg-luxury-accent text-white px-3 py-1 rounded-full text-xs font-bold uppercase">Featured</span>';
        }
        
        if (!$inStock) {
            $html .= '<span class="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold uppercase ml-2">Out of Stock</span>';
        }
        
        $html .= '</div>';
        
        // Wishlist button overlay
        if (isLoggedIn()) {
            $html .= '<button onclick="toggleWishlist(' . $productId . ')" class="absolute top-4 left-4 bg-white/90 backdrop-blur-sm w-12 h-12 rounded-full flex items-center justify-center hover:bg-luxury-accent hover:text-white transition-all duration-300 shadow-lg">
                <i class="fas fa-heart text-lg"></i>
            </button>';
        }
        
        $html .= '</div>';
    }
    
    // Content
    $html .= '<div class="p-6">';
    
    // Category
    if (isset($product['category_name_en'])) {
        $categoryName = getCategoryName($product);
        $html .= '<p class="text-luxury-textLight text-xs uppercase tracking-wider mb-2">' . e($categoryName) . '</p>';
    }
    
    // Title
    $html .= '<h3 class="text-xl font-luxury font-bold text-luxury-primary mb-3 min-h-[3rem] group-hover:text-luxury-accent transition-colors">
        <a href="product.php?id=' . $productId . '">' . e($productName) . '</a>
    </h3>';
    
    // Description
    $shortDesc = strlen($productDesc) > 100 ? substr($productDesc, 0, 100) . '...' : $productDesc;
    $html .= '<p class="text-luxury-textLight text-sm mb-4 line-clamp-2">' . e($shortDesc) . '</p>';
    
    // Price and actions
    $html .= '<div class="flex items-center justify-between mb-4">
        <span class="text-3xl font-bold text-luxury-accent font-luxury">' . e($price) . '</span>';
    
    // Rating (if available)
    if (isset($product['avg_rating']) && $product['avg_rating'] > 0) {
        $rating = (float)$product['avg_rating'];
        $html .= '<div class="flex items-center gap-1">';
        for ($i = 1; $i <= 5; $i++) {
            $html .= '<i class="fas fa-star ' . ($i <= $rating ? 'text-yellow-400' : 'text-gray-300') . '"></i>';
        }
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    // Buttons
    $html .= '<div class="space-y-3">';
    
    $html .= '<a href="product.php?id=' . $productId . '" 
        class="block w-full text-center border-2 border-luxury-primary text-luxury-primary py-3 px-4 rounded-full hover:bg-luxury-primary hover:text-white transition-all duration-300 font-semibold">
        <i class="fas fa-eye mr-2"></i>View Details
    </a>';
    
    if ($inStock) {
        if (isLoggedIn()) {
            $html .= '<form method="POST" action="cart_action.php" class="w-full">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="' . $productId . '">
                <input type="hidden" name="csrf_token" value="' . e(generateCSRFToken()) . '">
                <button type="submit" class="w-full bg-gradient-to-r from-luxury-accent to-yellow-500 text-white py-3 px-4 rounded-full hover:from-yellow-500 hover:to-luxury-accent transition-all duration-300 font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-shopping-cart mr-2"></i>Add to Cart
                </button>
            </form>';
        } else {
            $html .= '<a href="login.php" class="block w-full text-center bg-gradient-to-r from-luxury-accent to-yellow-500 text-white py-3 px-4 rounded-full hover:from-yellow-500 hover:to-luxury-accent transition-all duration-300 font-semibold shadow-lg">
                <i class="fas fa-shopping-cart mr-2"></i>Login to Purchase
            </a>';
        }
    } else {
        $html .= '<button disabled class="w-full bg-gray-300 text-gray-500 py-3 px-4 rounded-full cursor-not-allowed">
            <i class="fas fa-times mr-2"></i>Out of Stock
        </button>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate a stats card
 */
function statsCard(string $title, string $value, string $icon, string $color = 'blue'): string {
    $colors = [
        'blue' => ['bg' => 'from-blue-500 to-blue-600', 'icon' => 'bg-blue-100 text-blue-600'],
        'green' => ['bg' => 'from-green-500 to-green-600', 'icon' => 'bg-green-100 text-green-600'],
        'purple' => ['bg' => 'from-purple-500 to-purple-600', 'icon' => 'bg-purple-100 text-purple-600'],
        'orange' => ['bg' => 'from-orange-500 to-orange-600', 'icon' => 'bg-orange-100 text-orange-600'],
        'gold' => ['bg' => 'from-yellow-500 to-yellow-600', 'icon' => 'bg-yellow-100 text-yellow-600']
    ];
    
    $colorScheme = $colors[$color] ?? $colors['blue'];
    
    return '<div class="bg-gradient-to-br ' . $colorScheme['bg'] . ' text-white rounded-2xl p-6 shadow-xl transform hover:-translate-y-1 transition-all duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="' . $colorScheme['icon'] . ' w-16 h-16 rounded-2xl flex items-center justify-center">
                <i class="' . e($icon) . ' text-3xl"></i>
            </div>
        </div>
        <h3 class="text-4xl font-bold mb-2">' . e($value) . '</h3>
        <p class="text-white/80 font-medium">' . e($title) . '</p>
    </div>';
}

/**
 * Generate loading spinner
 */
function loadingSpinner(string $size = 'md'): string {
    $sizes = [
        'sm' => 'w-4 h-4',
        'md' => 'w-8 h-8',
        'lg' => 'w-12 h-12',
        'xl' => 'w-16 h-16'
    ];
    
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    
    return '<div class="' . $sizeClass . ' border-4 border-luxury-accent border-t-transparent rounded-full animate-spin"></div>';
}

/**
 * Generate a modern footer
 */
function modernFooter(): string {
    $year = date('Y');
    
    return '<footer class="bg-gradient-to-br from-luxury-primary via-gray-900 to-luxury-primary text-white mt-20">
        <!-- Main Footer -->
        <div class="container mx-auto px-6 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
                <!-- Brand Column -->
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-luxury-accent to-yellow-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-spa text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-luxury font-bold">Bloom & Vine</h3>
                            <p class="text-xs text-luxury-accentLight">Premium Flowers</p>
                        </div>
                    </div>
                    <p class="text-gray-300 mb-6 leading-relaxed">
                        Experience the beauty of nature with our carefully curated collection of premium flowers and arrangements.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-white/10 hover:bg-luxury-accent rounded-full flex items-center justify-center transition-all duration-300">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 hover:bg-luxury-accent rounded-full flex items-center justify-center transition-all duration-300">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 hover:bg-luxury-accent rounded-full flex items-center justify-center transition-all duration-300">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/10 hover:bg-luxury-accent rounded-full flex items-center justify-center transition-all duration-300">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h4 class="text-lg font-bold mb-6 text-luxury-accent">Quick Links</h4>
                    <ul class="space-y-3">
                        <li><a href="index.php" class="text-gray-300 hover:text-luxury-accent transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Home</a></li>
                        <li><a href="shop.php" class="text-gray-300 hover:text-luxury-accent transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Shop</a></li>
                        <li><a href="account.php" class="text-gray-300 hover:text-luxury-accent transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>My Account</a></li>
                        <li><a href="wishlist.php" class="text-gray-300 hover:text-luxury-accent transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Wishlist</a></li>
                    </ul>
                </div>
                
                <!-- Customer Service -->
                <div>
                    <h4 class="text-lg font-bold mb-6 text-luxury-accent">Customer Service</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-300 hover:text-luxury-accent transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>About Us</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-luxury-accent transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Contact Us</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-luxury-accent transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Shipping Info</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-luxury-accent transition-colors flex items-center"><i class="fas fa-chevron-right text-xs mr-2"></i>Returns</a></li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <h4 class="text-lg font-bold mb-6 text-luxury-accent">Contact Us</h4>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <i class="fas fa-phone text-luxury-accent mt-1 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-400">Phone</p>
                                <p class="text-white">+964 750 123 4567</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-envelope text-luxury-accent mt-1 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-400">Email</p>
                                <p class="text-white">info@bloomandvine.com</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt text-luxury-accent mt-1 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-400">Address</p>
                                <p class="text-white">Kurdistan Region, Iraq</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Bottom Bar -->
        <div class="border-t border-white/10">
            <div class="container mx-auto px-6 py-6">
                <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                    <p class="text-gray-400 text-sm">
                        © ' . $year . ' Bloom & Vine. All rights reserved.
                    </p>
                    <div class="flex items-center space-x-6 text-sm">
                        <a href="#" class="text-gray-400 hover:text-luxury-accent transition-colors">Privacy Policy</a>
                        <a href="#" class="text-gray-400 hover:text-luxury-accent transition-colors">Terms of Service</a>
                        <a href="#" class="text-gray-400 hover:text-luxury-accent transition-colors">Cookie Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>';
}

