<?php
declare(strict_types=1);

ob_start();

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/config/google_oauth.php';
require_once __DIR__ . '/vendor/autoload.php';

$expectedState = $_SESSION['google_oauth_state'] ?? '';
$receivedState = sanitizeInput('state', 'GET');

// CSRF protection using the OAuth state value.
if ($expectedState === '' || $receivedState === '' || !hash_equals($expectedState, $receivedState)) {
    unset($_SESSION['google_oauth_state']);
    error_log('Google OAuth state mismatch or missing state.');
    redirect('login.php', t('google_login_failed'), 'error');
}

unset($_SESSION['google_oauth_state']);

$code = sanitizeInput('code', 'GET');
if ($code === '') {
    error_log('Google OAuth missing authorization code.');
    redirect('login.php', t('google_login_failed'), 'error');
}

$config = loadGoogleOAuthConfig();

if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['redirect_uri'])) {
    error_log('Google OAuth config missing required values during callback.');
    redirect('login.php', t('google_login_failed'), 'error');
}

$client = new Google\Client();
$client->setClientId($config['client_id']);
$client->setClientSecret($config['client_secret']);
$client->setRedirectUri($config['redirect_uri']);
$client->setScopes(['email', 'profile']);

// Use a reliable CA bundle so SSL verification works on local dev (XAMPP/Windows)
// and on production without extra server config.
if (class_exists('\Composer\CaBundle\CaBundle')) {
    $caBundle = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
    if ($caBundle) {
        $client->setHttpClient(new \GuzzleHttp\Client(['verify' => $caBundle]));
    }
}

try {
    $token = $client->fetchAccessTokenWithAuthCode($code);
} catch (\Exception $e) {
    error_log('Google OAuth token exchange exception: ' . $e->getMessage());
    redirect('login.php', t('google_login_failed'), 'error');
}

if (isset($token['error'])) {
    error_log('Google OAuth token error: ' . ($token['error_description'] ?? $token['error']));
    redirect('login.php', t('google_login_failed'), 'error');
}

$client->setAccessToken($token);

try {
    $idToken  = $token['id_token'] ?? null;
    $payload  = $idToken ? $client->verifyIdToken($idToken) : null;
} catch (\Exception $e) {
    error_log('Google OAuth ID token verification exception: ' . $e->getMessage());
    redirect('login.php', t('google_login_failed'), 'error');
}

if (!is_array($payload) || empty($payload['email']) || empty($payload['email_verified'])) {
    error_log('Google OAuth ID token verification failed.');
    redirect('login.php', t('google_login_failed'), 'error');
}

$email = (string)$payload['email'];

try {
    $oauthService = new Google\Service\Oauth2($client);
    $userInfo = $oauthService->userinfo->get();
} catch (Exception $e) {
    error_log('Google OAuth userinfo error: ' . $e->getMessage());
    redirect('login.php', t('google_login_failed'), 'error');
}

$fullName = trim((string)($userInfo->name ?? ''));
$picture = trim((string)($userInfo->picture ?? ''));

if ($fullName === '') {
    $fullName = preg_replace('/@.*/', '', $email) ?: 'Customer';
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id, email, role, full_name FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        $passwordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_ARGON2ID);
        $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (:full_name, :email, :password_hash, :role)');
        $stmt->execute([
            'full_name'     => $fullName,
            'email'         => $email,
            'password_hash' => $passwordHash,
            'role'          => 'customer',
        ]);
        $userId = (int)$pdo->lastInsertId();
        $user = [
            'id'        => $userId,
            'email'     => $email,
            'role'      => 'customer',
            'full_name' => $fullName,
        ];
    }

    $_SESSION['user_id']   = (int)$user['id'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    if ($picture !== '') {
        $_SESSION['avatar_url'] = $picture;
    }

    logActivity('user_login', 'user', (int)$user['id'], "User logged in with Google: {$email}");
} catch (PDOException $e) {
    error_log('Google login DB error: ' . $e->getMessage());
    redirect('login.php', t('google_login_failed'), 'error');
}

$redirectTarget = safeRedirectTarget((string)($_SESSION['oauth_redirect'] ?? ''), 'index.php');
unset($_SESSION['oauth_redirect']);

if (($_SESSION['role'] ?? '') === 'super_admin') {
    $redirectTarget = 'admin/super_admin_dashboard.php';
}

ob_end_clean();
redirect($redirectTarget, t('login_success'), 'success');
