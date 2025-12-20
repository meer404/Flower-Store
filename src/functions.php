<?php
declare(strict_types=1);

/**
 * Helper Functions
 * Bloom & Vine Flower Store
 * 
 * This file contains utility functions for sanitization, language handling,
 * admin privilege checks, and other common operations.
 */

require_once __DIR__ . '/config/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitize output to prevent XSS attacks
 * 
 * @param string|null $string The string to sanitize
 * @param int $flags Optional flags for htmlspecialchars
 * @return string Sanitized string
 */
function e(?string $string, int $flags = ENT_QUOTES | ENT_HTML5): string {
    if ($string === null) {
        return '';
    }
    return htmlspecialchars($string, $flags, 'UTF-8');
}

/**
 * Sanitize input from POST/GET requests
 * 
 * @param string $key The key to retrieve from $_POST or $_GET
 * @param string $method 'POST' or 'GET'
 * @param string $default Default value if key doesn't exist
 * @return string Sanitized input value
 */
function sanitizeInput(string $key, string $method = 'POST', string $default = ''): string {
    $source = ($method === 'POST') ? $_POST : $_GET;
    
    if (!isset($source[$key])) {
        return $default;
    }
    
    return trim(strip_tags($source[$key]));
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['email']);
}

/**
 * Check if current user is an admin
 * 
 * @return bool True if user is admin
 */
function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Require admin access - redirects to login if not admin
 * 
 * @return void
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Require login - redirects to login if not logged in
 * 
 * @return void
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Get current language from session, default to 'en'
 * 
 * @return string Current language code ('en' or 'ku')
 */
function getCurrentLang(): string {
    return $_SESSION['lang'] ?? 'en';
}

/**
 * Check if current language is Kurdish (RTL)
 * 
 * @return bool True if language is Kurdish
 */
function isRTL(): bool {
    return getCurrentLang() === 'ku';
}

/**
 * Get HTML direction attribute based on current language
 * 
 * @return string 'ltr' or 'rtl'
 */
function getHtmlDir(): string {
    return isRTL() ? 'rtl' : 'ltr';
}

/**
 * Get HTML lang attribute based on current language
 * 
 * @return string 'en' or 'ku'
 */
function getLang(): string {
    return getCurrentLang();
}

/**
 * Load translation array based on current language
 * 
 * @return array Translation array
 */
function loadTranslations(): array {
    $lang = getCurrentLang();
    $translationFile = __DIR__ . "/translations/{$lang}.php";
    
    if (file_exists($translationFile)) {
        return require $translationFile;
    }
    
    // Fallback to English if translation file doesn't exist
    return require __DIR__ . '/translations/en.php';
}

/**
 * Translate a key to current language
 * 
 * @param string $key Translation key
 * @param array $replacements Optional array of replacements for placeholders
 * @return string Translated string
 */
function t(string $key, array $replacements = []): string {
    static $translations = null;
    
    if ($translations === null) {
        $translations = loadTranslations();
    }
    
    $text = $translations[$key] ?? $key;
    
    // Replace placeholders if provided
    if (!empty($replacements)) {
        foreach ($replacements as $placeholder => $value) {
            $text = str_replace(':' . $placeholder, e($value), $text);
        }
    }
    
    return $text;
}

/**
 * Generate CSRF token and store in session
 * 
 * @return string CSRF token
 */
function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if token is valid
 */
function verifyCSRFToken(string $token): bool {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format price with currency symbol
 * 
 * @param float $price Price to format
 * @param string $currency Currency symbol (default: $)
 * @return string Formatted price string
 */
function formatPrice(float $price, string $currency = '$'): string {
    return $currency . number_format($price, 2);
}

/**
 * Get product name in current language
 * 
 * @param array $product Product array with name_en and name_ku
 * @return string Product name in current language
 */
function getProductName(array $product): string {
    $lang = getCurrentLang();
    return $lang === 'ku' ? $product['name_ku'] : $product['name_en'];
}

/**
 * Get product description in current language
 * 
 * @param array $product Product array with description_en and description_ku
 * @return string Product description in current language
 */
function getProductDescription(array $product): string {
    $lang = getCurrentLang();
    return $lang === 'ku' ? $product['description_ku'] : $product['description_en'];
}

/**
 * Get category name in current language
 * 
 * @param array $category Category array with name_en and name_ku
 * @return string Category name in current language
 */
function getCategoryName(array $category): string {
    $lang = getCurrentLang();
    return $lang === 'ku' ? $category['name_ku'] : $category['name_en'];
}

/**
 * Get cart total count
 * 
 * @return int Total number of items in cart
 */
function getCartCount(): int {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    
    return array_sum($_SESSION['cart']);
}

/**
 * Get cart total price
 * 
 * @return float Total price of items in cart
 */
function getCartTotal(): float {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0.0;
    }
    
    $total = 0.0;
    $pdo = getDB();
    
    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $stmt = $pdo->prepare('SELECT price FROM products WHERE id = :id');
        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch();
        
        if ($product) {
            $total += (float)$product['price'] * (int)$quantity;
        }
    }
    
    return $total;
}

/**
 * Redirect with a message
 * 
 * @param string $url URL to redirect to
 * @param string $message Optional message to store in session
 * @param string $type Message type: 'success', 'error', 'info'
 * @return void
 */
function redirect(string $url, string $message = '', string $type = 'info'): void {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: {$url}");
    exit;
}

/**
 * Get and clear flash message
 * 
 * @return array|null Array with 'message' and 'type' keys, or null
 */
function getFlashMessage(): ?array {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

