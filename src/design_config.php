<?php
declare(strict_types=1);

/**
 * Luxury Design Configuration
 * Bloom & Vine Flower Store
 * 
 * Centralized design configuration for consistent luxury styling
 */

// Luxury Color Palette
define('COLOR_PRIMARY', '#1a1a1a');      // Deep charcoal/black
define('COLOR_ACCENT', '#d4af37');       // Luxury gold
define('COLOR_ACCENT_LIGHT', '#f5e6d3'); // Light gold/cream
define('COLOR_BACKGROUND', '#ffffff');    // Pure white
define('COLOR_TEXT', '#2d2d2d');          // Dark gray
define('COLOR_TEXT_LIGHT', '#6b7280');    // Light gray
define('COLOR_BORDER', '#e5e7eb');        // Subtle border gray

/**
 * Get Tailwind config script for luxury design
 */
function getLuxuryTailwindConfig(): string {
    return '
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        luxury: {
                            primary: "' . COLOR_PRIMARY . '",
                            accent: "' . COLOR_ACCENT . '",
                            accentLight: "' . COLOR_ACCENT_LIGHT . '",
                            text: "' . COLOR_TEXT . '",
                            textLight: "' . COLOR_TEXT_LIGHT . '",
                            border: "' . COLOR_BORDER . '"
                        }
                    },
                    fontFamily: {
                        luxury: ["\'Playfair Display\'", "\'Cormorant Garamond\'", "serif"],
                        sans: ["\'Inter\'", "\'Segoe UI\'", "sans-serif"]
                    },
                    boxShadow: {
                        luxury: "0 4px 20px rgba(0, 0, 0, 0.08)",
                        luxuryHover: "0 8px 30px rgba(0, 0, 0, 0.12)",
                        "luxury-xl": "0 20px 50px rgba(0, 0, 0, 0.15)"
                    },
                    animation: {
                        "fade-in": "fadeIn 0.5s ease-in",
                        "slide-up": "slideUp 0.5s ease-out",
                        "scale-in": "scaleIn 0.3s ease-out",
                        "float": "float 3s ease-in-out infinite"
                    },
                    keyframes: {
                        fadeIn: {
                            "0%": { opacity: "0" },
                            "100%": { opacity: "1" }
                        },
                        slideUp: {
                            "0%": { transform: "translateY(20px)", opacity: "0" },
                            "100%": { transform: "translateY(0)", opacity: "1" }
                        },
                        scaleIn: {
                            "0%": { transform: "scale(0.9)", opacity: "0" },
                            "100%": { transform: "scale(1)", opacity: "1" }
                        },
                        float: {
                            "0%, 100%": { transform: "translateY(0px)" },
                            "50%": { transform: "translateY(-10px)" }
                        }
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: ' . COLOR_ACCENT . ';
            border-radius: 5px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: ' . COLOR_PRIMARY . ';
        }
        
        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }
        
        /* Line clamp utility */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, ' . COLOR_PRIMARY . ', ' . COLOR_ACCENT . ');
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Glass morphism effect */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>';
}

/**
 * Get luxury navbar HTML
 */
function getLuxuryNavbar(string $currentPage = ''): string {
    $lang = getCurrentLang();
    $nav = '<nav class="bg-white border-b border-luxury-border shadow-sm">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center gap-4">
                <a href="index.php" class="text-2xl font-luxury font-bold text-luxury-primary tracking-wide">Bloom & Vine</a>
                <div class="flex items-center gap-8">
                    <a href="index.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium">' . e(t('nav_home')) . '</a>
                    <a href="shop.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium">' . e(t('nav_shop')) . '</a>';
    
    if (isLoggedIn()) {
        if (isAdmin()) {
            $nav .= '<a href="admin/dashboard.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium">' . e(t('nav_admin')) . '</a>';
        }
        $nav .= '<a href="account.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium">' . e('Account') . '</a>
                 <a href="cart.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium">
                     ' . e(t('nav_cart')) . ' <span class="bg-luxury-accent text-white px-2 py-0.5 rounded-full text-xs">' . e((string)getCartCount()) . '</span>
                 </a>
                 <a href="logout.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium">' . e(t('nav_logout')) . '</a>';
    } else {
        $nav .= '<a href="login.php" class="text-luxury-text hover:text-luxury-accent transition-colors font-medium">' . e(t('nav_login')) . '</a>
                 <a href="register.php" class="bg-luxury-primary text-white px-6 py-2 rounded-sm hover:bg-opacity-90 transition-all font-medium">' . e(t('nav_register')) . '</a>';
    }
    
    $nav .= '<a href="?lang=' . ($lang === 'en' ? 'ku' : 'en') . '" class="text-luxury-accent hover:text-luxury-primary font-semibold border border-luxury-accent px-3 py-1 rounded-sm">
                 ' . ($lang === 'en' ? 'KU' : 'EN') . '
             </a>
         </div>
     </div>
 </nav>';
    
    return $nav;
}

/**
 * Get luxury footer HTML
 */
function getLuxuryFooter(): string {
    return '<footer class="bg-luxury-primary text-white py-12 mt-20 border-t border-luxury-border">
        <div class="container mx-auto px-6 text-center">
            <p class="text-luxury-accentLight font-light tracking-wide">&copy; ' . e(date('Y')) . ' Bloom & Vine. ' . e('All rights reserved.') . '</p>
        </div>
    </footer>';
}

