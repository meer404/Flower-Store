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
    return isLoggedIn() && isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin');
}

/**
 * Check if current user is a super admin
 * 
 * @return bool True if user is super admin
 */
function isSuperAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}

/**
 * Require super admin access - redirects to login if not super admin
 * 
 * @return void
 */
function requireSuperAdmin(): void {
    if (!isSuperAdmin()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
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
            $text = str_replace(':' . $placeholder, e((string)$value), $text);
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
 * Get wishlist count for the current user
 * 
 * @return int Number of items in wishlist
 */
function getWishlistCount(): int {
    if (!isLoggedIn()) {
        return 0;
    }
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM wishlist WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    } catch (PDOException $e) {
        error_log('Get wishlist count error: ' . $e->getMessage());
        return 0;
    }
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

/**
 * Create a notification for a user
 * 
 * @param int $userId User ID to notify
 * @param string $type Notification type (order_status, order_tracking, payment_status, general)
 * @param string $title Notification title
 * @param string $message Notification message
 * @param int|null $orderId Optional order ID if related to an order
 * @return bool True if notification created successfully
 */
function createNotification(int $userId, string $type, string $title, string $message, ?int $orderId = null): bool {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('INSERT INTO notifications (user_id, order_id, type, title, message) VALUES (:user_id, :order_id, :type, :title, :message)');
        $stmt->execute([
            'user_id' => $userId,
            'order_id' => $orderId,
            'type' => $type,
            'title' => $title,
            'message' => $message
        ]);
        return true;
    } catch (PDOException $e) {
        error_log('Notification creation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notification count for current user
 * 
 * @return int Number of unread notifications
 */
function getUnreadNotificationCount(): int {
    if (!isLoggedIn()) {
        return 0;
    }
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = FALSE');
        $stmt->execute(['user_id' => (int)$_SESSION['user_id']]);
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    } catch (PDOException $e) {
        error_log('Notification count failed: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get notifications for current user
 * 
 * @param int $limit Maximum number of notifications to retrieve
 * @param bool $unreadOnly If true, only return unread notifications
 * @return array Array of notification records
 */
function getNotifications(int $limit = 20, bool $unreadOnly = false): array {
    if (!isLoggedIn()) {
        return [];
    }
    
    try {
        $pdo = getDB();
        $sql = 'SELECT * FROM notifications WHERE user_id = :user_id';
        if ($unreadOnly) {
            $sql .= ' AND is_read = FALSE';
        }
        $sql .= ' ORDER BY created_at DESC LIMIT :limit';
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', (int)$_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Get notifications failed: ' . $e->getMessage());
        return [];
    }
}

/**
 * Mark notification as read
 * 
 * @param int $notificationId Notification ID to mark as read
 * @return bool True if successful
 */
function markNotificationAsRead(int $notificationId): bool {
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = TRUE WHERE id = :id AND user_id = :user_id');
        $stmt->execute([
            'id' => $notificationId,
            'user_id' => (int)$_SESSION['user_id']
        ]);
        return true;
    } catch (PDOException $e) {
        error_log('Mark notification as read failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read for current user
 * 
 * @return bool True if successful
 */
function markAllNotificationsAsRead(): bool {
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = TRUE WHERE user_id = :user_id AND is_read = FALSE');
        $stmt->execute(['user_id' => (int)$_SESSION['user_id']]);
        return true;
    } catch (PDOException $e) {
        error_log('Mark all notifications as read failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Log activity for super admin tracking
 * 
 * @param string $action Action performed
 * @param string|null $entityType Type of entity (e.g., 'user', 'product', 'order')
 * @param int|null $entityId ID of the entity
 * @param string|null $description Additional description
 * @return bool True if logged successfully
 */
function logActivity(string $action, ?string $entityType = null, ?int $entityId = null, ?string $description = null): bool {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('
            INSERT INTO activity_log (user_id, action, entity_type, entity_id, description, ip_address, user_agent)
            VALUES (:user_id, :action, :entity_type, :entity_id, :description, :ip_address, :user_agent)
        ');
        $stmt->execute([
            'user_id' => isLoggedIn() ? (int)$_SESSION['user_id'] : null,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        return true;
    } catch (PDOException $e) {
        error_log('Activity logging failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get system setting value
 * 
 * @param string $key Setting key
 * @param mixed $default Default value if setting doesn't exist
 * @return mixed Setting value
 */
function getSystemSetting(string $key, $default = null) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT setting_value, setting_type FROM system_settings WHERE setting_key = :key');
        $stmt->execute(['key' => $key]);
        $setting = $stmt->fetch();
        
        if (!$setting) {
            return $default;
        }
        
        $value = $setting['setting_value'];
        $type = $setting['setting_type'];
        
        switch ($type) {
            case 'number':
                return is_numeric($value) ? (float)$value : $default;
            case 'boolean':
                return (bool)$value;
            case 'json':
                return json_decode($value, true) ?? $default;
            default:
                return $value ?? $default;
        }
    } catch (PDOException $e) {
        error_log('Get system setting failed: ' . $e->getMessage());
        return $default;
    }
}

/**
 * Set system setting value
 * 
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @param string $type Setting type (string, number, boolean, json)
 * @return bool True if successful
 */
function setSystemSetting(string $key, $value, string $type = 'string'): bool {
    try {
        $pdo = getDB();
        
        if ($type === 'json' && is_array($value)) {
            $value = json_encode($value);
        } elseif ($type === 'boolean') {
            $value = $value ? '1' : '0';
        } else {
            $value = (string)$value;
        }
        
        $stmt = $pdo->prepare('
            INSERT INTO system_settings (setting_key, setting_value, setting_type, updated_by)
            VALUES (:key, :value, :type, :updated_by)
            ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                setting_type = VALUES(setting_type),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP
        ');
        $stmt->execute([
            'key' => $key,
            'value' => $value,
            'type' => $type,
            'updated_by' => isLoggedIn() ? (int)$_SESSION['user_id'] : null
        ]);
        
        logActivity('system_setting_updated', 'system_setting', null, "Updated setting: {$key}");
        return true;
    } catch (PDOException $e) {
        error_log('Set system setting failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get sales report data
 * 
 * @param string $period 'day', 'week', 'month', or 'year'
 * @return array Report data
 */
function getSalesReport(string $period = 'month'): array {
    try {
        $pdo = getDB();
        
        $dateFormat = '%Y-%m';
        switch ($period) {
            case 'day':
                $dateFormat = '%Y-%m-%d';
                break;
            case 'week':
                $dateFormat = '%Y-%u';
                break;
            case 'month':
                $dateFormat = '%Y-%m';
                break;
            case 'year':
                $dateFormat = '%Y';
                break;
        }
        
        // Get sales by period
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(order_date, :date_format) as period,
                COUNT(*) as total_orders,
                SUM(grand_total) as total_revenue,
                SUM(CASE WHEN payment_status = 'paid' THEN grand_total ELSE 0 END) as paid_revenue,
                AVG(grand_total) as avg_order_value
            FROM orders
            WHERE order_date >= DATE_SUB(NOW(), INTERVAL 1 {$period})
            GROUP BY period
            ORDER BY period DESC
        ");
        $stmt->execute(['date_format' => $dateFormat]);
        $salesData = $stmt->fetchAll();
        
        // Get top products
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.name_en,
                p.name_ku,
                SUM(oi.quantity) as total_sold,
                SUM(oi.quantity * oi.unit_price) as total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL 1 {$period})
            AND o.payment_status = 'paid'
            GROUP BY p.id, p.name_en, p.name_ku
            ORDER BY total_sold DESC
            LIMIT 10
        ");
        $stmt->execute();
        $topProducts = $stmt->fetchAll();
        
        // Get customer stats
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT user_id) as total_customers,
                COUNT(DISTINCT CASE WHEN order_date >= DATE_SUB(NOW(), INTERVAL 1 {$period}) THEN user_id END) as new_customers
            FROM orders
            WHERE order_date >= DATE_SUB(NOW(), INTERVAL 1 {$period})
        ");
        $stmt->execute();
        $customerStats = $stmt->fetch();
        
        return [
            'sales_data' => $salesData,
            'top_products' => $topProducts,
            'customer_stats' => $customerStats,
            'period' => $period
        ];
    } catch (PDOException $e) {
        error_log('Get sales report failed: ' . $e->getMessage());
        return [
            'sales_data' => [],
            'top_products' => [],
            'customer_stats' => ['total_customers' => 0, 'new_customers' => 0],
            'period' => $period
        ];
    }
}

