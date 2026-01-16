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
 * Generate a URL relative to the current script execution context.
 * Useful for handling links when files are in different directories (e.g., /admin/ vs /).
 * 
 * @param string $path The target path from the project root (e.g., 'admin/dashboard.php' or 'index.php')
 * @return string The resolved relative URL
 */
function url(string $path): string {
    $path = ltrim($path, '/');
    
    // Check if we are currently inside the 'admin' directory
    // We check for /admin/ in the script name
    $currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
    $inAdmin = strpos($currentScript, '/admin/') !== false;
    
    // Check if the target path is pointing to the admin directory
    $targetInAdmin = strpos($path, 'admin/') === 0;
    
    if ($inAdmin) {
        // We are inside admin/
        if ($targetInAdmin) {
            // Target is also in admin/ (e.g. admin/users.php)
            // We want to link to "users.php", so strip "admin/" prefix
            return substr($path, 6); // length of 'admin/' is 6
        } else {
            // Target is in root (e.g. index.php)
            // We need to go up one level
            return '../' . $path;
        }
    } else {
        // We are in root (or src/)
        // Target is relative to root, so return as is
        return $path;
    }
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
        
        // Strict validation for period to prevent SQL injection
        $allowedPeriods = ['day', 'week', 'month', 'year'];
        if (!in_array($period, $allowedPeriods)) {
            $period = 'month';
        }
        
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

/**
 * Format time as "ago"
 * 
 * @param string|int|null $timestamp Timestamp string or integer
 * @return string Formatted time string
 */
function time_ago($timestamp): string {
    if (!$timestamp) {
        return '';
    }
    
    if (is_numeric($timestamp)) {
        $timestamp = (int)$timestamp;
    } else {
        $timestamp = strtotime($timestamp);
    }
    
    $current_time = time();
    $time_difference = $current_time - $timestamp;
    $seconds = $time_difference;
    $minutes = round($seconds / 60);           // value 60 is seconds
    $hours   = round($seconds / 3600);         // value 3600 is 60 minutes * 60 sec
    $days    = round($seconds / 86400);        // value 86400 is 24 hours * 60 minutes * 60 sec
    $weeks   = round($seconds / 604800);       // value 604800 is 7 days * 24 hours * 60 minutes * 60 sec
    $months  = round($seconds / 2629440);      // value 2629440 is ((365+365+365+365+366)/5/12) days * 24 hours * 60 minutes * 60 sec
    $years   = round($seconds / 31553280);     // value 31553280 is ((365+365+365+365+366)/5) days * 24 hours * 60 minutes * 60 sec

    if ($seconds <= 60) {
        return t('just_now');
    } else if ($minutes <= 60) {
        return $minutes == 1 ? t('one_minute_ago') : t('minutes_ago', ['count' => $minutes]);
    } else if ($hours <= 24) {
        return $hours == 1 ? t('an_hour_ago') : t('hours_ago', ['count' => $hours]);
    } else if ($days <= 7) {
        return $days == 1 ? t('yesterday') : t('days_ago', ['count' => $days]);
    } else if ($weeks <= 4.3) {
        return $weeks == 1 ? t('a_week_ago') : t('weeks_ago', ['count' => $weeks]);
    } else if ($months <= 12) {
        return $months == 1 ? t('a_month_ago') : t('months_ago', ['count' => $months]);
    } else {
        return $years == 1 ? t('one_year_ago') : t('years_ago', ['count' => $years]);
    }
}

/**
 * Check if user has a specific permission
 * Super admins have all permissions automatically
 * 
 * @param string $permission Permission to check
 * @param int|null $userId User ID (uses current user if null)
 * @return bool True if user has permission
 */
function hasPermission(string $permission, ?int $userId = null): bool {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $userId = $userId ?? (int)$_SESSION['user_id'];
    $pdo = getDB();
    
    // Super admins have all permissions
    $userRole = $pdo->prepare('SELECT role FROM users WHERE id = :id');
    $userRole->execute(['id' => $userId]);
    $role = $userRole->fetchColumn();
    
    if ($role === 'super_admin') {
        return true;
    }
    
    if ($role !== 'admin') {
        return false;
    }
    
    // Check if admin has this specific permission
    try {
        $stmt = $pdo->prepare('SELECT id FROM admin_permissions WHERE admin_id = :admin_id AND permission = :permission');
        $stmt->execute(['admin_id' => $userId, 'permission' => $permission]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Table doesn't exist yet, deny access
        return false;
    }
}

/**
 * Get all permissions for an admin
 * 
 * @param int|null $adminId Admin ID (uses current user if null)
 * @return array Array of permission strings
 */
function getAdminPermissions(?int $adminId = null): array {
    if (!isset($_SESSION['user_id'])) {
        return [];
    }
    
    $adminId = $adminId ?? (int)$_SESSION['user_id'];
    $pdo = getDB();
    
    // Super admins have all permissions
    $userRole = $pdo->prepare('SELECT role FROM users WHERE id = :id');
    $userRole->execute(['id' => $adminId]);
    $role = $userRole->fetchColumn();
    
    if ($role === 'super_admin') {
        return [
            'view_dashboard',
            'manage_products',
            'manage_categories',
            'view_orders',
            'manage_orders',
            'view_reports',
            'manage_admins',
            'view_users',
            'manage_users',
            'system_settings'
        ];
    }
    
    if ($role !== 'admin') {
        return [];
    }
    
    try {
        $stmt = $pdo->prepare('SELECT permission FROM admin_permissions WHERE admin_id = :admin_id ORDER BY permission');
        $stmt->execute(['admin_id' => $adminId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        // Table doesn't exist yet, return empty permissions
        return [];
    }
}

/**
 * Assign permissions to an admin
 * 
 * @param int $adminId Admin ID
 * @param array $permissions Array of permission strings
 * @return bool True on success
 */
function setAdminPermissions(int $adminId, array $permissions): bool {
    $pdo = getDB();
    
    try {
        // Remove existing permissions
        $stmt = $pdo->prepare('DELETE FROM admin_permissions WHERE admin_id = :admin_id');
        $stmt->execute(['admin_id' => $adminId]);
        
        // Add new permissions
        $stmt = $pdo->prepare('INSERT INTO admin_permissions (admin_id, permission) VALUES (:admin_id, :permission)');
        
        foreach ($permissions as $permission) {
            $stmt->execute(['admin_id' => $adminId, 'permission' => $permission]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log('Error setting admin permissions: ' . $e->getMessage());
        // If table doesn't exist, silently fail but allow admin creation
        if (strpos($e->getMessage(), 'admin_permissions') !== false) {
            return true; // Allow operation even if table missing
        }
        return false;
    }
}

/**
 * Require specific permission or deny access
 * 
 * @param string $permission Permission to require
 * @return void Exits with 403 if no permission
 */
function requirePermission(string $permission): void {
    if (!hasPermission($permission)) {
        http_response_code(403);
        die('Access denied. You do not have permission to access this resource.');
    }
}

/**
 * Get available permissions list
 * 
 * @return array Array with permission keys and descriptions
 */
function getAvailablePermissions(): array {
    return [
        'view_dashboard' => 'View Dashboard',
        'manage_products' => 'Add, Edit & Delete Products',
        'manage_categories' => 'Manage Categories',
        'view_orders' => 'View Orders & Details',
        'manage_orders' => 'Update Order Status',
        'view_reports' => 'Access Reports',
        'view_users' => 'View Customer Users',
        'manage_users' => 'Ban/Modify Customer Accounts',
        'system_settings' => 'System Settings'
    ];
}


