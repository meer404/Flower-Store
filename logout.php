<?php
declare(strict_types=1);

/**
 * Logout Handler
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	redirect('index.php', t('error'), 'error');
}

$csrfToken = sanitizeInput('csrf_token', 'POST');
if (!verifyCSRFToken($csrfToken)) {
	redirect('index.php', t('error'), 'error');
}

// Destroy session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

// Start new session for flash message
session_start();
redirect('index.php', t('login_title') . ' - ' . t('nav_logout'), 'info');

