<?php
declare(strict_types=1);

/**
 * Google OAuth configuration loader.
 */
function loadGoogleOAuthConfig(): array {
    $config = [
        'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
        'redirect_uri' => getenv('GOOGLE_REDIRECT_URI') ?: ''
    ];

    $localConfigPath = __DIR__ . '/google_oauth.local.php';
    if (file_exists($localConfigPath)) {
        $localConfig = require $localConfigPath;
        if (is_array($localConfig)) {
            foreach (['client_id', 'client_secret', 'redirect_uri'] as $key) {
                if (isset($localConfig[$key]) && is_string($localConfig[$key]) && $localConfig[$key] !== '') {
                    $config[$key] = $localConfig[$key];
                }
            }
        }
    }

    $missing = [];
    foreach (['client_id', 'client_secret', 'redirect_uri'] as $key) {
        if ($config[$key] === '') {
            $missing[] = $key;
        }
    }

    if ($missing) {
        throw new RuntimeException('Missing Google OAuth config: ' . implode(', ', $missing));
    }

    return $config;
}
