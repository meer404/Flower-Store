<?php
declare(strict_types=1);

/**
 * Logout Handler
 * Bloom & Vine Flower Store
 */

require_once __DIR__ . '/src/language.php';
require_once __DIR__ . '/src/functions.php';

// Destroy session
session_destroy();

// Start new session for flash message
session_start();
redirect('index.php', t('login_title') . ' - ' . t('nav_logout'), 'info');

