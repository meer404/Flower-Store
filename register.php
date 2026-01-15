<?php
declare(strict_types=1);

/**
 * Registration Page
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/design_config.php';
require_once __DIR__ . '/src/components.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$fullName = '';
$email = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitizeInput('full_name', 'POST');
    $email = sanitizeInput('email', 'POST');
    $password = sanitizeInput('password', 'POST');
    $confirmPassword = sanitizeInput('confirm_password', 'POST');
    $csrfToken = sanitizeInput('csrf_token', 'POST');
    
    // Verify CSRF token
    if (!verifyCSRFToken($csrfToken)) {
        $error = t('register_error');
    } else {
        // Validation
        if (empty($fullName) || empty($email) || empty($password)) {
            $error = t('register_error');
        } elseif ($password !== $confirmPassword) {
            $error = t('register_error');
        } elseif (strlen($password) < 8) {
            $error = t('register_error');
        } else {
            try {
                $pdo = getDB();
                
                // Check if email already exists
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
                $stmt->execute(['email' => $email]);
                if ($stmt->fetch()) {
                    $error = t('register_error');
                } else {
                    // Hash password using ARGON2ID
                    $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
                    
                    // Insert new user
                    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (:full_name, :email, :password_hash, :role)');
                    $stmt->execute([
                        'full_name' => $fullName,
                        'email' => $email,
                        'password_hash' => $passwordHash,
                        'role' => 'customer'
                    ]);
                    
                    redirect('login.php', t('register_success'), 'success');
                }
            } catch (PDOException $e) {
                error_log('Registration error: ' . $e->getMessage());
                $error = t('register_error');
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
    <title><?= e(t('register_title')) ?> - Bloom & Vine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?= getLuxuryTailwindConfig() ?>
</head>
<body class="bg-white min-h-screen flex items-center justify-center px-4 py-8" style="font-family: 'Inter', 'Segoe UI', sans-serif;">
    <div class="w-full max-w-md">
        <div class="bg-white border border-luxury-border shadow-luxury p-6 md:p-10">
            <h1 class="text-3xl md:text-4xl font-luxury font-bold text-luxury-primary mb-6 md:mb-8 text-center tracking-wide"><?= e(t('register_title')) ?></h1>
            
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-sm mb-6">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-sm mb-6">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-5 md:space-y-6">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                
                <div>
                    <label for="full_name" class="block text-sm font-medium text-luxury-text mb-2"><?= e(t('full_name')) ?></label>
                    <input type="text" id="full_name" name="full_name" required 
                           value="<?= e($fullName) ?>"
                           class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-luxury-text mb-2"><?= e(t('email')) ?></label>
                    <input type="email" id="email" name="email" required 
                           value="<?= e($email) ?>"
                           class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-luxury-text mb-2"><?= e(t('password')) ?></label>
                    <input type="password" id="password" name="password" required
                           minlength="8"
                           class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                    <p class="text-xs text-luxury-textLight mt-1.5"><?= e(t('min_8_chars')) ?></p>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-luxury-text mb-2"><?= e(t('confirm_password')) ?></label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           minlength="8"
                           class="w-full px-4 py-2.5 border border-luxury-border rounded-sm focus:outline-none focus:ring-2 focus:ring-luxury-accent focus:border-luxury-accent">
                </div>
                
                <button type="submit" 
                        class="w-full bg-luxury-primary text-white py-3 px-4 rounded-sm hover:bg-opacity-90 transition-all duration-300 font-medium shadow-md">
                    <?= e(t('register_button')) ?>
                </button>
            </form>
            
            <p class="mt-6 text-center text-luxury-textLight">
                <?= e(t('already_have_account')) ?> 
                <a href="login.php" class="text-luxury-accent hover:text-luxury-primary transition-colors font-medium"><?= e(t('login_title')) ?></a>
            </p>
            
            <div class="mt-4 text-center">
                <a href="index.php" class="text-luxury-accent hover:text-luxury-primary transition-colors font-medium"><?= e(t('nav_home')) ?></a>
            </div>
        </div>
    </div>
</body>
</html>

