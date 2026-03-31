<?php
declare(strict_types=1);

/**
 * Login Page
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';
require_once __DIR__ . '/src/components.php';

$redirectTarget = safeRedirectTarget((string)($_GET['redirect'] ?? ''), 'index.php');
$googleLoginUrl = url('google_login.php') . '?redirect=' . urlencode($redirectTarget);

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: {$redirectTarget}");
    exit;
}

$error = '';
$email = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput('email', 'POST');
    $password = sanitizeInput('password', 'POST');
    $csrfToken = sanitizeInput('csrf_token', 'POST');
    
    // Verify CSRF token
    if (!verifyCSRFToken($csrfToken)) {
        $error = t('login_error');
    } else {
        if (empty($email) || empty($password)) {
            $error = t('login_error');
        } else {
            try {
                $pdo = getDB();
                $stmt = $pdo->prepare('SELECT id, email, password_hash, role, full_name FROM users WHERE email = :email LIMIT 1');
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $passwordVerifyResult = password_verify($password, $user['password_hash']);
                }
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    // Redirect super admin to super admin dashboard
                    if ($user['role'] === 'super_admin') {
                        $redirect = safeRedirectTarget((string)($_GET['redirect'] ?? ''), 'admin/super_admin_dashboard.php');
                    } else {
                        $redirect = safeRedirectTarget((string)($_GET['redirect'] ?? ''), 'index.php');
                    }
                    
                    logActivity('user_login', 'user', $user['id'], "User logged in: {$user['email']}");
                    redirect($redirect, t('login_success'), 'success');
                } else {
                    $error = t('login_error');
                }
            } catch (PDOException $e) {
                error_log('Login error: ' . $e->getMessage());
                $error = t('login_error');
            }
        }
    }
}

$csrfToken = generateCSRFToken();
$lang = getCurrentLang();
$dir = getHtmlDir();
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t('login_title')) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen flex items-center justify-center" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <div class="w-full max-w-md p-8">
    <?php include __DIR__ . '/src/pwa_head.php'; ?>
        <div class="bg-white border border-luxury-border shadow-luxury p-10">
            <h1 class="text-4xl font-luxury font-bold text-luxury-primary mb-8 text-center tracking-wide"><?= e(t('login_title')) ?></h1>
            
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-sm mb-6">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                
                <div>
                    <label for="email" class="block text-sm font-medium text-luxury-text mb-2"><?= e(t('email')) ?></label>
                    <input type="email" id="email" name="email" required 
                           value="<?= e($email) ?>"
                           class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-luxury-text mb-2"><?= e(t('password')) ?></label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                </div>
                
                <button type="submit" 
                        class="w-full bg-luxury-primary text-white py-3 px-4 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                    <?= e(t('login_button')) ?>
                </button>
            </form>

            <div class="flex items-center gap-3 my-6">
                <div class="flex-1 h-px bg-luxury-border"></div>
                <span class="text-xs uppercase tracking-wide text-luxury-textLight"><?= e(t('or')) ?></span>
                <div class="flex-1 h-px bg-luxury-border"></div>
            </div>

            <a href="<?= e($googleLoginUrl) ?>" class="w-full flex items-center justify-center gap-2 bg-white border border-gray-300 text-gray-700 py-3 px-4 rounded hover:bg-gray-50 hover:shadow-md transition-all duration-200 font-medium" style="box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);">
                <svg class="w-5 h-5" style="color: #4285F4;" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="google-gradient" x1="0%" y1="0%">
                            <stop offset="0%" style="stop-color:#4285F4;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#34A853;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                    <path d="M23.745 12.27c0-.79-.07-1.54-.2-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v3h3.86c2.26-2.09 3.56-5.17 3.56-8.82z" fill="#4285F4"/>
                    <path d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.13-4.07 1.13-3.13 0-5.78-2.11-6.73-4.96h-3.98v3.099C3.05 21.3 7.31 24 12 24z" fill="#34A853"/>
                    <path d="M5.27 14.26c-.23-.72-.35-1.49-.35-2.26s.13-1.54.35-2.26V7.07H1.29C.47 8.55 0 10.22 0 12s.47 3.45 1.29 4.93l3.98-2.67z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.92 1.88 16.24 0.5 12 0.5c-4.69 0-8.95 2.7-10.71 6.57l3.98 2.67c.95-2.85 3.6-4.96 6.73-4.96z" fill="#EA4335"/>
                </svg>
                <?= e(t('continue_with_google')) ?>
            </a>
            
            <p class="mt-6 text-center text-luxury-textLight">
                <?= e(t('dont_have_account')) ?> 
                <a href="register.php" class="text-luxury-accent hover:text-luxury-primary transition-colors font-medium"><?= e(t('nav_register')) ?></a>
            </p>
            
            <div class="mt-4 text-center">
                <a href="index.php" class="text-luxury-accent hover:text-luxury-primary transition-colors font-medium"><?= e(t('nav_home')) ?></a>
            </div>
        </div>
    </div>
</body>
</html>

