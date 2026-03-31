<?php
declare(strict_types=1);

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';
require_once __DIR__ . '/src/config/google_oauth.php';
require_once __DIR__ . '/vendor/autoload.php';

$redirectTarget = safeRedirectTarget((string)($_GET['redirect'] ?? ''), 'index.php');

if (isLoggedIn()) {
    header('Location: ' . $redirectTarget);
    exit;
}

$config = loadGoogleOAuthConfig();

$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;
$_SESSION['oauth_redirect'] = $redirectTarget;

$client = new Google\Client();
$client->setClientId($config['client_id']);
$client->setClientSecret($config['client_secret']);
$client->setRedirectUri($config['redirect_uri']);
$client->setScopes(['email', 'profile']);
$client->setState($state);
$client->setPrompt('select_account');

$authUrl = $client->createAuthUrl();
header('Location: ' . $authUrl);
exit;
