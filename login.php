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

// Redirect if already logged in
if (isLoggedIn()) {
    $redirect = $_GET['redirect'] ?? 'index.php';
    header("Location: {$redirect}");
    exit;
}

$error = '';
$email = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput('email', 'POST');
    $password = sanitizeInput('password', 'POST');
    $csrfToken = sanitizeInput('csrf_token', 'POST');
    
    // #region agent log
    file_put_contents('f:\XAMPP\htdocs\flower-store\.cursor\debug.log', json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A','location'=>'login.php:26','message'=>'Login attempt started','data'=>['email'=>$email,'hasPassword'=>!empty($password),'hasCsrf'=>!empty($csrfToken)],'timestamp'=>time()*1000])."\n", FILE_APPEND);
    // #endregion
    
    // Verify CSRF token
    if (!verifyCSRFToken($csrfToken)) {
        // #region agent log
        file_put_contents('f:\XAMPP\htdocs\flower-store\.cursor\debug.log', json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A','location'=>'login.php:32','message'=>'CSRF token verification failed','data'=>[],'timestamp'=>time()*1000])."\n", FILE_APPEND);
        // #endregion
        $error = t('login_error');
    } else {
        if (empty($email) || empty($password)) {
            // #region agent log
            file_put_contents('f:\XAMPP\htdocs\flower-store\.cursor\debug.log', json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A','location'=>'login.php:35','message'=>'Email or password empty','data'=>['emailEmpty'=>empty($email),'passwordEmpty'=>empty($password)],'timestamp'=>time()*1000])."\n", FILE_APPEND);
            // #endregion
            $error = t('login_error');
        } else {
            try {
                $pdo = getDB();
                $stmt = $pdo->prepare('SELECT id, email, password_hash, role, full_name FROM users WHERE email = :email LIMIT 1');
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();
                
                // #region agent log
                file_put_contents('f:\XAMPP\htdocs\flower-store\.cursor\debug.log', json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'B','location'=>'login.php:42','message'=>'User lookup result','data'=>['userFound'=>!empty($user),'userId'=>$user['id']??null,'userEmail'=>$user['email']??null,'userRole'=>$user['role']??null,'hasPasswordHash'=>!empty($user['password_hash']??null),'hashLength'=>strlen($user['password_hash']??'')],'timestamp'=>time()*1000])."\n", FILE_APPEND);
                // #endregion
                
                if ($user) {
                    $passwordVerifyResult = password_verify($password, $user['password_hash']);
                    // #region agent log
                    file_put_contents('f:\XAMPP\htdocs\flower-store\.cursor\debug.log', json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'C','location'=>'login.php:46','message'=>'Password verification result','data'=>['passwordVerify'=>$passwordVerifyResult,'hashPrefix'=>substr($user['password_hash'],0,10),'role'=>$user['role']],'timestamp'=>time()*1000])."\n", FILE_APPEND);
                    // #endregion
                }
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    // #region agent log
                    file_put_contents('f:\XAMPP\htdocs\flower-store\.cursor\debug.log', json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D','location'=>'login.php:52','message'=>'Login successful, setting session','data'=>['userId'=>$user['id'],'role'=>$user['role'],'isSuperAdmin'=>$user['role']==='super_admin'],'timestamp'=>time()*1000])."\n", FILE_APPEND);
                    // #endregion
                    
                    // Redirect super admin to super admin dashboard
                    if ($user['role'] === 'super_admin') {
                        $redirect = $_GET['redirect'] ?? 'admin/super_admin_dashboard.php';
                        // #region agent log
                        file_put_contents('f:\XAMPP\htdocs\flower-store\.cursor\debug.log', json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D','location'=>'login.php:55','message'=>'Super admin redirect','data'=>['redirect'=>$redirect],'timestamp'=>time()*1000])."\n", FILE_APPEND);
                        // #endregion
                    } else {
                        $redirect = $_GET['redirect'] ?? 'index.php';
                    }
                    
                    logActivity('user_login', 'user', $user['id'], "User logged in: {$user['email']}");
                    redirect($redirect, t('login_success'), 'success');
                } else {
                    // #region agent log
                    file_put_contents('f:\XAMPP\htdocs\flower-store\.cursor\debug.log', json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A,C','location'=>'login.php:62','message'=>'Login failed','data'=>['userFound'=>!empty($user),'passwordVerify'=>($user?password_verify($password,$user['password_hash']):false)],'timestamp'=>time()*1000])."\n", FILE_APPEND);
                    // #endregion
                    $error = t('login_error');
                }
            } catch (PDOException $e) {
                // #region agent log
                file_put_contents('f:\XAMPP\htdocs\flower-store\.cursor\debug.log', json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'E','location'=>'login.php:65','message'=>'Database error','data'=>['error'=>$e->getMessage(),'code'=>$e->getCode()],'timestamp'=>time()*1000])."\n", FILE_APPEND);
                // #endregion
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

